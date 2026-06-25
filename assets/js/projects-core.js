// FUNÇÕES AUXILIARES DE DATA (manter todas as originais)
// ==============================================
function toISODateString(date) {
    if (!date) return '';
    
    if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
        return date;
    }
    
    const d = new Date(date);
    if (isNaN(d.getTime())) return '';
    
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
}

function toDateOnly(d) {
    if (!d) return null;
    
    if (typeof d === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(d)) {
        const parts = d.split('-');
        return new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    }
    
    const dt = new Date(d);
    return new Date(dt.getFullYear(), dt.getMonth(), dt.getDate());
}

function today() { 
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
}

function formatDateBR(d) {
    if (!d) return '-';
    const dt = toDateOnly(d);
    if (!dt || isNaN(dt.getTime())) return '-';
    return dt.toLocaleDateString('pt-BR');
}

function compareDates(dateA, dateB) {
    const a = toDateOnly(dateA);
    const b = toDateOnly(dateB);
    if (!a || !b) return 0;
    return a.getTime() - b.getTime();
}

function getMonthName(monthIndex) {
    const months = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                   'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    return months[monthIndex];
}

function getWeekNumber(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() + 3 - (d.getDay() + 6) % 7);
    const week1 = new Date(d.getFullYear(), 0, 4);
    const weekNum = 1 + Math.round(((d.getTime() - week1.getTime()) / 86400000 - 3 + (week1.getDay() + 6) % 7) / 7);
    return weekNum;
}

// ==============================================
// FUNÇÕES DE FILTRO E TABELA (manter as originais)
// ==============================================
function getFilteredProjects() {
    const idFilter = (document.getElementById('idFilter').value || '').toLowerCase();
    const projectFilter = (document.getElementById('projectFilter').value || '').toLowerCase();
    const segmentoFilter = document.getElementById('segmentoFilter').value;
    const leaderFilter = document.getElementById('leaderFilter').value;
    const search = (document.getElementById('search').value || '').toLowerCase();
    
    const statusFilterElements = document.getElementById('statusFilter').selectedOptions;
    const statusFilter = Array.from(statusFilterElements).map(option => option.value);
    
    const periodFilterFrom = document.getElementById('periodFilterFrom').value;
    const periodFilterTo = document.getElementById('periodFilterTo').value;
    
    const dateType = document.getElementById('dateFilterType').value;
    const dateFrom = document.getElementById('dateFilterFrom').value;
    const dateTo = document.getElementById('dateFilterTo').value;
    
    const taskStatusFilterElements = document.getElementById('taskStatusFilter').selectedOptions;
    const taskStatusFilter = Array.from(taskStatusFilterElements).map(option => option.value);
    
    const taskSegmentoFilter = document.getElementById('taskSegmentoFilter').value;
    const taskLeaderFilter = document.getElementById('taskLeaderFilter').value;

    let filteredProjects = projects.filter(p => {
        if (idFilter && !p.id.toString().includes(idFilter)) return false;
        if (projectFilter && !(p.projectName || '').toLowerCase().includes(projectFilter)) return false;
        if (segmentoFilter !== 'todos' && p.segmento !== segmentoFilter) return false;
        if (leaderFilter !== 'todos' && p.leaderId != leaderFilter) return false;
        
        if (statusFilter.length > 0 && !statusFilter.includes(p.status)) return false;
        
        if (periodFilterFrom || periodFilterTo) {
            const projectDate = p.createdAt ? toDateOnly(p.createdAt) : null;
            if (!projectDate) return false;
            
            const fromDateObj = periodFilterFrom ? toDateOnly(periodFilterFrom) : null;
            const toDateObj = periodFilterTo ? toDateOnly(periodFilterTo) : null;
            
            if (fromDateObj && compareDates(projectDate, fromDateObj) < 0) return false;
            if (toDateObj && compareDates(projectDate, toDateObj) > 0) return false;
        }
        
        if (search) {
            const hay = `${p.cliente} ${p.projectName} ${p.codigo} ${p.anviNumber} ${p.modelo} ${p.observacoes}`.toLowerCase();
            if (!hay.includes(search)) return false;
        }
        return true;
    });

    if (dateType && dateType !== 'todos' && (dateFrom || dateTo || taskStatusFilter.length > 0 || taskSegmentoFilter !== 'todos' || taskLeaderFilter !== 'todos')) {
        filteredProjects = filteredProjects.filter(p => {
            const task = p.tasks?.[dateType];
            if (!task) return false;
            
            if (taskStatusFilter.length > 0) {
                const taskStatus = calculateTaskStatus(task, p.status);
                if (!taskStatusFilter.includes(taskStatus)) return false;
            }
            
            if (taskSegmentoFilter !== 'todos' && p.segmento !== taskSegmentoFilter) return false;
            
            if (taskLeaderFilter !== 'todos' && p.leaderId != taskLeaderFilter) return false;
            
            let dateInRange = false;
            const fromDateObj = dateFrom ? toDateOnly(dateFrom) : null;
            const toDateObj = dateTo ? toDateOnly(dateTo) : null;
            
            if (task.planned) {
                const plannedDate = toDateOnly(task.planned);
                if ((!fromDateObj || compareDates(plannedDate, fromDateObj) >= 0) && 
                    (!toDateObj || compareDates(plannedDate, toDateObj) <= 0)) {
                    dateInRange = true;
                }
            }
            
            if ((dateFrom || dateTo) && !dateInRange) return false;
            
            return true;
        });
    }
    
    return filteredProjects;
}

