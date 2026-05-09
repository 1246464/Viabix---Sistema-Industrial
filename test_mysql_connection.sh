#!/bin/bash
# Check MySQL socket location and connectivity

echo "=== MySQL Socket Locations ==="
find /var/run -name "mysqld.sock" 2>/dev/null | head -5

echo -e "\n=== MySQL Process ==="
ps aux | grep mysqld | grep -v grep

echo -e "\n=== MySQL Config ==="
mysql_config_editor print 2>/dev/null || echo "No config editor settings"

echo -e "\n=== Try connecting as root with no password ==="
mysql -u root -h localhost -e "SELECT VERSION();" 2>&1 | head -3

echo -e "\n=== Try connecting via socket ==="
mysql -u root -S /var/run/mysqld/mysqld.sock -e "SELECT VERSION();" 2>&1 | head -3

echo -e "\n=== Check MySQL users ==="
mysql -u root -e "SELECT user, host, plugin FROM mysql.user;" 2>&1 | head -20
