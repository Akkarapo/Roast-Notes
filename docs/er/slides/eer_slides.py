"""Generate native (editable) Chen-notation EER slides for the Roast & Notes deck.
Each slide = entities (rect), weak entities (double rect), attributes (ellipse),
relationships (diamond), specialization circle (d), lines drawn with <x-connector>.
Usage: python3 eer_slides.py <out_dir>"""
import math, sys, html

INK, COB, PAPER = '#18248A', '#2338C4', '#FBF8F1'
LINE, CARD = '#5A6390', '#B86E14'

def tw(s, size):  # rough text width
    return len(s) * size * 0.6

class Slide:
    def __init__(s, sid, eyebrow, title, footer, notes):
        s.sid, s.eyebrow, s.title, s.footer, s.notes = sid, eyebrow, title, footer, notes
        s.n, s.lines, s.marks = {}, [], []
        s.edges, s.items = [], []   # structured copies (used by eer_drawio.py)

    # ---- nodes -------------------------------------------------------
    def ent(s, k, x, y, label=None, weak=False, w=None):
        label = label or k
        s.n[k] = dict(t='weak' if weak else 'ent', x=x, y=y, w=w or max(220, tw(label, 28) + 56), h=76, label=label)
    def att(s, k, x, y, label=None, key=False, partial=False, derived=False):
        label = label or k
        s.n[k] = dict(t='att', x=x, y=y, w=tw(label, 24) + 56, h=54, label=label, key=key, partial=partial, derived=derived)
    def rel(s, k, x, y, label=None, ident=False, w=None):
        label = label or k
        s.n[k] = dict(t='rel', x=x, y=y, w=w or max(200, tw(label, 24) + 110), h=110, label=label, ident=ident)
    def circ(s, k, x, y, label='d'):
        s.n[k] = dict(t='circ', x=x, y=y, w=68, h=68, label=label)
    def note(s, x, y, text, w=360):
        s.items.append(('note', x, y, text, w))
        s.marks.append(f'<p style="position:absolute;left:{x - w/2:.0f}px;top:{y:.0f}px;width:{w}px;font-size:24px;line-height:1.3;text-align:center;color:#6B7096;font-style:italic">{html.escape(text)}</p>')

    # ---- edges -------------------------------------------------------
    def ln(s, a, b, double=False, card=None, card_at='a', subset=False, side=1):
        A, B = s.n[a], s.n[b]
        s.edges.append((a, b, double))
        s._seg(A['x'], A['y'], B['x'], B['y'], double, rec=False)
        dx, dy = B['x'] - A['x'], B['y'] - A['y']; L = math.hypot(dx, dy); ux, uy = dx / L, dy / L
        if card:
            P = A if card_at == 'a' else B
            # point just outside the node edge on this line
            t = s._exit(P, ux if P is A else -ux, uy if P is A else -uy) + (40 if P['t'] == 'rel' else 26)
            px, py = (P['x'] + ux * t, P['y'] + uy * t) if P is A else (P['x'] - ux * t, P['y'] - uy * t)
            nx, ny = -uy * side, ux * side
            s.items.append(('card', px + nx*22, py + ny*22, card))
            s.marks.append(f'<p style="position:absolute;left:{px + nx*22 - 30:.0f}px;top:{py + ny*22 - 20:.0f}px;width:60px;height:40px;line-height:40px;font-size:28px;font-weight:700;text-align:center;color:{CARD}">{card}</p>')
        if subset:
            mx, my = A['x'] + dx * 0.55, A['y'] + dy * 0.55
            ang = math.degrees(math.atan2(-uy, -ux)) + 90
            s.items.append(('subset', mx, my, ang))
            s.marks.append(f'<p style="position:absolute;left:{mx-22:.0f}px;top:{my-24:.0f}px;width:44px;height:44px;line-height:44px;font-size:36px;font-weight:700;text-align:center;color:{COB};background:{PAPER};border-radius:50%;transform:rotate({ang:.0f}deg)">∪</p>')
    def ln_pts(s, x1, y1, x2, y2, double=False):
        s._seg(x1, y1, x2, y2, double)
    def label(s, x, y, text, color=CARD, size=26, w=160):
        s.items.append(('label', x, y, text, w, color))
        s.marks.append(f'<p style="position:absolute;left:{x - w/2:.0f}px;top:{y - 18:.0f}px;width:{w}px;font-size:{size}px;font-weight:700;text-align:center;color:{color}">{html.escape(text)}</p>')

    def _exit(s, N, ux, uy):  # distance from centre to node border along (ux,uy)
        a, b = N['w'] / 2, N['h'] / 2
        if N['t'] in ('att', 'circ'):
            return 1 / math.sqrt((ux / a) ** 2 + (uy / b) ** 2)
        if N['t'] == 'rel':
            return 1 / (abs(ux) / a + abs(uy) / b)
        return min(a / abs(ux) if ux else 1e9, b / abs(uy) if uy else 1e9)
    def _seg(s, x1, y1, x2, y2, double, rec=True):
        if rec: s.edges.append(((x1, y1), (x2, y2), double))
        st = f'color:{LINE};border-width:2px'
        if not double:
            s.lines.append(f'<x-connector x1="{x1:.0f}" y1="{y1:.0f}" x2="{x2:.0f}" y2="{y2:.0f}" head="none" style="{st}"></x-connector>')
            return
        L = math.hypot(x2 - x1, y2 - y1); nx, ny = -(y2 - y1) / L * 4, (x2 - x1) / L * 4
        for k in (1, -1):
            s.lines.append(f'<x-connector x1="{x1 + nx*k:.0f}" y1="{y1 + ny*k:.0f}" x2="{x2 + nx*k:.0f}" y2="{y2 + ny*k:.0f}" head="none" style="{st}"></x-connector>')

    # ---- render ------------------------------------------------------
    def node_html(s, N):
        l, t, w, h = N['x'] - N['w']/2, N['y'] - N['h']/2, N['w'], N['h']
        box = f'position:absolute;left:{l:.0f}px;top:{t:.0f}px;width:{w:.0f}px;height:{h:.0f}px;line-height:{h:.0f}px;text-align:center;white-space:nowrap'
        lab = html.escape(N['label'])
        if N['t'] == 'ent':
            return f'<p style="{box};background:{COB};color:{PAPER};font-size:28px;font-weight:700;border-radius:10px">{lab}</p>'
        if N['t'] == 'weak':
            return f'<p style="{box};background:{PAPER};color:{INK};font-size:28px;font-weight:700;border:7px double {COB};border-radius:10px;line-height:{h-14:.0f}px">{lab}</p>'
        if N['t'] == 'att':
            if N['key']: lab = f'<u>{lab}</u>'
            dec = 'text-decoration:underline dashed;' if N['partial'] else ''
            bd = f'2px dashed {COB}' if N['derived'] else '2px solid #B8C1F2'
            return f'<p style="{box};background:#E9ECFB;color:{INK};font-size:24px;{dec}border:{bd};border-radius:50%">{lab}</p>'
        if N['t'] == 'circ':
            return f'<p style="{box};background:{PAPER};color:{COB};font-size:30px;font-weight:700;border:3px solid {COB};border-radius:50%;line-height:{h-6:.0f}px">{lab}</p>'
        if N['t'] == 'rel':
            out = [f'<x-shape kind="diamond" style="position:absolute;left:{l:.0f}px;top:{t:.0f}px;width:{w:.0f}px;height:{h:.0f}px;background:#FFF1D6;border:2px solid {CARD}"></x-shape>']
            if N['ident']:
                out.append(f'<x-shape kind="diamond" style="position:absolute;left:{l+14:.0f}px;top:{t+8:.0f}px;width:{w-28:.0f}px;height:{h-16:.0f}px;background:#FFF1D6;border:2px solid {CARD}"></x-shape>')
            out.append(f'<p style="position:absolute;left:{l:.0f}px;top:{N["y"]-20:.0f}px;width:{w:.0f}px;height:40px;line-height:40px;font-size:24px;font-weight:700;text-align:center;white-space:nowrap;color:#6B3F08">{lab}</p>')
            return '\n'.join(out)

    def html(s):
        out = [f'<section id="{s.sid}" data-transition="fade" style="background:#FBF8F1;color:{INK};font-family:\'Anuphan\', Tahoma, sans-serif;padding:128px 128px 160px">',
               f'<p style="position:absolute;left:128px;top:96px;width:1200px;font-size:28px;font-weight:600;letter-spacing:1px;color:{COB}">{html.escape(s.eyebrow)}</p>',
               f'<h2 style="position:absolute;left:128px;top:136px;width:1664px;font-size:56px;font-weight:700;line-height:1.15">{html.escape(s.title)}</h2>']
        out += s.lines
        out += [s.node_html(N) for N in s.n.values()]
        out += s.marks
        out.append(f'<p style="position:absolute;left:128px;bottom:64px;width:1664px;font-size:26px;color:#4A5280">{html.escape(s.footer)}</p>')
        out.append(f'<aside>{html.escape(s.notes)}</aside>')
        out.append('</section>')
        return '\n'.join(out) + '\n'

