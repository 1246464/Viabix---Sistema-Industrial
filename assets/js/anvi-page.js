// ==================================================================================
// SISTEMA VIABIX - MODELO INDUSTRIAL 10/10 - ENGENHARIA FINANCEIRA
// VERSÃO FINAL 7.1 - CORREÇÕES APLICADAS + DESENHOS + MULTIUSUÁRIO + MYSQL
// ==================================================================================

// Constantes e calculos da ANVI movidos para assets/js/anvi-calculations.js.

// Relatorios e PDF da ANVI movidos para assets/js/anvi-report.js.

// Classificacao fiscal da ANVI movida para assets/js/anvi-tax-classification.js.

// Notificacoes, DRE Excel e controles fiscais movidos para assets/js/anvi-ui-helpers.js.

// Edicao, importacao e armazenamento de tabelas movidos para assets/js/anvi-table-ui.js.

function atualizarEncargos() {
    const encargosInput = document.getElementById('encargosSociais');
    if (encargosInput) {
        const encargosPercent = parseFloat(encargosInput.value) || 80;
        ENCARGOS_SOCIAIS = 1 + (encargosPercent / 100);
    }
}

function validarConsistenciaGeral() {
    const painel = document.getElementById('painelValidacao');
    if (!painel) return;

    const inconsistencias = [];
    const avisos = [];

    const anviNumber = document.getElementById('anviNumber')?.value;
    if (!anviNumber) inconsistencias.push('Nº ANVI não preenchido');

    const dataANVI = document.getElementById('dataANVI')?.value;
    if (!dataANVI) inconsistencias.push('Data da ANVI não preenchida');

    const client = document.getElementById('client')?.value;
    if (!client) inconsistencias.push('Cliente não preenchido');

    const productDescription = document.getElementById('productDescription')?.value;
    if (!productDescription) inconsistencias.push('Descrição do Produto não preenchida');

    const monthlyVolume = parseFloat(document.getElementById('monthlyVolume')?.value) || 0;
    if (monthlyVolume <= 0) inconsistencias.push('Volume mensal inválido (deve ser > 0)');

    const totalMateriaPrima = parseFloat(document.getElementById('totalMateriaPrima')?.value) || 0;
    if (totalMateriaPrima <= 0) avisos.push('Matéria Prima não cadastrada ou com valor zero');

    const totalMaoObraDireta = parseFloat(document.getElementById('totalMaoObraDireta')?.value) || 0;
    if (totalMaoObraDireta <= 0) avisos.push('Mão de Obra Direta não cadastrada ou com valor zero');

    const aliquotaIPI = parseFloat(document.getElementById('aliquotaIPI')?.value) || 0;
    const aliquotaICMS = parseFloat(document.getElementById('aliquotaICMS')?.value) || 0;
    const aliquotaPIS = parseFloat(document.getElementById('aliquotaPIS')?.value) || 0;
    const aliquotaCOFINS = parseFloat(document.getElementById('aliquotaCOFINS')?.value) || 0;

    if (aliquotaIPI <= 0) avisos.push('Alíquota de IPI zerada - verificar classificação fiscal');
    if (aliquotaICMS <= 0) avisos.push('Alíquota de ICMS zerada - verificar classificação fiscal');
    if (aliquotaPIS <= 0) avisos.push('Alíquota de PIS zerada - verificar regime tributário');
    if (aliquotaCOFINS <= 0) avisos.push('Alíquota de COFINS zerada - verificar regime tributário');

    const markup = parseFloat(document.getElementById('calcMarkupValue')?.textContent.replace(',', '.')) || 0;
    if (markup <= 1) inconsistencias.push('Markup inválido ou zerado - verifique a configuração de impostos e margem');

    const pontoEquilibrioQtd = parseFloat(document.getElementById('calcPontoEquilibrioQtd')?.textContent) || 0;
    if (pontoEquilibrioQtd > monthlyVolume && pontoEquilibrioQtd !== Infinity) {
        avisos.push(`Ponto de equilíbrio (${pontoEquilibrioQtd.toFixed(0)} un) acima do volume mensal (${monthlyVolume} un) - risco de não atingir rentabilidade`);
    }

    const margemSeguranca = parseFloat(document.getElementById('calcMargemSegurancaPercent')?.textContent) || 0;
    if (margemSeguranca < 10 && margemSeguranca > 0) {
        avisos.push(`Margem de segurança baixa (${margemSeguranca.toFixed(1)}%) - projeto sensível a variações de demanda`);
    }

    const roi = parseFloat(document.getElementById('dreROI')?.textContent?.replace('%', '').replace(',', '.')) || 0;
    if (roi < 20) {
        avisos.push(`ROI baixo (${roi.toFixed(1)}%) - abaixo do recomendado para projetos industriais (20%)`);
    }

    const payback = parseFloat(document.getElementById('drePayback')?.textContent?.replace(' meses', '').replace(',', '.')) || 0;
    if (payback > 24) {
        avisos.push(`Payback longo (${payback.toFixed(1)} meses) - acima do recomendado (24 meses)`);
    }

    const vidaUtilMeses = window.vidaUtilMeses || 0;
    if (vidaUtilMeses > 0 && vidaUtilMeses < 12) {
        avisos.push(`Vida útil do ferramental (${vidaUtilMeses.toFixed(1)} meses) inferior a 1 ano - considerar custo de reposição`);
    }

    if (inconsistencias.length > 0 || avisos.length > 0) {
        painel.style.display = 'block';

        let html = '';

        if (inconsistencias.length > 0) {
            html += '<div class="consistency-error">';
            html += '<h6 class="fw-bold"><i class="fas fa-times-circle me-2"></i>INCONSISTÊNCIAS CRÍTICAS - CORRIJA ANTES DE PROSSEGUIR</h6>';
            html += '<ul class="mb-0">';
            inconsistencias.forEach(item => {
                html += `<li><i class="fas fa-exclamation-circle me-2"></i>${item}</li>`;
            });
            html += '</ul></div>';
        }

        if (avisos.length > 0) {
            html += '<div class="consistency-warning">';
            html += '<h6 class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>AVISOS DE CONSISTÊNCIA - REVISE OS PONTOS ABAIXO</h6>';
            html += '<ul class="mb-0">';
            avisos.forEach(item => {
                html += `<li><i class="fas fa-info-circle me-2"></i>${item}</li>`;
            });
            html += '</ul></div>';
        }

        painel.innerHTML = html;
    } else {
        painel.style.display = 'none';
    }
}

