#!/bin/bash
# =============================================================================
# restore-from-backup.sh — Sprint 7.5
# =============================================================================
# Ripristina un backup da Backblaze B2 nel database puntato da DATABASE_URL.
#
# Usage:
#   ./restore-from-backup.sh backup-20260417-030000.sql.gz
#
# ATTENZIONE: sovrascrive il DB di destinazione. Non eseguire su produzione
# senza conferma esplicita. Per un test sicuro puntare DATABASE_URL a un DB
# di staging vuoto (vedi docs/DEPLOY.md → "Test restore staging").
#
# Variabili richieste:
#   DATABASE_URL              DB di destinazione (staging, non produzione!)
#   B2_APPLICATION_KEY_ID
#   B2_APPLICATION_KEY
#   B2_BUCKET                 default: spedizionefacile-backups
# =============================================================================

set -euo pipefail

if [ "$#" -ne 1 ]; then
  echo "Usage: $0 <backup-file.sql.gz>" >&2
  echo "Es.:   $0 backup-20260417-030000.sql.gz" >&2
  exit 2
fi

BACKUP_FILE="$1"
BUCKET="${B2_BUCKET:-spedizionefacile-backups}"
TMP_FILE="/tmp/restore-$$.sql.gz"

: "${DATABASE_URL:?DATABASE_URL mancante}"
: "${B2_APPLICATION_KEY_ID:?B2_APPLICATION_KEY_ID mancante}"
: "${B2_APPLICATION_KEY:?B2_APPLICATION_KEY mancante}"

log() { echo "[restore] $(date -u +%Y-%m-%dT%H:%M:%SZ) $*"; }
cleanup() { rm -f "${TMP_FILE}"; }
trap cleanup EXIT

# --- Salvaguardia produzione ---------------------------------------------------
# Rifiuta esplicitamente se DATABASE_URL sembra puntare al DB di produzione
# (hostname contiene "spedizionefacile-db"). Override con FORCE_RESTORE=1.
if [[ "${DATABASE_URL}" == *"spedizionefacile-db"* ]] && [ "${FORCE_RESTORE:-0}" != "1" ]; then
  log "ERRORE: DATABASE_URL punta al DB di produzione."
  log "Per forzare (sconsigliato!): FORCE_RESTORE=1 $0 ${BACKUP_FILE}"
  exit 1
fi

log "Download b2://${BUCKET}/daily/${BACKUP_FILE}"
b2 account authorize "${B2_APPLICATION_KEY_ID}" "${B2_APPLICATION_KEY}" > /dev/null
b2 file download "b2://${BUCKET}/daily/${BACKUP_FILE}" "${TMP_FILE}" > /dev/null

log "Restore in corso (puo' richiedere alcuni minuti)..."
gunzip -c "${TMP_FILE}" | psql "${DATABASE_URL}" --quiet --set ON_ERROR_STOP=1

log "Restore completato da ${BACKUP_FILE}"
log "Verificare: psql \"\$DATABASE_URL\" -c '\\dt' && conteggio righe tabelle chiave"
