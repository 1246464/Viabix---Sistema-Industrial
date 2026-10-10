package com.viabix.app.presentation.screens.anvis

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.google.gson.JsonObject
import com.viabix.app.data.api.RetrofitClient
import com.viabix.app.data.api.friendlyConnectionError
import com.viabix.app.data.api.friendlyError
import com.viabix.app.presentation.components.ErrorBlock
import com.viabix.app.presentation.components.GuidanceSummary
import com.viabix.app.presentation.components.LoadingBlock
import com.viabix.app.presentation.components.ModuleGuidanceCard
import com.viabix.app.presentation.components.NativeDateField
import com.viabix.app.presentation.components.NativeTopBar
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import javax.inject.Inject

data class AnviFormUiState(
    val loading: Boolean = false,
    val saving: Boolean = false,
    val editing: Boolean = false,
    val id: String = "",
    val numero: String = "",
    val revisao: String = "",
    val cliente: String = "",
    val projeto: String = "",
    val produto: String = "",
    val volumeMensal: String = "1000",
    val dataAnvi: String = "",
    val status: String = "em-andamento",
    val margemLucroMarkup: String = "20",
    val custoUnitario: String = "",
    val custoTotalMensal: String = "",
    val precoSugerido: String = "",
    val receitaMensal: String = "",
    val lucroLiquidoMensal: String = "",
    val investimentoTotal: String = "",
    val ppap: Boolean = false,
    val viabilidadeTecnica: Boolean = false,
    val viabilidadeEconomica: Boolean = false,
    val viabilidadeComercial: Boolean = false,
    val viabilidadeFiscal: Boolean = false,
    val error: String? = null,
    val saved: Boolean = false
)

