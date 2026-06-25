// FUNÇÕES DE GRÁFICOS (manter as originais)
// ==============================================
function initChartFilters() {
    const currentDate = new Date();
    document.getElementById('chartDateFrom').value = toISODateString(new Date(currentDate.getFullYear(), currentDate.getMonth(), 1));
    document.getElementById('chartDateTo').value = toISODateString(new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0));
    setupChartAutoUpdate();
}

function showChartsSection() {
    closeAllModals();
    document.getElementById('chartsSection').style.display = 'block';
    document.getElementById('projectForm').style.display = 'none';
    document.getElementById('leadersForm').style.display = 'none';
    
    initChartFilters();
    updateCharts();
}

function hideChartsSection() {
    document.getElementById('chartsSection').style.display = 'none';
}

function getChartFilteredProjects() {
    const taskKey = document.getElementById('chartTaskFilter').value;
    const segment = document.getElementById('chartSegment').value;
    const dateFrom = document.getElementById('chartDateFrom').value;
    const dateTo = document.getElementById('chartDateTo').value;
    
    const chartTaskStatusElements = document.getElementById('chartTaskStatus').selectedOptions;
    const chartTaskStatus = Array.from(chartTaskStatusElements).map(option => option.value);
    
    let filteredProjects = projects;
    
    if (segment !== 'todos') {
        filteredProjects = filteredProjects.filter(p => p.segmento === segment);
    }
    
    if (taskKey !== 'todos') {
        filteredProjects = filteredProjects.filter(p => {
            const task = p.tasks?.[taskKey];
            if (!task) return false;
            
            if (chartTaskStatus.length > 0) {
                const currentTaskStatus = calculateTaskStatus(task, p.status);
                if (!chartTaskStatus.includes(currentTaskStatus)) return false;
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

                if (!dateInRange) return false;
            }
            
            return true;
        });
    } else {
        if (chartTaskStatus.length > 0) {
            filteredProjects = filteredProjects.filter(p => {
                const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
                return taskKeys.some(key => {
                    const task = p.tasks?.[key];
                    return task && calculateTaskStatus(task, p.status) === chartTaskStatus[0];
                });
            });
        }
        
        if (dateFrom || dateTo) {
            filteredProjects = filteredProjects.filter(p => {
                const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
                return taskKeys.some(key => {
                    const task = p.tasks?.[key];
                    if (!task || !task.planned) return false;
                    
                    let dateInRange = false;
                    const fromDateObj = dateFrom ? toDateOnly(dateFrom) : null;
                    const toDateObj = dateTo ? toDateOnly(dateTo) : null;
                    
                    const plannedDate = toDateOnly(task.planned);
                    if ((!fromDateObj || compareDates(plannedDate, fromDateObj) >= 0) && 
                        (!toDateObj || compareDates(plannedDate, toDateObj) <= 0)) {
                        dateInRange = true;
                    }
                    
                    return dateInRange;
                });
            });
        }
    }
    
    return filteredProjects;
}

