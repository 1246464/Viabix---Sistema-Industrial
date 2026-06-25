let anviSuggestionTimer = null;
let anviSuggestionMap = new Map();
let efficiencyChart = null;
let riskReturnChart = null;

const demoPortfolio = [
    {
        id: 'DEMO-ANVI-001',
        numero: 'ANVI-DEMO-001',
        cliente: 'Metal Norte',
        projeto: 'Linha automatizada',
        score: 87,
        margem: 23.4,
        payback: 18,
        eficiencia: 82,
        projetos: 1,
        risco: 24
    },
    {
        id: 'DEMO-ANVI-002',
        numero: 'ANVI-DEMO-002',
        cliente: 'Química Alfa',
        projeto: 'Redução de setup',
        score: 74,
        margem: 16.8,
        payback: 24,
        eficiencia: 71,
        projetos: 1,
        risco: 38
    },
    {
        id: 'DEMO-ANVI-003',
        numero: 'ANVI-DEMO-003',
        cliente: 'Manufatura Beta',
        projeto: 'Expansão de capacidade',
        score: 58,
        margem: 9.6,
        payback: 31,
        eficiencia: 62,
        projetos: 0,
        risco: 61
    },
    {
        id: 'DEMO-ANVI-004',
        numero: 'ANVI-DEMO-004',
        cliente: 'Consultoria Delta',
        projeto: 'Novo centro operacional',
        score: 81,
        margem: 21.2,
        payback: 20,
        eficiencia: 78,
        projetos: 1,
        risco: 29
    }
];

function formatarData(dataStr) {
    if (!dataStr) return 'N/A';
    const data = new Date(dataStr + 'T00:00:00');
    return data.toLocaleDateString('pt-BR');
}

function obterClasseScore(score) {
    if (score >= 80) return 'score-high';
    if (score >= 60) return 'score-medium';
    return 'score-low';
}

