package com.viabix.app.data.local;

import androidx.room.*;
import com.viabix.app.domain.AnviEntity;
import com.viabix.app.domain.AuthTokenEntity;
import com.viabix.app.domain.ProjectEntity;
import kotlinx.coroutines.flow.Flow;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000,\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0000\n\u0002\u0010\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0003\n\u0002\u0010\u000e\n\u0002\b\u0005\n\u0002\u0018\u0002\n\u0002\u0010 \n\u0002\b\t\bg\u0018\u00002\u00020\u0001J\u0016\u0010\u0002\u001a\u00020\u00032\u0006\u0010\u0004\u001a\u00020\u0005H\u00a7@\u00a2\u0006\u0002\u0010\u0006J\u0016\u0010\u0007\u001a\u00020\u00032\u0006\u0010\b\u001a\u00020\tH\u00a7@\u00a2\u0006\u0002\u0010\nJ\u0016\u0010\u000b\u001a\u00020\u00032\u0006\u0010\f\u001a\u00020\tH\u00a7@\u00a2\u0006\u0002\u0010\nJ\u0018\u0010\r\u001a\u0004\u0018\u00010\u00052\u0006\u0010\b\u001a\u00020\tH\u00a7@\u00a2\u0006\u0002\u0010\nJ\u001c\u0010\u000e\u001a\u000e\u0012\n\u0012\b\u0012\u0004\u0012\u00020\u00050\u00100\u000f2\u0006\u0010\f\u001a\u00020\tH\'J\u001c\u0010\u0011\u001a\b\u0012\u0004\u0012\u00020\u00050\u00102\u0006\u0010\f\u001a\u00020\tH\u00a7@\u00a2\u0006\u0002\u0010\nJ\u0014\u0010\u0012\u001a\b\u0012\u0004\u0012\u00020\u00050\u0010H\u00a7@\u00a2\u0006\u0002\u0010\u0013J\u0016\u0010\u0014\u001a\u00020\u00032\u0006\u0010\u0004\u001a\u00020\u0005H\u00a7@\u00a2\u0006\u0002\u0010\u0006J\u001c\u0010\u0015\u001a\u00020\u00032\f\u0010\u0016\u001a\b\u0012\u0004\u0012\u00020\u00050\u0010H\u00a7@\u00a2\u0006\u0002\u0010\u0017J\u0016\u0010\u0018\u001a\u00020\u00032\u0006\u0010\u0004\u001a\u00020\u0005H\u00a7@\u00a2\u0006\u0002\u0010\u0006\u00a8\u0006\u0019"}, d2 = {"Lcom/viabix/app/data/local/AnviDao;", "", "deleteAnvi", "", "anvi", "Lcom/viabix/app/domain/AnviEntity;", "(Lcom/viabix/app/domain/AnviEntity;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "deleteAnviById", "anviId", "", "(Ljava/lang/String;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "deleteAnvisByTenant", "tenantId", "getAnviById", "getAnvisByTenant", "Lkotlinx/coroutines/flow/Flow;", "", "getAnvisByTenantSync", "getUnsyncedAnvis", "(Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "insertAnvi", "insertAnvis", "anvis", "(Ljava/util/List;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "updateAnvi", "app_debug"})
@androidx.room.Dao()
public abstract interface AnviDao {
    
    @androidx.room.Insert(onConflict = 1)
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object insertAnvi(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.AnviEntity anvi, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion);
    
    @androidx.room.Insert(onConflict = 1)
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object insertAnvis(@org.jetbrains.annotations.NotNull()
    java.util.List<com.viabix.app.domain.AnviEntity> anvis, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion);
    
    @androidx.room.Query(value = "SELECT * FROM anvis WHERE tenant_id = :tenantId ORDER BY data_criacao DESC")
    @org.jetbrains.annotations.NotNull()
    public abstract kotlinx.coroutines.flow.Flow<java.util.List<com.viabix.app.domain.AnviEntity>> getAnvisByTenant(@org.jetbrains.annotations.NotNull()
    java.lang.String tenantId);
    
    @androidx.room.Query(value = "SELECT * FROM anvis WHERE tenant_id = :tenantId ORDER BY data_criacao DESC")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getAnvisByTenantSync(@org.jetbrains.annotations.NotNull()
    java.lang.String tenantId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.util.List<com.viabix.app.domain.AnviEntity>> $completion);
    
    @androidx.room.Query(value = "SELECT * FROM anvis WHERE id = :anviId")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getAnviById(@org.jetbrains.annotations.NotNull()
    java.lang.String anviId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.domain.AnviEntity> $completion);
    
    @androidx.room.Query(value = "SELECT * FROM anvis WHERE synced = 0")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getUnsyncedAnvis(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super java.util.List<com.viabix.app.domain.AnviEntity>> $completion);
    
    @androidx.room.Update()
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object updateAnvi(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.AnviEntity anvi, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion);
    
    @androidx.room.Delete()
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object deleteAnvi(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.AnviEntity anvi, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion);
    
    @androidx.room.Query(value = "DELETE FROM anvis WHERE id = :anviId")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object deleteAnviById(@org.jetbrains.annotations.NotNull()
    java.lang.String anviId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion);
    
    @androidx.room.Query(value = "DELETE FROM anvis WHERE tenant_id = :tenantId")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object deleteAnvisByTenant(@org.jetbrains.annotations.NotNull()
    java.lang.String tenantId, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion);
}