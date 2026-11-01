# Promo clip (30 s, 1080p, with sound)

Everything is generated from code: `promo.html` (animation timeline driven by `seek(t)`),
`render.mjs` (Playwright captures 900 frames at 1920×1080 / 30 fps), `music.py` (music + SFX synthesized with numpy).

```bash
node render.mjs /tmp/frames 30 30          # frames
python3 music.py /tmp/promo.wav             # soundtrack
ffmpeg -framerate 30 -i /tmp/frames/f%05d.jpg -i /tmp/promo.wav \
  -c:v libx264 -preset slow -crf 16 -pix_fmt yuv420p -c:a aac -b:a 192k -shortest -movflags +faststart roast-notes-promo-1080p.mp4
```

Scenes: hook (0–3) · one-sentence Taste Quiz (3–7) · picked for you (7–11) · products / detail (11–15) ·
cart + discounts (15–19) · loyalty tiers (19–23) · admin report (23–26) · end card (26–30). Cuts land on the 120 bpm beat.
