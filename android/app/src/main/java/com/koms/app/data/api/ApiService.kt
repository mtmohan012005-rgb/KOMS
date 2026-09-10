package com.koms.app.data.api

import com.koms.app.data.model.Announcement
import com.koms.app.data.model.ApiResponse
import com.koms.app.data.model.AttendanceHistory
import com.koms.app.data.model.Dojo
import com.koms.app.data.model.FeeRecord
import com.koms.app.data.model.Tournament
import com.koms.app.data.model.User
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {
    
    @POST("auth/login.php")
    suspend fun login(@Body request: Map<String, String>): Response<ApiResponse<User>>

    @GET("dojos/list.php")
    suspend fun getDojos(): Response<ApiResponse<List<Dojo>>>

    @GET("attendance/history.php")
    suspend fun getAttendanceHistory(@Query("student_id") studentId: Int): Response<ApiResponse<List<AttendanceHistory>>>

    @GET("fees/records.php")
    suspend fun getFeeRecords(@Query("student_id") studentId: Int): Response<ApiResponse<List<FeeRecord>>>

    @GET("tournaments/list.php")
    suspend fun getTournaments(): Response<ApiResponse<List<Tournament>>>

    @GET("announcements/list.php")
    suspend fun getAnnouncements(): Response<ApiResponse<List<Announcement>>>
}
