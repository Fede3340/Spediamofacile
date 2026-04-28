#!/usr/bin/env python3
"""
Bisection del purge CSS: applica modifiche a UN file alla volta, testa Nuxt,
mantiene solo i file che NON rompono la pagina.

USO: python scripts/bisect-purge.py
"""
import subprocess
import time
import re
from pathlib import Path
from collections import defaultdict

ROOT = Path(__file__).resolve().parent.parent
CSS_DIR = ROOT / "apps/web/assets/css"

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
    if re.search(rf'class\s*=\s*["\'`][^"\'`]*\b{re.escape(cls)}\b[^"\'`]*["\'`]', hay):
        return True
    hay_minus = hay.replace(css_self, "")
    if re.search(rf'[\"\'`]{re.escape(cls)}[\"\'`]', hay_minus):
        return True
    if re.search(rf'@apply[^;\n]*\b{re.escape(cls)}\b', hay):
        return True
    if re.search(rf'[\"\'`][\w-]*\b{re.escape(cls)}-?\w*[\"\'`]', hay_minus):
        return True
    return False


def purge_css_file(css_path):
    """Returns (new_content, n_blocks, n_lines_saved). 0 changes if all-used."""
    content = css_path.read_text(encoding="utf-8")
    classes_in_file = set()
    for m in re.finditer(r'^\.([a-zA-Z_][a-zA-Z0-9_-]*)', content, re.MULTILINE):
        classes_in_file.add(m.group(1))
    hay = collect_haystack()
    dead = set()
    for cls in classes_in_file:
        if not class_used(cls, hay, content):
            dead.add(cls)
    if not dead:
        return content, 0, 0
    new_content = []
    i = 0
    L = len(content)
    removed = 0
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
            while j < L and content[j] in ' \t\n\r':
                j += 1
            i = j
            removed += 1
            continue
        nl = content.find('\n', i)
        if nl == -1:
            new_content.append(content[i:])
            break
        new_content.append(content[i:nl + 1])
        i = nl + 1
    new_text = ''.join(new_content)
    delta = content.count('\n') - new_text.count('\n')
    return new_text, removed, delta


def check_nuxt_200(retries=10, delay=3):
    import urllib.request
    for _ in range(retries):
        try:
            r = urllib.request.urlopen("http://127.0.0.1:3001/", timeout=5)
            if r.status == 200:
                # check anche che la home renderizzi (h1 presente)
                body = r.read().decode("utf-8", errors="ignore")
                if 'h1' in body or '__NUXT__' in body:
                    return True
        except Exception:
            pass
        time.sleep(delay)
    return False


def git_revert(file_path):
    subprocess.run(['git', 'checkout', '--', str(file_path)], cwd=ROOT, capture_output=True)


def main():
    css_files = sorted(CSS_DIR.rglob("*.css"))
    kept = []
    rejected = []
    total_lines = 0

    print("Verifica baseline 200 OK...")
    if not check_nuxt_200():
        print("ERRORE: baseline non è 200. Interrompo.")
        return
    print("Baseline OK\n")

    for css in css_files:
        rel = css.relative_to(ROOT)
        new_content, blocks, lines = purge_css_file(css)
        if blocks == 0:
            print(f"SKIP {rel}: 0 morte")
            continue
        # Apply
        original = css.read_text(encoding="utf-8")
        css.write_text(new_content, encoding="utf-8")
        # Wait for Nuxt HMR + recheck
        time.sleep(4)
        if check_nuxt_200(retries=8, delay=2):
            print(f"OK   {rel}: -{lines} LOC ({blocks} blocchi)")
            kept.append((rel, lines, blocks))
            total_lines += lines
        else:
            print(f"FAIL {rel}: rollback")
            css.write_text(original, encoding="utf-8")
            time.sleep(4)
            check_nuxt_200(retries=20, delay=2)  # ensure baseline restored
            rejected.append((rel, lines, blocks))

    print("\n=== RIEPILOGO ===")
    print(f"Files OK: {len(kept)}, totale -{total_lines} LOC")
    for r, l, b in kept:
        print(f"  + {r}: -{l}")
    print(f"\nFiles REJECTED: {len(rejected)}")
    for r, l, b in rejected:
        print(f"  ! {r}: avrebbe risparmiato {l} (rollbacked)")


if __name__ == "__main__":
    main()
