package com.viabix.app.domain.repository;

import com.viabix.app.data.api.ViabixApiService;
import com.viabix.app.data.local.ViabixDatabase;
import com.viabix.app.domain.AnviEntity;
import com.viabix.app.domain.AnviRequest;
import kotlinx.coroutines.flow.Flow;
import javax.inject.Inject;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000R\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0010\u000b\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0010\u0002\n\u0000\n\u0002\u0010\u000e\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\u0010 \n\u0002\b\u0005\u0018\u00002\u00020\u0001B\u001f\b\u0007\u0012\u0006\u0010\u0002\u001a\u00020\u0003\u0012\u0006\u0010\u0004\u001a\u00020\u0005\u0012\u0006\u0010\u0006\u001a\u00020\u0007\u00a2\u0006\u0002\u0010\bJ\u0016\u0010\u000b\u001a\u00020\f2\u0006\u0010\r\u001a\u00020\u000eH\u0086@\u00a2\u0006\u0002\u0010\u000fJ\u0016\u0010\u0010\u001a\u00020\u00112\u0006\u0010\u0012\u001a\u00020\u0013H\u0086@\u00a2\u0006\u0002\u0010\u0014J\u0018\u0010\u0015\u001a\u0004\u0018\u00010\u00162\u0006\u0010\u0012\u001a\u00020\u0013H\u0086@\u00a2\u0006\u0002\u0010\u0014J\u001a\u0010\u0017\u001a\u000e\u0012\n\u0012\b\u0012\u0004\u0012\u00020\u00160\u00190\u00182\u0006\u0010\u001a\u001a\u00020\u0013J\u001c\u0010\u001b\u001a\b\u0012\u0004\u0012\u00020\u00160\u00192\u0006\u0010\u001a\u001a\u00020\u0013H\u0086@\u00a2\u0006\u0002\u0010\u0014J\u001e\u0010\u001c\u001a\u00020\f2\u0006\u0010\u0012\u001a\u00020\u00132\u0006\u0010\r\u001a\u00020\u000eH\u0086@\u00a2\u0006\u0002\u0010\u001dR\u000e\u0010\t\u001a\u00020\nX\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0002\u001a\u00020\u0003X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0004\u001a\u00020\u0005X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0006\u001a\u00020\u0007X\u0082\u0004\u00a2\u0006\u0002\n\u0000\u00a8\u0006\u001e"}, d2 = {"Lcom/viabix/app/domain/repository/AnviRepository;", "", "apiService", "Lcom/viabix/app/data/api/ViabixApiService;", "database", "Lcom/viabix/app/data/local/ViabixDatabase;", "tokenManager", "Lcom/viabix/app/utils/TokenManager;", "(Lcom/viabix/app/data/api/ViabixApiService;Lcom/viabix/app/data/local/ViabixDatabase;Lcom/viabix/app/utils/TokenManager;)V", "anviDao", "Lcom/viabix/app/data/local/AnviDao;", "createAnvi", "", "request", "Lcom/viabix/app/domain/AnviRequest;", "(Lcom/viabix/app/domain/AnviRequest;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "deleteAnvi", "", "anviId", "", "(Ljava/lang/String;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "getAnviDetail", "Lcom/viabix/app/domain/AnviEntity;", "getAnvisByTenant", "Lkotlinx/coroutines/flow/Flow;", "", "tenantId", "syncAnvis", "updateAnvi", "(Ljava/lang/String;Lcom/viabix/app/domain/AnviRequest;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "app_debug"})
public final class AnviRepository {
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.data.api.ViabixApiService apiService = null;
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.data.local.ViabixDatabase database = null;
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.utils.TokenManager tokenManager = null;
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.data.local.AnviDao anviDao = null;
    
    @javax.inject.Inject()
    public AnviRepository(@org.jetbrains.annotations.NotNull()
    com.viabix.app.data.api.ViabixApiService apiService, @org.jetbrains.annotations.NotNull()
    com.viabix.app.data.local.ViabixDatabase database, @org.jetbrains.annotations.NotNull()
    com.viabix.app.utils.TokenManager tokenManager) {
        super();
    }
    
    @org.jetbrains.annotations.NotNull()
    public final kotlinx.coroutines.flow.Flow<java.util.List<com.viabix.app.domain.AnviEntity>> getAnvisByTenant(@org.jetbrains.annotations.NotNull()
    java.lang.String tenantId) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object syncAnvis(@org.jetbrains.annotations.NotNull()
    java.lang.String tenantId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.util.List<com.viabix.app.domain.AnviEntity>> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object getAnviDetail(@org.jetbrains.annotations.NotNull()
    java.lang.String anviId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.domain.AnviEntity> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object createAnvi(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.AnviRequest request, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.lang.Boolean> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object updateAnvi(@org.jetbrains.annotations.NotNull()
    java.lang.String anviId, @org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.AnviRequest request, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.lang.Boolean> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object deleteAnvi(@org.jetbrains.annotations.NotNull()
    java.lang.String anviId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion) {
        return null;
    }
}