// =========================================
// INICIALIZAÇÃO DO SISTEMA
// =========================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('%c[DOMREADY] Script iniciado', 'background: orange; color: black; font-weight: bold');
    console.log('%c✅ VIABIX - MODELO INDUSTRIAL 10/10 (V7.1 - CORREÇÕES + DESENHOS + MULTIUSUÁRIO + MYSQL)', 'background: #0a3d2e; color: white; font-size: 16px; padding: 10px; border-radius: 5px;');
    console.log('[DOMREADY] Chamando verificarSessao()...');

    verificarSessao().then(() => {
        if (!usuarioAtual) {
            return;
        }

        // Após verificar sessão, limpar bloqueios antigos
        limparBloqueiosAntigos();

        const anviIdInicial = new URLSearchParams(window.location.search).get('anvi_id');
        if (anviIdInicial) {
            setTimeout(() => abrirANVI(anviIdInicial), 350);
        }
    });

    window.totalCustosFixos = 0;
    window.totalCustosVariaveisIndiretos = 0;
    window.custoFixoPorPeca = 0;
    window.custoVariavelPorPeca = 0;
    window.vidaUtilMinima = Infinity;
    window.vidaUtilMeses = 0;
    window.rateioBloqueado = false;

    document.querySelectorAll('.custo-fixo-input, .rateio-percent-input').forEach(input => {
        input.addEventListener('input', function() {
            updateCustosIndiretosTotalCorrigidoComBloqueio();
        });
    });

    document.querySelectorAll('#custosIndiretosTable select').forEach(select => {
        select.addEventListener('change', function() {
            updateCustosIndiretosTotalCorrigidoComBloqueio();
        });
    });

    const metodoRateio = document.getElementById('metodoRateio');
    if (metodoRateio) {
        metodoRateio.addEventListener('change', function() {
            updateCustosIndiretosTotalCorrigidoComBloqueio();
        });
    }

    const regimePisCofins = document.getElementById('regimePisCofins');
    if (regimePisCofins) {
        regimePisCofins.addEventListener('change', function() {
            synchronizeCalculations();
        });
    }

    const regimeTributario = document.getElementById('regimeTributario');
    if (regimeTributario) {
        regimeTributario.addEventListener('change', function() {
            updatePisCofinsByRegime();
            synchronizeCalculations();
        });
    }

    const cbsField = document.getElementById('aliquotaCBS');
    const ibsField = document.getElementById('aliquotaIBS');
    const isField = document.getElementById('aliquotaIS');
    
    if (cbsField) cbsField.addEventListener('input', synchronizeCalculations);
    if (ibsField) ibsField.addEventListener('input', synchronizeCalculations);
    if (isField) isField.addEventListener('input', synchronizeCalculations);

    const encargosField = document.getElementById('encargosSociais');
    if (encargosField) {
        encargosField.addEventListener('input', function() {
            atualizarEncargos();
            synchronizeCalculations();
        });
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('qty-input') || 
            e.target.classList.contains('unit-price-input') ||
            e.target.classList.contains('ipi-aliquota-input') ||
            e.target.classList.contains('icms-aliquota-input')) {
            
            const row = e.target.closest('tr');
            if (row && (row.closest('#materiaPrimaTable') || row.closest('#insumosTable') || row.closest('#componentesTable'))) {
                const qtyInput = row.querySelector('.qty-input');
                const priceInput = row.querySelector('.unit-price-input');
                const ipiInput = row.querySelector('.ipi-aliquota-input');
                const icmsInput = row.querySelector('.icms-aliquota-input');
                const totalInput = row.querySelector('.total-price');
                const grossTotal = row.querySelector('.gross-total');
                const creditInput = row.querySelector('.credit-value');

                if (qtyInput && priceInput && totalInput) {
                    const qty = parseFloat(qtyInput.value) || 0;
                    const price = parseFloat(priceInput.value) || 0;
                    const ipi = parseFloat(ipiInput?.value) || 0;
                    const icms = parseFloat(icmsInput?.value) || 0;

                    const valorBruto = qty * price;
                    const valorIPI = valorBruto * (ipi / 100);
                    
                    const baseICMS = valorBruto / (1 - icms/100);
                    const valorICMS = baseICMS * (icms/100);
                    
                    const valorTotal = valorBruto + valorIPI + valorICMS;

                    if (grossTotal) grossTotal.value = valorTotal.toFixed(2);

                    const isNaoCumulativo = document.getElementById('regimePisCofins')?.value === 'nao-cumulativo';
                    
                    const aliquotaPIS = parseFloat(document.getElementById('aliquotaPIS')?.value) || 1.65;
                    const aliquotaCOFINS = parseFloat(document.getElementById('aliquotaCOFINS')?.value) || 7.6;
                    
                    let credito = 0;
                    if (isNaoCumulativo) {
                        const creditoIPI = valorIPI;
                        const creditoICMS = valorICMS;
                        const creditoPIS = valorBruto * (aliquotaPIS / 100);
                        const creditoCOFINS = valorBruto * (aliquotaCOFINS / 100);
                        credito = creditoIPI + creditoICMS + creditoPIS + creditoCOFINS;
                    } else {
                        const creditoIPI = valorIPI;
                        const creditoICMS = valorICMS;
                        credito = creditoIPI + creditoICMS;
                    }

                    if (creditInput) creditInput.value = credito.toFixed(2);
                    totalInput.value = (valorTotal - credito).toFixed(2);
                    
                    synchronizeCalculations();
                }
            }
        }

        if (e.target.classList.contains('tooling-life-input') || 
            (e.target.classList.contains('unit-price-input') && e.target.closest('[id*="Ferramental"], #toolingTable')) || 
            (e.target.classList.contains('qty-input') && e.target.closest('[id*="Ferramental"], #toolingTable'))) {

            const row = e.target.closest('tr');
            if (row) {
                const lifeInput = row.querySelector('.tooling-life-input');
                const priceInput = row.querySelector('.unit-price-input');
                const qtyInput = row.querySelector('.qty-input');
                const totalInput = row.querySelector('.total-price');
                const perPieceInput = row.querySelector('.tooling-per-piece');

                if (lifeInput && priceInput && qtyInput && totalInput && perPieceInput) {
                    const life = parseFloat(lifeInput.value) || 0;
                    const price = parseFloat(priceInput.value) || 0;
                    const qty = parseFloat(qtyInput.value) || 1;
                    const total = price * qty;
                    totalInput.value = total.toFixed(2);

                    if (life <= 0) {
                        perPieceInput.value = '0.00000';
                        lifeInput.classList.add('is-invalid');
                    } else {
                        perPieceInput.value = (total / life).toFixed(5);
                        lifeInput.classList.remove('is-invalid');
                    }
                    synchronizeCalculations();
                }
            }
        }

        if (e.target.closest('#processTable')) {
            const row = e.target.closest('tr');
            if (row) {
                calculateHourlyCost(row);
                calculateProcessCost(row);
                updateProcessTotal();
                synchronizeCalculations();
            }
        }

        if (e.target.classList.contains('packaging-qty-input') || 
            e.target.classList.contains('qty-per-package-input') || 
            (e.target.classList.contains('unit-price-input') && e.target.closest('#embalagemTable'))) {

            const row = e.target.closest('tr');
            if (row && row.closest('#embalagemTable')) {
                const packagingQty = parseFloat(row.querySelector('.packaging-qty-input')?.value) || 1;
                const qtyPerPackage = parseFloat(row.querySelector('.qty-per-package-input')?.value) || 1;
                const unitPrice = parseFloat(row.querySelector('.unit-price-input')?.value) || 0;
                const totalInput = row.querySelector('.total-price');

                if (qtyPerPackage > 0 && totalInput) {
                    totalInput.value = ((unitPrice * packagingQty) / qtyPerPackage).toFixed(2);
                    updateEmbalagemTotal();
                    synchronizeCalculations();
                }
            }
        }

        if (e.target.classList.contains('time-input') || 
            e.target.classList.contains('unit-price-input') ||
            e.target.classList.contains('qty-input')) {

            const row = e.target.closest('tr');
            if (row && (row.closest('#maoObraTable') || row.closest('#maoObraIndiretaTable'))) {
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
                    
                    synchronizeCalculations();
                }
            }
        }
    });

    const refreshCalculations = document.getElementById('refreshCalculations');
    if (refreshCalculations) {
        refreshCalculations.addEventListener('click', updateAllCalculations);
    }

    const updateFields = ['monthlyVolume', 'margemLucroMarkup', 'aliquotaIPI', 'aliquotaICMS', 
                         'aliquotaPIS', 'aliquotaCOFINS', 'aliquotaISS', 'aliquotaIRPJ', 'aliquotaCSLL',
                         'aliquotaIRPJAdicional', 'percentualPresumido',
                         'horasTrabalhadasMes', 'metodoRateio', 'regimeTributario', 'regimePisCofins'];

    updateFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', synchronizeCalculations);
            field.addEventListener('change', synchronizeCalculations);
        }
    });

    sincronizarMargemLucro();

    document.getElementById('width').addEventListener('input', calculateArea);
    document.getElementById('height').addEventListener('input', calculateArea);

    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row')) {
            if (!usuarioAtual || usuarioAtual.nivel === 'visitante') {
                alert('Visitantes não podem remover linhas.');
                return;
            }

            const row = e.target.closest('tr');
            const tableId = row.closest('table').id;

            row.remove();

            if (tableId === 'processTable') {
                updateProcessTotal();
            } else if (tableId === 'custosIndiretosTable') {
                updateCustosIndiretosTotalCorrigidoComBloqueio();
            } else if (tableId === 'embalagemTable') {
                updateEmbalagemTotal();
            } else if (tableId === 'toolingTable' || tableId.includes('Ferramental')) {
                const totalId = tableId === 'toolingTable' ? 'totalTooling' : 
                              tableId === 'materiaisFerramentalTable' ? 'totalMateriaisFerramental' : 
                              tableId === 'materiaisFerramentalMachoTable' ? 'totalMateriaisFerramentalMacho' :
                              tableId === 'materiaisFerramentalMatrizTable' ? 'totalMateriaisFerramentalMatriz' :
                              'totalMateriaisFerramentalGabarito';
                
                const perPieceId = tableId === 'toolingTable' ? 'totalToolingPerPiece' :
                                 tableId === 'materiaisFerramentalTable' ? 'totalMateriaisFerramentalPerPiece' :
                                 tableId === 'materiaisFerramentalMachoTable' ? 'totalMateriaisFerramentalMachoPerPiece' :
                                 tableId === 'materiaisFerramentalMatrizTable' ? 'totalMateriaisFerramentalMatrizPerPiece' :
                                 'totalMateriaisFerramentalGabaritoPerPiece';
                                 
                updateFerramentalTotal(tableId, totalId, perPieceId);
            } else if (tableId === 'materiaPrimaTable' || tableId === 'insumosTable' || tableId === 'componentesTable') {
                updateMateriaisComCredito(tableId, tableId === 'materiaPrimaTable' ? 'totalMateriaPrima' : 
                                                 tableId === 'insumosTable' ? 'totalInsumos' : 'totalComponentes');
            }

            synchronizeCalculations();
        }
    });

    setupModalButtons();

    setTimeout(() => {
        calculateArea();
        updateAllCalculations();
        validarMetodoRateio();
        validarConsistenciaGeral();
        if (usuarioAtual) {
            carregarANVIs();
        }
        updateDateTime();
        
        updateReforma2027UI();
    }, 500);

    const form = document.getElementById('viabilityForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarANVI();
        });
    }

    const searchDirANVI = document.getElementById('searchDirANVI');
    if (searchDirANVI) {
        searchDirANVI.addEventListener('input', function() {
            filterTable('dirANVITable', this.value);
        });
    }

    const searchMasterList = document.getElementById('searchMasterList');
    if (searchMasterList) {
        searchMasterList.addEventListener('input', function() {
            filterTable('masterANVITable', this.value);
        });
    }

    setInterval(updateDateTime, 1000);
});

