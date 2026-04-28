#!/usr/bin/env python3
"""
Purge sicuro classi CSS morte.

Strategia conservativa:
1. Estrae classi top-level da un file CSS (non quelle in @media — sono già conteggiate altrove)
2. Per ogni classe, cerca uso REALE in tutti i file Vue/TS/CSS:
   - class="...nome..." / :class
   - @apply nome
   - "nome" / 'nome' (stringa dinamica)
   - nome- prefix (pattern dinamici come `class-${var}`)
3. Se 0 hit → marca come morta
4. Cancella i blocchi { ... } di queste classi (incluso quelli dentro media-query)
5. Salva file con backup .bak

USO:
  python3 scripts/purge-dead-css.py --check       # solo report (no cancellazione)
  python3 scripts/purge-dead-css.py --apply       # esegue purge
  python3 scripts/purge-dead-css.py --apply --file=apps/web/assets/css/contatti.css
"""
import argparse
import re
import os
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SEARCH_DIRS = [
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
    ROOT / "apps/web/assets/css",
]

CSS_FILES = [
    "apps/web/assets/css/contatti.css",
    "apps/web/assets/css/tracking.css",
    "apps/web/assets/css/autenticazione.css",
    "apps/web/assets/css/servizi.css",
    "apps/web/assets/css/legal-pages.css",
    "apps/web/assets/css/pages/home.css",
    "apps/web/assets/css/content.css",
    "apps/web/assets/css/layout.css",
    "apps/web/assets/css/account.css",
    "apps/web/assets/css/admin.css",
    "apps/web/assets/css/shipment-flow.css",
    "apps/web/assets/css/main.css",
]

PROTECT_KEYWORDS = {"sr-only", "focus-visible", "ring", "shadow", "transition", "v-cloak"}


def collect_haystack():
    """Concatena tutto il contenuto Vue/TS/CSS in una megastring per ricerca."""
    parts = []
    for d in SEARCH_DIRS:
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


def class_used(cls: str, hay: str, css_self: str) -> bool:
    """True se la classe è usata da qualche parte (Vue/TS/CSS oltre a self)."""
    if cls in PROTECT_KEYWORDS:
        return True
    # 1) class="..." / :class / class binding
    pat_class = re.compile(rf'class[=:][\"\'`][^\"\'`]*\b{re.escape(cls)}\b', re.MULTILINE)
    if pat_class.search(hay):
        return True
    # 2) :class composti, computed: ... 'cls' : 'other-cls'
    pat_str = re.compile(rf'[\"\'`]{re.escape(cls)}[\"\'`]')
    # esclude self CSS file
    hay_minus = hay.replace(css_self, "")
    if pat_str.search(hay_minus):
        return True
    # 3) @apply CSS reference (Tailwind 4)
    pat_apply = re.compile(rf'@apply[^;]*\b{re.escape(cls)}\b')
    if pat_apply.search(hay):
        return True
    # 4) dynamic prefix: `cls-${var}` o `prefix-cls`. Cerco token completo o token-prefix
    pat_token = re.compile(rf'[\"\'`][^\"\'`]*\b{re.escape(cls)}-?\w*[\"\'`]')
    if pat_token.search(hay_minus):
        return True
    # 5) ID hash o attr selector — improbabile ma sicuro
    return False


def extract_top_classes(css_content: str) -> list:
    """Estrae nomi classi che iniziano una riga (top-level)."""
    classes = set()
    for m in re.finditer(r'^\.([a-zA-Z_][a-zA-Z0-9_-]*)', css_content, re.MULTILINE):
        classes.add(m.group(1))
    return sorted(classes)


def purge_class_blocks(css: str, classes_to_kill: set) -> tuple:
    """
    Rimuove tutti i blocchi { ... } che corrispondono a queste classi.
    Gestisce annidamento (cerca `}` matchata).
    Ritorna (nuovo_css, num_blocchi_rimossi).
    """
    out = []
    i = 0
    L = len(css)
    removed = 0
    while i < L:
        # Cerca pattern .nome { (selettore top-level o composto)
        # Ma solo per classi target. Pattern: ^\.classname o ^.classname[selettore]
        m = re.match(r'(\s*)\.([a-zA-Z_][a-zA-Z0-9_-]*)([^{]*)\{', css[i:])
        if m and m.group(2) in classes_to_kill:
            # Cerco la chiusura `}` matchata
            depth = 1
            j = i + m.end()
            while j < L and depth > 0:
                if css[j] == "{":
                    depth += 1
                elif css[j] == "}":
                    depth -= 1
                j += 1
            # Salta il blocco
            i = j
            removed += 1
            # consuma whitespace dopo
            while i < L and css[i] in " \t\n\r":
                i += 1
            continue
        # Copia normalmente fino alla prossima riga
        next_nl = css.find("\n", i)
        if next_nl == -1:
            out.append(css[i:])
            break
        out.append(css[i:next_nl + 1])
        i = next_nl + 1
    return "".join(out), removed


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--apply", action="store_true", help="Esegue purge (default: solo check)")
    parser.add_argument("--file", help="Esegue solo su un file specifico")
    args = parser.parse_args()

    files = [args.file] if args.file else CSS_FILES
    hay = collect_haystack()
    total_removed = 0
    total_lines_saved = 0

    for css_path in files:
        full = ROOT / css_path
        if not full.exists():
            print(f"SKIP {css_path} (non esiste)")
            continue
        original = full.read_text(encoding="utf-8")
        classes = extract_top_classes(original)
        dead = set()
        for cls in classes:
            if not class_used(cls, hay, original):
                dead.add(cls)
        if not dead:
            print(f"OK   {css_path:55s} -> 0 morte")
            continue
        new_css, removed = purge_class_blocks(original, dead)
        lines_before = original.count("\n")
        lines_after = new_css.count("\n")
        delta = lines_before - lines_after
        print(f"DEAD {css_path:55s} -> {len(dead):3d} classi, {removed:3d} blocchi, -{delta:4d} LOC")
        if args.apply:
            full.with_suffix(".css.bak").write_text(original, encoding="utf-8")
            full.write_text(new_css, encoding="utf-8")
        total_removed += removed
        total_lines_saved += delta

    print(f"\n=== TOTALE: {total_removed} blocchi morti, {total_lines_saved} LOC risparmiate ===")
    if not args.apply:
        print("(--check mode: nessuna modifica. Riesegui con --apply per applicare)")


if __name__ == "__main__":
    main()
