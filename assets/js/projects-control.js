// ==============================================
// Estado global e perguntas APQP movidos para assets/js/projects-state.js.

// Conexao, carregamento e salvamento MySQL movidos para assets/js/projects-api.js.

// Datas, filtros, tabelas, status, eficiencia e progresso movidos para assets/js/projects-core.js.

// Formularios, lideres, historico, replanejamento e Excel movidos para assets/js/projects-forms.js.

// Gantt e cronograma movidos para assets/js/projects-gantt.js.

// Graficos e modais de analise movidos para assets/js/projects-charts.js.

// APQP movido para assets/js/projects-apqp.js.

// Capabilidade movida para assets/js/projects-capability.js.

// Logo, PDF e handover movidos para assets/js/projects-reports.js.

// FUNÇÕES AUXILIARES GERAIS
// ==============================================
function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
}

function setupModalCloseHandlers() {
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const modalId = this.getAttribute('data-close');
            if (modalId) {
                document.getElementById(modalId).style.display = 'none';
            }
        });
    });
    
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
}

// ==============================================
// FUNÇÕES DE SALVAMENTO LOCAL (fallback)
// ==============================================
function saveToLocalStorage() {
    // Apenas para manter a compatibilidade com funções que chamam isso
    // Não usamos mais localStorage como principal
}

