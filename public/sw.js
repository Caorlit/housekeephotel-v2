// Service worker delle addette (scope /s/).
// - Pagine /s/...: prima la rete, se manca si usa l'ultima copia salvata.
// - File statici (/build, /icons): dalla cache, aggiornati quando cambiano.
// - Le chiamate dati (POST, JSON) non passano mai dalla cache.

const VERSIONE = 'hk-v1';

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        for (const k of await caches.keys()) if (k !== VERSIONE) await caches.delete(k);
        await self.clients.claim();
    })());
});

self.addEventListener('message', (event) => {
    if (event.data?.tipo !== 'precache') return;
    event.waitUntil((async () => {
        const cache = await caches.open(VERSIONE);
        for (const url of event.data.urls) {
            try {
                const r = await fetch(url, { credentials: 'same-origin' });
                if (r.ok) await cache.put(url, r);
            } catch { /* offline: pazienza */ }
        }
    })());
});

function eStatico(url) {
    return url.pathname.startsWith('/build/') || url.pathname.startsWith('/icons/');
}

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    if (req.mode === 'navigate' && url.pathname.startsWith('/s/')) {
        event.respondWith((async () => {
            const cache = await caches.open(VERSIONE);
            const chiave = url.pathname;
            try {
                const r = await fetch(req);
                if (r.ok) cache.put(chiave, r.clone());
                return r;
            } catch {
                return (await cache.match(chiave)) || Response.error();
            }
        })());
        return;
    }

    if (eStatico(url) || url.pathname.endsWith('manifest.webmanifest')) {
        event.respondWith((async () => {
            const cache = await caches.open(VERSIONE);
            const inCache = await cache.match(req);
            if (inCache) return inCache;
            const r = await fetch(req);
            if (r.ok) cache.put(req, r.clone());
            return r;
        })());
    }
});
