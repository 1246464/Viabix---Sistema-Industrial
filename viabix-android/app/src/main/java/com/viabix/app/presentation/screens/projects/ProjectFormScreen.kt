package com.viabix.app.presentation.screens.projects

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
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Divider
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.SavedStateHandle
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.google.gson.JsonNull
import com.google.gson.JsonObject
import com.viabix.app.data.api.ProjectLeader
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
import java.util.TimeZone
import javax.inject.Inject

data class ProjectTaskFormState(
    val planned: String = "",
    val start: String = "",
    val executed: String = "",
    val duration: String = "1",
    val number: String = "",
    val quantidadeEntrada: String = "",
    val quantidadeSaida: String = "",
    val resources: Map<String, String> = emptyMap()
)

data class ProjectTaskDefinition(
    val key: String,
    val label: String,
    val defaultDuration: Int
)

internal val PROJECT_TASK_DEFINITIONS = listOf(
    ProjectTaskDefinition("kom", "KOM", 1),
    ProjectTaskDefinition("ferramental", "Ferramental", 5),
    ProjectTaskDefinition("cadBomFt", "CAD + BOM + FT", 3),
    ProjectTaskDefinition("tryout", "Try-out", 3),
    ProjectTaskDefinition("entrega", "Entrega", 1),
    ProjectTaskDefinition("psw", "PSW", 1),
    ProjectTaskDefinition("handover", "Handover", 1)
)

data class ProjectFormUiState(
    val loading: Boolean = false,
    val saving: Boolean = false,
    val editing: Boolean = false,
    val id: Int? = null,
    val cliente: String = "",
    val projectName: String = "",
    val segmento: String = "",
    val leaderId: String = "",
    val codigo: String = "",
    val anviNumber: String = "",
    val modelo: String = "",
    val processo: String = "",
    val fase: String = "",
    val observacoes: String = "",
    val status: String = "Automático",
    val tasks: Map<String, ProjectTaskFormState> = emptyMap(),
    val apqp: Map<String, Map<String, ApqpAnswerFormState>> = emptyMap(),
    val leaders: List<ProjectLeader> = emptyList(),
    val error: String? = null,
    val saved: Boolean = false
)