// ==============================================
// CONFIGURAÇÃO DE EVENT LISTENERS
// ==============================================
function setupEventListeners() {
    // Botão Adicionar Projeto (apenas líder e admin)
    const addProjectBtn = document.getElementById('addProjectBtn');
    if (addProjectBtn) {
        addProjectBtn.addEventListener('click', () => showProjectForm());
    }
    
    // Botão Gerenciar Líderes (apenas admin)
    const manageLeadersBtn = document.getElementById('manageLeadersBtn');
    if (manageLeadersBtn) {
        manageLeadersBtn.addEventListener('click', showLeadersForm);
    }
    
    // Botão Salvar no MySQL (apenas líder e admin)
    const saveDataBtn = document.getElementById('saveDataBtn');
    if (saveDataBtn) {
        saveDataBtn.addEventListener('click', function() {
            if (mysqlConnected) {
                Promise.all(projects.map(p => saveProjectToMySQL(p)))
                    .then(() => alert('Todos os dados salvos no MySQL!'))
                    .catch(error => alert('Erro ao salvar no MySQL: ' + error));
            } else {
                alert('Não conectado ao MySQL. Verifique a conexão.');
            }
        });
    }
    
    // Botão Carregar do MySQL (todos têm acesso)
    document.getElementById('loadDataBtn').addEventListener('click', function() {
        if (mysqlConnected) {
            loadLeadersFromMySQL();
        } else {
            testMysqlConnection();
        }
    });
    
    // Botão Exportar Excel (todos têm acesso)
    document.getElementById('exportExcelBtn').addEventListener('click', exportToExcel);
    
    // Botão Importar Excel (apenas líder e admin)
    const importExcelBtn = document.getElementById('importExcelBtn');
    if (importExcelBtn) {
        importExcelBtn.addEventListener('click', importFromExcel);
    }
    
    // Botões de Gráficos e Filtros (todos têm acesso)
    document.getElementById('showChartsBtn').addEventListener('click', showChartsSection);
    document.getElementById('closeChartsBtn').addEventListener('click', hideChartsSection);
    document.getElementById('toggleFiltersBtn').addEventListener('click', () => {
        const filtersContainer = document.getElementById('filtersContainer');
        filtersContainer.classList.toggle('show');
    });
    
    // Botão Carregar Logo (apenas admin)
    const loadLogoBtn = document.getElementById('loadLogoBtn');
    if (loadLogoBtn) {
        loadLogoBtn.addEventListener('click', showLogoModal);
    }
    
    // Botão Testar Conexão MySQL (todos têm acesso)
    document.getElementById('testMysqlConnection').addEventListener('click', testMysqlConnection);
    
    const saveBtn = document.getElementById('saveProjectBtn');
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            console.log('Botão Salvar Projeto clicado!');
            saveProject();
        });
    } else {
        console.log('AVISO: Botão saveProjectBtn não encontrado! Usuário pode ser visualizador.');
    }

    const anviNumberInput = document.getElementById('anviNumber');
    if (anviNumberInput) {
        anviNumberInput.addEventListener('input', (e) => {
            anviNumberInput.dataset.lockedValue = '';
            clearTimeout(projectAnviSuggestionTimer);
            projectAnviSuggestionTimer = setTimeout(() => {
                buscarProjectAnviSuggestions(e.target.value.trim());
                syncSelectedProjectAnvi();
            }, 250);
        });
        anviNumberInput.addEventListener('change', syncSelectedProjectAnvi);
        buscarProjectAnviSuggestions('');
    }
    
    document.getElementById('cancelProjectBtn').addEventListener('click', () => {
        document.getElementById('projectForm').style.display = 'none';
    });
    
    // Botões de gerenciamento de líderes (modais sempre existem)
    const addLeaderBtn = document.getElementById('addLeaderBtn');
    if (addLeaderBtn) {
        addLeaderBtn.addEventListener('click', saveLeader);
    }
    
    const cancelLeaderBtn = document.getElementById('cancelLeaderBtn');
    if (cancelLeaderBtn) {
        cancelLeaderBtn.addEventListener('click', () => {
            document.getElementById('leadersForm').style.display = 'none';
        });
    }
    
    document.getElementById('saveHistoryBtn').addEventListener('click', saveHistoryItem);
    document.getElementById('cancelHistoryBtn').addEventListener('click', cancelHistoryEdit);
    
    document.getElementById('saveRescheduleBtn').addEventListener('click', saveReschedule);
    document.getElementById('cancelRescheduleBtn').addEventListener('click', () => {
        document.getElementById('rescheduleModal').style.display = 'none';
    });
    
    document.getElementById('confirmImportBtn').addEventListener('click', handleExcelImport);
    document.getElementById('cancelImportBtn').addEventListener('click', () => {
        document.getElementById('excelImportModal').style.display = 'none';
    });
    
    // Botões de logo (modais sempre existem, mas só admin acessa)
    const saveLogoBtn = document.getElementById('saveLogoBtn');
    if (saveLogoBtn) {
        saveLogoBtn.addEventListener('click', saveLogo);
    }
    
    const removeLogoBtn = document.getElementById('removeLogoBtn');
    if (removeLogoBtn) {
        removeLogoBtn.addEventListener('click', removeLogo);
    }
    
    document.getElementById('saveApqpBtn').addEventListener('click', saveApqpAnalysis);
    document.getElementById('cancelApqpBtn').addEventListener('click', () => {
        document.getElementById('apqpModal').style.display = 'none';
    });
    
    // Botões de filtro
    document.getElementById('applyFiltersBtn').addEventListener('click', function() {
        updateProjectsTable();
        updateSummary();
    });
    
    document.getElementById('clearAllFiltersBtn').addEventListener('click', clearAllFilters);
    
    document.getElementById('applyDateFilterBtn').addEventListener('click', function() {
        updateProjectsTable();
        updateSummary();
    });
    
    document.getElementById('clearDateFilterBtn').addEventListener('click', function() {
        document.getElementById('dateFilterType').value = 'todos';
        document.getElementById('taskSegmentoFilter').value = 'todos';
        document.getElementById('taskLeaderFilter').value = 'todos';
        document.getElementById('dateFilterFrom').value = '';
        document.getElementById('dateFilterTo').value = '';
        clearAllTaskStatuses();
        updateProjectsTable();
        updateSummary();
    });
    
    // Eventos de input nas tarefas
    const taskInputs = document.querySelectorAll('#projectForm input[type="date"]');
    taskInputs.forEach(input => {
        input.addEventListener('change', function() {
            const idParts = this.id.match(/(kom|ferramental|cadBomFt|tryout|entrega|psw|handover)(Planned|Start|Executed)/);
            if (idParts) {
                const taskKey = idParts[1];
                const taskData = {
                    planned: document.getElementById(`${taskKey}Planned`).value,
                    start: document.getElementById(`${taskKey}Start`).value,
                    executed: document.getElementById(`${taskKey}Executed`).value
                };
                const projectStatus = document.getElementById('projectStatusSelect').value;
                updateTaskStatusDisplay(taskKey, taskData, projectStatus);
            }
        });
    });
    
    document.getElementById('projectStatusSelect').addEventListener('change', function() {
        updateAllTaskStatusesDisplay(this.value);
    });
}

// ==============================================
// SINCRONIZAÇÃO EM TEMPO REAL (SSE)
// ==============================================
let eventSource = null;
let lastChangeId = 0;
let reconnectTimeout = null;
let ignoreSyncUntil = 0; // Timestamp para ignorar sincronização após salvar localmente

