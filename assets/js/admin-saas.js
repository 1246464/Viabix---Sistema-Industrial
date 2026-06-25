const alertBox = document.getElementById('alert');
const summaryGrid = document.getElementById('summaryGrid');
const tenantRows = document.getElementById('tenantRows');
const tenantDetail = document.getElementById('tenantDetail');
const statusFilter = document.getElementById('statusFilter');
const planFilter = document.getElementById('planFilter');
const tenantPeriodFilter = document.getElementById('tenantPeriodFilter');
const searchInput = document.getElementById('searchInput');
const reloadButton = document.getElementById('reloadButton');
const integrationHeader = document.getElementById('integrationHeader');
const opsSummary = document.getElementById('opsSummary');
const webhookRows = document.getElementById('webhookRows');
const paymentRows = document.getElementById('paymentRows');
const webhookProviderFilter = document.getElementById('webhookProviderFilter');
const webhookStatusFilter = document.getElementById('webhookStatusFilter');
const webhookPeriodFilter = document.getElementById('webhookPeriodFilter');
const webhookFilterButton = document.getElementById('webhookFilterButton');
const paymentProviderFilter = document.getElementById('paymentProviderFilter');
const paymentStatusFilter = document.getElementById('paymentStatusFilter');
const paymentPeriodFilter = document.getElementById('paymentPeriodFilter');
const paymentFilterButton = document.getElementById('paymentFilterButton');

let overview = null;
let selectedTenantId = null;

function showAlert(message, type = 'error') {
    alertBox.className = `alert ${type}`;
    alertBox.textContent = message;
    alertBox.style.display = 'block';
}

function hideAlert() {
    alertBox.style.display = 'none';
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
}

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('pt-BR');
}

function formatDateTime(value) {
    if (!value) return '-';
    return new Date(value).toLocaleString('pt-BR');
}

function badge(status) {
    return `<span class="badge ${status || ''}">${status || '-'}</span>`;
}

function chip(label, tone = '') {
    return `<span class="status-chip ${tone}">${label}</span>`;
}

function renderSummary(summary) {
    summaryGrid.innerHTML = `
        <div class="summary-card"><strong>${summary.tenants_total || 0}</strong><span>Tenants totais</span></div>
        <div class="summary-card"><strong>${summary.tenants_ativos || 0}</strong><span>Contas ativas</span></div>
        <div class="summary-card"><strong>${summary.tenants_trial || 0}</strong><span>Trials rodando</span></div>
        <div class="summary-card"><strong>${summary.tenants_inadimplentes || 0}</strong><span>Inadimplentes</span></div>
    `;
}

function renderPlanFilter() {
    const plans = overview?.plans || [];
    const currentValue = planFilter.value;

    planFilter.innerHTML = `
        <option value="">Todos os planos</option>
        ${plans.map((plan) => `<option value="${plan.codigo}">${plan.nome} (${plan.codigo})</option>`).join('')}
    `;

    if ([...planFilter.options].some((option) => option.value === currentValue)) {
        planFilter.value = currentValue;
    }
}

function getFilteredTenants() {
    if (!overview) return [];

    const query = searchInput.value.trim().toLowerCase();
    const status = statusFilter.value;
    const plan = planFilter.value;
    const period = tenantPeriodFilter.value;
    const now = Date.now();

    return overview.tenants.filter((tenant) => {
        const matchesStatus = !status || tenant.tenant_status === status;
        const matchesPlan = !plan || tenant.plan_code === plan;
        const haystack = [tenant.nome_fantasia, tenant.slug, tenant.plan_name, tenant.plan_code, tenant.email_financeiro]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();
        const matchesQuery = !query || haystack.includes(query);

        let matchesPeriod = true;
        if (period !== 'all') {
            const createdAt = tenant.created_at ? new Date(tenant.created_at).getTime() : null;
            if (!createdAt || Number.isNaN(createdAt)) {
                matchesPeriod = false;
            } else {
                const diff = now - createdAt;
                if (period === '24h') {
                    matchesPeriod = diff <= 24 * 60 * 60 * 1000;
                } else if (period === '7d') {
                    matchesPeriod = diff <= 7 * 24 * 60 * 60 * 1000;
                } else if (period === '30d') {
                    matchesPeriod = diff <= 30 * 24 * 60 * 60 * 1000;
                }
            }
        }

        return matchesStatus && matchesPlan && matchesQuery && matchesPeriod;
    });
}

