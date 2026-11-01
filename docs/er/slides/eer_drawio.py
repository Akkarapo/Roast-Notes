"""Export the 5 Chen-notation EER diagrams (same specs as the slides) to one draw.io file, one page each.
usage: python3 eer_drawio.py ../roast-notes-eer.drawio"""
import sys, html
from eer_slides import slides, INK, COB, PAPER, LINE, CARD

PAGES = {'eer-users': '1 · users (members / admins)', 'eer-products': '2 · products (beans / drip bags)',
         'eer-orders': '3 · carts · orders', 'eer-promotions': '4 · promotions (coupons / buy X get Y)',
         'eer-community': '5 · wish list · reviews · flavors'}
FONT = 'fontFamily=Helvetica;'

def esc(s): return html.escape(s, quote=True)

def page(sl, pid):
    cells, uid = [], [0]
    def nid():
        uid[0] += 1; return f'{pid}-{uid[0]}'
    def v(value, style, x, y, w, h, extra=''):
        i = nid()
        cells.append(f'<mxCell id="{i}" value="{esc(value)}" style="{style}" vertex="1" parent="1"{extra}><mxGeometry x="{x:.0f}" y="{y:.0f}" width="{w:.0f}" height="{h:.0f}" as="geometry"/></mxCell>')
        return i
    T = 'text;html=1;strokeColor=none;fillColor=none;' + FONT

    v(sl.eyebrow, T + f'align=left;verticalAlign=top;fontSize=28;fontStyle=1;fontColor={COB};', 128, 96, 1200, 40)
    v(sl.title, T + f'align=left;verticalAlign=top;fontSize=56;fontStyle=1;fontColor={INK};', 128, 136, 1664, 72)

    ids = {}
    for k, N in sl.n.items():
        x, y, w, h = N['x'] - N['w'] / 2, N['y'] - N['h'] / 2, N['w'], N['h']
        lab, t = N['label'], N['t']
        if t == 'ent':
            st = f'rounded=1;arcSize=14;whiteSpace=wrap;html=1;fillColor={COB};strokeColor={COB};fontColor={PAPER};fontSize=28;fontStyle=1;' + FONT
        elif t == 'weak':
            st = f'shape=ext;double=1;rounded=1;arcSize=14;whiteSpace=wrap;html=1;fillColor={PAPER};strokeColor={COB};strokeWidth=2;fontColor={INK};fontSize=28;fontStyle=1;' + FONT
        elif t == 'att':
            st = f'ellipse;whiteSpace=wrap;html=1;fillColor=#E9ECFB;fontColor={INK};fontSize=24;' + FONT
            st += f'dashed=1;strokeColor={COB};strokeWidth=2;' if N['derived'] else 'strokeColor=#B8C1F2;strokeWidth=2;'
            if N['key']: lab = f'<u>{esc(lab)}</u>'
            elif N['partial']: lab = f'<span style="text-decoration: underline dashed;">{esc(lab)}</span>'
            else: lab = esc(lab)
            ids[k] = nid()
            cells.append(f'<mxCell id="{ids[k]}" value="{esc(lab)}" style="{st}" vertex="1" parent="1"><mxGeometry x="{x:.0f}" y="{y:.0f}" width="{w:.0f}" height="{h:.0f}" as="geometry"/></mxCell>')
            continue
        elif t == 'rel':
            st = f'shape=rhombus;perimeter=rhombusPerimeter;whiteSpace=wrap;html=1;fillColor=#FFF1D6;strokeColor={CARD};strokeWidth=2;fontColor=#6B3F08;fontSize=24;fontStyle=1;' + FONT
            if N['ident']: st += 'double=1;'
        else:  # specialization circle
            st = f'ellipse;whiteSpace=wrap;html=1;aspect=fixed;fillColor={PAPER};strokeColor={COB};strokeWidth=3;fontColor={COB};fontSize=30;fontStyle=1;' + FONT
        ids[k] = v(lab, st, x, y, w, h)

    E = f'endArrow=none;html=1;strokeColor={LINE};strokeWidth=2;'
    for e in sl.edges:
        a, b, double = e
        st = E + ('shape=link;width=6;' if double else '')
        i = nid()
        if isinstance(a, str):
            cells.append(f'<mxCell id="{i}" style="{st}" edge="1" parent="1" source="{ids[a]}" target="{ids[b]}"><mxGeometry relative="1" as="geometry"/></mxCell>')
        else:
            cells.append(f'<mxCell id="{i}" style="{st}" edge="1" parent="1"><mxGeometry relative="1" as="geometry"><mxPoint x="{a[0]:.0f}" y="{a[1]:.0f}" as="sourcePoint"/><mxPoint x="{b[0]:.0f}" y="{b[1]:.0f}" as="targetPoint"/></mxGeometry></mxCell>')

    for it in sl.items:
        if it[0] == 'card':
            v(it[3], T + f'align=center;verticalAlign=middle;fontSize=28;fontStyle=1;fontColor={CARD};', it[1] - 30, it[2] - 20, 60, 40)
        elif it[0] == 'subset':
            v('∪', T + f'align=center;verticalAlign=middle;fontSize=36;fontStyle=1;fontColor={COB};rotation={it[3]:.0f};', it[1] - 22, it[2] - 22, 44, 44)
        elif it[0] == 'note':
            v(it[3], T + 'align=center;verticalAlign=top;fontSize=24;fontStyle=2;fontColor=#6B7096;', it[1] - it[4] / 2, it[2], it[4], 40)
        elif it[0] == 'label':
            v(it[3], T + f'align=center;verticalAlign=middle;fontSize=24;fontStyle=1;fontColor={it[5]};', it[1] - it[4] / 2, it[2] - 18, it[4], 36)

    v(sl.footer, T + 'align=left;verticalAlign=middle;fontSize=26;fontColor=#4A5280;', 128, 960, 1664, 44)
    body = ''.join(cells)
    return (f'<diagram id="{pid}" name="{esc(PAGES[sl.sid])}"><mxGraphModel dx="1920" dy="1080" grid="1" gridSize="10" guides="1" '
            f'tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1920" pageHeight="1080" background="{PAPER}" math="0" shadow="0">'
            f'<root><mxCell id="0"/><mxCell id="1" parent="0"/>{body}</root></mxGraphModel></diagram>')

out = sys.argv[1] if len(sys.argv) > 1 else 'roast-notes-eer.drawio'
xml = '<mxfile host="app.diagrams.net" agent="Roast &amp; Notes eer_drawio.py">' + ''.join(page(s, f'p{i+1}') for i, s in enumerate(slides)) + '</mxfile>\n'
open(out, 'w').write(xml)
print('wrote', out, len(xml), 'bytes,', len(slides), 'pages')
