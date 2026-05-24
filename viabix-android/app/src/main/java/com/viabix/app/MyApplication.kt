package com.viabix.app

import android.app.Application
import com.viabix.app.data.api.RetrofitClient
import dagger.hilt.android.HiltAndroidApp

@HiltAndroidApp
class MyApplication : Application() {
    override fun onCreate() {
        super.onCreate()
        RetrofitClient.initialize(this)
    }
}