function formatarMoeda(valor) {
    return Number(valor || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

function formatarPercentual(valor) {
    return `${Number(valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}%`;
}

function escapeHtml(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

function formatarPayback(valor) {
    return `${Number(valor || 0).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} meses`;
}

function scoreTone(score) {
    if (score < 60) return 'danger';
    if (score < 78) return 'warn';
    return '';
}

function normalizarPortfolio(lista) {
    const source = Array.isArray(lista) && lista.length ? lista : demoPortfolio;
    return source.slice(0, 6).map((item, index) => {
        const baseScore = item.score ?? (86 - index * 7);
        return {
            id: item.id || item.numero || `ANVI-${index + 1}`,
            numero: item.numero || item.nome || item.id || `ANVI-${index + 1}`,
            cliente: item.cliente || 'Cliente demo',
            projeto: item.projeto || item.produto || 'Projeto em análise',
            score: Math.max(42, Math.min(94, Number(baseScore))),
            margem: Number(item.margem ?? (24 - index * 2.8)),
            payback: Number(item.payback ?? (16 + index * 4)),
            eficiencia: Number(item.eficiencia ?? (82 - index * 4)),
            projetos: Number(item.projetos ?? (index % 3 === 2 ? 0 : 1)),
            risco: Number(item.risco ?? (24 + index * 8))
        };
    });
}

function sugestaoParaANVI(item) {
    if (item.score < 60 || item.margem < 10) {
        return {
            tone: 'danger',
            titulo: 'Revisar antes de aprovar',
            texto: `${item.numero} tem margem baixa ou score inferior ao mínimo. Reavalie custo, preço sugerido e payback.`
        };
    }
    if (item.payback > 24 || item.risco > 45) {
        return {
            tone: 'warning',
            titulo: 'Aprovar com ressalvas',
            texto: `${item.numero} pode seguir, mas precisa reduzir risco ou fasear investimento para melhorar retorno.`
        };
    }
    return {
        tone: '',
        titulo: 'Boa candidata para execução',
        texto: `${item.numero} está acima da média de eficiência e pode ser priorizada para virar projeto.`
    };
}

function renderizarPortfolio(lista) {
    const dados = normalizarPortfolio(lista);
    const total = dados.length;
    const mediaScore = total ? dados.reduce((acc, item) => acc + item.score, 0) / total : 0;
    const mediaEficiencia = total ? dados.reduce((acc, item) => acc + item.eficiencia, 0) / total : 0;
    const projetos = dados.reduce((acc, item) => acc + item.projetos, 0);

    document.getElementById('portfolioTotal').textContent = total.toLocaleString('pt-BR');
    document.getElementById('portfolioScore').textContent = mediaScore.toLocaleString('pt-BR', { maximumFractionDigits: 1 });
    document.getElementById('portfolioEfficiency').textContent = formatarPercentual(mediaEficiencia);
    document.getElementById('portfolioProjects').textContent = projetos.toLocaleString('pt-BR');

    document.getElementById('anviRadarList').innerHTML = dados.slice(0, 4).map(item => `
        <button type="button" class="anvi-radar-item" onclick="selecionarAnviRadar(decodeURIComponent('${encodeURIComponent(item.numero)}'))">
            <span>
                <strong>${escapeHtml(item.numero)}</strong>
                <span>${escapeHtml(item.cliente)} · ${escapeHtml(item.projeto)}</span>
            </span>
            <span class="radar-score ${scoreTone(item.score)}">${item.score}</span>
        </button>
    `).join('');

    document.getElementById('suggestionsGrid').innerHTML = dados.slice(0, 3).map(item => {
        const sugestao = sugestaoParaANVI(item);
        return `
            <div class="suggestion-card ${sugestao.tone}">
                <small>${escapeHtml(item.cliente)}</small>
                <strong>${escapeHtml(sugestao.titulo)}</strong>
                <p>${escapeHtml(sugestao.texto)}</p>
            </div>
        `;
    }).join('');

    renderizarGraficosPortfolio(dados);
}

function selecionarAnviRadar(numero) {
    document.getElementById('anviInput').value = numero;
    if (!numero.startsWith('ANVI-DEMO')) {
        carregarAnalise();
    }
}

function renderizarGraficosPortfolio(dados, selecionada = null) {
    const labels = dados.map(item => item.numero.replace('ANVI-', ''));
    const selectedScore = selecionada?.score ?? dados[0]?.score ?? 0;
    const selectedExec = selecionada?.execucao ?? dados[0]?.eficiencia ?? 0;
    const selectedPrazo = selecionada?.prazo ?? Math.max(35, 100 - (dados[0]?.payback || 24));
    const selectedRisco = selecionada?.riscoScore ?? Math.max(20, 100 - (dados[0]?.risco || 35));

    const efficiencyData = {
        labels: ['Score geral', 'Execução', 'Prazo', 'Risco controlado'],
        datasets: [
            {
                label: 'ANVI selecionada',
                data: [selectedScore, selectedExec, selectedPrazo, selectedRisco],
                backgroundColor: '#0a3d2e'
            },
            {
                label: 'Média do portfólio',
                data: [
                    media(dados, 'score'),
                    media(dados, 'eficiencia'),
                    media(dados.map(item => ({ valor: Math.max(35, 100 - item.payback) })), 'valor'),
                    media(dados.map(item => ({ valor: Math.max(20, 100 - item.risco) })), 'valor')
                ],
                backgroundColor: '#d89b2b'
            }
        ]
    };

    const riskData = {
        datasets: [{
            label: 'ANVIs',
            data: dados.map(item => ({
                x: item.risco,
                y: item.margem,
                r: Math.max(7, item.score / 7),
                numero: item.numero
            })),
            backgroundColor: 'rgba(15, 63, 47, 0.72)',
            borderColor: '#0a3d2e'
        }]
    };

    if (efficiencyChart) efficiencyChart.destroy();
    if (riskReturnChart) riskReturnChart.destroy();

    const efficiencyCtx = document.getElementById('efficiencyChart');
    const riskCtx = document.getElementById('riskReturnChart');

    if (!window.Chart || !efficiencyCtx || !riskCtx) return;

    efficiencyChart = new Chart(efficiencyCtx, {
        type: 'bar',
        data: efficiencyData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { min: 0, max: 100 } },
            plugins: { legend: { position: 'bottom' } }
        }
    });

    riskReturnChart = new Chart(riskCtx, {
        type: 'bubble',
        data: riskData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { title: { display: true, text: 'Risco' }, min: 0, max: 80 },
                y: { title: { display: true, text: 'Margem %' }, min: 0, max: 32 }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => `${ctx.raw.numero}: margem ${formatarPercentual(ctx.raw.y)}, risco ${ctx.raw.x}`
                    }
                }
            }
        }
    });
}

