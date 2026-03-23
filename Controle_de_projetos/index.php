<?php
require_once 'auth.php';
$usuario = getUsuario();
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
<style>
    /* TODO o CSS original permanece inalterado */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    body {
        background: #f0f8f0;
        color: #333;
        line-height: 1.5;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }
    .container {
        max-width: 100%;
        flex: 1;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 6px 30px rgba(0,0,0,.08);
        overflow: hidden;
        margin: 10px;
        display: flex;
        flex-direction: column;
    }
    header {
        background: linear-gradient(135deg, #2e7d32, #1b5e20);
        color: #fff;
        padding: 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }
    .logo {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .logo-image {
        height: 50px;
        width: auto;
        max-width: 150px;
        object-fit: contain;
        border-radius: 4px;
        background: white;
        padding: 4px;
        display: none;
    }
    .logo i {
        font-size: 2.2rem;
    }
    .header-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        padding: 16px;
        background: #e8f5e9;
        border-bottom: 1px solid #c8e6c9;
    }
    .summary-card {
        background: #fff;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
        box-shadow: 0 2px 6px rgba(0,0,0,.04);
        border-left: 4px solid #4caf50;
    }
    .summary-card-efficiency {
        border-left: 4px solid #2196f3 !important;
    }
    .summary-card-cancelado {
        border-left: 4px solid #9e9e9e !important;
    }
    .summary-card-espera {
        border-left: 4px solid #ff9800 !important;
    }
    .summary-card-em-andamento {
        border-left: 4px solid #2196f3 !important;
    }
    .summary-card-pendente {
        border-left: 4px solid #ff9800 !important;
    }
    .filters-collapsible {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    .filters-collapsible.show {
        max-height: 1000px;
    }
    .controls {
        padding: 16px;
        background: #e8f5e9;
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        align-items: flex-start;
        border-bottom: 1px solid #c8e6c9;
    }
    .filters-section {
        flex: 1;
        min-width: 300px;
    }
    .filters-title {
        font-weight: 700;
        font-size: 1rem;
        color: #2e7d32;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .filters-title i {
        font-size: 1.1rem;
    }
    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .filter-group label {
        font-weight: 600;
        font-size: 0.85rem;
        color: #444;
    }
    .filter-group input, .filter-group select {
        padding: 10px 12px;
        border: 1px solid #a5d6a7;
        border-radius: 6px;
        background: #fff;
        font-size: 0.9rem;
        transition: border 0.2s;
    }
    .filter-group input:focus, .filter-group select:focus {
        outline: none;
        border-color: #2e7d32;
        box-shadow: 0 0 0 2px rgba(46, 125, 50, 0.1);
    }
    select[multiple] {
        min-height: 100px;
        max-height: 150px;
        padding: 8px;
    }
    .date-filter-section {
        flex: 1;
        min-width: 300px;
        background: #f1f8e9;
        padding: 16px;
        border-radius: 8px;
        border: 1px solid #c8e6c9;
    }
    .date-filter-title {
        font-weight: 700;
        font-size: 1rem;
        color: #2e7d32;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .date-filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
    }
    .date-range-group {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .date-range-inputs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .btn {
        background: #673ab7;
        color: #fff;
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        display: inline-flex;
        gap: 8px;
        align-items: center;
        transition: all 0.2s;
        font-size: 0.9rem;
    }
    .btn-primary { background: #2e7d32; color: #fff; }
    .btn-success { background: #4caf50; color: #fff; }
    .btn-danger { background: #f44336; color: #fff; }
    .btn-info { background: #00bcd4; color: #fff; }
    .btn-warning { background: #ff9800; color: #fff; }
    .btn-sm { padding: 6px 10px; font-size: .85rem; }
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }
    .btn:active {
        transform: translateY(0);
    }
    .table-container {
        overflow-x: auto;
        padding: 10px;
        flex: 1;
        max-height: 70vh;
        overflow-y: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    th, td {
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    th {
        background: #2e7d32;
        color: #fff;
        position: sticky;
        top: 0;
        font-weight: 700;
        white-space: nowrap;
    }
    tr:nth-child(even) { background: #fbfbfb; }
    tr:hover {
        background-color: #f0f9f0 !important;
    }
    .status {
        padding: 5px 10px;
        border-radius: 14px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
        color: #000 !important;
        min-width: 90px;
        text-align: center;
    }
    .status-pendente { background: #ff9800; }
    .status-em-andamento { background: #2196f3; }
    .status-concluido { background: #4caf50; }
    .status-atrasado { background: #f44336; }
    .status-no-prazo { background: #4caf50; }
    .status-em-espera { background: #ff9800; }
    .status-cancelado { background: #9e9e9e; }
    .form-container {
        background: #fff;
        padding: 16px;
        border-radius: 8px;
        box-shadow: 0 6px 20px rgba(0,0,0,.06);
        margin: 16px 20px;
        display: none;
        max-height: 70vh;
        overflow-y: auto;
        border: 1px solid #c8e6c9;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
    }
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 700;
        color: #333;
        font-size: .9rem;
    }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9rem;
    }
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: none;
        border-color: #2e7d32;
        box-shadow: 0 0 0 2px rgba(46, 125, 50, 0.1);
    }
    .form-buttons {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 20px;
        position: sticky;
        bottom: 0;
        background: white;
        padding: 12px 0;
        z-index: 10;
        border-top: 1px solid #eee;
    }
    .task-section {
        background: #f8f9fa;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 12px;
    }
    .history-badge {
        background: #6c757d;
        color: #fff;
        padding: 4px 8px;
        border-radius: 10px;
        font-size: .75rem;
        cursor: pointer;
        margin-right: 5px;
        border: none;
    }
    .reschedule-btn {
        background: #6c757d;
        color: #fff;
        padding: 5px 8px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-size: .85rem;
    }
    .modal {
        display: none;
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,.45);
        z-index: 1000;
        overflow: auto;
    }
    .modal-content {
        background: #fff;
        margin: 4% auto;
        padding: 20px;
        border-radius: 10px;
        width: 92%;
        max-width: 900px;
        max-height: 80vh;
        overflow: auto;
        position: relative;
        border: 1px solid #4caf50;
    }
    .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #666;
    }
    .task-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 13px;
    }
    .task-table th, .task-table td {
        padding: 10px;
        text-align: center;
        border: 1px solid #ddd;
    }
    .task-table th {
        background: #2e7d32;
        color: #fff;
    }
    .task-table input[type="date"], .task-table input[type="text"], .task-table select, .task-table input[type="number"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 0.85rem;
    }
    .date-group {
        background: #f8f9fa;
        padding: 5px;
        border-radius: 4px;
        margin: 2px 0;
    }
    .date-group-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 3px;
        display: block;
    }
    .task-header {
        background: #e8f5e9;
        font-weight: bold;
    }
    .task-row {
        background: #f8f9fa;
    }
    .task-actions {
        padding: 8px;
        text-align: left;
        background: #f1f8e9;
    }
    .filter-status-count {
        font-weight: bold;
        color: #2e7d32;
        margin-left: 8px;
        background: #e8f5e9;
        padding: 4px 12px;
        border-radius: 10px;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .resource-group {
        margin: 8px 0;
        padding: 8px;
        background: #f8f9fa;
        border-radius: 6px;
    }
    .resource-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 4px;
        display: block;
    }
    .task-group-divider {
        height: 20px;
        background-color: #e8f5e9;
        position: relative;
        margin: 15px 0;
        border-left: 4px solid #2e7d32;
    }
    .task-group-divider::before {
        content: attr(data-task-name);
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: #2e7d32;
        color: white;
        padding: 2px 10px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 0.9rem;
    }
    .task-group {
        margin-bottom: 25px;
        border: 1px solid #c8e6c9;
        border-radius: 8px;
        overflow: hidden;
    }
    .task-group-header {
        background: #2e7d32;
        color: white;
        padding: 12px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .task-group-content {
        padding: 20px;
    }
    .column-group {
        border-right: 2px solid #2e7d32 !important;
        background-color: #f1f8e9 !important;
    }
    .column-group-header {
        background: #1b5e20 !important;
        text-align: center;
        font-size: 0.9rem;
    }
    .resource-section {
        background-color: #f9f9f9;
        padding: 12px;
        border-radius: 8px;
        margin: 10px 0;
    }
    .resource-section h4 {
        margin: 0 0 12px 0;
        color: #2e7d32;
        font-size: 0.9rem;
    }
    .sync-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
        margin-left: 10px;
        background: #e3f2fd;
        color: #0d47a1;
    }
    .sync-indicator.syncing {
        background: #fff8e1;
        color: #f57f17;
    }
    .sync-indicator.error {
        background: #ffebee;
        color: #c62828;
    }
    .charts-section {
        padding: 16px;
        background: #f8f9fa;
        border-top: 1px solid #c8e6c9;
        display: none;
    }
    .charts-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    .charts-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    .chart-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .chart-title {
        font-size: 1rem;
        margin-bottom: 12px;
        color: #2e7d32;
        font-weight: 600;
        text-align: center;
    }
    .chart-filters {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .chart-filters select, .chart-filters input {
        padding: 8px 12px;
        border: 1px solid #a5d6a7;
        border-radius: 6px;
        background: #fff;
    }
    .charts-efficiency-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
        order: 1;
        width: 100%;
    }
    .charts-container {
        order: 2;
    }
    .chart-section-group {
        display: block;
    }
    .chart-section-group .summary-card {
        margin-bottom: 15px;
    }
    .project-detail-item {
        padding: 12px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .project-detail-item:hover {
        background-color: #f5f5f5;
    }
    .project-detail-info {
        flex: 1;
    }
    .project-detail-actions {
        display: flex;
        gap: 8px;
    }
    @media (max-width: 992px) {
        .filters-grid, .date-filter-grid {
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        }
        .charts-container {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 768px) {
        .controls { 
            flex-direction: column; 
            gap: 20px;
        }
        .filters-section, .date-filter-section {
            width: 100%;
        }
        .filters-grid, .date-filter-grid {
            grid-template-columns: 1fr;
        }
        .header-buttons { 
            justify-content: flex-start; 
            margin-top: 10px; 
            width: 100%;
        }
        .task-table {
            font-size: 0.7rem;
        }
        .task-table input[type="date"], .task-table input[type="text"], .task-table select, .task-table input[type="number"] {
            padding: 6px;
            font-size: 0.75rem;
        }
        .task-group-divider::before {
            font-size: 0.8rem;
            padding: 1px 6px;
        }
        .chart-filters {
            grid-template-columns: 1fr;
        }
        .chart-section-group {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        .charts-efficiency-cards {
            grid-template-columns: 1fr;
        }
        select[multiple] {
            min-height: 80px;
            max-height: 120px;
        }
    }
    @media (max-width: 480px) {
        .task-table {
            display: block;
            overflow-x: auto;
        }
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
    .table-container th.sticky, 
    .table-container td.sticky {
        position: sticky;
        background: #fff;
        z-index: 5;
    }
    th.col-id, td.col-id { left: 0;  min-width: 60px; z-index: 6; }
    th.col-cliente, td.col-cliente { left: 60px; min-width: 150px; }
    th.col-projeto, td.col-projeto { left: 210px; min-width: 180px; }
    th.col-segmento, td.col-segmento { left: 390px; min-width: 120px; }
    th.col-lider, td.col-lider { left: 510px; min-width: 150px; }
    th.col-codigo, td.col-codigo { left: 660px; min-width: 100px; }
    th.col-anvi, td.col-anvi { left: 760px; min-width: 100px; }
    th.col-modelo, td.col-modelo { left: 860px; min-width: 120px; }
    th.col-processo, td.col-processo { left: 980px; min-width: 120px; }
    th.col-fase, td.col-fase { left: 1100px; min-width: 100px; }
    th.col-status, td.col-status { left: 1200px; min-width: 130px; }
    th.col-observacoes, td.col-observacoes { left: 1330px; min-width: 200px; }
    #syncFiltersBtn, #useTablePeriodBtn {
        background: #17a2b8;
        color: white;
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    #useTablePeriodBtn {
        background: #ff9800;
    }
    #useTablePeriodBtn:hover {
        background: #e68900;
    }
    .chart-period-info {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
        padding: 12px;
        background: #e8f5e9;
        border-radius: 6px;
        font-size: 0.85rem;
        color: #2e7d32;
    }
    .period-efficiency-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    #projectStatusModal .project-detail-item {
        padding: 10px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    #projectStatusModal .project-detail-item:hover {
        background-color: #f5f5f5;
    }
    #projectStatusModal .project-detail-info {
        flex: 1;
    }
    #projectStatusModal .project-detail-actions {
        display: flex;
        gap: 5px;
    }
    .charts-efficiency-section {
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    .charts-section {
        display: flex;
        flex-direction: column;
    }
    .project-task-status {
        margin-top: 5px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .project-task-status-item {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 0.8rem;
        background: #f5f5f5;
        padding: 4px 8px;
        border-radius: 4px;
    }
    .project-task-status-label {
        font-weight: bold;
        color: #666;
    }
    .project-task-status-value {
        font-weight: 600;
    }
    .chartjs-plugin-datalabels {
        font-weight: bold;
        color: white;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
        cursor: pointer;
        pointer-events: auto !important;
    }
    .chartjs-plugin-datalabels:hover {
        opacity: 0.8;
        transform: scale(1.1);
    }
    #projectListModal .modal-content {
        max-width: 90%;
        max-height: 85vh;
    }
    #projectListModal .task-table {
        margin-top: 15px;
        font-size: 0.85rem;
    }
    #projectListModal .task-table th,
    #projectListModal .task-table td {
        padding: 10px;
        text-align: left;
    }
    #projectListModal .task-table th {
        background: #2e7d32;
        color: white;
        position: sticky;
        top: 0;
    }
    #projectListModal .task-table tbody tr:hover {
        background-color: #f5f5f5;
    }
    .multi-select-controls {
        display: flex;
        gap: 6px;
        margin-top: 8px;
    }
    .multi-select-btn {
        padding: 6px 10px;
        font-size: 0.75rem;
        background: #f0f0f0;
        border: 1px solid #ccc;
        border-radius: 4px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .multi-select-btn:hover {
        background: #e0e0e0;
    }
    .filter-actions {
        display: flex;
        gap: 8px;
        margin-top: 12px;
        flex-wrap: wrap;
    }
    .filter-actions .btn {
        flex: 1;
        min-width: 120px;
        justify-content: center;
    }
    .projects-count {
        background: #2e7d32;
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .task-filter-count-container {
        margin-top: 12px;
        padding: 12px;
        background: #e8f5e9;
        border-radius: 8px;
        border: 1px solid #c8e6c9;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .task-filter-count-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px;
        background: white;
        border-radius: 6px;
        border-left: 4px solid #2e7d32;
    }
    .task-filter-count-label {
        font-weight: 600;
        color: #333;
        font-size: 0.9rem;
    }
    .task-filter-count-value {
        font-weight: 700;
        color: #2e7d32;
        font-size: 1.1rem;
        min-width: 40px;
        text-align: center;
    }
    .history-item {
        padding: 12px;
        margin-bottom: 8px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: #f9f9f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .history-item:hover {
        background: #f0f0f0;
    }
    .history-item-content {
        flex: 1;
        margin-right: 12px;
    }
    .history-item-date {
        font-weight: bold;
        color: #2e7d32;
        margin-bottom: 4px;
    }
    .history-item-reason {
        color: #555;
        margin-bottom: 4px;
    }
    .history-item-dates {
        font-size: 0.85rem;
        color: #666;
    }
    .history-item-actions {
        display: flex;
        gap: 4px;
    }
    .history-edit-form {
        padding: 12px;
        margin-bottom: 8px;
        border: 1px solid #4caf50;
        border-radius: 6px;
        background: #e8f5e9;
    }
    .history-form-buttons {
        display: flex;
        gap: 8px;
        margin-top: 12px;
        justify-content: flex-end;
    }
    .timeline-container {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-top: 20px;
    }
    .gantt-chart {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border: 1px solid #e0e0e0;
    }
    .gantt-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }
    .gantt-title {
        font-weight: bold;
        color: #2e7d32;
        font-size: 1.1rem;
    }
    .gantt-controls {
        display: flex;
        gap: 10px;
    }
    .gantt-scale-btn {
        padding: 4px 8px;
        background: #f0f0f0;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.8rem;
    }
    .gantt-scale-btn.active {
        background: #2e7d32;
        color: white;
        border-color: #2e7d32;
    }
    .gantt-table {
        border-collapse: collapse;
        width: 100%;
        font-size: 12px;
    }
    .gantt-table th {
        background: #f5f5f5;
        padding: 8px;
        text-align: center;
        border-left: 1px solid #ddd;
        border-bottom: 2px solid #2e7d32;
        white-space: nowrap;
        color: #000;
    }
    .gantt-table td {
        padding: 0;
        border-left: 1px solid #eee;
        border-bottom: 1px solid #ddd;
        vertical-align: top;
        position: relative;
    }
    .gantt-table tr:hover td {
        background-color: rgba(46, 125, 50, 0.05) !important;
    }
    .gantt-table td:first-child {
        border-left: none;
        border-right: 2px solid #2e7d32;
        background: #f0f8f0;
        position: sticky;
        left: 0;
        z-index: 5;
        padding: 8px;
    }
    .gantt-bar {
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        min-width: 2px;
    }
    .gantt-bar:hover {
        transform: scaleY(1.1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 10 !important;
    }
    .gantt-bar.planned {
        background: linear-gradient(90deg, #90caf9, #42a5f5);
        border: 1px solid #1e88e5;
    }
    .gantt-bar.actual {
        background: linear-gradient(90deg, #4caf50, #2e7d32) !important;
        border: 1px solid #1b5e20 !important;
    }
    .gantt-bar.delayed {
        background: linear-gradient(90deg, #ef9a9a, #ef5350);
        border: 1px solid #c62828;
    }
    .gantt-info-tooltip {
        position: fixed;
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.8rem;
        pointer-events: none;
        z-index: 1000;
        display: none;
    }
    .gantt-table-container::-webkit-scrollbar {
        height: 8px;
    }
    .gantt-table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .gantt-table-container::-webkit-scrollbar-thumb {
        background: #2e7d32;
        border-radius: 4px;
    }
    .gantt-table-container::-webkit-scrollbar-thumb:hover {
        background: #1b5e20;
    }
    .timeline-phase {
        position: relative;
        padding-left: 30px;
        border-left: 3px solid #2e7d32;
    }
    .timeline-phase::before {
        content: '';
        position: absolute;
        left: -10px;
        top: 0;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #2e7d32;
        border: 3px solid white;
        box-shadow: 0 0 0 3px #2e7d32;
    }
    .timeline-phase-header {
        background: #e8f5e9;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        border-left: 4px solid #2e7d32;
    }
    .timeline-phase-title {
        font-weight: bold;
        color: #1b5e20;
        font-size: 1.1rem;
    }
    .timeline-phase-subtitle {
        color: #666;
        font-size: 0.9rem;
        margin-top: 5px;
    }
    .timeline-tasks {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-left: 15px;
    }
    .timeline-task {
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        flex: 1;
        min-width: 200px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .timeline-task-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .timeline-task-name {
        font-weight: bold;
        color: #333;
    }
    .timeline-task-dates {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 15px;
    }
    .date-card {
        background: #f5f5f5;
        padding: 10px;
        border-radius: 6px;
        border-left: 4px solid #2196f3;
    }
    .date-card.planned {
        border-left-color: #4caf50;
    }
    .date-card.actual {
        border-left-color: #ff9800;
    }
    .date-card.completed {
        border-left-color: #2e7d32;
    }
    .date-label {
        font-size: 0.8rem;
        color: #666;
        margin-bottom: 5px;
    }
    .date-value {
        font-weight: bold;
        color: #333;
    }
    .timeline-project-info {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        border: 1px solid #e0e0e0;
    }
    .project-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }
    .project-info-item {
        padding: 10px;
        background: white;
        border-radius: 6px;
        border-left: 4px solid #2e7d32;
    }
    .project-info-label {
        font-size: 0.8rem;
        color: #666;
        margin-bottom: 5px;
    }
    .project-info-value {
        font-weight: bold;
        color: #333;
    }
    .progress-bar-container {
        width: 100%;
        background-color: #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
        margin: 15px 0;
        height: 30px;
        position: relative;
    }
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #4caf50, #2e7d32);
        transition: width 0.5s ease;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding-right: 10px;
        color: white;
        font-weight: bold;
        font-size: 14px;
    }
    .progress-bar-labels {
        display: flex;
        justify-content: space-between;
        margin-top: 5px;
        font-size: 0.85rem;
        color: #666;
    }
    .progress-weight-info {
        background-color: #e8f5e9;
        padding: 12px;
        border-radius: 8px;
        margin: 15px 0;
        border-left: 4px solid #2196f3;
    }
    .progress-weight-info h4 {
        color: #2196f3;
        margin-bottom: 8px;
        font-size: 1rem;
    }
    .progress-weight-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 8px;
    }
    .progress-weight-item {
        display: flex;
        justify-content: space-between;
        padding: 4px 8px;
        background-color: white;
        border-radius: 4px;
        border-left: 3px solid #4caf50;
    }
    .progress-weight-task {
        font-weight: 600;
        color: #333;
    }
    .progress-weight-value {
        font-weight: bold;
        color: #2e7d32;
    }
    #timelineModal .modal-content {
        max-width: 95%;
        max-height: 90vh;
    }
    .timeline-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .print-timeline-btn {
        background: #673ab7;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .logo-modal-content {
        max-width: 500px;
    }
    .logo-preview-container {
        display: flex;
        justify-content: center;
        margin: 20px 0;
    }
    .logo-preview {
        max-width: 300px;
        max-height: 150px;
        object-fit: contain;
        border: 2px dashed #ddd;
        padding: 10px;
        border-radius: 8px;
        background: #f9f9f9;
    }
    .logo-controls {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(400px);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    .apqp-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: bold;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: 10px;
    }
    .apqp-badge.pending {
        background-color: #ff9800;
        color: white;
    }
    .apqp-badge.partial {
        background-color: #2196f3;
        color: white;
    }
    .apqp-badge.completed {
        background-color: #4caf50;
        color: white;
    }
    .apqp-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
        padding: 15px;
        background: #f5f5f5;
        border-radius: 8px;
    }
    .apqp-summary-item {
        background: white;
        padding: 10px;
        border-radius: 6px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .apqp-summary-label {
        font-weight: 600;
        color: #666;
        font-size: 0.9rem;
    }
    .apqp-summary-value {
        font-weight: bold;
        font-size: 1.1rem;
    }
    .apqp-summary-value.pending {
        color: #ff9800;
    }
    .apqp-summary-value.partial {
        color: #2196f3;
    }
    .apqp-summary-value.completed {
        color: #4caf50;
    }
    .apqp-questions-container {
        max-height: 400px;
        overflow-y: auto;
        margin-bottom: 20px;
    }
    .apqp-question-section {
        margin-bottom: 15px;
        padding: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        background: #f9f9f9;
    }
    .apqp-question {
        margin-bottom: 10px;
    }
    .apqp-question-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #333;
    }
    .phase-apqp-status {
        margin-top: 5px;
    }
    .apqp-answer-options {
        display: flex;
        gap: 15px;
        margin-bottom: 10px;
    }
    .apqp-answer-option {
        display: flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
    }
    .apqp-answer-option input[type="radio"] {
        margin: 0;
    }
    .apqp-observations textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9rem;
        resize: vertical;
    }
    .pdf-options {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #ddd;
    }
    .pdf-option-group {
        margin-bottom: 15px;
    }
    .pdf-option-group label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .pdf-option-group input[type="checkbox"] {
        margin: 0;
    }
    .handover-report-section {
        margin-bottom: 25px;
        padding: 20px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .handover-report-section h3 {
        color: #2e7d32;
        border-bottom: 2px solid #e8f5e9;
        padding-bottom: 10px;
        margin-bottom: 15px;
        font-size: 1.2rem;
    }
    .handover-report-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .handover-report-item {
        padding: 12px;
        background: #f9f9f9;
        border-radius: 6px;
        border-left: 4px solid #4caf50;
    }
    .handover-report-label {
        font-size: 0.85rem;
        color: #666;
        margin-bottom: 5px;
        font-weight: 600;
    }
    .handover-report-value {
        font-weight: bold;
        color: #333;
        font-size: 1rem;
    }
    .handover-task-status {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 15px;
    }
    .handover-task-card {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    .handover-task-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e0e0e0;
    }
    .handover-task-name {
        font-weight: bold;
        color: #333;
        font-size: 1rem;
    }
    .handover-apqp-summary {
        background: #e8f5e9;
        padding: 12px;
        border-radius: 6px;
        margin-top: 10px;
        border-left: 4px solid #2196f3;
    }
    .handover-apqp-summary h4 {
        color: #2196f3;
        margin-bottom: 8px;
        font-size: 0.9rem;
    }
    .handover-metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    .handover-metric-card {
        padding: 15px;
        text-align: center;
        background: white;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    .handover-metric-value {
        font-size: 1.8rem;
        font-weight: bold;
        color: #2e7d32;
        margin: 10px 0;
    }
    .handover-metric-label {
        font-size: 0.85rem;
        color: #666;
    }
    .handover-observations textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9rem;
        resize: vertical;
        min-height: 120px;
    }
    .handover-report-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }
    #handoverReportModal .modal-content {
        max-width: 95%;
        max-height: 90vh;
    }
    .capability-section {
        background: #fff;
        border: 1px solid #c8e6c9;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
    }
    .capability-section h3 {
        color: #2e7d32;
        margin-bottom: 15px;
        border-bottom: 2px solid #e8f5e9;
        padding-bottom: 8px;
    }
    .project-info-capability {
        background: #f0f8f0;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        border-left: 4px solid #2196f3;
    }
    .project-info-item-capability {
        padding: 5px;
    }
    .project-info-label-capability {
        font-size: 0.8rem;
        color: #666;
        margin-bottom: 2px;
    }
    .project-info-value-capability {
        font-weight: bold;
        color: #333;
    }
    .characteristic-card {
        background: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 25px;
    }
    .characteristic-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    .characteristic-name {
        font-weight: bold;
        color: #333;
    }
    .characteristic-symbol {
        color: #f44336;
        font-weight: bold;
        font-size: 1.2rem;
    }
    .characteristic-symbol.cc {
        color: #f44336;
    }
    .characteristic-symbol.sc {
        color: #ff9800;
    }
    .characteristic-inputs {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px;
        margin: 15px 0;
    }
    .characteristic-inputs .form-group {
        margin-bottom: 5px;
    }
    .characteristic-inputs .form-group label {
        font-size: 0.8rem;
        margin-bottom: 3px;
    }
    .measurement-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
        font-size: 0.8rem;
        table-layout: fixed;
    }
    .measurement-table th {
        background: #2e7d32;
        color: white;
        padding: 4px;
        font-size: 0.7rem;
        text-align: center;
    }
    .measurement-table td {
        padding: 2px;
        border: 1px solid #ddd;
    }
    .measurement-table input {
        width: 100%;
        padding: 4px;
        border: 1px solid #ccc;
        border-radius: 2px;
        text-align: center;
        font-size: 0.7rem;
    }
    .capability-results {
        background: #e8f5e9;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
        border-left: 4px solid #2196f3;
    }
    .capability-results h4 {
        color: #2196f3;
        margin-bottom: 15px;
    }
    .results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
    }
    .result-item {
        background: white;
        padding: 10px;
        border-radius: 6px;
        text-align: center;
    }
    .result-label {
        font-size: 0.8rem;
        color: #666;
        margin-bottom: 5px;
    }
    .result-value {
        font-size: 1.4rem;
        font-weight: bold;
        color: #333;
    }
    .result-value.good {
        color: #4caf50;
    }
    .result-value.warning {
        color: #ff9800;
    }
    .result-value.bad {
        color: #f44336;
    }
    .capability-interpretation {
        margin-top: 15px;
        padding: 12px;
        background: white;
        border-radius: 6px;
        border-left: 4px solid #ff9800;
    }
    .add-characteristic-btn {
        background: #4caf50;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        margin-bottom: 15px;
    }
    .remove-characteristic-btn {
        background: #f44336;
        color: white;
        border: none;
        padding: 4px 8px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.75rem;
    }
    .capability-analysis-btn {
        background: #2196f3;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        margin-left: 5px;
    }
    .chart-container-small {
        width: 100%;
        height: 400px;
        margin: 20px 0;
    }
    .warning-message {
        background-color: #fff3cd;
        color: #856404;
        padding: 10px;
        border-radius: 4px;
        border-left: 4px solid #ffc107;
        margin: 10px 0;
        font-size: 0.9rem;
    }
    .study-date {
        background: #f0f8f0;
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 15px;
        border-left: 4px solid #2196f3;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .study-date label {
        font-weight: bold;
        color: #2196f3;
    }
    .study-date input {
        padding: 6px 10px;
        border: 1px solid #a5d6a7;
        border-radius: 4px;
    }
    .duration-field {
        width: 80px !important;
        text-align: center;
        margin: 0 5px;
        padding: 5px;
        border: 1px solid #a5d6a7;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    .duration-group {
        display: flex;
        align-items: center;
        gap: 5px;
        background: #f5f5f5;
        padding: 5px;
        border-radius: 4px;
        margin-top: 5px;
    }
    .duration-group label {
        font-size: 0.8rem;
        color: #666;
        margin: 0;
    }
    .duration-info {
        font-size: 0.8rem;
        color: #2e7d32;
        font-weight: bold;
        margin-left: 5px;
    }
    .gantt-duration-badge {
        position: absolute;
        bottom: -20px;
        left: 5px;
        font-size: 0.7rem;
        color: #666;
        background: rgba(255,255,255,0.9);
        padding: 2px 5px;
        border-radius: 3px;
        border: 1px solid #ddd;
    }
    .task-duration-cell {
        min-width: 100px;
        text-align: center;
        background: #f9f9f9;
    }
    .task-duration-cell input {
        width: 80px;
        padding: 5px;
        text-align: center;
        border: 1px solid #a5d6a7;
        border-radius: 4px;
    }
    .task-duration-cell .duration-unit {
        font-size: 0.7rem;
        color: #666;
        margin-left: 2px;
    }
    .handover-task-status {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 15px;
    }
    .handover-metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin: 15px 0;
    }
    .handover-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        margin-top: 20px;
    }
    .pdf-page-size-options {
        display: flex;
        gap: 20px;
        margin: 15px 0;
    }
    .pdf-page-size-option {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }
    .pdf-page-size-option input[type="radio"] {
        margin: 0;
        width: auto;
    }
    .pdf-preview-info {
        background: #e8f5e9;
        padding: 10px;
        border-radius: 6px;
        margin: 15px 0;
        font-size: 0.9rem;
        color: #2e7d32;
        text-align: center;
    }
    .pdf-capture-container {
        background: white;
        padding: 20px;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    .pdf-capture-title {
        font-size: 14px;
        font-weight: bold;
        color: #2e7d32;
        margin-bottom: 10px;
        text-align: center;
    }
    .pdf-capture-canvas {
        width: 100%;
        max-width: 1800px;
        margin: 0 auto;
        background: white;
    }
    .pdf-section {
        margin-bottom: 30px;
        page-break-inside: avoid;
    }
    .pdf-section-header {
        background: #2e7d32;
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        margin-bottom: 15px;
        font-weight: bold;
        font-size: 14px;
    }
    .pdf-section-content {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    .pdf-question-item {
        padding: 10px;
        margin-bottom: 8px;
        background: white;
        border-radius: 4px;
        border-left: 3px solid #2e7d32;
    }
    .pdf-question-text {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }
    .pdf-question-answer {
        font-size: 0.9rem;
        color: #666;
    }
    .pdf-question-observations {
        font-size: 0.85rem;
        color: #999;
        font-style: italic;
        margin-top: 3px;
    }
    .pdf-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-right: 5px;
    }
    .pdf-badge.sim {
        background: #4caf50;
        color: white;
    }
    .pdf-badge.nao {
        background: #f44336;
        color: white;
    }
    .pdf-badge.na {
        background: #ff9800;
        color: white;
    }
    .handover-capture-container {
        background: white;
        padding: 30px;
        max-width: 1200px;
        margin: 0 auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .handover-capture-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #2e7d32;
    }
    .handover-capture-header h1 {
        color: #2e7d32;
        font-size: 28px;
        margin-bottom: 10px;
    }
    .handover-capture-header h2 {
        color: #666;
        font-size: 18px;
        font-weight: normal;
    }
    .handover-capture-section {
        margin-bottom: 25px;
        page-break-inside: avoid;
    }
    .handover-capture-section h3 {
        color: #2e7d32;
        border-bottom: 2px solid #e8f5e9;
        padding-bottom: 8px;
        margin-bottom: 15px;
    }
    .handover-capture-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .handover-capture-card {
        background: #f9f9f9;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
    }
    .handover-capture-metric {
        text-align: center;
        padding: 15px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    .handover-capture-metric-value {
        font-size: 32px;
        font-weight: bold;
        color: #2e7d32;
    }
    .handover-capture-metric-label {
        font-size: 14px;
        color: #666;
    }
    .handover-capture-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }
    .handover-capture-table th {
        background: #2e7d32;
        color: white;
        padding: 10px;
        text-align: left;
    }
    .handover-capture-table td {
        padding: 10px;
        border: 1px solid #ddd;
    }
    .handover-capture-status {
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: bold;
        text-align: center;
        display: inline-block;
        min-width: 80px;
    }
    .handover-capture-status.concluido {
        background: #4caf50;
        color: white;
    }
    .handover-capture-status.andamento {
        background: #2196f3;
        color: white;
    }
    .handover-capture-status.atrasado {
        background: #f44336;
        color: white;
    }
    .handover-capture-status.pendente {
        background: #ff9800;
        color: white;
    }
    .handover-capture-footer {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        text-align: center;
        font-size: 12px;
        color: #999;
    }
    .apqp-phase-summary {
        background: #f8f9fa;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .apqp-phase-summary h4 {
        color: #2e7d32;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid #c8e6c9;
    }
    .apqp-phase-progress {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }
    .apqp-phase-bar {
        flex: 1;
        height: 10px;
        background: #e0e0e0;
        border-radius: 5px;
        overflow: hidden;
    }
    .apqp-phase-fill {
        height: 100%;
        background: linear-gradient(90deg, #4caf50, #2e7d32);
        border-radius: 5px;
    }
    .apqp-phase-status {
        font-size: 0.9rem;
        font-weight: bold;
    }
    .memorial-calculo {
        background: #e8f5e9;
        border: 1px solid #c8e6c9;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        font-family: 'Courier New', monospace;
    }
    .memorial-calculo h4 {
        color: #1b5e20;
        margin-bottom: 15px;
        border-bottom: 2px solid #81c784;
        padding-bottom: 5px;
        font-size: 1.1rem;
    }
    .memorial-calculo table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
        background: white;
    }
    .memorial-calculo th {
        background: #4caf50;
        color: white;
        padding: 8px;
        text-align: center;
        font-size: 0.85rem;
    }
    .memorial-calculo td {
        padding: 8px;
        border: 1px solid #c8e6c9;
        text-align: center;
        font-size: 0.85rem;
    }
    .memorial-calculo td:first-child {
        font-weight: bold;
        background: #f1f8e9;
    }
    .memorial-calculo .formula {
        background: #f5f5f5;
        padding: 10px;
        border-left: 4px solid #2196f3;
        margin: 10px 0;
        font-size: 0.9rem;
    }
    .memorial-calculo .formula code {
        background: #e3f2fd;
        padding: 2px 4px;
        border-radius: 3px;
        color: #0d47a1;
        font-family: 'Courier New', monospace;
    }
    .memorial-calculo .interpretacao-seis-sigma {
        background: #fff3e0;
        border-left: 4px solid #ff9800;
        padding: 12px;
        margin-top: 15px;
    }
    .clickable-number {
        cursor: pointer;
        transition: all 0.2s;
    }
    .clickable-number:hover {
        opacity: 0.7;
        transform: scale(1.2);
        text-decoration: underline;
    }
    .mysql-status {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: bold;
        margin-left: 10px;
    }
    .mysql-status.connected {
        background: #4caf50;
        color: white;
    }
    .mysql-status.disconnected {
        background: #f44336;
        color: white;
    }
    .mysql-status.checking {
        background: #ff9800;
        color: white;
    }
</style>
</head>
<body>
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
    <input id="anviNumber" placeholder="Número da ANVI"/>
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

<script>
// ==============================================
// VARIÁVEIS GLOBAIS
// ==============================================
let projects = [];
let leaders = [];
let nextProjectId = 1;
let nextLeaderId = 1;
let currentEditingProjectId = null;
let currentRescheduleInfo = null;
let mysqlConnected = false;

// Variáveis de controle de usuário
const userNivel = '<?php echo $usuario['nivel']; ?>';
const userName = '<?php echo addslashes($usuario['nome']); ?>';
const isVisualizador = userNivel === 'visualizador';
const isLider = userNivel === 'lider' || userNivel === 'admin';
const isAdmin = userNivel === 'admin';

let efficiencyChart = null;
let projectStatusChart = null;
let leaderChart = null;
let segmentChart = null;

let currentHistoryInfo = {
    projectId: null,
    taskKey: null,
    editingIndex: null
};

let currentTimelineProjectId = null;

let companyLogo = localStorage.getItem('companyLogo') || null;
let logoSize = parseInt(localStorage.getItem('logoSize')) || 50;

let currentApqpPhase = null;
let currentApqpProjectId = null;
let currentApqpAnswers = {};

let ganttScale = 'week';
let showGanttLabels = true;

let capabilityCharts = {};

// Registrar o plugin ChartDataLabels
Chart.register(ChartDataLabels);

// Definição das perguntas APQP por fase (igual ao original)
const APQP_QUESTIONS = {
    'kom': [
        { id: 'f1_q1', question: 'A Análise de Viabilidade (ANVI) foi concluída?', category: 'FASE 1 - Planejamento' },
        { id: 'f1_q2', question: 'Decisão de Fornecimento: As partes interessadas principais foram identificadas e envolvidas?', category: 'FASE 1 - Planejamento' },
        { id: 'f1_q3', question: 'Todos os requisitos do cliente foram identificados e documentados?', category: 'FASE 1 - Planejamento' },
        { id: 'f1_q4', question: 'O escopo do projeto está claramente definido e alinhado com o cliente?', category: 'FASE 1 - Planejamento' },
        { id: 'f1_q5', question: 'Pedido de Compras, Notificação ou Solicitação de desenvolvimento comercial foi emitido?', category: 'FASE 1 - Planejamento' },
        { id: 'f1_q6', question: 'Os recursos necessários foram identificados e alocados?', category: 'FASE 1 - Planejamento' },
        { id: 'f1_q7', question: 'O cronograma inicial foi desenvolvido e aprovado?', category: 'FASE 1 - Planejamento' },
        { id: 'f1_q8', question: 'As especificações de embalagem foram elaboradas e aprovadas?', category: 'FASE 1 - Planejamento' }
    ],
    'ferramental': [
        { id: 'f2_q1', question: 'Instalações, ferramentas e dispositivos foram avaliados e validados?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f2_q2', question: 'Os desenhos do ferramental foram aprovados pela engenharia?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f2_q3', question: 'Os fornecedores de componentes especializados foram contratados?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f2_q4', question: 'Os materiais para o ferramental estão disponíveis ou foram solicitados?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f2_q5', question: 'As tolerâncias dimensionais dos ferramentais foram verificadas e validadas?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f2_q6', question: 'Foram solicitados a confecção dos ferramentais fabricados internamente e a compra dos externos?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f2_q7', question: 'Foi definida data para o try-out de ferramentas/dispositivos?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f2_q8', question: 'Construção de protótipo (se requerido)?', category: 'FASE 2 - Desenvolvimento do Produto' }
    ],
    'cadBomFt': [
        { id: 'f2_q9', question: 'Os modelos CAD 3D estão completos e validados?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f2_q10', question: 'A pré-lista de materiais (BOM) do produto foi definida?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f2_q11', question: 'As fichas técnicas foram elaboradas e aprovadas?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f2_q12', question: 'As tolerâncias geométricas estão corretamente aplicadas?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f2_q13', question: 'Os desenhos CAD de fabricação estão liberados para produção?', category: 'FASE 2 - Desenvolvimento do Produto' },
        { id: 'f3_q2', question: 'A lista de materiais (BOM) do produto está completa e revisada?', category: 'FASE 3 - Desenvolvimento do Processo' }
    ],
    'tryout': [
        { id: 'f3_q3', question: 'O fluxograma do processo de manufatura foi elaborado e aprovado?', category: 'FASE 3 - Desenvolvimento do Processo' },
        { id: 'f3_q4', question: 'O FMEA de processo foi elaborado e aprovado?', category: 'FASE 3 - Desenvolvimento do Processo' },
        { id: 'f3_q5', question: 'A avaliação dos sistemas de medição (M.S.A) foi elaborada e aprovada?', category: 'FASE 3 - Desenvolvimento do Processo' },
        { id: 'f3_q6', question: 'As instruções de processo para o operador foram elaboradas e aprovadas?', category: 'FASE 3 - Desenvolvimento do Processo' },
        { id: 'f4_q1', question: 'O ferramental foi montado e inspecionado antes do tryout?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q2', question: 'As matérias-primas para o tryout estão disponíveis e aprovadas?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q3', question: 'Os parâmetros de processo foram definidos e documentados?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q4', question: 'O Trial Run da produção foi definido?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q5', question: 'O plano de controle da produção foi elaborado?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q6', question: 'Estudos preliminares de capabilidade do processo foram realizados?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q7', question: 'Foi solicitada a Ordem de Produção (OP) de Desenvolvimento?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q8', question: 'Teste de validação da produção / dimensional: As amostras foram inspecionadas dimensionalmente?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q9', question: 'Os resultados do tryout atendem aos requisitos do cliente?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q10', question: 'As não conformidades foram identificadas e tratadas?', category: 'FASE 4 - Validação do Produto/Processo' }
    ],
    'entrega': [
        { id: 'f4_q11', question: 'A documentação de entrega da amostra está completa e revisada?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q12', question: 'Os prazos de entrega estão alinhados com as expectativas do cliente?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q13', question: 'Os certificados de conformidade foram emitidos?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q14', question: 'As amostras de aprovação foram validadas pelo cliente?', category: 'FASE 4 - Validação do Produto/Processo' }
    ],
    'psw': [
        { id: 'f4_q15', question: 'PPAP dos sub-fornecedores foi avaliado e aprovado?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q16', question: 'Todos os documentos do PSW estão completos?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q17', question: 'Os resultados dos testes estão dentro das especificações?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q18', question: 'Os registros de produção estão disponíveis e organizados?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q19', question: 'O PSW foi revisado e aprovado pela qualidade?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q20', question: 'O cliente aprovou o PSW formalmente?', category: 'FASE 4 - Validação do Produto/Processo' }
    ],
    'handover': [
        { id: 'f4_q21', question: 'A transição de desenvolvimento para produção (Handover) foi realizada?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q22', question: 'Todo o conhecimento do projeto foi documentado?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q23', question: 'A equipe de produção foi treinada no novo produto/processo?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q24', question: 'A documentação final está arquivada no sistema de gestão?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f4_q25', question: 'O projeto foi formalmente encerrado com o cliente?', category: 'FASE 4 - Validação do Produto/Processo' },
        { id: 'f5_q1', question: 'Lições aprendidas / Análise das reclamações: As lições aprendidas foram registradas e compartilhadas?', category: 'FASE 5 - Retroalimentação e Ação Corretiva' }
    ]
};

// ==============================================
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
            alert('Erro ao conectar ao servidor: ' + error);
        }
    });
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
                    resolve(response);
                } else {
                    console.error('✗ MySQL retornou erro:', response.message);
                    reject(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('✗ Erro AJAX ao salvar:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText
                });
                reject(error);
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
// FUNÇÕES DE FORMULÁRIO DE PROJETO (modificadas para salvar no MySQL)
// ==============================================
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
        }
    } else {
        clearProjectForm();
        
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
        'anviNumber', 'modelo', 'processo', 'fase', 'observacoes',
        'projectStatusSelect'
    ];
    fields.forEach(field => {
        const el = document.getElementById(field);
        if (el) el.value = '';
    });
    
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
            
            alert('Projeto atualizado com sucesso!');
        }).catch(error => {
            alert('Erro ao salvar projeto no MySQL: ' + error);
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
            
            alert('Projeto salvo com sucesso!');
        }).catch(error => {
            alert('Erro ao salvar projeto no MySQL: ' + error);
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
// FUNÇÕES DE LOGO (manter as originais)
// ==============================================
function initLogo() {
    const logoImage = document.getElementById('logoImage');
    if (companyLogo) {
        logoImage.src = companyLogo;
        logoImage.style.display = 'block';
        logoImage.style.height = logoSize + 'px';
        
        const logoIcon = document.querySelector('.logo i');
        if (logoIcon) {
            logoIcon.style.fontSize = '1.8rem';
        }
    } else {
        logoImage.style.display = 'none';
    }
    
    const logoSizeControl = document.getElementById('logoSize');
    if (logoSizeControl) {
        logoSizeControl.value = logoSize;
        document.getElementById('logoSizeValue').textContent = logoSize + 'px';
        
        logoSizeControl.addEventListener('input', function() {
            document.getElementById('logoSizeValue').textContent = this.value + 'px';
            const preview = document.getElementById('logoPreview');
            if (preview && preview.src) {
                preview.style.height = this.value + 'px';
            }
        });
    }
}

function showLogoModal() {
    closeAllModals();
    document.getElementById('logoModal').style.display = 'block';
    
    const preview = document.getElementById('logoPreview');
    if (companyLogo) {
        preview.src = companyLogo;
        preview.style.height = logoSize + 'px';
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
    
    document.getElementById('logoFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const preview = document.getElementById('logoPreview');
                preview.src = event.target.result;
                preview.style.display = 'block';
                preview.style.height = document.getElementById('logoSize').value + 'px';
            };
            reader.readAsDataURL(file);
        }
    });
}

