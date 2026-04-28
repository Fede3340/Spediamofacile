#!/usr/bin/env python3
"""
Identifica classi CSS triviali (1-3 properties tutte mappabili a Tailwind utility)
e per ciascuna:
1. Stampa la mappa classe -> utility Tailwind
2. Trova nei file Vue dove è usata
3. Genera report dei punti di sostituzione

USO: python scripts/trivialize-css.py [--apply]
"""
import argparse
import re
from pathlib import Path
from collections import defaultdict

ROOT = Path(__file__).resolve().parent.parent
CSS_DIR = ROOT / "apps/web/assets/css"
VUE_DIRS = [
    ROOT / "apps/web/pages",
    ROOT / "apps/web/components",
    ROOT / "apps/web/layouts",
    ROOT / "apps/web/app.vue",
    ROOT / "apps/web/error.vue",
]

# Mapping: regex su declaration -> utility Tailwind
TRIVIAL_MAP = [
    (r'display\s*:\s*flex\s*;?', 'flex'),
    (r'display\s*:\s*block\s*;?', 'block'),
    (r'display\s*:\s*inline-block\s*;?', 'inline-block'),
    (r'display\s*:\s*inline-flex\s*;?', 'inline-flex'),
    (r'display\s*:\s*grid\s*;?', 'grid'),
    (r'display\s*:\s*none\s*;?', 'hidden'),
    (r'align-items\s*:\s*center\s*;?', 'items-center'),
    (r'align-items\s*:\s*flex-start\s*;?', 'items-start'),
    (r'align-items\s*:\s*flex-end\s*;?', 'items-end'),
    (r'justify-content\s*:\s*center\s*;?', 'justify-center'),
    (r'justify-content\s*:\s*space-between\s*;?', 'justify-between'),
    (r'justify-content\s*:\s*flex-end\s*;?', 'justify-end'),
    (r'flex-direction\s*:\s*column\s*;?', 'flex-col'),
    (r'flex-direction\s*:\s*row\s*;?', 'flex-row'),
    (r'text-align\s*:\s*center\s*;?', 'text-center'),
    (r'text-align\s*:\s*right\s*;?', 'text-right'),
    (r'text-align\s*:\s*left\s*;?', 'text-left'),
    (r'font-weight\s*:\s*bold\s*;?', 'font-bold'),
    (r'font-weight\s*:\s*600\s*;?', 'font-semibold'),
    (r'font-weight\s*:\s*700\s*;?', 'font-bold'),
    (r'font-weight\s*:\s*500\s*;?', 'font-medium'),
    (r'cursor\s*:\s*pointer\s*;?', 'cursor-pointer'),
    (r'cursor\s*:\s*not-allowed\s*;?', 'cursor-not-allowed'),
    (r'overflow\s*:\s*hidden\s*;?', 'overflow-hidden'),
    (r'overflow-x\s*:\s*hidden\s*;?', 'overflow-x-hidden'),
    (r'overflow-y\s*:\s*auto\s*;?', 'overflow-y-auto'),
    (r'position\s*:\s*relative\s*;?', 'relative'),
    (r'position\s*:\s*absolute\s*;?', 'absolute'),
    (r'position\s*:\s*fixed\s*;?', 'fixed'),
    (r'border-radius\s*:\s*9999px\s*;?', 'rounded-full'),
    (r'border-radius\s*:\s*50%\s*;?', 'rounded-full'),
    (r'width\s*:\s*100%\s*;?', 'w-full'),
    (r'height\s*:\s*100%\s*;?', 'h-full'),
    (r'pointer-events\s*:\s*none\s*;?', 'pointer-events-none'),
    (r'pointer-events\s*:\s*auto\s*;?', 'pointer-events-auto'),
    (r'opacity\s*:\s*0\s*;?', 'opacity-0'),
    (r'opacity\s*:\s*1\s*;?', 'opacity-100'),
    (r'visibility\s*:\s*hidden\s*;?', 'invisible'),
    (r'visibility\s*:\s*visible\s*;?', 'visible'),
]


def parse_css_top_blocks(content: str):
    """Yields (classname, full_block, body_text) per top-level class def."""
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
            body_match = re.search(r'\{(.*?)\}\s*$', block, re.DOTALL)
            body = body_match.group(1) if body_match else ''
            yield classname, block, body, i, j
            i = j
        else:
            i += 1


def map_decl_to_utility(decl):
    decl = decl.strip().rstrip(';').strip() + ';'
    for pat, util in TRIVIAL_MAP:
        if re.fullmatch(pat, decl, re.IGNORECASE):
            return util
    return None


