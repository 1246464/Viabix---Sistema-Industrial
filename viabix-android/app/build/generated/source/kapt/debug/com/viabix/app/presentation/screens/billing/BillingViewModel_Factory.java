package com.viabix.app.presentation.screens.billing;

import com.viabix.app.domain.repository.AuthRepository;
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
public final class BillingViewModel_Factory implements Factory<BillingViewModel> {
  private final Provider<TokenManager> tokenManagerProvider;

  private final Provider<AuthRepository> authRepositoryProvider;

  public BillingViewModel_Factory(Provider<TokenManager> tokenManagerProvider,
      Provider<AuthRepository> authRepositoryProvider) {
    this.tokenManagerProvider = tokenManagerProvider;
    this.authRepositoryProvider = authRepositoryProvider;
  }

  @Override
  public BillingViewModel get() {
    return newInstance(tokenManagerProvider.get(), authRepositoryProvider.get());
  }

  public static BillingViewModel_Factory create(Provider<TokenManager> tokenManagerProvider,
      Provider<AuthRepository> authRepositoryProvider) {
    return new BillingViewModel_Factory(tokenManagerProvider, authRepositoryProvider);
  }

  public static BillingViewModel newInstance(TokenManager tokenManager,
      AuthRepository authRepository) {
    return new BillingViewModel(tokenManager, authRepository);
  }
}
