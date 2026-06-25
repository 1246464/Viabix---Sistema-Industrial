// FUNÇÕES DE GANTT (manter as originais)
// ==============================================
function setGanttScale(scale) {
    ganttScale = scale;
    
    document.querySelectorAll('.gantt-scale-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    const clickedBtn = Array.from(document.querySelectorAll('.gantt-scale-btn')).find(
        btn => btn.textContent.trim().toLowerCase().includes(scale)
    );
    
    if (clickedBtn) {
        clickedBtn.classList.add('active');
    }
    
    renderGanttChart();
}

function toggleGanttLabels() {
    showGanttLabels = !showGanttLabels;
    
    const btn = Array.from(document.querySelectorAll('.gantt-scale-btn')).find(
        btn => btn.textContent.trim().includes('Rótulos')
    );
    
    if (btn) {
        btn.textContent = showGanttLabels ? 'Ocultar Rótulos' : 'Mostrar Rótulos';
    }
    
    renderGanttChart();
}

function renderGanttChart() {
    const projectId = currentTimelineProjectId;
    if (!projectId) return;
    
    const project = projects.find(p => p.id === projectId);
    if (!project) return;
    
    const ganttContainer = document.getElementById('ganttContainer');
    if (!ganttContainer) return;
    
    // Encontrar a data mínima e máxima entre todas as tarefas
    let minDate = null;
    let maxDate = null;
    
    const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
    const taskNames = {
        'kom': 'KOM',
        'ferramental': 'Ferramental',
        'cadBomFt': 'CAD+BOM+FT',
        'tryout': 'Try-out',
        'entrega': 'Entrega',
        'psw': 'PSW',
        'handover': 'Handover'
    };
    
    // Primeiro, coletar todas as tarefas existentes
    const existingTasks = [];
    taskKeys.forEach(taskKey => {
        const task = project.tasks?.[taskKey];
        if (task) {
            existingTasks.push({
                key: taskKey,
                name: taskNames[taskKey],
                task: task
            });
            
            // Calcular datas para o range
            if (task.planned) {
                const plannedDate = toDateOnly(task.planned);
                const duration = task.duration || getDefaultDuration(taskKey);
                const plannedEndDate = new Date(plannedDate);
                plannedEndDate.setDate(plannedEndDate.getDate() + duration);
                
                if (!minDate || plannedDate < minDate) minDate = new Date(plannedDate);
                if (!maxDate || plannedEndDate > maxDate) maxDate = new Date(plannedEndDate);
            }
            
            if (task.executed) {
                const executedDate = toDateOnly(task.executed);
                if (executedDate > maxDate) maxDate = new Date(executedDate);
            }
            
            if (task.start && !task.executed) {
                const startDate = toDateOnly(task.start);
                if (startDate > maxDate) maxDate = new Date(startDate);
            }
        }
    });
    
    // Se não houver tarefas, mostrar mensagem
    if (existingTasks.length === 0) {
        ganttContainer.innerHTML = '<div style="padding: 20px; text-align: center;">Nenhuma tarefa com datas definidas.</div>';
        return;
    }
    
    // Se não houver datas, definir padrão
    if (!minDate) {
        minDate = new Date();
        minDate.setDate(minDate.getDate() - 30);
    }
    
    if (!maxDate) {
        maxDate = new Date();
        maxDate.setDate(maxDate.getDate() + 90);
    }
    
    // Adicionar margem de 7 dias antes e depois
    minDate = new Date(minDate);
    minDate.setDate(minDate.getDate() - 7);
    
    maxDate = new Date(maxDate);
    maxDate.setDate(maxDate.getDate() + 7);
    
    const totalDays = Math.ceil((maxDate - minDate) / (1000 * 60 * 60 * 24));
    
    // Gerar HTML do Gantt usando tabela
    let html = '<div class="gantt-table-container" style="overflow-x: auto; max-width: 100%;">';
    html += '<table class="gantt-table" style="border-collapse: collapse; width: 100%;">';
    
    // CABEÇALHO
    html += '<thead>';
    html += '<tr>';
    
    // Coluna fixa do nome da tarefa
    html += '<th style="position: sticky; left: 0; background: #2e7d32; color: white; padding: 10px; min-width: 200px; z-index: 10; border: 1px solid #1b5e20;">Tarefa</th>';
    
    let currentDate = new Date(minDate);
    
    if (ganttScale === 'week') {
        while (currentDate <= maxDate) {
            const weekStart = new Date(currentDate);
            const weekEnd = new Date(currentDate);
            weekEnd.setDate(weekEnd.getDate() + 6);
            
            if (weekStart > maxDate) break;
            
            const weekDays = 7;
            const weekWidth = (weekDays / totalDays) * 100;
            const weekNumber = getWeekNumber(weekStart);
            const year = weekStart.getFullYear();
            
            html += `<th style="background: #f5f5f5; padding: 8px; text-align: center; min-width: 80px; border-left: 1px solid #ddd; border-bottom: 2px solid #2e7d32; width: ${weekWidth}%; color: #000;">`;
            html += `<div style="font-weight: bold; font-size: 0.8rem;">Semana ${weekNumber}/${year}</div>`;
            html += `<div style="font-size: 0.7rem;">${formatDateBR(weekStart)}</div>`;
            html += '</th>';
            
            currentDate.setDate(currentDate.getDate() + 7);
        }
    } else if (ganttScale === 'month') {
        currentDate = new Date(minDate);
        currentDate.setDate(1);
        
        while (currentDate <= maxDate) {
            const monthStart = new Date(currentDate);
            const monthEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            
            if (monthStart > maxDate) break;
            
            const monthDays = monthEnd.getDate();
            const monthWidth = (monthDays / totalDays) * 100;
            
            html += `<th style="background: #f5f5f5; padding: 8px; text-align: center; min-width: 100px; border-left: 1px solid #ddd; border-bottom: 2px solid #2e7d32; width: ${monthWidth}%; color: #000;">`;
            html += `<div style="font-weight: bold; font-size: 0.8rem;">${getMonthName(currentDate.getMonth())}</div>`;
            html += `<div style="font-size: 0.7rem;">${currentDate.getFullYear()}</div>`;
            html += '</th>';
            
            currentDate.setMonth(currentDate.getMonth() + 1);
        }
    } else if (ganttScale === 'quarter') {
        currentDate = new Date(minDate);
        currentDate.setMonth(Math.floor(currentDate.getMonth() / 3) * 3, 1);
        
        while (currentDate <= maxDate) {
            const quarterStart = new Date(currentDate);
            const quarterEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 3, 0);
            
            if (quarterStart > maxDate) break;
            
            const quarterDays = Math.ceil((quarterEnd - quarterStart) / (1000 * 60 * 60 * 24)) + 1;
            const quarterWidth = (quarterDays / totalDays) * 100;
            const quarterNumber = Math.floor(quarterStart.getMonth() / 3) + 1;
            
            html += `<th style="background: #f5f5f5; padding: 8px; text-align: center; min-width: 120px; border-left: 1px solid #ddd; border-bottom: 2px solid #2e7d32; width: ${quarterWidth}%; color: #000;">`;
            html += `<div style="font-weight: bold; font-size: 0.8rem;">${quarterNumber}º Trim</div>`;
            html += `<div style="font-size: 0.7rem;">${quarterStart.getFullYear()}</div>`;
            html += '</th>';
            
            currentDate.setMonth(currentDate.getMonth() + 3);
        }
    }
    
    html += '</tr>';
    html += '</thead>';
    
    // CORPO DA TABELA
    html += '<tbody>';
    
    // Linha da data atual (marcador "Hoje")
    const todayDate = today();
    
    if (todayDate >= minDate && todayDate <= maxDate) {
        html += '<tr style="height: 0;">';
        html += '<td style="position: sticky; left: 0; background: transparent; border: none;"></td>';
        
        currentDate = new Date(minDate);
        
        while (currentDate <= maxDate) {
            const colStart = new Date(currentDate);
            let colEnd;
            let colDays;
            
            if (ganttScale === 'week') {
                colEnd = new Date(currentDate);
                colEnd.setDate(colEnd.getDate() + 6);
                colDays = 7;
            } else if (ganttScale === 'month') {
                colEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
                colDays = colEnd.getDate();
            } else {
                colEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 3, 0);
                colDays = Math.ceil((colEnd - currentDate) / (1000 * 60 * 60 * 24)) + 1;
            }
            
            // Verificar se "Hoje" está dentro desta coluna
            if (todayDate >= colStart && todayDate <= colEnd) {
                const colWidth = (colDays / totalDays) * 100;
                const dayOffset = ((todayDate - colStart) / (1000 * 60 * 60 * 24)) / colDays;
                const position = (colWidth * dayOffset) + '%';
                
                html += `<td style="position: relative; padding: 0; border: none; height: 0;">`;
                html += `<div style="position: absolute; top: -30px; left: ${position}; width: 2px; height: ${existingTasks.length * 51}px; background: #f44336; z-index: 5;">`;
                html += `<span style="position: absolute; top: -25px; left: 5px; background: #f44336; color: white; padding: 2px 5px; border-radius: 3px; font-size: 0.7rem; white-space: nowrap;">Hoje</span>`;
                html += `</div></td>`;
            } else {
                html += '<td style="padding: 0; border: none; height: 0;"></td>';
            }
            
            // Avançar para a próxima coluna
            if (ganttScale === 'week') {
                currentDate.setDate(currentDate.getDate() + 7);
            } else if (ganttScale === 'month') {
                currentDate.setMonth(currentDate.getMonth() + 1);
            } else {
                currentDate.setMonth(currentDate.getMonth() + 3);
            }
        }
        
        html += '</tr>';
    }
    
    // Linhas das tarefas
    existingTasks.forEach(taskInfo => {
        const taskKey = taskInfo.key;
        const task = taskInfo.task;
        const taskName = taskInfo.name;
        const taskStatus = calculateTaskStatus(task, project.status);
        
        html += '<tr style="height: 50px;">';
        
        // Coluna do nome da tarefa (fixa)
        html += `<td style="position: sticky; left: 0; background: #f0f8f0; padding: 8px; border-right: 2px solid #2e7d32; border-bottom: 1px solid #ddd; min-width: 200px; z-index: 5;">`;
        html += `<div style="display: flex; flex-direction: column;">`;
        html += `<strong>${taskName}</strong>`;
        html += `<span class="status status-${taskStatus.toLowerCase().replace(/\s/g, '-')}" style="font-size: 0.7rem; padding: 2px 5px; width: fit-content; margin-top: 3px;">${taskStatus}</span>`;
        html += `</div>`;
        html += `</td>`;
        
        // Colunas da timeline (com as barras)
        currentDate = new Date(minDate);
        
        while (currentDate <= maxDate) {
            const colStart = new Date(currentDate);
            let colEnd;
            let colDays;
            
            if (ganttScale === 'week') {
                colEnd = new Date(currentDate);
                colEnd.setDate(colEnd.getDate() + 6);
                colDays = 7;
            } else if (ganttScale === 'month') {
                colEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
                colDays = colEnd.getDate();
            } else {
                colEnd = new Date(currentDate.getFullYear(), currentDate.getMonth() + 3, 0);
                colDays = Math.ceil((colEnd - currentDate) / (1000 * 60 * 60 * 24)) + 1;
            }
            
            const colWidth = (colDays / totalDays) * 100;
            
            html += `<td style="position: relative; padding: 0; border-left: 1px solid #eee; border-bottom: 1px solid #ddd; background: repeating-linear-gradient(45deg, #f9f9f9, #f9f9f9 10px, #f5f5f5 10px, #f5f5f5 20px);">`;
            
            // Container para as barras
            html += `<div style="position: relative; width: 100%; height: 50px;">`;
            
            // Barra planejada
            if (task.planned) {
                const plannedDate = toDateOnly(task.planned);
                const duration = task.duration || getDefaultDuration(taskKey);
                const plannedEnd = new Date(plannedDate);
                plannedEnd.setDate(plannedEnd.getDate() + duration);
                
                // Calcular posição relativa a esta coluna
                const barLeft = Math.max(0, ((plannedDate - colStart) / (1000 * 60 * 60 * 24)) / colDays) * 100;
                const barRight = Math.min(100, ((plannedEnd - colStart) / (1000 * 60 * 60 * 24)) / colDays) * 100;
                const barWidth = barRight - barLeft;
                
                // Verificar se a barra intersecta esta coluna
                if (barWidth > 0 && barLeft < 100 && barRight > 0) {
                    const adjustedLeft = Math.max(0, barLeft);
                    const adjustedWidth = Math.min(100 - adjustedLeft, barWidth);
                    
                    if (adjustedWidth > 0) {
                        html += `<div class="gantt-bar planned" style="position: absolute; left: ${adjustedLeft}%; width: ${adjustedWidth}%; height: 30px; top: 10px; background: linear-gradient(90deg, #90caf9, #42a5f5); border: 1px solid #1e88e5; border-radius: 4px; opacity: 0.7; cursor: pointer; z-index: 2;"`;
                        html += ` onmouseover="showGanttTooltip(event, '${taskName} - Planejado', '${formatDateBR(plannedDate)} a ${formatDateBR(plannedEnd)} (${duration} dias)')"`;
                        html += ` onmouseout="hideGanttTooltip()">`;
                        if (showGanttLabels && adjustedLeft < 20) {
                            html += `<span style="position: absolute; top: 2px; left: 5px; font-size: 0.7rem; color: white; font-weight: bold;">${duration}d</span>`;
                        }
                        html += `</div>`;
                    }
                }
            }
            
            // Barra real (executada)
            if (task.executed) {
                const executedDate = toDateOnly(task.executed);
                let startReal = task.start ? toDateOnly(task.start) : null;
                
                // Se não tiver data de início, usar a data de conclusão menos a duração
                if (!startReal && executedDate) {
                    startReal = new Date(executedDate);
                    const taskDuration = task.duration || getDefaultDuration(taskKey);
                    startReal.setDate(startReal.getDate() - taskDuration);
                }
                
                // Se a tarefa foi concluída no mesmo dia, garantir que a barra apareça
                if (startReal && startReal <= executedDate) {
                    // Calcular posição relativa a esta coluna
                    const barLeft = Math.max(0, ((startReal - colStart) / (1000 * 60 * 60 * 24)) / colDays) * 100;
                    const barRight = Math.min(100, ((executedDate - colStart) / (1000 * 60 * 60 * 24)) / colDays) * 100;
                    const barWidth = barRight - barLeft;
                    
                    // Garantir largura mínima para tarefas de 1 dia
                    const minBarWidth = (1 / colDays) * 100;
                    const effectiveBarWidth = Math.max(barWidth, barWidth > 0 ? minBarWidth : 0);
                    
                    if (effectiveBarWidth > 0 && barLeft < 100 && barRight > 0) {
                        const adjustedLeft = Math.max(0, barLeft);
                        const adjustedWidth = Math.min(100 - adjustedLeft, effectiveBarWidth);
                        
                        if (adjustedWidth > 0) {
                            html += `<div class="gantt-bar actual" style="position: absolute; left: ${adjustedLeft}%; width: ${adjustedWidth}%; height: 30px; top: 10px; background: linear-gradient(90deg, #4caf50, #2e7d32) !important; border: 1px solid #1b5e20 !important; border-radius: 4px; z-index: 3; cursor: pointer;"`;
                            html += ` onmouseover="showGanttTooltip(event, '${taskName} - Concluído', '${formatDateBR(startReal)} a ${formatDateBR(executedDate)}')"`;
                            html += ` onmouseout="hideGanttTooltip()">`;
                            if (showGanttLabels && adjustedLeft < 20) {
                                html += `<span style="position: absolute; top: 2px; left: 5px; font-size: 0.7rem; color: white; font-weight: bold;">✓</span>`;
                            }
                            html += `</div>`;
                        }
                    }
                } else if (executedDate) {
                    // Caso especial: apenas data de conclusão, sem início
                    const barLeft = Math.max(0, ((executedDate - colStart) / (1000 * 60 * 60 * 24)) / colDays) * 100;
                    const barWidth = (1 / colDays) * 100;
                    
                    if (barWidth > 0 && barLeft < 100) {
                        const adjustedLeft = Math.max(0, barLeft);
                        const adjustedWidth = Math.min(100 - adjustedLeft, barWidth);
                        
                        html += `<div class="gantt-bar actual" style="position: absolute; left: ${adjustedLeft}%; width: ${adjustedWidth}%; height: 30px; top: 10px; background: linear-gradient(90deg, #4caf50, #2e7d32) !important; border: 1px solid #1b5e20 !important; border-radius: 4px; z-index: 3; cursor: pointer;"`;
                        html += ` onmouseover="showGanttTooltip(event, '${taskName} - Concluído', '${formatDateBR(executedDate)}')"`;
                        html += ` onmouseout="hideGanttTooltip()">✓</div>`;
                    }
                }
            } else if (task.start) {
                // Em andamento
                const startReal = toDateOnly(task.start);
                const currentDate = today();
                
                if (startReal <= currentDate) {
                    // Calcular posição relativa a esta coluna
                    const barLeft = Math.max(0, ((startReal - colStart) / (1000 * 60 * 60 * 24)) / colDays) * 100;
                    const barRight = Math.min(100, ((currentDate - colStart) / (1000 * 60 * 60 * 24)) / colDays) * 100;
                    const barWidth = barRight - barLeft;
                    
                    if (barWidth > 0 && barLeft < 100 && barRight > 0) {
                        const adjustedLeft = Math.max(0, barLeft);
                        const adjustedWidth = Math.min(100 - adjustedLeft, barWidth);
                        
                        if (adjustedWidth > 0) {
                            html += `<div class="gantt-bar delayed" style="position: absolute; left: ${adjustedLeft}%; width: ${adjustedWidth}%; height: 30px; top: 10px; background: linear-gradient(90deg, #ef9a9a, #ef5350); border: 1px solid #c62828; border-radius: 4px; z-index: 3; cursor: pointer;"`;
                            html += ` onmouseover="showGanttTooltip(event, '${taskName} - Em Andamento', 'Início: ${formatDateBR(startReal)}')"`;
                            html += ` onmouseout="hideGanttTooltip()">`;
                            if (showGanttLabels && adjustedLeft < 20) {
                                html += `<span style="position: absolute; top: 2px; left: 5px; font-size: 0.7rem; color: white; font-weight: bold;">→</span>`;
                            }
                            html += `</div>`;
                        }
                    }
                }
            }
            
            html += `</div>`; // Fim do container de barras
            html += `</td>`;
            
            // Avançar para a próxima coluna
            if (ganttScale === 'week') {
                currentDate.setDate(currentDate.getDate() + 7);
            } else if (ganttScale === 'month') {
                currentDate.setMonth(currentDate.getMonth() + 1);
            } else {
                currentDate.setMonth(currentDate.getMonth() + 3);
            }
        }
        
        html += '</tr>';
    });
    
    html += '</tbody>';
    html += '</table>';
    html += '</div>';
    
    ganttContainer.innerHTML = html;
    
    // Adicionar tooltip
    let tooltip = document.querySelector('.gantt-info-tooltip');
    if (!tooltip) {
        tooltip = document.createElement('div');
        tooltip.className = 'gantt-info-tooltip';
        tooltip.style.position = 'fixed';
        tooltip.style.background = 'rgba(0,0,0,0.8)';
        tooltip.style.color = 'white';
        tooltip.style.padding = '5px 10px';
        tooltip.style.borderRadius = '4px';
        tooltip.style.fontSize = '0.8rem';
        tooltip.style.pointerEvents = 'none';
        tooltip.style.zIndex = '1000';
        tooltip.style.display = 'none';
        document.body.appendChild(tooltip);
    }
}

