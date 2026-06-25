function parseMoneyFromText(text) {
    return parseFloat(String(text || '')
        .replace('R$', '')
        .replace('%', '')
        .replace('meses', '')
        .replace('peças', '')
        .replace(/\s/g, '')
        .replace(/\./g, '')
        .replace(',', '.')) || 0;
}

function textNumberById(id) {
    return parseMoneyFromText(document.getElementById(id)?.textContent || document.getElementById(id)?.value || 0);
}

function capturarResumoFinanceiroPadronizado() {
    updateAllCalculations();

    const volumeMensal = parseFloat(document.getElementById('monthlyVolume')?.value) || 0;
    const custoUnitario = textNumberById('resumoCustoUnitario');
    const precoSugerido = textNumberById('precoSugeridoDestaque');
    const receitaMensal = textNumberById('resumoFaturamentoMensal') || (precoSugerido * volumeMensal);
    const lucroLiquidoMensal = textNumberById('dreLucroLiquido');
    const investimentoTotal = textNumberById('resumoTotalInvestimento');
    const margemLiquida = textNumberById('dreMargemLiquida');
    const roi = textNumberById('roiCalculado') || textNumberById('dreROI');
    const payback = textNumberById('paybackCalculado') || textNumberById('drePayback');
    const custoTotalMensal = custoUnitario * volumeMensal;
    const alertas = [];

    if (margemLiquida > 0 && margemLiquida < 10) {
        alertas.push({ tipo: 'margem_baixa', severidade: 'critico', mensagem: 'Margem líquida abaixo de 10%' });
    } else if (margemLiquida > 0 && margemLiquida < 15) {
        alertas.push({ tipo: 'margem_atencao', severidade: 'atencao', mensagem: 'Margem líquida abaixo do recomendado' });
    }
    if (roi > 0 && roi < INDUSTRY.ROI_MINIMO_RECOMENDADO) {
        alertas.push({ tipo: 'roi_baixo', severidade: 'atencao', mensagem: 'ROI anual abaixo de 20%' });
    }
    if (payback > INDUSTRY.PAYBACK_MAXIMO_RECOMENDADO) {
        alertas.push({ tipo: 'payback_longo', severidade: 'atencao', mensagem: 'Payback acima de 24 meses' });
    }

    return {
        schema_version: 1,
        moeda: 'BRL',
        volume_mensal: Number(volumeMensal.toFixed(2)),
        custos: {
            custo_unitario: Number(custoUnitario.toFixed(2)),
            custo_total_mensal: Number(custoTotalMensal.toFixed(2)),
            materia_prima: textNumberById('resumoMateriaPrima'),
            insumos: textNumberById('resumoInsumos'),
            componentes: textNumberById('resumoComponentes'),
            embalagem: textNumberById('resumoEmbalagem'),
            mao_obra_direta: textNumberById('resumoMaoObraDireta'),
            mao_obra_indireta: textNumberById('resumoMaoObraIndireta'),
            processo: textNumberById('resumoProcesso'),
            indiretos_fixos: textNumberById('resumoCustosIndiretosFixos'),
            indiretos_variaveis: textNumberById('resumoCustosIndiretosVariaveis')
        },
        receitas: {
            preco_sugerido: Number(precoSugerido.toFixed(2)),
            receita_mensal: Number(receitaMensal.toFixed(2)),
            lucro_liquido_mensal: Number(lucroLiquidoMensal.toFixed(2))
        },
        investimentos: {
            investimento_total: Number(investimentoTotal.toFixed(2)),
            ferramental: textNumberById('resumoFerramental'),
            materiais_ferramental: textNumberById('resumoMateriaisFerramental')
        },
        indicadores: {
            margem_esperada_pct: Number(margemLiquida.toFixed(2)),
            margem_liquida_pct: Number(margemLiquida.toFixed(2)),
            custo_total: Number(custoTotalMensal.toFixed(2)),
            preco_sugerido: Number(precoSugerido.toFixed(2)),
            payback_meses: Number(payback.toFixed(2)),
            roi_pct: Number(roi.toFixed(2)),
            desvio_estimado_realizado_valor: 0,
            desvio_estimado_realizado_pct: 0
        },
        alertas,
        calculado_em: new Date().toISOString()
    };
}