function media(lista, chave) {
    if (!Array.isArray(lista) || !lista.length) return 0;
    return Number((lista.reduce((acc, item) => acc + Number(item[chave] || 0), 0) / lista.length).toFixed(1));
}

function atualizarInteligenciaComAnalise(data) {
    const scores = data.viabilidade?.scores_por_area || {};
    const indicadores = data.indicadores_financeiros || {};
    const atual = {
        id: data.anvi?.id,
        numero: data.anvi?.numero || data.anvi?.id || 'ANVI selecionada',
        cliente: data.anvi?.cliente || 'Cliente',
        projeto: data.anvi?.projeto || 'Projeto',
        score: Number(data.viabilidade?.score_geral || 0),
        margem: Number(indicadores.margem_esperada_pct || 0),
        payback: Number(indicadores.payback_meses || 0),
        eficiencia: Number(scores.execucao || scores.planejamento || 0),
        projetos: data.projeto_vinculado ? 1 : 0,
        risco: Math.max(0, 100 - Number(scores.risco || 0)),
        execucao: Number(scores.execucao || 0),
        prazo: Number(scores.prazo || 0),
        riscoScore: Number(scores.risco || 0)
    };
    const portfolio = [atual, ...demoPortfolio.filter(item => item.numero !== atual.numero)].slice(0, 5);
    renderizarPortfolio(portfolio);
}

function renderizarPrioridades(decisao) {
    const container = document.getElementById('prioridadesContainer');
    const prioridades = Array.isArray(decisao?.prioridades) ? decisao.prioridades : [];
    const incompletos = Array.isArray(decisao?.dados_incompletos) ? decisao.dados_incompletos : [];

    if (!prioridades.length && !incompletos.length) {
        container.innerHTML = '<p class="text-muted mb-0">Nenhum ponto crítico identificado nos critérios atuais.</p>';
        return;
    }

    const itens = prioridades.map(item => `
        <div class="priority-item">
            <span class="priority-badge ${item.prioridade === 'Alta' ? 'alta' : ''}">${escapeHtml(item.prioridade || 'Média')}</span>
            <div>
                <div class="priority-title">${escapeHtml(item.area)} · ${escapeHtml(item.titulo)}</div>
                <p class="priority-action">${escapeHtml(item.acao)}</p>
            </div>
        </div>
    `);

    if (incompletos.length) {
        itens.push(`
            <div class="priority-item">
                <span class="priority-badge">Dados</span>
                <div>
                    <div class="priority-title">Informações incompletas</div>
                    <p class="priority-action">Completar: ${escapeHtml(incompletos.join(', '))}.</p>
                </div>
            </div>
        `);
    }

    container.innerHTML = itens.join('');
}

function renderizarDecisao(data) {
    const decisao = data.decisao || {};
    const viab = data.viabilidade || {};
    const indicadores = data.indicadores_financeiros || {};
    const scoreClass = obterClasseScore(viab.score_geral);
    const decisionMain = document.getElementById('decisionMain');
    const tom = decisao.tom || 'success';
    const statusClass = viab.status === 'VIÁVEL'
        ? 'status-viavel'
        : (viab.status === 'PARCIAL' || viab.status === 'VIÁVEL COM RESSALVAS' ? 'status-atencao' : 'status-inviavel');

    decisionMain.className = `decision-main ${tom === 'danger' ? 'danger' : (tom === 'warning' ? 'warning' : '')}`;
    document.getElementById('decisaoStatus').textContent = decisao.status || viab.status || '-';
    document.getElementById('decisaoResumo').textContent = decisao.resumo || viab.recomendacao || '';
    document.getElementById('decisaoProximaAcao').textContent = decisao.proxima_acao || viab.recomendacao || '';
    document.getElementById('scoreBadge').className = `score-badge compact ${scoreClass}`;
    document.getElementById('scoreBadge').textContent = viab.score_geral ?? '-';
    document.getElementById('viabilidadeStatus').innerHTML = `<strong class="${statusClass}">${escapeHtml(viab.status || '-')}</strong>`;
    document.getElementById('metricMargem').textContent = formatarPercentual(indicadores.margem_esperada_pct);
    document.getElementById('metricRoi').textContent = formatarPercentual(indicadores.roi_esperado_pct);
    document.getElementById('metricPayback').textContent = formatarPayback(indicadores.payback_meses);
    renderizarProjetoVinculado(data.projeto_vinculado);
    renderizarPrioridades(decisao);
}

