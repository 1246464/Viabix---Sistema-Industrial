package com.viabix.app.presentation.components

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Surface
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp

data class GuidanceSummary(
    val completed: Int,
    val total: Int,
    val pending: List<String> = emptyList(),
    val warnings: List<String> = emptyList()
)

@Composable
fun ModuleGuidanceCard(
    title: String,
    summary: GuidanceSummary,
    tips: List<String> = emptyList()
) {
    var expanded by remember { mutableStateOf(false) }
    val progress = if (summary.total > 0) {
        (summary.completed.toFloat() / summary.total.toFloat()).coerceIn(0f, 1f)
    } else 0f

    Surface(
        modifier = Modifier.fillMaxWidth(),
        color = Color(0xFFEAF4EE),
        shape = RoundedCornerShape(12.dp)
    ) {
        Column(
            modifier = Modifier.padding(14.dp),
            verticalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            Text(title, style = MaterialTheme.typography.titleSmall, fontWeight = FontWeight.Bold)
            LinearProgressIndicator(progress = progress, modifier = Modifier.fillMaxWidth())
            Text(
                "${summary.completed} de ${summary.total} grupos concluídos",
                style = MaterialTheme.typography.bodySmall
            )
            summary.pending.take(4).forEach { Text("• $it", style = MaterialTheme.typography.bodySmall) }
            summary.warnings.take(3).forEach {
                Text("Atenção: $it", color = Color(0xFF9A5A00), style = MaterialTheme.typography.bodySmall)
            }
            if (summary.pending.size > 4 || tips.isNotEmpty()) {
                TextButton(onClick = { expanded = !expanded }) {
                    Text(if (expanded) "Ocultar orientações" else "Ver orientações")
                }
            }
            if (expanded) {
                summary.pending.drop(4).forEach { Text("• $it", style = MaterialTheme.typography.bodySmall) }
                tips.forEach { Text("Dica: $it", style = MaterialTheme.typography.bodySmall) }
            }
        }
    }
}
