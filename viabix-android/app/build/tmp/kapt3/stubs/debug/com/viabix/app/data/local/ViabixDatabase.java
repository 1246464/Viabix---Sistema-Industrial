package com.viabix.app.data.local;

import android.content.Context;
import androidx.room.Database;
import androidx.room.Room;
import androidx.room.RoomDatabase;
import com.viabix.app.domain.AnviEntity;
import com.viabix.app.domain.AuthTokenEntity;
import com.viabix.app.domain.ProjectEntity;

@kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000 \n\u0002\u0018\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0000\n\u0002\u0018\u0002\n\u0002\b\u0002\b\'\u0018\u0000 \t2\u00020\u0001:\u0001\tB\u0005\u00a2\u0006\u0002\u0010\u0002J\b\u0010\u0003\u001a\u00020\u0004H&J\b\u0010\u0005\u001a\u00020\u0006H&J\b\u0010\u0007\u001a\u00020\bH&\u00a8\u0006\n"}, d2 = {"Lcom/viabix/app/data/local/ViabixDatabase;", "Landroidx/room/RoomDatabase;", "()V", "anviDao", "Lcom/viabix/app/data/local/AnviDao;", "authTokenDao", "Lcom/viabix/app/data/local/AuthTokenDao;", "projectDao", "Lcom/viabix/app/data/local/ProjectDao;", "Companion", "app_debug"})
@androidx.room.Database(entities = {com.viabix.app.domain.AuthTokenEntity.class, com.viabix.app.domain.AnviEntity.class, com.viabix.app.domain.ProjectEntity.class}, version = 1, exportSchema = false)
public abstract class ViabixDatabase extends androidx.room.RoomDatabase {
    @kotlin.jvm.Volatile()
    @org.jetbrains.annotations.Nullable()
    private static volatile com.viabix.app.data.local.ViabixDatabase instance;
    @org.jetbrains.annotations.NotNull()
    public static final com.viabix.app.data.local.ViabixDatabase.Companion Companion = null;
    
    public ViabixDatabase() {
        super();
    }
    
    @org.jetbrains.annotations.NotNull()
    public abstract com.viabix.app.data.local.AuthTokenDao authTokenDao();
    
    @org.jetbrains.annotations.NotNull()
    public abstract com.viabix.app.data.local.AnviDao anviDao();
    
    @org.jetbrains.annotations.NotNull()
    public abstract com.viabix.app.data.local.ProjectDao projectDao();
    
    @kotlin.Metadata(mv = {1, 9, 0}, k = 1, xi = 48, d1 = {"\u0000\u001a\n\u0002\u0018\u0002\n\u0002\u0010\u0000\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\b\u0086\u0003\u0018\u00002\u00020\u0001B\u0007\b\u0002\u00a2\u0006\u0002\u0010\u0002J\u000e\u0010\u0005\u001a\u00020\u00042\u0006\u0010\u0006\u001a\u00020\u0007R\u0010\u0010\u0003\u001a\u0004\u0018\u00010\u0004X\u0082\u000e\u00a2\u0006\u0002\n\u0000\u00a8\u0006\b"}, d2 = {"Lcom/viabix/app/data/local/ViabixDatabase$Companion;", "", "()V", "instance", "Lcom/viabix/app/data/local/ViabixDatabase;", "getInstance", "context", "Landroid/content/Context;", "app_debug"})
    public static final class Companion {
        
        private Companion() {
            super();
        }
        
        @org.jetbrains.annotations.NotNull()
        public final com.viabix.app.data.local.ViabixDatabase getInstance(@org.jetbrains.annotations.NotNull()
        android.content.Context context) {
            return null;
        }
    }
}