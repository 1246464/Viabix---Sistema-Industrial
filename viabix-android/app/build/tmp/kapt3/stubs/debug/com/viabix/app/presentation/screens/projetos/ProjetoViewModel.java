package com.viabix.app.presentation.screens.projetos;

import androidx.lifecycle.ViewModel;
import com.viabix.app.domain.ProjectEntity;
import com.viabix.app.domain.ProjectRequest;
import com.viabix.app.domain.repository.ProjetoRepository;
import com.viabix.app.utils.TokenManager;
import dagger.hilt.android.lifecycle.HiltViewModel;
import kotlinx.coroutines.flow.StateFlow;
import javax.inject.Inject;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000B\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0003\n\u0002\u0010\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0010\u000e\n\u0002\b\u0005\b\u0007\u0018\u00002\u00020\u0001B\u0017\b\u0007\u0012\u0006\u0010\u0002\u001a\u00020\u0003\u0012\u0006\u0010\u0004\u001a\u00020\u0005\u00a2\u0006\u0002\u0010\u0006J\u0006\u0010\u000e\u001a\u00020\u000fJ\u000e\u0010\u0010\u001a\u00020\u000f2\u0006\u0010\u0011\u001a\u00020\u0012J\u000e\u0010\u0013\u001a\u00020\u000f2\u0006\u0010\u0014\u001a\u00020\u0015J\u000e\u0010\u0016\u001a\u00020\u000f2\u0006\u0010\u0014\u001a\u00020\u0015J\u0010\u0010\u0017\u001a\u00020\u000f2\b\b\u0002\u0010\u0018\u001a\u00020\u0015J\u0016\u0010\u0019\u001a\u00020\u000f2\u0006\u0010\u0014\u001a\u00020\u00152\u0006\u0010\u0011\u001a\u00020\u0012R\u0014\u0010\u0007\u001a\b\u0012\u0004\u0012\u00020\t0\bX\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0002\u001a\u00020\u0003X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0004\u001a\u00020\u0005X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u0017\u0010\n\u001a\b\u0012\u0004\u0012\u00020\t0\u000b\u00a2\u0006\b\n\u0000\u001a\u0004\b\f\u0010\r\u00a8\u0006\u001a"}, d2 = {"Lcom/viabix/app/presentation/screens/projetos/ProjetoViewModel;", "Landroidx/lifecycle/ViewModel;", "projetoRepository", "Lcom/viabix/app/domain/repository/ProjetoRepository;", "tokenManager", "Lcom/viabix/app/utils/TokenManager;", "(Lcom/viabix/app/domain/repository/ProjetoRepository;Lcom/viabix/app/utils/TokenManager;)V", "_uiState", "Lkotlinx/coroutines/flow/MutableStateFlow;", "Lcom/viabix/app/presentation/screens/projetos/ProjetoUiState;", "uiState", "Lkotlinx/coroutines/flow/StateFlow;", "getUiState", "()Lkotlinx/coroutines/flow/StateFlow;", "clearMessages", "", "createProjeto", "request", "Lcom/viabix/app/domain/ProjectRequest;", "deleteProjeto", "projetoId", "", "loadProjetoDetail", "loadProjetos", "tenantId", "updateProjeto", "app_debug"})
@dagger.hilt.android.lifecycle.HiltViewModel()
public final class ProjetoViewModel extends androidx.lifecycle.ViewModel {
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.domain.repository.ProjetoRepository projetoRepository = null;
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.utils.TokenManager tokenManager = null;
    @org.jetbrains.annotations.NotNull()
    private final kotlinx.coroutines.flow.MutableStateFlow<com.viabix.app.presentation.screens.projetos.ProjetoUiState> _uiState = null;
    @org.jetbrains.annotations.NotNull()
    private final kotlinx.coroutines.flow.StateFlow<com.viabix.app.presentation.screens.projetos.ProjetoUiState> uiState = null;
    
    @javax.inject.Inject()
    public ProjetoViewModel(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.repository.ProjetoRepository projetoRepository, @org.jetbrains.annotations.NotNull()
    com.viabix.app.utils.TokenManager tokenManager) {
        super();
    }
    
    @org.jetbrains.annotations.NotNull()
    public final kotlinx.coroutines.flow.StateFlow<com.viabix.app.presentation.screens.projetos.ProjetoUiState> getUiState() {
        return null;
    }
    
    public final void loadProjetos(@org.jetbrains.annotations.NotNull()
    java.lang.String tenantId) {
    }
    
    public final void loadProjetoDetail(@org.jetbrains.annotations.NotNull()
    java.lang.String projetoId) {
    }
    
    public final void createProjeto(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.ProjectRequest request) {
    }
    
    public final void updateProjeto(@org.jetbrains.annotations.NotNull()
    java.lang.String projetoId, @org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.ProjectRequest request) {
    }
    
    public final void deleteProjeto(@org.jetbrains.annotations.NotNull()
    java.lang.String projetoId) {
    }
    
    public final void clearMessages() {
    }
}