function updateCharts() {
    const taskKey = document.getElementById('chartTaskFilter').value;
    const segment = document.getElementById('chartSegment').value;
    const dateFrom = document.getElementById('chartDateFrom').value;
    const dateTo = document.getElementById('chartDateTo').value;
    
    const chartTaskStatusElements = document.getElementById('chartTaskStatus').selectedOptions;
    const chartTaskStatus = Array.from(chartTaskStatusElements).map(option => option.value);
    
    let projectsForPeriod = getChartFilteredProjects();
    
    if (document.getElementById('efficiencyChart')) {
        renderEfficiencyChart(projectsForPeriod, taskKey);
    }
    if (document.getElementById('projectStatusChart')) {
        renderProjectStatusChart(projectsForPeriod);
    }
    if (document.getElementById('leaderChart')) {
        renderLeaderChart(projectsForPeriod);
    }
    if (document.getElementById('segmentChart')) {
        renderSegmentChart(projectsForPeriod);
    }
    
    let totalPlannedAll = 0;
    let totalExecutedAll = 0;
    let totalOnTimeAll = 0;
    
    const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
    taskKeys.forEach(key => {
        const stats = calculateTaskStatsForPeriod(projectsForPeriod, key);
        totalPlannedAll += stats.totalPlanned;
        totalExecutedAll += stats.totalExecuted;
        totalOnTimeAll += stats.totalOnTime;
    });
    
    const tasksEfficiencyRate = totalPlannedAll > 0 ? (totalOnTimeAll / totalPlannedAll) * 100 : 0;
    const projectsEfficiency = calculateProjectsEfficiency(projectsForPeriod);
    
    document.getElementById('periodTasksEfficiency').innerText = `${tasksEfficiencyRate.toFixed(1)}%`;
    document.getElementById('periodProjectsEfficiency').innerText = `${projectsEfficiency.toFixed(1)}%`;
    
    updatePeriodInfo(projectsForPeriod.length, taskKey, segment, dateFrom, dateTo, chartTaskStatus, totalPlannedAll, totalExecutedAll, totalOnTimeAll);
}

function updatePeriodInfo(projectCount, taskKey, segment, dateFrom, dateTo, taskStatus, totalPlanned, totalExecuted, totalOnTime) {
    const periodInfo = document.getElementById('periodInfoText');
    let infoText = `Mostrando ${projectCount} projetos`;
    
    if (taskKey !== 'todos') {
        infoText += ` | Tarefa: ${taskKey.toUpperCase()}`;
    }
    if (segment !== 'todos') {
        infoText += ` | Segmento: ${segment}`;
    }
    if (dateFrom || dateTo) {
        infoText += ` | Período: ${dateFrom ? formatDateBR(dateFrom) : 'Início'} a ${dateTo ? formatDateBR(dateTo) : 'Fim'}`;
    }
    if (taskStatus.length > 0) {
        infoText += ` | Status: ${taskStatus.join(', ')}`;
    }
    
    infoText += ` | Planejadas: ${totalPlanned} | Executadas: ${totalExecuted} | No prazo: ${totalOnTime}`;
    
    periodInfo.textContent = infoText;
}

function setupChartAutoUpdate() {
    document.getElementById('chartTaskFilter').addEventListener('change', updateCharts);
    document.getElementById('chartSegment').addEventListener('change', updateCharts);
    document.getElementById('chartDateFrom').addEventListener('change', updateCharts);
    document.getElementById('chartDateTo').addEventListener('change', updateCharts);
    document.getElementById('chartTaskStatus').addEventListener('change', updateCharts);
    document.getElementById('applyChartFilters').addEventListener('click', updateCharts);
}

function calculateTaskStatsForPeriod(projectsForPeriod, taskKey) {
    let totalPlannedInPeriod = 0;
    let totalExecutedInPeriod = 0;
    let totalExecutedOnTime = 0;

    projectsForPeriod.forEach(project => {
        const task = project.tasks?.[taskKey];
        if (task && task.planned) {
            const plannedDate = toDateOnly(task.planned);
            const executedDate = task.executed ? toDateOnly(task.executed) : null;
            
            const chartDateFrom = document.getElementById('chartDateFrom').value ? toDateOnly(document.getElementById('chartDateFrom').value) : null;
            const chartDateTo = document.getElementById('chartDateTo').value ? toDateOnly(document.getElementById('chartDateTo').value) : null;
            
            let isInPeriod = true;
            if (chartDateFrom && plannedDate < chartDateFrom) {
                isInPeriod = false;
            }
            if (chartDateTo && plannedDate > chartDateTo) {
                isInPeriod = false;
            }
            
            if (isInPeriod) {
                totalPlannedInPeriod++;
                
                if (executedDate) {
                    totalExecutedInPeriod++;
                    
                    if (executedDate <= plannedDate) {
                        totalExecutedOnTime++;
                    }
                }
            }
        }
    });

    const completionRate = totalPlannedInPeriod > 0 ? (totalExecutedInPeriod / totalPlannedInPeriod) * 100 : 0;
    const efficiencyRate = totalPlannedInPeriod > 0 ? (totalExecutedOnTime / totalPlannedInPeriod) * 100 : 0;

    return {
        totalPlanned: totalPlannedInPeriod,
        totalExecuted: totalExecutedInPeriod,
        totalOnTime: totalExecutedOnTime,
        completionRate: completionRate,
        efficiencyRate: efficiencyRate
    };
}