function calcularCustoVariavelUnitarioCorrigido(){
    const ids=[
        'totalMateriaPrima',
        'totalInsumos',
        'totalComponentes',
        'totalEmbalagem',
        'totalMaoObraDireta',
        'totalMaoObraIndireta',
        'totalCustosVariaveisIndiretosDisplay'
    ];
    let total=0;
    ids.forEach(id=>{
        const el=document.getElementById(id);
        if(el){
            const v=parseFloat(el.value||el.textContent)||0;
            total+=v;
        }
    });
    const processo=document.getElementById('calcProcessoResult');
    if(processo){
        total+=parseFloat(processo.textContent)||0;
    }
    return total;
}

function atualizarPontoEquilibrioCorrigido(){
    const preco=parseFloat(document.getElementById('calcPrecoBase')?.textContent)||0;
    const custosFixos=parseFloat(document.getElementById('totalCustosFixosDisplay')?.value)||0;
    const cvu=calcularCustoVariavelUnitarioCorrigido();
    const margem=preco-cvu;
    if(margem>0){
        const pe=custosFixos/margem;
        const el=document.getElementById('calcPontoEquilibrioQtd');
        if(el) el.textContent=pe.toFixed(0);
    }
}

document.addEventListener("input",()=>{
    atualizarPontoEquilibrioCorrigido();
});

