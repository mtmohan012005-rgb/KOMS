package com.koms.app.utils

object Constants {
    // Development default: Android Emulator points to host machine port 8080
    const val EMULATOR_URL = "http://10.0.2.2:8080/api/"
    const val LOCAL_URL = "http://127.0.0.1:8080/api/"
    const val RENDER_URL = "https://koms-backend.onrender.com/api/"
    const val BASE_URL = LOCAL_URL
    
    // SharedPreferences Constants
    const val PREF_NAME = "KomsSessionPref"
    const val KEY_IS_LOGGED_IN = "isLoggedIn"
    const val KEY_USER_ID = "userId"
    const val KEY_USER_NAME = "userName"
    const val KEY_USER_EMAIL = "userEmail"
    const val KEY_USER_ROLE = "userRole"
    const val KEY_AUTH_TOKEN = "authToken"
    const val KEY_DOJO_ID = "dojoId"
}
