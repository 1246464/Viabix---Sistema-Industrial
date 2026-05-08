package com.viabix.app.utils;

import android.webkit.JavascriptInterface;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\"\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0010\u000e\n\u0002\b\u0002\n\u0002\u0010\u0002\n\u0002\b\u0002\u0018\u00002\u00020\u0001B\r\u0012\u0006\u0010\u0002\u001a\u00020\u0003\u00a2\u0006\u0002\u0010\u0004J\b\u0010\u0005\u001a\u00020\u0006H\u0007J\b\u0010\u0007\u001a\u00020\u0006H\u0007J\u0010\u0010\b\u001a\u00020\t2\u0006\u0010\n\u001a\u00020\u0006H\u0007R\u000e\u0010\u0002\u001a\u00020\u0003X\u0082\u0004\u00a2\u0006\u0002\n\u0000\u00a8\u0006\u000b"}, d2 = {"Lcom/viabix/app/utils/WebViewJavascriptInterface;", "", "tokenManager", "Lcom/viabix/app/utils/TokenManager;", "(Lcom/viabix/app/utils/TokenManager;)V", "getAuthToken", "", "getTenantId", "log", "", "message", "app_debug"})
public final class WebViewJavascriptInterface {
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.utils.TokenManager tokenManager = null;
    
    public WebViewJavascriptInterface(@org.jetbrains.annotations.NotNull()
    com.viabix.app.utils.TokenManager tokenManager) {
        super();
    }
    
    /**
     * Obtém o token de autenticação para ser usado no WebView
     */
    @android.webkit.JavascriptInterface()
    @org.jetbrains.annotations.NotNull()
    public final java.lang.String getAuthToken() {
        return null;
    }
    
    /**
     * Obtém o tenant ID do usuário
     */
    @android.webkit.JavascriptInterface()
    @org.jetbrains.annotations.NotNull()
    public final java.lang.String getTenantId() {
        return null;
    }
    
    /**
     * Log from WebView
     */
    @android.webkit.JavascriptInterface()
    public final void log(@org.jetbrains.annotations.NotNull()
    java.lang.String message) {
    }
}