const ANVI_ETAPAS = [
    { label: 'Dados Gerais', tabId: 'dados' },
    { label: 'Produto & Especificação', tabId: 'materia-prima' },
    { label: 'Materiais', tabId: 'insumos' },
    { label: 'Processos', tabId: 'fluxo' },
    { label: 'Ferramental', tabId: 'ferramental' },
    { label: 'Custos Indiretos', tabId: 'custos-indiretos' },
    { label: 'Fiscal & Impostos', tabId: 'classificacao-fiscal' },
    { label: 'Resumo Financeiro', tabId: 'memorial-calculos' },
    { label: 'Aprovação', tabId: 'observacoes' }
];
let currentAnviStep = 0;
let anviSavedTimestamp = null;

function formatTime(date) {
    return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

function updateAnviSummary(saved = false) {
    const status = document.getElementById('statusAprovacao')?.value || 'Em andamento';
    const margem = parseFloat(document.getElementById('calcMargemContribuicao')?.textContent) || 0;
    const custoTotal = parseFloat(document.getElementById('totalCustosFixosDisplay')?.value) || 0;
    const preco = parseFloat(document.getElementById('calcPrecoBase')?.textContent) || 0;

    document.getElementById('anviResumoStatus').textContent = status.replace(/-/g, ' ');
    document.getElementById('anviResumoMargem').textContent = margem.toFixed(2);
    document.getElementById('anviResumoCustoTotal').textContent = custoTotal.toFixed(2);
    document.getElementById('anviResumoPreco').textContent = preco.toFixed(2);

    if (saved) {
        anviSavedTimestamp = new Date();
        document.getElementById('anviResumoSalvo').textContent = formatTime(anviSavedTimestamp);
    } else {
        document.getElementById('anviResumoSalvo').textContent = anviSavedTimestamp ? 'alterado' : 'não salvo';
    }
}

function updateStepper() {
    const buttons = document.querySelectorAll('.anvi-stepper .step-button');
    buttons.forEach((button, index) => {
        button.classList.toggle('active', index === currentAnviStep);
    });

    const progress = ((currentAnviStep + 1) / ANVI_ETAPAS.length) * 100;
    const progressBar = document.getElementById('anviStepProgress');
    if (progressBar) {
        progressBar.style.width = `${progress}%`;
        progressBar.setAttribute('aria-valuenow', Math.round(progress));
    }

    const prevButton = document.getElementById('prevStepBtn');
    const nextButton = document.getElementById('nextStepBtn');
    if (prevButton) prevButton.disabled = currentAnviStep === 0;
    if (nextButton) nextButton.disabled = currentAnviStep === ANVI_ETAPAS.length - 1;
}

function goToAnviStep(index) {
    if (index < 0 || index >= ANVI_ETAPAS.length) return;
    currentAnviStep = index;
    const step = ANVI_ETAPAS[index];
    const tabButton = document.getElementById(`${step.tabId}-tab`);
    if (tabButton) {
        tabButton.click();
    }
    updateStepper();
}

function nextAnviStep() {
    goToAnviStep(Math.min(currentAnviStep + 1, ANVI_ETAPAS.length - 1));
}

function previousAnviStep() {
    goToAnviStep(Math.max(currentAnviStep - 1, 0));
}

function renderAnviStepButtons() {
    const container = document.getElementById('anviStepButtons');
    if (!container) return;
    container.innerHTML = ANVI_ETAPAS.map((step, index) =>
        `<button type="button" class="step-button btn btn-sm" data-step-index="${index}">${step.label}</button>`
    ).join('');

    container.querySelectorAll('.step-button').forEach(button => {
        button.addEventListener('click', () => {
            const index = parseInt(button.getAttribute('data-step-index'), 10);
            goToAnviStep(index);
        });
    });
}

function markAnviEdited() {
    updateAnviSummary(false);
}

function registerAnviSummaryEvents() {
    document.querySelectorAll('#viabilityForm input, #viabilityForm select, #viabilityForm textarea').forEach(el => {
        el.addEventListener('input', markAnviEdited);
        el.addEventListener('change', markAnviEdited);
    });

    const prevButton = document.getElementById('prevStepBtn');
    const nextButton = document.getElementById('nextStepBtn');
    if (prevButton) prevButton.addEventListener('click', previousAnviStep);
    if (nextButton) nextButton.addEventListener('click', nextAnviStep);

    document.querySelectorAll('#myTab button[data-bs-toggle="tab"]').forEach(tabButton => {
        tabButton.addEventListener('shown.bs.tab', event => {
            const tabId = event.target.id.replace('-tab', '');
            const nextIndex = ANVI_ETAPAS.findIndex(step => step.tabId === tabId);
            if (nextIndex >= 0) {
                currentAnviStep = nextIndex;
                updateStepper();
            }
        });
    });
}

function setAnviDemoField(id, value) {
    const field = document.getElementById(id);
    if (!field) return;

    field.value = value;
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
}

function carregarDemoComercialANVI() {
    if (!confirm('Carregar dados de exemplo para apresentação comercial? Dados não salvos serão substituídos.')) {
        return;
    }

    const today = new Date().toISOString().split('T')[0];
    const currentYear = new Date().getFullYear();

    setAnviDemoField('anviNumber', `DEMO-${currentYear}-LAM-001`);
    setAnviDemoField('dataANVI', today);
    setAnviDemoField('revisaoANVI', '00');
    setAnviDemoField('lastUpdateDate', today);
    setAnviDemoField('statusAprovacao', 'em-andamento');
    setAnviDemoField('client', 'Montadora Aurora');
    setAnviDemoField('project', 'Para-brisa panorâmico SUV elétrico');
    setAnviDemoField('codigo', 'PA-SUV-EV-2026');
    setAnviDemoField('productDescription', 'Vidro laminado panorâmico com controle solar e serigrafia técnica');
    setAnviDemoField('segment', 'Autos');
    setAnviDemoField('monthlyVolume', '8500');
    setAnviDemoField('desenhoDate', today);
    setAnviDemoField('revisao', 'A');
    setAnviDemoField('glassType', 'laminado');
    setAnviDemoField('geometry', 'esferico');
    setAnviDemoField('thickness', '5.76');
    setAnviDemoField('width', '1480');
    setAnviDemoField('height', '920');
    setAnviDemoField('responsavelTecnica', 'Eng. Marina Costa');
    setAnviDemoField('responsavelComercial', 'Lucas Almeida');
    setAnviDemoField('responsavelEconomica', 'Patricia Nunes');
    setAnviDemoField('responsavelFiscal', 'Renato Fiscal');
    setAnviDemoField('observacaoGeral', 'Demo comercial com volume, margem e payback favoráveis para apresentação executiva.');
    setAnviDemoField('margemLucroMarkup', '22');
    setAnviDemoField('regimeTributario', 'lucro-real');
    setAnviDemoField('aliquotaIPI', '10');
    setAnviDemoField('aliquotaICMS', '18');
    setAnviDemoField('aliquotaPIS', '1.65');
    setAnviDemoField('aliquotaCOFINS', '7.6');
    setAnviDemoField('aliquotaIRPJ', '15');
    setAnviDemoField('aliquotaCSLL', '9');

    goToAnviStep(0);
    updateAnviSummary(false);

    if (typeof validarConsistenciaGeral === 'function') {
        setTimeout(validarConsistenciaGeral, 150);
    }

    alert('Demo comercial carregada. Revise os dados, complete custos se necessário e salve a ANVI para vinculá-la a um projeto.');
}
window.carregarDemoComercialANVI = carregarDemoComercialANVI;

const salvarANVIOriginal = salvarANVI;
async function salvarANVIFase3() {
    const saved = await salvarANVIOriginal();
    if (saved) {
        updateAnviSummary(true);
    }
    return saved;
}
salvarANVI = salvarANVIFase3;
window.salvarANVI = salvarANVIFase3;

window.addEventListener('load', () => {
    renderAnviStepButtons();
    updateStepper();
    updateAnviSummary(false);
    registerAnviSummaryEvents();
});

