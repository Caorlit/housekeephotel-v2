// Immagine di anteprima per i link condivisi (1200x630): sito/pubblico/immagini/condivisione.png
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const P = path.resolve(__dirname, '../pubblico');
const b64 = (f) => fs.readFileSync(path.join(P, f)).toString('base64');
(async () => {
  const b = await chromium.launch();
  const p = await b.newPage({ viewport: { width: 1200, height: 630 } });
  await p.setContent(`<html><head><style>
    @font-face{font-family:N;font-weight:800;src:url(data:font/woff2;base64,${b64('font/nunito-800.woff2')})}
    @font-face{font-family:N;font-weight:600;src:url(data:font/woff2;base64,${b64('font/nunito-600.woff2')})}
    body{margin:0;width:1200px;height:630px;font-family:N;background:linear-gradient(135deg,#f0f9ff,#ccfbf1);display:flex;align-items:center;overflow:hidden}
    .t{padding:0 0 0 70px;width:640px} h1{font-size:62px;line-height:1.05;color:#0B3B5C;margin:28px 0 18px;font-weight:800}
    p{font-size:28px;color:#0f766e;font-weight:600;margin:0}
    .tel{margin-left:auto;margin-right:90px;width:300px;border-radius:44px;background:#0f172a;padding:12px;box-shadow:0 30px 60px #0f172a40;transform:rotate(4deg) translateY(60px)}
    .tel img{display:block;width:100%;border-radius:34px}
  </style></head><body>
    <div class="t"><img src="data:image/svg+xml;base64,${b64('brand/housekeep-logo.svg')}" style="height:70px">
      <h1>Le camere pronte,<br>senza WhatsApp.</h1><p>🎁 3 mesi gratis · housekeephotel.com</p></div>
    <div class="tel"><img src="data:image/webp;base64,${b64('immagini/telefono-it.webp')}"></div>
  </body></html>`);
  await p.waitForTimeout(300);
  await p.screenshot({ path: path.join(P, 'immagini/condivisione.png') });
  await b.close();
})();
