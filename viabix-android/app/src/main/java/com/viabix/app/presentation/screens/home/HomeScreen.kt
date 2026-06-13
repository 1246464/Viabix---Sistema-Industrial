package com.viabix.app.presentation.screens.home

import android.util.Log
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ExitToApp
import androidx.compose.material.icons.filled.Refresh
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
import com.viabix.app.data.api.RetrofitClient
import com.viabix.app.utils.TokenManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import javax.inject.Inject

@HiltViewModel
class HomeScreenViewModel @Inject constructor(
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _userName = MutableStateFlow("Usuário")
    val userName: StateFlow<String> = _userName
    
    private val _userLogin = MutableStateFlow("admin")
    val userLogin: StateFlow<String> = _userLogin

    private val _dashboardState = MutableStateFlow(DashboardHomeState())
    val dashboardState: StateFlow<DashboardHomeState> = _dashboardState

    init {
        loadUserData()
        loadDashboard()
    }

    private fun loadUserData() {
        viewModelScope.launch {
            _userName.value = tokenManager.getUserName() ?: "Usuário"
            _userLogin.value = tokenManager.getUserLogin() ?: "admin"
        }
    }

    fun loadDashboard() {
        viewModelScope.launch {
            _dashboardState.value = _dashboardState.value.copy(isLoading = true, error = null)

            try {
                val response = RetrofitClient.getApiService().getDashboardStats()
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.success) {
                        _dashboardState.value = DashboardHomeState(
                            isLoading = false,
                            stats = body,
                            error = null
                        )
                    } else {
                        _dashboardState.value = DashboardHomeState(
                            isLoading = false,
                            error = body?.message ?: "Nao foi possivel carregar o painel."
                        )
                    }
                } else {
                    val message = if (response.code() == 401) {
                        "Sessao expirada. Saia e entre novamente."
                    } else {
                        "Servidor respondeu ${response.code()}."
                    }
                    _dashboardState.value = DashboardHomeState(
                        isLoading = false,
                        error = message
                    )
                }
            } catch (e: Exception) {
                Log.e("HomeScreen", "Erro ao carregar dashboard", e)
                _dashboardState.value = DashboardHomeState(
                    isLoading = false,
                    error = e.message ?: "Falha ao carregar indicadores."
                )
            }
        }
    }
}

data class DashboardHomeState(
    val isLoading: Boolean = false,
    val stats: DashboardStatsResponse? = null,
    val error: String? = null
)

data class DashboardStatsResponse(
    val success: Boolean = false,
    val message: String = "",
    val anvis: Int = 0,
    val projetos: Int = 0,
    val usuarios: Int = 0,
    val lideres: Int = 0,
    val vinculos: Int = 0,
    val anvis_recentes: Int = 0,
    val projetos_recentes: Int = 0,
    val dashboard_operacional: DashboardOperacional? = null
)

data class DashboardOperacional(
    val atencao_hoje: List<DashboardInsight>? = emptyList(),
    val prioridades: List<DashboardInsight>? = emptyList(),
    val health: DashboardHealth? = DashboardHealth(),
    val tendencias: List<DashboardTrend>? = emptyList()
)

data class DashboardInsight(
    val titulo: String = "",
    val detalhe: String = "",
    val status: String? = "ok",
    val statusLabel: String? = "",
    val actionLabel: String? = null,
    val actionUrl: String? = null
)

data class DashboardHealth(
    val score: Int = 0,
    val label: String? = "",
    val message: String? = ""
)

data class DashboardTrend(
    val label: String? = "",
    val valorAtual: Int = 0,
    val referencia: Int = 0,
    val unidade: String? = "",
    val descricao: String? = ""
)

