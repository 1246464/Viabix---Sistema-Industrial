<?php
require_once 'api/config.php';

$user = viabixFindUserForAuth('admin');

echo "=== USER LOOKUP TEST ===\n";
if ($user) {
    echo "✓ User 'admin' found!\n";
    echo "  ID: " . $user['id'] . "\n";
    echo "  Login: " . $user['login'] . "\n";
    echo "  Nome: " . ($user['nome'] ?? 'N/A') . "\n";
    echo "  Ativo: " . ($user['ativo'] ?? 'N/A') . "\n";
    echo "  Nivel: " . ($user['nivel'] ?? 'N/A') . "\n";
    echo "  Tenant ID: " . ($user['tenant_id'] ?? 'N/A') . "\n";
    echo "  Email: " . ($user['email'] ?? 'N/A') . "\n";
} else {
    echo "✗ User 'admin' NOT found\n";
}
