package com.viabix.app.presentation.navigation;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u00008\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0000\n\u0002\u0010\u000e\n\u0002\b\f\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0000\b6\u0018\u00002\u00020\u0001:\t\u0007\b\t\n\u000b\f\r\u000e\u000fB\u000f\b\u0004\u0012\u0006\u0010\u0002\u001a\u00020\u0003\u00a2\u0006\u0002\u0010\u0004R\u0011\u0010\u0002\u001a\u00020\u0003\u00a2\u0006\b\n\u0000\u001a\u0004\b\u0005\u0010\u0006\u0082\u0001\t\u0010\u0011\u0012\u0013\u0014\u0015\u0016\u0017\u0018\u00a8\u0006\u0019"}, d2 = {"Lcom/viabix/app/presentation/navigation/Screen;", "", "route", "", "(Ljava/lang/String;)V", "getRoute", "()Ljava/lang/String;", "AnviDetail", "AnviList", "BillingScreen", "Home", "Login", "ProjetoDetail", "ProjetosList", "Settings", "ViabilidadeDashboard", "Lcom/viabix/app/presentation/navigation/Screen$AnviDetail;", "Lcom/viabix/app/presentation/navigation/Screen$AnviList;", "Lcom/viabix/app/presentation/navigation/Screen$BillingScreen;", "Lcom/viabix/app/presentation/navigation/Screen$Home;", "Lcom/viabix/app/presentation/navigation/Screen$Login;", "Lcom/viabix/app/presentation/navigation/Screen$ProjetoDetail;", "Lcom/viabix/app/presentation/navigation/Screen$ProjetosList;", "Lcom/viabix/app/presentation/navigation/Screen$Settings;", "Lcom/viabix/app/presentation/navigation/Screen$ViabilidadeDashboard;", "app_debug"})
public abstract class Screen {
    @org.jetbrains.annotations.NotNull()
    private final java.lang.String route = null;
    
    private Screen(java.lang.String route) {
        super();
    }
    
    @org.jetbrains.annotations.NotNull()
    public final java.lang.String getRoute() {
        return null;
    }
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\u0014\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0010\u000e\n\u0002\b\u0002\b\u00c6\u0002\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002J\u000e\u0010\u0003\u001a\u00020\u00042\u0006\u0010\u0005\u001a\u00020\u0004\u00a8\u0006\u0006"}, d2 = {"Lcom/viabix/app/presentation/navigation/Screen$AnviDetail;", "Lcom/viabix/app/presentation/navigation/Screen;", "()V", "createRoute", "", "anviId", "app_debug"})
    public static final class AnviDetail extends com.viabix.app.presentation.navigation.Screen {
        @org.jetbrains.annotations.NotNull()
        public static final com.viabix.app.presentation.navigation.Screen.AnviDetail INSTANCE = null;
        
        private AnviDetail() {
        }
        
        @org.jetbrains.annotations.NotNull()
        public final java.lang.String createRoute(@org.jetbrains.annotations.NotNull()
        java.lang.String anviId) {
            return null;
        }
    }
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\f\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\b\u00c6\u0002\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002\u00a8\u0006\u0003"}, d2 = {"Lcom/viabix/app/presentation/navigation/Screen$AnviList;", "Lcom/viabix/app/presentation/navigation/Screen;", "()V", "app_debug"})
    public static final class AnviList extends com.viabix.app.presentation.navigation.Screen {
        @org.jetbrains.annotations.NotNull()
        public static final com.viabix.app.presentation.navigation.Screen.AnviList INSTANCE = null;
        
        private AnviList() {
        }
    }
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\f\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\b\u00c6\u0002\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002\u00a8\u0006\u0003"}, d2 = {"Lcom/viabix/app/presentation/navigation/Screen$BillingScreen;", "Lcom/viabix/app/presentation/navigation/Screen;", "()V", "app_debug"})
    public static final class BillingScreen extends com.viabix.app.presentation.navigation.Screen {
        @org.jetbrains.annotations.NotNull()
        public static final com.viabix.app.presentation.navigation.Screen.BillingScreen INSTANCE = null;
        