@Composable
fun HomeScreen(
    viewModel: HomeScreenViewModel = hiltViewModel(),
    onLogout: () -> Unit,
    onNavigateToViabilidade: () -> Unit,
    onNavigateToANVI: () -> Unit,
    onNavigateToProjetos: () -> Unit,
    onNavigateToFaturamento: () -> Unit = {},
    onOpenUrl: (String) -> Unit = {}
) {
    val userName by viewModel.userName.collectAsState()
    val userLogin by viewModel.userLogin.collectAsState()
    val dashboardState by viewModel.dashboardState.collectAsState()

    val primaryGreen = Color(0xFF0a3d2e)
    val lightGray = Color(0xFFF8F8F8)

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(lightGray)
    ) {
        // Header
        Surface(
            modifier = Modifier
                .fillMaxWidth()
                .height(100.dp),
            color = primaryGreen
        ) {
            Row(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(16.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column {
                    Text(
                        "Viabix",
                        fontSize = 24.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color.White
                    )
                    Text(
                        "Sistema Industrial Completo",
                        fontSize = 13.sp,
                        color = Color.White.copy(alpha = 0.8f)
                    )
                }
                Row(
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    IconButton(onClick = { Log.d("HomeScreen", "Configurações clicado") }) {
                        Icon(Icons.Default.Settings, contentDescription = "Configurações", tint = Color.White)
                    }
                    IconButton(onClick = {
                        Log.d("HomeScreen", "Botão Sair clicado")
                        onLogout()
                    }) {
                        Icon(Icons.Default.ExitToApp, contentDescription = "Sair", tint = Color.White)
                    }
                }
            }
        }

        // Welcome Section
        Box(
            modifier = Modifier
                .fillMaxWidth()
                .background(Color(0xFF1a5a3a))
                .padding(24.dp)
        ) {
            Column(
                modifier = Modifier.fillMaxWidth(),
                horizontalAlignment = Alignment.CenterHorizontally,
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                Text(
                    "Bem-vindo ao\nSistema Viabix",
                    fontSize = 28.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color.White,
                    modifier = Modifier.fillMaxWidth(),
                    textAlign = androidx.compose.ui.text.style.TextAlign.Center
                )
                Text(
                    "Escolha o módulo que deseja acessar",
                    fontSize = 14.sp,
                    color = Color.White.copy(alpha = 0.8f),
                    modifier = Modifier.fillMaxWidth(),
                    textAlign = androidx.compose.ui.text.style.TextAlign.Center
                )
                Spacer(modifier = Modifier.height(8.dp))
                Text(
                    "$userName - $userLogin",
                    fontSize = 13.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = Color.White.copy(alpha = 0.9f),
                    modifier = Modifier.fillMaxWidth(),
                    textAlign = androidx.compose.ui.text.style.TextAlign.Center
                )
            }
        }

        // Modules List
        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(16.dp),
            contentPadding = PaddingValues(bottom = 24.dp)
        ) {
            item {
                OperationalDashboardCard(
                    state = dashboardState,
                    onRefresh = viewModel::loadDashboard,
                    onOpenUrl = onOpenUrl
                )
            }

            item {
                QuickActionsCard(
                    onOpenAnvi = onNavigateToANVI,
                    onOpenProjetos = onNavigateToProjetos,
                    onOpenBilling = onNavigateToFaturamento,
                    onRefresh = viewModel::loadDashboard
                )
            }

            item {
                ModuleCardWithFeatures(
                    title = "ANVI",
                    description = "Análise de Viabilidade Industrial e Orçamentação",
                    features = listOf("Cálculo de custos industriais", "Análise de matéria-prima", "Relatórios detalhados"),
                    iconColor = Color(0xFF2a6e3b),
                    icon = "📊",
                    onClick = onNavigateToANVI
                )
            }

            item {
                ModuleCardWithFeatures(
                    title = "Controle de Projetos",
                    description = "Gestão Completa de Projetos e Cronogramas",
                    features = listOf("Gestão de tarefas e milestones", "Gráfico de Gantt interativo", "Acompanhamento em tempo real"),
                    iconColor = Color(0xFF3498db),
                    icon = "📅",
                    onClick = onNavigateToProjetos
                )
            }

            item {
                ModuleCardWithFeatures(
                    title = "Dashboard de Viabilidade",
                    description = "Análise de Compatibilidade e Viabilidade",
                    features = listOf("Análise de compatibilidade técnica", "Métricas de performance", "Indicadores visuais"),
                    iconColor = Color(0xFF0a3d2e),
                    icon = "📈",
                    onClick = onNavigateToViabilidade
                )
            }

            item {
                ModuleCardWithFeatures(
                    title = "Faturamento e SaaS",
                    description = "Gestão de Planos, Billing e Informações de Assinatura",
                    features = listOf("Visualizar planos disponíveis", "Gerenciar faturamento", "Informações de administração SaaS"),
                    iconColor = Color(0xFF0f4a28),
                    icon = "💳",
                    onClick = onNavigateToFaturamento
                )
            }
        }
    }
}

