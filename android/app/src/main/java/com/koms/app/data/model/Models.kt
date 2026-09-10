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
