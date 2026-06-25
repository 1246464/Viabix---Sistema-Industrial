// FUNÇÕES DE CONEXÃO COM MYSQL
// ==============================================
function updateMysqlStatus(status, message) {
    const statusElement = document.getElementById('mysqlStatus');
    if (!statusElement) return;
    
    statusElement.className = `mysql-status ${status}`;
    
    if (status === 'connected') {
        statusElement.innerHTML = `<i class="fas fa-check-circle"></i> Conectado`;
        mysqlConnected = true;
    } else if (status === 'disconnected') {
        statusElement.innerHTML = `<i class="fas fa-times-circle"></i> Desconectado`;
        mysqlConnected = false;
    } else {
        statusElement.innerHTML = `<i class="fas fa-sync fa-spin"></i> Verificando...`;
        mysqlConnected = false;
    }
    
    if (message) {
        console.log('MySQL Status:', message);
    }
}

function testMysqlConnection() {
    updateMysqlStatus('checking');
    
    $.ajax({
        url: 'api_mysql.php',
        type: 'POST',
        data: {
            action: 'testConnection'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                updateMysqlStatus('connected', response.message);
                loadLeadersFromMySQL();
            } else {
                updateMysqlStatus('disconnected', response.message);
                alert('Erro de conexão MySQL: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            updateMysqlStatus('disconnected', error);
            if (xhr.status === 401) {
                window.location.href = '../login.html';
                return;
            }
            alert('Erro ao conectar ao servidor: ' + error);
        }
    });
}

function handleApiSessionError(xhr) {
    if (xhr && xhr.status === 401) {
        updateMysqlStatus('disconnected', 'Sessão expirada');
        window.location.href = '../login.html';
        return true;
    }
    return false;
}

let projectLoadingCount = 0;
let projectLoadingTimer = null;

function getProjectLoadingMessage(action) {
    const messages = {
        testConnection: 'Verificando conexão com o servidor...',
        getLeaders: 'Carregando líderes...',
        getProjects: 'Carregando projetos...',
        saveProject: 'Salvando projeto...',
        deleteProject: 'Excluindo projeto...',
        saveLeader: 'Salvando líder...',
        deleteLeader: 'Excluindo líder...'
    };

    return messages[action] || 'Atualizando informações...';
}

function setProjectSaveStatus(status, message) {
    const element = document.getElementById('projectSaveStatus');
    if (!element) return;

    const icons = {
        idle: 'fa-circle',
        saving: 'fa-circle-notch fa-spin',
        saved: 'fa-circle-check',
        error: 'fa-triangle-exclamation'
    };

    element.className = `save-status-pill ${status && status !== 'idle' ? status : ''}`.trim();
    element.innerHTML = `<i class="fas ${icons[status] || icons.idle}"></i>${message || 'Pronto'}`;
}

function setProjectLoading(message) {
    const overlay = document.getElementById('projectLoadingOverlay');
    const messageElement = document.getElementById('projectLoadingMessage');
    if (!overlay || !messageElement) return;

    messageElement.textContent = message || 'Aguarde um instante...';
    document.body.classList.add('project-busy');
    clearTimeout(projectLoadingTimer);
    projectLoadingTimer = setTimeout(() => {
        overlay.classList.add('visible');
        overlay.setAttribute('aria-hidden', 'false');
    }, 180);
}

function clearProjectLoading() {
    const overlay = document.getElementById('projectLoadingOverlay');
    if (!overlay) return;

    clearTimeout(projectLoadingTimer);
    if (projectLoadingCount <= 0) {
        overlay.classList.remove('visible');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('project-busy');
    }
}

function extractAjaxAction(settings) {
    const data = settings && settings.data;
    if (!data) return '';

    if (typeof data === 'string') {
        return new URLSearchParams(data).get('action') || '';
    }

    return data.action || '';
}

$(document)
    .ajaxSend(function(_event, _xhr, settings) {
        if (!settings.url || !settings.url.includes('api_mysql.php')) return;
        _xhr.setRequestHeader('X-CSRF-Token', window.viabixCsrfToken || '');
        if (extractAjaxAction(settings) === 'saveProject') return;
        projectLoadingCount++;
        setProjectLoading(getProjectLoadingMessage(extractAjaxAction(settings)));
    })
    .ajaxComplete(function(_event, _xhr, settings) {
        if (!settings.url || !settings.url.includes('api_mysql.php')) return;
        if (extractAjaxAction(settings) === 'saveProject') return;
        projectLoadingCount = Math.max(0, projectLoadingCount - 1);
        clearProjectLoading();
    });

function carregarDadosIniciaisDoMySQL() {
    updateMysqlStatus('checking', 'Carregando dados...');
    loadLeadersFromMySQL();
}

// ==============================================
// FUNÇÕES DE CARREGAMENTO DO MYSQL
// ==============================================
function loadLeadersFromMySQL() {
    $.ajax({
        url: 'api_mysql.php',
        type: 'POST',
        data: {
            action: 'getLeaders'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                leaders = response.data;
                nextLeaderId = leaders.length > 0 ? Math.max(...leaders.map(l => l.id)) + 1 : 1;
                
                updateLeaderFilter();
                updateTaskLeaderFilter();
                updateProjectLeaderSelect();
                updateLeadersList();
                
                // Agora carrega os projetos
                loadProjectsFromMySQL();
            } else {
                console.error('Erro ao carregar líderes:', response.message);
                // Se não conseguir carregar líderes, ainda tenta carregar projetos
                loadProjectsFromMySQL();
            }
        },
        error: function(xhr, status, error) {
            if (handleApiSessionError(xhr)) return;
            console.error('Erro AJAX ao carregar líderes:', error);
            loadProjectsFromMySQL();
        }
    });
}

