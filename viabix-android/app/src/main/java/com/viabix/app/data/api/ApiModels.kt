package com.viabix.app.data.api

import com.google.gson.annotations.SerializedName
import com.google.gson.JsonParser
import com.google.gson.JsonObject
import retrofit2.Response
import java.net.ConnectException
import java.net.SocketTimeoutException
import java.net.UnknownHostException
import javax.net.ssl.SSLException

data class LoginResponse(
    val success: Boolean = false,
    val message: String = "",
    val token: String? = null,
    val user: LoginUser? = null,
    val tenant: LoginTenant? = null
)

data class LoginUser(
    val id: String = "",
    val login: String = "",
    val nome: String = "",
    val nivel: String = ""
)

data class LoginTenant(
    val id: String? = null,
    val nome: String? = null,
    val status: String? = null
)

data class AnviListResponse(
    val data: List<AnviSummary> = emptyList(),
    val total: Int = 0,
    val page: Int = 1,
    val limit: Int = 100,
    @SerializedName("total_pages") val totalPages: Int = 1
)

data class AnviSummary(
    val id: String = "",
    val numero: String = "",
    val revisao: String = "",
    val cliente: String? = null,
    val projeto: String? = null,
    val produto: String? = null,
    val status: String? = null,
    @SerializedName("data_anvi") val dataAnvi: String? = null,
    @SerializedName("data_atualizacao") val dataAtualizacao: String? = null
)

data class DeleteAnviRequest(val id: String)

data class ProjectListResponse(
    val success: Boolean = false,
    val message: String? = null,
    val data: List<ProjectSummary> = emptyList(),
    val total: Int = 0,
    val page: Int = 1,
    @SerializedName("total_pages") val totalPages: Int = 1
)

data class ProjectDetailResponse(
    val success: Boolean = false,
    val message: String? = null,
    val data: JsonObject? = null
)

data class ProjectLeaderListResponse(
    val success: Boolean = false,
    val message: String? = null,
    val data: List<ProjectLeader> = emptyList()
)

data class ProjectLeader(
    val id: Int = 0,
    val name: String = "",
    val email: String? = null,
    val department: String? = null
)

data class ProjectSummary(
    val id: Int = 0,
    val cliente: String? = null,
    val projectName: String? = null,
    val segmento: String? = null,
    val projectLeader: String? = null,
    val codigo: String? = null,
    val anviNumber: String? = null,
    val fase: String? = null,
    val status: String? = null,
    val tasks: Map<String, ProjectTask> = emptyMap(),
    val updated_at: String? = null
) {
    val progressPercent: Int
        get() {
            val taskWeights = mapOf(
                "kom" to 1,
                "ferramental" to 3,
                "cadBomFt" to 2,
                "tryout" to 3,
                "entrega" to 1,
                "psw" to 1,
                "handover" to 1
            )
            val completedWeight = taskWeights.entries.sumOf { (taskKey, weight) ->
                if (!tasks[taskKey]?.executed.isNullOrBlank()) weight else 0
            }
            return ((completedWeight.toDouble() / taskWeights.values.sum()) * 100).toInt()
        }
}

data class ProjectTask(
    val planned: String? = null,
    val start: String? = null,
    val executed: String? = null,
    val duration: Int? = null
)

data class OperationResponse(
    val success: Boolean = false,
    val message: String? = null,
    val error: String? = null,
    val insertId: Int? = null,
    val updatedId: Int? = null,
    val versao: Int? = null
)

data class SubscriptionResponse(
    val success: Boolean = false,
    val message: String? = null,
    val tenant: SubscriptionTenant? = null,
    val subscription: Subscription? = null
)

data class SubscriptionTenant(
    val id: String? = null,
    val nome: String? = null,
    val status: String? = null
)

data class Subscription(
    val id: String = "",
    val status: String = "",
    val gateway: String = "",
    val cycle: String = "",
    val amount: Double = 0.0,
    @SerializedName("trial_until") val trialUntil: String? = null,
    @SerializedName("ends_at") val endsAt: String? = null,
    @SerializedName("days_remaining") val daysRemaining: Int? = null,
    val plan: SubscriptionPlan? = null
)

data class SubscriptionPlan(
    val code: String = "",
    val name: String = "",
    val limits: PlanLimits = PlanLimits(),
    val features: PlanFeatures = PlanFeatures()
)

data class PlanLimits(
    val users: Int? = null,
    @SerializedName("anvis_monthly") val anvisMonthly: Int? = null,
    @SerializedName("active_projects") val activeProjects: Int? = null
)

data class PlanFeatures(
    @SerializedName("modulo_anvi") val anvi: Boolean = false,
    @SerializedName("modulo_projetos") val projects: Boolean = false,
    @SerializedName("exportacao") val export: Boolean = false,
    val api: Boolean = false,
    val sso: Boolean = false
)

data class InvoicesResponse(
    val success: Boolean = false,
    val message: String? = null,
    val invoices: List<Invoice> = emptyList()
)

data class Invoice(
    val id: Int = 0,
    val number: String = "",
    val status: String = "",
    @SerializedName("amount_total") val amountTotal: Double = 0.0,
    @SerializedName("amount_paid") val amountPaid: Double = 0.0,
    val currency: String = "BRL",
    @SerializedName("billing_url") val billingUrl: String? = null,
    @SerializedName("due_at") val dueAt: String? = null,
    @SerializedName("paid_at") val paidAt: String? = null,
    @SerializedName("plan_name") val planName: String? = null
)

fun Response<*>.friendlyError(fallback: String): String {
    val statusMessage = when (code()) {
        401 -> "Sua sessão expirou. Entre novamente."
        403 -> "Sua conta não tem permissão para esta ação."
        404 -> "O registro solicitado não foi encontrado."
        409 -> "Já existe um registro com esses dados."
        in 500..599 -> "O servidor está temporariamente indisponível."
        else -> null
    }
    if (statusMessage != null) return statusMessage

    val serverMessage = runCatching {
        val root = JsonParser().parse(errorBody()?.string().orEmpty()).asJsonObject
        sequenceOf("message", "error", "erro")
            .mapNotNull { key -> root.get(key)?.takeIf { it.isJsonPrimitive }?.asString }
            .firstOrNull { it.isNotBlank() }
    }.getOrNull()

    return serverMessage ?: fallback
}

fun Throwable.friendlyConnectionError(action: String): String = when (this) {
    is SocketTimeoutException -> "O servidor demorou para responder. Tente novamente."
    is UnknownHostException, is ConnectException -> "Sem conexão com o servidor. Verifique sua internet."
    is SSLException -> "Não foi possível estabelecer uma conexão segura."
    else -> "Falha de conexão ao $action."
}