function updateProjectsTable() {
    const tbody = document.getElementById('projectsTableBody');
    tbody.innerHTML = '';
    const list = getFilteredProjects();
    
    if (!list.length) { 
        tbody.innerHTML = '<tr><td colspan="65" style="text-align:center;padding:18px">Nenhum projeto encontrado.</td></tr>'; 
        return; 
    }

    list.forEach(p => {
        const komStatus = calculateTaskStatus(p.tasks?.kom, p.status);
        const ferramentalStatus = calculateTaskStatus(p.tasks?.ferramental, p.status);
        const cadBomFtStatus = calculateTaskStatus(p.tasks?.cadBomFt, p.status);
        const tryoutStatus = calculateTaskStatus(p.tasks?.tryout, p.status);
        const entregaStatus = calculateTaskStatus(p.tasks?.entrega, p.status);
        const pswStatus = calculateTaskStatus(p.tasks?.psw, p.status);
        const handoverStatus = calculateTaskStatus(p.tasks?.handover, p.status);
        
        let capabilitySummary = '-';
        if (p.capability && p.capability.characteristics && p.capability.characteristics.length > 0) {
            const totalChars = p.capability.characteristics.length;
            const capableChars = p.capability.characteristics.filter(c => c.stats?.cpk >= 1.33).length;
            const avgCpk = p.capability.characteristics.reduce((sum, c) => sum + (c.stats?.cpk || 0), 0) / totalChars;
            capabilitySummary = `${capableChars}/${totalChars} capaz (Cpk médio: ${avgCpk.toFixed(2)})`;
        }
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${p.id}</td>
            <td>${p.cliente || '-'}</td>
            <td>${p.projectName || '-'}</td>
            <td>${p.segmento || '-'}</td>
            <td>${p.projectLeader || '-'}</td>
            <td>${p.codigo || '-'}</td>
            <td>${p.anviNumber || '-'}</td>
            <td>${p.modelo || '-'}</td>
            <td>${p.processo || '-'}</td>
            <td>${p.fase || '-'}</td>
            <td><span class="status status-${p.status.toLowerCase().replace(/\s/g, '-')}">${p.status}</span></td>
            <td>${p.observacoes || '-'}</td>

            <!-- KOM -->
            <td class="column-group">${renderStatusCell(komStatus, p.tasks?.kom?.history, 'kom', p.id)}</td>
            <td>${formatDateBR(p.tasks?.kom?.planned)}</td>
            <td class="task-duration-cell">${p.tasks?.kom?.duration || 1} <span class="duration-unit">dias</span></td>
            <td>${formatDateBR(p.tasks?.kom?.start)}</td>
            <td>${formatDateBR(p.tasks?.kom?.executed)}</td>

            <!-- FERRAMENTAL -->
            <td class="column-group">${renderStatusCell(ferramentalStatus, p.tasks?.ferramental?.history, 'ferramental', p.id)}</td>
            <td>${formatDateBR(p.tasks?.ferramental?.planned)}</td>
            <td class="task-duration-cell">${p.tasks?.ferramental?.duration || 5} <span class="duration-unit">dias</span></td>
            <td>${formatDateBR(p.tasks?.ferramental?.start)}</td>
            <td>${formatDateBR(p.tasks?.ferramental?.executed)}</td>
            
            <!-- Recursos do Ferramental -->
            <td>${formatDateBR(p.tasks?.ferramental?.resources?.femea) || '-'}</td>
            <td>${formatDateBR(p.tasks?.ferramental?.resources?.gabaritoFanavid) || '-'}</td>
            <td>${formatDateBR(p.tasks?.ferramental?.resources?.gabaritoUsinado) || '-'}</td>
            <td>${formatDateBR(p.tasks?.ferramental?.resources?.matriz) || '-'}</td>
            <td>${formatDateBR(p.tasks?.ferramental?.resources?.macho) || '-'}</td>
            <td>${formatDateBR(p.tasks?.ferramental?.resources?.template) || '-'}</td>
            <td>${formatDateBR(p.tasks?.ferramental?.resources?.chapelona) || '-'}</td>
            <td>${formatDateBR(p.tasks?.ferramental?.resources?.plotter) || '-'}</td>
            <td>${formatDateBR(p.tasks?.ferramental?.resources?.tela) || '-'}</td>

            <!-- CAD+BOM+FT -->
            <td class="column-group">${renderStatusCell(cadBomFtStatus, p.tasks?.cadBomFt?.history, 'cadBomFt', p.id)}</td>
            <td>${formatDateBR(p.tasks?.cadBomFt?.planned)}</td>
            <td class="task-duration-cell">${p.tasks?.cadBomFt?.duration || 3} <span class="duration-unit">dias</span></td>
            <td>${formatDateBR(p.tasks?.cadBomFt?.start)}</td>
            <td>${formatDateBR(p.tasks?.cadBomFt?.executed)}</td>

            <!-- TRY-OUT -->
            <td class="column-group">${renderStatusCell(tryoutStatus, p.tasks?.tryout?.history, 'tryout', p.id)}</td>
            <td>${p.tasks?.tryout?.quantidadeEntrada || '-'}</td>
            <td>${p.tasks?.tryout?.quantidadeSaida || '-'}</td>
            <td>${p.tasks?.tryout?.number || '-'}</td>
            <td>${formatDateBR(p.tasks?.tryout?.planned)}</td>
            <td class="task-duration-cell">${p.tasks?.tryout?.duration || 3} <span class="duration-unit">dias</span></td>
            <td>${formatDateBR(p.tasks?.tryout?.start)}</td>
            <td>${formatDateBR(p.tasks?.tryout?.executed)}</td>
            
            <!-- Recursos do Try-out -->
            <td>${p.tasks?.tryout?.resources?.corte || '-'}</td>
            <td>${p.tasks?.tryout?.resources?.lapidacao || '-'}</td>
            <td>${p.tasks?.tryout?.resources?.furacao || '-'}</td>
            <td>${p.tasks?.tryout?.resources?.montagem || '-'}</td>
            <td>${p.tasks?.tryout?.resources?.serigrafia || '-'}</td>
            <td>${p.tasks?.tryout?.resources?.queima || '-'}</td>
            <td>${p.tasks?.tryout?.resources?.fornos || '-'}</td>

            <!-- ENTREGA -->
            <td class="column-group">${renderStatusCell(entregaStatus, p.tasks?.entrega?.history, 'entrega', p.id)}</td>
            <td>${p.tasks?.entrega?.number || '-'}</td>
            <td>${formatDateBR(p.tasks?.entrega?.planned)}</td>
            <td class="task-duration-cell">${p.tasks?.entrega?.duration || 1} <span class="duration-unit">dias</span></td>
            <td>${formatDateBR(p.tasks?.entrega?.start)}</td>
            <td>${formatDateBR(p.tasks?.entrega?.executed)}</td>

            <!-- PSW -->
            <td class="column-group">${renderStatusCell(pswStatus, p.tasks?.psw?.history, 'psw', p.id)}</td>
            <td>${formatDateBR(p.tasks?.psw?.planned)}</td>
            <td class="task-duration-cell">${p.tasks?.psw?.duration || 1} <span class="duration-unit">dias</span></td>
            <td>${formatDateBR(p.tasks?.psw?.start)}</td>
            <td>${formatDateBR(p.tasks?.psw?.executed)}</td>

            <!-- HANDOVER -->
            <td class="column-group">${renderStatusCell(handoverStatus, p.tasks?.handover?.history, 'handover', p.id)}</td>
            <td>${formatDateBR(p.tasks?.handover?.planned)}</td>
            <td class="task-duration-cell">${p.tasks?.handover?.duration || 1} <span class="duration-unit">dias</span></td>
            <td>${formatDateBR(p.tasks?.handover?.start)}</td>
            <td>${formatDateBR(p.tasks?.handover?.executed)}</td>

            <!-- CAPABILIDADE -->
            <td class="column-group">
                <button class="btn btn-info btn-sm" onclick="showCapabilityForProject(${p.id})">
                    <i class="fas fa-chart-line"></i> ${capabilitySummary}
                </button>
            </td>

            <!-- AÇÕES -->
            <td class="column-group">
                <button class="btn btn-primary btn-sm" onclick="editProject(${p.id})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-danger btn-sm" onclick="deleteProject(${p.id})"><i class="fas fa-trash"></i></button>
                <button class="btn btn-chart btn-sm" onclick="showTimeline(${p.id})"><i class="fas fa-calendar-alt"></i> Cronograma</button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    updateTaskStatusCount();
    countFilteredProjectsByTask();
}

function renderStatusCell(status, history, taskKey, projectId) {
    const historyCount = history ? history.length : 0;
    const project = projects.find(p => p.id === projectId);
    const apqpBadge = project ? getApqpBadgeHtml(project, taskKey) : '';
    return `
        <span class="status status-${status.toLowerCase().replace(/\s/g, '-')}">${status}</span>
        <button class="history-badge" onclick="showHistoryModal(${projectId}, '${taskKey}')">${historyCount}</button>
        <button class="reschedule-btn" onclick="openRescheduleModal(${projectId}, '${taskKey}')"><i class="fas fa-calendar-alt"></i></button>
        ${apqpBadge}
    `;
}

function updateSummary() {
    const list = getFilteredProjects();
    document.getElementById('totalProjects').innerText = list.length;
    document.getElementById('onTimeProjects').innerText = list.filter(p => p.status === "No Prazo").length;
    document.getElementById('delayedProjects').innerText = list.filter(p => p.status === "Atrasado").length;
    document.getElementById('completedProjects').innerText = list.filter(p => p.status === "Concluído").length;
    document.getElementById('onHoldProjects').innerText = list.filter(p => p.status === "Em Espera").length;
    document.getElementById('cancelledProjects').innerText = list.filter(p => p.status === "Cancelado").length;
    document.getElementById('inProgressProjects').innerText = list.filter(p => p.status === "Em Andamento").length;
    document.getElementById('pendingProjects').innerText = list.filter(p => p.status === "Pendente").length;
    document.getElementById('totalLeaders').innerText = leaders.length;
    
    const tasksEfficiency = calculateTasksEfficiency(list);
    const projectsEfficiency = calculateProjectsEfficiency(list);
    
    document.getElementById('tasksEfficiency').innerText = `${tasksEfficiency.toFixed(1)}%`;
    document.getElementById('projectsEfficiency').innerText = `${projectsEfficiency.toFixed(1)}%`;
}

function updateLeaderFilter() {
    const sel = document.getElementById('leaderFilter');
    const oldVal = sel.value;
    sel.innerHTML = '<option value="todos">Todos os Líderes</option>';
    leaders.forEach(l => sel.innerHTML += `<option value="${l.id}">${l.name}</option>`);
    if (oldVal) sel.value = oldVal;
}

function updateTaskLeaderFilter() {
    const sel = document.getElementById('taskLeaderFilter');
    const oldVal = sel.value;
    sel.innerHTML = '<option value="todos">Todos os Líderes</option>';
    leaders.forEach(l => sel.innerHTML += `<option value="${l.id}">${l.name}</option>`);
    if (oldVal) sel.value = oldVal;
}

function updateProjectLeaderSelect() {
    const sel = document.getElementById('projectLeader');
    const oldVal = sel.value;
    sel.innerHTML = '<option value="">Selecione</option>';
    leaders.forEach(l => sel.innerHTML += `<option value="${l.id}">${l.name}</option>`);
    if (oldVal) sel.value = oldVal;
}

function updateLeadersList() {
    const cont = document.getElementById('leadersListContainer');
    cont.innerHTML = '';
    if (!leaders.length) { 
        cont.innerHTML = '<p>Nenhum líder cadastrado.</p>'; 
        return; 
    }
    
    const ul = document.createElement('ul');
    ul.style.listStyle = 'none';
    ul.style.padding = '0';
    
    leaders.forEach(l => {
        const li = document.createElement('li');
        li.style.padding = '12px';
        li.style.borderBottom = '1px solid #eee';
        li.style.display = 'flex';
        li.style.justifyContent = 'space-between';
        li.style.alignItems = 'center';
        li.innerHTML = `
            <div>
                <strong>${l.name}</strong> (${l.department})<br>
                <small>${l.email}</small>
            </div>
            <button class="btn btn-danger btn-sm" onclick="deleteLeader(${l.id})"><i class="fas fa-trash"></i></button>
        `;
        ul.appendChild(li);
    });
    
    cont.appendChild(ul);
}

function updateTaskStatusCount() {
    const taskStatusFilterElements = document.getElementById('taskStatusFilter').selectedOptions;
    const taskStatusFilter = Array.from(taskStatusFilterElements).map(option => option.value);
    
    const dateType = document.getElementById('dateFilterType').value;
    const dateFrom = document.getElementById('dateFilterFrom').value;
    const dateTo = document.getElementById('dateFilterTo').value;
    const taskSegmentoFilter = document.getElementById('taskSegmentoFilter').value;
    const taskLeaderFilter = document.getElementById('taskLeaderFilter').value;
    
    if (taskStatusFilter.length === 0 && dateType === 'todos' && !dateFrom && !dateTo && taskSegmentoFilter === 'todos' && taskLeaderFilter === 'todos') {
        document.getElementById('taskStatusCount').style.display = 'none';
        return;
    }
    
    let count = 0;
    
    if (dateType && dateType !== 'todos') {
        projects.forEach(p => {
            const task = p.tasks?.[dateType];
            if (!task) return;
            
            if (taskStatusFilter.length > 0) {
                const taskStatus = calculateTaskStatus(task, p.status);
                if (!taskStatusFilter.includes(taskStatus)) return;
            }
            
            if (taskSegmentoFilter !== 'todos' && p.segmento !== taskSegmentoFilter) return;
            
            if (taskLeaderFilter !== 'todos' && p.leaderId != taskLeaderFilter) return;
            
            let dateInRange = false;
            const fromDateObj = dateFrom ? toDateOnly(dateFrom) : null;
            const toDateObj = dateTo ? toDateOnly(dateTo) : null;
            
            if (task.planned) {
                const plannedDate = toDateOnly(task.planned);
                if ((!fromDateObj || compareDates(plannedDate, fromDateObj) >= 0) && 
                    (!toDateObj || compareDates(plannedDate, toDateObj) <= 0)) {
                    dateInRange = true;
                }
            }

            if ((dateFrom || dateTo) && !dateInRange) return;
            
            count++;
        });
    }
    
    document.getElementById('taskStatusCountValue').textContent = count;
    document.getElementById('taskStatusCount').style.display = 'inline-block';
}

function countFilteredProjectsByTask() {
    const taskType = document.getElementById('dateFilterType').value;
    const taskSegmento = document.getElementById('taskSegmentoFilter').value;
    const taskLeader = document.getElementById('taskLeaderFilter').value;
    const dateFrom = document.getElementById('dateFilterFrom').value;
    const dateTo = document.getElementById('dateFilterTo').value;
    
    const taskStatusFilterElements = document.getElementById('taskStatusFilter').selectedOptions;
    const taskStatusFilter = Array.from(taskStatusFilterElements).map(option => option.value);
    
    let totalByTaskType = 0;
    let totalByStatus = 0;
    let totalBySegment = 0;
    let totalByLeader = 0;
    
    const hasAnyFilter = taskType !== 'todos' || 
                        taskSegmento !== 'todos' || 
                        taskLeader !== 'todos' || 
                        dateFrom || 
                        dateTo || 
                        taskStatusFilter.length > 0;
    
    if (!hasAnyFilter) {
        document.getElementById('taskFilterCountContainer').style.display = 'none';
        return;
    }
    
    document.getElementById('taskFilterCountContainer').style.display = 'flex';
    
    projects.forEach(project => {
        let matchesTaskType = false;
        let matchesStatus = false;
        let matchesSegment = false;
        let matchesLeader = false;
        
        if (taskType !== 'todos') {
            const task = project.tasks?.[taskType];
            if (task) {
                matchesTaskType = true;
                
                if (taskStatusFilter.length > 0) {
                    const taskStatus = calculateTaskStatus(task, project.status);
                    if (taskStatusFilter.includes(taskStatus)) {
                        matchesStatus = true;
                    }
                } else {
                    matchesStatus = true;
                }
                
                if (taskSegmento !== 'todos') {
                    if (project.segmento === taskSegmento) {
                        matchesSegment = true;
                    }
                } else {
                    matchesSegment = true;
                }
                
                if (taskLeader !== 'todos') {
                    if (project.leaderId == taskLeader) {
                        matchesLeader = true;
                    }
                } else {
                    matchesLeader = true;
                }
                
                if (dateFrom || dateTo) {
                    let dateInRange = false;
                    const fromDateObj = dateFrom ? toDateOnly(dateFrom) : null;
                    const toDateObj = dateTo ? toDateOnly(dateTo) : null;
                    
                    if (task.planned) {
                        const plannedDate = toDateOnly(task.planned);
                        if ((!fromDateObj || compareDates(plannedDate, fromDateObj) >= 0) && 
                            (!toDateObj || compareDates(plannedDate, toDateObj) <= 0)) {
                            dateInRange = true;
                        }
                    }
                    
                    if (!dateInRange) {
                        matchesTaskType = false;
                    }
                }
            }
        } else {
            const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
            let hasAnyTask = false;
            
            taskKeys.forEach(key => {
                const task = project.tasks?.[key];
                if (task) {
                    hasAnyTask = true;
                    
                    if (taskStatusFilter.length > 0) {
                        const taskStatus = calculateTaskStatus(task, project.status);
                        if (taskStatusFilter.includes(taskStatus)) {
                            matchesStatus = true;
                        }
                    } else {
                        matchesStatus = true;
                    }
                }
            });
            
            if (hasAnyTask) {
                matchesTaskType = true;
            }
            
            if (taskSegmento !== 'todos') {
                if (project.segmento === taskSegmento) {
                    matchesSegment = true;
                }
            } else {
                matchesSegment = true;
            }
            
            if (taskLeader !== 'todos') {
                if (project.leaderId == taskLeader) {
                    matchesLeader = true;
                }
            } else {
                matchesLeader = true;
            }
        }
        
        if (matchesTaskType) totalByTaskType++;
        if (matchesStatus) totalByStatus++;
        if (matchesSegment) totalBySegment++;
        if (matchesLeader) totalByLeader++;
    });
    
    document.getElementById('taskFilterCountTotal').textContent = totalByTaskType;
    document.getElementById('taskFilterCountByStatus').textContent = totalByStatus;
    document.getElementById('taskFilterCountBySegment').textContent = totalBySegment;
    document.getElementById('taskFilterCountByLeader').textContent = totalByLeader;
}

function selectAllStatuses() {
    const select = document.getElementById('statusFilter');
    for (let i = 0; i < select.options.length; i++) {
        select.options[i].selected = true;
    }
    updateProjectsTable();
    updateSummary();
}

function clearAllStatuses() {
    const select = document.getElementById('statusFilter');
    for (let i = 0; i < select.options.length; i++) {
        select.options[i].selected = false;
    }
    updateProjectsTable();
    updateSummary();
}

function selectAllTaskStatuses() {
    const select = document.getElementById('taskStatusFilter');
    for (let i = 0; i < select.options.length; i++) {
        select.options[i].selected = true;
    }
}

function clearAllTaskStatuses() {
    const select = document.getElementById('taskStatusFilter');
    for (let i = 0; i < select.options.length; i++) {
        select.options[i].selected = false;
    }
}

function selectAllChartTaskStatuses() {
    const select = document.getElementById('chartTaskStatus');
    for (let i = 0; i < select.options.length; i++) {
        select.options[i].selected = true;
    }
}

function clearAllChartTaskStatuses() {
    const select = document.getElementById('chartTaskStatus');
    for (let i = 0; i < select.options.length; i++) {
        select.options[i].selected = false;
    }
}

function clearAllFilters() {
    document.getElementById('idFilter').value = '';
    document.getElementById('projectFilter').value = '';
    document.getElementById('segmentoFilter').value = 'todos';
    document.getElementById('leaderFilter').value = 'todos';
    document.getElementById('search').value = '';
    document.getElementById('periodFilterFrom').value = '';
    document.getElementById('periodFilterTo').value = '';
    clearAllStatuses();
    
    document.getElementById('dateFilterType').value = 'todos';
    document.getElementById('taskSegmentoFilter').value = 'todos';
    document.getElementById('taskLeaderFilter').value = 'todos';
    document.getElementById('dateFilterFrom').value = '';
    document.getElementById('dateFilterTo').value = '';
    clearAllTaskStatuses();
    
    updateProjectsTable();
    updateSummary();
}

// ==============================================
// FUNÇÕES DE STATUS - CORRIGIDAS
// ==============================================
function calculateTaskStatus(task, projectStatus = null) {
    if (projectStatus === 'Cancelado' || projectStatus === 'Em Espera') {
        return projectStatus;
    }
    
    if (!task) return 'Pendente';
    
    // Se a tarefa foi concluída
    if (task.executed) return 'Concluído';
    
    const todayDate = today();
    
    // Se a tarefa tem data de início (está em andamento)
    if (task.start) {
        const startDate = toDateOnly(task.start);
        
        if (startDate && startDate <= todayDate) {
            // Verificar se já passou da data planejada
            if (task.planned) {
                const plannedDate = toDateOnly(task.planned);
                if (plannedDate && todayDate > plannedDate) {
                    return 'Atrasado';
                }
            }
            return 'Em Andamento';
        }
        return 'Pendente';
    }
    
    // Se a tarefa não começou, verificar se já passou da data planejada
    if (task.planned) {
        const plannedDate = toDateOnly(task.planned);
        if (plannedDate && todayDate > plannedDate) {
            return 'Atrasado';
        }
        // Se a data planejada é hoje ou no futuro, está no prazo
        if (plannedDate && todayDate <= plannedDate) {
            return 'No Prazo';
        }
    }
    
    return 'Pendente';
}

function calculateProjectStatus(project) {
    const tasks = project.tasks;
    if (!tasks) return 'Pendente';
    
    // Se o status foi definido manualmente como Cancelado ou Em Espera
    if (project.manualStatus === 'Cancelado' || project.manualStatus === 'Em Espera') {
        return project.manualStatus;
    }
    
    const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
    let allCompleted = true;
    let anyInProgress = false;
    let anyDelayed = false;
    let anyNoPrazo = false;

    taskKeys.forEach(key => {
        const task = tasks[key];
        // Só considera a tarefa se ela existir (para projetos que não têm todas as tarefas)
        if (task) {
            const status = calculateTaskStatus(task, project.status);
            if (status !== 'Concluído') allCompleted = false;
            if (status === 'Em Andamento') anyInProgress = true;
            if (status === 'Atrasado') anyDelayed = true;
            if (status === 'No Prazo') anyNoPrazo = true;
        }
    });

    // Prioridade: Atrasado > Em Andamento > No Prazo > Pendente > Concluído
    if (anyDelayed) return 'Atrasado';
    if (allCompleted) return 'Concluído';
    if (anyInProgress) return 'Em Andamento';
    if (anyNoPrazo) return 'No Prazo';
    
    return 'Pendente';
}

// ==============================================
// FUNÇÕES DE EFICIÊNCIA
// ==============================================
function calculateTasksEfficiency(projects) {
    if (!projects.length) return 0;
    
    let totalEfficiency = 0;
    let taskCount = 0;
    
    projects.forEach(project => {
        const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
        taskKeys.forEach(key => {
            const task = project.tasks?.[key];
            if (task) {
                const efficiency = calculateTaskEfficiency(task);
                if (efficiency !== null) {
                    totalEfficiency += efficiency;
                    taskCount++;
                }
            }
        });
    });
    
    return taskCount > 0 ? (totalEfficiency / taskCount) : 0;
}

function calculateTaskEfficiency(task) {
    if (!task) return null;
    
    const todayDate = today();
    const plannedDate = task.planned ? toDateOnly(task.planned) : null;
    const executedDate = task.executed ? toDateOnly(task.executed) : null;
    
    if (executedDate) {
        if (!plannedDate) {
            return 100;
        }
        if (executedDate <= plannedDate) {
            return 100;
        } else {
            const delayDays = Math.floor((executedDate - plannedDate) / (1000 * 60 * 60 * 24));
            const penalty = Math.min(delayDays * 5, 100);
            return Math.max(0, 100 - penalty);
        }
    } else {
        if (plannedDate && todayDate > plannedDate) {
            const delayDays = Math.floor((todayDate - plannedDate) / (1000 * 60 * 60 * 24));
            const penalty = Math.min(delayDays * 10, 100);
            return Math.max(0, 100 - penalty);
        }
        return null;
    }
}

function calculateProjectsEfficiency(projects) {
    if (!projects.length) return 0;
    
    const completedProjects = projects.filter(p => p.status === "Concluído").length;
    return (completedProjects / projects.length) * 100;
}

// ==============================================
// FUNÇÃO DE PROGRESSO CORRIGIDA
// ==============================================
function calculateWeightedProjectProgress(project) {
    const taskWeights = {
        'kom': 1,
        'ferramental': 3,
        'cadBomFt': 2,
        'tryout': 3,
        'entrega': 1,
        'psw': 1,
        'handover': 1
    };
    
    let totalWeight = 0;
    let completedWeight = 0;
    
    Object.keys(taskWeights).forEach(taskKey => {
        const weight = taskWeights[taskKey];
        const task = project.tasks?.[taskKey];
        
        totalWeight += weight;
        
        if (task && task.executed) {
            completedWeight += weight;
        }
    });
    
    const progressPercentage = totalWeight > 0 ? (completedWeight / totalWeight) * 100 : 0;
    
    return {
        progress: progressPercentage,
        completedWeight: completedWeight,
        totalWeight: totalWeight,
        details: taskWeights
    };
}

function getDefaultDuration(taskKey) {
    const defaultDurations = {
        'kom': 1,
        'ferramental': 5,
        'cadBomFt': 3,
        'tryout': 3,
        'entrega': 1,
        'psw': 1,
        'handover': 1
    };
    return defaultDurations[taskKey] || 1;
}

// ==============================================

