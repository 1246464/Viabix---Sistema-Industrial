// FUNÇÕES DE FORMULÁRIO DE PROJETO (modificadas para salvar no MySQL)
// ==============================================
let projectAnviSuggestionTimer = null;
let projectAnviSuggestionMap = new Map();

function renderProjectAnviSuggestions(lista) {
    const datalist = document.getElementById('projectAnviSuggestions');
    if (!datalist) return;

    projectAnviSuggestionMap = new Map();
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
        projectAnviSuggestionMap.set(value, item);
    });
}

function buscarProjectAnviSuggestions(termo = '') {
    const projectId = currentEditingProjectId || '';
    fetch(`../api/anvis_sugestoes.php?q=${encodeURIComponent(termo)}&limit=20&available_for_project=1&project_id=${encodeURIComponent(projectId)}`)
        .then(response => response.ok ? response.json() : null)
        .then(data => {
            if (data?.success) {
                renderProjectAnviSuggestions(data.data || []);
                syncSelectedProjectAnvi();
            }
        })
        .catch(() => {});
}

function syncSelectedProjectAnvi() {
    const numberInput = document.getElementById('anviNumber');
    const idInput = document.getElementById('anviId');
    if (!numberInput || !idInput) return;

    const selected = projectAnviSuggestionMap.get(numberInput.value.trim());
    if (selected) {
        idInput.value = selected.id || '';
    } else if (numberInput.dataset.lockedValue !== numberInput.value.trim()) {
        idInput.value = '';
    }
}

function showProjectForm(editId = null) {
    closeAllModals();
    document.getElementById('projectForm').style.display = 'block';
    document.getElementById('leadersForm').style.display = 'none';
    document.getElementById('chartsSection').style.display = 'none';
    
    currentEditingProjectId = editId;
    document.getElementById('formTitle').textContent = editId ? 'Editar Projeto' : 'Novo Projeto';
    
    if (editId) {
        const p = projects.find(pr => pr.id === editId);
        if (p) {
            document.getElementById('cliente').value = p.cliente || '';
            document.getElementById('projectName').value = p.projectName || '';
            document.getElementById('segmento').value = p.segmento || '';
            document.getElementById('projectLeader').value = p.leaderId || '';
            document.getElementById('codigo').value = p.codigo || '';
            document.getElementById('anviNumber').value = p.anviNumber || '';
            document.getElementById('anviNumber').dataset.lockedValue = p.anviNumber || '';
            document.getElementById('anviId').value = p.anviId || p.sourceContext?.anviId || '';
            document.getElementById('modelo').value = p.modelo || '';
            document.getElementById('processo').value = p.processo || '';
            document.getElementById('fase').value = p.fase || '';
            document.getElementById('observacoes').value = p.observacoes || '';
            
            let projectStatusValue = 'automatico';
            if (p.status === 'Em Espera') projectStatusValue = 'em espera';
            else if (p.status === 'Cancelado') projectStatusValue = 'cancelado';
            document.getElementById('projectStatusSelect').value = projectStatusValue;
            
            fillTaskDates('kom', p.tasks?.kom);
            fillTaskDates('ferramental', p.tasks?.ferramental);
            fillTaskDates('cadBomFt', p.tasks?.cadBomFt);
            fillTaskDates('tryout', p.tasks?.tryout);
            fillTaskDates('entrega', p.tasks?.entrega);
            fillTaskDates('psw', p.tasks?.psw);
            fillTaskDates('handover', p.tasks?.handover);
            
            document.getElementById('tryoutNumber').value = p.tasks?.tryout?.number || '';
            document.getElementById('entregaNumber').value = p.tasks?.entrega?.number || '';
            
            document.getElementById('tryoutQuantidadeEntrada').value = p.tasks?.tryout?.quantidadeEntrada || '';
            document.getElementById('tryoutQuantidadeSaida').value = p.tasks?.tryout?.quantidadeSaida || '';
            
            document.getElementById('tryoutCorte').value = p.tasks?.tryout?.resources?.corte || '';
            document.getElementById('tryoutLapidacao').value = p.tasks?.tryout?.resources?.lapidacao || '';
            document.getElementById('tryoutFuracao').value = p.tasks?.tryout?.resources?.furacao || '';
            document.getElementById('tryoutMontagem').value = p.tasks?.tryout?.resources?.montagem || '';
            document.getElementById('tryoutSerigrafia').value = p.tasks?.tryout?.resources?.serigrafia || '';
            document.getElementById('tryoutQueima').value = p.tasks?.tryout?.resources?.queima || '';
            document.getElementById('tryoutFornos').value = p.tasks?.tryout?.resources?.fornos || '';
            
            document.getElementById('ferramentalFemea').value = toISODateString(p.tasks?.ferramental?.resources?.femea) || '';
            document.getElementById('ferramentalGabaritoFanavid').value = toISODateString(p.tasks?.ferramental?.resources?.gabaritoFanavid) || '';
            document.getElementById('ferramentalGabaritoUsinado').value = toISODateString(p.tasks?.ferramental?.resources?.gabaritoUsinado) || '';
            document.getElementById('ferramentalMatriz').value = toISODateString(p.tasks?.ferramental?.resources?.matriz) || '';
            document.getElementById('ferramentalMacho').value = toISODateString(p.tasks?.ferramental?.resources?.macho) || '';
            document.getElementById('ferramentalTemplate').value = toISODateString(p.tasks?.ferramental?.resources?.template) || '';
            document.getElementById('ferramentalChapelona').value = toISODateString(p.tasks?.ferramental?.resources?.chapelona) || '';
            document.getElementById('ferramentalPlotter').value = toISODateString(p.tasks?.ferramental?.resources?.plotter) || '';
            document.getElementById('ferramentalTela').value = toISODateString(p.tasks?.ferramental?.resources?.tela) || '';
            
            if (p.capability) {
                loadCapabilityData(p);
            } else {
                const container = document.getElementById('capabilityCharacteristics');
                if (container) {
                    container.innerHTML = '';
                    addCapabilityCharacteristic();
                }
            }
            
            updateAllTaskStatusesDisplay(p.status);
            verificarVinculoComANVI(editId);
        }
    } else {
        clearProjectForm();
        renderProjectLinkedAnvi(null);
        
        const container = document.getElementById('capabilityCharacteristics');
        if (container) {
            container.innerHTML = '';
            addCapabilityCharacteristic();
        }
        
        updateAllTaskStatusesDisplay('automatico');
    }
    
    document.getElementById('projectForm').scrollTop = 0;
    updateCapabilityProjectInfo();
}

