// CONSTANTES
let ENCARGOS_SOCIAIS = 1.8; // 80% de encargos sobre salário (valor padrão)

// VARIÁVEIS GLOBAIS DE ESTADO
window.totalCustosFixos = 0;
window.totalCustosVariaveisIndiretos = 0;
window.custoFixoPorPeca = 0;
window.custoVariavelPorPeca = 0;
window.vidaUtilMinima = Infinity;
window.rateioBloqueado = false;
window.vidaUtilMeses = 0;
window.currentTargetTable = 'materiaisFerramentalTable';

// CONSTANTES INDUSTRIAIS
const INDUSTRY = {
    MARGEM_MINIMA_RECOMENDADA: 15,
    ROI_MINIMO_RECOMENDADO: 20,
    PAYBACK_MAXIMO_RECOMENDADO: 24,
    HORAS_TRABALHADAS_PADRAO: 176,
    CAPACIDADE_MINIMA_SEGURANCA: 0.2,
    TAXA_JUROS_ANUAL: 12
};

// Operacoes de auth/usuarios movidas para assets/js/anvi-auth-users.js.


// Utilitarios de formatacao movidos para assets/js/anvi-utils.js.


// Operacoes de desenhos movidas para assets/js/anvi-drawings.js.


// =========================================
// FUNÇÕES CORRIGIDAS DO SISTEMA (MANTIDAS DO ORIGINAL)
// =========================================
function updateMateriaisComCredito(tableId, totalInputId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    let totalLiquido = 0;
    let totalCredito = 0;
    const regimePisCofins = document.getElementById('regimePisCofins')?.value || 'nao-cumulativo';
    const isNaoCumulativo = regimePisCofins === 'nao-cumulativo';
    
    const aliquotaPIS = parseFloat(document.getElementById('aliquotaPIS')?.value) || 1.65;
    const aliquotaCOFINS = parseFloat(document.getElementById('aliquotaCOFINS')?.value) || 7.6;

    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input')?.value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price-input')?.value) || 0;
        const ipiAliquota = parseFloat(row.querySelector('.ipi-aliquota-input')?.value) || 0;
        const icmsAliquota = parseFloat(row.querySelector('.icms-aliquota-input')?.value) || 0;

        const valorBruto = qty * unitPrice;
        
        const baseICMS = valorBruto / (1 - icmsAliquota/100);
        const valorICMS = baseICMS * (icmsAliquota/100);
        
        const valorIPI = valorBruto * (ipiAliquota / 100);
        
        const valorTotalComImpostos = valorBruto + valorIPI + valorICMS;

        let creditoIPI = 0;
        let creditoICMS = 0;
        let creditoPIS = 0;
        let creditoCOFINS = 0;

        if (isNaoCumulativo) {
            creditoIPI = valorIPI;
            creditoICMS = valorICMS;
            creditoPIS = valorBruto * (aliquotaPIS / 100);
            creditoCOFINS = valorBruto * (aliquotaCOFINS / 100);
        }

        const creditoTotal = creditoIPI + creditoICMS + creditoPIS + creditoCOFINS;
        const valorLiquido = valorTotalComImpostos - creditoTotal;
        totalLiquido += valorLiquido;
        totalCredito += creditoTotal;

        const totalInput = row.querySelector('.total-price');
        if (totalInput) {
            totalInput.value = valorLiquido.toFixed(2);
        }

        const creditoInput = row.querySelector('.credit-value');
        if (creditoInput) {
            creditoInput.value = creditoTotal.toFixed(2);
        }

        const grossTotal = row.querySelector('.gross-total');
        if (grossTotal) {
            grossTotal.value = valorTotalComImpostos.toFixed(2);
        }
    });

    const totalInput = document.getElementById(totalInputId);
    if (totalInput) {
        totalInput.value = totalLiquido.toFixed(2);
    }

    const taxCreditSection = document.getElementById('taxCreditSection');
    const taxCreditValue = document.getElementById('taxCreditValue');
    if (taxCreditSection && taxCreditValue) {
        if (isNaoCumulativo && totalCredito > 0) {
            taxCreditSection.style.display = 'block';
            taxCreditValue.textContent = 'R$ ' + totalCredito.toFixed(2).replace('.', ',');
        } else {
            taxCreditSection.style.display = 'none';
        }
    }
}

