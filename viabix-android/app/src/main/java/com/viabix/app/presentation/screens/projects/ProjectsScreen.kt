package com.viabix.app.presentation.screens.projects

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.clickable
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Edit
import androidx.compose.material.icons.filled.Search
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Button
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.viabix.app.data.api.ProjectSummary
import com.viabix.app.data.api.RetrofitClient
import com.viabix.app.data.api.friendlyError
import com.viabix.app.data.api.friendlyConnectionError
import com.viabix.app.presentation.components.EmptyBlock
import com.viabix.app.presentation.components.ErrorBlock
import com.viabix.app.presentation.components.LoadingBlock
import com.viabix.app.presentation.components.NativeTopBar
import com.viabix.app.presentation.components.StatusPill
import com.viabix.app.utils.TokenManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

data class ProjectsUiState(
    val loading: Boolean = false,
    val loadingMore: Boolean = false,
    val deletingId: Int? = null,
    val items: List<ProjectSummary> = emptyList(),
    val page: Int = 0,
    val totalPages: Int = 1,
    val total: Int = 0,
    val canEdit: Boolean = false,
    val canDelete: Boolean = false,
    val error: String? = null
)

@HiltViewModel
class ProjectsViewModel @Inject constructor(
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(ProjectsUiState())
    val state: StateFlow<ProjectsUiState> = _state.asStateFlow()

    init {
        loadCapabilities()
        refresh()
    }

    private fun loadCapabilities() {
        viewModelScope.launch {
            val level = tokenManager.getUserLevel().orEmpty().lowercase()
            _state.update {
                it.copy(
                    canEdit = level !in setOf("visitante", "visualizador"),
                    canDelete = level in setOf("admin", "owner", "suporte")
                )
            }
        }
    }

    fun refresh() = loadPage(page = 1, append = false)

    fun loadNextPage() {
        val current = _state.value
        if (!current.loading && !current.loadingMore && current.page < current.totalPages) {
            loadPage(page = current.page + 1, append = true)
        }
    }

    private fun loadPage(page: Int, append: Boolean) {
        viewModelScope.launch {
            _state.update {
                if (append) it.copy(loadingMore = true, error = null)
                else it.copy(loading = true, error = null)
            }
            try {
                val response = RetrofitClient.getApiService().getProjects(page = page, limit = PAGE_SIZE)
                val body = response.body()
                if (response.isSuccessful && body?.success == true) {
                    _state.update { current ->
                        val merged = if (append) (current.items + body.data).distinctBy { it.id } else body.data
                        current.copy(
                            loading = false,
                            loadingMore = false,
                            items = merged,
                            page = body.page,
                            totalPages = body.totalPages.coerceAtLeast(1),
                            total = body.total,
                            error = null
                        )
                    }
                } else {
                    _state.update { it.copy(loading = false, loadingMore = false, error = body?.message ?: response.friendlyError("Não foi possível carregar os projetos.")) }
                }
            } catch (exception: Exception) {
                _state.update { it.copy(loading = false, loadingMore = false, error = exception.friendlyConnectionError("carregar os projetos")) }
            }
        }
    }

    fun delete(project: ProjectSummary) {
        viewModelScope.launch {
            _state.update { it.copy(deletingId = project.id, error = null) }
            try {
                val response = RetrofitClient.getApiService().deleteProject(projectId = project.id)
                val body = response.body()
                if (response.isSuccessful && body?.success == true) {
                    _state.update { current ->
                        current.copy(
                            deletingId = null,
                            items = current.items.filterNot { it.id == project.id },
                            total = (current.total - 1).coerceAtLeast(0)
                        )
                    }
                } else {
                    _state.update { it.copy(deletingId = null, error = body?.message ?: response.friendlyError("Não foi possível excluir o projeto.")) }
                }
            } catch (exception: Exception) {
                _state.update { it.copy(deletingId = null, error = exception.friendlyConnectionError("excluir o projeto")) }
            }
        }
    }

    private companion object {
        const val PAGE_SIZE = 30
    }
}

