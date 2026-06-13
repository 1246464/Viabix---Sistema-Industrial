package com.viabix.app.presentation.screens.login

import android.util.Log
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.viabix.app.data.api.RetrofitClient
import com.viabix.app.utils.TokenManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.update
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class LoginViewModel @Inject constructor(
    private val tokenManager: TokenManager
) : ViewModel() {

    private val _loginState = MutableStateFlow(LoginState())
    val loginState: StateFlow<LoginState> = _loginState.asStateFlow()

    init {
        checkExistingToken()
    }

    private fun checkExistingToken() {
        viewModelScope.launch {
            val token = tokenManager.getToken()
            Log.d("LoginViewModel", "Iniciando checkExistingToken. Token encontrado: ${token != null}")
            if (token != null) {
                _loginState.update { it.copy(isLoggedIn = true) }
            }
        }
    }

    fun login(email: String, password: String) {
        if (email.isBlank() || password.isBlank()) {
            _loginState.update { it.copy(error = "Login e senha são obrigatórios") }
            return
        }

        viewModelScope.launch {
            _loginState.update { it.copy(isLoading = true, error = null) }
            Log.d("LoginViewModel", "Tentando login para: $email")

            try {
                val apiService = RetrofitClient.getApiService()
                val response = apiService.login(email, password)

                Log.d("LoginViewModel", "Resposta recebida. Sucesso: ${response.isSuccessful}")

                if (response.isSuccessful) {
                    val body = response.body()
                    val token = body?.get("token") as? String
                    if (!token.isNullOrEmpty()) {
                        Log.d("LoginViewModel", "Token recebido com sucesso")
                        tokenManager.saveToken(token)
                        
                        val tenant = body["tenant"] as? Map<String, Any>
                        val tenantIdRaw = tenant?.get("id") ?: body["tenant_id"]
                        val tenantIdStr = when (tenantIdRaw) {
                            is String -> tenantIdRaw
                            is Double -> tenantIdRaw.toInt().toString()
                            null -> ""
                            else -> tenantIdRaw.toString()
                        }
                        if (tenantIdStr.isNotBlank() && tenantIdStr != "null") {
                            tokenManager.saveTenantId(tenantIdStr)
                        }

                        // Salvar dados do usuário
                        val user = body["user"] as? Map<String, Any>
                        val userName = user?.get("nome") as? String ?: "Usuário"
                        val userLogin = user?.get("login") as? String ?: email
                        val userLevel = user?.get("nivel") as? String ?: "user"
                        tokenManager.saveUserData(userName, userLogin, userLevel)

                        _loginState.update { it.copy(isLoading = false, isLoggedIn = true, error = null) }
                    } else {
                        Log.e("LoginViewModel", "Token nulo ou vazio na resposta")
                        _loginState.update { it.copy(isLoading = false, error = "Erro na resposta do servidor") }
                    }
                } else {
                    Log.e("LoginViewModel", "Erro no login: ${response.code()}")
                    _loginState.update { it.copy(isLoading = false, error = "Credenciais inválidas") }
                }
            } catch (e: Exception) {
                Log.e("LoginViewModel", "Exceção no login: ${e.message}", e)
                _loginState.update { it.copy(isLoading = false, error = "Falha na conexão") }
            }
        }
    }

    fun logout() {
        Log.d("LoginViewModel", "Executando logout no LoginViewModel")
        // Resetamos o estado síncronamente para refletir na UI imediatamente
        _loginState.update { it.copy(isLoggedIn = false, isLoading = false, error = null) }
        
        viewModelScope.launch {
            tokenManager.clearToken()
            Log.d("LoginViewModel", "Token e dados limpos do DataStore")
        }
    }
}

data class LoginState(
    val isLoggedIn: Boolean = false,
    val isLoading: Boolean = false,
    val error: String? = null
)