function clearProjectForm() {
    // Limpar todos os campos do formulário de projeto
    const fields = [
        'cliente', 'projectName', 'segmento', 'projectLeader', 'codigo', 
        'anviNumber', 'anviId', 'modelo', 'processo', 'fase', 'observacoes',
        'projectStatusSelect'
    ];
    fields.forEach(field => {
        const el = document.getElementById(field);
        if (el) el.value = '';
    });
    const anviInput = document.getElementById('anviNumber');
    if (anviInput) anviInput.dataset.lockedValue = '';
    
    // Limpar datas das tarefas
    const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
    taskKeys.forEach(taskKey => {
        fillTaskDates(taskKey, null);
    });
}

function updateAllTaskStatusesDisplay(projectStatus) {
    const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
    taskKeys.forEach(taskKey => {
        const taskData = {
            planned: document.getElementById(`${taskKey}Planned`).value,
            start: document.getElementById(`${taskKey}Start`).value,
            executed: document.getElementById(`${taskKey}Executed`).value
        };
        updateTaskStatusDisplay(taskKey, taskData, projectStatus);
    });
}

function updateTaskStatusDisplay(taskKey, taskData, projectStatus) {
    const statusCell = document.getElementById(`${taskKey}StatusCell`);
    let statusToUse = 'automatico';
    if (projectStatus === 'em espera') statusToUse = 'Em Espera';
    else if (projectStatus === 'cancelado') statusToUse = 'Cancelado';
    
    const status = calculateTaskStatus(taskData, statusToUse);
    statusCell.textContent = status;
    statusCell.className = `status status-${status.toLowerCase().replace(/\s/g, '-')}`;
}

function fillTaskDates(taskKey, taskData) {
    if (taskData) {
        document.getElementById(`${taskKey}Planned`).value = toISODateString(taskData.planned);
        document.getElementById(`${taskKey}Planned2`).value = toISODateString(taskData.planned);
        document.getElementById(`${taskKey}Start`).value = toISODateString(taskData.start);
        document.getElementById(`${taskKey}Executed`).value = toISODateString(taskData.executed);
        document.getElementById(`${taskKey}Duration`).value = taskData.duration || getDefaultDuration(taskKey);
        
        if (taskKey === 'tryout' || taskKey === 'entrega') {
            document.getElementById(`${taskKey}Number`).value = taskData.number || '';
        }
        
        if (taskKey === 'tryout') {
            document.getElementById('tryoutQuantidadeEntrada').value = taskData.quantidadeEntrada || '';
            document.getElementById('tryoutQuantidadeSaida').value = taskData.quantidadeSaida || '';
        }
    } else {
        document.getElementById(`${taskKey}Planned`).value = '';
        document.getElementById(`${taskKey}Planned2`).value = '';
        document.getElementById(`${taskKey}Start`).value = '';
        document.getElementById(`${taskKey}Executed`).value = '';
        document.getElementById(`${taskKey}Duration`).value = getDefaultDuration(taskKey);
        
        if (taskKey === 'tryout' || taskKey === 'entrega') {
            document.getElementById(`${taskKey}Number`).value = '';
        }
        
        if (taskKey === 'tryout') {
            document.getElementById('tryoutQuantidadeEntrada').value = '';
            document.getElementById('tryoutQuantidadeSaida').value = '';
        }
    }
}

