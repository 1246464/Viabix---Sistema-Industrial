package com.viabix.app.data.api

import android.content.Context
import com.viabix.app.BuildConfig
import com.google.gson.JsonObject
import com.viabix.app.presentation.screens.home.DashboardStatsResponse
import com.viabix.app.presentation.screens.viabilidade.DashboardViabilidadeResponse
import com.viabix.app.utils.TokenManager
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.Body
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.http.GET
import retrofit2.http.HTTP
import retrofit2.http.POST
import retrofit2.http.Query
import java.util.concurrent.TimeUnit

object RetrofitClient {
    private lateinit var retrofit: Retrofit
    private lateinit var tokenManager: TokenManager

    fun initialize(context: Context) {
        tokenManager = TokenManager(context.applicationContext)

        val authInterceptor = Interceptor { chain ->
            val originalRequest = chain.request()
            val token = if (originalRequest.url.encodedPath.endsWith("/api/login.php")) {
                null
            } else {
                tokenManager.getTokenSync()
            }

            val request = if (token.isNullOrBlank()) {
                originalRequest
            } else {
                originalRequest.newBuilder()
                    .header("Authorization", "Bearer $token")
                    .header("Accept", "application/json")
                    .build()
            }
            chain.proceed(request)
        }

        val loggingInterceptor = HttpLoggingInterceptor().apply {
            level = if (BuildConfig.DEBUG) {
                HttpLoggingInterceptor.Level.BASIC
            } else {
                HttpLoggingInterceptor.Level.NONE
            }
            redactHeader("Authorization")
        }

        val client = OkHttpClient.Builder()
            .addInterceptor(authInterceptor)
            .addInterceptor(loggingInterceptor)
            .connectTimeout(20, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .build()

        retrofit = Retrofit.Builder()
            .baseUrl(BuildConfig.API_BASE_URL)
            .client(client)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
    }

    fun getApiService(): ViabixApiService = retrofit.create(ViabixApiService::class.java)
}

interface ViabixApiService {
    @FormUrlEncoded
    @POST("api/login.php")
    suspend fun login(
        @Field("email") login: String,
        @Field("password") password: String
    ): Response<LoginResponse>

    @GET("api/estatisticas.php")
    suspend fun getDashboardStats(): Response<DashboardStatsResponse>

    @GET("api/anvi.php")
    suspend fun getAnvis(
        @Query("page") page: Int = 1,
        @Query("limit") limit: Int = 100
    ): Response<AnviListResponse>

    @GET("api/anvi.php")
    suspend fun getAnvi(@Query("id") id: String): Response<JsonObject>

    @POST("api/anvi.php")
    suspend fun saveAnvi(@Body anvi: JsonObject): Response<OperationResponse>

    @HTTP(method = "DELETE", path = "api/anvi.php", hasBody = true)
    suspend fun deleteAnvi(@Body request: DeleteAnviRequest): Response<OperationResponse>

    @GET("api/dashboard_viabilidade_simple.php")
    suspend fun getDashboardViabilidade(
        @Query("anvi_id") anviId: String
    ): Response<DashboardViabilidadeResponse>

    @FormUrlEncoded
    @POST("Controle_de_projetos/api_mysql.php")
    suspend fun getProjects(
        @Field("action") action: String = "getProjects",
        @Field("page") page: Int = 1,
        @Field("limit") limit: Int = 100
    ): Response<ProjectListResponse>

    @FormUrlEncoded
    @POST("Controle_de_projetos/api_mysql.php")
    suspend fun getProject(
        @Field("projectId") projectId: Int,
        @Field("action") action: String = "getProject"
    ): Response<ProjectDetailResponse>

    @FormUrlEncoded
    @POST("Controle_de_projetos/api_mysql.php")
    suspend fun getProjectLeaders(
        @Field("action") action: String = "getLeaders"
    ): Response<ProjectLeaderListResponse>

    @FormUrlEncoded
    @POST("Controle_de_projetos/api_mysql.php")
    suspend fun saveProject(
        @Field("project") projectJson: String,
        @Field("action") action: String = "saveProject"
    ): Response<OperationResponse>

    @FormUrlEncoded
    @POST("Controle_de_projetos/api_mysql.php")
    suspend fun deleteProject(
        @Field("projectId") projectId: Int,
        @Field("action") action: String = "deleteProject"
    ): Response<OperationResponse>

    @GET("api/subscription_current.php")
    suspend fun getSubscription(): Response<SubscriptionResponse>

    @GET("api/billing_invoices.php")
    suspend fun getInvoices(@Query("limit") limit: Int = 50): Response<InvoicesResponse>
}
