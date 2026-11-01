"""Soundtrack for the walkthrough video, synced to timeline.json (music + SFX, all synthesized).
usage: python3 music.py timeline.json out.wav"""
import sys, json
import numpy as np
from scipy.signal import butter, sosfilt
from scipy.io import wavfile

TL = json.load(open(sys.argv[1]))
SR, DUR = 44100, TL['total']
N = int(SR * DUR); L = np.zeros(N); R = np.zeros(N)
rng = np.random.default_rng(11)
def t_(d): return np.arange(int(d * SR)) / SR
def add(sig, at, gain=1.0, pan=0.0):
    i = int(at * SR)
    if i >= N or i < 0: return
    sig = sig[: N - i] * gain
    L[i:i + len(sig)] += sig * np.sqrt((1 - pan) / 2) * 1.414; R[i:i + len(sig)] += sig * np.sqrt((1 + pan) / 2) * 1.414
def env(d, a=.005, rel=None):
    t = t_(d); e = np.minimum(1, t / a)
    if rel: e *= np.clip((d - t) / rel, 0, 1)
    return e
def filt(x, kind, f, order=2): return sosfilt(butter(order, f, btype=kind, fs=SR, output='sos'), x)
def hz(m): return 440 * 2 ** ((m - 69) / 12)
def kick():
    t = t_(.35); f = 45 + 110 * np.exp(-t / .04)
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t / .13)
def hat(o=False):
    t = t_(.22 if o else .06); return filt(rng.standard_normal(len(t)), 'high', 7000) * np.exp(-t / (.07 if o else .015)) * .3
def clap():
    t = t_(.25); return filt(rng.standard_normal(len(t)), 'band', [900, 3500]) * (np.exp(-t / .06)) * .45
def pluck(m, d=.22):
    t = t_(d); x = sum(np.sin(2 * np.pi * hz(m) * k * t) / k ** 1.6 for k in range(1, 6))
    return x * np.exp(-t / .09) * env(d, .002) * .24
def pad(ms, d):
    t = t_(d); x = sum(2 * ((hz(m) * (1 + dt) * t) % 1) - 1 for m in ms for dt in (-.004, .004))
    return filt(x, 'low', 1300) * env(d, .3, rel=.4) * .045
def bass(m, d=.22):
    t = t_(d); x = np.sin(2 * np.pi * hz(m) * t) + .3 * np.sign(np.sin(2 * np.pi * hz(m) * t))
    return filt(x, 'low', 550) * env(d, .004, rel=.05) * .28
def bell(m, d=1.4):
    t = t_(d); f = hz(m)
    return (np.sin(2 * np.pi * f * t) + .5 * np.sin(2 * np.pi * f * 2.76 * t) * np.exp(-t / .3)) * np.exp(-t / .6) * .17
def whoosh(d=.5):
    t = t_(d); n = rng.standard_normal(len(t)); out = np.zeros_like(n)
    for k in range(8):
        a, b = int(k * len(t) / 8), int((k + 1) * len(t) / 8); fc = 400 + 4200 * k / 7
        out[a:b] = filt(n, 'band', [fc * .7, fc * 1.4])[a:b]
    return out * np.sin(np.pi * t / d) ** 2 * .3
def pop(m=84):
    t = t_(.12); f = hz(m) * (1 + .6 * np.exp(-t / .01))
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t / .03) * .22

CH = [[60, 64, 67], [55, 59, 62], [57, 60, 64], [53, 57, 60]]; ROOT = [36, 31, 33, 29]
INTRO, OUT = TL['intro'], TL['outro_start']
# intro: pad + sparse arp
add(pad([60, 64, 67, 72], INTRO + .3), 0, 1.0)
for k in range(int(INTRO / .25)): add(pluck([72, 76, 79, 84][k % 4]), k * .25, .7, pan=(-.3 if k % 2 else .3))
# groove from the end of the intro to the outro (beat grid anchored on the intro)
bt, k = INTRO, 0
while bt < OUT:
    bar = k // 8; ch = CH[bar % 4]; beat = k % 2 == 0
    if k % 8 == 0: add(pad([m + 12 for m in ch], 4.05), bt, .8)
    if beat: add(kick(), bt, .75)
    if k % 4 == 2: add(clap(), bt, .45)
    add(hat(k % 8 == 7), bt + .25 if beat else bt, .6, pan=.25)
    add(bass(ROOT[bar % 4] + (12 if not beat else 0)), bt, .9)
    for s in range(2): add(pluck(ch[(k * 2 + s) % 3] + 12), bt + s * .25, .55, pan=(-.3 if s else .3))
    bt += .25 * 2; k += 1
# chapter cards: whoosh + bell; captions: soft pop
for i, s in enumerate(TL['shots']):
    add(whoosh(.5), s['card'] - .3, .9); add(bell(79 + (i % 3) * 2, 1.2), s['card'] + .05, .8)
    for ct, _ in s['caps']: add(pop(86), ct, .7)
# outro
add(whoosh(.6), OUT - .35, 1.0); add(kick(), OUT, 1.0)
add(pad([48, 60, 64, 67, 72], DUR - OUT + .2) * 1.8, OUT)
for i, m in enumerate([72, 76, 79, 84, 88]): add(bell(m, 2.0), OUT + .2 + i * .12, .9, pan=-.4 + i * .2)

mix = np.stack([filt(L, 'high', 30), filt(R, 'high', 30)], 1)
fi = int((DUR - 1.2) * SR); f = np.ones(N); f[fi:] = np.linspace(1, 0, N - fi); mix *= f[:, None]
fo = int(.3 * SR); mix[:fo] *= np.linspace(0, 1, fo)[:, None]
mix = np.tanh(mix * 1.2) / np.tanh(1.2); mix *= .89 / np.abs(mix).max()
wavfile.write(sys.argv[2], SR, (mix * 32767).astype(np.int16)); print('ok', round(DUR, 2), 's')
