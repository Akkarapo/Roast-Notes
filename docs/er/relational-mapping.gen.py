import json, re, html
from xml.sax.saxutils import escape
S = json.load(open('schema.json'))
COLS = [
    ['member_tiers', 'users', 'user_taste_preferences', 'flavor_categories'],
    ['carts', 'cart_items', 'wishlists', 'flavor_notes', 'product_flavor_notes'],
    ['orders', 'order_items', 'reviews', 'order_status_changes'],
    ['products', 'product_variants', 'cupping_scores', 'origins'],
    ['promotions', 'coupons', 'buy_x_get_y', 'promotion_products', 'order_discounts'],
]
GROUP = {  # header colours per module
    'member_tiers': '#7A5AF8', 'users': '#615CF6', 'user_taste_preferences': '#615CF6',
    'flavor_categories': '#2F9E8F', 'flavor_notes': '#2F9E8F', 'product_flavor_notes': '#2F9E8F',
    'carts': '#D9822B', 'cart_items': '#D9822B', 'wishlists': '#C2417A', 'reviews': '#C2417A',
    'orders': '#D9822B', 'order_items': '#D9822B', 'order_status_changes': '#D9822B', 'order_discounts': '#B42CE6',
    'products': '#2338C4', 'product_variants': '#2338C4', 'cupping_scores': '#2338C4', 'origins': '#2338C4',
    'promotions': '#B42CE6', 'coupons': '#B42CE6', 'buy_x_get_y': '#B42CE6', 'promotion_products': '#B42CE6',
}
W_KEY, W_NAME, W_TYPE, RH, HEAD = 64, 190, 150, 26, 32
W = W_KEY + W_NAME + W_TYPE
X0, DX, Y0, GAP = 40, W + 120, 120, 70

def short(ty):
    ty = ty.replace(' unsigned', '')
    if ty.startswith('enum('):
        vals = re.findall(r"'([^']*)'", ty)
        return 'enum(' + ','.join(vals) + ')' if len(','.join(vals)) <= 18 else 'enum'
    if ty == 'tinyint(1)': return 'boolean'
    return re.sub(r'^(bigint|int|smallint|tinyint|mediumint)\(\d+\)', r'\1', ty)

cells, ids, rowid = [], {}, {}
nid = [2]
def new():
    nid[0] += 1; return f'c{nid[0]}'
def cell(id_, value, style, x, y, w, h, parent='1', vertex=True):
    cells.append(f'<mxCell id="{id_}" value="{escape(value, {chr(34): "&quot;"})}" style="{style}" vertex="1" parent="{parent}"><mxGeometry x="{x}" y="{y}" width="{w}" height="{h}" as="geometry"/></mxCell>')

for ci, col in enumerate(COLS):
    y = Y0
    for t in col:
        d = S[t]
        pk = d['pk'].split(',')
        fk = {f['c']: f for f in d['fks']}
        uq_single = {u['cols'] for u in d['uq'] if ',' not in u['cols']}
        uq_multi = [u['cols'] for u in d['uq'] if ',' in u['cols']]
        rows = [c for c in d['cols'] if c['c'] not in ('created_at', 'updated_at')]
        ts = 1 if any(c['c'] == 'created_at' for c in d['cols']) else 0
        n = len(rows) + len(uq_multi) + ts
        h = HEAD + n * RH
        x = X0 + ci * DX
        tid = new(); ids[t] = tid
        color = GROUP[t]
        cell(tid, t, f'shape=table;startSize={HEAD};container=1;collapsible=0;childLayout=tableLayout;fixedRows=1;rowLines=0;fontStyle=1;align=center;resizeLast=1;html=1;fillColor={color};fontColor=#FFFFFF;strokeColor={color};fontSize=14;rounded=1;arcSize=4;', x, y, W, h)
        ry = HEAD
        for c in rows:
            keys = []
            if c['c'] in pk: keys.append('PK')
            if c['c'] in fk: keys.append('FK')
            if c['c'] in uq_single: keys.append('UK')
            key = ','.join(keys)
            r = new(); rowid[(t, c['c'])] = r
            is_pk = 'PK' in keys
            last_pk = is_pk and c['c'] == pk[-1]
            cell(r, '', f'shape=tableRow;horizontal=0;startSize=0;swimlaneHead=0;swimlaneBody=0;fillColor=none;collapsible=0;dropTarget=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;top=0;left=0;right=0;bottom={1 if last_pk else 0};strokeColor={color};html=1;', 0, ry, W, RH, parent=tid)
            base = 'shape=partialRectangle;connectable=0;top=0;left=0;bottom=0;right=0;overflow=hidden;whiteSpace=wrap;html=1;fontSize=12;'
            cell(new(), key, base + f'fillColor=#F1F0FE;fontStyle=1;fontColor={color};align=center;', 0, 0, W_KEY, RH, parent=r)
            name = c['c'] + (' ?' if c['n'] == 'YES' else '')
            cell(new(), name, base + f'fillColor=#FFFFFF;align=left;spacingLeft=8;fontStyle={5 if is_pk else 0};', W_KEY, 0, W_NAME, RH, parent=r)
            cell(new(), short(c['ty']), base + 'fillColor=#FFFFFF;align=left;spacingLeft=6;fontColor=#6B6B80;', W_KEY + W_NAME, 0, W_TYPE, RH, parent=r)
            ry += RH
        for extra in [f'UNIQUE ({u.replace(",", ", ")})' for u in uq_multi] + (['created_at, updated_at'] if ts else []):
            r = new()
            cell(r, '', 'shape=tableRow;horizontal=0;startSize=0;swimlaneHead=0;swimlaneBody=0;fillColor=none;collapsible=0;dropTarget=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;top=0;left=0;right=0;bottom=0;html=1;', 0, ry, W, RH, parent=tid)
            base = 'shape=partialRectangle;connectable=0;top=0;left=0;bottom=0;right=0;overflow=hidden;html=1;fontSize=11;fillColor=#FAFAFF;fontStyle=2;fontColor=#6B6B80;'
            cell(new(), '', base, 0, 0, W_KEY, RH, parent=r)
            cell(new(), extra, base + 'align=left;spacingLeft=8;', W_KEY, 0, W_NAME + W_TYPE, RH, parent=r)
            ry += RH
        y += h + GAP