@HiltViewModel
class ProjectFormViewModel @Inject constructor(
    savedStateHandle: SavedStateHandle
) : ViewModel() {
    private val projectId = savedStateHandle.get<String>("projectId")?.toIntOrNull()
    private var source: JsonObject? = null
    private val _state = MutableStateFlow(ProjectFormUiState(editing = projectId != null, id = projectId))
    val state: StateFlow<ProjectFormUiState> = _state.asStateFlow()

    init { load() }

    private fun load() {
        viewModelScope.launch {
            _state.update { it.copy(loading = true, error = null) }
            try {
                val api = RetrofitClient.getApiService()
                val leadersResponse = api.getProjectLeaders()
                val leadersBody = leadersResponse.body()
                if (!leadersResponse.isSuccessful || leadersBody?.success != true) {
                    _state.update {
                        it.copy(
                            loading = false,
                            error = leadersBody?.message ?: leadersResponse.friendlyError("Não foi possível carregar os líderes.")
                        )
                    }
                    return@launch
                }

                if (projectId == null) {
                    _state.update {
                        it.copy(
                            loading = false,
                            leaders = leadersBody.data,
                            tasks = defaultProjectTaskStates(),
                            apqp = defaultApqpStates()
                        )
                    }
                    return@launch
                }

                val projectResponse = api.getProject(projectId)
                val projectBody = projectResponse.body()
                val project = projectBody?.data
                if (projectResponse.isSuccessful && projectBody?.success == true && project != null) {
                    source = project.deepCopy()
                    _state.update {
                        it.copy(
                            loading = false,
                            leaders = leadersBody.data,
                            id = projectId,
                            cliente = project.text("cliente"),
                            projectName = project.text("projectName"),
                            segmento = project.text("segmento"),
                            leaderId = project.text("leaderId"),
                            codigo = project.text("codigo"),
                            anviNumber = project.text("anviNumber"),
                            modelo = project.text("modelo"),
                            processo = project.text("processo"),
                            fase = project.text("fase"),
                            observacoes = project.text("observacoes"),
                            status = project.text("manualStatus").takeIf {
                                it == "Em Espera" || it == "Cancelado"
                            } ?: "Automático",
                            tasks = readProjectTaskStates(project),
                            apqp = readProjectApqpStates(project)
                        )
                    }
                } else {
                    _state.update {
                        it.copy(
                            loading = false,
                            leaders = leadersBody.data,
                            error = projectBody?.message ?: projectResponse.friendlyError("Não foi possível carregar o projeto.")
                        )
                    }
                }
            } catch (exception: Exception) {
                _state.update { it.copy(loading = false, error = exception.friendlyConnectionError("carregar o projeto")) }
            }
        }
    }

    fun updateCliente(value: String) = change { copy(cliente = value) }
    fun updateName(value: String) = change { copy(projectName = value) }
    fun updateSegmento(value: String) = change { copy(segmento = value) }
    fun updateLeader(value: String) = change { copy(leaderId = value) }
    fun updateCodigo(value: String) = change { copy(codigo = value) }
    fun updateAnvi(value: String) = change { copy(anviNumber = value) }
    fun updateModelo(value: String) = change { copy(modelo = value) }
    fun updateProcesso(value: String) = change { copy(processo = value) }
    fun updateFase(value: String) = change { copy(fase = value) }
    fun updateObservacoes(value: String) = change { copy(observacoes = value) }
    fun updateStatus(value: String) = change { copy(status = value) }
    fun updateTaskPlanned(key: String, value: String) = updateTask(key) { copy(planned = value.dateInput()) }
    fun updateTaskStart(key: String, value: String) = updateTask(key) { copy(start = value.dateInput()) }
    fun updateTaskExecuted(key: String, value: String) = updateTask(key) { copy(executed = value.dateInput()) }
    fun updateTaskDuration(key: String, value: String) = updateTask(key) {
        copy(duration = value.filter(Char::isDigit).take(3))
    }
    fun updateTaskNumber(key: String, value: String) = updateTask(key) { copy(number = value) }
    fun updateTaskInputQuantity(key: String, value: String) = updateTask(key) {
        copy(quantidadeEntrada = value.filter(Char::isDigit))
    }
    fun updateTaskOutputQuantity(key: String, value: String) = updateTask(key) {
        copy(quantidadeSaida = value.filter(Char::isDigit))
    }
    fun updateTaskResource(taskKey: String, resourceKey: String, value: String) = updateTask(taskKey) {
        copy(resources = resources.toMutableMap().apply { this[resourceKey] = value })
    }
    fun updateApqpAnswer(phaseKey: String, questionId: String, value: String) = updateApqp(phaseKey, questionId) {
        copy(answer = if (answer == value) "" else value)
    }
    fun updateApqpObservations(phaseKey: String, questionId: String, value: String) =
        updateApqp(phaseKey, questionId) { copy(observations = value) }

    private fun updateTask(key: String, transform: ProjectTaskFormState.() -> ProjectTaskFormState) {
        change {
            copy(tasks = tasks.toMutableMap().apply {
                this[key] = (this[key] ?: defaultProjectTaskState(key)).transform()
            })
        }
    }

    private fun updateApqp(
        phaseKey: String,
        questionId: String,
        transform: ApqpAnswerFormState.() -> ApqpAnswerFormState
    ) {
        change {
            val phases = apqp.toMutableMap()
            val answers = phases[phaseKey].orEmpty().toMutableMap()
            answers[questionId] = (answers[questionId] ?: ApqpAnswerFormState()).transform()
            phases[phaseKey] = answers
            copy(apqp = phases)
        }
    }

    private fun change(transform: ProjectFormUiState.() -> ProjectFormUiState) {
        _state.update { it.transform().copy(error = null) }
    }

    fun retry() {
        if (_state.value.editing && source == null) load() else save()
    }

    fun save() {
        val current = _state.value
        if (current.cliente.isBlank() || current.projectName.isBlank() || current.leaderId.isBlank()) {
            _state.update { it.copy(error = "Cliente, projeto e líder são obrigatórios.") }
            return
        }
        projectBlockingError(current)?.let { message ->
            _state.update { it.copy(error = message) }
            return
        }

        viewModelScope.launch {
            _state.update { it.copy(saving = true, error = null) }
            val payload = buildProjectPayload(source, current)

            try {
                val response = RetrofitClient.getApiService().saveProject(payload.toString())
                val body = response.body()
                if (response.isSuccessful && body?.success == true) {
                    source = payload
                    _state.update { it.copy(saving = false, saved = true, id = it.id ?: body.insertId) }
                } else {
                    _state.update {
                        it.copy(
                            saving = false,
                            error = body?.message ?: body?.error ?: response.friendlyError("Não foi possível salvar o projeto.")
                        )
                    }
                }
            } catch (exception: Exception) {
                _state.update { it.copy(saving = false, error = exception.friendlyConnectionError("salvar o projeto")) }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProjectFormScreen(
    onNavigateBack: () -> Unit,
    onSaved: () -> Unit,
    viewModel: ProjectFormViewModel = hiltViewModel()
) {
    val state by viewModel.state.collectAsState()
    var leaderMenuOpen by remember { mutableStateOf(false) }
    var statusMenuOpen by remember { mutableStateOf(false) }
    var expandedTaskKey by remember { mutableStateOf<String?>(null) }
    LaunchedEffect(state.saved) {
        if (state.saved) onSaved()
    }

    Column(Modifier.fillMaxSize()) {
        NativeTopBar(if (state.editing) "Editar projeto" else "Novo projeto", onNavigateBack)
        when {
            state.loading -> LoadingBlock("Carregando projeto...")
            else -> Column(
                modifier = Modifier
                    .fillMaxSize()
                    .verticalScroll(rememberScrollState())
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                state.error?.let { ErrorBlock(it, viewModel::retry) }
                ModuleGuidanceCard(
                    title = "Assistente do projeto",
                    summary = buildProjectGuidance(state),
                    tips = listOf(
                        "Planeje as datas antes de iniciar uma etapa.",
                        "Abra cada etapa para preencher recursos e APQP sem perder o histórico existente."
                    )
                )
                ProjectField("Cliente *", state.cliente, viewModel::updateCliente)
                ProjectField("Nome do projeto *", state.projectName, viewModel::updateName)
                ProjectField("Segmento", state.segmento, viewModel::updateSegmento)
                ExposedDropdownMenuBox(
                    expanded = leaderMenuOpen,
                    onExpandedChange = { leaderMenuOpen = !leaderMenuOpen }
                ) {
                    OutlinedTextField(
                        value = state.leaders.firstOrNull { it.id.toString() == state.leaderId }?.name.orEmpty(),
                        onValueChange = {},
                        modifier = Modifier.fillMaxWidth().menuAnchor(),
                        readOnly = true,
                        label = { Text("Líder *") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(leaderMenuOpen) }
                    )
                    ExposedDropdownMenu(
                        expanded = leaderMenuOpen,
                        onDismissRequest = { leaderMenuOpen = false }
                    ) {
                        state.leaders.forEach { leader ->
                            DropdownMenuItem(
                                text = { Text(leader.name) },
                                onClick = {
                                    viewModel.updateLeader(leader.id.toString())
                                    leaderMenuOpen = false
                                }
                            )
                        }
                    }
                }
                ProjectField("Código", state.codigo, viewModel::updateCodigo)
                ProjectField("Número da ANVI vinculada", state.anviNumber, viewModel::updateAnvi)
                ProjectField("Modelo", state.modelo, viewModel::updateModelo)
                ProjectField("Processo", state.processo, viewModel::updateProcesso)
                ProjectField("Fase", state.fase, viewModel::updateFase)
                ExposedDropdownMenuBox(
                    expanded = statusMenuOpen,
                    onExpandedChange = { statusMenuOpen = !statusMenuOpen }
                ) {
                    OutlinedTextField(
                        value = state.status,
                        onValueChange = {},
                        modifier = Modifier.fillMaxWidth().menuAnchor(),
                        readOnly = true,
                        label = { Text("Status do projeto") },
                        supportingText = { Text("No modo automático, as datas das etapas definem o status.") },
                        trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(statusMenuOpen) }
                    )
                    ExposedDropdownMenu(
                        expanded = statusMenuOpen,
                        onDismissRequest = { statusMenuOpen = false }
                    ) {
                        listOf("Automático", "Em Espera", "Cancelado").forEach { option ->
                            DropdownMenuItem(
                                text = { Text(option) },
                                onClick = {
                                    viewModel.updateStatus(option)
                                    statusMenuOpen = false
                                }
                            )
                        }
                    }
                }
                OutlinedTextField(
                    value = state.observacoes,
                    onValueChange = viewModel::updateObservacoes,
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Observações") },
                    minLines = 3
                )
                Text("Etapas do projeto", style = MaterialTheme.typography.titleMedium)
                Text(
                    "Abra uma etapa para editar datas, recursos e o checklist APQP.",
                    style = MaterialTheme.typography.bodySmall
                )
                PROJECT_TASK_DEFINITIONS.forEach { definition ->
                    val task = state.tasks[definition.key] ?: defaultProjectTaskState(definition.key)
                    ProjectTaskCard(
                        definition = definition,
                        task = task,
                        apqpAnswers = state.apqp[definition.key].orEmpty(),
                        expanded = expandedTaskKey == definition.key,
                        onToggle = {
                            expandedTaskKey = if (expandedTaskKey == definition.key) null else definition.key
                        },
                        onPlannedChange = { viewModel.updateTaskPlanned(definition.key, it) },
                        onStartChange = { viewModel.updateTaskStart(definition.key, it) },
                        onExecutedChange = { viewModel.updateTaskExecuted(definition.key, it) },
                        onDurationChange = { viewModel.updateTaskDuration(definition.key, it) },
                        onNumberChange = { viewModel.updateTaskNumber(definition.key, it) },
                        onInputQuantityChange = { viewModel.updateTaskInputQuantity(definition.key, it) },
                        onOutputQuantityChange = { viewModel.updateTaskOutputQuantity(definition.key, it) },
                        onResourceChange = { resourceKey, value ->
                            viewModel.updateTaskResource(definition.key, resourceKey, value)
                        },
                        onApqpAnswerChange = { questionId, value ->
                            viewModel.updateApqpAnswer(definition.key, questionId, value)
                        },
                        onApqpObservationsChange = { questionId, value ->
                            viewModel.updateApqpObservations(definition.key, questionId, value)
                        }
                    )
                }
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

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun ProjectTaskCard(
    definition: ProjectTaskDefinition,
    task: ProjectTaskFormState,
    apqpAnswers: Map<String, ApqpAnswerFormState>,
    expanded: Boolean,
    onToggle: () -> Unit,
    onPlannedChange: (String) -> Unit,
    onStartChange: (String) -> Unit,
    onExecutedChange: (String) -> Unit,
    onDurationChange: (String) -> Unit,
    onNumberChange: (String) -> Unit,
    onInputQuantityChange: (String) -> Unit,
    onOutputQuantityChange: (String) -> Unit,
    onResourceChange: (String, String) -> Unit,
    onApqpAnswerChange: (String, String) -> Unit,
    onApqpObservationsChange: (String, String) -> Unit
) {
    val questions = APQP_QUESTIONS[definition.key].orEmpty()
    val answered = questions.count { apqpAnswers[it.id]?.answer?.isNotBlank() == true }
    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant)
    ) {
        Column(
            modifier = Modifier.padding(12.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column(Modifier.weight(1f)) {
                    Text(definition.label, style = MaterialTheme.typography.titleSmall)
                    Text("APQP: $answered/${questions.size}", style = MaterialTheme.typography.bodySmall)
                }
                TextButton(onClick = onToggle) { Text(if (expanded) "Fechar" else "Editar") }
            }
            if (expanded) {
                NativeDateField("Planejado", task.planned, onPlannedChange)
                NativeDateField("Início", task.start, onStartChange)
                NativeDateField("Conclusão", task.executed, onExecutedChange)
                OutlinedTextField(
                    value = task.duration,
                    onValueChange = onDurationChange,
                    modifier = Modifier.fillMaxWidth(),
                    label = { Text("Duração (dias)") },
                    keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
                    singleLine = true
                )

                when (definition.key) {
                    "ferramental" -> {
                        Text("Recursos do ferramental", style = MaterialTheme.typography.titleSmall)
                        FERRAMENTAL_RESOURCES.forEach { resource ->
                            NativeDateField(
                                resource.label,
                                task.resources[resource.key].orEmpty(),
                                { onResourceChange(resource.key, it) }
                            )
                        }
                    }
                    "tryout" -> {
                        Text("Dados e recursos do Try-out", style = MaterialTheme.typography.titleSmall)
                        ProjectField("Número do Try-out", task.number, onNumberChange)
                        NumericProjectField("Quantidade de entrada", task.quantidadeEntrada, onInputQuantityChange)
                        NumericProjectField("Quantidade de saída", task.quantidadeSaida, onOutputQuantityChange)
                        TRYOUT_RESOURCES.forEach { resource ->
                            ResourceDropdownField(
                                definition = resource,
                                value = task.resources[resource.key].orEmpty(),
                                onValueChange = { onResourceChange(resource.key, it) }
                            )
                        }
                    }
                    "entrega" -> ProjectField("Número da entrega", task.number, onNumberChange)
                }

                Divider()
                Text("Checklist APQP ($answered/${questions.size})", style = MaterialTheme.typography.titleSmall)
                questions.forEachIndexed { index, question ->
                    val answer = apqpAnswers[question.id] ?: ApqpAnswerFormState()
                    Text("${index + 1}. ${question.question}", style = MaterialTheme.typography.bodyMedium)
                    Text(question.category, style = MaterialTheme.typography.labelSmall)
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        listOf("sim" to "Sim", "nao" to "Não", "na" to "N/A").forEach { option ->
                            FilterChip(
                                selected = answer.answer == option.first,
                                onClick = { onApqpAnswerChange(question.id, option.first) },
                                label = { Text(option.second) }
                            )
                        }
                    }
                    OutlinedTextField(
                        value = answer.observations,
                        onValueChange = { onApqpObservationsChange(question.id, it) },
                        modifier = Modifier.fillMaxWidth(),
                        label = { Text("Observações") },
                        minLines = 2
                    )
                    if (index < questions.lastIndex) Divider()
                }
            }
        }
    }
}

@Composable
private fun NumericProjectField(label: String, value: String, onValueChange: (String) -> Unit) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        modifier = Modifier.fillMaxWidth(),
        label = { Text(label) },
        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Number),
        singleLine = true
    )
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
private fun ResourceDropdownField(
    definition: ProjectResourceDefinition,
    value: String,
    onValueChange: (String) -> Unit
) {
    var expanded by remember(definition.key) { mutableStateOf(false) }
    ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = !expanded }) {
        OutlinedTextField(
            value = value,
            onValueChange = {},
            modifier = Modifier.fillMaxWidth().menuAnchor(),
            readOnly = true,
            label = { Text(definition.label) },
            trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded) }
        )
        ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
            DropdownMenuItem(
                text = { Text("Não definido") },
                onClick = { onValueChange(""); expanded = false }
            )
            definition.options.forEach { option ->
                DropdownMenuItem(
                    text = { Text(option) },
                    onClick = { onValueChange(option); expanded = false }
                )
            }
        }
    }
}

