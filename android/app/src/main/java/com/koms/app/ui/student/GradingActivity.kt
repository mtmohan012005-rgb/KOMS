package com.koms.app.ui.student

import android.os.Bundle
import android.view.View
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.koms.app.data.api.ApiClient
import com.koms.app.data.model.Tournament
import com.koms.app.databinding.ActivityGradingBinding
import kotlinx.coroutines.launch

class GradingActivity : AppCompatActivity() {

    private lateinit var binding: ActivityGradingBinding
    private lateinit var adapter: TournamentAdapter

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        binding = ActivityGradingBinding.inflate(layoutInflater)
        setContentView(binding.root)

        binding.toolbar.setNavigationOnClickListener { finish() }

        adapter = TournamentAdapter(emptyList())
        binding.rvTournaments.layoutManager = LinearLayoutManager(this)
        binding.rvTournaments.adapter = adapter

        fetchTournaments()
    }

    private fun fetchTournaments() {
        binding.progressBar.visibility = View.VISIBLE
        lifecycleScope.launch {
            try {
                val response = ApiClient.apiService.getTournaments()
                if (response.isSuccessful && !response.body()?.data.isNullOrEmpty()) {
                    adapter.updateList(response.body()!!.data!!)
                } else {
                    adapter.updateList(getMockTournaments())
                }
            } catch (_: Exception) {
                adapter.updateList(getMockTournaments())
            } finally {
                binding.progressBar.visibility = View.GONE
            }
        }
    }

    private fun getMockTournaments(): List<Tournament> {
        return listOf(
            Tournament(1, "National Karate Championship 2025", "2025-07-20", "National Indoor Sports Arena", "2025-06-30"),
            Tournament(2, "Summer Belt Grading Exam", "2025-06-15", "Central Dojo Main Hall", "2025-06-10"),
            Tournament(3, "Regional Kumite Cup", "2025-08-12", "City Sports Complex", "2025-07-31")
        )
    }
}
