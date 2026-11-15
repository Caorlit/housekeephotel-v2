// Genera le pagine HTML del sito housekeephotel.com (it, en, es) in sito/pagine/.
// Poi Vite (vite.sito.config.js) le compila con Tailwind in sito/dist/.
// Uso: npm run sito
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import it from './testi/it.mjs';
import en from './testi/en.mjs';
import es from './testi/es.mjs';

const QUI = path.dirname(fileURLToPath(import.meta.url));
const PAGINE = path.join(QUI, 'pagine');

// Dati dell'azienda e indirizzi (cambiare qui).
const AZIENDA = {
    nome: 'Caorle.it S.r.l.',
    indirizzo: 'Via Giovanni Vescovo 2, 30021 Caorle (VE)',
    piva: process.env.SITO_PIVA || '[P.IVA da inserire]',
    email: 'info@housekeephotel.com',
    telefono: '+39 351 698 8159',
    whatsapp: 'https://wa.me/393516988159',
};
const SITO = 'https://housekeephotel.com';
const APP = 'https://app.housekeephotel.com';
const LINGUE = [it, en, es];

const sost = (s, t) => s.replaceAll('{email}', `<a href="mailto:${AZIENDA.email}" class="underline">${AZIENDA.email}</a>`).replaceAll('<a href="{privacy}">', `<a href="${t.percorso}privacy/" class="underline font-semibold text-teal-700">`);

// --- Icone (tratto semplice, ereditano il colore) ---------------------------------
const icona = {
    messaggi: '<path d="M4 5h16v11H8l-4 4z"/><path d="m9 10 2 2 4-4"/>',
    pronta: '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
    radio: '<rect x="7" y="7" width="10" height="14" rx="2"/><path d="M10 7V3M10 11h4M10 14h4"/>',
    whatsapp: '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7z"/><path d="M9 9c0 3 3 6 6 6l1-1.5-2-1-1 .8c-1-.4-2-1.4-2.4-2.4l.8-1-1-2z"/>',
    spunta: '<path d="m5 12 4 4 10-10"/>',
};
const svg = (nome, classe = 'size-6') => `<svg class="${classe}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icona[nome]}</svg>`;

// --- Pezzi comuni ---------------------------------------------------------------
function testa(t, titolo, descrizione, percorsoPagina) {
    const alternativi = LINGUE.map((l) => `<link rel="alternate" hreflang="${l.lang}" href="${SITO}${l.percorso}${percorsoPagina}">`).join('\n    ');
    return `<!doctype html>
<html lang="${t.lang}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${titolo}</title>
    <meta name="description" content="${descrizione}">
    <link rel="canonical" href="${SITO}${t.percorso}${percorsoPagina}">
    ${alternativi}
    <link rel="alternate" hreflang="x-default" href="${SITO}/${percorsoPagina}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="${titolo}">
    <meta property="og:description" content="${descrizione}">
    <meta property="og:url" content="${SITO}${t.percorso}${percorsoPagina}">
    <meta property="og:image" content="${SITO}/immagini/condivisione.png">
    <meta property="og:locale" content="${{ it: 'it_IT', en: 'en_GB', es: 'es_ES' }[t.lang]}">
    <meta name="theme-color" content="#0EA5E9">
    <link rel="icon" href="/brand/favicon-32.png" sizes="32x32">
    <link rel="icon" href="/brand/housekeep-simbolo.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/brand/apple-touch-icon.png">
    <link rel="preload" href="/font/nunito-800.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/stile.css">
</head>`;
}

