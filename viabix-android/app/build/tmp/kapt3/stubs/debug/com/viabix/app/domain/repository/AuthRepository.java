package com.viabix.app.domain.repository;

import com.viabix.app.data.api.ViabixApiService;
import com.viabix.app.data.local.ViabixDatabase;
import com.viabix.app.domain.LoginRequest;
import com.viabix.app.domain.LoginResponse;
import com.viabix.app.utils.TokenManager;
import javax.inject.Inject;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u00004\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0002\b\u0003\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0010\u0002\n\u0000\u0018\u00002\u00020\u0001B\u001f\b\u0007\u0012\u0006\u0010\u0002\u001a\u00020\u0003\u0012\u0006\u0010\u0004\u001a\u00020\u0005\u0012\u0006\u0010\u0006\u001a\u00020\u0007\u00a2\u0006\u0002\u0010\bJ\u000e\u0010\t\u001a\u00020\nH\u0086@\u00a2\u0006\u0002\u0010\u000bJ\u0016\u0010\f\u001a\u00020\n2\u0006\u0010\r\u001a\u00020\u000eH\u0086@\u00a2\u0006\u0002\u0010\u000fJ\u000e\u0010\u0010\u001a\u00020\u0011H\u0086@\u00a2\u0006\u0002\u0010\u000bR\u000e\u0010\u0002\u001a\u00020\u0003X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0004\u001a\u00020\u0005X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0006\u001a\u00020\u0007X\u0082\u0004\u00a2\u0006\u0002\n\u0000\u00a8\u0006\u0012"}, d2 = {"Lcom/viabix/app/domain/repository/AuthRepository;", "", "apiService", "Lcom/viabix/app/data/api/ViabixApiService;", "database", "Lcom/viabix/app/data/local/ViabixDatabase;", "tokenManager", "Lcom/viabix/app/utils/TokenManager;", "(Lcom/viabix/app/data/api/ViabixApiService;Lcom/viabix/app/data/local/ViabixDatabase;Lcom/viabix/app/utils/TokenManager;)V", "checkSession", "Lcom/viabix/app/domain/LoginResponse;", "(Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "login", "request", "Lcom/viabix/app/domain/LoginRequest;", "(Lcom/viabix/app/domain/LoginRequest;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "logout", "", "app_debug"})
public final class AuthRepository {
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.data.api.ViabixApiService apiService = null;
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.data.local.ViabixDatabase database = null;
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.utils.TokenManager tokenManager = null;
    
    @javax.inject.Inject()
    public AuthRepository(@org.jetbrains.annotations.NotNull()
    com.viabix.app.data.api.ViabixApiService apiService, @org.jetbrains.annotations.NotNull()
    com.viabix.app.data.local.ViabixDatabase database, @org.jetbrains.annotations.NotNull()
    com.viabix.app.utils.TokenManager tokenManager) {
        super();
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object login(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.LoginRequest request, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.domain.LoginResponse> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object checkSession(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.domain.LoginResponse> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object logout(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion) {
        return null;
    }
}