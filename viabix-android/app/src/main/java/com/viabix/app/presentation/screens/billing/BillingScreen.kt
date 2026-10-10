package com.viabix.app.presentation.screens.billing

import android.content.Intent
import android.net.Uri
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
import androidx.compose.material.icons.filled.OpenInNew
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.viabix.app.data.api.Invoice
import com.viabix.app.data.api.RetrofitClient
import com.viabix.app.data.api.SubscriptionResponse
import com.viabix.app.data.api.friendlyError
import com.viabix.app.data.api.friendlyConnectionError
import com.viabix.app.presentation.components.EmptyBlock
import com.viabix.app.presentation.components.ErrorBlock
import com.viabix.app.presentation.components.LoadingBlock
import com.viabix.app.presentation.components.NativeTopBar
import com.viabix.app.presentation.components.StatusPill
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.async
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import java.text.NumberFormat
import java.util.Locale
import javax.inject.Inject

data class BillingUiState(
    val loading: Boolean = false,
    val subscription: SubscriptionResponse? = null,
    val invoices: List<Invoice> = emptyList(),
    val error: String? = null
)

@HiltViewModel
class BillingViewModel @Inject constructor() : ViewModel() {
    private val _state = MutableStateFlow(BillingUiState())
    val state: StateFlow<BillingUiState> = _state.asStateFlow()

    init { refresh() }

    fun refresh() {
        viewModelScope.launch {
            _state.update { it.copy(loading = true, error = null) }
            try {
                val api = RetrofitClient.getApiService()
                val subscriptionRequest = async { api.getSubscription() }
                val invoicesRequest = async { api.getInvoices() }
                val subscriptionResponse = subscriptionRequest.await()
                val invoicesResponse = invoicesRequest.await()

                if (!subscriptionResponse.isSuccessful) {
                    _state.update { it.copy(loading = false, error = subscriptionResponse.friendlyError("Não foi possível carregar a assinatura.")) }
                    return@launch
                }
                if (!invoicesResponse.isSuccessful) {
                    _state.update { it.copy(loading = false, error = invoicesResponse.friendlyError("Não foi possível carregar as faturas.")) }
                    return@launch
                }

                _state.update {
                    it.copy(
                        loading = false,
                        subscription = subscriptionResponse.body(),
                        invoices = invoicesResponse.body()?.invoices.orEmpty()
                    )
                }
            } catch (exception: Exception) {
                _state.update { it.copy(loading = false, error = exception.friendlyConnectionError("carregar o faturamento")) }
            }
        }
    }
}

@Composable
fun BillingScreen(
    onNavigateBack: () -> Unit,
    viewModel: BillingViewModel = hiltViewModel()
) {
    val state by viewModel.state.collectAsState()
    val context = LocalContext.current

    Column(modifier = Modifier.fillMaxSize()) {
        NativeTopBar("Assinatura e faturas", onNavigateBack, viewModel::refresh)
        when {
            state.loading && state.subscription == null -> LoadingBlock("Carregando faturamento...")
            state.error != null && state.subscription == null -> Column(Modifier.padding(16.dp)) {
                ErrorBlock(state.error.orEmpty(), viewModel::refresh)
            }
            else -> LazyColumn(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                if (state.error != null) {
                    item { ErrorBlock(state.error.orEmpty(), viewModel::refresh) }
                }
                item { SubscriptionCard(state.subscription) }
                item { Text("Faturas", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold) }
                if (state.invoices.isEmpty()) {
                    item { EmptyBlock("Nenhuma fatura encontrada.") }
                } else {
                    items(state.invoices, key = { it.id }) { invoice ->
                        InvoiceCard(invoice) { url ->
                            context.startActivity(Intent(Intent.ACTION_VIEW, Uri.parse(url)))
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun SubscriptionCard(response: SubscriptionResponse?) {
    val subscription = response?.subscription
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            Text(response?.tenant?.nome ?: "Minha empresa", style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            if (subscription == null) {
                Text(response?.message ?: "Nenhuma assinatura ativa.", color = Color.Gray)
                return@Column
            }
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text(subscription.plan?.name ?: "Plano", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.Bold)
                    Text(subscription.cycle.ifBlank { "Ciclo não informado" }, color = Color.Gray)
                }
                StatusPill(subscription.status)
            }
            val limits = subscription.plan?.limits
            Text("Usuários: ${formatLimit(limits?.users)}")
            Text("ANVIs mensais: ${formatLimit(limits?.anvisMonthly)}")
            Text("Projetos ativos: ${formatLimit(limits?.activeProjects)}")
            val renewal = subscription.endsAt ?: subscription.trialUntil
            if (!renewal.isNullOrBlank()) Text("Próxima referência: $renewal")
            subscription.daysRemaining?.let { Text("Dias restantes: $it") }
        }
    }
}

@Composable
private fun InvoiceCard(invoice: Invoice, onOpenPayment: (String) -> Unit) {
    val currency = NumberFormat.getCurrencyInstance(Locale("pt", "BR"))
    Card(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(12.dp),
        colors = CardDefaults.cardColors(containerColor = Color.White),
        elevation = CardDefaults.cardElevation(defaultElevation = 1.dp)
    ) {
        Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(8.dp)) {
            Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                Column {
                    Text(invoice.number.ifBlank { "Fatura #${invoice.id}" }, fontWeight = FontWeight.Bold)
                    Text(invoice.planName.orEmpty(), color = Color.Gray)
                }
                StatusPill(invoice.status)
            }
            Text(currency.format(invoice.amountTotal), style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.Bold)
            if (!invoice.dueAt.isNullOrBlank()) Text("Vencimento: ${invoice.dueAt}")
            invoice.billingUrl?.takeIf { it.startsWith("https://") }?.let { url ->
                Button(onClick = { onOpenPayment(url) }) {
                    Icon(Icons.Default.OpenInNew, contentDescription = null)
                    Text(" Abrir pagamento seguro")
                }
            }
        }
    }
}

private fun formatLimit(value: Int?): String = value?.toString() ?: "Ilimitado"
