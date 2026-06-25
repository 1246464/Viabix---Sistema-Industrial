<?php
/**
 * Monta um banco limpo de homologacao usando os SQLs versionados do projeto.
 *
 * Uso:
 *   php scripts/db_homolog_migrate.php --db=viabix_homolog --fresh --seed-demo
 *   php scripts/db_homolog_migrate.php --host=127.0.0.1 --port=3306 --user=root --pass= --db=viabix_homolog --fresh
 */

require_once __DIR__ . '/../bootstrap_env.php';

function usageAndExit(int $code = 1): void
{
    $message = <<<TXT
Uso:
  php scripts/db_homolog_migrate.php --db=viabix_homolog [--fresh] [--seed-demo]

Opcoes:
  --db=NOME        Banco alvo. Deve parecer homologacao/teste/staging.
  --host=HOST      Host do MySQL. Padrao: DB_HOST do ambiente.
  --port=PORTA     Porta do MySQL. Padrao: DB_PORT do ambiente.
  --user=USUARIO   Usuario do MySQL. Padrao: DB_USER do ambiente.
  --pass=SENHA     Senha do MySQL. Padrao: DB_PASS do ambiente.
  --pass-env=NOME  Le a senha de uma variavel de ambiente, evitando expo-la no comando.
  --fresh          Recria o banco alvo do zero. Nunca permitido para nomes de producao.
  --seed-demo      Importa dados comerciais de demonstracao.

TXT;
    fwrite($code === 0 ? STDOUT : STDERR, $message);
    exit($code);
}

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

function hasFlag(array $argv, string $name): bool
{
    return in_array('--' . $name, $argv, true);
}

function assertSafeDatabaseName(string $dbName): void
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
        throw new RuntimeException('Nome do banco invalido. Use apenas letras, numeros e underscore.');
    }

    $reserved = ['mysql', 'information_schema', 'performance_schema', 'sys', 'viabix_db', 'viabix_prod'];
    if (in_array(strtolower($dbName), $reserved, true)) {
        throw new RuntimeException('Banco protegido. Escolha um banco de homologacao/teste.');
    }

    if (!preg_match('/(homolog|homologacao|staging|teste|test|dev)/i', $dbName)) {
        throw new RuntimeException('Por seguranca, o nome do banco precisa indicar homologacao/teste/staging.');
    }
}

function normalizeSql(string $sql, string $demoPasswordHash = ''): string
{
    $sql = preg_replace('/^\s*CREATE\s+DATABASE\b.*?;\s*/ims', '', $sql) ?? $sql;
    $sql = preg_replace('/^\s*DROP\s+DATABASE\b.*?;\s*/ims', '', $sql) ?? $sql;
    $sql = preg_replace('/^\s*USE\s+`?[a-zA-Z0-9_]+`?\s*;\s*/im', '', $sql) ?? $sql;

    if ($demoPasswordHash !== '') {
        $sql = str_replace('$2y$12$replaceWithGeneratedHashBeforeImport', $demoPasswordHash, $sql);
    }

    // Compatibilidade com MySQL/MariaDB locais que nao aceitam IF NOT EXISTS em ALTER.
    // O fluxo recomendado usa --fresh, entao as colunas/indices ainda nao existem.
    $sql = preg_replace('/\bADD\s+COLUMN\s+IF\s+NOT\s+EXISTS\b/i', 'ADD COLUMN', $sql) ?? $sql;
    $sql = preg_replace('/\bADD\s+INDEX\s+IF\s+NOT\s+EXISTS\b/i', 'ADD INDEX', $sql) ?? $sql;

    return $sql;
}

function splitSqlStatements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $quote = null;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($quote === null && $char === '-' && $next === '-') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            $buffer .= "\n";
            continue;
        }

        if ($quote === null && $char === '#') {
            while ($i < $length && $sql[$i] !== "\n") {
                $i++;
            }
            $buffer .= "\n";
            continue;
        }

        if ($quote === null && $char === '/' && $next === '*') {
            $i += 2;
            while ($i + 1 < $length && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                $i++;
            }
            $i++;
            continue;
        }

        if (($char === "'" || $char === '"' || $char === '`') && ($i === 0 || $sql[$i - 1] !== '\\')) {
            if ($quote === null) {
                $quote = $char;
            } elseif ($quote === $char) {
                $quote = null;
            }
        }

        if ($char === ';' && $quote === null) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