function saveLogo() {
    const fileInput = document.getElementById('logoFile');
    const preview = document.getElementById('logoPreview');
    const sizeControl = document.getElementById('logoSize');
    
    if (!fileInput.files[0] && !preview.src) {
        if (companyLogo) {
            logoSize = parseInt(sizeControl.value);
            localStorage.setItem('logoSize', logoSize);
            
            const logoImage = document.getElementById('logoImage');
            if (logoImage) {
                logoImage.style.height = logoSize + 'px';
            }
            
            alert('Tamanho do logotipo atualizado!');
            closeAllModals();
        } else {
            alert('Selecione um arquivo de imagem primeiro.');
        }
        return;
    }
    
    if (fileInput.files[0]) {
        const file = fileInput.files[0];
        const reader = new FileReader();
        
        reader.onload = function(event) {
            companyLogo = event.target.result;
            logoSize = parseInt(sizeControl.value);
            
            localStorage.setItem('companyLogo', companyLogo);
            localStorage.setItem('logoSize', logoSize);
            
            const logoImage = document.getElementById('logoImage');
            if (logoImage) {
                logoImage.src = companyLogo;
                logoImage.style.display = 'block';
                logoImage.style.height = logoSize + 'px';
            }
            
            const logoIcon = document.querySelector('.logo i');
            if (logoIcon) {
                logoIcon.style.fontSize = '1.8rem';
            }
            
            alert('Logotipo salvo com sucesso!');
            closeAllModals();
        };
        
        reader.readAsDataURL(file);
    } else if (preview.src) {
        companyLogo = preview.src;
        logoSize = parseInt(sizeControl.value);
        
        localStorage.setItem('companyLogo', companyLogo);
        localStorage.setItem('logoSize', logoSize);
        
        const logoImage = document.getElementById('logoImage');
        if (logoImage) {
            logoImage.src = companyLogo;
            logoImage.style.display = 'block';
            logoImage.style.height = logoSize + 'px';
        }
        
        alert('Logotipo atualizado!');
        closeAllModals();
    }
}

