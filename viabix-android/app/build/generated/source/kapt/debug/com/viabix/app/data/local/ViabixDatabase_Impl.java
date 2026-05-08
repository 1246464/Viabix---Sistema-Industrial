package com.viabix.app.data.local;

import androidx.annotation.NonNull;
import androidx.room.DatabaseConfiguration;
import androidx.room.InvalidationTracker;
import androidx.room.RoomDatabase;
import androidx.room.RoomOpenHelper;
import androidx.room.migration.AutoMigrationSpec;
import androidx.room.migration.Migration;
import androidx.room.util.DBUtil;
import androidx.room.util.TableInfo;
import androidx.sqlite.db.SupportSQLiteDatabase;
import androidx.sqlite.db.SupportSQLiteOpenHelper;
import java.lang.Class;
import java.lang.Override;
import java.lang.String;
import java.lang.SuppressWarnings;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import javax.annotation.processing.Generated;

@Generated("androidx.room.RoomProcessor")
@SuppressWarnings({"unchecked", "deprecation"})
public final class ViabixDatabase_Impl extends ViabixDatabase {
  private volatile AuthTokenDao _authTokenDao;

  private volatile AnviDao _anviDao;

  private volatile ProjectDao _projectDao;

  @Override
  @NonNull
  protected SupportSQLiteOpenHelper createOpenHelper(@NonNull final DatabaseConfiguration config) {
    final SupportSQLiteOpenHelper.Callback _openCallback = new RoomOpenHelper(config, new RoomOpenHelper.Delegate(1) {
      @Override
      public void createAllTables(@NonNull final SupportSQLiteDatabase db) {
        db.execSQL("CREATE TABLE IF NOT EXISTS `auth_tokens` (`id` INTEGER NOT NULL, `token` TEXT NOT NULL, `user_id` TEXT NOT NULL, `tenant_id` TEXT NOT NULL, `expires_at` INTEGER NOT NULL, PRIMARY KEY(`id`))");
        db.execSQL("CREATE TABLE IF NOT EXISTS `anvis` (`id` TEXT NOT NULL, `tenant_id` TEXT NOT NULL, `nome` TEXT NOT NULL, `descricao` TEXT, `status` TEXT NOT NULL, `data_criacao` TEXT NOT NULL, `data_atualizacao` TEXT NOT NULL, `custo_total` REAL NOT NULL, `synced` INTEGER NOT NULL, PRIMARY KEY(`id`))");
        db.execSQL("CREATE TABLE IF NOT EXISTS `projects` (`id` TEXT NOT NULL, `tenant_id` TEXT NOT NULL, `nome` TEXT NOT NULL, `descricao` TEXT, `status` TEXT NOT NULL, `data_inicio` TEXT NOT NULL, `data_conclusao` TEXT, `data_criacao` TEXT NOT NULL, `synced` INTEGER NOT NULL, PRIMARY KEY(`id`))");
        db.execSQL("CREATE TABLE IF NOT EXISTS room_master_table (id INTEGER PRIMARY KEY,identity_hash TEXT)");
        db.execSQL("INSERT OR REPLACE INTO room_master_table (id,identity_hash) VALUES(42, '53c2e01d4062deb7441af4b62670e9dc')");
      }

      @Override
      public void dropAllTables(@NonNull final SupportSQLiteDatabase db) {
        db.execSQL("DROP TABLE IF EXISTS `auth_tokens`");
        db.execSQL("DROP TABLE IF EXISTS `anvis`");
        db.execSQL("DROP TABLE IF EXISTS `projects`");
        final List<? extends RoomDatabase.Callback> _callbacks = mCallbacks;
        if (_callbacks != null) {
          for (RoomDatabase.Callback _callback : _callbacks) {
            _callback.onDestructiveMigration(db);
          }
        }
      }

      @Override
      public void onCreate(@NonNull final SupportSQLiteDatabase db) {
        final List<? extends RoomDatabase.Callback> _callbacks = mCallbacks;
        if (_callbacks != null) {
          for (RoomDatabase.Callback _callback : _callbacks) {
            _callback.onCreate(db);
          }
        }
      }

      @Override
      public void onOpen(@NonNull final SupportSQLiteDatabase db) {
        mDatabase = db;
        internalInitInvalidationTracker(db);
        final List<? extends RoomDatabase.Callback> _callbacks = mCallbacks;
        if (_callbacks != null) {
          for (RoomDatabase.Callback _callback : _callbacks) {
            _callback.onOpen(db);
          }
        }
      }

      @Override
      public void onPreMigrate(@NonNull final SupportSQLiteDatabase db) {
        DBUtil.dropFtsSyncTriggers(db);
      }

      @Override
      public void onPostMigrate(@NonNull final SupportSQLiteDatabase db) {
      }

      @Override
      @NonNull
      public RoomOpenHelper.ValidationResult onValidateSchema(
          @NonNull final SupportSQLiteDatabase db) {
        final HashMap<String, TableInfo.Column> _columnsAuthTokens = new HashMap<String, TableInfo.Column>(5);
        _columnsAuthTokens.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAuthTokens.put("token", new TableInfo.Column("token", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAuthTokens.put("user_id", new TableInfo.Column("user_id", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAuthTokens.put("tenant_id", new TableInfo.Column("tenant_id", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAuthTokens.put("expires_at", new TableInfo.Column("expires_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysAuthTokens = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesAuthTokens = new HashSet<TableInfo.Index>(0);
        final TableInfo _infoAuthTokens = new TableInfo("auth_tokens", _columnsAuthTokens, _foreignKeysAuthTokens, _indicesAuthTokens);
        final TableInfo _existingAuthTokens = TableInfo.read(db, "auth_tokens");
        if (!_infoAuthTokens.equals(_existingAuthTokens)) {
          return new RoomOpenHelper.ValidationResult(false, "auth_tokens(com.viabix.app.domain.AuthTokenEntity).\n"
                  + " Expected:\n" + _infoAuthTokens + "\n"
                  + " Found:\n" + _existingAuthTokens);
        }
        final HashMap<String, TableInfo.Column> _columnsAnvis = new HashMap<String, TableInfo.Column>(9);
        _columnsAnvis.put("id", new TableInfo.Column("id", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAnvis.put("tenant_id", new TableInfo.Column("tenant_id", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAnvis.put("nome", new TableInfo.Column("nome", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAnvis.put("descricao", new TableInfo.Column("descricao", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAnvis.put("status", new TableInfo.Column("status", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAnvis.put("data_criacao", new TableInfo.Column("data_criacao", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAnvis.put("data_atualizacao", new TableInfo.Column("data_atualizacao", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAnvis.put("custo_total", new TableInfo.Column("custo_total", "REAL", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAnvis.put("synced", new TableInfo.Column("synced", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysAnvis = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesAnvis = new HashSet<TableInfo.Index>(0);
        final TableInfo _infoAnvis = new TableInfo("anvis", _columnsAnvis, _foreignKeysAnvis, _indicesAnvis);
        final TableInfo _existingAnvis = TableInfo.read(db, "anvis");
        if (!_infoAnvis.equals(_existingAnvis)) {
          return new RoomOpenHelper.ValidationResult(false, "anvis(com.viabix.app.domain.AnviEntity).\n"
                  + " Expected:\n" + _infoAnvis + "\n"
                  + " Found:\n" + _existingAnvis);
        }
        final HashMap<String, TableInfo.Column> _columnsProjects = new HashMap<String, TableInfo.Column>(9);
        _columnsProjects.put("id", new TableInfo.Column("id", "TEXT", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsProjects.put("tenant_id", new TableInfo.Column("tenant_id", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsProjects.put("nome", new TableInfo.Column("nome", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsProjects.put("descricao", new TableInfo.Column("descricao", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsProjects.put("status", new TableInfo.Column("status", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsProjects.put("data_inicio", new TableInfo.Column("data_inicio", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsProjects.put("data_conclusao", new TableInfo.Column("data_conclusao", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsProjects.put("data_criacao", new TableInfo.Column("data_criacao", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsProjects.put("synced", new TableInfo.Column("synced", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysProjects = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesProjects = new HashSet<TableInfo.Index>(0);
        final TableInfo _infoProjects = new TableInfo("projects", _columnsProjects, _foreignKeysProjects, _indicesProjects);
        final TableInfo _existingProjects = TableInfo.read(db, "projects");
        if (!_infoProjects.equals(_existingProjects)) {
          return new RoomOpenHelper.ValidationResult(false, "projects(com.viabix.app.domain.ProjectEntity).\n"
                  + " Expected:\n" + _infoProjects + "\n"
                  + " Found:\n" + _existingProjects);
        }
        return new RoomOpenHelper.ValidationResult(true, null);
      }
    }, "53c2e01d4062deb7441af4b62670e9dc", "62d3b4903f1d0de457879d737a615674");
    final SupportSQLiteOpenHelper.Configuration _sqliteConfig = SupportSQLiteOpenHelper.Configuration.builder(config.context).name(config.name).callback(_openCallback).build();
    final SupportSQLiteOpenHelper _helper = config.sqliteOpenHelperFactory.create(_sqliteConfig);
    return _helper;
  }

  @Override
  @NonNull
  protected InvalidationTracker createInvalidationTracker() {
    final HashMap<String, String> _shadowTablesMap = new HashMap<String, String>(0);
    final HashMap<String, Set<String>> _viewTables = new HashMap<String, Set<String>>(0);
    return new InvalidationTracker(this, _shadowTablesMap, _viewTables, "auth_tokens","anvis","projects");
  }

  @Override
  public void clearAllTables() {
    super.assertNotMainThread();
    final SupportSQLiteDatabase _db = super.getOpenHelper().getWritableDatabase();
    try {
      super.beginTransaction();
      _db.execSQL("DELETE FROM `auth_tokens`");
      _db.execSQL("DELETE FROM `anvis`");
      _db.execSQL("DELETE FROM `projects`");
      super.setTransactionSuccessful();
    } finally {
      super.endTransaction();
      _db.query("PRAGMA wal_checkpoint(FULL)").close();
      if (!_db.inTransaction()) {
        _db.execSQL("VACUUM");
      }
    }
  }

  @Override
  @NonNull
  protected Map<Class<?>, List<Class<?>>> getRequiredTypeConverters() {
    final HashMap<Class<?>, List<Class<?>>> _typeConvertersMap = new HashMap<Class<?>, List<Class<?>>>();
    _typeConvertersMap.put(AuthTokenDao.class, AuthTokenDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(AnviDao.class, AnviDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(ProjectDao.class, ProjectDao_Impl.getRequiredConverters());
    return _typeConvertersMap;
  }

  @Override
  @NonNull
  public Set<Class<? extends AutoMigrationSpec>> getRequiredAutoMigrationSpecs() {
    final HashSet<Class<? extends AutoMigrationSpec>> _autoMigrationSpecsSet = new HashSet<Class<? extends AutoMigrationSpec>>();
    return _autoMigrationSpecsSet;
  }

  @Override
  @NonNull
  public List<Migration> getAutoMigrations(
      @NonNull final Map<Class<? extends AutoMigrationSpec>, AutoMigrationSpec> autoMigrationSpecs) {
    final List<Migration> _autoMigrations = new ArrayList<Migration>();
    return _autoMigrations;
  }

  @Override
  public AuthTokenDao authTokenDao() {
    if (_authTokenDao != null) {
      return _authTokenDao;
    } else {
      synchronized(this) {
        if(_authTokenDao == null) {
          _authTokenDao = new AuthTokenDao_Impl(this);
        }
        return _authTokenDao;
      }
    }
  }

  @Override
  public AnviDao anviDao() {
    if (_anviDao != null) {
      return _anviDao;
    } else {
      synchronized(this) {
        if(_anviDao == null) {
          _anviDao = new AnviDao_Impl(this);
        }
        return _anviDao;
      }
    }
  }

  @Override
  public ProjectDao projectDao() {
    if (_projectDao != null) {
      return _projectDao;
    } else {
      synchronized(this) {
        if(_projectDao == null) {
          _projectDao = new ProjectDao_Impl(this);
        }
        return _projectDao;
      }
    }
  }
}