function atualizarCalculoMaoObra(row) {
    const timeInput = row.querySelector('.time-input');
    const priceInput = row.querySelector('.unit-price-input');
    const qtyInput = row.querySelector('.qty-input');
    const totalInput = row.querySelector('.total-price');

    if (timeInput && priceInput && qtyInput && totalInput) {
        const time = parseFloat(timeInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const qty = parseFloat(qtyInput.value) || 1;
        
        atualizarEncargos();
        
        const total = (time / 60) * price * ENCARGOS_SOCIAIS * qty;
        totalInput.value = total.toFixed(2);
    }
}

function updatePontoEquilibrioAnaliseCorrigido() {
    if (window.rateioBloqueado) return;

    try {
        const totalCustosFixos = window.totalCustosFixos || 0;

        const precoBaseElement = document.getElementById('precoBaseAtual');
        let precoBase = 0;
        if (precoBaseElement) {
            const precoText = precoBaseElement.textContent
                .replace('R$ ', '')
                .replace(/\./g, '')
                .replace(',', '.');
            precoBase = parseFloat(precoText) || 0;
        }

        const custoVariavelUnitarioTotal = calcularCustoVariavelUnitario();
        
        console.log('📊 Ponto de Equilíbrio - Valores:');
        console.log('   Custo Variável Total:', custoVariavelUnitarioTotal);
        console.log('   Preço Base:', precoBase);
        console.log('   Custos Fixos:', totalCustosFixos);

        let pontoEquilibrioQtd = 0;
        if (precoBase > custoVariavelUnitarioTotal && precoBase > 0 && totalCustosFixos > 0) {
            pontoEquilibrioQtd = totalCustosFixos / (precoBase - custoVariavelUnitarioTotal);
        } else if (precoBase > 0 && precoBase <= custoVariavelUnitarioTotal) {
            pontoEquilibrioQtd = Infinity;
        }

        pontoEquilibrioQtd = pontoEquilibrioQtd === Infinity ? 999999 : Math.max(0, pontoEquilibrioQtd);

        const pontoEquilibrioValor = pontoEquilibrioQtd * precoBase;
        const volumeMensal = parseFloat(document.getElementById('monthlyVolume').value) || 0;
        const margemSegurancaPercent = volumeMensal > 0 && pontoEquilibrioQtd !== Infinity ? 
            ((volumeMensal - pontoEquilibrioQtd) / volumeMensal) * 100 : 0;

        const resumoPontoEquilibrioQtd = document.getElementById('resumoPontoEquilibrioQtd');
        if (resumoPontoEquilibrioQtd) {
            resumoPontoEquilibrioQtd.textContent = pontoEquilibrioQtd === Infinity ? 
                'Inatingível' : pontoEquilibrioQtd.toFixed(0) + ' unidades';
        }

        const resumoPontoEquilibrioValor = document.getElementById('resumoPontoEquilibrioValor');
        if (resumoPontoEquilibrioValor) {
            resumoPontoEquilibrioValor.textContent = pontoEquilibrioQtd === Infinity ? 
                'R$ 0,00' : formatMoney(pontoEquilibrioValor);
        }

        const resumoMargemSegurancaPercent = document.getElementById('resumoMargemSegurancaPercent');
        if (resumoMargemSegurancaPercent) {
            resumoMargemSegurancaPercent.textContent = pontoEquilibrioQtd === Infinity ? 
                '-100%' : margemSegurancaPercent.toFixed(1) + '%';
        }

        const calcPontoEquilibrioQtd = document.getElementById('calcPontoEquilibrioQtd');
        if (calcPontoEquilibrioQtd) {
            calcPontoEquilibrioQtd.textContent = pontoEquilibrioQtd === Infinity ? '∞' : pontoEquilibrioQtd.toFixed(0);
        }
        
        const calcPontoEquilibrioValor = document.getElementById('calcPontoEquilibrioValor');
        if (calcPontoEquilibrioValor) {
            calcPontoEquilibrioValor.textContent = pontoEquilibrioQtd === Infinity ? '∞' : pontoEquilibrioValor.toFixed(2);
        }
        
        const calcMargemSegurancaPercent = document.getElementById('calcMargemSegurancaPercent');
        if (calcMargemSegurancaPercent) {
            calcMargemSegurancaPercent.textContent = pontoEquilibrioQtd === Infinity ? '-100.0' : margemSegurancaPercent.toFixed(1);
        }

    } catch (error) {
        console.error('Erro no cálculo do ponto de equilíbrio:', error);
    }
}

function validarMetodoRateio() {
    const metodo = document.getElementById('metodoRateio').value;
    const rows = document.querySelectorAll('#custosIndiretosTable tbody tr');
    const warningElement = document.getElementById('rateioMethodWarning');
    
    if (!warningElement) return true;

    let hasInconsistency = false;
    let warningMessage = '';

    if (metodo === 'horas' || metodo === 'custo') {
        const modTable = document.getElementById('maoObraTable');
        const hasMod = modTable && modTable.querySelector('tbody tr') !== null;
        
        if (!hasMod) {
            hasInconsistency = true;
            warningMessage = 'Método baseado em MOD selecionado, mas não há dados de mão de obra direta. O cálculo usará valores padrão.';
        }
    }

    if (metodo === 'area') {
        const area = parseFloat(document.getElementById('area').value) || 0;
        if (area <= 0) {
            hasInconsistency = true;
            warningMessage = 'Método baseado em área selecionado, mas a área da peça não está definida. O cálculo usará valores padrão.';
        }
    }

    if (hasInconsistency) {
        warningElement.style.display = 'block';
        warningElement.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + warningMessage;
    } else {
        warningElement.style.display = 'none';
    }

    return true;
}

function updateCustosIndiretosTotalCorrigidoComBloqueio() {
    const table = document.getElementById('custosIndiretosTable');
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    let totalCustosFixos = 0;
    let totalCustosVariaveis = 0;
    let totalRateado = 0;
    let somaPercentuais = 0;
    let countRows = 0;

    validarMetodoRateio();

    rows.forEach(row => {
        const valorInput = row.querySelector('.custo-fixo-input');
        const tipoSelect = row.querySelector('select');
        const percentualInput = row.querySelector('.rateio-percent-input');
        const rateioValorInput = row.querySelector('.rateio-valor');

        if (valorInput && tipoSelect && percentualInput && rateioValorInput) {
            const valor = parseFloat(valorInput.value) || 0;
            let percentualRateio = parseFloat(percentualInput.value) || 100;

            if (percentualRateio > 100) {
                percentualRateio = 100;
                percentualInput.value = 100;
            }

            somaPercentuais += percentualRateio;
            countRows++;

            const valorRateado = valor * (percentualRateio / 100);

            if (tipoSelect.value === 'fixo') {
                totalCustosFixos += valorRateado;
            } else {
                totalCustosVariaveis += valorRateado;
            }

            totalRateado += valorRateado;
            rateioValorInput.value = valorRateado.toFixed(2);
        }
    });

    const rateioBloqueioError = document.getElementById('rateioBloqueioError');
    const painelValidacao = document.getElementById('painelValidacao');
    const memorialCard = document.querySelector('#memorial-calculos .card');

    const estavaBloqueado = window.rateioBloqueado;
    
    if (countRows > 0 && somaPercentuais > 100) {
        window.rateioBloqueado = true;
        if (rateioBloqueioError) {
            rateioBloqueioError.style.display = 'block';
        }
        
        if (memorialCard) {
            memorialCard.classList.add('calculation-blocked');
        }

        if (painelValidacao) {
            painelValidacao.style.display = 'block';
            painelValidacao.innerHTML = `
                <div class="consistency-error">
                    <h6 class="fw-bold"><i class="fas fa-ban me-2"></i>ERRO CRÍTICO - CÁLCULOS BLOQUEADOS POR SEGURANÇA</h6>
                    <p class="mb-2"><strong>Soma dos percentuais de rateio: ${somaPercentuais.toFixed(1)}% > 100%</strong></p>
                    <p class="mb-0">Ajuste os percentuais na aba "Custos Indiretos" para valores ≤ 100%.</p>
                </div>
            `;
        }
        
        const warningDiv = document.getElementById('custoIndiretoWarning');
        const warningTextSpan = document.getElementById('custoIndiretoWarningText');
        if (warningDiv && warningTextSpan) {
            warningDiv.style.display = 'block';
            warningTextSpan.innerHTML = `🚫 SOMA DOS PERCENTUAIS (${somaPercentuais.toFixed(1)}%) > 100% - CÁLCULOS BLOQUEADOS.`;
        }
        
    } else {
        window.rateioBloqueado = false;
        if (rateioBloqueioError) rateioBloqueioError.style.display = 'none';
        if (memorialCard) {
            memorialCard.classList.remove('calculation-blocked');
        }
        
        const warningDiv = document.getElementById('custoIndiretoWarning');
        if (warningDiv && countRows > 0 && somaPercentuais > 0) {
            if (somaPercentuais < 100) {
                warningDiv.style.display = 'block';
                document.getElementById('custoIndiretoWarningText').innerHTML = 
                    `✅ SOMA DOS PERCENTUAIS (${somaPercentuais.toFixed(1)}%) < 100% - CÁLCULOS LIBERADOS.`;
            } else {
                warningDiv.style.display = 'none';
            }
        } else {
            if (warningDiv) warningDiv.style.display = 'none';
        }
        
        if (estavaBloqueado && !window.rateioBloqueado) {
            setTimeout(() => {
                updateAllCalculations();
            }, 100);
        }
    }

    const totalCustosIndiretos = document.getElementById('totalCustosIndiretos');
    if (totalCustosIndiretos) {
        totalCustosIndiretos.value = totalRateado.toFixed(2);
    }

    const totalCustosFixosDisplay = document.getElementById('totalCustosFixosDisplay');
    if (totalCustosFixosDisplay) {
        totalCustosFixosDisplay.value = totalCustosFixos.toFixed(2);
    }

    const totalCustosVariaveisIndiretosDisplay = document.getElementById('totalCustosVariaveisIndiretosDisplay');
    if (totalCustosVariaveisIndiretosDisplay) {
        totalCustosVariaveisIndiretosDisplay.value = totalCustosVariaveis.toFixed(2);
    }

    const monthlyVolume = parseFloat(document.getElementById('monthlyVolume').value) || 1;
    const custoFixoPorPeca = monthlyVolume > 0 ? totalCustosFixos / monthlyVolume : 0;
    const custoVariavelPorPeca = monthlyVolume > 0 ? totalCustosVariaveis / monthlyVolume : 0;

    const custoFixoPorPecaDisplay = document.getElementById('custoFixoPorPecaDisplay');
    if (custoFixoPorPecaDisplay) {
        custoFixoPorPecaDisplay.value = custoFixoPorPeca.toFixed(2);
    }

    const custoVariavelIndiretoPorPecaDisplay = document.getElementById('custoVariavelIndiretoPorPecaDisplay');
    if (custoVariavelIndiretoPorPecaDisplay) {
        custoVariavelIndiretoPorPecaDisplay.value = custoVariavelPorPeca.toFixed(2);
    }

    window.totalCustosFixos = totalCustosFixos;
    window.totalCustosVariaveisIndiretos = totalCustosVariaveis;
    window.custoFixoPorPeca = custoFixoPorPeca;
    window.custoVariavelPorPeca = custoVariavelPorPeca;
}

function calcularCapacidadeProdutiva() {
    try {
        const rows = document.querySelectorAll('#processTable tbody tr');
        if (rows.length === 0) return { capacidadeMaxima: 0, folga: 0, gargalo: 'Nenhum processo definido' };

        let gargalo = Infinity;
        let processoGargalo = '';

        rows.forEach(row => {
            const efficiency = parseFloat(row.querySelector('.efficiency-input')?.value) || 100;
            const output = parseFloat(row.querySelector('.output-input')?.value) || 0;

            if (output > 0) {
                const producaoEfetiva = output * (efficiency / 100);
                if (producaoEfetiva < gargalo) {
                    gargalo = producaoEfetiva;
                    processoGargalo = row.querySelector('td:first-child input')?.value || 'Processo desconhecido';
                }
            }
        });

        if (gargalo === Infinity) {
            return { capacidadeMaxima: 0, folga: 0, gargalo: 'Defina a produção por hora nos processos' };
        }

        const horasTrabalhadas = parseFloat(document.getElementById('horasTrabalhadasMes').value) || 176;
        const capacidadeMaxima = gargalo * horasTrabalhadas;
        const volumeMensal = parseFloat(document.getElementById('monthlyVolume').value) || 0;

        const folga = capacidadeMaxima > 0 ? ((capacidadeMaxima - volumeMensal) / capacidadeMaxima) * 100 : 0;

        return {
            capacidadeMaxima: Math.round(capacidadeMaxima),
            folga: parseFloat(folga.toFixed(1)),
            gargalo: processoGargalo
        };
    } catch (error) {
        console.error('Erro ao calcular capacidade produtiva:', error);
        return { capacidadeMaxima: 0, folga: 0, gargalo: 'Erro no cálculo' };
    }
}

function getTotalFerramentalPorPeca() {
    const tabelasPerPiece = [
        'totalToolingPerPiece',
        'totalMateriaisFerramentalPerPiece',
        'totalMateriaisFerramentalMachoPerPiece',
        'totalMateriaisFerramentalMatrizPerPiece',
        'totalMateriaisFerramentalGabaritoPerPiece'
    ];

    let total = 0;
    tabelasPerPiece.forEach(tableId => {
        total += parseFloat(document.getElementById(tableId)?.value) || 0;
    });
    return total;
}

function calcularMargemContribuicao() {
    try {
        const precoBaseElement = document.getElementById('precoBaseAtual');
        let precoBase = 0;
        if (precoBaseElement) {
            const precoText = precoBaseElement.textContent
                .replace('R$ ', '')
                .replace(/\./g, '')
                .replace(',', '.');
            precoBase = parseFloat(precoText) || 0;
        }

        const totalMateriaPrima = parseFloat(document.getElementById('totalMateriaPrima')?.value) || 0;
        const totalInsumos = parseFloat(document.getElementById('totalInsumos')?.value) || 0;
        const totalComponentes = parseFloat(document.getElementById('totalComponentes')?.value) || 0;
        const totalEmbalagem = parseFloat(document.getElementById('totalEmbalagem')?.value) || 0;

        let totalFerramentalPorPeca = getTotalFerramentalPorPeca();

        const totalMaoObraDireta = parseFloat(document.getElementById('totalMaoObraDireta')?.value) || 0;
        const totalMaoObraIndireta = parseFloat(document.getElementById('totalMaoObraIndireta')?.value) || 0;
        const totalProcesso = parseFloat(document.getElementById('totalProcesso')?.value) || 0;
        const custoVariavelPorPeca = window.custoVariavelPorPeca || 0;

        const custoVariavelUnitarioTotal = totalMateriaPrima + totalInsumos + totalComponentes + totalEmbalagem + 
                                         totalFerramentalPorPeca + 
                                         totalMaoObraDireta + totalMaoObraIndireta + totalProcesso + custoVariavelPorPeca;

        const margemContribuicao = precoBase - custoVariavelUnitarioTotal;
        const indiceContribuicao = precoBase > 0 ? (margemContribuicao / precoBase) * 100 : 0;

        return {
            margem: margemContribuicao,
            indice: indiceContribuicao
        };
    } catch (error) {
        console.error('Erro ao calcular margem de contribuição:', error);
        return { margem: 0, indice: 0 };
    }
}

function atualizarPainelCapacidade() {
    const painel = document.getElementById('painelCapacidade');
    const validacoesContainer = document.getElementById('validacoesCapacidade');
    const dataHoraSpan = document.getElementById('dataHoraValidacao');
    
    if (!painel || !validacoesContainer) return;

    try {
        const capacidade = calcularCapacidadeProdutiva();
        const margemContrib = calcularMargemContribuicao();
        
        const volumeMensal = parseFloat(document.getElementById('monthlyVolume').value) || 0;
        const precoBaseElement = document.getElementById('precoBaseAtual');
        let precoBase = 0;
        if (precoBaseElement) {
            const precoText = precoBaseElement.textContent
                .replace('R$ ', '')
                .replace(/\./g, '')
                .replace(',', '.');
            precoBase = parseFloat(precoText) || 0;
        }
        
        const custoVariavelUnitario = calcularCustoVariavelUnitario();
        const margemContribuicao = precoBase - custoVariavelUnitario;
        
        const roi = parseFloat(document.getElementById('dreROI')?.textContent?.replace('%', '').replace(',', '.')) || 0;
        const payback = parseFloat(document.getElementById('drePayback')?.textContent?.replace(' meses', '').replace(',', '.')) || 0;
        const vidaUtil = window.vidaUtilMeses || 0;
        
        const validacoes = [];
        
        if (capacidade.capacidadeMaxima > 0) {
            validacoes.push({
                texto: `Capacidade produtiva adequada (${capacidade.capacidadeMaxima.toFixed(0)} peças/mês) - Folga: ${capacidade.folga.toFixed(1)}%`,
                valida: capacidade.folga >= 10
            });
        }
        
        validacoes.push({
            texto: `Preço de venda cobre custos variáveis com impostos (Margem de contribuição: R$ ${margemContribuicao.toFixed(2).replace('.', ',')})`,
            valida: margemContribuicao > 0
        });
        
        validacoes.push({
            texto: `ROI adequado: ${roi.toFixed(1)}%`,
            valida: roi >= INDUSTRY.ROI_MINIMO_RECOMENDADO
        });
        
        validacoes.push({
            texto: `Payback rápido: ${payback.toFixed(1)} meses`,
            valida: payback <= INDUSTRY.PAYBACK_MAXIMO_RECOMENDADO
        });
        
        validacoes.push({
            texto: `Vida útil do ferramental: ${vidaUtil.toFixed(1)} meses`,
            valida: vidaUtil >= 12
        });
        
        const margemLucro = parseFloat(document.getElementById('margemLucroMarkup')?.value) || 0;
        validacoes.push({
            texto: `Margem de lucro adequada (${margemLucro.toFixed(1)}%)`,
            valida: margemLucro >= INDUSTRY.MARGEM_MINIMA_RECOMENDADA
        });
        
        let html = '';
        validacoes.forEach(v => {
            html += `<span class="item"><i class="fas fa-${v.valida ? 'check-circle text-success' : 'exclamation-triangle text-warning'}"></i> ${v.texto}</span>`;
        });
        
        validacoesContainer.innerHTML = html;
        
        const now = new Date();
        dataHoraSpan.textContent = now.toLocaleDateString('pt-BR') + ' ' + now.toLocaleTimeString('pt-BR');
        
        painel.style.display = 'flex';
        
    } catch (error) {
        console.error('Erro ao atualizar painel de capacidade:', error);
    }
}

function calcularCustoVariavelUnitario() {
    const totalMateriaPrima = parseFloat(document.getElementById('totalMateriaPrima')?.value) || 0;
    const totalInsumos = parseFloat(document.getElementById('totalInsumos')?.value) || 0;
    const totalComponentes = parseFloat(document.getElementById('totalComponentes')?.value) || 0;
    const totalEmbalagem = parseFloat(document.getElementById('totalEmbalagem')?.value) || 0;

    let totalFerramentalPorPeca = getTotalFerramentalPorPeca();

    const totalMaoObraDireta = parseFloat(document.getElementById('totalMaoObraDireta')?.value) || 0;
    const totalMaoObraIndireta = parseFloat(document.getElementById('totalMaoObraIndireta')?.value) || 0;
    const totalProcesso = parseFloat(document.getElementById('totalProcesso')?.value) || 0;
    const custoVariavelPorPeca = window.custoVariavelPorPeca || 0;

    return totalMateriaPrima + totalInsumos + totalComponentes + totalEmbalagem + 
           totalFerramentalPorPeca + totalMaoObraDireta + totalMaoObraIndireta + totalProcesso + custoVariavelPorPeca;
}

function updateMemorialCalculationsCorrigidoFinal() {
    if (window.rateioBloqueado) return;

    try {
        const width = parseFloat(document.getElementById('width').value) || 0;
        const height = parseFloat(document.getElementById('height').value) || 0;
        const area = (width * height) / 1000000;
        const commercialArea = ((width + 40) * (height + 40)) / 1000000;

        document.getElementById('calcWidth').textContent = width;
        document.getElementById('calcHeight').textContent = height;
        document.getElementById('calcAreaResult').textContent = area.toFixed(4);
        document.getElementById('calcCommercialAreaResult').textContent = commercialArea.toFixed(4);

        const totalMateriaPrima = parseFloat(document.getElementById('totalMateriaPrima')?.value) || 0;
        const totalInsumos = parseFloat(document.getElementById('totalInsumos')?.value) || 0;
        const totalComponentes = parseFloat(document.getElementById('totalComponentes')?.value) || 0;
        const totalEmbalagem = parseFloat(document.getElementById('totalEmbalagem')?.value) || 0;

        let totalFerramentalPorPeca = getTotalFerramentalPorPeca();

        const totalMaoObraDireta = parseFloat(document.getElementById('totalMaoObraDireta')?.value) || 0;
        const totalMaoObraIndireta = parseFloat(document.getElementById('totalMaoObraIndireta')?.value) || 0;
        const totalProcesso = parseFloat(document.getElementById('totalProcesso')?.value) || 0;
        const custoFixoPorPeca = window.custoFixoPorPeca || 0;
        const custoVariavelPorPeca = window.custoVariavelPorPeca || 0;

        document.getElementById('calcMateriaPrimaResult').textContent = totalMateriaPrima.toFixed(2);
        document.getElementById('calcInsumosResult').textContent = totalInsumos.toFixed(2);
        document.getElementById('calcComponentesResult').textContent = totalComponentes.toFixed(2);
        document.getElementById('calcEmbalagemResult').textContent = totalEmbalagem.toFixed(2);
        document.getElementById('calcFerramentalResult').textContent = (parseFloat(document.getElementById('totalToolingPerPiece')?.value) || 0).toFixed(5);
        document.getElementById('calcMateriaisFerramentalResult').textContent = (parseFloat(document.getElementById('totalMateriaisFerramentalPerPiece')?.value) || 0).toFixed(5);
        document.getElementById('calcMaoObraDiretaResult').textContent = totalMaoObraDireta.toFixed(2);
        document.getElementById('calcMaoObraIndiretaResult').textContent = totalMaoObraIndireta.toFixed(2);
        document.getElementById('calcCustosIndiretosResult').textContent = custoFixoPorPeca.toFixed(2);
        document.getElementById('calcCustosVariaveisIndiretosResult').textContent = custoVariavelPorPeca.toFixed(2);
        document.getElementById('calcProcessoResult').textContent = totalProcesso.toFixed(2);

        const custoUnitarioTotal = totalMateriaPrima + totalInsumos + totalComponentes + totalEmbalagem + 
                                 totalFerramentalPorPeca + 
                                 totalMaoObraDireta + totalMaoObraIndireta + totalProcesso + 
                                 custoFixoPorPeca + custoVariavelPorPeca;

        document.getElementById('calcCustoUnitarioResult').textContent = custoUnitarioTotal.toFixed(2);

        const margemLucro = parseFloat(document.getElementById('margemLucroMarkup')?.value) || 0;
        const ipi = parseFloat(document.getElementById('aliquotaIPI')?.value) || 0;
        const icms = parseFloat(document.getElementById('aliquotaICMS')?.value) || 0;
        const pis = parseFloat(document.getElementById('aliquotaPIS')?.value) || 0;
        const cofins = parseFloat(document.getElementById('aliquotaCOFINS')?.value) || 0;
        const iss = parseFloat(document.getElementById('aliquotaISS')?.value) || 0;

        const regimeTributario = document.getElementById('regimeTributario')?.value || 'lucro-real';
        const percentualPresumido = parseFloat(document.getElementById('percentualPresumido')?.value) || 8;

        let aliquotaIRPJCSLLFaturamento = 0;
        if (regimeTributario === 'lucro-presumido' || regimeTributario === 'reforma-2027') {
            const irpj = parseFloat(document.getElementById('aliquotaIRPJ')?.value) || 15;
            const csll = parseFloat(document.getElementById('aliquotaCSLL')?.value) || 9;
            aliquotaIRPJCSLLFaturamento = (irpj + csll) * (percentualPresumido / 100);
        }

        const aliquotaCBS = parseFloat(document.getElementById('aliquotaCBS')?.value) || 12.5;
        const aliquotaIBS = parseFloat(document.getElementById('aliquotaIBS')?.value) || 14;
        const aliquotaIS = parseFloat(document.getElementById('aliquotaIS')?.value) || 0;

        let impostosPorDentro = 0;
        if (regimeTributario === 'reforma-2027') {
            impostosPorDentro = aliquotaIBS + aliquotaCBS + aliquotaIS + aliquotaIRPJCSLLFaturamento;
        } else {
            impostosPorDentro = icms + pis + cofins + iss + aliquotaIRPJCSLLFaturamento;
        }
        
        const somaMarkup = impostosPorDentro + margemLucro;

        let markup = 1;
        if (somaMarkup < 100) {
            markup = 1 / (1 - somaMarkup / 100);
        }

        document.getElementById('calcMarkupValue').textContent = markup.toFixed(4);

        const precoBase = custoUnitarioTotal * markup;
        document.getElementById('calcPrecoBase').textContent = precoBase.toFixed(2);

        const precoFinal = precoBase * (1 + ipi / 100);
        document.getElementById('calcPrecoFinal').textContent = precoFinal.toFixed(2);

        const totalCustosFixos = window.totalCustosFixos || 0;
        
        const custoVariavelUnitarioTotal = totalMateriaPrima + totalInsumos + totalComponentes + totalEmbalagem + 
                                         totalFerramentalPorPeca + 
                                         totalMaoObraDireta + totalMaoObraIndireta + totalProcesso + custoVariavelPorPeca;

        const volumeMensal = parseFloat(document.getElementById('monthlyVolume').value) || 0;

        let pontoEquilibrioQtd = 0;
        if (precoBase > custoVariavelUnitarioTotal && precoBase > 0 && totalCustosFixos > 0) {
            pontoEquilibrioQtd = totalCustosFixos / (precoBase - custoVariavelUnitarioTotal);
        } else if (precoBase > 0 && precoBase <= custoVariavelUnitarioTotal) {
            pontoEquilibrioQtd = Infinity;
        }

        pontoEquilibrioQtd = pontoEquilibrioQtd === Infinity ? 999999 : Math.max(0, pontoEquilibrioQtd);

        document.getElementById('calcPontoEquilibrioQtd').textContent = pontoEquilibrioQtd === Infinity ? '∞' : pontoEquilibrioQtd.toFixed(0);

        const pontoEquilibrioValor = pontoEquilibrioQtd * precoBase;
        document.getElementById('calcPontoEquilibrioValor').textContent = pontoEquilibrioQtd === Infinity ? '∞' : pontoEquilibrioValor.toFixed(2);

        const margemSegurancaPercent = volumeMensal > 0 && pontoEquilibrioQtd !== Infinity ? 
            ((volumeMensal - pontoEquilibrioQtd) / volumeMensal) * 100 : 0;
        document.getElementById('calcMargemSegurancaPercent').textContent = pontoEquilibrioQtd === Infinity ? '-100.0' : margemSegurancaPercent.toFixed(1);

        const output = parseFloat(document.querySelector('#processTable .output-input')?.value) || 0;
        const efficiency = parseFloat(document.querySelector('#processTable .efficiency-input')?.value) || 100;
        const producaoEfetiva = output * (efficiency / 100);

        document.getElementById('calcProducaoEfetiva').textContent = producaoEfetiva.toFixed(2);

        const capacidade = calcularCapacidadeProdutiva();
        document.getElementById('calcCapacidadeMaxima').textContent = capacidade.capacidadeMaxima.toFixed(0);
        document.getElementById('calcVolumeMensal').textContent = volumeMensal.toFixed(0);
        document.getElementById('calcFolgaCapacidade').textContent = capacidade.folga.toFixed(1);

        const margemContrib = calcularMargemContribuicao();
        document.getElementById('calcMargemContribuicao').textContent = margemContrib.margem.toFixed(2);
        document.getElementById('calcIndiceContribuicao').textContent = margemContrib.indice.toFixed(1);

        const encargosPercent = parseFloat(document.getElementById('encargosSociais')?.value) || 80;
        document.getElementById('encargosDisplay').textContent = encargosPercent + '%';

    } catch (error) {
        console.error('Erro ao atualizar memorial de cálculos:', error);
    }
}

function updatePrecoSugeridoCorrigidoComIRPJCSLL() {
    if (window.rateioBloqueado) return;

    const custoUnitarioTotalElement = document.getElementById('resumoCustoUnitario');
    let custoUnitarioTotal = 0;
    if (custoUnitarioTotalElement) {
        const custoText = custoUnitarioTotalElement.textContent
            .replace('R$ ', '')
            .replace(/\./g, '')
            .replace(',', '.');
        custoUnitarioTotal = parseFloat(custoText) || 0;
    }

    const margemLucro = parseFloat(document.getElementById('margemLucroMarkup')?.value) || 0;
    const regimeTributario = document.getElementById('regimeTributario')?.value || 'lucro-real';
    const percentualPresumido = parseFloat(document.getElementById('percentualPresumido')?.value) || 8;

    let aliquotaPIS = parseFloat(document.getElementById('aliquotaPIS')?.value) || 0;
    let aliquotaCOFINS = parseFloat(document.getElementById('aliquotaCOFINS')?.value) || 0;
    let icms = parseFloat(document.getElementById('aliquotaICMS')?.value) || 0;
    let iss = parseFloat(document.getElementById('aliquotaISS')?.value) || 0;
    
    const aliquotaCBS = parseFloat(document.getElementById('aliquotaCBS')?.value) || 12.5;
    const aliquotaIBS = parseFloat(document.getElementById('aliquotaIBS')?.value) || 14;
    const aliquotaIS = parseFloat(document.getElementById('aliquotaIS')?.value) || 0;
    
    const ipi = parseFloat(document.getElementById('aliquotaIPI')?.value) || 0;

    let aliquotaIRPJCSLLFaturamento = 0;
    
    if (regimeTributario === 'lucro-presumido' || regimeTributario === 'reforma-2027') {
        const irpj = parseFloat(document.getElementById('aliquotaIRPJ')?.value) || 15;
        const csll = parseFloat(document.getElementById('aliquotaCSLL')?.value) || 9;
        aliquotaIRPJCSLLFaturamento = (irpj + csll) * (percentualPresumido / 100);
    } else if (regimeTributario === 'lucro-real') {
        aliquotaIRPJCSLLFaturamento = 0;
    } else if (regimeTributario === 'simples') {
        aliquotaIRPJCSLLFaturamento = 0;
    }

    let impostosPorDentro = 0;
    let somaMarkup = 0;
    
    if (regimeTributario === 'reforma-2027') {
        impostosPorDentro = aliquotaIBS + aliquotaCBS + aliquotaIS + aliquotaIRPJCSLLFaturamento;
    } else if (regimeTributario === 'simples') {
        impostosPorDentro = 6.0;
    } else {
        impostosPorDentro = icms + aliquotaPIS + aliquotaCOFINS + iss + aliquotaIRPJCSLLFaturamento;
    }
    
    somaMarkup = impostosPorDentro + margemLucro;

    let markup = 1;
    if (somaMarkup < 100) {
        markup = 1 / (1 - somaMarkup / 100);
    } else {
        console.error('ERRO: Soma dos percentuais de markup excede 100%');
        markup = 2;
    }

    const precoBase = custoUnitarioTotal * markup;
    const precoFinal = precoBase * (1 + ipi / 100);

    const custoUnitarioBase = document.getElementById('custoUnitarioBase');
    if (custoUnitarioBase) custoUnitarioBase.textContent = formatMoney(custoUnitarioTotal);

    const markupAtual = document.getElementById('markupAtual');
    if (markupAtual) markupAtual.textContent = markup.toFixed(4);

    const precoBaseAtual = document.getElementById('precoBaseAtual');
    if (precoBaseAtual) precoBaseAtual.textContent = formatMoney(precoBase);

    const ipiAtual = document.getElementById('ipiAtual');
    if (ipiAtual) ipiAtual.textContent = ipi.toFixed(2) + '%';

    const precoSugeridoDestaque = document.getElementById('precoSugeridoDestaque');
    if (precoSugeridoDestaque) {
        precoSugeridoDestaque.textContent = formatMoney(precoFinal);
    }

    const resumoMarkup = document.getElementById('resumoMarkup');
    if (resumoMarkup) resumoMarkup.textContent = markup.toFixed(4);

    const resumoIPI = document.getElementById('resumoIPI');
    if (resumoIPI) resumoIPI.textContent = ipi.toFixed(2) + '%';

    const resumoICMS = document.getElementById('resumoICMS');
    if (resumoICMS) resumoICMS.textContent = (regimeTributario === 'reforma-2027' ? aliquotaIBS.toFixed(2) : icms.toFixed(2)) + '%';

    const resumoPIS = document.getElementById('resumoPIS');
    if (resumoPIS) resumoPIS.textContent = (regimeTributario === 'reforma-2027' ? aliquotaCBS.toFixed(2) : aliquotaPIS.toFixed(2)) + '%';

    const resumoCOFINS = document.getElementById('resumoCOFINS');
    if (resumoCOFINS) resumoCOFINS.textContent = (regimeTributario === 'reforma-2027' ? '0' : aliquotaCOFINS.toFixed(2)) + '%';

    const resumoISS = document.getElementById('resumoISS');
    if (resumoISS) resumoISS.textContent = (regimeTributario === 'reforma-2027' ? '0' : iss.toFixed(2)) + '%';

    const resumoIRPJCSLLFaturamento = document.getElementById('resumoIRPJCSLLFaturamento');
    if (resumoIRPJCSLLFaturamento) {
        resumoIRPJCSLLFaturamento.textContent = aliquotaIRPJCSLLFaturamento.toFixed(2) + '%';
    }

    const resumoMargemLucroMarkup = document.getElementById('resumoMargemLucroMarkup');
    if (resumoMargemLucroMarkup) resumoMargemLucroMarkup.textContent = margemLucro.toFixed(2) + '%';

    const cargaTributariaPercent = precoBase > 0 ? (impostosPorDentro) : 0;
    const resumoTotalImpostos = document.getElementById('resumoTotalImpostos');
    if (resumoTotalImpostos) {
        resumoTotalImpostos.textContent = (cargaTributariaPercent).toFixed(2) + '%';
    }

    const markupDemonstracao = document.getElementById('markupDemonstracao');
    const markupFormulaDetalhada = document.getElementById('markupFormulaDetalhada');
    
    if (markupDemonstracao) {
        if (regimeTributario === 'reforma-2027') {
            markupDemonstracao.innerHTML = `Markup = 1 / (1 - (IBS ${aliquotaIBS.toFixed(2)}% + CBS ${aliquotaCBS.toFixed(2)}% + IS ${aliquotaIS.toFixed(2)}% + IRPJ/CSLL ${aliquotaIRPJCSLLFaturamento.toFixed(2)}% + Margem ${margemLucro.toFixed(2)}%) / 100) = 1 / (1 - ${somaMarkup.toFixed(2)}/100) = 1 / ${(1 - somaMarkup/100).toFixed(4)} = ${markup.toFixed(4)}`;
            if (markupFormulaDetalhada) {
                markupFormulaDetalhada.innerHTML = `Markup = 1 ÷ (1 - (IBS + CBS + IS + IRPJ/CSLL × %Presumido + Margem)/100)<br>Markup = 1 ÷ (1 - (${aliquotaIBS.toFixed(2)}% + ${aliquotaCBS.toFixed(2)}% + ${aliquotaIS.toFixed(2)}% + ${aliquotaIRPJCSLLFaturamento.toFixed(2)}% + ${margemLucro.toFixed(2)}%)/100)`;
            }
        } else if (regimeTributario === 'lucro-real') {
            markupDemonstracao.innerHTML = `Markup = 1 / (1 - (ICMS ${icms.toFixed(2)}% + PIS ${aliquotaPIS.toFixed(2)}% + COFINS ${aliquotaCOFINS.toFixed(2)}% + ISS ${iss.toFixed(2)}% + Margem ${margemLucro.toFixed(2)}%) / 100) = 1 / (1 - ${somaMarkup.toFixed(2)}/100) = 1 / ${(1 - somaMarkup/100).toFixed(4)} = ${markup.toFixed(4)} (IRPJ/CSLL calculados sobre o lucro real)`;
            if (markupFormulaDetalhada) {
                markupFormulaDetalhada.innerHTML = `Markup = 1 ÷ (1 - (ICMS + PIS + COFINS + ISS + Margem)/100)<br>Markup = 1 ÷ (1 - (${icms.toFixed(2)}% + ${aliquotaPIS.toFixed(2)}% + ${aliquotaCOFINS.toFixed(2)}% + ${iss.toFixed(2)}% + ${margemLucro.toFixed(2)}%)/100)`;
            }
        } else if (regimeTributario === 'simples') {
            markupDemonstracao.innerHTML = `Markup = 1 / (1 - (Alíquota Simples 6.0% + Margem ${margemLucro.toFixed(2)}%) / 100) = 1 / (1 - ${somaMarkup.toFixed(2)}/100) = 1 / ${(1 - somaMarkup/100).toFixed(4)} = ${markup.toFixed(4)}`;
            if (markupFormulaDetalhada) {
                markupFormulaDetalhada.innerHTML = `Markup = 1 ÷ (1 - (Alíquota Simples + Margem)/100)<br>Markup = 1 ÷ (1 - (6.0% + ${margemLucro.toFixed(2)}%)/100)`;
            }
        } else {
            markupDemonstracao.innerHTML = `Markup = 1 / (1 - (ICMS ${icms.toFixed(2)}% + PIS ${aliquotaPIS.toFixed(2)}% + COFINS ${aliquotaCOFINS.toFixed(2)}% + ISS ${iss.toFixed(2)}% + IRPJ/CSLL ${aliquotaIRPJCSLLFaturamento.toFixed(2)}% + Margem ${margemLucro.toFixed(2)}%) / 100) = 1 / (1 - ${somaMarkup.toFixed(2)}/100) = 1 / ${(1 - somaMarkup/100).toFixed(4)} = ${markup.toFixed(4)}`;
            if (markupFormulaDetalhada) {
                markupFormulaDetalhada.innerHTML = `Markup = 1 ÷ (1 - (ICMS + PIS + COFINS + ISS + IRPJ/CSLL × %Presumido + Margem)/100)<br>Markup = 1 ÷ (1 - (${icms.toFixed(2)}% + ${aliquotaPIS.toFixed(2)}% + ${aliquotaCOFINS.toFixed(2)}% + ${iss.toFixed(2)}% + ${aliquotaIRPJCSLLFaturamento.toFixed(2)}% + ${margemLucro.toFixed(2)}%)/100)`;
            }
        }
    }

    const monthlyVolume = parseFloat(document.getElementById('monthlyVolume')?.value) || 0;
    const faturamentoMensal = precoFinal * monthlyVolume;
    const resumoFaturamentoMensal = document.getElementById('resumoFaturamentoMensal');
    if (resumoFaturamentoMensal) {
        resumoFaturamentoMensal.textContent = formatMoney(faturamentoMensal);
    }

    window.precoVendaCalculado = precoFinal;
    
    const lucroOperacionalPorUnidade = precoBase - custoUnitarioTotal;
    
    let lucroLiquidoPorUnidade = lucroOperacionalPorUnidade;
    if (regimeTributario === 'lucro-presumido' || regimeTributario === 'reforma-2027') {
        lucroLiquidoPorUnidade = lucroOperacionalPorUnidade * (1 - aliquotaIRPJCSLLFaturamento/100);
    }
    
    const lucroPorUnidadeElement = document.getElementById('lucroPorUnidade');
    if (lucroPorUnidadeElement) {
        lucroPorUnidadeElement.textContent = formatMoney(lucroOperacionalPorUnidade);
    }
    
    const lucroLiquidoUnidadeElement = document.getElementById('lucroLiquidoUnidade');
    if (lucroLiquidoUnidadeElement) {
        lucroLiquidoUnidadeElement.textContent = formatMoney(lucroLiquidoPorUnidade);
    }
    
    const margemOperacionalPercentElement = document.getElementById('margemOperacionalPercent');
    if (margemOperacionalPercentElement) {
        const margemPercent = precoBase > 0 ? (lucroOperacionalPorUnidade / precoBase) * 100 : 0;
        margemOperacionalPercentElement.textContent = margemPercent.toFixed(1) + '%';
    }
}

function updateAllCalculations() {
    if (window.rateioBloqueado) {
        console.warn('⚠️ Cálculos bloqueados por superalocação de custos indiretos.');
        return;
    }

    try {
        updateAllTableTotals();
        updateResumoFinanceiro();
        updatePrecoSugeridoCorrigidoComIRPJCSLL();
        updatePontoEquilibrioAnaliseCorrigido();
        updateMemorialCalculationsCorrigidoFinal();
        updateDRECompleto();
        validarConsistenciaGeral();
        atualizarPainelCapacidade();
        updateDateTime();
        
        console.log('✅ Cálculos atualizados com sucesso (V7.1 + Desenhos + Multiusuário + MySQL).');
    } catch (error) {
        console.error('❌ Erro ao atualizar cálculos:', error);
    }
}

function updateAllTableTotals() {
    updateMateriaisComCredito('materiaPrimaTable', 'totalMateriaPrima');
    updateMateriaisComCredito('insumosTable', 'totalInsumos');
    updateMateriaisComCredito('componentesTable', 'totalComponentes');
    
    updateFerramentalTotal('toolingTable', 'totalTooling', 'totalToolingPerPiece');
    updateFerramentalTotal('materiaisFerramentalTable', 'totalMateriaisFerramental', 'totalMateriaisFerramentalPerPiece');
    
    if (document.getElementById('materiaisFerramentalMachoTable')) {
        updateFerramentalTotal('materiaisFerramentalMachoTable', 'totalMateriaisFerramentalMacho', 'totalMateriaisFerramentalMachoPerPiece');
    }
    if (document.getElementById('materiaisFerramentalMatrizTable')) {
        updateFerramentalTotal('materiaisFerramentalMatrizTable', 'totalMateriaisFerramentalMatriz', 'totalMateriaisFerramentalMatrizPerPiece');
    }
    if (document.getElementById('materiaisFerramentalGabaritoTable')) {
        updateFerramentalTotal('materiaisFerramentalGabaritoTable', 'totalMateriaisFerramentalGabarito', 'totalMateriaisFerramentalGabaritoPerPiece');
    }
    
    updateEmbalagemTotal();
    
    updateTableTotal('maoObraTable', 'totalMaoObraDireta');
    updateTableTotal('maoObraIndiretaTable', 'totalMaoObraIndireta');
    
    updateProcessTotal();
    
    updateCustosIndiretosTotalCorrigidoComBloqueio();
    
    calcularVidaUtilMinima();
}

function updateFerramentalTotal(tableId, totalInputId, perPieceInputId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    let total = 0;
    let totalPerPiece = 0;

    rows.forEach(row => {
        const totalInput = row.querySelector('.total-price');
        const perPieceInput = row.querySelector('.tooling-per-piece');
        const lifeInput = row.querySelector('.tooling-life-input');
        const priceInput = row.querySelector('.unit-price-input');
        const qtyInput = row.querySelector('.qty-input');

        if (lifeInput && priceInput && qtyInput && totalInput && perPieceInput) {
            const life = parseFloat(lifeInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const qty = parseFloat(qtyInput.value) || 1;
            const valorTotal = price * qty;

            totalInput.value = valorTotal.toFixed(2);

            if (life <= 0) {
                perPieceInput.value = '0.00000';
                lifeInput.classList.add('is-invalid');
            } else {
                perPieceInput.value = (valorTotal / life).toFixed(5);
                lifeInput.classList.remove('is-invalid');
                totalPerPiece += valorTotal / life;
            }

            total += valorTotal;
        }
    });

    const totalInput = document.getElementById(totalInputId);
    if (totalInput) totalInput.value = total.toFixed(2);

    const perPieceInput = document.getElementById(perPieceInputId);
    if (perPieceInput) perPieceInput.value = totalPerPiece.toFixed(5);
    
    calcularVidaUtilMinima();
}

function updateEmbalagemTotal() {
    const table = document.getElementById('embalagemTable');
    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    let total = 0;

    rows.forEach(row => {
        const packagingQty = parseFloat(row.querySelector('.packaging-qty-input')?.value) || 1;
        const qtyPerPackage = parseFloat(row.querySelector('.qty-per-package-input')?.value) || 1;
        const unitPrice = parseFloat(row.querySelector('.unit-price-input')?.value) || 0;
        const totalInput = row.querySelector('.total-price');

        if (qtyPerPackage > 0 && totalInput) {
            const totalPerPiece = (unitPrice * packagingQty) / qtyPerPackage;
            totalInput.value = totalPerPiece.toFixed(2);
            total += totalPerPiece;
        }
    });

    const totalInput = document.getElementById('totalEmbalagem');
    if (totalInput) totalInput.value = total.toFixed(2);
}

function updateTableTotal(tableId, totalInputId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const totalInputs = table.querySelectorAll('.total-price');
    let sum = 0;
    totalInputs.forEach(input => {
        sum += parseFloat(input.value) || 0;
    });

    const tableTotalInput = document.getElementById(totalInputId);
    if (tableTotalInput) {
        tableTotalInput.value = sum.toFixed(2);
    }
}

function calculateHourlyCost(row) {
    const power = parseFloat(row.querySelector('.power-input')?.value) || 0;
    const energyPrice = parseFloat(row.querySelector('.energy-price-input')?.value) || 0;
    const water = parseFloat(row.querySelector('.water-input')?.value) || 0;
    const waterPrice = parseFloat(row.querySelector('.water-price-input')?.value) || 0;
    const depreciationMonthly = parseFloat(row.querySelector('.depreciation-input')?.value) || 0;
    const otherCosts = parseFloat(row.querySelector('.other-costs-input')?.value) || 0;
    const hoursPerMonth = parseFloat(document.getElementById('horasTrabalhadasMes')?.value) || INDUSTRY.HORAS_TRABALHADAS_PADRAO;

    const energyCost = power * energyPrice;
    const waterCost = water * waterPrice;
    const depreciationHourly = hoursPerMonth > 0 ? depreciationMonthly / hoursPerMonth : 0;
    const totalHourlyCost = energyCost + waterCost + depreciationHourly + otherCosts;

    const hourlyCostInput = row.querySelector('.hourly-cost-input');
    if (hourlyCostInput) {
        hourlyCostInput.value = totalHourlyCost.toFixed(2);
    }

    return totalHourlyCost;
}

function calculateProcessCost(row) {
    const hourlyCost = calculateHourlyCost(row);
    const efficiency = parseFloat(row.querySelector('.efficiency-input')?.value) || 100;
    const output = parseFloat(row.querySelector('.output-input')?.value) || 1;
    const setupTime = parseFloat(row.querySelector('.setup-time-input')?.value) || 0;

    const producaoEfetiva = output * (efficiency / 100);

    const operationalCostPerPiece = producaoEfetiva > 0 ? hourlyCost / producaoEfetiva : 0;
    const setupCostPerPiece = producaoEfetiva > 0 ? (hourlyCost * (setupTime / 60)) / producaoEfetiva : 0;
    const totalCost = operationalCostPerPiece + setupCostPerPiece;

    const totalInput = row.querySelector('.process-total');
    if (totalInput) {
        totalInput.value = totalCost.toFixed(4);
    }

    return totalCost;
}

function updateProcessTotal() {
    const rows = document.querySelectorAll('#processTable tbody tr');
    let sum = 0;

    rows.forEach(row => {
        const totalCost = calculateProcessCost(row);
        sum += totalCost;
    });

    const totalProcessoInput = document.getElementById('totalProcesso');
    if (totalProcessoInput) {
        totalProcessoInput.value = sum.toFixed(2);
    }
}

function calcularVidaUtilMinima() {
    let vidaMinima = Infinity;

    const tabelasFerramental = [
        'toolingTable',
        'materiaisFerramentalTable',
        'materiaisFerramentalMachoTable',
        'materiaisFerramentalMatrizTable',
        'materiaisFerramentalGabaritoTable'
    ];

    tabelasFerramental.forEach(tableId => {
        const table = document.getElementById(tableId);
        if (table) {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const lifeInput = row.querySelector('.tooling-life-input');
                if (lifeInput) {
                    const vida = parseFloat(lifeInput.value) || 0;
                    if (vida > 0 && vida < vidaMinima) {
                        vidaMinima = vida;
                    }
                }
            });
        }
    });

    window.vidaUtilMinima = vidaMinima !== Infinity ? vidaMinima : 0;

    const monthlyVolume = parseFloat(document.getElementById('monthlyVolume').value) || 1;
    window.vidaUtilMeses = monthlyVolume > 0 ? window.vidaUtilMinima / monthlyVolume : 0;

    const resumoVidaUtil = document.getElementById('resumoVidaUtilMinima');
    if (resumoVidaUtil) {
        resumoVidaUtil.textContent = window.vidaUtilMinima.toLocaleString('pt-BR') + ' peças';
    }
    
    const calcVidaUtilMeses = document.getElementById('calcVidaUtilMeses');
    if (calcVidaUtilMeses) {
        calcVidaUtilMeses.textContent = window.vidaUtilMeses.toFixed(1);
    }
}

