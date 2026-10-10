package com.viabix.app.presentation.screens.projects

data class ApqpQuestionDefinition(
    val id: String,
    val question: String,
    val category: String
)

data class ApqpAnswerFormState(
    val answer: String = "",
    val observations: String = ""
)

data class ProjectResourceDefinition(
    val key: String,
    val label: String,
    val options: List<String> = emptyList(),
    val isDate: Boolean = false
)

internal val FERRAMENTAL_RESOURCES = listOf(
    ProjectResourceDefinition("femea", "Fêmea", isDate = true),
    ProjectResourceDefinition("gabaritoFanavid", "Gabarito Fanavid", isDate = true),
    ProjectResourceDefinition("gabaritoUsinado", "Gabarito Usinado", isDate = true),
    ProjectResourceDefinition("matriz", "Matriz", isDate = true),
    ProjectResourceDefinition("macho", "Macho", isDate = true),
    ProjectResourceDefinition("template", "Template", isDate = true),
    ProjectResourceDefinition("chapelona", "Chapelona", isDate = true),
    ProjectResourceDefinition("plotter", "Plotter", isDate = true),
    ProjectResourceDefinition("tela", "Tela", isDate = true)
)

internal val TRYOUT_RESOURCES = listOf(
    ProjectResourceDefinition(
        "corte", "Corte",
        listOf("Bottero", "Bottero+ Bystronic 02", "Bottero+ Bystronic 04", "Bottero+ Bystronic 05", "Bottero+ Bystronic 06", "Intermac - Jumbo")
    ),
    ProjectResourceDefinition(
        "lapidacao", "Lapidação",
        listOf("Bystronic 02", "Bystronic 04", "Bystronic 05", "Bystronic 06", "Intermac 01 - P1", "Intermac 02 - P1", "Intermac 01 - P2", "Lixa", "Americana", "Biseladora")
    ),
    ProjectResourceDefinition(
        "furacao", "Furação / Rec.",
        listOf("Bystronic 02 + Intermac", "Bystronic 05 + Intermac", "Bystronic 05", "Intermac + Toledo", "Intermac", "Toledo")
    ),
    ProjectResourceDefinition("montagem", "Montagem", listOf("Autos", "Arquitetura", "Ônibus", "Blindados")),
    ProjectResourceDefinition("serigrafia", "Serigrafia", listOf("Svécia", "Cugher", "Dip Tech", "Manual")),
    ProjectResourceDefinition("queima", "Queima", listOf("F. Verical BLD", "HTF")),
    ProjectResourceDefinition(
        "fornos", "Fornos",
        listOf("KBFO 1", "KBFO 2", "HTBS", "HTF", "ESU", "MATRIX-P1", "MATRIX-P2", "GLASS ROBOT-P2", "SCREEN MAX-P2", "F1-P2", "F2-P2", "F3-P2", "F4-P2", "F6-P2", "FB1-P2", "FB2-P2", "FB3-P2", "F7-P2")
    )
)

private fun q(id: String, question: String, category: String) =
    ApqpQuestionDefinition(id, question, category)