function removeLogo() {
    if (confirm('Tem certeza que deseja remover o logotipo?')) {
        companyLogo = null;
        localStorage.removeItem('companyLogo');
        localStorage.removeItem('logoSize');
        
        const logoImage = document.getElementById('logoImage');
        if (logoImage) {
            logoImage.style.display = 'none';
            logoImage.src = '';
        }
        
        const logoIcon = document.querySelector('.logo i');
        if (logoIcon) {
            logoIcon.style.fontSize = '2.2rem';
        }
        
        const preview = document.getElementById('logoPreview');
        if (preview) {
            preview.src = '';
            preview.style.display = 'none';
        }
        
        alert('Logotipo removido!');
        closeAllModals();
    }
}

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}

// ==============================================
// FUNÇÕES DE PDF COMPLETAS (manter as originais, mas adaptar para MySQL)
// ==============================================
function showPdfOptions() {
    closeAllModals();
    document.getElementById('pdfOptionsModal').style.display = 'block';
}

function getPageSizeConfig() {
    const selectedSize = document.querySelector('input[name="pageSize"]:checked')?.value || 'a4-portrait';
    
    switch(selectedSize) {
        case 'a4-portrait':
            return { orientation: 'p', unit: 'mm', format: 'a4', width: 210, height: 297 };
        case 'a4-landscape':
            return { orientation: 'l', unit: 'mm', format: 'a4', width: 297, height: 210 };
        case 'a3-portrait':
            return { orientation: 'p', unit: 'mm', format: 'a3', width: 297, height: 420 };
        case 'a3-landscape':
            return { orientation: 'l', unit: 'mm', format: 'a3', width: 420, height: 297 };
        default:
            return { orientation: 'p', unit: 'mm', format: 'a4', width: 210, height: 297 };
    }
}

