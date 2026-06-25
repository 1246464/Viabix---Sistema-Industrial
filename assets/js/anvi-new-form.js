// Modificar a função novaANVI
function novaANVI() {
if (!usuarioAtual) {
    alert('Você precisa estar logado.');
    return;
}

if (usuarioAtual.nivel === 'visitante') {
    alert('Visitantes não podem criar novas ANVIs.');
    return;
}

if (!confirm('Deseja iniciar uma nova ANVI? Todos os dados não salvos serão perdidos.')) {
    return;
}

// Desbloquear ANVI atual se houver
if (anviAtual.id) {
    desbloquearANVI(anviAtual.id);
}

// Reabilitar todos os inputs (caso estivesse em modo somente leitura)
document.querySelectorAll('input, select, textarea, button').forEach(el => {
    el.disabled = false;
});

// Limpar formulário
document.getElementById('anviNumber').value = '';
document.getElementById('dataANVI').value = new Date().toISOString().split('T')[0];
document.getElementById('revisaoANVI').value = '01';
document.getElementById('lastUpdateDate').value = new Date().toISOString().split('T')[0];
document.getElementById('statusAprovacao').value = 'em-andamento';
document.getElementById('client').value = '';
document.getElementById('project').value = '';
document.getElementById('codigo').value = '';
document.getElementById('productDescription').value = '';
document.getElementById('segment').value = '';
document.getElementById('monthlyVolume').value = '1000';
document.getElementById('desenhoDate').value = '';
document.getElementById('revisao').value = '';
document.getElementById('glassType').value = '';
document.getElementById('geometry').value = '';
document.getElementById('thickness').value = '';
document.getElementById('width').value = '';
document.getElementById('height').value = '';
document.getElementById('glassColor').value = '';
document.getElementById('pvbType').value = '';
document.getElementById('responsavelTecnica').value = '';
document.getElementById('responsavelComercial').value = '';
document.getElementById('responsavelEconomica').value = '';
document.getElementById('responsavelFiscal').value = '';
document.getElementById('observacaoGeral').value = '';

document.getElementById('ppap').checked = false;
document.getElementById('viabilidadeTecnica').checked = false;
document.getElementById('viabilidadeEconomica').checked = false;
document.getElementById('viabilidadeComercial').checked = false;
document.getElementById('viabilidadeFiscal').checked = false;

const tabelas = [
    'homologacoesTable',
    'materiaPrimaTable',
    'insumosTable',
    'componentesTable',
    'processTable',
    'toolingTable',
    'materiaisFerramentalTable',
    'materiaisFerramentalMachoTable',
    'materiaisFerramentalMatrizTable',
    'materiaisFerramentalGabaritoTable',
    'embalagemTable',
    'normasTable',
    'maoObraTable',
    'maoObraIndiretaTable',
    'custosIndiretosTable',
    'classificacaoFiscalTable'
];

tabelas.forEach(tableId => {
    const table = document.getElementById(tableId);
    if (table) {
        const tbody = table.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = '';
        }
    }
});

desenhosANVI = [];
renderizarDesenhos();

document.getElementById('observacoesText').value = '';

anviAtual.id = null;
anviAtual.versao = 1;
anviAtual.bloqueada = false;

setTimeout(() => {
    calculateArea();
    updateAllCalculations();
    validarConsistenciaGeral();
}, 100);

mostrarNotificacao('Nova ANVI criada com sucesso!', 'success');
}

