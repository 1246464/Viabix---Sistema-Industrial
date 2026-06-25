// FUNÇÕES DE CAPABILIDADE (manter as originais)
// ==============================================
function updateCapabilityProjectInfo() {
    const projectId = currentEditingProjectId;
    if (!projectId) {
        document.getElementById('capabilityProjectInfo').innerHTML = '<p>Selecione um projeto para realizar o estudo de capabilidade.</p>';
        return;
    }
    
    const project = projects.find(p => p.id === projectId);
    if (!project) return;
    
    const infoHtml = `
        <div class="project-info-item-capability">
            <div class="project-info-label-capability">Cliente</div>
            <div class="project-info-value-capability">${project.cliente || '-'}</div>
        </div>
        <div class="project-info-item-capability">
            <div class="project-info-label-capability">Projeto</div>
            <div class="project-info-value-capability">${project.projectName || '-'}</div>
        </div>
        <div class="project-info-item-capability">
            <div class="project-info-label-capability">Modelo</div>
            <div class="project-info-value-capability">${project.modelo || '-'}</div>
        </div>
        <div class="project-info-item-capability">
            <div class="project-info-label-capability">Código</div>
            <div class="project-info-value-capability">${project.codigo || '-'}</div>
        </div>
        <div class="project-info-item-capability">
            <div class="project-info-label-capability">Processo</div>
            <div class="project-info-value-capability">${project.processo || '-'}</div>
        </div>
        <div class="project-info-item-capability">
            <div class="project-info-label-capability">Segmento</div>
            <div class="project-info-value-capability">${project.segmento || '-'}</div>
        </div>
        <div class="project-info-item-capability">
            <div class="project-info-label-capability">Líder</div>
            <div class="project-info-value-capability">${project.projectLeader || '-'}</div>
        </div>
    `;
    
    document.getElementById('capabilityProjectInfo').innerHTML = infoHtml;
}

