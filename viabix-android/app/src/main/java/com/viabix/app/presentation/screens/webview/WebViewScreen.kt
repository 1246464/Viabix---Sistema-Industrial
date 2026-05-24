package com.viabix.app.presentation.screens.webview

import android.webkit.CookieManager
import android.webkit.JavascriptInterface
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.compose.foundation.layout.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.ArrowBack
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.hilt.navigation.compose.hiltViewModel
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.viabix.app.utils.TokenManager
import dagger.hilt.android.lifecycle.HiltViewModel
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch
import javax.inject.Inject

@HiltViewModel
class WebViewScreenViewModel @Inject constructor(
    private val tokenManager: TokenManager
) : ViewModel() {
    private val _token = MutableStateFlow<String?>(null)
    val token: StateFlow<String?> = _token

    init {
        loadToken()
    }

    private fun loadToken() {
        viewModelScope.launch {
            _token.value = tokenManager.getToken()
        }
    }
}

class TokenJavascriptInterface(private val token: String?) {
    @JavascriptInterface
    fun getToken(): String {
        return token ?: ""
    }
}

@Composable
fun WebViewScreen(
    viewModel: WebViewScreenViewModel = hiltViewModel(),
    title: String,
    url: String,
    onNavigateBack: () -> Unit
) {
    val token by viewModel.token.collectAsState()
    var webViewInstance by remember { mutableStateOf<WebView?>(null) }

    // Carregar URL APÓS token estar disponível
    LaunchedEffect(token, webViewInstance) {
        val currentToken = token
        if (currentToken != null && webViewInstance != null) {
            try {
                android.util.Log.d("WebViewScreen", "1. Injetando token ANTES de carregar página")
                
                // Sincronizar Cookie globalmente (funciona em HTTPS)
                val cookieManager = CookieManager.getInstance()
                cookieManager.setAcceptCookie(true)
                cookieManager.setCookie("https://viabix.com.br", "jwt_token=$currentToken; Path=/; Secure")
                cookieManager.flush()
                android.util.Log.d("WebViewScreen", "2. Cookie injetado: jwt_token")
                
                // Agora carregar a página COM token já definido
                webViewInstance?.loadUrl(url)
                android.util.Log.d("WebViewScreen", "3. URL carregada: $url")
            } catch (e: Exception) {
                android.util.Log.e("WebViewScreen", "Erro ao preparar WebView: ${e.message}")
            }
        }
    }

    Column(modifier = Modifier.fillMaxSize()) {
        // Header
        Surface(
            modifier = Modifier
                .fillMaxWidth()
                .height(56.dp),
            color = Color(0xFF165a38)
        ) {
            Row(
                modifier = Modifier
                    .fillMaxSize()
                    .padding(horizontal = 16.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                IconButton(onClick = onNavigateBack) {
                    Icon(
                        imageVector = Icons.Default.ArrowBack,
                        contentDescription = "Voltar",
                        tint = Color.White
                    )
                }
                Text(
                    text = title,
                    color = Color.White,
                    style = MaterialTheme.typography.titleMedium
                )
            }
        }

        // WebView
        AndroidView(
            modifier = Modifier.fillMaxSize(),
            factory = { context ->
                WebView(context).apply {
                    webViewClient = object : WebViewClient() {
                        override fun onPageFinished(view: WebView?, pageUrl: String?) {
                            super.onPageFinished(view, pageUrl)
                            android.util.Log.d("WebViewScreen", "4. Página carregada: $pageUrl")
                            
                            // Injetar fetch interceptor APÓS página carregar
                            token?.let { t ->
                                val tokenEscaped = t.replace("'", "\\'")
                                view?.evaluateJavascript(
                                    """javascript:(function() {
                                        try {
                                            window.viabixToken = '$tokenEscaped';
                                            console.log('5. Token reinjetado em window.viabixToken');
                                            
                                            // Interceptar fetch para adicionar Authorization header
                                            const originalFetch = window.fetch;
                                            window.fetch = function(...args) {
                                                let fetchUrl = args[0];
                                                let options = args[1] || {};
                                                if (!options.headers) options.headers = {};
                                                
                                                // Adicionar Authorization header automaticamente
                                                if (!options.headers['Authorization']) {
                                                    options.headers['Authorization'] = 'Bearer $tokenEscaped';
                                                    console.log('6. Authorization header adicionado para:', fetchUrl);
                                                }
                                                args[1] = options;
                                                return originalFetch.apply(this, args);
                                            };
                                            console.log('7. Fetch interceptor instalado com sucesso');
                                        } catch(err) {
                                            console.error('Erro ao injetar fetch interceptor:', err);
                                        }
                                    })();""".trimIndent(),
                                    null
                                )
                            }
                        }
                    }
                    settings.apply {
                        javaScriptEnabled = true
                        domStorageEnabled = true
                        databaseEnabled = true
                        useWideViewPort = true
                        loadWithOverviewMode = true
                        mixedContentMode = android.webkit.WebSettings.MIXED_CONTENT_ALWAYS_ALLOW
                    }
                    addJavascriptInterface(TokenJavascriptInterface(token), "ViabixApp")
                    webViewInstance = this
                    // NÃO carregar URL aqui - deixar que LaunchedEffect carregue APÓS token estar injetado
                }
            },
            update = { /* No-op */ }
        )
    }
}
