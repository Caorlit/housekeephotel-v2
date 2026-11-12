// Piccolo archivio per le foto in attesa di invio (IndexedDB).
// Le azioni stanno in localStorage, ma le foto sono troppo grandi per localStorage.
// Se IndexedDB non è disponibile, le foto restano in memoria finché la pagina è aperta.

const memoria = new Map();
let dbPromise = null;

function apri() {
    if (!('indexedDB' in window)) return Promise.reject(new Error('no-idb'));
    dbPromise ??= new Promise((resolve, reject) => {
        const req = indexedDB.open('housekeep', 1);
        req.onupgradeneeded = () => req.result.createObjectStore('foto');
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
    return dbPromise;
}

async function operazione(modo, fn) {
    const db = await apri();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('foto', modo);
        const req = fn(tx.objectStore('foto'));
        tx.oncomplete = () => resolve(req?.result);
        tx.onerror = () => reject(tx.error);
    });
}

export async function salva(id, blob) {
    memoria.set(id, blob);
    try { await operazione('readwrite', (s) => s.put(blob, id)); } catch { /* resta in memoria */ }
}

export async function leggi(id) {
    if (memoria.has(id)) return memoria.get(id);
    try { return (await operazione('readonly', (s) => s.get(id))) ?? null; } catch { return null; }
}

export async function elimina(id) {
    memoria.delete(id);
    try { await operazione('readwrite', (s) => s.delete(id)); } catch { /* niente */ }
}