@HiltViewModel
class AnviFormViewModel @Inject constructor(
    savedStateHandle: SavedStateHandle
) : ViewModel() {
    private val anviId = savedStateHandle.get<String>("anviId")?.takeIf { it.isNotBlank() }
    private var source: JsonObject? = null
    private val _state = MutableStateFlow(
        AnviFormUiState(
            editing = anviId != null,
            dataAnvi = SimpleDateFormat("yyyy-MM-dd", Locale.US).format(Date())
        )
    )
    val state: StateFlow<AnviFormUiState> = _state.asStateFlow()

    init {
        if (anviId != null) load(anviId)
    }

    private fun load(id: String) {
        viewModelScope.launch {
            _state.update { it.copy(loading = true, error = null) }
            try {
                val response = RetrofitClient.getApiService().getAnvi(id)
                val body = response.body()
                if (response.isSuccessful && body != null) {
                    source = body.deepCopy()
                    _state.update {
                        it.copy(
                            loading = false,
                            id = body.text("id"),
                            numero = body.text("numero"),
                            revisao = body.text("revisao"),
                            cliente = body.text("cliente"),
                            projeto = body.text("projeto"),
                            produto = body.text("produto"),
                            volumeMensal = body.text("volumeMensal", "volume_mensal").ifBlank { "1000" },
                            dataAnvi = body.text("dataANVI", "data_anvi"),
                            status = body.text("status").ifBlank { "em-andamento" },
                            margemLucroMarkup = body.nestedText("configuracoes", "margemLucroMarkup").ifBlank { "20" },
                            custoUnitario = body.nestedText("financeiro", "custos", "custo_unitario"),
                            custoTotalMensal = body.nestedText("financeiro", "custos", "custo_total_mensal"),
                            precoSugerido = body.nestedText("financeiro", "receitas", "preco_sugerido"),
                            receitaMensal = body.nestedText("financeiro", "receitas", "receita_mensal"),
                            lucroLiquidoMensal = body.nestedText("financeiro", "receitas", "lucro_liquido_mensal"),
                            investimentoTotal = body.nestedText("financeiro", "investimentos", "investimento_total"),
                            ppap = body.nestedBoolean("checkboxes", "ppap"),
                            viabilidadeTecnica = body.nestedBoolean("checkboxes", "viabilidadeTecnica"),
                            viabilidadeEconomica = body.nestedBoolean("checkboxes", "viabilidadeEconomica"),
                            viabilidadeComercial = body.nestedBoolean("checkboxes", "viabilidadeComercial"),
                            viabilidadeFiscal = body.nestedBoolean("checkboxes", "viabilidadeFiscal")
                        )
                    }
                } else {
                    _state.update { it.copy(loading = false, error = response.friendlyError("Não foi possível carregar a ANVI.")) }
                }
            } catch (exception: Exception) {
                _state.update { it.copy(loading = false, error = exception.friendlyConnectionError("carregar a ANVI")) }
            }
        }
    }

    fun updateNumero(value: String) = change { copy(numero = value) }
    fun updateRevisao(value: String) = change { copy(revisao = value) }
    fun updateCliente(value: String) = change { copy(cliente = value) }
    fun updateProjeto(value: String) = change { copy(projeto = value) }
    fun updateProduto(value: String) = change { copy(produto = value) }
    fun updateVolume(value: String) = change { copy(volumeMensal = value.filter(Char::isDigit)) }
    fun updateData(value: String) = change { copy(dataAnvi = value) }
    fun updateStatus(value: String) = change { copy(status = value) }
    fun updateMarkup(value: String) = change { copy(margemLucroMarkup = value.decimalInput()) }
    fun updateCustoUnitario(value: String) = change { copy(custoUnitario = value.decimalInput()) }
    fun updateCustoTotal(value: String) = change { copy(custoTotalMensal = value.decimalInput()) }
    fun updatePreco(value: String) = change { copy(precoSugerido = value.decimalInput()) }
    fun updateReceita(value: String) = change { copy(receitaMensal = value.decimalInput()) }
    fun updateLucro(value: String) = change { copy(lucroLiquidoMensal = value.decimalInput()) }
    fun updateInvestimento(value: String) = change { copy(investimentoTotal = value.decimalInput()) }
    fun updatePpap(value: Boolean) = change { copy(ppap = value) }
    fun updateTecnica(value: Boolean) = change { copy(viabilidadeTecnica = value) }
    fun updateEconomica(value: Boolean) = change { copy(viabilidadeEconomica = value) }
    fun updateComercial(value: Boolean) = change { copy(viabilidadeComercial = value) }
    fun updateFiscal(value: Boolean) = change { copy(viabilidadeFiscal = value) }

    private fun change(transform: AnviFormUiState.() -> AnviFormUiState) {
        _state.update { it.transform().copy(error = null) }
    }

    fun retry() {
        if (_state.value.editing && source == null && anviId != null) load(anviId) else save()
    }

    fun save() {
        val current = _state.value
        if (current.numero.isBlank() || current.revisao.isBlank()) {
            _state.update { it.copy(error = "Número da ANVI e revisão são obrigatórios.") }
            return
        }
        if ((current.volumeMensal.toIntOrNull() ?: 0) <= 0 || current.dataAnvi.isBlank()) {
            _state.update { it.copy(error = "Informe uma data válida e um volume mensal maior que zero.") }
            return
        }

        viewModelScope.launch {
            _state.update { it.copy(saving = true, error = null) }
            val payload = buildAnviPayload(source, current)
            val id = payload.get("id").asString

            try {
                val response = RetrofitClient.getApiService().saveAnvi(payload)
                val body = response.body()
                if (response.isSuccessful && body?.success == true) {
                    source = payload
                    _state.update { it.copy(saving = false, id = id, saved = true) }
                } else {
                    _state.update {
                        it.copy(
                            saving = false,
                            error = body?.message ?: body?.error ?: response.friendlyError("Não foi possível salvar a ANVI.")
                        )
                    }
                }
            } catch (exception: Exception) {
                _state.update { it.copy(saving = false, error = exception.friendlyConnectionError("salvar a ANVI")) }
            }
        }
    }
}