function capturarTodosDados() {
    const dados = {
        informacoesBasicas: {
            anviNumber: document.getElementById('anviNumber').value,
            dataANVI: document.getElementById('dataANVI').value,
            revisaoANVI: document.getElementById('revisaoANVI').value,
            lastUpdateDate: document.getElementById('lastUpdateDate').value,
            statusAprovacao: document.getElementById('statusAprovacao').value,
            client: document.getElementById('client').value,
            project: document.getElementById('project').value,
            codigo: document.getElementById('codigo').value,
            productDescription: document.getElementById('productDescription').value,
            segment: document.getElementById('segment').value,
            monthlyVolume: document.getElementById('monthlyVolume').value,
            desenhoDate: document.getElementById('desenhoDate').value,
            revisao: document.getElementById('revisao').value,
            glassType: document.getElementById('glassType').value,
            geometry: document.getElementById('geometry').value,
            thickness: document.getElementById('thickness').value,
            width: document.getElementById('width').value,
            height: document.getElementById('height').value,
            area: document.getElementById('area').value,
            commercialArea: document.getElementById('commercialArea').value,
            responsavelTecnica: document.getElementById('responsavelTecnica').value,
            responsavelComercial: document.getElementById('responsavelComercial').value,
            responsavelEconomica: document.getElementById('responsavelEconomica').value,
            responsavelFiscal: document.getElementById('responsavelFiscal').value,
            observacaoGeral: document.getElementById('observacaoGeral').value,
            glassColor: document.getElementById('glassColor').value,
            pvbType: document.getElementById('pvbType').value
        },
        configuracoes: {
            margemLucroMarkup: document.getElementById('margemLucroMarkup').value,
            regimeTributario: document.getElementById('regimeTributario').value,
            regimePisCofins: document.getElementById('regimePisCofins').value,
            percentualPresumido: document.getElementById('percentualPresumido').value,
            aliquotaIPI: document.getElementById('aliquotaIPI').value,
            aliquotaPIS: document.getElementById('aliquotaPIS').value,
            aliquotaCOFINS: document.getElementById('aliquotaCOFINS').value,
            aliquotaICMS: document.getElementById('aliquotaICMS').value,
            aliquotaISS: document.getElementById('aliquotaISS').value,
            aliquotaIRPJ: document.getElementById('aliquotaIRPJ').value,
            aliquotaCSLL: document.getElementById('aliquotaCSLL').value,
            aliquotaIRPJAdicional: document.getElementById('aliquotaIRPJAdicional').value,
            metodoRateio: document.getElementById('metodoRateio').value,
            horasTrabalhadasMes: document.getElementById('horasTrabalhadasMes').value,
            aliquotaCBS: document.getElementById('aliquotaCBS')?.value || 12.5,
            aliquotaIBS: document.getElementById('aliquotaIBS')?.value || 14,
            aliquotaIS: document.getElementById('aliquotaIS')?.value || 0,
            encargosSociais: document.getElementById('encargosSociais')?.value || 80
        },
        financeiro: capturarResumoFinanceiroPadronizado(),
        checkboxes: {
            ppap: document.getElementById('ppap').checked,
            viabilidadeTecnica: document.getElementById('viabilidadeTecnica').checked,
            viabilidadeEconomica: document.getElementById('viabilidadeEconomica').checked,
            viabilidadeComercial: document.getElementById('viabilidadeComercial').checked,
            viabilidadeFiscal: document.getElementById('viabilidadeFiscal').checked
        },
        tabelas: {
            homologacoes: capturarDadosTabela('homologacoesTable'),
            materiaPrima: capturarDadosTabela('materiaPrimaTable'),
            insumos: capturarDadosTabela('insumosTable'),
            componentes: capturarDadosTabela('componentesTable'),
            processo: capturarDadosTabela('processTable'),
            ferramentalTerceiro: capturarDadosTabela('toolingTable'),
            materiaisFerramentalFemea: capturarDadosTabela('materiaisFerramentalTable'),
            materiaisFerramentalMacho: capturarDadosTabela('materiaisFerramentalMachoTable'),
            materiaisFerramentalMatriz: capturarDadosTabela('materiaisFerramentalMatrizTable'),
            materiaisFerramentalGabarito: capturarDadosTabela('materiaisFerramentalGabaritoTable'),
            embalagem: capturarDadosTabela('embalagemTable'),
            normas: capturarDadosTabela('normasTable'),
            maoObraDireta: capturarDadosTabela('maoObraTable'),
            maoObraIndireta: capturarDadosTabela('maoObraIndiretaTable'),
            custosIndiretos: capturarDadosTabela('custosIndiretosTable'),
            classificacaoFiscal: capturarDadosTabela('classificacaoFiscalTable')
        },
        desenhos: desenhosANVI,
        observacoes: document.getElementById('observacoesText').value,
        timestamps: {
            dataCriacao: new Date().toISOString(),
            dataAtualizacao: new Date().toISOString()
        }
    };
    return dados;
}

