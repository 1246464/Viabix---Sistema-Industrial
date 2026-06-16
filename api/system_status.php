<?php
/**
 * Diagnostico amigavel do sistema Viabix.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$startedAt = microtime(true);
$checks = [];
$summary = [
    'ok' => 0,
    'warning' => 0,
    'error' => 0,
];
$overallStatus = 'ok';

function viabixStatusAdd(array &$checks, array &$summary, string &$overallStatus, string $key, string $label, string $status, string $message, array $details = []): void {
    $checks[] = [
        'key' => $key,
        'label' => $label,
        'status' => $status,
        'message' => $message,
        'details' => $details,
    ];

    if (isset($summary[$status])) {
        $summary[$status]++;
    }

    if ($status === 'error') {
        $overallStatus = 'error';
        return;
    }

    if ($status === 'warning' && $overallStatus === 'ok') {
        $overallStatus = 'warning';
    }
}

function viabixStatusTableColumns(string $table, array $requiredColumns): array {
    $missing = [];

    if (!viabixHasTable($table)) {
        return $requiredColumns;
    }

    foreach ($requiredColumns as $column) {
        if (!viabixHasColumn($table, $column)) {
            $missing[] = $column;
        }
    }

    return $missing;
}

function viabixStatusCountRows(PDO $pdo, string $table, ?string $tenantId = null): ?int {
    if (!viabixHasTable($table)) {
        return null;
    }

    if ($tenantId && viabixHasColumn($table, 'tenant_id')) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
        return (int) $stmt->fetchColumn();
    }

    return (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

function viabixStatusFileReady(string $relativePath): bool {
    return is_file(dirname(__DIR__) . '/' . ltrim($relativePath, '/'));
}

function viabixStatusRecentErrors(int $limit = 8): array {
    $file = __DIR__ . '/../logs/error.log';
    if (!is_file($file) || !is_readable($file)) {
        return [];
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return [];
    }

    $items = [];
    foreach (array_reverse($lines) as $line) {
        $decoded = json_decode($line, true);
        if (is_array($decoded)) {
            $context = is_array($decoded['context'] ?? null) ? $decoded['context'] : [];
            $items[] = [
                'codigo' => $context['error_id'] ?? '-',
                'quando' => $decoded['timestamp'] ?? null,
                'mensagem' => $decoded['message'] ?? 'Erro registrado',
                'usuario_id' => $context['user_id'] ?? null,
                'tenant_id' => $context['tenant_id'] ?? null,
                'url' => $context['url'] ?? null,
            ];
        } else {
            $items[] = [
                'codigo' => '-',
                'quando' => null,
                'mensagem' => substr($line, 0, 180),
                'usuario_id' => null,
                'tenant_id' => null,
                'url' => null,
            ];
        }

        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

try {
    $user = viabixRequireAuthenticatedSession();
    $tenantId = $user['tenant_id'] ?? viabixCurrentTenantId();

    viabixStatusAdd($checks, $summary, $overallStatus, 'session', 'Sessao e login', 'ok', 'Login ativo e reconhecido pelo sistema.', [
        'usuario' => $user['nome'] ?: $user['login'],
        'nivel' => viabixNormalizeUserLevel($user['nivel'] ?? null),
        'tenant_id' => $tenantId,
    ]);

    $dbStartedAt = microtime(true);
    $pdo->query('SELECT 1')->fetchColumn();
    viabixStatusAdd($checks, $summary, $overallStatus, 'database', 'Banco de dados', 'ok', 'Banco respondeu corretamente.', [
        'latencia_ms' => round((microtime(true) - $dbStartedAt) * 1000, 2),
        'porta' => defined('DB_PORT') ? DB_PORT : null,
    ]);

    $requiredTables = [
        'tenants' => ['id', 'nome', 'ativo'],
        'usuarios' => ['id', 'tenant_id', 'login', 'nome', 'senha', 'nivel', 'ativo'],
        'anvis' => ['id', 'tenant_id', 'numero', 'revisao', 'cliente', 'projeto', 'produto', 'dados', 'projeto_id'],
        'projetos' => ['id', 'tenant_id', 'dados', 'created_at', 'updated_at'],
    ];
    $missingSchema = [];
    foreach ($requiredTables as $table => $columns) {
        if (!viabixHasTable($table)) {
            $missingSchema[$table] = ['tabela ausente'];
            continue;
        }

        $missingColumns = viabixStatusTableColumns($table, $columns);
        if ($missingColumns) {
            $missingSchema[$table] = $missingColumns;
        }
    }

    viabixStatusAdd(
        $checks,
        $summary,
        $overallStatus,
        'schema',
        'Estrutura principal',
        $missingSchema ? 'error' : 'ok',
        $missingSchema ? 'Existem tabelas ou campos essenciais faltando.' : 'Tabelas principais estao prontas.',
        ['pendencias' => $missingSchema]
    );

    $optionalTables = ['subscriptions', 'plans', 'logs_atividade'];
    $missingOptional = [];
    foreach ($optionalTables as $table) {
        if (!viabixHasTable($table)) {
            $missingOptional[] = $table;
        }
    }

    viabixStatusAdd(
        $checks,
        $summary,
        $overallStatus,
        'saas',
        'Base SaaS',
        $missingOptional ? 'warning' : 'ok',
        $missingOptional ? 'Algumas tabelas SaaS opcionais nao foram encontradas.' : 'Estrutura SaaS encontrada.',
        ['tabelas_ausentes' => $missingOptional]
    );

    $anviRows = viabixStatusCountRows($pdo, 'anvis', $tenantId);
    $anviMissingHelpful = viabixStatusTableColumns('anvis', ['dados', 'dados_financeiros', 'projeto_id']);
    viabixStatusAdd(
        $checks,
        $summary,
        $overallStatus,
        'anvi',
        'Modulo ANVI',
        viabixHasTable('anvis') ? ($anviMissingHelpful ? 'warning' : 'ok') : 'error',
        viabixHasTable('anvis')
            ? ($anviMissingHelpful ? 'ANVI funciona, mas alguns campos de integracao podem limitar recursos.' : 'ANVI pronto para carregar e salvar.')
            : 'Tabela de ANVI nao encontrada.',
        [
            'registros' => $anviRows,
            'campos_recomendados_ausentes' => $anviMissingHelpful,
            'api' => viabixStatusFileReady('api/anvi.php'),
        ]
    );

    $projectRows = viabixStatusCountRows($pdo, 'projetos', $tenantId);
    $projectMissingHelpful = viabixStatusTableColumns('projetos', ['created_at', 'updated_at', 'tenant_id']);
    viabixStatusAdd(
        $checks,
        $summary,
        $overallStatus,
        'projects',
        'Controle de Projetos',
        viabixHasTable('projetos') ? ($projectMissingHelpful ? 'warning' : 'ok') : 'error',
        viabixHasTable('projetos')
            ? ($projectMissingHelpful ? 'Projetos estao disponiveis, com campos recomendados pendentes.' : 'Projetos prontos para carregar e salvar.')
            : 'Tabela de projetos nao encontrada.',
        [
            'registros' => $projectRows,
            'campos_recomendados_ausentes' => $projectMissingHelpful,
            'api' => viabixStatusFileReady('Controle_de_projetos/api_mysql.php'),
        ]
    );

    $dashboardReady = viabixStatusFileReady('dashboard_viabilidade.html')
        && viabixStatusFileReady('api/dashboard_viabilidade_simple.php')
        && viabixHasTable('anvis');
    viabixStatusAdd(
        $checks,
        $summary,
        $overallStatus,
        'viability_dashboard',
        'Dashboard de Viabilidade',
        $dashboardReady ? 'ok' : 'error',
        $dashboardReady ? 'Dashboard pronto para analisar ANVI e projeto vinculado.' : 'Falta arquivo ou tabela necessaria para o dashboard.',
        [
            'pagina' => viabixStatusFileReady('dashboard_viabilidade.html'),
            'api' => viabixStatusFileReady('api/dashboard_viabilidade_simple.php'),
            'tabela_anvis' => viabixHasTable('anvis'),
            'tabela_projetos' => viabixHasTable('projetos'),
        ]
    );

    $logsPath = realpath(__DIR__ . '/../logs') ?: (__DIR__ . '/../logs');
    $recentErrors = viabixStatusRecentErrors();
    viabixStatusAdd(
        $checks,
        $summary,
        $overallStatus,
        'logs',
        'Logs do sistema',
        (is_dir($logsPath) && is_writable($logsPath)) ? 'ok' : 'warning',
        (is_dir($logsPath) && is_writable($logsPath)) ? 'Pasta de logs pronta para registrar erros.' : 'A pasta de logs precisa existir e aceitar escrita.',
        [
            'pasta' => $logsPath,
            'existe' => is_dir($logsPath),
            'gravavel' => is_dir($logsPath) && is_writable($logsPath),
            'erros_recentes' => count($recentErrors),
        ]
    );

    $recommendations = [];
    foreach ($checks as $check) {
        if ($check['status'] !== 'ok') {
            $recommendations[] = $check['label'] . ': ' . $check['message'];
        }
    }

    echo json_encode([
        'success' => true,
        'status' => $overallStatus,
        'summary' => $summary,
        'generated_at' => date('c'),
        'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        'checks' => $checks,
        'recommendations' => $recommendations,
        'recent_errors' => $recentErrors,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    $errorId = viabixGenerateErrorId('diag');
    logError('Falha ao executar diagnóstico', [
        'error_id' => $errorId,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Nao foi possivel executar o diagnostico.',
        'error_id' => $errorId,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
