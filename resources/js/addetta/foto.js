// Compressione delle foto nel browser prima dell'invio:
// lato lungo massimo 1600px, JPEG qualità 0.75 (CLAUDE.md §3).

const LATO = 1600;
const QUALITA = 0.75;

function caricaImmagine(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
        img.onerror = (e) => { URL.revokeObjectURL(url); reject(e); };
        img.src = url;
    });
}

export async function comprimi(file) {
    let sorgente;
    try {
        // Rispetta l'orientamento EXIF (foto girate dei telefoni).
        sorgente = await createImageBitmap(file, { imageOrientation: 'from-image' });
    } catch {
        try {
            sorgente = await caricaImmagine(file);
        } catch {
            return file; // formato che il browser non sa leggere: si invia così com'è
        }
    }

    const w = sorgente.width;
    const h = sorgente.height;
    const scala = Math.min(1, LATO / Math.max(w, h));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(w * scala);
    canvas.height = Math.round(h * scala);
    canvas.getContext('2d').drawImage(sorgente, 0, 0, canvas.width, canvas.height);
    sorgente.close?.();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', QUALITA));
    return blob || file;
}
