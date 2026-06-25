function mostrarNotificacao(mensagem, tipo = 'info') {
    const notificacao = document.createElement('div');
    notificacao.style.position = 'fixed';
    notificacao.style.top = '20px';
    notificacao.style.right = '20px';
    notificacao.style.padding = '15px 25px';
    notificacao.style.borderRadius = '50px';
    notificacao.style.zIndex = '10000';
    notificacao.style.boxShadow = '0 5px 20px rgba(0,0,0,0.2)';
    notificacao.style.animation = 'slideInRight 0.3s';
    notificacao.style.fontWeight = '600';
    
    if (tipo === 'success') {
        notificacao.style.background = 'linear-gradient(145deg, #2e7d32, #1e5622)';
        notificacao.style.color = 'white';
        notificacao.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + mensagem;
    } else if (tipo === 'warning') {
        notificacao.style.background = 'linear-gradient(145deg, #ff8f00, #ff6f00)';
        notificacao.style.color = 'white';
        notificacao.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>' + mensagem;
    } else {
        notificacao.style.background = 'linear-gradient(145deg, #0288d1, #01579b)';
        notificacao.style.color = 'white';
        notificacao.innerHTML = '<i class="fas fa-info-circle me-2"></i>' + mensagem;
    }
    
    document.body.appendChild(notificacao);
    
    setTimeout(() => {
        notificacao.style.animation = 'fadeOut 0.3s';
        setTimeout(() => {
            document.body.removeChild(notificacao);
        }, 300);
    }, 3000);
}

function exportarDREExcel() {
    try {
        const workbook = XLSX.utils.book_new();
        
        const dados = [
            ['DEMONSTRATIVO DE RESULTADOS DO EXERCÍCIO - DRE'],
            ['Período: Mensal'],
            [],
            ['1. RECEITA OPERACIONAL BRUTA'],
            ['Receita Bruta de Vendas', document.getElementById('dreReceitaBruta').textContent],
            [],
            ['2. DEDUÇÕES DA RECEITA BRUTA'],
            ['IPI', document.getElementById('dreIPI').textContent],
            ['ICMS', document.getElementById('dreICMS').textContent],
            ['PIS', document.getElementById('drePIS').textContent],
            ['COFINS', document.getElementById('dreCOFINS').textContent],
            ['ISS', document.getElementById('dreISS').textContent],
            ['TOTAL DAS DEDUÇÕES', document.getElementById('dreTotalDeducoes').textContent],
            [],
            ['3. RECEITA OPERACIONAL LÍQUIDA'],
            ['Receita Líquida', document.getElementById('dreReceitaLiquida').textContent],
            [],
            ['4. CUSTOS DOS PRODUTOS VENDIDOS (CPV)'],
            ['Matéria Prima', document.getElementById('dreCustoMP').textContent],
            ['Insumos', document.getElementById('dreCustoInsumos').textContent],
            ['Componentes', document.getElementById('dreCustoComponentes').textContent],
            ['Embalagem', document.getElementById('dreCustoEmbalagem').textContent],
            ['Mão de Obra Direta (MOD)', document.getElementById('dreCustoMOD').textContent],
            ['Custo do Processo', document.getElementById('dreCustoProcesso').textContent],
            ['Custos Variáveis Indiretos', document.getElementById('dreCustoVariavelIndireto').textContent],
            ['TOTAL CPV', document.getElementById('dreTotalCPV').textContent],
            [],
            ['5. LUCRO BRUTO'],
            ['Lucro Bruto', document.getElementById('dreLucroBruto').textContent],
            ['Margem Bruta', document.getElementById('dreMargemBruta').textContent],
            [],
            ['6. DESPESAS OPERACIONAIS'],
            ['Mão de Obra Indireta (MOI)', document.getElementById('dreDespesaMOI').textContent],
            ['Custos Fixos Indiretos', document.getElementById('dreDespesaCustosFixos').textContent],
            ['TOTAL DESPESAS OPERACIONAIS', document.getElementById('dreTotalDespesas').textContent],
            [],
            ['7. LUCRO OPERACIONAL (EBIT)'],
            ['Lucro Operacional', document.getElementById('dreLucroOperacional').textContent],
            ['Margem Operacional', document.getElementById('dreMargemOperacional').textContent],
            [],
            ['8. IMPOSTOS SOBRE O LUCRO'],
            ['IRPJ', document.getElementById('dreIRPJ').textContent],
            ['CSLL', document.getElementById('dreCSLL').textContent],
            ['IRPJ Adicional', document.getElementById('dreIRPJAdicional').textContent],
            ['TOTAL IMPOSTOS S/ LUCRO', document.getElementById('dreTotalImpostosLucro').textContent],
            [],
            ['9. LUCRO LÍQUIDO DO EXERCÍCIO'],
            ['LUCRO LÍQUIDO', document.getElementById('dreLucroLiquido').textContent],
            ['Margem Líquida', document.getElementById('dreMargemLiquida').textContent],
            [],
            ['INDICADORES FINANCEIROS'],
            ['ROI Anual', document.getElementById('dreROI').textContent],
            ['Payback', document.getElementById('drePayback').textContent],
            ['Ponto de Equilíbrio', document.getElementById('drePE').textContent],
            ['Vida Útil', document.getElementById('dreVidaUtil').textContent]
        ];

        const worksheet = XLSX.utils.aoa_to_sheet(dados);
        XLSX.utils.book_append_sheet(workbook, worksheet, 'DRE');

        const fileName = `DRE_${document.getElementById('anviNumber').value || 'ANVI'}_${new Date().toISOString().split('T')[0]}.xlsx`;
        XLSX.writeFile(workbook, fileName);

    } catch (error) {
        console.error('Erro ao exportar DRE para Excel:', error);
        alert('Erro ao exportar DRE. Verifique o console para mais detalhes.');
    }
}

