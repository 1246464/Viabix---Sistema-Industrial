const alertBox = document.getElementById('alert');
const submitButton = document.getElementById('submitButton');
const signupForm = document.getElementById('signupForm');
const planInput = document.getElementById('plan_code');
const selectedPlanBox = document.getElementById('selectedPlanBox');
const selectedPlanDescription = document.getElementById('selectedPlanDescription');

// Carregar token CSRF quando a página carrega
async function carregarCsrfToken() {
    try {
        const response = await fetch('api/check_session.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.csrf_token) {
            document.getElementById('csrfToken').value = data.csrf_token;
        }
    } catch (err) {
        console.warn('Erro ao carregar CSRF token:', err);
    }
}

// Chamar ao carregar a página
window.addEventListener('DOMContentLoaded', carregarCsrfToken);

const planCatalog = {
    starter: {
        name: 'Starter',
        description: 'Entrada para equipes menores, com ANVI, projetos e exportação padrão.',
        limits: '3 usuários · 30 ANVIs/mês · 20 projetos ativos'
    },
    pro: {
        name: 'Pro',
        description: 'Plano mais equilibrado para operação comercial em escala, com API liberada e maior capacidade.',
        limits: '15 usuários · ANVIs ilimitadas · projetos ilimitados'
    },
    enterprise: {
        name: 'Enterprise',
        description: 'Conta pensada para operação corporativa, com usuários ilimitados, SSO e suporte dedicado.',
        limits: 'Usuários ilimitados · ANVIs ilimitadas · SSO'
    }
};

function applySelectedPlan(planCode) {
    const normalizedPlan = planCatalog[planCode] ? planCode : 'starter';
    const plan = planCatalog[normalizedPlan];

    planInput.value = normalizedPlan;
    selectedPlanBox.querySelector('strong').textContent = `Plano selecionado para o trial: ${plan.name}`;
    selectedPlanDescription.textContent = `${plan.description} Limites do trial: ${plan.limits}.`;
    document.querySelectorAll('.plan-choice').forEach((button) => {
        button.classList.toggle('active', button.dataset.plan === normalizedPlan);
    });
}

function showAlert(message, type = 'error') {
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = message;
    alertBox.style.display = 'block';
}

function setLoading(isLoading) {
    submitButton.disabled = isLoading;
    submitButton.innerHTML = isLoading
        ? '<i class="fas fa-spinner fa-spin"></i> Criando ambiente...'
        : '<i class="fas fa-rocket"></i> Começar teste grátis';
}

(function hydrateSelectedPlanFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const requestedPlan = (params.get('plan') || '').toLowerCase();
    applySelectedPlan(requestedPlan);
})();

document.getElementById('planChoiceGrid').addEventListener('click', (event) => {
    const button = event.target.closest('.plan-choice');
    if (!button) return;
    applySelectedPlan(button.dataset.plan);
});

signupForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    alertBox.style.display = 'none';
    setLoading(true);

    const payload = Object.fromEntries(new FormData(signupForm).entries());

    try {
        const response = await fetch('api/signup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            showAlert(data.message || 'Não foi possível criar a conta trial.');
            setLoading(false);
            return;
        }

        const planName = data.subscription?.plan_name ? ` no plano ${data.subscription.plan_name}` : '';
        showAlert(`Conta criada com sucesso${planName}. Redirecionando...`, 'success');

        setTimeout(() => {
            window.location.href = 'billing.html';
        }, 1200);
    } catch (error) {
        console.error(error);
        showAlert('Falha de conexão ao criar a conta trial.');
        setLoading(false);
    }
});

(async function verifySession() {
    try {
        const response = await fetch('api/check_session.php', {
            credentials: 'include'
        });
        const data = await response.json();
        if (response.ok && data.logado) {
            window.location.href = 'dashboard.html';
        }
    } catch (error) {
        console.error('Sessão não ativa:', error);
    }
})();
