package com.koms.app.data.model

import com.google.gson.annotations.SerializedName

// Generic API Response Wrapper
data class ApiResponse<T>(
    @field:SerializedName("success") val success: Boolean,
    @field:SerializedName("message") val message: String,
    @field:SerializedName("data") val data: T?,
    @field:SerializedName("errors") val errors: List<String>?,
)

data class User(
    @field:SerializedName("user_id") val userId: Int,
    @field:SerializedName("name") val name: String,
    @field:SerializedName("email") val email: String,
    @field:SerializedName("role") val role: String,
    @field:SerializedName("token") val token: String,
)

data class Dojo(
    @field:SerializedName("id") val id: Int,
    @field:SerializedName("name") val name: String,
    @field:SerializedName("location") val location: String,
    @field:SerializedName("master_name") val masterName: String,
    @field:SerializedName("training_days") val trainingDays: String?,
    @field:SerializedName("training_timings") val trainingTimings: String?,
)

data class AttendanceHistory(
    @field:SerializedName("session_date") val sessionDate: String,
    @field:SerializedName("start_time") val startTime: String?,
    @field:SerializedName("status") val status: String,
    @field:SerializedName("remarks") val remarks: String?,
)

data class FeeRecord(
    @field:SerializedName("id") val id: Int,
    @field:SerializedName("billing_month") val billingMonth: String,
    @field:SerializedName("amount_due") val amountDue: Double,
    @field:SerializedName("due_date") val dueDate: String,
    @field:SerializedName("status") val status: String,
    @field:SerializedName("fee_name") val feeName: String,
)

data class Tournament(
    @field:SerializedName("id") val id: Int,
    @field:SerializedName("name") val name: String,
    @field:SerializedName("event_date") val eventDate: String,
    @field:SerializedName("venue") val venue: String,
    @field:SerializedName("registration_deadline") val registrationDeadline: String,
)

data class Announcement(
    @field:SerializedName("id") val id: Int,
    @field:SerializedName("title") val title: String,
    @field:SerializedName("content") val content: String,
    @field:SerializedName("publish_date") val publishDate: String,
)

data class StudentProfileData(
    @field:SerializedName("student_id") val studentId: Int = 0,
    @field:SerializedName("member_id") val memberId: String? = null,
    @field:SerializedName("name") val name: String? = null,
    @field:SerializedName("first_name") val firstName: String? = null,
    @field:SerializedName("last_name") val lastName: String? = null,
    @field:SerializedName("email") val email: String? = null,
    @field:SerializedName("role") val role: String? = null,
    @field:SerializedName("dob") val dob: String? = null,
    @field:SerializedName("age") val age: Int = 0,
    @field:SerializedName("gender") val gender: String? = null,
    @field:SerializedName("blood_group") val bloodGroup: String? = null,
    @field:SerializedName("father_name") val fatherName: String? = null,
    @field:SerializedName("mother_name") val motherName: String? = null,
    @field:SerializedName("phone") val phone: String? = null,
    @field:SerializedName("alternate_phone") val alternatePhone: String? = null,
    @field:SerializedName("address") val address: String? = null,
    @field:SerializedName("date_of_joining") val dateOfJoining: String? = null,
    @field:SerializedName("dojo_name") val dojoName: String? = null,
    @field:SerializedName("dojo_location") val dojoLocation: String? = null,
    @field:SerializedName("training_schedule") val trainingSchedule: String? = null,
    @field:SerializedName("current_belt") val currentBelt: String? = null,
    @field:SerializedName("target_belt") val targetBelt: String? = null,
    @field:SerializedName("attendance_percentage") val attendancePercentage: Int = 0,
    @field:SerializedName("classes_attended") val classesAttended: Int = 0,
    @field:SerializedName("total_classes") val totalClasses: Int = 0,
    @field:SerializedName("pending_fees") val pendingFees: Double = 0.0,
    @field:SerializedName("tournament_entries") val tournamentEntries: Int = 0,
    @field:SerializedName("achievements_count") val achievementsCount: Int = 0,
    @field:SerializedName("certificates_count") val certificatesCount: Int = 0
)

data class StudentDashboardStats(
    @field:SerializedName("student_id") val studentId: Int = 0,
    @field:SerializedName("student_name") val studentName: String? = null,
    @field:SerializedName("student_code") val studentCode: String? = null,
    @field:SerializedName("attendance_percentage") val attendancePercentage: Int = 0,
    @field:SerializedName("present_count") val presentCount: Int = 0,
    @field:SerializedName("total_count") val totalCount: Int = 0,
    @field:SerializedName("pending_fees") val pendingFees: Double = 0.0,
    @field:SerializedName("tournament_entries") val tournamentEntries: Int = 0,
    @field:SerializedName("current_belt") val currentBelt: String? = null,
    @field:SerializedName("dojo_name") val dojoName: String? = null,
    @field:SerializedName("dojo_details") val dojoDetails: String? = null
)

