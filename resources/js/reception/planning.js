// Planning delle prenotazioni: righe = camere, colonne = 14 giorni.
// Ogni soggiorno è una barra da metà del giorno di arrivo a metà del giorno di partenza.
import { invia } from './invia';

const GIORNO_MS = 86400000;

function giorni(da, a) {
    return Math.round((Date.parse(a + 'T00:00:00Z') - Date.parse(da + 'T00:00:00Z')) / GIORNO_MS);
}

function piu(data, n) {
    return new Date(Date.parse(data + 'T00:00:00Z') + n * GIORNO_MS).toISOString().slice(0, 10);
}

/** Stessa regola del server (Stay::pulizia). */
function pulizia(s, data) {
    if (data === s.partenza) return 'partenza';
    const d = giorni(s.arrivo, data);
    if (d <= 0 || data > s.partenza || s.riassetto_ogni < 1) return null;
    return d % s.riassetto_ogni === 0 ? 'fermata' : null;
}

const COLORI = ['#2563eb', '#9333ea', '#0d9488', '#ea580c', '#db2777', '#4f46e5', '#0891b2', '#a16207'];

export default () => {
    const cfg = JSON.parse(document.getElementById('planning-config').textContent);
    const N = cfg.giorni;

    return {
        camere: cfg.camere,
        soggiorni: cfg.soggiorni,
        frequenze: cfg.frequenze,
        dal: cfg.dal,
        oggi: cfg.oggi,
        modulo: null,      // soggiorno in modifica (o nuovo)
        errore: null,
        salvando: false,

        get colonne() {
            return Array.from({ length: N }, (_, i) => {
                const data = piu(this.dal, i);
                const d = new Date(data + 'T12:00:00');
                return {
                    data,
                    giorno: d.toLocaleDateString('it-IT', { weekday: 'short' }),
                    numero: d.getDate(),
                    mese: d.toLocaleDateString('it-IT', { month: 'short' }),
                    festivo: [0, 6].includes(d.getDay()),
                    oggi: data === this.oggi,
                };
            });
        },

        soggiorniDi(roomId) {
            return this.soggiorni.filter((s) => s.room_id === roomId);
        },

        /** Posizione della barra in mezze colonne (2 per giorno), tagliata ai bordi. */
        barra(s) {
            const inizio = Math.max(0, giorni(this.dal, s.arrivo) * 2 + 1);
            const fine = Math.min(N * 2, giorni(this.dal, s.partenza) * 2 + 1);
            const colore = COLORI[s.id % COLORI.length];
            return `grid-column: ${inizio + 1} / ${fine + 1}; background:${colore}`;
        },

        etichetta(s) {
            return [s.riferimento, s.pax ? s.pax + ' pax' : null, s.riassetto_ogni ? '🛏 ogni ' + s.riassetto_ogni + 'g' : 'no riassetto']
                .filter(Boolean).join(' · ');
        },

        /** 🧳 o 🛏 previsti per quella camera in quel giorno. */
        icona(roomId, data) {
            for (const s of this.soggiorniDi(roomId)) {
                const p = pulizia(s, data);
                if (p === 'partenza') return '🧳';
                if (p === 'fermata') return '🛏';
            }
            return '';
        },

        nuovo(camera, data) {
            this.errore = null;
            this.modulo = {
                id: null, room_id: camera.id, arrivo: data, partenza: piu(data, 1),
                riassetto_ogni: 2, riferimento: '', pax: '', nota: '',
            };
        },

        modifica(s) {
            this.errore = null;
            this.modulo = { ...s, riferimento: s.riferimento ?? '', pax: s.pax ?? '', nota: s.nota ?? '' };
        },

        notti() {
            return this.modulo ? Math.max(0, giorni(this.modulo.arrivo, this.modulo.partenza)) : 0;
        },

        async salva() {
            const m = this.modulo;
            const dati = {
                dal: this.dal, room_id: Number(m.room_id), arrivo: m.arrivo, partenza: m.partenza,
                riassetto_ogni: Number(m.riassetto_ogni), riferimento: m.riferimento || null,
                pax: m.pax ? Number(m.pax) : null, nota: m.nota || null,
            };
            await this.esegui(() => m.id
                ? invia(cfg.urls.aggiorna.replace('__ID__', m.id), dati, 'PUT')
                : invia(cfg.urls.crea, dati));
        },

        async cancella() {
            if (!confirm('Cancellare questa prenotazione? Le pulizie previste e non ancora iniziate verranno tolte.')) return;
            await this.esegui(() => invia(cfg.urls.aggiorna.replace('__ID__', this.modulo.id), { dal: this.dal }, 'DELETE'));
        },

        async esegui(richiesta) {
            this.salvando = true;
            this.errore = null;
            try {
                const r = await richiesta();
                this.soggiorni = r.soggiorni;
                this.modulo = null;
            } catch (e) {
                this.errore = e.message;
            } finally {
                this.salvando = false;
            }
        },

        vai(giorniDiSpostamento) {
            window.location = `${cfg.urls.pagina}?dal=${piu(this.dal, giorniDiSpostamento)}`;
        },
    };
};
