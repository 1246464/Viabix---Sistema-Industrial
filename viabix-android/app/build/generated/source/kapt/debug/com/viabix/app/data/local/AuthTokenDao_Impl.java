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
import com.viabix.app.domain.AuthTokenEntity;
import java.lang.Class;
import java.lang.Exception;
import java.lang.Object;
import java.lang.Override;
import java.lang.String;
import java.lang.SuppressWarnings;
import java.util.Collections;
import java.util.List;
import java.util.concurrent.Callable;
import javax.annotation.processing.Generated;
import kotlin.Unit;
import kotlin.coroutines.Continuation;
import kotlinx.coroutines.flow.Flow;

@Generated("androidx.room.RoomProcessor")
@SuppressWarnings({"unchecked", "deprecation"})
public final class AuthTokenDao_Impl implements AuthTokenDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<AuthTokenEntity> __insertionAdapterOfAuthTokenEntity;

  private final EntityDeletionOrUpdateAdapter<AuthTokenEntity> __deletionAdapterOfAuthTokenEntity;

  private final SharedSQLiteStatement __preparedStmtOfClearAllTokens;

  public AuthTokenDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfAuthTokenEntity = new EntityInsertionAdapter<AuthTokenEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR REPLACE INTO `auth_tokens` (`id`,`token`,`user_id`,`tenant_id`,`expires_at`) VALUES (?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final AuthTokenEntity entity) {
        statement.bindLong(1, entity.getId());
        if (entity.getToken() == null) {
          statement.bindNull(2);
        } else {
          statement.bindString(2, entity.getToken());
        }
        if (entity.getUser_id() == null) {
          statement.bindNull(3);
        } else {
          statement.bindString(3, entity.getUser_id());
        }
        if (entity.getTenant_id() == null) {
          statement.bindNull(4);
        } else {
          statement.bindString(4, entity.getTenant_id());
        }
        statement.bindLong(5, entity.getExpires_at());
      }
    };
    this.__deletionAdapterOfAuthTokenEntity = new EntityDeletionOrUpdateAdapter<AuthTokenEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "DELETE FROM `auth_tokens` WHERE `id` = ?";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final AuthTokenEntity entity) {
        statement.bindLong(1, entity.getId());
      }
    };
    this.__preparedStmtOfClearAllTokens = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM auth_tokens";
        return _query;
      }
    };
  }

  @Override
  public Object insertToken(final AuthTokenEntity token,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __insertionAdapterOfAuthTokenEntity.insert(token);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteToken(final AuthTokenEntity token,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __deletionAdapterOfAuthTokenEntity.handle(token);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object clearAllTokens(final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfClearAllTokens.acquire();
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
          __preparedStmtOfClearAllTokens.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Object getToken(final Continuation<? super AuthTokenEntity> $completion) {
    final String _sql = "SELECT * FROM auth_tokens WHERE id = 1";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<AuthTokenEntity>() {
      @Override
      @Nullable
      public AuthTokenEntity call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfToken = CursorUtil.getColumnIndexOrThrow(_cursor, "token");
          final int _cursorIndexOfUserId = CursorUtil.getColumnIndexOrThrow(_cursor, "user_id");
          final int _cursorIndexOfTenantId = CursorUtil.getColumnIndexOrThrow(_cursor, "tenant_id");
          final int _cursorIndexOfExpiresAt = CursorUtil.getColumnIndexOrThrow(_cursor, "expires_at");
          final AuthTokenEntity _result;
          if (_cursor.moveToFirst()) {
            final int _tmpId;
            _tmpId = _cursor.getInt(_cursorIndexOfId);
            final String _tmpToken;
            if (_cursor.isNull(_cursorIndexOfToken)) {
              _tmpToken = null;
            } else {
              _tmpToken = _cursor.getString(_cursorIndexOfToken);
            }
            final String _tmpUser_id;
            if (_cursor.isNull(_cursorIndexOfUserId)) {
              _tmpUser_id = null;
            } else {
              _tmpUser_id = _cursor.getString(_cursorIndexOfUserId);
            }
            final String _tmpTenant_id;
            if (_cursor.isNull(_cursorIndexOfTenantId)) {
              _tmpTenant_id = null;
            } else {
              _tmpTenant_id = _cursor.getString(_cursorIndexOfTenantId);
            }
            final long _tmpExpires_at;
            _tmpExpires_at = _cursor.getLong(_cursorIndexOfExpiresAt);
            _result = new AuthTokenEntity(_tmpId,_tmpToken,_tmpUser_id,_tmpTenant_id,_tmpExpires_at);
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
  public Flow<AuthTokenEntity> getTokenFlow() {
    final String _sql = "SELECT * FROM auth_tokens WHERE id = 1";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    return CoroutinesRoom.createFlow(__db, false, new String[] {"auth_tokens"}, new Callable<AuthTokenEntity>() {
      @Override
      @Nullable
      public AuthTokenEntity call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfToken = CursorUtil.getColumnIndexOrThrow(_cursor, "token");
          final int _cursorIndexOfUserId = CursorUtil.getColumnIndexOrThrow(_cursor, "user_id");
          final int _cursorIndexOfTenantId = CursorUtil.getColumnIndexOrThrow(_cursor, "tenant_id");
          final int _cursorIndexOfExpiresAt = CursorUtil.getColumnIndexOrThrow(_cursor, "expires_at");
          final AuthTokenEntity _result;
          if (_cursor.moveToFirst()) {
            final int _tmpId;
            _tmpId = _cursor.getInt(_cursorIndexOfId);
            final String _tmpToken;
            if (_cursor.isNull(_cursorIndexOfToken)) {
              _tmpToken = null;
            } else {
              _tmpToken = _cursor.getString(_cursorIndexOfToken);
            }
            final String _tmpUser_id;
            if (_cursor.isNull(_cursorIndexOfUserId)) {
              _tmpUser_id = null;
            } else {
              _tmpUser_id = _cursor.getString(_cursorIndexOfUserId);
            }
            final String _tmpTenant_id;
            if (_cursor.isNull(_cursorIndexOfTenantId)) {
              _tmpTenant_id = null;
            } else {
              _tmpTenant_id = _cursor.getString(_cursorIndexOfTenantId);
            }
            final long _tmpExpires_at;
            _tmpExpires_at = _cursor.getLong(_cursorIndexOfExpiresAt);
            _result = new AuthTokenEntity(_tmpId,_tmpToken,_tmpUser_id,_tmpTenant_id,_tmpExpires_at);
          } else {
            _result = null;
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

  @NonNull
  public static List<Class<?>> getRequiredConverters() {
    return Collections.emptyList();
  }
}
