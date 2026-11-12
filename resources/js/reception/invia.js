/** POST JSON con il token CSRF della pagina. */
export async function invia(url, dati = {}) {
    const r = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify(dati),
    });
    if (r.status === 401 || r.status === 419) {
        alert('La sessione è scaduta: la pagina verrà ricaricata.');
        window.location.reload();
        throw new Error('sessione');
    }
    const json = await r.json().catch(() => ({}));
    if (!r.ok) throw new Error(json.message || 'Errore ' + r.status);
    return json;
}
