package com.koms.app.data.api

import com.koms.app.data.model.*
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {
    
    @POST("auth/login.php")
    suspend fun login(@Body request: Map<String, String>): Response<ApiResponse<User>>

    // Student Portal Endpoints
    @GET("students/dashboard.php")
    suspend fun getDashboardStats(@Query("student_id") studentId: Int? = null): Response<ApiResponse<StudentDashboardStats>>

    @GET("students/profile.php")
    suspend fun getStudentProfile(@Query("student_id") studentId: Int? = null): Response<ApiResponse<StudentProfileData>>

    @POST("students/update_profile.php")
    suspend fun updateStudentProfile(@Body body: Map<String, String>): Response<ApiResponse<Map<String, Any>>>

    @GET("attendance/history.php")
    suspend fun getAttendanceHistory(@Query("student_id") studentId: Int): Response<ApiResponse<List<AttendanceHistory>>>

    @GET("fees/records.php")
    suspend fun getFeeRecords(@Query("student_id") studentId: Int): Response<ApiResponse<List<FeeRecord>>>

    @GET("dojos/list.php")
    suspend fun getDojos(): Response<ApiResponse<List<Dojo>>>

    @GET("tournaments/list.php")
    suspend fun getTournaments(): Response<ApiResponse<List<Tournament>>>

    @GET("announcements/list.php")
    suspend fun getAnnouncements(): Response<ApiResponse<List<Announcement>>>

    // Master Portal Endpoints
    @GET("master/dashboard.php")
    suspend fun getMasterDashboard(): Response<ApiResponse<MasterDashboardData>>

    @GET("master/student_requests.php")
    suspend fun getStudentRequests(): Response<ApiResponse<List<StudentRequestItem>>>

    @POST("master/student_requests.php")
    suspend fun processStudentRequest(@Body body: Map<String, Any>): Response<ApiResponse<Map<String, Any>>>

    @GET("master/students.php")
    suspend fun getRegisteredStudents(): Response<ApiResponse<List<RegisteredStudentItem>>>

    @GET("master/student_profile.php")
    suspend fun getMasterStudentProfile(@Query("student_id") studentId: Int): Response<ApiResponse<Map<String, Any>>>

    @GET("master/attendance.php")
    suspend fun getDojoAttendanceToday(@Query("date") date: String? = null): Response<ApiResponse<AttendanceRosterResponse>>

    @POST("master/attendance.php")
    suspend fun saveDojoAttendance(@Body body: Map<String, Any>): Response<ApiResponse<Map<String, Any>>>

    @GET("master/fees.php")
    suspend fun getMasterFeeSummary(): Response<ApiResponse<DojoFeeSummaryResponse>>

    @POST("master/fees.php")
    suspend fun recordFeePayment(@Body body: Map<String, Any>): Response<ApiResponse<Map<String, Any>>>

    @POST("master/grading.php")
    suspend fun promoteStudentBelt(@Body body: Map<String, Any>): Response<ApiResponse<Map<String, Any>>>

    @POST("master/achievements.php")
    suspend fun addAchievement(@Body body: Map<String, Any>): Response<ApiResponse<Map<String, Any>>>

    @POST("master/certificates.php")
    suspend fun addCertificate(@Body body: Map<String, Any>): Response<ApiResponse<Map<String, Any>>>

    @GET("master/schedule.php")
    suspend fun getDojoSchedule(): Response<ApiResponse<Map<String, Any>>>

    @POST("master/schedule.php")
    suspend fun updateDojoSchedule(@Body body: Map<String, Any>): Response<ApiResponse<Map<String, Any>>>

    // Master Dojo Registration
    @POST("master/register_dojo.php")
    suspend fun registerDojo(@Body body: Map<String, Any>): Response<ApiResponse<Map<String, Any>>>

    // Student Join Dojo
    @POST("students/join_dojo.php")
    suspend fun joinDojo(@Body body: Map<String, Any>): Response<ApiResponse<Map<String, Any>>>

    // Grand Master / Admin Endpoints
    @GET("admin/dashboard.php")
    suspend fun getAdminDashboard(): Response<ApiResponse<Map<String, Any>>>

    @GET("admin/master_requests.php")
    suspend fun getPendingDojos(): Response<ApiResponse<List<Map<String, Any>>>>

    @POST("admin/master_requests.php")
    suspend fun processDojoRequest(@Body body: Map<String, Any>): Response<ApiResponse<Map<String, Any>>>
}
