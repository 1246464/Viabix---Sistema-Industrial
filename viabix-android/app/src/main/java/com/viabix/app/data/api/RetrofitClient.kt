package com.viabix.app.data.api

import android.content.Context
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import com.viabix.app.presentation.screens.viabilidade.DashboardViabilidadeResponse
import com.viabix.app.utils.TokenManager
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.runBlocking
import okhttp3.Cookie
import okhttp3.CookieJar
import okhttp3.HttpUrl
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Response
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import retrofit2.http.Field
import retrofit2.http.FormUrlEncoded
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query
import java.util.concurrent.TimeUnit

private val Context.dataStore by preferencesDataStore(name = "settings")
private val TOKEN_KEY = stringPreferencesKey("jwt_token")

object RetrofitClient {
    private const val BASE_URL = "https://viabix.com.br/"
    private lateinit var retrofit: Retrofit
    private lateinit var tokenManager: TokenManager
    private lateinit var context: Context
    private val cookieStore = mutableSetOf<Cookie>()

    fun initialize(context: Context) {
        RetrofitClient.context = context
        tokenManager = TokenManager(context)

        val cookieJar = object : CookieJar {
            override fun saveFromResponse(url: HttpUrl, cookies: List<Cookie>) {
                cookieStore.addAll(cookies)
            }

            override fun loadForRequest(url: HttpUrl): List<Cookie> {
                return cookieStore.toList()
            }
        }

        val loggingInterceptor = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }

        val authInterceptor = Interceptor { chain ->
            val originalRequest = chain.request()
            
            // Não adicionar token na rota de login
            if (originalRequest.url.encodedPath.contains("login.php")) {
                return@Interceptor chain.proceed(originalRequest)
            }

            // Obter token de forma síncrona usando runBlocking
            val token = try {
                runBlocking {
                    context.dataStore.data.map { preferences ->
                        preferences[TOKEN_KEY]
                    }.first()
                }
            } catch (e: Exception) {
                null
            } ?: ""
            
            val requestWithAuth = if (token.isNotEmpty()) {
                originalRequest.newBuilder()
                    .addHeader("Authorization", "Bearer $token")
                    .addHeader("X-Token-Source", "okhttp-interceptor")
                    .build()
            } else {
                originalRequest
            }

            chain.proceed(requestWithAuth)
        }

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

interface ViabixApiServiceWithViabilidade {
    @FormUrlEncoded
    @POST("api/login.php")
    suspend fun login(
        @Field("login") login: String, // Alterado de email para login para testar compatibilidade
        @Field("password") password: String
    ): Response<Map<String, Any>>

    @GET("api/anvi_list.php")
    suspend fun getAnviList(): Response<Map<String, Any>>

    @GET("api/dashboard_viabilidade_simple.php")
    suspend fun getDashboardViabilidade(
        @Query("anvi_id") anviId: String
    ): Response<DashboardViabilidadeResponse>
}
