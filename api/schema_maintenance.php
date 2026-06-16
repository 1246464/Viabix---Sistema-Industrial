<?php
/**
 * Verificador e atualizador seguro da estrutura principal do banco.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

function viabixSchemaColumnSql(): array {
    return [
        'tenants' => [
            'id' => 'CHAR(36) NOT NULL PRIMARY KEY',
            'nome' => "VARCHAR(150) NOT NULL DEFAULT 'Empresa'",
            'slug' => 'VARCHAR(120) NULL',
            'ativo' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ],
        'usuarios' => [
            'id' => 'CHAR(36) NOT NULL PRIMARY KEY',
            'tenant_id' => 'CHAR(36) NULL',
            'login' => 'VARCHAR(100) NULL',
            'email' => 'VARCHAR(150) NULL',
            'nome' => "VARCHAR(150) NOT NULL DEFAULT ''",
            'senha' => 'VARCHAR(255) NULL',
            'nivel' => "VARCHAR(40) NOT NULL DEFAULT 'usuario'",
            'ativo' => 'TINYINT(1) NOT NULL DEFAULT 1',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ],
        'anvis' => [
            'id' => 'VARCHAR(50) NOT NULL PRIMARY KEY',
            'tenant_id' => 'CHAR(36) NULL',
            'numero' => 'VARCHAR(50) NULL',
            'revisao' => 'VARCHAR(20) NULL',
            'cliente' => 'VARCHAR(255) NULL',
            'projeto' => 'VARCHAR(255) NULL',
            'produto' => 'VARCHAR(255) NULL',
            'volume_mensal' => 'INT NULL DEFAULT 0',
            'data_anvi' => 'DATE NULL',
            'status' => "VARCHAR(40) NOT NULL DEFAULT 'em-andamento'",
            'dados' => 'LONGTEXT NULL',
            'dados_financeiros' => 'LONGTEXT NULL',
            'projeto_id' => 'INT NULL',
            'versao' => 'INT NOT NULL DEFAULT 1',
            'hash_conteudo' => 'VARCHAR(64) NULL',
            'criado_por' => 'CHAR(36) NULL',
            'atualizado_por' => 'CHAR(36) NULL',
            'data_criacao' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'data_atualizacao' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ],
        'projetos' => [
            'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
            'tenant_id' => 'CHAR(36) NULL',
            'dados' => 'LONGTEXT NULL',
            'dados_financeiros_reais' => 'LONGTEXT NULL',
            'criado_por' => 'CHAR(36) NULL',
            'atualizado_por' => 'CHAR(36) NULL',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ],
        'logs_atividade' => [
            'id' => 'BIGINT AUTO_INCREMENT PRIMARY KEY',
            'tenant_id' => 'CHAR(36) NULL',
            'usuario_id' => 'CHAR(36) NULL',
            'acao' => 'VARCHAR(100) NOT NULL',
            'entidade' => 'VARCHAR(80) NULL',
            'entidade_id' => 'VARCHAR(80) NULL',
            'detalhes' => 'TEXT NULL',
            'ip_address' => 'VARCHAR(64) NULL',
            'user_agent' => 'TEXT NULL',
            'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        ],
    ];
}

function viabixSchemaTableSql(string $table, array $columns): string {
    return 'CREATE TABLE IF NOT EXISTS ' . $table . ' (' . PHP_EOL
        . '  ' . implode(',' . PHP_EOL . '  ', array_map(
            fn($name, $sql) => $name . ' ' . $sql,
            array_keys($columns),
            $columns
        ))
        . PHP_EOL . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
}

function viabixSchemaIndexExists(PDO $pdo, string $table, string $index): bool {
    if (!viabixHasTable($table)) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?
         LIMIT 1'
    );
    $stmt->execute([$table, $index]);
    return (bool) $stmt->fetchColumn();
}

function viabixSchemaAddIndex(PDO $pdo, string $table, string $index, string $columns, array &$report, bool $apply): void {
    if (!viabixHasTable($table) || viabixSchemaIndexExists($pdo, $table, $index)) {
        return;
    }

    $report['pendencias'][] = "{$table}: índice {$index}";
    if ($apply) {
        $pdo->exec("ALTER TABLE {$table} ADD INDEX {$index} ({$columns})");
        $report['corrigidos'][] = "{$table}: índice {$index} criado";
    }
}

function viabixRunSchemaMaintenance(PDO $pdo, bool $apply): array {
    $definitions = viabixSchemaColumnSql();
    $report = [
        'modo' => $apply ? 'corrigir' : 'verificar',
        'corrigidos' => [],
        'pendencias' => [],
        'sem_alteracao' => [],
        'avisos' => [],
    ];

    foreach ($definitions as $table => $columns) {
        if (!viabixHasTable($table)) {
            $report['pendencias'][] = "{$table}: tabela ausente";
            if ($apply) {
                $pdo->exec(viabixSchemaTableSql($table, $columns));
                $report['corrigidos'][] = "{$table}: tabela criada";
            }
            continue;
        }

        foreach ($columns as $column => $sql) {
            if (viabixHasColumn($table, $column)) {
                continue;
            }

            if (stripos($sql, 'PRIMARY KEY') !== false || stripos($sql, 'AUTO_INCREMENT') !== false) {
                $report['avisos'][] = "{$table}.{$column}: coluna estrutural principal ausente; revisar manualmente para não alterar chaves existentes.";
                continue;
            }

            $report['pendencias'][] = "{$table}.{$column}";
            if ($apply) {
                $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$sql}");
                $report['corrigidos'][] = "{$table}.{$column} adicionada";
            }
        }
    }

    viabixSchemaAddIndex($pdo, 'anvis', 'idx_anvis_tenant_numero_revisao', 'tenant_id, numero, revisao', $report, $apply);
    viabixSchemaAddIndex($pdo, 'anvis', 'idx_anvis_projeto_id', 'projeto_id', $report, $apply);
    viabixSchemaAddIndex($pdo, 'projetos', 'idx_projetos_tenant', 'tenant_id', $report, $apply);
    viabixSchemaAddIndex($pdo, 'logs_atividade', 'idx_logs_tenant_created', 'tenant_id, created_at', $report, $apply);

    if (!$report['pendencias'] && !$report['avisos']) {
        $report['sem_alteracao'][] = 'Banco já está no padrão principal esperado.';
    }

    return $report;
}

try {
    $user = viabixRequireAuthenticatedSession();
    $method = $_SERVER['REQUEST_METHOD'];
    $apply = $method === 'POST' && (($_POST['apply'] ?? $_GET['apply'] ?? '') === '1');

    if ($apply && viabixNormalizeUserLevel($user['nivel'] ?? null) !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Apenas administradores podem corrigir a estrutura do banco.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $report = viabixRunSchemaMaintenance($pdo, $apply);

    if ($apply) {
        viabixLogActivity(
            $user['id'],
            'padronizar_banco',
            'Executou atualização segura do banco: ' . count($report['corrigidos']) . ' correção(ões).',
            'schema',
            null
        );
    }

    echo json_encode([
        'success' => true,
        'generated_at' => date('c'),
        'report' => $report,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    $errorId = viabixGenerateErrorId('schema');
    logError('Erro na padronização do banco', [
        'error_id' => $errorId,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => viabixPublicErrorMessage($e),
        'error_id' => $errorId,
    ], JSON_UNESCAPED_UNICODE);
}