def is_trivial(body_text):
    """Returns list of utilities if all decl are mappable, else None."""
    body_text = body_text.strip()
    if '{' in body_text or '@' in body_text or '::' in body_text or '&:' in body_text:
        return None
    decls = [d.strip() for d in body_text.split(';') if d.strip()]
    if not decls or len(decls) > 3:
        return None
    utils = []
    for d in decls:
        util = map_decl_to_utility(d + ';')
        if util is None:
            return None
        utils.append(util)
    return utils


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--apply", action="store_true")
    args = parser.parse_args()

    css_files = sorted(CSS_DIR.rglob("*.css"))
    vue_files = []
    for d in VUE_DIRS:
        if d.is_file():
            vue_files.append(d)
        elif d.is_dir():
            vue_files.extend(d.rglob("*.vue"))

    # Cache vue contents
    vue_contents = {f: f.read_text(encoding="utf-8", errors="ignore") for f in vue_files}

    # Per ogni CSS file, trova classi triviali e poi mappa-le ai vue file
    total_replaced = 0
    total_lines_saved = 0

    for css in css_files:
        try:
            content = css.read_text(encoding="utf-8")
        except Exception:
            continue
        # Map class -> count occurrences in file (sia top level che dentro media-query)
        class_count = defaultdict(int)
        for m in re.finditer(r'\.([a-zA-Z_][a-zA-Z0-9_-]*)\b', content):
            class_count[m.group(1)] += 1

        # Trovo tutte le classi top-level e quelle triviali, ESCLUDO se appare > 1 volta
        # nel file (sintomo di override responsive o variant)
        trivial_blocks = []
        for cls_name, block, body, start, end in parse_css_top_blocks(content):
            utils = is_trivial(body)
            if not utils:
                continue
            # SAFE-MODE: skip se la classe appare più di 1 volta nel file
            # (override responsive / variants / hover states)
            if class_count[cls_name] > 1:
                continue
            # SAFE-MODE: skip se il file ha @media query (rischio override invisibile)
            if '@media' in content:
                # accetto solo se la classe NON appare in nessun blocco @media
                media_pattern = re.compile(r'@media[^{]+\{[^@]*?\.' + re.escape(cls_name) + r'\b')
                if media_pattern.search(content):
                    continue
            trivial_blocks.append((cls_name, utils, block))

        if not trivial_blocks:
            continue

        new_content = content
        for cls_name, utils, block in trivial_blocks:
            # Cerco file Vue che usano questa classe
            usage_files = []
            pat_class_attr = re.compile(rf'class\s*=\s*["\'`]([^"\'`]*\b{re.escape(cls_name)}\b[^"\'`]*)["\'`]')
            for vf, vc in vue_contents.items():
                if pat_class_attr.search(vc):
                    usage_files.append(vf)
            if not usage_files:
                # NESSUN uso => orphan: cancellabile direttamente
                if args.apply:
                    new_content = new_content.replace(block, '')
                    total_replaced += 1
                    total_lines_saved += block.count('\n')
                print(f"  ORPHAN .{cls_name} (utils: {' '.join(utils)}) -> {css.relative_to(ROOT)}")
                continue
            # Sostituisco nei file Vue
            replaced_anywhere = False
            for vf in usage_files:
                vc = vue_contents[vf]
                new_vc = vc
                # Per ogni occorrenza in class="..." sostituisco con utility
                def sub_class(match):
                    nonlocal replaced_anywhere
                    full = match.group(1)
                    tokens = full.split()
                    if cls_name not in tokens:
                        return match.group(0)
                    new_tokens = []
                    for t in tokens:
                        if t == cls_name:
                            new_tokens.extend(utils)
                            replaced_anywhere = True
                        else:
                            new_tokens.append(t)
                    quote_char = match.group(0)[0] if match.group(0)[0] in '"\'`' else '"'
                    return f'class={quote_char}{" ".join(new_tokens)}{quote_char}'
                # ricerca completa
                new_vc = re.sub(r'class\s*=\s*"([^"]*)"', sub_class, new_vc)
                new_vc = re.sub(r"class\s*=\s*'([^']*)'", sub_class, new_vc)
                new_vc = re.sub(r'class\s*=\s*`([^`]*)`', sub_class, new_vc)
                if new_vc != vc:
                    if args.apply:
                        vf.write_text(new_vc, encoding="utf-8")
                    vue_contents[vf] = new_vc

            if replaced_anywhere:
                # Cancello blocco CSS
                if args.apply:
                    new_content = new_content.replace(block, '')
                    total_replaced += 1
                    total_lines_saved += block.count('\n')
                print(f"  REPLACED .{cls_name} -> {' '.join(utils)} in {len(usage_files)} vue file ({css.relative_to(ROOT)})")

        if args.apply and new_content != content:
            css.write_text(new_content, encoding="utf-8")

    print(f"\n=== TOTALE: {total_replaced} classi sostituite, {total_lines_saved} LOC CSS risparmiate ===")


if __name__ == "__main__":
    main()
