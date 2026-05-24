package com.viabix.app.utils;

@javax.inject.Singleton()
@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\"\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0010\u0002\n\u0002\b\u0002\n\u0002\u0010\u000e\n\u0002\b\u0011\b\u0007\u0018\u0000 \u00192\u00020\u0001:\u0001\u0019B\u0011\b\u0007\u0012\b\b\u0001\u0010\u0002\u001a\u00020\u0003\u00a2\u0006\u0002\u0010\u0004J\u000e\u0010\u0005\u001a\u00020\u0006H\u0086@\u00a2\u0006\u0002\u0010\u0007J\u0010\u0010\b\u001a\u0004\u0018\u00010\tH\u0086@\u00a2\u0006\u0002\u0010\u0007J\u0010\u0010\n\u001a\u0004\u0018\u00010\tH\u0086@\u00a2\u0006\u0002\u0010\u0007J\b\u0010\u000b\u001a\u0004\u0018\u00010\tJ\u0010\u0010\f\u001a\u0004\u0018\u00010\tH\u0086@\u00a2\u0006\u0002\u0010\u0007J\u0010\u0010\r\u001a\u0004\u0018\u00010\tH\u0086@\u00a2\u0006\u0002\u0010\u0007J\u0010\u0010\u000e\u001a\u0004\u0018\u00010\tH\u0086@\u00a2\u0006\u0002\u0010\u0007J\u0016\u0010\u000f\u001a\u00020\u00062\u0006\u0010\u0010\u001a\u00020\tH\u0086@\u00a2\u0006\u0002\u0010\u0011J\u0016\u0010\u0012\u001a\u00020\u00062\u0006\u0010\u0013\u001a\u00020\tH\u0086@\u00a2\u0006\u0002\u0010\u0011J&\u0010\u0014\u001a\u00020\u00062\u0006\u0010\u0015\u001a\u00020\t2\u0006\u0010\u0016\u001a\u00020\t2\u0006\u0010\u0017\u001a\u00020\tH\u0086@\u00a2\u0006\u0002\u0010\u0018R\u000e\u0010\u0002\u001a\u00020\u0003X\u0082\u0004\u00a2\u0006\u0002\n\u0000\u00a8\u0006\u001a"}, d2 = {"Lcom/viabix/app/utils/TokenManager;", "", "context", "Landroid/content/Context;", "(Landroid/content/Context;)V", "clearToken", "", "(Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "getTenantId", "", "getToken", "getTokenSync", "getUserLevel", "getUserLogin", "getUserName", "saveTenantId", "tenantId", "(Ljava/lang/String;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "saveToken", "token", "saveUserData", "name", "login", "level", "(Ljava/lang/String;Ljava/lang/String;Ljava/lang/String;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "Companion", "app_debug"})
public final class TokenManager {
    @org.jetbrains.annotations.NotNull()
    private final android.content.Context context = null;
    @org.jetbrains.annotations.NotNull()
    private static final androidx.datastore.preferences.core.Preferences.Key<java.lang.String> TOKEN_KEY = null;
    @org.jetbrains.annotations.NotNull()
    private static final androidx.datastore.preferences.core.Preferences.Key<java.lang.String> TENANT_ID_KEY = null;
    @org.jetbrains.annotations.NotNull()
    private static final androidx.datastore.preferences.core.Preferences.Key<java.lang.String> USER_NAME_KEY = null;
    @org.jetbrains.annotations.NotNull()
    private static final androidx.datastore.preferences.core.Preferences.Key<java.lang.String> USER_LOGIN_KEY = null;
    @org.jetbrains.annotations.NotNull()
    private static final androidx.datastore.preferences.core.Preferences.Key<java.lang.String> USER_LEVEL_KEY = null;
    @org.jetbrains.annotations.NotNull()
    public static final com.viabix.app.utils.TokenManager.Companion Companion = null;
    
    @javax.inject.Inject()
    public TokenManager(@dagger.hilt.android.qualifiers.ApplicationContext()
    @org.jetbrains.annotations.NotNull()
    android.content.Context context) {
        super();
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object saveToken(@org.jetbrains.annotations.NotNull()
    java.lang.String token, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object getToken(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.lang.String> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.String getTokenSync() {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object saveTenantId(@org.jetbrains.annotations.NotNull()
    java.lang.String tenantId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object getTenantId(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.lang.String> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object saveUserData(@org.jetbrains.annotations.NotNull()
    java.lang.String name, @org.jetbrains.annotations.NotNull()
    java.lang.String login, @org.jetbrains.annotations.NotNull()
    java.lang.String level, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object getUserName(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.lang.String> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object getUserLogin(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.lang.String> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object getUserLevel(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.lang.String> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object clearToken(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion) {
        return null;
    }
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\u0018\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0002\u0010\u000e\n\u0002\b\u0005\b\u0086\u0003\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002R\u0014\u0010\u0003\u001a\b\u0012\u0004\u0012\u00020\u00050\u0004X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u0014\u0010\u0006\u001a\b\u0012\u0004\u0012\u00020\u00050\u0004X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u0014\u0010\u0007\u001a\b\u0012\u0004\u0012\u00020\u00050\u0004X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u0014\u0010\b\u001a\b\u0012\u0004\u0012\u00020\u00050\u0004X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u0014\u0010\t\u001a\b\u0012\u0004\u0012\u00020\u00050\u0004X\u0082\u0004\u00a2\u0006\u0002\n\u0000\u00a8\u0006\n"}, d2 = {"Lcom/viabix/app/utils/TokenManager$Companion;", "", "()V", "TENANT_ID_KEY", "Landroidx/datastore/preferences/core/Preferences$Key;", "", "TOKEN_KEY", "USER_LEVEL_KEY", "USER_LOGIN_KEY", "USER_NAME_KEY", "app_debug"})
    public static final class Companion {
        
        private Companion() {
            super();
        }
    }
}