slides = []

# 1 ─ users → members / admins + member tiers
s = Slide('eer-users', 'EER · 1 ผู้ใช้และสมาชิก', 'users → members / admins',
          'Map: ตาราง users เดียว + คอลัมน์ role · คอลัมน์ของสมาชิกเป็น nullable · total_spend คำนวณจาก orders ไม่ได้เก็บ',
          'specialization แรก user ทุกคนเป็นสมาชิกหรือแอดมินอย่างใดอย่างหนึ่ง เลยเป็น disjoint คือตัว d และ total คือเส้นคู่ แยกด้วยคอลัมน์ role สมาชิกมีแอตทริบิวต์เฉพาะ คือระดับคั่วกับวิธีชงที่ชอบ ส่วน total_spend เป็นเส้นประเพราะคำนวณจากออเดอร์ที่จัดส่งแล้ว สมาชิกทุกคนต้องอยู่ในระดับหนึ่งเสมอ เลยเป็นเส้นคู่ฝั่ง members ตอน map ใช้ตาราง users ตารางเดียว เพราะแอดมินไม่มีแอตทริบิวต์เฉพาะ แยกตารางไปก็ต้อง JOIN เพิ่มเปล่า ๆ ครับ')
s.ent('users', 560, 420)
for k, x, y, kw in [('id', 330, 300, 1), ('name', 490, 290, 0), ('email', 660, 290, 0), ('phone', 830, 305, 0), ('role', 300, 450, 0), ('address', 840, 420, 0)]:
    s.att(k + '_u', x, y, k, key=kw); s.ln('users', k + '_u')
