#!/bin/bash
ssh -o StrictHostKeyChecking=no root@146.190.244.133 "
php -r \"
require '/var/www/viabix/api/config.php';
echo 'Banco de dados: ' . DB_NAME . PHP_EOL;
try {
    \$stmt = \$pdo->query('SELECT COUNT(*) as cnt FROM anvis');
    \$cnt = \$stmt->fetch()['cnt'];
    echo 'Total de ANVIs: ' . \$cnt . PHP_EOL;
} catch (Exception \$e) {
    echo 'ERRO: ' . \$e->getMessage() . PHP_EOL;
}
\"
"