@Composable
fun QuickActionsCard(
    onOpenAnvi: () -> Unit,
    onOpenProjetos: () -> Unit,
    onOpenBilling: () -> Unit,
    onRefresh: () -> Unit
) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Text(
                "Acoes rapidas",
                fontSize = 16.sp,
                fontWeight = FontWeight.Bold,
                color = Color(0xFF0a3d2e)
            )
            Text(
                "Atalhos para as tarefas mais usadas no dia a dia.",
                fontSize = 12.sp,
                color = Color.Gray
            )

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                QuickActionButton(
                    label = "ANVI",
                    icon = "📊",
                    onClick = onOpenAnvi,
                    modifier = Modifier.weight(1f)
                )
                QuickActionButton(
                    label = "Projetos",
                    icon = "📅",
                    onClick = onOpenProjetos,
                    modifier = Modifier.weight(1f)
                )
            }

            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                QuickActionButton(
                    label = "Faturamento",
                    icon = "💳",
                    onClick = onOpenBilling,
                    modifier = Modifier.weight(1f)
                )
                QuickActionButton(
                    label = "Atualizar",
                    icon = "↻",
                    onClick = onRefresh,
                    modifier = Modifier.weight(1f)
                )
            }
        }
    }
}

@Composable
fun QuickActionButton(
    label: String,
    icon: String,
    onClick: () -> Unit,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier
            .height(64.dp)
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(8.dp),
        color = Color(0xFFF3F7F4)
    ) {
        Row(
            modifier = Modifier
                .fillMaxSize()
                .padding(horizontal = 12.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            Text(icon, fontSize = 22.sp)
            Text(
                label,
                fontSize = 13.sp,
                fontWeight = FontWeight.SemiBold,
                color = Color(0xFF0a3d2e)
            )
        }
    }
}

@Composable
fun ModuleCardWithFeatures(
    title: String,
    description: String,
    features: List<String>,
    iconColor: Color,
    icon: String,
    onClick: () -> Unit
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.spacedBy(12.dp),
                verticalAlignment = Alignment.Top
            ) {
                Box(
                    modifier = Modifier
                        .size(60.dp)
                        .background(
                            color = iconColor.copy(alpha = 0.15f),
                            shape = RoundedCornerShape(12.dp)
                        ),
                    contentAlignment = Alignment.Center
                ) {
                    Text(icon, fontSize = 32.sp)
                }
                Column(
                    modifier = Modifier.weight(1f),
                    verticalArrangement = Arrangement.spacedBy(4.dp)
                ) {
                    Text(
                        title,
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color(0xFF1d7a4a)
                    )
                    Text(
                        description,
                        fontSize = 12.sp,
                        color = Color.Gray,
                        maxLines = 2
                    )
                }
            }

            Column(
                modifier = Modifier.fillMaxWidth(),
                verticalArrangement = Arrangement.spacedBy(6.dp)
            ) {
                features.forEach { feature ->
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp),
                        verticalAlignment = Alignment.CenterVertically
                    ) {
                        Text("✓", fontSize = 16.sp, color = Color(0xFF1d7a4a), fontWeight = FontWeight.Bold)
                        Text(feature, fontSize = 13.sp, color = Color(0xFF333333))
                    }
                }
            }
        }
    }
}

