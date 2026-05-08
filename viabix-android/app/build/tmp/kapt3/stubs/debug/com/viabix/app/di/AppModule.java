package com.viabix.app.di;

import android.content.Context;
import com.viabix.app.data.api.RetrofitClient;
import com.viabix.app.data.api.ViabixApiService;
import com.viabix.app.data.local.ViabixDatabase;
import com.viabix.app.domain.repository.AnviRepository;
import com.viabix.app.domain.repository.AuthRepository;
import com.viabix.app.domain.repository.ProjetoRepository;
import com.viabix.app.utils.TokenManager;
import dagger.Module;
import dagger.Provides;
import dagger.hilt.InstallIn;
import dagger.hilt.android.qualifiers.ApplicationContext;
import dagger.hilt.components.SingletonComponent;
import javax.inject.Singleton;

@dagger.Module()
@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000:\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0003\b\u00c7\u0002\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002J \u0010\u0003\u001a\u00020\u00042\u0006\u0010\u0005\u001a\u00020\u00062\u0006\u0010\u0007\u001a\u00020\b2\u0006\u0010\t\u001a\u00020\nH\u0007J \u0010\u000b\u001a\u00020\f2\u0006\u0010\u0005\u001a\u00020\u00062\u0006\u0010\u0007\u001a\u00020\b2\u0006\u0010\t\u001a\u00020\nH\u0007J\u0012\u0010\r\u001a\u00020\b2\b\b\u0001\u0010\u000e\u001a\u00020\u000fH\u0007J \u0010\u0010\u001a\u00020\u00112\u0006\u0010\u0005\u001a\u00020\u00062\u0006\u0010\u0007\u001a\u00020\b2\u0006\u0010\t\u001a\u00020\nH\u0007J\u0012\u0010\u0012\u001a\u00020\n2\b\b\u0001\u0010\u000e\u001a\u00020\u000fH\u0007J\u001a\u0010\u0013\u001a\u00020\u00062\b\b\u0001\u0010\u000e\u001a\u00020\u000f2\u0006\u0010\t\u001a\u00020\nH\u0007\u00a8\u0006\u0014"}, d2 = {"Lcom/viabix/app/di/AppModule;", "", "()V", "provideAnviRepository", "Lcom/viabix/app/domain/repository/AnviRepository;", "apiService", "Lcom/viabix/app/data/api/ViabixApiService;", "database", "Lcom/viabix/app/data/local/ViabixDatabase;", "tokenManager", "Lcom/viabix/app/utils/TokenManager;", "provideAuthRepository", "Lcom/viabix/app/domain/repository/AuthRepository;", "provideDatabase", "context", "Landroid/content/Context;", "provideProjetoRepository", "Lcom/viabix/app/domain/repository/ProjetoRepository;", "provideTokenManager", "provideViabixApiService", "app_debug"})
@dagger.hilt.InstallIn(value = {dagger.hilt.components.SingletonComponent.class})
public final class AppModule {
    @org.jetbrains.annotations.NotNull()
    public static final com.viabix.app.di.AppModule INSTANCE = null;
    
    private AppModule() {
        super();
    }
    
    @javax.inject.Singleton()
    @dagger.Provides()
    @org.jetbrains.annotations.NotNull()
    public final com.viabix.app.utils.TokenManager provideTokenManager(@dagger.hilt.android.qualifiers.ApplicationContext()
    @org.jetbrains.annotations.NotNull()
    android.content.Context context) {
        return null;
    }
    
    @javax.inject.Singleton()
    @dagger.Provides()
    @org.jetbrains.annotations.NotNull()
    public final com.viabix.app.data.local.ViabixDatabase provideDatabase(@dagger.hilt.android.qualifiers.ApplicationContext()
    @org.jetbrains.annotations.NotNull()
    android.content.Context context) {
        return null;
    }
    
    @javax.inject.Singleton()
    @dagger.Provides()
    @org.jetbrains.annotations.NotNull()
    public final com.viabix.app.data.api.ViabixApiService provideViabixApiService(@dagger.hilt.android.qualifiers.ApplicationContext()
    @org.jetbrains.annotations.NotNull()
    android.content.Context context, @org.jetbrains.annotations.NotNull()
    com.viabix.app.utils.TokenManager tokenManager) {
        return null;
    }
    
    @javax.inject.Singleton()
    @dagger.Provides()
    @org.jetbrains.annotations.NotNull()
    public final com.viabix.app.domain.repository.AuthRepository provideAuthRepository(@org.jetbrains.annotations.NotNull()
    com.viabix.app.data.api.ViabixApiService apiService, @org.jetbrains.annotations.NotNull()
    com.viabix.app.data.local.ViabixDatabase database, @org.jetbrains.annotations.NotNull()
    com.viabix.app.utils.TokenManager tokenManager) {
        return null;
    }
    
    @javax.inject.Singleton()
    @dagger.Provides()
    @org.jetbrains.annotations.NotNull()
    public final com.viabix.app.domain.repository.AnviRepository provideAnviRepository(@org.jetbrains.annotations.NotNull()
    com.viabix.app.data.api.ViabixApiService apiService, @org.jetbrains.annotations.NotNull()
    com.viabix.app.data.local.ViabixDatabase database, @org.jetbrains.annotations.NotNull()
    com.viabix.app.utils.TokenManager tokenManager) {
        return null;
    }
    
    @javax.inject.Singleton()
    @dagger.Provides()
    @org.jetbrains.annotations.NotNull()
    public final com.viabix.app.domain.repository.ProjetoRepository provideProjetoRepository(@org.jetbrains.annotations.NotNull()
    com.viabix.app.data.api.ViabixApiService apiService, @org.jetbrains.annotations.NotNull()
    com.viabix.app.data.local.ViabixDatabase database, @org.jetbrains.annotations.NotNull()
    com.viabix.app.utils.TokenManager tokenManager) {
        return null;
    }
}