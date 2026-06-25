// FUNÇÕES APQP (manter as originais)
// ==============================================
function showApqpAnalysis(projectId, phaseKey) {
    console.log('=== Abrindo Análise APQP ===');
    console.log('Project ID:', projectId);
    console.log('Phase Key:', phaseKey);
    
    closeAllModals();
    
    currentApqpProjectId = projectId;
    currentApqpPhase = phaseKey;
    
    const project = projects.find(p => p.id === projectId);
    if (!project) {
        console.error('ERRO: Projeto não encontrado');
        alert('Erro: Projeto não encontrado!');
        return;
    }
    
    console.log('Projeto encontrado:', project.name);
    
    // CRITICAL FIX: Garantir que apqp seja um objeto, não array
    if (!project.apqp || Array.isArray(project.apqp)) {
        console.warn('⚠️ APQP era array ou null ao abrir modal, convertendo para objeto');
        project.apqp = {};
    }
    
    currentApqpAnswers = project.apqp?.[phaseKey]?.answers || {};
    console.log('Respostas salvas anteriormente:', Object.keys(currentApqpAnswers).length);
    console.log('Estrutura APQP completa do projeto:', JSON.stringify(project.apqp, null, 2));
    console.log('Dados da fase atual:', JSON.stringify(project.apqp?.[phaseKey], null, 2));
    
    const phaseNames = {
        'kom': 'KOM - Kick-off Meeting',
        'ferramental': 'Ferramental',
        'cadBomFt': 'CAD+BOM+FT',
        'tryout': 'Try-out',
        'entrega': 'Entrega da Amostra',
        'psw': 'PSW',
        'handover': 'Handover'
    };
    
    document.getElementById('apqpModalTitle').textContent = `Análise APQP - ${phaseNames[phaseKey] || phaseKey}`;
    
    generateApqpQuestions(phaseKey);
    updateApqpSummary();
    document.getElementById('apqpModal').style.display = 'block';
    
    console.log('Modal APQP aberto com sucesso');
}

function generateApqpQuestions(phaseKey) {
    console.log('=== Gerando perguntas APQP ===');
    console.log('Phase:', phaseKey);
    
    const container = document.getElementById('apqpQuestionsContainer');
    if (!container) {
        console.error('ERRO: Container apqpQuestionsContainer não encontrado!');
        return;
    }
    
    const questions = APQP_QUESTIONS[phaseKey] || [];
    console.log('Total de perguntas a gerar:', questions.length);
    
    container.innerHTML = '';
    
    if (questions.length === 0) {
        console.warn('AVISO: Nenhuma pergunta definida para esta fase');
        container.innerHTML = '<p class="no-questions">Nenhuma pergunta definida para esta fase.</p>';
        return;
    }
    
    questions.forEach((q, index) => {
        const questionSection = document.createElement('div');
        questionSection.className = 'apqp-question-section';
        
        const savedAnswer = currentApqpAnswers[q.id] || {};
        
        questionSection.innerHTML = `
            <div class="apqp-question">
                <span class="apqp-question-label">${index + 1}. ${q.question}</span>
                <div class="phase-apqp-status">
                    <small><strong>Categoria:</strong> ${q.category}</small>
                </div>
                
                <div class="apqp-answer-options">
                    <label class="apqp-answer-option">
                        <input type="radio" name="apqp_${q.id}" value="sim" ${savedAnswer.answer === 'sim' ? 'checked' : ''}>
                        <span>Sim</span>
                    </label>
                    <label class="apqp-answer-option">
                        <input type="radio" name="apqp_${q.id}" value="nao" ${savedAnswer.answer === 'nao' ? 'checked' : ''}>
                        <span>Não</span>
                    </label>
                    <label class="apqp-answer-option">
                        <input type="radio" name="apqp_${q.id}" value="na" ${savedAnswer.answer === 'na' ? 'checked' : ''}>
                        <span>Não se Aplica</span>
                    </label>
                </div>
                
                <div class="apqp-observations">
                    <label>Observações:</label>
                    <textarea id="obs_${q.id}" placeholder="Adicione observações se necessário..." rows="2">${savedAnswer.observations || ''}</textarea>
                </div>
            </div>
        `;
        
        container.appendChild(questionSection);
    });
    
    console.log('✓ Perguntas APQP geradas com sucesso!');
    
    // Adicionar listener para atualizar o resumo quando uma resposta for selecionada
    const radioButtons = container.querySelectorAll('input[type="radio"]');
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('Resposta alterada:', this.name, '=', this.value);
            updateApqpSummaryLive();
        });
    });
}