function intestazione(t) {
    const voci = [['#come', t.nav.come], ['#addette', t.nav.addette], ['#reception', t.nav.reception], ['#prezzi', t.nav.prezzi], ['#faq', t.nav.faq]];
    const lingue = LINGUE.map((l) => l.lang === t.lang
        ? `<span class="px-2 py-1 rounded-md bg-sky-100 text-sky-800 font-bold" aria-current="true">${l.lang.toUpperCase()}</span>`
        : `<a href="${l.percorso}" hreflang="${l.lang}" lang="${l.lang}" class="px-2 py-1 rounded-md hover:bg-slate-100">${l.lang.toUpperCase()}</a>`).join('');
    return `
<header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200">
    <div class="contenitore flex items-center gap-4 h-16">
        <a href="${t.percorso}" class="shrink-0" aria-label="Housekeep Hotel"><img src="/brand/housekeep-logo.svg" alt="Housekeep Hotel" class="h-9 sm:h-10 w-auto" width="230" height="46"></a>
        <nav class="hidden lg:flex items-center gap-1 ml-4 text-slate-700 font-semibold" aria-label="Menu">
            ${voci.map(([h, n]) => `<a href="${h}" class="px-3 py-2 rounded-lg hover:bg-slate-100">${n}</a>`).join('')}
        </nav>
        <div class="ml-auto flex items-center gap-2 text-sm">
            <div class="hidden sm:flex items-center gap-1 text-slate-600" aria-label="Lingua">${lingue}</div>
            <a href="${APP}/login" class="hidden md:inline px-3 py-2 rounded-lg text-slate-700 hover:bg-slate-100 font-semibold">${t.nav.accedi}</a>
            <a href="#prova" class="pulsante pulsante-primario !py-2 !px-4">${t.nav.cta}</a>
        </div>
    </div>
    <div class="sm:hidden border-t border-slate-100 py-1 flex justify-center gap-1 text-sm text-slate-600">${lingue}</div>
</header>`;
}

function piede(t) {
    return `
<footer class="bg-slate-900 text-slate-300">
    <div class="contenitore py-12 grid gap-8 md:grid-cols-[1fr_auto]">
        <div>
            <img src="/brand/housekeep-logo-negativo.svg" alt="Housekeep Hotel" class="h-10 w-auto" width="230" height="46" loading="lazy">
            <p class="mt-4 text-sm leading-relaxed">${AZIENDA.nome} · ${AZIENDA.indirizzo}<br>P.IVA ${AZIENDA.piva}<br>
            <a href="mailto:${AZIENDA.email}" class="hover:text-white">${AZIENDA.email}</a> · <a href="tel:${AZIENDA.telefono.replaceAll(' ', '')}" class="hover:text-white">${AZIENDA.telefono}</a></p>
        </div>
        <div class="flex flex-col gap-2 text-sm md:text-right">
            <a href="${t.percorso}privacy/" class="hover:text-white">${t.footer.privacy}</a>
            <a href="${APP}/login" class="hover:text-white">${t.footer.app}</a>
            <a href="${AZIENDA.whatsapp}" class="hover:text-white">WhatsApp</a>
        </div>
    </div>
    <div class="border-t border-slate-800"><p class="contenitore py-4 text-xs text-slate-500">© ${new Date().getFullYear()} ${AZIENDA.nome} · ${t.footer.diritti}</p></div>
</footer>`;
}

// Telefono disegnato attorno a uno screenshot.
const telefono = (src, alt, classe = '') => `
<div class="telefono ${classe}"><img src="${src}" alt="${alt}" width="390" height="844" loading="lazy" decoding="async"></div>`;
const schermo = (src, alt, classe = '') => `
<div class="schermo ${classe}"><div class="schermo-barra"><span></span><span></span><span></span></div><img src="${src}" alt="${alt}" width="1280" height="800" loading="lazy" decoding="async"></div>`;