window.addEventListener("load",()=>{
    atualizarPontoEquilibrioCorrigido();
});

const anviOperations = window.ViabixAnviOperations.mount({
    api: window.ViabixApiCore,
    getUsuarioAtual: () => usuarioAtual,
    getRateioBloqueado: () => window.rateioBloqueado,
    capturarTodosDados,
    restaurarTodosDados,
    setDesenhos: (desenhos) => {
        desenhosANVI = desenhos;
        renderizarDesenhos();
    },
    updateAllCalculations,
    validarConsistenciaGeral,
    mostrarNotificacao,
    verificarVinculoComProjeto
});

const anviAtual = anviOperations.getState();
let salvarANVI = anviOperations.salvarANVI;
const carregarANVIs = anviOperations.carregarANVIs;
const abrirANVI = anviOperations.abrirANVI;
const excluirANVI = anviOperations.excluirANVI;
const limparBloqueiosAntigos = anviOperations.limparBloqueiosAntigos;
const desbloquearANVI = anviOperations.desbloquearANVI;
const abrirANVISomenteLeitura = anviOperations.abrirANVISomenteLeitura;

// Exportar funções para o escopo global
window.aplicarClassificacaoFiscal = aplicarClassificacaoFiscal;
window.aplicarClassificacaoFiscalParaCalculos = aplicarClassificacaoFiscalParaCalculos;
window.adicionarSelecionadosParaClassificacaoFiscal = adicionarSelecionadosParaClassificacaoFiscal;
window.novaANVI = novaANVI;
window.salvarANVI = salvarANVI;
window.validarConsistenciaGeral = validarConsistenciaGeral;
window.gerarPDFCompleto = gerarPDFCompleto;
window.gerarPDFANVI = gerarPDFANVI;
window.exportarDREExcel = exportarDREExcel;
window.carregarANVIs = carregarANVIs;
window.abrirANVI = abrirANVI;
window.excluirANVI = excluirANVI;
window.abrirANVISomenteLeitura = abrirANVISomenteLeitura;
window.desbloquearANVI = desbloquearANVI;
window.adicionarLinhaManual = adicionarLinhaManual;
window.adicionarLinhaProcesso = adicionarLinhaProcesso;
window.adicionarLinhaFerramental = adicionarLinhaFerramental;
window.adicionarLinhaEmbalagem = adicionarLinhaEmbalagem;
window.adicionarLinhaMOD = adicionarLinhaMOD;
window.adicionarLinhaCustoIndireto = adicionarLinhaCustoIndireto;
window.adicionarSelecionadosParaTabela = adicionarSelecionadosParaTabela;
window.adicionarSelecionadosParaMateriais = adicionarSelecionadosParaMateriais;
window.adicionarSelecionadosParaProcesso = adicionarSelecionadosParaProcesso;
window.adicionarSelecionadosParaFerramental = adicionarSelecionadosParaFerramental;
window.adicionarSelecionadosParaMateriaisFerramental = adicionarSelecionadosParaMateriaisFerramental;
window.adicionarSelecionadosParaCustosIndiretos = adicionarSelecionadosParaCustosIndiretos;
window.adicionarSelecionadosParaEmbalagem = adicionarSelecionadosParaEmbalagem;
window.adicionarSelecionadosParaMaoObra = adicionarSelecionadosParaMaoObra;
window.fazerLogout = fazerLogout;
window.abrirModalUsuarios = abrirModalUsuarios;
window.abrirModalNovoUsuario = abrirModalNovoUsuario;
window.editarUsuario = editarUsuario;
window.salvarUsuario = salvarUsuario;
window.excluirUsuario = excluirUsuario;