@Composable
fun ProjectsScreen(
    onNavigateBack: () -> Unit,
    onCreateProject: () -> Unit,
    onEditProject: (Int) -> Unit,
    viewModel: ProjectsViewModel = hiltViewModel()
) {
    val state by viewModel.state.collectAsState()
    var query by remember { mutableStateOf("") }
    var pendingDelete by remember { mutableStateOf<ProjectSummary?>(null) }
    val filtered = state.items.filter { project ->
        query.isBlank() || listOf(project.projectName, project.cliente, project.codigo, project.segmento, project.projectLeader)
            .any { it.orEmpty().contains(query, ignoreCase = true) }
    }

    Column(modifier = Modifier.fillMaxSize()) {
        NativeTopBar("Projetos", onNavigateBack, viewModel::refresh)
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            OutlinedTextField(
                value = query,
                onValueChange = { query = it },
                modifier = Modifier.fillMaxWidth(),
                label = { Text("Buscar projeto, cliente ou líder") },
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
                singleLine = true
            )
            if (state.canEdit) {
                Button(onClick = onCreateProject, modifier = Modifier.fillMaxWidth()) {
                    Icon(Icons.Default.Add, contentDescription = null)
                    Text(" Novo projeto")
                }
            }
            when {
                state.loading && state.items.isEmpty() -> LoadingBlock("Carregando projetos...")
                state.error != null && state.items.isEmpty() -> ErrorBlock(state.error.orEmpty(), viewModel::refresh)
                filtered.isEmpty() -> EmptyBlock(if (query.isBlank()) "Nenhum projeto cadastrado." else "Nenhum projeto encontrado para esta busca.")
                else -> LazyColumn(
                    modifier = Modifier.weight(1f),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    if (state.error != null) {
                        item { ErrorBlock(state.error.orEmpty(), viewModel::refresh) }
                    }
                    items(filtered, key = { it.id }) { project ->
                        ProjectCard(
                            project = project,
                            deleting = state.deletingId == project.id,
                            canEdit = state.canEdit,
                            canDelete = state.canDelete,
                            onEdit = { onEditProject(project.id) },
                            onDelete = { pendingDelete = project }
                        )
                    }
                    if (state.page < state.totalPages) {
                        item {
                            if (state.loadingMore) {
                                LoadingBlock("Carregando mais projetos...")
                            } else {
                                TextButton(
                                    onClick = viewModel::loadNextPage,
                                    modifier = Modifier.fillMaxWidth()
                                ) { Text("Carregar mais (${state.items.size} de ${state.total})") }
                            }
                        }
                    }
                }
            }
        }
    }

    pendingDelete?.let { project ->
        AlertDialog(
            onDismissRequest = { pendingDelete = null },
            title = { Text("Excluir ${project.projectName ?: "projeto"}?") },
            text = { Text("O projeto e seus dados operacionais serão removidos.") },
            confirmButton = {
                TextButton(onClick = { pendingDelete = null; viewModel.delete(project) }) {
                    Text("Excluir", color = Color(0xFFC62828))
                }
            },
            dismissButton = { TextButton(onClick = { pendingDelete = null }) { Text("Cancelar") } }
        )
    }
}

@Composable
private fun ProjectCard(
    project: ProjectSummary,
    deleting: Boolean,
    canEdit: Boolean,
    canDelete: Boolean,
    onEdit: () -> Unit,
    onDelete: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth().clickable(enabled = !deleting && canEdit, onClick = onEdit),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(9.dp)) {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(project.projectName.orEmpty().ifBlank { "Projeto #${project.id}" }, fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                    Text(project.cliente.orEmpty().ifBlank { "Cliente não informado" }, color = Color.Gray)
                }
                if (canEdit || canDelete) {
                    Row {
                        if (canEdit) {
                            IconButton(onClick = onEdit, enabled = !deleting) {
                                Icon(Icons.Default.Edit, contentDescription = "Editar")
                            }
                        }
                        if (canDelete) {
                            IconButton(onClick = onDelete, enabled = !deleting) {
                                Icon(Icons.Default.Delete, contentDescription = "Excluir", tint = Color(0xFFC62828))
                            }
                        }
                    }
                }
            }
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                StatusPill(project.status.orEmpty())
                Text(project.segmento.orEmpty(), style = MaterialTheme.typography.bodySmall, color = Color.Gray)
            }
            if (!project.projectLeader.isNullOrBlank()) Text("Líder: ${project.projectLeader}")
            if (!project.anviNumber.isNullOrBlank()) Text("ANVI vinculada: ${project.anviNumber}")
            Text("Progresso das etapas: ${project.progressPercent}%", style = MaterialTheme.typography.bodySmall)
            LinearProgressIndicator(
                progress = project.progressPercent / 100f,
                modifier = Modifier.fillMaxWidth(),
                color = Color(0xFF1D7A4A),
                trackColor = Color(0xFFE0E0E0)
            )
        }
    }
}
