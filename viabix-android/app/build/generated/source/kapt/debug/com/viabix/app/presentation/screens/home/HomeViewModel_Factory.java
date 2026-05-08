package com.viabix.app.presentation.screens.home;

import com.viabix.app.domain.repository.AuthRepository;
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
public final class HomeViewModel_Factory implements Factory<HomeViewModel> {
  private final Provider<AuthRepository> authRepositoryProvider;

  public HomeViewModel_Factory(Provider<AuthRepository> authRepositoryProvider) {
    this.authRepositoryProvider = authRepositoryProvider;
  }

  @Override
  public HomeViewModel get() {
    return newInstance(authRepositoryProvider.get());
  }

  public static HomeViewModel_Factory create(Provider<AuthRepository> authRepositoryProvider) {
    return new HomeViewModel_Factory(authRepositoryProvider);
  }

  public static HomeViewModel newInstance(AuthRepository authRepository) {
    return new HomeViewModel(authRepository);
  }
}
