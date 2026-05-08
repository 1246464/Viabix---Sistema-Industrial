package com.viabix.app;

import android.app.Activity;
import android.app.Service;
import android.view.View;
import androidx.fragment.app.Fragment;
import androidx.lifecycle.SavedStateHandle;
import androidx.lifecycle.ViewModel;
import com.viabix.app.data.api.ViabixApiService;
import com.viabix.app.data.local.ViabixDatabase;
import com.viabix.app.di.AppModule_ProvideAnviRepositoryFactory;
import com.viabix.app.di.AppModule_ProvideAuthRepositoryFactory;
import com.viabix.app.di.AppModule_ProvideDatabaseFactory;
import com.viabix.app.di.AppModule_ProvideProjetoRepositoryFactory;
import com.viabix.app.di.AppModule_ProvideTokenManagerFactory;
import com.viabix.app.di.AppModule_ProvideViabixApiServiceFactory;
import com.viabix.app.domain.repository.AnviRepository;
import com.viabix.app.domain.repository.AuthRepository;
import com.viabix.app.domain.repository.ProjetoRepository;
import com.viabix.app.presentation.screens.anvi.AnviViewModel;
import com.viabix.app.presentation.screens.anvi.AnviViewModel_HiltModules;
import com.viabix.app.presentation.screens.auth.AuthViewModel;
import com.viabix.app.presentation.screens.auth.AuthViewModel_HiltModules;
import com.viabix.app.presentation.screens.billing.BillingViewModel;
import com.viabix.app.presentation.screens.billing.BillingViewModel_HiltModules;
import com.viabix.app.presentation.screens.home.HomeViewModel;
import com.viabix.app.presentation.screens.home.HomeViewModel_HiltModules;
import com.viabix.app.presentation.screens.projetos.ProjetoViewModel;
import com.viabix.app.presentation.screens.projetos.ProjetoViewModel_HiltModules;
import com.viabix.app.presentation.screens.viabilidade.ViabilidadeViewModel;
import com.viabix.app.presentation.screens.viabilidade.ViabilidadeViewModel_HiltModules;
import com.viabix.app.utils.TokenManager;
import dagger.hilt.android.ActivityRetainedLifecycle;
import dagger.hilt.android.ViewModelLifecycle;
import dagger.hilt.android.internal.builders.ActivityComponentBuilder;
import dagger.hilt.android.internal.builders.ActivityRetainedComponentBuilder;
import dagger.hilt.android.internal.builders.FragmentComponentBuilder;
import dagger.hilt.android.internal.builders.ServiceComponentBuilder;
import dagger.hilt.android.internal.builders.ViewComponentBuilder;
import dagger.hilt.android.internal.builders.ViewModelComponentBuilder;
import dagger.hilt.android.internal.builders.ViewWithFragmentComponentBuilder;
import dagger.hilt.android.internal.lifecycle.DefaultViewModelFactories;
import dagger.hilt.android.internal.lifecycle.DefaultViewModelFactories_InternalFactoryFactory_Factory;
import dagger.hilt.android.internal.managers.ActivityRetainedComponentManager_LifecycleModule_ProvideActivityRetainedLifecycleFactory;
import dagger.hilt.android.internal.managers.SavedStateHandleHolder;
import dagger.hilt.android.internal.modules.ApplicationContextModule;
import dagger.hilt.android.internal.modules.ApplicationContextModule_ProvideContextFactory;
import dagger.internal.DaggerGenerated;
import dagger.internal.DoubleCheck;
import dagger.internal.IdentifierNameString;
import dagger.internal.KeepFieldType;
import dagger.internal.LazyClassKeyMap;
import dagger.internal.MapBuilder;
import dagger.internal.Preconditions;
import dagger.internal.Provider;
import java.util.Collections;
import java.util.Map;
import java.util.Set;
import javax.annotation.processing.Generated;

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
public final class DaggerViabixApplication_HiltComponents_SingletonC {
  private DaggerViabixApplication_HiltComponents_SingletonC() {
  }

  public static Builder builder() {
    return new Builder();
  }

  public static final class Builder {
    private ApplicationContextModule applicationContextModule;