edges = []
for t, d in S.items():
    pk = d['pk'].split(',')
    uq_single = {u['cols'] for u in d['uq'] if ',' not in u['cols']}
    nullable = {c['c'] for c in d['cols'] if c['n'] == 'YES'}
    for f in d['fks']:
        one_to_one = f['c'] in uq_single or (pk == [f['c']])
        start = 'ERzeroToOne' if one_to_one else ('ERzeroToMany')
        end = 'ERzeroToOne' if f['c'] in nullable else 'ERmandOne'
        src, dst = rowid[(t, f['c'])], rowid[(f['rt'], f['rc'])]
        style = f'edgeStyle=entityRelationEdgeStyle;fontSize=12;html=1;endArrow={end};startArrow={start};endFill=0;startFill=0;strokeColor=#8E94A6;strokeWidth=1.5;rounded=1;'
        if t == f['rt']:  # self reference (flavor_categories.parent_id)
            style = f'edgeStyle=orthogonalEdgeStyle;fontSize=12;html=1;endArrow={end};startArrow={start};endFill=0;startFill=0;strokeColor=#8E94A6;strokeWidth=1.5;exitX=1;exitY=0.5;entryX=1;entryY=0.5;'
        edges.append(f'<mxCell id="{new()}" style="{style}" edge="1" parent="1" source="{src}" target="{dst}"><mxGeometry relative="1" as="geometry"/></mxCell>')

title = ('<mxCell id="title" value="&lt;b&gt;Roast &amp;amp; Notes — Relational Mapping&lt;/b&gt;&lt;br&gt;&lt;span style=&quot;font-size:13px;color:#6B6B80&quot;&gt;22 ตาราง · PK = primary key · FK = foreign key · UK = unique · ชื่อ ? = NULL ได้ · ตัวเอียง = ข้อจำกัดหลายคอลัมน์&lt;/span&gt;" '
         'style="text;html=1;align=left;verticalAlign=top;fontSize=24;fontColor=#1B1F3B;" vertex="1" parent="1"><mxGeometry x="40" y="20" width="1400" height="70" as="geometry"/></mxCell>')
legend_y = 20
xml = ('<mxfile host="app.diagrams.net" agent="Roast &amp; Notes generator"><diagram id="relational" name="Relational Mapping"><mxGraphModel dx="1600" dy="1000" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="0" pageScale="1" pageWidth="2400" pageHeight="1800" math="0" shadow="0"><root><mxCell id="0"/><mxCell id="1" parent="0"/>'
       + title + ''.join(cells) + ''.join(edges) + '</root></mxGraphModel></diagram></mxfile>')
open('roast-notes-relational-mapping.drawio', 'w').write(xml)
print(len(cells), len(edges))