function renderizarProjetoVinculado(projeto) {
    const panel = document.getElementById('linkedProjectPanel');
    if (!panel) return;

    if (!projeto) {
        panel.classList.remove('visible');
        return;
    }

    const progresso = Math.max(0, Math.min(100, Number(projeto.progresso || 0)));
    panel.classList.add('visible');
    document.getElementById('linkedProjectName').textContent = projeto.nome || `Projeto #${projeto.id}`;
    document.getElementById('linkedProjectStatus').textContent = `Status: ${projeto.status || '-'}`;
    document.getElementById('linkedProjectLeader').textContent = `Líder: ${projeto.lider || '-'}`;
    document.getElementById('linkedProjectTasks').textContent = `Tarefas: ${Number(projeto.tarefas_concluidas || 0)}/${Number(projeto.tarefas_total || 0)}`;
    document.getElementById('linkedProjectLate').textContent = `Atrasos: ${Number(projeto.tarefas_atrasadas || 0)}`;
    document.getElementById('linkedProjectProgressBar').style.width = `${progresso}%`;
    document.getElementById('linkedProjectProgressText').textContent = `${progresso.toLocaleString('pt-BR', { maximumFractionDigits: 1 })}% concluído · ${formatarPercentual(projeto.pontualidade_pct)} de pontualidade`;

    const link = document.getElementById('linkedProjectUrl');
    if (projeto.url) {
        link.href = projeto.url;
        link.style.display = 'inline-flex';
    } else {
        link.style.display = 'none';
    }
}

function renderizarSugestoesANVI(lista) {
    const datalist = document.getElementById('anviSuggestions');
    anviSuggestionMap = new Map();
    datalist.innerHTML = '';

    (Array.isArray(lista) ? lista : []).forEach(item => {
        const value = item.numero || item.id;
        if (!value) return;

        const option = document.createElement('option');
        const detalhes = [item.revisao ? `Rev. ${item.revisao}` : '', item.cliente, item.projeto]
            .filter(Boolean)
            .join(' · ');
        option.value = value;
        option.label = detalhes ? `${item.nome} · ${detalhes}` : item.nome;
        datalist.appendChild(option);
        anviSuggestionMap.set(value, item);
    });
}

function buscarSugestoesANVI(termo = '') {
    fetch(`api/anvis_sugestoes.php?q=${encodeURIComponent(termo)}&limit=15`)
        .then(response => response.ok ? response.json() : null)
        .then(data => {
            if (data?.success) {
                const lista = data.data || [];
                renderizarSugestoesANVI(lista);
                if (!termo) {
                    renderizarPortfolio(lista.length ? lista : demoPortfolio);
                }
            }
        })
        .catch(() => {});
}

function renderizarIndicadoresFinanceiros(indicadores, alertas) {
    const container = document.getElementById('indicadoresFinanceiros');
    const alertasContainer = document.getElementById('alertasFinanceiros');
    const itens = [
        ['Margem esperada', formatarPercentual(indicadores?.margem_esperada_pct)],
        ['Custo total', formatarMoeda(indicadores?.custo_total)],
        ['Preço sugerido', formatarMoeda(indicadores?.preco_sugerido)],
        ['Payback', formatarPayback(indicadores?.payback_meses)],
        ['ROI', formatarPercentual(indicadores?.roi_esperado_pct)],
        ['Desvio estimado x realizado', formatarPercentual(indicadores?.desvio_estimado_realizado_pct)]
    ];

    container.innerHTML = itens.map(([label, valor]) => `
        <div class="financial-metric">
            <small>${label}</small>
            <strong>${valor}</strong>
        </div>
    `).join('');

    const listaAlertas = Array.isArray(alertas) ? alertas : [];
    alertasContainer.innerHTML = listaAlertas.length
        ? listaAlertas.map(alerta => `
            <div class="financial-alert ${alerta.severidade === 'critico' ? 'critico' : ''}">
                <strong>${alerta.severidade === 'critico' ? 'Crítico' : 'Atenção'}:</strong>
                ${escapeHtml(alerta.mensagem)}
            </div>
        `).join('')
        : '<div class="financial-alert"><strong>OK:</strong> sem alertas financeiros relevantes para os critérios atuais.</div>';
}