function updateApqpSummary() {
    const questions = APQP_QUESTIONS[currentApqpPhase] || [];
    const totalQuestions = questions.length;
    
    let answeredQuestions = 0;
    questions.forEach(q => {
        if (currentApqpAnswers[q.id] && currentApqpAnswers[q.id].answer) {
            answeredQuestions++;
        }
    });
    
    let status = 'Não Iniciado';
    let statusClass = 'pending';
    
    if (answeredQuestions === totalQuestions && totalQuestions > 0) {
        status = 'Completo';
        statusClass = 'completed';
    } else if (answeredQuestions > 0) {
        status = 'Parcial';
        statusClass = 'partial';
    }
    
    document.getElementById('apqpTotalQuestions').textContent = totalQuestions;
    document.getElementById('apqpAnsweredQuestions').textContent = answeredQuestions;
    document.getElementById('apqpStatusValue').textContent = status;
    document.getElementById('apqpStatusValue').className = `apqp-summary-value ${statusClass}`;
}

// Função para atualizar o resumo em tempo real conforme o usuário responde
function updateApqpSummaryLive() {
    const questions = APQP_QUESTIONS[currentApqpPhase] || [];
    const totalQuestions = questions.length;
    
    let answeredQuestions = 0;
    questions.forEach(q => {
        const answerElement = document.querySelector(`input[name="apqp_${q.id}"]:checked`);
        if (answerElement) {
            answeredQuestions++;
        }
    });
    
    let status = 'Não Iniciado';
    let statusClass = 'pending';
    
    if (answeredQuestions === totalQuestions && totalQuestions > 0) {
        status = 'Completo';
        statusClass = 'completed';
    } else if (answeredQuestions > 0) {
        status = 'Parcial';
        statusClass = 'partial';
    }
    
    document.getElementById('apqpTotalQuestions').textContent = totalQuestions;
    document.getElementById('apqpAnsweredQuestions').textContent = answeredQuestions;
    document.getElementById('apqpStatusValue').textContent = status;
    document.getElementById('apqpStatusValue').className = `apqp-summary-value ${statusClass}`;
    
    console.log('Resumo atualizado:', answeredQuestions, '/', totalQuestions);
}

