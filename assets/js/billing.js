const alertBox = document.getElementById('alert');
const planGrid = document.getElementById('planGrid');
const invoiceRows = document.getElementById('invoiceRows');
const providerBadge = document.getElementById('providerBadge');
const providerNotePrimary = document.getElementById('providerNotePrimary');
const providerNoteSecondary = document.getElementById('providerNoteSecondary');

const planCatalog = [
    {
        code: 'starter',
        name: 'Starter',
        monthlyPrice: 297,
        annualPrice: 2970,
        features: ['Até 3 usuários', 'ANVI e Projetos', 'Exportação padrão']
    },
    {
        code: 'pro',
        name: 'Pro',
        monthlyPrice: 697,
        annualPrice: 6970,
        features: ['Até 15 usuários', 'API liberada', 'Operação comercial completa']
    },
    {
        code: 'enterprise',
        name: 'Enterprise',
        monthlyPrice: 1497,
        annualPrice: 14970,
        features: ['Usuários ilimitados', 'API + SSO', 'Fluxos avançados e suporte dedicado']
    }
];

let currentSubscription = null;
let latestOpenInvoice = null;
let billingProviders = {
    default: 'manual',
    manual: { enabled: true, mode: 'local', label: 'Manual' },
    asaas: { enabled: false, mode: 'sandbox', label: 'Asaas' }
};

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
}

function formatDate(value) {
    if (!value) return '-';
    return new Date(value).toLocaleDateString('pt-BR');
}

function showAlert(message, type = 'error') {
    alertBox.className = `alert ${type}`;
    alertBox.textContent = message;
    alertBox.style.display = 'block';
}

function getPreferredProvider() {
    return billingProviders?.asaas?.enabled ? 'asaas' : 'manual';
}

function renderProviderState() {
    const provider = getPreferredProvider();

    if (provider === 'asaas') {
        providerBadge.textContent = `Gateway ativo: Asaas (${billingProviders.asaas.mode})`;
        providerNotePrimary.textContent = 'A cobrança é criada no Asaas e o cliente é redirecionado para a URL oficial de pagamento.';
        providerNoteSecondary.textContent = 'Depois do pagamento, o webhook do Asaas atualiza faturas, pagamentos, assinatura e tenant automaticamente.';
        return;
    }

    providerBadge.textContent = 'Modo atual: Manual/local';
    providerNotePrimary.textContent = 'O checkout ainda roda em modo local. A fatura é criada no banco e a confirmação pode ser simulada pelo endpoint de webhook.';
    providerNoteSecondary.textContent = 'Assim que a chave do Asaas for configurada, esta tela passa a abrir a cobrança oficial sem trocar o fluxo principal.';
}

function formatLimit(value) {
    return value === null || value === undefined ? 'Ilimitado' : value;
}

function setSubscriptionHeader(subscription) {
    const badge = document.getElementById('subscriptionBadge');
    const limits = subscription?.plan?.limits || {};
    document.getElementById('planName').textContent = subscription?.plan?.name || '-';
    document.getElementById('billingCycle').textContent = subscription?.cycle || '-';
    document.getElementById('usersLimit').textContent = formatLimit(limits.users);
    document.getElementById('anvisLimit').textContent = formatLimit(limits.anvis_monthly);
    document.getElementById('projectsLimit').textContent = formatLimit(limits.active_projects);
    document.getElementById('renewalDate').textContent = formatDate(subscription?.ends_at || subscription?.trial_until);
    document.getElementById('renewalLabel').textContent = subscription?.status === 'trial' ? 'Fim do trial' : 'Vencimento';
    document.getElementById('daysRemaining').textContent = subscription?.days_remaining != null ? subscription.days_remaining : '-';

    if (!subscription) {
        badge.textContent = 'Sem assinatura';
        badge.className = 'pill warn';
        return;
    }

    badge.textContent = `Status: ${subscription.status}`;
    badge.className = `pill ${subscription.status === 'ativa' ? 'active' : 'warn'}`;
    renderLifecycle(subscription.status);
}

function renderLifecycle(status) {
    const steps = document.querySelectorAll('#lifecycleSteps .lifecycle-step');
    steps.forEach((step) => step.classList.remove('done', 'current'));
    steps[0].classList.add('done');

    if (status === 'trial') {
        steps[1].classList.add('current');
        return;
    }

    steps[1].classList.add('done');
    if (status === 'ativa') {
        steps[2].classList.add('done');
        steps[3].classList.add('current');
        return;
    }

    steps[2].classList.add('current');
}

