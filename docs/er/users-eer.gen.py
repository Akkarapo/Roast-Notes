import math
from xml.sax.saxutils import escape
E, A, R, D, INK, GRAY = '#615CF6', '#E2E1FB', '#8F8CF7', '#CFE3FF', '#222222', '#8A8A9A'
FONT = 'fontFamily=Helvetica;'
cells = []
n = [10]
def nid():
    n[0] += 1; return f'n{n[0]}'
def q(s): return escape(s, {'"': '&quot;'})
def v(value, style, x, y, w, h, id_=None, parent='1'):
    id_ = id_ or nid()
    cells.append(f'<mxCell id="{id_}" value="{q(value)}" style="{style}" vertex="1" parent="{parent}"><mxGeometry x="{x}" y="{y}" width="{w}" height="{h}" as="geometry"/></mxCell>')
    return id_
def e(src, tgt, style='endArrow=none;html=1;strokeColor=#222222;strokeWidth=1.5;', label='', pts=None):
    id_ = nid()
    geo = '<mxGeometry relative="1" as="geometry"/>' if not pts else '<mxGeometry relative="1" as="geometry"><Array as="points">' + ''.join(f'<mxPoint x="{a}" y="{b}"/>' for a, b in pts) + '</Array></mxGeometry>'
    cells.append(f'<mxCell id="{id_}" value="{q(label)}" style="{style}" edge="1" parent="1" source="{src}" target="{tgt}">{geo}</mxCell>')
    return id_
def text(x, y, s, size=14, color=INK, bold=False, align='center', w=None, italic=False, h=24):
    w = w or max(40, len(s) * size * 0.62)
    style = f'text;html=1;align={align};verticalAlign=middle;fontSize={size};fontColor={color};fontStyle={(1 if bold else 0) + (2 if italic else 0)};{FONT}whiteSpace=wrap;'
    x0 = x - w / 2 if align == 'center' else x
    return v(s, style, x0, y - h / 2, w, h)
def entity(cx, cy, label, w=170):
    return v(label, f'rounded=1;arcSize=8;whiteSpace=wrap;html=1;fillColor={E};strokeColor={E};fontColor=#FFFFFF;fontStyle=1;fontSize=18;{FONT}', cx - w / 2, cy - 28, w, 56)
def attr(cx, cy, label, key=None, rx=None, derived=False):
    w = rx * 2 if rx else max(90, len(label) * 9.4 + 40)
    style = f'ellipse;whiteSpace=wrap;html=1;fillColor={A};strokeColor={A if not derived else "#615CF6"};fontColor={INK};fontSize=16;{FONT}'
    if key == 'pk': style += 'fontStyle=4;'
    if derived: style += 'dashed=1;strokeWidth=1.5;'
    val = label if key != 'partial' else f'<span style="text-decoration: underline dashed;">{label}</span>'
    return v(val, style, cx - w / 2, cy - 28, w, 56)
def rel(cx, cy, label, w=170):
    return v(label, f'rhombus;whiteSpace=wrap;html=1;fillColor={R};strokeColor={R};fontColor=#FFFFFF;fontStyle=1;fontSize=17;{FONT}', cx - w / 2, cy - 38, w, 76)
def circle(cx, cy, label='d'):
    return v(label, f'ellipse;whiteSpace=wrap;html=1;aspect=fixed;fillColor={D};strokeColor={D};fontSize=18;fontColor={INK};{FONT}', cx - 28, cy - 28, 56, 56)
def card(x, y, s): text(x, y, s, 15, INK, True, w=30)
def double(a, b): return e(a, b, f'shape=link;html=1;endArrow=none;strokeColor={INK};strokeWidth=1.2;width=6;')
def panel(x, y, w, h, label, color):
    v('', f'rounded=1;arcSize=1;whiteSpace=wrap;html=1;fillColor=none;strokeColor={color};strokeWidth=4;', x, y, w, h)
    lw = len(label) * 13 + 34
    v(label, f'rounded=1;arcSize=12;whiteSpace=wrap;html=1;fillColor={color};strokeColor={color};fontColor=#FFFFFF;fontSize=22;{FONT}', x, y - 70, lw, 56)

