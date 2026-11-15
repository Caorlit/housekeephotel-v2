#!/usr/bin/env bash
# Compila il sito e lo copia nel repository di pubblicazione "housekeephotel-site"
# (clonato accanto a questo: ../housekeephotel-site). Poi si fa commit e push lì,
# e sul server del sito si lancia: bash pubblica.sh
# Uso (in locale, da questo repository):  bash sito/esporta.sh
set -euo pipefail
cd "$(dirname "$0")/.."
DEST="${SITO_REPO:-../housekeephotel-site}"
[ -d "$DEST/.git" ] || { echo "✗ Non trovo il repository del sito in $DEST" >&2; exit 1; }

npm run sito
rm -rf "$DEST/public"
cp -R sito/dist "$DEST/public"
echo "✓ Sito copiato in $DEST/public. Ora: cd $DEST && git add -A && git commit -m '...' && git push"