function renderPlans() {
    planGrid.innerHTML = planCatalog.map((plan) => {
        const current = currentSubscription?.plan?.code === plan.code;
        const buttonLabel = current ? 'Plano atual' : (currentSubscription?.status === 'trial' ? 'Ativar neste plano' : 'Trocar para este plano');
        return `
            <article class="plan-card ${current ? 'current' : ''}">
                <h3>${plan.name}</h3>
                <div class="price">${formatCurrency(plan.monthlyPrice)} <small>/ mês</small></div>
                <div class="note">Anual: ${formatCurrency(plan.annualPrice)}</div>
                <ul>
                    ${plan.features.map((feature) => `<li><i class="fas fa-check-circle"></i><span>${feature}</span></li>`).join('')}
                </ul>
                <button ${current ? 'disabled' : ''} onclick="createCheckout('${plan.code}')">${buttonLabel}</button>
            </article>
        `;
    }).join('');
}

function renderInvoices(invoices) {
    if (!invoices.length) {
        invoiceRows.innerHTML = '<tr><td colspan="6">Nenhuma fatura encontrada.</td></tr>';
        updatePrimaryPaymentButton(null);
        return;
    }

    latestOpenInvoice = invoices.find((invoice) => ['pendente', 'vencida'].includes(invoice.status) && invoice.billing_url) || null;
    updatePrimaryPaymentButton(latestOpenInvoice);

    invoiceRows.innerHTML = invoices.map((invoice) => `
        <tr>
            <td>${invoice.number}</td>
            <td><span class="status ${invoice.status}">${invoice.status}</span></td>
            <td>${invoice.plan_name || '-'}</td>
            <td>${formatCurrency(invoice.amount_total)}</td>
            <td>${formatDate(invoice.due_at)}</td>
            <td>${invoice.billing_url ? `<a href="${invoice.billing_url}" target="_blank" rel="noopener">Abrir</a>` : '-'}</td>
        </tr>
    `).join('');
}

function updatePrimaryPaymentButton(invoice) {
    const button = document.getElementById('primaryPaymentButton');
    if (!invoice) {
        button.style.display = 'none';
        button.removeAttribute('href');
        return;
    }

    button.href = invoice.billing_url;
    button.textContent = invoice.status === 'vencida' ? 'Pagar fatura vencida' : 'Pagar fatura aberta';
    button.insertAdjacentHTML('afterbegin', '<i class="fas fa-credit-card"></i> ');
    button.style.display = 'inline-flex';
}

async function loadSubscription() {
    const data = await ViabixApiCore.getSubscriptionCurrent();
    if (!data._ok || !data.success) {
        throw new Error(data.message || 'Erro ao carregar assinatura.');
    }

    billingProviders = data.billing_providers || billingProviders;
    currentSubscription = data.subscription;
    renderProviderState();
    setSubscriptionHeader(currentSubscription);
    renderPlans();
}

async function loadInvoices() {
    const data = await ViabixApiCore.getBillingInvoices();
    if (!data._ok || !data.success) {
        throw new Error(data.message || 'Erro ao carregar faturas.');
    }

    renderInvoices(data.invoices || []);
}

async function createCheckout(planCode) {
    alertBox.style.display = 'none';
    const provider = getPreferredProvider();

    try {
        const data = await ViabixApiCore.createCheckout({ plan_code: planCode, cycle: 'mensal', provider });
        if (!data._ok || !data.success) {
            throw new Error(data.message || 'Falha ao gerar checkout.');
        }

        showAlert(
            provider === 'asaas'
                ? 'Checkout criado no Asaas. A URL oficial será aberta em seguida.'
                : 'Checkout gerado com sucesso. Uma fatura pendente foi criada para o plano selecionado.',
            'success'
        );
        await loadSubscription();
        await loadInvoices();

        if (data.checkout?.url) {
            window.open(data.checkout.url, '_blank', 'noopener');
        }
    } catch (error) {
        showAlert(error.message || 'Não foi possível gerar checkout.');
    }
}

async function loadAll(force = false) {
    try {
        await loadSubscription();
        await loadInvoices();
        if (force) {
            showAlert('Assinatura atualizada.', 'success');
        }
    } catch (error) {
        showAlert(error.message || 'Não foi possível carregar a área de billing.');
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    await loadAll();
});
