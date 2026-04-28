#!/usr/bin/env python3
"""
Analizza CSS per identificare:
1. Classi DUPLICATE tra file (stesso .nome definito in 2+ CSS)
2. Classi TRIVIALI Tailwind (1-3 proprietà che mappano a utility)
3. Classi morte di secondo livello (usate solo in @media print → in genere ok)

USO:
  python scripts/analyze-css.py
"""
import re
import os
from pathlib import Path
from collections import defaultdict

ROOT = Path(__file__).resolve().parent.parent
CSS_DIR = ROOT / "apps/web/assets/css"

CSS_FILES = sorted(CSS_DIR.rglob("*.css"))


def parse_css_blocks(content):
    """Estrae blocchi top-level: dict {classname: full_block_text}."""
    blocks = {}
    i = 0
    L = len(content)
    while i < L:
        m = re.match(r'(\s*)\.([a-zA-Z_][a-zA-Z0-9_-]*)([^{,]*)\{', content[i:])
        if m:
            classname = m.group(2)
            depth = 1
            j = i + m.end()
            while j < L and depth > 0:
                if content[j] == '{':
                    depth += 1
                elif content[j] == '}':
                    depth -= 1
                j += 1
            block = content[i:j]
            if classname not in blocks:
                blocks[classname] = []
            blocks[classname].append(block)
            i = j
        else:
            i += 1
    return blocks


# ── 1) Classi duplicate tra file ──
class_to_files = defaultdict(list)
for css in CSS_FILES:
    try:
        content = css.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    blocks = parse_css_blocks(content)
    for cls in blocks:
        class_to_files[cls].append(str(css.relative_to(ROOT)))

print("=== CLASSI DUPLICATE TRA FILE ===")
dup_count = 0
for cls, files in sorted(class_to_files.items()):
    if len(files) > 1:
        dup_count += 1
        if dup_count <= 30:
            print(f"  {cls:40s} -> {len(files)} file: {files}")
print(f"\nTotale duplicate cross-file: {dup_count}")


# ── 2) Classi triviali Tailwind ──
TRIVIAL_PATTERNS = [
    # property: value -> tailwind utility
    (r'display\s*:\s*flex\s*;', 'flex'),
    (r'display\s*:\s*block\s*;', 'block'),
    (r'display\s*:\s*inline-block\s*;', 'inline-block'),
    (r'display\s*:\s*inline-flex\s*;', 'inline-flex'),
    (r'display\s*:\s*grid\s*;', 'grid'),
    (r'display\s*:\s*none\s*;', 'hidden'),
    (r'align-items\s*:\s*center\s*;', 'items-center'),
    (r'justify-content\s*:\s*center\s*;', 'justify-center'),
    (r'justify-content\s*:\s*space-between\s*;', 'justify-between'),
    (r'flex-direction\s*:\s*column\s*;', 'flex-col'),
    (r'text-align\s*:\s*center\s*;', 'text-center'),
    (r'text-align\s*:\s*right\s*;', 'text-right'),
    (r'font-weight\s*:\s*bold\s*;', 'font-bold'),
    (r'font-weight\s*:\s*600\s*;', 'font-semibold'),
    (r'font-weight\s*:\s*700\s*;', 'font-bold'),
    (r'cursor\s*:\s*pointer\s*;', 'cursor-pointer'),
    (r'overflow\s*:\s*hidden\s*;', 'overflow-hidden'),
    (r'position\s*:\s*relative\s*;', 'relative'),
    (r'position\s*:\s*absolute\s*;', 'absolute'),
    (r'position\s*:\s*fixed\s*;', 'fixed'),
    (r'border-radius\s*:\s*9999px\s*;', 'rounded-full'),
    (r'border-radius\s*:\s*50%\s*;', 'rounded-full'),
    (r'width\s*:\s*100%\s*;', 'w-full'),
    (r'height\s*:\s*100%\s*;', 'h-full'),
]

print("\n=== CLASSI POTENZIALMENTE TRIVIALI (3 properties max, tutte mappabili) ===")
trivial_count = 0
for css in CSS_FILES:
    try:
        content = css.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    blocks_pairs = []
    for cls_name, block_list in parse_css_blocks(content).items():
        if len(block_list) > 1:
            continue  # skip if multi-defined in same file (media query variants)
        block = block_list[0]
        # estraggo content dentro { ... }
        body = re.search(r'\{(.*)\}', block, re.DOTALL)
        if not body:
            continue
        body_text = body.group(1).strip()
        # ignora se ha @media, @keyframes, &, ::, :hover etc. nidificati
        if '{' in body_text or '@' in body_text or '::' in body_text or '&:' in body_text:
            continue
        decls = [d.strip() for d in body_text.split(';') if d.strip()]
        if not decls or len(decls) > 3:
            continue
        all_trivial = all(any(re.match(rf'^{pat}\s*$', d + ';') for pat, _ in TRIVIAL_PATTERNS) for d in decls)
        if all_trivial:
            blocks_pairs.append((cls_name, decls))
    if blocks_pairs:
        trivial_count += len(blocks_pairs)
        # print only first 3 per file
        print(f"\n{css.relative_to(ROOT)}: {len(blocks_pairs)} triviali")
        for cls_name, decls in blocks_pairs[:3]:
            print(f"  .{cls_name}: {decls}")

print(f"\nTotale classi triviali Tailwind: {trivial_count}")