function initRealtimeSync() {
    if (eventSource) {
        eventSource.close();
    }
    
    // Conectar ao SSE
    eventSource = new EventSource('sse.php?lastId=' + lastChangeId);
    
    eventSource.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            
            // Ignorar pings
            if (data.type === 'ping') {
                return;
            }
            
            // Atualizar lastChangeId
            if (data.id) {
                lastChangeId = data.id;
            }
            
            // Ignorar sincronização se acabamos de salvar algo
            if (Date.now() < ignoreSyncUntil) {
                console.log('⏭ Ignorando sincronização SSE (salvamento recente)');
                return;
            }
            
            // Processar mudanças
            if (data.type === 'projeto_criado' || data.type === 'projeto_atualizado' || data.type === 'projeto_excluido') {
                console.log('Sincronizando projetos...', data.type);
                loadProjectsFromMySQL();
            } else if (data.type === 'lider_criado' || data.type === 'lider_atualizado' || data.type === 'lider_excluido') {
                console.log('Sincronizando líderes...', data.type);
                loadLeadersFromMySQL();
            }
            
        } catch (e) {
            console.error('Erro ao processar evento SSE:', e);
        }
    };
    
    eventSource.onerror = function(error) {
        console.log('Erro SSE, reconectando em 5 segundos...');
        eventSource.close();
        
        // Reconectar após 5 segundos
        if (reconnectTimeout) {
            clearTimeout(reconnectTimeout);
        }
        reconnectTimeout = setTimeout(initRealtimeSync, 5000);
    };
    
    eventSource.onopen = function() {
        console.log('Sincronização em tempo real ativada!');
    };
}

// ==============================================
// ATUALIZAÇÃO AUTOMÁTICA DE STATUS
// ==============================================
function autoUpdateTaskStatuses() {
    if (!projects || projects.length === 0) {
        console.log('Verificação automática: nenhum projeto carregado ainda');
        return;
    }
    
    console.log('Executando verificação automática de status...');
    let hasChanges = false;
    let updatedCount = 0;
    
    projects.forEach(project => {
        // Pular projetos cancelados ou em espera
        if (project.manualStatus === 'Cancelado' || project.manualStatus === 'Em Espera') {
            return;
        }
        
        // Guardar o status anterior
        const oldStatus = project.status;
        
        // Calcular o novo status do projeto baseado na data atual
        const newStatus = calculateProjectStatus(project);
        
        // Se o status mudou, atualizar
        if (oldStatus !== newStatus) {
            console.log(`Projeto ${project.id} (${project.name}): ${oldStatus} → ${newStatus}`);
            project.status = newStatus;
            hasChanges = true;
            updatedCount++;
            
            // Atualização automática é apenas visual; o banco é salvo nas ações do usuário.
        }
    });
    
    // Se houve mudanças, atualizar a interface
    if (hasChanges) {
        console.log(`✓ ${updatedCount} projeto(s) atualizado(s) automaticamente`);
        updateProjectsTable();
        updateSummary();
    } else {
        console.log('✓ Verificação concluída - nenhuma atualização necessária');
    }
}

// Função auxiliar para salvar um único projeto no MySQL sem recarregar tudo
function saveSingleProjectToMySQL(project) {
    // Ignorar sincronização SSE pelos próximos 3 segundos
    ignoreSyncUntil = Date.now() + 3000;
    
    $.ajax({
        url: 'api_mysql.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'saveProject',
            project: JSON.stringify(project)
        },
        success: function(response) {
            if (response.success) {
                console.log(`✓ Projeto ${project.id} (${project.name}) salvo automaticamente no MySQL`);
            } else {
                console.error(`✗ Erro ao salvar projeto ${project.id}:`, response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error(`✗ Erro AJAX ao salvar projeto ${project.id} automaticamente:`, error);
        }
    });
}

// ==============================================
// INICIALIZAÇÃO
// ==============================================
function init() {
    initLogo();

    // Inicializar com dados vazios até carregar do MySQL
    projects = [];
    
    updateLeaderFilter();
    updateTaskLeaderFilter();
    updateProjectLeaderSelect();
    updateLeadersList();
    updateProjectsTable();
    updateSummary();
    setupEventListeners();
    setupModalCloseHandlers();
    
    // Carregar dados automaticamente; o botão "Testar conexão" fica como diagnóstico manual.
    carregarDadosIniciaisDoMySQL();
    
    // Iniciar sincronização em tempo real após a tela estar pronta.
    initRealtimeSync();
    
    const dateInput = document.getElementById('capabilityStudyDate');
    if (dateInput && !dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // Executar verificação imediatamente após 2 segundos (tempo para carregar os dados)
    setTimeout(autoUpdateTaskStatuses, 2000);
    
    // Verificar status periodicamente sem gerar salvamentos em segundo plano.
    setInterval(autoUpdateTaskStatuses, 300000);
    
    // Verificar quando o usuário retorna à página/aba
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            console.log('Página ativa novamente - verificando status...');
            setTimeout(autoUpdateTaskStatuses, 500);
        }
    });
    
    // Verificar quando a janela ganha o foco
    window.addEventListener('focus', function() {
        console.log('Janela ganhou foco - verificando status...');
        setTimeout(autoUpdateTaskStatuses, 500);
    });
    
    console.log('Verificação automática de status ativada (primeira execução em 2 segundos, depois a cada 30 segundos)');
}

