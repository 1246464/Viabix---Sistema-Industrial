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
const userNivel = window.viabixProjectUser?.nivel || '';
const userName = window.viabixProjectUser?.nome || '';
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

