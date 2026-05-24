package com.viabix.app.presentation.screens.login;

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
    "KotlinInternalInJava"
})
public final class LoginViewModel_Factory implements Factory<LoginViewModel> {
  private final Provider<TokenManager> tokenManagerProvider;

  public LoginViewModel_Factory(Provider<TokenManager> tokenManagerProvider) {
    this.tokenManagerProvider = tokenManagerProvider;
  }

  @Override
  public LoginViewModel get() {
    return newInstance(tokenManagerProvider.get());
  }

  public static LoginViewModel_Factory create(Provider<TokenManager> tokenManagerProvider) {
    return new LoginViewModel_Factory(tokenManagerProvider);
  }

  public static LoginViewModel newInstance(TokenManager tokenManager) {
    return new LoginViewModel(tokenManager);
  }
}
