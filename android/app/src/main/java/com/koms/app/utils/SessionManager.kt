package com.koms.app.utils

import android.content.Context
import android.content.SharedPreferences

class SessionManager(context: Context) {
    private val prefs: SharedPreferences = context.getSharedPreferences(Constants.PREF_NAME, Context.MODE_PRIVATE)
    private val editor: SharedPreferences.Editor = prefs.edit()

    fun saveAuthToken(token: String) {
        editor.putString(Constants.KEY_AUTH_TOKEN, token)
        editor.putBoolean(Constants.KEY_IS_LOGGED_IN, true)
        editor.apply()
    }

    fun saveUserDetails(userId: Int, name: String, email: String, role: String, dojoId: Int = 0) {
        editor.putInt(Constants.KEY_USER_ID, userId)
        editor.putString(Constants.KEY_USER_NAME, name)
        editor.putString(Constants.KEY_USER_EMAIL, email)
        editor.putString(Constants.KEY_USER_ROLE, role)
        editor.putInt(Constants.KEY_DOJO_ID, dojoId)
        editor.apply()
    }

    fun fetchAuthToken(): String? {
        return prefs.getString(Constants.KEY_AUTH_TOKEN, null)
    }

    fun getAuthToken(): String? {
        return fetchAuthToken()
    }

    fun getUserId(): Int {
        return prefs.getInt(Constants.KEY_USER_ID, 0)
    }

    fun getDojoId(): Int {
        return prefs.getInt(Constants.KEY_DOJO_ID, 1)
    }

    fun getUserRole(): String? {
        return prefs.getString(Constants.KEY_USER_ROLE, null)
    }

    fun getUserName(): String? {
        return prefs.getString(Constants.KEY_USER_NAME, null)
    }

    fun getUserEmail(): String? {
        return prefs.getString(Constants.KEY_USER_EMAIL, null)
    }

    fun isLoggedIn(): Boolean {
        return prefs.getBoolean(Constants.KEY_IS_LOGGED_IN, false)
    }

    fun logout() {
        editor.clear()
        editor.apply()
    }
}
