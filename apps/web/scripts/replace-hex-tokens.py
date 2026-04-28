#!/usr/bin/env python3
"""
Sprint 4.7 — Hex hardcoded -> CSS tokens
Sostituisce hex frequenti con var(--token, #HEX) nei blocchi <style> dei .vue.

VINCOLI:
 - Sostituzione SOLO dentro <style>...</style>, mai in <template>/<script>.
 - Usa var(--token, #HEX) come fallback per zero regressioni.
 - Preserva lower/upper case originario nel fallback.
 - Salta #fff/#ffffff/#000/#000000 (non servono token).
 - Matcha solo hex #RRGGBB, case-insensitive, con word-boundary.

Esegui da root:  python scripts/replace-hex-tokens.py [--dry-run]
"""
from __future__ import annotations

import re
import sys
from pathlib import Path
from collections import Counter

ROOT = Path(__file__).resolve().parent.parent
TARGETS = [ROOT / "components", ROOT / "pages"]
SKIP_FILES = {
    # Altri agent stanno lavorando qui → non toccare
    ROOT / "pages" / "la-tua-spedizione" / "[step].vue",
}

# Mapping hex -> token (chiavi lowercase, tutti i match sono normalizzati).
# Ordine: brand core, poi text, poi border, poi surface, poi accent, poi success/warn.
HEX_TO_TOKEN: dict[str, str] = {
    # --- Brand core ---
    "#095866": "--color-brand-primary",
    "#074a56": "--color-brand-primary-hover",
    "#0b6d7d": "--color-brand-primary-light",
    "#e44203": "--color-brand-accent",
    "#c93800": "--color-brand-accent-hover",
    "#c73600": "--color-accent-dark",
    "#0a8a7a": "--color-brand-success",
    "#ef4444": "--color-brand-error",
    # --- Text ---
    "#1d2738": "--color-brand-text",
    "#5a6474": "--color-brand-text-secondary",
    "#6b7280": "--color-brand-text-muted",
    "#252b42": "--color-text-dark-alt",
    "#5c6473": "--color-text-slate",
    "#7a8392": "--color-text-slate-alt",
    "#8a919c": "--color-text-faint",
    "#a0a7b4": "--color-text-ghost",
    "#7c8594": "--color-text-dim",
    "#737373": "--color-neutral-500",
    # --- Borders ---
    "#dfe2e7": "--color-border",
    "#e6e9ee": "--color-border-soft",
    "#e9ebec": "--color-border-muted",
    "#c0c5ce": "--color-border-hover",
    "#d9dfe6": "--color-border-strong",
    "#c7d8de": "--color-border-teal-soft",
    "#dfe8ec": "--color-border-teal-faint",
    "#e5eaf0": "--color-border-faint",
    "#c8ccd0": "--color-border-neutral",
    "#c0c5cc": "--color-border-neutral-alt",
    # --- Surfaces ---
    "#f8f9fb": "--surface-page",
    "#eef0f3": "--surface-page-end",
    "#fbfcfd": "--surface-subtle",
    "#f5f6f9": "--surface-muted",
    "#f7f8fa": "--surface-soft",
    "#eef7f8": "--surface-teal-soft",
    "#f4fafc": "--surface-teal-wash",
    "#f0f8f9": "--surface-teal-tint",
    "#f3f8f9": "--surface-teal-pale",
    "#fff8f5": "--surface-accent-soft",
    "#fff5f4": "--surface-accent-wash",
    "#fafbfc": "--surface-neutral-50",
    "#f5f8fa": "--surface-neutral-100",
    "#f8fafb": "--surface-teal-wash-alt",
    "#edf7f8": "--surface-teal-mist",
    "#eef2f4": "--surface-teal-haze",
    "#d8e9f0": "--surface-teal-glow",
    # --- Accent scale ---
    "#fff7f2": "--color-accent-50",
    "#fef2eb": "--color-accent-100",
    "#fde8da": "--color-accent-200",
    "#fcd9b2": "--color-accent-300",
    "#f2d6c6": "--color-accent-400",
    "#a82e00": "--color-accent-700",
    "#7c2d12": "--color-accent-800",
    # --- Teal scale ---
    "#f0f7f8": "--color-teal-50",
    "#9eb9c1": "--color-teal-300",
    "#0b9ab3": "--color-teal-400",
    "#053440": "--color-teal-700",
    "#042830": "--color-teal-800",
    # --- Success (teal-green brand) ---
    "#e8f5f2": "--color-success-bg",
    "#c8e9d8": "--color-success-bg-strong",
    "#b7ebc6": "--color-success-border",
    "#0a6b5e": "--color-success-text",
    "#0a7a53": "--color-success-text-strong",
    # --- Success (verde system — Stripe, pagamenti) ---
    "#f0fdf4": "--color-success-surface-soft",
    "#d1fae5": "--color-success-surface-tint",
    "#047857": "--color-success-emerald",
    "#166534": "--color-success-forest",
    # --- Error ---
    "#fef2f2": "--color-error-bg",
    "#fecaca": "--color-error-border",
    "#b91c1c": "--color-error-text",
    "#991b1b": "--color-error-text-strong",
    # --- Warning ---
    "#fffbeb": "--color-warning-bg",
    "#b45309": "--color-warning-text",
    "#92400e": "--color-warning-text-strong",
    # --- Secondary teal soft ---
    "#135a67": "--color-brand-secondary-soft-text",
    "#0a4954": "--color-brand-secondary-soft-text-strong",
    "#98b4bc": "--color-brand-secondary-soft-border-strong",
    # --- Brand bg-alt ---
    "#f3f4f6": "--color-brand-bg-alt",
    # --- Bridge: hex "fuori palette" residui (Tailwind slate/teal/orange) mappati a token brand ---
    # Teal Tailwind 600 vicino al brand primary (#0d9488 ~ #095866 family)
    "#0d9488": "--color-brand-primary",
    # Orange Tailwind 500 → brand accent
    "#f97316": "--color-brand-accent",
    # Slate/navy scuri che fanno da alias di testo scuro brand
    "#233547": "--color-brand-text",
    "#1f2a3c": "--color-brand-text",
    "#1a1a1a": "--color-brand-text",
    "#475569": "--color-brand-text-secondary",
    "#334155": "--color-brand-text",
    "#5c6d7f": "--color-brand-text-secondary",
    "#738394": "--color-text-faint",
    "#5f6b7a": "--color-brand-text-secondary",
    "#525252": "--color-brand-text-secondary",
    # Bordi/superfici slate
    "#cbd5e1": "--color-border",
    "#d8dce3": "--color-border",
    "#e2e8f0": "--color-border-soft",
    "#bfd2d8": "--color-border-teal-soft",
    "#eef1f3": "--surface-page-end",
    "#f0f3f5": "--surface-neutral-50",
    # Varianti accent/primary minori
    "#005961": "--color-brand-primary",
    "#074e5b": "--color-brand-primary-hover",
    "#c63802": "--color-brand-accent-hover",
    "#a34b18": "--color-accent-700",
}

