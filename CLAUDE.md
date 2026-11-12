# Housekeep Hotel v2 — istruzioni di progetto

> Questo file serve a Claude Code: leggilo tutto prima di scrivere codice.
> Proprietario del progetto: Marco Gusso (Caorle.it S.r.l.). Lingua di lavoro: **italiano** (commenti, messaggi di commit e interfaccia in italiano).

## 1. Cos'è

Una web app che sostituisce WhatsApp e i walkie-talkie nella comunicazione tra la **reception** di un hotel e le **addette alle pulizie**.

- L'addetta vede le camere che le sono state assegnate oggi. Tocca una camera quando inizia (gialla) e quando ha finito (verde).
- Se trova un problema (una rottura, un oggetto dimenticato, una camera molto sporca) preme il **pulsante rosso** e scatta una foto. Può aggiungere una nota breve.
- La reception vede tutte le camere su un tabellone che si aggiorna da solo, e sente un suono quando una camera è pronta o arriva una segnalazione.

### Il vincolo più importante

Le utenti sono **signore spesso non giovani, poco abituate ad app nuove, a volte straniere**. Il prodotto ha successo solo se è **più facile di un messaggio WhatsApp**. Ogni scelta va giudicata così: *"una signora di 60 anni che parla poco italiano lo capisce al primo colpo, con i guanti, in un corridoio senza wifi?"*

Una versione precedente (Laravel 8 + Firebase + Pusher, sviluppata da terzi) è fallita: era instabile, l'app delle addette non partiva sul server e richiedeva login con telefono e password. **Non riusare quel codice.** Del vecchio progetto si riprende solo lo schema dati, come riferimento (sezione 5).

## 2. Principi di design (obbligatori)

1. **Niente login per le addette.** Ogni addetta riceve un link personale o un QR code (con un token lungo e casuale) che apre la sua schermata. Il PIN di 4 cifre è facoltativo e si attiva per hotel. Il dispositivo resta "ricordato" per almeno 90 giorni.
2. **Una sola schermata per le addette.** Niente menu, niente hamburger, niente impostazioni. In cima il nome e la data, sotto le tessere delle camere.
3. **Tessere grandi.** Il numero della camera molto grande (almeno 48px), l'area da toccare di almeno 88×88px, al massimo 2 colonne su telefono.
4. **Colori e icone prima delle parole.** Grigio = da fare, giallo = in pulizia, verde = pronta, rosso = segnalazione. Il testo è solo di supporto.
5. **Feedback immediato.** Il colore cambia subito, anche senza rete. Vibrazione breve (`navigator.vibrate`) se disponibile. Si può annullare per 5 secondi ("Annulla" grande).
6. **Funziona offline.** Le azioni si mettono in coda in localStorage e partono quando torna la rete. Un indicatore discreto mostra quante azioni sono in attesa. È installabile come PWA (manifest + service worker) con un'icona sulla schermata home.
7. **Multilingua dell'interfaccia addette:** italiano, inglese, rumeno, ucraino, spagnolo e bengali. La lingua si sceglie per addetta in reception, non dall'addetta. I testi sono pochissimi.
8. **Lato reception** è normale ma pulito. Funziona su PC e su tablet.

## 3. Stack tecnico

