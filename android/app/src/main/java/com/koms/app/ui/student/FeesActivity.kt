package com.koms.app.ui.student

import android.os.Bundle
import android.view.View
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.koms.app.data.api.ApiClient
import com.koms.app.data.model.FeeRecord
import com.koms.app.databinding.ActivityFeesBinding
import com.koms.app.utils.SessionManager
import kotlinx.coroutines.launch

class FeesActivity : AppCompatActivity() {

    private lateinit var binding: ActivityFeesBinding
    private lateinit var adapter: FeeAdapter
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        binding = ActivityFeesBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)
        binding.toolbar.setNavigationOnClickListener { finish() }

        adapter = FeeAdapter(emptyList())
        binding.rvFees.layoutManager = LinearLayoutManager(this)
        binding.rvFees.adapter = adapter

        fetchFees()
    }

    private fun fetchFees() {
        binding.progressBar.visibility = View.VISIBLE
        val studentId = sessionManager.getUserId()
        lifecycleScope.launch {
            try {
                val response = ApiClient.apiService.getFeeRecords(studentId)
                if (response.isSuccessful && !response.body()?.data.isNullOrEmpty()) {
                    adapter.updateList(response.body()!!.data!!)
                } else {
                    adapter.updateList(getMockFees())
                }
            } catch (_: Exception) {
                adapter.updateList(getMockFees())
            } finally {
                binding.progressBar.visibility = View.GONE
            }
        }
    }

    private fun getMockFees(): List<FeeRecord> {
        return listOf(
            FeeRecord(101, "May 2025", 50.00, "2025-05-05", "Paid", "Monthly Training Fee"),
            FeeRecord(102, "June 2025", 50.00, "2025-06-05", "Pending", "Monthly Training Fee"),
            FeeRecord(103, "Q2 2025", 35.00, "2025-06-15", "Upcoming", "Grading Examination Fee")
        )
    }
}
