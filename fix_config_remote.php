<?php
// Script para corrigir config.php em produção

$file = '/var/www/viabix/api/config.php';

echo "Lendo $file...\n";
$content = file_get_contents($file);

// Criar backup first
copy($file, $file . '.backup.' . time());
echo "Backup criado\n";

// Remove lines with PDO::MYSQL_ATTR_SSL_MODE
echo "\nRemovendo linhas problemáticas...\n";

// Pattern: if (defined('PDO::MYSQL_ATTR_SSL_MODE')) {
//             $options[PDO::MYSQL_ATTR_SSL_MODE] = PDO::MYSQL_ATTR_SSL_PREFERRED;
//         }

$lines = explode("\n", $content);
$fixed_lines = [];
$skip_until_close = false;

foreach ($lines as $line) {
    // Detectar início do bloco problemático
    if (strpos($line, 'if (defined(\'PDO::MYSQL_ATTR_SSL_MODE\')') !== false) {
        $skip_until_close = true;
        echo "  Removendo: " . trim($line) . "\n";
        continue;
    }
    
    // Se já estamos no bloco problemático, pular até encontrar o }
    if ($skip_until_close) {
        if (strpos($line, '}') !== false && trim($line) === '}') {
            echo "  Removendo: " . trim($line) . "\n";
            $skip_until_close = false;
        } else {
            echo "  Removendo: " . trim($line) . "\n";
        }
        continue;
    }
    
    $fixed_lines[] = $line;
}

$fixed_content = implode("\n", $fixed_lines);

echo "\nEscrevendo arquivo corrigido...\n";
file_put_contents($file, $fixed_content);
echo "✓ config.php corrigido\n";

// Testar
echo "\nTestando require do config.php...\n";
try {
    require_once($file);
    echo "✓ Config carregado com sucesso!\n";
} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✓ Tudo pronto!\n";
