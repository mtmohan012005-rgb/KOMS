package com.koms.app.ui.senior

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.koms.app.databinding.ActivitySeniorDashboardBinding
import com.koms.app.ui.auth.LoginActivity
import com.koms.app.ui.student.AttendanceActivity
import com.koms.app.utils.SessionManager

class SeniorDashboardActivity : AppCompatActivity() {

    private lateinit var binding: ActivitySeniorDashboardBinding
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivitySeniorDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)

        val name = sessionManager.getUserName() ?: "Senior Instructor"
        binding.tvSeniorWelcomeName.text = "Welcome, $name"

        setupLogout()
        setupActions()
    }

    private fun setupLogout() {
        binding.btnSeniorLogout.setOnClickListener {
            sessionManager.logout()
            startActivity(Intent(this, LoginActivity::class.java))
            finish()
        }
    }

    private fun setupActions() {
        binding.cardSeniorMarkAttendance.setOnClickListener {
            startActivity(Intent(this, AttendanceActivity::class.java))
        }

        binding.cardSeniorViewStudents.setOnClickListener {
            val sampleStudents = arrayOf(
                "Arun Kumar (3rd Kyu Green Belt)",
                "Priya Sharma (4th Kyu Orange Belt)",
                "S. Karthik (5th Kyu Yellow Belt)",
                "R. Vikram (6th Kyu White Belt)",
                "Deepak Raj (2nd Kyu Blue Belt)"
            )
            MaterialAlertDialogBuilder(this)
                .setTitle("Assigned Dojo Students (Ranga Nagar)")
                .setItems(sampleStudents) { _, which ->
                    Toast.makeText(this, "Student: ${sampleStudents[which]}", Toast.LENGTH_SHORT).show()
                }
                .setPositiveButton("Close", null)
                .show()
        }
    }
}
