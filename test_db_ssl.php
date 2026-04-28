<?php
$host = 'db-mysql-tor1-97153-do-user-35989295-0.h.db.ondigitalocean.com';
$user = 'doadmin';
$pass = 'YOUR_DATABASE_PASSWORD_HERE';
$db = 'viabix_db';

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    $pdo = new PDO(
        'mysql:host=' . $host . ';port=3306;dbname=' . $db,
        $user,
        $pass,
        $options
    );
    echo json_encode(['success' => true, 'message' => 'Conectado com sucesso ao MySQL']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
