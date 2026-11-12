// Reception e amministrazione.
import Alpine from 'alpinejs';
import QRCode from 'qrcode';
import giornata from './reception/giornata';
import tabellone from './reception/tabellone';

/** Disegna un QR code (SVG) dentro l'elemento. */
window.disegnaQr = async (el, testo) => {
    el.innerHTML = await QRCode.toString(testo, { type: 'svg', margin: 1, errorCorrectionLevel: 'M' });
    el.querySelector('svg')?.setAttribute('class', 'w-full h-full');
};

/** Copia negli appunti e conferma sul pulsante. */
window.copia = async (testo, pulsante) => {
    try {
        await navigator.clipboard.writeText(testo);
        const prima = pulsante.textContent;
        pulsante.textContent = '✓ Copiato';
        setTimeout(() => { pulsante.textContent = prima; }, 1500);
    } catch {
        window.prompt('Copia il link:', testo);
    }
};

Alpine.data('giornata', giornata);
Alpine.data('tabellone', tabellone);

window.Alpine = Alpine;
Alpine.start();
