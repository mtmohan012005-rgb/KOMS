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
    @field:SerializedName("member_id") val memberId: String? = null,
    @field:SerializedName("name") val name: String,
    @field:SerializedName("email") val email: String,
    @field:SerializedName("role") val role: String,
    @field:SerializedName("dojo_id") val dojoId: Int? = null,
    @field:SerializedName("token") val token: String,
)

data class Dojo(
    @field:SerializedName("id") val id: Int,
    @field:SerializedName("name") val name: String,
    @field:SerializedName("location") val location: String,
    @field:SerializedName("master_name") val masterName: String? = null,
    @field:SerializedName("training_days") val trainingDays: String? = null,
    @field:SerializedName("training_timings") val trainingTimings: String? = null,
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

// Master Dashboard Data Models
data class MasterDashboardData(
    @field:SerializedName("master") val master: MasterInfo,
    @field:SerializedName("dojo") val dojo: DojoInfo,
    @field:SerializedName("summary") val summary: MasterSummaryStats,
    @field:SerializedName("attendance_overview") val attendanceOverview: AttendanceOverviewStats,
    @field:SerializedName("fee_collection") val feeCollection: FeeCollectionStats,
    @field:SerializedName("recent_requests") val recentRequests: List<StudentRequestItem> = emptyList(),
    @field:SerializedName("registered_students") val registeredStudents: List<RegisteredStudentItem> = emptyList(),
    @field:SerializedName("class_schedule") val classSchedule: List<ClassScheduleItem> = emptyList(),
    @field:SerializedName("recent_activities") val recentActivities: List<ActivityItem> = emptyList(),
    @field:SerializedName("quote") val quote: QuoteItem? = null
)

data class MasterInfo(
    @field:SerializedName("id") val id: Int,
    @field:SerializedName("name") val name: String,
    @field:SerializedName("role") val role: String,
    @field:SerializedName("email") val email: String? = null
)

data class DojoInfo(
    @field:SerializedName("id") val id: Int,
    @field:SerializedName("name") val name: String,
    @field:SerializedName("location") val location: String,
    @field:SerializedName("training_days") val trainingDays: String? = null,
    @field:SerializedName("training_timings") val trainingTimings: String? = null
)

data class MasterSummaryStats(
    @field:SerializedName("total_students") val totalStudents: Int = 0,
    @field:SerializedName("active_students") val activeStudents: Int = 0,
    @field:SerializedName("active_students_pct") val activeStudentsPct: Int = 0,
    @field:SerializedName("pending_requests") val pendingRequests: Int = 0,
    @field:SerializedName("today_attendance_present") val todayAttendancePresent: Int = 0,
    @field:SerializedName("today_attendance_total") val todayAttendanceTotal: Int = 0,
    @field:SerializedName("today_attendance_pct") val todayAttendancePct: Int = 0,
    @field:SerializedName("monthly_attendance_pct") val monthlyAttendancePct: Int = 0,
    @field:SerializedName("monthly_attendance_trend") val monthlyAttendanceTrend: String? = "+12%",
    @field:SerializedName("pending_fees") val pendingFees: Double = 0.0,
    @field:SerializedName("pending_fees_students_count") val pendingFeesStudentsCount: Int = 0,
    @field:SerializedName("upcoming_classes_count") val upcomingClassesCount: Int = 0
)

data class AttendanceOverviewStats(
    @field:SerializedName("present") val present: Int = 0,
    @field:SerializedName("late") val late: Int = 0,
    @field:SerializedName("absent") val absent: Int = 0,
    @field:SerializedName("excused") val excused: Int = 0,
    @field:SerializedName("percentage") val percentage: Int = 0,
    @field:SerializedName("today_class_title") val todayClassTitle: String? = "Karate Training",
    @field:SerializedName("today_class_timing") val todayClassTiming: String? = "06:00 PM - 07:30 PM"
)

data class FeeCollectionStats(
    @field:SerializedName("total") val total: Double = 0.0,
    @field:SerializedName("collected") val collected: Double = 0.0,
    @field:SerializedName("pending") val pending: Double = 0.0,
    @field:SerializedName("percentage") val percentage: Int = 0
)

data class StudentRequestItem(
    @field:SerializedName("index") val index: Int = 0,
    @field:SerializedName("request_id") val requestId: Int = 0,
    @field:SerializedName("student_id") val studentId: Int = 0,
    @field:SerializedName("student_name") val studentName: String = "",
    @field:SerializedName("student_code") val studentCode: String = "",
    @field:SerializedName("email") val email: String? = null,
    @field:SerializedName("phone") val phone: String? = null,
    @field:SerializedName("date") val date: String = "",
    @field:SerializedName("training_level") val trainingLevel: String = "Beginner",
    @field:SerializedName("experience") val experience: String = "None",
    @field:SerializedName("status") val status: String = "Pending"
)

data class RegisteredStudentItem(
    @field:SerializedName("index") val index: Int = 0,
    @field:SerializedName("student_id") val studentId: Int = 0,
    @field:SerializedName("name") val name: String = "",
    @field:SerializedName("student_code") val studentCode: String = "",
    @field:SerializedName("belt") val belt: String = "White",
    @field:SerializedName("clean_belt") val cleanBelt: String = "White",
    @field:SerializedName("training_level") val trainingLevel: String = "Beginner",
    @field:SerializedName("attendance_pct") val attendancePct: Int = 0,
    @field:SerializedName("status") val status: String = "Active",
    @field:SerializedName("phone") val phone: String? = null,
    @field:SerializedName("email") val email: String? = null,
    @field:SerializedName("pending_fees") val pendingFees: Double = 0.0,
    @field:SerializedName("date_of_joining") val dateOfJoining: String? = null
)

data class ClassScheduleItem(
    @field:SerializedName("day") val day: String = "",
    @field:SerializedName("day_full") val dayFull: String? = null,
    @field:SerializedName("title") val title: String = "Karate Training",
    @field:SerializedName("timing") val timing: String = "",
    @field:SerializedName("type") val type: String? = "Regular"
)

data class ActivityItem(
    @field:SerializedName("id") val id: Int = 0,
    @field:SerializedName("title") val title: String = "",
    @field:SerializedName("time") val time: String = "",
    @field:SerializedName("module") val module: String? = null,
    @field:SerializedName("action") val action: String? = null
)

data class QuoteItem(
    @field:SerializedName("text") val text: String = "",
    @field:SerializedName("author") val author: String = ""
)

data class AttendanceSessionStudentItem(
    @field:SerializedName("student_id") val studentId: Int,
    @field:SerializedName("name") val name: String,
    @field:SerializedName("student_code") val studentCode: String,
    @field:SerializedName("belt") val belt: String,
    @field:SerializedName("status") var status: String,
    @field:SerializedName("remarks") var remarks: String? = null
)

data class AttendanceRosterResponse(
    @field:SerializedName("session_id") val sessionId: Int = 0,
    @field:SerializedName("session_date") val sessionDate: String = "",
    @field:SerializedName("class_title") val classTitle: String = "Karate Training",
    @field:SerializedName("class_timing") val classTiming: String = "06:00 PM - 07:30 PM",
    @field:SerializedName("students") val students: List<AttendanceSessionStudentItem> = emptyList()
)

data class DojoFeeSummaryResponse(
    @field:SerializedName("summary") val summary: FeeCollectionStats,
    @field:SerializedName("students") val students: List<StudentFeeItem> = emptyList(),
    @field:SerializedName("recent_payments") val recentPayments: List<PaymentItem> = emptyList()
)

data class StudentFeeItem(
    @field:SerializedName("student_id") val studentId: Int,
    @field:SerializedName("name") val name: String,
    @field:SerializedName("student_code") val studentCode: String,
    @field:SerializedName("amount_due") val amountDue: Double,
    @field:SerializedName("amount_paid") val amountPaid: Double,
    @field:SerializedName("amount_pending") val amountPending: Double,
    @field:SerializedName("status") val status: String
)

data class PaymentItem(
    @field:SerializedName("payment_id") val paymentId: Int,
    @field:SerializedName("student_id") val studentId: Int,
    @field:SerializedName("student_name") val studentName: String,
    @field:SerializedName("amount") val amount: Double,
    @field:SerializedName("payment_method") val paymentMethod: String,
    @field:SerializedName("transaction_ref") val transactionRef: String?,
    @field:SerializedName("payment_date") val paymentDate: String
)

data class AchievementItem(
    @field:SerializedName("id") val id: Int = 0,
    @field:SerializedName("title") val title: String = "",
    @field:SerializedName("competition_event") val competitionEvent: String? = null,
    @field:SerializedName("position_result") val positionResult: String? = null,
    @field:SerializedName("date") val date: String = "",
    @field:SerializedName("description") val description: String? = null
)

data class CertificateItem(
    @field:SerializedName("id") val id: Int = 0,
    @field:SerializedName("certificate_type") val certificateType: String = "",
    @field:SerializedName("title") val title: String = "",
    @field:SerializedName("certificate_number") val certificateNumber: String = "",
    @field:SerializedName("date") val date: String = "",
    @field:SerializedName("notes") val notes: String? = null
)

data class BeltHistoryItem(
    @field:SerializedName("id") val id: Int = 0,
    @field:SerializedName("previous_belt") val previousBelt: String? = null,
    @field:SerializedName("new_belt") val newBelt: String = "",
    @field:SerializedName("exam_date") val examDate: String = "",
    @field:SerializedName("grade") val grade: String? = null,
    @field:SerializedName("remarks") val remarks: String? = null
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
    @field:SerializedName("master_name") val masterName: String? = null,
    @field:SerializedName("master_role") val masterRole: String? = null,
    @field:SerializedName("dojo_students_count") val dojoStudentsCount: Int = 0,
    @field:SerializedName("training_schedule") val trainingSchedule: String? = null,
    @field:SerializedName("current_belt") val currentBelt: String? = null,
    @field:SerializedName("target_belt") val targetBelt: String? = null,
    @field:SerializedName("training_level") val trainingLevel: String? = null,
    @field:SerializedName("promotion_date") val promotionDate: String? = null,
    @field:SerializedName("attendance_percentage") val attendancePercentage: Int = 0,
    @field:SerializedName("classes_attended") val classesAttended: Int = 0,
    @field:SerializedName("total_classes") val totalClasses: Int = 0,
    @field:SerializedName("fees_total") val feesTotal: Double = 0.0,
    @field:SerializedName("fees_paid") val feesPaid: Double = 0.0,
    @field:SerializedName("pending_fees") val pendingFees: Double = 0.0,
    @field:SerializedName("fees_percentage") val feesPercentage: Int = 0,
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
    @field:SerializedName("fees_total") val feesTotal: Double = 0.0,
    @field:SerializedName("fees_paid") val feesPaid: Double = 0.0,
    @field:SerializedName("pending_fees") val pendingFees: Double = 0.0,
    @field:SerializedName("fees_percentage") val feesPercentage: Int = 0,
    @field:SerializedName("achievements_count") val achievementsCount: Int = 0,
    @field:SerializedName("certificates_count") val certificatesCount: Int = 0,
    @field:SerializedName("tournament_entries") val tournamentEntries: Int = 0,
    @field:SerializedName("current_belt") val currentBelt: String? = null,
    @field:SerializedName("target_belt") val targetBelt: String? = null,
    @field:SerializedName("training_level") val trainingLevel: String? = null,
    @field:SerializedName("promotion_date") val promotionDate: String? = null,
    @field:SerializedName("dojo_name") val dojoName: String? = null,
    @field:SerializedName("dojo_location") val dojoLocation: String? = null,
    @field:SerializedName("master_name") val masterName: String? = null,
    @field:SerializedName("master_role") val masterRole: String? = null,
    @field:SerializedName("total_students") val totalStudents: Int = 0,
    @field:SerializedName("dojo_details") val dojoDetails: String? = null
)
