<?php
require_once 'auth.php';
$usuario = getUsuario();
$csrfToken = viabixGetCsrfToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Controle de Projetos Completo - MySQL</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.0.2"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
window.viabixCsrfToken = <?php echo json_encode($csrfToken); ?>;
window.viabixProjectUser = {
    nivel: <?php echo json_encode($usuario['nivel']); ?>,
    nome: <?php echo json_encode($usuario['nome']); ?>
};
</script>
<link href="../assets/css/projects-control.css?v=20260625" rel="stylesheet"/>

<link href="../assets/css/viabix-theme.css?v=20260625" rel="stylesheet"/>
</head>
<body>
<div id="projectLoadingOverlay" class="project-loading-overlay" role="status" aria-live="polite" aria-hidden="true">
    <div class="project-loading-card">
        <div class="project-loading-spinner" aria-hidden="true"></div>
        <div>
            <div class="project-loading-title">Processando</div>
            <div id="projectLoadingMessage" class="project-loading-message">Aguarde um instante...</div>
        </div>
    </div>
</div>
<!-- MENU DE NAVEGAÇÃO INTEGRADO -->
<nav style="background: linear-gradient(135deg, #0a3d2e 0%, #1b5e20 100%); padding: 10px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.2);">
    <div style="max-width: 100%; padding: 0 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <a href="../dashboard.html" style="color: white; text-decoration: none; display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 1.1rem;">
                    <i class="fas fa-industry"></i>
                    Viabix
                </a>
                <span style="color: rgba(255,255,255,0.4);">|</span>
                <a href="../dashboard.html" style="color: rgba(255,255,255,0.8); text-decoration: none; padding: 5px 12px; border-radius: 5px; transition: 0.3s; display: flex; align-items: center; gap: 5px;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="../anvi.html" style="color: rgba(255,255,255,0.8); text-decoration: none; padding: 5px 12px; border-radius: 5px; transition: 0.3s; display: flex; align-items: center; gap: 5px;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'">
                    <i class="fas fa-calculator"></i> ANVI
                </a>
                <a href="index.php" style="color: white; text-decoration: none; padding: 5px 12px; border-radius: 5px; background: rgba(255,255,255,0.2); display: flex; align-items: center; gap: 5px;">
                    <i class="fas fa-project-diagram"></i> Projetos
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container">
<header>
<div class="logo">
<img id="logoImage" class="logo-image" alt="Logotipo da Empresa" src="" style="display:none;">
<i class="fas fa-project-diagram"></i>
<div>
<h1 style="font-size:1.2rem;margin-bottom:4px">Controle de Projetos Completo - MySQL</h1>
<div style="font-size:.85rem;opacity:.9">Gestão de projetos com múltiplos líderes e Gantt</div>
</div>
</div>
<div style="flex: 1; display: flex; align-items: center; justify-content: flex-end; gap: 15px; margin-right: 15px;">
<div style="text-align: right; line-height: 1.3;">
<div style="font-size: 0.95rem; font-weight: 600;">
<i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($usuario['nome']); ?>
</div>
<div style="font-size: 0.75rem; opacity: 0.85; text-transform: uppercase;">
<?php 
$nivel_texto = ['admin' => 'Administrador', 'lider' => 'Líder', 'visualizador' => 'Visualizador'];
echo $nivel_texto[$usuario['nivel']] ?? $usuario['nivel']; 
?>
</div>
</div>
<a href="logout.php" class="btn btn-danger" style="text-decoration: none; padding: 8px 15px; font-size: 0.85rem;">
<i class="fas fa-sign-out-alt"></i> Sair
</a>
</div>
<div class="header-buttons">
<?php if (!isVisualizador()): ?>
<button class="btn btn-primary" id="addProjectBtn"><i class="fas fa-plus"></i> Novo Projeto</button>
<?php endif; ?>
<button class="btn btn-success" id="btnVerANVI" style="display: none;">
    <i class="fas fa-link"></i> Ver ANVI Vinculada
</button>
<?php if (isAdmin()): ?>
<button class="btn btn-info" id="manageLeadersBtn"><i class="fas fa-users"></i> Gerenciar Líderes</button>
<a href="usuarios_manager.php" class="btn btn-warning" style="text-decoration: none;"><i class="fas fa-users-cog"></i> Gerenciar Usuários</a>
<?php endif; ?>
<?php if (!isVisualizador()): ?>
<button class="btn btn-success" id="saveDataBtn"><i class="fas fa-save"></i> Salvar no MySQL</button>
<?php endif; ?>
<button class="btn btn-warning" id="loadDataBtn"><i class="fas fa-download"></i> Carregar do MySQL</button>
<button class="btn btn-primary" id="exportExcelBtn"><i class="fas fa-file-excel"></i> Exportar Excel</button>
<?php if (!isVisualizador()): ?>
<button class="btn btn-info" id="importExcelBtn"><i class="fas fa-file-import"></i> Importar Excel</button>
<?php endif; ?>
<button class="btn btn-info" id="showChartsBtn"><i class="fas fa-chart-bar"></i> Gráficos</button>
<button class="btn btn-info" id="toggleFiltersBtn"><i class="fas fa-filter"></i> Filtros</button>
<?php if (isAdmin()): ?>
<button class="btn btn-info" id="loadLogoBtn"><i class="fas fa-image"></i> Logotipo</button>
<?php endif; ?>
</div>
</header>

<!-- Indicador de status MySQL -->
<div style="background: #e8f5e9; padding: 5px 15px; border-bottom: 1px solid #c8e6c9; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <i class="fas fa-database"></i> <strong>Status MySQL:</strong> 
        <span id="mysqlStatus" class="mysql-status checking">Verificando...</span>
    </div>
    <div style="display: flex; gap: 8px;">
        <button class="btn btn-sm" id="testMysqlConnection" style="background: #2196f3; color: white;">
            <i class="fas fa-plug"></i> Testar Conexão
        </button>
        <button class="btn btn-sm" onclick="debugApqpData()" style="background: #ff9800; color: white;">
            <i class="fas fa-bug"></i> Debug APQP
        </button>
    </div>
</div>

<div class="summary">
<div class="summary-card"><h3 style="font-size:.85rem;color:#666">Total de Projetos</h3><p id="totalProjects">0</p></div>
<div class="summary-card"><h3 style="font-size:.85rem;color:#666">No Prazo</h3><p id="onTimeProjects">0</p></div>
<div class="summary-card"><h3 style="font-size:.85rem;color:#666">Atrasados</h3><p id="delayedProjects">0</p></div>
<div class="summary-card"><h3 style="font-size:.85rem;color:#666">Concluídos</h3><p id="completedProjects">0</p></div>
<div class="summary-card summary-card-espera"><h3 style="font-size:.85rem;color:#666">Em Espera</h3><p id="onHoldProjects">0</p></div>
<div class="summary-card summary-card-cancelado"><h3 style="font-size:.85rem;color:#666">Cancelados</h3><p id="cancelledProjects">0</p></div>
<div class="summary-card summary-card-em-andamento"><h3 style="font-size:.85rem;color:#666">Em Andamento</h3><p id="inProgressProjects">0</p></div>
<div class="summary-card summary-card-pendente"><h3 style="font-size:.85rem;color:#666">Pendentes</h3><p id="pendingProjects">0</p></div>
<div class="summary-card"><h3 style="font-size:.85rem;color:#666">Líderes Cadastrados</h3><p id="totalLeaders">0</p></div>
<div class="summary-card summary-card-efficiency"><h3 style="font-size:.85rem;color:#666">Eficiência das Tarefas</h3><p id="tasksEfficiency">0%</p></div>
<div class="summary-card summary-card-efficiency"><h3 style="font-size:.85rem;color:#666">Eficiência de Projetos</h3><p id="projectsEfficiency">0%</p></div>
</div>

