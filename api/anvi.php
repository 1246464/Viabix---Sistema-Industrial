<?php
/**
 * Gerenciamento de ANVIs - Sistema Viabix
 * VERSÃO SIMPLIFICADA - SEM PROCEDURE
 */

require_once 'config.php';

// Limpar qualquer saída anterior
if (ob_get_level()) ob_clean();

header('Content-Type: application/json');

// Iniciar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação - Suportar SESSION e JWT (como Controle de Projetos)
$user = viabixGetAuthenticatedUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

// Sincronizar SESSION com dados do usuário autenticado
// IMPORTANTE: SEMPRE sincronizar tenant_id para JWT após SESSION anterior
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_login'] = $user['login'] ?? '';
    $_SESSION['nome'] = $user['nome'] ?? '';
    $_SESSION['nivel'] = $user['nivel'] ?? '';
    $_SESSION['user_level'] = $user['nivel'] ?? '';
    $_SESSION['tenant_id'] = $user['tenant_id'] ?? '';
} else {
    // Sincronizar tenant_id mesmo que user_id já exista
    $_SESSION['tenant_id'] = $user['tenant_id'] ?? $_SESSION['tenant_id'] ?? '';
    $_SESSION['user_level'] = $_SESSION['user_level'] ?? $user['nivel'] ?? 'usuario';
}

$user_id = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'] ?? 'usuario';
$tenant_id = viabixCurrentTenantId();
$tenantAwareAnvis = viabixHasColumn('anvis', 'tenant_id') && $tenant_id;
$anvisHasDadosFinanceiros = viabixHasColumn('anvis', 'dados_financeiros');

function viabixFloatValue($value): float
{
    if (is_numeric($value)) {
        return (float) $value;
    }

    if (!is_string($value)) {
        return 0.0;
    }

    $normalized = trim($value);
    $normalized = str_replace(['R$', '%', 'meses', 'mes', 'peças', 'pecas', ' '], '', $normalized);
    $normalized = str_replace('.', '', $normalized);
    $normalized = str_replace(',', '.', $normalized);

    return is_numeric($normalized) ? (float) $normalized : 0.0;
}

function viabixRoundMoney(float $value): float
{
    return round($value, 2);
}