        private BillingScreen() {
        }
    }
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\f\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\b\u00c6\u0002\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002\u00a8\u0006\u0003"}, d2 = {"Lcom/viabix/app/presentation/navigation/Screen$Home;", "Lcom/viabix/app/presentation/navigation/Screen;", "()V", "app_debug"})
    public static final class Home extends com.viabix.app.presentation.navigation.Screen {
        @org.jetbrains.annotations.NotNull()
        public static final com.viabix.app.presentation.navigation.Screen.Home INSTANCE = null;
        
        private Home() {
        }
    }
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\f\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\b\u00c6\u0002\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002\u00a8\u0006\u0003"}, d2 = {"Lcom/viabix/app/presentation/navigation/Screen$Login;", "Lcom/viabix/app/presentation/navigation/Screen;", "()V", "app_debug"})
    public static final class Login extends com.viabix.app.presentation.navigation.Screen {
        @org.jetbrains.annotations.NotNull()
        public static final com.viabix.app.presentation.navigation.Screen.Login INSTANCE = null;
        
        private Login() {
        }
    }
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\u0014\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0010\u000e\n\u0002\b\u0002\b\u00c6\u0002\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002J\u000e\u0010\u0003\u001a\u00020\u00042\u0006\u0010\u0005\u001a\u00020\u0004\u00a8\u0006\u0006"}, d2 = {"Lcom/viabix/app/presentation/navigation/Screen$ProjetoDetail;", "Lcom/viabix/app/presentation/navigation/Screen;", "()V", "createRoute", "", "projectId", "app_debug"})
    public static final class ProjetoDetail extends com.viabix.app.presentation.navigation.Screen {
        @org.jetbrains.annotations.NotNull()
        public static final com.viabix.app.presentation.navigation.Screen.ProjetoDetail INSTANCE = null;
        
        private ProjetoDetail() {
        }
        
        @org.jetbrains.annotations.NotNull()
        public final java.lang.String createRoute(@org.jetbrains.annotations.NotNull()
        java.lang.String projectId) {
            return null;
        }
    }
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\f\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\b\u00c6\u0002\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002\u00a8\u0006\u0003"}, d2 = {"Lcom/viabix/app/presentation/navigation/Screen$ProjetosList;", "Lcom/viabix/app/presentation/navigation/Screen;", "()V", "app_debug"})
    public static final class ProjetosList extends com.viabix.app.presentation.navigation.Screen {
        @org.jetbrains.annotations.NotNull()
        public static final com.viabix.app.presentation.navigation.Screen.ProjetosList INSTANCE = null;
        
        private ProjetosList() {
        }
    }
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\f\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\b\u00c6\u0002\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002\u00a8\u0006\u0003"}, d2 = {"Lcom/viabix/app/presentation/navigation/Screen$Settings;", "Lcom/viabix/app/presentation/navigation/Screen;", "()V", "app_debug"})
    public static final class Settings extends com.viabix.app.presentation.navigation.Screen {
        @org.jetbrains.annotations.NotNull()
        public static final com.viabix.app.presentation.navigation.Screen.Settings INSTANCE = null;
        
        private Settings() {
        }
    }
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\f\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\b\u00c6\u0002\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002\u00a8\u0006\u0003"}, d2 = {"Lcom/viabix/app/presentation/navigation/Screen$ViabilidadeDashboard;", "Lcom/viabix/app/presentation/navigation/Screen;", "()V", "app_debug"})
    public static final class ViabilidadeDashboard extends com.viabix.app.presentation.navigation.Screen {
        @org.jetbrains.annotations.NotNull()
        public static final com.viabix.app.presentation.navigation.Screen.ViabilidadeDashboard INSTANCE = null;
        
        private ViabilidadeDashboard() {
        }
    }
}