<div class="filters-collapsible" id="filtersContainer">
<div class="controls">
<div class="filters-section">
<div class="filters-title"><i class="fas fa-filter"></i> Filtros de Projetos</div>
<div class="filters-grid">
<div class="filter-group">
<label for="idFilter">ID do Projeto</label>
<input id="idFilter" placeholder="Digite o ID"/>
</div>
<div class="filter-group">
<label for="projectFilter">Nome do Projeto</label>
<input id="projectFilter" placeholder="Digite o nome"/>
</div>
<div class="filter-group">
<label for="segmentoFilter">Segmento</label>
<select id="segmentoFilter">
<option value="todos">Todos os Segmentos</option>
<option>Blindados</option><option>Autos</option><option>Agrícola</option><option>Ônibus &amp; Caminhões</option><option>Trens</option><option>OEM</option>
</select>
</div>
<div class="filter-group">
<label for="leaderFilter">Líder</label>
<select id="leaderFilter"><option value="todos">Todos os Líderes</option></select>
</div>
<div class="filter-group">
<label for="statusFilter">Status do Projeto</label>
<select id="statusFilter" multiple size="3">
<option value="Pendente">Pendente</option>
<option value="Em Andamento">Em Andamento</option>
<option value="No Prazo">No Prazo</option>
<option value="Atrasado">Atrasado</option>
<option value="Concluído">Concluído</option>
<option value="Em Espera">Em Espera</option>
<option value="Cancelado">Cancelado</option>
</select>
<div class="multi-select-controls">
<button class="multi-select-btn" onclick="selectAllStatuses()">Selecionar Todos</button>
<button class="multi-select-btn" onclick="clearAllStatuses()">Limpar</button>
</div>
</div>
<div class="filter-group">
<label for="search">Pesquisa Geral</label>
<input id="search" placeholder="Pesquisar..."/>
</div>
<div class="filter-group">
<label for="periodFilter">Período (Criação)</label>
<div class="date-range-inputs">
<input id="periodFilterFrom" type="date" placeholder="De"/>
<input id="periodFilterTo" type="date" placeholder="Até"/>
</div>
</div>
</div>
<div class="filter-actions">
<button class="btn btn-primary btn-sm" id="applyFiltersBtn"><i class="fas fa-filter"></i> Aplicar Filtros</button>
<button class="btn btn-danger btn-sm" id="clearAllFiltersBtn"><i class="fas fa-times"></i> Limpar Tudo</button>
</div>
</div>

<div class="date-filter-section">
<div class="date-filter-title"><i class="fas fa-tasks"></i> Filtros por Tarefa</div>
<div class="date-filter-grid">
<div class="filter-group">
<label for="dateFilterType">Tipo de Tarefa</label>
<select id="dateFilterType">
<option value="todos">Todas as Tarefas</option>
<option value="kom">KOM</option>
<option value="ferramental">Ferramental</option>
<option value="cadBomFt">CAD+BOM+FT</option>
<option value="tryout">Try-out</option>
<option value="entrega">Entrega</option>
<option value="psw">PSW</option>
<option value="handover">Handover</option>
</select>
</div>
<div class="filter-group">
<label for="taskSegmentoFilter">Segmento da Tarefa</label>
<select id="taskSegmentoFilter">
<option value="todos">Todos os Segmentos</option>
<option>Blindados</option><option>Autos</option><option>Agrícola</option><option>Ônibus &amp; Caminhões</option><option>Trens</option><option>OEM</option>
</select>
</div>
<div class="filter-group">
<label for="taskLeaderFilter">Líder da Tarefa</label>
<select id="taskLeaderFilter"><option value="todos">Todos os Líderes</option></select>
</div>
<div class="filter-group">
<label for="taskStatusFilter">Status da Tarefa</label>
<select id="taskStatusFilter" multiple size="3">
<option value="Pendente">Pendente</option>
<option value="Em Andamento">Em Andamento</option>
<option value="No Prazo">No Prazo</option>
<option value="Atrasado">Atrasado</option>
<option value="Concluído">Concluído</option>
<option value="Cancelado">Cancelado</option>
<option value="Em Espera">Em Espera</option>
</select>
<div class="multi-select-controls">
<button class="multi-select-btn" onclick="selectAllTaskStatuses()">Selecionar Todos</button>
<button class="multi-select-btn" onclick="clearAllTaskStatuses()">Limpar</button>
</div>
</div>
<div class="filter-group">
<label>Período da Tarefa</label>
<div class="date-range-inputs">
<input id="dateFilterFrom" type="date" placeholder="De"/>
<input id="dateFilterTo" type="date" placeholder="Até"/>
</div>
</div>
</div>

<div class="task-filter-count-container" id="taskFilterCountContainer" style="display: none;">
    <div class="task-filter-count-item">
        <span class="task-filter-count-label">Projetos filtrados:</span>
        <span class="task-filter-count-value" id="taskFilterCountTotal">0</span>
    </div>
    <div class="task-filter-count-item">
        <span class="task-filter-count-label">Por status selecionado:</span>
        <span class="task-filter-count-value" id="taskFilterCountByStatus">0</span>
    </div>
    <div class="task-filter-count-item">
        <span class="task-filter-count-label">Por segmento selecionado:</span>
        <span class="task-filter-count-value" id="taskFilterCountBySegment">0</span>
    </div>
    <div class="task-filter-count-item">
        <span class="task-filter-count-label">Por líder selecionado:</span>
        <span class="task-filter-count-value" id="taskFilterCountByLeader">0</span>
    </div>
</div>

<div class="filter-actions">
<button class="btn btn-primary btn-sm" id="applyDateFilterBtn"><i class="fas fa-filter"></i> Aplicar Filtro</button>
<button class="btn btn-danger btn-sm" id="clearDateFilterBtn"><i class="fas fa-times"></i> Limpar</button>
<span class="filter-status-count" id="taskStatusCount" style="display:none">
    <i class="fas fa-project-diagram"></i> Projetos: <span id="taskStatusCountValue">0</span>
</span>
</div>
</div>
</div>
</div>

<div aria-hidden="true" class="form-container" id="projectForm">
<h2 id="formTitle">Novo Projeto</h2>
<div class="form-grid">
<div class="form-group"><label>Cliente</label><input id="cliente"/></div>
<div class="form-group"><label>Projeto</label><input id="projectName"/></div>