async function captureElementAsImage(element, options = {}) {
    if (!element) return null;
    
    // Clonar o elemento para não afetar o original
    const clone = element.cloneNode(true);
    clone.style.width = options.width || '1800px';
    clone.style.position = 'absolute';
    clone.style.left = '0';
    clone.style.top = '0';
    clone.style.visibility = 'visible';
    clone.style.background = 'white';
    
    const container = document.getElementById('ganttCaptureContainer');
    container.innerHTML = '';
    container.appendChild(clone);
    container.style.display = 'block';
    
    // Esperar um pouco para o CSS ser aplicado
    await new Promise(resolve => setTimeout(resolve, 200));
    
    try {
        const canvas = await html2canvas(clone, {
            scale: 2,
            backgroundColor: '#ffffff',
            allowTaint: false,
            useCORS: true,
            logging: false,
            windowWidth: parseInt(options.width) || 1800
        });
        
        return canvas.toDataURL('image/png');
    } catch (error) {
        console.error('Erro ao capturar elemento:', error);
        return null;
    } finally {
        container.style.display = 'none';
        container.innerHTML = '';
    }
}

async function captureCapabilityCharts(characteristicId) {
    const chartElementId = `${characteristicId}_chart`;
    const chartElement = document.getElementById(chartElementId);
    
    if (!chartElement) {
        console.warn(`Elemento do gráfico não encontrado para ID: ${chartElementId}`);
        return null;
    }
    
    const canvas = chartElement.querySelector('canvas');
    if (!canvas) {
        console.warn(`Canvas não encontrado dentro do elemento: ${chartElementId}`);
        return null;
    }
    
    try {
        const canvasClone = canvas.cloneNode(true);
        canvasClone.width = canvas.width;
        canvasClone.height = canvas.height;
        
        const ctx = canvasClone.getContext('2d');
        ctx.drawImage(canvas, 0, 0);
        
        return canvasClone.toDataURL('image/png');
    } catch (error) {
        console.error('Erro ao capturar canvas:', error);
        return await captureElementAsImage(canvas, { width: 1200 });
    }
}

