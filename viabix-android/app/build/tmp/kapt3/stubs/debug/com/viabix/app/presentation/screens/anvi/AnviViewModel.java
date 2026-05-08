package com.viabix.app.presentation.screens.anvi;

import androidx.lifecycle.ViewModel;
import com.viabix.app.domain.AnviEntity;
import com.viabix.app.domain.AnviRequest;
import com.viabix.app.domain.repository.AnviRepository;
import com.viabix.app.utils.TokenManager;
import dagger.hilt.android.lifecycle.HiltViewModel;
import kotlinx.coroutines.flow.StateFlow;
import javax.inject.Inject;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000J\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0003\n\u0002\u0010\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0010\u000e\n\u0002\b\u0003\b\u0007\u0018\u00002\u00020\u0001B\u0017\b\u0007\u0012\u0006\u0010\u0002\u001a\u00020\u0003\u0012\u0006\u0010\u0004\u001a\u00020\u0005\u00a2\u0006\u0002\u0010\u0006J\u0006\u0010\u000e\u001a\u00020\u000fJ\u000e\u0010\u0010\u001a\u00020\u000f2\u0006\u0010\u0011\u001a\u00020\u0012J\u000e\u0010\u0013\u001a\u00020\u000f2\u0006\u0010\u0014\u001a\u00020\u0015J\u000e\u0010\u0016\u001a\u00020\u000f2\u0006\u0010\u0017\u001a\u00020\u0018J\u0010\u0010\u0019\u001a\u00020\u000f2\b\b\u0002\u0010\u001a\u001a\u00020\u0018R\u0014\u0010\u0007\u001a\b\u0012\u0004\u0012\u00020\t0\bX\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0002\u001a\u00020\u0003X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0004\u001a\u00020\u0005X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u0017\u0010\n\u001a\b\u0012\u0004\u0012\u00020\t0\u000b\u00a2\u0006\b\n\u0000\u001a\u0004\b\f\u0010\r\u00a8\u0006\u001b"}, d2 = {"Lcom/viabix/app/presentation/screens/anvi/AnviViewModel;", "Landroidx/lifecycle/ViewModel;", "anviRepository", "Lcom/viabix/app/domain/repository/AnviRepository;", "tokenManager", "Lcom/viabix/app/utils/TokenManager;", "(Lcom/viabix/app/domain/repository/AnviRepository;Lcom/viabix/app/utils/TokenManager;)V", "_uiState", "Lkotlinx/coroutines/flow/MutableStateFlow;", "Lcom/viabix/app/presentation/screens/anvi/AnviUiState;", "uiState", "Lkotlinx/coroutines/flow/StateFlow;", "getUiState", "()Lkotlinx/coroutines/flow/StateFlow;", "clearMessages", "", "createAnvi", "request", "Lcom/viabix/app/domain/AnviRequest;", "deleteAnvi", "anvi", "Lcom/viabix/app/domain/AnviEntity;", "loadAnviDetail", "anviId", "", "loadAnvis", "tenantId", "app_debug"})
@dagger.hilt.android.lifecycle.HiltViewModel()
public final class AnviViewModel extends androidx.lifecycle.ViewModel {
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.domain.repository.AnviRepository anviRepository = null;
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.utils.TokenManager tokenManager = null;
    @org.jetbrains.annotations.NotNull()
    private final kotlinx.coroutines.flow.MutableStateFlow<com.viabix.app.presentation.screens.anvi.AnviUiState> _uiState = null;
    @org.jetbrains.annotations.NotNull()
    private final kotlinx.coroutines.flow.StateFlow<com.viabix.app.presentation.screens.anvi.AnviUiState> uiState = null;
    
    @javax.inject.Inject()
    public AnviViewModel(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.repository.AnviRepository anviRepository, @org.jetbrains.annotations.NotNull()
    com.viabix.app.utils.TokenManager tokenManager) {
        super();
    }
    
    @org.jetbrains.annotations.NotNull()
    public final kotlinx.coroutines.flow.StateFlow<com.viabix.app.presentation.screens.anvi.AnviUiState> getUiState() {
        return null;
    }
    
    public final void loadAnvis(@org.jetbrains.annotations.NotNull()
    java.lang.String tenantId) {
    }
    
    public final void loadAnviDetail(@org.jetbrains.annotations.NotNull()
    java.lang.String anviId) {
    }
    
    public final void createAnvi(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.AnviRequest request) {
    }
    
    public final void deleteAnvi(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.AnviEntity anvi) {
    }
    
    public final void clearMessages() {
    }
}