function capturarDadosTabela(tableId) {
const table = document.getElementById(tableId);
if (!table) return [];

const rows = table.querySelectorAll('tbody tr');
const dados = [];

rows.forEach(row => {
    const rowData = {};
    const cells = row.querySelectorAll('td');
    
    // Pular a última célula se for o botão de ação
    for (let i = 0; i < cells.length - 1; i++) {
        const cell = cells[i];
        const input = cell.querySelector('input');
        const select = cell.querySelector('select');
        
        if (input) {
            if (input.type === 'checkbox') {
                rowData[`col${i}`] = input.checked;
            } else if (input.type === 'number' || input.type === 'text') {
                rowData[`col${i}`] = input.value;
            }
        } else if (select) {
            rowData[`col${i}`] = select.value;
        } else {
            rowData[`col${i}`] = cell.textContent.trim();
        }
    }
    
    if (Object.keys(rowData).length > 0) {
        dados.push(rowData);
    }
});

console.log(`Dados capturados da tabela ${tableId}:`, dados); // Para debug
return dados;
}

function restaurarTodosDados(anviData) {
    if (!anviData) return;

    if (anviData.informacoesBasicas) {
        Object.keys(anviData.informacoesBasicas).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                element.value = anviData.informacoesBasicas[key] || '';
            }
        });
    }

    if (anviData.configuracoes) {
        Object.keys(anviData.configuracoes).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                element.value = anviData.configuracoes[key] || '';
            }
        });
        
        const cbsElement = document.getElementById('aliquotaCBS');
        const ibsElement = document.getElementById('aliquotaIBS');
        const isElement = document.getElementById('aliquotaIS');
        
        if (cbsElement && anviData.configuracoes.aliquotaCBS) {
            cbsElement.value = anviData.configuracoes.aliquotaCBS;
        }
        if (ibsElement && anviData.configuracoes.aliquotaIBS) {
            ibsElement.value = anviData.configuracoes.aliquotaIBS;
        }
        if (isElement && anviData.configuracoes.aliquotaIS) {
            isElement.value = anviData.configuracoes.aliquotaIS;
        }
        
        const encargosElement = document.getElementById('encargosSociais');
        if (encargosElement && anviData.configuracoes.encargosSociais) {
            encargosElement.value = anviData.configuracoes.encargosSociais;
        }
        
        updatePisCofinsByRegime();
    }

    if (anviData.checkboxes) {
        Object.keys(anviData.checkboxes).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                element.checked = anviData.checkboxes[key] || false;
            }
        });
    }

    if (anviData.tabelas) {
        Object.keys(anviData.tabelas).forEach(tableKey => {
            const tableId = mapTableKeyToId(tableKey);
            if (tableId) {
                restaurarDadosTabela(tableId, anviData.tabelas[tableKey]);
            }
        });
    }

    if (anviData.observacoes) {
        document.getElementById('observacoesText').value = anviData.observacoes;
    }

    setTimeout(() => {
        calculateArea();
        updateAllCalculations();
        validarConsistenciaGeral();
    }, 300);
}