# ===================== Chen =====================
panel(40, 110, 1760, 1120, 'Chen', '#6366F1')
U = (820, 430)
uattrs = [(570, 340, 'id', 'pk'), (610, 250, 'name', None), (710, 185, 'email', None), (850, 160, 'role', None),
          (990, 185, 'password', None), (1090, 250, 'phone', None), (1120, 340, 'address', None)]
ids = {}
for x, y, s, k in uattrs: ids[s] = attr(x, y, s, k)
text(850, 120, 'discriminator', 12, GRAY)
users = entity(*U, 'users')
for x, y, s, k in uattrs: e(users, ids[s])
# users M — changes_status — N orders  (who moved an order's status; customer or admin)
orders = entity(170, 430, 'orders', 150)
o_id = attr(120, 320, 'id', 'pk'); o_st = attr(240, 290, 'status'); o_tot = attr(350, 330, 'total')
for a_ in (o_id, o_st, o_tot): e(orders, a_)
cs = rel(470, 430, 'changes_status', 200)
e(cs, orders); e(users, cs)
card(310, 410, 'N'); card(640, 410, 'M')
for x, y, s in [(360, 560, 'from_status'), (530, 570, 'to_status'), (680, 545, 'note')]:
    e(cs, attr(x, y, s))
text(470, 615, 'บันทึกเป็นตาราง order_status_changes', 13, GRAY, w=300)
# specialization users ⇒ d (total, disjoint) on role
d = circle(820, 640)
double(users, d)
text(850, 540, 'role', 14, GRAY, w=40, align='left')
members = entity(470, 790, 'members')
admins = entity(1170, 790, 'admins')
for sub, tx in [(members, 470), (admins, 1170)]:
    e(d, sub)
# ⊂ marks on the d → subclass lines (open side faces the superclass)
for sx in (470, 1170):
    x1, y1, x2, y2 = 820, 640, sx, 762
    mx, my = x1 + (x2 - x1) * 0.45, y1 + (y2 - y1) * 0.45
    ang = math.degrees(math.atan2(y1 - y2, x1 - x2))
    v('⊂', f'text;html=1;align=center;verticalAlign=middle;fontSize=22;fontColor={INK};rotation={ang};{FONT}', mx - 15, my - 15, 30, 30)
text(1170, 850, "role = 'admin'", 14, GRAY, w=200)
text(1170, 876, 'จัดการสินค้า · โปร · ออเดอร์ · สมาชิก · รีวิว ผ่าน /admin', 14, GRAY, w=420)
text(1170, 902, 'ไม่มีระดับสมาชิก ไม่ทำ Taste Quiz', 14, GRAY, w=300)
text(1350, 640, 'disjoint (d) · total (เส้นคู่จาก users) · attribute-defined บน role', 14, GRAY, w=470)
# members attributes
m_attrs = [(740, 760, 'preferred_roast_level', None, False), (770, 850, 'preferred_brew_method', None, False),
           (720, 940, 'taste_quiz_completed_at', None, False), (230, 900, 'total_spend', None, True)]
for x, y, s, k, der in m_attrs: e(members, attr(x, y, s, k, derived=der))
text(230, 945, 'derived = SUM(total) ของออเดอร์ที่ได้รับแล้ว', 13, GRAY, w=320)
# members N — belongs_to — 1 member_tiers
bt = rel(470, 950, 'belongs_to', 170)
tiers = entity(470, 1110, 'member_tiers', 190)
double(members, bt)
e(bt, tiers)
card(495, 870, 'N'); card(495, 1040, '1')
for x, y, s, k in [(260, 1050, 'id', 'pk'), (250, 1150, 'name', None), (700, 1040, 'min_spend', None),
                   (720, 1140, 'discount_percent', None), (930, 1110, 'early_access', None)]:
    e(tiers, attr(x, y, s, k))
# members 1 — places — N orders
pl = rel(170, 600, 'places', 140)
e(orders, pl); e(pl, members, pts=[(170, 790)])
card(190, 500, 'N'); card(340, 770, '1')

