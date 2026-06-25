// =========================================
// INTEGRAÇÃO COM PROJETOS
// =========================================

// Variável global para armazenar dados do vínculo
let vinculoAtual = null;

function esconderResumoProjetoVinculado() {
const card = document.getElementById('linkedProjectCard');
if (card) {
    card.classList.remove('visible');
}
}

function textoSeguroProjeto(valor, fallback = '-') {
const texto = String(valor ?? '').trim();
return texto || fallback;
}

function formatarDataProjeto(valor) {
if (!valor) {
    return 'sem atualização';
}

const data = new Date(String(valor).replace(' ', 'T'));
if (Number.isNaN(data.getTime())) {
    return 'sem atualização';
}

return data.toLocaleDateString('pt-BR');
}

function renderizarResumoProjetoVinculado(projeto) {
const card = document.getElementById('linkedProjectCard');
const title = document.getElementById('linkedProjectTitle');
const meta = document.getElementById('linkedProjectMeta');
const progress = document.getElementById('linkedProjectProgress');
const progressText = document.getElementById('linkedProjectProgressText');
const progressWrap = progress?.closest('.progress');

if (!card || !title || !meta || !progress || !progressText) {
    return;
}

const progresso = Math.max(0, Math.min(100, parseInt(projeto.progresso, 10) || 0));
title.textContent = `Projeto #${projeto.id}: ${textoSeguroProjeto(projeto.nome, 'Sem nome')}`;
meta.innerHTML = '';

[
    ['fa-circle-check', textoSeguroProjeto(projeto.status, 'Pendente')],
    ['fa-chart-line', `${progresso}% concluído`],
    ['fa-user', textoSeguroProjeto(projeto.lider, 'Sem líder')],
    ['fa-building', textoSeguroProjeto(projeto.cliente, 'Sem cliente')],
    ['fa-clock', formatarDataProjeto(projeto.updated_at)]
].forEach(([icone, texto]) => {
    const item = document.createElement('span');
    item.innerHTML = `<i class="fas ${icone}"></i>`;
    item.append(document.createTextNode(texto));
    meta.appendChild(item);
});

progress.style.width = `${progresso}%`;
progressText.textContent = `${progresso}%`;
if (progressWrap) {
    progressWrap.setAttribute('aria-valuenow', String(progresso));
}

card.classList.add('visible');
}

// Verificar se a ANVI está vinculada a um projeto
async function verificarVinculoComProjeto() {
const anviNumber = document.getElementById('anviNumber').value;
const revisaoANVI = document.getElementById('revisaoANVI').value;

if (!anviNumber || !revisaoANVI) {
    // Ocultar botões se não há ANVI carregada
    document.getElementById('btnCriarProjeto').style.display = 'none';
    document.getElementById('btnVerProjeto').style.display = 'none';
    document.getElementById('badgeVinculo').style.display = 'none';
    esconderResumoProjetoVinculado();
    return;
}

const anviId = `${anviNumber}_${revisaoANVI}`;

try {
    const response = await fetch(`api/verificar_vinculo.php?anvi_id=${encodeURIComponent(anviId)}`, {
        credentials: 'include'
    });
    
    if (!response.ok) {
        esconderResumoProjetoVinculado();
        return;
    }
    
    vinculoAtual = await response.json();
    
    if (vinculoAtual.tem_vinculo && vinculoAtual.projeto) {
        // ANVI está vinculada a um projeto
        document.getElementById('btnCriarProjeto').style.display = 'none';
        document.getElementById('btnVerProjeto').style.display = 'inline-block';
        document.getElementById('badgeVinculo').style.display = 'inline-block';
        document.getElementById('vinculoStatus').textContent = `Projeto #${vinculoAtual.projeto.id}: ${vinculoAtual.projeto.nome}`;
        renderizarResumoProjetoVinculado(vinculoAtual.projeto);
    } else {
        // ANVI não está vinculada
        document.getElementById('btnCriarProjeto').style.display = 'inline-block';
        document.getElementById('btnVerProjeto').style.display = 'none';
        document.getElementById('badgeVinculo').style.display = 'none';
        esconderResumoProjetoVinculado();
    }
} catch (e) {
    esconderResumoProjetoVinculado();
}
}