function addCapabilityCharacteristic() {
    const container = document.getElementById('capabilityCharacteristics');
    if (!container) return;
    
    const characteristicCount = container.children.length;
    const newId = `char_${Date.now()}_${characteristicCount}`;
    
    const charDiv = document.createElement('div');
    charDiv.className = 'characteristic-card';
    charDiv.id = newId;
    
    // Gerar 5 amostras com 25 medições cada (linhas da tabela)
    let measurementsHTML = '';
    for (let amostra = 1; amostra <= 5; amostra++) {
        measurementsHTML += '<tr>';
        measurementsHTML += `<td style="background: #e8f5e9; font-weight: bold; text-align: center;">Amostra ${amostra}</td>`;
        for (let med = 1; med <= 25; med++) {
            measurementsHTML += `<td><input type="number" class="measurement" step="0.001" placeholder="M${med}"></td>`;
        }
        measurementsHTML += '</tr>';
    }
    
    const todayDate = new Date().toISOString().split('T')[0];
    
    charDiv.innerHTML = `
        <div class="characteristic-header">
            <div>
                <input type="text" class="characteristic-name" placeholder="Nome da característica (ex: Diâmetro)" style="width: 250px; padding: 5px;">
                <select class="characteristic-type" style="margin-left: 10px; padding: 5px;">
                    <option value="cc">CC - Característica Crítica</option>
                    <option value="sc">SC - Característica Significativa</option>
                </select>
            </div>
            <button class="remove-characteristic-btn" onclick="removeCapabilityCharacteristic('${newId}')">
                <i class="fas fa-trash"></i> Remover
            </button>
        </div>
        
        <div class="characteristic-inputs">
            <div class="form-group">
                <label>LIE (Limite Inferior)</label>
                <input type="number" class="lie" step="0.001" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>LSE (Limite Superior)</label>
                <input type="number" class="lse" step="0.001" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Alvo</label>
                <input type="number" class="target" step="0.001" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>Tolerância</label>
                <input type="text" class="tolerance" readonly placeholder="Calculado" style="background: #f0f0f0;">
            </div>
        </div>
        
        <div id="${newId}_warning" class="warning-message" style="display: none;">
            <i class="fas fa-exclamation-triangle"></i> Aviso: O estudo requer 125 medições (5 amostras x 25 medições).
        </div>
        
        <h4>Medições (5 amostras, cada uma com 25 medições)</h4>
        <div style="overflow-x: auto;">
            <table class="measurement-table">
                <thead>
                    <tr>
                        <th>Amostra</th>
                        ${Array.from({ length: 25 }, (_, i) => `<th>M${i+1}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${measurementsHTML}
                </tbody>
            </table>
        </div>
        
        <div class="chart-container-small" id="${newId}_chart"></div>
        
        <div class="memorial-calculo" id="${newId}_memorial">
            <h4>Memorial de Cálculo - Metodologia Seis Sigma</h4>
            <div class="formula">
                <p><strong>Fórmulas Utilizadas:</strong></p>
                <p><code>Média (μ) = Σx / n</code> (Soma das medições dividido pelo número de amostras)</p>
                <p><code>Desvio Padrão (σ) = √[Σ(x - μ)² / (n-1)]</code> (Desvio padrão amostral)</p>
                <p><code>Cp = (LSE - LIE) / (6σ)</code> (Capacidade potencial do processo)</p>
                <p><code>Cpu = (LSE - μ) / (3σ)</code> (Capacidade unilateral superior)</p>
                <p><code>Cpl = (μ - LIE) / (3σ)</code> (Capacidade unilateral inferior)</p>
                <p><code>Cpk = min(Cpu, Cpl)</code> (Capacidade real do processo)</p>
                <p><code>Pp = (LSE - LIE) / (6σ<sub>pop</sub>)</code> (Performance potencial, usando σ populacional)</p>
                <p><code>Ppk = min[(LSE - μ)/(3σ<sub>pop</sub>), (μ - LIE)/(3σ<sub>pop</sub>)]</code> (Performance real)</p>
                <p><code>Nível Sigma (Z) = min(Cpu, Cpl) × 3</code> (Aproximação do nível sigma)</p>
                <p><code>DPMO = 1.000.000 × (1 - Φ(Z))</code> (Defeitos por milhão, aproximado por tabela)</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Parâmetro</th>
                        <th>Valor</th>
                        <th>Interpretação</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Número de Amostras (n)</td><td id="${newId}_n">0</td><td>-</td></tr>
                    <tr><td>Média (μ)</td><td id="${newId}_mean2">-</td><td>Centralização do processo</td></tr>
                    <tr><td>Desvio Padrão (σ)</td><td id="${newId}_stddev2">-</td><td>Variabilidade do processo</td></tr>
                    <tr><td>LIE (Limite Inferior)</td><td id="${newId}_lie2">-</td><td>Especificação mínima</td></tr>
                    <tr><td>LSE (Limite Superior)</td><td id="${newId}_lse2">-</td><td>Especificação máxima</td></tr>
                    <tr><td>Tolerância (LSE - LIE)</td><td id="${newId}_tol2">-</td><td>Largura da especificação</td></tr>
                    <tr><td>Cp</td><td id="${newId}_cp2">-</td><td>Capacidade potencial (ignora centralização)</td></tr>
                    <tr><td>Cpk</td><td id="${newId}_cpk2">-</td><td>Capacidade real (considera centralização)</td></tr>
                    <tr><td>Pp</td><td id="${newId}_pp2">-</td><td>Performance potencial (σ populacional)</td></tr>
                    <tr><td>Ppk</td><td id="${newId}_ppk2">-</td><td>Performance real (σ populacional)</td></tr>
                    <tr><td>Nível Sigma (Z)</td><td id="${newId}_sigma2">-</td><td>Número de desvios padrão até o limite</td></tr>
                    <tr><td>DPMO Estimado</td><td id="${newId}_dpmo2">-</td><td>Defeitos por milhão de oportunidades</td></tr>
                </tbody>
            </table>
            
            <div class="interpretacao-seis-sigma" id="${newId}_interpretacao_detalhada">
                <strong>Interpretação Detalhada (Seis Sigma):</strong><br>
                <span id="${newId}_interpretacao_texto">Insira os valores de LIE, LSE e medições para calcular.</span>
            </div>
        </div>
        
        <div class="capability-results">
            <h4>Resultados da Análise (Metodologia Seis Sigma)</h4>
            <div class="results-grid">
                <div class="result-item">
                    <div class="result-label">Média (μ)</div>
                    <div class="result-value" id="${newId}_mean">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Desvio Padrão (σ)</div>
                    <div class="result-value" id="${newId}_stddev">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Cp (Capacidade Potencial)</div>
                    <div class="result-value" id="${newId}_cp">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Cpk (Capacidade Real)</div>
                    <div class="result-value" id="${newId}_cpk">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Pp (Performance Potencial)</div>
                    <div class="result-value" id="${newId}_pp">-</div>
                </div>
                <div class="result-item">
                    <div class="result-label">Ppk (Performance Real)</div>
                    <div class="result-value" id="${newId}_ppk">-</div>
                </div>
            </div>
            
            <div class="capability-interpretation" id="${newId}_interpretation">
                <strong>Interpretação:</strong> Insira os valores de LIE, LSE e medições.
            </div>
        </div>
    `;
    
    container.appendChild(charDiv);
    
    // Adicionar event listeners
    const inputs = charDiv.querySelectorAll('input.lie, input.lse, input.target, input.measurement');
    inputs.forEach(input => {
        input.addEventListener('input', () => updateCapabilityResults(newId));
    });
    
    const lieInput = charDiv.querySelector('.lie');
    const lseInput = charDiv.querySelector('.lse');
    const toleranceInput = charDiv.querySelector('.tolerance');
    
    if (lieInput && lseInput && toleranceInput) {
        [lieInput, lseInput].forEach(input => {
            input.addEventListener('input', () => {
                const lie = parseFloat(lieInput.value);
                const lse = parseFloat(lseInput.value);
                if (!isNaN(lie) && !isNaN(lse)) {
                    toleranceInput.value = (lse - lie).toFixed(3);
                } else {
                    toleranceInput.value = '';
                }
                updateCapabilityResults(newId);
            });
        });
    }
    
    const dateInput = document.getElementById('capabilityStudyDate');
    if (dateInput && !dateInput.value) {
        dateInput.value = todayDate;
    }
    
    updateCapabilityProjectInfo();
}

function removeCapabilityCharacteristic(id) {
    const element = document.getElementById(id);
    if (element) {
        element.remove();
    }
}

function getAllMeasurementsFromCharacteristic(charDiv) {
    const measurements = [];
    const measurementInputs = charDiv.querySelectorAll('.measurement');
    measurementInputs.forEach(input => {
        const value = parseFloat(input.value);
        if (!isNaN(value) && value !== '') {
            measurements.push(value);
        }
    });
    return measurements;
}

function calculateCapabilityStats(measurements, lie, lse) {
    if (measurements.length < 2 || isNaN(lie) || isNaN(lse)) {
        return null;
    }
    
    const n = measurements.length;
    const mean = measurements.reduce((a, b) => a + b, 0) / n;
    
    // Desvio padrão amostral (para Cp e Cpk)
    const varianceSample = measurements.reduce((acc, val) => acc + Math.pow(val - mean, 2), 0) / (n - 1);
    const stdDevSample = Math.sqrt(varianceSample);
    
    // Desvio padrão populacional (para Pp e Ppk)
    const variancePop = measurements.reduce((acc, val) => acc + Math.pow(val - mean, 2), 0) / n;
    const stdDevPop = Math.sqrt(variancePop);
    
    const tolerance = lse - lie;
    
    // Cp = Tolerância / (6 * σ) - Capacidade potencial do processo (assumindo processo centrado)
    const cp = tolerance > 0 && stdDevSample > 0 ? tolerance / (6 * stdDevSample) : 0;
    
    // Cálculo dos índices de capacidade unilateral
    const cpu = (lse - mean) / (3 * stdDevSample);
    const cpl = (mean - lie) / (3 * stdDevSample);
    const cpk = Math.min(cpu, cpl);
    
    // Pp = Tolerância / (6 * σ_pop) - Performance do processo (inclui variação total)
    const pp = tolerance > 0 && stdDevPop > 0 ? tolerance / (6 * stdDevPop) : 0;
    
    // Ppk - Performance real considerando centralização
    const ppu = (lse - mean) / (3 * stdDevPop);
    const ppl = (mean - lie) / (3 * stdDevPop);
    const ppk = Math.min(ppu, ppl);
    
    // Nível Sigma (Z) - Número de desvios padrão entre a média e o limite mais próximo
    const sigmaLevel = Math.min(cpu, cpl) * 3; // Aproximação do nível sigma
    
    return {
        mean,
        stdDev: stdDevSample,
        stdDevPop,
        cp,
        cpk,
        pp,
        ppk,
        cpu,
        cpl,
        sigmaLevel,
        sampleSize: n
    };
}

function updateCapabilityResults(characteristicId) {
    const charDiv = document.getElementById(characteristicId);
    if (!charDiv) return;
    
    const measurements = getAllMeasurementsFromCharacteristic(charDiv);
    
    const warningDiv = document.getElementById(`${characteristicId}_warning`);
    if (measurements.length > 0 && measurements.length < 125) {
        warningDiv.style.display = 'block';
        warningDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Aviso: Estudo requer 125 medições (5 amostras x 25 medições). Atual: ${measurements.length}.`;
    } else if (measurements.length === 125) {
        warningDiv.style.display = 'none';
    } else {
        warningDiv.style.display = 'none';
    }
    
    const lie = parseFloat(charDiv.querySelector('.lie')?.value);
    const lse = parseFloat(charDiv.querySelector('.lse')?.value);
    
    if (measurements.length < 2 || isNaN(lie) || isNaN(lse)) {
        return;
    }
    
    const stats = calculateCapabilityStats(measurements, lie, lse);
    if (!stats) return;
    
    // Atualizar resultados principais
    document.getElementById(`${characteristicId}_mean`).textContent = stats.mean.toFixed(3);
    document.getElementById(`${characteristicId}_stddev`).textContent = stats.stdDev.toFixed(3);
    document.getElementById(`${characteristicId}_cp`).textContent = stats.cp.toFixed(3);
    document.getElementById(`${characteristicId}_cpk`).textContent = stats.cpk.toFixed(3);
    document.getElementById(`${characteristicId}_pp`).textContent = stats.pp.toFixed(3);
    document.getElementById(`${characteristicId}_ppk`).textContent = stats.ppk.toFixed(3);
    
    // Atualizar memorial de cálculo
    document.getElementById(`${characteristicId}_n`).textContent = stats.sampleSize;
    document.getElementById(`${characteristicId}_mean2`).textContent = stats.mean.toFixed(3);
    document.getElementById(`${characteristicId}_stddev2`).textContent = stats.stdDev.toFixed(3);
    document.getElementById(`${characteristicId}_lie2`).textContent = lie.toFixed(3);
    document.getElementById(`${characteristicId}_lse2`).textContent = lse.toFixed(3);
    document.getElementById(`${characteristicId}_tol2`).textContent = (lse - lie).toFixed(3);
    document.getElementById(`${characteristicId}_cp2`).textContent = stats.cp.toFixed(3);
    document.getElementById(`${characteristicId}_cpk2`).textContent = stats.cpk.toFixed(3);
    document.getElementById(`${characteristicId}_pp2`).textContent = stats.pp.toFixed(3);
    document.getElementById(`${characteristicId}_ppk2`).textContent = stats.ppk.toFixed(3);
    document.getElementById(`${characteristicId}_sigma2`).textContent = stats.sigmaLevel.toFixed(2);
    
    const ppm = calculateDPMO(stats.sigmaLevel);
    document.getElementById(`${characteristicId}_dpmo2`).textContent = ppm.toFixed(0) + ' ppm';
    
    const cpElement = document.getElementById(`${characteristicId}_cp`);
    const cpkElement = document.getElementById(`${characteristicId}_cpk`);
    
    cpElement.className = 'result-value';
    cpkElement.className = 'result-value';
    
    if (stats.cp >= 1.33) cpElement.classList.add('good');
    else if (stats.cp >= 1.0) cpElement.classList.add('warning');
    else cpElement.classList.add('bad');
    
    if (stats.cpk >= 1.33) cpkElement.classList.add('good');
    else if (stats.cpk >= 1.0) cpkElement.classList.add('warning');
    else cpkElement.classList.add('bad');
    
    const interpretation = document.getElementById(`${characteristicId}_interpretation`);
    if (interpretation) {
        let interpretationText = '';
        let actionText = '';
        
        // Interpretação baseada no Cpk
        if (stats.cpk >= 1.33) {
            interpretationText = '✅ CAPABILIDADE EXCELENTE (Cpk ≥ 1.33)';
            actionText = 'O processo atende consistentemente às especificações. Nível Seis Sigma alcançado. Recomenda-se manter o controle estatístico do processo (CEP) e monitorar para evitar desvios.';
        } else if (stats.cpk >= 1.0) {
            interpretationText = '⚠️ CAPABILIDADE ADEQUADA (1.0 ≤ Cpk < 1.33)';
            actionText = 'O processo atende às especificações, mas com margem reduzida. Requer monitoramento contínuo. Recomenda-se reduzir a variação do processo e revisar os limites de controle.';
        } else if (stats.cpk >= 0.67) {
            interpretationText = '🔻 CAPABILIDADE INSUFICIENTE (0.67 ≤ Cpk < 1.0)';
            actionText = 'O processo não atende consistentemente às especificações. Ação corretiva necessária. Recomenda-se analisar as causas da variação, revisar o processo e implementar melhorias.';
        } else {
            interpretationText = '❌ PROCESSO INCAPAZ (Cpk < 0.67)';
            actionText = 'O processo é incapaz de atender às especificações. Ação imediata obrigatória. Recomenda-se interromper a produção, realizar análise aprofundada do processo, revisar especificações ou reprojetar o produto/processo.';
        }
        
        // Adicionar análise de centralização
        if (Math.abs(stats.cp - stats.cpk) > 0.2) {
            actionText += ' O processo está descentralizado (Cpk < Cp). Ajuste a média do processo para o valor alvo.';
        }
        
        // Adicionar análise de variação
        if (stats.cp < 1.0 && stats.cpk < 1.0) {
            actionText += ' A variação do processo é excessiva. Reduza a variação das causas comuns.';
        }
        
        // Adicionar nível Sigma
        const sigmaLevelText = `Nível Sigma aproximado: ${stats.sigmaLevel.toFixed(2)} (defeitos por milhão: ${calculateDPMO(stats.sigmaLevel).toFixed(0)} ppm)`;
        
        interpretation.innerHTML = `
            <strong>Interpretação Analítica:</strong><br>
            <span style="font-size: 1.1rem; font-weight: bold;">${interpretationText}</span><br>
            <span>${actionText}</span><br>
            <span style="color: #666;">${sigmaLevelText}</span><br>
            <span style="font-size: 0.85rem;">Baseado em ${stats.sampleSize} amostras.</span>
        `;
    }
    
    const interpretacaoDetalhada = document.getElementById(`${characteristicId}_interpretacao_texto`);
    if (interpretacaoDetalhada) {
        let texto = '';
        if (stats.cpk >= 1.33) {
            texto = '✅ CAPABILIDADE EXCELENTE: O processo é capaz e atende aos requisitos Seis Sigma. ';
            if (stats.cp > stats.cpk + 0.2) texto += 'O processo está descentralizado - ajuste a média para o valor alvo.';
        } else if (stats.cpk >= 1.0) {
            texto = '⚠️ CAPABILIDADE ADEQUADA: O processo atende, mas com margem reduzida. Reduza a variação.';
        } else if (stats.cpk >= 0.67) {
            texto = '🔻 CAPABILIDADE INSUFICIENTE: Ação corretiva necessária. Analise as causas especiais.';
        } else {
            texto = '❌ PROCESSO INCAPAZ: Ação imediata obrigatória. Interrompa a produção se possível.';
        }
        texto += ` Nível Sigma: ${stats.sigmaLevel.toFixed(2)} (${calculateDPMO(stats.sigmaLevel).toFixed(0)} ppm).`;
        interpretacaoDetalhada.textContent = texto;
    }
    
    // Criar gráfico de histograma com tendência polinomial
    renderCapabilityHistogram(characteristicId, measurements, lie, lse, stats);
}