function renderizarComparativoRevisoes(comparativo) {
    const container = document.getElementById('comparativoRevisoes');

    if (!comparativo || !comparativo.variacoes) {
        container.innerHTML = '<p class="text-muted mb-0">Nenhuma revisão anterior encontrada para comparação.</p>';
        return;
    }

    const variacoes = comparativo.variacoes;
    const linhas = [
        ['Margem', formatarPercentual(variacoes.margem_esperada_pct), variacoes.margem_esperada_pct],
        ['Custo total', formatarMoeda(variacoes.custo_total), variacoes.custo_total],
        ['Preço sugerido', formatarMoeda(variacoes.preco_sugerido), variacoes.preco_sugerido],
        ['Payback', formatarPayback(variacoes.payback_meses), variacoes.payback_meses],
        ['ROI', formatarPercentual(variacoes.roi_esperado_pct), variacoes.roi_esperado_pct]
    ];

    const classeTendencia = (label, valor) => {
        const numero = Number(valor || 0);
        if (numero === 0) return 'trend-neutral';
        if (label === 'Custo total' || label === 'Payback') {
            return numero < 0 ? 'trend-up' : 'trend-down';
        }
        return numero > 0 ? 'trend-up' : 'trend-down';
    };

    container.innerHTML = `
        <p class="text-muted">Comparando Rev. ${escapeHtml(comparativo.revisao_atual)} com Rev. ${escapeHtml(comparativo.revisao_anterior)}</p>
        <div class="financial-grid">
            ${linhas.map(([label, valor, bruto]) => `
                <div class="financial-metric">
                    <small>${escapeHtml(label)}</small>
                    <strong class="${classeTendencia(label, bruto)}">${valor}</strong>
                </div>
            `).join('')}
        </div>
    `;
}

