// Screenshot dell'app (hotel di esempio "Bellavista") per il sito.
// Uso: vedi sito/LEGGIMI.md. Richiede l'app in esecuzione su BASE con il database di vetrina.
const { chromium } = require('playwright');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const BASE = process.env.BASE || 'http://127.0.0.1:8010';
const OUT = path.resolve(__dirname, '../pubblico/immagini');
const ROOT = path.resolve(__dirname, '../..');
const TOKEN = fs.readFileSync(path.join(ROOT, 'storage/app/vetrina-token.txt'), 'utf8').trim();
const FOTO = path.join(ROOT, 'storage/app/private/vetrina/rubinetto.jpg');

function lingua(l) {
  execSync(`php artisan tinker --execute "App\\Models\\Staff::withoutGlobalScopes()->where('nome','Maria')->update(['lingua'=>'${l}']);"`,
    { cwd: ROOT, env: { ...process.env } });
}

(async () => {
  const b = await chromium.launch();

  // --- Telefono dell'addetta, nelle 3 lingue del sito ---
  for (const l of ['it', 'en', 'es']) {
    lingua(l);
    const ctx = await b.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true, locale: l });
    const p = await ctx.newPage();
    await p.goto(`${BASE}/s/${TOKEN}`); await p.waitForTimeout(900);
    await p.screenshot({ path: `${OUT}/telefono-${l}.png` });

    await p.click('button[title]'); await p.waitForTimeout(400); // 📅
    await p.screenshot({ path: `${OUT}/prossimi-${l}.png` });
    await p.goBack(); await p.waitForTimeout(300);

    await p.locator('div.fixed button.bg-red-600').click(); await p.waitForTimeout(300);
    const [fc] = await Promise.all([p.waitForEvent('filechooser'), p.locator('div.grid-cols-3 button', { hasText: '106' }).click()]);
    await fc.setFiles(FOTO); await p.waitForTimeout(900);
    await p.locator('textarea').fill({ it: 'Il rubinetto del bagno perde', en: 'The bathroom tap is leaking', es: 'El grifo del baño gotea' }[l]);
    await p.locator('h1').first().evaluate(() => document.activeElement.blur());
    await p.screenshot({ path: `${OUT}/segnalazione-${l}.png` });
    await ctx.close();
  }
  lingua('it');

  // --- Reception ---
  const ctx = await b.newContext({ viewport: { width: 1280, height: 800 }, deviceScaleFactor: 2, locale: 'it-IT', timezoneId: 'Europe/Rome' });
  const p = await ctx.newPage();
  await p.goto(`${BASE}/login`);
  await p.fill('input[name=email]', 'reception@vetrina.test'); await p.fill('input[name=password]', 'vetrina1234');
  await p.click('button:has-text("Accedi")'); await p.waitForURL('**/reception');
  await p.evaluate(() => localStorage.setItem('hk_suoni', '1'));
  await p.reload(); await p.waitForTimeout(700);
  await p.mouse.click(5, 790); await p.waitForTimeout(300); // sblocca l'audio, sparisce l'avviso
  await p.screenshot({ path: `${OUT}/tabellone.png` });

  await p.goto(`${BASE}/reception/giornata`); await p.waitForTimeout(700);
  await p.screenshot({ path: `${OUT}/giornata.png` });

  await p.goto(`${BASE}/reception/prenotazioni`); await p.waitForTimeout(700);
  await p.screenshot({ path: `${OUT}/prenotazioni.png` });

  await b.close();
})();
