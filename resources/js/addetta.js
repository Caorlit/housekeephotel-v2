// Schermata delle addette: /s/{token}
//
// Ogni tocco cambia subito il colore (anche senza rete) e mette l'azione in coda
// (localStorage). L'azione parte dopo 5 secondi, così si può annullare.
// La coda si svuota da sola quando torna la rete.

import Alpine from 'alpinejs';
import { comprimi } from './addetta/foto';
import * as archivio from './addetta/archivio';

const ANNULLA_MS = 5000;
const INVIO_OGNI_MS = 2000;
const AGGIORNA_OGNI_MS = 20000;
const PROSSIMO = { da_fare: 'in_pulizia', in_pulizia: 'pronta' };

function uuid() {
    if (crypto.randomUUID) return crypto.randomUUID();
    const b = crypto.getRandomValues(new Uint8Array(16));
    b[6] = (b[6] & 0x0f) | 0x40;
    b[8] = (b[8] & 0x3f) | 0x80;
    const h = [...b].map((x) => x.toString(16).padStart(2, '0')).join('');
    return `${h.slice(0, 8)}-${h.slice(8, 12)}-${h.slice(12, 16)}-${h.slice(16, 20)}-${h.slice(20)}`;
}

function leggi(chiave) {
    try { return JSON.parse(localStorage.getItem(chiave)); } catch { return null; }
}

function scrivi(chiave, valore) {
    try { localStorage.setItem(chiave, JSON.stringify(valore)); } catch { /* spazio pieno o modalità privata */ }
}

function vibra(ms = 30) {
    try { navigator.vibrate?.(ms); } catch { /* niente */ }
}

async function richiesta(url, opzioni = {}, timeoutMs = 15000) {
    const ctrl = new AbortController();
    const timer = setTimeout(() => ctrl.abort(), timeoutMs);
    try {
        return await fetch(url, {
            ...opzioni,
            signal: ctrl.signal,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', ...(opzioni.headers || {}) },
        });
    } finally {
        clearTimeout(timer);
    }
}

