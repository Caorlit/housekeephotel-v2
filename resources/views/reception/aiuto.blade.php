@extends('layouts.app')
@section('titolo', 'Aiuto')

@php
    $indice = [
        'inizio' => 'Come iniziare',
        'colori' => 'Colori e icone',
        'tabellone' => 'Tabellone',
        'giornata' => 'Preparazione del giorno',
        'prenotazioni' => 'Prenotazioni',
        'addette' => 'Addette',
        'camere' => 'Camere',
        'telefono' => 'Cosa vede l\'addetta sul telefono',
        'domande' => 'Domande frequenti',
    ];
@endphp

@section('content')
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold mb-1">❓ Aiuto</h1>
    <p class="text-slate-600 mb-4">Come si usa Housekeep, pagina per pagina. In ogni pagina il simbolo <span class="inline-flex size-6 items-center justify-center rounded-full bg-slate-200 text-sm font-bold">?</span> accanto al titolo porta qui alla spiegazione giusta.</p>

    <nav class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <p class="font-semibold mb-2">In questa pagina</p>
        <ol class="grid sm:grid-cols-2 gap-x-6 gap-y-1 list-decimal list-inside text-slate-700">
            @foreach ($indice as $ancora => $titolo)
                <li><a href="#{{ $ancora }}" class="underline">{{ $titolo }}</a></li>
            @endforeach
        </ol>
    </nav>

    <div class="space-y-8 [&_h2]:text-xl [&_h2]:font-bold [&_h2]:mb-3 [&_h2]:scroll-mt-4 [&_h3]:font-semibold [&_h3]:mt-4 [&_h3]:mb-1 [&_p]:mb-2 [&_li]:mb-1">

        {{-- 1 --}}
        <section id="inizio" class="bg-white rounded-xl shadow-sm p-5">
            <h2>1. Come iniziare</h2>
            <p>La prima volta, in quest'ordine (ogni passo serve a quello dopo):</p>
            <ol class="list-decimal list-inside space-y-1">
                <li><b>Camere</b>: inserisci tutte le camere dell'hotel. Esempio: <i>da 101 a 120, piano 1</i>, poi <i>da 201 a 220, piano 2</i>.</li>
                <li><b>Addette</b>: aggiungi ogni addetta con il nome e la <b>sua lingua</b>, poi stampa il suo foglio con il QR code e consegnaglielo.</li>
                <li><b>Prenotazioni</b>: importa il file CSV dal gestionale (oppure inserisci le prenotazioni a mano).</li>
                <li><b>Preparazione del giorno</b>: il primo giorno assegna le camere alle addette. Dal giorno dopo i riassetti vanno da soli alla stessa addetta.</li>
                <li><b>Tabellone</b>: tienilo aperto sul computer della reception e clicca la striscia gialla per attivare <b>suoni e notifiche</b>.</li>
            </ol>
            <p class="mt-2">Da lì in poi, ogni giorno: controlla la Preparazione del giorno al mattino (giorni liberi, camere extra) e segui il Tabellone.</p>
        </section>

        {{-- 2 --}}
        <section id="colori" class="bg-white rounded-xl shadow-sm p-5">
            <h2>2. Colori e icone</h2>
            <div class="grid sm:grid-cols-2 gap-2">
                <div class="flex items-center gap-3"><span class="size-8 rounded-lg bg-slate-200 shrink-0"></span> <span><b>Grigio</b> = da fare</span></div>
                <div class="flex items-center gap-3"><span class="size-8 rounded-lg bg-yellow-300 shrink-0"></span> <span><b>Giallo</b> = l'addetta sta pulendo</span></div>
                <div class="flex items-center gap-3"><span class="size-8 rounded-lg bg-green-600 shrink-0"></span> <span><b>Verde</b> = pronta</span></div>
                <div class="flex items-center gap-3"><span class="size-8 rounded-lg ring-4 ring-red-600 shrink-0"></span> <span><b>Bordo rosso</b> = urgente</span></div>
                <div class="flex items-center gap-3"><span class="text-2xl w-8 text-center">🧳</span> <span><b>Cambio</b>: l'ospite parte, pulizia completa</span></div>
                <div class="flex items-center gap-3"><span class="text-2xl w-8 text-center">🛏</span> <span><b>Riassetto</b>: l'ospite resta</span></div>
                <div class="flex items-center gap-3"><span class="text-2xl w-8 text-center">★</span> <span><b>Extra</b>: pulizia aggiunta a mano</span></div>
                <div class="flex items-center gap-3"><span class="text-2xl w-8 text-center">📝</span> <span>c'è una <b>nota</b> per l'addetta</span></div>
                <div class="flex items-center gap-3"><span class="size-8 rounded-full bg-red-600 text-white font-bold flex items-center justify-center shrink-0">1</span> <span>segnalazioni <b>aperte</b> sulla camera</span></div>
            </div>
        </section>

        {{-- 3 --}}
        <section id="tabellone" class="bg-white rounded-xl shadow-sm p-5">
            <h2>3. Tabellone</h2>
            <p>Mostra tutte le camere di <b>oggi</b> e si aggiorna da solo ogni 5 secondi: non serve ricaricare la pagina.</p>
            <h3>Suoni e notifiche</h3>
            <p>Clicca la striscia gialla <i>"🔔 Clicca qui per attivare suoni e notifiche"</i> e rispondi <b>Consenti</b> alla domanda del browser. Poi:</p>
            <ul class="list-disc list-inside">
                <li>camera pronta → suono breve <i>"din-don"</i> + notifica <i>"✅ Camera 104 pronta"</i>;</li>
                <li>nuova segnalazione → tre bip più forti + notifica <i>"⚠️ Segnalazione camera 108"</i>.</li>
            </ul>
            <p>Dopo aver ricaricato la pagina il browser blocca i suoni finché non clicchi un punto qualsiasi: un avviso te lo ricorda.</p>
            <h3>Cambiare una camera a mano</h3>
            <p>Clicca la camera: si apre una finestra dove puoi</p>
            <ul class="list-disc list-inside">
                <li>cambiare lo <b>stato</b> (es. l'addetta ha dimenticato di toccare "pronta");</li>
                <li>darla a un'altra <b>addetta</b>;</li>
                <li>segnarla <b>urgente</b> (es. ospite in arrivo presto): sul telefono dell'addetta va in cima con il bordo rosso;</li>
                <li>scrivere una <b>nota</b> (es. <i>"Culla da togliere"</i>): l'addetta la vede con 📝.</li>
            </ul>
            <h3>Segnalazioni</h3>
            <p>Arrivano nella colonna a destra con la foto (cliccala per ingrandirla). Clicca <b>👀 Presa in carico</b> quando qualcuno se ne occupa e <b>✓ Risolta</b> quando è sistemata: sparisce dalla colonna.</p>
            <p><b>📲 Inoltra su WhatsApp</b>: apre WhatsApp con il messaggio già scritto (camera, nota, chi l'ha segnalata e il link alla foto); scegli il contatto, ad esempio il manutentore, e premi invia. Il link alla foto funziona solo per chi lo riceve e scade dopo 30 giorni.</p>
            <h3>Avvisare un'addetta su WhatsApp</h3>
            <p>Se nella pagina Addette hai inserito il suo telefono, quando segni una camera <b>urgente</b> nel pannello compare <b>📲 Avvisa … su WhatsApp</b>: il messaggio è già scritto nella sua lingua. Utile quando ha il telefono in tasca.</p>
            <h3>Filtri</h3>
            <p>In alto puoi mostrare solo un piano o solo un'addetta (o le camere non assegnate).</p>
        </section>

        {{-- 4 --}}
        <section id="giornata" class="bg-white rounded-xl shadow-sm p-5">
            <h2>4. Preparazione del giorno</h2>
            <p>Qui decidi <b>quali camere</b> pulire in un giorno, <b>di che tipo</b> e <b>a chi</b> vanno. Si apre su oggi; con ◀ ▶ o il calendario scegli un altro giorno (anche domani, per prepararlo in anticipo).</p>
            <p>Le camere delle <b>prenotazioni</b> compaiono da sole (Cambio il giorno della partenza, Riassetto secondo la frequenza) e vanno all'ultima addetta che ha pulito quella camera.</p>
            <h3>I "pennelli": scegli e tocca</h3>
            <ol class="list-decimal list-inside">
                <li>In alto scegli cosa fare: un'<b>addetta</b> (es. <i>Maria</i>), <b>Nessuna</b>, un <b>tipo</b> (🧳 Cambio, 🛏 Riassetto, ★ Extra) oppure <b>✕ Togli dal giorno</b>.</li>
                <li>Tocca le camere: ricevono quello che hai scelto.</li>
            </ol>
            <p><b>Esempio:</b> scegli <i>Maria</i> e tocca 101, 102, 103 → sono di Maria. Scegli <i>🛏 Riassetto</i> e tocca 105 → la 105 diventa un riassetto. <i>"applica a tutto il piano"</i> fa lo stesso su un piano intero.</p>
            <p>Le camere tratteggiate non sono previste quel giorno: toccandole con un pennello si aggiungono.</p>
            <h3>Sostituisci (giorno libero)</h3>
            <p>Se un'addetta è a riposo: <i>Chi manca? Maria → Chi la sostituisce? Olena → Sposta le sue camere</i>. Vale <b>solo per quel giorno</b>: dal giorno dopo le camere tornano da sole a Maria.</p>
            <h3>Altri strumenti</h3>
            <ul class="list-disc list-inside">
                <li><b>Inserimento rapido</b>: incolla i numeri (es. <i>101, 104 108</i>), scegli tipo e addetta, <i>Inserisci</i>.</li>
                <li><b>Distribuisci automaticamente</b>: divide le camere non assegnate in modo equo tra le addette spuntate, a blocchi per piano.</li>
                <li><b>Copia da ieri</b>: riprende camere, tipo e addette del giorno prima.</li>
            </ul>
            <p>Una camera già iniziata o con una segnalazione non si può togliere dal giorno.</p>
        </section>

        {{-- 5 --}}
        <section id="prenotazioni" class="bg-white rounded-xl shadow-sm p-5">
            <h2>5. Prenotazioni</h2>
            <p>Il planning mostra 14 giorni: ogni riga è una camera, ogni barra colorata un soggiorno. Le icone 🧳 e 🛏 in basso indicano le pulizie previste.</p>
            <h3>Inserire o modificare a mano</h3>
            <ul class="list-disc list-inside">
                <li><b>Nuova</b>: tocca una casella vuota (camera e giorno di arrivo), completa partenza e riassetto, <i>Salva</i>.</li>
                <li><b>Modifica o cancella</b>: tocca la barra.</li>
                <li><b>Riassetto</b>: ogni giorno, ogni 2 giorni (proposto), ogni 3 giorni, oppure nessuno fino alla partenza. Si conta dall'arrivo.</li>
            </ul>
            <p><b>Esempio</b>: arrivo il 10, partenza il 15, riassetto ogni 2 giorni → 🛏 il 12 e il 14, 🧳 il 15. Il giorno dell'arrivo niente: la camera è già pronta dalla partenza precedente.</p>
            <p>Se cambi o cancelli una prenotazione, le pulizie previste si aggiornano da sole (quelle già iniziate non si toccano). Non si possono inserire due soggiorni sovrapposti nella stessa camera.</p>
            <h3>Importare dal gestionale (CSV)</h3>
            <ol class="list-decimal list-inside">
                <li>Esporta le prenotazioni dal gestionale in formato CSV.</li>
                <li><i>Prenotazioni → ⬆ Importa da file CSV</i>, scegli il file e il riassetto da usare per le nuove.</li>
                <li>Guarda l'<b>anteprima</b>: <span class="text-green-800">nuove</span>, <span class="text-blue-800">aggiornate</span>, già presenti, <span class="text-red-800">scartate</span> (con il motivo, es. <i>camera 999 non trovata</i>).</li>
                <li>Clicca <b>Importa</b>.</li>
            </ol>
            <p>Puoi importare lo stesso file più volte: non crea doppioni e aggiorna le date cambiate. Nome dell'ospite e prezzi <b>non</b> vengono importati. Le prenotazioni <b>cancellate</b> nel gestionale vanno tolte a mano dal planning.</p>
        </section>

        {{-- 6 --}}
        <section id="addette" class="bg-white rounded-xl shadow-sm p-5">
            <h2>6. Addette</h2>
            <ul class="list-disc list-inside">
                <li><b>Aggiungi</b>: nome + lingua dell'app (italiano, inglese, rumeno, ucraino, spagnolo, bengalese). L'addetta non deve impostare niente. Il <b>telefono</b> è facoltativo: serve solo per avvisarla su WhatsApp delle camere urgenti.</li>
                <li><b>🖨 Stampa foglio</b>: un foglio A4 con il QR code grande e le istruzioni nella sua lingua. Lei inquadra il QR con la fotocamera e si apre la sua pagina; conviene aggiungerla alla schermata Home.</li>
                <li><b>▦ QR e link</b>: mostra il QR sullo schermo o copia il link da mandarle (es. su WhatsApp).</li>
                <li><b>↻ Rigenera link</b>: se un telefono o un foglio finiscono in mani sbagliate. Il vecchio QR smette subito di funzionare: ristampa il foglio.</li>
                <li><b>PIN</b> (facoltativo): 4 cifre in più per sicurezza. Funziona solo se è accesa la spunta <i>"PIN di sicurezza"</i>; l'addetta lo inserisce una volta ogni 90 giorni per telefono.</li>
                <li><b>Disattiva</b> (togli la spunta "attiva"): per chi non lavora più. Il suo link smette di funzionare, lo storico resta.</li>
            </ul>
        </section>

        {{-- 7 --}}
        <section id="camere" class="bg-white rounded-xl shadow-sm p-5">
            <h2>7. Camere</h2>
            <ul class="list-disc list-inside">
                <li><b>Importazione rapida</b>: <i>da 101 a 120, piano 1</i> crea 20 camere. Quelle già presenti non vengono toccate.</li>
                <li><b>Una camera</b>: per numeri particolari, es. <i>12B</i>.</li>
                <li><b>Nota fissa</b>: vale tutti i giorni, es. <i>letto a castello</i>; l'addetta la vede con 📝.</li>
                <li><b>Disattiva</b> una camera in ristrutturazione: non compare più nella preparazione.</li>
            </ul>
            <p>Il numero deve essere uguale a quello del gestionale (colonna PMS del CSV, la parte prima del trattino: <i>207-M+S-B</i> → <i>207</i>).</p>
        </section>

        {{-- 8 --}}
        <section id="telefono" class="bg-white rounded-xl shadow-sm p-5">
            <h2>8. Cosa vede l'addetta sul telefono</h2>
            <ul class="list-disc list-inside">
                <li>Le sue camere di oggi come tessere grandi: <b>un tocco</b> = giallo (inizio), <b>un altro</b> = verde (pronta). Toccando una verde le chiede se riaprirla.</li>
                <li>Dopo ogni tocco ha <b>5 secondi</b> per annullare.</li>
                <li><b>Pulsante rosso</b>: sceglie la camera, scatta una foto, aggiunge una nota se vuole, <i>Invia</i>. Arriva a te sul tabellone.</li>
                <li><b>📅</b> accanto al suo nome: le camere previste nei prossimi 7 giorni, con l'avviso che possono cambiare.</li>
                <li>Quando <b>tu cambi qualcosa</b> sulle sue camere (urgente, nuova camera, stato, nota) il suo telefono <b>suona, vibra</b> e mostra un avviso entro una decina di secondi; la tessera lampeggia. Per l'<b>urgente</b> il suono è più forte. Funziona con lo schermo acceso e dopo che lei ha toccato la pagina almeno una volta.</li>
                <li>Funziona anche <b>senza rete</b>: i tocchi restano sul telefono (lo vede in alto: <i>"Senza rete"</i> e il numero in attesa) e partono da soli quando torna la rete.</li>
            </ul>
        </section>

        {{-- 9 --}}
        <section id="domande" class="bg-white rounded-xl shadow-sm p-5">
            <h2>9. Domande frequenti</h2>
            <div class="space-y-2">
                @foreach ([
                    ['L\'addetta non vede le sue camere', 'Controlla in <b>Preparazione del giorno</b> (oggi) che le camere siano assegnate a lei: con il pennello del suo nome toccale. Il suo telefono si aggiorna entro 20 secondi.'],
                    ['Una camera prenotata non compare tra quelle da pulire', 'Controlla nel planning che la prenotazione abbia le date giuste e il riassetto desiderato (con "Nessuno" compare solo il Cambio alla partenza). Se oggi l\'avevi tolta a mano, non torna da sola: aggiungila con un pennello.'],
                    ['L\'addetta ha cambiato telefono o ha perso il foglio', 'Ristampa il foglio (stesso QR) oppure mandale il link. Se il foglio può essere finito in mani sbagliate, usa <b>↻ Rigenera link</b> e stampa quello nuovo.'],
                    ['Sul telefono compare "Link non valido"', 'L\'addetta è stata disattivata o il link è stato rigenerato: dalle il QR nuovo dalla pagina Addette.'],
                    ['Il telefono dell\'addetta non ha rete in un corridoio', 'Va bene così: può continuare a toccare le camere. Le azioni partono da sole appena torna la rete; in alto vede quante sono in attesa.'],
                    ['Non sento i suoni', 'Controlla che sul tabellone ci sia "🔔 Suoni attivi", clicca un punto qualsiasi della pagina (dopo un ricaricamento il browser li blocca) e che l\'audio del computer non sia muto.'],
                    ['Una camera è segnata pronta per sbaglio', 'Sul tabellone clicca la camera e scegli lo stato giusto. Oppure l\'addetta tocca la tessera verde e conferma "riaprire".'],
                    ['Un\'addetta è in ferie per una settimana', 'Ogni mattina usa <b>Sostituisci</b>. Se invece le sue camere devono passare stabilmente a un\'altra, assegnale con il pennello: da quel giorno la titolare è la nuova.'],
                    ['Ho segnato una camera urgente ma l\'addetta non l\'ha sentito', 'Il telefono suona solo con lo <b>schermo acceso</b> e dopo che l\'addetta ha toccato la pagina almeno una volta (regola dei telefoni). Con lo schermo spento l\'avviso compare appena lo riaccende. Per le vere emergenze, meglio anche una chiamata.'],
                    ['Ho dimenticato la password', 'Nella pagina di accesso clicca <b>"Password dimenticata?"</b>: arriva un\'email con il link (vale 60 minuti). Email e password si cambiano anche da <b>👤</b> in alto a destra.'],
                ] as [$domanda, $risposta])
                    <details class="rounded-lg border p-3 open:bg-slate-50">
                        <summary class="font-semibold cursor-pointer">{{ $domanda }}</summary>
                        <p class="mt-2 text-slate-700">{!! $risposta !!}</p>
                    </details>
                @endforeach
            </div>
            <p class="mt-4 text-slate-600">Non trovi la risposta? Scrivi a <a href="mailto:info@housekeephotel.com" class="underline">info@housekeephotel.com</a>.</p>
        </section>
    </div>
</div>
@endsection
