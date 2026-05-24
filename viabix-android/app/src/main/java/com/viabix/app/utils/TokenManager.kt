package com.viabix.app.utils

import android.content.Context
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.runBlocking
import javax.inject.Inject
import javax.inject.Singleton

private val Context.dataStore: DataStore<Preferences> by preferencesDataStore(name = "settings")

@Singleton
class TokenManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    companion object {
        private val TOKEN_KEY = stringPreferencesKey("jwt_token")
        private val TENANT_ID_KEY = stringPreferencesKey("tenant_id")
        private val USER_NAME_KEY = stringPreferencesKey("user_name")
        private val USER_LOGIN_KEY = stringPreferencesKey("user_login")
        private val USER_LEVEL_KEY = stringPreferencesKey("user_level")
    }

    suspend fun saveToken(token: String) {
        context.dataStore.edit { preferences ->
            preferences[TOKEN_KEY] = token
        }
    }

    suspend fun getToken(): String? {
        return context.dataStore.data.map { preferences ->
            preferences[TOKEN_KEY]
        }.first()
    }

    // Método síncrono para ser usado em Interceptors
    fun getTokenSync(): String? {
        return runBlocking {
            context.dataStore.data.map { preferences ->
                preferences[TOKEN_KEY]
            }.first()
        }
    }

    suspend fun saveTenantId(tenantId: String) {
        context.dataStore.edit { preferences ->
            preferences[TENANT_ID_KEY] = tenantId
        }
    }

    suspend fun getTenantId(): String? {
        return context.dataStore.data.map { preferences ->
            preferences[TENANT_ID_KEY]
        }.first()
    }

    suspend fun saveUserData(name: String, login: String, level: String) {
        context.dataStore.edit { preferences ->
            preferences[USER_NAME_KEY] = name
            preferences[USER_LOGIN_KEY] = login
            preferences[USER_LEVEL_KEY] = level
        }
    }

    suspend fun getUserName(): String? {
        return context.dataStore.data.map { preferences ->
            preferences[USER_NAME_KEY]
        }.first()
    }

    suspend fun getUserLogin(): String? {
        return context.dataStore.data.map { preferences ->
            preferences[USER_LOGIN_KEY]
        }.first()
    }

    suspend fun getUserLevel(): String? {
        return context.dataStore.data.map { preferences ->
            preferences[USER_LEVEL_KEY]
        }.first()
    }

    suspend fun clearToken() {
        context.dataStore.edit { preferences ->
            preferences.remove(TOKEN_KEY)
            preferences.remove(TENANT_ID_KEY)
            preferences.remove(USER_NAME_KEY)
            preferences.remove(USER_LOGIN_KEY)
            preferences.remove(USER_LEVEL_KEY)
        }
    }
}