<div class="form-group">
<label for="projectStatusSelect">Status do Projeto</label>
<select id="projectStatusSelect">
<option selected="" value="automatico">Automático</option>
<option value="em espera">Em Espera</option>
<option value="cancelado">Cancelado</option>
</select>
<small style="display:block; margin-top:4px; color:#666; font-size:0.8rem">Escolha Automático para que o status seja calculado conforme as tarefas.</small>
</div>

<div class="form-group"><label>Segmento</label>
<select id="segmento">
<option value="">--</option>
<option>Blindados</option>
<option>Autos</option>
<option>Agrícola</option>
<option>Ônibus &amp; Caminhões</option>
<option>Trens</option>
<option>OEM</option>
</select>
</div>
<div class="form-group"><label>Líder</label><select id="projectLeader"><option value="">Selecione</option></select></div>
<div class="form-group"><label>Código</label><input id="codigo"/></div>
<div class="form-group">
    <label>N° ANVI (Análise de Viabilidade)</label>
    <input id="anviNumber" list="projectAnviSuggestions" placeholder="Digite para buscar ANVIs disponíveis"/>
    <input id="anviId" type="hidden"/>
    <datalist id="projectAnviSuggestions"></datalist>
    <small style="display:block; margin-top:4px; color:#666; font-size:0.8rem">Escolha uma ANVI existente ou digite um número novo para uso apenas neste projeto.</small>
</div>
<div class="project-linked-anvi" id="projectLinkedAnviPanel">
    <div>
        <strong id="projectLinkedAnviTitle">ANVI vinculada</strong>
        <span id="projectLinkedAnviMeta">Selecione um projeto para consultar o vínculo.</span>
    </div>
    <button class="btn btn-success btn-sm" id="projectLinkedAnviOpenBtn" type="button">
        <i class="fas fa-external-link-alt"></i> Abrir ANVI
    </button>
</div>
<div class="form-group"><label>Modelo</label>
<select id="modelo">
<option value="">--</option>
<option>PBS</option><option>PBE</option><option>PBD</option>
<option>QDE</option><option>QDD</option>
<option>FDE</option><option>FDD</option>
<option>PDE</option><option>PDD</option>
<option>PTE</option><option>PTD</option><option>PTBE</option><option>PTBD</option>
<option>FTE</option><option>FTD</option>
<option>QTE</option><option>QTD</option>
<option>VGA</option>
<option>TSP</option><option>TSA</option><option>TSB</option><option>TSC</option>
<option>OLS</option>
<option>OUTROS</option>
</select>
</div>
<div class="form-group"><label>Processo</label>
<select id="processo">
<option value="">--</option>
<option>Laminado</option>
<option>Temperado</option>
<option>Laminado/Temperado</option>
<option>Blindado</option>
<option>Insulado</option>
</select>
</div>
<div class="form-group"><label>Fase</label><select id="fase"><option value="">--</option><option>Protótipo</option><option>Série</option></select></div>
<div class="form-group" style="grid-column:1/-1"><label>Observações</label><textarea id="observacoes" rows="3"></textarea></div>
</div>
<h3 style="margin-top:20px;padding-bottom:8px;border-bottom:2px solid #2e7d32">Datas e Duração das Tarefas</h3>
<p style="font-size:0.9rem;color:#666;margin-bottom:16px">Defina as datas planejadas, duração em dias e execução para cada tarefa do projeto.</p>

<!-- Tarefas KOM -->
<div class="task-group">
<div class="task-group-header">
<span>KOM - Kick-off Meeting</span>
<span class="status" id="komStatusCell">Pendente</span>
</div>
<div class="task-group-content">
<table class="task-table">
<thead>
<tr class="task-header">
<th colspan="2">Datas Planejadas</th>
<th>Duração</th>
<th colspan="2">Datas de Execução</th>
<th style="color:black">Ações</th>
</tr>
<tr class="task-header">
<th>Planejado</th>
<th>Replanejado</th>
<th>Dias</th>
<th>Início</th>
<th>Conclusão</th>
<th>Histórico</th>
</tr>
</thead>
<tbody>
<tr class="task-row">
<td>
<div class="date-group">
<span class="date-group-label">Original</span>
<input id="komPlanned" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Atual</span>
<input id="komPlanned2" readonly="" style="background-color:#f0f0f0;" type="date"/>
</div>
</td>
<td>
<div class="duration-group">
<input class="duration-field" id="komDuration" min="0" step="1" type="number" value="1">
<span class="duration-unit">dias</span>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Início</span>
<input id="komStart" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Conclusão</span>
<input id="komExecuted" type="date"/>
</div>
</td>
<td class="task-actions">
<button class="history-badge" onclick="showHistoryModal(0, 'kom')">0</button>
<button class="reschedule-btn" onclick="openRescheduleModal(0, 'kom')"><i class="fas fa-calendar-alt"></i> Replanejar</button>
</td>
</tr>
</tbody>
</table>
</div>
</div>

<!-- Tarefas FERRAMENTAL -->
<div class="task-group">
<div class="task-group-header">
<span>FERRAMENTAL - Desenvolvimento and preparação de ferramentais</span>
<span class="status" id="ferramentalStatusCell">Pendente</span>
</div>
<div class="task-group-content">
<table class="task-table">
<thead>
<tr class="task-header">
<th colspan="2">Datas Planejadas</th>
<th>Duração</th>
<th colspan="2">Datas de Execução</th>
<th style="color:black">Ações</th>
</tr>
<tr class="task-header">
<th>Planejado</th>
<th>Replanejado</th>
<th>Dias</th>
<th>Início</th>
<th>Conclusão</th>
<th>Histórico</th>
</tr>
</thead>
<tbody>
<tr class="task-row">
<td>
<div class="date-group">
<span class="date-group-label">Original</span>
<input id="ferramentalPlanned" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Atual</span>
<input id="ferramentalPlanned2" readonly="" style="background-color:#f0f0f0;" type="date"/>
</div>
</td>
<td>
<div class="duration-group">
<input class="duration-field" id="ferramentalDuration" min="0" step="1" type="number" value="5">
<span class="duration-unit">dias</span>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Início</span>
<input id="ferramentalStart" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Conclusão</span>
<input id="ferramentalExecuted" type="date"/>
</div>
</td>
<td class="task-actions">
<button class="history-badge" onclick="showHistoryModal(0, 'ferramental')">0</button>
<button class="reschedule-btn" onclick="openRescheduleModal(0, 'ferramental')"><i class="fas fa-calendar-alt"></i> Replanejar</button>
</td>
</tr>
</tbody>
</table>
<div class="resource-section">
<h4>Recursos do Ferramental</h4>
<div class="form-grid">
<div class="form-group">
<label>Fêmea:</label>
<input id="ferramentalFemea" type="date"/>
</div>
<div class="form-group">
<label>Gabarito Fanavid:</label>
<input id="ferramentalGabaritoFanavid" type="date"/>
</div>
<div class="form-group">
<label>Gabarito Usinado:</label>
<input id="ferramentalGabaritoUsinado" type="date"/>
</div>
<div class="form-group">
<label>Matriz:</label>
<input id="ferramentalMatriz" type="date"/>
</div>
<div class="form-group">
<label>Macho:</label>
<input id="ferramentalMacho" type="date"/>
</div>
<div class="form-group">
<label>Template:</label>
<input id="ferramentalTemplate" type="date"/>
</div>
<div class="form-group">
<label>Chapelona:</label>
<input id="ferramentalChapelona" type="date"/>
</div>
<div class="form-group">
<label>Plotter:</label>
<input id="ferramentalPlotter" type="date"/>
</div>
<div class="form-group">
<label>Tela:</label>
<input id="ferramentalTela" type="date"/>
</div>
</div>
</div>
</div>
</div>

