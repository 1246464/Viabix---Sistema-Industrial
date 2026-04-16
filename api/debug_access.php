<?php
// Arquivo de debug - SEM includes, SEM config
header('Content-Type: text/plain');

echo "=== DEBUG ACESSO DIRETO ===\n\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";
echo "\n=== SUCCESS ===\n";
echo "Se você está vendo isto, o acesso direto funciona!\n";
?>