#!/usr/bin/env bash
# Deploy su Hostinger (da lanciare via SSH nella cartella del progetto):
#   cd ~/websites/Cn9B3bgz4/.h5g/laravel && bash deploy.sh
#
# Il codice sta FUORI dalla cartella web. In public_html ci sono solo un
# index.php che carica Laravel e la copia dei file statici (deploy/pubblica.sh).
# Gli asset (public/build) sono già compilati nel repository: sul server non serve Node.
set -euo pipefail

cd "$(dirname "$0")"
PHP="${PHP:-php}"
COMPOSER="${COMPOSER:-composer}"
# Su Hostinger (siti isolati) la cartella personale non è scrivibile:
# Composer usa una cartella temporanea per la sua cache.
export COMPOSER_HOME="${COMPOSER_HOME:-/tmp/composer-$(id -u)}"

echo "→ Aggiorno il codice"
git pull --ff-only

echo "→ Dipendenze PHP"
$COMPOSER install --no-dev --optimize-autoloader --no-interaction --prefer-dist

if [ ! -f .env ]; then
    echo "✗ Manca il file .env (copia .env.example e compilalo)." >&2
    exit 1
fi

echo "→ Modalità manutenzione"
$PHP artisan down --retry=15 || true
trap '$PHP artisan up' EXIT

echo "→ Database"
$PHP artisan migrate --force

echo "→ Cache"
$PHP artisan optimize:clear
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache

echo "→ Cartella web"
bash deploy/pubblica.sh

echo "→ Permessi"
chmod -R ug+rwX storage bootstrap/cache

echo "✓ Deploy completato"

# Controllo che i file sensibili NON siano raggiungibili dal web.
URL="${APP_URL_CHECK:-https://app.housekeephotel.com}"
if command -v curl >/dev/null; then
    echo "→ Verifica sicurezza su $URL"
    for p in /.env /composer.json /storage/logs/laravel.log /vendor/autoload.php /artisan \
             /laravel/.env /laravel/composer.json /laravel/storage/logs/laravel.log /.git/config /laravel/.git/config; do
        code=$(curl -s -o /dev/null -w '%{http_code}' "$URL$p" || echo 000)
        if [ "$code" = "200" ]; then
            echo "  ✗ ATTENZIONE: $p risponde 200!" >&2
        else
            echo "  ✓ $p → $code"
        fi
    done
    code=$(curl -s -o /dev/null -w '%{http_code}' "$URL/login" || echo 000)
    echo "  login → $code (atteso 200)"
fi
