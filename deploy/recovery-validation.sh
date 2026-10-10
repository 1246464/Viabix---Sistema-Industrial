#!/usr/bin/env bash
set -euo pipefail
mysql -e "GRANT ALL PRIVILEGES ON viabix_staging.* TO 'viabix_app'@'localhost'"
trap 'systemctl stop viabix-validation.service; mysql -e "REVOKE ALL PRIVILEGES ON viabix_staging.* FROM '\''viabix_app'\''@'\''localhost'\''"' EXIT
systemd-run --unit=viabix-validation --collect --property=User=www-data --setenv=DB_NAME=viabix_staging --setenv=REDIS_DB=2 /usr/bin/php -S 127.0.0.1:8081 -t /var/www/viabix
sleep 2
php /var/www/viabix/deploy/recovery-check.php
