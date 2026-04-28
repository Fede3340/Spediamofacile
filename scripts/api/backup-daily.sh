#!/bin/bash
# =============================================================================
# backup-daily.sh — Sprint 7.5
# =============================================================================
# Dump Postgres giornaliero compresso con upload su Backblaze B2 e retention 30gg.
# Eseguito da Render cron (03:00 UTC). Log su stdout → Render Logs.
#
# Variabili richieste (impostate da render.yaml):
#   DATABASE_URL              connection string Postgres
#   B2_APPLICATION_KEY_ID     key ID Backblaze
#   B2_APPLICATION_KEY        application key Backblaze
#   B2_BUCKET                 nome bucket (default: spedizionefacile-backups)
#   RETENTION_DAYS            giorni di retention (default: 30)
#
# Rollback: disabilitare il servizio cron su Render (Dashboard → Suspend).
# =============================================================================

set -euo pipefail

BUCKET="${B2_BUCKET:-spedizionefacile-backups}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"
TIMESTAMP="$(date -u +%Y%m%d-%H%M%S)"
BACKUP_DIR="/tmp/db-backup"
BACKUP_FILE="backup-${TIMESTAMP}.sql.gz"
BACKUP_PATH="${BACKUP_DIR}/${BACKUP_FILE}"
REMOTE_PATH="daily/${BACKUP_FILE}"

log() { echo "[backup-daily] $(date -u +%Y-%m-%dT%H:%M:%SZ) $*"; }

cleanup() { rm -rf "${BACKUP_DIR}" || true; }
trap cleanup EXIT

# --- 0. Verifica variabili -----------------------------------------------------
: "${DATABASE_URL:?DATABASE_URL mancante}"
: "${B2_APPLICATION_KEY_ID:?B2_APPLICATION_KEY_ID mancante}"
: "${B2_APPLICATION_KEY:?B2_APPLICATION_KEY mancante}"

mkdir -p "${BACKUP_DIR}"
log "Avvio backup → ${BACKUP_FILE}"

# --- 1. Dump + gzip ------------------------------------------------------------
# --no-owner / --no-acl rendono il dump portabile su DB di restore differenti.
# --clean + --if-exists consentono restore idempotente.
pg_dump "${DATABASE_URL}" \
  --no-owner \
  --no-acl \
  --clean \
  --if-exists \
  --format=plain \
  | gzip -9 > "${BACKUP_PATH}"

SIZE_BYTES="$(stat -c%s "${BACKUP_PATH}" 2>/dev/null || stat -f%z "${BACKUP_PATH}")"
log "Dump completato: ${SIZE_BYTES} bytes"

# Guard: un dump inspiegabilmente vuoto (< 1 KB) e' probabilmente un fallimento silenzioso.
if [ "${SIZE_BYTES}" -lt 1024 ]; then
  log "ERRORE: backup troppo piccolo (${SIZE_BYTES} bytes), abort"
  exit 1
fi

# --- 2. Autenticazione B2 ------------------------------------------------------
# b2sdk CLI: installato via pip nel buildCommand del cron service.
b2 account authorize "${B2_APPLICATION_KEY_ID}" "${B2_APPLICATION_KEY}" > /dev/null
log "B2 autenticato"

# --- 3. Upload -----------------------------------------------------------------
b2 file upload "${BUCKET}" "${BACKUP_PATH}" "${REMOTE_PATH}" > /dev/null
log "Upload OK → b2://${BUCKET}/${REMOTE_PATH}"

# --- 4. Retention: elimina file > RETENTION_DAYS -------------------------------
# Elenca i file, estrae timestamp dal nome, confronta con cutoff.
CUTOFF_DATE="$(date -u -d "${RETENTION_DAYS} days ago" +%Y%m%d 2>/dev/null \
  || date -u -v-"${RETENTION_DAYS}"d +%Y%m%d)"
log "Retention: elimino file prima di ${CUTOFF_DATE}"

DELETED=0
b2 ls "b2://${BUCKET}/daily/" 2>/dev/null | while read -r LINE; do
  FNAME="$(echo "${LINE}" | awk '{print $NF}')"
  # Nome atteso: backup-YYYYMMDD-HHMMSS.sql.gz → estraggo YYYYMMDD
  DATE_PART="$(echo "${FNAME}" | sed -n 's/.*backup-\([0-9]\{8\}\)-.*/\1/p')"
  [ -z "${DATE_PART}" ] && continue
  if [ "${DATE_PART}" \< "${CUTOFF_DATE}" ]; then
    b2 rm "b2://${BUCKET}/daily/${FNAME}" > /dev/null 2>&1 && {
      log "Eliminato: ${FNAME}"
      DELETED=$((DELETED + 1))
    }
  fi
done

log "Backup completato. File eliminati: ${DELETED}"
