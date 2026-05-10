package com.viabix.app.data.api

import com.viabix.app.presentation.screens.viabilidade.DashboardViabilidadeResponse
import retrofit2.Response
import retrofit2.http.GET
import retrofit2.http.Query

/**
 * API endpoints para Dashboard de Viabilidade
 * Extensão para ViabixApiService
 */
interface ViabilidadeApiService {
    @GET("api/dashboard_viabilidade_simple.php")
    suspend fun getDashboardViabilidade(
        @Query("anvi_id") anviId: String
    ): Response<DashboardViabilidadeResponse>
}

/**
 * Função auxiliar para obter o serviço de Viabilidade
 */
fun getViabilidadeApiService(): ViabilidadeApiService {
    return RetrofitClient.getApiService() as? ViabilidadeApiService
        ?: throw IllegalStateException("ViabilidadeApiService not available")
}