function saveProject() {
    console.log('saveProject chamado - userNivel:', userNivel, 'isVisualizador:', isVisualizador, 'isLider:', isLider);
    
    if (isVisualizador) {
        alert('Você não tem permissão para salvar projetos.');
        return;
    }
    
    console.log('Permissão OK - Verificando conexão MySQL...');
    
    if (!mysqlConnected) {
        alert('Não conectado ao MySQL. Verifique a conexão antes de salvar.');
        return;
    }
    
    console.log('Conexão OK - Coletando dados do formulário...');
    
    const cliente = document.getElementById('cliente').value.trim();
    const projectName = document.getElementById('projectName').value.trim();
    const segmento = document.getElementById('segmento').value;
    const leaderId = document.getElementById('projectLeader').value;
    const codigo = document.getElementById('codigo').value.trim();
    const anviNumber = document.getElementById('anviNumber').value.trim();
    syncSelectedProjectAnvi();
    const selectedAnviId = document.getElementById('anviId').value.trim();
    const selectedAnvi = selectedAnviId ? projectAnviSuggestionMap.get(anviNumber) : null;
    const modelo = document.getElementById('modelo').value;
    const processo = document.getElementById('processo').value;
    const fase = document.getElementById('fase').value;
    const observacoes = document.getElementById('observacoes').value.trim();

    if (!cliente || !projectName || !leaderId) {
        alert('Preencha os campos obrigatórios: Cliente, Projeto e Líder.');
        return;
    }

    const statusSelectVal = document.getElementById('projectStatusSelect').value;
    let finalStatus = 'Pendente';
    let manualStatus = null;
    
    if (statusSelectVal === 'automatico') {
        manualStatus = null;
    } else if (statusSelectVal === 'em espera') {
        finalStatus = 'Em Espera';
        manualStatus = 'Em Espera';
    } else if (statusSelectVal === 'cancelado') {
        finalStatus = 'Cancelado';
        manualStatus = 'Cancelado';
    }

    const tasks = {};
    ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'].forEach(taskKey => {
        const planned = toISODateString(document.getElementById(`${taskKey}Planned`).value);
        const start = toISODateString(document.getElementById(`${taskKey}Start`).value);
        const executed = toISODateString(document.getElementById(`${taskKey}Executed`).value);
        const duration = parseInt(document.getElementById(`${taskKey}Duration`).value) || getDefaultDuration(taskKey);
        const number = (taskKey === 'tryout' || taskKey === 'entrega') ? 
            (document.getElementById(`${taskKey}Number`) ? document.getElementById(`${taskKey}Number`).value : '') : undefined;
        
        const quantidadeEntrada = (taskKey === 'tryout') ? 
            (document.getElementById('tryoutQuantidadeEntrada') ? parseInt(document.getElementById('tryoutQuantidadeEntrada').value) || 0 : 0) : undefined;
        const quantidadeSaida = (taskKey === 'tryout') ? 
            (document.getElementById('tryoutQuantidadeSaida') ? parseInt(document.getElementById('tryoutQuantidadeSaida').value) || 0 : 0) : undefined;
        
        let resources = undefined;
        if (taskKey === 'tryout') {
            resources = {
                corte: (document.getElementById('tryoutCorte') ? document.getElementById('tryoutCorte').value : ''),
                lapidacao: (document.getElementById('tryoutLapidacao') ? document.getElementById('tryoutLapidacao').value : ''),
                furacao: (document.getElementById('tryoutFuracao') ? document.getElementById('tryoutFuracao').value : ''),
                montagem: (document.getElementById('tryoutMontagem') ? document.getElementById('tryoutMontagem').value : ''),
                serigrafia: (document.getElementById('tryoutSerigrafia') ? document.getElementById('tryoutSerigrafia').value : ''),
                queima: (document.getElementById('tryoutQueima') ? document.getElementById('tryoutQueima').value : ''),
                fornos: (document.getElementById('tryoutFornos') ? document.getElementById('tryoutFornos').value : '')
            };
        } else if (taskKey === 'ferramental') {
            resources = {
                femea: toISODateString(document.getElementById('ferramentalFemea') ? document.getElementById('ferramentalFemea').value : ''),
                gabaritoFanavid: toISODateString(document.getElementById('ferramentalGabaritoFanavid') ? document.getElementById('ferramentalGabaritoFanavid').value : ''),
                gabaritoUsinado: toISODateString(document.getElementById('ferramentalGabaritoUsinado') ? document.getElementById('ferramentalGabaritoUsinado').value : ''),
                matriz: toISODateString(document.getElementById('ferramentalMatriz') ? document.getElementById('ferramentalMatriz').value : ''),
                macho: toISODateString(document.getElementById('ferramentalMacho') ? document.getElementById('ferramentalMacho').value : ''),
                template: toISODateString(document.getElementById('ferramentalTemplate') ? document.getElementById('ferramentalTemplate').value : ''),
                chapelona: toISODateString(document.getElementById('ferramentalChapelona') ? document.getElementById('ferramentalChapelona').value : ''),
                plotter: toISODateString(document.getElementById('ferramentalPlotter') ? document.getElementById('ferramentalPlotter').value : ''),
                tela: toISODateString(document.getElementById('ferramentalTela') ? document.getElementById('ferramentalTela').value : '')
            };
        }

        tasks[taskKey] = {
            planned: planned || null,
            start: start || null,
            executed: executed || null,
            duration: duration,
            number: number || null,
            quantidadeEntrada: quantidadeEntrada || null,
            quantidadeSaida: quantidadeSaida || null,
            resources: resources,
            history: currentEditingProjectId ? 
                (projects.find(p => p.id === currentEditingProjectId)?.tasks?.[taskKey]?.history || []) : []
        };
    });

    if (statusSelectVal === 'automatico') {
        finalStatus = calculateProjectStatus({ tasks });
    }

    const leader = leaders.find(l => l.id == leaderId);
    const projectData = {
        cliente, 
        projectName, 
        segmento, 
        leaderId, 
        codigo, 
        anviNumber,
        anviId: selectedAnviId || null,
        anviRevision: selectedAnvi?.revisao || null,
        sourceContext: selectedAnviId ? {
            source: 'anvi',
            anviId: selectedAnviId,
            anviNumber,
            anviRevision: selectedAnvi?.revisao || ''
        } : {
            source: 'manual',
            anviNumber
        },
        modelo, 
        processo, 
        fase, 
        observacoes,
        projectLeader: leader ? leader.name : '',
        tasks,
        manualStatus: manualStatus,
        status: finalStatus
    };

    if (currentEditingProjectId) {
        // Atualizar projeto existente
        projectData.id = currentEditingProjectId;
        const existingProject = projects.find(p => p.id === currentEditingProjectId);
        projectData.capability = saveCapabilityData(existingProject);
        
        // CRITICAL FIX: Preservar dados APQP existentes e garantir que seja um objeto
        if (existingProject && existingProject.apqp && !Array.isArray(existingProject.apqp)) {
            projectData.apqp = existingProject.apqp;
        } else {
            projectData.apqp = {};
        }
        
        // Salvar no MySQL
        saveProjectToMySQL(projectData).then(response => {
            // Atualizar array local
            const idx = projects.findIndex(p => p.id === currentEditingProjectId);
            if (idx >= 0) {
                projects[idx] = { ...projects[idx], ...projectData };
            }
            
            saveToLocalStorage();
            clearProjectForm();
            updateProjectsTable();
            updateSummary();
            document.getElementById('projectForm').style.display = 'none';
            currentEditingProjectId = null;
            
            setProjectSaveStatus('saved', 'Salvo');
        }).catch(error => {
            alert('Não foi possível salvar o projeto. ' + error);
        });
    } else {
        // Novo projeto
        const newProject = {
            ...projectData,
            createdAt: new Date().toISOString(),
            apqp: {}  // CRITICAL FIX: Inicializar apqp como objeto vazio
        };
        newProject.capability = saveCapabilityData(newProject);
        
        // Salvar no MySQL
        saveProjectToMySQL(newProject).then(response => {
            newProject.id = response.insertId;
            projects.push(newProject);
            
            saveToLocalStorage();
            clearProjectForm();
            updateProjectsTable();
            updateSummary();
            document.getElementById('projectForm').style.display = 'none';
            currentEditingProjectId = null;
            
            setProjectSaveStatus('saved', 'Salvo');
        }).catch(error => {
            alert('Não foi possível salvar o projeto. ' + error);
        });
    }
}

