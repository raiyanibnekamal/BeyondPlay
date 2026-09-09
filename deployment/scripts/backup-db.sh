#!/usr/bin/env bash
# Arena MySQL backup — credentials from environment (never hardcode).
# Usage: export DB_HOST DB_USER DB_PASSWORD DB_NAME BACKUP_DIR; ./backup-db.sh

set -euo pipefail

DB_HOST="${DB_HOST:-127.0.0.1}"
DB_USER="${DB_USER:?Set DB_USER}"
DB_PASSWORD="${DB_PASSWORD:?Set DB_PASSWORD}"
DB_NAME="${DB_NAME:-tournament_db}"
BACKUP_DIR="${BACKUP_DIR:-./backups}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"

mkdir -p "$BACKUP_DIR"
STAMP="$(date +%Y-%m-%d_%H-%M)"
FILE="$BACKUP_DIR/arena_${STAMP}.sql.gz"

export MYSQL_PWD="$DB_PASSWORD"
mysqldump -h "$DB_HOST" -u "$DB_USER" --single-transaction --routines --triggers "$DB_NAME" | gzip -9 > "$FILE"
unset MYSQL_PWD

find "$BACKUP_DIR" -name 'arena_*.sql.gz' -type f -mtime +"$RETENTION_DAYS" -delete

echo "Backup written: $FILE"