function viabixBuildFinancialSummary(array $input): array
{
    $financeiro = is_array($input['financeiro'] ?? null) ? $input['financeiro'] : [];
    $indicadores = is_array($financeiro['indicadores'] ?? null) ? $financeiro['indicadores'] : [];
    $custos = is_array($financeiro['custos'] ?? null) ? $financeiro['custos'] : [];
    $receitas = is_array($financeiro['receitas'] ?? null) ? $financeiro['receitas'] : [];
    $investimentos = is_array($financeiro['investimentos'] ?? null) ? $financeiro['investimentos'] : [];
    $realizado = is_array($financeiro['realizado'] ?? null) ? $financeiro['realizado'] : [];
    $config = is_array($input['configuracoes'] ?? null) ? $input['configuracoes'] : [];

    $custoUnitario = viabixFloatValue($custos['custo_unitario'] ?? $indicadores['custo_total'] ?? 0);
    $custoTotalMensal = viabixFloatValue($custos['custo_total_mensal'] ?? 0);
    $precoSugerido = viabixFloatValue($receitas['preco_sugerido'] ?? $indicadores['preco_sugerido'] ?? 0);
    $receitaMensal = viabixFloatValue($receitas['receita_mensal'] ?? 0);
    $lucroLiquidoMensal = viabixFloatValue($receitas['lucro_liquido_mensal'] ?? 0);
    $investimentoTotal = viabixFloatValue($investimentos['investimento_total'] ?? $indicadores['investimento_total'] ?? 0);
    $volumeMensal = viabixFloatValue($input['volumeMensal'] ?? ($input['informacoesBasicas']['monthlyVolume'] ?? 0));

    if ($custoTotalMensal <= 0 && $custoUnitario > 0 && $volumeMensal > 0) {
        $custoTotalMensal = $custoUnitario * $volumeMensal;
    }
    if ($receitaMensal <= 0 && $precoSugerido > 0 && $volumeMensal > 0) {
        $receitaMensal = $precoSugerido * $volumeMensal;
    }

    $margemEsperada = viabixFloatValue($indicadores['margem_esperada_pct'] ?? $indicadores['margem_liquida_pct'] ?? 0);
    if ($margemEsperada == 0.0 && $receitaMensal > 0 && $lucroLiquidoMensal != 0.0) {
        $margemEsperada = ($lucroLiquidoMensal / $receitaMensal) * 100;
    }

    $roi = viabixFloatValue($indicadores['roi_pct'] ?? $financeiro['roi_esperado_pct'] ?? 0);
    if ($roi == 0.0 && $investimentoTotal > 0 && $lucroLiquidoMensal != 0.0) {
        $roi = ($lucroLiquidoMensal * 12 / $investimentoTotal) * 100;
    }

    $payback = viabixFloatValue($indicadores['payback_meses'] ?? $financeiro['payback_meses'] ?? 0);
    if ($payback == 0.0 && $lucroLiquidoMensal > 0 && $investimentoTotal > 0) {
        $payback = $investimentoTotal / $lucroLiquidoMensal;
    }

    $custoReal = viabixFloatValue($realizado['custo_real'] ?? $financeiro['custo_real'] ?? 0);
    $desvioValor = $custoReal > 0 ? $custoReal - $custoTotalMensal : viabixFloatValue($indicadores['desvio_estimado_realizado_valor'] ?? 0);
    $desvioPct = $custoReal > 0 && $custoTotalMensal > 0
        ? (($custoReal - $custoTotalMensal) / $custoTotalMensal) * 100
        : viabixFloatValue($indicadores['desvio_estimado_realizado_pct'] ?? 0);

    $alertas = [];
    if ($margemEsperada > 0 && $margemEsperada < 10) {
        $alertas[] = ['tipo' => 'margem_baixa', 'severidade' => 'critico', 'mensagem' => 'Margem líquida abaixo de 10%'];
    } elseif ($margemEsperada > 0 && $margemEsperada < 15) {
        $alertas[] = ['tipo' => 'margem_atencao', 'severidade' => 'atencao', 'mensagem' => 'Margem líquida abaixo do recomendado'];
    }
    if ($roi > 0 && $roi < 20) {
        $alertas[] = ['tipo' => 'roi_baixo', 'severidade' => 'atencao', 'mensagem' => 'ROI anual abaixo de 20%'];
    }
    if ($payback > 24) {
        $alertas[] = ['tipo' => 'payback_longo', 'severidade' => 'atencao', 'mensagem' => 'Payback acima de 24 meses'];
    }
    if ($desvioPct > 10) {
        $alertas[] = ['tipo' => 'desvio_custo', 'severidade' => $desvioPct > 25 ? 'critico' : 'atencao', 'mensagem' => 'Custo realizado acima do estimado'];
    }

    return [
        'schema_version' => 1,
        'moeda' => 'BRL',
        'volume_mensal' => viabixRoundMoney($volumeMensal),
        'custo_unitario' => viabixRoundMoney($custoUnitario),
        'custo_total' => viabixRoundMoney($custoTotalMensal),
        'preco_sugerido' => viabixRoundMoney($precoSugerido),
        'receita_mensal' => viabixRoundMoney($receitaMensal),
        'lucro_liquido_mensal' => viabixRoundMoney($lucroLiquidoMensal),
        'investimento_total' => viabixRoundMoney($investimentoTotal),
        'margem_esperada_pct' => round($margemEsperada, 2),
        'roi_esperado_pct' => round($roi, 2),
        'payback_meses' => round($payback, 2),
        'desvio_estimado_realizado_valor' => viabixRoundMoney($desvioValor),
        'desvio_estimado_realizado_pct' => round($desvioPct, 2),
        'margem_lucro_markup_pct' => round(viabixFloatValue($config['margemLucroMarkup'] ?? 0), 2),
        'alertas' => $alertas,
        'riscos_identificados' => array_column($alertas, 'mensagem'),
        'calculado_em' => date('c'),
    ];
}