# Hex da SALTARE (bianco/nero: non servono token)
HEX_SKIP = {"#fff", "#ffffff", "#000", "#000000"}

STYLE_BLOCK_RE = re.compile(r"(<style\b[^>]*>)(.*?)(</style>)", re.DOTALL)
HEX_RE = re.compile(r"#[0-9a-fA-F]{6}\b")


def replace_in_style(style_body: str, counter: Counter) -> str:
    def repl(m: re.Match[str]) -> str:
        raw = m.group(0)
        key = raw.lower()
        if key in HEX_SKIP:
            return raw
        token = HEX_TO_TOKEN.get(key)
        if not token:
            return raw
        counter[key] += 1
        # Preserve original case in fallback
        return f"var({token}, {raw})"

    # Evita doppie sostituzioni: se hex già dentro `var(--xxx,`, skippa.
    # Strategia: sostituisci solo hex non preceduti da "," dentro una var(...)
    # Trick: processa la stringa splittando sui token var(--...,...) esistenti.
    parts = re.split(r"(var\s*\(\s*--[^)]+\))", style_body)
    for i, part in enumerate(parts):
        if i % 2 == 1:
            # questo è già un var(...), non toccare
            continue
        parts[i] = HEX_RE.sub(repl, part)
    return "".join(parts)


def process_file(path: Path, counter: Counter, dry_run: bool) -> int:
    try:
        original = path.read_text(encoding="utf-8")
    except UnicodeDecodeError:
        print(f"  [SKIP] encoding issue: {path}")
        return 0

    new_content = STYLE_BLOCK_RE.sub(
        lambda m: m.group(1) + replace_in_style(m.group(2), counter) + m.group(3),
        original,
    )
    if new_content == original:
        return 0
    if not dry_run:
        path.write_text(new_content, encoding="utf-8")
    return 1


def main() -> int:
    dry_run = "--dry-run" in sys.argv
    counter: Counter[str] = Counter()
    files_changed = 0
    files_scanned = 0

    for target in TARGETS:
        if not target.is_dir():
            continue
        for vue in target.rglob("*.vue"):
            if vue.resolve() in {p.resolve() for p in SKIP_FILES}:
                print(f"  [SKIP list] {vue.relative_to(ROOT)}")
                continue
            files_scanned += 1
            files_changed += process_file(vue, counter, dry_run)

    mode = "[DRY RUN]" if dry_run else "[APPLIED]"
    print(f"\n{mode} file scanned: {files_scanned}, file modificati: {files_changed}")
    print(f"{mode} sostituzioni totali: {sum(counter.values())}")
    print(f"\nTop-20 hex sostituiti:")
    for hex_k, n in counter.most_common(20):
        tok = HEX_TO_TOKEN[hex_k]
        print(f"  {n:>5}  {hex_k}  ->  var({tok})")
    return 0


if __name__ == "__main__":
    sys.exit(main())
