#!/usr/bin/env python3
"""
Pick the 40 CQI Arabica lots used as Roast & Notes demo products.

Usage:
    curl -LO https://raw.githubusercontent.com/jldbc/coffee-quality-database/master/data/arabica_data_cleaned.csv
    python3 database/data/select_cqi.py arabica_data_cleaned.csv database/data/cqi-selected.csv

Rules (docs/specs/SPEC-catalog.md):
  - lots with a region and processing method, Total.Cup.Points >= 82
    (variety / altitude may be blank — too few Dark-tendency lots otherwise)
  - stratified by roast tendency (Acidity / Body): target Light 13, Medium 14, Dark 13;
    a short stratum is topped up with Medium lots
  - every Thai lot that passes is kept
  - round-robin across countries so the demo covers many origins
  - the 8 highest-scoring picks are also sold as drip bags
Output is deterministic: running it twice produces the same file.
"""
import csv
import re
import sys
from collections import OrderedDict, defaultdict
from datetime import datetime

QUOTA = {"light": 13, "medium": 14, "dark": 13}
DRIP_BAGS = 8
MIN_POINTS = 82.0

COUNTRY_NAMES = {
    "Tanzania, United Republic Of": "Tanzania",
    "United States (Hawaii)": "United States (Hawaii)",
    "Cote d?Ivoire": "Cote d'Ivoire",
}
PROCESS = {
    "Washed / Wet": "washed",
    "Natural / Dry": "natural",
    "Pulped natural / honey": "honey",
    "Semi-washed / Semi-pulped": "semi_washed",
    "Other": "other",
}
SCORES = ["Aroma", "Flavor", "Aftertaste", "Acidity", "Body", "Balance",
          "Uniformity", "Clean.Cup", "Sweetness", "Cupper.Points"]
TYPOS = {"thialand": "thailand", "chiangrai": "chiang rai"}
PLACEHOLDER_FARMS = {"several", "various", "n/a", "na", "-"}
SMALL_WORDS = {"de", "del", "la", "el", "y", "of"}


def blank(value: str) -> bool:
    return value is None or value.strip() in ("", "NA")


def title(text: str) -> str:
    words = re.split(r"(\s+|-)", text.strip().lower())
    out = []
    for i, w in enumerate(words):
        out.append(w if (i and w in SMALL_WORDS) else w[:1].upper() + w[1:])
    return "".join(out)


def clean_region(region: str, country: str) -> str:
    text = region.lower()
    for wrong, right in TYPOS.items():
        text = text.replace(wrong, right)
    # drop the country name and empty comma parts, keep the most specific remaining part
    country_words = country.lower().split(" (")[0]
    parts = [p.strip(" .") for p in re.split(r"[,/;]", text)]
    parts = [p.replace(country_words, "").strip(" .-") for p in parts]
    parts = [p for p in parts if p and len(p) > 2]
    if not parts:
        return ""
    return title(parts[-1] if len(parts) > 1 and len(parts[-1]) < 25 else parts[0])[:60]


def roast_tendency(row) -> str:
    ratio = float(row["Acidity"]) / float(row["Body"])
    if ratio > 1.05:
        return "light"
    if ratio < 0.95:
        return "dark"
    return "medium"


def variety(row) -> str:
    return "" if blank(row["Variety"]) or row["Variety"] == "Other" else row["Variety"]


def altitude(row) -> str:
    """Mean altitude in metres; blank when missing or obviously mis-entered (feet / typos)."""
    if blank(row["altitude_mean_meters"]):
        return ""
    value = float(row["altitude_mean_meters"])
    return str(round(value)) if 300 <= value <= 3000 else ""


def parse_date(text: str) -> str:
    if blank(text):
        return ""
    cleaned = re.sub(r"(\d+)(st|nd|rd|th)", r"\1", text)
    return datetime.strptime(cleaned, "%B %d, %Y").date().isoformat()


