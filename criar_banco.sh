#!/bin/bash

# Criar banco de dados
mysql -u root -p59380204Mm -e "CREATE DATABASE IF NOT EXISTS viabix_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Executar script SQL
mysql -u root -p59380204Mm viabix_db < /var/www/viabix/BD/viabix_saas_multitenant.sql

echo "✅ Banco de dados e tabelas criados!"