function renderizarAnalise(analise) {
    const container = document.getElementById('analiseContainer');
    container.innerHTML = '';

    if (analise.financeiro) {
        const fin = analise.financeiro;
        container.innerHTML += `
            <div class="analise-item financeiro">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="score-badge ${obterClasseScore(fin.score)}">${fin.score}</div>
                    </div>
                    <div class="col-md-9">
                        <h4>Financeiro</h4>
                        <p><strong>Investimento:</strong> ${formatarMoeda(fin.investimento)}</p>
                        <p><strong>ROI Esperado:</strong> ${formatarPercentual(fin.roi_esperado)}</p>
                        <p><strong>Payback:</strong> ${formatarPayback(fin.payback_meses)}</p>
                        <p><strong>Margem:</strong> ${formatarPercentual(fin.margem)}</p>
                    </div>
                </div>
            </div>
        `;
    }

    if (analise.planejamento) {
        const plan = analise.planejamento;
        const detalhesProjeto = plan.origem === 'projeto_vinculado'
            ? `
                <p><strong>Origem:</strong> Projeto vinculado</p>
                <p><strong>Progresso:</strong> ${formatarPercentual(plan.progresso_projeto)}</p>
                <p><strong>Tarefas concluídas:</strong> ${Number(plan.tarefas_concluidas || 0).toLocaleString('pt-BR')}</p>
                <p><strong>Tarefas atrasadas:</strong> ${Number(plan.tarefas_atrasadas || 0).toLocaleString('pt-BR')}</p>
                <p><strong>Pontualidade:</strong> ${formatarPercentual(plan.pontualidade_pct)}</p>
            `
            : '';
        container.innerHTML += `
            <div class="analise-item planejamento">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="score-badge ${obterClasseScore(plan.score)}">${plan.score}</div>
                    </div>
                    <div class="col-md-9">
                        <h4>Planejamento</h4>
                        <p><strong>Fases:</strong> ${Number(plan.fases || 0).toLocaleString('pt-BR')}</p>
                        <p><strong>Duração:</strong> ${formatarPayback(plan.duracao_meses)}</p>
                        ${detalhesProjeto}
                    </div>
                </div>
            </div>
        `;
    }

    if (analise.qualidade) {
        const qual = analise.qualidade;
        container.innerHTML += `
            <div class="analise-item qualidade">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="score-badge ${obterClasseScore(qual.score)}">${qual.score}</div>
                    </div>
                    <div class="col-md-9">
                        <h4>Qualidade</h4>
                        <p><strong>Cobertura de Testes:</strong> ${formatarPercentual(qual.cobertura_testes)}</p>
                        <p><strong>Score de Código:</strong> ${Number(qual.score_codigo || 0).toLocaleString('pt-BR')}</p>
                    </div>
                </div>
            </div>
        `;
    }

    if (analise.execucao) {
        const exec = analise.execucao;
        container.innerHTML += `
            <div class="analise-item planejamento">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="score-badge ${obterClasseScore(exec.score)}">${exec.score}</div>
                    </div>
                    <div class="col-md-9">
                        <h4>Execução</h4>
                        <p><strong>Progresso:</strong> ${formatarPercentual(exec.progresso)}</p>
                        <p><strong>Tarefas:</strong> ${Number(exec.tarefas_concluidas || 0).toLocaleString('pt-BR')}/${Number(exec.tarefas_total || 0).toLocaleString('pt-BR')}</p>
                        <p><strong>Pendentes:</strong> ${Number(exec.tarefas_pendentes || 0).toLocaleString('pt-BR')}</p>
                    </div>
                </div>
            </div>
        `;
    }

    if (analise.prazo) {
        const prazo = analise.prazo;
        container.innerHTML += `
            <div class="analise-item qualidade">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="score-badge ${obterClasseScore(prazo.score)}">${prazo.score}</div>
                    </div>
                    <div class="col-md-9">
                        <h4>Prazo</h4>
                        <p><strong>Pontualidade:</strong> ${formatarPercentual(prazo.pontualidade_pct)}</p>
                        <p><strong>Tarefas atrasadas:</strong> ${Number(prazo.tarefas_atrasadas || 0).toLocaleString('pt-BR')}</p>
                        <p><strong>Ação:</strong> ${escapeHtml(prazo.acao || '-')}</p>
                    </div>
                </div>
            </div>
        `;
    }

    if (analise.risco) {
        const risco = analise.risco;
        container.innerHTML += `
            <div class="analise-item financeiro">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="score-badge ${obterClasseScore(risco.score)}">${risco.score}</div>
                    </div>
                    <div class="col-md-9">
                        <h4>Risco</h4>
                        <p><strong>Nível:</strong> ${escapeHtml(risco.nivel || '-')}</p>
                        <p><strong>Alertas financeiros:</strong> ${Number(risco.alertas_financeiros || 0).toLocaleString('pt-BR')}</p>
                        <p><strong>Dados incompletos:</strong> ${(risco.dados_incompletos || []).length ? escapeHtml((risco.dados_incompletos || []).join(', ')) : 'nenhum'}</p>
                    </div>
                </div>
            </div>
        `;
    }

    if (analise.recursos) {
        const rec = analise.recursos;
        container.innerHTML += `
            <div class="analise-item recursos">
                <div class="row align-items-center">
                    <div class="col-md-3 text-center">
                        <div class="score-badge ${obterClasseScore(rec.score)}">${rec.score}</div>
                    </div>
                    <div class="col-md-9">
                        <h4>Recursos</h4>
                        <p><strong>Equipe:</strong> ${Number(rec.equipe || 0).toLocaleString('pt-BR')} pessoas</p>
                        <p><strong>Especialistas:</strong> ${Number(rec.especialistas || 0).toLocaleString('pt-BR')}</p>
                    </div>
                </div>
            </div>
        `;
    }
}