Alpine.data('addetta', () => {
    const cfg = JSON.parse(document.getElementById('hk-config').textContent);
    const K_CODA = `hk_coda_${cfg.staffId}`;
    const K_DATI = `hk_dati_${cfg.staffId}`;

    return {
        t: cfg.t,
        ANNULLA_MS,
        base: [],          // le camere come le conosce il server
        camere: [],        // base + azioni in coda (quello che si vede)
        coda: [],
        versione: null,
        online: navigator.onLine,
        inviando: false,
        annullabile: null,
        timerAnnulla: null,
        daRiaprire: null,
        notaAperta: null,
        problema: null,
        avviso: null,

        get inAttesa() {
            return this.coda.length;
        },

        init() {
            this.coda = Array.isArray(leggi(K_CODA)) ? leggi(K_CODA) : [];

            // Se la pagina arriva dalla cache (offline), i dati salvati sono più recenti.
            const salvati = leggi(K_DATI);
            const dati = salvati && salvati.data === cfg.dati.data && salvati.generato > cfg.dati.generato
                ? salvati : cfg.dati;
            this.applicaServer(dati);

            window.addEventListener('online', () => { this.online = true; this.invia(); this.aggiorna(); });
            window.addEventListener('offline', () => { this.online = false; });
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') { this.invia(); this.aggiorna(); }
            });
            setInterval(() => this.invia(), INVIO_OGNI_MS);
            setInterval(() => this.aggiorna(), AGGIORNA_OGNI_MS);

            this.registraServiceWorker();
            this.invia();
        },

        // --- Stato -----------------------------------------------------------

        applicaServer(dati) {
            this.base = dati.camere;
            this.versione = dati.versione;
            scrivi(K_DATI, dati);
            this.ricalcola();
        },

        ricalcola() {
            const camere = this.base.map((c) => ({ ...c }));
            const perId = new Map(camere.map((c) => [c.id, c]));
            for (const a of this.coda) {
                const c = perId.get(a.room_day_id);
                if (!c) continue;
                if (a.tipo === 'stato') c.stato = a.stato;
                if (a.tipo === 'segnalazione') c.segnalazioni += 1;
            }
            this.camere = camere;
        },

        salvaCoda() {
            scrivi(K_CODA, this.coda);
        },

        // --- Tocchi sulle tessere ----------------------------------------------

        tocca(c) {
            if (c.stato === 'pronta') {
                this.daRiaprire = c;
                return;
            }
            this.aggiungiAzione(c, PROSSIMO[c.stato]);
        },

        confermaRiapri() {
            const c = this.daRiaprire;
            this.daRiaprire = null;
            if (c) this.aggiungiAzione(c, 'in_pulizia');
        },

        aggiungiAzione(c, stato) {
            const adesso = Date.now();
            const azione = {
                id: uuid(), tipo: 'stato', room_day_id: c.id, numero: c.numero,
                stato, ts: adesso, dopo: adesso + ANNULLA_MS,
            };
            this.coda.push(azione);
            this.salvaCoda();
            this.ricalcola();
            vibra(stato === 'pronta' ? [40, 60, 40] : 30);

            // null e poi la nuova azione: così la barra dei 5 secondi riparte da capo.
            this.annullabile = null;
            this.$nextTick(() => { this.annullabile = azione; });
            clearTimeout(this.timerAnnulla);
            this.timerAnnulla = setTimeout(() => {
                if (this.annullabile?.id === azione.id) this.annullabile = null;
                this.invia();
            }, ANNULLA_MS);
        },

        annulla() {
            const a = this.annullabile;
            if (!a) return;
            this.coda = this.coda.filter((x) => x.id !== a.id);
            this.salvaCoda();
            this.ricalcola();
            this.annullabile = null;
            clearTimeout(this.timerAnnulla);
            vibra(15);
        },

        // --- Pulsante rosso ----------------------------------------------------

        apriProblema() {
            this.problema = { camera: null, nota: '', blob: null, anteprima: null };
            if (this.camere.length === 1) this.scegliCamera(this.camere[0]);
        },

        scegliCamera(c) {
            this.problema.camera = c;
            // Apre subito la fotocamera (siamo ancora dentro il tocco dell'utente).
            this.$refs.foto.click();
        },

        async fotoScelta(evento) {
            const file = evento.target.files?.[0];
            evento.target.value = '';
            if (!file || !this.problema) return;
            const blob = await comprimi(file);
            if (!this.problema) return;
            if (this.problema.anteprima) URL.revokeObjectURL(this.problema.anteprima);
            this.problema.blob = blob;
            this.problema.anteprima = URL.createObjectURL(blob);
        },

        async inviaProblema() {
            const p = this.problema;
            if (!p?.camera || (!p.blob && !p.nota?.trim())) return;
            const adesso = Date.now();
            const azione = {
                id: uuid(), tipo: 'segnalazione', room_day_id: p.camera.id, numero: p.camera.numero,
                nota: p.nota.trim(), conFoto: !!p.blob, ts: adesso, dopo: adesso,
            };
            if (p.blob) await archivio.salva(azione.id, p.blob);
            this.coda.push(azione);
            this.salvaCoda();
            this.ricalcola();
            this.chiudiProblema();
            vibra([40, 60, 40]);
            this.mostraAvviso('✓ ' + this.t.inviata);
            this.invia();
        },

        chiudiProblema() {
            if (this.problema?.anteprima) URL.revokeObjectURL(this.problema.anteprima);
            this.problema = null;
        },

        mostraAvviso(testo) {
            this.avviso = testo;
            setTimeout(() => { this.avviso = null; }, 2200);
        },

        // --- Sincronizzazione --------------------------------------------------

        togliDallaCoda(ids) {
            const set = new Set(ids);
            this.coda = this.coda.filter((a) => !set.has(a.id));
            this.salvaCoda();
        },

        gestisciErrore(risposta) {
            if (risposta.status === 401 || risposta.status === 404) {
                // PIN richiesto o link non più valido: la pagina mostrerà cosa fare.
                window.location.reload();
                return true;
            }
            return false;
        },

        async invia() {
            if (this.inviando) return;
            const adesso = Date.now();
            const pronte = this.coda.filter((a) => a.dopo <= adesso);
            if (pronte.length === 0) return;

            this.inviando = true;
            try {
                const stati = pronte.filter((a) => a.tipo === 'stato');
                if (stati.length) {
                    const r = await richiesta(cfg.urls.azioni, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            azioni: stati.map((a) => ({
                                client_action_id: a.id, room_day_id: a.room_day_id, stato: a.stato, ts: a.ts,
                            })),
                        }),
                    });
                    if (r.ok) {
                        this.online = true;
                        this.togliDallaCoda(stati.map((a) => a.id));
                        this.applicaServer(await r.json());
                    } else if (!this.gestisciErrore(r) && r.status === 422) {
                        this.togliDallaCoda(stati.map((a) => a.id)); // dati non validi: inutile riprovare
                        this.ricalcola();
                    }
                }

                for (const a of pronte.filter((x) => x.tipo === 'segnalazione')) {
                    const form = new FormData();
                    form.append('client_action_id', a.id);
                    form.append('room_day_id', a.room_day_id);
                    form.append('ts', a.ts);
                    if (a.nota) form.append('nota', a.nota);
                    if (a.conFoto) {
                        const blob = await archivio.leggi(a.id);
                        if (blob) form.append('foto', blob, 'foto.jpg');
                        else if (!a.nota) { this.togliDallaCoda([a.id]); continue; } // foto persa
                    }
                    const r = await richiesta(cfg.urls.segnalazioni, { method: 'POST', body: form }, 90000);
                    if (r.ok || r.status === 422) {
                        this.online = true;
                        this.togliDallaCoda([a.id]);
                        await archivio.elimina(a.id);
                    } else if (this.gestisciErrore(r)) {
                        break;
                    }
                }
                if (pronte.some((a) => a.tipo === 'segnalazione')) await this.aggiorna(true);
            } catch {
                this.online = false; // rete assente: si riprova tra poco
            } finally {
                this.inviando = false;
                this.ricalcola();
            }
        },

        async aggiorna(forza = false) {
            try {
                const url = cfg.urls.camere + (this.versione && !forza ? `?versione=${this.versione}` : '');
                const r = await richiesta(url);
                if (r.status === 204) { this.online = true; return; }
                if (r.ok) {
                    this.online = true;
                    this.applicaServer(await r.json());
                } else {
                    this.gestisciErrore(r);
                }
            } catch {
                this.online = false;
            }
        },

        // --- PWA ---------------------------------------------------------------

        async registraServiceWorker() {
            if (!('serviceWorker' in navigator)) return;
            try {
                const reg = await navigator.serviceWorker.register('/sw.js', { scope: '/s/' });
                await navigator.serviceWorker.ready;
                // Mette in cache la pagina e i suoi file, così si apre anche senza rete.
                const urls = [
                    location.pathname,
                    ...[...document.querySelectorAll('script[src], link[rel="stylesheet"], link[rel="manifest"]')]
                        .map((el) => el.src || el.href),
                    '/icons/icon-192.png',
                ];
                (reg.active || navigator.serviceWorker.controller)?.postMessage({ tipo: 'precache', urls });
            } catch { /* niente PWA: l'app funziona lo stesso */ }
        },
    };
});

window.Alpine = Alpine;
Alpine.start();
