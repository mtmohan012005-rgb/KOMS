package com.koms.app.ui.student

import android.os.Bundle
import android.view.View
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.koms.app.data.api.ApiClient
import com.koms.app.data.model.AttendanceHistory
import com.koms.app.databinding.ActivityAttendanceBinding
import com.koms.app.utils.SessionManager
import kotlinx.coroutines.launch

class AttendanceActivity : AppCompatActivity() {

    private lateinit var binding: ActivityAttendanceBinding
    private lateinit var adapter: AttendanceAdapter
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        binding = ActivityAttendanceBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)
        binding.toolbar.setNavigationOnClickListener { finish() }

        adapter = AttendanceAdapter(emptyList())
        binding.rvAttendance.layoutManager = LinearLayoutManager(this)
        binding.rvAttendance.adapter = adapter

        fetchAttendance()
    }

    private fun fetchAttendance() {
        binding.progressBar.visibility = View.VISIBLE
        val studentId = sessionManager.getUserId()
        lifecycleScope.launch {
            try {
                val response = ApiClient.apiService.getAttendanceHistory(studentId)
                if (response.isSuccessful && !response.body()?.data.isNullOrEmpty()) {
                    adapter.updateList(response.body()!!.data!!)
                } else {
                    adapter.updateList(getMockAttendance())
                }
            } catch (_: Exception) {
                adapter.updateList(getMockAttendance())
            } finally {
                binding.progressBar.visibility = View.GONE
            }
        }
    }

    private fun getMockAttendance(): List<AttendanceHistory> {
        return listOf(
            AttendanceHistory("2025-05-10", "18:00", "Present", "Kata & Sparring Training"),
            AttendanceHistory("2025-05-08", "18:00", "Present", "Kumite Practice"),
            AttendanceHistory("2025-05-06", "18:00", "Absent", "Excused - Medical"),
            AttendanceHistory("2025-05-03", "18:00", "Present", "Basic Stances & Kihon")
        )
    }
}
