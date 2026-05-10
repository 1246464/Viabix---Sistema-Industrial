package com.viabix.app.presentation.screens.viabilidade

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.hilt.navigation.compose.hiltViewModel

@Composable
fun ViabilidadeDashboardScreen(
    viewModel: ViabilidadeViewModel = hiltViewModel(),
    onNavigateBack: () -> Unit
) {
    val uiState by viewModel.uiState.collectAsState()
    var anviInput by remember { mutableStateOf("") }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(
                brush = androidx.compose.foundation.background(
                    color = Color(0xFF2ecc71)
                ).brush
            )
    ) {
        // Header
        Surface(
            modifier = Modifier
                .fillMaxWidth()
                .height(80.dp),
            color = Color(0xFF27ae60)
        ) {
            Row(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(16.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Text(
                    "Dashboard de Viabilidade",
                    fontSize = 20.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color.White
                )
                Button(
                    onClick = onNavigateBack,
                    colors = ButtonDefaults.buttonColors(
                        containerColor = Color.White
                    )
                ) {
                    Text("Voltar", color = Color(0xFF27ae60))
                }
            }
        }

        LazyColumn(
            modifier = Modifier
                .fillMaxSize()
                .padding(16.dp),
            verticalArrangement = Arrangement.spacedBy(12.dp)
        ) {
            // Input Section
            item {
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    shape = RoundedCornerShape(12.dp),
                    colors = CardDefaults.cardColors(containerColor = Color.White)
                ) {
                    Column(
                        modifier = Modifier.padding(16.dp),
                        verticalArrangement = Arrangement.spacedBy(12.dp)
                    ) {
                        TextField(
                            value = anviInput,
                            onValueChange = { anviInput = it },
                            label = { Text("ID do ANVI") },
                            modifier = Modifier.fillMaxWidth(),
                            singleLine = true,
                            colors = TextFieldDefaults.colors(
                                focusedContainerColor = Color(0xFFF5F5F5),
                                unfocusedContainerColor = Color(0xFFF5F5F5)
                            )
                        )
                        Button(
                            onClick = {
                                if (anviInput.isNotBlank()) {
                                    viewModel.loadViabilidadeData(anviInput)
                                }
                            },
                            modifier = Modifier
                                .fillMaxWidth()
                                .height(48.dp),
                            colors = ButtonDefaults.buttonColors(
                                containerColor = Color(0xFF2ecc71)
                            )
                        ) {
                            Text("Carregar Análise", color = Color.White, fontSize = 16.sp)
                        }
                    }
                }
            }

            // Loading state
            if (uiState.isLoading) {
                item {
                    Box(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(200.dp),
                        contentAlignment = Alignment.Center
                    ) {
                        CircularProgressIndicator(color = Color.White)
                    }
                }
            }

            // Error state
            if (uiState.error != null) {
                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = Color(0xFFffebee))
                    ) {
                        Column(
                            modifier = Modifier.padding(16.dp)
                        ) {
                            Text(
                                "❌ Erro ao carregar",
                                fontSize = 16.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFFC62828)
                            )
                            Text(
                                uiState.error ?: "Erro desconhecido",
                                color = Color(0xFFC62828),
                                modifier = Modifier.padding(top = 8.dp)
                            )
                        }
                    }
                }
            }

            // ANVI Info
            if (uiState.anviData != null && !uiState.isLoading && uiState.error == null) {
                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = Color.White)
                    ) {
                        Column(
                            modifier = Modifier.padding(16.dp),
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Text(
                                "📋 Informações do ANVI",
                                fontSize = 16.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF27ae60)
                            )
                            InfoRow("ID:", uiState.anviData!!.id)
                            InfoRow("Número:", uiState.anviData!!.numero)
                            InfoRow("Cliente:", uiState.anviData!!.cliente)
                            InfoRow("Projeto:", uiState.anviData!!.projeto)
                            InfoRow("Status:", uiState.anviData!!.status)
                        }
                    }
                }
            }

            // Viability Score
            if (uiState.viabilidadeData != null && !uiState.isLoading && uiState.error == null) {
                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        shape = RoundedCornerShape(12.dp),
                        colors = CardDefaults.cardColors(containerColor = Color.White)
                    ) {
                        Column(
                            modifier = Modifier.padding(16.dp),
                            horizontalAlignment = Alignment.CenterHorizontally,
                            verticalArrangement = Arrangement.spacedBy(12.dp)
                        ) {
                            Text(
                                "👍 Viabilidade Geral",
                                fontSize = 16.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF27ae60)
                            )
                            
                            // Score Circle
                            Box(
                                modifier = Modifier
                                    .size(100.dp)
                                    .background(
                                        color = Color(0xFF2ecc71),
                                        shape = RoundedCornerShape(50.dp)
                                    ),
                                contentAlignment = Alignment.Center
                            ) {
                                Text(
                                    "${uiState.viabilidadeData!!.score_geral}",
                                    fontSize = 36.sp,
                                    fontWeight = FontWeight.Bold,
                                    color = Color.White
                                )
                            }

                            Text(
                                uiState.viabilidadeData!!.status,
                                fontSize = 20.sp,
                                fontWeight = FontWeight.Bold,
                                color = Color(0xFF2ecc71)
                            )
                            Text(
                                uiState.viabilidadeData!!.recomendacao,
                                fontSize = 14.sp,
                                color = Color.Gray
                            )
                        }
                    }
                }
            }

            // Detailed Analysis
            if (uiState.analiseData != null && !uiState.isLoading && uiState.error == null) {
                item {
                    Text(
                        "🔬 Análise Detalhada",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color.White,
                        modifier = Modifier.padding(top = 12.dp)
                    )
                }

                // Financial
                item {
                    AnalysisCard(
                        title = "Financeiro",
                        score = uiState.analiseData!!.financeiro.score,
                        details = listOf(
                            "Investimento: R$ ${uiState.analiseData!!.financeiro.investimento.toLocaleString()}",
                            "ROI: ${uiState.analiseData!!.financeiro.roi_esperado}%",
                            "Payback: ${uiState.analiseData!!.financeiro.payback_meses} meses"
                        )
                    )
                }

                // Planning
                item {
                    AnalysisCard(
                        title = "Planejamento",
                        score = uiState.analiseData!!.planejamento.score,
                        details = listOf(
                            "Fases: ${uiState.analiseData!!.planejamento.fases}",
                            "Duração: ${uiState.analiseData!!.planejamento.duracao_meses} meses"
                        )
                    )
                }

                // Quality
                item {
                    AnalysisCard(
                        title = "Qualidade",
                        score = uiState.analiseData!!.qualidade.score.toInt(),
                        details = listOf(
                            "Cobertura de Testes: ${uiState.analiseData!!.qualidade.cobertura_testes}%",
                            "Score de Código: ${uiState.analiseData!!.qualidade.score_codigo}"
                        )
                    )
                }

                // Resources
                item {
                    AnalysisCard(
                        title = "Recursos",
                        score = uiState.analiseData!!.recursos.score,
                        details = listOf(
                            "Equipe: ${uiState.analiseData!!.recursos.equipe} pessoas",
                            "Especialistas: ${uiState.analiseData!!.recursos.especialistas}"
                        )
                    )
                }
            }

            // Compatibilities
            if (uiState.compatibilidades.isNotEmpty() && !uiState.isLoading && uiState.error == null) {
                item {
                    Text(
                        "✅ Compatibilidades por Área",
                        fontSize = 16.sp,
                        fontWeight = FontWeight.Bold,
                        color = Color.White,
                        modifier = Modifier.padding(top = 12.dp)
                    )
                }

                items(uiState.compatibilidades.size) { index ->
                    val comp = uiState.compatibilidades[index]
                    CompatibilityCard(comp)
                }
            }

            // Bottom spacing
            item {
                Spacer(modifier = Modifier.height(20.dp))
            }
        }
    }
}

