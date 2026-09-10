package com.koms.app.ui.student

import android.os.Bundle
import android.view.View
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import androidx.recyclerview.widget.LinearLayoutManager
import com.koms.app.data.api.ApiClient
import com.koms.app.data.model.Dojo
import com.koms.app.databinding.ActivityDojosBinding
import kotlinx.coroutines.launch

class DojosActivity : AppCompatActivity() {

    private lateinit var binding: ActivityDojosBinding
    private lateinit var adapter: DojoAdapter

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        binding = ActivityDojosBinding.inflate(layoutInflater)
        setContentView(binding.root)

        binding.toolbar.setNavigationOnClickListener { finish() }

        adapter = DojoAdapter(emptyList())
        binding.rvDojos.layoutManager = LinearLayoutManager(this)
        binding.rvDojos.adapter = adapter

        fetchDojos()
    }

    private fun fetchDojos() {
        binding.progressBar.visibility = View.VISIBLE
        lifecycleScope.launch {
            try {
                val response = ApiClient.apiService.getDojos()
                if (response.isSuccessful && response.body()?.data != null) {
                    adapter.updateDojos(response.body()!!.data!!)
                } else {
                    adapter.updateDojos(getMockDojos())
                }
            } catch (_: Exception) {
                // Fallback demo data when local server is unreachable
                adapter.updateDojos(getMockDojos())
            } finally {
                binding.progressBar.visibility = View.GONE
            }
        }
    }

    private fun getMockDojos(): List<Dojo> {
        return listOf(
            Dojo(1, "Central Karate Dojo", "123 Main Street, Downtown", "Sensei Master Tanaka", "Mon, Wed, Fri", "6:00 PM - 8:00 PM"),
            Dojo(2, "Dragon Martial Arts Academy", "456 Oak Avenue, Northside", "Sensei Master Kenji", "Tue, Thu, Sat", "5:00 PM - 7:00 PM"),
            Dojo(3, "Warrior Spirit Dojo", "789 Pine Road, West End", "Sensei Master Sato", "Mon - Sat", "6:30 PM - 8:30 PM")
        )
    }
}
