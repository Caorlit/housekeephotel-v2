#!/usr/bin/env bash
# Collega la cartella web del sito (public_html) al codice Laravel, che sta FUORI
# dalla cartella web (su Hostinger: ~/websites/<id>/.h5g/laravel).
#
# Il server di Hostinger (hcdn/nginx) ignora i .htaccess: per questo in public_html
# mettiamo solo un index.php che carica Laravel e una COPIA dei file statici.
# Va rilanciato a ogni deploy (deploy.sh lo fa da solo).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
WEB="${WEB_DIR:-$ROOT/../../public_html}"
WEB="$(cd "$WEB" && pwd)"

case "$ROOT/" in
    "$WEB"/*) echo "✗ Il codice ($ROOT) sta DENTRO la cartella web ($WEB): fermo tutto." >&2; exit 1 ;;
esac

echo "→ Cartella web: $WEB"

cat > "$WEB/index.php.nuovo" <<PHP
<?php

// Generato da deploy/pubblica.sh: NON modificare a mano.
// Il codice dell'app sta fuori dalla cartella web, in $ROOT
require '$ROOT/public/index.php';
PHP
mv "$WEB/index.php.nuovo" "$WEB/index.php"

for f in build icons favicon.ico robots.txt; do
    if [ -e "$ROOT/public/$f" ]; then
        rm -rf "$WEB/$f.nuovo"
        cp -R "$ROOT/public/$f" "$WEB/$f.nuovo"
        rm -rf "$WEB/$f"
        mv "$WEB/$f.nuovo" "$WEB/$f"
    fi
done

# sw.js lo serve Laravel (senza cache): niente copia statica.
rm -f "$WEB/sw.js"

# Il vecchio .htaccess qui non serve (il server non lo legge).
rm -f "$WEB/.htaccess"

echo "✓ public_html aggiornata"