@Composable
private fun ProjectField(label: String, value: String, onValueChange: (String) -> Unit) {
    OutlinedTextField(
        value = value,
        onValueChange = onValueChange,
        modifier = Modifier.fillMaxWidth(),
        label = { Text(label) },
        singleLine = true
    )
}

private fun JsonObject.text(key: String): String {
    val value = get(key)
    return if (value != null && value.isJsonPrimitive && !value.isJsonNull) value.asString else ""
}

private fun defaultProjectTaskState(key: String): ProjectTaskFormState {
    val duration = PROJECT_TASK_DEFINITIONS.firstOrNull { it.key == key }?.defaultDuration ?: 1
    return ProjectTaskFormState(duration = duration.toString())
}

private fun defaultProjectTaskStates(): Map<String, ProjectTaskFormState> =
    PROJECT_TASK_DEFINITIONS.associate { it.key to defaultProjectTaskState(it.key) }

private fun readProjectTaskStates(project: JsonObject): Map<String, ProjectTaskFormState> {
    val tasks = project.get("tasks")?.takeIf { it.isJsonObject }?.asJsonObject
    return PROJECT_TASK_DEFINITIONS.associate { definition ->
        val task = tasks?.get(definition.key)?.takeIf { it.isJsonObject }?.asJsonObject
        definition.key to ProjectTaskFormState(
            planned = task?.text("planned").orEmpty(),
            start = task?.text("start").orEmpty(),
            executed = task?.text("executed").orEmpty(),
            duration = task?.text("duration").orEmpty().ifBlank { definition.defaultDuration.toString() },
            number = task?.text("number").orEmpty(),
            quantidadeEntrada = task?.text("quantidadeEntrada").orEmpty(),
            quantidadeSaida = task?.text("quantidadeSaida").orEmpty(),
            resources = task?.get("resources")?.takeIf { it.isJsonObject }?.asJsonObject
                ?.entrySet()?.associate { it.key to it.value.primitiveText() }.orEmpty()
        )
    }
}