// Abrir modal para criar projeto a partir da ANVI
async function abrirModalCriarProjeto() {
const anviNumber = document.getElementById('anviNumber').value;
const revisaoANVI = document.getElementById('revisaoANVI').value;
const nomeAnvi = document.getElementById('productDescription').value;
const cliente = document.getElementById('client').value;

if (!anviNumber || !revisaoANVI) {
    alert('Por favor, salve a ANVI antes de criar um projeto.');
    return;
}

// Buscar líderes disponíveis
let lideresHtml = '<option value="">Selecione um líder</option>';
try {
    const formData = new FormData();
    formData.append('action', 'getLeaders');

    const response = await fetch('Controle_de_projetos/api_mysql.php', {
        method: 'POST',
        credentials: 'include',
        body: formData
    });
    const data = await response.json();
    if (data.success && Array.isArray(data.data)) {
        data.data.forEach(lider => {
            lideresHtml += `<option value="${lider.id}">${lider.name}</option>`;
        });
    }
} catch (e) {
    console.error('Erro ao carregar líderes:', e);
}

// Criar modal dinâmico
const modalHtml = `
    <div class="modal fade" id="modalCriarProjeto" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #0a3d2e, #1a5a3a); color: white;">
                    <h5 class="modal-title">
                        <i class="fas fa-project-diagram me-2"></i>
                        Criar Projeto a partir da ANVI
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>ANVI:</strong> ${anviNumber} Rev. ${revisaoANVI} - ${nomeAnvi}
                    </div>
                    
                    <form id="formCriarProjeto">
                        <div class="mb-3">
                            <label class="form-label">Nome do Projeto *</label>
                            <input type="text" class="form-control" id="nomeProjeto" 
                                value="Projeto - ${nomeAnvi}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricaoProjeto" rows="3"
                                placeholder="Descrição do projeto...">${cliente ? 'Cliente: ' + cliente + ' - ' : ''}Projeto baseado na ANVI ${anviNumber}</textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Líder do Projeto</label>
                                <select class="form-select" id="liderProjeto">
                                    ${lideresHtml}
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Data de Início</label>
                                <input type="date" class="form-control" id="dataInicioProjeto" 
                                    value="${new Date().toISOString().split('T')[0]}">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Data de Término Prevista</label>
                            <input type="date" class="form-control" id="dataFimProjeto">
                        </div>
                        
                        <div class="alert alert-success">
                            <i class="fas fa-dollar-sign me-2"></i>
                            O orçamento do projeto será automaticamente preenchido com o valor final da ANVI.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary" onclick="criarProjetoDaANVI()">
                        <i class="fas fa-check me-2"></i>Criar Projeto
                    </button>
                </div>
            </div>
        </div>
    </div>
`;

// Remover modal anterior se existir
const modalExistente = document.getElementById('modalCriarProjeto');
if (modalExistente) {
    modalExistente.remove();
}

// Adicionar modal ao body
document.body.insertAdjacentHTML('beforeend', modalHtml);

// Mostrar modal
const modal = new bootstrap.Modal(document.getElementById('modalCriarProjeto'));
modal.show();
}

// Criar projeto a partir da ANVI
async function criarProjetoDaANVI() {
const anviNumber = document.getElementById('anviNumber').value;
const revisaoANVI = document.getElementById('revisaoANVI').value;
const anviId = `${anviNumber}_${revisaoANVI}`;

const nomeProjeto = document.getElementById('nomeProjeto').value.trim();
const descricaoProjeto = document.getElementById('descricaoProjeto').value.trim();
const liderProjeto = document.getElementById('liderProjeto').value;
const dataInicio = document.getElementById('dataInicioProjeto').value;
const dataFim = document.getElementById('dataFimProjeto').value;

if (!nomeProjeto) {
    alert('Por favor, preencha o nome do projeto.');
    return;
}

try {
    const response = await fetch('api/criar_projeto_de_anvi.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
            anvi_id: anviId,
            nome_projeto: nomeProjeto,
            descricao: descricaoProjeto,
            lider_id: liderProjeto || null,
            data_inicio: dataInicio,
            data_fim_prevista: dataFim || null
        })
    });
    
    const result = await response.json();
    
    if (response.ok && result.sucesso) {
        // Fechar modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCriarProjeto'));
        modal.hide();
        
        // Mostrar notificação de sucesso
        mostrarNotificacao(`✅ Projeto #${result.projeto_id} criado e vinculado com sucesso!`, 'success');
        
        // Atualizar interface
        await verificarVinculoComProjeto();
        
        // Perguntar se quer abrir o projeto
        if (confirm('Projeto criado com sucesso! Deseja abrir o projeto agora?')) {
            window.open(`Controle_de_projetos/index.php?projeto_id=${result.projeto_id}`, '_blank');
        }
    } else if (response.status === 409) {
        alert(result.erro || 'Esta ANVI já está vinculada a outro projeto.');
    } else {
        alert(result.erro || 'Erro ao criar projeto.');
    }
} catch (e) {
    console.error('Erro ao criar projeto:', e);
    alert('Erro de conexão com o servidor.');
}
}

// Abrir projeto vinculado
function abrirProjetoVinculado() {
if (vinculoAtual && vinculoAtual.projeto) {
    const url = vinculoAtual.projeto.url || `Controle_de_projetos/index.php?projeto_id=${vinculoAtual.projeto.id}`;
    window.open(url, '_blank');
} else {
    alert('Nenhum projeto vinculado a esta ANVI.');
}
}

