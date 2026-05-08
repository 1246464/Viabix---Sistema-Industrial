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
import com.viabix.app.domain.AnviEntity;
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
public final class AnviDao_Impl implements AnviDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<AnviEntity> __insertionAdapterOfAnviEntity;

  private final EntityDeletionOrUpdateAdapter<AnviEntity> __deletionAdapterOfAnviEntity;

  private final EntityDeletionOrUpdateAdapter<AnviEntity> __updateAdapterOfAnviEntity;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAnviById;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAnvisByTenant;

  public AnviDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfAnviEntity = new EntityInsertionAdapter<AnviEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR REPLACE INTO `anvis` (`id`,`tenant_id`,`nome`,`descricao`,`status`,`data_criacao`,`data_atualizacao`,`custo_total`,`synced`) VALUES (?,?,?,?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final AnviEntity entity) {
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
        if (entity.getData_criacao() == null) {
          statement.bindNull(6);
        } else {
          statement.bindString(6, entity.getData_criacao());
        }
        if (entity.getData_atualizacao() == null) {
          statement.bindNull(7);
        } else {
          statement.bindString(7, entity.getData_atualizacao());
        }
        statement.bindDouble(8, entity.getCusto_total());
        final int _tmp = entity.getSynced() ? 1 : 0;
        statement.bindLong(9, _tmp);
      }
    };
    this.__deletionAdapterOfAnviEntity = new EntityDeletionOrUpdateAdapter<AnviEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "DELETE FROM `anvis` WHERE `id` = ?";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final AnviEntity entity) {
        if (entity.getId() == null) {
          statement.bindNull(1);
        } else {
          statement.bindString(1, entity.getId());
        }
      }
    };
    this.__updateAdapterOfAnviEntity = new EntityDeletionOrUpdateAdapter<AnviEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "UPDATE OR ABORT `anvis` SET `id` = ?,`tenant_id` = ?,`nome` = ?,`descricao` = ?,`status` = ?,`data_criacao` = ?,`data_atualizacao` = ?,`custo_total` = ?,`synced` = ? WHERE `id` = ?";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final AnviEntity entity) {
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
        if (entity.getData_criacao() == null) {
          statement.bindNull(6);
        } else {
          statement.bindString(6, entity.getData_criacao());
        }
        if (entity.getData_atualizacao() == null) {
          statement.bindNull(7);
        } else {
          statement.bindString(7, entity.getData_atualizacao());
        }
        statement.bindDouble(8, entity.getCusto_total());
        final int _tmp = entity.getSynced() ? 1 : 0;
        statement.bindLong(9, _tmp);
        if (entity.getId() == null) {
          statement.bindNull(10);
        } else {
          statement.bindString(10, entity.getId());
        }
      }
    };
    this.__preparedStmtOfDeleteAnviById = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM anvis WHERE id = ?";
        return _query;
      }
    };
    this.__preparedStmtOfDeleteAnvisByTenant = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM anvis WHERE tenant_id = ?";
        return _query;
      }
    };
  }

  @Override
  public Object insertAnvi(final AnviEntity anvi, final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __insertionAdapterOfAnviEntity.insert(anvi);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object insertAnvis(final List<AnviEntity> anvis,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __insertionAdapterOfAnviEntity.insert(anvis);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteAnvi(final AnviEntity anvi, final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __deletionAdapterOfAnviEntity.handle(anvi);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object updateAnvi(final AnviEntity anvi, final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __updateAdapterOfAnviEntity.handle(anvi);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteAnviById(final String anviId, final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfDeleteAnviById.acquire();
        int _argIndex = 1;
        if (anviId == null) {
          _stmt.bindNull(_argIndex);
        } else {
          _stmt.bindString(_argIndex, anviId);
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
          __preparedStmtOfDeleteAnviById.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteAnvisByTenant(final String tenantId,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfDeleteAnvisByTenant.acquire();
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
          __preparedStmtOfDeleteAnvisByTenant.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Flow<List<AnviEntity>> getAnvisByTenant(final String tenantId) {
    final String _sql = "SELECT * FROM anvis WHERE tenant_id = ? ORDER BY data_criacao DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    if (tenantId == null) {
      _statement.bindNull(_argIndex);
    } else {
      _statement.bindString(_argIndex, tenantId);
    }
    return CoroutinesRoom.createFlow(__db, false, new String[] {"anvis"}, new Callable<List<AnviEntity>>() {
      @Override
      @NonNull
      public List<AnviEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfTenantId = CursorUtil.getColumnIndexOrThrow(_cursor, "tenant_id");
          final int _cursorIndexOfNome = CursorUtil.getColumnIndexOrThrow(_cursor, "nome");
          final int _cursorIndexOfDescricao = CursorUtil.getColumnIndexOrThrow(_cursor, "descricao");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfDataCriacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_criacao");
          final int _cursorIndexOfDataAtualizacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_atualizacao");
          final int _cursorIndexOfCustoTotal = CursorUtil.getColumnIndexOrThrow(_cursor, "custo_total");
          final int _cursorIndexOfSynced = CursorUtil.getColumnIndexOrThrow(_cursor, "synced");
          final List<AnviEntity> _result = new ArrayList<AnviEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final AnviEntity _item;
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
            final String _tmpData_criacao;
            if (_cursor.isNull(_cursorIndexOfDataCriacao)) {
              _tmpData_criacao = null;
            } else {
              _tmpData_criacao = _cursor.getString(_cursorIndexOfDataCriacao);
            }
            final String _tmpData_atualizacao;
            if (_cursor.isNull(_cursorIndexOfDataAtualizacao)) {
              _tmpData_atualizacao = null;
            } else {
              _tmpData_atualizacao = _cursor.getString(_cursorIndexOfDataAtualizacao);
            }
            final double _tmpCusto_total;
            _tmpCusto_total = _cursor.getDouble(_cursorIndexOfCustoTotal);
            final boolean _tmpSynced;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfSynced);
            _tmpSynced = _tmp != 0;
            _item = new AnviEntity(_tmpId,_tmpTenant_id,_tmpNome,_tmpDescricao,_tmpStatus,_tmpData_criacao,_tmpData_atualizacao,_tmpCusto_total,_tmpSynced);
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
  public Object getAnvisByTenantSync(final String tenantId,
      final Continuation<? super List<AnviEntity>> $completion) {
    final String _sql = "SELECT * FROM anvis WHERE tenant_id = ? ORDER BY data_criacao DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    if (tenantId == null) {
      _statement.bindNull(_argIndex);
    } else {
      _statement.bindString(_argIndex, tenantId);
    }
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<AnviEntity>>() {
      @Override
      @NonNull
      public List<AnviEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfTenantId = CursorUtil.getColumnIndexOrThrow(_cursor, "tenant_id");
          final int _cursorIndexOfNome = CursorUtil.getColumnIndexOrThrow(_cursor, "nome");
          final int _cursorIndexOfDescricao = CursorUtil.getColumnIndexOrThrow(_cursor, "descricao");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfDataCriacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_criacao");
          final int _cursorIndexOfDataAtualizacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_atualizacao");
          final int _cursorIndexOfCustoTotal = CursorUtil.getColumnIndexOrThrow(_cursor, "custo_total");
          final int _cursorIndexOfSynced = CursorUtil.getColumnIndexOrThrow(_cursor, "synced");
          final List<AnviEntity> _result = new ArrayList<AnviEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final AnviEntity _item;
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
            final String _tmpData_criacao;
            if (_cursor.isNull(_cursorIndexOfDataCriacao)) {
              _tmpData_criacao = null;
            } else {
              _tmpData_criacao = _cursor.getString(_cursorIndexOfDataCriacao);
            }
            final String _tmpData_atualizacao;
            if (_cursor.isNull(_cursorIndexOfDataAtualizacao)) {
              _tmpData_atualizacao = null;
            } else {
              _tmpData_atualizacao = _cursor.getString(_cursorIndexOfDataAtualizacao);
            }
            final double _tmpCusto_total;
            _tmpCusto_total = _cursor.getDouble(_cursorIndexOfCustoTotal);
            final boolean _tmpSynced;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfSynced);
            _tmpSynced = _tmp != 0;
            _item = new AnviEntity(_tmpId,_tmpTenant_id,_tmpNome,_tmpDescricao,_tmpStatus,_tmpData_criacao,_tmpData_atualizacao,_tmpCusto_total,_tmpSynced);
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
  public Object getAnviById(final String anviId,
      final Continuation<? super AnviEntity> $completion) {
    final String _sql = "SELECT * FROM anvis WHERE id = ?";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    if (anviId == null) {
      _statement.bindNull(_argIndex);
    } else {
      _statement.bindString(_argIndex, anviId);
    }
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<AnviEntity>() {
      @Override
      @Nullable
      public AnviEntity call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfTenantId = CursorUtil.getColumnIndexOrThrow(_cursor, "tenant_id");
          final int _cursorIndexOfNome = CursorUtil.getColumnIndexOrThrow(_cursor, "nome");
          final int _cursorIndexOfDescricao = CursorUtil.getColumnIndexOrThrow(_cursor, "descricao");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfDataCriacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_criacao");
          final int _cursorIndexOfDataAtualizacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_atualizacao");
          final int _cursorIndexOfCustoTotal = CursorUtil.getColumnIndexOrThrow(_cursor, "custo_total");
          final int _cursorIndexOfSynced = CursorUtil.getColumnIndexOrThrow(_cursor, "synced");
          final AnviEntity _result;
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
            final String _tmpData_criacao;
            if (_cursor.isNull(_cursorIndexOfDataCriacao)) {
              _tmpData_criacao = null;
            } else {
              _tmpData_criacao = _cursor.getString(_cursorIndexOfDataCriacao);
            }
            final String _tmpData_atualizacao;
            if (_cursor.isNull(_cursorIndexOfDataAtualizacao)) {
              _tmpData_atualizacao = null;
            } else {
              _tmpData_atualizacao = _cursor.getString(_cursorIndexOfDataAtualizacao);
            }
            final double _tmpCusto_total;
            _tmpCusto_total = _cursor.getDouble(_cursorIndexOfCustoTotal);
            final boolean _tmpSynced;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfSynced);
            _tmpSynced = _tmp != 0;
            _result = new AnviEntity(_tmpId,_tmpTenant_id,_tmpNome,_tmpDescricao,_tmpStatus,_tmpData_criacao,_tmpData_atualizacao,_tmpCusto_total,_tmpSynced);
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
  public Object getUnsyncedAnvis(final Continuation<? super List<AnviEntity>> $completion) {
    final String _sql = "SELECT * FROM anvis WHERE synced = 0";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<AnviEntity>>() {
      @Override
      @NonNull
      public List<AnviEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfTenantId = CursorUtil.getColumnIndexOrThrow(_cursor, "tenant_id");
          final int _cursorIndexOfNome = CursorUtil.getColumnIndexOrThrow(_cursor, "nome");
          final int _cursorIndexOfDescricao = CursorUtil.getColumnIndexOrThrow(_cursor, "descricao");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfDataCriacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_criacao");
          final int _cursorIndexOfDataAtualizacao = CursorUtil.getColumnIndexOrThrow(_cursor, "data_atualizacao");
          final int _cursorIndexOfCustoTotal = CursorUtil.getColumnIndexOrThrow(_cursor, "custo_total");
          final int _cursorIndexOfSynced = CursorUtil.getColumnIndexOrThrow(_cursor, "synced");
          final List<AnviEntity> _result = new ArrayList<AnviEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final AnviEntity _item;
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
            final String _tmpData_criacao;
            if (_cursor.isNull(_cursorIndexOfDataCriacao)) {
              _tmpData_criacao = null;
            } else {
              _tmpData_criacao = _cursor.getString(_cursorIndexOfDataCriacao);
            }
            final String _tmpData_atualizacao;
            if (_cursor.isNull(_cursorIndexOfDataAtualizacao)) {
              _tmpData_atualizacao = null;
            } else {
              _tmpData_atualizacao = _cursor.getString(_cursorIndexOfDataAtualizacao);
            }
            final double _tmpCusto_total;
            _tmpCusto_total = _cursor.getDouble(_cursorIndexOfCustoTotal);
            final boolean _tmpSynced;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfSynced);
            _tmpSynced = _tmp != 0;
            _item = new AnviEntity(_tmpId,_tmpTenant_id,_tmpNome,_tmpDescricao,_tmpStatus,_tmpData_criacao,_tmpData_atualizacao,_tmpCusto_total,_tmpSynced);
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