private fun defaultApqpStates(): Map<String, Map<String, ApqpAnswerFormState>> =
    APQP_QUESTIONS.mapValues { (_, questions) ->
        questions.associate { it.id to ApqpAnswerFormState() }
    }

private fun readProjectApqpStates(project: JsonObject): Map<String, Map<String, ApqpAnswerFormState>> {
    val apqp = project.get("apqp")?.takeIf { it.isJsonObject }?.asJsonObject
    return APQP_QUESTIONS.mapValues { (phaseKey, questions) ->
        val answers = apqp?.get(phaseKey)?.takeIf { it.isJsonObject }?.asJsonObject
            ?.get("answers")?.takeIf { it.isJsonObject }?.asJsonObject
        questions.associate { question ->
            val saved = answers?.get(question.id)?.takeIf { it.isJsonObject }?.asJsonObject
            question.id to ApqpAnswerFormState(
                answer = saved?.text("answer").orEmpty(),
                observations = saved?.text("observations").orEmpty()
            )
        }
    }
}

private fun com.google.gson.JsonElement.primitiveText(): String =
    if (isJsonPrimitive && !isJsonNull) asString else ""

internal fun buildProjectPayload(source: JsonObject?, state: ProjectFormUiState): JsonObject {
    val payload = source?.deepCopy() ?: JsonObject()
    val previousAnviNumber = payload.text("anviNumber")
    state.id?.let { payload.addProperty("id", it) }
    payload.addProperty("cliente", state.cliente.trim())
    payload.addProperty("projectName", state.projectName.trim())
    payload.addProperty("segmento", state.segmento.trim())
    payload.addProperty("leaderId", state.leaderId)
    payload.addProperty(
        "projectLeader",
        state.leaders.firstOrNull { it.id.toString() == state.leaderId }?.name
            ?: payload.text("projectLeader")
    )
    payload.addProperty("codigo", state.codigo.trim())
    payload.addProperty("anviNumber", state.anviNumber.trim())
    if (previousAnviNumber != state.anviNumber.trim()) {
        payload.remove("anviId")
        payload.remove("anviRevision")
        payload.add("sourceContext", JsonObject().apply {
            addProperty("source", "manual")
            addProperty("anviNumber", state.anviNumber.trim())
        })
    }
    payload.addProperty("modelo", state.modelo.trim())
    payload.addProperty("processo", state.processo.trim())
    payload.addProperty("fase", state.fase.trim())
    payload.addProperty("observacoes", state.observacoes.trim())

    if (state.tasks.isNotEmpty()) {
        val tasks = payload.get("tasks")?.takeIf { it.isJsonObject }?.asJsonObject?.deepCopy() ?: JsonObject()
        PROJECT_TASK_DEFINITIONS.forEach { definition ->
            val taskState = state.tasks[definition.key] ?: defaultProjectTaskState(definition.key)
            val task = tasks.get(definition.key)?.takeIf { it.isJsonObject }?.asJsonObject?.deepCopy() ?: JsonObject()
            task.addDate("planned", taskState.planned)
            task.addDate("start", taskState.start)
            task.addDate("executed", taskState.executed)
            task.addProperty("duration", taskState.duration.toIntOrNull()?.takeIf { it > 0 } ?: definition.defaultDuration)
            if (definition.key == "tryout" || definition.key == "entrega") {
                task.addNullableText("number", taskState.number)
            }
            if (definition.key == "tryout") {
                task.addNullableNumber("quantidadeEntrada", taskState.quantidadeEntrada)
                task.addNullableNumber("quantidadeSaida", taskState.quantidadeSaida)
            }
            if (taskState.resources.isNotEmpty()) {
                val resources = task.get("resources")?.takeIf { it.isJsonObject }?.asJsonObject?.deepCopy() ?: JsonObject()
                taskState.resources.forEach { (key, value) -> resources.addProperty(key, value) }
                task.add("resources", resources)
            }
            tasks.add(definition.key, task)
        }
        payload.add("tasks", tasks)
    }

    mergeProjectApqp(payload, state.apqp)

    when (state.status) {
        "Em Espera", "Cancelado" -> {
            payload.addProperty("manualStatus", state.status)
            payload.addProperty("status", state.status)
        }
        else -> {
            payload.add("manualStatus", JsonNull.INSTANCE)
            val taskStates = if (state.tasks.isNotEmpty()) state.tasks else readProjectTaskStates(payload)
            payload.addProperty("status", calculateProjectStatus(taskStates))
        }
    }
    return payload
}