// Função auxiliar para calcular DPMO (defeitos por milhão)
function calculateDPMO(sigmaLevel) {
    console.log('🔍 VALOR RECEBIDO:', sigmaLevel, 'Tipo:', typeof sigmaLevel);
    
    const sigmaLongoPrazo = sigmaLevel - 1.5;
    console.log('📊 sigmaLongoPrazo (com deslocamento):', sigmaLongoPrazo.toFixed(2));
    
    if (sigmaLongoPrazo <= 0) {
        console.log('⚠️ sigmaLongoPrazo <= 0, retornando 500000');
        return 500000;
    }
    if (sigmaLongoPrazo >= 6.0) {
        console.log('✅ sigmaLongoPrazo >= 6, retornando 3');
        return 3;
    }
    
    const sigmaTable = [
        { z: 1.5, dpmo: 501350 }, { z: 1.6, dpmo: 460170 }, { z: 1.7, dpmo: 420740 },
        { z: 1.8, dpmo: 382090 }, { z: 1.9, dpmo: 344580 }, { z: 2.0, dpmo: 308540 },
        { z: 2.1, dpmo: 274250 }, { z: 2.2, dpmo: 241960 }, { z: 2.3, dpmo: 211860 },
        { z: 2.4, dpmo: 184060 }, { z: 2.5, dpmo: 158650 }, { z: 2.6, dpmo: 135670 },
        { z: 2.7, dpmo: 115070 }, { z: 2.8, dpmo: 96800 }, { z: 2.9, dpmo: 80760 },
        { z: 3.0, dpmo: 66810 }, { z: 3.1, dpmo: 54790 }, { z: 3.2, dpmo: 44570 },
        { z: 3.3, dpmo: 35930 }, { z: 3.4, dpmo: 28720 }, { z: 3.5, dpmo: 22750 },
        { z: 3.6, dpmo: 17870 }, { z: 3.7, dpmo: 13900 }, { z: 3.8, dpmo: 10720 },
        { z: 3.9, dpmo: 8200 }, { z: 4.0, dpmo: 6210 }, { z: 4.1, dpmo: 4670 },
        { z: 4.2, dpmo: 3480 }, { z: 4.3, dpmo: 2570 }, { z: 4.4, dpmo: 1880 },
        { z: 4.5, dpmo: 1350 }, { z: 4.6, dpmo: 970 }, { z: 4.7, dpmo: 680 },
        { z: 4.8, dpmo: 480 }, { z: 4.9, dpmo: 330 }, { z: 5.0, dpmo: 230 },
        { z: 5.1, dpmo: 159 }, { z: 5.2, dpmo: 108 }, { z: 5.3, dpmo: 72 },
        { z: 5.4, dpmo: 48 }, { z: 5.5, dpmo: 32 }, { z: 5.6, dpmo: 21 },
        { z: 5.7, dpmo: 13 }, { z: 5.8, dpmo: 8.5 }, { z: 5.9, dpmo: 5.5 },
        { z: 6.0, dpmo: 3.4 }
    ];
    
    for (let i = 0; i < sigmaTable.length - 1; i++) {
        if (sigmaLongoPrazo >= sigmaTable[i].z && sigmaLongoPrazo <= sigmaTable[i + 1].z) {
            const lower = sigmaTable[i];
            const upper = sigmaTable[i + 1];
            
            console.log(`📌 Entre z=${lower.z} (${lower.dpmo} ppm) e z=${upper.z} (${upper.dpmo} ppm)`);
            
            const ratio = (sigmaLongoPrazo - lower.z) / (upper.z - lower.z);
            console.log('📐 ratio:', ratio.toFixed(3));
            
            const dpmo = lower.dpmo + ratio * (upper.dpmo - lower.dpmo);
            console.log('🧮 DPMO calculado (bruto):', dpmo);
            
            const dpmoArredondado = Math.round(dpmo);
            console.log('✅ DPMO ARREDONDADO:', dpmoArredondado, 'ppm');
            
            return dpmoArredondado;
        }
    }
    
    if (sigmaLongoPrazo > 6.0) {
        console.log('✅ sigmaLongoPrazo > 6, retornando 3');
        return 3;
    }
    
    console.log('⚠️ Fora da tabela, retornando 500000');
    return 500000;
}

