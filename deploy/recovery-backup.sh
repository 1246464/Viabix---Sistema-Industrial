#!/usr/bin/env bash
set -euo pipefail
umask 077
backup_root=/var/backups/viabix
mkdir -p "$backup_root/database" "$backup_root/app"
stamp=$(date -u +%Y%m%d_%H%M%S)
target="$backup_root/database/viabix_db_$stamp.sql.gz"
mysqldump --no-tablespaces --single-transaction --routines --triggers viabix_db | gzip > "$target.partial"
gzip -t "$target.partial"
mv "$target.partial" "$target"
tar -czf "$backup_root/app/viabix_$stamp.tar.gz" --exclude=logs -C /var/www/viabix .
find "$backup_root/database" -maxdepth 1 -type f -name 'viabix_db_*.sql.gz' -mtime +14 -delete
find "$backup_root/app" -maxdepth 1 -type f -name 'viabix_*.tar.gz' -mtime +14 -delete
echo "Backup completed at $stamp UTC"
