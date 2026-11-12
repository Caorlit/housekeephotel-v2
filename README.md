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
| 3 | Reception: tabellone completo, preparazione del giorno, addette (QR), camere | ⏳ (c'è solo un tabellone provvisorio) |
| 4 | Superadmin e statistiche | ⏳ |

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

## Primo deploy su Hostinger

1. **PHP:** in hPanel imposta PHP 8.3 o superiore per `app.housekeephotel.com`.
2. **Database:** hPanel → Database → crea database e utente MySQL.
3. **Codice fuori dalla cartella web**, via SSH:
   ```bash
   cd ~/domains/app.housekeephotel.com
   git clone https://github.com/caorlit/housekeephotel-v2.git laravel
   cd laravel
   cp .env.example .env && nano .env      # DB, APP_URL, email, SUPERADMIN_*
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force --seed     # crea il superadmin
   ```
4. **La cartella pubblica deve servire SOLO `laravel/public`:**
   ```bash
   cd ~/domains/app.housekeephotel.com
   mv public_html public_html.vecchia     # (vuota, si può cancellare)
   ln -s laravel/public public_html
   ```
5. **Cron** (hPanel → Avanzate → Cron Job), ogni minuto:
   `cd ~/domains/app.housekeephotel.com/laravel && php artisan schedule:run >> /dev/null 2>&1`
6. Da ora in poi ogni aggiornamento: `cd ~/domains/app.housekeephotel.com/laravel && ./deploy.sh`.
   Lo script controlla anche che `/.env`, `/composer.json`, `/vendor` e `/storage` **non** rispondano 200.

## Scelte tecniche da sapere

- **Addette senza sessione e senza CSRF.** Le rotte `/s/{token}/…` si autenticano solo col token di 64 caratteri nell'URL. Così le azioni salvate offline possono partire anche ore dopo, senza un token CSRF scaduto. Ci sono rate limiting, `Referrer-Policy: no-referrer` (il token non esce verso altri siti) e "rigenera link".
- **Foto private.** Sono salvate in `storage/app/private/segnalazioni/{hotel}/{anno-mese}/` e non nella cartella pubblica, perché possono ritrarre oggetti degli ospiti. Si vedono solo tramite `/reception/foto/{id}` da utenti dello stesso hotel. Così non serve `storage:link`. Si cancellano dopo 90 giorni (`hk:cancella-foto`, ogni notte).
- **Isolamento tra hotel.** Il trait `BelongsToHotel` filtra in automatico ogni query sull'hotel attivo. L'hotel viene impostato prima del route model binding. Test: `tests/Feature/IsolamentoHotelTest.php`.
- **Offline.** Ogni tocco va in coda in `localStorage` (le foto in IndexedDB) e parte dopo i 5 secondi di "Annulla". Ogni azione ha un `client_action_id` (UUID) e l'orario del telefono. Il server scarta le azioni più vecchie dell'ultimo cambio e ignora i doppioni (`app/Services/RoomStateService.php`).
- **Segnalazione creata** → evento `App\Events\IssueCreated`: qui si aggancerà l'inoltro WhatsApp (fase 2 del prodotto).