function editProject(id) {
    if (isVisualizador) {
        alert('Você não tem permissão para editar projetos.');
        return;
    }
    showProjectForm(id);
}

function deleteProject(id) {
    if (isVisualizador) {
        alert('Você não tem permissão para excluir projetos.');
        return;
    }
    
    if (!mysqlConnected) {
        alert('Não conectado ao MySQL. Verifique a conexão antes de excluir.');
        return;
    }
    
    if (confirm('Tem certeza que deseja excluir este projeto?')) {
        deleteProjectFromMySQL(id).then(response => {
            projects = projects.filter(p => p.id !== id);
            saveToLocalStorage();
            clearProjectForm();
            updateProjectsTable();
            updateSummary();
            alert('Projeto excluído com sucesso!');
        }).catch(error => {
            alert('Erro ao excluir projeto no MySQL: ' + error);
        });
    }
}

function clearProjectForm() {
    document.querySelectorAll('#projectForm input, #projectForm select, #projectForm textarea')
        .forEach(el => {
            if (el.tagName === 'SELECT') {
                el.selectedIndex = 0;
            } else {
                el.value = '';
            }
        });
}

// ==============================================
// FUNÇÕES DE LÍDERES (CORRIGIDAS)
// ==============================================
function showLeadersForm() {
    closeAllModals();
    document.getElementById('projectForm').style.display = 'none';
    document.getElementById('leadersForm').style.display = 'block';
    document.getElementById('chartsSection').style.display = 'none';
    
    // Resetar campos do formulário manualmente
    document.getElementById('newLeaderName').value = '';
    document.getElementById('newLeaderEmail').value = '';
    document.getElementById('newLeaderDepartment').value = '';
    
    updateLeadersList();
    document.getElementById('leadersForm').scrollTop = 0;
}

