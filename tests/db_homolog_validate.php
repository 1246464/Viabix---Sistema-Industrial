<?php
/**
 * Valida se o banco de homologacao tem o contrato minimo esperado.
 *
 * Uso:
 *   php tests/db_homolog_validate.php --db=viabix_homolog
 *   php tests/db_homolog_validate.php --host=127.0.0.1 --port=3306 --user=root --pass= --db=viabix_homolog
 */

require_once __DIR__ . '/../bootstrap_env.php';

function optionValue(array $argv, string $name, ?string $default = null): ?string
{
    $prefix = '--' . $name . '=';
    foreach ($argv as $arg) {
        if (str_starts_with($arg, $prefix)) {
            return substr($arg, strlen($prefix));
        }
    }

    return $default;
}

function connectHomologDatabase(string $dbName, array $argv): PDO
{
    $host = optionValue($argv, 'host', viabix_env('DB_HOST', viabix_env('MYSQL_HOST', '127.0.0.1')));
    $port = (int) optionValue($argv, 'port', viabix_env('DB_PORT', '3306'));
    $user = optionValue($argv, 'user', viabix_env('DB_USER', viabix_env('MYSQL_USER', 'root')));
    $passEnv = optionValue($argv, 'pass-env', null);
    $envPass = $passEnv ? getenv($passEnv) : false;
    $pass = $envPass !== false ? (string) $envPass : optionValue($argv, 'pass', viabix_env('DB_PASS', viabix_env('MYSQL_PASSWORD', '')));
    $charset = viabix_env('DB_CHARSET', 'utf8mb4');

    return new PDO(
        "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );
    $stmt->execute([$table]);
    return (bool) $stmt->fetchColumn();
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1'
    );
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}

function assertTrue(bool $condition, string $message, array &$errors): void
{
    if (!$condition) {
        $errors[] = $message;
    }
}

$dbName = optionValue($argv, 'db', viabix_env('HOMOLOG_DB_NAME', 'viabix_homolog'));
$errors = [];

try {
    $pdo = connectHomologDatabase($dbName, $argv);

    $requiredTables = [
        'plans',
        'tenants',
        'subscriptions',
        'subscription_events',
        'invoices',
        'payments',
        'webhook_events',
        'tenant_settings',
        'device_sessions',
        'usuarios',
        'lideres',
        'projetos',
        'anvis',
        'anvis_historico',
        'logs_atividade',
        'projeto_riscos',
        'projeto_etapas',
        'projeto_alocacoes',
        'projeto_historico_custos',
        'anvi_resumo_financeiro',
        'roles',
        'permissions',
        'role_permissions',
        'user_roles',
        'user_custom_permissions',
        'audit_logs',
        'security_events',
    ];

    foreach ($requiredTables as $table) {
        assertTrue(tableExists($pdo, $table), "Tabela ausente: {$table}", $errors);
    }

    $requiredColumns = [
        'plans' => ['codigo', 'limite_usuarios', 'limite_anvis_mensal', 'limite_projetos_ativos'],
        'tenants' => ['slug', 'status', 'trial_ate'],
        'subscriptions' => ['tenant_id', 'plan_id', 'status', 'trial_ate', 'fim_vigencia'],
        'usuarios' => ['tenant_id', 'login', 'email', 'senha', 'nivel', 'ativo'],
        'anvis' => ['tenant_id', 'numero', 'revisao', 'dados', 'dados_financeiros'],
        'projetos' => ['tenant_id', 'nome', 'status', 'dados', 'dados_financeiros_reais'],
        'invoices' => ['tenant_id', 'subscription_id', 'status', 'valor_total', 'url_cobranca'],
        'webhook_events' => ['provider', 'event_id', 'event_type', 'payload', 'processado'],
    ];

    foreach ($requiredColumns as $table => $columns) {
        foreach ($columns as $column) {
            assertTrue(columnExists($pdo, $table, $column), "Coluna ausente: {$table}.{$column}", $errors);
        }
    }

    $plans = $pdo->query(
        "SELECT codigo, limite_usuarios, limite_anvis_mensal, limite_projetos_ativos
         FROM plans
         WHERE status = 'ativo'
         ORDER BY codigo"
    )->fetchAll();

    $planCodes = array_column($plans, 'codigo');
    foreach (['starter', 'pro', 'enterprise'] as $code) {
        assertTrue(in_array($code, $planCodes, true), "Plano ativo ausente: {$code}", $errors);
    }

    $starter = null;
    foreach ($plans as $plan) {
        if ($plan['codigo'] === 'starter') {
            $starter = $plan;
            break;
        }
    }
    if ($starter) {
        assertTrue((int) $starter['limite_usuarios'] === 3, 'Starter deve limitar 3 usuarios.', $errors);
        assertTrue((int) $starter['limite_anvis_mensal'] === 30, 'Starter deve limitar 30 ANVIs mensais.', $errors);
        assertTrue((int) $starter['limite_projetos_ativos'] === 20, 'Starter deve limitar 20 projetos ativos.', $errors);
    }

    $views = ['v_projeto_resumo_riscos', 'v_projeto_progresso_etapas', 'v_projeto_alocacao_resumo', 'user_permissions'];
    foreach ($views as $view) {
        assertTrue(tableExists($pdo, $view), "View ausente: {$view}", $errors);
    }

    if ($errors) {
        echo "Falhou: banco {$dbName} ainda tem pendencias." . PHP_EOL;
        foreach ($errors as $error) {
            echo "- {$error}" . PHP_EOL;
        }
        exit(1);
    }

    echo "OK: banco {$dbName} pronto para homologacao basica." . PHP_EOL;
    echo 'Planos ativos: ' . implode(', ', $planCodes) . PHP_EOL;

    $countTables = ['tenants', 'usuarios', 'subscriptions', 'invoices', 'anvis', 'projetos', 'webhook_events'];
    foreach ($countTables as $table) {
        if (tableExists($pdo, $table)) {
            $count = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            echo "{$table}: {$count}" . PHP_EOL;
        }
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro ao validar banco de homologacao: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
