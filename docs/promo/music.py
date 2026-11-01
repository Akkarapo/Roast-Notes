"""Synthesize the 30 s soundtrack for the Roast & Notes promo (music + SFX synced to promo.html).
usage: python3 music.py out.wav
Everything is generated from code: no samples. 120 bpm, I–V–vi–IV in C, drop at 3.0 s, outro at 26 s."""
import sys
import numpy as np
from scipy.signal import butter, sosfilt
from scipy.io import wavfile

SR, DUR = 44100, 30.0
N = int(SR * DUR)
rng = np.random.default_rng(7)
L = np.zeros(N); R = np.zeros(N)
BEAT = 0.5

def t_(d): return np.arange(int(d * SR)) / SR
def add(sig, at, gain=1.0, pan=0.0):
    i = int(at * SR)
    if i >= N: return
    sig = sig[: N - i] * gain
    L[i:i + len(sig)] += sig * np.sqrt((1 - pan) / 2) * 1.414
    R[i:i + len(sig)] += sig * np.sqrt((1 + pan) / 2) * 1.414
def env(d, a=0.005, rel=None, decay=None):
    t = t_(d); e = np.minimum(1, t / max(a, 1e-4))
    if decay: e *= np.exp(-t / decay)
    if rel: e *= np.clip((d - t) / rel, 0, 1)
    return e
def filt(x, kind, f, order=2):
    return sosfilt(butter(order, f, btype=kind, fs=SR, output='sos'), x)
def hz(m): return 440 * 2 ** ((m - 69) / 12)

# ---- instruments --------------------------------------------------------
def kick():
    t = t_(0.35); f = 45 + 110 * np.exp(-t / 0.04)
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t / 0.13) + filt(rng.standard_normal(len(t)), 'high', 3000) * np.exp(-t / 0.004) * 0.15
def clap():
    t = t_(0.25); n = filt(rng.standard_normal(len(t)), 'band', [900, 3500])
    e = sum(np.exp(-np.maximum(t - k * 0.011, 0) / 0.01) * (t >= k * 0.011) for k in range(3)) + np.exp(-t / 0.07) * 0.6
    return n * e * 0.6
def hat(open_=False):
    t = t_(0.25 if open_ else 0.06); return filt(rng.standard_normal(len(t)), 'high', 7000) * np.exp(-t / (0.08 if open_ else 0.015)) * 0.35
def saw(f, d):
    t = t_(d); ph = (f * t) % 1; return 2 * ph - 1
def pluck(m, d=0.22):
    t = t_(d); x = sum(np.sin(2 * np.pi * hz(m) * k * t) / k ** 1.6 for k in range(1, 6))
    return x * np.exp(-t / 0.09) * env(d, 0.002) * 0.28
def pad(ms, d):
    x = sum(saw(hz(m) * (1 + det), d) for m in ms for det in (-0.004, 0.004))
    return filt(x, 'low', 1400) * env(d, 0.25, rel=0.3) * 0.05
def bass(m, d=0.22):
    t = t_(d); x = np.sin(2 * np.pi * hz(m) * t) + 0.3 * np.sign(np.sin(2 * np.pi * hz(m) * t))
    return filt(x, 'low', 600) * env(d, 0.004, rel=0.05) * 0.32
def bell(m, d=1.6):
    t = t_(d); f = hz(m)
    return (np.sin(2 * np.pi * f * t) + 0.5 * np.sin(2 * np.pi * f * 2.76 * t) * np.exp(-t / 0.3)) * np.exp(-t / 0.6) * 0.18
def whoosh(d=0.5, up=True):
    t = t_(d); n = rng.standard_normal(len(t)); out = np.zeros_like(n)
    for k in range(8):  # stepped band sweep
        a, b = int(k * len(t) / 8), int((k + 1) * len(t) / 8)
        fc = (400 + 4200 * (k / 7)) if up else (4600 - 4200 * (k / 7))
        out[a:b] = filt(n, 'band', [fc * 0.7, fc * 1.4])[a:b]
    return out * np.sin(np.pi * t / d) ** 2 * 0.35