<!-- Tarefas CAD+BOM+FT -->
<div class="task-group">
<div class="task-group-header">
<span>CAD+BOM+FT - Projeto CAD, Lista de Materiais (BOM) and Folha de Tempos (FT)</span>
<span class="status" id="cadBomFtStatusCell">Pendente</span>
</div>
<div class="task-group-content">
<table class="task-table">
<thead>
<tr class="task-header">
<th colspan="2">Datas Planejadas</th>
<th>Duração</th>
<th colspan="2">Datas de Execução</th>
<th style="color:black">Ações</th>
</tr>
<tr class="task-header">
<th>Planejado</th>
<th>Replanejado</th>
<th>Dias</th>
<th>Início</th>
<th>Conclusão</th>
<th>Histórico</th>
</tr>
</thead>
<tbody>
<tr class="task-row">
<td>
<div class="date-group">
<span class="date-group-label">Original</span>
<input id="cadBomFtPlanned" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Atual</span>
<input id="cadBomFtPlanned2" readonly="" style="background-color:#f0f0f0;" type="date"/>
</div>
</td>
<td>
<div class="duration-group">
<input class="duration-field" id="cadBomFtDuration" min="0" step="1" type="number" value="3">
<span class="duration-unit">dias</span>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Início</span>
<input id="cadBomFtStart" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Conclusão</span>
<input id="cadBomFtExecuted" type="date"/>
</div>
</td>
<td class="task-actions">
<button class="history-badge" onclick="showHistoryModal(0, 'cadBomFt')">0</button>
<button class="reschedule-btn" onclick="openRescheduleModal(0, 'cadBomFt')"><i class="fas fa-calendar-alt"></i> Replanejar</button>
</td>
</tr>
</tbody>
</table>
</div>
</div>

<!-- Tarefas TRY-OUT -->
<div class="task-group">
<div class="task-group-header">
<span>TRY-OUT - Testes e ajustes dos ferramentais</span>
<span class="status" id="tryoutStatusCell">Pendente</span>
</div>
<div class="task-group-content">
<div class="form-grid">
<div class="form-group">
<label>Número do Try-out:</label>
<input id="tryoutNumber" placeholder="Número" type="text"/>
</div>
<div class="form-group">
<label>Quantidade de Entrada de Peças:</label>
<input id="tryoutQuantidadeEntrada" type="number" min="0" step="1" placeholder="0"/>
</div>
<div class="form-group">
<label>Quantidade de Saída de Peças:</label>
<input id="tryoutQuantidadeSaida" type="number" min="0" step="1" placeholder="0"/>
</div>
</div>
<table class="task-table">
<thead>
<tr class="task-header">
<th colspan="2">Datas Planejadas</th>
<th>Duração</th>
<th colspan="2">Datas de Execução</th>
<th style="color:black">Ações</th>
</tr>
<tr class="task-header">
<th>Planejado</th>
<th>Replanejado</th>
<th>Dias</th>
<th>Início</th>
<th>Conclusão</th>
<th>Histórico</th>
</tr>
</thead>
<tbody>
<tr class="task-row">
<td>
<div class="date-group">
<span class="date-group-label">Original</span>
<input id="tryoutPlanned" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Atual</span>
<input id="tryoutPlanned2" readonly="" style="background-color:#f0f0f0;" type="date"/>
</div>
</td>
<td>
<div class="duration-group">
<input class="duration-field" id="tryoutDuration" min="0" step="1" type="number" value="3">
<span class="duration-unit">dias</span>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Início</span>
<input id="tryoutStart" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Conclusão</span>
<input id="tryoutExecuted" type="date"/>
</div>
</td>
<td class="task-actions">
<button class="history-badge" onclick="showHistoryModal(0, 'tryout')">0</button>
<button class="reschedule-btn" onclick="openRescheduleModal(0, 'tryout')"><i class="fas fa-calendar-alt"></i> Replanejar</button>
</td>
</tr>
</tbody>
</table>
<div class="resource-section">
<h4>Recursos do Try-out</h4>
<div class="form-grid">
<div class="form-group">
<label>Corte:</label>
<select id="tryoutCorte">
<option value="">-- Selecione --</option>
<option>Bottero</option>
<option>Bottero+ Bystronic 02</option>
<option>Bottero+ Bystronic 04</option>
<option>Bottero+ Bystronic 05</option>
<option>Bottero+ Bystronic 06</option>
<option>Intermac - Jumbo</option>
</select>
</div>
<div class="form-group">
<label>Lapidação:</label>
<select id="tryoutLapidacao">
<option value="">-- Selecione --</option>
<option>Bystronic 02</option>
<option>Bystronic 04</option>
<option>Bystronic 05</option>
<option>Bystronic 06</option>
<option>Intermac 01 - P1</option>
<option>Intermac 02 - P1</option>
<option>Intermac 01 - P2</option>
<option>Lixa</option>
<option>Americana</option>
<option>Biseladora</option>
</select>
</div>
<div class="form-group">
<label>Furação / Rec:</label>
<select id="tryoutFuracao">
<option value="">-- Selecione --</option>
<option>Bystronic 02 + Intermac</option>
<option>Bystronic 05 + Intermac</option>
<option>Bystronic 05</option>
<option>Intermac + Toledo</option>
<option>Intermac</option>
<option>Toledo</option>
</select>
</div>
<div class="form-group">
<label>Montagem:</label>
<select id="tryoutMontagem">
<option value="">-- Selecione --</option>
<option>Autos</option>
<option>Arquitetura</option>
<option>Ônibus</option>
<option>Blindados</option>
</select>
</div>
<div class="form-group">
<label>Serigrafia:</label>
<select id="tryoutSerigrafia">
<option value="">-- Selecione --</option>
<option>Svécia</option>
<option>Cugher</option>
<option>Dip Tech</option>
<option>Manual</option>
</select>
</div>
<div class="form-group">
<label>Queima:</label>
<select id="tryoutQueima">
<option value="">-- Selecione --</option>
<option>F. Verical BLD</option>
<option>HTF</option>
</select>
</div>
<div class="form-group">
<label>Fornos:</label>
<select id="tryoutFornos">
<option value="">-- Selecione --</option>
<option>KBFO 1</option>
<option>KBFO 2</option>
<option>HTBS</option>
<option>HTF</option>
<option>ESU</option>
<option>MATRIX-P1</option>
<option>MATRIX-P2</option>
<option>GLASS ROBOT-P2</option>
<option>SCREEN MAX-P2</option>
<option>F1-P2</option>
<option>F2-P2</option>
<option>F3-P2</option>
<option>F4-P2</option>
<option>F6-P2</option>
<option>FB1-P2</option>
<option>FB2-P2</option>
<option>FB3-P2</option>
<option>F7-P2</option>
</select>
</div>
</div>
</div>
</div>
</div>

