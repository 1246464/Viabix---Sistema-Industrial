package com.viabix.app.presentation.screens.viabilidade

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.viabix.app.data.api.RetrofitClient
import com.viabix.app.utils.TokenManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class ViabilidadeViewModel @Inject constructor(
    private val tokenManager: TokenManager
) : ViewModel() {

    private val _uiState = MutableStateFlow(ViabilidadeUiState())
    val uiState: StateFlow<ViabilidadeUiState> = _uiState

    fun loadViabilidadeData(anviId: String) {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(
                isLoading = true,
                error = null,
                anviId = anviId
            )

            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.getDashboardViabilidade(anviId)

                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null) {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            anviData = body.anvi,
                            analiseData = body.analise,
                            viabilidadeData = body.viabilidade,
                            compatibilidades = body.compatibilidades
                        )
                    } else {
                        _uiState.value = _uiState.value.copy(
                            isLoading = false,
                            error = "Dados inválidos recebidos"
                        )
                    }
                } else {
                    _uiState.value = _uiState.value.copy(
                        isLoading = false,
                        error = "Erro ao carregar dados: ${response.code()}"
                    )
                }
            } catch (e: Exception) {
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = e.message ?: "Erro desconhecido"
                )
            }
        }
    }

    fun clearError() {
        _uiState.value = _uiState.value.copy(error = null)
    }
}

// Response data class para parsear a resposta da API
data class DashboardViabilidadeResponse(
    val anvi: AnviData,
    val analise: AnaliseData,
    val viabilidade: ViabilidadeData,
    val compatibilidades: List<Compatibilidade>
)