function calculateArea() {
    const width = parseFloat(document.getElementById('width').value) || 0;
    const height = parseFloat(document.getElementById('height').value) || 0;

    const area = (width * height) / 1000000;
    document.getElementById('area').value = area.toFixed(4);

    const commercialArea = ((width + 40) * (height + 40)) / 1000000;
    document.getElementById('commercialArea').value = commercialArea.toFixed(4);

    synchronizeCalculations();
}

function updateResumoFinanceiro() {
    const totalMateriaPrima = parseFloat(document.getElementById('totalMateriaPrima')?.value) || 0;
    const totalInsumos = parseFloat(document.getElementById('totalInsumos')?.value) || 0;
    const totalComponentes = parseFloat(document.getElementById('totalComponentes')?.value) || 0;
    const totalEmbalagem = parseFloat(document.getElementById('totalEmbalagem')?.value) || 0;
    
    let totalFerramentalPorPeca = getTotalFerramentalPorPeca();

    const totalMaoObraDireta = parseFloat(document.getElementById('totalMaoObraDireta')?.value) || 0;
    const totalMaoObraIndireta = parseFloat(document.getElementById('totalMaoObraIndireta')?.value) || 0;
    const totalProcesso = parseFloat(document.getElementById('totalProcesso')?.value) || 0;
    const custoFixoPorPeca = window.custoFixoPorPeca || 0;
    const custoVariavelPorPeca = window.custoVariavelPorPeca || 0;

    const custoUnitarioTotal = totalMateriaPrima + totalInsumos + totalComponentes + totalEmbalagem + 
                              totalFerramentalPorPeca + 
                              totalMaoObraDireta + totalMaoObraIndireta + totalProcesso + 
                              custoFixoPorPeca + custoVariavelPorPeca;

    const resumoCustoUnitario = document.getElementById('resumoCustoUnitario');
    if (resumoCustoUnitario) {
        resumoCustoUnitario.textContent = formatMoney(custoUnitarioTotal);
    }

    const updates = {
        'resumoMateriaPrima': totalMateriaPrima,
        'resumoInsumos': totalInsumos,
        'resumoComponentes': totalComponentes,
        'resumoEmbalagem': totalEmbalagem,
        'resumoMaoObraDireta': totalMaoObraDireta,
        'resumoMaoObraIndireta': totalMaoObraIndireta,
        'resumoProcesso': totalProcesso,
        'resumoCustosIndiretosFixos': custoFixoPorPeca,
        'resumoCustosIndiretosVariaveis': custoVariavelPorPeca
    };

    Object.entries(updates).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = formatMoney(value);
        }
    });

    const resumoFerramentalPorPecaElement = document.getElementById('resumoFerramentalPorPeca');
    if (resumoFerramentalPorPecaElement) {
        resumoFerramentalPorPecaElement.textContent = formatMoney(totalFerramentalPorPeca);
    }

    const totalMateriaisDiretos = totalMateriaPrima + totalInsumos + totalComponentes + totalEmbalagem + totalFerramentalPorPeca;
    const resumoTotalMateriaisDiretos = document.getElementById('resumoTotalMateriaisDiretos');
    if (resumoTotalMateriaisDiretos) {
        resumoTotalMateriaisDiretos.textContent = formatMoney(totalMateriaisDiretos);
    }

    let totalInvestimento = 0;
    const tabelasInvestimento = [
        'totalTooling',
        'totalMateriaisFerramental',
        'totalMateriaisFerramentalMacho',
        'totalMateriaisFerramentalMatriz',
        'totalMateriaisFerramentalGabarito'
    ];

    tabelasInvestimento.forEach(tableId => {
        totalInvestimento += parseFloat(document.getElementById(tableId)?.value || 0);
    });

    const resumoFerramental = document.getElementById('resumoFerramental');
    if (resumoFerramental) {
        resumoFerramental.textContent = formatMoney(parseFloat(document.getElementById('totalTooling')?.value || 0));
    }

    const resumoMateriaisFerramental = document.getElementById('resumoMateriaisFerramental');
    if (resumoMateriaisFerramental) {
        resumoMateriaisFerramental.textContent = formatMoney(parseFloat(document.getElementById('totalMateriaisFerramental')?.value || 0));
    }

    const resumoTotalInvestimento = document.getElementById('resumoTotalInvestimento');
    if (resumoTotalInvestimento) {
        resumoTotalInvestimento.textContent = formatMoney(totalInvestimento);
    }

    const monthlyVolume = parseFloat(document.getElementById('monthlyVolume').value) || 0;
    const resumoVolumeMensal = document.getElementById('resumoVolumeMensal');
    if (resumoVolumeMensal) {
        resumoVolumeMensal.textContent = monthlyVolume.toLocaleString('pt-BR') + ' peças';
    }
}

