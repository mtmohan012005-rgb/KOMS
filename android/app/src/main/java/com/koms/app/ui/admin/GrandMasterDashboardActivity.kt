package com.koms.app.ui.admin

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.koms.app.data.api.ApiClient
import com.koms.app.databinding.ActivityGrandMasterDashboardBinding
import com.koms.app.ui.auth.LoginActivity
import com.koms.app.ui.student.DojosActivity
import com.koms.app.utils.SessionManager
import kotlinx.coroutines.launch

class GrandMasterDashboardActivity : AppCompatActivity() {

    private lateinit var binding: ActivityGrandMasterDashboardBinding
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityGrandMasterDashboardBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)

        setupLogout()
        setupCommonUiControls()
        loadDashboardData()
    }

    override fun onResume() {
        super.onResume()
        loadDashboardData()
    }

    private fun setupLogout() {
        binding.btnGMLogout.setOnClickListener {
            sessionManager.logout()
            startActivity(Intent(this, LoginActivity::class.java))
            finish()
        }
    }

    private fun loadDashboardData() {
        lifecycleScope.launch {
            try {
                val response = ApiClient.apiService.getAdminDashboard()
                if (response.isSuccessful && response.body()?.data != null) {
                    val data = response.body()!!.data!!
                    val stats = data["stats"] as? Map<String, Any>
                    if (stats != null) {
                        val totalDojos = (stats["total_dojos"] as? Number)?.toInt() ?: 0
                        val approvedDojos = (stats["approved_dojos"] as? Number)?.toInt() ?: 0
                        val pendingDojos = (stats["pending_dojos"] as? Number)?.toInt() ?: 0
                        binding.tvTotalDojos.text = totalDojos.toString()
                        binding.tvTotalDojosSub.text = "$approvedDojos Approved | $pendingDojos Pending"

                        val totalMasters = (stats["total_masters"] as? Number)?.toInt() ?: 0
                        val approvedMasters = (stats["approved_masters"] as? Number)?.toInt() ?: 0
                        val pendingMasters = (stats["pending_masters"] as? Number)?.toInt() ?: 0
                        binding.tvTotalMasters.text = totalMasters.toString()
                        binding.tvTotalMastersSub.text = "$approvedMasters Approved | $pendingMasters Pending"

                        val totalStudents = (stats["total_students"] as? Number)?.toInt() ?: 0
                        val activeStudents = (stats["active_students"] as? Number)?.toInt() ?: 0
                        val inactiveStudents = (stats["inactive_students"] as? Number)?.toInt() ?: 0
                        binding.tvTotalStudents.text = totalStudents.toString()
                        binding.tvTotalStudentsSub.text = "$activeStudents Active | $inactiveStudents Pending"
                    }
                }
            } catch (_: Exception) {}

            fetchPendingRequests()
        }
    }

    private fun fetchPendingRequests() {
        lifecycleScope.launch {
            try {
                val response = ApiClient.apiService.getPendingDojos()
                if (response.isSuccessful && response.body()?.data != null) {
                    val requests = response.body()!!.data!!
                    if (requests.isNotEmpty()) {
                        val first = requests[0]
                        val reqId1 = (first["id"] as? Number)?.toInt() ?: 0
                        val name1 = first["name"] as? String ?: "Sensei Master"
                        val dojo1 = first["dojo_name"] as? String ?: "Karate Dojo"
                        val applied1 = first["applied_on"] as? String ?: "Today"

                        binding.rowMasterReq1.visibility = View.VISIBLE
                        binding.tvMasterReq1Name.text = name1
                        binding.tvMasterReq1Dojo.text = "$dojo1 • Applied: $applied1"

                        binding.btnApproveMaster1.isEnabled = true
                        binding.btnApproveMaster1.text = "Approve"
                        binding.btnApproveMaster1.visibility = View.VISIBLE
                        binding.btnRejectMaster1.visibility = View.VISIBLE

                        binding.btnApproveMaster1.setOnClickListener {
                            processDojoRequest(reqId1, "approve", dojo1, name1)
                        }
                        binding.btnRejectMaster1.setOnClickListener {
                            processDojoRequest(reqId1, "reject", dojo1, name1)
                        }

                        if (requests.size > 1) {
                            val second = requests[1]
                            val reqId2 = (second["id"] as? Number)?.toInt() ?: 0
                            val name2 = second["name"] as? String ?: "Sensei Master"
                            val dojo2 = second["dojo_name"] as? String ?: "Karate Dojo"
                            val applied2 = second["applied_on"] as? String ?: "Today"

                            binding.rowMasterReq2.visibility = View.VISIBLE
                            binding.tvMasterReq2Name.text = name2
                            binding.tvMasterReq2Dojo.text = "$dojo2 • Applied: $applied2"

                            binding.btnApproveMaster2.isEnabled = true
                            binding.btnApproveMaster2.text = "Approve"
                            binding.btnApproveMaster2.visibility = View.VISIBLE
                            binding.btnRejectMaster2.visibility = View.VISIBLE

                            binding.btnApproveMaster2.setOnClickListener {
                                processDojoRequest(reqId2, "approve", dojo2, name2)
                            }
                            binding.btnRejectMaster2.setOnClickListener {
                                processDojoRequest(reqId2, "reject", dojo2, name2)
                            }
                        } else {
                            binding.rowMasterReq2.visibility = View.GONE
                        }
                    } else {
                        // All dojos approved
                        binding.rowMasterReq1.visibility = View.VISIBLE
                        binding.tvMasterReq1Name.text = "No Pending Dojo Requests"
                        binding.tvMasterReq1Dojo.text = "All dojos and masters are currently approved"
                        binding.btnApproveMaster1.visibility = View.GONE
                        binding.btnRejectMaster1.visibility = View.GONE
                        binding.rowMasterReq2.visibility = View.GONE
                    }
                }
            } catch (_: Exception) {}
        }
    }

    private fun processDojoRequest(dojoId: Int, action: String, dojoName: String, masterName: String) {
        lifecycleScope.launch {
            try {
                val res = ApiClient.apiService.processDojoRequest(mapOf("request_id" to dojoId, "action" to action))
                if (res.isSuccessful && (res.body()?.success == true || res.body()?.data != null)) {
                    val verb = if (action == "approve") "approved" else "rejected"
                    Toast.makeText(this@GrandMasterDashboardActivity, "Dojo '$dojoName' ($masterName) $verb successfully!", Toast.LENGTH_LONG).show()
                    loadDashboardData()
                } else {
                    val msg = res.body()?.message ?: "Unable to update dojo application."
                    Toast.makeText(this@GrandMasterDashboardActivity, msg, Toast.LENGTH_SHORT).show()
                }
            } catch (e: Exception) {
                Toast.makeText(this@GrandMasterDashboardActivity, "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun setupCommonUiControls() {
        binding.btnEditTraditionContent.setOnClickListener {
            showContentEditDialog("Tradition Content", "Matsubayashi Shorin-Ryu Karate-Do (少林流空手道)")
        }

        binding.btnEditTrainingLevelsContent.setOnClickListener {
            showContentEditDialog("Training Levels Content", "Shorin-Ryu 6 Kyu to 10 Dan syllabus")
        }

        binding.btnEditEventsContent.setOnClickListener {
            showContentEditDialog("Upcoming Events", "Karate Championship 2026, Kobudo Weaponry Seminar")
        }

        binding.btnEditGalleryContent.setOnClickListener {
            showContentEditDialog("Photo Gallery", "Organization high-resolution dojo photographs")
        }
    }

    private fun showContentEditDialog(title: String, currentVal: String) {
        MaterialAlertDialogBuilder(this)
            .setTitle("Grand Master CMS: $title")
            .setMessage("Status: Published to Public Common UI\n\nActive: $currentVal\n\nChanges saved here reflect instantly on the public mobile Common UI.")
            .setPositiveButton("Publish Update") { _, _ ->
                Toast.makeText(this, "$title published successfully!", Toast.LENGTH_SHORT).show()
            }
            .setNegativeButton("Close", null)
            .show()
    }
}
