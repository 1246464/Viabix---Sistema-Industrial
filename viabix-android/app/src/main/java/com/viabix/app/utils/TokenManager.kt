package com.viabix.app.utils

import android.content.Context
import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import android.util.Base64
import androidx.datastore.core.DataStore
import androidx.datastore.preferences.core.Preferences
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import dagger.hilt.android.qualifiers.ApplicationContext
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map
import kotlinx.coroutines.runBlocking
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec
import javax.inject.Inject
import javax.inject.Singleton

private val Context.dataStore: DataStore<Preferences> by preferencesDataStore(name = "settings")

@Singleton
class TokenManager @Inject constructor(
    @ApplicationContext private val context: Context
) {
    companion object {
        private const val KEYSTORE_PROVIDER = "AndroidKeyStore"
        private const val TOKEN_KEY_ALIAS = "viabix_session_token"
        private const val TOKEN_FORMAT = "v1"
        private val LEGACY_TOKEN_KEY = stringPreferencesKey("jwt_token")
        private val ENCRYPTED_TOKEN_KEY = stringPreferencesKey("jwt_token_encrypted")
        private val TENANT_ID_KEY = stringPreferencesKey("tenant_id")
        private val USER_NAME_KEY = stringPreferencesKey("user_name")
        private val USER_LOGIN_KEY = stringPreferencesKey("user_login")
        private val USER_LEVEL_KEY = stringPreferencesKey("user_level")
    }

    suspend fun saveToken(token: String) {
        context.dataStore.edit { preferences ->
            preferences[ENCRYPTED_TOKEN_KEY] = encryptToken(token)
            preferences.remove(LEGACY_TOKEN_KEY)
        }
    }

    suspend fun getToken(): String? {
        val preferences = context.dataStore.data.first()
        preferences[ENCRYPTED_TOKEN_KEY]?.let { encrypted ->
            val token = decryptToken(encrypted)
            if (token != null) return token

            // A chave pode ter sido invalidada pelo sistema. Não mantém uma
            // sessão impossível de descriptografar: o usuário fará login outra vez.
            context.dataStore.edit { it.remove(ENCRYPTED_TOKEN_KEY) }
            return null
        }

        // Migração transparente de instalações que gravavam o JWT em texto puro.
        return preferences[LEGACY_TOKEN_KEY]?.takeIf { it.isNotBlank() }?.also { legacyToken ->
            saveToken(legacyToken)
        }
    }

    // Método síncrono para ser usado em Interceptors
    fun getTokenSync(): String? {
        return runBlocking { getToken() }
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
            preferences.remove(ENCRYPTED_TOKEN_KEY)
            preferences.remove(LEGACY_TOKEN_KEY)
            preferences.remove(TENANT_ID_KEY)
            preferences.remove(USER_NAME_KEY)
            preferences.remove(USER_LOGIN_KEY)
            preferences.remove(USER_LEVEL_KEY)
        }
    }

    private fun encryptToken(token: String): String {
        val cipher = Cipher.getInstance("AES/GCM/NoPadding")
        cipher.init(Cipher.ENCRYPT_MODE, getOrCreateSecretKey())
        val ciphertext = cipher.doFinal(token.toByteArray(Charsets.UTF_8))
        val iv = Base64.encodeToString(cipher.iv, Base64.NO_WRAP)
        val payload = Base64.encodeToString(ciphertext, Base64.NO_WRAP)
        return "$TOKEN_FORMAT:$iv:$payload"
    }

    private fun decryptToken(value: String): String? = runCatching {
        val parts = value.split(':', limit = 3)
        require(parts.size == 3 && parts[0] == TOKEN_FORMAT)
        val iv = Base64.decode(parts[1], Base64.NO_WRAP)
        val ciphertext = Base64.decode(parts[2], Base64.NO_WRAP)
        val cipher = Cipher.getInstance("AES/GCM/NoPadding")
        cipher.init(Cipher.DECRYPT_MODE, getOrCreateSecretKey(), GCMParameterSpec(128, iv))
        cipher.doFinal(ciphertext).toString(Charsets.UTF_8)
    }.getOrNull()

    private fun getOrCreateSecretKey(): SecretKey {
        val keyStore = KeyStore.getInstance(KEYSTORE_PROVIDER).apply { load(null) }
        (keyStore.getKey(TOKEN_KEY_ALIAS, null) as? SecretKey)?.let { return it }

        return KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, KEYSTORE_PROVIDER).run {
            init(
                KeyGenParameterSpec.Builder(
                    TOKEN_KEY_ALIAS,
                    KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT
                )
                    .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                    .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                    .setRandomizedEncryptionRequired(true)
                    .build()
            )
            generateKey()
        }
    }
}