private fun JsonObject.addDate(key: String, value: String) {
    if (value.isBlank()) add(key, JsonNull.INSTANCE) else addProperty(key, value.trim())
}

private fun JsonObject.addNullableText(key: String, value: String) {
    if (value.isBlank()) add(key, JsonNull.INSTANCE) else addProperty(key, value.trim())
}

private fun JsonObject.addNullableNumber(key: String, value: String) {
    val number = value.toIntOrNull()
    if (number == null) add(key, JsonNull.INSTANCE) else addProperty(key, number)
}

private fun mergeProjectApqp(
    payload: JsonObject,
    form: Map<String, Map<String, ApqpAnswerFormState>>
) {
    if (form.isEmpty()) return
    val apqp = payload.get("apqp")?.takeIf { it.isJsonObject }?.asJsonObject?.deepCopy() ?: JsonObject()
    val today = SimpleDateFormat("yyyy-MM-dd", Locale.US).format(Date())
    val timestamp = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSS'Z'", Locale.US).apply {
        timeZone = TimeZone.getTimeZone("UTC")
    }.format(Date())

    APQP_QUESTIONS.forEach { (phaseKey, questions) ->
        val formAnswers = form[phaseKey].orEmpty()
        val existingPhase = apqp.get(phaseKey)?.takeIf { it.isJsonObject }?.asJsonObject
        if (existingPhase == null && formAnswers.values.none { it.answer.isNotBlank() }) return@forEach

        val phase = existingPhase?.deepCopy() ?: JsonObject()
        val answers = phase.get("answers")?.takeIf { it.isJsonObject }?.asJsonObject?.deepCopy() ?: JsonObject()
        questions.forEach { question ->
            val answerState = formAnswers[question.id] ?: ApqpAnswerFormState()
            if (answerState.answer.isBlank()) {
                answers.remove(question.id)
            } else {
                val answer = answers.get(question.id)?.takeIf { it.isJsonObject }?.asJsonObject?.deepCopy() ?: JsonObject()
                answer.addProperty("question", question.question)
                answer.addProperty("category", question.category)
                answer.addProperty("answer", answerState.answer)
                answer.addProperty("observations", answerState.observations.trim())
                answer.addProperty("date", today)
                answers.add(question.id, answer)
            }
        }
        phase.add("answers", answers)
        phase.addProperty("lastUpdated", timestamp)
        phase.addProperty("completed", questions.all { answers.has(it.id) })
        apqp.add(phaseKey, phase)
    }
    payload.add("apqp", apqp)
}