@Composable
fun AnviFormScreen(
    onNavigateBack: () -> Unit,
    onSaved: () -> Unit,
    viewModel: AnviFormViewModel = hiltViewModel()
) {
    val state by viewModel.state.collectAsState()
    LaunchedEffect(state.saved) {
        if (state.saved) onSaved()
    }

    Column(Modifier.fillMaxSize()) {
        NativeTopBar(if (state.editing) "Editar ANVI" else "Nova ANVI", onNavigateBack)
        when {
            state.loading -> LoadingBlock("Carregando ANVI...")
            else -> Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                state.error?.let { ErrorBlock(it, viewModel::retry) }
                ModuleGuidanceCard(
                    title = "Assistente da ANVI",
                    summary = buildAnviGuidance(state),
                    tips = listOf(
                        "Comece pelos dados gerais e depois confira custos, preço e validações.",
                        "O preço sugerido deve cobrir o custo unitário e a margem pretendida."
                    )
                )
                FormField("Número da ANVI *", state.numero, viewModel::updateNumero, !state.editing)
                FormField("Revisão *", state.revisao, viewModel::updateRevisao)
                FormField("Cliente", state.cliente, viewModel::updateCliente)
                FormField("Projeto", state.projeto, viewModel::updateProjeto)
                FormField("Produto", state.produto, viewModel::updateProduto)
                OutlinedTextField(
                    value = state.volumeMensal,
                    onValueChange = viewModel::updateVolume,
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Volume mensal") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    singleLine = true
                )
                NativeDateField("Data da ANVI", state.dataAnvi, viewModel::updateData)
                FormField("Status", state.status, viewModel::updateStatus)
                Text("Resumo financeiro", style = MaterialTheme.typography.titleMedium)
                DecimalField("Margem desejada (%)", state.margemLucroMarkup, viewModel::updateMarkup, "Percentual usado para avaliar o preço de venda.")
                DecimalField("Custo unitário (R$)", state.custoUnitario, viewModel::updateCustoUnitario, "Custo total para produzir uma unidade.")
                DecimalField("Custo total mensal (R$)", state.custoTotalMensal, viewModel::updateCustoTotal, "Custo previsto para todo o volume mensal.")
                DecimalField("Preço sugerido (R$)", state.precoSugerido, viewModel::updatePreco, "Preço unitário proposto ao cliente.")
                DecimalField("Receita mensal (R$)", state.receitaMensal, viewModel::updateReceita, "Receita estimada para o volume informado.")
                DecimalField("Lucro líquido mensal (R$)", state.lucroLiquidoMensal, viewModel::updateLucro, "Resultado mensal depois dos custos considerados.")
                DecimalField("Investimento total (R$)", state.investimentoTotal, viewModel::updateInvestimento, "Investimentos necessários para iniciar o projeto.")
                Text("Validações", style = MaterialTheme.typography.titleMedium)
                CheckRow("PPAP aplicável/concluído", state.ppap, viewModel::updatePpap)
                CheckRow("Viabilidade técnica", state.viabilidadeTecnica, viewModel::updateTecnica)
                CheckRow("Viabilidade econômica", state.viabilidadeEconomica, viewModel::updateEconomica)
                CheckRow("Viabilidade comercial", state.viabilidadeComercial, viewModel::updateComercial)
                CheckRow("Viabilidade fiscal", state.viabilidadeFiscal, viewModel::updateFiscal)
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(12.dp)) {
                    OutlinedButton(onClick = onNavigateBack, modifier = Modifier.weight(1f), enabled = !state.saving) {
                        Text("Cancelar")
                    }
                    Button(onClick = viewModel::save, modifier = Modifier.weight(1f), enabled = !state.saving) {
                        if (state.saving) CircularProgressIndicator() else Text("Salvar")
                    }
                }
            }
        }
    }
}

@Composable
private fun DecimalField(
    label: String,
    value: String,
    onValueChange: (String) -> Unit,
    help: String
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        modifier = Modifier.fillMaxWidth(),
        label = { Text(label) },
        supportingText = { Text(help) },
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
        singleLine = true
    )
}

internal fun buildAnviGuidance(state: AnviFormUiState): GuidanceSummary {
    val checks = listOf(
        state.numero.isNotBlank() && state.revisao.isNotBlank(),
        state.cliente.isNotBlank(),
        state.projeto.isNotBlank() && state.produto.isNotBlank(),
        (state.volumeMensal.toIntOrNull() ?: 0) > 0,
        state.dataAnvi.isNotBlank(),
        state.custoUnitario.decimalValue() > 0 && state.precoSugerido.decimalValue() > 0,
        state.custoTotalMensal.decimalValue() > 0 && state.receitaMensal.decimalValue() > 0,
        state.ppap || state.viabilidadeTecnica || state.viabilidadeEconomica ||
            state.viabilidadeComercial || state.viabilidadeFiscal
    )
    val pending = buildList {
        if (!checks[0]) add("Informe o número e a revisão da ANVI.")
        if (!checks[1]) add("Identifique o cliente.")
        if (!checks[2]) add("Complete projeto e produto.")
        if (!checks[3]) add("Informe um volume mensal maior que zero.")
        if (!checks[4]) add("Selecione a data da ANVI.")
        if (!checks[5]) add("Complete custo unitário e preço sugerido.")
        if (!checks[6]) add("Complete os totais mensais de custo e receita.")
        if (!checks[7]) add("Revise as validações de viabilidade aplicáveis.")
    }
    val warnings = buildList {
        val cost = state.custoUnitario.decimalValue()
        val price = state.precoSugerido.decimalValue()
        if (cost > 0 && price > 0 && price < cost) add("o preço sugerido está abaixo do custo unitário.")
        val monthlyCost = state.custoTotalMensal.decimalValue()
        val revenue = state.receitaMensal.decimalValue()
        if (monthlyCost > 0 && revenue > 0 && revenue < monthlyCost) add("a receita mensal está abaixo do custo mensal.")
    }
    return GuidanceSummary(checks.count { it }, checks.size, pending, warnings)
}

