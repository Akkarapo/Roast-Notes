import json, re
S = json.load(open('schema.json'))
GROUPS = [
    ('members', '#615CF6', ['member_tiers', 'users', 'user_taste_preferences']),
    ('flavors', '#2F9E8F', ['flavor_categories', 'flavor_notes', 'product_flavor_notes']),
    ('catalog', '#2338C4', ['origins', 'products', 'product_variants', 'cupping_scores']),
    ('ordering', '#D9822B', ['carts', 'cart_items', 'orders', 'order_items', 'order_status_changes']),
    ('promotions', '#B42CE6', ['promotions', 'coupons', 'buy_x_get_y', 'promotion_products', 'order_discounts']),
    ('community', '#C2417A', ['wishlists', 'reviews']),
]
NOTES = {
    'products': "superclass: type = 'bean' → beans, 'drip_bag' → drip_bags (disjoint, total; single-table mapping)",
    'product_variants': 'one row per bag size; price and stock live here (weak entity of products, identified by product_id + size)',
    'promotions': "supertype: type = 'coupon' → coupons, 'buy_x_get_y' → buy_x_get_y (disjoint, total)",
    'coupons': 'subtype of promotions (PK = FK)',
    'buy_x_get_y': 'subtype of promotions (PK = FK)',
    'cupping_scores': '1:1 with products (PK = FK)',
    'reviews': 'one review per purchased line; reviewer and product reached through order_items (3NF)',
    'order_items': 'price snapshot at order time',
    'order_status_changes': 'status history (orders.status keeps the current one)',
    'flavor_categories': 'self-reference: parent_id NULL = top-level group (Fruity, Floral, ...)',
}
color = {t: c for _, c, ts in GROUPS for t in ts}
enums, lines = [], []
def ty(t, col):
    raw = col['ty'].replace(' unsigned', '')
    if raw.startswith('enum('):
        name = f'{t}_{col["c"]}'
        vals = re.findall(r"'([^']*)'", raw)
        enums.append(f'Enum {name} {{\n' + ''.join(f'  "{v}"\n' for v in vals) + '}')
        return name
    if raw == 'tinyint(1)': return 'boolean'
    return re.sub(r'^(bigint|int|smallint|tinyint|mediumint)\(\d+\)', r'\1', raw)

refs = []
for _, _, tables in GROUPS:
    for t in tables:
        d = S[t]
        pk = d['pk'].split(',')
        uq_single = {u['cols'] for u in d['uq'] if ',' not in u['cols']}
        out = [f'Table {t} [headercolor: {color[t]}] {{']
        for c in d['cols']:
            st = []
            if len(pk) == 1 and c['c'] in pk: st.append('pk')
            if len(pk) == 1 and c['c'] in pk and 'auto_increment' in json.dumps(c): st.append('increment')
            if c['c'] in uq_single: st.append('unique')
            st.append('null' if c['n'] == 'YES' else 'not null')
            out.append(f'  {c["c"]} {ty(t, c)} [{", ".join(st)}]')
        idx = []
        if len(pk) > 1: idx.append(f'    ({", ".join(pk)}) [pk]')
        for u in d['uq']:
            if ',' in u['cols']: idx.append(f'    ({", ".join(u["cols"].split(","))}) [unique]')
        if idx: out += ['', '  indexes {'] + idx + ['  }']
        if t in NOTES: out += ['', "  Note: '" + NOTES[t].replace("'", "\\'") + "'"]
        out.append('}')
        lines.append('\n'.join(out))
        for f in d['fks']:
            one = f['c'] in uq_single or pk == [f['c']]
            refs.append(f'Ref: {t}.{f["c"]} {"-" if one else ">"} {f["rt"]}.{f["rc"]}')

groups = [f'TableGroup {g} [color: {c}] {{\n' + ''.join(f'  {t}\n' for t in ts) + '}' for g, c, ts in GROUPS]
dbml = ('// Roast & Notes — Database course 261343\n// 22 tables, generated from the live MySQL schema. Paste into https://dbdiagram.io\n\n'
        + '\n\n'.join(lines) + '\n\n// ---------- relationships ( > many-to-one, - one-to-one ) ----------\n' + '\n'.join(refs)
        + '\n\n// ---------- enums ----------\n' + '\n\n'.join(enums) + '\n\n// ---------- groups ----------\n' + '\n\n'.join(groups) + '\n')
open('roast-notes.dbml', 'w').write(dbml)
print(len(dbml.splitlines()), 'lines', len(refs), 'refs', len(enums), 'enums')
