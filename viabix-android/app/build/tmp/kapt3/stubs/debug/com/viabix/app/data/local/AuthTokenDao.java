package com.viabix.app.data.local;

import androidx.room.*;
import com.viabix.app.domain.AnviEntity;
import com.viabix.app.domain.AuthTokenEntity;
import com.viabix.app.domain.ProjectEntity;
import kotlinx.coroutines.flow.Flow;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\"\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0000\n\u0002\u0010\u0002\n\u0002\b\u0003\n\u0002\u0018\u0002\n\u0002\b\u0003\n\u0002\u0018\u0002\n\u0002\b\u0002\bg\u0018\u00002\u00020\u0001J\u000e\u0010\u0002\u001a\u00020\u0003H\u00a7@\u00a2\u0006\u0002\u0010\u0004J\u0016\u0010\u0005\u001a\u00020\u00032\u0006\u0010\u0006\u001a\u00020\u0007H\u00a7@\u00a2\u0006\u0002\u0010\bJ\u0010\u0010\t\u001a\u0004\u0018\u00010\u0007H\u00a7@\u00a2\u0006\u0002\u0010\u0004J\u0010\u0010\n\u001a\n\u0012\u0006\u0012\u0004\u0018\u00010\u00070\u000bH\'J\u0016\u0010\f\u001a\u00020\u00032\u0006\u0010\u0006\u001a\u00020\u0007H\u00a7@\u00a2\u0006\u0002\u0010\b\u00a8\u0006\r"}, d2 = {"Lcom/viabix/app/data/local/AuthTokenDao;", "", "clearAllTokens", "", "(Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "deleteToken", "token", "Lcom/viabix/app/domain/AuthTokenEntity;", "(Lcom/viabix/app/domain/AuthTokenEntity;Lkotlin/coroutines/Continuation;)Ljava/lang/Object;", "getToken", "getTokenFlow", "Lkotlinx/coroutines/flow/Flow;", "insertToken", "app_debug"})
@androidx.room.Dao()
public abstract interface AuthTokenDao {
    
    @androidx.room.Insert(onConflict = 1)
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object insertToken(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.AuthTokenEntity token, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion);
    
    @androidx.room.Query(value = "SELECT * FROM auth_tokens WHERE id = 1")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object getToken(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super com.viabix.app.domain.AuthTokenEntity> $completion);
    
    @androidx.room.Query(value = "SELECT * FROM auth_tokens WHERE id = 1")
    @org.jetbrains.annotations.NotNull()
    public abstract kotlinx.coroutines.flow.Flow<com.viabix.app.domain.AuthTokenEntity> getTokenFlow();
    
    @androidx.room.Delete()
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object deleteToken(@org.jetbrains.annotations.NotNull()
    com.viabix.app.domain.AuthTokenEntity token, @org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion);
    
    @androidx.room.Query(value = "DELETE FROM auth_tokens")
    @org.jetbrains.annotations.Nullable()
    public abstract java.lang.Object clearAllTokens(@org.jetbrains.annotations.NotNull()
    kotlin.coroutines.Continuation<? super kotlin.Unit> $completion);
}