@Composable
private fun CheckRow(label: String, checked: Boolean, onCheckedChange: (Boolean) -> Unit) {
    Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(8.dp)) {
        Checkbox(checked = checked, onCheckedChange = onCheckedChange)
        Text(label, modifier = Modifier.padding(top = 12.dp))
    }
}

@Composable
private fun FormField(
    label: String,
    value: String,
    onValueChange: (String) -> Unit,
    enabled: Boolean = true
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        modifier = Modifier.fillMaxWidth(),
        label = { Text(label) },
        enabled = enabled,
        singleLine = true
    )
}

private fun JsonObject.text(vararg keys: String): String {
    for (key in keys) {
        val value = get(key)
        if (value != null && value.isJsonPrimitive && !value.isJsonNull) return value.asString
    }
    return ""
}

private fun JsonObject.nestedText(vararg path: String): String {
    var current: com.google.gson.JsonElement = this
    for (key in path) {
        if (!current.isJsonObject || !current.asJsonObject.has(key)) return ""
        current = current.asJsonObject.get(key)
    }
    return if (current.isJsonPrimitive) current.asString else ""
}

private fun JsonObject.nestedBoolean(vararg path: String): Boolean {
    val value = nestedText(*path)
    return value.equals("true", ignoreCase = true) || value == "1"
}

internal fun buildAnviPayload(source: JsonObject?, state: AnviFormUiState): JsonObject {
    val payload = source?.deepCopy() ?: JsonObject()
    payload.addProperty("id", state.id.ifBlank { buildAnviId(state.numero, state.revisao) })
    payload.addProperty("numero", state.numero.trim())
    payload.addProperty("revisao", state.revisao.trim())
    payload.addProperty("cliente", state.cliente.trim())
    payload.addProperty("projeto", state.projeto.trim())
    payload.addProperty("produto", state.produto.trim())
    payload.addProperty("volumeMensal", state.volumeMensal.toIntOrNull() ?: 0)
    payload.addProperty("dataANVI", state.dataAnvi.trim())
    payload.addProperty("status", state.status.trim().ifBlank { "em-andamento" })
    val configuracoes = payload.objectAt("configuracoes")
    configuracoes.addProperty("margemLucroMarkup", state.margemLucroMarkup.decimalValue())

    val financeiro = payload.objectAt("financeiro")
    financeiro.addProperty("schema_version", 1)
    financeiro.addProperty("moeda", "BRL")
    financeiro.addProperty("volume_mensal", state.volumeMensal.toDoubleOrNull() ?: 0.0)
    financeiro.objectAt("custos").apply {
        addProperty("custo_unitario", state.custoUnitario.decimalValue())
        addProperty("custo_total_mensal", state.custoTotalMensal.decimalValue())
    }
    financeiro.objectAt("receitas").apply {
        addProperty("preco_sugerido", state.precoSugerido.decimalValue())
        addProperty("receita_mensal", state.receitaMensal.decimalValue())
        addProperty("lucro_liquido_mensal", state.lucroLiquidoMensal.decimalValue())
    }
    financeiro.objectAt("investimentos").addProperty("investimento_total", state.investimentoTotal.decimalValue())

    payload.objectAt("checkboxes").apply {
        addProperty("ppap", state.ppap)
        addProperty("viabilidadeTecnica", state.viabilidadeTecnica)
        addProperty("viabilidadeEconomica", state.viabilidadeEconomica)
        addProperty("viabilidadeComercial", state.viabilidadeComercial)
        addProperty("viabilidadeFiscal", state.viabilidadeFiscal)
    }
    return payload
}

internal fun buildAnviId(numero: String, revisao: String): String =
    "${numero.trim()}_${revisao.trim()}"
        .replace(Regex("[^A-Za-z0-9_-]"), "-")
        .take(50)

private fun JsonObject.objectAt(key: String): JsonObject {
    val existing = get(key)
    if (existing != null && existing.isJsonObject) return existing.asJsonObject
    return JsonObject().also { add(key, it) }
}

private fun String.decimalInput(): String = filter { it.isDigit() || it == ',' || it == '.' }
private fun String.decimalValue(): Double = replace(',', '.').toDoubleOrNull() ?: 0.0