function renderTenantRows() {
    const tenants = getFilteredTenants();

    if (!tenants.length) {
        tenantRows.innerHTML = '<tr><td colspan="6" class="empty">Nenhum tenant encontrado com os filtros atuais.</td></tr>';
        return;
    }

    tenantRows.innerHTML = tenants.map((tenant) => `
        <tr>
            <td>
                <div class="tenant-name">${tenant.nome_fantasia}</div>
                <div class="muted">${tenant.slug} · ${tenant.email_financeiro || 'sem e-mail financeiro'}</div>
            </td>
            <td>${badge(tenant.tenant_status)}<div style="margin-top:6px">${badge(tenant.subscription_status || 'sem-assinatura')}</div></td>
            <td>
                <div>${tenant.plan_name || '-'}</div>
                <div class="muted">${tenant.ciclo || '-'}</div>
            </td>
            <td>
                <div>${tenant.usuarios_ativos || 0} usuários</div>
                <div class="muted">${tenant.total_anvis || 0} ANVIs · ${tenant.total_projetos || 0} projetos</div>
            </td>
            <td>
                <div>${formatCurrency(tenant.valor_contratado)}</div>
                <div class="muted">${tenant.invoices_vencidas || 0} vencidas</div>
            </td>
            <td>
                <button type="button" onclick="loadTenantDetail('${tenant.id}')">Abrir</button>
            </td>
        </tr>
    `).join('');
}

function renderPlansSelect(plans, selectedCode) {
    return `
        <select id="planSelect">
            ${plans.map((plan) => `<option value="${plan.codigo}" ${plan.codigo === selectedCode ? 'selected' : ''}>${plan.nome} (${plan.codigo})</option>`).join('')}
        </select>
    `;
}

