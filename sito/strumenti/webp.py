# Converte gli screenshot PNG del sito in WebP (più leggeri) e toglie i PNG.
# Uso: python3 -I sito/strumenti/webp.py
import pathlib
from PIL import Image

cartella = pathlib.Path(__file__).resolve().parent.parent / 'pubblico' / 'immagini'
for png in sorted(cartella.glob('*.png')):
    Image.open(png).save(png.with_suffix('.webp'), 'WEBP', quality=82, method=6)
    png.unlink()
    print(png.with_suffix('.webp').name, png.with_suffix('.webp').stat().st_size // 1024, 'KB')