// ==============================================
// FUNÇÕES DE GRÁFICOS DE RENDERIZAÇÃO (manter as originais)
// ==============================================
function renderEfficiencyChart(projectsForPeriod, taskKey) {
    const ctx = document.getElementById('efficiencyChart');
    if (!ctx) return;
    
    const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
    const taskLabels = ['KOM', 'Ferramental', 'CAD+BOM+FT', 'Try-out', 'Entrega', 'PSW', 'Handover'];
    
    const completionRates = [];
    const efficiencyRates = [];
    const labels = [];
    const backgroundColors = [
        'rgba(54, 162, 235, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(255, 159, 64, 0.7)',
        'rgba(255, 99, 132, 0.7)',
        'rgba(201, 203, 207, 0.7)',
        'rgba(255, 205, 86, 0.7)'
    ];
    
    const plannedTasks = [];
    const executedTasks = [];
    const onTimeTasks = [];
    const labelTextsCompletion = [];
    const labelTextsEfficiency = [];
    
    taskKeys.forEach((taskKey, index) => {
        const stats = calculateTaskStatsForPeriod(projectsForPeriod, taskKey);
        completionRates.push(stats.completionRate);
        efficiencyRates.push(stats.efficiencyRate);
        labels.push(taskLabels[index]);
        plannedTasks.push(stats.totalPlanned);
        executedTasks.push(stats.totalExecuted);
        onTimeTasks.push(stats.totalOnTime);
        
        labelTextsCompletion.push(`${stats.totalExecuted}/${stats.totalPlanned}\n(${stats.completionRate.toFixed(0)}%)`);
        labelTextsEfficiency.push(`${stats.totalOnTime}/${stats.totalPlanned}\n(${stats.efficiencyRate.toFixed(0)}%)`);
    });
    
    if (efficiencyChart) {
        efficiencyChart.destroy();
    }
    
    const totalEfficiency = efficiencyRates.reduce((sum, rate) => sum + rate, 0) / efficiencyRates.length;
    document.getElementById('efficiencyValue').textContent = `${totalEfficiency.toFixed(1)}%`;
    
    // Criar o gráfico
    efficiencyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Taxa de Conclusão (%)',
                    data: completionRates,
                    backgroundColor: 'rgba(76, 175, 80, 0.75)',
                    borderColor: 'rgba(56, 142, 60, 1)',
                    borderWidth: 2,
                    yAxisID: 'y'
                },
                {
                    label: 'Eficiência Real (%)',
                    data: efficiencyRates,
                    backgroundColor: 'rgba(33, 150, 243, 0.75)',
                    borderColor: 'rgba(25, 118, 210, 1)',
                    borderWidth: 2,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: `Comparativo de Conclusão vs Eficiência`
                },
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            const index = context.dataIndex;
                            const datasetIndex = context.datasetIndex;
                            
                            if (datasetIndex === 0) {
                                return `Concluídas: ${executedTasks[index]}/${plannedTasks[index]} | Taxa: ${completionRates[index].toFixed(1)}%`;
                            } else {
                                return `No prazo: ${onTimeTasks[index]}/${plannedTasks[index]} | Eficiência: ${efficiencyRates[index].toFixed(1)}%`;
                            }
                        }
                    }
                },
                datalabels: {
                    anchor: 'center',
                    align: 'center',
                    color: function(context) {
                        return context.datasetIndex === 0 ? 'white' : 'black';
                    },
                    font: {
                        weight: 'bold',
                        size: 10
                    },
                    formatter: function(value, context) {
                        if (context.datasetIndex === 0) {
                            return labelTextsCompletion[context.dataIndex];
                        } else {
                            return labelTextsEfficiency[context.dataIndex];
                        }
                    }
                }
            },
            onClick: function(evt, elements) {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const taskName = taskLabels[index];
                    const taskKeyName = taskKeys[index];
                    
                    // Filtrar projetos que têm essa tarefa
                    const taskProjectsList = projectsForPeriod.filter(p => {
                        const task = p.tasks?.[taskKeyName];
                        return task && task.planned;
                    });
                    
                    showProjectsModal(taskProjectsList, `Projetos com Tarefa: ${taskName}`, taskKeyName);
                }
            }
        },
        plugins: [ChartDataLabels]
    });
    
    // Adicionar evento de clique nos números do datalabels manualmente
    setTimeout(() => {
        const canvas = ctx;
        canvas.addEventListener('click', function(event) {
            const activePoints = efficiencyChart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true);
            if (activePoints.length === 0) {
                // Se não clicou em nenhuma barra, verificar se clicou em algum número
                const rect = canvas.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                
                // Procurar por números nas posições aproximadas
                const chartArea = efficiencyChart.chartArea;
                const xAxis = efficiencyChart.scales.x;
                const yAxis = efficiencyChart.scales.y;
                
                // Para cada tarefa, verificar se o clique está próximo da posição do número
                for (let i = 0; i < labels.length; i++) {
                    const xPos = xAxis.getPixelForValue(i);
                    const yPosCompletion = yAxis.getPixelForValue(completionRates[i]);
                    const yPosEfficiency = yAxis.getPixelForValue(efficiencyRates[i]);
                    
                    // Distância aproximada do número
                    const tolerance = 15;
                    
                    // Verificar clique no número da conclusão
                    if (Math.abs(x - xPos) < tolerance && Math.abs(y - yPosCompletion) < tolerance) {
                        const taskKeyName = taskKeys[i];
                        const taskProjectsList = projectsForPeriod.filter(p => {
                            const task = p.tasks?.[taskKeyName];
                            return task && task.planned;
                        });
                        showProjectsModal(taskProjectsList, `Projetos com Tarefa: ${labels[i]} (Conclusão)`, taskKeyName);
                        return;
                    }
                    
                    // Verificar clique no número da eficiência
                    if (Math.abs(x - xPos) < tolerance && Math.abs(y - yPosEfficiency) < tolerance) {
                        const taskKeyName = taskKeys[i];
                        const taskProjectsList = projectsForPeriod.filter(p => {
                            const task = p.tasks?.[taskKeyName];
                            return task && task.planned;
                        });
                        showProjectsModal(taskProjectsList, `Projetos com Tarefa: ${labels[i]} (Eficiência)`, taskKeyName);
                        return;
                    }
                }
            }
        });
    }, 500);
}