internal fun calculateProjectStatus(
    tasks: Map<String, ProjectTaskFormState>,
    today: String = SimpleDateFormat("yyyy-MM-dd", Locale.US).format(Date())
): String {
    if (tasks.isEmpty()) return "Pendente"
    val statuses = tasks.values.map { calculateTaskStatus(it, today) }
    return when {
        statuses.any { it == "Atrasado" } -> "Atrasado"
        statuses.all { it == "Concluído" } -> "Concluído"
        statuses.any { it == "Em Andamento" } -> "Em Andamento"
        statuses.any { it == "No Prazo" } -> "No Prazo"
        else -> "Pendente"
    }
}

private fun calculateTaskStatus(task: ProjectTaskFormState, today: String): String {
    if (task.executed.isNotBlank()) return "Concluído"
    if (task.start.isNotBlank()) {
        if (!task.start.isIsoDate() || task.start > today) return "Pendente"
        if (task.planned.isIsoDate() && today > task.planned) return "Atrasado"
        return "Em Andamento"
    }
    if (task.planned.isIsoDate()) {
        return if (today > task.planned) "Atrasado" else "No Prazo"
    }
    return "Pendente"
}

private fun String.isIsoDate(): Boolean {
    if (!matches(Regex("\\d{4}-\\d{2}-\\d{2}"))) return false
    return runCatching {
        SimpleDateFormat("yyyy-MM-dd", Locale.US).apply { isLenient = false }.parse(this)
    }.getOrNull() != null
}