# ===================== Tier logic =====================
LY = 1380
panel(40, LY, 1760, 640, 'Tier logic', '#2F9E8F')
FLOW = f'rounded=1;arcSize=14;whiteSpace=wrap;html=1;fillColor=#EEEEFC;strokeColor={E};strokeWidth=1.5;fontColor={INK};fontSize=15;{FONT}'
START = f'rounded=1;arcSize=40;whiteSpace=wrap;html=1;fillColor={E};strokeColor={E};fontColor=#FFFFFF;fontStyle=1;fontSize=15;{FONT}'
DEC = f'rhombus;whiteSpace=wrap;html=1;fillColor={R};strokeColor={R};fontColor=#FFFFFF;fontStyle=1;fontSize=15;{FONT}'
OK = f'rounded=1;arcSize=14;whiteSpace=wrap;html=1;fillColor=#E3F5EE;strokeColor=#2F9E8F;strokeWidth=1.5;fontColor={INK};fontSize=15;{FONT}'
ARR = f'endArrow=block;endFill=1;html=1;strokeColor=#5A5F73;strokeWidth=2;fontSize=14;fontColor={INK};fontStyle=1;'
y0 = LY + 70
s1 = v('แอดมินเปลี่ยนออเดอร์เป็น<br><b>"ได้รับแล้ว" (delivered)</b><br><span style="font-size:12px">Order::markDelivered()</span>', START, 80, y0, 250, 100)
s2 = v('<b>คำนวณยอดสะสม</b><br>spend = SUM(orders.total)<br>WHERE user_id = ?<br>AND status = \'delivered\'', FLOW, 400, y0, 270, 100)
s3 = v('<b>หาระดับที่ได้</b><br>earned = ระดับที่ min_spend ≤ spend<br>ที่สูงที่สุด', FLOW, 740, y0, 280, 100)
s4 = v('earned สูงกว่า<br>ระดับปัจจุบัน?', DEC, 1090, y0 - 10, 220, 120)
s5 = v('<b>เลื่อนระดับ</b><br>UPDATE users<br>SET member_tier_id = earned', OK, 1400, y0 - 60, 300, 90)
s6 = v('<b>คงระดับเดิม</b><br>ระดับขึ้นได้อย่างเดียว ไม่ลดลง', FLOW, 1400, y0 + 80, 300, 80)
e(s1, s2, ARR); e(s2, s3, ARR); e(s3, s4, ARR)
e(s4, s5, ARR + 'exitX=0.5;exitY=0;exitDx=0;exitDy=0;', 'ใช่', pts=[(1200, y0 - 15)])
e(s4, s6, ARR + 'exitX=0.5;exitY=1;exitDx=0;exitDy=0;', 'ไม่', pts=[(1200, y0 + 120)])
# tier table
TY = LY + 270
text(80, TY, 'ระดับสมาชิก (ตาราง member_tiers)', 17, INK, True, 'left', 400)
cols = [('ระดับ', 210), ('ยอดสะสมขั้นต่ำ (min_spend)', 250), ('ส่วนลด', 110), ('ซื้อล็อตหายากก่อน', 190)]
rows = [('Bean', '฿0', '0%', '—'), ('Barista', '≥ ฿25,000', '5%', '—'), ('Roaster Master', '≥ ฿50,000', '10%', '✓ ก่อนคนอื่น 14 วัน')]
x = 80
for i, (h, w) in enumerate(cols):
    v(h, f'whiteSpace=wrap;html=1;fillColor={E};strokeColor=#FFFFFF;fontColor=#FFFFFF;fontStyle=1;fontSize=15;{FONT}', x, TY + 25, w, 40)
    for j, r in enumerate(rows):
        v(r[i], f'whiteSpace=wrap;html=1;fillColor=#EEEEFC;strokeColor=#FFFFFF;fontColor={INK};fontSize=15;{FONT}' + ('fontStyle=1;' if i == 0 else ''), x, TY + 65 + j * 40, w, 40)
    x += w
text(80, TY + 205, 'สมาชิกใหม่เริ่มที่ Bean (users.member_tier_id default = 1) · แอดมินปรับระดับเองได้ แต่การคำนวณครั้งถัดไปเลื่อนขึ้นได้อย่างเดียว', 13, GRAY, False, 'left', 780)
# effects of a tier
FX = 960
text(FX, TY, 'ระดับมีผลตรงไหน', 17, INK, True, 'left', 300)
v('<b>1. ส่วนลดตอนคิดราคา</b> — กฎตัวที่ 3 ของ pipeline<br>ซื้อ X แถม Y → คูปอง → <b>LoyaltyTierRule</b><br>ส่วนลด = (ยอดสินค้า − ส่วนลดก่อนหน้า) × discount_percent ÷ 100 ปัดลง<br>บันทึกใน order_discounts (source = \'loyalty\')',
  FLOW + 'align=left;spacingLeft=14;', FX, TY + 25, 780, 110)
