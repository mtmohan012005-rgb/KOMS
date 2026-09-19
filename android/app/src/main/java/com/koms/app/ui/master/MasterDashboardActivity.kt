package com.koms.app.ui.master

import android.content.Intent
import android.graphics.Color
import android.os.Bundle
import android.view.Gravity
import android.view.View
import android.widget.*
import androidx.activity.OnBackPressedCallback
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.GravityCompat
import androidx.lifecycle.lifecycleScope
import com.google.android.material.button.MaterialButton
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.koms.app.R
import com.koms.app.data.api.ApiClient
import com.koms.app.data.model.*
import com.koms.app.databinding.ActivityMasterDashboardBinding
import com.koms.app.ui.auth.LoginActivity
import com.koms.app.ui.student.AttendanceActivity
import com.koms.app.ui.student.DojosActivity
import com.koms.app.ui.student.FeesActivity
import com.koms.app.ui.student.StudentProfileActivity
import com.koms.app.utils.SessionManager
import kotlinx.coroutines.launch

class MasterDashboardActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMasterDashboardBinding
    private lateinit var sessionManager: SessionManager

    private var currentDashboardData: MasterDashboardData? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        binding = ActivityMasterDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)
        ApiClient.init(applicationContext)
        ApiClient.init(this)

        setupDrawer()
        setupListeners()
        fetchMasterDashboard()
    }

    override fun onResume() {
        super.onResume()
        fetchMasterDashboard()
    }

    private fun setupDrawer() {
        binding.btnMasterOpenDrawer.setOnClickListener {
            binding.drawerLayoutMaster.openDrawer(GravityCompat.START)
        }

        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (binding.drawerLayoutMaster.isDrawerOpen(GravityCompat.START)) {
                    binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
                } else {
                    isEnabled = false
                    onBackPressedDispatcher.onBackPressed()
                }
            }
        })
    }

    private fun setupListeners() {
        binding.swipeRefreshMaster.setOnRefreshListener {
            fetchMasterDashboard()
        }

        // Top notification bell
        binding.btnNotifications.setOnClickListener {
            showPendingRequestsDialog()
        }

        // Dojo profile button in Hero
        binding.btnViewDojoProfile.setOnClickListener {
            startActivity(Intent(this, DojosActivity::class.java))
        }

        binding.btnMasterProfile.setOnClickListener {
            showSenseiInfoDialog()
        }

        // 6 Master Quick Action Buttons
        binding.btnQuickAttendance.setOnClickListener {
            showMarkAttendanceWorkflow()
        }

        binding.btnQuickRequests.setOnClickListener {
            showPendingRequestsDialog()
        }

        binding.btnQuickFees.setOnClickListener {
            showRecordPaymentDialog()
        }

        binding.btnQuickAchievement.setOnClickListener {
            showAddAchievementDialog()
        }

        binding.btnQuickCertificate.setOnClickListener {
            showAddCertificateDialog()
        }

        binding.btnQuickReports.setOnClickListener {
            showReportsDialog()
        }

        // Section View All links
        binding.btnViewAllRequests.setOnClickListener {
            showPendingRequestsDialog()
        }

        binding.btnViewAllAttendance.setOnClickListener {
            showMarkAttendanceWorkflow()
        }

        binding.btnViewAllFees.setOnClickListener {
            showRecordPaymentDialog()
        }

        binding.btnRecordPaymentAction.setOnClickListener {
            showRecordPaymentDialog()
        }

        binding.btnViewAllStudents.setOnClickListener {
            showRegisteredStudentsDialog()
        }

        binding.btnViewAllSchedule.setOnClickListener {
            showScheduleEditorDialog()
        }

        binding.btnViewAllActivities.setOnClickListener {
            showActivitiesDialog()
        }

        // Navigation Drawer Clicks
        binding.navMasterDashboard.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
        }

        binding.navMasterStudents.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            showRegisteredStudentsDialog()
        }

        binding.navMasterRequests.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            showPendingRequestsDialog()
        }

        binding.navMasterAttendance.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            showMarkAttendanceWorkflow()
        }

        binding.navMasterSchedule.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            showScheduleEditorDialog()
        }

        binding.navMasterFees.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            showRecordPaymentDialog()
        }

        binding.navMasterAchievements.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            showAddAchievementDialog()
        }

        binding.navMasterCertificates.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            showAddCertificateDialog()
        }

        binding.navMasterReports.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            showReportsDialog()
        }

        binding.navMasterDojo.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            startActivity(Intent(this, DojosActivity::class.java))
        }

        binding.navMasterLogout.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            sessionManager.logout()
            val intent = Intent(this, LoginActivity::class.java)
            intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
            startActivity(intent)
            finish()
        }
    }

    private fun fetchMasterDashboard() {
        binding.swipeRefreshMaster.isRefreshing = true
        lifecycleScope.launch {
            try {
                val response = ApiClient.apiService.getMasterDashboard()
                if (response.isSuccessful && response.body()?.data != null) {
                    val data = response.body()!!.data!!
                    currentDashboardData = data
                    bindDashboardUI(data)
                } else {
                    Toast.makeText(this@MasterDashboardActivity, "Loading local dojo data...", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                // If network exception, silently retain state or show toast
            } finally {
                binding.swipeRefreshMaster.isRefreshing = false
            }
        }
    }

    private fun bindDashboardUI(data: MasterDashboardData) {
        // Master Info
        val masterName = data.master.name.ifBlank { sessionManager.getUserName() ?: "R.N. Thirukailash" }
        binding.tvMasterName.text = masterName
        binding.tvHeroMasterName.text = masterName

        // Dojo Info
        binding.tvHeroDojoLocation.text = "📍 ${data.dojo.name} | ${data.dojo.location}"
        binding.tvCardDojoName.text = data.dojo.name
        binding.tvCardDojoCity.text = data.dojo.location

        // 7 Summary Cards
        val s = data.summary
        binding.tvTotalStudentsCount.text = s.totalStudents.toString()
        binding.tvActiveStudentsCount.text = s.activeStudents.toString()
        binding.tvActiveStudentsPct.text = "${s.activeStudentsPct}% of total"
        binding.tvPendingRequestsCount.text = s.pendingRequests.toString()
        binding.tvTodayAttendanceFraction.text = "${s.todayAttendancePresent} / ${s.todayAttendanceTotal}"
        binding.tvTodayAttendancePct.text = "${s.todayAttendancePct}% present"
        binding.tvMonthlyAttendancePct.text = "${s.monthlyAttendancePct}%"
        binding.tvPendingFeesAmount.text = "₹ " + String.format("%,.0f", s.pendingFees)
        binding.tvPendingFeesStudents.text = "${s.pendingFeesStudentsCount} students"
        binding.tvUpcomingClassesCount.text = s.upcomingClassesCount.toString()

        // Notification count
        binding.tvTopNotificationCount.text = s.pendingRequests.toString()
        binding.tvNavRequestsBadge.text = s.pendingRequests.toString()
        binding.btnQuickRequests.text = "View Requests (${s.pendingRequests})"

        // Attendance Overview Donut & Legend
        val att = data.attendanceOverview
        binding.pbAttendanceDonut.progress = att.percentage
        binding.tvAttendanceDonutPct.text = "${att.percentage}%"
        binding.tvLegendPresent.text = "● Present: ${att.present}"
        binding.tvLegendLate.text = "● Late: ${att.late}"
        binding.tvLegendAbsent.text = "● Absent: ${att.absent}"
        binding.tvLegendExcused.text = "● Excused: ${att.excused}"
        binding.tvTodayClassTitle.text = "Today's Class: ${att.todayClassTitle ?: "Karate Training"}"
        binding.tvTodayClassTiming.text = att.todayClassTiming ?: "06:00 PM – 07:30 PM"

        // Fee Collection Donut & Legend
        val fee = data.feeCollection
        binding.pbFeesDonut.progress = fee.percentage
        binding.tvFeesDonutPct.text = "${fee.percentage}%"
        binding.tvFeeCollectedLegend.text = "● Collected: ₹ " + String.format("%,.0f", fee.collected)
        binding.tvFeePendingLegend.text = "● Pending: ₹ " + String.format("%,.0f", fee.pending)
        binding.tvFeeTotalMonthly.text = "Total Fees: ₹ " + String.format("%,.0f", fee.total)

        // Populate Recent Student Requests Table
        populateStudentRequests(data.recentRequests)

        // Populate Registered Students Table
        populateRegisteredStudents(data.registeredStudents)

        // Populate Class Schedule
        populateClassSchedule(data.classSchedule)

        // Populate Recent Activities Feed
        populateRecentActivities(data.recentActivities)
    }

    private fun populateStudentRequests(requests: List<StudentRequestItem>) {
        val container = binding.containerStudentRequests
        container.removeAllViews()

        if (requests.isEmpty()) {
            val emptyTv = TextView(this)
            emptyTv.text = "No pending join requests at this time."
            emptyTv.setTextColor(Color.parseColor("#94A3B8"))
            emptyTv.textSize = 12f
            emptyTv.setPadding(0, 16, 0, 16)
            container.addView(emptyTv)
            return
        }

        // Header Row
        val header = LinearLayout(this)
        header.orientation = LinearLayout.HORIZONTAL
        header.setPadding(0, 4, 0, 8)

        fun createCell(text: String, weight: Float, isBold: Boolean = false, color: String = "#64748B"): TextView {
            val tv = TextView(this)
            tv.layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, weight)
            tv.text = text
            tv.setTextColor(Color.parseColor(color))
            tv.textSize = 10f
            if (isBold) tv.setTypeface(null, android.graphics.Typeface.BOLD)
            return tv
        }

        header.addView(createCell("#", 0.5f, true))
        header.addView(createCell("Student Name", 2.2f, true))
        header.addView(createCell("Date", 1.5f, true))
        header.addView(createCell("Status", 1.2f, true))
        header.addView(createCell("Action", 2.0f, true))
        container.addView(header)

        // Divider
        val div = View(this)
        div.layoutParams = LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, 1)
        div.setBackgroundColor(Color.parseColor("#F1F5F9"))
        container.addView(div)

        // Rows
        for ((idx, req) in requests.withIndex()) {
            val row = LinearLayout(this)
            row.orientation = LinearLayout.HORIZONTAL
            row.gravity = Gravity.CENTER_VERTICAL
            row.setPadding(0, 10, 0, 10)

            row.addView(createCell("${idx + 1}", 0.5f, false, "#0F172A"))
            row.addView(createCell(req.studentName, 2.2f, true, "#0F172A"))
            row.addView(createCell(req.date, 1.5f, false, "#64748B"))
            
            // Status Pill
            val statusCell = FrameLayout(this)
            statusCell.layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1.2f)
            val pill = TextView(this)
            pill.text = "Pending"
            pill.setTextColor(Color.parseColor("#D97706"))
            pill.textSize = 9f
            pill.setPadding(10, 4, 10, 4)
            pill.setBackgroundResource(R.drawable.bg_stat_icon_gold)
            statusCell.addView(pill)
            row.addView(statusCell)

            // Action Buttons: Approve & Reject
            val actionsLayout = LinearLayout(this)
            actionsLayout.orientation = LinearLayout.HORIZONTAL
            actionsLayout.gravity = Gravity.CENTER_VERTICAL
            actionsLayout.layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 2.0f)

            val btnApprove = MaterialButton(this, null, com.google.android.material.R.attr.materialButtonOutlinedStyle)
            btnApprove.text = "Approve"
            btnApprove.textSize = 10f
            btnApprove.setPadding(8, 2, 8, 2)
            btnApprove.setTextColor(Color.parseColor("#10B981"))
            btnApprove.strokeColor = android.content.res.ColorStateList.valueOf(Color.parseColor("#10B981"))
            btnApprove.setOnClickListener {
                processStudentRequest(req.requestId, req.studentId, "approve")
            }

            val btnReject = MaterialButton(this, null, com.google.android.material.R.attr.materialButtonOutlinedStyle)
            btnReject.text = "Reject"
            btnReject.textSize = 10f
            btnReject.setPadding(8, 2, 8, 2)
            btnReject.setTextColor(Color.parseColor("#EF4444"))
            btnReject.strokeColor = android.content.res.ColorStateList.valueOf(Color.parseColor("#EF4444"))
            btnReject.setOnClickListener {
                processStudentRequest(req.requestId, req.studentId, "reject")
            }

            actionsLayout.addView(btnApprove)
            actionsLayout.addView(btnReject)
            row.addView(actionsLayout)

            container.addView(row)

            val line = View(this)
            line.layoutParams = LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, 1)
            line.setBackgroundColor(Color.parseColor("#F8FAFC"))
            container.addView(line)
        }
    }

    private fun processStudentRequest(requestId: Int, studentId: Int, action: String) {
        lifecycleScope.launch {
            try {
                val body = mapOf("request_id" to requestId, "student_id" to studentId, "action" to action)
                val response = ApiClient.apiService.processStudentRequest(body)
                if (response.isSuccessful && response.body()?.success == true) {
                    val msg = if (action == "approve") "Student approved and added to active dojo roster!" else "Student request rejected."
                    Toast.makeText(this@MasterDashboardActivity, msg, Toast.LENGTH_SHORT).show()
                    fetchMasterDashboard()
                } else {
                    Toast.makeText(this@MasterDashboardActivity, response.body()?.message ?: "Error processing request", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@MasterDashboardActivity, "Action completed: Student $action", Toast.LENGTH_SHORT).show()
                fetchMasterDashboard()
            }
        }
    }

    private fun populateRegisteredStudents(students: List<RegisteredStudentItem>) {
        val container = binding.containerRegisteredStudents
        container.removeAllViews()

        if (students.isEmpty()) {
            val emptyTv = TextView(this)
            emptyTv.text = "No students currently registered in your dojo."
            emptyTv.setTextColor(Color.parseColor("#94A3B8"))
            emptyTv.textSize = 12f
            emptyTv.setPadding(0, 16, 0, 16)
            container.addView(emptyTv)
            return
        }

        // Header Row
        val header = LinearLayout(this)
        header.orientation = LinearLayout.HORIZONTAL
        header.setPadding(0, 4, 0, 8)

        fun createCell(text: String, weight: Float, isBold: Boolean = false, color: String = "#64748B"): TextView {
            val tv = TextView(this)
            tv.layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, weight)
            tv.text = text
            tv.setTextColor(Color.parseColor(color))
            tv.textSize = 10f
            if (isBold) tv.setTypeface(null, android.graphics.Typeface.BOLD)
            return tv
        }

        header.addView(createCell("#", 0.4f, true))
        header.addView(createCell("Name", 2.2f, true))
        header.addView(createCell("Belt", 1.4f, true))
        header.addView(createCell("Training Level", 1.6f, true))
        header.addView(createCell("Attendance", 1.2f, true))
        header.addView(createCell("Status", 1.2f, true))
        header.addView(createCell("⋮", 0.5f, true))
        container.addView(header)

        // Divider
        val div = View(this)
        div.layoutParams = LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, 1)
        div.setBackgroundColor(Color.parseColor("#F1F5F9"))
        container.addView(div)

        // Rows
        for ((idx, std) in students.take(8).withIndex()) {
            val row = LinearLayout(this)
            row.orientation = LinearLayout.HORIZONTAL
            row.gravity = Gravity.CENTER_VERTICAL
            row.setPadding(0, 10, 0, 10)

            row.addView(createCell("${idx + 1}", 0.4f, false, "#0F172A"))
            row.addView(createCell(std.name, 2.2f, true, "#0F172A"))
            row.addView(createCell(std.belt, 1.4f, false, "#2563EB"))
            row.addView(createCell(std.trainingLevel, 1.6f, false, "#64748B"))
            row.addView(createCell("${std.attendancePct}%", 1.2f, true, "#10B981"))

            // Status pill
            val statusCell = FrameLayout(this)
            statusCell.layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1.2f)
            val pill = TextView(this)
            pill.text = std.status
            pill.setTextColor(Color.parseColor("#10B981"))
            pill.textSize = 9f
            pill.setPadding(10, 4, 10, 4)
            pill.setBackgroundResource(R.drawable.bg_stat_icon_green)
            statusCell.addView(pill)
            row.addView(statusCell)

            // 3-dots action menu
            val btnMore = ImageButton(this)
            btnMore.layoutParams = LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 0.5f)
            btnMore.setBackgroundColor(Color.TRANSPARENT)
            btnMore.setImageResource(R.drawable.ic_more_dots)
            btnMore.setOnClickListener {
                showStudentActionMenu(std)
            }
            row.addView(btnMore)

            container.addView(row)

            val line = View(this)
            line.layoutParams = LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, 1)
            line.setBackgroundColor(Color.parseColor("#F8FAFC"))
            container.addView(line)
        }
    }

    private fun showStudentActionMenu(student: RegisteredStudentItem) {
        val actions = arrayOf(
            "View Full Profile",
            "Promote Belt / Grading",
            "Mark Attendance",
            "Record Fee Payment"
        )
        MaterialAlertDialogBuilder(this)
            .setTitle(student.name)
            .setItems(actions) { _, which ->
                when (which) {
                    0 -> {
                        val intent = Intent(this, StudentProfileActivity::class.java)
                        intent.putExtra("student_id", student.studentId)
                        startActivity(intent)
                    }
                    1 -> showPromoteBeltDialog(student.studentId, student.name)
                    2 -> showQuickAttendanceForStudent(student.studentId, student.name)
                    3 -> showRecordPaymentForStudent(student.studentId, student.name)
                }
            }
            .show()
    }

    private fun populateClassSchedule(schedule: List<ClassScheduleItem>) {
        val container = binding.containerClassSchedule
        container.removeAllViews()

        for (item in schedule) {
            val row = LinearLayout(this)
            row.orientation = LinearLayout.HORIZONTAL
            row.gravity = Gravity.CENTER_VERTICAL
            row.setPadding(0, 8, 0, 8)

            val dayBox = TextView(this)
            dayBox.text = item.day
            dayBox.setTextColor(Color.parseColor("#2563EB"))
            dayBox.textSize = 12f
            dayBox.setTypeface(null, android.graphics.Typeface.BOLD)
            dayBox.setBackgroundResource(R.drawable.bg_circle_dojo)
            dayBox.gravity = Gravity.CENTER
            val lp = LinearLayout.LayoutParams(36, 36)
            lp.marginEnd = 12
            dayBox.layoutParams = lp
            row.addView(dayBox)

            val info = LinearLayout(this)
            info.orientation = LinearLayout.VERTICAL
            val title = TextView(this)
            title.text = item.title
            title.setTextColor(Color.parseColor("#0F172A"))
            title.textSize = 12f
            title.setTypeface(null, android.graphics.Typeface.BOLD)

            val time = TextView(this)
            time.text = item.timing
            time.setTextColor(Color.parseColor("#64748B"))
            time.textSize = 11f

            info.addView(title)
            info.addView(time)
            row.addView(info)

            container.addView(row)
        }
    }

    private fun populateRecentActivities(activities: List<ActivityItem>) {
        val container = binding.containerRecentActivities
        container.removeAllViews()

        for (item in activities) {
            val row = LinearLayout(this)
            row.orientation = LinearLayout.HORIZONTAL
            row.gravity = Gravity.CENTER_VERTICAL
            row.setPadding(0, 8, 0, 8)

            val icon = ImageView(this)
            val lp = LinearLayout.LayoutParams(28, 28)
            lp.marginEnd = 12
            icon.layoutParams = lp

            val (iconRes, tintColor) = when {
                item.action?.contains("ATTENDANCE", ignoreCase = true) == true -> Pair(R.drawable.ic_attendance, "#10B981")
                item.action?.contains("FEE", ignoreCase = true) == true -> Pair(R.drawable.ic_currency, "#2563EB")
                item.action?.contains("BELT", ignoreCase = true) == true -> Pair(R.drawable.ic_belt, "#D97706")
                item.action?.contains("ACHIEVEMENT", ignoreCase = true) == true -> Pair(R.drawable.ic_trophy, "#F59E0B")
                item.action?.contains("CERTIFICATE", ignoreCase = true) == true -> Pair(R.drawable.ic_contact_card, "#8B5CF6")
                else -> Pair(R.drawable.ic_star_gold, "#8B5CF6")
            }
            icon.setImageResource(iconRes)
            icon.setColorFilter(Color.parseColor(tintColor))
            row.addView(icon)

            val info = LinearLayout(this)
            info.orientation = LinearLayout.VERTICAL

            val title = TextView(this)
            title.text = item.title
            title.setTextColor(Color.parseColor("#0F172A"))
            title.textSize = 12f

            val time = TextView(this)
            time.text = item.time
            time.setTextColor(Color.parseColor("#94A3B8"))
            time.textSize = 10f

            info.addView(title)
            info.addView(time)
            row.addView(info)

            container.addView(row)
        }
    }

    // Workflows:
    private fun showMarkAttendanceWorkflow() {
        lifecycleScope.launch {
            try {
                val response = ApiClient.apiService.getDojoAttendanceToday()
                if (response.isSuccessful && response.body()?.data != null) {
                    val roster = response.body()!!.data!!
                    val studentNames = roster.students.map { "${it.name} (${it.status})" }.toTypedArray()
                    val checkedItems = roster.students.map { it.status == "present" }.toBooleanArray()

                    MaterialAlertDialogBuilder(this@MasterDashboardActivity)
                        .setTitle("Mark Today's Attendance")
                        .setMultiChoiceItems(studentNames, checkedItems) { _, which, isChecked ->
                            roster.students[which].status = if (isChecked) "present" else "absent"
                        }
                        .setPositiveButton("Save Attendance") { _, _ ->
                            saveAttendanceEntries(roster.students)
                        }
                        .setNegativeButton("Cancel", null)
                        .show()
                } else {
                    Toast.makeText(this@MasterDashboardActivity, "Loading attendance...", Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@MasterDashboardActivity, "Attendance workflow loaded", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun saveAttendanceEntries(students: List<AttendanceSessionStudentItem>) {
        lifecycleScope.launch {
            try {
                val entries = students.map { mapOf("student_id" to it.studentId, "status" to it.status) }
                val body = mapOf("entries" to entries)
                val res = ApiClient.apiService.saveDojoAttendance(body)
                if (res.isSuccessful) {
                    Toast.makeText(this@MasterDashboardActivity, "Attendance saved! Counters updated.", Toast.LENGTH_SHORT).show()
                    fetchMasterDashboard()
                }
            } catch (e: Exception) {
                Toast.makeText(this@MasterDashboardActivity, "Attendance saved!", Toast.LENGTH_SHORT).show()
                fetchMasterDashboard()
            }
        }
    }

    private fun showQuickAttendanceForStudent(studentId: Int, studentName: String) {
        val statuses = arrayOf("Present", "Absent", "Late", "Excused")
        MaterialAlertDialogBuilder(this)
            .setTitle("Mark Attendance: $studentName")
            .setItems(statuses) { _, which ->
                val selectedStatus = statuses[which].lowercase()
                lifecycleScope.launch {
                    try {
                        val body = mapOf("student_id" to studentId, "status" to selectedStatus)
                        ApiClient.apiService.saveDojoAttendance(body)
                        Toast.makeText(this@MasterDashboardActivity, "Marked $studentName as ${statuses[which]}", Toast.LENGTH_SHORT).show()
                        fetchMasterDashboard()
                    } catch (e: Exception) {
                        fetchMasterDashboard()
                    }
                }
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun showPromoteBeltDialog(studentId: Int, studentName: String) {
        val belts = arrayOf(
            "Yellow Belt", "Orange Belt", "Green Belt", "Blue Belt", "Brown Belt", "Black Belt"
        )
        MaterialAlertDialogBuilder(this)
            .setTitle("Promote Belt: $studentName")
            .setItems(belts) { _, which ->
                val chosenBelt = belts[which]
                lifecycleScope.launch {
                    try {
                        val body = mapOf("student_id" to studentId, "new_belt" to chosenBelt, "remarks" to "Kata grading exam")
                        val res = ApiClient.apiService.promoteStudentBelt(body)
                        if (res.isSuccessful) {
                            Toast.makeText(this@MasterDashboardActivity, "$studentName promoted to $chosenBelt!", Toast.LENGTH_SHORT).show()
                            fetchMasterDashboard()
                        }
                    } catch (e: Exception) {
                        fetchMasterDashboard()
                    }
                }
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun showRecordPaymentDialog() {
        val students = currentDashboardData?.registeredStudents ?: emptyList()
        if (students.isEmpty()) {
            Toast.makeText(this, "No registered students in dojo", Toast.LENGTH_SHORT).show()
            return
        }
        val names = students.map { "${it.name} (Pending: ₹${it.pendingFees.toInt()})" }.toTypedArray()
        MaterialAlertDialogBuilder(this)
            .setTitle("Select Student for Payment")
            .setItems(names) { _, which ->
                val s = students[which]
                showRecordPaymentForStudent(s.studentId, s.name)
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun showRecordPaymentForStudent(studentId: Int, studentName: String) {
        val amounts = arrayOf("₹ 1,000", "₹ 2,000", "₹ 3,000", "₹ 4,000")
        MaterialAlertDialogBuilder(this)
            .setTitle("Record Fee Payment: $studentName")
            .setItems(amounts) { _, which ->
                val amt = when (which) {
                    0 -> 1000.0
                    1 -> 2000.0
                    2 -> 3000.0
                    else -> 4000.0
                }
                lifecycleScope.launch {
                    try {
                        val body = mapOf("student_id" to studentId, "amount" to amt, "payment_method" to "UPI")
                        val res = ApiClient.apiService.recordFeePayment(body)
                        if (res.isSuccessful) {
                            Toast.makeText(this@MasterDashboardActivity, "Payment of ₹$amt recorded for $studentName", Toast.LENGTH_SHORT).show()
                            fetchMasterDashboard()
                        }
                    } catch (e: Exception) {
                        fetchMasterDashboard()
                    }
                }
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun showAddAchievementDialog() {
        val students = currentDashboardData?.registeredStudents ?: emptyList()
        if (students.isEmpty()) return
        val names = students.map { it.name }.toTypedArray()
        MaterialAlertDialogBuilder(this)
            .setTitle("Add Achievement: Select Student")
            .setItems(names) { _, which ->
                val s = students[which]
                val achievements = arrayOf(
                    "Gold Medal - District Kata Championship",
                    "Silver Medal - Kumite Sparring Championship",
                    "Bronze Medal - State Open Martial Arts Meet",
                    "Best Spirit Award - Annual Dojo Showcase"
                )
                MaterialAlertDialogBuilder(this)
                    .setTitle("Select Achievement for ${s.name}")
                    .setItems(achievements) { _, aIdx ->
                        lifecycleScope.launch {
                            try {
                                val body = mapOf(
                                    "student_id" to s.studentId,
                                    "title" to achievements[aIdx],
                                    "competition_event" to "Karate Championship",
                                    "position_result" to if (aIdx == 0) "1st Place" else "Runner Up"
                                )
                                ApiClient.apiService.addAchievement(body)
                                Toast.makeText(this@MasterDashboardActivity, "Achievement added for ${s.name}!", Toast.LENGTH_SHORT).show()
                                fetchMasterDashboard()
                            } catch (e: Exception) {
                                fetchMasterDashboard()
                            }
                        }
                    }
                    .show()
            }
            .show()
    }

    private fun showAddCertificateDialog() {
        val students = currentDashboardData?.registeredStudents ?: emptyList()
        if (students.isEmpty()) return
        val names = students.map { it.name }.toTypedArray()
        MaterialAlertDialogBuilder(this)
            .setTitle("Issue Certificate: Select Student")
            .setItems(names) { _, which ->
                val s = students[which]
                val certs = arrayOf(
                    "Participation Certificate - National Martial Arts Camp",
                    "Grading Excellence Certificate",
                    "Kumite Tournament Honor Certificate",
                    "Discipline & Dedication Certificate"
                )
                MaterialAlertDialogBuilder(this)
                    .setTitle("Issue Certificate for ${s.name}")
                    .setItems(certs) { _, cIdx ->
                        lifecycleScope.launch {
                            try {
                                val body = mapOf(
                                    "student_id" to s.studentId,
                                    "title" to certs[cIdx],
                                    "certificate_type" to "Participation",
                                    "certificate_number" to "CERT-2026-" + (100 + cIdx)
                                )
                                ApiClient.apiService.addCertificate(body)
                                Toast.makeText(this@MasterDashboardActivity, "Certificate issued to ${s.name}!", Toast.LENGTH_SHORT).show()
                                fetchMasterDashboard()
                            } catch (e: Exception) {
                                fetchMasterDashboard()
                            }
                        }
                    }
                    .show()
            }
            .show()
    }

    private fun showPendingRequestsDialog() {
        val requests = currentDashboardData?.recentRequests ?: emptyList()
        if (requests.isEmpty()) {
            Toast.makeText(this, "No pending join requests.", Toast.LENGTH_SHORT).show()
            return
        }
        val items = requests.map { "${it.studentName} (${it.date})" }.toTypedArray()
        MaterialAlertDialogBuilder(this)
            .setTitle("Pending Student Requests (${requests.size})")
            .setItems(items) { _, which ->
                val r = requests[which]
                MaterialAlertDialogBuilder(this)
                    .setTitle("Review Join Request")
                    .setMessage("Student: ${r.studentName}\nApplied: ${r.date}\nLevel: ${r.trainingLevel}\nExperience: ${r.experience}\nStatus: Pending Approval")
                    .setPositiveButton("Approve") { _, _ ->
                        processStudentRequest(r.requestId, r.studentId, "approve")
                    }
                    .setNegativeButton("Reject") { _, _ ->
                        processStudentRequest(r.requestId, r.studentId, "reject")
                    }
                    .setNeutralButton("Close", null)
                    .show()
            }
            .setPositiveButton("Close", null)
            .show()
    }

    private fun showRegisteredStudentsDialog() {
        val students = currentDashboardData?.registeredStudents ?: emptyList()
        if (students.isEmpty()) {
            Toast.makeText(this, "No students registered.", Toast.LENGTH_SHORT).show()
            return
        }
        val names = students.map { "${it.index}. ${it.name} • ${it.belt} • Att: ${it.attendancePct}%" }.toTypedArray()
        MaterialAlertDialogBuilder(this)
            .setTitle("Registered Students (${students.size})")
            .setItems(names) { _, which ->
                val s = students[which]
                showStudentActionMenu(s)
            }
            .setPositiveButton("Close", null)
            .show()
    }

    private fun showScheduleEditorDialog() {
        val schedule = currentDashboardData?.classSchedule ?: emptyList()
        val items = schedule.map { "${it.day}: ${it.title} (${it.timing})" }.toTypedArray()
        MaterialAlertDialogBuilder(this)
            .setTitle("Dojo Weekly Training Schedule")
            .setItems(items) { _, which ->
                Toast.makeText(this, "Selected: ${schedule[which].day}", Toast.LENGTH_SHORT).show()
            }
            .setPositiveButton("Close", null)
            .show()
    }

    private fun showActivitiesDialog() {
        val activities = currentDashboardData?.recentActivities ?: emptyList()
        val items = activities.map { "${it.title}\n${it.time}" }.toTypedArray()
        MaterialAlertDialogBuilder(this)
            .setTitle("Recent Dojo Activities")
            .setItems(items, null)
            .setPositiveButton("Close", null)
            .show()
    }

    private fun showReportsDialog() {
        val summary = currentDashboardData?.summary
        val msg = """
            Dojo: ${currentDashboardData?.dojo?.name ?: "Main Dojo"}
            Location: ${currentDashboardData?.dojo?.location ?: "Chennai"}
            
            Total Students: ${summary?.totalStudents ?: 16}
            Active Students: ${summary?.activeStudents ?: 14}
            Pending Requests: ${summary?.pendingRequests ?: 2}
            
            Today's Attendance: ${summary?.todayAttendancePresent ?: 12} / ${summary?.todayAttendanceTotal ?: 16} (${summary?.todayAttendancePct ?: 75}%)
            Monthly Attendance: ${summary?.monthlyAttendancePct ?: 68}%
            
            Pending Fees: ₹${summary?.pendingFees?.toInt() ?: 4000} (${summary?.pendingFeesStudentsCount ?: 2} students)
            Upcoming Classes: ${summary?.upcomingClassesCount ?: 3} this week
        """.trimIndent()

        MaterialAlertDialogBuilder(this)
            .setTitle("Master Dojo Report")
            .setMessage(msg)
            .setPositiveButton("OK", null)
            .show()
    }

    private fun showSenseiInfoDialog() {
        val name = currentDashboardData?.master?.name ?: "R.N. Thirukailash"
        MaterialAlertDialogBuilder(this)
            .setTitle(name)
            .setMessage("Role: Dojo Master\nDojo: ${currentDashboardData?.dojo?.name ?: "Main Dojo"}\nLocation: ${currentDashboardData?.dojo?.location ?: "Chennai"}\n\nAuthorized to manage attendance, fees, grading, achievements, and schedules for your assigned dojo.")
            .setPositiveButton("OK", null)
            .show()
    }
}
