# Housekeep Hotel v2

Comunicazione tra reception e addette alle pulizie, al posto di WhatsApp e walkie-talkie.
Le istruzioni di progetto complete sono in [`CLAUDE.md`](CLAUDE.md).

**Stack:** Laravel 13 (PHP 8.3+), MySQL, Blade + Alpine.js, Tailwind (compilato in locale), polling.
Nessun servizio esterno.

## Stato

| Fase | Contenuto | Stato |
|---|---|---|
| 0 | Base, login reception, header di sicurezza, `deploy.sh` | ✅ |
| 1 | Modello dati, isolamento tra hotel, cambi di stato, registro eventi, test | ✅ |
| 2 | Schermata addette `/s/{token}`: tessere, Annulla, offline, PWA, foto, 6 lingue, PIN | ✅ |
| 3 | Reception: tabellone (polling 5 s, suoni, notifiche, segnalazioni), preparazione del giorno, addette (QR e foglio A4), camere | ✅ |
| 3b | Prenotazioni: planning, Cambio/Riassetto generati in automatico (stessa addetta), Sostituisci, import CSV | ✅ |
| 4 | Superadmin (hotel, utenti con invito), profilo e password dimenticata, statistiche, avvisi email (nuovi hotel/utenti, riepilogo settimanale, inattività 7 gg, errori) | ✅ |

## Sviluppo in locale

```bash
composer install
npm install
cp .env.example .env    # poi: APP_ENV=local, APP_DEBUG=true, DB_CONNECTION=sqlite
php artisan key:generate
php artisan migrate --seed
php artisan db:seed --class=DemoSeeder   # hotel di prova: stampa i link delle addette
npm run build            # ricordarsi di committare public/build
php artisan serve
php artisan test
```

Dati di prova: reception `reception@demo.test` / `demo1234`. Il seeder stampa i link di Maria (it), Olena (uk) e Rina (bn).

## Deploy su Hostinger

Il piano isola ogni sito e il web server **ignora i `.htaccess`** (dettagli nel CLAUDE.md, §4).

| Cosa | Dove |
|---|---|
| Codice Laravel (con `.env`, `storage`, foto) | `~/websites/Cn9B3bgz4/.h5g/laravel/` (fuori dalla cartella web) |
| Cartella web | `~/websites/Cn9B3bgz4/public_html/`: solo `index.php` + copia di `build/`, `icons/`, `sw.js` |
| Chiave di deploy GitHub (sola lettura) | `~/websites/Cn9B3bgz4/.h5g/ssh/housekeep` |

Aggiornare il sito:

```bash
cd ~/websites/Cn9B3bgz4/.h5g/laravel && bash deploy.sh
```

`deploy.sh` scarica il codice, installa le dipendenze, lancia le migrazioni, ricrea le cache, rigenera `public_html` (`deploy/pubblica.sh`) e controlla che i file sensibili non siano raggiungibili.

Email: casella `info@housekeephotel.com` (SMTP Hostinger). Sul server non c'è un editor: si configura con `php artisan hk:imposta-email info@housekeephotel.com` (chiede la password senza mostrarla) e si prova con `php artisan hk:prova-email`.

Cron (hPanel → Avanzate → Cron Job), ogni minuto:
`cd ~/websites/Cn9B3bgz4/.h5g/laravel && php artisan schedule:run >> /dev/null 2>&1`

## Scelte tecniche da sapere

- **Addette senza sessione e senza CSRF.** Le rotte `/s/{token}/…` si autenticano solo col token di 64 caratteri nell'URL. Così le azioni salvate offline possono partire anche ore dopo, senza un token CSRF scaduto. Ci sono rate limiting, `Referrer-Policy: no-referrer` (il token non esce verso altri siti) e "rigenera link".
- **Foto private.** Sono salvate in `storage/app/private/segnalazioni/{hotel}/{anno-mese}/` e non nella cartella pubblica, perché possono ritrarre oggetti degli ospiti. Si vedono solo tramite `/reception/foto/{id}` da utenti dello stesso hotel. Così non serve `storage:link`. Si cancellano dopo 90 giorni (`hk:cancella-foto`, ogni notte).
- **Isolamento tra hotel.** Il trait `BelongsToHotel` filtra in automatico ogni query sull'hotel attivo. L'hotel viene impostato prima del route model binding. Test: `tests/Feature/IsolamentoHotelTest.php`.
- **Offline.** Ogni tocco va in coda in `localStorage` (le foto in IndexedDB) e parte dopo i 5 secondi di "Annulla". Ogni azione ha un `client_action_id` (UUID) e l'orario del telefono. Il server scarta le azioni più vecchie dell'ultimo cambio e ignora i doppioni (`app/Services/RoomStateService.php`).
- **Segnalazione creata** → evento `App\Events\IssueCreated`: qui si aggancerà l'inoltro WhatsApp (fase 2 del prodotto).
