package com.viabix.app.presentation.screens.auth;

import androidx.lifecycle.ViewModel;
import com.viabix.app.domain.LoginRequest;
import com.viabix.app.domain.User;
import com.viabix.app.domain.Subscription;
import com.viabix.app.domain.repository.AuthRepository;
import dagger.hilt.android.lifecycle.HiltViewModel;
import kotlinx.coroutines.flow.StateFlow;
import javax.inject.Inject;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000.\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0000\n\u0002\u0010\u000b\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0010\u000e\n\u0002\b\u0014\n\u0002\u0010\b\n\u0002\b\u0002\b\u0086\b\u0018\u00002\u00020\u0001BE\u0012\b\b\u0002\u0010\u0002\u001a\u00020\u0003\u0012\b\b\u0002\u0010\u0004\u001a\u00020\u0003\u0012\n\b\u0002\u0010\u0005\u001a\u0004\u0018\u00010\u0006\u0012\n\b\u0002\u0010\u0007\u001a\u0004\u0018\u00010\b\u0012\b\b\u0002\u0010\t\u001a\u00020\n\u0012\b\b\u0002\u0010\u000b\u001a\u00020\n\u00a2\u0006\u0002\u0010\fJ\t\u0010\u0015\u001a\u00020\u0003H\u00c6\u0003J\t\u0010\u0016\u001a\u00020\u0003H\u00c6\u0003J\u000b\u0010\u0017\u001a\u0004\u0018\u00010\u0006H\u00c6\u0003J\u000b\u0010\u0018\u001a\u0004\u0018\u00010\bH\u00c6\u0003J\t\u0010\u0019\u001a\u00020\nH\u00c6\u0003J\t\u0010\u001a\u001a\u00020\nH\u00c6\u0003JI\u0010\u001b\u001a\u00020\u00002\b\b\u0002\u0010\u0002\u001a\u00020\u00032\b\b\u0002\u0010\u0004\u001a\u00020\u00032\n\b\u0002\u0010\u0005\u001a\u0004\u0018\u00010\u00062\n\b\u0002\u0010\u0007\u001a\u0004\u0018\u00010\b2\b\b\u0002\u0010\t\u001a\u00020\n2\b\b\u0002\u0010\u000b\u001a\u00020\nH\u00c6\u0001J\u0013\u0010\u001c\u001a\u00020\u00032\b\u0010\u001d\u001a\u0004\u0018\u00010\u0001H\u00d6\u0003J\t\u0010\u001e\u001a\u00020\u001fH\u00d6\u0001J\t\u0010 \u001a\u00020\nH\u00d6\u0001R\u0013\u0010\u0005\u001a\u0004\u0018\u00010\u0006\u00a2\u0006\b\n\u0000\u001a\u0004\b\r\u0010\u000eR\u0011\u0010\t\u001a\u00020\n\u00a2\u0006\b\n\u0000\u001a\u0004\b\u000f\u0010\u0010R\u0011\u0010\u0002\u001a\u00020\u0003\u00a2\u0006\b\n\u0000\u001a\u0004\b\u0002\u0010\u0011R\u0011\u0010\u0004\u001a\u00020\u0003\u00a2\u0006\b\n\u0000\u001a\u0004\b\u0004\u0010\u0011R\u0013\u0010\u0007\u001a\u0004\u0018\u00010\b\u00a2\u0006\b\n\u0000\u001a\u0004\b\u0012\u0010\u0013R\u0011\u0010\u000b\u001a\u00020\n\u00a2\u0006\b\n\u0000\u001a\u0004\b\u0014\u0010\u0010\u00a8\u0006!"}, d2 = {"Lcom/viabix/app/presentation/screens/auth/AuthUiState;", "", "isLoading", "", "isLoggedIn", "currentUser", "Lcom/viabix/app/domain/User;", "subscription", "Lcom/viabix/app/domain/Subscription;", "errorMessage", "", "successMessage", "(ZZLcom/viabix/app/domain/User;Lcom/viabix/app/domain/Subscription;Ljava/lang/String;Ljava/lang/String;)V", "getCurrentUser", "()Lcom/viabix/app/domain/User;", "getErrorMessage", "()Ljava/lang/String;", "()Z", "getSubscription", "()Lcom/viabix/app/domain/Subscription;", "getSuccessMessage", "component1", "component2", "component3", "component4", "component5", "component6", "copy", "equals", "other", "hashCode", "", "toString", "app_debug"})
public final class AuthUiState {
    private final boolean isLoading = false;
    private final boolean isLoggedIn = false;
    @org.jetbrains.annotations.Nullable()
    private final com.viabix.app.domain.User currentUser = null;
    @org.jetbrains.annotations.Nullable()
    private final com.viabix.app.domain.Subscription subscription = null;
    @org.jetbrains.annotations.NotNull()
    private final java.lang.String errorMessage = null;
    @org.jetbrains.annotations.NotNull()
    private final java.lang.String successMessage = null;
    
    public AuthUiState(boolean isLoading, boolean isLoggedIn, @org.jetbrains.annotations.Nullable()
    com.viabix.app.domain.User currentUser, @org.jetbrains.annotations.Nullable()
    com.viabix.app.domain.Subscription subscription, @org.jetbrains.annotations.NotNull()
    java.lang.String errorMessage, @org.jetbrains.annotations.NotNull()
    java.lang.String successMessage) {
        super();
    }
    
    public final boolean isLoading() {
        return false;
    }
    
    public final boolean isLoggedIn() {
        return false;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final com.viabix.app.domain.User getCurrentUser() {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final com.viabix.app.domain.Subscription getSubscription() {
        return null;
    }
    
    @org.jetbrains.annotations.NotNull()
    public final java.lang.String getErrorMessage() {
        return null;
    }
    
    @org.jetbrains.annotations.NotNull()
    public final java.lang.String getSuccessMessage() {
        return null;
    }
    
    public AuthUiState() {
        super();
    }
    
    public final boolean component1() {
        return false;
    }
    
    public final boolean component2() {
        return false;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final com.viabix.app.domain.User component3() {
        return null;
    }
    
    @org.jetbrains.annotations.Nullable()
    public final com.viabix.app.domain.Subscription component4() {
        return null;
    }
    
    @org.jetbrains.annotations.NotNull()
    public final java.lang.String component5() {
        return null;
    }
    
    @org.jetbrains.annotations.NotNull()
    public final java.lang.String component6() {
        return null;
    }
    
    @org.jetbrains.annotations.NotNull()
    public final com.viabix.app.presentation.screens.auth.AuthUiState copy(boolean isLoading, boolean isLoggedIn, @org.jetbrains.annotations.Nullable()
    com.viabix.app.domain.User currentUser, @org.jetbrains.annotations.Nullable()
    com.viabix.app.domain.Subscription subscription, @org.jetbrains.annotations.NotNull()
    java.lang.String errorMessage, @org.jetbrains.annotations.NotNull()
    java.lang.String successMessage) {
        return null;
    }
    
    @java.lang.Override()
    public boolean equals(@org.jetbrains.annotations.Nullable()
    java.lang.Object other) {
        return false;
    }
    
    @java.lang.Override()
    public int hashCode() {
        return 0;
    }
    
    @java.lang.Override()
    @org.jetbrains.annotations.NotNull()
    public java.lang.String toString() {
        return null;
    }
}