function renderizarCompatibilidades(compatibilidades) {
    const container = document.getElementById('compatibilidadesContainer');
    container.innerHTML = '';

    if (!compatibilidades || compatibilidades.length === 0) {
        container.innerHTML = '<p class="text-muted">Nenhuma compatibilidade registrada</p>';
        return;
    }

    compatibilidades.forEach(comp => {
        const classe = comp.status === 'compativel' ? 'compativel' : 'incompativel';
        const icone = comp.status === 'compativel' ? 'check-circle text-success' : 'times-circle text-danger';
        
        container.innerHTML += `
            <div class="compatibilidade-item ${classe}">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <i class="fas fa-2x fa-${icone}"></i>
                    </div>
                    <div class="col-md-4">
                        <h5>${escapeHtml(comp.area)}</h5>
                        <p class="mb-0"><strong>Score:</strong> ${Number(comp.score || 0).toLocaleString('pt-BR')}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-0">${escapeHtml(comp.detalhes)}</p>
                    </div>
                </div>
            </div>
        `;
    });
}

function exibirErro(mensagem) {
    document.getElementById('status').style.display = 'none';
    document.getElementById('resultsContainer').style.display = 'none';
    document.getElementById('errorContainer').classList.add('show');
    document.getElementById('errorMessage').textContent = mensagem;
}

function carregarAnalise() {
    const anviId = document.getElementById('anviInput').value.trim();

    if (!anviId) {
        exibirErro('Por favor, digite um ID do ANVI válido.');
        return;
    }

    // Show loading
    document.getElementById('status').innerHTML = `
        <div class="text-center">
            <div class="spinner-border mb-3" role="status">
                <span class="sr-only">Carregando...</span>
            </div>
            <p>Carregando dados do ANVI...</p>
        </div>
    `;
    document.getElementById('status').style.display = 'block';
    document.getElementById('resultsContainer').style.display = 'none';
    document.getElementById('errorContainer').classList.remove('show');

    // Fetch data
    fetch(`api/dashboard_viabilidade_simple.php?anvi_id=${encodeURIComponent(anviId)}&t=${Date.now()}`)
        .then(async response => {
            const data = await response.json().catch(() => null);
            if (!response.ok) {
                throw new Error(data?.erro || 'Erro ao buscar dados');
            }
            return data;
        })
        .then(data => {
            console.log('Dados recebidos:', data);
            
            if (!data || !data.anvi) {
                exibirErro('Dados inválidos recebidos do servidor.');
                return;
            }

            // Hide loading and show results
            document.getElementById('status').style.display = 'none';
            document.getElementById('errorContainer').classList.remove('show');
            document.getElementById('resultsContainer').style.display = 'block';

            // Populate executive summary
            document.getElementById('anviNumero').textContent = data.anvi.numero || data.anvi.id || 'N/A';
            document.getElementById('anviCliente').textContent = data.anvi.cliente || 'N/A';
            document.getElementById('anviProjeto').textContent = data.anvi.projeto || 'N/A';
            document.getElementById('anviStatus').textContent = data.anvi.status || 'N/A';
            document.getElementById('anviData').textContent = `Data ANVI: ${formatarData(data.anvi.data_anvi)}`;
            renderizarDecisao(data);

            renderizarIndicadoresFinanceiros(data.indicadores_financeiros || {}, data.alertas_financeiros || []);
            renderizarComparativoRevisoes(data.comparativo_revisoes || {});

            // Render analysis
            if (data.analise) {
                renderizarAnalise(data.analise);
            }

            // Render compatibilities
            if (data.compatibilidades) {
                renderizarCompatibilidades(data.compatibilidades);
            }

            atualizarInteligenciaComAnalise(data);
        })
        .catch(erro => {
            console.error('Erro:', erro);
            exibirErro(erro.message || 'Erro desconhecido ao carregar dados.');
        });
}

// Allow Enter key to load analysis
document.getElementById('anviInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        carregarAnalise();
    }
});

document.getElementById('anviInput').addEventListener('input', function(e) {
    clearTimeout(anviSuggestionTimer);
    anviSuggestionTimer = setTimeout(() => {
        buscarSugestoesANVI(e.target.value.trim());
    }, 250);
});

document.addEventListener('DOMContentLoaded', function() {
    renderizarPortfolio(demoPortfolio);
    buscarSugestoesANVI('');
    const params = new URLSearchParams(window.location.search);
    const anviId = params.get('anvi_id');
    if (anviId) {
        document.getElementById('anviInput').value = anviId;
        carregarAnalise();
    }
});