// Função para gerar dados de regressão polinomial (grau 2)
function generatePolynomialFit(xValues, yValues, degree = 2) {
    if (xValues.length < degree + 1) return xValues.map(() => NaN);
    
    // Usando método simplificado: média móvel ponderada para suavização
    // Em produção, usar biblioteca de álgebra linear para regressão polinomial real
    const windowSize = Math.max(3, Math.floor(xValues.length / 5));
    const fittedY = [];
    
    for (let i = 0; i < xValues.length; i++) {
        let sum = 0;
        let count = 0;
        for (let j = Math.max(0, i - windowSize); j < Math.min(xValues.length, i + windowSize + 1); j++) {
            const weight = 1 / (Math.abs(i - j) + 1);
            sum += yValues[j] * weight;
            count += weight;
        }
        fittedY.push(count > 0 ? sum / count : yValues[i]);
    }
    
    return fittedY;
}

function renderCapabilityHistogram(characteristicId, measurements, lie, lse, stats) {
    const chartContainer = document.getElementById(`${characteristicId}_chart`);
    if (!chartContainer) return;
    
    // Destruir gráfico anterior se existir
    if (capabilityCharts[characteristicId]) {
        capabilityCharts[characteristicId].destroy();
    }
    
    if (measurements.length < 10) {
        chartContainer.innerHTML = '<p style="text-align:center; padding:20px;">Insira mais medições para gerar o histograma.</p>';
        return;
    }
    
    // Limpar o container e criar um novo canvas
    chartContainer.innerHTML = '<canvas></canvas>';
    const canvas = chartContainer.querySelector('canvas');
    
    // Criar bins para o histograma (método Freedman-Diaconis simplificado)
    const numBins = Math.min(20, Math.floor(Math.sqrt(measurements.length)) + 5);
    const minMeas = Math.min(...measurements);
    const maxMeas = Math.max(...measurements);
    const binWidth = (maxMeas - minMeas) / numBins;
    
    const bins = Array(numBins).fill(0);
    const binEdges = [];
    
    for (let i = 0; i <= numBins; i++) {
        binEdges.push(minMeas + i * binWidth);
    }
    
    measurements.forEach(value => {
        for (let i = 0; i < numBins; i++) {
            if (value >= binEdges[i] && value < binEdges[i + 1]) {
                bins[i]++;
                break;
            }
        }
        if (value === maxMeas) bins[numBins - 1]++;
    });
    
    // Gerar linha de tendência polinomial (média dos valores por bin)
    const binCenters = binEdges.slice(0, -1).map((edge, i) => edge + binWidth / 2);
    
    // Para a linha de tendência, usamos a média das medições em cada bin
    const binValues = [];
    for (let i = 0; i < numBins; i++) {
        const binMin = binEdges[i];
        const binMax = binEdges[i + 1];
        const valuesInBin = measurements.filter(m => m >= binMin && m < binMax);
        const avg = valuesInBin.length > 0 ? valuesInBin.reduce((a, b) => a + b, 0) / valuesInBin.length : binCenters[i];
        binValues.push(avg);
    }
    
    // Suavizar a tendência
    const trendY = generatePolynomialFit(binCenters, binValues, 2);
    
    capabilityCharts[characteristicId] = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: binCenters.map(v => v.toFixed(2)),
            datasets: [
                {
                    label: 'Frequência',
                    data: bins,
                    backgroundColor: 'rgba(33, 150, 243, 0.6)',
                    borderColor: 'rgba(33, 150, 243, 1)',
                    borderWidth: 1,
                    yAxisID: 'y'
                },
                {
                    label: 'Tendência Polinomial (Grau 2)',
                    data: trendY.map((y, i) => ({ x: binCenters[i], y: bins[i] * (y / binValues[i]) || 0 })),
                    type: 'line',
                    fill: false,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 3,
                    pointRadius: 0,
                    borderDash: [5, 5],
                    tension: 0.4,
                    yAxisID: 'y',
                    order: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Frequência'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Valores das Medições'
                    },
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: `Histograma das Medições (μ=${stats.mean.toFixed(3)}, σ=${stats.stdDev.toFixed(3)}) - Linha de Tendência`,
                    font: { size: 14 }
                },
                legend: {
                    position: 'top',
                    labels: { usePointStyle: true, boxWidth: 6 }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if (context.dataset.label.includes('Frequência')) {
                                return `Frequência: ${context.raw}`;
                            } else {
                                return `Tendência: ${context.raw.y.toFixed(1)}`;
                            }
                        }
                    }
                },
                annotation: {
                    annotations: {
                        lineLIE: {
                            type: 'line',
                            xMin: lie,
                            xMax: lie,
                            borderColor: '#f44336',
                            borderWidth: 3,
                            label: {
                                content: `LIE: ${lie.toFixed(3)}`,
                                enabled: true,
                                position: 'start',
                                backgroundColor: 'rgba(244,67,54,0.9)',
                                color: 'white',
                                font: { weight: 'bold', size: 10 }
                            }
                        },
                        lineLSE: {
                            type: 'line',
                            xMin: lse,
                            xMax: lse,
                            borderColor: '#f44336',
                            borderWidth: 3,
                            label: {
                                content: `LSE: ${lse.toFixed(3)}`,
                                enabled: true,
                                position: 'end',
                                backgroundColor: 'rgba(244,67,54,0.9)',
                                color: 'white',
                                font: { weight: 'bold', size: 10 }
                            }
                        },
                        lineMean: {
                            type: 'line',
                            xMin: stats.mean,
                            xMax: stats.mean,
                            borderColor: '#2196f3',
                            borderWidth: 3,
                            borderDash: [6, 6],
                            label: {
                                content: `Média: ${stats.mean.toFixed(3)}`,
                                enabled: true,
                                position: 'center',
                                backgroundColor: 'rgba(33,150,243,0.9)',
                                color: 'white',
                                font: { weight: 'bold', size: 10 }
                            }
                        }
                    }
                }
            }
        }
    });
}

function saveCapabilityData(project) {
    const container = document.getElementById('capabilityCharacteristics');
    if (!container) return {};
    
    const studyDate = document.getElementById('capabilityStudyDate')?.value || new Date().toISOString().split('T')[0];
    
    const characteristics = [];
    const charCards = container.querySelectorAll('.characteristic-card');
    
    charCards.forEach((card, index) => {
        const nameInput = card.querySelector('.characteristic-name');
        const typeSelect = card.querySelector('.characteristic-type');
        const lieInput = card.querySelector('.lie');
        const lseInput = card.querySelector('.lse');
        const targetInput = card.querySelector('.target');
        
        const measurements = getAllMeasurementsFromCharacteristic(card);
        
        const lie = parseFloat(lieInput?.value);
        const lse = parseFloat(lseInput?.value);
        const target = parseFloat(targetInput?.value);
        
        let stats = {};
        if (measurements.length >= 2 && !isNaN(lie) && !isNaN(lse)) {
            stats = calculateCapabilityStats(measurements, lie, lse) || {};
        }
        
        // Gerar um ID único baseado no timestamp + índice
        const uniqueId = `char_${Date.now()}_${index}`;
        card.id = uniqueId;
        
        characteristics.push({
            id: uniqueId, // Salvar o ID gerado
            name: nameInput?.value || `Característica ${index + 1}`,
            type: typeSelect?.value || 'cc',
            lie: lieInput?.value ? parseFloat(lieInput.value) : null,
            lse: lseInput?.value ? parseFloat(lseInput.value) : null,
            target: targetInput?.value ? parseFloat(targetInput.value) : null,
            measurements: measurements,
            stats: stats,
            sampleSize: measurements.length,
            studyDate: studyDate,
            lastUpdated: new Date().toISOString()
        });
    });
    
    return {
        characteristics: characteristics,
        studyDate: studyDate,
        lastUpdated: new Date().toISOString(),
        projectId: project.id,
        totalCharacteristics: characteristics.length,
        capableCharacteristics: characteristics.filter(c => c.stats?.cpk >= 1.33).length
    };
}

