<?php
// Run via CLI against the temporary localhost-only validation server.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$cookie = '';
function requestCheck(string $path, ?array $data = null): array {
    global $cookie;
    $curl = curl_init('http://127.0.0.1:8081' . $path);
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>20,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json'], CURLOPT_COOKIE=>$cookie,
        CURLOPT_HEADERFUNCTION=>function($ch,$line) use (&$cookie) {
            if (preg_match('/^Set-Cookie:\s*(viabix_session=[^;]+)/i',$line,$m)) { $cookie=$m[1]; }
            return strlen($line);
        }]);
    if ($data !== null) {
        if (str_starts_with($path, '/Controle_de_projetos/')) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        } else { curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data)); }
    }
    $body=curl_exec($curl); $code=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
    $json=json_decode($body,true); curl_close($curl);
    if (!is_array($json)) { throw new RuntimeException("$path: HTTP $code, invalid JSON"); }
    return [$code,$json];
}
$suffix=bin2hex(random_bytes(4));
$password=bin2hex(random_bytes(16));
[$code,$session]=requestCheck('/api/check_session.php');
[$code,$signup]=requestCheck('/api/signup.php', [
    'company_name'=>'Deployment Validation '.$suffix,'contact_name'=>'Validation',
    'login'=>'validation.'.$suffix,'email'=>'validation.'.$suffix.'@example.invalid',
    'password'=>$password,'plan_code'=>'pro','_csrf_token'=>$session['csrf_token']??''
]);
if (empty($signup['success'])) { throw new RuntimeException('Signup failed: HTTP '.$code.' '.($signup['message']??'')); }
echo "Signup: OK (isolated staging database)\n";
$cookie='';
[$code,$session]=requestCheck('/api/check_session.php');
[$code,$login]=requestCheck('/api/login.php', ['login'=>'validation.'.$suffix,'senha'=>$password,'_csrf_token'=>$session['csrf_token']??'']);
if (empty($login['success'])) { throw new RuntimeException('Login failed: HTTP '.$code.' '.($login['message']??'')); }
echo "Login: OK\n";
foreach (['/api/check_session.php','/api/anvi.php','/api/estatisticas.php','/api/subscription_current.php'] as $path) {
    [$code,$json]=requestCheck($path);
    echo "$path: HTTP $code; success=".json_encode($json['success']??null)."\n";
    if ($code>=400 || isset($json['error']) || (($json['success']??true)===false)) { throw new RuntimeException($path.' failed: '.json_encode($json)); }
}
[$code,$session]=requestCheck('/api/check_session.php');
[$code,$projects]=requestCheck('/Controle_de_projetos/api_mysql.php', ['action'=>'getProjects','_csrf_token'=>$session['csrf_token']??'']);
if (empty($projects['success'])) { throw new RuntimeException('Projects failed: '.json_encode($projects)); }
echo "Projects: OK\n";
