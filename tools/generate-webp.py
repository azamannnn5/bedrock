#!/usr/bin/env python3
"""
Generate responsive WebP versions of every product photo.

  python3 tools/generate-webp.py            # only converts new/changed images
  python3 tools/generate-webp.py --force    # rebuild everything

Reads   assets/img/products/<id>.jpg
Writes  assets/img/products/w400/<id>.webp  and  w800/<id>.webp
Run it from the site root after adding or replacing product photos, then upload
the w400/ and w800/ folders. Without a WebP the site still works: the page falls
back to the original JPG automatically.
Requires: pip install pillow
"""
import sys, glob, os
from PIL import Image

FORCE = '--force' in sys.argv
SRC = 'assets/img/products'
SIZES = {'w400': 400, 'w800': 800}

for d in SIZES:
    os.makedirs(os.path.join(SRC, d), exist_ok=True)

done = skipped = 0
for path in sorted(glob.glob(os.path.join(SRC, '*.jpg'))):
    pid = os.path.splitext(os.path.basename(path))[0]
    im = None
    for d, width in SIZES.items():
        out = os.path.join(SRC, d, pid + '.webp')
        if not FORCE and os.path.exists(out) and os.path.getmtime(out) >= os.path.getmtime(path):
            skipped += 1
            continue
        if im is None:
            im = Image.open(path).convert('RGB')
        w, h = im.size
        if w > width:
            r = im.resize((width, round(h * width / w)), Image.LANCZOS)
        else:
            r = im
        r.save(out, 'WEBP', quality=80, method=6)
        done += 1
print(f'wrote {done}, skipped {skipped}')