function updateDRECompleto() {
    try {
        const monthlyVolume = parseFloat(document.getElementById('monthlyVolume').value) || 0;
        
        const precoBaseElement = document.getElementById('precoBaseAtual');
        let precoBase = 0;
        if (precoBaseElement) {
            const precoText = precoBaseElement.textContent
                .replace('R$ ', '')
                .replace(/\./g, '')
                .replace(',', '.');
            precoBase = parseFloat(precoText) || 0;
        }

        const precoFinalElement = document.getElementById('precoSugeridoDestaque');
        let precoFinal = 0;
        if (precoFinalElement) {
            const precoText = precoFinalElement.textContent
                .replace('R$ ', '')
                .replace(/\./g, '')
                .replace(',', '.');
            precoFinal = parseFloat(precoText) || 0;
        }

        const receitaBruta = precoFinal * monthlyVolume;
        document.getElementById('dreReceitaBruta').textContent = formatMoney(receitaBruta);

        const regimeTributario = document.getElementById('regimeTributario').value;
        
        const aliquotaIPI = parseFloat(document.getElementById('aliquotaIPI').value) || 0;
        const aliquotaICMS = parseFloat(document.getElementById('aliquotaICMS').value) || 0;
        const aliquotaPIS = parseFloat(document.getElementById('aliquotaPIS').value) || 0;
        const aliquotaCOFINS = parseFloat(document.getElementById('aliquotaCOFINS').value) || 0;
        const aliquotaISS = parseFloat(document.getElementById('aliquotaISS').value) || 0;
        
        const aliquotaCBS = parseFloat(document.getElementById('aliquotaCBS')?.value) || 12.5;
        const aliquotaIBS = parseFloat(document.getElementById('aliquotaIBS')?.value) || 14;
        const aliquotaIS = parseFloat(document.getElementById('aliquotaIS')?.value) || 0;

        const valorIPI = receitaBruta - (receitaBruta / (1 + aliquotaIPI/100));
        const baseICMS = precoBase * monthlyVolume;
        
        let valorICMS = 0, valorPIS = 0, valorCOFINS = 0, valorISS = 0;
        let valorCBS = 0, valorIBS = 0, valorIS = 0;
        
        const linhaICMS = document.getElementById('dreLinhaICMS');
        const linhaPIS = document.getElementById('dreLinhaPIS');
        const linhaCOFINS = document.getElementById('dreLinhaCOFINS');
        const linhaISS = document.getElementById('dreLinhaISS');
        
        const linhaCBS = document.getElementById('dreLinhaCBS');
        const linhaIBS = document.getElementById('dreLinhaIBS');
        const linhaIS = document.getElementById('dreLinhaIS');
        
        if (regimeTributario === 'reforma-2027') {
            if (linhaICMS) linhaICMS.style.display = 'none';
            if (linhaPIS) linhaPIS.style.display = 'none';
            if (linhaCOFINS) linhaCOFINS.style.display = 'none';
            if (linhaISS) linhaISS.style.display = 'none';
            
            if (linhaCBS) linhaCBS.style.display = '';
            if (linhaIBS) linhaIBS.style.display = '';
            if (linhaIS) linhaIS.style.display = aliquotaIS > 0 ? '' : 'none';
            
            valorCBS = precoBase * monthlyVolume * (aliquotaCBS / 100);
            valorIBS = precoBase * monthlyVolume * (aliquotaIBS / 100);
            valorIS = precoBase * monthlyVolume * (aliquotaIS / 100);
            
            document.getElementById('dreCBS').textContent = formatMoney(valorCBS);
            document.getElementById('dreIBS').textContent = formatMoney(valorIBS);
            document.getElementById('dreIS').textContent = formatMoney(valorIS);
            
        } else {
            if (linhaICMS) linhaICMS.style.display = '';
            if (linhaPIS) linhaPIS.style.display = '';
            if (linhaCOFINS) linhaCOFINS.style.display = '';
            if (linhaISS) linhaISS.style.display = '';
            
            if (linhaCBS) linhaCBS.style.display = 'none';
            if (linhaIBS) linhaIBS.style.display = 'none';
            if (linhaIS) linhaIS.style.display = 'none';
            
            valorICMS = baseICMS * (aliquotaICMS / 100);
            valorPIS = baseICMS * (aliquotaPIS / 100);
            valorCOFINS = baseICMS * (aliquotaCOFINS / 100);
            valorISS = baseICMS * (aliquotaISS / 100);
            
            document.getElementById('dreICMS').textContent = formatMoney(valorICMS);
            document.getElementById('drePIS').textContent = formatMoney(valorPIS);
            document.getElementById('dreCOFINS').textContent = formatMoney(valorCOFINS);
            document.getElementById('dreISS').textContent = formatMoney(valorISS);
        }

        document.getElementById('dreIPI').textContent = formatMoney(valorIPI);

        const totalDeducoes = valorIPI + valorICMS + valorPIS + valorCOFINS + valorISS + valorCBS + valorIBS + valorIS;
        document.getElementById('dreTotalDeducoes').textContent = formatMoney(totalDeducoes);

        const receitaLiquida = receitaBruta - totalDeducoes;
        document.getElementById('dreReceitaLiquida').textContent = formatMoney(receitaLiquida);

        const custoMP = (parseFloat(document.getElementById('totalMateriaPrima').value) || 0) * monthlyVolume;
        const custoInsumos = (parseFloat(document.getElementById('totalInsumos').value) || 0) * monthlyVolume;
        const custoComponentes = (parseFloat(document.getElementById('totalComponentes').value) || 0) * monthlyVolume;
        const custoEmbalagem = (parseFloat(document.getElementById('totalEmbalagem').value) || 0) * monthlyVolume;
        const custoMOD = (parseFloat(document.getElementById('totalMaoObraDireta').value) || 0) * monthlyVolume;
        const custoProcesso = (parseFloat(document.getElementById('totalProcesso').value) || 0) * monthlyVolume;
        const custoVariavelIndireto = (window.custoVariavelPorPeca || 0) * monthlyVolume;

        document.getElementById('dreCustoMP').textContent = formatMoney(custoMP);
        document.getElementById('dreCustoInsumos').textContent = formatMoney(custoInsumos);
        document.getElementById('dreCustoComponentes').textContent = formatMoney(custoComponentes);
        document.getElementById('dreCustoEmbalagem').textContent = formatMoney(custoEmbalagem);
        document.getElementById('dreCustoMOD').textContent = formatMoney(custoMOD);
        document.getElementById('dreCustoProcesso').textContent = formatMoney(custoProcesso);
        document.getElementById('dreCustoVariavelIndireto').textContent = formatMoney(custoVariavelIndireto);

        const totalCPV = custoMP + custoInsumos + custoComponentes + custoEmbalagem + 
                        custoMOD + custoProcesso + custoVariavelIndireto;
        document.getElementById('dreTotalCPV').textContent = formatMoney(totalCPV);

        const lucroBruto = receitaLiquida - totalCPV;
        document.getElementById('dreLucroBruto').textContent = formatMoney(lucroBruto);

        const margemBruta = receitaLiquida > 0 ? (lucroBruto / receitaLiquida) * 100 : 0;
        document.getElementById('dreMargemBruta').textContent = formatPercent(margemBruta);

        const despesaMOI = (parseFloat(document.getElementById('totalMaoObraIndireta').value) || 0) * monthlyVolume;
        const despesaCustosFixos = (window.custoFixoPorPeca || 0) * monthlyVolume;

        document.getElementById('dreDespesaMOI').textContent = formatMoney(despesaMOI);
        document.getElementById('dreDespesaCustosFixos').textContent = formatMoney(despesaCustosFixos);

        const totalDespesas = despesaMOI + despesaCustosFixos;
        document.getElementById('dreTotalDespesas').textContent = formatMoney(totalDespesas);

        const lucroOperacional = lucroBruto - totalDespesas;
        document.getElementById('dreLucroOperacional').textContent = formatMoney(lucroOperacional);

        const margemOperacional = receitaLiquida > 0 ? (lucroOperacional / receitaLiquida) * 100 : 0;
        document.getElementById('dreMargemOperacional').textContent = formatPercent(margemOperacional);

        const percentualPresumido = parseFloat(document.getElementById('percentualPresumido').value) || 8;
        const aliquotaIRPJ = parseFloat(document.getElementById('aliquotaIRPJ').value) || 15;
        const aliquotaCSLL = parseFloat(document.getElementById('aliquotaCSLL').value) || 9;
        const aliquotaIRPJAdicional = parseFloat(document.getElementById('aliquotaIRPJAdicional').value) || 0;

        let valorIRPJ = 0, valorCSLL = 0, valorIRPJAdicional = 0;

        if (regimeTributario === 'lucro-presumido' || regimeTributario === 'reforma-2027') {
            const basePresumida = precoBase * monthlyVolume * (percentualPresumido / 100);
            valorIRPJ = basePresumida * (aliquotaIRPJ / 100);
            valorCSLL = basePresumida * (aliquotaCSLL / 100);

            if (aliquotaIRPJAdicional > 0 && basePresumida > 20000) {
                const excedente = basePresumida - 20000;
                valorIRPJAdicional = excedente * (aliquotaIRPJAdicional / 100);
            }
        } else if (regimeTributario === 'lucro-real') {
            valorIRPJ = lucroOperacional * 0.15;
            valorCSLL = lucroOperacional * 0.09;
            if (lucroOperacional > 20000) {
                valorIRPJAdicional = (lucroOperacional - 20000) * 0.10;
            }
        }

        document.getElementById('dreIRPJ').textContent = formatMoney(valorIRPJ);
        document.getElementById('dreCSLL').textContent = formatMoney(valorCSLL);
        document.getElementById('dreIRPJAdicional').textContent = formatMoney(valorIRPJAdicional);

        const totalImpostosLucro = valorIRPJ + valorCSLL + valorIRPJAdicional;
        document.getElementById('dreTotalImpostosLucro').textContent = formatMoney(totalImpostosLucro);

        const lucroLiquido = lucroOperacional - totalImpostosLucro;
        document.getElementById('dreLucroLiquido').textContent = formatMoney(lucroLiquido);

        const margemLiquida = receitaLiquida > 0 ? (lucroLiquido / receitaLiquida) * 100 : 0;
        document.getElementById('dreMargemLiquida').textContent = formatPercent(margemLiquida);

        const totalInvestimento = parseFloat(document.getElementById('resumoTotalInvestimento').textContent
            .replace('R$ ', '').replace(/\./g, '').replace(',', '.')) || 0;

        const lucroLiquidoPorUnidade = monthlyVolume > 0 ? lucroLiquido / monthlyVolume : 0;

        const roi = totalInvestimento > 0 ? (lucroLiquido * 12 / totalInvestimento) * 100 : 0;
        document.getElementById('dreROI').textContent = roi.toFixed(1) + '%';

        const payback = lucroLiquido > 0 ? totalInvestimento / lucroLiquido : 0;
        document.getElementById('drePayback').textContent = payback.toFixed(1) + ' meses';

        const roiCalculadoElement = document.getElementById('roiCalculado');
        if (roiCalculadoElement) {
            roiCalculadoElement.textContent = roi.toFixed(2) + '%';
            roiCalculadoElement.className = 'roi-indicator ' + (roi >= INDUSTRY.ROI_MINIMO_RECOMENDADO ? 'roi-positive' : roi > 0 ? 'roi-positive' : 'roi-negative');
        }

        const paybackCalculadoElement = document.getElementById('paybackCalculado');
        if (paybackCalculadoElement) {
            paybackCalculadoElement.textContent = payback.toFixed(1) + ' meses';
        }

        const lucroLiquidoUnidadeElement = document.getElementById('lucroLiquidoUnidade');
        if (lucroLiquidoUnidadeElement) {
            lucroLiquidoUnidadeElement.textContent = formatMoney(lucroLiquidoPorUnidade);
        }

        const pontoEquilibrioQtd = document.getElementById('resumoPontoEquilibrioQtd').textContent;
        document.getElementById('drePE').textContent = pontoEquilibrioQtd;

        const vidaUtil = window.vidaUtilMeses.toFixed(1);
        document.getElementById('dreVidaUtil').textContent = vidaUtil + ' meses';

        let analise = '';
        if (margemLiquida < 10) {
            analise = '⚠️ Margem líquida baixa. Recomenda-se revisar custos ou aumentar preço.';
        } else if (margemLiquida < 15) {
            analise = '⚠️ Margem líquida dentro do aceitável, mas abaixo do recomendado (15%).';
        } else if (margemLiquida < 20) {
            analise = '✅ Margem líquida adequada. Projeto com boa rentabilidade.';
        } else {
            analise = '✅ Excelente margem líquida. Projeto altamente rentável.';
        }

        if (roi < INDUSTRY.ROI_MINIMO_RECOMENDADO) {
            analise += ' ROI abaixo do mínimo industrial.';
        } else {
            analise += ' ROI dentro dos parâmetros.';
        }

        if (payback > INDUSTRY.PAYBACK_MAXIMO_RECOMENDADO) {
            analise += ' Payback longo.';
        }

        document.getElementById('dreAnalise').textContent = analise;

        const now = new Date();
        document.getElementById('dreData').textContent = 'Emitido em: ' + now.toLocaleDateString('pt-BR');
        document.getElementById('drePeriodo').textContent = 'Período: Mensal';

    } catch (error) {
        console.error('Erro ao atualizar DRE:', error);
    }
}