async function generateCompletePDF() {
    const projectId = currentTimelineProjectId;
    const project = projects.find(p => p.id === projectId);
    if (!project) {
        alert('Nenhum projeto selecionado.');
        return;
    }
    
    const includeApqp = document.getElementById('includeApqp')?.checked ?? true;
    const includeGantt = document.getElementById('includeGantt')?.checked ?? true;
    const includeCapability = document.getElementById('includeCapability')?.checked ?? true;
    const includeTimeline = document.getElementById('includeTimeline')?.checked ?? true;
    
    const pageConfig = getPageSizeConfig();
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF(pageConfig.orientation, pageConfig.unit, pageConfig.format);
    
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 15;
    const contentWidth = pageWidth - 2 * margin;
    let yPos = margin;
    
    function checkPageHeight(neededHeight) {
        if (yPos + neededHeight > pageHeight - margin) {
            doc.addPage(pageConfig.orientation, pageConfig.format);
            yPos = margin;
            return true;
        }
        return false;
    }
    
    // Logotipo
    if (companyLogo) {
        try {
            const img = await loadImage(companyLogo);
            
            const maxWidth = 50;
            const maxHeight = 20;
            
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
            yPos += logoHeight + 8;
        } catch (e) {
            console.warn('Erro ao adicionar logotipo:', e);
            yPos += 8;
        }
    } else {
        yPos += 8;
    }
    
    // Título
    doc.setFontSize(22);
    doc.setTextColor(46, 125, 50);
    doc.text("RELATÓRIO COMPLETO DO PROJETO", pageWidth / 2, yPos, { align: 'center' });
    yPos += 15;
    
    doc.setFontSize(14);
    doc.setTextColor(0, 0, 0);
    doc.text(`Projeto: ${project.projectName} (ID: ${project.id})`, margin, yPos);
    yPos += 8;
    doc.setFontSize(10);
    doc.text(`Cliente: ${project.cliente || '-'} | Líder: ${project.projectLeader || '-'} | Status: ${project.status}`, margin, yPos);
    yPos += 8;
    doc.text(`Código: ${project.codigo || '-'} | ANVI: ${project.anviNumber || '-'} | Modelo: ${project.modelo || '-'} | Processo: ${project.processo || '-'} | Fase: ${project.fase || '-'}`, margin, yPos);
    yPos += 12;
    
    doc.setDrawColor(46, 125, 50);
    doc.setLineWidth(0.5);
    doc.line(margin, yPos, pageWidth - margin, yPos);
    yPos += 10;
    
    // Progresso
    checkPageHeight(50);
    const progressData = calculateWeightedProjectProgress(project);
    
    doc.setFillColor(240, 248, 240);
    doc.roundedRect(margin, yPos, contentWidth, 40, 3, 3, 'F');
    
    doc.setFontSize(12);
    doc.setTextColor(33, 33, 33);
    doc.setFont(undefined, 'bold');
    doc.text("PROGRESSO DO PROJETO", margin + 10, yPos + 8);
    
    doc.setFontSize(10);
    doc.setFont(undefined, 'normal');
    doc.text(`Progresso Ponderado: ${progressData.progress.toFixed(1)}% (Concluído: ${progressData.completedWeight} | Total: ${progressData.totalWeight})`, margin + 10, yPos + 16);
    
    // Barra de progresso
    doc.setFillColor(200, 200, 200);
    doc.roundedRect(margin + 10, yPos + 22, 120, 8, 2, 2, 'F');
    doc.setFillColor(76, 175, 80);
    doc.roundedRect(margin + 10, yPos + 22, (progressData.progress / 100) * 120, 8, 2, 2, 'F');
    
    yPos += 45;
    
    // CAPTURAR GRÁFICO DE GANTT COMO IMAGEM
    if (includeGantt) {
        checkPageHeight(20);
        
        doc.setFillColor(46, 125, 50);
        doc.setTextColor(255, 255, 255);
        doc.rect(margin, yPos, contentWidth, 10, 'F');
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text("GRÁFICO DE GANTT", margin + 5, yPos + 7);
        yPos += 18;
        
        // Capturar o Gantt como imagem
        const ganttElement = document.getElementById('ganttContainer');
        if (ganttElement) {
            // Ajustar largura para captura
            const ganttWidth = pageConfig.orientation === 'l' ? 2500 : 1800;
            const ganttImage = await captureElementAsImage(ganttElement, { width: ganttWidth });
            
            if (ganttImage) {
                // Calcular altura da imagem no PDF mantendo proporção
                const img = await loadImage(ganttImage);
                const imgWidth = contentWidth;
                const imgHeight = (img.height * imgWidth) / img.width;
                
                checkPageHeight(imgHeight + 10);
                
                doc.addImage(ganttImage, 'PNG', margin, yPos, imgWidth, imgHeight);
                yPos += imgHeight + 10;
            } else {
                doc.setFontSize(10);
                doc.setTextColor(100, 100, 100);
                doc.text("Não foi possível gerar a imagem do Gantt.", margin, yPos);
                yPos += 10;
            }
        }
    }
    
    // SEÇÃO DE CAPABILIDADE
    if (includeCapability && project.capability && project.capability.characteristics && project.capability.characteristics.length > 0) {
        checkPageHeight(20);
        
        doc.setFillColor(46, 125, 50);
        doc.setTextColor(255, 255, 255);
        doc.rect(margin, yPos, contentWidth, 10, 'F');
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text("ESTUDO DE CAPABILIDADE", margin + 5, yPos + 7);
        yPos += 15;
        
        doc.setFontSize(10);
        doc.setTextColor(0, 0, 0);
        doc.setFont(undefined, 'normal');
        doc.text(`Data do Estudo: ${project.capability.studyDate ? formatDateBR(project.capability.studyDate) : '-'}`, margin, yPos);
        yPos += 8;
        
        // Para cada característica, mostrar resumo e capturar gráfico
        const characteristicsToShow = project.capability.characteristics;
        
        for (const [index, char] of characteristicsToShow.entries()) {
            checkPageHeight(80);
            
            doc.setFillColor(250, 250, 250);
            doc.rect(margin, yPos, contentWidth, 30, 'F');
            
            doc.setFontSize(11);
            doc.setFont(undefined, 'bold');
            doc.text(`${index + 1}. ${char.name || `Característica ${index + 1}`}`, margin + 5, yPos + 6);
            
            doc.setFontSize(9);
            doc.setFont(undefined, 'normal');
            doc.text(`Tipo: ${char.type === 'cc' ? 'CC' : 'SC'} | LIE: ${char.lie?.toFixed(3) || '-'} | LSE: ${char.lse?.toFixed(3) || '-'} | Média: ${char.stats?.mean?.toFixed(3) || '-'} | Desvio: ${char.stats?.stdDev?.toFixed(3) || '-'}`, margin + 5, yPos + 13);
            doc.text(`Cp: ${char.stats?.cp?.toFixed(3) || '-'} | Cpk: ${char.stats?.cpk?.toFixed(3) || '-'} | Nível Sigma: ${char.stats?.sigmaLevel?.toFixed(2) || '-'}`, margin + 5, yPos + 20);
            
            yPos += 35;
            
            // Capturar gráfico da característica - Usando o ID salvo
            if (char.id) {
                const chartImage = await captureCapabilityCharts(char.id);
                
                if (chartImage) {
                    checkPageHeight(120);
                    
                    const img = await loadImage(chartImage);
                    const imgWidth = contentWidth;
                    const imgHeight = (img.height * imgWidth) / img.width;
                    
                    doc.addImage(chartImage, 'PNG', margin, yPos, imgWidth, imgHeight);
                    yPos += imgHeight + 10;
                } else {
                    // Se não encontrar o elemento do gráfico, mostrar apenas texto
                    doc.setFontSize(9);
                    doc.setTextColor(100, 100, 100);
                    doc.text("(Gráfico não disponível para esta característica)", margin, yPos);
                    yPos += 8;
                }
            } else {
                doc.setFontSize(9);
                doc.setTextColor(100, 100, 100);
                doc.text("(ID da característica não encontrado)", margin, yPos);
                yPos += 8;
            }
        }
        
        // Resumo geral
        checkPageHeight(50);
        
        const totalChars = project.capability.characteristics.length;
        const capableChars = project.capability.characteristics.filter(c => c.stats?.cpk >= 1.33).length;
        const avgCpk = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.cpk || 0), 0) / totalChars;
        const avgSigma = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.sigmaLevel || 0), 0) / totalChars;
        const avgPpm = calculateDPMO(avgSigma).toFixed(0);
        
        doc.setFillColor(240, 248, 240);
        doc.roundedRect(margin, yPos, contentWidth, 35, 3, 3, 'F');
        
        doc.setFontSize(11);
        doc.setTextColor(33, 33, 33);
        doc.setFont(undefined, 'bold');
        doc.text("RESUMO GERAL DA CAPABILIDADE", margin + 10, yPos + 8);
        
        doc.setFontSize(9);
        doc.setFont(undefined, 'normal');
        doc.text(`Total de Características: ${totalChars} | Capazes: ${capableChars} (${((capableChars/totalChars)*100).toFixed(1)}%) | Cpk Médio: ${avgCpk.toFixed(3)}`, margin + 10, yPos + 15);
        doc.text(`Nível Sigma Médio: ${avgSigma.toFixed(2)} (${avgPpm} ppm)`, margin + 10, yPos + 22);
        
        yPos += 40;
    }
    
    // LINHA DO TEMPO DAS TAREFAS (como texto)
    if (includeTimeline) {
        checkPageHeight(40 + 120);
        
        doc.setFillColor(46, 125, 50);
        doc.setTextColor(255, 255, 255);
        doc.rect(margin, yPos, contentWidth, 10, 'F');
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text("LINHA DO TEMPO DAS TAREFAS", margin + 5, yPos + 7);
        yPos += 18;
        
        const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
        const taskNames = {
            'kom': 'KOM', 'ferramental': 'Ferramental', 'cadBomFt': 'CAD+BOM+FT', 
            'tryout': 'Try-out', 'entrega': 'Entrega', 'psw': 'PSW', 'handover': 'Handover'
        };
        
        taskKeys.forEach(key => {
            checkPageHeight(25);
            const task = project.tasks?.[key];
            if (!task) return;
            
            doc.setFillColor(250, 250, 250);
            doc.rect(margin, yPos, contentWidth, 20, 'F');
            
            doc.setFontSize(10);
            doc.setTextColor(0, 0, 0);
            doc.setFont(undefined, 'bold');
            doc.text(taskNames[key], margin + 5, yPos + 6);
            
            doc.setFontSize(9);
            doc.setFont(undefined, 'normal');
            doc.text(`Planejado: ${formatDateBR(task.planned) || '-'} | Início: ${formatDateBR(task.start) || '-'} | Conclusão: ${formatDateBR(task.executed) || '-'} | Duração: ${task.duration || getDefaultDuration(key)} dias`, margin + 5, yPos + 12);
            
            const taskStatus = calculateTaskStatus(task, project.status);
            const statusColors = {
                'Concluído': '#4caf50',
                'Em Andamento': '#2196f3',
                'Atrasado': '#f44336',
                'No Prazo': '#4caf50',
                'Pendente': '#ff9800',
                'Cancelado': '#9e9e9e',
                'Em Espera': '#ff9800'
            };
            
            doc.setFillColor(statusColors[taskStatus] || '#999');
            doc.rect(pageWidth - margin - 30, yPos + 5, 25, 8, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(8);
            doc.text(taskStatus, pageWidth - margin - 20, yPos + 10);
            
            yPos += 25;
        });
        
        yPos += 5;
    }
    
    // ANÁLISE APQP
    if (includeApqp) {
        checkPageHeight(20);
        
        doc.setFillColor(46, 125, 50);
        doc.setTextColor(255, 255, 255);
        doc.rect(margin, yPos, contentWidth, 10, 'F');
        doc.setFontSize(12);
        doc.setFont(undefined, 'bold');
        doc.text("ANÁLISE APQP - FASES 1 A 5", margin + 5, yPos + 7);
        yPos += 18;
        
        const phaseKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
        const phaseNames = {
            'kom': 'FASE 1 - Planejamento (KOM)',
            'ferramental': 'FASE 2 - Desenvolvimento do Produto (Ferramental)',
            'cadBomFt': 'FASE 2/3 - Desenvolvimento (CAD+BOM+FT)',
            'tryout': 'FASE 3/4 - Desenvolvimento do Processo (Try-out)',
            'entrega': 'FASE 4 - Validação (Entrega)',
            'psw': 'FASE 4 - Validação (PSW)',
            'handover': 'FASE 4/5 - Validação e Retroalimentação (Handover)'
        };
        
        phaseKeys.forEach(phase => {
            const phaseData = project.apqp?.[phase];
            const questions = APQP_QUESTIONS[phase] || [];
            
            checkPageHeight(questions.length * 15 + 40);
            
            // Cabeçalho da fase
            doc.setFillColor(33, 33, 33);
            doc.rect(margin, yPos, contentWidth, 12, 'F');
            
            doc.setFontSize(11);
            doc.setTextColor(255, 255, 255);
            doc.setFont(undefined, 'bold');
            doc.text(phaseNames[phase], margin + 5, yPos + 5);
            
            // Resumo da fase
            const totalQ = questions.length;
            let answeredQ = 0;
            if (phaseData && phaseData.answers) {
                answeredQ = Object.keys(phaseData.answers).length;
            }
            const percentComplete = totalQ > 0 ? (answeredQ / totalQ) * 100 : 0;
            
            doc.setFontSize(8);
            doc.text(`${answeredQ}/${totalQ} respondidas (${percentComplete.toFixed(0)}%)`, pageWidth - margin - 40, yPos + 5);
            
            yPos += 15;
            
            if (!phaseData || !phaseData.answers || Object.keys(phaseData.answers).length === 0) {
                doc.setFontSize(9);
                doc.setTextColor(100, 100, 100);
                doc.setFont(undefined, 'italic');
                doc.text("Nenhuma resposta registrada para esta fase.", margin + 10, yPos);
                yPos += 8;
            } else {
                // Listar perguntas com respostas
                questions.forEach((q, idx) => {
                    const answer = phaseData.answers[q.id];
                    
                    checkPageHeight(12);
                    
                    doc.setFillColor(250, 250, 250);
                    doc.rect(margin, yPos, contentWidth, 10, 'F');
                    
                    doc.setFontSize(8);
                    doc.setTextColor(33, 33, 33);
                    doc.setFont(undefined, 'normal');
                    
                    // Quebrar a pergunta em várias linhas se necessário
                    const questionLines = doc.splitTextToSize(`${idx + 1}. ${q.question}`, contentWidth - 80);
                    doc.text(questionLines, margin + 5, yPos + 3);
                    
                    if (answer) {
                        let answerText = answer.answer === 'sim' ? 'Sim' : (answer.answer === 'nao' ? 'Não' : 'N/A');
                        let answerColor = answer.answer === 'sim' ? '#4caf50' : (answer.answer === 'nao' ? '#f44336' : '#ff9800');
                        
                        doc.setTextColor(255, 255, 255);
                        doc.setFillColor(answerColor);
                        doc.rect(pageWidth - margin - 25, yPos + 2, 20, 6, 'F');
                        doc.setFontSize(7);
                        doc.text(answerText, pageWidth - margin - 18, yPos + 6);
                        
                        if (answer.observations) {
                            doc.setFontSize(6);
                            doc.setTextColor(150, 150, 150);
                            doc.text(`Obs: ${answer.observations}`, margin + 5, yPos + 8);
                        }
                    } else {
                        doc.setTextColor(150, 150, 150);
                        doc.setFontSize(7);
                        doc.text("Não respondida", pageWidth - margin - 20, yPos + 6);
                    }
                    
                    yPos += questionLines.length * 4 + 10;
                });
            }
            
            yPos += 5;
        });
    }
    
    // Rodapé
    const totalPages = doc.internal.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text(`Gerado em: ${new Date().toLocaleDateString('pt-BR')} ${new Date().toLocaleTimeString('pt-BR')}`, margin, pageHeight - 5);
        doc.text(`Página ${i} de ${totalPages}`, pageWidth - margin, pageHeight - 5, { align: 'right' });
    }
    
    const sizeName = pageConfig.orientation === 'p' ? 'retrato' : 'paisagem';
    const fileName = `projeto_${project.projectName.replace(/[^a-z0-9]/gi, '_')}_${project.id}_${sizeName}.pdf`;
    doc.save(fileName);
    
    document.getElementById('pdfOptionsModal').style.display = 'none';
}