<!-- Tarefas ENTREGA -->
<div class="task-group">
<div class="task-group-header">
<span>ENTREGA - Entrega da Amostra</span>
<span class="status" id="entregaStatusCell">Pendente</span>
</div>
<div class="task-group-content">
<div class="form-group">
<label>Número da Entrega:</label>
<input id="entregaNumber" placeholder="Número" type="text"/>
</div>
<table class="task-table">
<thead>
<tr class="task-header">
<th colspan="2">Datas Planejadas</th>
<th>Duração</th>
<th colspan="2">Datas de Execução</th>
<th style="color:black">Ações</th>
</tr>
<tr class="task-header">
<th>Planejado</th>
<th>Replanejado</th>
<th>Dias</th>
<th>Início</th>
<th>Conclusão</th>
<th>Histórico</th>
</tr>
</thead>
<tbody>
<tr class="task-row">
<td>
<div class="date-group">
<span class="date-group-label">Original</span>
<input id="entregaPlanned" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Atual</span>
<input id="entregaPlanned2" readonly="" style="background-color:#f0f0f0;" type="date"/>
</div>
</td>
<td>
<div class="duration-group">
<input class="duration-field" id="entregaDuration" min="0" step="1" type="number" value="1">
<span class="duration-unit">dias</span>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Início</span>
<input id="entregaStart" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Conclusão</span>
<input id="entregaExecuted" type="date"/>
</div>
</td>
<td class="task-actions">
<button class="history-badge" onclick="showHistoryModal(0, 'entrega')">0</button>
<button class="reschedule-btn" onclick="openRescheduleModal(0, 'entrega')"><i class="fas fa-calendar-alt"></i> Replanejar</button>
</td>
</tr>
</tbody>
</table>
</div>
</div>

<!-- Tarefas PSW -->
<div class="task-group">
<div class="task-group-header">
<span>PSW - Part Submission Warrant</span>
<span class="status" id="pswStatusCell">Pendente</span>
</div>
<div class="task-group-content">
<table class="task-table">
<thead>
<tr class="task-header">
<th colspan="2">Datas Planejadas</th>
<th>Duração</th>
<th colspan="2">Datas de Execução</th>
<th style="color:black">Ações</th>
</tr>
<tr class="task-header">
<th>Planejado</th>
<th>Replanejado</th>
<th>Dias</th>
<th>Início</th>
<th>Conclusão</th>
<th>Histórico</th>
</tr>
</thead>
<tbody>
<tr class="task-row">
<td>
<div class="date-group">
<span class="date-group-label">Original</span>
<input id="pswPlanned" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Atual</span>
<input id="pswPlanned2" readonly="" style="background-color:#f0f0f0;" type="date"/>
</div>
</td>
<td>
<div class="duration-group">
<input class="duration-field" id="pswDuration" min="0" step="1" type="number" value="1">
<span class="duration-unit">dias</span>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Início</span>
<input id="pswStart" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Conclusão</span>
<input id="pswExecuted" type="date"/>
</div>
</td>
<td class="task-actions">
<button class="history-badge" onclick="showHistoryModal(0, 'psw')">0</button>
<button class="reschedule-btn" onclick="openRescheduleModal(0, 'psw')"><i class="fas fa-calendar-alt"></i> Replanejar</button>
</td>
</tr>
</tbody>
</table>
</div>
</div>

<!-- Tarefas HANDOVER -->
<div class="task-group">
<div class="task-group-header">
<span>HANDOVER - Transferência do projeto</span>
<span class="status" id="handoverStatusCell">Pendente</span>
</div>
<div class="task-group-content">
<table class="task-table">
<thead>
<tr class="task-header">
<th colspan="2">Datas Planejadas</th>
<th>Duração</th>
<th colspan="2">Datas de Execução</th>
<th style="color:black">Ações</th>
</tr>
<tr class="task-header">
<th>Planejado</th>
<th>Replanejado</th>
<th>Dias</th>
<th>Início</th>
<th>Conclusão</th>
<th>Histórico</th>
</tr>
</thead>
<tbody>
<tr class="task-row">
<td>
<div class="date-group">
<span class="date-group-label">Original</span>
<input id="handoverPlanned" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Atual</span>
<input id="handoverPlanned2" readonly="" style="background-color:#f0f0f0;" type="date"/>
</div>
</td>
<td>
<div class="duration-group">
<input class="duration-field" id="handoverDuration" min="0" step="1" type="number" value="1">
<span class="duration-unit">dias</span>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Início</span>
<input id="handoverStart" type="date"/>
</div>
</td>
<td>
<div class="date-group">
<span class="date-group-label">Conclusão</span>
<input id="handoverExecuted" type="date"/>
</div>
</td>
<td class="task-actions">
<button class="history-badge" onclick="showHistoryModal(0, 'handover')">0</button>
<button class="reschedule-btn" onclick="openRescheduleModal(0, 'handover')"><i class="fas fa-calendar-alt"></i> Replanejar</button>
</td>
</tr>
</tbody>
</table>
</div>
</div>

<!-- Seção de Estudo de Capabilidade -->
<div class="capability-section">
    <h3><i class="fas fa-chart-line"></i> Estudo de Capabilidade do Processo (APQP)</h3>
    <p style="color: #666; margin-bottom: 15px; font-size: 0.9rem;">
        Defina as características especiais do produto/processo e insira as medições para <strong>5 amostras, cada uma com 25 medições</strong> (total de 125 pontos).
    </p>
    
    <div class="study-date">
        <label for="capabilityStudyDate">Data do Estudo (manual):</label>
        <input type="date" id="capabilityStudyDate" value="">
        <small style="color: #666; margin-left: 10px;">Registre quando o estudo foi realizado</small>
    </div>
    
    <div class="project-info-capability" id="capabilityProjectInfo">
        <!-- Será preenchido dinamicamente -->
    </div>
    
    <div id="capabilityCharacteristics"></div>
    
    <button class="add-characteristic-btn" onclick="addCapabilityCharacteristic()">
        <i class="fas fa-plus"></i> Adicionar Característica Especial
    </button>
    
    <button class="btn btn-info" onclick="exportCapabilityToPDF()" style="margin-left: 10px;">
        <i class="fas fa-file-pdf"></i> Exportar Estudo de Capabilidade
    </button>
</div>

<div class="form-buttons">
<button class="btn btn-danger" id="cancelProjectBtn">Cancelar</button>
<?php if (!isVisualizador()): ?>
<button class="btn btn-primary" id="saveProjectBtn">Salvar Projeto</button>
<span class="save-status-pill" id="projectSaveStatus" aria-live="polite">
    <i class="fas fa-circle"></i>
    Pronto
</span>
<?php else: ?>
<button class="btn btn-primary" disabled style="opacity: 0.5; cursor: not-allowed;">Sem permissão para salvar</button>
<?php endif; ?>
</div>
</div>

