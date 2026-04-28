#!/usr/bin/env bash
#
# FILE: scripts/deploy-notify-sentry.sh
# SCOPO: Notifica a Sentry di un nuovo rilascio e associa i commit.
#
# COSA FA:
#   1. Crea una "release" in Sentry (versione = GIT SHA breve).
#   2. Associa automaticamente i commit del repository alla release.
#   3. Finalizza la release (la segna come "deployata adesso").
#
# QUANDO SI ESEGUE:
#   Dentro GitHub Actions deploy.yml (step W3), DOPO il deploy riuscito.
#   Mai eseguire in locale su main.
#
# VARIABILI RICHIESTE:
#   SENTRY_AUTH_TOKEN  — token con scope "project:releases"
#                         (https://sentry.io/settings/account/api/auth-tokens/)
#   SENTRY_ORG         — slug organizzazione (es. "spediamofacile")
#   SENTRY_PROJECT     — slug progetto (es. "laravel-backend")
#   SENTRY_ENVIRONMENT — "production" | "staging"
#   SENTRY_RELEASE     — opzionale, default = git rev-parse --short HEAD
#
# STRUMENTI:
#   sentry-cli (https://docs.sentry.io/cli/installation/)
#   Installazione rapida: curl -sL https://sentry.io/get-cli/ | bash
#
# USO LOCALE (manuale, se serve):
#   export SENTRY_AUTH_TOKEN=...
#   export SENTRY_ORG=... SENTRY_PROJECT=...
#   export SENTRY_ENVIRONMENT=staging
#   ./scripts/deploy-notify-sentry.sh

set -euo pipefail

# --- Guard: variabili obbligatorie ------------------------------------------
: "${SENTRY_AUTH_TOKEN:?SENTRY_AUTH_TOKEN mancante}"
: "${SENTRY_ORG:?SENTRY_ORG mancante}"
: "${SENTRY_PROJECT:?SENTRY_PROJECT mancante}"
: "${SENTRY_ENVIRONMENT:?SENTRY_ENVIRONMENT mancante (production|staging)}"

# --- Release version (GIT SHA breve come default) ---------------------------
RELEASE="${SENTRY_RELEASE:-$(git rev-parse --short HEAD)}"
echo "[sentry] Release: $RELEASE ($SENTRY_ENVIRONMENT)"

# --- Check sentry-cli installato -------------------------------------------
if ! command -v sentry-cli >/dev/null 2>&1; then
    echo "[sentry] sentry-cli non trovato. Installo..."
    curl -sL https://sentry.io/get-cli/ | bash
fi

# --- 1. Crea release ---------------------------------------------------------
sentry-cli releases new "$RELEASE" \
    --org "$SENTRY_ORG" \
    --project "$SENTRY_PROJECT"

# --- 2. Associa commit (auto-detect da git) ---------------------------------
# --auto: legge i commit dall'ultimo deploy al HEAD corrente
# --ignore-missing: non fallisce se i commit precedenti non esistono piu'
sentry-cli releases set-commits "$RELEASE" \
    --org "$SENTRY_ORG" \
    --project "$SENTRY_PROJECT" \
    --auto \
    --ignore-missing

# --- 3. Finalizza (segna come deployata) ------------------------------------
sentry-cli releases finalize "$RELEASE" \
    --org "$SENTRY_ORG" \
    --project "$SENTRY_PROJECT"

# --- 4. Tag deploy per environment ------------------------------------------
sentry-cli releases deploys "$RELEASE" new \
    --env "$SENTRY_ENVIRONMENT" \
    --org "$SENTRY_ORG" \
    --project "$SENTRY_PROJECT"

echo "[sentry] OK — release $RELEASE notificata su $SENTRY_ENVIRONMENT"
