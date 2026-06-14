#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/viabix}"
ENV_FILE="${ENV_FILE:-$APP_DIR/.env}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/viabix}"
RETENTION_DAYS="${RETENTION_DAYS:-14}"
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Arquivo de ambiente não encontrado: $ENV_FILE" >&2
  exit 1
fi

set -a
source "$ENV_FILE"
set +a

: "${DB_HOST:?DB_HOST não definido}"
: "${DB_NAME:?DB_NAME não definido}"
: "${DB_USER:?DB_USER não definido}"
: "${DB_PASS:?DB_PASS não definido}"
DB_PORT="${DB_PORT:-3306}"
DB_SSL_MODE="${DB_SSL_MODE:-}"

mkdir -p "$BACKUP_DIR/database"
mkdir -p "$BACKUP_DIR/app"

DB_BACKUP_FILE="$BACKUP_DIR/database/${DB_NAME}_${TIMESTAMP}.sql.gz"
APP_BACKUP_FILE="$BACKUP_DIR/app/viabix_files_${TIMESTAMP}.tar.gz"

MYSQLDUMP_ARGS=(
  --host="$DB_HOST" \
  --port="$DB_PORT" \
  --user="$DB_USER" \
  --password="$DB_PASS" \
  --single-transaction \
  --quick \
  --routines \
  --triggers
)

if [[ -n "$DB_SSL_MODE" ]]; then
  MYSQLDUMP_ARGS+=(--ssl-mode="$DB_SSL_MODE")
fi

echo "Iniciando backup do banco $DB_NAME em $DB_HOST:$DB_PORT..."
if ! mysqldump "${MYSQLDUMP_ARGS[@]}" "$DB_NAME" | gzip > "$DB_BACKUP_FILE"; then
  rm -f "$DB_BACKUP_FILE"
  echo "Falha no backup do banco. Confira DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASS no $ENV_FILE." >&2
  exit 2
fi

tar \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='logs/*.log' \
  --exclude='BD/*.sql' \
  -czf "$APP_BACKUP_FILE" \
  -C "$APP_DIR" .

find "$BACKUP_DIR/database" -type f -mtime +"$RETENTION_DAYS" -delete
find "$BACKUP_DIR/app" -type f -mtime +"$RETENTION_DAYS" -delete

echo "Backup concluído:"
echo "- Banco: $DB_BACKUP_FILE"
echo "- Arquivos: $APP_BACKUP_FILE"