v('<b>2. ล็อตหายาก (early access)</b><br>ถ้า products.exclusive_until &gt; now() ซื้อได้เฉพาะระดับที่ early_access = true<br>ระดับอื่นเห็นสินค้าแต่ใส่ตะกร้าไม่ได้ (ตรวจซ้ำตอนยืนยันออเดอร์)',
  FLOW + 'align=left;spacingLeft=14;', FX, TY + 150, 780, 95)
text(FX, TY + 275, 'นับเฉพาะออเดอร์ที่ได้รับแล้ว · ยกเลิก/ยังไม่ได้รับไม่นับ · ยอดสะสมคำนวณสดจาก orders ไม่เก็บซ้ำ (ไม่มีข้อมูลซ้ำซ้อน)', 13, GRAY, False, 'left', 780)

# ===================== Mermaid (relational mapping) =====================
MY = 2170
panel(40, MY, 1760, 900, 'Mermaid', '#B42CE6')
W_T, W_N, W_K, RH = 130, 260, 90, 38
rowids = {}
def table(x, y, title, rows, note=None):
    w = W_T + W_N + W_K + 8
    v(title, f'whiteSpace=wrap;html=1;fillColor={E};strokeColor={E};fontColor=#FFFFFF;fontStyle=1;fontSize=17;{FONT}', x, y, w, 36)
    for i, (ty, nm, key, extra) in enumerate(rows):
        yy = y + 40 + i * (RH + 4)
        cell_style = f'whiteSpace=wrap;html=1;fillColor=#EEEEFC;strokeColor=none;fontColor={INK};fontSize=15;{FONT}'
        left = v(ty, cell_style, x, yy, W_T, RH)
        label = (f'<u>{nm}</u>' if 'PK' in key else nm) + (f' <font color="#6157F6">{extra}</font>' if extra else '')
        v(label, cell_style, x + W_T + 4, yy, W_N, RH)
        right = v(key, cell_style, x + W_T + W_N + 8, yy, W_K, RH)
        rowids[(title, nm)] = (left, right)
    bottom = y + 40 + len(rows) * (RH + 4)
    if note: text(x + w / 2, bottom + 12, note, 13, '#6157F6', w=w)
    return bottom
T_users = table(640, MY + 70, 'users', [
    ('big-int', 'id', 'PK', ''), ('tiny-int', 'member_tier_id', 'FK', ''), ('string', 'name', '', ''), ('string', 'email', 'UK', ''),
    ('string', 'password', '', ''), ('enum', 'role', '', "('member' | 'admin')"), ('enum', 'preferred_roast_level', '', '(nullable)'),
    ('enum', 'preferred_brew_method', '', '(nullable)'), ('date-time', 'taste_quiz_completed_at', '', '(nullable)')])
T_tiers = table(1260, MY + 70, 'member_tiers', [
    ('tiny-int', 'id', 'PK', ''), ('string', 'slug', 'UK', ''), ('string', 'name', '', ''), ('decimal', 'min_spend', 'UK', '(≥0)'),
    ('tiny-int', 'discount_percent', '', '(0–100)'), ('boolean', 'early_access', '', '')])
T_orders = table(1260, MY + 420, 'orders', [
    ('big-int', 'id', 'PK', ''), ('big-int', 'user_id', 'FK', ''), ('enum', 'status', '', ''), ('decimal', 'total', '', '')])
T_osc = table(640, MY + 520, 'order_status_changes', [
    ('big-int', 'id', 'PK', ''), ('big-int', 'order_id', 'FK', ''), ('big-int', 'changed_by', 'FK', '(nullable)'),
    ('enum', 'from_status', '', '(nullable)'), ('enum', 'to_status', '', ''), ('string', 'note', '', '(nullable)')])
T_utp = table(60, MY + 620, 'user_taste_preferences', [
    ('big-int', 'user_id', 'PK, FK', ''), ('big-int', 'flavor_category_id', 'PK, FK', '')])