internal val APQP_QUESTIONS: Map<String, List<ApqpQuestionDefinition>> = mapOf(
    "kom" to listOf(
        q("f1_q1", "A Análise de Viabilidade (ANVI) foi concluída?", "FASE 1 - Planejamento"),
        q("f1_q2", "Decisão de Fornecimento: As partes interessadas principais foram identificadas e envolvidas?", "FASE 1 - Planejamento"),
        q("f1_q3", "Todos os requisitos do cliente foram identificados e documentados?", "FASE 1 - Planejamento"),
        q("f1_q4", "O escopo do projeto está claramente definido e alinhado com o cliente?", "FASE 1 - Planejamento"),
        q("f1_q5", "Pedido de Compras, Notificação ou Solicitação de desenvolvimento comercial foi emitido?", "FASE 1 - Planejamento"),
        q("f1_q6", "Os recursos necessários foram identificados e alocados?", "FASE 1 - Planejamento"),
        q("f1_q7", "O cronograma inicial foi desenvolvido e aprovado?", "FASE 1 - Planejamento"),
        q("f1_q8", "As especificações de embalagem foram elaboradas e aprovadas?", "FASE 1 - Planejamento")
    ),
    "ferramental" to listOf(
        q("f2_q1", "Instalações, ferramentas e dispositivos foram avaliados e validados?", "FASE 2 - Desenvolvimento do Produto"),
        q("f2_q2", "Os desenhos do ferramental foram aprovados pela engenharia?", "FASE 2 - Desenvolvimento do Produto"),
        q("f2_q3", "Os fornecedores de componentes especializados foram contratados?", "FASE 2 - Desenvolvimento do Produto"),
        q("f2_q4", "Os materiais para o ferramental estão disponíveis ou foram solicitados?", "FASE 2 - Desenvolvimento do Produto"),
        q("f2_q5", "As tolerâncias dimensionais dos ferramentais foram verificadas e validadas?", "FASE 2 - Desenvolvimento do Produto"),
        q("f2_q6", "Foram solicitados a confecção dos ferramentais fabricados internamente e a compra dos externos?", "FASE 2 - Desenvolvimento do Produto"),
        q("f2_q7", "Foi definida data para o try-out de ferramentas/dispositivos?", "FASE 2 - Desenvolvimento do Produto"),
        q("f2_q8", "Construção de protótipo (se requerido)?", "FASE 2 - Desenvolvimento do Produto")
    ),
    "cadBomFt" to listOf(
        q("f2_q9", "Os modelos CAD 3D estão completos e validados?", "FASE 2 - Desenvolvimento do Produto"),
        q("f2_q10", "A pré-lista de materiais (BOM) do produto foi definida?", "FASE 2 - Desenvolvimento do Produto"),
        q("f2_q11", "As fichas técnicas foram elaboradas e aprovadas?", "FASE 2 - Desenvolvimento do Produto"),
        q("f2_q12", "As tolerâncias geométricas estão corretamente aplicadas?", "FASE 2 - Desenvolvimento do Produto"),
        q("f2_q13", "Os desenhos CAD de fabricação estão liberados para produção?", "FASE 2 - Desenvolvimento do Produto"),
        q("f3_q2", "A lista de materiais (BOM) do produto está completa e revisada?", "FASE 3 - Desenvolvimento do Processo")
    ),
    "tryout" to listOf(
        q("f3_q3", "O fluxograma do processo de manufatura foi elaborado e aprovado?", "FASE 3 - Desenvolvimento do Processo"),
        q("f3_q4", "O FMEA de processo foi elaborado e aprovado?", "FASE 3 - Desenvolvimento do Processo"),
        q("f3_q5", "A avaliação dos sistemas de medição (M.S.A) foi elaborada e aprovada?", "FASE 3 - Desenvolvimento do Processo"),
        q("f3_q6", "As instruções de processo para o operador foram elaboradas e aprovadas?", "FASE 3 - Desenvolvimento do Processo"),
        q("f4_q1", "O ferramental foi montado e inspecionado antes do tryout?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q2", "As matérias-primas para o tryout estão disponíveis e aprovadas?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q3", "Os parâmetros de processo foram definidos e documentados?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q4", "O Trial Run da produção foi definido?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q5", "O plano de controle da produção foi elaborado?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q6", "Estudos preliminares de capabilidade do processo foram realizados?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q7", "Foi solicitada a Ordem de Produção (OP) de Desenvolvimento?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q8", "Teste de validação da produção / dimensional: As amostras foram inspecionadas dimensionalmente?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q9", "Os resultados do tryout atendem aos requisitos do cliente?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q10", "As não conformidades foram identificadas e tratadas?", "FASE 4 - Validação do Produto/Processo")
    ),
    "entrega" to listOf(
        q("f4_q11", "A documentação de entrega da amostra está completa e revisada?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q12", "Os prazos de entrega estão alinhados com as expectativas do cliente?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q13", "Os certificados de conformidade foram emitidos?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q14", "As amostras de aprovação foram validadas pelo cliente?", "FASE 4 - Validação do Produto/Processo")
    ),
    "psw" to listOf(
        q("f4_q15", "PPAP dos sub-fornecedores foi avaliado e aprovado?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q16", "Todos os documentos do PSW estão completos?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q17", "Os resultados dos testes estão dentro das especificações?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q18", "Os registros de produção estão disponíveis e organizados?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q19", "O PSW foi revisado e aprovado pela qualidade?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q20", "O cliente aprovou o PSW formalmente?", "FASE 4 - Validação do Produto/Processo")
    ),
    "handover" to listOf(
        q("f4_q21", "A transição de desenvolvimento para produção (Handover) foi realizada?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q22", "Todo o conhecimento do projeto foi documentado?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q23", "A equipe de produção foi treinada no novo produto/processo?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q24", "A documentação final está arquivada no sistema de gestão?", "FASE 4 - Validação do Produto/Processo"),
        q("f4_q25", "O projeto foi formalmente encerrado com o cliente?", "FASE 4 - Validação do Produto/Processo"),
        q("f5_q1", "Lições aprendidas / Análise das reclamações: As lições aprendidas foram registradas e compartilhadas?", "FASE 5 - Retroalimentação e Ação Corretiva")
    )
)
