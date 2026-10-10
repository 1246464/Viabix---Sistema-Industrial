package com.viabix.app.presentation.screens.anvis

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
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
import com.viabix.app.data.api.AnviSummary
import com.viabix.app.data.api.DeleteAnviRequest
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

data class AnviListUiState(
    val loading: Boolean = false,
    val loadingMore: Boolean = false,
    val deletingId: String? = null,
    val items: List<AnviSummary> = emptyList(),
    val page: Int = 0,
    val totalPages: Int = 1,
    val total: Int = 0,
    val canEdit: Boolean = false,
    val canDelete: Boolean = false,
    val error: String? = null
)

@HiltViewModel
class AnviListViewModel @Inject constructor(
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _state = MutableStateFlow(AnviListUiState())
    val state: StateFlow<AnviListUiState> = _state.asStateFlow()

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
                val response = RetrofitClient.getApiService().getAnvis(page = page, limit = PAGE_SIZE)
                if (response.isSuccessful) {
                    val body = response.body()
                    _state.update { current ->
                        val received = body?.data.orEmpty()
                        val merged = if (append) (current.items + received).distinctBy { it.id } else received
                        current.copy(
                            loading = false,
                            loadingMore = false,
                            items = merged,
                            page = body?.page ?: page,
                            totalPages = body?.totalPages?.coerceAtLeast(1) ?: 1,
                            total = body?.total ?: merged.size,
                            error = null
                        )
                    }
                } else {
                    _state.update { it.copy(loading = false, loadingMore = false, error = response.friendlyError("Não foi possível carregar as ANVIs.")) }
                }
            } catch (exception: Exception) {
                _state.update { it.copy(loading = false, loadingMore = false, error = exception.friendlyConnectionError("carregar as ANVIs")) }
            }
        }
    }

    fun delete(item: AnviSummary) {
        viewModelScope.launch {
            _state.update { it.copy(deletingId = item.id, error = null) }
            try {
                val response = RetrofitClient.getApiService().deleteAnvi(DeleteAnviRequest(item.id))
                val body = response.body()
                if (response.isSuccessful && body?.success == true) {
                    _state.update { current ->
                        current.copy(
                            deletingId = null,
                            items = current.items.filterNot { it.id == item.id },
                            total = (current.total - 1).coerceAtLeast(0)
                        )
                    }
                } else {
                    _state.update { it.copy(deletingId = null, error = body?.message ?: body?.error ?: response.friendlyError("Não foi possível excluir a ANVI.")) }
                }
            } catch (exception: Exception) {
                _state.update { it.copy(deletingId = null, error = exception.friendlyConnectionError("excluir a ANVI")) }
            }
        }
    }

    private companion object {
        const val PAGE_SIZE = 30
    }
}

@Composable
fun AnviListScreen(
    onNavigateBack: () -> Unit,
    onOpenAnvi: (String) -> Unit,
    onCreateAnvi: () -> Unit,
    onEditAnvi: (String) -> Unit,
    viewModel: AnviListViewModel = hiltViewModel()
) {
    val state by viewModel.state.collectAsState()
    var query by remember { mutableStateOf("") }
    var pendingDelete by remember { mutableStateOf<AnviSummary?>(null) }
    val filtered = state.items.filter { item ->
        query.isBlank() || listOf(item.numero, item.cliente, item.projeto, item.produto)
            .any { it.orEmpty().contains(query, ignoreCase = true) }
    }

    Column(modifier = Modifier.fillMaxSize()) {
        NativeTopBar("ANVIs", onNavigateBack, viewModel::refresh)
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
                label = { Text("Buscar por número, cliente ou projeto") },
                leadingIcon = { Icon(Icons.Default.Search, contentDescription = null) },
                singleLine = true
            )
            if (state.canEdit) {
                Button(onClick = onCreateAnvi, modifier = Modifier.fillMaxWidth()) {
                    Icon(Icons.Default.Add, contentDescription = null)
                    Text(" Nova ANVI")
                }
            }
            when {
                state.loading && state.items.isEmpty() -> LoadingBlock("Carregando ANVIs...")
                state.error != null && state.items.isEmpty() -> ErrorBlock(state.error.orEmpty(), viewModel::refresh)
                filtered.isEmpty() -> EmptyBlock(if (query.isBlank()) "Nenhuma ANVI cadastrada." else "Nenhuma ANVI encontrada para esta busca.")
                else -> LazyColumn(
                    modifier = Modifier.weight(1f),
                    verticalArrangement = Arrangement.spacedBy(10.dp)
                ) {
                    if (state.error != null) {
                        item { ErrorBlock(state.error.orEmpty(), viewModel::refresh) }
                    }
                    items(filtered, key = { it.id }) { item ->
                        AnviCard(
                            item = item,
                            deleting = state.deletingId == item.id,
                            canEdit = state.canEdit,
                            canDelete = state.canDelete,
                            onOpen = { onOpenAnvi(item.id) },
                            onEdit = { onEditAnvi(item.id) },
                            onDelete = { pendingDelete = item }
                        )
                    }
                    if (state.page < state.totalPages) {
                        item {
                            if (state.loadingMore) {
                                LoadingBlock("Carregando mais ANVIs...")
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

    pendingDelete?.let { item ->
        AlertDialog(
            onDismissRequest = { pendingDelete = null },
            title = { Text("Excluir ANVI ${item.numero}?") },
            text = { Text("Esta ação remove o registro e não pode ser desfeita.") },
            confirmButton = {
                TextButton(onClick = { pendingDelete = null; viewModel.delete(item) }) {
                    Text("Excluir", color = Color(0xFFC62828))
                }
            },
            dismissButton = { TextButton(onClick = { pendingDelete = null }) { Text("Cancelar") } }
        )
    }
}

@Composable
private fun AnviCard(
    item: AnviSummary,
    deleting: Boolean,
    canEdit: Boolean,
    canDelete: Boolean,
    onOpen: () -> Unit,
    onEdit: () -> Unit,
    onDelete: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(enabled = !deleting, onClick = onOpen),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column(modifier = Modifier.weight(1f)) {
                    Text("ANVI ${item.numero} · Rev. ${item.revisao}", fontWeight = FontWeight.Bold, style = MaterialTheme.typography.titleMedium)
                    Text(item.cliente.orEmpty().ifBlank { "Cliente não informado" }, color = Color.Gray)
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
            if (!item.projeto.isNullOrBlank()) Text("Projeto: ${item.projeto}")
            if (!item.produto.isNullOrBlank()) Text("Produto: ${item.produto}")
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                StatusPill(item.status.orEmpty())
                Text(item.dataAtualizacao ?: item.dataAnvi.orEmpty(), color = Color.Gray, style = MaterialTheme.typography.bodySmall)
            }
        }
    }
}