// ==============================================
// FUNÇÕES DE HANDOVER (manter as originais, usando nova função de progresso)
// ==============================================
function generateHandoverReport() {
    const projectId = currentTimelineProjectId;
    const project = projects.find(p => p.id === projectId);
    if (!project) {
        alert('Nenhum projeto selecionado.');
        return;
    }
    
    closeAllModals();
    document.getElementById('handoverReportTitle').textContent = `Relatório Handover - ${project.projectName}`;
    
    const content = document.getElementById('handoverReportContent');
    
    const progressData = calculateWeightedProjectProgress(project);
    
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
    
    // Calcular métricas
    const totalTasks = taskKeys.length;
    const completedTasks = taskKeys.filter(key => {
        const task = project.tasks?.[key];
        return task && task.executed;
    }).length;
    
    const delayedTasks = taskKeys.filter(key => {
        const task = project.tasks?.[key];
        if (!task || task.executed) return false;
        const taskStatus = calculateTaskStatus(task, project.status);
        return taskStatus === 'Atrasado';
    }).length;
    
    const apqpCompleted = taskKeys.filter(key => {
        const status = getApqpStatus(project, key);
        return status.status === 'completed';
    }).length;
    
    let capabilitySummary = '';
    if (project.capability && project.capability.characteristics && project.capability.characteristics.length > 0) {
        const totalChars = project.capability.characteristics.length;
        const capableChars = project.capability.characteristics.filter(c => c.stats?.cpk >= 1.33).length;
        const avgCpk = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.cpk || 0), 0) / totalChars;
        const avgSigma = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.sigmaLevel || 0), 0) / totalChars;
        const avgPpm = calculateDPMO(avgSigma).toFixed(0);
        capabilitySummary = `${capableChars}/${totalChars} características capazes | Cpk médio: ${avgCpk.toFixed(2)} | Nível Sigma: ${avgSigma.toFixed(2)} (${avgPpm} ppm)`;
    } else {
        capabilitySummary = 'Não realizado';
    }
    
    const handoverTask = project.tasks?.handover;
    const handoverStatus = handoverTask ? calculateTaskStatus(handoverTask, project.status) : 'Pendente';
    
    let handoverHTML = `
        <div class="handover-report-section">
            <h3><i class="fas fa-info-circle"></i> Informações Gerais do Projeto</h3>
            <div class="handover-report-grid">
                <div class="handover-report-item">
                    <div class="handover-report-label">Cliente</div>
                    <div class="handover-report-value">${project.cliente || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Projeto</div>
                    <div class="handover-report-value">${project.projectName || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Código</div>
                    <div class="handover-report-value">${project.codigo || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">ANVI</div>
                    <div class="handover-report-value">${project.anviNumber || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Líder</div>
                    <div class="handover-report-value">${project.projectLeader || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Segmento</div>
                    <div class="handover-report-value">${project.segmento || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Modelo</div>
                    <div class="handover-report-value">${project.modelo || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Processo</div>
                    <div class="handover-report-value">${project.processo || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Fase</div>
                    <div class="handover-report-value">${project.fase || '-'}</div>
                </div>
                <div class="handover-report-item">
                    <div class="handover-report-label">Status do Projeto</div>
                    <div class="handover-report-value"><span class="status status-${project.status.toLowerCase().replace(/\s/g, '-')}">${project.status}</span></div>
                </div>
            </div>
        </div>
        
        <div class="handover-report-section">
            <h3><i class="fas fa-chart-pie"></i> Métricas do Projeto</h3>
            <div class="handover-metrics-grid">
                <div class="handover-metric-card">
                    <div class="handover-metric-label">Progresso Ponderado</div>
                    <div class="handover-metric-value">${progressData.progress.toFixed(1)}%</div>
                    <div class="handover-metric-label">(${progressData.completedWeight}/${progressData.totalWeight})</div>
                </div>
                <div class="handover-metric-card">
                    <div class="handover-metric-label">Tarefas Concluídas</div>
                    <div class="handover-metric-value">${completedTasks}/${totalTasks}</div>
                </div>
                <div class="handover-metric-card">
                    <div class="handover-metric-label">Tarefas Atrasadas</div>
                    <div class="handover-metric-value">${delayedTasks}</div>
                </div>
                <div class="handover-metric-card">
                    <div class="handover-metric-label">APQP Completo</div>
                    <div class="handover-metric-value">${apqpCompleted}/${totalTasks}</div>
                </div>
                <div class="handover-metric-card">
                    <div class="handover-metric-label">Capabilidade</div>
                    <div class="handover-metric-value" style="font-size: 1.2rem;">${capabilitySummary}</div>
                </div>
            </div>
        </div>
        
        <div class="handover-report-section">
            <h3><i class="fas fa-tasks"></i> Status das Tarefas</h3>
            <div class="handover-task-status">
    `;
    
    taskKeys.forEach(key => {
        const task = project.tasks?.[key];
        if (!task) return;
        
        const taskStatus = calculateTaskStatus(task, project.status);
        const apqpStatus = getApqpStatus(project, key);
        
        let apqpText = 'APQP: ';
        if (apqpStatus.status === 'completed') apqpText += '✓ Completo';
        else if (apqpStatus.status === 'partial') apqpText += `⚠ Parcial (${apqpStatus.answered}/${apqpStatus.total})`;
        else apqpText += '✗ Não iniciado';
        
        handoverHTML += `
            <div class="handover-task-card">
                <div class="handover-task-header">
                    <span class="handover-task-name">${taskNames[key]}</span>
                    <span class="status status-${taskStatus.toLowerCase().replace(/\s/g, '-')}">${taskStatus}</span>
                </div>
                <div class="handover-task-dates">
                    ${task.planned ? `<div><strong>Planejado:</strong> ${formatDateBR(task.planned)}</div>` : ''}
                    ${task.start ? `<div><strong>Início:</strong> ${formatDateBR(task.start)}</div>` : ''}
                    ${task.executed ? `<div><strong>Conclusão:</strong> ${formatDateBR(task.executed)}</div>` : ''}
                    <div><strong>Duração:</strong> ${task.duration || getDefaultDuration(key)} dias</div>
                </div>
                <div class="handover-apqp-summary">
                    <h4>${apqpText}</h4>
                </div>
            </div>
        `;
    });
    
    handoverHTML += `
            </div>
        </div>
        
        <div class="handover-report-section">
            <h3><i class="fas fa-clipboard-list"></i> Observações para Transferência</h3>
            <div class="handover-observations">
                <textarea id="handoverObservations" placeholder="Adicione observações importantes para o handover do projeto...">${project.observacoes || ''}</textarea>
            </div>
        </div>
        
        <div class="handover-report-actions">
            <button class="btn btn-primary" onclick="printHandoverReport()">
                <i class="fas fa-print"></i> Imprimir Relatório
            </button>
            <button class="btn btn-success" onclick="generateHandoverReportPDF()">
                <i class="fas fa-file-pdf"></i> Gerar PDF
            </button>
        </div>
    `;
    
    content.innerHTML = handoverHTML;
    document.getElementById('handoverReportModal').style.display = 'block';
}

