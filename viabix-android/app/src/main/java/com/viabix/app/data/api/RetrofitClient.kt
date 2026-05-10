package com.viabix.app.data.api

import android.content.Context
import com.viabix.app.presentation.screens.viabilidade.DashboardViabilidadeResponse
import com.viabix.app.utils.TokenManager
import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.GET
import retrofit2.http.Query
import java.util.concurrent.TimeUnit

object RetrofitClient {
    private const val BASE_URL = "https://viabix.com.br/"
    private lateinit var retrofit: Retrofit
    private lateinit var tokenManager: TokenManager
    private val cookieStore = mutableSetOf<Cookie>()

    fun initialize(context: Context, tokenMgr: TokenManager) {
        tokenManager = tokenMgr

        // Cookie Jar
        val cookieJar = object : CookieJar {
            override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
                cookieStore.addAll(cookies)
            }

            override fun loadForRequest(url: HttpUrl): List<Cookie> {
                return cookieStore.toList()
            }
        }

        // Logging Interceptor
        val loggingInterceptor = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }

        // Auth Interceptor para adicionar token JWT
        val authInterceptor = Interceptor { chain ->
            val originalRequest = chain.request()
            val token = tokenManager.getToken() ?: ""
            
            val requestWithAuth = if (token.isNotEmpty()) {
                originalRequest.newBuilder()
                    .addHeader("Authorization", "Bearer $token")
                    .addHeader("Content-Type", "application/json")
                    .build()
            } else {
                originalRequest.newBuilder()
                    .addHeader("Content-Type", "application/json")
                    .build()
            }

            chain.proceed(requestWithAuth)
        }

        // OkHttpClient with SSL verification disabled for self-signed certs
        val okHttpClient = OkHttpClient.Builder()
            .addInterceptor(authInterceptor)
            .addInterceptor(loggingInterceptor)
            .cookieJar(cookieJar)
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .build()

        retrofit = Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
    }

    fun getApiService(): ViabixApiServiceWithViabilidade {
        return retrofit.create(ViabixApiServiceWithViabilidade::class.java)
    }
}

/**
 * Interface consolidada com todos os endpoints incluindo Viabilidade
 */
interface ViabixApiServiceWithViabilidade {
    // Autenticação
    @GET("api/login.php")
    suspend fun login(
        @Query("email") email: String,
        @Query("password") password: String
    ): Response<Map<String, Any>>

    // ANVI endpoints
    @GET("api/anvi_list.php")
    suspend fun getAnviList(): Response<Map<String, Any>>

    // Dashboard Viabilidade
    @GET("api/dashboard_viabilidade_simple.php")
    suspend fun getDashboardViabilidade(
        @Query("anvi_id") anviId: String
    ): Response<DashboardViabilidadeResponse>

    // Adicionar mais endpoints conforme necessário
}