- **PHP 8.2+ e Laravel 11** (o la versione LTS/stabile più recente compatibile con l'hosting).
- **MySQL** (database fornito da Hostinger).
- **Blade + Alpine.js** per l'interattività. **Niente** SPA, React, Vue o Inertia.
- CSS: Tailwind compilato **in locale** con Vite. La cartella `public/build` va committata, perché sul server non c'è Node.
- Tempo reale: **polling leggero** (ogni 5 secondi su un endpoint JSON che risponde solo se ci sono cambiamenti dall'ultimo `updated_at`). Server-Sent Events sono accettabili solo se funzionano in modo affidabile sull'hosting condiviso. **Vietati:** Firebase, Pusher, WebSocket, Redis, code esterne, qualsiasi servizio esterno a pagamento.
- Foto: compresse **nel browser** prima dell'upload (lato lungo 1600px, JPEG qualità 0.75) e salvate sul server in `storage/app/public/segnalazioni/{hotel_id}/{anno-mese}/`. Se `storage:link` non funziona sull'hosting, servirle tramite una rotta autenticata.
- Notifiche in reception: suono + Notification API del browser (con il permesso chiesto una volta).
- Dipendenze composer: **il minimo indispensabile**. Prima di aggiungerne una, chiedi a Marco.

## 4. Hosting e deploy

- Hosting: **Hostinger, piano Agency** (hosting condiviso con accesso SSH, composer, cron; **non** ci sono Node, Redis o estensioni PHP particolari come gRPC).
- Dominio dell'app: **`app.housekeephotel.com`** (sottodominio creato da Marco sul piano Agency; aggiorna qui se il nome cambia).
- **Struttura sul server (verificata il 12/11/2026):** il piano isola ogni sito; l'utente SSH (`u977811972_Cn9B3bgz4`) può scrivere solo in `~/websites/Cn9B3bgz4/public_html` e in `~/websites/Cn9B3bgz4/.h5g`. Il web server (hcdn/nginx) **ignora i `.htaccess`** e manda a `public_html/index.php` ogni indirizzo che non è un file. Quindi: il codice Laravel sta in `~/websites/Cn9B3bgz4/.h5g/laravel/` (fuori dalla cartella web); in `public_html` ci sono solo un `index.php` che carica Laravel e la copia dei file statici, generati da `deploy/pubblica.sh` (lanciato da `bash deploy.sh`: sul server i file non si possono eseguire direttamente). La chiave di deploy per GitHub sta in `.h5g/ssh/`. Va verificato dopo ogni deploy che `/.env`, `/storage`, `/vendor` e `/composer.json` **non** siano raggiungibili dal web (devono rispondere 403 o 404).
- **Mai** zip, backup o dump dentro le cartelle pubbliche.
- In produzione: `APP_ENV=production`, `APP_DEBUG=false`. Poi `php artisan config:cache`, `route:cache`, `view:cache`.
- Deploy: `git pull` via SSH sul server, poi `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force` e il ricaricamento della cache. Prepara uno script `deploy.sh` che faccia tutto questo.
- Cron Hostinger ogni minuto: `php artisan schedule:run`.
- Il database e l'email vanno configurati in `.env`, che **non** si committa mai. Committa `.env.example` aggiornato.

## 5. Modello dati

Ispirato al vecchio schema, semplificato. Usa nomi di tabelle in inglese e interfaccia in italiano.

- **hotels**: id, nome, indirizzo, telefono, email, `pin_richiesto` (bool), `fuso_orario` (default Europe/Rome), `attivo`, timestamps.
- **users** (reception e amministratori): id, hotel_id (null per il superadmin), nome, email, password, ruolo (`superadmin` | `reception`), timestamps. Il login è standard email + password (Laravel Breeze o un'implementazione minimale equivalente).
- **staff** (addette): id, hotel_id, nome, `lingua` (it/en/ro/uk/es/bn), `token_accesso` (64 caratteri casuali, unico, rigenerabile), `pin` (hash, nullable), `attivo`, timestamps. Niente telefono obbligatorio e niente password.
- **rooms** (anagrafica camere dell'hotel): id, hotel_id, numero, piano, note_fisse (nullable), `attiva`.
- **room_days** (lo stato di una camera in un giorno): id, hotel_id, room_id, data, `tipo` (`partenza` | `fermata` | `extra`), `stato` (`da_fare` | `in_pulizia` | `pronta`), staff_id (nullable, l'assegnata), `urgente` (bool), nota_reception, `iniziata_alle`, `finita_alle`, timestamps. Unico per (room_id, data).
- **issues** (segnalazioni): id, hotel_id, room_day_id, staff_id, nota (nullable), `foto_path` (nullable), `stato` (`aperta` | `presa_in_carico` | `risolta`), `letta_alle`, `risolta_alle`, timestamps.
- **events** (registro di tutto ciò che succede, per storico e statistiche): id, hotel_id, room_day_id, staff_id/user_id, tipo, payload JSON, created_at.

Ogni query deve essere **filtrata per hotel_id** (il sistema ospita più hotel). Usa un global scope o un middleware e scrivi test che verifichino che un hotel non vede i dati di un altro.

## 6. Schermate

### 6.1 Addetta — `/s/{token}`
- Intestazione: "Ciao Maria" + data di oggi + indicatore di rete/coda.
- Griglia delle camere assegnate oggi, ordinate per piano e numero. Le urgenti sono in cima con un bordo evidente.
- Primo tocco su una tessera grigia → gialla (`in_pulizia`). Tocco su una gialla → verde (`pronta`). Tocco su una verde → chiede "Riaprire?" con due pulsanti enormi ✓ / ✗.
- La tessera mostra un'icona per il tipo (partenza o fermata) e un'eventuale nota della reception (icona 📝; con un tocco lungo o un'icona si apre il testo).
- **Pulsante rosso** (fisso in basso, grande): apre la scelta della camera (le sue camere) e poi **subito la fotocamera** (`<input type="file" accept="image/*" capture="environment">`). Dopo lo scatto: anteprima + pulsante "Invia" + campo nota facoltativo. Il messaggio vocale è facoltativo, fase 2.
- Se nessuna camera è assegnata: un messaggio semplice "Nessuna camera per oggi".

### 6.2 Reception — tabellone `/reception`
- Griglia di tutte le camere del giorno, colorate per stato, con il nome dell'addetta. Filtri per piano e per addetta. Contatori in alto: da fare / in pulizia / pronte / segnalazioni aperte.
- Le segnalazioni aperte stanno in una colonna laterale con foto in miniatura. Si possono segnare "presa in carico" e "risolta".
- Suono + notifica del browser quando: una camera diventa pronta (suono breve) o arriva una segnalazione (suono diverso, più evidente).
- La reception può cambiare a mano lo stato di una camera e marcarla urgente.

### 6.3 Reception — preparazione del giorno `/reception/giornata`
- Si sceglie la data (default: oggi).
- **Inserimento rapido:** si incolla un elenco di numeri di camera (separati da virgole, spazi o a capo), oppure si spuntano le camere da una griglia. Per ognuna si indica partenza o fermata.
- **Assegnazione:** si seleziona un'addetta e si toccano le camere da darle (selezione multipla veloce). In alternativa si distribuisce automaticamente in modo equo per piano.
- Si può copiare l'assegnazione di ieri come punto di partenza.

### 6.4 Reception — addette `/reception/addette`
- Elenco, aggiunta (nome + lingua), disattivazione.
- Per ciascuna: **QR code e link** da mostrare o stampare (una pagina A4 con QR grande + istruzioni con icone), "rigenera link" (invalida il vecchio), PIN facoltativo.

### 6.5 Reception — camere `/reception/camere`
- Anagrafica delle camere (numero, piano) con importazione rapida "da 101 a 120, piano 1".

### 6.6 Superadmin `/admin`
- Gestione degli hotel e dei rispettivi utenti reception. Statistiche di base (tempi medi di pulizia, segnalazioni per periodo).

## 7. Regole di funzionamento

- **Inizio giornata:** le `room_days` si creano dalla schermata di preparazione. Un job pianificato a mezzanotte (fuso dell'hotel) **non** cancella niente: la vista "oggi" è semplicemente la data corrente.
- **Concorrenza:** vince l'ultima azione, ma ogni cambio va scritto in `events`. Le azioni offline portano il timestamp del dispositivo: se arrivano in ritardo vengono applicate solo se più recenti dell'ultimo stato registrato.
- **Idempotenza:** ogni azione dal telefono ha un `client_action_id` (UUID), così i reinvii dopo un problema di rete non duplicano niente.
- **Foto e privacy:** le foto possono ritrarre oggetti degli ospiti. Si cancellano automaticamente dopo **90 giorni** (comando pianificato) e sono visibili solo agli utenti dell'hotel.
- **Sicurezza:** token delle addette casuali (`Str::random(64)`), rate limiting sugli endpoint pubblici, CSRF sulle form, validazione del tipo e della dimensione dei file caricati (massimo 8 MB, solo immagini), header di sicurezza standard.

## 8. Fuori dalla prima versione (non costruirlo adesso)

- Integrazione con i gestionali alberghieri (PMS) e le loro API. Per ora basta l'inserimento manuale.
- Chat libera tra reception e addette.
- Inoltro delle segnalazioni a WhatsApp: idea per la fase 2, da tenere in mente nella struttura (un listener sull'evento "segnalazione creata").
- App native per gli store.
- Pagamenti e abbonamenti.

## 9. Come lavorare

- Prima di iniziare una parte grande, scrivi un piano breve e chiedi conferma a Marco.
- Commit piccoli e frequenti, con messaggi in italiano.
- **Test** (Pest o PHPUnit) almeno per: l'isolamento tra hotel, i cambi di stato delle camere, l'idempotenza delle azioni offline, l'upload delle foto, l'accesso con token.
- Prova ogni schermata delle addette in **vista mobile** (375px di larghezza) prima di considerarla finita.
- Dopo ogni deploy verifica: la home risponde, `/.env` non è raggiungibile, il login della reception funziona, il link di una addetta di prova funziona.
- Non introdurre servizi esterni, account di terzi o costi ricorrenti senza l'approvazione esplicita di Marco.

## 10. Primo traguardo (pilota)

Un hotel, 1 utente reception, 2–4 addette, 20–60 camere, per 2–3 settimane. Il pilota funziona se:
- le addette usano l'app ogni giorno **senza tornare a WhatsApp** per comunicare le camere pronte;
- la reception sa in ogni momento quali camere sono pronte;
- nessuna azione va persa per problemi di rete.

## Riferimenti

- Vecchio sistema (solo consultazione, **non** toccarlo): piano Hostinger "Blu Est", `admin.housekeephotel.com/house_keeping/` e `webapp.housekeephotel.com/house_keeping/`. Analisi tecnica nel progetto claude.ai "Housekeephotel", documento `analisi-tecnica-housekeephotel.md`.