function saveApqpAnalysis() {
    console.log('=== Salvando Análise APQP ===');
    console.log('Project ID:', currentApqpProjectId);
    console.log('Phase:', currentApqpPhase);
    
    if (!currentApqpProjectId || !currentApqpPhase) {
        console.error('ERRO: currentApqpProjectId ou currentApqpPhase não definidos');
        alert('Erro: Projeto ou fase não identificados. Por favor, tente novamente.');
        return;
    }
    
    const project = projects.find(p => p.id === currentApqpProjectId);
    if (!project) {
        console.error('ERRO: Projeto não encontrado no array projects');
        alert('Erro: Projeto não encontrado. Por favor, recarregue a página.');
        return;
    }
    
    console.log('Projeto encontrado:', project.name);
    
    // CRITICAL FIX: Garantir que apqp seja um objeto, não array
    if (!project.apqp || Array.isArray(project.apqp)) {
        console.warn('⚠️ APQP era array ou null, convertendo para objeto');
        project.apqp = {};
    }
    if (!project.apqp[currentApqpPhase]) project.apqp[currentApqpPhase] = {};
    
    const questions = APQP_QUESTIONS[currentApqpPhase] || [];
    console.log('Total de perguntas:', questions.length);
    
    const answers = {};
    let answeredCount = 0;
    
    questions.forEach(q => {
        const answerElement = document.querySelector(`input[name="apqp_${q.id}"]:checked`);
        const observationsElement = document.getElementById(`obs_${q.id}`);
        
        if (answerElement) {
            answers[q.id] = {
                question: q.question,
                category: q.category,
                answer: answerElement.value,
                observations: observationsElement ? observationsElement.value : '',
                date: new Date().toISOString().split('T')[0]
            };
            answeredCount++;
        }
    });
    
    console.log('Respostas coletadas:', answeredCount);
    console.log('Dados das respostas:', answers);
    
    // CRITICAL FIX: Garantir que apqp seja objeto antes de adicionar fase
    if (!project.apqp || Array.isArray(project.apqp)) {
        console.warn('⚠️ APQP era array ao salvar, convertendo para objeto');
        project.apqp = {};
    }
    
    project.apqp[currentApqpPhase] = {
        answers: answers,
        lastUpdated: new Date().toISOString(),
        completed: Object.keys(answers).length === questions.length
    };
    
    console.log('APQP atualizado no projeto local:');
    console.log('- Fase:', currentApqpPhase);
    console.log('- Respostas salvas:', Object.keys(answers).length);
    console.log('- Completo:', Object.keys(answers).length === questions.length);
    console.log('- Estrutura APQP do projeto:', JSON.stringify(project.apqp, null, 2));
    
    console.log('Salvando projeto no MySQL...');
    console.log('Dados completos do projeto a ser salvo:');
    console.table({
        'ID': project.id,
        'Nome': project.name,
        'Tem APQP': !!project.apqp,
        'Fases APQP': project.apqp ? Object.keys(project.apqp).join(', ') : 'nenhuma',
        'Respostas na fase atual': project.apqp?.[currentApqpPhase]?.answers ? Object.keys(project.apqp[currentApqpPhase].answers).length : 0
    });
    
    // Debug: mostrar o JSON que será enviado
    const jsonToSend = JSON.stringify(project);
    console.log('📤 JSON que será enviado (primeiros 500 chars):', jsonToSend.substring(0, 500));
    console.log('📤 Tamanho total do JSON:', jsonToSend.length, 'caracteres');
    
    // Verificar se APQP está no JSON stringificado
    if (jsonToSend.includes('"apqp"')) {
        console.log('✅ Confirmado: "apqp" está presente no JSON string');
        const apqpMatch = jsonToSend.match(/"apqp":\{[^}]+/);
        if (apqpMatch) {
            console.log('   Trecho do APQP no JSON:', apqpMatch[0].substring(0, 200));
        }
    } else {
        console.error('❌ ALERTA: "apqp" NÃO está no JSON string que será enviado!');
    }
    
    // Salvar no MySQL
    saveProjectToMySQL(project).then((response) => {
        console.log('✓ Análise APQP salva com sucesso no MySQL!', response);
        
        // Atualizar o projeto no array local com certeza
        const projectIndex = projects.findIndex(p => p.id === currentApqpProjectId);
        if (projectIndex !== -1) {
            projects[projectIndex] = project;
            console.log('✓ Projeto atualizado no array local na posição', projectIndex);
        }
        
        // Ignorar sincronização SSE pelos próximos 3 segundos
        ignoreSyncUntil = Date.now() + 3000;
        console.log('⏱ Sincronização SSE será ignorada pelos próximos 3 segundos');
        
        closeAllModals();
        
        if (currentTimelineProjectId === currentApqpProjectId) {
            showTimeline(currentTimelineProjectId);
        }
        
        updateProjectsTable();
        updateSummary();
        
        // Após 2 segundos, verificar se os dados foram salvos (aumentado o tempo)
        setTimeout(() => {
            console.log('🔍 Verificando se os dados APQP foram persistidos...');
            verifyApqpInDatabase(currentApqpProjectId, currentApqpPhase);
        }, 2000);
        
        alert(`Análise APQP salva com sucesso!\n${answeredCount} de ${questions.length} perguntas respondidas.`);
    }).catch(error => {
        console.error('✗ Erro ao salvar análise APQP no MySQL:', error);
        alert('Erro ao salvar análise APQP no MySQL: ' + error);
    });
}