private fun String.dateInput(): String = filter { it.isDigit() || it == '-' }.take(10)

internal fun buildProjectGuidance(state: ProjectFormUiState): GuidanceSummary {
    val generalComplete = state.cliente.isNotBlank() && state.projectName.isNotBlank() && state.leaderId.isNotBlank()
    val plannedPhases = PROJECT_TASK_DEFINITIONS.count { state.tasks[it.key]?.planned?.isNotBlank() == true }
    val completeApqpPhases = PROJECT_TASK_DEFINITIONS.count { definition ->
        val questions = APQP_QUESTIONS[definition.key].orEmpty()
        questions.isNotEmpty() && questions.all { state.apqp[definition.key]?.get(it.id)?.answer?.isNotBlank() == true }
    }
    val pending = buildList {
        if (!generalComplete) add("Complete cliente, nome do projeto e líder.")
        PROJECT_TASK_DEFINITIONS.filter { state.tasks[it.key]?.planned.isNullOrBlank() }.forEach {
            add("Defina a data planejada de ${it.label}.")
        }
        PROJECT_TASK_DEFINITIONS.forEach { definition ->
            val questions = APQP_QUESTIONS[definition.key].orEmpty()
            val answered = questions.count { state.apqp[definition.key]?.get(it.id)?.answer?.isNotBlank() == true }
            if (questions.isNotEmpty() && answered < questions.size) {
                add("APQP de ${definition.label}: $answered/${questions.size} respostas.")
            }
        }
    }
    val warnings = buildList {
        state.tasks.forEach { (key, task) ->
            val label = PROJECT_TASK_DEFINITIONS.firstOrNull { it.key == key }?.label ?: key
            if (task.start.isNotBlank() && task.executed.isNotBlank() && task.executed < task.start) {
                add("a conclusão de $label está anterior ao início.")
            }
        }
        val tryout = state.tasks["tryout"]
        val input = tryout?.quantidadeEntrada?.toIntOrNull()
        val output = tryout?.quantidadeSaida?.toIntOrNull()
        if (input != null && output != null && output > input) {
            add("a quantidade de saída do Try-out é maior que a entrada.")
        }
    }
    val total = 1 + PROJECT_TASK_DEFINITIONS.size * 2
    val completed = (if (generalComplete) 1 else 0) + plannedPhases + completeApqpPhases
    return GuidanceSummary(completed, total, pending, warnings)
}

private fun projectBlockingError(state: ProjectFormUiState): String? {
    state.tasks.forEach { (key, task) ->
        if (task.start.isNotBlank() && task.executed.isNotBlank() && task.executed < task.start) {
            val label = PROJECT_TASK_DEFINITIONS.firstOrNull { it.key == key }?.label ?: key
            return "A conclusão de $label não pode ser anterior à data de início."
        }
    }
    return null
}
