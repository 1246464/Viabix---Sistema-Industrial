package com.viabix.app.presentation.screens.viabilidade;

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
public final class ViabilidadeViewModel_Factory implements Factory<ViabilidadeViewModel> {
  private final Provider<TokenManager> tokenManagerProvider;

  public ViabilidadeViewModel_Factory(Provider<TokenManager> tokenManagerProvider) {
    this.tokenManagerProvider = tokenManagerProvider;
  }

  @Override
  public ViabilidadeViewModel get() {
    return newInstance(tokenManagerProvider.get());
  }

  public static ViabilidadeViewModel_Factory create(Provider<TokenManager> tokenManagerProvider) {
    return new ViabilidadeViewModel_Factory(tokenManagerProvider);
  }

  public static ViabilidadeViewModel newInstance(TokenManager tokenManager) {
    return new ViabilidadeViewModel(tokenManager);
  }
}