// --- Home ----------------------------------------------------------------------
function home(t) {
    const L = t.lang;
    const img = { telefono: `/immagini/telefono-${L}.webp`, tabellone: '/immagini/tabellone.webp', segnalazione: `/immagini/segnalazione-${L}.webp` };
    const c = t.prova.campi;

    return `${testa(t, t.meta.titolo, t.meta.descrizione, '')}
<body class="bg-white text-slate-800 antialiased">
${intestazione(t)}
<main>
    <!-- Apertura -->
    <section class="relative overflow-hidden bg-gradient-to-b from-sky-50 via-white to-white">
        <svg class="absolute -bottom-6 left-0 w-full h-40 text-teal-100" viewBox="0 0 1440 160" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0 96c180-56 360-56 540 0s360 56 540 0 270-40 360-16v80H0z"/></svg>
        <div class="contenitore relative grid lg:grid-cols-[1.05fr_1fr] gap-12 items-center pt-12 pb-20 lg:pt-20 lg:pb-28">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-teal-100 text-teal-900 font-bold px-4 py-1.5 text-sm">🎁 ${t.hero.etichetta}</p>
                <h1 class="mt-5 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-[#0B3B5C] leading-[1.05]">${t.hero.titolo}</h1>
                <p class="mt-5 text-lg sm:text-xl text-slate-600 max-w-xl">${t.hero.sottotitolo}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#prova" class="pulsante pulsante-primario">${t.hero.cta1}</a>
                    <a href="#prova" data-interesse="demo" class="pulsante pulsante-secondario">${t.hero.cta2}</a>
                </div>
                <p class="mt-4 text-sm text-slate-500">${t.hero.nota}</p>
            </div>
            <div class="relative h-[460px] sm:h-[520px]">
                ${schermo(img.tabellone, t.come.passi[1][0], 'absolute right-0 top-6 w-[92%] hidden sm:block')}
                ${telefono(img.telefono, t.come.passi[0][0], 'absolute left-1/2 -translate-x-1/2 sm:left-0 sm:translate-x-0 bottom-0 w-56 sm:w-60')}
            </div>
        </div>
    </section>

    <!-- Problemi -->
    <section class="py-20 bg-white">
        <div class="contenitore">
            <h2 class="titolo-sezione">${t.problemi.titolo}</h2>
            <p class="sottotitolo-sezione">${t.problemi.sottotitolo}</p>
            <div class="mt-12 grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                ${t.problemi.voci.map(([ic, tit, txt]) => `
                <div class="rounded-2xl border border-slate-200 p-6 hover:shadow-lg hover:-translate-y-0.5 transition">
                    <div class="size-12 rounded-xl bg-gradient-to-br from-sky-500 to-teal-500 text-white flex items-center justify-center">${svg(ic)}</div>
                    <h3 class="mt-4 text-lg font-extrabold text-[#0B3B5C]">${tit}</h3>
                    <p class="mt-2 text-slate-600">${txt}</p>
                </div>`).join('')}
            </div>
        </div>
    </section>

    <!-- Come funziona -->
    <section id="come" class="py-20 bg-slate-50 scroll-mt-16">
        <div class="contenitore">
            <h2 class="titolo-sezione">${t.come.titolo}</h2>
            <p class="sottotitolo-sezione">${t.come.sottotitolo}</p>
            <div class="mt-14 grid md:grid-cols-3 gap-10">
                ${t.come.passi.map(([tit, txt, quale], i) => `
                <div class="flex flex-col">
                    <div class="h-[400px] flex items-end justify-center">
                        ${quale === 'tabellone' ? schermo(img.tabellone, tit, 'w-full') : telefono(img[quale], tit, 'w-52')}
                    </div>
                    <div class="mt-8 flex gap-4">
                        <span class="shrink-0 size-10 rounded-full bg-[#0B3B5C] text-white font-extrabold flex items-center justify-center">${i + 1}</span>
                        <div><h3 class="text-xl font-extrabold text-[#0B3B5C]">${tit}</h3><p class="mt-1 text-slate-600">${txt}</p></div>
                    </div>
                </div>`).join('')}
            </div>
        </div>
    </section>

    <!-- Addette -->
    <section id="addette" class="py-20 bg-white scroll-mt-16">
        <div class="contenitore grid lg:grid-cols-[1fr_auto] gap-14 items-center">
            <div>
                <h2 class="titolo-sezione !text-left">${t.addette.titolo}</h2>
                <p class="sottotitolo-sezione !text-left !mx-0">${t.addette.sottotitolo}</p>
                <ul class="mt-10 grid sm:grid-cols-2 gap-x-8 gap-y-6">
                    ${t.addette.voci.map(([tit, txt]) => `
                    <li class="flex gap-3"><span class="shrink-0 mt-0.5 size-7 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center">${svg('spunta', 'size-4')}</span>
                        <span><b class="block text-[#0B3B5C] text-lg">${tit}</b><span class="text-slate-600">${txt}</span></span></li>`).join('')}
                </ul>
                <p class="mt-8 inline-block rounded-xl bg-sky-50 text-sky-900 font-semibold px-4 py-3">🌍 ${t.addette.lingue}</p>
            </div>
            <div class="flex justify-center gap-5">
                ${telefono(`/immagini/telefono-${L}.webp`, t.addette.titolo, 'w-52 hidden sm:block')}
                ${telefono(`/immagini/prossimi-${L}.webp`, t.addette.voci[5][0], 'w-52 sm:mt-16')}
            </div>
        </div>
    </section>

    <!-- Reception -->
    <section id="reception" class="py-20 bg-gradient-to-b from-slate-50 to-white scroll-mt-16">
        <div class="contenitore">
            <h2 class="titolo-sezione">${t.reception.titolo}</h2>
            <p class="sottotitolo-sezione">${t.reception.sottotitolo}</p>
            ${schermo('/immagini/tabellone.webp', t.reception.voci[0][0], 'mt-12 max-w-5xl mx-auto')}
            <div class="mt-14 grid sm:grid-cols-2 lg:grid-cols-3 gap-x-10 gap-y-8">
                ${t.reception.voci.map(([tit, txt]) => `
                <div><h3 class="flex items-center gap-2 text-lg font-extrabold text-[#0B3B5C]"><span class="size-2.5 rounded-full bg-gradient-to-br from-sky-500 to-teal-500"></span>${tit}</h3><p class="mt-1 text-slate-600">${txt}</p></div>`).join('')}
            </div>
            <div class="mt-14 grid md:grid-cols-2 gap-6">
                ${schermo('/immagini/giornata.webp', t.reception.voci[3][0])}
                ${schermo('/immagini/prenotazioni.webp', t.reception.voci[2][0])}
            </div>
        </div>
    </section>

    <!-- Prezzi -->
    <section id="prezzi" class="py-20 bg-white scroll-mt-16">
        <div class="contenitore">
            <h2 class="titolo-sezione">${t.prezzi.titolo}</h2>
            <p class="sottotitolo-sezione">${t.prezzi.sottotitolo}</p>
            <p class="mt-8 mx-auto max-w-2xl text-center rounded-2xl bg-gradient-to-r from-sky-500 to-teal-500 text-white text-lg px-6 py-4 shadow">🎁 ${t.prezzi.promo}</p>
            <div class="mt-10 grid md:grid-cols-3 gap-6 items-stretch">
                ${t.prezzi.piani.map(([nome, prezzo, camere, addette], i) => `
                <div class="relative rounded-3xl border ${i === 0 ? 'border-teal-500 ring-2 ring-teal-500 shadow-xl' : 'border-slate-200'} p-8 flex flex-col">
                    ${i === 0 ? `<span class="absolute -top-3 left-8 rounded-full bg-teal-500 text-white text-xs font-bold px-3 py-1 uppercase tracking-wide">${t.prezzi.consigliato}</span>` : ''}
                    <h3 class="text-xl font-extrabold text-[#0B3B5C]">${nome}</h3>
                    <p class="mt-4"><span class="text-4xl font-extrabold text-[#0B3B5C]">${prezzo}</span>${/\d/.test(prezzo) ? `<span class="text-slate-500">${t.prezzi.mese}</span>` : ''}</p>
                    <ul class="mt-6 space-y-2 text-slate-700">
                        ${[camere, addette, ...t.prezzi.inclusi].map((v) => `<li class="flex gap-2"><span class="text-teal-600">${svg('spunta', 'size-5')}</span>${v}</li>`).join('')}
                    </ul>
                    <a href="#prova" class="mt-auto pt-8"><span class="pulsante ${i === 0 ? 'pulsante-primario' : 'pulsante-secondario'} w-full">${t.prezzi.cta}</span></a>
                </div>`).join('')}
            </div>
            <p class="mt-6 text-center text-sm text-slate-500">${t.prezzi.note}</p>
        </div>
    </section>

    <!-- Domande -->
    <section id="faq" class="py-20 bg-slate-50 scroll-mt-16">
        <div class="contenitore max-w-3xl">
            <h2 class="titolo-sezione">${t.faq.titolo}</h2>
            <div class="mt-10 space-y-3">
                ${t.faq.voci.map(([d, r]) => `
                <details class="group rounded-2xl bg-white border border-slate-200 px-6 py-4 open:shadow-md">
                    <summary class="flex items-center justify-between gap-4 cursor-pointer list-none font-bold text-lg text-[#0B3B5C]">${d}<span class="text-teal-600 text-2xl transition group-open:rotate-45">+</span></summary>
                    <p class="mt-3 text-slate-600">${r}</p>
                </details>`).join('')}
            </div>
        </div>
    </section>

    <!-- Prova gratis -->
    <section id="prova" class="py-20 bg-gradient-to-br from-sky-600 to-teal-600 text-white scroll-mt-16">
        <div class="contenitore grid lg:grid-cols-[1fr_1.2fr] gap-12 items-start">
            <div>
                <h2 class="text-3xl sm:text-4xl font-extrabold">${t.prova.titolo}</h2>
                <p class="mt-4 text-lg text-sky-50">${t.prova.sottotitolo}</p>
                <div class="mt-10 rounded-2xl bg-white/10 p-6">
                    <p class="font-bold">${t.prova.oppure}</p>
                    <a href="${AZIENDA.whatsapp}" class="mt-4 pulsante bg-[#25D366] text-white hover:bg-[#1ebe5b]">${svg('whatsapp', 'size-5')} ${t.prova.whatsapp}</a>
                    <p class="mt-4 text-sky-50"><a href="mailto:${AZIENDA.email}" class="underline">${AZIENDA.email}</a><br><a href="tel:${AZIENDA.telefono.replaceAll(' ', '')}" class="underline">${AZIENDA.telefono}</a></p>
                </div>
            </div>
            <form method="POST" action="${APP}/richiesta-prova" class="rounded-3xl bg-white text-slate-800 p-6 sm:p-8 shadow-2xl grid sm:grid-cols-2 gap-4">
                <p id="errore-modulo" class="sm:col-span-2 hidden rounded-xl bg-red-100 text-red-900 px-4 py-3 font-semibold">${t.prova.errore}</p>
                <input type="hidden" name="lingua" value="${L}">
                <div class="hidden" aria-hidden="true"><label>Sito web<input name="sito_web" tabindex="-1" autocomplete="off"></label></div>
                <label class="campo">${c.nome} *<input name="nome" required maxlength="100" autocomplete="name"></label>
                <label class="campo">${c.hotel} *<input name="hotel" required maxlength="150" autocomplete="organization"></label>
                <label class="campo">${c.citta}<input name="citta" maxlength="100" autocomplete="address-level2"></label>
                <label class="campo">${c.camere}<input name="camere" type="number" min="1" max="5000" inputmode="numeric"></label>
                <label class="campo">${c.email} *<input name="email" type="email" required maxlength="255" autocomplete="email"></label>
                <label class="campo">${c.telefono}<input name="telefono" type="tel" maxlength="40" autocomplete="tel"></label>
                <label class="campo sm:col-span-2">${c.interesse}
                    <select name="interesse"><option value="prova">${c.prova}</option><option value="demo">${c.demo}</option></select></label>
                <label class="campo sm:col-span-2">${c.messaggio}<textarea name="messaggio" rows="3" maxlength="2000"></textarea></label>
                <label class="sm:col-span-2 flex items-start gap-3 text-sm text-slate-600"><input type="checkbox" name="privacy" value="1" required class="mt-1 size-5 accent-teal-600"><span>${sost(c.privacy, t)}</span></label>
                <button class="sm:col-span-2 pulsante pulsante-primario w-full text-lg">${c.invia}</button>
            </form>
        </div>
    </section>
</main>
${piede(t)}
<script>
    // Errore di ritorno dal modulo e scelta "demo" dai pulsanti.
    if (new URLSearchParams(location.search).has('errore')) document.getElementById('errore-modulo').classList.remove('hidden');
    document.querySelectorAll('[data-interesse]').forEach((a) => a.addEventListener('click', () => {
        document.querySelector('select[name=interesse]').value = a.dataset.interesse;
    }));
</script>
</body>
</html>
`;
}

