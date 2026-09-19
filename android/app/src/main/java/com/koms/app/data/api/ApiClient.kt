package com.koms.app.data.api

import android.content.Context
import com.koms.app.BuildConfig
import com.koms.app.utils.Constants
import com.koms.app.utils.SessionManager
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiClient {
    @Volatile
    private var tokenProvider: (() -> String?)? = null

    @Volatile
    private var appContext: Context? = null

    fun init(context: Context) {
        appContext = context.applicationContext
    }

    fun setTokenProvider(provider: () -> String?) {
        tokenProvider = provider
    }

    private val authInterceptor = Interceptor { chain ->
        val original = chain.request()
        val token = tokenProvider?.invoke() ?: appContext?.let { SessionManager(it).getAuthToken() }
        val requestBuilder = original.newBuilder()
        if (!token.isNullOrBlank()) {
            requestBuilder.header("Authorization", "Bearer $token")
        }
        chain.proceed(requestBuilder.build())
    }

    private val loggingInterceptor: HttpLoggingInterceptor by lazy {
        HttpLoggingInterceptor().apply {
            level = if (BuildConfig.DEBUG) {
                HttpLoggingInterceptor.Level.BODY
            } else {
                HttpLoggingInterceptor.Level.NONE
            }
        }
    }

    private val okHttpClient: OkHttpClient by lazy {
        OkHttpClient.Builder()
            .connectTimeout(30, TimeUnit.SECONDS)
            .readTimeout(30, TimeUnit.SECONDS)
            .writeTimeout(30, TimeUnit.SECONDS)
            .addInterceptor(authInterceptor)
            .addInterceptor(loggingInterceptor)
            .retryOnConnectionFailure(true)
            .build()
    }

    @Volatile
    private var retrofitInstance: Retrofit? = null

    @Volatile
    var currentBaseUrl: String = Constants.LOCAL_URL

    val client: Retrofit
        get() {
            return retrofitInstance ?: synchronized(this) {
                retrofitInstance ?: Retrofit.Builder()
                    .baseUrl(currentBaseUrl)
                    .client(okHttpClient)
                    .addConverterFactory(GsonConverterFactory.create())
                    .build().also { retrofitInstance = it }
            }
        }

    val apiService: ApiService
        get() = client.create(ApiService::class.java)

    val localApiService: ApiService
        get() = apiService

    fun updateBaseUrl(newUrl: String) {
        synchronized(this) {
            if (currentBaseUrl != newUrl) {
                currentBaseUrl = newUrl
                retrofitInstance = null
            }
        }
    }
}
