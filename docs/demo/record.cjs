// Scripted walkthrough of every Roast & Notes feature, recorded chapter by chapter with CDP screencast.
// usage: node record.cjs <outDir>   (site at http://127.0.0.1:8080, fresh `migrate:fresh --seed` data)
const { chromium } = require('playwright');
const fs = require('fs');
const B = 'http://127.0.0.1:8080';
const OUT = process.argv[2] || 'rec';
const VW = 1600, VH = 1000;
const sleep = ms => new Promise(r => setTimeout(r, ms));

// fake macOS-style cursor that survives navigations (position kept in sessionStorage)
const CURSOR = () => {
  const mk = () => {
    if (document.getElementById('__cur')) return;
    const d = document.createElement('div'); d.id = '__cur';
    d.innerHTML = '<svg width="34" height="34" viewBox="0 0 24 24"><path d="M4 2 L4 19 L8.6 14.8 L11.6 21.4 L14.4 20.2 L11.4 13.7 L17.6 13.7 Z" fill="#111" stroke="#fff" stroke-width="1.4" stroke-linejoin="round"/></svg><span></span>';
    d.style.cssText = 'position:fixed;left:0;top:0;z-index:2147483647;pointer-events:none;width:34px;height:34px;transform:translate(-4px,-2px)';
    const ring = d.querySelector('span');
    ring.style.cssText = 'position:absolute;left:-14px;top:-16px;width:36px;height:36px;border-radius:50%;background:rgba(35,56,196,.35);transform:scale(0);opacity:0;transition:transform .35s ease,opacity .35s ease';
    document.documentElement.appendChild(d);
    const p = JSON.parse(sessionStorage.getItem('__cur') || '[800,500]');
    d.style.left = p[0] + 'px'; d.style.top = p[1] + 'px';
  };
  window.__cur = (x, y, ms) => new Promise(res => {
    mk(); const d = document.getElementById('__cur');
    const sx = parseFloat(d.style.left), sy = parseFloat(d.style.top), t0 = performance.now();
    const step = t => { const k = Math.min(1, (t - t0) / ms), e = k < .5 ? 4 * k * k * k : 1 - Math.pow(-2 * k + 2, 3) / 2;
      d.style.left = (sx + (x - sx) * e) + 'px'; d.style.top = (sy + (y - sy) * e) + 'px';
      if (k < 1) requestAnimationFrame(step); else { sessionStorage.setItem('__cur', JSON.stringify([x, y])); res(); } };
    requestAnimationFrame(step);
  });
  window.__press = () => { mk(); const r = document.querySelector('#__cur span'); r.style.transform = 'scale(1)'; r.style.opacity = '1';
    setTimeout(() => { r.style.transform = 'scale(1.6)'; r.style.opacity = '0'; }, 220); };
  window.__scroll = (y, ms) => new Promise(res => { const s = scrollY, t0 = performance.now();
    const st = t => { const k = Math.min(1, (t - t0) / ms), e = k < .5 ? 2 * k * k : 1 - Math.pow(-2 * k + 2, 2) / 2;
      scrollTo(0, s + (y - s) * e); k < 1 ? requestAnimationFrame(st) : res(); }; requestAnimationFrame(st); });
  if (document.readyState !== 'loading') mk(); else document.addEventListener('DOMContentLoaded', mk);
};

