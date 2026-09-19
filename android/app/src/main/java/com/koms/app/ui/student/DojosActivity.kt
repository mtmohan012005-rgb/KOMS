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

        adapter = DojoAdapter(emptyList()) { selectedDojo ->
            showJoinDojoDialog(selectedDojo)
        }
        binding.rvDojos.layoutManager = LinearLayoutManager(this)
        binding.rvDojos.adapter = adapter

        fetchDojos()
    }

    private fun showJoinDojoDialog(dojo: Dojo) {
        val master = dojo.masterName ?: "Sensei Master"
        val schedule = "${dojo.trainingDays ?: "Mon-Sat"} • ${dojo.trainingTimings ?: "6:00 PM - 8:00 PM"}"
        com.google.android.material.dialog.MaterialAlertDialogBuilder(this)
            .setTitle("🥋 ${dojo.name}")
            .setMessage("Location: ${dojo.location}\nSensei: $master\nSchedule: $schedule\n\nWould you like to submit a student membership request to this dojo? The Sensei will review and approve your registration.")
            .setPositiveButton("Submit Application") { _, _ ->
                submitJoinRequest(dojo)
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun submitJoinRequest(dojo: Dojo) {
        binding.progressBar.visibility = View.VISIBLE
        lifecycleScope.launch {
            try {
                val res = ApiClient.apiService.joinDojo(mapOf("dojo_id" to dojo.id))
                if (res.isSuccessful && (res.body()?.success == true || res.body()?.data != null)) {
                    android.widget.Toast.makeText(this@DojosActivity, "Join request submitted! Sensei ${dojo.masterName ?: "Master"} will review your request.", android.widget.Toast.LENGTH_LONG).show()
                } else {
                    val errMsg = res.body()?.message ?: "You already have an active or pending membership."
                    android.widget.Toast.makeText(this@DojosActivity, errMsg, android.widget.Toast.LENGTH_LONG).show()
                }
            } catch (e: Exception) {
                android.widget.Toast.makeText(this@DojosActivity, "Error: ${e.message}", android.widget.Toast.LENGTH_SHORT).show()
            } finally {
                binding.progressBar.visibility = View.GONE
            }
        }
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