s.circ('d', 560, 560)
s.ent('admins', 340, 690); s.ent('members', 780, 690)
s.ln('users', 'd', double=True)
s.ln('d', 'admins', subset=True); s.ln('d', 'members', subset=True)
s.label(470, 520, 'role', color='#6B7096', size=24, w=80)
s.note(340, 760, 'ไม่มีแอตทริบิวต์เฉพาะ', w=320)
s.att('total_spend', 990, 560, derived=True); s.ln('members', 'total_spend')
s.att('preferred_roast_level', 640, 850); s.ln('members', 'preferred_roast_level')
s.att('preferred_brew_method', 1040, 850); s.ln('members', 'preferred_brew_method')
s.rel('belongs_to', 1130, 690)
s.ent('member_tiers', 1480, 690)
s.ln('members', 'belongs_to', double=True, card='N', card_at='b')
s.ln('belongs_to', 'member_tiers', card='1', card_at='b')
for k, x, y, kw in [('id', 1350, 560, 1), ('min_spend', 1590, 560, 0), ('discount_percent', 1560, 830, 0), ('early_access', 1330, 830, 0)]:
    s.att(k + '_t', x, y, k, key=kw); s.ln('member_tiers', k + '_t')
slides.append(s)

# 2 ─ products → beans / drip bags + variants + origins + flavor notes
s = Slide('eer-products', 'EER · 2 สินค้า', 'products → beans / drip bags',
          'Map: products ตารางเดียว (type) · product_variants เป็น weak entity แยกตาราง — ราคาและสต็อกอยู่ที่ variant',
          'specialization ที่สอง สินค้าแยกเป็นเมล็ดคั่วกับดริปแบ็ก แยกด้วยคอลัมน์ type แต่ที่สำคัญกว่าคือ product_variants ซึ่งเป็น weak entity กรอบคู่ ระบุตัวตนด้วย product_id บวก size ที่ขีดเส้นประ เมล็ดเดียวขายหลายขนาด และแต่ละขนาดมีราคากับสต็อกของตัวเอง ถ้าเก็บใน products จะต้องมีคอลัมน์ราคาซ้ำ ๆ หลายช่อง เลยแยกเป็นแถว สินค้ามาจากแหล่งปลูกเดียว และมีโน้ตรสชาติได้หลายตัวแบบ M:N ครับ')
