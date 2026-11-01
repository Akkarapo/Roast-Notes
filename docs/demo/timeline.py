"""Build the edit decision list for the walkthrough video from the recorded chapters.
usage: python3 timeline.py <recDir> > timeline.json"""
import json, os, sys

REC = sys.argv[1]
INTRO, OUTRO, CARD = 3.5, 4.5, 1.4
CHAPTERS = [  # folder, number, title, subtitle, playback speed
    ('01-browse',   '01', 'ค้นพบเมล็ดที่ใช่',        'หน้าแรก · กรองตามรส · รายละเอียดเมล็ด', 1.15),
    ('02-quiz',     '02', 'รู้ใจตั้งแต่สมัคร',        'สมัครสมาชิก · Taste Quiz · match %',      1.6),
    ('03-shop',     '03', 'ช้อปแบบสมาชิก',          'Early access · Wish List · ใส่ตะกร้า',     1.1),
    ('04-checkout', '04', 'ส่วนลดคิดให้เอง',         'คูปอง · ซื้อ 10 แถม 2 · ส่วนลดสมาชิก',      1.1),
    ('05-after',    '05', 'หลังการขาย',             'ประวัติ · รีวิว · Wish List · ระดับสมาชิก',  1.2),
    ('06-admin',    '06', 'หลังร้านในที่เดียว',       'ออเดอร์ · สต็อก · คูปอง · ผู้ใช้ · รีวิว',     1.55),
    ('07-report',   '07', 'ตัวเลขจาก SQL จริง',      'รายงานยอดขาย · ดู SQL · CSV',             1.05),
]
marks = json.load(open(os.path.join(REC, 'marks.json')))
t, shots = INTRO, []
for folder, num, title, sub, speed in CHAPTERS:
    ts = json.load(open(os.path.join(REC, folder, 'ts.json')))
    dur = (ts[-1] - ts[0]) / speed
    caps = [[t + CARD + m / speed, label] for m, label in marks.get(folder, [])]
    shots.append(dict(folder=os.path.abspath(os.path.join(REC, folder)), num=num, title=title, sub=sub, speed=speed,
                      card=t, start=t + CARD, end=t + CARD + dur, ts=[round(x - ts[0], 4) for x in ts], caps=caps))
    t += CARD + dur
print(json.dumps(dict(intro=INTRO, outro_start=t, total=t + OUTRO, card=CARD, shots=shots), ensure_ascii=False))