@Composable
fun OperationalDashboardCard(
    state: DashboardHomeState,
    onRefresh: () -> Unit,
    onOpenUrl: (String) -> Unit
) {
    val primaryGreen = Color(0xFF0a3d2e)
    val stats = state.stats
    val operacional = stats?.dashboard_operacional
    val health = operacional?.health ?: DashboardHealth()

    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(8.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier.padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(14.dp)
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        "Painel operacional",
                        fontSize = 18.sp,
                        fontWeight = FontWeight.Bold,
                        color = primaryGreen
                    )
                    Text(
                        "Acompanhamento em tempo real da conta",
                        fontSize = 12.sp,
                        color = Color.Gray
                    )
                }
                IconButton(onClick = onRefresh) {
                    Icon(Icons.Default.Refresh, contentDescription = "Atualizar", tint = primaryGreen)
                }
            }

            when {
                state.isLoading -> {
                    Row(
                        modifier = Modifier
                            .fillMaxWidth()
                            .padding(vertical = 8.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        CircularProgressIndicator(
                            modifier = Modifier.size(24.dp),
                            color = primaryGreen,
                            strokeWidth = 2.dp
                        )
                        Text("Carregando indicadores...", color = Color.Gray, fontSize = 13.sp)
                    }
                }

                state.error != null -> {
                    Surface(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(8.dp),
                        color = Color(0xFFFFEBEE)
                    ) {
                        Text(
                            state.error,
                            modifier = Modifier.padding(12.dp),
                            color = Color(0xFFC62828),
                            fontSize = 13.sp
                        )
                    }
                }

                stats != null -> {
                    Row(
                        modifier = Modifier.fillMaxWidth(),
                        horizontalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        MetricBox(
                            label = "ANVIs",
                            value = stats.anvis.toString(),
                            modifier = Modifier.weight(1f)
                        )
                        MetricBox(
                            label = "Projetos",
                            value = stats.projetos.toString(),
                            modifier = Modifier.weight(1f)
                        )
                        MetricBox(
                            label = "Vinculos",
                            value = stats.vinculos.toString(),
                            modifier = Modifier.weight(1f)
                        )
                    }

                    HealthSection(health)

                    InsightSection(
                        title = "Atencao hoje",
                        items = operacional?.atencao_hoje.orEmpty().take(3),
                        onOpenUrl = onOpenUrl
                    )

                    InsightSection(
                        title = "Fila de prioridades",
                        items = operacional?.prioridades.orEmpty().take(3),
                        onOpenUrl = onOpenUrl
                    )

                    TrendSection(operacional?.tendencias.orEmpty().take(3))
                }
            }
        }
    }
}

@Composable
fun MetricBox(
    label: String,
    value: String,
    modifier: Modifier = Modifier
) {
    Surface(
        modifier = modifier,
        shape = RoundedCornerShape(8.dp),
        color = Color(0xFFF3F7F4)
    ) {
        Column(
            modifier = Modifier.padding(10.dp),
            horizontalAlignment = Alignment.CenterHorizontally
        ) {
            Text(
                value,
                fontSize = 20.sp,
                fontWeight = FontWeight.Bold,
                color = Color(0xFF0a3d2e)
            )
            Text(label, fontSize = 11.sp, color = Color.Gray)
        }
    }
}