function showGanttTooltip(event, title, content) {
    const tooltip = document.querySelector('.gantt-info-tooltip');
    if (!tooltip) return;
    
    tooltip.innerHTML = `<strong>${title}</strong><br>${content}`;
    tooltip.style.display = 'block';
    tooltip.style.left = (event.pageX + 10) + 'px';
    tooltip.style.top = (event.pageY - 30) + 'px';
}

function hideGanttTooltip() {
    const tooltip = document.querySelector('.gantt-info-tooltip');
    if (tooltip) {
        tooltip.style.display = 'none';
    }
}

// ==============================================
// FUNÇÕES DE CRONOGRAMA (manter as originais, usando a nova função de progresso)
// ==============================================
function showTimeline(projectId) {
    closeAllModals();
    currentTimelineProjectId = projectId;
    const project = projects.find(p => p.id === projectId);
    if (!project) return;

    document.getElementById('timelineModalTitle').textContent = `Cronograma - ${project.projectName} (ID: ${project.id})`;
    
    const progressData = calculateWeightedProjectProgress(project);
    
    const progressBarHTML = `
        <div class="progress-weight-info">
            <h4>Progresso Ponderado do Projeto</h4>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: ${progressData.progress}%">
                    ${progressData.progress.toFixed(1)}%
                </div>
            </div>
            <div class="progress-bar-labels">
                <span>0%</span>
                <span>${progressData.progress.toFixed(1)}%</span>
                <span>100%</span>
            </div>
            <p style="text-align: center; margin-top: 8px; font-size: 0.9rem;">
                Concluído: ${progressData.completedWeight} | Total: ${progressData.totalWeight}
            </p>
            
            <div class="progress-weight-list">
                <div class="progress-weight-item">
                    <span class="progress-weight-task">KOM</span>
                    <span class="progress-weight-value">Peso: 1</span>
                </div>
                <div class="progress-weight-item" style="border-left-color: #2196f3;">
                    <span class="progress-weight-task">Ferramental</span>
                    <span class="progress-weight-value">Peso: 3 (Maior)</span>
                </div>
                <div class="progress-weight-item">
                    <span class="progress-weight-task">CAD+BOM+FT</span>
                    <span class="progress-weight-value">Peso: 2</span>
                </div>
                <div class="progress-weight-item" style="border-left-color: #2196f3;">
                    <span class="progress-weight-task">Try-out</span>
                    <span class="progress-weight-value">Peso: 3 (Maior)</span>
                </div>
                <div class="progress-weight-item">
                    <span class="progress-weight-task">Entrega da Amostra</span>
                    <span class="progress-weight-value">Peso: 1</span>
                </div>
                <div class="progress-weight-item">
                    <span class="progress-weight-task">PSW</span>
                    <span class="progress-weight-value">Peso: 1</span>
                </div>
                <div class="progress-weight-item">
                    <span class="progress-weight-task">Handover</span>
                    <span class="progress-weight-value">Peso: 1</span>
                </div>
            </div>
        </div>
    `;
    
    const projectInfoHTML = `
        <div class="project-info-grid">
            <div class="project-info-item">
                <div class="project-info-label">Cliente</div>
                <div class="project-info-value">${project.cliente || '-'}</div>
            </div>
            <div class="project-info-item">
                <div class="project-info-label">Código</div>
                <div class="project-info-value">${project.codigo || '-'}</div>
            </div>
            <div class="project-info-item">
                <div class="project-info-label">ANVI</div>
                <div class="project-info-value">${project.anviNumber || '-'}</div>
            </div>
            <div class="project-info-item">
                <div class="project-info-label">Segmento</div>
                <div class="project-info-value">${project.segmento || '-'}</div>
            </div>
            <div class="project-info-item">
                <div class="project-info-label">Líder</div>
                <div class="project-info-value">${project.projectLeader || '-'}</div>
            </div>
            <div class="project-info-item">
                <div class="project-info-label">Modelo</div>
                <div class="project-info-value">${project.modelo || '-'}</div>
            </div>
            <div class="project-info-item">
                <div class="project-info-label">Processo</div>
                <div class="project-info-value">${project.processo || '-'}</div>
            </div>
            <div class="project-info-item">
                <div class="project-info-label">Fase</div>
                <div class="project-info-value">${project.fase || '-'}</div>
            </div>
            <div class="project-info-item">
                <div class="project-info-label">Status do Projeto</div>
                <div class="project-info-value">
                    <span class="status status-${project.status.toLowerCase().replace(/\s/g, '-')}">
                        ${project.status}
                    </span>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('timelineProjectInfo').innerHTML = progressBarHTML + projectInfoHTML;
    
    renderGanttChart();
    
    const timelineContainer = document.getElementById('timelineContainer');
    timelineContainer.innerHTML = '';
    
    const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
    const taskNames = {
        'kom': 'KOM - Kick-off Meeting',
        'ferramental': 'Ferramental - Desenvolvimento e preparação',
        'cadBomFt': 'CAD+BOM+FT - Projeto CAD, Lista de Materiais e Folha de Tempos',
        'tryout': 'TRY-OUT - Testes e ajustes dos ferramentais',
        'entrega': 'ENTREGA - Entrega da Amostra',
        'psw': 'PSW - Part Submission Warrant',
        'handover': 'HANDOVER - Transferência do projeto'
    };
    
    taskKeys.forEach((taskKey, index) => {
        const task = project.tasks?.[taskKey];
        if (!task) return;
        
        const taskStatus = calculateTaskStatus(task, project.status);
        const taskWeight = progressData.details[taskKey] || 1;
        const apqpBadge = getApqpBadgeHtml(project, taskKey);
        
        const timelinePhase = document.createElement('div');
        timelinePhase.className = 'timeline-phase';
        
        const timelinePhaseHeader = document.createElement('div');
        timelinePhaseHeader.className = 'timeline-phase-header';
        timelinePhaseHeader.innerHTML = `
            <div class="timeline-phase-title">${taskNames[taskKey]} <small>(Peso: ${taskWeight} | Duração: ${task.duration || getDefaultDuration(taskKey)} dias)</small></div>
            <div class="timeline-phase-subtitle">
                Status: <span class="status status-${taskStatus.toLowerCase().replace(/\s/g, '-')}">${taskStatus}</span>
            </div>
            <div class="phase-apqp-status">
                ${apqpBadge}
            </div>
        `;
        
        const timelineTasks = document.createElement('div');
        timelineTasks.className = 'timeline-tasks';
        
        const timelineTask = document.createElement('div');
        timelineTask.className = 'timeline-task';
        
        const datesHTML = `
            <div class="timeline-task-header">
                <div class="timeline-task-name">${taskKey.toUpperCase()}</div>
                <button class="btn btn-sm" onclick="showApqpAnalysis(${project.id}, '${taskKey}')">
                    <i class="fas fa-clipboard-check"></i> Análise APQP
                </button>
            </div>
            <div class="timeline-task-dates">
                ${task.planned ? `
                <div class="date-card planned">
                    <div class="date-label">Planejado</div>
                    <div class="date-value">${formatDateBR(task.planned)}</div>
                </div>
                ` : ''}
                
                ${task.start ? `
                <div class="date-card actual">
                    <div class="date-label">Início Real</div>
                    <div class="date-value">${formatDateBR(task.start)}</div>
                </div>
                ` : ''}
                
                ${task.executed ? `
                <div class="date-card completed">
                    <div class="date-label">Conclusão</div>
                    <div class="date-value">${formatDateBR(task.executed)}</div>
                </div>
                ` : ''}
                
                <div class="date-card">
                    <div class="date-label">Duração</div>
                    <div class="date-value">${task.duration || getDefaultDuration(taskKey)} dias</div>
                </div>
                
                ${!task.executed && task.planned ? `
                <div class="date-card">
                    <div class="date-label">Situação</div>
                    <div class="date-value">
                        ${compareDates(today(), toDateOnly(task.planned)) > 0 ? 'Atrasado' : 'No Prazo'}
                    </div>
                </div>
                ` : ''}
            </div>
        `;
        
        timelineTask.innerHTML = datesHTML;
        timelineTasks.appendChild(timelineTask);
        
        timelinePhase.appendChild(timelinePhaseHeader);
        timelinePhase.appendChild(timelineTasks);
        timelineContainer.appendChild(timelinePhase);
    });
    
    document.getElementById('timelineModal').style.display = 'block';
}

function printTimeline() {
    const projectId = currentTimelineProjectId;
    const project = projects.find(p => p.id === projectId);
    if (!project) {
        alert('Nenhum projeto selecionado para imprimir.');
        return;
    }

    const modalContent = document.getElementById('timelineModal').innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Cronograma do Projeto - ${project.projectName}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .timeline-container { margin-top: 20px; }
                    .timeline-phase { margin-bottom: 30px; border-left: 3px solid #2e7d32; padding-left: 20px; }
                    .timeline-phase-header { background: #f0f9f0; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
                    .timeline-task { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 10px; }
                    .date-card { background: #f0f0f0; padding: 10px; border-radius: 5px; margin-bottom: 5px; }
                    .project-info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 20px; }
                    .project-info-item { border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
                    .status { padding: 3px 8px; border-radius: 10px; font-size: 12px; font-weight: bold; }
                    .status-concluído { background: #4caf50; color: white; }
                    .status-em-andamento { background: #2196f3; color: white; }
                    .status-atrasado { background: #f44336; color: white; }
                    .status-no-prazo { background: #4caf50; color: white; }
                    .status-pendente { background: #ff9800; color: white; }
                    .gantt-chart { margin: 20px 0; overflow-x: auto; }
                    .gantt-table { border-collapse: collapse; width: 100%; }
                    .gantt-table th { background: #f5f5f5; padding: 8px; text-align: center; border-left: 1px solid #ddd; border-bottom: 2px solid #2e7d32; color: #000; }
                    .gantt-table td { padding: 0; border-left: 1px solid #eee; border-bottom: 1px solid #ddd; }
                    .gantt-table td:first-child { background: #f0f8f0; border-right: 2px solid #2e7d32; padding: 8px; }
                    .gantt-bar { position: absolute; height: 30px; top: 10px; border-radius: 4px; }
                    .gantt-bar.planned { background: linear-gradient(90deg, #90caf9, #42a5f5); }
                    .gantt-bar.actual { background: linear-gradient(90deg, #4caf50, #2e7d32); }
                    .gantt-bar.delayed { background: linear-gradient(90deg, #ef9a9a, #ef5350); }
                    .btn, .modal .close, .timeline-actions { display: none !important; }
                </style>
            </head>
            <body>
                <h1>Cronograma do Projeto - ${project.projectName}</h1>
                <p>Gerado em: ${new Date().toLocaleDateString('pt-BR')} ${new Date().toLocaleTimeString('pt-BR')}</p>
                <div id="timelineModal">${modalContent}</div>
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}

// ==============================================