function saveLeader() {
    if (!mysqlConnected) {
        alert('Não conectado ao MySQL. Verifique a conexão antes de salvar.');
        return;
    }
    
    const nameInput = document.getElementById('newLeaderName');
    const emailInput = document.getElementById('newLeaderEmail');
    const departmentInput = document.getElementById('newLeaderDepartment');
    
    const name = nameInput.value.trim();
    const email = emailInput.value.trim();
    const department = departmentInput.value.trim();

    if (!name || !email || !department) {
        alert('Preencha todos os campos do líder.');
        return;
    }

    const leaderData = { name, email, department };

    saveLeaderToMySQL(leaderData).then(response => {
        leaderData.id = response.insertId;
        leaders.push(leaderData);
        
        updateLeaderFilter();
        updateTaskLeaderFilter();
        updateProjectLeaderSelect();
        updateLeadersList();
        
        // Resetar campos manualmente
        nameInput.value = '';
        emailInput.value = '';
        departmentInput.value = '';
        
        alert('Líder salvo com sucesso!');
    }).catch(error => {
        alert('Erro ao salvar líder no MySQL: ' + error);
    });
}

function deleteLeader(id) {
    if (!mysqlConnected) {
        alert('Não conectado ao MySQL. Verifique a conexão antes de excluir.');
        return;
    }
    
    // Verificar se o ID é válido
    if (!id || id <= 0) {
        alert('ID de líder inválido.');
        return;
    }
    
    // Verificar se existem projetos associados
    const projectsWithLeader = projects.filter(p => p.leaderId == id);
    if (projectsWithLeader.length > 0) {
        alert(`Não é possível excluir este líder pois existem ${projectsWithLeader.length} projeto(s) associado(s) a ele.`);
        return;
    }
    
    if (confirm('Tem certeza que deseja excluir este líder?')) {
        deleteLeaderFromMySQL(id).then(response => {
            // Filtrar apenas o líder com o ID específico
            const beforeCount = leaders.length;
            leaders = leaders.filter(l => l.id !== id);
            const afterCount = leaders.length;
            
            if (beforeCount === afterCount) {
                console.warn('Líder não encontrado no array local, mas foi removido do banco');
            }
            
            updateLeaderFilter();
            updateTaskLeaderFilter();
            updateProjectLeaderSelect();
            updateLeadersList();
            
            alert('Líder excluído com sucesso!');
        }).catch(error => {
            alert('Erro ao excluir líder no MySQL: ' + error);
        });
    }
}

function updateLeadersList() {
    const cont = document.getElementById('leadersListContainer');
    if (!cont) return;
    
    cont.innerHTML = '';
    
    if (!leaders || leaders.length === 0) { 
        cont.innerHTML = '<p style="text-align:center;padding:20px;">Nenhum líder cadastrado.</p>'; 
        return; 
    }
    
    // Ordenar líderes por nome
    const sortedLeaders = [...leaders].sort((a, b) => a.name.localeCompare(b.name));
    
    const ul = document.createElement('ul');
    ul.style.listStyle = 'none';
    ul.style.padding = '0';
    
    sortedLeaders.forEach(l => {
        // Garantir que o ID é um número
        const leaderId = parseInt(l.id) || 0;
        
        const li = document.createElement('li');
        li.style.padding = '12px';
        li.style.borderBottom = '1px solid #eee';
        li.style.display = 'flex';
        li.style.justifyContent = 'space-between';
        li.style.alignItems = 'center';
        li.setAttribute('data-leader-id', leaderId);
        li.innerHTML = `
            <div>
                <strong>${l.name}</strong> (${l.department})<br>
                <small>${l.email}</small>
            </div>
            <button class="btn btn-danger btn-sm" onclick="deleteLeader(${leaderId})">
                <i class="fas fa-trash"></i> Excluir
            </button>
        `;
        ul.appendChild(li);
    });
    
    cont.appendChild(ul);
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
                    reject(response.message || 'Erro desconhecido ao excluir líder');
                }
            },
            error: function(xhr, status, error) {
                reject(error);
            }
        });
    });
}

// ==============================================
// FUNÇÕES DE HISTÓRICO (manter as originais)
// ==============================================
function showHistoryModal(projectId, taskKey) {
    closeAllModals();
    const project = projects.find(p => p.id === projectId);
    if (!project) return;
    
    const task = project.tasks?.[taskKey];
    const history = task?.history || [];
    
    currentHistoryInfo = {
        projectId: projectId,
        taskKey: taskKey,
        editingIndex: null
    };
    
    const taskNames = {
        'kom': 'KOM - Kick-off Meeting',
        'ferramental': 'Ferramental',
        'cadBomFt': 'CAD+BOM+FT',
        'tryout': 'Try-out',
        'entrega': 'Entrega da Amostra',
        'psw': 'PSW',
        'handover': 'Handover'
    };
    
    const title = document.getElementById('historyModalTitle');
    title.textContent = `Histórico - ${taskNames[taskKey] || taskKey} - ${project.projectName}`;
    
    renderHistoryList();
    document.getElementById('historyFormContainer').style.display = 'none';
    document.getElementById('historyModal').style.display = 'block';
}

