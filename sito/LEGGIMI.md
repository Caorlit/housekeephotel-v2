# Sito di presentazione – housekeephotel.com

Sito statico in tre lingue (IT `/`, EN `/en/`, ES `/es/`), senza cookie né servizi esterni.
Il modulo "Prova gratis" invia a `https://app.housekeephotel.com/richiesta-prova` (l'app salva la richiesta,
avvisa i superadmin via email e la mostra in Admin → Richieste), poi torna alla pagina `/grazie/`.

## Cambiare testi e prezzi
- Testi: `sito/testi/it.mjs`, `en.mjs`, `es.mjs` (stesse chiavi in tutte e tre).
- Dati dell'azienda (indirizzo, P.IVA, email, telefono, WhatsApp): in cima a `sito/genera.mjs` (oggetto `AZIENDA`).
- Grafica: `sito/genera.mjs` (struttura) e `sito/stile.css`.
- Poi, in locale: `npm run sito` → rigenera `sito/dist/` (da committare: sul server non c'è Node).

## Screenshot dell'app
Dati di esempio "Hotel Bellavista" (nomi inventati):
```bash
DB_DATABASE=$PWD/database/vetrina.sqlite php artisan migrate:fresh --seeder=VetrinaSeeder --force
DB_DATABASE=$PWD/database/vetrina.sqlite php artisan serve --port=8010 &
DB_DATABASE=$PWD/database/vetrina.sqlite node sito/strumenti/screenshot.cjs
python3 -I sito/strumenti/webp.py
node sito/strumenti/condivisione.cjs      # immagine di anteprima per i link condivisi
npm run sito
```

## Pubblicazione
Sul server del sito (vedi `sito/pubblica.sh`): `bash sito/pubblica.sh`, poi svuotare la cache della CDN.
