#!/usr/bin/env bash
# Sprint 7.1 — Upload source map al Sentry symbolication service.
#
# Esegui DOPO `npm run build` e PRIMA di deploy in produzione.
# Richiede:
#   - SENTRY_AUTH_TOKEN  (secret CI, Internal Integration con scope project:releases)
#   - SENTRY_ORG         (es. "spediamofacile")
#   - SENTRY_PROJECT     (es. "spediamofacile-nuxt")
#   - SENTRY_RELEASE     (stesso valore di NUXT_PUBLIC_SENTRY_RELEASE, es. git sha)
#
# Step finale: rimuove le .map da .output/public/_nuxt/ per non esporle al web.
# Sentry le conserva lato server per resolvere gli stack trace.

set -euo pipefail

: "${SENTRY_AUTH_TOKEN:?Variabile SENTRY_AUTH_TOKEN mancante}"
: "${SENTRY_ORG:?Variabile SENTRY_ORG mancante}"
: "${SENTRY_PROJECT:?Variabile SENTRY_PROJECT mancante}"
: "${SENTRY_RELEASE:?Variabile SENTRY_RELEASE mancante}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
SOURCEMAPS_DIR="${PROJECT_ROOT}/.output/public/_nuxt"

if [ ! -d "${SOURCEMAPS_DIR}" ]; then
  echo "[upload-sourcemaps] directory non trovata: ${SOURCEMAPS_DIR}" >&2
  echo "[upload-sourcemaps] esegui prima 'npm run build'" >&2
  exit 1
fi

echo "[upload-sourcemaps] release=${SENTRY_RELEASE} project=${SENTRY_PROJECT}"

# Crea il record della release su Sentry (idempotente).
npx --yes @sentry/cli releases new "${SENTRY_RELEASE}" \
  --org "${SENTRY_ORG}" \
  --project "${SENTRY_PROJECT}"

# Upload dei .js + .map con risoluzione automatica del mapping.
npx --yes @sentry/cli sourcemaps upload \
  --org "${SENTRY_ORG}" \
  --project "${SENTRY_PROJECT}" \
  --release "${SENTRY_RELEASE}" \
  "${SOURCEMAPS_DIR}"

# Finalizza la release (segna il deploy come completato per il dashboard).
npx --yes @sentry/cli releases finalize "${SENTRY_RELEASE}" \
  --org "${SENTRY_ORG}" \
  --project "${SENTRY_PROJECT}"

# Sicurezza: rimuovi le .map dal bundle pubblico — Sentry ora le ha.
echo "[upload-sourcemaps] rimozione .map dal bundle pubblico"
find "${SOURCEMAPS_DIR}" -name '*.map' -type f -delete

echo "[upload-sourcemaps] OK"
