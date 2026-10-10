<?php
/**
 * Executor único da suíte de validação Viabix.
 *
 * Uso:
 *   php tests/run.php
 *   php tests/run.php --with-db --db=viabix_homolog
 */

$root = dirname(__DIR__);
$withDatabase = in_array('--with-db', $argv, true);
$commands = [
    [PHP_BINARY, $root . '/tests/smoke_static.php'],
];

if ($withDatabase) {
    $databaseCommand = [PHP_BINARY, $root . '/tests/db_homolog_validate.php'];
    foreach ($argv as $argument) {
        if ($argument !== '--with-db' && str_starts_with($argument, '--')) {
            $databaseCommand[] = $argument;
        }
    }
    $commands[] = $databaseCommand;
}

$startedAt = microtime(true);
foreach ($commands as $commandParts) {
    $command = implode(' ', array_map('escapeshellarg', $commandParts));
    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, 'Falha na suíte Viabix.' . PHP_EOL);
        exit($exitCode);
    }
}

$duration = number_format(microtime(true) - $startedAt, 2, '.', '');
echo "OK: suíte Viabix concluída em {$duration}s" . ($withDatabase ? ' com banco.' : ' sem banco.') . PHP_EOL;
