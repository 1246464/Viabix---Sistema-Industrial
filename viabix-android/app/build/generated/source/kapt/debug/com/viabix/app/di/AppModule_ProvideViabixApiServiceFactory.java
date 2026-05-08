package com.viabix.app.di;

import android.content.Context;
import com.viabix.app.data.api.ViabixApiService;
import com.viabix.app.utils.TokenManager;
import dagger.internal.DaggerGenerated;
import dagger.internal.Factory;
import dagger.internal.Preconditions;
import dagger.internal.QualifierMetadata;
import dagger.internal.ScopeMetadata;
import javax.annotation.processing.Generated;
import javax.inject.Provider;

@ScopeMetadata("javax.inject.Singleton")
@QualifierMetadata("dagger.hilt.android.qualifiers.ApplicationContext")
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
public final class AppModule_ProvideViabixApiServiceFactory implements Factory<ViabixApiService> {
  private final Provider<Context> contextProvider;

  private final Provider<TokenManager> tokenManagerProvider;

  public AppModule_ProvideViabixApiServiceFactory(Provider<Context> contextProvider,
      Provider<TokenManager> tokenManagerProvider) {
    this.contextProvider = contextProvider;
    this.tokenManagerProvider = tokenManagerProvider;
  }

  @Override
  public ViabixApiService get() {
    return provideViabixApiService(contextProvider.get(), tokenManagerProvider.get());
  }

  public static AppModule_ProvideViabixApiServiceFactory create(Provider<Context> contextProvider,
      Provider<TokenManager> tokenManagerProvider) {
    return new AppModule_ProvideViabixApiServiceFactory(contextProvider, tokenManagerProvider);
  }

  public static ViabixApiService provideViabixApiService(Context context,
      TokenManager tokenManager) {
    return Preconditions.checkNotNullFromProvides(AppModule.INSTANCE.provideViabixApiService(context, tokenManager));
  }
}
