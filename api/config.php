<?php
/**
 * Configuração central do Sistema Viabix.
 *
 * Os blocos operacionais foram separados em `api/lib/*` para reduzir o
 * tamanho deste bootstrap e facilitar manutenção por domínio.
 */

require_once __DIR__ . '/lib/runtime.php';
require_once __DIR__ . '/lib/support.php';
require_once __DIR__ . '/lib/schema.php';
require_once __DIR__ . '/lib/auth_tenant.php';
require_once __DIR__ . '/lib/billing_gateway.php';
?>