<!-- Formulário de Líderes -->
<div aria-hidden="true" class="form-container" id="leadersForm">
<h2>Gerenciar Líderes</h2>
<div class="form-grid">
<div class="form-group"><label>Nome</label><input id="newLeaderName"/></div>
<div class="form-group"><label>Email</label><input id="newLeaderEmail" type="email"/></div>
<div class="form-group"><label>Departamento</label><input id="newLeaderDepartment"/></div>
</div>
<div class="form-buttons">
<button class="btn btn-danger" id="cancelLeaderBtn">Cancelar</button>
<button class="btn btn-primary" id="addLeaderBtn">Adicionar Líder</button>
</div>
<div style="margin-top:12px">
<h3>Líderes Cadastrados</h3>
<div id="leadersListContainer"></div>
</div>
</div>

<!-- Seção de Gráficos -->
<div class="charts-section" id="chartsSection">
<div class="charts-header">
<h2>Gráficos de Eficiência</h2>
<button class="btn btn-danger" id="closeChartsBtn"><i class="fas fa-times"></i> Fechar Gráficos</button>
</div>

<div class="chart-filters">
    <div class="filter-group">
        <label for="chartTaskFilter">Filtrar por Tarefa</label>
        <select id="chartTaskFilter">
            <option value="todos">Todas as Tarefas</option>
            <option value="kom">KOM</option>
            <option value="ferramental">Ferramental</option>
            <option value="cadBomFt">CAD+BOM+FT</option>
            <option value="tryout">Try-out</option>
            <option value="entrega">Entrega</option>
            <option value="psw">PSW</option>
            <option value="handover">Handover</option>
        </select>
    </div>
    
    <div class="filter-group">
        <label for="chartSegment">Segmento</label>
        <select id="chartSegment">
            <option value="todos">Todos</option>
            <option>Blindados</option>
            <option>Autos</option>
            <option>Agrícola</option>
            <option>Ônibus &amp; Caminhões</option>
            <option>Trens</option>
            <option>OEM</option>
        </select>
    </div>
    
    <div class="filter-group">
        <label for="chartDateFrom">De período</label>
        <input id="chartDateFrom" type="date"/>
    </div>
    
    <div class="filter-group">
        <label for="chartDateTo">Até período</label>
        <input id="chartDateTo" type="date"/>
    </div>
    
    <div class="filter-group">
        <label for="chartTaskStatus">Status da Tarefa</label>
        <select id="chartTaskStatus" multiple size="3">
            <option value="Pendente">Pendente</option>
            <option value="Em Andamento">Em Andamento</option>
            <option value="No Prazo">No Prazo</option>
            <option value="Atrasado">Atrasado</option>
            <option value="Concluído">Concluído</option>
            <option value="Cancelado">Cancelado</option>
            <option value="Em Espera">Em Espera</option>
        </select>
        <div class="multi-select-controls">
            <button class="multi-select-btn" onclick="selectAllChartTaskStatuses()">Selecionar Todos</button>
            <button class="multi-select-btn" onclick="clearAllChartTaskStatuses()">Limpar</button>
        </div>
    </div>
    
    <div class="filter-group">
        <label>&nbsp;</label>
        <button class="btn btn-primary" id="applyChartFilters" style="width:100%;">
            <i class="fas fa-filter"></i> Aplicar Filtros
        </button>
    </div>
</div>

<div class="chart-period-info">
    <i class="fas fa-info-circle"></i>
    <span id="periodInfoText">Os gráficos mostram os dados filtrados. Use os filtros acima para ajustar.</span>
</div>

<div class="charts-efficiency-section">
    <div class="charts-efficiency-cards">
        <div class="summary-card summary-card-efficiency">
            <h3 style="font-size:.85rem;color:#666">Eficiência de Projetos no Período</h3>
            <p id="periodProjectsEfficiency">0%</p>
        </div>
        <div class="summary-card summary-card-efficiency">
            <h3 style="font-size:.85rem;color:#666">Eficiência das Tarefas no Período</h3>
            <p id="periodTasksEfficiency">0%</p>
        </div>
    </div>
    
    <div class="charts-container">
        <div class="chart-card">
            <h3 class="chart-title">Comparativo de Conclusão vs Eficiência</h3>
            <div class="chart-period-info">
                <i class="fas fa-info-circle"></i>
                <span id="efficiencyChartInfo">Eficiência das tarefas no período: <span id="efficiencyValue">0%</span></span>
            </div>
            <canvas id="efficiencyChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h3 class="chart-title">Status dos Projetos <small>(Clique nas fatias para ver detalhes)</small></h3>
            <div class="chart-period-info">
                <i class="fas fa-info-circle"></i>
                <span id="projectStatusChartInfo">Projetos concluídos no período: <span id="completedProjectsValue">0%</span></span>
            </div>
            <canvas id="projectStatusChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h3 class="chart-title">Eficiência por Líder (Concluído / Planejado) <small>(Clique nas barras para ver detalhes)</small></h3>
            <canvas id="leaderChart"></canvas>
        </div>
        
        <div class="chart-card">
            <h3 class="chart-title">Eficiência por Segmento (Concluído / Planejado) <small>(Clique nas barras para ver detalhes)</small></h3>
            <canvas id="segmentChart"></canvas>
        </div>
    </div>
</div>
</div>

<!-- Tabela de Projetos -->
<div class="table-container">
<table>
<thead>
<tr>
<th>ID</th><th>Cliente</th><th>Projeto</th><th>Segmento</th><th>Líder</th><th>Código</th><th>ANVI</th><th>Modelo</th><th>Processo</th><th>Fase</th><th>Status</th><th>Observações</th>
<!-- KOM -->
<th class="column-group" style="color:black">KOM Status</th><th>KOM Planejado</th><th>KOM Duração</th><th>KOM Início</th><th>KOM Executado</th>
<!-- FERRAMENTAL -->
<th class="column-group" style="color:black">Ferramental Status</th><th>Ferramental Planejado</th><th>Ferramental Duração</th><th>Ferramental Início</th><th>Ferramental Executado</th>
<!-- Recursos do Ferramental -->
<th>Fêmea</th><th>Gab. Fanavid</th><th>Gab. Usinado</th><th>Matriz</th><th>Macho</th><th>Template</th><th>Chapelona</th><th>Plotter</th><th>Tela</th>
<!-- CAD+BOM+FT -->
<th class="column-group" style="color:black">CAD\+BOM\+FT Status</th><th>CAD+BOM+FT Planejado</th><th>CAD+BOM+FT Duração</th><th>CAD+BOM+FT Início</th><th>CAD+BOM+FT Executado</th>
<!-- TRY-OUT -->
<th class="column-group" style="color:black">Try-out Status</th>
<th>Quant. Entrada</th>
<th>Quant. Saída</th>
<th>Try-out Número</th><th>Try-out Planejado</th><th>Try-out Duração</th><th>Try-out Início</th><th>Try-out Executado</th>
<!-- Recursos do Try-out -->
<th>Corte</th><th>Lapidação</th><th>Furação/Rec</th><th>Montagem</th><th>Serigrafia</th><th>Queima</th><th>Fornos</th>
<!-- ENTREGA -->
<th class="column-group" style="color:black">Entrega da Amostra Status</th><th>Entrega da Amostra Número</th><th>Entrega da Amostra Planejado</th><th>Entrega da Amostra Duração</th><th>Entrega da Amostra Início</th><th>Entrega da Amostra Executado</th>
<!-- PSW -->
<th class="column-group" style="color:black">PSW Status</th><th>PSW Planejado</th><th>PSW Duração</th><th>PSW Início</th><th>PSW Executado</th>
<!-- HANDOVER -->
<th class="column-group" style="color:black">Handover Status</th><th>Handover Planejado</th><th>Handover Duração</th><th>Handover Início</th><th>Handover Executado</th>
<th class="column-group" style="color:black">Capabilidade</th>
<th class="column-group" style="color:black">Ações</th>
</tr>
</thead>
<tbody id="projectsTableBody">
<!-- Os projetos serão inseridos aqui via JavaScript -->
</tbody>
</table>
</div>
</div>