function mapTableKeyToId(tableKey) {
    const mapping = {
        'homologacoes': 'homologacoesTable',
        'materiaPrima': 'materiaPrimaTable',
        'insumos': 'insumosTable',
        'componentes': 'componentesTable',
        'processo': 'processTable',
        'ferramentalTerceiro': 'toolingTable',
        'materiaisFerramentalFemea': 'materiaisFerramentalTable',
        'materiaisFerramentalMacho': 'materiaisFerramentalMachoTable',
        'materiaisFerramentalMatriz': 'materiaisFerramentalMatrizTable',
        'materiaisFerramentalGabarito': 'materiaisFerramentalGabaritoTable',
        'embalagem': 'embalagemTable',
        'normas': 'normasTable',
        'maoObraDireta': 'maoObraTable',
        'maoObraIndireta': 'maoObraIndiretaTable',
        'custosIndiretos': 'custosIndiretosTable',
        'classificacaoFiscal': 'classificacaoFiscalTable'
    };
    return mapping[tableKey] || null;
}

function restaurarDadosTabela(tableId, dados) {
    if (tableId === 'homologacoesTable') {
        restaurarDadosHomologacoes(tableId, dados);
        return;
    }

    if (tableId === 'custosIndiretosTable') {
        restaurarDadosCustosIndiretos(dados);
        return;
    }

    if (tableId === 'processTable') {
        restaurarDadosProcessos(dados);
        return;
    }

    if (tableId === 'materiaPrimaTable' || tableId === 'insumosTable' || tableId === 'componentesTable') {
        restaurarDadosMateriaisComImpostos(tableId, dados);
        return;
    }

    if (tableId === 'toolingTable' || 
        tableId === 'materiaisFerramentalTable' || 
        tableId === 'materiaisFerramentalMachoTable' || 
        tableId === 'materiaisFerramentalMatrizTable' || 
        tableId === 'materiaisFerramentalGabaritoTable') {
        
        restaurarDadosFerramental(tableId, dados);
        return;
    }

    if (tableId === 'embalagemTable') {
        restaurarDadosEmbalagem(tableId, dados);
        return;
    }

    if (tableId === 'maoObraTable' || tableId === 'maoObraIndiretaTable') {
        restaurarDadosMaoObra(tableId, dados);
        return;
    }

    const table = document.getElementById(tableId);
    if (!table || !dados || dados.length === 0) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    dados.forEach(rowData => {
        const newRow = tbody.insertRow();
        const colCount = Object.keys(rowData).length;

        for (let i = 0; i < colCount; i++) {
            const cell = newRow.insertCell(i);
            const value = rowData[`col${i}`];

            const headerRow = table.querySelector('thead tr');
            if (headerRow) {
                const headerCells = headerRow.querySelectorAll('th');
                if (headerCells[i]) {
                    const headerText = headerCells[i].textContent.toLowerCase();

                    if (headerText.includes('ação')) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn btn-sm btn-danger remove-row';
                        button.innerHTML = '<i class="fas fa-trash"></i>';
                        button.addEventListener('click', function() {
                            this.closest('tr').remove();
                            synchronizeCalculations();
                        });
                        cell.appendChild(button);
                        continue;
                    }
                }
            }

            cell.textContent = value || '';
        }

        const actionCell = newRow.insertCell(colCount);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-danger remove-row';
        button.innerHTML = '<i class="fas fa-trash"></i>';
        button.addEventListener('click', function() {
            this.closest('tr').remove();
            synchronizeCalculations();
        });
        actionCell.appendChild(button);
    });
}

function restaurarDadosHomologacoes(tableId, dados) {
    const table = document.getElementById(tableId);
    if (!table || !dados || dados.length === 0) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    dados.forEach(rowData => {
        const newRow = tbody.insertRow();
        
        const cell1 = newRow.insertCell(0);
        cell1.textContent = rowData['col0'] || '';

        const cell2 = newRow.insertCell(1);
        cell2.textContent = rowData['col1'] || '';

        const cell3 = newRow.insertCell(2);
        cell3.textContent = rowData['col2'] || '';

        const cell4 = newRow.insertCell(3);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-danger remove-row';
        button.innerHTML = '<i class="fas fa-trash"></i>';
        button.addEventListener('click', function() {
            this.closest('tr').remove();
            synchronizeCalculations();
        });
        cell4.appendChild(button);
    });
}

