package com.viabix.app.domain.repository;

import com.viabix.app.data.api.ViabixApiService;
import com.viabix.app.data.local.ViabixDatabase;
import com.viabix.app.utils.TokenManager;
import dagger.internal.DaggerGenerated;
import dagger.internal.Factory;
import dagger.internal.QualifierMetadata;
import dagger.internal.ScopeMetadata;
import javax.annotation.processing.Generated;
import javax.inject.Provider;

@ScopeMetadata
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
public final class AnviRepository_Factory implements Factory<AnviRepository> {
  private final Provider<ViabixApiService> apiServiceProvider;

  private final Provider<ViabixDatabase> databaseProvider;

  private final Provider<TokenManager> tokenManagerProvider;

  public AnviRepository_Factory(Provider<ViabixApiService> apiServiceProvider,
      Provider<ViabixDatabase> databaseProvider, Provider<TokenManager> tokenManagerProvider) {
    this.apiServiceProvider = apiServiceProvider;
    this.databaseProvider = databaseProvider;
    this.tokenManagerProvider = tokenManagerProvider;
  }

  @Override
  public AnviRepository get() {
    return newInstance(apiServiceProvider.get(), databaseProvider.get(), tokenManagerProvider.get());
  }

  public static AnviRepository_Factory create(Provider<ViabixApiService> apiServiceProvider,
      Provider<ViabixDatabase> databaseProvider, Provider<TokenManager> tokenManagerProvider) {
    return new AnviRepository_Factory(apiServiceProvider, databaseProvider, tokenManagerProvider);
  }

  public static AnviRepository newInstance(ViabixApiService apiService, ViabixDatabase database,
      TokenManager tokenManager) {
    return new AnviRepository(apiService, database, tokenManager);
  }
}
