# Walkthrough video (≈1:54, 1080p, with sound)

Every feature of Roast & Notes, recorded from the real site and edited into an Apple-event style video — all from code.

```bash
php artisan migrate:fresh --seed && php artisan serve --port=8080   # fresh demo data
node record.cjs /tmp/rec                                         # 7 chapters, scripted with a visible cursor
python3 timeline.py /tmp/rec > /tmp/timeline.json                # edit list: chapter cards, speeds, captions
node render.mjs /tmp/timeline.json /tmp/frames 30                # compose.html → 1920×1080 frames
python3 music.py /tmp/timeline.json /tmp/demo.wav                # synthesized soundtrack synced to the cuts
ffmpeg -framerate 30 -i /tmp/frames/f%05d.jpg -i /tmp/demo.wav -c:v libx264 -crf 17 -pix_fmt yuv420p \
  -c:a aac -b:a 192k -shortest -movflags +faststart roast-notes-walkthrough-1080p.mp4
```

Chapters: 01 browse & filter · 02 register + Taste Quiz · 03 early access, wish list, add to cart ·
04 coupon / buy 10 get 2 / tier discount + checkout · 05 orders, review, wish list, member tier ·
06 admin: advance & cancel orders, stock, new coupon, user tier, review moderation · 07 sales report, SQL, CSV.
Speeds per chapter live in `timeline.py`; total must stay ≤ 120 s.
