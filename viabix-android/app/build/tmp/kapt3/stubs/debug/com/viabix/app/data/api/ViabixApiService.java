package com.viabix.app.data.api;

import com.viabix.app.domain.*;
import retrofit2.http.*;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000h\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\b\u0003\n\u0002\u0010\u000e\n\u0002\b\u0002\n\u0002\u0010 \n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0004\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0010\u0002\n\u0002\b\u0005\bf\u0018\u00002\u00020\u0001J\u000e\u0010\u0002\u001a\u00020\u0003H\u00a7@\u00a2\u0006\u0002\u0010\u0004J\u0018\u0010\u0005\u001a\u00020\u00062\b\b\u0001\u0010\u0007\u001a\u00020\bH\u00a7@\u00a2\u0006\u0002\u0010\tJ\u001e\u0010\n\u001a\b\u0012\u0004\u0012\u00020\f0\u000b2\b\b\u0001\u0010\u0007\u001a\u00020\rH\u00a7@\u00a2\u0006\u0002\u0010\u000eJ\u0018\u0010\u000f\u001a\u00020\u00062\b\b\u0001\u0010\u0010\u001a\u00020\u0011H\u00a7@\u00a2\u0006\u0002\u0010\u0012J&\u0010\u0013\u001a\u000e\u0012\n\u0012\b\u0012\u0004\u0012\u00020\u00150\u00140\u000b2\n\b\u0003\u0010\u0016\u001a\u0004\u0018\u00010\u0011H\u00a7@\u00a2\u0006\u0002\u0010\u0012J\u0014\u0010\u0017\u001a\b\u0012\u0004\u0012\u00020\u00180\u000bH\u00a7@\u00a2\u0006\u0002\u0010\u0004J\u0014\u0010\u0019\u001a\b\u0012\u0004\u0012\u00020\u001a0\u000bH\u00a7@\u00a2\u0006\u0002\u0010\u0004J\u0014\u0010\u001b\u001a\b\u0012\u0004\u0012\u00020\u001c0\u000bH\u00a7@\u00a2\u0006\u0002\u0010\u0004J\u001e\u0010\u001d\u001a\b\u0012\u0004\u0012\u00020\f0\u000b2\b\b\u0001\u0010\u001e\u001a\u00020\u0011H\u00a7@\u00a2\u0006\u0002\u0010\u0012J&\u0010\u001f\u001a\u000e\u0012\n\u0012\b\u0012\u0004\u0012\u00020\f0\u00140\u000b2\n\b\u0003\u0010\u0016\u001a\u0004\u0018\u00010\u0011H\u00a7@\u00a2\u0006\u0002\u0010\u0012J\u0018\u0010 \u001a\u00020\u00032\b\b\u0001\u0010\u0007\u001a\u00020!H\u00a7@\u00a2\u0006\u0002\u0010\"J\u000e\u0010#\u001a\u00020$H\u00a7@\u00a2\u0006\u0002\u0010\u0004J\"\u0010%\u001a\u00020\u00062\b\b\u0001\u0010\u0010\u001a\u00020\u00112\b\b\u0001\u0010\u0007\u001a\u00020\bH\u00a7@\u00a2\u0006\u0002\u0010&J(\u0010\'\u001a\b\u0012\u0004\u0012\u00020\f0\u000b2\b\b\u0001\u0010\u001e\u001a\u00020\u00112\b\b\u0001\u0010\u0007\u001a\u00020\rH\u00a7@\u00a2\u0006\u0002\u0010(\u00a8\u0006)"}, d2 = {"Lcom/viabix/app/data/api/ViabixApiService;", "", "checkSession", "Lcom/viabix/app/domain/LoginResponse;", "(Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "createAnvi", "Lcom/viabix/app/domain/AnviResponse;", "request", "Lcom/viabix/app/domain/AnviRequest;", "(Lcom/viabix/app/domain/AnviRequest;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "createProject", "Lcom/viabix/app/data/api/Response;", "Lcom/viabix/app/domain/ProjectEntity;", "Lcom/viabix/app/domain/ProjectRequest;", "(Lcom/viabix/app/domain/ProjectRequest;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "getAnviDetail", "anviId", "", "(Ljava/lang/String;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "getAnvis", "", "Lcom/viabix/app/domain/AnviEntity;", "tenantId", "getCurrentSubscription", "Lcom/viabix/app/data/api/SubscriptionData;", "getDashboardData", "Lcom/viabix/app/data/api/DashboardData;", "getInvoices", "Lcom/viabix/app/data/api/InvoiceData;", "getProjectDetail", "projectId", "getProjects", "login", "Lcom/viabix/app/domain/LoginRequest;", "(Lcom/viabix/app/domain/LoginRequest;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "logout", "", "updateAnvi", "(Ljava/lang/String;Lcom/viabix/app/domain/AnviRequest;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "updateProject", "(Ljava/lang/String;Lcom/viabix/app/domain/ProjectRequest;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "app_debug"})
public abstract interface ViabixApiService {
    
    @retrofit2.http.POST(value = "api/login.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object login(@retrofit2.http.Body()
    @org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.LoginRequest request, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.domain.LoginResponse> $completion);
    
    @retrofit2.http.POST(value = "api/check_session.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object checkSession(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.domain.LoginResponse> $completion);
    
    @retrofit2.http.POST(value = "api/logout.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object logout(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion);
    
    @retrofit2.http.GET(value = "api/anvi_list.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getAnvis(@retrofit2.http.Query(value = "tenant_id")
    @org.jetbrains.annotations.Nullable()
    java.lang.String tenantId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.data.api.Response<java.util.List<com.viabix.app.domain.AnviEntity>>> $completion);
    
    @retrofit2.http.GET(value = "api/anvi_detail.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getAnviDetail(@retrofit2.http.Query(value = "id")
    @org.jetbrains.annotations.NotNull()
    java.lang.String anviId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.domain.AnviResponse> $completion);
    
    @retrofit2.http.POST(value = "api/anvi_create.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object createAnvi(@retrofit2.http.Body()
    @org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.AnviRequest request, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.domain.AnviResponse> $completion);
    
    @retrofit2.http.PUT(value = "api/anvi_update.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object updateAnvi(@retrofit2.http.Query(value = "id")
    @org.jetbrains.annotations.NotNull()
    java.lang.String anviId, @retrofit2.http.Body()
    @org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.AnviRequest request, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.domain.AnviResponse> $completion);
    
    @retrofit2.http.GET(value = "api/projetos_list.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getProjects(@retrofit2.http.Query(value = "tenant_id")
    @org.jetbrains.annotations.Nullable()
    java.lang.String tenantId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.data.api.Response<java.util.List<com.viabix.app.domain.ProjectEntity>>> $completion);
    
    @retrofit2.http.GET(value = "api/projeto_detail.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getProjectDetail(@retrofit2.http.Query(value = "id")
    @org.jetbrains.annotations.NotNull()
    java.lang.String projectId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.data.api.Response<com.viabix.app.domain.ProjectEntity>> $completion);
    
    @retrofit2.http.POST(value = "api/projeto_create.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object createProject(@retrofit2.http.Body()
    @org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.ProjectRequest request, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.data.api.Response<com.viabix.app.domain.ProjectEntity>> $completion);
    
    @retrofit2.http.PUT(value = "api/projeto_update.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object updateProject(@retrofit2.http.Query(value = "id")
    @org.jetbrains.annotations.NotNull()
    java.lang.String projectId, @retrofit2.http.Body()
    @org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.ProjectRequest request, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.data.api.Response<com.viabix.app.domain.ProjectEntity>> $completion);
    
    @retrofit2.http.GET(value = "api/check_session.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getDashboardData(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.data.api.Response<com.viabix.app.data.api.DashboardData>> $completion);
    
    @retrofit2.http.GET(value = "api/subscription_current.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getCurrentSubscription(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.data.api.Response<com.viabix.app.data.api.SubscriptionData>> $completion);
    
    @retrofit2.http.GET(value = "api/billing_invoices.php")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getInvoices(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.data.api.Response<com.viabix.app.data.api.InvoiceData>> $completion);
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 3, xi = 48)
    public static final class DefaultImpls {
    }
}