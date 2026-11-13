// Preparazione del giorno: si sceglie uno "strumento" e si toccano le camere.
import { invia } from './invia';

const TIPI = [
    { valore: 'partenza', nome: 'Cambio', icona: '🧳' },
    { valore: 'fermata', nome: 'Riassetto', icona: '🛏' },
    { valore: 'extra', nome: 'Extra', icona: '★' },
];

export default () => {
    const cfg = JSON.parse(document.getElementById('giornata-config').textContent);
    let coda = Promise.resolve();

    return {
        tipi: TIPI,
        camere: cfg.stato.camere,
        addette: cfg.stato.addette,
        strumento: cfg.stato.addette.length
            ? { tipo: 'assegna', staff_id: cfg.stato.addette[0].id }
            : { tipo: 'tipo', valore: 'partenza' },
        messaggio: null,
        avvisi: [],
        occupato: false,
        incollaTesto: '',
        incollaTipo: 'partenza',
        incollaAddetta: '',
        distribuisciA: cfg.stato.addette.map((a) => a.id),

        get piani() {
            const per = new Map();
            for (const c of this.camere) {
                if (!per.has(c.piano)) per.set(c.piano, []);
                per.get(c.piano).push(c);
            }
            return [...per].map(([numero, camere]) => ({ numero, camere }));
        },
        get nelGiorno() {
            return this.camere.filter((c) => c.giorno).length;
        },

        conta(staffId) {
            return this.camere.filter((c) => c.giorno && c.giorno.staff_id === staffId).length;
        },
        contaTipo(tipo) {
            return this.camere.filter((c) => c.giorno && c.giorno.tipo === tipo).length;
        },
        addetta(id) {
            return this.addette.find((a) => a.id === id);
        },
        nomeAddetta(id) {
            return this.addetta(id)?.nome ?? '—';
        },
        iconaTipo(t) {
            return TIPI.find((x) => x.valore === t)?.icona ?? '';
        },
        eAttivo(tipo, valore) {
            const s = this.strumento;
            if (s.tipo !== tipo) return false;
            if (tipo === 'assegna') return s.staff_id === valore;
            if (tipo === 'tipo') return s.valore === valore;
            return true;
        },
        stile(c) {
            if (!c.giorno) return '';
            const a = this.addetta(c.giorno.staff_id);
            return a ? `border-color:${a.colore}; background:${a.colore}1f` : 'border-color:#64748b; background:#fff';
        },

        vaiAl(data) {
            if (data) window.location = `${cfg.urls.pagina}?data=${data}`;
        },

        tocca(c) {
            this.applicaA([c]);
        },

        applicaA(camere) {
            const s = this.strumento;
            const dati = { room_ids: camere.map((c) => c.id), azione: s.tipo };
            // Aggiornamento immediato sullo schermo, poi conferma dal server.
            for (const c of camere) {
                if (s.tipo === 'togli') {
                    if (c.giorno && c.giorno.stato === 'da_fare') c.giorno = null;
                } else {
                    c.giorno ??= { tipo: 'partenza', stato: 'da_fare', staff_id: null, urgente: false };
                    if (s.tipo === 'assegna') c.giorno.staff_id = s.staff_id;
                    if (s.tipo === 'tipo') c.giorno.tipo = s.valore;
                }
            }
            if (s.tipo === 'assegna') dati.staff_id = s.staff_id;
            if (s.tipo === 'tipo') dati.tipo = s.valore;
            this.esegui(() => invia(cfg.urls.applica, dati));
        },

        incolla() {
            if (!this.incollaTesto.trim()) return;
            this.esegui(
                () => invia(cfg.urls.incolla, {
                    testo: this.incollaTesto,
                    tipo: this.incollaTipo,
                    staff_id: this.incollaAddetta ? Number(this.incollaAddetta) : null,
                }),
                () => { this.incollaTesto = ''; },
            );
        },

        distribuisci() {
            this.esegui(() => invia(cfg.urls.distribuisci, { staff_ids: this.distribuisciA }));
        },

        copiaIeri() {
            this.esegui(() => invia(cfg.urls.copiaIeri));
        },

        // Le richieste partono una alla volta, nell'ordine dei tocchi.
        esegui(richiesta, dopo) {
            this.occupato = true;
            coda = coda.then(async () => {
                try {
                    const r = await richiesta();
                    this.camere = r.camere;
                    this.addette = r.addette;
                    this.avvisi = r.avvisi || [];
                    this.messaggio = r.messaggio || null;
                    dopo?.();
                } catch (e) {
                    this.avvisi = ['Non salvato: ' + e.message + '. Ricarica la pagina.'];
                } finally {
                    this.occupato = false;
                }
            });
        },
    };
};
