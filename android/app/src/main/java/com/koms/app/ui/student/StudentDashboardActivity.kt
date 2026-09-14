package com.koms.app.ui.student

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.GravityCompat
import androidx.lifecycle.lifecycleScope
import com.koms.app.data.api.ApiClient
import com.koms.app.databinding.ActivityStudentDashboardBinding
import com.koms.app.ui.auth.LoginActivity
import com.koms.app.utils.SessionManager
import kotlinx.coroutines.launch

class StudentDashboardActivity : AppCompatActivity() {

    private lateinit var binding: ActivityStudentDashboardBinding
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        binding = ActivityStudentDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)

        setupStudentProfile()
        setupDrawer()
        setupCardClicks()
        setupDrawerClicks()
        syncLiveDataFromCloud()
    }

    override fun onResume() {
        super.onResume()
        syncLiveDataFromCloud()
    }

    private fun syncLiveDataFromCloud() {
        val studentId = sessionManager.getUserId()
        if (studentId <= 0) return

        lifecycleScope.launch {
            // 1. Fetch Profile over Internet
            try {
                val profileRes = ApiClient.apiService.getStudentProfile(studentId)
                if (profileRes.isSuccessful && profileRes.body()?.data != null) {
                    val profile = profileRes.body()!!.data!!
                    val name = profile.fullName ?: profile.firstName ?: ""
                    if (name.isNotBlank()) {
                        binding.tvStudentName.text = name
                        binding.tvHeroWelcome.text = "Welcome back, $name"
                        val initials = name.trim().split("\\s+".toRegex())
                            .filter { it.isNotEmpty() }
                            .take(2)
                            .map { it[0].uppercaseChar() }
                            .joinToString("")
                        binding.tvAvatarInitials.text = if (initials.isNotEmpty()) initials else "JD"
                    }
                    if (!profile.memberId.isNullOrBlank()) {
                        binding.tvStudentCode.text = profile.memberId
                    }
                }
            } catch (_: Exception) {}

            // 2. Fetch Attendance over Internet
            try {
                val attRes = ApiClient.apiService.getAttendanceHistory(studentId)
                if (attRes.isSuccessful && !attRes.body()?.data.isNullOrEmpty()) {
                    val list = attRes.body()!!.data!!
                    val total = list.size
                    val present = list.count { it.status.equals("present", ignoreCase = true) }
                    val pct = if (total > 0) ((present * 100) / total) else 0
                    binding.tvStatAttendance.text = "$pct%"
                    binding.tvStatAttendanceSub.text = "$present of $total present"
                }
            } catch (_: Exception) {}

            // 3. Fetch Fees over Internet
            try {
                val feeRes = ApiClient.apiService.getFeeRecords(studentId)
                if (feeRes.isSuccessful && !feeRes.body()?.data.isNullOrEmpty()) {
                    val list = feeRes.body()!!.data!!
                    val pending = list.filter { it.status.equals("pending", ignoreCase = true) }
                    val totalPending = pending.sumOf { it.amountDue }
                    if (totalPending <= 0.0) {
                        binding.tvStatFees.text = "₹0"
                        binding.tvStatFeesSub.text = "All fees cleared"
                    } else {
                        binding.tvStatFees.text = "₹%.0f".format(totalPending)
                        binding.tvStatFeesSub.text = "${pending.size} payment pending"
                    }
                }
            } catch (_: Exception) {}
        }
    }

    private fun setupStudentProfile() {
        // Read user data from session or default to screenshot's "Jane Doe"
        val rawName = sessionManager.getUserName()
        val studentName = if (!rawName.isNullOrBlank() && !rawName.equals("Student Demo", ignoreCase = true)) {
            rawName
        } else {
            "Jane Doe"
        }

        binding.tvStudentName.text = studentName
        binding.tvHeroWelcome.text = "Welcome back, $studentName"

        // Compute initials (e.g., "Jane Doe" -> "JD")
        val initials = studentName.trim().split("\\s+".toRegex())
            .filter { it.isNotEmpty() }
            .take(2)
            .map { it[0].uppercaseChar() }
            .joinToString("")
        binding.tvAvatarInitials.text = if (initials.isNotEmpty()) initials else "JD"

        // Student ID (e.g., MD-00004)
        val studentId = sessionManager.getUserId()
        binding.tvStudentCode.text = if (studentId > 0) "MD-%05d".format(studentId) else "MD-00004"
    }

    private fun setupDrawer() {
        // Hamburger click opens drawer
        binding.btnOpenDrawer.setOnClickListener {
            binding.drawerLayout.openDrawer(GravityCompat.START)
        }

        // Handle back press to close drawer if open
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (binding.drawerLayout.isDrawerOpen(GravityCompat.START)) {
                    binding.drawerLayout.closeDrawer(GravityCompat.START)
                } else {
                    isEnabled = false
                    onBackPressedDispatcher.onBackPressed()
                }
            }
        })
    }

    private fun setupCardClicks() {
        // Hero Buttons
        binding.btnHeroAttendance.setOnClickListener {
            startActivity(Intent(this, AttendanceActivity::class.java))
        }

        binding.btnHeroProfile.setOnClickListener {
            startActivity(Intent(this, StudentProfileActivity::class.java))
        }

        binding.btnTopProfile.setOnClickListener {
            startActivity(Intent(this, StudentProfileActivity::class.java))
        }

        // 4 Stat Cards
        binding.cardStatAttendance.setOnClickListener {
            startActivity(Intent(this, AttendanceActivity::class.java))
        }

        binding.cardStatFees.setOnClickListener {
            startActivity(Intent(this, FeesActivity::class.java))
        }

        binding.cardStatTournaments.setOnClickListener {
            Toast.makeText(this, "Tournament entries: 1 active registration", Toast.LENGTH_SHORT).show()
        }

        binding.cardStatBelt.setOnClickListener {
            startActivity(Intent(this, GradingActivity::class.java))
        }

        // My Training Base Card
        binding.cardTrainingBase.setOnClickListener {
            startActivity(Intent(this, DojosActivity::class.java))
        }
        binding.btnViewDojoLink.setOnClickListener {
            startActivity(Intent(this, DojosActivity::class.java))
        }

        // Quick Actions
        binding.actionAttendance.setOnClickListener {
            startActivity(Intent(this, AttendanceActivity::class.java))
        }

        binding.actionFees.setOnClickListener {
            startActivity(Intent(this, FeesActivity::class.java))
        }

        binding.actionGrading.setOnClickListener {
            startActivity(Intent(this, GradingActivity::class.java))
        }

        binding.actionTournaments.setOnClickListener {
            Toast.makeText(this, "National Martial Arts Championship 2026", Toast.LENGTH_SHORT).show()
        }

        binding.actionFindDojo.setOnClickListener {
            startActivity(Intent(this, DojosActivity::class.java))
        }

        binding.actionAnnouncements.setOnClickListener {
            Toast.makeText(this, "Dojo Notice: Special Kata Workshop this Saturday at 10 AM", Toast.LENGTH_LONG).show()
        }
    }

    private fun setupDrawerClicks() {
        // Drawer Menu Items
        binding.navDashboard.setOnClickListener {
            binding.drawerLayout.closeDrawer(GravityCompat.START)
        }

        binding.navProfile.setOnClickListener {
            binding.drawerLayout.closeDrawer(GravityCompat.START)
            startActivity(Intent(this, StudentProfileActivity::class.java))
        }

        binding.navAttendance.setOnClickListener {
            binding.drawerLayout.closeDrawer(GravityCompat.START)
            startActivity(Intent(this, AttendanceActivity::class.java))
        }

        binding.navBeltProgress.setOnClickListener {
            binding.drawerLayout.closeDrawer(GravityCompat.START)
            startActivity(Intent(this, GradingActivity::class.java))
        }

        binding.navAchievements.setOnClickListener {
            binding.drawerLayout.closeDrawer(GravityCompat.START)
            Toast.makeText(this, "Student Achievements: 1 Active Tournament, Purple Belt (3rd Kyu)", Toast.LENGTH_LONG).show()
        }

        binding.navFees.setOnClickListener {
            binding.drawerLayout.closeDrawer(GravityCompat.START)
            startActivity(Intent(this, FeesActivity::class.java))
        }

        binding.navAnnouncements.setOnClickListener {
            binding.drawerLayout.closeDrawer(GravityCompat.START)
            Toast.makeText(this, "Notice: All dojo belts grading session scheduled for next month", Toast.LENGTH_LONG).show()
        }

        binding.navTournaments.setOnClickListener {
            binding.drawerLayout.closeDrawer(GravityCompat.START)
            Toast.makeText(this, "Tournaments: 1 Registered Tournament Entry", Toast.LENGTH_SHORT).show()
        }

        binding.navMyDojo.setOnClickListener {
            binding.drawerLayout.closeDrawer(GravityCompat.START)
            startActivity(Intent(this, DojosActivity::class.java))
        }

        // Drawer Logout
        binding.navLogout.setOnClickListener {
            binding.drawerLayout.closeDrawer(GravityCompat.START)
            sessionManager.logout()
            val intent = Intent(this, LoginActivity::class.java)
            intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
            startActivity(intent)
            finish()
        }
    }
}
