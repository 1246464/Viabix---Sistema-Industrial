function aplicarPermissoesDePlano(features = {}) {
    const anviEnabled = features.modulo_anvi !== false;
    const projetosEnabled = features.modulo_projetos !== false;

    document.getElementById('moduleAnvi').classList.toggle('is-disabled', !anviEnabled);
    document.getElementById('moduleProjetos').classList.toggle('is-disabled', !projetosEnabled);
    document.getElementById('quickNewAnvi').classList.toggle('is-disabled', !anviEnabled);
    document.getElementById('quickProjects').classList.toggle('is-disabled', !projetosEnabled);
}

// Verificar sessão e carregar dados do usuário
async function verificarSessao() {
    try {
        const data = await ViabixApiCore.checkSession();

        if (ViabixApiCore.isAuthenticatedSession(data)) {
            document.getElementById('userName').textContent = data.nome || data.usuario;
            aplicarPermissoesDePlano(data.features || {});

            // Mostrar botão Admin SaaS APENAS para admins
            const isAdmin = data.user?.nivel === 'admin' || data.user?.role === 'admin';
            if (isAdmin) {
                document.getElementById('adminButton').style.display = 'inline-flex';
            } else {
                document.getElementById('adminButton').style.display = 'none';
            }

            const tenantBadge = document.getElementById('tenantBadge');
            if (data.tenant && (data.tenant.nome || data.subscription?.plano_nome)) {
                const tenantNome = data.tenant.nome || 'Conta ativa';
                const planoNome = data.subscription?.plano_nome ? ` • ${data.subscription.plano_nome}` : '';
                tenantBadge.textContent = `${tenantNome}${planoNome}`;
                tenantBadge.style.display = 'inline-flex';
            }

            return data;
        } else {
            window.location.href = 'login.html';
        }
    } catch (e) {
        console.error('Erro ao verificar sessão:', e);
        window.location.href = 'login.html';
    }

    return null;
}

// Cada resposta só pode atualizar a tela se ainda corresponder ao pedido atual.
let dashboardRequestVersion = 0;
let dashboardPendingKey = null;
let dashboardPendingPromise = null;

function definirEstadoDashboard(loading, message) {
    document.getElementById('insightsGrid')?.setAttribute('aria-busy', String(loading));
    document.getElementById('statsBar')?.setAttribute('aria-busy', String(loading));
    const status = document.getElementById('dashboardLoadStatus');
    if (status) status.textContent = message;
}

function carregarEstatisticas() {
    const filters = obterFiltrosDashboard();
    const key = JSON.stringify(filters);
    if (dashboardPendingPromise && dashboardPendingKey === key) return dashboardPendingPromise;
    const version = ++dashboardRequestVersion;
    dashboardPendingKey = key;
    definirEstadoDashboard(true, 'Carregando indicadores...');
    dashboardPendingPromise = (async () => {
        try {
            const data = await ViabixApiCore.getDashboardStats(filters);
            if (version !== dashboardRequestVersion) return;
            if (data._httpStatus === 401) {
                window.location.href = 'login.html';
                return;
            }
            if (!data._ok || data.success === false) throw new Error('Falha ao carregar indicadores');
            document.getElementById('totalAnvis').textContent = data.anvis ?? 0;
            document.getElementById('totalProjetos').textContent = data.projetos ?? 0;
            document.getElementById('totalVinculos').textContent = data.vinculos ?? 0;
            document.getElementById('totalUsuarios').textContent = data.usuarios ?? 0;
            document.getElementById('totalLideres').textContent = data.lideres ?? 0;
            renderizarInsightsOperacionais(data.dashboard_operacional || {});
            definirEstadoDashboard(false, 'Indicadores atualizados');
        } catch (error) {
            if (version !== dashboardRequestVersion) return;
            console.error('Erro ao carregar estatísticas:', error);
            definirEstadoDashboard(false, 'Não foi possível atualizar. Clique em Aplicar filtros para tentar novamente.');
        } finally {
            if (version === dashboardRequestVersion) {
                dashboardPendingPromise = null;
                dashboardPendingKey = null;
                document.getElementById('insightsGrid')?.setAttribute('aria-busy', 'false');
                document.getElementById('statsBar')?.setAttribute('aria-busy', 'false');
            }
        }
    })();
    return dashboardPendingPromise;
}

function obterFiltrosDashboard() {
    return {
        period: document.getElementById('dashboardPeriodFilter')?.value || '30',
        status: document.getElementById('dashboardStatusFilter')?.value || '',
        cliente: document.getElementById('dashboardClientFilter')?.value || '',
        responsavel: document.getElementById('dashboardOwnerFilter')?.value || ''
    };
}

function inicializarFiltrosDashboard() {
    const botaoAplicar = document.getElementById('dashboardApplyFilters');
    const insightsGrid = document.getElementById('insightsGrid');

    if (botaoAplicar) {
        botaoAplicar.addEventListener('click', () => {
            carregarEstatisticas();
        });
    }

    if (insightsGrid) {
        insightsGrid.addEventListener('click', (event) => {
            const target = event.target;
            if (target.matches('.insight-action')) {
                const url = target.getAttribute('data-url');
                if (url) {
                    window.location.href = url;
                }
            }
        });
    }
}

