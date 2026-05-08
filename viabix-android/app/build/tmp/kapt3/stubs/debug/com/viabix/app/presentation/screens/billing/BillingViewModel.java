package com.viabix.app.presentation.screens.billing;

import androidx.lifecycle.ViewModel;
import com.viabix.app.domain.repository.AuthRepository;
import com.viabix.app.utils.TokenManager;
import dagger.hilt.android.lifecycle.HiltViewModel;
import kotlinx.coroutines.flow.StateFlow;
import java.text.SimpleDateFormat;
import java.util.*;
import javax.inject.Inject;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u00008\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0003\n\u0002\u0010\u000e\n\u0002\b\u0002\n\u0002\u0010\u0002\n\u0000\b\u0007\u0018\u00002\u00020\u0001B\u0017\b\u0007\u0012\u0006\u0010\u0002\u001a\u00020\u0003\u0012\u0006\u0010\u0004\u001a\u00020\u0005\u00a2\u0006\u0002\u0010\u0006J\u0010\u0010\u000e\u001a\u00020\u000f2\u0006\u0010\u0010\u001a\u00020\u000fH\u0002J\u0006\u0010\u0011\u001a\u00020\u0012R\u0014\u0010\u0007\u001a\b\u0012\u0004\u0012\u00020\t0\bX\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0004\u001a\u00020\u0005X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u000e\u0010\u0002\u001a\u00020\u0003X\u0082\u0004\u00a2\u0006\u0002\n\u0000R\u0017\u0010\n\u001a\b\u0012\u0004\u0012\u00020\t0\u000b\u00a2\u0006\b\n\u0000\u001a\u0004\b\f\u0010\r\u00a8\u0006\u0013"}, d2 = {"Lcom/viabix/app/presentation/screens/billing/BillingViewModel;", "Landroidx/lifecycle/ViewModel;", "tokenManager", "Lcom/viabix/app/utils/TokenManager;", "authRepository", "Lcom/viabix/app/domain/repository/AuthRepository;", "(Lcom/viabix/app/utils/TokenManager;Lcom/viabix/app/domain/repository/AuthRepository;)V", "_uiState", "Lkotlinx/coroutines/flow/MutableStateFlow;", "Lcom/viabix/app/presentation/screens/billing/BillingUiState;", "uiState", "Lkotlinx/coroutines/flow/StateFlow;", "getUiState", "()Lkotlinx/coroutines/flow/StateFlow;", "calcularProximaRenovacao", "", "ciclo", "loadBillingData", "", "app_debug"})
@dagger.hilt.android.lifecycle.HiltViewModel()
public final class BillingViewModel extends androidx.lifecycle.ViewModel {
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.utils.TokenManager tokenManager = null;
    @org.jetbrains.annotations.NotNull()
    private final com.viabix.app.domain.repository.AuthRepository authRepository = null;
    @org.jetbrains.annotations.NotNull()
    private final kotlinx.coroutines.flow.MutableStateFlow<com.viabix.app.presentation.screens.billing.BillingUiState> _uiState = null;
    @org.jetbrains.annotations.NotNull()
    private final kotlinx.coroutines.flow.StateFlow<com.viabix.app.presentation.screens.billing.BillingUiState> uiState = null;
    
    @javax.inject.Inject()
    public BillingViewModel(@org.jetbrains.annotations.NotNull()
    com.viabix.app.utils.TokenManager tokenManager, @org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.repository.AuthRepository authRepository) {
        super();
    }
    
    @org.jetbrains.annotations.NotNull()
    public final kotlinx.coroutines.flow.StateFlow<com.viabix.app.presentation.screens.billing.BillingUiState> getUiState() {
        return null;
    }
    
    public final void loadBillingData() {
    }
    
    private final java.lang.String calcularProximaRenovacao(java.lang.String ciclo) {
        return null;
    }
}