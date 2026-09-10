package com.koms.app.ui.master

import android.content.Intent
import android.os.Bundle
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.GravityCompat
import com.koms.app.databinding.ActivityMasterDashboardBinding
import com.koms.app.ui.auth.LoginActivity
import com.koms.app.ui.student.AttendanceActivity
import com.koms.app.ui.student.DojosActivity
import com.koms.app.ui.student.FeesActivity
import com.koms.app.ui.student.GradingActivity
import com.koms.app.utils.SessionManager

class MasterDashboardActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMasterDashboardBinding
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        binding = ActivityMasterDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)

        setupMasterProfile()
        setupDrawer()
        setupCardClicks()
        setupDrawerClicks()
    }

    private fun setupMasterProfile() {
        val rawName = sessionManager.getUserName()
        val masterName = if (!rawName.isNullOrBlank() && !rawName.equals("Jane Doe", ignoreCase = true) && !rawName.equals("Student Demo", ignoreCase = true)) {
            rawName
        } else {
            "John Sensei"
        }
        binding.tvMasterName.text = masterName
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

    private fun setupCardClicks() {
        // Hero Buttons
        binding.btnHeroManageStudents.setOnClickListener {
            Toast.makeText(this, "Active Students: 3 enrolled in Mass Dragon Dojo", Toast.LENGTH_SHORT).show()
        }

        binding.btnHeroJoinRequests.setOnClickListener {
            Toast.makeText(this, "Join Requests: 0 pending requests", Toast.LENGTH_SHORT).show()
        }

        binding.btnMasterProfile.setOnClickListener {
            Toast.makeText(this, "Logged in as Sensei Master (${binding.tvMasterName.text})", Toast.LENGTH_SHORT).show()
        }

        // 4 Stat Cards
        binding.cardMasterStudents.setOnClickListener {
            Toast.makeText(this, "Students: 3 approved dojo members", Toast.LENGTH_SHORT).show()
        }

        binding.cardMasterRequests.setOnClickListener {
            Toast.makeText(this, "Requests: All student join requests reviewed", Toast.LENGTH_SHORT).show()
        }

        binding.cardMasterAttendance.setOnClickListener {
            startActivity(Intent(this, AttendanceActivity::class.java))
        }

        binding.cardMasterFees.setOnClickListener {
            startActivity(Intent(this, FeesActivity::class.java))
        }

        // Dojo Operations Grid
        binding.actionMasterStudents.setOnClickListener {
            Toast.makeText(this, "Managing 3 Active Students (Kenji, Ryu, Jane)", Toast.LENGTH_SHORT).show()
        }

        binding.actionMasterRequests.setOnClickListener {
            Toast.makeText(this, "0 Pending Join Requests", Toast.LENGTH_SHORT).show()
        }

        binding.actionMasterAttendance.setOnClickListener {
            startActivity(Intent(this, AttendanceActivity::class.java))
        }

        binding.actionMasterFees.setOnClickListener {
            startActivity(Intent(this, FeesActivity::class.java))
        }

        binding.actionMasterGrading.setOnClickListener {
            startActivity(Intent(this, GradingActivity::class.java))
        }

        binding.actionMasterDojoProfile.setOnClickListener {
            startActivity(Intent(this, DojosActivity::class.java))
        }
    }

    private fun setupDrawerClicks() {
        binding.navMasterDashboard.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
        }

        binding.navMasterStudents.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            Toast.makeText(this, "Students Roster: 3 Active Dojo Members", Toast.LENGTH_SHORT).show()
        }

        binding.navMasterRequests.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            Toast.makeText(this, "Join Requests: 0 Pending", Toast.LENGTH_SHORT).show()
        }

        binding.navMasterAttendance.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            startActivity(Intent(this, AttendanceActivity::class.java))
        }

        binding.navMasterFees.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            startActivity(Intent(this, FeesActivity::class.java))
        }

        binding.navMasterGrading.setOnClickListener {
            binding.drawerLayoutMaster.closeDrawer(GravityCompat.START)
            startActivity(Intent(this, GradingActivity::class.java))
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
}
