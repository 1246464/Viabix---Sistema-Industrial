package com.viabix.app.data.local;

import android.database.Cursor;
import android.os.CancellationSignal;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.room.CoroutinesRoom;
import androidx.room.EntityDeletionOrUpdateAdapter;
import androidx.room.EntityInsertionAdapter;
import androidx.room.RoomDatabase;
import androidx.room.RoomSQLiteQuery;
import androidx.room.SharedSQLiteStatement;
import androidx.room.util.CursorUtil;
import androidx.room.util.DBUtil;
import androidx.sqlite.db.SupportSQLiteStatement;
import com.viabix.app.domain.ProjectEntity;
import java.lang.Class;
import java.lang.Exception;
import java.lang.Object;
import java.lang.Override;
import java.lang.String;
import java.lang.SuppressWarnings;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.concurrent.Callable;
import javax.annotation.processing.Generated;
import kotlin.Unit;
import kotlin.coroutines.Continuation;
import kotlinx.coroutines.flow.Flow;

@Generated("androidx.room.RoomProcessor")
@SuppressWarnings({"unchecked", "deprecation"})
public final class ProjectDao_Impl implements ProjectDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<ProjectEntity> __insertionAdapterOfProjectEntity;

  private final EntityDeletionOrUpdateAdapter<ProjectEntity> __deletionAdapterOfProjectEntity;

  private final EntityDeletionOrUpdateAdapter<ProjectEntity> __updateAdapterOfProjectEntity;

  private final SharedSQLiteStatement __preparedStmtOfDeleteProjectById;

  private final SharedSQLiteStatement __preparedStmtOfDeleteProjectsByTenant;

  public ProjectDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfProjectEntity = new EntityInsertionAdapter<ProjectEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR REPLACE INTO `projects` (`id`,`tenant_id`,`nome`,`descricao`,`status`,`data_inicio`,`data_conclusao`,`data_criacao`,`synced`) VALUES (?,?,?,?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final ProjectEntity entity) {
        if (entity.getId() == null) {
          statement.bindNull(1);
        } else {
          statement.bindString(1, entity.getId());
        }
        if (entity.getTenant_id() == null) {
          statement.bindNull(2);
        } else {
          statement.bindString(2, entity.getTenant_id());
        }
        if (entity.getNome() == null) {
          statement.bindNull(3);
        } else {
          statement.bindString(3, entity.getNome());
        }
        if (entity.getDescricao() == null) {
          statement.bindNull(4);
        } else {
          statement.bindString(4, entity.getDescricao());
        }
        if (entity.getStatus() == null) {
          statement.bindNull(5);
        } else {
          statement.bindString(5, entity.getStatus());
        }
        if (entity.getData_inicio() == null) {
          statement.bindNull(6);
        } else {
          statement.bindString(6, entity.getData_inicio());
        }
        if (entity.getData_conclusao() == null) {
          statement.bindNull(7);
        } else {
          statement.bindString(7, entity.getData_conclusao());
        }
        if (entity.getData_criacao() == null) {
          statement.bindNull(8);
        } else {
          statement.bindString(8, entity.getData_criacao());
        }
        final int _tmp = entity.getSynced() ? 1 : 0;
        statement.bindLong(9, _tmp);
      }
    };
    this.__deletionAdapterOfProjectEntity = new EntityDeletionOrUpdateAdapter<ProjectEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "DELETE FROM `projects` WHERE `id` = ?";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final ProjectEntity entity) {
        if (entity.getId() == null) {
          statement.bindNull(1);
        } else {
          statement.bindString(1, entity.getId());
        }
      }
    };
    this.__updateAdapterOfProjectEntity = new EntityDeletionOrUpdateAdapter<ProjectEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "UPDATE OR ABORT `projects` SET `id` = ?,`tenant_id` = ?,`nome` = ?,`descricao` = ?,`status` = ?,`data_inicio` = ?,`data_conclusao` = ?,`data_criacao` = ?,`synced` = ? WHERE `id` = ?";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final ProjectEntity entity) {
        if (entity.getId() == null) {
          statement.bindNull(1);
        } else {
          statement.bindString(1, entity.getId());
        }
        if (entity.getTenant_id() == null) {
          statement.bindNull(2);
        } else {
          statement.bindString(2, entity.getTenant_id());
        }
        if (entity.getNome() == null) {
          statement.bindNull(3);
        } else {
          statement.bindString(3, entity.getNome());
        }
        if (entity.getDescricao() == null) {
          statement.bindNull(4);
        } else {
          statement.bindString(4, entity.getDescricao());
        }
        if (entity.getStatus() == null) {
          statement.bindNull(5);
        } else {
          statement.bindString(5, entity.getStatus());
        }
        if (entity.getData_inicio() == null) {
          statement.bindNull(6);
        } else {
          statement.bindString(6, entity.getData_inicio());
        }
        if (entity.getData_conclusao() == null) {
          statement.bindNull(7);
        } else {
          statement.bindString(7, entity.getData_conclusao());
        }
        if (entity.getData_criacao() == null) {
          statement.bindNull(8);
        } else {
          statement.bindString(8, entity.getData_criacao());
        }
        final int _tmp = entity.getSynced() ? 1 : 0;
        statement.bindLong(9, _tmp);
        if (entity.getId() == null) {
          statement.bindNull(10);
        } else {
          statement.bindString(10, entity.getId());
        }
      }
    };
    this.__preparedStmtOfDeleteProjectById = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM projects WHERE id = ?";
        return _query;
      }
    };
    this.__preparedStmtOfDeleteProjectsByTenant = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM projects WHERE tenant_id = ?";
        return _query;
      }
    };
  }

  @Override
  public Object insertProject(final ProjectEntity project,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __insertionAdapterOfProjectEntity.insert(project);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object insertProjects(final List<ProjectEntity> projects,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __insertionAdapterOfProjectEntity.insert(projects);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteProject(final ProjectEntity project,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __deletionAdapterOfProjectEntity.handle(project);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object updateProject(final ProjectEntity project,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __updateAdapterOfProjectEntity.handle(project);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteProjectById(final String projectId,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfDeleteProjectById.acquire();
        int _argIndex = 1;
        if (projectId == null) {
          _stmt.bindNull(_argIndex);
        } else {
          _stmt.bindString(_argIndex, projectId);
        }
        try {
          __db.beginTransaction();
          try {
            _stmt.executeUpdateDelete();
            __db.setTransactionSuccessful();
            return Unit.INSTANCE;
          } finally {
            __db.endTransaction();
          }
        } finally {
          __preparedStmtOfDeleteProjectById.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteProjectsByTenant(final String tenantId,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfDeleteProjectsByTenant.acquire();
        int _argIndex = 1;
        if (tenantId == null) {
          _stmt.bindNull(_argIndex);
        } else {
          _stmt.bindString(_argIndex, tenantId);
        }
        try {
          __db.beginTransaction();
          try {
            _stmt.executeUpdateDelete();
            __db.setTransactionSuccessful();
            return Unit.INSTANCE;
          } finally {
            __db.endTransaction();
          }
        } finally {
          __preparedStmtOfDeleteProjectsByTenant.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Flow<List<ProjectEntity>> getProjectsByTenant(final String tenantId) {
    final String _sql = "SELECT * FROM projects WHERE tenant_id = ? ORDER BY data_criacao DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    if (tenantId == null) {
      _statement.bindNull(_argIndex);
    } else {
      _statement.bindString(_argIndex, tenantId);
    }
    return CoroutinesRoom.createFlow(__db, false, new String[] {"projects"}, new Callable<List<ProjectEntity>>() {
      @Override
      @NonNull
      public List<ProjectEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfTenantId = CursorUtil.getColumnIndexOrThrow(_cursor, "tenant_id");
          final int _cursorIndexOfNome = CursorUtil.getColumnIndexOrThrow(_cursor, "nome");
          final int _cursorIndexOfDescricao = CursorUtil.getColumnIndexOrThrow(_cursor, "descricao");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfDataInicio = CursorUtil.getColumnIndexOrThrow(_cursor, "data_inicio");
          final int _cursorIndexOfDataConclusao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_conclusao");
          final int _cursorIndexOfDataCriacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_criacao");
          final int _cursorIndexOfSynced = CursorUtil.getColumnIndexOrThrow(_cursor, "synced");
          final List<ProjectEntity> _result = new ArrayList<ProjectEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final ProjectEntity _item;
            final String _tmpId;
            if (_cursor.isNull(_cursorIndexOfId)) {
              _tmpId = null;
            } else {
              _tmpId = _cursor.getString(_cursorIndexOfId);
            }
            final String _tmpTenant_id;
            if (_cursor.isNull(_cursorIndexOfTenantId)) {
              _tmpTenant_id = null;
            } else {
              _tmpTenant_id = _cursor.getString(_cursorIndexOfTenantId);
            }
            final String _tmpNome;
            if (_cursor.isNull(_cursorIndexOfNome)) {
              _tmpNome = null;
            } else {
              _tmpNome = _cursor.getString(_cursorIndexOfNome);
            }
            final String _tmpDescricao;
            if (_cursor.isNull(_cursorIndexOfDescricao)) {
              _tmpDescricao = null;
            } else {
              _tmpDescricao = _cursor.getString(_cursorIndexOfDescricao);
            }
            final String _tmpStatus;
            if (_cursor.isNull(_cursorIndexOfStatus)) {
              _tmpStatus = null;
            } else {
              _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            }
            final String _tmpData_inicio;
            if (_cursor.isNull(_cursorIndexOfDataInicio)) {
              _tmpData_inicio = null;
            } else {
              _tmpData_inicio = _cursor.getString(_cursorIndexOfDataInicio);
            }
            final String _tmpData_conclusao;
            if (_cursor.isNull(_cursorIndexOfDataConclusao)) {
              _tmpData_conclusao = null;
            } else {
              _tmpData_conclusao = _cursor.getString(_cursorIndexOfDataConclusao);
            }
            final String _tmpData_criacao;
            if (_cursor.isNull(_cursorIndexOfDataCriacao)) {
              _tmpData_criacao = null;
            } else {
              _tmpData_criacao = _cursor.getString(_cursorIndexOfDataCriacao);
            }
            final boolean _tmpSynced;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfSynced);
            _tmpSynced = _tmp != 0;
            _item = new ProjectEntity(_tmpId,_tmpTenant_id,_tmpNome,_tmpDescricao,_tmpStatus,_tmpData_inicio,_tmpData_conclusao,_tmpData_criacao,_tmpSynced);
            _result.add(_item);
          }
          return _result;
        } finally {
          _cursor.close();
        }
      }

      @Override
      protected void finalize() {
        _statement.release();
      }
    });
  }

  @Override
  public Object getProjectsByTenantSync(final String tenantId,
      final Continuation<? super List<ProjectEntity>> $completion) {
    final String _sql = "SELECT * FROM projects WHERE tenant_id = ? ORDER BY data_criacao DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    if (tenantId == null) {
      _statement.bindNull(_argIndex);
    } else {
      _statement.bindString(_argIndex, tenantId);
    }
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<ProjectEntity>>() {
      @Override
      @NonNull
      public List<ProjectEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfTenantId = CursorUtil.getColumnIndexOrThrow(_cursor, "tenant_id");
          final int _cursorIndexOfNome = CursorUtil.getColumnIndexOrThrow(_cursor, "nome");
          final int _cursorIndexOfDescricao = CursorUtil.getColumnIndexOrThrow(_cursor, "descricao");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfDataInicio = CursorUtil.getColumnIndexOrThrow(_cursor, "data_inicio");
          final int _cursorIndexOfDataConclusao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_conclusao");
          final int _cursorIndexOfDataCriacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_criacao");
          final int _cursorIndexOfSynced = CursorUtil.getColumnIndexOrThrow(_cursor, "synced");
          final List<ProjectEntity> _result = new ArrayList<ProjectEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final ProjectEntity _item;
            final String _tmpId;
            if (_cursor.isNull(_cursorIndexOfId)) {
              _tmpId = null;
            } else {
              _tmpId = _cursor.getString(_cursorIndexOfId);
            }
            final String _tmpTenant_id;
            if (_cursor.isNull(_cursorIndexOfTenantId)) {
              _tmpTenant_id = null;
            } else {
              _tmpTenant_id = _cursor.getString(_cursorIndexOfTenantId);
            }
            final String _tmpNome;
            if (_cursor.isNull(_cursorIndexOfNome)) {
              _tmpNome = null;
            } else {
              _tmpNome = _cursor.getString(_cursorIndexOfNome);
            }
            final String _tmpDescricao;
            if (_cursor.isNull(_cursorIndexOfDescricao)) {
              _tmpDescricao = null;
            } else {
              _tmpDescricao = _cursor.getString(_cursorIndexOfDescricao);
            }
            final String _tmpStatus;
            if (_cursor.isNull(_cursorIndexOfStatus)) {
              _tmpStatus = null;
            } else {
              _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            }
            final String _tmpData_inicio;
            if (_cursor.isNull(_cursorIndexOfDataInicio)) {
              _tmpData_inicio = null;
            } else {
              _tmpData_inicio = _cursor.getString(_cursorIndexOfDataInicio);
            }
            final String _tmpData_conclusao;
            if (_cursor.isNull(_cursorIndexOfDataConclusao)) {
              _tmpData_conclusao = null;
            } else {
              _tmpData_conclusao = _cursor.getString(_cursorIndexOfDataConclusao);
            }
            final String _tmpData_criacao;
            if (_cursor.isNull(_cursorIndexOfDataCriacao)) {
              _tmpData_criacao = null;
            } else {
              _tmpData_criacao = _cursor.getString(_cursorIndexOfDataCriacao);
            }
            final boolean _tmpSynced;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfSynced);
            _tmpSynced = _tmp != 0;
            _item = new ProjectEntity(_tmpId,_tmpTenant_id,_tmpNome,_tmpDescricao,_tmpStatus,_tmpData_inicio,_tmpData_conclusao,_tmpData_criacao,_tmpSynced);
            _result.add(_item);
          }
          return _result;
        } finally {
          _cursor.close();
          _statement.release();
        }
      }
    }, $completion);
  }

  @Override
  public Object getProjectById(final String projectId,
      final Continuation<? super ProjectEntity> $completion) {
    final String _sql = "SELECT * FROM projects WHERE id = ?";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    if (projectId == null) {
      _statement.bindNull(_argIndex);
    } else {
      _statement.bindString(_argIndex, projectId);
    }
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<ProjectEntity>() {
      @Override
      @Nullable
      public ProjectEntity call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfTenantId = CursorUtil.getColumnIndexOrThrow(_cursor, "tenant_id");
          final int _cursorIndexOfNome = CursorUtil.getColumnIndexOrThrow(_cursor, "nome");
          final int _cursorIndexOfDescricao = CursorUtil.getColumnIndexOrThrow(_cursor, "descricao");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfDataInicio = CursorUtil.getColumnIndexOrThrow(_cursor, "data_inicio");
          final int _cursorIndexOfDataConclusao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_conclusao");
          final int _cursorIndexOfDataCriacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_criacao");
          final int _cursorIndexOfSynced = CursorUtil.getColumnIndexOrThrow(_cursor, "synced");
          final ProjectEntity _result;
          if (_cursor.moveToFirst()) {
            final String _tmpId;
            if (_cursor.isNull(_cursorIndexOfId)) {
              _tmpId = null;
            } else {
              _tmpId = _cursor.getString(_cursorIndexOfId);
            }
            final String _tmpTenant_id;
            if (_cursor.isNull(_cursorIndexOfTenantId)) {
              _tmpTenant_id = null;
            } else {
              _tmpTenant_id = _cursor.getString(_cursorIndexOfTenantId);
            }
            final String _tmpNome;
            if (_cursor.isNull(_cursorIndexOfNome)) {
              _tmpNome = null;
            } else {
              _tmpNome = _cursor.getString(_cursorIndexOfNome);
            }
            final String _tmpDescricao;
            if (_cursor.isNull(_cursorIndexOfDescricao)) {
              _tmpDescricao = null;
            } else {
              _tmpDescricao = _cursor.getString(_cursorIndexOfDescricao);
            }
            final String _tmpStatus;
            if (_cursor.isNull(_cursorIndexOfStatus)) {
              _tmpStatus = null;
            } else {
              _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            }
            final String _tmpData_inicio;
            if (_cursor.isNull(_cursorIndexOfDataInicio)) {
              _tmpData_inicio = null;
            } else {
              _tmpData_inicio = _cursor.getString(_cursorIndexOfDataInicio);
            }
            final String _tmpData_conclusao;
            if (_cursor.isNull(_cursorIndexOfDataConclusao)) {
              _tmpData_conclusao = null;
            } else {
              _tmpData_conclusao = _cursor.getString(_cursorIndexOfDataConclusao);
            }
            final String _tmpData_criacao;
            if (_cursor.isNull(_cursorIndexOfDataCriacao)) {
              _tmpData_criacao = null;
            } else {
              _tmpData_criacao = _cursor.getString(_cursorIndexOfDataCriacao);
            }
            final boolean _tmpSynced;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfSynced);
            _tmpSynced = _tmp != 0;
            _result = new ProjectEntity(_tmpId,_tmpTenant_id,_tmpNome,_tmpDescricao,_tmpStatus,_tmpData_inicio,_tmpData_conclusao,_tmpData_criacao,_tmpSynced);
          } else {
            _result = null;
          }
          return _result;
        } finally {
          _cursor.close();
          _statement.release();
        }
      }
    }, $completion);
  }

  @Override
  public Object getUnsyncedProjects(final Continuation<? super List<ProjectEntity>> $completion) {
    final String _sql = "SELECT * FROM projects WHERE synced = 0";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<ProjectEntity>>() {
      @Override
      @NonNull
      public List<ProjectEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfTenantId = CursorUtil.getColumnIndexOrThrow(_cursor, "tenant_id");
          final int _cursorIndexOfNome = CursorUtil.getColumnIndexOrThrow(_cursor, "nome");
          final int _cursorIndexOfDescricao = CursorUtil.getColumnIndexOrThrow(_cursor, "descricao");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfDataInicio = CursorUtil.getColumnIndexOrThrow(_cursor, "data_inicio");
          final int _cursorIndexOfDataConclusao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_conclusao");
          final int _cursorIndexOfDataCriacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_criacao");
          final int _cursorIndexOfSynced = CursorUtil.getColumnIndexOrThrow(_cursor, "synced");
          final List<ProjectEntity> _result = new ArrayList<ProjectEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final ProjectEntity _item;
            final String _tmpId;
            if (_cursor.isNull(_cursorIndexOfId)) {
              _tmpId = null;
            } else {
              _tmpId = _cursor.getString(_cursorIndexOfId);
            }
            final String _tmpTenant_id;
            if (_cursor.isNull(_cursorIndexOfTenantId)) {
              _tmpTenant_id = null;
            } else {
              _tmpTenant_id = _cursor.getString(_cursorIndexOfTenantId);
            }
            final String _tmpNome;
            if (_cursor.isNull(_cursorIndexOfNome)) {
              _tmpNome = null;
            } else {
              _tmpNome = _cursor.getString(_cursorIndexOfNome);
            }
            final String _tmpDescricao;
            if (_cursor.isNull(_cursorIndexOfDescricao)) {
              _tmpDescricao = null;
            } else {
              _tmpDescricao = _cursor.getString(_cursorIndexOfDescricao);
            }
            final String _tmpStatus;
            if (_cursor.isNull(_cursorIndexOfStatus)) {
              _tmpStatus = null;
            } else {
              _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            }
            final String _tmpData_inicio;
            if (_cursor.isNull(_cursorIndexOfDataInicio)) {
              _tmpData_inicio = null;
            } else {
              _tmpData_inicio = _cursor.getString(_cursorIndexOfDataInicio);
            }
            final String _tmpData_conclusao;
            if (_cursor.isNull(_cursorIndexOfDataConclusao)) {
              _tmpData_conclusao = null;
            } else {
              _tmpData_conclusao = _cursor.getString(_cursorIndexOfDataConclusao);
            }
            final String _tmpData_criacao;
            if (_cursor.isNull(_cursorIndexOfDataCriacao)) {
              _tmpData_criacao = null;
            } else {
              _tmpData_criacao = _cursor.getString(_cursorIndexOfDataCriacao);
            }
            final boolean _tmpSynced;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfSynced);
            _tmpSynced = _tmp != 0;
            _item = new ProjectEntity(_tmpId,_tmpTenant_id,_tmpNome,_tmpDescricao,_tmpStatus,_tmpData_inicio,_tmpData_conclusao,_tmpData_criacao,_tmpSynced);
            _result.add(_item);
          }
          return _result;
        } finally {
          _cursor.close();
          _statement.release();
        }
      }
    }, $completion);
  }

  @NonNull
  public static List<Class<?>> getRequiredConverters() {
    return Collections.emptyList();
  }
}