function renderProjectStatusChart(projectsForPeriod) {
    const ctx = document.getElementById('projectStatusChart');
    if (!ctx) return;
    
    const statusCounts = {
        'Concluído': 0,
        'No Prazo': 0,
        'Em Andamento': 0,
        'Atrasado': 0,
        'Pendente': 0,
        'Em Espera': 0,
        'Cancelado': 0
    };
    
    projectsForPeriod.forEach(p => {
        statusCounts[p.status] = (statusCounts[p.status] || 0) + 1;
    });
    
    const totalProjects = projectsForPeriod.length;
    const completedProjects = statusCounts['Concluído'] || 0;
    const completedPercentage = totalProjects > 0 ? (completedProjects / totalProjects) * 100 : 0;
    
    document.getElementById('completedProjectsValue').textContent = `${completedPercentage.toFixed(1)}%`;
    
    if (projectStatusChart) {
        projectStatusChart.destroy();
    }
    
    const backgroundColorMap = {
        'Concluído': 'rgba(75, 192, 192, 0.7)',
        'No Prazo': 'rgba(255, 206, 86, 0.7)',
        'Em Andamento': 'rgba(54, 162, 235, 0.7)',
        'Atrasado': 'rgba(255, 99, 132, 0.7)',
        'Pendente': 'rgba(201, 203, 207, 0.7)',
        'Em Espera': 'rgba(255, 152, 0, 0.7)',
        'Cancelado': 'rgba(158, 158, 158, 0.7)'
    };
    
    const borderColorMap = {
        'Concluído': 'rgba(75, 192, 192, 1)',
        'No Prazo': 'rgba(255, 206, 86, 1)',
        'Em Andamento': 'rgba(54, 162, 235, 1)',
        'Atrasado': 'rgba(255, 99, 132, 1)',
        'Pendente': 'rgba(201, 203, 207, 1)',
        'Em Espera': 'rgba(255, 152, 0, 1)',
        'Cancelado': 'rgba(158, 158, 158, 1)'
    };
    
    const activeStatuses = Object.keys(statusCounts).filter(status => statusCounts[status] > 0);
    
    projectStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: activeStatuses.map(status => `${status} (${statusCounts[status]})`),
            datasets: [{
                data: activeStatuses.map(status => statusCounts[status]),
                backgroundColor: activeStatuses.map(status => backgroundColorMap[status]),
                borderColor: activeStatuses.map(status => borderColorMap[status]),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: `Status dos Projetos - Total: ${projectsForPeriod.length}`
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label.replace(` (${value})`, '')}: ${value} projetos (${percentage}%)`;
                        }
                    }
                },
                datalabels: {
                    display: true,
                    color: '#fff',
                    font: {
                        weight: 'bold'
                    },
                    formatter: function(value, context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = Math.round((value / total) * 100);
                        return percentage + '%';
                    }
                }
            },
            onClick: function(evt, elements) {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const statusName = activeStatuses[index];
                    const statusProjectsList = projectsForPeriod.filter(p => p.status === statusName);
                    showProjectsModal(statusProjectsList, `Projetos com Status: ${statusName}`);
                }
            }
        },
        plugins: [ChartDataLabels]
    });
}

function renderLeaderChart(projectsForPeriod) {
    const ctx = document.getElementById('leaderChart');
    if (!ctx) return;
    
    const leaderStats = {};
    const labelTexts = [];
    
    projectsForPeriod.forEach(project => {
        if (project.leaderId) {
            const leader = leaders.find(l => l.id == project.leaderId);
            if (leader) {
                if (!leaderStats[leader.name]) {
                    leaderStats[leader.name] = {
                        total: 0,
                        completed: 0
                    };
                }
                leaderStats[leader.name].total++;
                if (project.status === "Concluído") {
                    leaderStats[leader.name].completed++;
                }
            }
        }
    });
    
    const leaderNames = Object.keys(leaderStats);
    const efficiencies = [];
    const completedCounts = [];
    const totalCounts = [];
    
    leaderNames.forEach(leaderName => {
        const stats = leaderStats[leaderName];
        const efficiency = stats.total > 0 ? (stats.completed / stats.total) * 100 : 0;
        efficiencies.push(efficiency);
        completedCounts.push(stats.completed);
        totalCounts.push(stats.total);
        
        labelTexts.push(`${stats.completed}/${stats.total}\n(${efficiency.toFixed(0)}%)`);
    });
    
    if (leaderChart) {
        leaderChart.destroy();
    }
    
    if (leaderNames.length === 0) {
        ctx.parentElement.innerHTML = '<div class="chart-title">Eficiência por Líder (Concluído / Planejado)</div><p style="text-align:center;padding:20px">Nenhum dado disponível para os filtros selecionados</p>';
        leaderChart = null;
        return;
    }
    
    leaderChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: leaderNames,
            datasets: [{
                label: 'Eficiência de Projetos (%)',
                data: efficiencies,
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Eficiência por Líder (Concluído / Planejado)'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Eficiência: ${context.raw.toFixed(1)}%`;
                        },
                        afterLabel: function(context) {
                            const index = context.dataIndex;
                            return `Projetos concluídos: ${completedCounts[index]}/${totalCounts[index]}`;
                        }
                    }
                },
                datalabels: {
                    anchor: 'center',
                    align: 'center',
                    color: 'white',
                    font: {
                        weight: 'bold',
                        size: 11
                    },
                    formatter: function(value, context) {
                        return labelTexts[context.dataIndex];
                    }
                }
            },
            onClick: function(evt, elements) {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const leaderName = leaderNames[index];
                    const leaderProjectsList = projectsForPeriod.filter(p => {
                        const leader = leaders.find(l => l.id == p.leaderId);
                        return leader && leader.name === leaderName;
                    });
                    showProjectsModal(leaderProjectsList, `Projetos do Líder: ${leaderName}`);
                }
            }
        },
        plugins: [ChartDataLabels]
    });
    
    // Adicionar evento de clique nos números
    setTimeout(() => {
        const canvas = ctx;
        canvas.addEventListener('click', function(event) {
            const activePoints = leaderChart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true);
            if (activePoints.length === 0) {
                const rect = canvas.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                
                const chartArea = leaderChart.chartArea;
                const xAxis = leaderChart.scales.x;
                const yAxis = leaderChart.scales.y;
                
                for (let i = 0; i < leaderNames.length; i++) {
                    const xPos = xAxis.getPixelForValue(i);
                    const yPos = yAxis.getPixelForValue(efficiencies[i]);
                    
                    const tolerance = 15;
                    
                    if (Math.abs(x - xPos) < tolerance && Math.abs(y - yPos) < tolerance) {
                        const leaderName = leaderNames[i];
                        const leaderProjectsList = projectsForPeriod.filter(p => {
                            const leader = leaders.find(l => l.id == p.leaderId);
                            return leader && leader.name === leaderName;
                        });
                        showProjectsModal(leaderProjectsList, `Projetos do Líder: ${leaderName}`);
                        return;
                    }
                }
            }
        });
    }, 500);
}