    private Builder() {
    }

    public Builder applicationContextModule(ApplicationContextModule applicationContextModule) {
      this.applicationContextModule = Preconditions.checkNotNull(applicationContextModule);
      return this;
    }

    public ViabixApplication_HiltComponents.SingletonC build() {
      Preconditions.checkBuilderRequirement(applicationContextModule, ApplicationContextModule.class);
      return new SingletonCImpl(applicationContextModule);
    }
  }

  private static final class ActivityRetainedCBuilder implements ViabixApplication_HiltComponents.ActivityRetainedC.Builder {
    private final SingletonCImpl singletonCImpl;

    private SavedStateHandleHolder savedStateHandleHolder;

    private ActivityRetainedCBuilder(SingletonCImpl singletonCImpl) {
      this.singletonCImpl = singletonCImpl;
    }

    @Override
    public ActivityRetainedCBuilder savedStateHandleHolder(
        SavedStateHandleHolder savedStateHandleHolder) {
      this.savedStateHandleHolder = Preconditions.checkNotNull(savedStateHandleHolder);
      return this;
    }

    @Override
    public ViabixApplication_HiltComponents.ActivityRetainedC build() {
      Preconditions.checkBuilderRequirement(savedStateHandleHolder, SavedStateHandleHolder.class);
      return new ActivityRetainedCImpl(singletonCImpl, savedStateHandleHolder);
    }
  }

  private static final class ActivityCBuilder implements ViabixApplication_HiltComponents.ActivityC.Builder {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private Activity activity;

    private ActivityCBuilder(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
    }

    @Override
    public ActivityCBuilder activity(Activity activity) {
      this.activity = Preconditions.checkNotNull(activity);
      return this;
    }

    @Override
    public ViabixApplication_HiltComponents.ActivityC build() {
      Preconditions.checkBuilderRequirement(activity, Activity.class);
      return new ActivityCImpl(singletonCImpl, activityRetainedCImpl, activity);
    }
  }

  private static final class FragmentCBuilder implements ViabixApplication_HiltComponents.FragmentC.Builder {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private Fragment fragment;

    private FragmentCBuilder(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, ActivityCImpl activityCImpl) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;
    }

    @Override
    public FragmentCBuilder fragment(Fragment fragment) {
      this.fragment = Preconditions.checkNotNull(fragment);
      return this;
    }

