package com.viabix.app.presentation.screens.viabilidade

data class ViabilidadeUiState(
    val isLoading: Boolean = false,
    val anviId: String = "",
    val anviData: AnviData? = null,
    val analiseData: AnaliseData? = null,
    val viabilidadeData: ViabilidadeData? = null,
    val compatibilidades: List<Compatibilidade> = emptyList(),
    val error: String? = null
)

data class AnviData(
    val id: String,
    val numero: String,
    val revisao: String,
    val cliente: String,
    val projeto: String,
    val produto: String,
    val status: String,
    val data_anvi: String,
    val data_criacao: String
)

data class AnaliseData(
    val financeiro: FinanceiroAnalise,
    val planejamento: PlanejamentoAnalise,
    val qualidade: QualidadeAnalise,
    val recursos: RecursosAnalise
)

data class FinanceiroAnalise(
    val investimento: Int,
    val margem: Int,
    val investimento_total: Int,
    val roi_esperado: Int,
    val payback_meses: Int,
    val duracao_meses: Int,
    val riscos: List<String>,
    val score: Int
)

data class PlanejamentoAnalise(
    val duracao_meses: Int,
    val fases: Int,
    val score: Int
)

data class QualidadeAnalise(
    val cobertura_testes: Int,
    val score_codigo: Int,
    val score: Double
)

data class RecursosAnalise(
    val equipe: Int,
    val especialistas: Int,
    val score: Int
)

data class ViabilidadeData(
    val score_geral: Double,
    val status: String,
    val recomendacao: String
)

data class Compatibilidade(
    val area: String,
    val status: String,
    val score: Double,
    val detalhes: String
)