def pop(m=84):
    t = t_(0.12); f = hz(m) * (1 + 0.6 * np.exp(-t / 0.01))
    return np.sin(2 * np.pi * np.cumsum(f) / SR) * np.exp(-t / 0.03) * 0.3
def click():
    t = t_(0.03); return filt(rng.standard_normal(len(t)), 'band', [1800, 6000]) * np.exp(-t / 0.004) * 0.25

# ---- arrangement --------------------------------------------------------
CH = [[60, 64, 67], [55, 59, 62], [57, 60, 64], [53, 57, 60]]  # C G Am F
ROOT = [36, 31, 33, 29]
for bar in range(13):                               # 0–26 s
    s0 = bar * 2.0; ch = CH[bar % 4]
    add(pad([m + 12 for m in ch] if bar else ch, 2.05), s0, 1.0 if bar >= 1 else 0.8)
    for k in range(16):                             # 16th arp
        if bar == 0 and k % 2: continue
        add(pluck(ch[k % 3] + 12 + (12 if k % 8 >= 6 else 0)), s0 + k * 0.125, 0.8, pan=(-0.3 if k % 2 else 0.3))
    if s0 >= 3.0 or (s0 + 2 > 3.0):
        for k in range(4):                          # beats
            bt = s0 + k * BEAT
            if bt < 3.0 or bt >= 26.0: continue
            add(kick(), bt, 0.9)
            if k in (1, 3): add(clap(), bt, 0.7)
            add(hat(k == 3), bt + 0.25, 0.8, pan=0.25)
            for e in (0, 0.25):
                add(bass(ROOT[bar % 4] + (12 if e else 0)), bt + e, 1.0)
# drop riser + impact
rt = t_(1.0); add(filt(rng.standard_normal(len(rt)), 'high', 1500) * (rt / 1.0) ** 2 * 0.25, 2.0)
add(kick(), 3.0, 1.2); add(bell(84, 1.2), 3.0, 0.6)
# outro: big C chord + bell arpeggio
add(pad([48, 60, 64, 67, 72], 4.0) * 1.8, 26.0)
for i, m in enumerate([72, 76, 79, 84, 88]): add(bell(m, 2.2), 26.15 + i * 0.12, 0.9, pan=-0.4 + i * 0.2)
add(kick(), 26.0, 1.1)

# ---- SFX synced to promo.html ------------------------------------------
for c in (3, 7, 11, 13, 15, 19, 23, 26): add(whoosh(0.45), c - 0.3, 0.9)
for at, m in [(0.2, 79), (0.6, 84), (1.05, 86), (1.5, 88), (3.25, 81), (4.25, 79), (9.45, 88), (9.85, 91),
              (15.85, 84), (16.3, 86), (16.75, 88), (19.35, 79), (19.75, 84), (20.15, 88), (26.2, 91)]:
    add(pop(m), at, 0.9)
NG = 41
for i in range(NG): add(click(), 3.7 + i * 2.0 / NG + rng.uniform(-0.01, 0.01), 0.7, pan=rng.uniform(-.2, .2))
add(click() * 2, 6.25, 1.0); add(pop(72), 6.3, 0.8)
for i, m in enumerate([76, 79, 84]): add(bell(m, 1.0), 17.9 + i * 0.09, 0.8)          # order success
for at, m in [(20.4, 79), (21.3, 84), (22.2, 91)]: add(bell(m, 0.9), at, 0.7)           # tier reached

# ---- master -------------------------------------------------------------
for X in (L, R):
    X[:] = filt(X, 'high', 30)
mix = np.stack([L, R], 1)
fade = np.ones(N); f0 = int(29.0 * SR); fade[f0:] = np.linspace(1, 0, N - f0)
mix *= fade[:, None]
mix = np.tanh(mix * 1.2) / np.tanh(1.2)            # soft clip / glue
mix *= 0.89 / np.abs(mix).max()
wavfile.write(sys.argv[1] if len(sys.argv) > 1 else 'promo.wav', SR, (mix * 32767).astype(np.int16))
print('ok', DUR, 's')