    @Override
    public ViabixApplication_HiltComponents.FragmentC build() {
      Preconditions.checkBuilderRequirement(fragment, Fragment.class);
      return new FragmentCImpl(singletonCImpl, activityRetainedCImpl, activityCImpl, fragment);
    }
  }

  private static final class ViewWithFragmentCBuilder implements ViabixApplication_HiltComponents.ViewWithFragmentC.Builder {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private final FragmentCImpl fragmentCImpl;

    private View view;

    private ViewWithFragmentCBuilder(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, ActivityCImpl activityCImpl,
        FragmentCImpl fragmentCImpl) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;
      this.fragmentCImpl = fragmentCImpl;
    }

    @Override
    public ViewWithFragmentCBuilder view(View view) {
      this.view = Preconditions.checkNotNull(view);
      return this;
    }

    @Override
    public ViabixApplication_HiltComponents.ViewWithFragmentC build() {
      Preconditions.checkBuilderRequirement(view, View.class);
      return new ViewWithFragmentCImpl(singletonCImpl, activityRetainedCImpl, activityCImpl, fragmentCImpl, view);
    }
  }

  private static final class ViewCBuilder implements ViabixApplication_HiltComponents.ViewC.Builder {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private View view;

    private ViewCBuilder(SingletonCImpl singletonCImpl, ActivityRetainedCImpl activityRetainedCImpl,
        ActivityCImpl activityCImpl) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;
    }

    @Override
    public ViewCBuilder view(View view) {
      this.view = Preconditions.checkNotNull(view);
      return this;
    }

    @Override
    public ViabixApplication_HiltComponents.ViewC build() {
      Preconditions.checkBuilderRequirement(view, View.class);
      return new ViewCImpl(singletonCImpl, activityRetainedCImpl, activityCImpl, view);
    }
  }

  private static final class ViewModelCBuilder implements ViabixApplication_HiltComponents.ViewModelC.Builder {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private SavedStateHandle savedStateHandle;

    private ViewModelLifecycle viewModelLifecycle;

    private ViewModelCBuilder(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
    }

    @Override
    public ViewModelCBuilder savedStateHandle(SavedStateHandle handle) {
      this.savedStateHandle = Preconditions.checkNotNull(handle);
      return this;
    }

    @Override
    public ViewModelCBuilder viewModelLifecycle(ViewModelLifecycle viewModelLifecycle) {
      this.viewModelLifecycle = Preconditions.checkNotNull(viewModelLifecycle);
      return this;
    }

    @Override
    public ViabixApplication_HiltComponents.ViewModelC build() {
      Preconditions.checkBuilderRequirement(savedStateHandle, SavedStateHandle.class);
      Preconditions.checkBuilderRequirement(viewModelLifecycle, ViewModelLifecycle.class);
      return new ViewModelCImpl(singletonCImpl, activityRetainedCImpl, savedStateHandle, viewModelLifecycle);
    }
  }

  private static final class ServiceCBuilder implements ViabixApplication_HiltComponents.ServiceC.Builder {
    private final SingletonCImpl singletonCImpl;

    private Service service;

    private ServiceCBuilder(SingletonCImpl singletonCImpl) {
      this.singletonCImpl = singletonCImpl;
    }

    @Override
    public ServiceCBuilder service(Service service) {
      this.service = Preconditions.checkNotNull(service);
      return this;
    }

    @Override
    public ViabixApplication_HiltComponents.ServiceC build() {
      Preconditions.checkBuilderRequirement(service, Service.class);
      return new ServiceCImpl(singletonCImpl, service);
    }
  }

  private static final class ViewWithFragmentCImpl extends ViabixApplication_HiltComponents.ViewWithFragmentC {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private final FragmentCImpl fragmentCImpl;

    private final ViewWithFragmentCImpl viewWithFragmentCImpl = this;

    private ViewWithFragmentCImpl(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, ActivityCImpl activityCImpl,
        FragmentCImpl fragmentCImpl, View viewParam) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;
      this.fragmentCImpl = fragmentCImpl;


    }
  }

  private static final class FragmentCImpl extends ViabixApplication_HiltComponents.FragmentC {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private final FragmentCImpl fragmentCImpl = this;

    private FragmentCImpl(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, ActivityCImpl activityCImpl,
        Fragment fragmentParam) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;


    }

    @Override
    public DefaultViewModelFactories.InternalFactoryFactory getHiltInternalFactoryFactory() {
      return activityCImpl.getHiltInternalFactoryFactory();
    }

    @Override
    public ViewWithFragmentComponentBuilder viewWithFragmentComponentBuilder() {
      return new ViewWithFragmentCBuilder(singletonCImpl, activityRetainedCImpl, activityCImpl, fragmentCImpl);
    }
  }

  private static final class ViewCImpl extends ViabixApplication_HiltComponents.ViewC {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl;

    private final ViewCImpl viewCImpl = this;

    private ViewCImpl(SingletonCImpl singletonCImpl, ActivityRetainedCImpl activityRetainedCImpl,
        ActivityCImpl activityCImpl, View viewParam) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;
      this.activityCImpl = activityCImpl;


    }
  }

  private static final class ActivityCImpl extends ViabixApplication_HiltComponents.ActivityC {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ActivityCImpl activityCImpl = this;

    private ActivityCImpl(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, Activity activityParam) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;


    }

    @Override
    public void injectMainActivity(MainActivity mainActivity) {
    }

    @Override
    public DefaultViewModelFactories.InternalFactoryFactory getHiltInternalFactoryFactory() {
      return DefaultViewModelFactories_InternalFactoryFactory_Factory.newInstance(getViewModelKeys(), new ViewModelCBuilder(singletonCImpl, activityRetainedCImpl));
    }

    @Override
    public Map<Class<?>, Boolean> getViewModelKeys() {
      return LazyClassKeyMap.<Boolean>of(MapBuilder.<String, Boolean>newMapBuilder(6).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_anvi_AnviViewModel, AnviViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_auth_AuthViewModel, AuthViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_billing_BillingViewModel, BillingViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_home_HomeViewModel, HomeViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_projetos_ProjetoViewModel, ProjetoViewModel_HiltModules.KeyModule.provide()).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_viabilidade_ViabilidadeViewModel, ViabilidadeViewModel_HiltModules.KeyModule.provide()).build());
    }

    @Override
    public ViewModelComponentBuilder getViewModelComponentBuilder() {
      return new ViewModelCBuilder(singletonCImpl, activityRetainedCImpl);
    }

    @Override
    public FragmentComponentBuilder fragmentComponentBuilder() {
      return new FragmentCBuilder(singletonCImpl, activityRetainedCImpl, activityCImpl);
    }

    @Override
    public ViewComponentBuilder viewComponentBuilder() {
      return new ViewCBuilder(singletonCImpl, activityRetainedCImpl, activityCImpl);
    }

    @IdentifierNameString
    private static final class LazyClassKeyProvider {
      static String com_viabix_app_presentation_screens_projetos_ProjetoViewModel = "com.viabix.app.presentation.screens.projetos.ProjetoViewModel";

      static String com_viabix_app_presentation_screens_anvi_AnviViewModel = "com.viabix.app.presentation.screens.anvi.AnviViewModel";

      static String com_viabix_app_presentation_screens_auth_AuthViewModel = "com.viabix.app.presentation.screens.auth.AuthViewModel";

      static String com_viabix_app_presentation_screens_viabilidade_ViabilidadeViewModel = "com.viabix.app.presentation.screens.viabilidade.ViabilidadeViewModel";

      static String com_viabix_app_presentation_screens_billing_BillingViewModel = "com.viabix.app.presentation.screens.billing.BillingViewModel";

      static String com_viabix_app_presentation_screens_home_HomeViewModel = "com.viabix.app.presentation.screens.home.HomeViewModel";

      @KeepFieldType
      ProjetoViewModel com_viabix_app_presentation_screens_projetos_ProjetoViewModel2;

      @KeepFieldType
      AnviViewModel com_viabix_app_presentation_screens_anvi_AnviViewModel2;

      @KeepFieldType
      AuthViewModel com_viabix_app_presentation_screens_auth_AuthViewModel2;

      @KeepFieldType
      ViabilidadeViewModel com_viabix_app_presentation_screens_viabilidade_ViabilidadeViewModel2;

      @KeepFieldType
      BillingViewModel com_viabix_app_presentation_screens_billing_BillingViewModel2;

      @KeepFieldType
      HomeViewModel com_viabix_app_presentation_screens_home_HomeViewModel2;
    }
  }

  private static final class ViewModelCImpl extends ViabixApplication_HiltComponents.ViewModelC {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl;

    private final ViewModelCImpl viewModelCImpl = this;

    private Provider<AnviViewModel> anviViewModelProvider;

    private Provider<AuthViewModel> authViewModelProvider;

    private Provider<BillingViewModel> billingViewModelProvider;

    private Provider<HomeViewModel> homeViewModelProvider;

    private Provider<ProjetoViewModel> projetoViewModelProvider;

    private Provider<ViabilidadeViewModel> viabilidadeViewModelProvider;

    private ViewModelCImpl(SingletonCImpl singletonCImpl,
        ActivityRetainedCImpl activityRetainedCImpl, SavedStateHandle savedStateHandleParam,
        ViewModelLifecycle viewModelLifecycleParam) {
      this.singletonCImpl = singletonCImpl;
      this.activityRetainedCImpl = activityRetainedCImpl;

      initialize(savedStateHandleParam, viewModelLifecycleParam);

    }

    @SuppressWarnings("unchecked")
    private void initialize(final SavedStateHandle savedStateHandleParam,
        final ViewModelLifecycle viewModelLifecycleParam) {
      this.anviViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 0);
      this.authViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 1);
      this.billingViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 2);
      this.homeViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 3);
      this.projetoViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 4);
      this.viabilidadeViewModelProvider = new SwitchingProvider<>(singletonCImpl, activityRetainedCImpl, viewModelCImpl, 5);
    }

    @Override
    public Map<Class<?>, javax.inject.Provider<ViewModel>> getHiltViewModelMap() {
      return LazyClassKeyMap.<javax.inject.Provider<ViewModel>>of(MapBuilder.<String, javax.inject.Provider<ViewModel>>newMapBuilder(6).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_anvi_AnviViewModel, ((Provider) anviViewModelProvider)).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_auth_AuthViewModel, ((Provider) authViewModelProvider)).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_billing_BillingViewModel, ((Provider) billingViewModelProvider)).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_home_HomeViewModel, ((Provider) homeViewModelProvider)).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_projetos_ProjetoViewModel, ((Provider) projetoViewModelProvider)).put(LazyClassKeyProvider.com_viabix_app_presentation_screens_viabilidade_ViabilidadeViewModel, ((Provider) viabilidadeViewModelProvider)).build());
    }

    @Override
    public Map<Class<?>, Object> getHiltViewModelAssistedMap() {
      return Collections.<Class<?>, Object>emptyMap();
    }

    @IdentifierNameString
    private static final class LazyClassKeyProvider {
      static String com_viabix_app_presentation_screens_home_HomeViewModel = "com.viabix.app.presentation.screens.home.HomeViewModel";

      static String com_viabix_app_presentation_screens_projetos_ProjetoViewModel = "com.viabix.app.presentation.screens.projetos.ProjetoViewModel";

      static String com_viabix_app_presentation_screens_billing_BillingViewModel = "com.viabix.app.presentation.screens.billing.BillingViewModel";

      static String com_viabix_app_presentation_screens_anvi_AnviViewModel = "com.viabix.app.presentation.screens.anvi.AnviViewModel";

      static String com_viabix_app_presentation_screens_auth_AuthViewModel = "com.viabix.app.presentation.screens.auth.AuthViewModel";

      static String com_viabix_app_presentation_screens_viabilidade_ViabilidadeViewModel = "com.viabix.app.presentation.screens.viabilidade.ViabilidadeViewModel";

      @KeepFieldType
      HomeViewModel com_viabix_app_presentation_screens_home_HomeViewModel2;

      @KeepFieldType
      ProjetoViewModel com_viabix_app_presentation_screens_projetos_ProjetoViewModel2;

      @KeepFieldType
      BillingViewModel com_viabix_app_presentation_screens_billing_BillingViewModel2;

      @KeepFieldType
      AnviViewModel com_viabix_app_presentation_screens_anvi_AnviViewModel2;

      @KeepFieldType
      AuthViewModel com_viabix_app_presentation_screens_auth_AuthViewModel2;

      @KeepFieldType
      ViabilidadeViewModel com_viabix_app_presentation_screens_viabilidade_ViabilidadeViewModel2;
    }

    private static final class SwitchingProvider<T> implements Provider<T> {
      private final SingletonCImpl singletonCImpl;

      private final ActivityRetainedCImpl activityRetainedCImpl;

      private final ViewModelCImpl viewModelCImpl;

      private final int id;

      SwitchingProvider(SingletonCImpl singletonCImpl, ActivityRetainedCImpl activityRetainedCImpl,
          ViewModelCImpl viewModelCImpl, int id) {
        this.singletonCImpl = singletonCImpl;
        this.activityRetainedCImpl = activityRetainedCImpl;
        this.viewModelCImpl = viewModelCImpl;
        this.id = id;
      }

      @SuppressWarnings("unchecked")
      @Override
      public T get() {
        switch (id) {
          case 0: // com.viabix.app.presentation.screens.anvi.AnviViewModel 
          return (T) new AnviViewModel(singletonCImpl.provideAnviRepositoryProvider.get(), singletonCImpl.provideTokenManagerProvider.get());

          case 1: // com.viabix.app.presentation.screens.auth.AuthViewModel 
          return (T) new AuthViewModel(singletonCImpl.provideAuthRepositoryProvider.get());

          case 2: // com.viabix.app.presentation.screens.billing.BillingViewModel 
          return (T) new BillingViewModel(singletonCImpl.provideTokenManagerProvider.get(), singletonCImpl.provideAuthRepositoryProvider.get());

          case 3: // com.viabix.app.presentation.screens.home.HomeViewModel 
          return (T) new HomeViewModel(singletonCImpl.provideAuthRepositoryProvider.get());

          case 4: // com.viabix.app.presentation.screens.projetos.ProjetoViewModel 
          return (T) new ProjetoViewModel(singletonCImpl.provideProjetoRepositoryProvider.get(), singletonCImpl.provideTokenManagerProvider.get());

          case 5: // com.viabix.app.presentation.screens.viabilidade.ViabilidadeViewModel 
          return (T) new ViabilidadeViewModel(singletonCImpl.provideTokenManagerProvider.get());

          default: throw new AssertionError(id);
        }
      }
    }
  }

  private static final class ActivityRetainedCImpl extends ViabixApplication_HiltComponents.ActivityRetainedC {
    private final SingletonCImpl singletonCImpl;

    private final ActivityRetainedCImpl activityRetainedCImpl = this;

    private Provider<ActivityRetainedLifecycle> provideActivityRetainedLifecycleProvider;

    private ActivityRetainedCImpl(SingletonCImpl singletonCImpl,
        SavedStateHandleHolder savedStateHandleHolderParam) {
      this.singletonCImpl = singletonCImpl;

      initialize(savedStateHandleHolderParam);

    }

    @SuppressWarnings("unchecked")
    private void initialize(final SavedStateHandleHolder savedStateHandleHolderParam) {
      this.provideActivityRetainedLifecycleProvider = DoubleCheck.provider(new SwitchingProvider<ActivityRetainedLifecycle>(singletonCImpl, activityRetainedCImpl, 0));
    }

    @Override
    public ActivityComponentBuilder activityComponentBuilder() {
      return new ActivityCBuilder(singletonCImpl, activityRetainedCImpl);
    }

    @Override
    public ActivityRetainedLifecycle getActivityRetainedLifecycle() {
      return provideActivityRetainedLifecycleProvider.get();
    }

    private static final class SwitchingProvider<T> implements Provider<T> {
      private final SingletonCImpl singletonCImpl;

      private final ActivityRetainedCImpl activityRetainedCImpl;

      private final int id;

      SwitchingProvider(SingletonCImpl singletonCImpl, ActivityRetainedCImpl activityRetainedCImpl,
          int id) {
        this.singletonCImpl = singletonCImpl;
        this.activityRetainedCImpl = activityRetainedCImpl;
        this.id = id;
      }

      @SuppressWarnings("unchecked")
      @Override
      public T get() {
        switch (id) {
          case 0: // dagger.hilt.android.ActivityRetainedLifecycle 
          return (T) ActivityRetainedComponentManager_LifecycleModule_ProvideActivityRetainedLifecycleFactory.provideActivityRetainedLifecycle();

          default: throw new AssertionError(id);
        }
      }
    }
  }

  private static final class ServiceCImpl extends ViabixApplication_HiltComponents.ServiceC {
    private final SingletonCImpl singletonCImpl;

    private final ServiceCImpl serviceCImpl = this;

    private ServiceCImpl(SingletonCImpl singletonCImpl, Service serviceParam) {
      this.singletonCImpl = singletonCImpl;


    }
  }

  private static final class SingletonCImpl extends ViabixApplication_HiltComponents.SingletonC {
    private final ApplicationContextModule applicationContextModule;

    private final SingletonCImpl singletonCImpl = this;

    private Provider<TokenManager> provideTokenManagerProvider;

    private Provider<ViabixApiService> provideViabixApiServiceProvider;

    private Provider<ViabixDatabase> provideDatabaseProvider;

    private Provider<AnviRepository> provideAnviRepositoryProvider;

    private Provider<AuthRepository> provideAuthRepositoryProvider;

    private Provider<ProjetoRepository> provideProjetoRepositoryProvider;

    private SingletonCImpl(ApplicationContextModule applicationContextModuleParam) {
      this.applicationContextModule = applicationContextModuleParam;
      initialize(applicationContextModuleParam);

    }

    @SuppressWarnings("unchecked")
    private void initialize(final ApplicationContextModule applicationContextModuleParam) {
      this.provideTokenManagerProvider = DoubleCheck.provider(new SwitchingProvider<TokenManager>(singletonCImpl, 0));
      this.provideViabixApiServiceProvider = DoubleCheck.provider(new SwitchingProvider<ViabixApiService>(singletonCImpl, 2));
      this.provideDatabaseProvider = DoubleCheck.provider(new SwitchingProvider<ViabixDatabase>(singletonCImpl, 3));
      this.provideAnviRepositoryProvider = DoubleCheck.provider(new SwitchingProvider<AnviRepository>(singletonCImpl, 1));
      this.provideAuthRepositoryProvider = DoubleCheck.provider(new SwitchingProvider<AuthRepository>(singletonCImpl, 4));
      this.provideProjetoRepositoryProvider = DoubleCheck.provider(new SwitchingProvider<ProjetoRepository>(singletonCImpl, 5));
    }

    @Override
    public void injectViabixApplication(ViabixApplication viabixApplication) {
    }

    @Override
    public TokenManager tokenManager() {
      return provideTokenManagerProvider.get();
    }

    @Override
    public Set<Boolean> getDisableFragmentGetContextFix() {
      return Collections.<Boolean>emptySet();
    }

    @Override
    public ActivityRetainedComponentBuilder retainedComponentBuilder() {
      return new ActivityRetainedCBuilder(singletonCImpl);
    }

    @Override
    public ServiceComponentBuilder serviceComponentBuilder() {
      return new ServiceCBuilder(singletonCImpl);
    }

    private static final class SwitchingProvider<T> implements Provider<T> {
      private final SingletonCImpl singletonCImpl;

      private final int id;

      SwitchingProvider(SingletonCImpl singletonCImpl, int id) {
        this.singletonCImpl = singletonCImpl;
        this.id = id;
      }

      @SuppressWarnings("unchecked")
      @Override
      public T get() {
        switch (id) {
          case 0: // com.viabix.app.utils.TokenManager 
          return (T) AppModule_ProvideTokenManagerFactory.provideTokenManager(ApplicationContextModule_ProvideContextFactory.provideContext(singletonCImpl.applicationContextModule));

          case 1: // com.viabix.app.domain.repository.AnviRepository 
          return (T) AppModule_ProvideAnviRepositoryFactory.provideAnviRepository(singletonCImpl.provideViabixApiServiceProvider.get(), singletonCImpl.provideDatabaseProvider.get(), singletonCImpl.provideTokenManagerProvider.get());

          case 2: // com.viabix.app.data.api.ViabixApiService 
          return (T) AppModule_ProvideViabixApiServiceFactory.provideViabixApiService(ApplicationContextModule_ProvideContextFactory.provideContext(singletonCImpl.applicationContextModule), singletonCImpl.provideTokenManagerProvider.get());

          case 3: // com.viabix.app.data.local.ViabixDatabase 
          return (T) AppModule_ProvideDatabaseFactory.provideDatabase(ApplicationContextModule_ProvideContextFactory.provideContext(singletonCImpl.applicationContextModule));

          case 4: // com.viabix.app.domain.repository.AuthRepository 
          return (T) AppModule_ProvideAuthRepositoryFactory.provideAuthRepository(singletonCImpl.provideViabixApiServiceProvider.get(), singletonCImpl.provideDatabaseProvider.get(), singletonCImpl.provideTokenManagerProvider.get());

          case 5: // com.viabix.app.domain.repository.ProjetoRepository 
          return (T) AppModule_ProvideProjetoRepositoryFactory.provideProjetoRepository(singletonCImpl.provideViabixApiServiceProvider.get(), singletonCImpl.provideDatabaseProvider.get(), singletonCImpl.provideTokenManagerProvider.get());

          default: throw new AssertionError(id);
        }
      }
    }
  }
}
