package com.viabix.app.data.api;

import android.content.Context;
import android.util.Log;
import com.viabix.app.utils.TokenManager;
import okhttp3.Cookie;
import okhttp3.CookieJar;
import okhttp3.HttpUrl;
import okhttp3.Interceptor;
import okhttp3.OkHttpClient;
import okhttp3.logging.HttpLoggingInterceptor;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;
import java.util.concurrent.TimeUnit;

@kotlin.Metadata(mv = {1, 9, 0}, k = 2, xi = 48, d1 = {"\u0000\u0014\n\u0000\n\u0002\u0010\u000b\n\u0002\u0018\u0002\n\u0002\b\u0002\n\u0002\u0018\u0002\n\u0000\u001a\f\u0010\u0000\u001a\u00020\u0001*\u00020\u0002H\u0002\u001a\u0014\u0010\u0003\u001a\u00020\u0001*\u00020\u00022\u0006\u0010\u0004\u001a\u00020\u0005H\u0002\u00a8\u0006\u0006"}, d2 = {"isExpired", "", "Lokhttp3/Cookie;", "matches", "url", "Lokhttp3/HttpUrl;", "app_debug"})
public final class RetrofitClientKt {
    
    private static final boolean isExpired(okhttp3.Cookie $this$isExpired) {
        return false;
    }
    
    private static final boolean matches(okhttp3.Cookie $this$matches, okhttp3.HttpUrl url) {
        return false;
    }
}