// Função para verificar se os dados APQP foram realmente salvos no banco
function verifyApqpInDatabase(projectId, phase) {
    console.log('🔍 Verificando projeto', projectId, 'diretamente no MySQL...');
    
    $.ajax({
        url: 'api_mysql.php',
        type: 'POST',
        data: {
            action: 'getProjectById',
            projectId: projectId
        },
        dataType: 'json',
        success: function(response) {
            console.log('📥 Resposta completa do MySQL:', response);
            
            if (response.success) {
                const project = response.data;
                
                console.log('📦 Projeto recarregado:', project.name || 'sem nome');
                console.log('📦 ID do projeto:', project.id);
                console.log('📦 JSON bruto (primeiros 1000 chars):\n', response.rawJson);
                console.log('📦 Objeto completo:', project);
                console.log('📦 Chaves do projeto:', Object.keys(project));
                
                // CRITICAL FIX: Verificar se apqp é array vazio
                if (Array.isArray(project.apqp)) {
                    console.error('❌ PROBLEMA CRÍTICO! project.apqp é um ARRAY, não um OBJETO!');
                    console.error('   Valor:', project.apqp);
                    console.error('   Tipo:', typeof project.apqp);
                    alert('ERRO CRÍTICO: O APQP foi salvo como array vazio no banco!\n\nIsso impede o salvamento correto dos dados.\nO sistema converterá para objeto nas próximas tentativas.');
                    return;
                }
                
                if (project.apqp) {
                    console.log('📦 project.apqp existe!', typeof project.apqp);
                    console.log('📦 Chaves em apqp:', Object.keys(project.apqp));
                    
                    if (project.apqp[phase]) {
                        const answersCount = Object.keys(project.apqp[phase].answers || {}).length;
                        console.log(`✅ SUCESSO! Dados APQP da fase "${phase}" estão no banco!`);
                        console.log(`   ${answersCount} respostas encontradas`);
                        console.log('   Dados completos:', JSON.stringify(project.apqp[phase], null, 2));
                    } else {
                        console.error(`❌ PROBLEMA! Fase "${phase}" não encontrada em project.apqp`);
                        console.log('   Fases disponíveis:', Object.keys(project.apqp));
                        alert(`AVISO: A fase "${phase}" não foi salva!\nFases encontradas: ${Object.keys(project.apqp).join(', ')}`);
                    }
                } else {
                    console.error('❌ PROBLEMA! project.apqp não existe ou está vazio!');
                    console.log('   Tipo de project.apqp:', typeof project.apqp);
                    console.log('   Valor de project.apqp:', project.apqp);
                    alert('AVISO: Os dados APQP não foram salvos!\nO objeto APQP não existe no banco de dados.');
                }
            } else {
                console.error('❌ Erro ao buscar projeto:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Erro AJAX ao verificar:', error);
            console.error('   Status:', status);
            console.error('   Resposta:', xhr.responseText);
        }
    });
}

// Função de debug para verificar dados APQP
function debugApqpData() {
    console.log('═══════════════════════════════════════');
    console.log('🔍 DEBUG: Verificando dados APQP');
    console.log('═══════════════════════════════════════');
    console.log('ℹ️ Logs do servidor PHP: C:\\xampp\\apache\\logs\\error.log');
    console.log('ℹ️ Use "tail -f" ou abra o arquivo para ver logs em tempo real\n');
    
    console.log('📦 DADOS LOCAIS (array projects):');
    projects.forEach(p => {
        if (p.apqp && Object.keys(p.apqp).length > 0) {
            console.log(`\n  Projeto "${p.name}" (ID: ${p.id}):`);
            Object.keys(p.apqp).forEach(phase => {
                const answersCount = Object.keys(p.apqp[phase].answers || {}).length;
                console.log(`    - ${phase}: ${answersCount} respostas`);
            });
        }
    });
    
    console.log('\n📡 RECARREGANDO DO MYSQL...');
    $.ajax({
        url: 'api_mysql.php',
        type: 'POST',
        data: { action: 'getProjects' },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                console.log('\n💾 DADOS NO MYSQL:');
                let foundApqp = false;
                response.data.forEach(p => {
                    if (p.apqp && Object.keys(p.apqp).length > 0) {
                        foundApqp = true;
                        console.log(`\n  Projeto "${p.name}" (ID: ${p.id}):`);
                        Object.keys(p.apqp).forEach(phase => {
                            const answersCount = Object.keys(p.apqp[phase].answers || {}).length;
                            console.log(`    - ${phase}: ${answersCount} respostas`);
                            console.log(`      Dados:`, p.apqp[phase]);
                        });
                    }
                });
                
                if (!foundApqp) {
                    console.warn('⚠️ NENHUM projeto com dados APQP encontrado no MySQL!');
                }
                
                console.log('\n═══════════════════════════════════════');
                alert('Debug APQP concluído! Verifique o console (F12)');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro ao carregar projetos:', error);
        }
    });
}

function getApqpStatus(project, phaseKey) {
    if (!project.apqp || !project.apqp[phaseKey]) {
        return { status: 'pending', answered: 0, total: APQP_QUESTIONS[phaseKey]?.length || 0 };
    }
    
    const phaseData = project.apqp[phaseKey];
    const questions = APQP_QUESTIONS[phaseKey] || [];
    const total = questions.length;
    
    if (!phaseData.answers || Object.keys(phaseData.answers).length === 0) {
        return { status: 'pending', answered: 0, total };
    }
    
    const answered = Object.keys(phaseData.answers).length;
    
    if (answered === total) {
        return { status: 'completed', answered, total };
    } else if (answered > 0) {
        return { status: 'partial', answered, total };
    } else {
        return { status: 'pending', answered, total };
    }
}

function getApqpBadgeHtml(project, phaseKey) {
    const status = getApqpStatus(project, phaseKey);
    
    if (status.total === 0) return '';
    
    let badgeClass = 'pending';
    let badgeText = `${status.answered}/${status.total}`;
    
    if (status.status === 'completed') {
        badgeClass = 'completed';
        badgeText = `✓ ${status.answered}/${status.total}`;
    } else if (status.status === 'partial') {
        badgeClass = 'partial';
    }
    
    return `
        <button class="apqp-badge ${badgeClass}" onclick="showApqpAnalysis(${project.id}, '${phaseKey}')">
            <i class="fas fa-clipboard-check"></i> APQP: ${badgeText}
        </button>
    `;
}

// ==============================================