function updateDateTime() {
    const now = new Date();
    const dateTimeStr = now.toLocaleDateString('pt-BR') + ' ' + now.toLocaleTimeString('pt-BR');
    const element = document.getElementById('currentDateTime');
    if (element) {
        element.textContent = dateTimeStr;
    }
}

function updateReforma2027UI() {
    const regimeTributario = document.getElementById('regimeTributario').value;
    const reformaFields = document.getElementById('reforma2027Fields');
    const pisLabel = document.getElementById('pisLabel');
    const cofinsLabel = document.getElementById('cofinsLabel');
    const icmsLabel = document.getElementById('icmsLabel');
    const issLabel = document.getElementById('issLabel');
    
    const aliquotaPIS = document.getElementById('aliquotaPIS');
    const aliquotaCOFINS = document.getElementById('aliquotaCOFINS');
    const aliquotaICMS = document.getElementById('aliquotaICMS');
    const aliquotaISS = document.getElementById('aliquotaISS');
    
    if (regimeTributario === 'reforma-2027') {
        if (reformaFields) reformaFields.style.display = 'block';
        
        if (pisLabel) pisLabel.innerHTML = 'Substituído por CBS a partir de 2027';
        if (cofinsLabel) cofinsLabel.innerHTML = 'Substituído por CBS a partir de 2027';
        if (icmsLabel) icmsLabel.innerHTML = 'Substituído por IBS a partir de 2027';
        if (issLabel) issLabel.innerHTML = 'Substituído por IBS a partir de 2027';
        
        if (aliquotaPIS) aliquotaPIS.disabled = true;
        if (aliquotaCOFINS) aliquotaCOFINS.disabled = true;
        if (aliquotaICMS) aliquotaICMS.disabled = true;
        if (aliquotaISS) aliquotaISS.disabled = true;
        
    } else {
        if (reformaFields) reformaFields.style.display = 'none';
        
        if (pisLabel) pisLabel.innerHTML = regimeTributario === 'lucro-real' ? 'Não Cumulativo' : 'Cumulativo';
        if (cofinsLabel) cofinsLabel.innerHTML = regimeTributario === 'lucro-real' ? 'Não Cumulativo' : 'Cumulativo';
        if (icmsLabel) icmsLabel.innerHTML = '"Por dentro" - incluído no markup';
        if (issLabel) issLabel.innerHTML = 'Aplicável apenas para serviços';
        
        if (aliquotaPIS) aliquotaPIS.disabled = false;
        if (aliquotaCOFINS) aliquotaCOFINS.disabled = false;
        if (aliquotaICMS) aliquotaICMS.disabled = false;
        if (aliquotaISS) aliquotaISS.disabled = false;
    }
}

// Captura e restauracao de dados movidas para assets/js/anvi-data-io.js.

function updatePisCofinsByRegime() {
    const regimeTributario = document.getElementById('regimeTributario').value;
    const regimePisCofins = document.getElementById('regimePisCofins');
    const aliquotaPIS = document.getElementById('aliquotaPIS');
    const aliquotaCOFINS = document.getElementById('aliquotaCOFINS');

    if (!regimePisCofins || !aliquotaPIS || !aliquotaCOFINS) return;

    if (regimeTributario === 'lucro-real') {
        regimePisCofins.value = 'nao-cumulativo';
        aliquotaPIS.value = 1.65;
        aliquotaCOFINS.value = 7.6;
    } else if (regimeTributario === 'reforma-2027') {
        regimePisCofins.value = 'nao-cumulativo';
        aliquotaPIS.value = 1.65;
        aliquotaCOFINS.value = 7.6;
    } else {
        regimePisCofins.value = 'cumulativo';
        aliquotaPIS.value = 0.65;
        aliquotaCOFINS.value = 3.0;
    }

    updateReforma2027UI();

    if (regimePisCofins) {
        const event = new Event('change', { bubbles: true });
        regimePisCofins.dispatchEvent(event);
    }

    synchronizeCalculations();
}