function loadProjectsFromMySQL() {
    $.ajax({
        url: 'api_mysql.php',
        type: 'POST',
        data: {
            action: 'getProjects'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                projects = response.data;
                
                console.log('✓ Projetos carregados do MySQL:', projects.length);
                
                // Garantir que cada projeto tenha a estrutura correta
                projects.forEach(p => {
                    if (!p.tasks) p.tasks = {};
                    
                    // CRITICAL FIX: Converter array vazio em objeto
                    if (!p.apqp || Array.isArray(p.apqp)) {
                        console.warn(`⚠️ Projeto ${p.id}: apqp é array ou null, convertendo para objeto`);
                        p.apqp = {};
                    }
                    
                    if (!p.capability) p.capability = { characteristics: [] };
                    
                    // Log dos dados APQP se existirem
                    if (p.apqp && Object.keys(p.apqp).length > 0) {
                        console.log(`  📋 Projeto "${p.name}" (ID: ${p.id}) possui dados APQP nas fases:`, Object.keys(p.apqp));
                        // Mostrar quantas respostas em cada fase
                        Object.keys(p.apqp).forEach(phase => {
                            const answersCount = Object.keys(p.apqp[phase].answers || {}).length;
                            console.log(`     - ${phase}: ${answersCount} respostas`);
                        });
                    }
                });
                
                nextProjectId = projects.length > 0 ? Math.max(...projects.map(p => p.id)) + 1 : 1;
                
                // Verificar e atualizar status de tarefas vencidas imediatamente
                autoUpdateTaskStatuses();
                
                updateProjectsTable();
                updateSummary();
                abrirProjetoInicialDaUrl();
                updateMysqlStatus('connected', 'Dados carregados com sucesso');
            } else {
                console.error('Erro ao carregar projetos:', response.message);
                updateMysqlStatus('disconnected', response.message);
                
                // Fallback para dados vazios
                projects = [];
                updateProjectsTable();
                updateSummary();
            }
        },
        error: function(xhr, status, error) {
            if (handleApiSessionError(xhr)) return;
            console.error('Erro AJAX ao carregar projetos:', error);
            updateMysqlStatus('disconnected', error);
            
            // Fallback para dados vazios
            projects = [];
            updateProjectsTable();
            updateSummary();
        }
    });
}

// ==============================================
// FUNÇÕES DE SALVAMENTO NO MYSQL
// ==============================================
function saveProjectToMySQL(projectData) {
    console.log('>>> saveProjectToMySQL chamado');
    console.log('>>> Dados a enviar:', {
        id: projectData.id,
        name: projectData.name,
        hasApqp: !!projectData.apqp,
        apqpKeys: projectData.apqp ? Object.keys(projectData.apqp) : []
    });
    
    setProjectSaveStatus('saving', 'Salvando...');
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'api_mysql.php',
            type: 'POST',
            data: {
                action: 'saveProject',
                project: JSON.stringify(projectData)
            },
            dataType: 'json',
            success: function(response) {
                console.log('>>> Resposta do MySQL:', response);
                if (response.success) {
                    console.log('✓ MySQL confirmou salvamento');
                    setProjectSaveStatus('saved', 'Salvo');
                    resolve(response);
                } else {
                    console.error('✗ MySQL retornou erro:', response.message);
                    const message = response.error_id
                        ? `${response.message || 'Erro ao salvar'} (código ${response.error_id})`
                        : (response.message || 'Erro ao salvar');
                    setProjectSaveStatus('error', response.error_id ? `Erro ${response.error_id}` : 'Erro ao salvar');
                    reject(message);
                }
            },
            error: function(xhr, status, error) {
                console.error('✗ Erro AJAX ao salvar:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                let message = 'Erro ao salvar. Tente novamente em instantes.';
                try {
                    const payload = JSON.parse(xhr.responseText || '{}');
                    if (payload.message) message = payload.message;
                    if (payload.error_id) message += ` (código ${payload.error_id})`;
                    setProjectSaveStatus('error', payload.error_id ? `Erro ${payload.error_id}` : 'Erro ao salvar');
                } catch (_e) {
                    setProjectSaveStatus('error', 'Erro ao salvar');
                    if (error) message = error;
                }
                reject(message);
            }
        });
    });
}

function deleteProjectFromMySQL(projectId) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'api_mysql.php',
            type: 'POST',
            data: {
                action: 'deleteProject',
                projectId: projectId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    resolve(response);
                } else {
                    reject(response.message);
                }
            },
            error: function(xhr, status, error) {
                reject(error);
            }
        });
    });
}

function saveLeaderToMySQL(leaderData) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'api_mysql.php',
            type: 'POST',
            data: {
                action: 'saveLeader',
                leader: JSON.stringify(leaderData)
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    resolve(response);
                } else {
                    reject(response.message);
                }
            },
            error: function(xhr, status, error) {
                reject(error);
            }
        });
    });
}

function deleteLeaderFromMySQL(leaderId) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'api_mysql.php',
            type: 'POST',
            data: {
                action: 'deleteLeader',
                leaderId: leaderId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    resolve(response);
                } else {
                    reject(response.message);
                }
            },
            error: function(xhr, status, error) {
                reject(error);
            }
        });
    });
}

// ==============================================

