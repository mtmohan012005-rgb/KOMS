package com.koms.app.utils

object Constants {
    // Production Cloud Backend on Render
    const val BASE_URL = "https://koms-backend.onrender.com/api/"
    const val LOCAL_URL = "http://127.0.0.1:8080/api/"
    const val EMULATOR_URL = "http://10.0.2.2:8080/api/"
    
    // SharedPreferences Constants
    const val PREF_NAME = "KomsSessionPref"
    const val KEY_IS_LOGGED_IN = "isLoggedIn"
    const val KEY_USER_ID = "userId"
    const val KEY_USER_NAME = "userName"
    const val KEY_USER_EMAIL = "userEmail"
    const val KEY_USER_ROLE = "userRole"
    const val KEY_AUTH_TOKEN = "authToken"
}