def candidates(rows):
    seen = set()
    for row in rows:
        # region + process are required; variety and altitude are optional (nullable columns)
        if blank(row["Region"]) or row["Processing.Method"] not in PROCESS:
            continue
        if float(row["Total.Cup.Points"]) < MIN_POINTS:
            continue
        country = COUNTRY_NAMES.get(row["Country.of.Origin"], row["Country.of.Origin"])
        if country == "United States":  # mislabelled in CQI (e.g. region "Antioquia" is in Colombia)
            continue
        region = clean_region(row["Region"], country)
        if not region:
            continue
        key = (country, region, variety(row))
        if key in seen:  # keep only the best lot per country/region/variety
            continue
        seen.add(key)
        yield {**row, "_country": country, "_region": region, "_roast": roast_tendency(row)}


def round_robin(lots, limit, already):
    """Take up to `limit` lots, cycling through countries (highest score first)."""
    by_country = OrderedDict()
    for lot in lots:
        by_country.setdefault(lot["_country"], []).append(lot)
    picked = []
    while len(picked) < limit and any(by_country.values()):
        for country in list(by_country):
            if by_country[country] and len(picked) < limit:
                lot = by_country[country].pop(0)
                if lot[""] not in already:
                    picked.append(lot)
    return picked


def main(src: str, dest: str) -> None:
    with open(src, newline="", encoding="utf-8") as f:
        rows = list(csv.DictReader(f))

    pool = sorted(candidates(rows), key=lambda r: (-float(r["Total.Cup.Points"]), int(r[""])))
    strata = defaultdict(list)
    for lot in pool:
        strata[lot["_roast"]].append(lot)

    selected = []
    shortfall = 0
    # Light and Dark first; any shortfall (Dark lots are rare in CQI) is filled with Medium lots
    for roast in ("light", "dark", "medium"):
        quota = QUOTA[roast] + (shortfall if roast == "medium" else 0)
        thai = [lot for lot in strata[roast] if lot["_country"] == "Thailand"][:quota]
        ids = {lot[""] for lot in thai}
        others = [lot for lot in strata[roast] if lot[""] not in ids]
        chosen = thai + round_robin(others, quota - len(thai), ids)
        shortfall += quota - len(chosen) if roast != "medium" else 0
        if roast == "medium" and len(chosen) < quota:
            sys.exit(f"Not enough lots: {len(selected) + len(chosen)}/{sum(QUOTA.values())}")
        selected += chosen

    selected.sort(key=lambda r: int(r[""]))
    top = sorted(selected, key=lambda r: (-float(r["Total.Cup.Points"]), int(r[""])))[:DRIP_BAGS]
    drip_ids = {lot[""] for lot in top}

    fields = ["cqi_ref", "country", "region", "farm_name", "variety", "process", "altitude_m",
              "graded_on", "roast_tendency"] + [s.lower().replace(".", "_") for s in SCORES] + ["total_points", "drip_bag"]
    with open(dest, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f, lineterminator="\n")
        writer.writerow(fields)
        for lot in selected:
            writer.writerow([
                lot[""], lot["_country"], lot["_region"],
                "" if blank(lot["Farm.Name"]) or lot["Farm.Name"].lower() in PLACEHOLDER_FARMS else title(lot["Farm.Name"])[:150],
                variety(lot), PROCESS[lot["Processing.Method"]],
                altitude(lot), parse_date(lot["Grading.Date"]), lot["_roast"],
                *[f"{float(lot[s]):.2f}" for s in SCORES],
                f"{float(lot['Total.Cup.Points']):.2f}", int(lot[""] in drip_ids),
            ])

    counts = defaultdict(int)
    for lot in selected:
        counts[lot["_roast"]] += 1
    print(f"{len(selected)} lots, {len({l['_country'] for l in selected})} countries, roast {dict(counts)}, "
          f"thai {sum(l['_country'] == 'Thailand' for l in selected)}, drip bags {len(drip_ids)} -> {dest}")


if __name__ == "__main__":
    if len(sys.argv) != 3:
        sys.exit(__doc__)
    main(sys.argv[1], sys.argv[2])
