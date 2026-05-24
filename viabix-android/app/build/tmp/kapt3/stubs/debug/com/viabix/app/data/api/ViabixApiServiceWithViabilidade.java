package com.viabix.app.data.api;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\"\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0000\n\u0002\u0018\u0002\n\u0002\u0010$\n\u0002\u0010\u000e\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0002\b\u0006\bf\u0018\u00002\u00020\u0001J \u0010\u0002\u001a\u0014\u0012\u0010\u0012\u000e\u0012\u0004\u0012\u00020\u0005\u0012\u0004\u0012\u00020\u00010\u00040\u0003H\u00a7@\u00a2\u0006\u0002\u0010\u0006J\u001e\u0010\u0007\u001a\b\u0012\u0004\u0012\u00020\b0\u00032\b\b\u0001\u0010\t\u001a\u00020\u0005H\u00a7@\u00a2\u0006\u0002\u0010\nJ4\u0010\u000b\u001a\u0014\u0012\u0010\u0012\u000e\u0012\u0004\u0012\u00020\u0005\u0012\u0004\u0012\u00020\u00010\u00040\u00032\b\b\u0001\u0010\u000b\u001a\u00020\u00052\b\b\u0001\u0010\f\u001a\u00020\u0005H\u00a7@\u00a2\u0006\u0002\u0010\r\u00a8\u0006\u000e"}, d2 = {"Lcom/viabix/app/data/api/ViabixApiServiceWithViabilidade;", "", "getAnviList", "Lretrofit2/Response;", "", "", "(Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "getDashboardViabilidade", "Lcom/viabix/app/presentation/screens/viabilidade/DashboardViabilidadeResponse;", "anviId", "(Ljava/lang/String;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "login", "password", "(Ljava/lang/String;Ljava/lang/String;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "app_debug"})
public abstract interface ViabixApiServiceWithViabilidade {
    
    @retrofit2.http.FormUrlEncoded()
    @retrofit2.http.POST(value = "api/login.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object login(@retrofit2.http.Field(value = "login")
    @org.jetbrains.annotations.NotNull()
    java.lang.String login, @retrofit2.http.Field(value = "password")
    @org.jetbrains.annotations.NotNull()
    java.lang.String password, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super retrofit2.Response<java.util.Map<java.lang.String, java.lang.Object>>> $completion);
    
    @retrofit2.http.GET(value = "api/anvi_list.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getAnviList(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super retrofit2.Response<java.util.Map<java.lang.String, java.lang.Object>>> $completion);
    
    @retrofit2.http.GET(value = "api/dashboard_viabilidade_simple.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getDashboardViabilidade(@retrofit2.http.Query(value = "anvi_id")
    @org.jetbrains.annotations.NotNull()
    java.lang.String anviId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super retrofit2.Response<com.viabix.app.presentation.screens.viabilidade.DashboardViabilidadeResponse>> $completion);
}