@Composable
fun InfoRow(label: String, value: String) {
    Row(
        modifier = Modifier.fillMaxWidth(),
        horizontalArrangement = Arrangement.SpaceBetween
    ) {
        Text(label, fontWeight = FontWeight.Bold, color = Color.Black)
        Text(value, color = Color.Gray)
    }
}

@Composable
fun AnalysisCard(title: String, score: Int, details: List<String>) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White)
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            horizontalArrangement = Arrangement.spacedBy(16.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Score Circle
            Box(
                modifier = Modifier
                    .size(70.dp)
                    .background(
                        color = Color(0xFF2ecc71),
                        shape = RoundedCornerShape(35.dp)
                    ),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    "$score",
                    fontSize = 24.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color.White
                )
            }

            // Details
            Column(
                verticalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                Text(
                    title,
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color.Black
                )
                details.forEach { detail ->
                    Text(detail, fontSize = 12.sp, color = Color.Gray)
                }
            }
        }
    }
}

@Composable
fun CompatibilityCard(compatibility: Compatibilidade) {
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White)
    ) {
        Row(
            modifier = Modifier.padding(16.dp),
            horizontalArrangement = Arrangement.spacedBy(12.dp),
            verticalAlignment = Alignment.CenterVertically
        ) {
            // Icon
            Box(
                modifier = Modifier
                    .size(50.dp)
                    .background(
                        color = if (compatibility.status == "compativel") Color(0xFF4CAF50) else Color(0xFFf44336),
                        shape = RoundedCornerShape(25.dp)
                    ),
                contentAlignment = Alignment.Center
            ) {
                Text(
                    if (compatibility.status == "compativel") "✓" else "✗",
                    fontSize = 24.sp,
                    color = Color.White,
                    fontWeight = FontWeight.Bold
                )
            }

            // Details
            Column(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(4.dp)
            ) {
                Text(
                    compatibility.area,
                    fontSize = 14.sp,
                    fontWeight = FontWeight.Bold,
                    color = Color.Black
                )
                Text(
                    "Score: ${compatibility.score}",
                    fontSize = 12.sp,
                    color = Color.Gray
                )
                Text(
                    compatibility.detalhes,
                    fontSize = 11.sp,
                    color = Color.Gray
                )
            }
        }
    }
}

// Extension function para formatação
fun Int.toLocaleString(): String {
    return String.format("%,d", this).replace(",", ".")
}