function renderSegmentChart(projectsForPeriod) {
    const ctx = document.getElementById('segmentChart');
    if (!ctx) return;
    
    // Array completo de segmentos incluindo OEM
    const segments = ['Blindados', 'Autos', 'Agrícola', 'Ônibus & Caminhões', 'Trens', 'OEM'];
    const segmentStats = {};
    const labelTexts = [];
    
    segments.forEach(segment => {
        segmentStats[segment] = {
            total: 0,
            completed: 0
        };
    });
    
    projectsForPeriod.forEach(project => {
        if (project.segmento && segments.includes(project.segmento)) {
            segmentStats[project.segmento].total++;
            if (project.status === "Concluído") {
                segmentStats[project.segmento].completed++;
            }
        }
    });
    
    const segmentNames = [];
    const efficiencies = [];
    const completedCounts = [];
    const totalCounts = [];
    
    segments.forEach(segment => {
        if (segmentStats[segment].total > 0) {
            const stats = segmentStats[segment];
            const efficiency = stats.total > 0 ? (stats.completed / stats.total) * 100 : 0;
            segmentNames.push(segment);
            efficiencies.push(efficiency);
            completedCounts.push(stats.completed);
            totalCounts.push(stats.total);
            
            labelTexts.push(`${stats.completed}/${stats.total}\n(${efficiency.toFixed(0)}%)`);
        }
    });
    
    if (segmentChart) {
        segmentChart.destroy();
    }
    
    segmentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: segmentNames,
            datasets: [{
                label: 'Eficiência de Projetos (%)',
                data: efficiencies,
                backgroundColor: 'rgba(75, 192, 192, 0.8)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Eficiência por Segmento (Concluído / Planejado)'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Eficiência: ${context.raw.toFixed(1)}%`;
                        },
                        afterLabel: function(context) {
                            const index = context.dataIndex;
                            return `Projetos concluídos: ${completedCounts[index]}/${totalCounts[index]}`;
                        }
                    }
                },
                datalabels: {
                    anchor: 'center',
                    align: 'center',
                    color: 'white',
                    font: {
                        weight: 'bold',
                        size: 11
                    },
                    formatter: function(value, context) {
                        return labelTexts[context.dataIndex];
                    }
                }
            },
            onClick: function(evt, elements) {
                if (elements.length > 0) {
                    const index = elements[0].index;
                    const segmentName = segmentNames[index];
                    const segmentProjectsList = projectsForPeriod.filter(p => p.segmento === segmentName);
                    showProjectsModal(segmentProjectsList, `Projetos do Segmento: ${segmentName}`);
                }
            }
        },
        plugins: [ChartDataLabels]
    });
    
    // Adicionar evento de clique nos números
    setTimeout(() => {
        const canvas = ctx;
        canvas.addEventListener('click', function(event) {
            const activePoints = segmentChart.getElementsAtEventForMode(event, 'nearest', { intersect: true }, true);
            if (activePoints.length === 0) {
                const rect = canvas.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                
                const chartArea = segmentChart.chartArea;
                const xAxis = segmentChart.scales.x;
                const yAxis = segmentChart.scales.y;
                
                for (let i = 0; i < segmentNames.length; i++) {
                    const xPos = xAxis.getPixelForValue(i);
                    const yPos = yAxis.getPixelForValue(efficiencies[i]);
                    
                    const tolerance = 15;
                    
                    if (Math.abs(x - xPos) < tolerance && Math.abs(y - yPos) < tolerance) {
                        const segmentName = segmentNames[i];
                        const segmentProjectsList = projectsForPeriod.filter(p => p.segmento === segmentName);
                        showProjectsModal(segmentProjectsList, `Projetos do Segmento: ${segmentName}`);
                        return;
                    }
                }
            }
        });
    }, 500);
}

function showProjectsModal(projectsList, title, taskKey = null) {
    closeAllModals();
    const modal = document.getElementById('projectListModal');
    const modalTitle = document.getElementById('projectListModalTitle');
    const modalContent = document.getElementById('projectListModalContent');
    
    modalTitle.textContent = title;
    modalContent.innerHTML = '';
    
    if (!projectsList || projectsList.length === 0) {
        modalContent.innerHTML = '<p>Nenhum projeto encontrado.</p>';
        modal.style.display = 'block';
        return;
    }
    
    const showTaskStatus = taskKey !== null;
    
    const table = document.createElement('table');
    table.className = 'task-table';
    
    let tableHTML = `
        <thead>
            <tr>
                <th>ID</th>
                <th>Projeto</th>
                <th>Cliente</th>
                <th>Segmento</th>
                <th>Status do Projeto</th>
    `;
    
    if (showTaskStatus) {
        tableHTML += `<th>Status da Tarefa (${taskKey.toUpperCase()})</th>`;
    }
    
    tableHTML += `
                <th>Líder</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
    `;
    
    projectsList.forEach(project => {
        const projectStatus = project.status;
        let taskStatus = '';
        
        if (showTaskStatus) {
            const task = project.tasks?.[taskKey];
            taskStatus = calculateTaskStatus(task, project.status);
        }
        
        tableHTML += `
            <tr>
                <td>${project.id}</td>
                <td>${project.projectName || '-'}</td>
                <td>${project.cliente || '-'}</td>
                <td>${project.segmento || '-'}</td>
                <td><span class="status status-${projectStatus.toLowerCase().replace(/\s/g, '-')}">${projectStatus}</span></td>
        `;
        
        if (showTaskStatus) {
            tableHTML += `<td><span class="status status-${taskStatus.toLowerCase().replace(/\s/g, '-')}">${taskStatus}</span></td>`;
        }
        
        tableHTML += `
                <td>${project.projectLeader || '-'}</td>
                <td>
                    <button class="btn btn-primary btn-sm" onclick="editProject(${project.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-chart btn-sm" onclick="showTimeline(${project.id})">
                        <i class="fas fa-calendar-alt"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tableHTML += '</tbody>';
    table.innerHTML = tableHTML;
    
    modalContent.appendChild(table);
    modal.style.display = 'block';
}

// ==============================================