s.ent('products', 720, 430)
for k, x, y, kw in [('id', 560, 300, 1), ('name', 720, 285, 0), ('roast_level', 900, 295, 0), ('process', 1070, 330, 0), ('type', 880, 570, 0)]:
    s.att(k + '_p', x, y, k, key=kw); s.ln('products', k + '_p')
s.circ('d', 720, 580)
s.ent('beans', 500, 730); s.ent('drip_bags', 940, 730)
s.ln('products', 'd', double=True)
s.ln('d', 'beans', subset=True); s.ln('d', 'drip_bags', subset=True)
s.note(500, 800, '100 / 250 / 500 g', w=300); s.note(940, 800, 'sachet', w=240)
s.rel('from', 440, 430, w=170); s.ent('origins', 232, 430, w=200)
s.ln('products', 'from', card='N', card_at='b', side=-1); s.ln('from', 'origins', card='1', card_at='b', side=-1)
s.att('country', 232, 300); s.ln('origins', 'country')
s.att('region', 240, 570); s.ln('origins', 'region')
s.rel('has_size', 1180, 430, ident=True)
s.ent('product_variants', 1530, 430, weak=True, w=340)
s.ln('products', 'has_size', card='1', card_at='b')
s.ln('has_size', 'product_variants', double=True, card='N', card_at='a')
for k, x, y, pk in [('size', 1400, 300, 1), ('price', 1580, 290, 0), ('stock', 1720, 360, 0), ('sku', 1700, 530, 0)]:
    s.att(k + '_v', x, y, k, partial=bool(pk)); s.ln('product_variants', k + '_v')
s.rel('has_note', 1200, 660)
s.ent('flavor_notes', 1530, 760)
s.ln('products', 'has_note', card='M', card_at='b')
s.ln('has_note', 'flavor_notes', card='N', card_at='a')
s.att('name_f', 1440, 880, 'name'); s.ln('flavor_notes', 'name_f')
slides.append(s)

# 3 ─ cart & orders
s = Slide('eer-orders', 'EER · 3 ตะกร้าและออเดอร์', 'carts · orders · ประวัติสถานะ',
          'Map: in_cart → cart_items · contains → order_items (ราคา ณ วันสั่ง) · changes_status → order_status_changes',
          'ฝั่งการสั่งซื้อ สมาชิกหนึ่งคนมีตะกร้าเดียว ของในตะกร้าเป็น M:N ระหว่างตะกร้ากับ variant มีแอตทริบิวต์ grind กับ quantity จึงกลายเป็นตาราง cart_items สมาชิกสั่งได้หลายออเดอร์ ออเดอร์ก็เป็น M:N กับ variant ผ่าน order_items ซึ่งเก็บราคา ณ วันสั่งไว้ ส่วนแอดมินเปลี่ยนสถานะออเดอร์ได้หลายครั้ง เป็น M:N ที่มีแอตทริบิวต์ from_status กับ to_status จึงเป็นตาราง order_status_changes ครับ')
