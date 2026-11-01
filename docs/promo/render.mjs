// Render promo.html frame-by-frame at 1920x1080 / 30 fps.
// usage: node render.mjs <outDir> [fps=30] [dur=30] [onlyTimes...]
import { chromium } from 'playwright';
import fs from 'fs'; import path from 'path'; import url from 'url';
const dir = path.dirname(url.fileURLToPath(import.meta.url));
const [out, fps = 30, dur = 30, ...only] = process.argv.slice(2);
fs.mkdirSync(out, { recursive: true });
const b = await chromium.launch({ executablePath: process.env.CHROME || '/opt/pw-browsers/chromium_headless_shell-1194/chrome-linux/headless_shell' });
const p = await (await b.newContext({ viewport: { width: 1920, height: 1080 }, deviceScaleFactor: 1 })).newPage();
await p.goto('file://' + dir + '/promo.html', { waitUntil: 'networkidle' });
await p.evaluate(() => document.fonts.ready);
await p.waitForTimeout(500);
const times = only.length ? only.map(Number) : [...Array(Math.round(fps * dur)).keys()].map(i => i / fps);
let i = 0;
for (const t of times) {
  await p.evaluate(t => window.seek(t), t);
  await p.screenshot({ path: `${out}/f${String(only.length ? Math.round(t * 100) : i).padStart(5, '0')}.jpg`, type: 'jpeg', quality: 94 });
  i++;
}
await b.close(); console.log('frames', times.length);
