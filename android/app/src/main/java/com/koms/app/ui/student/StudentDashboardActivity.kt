package com.koms.app.ui.student

import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import com.koms.app.databinding.ActivityStudentDashboardBinding
import com.koms.app.ui.auth.LoginActivity
import com.koms.app.utils.SessionManager
import java.util.Locale

class StudentDashboardActivity : AppCompatActivity() {

    private lateinit var binding: ActivityStudentDashboardBinding
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        binding = ActivityStudentDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)
        
        sessionManager = SessionManager(this)

        val userRole = sessionManager.getUserRole() ?: "student"
        binding.tvWelcomeMessage.text = "Welcome back!"
        binding.tvRoleBadge.text = userRole.uppercase(Locale.ROOT)

        // Navigation Actions for Cards
        binding.cardFindDojo.setOnClickListener {
            startActivity(Intent(this, DojosActivity::class.java))
        }

        binding.cardAttendance.setOnClickListener {
            startActivity(Intent(this, AttendanceActivity::class.java))
        }

        binding.cardFees.setOnClickListener {
            startActivity(Intent(this, FeesActivity::class.java))
        }

        binding.cardGrading.setOnClickListener {
            startActivity(Intent(this, GradingActivity::class.java))
        }

        // Logout Action
        binding.btnLogout.setOnClickListener {
            sessionManager.logout()
            val intent = Intent(this, LoginActivity::class.java)
            intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
            startActivity(intent)
            finish()
        }
    }
}