<!-- Modal de Histórico -->
<div class="modal" id="historyModal">
    <div class="modal-content">
        <span class="close" data-close="historyModal">×</span>
        <h3 id="historyModalTitle">Histórico</h3>
        <div id="historyContent"></div>
        <div id="historyFormContainer" style="display:none;">
            <h4>Adicionar/Editar Histórico</h4>
            <div class="form-grid">
                <div class="form-group">
                    <label>Data do Histórico</label>
                    <input type="date" id="historyDate">
                </div>
                <div class="form-group">
                    <label>Motivo</label>
                    <textarea id="historyReason" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Data Antiga</label>
                    <input type="date" id="historyOldDate">
                </div>
                <div class="form-group">
                    <label>Data Nova</label>
                    <input type="date" id="historyNewDate">
                </div>
            </div>
            <div class="form-buttons">
                <button class="btn btn-danger" id="cancelHistoryBtn">Cancelar</button>
                <button class="btn btn-primary" id="saveHistoryBtn">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Replanejamento -->
<div class="modal" id="rescheduleModal">
    <div class="modal-content">
        <span class="close" data-close="rescheduleModal">×</span>
        <h3 id="rescheduleModalTitle">Replanejar</h3>
        <p id="rescheduleTaskInfo">Tarefa:</p>
        <div class="form-group">
            <label>Data Planejada Atual</label>
            <input id="currentDate" readonly="" style="background-color: #f0f0f0;" type="text"/>
        </div>
        <div class="form-group">
            <label>Nova Data Planejada</label>
            <input id="newDate" type="date"/>
        </div>
        <div class="form-group"><label>Motivo</label><textarea id="rescheduleReason"></textarea></div>
        <div class="form-buttons">
            <button class="btn btn-danger" id="cancelRescheduleBtn">Cancelar</button>
            <button class="btn btn-primary" id="saveRescheduleBtn">Salvar</button>
        </div>
    </div>
</div>

<!-- Modal de Arquivo -->
<div class="modal" id="fileModal">
    <div class="modal-content">
        <span class="close" data-close="fileModal">×</span>
        <h3 id="fileModalTitle">Exportar</h3>
        <div id="fileModalContent"></div>
    </div>
</div>

<!-- Modal de Importação Excel -->
<div class="modal" id="excelImportModal">
    <div class="modal-content">
        <span class="close" data-close="excelImportModal">×</span>
        <h3>Importar Excel</h3>
        <div class="form-group">
            <label>Selecione o arquivo</label>
            <input accept=".xlsx,.xls" id="excelFile" type="file"/>
        </div>
        <div class="form-group">
            <label><input id="importOverwrite" type="checkbox"/> Sobrescrever dados</label>
        </div>
        <div class="form-buttons">
            <button class="btn btn-danger" id="cancelImportBtn">Cancelar</button>
            <button class="btn btn-primary" id="confirmImportBtn">Importar</button>
        </div>
    </div>
</div>

<!-- Modal de Status do Projeto -->
<div class="modal" id="projectStatusModal">
    <div class="modal-content">
        <span class="close" data-close="projectStatusModal">×</span>
        <h3>Projetos com status: <span id="modalStatusTitle"></span></h3>
        <div id="projectStatusModalList"></div>
    </div>
</div>

<!-- Modal de Lista de Projetos -->
<div class="modal" id="projectListModal">
    <div class="modal-content">
        <span class="close" data-close="projectListModal">×</span>
        <h3 id="projectListModalTitle">Projetos</h3>
        <div id="projectListModalContent"></div>
    </div>
</div>

<!-- Modal de Cronograma -->
<div class="modal" id="timelineModal">
    <div class="modal-content">
        <span class="close" data-close="timelineModal">×</span>
        <h3 id="timelineModalTitle">Cronograma do Projeto</h3>
        <div id="timelineProjectInfo" class="timeline-project-info"></div>
        <div id="ganttChartSection" class="gantt-chart">
            <div class="gantt-header">
                <div class="gantt-title">
                    <i class="fas fa-chart-gantt"></i> Gráfico de Gantt
                </div>
                <div class="gantt-controls">
                    <button class="gantt-scale-btn active" onclick="setGanttScale('week')">Semana</button>
                    <button class="gantt-scale-btn" onclick="setGanttScale('month')">Mês</button>
                    <button class="gantt-scale-btn" onclick="setGanttScale('quarter')">Trimestre</button>
                    <button class="gantt-scale-btn" onclick="toggleGanttLabels()">Ocultar Rótulos</button>
                </div>
            </div>
            <div id="ganttContainer" class="gantt-grid">
                <!-- Gantt será gerado dinamicamente -->
            </div>
        </div>
        <div id="timelineContainer" class="timeline-container"></div>
        <div class="timeline-actions">
            <button class="btn btn-success" id="generateHandoverReportBtn" onclick="generateHandoverReport()">
                <i class="fas fa-clipboard-check"></i> Gerar Relatório Handover
            </button>
            <button class="btn btn-info" id="showCapabilityBtn" onclick="showCapabilityModal()">
                <i class="fas fa-chart-line"></i> Estudo de Capabilidade
            </button>
            <button class="btn btn-primary" id="printTimelineBtn" onclick="printTimeline()">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <button class="btn btn-warning" id="generatePdfBtn" onclick="showPdfOptions()">
                <i class="fas fa-file-pdf"></i> Gerar PDF Completo
            </button>
        </div>
    </div>
</div>

<!-- Modal de Logotipo -->
<div class="modal" id="logoModal">
    <div class="modal-content logo-modal-content">
        <span class="close" data-close="logoModal">×</span>
        <h3>Configuração do Logotipo</h3>
        
        <div class="form-group">
            <label for="logoFile">Selecione um arquivo de imagem:</label>
            <input type="file" id="logoFile" accept="image/*" class="form-control">
            <small>Formatos suportados: JPG, PNG, GIF, SVG. Tamanho recomendado: 300x150px</small>
        </div>
        
        <div class="logo-preview-container">
            <img id="logoPreview" class="logo-preview" src="" alt="Pré-visualização do Logotipo">
        </div>
        
        <div class="form-group">
            <label for="logoSize">Tamanho do Logotipo (px):</label>
            <input type="range" id="logoSize" min="30" max="150" value="50" class="form-control">
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-top: 5px;">
                <span>Pequeno</span>
                <span id="logoSizeValue">50px</span>
                <span>Grande</span>
            </div>
        </div>
        
        <div class="logo-controls">
            <button class="btn btn-danger" id="removeLogoBtn">
                <i class="fas fa-trash"></i> Remover Logotipo
            </button>
            <button class="btn btn-primary" id="saveLogoBtn">
                <i class="fas fa-save"></i> Salvar Logotipo
            </button>
        </div>
        
        <div style="margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-radius: 5px; font-size: 0.85rem;">
            <p><strong>Nota:</strong> O logotipo será exibido no cabeçalho da aplicação e também será incluído automaticamente em todos os PDFs gerados.</p>
        </div>
    </div>
