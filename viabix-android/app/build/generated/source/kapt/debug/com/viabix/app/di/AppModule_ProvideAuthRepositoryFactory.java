package com.viabix.app.di;

import com.viabix.app.data.api.ViabixApiService;
import com.viabix.app.data.local.ViabixDatabase;
import com.viabix.app.domain.repository.AuthRepository;
import com.viabix.app.utils.TokenManager;
import dagger.internal.DaggerGenerated;
import dagger.internal.Factory;
import dagger.internal.Preconditions;
import dagger.internal.QualifierMetadata;
import dagger.internal.ScopeMetadata;
import javax.annotation.processing.Generated;
import javax.inject.Provider;

@ScopeMetadata("javax.inject.Singleton")
@QualifierMetadata
@DaggerGenerated
@Generated(
    value = "dagger.internal.codegen.ComponentProcessor",
    comments = "https://dagger.dev"
)
@SuppressWarnings({
    "unchecked",
    "rawtypes",
    "KotlinInternal",
    "KotlinInternalInJava",
    "cast"
})
public final class AppModule_ProvideAuthRepositoryFactory implements Factory<AuthRepository> {
  private final Provider<ViabixApiService> apiServiceProvider;

  private final Provider<ViabixDatabase> databaseProvider;

  private final Provider<TokenManager> tokenManagerProvider;

  public AppModule_ProvideAuthRepositoryFactory(Provider<ViabixApiService> apiServiceProvider,
      Provider<ViabixDatabase> databaseProvider, Provider<TokenManager> tokenManagerProvider) {
    this.apiServiceProvider = apiServiceProvider;
    this.databaseProvider = databaseProvider;
    this.tokenManagerProvider = tokenManagerProvider;
  }

  @Override
  public AuthRepository get() {
    return provideAuthRepository(apiServiceProvider.get(), databaseProvider.get(), tokenManagerProvider.get());
  }

  public static AppModule_ProvideAuthRepositoryFactory create(
      Provider<ViabixApiService> apiServiceProvider, Provider<ViabixDatabase> databaseProvider,
      Provider<TokenManager> tokenManagerProvider) {
    return new AppModule_ProvideAuthRepositoryFactory(apiServiceProvider, databaseProvider, tokenManagerProvider);
  }

  public static AuthRepository provideAuthRepository(ViabixApiService apiService,
      ViabixDatabase database, TokenManager tokenManager) {
    return Preconditions.checkNotNullFromProvides(AppModule.INSTANCE.provideAuthRepository(apiService, database, tokenManager));
  }
}