function obterProjetoIdDaUrl() {
    const rawValue = new URLSearchParams(window.location.search).get('projeto_id');
    if (!rawValue) {
        return null;
    }

    const projectId = parseInt(rawValue, 10);
    return Number.isFinite(projectId) ? projectId : null;
}

function abrirProjetoInicialDaUrl() {
    const projectId = obterProjetoIdDaUrl();
    if (!projectId) {
        return;
    }

    const project = projects.find(p => p.id === projectId);
    if (!project) {
        console.warn('Projeto informado na URL não foi encontrado:', projectId);
        return;
    }

    showProjectForm(projectId);
    verificarVinculoComANVI(projectId);

    const form = document.getElementById('projectForm');
    if (form) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// =========================================
// INTEGRAÇÃO COM ANVI
// =========================================

// Variável global para armazenar dados do vínculo
let vinculoANVI = null;

function renderProjectLinkedAnvi(anvi) {
    const panel = document.getElementById('projectLinkedAnviPanel');
    const title = document.getElementById('projectLinkedAnviTitle');
    const meta = document.getElementById('projectLinkedAnviMeta');
    if (!panel || !title || !meta) return;

    if (!anvi) {
        panel.classList.remove('visible');
        return;
    }

    title.textContent = `ANVI ${anvi.numero || anvi.id}${anvi.revisao ? ' Rev. ' + anvi.revisao : ''}`;
    meta.textContent = [anvi.cliente, anvi.projeto, anvi.produto, anvi.status].filter(Boolean).join(' • ') || 'Sem detalhes adicionais';
    panel.classList.add('visible');
}

// Verificar se o projeto selecionado está vinculado a uma ANVI
async function verificarVinculoComANVI(projetoId) {
    if (!projetoId) {
        document.getElementById('btnVerANVI').style.display = 'none';
        renderProjectLinkedAnvi(null);
        return;
    }
    
    try {
        const response = await fetch(`../api/verificar_vinculo.php?projeto_id=${projetoId}`);
        
        if (!response.ok) {
            console.error('Erro ao verificar vínculo');
            return;
        }
        
        vinculoANVI = await response.json();
        
        if (vinculoANVI.tem_vinculo && vinculoANVI.anvi) {
            // Projeto está vinculado a uma ANVI
            document.getElementById('btnVerANVI').style.display = 'inline-block';
            document.getElementById('btnVerANVI').title = `ANVI: ${vinculoANVI.anvi.numero || vinculoANVI.anvi.id}`;
            renderProjectLinkedAnvi(vinculoANVI.anvi);
        } else {
            // Projeto não está vinculado
            document.getElementById('btnVerANVI').style.display = 'none';
            renderProjectLinkedAnvi(null);
        }
    } catch (e) {
        console.error('Erro ao verificar vínculo:', e);
    }
}

// Abrir ANVI vinculada
function abrirANVIVinculada() {
    if (vinculoANVI && vinculoANVI.anvi) {
        window.open(`../anvi.html?anvi_id=${vinculoANVI.anvi.id}`, '_blank');
    } else {
        alert('Nenhuma ANVI vinculada a este projeto.');
    }
}

// Event listener para o botão Ver ANVI
document.getElementById('btnVerANVI')?.addEventListener('click', abrirANVIVinculada);
document.getElementById('projectLinkedAnviOpenBtn')?.addEventListener('click', abrirANVIVinculada);

// Observar mudanças na tabela de projetos para verificar vínculos
const observarSelecaoProjeto = () => {
    // Quando um projeto é carregado ou selecionado, verificar vínculo
    const originalLoadData = window.loadData;
    if (originalLoadData) {
        window.loadData = async function() {
            await originalLoadData.apply(this, arguments);
            // Após carregar, verificar se há projeto ativo
            if (projects && projects.length > 0) {
                // Se houver apenas um projeto, verificar vínculo
                if (projects.length === 1) {
                    verificarVinculoComANVI(projects[0].id);
                }
            }
        };
    }
    
    // Observar cliques nas linhas de projetos
    document.addEventListener('click', (e) => {
        const projetoRow = e.target.closest('.project-row');
        if (projetoRow && projetoRow.dataset.projectId) {
            const projetoId = parseInt(projetoRow.dataset.projectId);
            verificarVinculoComANVI(projetoId);
        }
    });
};

// Inicializar observação após DOM carregar
setTimeout(observarSelecaoProjeto, 1000);

document.addEventListener('DOMContentLoaded', init);


