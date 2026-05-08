package com.viabix.app.presentation.screens.projetos;

import com.viabix.app.domain.repository.ProjetoRepository;
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
public final class ProjetoViewModel_Factory implements Factory<ProjetoViewModel> {
  private final Provider<ProjetoRepository> projetoRepositoryProvider;

  private final Provider<TokenManager> tokenManagerProvider;

  public ProjetoViewModel_Factory(Provider<ProjetoRepository> projetoRepositoryProvider,
      Provider<TokenManager> tokenManagerProvider) {
    this.projetoRepositoryProvider = projetoRepositoryProvider;
    this.tokenManagerProvider = tokenManagerProvider;
  }

  @Override
  public ProjetoViewModel get() {
    return newInstance(projetoRepositoryProvider.get(), tokenManagerProvider.get());
  }

  public static ProjetoViewModel_Factory create(
      Provider<ProjetoRepository> projetoRepositoryProvider,
      Provider<TokenManager> tokenManagerProvider) {
    return new ProjetoViewModel_Factory(projetoRepositoryProvider, tokenManagerProvider);
  }

  public static ProjetoViewModel newInstance(ProjetoRepository projetoRepository,
      TokenManager tokenManager) {
    return new ProjetoViewModel(projetoRepository, tokenManager);
  }
}