function renderTenantDetail(detail) {
    const { tenant, users, events, invoices, payments, webhooks } = detail;
    tenantDetail.innerHTML = `
        <div>
            <h3>${tenant.nome_fantasia}</h3>
            <div class="muted" style="margin-top:6px">${tenant.slug} · ${tenant.email_financeiro || 'sem e-mail financeiro'}</div>
            <div style="margin-top:10px">${badge(tenant.status)} ${badge(tenant.subscription_status || 'sem-assinatura')}</div>
        </div>

        <div class="detail-section">
            <h3>Plano atual</h3>
            <div>${tenant.plan_name || '-'} · ${tenant.plan_code || '-'}</div>
            <div class="muted">${tenant.ciclo || '-'} · ${formatCurrency(tenant.valor_contratado)}</div>
            <div class="muted">Vigência até ${formatDate(tenant.fim_vigencia || tenant.trial_ate)}</div>
        </div>

        <div class="detail-section">
            <h3>Trocar plano</h3>
            ${renderPlansSelect(overview.plans || [], tenant.plan_code)}
            <div class="detail-actions">
                <button type="button" onclick="changePlan('${tenant.id}')">Aplicar plano selecionado</button>
            </div>
        </div>

        <div class="detail-section">
            <h3>Status operacional</h3>
            <div class="detail-actions">
                <button type="button" onclick="updateTenantStatus('${tenant.id}', 'trial', 'trial', 14)">Reabrir trial 14 dias</button>
                <button type="button" onclick="updateTenantStatus('${tenant.id}', 'ativo', 'ativa')">Ativar conta</button>
                <button type="button" onclick="updateTenantStatus('${tenant.id}', 'suspenso', 'suspensa')">Suspender conta</button>
                <button type="button" onclick="updateTenantStatus('${tenant.id}', 'inadimplente', 'inadimplente')">Marcar inadimplência</button>
                <button type="button" onclick="updateTenantStatus('${tenant.id}', 'cancelado', 'cancelada')">Cancelar conta</button>
            </div>
        </div>

        <div class="detail-section">
            <h3>Usuários recentes</h3>
            <table class="mini-table">
                <thead><tr><th>Login</th><th>Nível</th><th>Status</th></tr></thead>
                <tbody>
                    ${(users || []).length ? users.map((user) => `
                        <tr>
                            <td>${user.nome}<div class="muted">${user.login}</div></td>
                            <td>${user.nivel}</td>
                            <td>${user.ativo ? 'ativo' : 'inativo'}</td>
                        </tr>
                    `).join('') : '<tr><td colspan="3">Sem usuários.</td></tr>'}
                </tbody>
            </table>
        </div>

        <div class="detail-section">
            <h3>Últimos eventos</h3>
            <table class="mini-table">
                <thead><tr><th>Evento</th><th>Origem</th><th>Data</th></tr></thead>
                <tbody>
                    ${(events || []).length ? events.map((event) => `
                        <tr>
                            <td>${event.tipo_evento}</td>
                            <td>${event.origem}</td>
                            <td>${formatDate(event.created_at)}</td>
                        </tr>
                    `).join('') : '<tr><td colspan="3">Sem eventos.</td></tr>'}
                </tbody>
            </table>
        </div>

        <div class="detail-section">
            <h3>Últimas faturas</h3>
            <table class="mini-table">
                <thead><tr><th>Número</th><th>Status</th><th>Valor</th></tr></thead>
                <tbody>
                    ${(invoices || []).length ? invoices.slice(0, 8).map((invoice) => `
                        <tr>
                            <td>${invoice.numero}</td>
                            <td>${invoice.status}</td>
                            <td>${formatCurrency(invoice.valor_total)}</td>
                        </tr>
                    `).join('') : '<tr><td colspan="3">Sem faturas.</td></tr>'}
                </tbody>
            </table>
        </div>

        <div class="detail-section">
            <h3>Últimos pagamentos</h3>
            <table class="mini-table">
                <thead><tr><th>ID</th><th>Status</th><th>Valor</th></tr></thead>
                <tbody>
                    ${(payments || []).length ? payments.slice(0, 8).map((payment) => `
                        <tr>
                            <td>${payment.gateway_payment_id || payment.id}<div class="muted">${payment.metodo || '-'}</div></td>
                            <td>${payment.status}</td>
                            <td>${formatCurrency(payment.valor)}</td>
                        </tr>
                    `).join('') : '<tr><td colspan="3">Sem pagamentos.</td></tr>'}
                </tbody>
            </table>
        </div>

        <div class="detail-section">
            <h3>Últimos webhooks</h3>
            <table class="mini-table">
                <thead><tr><th>Evento</th><th>Status</th><th>Data</th></tr></thead>
                <tbody>
                    ${(webhooks || []).length ? webhooks.slice(0, 8).map((webhook) => `
                        <tr>
                            <td>${webhook.event_type}<div class="muted">${webhook.provider} · ${webhook.event_id}</div></td>
                            <td>
                                ${webhook.erro_processamento ? 'erro' : (webhook.processado ? 'processado' : 'pendente')}
                                ${webhook.erro_processamento ? `<div class="muted">${webhook.erro_processamento}</div><button type="button" class="inline-action" onclick="reprocessWebhook(${webhook.id}, '${tenant.id}')">Reprocessar</button>` : ''}
                            </td>
                            <td>${formatDateTime(webhook.created_at)}</td>
                        </tr>
                    `).join('') : '<tr><td colspan="3">Sem webhooks.</td></tr>'}
                </tbody>
            </table>
        </div>
    `;
}