s.ent('members', 300, 370)
s.rel('owns', 300, 540, w=170)
s.ent('carts', 300, 710, w=200)
s.ln('members', 'owns', card='1', card_at='b', side=-1); s.ln('owns', 'carts', double=True, card='1', card_at='a', side=-1)
s.rel('places', 760, 370, w=190)
s.ent('orders', 1240, 370, w=220)
s.ln('members', 'places', card='1', card_at='b'); s.ln('places', 'orders', double=True, card='N', card_at='a')
for k, x, y in [('order_number', 1100, 265), ('status', 1330, 260), ('total', 1500, 300)]:
    s.att(k, x, y); s.ln('orders', k)
s.ent('product_variants', 1240, 710, w=320)
s.rel('in_cart', 760, 710, w=190)
s.ln('carts', 'in_cart', card='M', card_at='b'); s.ln('in_cart', 'product_variants', card='N', card_at='a')
s.att('grind', 660, 860); s.ln('in_cart', 'grind')
s.att('quantity', 860, 860); s.ln('in_cart', 'quantity')
s.rel('contains', 1240, 540, w=210)
s.ln('orders', 'contains', double=True, card='M', card_at='b', side=-1); s.ln('contains', 'product_variants', card='N', card_at='a', side=-1)
s.att('unit_price', 1000, 480); s.ln('contains', 'unit_price')
s.att('quantity_o', 1010, 600, 'quantity'); s.ln('contains', 'quantity_o')
s.ent('admins', 1660, 710, w=200)
s.rel('changes_status', 1640, 530, w=300)
s.ln('orders', 'changes_status', card='M', card_at='b'); s.ln('changes_status', 'admins', card='N', card_at='a', side=-1)
s.att('from_status', 1660, 400); s.ln('changes_status', 'from_status')
s.att('to_status', 1440, 640); s.ln('changes_status', 'to_status')
slides.append(s)

# 4 ─ promotions
s = Slide('eer-promotions', 'EER · 4 โปรโมชั่น', 'promotions → coupons / buy X get Y',
          'Map: แยกตาราง supertype + subtype (promotion_id เป็นทั้ง PK และ FK) · ส่วนลดที่ใช้จริงเก็บใน order_discounts',
          'โปรโมชั่นทุกแบบมีชื่อ วันเริ่ม วันหมด และสถานะเหมือนกัน เก็บที่ supertype promotions แล้วแยกเป็นคูปองกับซื้อ X แถม Y แบบ disjoint และ total ตรงนี้ต่างจากสองอันแรก เพราะ subtype มีแอตทริบิวต์ของตัวเอง เลย map เป็นตารางแยก ใช้ promotion_id เป็นทั้ง PK และ FK โปรผูกกับสินค้าที่ร่วมรายการแบบ M:N และส่วนลดที่ใช้จริงในแต่ละออเดอร์เก็บใน order_discounts พร้อมจำนวนเงิน ยอดย้อนหลังจึงไม่เปลี่ยนแม้แก้โปรทีหลังครับ')
s.ent('promotions', 700, 430)
for k, x, y, kw in [('id', 440, 310, 1), ('name', 610, 290, 0), ('starts_at', 800, 290, 0), ('ends_at', 980, 310, 0), ('type', 460, 520, 0), ('is_active', 1150, 320, 0)]:
    s.att(k + '_p', x, y, k, key=kw); s.ln('promotions', k + '_p')