function renderHistoryList() {
    const content = document.getElementById('historyContent');
    const { projectId, taskKey } = currentHistoryInfo;
    const project = projects.find(p => p.id === projectId);
    if (!project) return;
    
    const task = project.tasks?.[taskKey];
    const history = task?.history || [];
    
    content.innerHTML = '';
    
    if (history.length === 0) {
        content.innerHTML = '<p style="text-align:center;padding:20px;">Nenhum histórico disponível.</p>';
        return;
    }
    
    history.forEach((item, index) => {
        const historyItem = document.createElement('div');
        historyItem.className = 'history-item';
        historyItem.innerHTML = `
            <div class="history-item-content">
                <div class="history-item-date">
                    <strong>Data:</strong> ${formatDateBR(item.date)}
                </div>
                <div class="history-item-reason">
                    <strong>Motivo:</strong> ${item.reason || ''}
                </div>
                <div class="history-item-dates">
                    <strong>De:</strong> ${formatDateBR(item.oldDate)} <strong>Para:</strong> ${formatDateBR(item.newDate)}
                </div>
            </div>
            <div class="history-item-actions">
                <button class="btn btn-primary btn-sm" onclick="editHistoryItem(${index})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="deleteHistoryItem(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        content.appendChild(historyItem);
    });
}

function editHistoryItem(index) {
    const { projectId, taskKey } = currentHistoryInfo;
    const project = projects.find(p => p.id === projectId);
    if (!project) return;
    
    const task = project.tasks?.[taskKey];
    if (!task || !task.history || index >= task.history.length) return;
    
    const historyItem = task.history[index];
    
    currentHistoryInfo.editingIndex = index;
    
    document.getElementById('historyDate').value = toISODateString(historyItem.date);
    document.getElementById('historyReason').value = historyItem.reason || '';
    document.getElementById('historyOldDate').value = toISODateString(historyItem.oldDate);
    document.getElementById('historyNewDate').value = toISODateString(historyItem.newDate);
    
    document.getElementById('historyFormContainer').style.display = 'block';
    document.getElementById('historyReason').focus();
}

function saveHistoryItem() {
    const { projectId, taskKey, editingIndex } = currentHistoryInfo;
    const project = projects.find(p => p.id === projectId);
    if (!project) return;
    
    if (!project.tasks) project.tasks = {};
    if (!project.tasks[taskKey]) project.tasks[taskKey] = {};
    if (!project.tasks[taskKey].history) project.tasks[taskKey].history = [];
    
    const date = document.getElementById('historyDate').value;
    const reason = document.getElementById('historyReason').value.trim();
    const oldDate = document.getElementById('historyOldDate').value;
    const newDate = document.getElementById('historyNewDate').value;
    
    if (!date || !reason || !oldDate || !newDate) {
        alert('Preencha todos os campos do histórico.');
        return;
    }
    
    const historyItem = {
        date: date,
        reason: reason,
        oldDate: oldDate,
        newDate: newDate
    };
    
    if (editingIndex !== null && editingIndex < project.tasks[taskKey].history.length) {
        project.tasks[taskKey].history[editingIndex] = historyItem;
    } else {
        project.tasks[taskKey].history.push(historyItem);
    }
    
    // Salvar no MySQL
    saveProjectToMySQL(project).then(() => {
        renderHistoryList();
        document.getElementById('historyFormContainer').style.display = 'none';
        updateProjectsTable();
    }).catch(error => {
        alert('Erro ao salvar histórico no MySQL: ' + error);
    });
}

function deleteHistoryItem(index) {
    if (!confirm('Tem certeza que deseja excluir este histórico?')) return;
    
    const { projectId, taskKey } = currentHistoryInfo;
    const project = projects.find(p => p.id === projectId);
    if (!project) return;
    
    const task = project.tasks?.[taskKey];
    if (!task || !task.history || index >= task.history.length) return;
    
    task.history.splice(index, 1);
    
    // Salvar no MySQL
    saveProjectToMySQL(project).then(() => {
        renderHistoryList();
        updateProjectsTable();
    }).catch(error => {
        alert('Erro ao salvar histórico no MySQL: ' + error);
    });
}

function cancelHistoryEdit() {
    document.getElementById('historyFormContainer').style.display = 'none';
}

// ==============================================
// FUNÇÕES DE REPLANEJAMENTO (manter as originais)
// ==============================================
function openRescheduleModal(projectId, taskKey) {
    closeAllModals();
    const project = projects.find(p => p.id == projectId);
    if (!project) return;
    
    const task = project.tasks?.[taskKey];
    currentRescheduleInfo = { projectId, taskKey };
    
    const taskNames = {
        'kom': 'KOM - Kick-off Meeting',
        'ferramental': 'Ferramental',
        'cadBomFt': 'CAD+BOM+FT',
        'tryout': 'Try-out',
        'entrega': 'Entrega da Amostra',
        'psw': 'PSW',
        'handover': 'Handover'
    };
    
    document.getElementById('rescheduleModalTitle').textContent = `Replanejar - ${taskNames[taskKey] || taskKey}`;
    document.getElementById('rescheduleTaskInfo').textContent = `Projeto: ${project.projectName} | Tarefa: ${taskNames[taskKey] || taskKey}`;
    document.getElementById('currentDate').value = (task && task.planned) ? formatDateBR(task.planned) : 'Não definida';
    document.getElementById('newDate').value = toISODateString(task?.planned);
    document.getElementById('rescheduleReason').value = '';
    document.getElementById('rescheduleModal').style.display = 'block';
}

function saveReschedule() {
    if (!currentRescheduleInfo) return;
    const { projectId, taskKey } = currentRescheduleInfo;
    
    const newDate = toISODateString(document.getElementById('newDate').value);
    const reason = document.getElementById('rescheduleReason').value.trim();

    if (!newDate || !reason) {
        alert('Preencha a nova data e o motivo do replanejamento.');
        return;
    }

    const project = projects.find(p => p.id == projectId);
    if (!project) return;
    
    if (!project.tasks) project.tasks = {};
    if (!project.tasks[taskKey]) project.tasks[taskKey] = {};
    
    if (!project.tasks[taskKey].history) project.tasks[taskKey].history = [];
    project.tasks[taskKey].history.push({
        date: new Date().toISOString().split('T')[0],
        reason,
        oldDate: project.tasks[taskKey].planned,
        newDate: newDate
    });

    project.tasks[taskKey].planned = newDate;
    
    if (!project.manualStatus) {
        project.status = calculateProjectStatus(project);
    }
    
    // Salvar no MySQL
    saveProjectToMySQL(project).then(() => {
        updateProjectsTable();
        updateSummary();
        document.getElementById('rescheduleModal').style.display = 'none';
        currentRescheduleInfo = null;
        alert('Replanejamento salvo com sucesso!');
    }).catch(error => {
        alert('Erro ao salvar replanejamento no MySQL: ' + error);
    });
}

// ==============================================
// FUNÇÕES DE EXPORT/IMPORT EXCEL (manter as originais)
// ==============================================
function exportToExcel() {
    const data = projects.map(p => {
        const row = {
            'ID': p.id,
            'Cliente': p.cliente,
            'Projeto': p.projectName,
            'Segmento': p.segmento,
            'Líder': p.projectLeader,
            'Código': p.codigo,
            'ANVI': p.anviNumber || '',
            'Modelo': p.modelo,
            'Processo': p.processo,
            'Fase': p.fase,
            'Status': p.status,
            'Observações': p.observacoes
        };

        ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'].forEach(taskKey => {
            const task = p.tasks?.[taskKey];
            row[`${taskKey.toUpperCase()} Status`] = calculateTaskStatus(task, p.status);
            row[`${taskKey.toUpperCase()} Planejado`] = formatDateBR(task?.planned);
            row[`${taskKey.toUpperCase()} Duração (dias)`] = task?.duration || getDefaultDuration(taskKey);
            row[`${taskKey.toUpperCase()} Início`] = formatDateBR(task?.start);
            row[`${taskKey.toUpperCase()} Executado`] = formatDateBR(task?.executed);
            if (taskKey === 'tryout' || taskKey === 'entrega') row[`${taskKey.toUpperCase()} Número`] = task?.number || '';
            
            if (taskKey === 'tryout') {
                row[`${taskKey.toUpperCase()} Quant. Entrada`] = task?.quantidadeEntrada || '';
                row[`${taskKey.toUpperCase()} Quant. Saída`] = task?.quantidadeSaida || '';
            }
            
            if (taskKey === 'tryout') {
                row[`${taskKey.toUpperCase()} Corte`] = task?.resources?.corte || '';
                row[`${taskKey.toUpperCase()} Lapidação`] = task?.resources?.lapidacao || '';
                row[`${taskKey.toUpperCase()} Furação/Rec`] = task?.resources?.furacao || '';
                row[`${taskKey.toUpperCase()} Montagem`] = task?.resources?.montagem || '';
                row[`${taskKey.toUpperCase()} Serigrafia`] = task?.resources?.serigrafia || '';
                row[`${taskKey.toUpperCase()} Queima`] = task?.resources?.queima || '';
                row[`${taskKey.toUpperCase()} Fornos`] = task?.resources?.fornos || '';
            } else if (taskKey === 'ferramental') {
                row[`${taskKey.toUpperCase()} Fêmea`] = formatDateBR(task?.resources?.femea) || '';
                row[`${taskKey.toUpperCase()} Gabarito Fanavid`] = formatDateBR(task?.resources?.gabaritoFanavid) || '';
                row[`${taskKey.toUpperCase()} Gabarito Usinado`] = formatDateBR(task?.resources?.gabaritoUsinado) || '';
                row[`${taskKey.toUpperCase()} Matriz`] = formatDateBR(task?.resources?.matriz) || '';
                row[`${taskKey.toUpperCase()} Macho`] = formatDateBR(task?.resources?.macho) || '';
                row[`${taskKey.toUpperCase()} Template`] = formatDateBR(task?.resources?.template) || '';
                row[`${taskKey.toUpperCase()} Chapelona`] = formatDateBR(task?.resources?.chapelona) || '';
                row[`${taskKey.toUpperCase()} Plotter`] = formatDateBR(task?.resources?.plotter) || '';
                row[`${taskKey.toUpperCase()} Tela`] = formatDateBR(task?.resources?.tela) || '';
            }
        });

        return row;
    });

    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Projetos');
    XLSX.writeFile(wb, 'projetos.xlsx');
}

function importFromExcel() {
    closeAllModals();
    document.getElementById('excelImportModal').style.display = 'block';
}

function handleExcelImport() {
    const fileInput = document.getElementById('excelFile');
    const overwrite = document.getElementById('importOverwrite').checked;
    
    if (!fileInput.files.length) {
        alert('Selecione um arquivo Excel para importar.');
        return;
    }

    const file = fileInput.files[0];
    const reader = new FileReader();
    
    reader.onload = function(e) {
        try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            const jsonData = XLSX.utils.sheet_to_json(worksheet);
            
            if (overwrite) {
                projects = [];
            }
            
            // Processar cada linha
            const promises = [];
            jsonData.forEach(row => {
                const project = {
                    cliente: row.Cliente || '',
                    projectName: row.Projeto || '',
                    segmento: row.Segmento || '',
                    leaderId: leaders.find(l => l.name === row.Líder)?.id || null,
                    projectLeader: row.Líder || '',
                    codigo: row.Código || '',
                    anviNumber: row.ANVI || '',
                    modelo: row.Modelo || '',
                    processo: row.Processo || '',
                    fase: row.Fase || '',
                    status: row.Status || 'Pendente',
                    observacoes: row.Observações || '',
                    tasks: {},
                    createdAt: new Date().toISOString()
                };
                
                ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'].forEach(taskKey => {
                    const task = {
                        planned: parseExcelDate(row[`${taskKey.toUpperCase()} Planejado`]),
                        start: parseExcelDate(row[`${taskKey.toUpperCase()} Início`]),
                        executed: parseExcelDate(row[`${taskKey.toUpperCase()} Executado`]),
                        duration: row[`${taskKey.toUpperCase()} Duração (dias)`] || getDefaultDuration(taskKey),
                        history: []
                    };
                    
                    if (taskKey === 'tryout' || taskKey === 'entrega') {
                        task.number = row[`${taskKey.toUpperCase()} Número`] || '';
                    }
                    
                    if (taskKey === 'tryout') {
                        task.quantidadeEntrada = row[`${taskKey.toUpperCase()} Quant. Entrada`] || 0;
                        task.quantidadeSaida = row[`${taskKey.toUpperCase()} Quant. Saída`] || 0;
                    }
                    
                    if (taskKey === 'tryout') {
                        task.resources = {
                            corte: row[`${taskKey.toUpperCase()} Corte`] || '',
                            lapidacao: row[`${taskKey.toUpperCase()} Lapidação`] || '',
                            furacao: row[`${taskKey.toUpperCase()} Furação/Rec`] || '',
                            montagem: row[`${taskKey.toUpperCase()} Montagem`] || '',
                            serigrafia: row[`${taskKey.toUpperCase()} Serigrafia`] || '',
                            queima: row[`${taskKey.toUpperCase()} Queima`] || '',
                            fornos: row[`${taskKey.toUpperCase()} Fornos`] || ''
                        };
                    } else if (taskKey === 'ferramental') {
                        task.resources = {
                            femea: parseExcelDate(row[`${taskKey.toUpperCase()} Fêmea`]),
                            gabaritoFanavid: parseExcelDate(row[`${taskKey.toUpperCase()} Gabarito Fanavid`]),
                            gabaritoUsinado: parseExcelDate(row[`${taskKey.toUpperCase()} Gabarito Usinado`]),
                            matriz: parseExcelDate(row[`${taskKey.toUpperCase()} Matriz`]),
                            macho: parseExcelDate(row[`${taskKey.toUpperCase()} Macho`]),
                            template: parseExcelDate(row[`${taskKey.toUpperCase()} Template`]),
                            chapelona: parseExcelDate(row[`${taskKey.toUpperCase()} Chapelona`]),
                            plotter: parseExcelDate(row[`${taskKey.toUpperCase()} Plotter`]),
                            tela: parseExcelDate(row[`${taskKey.toUpperCase()} Tela`])
                        };
                    }
                    
                    project.tasks[taskKey] = task;
                });
                
                // Salvar no MySQL
                promises.push(saveProjectToMySQL(project).then(response => {
                    project.id = response.insertId;
                    projects.push(project);
                }));
            });
            
            Promise.all(promises).then(() => {
                updateProjectsTable();
                updateSummary();
                alert(`Importação concluída. ${jsonData.length} projetos importados.`);
                document.getElementById('excelImportModal').style.display = 'none';
            }).catch(error => {
                alert('Erro ao importar projetos no MySQL: ' + error);
            });
        } catch (error) {
            console.error('Erro na importação:', error);
            alert('Erro ao importar arquivo. Verifique o formato.');
        }
    };
    
    reader.readAsArrayBuffer(file);
}

function parseExcelDate(excelDate) {
    if (!excelDate || excelDate === '-') return null;
    
    if (typeof excelDate === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(excelDate)) {
        return excelDate;
    }
    
    let date;
    if (typeof excelDate === 'number') {
        const excelEpoch = new Date(1900, 0, 1);
        date = new Date(excelEpoch.getTime() + (excelDate - 1) * 86400000);
    } else if (excelDate instanceof Date) {
        date = excelDate;
    } else if (typeof excelDate === 'string') {
        const parts = excelDate.split('/');
        if (parts.length === 3) {
            date = new Date(parts[2], parts[1] - 1, parts[0]);
        } else {
            date = new Date(excelDate);
        }
    } else {
        return null;
    }
    
    if (isNaN(date.getTime())) return null;
    return toISODateString(date);
}

// ==============================================