function renderIntegration(integration) {
    const summary = integration?.summary || {};
    const providers = integration?.providers || {};
    const asaasEnabled = !!summary.asaas_enabled;

    integrationHeader.innerHTML = `
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
            ${chip(`Default: ${summary.default_provider || 'manual'}`)}
            ${chip(`Asaas: ${asaasEnabled ? 'habilitado' : 'desabilitado'}`, asaasEnabled ? '' : 'warn')}
            ${chip(`Ambiente: ${summary.asaas_environment || '-'}`)}
            ${chip(`Token webhook: ${summary.asaas_webhook_token_configured ? 'configurado' : 'ausente'}`, summary.asaas_webhook_token_configured ? '' : 'warn')}
        </div>
        <div class="muted" style="margin-top:10px">Webhook esperado: ${providers?.asaas?.webhook_url || '-'}</div>
    `;

    opsSummary.innerHTML = `
        <div class="summary-card"><strong>${summary.webhooks_last_24h || 0}</strong><span>Webhooks 24h</span></div>
        <div class="summary-card"><strong>${summary.webhooks_pending || 0}</strong><span>Webhooks pendentes</span></div>
        <div class="summary-card"><strong>${summary.webhooks_with_error || 0}</strong><span>Webhooks com erro</span></div>
        <div class="summary-card"><strong>${summary.failed_payments || 0}</strong><span>Pagamentos falhos</span></div>
        <div class="summary-card"><strong>${summary.pending_invoices || 0}</strong><span>Faturas pendentes</span></div>
        <div class="summary-card"><strong>${formatDateTime(summary.last_webhook_at)}</strong><span>Último webhook</span></div>
        <div class="summary-card"><strong>${formatDateTime(summary.last_paid_invoice_at)}</strong><span>Último pagamento confirmado</span></div>
        <div class="summary-card"><strong>${providers?.asaas?.enabled ? providers.asaas.mode : 'fallback'}</strong><span>Modo operacional</span></div>
    `;

}

async function loadOverview() {
    hideAlert();
    const response = await fetch('api/admin_saas.php?action=overview', { credentials: 'include' });
    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Falha ao carregar overview admin.');
    }

    overview = data;
    renderSummary(data.summary || {});
    renderPlanFilter();
    renderTenantRows();
    renderIntegration(data.integration || {});
}

function renderGlobalWebhooks(webhooks) {
    webhookRows.innerHTML = webhooks.length ? webhooks.map((webhook) => `
        <tr>
            <td>${webhook.event_type}<div class="muted">${webhook.provider} · ${webhook.event_id}</div></td>
            <td>${webhook.nome_fantasia || '-'}<div class="muted">${webhook.slug || 'sem tenant'}</div></td>
            <td>
                ${webhook.erro_processamento ? badge('erro') : badge(webhook.processado ? 'processado' : 'pendente')}
                ${webhook.erro_processamento ? `<button type="button" class="inline-action" onclick="reprocessWebhook(${webhook.id}${webhook.tenant_id ? `, '${webhook.tenant_id}'` : ''})">Reprocessar</button>` : ''}
            </td>
            <td>${formatDateTime(webhook.created_at)}${webhook.erro_processamento ? `<div class="muted">${webhook.erro_processamento}</div>` : ''}</td>
        </tr>
    `).join('') : '<tr><td colspan="4" class="empty">Nenhum webhook encontrado para os filtros atuais.</td></tr>';
}

async function loadGlobalWebhooks() {
    const params = new URLSearchParams({
        provider: webhookProviderFilter.value,
        status: webhookStatusFilter.value,
        period: webhookPeriodFilter.value,
        limit: '100'
    });

    const response = await fetch(`api/admin_saas.php?action=webhooks&${params.toString()}`, { credentials: 'include' });
    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Falha ao carregar webhooks filtrados.');
    }

    renderGlobalWebhooks(data.webhooks || []);
}

function renderGlobalPayments(payments) {
    paymentRows.innerHTML = payments.length ? payments.map((payment) => `
        <tr>
            <td>${payment.gateway_payment_id || payment.id}<div class="muted">${payment.invoice_number || '-'} · ${payment.metodo || '-'} · ${payment.provider || '-'}</div></td>
            <td>${payment.nome_fantasia || '-'}</td>
            <td>${badge(payment.status)}</td>
            <td>${formatCurrency(payment.valor)}<div class="muted">${formatDateTime(payment.pago_em || payment.created_at)}</div></td>
        </tr>
    `).join('') : '<tr><td colspan="4" class="empty">Nenhum pagamento encontrado para os filtros atuais.</td></tr>';
}

async function loadGlobalPayments() {
    const params = new URLSearchParams({
        provider: paymentProviderFilter.value,
        status: paymentStatusFilter.value,
        period: paymentPeriodFilter.value,
        limit: '100'
    });

    const response = await fetch(`api/admin_saas.php?action=payments&${params.toString()}`, { credentials: 'include' });
    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Falha ao carregar pagamentos filtrados.');
    }

    renderGlobalPayments(data.payments || []);
}