function printHandoverReport() {
    const projectId = currentTimelineProjectId;
    const project = projects.find(p => p.id === projectId);
    if (!project) {
        alert('Nenhum projeto selecionado.');
        return;
    }

    const printContent = document.getElementById('handoverReportContent').innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Relatório Handover - ${project.projectName}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    .handover-report-section { margin-bottom: 25px; }
                    .handover-report-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
                    .handover-report-item { border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
                    .handover-report-label { font-weight: bold; color: #666; }
                    .handover-metrics-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
                    .handover-metric-card { border: 1px solid #ddd; padding: 15px; text-align: center; border-radius: 5px; }
                    .handover-metric-value { font-size: 24px; font-weight: bold; color: #2e7d32; }
                    .handover-task-status { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
                    .handover-task-card { border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
                    .status { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 12px; }
                    .status-concluido { background: #4caf50; color: white; }
                    .status-em-andamento { background: #2196f3; color: white; }
                    .status-atrasado { background: #f44336; color: white; }
                    .status-no-prazo { background: #4caf50; color: white; }
                    .status-pendente { background: #ff9800; color: white; }
                    button, .handover-report-actions, .modal .close { display: none !important; }
                </style>
            </head>
            <body>
                <h1>Relatório Handover - ${project.projectName}</h1>
                <p>Gerado em: ${new Date().toLocaleDateString('pt-BR')} ${new Date().toLocaleTimeString('pt-BR')}</p>
                ${printContent}
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

async function generateHandoverReportPDF() {
    const projectId = currentTimelineProjectId;
    const project = projects.find(p => p.id === projectId);
    if (!project) {
        alert('Nenhum projeto selecionado.');
        return;
    }
    
    const observations = document.getElementById('handoverObservations')?.value || '';
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 15;
    let yPos = margin;
    
    function checkPageHeight(neededHeight) {
        if (yPos + neededHeight > pageHeight - margin) {
            doc.addPage();
            yPos = margin;
            return true;
        }
        return false;
    }
    
    // Logotipo
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
            console.warn('Erro ao adicionar logotipo:', e);
            yPos += 5;
        }
    } else {
        yPos += 5;
    }
    
    // Título
    doc.setFontSize(20);
    doc.setTextColor(46, 125, 50);
    doc.text("RELATÓRIO HANDOVER", pageWidth / 2, yPos, { align: 'center' });
    yPos += 15;
    
    doc.setFontSize(14);
    doc.setTextColor(0, 0, 0);
    doc.text(`Projeto: ${project.projectName} (ID: ${project.id})`, margin, yPos);
    yPos += 8;
    doc.setFontSize(10);
    doc.text(`Cliente: ${project.cliente || '-'} | Líder: ${project.projectLeader || '-'} | Data: ${new Date().toLocaleDateString('pt-BR')}`, margin, yPos);
    yPos += 10;
    
    doc.setDrawColor(46, 125, 50);
    doc.setLineWidth(0.5);
    doc.line(margin, yPos, pageWidth - margin, yPos);
    yPos += 10;
    
    // Informações Gerais
    checkPageHeight(50);
    doc.setFillColor(240, 248, 240);
    doc.roundedRect(margin, yPos, pageWidth - 2 * margin, 40, 3, 3, 'F');
    
    doc.setFontSize(12);
    doc.setTextColor(33, 33, 33);
    doc.setFont(undefined, 'bold');
    doc.text("INFORMAÇÕES GERAIS", margin + 10, yPos + 8);
    
    doc.setFontSize(9);
    doc.setFont(undefined, 'normal');
    doc.text(`Código: ${project.codigo || '-'} | ANVI: ${project.anviNumber || '-'} | Modelo: ${project.modelo || '-'} | Processo: ${project.processo || '-'}`, margin + 10, yPos + 15);
    doc.text(`Segmento: ${project.segmento || '-'} | Fase: ${project.fase || '-'} | Status: ${project.status}`, margin + 10, yPos + 22);
    doc.text(`Observações: ${project.observacoes || 'Nenhuma observação'}`, margin + 10, yPos + 29);
    
    yPos += 45;
    
    // Métricas
    checkPageHeight(60);
    const progressData = calculateWeightedProjectProgress(project);
    
    const taskKeys = ['kom', 'ferramental', 'cadBomFt', 'tryout', 'entrega', 'psw', 'handover'];
    const totalTasks = taskKeys.length;
    const completedTasks = taskKeys.filter(key => {
        const task = project.tasks?.[key];
        return task && task.executed;
    }).length;
    
    const delayedTasks = taskKeys.filter(key => {
        const task = project.tasks?.[key];
        if (!task || task.executed) return false;
        const taskStatus = calculateTaskStatus(task, project.status);
        return taskStatus === 'Atrasado';
    }).length;
    
    const apqpCompleted = taskKeys.filter(key => {
        const status = getApqpStatus(project, key);
        return status.status === 'completed';
    }).length;
    
    let capabilityText = 'Não realizado';
    if (project.capability && project.capability.characteristics && project.capability.characteristics.length > 0) {
        const totalChars = project.capability.characteristics.length;
        const capableChars = project.capability.characteristics.filter(c => c.stats?.cpk >= 1.33).length;
        const avgCpk = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.cpk || 0), 0) / totalChars;
        const avgSigma = project.capability.characteristics.reduce((sum, c) => sum + (c.stats?.sigmaLevel || 0), 0) / totalChars;
        const avgPpm = calculateDPMO(avgSigma).toFixed(0);
        capabilityText = `${capableChars}/${totalChars} capazes | Cpk médio: ${avgCpk.toFixed(2)} | Sigma: ${avgSigma.toFixed(2)} (${avgPpm} ppm)`;
    }
    
    doc.setFillColor(46, 125, 50);
    doc.setTextColor(255, 255, 255);
    doc.rect(margin, yPos, pageWidth - 2 * margin, 10, 'F');
    doc.setFontSize(11);
    doc.setFont(undefined, 'bold');
    doc.text("MÉTRICAS DO PROJETO", margin + 5, yPos + 7);
    yPos += 15;
    
    const metrics = [
        { label: 'Progresso Ponderado', value: `${progressData.progress.toFixed(1)}%` },
        { label: 'Tarefas Concluídas', value: `${completedTasks}/${totalTasks}` },
        { label: 'Tarefas Atrasadas', value: delayedTasks },
        { label: 'APQP Completo', value: `${apqpCompleted}/${totalTasks}` },
        { label: 'Capabilidade', value: capabilityText }
    ];
    
    let xPos = margin;
    const colWidth = (pageWidth - 2 * margin) / 3;
    
    metrics.forEach((metric, index) => {
        if (index % 3 === 0 && index > 0) {
            yPos += 25;
            xPos = margin;
        }
        
        doc.setFillColor(250, 250, 250);
        doc.rect(xPos, yPos, colWidth - 5, 20, 'F');
        doc.setDrawColor(200, 200, 200);
        doc.rect(xPos, yPos, colWidth - 5, 20);
        
        doc.setFontSize(8);
        doc.setTextColor(100, 100, 100);
        doc.setFont(undefined, 'normal');
        doc.text(metric.label, xPos + 5, yPos + 5);
        
        doc.setFontSize(12);
        doc.setTextColor(46, 125, 50);
        doc.setFont(undefined, 'bold');
        doc.text(metric.value, xPos + 5, yPos + 15);
        
        xPos += colWidth;
    });
    
    yPos += 30;
    
    // Tarefas
    checkPageHeight(40 + taskKeys.length * 15);
    doc.setFillColor(46, 125, 50);
    doc.setTextColor(255, 255, 255);
    doc.rect(margin, yPos, pageWidth - 2 * margin, 10, 'F');
    doc.setFontSize(11);
    doc.setFont(undefined, 'bold');
    doc.text("STATUS DAS TAREFAS", margin + 5, yPos + 7);
    yPos += 15;
    
    const taskNames = {
        'kom': 'KOM', 'ferramental': 'Ferramental', 'cadBomFt': 'CAD+BOM+FT', 
        'tryout': 'Try-out', 'entrega': 'Entrega', 'psw': 'PSW', 'handover': 'Handover'
    };
    
    taskKeys.forEach(key => {
        const task = project.tasks?.[key];
        if (!task) return;
        
        checkPageHeight(15);
        
        const taskStatus = calculateTaskStatus(task, project.status);
        const apqpStatus = getApqpStatus(project, key);
        
        doc.setFillColor(250, 250, 250);
        doc.rect(margin, yPos, pageWidth - 2 * margin, 12, 'F');
        
        doc.setFontSize(9);
        doc.setTextColor(0, 0, 0);
        doc.setFont(undefined, 'bold');
        doc.text(taskNames[key], margin + 5, yPos + 4);
        
        doc.setFontSize(8);
        doc.setFont(undefined, 'normal');
        doc.text(`Planejado: ${formatDateBR(task.planned) || '-'} | Conclusão: ${formatDateBR(task.executed) || '-'} | APQP: ${apqpStatus.answered}/${apqpStatus.total}`, margin + 35, yPos + 4);
        
        const statusColors = {
            'Concluído': '#4caf50',
            'Em Andamento': '#2196f3',
            'Atrasado': '#f44336',
            'No Prazo': '#4caf50',
            'Pendente': '#ff9800',
            'Cancelado': '#9e9e9e',
            'Em Espera': '#ff9800'
        };
        
        doc.setFillColor(statusColors[taskStatus] || '#999');
        doc.rect(pageWidth - margin - 25, yPos + 2, 20, 6, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(7);
        doc.text(taskStatus, pageWidth - margin - 18, yPos + 6);
        
        yPos += 15;
    });
    
    // Observações
    checkPageHeight(40);
    doc.setFillColor(46, 125, 50);
    doc.setTextColor(255, 255, 255);
    doc.rect(margin, yPos, pageWidth - 2 * margin, 10, 'F');
    doc.setFontSize(11);
    doc.setFont(undefined, 'bold');
    doc.text("OBSERVAÇÕES PARA TRANSFERÊNCIA", margin + 5, yPos + 7);
    yPos += 15;
    
    doc.setFillColor(250, 250, 250);
    doc.rect(margin, yPos, pageWidth - 2 * margin, 30, 'F');
    doc.setDrawColor(200, 200, 200);
    doc.rect(margin, yPos, pageWidth - 2 * margin, 30);
    
    doc.setFontSize(9);
    doc.setTextColor(33, 33, 33);
    doc.setFont(undefined, 'normal');
    
    const obsLines = doc.splitTextToSize(observations || project.observacoes || 'Nenhuma observação registrada.', pageWidth - 2 * margin - 10);
    doc.text(obsLines, margin + 5, yPos + 5);
    
    yPos += 35;
    
    // Rodapé
    const totalPages = doc.internal.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text(`Gerado em: ${new Date().toLocaleDateString('pt-BR')} ${new Date().toLocaleTimeString('pt-BR')}`, margin, pageHeight - 5);
        doc.text(`Página ${i} de ${totalPages}`, pageWidth - margin, pageHeight - 5, { align: 'right' });
    }
    
    const fileName = `handover_${project.projectName.replace(/[^a-z0-9]/gi, '_')}_${project.id}.pdf`;
    doc.save(fileName);
}

// ==============================================
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
            
            // Salvar no MySQL
            saveSingleProjectToMySQL(project);
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
    
    // Tentar conectar ao MySQL
    testMysqlConnection();
    
    // Iniciar sincronização em tempo real
    initRealtimeSync();
    
    updateLeaderFilter();
    updateTaskLeaderFilter();
    updateProjectLeaderSelect();
    updateLeadersList();
    setupEventListeners();
    setupModalCloseHandlers();
    
    const dateInput = document.getElementById('capabilityStudyDate');
    if (dateInput && !dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // Inicializar com dados vazios até carregar do MySQL
    projects = [];
    updateProjectsTable();
    updateSummary();
    
    // Executar verificação imediatamente após 2 segundos (tempo para carregar os dados)
    setTimeout(autoUpdateTaskStatuses, 2000);
    
    // Verificar status automaticamente a cada 30 segundos (30000ms)
    // Isso garante que tarefas vencidas sejam marcadas como atrasadas automaticamente
    setInterval(autoUpdateTaskStatuses, 30000);
    
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

// =========================================
// INTEGRAÇÃO COM ANVI
// =========================================

// Variável global para armazenar dados do vínculo
let vinculoANVI = null;

// Verificar se o projeto selecionado está vinculado a uma ANVI
async function verificarVinculoComANVI(projetoId) {
    if (!projetoId) {
        document.getElementById('btnVerANVI').style.display = 'none';
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
            document.getElementById('btnVerANVI').title = `ANVI: ${vinculoANVI.anvi.nome}`;
        } else {
            // Projeto não está vinculado
            document.getElementById('btnVerANVI').style.display = 'none';
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
</script>
</body>
</html>