// --- Pagine semplici (grazie, privacy) ------------------------------------------
function semplice(t, percorsoPagina, titolo, corpo, indicizza = true) {
    return `${testa(t, `${titolo} – Housekeep Hotel`, t.meta.descrizione, percorsoPagina).replace('</head>', indicizza ? '</head>' : '    <meta name="robots" content="noindex">\n</head>')}
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">
${intestazione(t)}
<main class="flex-1 py-16"><div class="contenitore max-w-3xl">${corpo}</div></main>
${piede(t)}
</body>
</html>
`;
}

const grazie = (t) => semplice(t, 'grazie/', t.grazie.titolo, `
    <div class="rounded-3xl bg-white shadow-sm p-10 text-center">
        <div class="mx-auto size-16 rounded-full bg-gradient-to-br from-sky-500 to-teal-500 text-white flex items-center justify-center">${svg('spunta', 'size-9')}</div>
        <h1 class="mt-6 text-3xl font-extrabold text-[#0B3B5C]">${t.grazie.titolo}</h1>
        <p class="mt-4 text-lg text-slate-600">${t.grazie.testo}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="${t.percorso}" class="pulsante pulsante-secondario">${t.grazie.torna}</a>
            <a href="${AZIENDA.whatsapp}" class="pulsante bg-[#25D366] text-white">${svg('whatsapp', 'size-5')} WhatsApp</a>
        </div>
    </div>`, false);

