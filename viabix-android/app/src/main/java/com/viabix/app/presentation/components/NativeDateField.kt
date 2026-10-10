package com.viabix.app.presentation.components

import android.app.DatePickerDialog
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.unit.dp
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale

@Composable
fun NativeDateField(
    label: String,
    value: String,
    onValueChange: (String) -> Unit,
    modifier: Modifier = Modifier
) {
    val context = LocalContext.current
    Column(modifier = modifier, verticalArrangement = Arrangement.spacedBy(4.dp)) {
        Text(label, style = MaterialTheme.typography.labelMedium)
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.spacedBy(8.dp)
        ) {
            OutlinedButton(
                onClick = {
                    val calendar = value.toCalendarOrToday()
                    DatePickerDialog(
                        context,
                        { _, year, month, day ->
                            onValueChange(String.format(Locale.US, "%04d-%02d-%02d", year, month + 1, day))
                        },
                        calendar.get(Calendar.YEAR),
                        calendar.get(Calendar.MONTH),
                        calendar.get(Calendar.DAY_OF_MONTH)
                    ).show()
                },
                modifier = Modifier.weight(1f)
            ) {
                Text(value.toDisplayDate().ifBlank { "Selecionar data" })
            }
            if (value.isNotBlank()) {
                TextButton(onClick = { onValueChange("") }) { Text("Limpar") }
            }
        }
    }
}

private fun String.toCalendarOrToday(): Calendar {
    val calendar = Calendar.getInstance()
    if (isBlank()) return calendar
    val parsed = runCatching {
        SimpleDateFormat("yyyy-MM-dd", Locale.US).apply { isLenient = false }.parse(this)
    }.getOrNull()
    if (parsed != null) calendar.time = parsed
    return calendar
}

private fun String.toDisplayDate(): String {
    if (isBlank()) return ""
    val parsed = runCatching {
        SimpleDateFormat("yyyy-MM-dd", Locale.US).apply { isLenient = false }.parse(this)
    }.getOrNull() ?: return this
    return SimpleDateFormat("dd/MM/yyyy", Locale("pt", "BR")).format(parsed)
}
