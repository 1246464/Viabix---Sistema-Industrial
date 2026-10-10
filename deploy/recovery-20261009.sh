#!/usr/bin/env bash
# Initial provisioning of the newly created Ubuntu 24.04 Droplet only.
set -euo pipefail
umask 027
APP=/var/www/viabix
test -f "$APP/index.html"
test ! -e "$APP/.env" || { echo 'Environment already exists; refusing to replace credentials.'; exit 1; }
install -d -m 750 -o www-data -g www-data "$APP/logs" "$APP/uploads" /var/log/viabix
a2enmod proxy_fcgi setenvif rewrite headers ssl
a2enconf php8.3-fpm
install -m 644 "$APP/deploy/recovery-apache.conf" /etc/apache2/sites-available/viabix.conf
a2dissite 000-default
a2ensite viabix
install -m 644 "$APP/deploy/recovery-php.ini" /etc/php/8.3/fpm/conf.d/99-viabix.ini
sed -i 's/^pm.max_children = .*/pm.max_children = 5/; s/^pm.start_servers = .*/pm.start_servers = 1/; s/^pm.min_spare_servers = .*/pm.min_spare_servers = 1/; s/^pm.max_spare_servers = .*/pm.max_spare_servers = 2/' /etc/php/8.3/fpm/pool.d/www.conf
php "$APP/scripts/db_homolog_migrate.php" --host=localhost --user=root --db=viabix_staging
mysql -e 'CREATE DATABASE viabix_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
mysqldump --no-tablespaces --single-transaction viabix_staging | mysql viabix_db
php "$APP/deploy/recovery-env.php"
chown root:www-data "$APP/.env"
chmod 640 "$APP/.env"
apache2ctl configtest
systemctl restart php8.3-fpm apache2
ufw allow OpenSSH
ufw allow 'Apache Full'
ufw --force enable
echo 'Initial configuration complete.'