CROW = 'html=1;strokeColor=#8E94A6;strokeWidth=2.5;endFill=0;startFill=0;fontSize=15;fontStyle=1;fontColor=#222222;labelBackgroundColor=#FFFFFF;'
ORTH = 'edgeStyle=orthogonalEdgeStyle;rounded=1;'
L, Rt = 0, 1
def link(src, dst, start, end, label, sx, sy, tx, ty, pts=None, side=(1, 0)):
    a_ = rowids[src][side[0]]; b_ = rowids[dst][side[1]]
    e(a_, b_, CROW + ORTH + f'startArrow={start};endArrow={end};exitX={sx};exitY={sy};exitDx=0;exitDy=0;entryX={tx};entryY={ty};entryDx=0;entryDy=0;', label, pts)
# users.member_tier_id  >---||  member_tiers.id
link(('users', 'member_tier_id'), ('member_tiers', 'id'), 'ERmany', 'ERmandOne', 'belongs to tier', 1, 0.5, 0, 0.5, pts=[(1200, MY + 70 + 40 + 42 + 19), (1200, MY + 70 + 40 + 19)], side=(1, 0))
# orders.user_id  >o---||  users.id
link(('orders', 'user_id'), ('users', 'id'), 'ERzeroToMany', 'ERmandOne', 'places', 0, 0.5, 1, 0.3, pts=[(1185, MY + 420 + 40 + 42 + 19), (1185, MY + 70 + 40 + 11)], side=(0, 1))
# order_status_changes.changed_by  >o---o|  users.id
link(('order_status_changes', 'changed_by'), ('users', 'id'), 'ERzeroToMany', 'ERzeroToOne', 'changed by', 1, 0.5, 1, 0.75, pts=[(1160, MY + 520 + 40 + 84 + 19), (1160, MY + 70 + 40 + 29)], side=(1, 1))
# order_status_changes.order_id  >---||  orders.id
link(('order_status_changes', 'order_id'), ('orders', 'id'), 'ERmany', 'ERmandOne', 'history of', 1, 0.5, 0, 0.5, pts=[(1215, MY + 520 + 40 + 42 + 19), (1215, MY + 420 + 40 + 19)], side=(1, 0))
# user_taste_preferences.user_id  >o---||  users.id
link(('user_taste_preferences', 'user_id'), ('users', 'id'), 'ERzeroToMany', 'ERmandOne', 'likes', 1, 0.5, 0, 0.5, pts=[(595, MY + 620 + 40 + 19), (595, MY + 70 + 40 + 19)], side=(1, 0))
v('<b><font color="#6157F6">members / admins → single table</font></b><br><br>'
  'ไม่มีตาราง members / admins แยก ใช้ users + discriminator "role"<br>'
  "members = σ role='member' (users)<br>admins = σ role='admin' (users)<br><br>"
  'คอลัมน์เฉพาะ members (preferred_*, taste_quiz_completed_at)<br>เป็น NULL ได้ เพราะแถวของ admin ไม่ใช้<br>'
  'member_tier_id ใช้กับ members · admin ได้ค่า default (Bean)<br><br>'
  'M:N changes_status (users ↔ orders) + แอตทริบิวต์<br>→ ตาราง order_status_changes',
  f'rounded=1;arcSize=4;whiteSpace=wrap;html=1;fillColor=#F7F6FF;strokeColor=#6157F6;strokeWidth=2;dashed=1;align=left;verticalAlign=top;spacing=16;fontSize=15;fontColor={INK};{FONT}',
  60, MY + 80, 488, 400)

xml = ('<mxfile host="app.diagrams.net" agent="Roast &amp; Notes generator"><diagram id="users-eer" name="users EER">'
       '<mxGraphModel dx="1600" dy="1000" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="0" pageScale="1" pageWidth="1900" pageHeight="3300" math="0" shadow="0">'
       '<root><mxCell id="0"/><mxCell id="1" parent="0"/>' + ''.join(cells) + '</root></mxGraphModel></diagram></mxfile>')
open('users-eer.drawio', 'w').write(xml)
import xml.dom.minidom; xml.dom.minidom.parse('users-eer.drawio'); print('ok', len(cells))
