package com.viabix.app.data.api;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000:\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0002\b\u0002\n\u0002\u0010\u000e\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0010#\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0010\u0002\n\u0000\b\u00c6\u0002\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002J\u0006\u0010\u000e\u001a\u00020\u000fJ\u000e\u0010\u0010\u001a\u00020\u00112\u0006\u0010\u0005\u001a\u00020\u0006R\u000e\u0010\u0003\u001a\u00020\u0004X\u0082T\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0005\u001a\u00020\u0006X\u0082.\u00a2\u0006\u0002\n\u0000R\u0014\u0010\u0007\u001a\b\u0012\u0004\u0012\u00020\t0\bX\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\n\u001a\u00020\u000bX\u0082.\u00a2\u0006\u0002\n\u0000R\u000e\u0010\f\u001a\u00020\rX\u0082.\u00a2\u0006\u0002\n\u0000\u00a8\u0006\u0012"}, d2 = {"Lcom/viabix/app/data/api/RetrofitClient;", "", "()V", "BASE_URL", "", "context", "Landroid/content/Context;", "cookieStore", "", "Lokhttp3/Cookie;", "retrofit", "Lretrofit2/Retrofit;", "tokenManager", "Lcom/viabix/app/utils/TokenManager;", "getApiService", "Lcom/viabix/app/data/api/ViabixApiServiceWithViabilidade;", "initialize", "", "app_debug"})
public final class RetrofitClient {
    @org.jetbrains.annotations.NotNull()
    private static final java.lang.String BASE_URL = "https://viabix.com.br/";
    private static retrofit2.Retrofit retrofit;
    private static com.viabix.app.utils.TokenManager tokenManager;
    private static android.content.Context context;
    @org.jetbrains.annotations.NotNull()
    private static final java.util.Set<okhttp3.Cookie> cookieStore = null;
    @org.jetbrains.annotations.NotNull()
    public static final com.viabix.app.data.api.RetrofitClient INSTANCE = null;
    
    private RetrofitClient() {
        super();
    }
    
    public final void initialize(@org.jetbrains.annotations.NotNull()
    android.content.Context context) {
    }
    
    @org.jetbrains.annotations.NotNull()
    public final com.viabix.app.data.api.ViabixApiServiceWithViabilidade getApiService() {
        return null;
    }
}