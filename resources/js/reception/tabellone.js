// Tabellone della reception: si aggiorna ogni 5 secondi e avvisa con suono + notifica
// quando una camera diventa pronta o arriva una segnalazione.
import { invia } from './invia';

const OGNI_MS = 5000;
const K_SUONI = 'hk_suoni';

// --- Suoni (generati nel browser, niente file audio) --------------------------

let audio = null;

function contestoAudio() {
    audio ??= new (window.AudioContext || window.webkitAudioContext)();
    if (audio.state === 'suspended') audio.resume();
    return audio;
}

function tono(freq, inizio, durata, volume = 0.25, forma = 'sine') {
    const ctx = contestoAudio();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.type = forma;
    osc.frequency.value = freq;
    const t = ctx.currentTime + inizio;
    gain.gain.setValueAtTime(0.0001, t);
    gain.gain.exponentialRampToValueAtTime(volume, t + 0.02);
    gain.gain.exponentialRampToValueAtTime(0.0001, t + durata);
    osc.connect(gain).connect(ctx.destination);
    osc.start(t);
    osc.stop(t + durata + 0.05);
}

/** Camera pronta: "din-don" breve e gentile. */
function suonoPronta() {
    tono(880, 0, 0.18);
    tono(1320, 0.16, 0.3);
}

/** Segnalazione: tre bip più forti e insistenti. */
function suonoSegnalazione() {
    for (let i = 0; i < 3; i++) {
        tono(740, i * 0.32, 0.14, 0.45, 'square');
        tono(520, i * 0.32 + 0.15, 0.14, 0.45, 'square');
    }
}

function notifica(titolo, corpo) {
    try {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(titolo, { body: corpo, icon: '/icons/icon-192.png', tag: titolo });
        }
    } catch { /* niente */ }
}

export default () => {
    const cfg = JSON.parse(document.getElementById('tabellone-config').textContent);

    return {
        camere: [],
        segnalazioni: [],
        addette: [],
        versione: null,
        filtroPiano: '',
        filtroAddetta: '',
        aperta: null,          // camera selezionata (pannello)
        notaBozza: '',
        suoniAttivi: false,
        audioBloccato: false,
        connessione: true,
        ultimoAggiornamento: null,
        fotoGrande: null,
        statiPrima: new Map(),
        segnalazioniViste: new Set(),

        init() {
            try { this.suoniAttivi = localStorage.getItem(K_SUONI) === '1'; } catch { /* niente */ }
            this.applica(cfg.dati, false);
            // Il browser blocca l'audio finché non si tocca la pagina: al primo clic si sblocca.
            if (this.suoniAttivi) {
                this.audioBloccato = true;
                document.addEventListener('click', () => { contestoAudio(); this.audioBloccato = false; }, { once: true });
            }
            setInterval(() => this.aggiorna(), OGNI_MS);
            document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' && this.aggiorna());
        },

        // --- Dati ---------------------------------------------------------------

        get piani() {
            return [...new Set(this.camere.map((c) => c.piano))];
        },
        get visibili() {
            return this.camere.filter((c) =>
                (this.filtroPiano === '' || c.piano === Number(this.filtroPiano)) &&
                (this.filtroAddetta === '' ||
                    (this.filtroAddetta === 'nessuna' ? c.staff_id === null : c.staff_id === Number(this.filtroAddetta))));
        },
        conta(stato) {
            return this.camere.filter((c) => c.stato === stato).length;
        },
        get aperte() {
            return this.segnalazioni.filter((s) => s.stato === 'aperta').length;
        },
        segnalazioniDi(c) {
            return this.segnalazioni.filter((s) => s.room_day_id === c.id);
        },

        applica(dati, avvisa = true) {
            if (avvisa) this.confronta(dati);
            this.camere = dati.camere;
            this.segnalazioni = dati.segnalazioni;
            this.addette = dati.addette;
            this.versione = dati.versione;
            this.statiPrima = new Map(dati.camere.map((c) => [c.id, c.stato]));
            for (const s of dati.segnalazioni) this.segnalazioniViste.add(s.id);
            if (this.aperta) this.aperta = dati.camere.find((c) => c.id === this.aperta.id) ?? null;
            this.ultimoAggiornamento = new Date().toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.title = (this.aperte ? `(${this.aperte}) ` : '') + 'Tabellone · Housekeep';
        },

        /** Cosa è cambiato rispetto a prima: suoni e notifiche. */
        confronta(dati) {
            const pronte = dati.camere.filter((c) => c.stato === 'pronta' && this.statiPrima.has(c.id) && this.statiPrima.get(c.id) !== 'pronta');
            const nuove = dati.segnalazioni.filter((s) => !this.segnalazioniViste.has(s.id));

            for (const c of pronte.slice(0, 3)) notifica(`✅ Camera ${c.numero} pronta`, c.staff ? `da ${c.staff}` : '');
            for (const s of nuove.slice(0, 3)) notifica(`⚠️ Segnalazione camera ${s.numero}`, [s.staff, s.nota].filter(Boolean).join(': '));

            if (this.suoniAttivi) {
                if (nuove.length) suonoSegnalazione();
                else if (pronte.length) suonoPronta();
            }
        },

        async aggiorna() {
            try {
                const r = await fetch(`${cfg.urls.dati}?versione=${this.versione ?? ''}`, {
                    headers: { Accept: 'application/json' }, credentials: 'same-origin',
                });
                if (r.status === 401) { window.location.reload(); return; }
                this.connessione = r.ok;
                if (r.status === 200) this.applica(await r.json());
            } catch {
                this.connessione = false;
            }
        },

        // --- Suoni e notifiche ----------------------------------------------------

        async attivaSuoni() {
            contestoAudio();
            this.audioBloccato = false;
            this.suoniAttivi = true;
            try { localStorage.setItem(K_SUONI, '1'); } catch { /* niente */ }
            if ('Notification' in window && Notification.permission === 'default') {
                try { await Notification.requestPermission(); } catch { /* niente */ }
            }
            suonoPronta();
        },
        disattivaSuoni() {
            this.suoniAttivi = false;
            try { localStorage.setItem(K_SUONI, '0'); } catch { /* niente */ }
        },
        provaSegnalazione() {
            suonoSegnalazione();
        },

        // --- Azioni della reception ---------------------------------------------

        apri(c) {
            this.aperta = c;
            this.notaBozza = c.nota ?? '';
        },

        async esegui(url, dati) {
            try {
                this.applica(await invia(url, dati), false);
            } catch (e) {
                alert('Non salvato: ' + e.message);
            }
        },
        cambiaStato(c, stato) {
            c.stato = stato; // subito sullo schermo
            this.esegui(cfg.urls.stato.replace('__ID__', c.id), { stato });
        },
        cambiaUrgente(c) {
            this.esegui(cfg.urls.camera.replace('__ID__', c.id), { urgente: !c.urgente });
        },
        salvaNota(c) {
            this.esegui(cfg.urls.camera.replace('__ID__', c.id), { nota_reception: this.notaBozza.trim() || null });
        },
        riassegna(c, staffId) {
            this.esegui(cfg.urls.camera.replace('__ID__', c.id), { staff_id: staffId === '' ? null : Number(staffId) });
        },
        segnalazione(s, stato) {
            this.esegui(cfg.urls.segnalazione.replace('__ID__', s.id), { stato });
        },
    };
};