// Fluxo de etapas e demo comercial movidos para assets/js/anvi-workflow.js.

// Inicializar eventos de fechamento do modal de desenhos
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('desenhoModal');
    const closeBtn = document.querySelector('.desenho-modal-close');
    const prevBtn = document.getElementById('desenhoPrev');
    const nextBtn = document.getElementById('desenhoNext');
    
    if (closeBtn) {
        closeBtn.addEventListener('click', fecharModal);
    }
    
    if (prevBtn) {
        prevBtn.addEventListener('click', desenhoAnterior);
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', proximoDesenho);
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharModal();
        } else if (e.key === 'ArrowRight') {
            if (document.getElementById('desenhoModal').style.display === 'block') {
                proximoDesenho();
            }
        } else if (e.key === 'ArrowLeft') {
            if (document.getElementById('desenhoModal').style.display === 'block') {
                desenhoAnterior();
            }
        }
    });
    
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                fecharModal();
            }
        });
    }
});

// Fluxo de criacao de nova ANVI movido para assets/js/anvi-new-form.js.

// Integracao com projetos movida para assets/js/anvi-project-link.js.

// Prevenir fechamento da aba sem desbloquear
window.addEventListener('beforeunload', function(e) {
if (anviAtual.id && anviAtual.bloqueada) {
    // Desbloquear de forma síncrona (navegador pode não esperar)
    navigator.sendBeacon('api/anvi.php', JSON.stringify({
        id: anviAtual.id,
        acao: 'desbloquear'
    }));
}
});










