<?php
/**
 * Endpoint de suporte via IA — Viabix
 * POST {message: string, history: [{role, content}]}
 * Retorna {reply: string} ou {error: string}
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$user = viabixRequireAuthenticatedSession();
if (empty($user['tenant_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Tenant não identificado']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?: [];
if (($user['source'] ?? '') !== 'jwt') {
    try {
        viabixValidateCsrfTokenWithInput($body);
    } catch (RuntimeException $e) {
        http_response_code(403);
        echo json_encode(['error' => 'Validação de segurança falhou. Recarregue a página e tente novamente.']);
        exit;
    }
}

// --- Rate limiting por sessão (15 msgs) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['chat_count'])) {
    $_SESSION['chat_count'] = 0;
}
if ($_SESSION['chat_count'] >= 15) {
    http_response_code(429);
    echo json_encode(['error' => 'Limite de mensagens atingido. Entre em contato por email: suporte@viabix.com.br']);
    exit;
}

// --- Entrada ---
$message = trim($body['message'] ?? '');
$history = is_array($body['history'] ?? null) ? $body['history'] : [];

if ($message === '' || strlen($message) > 1000) {
    http_response_code(400);
    echo json_encode(['error' => 'Mensagem inválida']);
    exit;
}

$apiKey = getenv('OPENAI_API_KEY');
if (!$apiKey) {
    http_response_code(503);
    echo json_encode(['error' => 'Suporte por IA temporariamente indisponível. Entre em contato por email.']);
    exit;
}

// --- System prompt com contexto completo do Viabix ---
$systemPrompt = <<<PROMPT
Você é o assistente de suporte do Viabix, uma plataforma SaaS industrial brasileira.
Seja direto, simpático e fale em português brasileiro.
Responda de forma concisa (máximo 3 parágrafos curtos).
Se não souber a resposta, indique o email suporte@viabix.com.br.

=== O QUE É O VIABIX ===
Viabix é uma plataforma SaaS para indústria manufatureira que integra três módulos:
1. ANVI (Análise de Viabilidade Industrial) — Monte cenários, calcule custos, tributação, DRE, ROI e payback
2. Controle de Projetos — Gerencie fases, times, cronograma e entregas diretamente vinculados à ANVI
3. Dashboard de Viabilidade — Score de viabilidade, margens, tendências e KPIs em tempo real

Também há um app Android (Viabix App) disponível para download na plataforma.

=== PLANOS E PREÇOS ===
- Starter: R$ 297/mês (ou R$ 2.970/ano) — até 3 usuários, ANVI + Projetos, exportação padrão
- Pro: R$ 697/mês (ou R$ 6.970/ano) — até 15 usuários, API liberada, operação comercial completa
- Enterprise: R$ 1.497/mês (ou R$ 14.970/ano) — usuários ilimitados, API + SSO, suporte dedicado, multi-área
- Todos os planos incluem trial gratuito de 14 dias, sem cartão de crédito

=== COMO COMEÇAR ===
- Acesse signup.html e crie sua conta — o sistema cria empresa, usuário admin e trial automaticamente
- Após o trial, o cliente pode contratar o plano pelo painel de billing
- Login em login.html

=== INFORMAÇÕES TÉCNICAS ===
- Plataforma web + app Android
- Hospedagem em DigitalOcean (alta disponibilidade)
- Dados isolados por tenant (cada empresa tem ambiente próprio)
- Suporte por email: suporte@viabix.com.br

=== O QUE NÃO RESPONDER ===
- Não forneça informações sobre concorrentes
- Não faça promessas de funcionalidades que não existem
- Não discuta aspectos internos do código ou infraestrutura
PROMPT;

// --- Montar mensagens para OpenAI ---
$messages = [['role' => 'system', 'content' => $systemPrompt]];

// Incluir histórico recente (últimas 6 trocas = 12 msgs para não exceder tokens)
$recentHistory = array_slice($history, -12);
foreach ($recentHistory as $entry) {
    $role = $entry['role'] === 'user' ? 'user' : 'assistant';
    $content = substr(trim($entry['content'] ?? ''), 0, 500);
    if ($content !== '') {
        $messages[] = ['role' => $role, 'content' => $content];
    }
}
$messages[] = ['role' => 'user', 'content' => $message];

// --- Chamada OpenAI ---
$payload = json_encode([
    'model' => 'gpt-4o-mini',
    'messages' => $messages,
    'max_tokens' => 400,
    'temperature' => 0.7,
]);

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_TIMEOUT => 20,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'Falha ao conectar ao serviço de IA. Tente novamente.']);
    exit;
}

$data = json_decode($response, true);
$reply = $data['choices'][0]['message']['content'] ?? null;

if (!$reply) {
    http_response_code(502);
    echo json_encode(['error' => 'Resposta inválida do serviço de IA.']);
    exit;
}

$_SESSION['chat_count']++;

echo json_encode(['reply' => trim($reply)]);