@Composable
fun HealthSection(health: DashboardHealth) {
    val score = health.score.coerceIn(0, 100)
    val color = when {
        score >= 80 -> Color(0xFF2E7D32)
        score >= 60 -> Color(0xFF558B2F)
        score >= 40 -> Color(0xFFF57C00)
        else -> Color(0xFFC62828)
    }

    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween
        ) {
            Text(
                "Saude operacional",
                fontSize = 14.sp,
                fontWeight = FontWeight.Bold,
                color = Color(0xFF333333)
            )
            Text(
                "${health.label.orEmpty().ifBlank { "Sem dados" }} - $score%",
                fontSize = 13.sp,
                fontWeight = FontWeight.SemiBold,
                color = color
            )
        }
        LinearProgressIndicator(
            progress = score / 100f,
            modifier = Modifier
                .fillMaxWidth()
                .height(8.dp),
            color = color,
            trackColor = Color(0xFFE0E0E0)
        )
        if (health.message.orEmpty().isNotBlank()) {
            Text(health.message.orEmpty(), fontSize = 12.sp, color = Color.Gray)
        }
    }
}

@Composable
fun InsightSection(
    title: String,
    items: List<DashboardInsight>,
    onOpenUrl: (String) -> Unit
) {
    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Text(
            title,
            fontSize = 14.sp,
            fontWeight = FontWeight.Bold,
            color = Color(0xFF333333)
        )

        if (items.isEmpty()) {
            Text("Nenhum item no momento.", fontSize = 12.sp, color = Color.Gray)
        } else {
            items.forEach { item ->
                InsightRow(item, onOpenUrl)
            }
        }
    }
}

@Composable
fun InsightRow(
    item: DashboardInsight,
    onOpenUrl: (String) -> Unit
) {
    val statusColor = when (item.status.orEmpty().lowercase()) {
        "critico" -> Color(0xFFC62828)
        "atencao" -> Color(0xFFF57C00)
        else -> Color(0xFF2E7D32)
    }

    Surface(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(enabled = !item.actionUrl.isNullOrBlank()) {
                item.actionUrl?.let(onOpenUrl)
            },
        shape = RoundedCornerShape(8.dp),
        color = Color(0xFFFAFAFA)
    ) {
        Row(
            modifier = Modifier.padding(10.dp),
            horizontalArrangement = Arrangement.spacedBy(10.dp),
            verticalAlignment = Alignment.Top
        ) {
            Box(
                modifier = Modifier
                    .size(10.dp)
                    .background(statusColor, RoundedCornerShape(5.dp))
            )
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    item.titulo.ifBlank { "Item operacional" },
                    fontSize = 13.sp,
                    fontWeight = FontWeight.SemiBold,
                    color = Color(0xFF222222)
                )
                if (item.detalhe.isNotBlank()) {
                    Text(item.detalhe, fontSize = 12.sp, color = Color.Gray)
                }
            }
            if (item.statusLabel.orEmpty().isNotBlank()) {
                Column(horizontalAlignment = Alignment.End) {
                    Text(
                        item.statusLabel.orEmpty(),
                        fontSize = 11.sp,
                        fontWeight = FontWeight.Bold,
                        color = statusColor
                    )
                    if (!item.actionUrl.isNullOrBlank()) {
                        Text(
                            item.actionLabel ?: "Abrir",
                            fontSize = 11.sp,
                            color = Color(0xFF0a3d2e),
                            fontWeight = FontWeight.SemiBold
                        )
                    }
                }
            }
        }
    }
}

@Composable
fun TrendSection(items: List<DashboardTrend>) {
    if (items.isEmpty()) return

    Column(verticalArrangement = Arrangement.spacedBy(8.dp)) {
        Text(
            "Tendencias",
            fontSize = 14.sp,
            fontWeight = FontWeight.Bold,
            color = Color(0xFF333333)
        )
        items.forEach { item ->
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(item.label.orEmpty(), fontSize = 13.sp, fontWeight = FontWeight.SemiBold)
                    Text(item.descricao.orEmpty(), fontSize = 11.sp, color = Color.Gray)
                }
                Text(
                    "${item.valorAtual}${item.unidade.orEmpty()}",
                    fontSize = 16.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color(0xFF0a3d2e)
                )
            }
        }
    }
}
