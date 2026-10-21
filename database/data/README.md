# Catalog demo data

`cqi-selected.csv` — 40 Arabica lots picked from the Coffee Quality Institute (CQI) reviews
by `select_cqi.py` (see that file for the selection rules and how to re-run it).

| Column | From CQI? |
|---|---|
| country, region, farm_name, variety, process, altitude_m, graded_on | yes (cleaned: title case, typos, placeholder farm names removed) |
| aroma … cupper_points, total_points | yes, unchanged |
| roast_tendency | derived: Acidity / Body ratio (> 1.05 light, < 0.95 dark) |
| drip_bag | derived: 8 highest total_points |

Flavor notes, roast level, prices and roast dates are **not** in CQI. They are generated
by `app/Support/Catalog/DemoDataRules.php` and are demo data only.

Note: CQI's `Color` column is the colour of the green (unroasted) bean, not a roast level.

## Source and licence

Data scraped from the CQI database by James LeDoux:
https://github.com/jldbc/coffee-quality-database (`data/arabica_data_cleaned.csv`).
The same data is mirrored on Kaggle as *Coffee Beans Reviews by Coffee Quality Institute*.

```
MIT License

Copyright (c) 2018 James LeDoux

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```
