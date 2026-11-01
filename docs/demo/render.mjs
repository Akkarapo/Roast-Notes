// Render compose.html frame by frame (1920x1080) from timeline.json.
// usage: node render.mjs <timeline.json> <outDir> [fps=30] [onlyTimes...]
import { chromium } from 'playwright';
import fs from 'fs'; import path from 'path'; import url from 'url';
const dir = path.dirname(url.fileURLToPath(import.meta.url));
const [tlPath, out, fps = 30, ...only] = process.argv.slice(2);
const TL = JSON.parse(fs.readFileSync(tlPath, 'utf8'));
fs.mkdirSync(out, { recursive: true });
const b = await chromium.launch({ executablePath: process.env.CHROME || '/opt/pw-browsers/chromium_headless_shell-1194/chrome-linux/headless_shell',
  args: ['--allow-file-access-from-files'] });
const p = await (await b.newContext({ viewport: { width: 1920, height: 1080 } })).newPage();
await p.goto('file://' + dir + '/compose.html', { waitUntil: 'networkidle' });
await p.evaluate(tl => window.init(tl), TL);
await p.evaluate(() => document.fonts.ready); await p.waitForTimeout(400);
const times = only.length ? only.map(Number) : [...Array(Math.round(TL.total * fps)).keys()].map(i => i / fps);
let i = 0;
for (const t of times) {
  await p.evaluate(t => window.seek(t), t);
  await p.screenshot({ path: `${out}/f${String(only.length ? Math.round(t * 10) : i).padStart(5, '0')}.jpg`, type: 'jpeg', quality: 93 });
  i++;
}
await b.close(); console.log('frames', times.length, 'total', TL.total.toFixed(2), 's');