(async () => {
  const br = await chromium.launch({ executablePath: process.env.CHROME || '/opt/pw-browsers/chromium_headless_shell-1194/chrome-linux/headless_shell' });
  const marks = {};

  async function ctx() {
    const c = await br.newContext({ viewport: { width: VW, height: VH }, deviceScaleFactor: 1, acceptDownloads: true });
    await c.addInitScript(CURSOR);
    const p = await c.newPage();
    p.on('dialog', d => d.accept());
    return p;
  }
  async function login(p, email) {
    await p.goto(B + '/login'); await p.fill('input[name=email]', email); await p.fill('input[name=password]', 'password');
    await Promise.all([p.waitForNavigation(), p.click('button[type=submit]')]);
  }

  // ---- recording -----------------------------------------------------------
  let cdp, frames, chapter, t0;
  async function start(p, name) {
    chapter = name; frames = []; marks[name] = [];
    fs.mkdirSync(`${OUT}/${name}`, { recursive: true });
    cdp = await p.context().newCDPSession(p);
    cdp.on('Page.screencastFrame', async f => {
      fs.writeFileSync(`${OUT}/${name}/f${String(frames.length).padStart(5, '0')}.jpg`, Buffer.from(f.data, 'base64'));
      frames.push(f.metadata.timestamp);
      try { await cdp.send('Page.screencastFrameAck', { sessionId: f.sessionId }); } catch (e) {}
    });
    await cdp.send('Page.startScreencast', { format: 'jpeg', quality: 90, maxWidth: VW, maxHeight: VH, everyNthFrame: 1 });
    t0 = Date.now() / 1000; await sleep(400);
  }
  async function stop() {
    await sleep(500); await cdp.send('Page.stopScreencast'); await sleep(200);
    fs.writeFileSync(`${OUT}/${chapter}/ts.json`, JSON.stringify(frames));
    console.log(chapter, frames.length, 'frames', frames.length ? (frames.at(-1) - frames[0]).toFixed(1) + 's' : '');
  }
  const mark = label => marks[chapter].push([Date.now() / 1000 - t0, label]);   // caption cue

  // ---- interaction helpers --------------------------------------------------
  async function reveal(p, loc) {
    const r = await loc.evaluate(e => { const b = e.getBoundingClientRect(); return [b.top + scrollY, b.height]; });
    const top = await p.evaluate(() => scrollY);
    if (r[0] - top < 110 || r[0] + r[1] - top > VH - 90) {
      await p.evaluate(([y]) => window.__scroll(Math.max(0, y), 650), [r[0] - VH / 2 + r[1] / 2]);
    }
  }
  async function hover(p, sel, ms = 520) {
    const loc = typeof sel === 'string' ? p.locator(sel).first() : sel;
    await reveal(p, loc); const b = await loc.boundingBox();
    await p.evaluate(([x, y, ms]) => window.__cur(x, y, ms), [b.x + b.width / 2, b.y + b.height / 2, ms]);
    return loc;
  }
  async function click(p, sel, { nav = false, wait = 450 } = {}) {
    const loc = await hover(p, sel);
    await p.evaluate(() => window.__press()); await sleep(160);
    if (nav) await Promise.all([p.waitForLoadState('networkidle').catch(() => {}), p.waitForNavigation({ timeout: 8000 }).catch(() => {}), loc.click({ force: true })]);
    else await loc.click({ force: true });
    await sleep(wait);
  }
  async function type(p, sel, text, delay = 38) {
    await click(p, sel, { wait: 120 });
    await p.locator(sel).first().fill('');
    await p.locator(sel).first().pressSequentially(text, { delay });
    await sleep(200);
  }
  const go = async (p, url, wait = 700) => { await p.goto(B + url, { waitUntil: 'networkidle' }); await sleep(wait); };
  const scroll = (p, y, ms = 1100) => p.evaluate(([y, ms]) => window.__scroll(y, ms), [y, ms]);
  const step = async (name, fn) => { try { await fn(); } catch (e) { console.log('  ! ' + name + ': ' + e.message.split('\n')[0]); } };

  // ===== 1 · browse ===========================================================
  let p = await ctx();
  await go(p, '/', 200);
  await start(p, '01-browse');
  mark('หน้าแรก');
  await sleep(900); await scroll(p, 620, 1400); await sleep(700); await scroll(p, 0, 900);
  await step('products', () => click(p, 'nav a[href$="/products"]', { nav: true, wait: 700 }));
  mark('กรองตามรสและระดับคั่ว');
  await step('fruity', () => click(p, 'label:has(input[value="fruity"])', { nav: true, wait: 600 }));
  await step('light', () => click(p, 'label:has(input[value="light"])', { nav: true, wait: 800 }));
  mark('รายละเอียดเมล็ด');
  await step('card', () => click(p, 'main a[href*="/products/"]:not([href*="?"])', { nav: true, wait: 900 }));
  await scroll(p, 700, 1500); await sleep(800); await scroll(p, 1400, 1300); await sleep(700);
  await stop();

  // ===== 2 · register + taste quiz ===========================================
  await go(p, '/register', 200);
  await start(p, '02-quiz');
  mark('สมัครสมาชิก');
  const email = `mint${Date.now() % 100000}@roastnotes.test`;
  await step('name', () => type(p, 'input[name=name]', 'มินท์ ชอบกาแฟ'));
  await step('email', () => type(p, 'input[name=email]', email, 20));
  await step('phone', () => type(p, 'input[name=phone]', '0899999999', 20));
  await step('addr', () => type(p, 'textarea[name=address]', '9 ถ.นิมมานเหมินท์ เชียงใหม่', 20));
  await step('pw', () => type(p, 'input[name=password]', 'password', 15));
  await step('pw2', () => type(p, 'input[name=password_confirmation]', 'password', 15));
  await step('submit', () => click(p, 'form[action$="/register"] button[type=submit]', { nav: true, wait: 900 }));
  if (!p.url().includes('taste-quiz')) await go(p, '/taste-quiz', 500);
  mark('Taste Quiz 3 ข้อ');
  await step('f1', () => click(p, 'label:has(input[name="flavors[]"][value="1"])', { wait: 300 }));
  await step('f2', () => click(p, 'label:has(input[name="flavors[]"][value="10"])', { wait: 300 }));
  await step('next1', () => click(p, 'button:has-text("ถัดไป")', { wait: 600 }));
  await step('roast', () => click(p, 'label:has(input[name=roast][value=light])', { wait: 300 }));
  await step('next2', () => click(p, 'button:has-text("ถัดไป"):visible', { wait: 600 }));
  await step('brew', () => click(p, 'label:has(input[name=brew][value=pour_over])', { wait: 300 }));
  await step('save', () => click(p, 'button:has-text("บันทึก Taste Profile")', { nav: true, wait: 900 }));
  mark('แนะนำเมล็ดที่เข้ากับลิ้น');
  await step('picked', async () => { const y = await p.evaluate(() => { const h = [...document.querySelectorAll('h2,h3')].find(e => /picked|แนะนำ/.test(e.innerText)); return h ? h.getBoundingClientRect().top + scrollY - 160 : 600; }); await scroll(p, y, 1300); });
  await sleep(600);
  await step('badge', () => hover(p, 'main a[href*="/products/"]', 700));
  await sleep(900);
  await stop();

  // ===== 3 · early access, wishlist, add to cart ============================
  p = await ctx(); await login(p, 'member1@roastnotes.test');
  await go(p, '/products/gimbowereda-natural', 200);
  await start(p, '03-shop');
  mark('ล็อตหายาก · Roaster Master ซื้อได้ก่อน');
  await sleep(900);
  await step('wish', () => click(p, 'main form[action$="/wishlist/gimbowereda-natural"] button', { nav: true, wait: 700 }));
  mark('เลือกขนาดถุงและการบด');
  await step('size', () => click(p, 'label:has(input[name=variant_id][value="6"])', { wait: 350 }));
  await step('grind', () => click(p, 'label:has(input[name=grind][value=drip])', { wait: 350 }));
  await step('plus', () => click(p, 'form[action$="/cart/items"] button:has-text("+")', { wait: 300 }));
  await step('add', () => click(p, 'form[action$="/cart/items"] button[type=submit]', { nav: true, wait: 800 }));
  mark('ดริปแบ็ก ซื้อ 10 แถม 2');
  await go(p, '/products/yunnan-catimor-drip-bag', 500);
  await step('qty', () => type(p, 'form[action$="/cart/items"] input[name=quantity]', '12', 60));
  await step('add2', () => click(p, 'form[action$="/cart/items"] button[type=submit]', { nav: true, wait: 700 }));
  await stop();

  // ===== 4 · cart, coupon, checkout =========================================
  await go(p, '/cart', 200);
  await start(p, '04-checkout');
  mark('ส่วนลดคิดให้อัตโนมัติ');
  await sleep(1100);
  await step('coupon', () => type(p, 'input[name=code]', 'LIGHTROAST15', 55));
  await step('apply', () => click(p, 'button:has-text("ใช้โค้ด")', { nav: true, wait: 1000 }));
  await step('summary', () => hover(p, 'text=ยอดสุทธิ', 700)); await sleep(1200);
  await step('tocheckout', () => click(p, 'a[href$="/checkout"]', { nav: true, wait: 700 }));
  mark('ยืนยันคำสั่งซื้อ');
  await step('note', () => type(p, 'textarea[name=note]', 'ฝากไว้หน้าบ้านได้เลยครับ', 25));
  await step('confirm', () => click(p, 'button:has-text("ยืนยันคำสั่งซื้อ")', { nav: true, wait: 1600 }));
  await stop();

  // ===== 5 · orders, review, wishlist, member =================================
  await go(p, '/orders', 200);
  await start(p, '05-after');
  mark('ประวัติการสั่งซื้อ');
  await sleep(900);
  await step('order', () => click(p, 'main a[href*="/orders/RN-"]', { nav: true, wait: 1000 }));
  mark('รีวิวได้เฉพาะของที่ได้รับแล้ว');
  await go(p, '/order-items/3/review/create', 500);
  await step('star', () => click(p, 'label:has(input[name=rating][value="5"])', { wait: 300 }));
  await step('brew', () => click(p, 'label:has(input[name=brew_method][value=drip_bag])', { wait: 300 }));
  await step('comment', () => type(p, 'textarea[name=comment]', 'หอมผลไม้ หวานฉ่ำ ชงง่ายมาก', 30));
  await step('send', () => click(p, 'button:has-text("ส่งรีวิว")', { nav: true, wait: 1000 }));
  mark('Wish List และระดับสมาชิก');
  await go(p, '/wishlist', 900);
  await go(p, '/member', 600); await scroll(p, 420, 1000); await sleep(900);
  await stop();

  // ===== 6 · admin =============================================================
  p = await ctx(); await login(p, 'admin@roastnotes.test');
  await go(p, '/admin', 200);
  await start(p, '06-admin');
  mark('แดชบอร์ดหลังร้าน');
  await sleep(1000);
  await step('neworder', () => click(p, 'main a[href*="/admin/orders/RN-"]', { nav: true, wait: 800 }));
  mark('อัปเดตสถานะทีละขั้น');
  for (let i = 0; i < 4; i++) await step('advance' + i, () => click(p, 'form[action$="/advance"] button[type=submit]', { nav: true, wait: 650 }));
  await step('history', () => hover(p, 'text=ประวัติ', 600)); await sleep(700);
  mark('ยกเลิกและคืนสต็อก');
  await go(p, '/admin/orders/RN-20261031-0001', 400);
  await step('reason', () => type(p, 'input[name=reason]', 'ลูกค้าขอยกเลิก', 30));
  await step('cancel', () => click(p, 'button:has-text("ยกเลิกและคืนสต็อก")', { nav: true, wait: 800 }));
  mark('จัดการสินค้าและสต็อก');
  await go(p, '/admin/products?state=low', 600);
  await step('edit', () => click(p, 'main a[href*="/edit"]', { nav: true, wait: 600 }));
  await step('stock', async () => { const s = p.locator('input[name$="[stock]"]').nth(1); await reveal(p, s); await type(p, 'input[name="variants[250g][stock]"]', '40', 80); });
  await step('save', () => click(p, 'main form[action*="/admin/products/"] button[type=submit]:visible >> nth=-1', { nav: true, wait: 700 }));
  mark('สร้างคูปองใหม่');
  await go(p, '/admin/promotions/create', 400);
  await step('pname', () => type(p, 'input[name=name]', 'ฉลองเปิดร้าน', 30));
  await step('code', () => type(p, 'input[name=code]', 'HELLO10', 50));
  await step('pct', () => type(p, 'input[name=percent_off]', '10', 60));
  await step('group', () => click(p, 'button:has-text("เลือกทั้งกลุ่ม")', { wait: 400 }));
  await step('create', () => click(p, 'button:has-text("สร้างโปรโมชั่น")', { nav: true, wait: 800 }));
  mark('ผู้ใช้และระดับสมาชิก');
  await go(p, '/admin/users', 600);
  await step('user', () => click(p, 'main a[href$="/admin/users/4"]', { nav: true, wait: 600 }));
  await step('tier', async () => { await hover(p, 'select[name=member_tier_id]'); await p.selectOption('select[name=member_tier_id]', '2'); await sleep(400); });
  await step('savetier', () => click(p, 'button:has-text("บันทึกระดับ")', { nav: true, wait: 700 }));
  mark('ดูแลรีวิว');
  await go(p, '/admin/reviews', 600);
  await step('delrev', () => click(p, 'main form[action*="/admin/reviews/"] button:has-text("ลบ")', { nav: true, wait: 800 }));
  await stop();

  // ===== 7 · sales report =====================================================
  await go(p, '/admin/reports', 200);
  await start(p, '07-report');
  mark('รายงานยอดขาย');
  await sleep(900);
  await step('12m', () => click(p, 'a[href$="range=12m"]', { nav: true, wait: 900 }));
  await scroll(p, 700, 1300); await sleep(600);
  mark('ดู SQL ที่รันจริง');
  await step('sql', () => click(p, 'summary:has-text("ดู SQL") >> nth=1', { wait: 900 }));
  await step('sqlscroll', async () => { const y = await p.evaluate(() => scrollY + 260); await scroll(p, y, 900); }); await sleep(1100);
  mark('ดาวน์โหลด CSV');
  await step('csv', () => hover(p, 'a[href*=".csv"] >> nth=1', 600)); await p.evaluate(() => window.__press()); await sleep(900);
  await stop();

  fs.writeFileSync(`${OUT}/marks.json`, JSON.stringify(marks, null, 1));
  await br.close();
})();
