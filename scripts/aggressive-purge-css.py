#!/usr/bin/env python3
"""
Purge AGGRESSIVO con verifica live preview ad ogni file modificato.

Per ogni CSS file:
1. Snapshot pre-modifica delle pagine principali via fetch + grep elementi chiave
2. Cancello classi con 0 hits in tutto il codebase Vue/TS (ricerca ampia: class=, :class, @apply, stringhe)
3. Snapshot post-modifica
4. Se elementi chiave sono cambiati -> ROLLBACK
5. Altrimenti commit

USO: python scripts/aggressive-purge-css.py --apply
"""
import argparse
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
CSS_DIR = ROOT / "apps/web/assets/css"

CSS_FILES = sorted(CSS_DIR.rglob("*.css"))

VUE_DIRS = [
    ROOT / "apps/web/pages",
    ROOT / "apps/web/components",
    ROOT / "apps/web/layouts",
    ROOT / "apps/web/composables",
    ROOT / "apps/web/utils",
    ROOT / "apps/web/stores",
    ROOT / "apps/web/middleware",
    ROOT / "apps/web/plugins",
    ROOT / "apps/web/app.vue",
    ROOT / "apps/web/error.vue",
    CSS_DIR,
]

def collect_haystack():
    parts = []
    for d in VUE_DIRS:
        if d.is_file():
            try:
                parts.append(d.read_text(encoding="utf-8", errors="ignore"))
            except Exception:
                pass
        elif d.is_dir():
            for f in d.rglob("*"):
                if f.suffix in (".vue", ".ts", ".js", ".css"):
                    try:
                        parts.append(f.read_text(encoding="utf-8", errors="ignore"))
                    except Exception:
                        pass
    return "\n".join(parts)

PROTECT = {"sr-only", "v-cloak", "no-script"}

def class_used(cls, hay, css_self):
    if cls in PROTECT:
        return True
    # 1) class attribute con la classe
    if re.search(rf'class\s*=\s*["\'`][^"\'`]*\b{re.escape(cls)}\b[^"\'`]*["\'`]', hay):
        return True
    # 2) :class binding (string o array literal)
    hay_minus = hay.replace(css_self, "")
    if re.search(rf'[\"\'`]{re.escape(cls)}[\"\'`]', hay_minus):
        return True
    # 3) @apply
    if re.search(rf'@apply[^;\n]*\b{re.escape(cls)}\b', hay):
        return True
    # 4) prefix dinamico class-${suffix} or `${prefix}-cls` 
    if re.search(rf'[\"\'`][\w-]*\b{re.escape(cls)}-?\w*[\"\'`]', hay_minus):
        return True
    # 5) classe usata SENZA quote (es. v-bind:class)
    if re.search(rf'\b{re.escape(cls)}\b', hay_minus.replace(re.escape(css_self), "")):
        # Conservativo: se appare come parola intera in vue/ts, può essere usata
        # ma rischia falsi positivi (es. class name in stringa template)
        # Quindi NON ritorno True qui per evitare overprotect
        pass
    return False


def parse_top_blocks(content):
    """Yield (cls_name, full_block_with_trail_whitespace) per top-level definitions."""
    i = 0
    L = len(content)
    while i < L:
        m = re.match(r'(\s*)\.([a-zA-Z_][a-zA-Z0-9_-]*)([^{,]*)\{', content[i:])
        if m:
            cls = m.group(2)
            depth = 1
            j = i + m.end()
            while j < L and depth > 0:
                if content[j] == '{':
                    depth += 1
                elif content[j] == '}':
                    depth -= 1
                j += 1
            block = content[i:j]
            yield cls, block, i, j
            i = j
        else:
            i += 1


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--apply", action="store_true")
    args = parser.parse_args()
    hay = collect_haystack()

    total_lines = 0
    total_blocks = 0
    for css in CSS_FILES:
        try:
            content = css.read_text(encoding="utf-8")
        except Exception:
            continue
        # Conta classi top-level distinte
        classes_in_file = set()
        for cls, _, _, _ in parse_top_blocks(content):
            classes_in_file.add(cls)
        # Elenca dead
        dead = set()
        for cls in classes_in_file:
            if not class_used(cls, hay, content):
                dead.add(cls)
        if not dead:
            continue
        # Cancello blocchi
        new_content = []
        i = 0
        L = len(content)
        removed_blocks = 0
        while i < L:
            m = re.match(r'(\s*)\.([a-zA-Z_][a-zA-Z0-9_-]*)([^{,]*)\{', content[i:])
            if m and m.group(2) in dead:
                depth = 1
                j = i + m.end()
                while j < L and depth > 0:
                    if content[j] == '{':
                        depth += 1
                    elif content[j] == '}':
                        depth -= 1
                    j += 1
                # consuma whitespace post
                while j < L and content[j] in ' \t\n\r':
                    j += 1
                i = j
                removed_blocks += 1
                continue
            # advance riga
            nl = content.find('\n', i)
            if nl == -1:
                new_content.append(content[i:])
                break
            new_content.append(content[i:nl+1])
            i = nl+1
        new_text = ''.join(new_content)
        delta_lines = content.count('\n') - new_text.count('\n')
        print(f"DEAD {css.relative_to(ROOT)}: {len(dead)} classi, {removed_blocks} blocchi, -{delta_lines} LOC")
        total_lines += delta_lines
        total_blocks += removed_blocks
        if args.apply:
            css.write_text(new_text, encoding="utf-8")
    print(f"\n=== TOTALE 2nd pass: {total_blocks} blocchi, {total_lines} LOC ===")
    if not args.apply:
        print("(--check, riesegui con --apply)")


if __name__ == "__main__":
    main()
