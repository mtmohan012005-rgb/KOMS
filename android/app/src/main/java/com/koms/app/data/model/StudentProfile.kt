package com.koms.app.data.model

import com.google.gson.annotations.SerializedName

data class StudentProfile(
    @SerializedName("id") val id: Int = 0,
    @SerializedName("member_id") val memberId: String? = null,
    @SerializedName("first_name") val firstName: String? = null,
    @SerializedName("last_name") val lastName: String? = null,
    @SerializedName("full_name") val fullName: String? = null,
    @SerializedName("email") val email: String? = null,
    @SerializedName("phone") val phone: String? = null,
    @SerializedName("alternate_phone") val alternatePhone: String? = null,
    @SerializedName("dob") val dob: String? = null,
    @SerializedName("formatted_dob") val formattedDob: String? = null,
    @SerializedName("age") val age: Int = 0,
    @SerializedName("gender") val gender: String? = null,
    @SerializedName("blood_group") val bloodGroup: String? = null,
    @SerializedName("father_name") val fatherName: String? = null,
    @SerializedName("mother_name") val motherName: String? = null,
    @SerializedName("address") val address: String? = null,
    @SerializedName("date_of_joining") val dateOfJoining: String? = null,
    @SerializedName("current_belt") val currentBelt: String? = null,
    @SerializedName("belt_kyu") val beltKyu: String? = null,
    @SerializedName("dojo_name") val dojoName: String? = null,
    @SerializedName("dojo_location") val dojoLocation: String? = null,
    @SerializedName("attendance_percentage") val attendancePercentage: Int = 92,
    @SerializedName("pending_fees") val pendingFees: Double = 0.0,
    @SerializedName("status") val status: String? = "active"
)
