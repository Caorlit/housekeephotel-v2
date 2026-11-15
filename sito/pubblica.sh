#!/usr/bin/env bash
# Pubblica il sito di presentazione su housekeephotel.com (Hostinger, sito isolato).
# Il repository va clonato nella cartella .h5g del sito, es.:
#   ~/websites/<ID-SITO>/.h5g/housekeephotel-v2
# Uso (sul server):  cd ~/websites/<ID-SITO>/.h5g/housekeephotel-v2 && bash sito/pubblica.sh
# Il sito è già compilato in sito/dist (sul server non serve Node).
set -euo pipefail

cd "$(dirname "$0")/.."
REPO="$(pwd)"
WEB="${WEB_DIR:-$REPO/../../public_html}"
WEB="$(cd "$WEB" && pwd)"

case "$WEB" in
    */public_html) ;;
    *) echo "✗ La cartella web non sembra public_html: $WEB" >&2; exit 1 ;;
esac
[ -f sito/dist/index.html ] || { echo "✗ Manca sito/dist: il sito non è compilato." >&2; exit 1; }

echo "→ Aggiorno il codice"
git pull --ff-only

echo "→ Copio il sito in $WEB"
find "$WEB" -mindepth 1 -maxdepth 1 -exec rm -rf {} +
cp -R sito/dist/. "$WEB/"

echo "✓ Sito pubblicato. Ricordati di svuotare la cache della CDN in hPanel."
for p in / /en/ /es/ /privacy/; do
    echo "  $p → $(curl -s -o /dev/null -w '%{http_code}' "https://housekeephotel.com$p" || echo '?')"
done