const privacy = (t) => semplice(t, 'privacy/', t.privacy.titolo, `
    <article class="rounded-3xl bg-white shadow-sm p-8 sm:p-10">
        <h1 class="text-3xl font-extrabold text-[#0B3B5C]">${t.privacy.titolo}</h1>
        <p class="mt-1 text-sm text-slate-500">${t.privacy.aggiornata}</p>
        ${t.privacy.sezioni.map(([tit, txt]) => `<h2 class="mt-8 text-xl font-bold text-[#0B3B5C]">${tit}</h2><p class="mt-2 text-slate-700 leading-relaxed">${sost(txt, t)}</p>`).join('')}
    </article>`);

// --- Scrittura ---------------------------------------------------------------------
fs.rmSync(PAGINE, { recursive: true, force: true });
const scrivi = (rel, html) => {
    const file = path.join(PAGINE, rel);
    fs.mkdirSync(path.dirname(file), { recursive: true });
    fs.writeFileSync(file, html);
};
for (const t of LINGUE) {
    const base = t.percorso.replace(/^\//, '');
    scrivi(`${base}index.html`, home(t));
    scrivi(`${base}grazie/index.html`, grazie(t));
    scrivi(`${base}privacy/index.html`, privacy(t));
}
fs.copyFileSync(path.join(QUI, 'stile.css'), path.join(PAGINE, 'stile.css'));

// sitemap e robots
const urls = LINGUE.flatMap((t) => ['', 'privacy/'].map((p) => `${SITO}${t.percorso}${p}`));
fs.mkdirSync(path.join(QUI, 'pubblico'), { recursive: true });
fs.writeFileSync(path.join(QUI, 'pubblico', 'sitemap.xml'), `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${urls.map((u) => `  <url><loc>${u}</loc></url>`).join('\n')}
</urlset>
`);
fs.writeFileSync(path.join(QUI, 'pubblico', 'robots.txt'), `User-agent: *\nAllow: /\nSitemap: ${SITO}/sitemap.xml\n`);

console.log(`Pagine generate in ${path.relative(process.cwd(), PAGINE)} (P.IVA: ${AZIENDA.piva})`);