function restaurarDadosMateriaisComImpostos(tableId, dados) {
    const table = document.getElementById(tableId);
    if (!table || !dados || dados.length === 0) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    dados.forEach(rowData => {
        const newRow = tbody.insertRow();

        const colCount = Object.keys(rowData).length;
        for (let i = 0; i < colCount; i++) {
            const cell = newRow.insertCell(i);
            const value = rowData[`col${i}`];

            const headerRow = table.querySelector('thead tr');
            if (headerRow) {
                const headerCells = headerRow.querySelectorAll('th');
                if (headerCells[i]) {
                    const headerText = headerCells[i].textContent.toLowerCase();

                    if (headerText.includes('ação')) {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'btn btn-sm btn-danger remove-row';
                        button.innerHTML = '<i class="fas fa-trash"></i>';
                        button.addEventListener('click', function() {
                            this.closest('tr').remove();
                            synchronizeCalculations();
                        });
                        cell.appendChild(button);
                        continue;
                    }

                    if (headerText.includes('quantidade') || headerText.includes('valor unit') || 
                        headerText.includes('valor total') || headerText.includes('alíq') ||
                        headerText.includes('crédito') || headerText.includes('líquido')) {

                        const input = document.createElement('input');
                        input.type = 'number';
                        input.className = 'form-control form-control-sm';

                        if (headerText.includes('quantidade')) {
                            input.classList.add('qty-input');
                            input.step = '0.01';
                        }
                        if (headerText.includes('valor unit')) {
                            input.classList.add('unit-price-input');
                            input.step = '0.01';
                        }
                        if (headerText.includes('alíq. ipi')) {
                            input.classList.add('ipi-aliquota-input');
                            input.step = '0.1';
                        }
                        if (headerText.includes('alíq. icms')) {
                            input.classList.add('icms-aliquota-input');
                            input.step = '0.1';
                        }
                        if (headerText.includes('valor total c/ impostos')) {
                            input.classList.add('gross-total');
                            input.readOnly = true;
                        }
                        if (headerText.includes('valor crédito')) {
                            input.classList.add('credit-value');
                            input.readOnly = true;
                        }
                        if (headerText.includes('valor líquido')) {
                            input.classList.add('total-price');
                            input.readOnly = true;
                        }

                        input.value = value || '0';
                        input.addEventListener('input', synchronizeCalculations);
                        cell.appendChild(input);
                        continue;
                    }

                    if (headerText.includes('código') || headerText.includes('descrição') || 
                        headerText.includes('ncm') || headerText.includes('unidade') ||
                        headerText.includes('tipo') || headerText.includes('espessura')) {

                        const input = document.createElement('input');
                        input.type = 'text';
                        input.className = 'form-control form-control-sm';
                        input.value = value || '';
                        cell.appendChild(input);
                        continue;
                    }
                }
            }

            cell.textContent = value || '';
        }
    });
}

function restaurarDadosFerramental(tableId, dados) {
    const table = document.getElementById(tableId);
    if (!table || !dados || dados.length === 0) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    dados.forEach(rowData => {
        const newRow = tbody.insertRow();
        const headerRow = table.querySelector('thead tr');
        if (!headerRow) return;

        const headerCells = headerRow.querySelectorAll('th');
        const colCount = headerCells.length;

        for (let i = 0; i < colCount - 1; i++) {
            const cell = newRow.insertCell(i);
            const headerText = headerCells[i].textContent.toLowerCase();
            const value = rowData[`col${i}`] || '';

            if (headerText.includes('código') || headerText.includes('descrição') || headerText.includes('unidade')) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = value;
                cell.appendChild(input);
            } else if (headerText.includes('vida útil')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm tooling-life-input';
                input.step = '1000';
                input.value = value || '100000';
                input.addEventListener('input', synchronizeCalculations);
                cell.appendChild(input);
            } else if (headerText.includes('quantidade')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm qty-input';
                input.step = '1';
                input.value = value || '1';
                input.addEventListener('input', synchronizeCalculations);
                cell.appendChild(input);
            } else if (headerText.includes('valor unit')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm unit-price-input';
                input.step = '0.01';
                input.value = value || '0';
                input.addEventListener('input', synchronizeCalculations);
                cell.appendChild(input);
            } else if (headerText.includes('valor total')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm total-price';
                input.step = '0.01';
                input.value = value || '0';
                input.readOnly = true;
                cell.appendChild(input);
            } else if (headerText.includes('custo/peça')) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm tooling-per-piece';
                input.step = '0.00001';
                input.value = value || '0';
                input.readOnly = true;
                cell.appendChild(input);
            }
        }

        const actionCell = newRow.insertCell(colCount - 1);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-danger remove-row';
        button.innerHTML = '<i class="fas fa-trash"></i>';
        button.addEventListener('click', function() {
            this.closest('tr').remove();
            synchronizeCalculations();
        });
        actionCell.appendChild(button);
    });
}