</div>

<!-- Modal de Análise APQP -->
<div class="modal" id="apqpModal">
    <div class="modal-content">
        <span class="close" data-close="apqpModal">×</span>
        <h3 id="apqpModalTitle">Análise APQP</h3>
        
        <div class="apqp-summary">
            <div class="apqp-summary-item">
                <span class="apqp-summary-label">Total de Perguntas:</span>
                <span class="apqp-summary-value" id="apqpTotalQuestions">0</span>
            </div>
            <div class="apqp-summary-item">
                <span class="apqp-summary-label">Respondidas:</span>
                <span class="apqp-summary-value" id="apqpAnsweredQuestions">0</span>
            </div>
            <div class="apqp-summary-item">
                <span class="apqp-summary-label">Status:</span>
                <span class="apqp-summary-value" id="apqpStatusValue">Não Iniciado</span>
            </div>
        </div>
        
        <div id="apqpQuestionsContainer" class="apqp-questions-container">
        </div>
        
        <div class="form-buttons">
            <button class="btn btn-danger" id="cancelApqpBtn">
                <i class="fas fa-times"></i> Cancelar
            </button>
            <button class="btn btn-primary" id="saveApqpBtn">
                <i class="fas fa-save"></i> Salvar Análise APQP
            </button>
        </div>
    </div>
</div>

<!-- Modal de Relatório Handover -->
<div class="modal" id="handoverReportModal">
    <div class="modal-content">
        <span class="close" data-close="handoverReportModal">×</span>
        <h2 id="handoverReportTitle">Relatório Handover - Transferência do Projeto</h2>
        
        <div id="handoverReportContent">
            <!-- O conteúdo será gerado dinamicamente -->
        </div>
        
        <div class="handover-report-actions">
            <button class="btn btn-primary" id="printHandoverReportBtn" onclick="printHandoverReport()">
                <i class="fas fa-print"></i> Imprimir Relatório
            </button>
            <button class="btn btn-success" id="generateHandoverPdfBtn" onclick="generateHandoverReportPDF()">
                <i class="fas fa-file-pdf"></i> Gerar PDF
            </button>
        </div>
    </div>
</div>

<!-- Modal de Estudo de Capabilidade -->
<div class="modal" id="capabilityModal">
    <div class="modal-content">
        <span class="close" data-close="capabilityModal">×</span>
        <h3 id="capabilityModalTitle">Estudo de Capabilidade do Processo</h3>
        
        <div id="capabilityModalContent">
            <!-- Conteúdo dinâmico -->
        </div>
        
        <div class="form-buttons">
            <button class="btn btn-danger" onclick="document.getElementById('capabilityModal').style.display='none'">
                <i class="fas fa-times"></i> Fechar
            </button>
            <button class="btn btn-primary" onclick="exportCapabilityToPDF()">
                <i class="fas fa-file-pdf"></i> Exportar PDF
            </button>
        </div>
    </div>
</div>

<!-- Modal de Opções PDF -->
<div class="modal" id="pdfOptionsModal">
    <div class="modal-content">
        <span class="close" data-close="pdfOptionsModal">×</span>
        <h3>Opções de Geração de PDF</h3>
        
        <div class="pdf-options">
            <div class="pdf-option-group">
                <h4>Tamanho da Página:</h4>
                <div class="pdf-page-size-options">
                    <label class="pdf-page-size-option">
                        <input type="radio" name="pageSize" value="a4-portrait" checked> A4 Retrato
                    </label>
                    <label class="pdf-page-size-option">
                        <input type="radio" name="pageSize" value="a4-landscape"> A4 Paisagem
                    </label>
                    <label class="pdf-page-size-option">
                        <input type="radio" name="pageSize" value="a3-portrait"> A3 Retrato
                    </label>
                    <label class="pdf-page-size-option">
                        <input type="radio" name="pageSize" value="a3-landscape"> A3 Paisagem
                    </label>
                </div>
            </div>
            
            <div class="pdf-option-group">
                <h4>Seções a incluir:</h4>
                <label>
                    <input type="checkbox" id="includeApqp" checked> Incluir Análise APQP (Fases 1-5)
                </label>
                <label>
                    <input type="checkbox" id="includeGantt" checked> Incluir Gráfico de Gantt
                </label>
                <label>
                    <input type="checkbox" id="includeCapability" checked> Incluir Estudo de Capabilidade
                </label>
                <label>
                    <input type="checkbox" id="includeTimeline" checked> Incluir Linha do Tempo
                </label>
            </div>
            
            <div class="pdf-preview-info" id="pdfPreviewInfo">
                ⚠️ Para melhor visualização, recomendamos usar formato PAISAGEM (A4 ou A3).
            </div>
        </div>
        
        <div class="form-buttons">
            <button class="btn btn-danger" onclick="document.getElementById('pdfOptionsModal').style.display='none'">
                Cancelar
            </button>
            <button class="btn btn-primary" onclick="generateCompletePDF()">
                <i class="fas fa-file-pdf"></i> Gerar PDF Completo
            </button>
        </div>
    </div>
</div>

<!-- Input oculto para arquivo de logo -->
<input type="file" id="logoFileInput" accept="image/*" style="display: none;">

<!-- Container oculto para captura de tela do Gantt -->
<div id="ganttCaptureContainer" style="position: absolute; left: -9999px; top: -9999px; width: 2000px; background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;"></div>

<!-- Container oculto para captura de tela da Capabilidade -->
<div id="capabilityCaptureContainer" style="position: absolute; left: -9999px; top: -9999px; width: 2000px; background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px;"></div>

<!-- Container oculto para captura de tela do Handover -->
<div id="handoverCaptureContainer" style="position: absolute; left: -9999px; top: -9999px; width: 1200px; background: white; padding: 30px; border: 1px solid #ddd; border-radius: 8px;"></div>

<!-- Container oculto para captura do APQP -->
<div id="apqpCaptureContainer" style="position: absolute; left: -9999px; top: -9999px; width: 2000px; background: white; padding: 30px; border: 1px solid #ddd; border-radius: 8px;"></div>

<script src="../assets/js/projects-state.js"></script>
<script src="../assets/js/projects-api.js"></script>
<script src="../assets/js/projects-core.js"></script>
<script src="../assets/js/projects-forms.js"></script>
<script src="../assets/js/projects-gantt.js"></script>
<script src="../assets/js/projects-charts.js"></script>
<script src="../assets/js/projects-apqp.js"></script>
<script src="../assets/js/projects-capability.js"></script>
<script src="../assets/js/projects-reports.js"></script>
<script src="../assets/js/projects-control.js"></script>

</body>
</html>

