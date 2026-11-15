// Testi del sito in italiano. Le chiavi sono le stesse in en.mjs ed es.mjs.
export default {
    lang: 'it',
    percorso: '/',
    meta: {
        titolo: 'Housekeep Hotel – La comunicazione semplice tra reception e addette alle pulizie',
        descrizione: 'Web app per hotel: le addette toccano la camera, la reception sa subito quando è pronta. Niente password, funziona senza Wi-Fi, in 6 lingue. 3 mesi gratis.',
    },
    nav: { come: 'Come funziona', addette: 'Addette', reception: 'Reception', prezzi: 'Prezzi', faq: 'Domande', cta: 'Prova gratis', accedi: 'Accedi' },

    hero: {
        etichetta: 'Promo lancio: 3 mesi gratis',
        titolo: 'Comunica facilmente con le addette ai piani',
        sottotitolo: 'Basta una web app: niente da installare e niente password. L\'addetta tocca la camera, la reception sa subito quando è pronta.',
        cta1: 'Prova gratis 3 mesi',
        cta2: 'Richiedi una demo',
        nota: 'Senza carta di credito · Pronto in un giorno',
    },

    problemi: {
        titolo: 'Basta messaggi persi e corse in corridoio',
        sottotitolo: 'Oggi le camere pronte passano da chat, telefonate e walkie-talkie. Con Housekeep è tutto in un posto solo.',
        voci: [
            ['messaggi', 'Nessun messaggio perso', 'Ogni camera e ogni segnalazione resta sul tabellone: niente si perde tra le chat di gruppo.'],
            ['pronta', 'Sai subito quando la camera è pronta', 'L\'addetta tocca, la tessera diventa verde e in reception suona un avviso. In tempo reale.'],
            ['radio', 'Niente walkie-talkie né telefonate', 'Non serve cercare nessuno: lo stato di tutte le camere è sempre sotto gli occhi.'],
            ['whatsapp', 'Collegato a WhatsApp', 'Inoltri una segnalazione al manutentore con un tocco e avvisi l\'addetta delle urgenze.'],
        ],
    },

    come: {
        titolo: 'Come funziona',
        sottotitolo: 'Tre gesti, zero formazione.',
        passi: [
            ['L\'addetta tocca la camera', 'Un tocco quando inizia (giallo), uno quando ha finito (verde). Funziona anche dove non c\'è campo.', 'telefono'],
            ['La reception vede tutto in tempo reale', 'Il tabellone si aggiorna da solo e suona quando una camera è pronta o arriva una segnalazione.', 'tabellone'],
            ['Un problema? Basta una foto', 'Il pulsante rosso apre la fotocamera: la segnalazione arriva subito in reception, pronta da inoltrare su WhatsApp.', 'segnalazione'],
        ],
    },

    addette: {
        titolo: 'Facile per tutte, dal primo giorno',
        sottotitolo: 'Pensata per chi non ama le app: tasti grandi, colori chiari, pochissime parole.',
        voci: [
            ['Nessuna password', 'Ogni addetta ha il suo QR code personale: lo inquadra e la sua pagina si apre.'],
            ['Funziona senza Wi-Fi', 'Nei corridoi senza campo i tocchi restano sul telefono e partono da soli quando torna la rete.'],
            ['Tasti grandi, anche con i guanti', 'Numeri enormi e colori: grigio da fare, giallo in pulizia, verde pronta.'],
            ['Nella sua lingua', 'La reception sceglie la lingua per ogni addetta.'],
            ['Si installa come un\'app', 'Un\'icona sulla schermata Home, senza passare dagli store.'],
            ['I prossimi giorni a colpo d\'occhio', 'Con il calendario vede le camere previste per la settimana.'],
        ],
        lingue: 'Italiano · English · Română · Українська · Español · বাংলা',
    },

    reception: {
        titolo: 'Il controllo della giornata, da un solo schermo',
        sottotitolo: 'Per la reception e la governante: su PC o tablet, senza installare nulla.',
        voci: [
            ['Tabellone in tempo reale', 'Tutte le camere colorate per stato, con l\'addetta e l\'orario. Filtri per piano e per addetta.'],
            ['Avvisi sonori e notifiche', 'Un suono quando una camera è pronta, uno più forte quando arriva una segnalazione.'],
            ['Importa dal gestionale', 'Carichi il file delle prenotazioni: cambi e riassetti si creano da soli, alla stessa addetta.'],
            ['Giornata pronta in un minuto', 'Assegni le camere con un tocco, distribuisci in automatico, gestisci i giorni liberi.'],
            ['Inoltro su WhatsApp', 'La segnalazione con la foto va al manutentore, l\'urgenza all\'addetta: nella sua lingua.'],
            ['Statistiche', 'Tempi medi di pulizia, camere per addetta, segnalazioni risolte.'],
        ],
    },

    prezzi: {
        titolo: 'Prezzi semplici, nessun vincolo',
        sottotitolo: 'Un abbonamento annuale, un\'unica fattura. Le addette sono illimitate.',
        promo: 'Promo lancio: <b>3 mesi gratis</b>. Basta registrarsi, senza carta di credito.',
        mese: '/anno',
        piani: [
            ['Small', '199 €', 'fino a 35 camere', 'addette illimitate'],
            ['Medium', '379 €', 'da 36 a 70 camere', 'addette illimitate'],
            ['Large', 'Su richiesta', 'oltre 70 camere', 'addette illimitate'],
        ],
        inclusi: ['1 receptionist inclusa', 'Tabellone e app per le addette', 'Import dal gestionale', 'Inoltro su WhatsApp', 'Assistenza via email'],
        consigliato: 'Il più scelto',
        note: 'Attivazione hotel 99 € una tantum · Receptionist in più 60 €/anno · Assistenza telefonica e WhatsApp 100 €/anno · Prezzi IVA esclusa',
        cta: 'Inizia la prova gratuita',
    },

    faq: {
        titolo: 'Domande frequenti',
        voci: [
            ['Bisogna installare un\'app?', 'No: è una web app. Si apre dal QR code e, se si vuole, si aggiunge alla schermata Home come un\'app normale.'],
            ['Le addette devono creare un account?', 'No, niente email né password: ognuna ha il suo QR code personale. Se un telefono si perde, la reception rigenera il codice.'],
            ['E se nel corridoio non c\'è campo?', 'L\'app funziona lo stesso: i tocchi restano sul telefono e partono da soli appena torna la rete. Nessuna azione va persa.'],
            ['Funziona con il nostro gestionale?', 'Importa le prenotazioni da un file CSV, che quasi tutti i gestionali esportano. Cambi e riassetti si creano da soli.'],
            ['Serve un telefono aziendale?', 'No. Va bene il telefono dell\'addetta, Android o iPhone, oppure un tablet in dotazione al piano.'],
            ['Le foto delle segnalazioni sono al sicuro?', 'Le vede solo lo staff dell\'hotel e si cancellano da sole dopo 90 giorni. Il link inoltrato su WhatsApp scade dopo 30 giorni.'],
            ['Come funziona la prova gratuita?', 'Ci scrivi, attiviamo il tuo hotel e ti aiutiamo a partire. Per 3 mesi non paghi nulla e non serve la carta di credito. Alla fine ti mandiamo i dati per il pagamento annuale: decidi tu se continuare.'],
        ],
    },

    prova: {
        titolo: 'Prova Housekeep gratis per 3 mesi',
        sottotitolo: 'Compila il modulo: ti ricontattiamo entro un giorno lavorativo e attiviamo il tuo hotel.',
        campi: {
            nome: 'Nome e cognome', hotel: 'Nome dell\'hotel', citta: 'Città', email: 'Email', telefono: 'Telefono',
            camere: 'Numero di camere', interesse: 'Cosa preferisci?', prova: 'Attivare la prova gratuita', demo: 'Una demo guidata',
            messaggio: 'Messaggio (facoltativo)', privacy: 'Ho letto l\'<a href="{privacy}">informativa privacy</a> e acconsento a essere ricontattato.',
            invia: 'Invia la richiesta',
        },
        errore: 'Qualcosa non è andato: controlla i campi obbligatori e la spunta della privacy, poi riprova.',
        oppure: 'Preferisci parlarne subito?',
        whatsapp: 'Scrivici su WhatsApp',
    },

    footer: { privacy: 'Privacy', app: 'Accedi all\'app', diritti: 'Tutti i diritti riservati.' },

    grazie: {
        titolo: 'Grazie, richiesta ricevuta!',
        testo: 'Ti ricontattiamo entro un giorno lavorativo all\'email che ci hai lasciato. Se hai fretta, scrivici su WhatsApp.',
        torna: 'Torna al sito',
    },

    privacy: {
        titolo: 'Informativa privacy',
        aggiornata: 'Ultimo aggiornamento: novembre 2026',
        sezioni: [
            ['Titolare del trattamento', 'Caorle.it S.r.l., Via Giovanni Vescovo 2, 30021 Caorle (VE), email {email}.'],
            ['Quali dati raccogliamo', 'Con il modulo "Prova gratis": nome, nome e città dell\'hotel, email, telefono, numero di camere ed eventuale messaggio. Il sito non usa cookie di profilazione né strumenti di statistica di terze parti, e i caratteri sono ospitati sul nostro server.'],
            ['Perché li usiamo', 'Solo per ricontattarti, attivare la prova gratuita e rispondere alle tue domande (base giuridica: misure precontrattuali richieste dall\'interessato, art. 6.1.b GDPR).'],
            ['Per quanto tempo', 'Per il tempo necessario a gestire la richiesta e, se non diventi cliente, non oltre 24 mesi.'],
            ['Chi li tratta', 'Il personale di Caorle.it S.r.l. e il fornitore di hosting, in qualità di responsabile del trattamento. I dati non vengono venduti né ceduti a terzi per fini commerciali.'],
            ['I tuoi diritti', 'Puoi chiedere in qualsiasi momento accesso, rettifica, cancellazione, limitazione o portabilità dei tuoi dati e opporti al trattamento scrivendo a {email}. Puoi anche proporre reclamo al Garante per la protezione dei dati personali (www.garanteprivacy.it).'],
        ],
    },
};