async function loadTenantDetail(tenantId) {
    selectedTenantId = tenantId;
    const response = await fetch(`api/admin_saas.php?action=detail&tenant_id=${encodeURIComponent(tenantId)}`, { credentials: 'include' });
    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Falha ao carregar detalhes do tenant.');
    }

    renderTenantDetail(data);
}

async function updateTenantStatus(tenantId, tenantStatus, subscriptionStatus, trialDays = null) {
    hideAlert();
    try {
        const payload = { tenant_id: tenantId, tenant_status: tenantStatus, subscription_status: subscriptionStatus };
        if (trialDays) {
            payload.trial_days = trialDays;
        }

        const response = await fetch('api/admin_saas.php?action=update_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.viabixCsrfToken || '' },
            credentials: 'include',
            body: JSON.stringify(payload)
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Não foi possível atualizar o status.');
        }

        showAlert(data.message, 'success');
        await loadOverview();
        await loadGlobalWebhooks();
        await loadGlobalPayments();
        if (selectedTenantId) {
            await loadTenantDetail(selectedTenantId);
        }
    } catch (error) {
        showAlert(error.message || 'Erro ao atualizar status.');
    }
}

async function changePlan(tenantId) {
    hideAlert();
    const select = document.getElementById('planSelect');
    const planCode = select?.value;
    if (!planCode) {
        showAlert('Selecione um plano antes de aplicar.');
        return;
    }

    try {
        const response = await fetch('api/admin_saas.php?action=change_plan', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.viabixCsrfToken || '' },
            credentials: 'include',
            body: JSON.stringify({ tenant_id: tenantId, plan_code: planCode, cycle: 'mensal' })
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Não foi possível alterar o plano.');
        }

        showAlert(data.message, 'success');
        await loadOverview();
        await loadGlobalWebhooks();
        await loadGlobalPayments();
        await loadTenantDetail(tenantId);
    } catch (error) {
        showAlert(error.message || 'Erro ao trocar plano.');
    }
}

async function reprocessWebhook(webhookId, tenantId = null) {
    hideAlert();

    try {
        const response = await fetch('api/admin_saas.php?action=reprocess_webhook', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.viabixCsrfToken || '' },
            credentials: 'include',
            body: JSON.stringify({ webhook_id: webhookId })
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Não foi possível reprocessar o webhook.');
        }

        showAlert(data.message, 'success');
        await loadOverview();
        await loadGlobalWebhooks();
        await loadGlobalPayments();

        const targetTenantId = tenantId || selectedTenantId;
        if (targetTenantId) {
            await loadTenantDetail(targetTenantId);
        }
    } catch (error) {
        showAlert(error.message || 'Erro ao reprocessar webhook.');
    }
}

reloadButton.addEventListener('click', async () => {
    try {
        await loadOverview();
        await loadGlobalWebhooks();
        await loadGlobalPayments();
        if (selectedTenantId) {
            await loadTenantDetail(selectedTenantId);
        }
    } catch (error) {
        showAlert(error.message || 'Erro ao atualizar overview.');
    }
});

searchInput.addEventListener('input', renderTenantRows);
statusFilter.addEventListener('change', renderTenantRows);
planFilter.addEventListener('change', renderTenantRows);
tenantPeriodFilter.addEventListener('change', renderTenantRows);
webhookFilterButton.addEventListener('click', async () => {
    try {
        await loadGlobalWebhooks();
    } catch (error) {
        showAlert(error.message || 'Não foi possível carregar webhooks filtrados.');
    }
});
paymentFilterButton.addEventListener('click', async () => {
    try {
        await loadGlobalPayments();
    } catch (error) {
        showAlert(error.message || 'Não foi possível carregar pagamentos filtrados.');
    }
});

document.addEventListener('DOMContentLoaded', async () => {
    try {
        await loadOverview();
        await loadGlobalWebhooks();
        await loadGlobalPayments();
    } catch (error) {
        showAlert(error.message || 'Não foi possível carregar o painel admin.');
    }
});