function restaurarDadosEmbalagem(tableId, dados) {
    const table = document.getElementById(tableId);
    if (!table || !dados || dados.length === 0) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    dados.forEach(rowData => {
        const newRow = tbody.insertRow();

        for (let i = 0; i < 8; i++) {
            const cell = newRow.insertCell(i);
            const value = rowData[`col${i}`] || '';

            if (i === 0 || i === 1 || i === 2) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = value;
                cell.appendChild(input);
            } else if (i === 3) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm packaging-qty-input';
                input.step = '1';
                input.value = value || '1';
                input.addEventListener('input', synchronizeCalculations);
                cell.appendChild(input);
            } else if (i === 4) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm qty-per-package-input';
                input.step = '1';
                input.value = value || '1';
                input.addEventListener('input', synchronizeCalculations);
                cell.appendChild(input);
            } else if (i === 5) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm unit-price-input';
                input.step = '0.01';
                input.value = value || '0';
                input.addEventListener('input', synchronizeCalculations);
                cell.appendChild(input);
            } else if (i === 6) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm total-price';
                input.step = '0.01';
                input.value = value || '0';
                input.readOnly = true;
                cell.appendChild(input);
            } else if (i === 7) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-danger remove-row';
                button.innerHTML = '<i class="fas fa-trash"></i>';
                button.addEventListener('click', function() {
                    this.closest('tr').remove();
                    synchronizeCalculations();
                });
                cell.appendChild(button);
            }
        }
    });
}

function restaurarDadosMaoObra(tableId, dados) {
    const table = document.getElementById(tableId);
    if (!table || !dados || dados.length === 0) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    dados.forEach(rowData => {
        const newRow = tbody.insertRow();

        for (let i = 0; i < 8; i++) {
            const cell = newRow.insertCell(i);
            const value = rowData[`col${i}`] || '';

            if (i === 0 || i === 1 || i === 2) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = value;
                cell.appendChild(input);
            } else if (i === 3) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm time-input';
                input.step = '0.1';
                input.value = value || '0';
                input.addEventListener('input', synchronizeCalculations);
                cell.appendChild(input);
            } else if (i === 4) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm qty-input';
                input.step = '1';
                input.value = value || '1';
                input.addEventListener('input', synchronizeCalculations);
                cell.appendChild(input);
            } else if (i === 5) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm unit-price-input';
                input.step = '0.01';
                input.value = value || '0';
                input.addEventListener('input', synchronizeCalculations);
                cell.appendChild(input);
            } else if (i === 6) {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm total-price';
                input.step = '0.01';
                input.value = value || '0';
                input.readOnly = true;
                cell.appendChild(input);
            } else if (i === 7) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-danger remove-row';
                button.innerHTML = '<i class="fas fa-trash"></i>';
                button.addEventListener('click', function() {
                    this.closest('tr').remove();
                    synchronizeCalculations();
                });
                cell.appendChild(button);
            }
        }
    });
}

