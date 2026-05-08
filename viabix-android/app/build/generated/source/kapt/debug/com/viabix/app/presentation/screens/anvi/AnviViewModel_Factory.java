package com.viabix.app.presentation.screens.anvi;

import com.viabix.app.domain.repository.AnviRepository;
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
public final class AnviViewModel_Factory implements Factory<AnviViewModel> {
  private final Provider<AnviRepository> anviRepositoryProvider;

  private final Provider<TokenManager> tokenManagerProvider;

  public AnviViewModel_Factory(Provider<AnviRepository> anviRepositoryProvider,
      Provider<TokenManager> tokenManagerProvider) {
    this.anviRepositoryProvider = anviRepositoryProvider;
    this.tokenManagerProvider = tokenManagerProvider;
  }

  @Override
  public AnviViewModel get() {
    return newInstance(anviRepositoryProvider.get(), tokenManagerProvider.get());
  }

  public static AnviViewModel_Factory create(Provider<AnviRepository> anviRepositoryProvider,
      Provider<TokenManager> tokenManagerProvider) {
    return new AnviViewModel_Factory(anviRepositoryProvider, tokenManagerProvider);
  }

  public static AnviViewModel newInstance(AnviRepository anviRepository,
      TokenManager tokenManager) {
    return new AnviViewModel(anviRepository, tokenManager);
  }
}
