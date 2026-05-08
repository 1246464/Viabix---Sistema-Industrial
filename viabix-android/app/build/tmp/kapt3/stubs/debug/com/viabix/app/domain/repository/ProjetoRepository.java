package com.viabix.app.domain.repository;

import com.viabix.app.data.api.ViabixApiService;
import com.viabix.app.data.local.ViabixDatabase;
import com.viabix.app.domain.ProjectEntity;
import com.viabix.app.domain.ProjectRequest;
import com.viabix.app.utils.TokenManager;
import javax.inject.Inject;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000N\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0010\u000b\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0010\u0002\n\u0000\n\u0002\u0010\u000e\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0010 \n\u0002\b\u0004\u0018\u00002\u00020\u0001B\u001f\b\u0007\u0012\u0006\u0010\u0002\u001a\u00020\u0003\u0012\u0006\u0010\u0004\u001a\u00020\u0005\u0012\u0006\u0010\u0006\u001a\u00020\u0007\u00a2\u0006\u0002\u0010\bJ\u0016\u0010\u000b\u001a\u00020\f2\u0006\u0010\r\u001a\u00020\u000eH\u0086@\u00a2\u0006\u0002\u0010\u000fJ\u0016\u0010\u0010\u001a\u00020\u00112\u0006\u0010\u0012\u001a\u00020\u0013H\u0086@\u00a2\u0006\u0002\u0010\u0014J\u0018\u0010\u0015\u001a\u0004\u0018\u00010\u00162\u0006\u0010\u0012\u001a\u00020\u0013H\u0086@\u00a2\u0006\u0002\u0010\u0014J\u001c\u0010\u0017\u001a\b\u0012\u0004\u0012\u00020\u00160\u00182\u0006\u0010\u0019\u001a\u00020\u0013H\u0086@\u00a2\u0006\u0002\u0010\u0014J\u001e\u0010\u001a\u001a\u00020\f2\u0006\u0010\u0012\u001a\u00020\u00132\u0006\u0010\r\u001a\u00020\u000eH\u0086@\u00a2\u0006\u0002\u0010\u001bR\u000e\u0010\u0002\u001a\u00020\u0003X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0004\u001a\u00020\u0005X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\t\u001a\u00020\nX\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0006\u001a\u00020\u0007X\u0082\u0004\u00a2\u0006\u0002\n\u0000\u00a8\u0006\u001c"}, d2 = {"Lcom/viabix/app/domain/repository/ProjetoRepository;", "", "apiService", "Lcom/viabix/app/data/api/ViabixApiService;", "database", "Lcom/viabix/app/data/local/ViabixDatabase;", "tokenManager", "Lcom/viabix/app/utils/TokenManager;", "(Lcom/viabix/app/data/api/ViabixApiService;Lcom/viabix/app/data/local/ViabixDatabase;Lcom/viabix/app/utils/TokenManager;)V", "projectDao", "Lcom/viabix/app/data/local/ProjectDao;", "createProjeto", "", "request", "Lcom/viabix/app/domain/ProjectRequest;", "(Lcom/viabix/app/domain/ProjectRequest;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "deleteProjeto", "", "projetoId", "", "(Ljava/lang/String;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "getProjetoDetail", "Lcom/viabix/app/domain/ProjectEntity;", "syncProjetos", "", "tenantId", "updateProjeto", "(Ljava/lang/String;Lcom/viabix/app/domain/ProjectRequest;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "app_debug"})
public final class ProjetoRepository {
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.data.api.ViabixApiService apiService = null;
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.data.local.ViabixDatabase database = null;
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.utils.TokenManager tokenManager = null;
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.data.local.ProjectDao projectDao = null;
    
    @javax.inject.Inject()
    public ProjetoRepository(@org.jetbrains.annotations.NotNull()
    com.viabix.app.data.api.ViabixApiService apiService, @org.jetbrains.annotations.NotNull()
    com.viabix.app.data.local.ViabixDatabase database, @org.jetbrains.annotations.NotNull()
    com.viabix.app.utils.TokenManager tokenManager) {
        super();
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object syncProjetos(@org.jetbrains.annotations.NotNull()
    java.lang.String tenantId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.util.List<com.viabix.app.domain.ProjectEntity>> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object getProjetoDetail(@org.jetbrains.annotations.NotNull()
    java.lang.String projetoId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.domain.ProjectEntity> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object createProjeto(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.ProjectRequest request, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.lang.Boolean> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object updateProjeto(@org.jetbrains.annotations.NotNull()
    java.lang.String projetoId, @org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.ProjectRequest request, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.lang.Boolean> $completion) {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final java.lang.Object deleteProjeto(@org.jetbrains.annotations.NotNull()
    java.lang.String projetoId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion) {
        return null;
    }
}