package com.viabix.app.presentation.screens.home

import android.util.Log
import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Settings
import androidx.compose.material.icons.filled.ExitToApp
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel
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

    init {
        loadUserData()
    }

    private fun loadUserData() {
        viewModelScope.launch {
            _userName.value = tokenManager.getUserName() ?: "Usuário"
            _userLogin.value = tokenManager.getUserLogin() ?: "admin"
        }
    }
}

@Composable
fun HomeScreen(
    viewModel: HomeScreenViewModel = hiltViewModel(),
    onLogout: () -> Unit,
    onNavigateToViabilidade: () -> Unit,
    onNavigateToANVI: () -> Unit,
    onNavigateToProjetos: () -> Unit,
    onNavigateToFaturamento: () -> Unit = {}
) {
    val userName by viewModel.userName.collectAsState()
    val userLogin by viewModel.userLogin.collectAsState()

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