function applySqlFile(PDO $pdo, string $path, string $demoPasswordHash = ''): int
{
    if (!is_file($path)) {
        throw new RuntimeException("Arquivo SQL nao encontrado: {$path}");
    }

    $sql = normalizeSql((string) file_get_contents($path), $demoPasswordHash);
    $count = 0;

    foreach (splitSqlStatements($sql) as $statement) {
        if (preg_match('/^(CREATE\s+DATABASE|DROP\s+DATABASE|USE)\b/i', $statement)) {
            continue;
        }

        $pdo->exec($statement);
        $count++;
    }

    return $count;
}

function databaseConnectionOptions(array $argv): array
{
    $passEnv = optionValue($argv, 'pass-env', null);
    $pass = $passEnv ? getenv($passEnv) : false;

    return [
        'host' => optionValue($argv, 'host', viabix_env('DB_HOST', viabix_env('MYSQL_HOST', '127.0.0.1'))),
        'port' => (int) optionValue($argv, 'port', viabix_env('DB_PORT', '3306')),
        'user' => optionValue($argv, 'user', viabix_env('DB_USER', viabix_env('MYSQL_USER', 'root'))),
        'pass' => $pass !== false ? (string) $pass : optionValue($argv, 'pass', viabix_env('DB_PASS', viabix_env('MYSQL_PASSWORD', ''))),
        'charset' => viabix_env('DB_CHARSET', 'utf8mb4'),
    ];
}

function connectServer(array $options): PDO
{
    $host = $options['host'];
    $port = (int) $options['port'];
    $user = $options['user'];
    $pass = $options['pass'];
    $charset = viabix_env('DB_CHARSET', 'utf8mb4');

    return new PDO(
        "mysql:host={$host};port={$port};charset={$charset}",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function connectDatabase(string $dbName, array $options): PDO
{
    $host = $options['host'];
    $port = (int) $options['port'];
    $user = $options['user'];
    $pass = $options['pass'];
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

try {
    if (hasFlag($argv, 'help')) {
        usageAndExit(0);
    }

    $dbName = optionValue($argv, 'db', viabix_env('HOMOLOG_DB_NAME', 'viabix_homolog'));
    $fresh = hasFlag($argv, 'fresh');
    $seedDemo = hasFlag($argv, 'seed-demo');
    $connection = databaseConnectionOptions($argv);

    assertSafeDatabaseName($dbName);

    $server = connectServer($connection);
    if ($fresh) {
        $server->exec("DROP DATABASE IF EXISTS `{$dbName}`");
    }
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = connectDatabase($dbName, $connection);
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    $root = dirname(__DIR__);
    $files = [
        'BD/viabix_saas_multitenant.sql',
        'BD/schema_extensoes_viabilidade.sql',
        'BD/migracao_permissoes.sql',
    ];

    $report = [];
    foreach ($files as $file) {
        $report[$file] = applySqlFile($pdo, $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file));
    }

    if ($seedDemo) {
        $demoPassword = viabix_env('HOMOLOG_DEMO_PASSWORD', 'Demo@123456!');
        $demoHash = password_hash($demoPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $report['BD/demo_comercial_seed.sql'] = applySqlFile(
            $pdo,
            $root . DIRECTORY_SEPARATOR . 'BD' . DIRECTORY_SEPARATOR . 'demo_comercial_seed.sql',
            $demoHash
        );
    }

    $tables = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()')->fetchColumn();

    echo json_encode([
        'success' => true,
        'database' => $dbName,
        'fresh' => $fresh,
        'seed_demo' => $seedDemo,
        'tables' => $tables,
        'applied_statements' => $report,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro ao preparar banco de homologacao: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