function loadCapabilityData(project) {
    const container = document.getElementById('capabilityCharacteristics');
    if (!container) return;
    
    container.innerHTML = '';
    
    const dateInput = document.getElementById('capabilityStudyDate');
    if (project.capability?.studyDate) {
        dateInput.value = project.capability.studyDate;
    } else {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
    
    updateCapabilityProjectInfo();
    
    if (!project.capability || !project.capability.characteristics || project.capability.characteristics.length === 0) {
        addCapabilityCharacteristic();
        return;
    }
    
    project.capability.characteristics.forEach((char, index) => {
        const newId = `char_${Date.now()}_${index}`;
        
        const charDiv = document.createElement('div');
        charDiv.className = 'characteristic-card';
        charDiv.id = newId;
        
        // Gerar 5 amostras com 25 medições
        let measurementsHTML = '';
        for (let amostra = 0; amostra < 5; amostra++) {
            measurementsHTML += '<tr>';
            measurementsHTML += `<td style="background: #e8f5e9; font-weight: bold; text-align: center;">Amostra ${amostra + 1}</td>`;
            for (let med = 0; med < 25; med++) {
                const value = char.measurements && (amostra * 25 + med) < char.measurements.length ? 
                    char.measurements[amostra * 25 + med] : '';
                measurementsHTML += `<td><input type="number" class="measurement" step="0.001" placeholder="M${med+1}" value="${value}"></td>`;
            }
            measurementsHTML += '</tr>';
        }
        
        charDiv.innerHTML = `
            <div class="characteristic-header">
                <div>
                    <input type="text" class="characteristic-name" placeholder="Nome da característica" style="width: 250px; padding: 5px;" value="${char.name || ''}">
                    <select class="characteristic-type" style="margin-left: 10px; padding: 5px;">
                        <option value="cc" ${char.type === 'cc' ? 'selected' : ''}>CC - Característica Crítica</option>
                        <option value="sc" ${char.type === 'sc' ? 'selected' : ''}>SC - Característica Significativa</option>
                    </select>
                </div>
                <button class="remove-characteristic-btn" onclick="removeCapabilityCharacteristic('${newId}')">
                    <i class="fas fa-trash"></i> Remover
                </button>
            </div>
            
            <div class="characteristic-inputs">
                <div class="form-group">
                    <label>LIE (Limite Inferior)</label>
                    <input type="number" class="lie" step="0.001" placeholder="0.00" value="${char.lie || ''}">
                </div>
                <div class="form-group">
                    <label>LSE (Limite Superior)</label>
                    <input type="number" class="lse" step="0.001" placeholder="0.00" value="${char.lse || ''}">
                </div>
                <div class="form-group">
                    <label>Alvo</label>
                    <input type="number" class="target" step="0.001" placeholder="0.00" value="${char.target || ''}">
                </div>
                <div class="form-group">
                    <label>Tolerância</label>
                    <input type="text" class="tolerance" readonly placeholder="Calculado" style="background: #f0f0f0;" value="${char.lie && char.lse ? (char.lse - char.lie).toFixed(3) : ''}">
                </div>
            </div>
            
            <div id="${newId}_warning" class="warning-message" style="${char.measurements && char.measurements.length < 125 ? 'display: block;' : 'display: none;'}">
                <i class="fas fa-exclamation-triangle"></i> Aviso: O estudo requer 125 medições (5 amostras x 25 medições).
            </div>
            
            <h4>Medições (5 amostras, cada uma com 25 medições)</h4>
            <div style="overflow-x: auto;">
                <table class="measurement-table">
                    <thead>
                        <tr>
                            <th>Amostra</th>
                            ${Array.from({ length: 25 }, (_, i) => `<th>M${i+1}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${measurementsHTML}
                    </tbody>
                </table>
            </div>
            
            <div class="chart-container-small" id="${newId}_chart"></div>
            
            <div class="memorial-calculo" id="${newId}_memorial">
                <h4>Memorial de Cálculo - Metodologia Seis Sigma</h4>
                <div class="formula">
                    <p><strong>Fórmulas Utilizadas:</strong></p>
                    <p><code>Média (μ) = Σx / n</code> (Soma das medições dividido pelo número de amostras)</p>
                    <p><code>Desvio Padrão (σ) = √[Σ(x - μ)² / (n-1)]</code> (Desvio padrão amostral)</p>
                    <p><code>Cp = (LSE - LIE) / (6σ)</code> (Capacidade potencial do processo)</p>
                    <p><code>Cpu = (LSE - μ) / (3σ)</code> (Capacidade unilateral superior)</p>
                    <p><code>Cpl = (μ - LIE) / (3σ)</code> (Capacidade unilateral inferior)</p>
                    <p><code>Cpk = min(Cpu, Cpl)</code> (Capacidade real do processo)</p>
                    <p><code>Pp = (LSE - LIE) / (6σ<sub>pop</sub>)</code> (Performance potencial, usando σ populacional)</p>
                    <p><code>Ppk = min[(LSE - μ)/(3σ<sub>pop</sub>), (μ - LIE)/(3σ<sub>pop</sub>)]</code> (Performance real)</p>
                    <p><code>Nível Sigma (Z) = min(Cpu, Cpl) × 3</code> (Aproximação do nível sigma)</p>
                    <p><code>DPMO = 1.000.000 × (1 - Φ(Z))</code> (Defeitos por milhão, aproximado por tabela)</p>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Parâmetro</th>
                            <th>Valor</th>
                            <th>Interpretação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Número de Amostras (n)</td><td id="${newId}_n">${char.sampleSize || 0}</td><td>-</td></tr>
                        <tr><td>Média (μ)</td><td id="${newId}_mean2">${char.stats?.mean ? char.stats.mean.toFixed(3) : '-'}</td><td>Centralização do processo</td></tr>
                        <tr><td>Desvio Padrão (σ)</td><td id="${newId}_stddev2">${char.stats?.stdDev ? char.stats.stdDev.toFixed(3) : '-'}</td><td>Variabilidade do processo</td></tr>
                        <tr><td>LIE (Limite Inferior)</td><td id="${newId}_lie2">${char.lie !== null ? char.lie.toFixed(3) : '-'}</td><td>Especificação mínima</td></tr>
                        <tr><td>LSE (Limite Superior)</td><td id="${newId}_lse2">${char.lse !== null ? char.lse.toFixed(3) : '-'}</td><td>Especificação máxima</td></tr>
                        <tr><td>Tolerância (LSE - LIE)</td><td id="${newId}_tol2">${char.lie !== null && char.lse !== null ? (char.lse - char.lie).toFixed(3) : '-'}</td><td>Largura da especificação</td></tr>
                        <tr><td>Cp</td><td id="${newId}_cp2">${char.stats?.cp ? char.stats.cp.toFixed(3) : '-'}</td><td>Capacidade potencial (ignora centralização)</td></tr>
                        <tr><td>Cpk</td><td id="${newId}_cpk2">${char.stats?.cpk ? char.stats.cpk.toFixed(3) : '-'}</td><td>Capacidade real (considera centralização)</td></tr>
                        <tr><td>Pp</td><td id="${newId}_pp2">${char.stats?.pp ? char.stats.pp.toFixed(3) : '-'}</td><td>Performance potencial (σ populacional)</td></tr>
                        <tr><td>Ppk</td><td id="${newId}_ppk2">${char.stats?.ppk ? char.stats.ppk.toFixed(3) : '-'}</td><td>Performance real (σ populacional)</td></tr>
                        <tr><td>Nível Sigma (Z)</td><td id="${newId}_sigma2">${char.stats?.sigmaLevel ? char.stats.sigmaLevel.toFixed(2) : '-'}</td><td>Número de desvios padrão até o limite</td></tr>
                        <tr><td>DPMO Estimado</td><td id="${newId}_dpmo2">${char.stats?.sigmaLevel ? calculateDPMO(char.stats.sigmaLevel).toFixed(0) + ' ppm' : '-'}</td><td>Defeitos por milhão de oportunidades</td></tr>
                    </tbody>
                </table>
                
                <div class="interpretacao-seis-sigma" id="${newId}_interpretacao_detalhada">
                    <strong>Interpretação Detalhada (Seis Sigma):</strong><br>
                    <span id="${newId}_interpretacao_texto">${getCapabilityInterpretation(char.stats)}</span>
                </div>
            </div>
            
            <div class="capability-results">
                <h4>Resultados da Análise (Metodologia Seis Sigma)</h4>
                <div class="results-grid">
                    <div class="result-item">
                        <div class="result-label">Média (μ)</div>
                        <div class="result-value" id="${newId}_mean">${char.stats?.mean ? char.stats.mean.toFixed(3) : '-'}</div>
                    </div>
                    <div class="result-item">
                        <div class="result-label">Desvio Padrão (σ)</div>
                        <div class="result-value" id="${newId}_stddev">${char.stats?.stdDev ? char.stats.stdDev.toFixed(3) : '-'}</div>
                    </div>
                    <div class="result-item">
                        <div class="result-label">Cp</div>
                        <div class="result-value" id="${newId}_cp">${char.stats?.cp ? char.stats.cp.toFixed(3) : '-'}</div>
                    </div>
                    <div class="result-item">
                        <div class="result-label">Cpk</div>
                        <div class="result-value" id="${newId}_cpk">${char.stats?.cpk ? char.stats.cpk.toFixed(3) : '-'}</div>
                    </div>
                    <div class="result-item">
                        <div class="result-label">Pp</div>
                        <div class="result-value" id="${newId}_pp">${char.stats?.pp ? char.stats.pp.toFixed(3) : '-'}</div>
                    </div>
                    <div class="result-item">
                        <div class="result-label">Ppk</div>
                        <div class="result-value" id="${newId}_ppk">${char.stats?.ppk ? char.stats.ppk.toFixed(3) : '-'}</div>
                    </div>
                </div>
                
                <div class="capability-interpretation" id="${newId}_interpretation">
                    <strong>Interpretação Analítica:</strong> ${getCapabilityInterpretation(char.stats)} (${char.measurements?.length || 0} amostras)
                </div>
            </div>
        `;
        
        container.appendChild(charDiv);
        
        const inputs = charDiv.querySelectorAll('input.lie, input.lse, input.target, input.measurement');
        inputs.forEach(input => {
            input.addEventListener('input', () => updateCapabilityResults(newId));
        });
        
        const lieInput = charDiv.querySelector('.lie');
        const lseInput = charDiv.querySelector('.lse');
        const toleranceInput = charDiv.querySelector('.tolerance');
        
        if (lieInput && lseInput && toleranceInput) {
            [lieInput, lseInput].forEach(input => {
                input.addEventListener('input', () => {
                    const lie = parseFloat(lieInput.value);
                    const lse = parseFloat(lseInput.value);
                    if (!isNaN(lie) && !isNaN(lse)) {
                        toleranceInput.value = (lse - lie).toFixed(3);
                    } else {
                        toleranceInput.value = '';
                    }
                    updateCapabilityResults(newId);
                });
            });
        }
        
        if (char.measurements && char.measurements.length > 0 && char.lie && char.lse) {
            updateCapabilityResults(newId);
        }
    });
}

function getCapabilityInterpretation(stats) {
    if (!stats || !stats.cp || !stats.cpk) {
        return 'Insira os valores de LIE, LSE e medições para calcular a capabilidade.';
    }
    
    const cpk = stats.cpk;
    const cp = stats.cp;
    const sigmaLevel = stats.sigmaLevel || 0;
    
    let interpretation = '';
    
    if (cpk >= 1.33) {
        interpretation = '✅ CAPABILIDADE EXCELENTE (Cpk ≥ 1.33). ';
        interpretation += 'O processo atende consistentemente às especificações. ';
        if (cpk > 1.67) {
            interpretation += 'Capabilidade muito acima do necessário. Considere reduzir o controle ou revisar especificações.';
        } else {
            interpretation += 'Manter o controle estatístico do processo (CEP).';
        }
    } else if (cpk >= 1.0) {
        interpretation = '⚠️ CAPABILIDADE ADEQUADA (1.0 ≤ Cpk < 1.33). ';
        interpretation += 'Processo atende às especificações, mas com margem reduzida. ';
        interpretation += 'Requer monitoramento contínuo. Recomenda-se reduzir a variação.';
    } else if (cpk >= 0.67) {
        interpretation = '🔻 CAPABILIDADE INSUFICIENTE (0.67 ≤ Cpk < 1.0). ';
        interpretation += 'Processo não atende consistentemente às especificações. ';
        interpretation += 'Ação corretiva necessária: analisar causas da variação e implementar melhorias.';
    } else {
        interpretation = '❌ PROCESSO INCAPAZ (Cpk < 0.67). ';
        interpretation += 'Processo incapaz de atender às especificações. ';
        interpretation += 'Ação imediata: interromper produção, reprojetar processo ou revisar especificações.';
    }
    
    // Adicionar análise de centralização
    if (cp > cpk * 1.2) {
        interpretation += ' Processo descentralizado (Cpk < Cp). Ajuste a média para o valor alvo.';
    }
    
    // Adicionar nível Sigma
    const ppm = calculateDPMO(sigmaLevel).toFixed(0);
    interpretation += ` Nível Sigma: ${sigmaLevel.toFixed(2)} (${ppm} ppm).`;
    
    return interpretation;
}

function showCapabilityModal() {
    const projectId = currentTimelineProjectId;
    if (!projectId) {
        alert('Nenhum projeto selecionado.');
        return;
    }
    
    const project = projects.find(p => p.id === projectId);
    if (!project) return;
    
    const modal = document.getElementById('capabilityModal');
    const content = document.getElementById('capabilityModalContent');
    
    let html = `
        <div class="capability-section" style="margin-top: 0;">
            <h3><i class="fas fa-chart-line"></i> Estudo de Capabilidade - ${project.projectName}</h3>
            <p style="color: #666; margin-bottom: 15px;">
                Resultados consolidados do estudo de capabilidade (Metodologia Seis Sigma).
            </p>
            
            <div class="project-info-capability">
                <div class="project-info-item-capability">
                    <div class="project-info-label-capability">Cliente</div>
                    <div class="project-info-value-capability">${project.cliente || '-'}</div>
                </div>
                <div class="project-info-item-capability">
                    <div class="project-info-label-capability">Projeto</div>
                    <div class="project-info-value-capability">${project.projectName || '-'}</div>
                </div>
                <div class="project-info-item-capability">
                    <div class="project-info-label-capability">Modelo</div>
                    <div class="project-info-value-capability">${project.modelo || '-'}</div>
                </div>
                <div class="project-info-item-capability">
                    <div class="project-info-label-capability">Código</div>
                    <div class="project-info-value-capability">${project.codigo || '-'}</div>
                </div>
                <div class="project-info-item-capability">
                    <div class="project-info-label-capability">Processo</div>
                    <div class="project-info-value-capability">${project.processo || '-'}</div>
                </div>
                <div class="project-info-item-capability">
                    <div class="project-info-label-capability">Segmento</div>
                    <div class="project-info-value-capability">${project.segmento || '-'}</div>
                </div>
                <div class="project-info-item-capability">
                    <div class="project-info-label-capability">Líder</div>
                    <div class="project-info-value-capability">${project.projectLeader || '-'}</div>
                </div>
            </div>
    `;
    
    if (!project.capability || !project.capability.characteristics || project.capability.characteristics.length === 0) {
        html += '<p style="text-align: center; padding: 20px;">Nenhum estudo de capabilidade realizado para este projeto.</p>';
    } else {
        html += `<p><strong>Data do Estudo:</strong> ${project.capability.studyDate ? formatDateBR(project.capability.studyDate) : 'Não informada'}</p>`;
        
        project.capability.characteristics.forEach((char, index) => {
            const cpClass = char.stats?.cp >= 1.33 ? 'good' : (char.stats?.cp >= 1.0 ? 'warning' : 'bad');
            const cpkClass = char.stats?.cpk >= 1.33 ? 'good' : (char.stats?.cpk >= 1.0 ? 'warning' : 'bad');
            const sigmaLevel = char.stats?.sigmaLevel || 0;
            const ppm = calculateDPMO(sigmaLevel).toFixed(0);
            
            html += `
                <div class="characteristic-card" style="margin-bottom: 20px;">
                    <div class="characteristic-header">
                        <div>
                            <span class="characteristic-name">${char.name || `Característica ${index + 1}`}</span>
                            <span class="characteristic-symbol ${char.type === 'cc' ? 'cc' : 'sc'}">
                                ${char.type === 'cc' ? 'CC' : 'SC'}
                            </span>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0;">
                        <div><strong>LIE:</strong> ${char.lie !== null ? char.lie.toFixed(3) : '-'}</div>
                        <div><strong>LSE:</strong> ${char.lse !== null ? char.lse.toFixed(3) : '-'}</div>
                        <div><strong>Alvo:</strong> ${char.target !== null ? char.target.toFixed(3) : '-'}</div>
                        <div><strong>Tolerância:</strong> ${char.lie !== null && char.lse !== null ? (char.lse - char.lie).toFixed(3) : '-'}</div>
                        <div><strong>Amostras:</strong> ${char.measurements?.length || 0} (125 esperadas)</div>
                        <div><strong>Nível Sigma:</strong> ${sigmaLevel.toFixed(2)}</div>
                        <div><strong>DPMO:</strong> ${ppm} ppm</div>
                    </div>
                    
                    <div style="margin: 15px 0; max-height: 100px; overflow-y: auto; font-size: 0.8rem;">
                        <strong>Medições (primeiras 20):</strong> 
                        ${char.measurements?.slice(0, 20).map(m => m.toFixed(3)).join(' | ') || '-'}
                        ${char.measurements?.length > 20 ? ' ...' : ''}
                    </div>
                    
                    <div class="capability-results" style="margin: 0;">
                        <div class="results-grid">
                            <div class="result-item">
                                <div class="result-label">Média (μ)</div>
                                <div class="result-value">${char.stats?.mean ? char.stats.mean.toFixed(3) : '-'}</div>
                            </div>
                            <div class="result-item">
                                <div class="result-label">Desvio Padrão (σ)</div>
                                <div class="result-value">${char.stats?.stdDev ? char.stats.stdDev.toFixed(3) : '-'}</div>
                            </div>
                            <div class="result-item">
                                <div class="result-label">Cp</div>
                                <div class="result-value ${cpClass}">${char.stats?.cp ? char.stats.cp.toFixed(3) : '-'}</div>
                            </div>
                            <div class="result-item">
                                <div class="result-label">Cpk</div>
                                <div class="result-value ${cpkClass}">${char.stats?.cpk ? char.stats.cpk.toFixed(3) : '-'}</div>
                            </div>
                            <div class="result-item">
                                <div class="result-label">Pp</div>
                                <div class="result-value">${char.stats?.pp ? char.stats.pp.toFixed(3) : '-'}</div>
                            </div>
                            <div class="result-item">
                                <div class="result-label">Ppk</div>
                                <div class="result-value">${char.stats?.ppk ? char.stats.ppk.toFixed(3) : '-'}</div>
                            </div>
                        </div>
                        
                        <div class="capability-interpretation">
                            <strong>Interpretação Analítica:</strong> ${getCapabilityInterpretation(char.stats)}
                        </div>
                    </div>
                </div>
            `;
        });
        
        const totalChars = project.capability.characteristics.length;
        const capableChars = project.capability.characteristics.filter(c => c.stats?.cpk >= 1.33).length;
        const avgCpk = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.cpk || 0), 0) / totalChars;
        const totalSamples = project.capability.characteristics.reduce((sum, c) => sum + (c.measurements?.length || 0), 0);
        const avgSigma = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.sigmaLevel || 0), 0) / totalChars;
        const avgPpm = calculateDPMO(avgSigma).toFixed(0);
        
        html += `
            <div class="capability-results" style="margin-top: 20px;">
                <h4>Resumo Geral do Estudo</h4>
                <div class="results-grid">
                    <div class="result-item">
                        <div class="result-label">Total de Características</div>
                        <div class="result-value">${totalChars}</div>
                    </div>
                    <div class="result-item">
                        <div class="result-label">Capazes (Cpk≥1.33)</div>
                        <div class="result-value ${capableChars === totalChars ? 'good' : (capableChars > 0 ? 'warning' : 'bad')}">
                            ${capableChars} (${((capableChars / totalChars) * 100).toFixed(1)}%)
                        </div>
                    </div>
                    <div class="result-item">
                        <div class="result-label">Cpk Médio</div>
                        <div class="result-value ${avgCpk >= 1.33 ? 'good' : avgCpk >= 1.0 ? 'warning' : 'bad'}">${avgCpk.toFixed(3)}</div>
                    </div>
                    <div class="result-item">
                        <div class="result-label">Nível Sigma Médio</div>
                        <div class="result-value">${avgSigma.toFixed(2)} (${avgPpm} ppm)</div>
                    </div>
                    <div class="result-item">
                        <div class="result-label">Total de Amostras</div>
                        <div class="result-value">${totalSamples}</div>
                    </div>
                </div>
                
                <div class="capability-interpretation">
                    <strong>Interpretação Geral:</strong> 
                    ${capableChars === totalChars ? '✅ Todas as características são capazes.' : 
                      capableChars > totalChars/2 ? '⚠️ Maioria das características é capaz, mas algumas necessitam atenção.' : 
                      '❌ Maioria das características é incapaz. Revisão do processo necessária.'}
                    ${avgCpk < 1.0 ? ' Ação corretiva obrigatória para melhorar a capabilidade do processo.' : ''}
                    ${avgCpk >= 1.33 ? ' Processo atende aos requisitos Seis Sigma.' : ''}
                </div>
            </div>
        `;
    }
    
    html += `
        <div style="margin-top: 20px; text-align: right;">
            <small>Última atualização: ${project.capability?.lastUpdated ? new Date(project.capability.lastUpdated).toLocaleString('pt-BR') : 'Nunca'}</small>
        </div>
    </div>
    `;
    
    content.innerHTML = html;
    modal.style.display = 'block';
}

function showCapabilityForProject(projectId) {
    const project = projects.find(p => p.id === projectId);
    if (!project) return;
    
    currentTimelineProjectId = projectId;
    showCapabilityModal();
}

async function exportCapabilityToPDF() {
    const projectId = currentEditingProjectId || currentTimelineProjectId;
    if (!projectId) {
        alert('Nenhum projeto selecionado.');
        return;
    }
    
    const project = projects.find(p => p.id === projectId);
    if (!project) return;
    
    if (!project.capability || !project.capability.characteristics || project.capability.characteristics.length === 0) {
        alert('Não há dados de capabilidade para este projeto.');
        return;
    }
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 15;
    const contentWidth = pageWidth - 2 * margin;
    let yPos = margin;
    
    function checkPageHeight(neededHeight) {
        if (yPos + neededHeight > pageHeight - margin) {
            doc.addPage();
            yPos = margin;
            return true;
        }
        return false;
    }
    
    if (companyLogo) {
        try {
            const img = await loadImage(companyLogo);
            
            const maxWidth = 40;
            const maxHeight = 15;
            
            let logoWidth = img.width;
            let logoHeight = img.height;
            
            const ratio = logoWidth / logoHeight;
            
            if (logoWidth > maxWidth) {
                logoWidth = maxWidth;
                logoHeight = logoWidth / ratio;
            }
            
            if (logoHeight > maxHeight) {
                logoHeight = maxHeight;
                logoWidth = logoHeight * ratio;
            }
            
            doc.addImage(companyLogo, 'PNG', margin, yPos, logoWidth, logoHeight);
            yPos += logoHeight + 5;
        } catch (e) {
            console.warn('Não foi possível adicionar o logotipo ao PDF:', e);
            yPos += 5;
        }
    } else {
        yPos += 5;
    }
    
    doc.setFontSize(20);
    doc.setTextColor(33, 150, 243);
    doc.text("ESTUDO DE CAPABILIDADE DO PROCESSO", pageWidth / 2, yPos, { align: 'center' });
    yPos += 15;
    
    doc.setFontSize(12);
    doc.setTextColor(0, 0, 0);
    doc.text(`Projeto: ${project.projectName} (ID: ${project.id})`, margin, yPos);
    yPos += 7;
    doc.text(`Cliente: ${project.cliente || '-'} | Líder: ${project.projectLeader || '-'} | Data: ${project.capability.studyDate ? formatDateBR(project.capability.studyDate) : '-'}`, margin, yPos);
    yPos += 7;
    doc.text(`Modelo: ${project.modelo || '-'} | Código: ${project.codigo || '-'} | Processo: ${project.processo || '-'} | Segmento: ${project.segmento || '-'}`, margin, yPos);
    yPos += 10;
    
    doc.setDrawColor(33, 150, 243);
    doc.setLineWidth(1);
    doc.line(margin, yPos, pageWidth - margin, yPos);
    yPos += 10;
    
    project.capability.characteristics.forEach((char, index) => {
        if (!char.stats) return;
        
        checkPageHeight(70);
        
        doc.setFillColor(250, 250, 250);
        doc.rect(margin, yPos, contentWidth, 55, 'F');
        doc.setDrawColor(200, 200, 200);
        doc.rect(margin, yPos, contentWidth, 55);
        
        doc.setFontSize(14);
        doc.setTextColor(33, 33, 33);
        doc.setFont(undefined, 'bold');
        doc.text(`${index + 1}. ${char.name || `Característica ${index + 1}`}`, margin + 5, yPos + 8);
        
        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        doc.text(`Tipo: ${char.type === 'cc' ? 'CC - Característica Crítica' : 'SC - Característica Significativa'}`, margin + 5, yPos + 15);
        doc.text(`LIE: ${char.lie?.toFixed(3) || '-'} | LSE: ${char.lse?.toFixed(3) || '-'} | Alvo: ${char.target?.toFixed(3) || '-'}`, margin + 5, yPos + 22);
        doc.text(`Média: ${char.stats.mean?.toFixed(3) || '-'} | Desvio: ${char.stats.stdDev?.toFixed(3) || '-'} | Nível Sigma: ${char.stats.sigmaLevel?.toFixed(2) || '-'}`, margin + 5, yPos + 29);
        
        if (char.measurements && char.measurements.length > 0) {
            doc.setFontSize(8);
            doc.setTextColor(100, 100, 100);
            
            const measurementsText = char.measurements.map(m => m.toFixed(3)).join(' | ');
            const lines = doc.splitTextToSize(measurementsText, contentWidth - 10);
            
            let yOffset = yPos + 36;
            const maxLines = 3;
            
            for (let i = 0; i < Math.min(lines.length, maxLines); i++) {
                doc.text(lines[i], margin + 5, yOffset);
                yOffset += 4;
            }
            
            if (lines.length > maxLines) {
                doc.text(`... e mais ${lines.length - maxLines} medições`, margin + 5, yOffset);
            }
        }
        
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        
        if (char.stats.cp) {
            if (char.stats.cp >= 1.33) doc.setTextColor(76, 175, 80);
            else if (char.stats.cp >= 1.0) doc.setTextColor(255, 152, 0);
            else doc.setTextColor(244, 67, 54);
        }
        doc.text(`Cp: ${char.stats.cp?.toFixed(3) || '-'}`, margin + contentWidth - 70, yPos + 15);
        
        if (char.stats.cpk) {
            if (char.stats.cpk >= 1.33) doc.setTextColor(76, 175, 80);
            else if (char.stats.cpk >= 1.0) doc.setTextColor(255, 152, 0);
            else doc.setTextColor(244, 67, 54);
        }
        doc.text(`Cpk: ${char.stats.cpk?.toFixed(3) || '-'}`, margin + contentWidth - 70, yPos + 25);
        
        doc.setTextColor(33, 33, 33);
        
        doc.setFontSize(9);
        doc.setFont(undefined, 'italic');
        const interpretation = getCapabilityInterpretation(char.stats);
        const shortInterpretation = interpretation.length > 80 ? interpretation.substring(0, 80) + '...' : interpretation;
        doc.text(shortInterpretation, margin + 5, yPos + 45);
        
        yPos += 60;
    });
    
    checkPageHeight(50);
    
    const totalChars = project.capability.characteristics.length;
    const capableChars = project.capability.characteristics.filter(c => c.stats?.cpk >= 1.33).length;
    const avgCpk = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.cpk || 0), 0) / totalChars;
    const totalSamples = project.capability.characteristics.reduce((sum, c) => sum + (c.measurements?.length || 0), 0);
    const avgSigma = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.sigmaLevel || 0), 0) / totalChars;
    const avgPpm = calculateDPMO(avgSigma).toFixed(0);
    
    doc.setFillColor(240, 248, 240);
    doc.roundedRect(margin, yPos, contentWidth, 40, 3, 3, 'F');
    
    doc.setFontSize(12);
    doc.setTextColor(33, 33, 33);
    doc.setFont(undefined, 'bold');
    doc.text("RESUMO GERAL DA CAPABILIDADE", margin + 10, yPos + 8);
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.text(`Total de Características: ${totalChars}`, margin + 10, yPos + 15);
    doc.text(`Capazes (Cpk ≥ 1.33): ${capableChars} (${((capableChars / totalChars) * 100).toFixed(1)}%)`, margin + 100, yPos + 15);
    doc.text(`Cpk Médio: ${avgCpk.toFixed(3)} | Total de Amostras: ${totalSamples}`, margin + 10, yPos + 22);
    doc.text(`Nível Sigma Médio: ${avgSigma.toFixed(2)} (${avgPpm} ppm)`, margin + 10, yPos + 29);
    
    yPos += 45;
    
    doc.setFillColor(255, 243, 224);
    doc.roundedRect(margin, yPos, contentWidth, 25, 3, 3, 'F');
    
    let overallInterpretation = '';
    const capabilityRate = capableChars / totalChars;
    
    if (capabilityRate >= 0.9) {
        overallInterpretation = 'Excelente: Mais de 90% das características são capazes.';
    } else if (capabilityRate >= 0.7) {
        overallInterpretation = 'Bom: 70-90% das características são capazes.';
    } else if (capabilityRate >= 0.5) {
        overallInterpretation = 'Regular: 50-70% das características são capazes.';
    } else {
        overallInterpretation = 'Crítico: Menos de 50% das características são capazes.';
    }
    
    overallInterpretation += ` Nível Sigma geral: ${avgSigma.toFixed(2)} (${avgPpm} ppm).`;
    
    if (avgCpk < 1.0) {
        overallInterpretation += ' Ação corretiva obrigatória.';
    }
    
    doc.setFontSize(10);
    doc.setTextColor(33, 33, 33);
    doc.setFont(undefined, 'bold');
    doc.text("INTERPRETAÇÃO GERAL:", margin + 10, yPos + 8);
    doc.setFont(undefined, 'normal');
    doc.text(overallInterpretation, margin + 10, yPos + 15);
    
    yPos += 30;
    
    const footerY = pageHeight - 10;
    doc.setFontSize(8);
    doc.setTextColor(100, 100, 100);
    doc.text(`Gerado em: ${new Date().toLocaleDateString('pt-BR')} ${new Date().toLocaleTimeString('pt-BR')}`, margin, footerY);
    doc.text(`Página ${doc.internal.getNumberOfPages()}`, pageWidth - margin, footerY, { align: 'right' });
    
    const fileName = `capabilidade_${project.projectName.replace(/[^a-z0-9]/gi, '_')}_${project.id}.pdf`;
    doc.save(fileName);
}

// ==============================================

