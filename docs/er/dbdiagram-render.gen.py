import re, json, html, sys
src=open(sys.argv[1]).read()
tables={}; order=[]
for m in re.finditer(r'Table (\w+) \[headercolor: (#\w+)\] \{(.*?)\n\}', src, re.S):
    name,color,body=m.groups(); cols=[]; pkcomp=[]
    idx=re.search(r'indexes \{(.*?)\}',body,re.S)
    if idx:
        for im in re.finditer(r'\(([^)]*)\) \[pk\]',idx.group(1)): pkcomp=[c.strip() for c in im.group(1).split(',')]
    for line in body.split('\n'):
        cm=re.match(r'\s{2}(\w+) ([\w(),]+) \[([^\]]*)\]',line)
        if cm and not line.strip().startswith(('Note','indexes','(')):
            c,t,f=cm.groups(); cols.append(dict(n=c,t=t,pk='pk' in f or c in pkcomp,uq='unique' in f,null='not null' not in f))
    tables[name]=dict(color=color,cols=cols); order.append(name)
refs=[]
for m in re.finditer(r'Ref: (\w+)\.(\w+) ([>-]) (\w+)\.(\w+)',src): refs.append(m.groups())
fk={(a,b) for a,b,_,_,_ in refs}
groups=[]
for m in re.finditer(r'TableGroup (\w+) \[color: (#\w+)\] \{(.*?)\}',src,re.S):
    groups.append((m.group(1),m.group(2),m.group(3).split()))
COLS=[['member_tiers','users','user_taste_preferences'],['flavor_categories','flavor_notes','product_flavor_notes','wishlists','reviews'],['origins','products'],['product_variants','cupping_scores'],['carts','cart_items','orders'],['order_items','order_status_changes'],['promotions','coupons','buy_x_get_y','promotion_products','order_discounts']]
TW,RH,HH,GAP,VG,X0,Y0=270,24,34,110,46,60,70
pos={}
for ci,col in enumerate(COLS):
    y=Y0; x=X0+ci*(TW+GAP)
    prevg=None
    for t in col:
        g=[gn for gn,_,ts in groups if t in ts][0]
        if prevg and g!=prevg: y+=40
        prevg=g
        h=HH+RH*len(tables[t]['cols'])
        pos[t]=(x,y,h); y+=h+VG
W=X0*2+len(COLS)*TW+(len(COLS)-1)*GAP; H=max(p[1]+p[2] for p in pos.values())+60
def rowy(t,c):
    i=[k['n'] for k in tables[t]['cols']].index(c); x,y,h=pos[t]; return y+HH+RH*i+RH/2
colof={t:ci for ci,col in enumerate(COLS) for t in col}
lanes={}
def lane(g):
    lanes[g]=lanes.get(g,0)+1; return lanes[g]
paths=[]
def tick(x,y,d):  # one: two bars
    return f'M{x+d*8},{y-7}V{y+7}M{x+d*13},{y-7}V{y+7}'
def crow(x,y,d):
    return f'M{x},{y-7}L{x+d*12},{y}L{x},{y+7}M{x+d*12},{y-7}V{y+7}'
for a,ac,kind,b,bc in refs:
    ca,cb=colof[a],colof[b]; ya,yb=rowy(a,ac),rowy(b,bc)
    xa,_,_=pos[a]; xb,_,_=pos[b]
    if cb>ca: sa=xa+TW; sb=xb; da,db=1,-1; g=ca
    elif cb<ca: sa=xa; sb=xb+TW; da,db=-1,1; g=ca-1
    else: sa=xa+TW; sb=xb+TW; da,db=1,1; g=ca
    L=lane(g); lx=X0+g*(TW+GAP)+TW+14+L*9
    if a==b: lx=sa+24
    d=f'M{sa},{ya}H{lx}V{yb}H{sb}'
    ma=crow(sa,ya,da) if kind=='>' else tick(sa,ya,da)
    mb=tick(sb,yb,db)
    paths.append((d,ma,mb))
out=[f'<!doctype html><html><head><meta charset=utf-8><link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel=stylesheet><style>body{{margin:0;background:#FBF8F1;font-family:Inter,Arial,sans-serif}} .t{{position:absolute;width:{TW}px;background:#fff;border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,.12),0 0 0 1px rgba(24,36,138,.12);overflow:hidden}} .h{{height:{HH}px;color:#fff;font-weight:700;font-size:15px;display:flex;align-items:center;padding:0 12px}} .r{{height:{RH}px;display:flex;align-items:center;padding:0 10px;font-size:12.5px;border-top:1px solid #EEF0F6;color:#1F2547}} .r .n{{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}} .r .ty{{color:#8A90AE;font-size:11.5px;margin-left:6px}} .b{{font-size:9px;font-weight:700;border-radius:3px;padding:1px 4px;margin-left:5px}} .pk{{background:#FDE9B8;color:#8A5A00}} .fk{{background:#E3E7FF;color:#2338C4}} .uq{{background:#E7F5EF;color:#1F7A5A}} .g{{position:absolute;border-radius:14px;border:2px dashed}} .gl{{position:absolute;font-size:13px;font-weight:700;letter-spacing:.5px;text-transform:uppercase}}</style></head><body><div style="position:relative;width:{W}px;height:{H}px">']
for gn,gc,ts in groups:
    xs=[pos[t][0] for t in ts]; ys=[pos[t][1] for t in ts]; ye=[pos[t][1]+pos[t][2] for t in ts]
    x1,y1,x2,y2=min(xs)-14,min(ys)-30,max(xs)+TW+14,max(ye)+14
    out.append(f'<div class=g style="left:{x1}px;top:{y1}px;width:{x2-x1}px;height:{y2-y1}px;border-color:{gc}55;background:{gc}0d"></div><div class=gl style="left:{x1+12}px;top:{y1+6}px;color:{gc}">{gn}</div>')
svg=[f'<svg width={W} height={H} style="position:absolute;left:0;top:0" fill=none stroke-linejoin=round>']
for d,ma,mb in paths: svg.append(f'<path d="{d}" stroke="#9AA1BF" stroke-width="1.6"/><path d="{ma}" stroke="#5A6390" stroke-width="1.6"/><path d="{mb}" stroke="#5A6390" stroke-width="1.6"/>')
svg.append('</svg>'); out+=svg
for t,(x,y,h) in pos.items():
    T=tables[t]; out.append(f'<div class=t style="left:{x}px;top:{y}px"><div class=h style="background:{T["color"]}">{t}</div>')
    for c in T['cols']:
        badges=('<span class="b pk">PK</span>' if c['pk'] else '')+('<span class="b fk">FK</span>' if (t,c['n']) in fk else '')+('<span class="b uq">UQ</span>' if c['uq'] and not c['pk'] else '')
        nm=f'<u>{c["n"]}</u>' if c['pk'] else c['n']
        ty=re.sub(r'^(users|products|product_variants|cart_items|orders|order_items|order_status_changes|promotions|reviews)_.*','enum',c['t'])
        out.append(f'<div class=r><span class=n style="font-weight:{600 if c["pk"] else 400}">{nm}</span>{badges}<span class=ty>{ty}{"?" if c["null"] else ""}</span></div>')
    out.append('</div>')
out.append('</div></body></html>')
open(sys.argv[2],'w').write(''.join(out)); print(W,H)
