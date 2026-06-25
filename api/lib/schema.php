<?php
/**
 * Helpers de introspecção do schema do banco.
 */

if (!defined('VIABIX_APP')) {
    http_response_code(403);
    exit('Acesso direto não permitido.');
}

function viabixHasTable($tableName) {
    static $cache = [];
    global $pdo;

    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    $stmt = $pdo->prepare(
        "SELECT 1
         FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?
         LIMIT 1"
    );
    $stmt->execute([$tableName]);
    $cache[$tableName] = (bool) $stmt->fetchColumn();

    return $cache[$tableName];
}

/**
 * Verifica se uma coluna existe antes de montar queries compatíveis com fases diferentes do schema.
 */
function viabixHasColumn($tableName, $columnName) {
    static $cache = [];
    global $pdo;

    $cacheKey = $tableName . '.' . $columnName;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $stmt = $pdo->prepare(
        "SELECT 1
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = ?
           AND column_name = ?
         LIMIT 1"
    );
    $stmt->execute([$tableName, $columnName]);
    $cache[$cacheKey] = (bool) $stmt->fetchColumn();

    return $cache[$cacheKey];
}

/**
 * Mapeia níveis legados e novos para uma escala comum.
 */

