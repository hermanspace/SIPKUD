#!/usr/bin/env bash
#
# SIPKUD - Backup database PostgreSQL harian
#
# Menjalankan pg_dump di dalam container postgres-db (infrastruktur server),
# menyimpan hasil terkompresi ke BACKUP_DIR, memverifikasi hasil dump,
# dan menghapus backup yang lebih tua dari RETENTION_DAYS.
#
# Pemakaian:
#   ./scripts/backup-db.sh                  # pakai nilai default / .env
#   BACKUP_DIR=/mnt/backup ./scripts/backup-db.sh
#
# Jadwalkan via cron di host (bukan di dalam container aplikasi):
#   0 1 * * * /var/www/sipkud/scripts/backup-db.sh >> /var/log/sipkud-backup.log 2>&1
#
# Lihat docs/BACKUP_RESTORE.md untuk prosedur restore.

set -euo pipefail

# ---------------------------------------------------------------------------
# Konfigurasi (bisa dioverride via environment)
# ---------------------------------------------------------------------------
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ENV_FILE:-$PROJECT_DIR/.env}"

# Baca kredensial DB dari .env aplikasi jika tersedia
if [[ -f "$ENV_FILE" ]]; then
    DB_DATABASE_ENV=$(grep -E '^DB_DATABASE=' "$ENV_FILE" | tail -1 | cut -d= -f2- | tr -d '"' || true)
    DB_USERNAME_ENV=$(grep -E '^DB_USERNAME=' "$ENV_FILE" | tail -1 | cut -d= -f2- | tr -d '"' || true)
fi

DB_CONTAINER="${DB_CONTAINER:-postgres-db}"
DB_DATABASE="${DB_DATABASE:-${DB_DATABASE_ENV:-SIPKUDDB}}"
DB_USERNAME="${DB_USERNAME:-${DB_USERNAME_ENV:-postgres}}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/sipkud}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"

TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_FILE="$BACKUP_DIR/sipkud-$DB_DATABASE-$TIMESTAMP.dump"

# ---------------------------------------------------------------------------
# Backup
# ---------------------------------------------------------------------------
mkdir -p "$BACKUP_DIR"

echo "[$(date '+%F %T')] Mulai backup database '$DB_DATABASE' dari container '$DB_CONTAINER'..."

# Format custom (-Fc): terkompresi dan mendukung pg_restore selektif
docker exec "$DB_CONTAINER" pg_dump \
    -U "$DB_USERNAME" \
    -d "$DB_DATABASE" \
    --format=custom \
    --no-owner \
    > "$BACKUP_FILE"

# ---------------------------------------------------------------------------
# Verifikasi: file tidak kosong dan bisa dibaca pg_restore
# ---------------------------------------------------------------------------
if [[ ! -s "$BACKUP_FILE" ]]; then
    echo "[$(date '+%F %T')] ERROR: file backup kosong: $BACKUP_FILE" >&2
    rm -f "$BACKUP_FILE"
    exit 1
fi

if ! docker exec -i "$DB_CONTAINER" pg_restore --list < "$BACKUP_FILE" > /dev/null 2>&1; then
    echo "[$(date '+%F %T')] ERROR: file backup korup (pg_restore --list gagal): $BACKUP_FILE" >&2
    exit 1
fi

SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
echo "[$(date '+%F %T')] Backup berhasil: $BACKUP_FILE ($SIZE)"

# ---------------------------------------------------------------------------
# Retensi: hapus backup lebih tua dari RETENTION_DAYS
# ---------------------------------------------------------------------------
DELETED=$(find "$BACKUP_DIR" -name 'sipkud-*.dump' -type f -mtime "+$RETENTION_DAYS" -print -delete | wc -l)
if [[ "$DELETED" -gt 0 ]]; then
    echo "[$(date '+%F %T')] Retensi: $DELETED backup lama (> $RETENTION_DAYS hari) dihapus."
fi

echo "[$(date '+%F %T')] Selesai."