function restaurarDadosProcessos(dados) {
    const table = document.getElementById('processTable');
    if (!table || !dados || dados.length === 0) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    dados.forEach(rowData => {
        const newRow = tbody.insertRow();

        for (let i = 0; i < 14; i++) {
            const cell = newRow.insertCell(i);
            const value = rowData[`col${i}`] || '';

            if (i === 0 || i === 1) {
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control form-control-sm';
                input.value = value;
                cell.appendChild(input);
            } else if (i === 13) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-sm btn-danger remove-row';
                button.innerHTML = '<i class="fas fa-trash"></i>';
                button.addEventListener('click', function() {
                    this.closest('tr').remove();
                    updateProcessTotal();
                    synchronizeCalculations();
                });
                cell.appendChild(button);
            } else {
                const input = document.createElement('input');
                input.type = 'number';
                input.className = 'form-control form-control-sm';
                input.step = '0.01';
                input.value = value || '0';

                if (i === 2) input.classList.add('power-input');
                if (i === 3) input.classList.add('energy-price-input');
                if (i === 4) input.classList.add('water-input');
                if (i === 5) input.classList.add('water-price-input');
                if (i === 6) input.classList.add('efficiency-input');
                if (i === 7) input.classList.add('output-input');
                if (i === 8) input.classList.add('setup-time-input');
                if (i === 9) input.classList.add('depreciation-input');
                if (i === 10) input.classList.add('other-costs-input');
                if (i === 11) {
                    input.classList.add('hourly-cost-input');
                    input.readOnly = true;
                }
                if (i === 12) {
                    input.classList.add('process-total');
                    input.readOnly = true;
                }

                input.addEventListener('input', function() {
                    if (i <= 10) {
                        calculateHourlyCost(newRow);
                        calculateProcessCost(newRow);
                        updateProcessTotal();
                        synchronizeCalculations();
                    }
                });

                cell.appendChild(input);
            }
        }
    });

    updateProcessTotal();
}

function restaurarDadosCustosIndiretos(dados) {
    const table = document.getElementById('custosIndiretosTable');
    if (!table || !dados || dados.length === 0) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    dados.forEach(rowData => {
        const newRow = tbody.insertRow();

        const cell1 = newRow.insertCell(0);
        const input1 = document.createElement('input');
        input1.type = 'text';
        input1.className = 'form-control form-control-sm';
        input1.value = rowData['col0'] || '';
        cell1.appendChild(input1);

        const cell2 = newRow.insertCell(1);
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';

        const optionFixo = document.createElement('option');
        optionFixo.value = 'fixo';
        optionFixo.textContent = 'Custo Fixo';

        const optionVariavel = document.createElement('option');
        optionVariavel.value = 'variavel';
        optionVariavel.textContent = 'Custo Variável';

        select.appendChild(optionFixo);
        select.appendChild(optionVariavel);
        select.value = rowData['col1'] || 'fixo';
        select.addEventListener('change', function() {
            updateCustosIndiretosTotalCorrigidoComBloqueio();
        });
        cell2.appendChild(select);

        const cell3 = newRow.insertCell(2);
        const input3 = document.createElement('input');
        input3.type = 'number';
        input3.className = 'form-control form-control-sm custo-fixo-input';
        input3.step = '0.01';
        input3.value = rowData['col2'] || '0';
        input3.addEventListener('input', function() {
            updateCustosIndiretosTotalCorrigidoComBloqueio();
        });
        cell3.appendChild(input3);

        const cell4 = newRow.insertCell(3);
        const input4 = document.createElement('input');
        input4.type = 'number';
        input4.className = 'form-control form-control-sm rateio-percent-input';
        input4.step = '0.1';
        input4.value = rowData['col3'] || '100';
        input4.addEventListener('input', function() {
            updateCustosIndiretosTotalCorrigidoComBloqueio();
        });
        cell4.appendChild(input4);

        const cell5 = newRow.insertCell(4);
        const input5 = document.createElement('input');
        input5.type = 'number';
        input5.className = 'form-control form-control-sm rateio-valor';
        input5.step = '0.01';
        input5.value = rowData['col4'] || '0';
        input5.readOnly = true;
        cell5.appendChild(input5);

        const cell6 = newRow.insertCell(5);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm btn-danger remove-row';
        button.innerHTML = '<i class="fas fa-trash"></i>';
        button.addEventListener('click', function() {
            this.closest('tr').remove();
            updateCustosIndiretosTotalCorrigidoComBloqueio();
        });
        cell6.appendChild(button);
    });

    updateCustosIndiretosTotalCorrigidoComBloqueio();
}

