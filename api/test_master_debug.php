<?php
// Teste minimalista
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');

echo "INICIANDO DEBUG\n";
flush();

try {
    echo "Incluindo test_master.php...\n";
    include_once __DIR__ . '/test_master.php';
    echo "FIM DO ARQUIVO\n";
} catch (Throwable $e) {
    echo "ERRO CAPTURADO: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
                                                                                                                       
?>