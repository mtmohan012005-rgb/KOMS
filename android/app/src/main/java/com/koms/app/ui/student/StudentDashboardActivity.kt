package com.koms.app.ui.student

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.GravityCompat
import com.koms.app.databinding.ActivityStudentDashboardBinding
import com.koms.app.ui.auth.LoginActivity
import com.koms.app.utils.SessionManager

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
            Toast.makeText(this, "Profile: ${binding.tvStudentName.text} (${binding.tvStudentCode.text})", Toast.LENGTH_SHORT).show()
        }

        binding.btnTopProfile.setOnClickListener {
            Toast.makeText(this, "Logged in as ${binding.tvStudentName.text}", Toast.LENGTH_SHORT).show()
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
            Toast.makeText(this, "Student Profile: ${binding.tvStudentName.text}", Toast.LENGTH_SHORT).show()
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
