import html
E, A, R, D = '#615CF6', '#E2E1FB', '#8F8CF7', '#CFE3FF'
INK, GRAY = '#222', '#8A8A9A'
out = []
under = []
LAYER = {'lines': out}
def t(x, y, s, size=20, fill=INK, weight=400, anchor='middle', deco=None, family='Inter, Anuphan, sans-serif'):
    d = f' text-decoration="{deco}"' if deco else ''
    out.append(f'<text x="{x}" y="{y}" font-size="{size}" fill="{fill}" font-weight="{weight}" text-anchor="{anchor}" dominant-baseline="middle" font-family="{family}"{d}>{html.escape(s)}</text>')
def line(x1, y1, x2, y2, w=1.6, color=INK, dash=None):
    d = f' stroke-dasharray="{dash}"' if dash else ''
    LAYER['lines'].append(f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{color}" stroke-width="{w}"{d}/>')
def double(x1, y1, x2, y2, gap=3.5):
    import math
    dx, dy = x2 - x1, y2 - y1; L = math.hypot(dx, dy); nx, ny = -dy / L * gap, dx / L * gap
    line(x1 + nx, y1 + ny, x2 + nx, y2 + ny); line(x1 - nx, y1 - ny, x2 - nx, y2 - ny)
def entity(cx, cy, label, w=160, weak=False):
    h = 56
    out.append(f'<rect x="{cx-w/2}" y="{cy-h/2}" width="{w}" height="{h}" rx="6" fill="{E}"/>')
    if weak:
        out.append(f'<rect x="{cx-w/2+5}" y="{cy-h/2+5}" width="{w-10}" height="{h-10}" rx="4" fill="none" stroke="#fff" stroke-width="1.6"/>')
    t(cx, cy + 1, label, 21, '#fff', 700)
def attr(cx, cy, label, key=None, rx=None):
    rx = rx or max(48, len(label) * 6.2 + 26)
    out.append(f'<ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="29" fill="{A}"/>')
    t(cx, cy + 1, label, 20, INK, 400, deco='underline' if key == 'pk' else None)
    if key == 'partial':
        w = len(label) * 10.5
        out.append(f'<line x1="{cx-w/2}" y1="{cy+14}" x2="{cx+w/2}" y2="{cy+14}" stroke="{INK}" stroke-width="1.6" stroke-dasharray="5 4"/>')
def rel(cx, cy, label, w=150, ident=False):
    h = 76
    pts = f'{cx-w/2},{cy} {cx},{cy-h/2} {cx+w/2},{cy} {cx},{cy+h/2}'
    out.append(f'<polygon points="{pts}" fill="{R}" stroke-linejoin="round"/>')
    if ident:
        pts2 = f'{cx-w/2+12},{cy} {cx},{cy-h/2+7} {cx+w/2-12},{cy} {cx},{cy+h/2-7}'
        out.append(f'<polygon points="{pts2}" fill="none" stroke="#fff" stroke-width="1.6"/>')
    t(cx, cy + 1, label, 20, '#fff', 700)
def card(x, y, s):
    t(x, y, s, 17, INK, 700)
def panel(x, y, w, h, label, color):
    out.append(f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="8" fill="none" stroke="{color}" stroke-width="4"/>')
    lw = len(label) * 13 + 30
    out.append(f'<rect x="{x}" y="{y-70}" width="{lw}" height="58" rx="8" fill="{color}"/>')
    t(x + lw / 2, y - 41, label, 26, '#fff', 500, family='Inter, sans-serif')

# ---------------- Chen panel ----------------
panel(40, 110, 1760, 1000, 'Chen', '#6366F1')
LAYER['lines'] = under
PX, PY = 760, 470          # products
for (x, y, s_, k) in [(470, 380, 'id', 'pk'), (510, 290, 'name', None), (620, 215, 'process', None), (760, 190, 'type', None),
                     (900, 215, 'roast_level', None), (1010, 290, 'roast_date', None), (1050, 380, 'is_active', None)]:
    line(x, y, PX, PY); attr(x, y, s_, k)
t(760, 148, 'discriminator', 15, GRAY)
entity(PX, PY, 'products', 170)
# origins N:1
line(170, 470, PX, 470)
line(170, 470, 170, 590); line(170, 470, 310, 610)
entity(170, 470, 'origins', 140)
attr(170, 590, 'id', 'pk'); attr(310, 610, 'country')
rel(410, 470, 'grown_in', 160)
card(305, 450, '1'); card(515, 450, 'N')
# products 1 — has_size (identifying) — N product_variants (weak, total)
VX, VY = 1300, 470
line(PX, PY, 1010, 470)
double(1090, 470, VX - 115, 470)
for (x, y, s_, k) in [(1170, 320, 'weight_g', None), (1300, 290, 'price', None), (1430, 320, 'stock', None),
                     (1210, 610, 'size', 'partial'), (1360, 620, 'sku', None)]:
    line(x, y, VX, VY)
line(VX + 60, VY, 1560, 760)
rel(1010, 470, 'has_size', 160, ident=True)
card(860, 450, '1'); card(1150, 450, 'N')
entity(VX, VY, 'product_variants', 230, weak=True)
for (x, y, s_, k) in [(1170, 320, 'weight_g', None), (1300, 290, 'price', None), (1430, 320, 'stock', None),
                     (1210, 610, 'size', 'partial'), (1360, 620, 'sku', None)]:
    attr(x, y, s_, k)
t(1285, 675, 'partial key: size  →  ระบุตัวด้วย (product_id, size)', 16, GRAY)
# product_variants 1 — sold_as — N order_items
line(1560, 760, 1560, 900)
line(1560, 900, 1460, 1010); line(1560, 900, 1680, 1010); line(1560, 900, 1300, 900)
rel(1560, 760, 'sold_as', 140)
card(1505, 620, '1'); card(1585, 830, 'N')
entity(1560, 900, 'order_items', 180)
attr(1460, 1010, 'id', 'pk'); attr(1680, 1010, 'quantity'); attr(1290, 900, 'grind')
# specialization: products ⇒ d (total, disjoint), attribute-defined on type
import math
double(PX, PY + 28, PX, 640 - 28)
BX, DX, SY = 560, 960, 800
marks = []
for (sx, lbl_) in [(BX, 'beans'), (DX, 'drip_bags')]:
    x1, y1, x2, y2 = PX + (sx - PX) * 0.14, 640 + (SY - 640) * 0.2, sx, SY - 28
    line(x1, y1, x2, y2)
    mx, my = (x1 + x2) / 2, (y1 + y2) / 2
    toward_d = math.degrees(math.atan2(y1 - y2, x1 - x2))   # open side of ⊂ faces the superclass
    marks.append(f'<text x="{mx}" y="{my}" font-size="26" text-anchor="middle" dominant-baseline="central" transform="rotate({toward_d} {mx} {my})" font-family="DejaVu Sans, sans-serif" fill="{INK}">⊂</text>')
out.append(f'<circle cx="{PX}" cy="640" r="28" fill="{D}"/>'); t(PX, 641, 'd', 22)
out.extend(marks)
t(PX + 16, 575, 'type', 17, GRAY, 400, 'start')
entity(BX, SY, 'beans', 170); entity(DX, SY, 'drip_bags', 170)
t(BX, 860, "type = 'bean'", 17, GRAY); t(BX, 888, 'ขาย 100 / 250 / 500 g · เลือกการบดได้', 17, GRAY)
t(DX, 860, "type = 'drip_bag'", 17, GRAY); t(DX, 888, 'ขายเป็นซอง 12 g · ไม่ต้องบด', 17, GRAY)
t(PX, 980, 'disjoint (d) · total (เส้นคู่จาก products) · attribute-defined บน type', 18, GRAY)
LAYER['lines'] = out
out[:0] = []  # keep panel first
chen = '\n'.join(out[:2] + under + out[2:]); out.clear(); under.clear()

# ---------------- Mermaid-style mapping panel ----------------
W_TYPE, W_NAME, W_KEY, RH = 150, 230, 110, 40
def table(x, y, title, rows, note=None, wn=W_NAME):
    w = W_TYPE + wn + W_KEY + 8
    out.append(f'<rect x="{x}" y="{y}" width="{w}" height="{RH}" fill="{E}"/>')
    t(x + w / 2, y + RH / 2 + 1, title, 21, '#fff', 700)
    for i, (ty, nm, key, extra) in enumerate(rows):
        yy = y + RH + 4 + i * (RH + 4)
        for (cx, cw) in [(x, W_TYPE), (x + W_TYPE + 4, wn), (x + W_TYPE + wn + 8, W_KEY)]:
            out.append(f'<rect x="{cx}" y="{yy}" width="{cw}" height="{RH}" fill="#EEEEFC"/>')
        t(x + W_TYPE / 2, yy + RH / 2 + 1, ty, 19)
        under = 'underline' if key and 'PK' in key else None
        if extra:
            nmw = len(nm) * 10.5
            tx = x + W_TYPE + 4 + wn / 2
            out.append(f'<text x="{tx}" y="{yy+RH/2+1}" font-size="19" text-anchor="middle" dominant-baseline="middle" font-family="Inter, Anuphan, sans-serif" fill="{INK}"><tspan{" text-decoration=\"underline\"" if under else ""}>{html.escape(nm)}</tspan> <tspan fill="#6157F6">{html.escape(extra)}</tspan></text>')
        else:
            t(x + W_TYPE + 4 + wn / 2, yy + RH / 2 + 1, nm, 19, deco=under)
        t(x + W_TYPE + wn + 8 + W_KEY / 2, yy + RH / 2 + 1, key or '', 19)
    bottom = y + RH + 4 + len(rows) * (RH + 4)
    if note:
        t(x + w / 2, bottom + 14, note, 16, '#6157F6')
    return (x, y, w, bottom)
def path(d, color='#8E94A6'):
    out.append(f'<path d="{d}" fill="none" stroke="{color}" stroke-width="3" stroke-linecap="round"/>')
def one(x, y, horiz=True):  # || mark
    c = '#8E94A6'
    if horiz:
        line(x, y - 11, x, y + 11, 3, c); line(x + 8, y - 11, x + 8, y + 11, 3, c)
    else:
        line(x - 11, y, x + 11, y, 3, c); line(x - 11, y + 8, x + 11, y + 8, 3, c)
def many(x, y, direction, optional=True):  # crow's foot pointing into table at (x,y); direction = 'left'|'right'|'down'|'up' (where the table is)
    c = '#8E94A6'
    dx, dy = {'right': (1, 0), 'left': (-1, 0), 'down': (0, 1), 'up': (0, -1)}[direction]
    bx, by = x - dx * 16, y - dy * 16
    px, py = -dy, dx
    for s in (-1, 0, 1):
        line(bx, by, x + px * s * 12, y + py * s * 12, 3, c)
    if optional:
        ox, oy = x - dx * 30, y - dy * 30
        out.append(f'<circle cx="{ox}" cy="{oy}" r="9" fill="#fff" stroke="{c}" stroke-width="3"/>')
    else:
        line(x - dx * 26 + px * 11, y - dy * 26 + py * 11, x - dx * 26 - px * 11, y - dy * 26 - py * 11, 3, c)
def lbl(x, y, s):
    t(x, y, s, 19, INK, 700)

panel(40, 1250, 1760, 960, 'Mermaid', '#B42CE6')
P = table(600, 1320, 'products', [
    ('big-int', 'id', 'PK', None), ('big-int', 'origin_id', 'FK', None), ('string', 'name', '', None), ('string', 'slug', 'UK', None),
    ('enum', 'type', '', "('bean' | 'drip_bag')"), ('enum', 'process', '', None), ('enum', 'roast_level', '', None),
    ('date', 'roast_date', '', None), ('boolean', 'is_active', '', None)], wn=260)
V = table(1262, 1320, 'product_variants', [
    ('big-int', 'id', 'PK', None), ('big-int', 'product_id', 'FK', None), ('enum', 'size', '', None),
    ('int', 'weight_g', '', '(>0)'), ('decimal', 'price', '', '(>0)'), ('int', 'stock', '', '(≥0)'), ('string', 'sku', 'UK', None)],
    note='UNIQUE (product_id, size)', wn=250)
O = table(1262, 1800, 'order_items', [
    ('big-int', 'id', 'PK', None), ('big-int', 'order_id', 'FK', None), ('big-int', 'product_variant_id', 'FK', None),
    ('enum', 'grind', '', None), ('decimal', 'unit_price', '', '(snapshot)'), ('int', 'quantity', '', '(>0)')], wn=250)
C = table(600, 1900, 'cart_items', [
    ('big-int', 'id', 'PK', None), ('big-int', 'cart_id', 'FK', None), ('big-int', 'product_variant_id', 'FK', None),
    ('enum', 'grind', '', None), ('int', 'quantity', '', '(1–20)')], note='UNIQUE (cart_id, product_variant_id, grind)', wn=260)
# products ||--|{ product_variants  "has size"
def rowy(top, i): return top + 40 + 4 + i * 44 + 20
x1, y1 = P[0] + P[2], rowy(1320, 0)
x2, y2 = V[0], rowy(1320, 1)
path(f'M{x1+2} {y1} C {x1+70} {y1}, {x2-70} {y2}, {x2-2} {y2}')
one(x1 + 12, y1); many(x2 - 2, y2, 'right', optional=False)
lbl((x1 + x2) / 2, y1 - 22, 'has size')
# product_variants ||--o{ order_items  "sold as"
vx = V[0] + V[2] / 2
path(f'M{vx} {V[3]+30} L {vx} {1800-4}')
one(vx, V[3] + 34, horiz=False); many(vx, 1800 - 2, 'down')
lbl(vx + 70, (V[3] + 30 + 1800) / 2, 'sold as')
# product_variants ||--o{ cart_items  "in cart"
sx, sy = V[0], rowy(1320, 5)
ex, ey = C[0] + C[2], rowy(1900, 2)
mid = (P[0] + P[2] + V[0]) / 2
path(f'M{sx-2} {sy} C {mid} {sy}, {mid} {ey}, {ex+2} {ey}')
one(sx - 22, sy); many(ex + 2, ey, 'left')
lbl(mid + 4, (sy + ey) / 2, 'in cart')
# specialization note (single-table mapping)
nx, ny, nw = 80, 1330, 470
out.append(f'<rect x="{nx}" y="{ny}" width="{nw}" height="300" rx="10" fill="#F7F6FF" stroke="#6157F6" stroke-width="2" stroke-dasharray="7 6"/>')
t(nx + 24, ny + 34, 'beans / drip_bags  →  single table', 20, '#6157F6', 700, 'start')
for i, s in enumerate(['ไม่มีคอลัมน์เฉพาะ subclass จึงรวมเป็น',
                       'ตาราง products + discriminator "type"',
                       "beans      = σ type='bean' (products)",
                       "drip_bags = σ type='drip_bag' (products)",
                       'ข้อจำกัดของแต่ละ subclass อยู่ที่ variant:',
                       "bean → size ∈ {100g, 250g, 500g}",
                       "drip_bag → size = sachet, grind = none"]):
    t(nx + 24, ny + 78 + i * 30, s, 18, INK, 400, 'start')
path(f'M{nx+nw} {ny+150} C {nx+nw+30} {ny+150}, {P[0]-40} {rowy(1320, 4)}, {P[0]-4} {rowy(1320, 4)}', '#C9C6F7')
mermaid = '\n'.join(out)

doc = f'''<!doctype html><html><head><meta charset="utf-8">
<link href="https://fonts.bunny.net/css?family=anuphan:400,500,700|inter:400,500,700&display=swap" rel="stylesheet">
<style>body{{margin:0;background:#fff}} svg{{display:block}}</style></head><body>
<svg id="all" width="1840" height="2250" viewBox="0 0 1840 2250" xmlns="http://www.w3.org/2000/svg">
<rect width="1840" height="2250" fill="#fff"/>
{chen}
{mermaid}
</svg></body></html>'''
open('/tmp/claude-0/-home-claude/29c617a8-675f-54ed-9382-f7a44229947b/scratchpad/er/products-eer.html', 'w').write(doc)
