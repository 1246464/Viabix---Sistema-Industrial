<!DOCTYPE html>
<html>
<head><title>Aguarde...</title></head>
<body style="text-align:center;padding:50px">
<h1>Iniciando deploy...</h1>
<p>Por favor aguarde...</p>
<?php
// Auto-execute git pull
@mkdir('/tmp/deploy');
$lock_file = '/tmp/deploy/lock_' . time();
file_put_contents($lock_file, 'deploying');

chdir('/var/www/html');

// Tentarpull com timeout
$output = [];
$return_code = 0;
@exec('timeout 30 git pull origin main 2>&1', $output, $return_code);

// Exibir resultado
$success = ($return_code === 0);
echo '<script>';
echo 'setTimeout(function(){ window.location.href="/"; }, 3000);';
echo '</script>';
echo '<p style="color:' . ($success ? 'green' : 'red') . '">';
echo $success ? 'Deploy realizado!' : 'Deploy falhou!';
echo '</p>';
echo '<pre>';
echo implode("\n", $output);
echo '</pre>';

@unlink($lock_file);
?>
</body>
</html>
