-- Demo comercial Viabix
-- Use somente em homologacao/apresentacao. Nao rode em producao.
-- Antes de usar, troque o hash do usuario demo por um bcrypt gerado para a senha desejada.

SET @tenant_id = 'demo-tenant-0000-0000-000000000001';
SET @user_id = 'demo-user-0000-0000-000000000001';
SET @subscription_id = 'demo-sub-0000-0000-000000000001';
SET @anvi_id = 'DEMO-2026-LAM-001';

INSERT INTO tenants (id, slug, nome_fantasia, razao_social, cnpj, email_financeiro, telefone, status, timezone, moeda, trial_ate, ativado_em)
VALUES (@tenant_id, 'montadora-aurora-demo', 'Montadora Aurora Demo', 'Montadora Aurora Demo S.A.', '00000000000191', 'demo@viabix.com.br', '+5511999990000', 'trial', 'America/Sao_Paulo', 'BRL', DATE_ADD(NOW(), INTERVAL 14 DAY), NOW())
ON DUPLICATE KEY UPDATE nome_fantasia = VALUES(nome_fantasia), status = VALUES(status), trial_ate = VALUES(trial_ate);

INSERT INTO usuarios (id, tenant_id, login, email, nome, senha, nivel, ativo)
VALUES (@user_id, @tenant_id, 'demo.admin', 'demo@viabix.com.br', 'Admin Demo', '$2y$12$replaceWithGeneratedHashBeforeImport', 'admin', 1)
ON DUPLICATE KEY UPDATE nome = VALUES(nome), nivel = VALUES(nivel), ativo = VALUES(ativo);

INSERT INTO subscriptions (id, tenant_id, plan_id, status, gateway, ciclo, quantidade_usuarios_contratados, valor_contratado, trial_iniciado_em, trial_ate, inicio_vigencia, fim_vigencia)
SELECT @subscription_id, @tenant_id, p.id, 'trial', 'manual', 'mensal', 3, 0, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY)
FROM plans p
WHERE p.codigo = 'pro'
LIMIT 1
ON DUPLICATE KEY UPDATE status = VALUES(status), trial_ate = VALUES(trial_ate), fim_vigencia = VALUES(fim_vigencia);

INSERT INTO subscription_events (subscription_id, tenant_id, tipo_evento, origem, payload)
VALUES (@subscription_id, @tenant_id, 'demo_seeded', 'sistema', JSON_OBJECT('plan_code', 'pro', 'purpose', 'commercial_demo'));

INSERT INTO anvis (id, tenant_id, numero, revisao, cliente, projeto, produto, volume_mensal, data_anvi, status, dados, criado_por, atualizado_por)
VALUES (
    @anvi_id,
    @tenant_id,
    'DEMO-2026-LAM-001',
    '00',
    'Montadora Aurora',
    'Para-brisa panoramico SUV eletrico',
    'Vidro laminado panoramico com controle solar',
    8500,
    CURDATE(),
    'em-andamento',
    JSON_OBJECT(
        'informacoesBasicas', JSON_OBJECT(
            'anviNumber', 'DEMO-2026-LAM-001',
            'client', 'Montadora Aurora',
            'project', 'Para-brisa panoramico SUV eletrico',
            'productDescription', 'Vidro laminado panoramico com controle solar',
            'segment', 'Autos',
            'monthlyVolume', '8500'
        ),
        'financeiro', JSON_OBJECT(
            'indicadores', JSON_OBJECT('margem_liquida_pct', 18.6, 'roi_pct', 42.4, 'payback_meses', 14.2),
            'custos', JSON_OBJECT('custo_unitario', 286.40, 'custo_total_mensal', 2434400),
            'receitas', JSON_OBJECT('preco_sugerido', 352.20, 'receita_mensal', 2993700, 'lucro_liquido_mensal', 556900),
            'investimentos', JSON_OBJECT('investimento_total', 7900000)
        )
    ),
    @user_id,
    @user_id
)
ON DUPLICATE KEY UPDATE dados = VALUES(dados), volume_mensal = VALUES(volume_mensal), atualizado_por = VALUES(atualizado_por);

INSERT INTO projetos (tenant_id, anvi_id, cliente, nome, segmento, codigo, fase, status, progresso, orcamento, dados)
VALUES (
    @tenant_id,
    @anvi_id,
    'Montadora Aurora',
    'Industrializacao para-brisa panoramico',
    'Automotivo',
    'PRJ-DEMO-2026-001',
    'Planejamento',
    'em-andamento',
    38.00,
    7900000.00,
    JSON_OBJECT(
        'projectName', 'Industrializacao para-brisa panoramico',
        'cliente', 'Montadora Aurora',
        'anviId', @anvi_id,
        'anviNumber', 'DEMO-2026-LAM-001',
        'status', 'Em Andamento',
        'fase', 'Planejamento',
        'projectLeader', 'Eng. Marina Costa',
        'tasks', JSON_OBJECT(
            'kom', JSON_OBJECT('planned', CURDATE(), 'executed', CURDATE(), 'duration', 1),
            'ferramental', JSON_OBJECT('planned', DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'executed', NULL, 'duration', 5),
            'tryout', JSON_OBJECT('planned', DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'executed', NULL, 'duration', 3),
            'psw', JSON_OBJECT('planned', DATE_ADD(CURDATE(), INTERVAL 75 DAY), 'executed', NULL, 'duration', 1)
        )
    )
);