function classeStatus(tipo) {
    if (tipo === 'critico') return 'status-critico';
    if (tipo === 'atencao') return 'status-atencao';
    return 'status-ok';
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

function renderarHealthOperational(health) {
    const healthEl = document.getElementById('dashboardHealth');
    if (!healthEl) return;

    const score = Number(health.score ?? 0);
    const status = health.label || 'Sem dados';
    const message = health.message || 'Avaliação operacional não disponível';
    const fill = healthEl.querySelector('.health-fill');
    const messageEl = document.getElementById('dashboardHealthMessage');
    const scoreLabel = healthEl.querySelector('.health-score strong');

    if (scoreLabel) scoreLabel.textContent = `${status} • ${score}%`;
    if (fill) fill.style.width = `${Math.min(100, Math.max(0, score))}%`;
    if (messageEl) messageEl.textContent = message;
}

function renderizarInsightsOperacionais(dados) {
    const atencaoHoje = Array.isArray(dados.atencao_hoje) ? dados.atencao_hoje : [];
    const prioridades = Array.isArray(dados.prioridades) ? dados.prioridades : [];
    const riscosFinanceiros = Array.isArray(dados.financeiro?.alertas) ? dados.financeiro.alertas : [];
    const tendencias = Array.isArray(dados.tendencias) ? dados.tendencias : [];
    const listaAtencaoHoje = document.getElementById('listaAtencaoHoje');
    const listaPrioridades = document.getElementById('listaPrioridades');
    const listaRiscosFinanceiros = document.getElementById('listaRiscosFinanceiros');
    const tendenciaWrap = document.getElementById('tendenciaWrap');

    renderarHealthOperational(dados.health || {});

    listaAtencaoHoje.innerHTML = atencaoHoje.map((item) => `
        <li class="insight-item">
            <div>
                <strong>${escapeHtml(item.titulo)}</strong>
                <small>${escapeHtml(item.detalhe)}</small>
            </div>
            <div class="item-right">
                <span class="status-pill ${classeStatus(item.status)}">${escapeHtml(item.statusLabel)}</span>
                ${item.actionUrl ? `<button type="button" class="insight-action" data-url="${escapeHtml(item.actionUrl)}">${escapeHtml(item.actionLabel || 'Ver')}</button>` : ''}
            </div>
        </li>
    `).join('');

    listaPrioridades.innerHTML = prioridades.map((item) => `
        <li class="insight-item">
            <div>
                <strong>${escapeHtml(item.titulo)}</strong>
                <small>${escapeHtml(item.detalhe)}</small>
            </div>
            <div class="item-right">
                <span class="status-pill ${classeStatus(item.status)}">${escapeHtml(item.statusLabel)}</span>
                ${item.actionUrl ? `<button type="button" class="insight-action" data-url="${escapeHtml(item.actionUrl)}">${escapeHtml(item.actionLabel || 'Ver')}</button>` : ''}
            </div>
        </li>
    `).join('');

    const financeiroFallback = [{
        titulo: 'Nenhum risco financeiro relevante',
        detalhe: 'Margem, ROI, payback e desvios estão dentro dos critérios salvos',
        status: 'ok',
        statusLabel: 'OK'
    }];

    listaRiscosFinanceiros.innerHTML = (riscosFinanceiros.length ? riscosFinanceiros : financeiroFallback).map((item) => `
        <li class="insight-item">
            <div>
                <strong>${escapeHtml(item.titulo)}</strong>
                <small>${escapeHtml(item.detalhe)}</small>
            </div>
            <div class="item-right">
                <span class="status-pill ${classeStatus(item.status)}">${escapeHtml(item.statusLabel)}</span>
                ${item.actionUrl ? `<button type="button" class="insight-action" data-url="${escapeHtml(item.actionUrl)}">${escapeHtml(item.actionLabel || 'Ver')}</button>` : ''}
            </div>
        </li>
    `).join('');

    tendenciaWrap.innerHTML = tendencias.map((item) => {
        const valorAtual = Number(item.valorAtual || 0);
        const referencia = Number(item.referencia || 0);
        const delta = Number((valorAtual - referencia).toFixed(1));
        const melhora = item.invertido ? delta < 0 : delta > 0;
        const classeDelta = melhora ? 'trend-positive' : 'trend-negative';
        const sinal = delta > 0 ? '+' : '';
        const largura = Math.max(6, Math.min(100, referencia > 0 ? (valorAtual / referencia) * 100 : valorAtual));

        return `
            <div class="trend-item">
                <span class="trend-label">${escapeHtml(item.label)}</span>
                <div class="trend-values">
                    <span>${valorAtual}${escapeHtml(item.unidade || '')}</span>
                    <span class="${classeDelta}">${sinal}${delta}${escapeHtml(item.unidade || '')}</span>
                </div>
                <div class="trend-bar">
                    <div class="trend-fill" style="width: ${largura}%; background: ${escapeHtml(item.cor)};"></div>
                </div>
                <small>${escapeHtml(item.descricao || 'referência de 30 dias')}</small>
            </div>
        `;
    }).join('');
}

// Inicializar
document.addEventListener('DOMContentLoaded', async function() {
    const sessao = await verificarSessao();
    if (sessao) {
        inicializarFiltrosDashboard();
        carregarEstatisticas();
    }
});