s.circ('d', 700, 580)
s.ent('coupons', 470, 730, w=220); s.ent('buy_x_get_y', 940, 730)
s.ln('promotions', 'd', double=True)
s.ln('d', 'coupons', subset=True); s.ln('d', 'buy_x_get_y', subset=True)
s.att('code', 260, 860); s.ln('coupons', 'code')
s.att('percent_off', 500, 860); s.ln('coupons', 'percent_off')
s.att('buy_quantity', 820, 860); s.ln('buy_x_get_y', 'buy_quantity')
s.att('free_quantity', 1090, 860); s.ln('buy_x_get_y', 'free_quantity')
s.rel('applies_to', 1240, 430)
s.ent('products', 1580, 430)
s.ln('promotions', 'applies_to', card='M', card_at='b'); s.ln('applies_to', 'products', card='N', card_at='a')
s.rel('discounts', 1280, 660)
s.ent('orders', 1600, 760, w=220)
s.ln('promotions', 'discounts', card='N', card_at='b', side=-1); s.ln('discounts', 'orders', card='M', card_at='a', side=-1)
s.att('amount', 1360, 860); s.ln('discounts', 'amount')
slides.append(s)

# 5 ─ community + taste
s = Slide('eer-community', 'EER · 5 ชุมชนและรสนิยม', 'wish list · reviews · flavor categories',
          'Map: wishes → wishlists · likes → user_taste_preferences · reviews.order_item_id UNIQUE · parent_id อ้างตารางตัวเอง',
          'ส่วนชุมชน สมาชิกกดหัวใจสินค้าได้หลายชิ้นเป็น M:N กลายเป็นตาราง wishlists รีวิวผูกกับรายการที่ซื้อแบบ 1 ต่อ 1 ฝั่งรีวิวเป็นเส้นคู่เพราะรีวิวทุกอันต้องมาจากของที่ซื้อจริง order_item_id เป็น UNIQUE เลยรีวิวได้ครั้งเดียว และไม่เก็บ user_id หรือ product_id ซ้ำ หาได้ด้วย JOIN ส่วน Taste Quiz คือสมาชิกชอบหมวดรสได้หลายหมวด และหมวดรสอ้างถึงตัวเองเป็นลำดับชั้นผ่าน parent_id ครับ')
s.ent('members', 300, 420)
s.rel('wishes', 680, 420, w=190)
s.ent('products', 1050, 420, w=230)
s.ln('members', 'wishes', card='M', card_at='b'); s.ln('wishes', 'products', card='N', card_at='a')
s.att('created_at', 680, 290); s.ln('wishes', 'created_at')
s.rel('likes', 300, 610, w=170)
s.ent('flavor_categories', 330, 800, w=320)
s.ln('members', 'likes', card='M', card_at='b', side=-1); s.ln('likes', 'flavor_categories', card='N', card_at='a', side=-1)
s.rel('parent_of', 790, 800, w=210)
s.n['parent_of']['y'] = 800
s.ln_pts(490, 780, 790, 745); s.ln_pts(490, 820, 790, 855)
s.label(620, 735, '1 parent', w=150, size=24); s.label(620, 868, 'N child', w=150, size=24)
s.ent('order_items', 1050, 740, w=250)
s.rel('reviewed', 1360, 740, w=190)
s.ent('reviews', 1640, 740, w=200)
s.ln('order_items', 'reviewed', card='1', card_at='b'); s.ln('reviewed', 'reviews', double=True, card='1', card_at='a')
for k, x, y, kw in [('id_r', 1520, 600, 1), ('rating', 1720, 600, 0), ('brew_method', 1540, 880, 0), ('comment', 1720, 880, 0)]:
    s.att(k, x, y, 'id' if k == 'id_r' else k, key=kw); s.ln('reviews', k)
s.note(1050, 640, 'ผู้รีวิวและสินค้า หาได้ด้วย JOIN', w=420)
slides.append(s)

if __name__ == '__main__':
  out = sys.argv[1] if len(sys.argv) > 1 else '.'
  for sl in slides:
    open(f'{out}/{sl.sid}.html', 'w').write(sl.html())
    print(sl.sid, sl.html().count('<') // 2, 'elements approx')