function viabixSaveFinancialSummary(PDO $pdo, string $tenantId, string $anviId, string $numero, string $revisao, array $summary): void
{
    if (!$tenantId || !viabixHasTable('anvi_resumo_financeiro')) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO anvi_resumo_financeiro (
            tenant_id, anvi_id, anvi_numero, anvi_revisao, moeda, volume_mensal,
            custo_unitario, custo_total, preco_sugerido, receita_mensal,
            lucro_liquido_mensal, investimento_total, margem_esperada_pct,
            roi_esperado_pct, payback_meses, desvio_estimado_realizado_valor,
            desvio_estimado_realizado_pct, alertas, calculado_em
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            anvi_numero = VALUES(anvi_numero),
            anvi_revisao = VALUES(anvi_revisao),
            moeda = VALUES(moeda),
            volume_mensal = VALUES(volume_mensal),
            custo_unitario = VALUES(custo_unitario),
            custo_total = VALUES(custo_total),
            preco_sugerido = VALUES(preco_sugerido),
            receita_mensal = VALUES(receita_mensal),
            lucro_liquido_mensal = VALUES(lucro_liquido_mensal),
            investimento_total = VALUES(investimento_total),
            margem_esperada_pct = VALUES(margem_esperada_pct),
            roi_esperado_pct = VALUES(roi_esperado_pct),
            payback_meses = VALUES(payback_meses),
            desvio_estimado_realizado_valor = VALUES(desvio_estimado_realizado_valor),
            desvio_estimado_realizado_pct = VALUES(desvio_estimado_realizado_pct),
            alertas = VALUES(alertas),
            calculado_em = NOW()
    ");

    $stmt->execute([
        $tenantId,
        $anviId,
        $numero,
        $revisao,
        $summary['moeda'] ?? 'BRL',
        $summary['volume_mensal'] ?? 0,
        $summary['custo_unitario'] ?? 0,
        $summary['custo_total'] ?? 0,
        $summary['preco_sugerido'] ?? 0,
        $summary['receita_mensal'] ?? 0,
        $summary['lucro_liquido_mensal'] ?? 0,
        $summary['investimento_total'] ?? 0,
        $summary['margem_esperada_pct'] ?? 0,
        $summary['roi_esperado_pct'] ?? 0,
        $summary['payback_meses'] ?? 0,
        $summary['desvio_estimado_realizado_valor'] ?? 0,
        $summary['desvio_estimado_realizado_pct'] ?? 0,
        json_encode($summary['alertas'] ?? [], JSON_UNESCAPED_UNICODE),
    ]);
}

// Verificar método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Proteção CSRF para operações de escrita — pular se autenticado via JWT (app mobile/Android)
if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'], true) && ($user['source'] ?? '') !== 'jwt') {
    require_once 'csrf.php';
    try {
        viabixValidateCsrfToken();
    } catch (RuntimeException $e) {
        http_response_code(403);
        echo json_encode(['error' => 'Validação de segurança falhou. Recarregue a página e tente novamente.']);
        exit;
    }
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Buscar uma ANVI específica
                if ($tenantAwareAnvis) {
                    $stmt = $pdo->prepare("SELECT * FROM anvis WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$_GET['id'], $tenant_id]);
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM anvis WHERE id = ?");
                    $stmt->execute([$_GET['id']]);
                }
                $anvi = $stmt->fetch();
                
                if (!$anvi) {
                    http_response_code(404);
                    echo json_encode(['error' => 'ANVI não encontrada']);
                    exit;
                }
                
                // Decodificar JSON - os dados completos estão no campo 'dados'
                $dadosCompletos = json_decode($anvi['dados'], true);
                
                // Mesclar com os metadados
                if (is_array($dadosCompletos)) {
                    $anvi = array_merge($anvi, $dadosCompletos);
                }
                
                echo json_encode($anvi);
                
            } else {
                // Listar todas as ANVIs (apenas metadados) — com paginação
                $page  = max(1, (int)($_GET['page'] ?? 1));
                $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
                $offset = ($page - 1) * $limit;

                if ($tenantAwareAnvis) {
                    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM anvis WHERE tenant_id = ?");
                    $countStmt->execute([$tenant_id]);
                    $total = (int)$countStmt->fetchColumn();

                    $stmt = $pdo->prepare(
                        "SELECT * FROM anvis
                         WHERE tenant_id = ?
                         ORDER BY data_atualizacao DESC
                         LIMIT ? OFFSET ?"
                    );
                    $stmt->execute([$tenant_id, $limit, $offset]);
                } else {
                    $total = (int)$pdo->query("SELECT COUNT(*) FROM anvis")->fetchColumn();
                    $stmt  = $pdo->prepare("SELECT * FROM anvis ORDER BY data_atualizacao DESC LIMIT ? OFFSET ?");
                    $stmt->execute([$limit, $offset]);
                }

                echo json_encode([
                    'data'        => $stmt->fetchAll(),
                    'total'       => $total,
                    'page'        => $page,
                    'limit'       => $limit,
                    'total_pages' => (int)ceil($total / $limit),
                ]);
            }
            break;
            
        case 'POST':
            // Salvar ANVI
            if ($user_level === 'visitante') {
                http_response_code(403);
                echo json_encode(['error' => 'Visitantes não podem salvar ANVIs']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos']);
                exit;
            }
            
            // Validar campos obrigatórios
            if (empty($input['id']) || empty($input['numero']) || empty($input['revisao'])) {
                echo json_encode(['success' => false, 'message' => 'ID, Nº ANVI e Revisão são obrigatórios']);
                exit;
            }
            
            $id = $input['id'];
            $numero = $input['numero'];
            $revisao = $input['revisao'];
            $cliente = $input['cliente'] ?? '';
            $projeto = $input['projeto'] ?? '';
            $produto = $input['produto'] ?? '';
            $volume_mensal = intval($input['volumeMensal'] ?? 1000);
            $data_anvi = $input['dataANVI'] ?? date('Y-m-d');
            $status = $input['status'] ?? 'em-andamento';
            $force = $input['force'] ?? false; // Flag para forçar substituição
            
            // Converter todos os dados para JSON
            $dados_json = json_encode($input, JSON_UNESCAPED_UNICODE);
            $dados_financeiros = viabixBuildFinancialSummary($input);
            $dados_financeiros_json = json_encode($dados_financeiros, JSON_UNESCAPED_UNICODE);
            
            // Calcular hash
            $hash = hash('sha256', $dados_json);
            
            // Verificar se já existe uma ANVI com o mesmo número e revisão
            if ($tenantAwareAnvis) {
                $stmt = $pdo->prepare("SELECT id, numero, revisao, cliente FROM anvis WHERE numero = ? AND revisao = ? AND tenant_id = ?");
                $stmt->execute([$numero, $revisao, $tenant_id]);
            } else {
                $stmt = $pdo->prepare("SELECT id, numero, revisao, cliente FROM anvis WHERE numero = ? AND revisao = ?");
                $stmt->execute([$numero, $revisao]);
            }
            $duplicata = $stmt->fetch();
            
            // Se existe duplicata e não foi forçado, perguntar ao usuário
            if ($duplicata && !$force) {
                http_response_code(409); // Conflict
                echo json_encode([
                    'success' => false,
                    'duplicate' => true,
                    'message' => "Já existe um registro salvo com Nº {$numero} Rev. {$revisao}",
                    'existing' => [
                        'id' => $duplicata['id'],
                        'numero' => $duplicata['numero'],
                        'revisao' => $duplicata['revisao'],
                        'cliente' => $duplicata['cliente']
                    ]
                ]);
                exit;
            }
            
            // Verificar se a ANVI já existe pelo ID
            if ($tenantAwareAnvis) {
                $stmt = $pdo->prepare("SELECT id, versao FROM anvis WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $tenant_id]);
            } else {
                $stmt = $pdo->prepare("SELECT id, versao FROM anvis WHERE id = ?");
                $stmt->execute([$id]);
            }
            $existente = $stmt->fetch();
            
            if ($existente) {
                // Atualizar existente
                $nova_versao = $existente['versao'] + 1;
                
                $dadosFinanceirosSet = $anvisHasDadosFinanceiros ? "dados_financeiros = ?," : "";
                $stmt = $pdo->prepare("
                    UPDATE anvis SET 
                        numero = ?, 
                        revisao = ?, 
                        cliente = ?, 
                        projeto = ?, 
                        produto = ?, 
                        volume_mensal = ?, 
                        data_anvi = ?, 
                        status = ?, 
                        dados = ?, 
                        {$dadosFinanceirosSet}
                        versao = ?, 
                        hash_conteudo = ?,
                        atualizado_por = ?,
                        data_atualizacao = NOW()
                    WHERE id = ?" . ($tenantAwareAnvis ? " AND tenant_id = ?" : "") . "
                ");

                $params = [
                    $numero, $revisao, $cliente, $projeto, $produto, 
                    $volume_mensal, $data_anvi, $status, $dados_json
                ];
                if ($anvisHasDadosFinanceiros) {
                    $params[] = $dados_financeiros_json;
                }
                $params = array_merge($params, [$nova_versao, $hash, $user_id, $id]);
                if ($tenantAwareAnvis) {
                    $params[] = $tenant_id;
                }
                $stmt->execute($params);
                
                $mensagem = 'ANVI atualizada com sucesso';
                $versao_retorno = $nova_versao;
                
            } else {
                // Inserir nova
                $nova_versao = 1;
                
                if ($tenantAwareAnvis) {
                    $financeiroColumn = $anvisHasDadosFinanceiros ? ', dados_financeiros' : '';
                    $financeiroPlaceholder = $anvisHasDadosFinanceiros ? ', ?' : '';
                    $stmt = $pdo->prepare("
                        INSERT INTO anvis (
                            id, tenant_id, numero, revisao, cliente, projeto, produto,
                            volume_mensal, data_anvi, status, dados, versao,
                            hash_conteudo, criado_por, atualizado_por{$financeiroColumn}
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?{$financeiroPlaceholder})
                    ");
                    $params = [
                        $id, $tenant_id, $numero, $revisao, $cliente, $projeto, $produto,
                        $volume_mensal, $data_anvi, $status, $dados_json,
                        $nova_versao, $hash, $user_id, $user_id
                    ];
                    if ($anvisHasDadosFinanceiros) {
                        $params[] = $dados_financeiros_json;
                    }
                    $stmt->execute($params);
                } else {
                    $financeiroColumn = $anvisHasDadosFinanceiros ? ', dados_financeiros' : '';
                    $financeiroPlaceholder = $anvisHasDadosFinanceiros ? ', ?' : '';
                    $stmt = $pdo->prepare("
                        INSERT INTO anvis (
                            id, numero, revisao, cliente, projeto, produto, 
                            volume_mensal, data_anvi, status, dados, versao, 
                            hash_conteudo, criado_por, atualizado_por{$financeiroColumn}
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?{$financeiroPlaceholder})
                    ");
                    $params = [
                        $id, $numero, $revisao, $cliente, $projeto, $produto,
                        $volume_mensal, $data_anvi, $status, $dados_json, 
                        $nova_versao, $hash, $user_id, $user_id
                    ];
                    if ($anvisHasDadosFinanceiros) {
                        $params[] = $dados_financeiros_json;
                    }
                    $stmt->execute($params);
                }
                
                $mensagem = 'ANVI criada com sucesso';
                $versao_retorno = $nova_versao;
            }

            $avisos_salvamento = [];
            try {
                viabixSaveFinancialSummary($pdo, (string) $tenant_id, $id, $numero, $revisao, $dados_financeiros);
            } catch (Throwable $e) {
                $avisos_salvamento[] = 'Resumo financeiro não foi atualizado, mas a ANVI foi salva.';
                logError('Falha ao salvar resumo financeiro da ANVI', [
                    'error' => $e->getMessage(),
                    'anvi_id' => $id,
                    'tenant_id' => $tenant_id,
                ]);
            }
            
            // Registrar log
            try {
                viabixLogActivity(
                    $user_id,
                    'salvar_anvi',
                    "Salvou ANVI: {$numero} Rev. {$revisao} (versão {$versao_retorno})",
                    'anvi',
                    $id
                );
            } catch (Throwable $e) {
                $avisos_salvamento[] = 'Log de atividade não foi registrado, mas a ANVI foi salva.';
                logError('Falha ao registrar log de salvamento da ANVI', [
                    'error' => $e->getMessage(),
                    'anvi_id' => $id,
                    'tenant_id' => $tenant_id,
                ]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => $mensagem,
                'versao' => $versao_retorno,
                'warnings' => $avisos_salvamento
            ]);
            
            break;
            
        case 'PUT':
            // Bloquear/Desbloquear ANVI para edição
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['acao'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Ação não especificada']);
                exit;
            }
            
            $acao = $input['acao'];
            
            // Limpar bloqueios antigos do próprio usuário
            if ($acao === 'limpar_bloqueios') {
                if ($tenantAwareAnvis) {
                    $stmt = $pdo->prepare("UPDATE anvis SET bloqueado_por = NULL, bloqueado_em = NULL WHERE bloqueado_por = ? AND tenant_id = ?");
                    $stmt->execute([$user_id, $tenant_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE anvis SET bloqueado_por = NULL, bloqueado_em = NULL WHERE bloqueado_por = ?");
                    $stmt->execute([$user_id]);
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Bloqueios limpos',
                    'count' => $stmt->rowCount()
                ]);
                break;
            }
            
            // Para outras ações, ID é obrigatório
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID não especificado']);
                exit;
            }
            
            $id = $input['id'];
            
            if ($acao === 'bloquear') {
                // Bloquear ANVI para edição exclusiva
                $sql = "UPDATE anvis SET bloqueado_por = ?, bloqueado_em = NOW() WHERE id = ?";
                if ($tenantAwareAnvis) {
                    $sql .= " AND tenant_id = ?";
                }
                $sql .= " AND (bloqueado_por IS NULL OR bloqueado_por = ? OR bloqueado_em < DATE_SUB(NOW(), INTERVAL 30 MINUTE))";
                $stmt = $pdo->prepare($sql);
                $params = [$user_id, $id];
                if ($tenantAwareAnvis) {
                    $params[] = $tenant_id;
                }
                $params[] = $user_id;
                $stmt->execute($params);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'ANVI bloqueada para edição']);
                } else {
                    // Verificar quem bloqueou
                    $sql = "SELECT u.nome as usuario, bloqueado_em FROM anvis a JOIN usuarios u ON a.bloqueado_por = u.id WHERE a.id = ?";
                    $params = [$id];
                    if ($tenantAwareAnvis) {
                        $sql .= " AND a.tenant_id = ?";
                        $params[] = $tenant_id;
                    }
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $bloqueio = $stmt->fetch();
                    
                    echo json_encode([
                        'success' => false, 
                        'message' => 'ANVI já está sendo editada por outro usuário',
                        'bloqueado_por' => $bloqueio['usuario'] ?? 'outro usuário',
                        'bloqueado_em' => $bloqueio['bloqueado_em'] ?? null
                    ]);
                }
            } else if ($acao === 'desbloquear') {
                // Desbloquear ANVI
                $sql = "UPDATE anvis SET bloqueado_por = NULL, bloqueado_em = NULL WHERE id = ? AND bloqueado_por = ?";
                $params = [$id, $user_id];
                if ($tenantAwareAnvis) {
                    $sql .= " AND tenant_id = ?";
                    $params[] = $tenant_id;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                echo json_encode(['success' => true, 'message' => 'ANVI desbloqueada']);
            }
            break;
            
        case 'DELETE':
            // Excluir ANVI
            if ($user_level === 'visitante') {
                http_response_code(403);
                echo json_encode(['error' => 'Visitantes não podem excluir ANVIs']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID não fornecido']);
                exit;
            }
            
            $id = $input['id'];
            
            // Verificar se está bloqueada
            $sql = "SELECT bloqueado_por, u.nome as usuario FROM anvis a LEFT JOIN usuarios u ON a.bloqueado_por = u.id WHERE a.id = ?";
            $params = [$id];
            if ($tenantAwareAnvis) {
                $sql .= " AND a.tenant_id = ?";
                $params[] = $tenant_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $anvi = $stmt->fetch();
            
            if ($anvi && $anvi['bloqueado_por'] && $anvi['bloqueado_por'] != $user_id) {
                echo json_encode([
                    'success' => false, 
                    'message' => "ANVI está sendo editada por {$anvi['usuario']}. Não é possível excluir no momento."
                ]);
                exit;
            }
            
            // Obter dados para o log
            if ($tenantAwareAnvis) {
                $stmt = $pdo->prepare("SELECT numero, revisao FROM anvis WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $tenant_id]);
            } else {
                $stmt = $pdo->prepare("SELECT numero, revisao FROM anvis WHERE id = ?");
                $stmt->execute([$id]);
            }
            $dados = $stmt->fetch();
            
            // Excluir
            if ($tenantAwareAnvis) {
                $stmt = $pdo->prepare("DELETE FROM anvis WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $tenant_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM anvis WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            if ($stmt->rowCount() > 0) {
                // Registrar log
                viabixLogActivity(
                    $user_id,
                    'excluir_anvi',
                    "Excluiu ANVI: {$dados['numero']} Rev. {$dados['revisao']}",
                    'anvi',
                    $id
                );
                
                echo json_encode(['success' => true, 'message' => 'ANVI excluída com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ANVI não encontrada']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
} catch (PDOException $e) {
    logError("Erro em anvi.php", ['error' => $e->getMessage(), 'method' => $method]);
    http_response_code(500);
    echo json_encode(['error' => APP_DEBUG ? ('Erro interno: ' . $e->getMessage()) : 'Erro interno do servidor']);
}
?>
