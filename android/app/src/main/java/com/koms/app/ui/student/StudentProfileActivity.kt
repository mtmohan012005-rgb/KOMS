package com.koms.app.ui.student

import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.view.LayoutInflater
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.koms.app.data.api.ApiClient
import com.koms.app.databinding.ActivityStudentProfileBinding
import com.koms.app.databinding.DialogEditStudentProfileBinding
import com.koms.app.utils.SessionManager
import kotlinx.coroutines.launch

class StudentProfileActivity : AppCompatActivity() {

    private lateinit var binding: ActivityStudentProfileBinding
    private lateinit var sessionManager: SessionManager

    companion object {
        private const val PREFS_PROFILE = "KomsStudentProfilePrefs"
        private const val KEY_FATHER_NAME = "father_name"
        private const val KEY_MOTHER_NAME = "mother_name"
        private const val KEY_PHONE = "phone"
        private const val KEY_ALT_PHONE = "alt_phone"
        private const val KEY_BLOOD_GROUP = "blood_group"
        private const val KEY_ADDRESS = "address"
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityStudentProfileBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)

        setupStudentData()
        setupListeners()
        fetchStudentProfile()
    }

    private fun setupStudentData() {
        val rawName = sessionManager.getUserName()
        val studentName = if (!rawName.isNullOrBlank() && !rawName.equals("Student Demo", ignoreCase = true)) {
            rawName
        } else {
            "L. Sai Rohan"
        }

        binding.tvHeroStudentName.text = studentName
        binding.tvInfoFullName.text = studentName

        val studentId = sessionManager.getUserId()
        val code = if (studentId > 0) "MD-%05d".format(studentId) else "MD-00010"
        binding.tvHeroStudentId.text = code

        // Load persisted editable fields or fall back to authentic defaults
        val prefs = getSharedPreferences(PREFS_PROFILE, Context.MODE_PRIVATE)
        val father = prefs.getString(KEY_FATHER_NAME, "Lingadhurai. S") ?: "Lingadhurai. S"
        val mother = prefs.getString(KEY_MOTHER_NAME, "Patturani. L") ?: "Patturani. L"
        val phone = prefs.getString(KEY_PHONE, "8939319656") ?: "8939319656"
        val altPhone = prefs.getString(KEY_ALT_PHONE, "9841882666") ?: "9841882666"
        val bloodGroup = prefs.getString(KEY_BLOOD_GROUP, "A1+ve") ?: "A1+ve"
        val address = prefs.getString(KEY_ADDRESS, "J.K. builders 2nd floor, Rangangar 1st main, Old Perungalathur, Chennai.")
            ?: "J.K. builders 2nd floor, Rangangar 1st main, Old Perungalathur, Chennai."

        binding.tvFatherName.text = father
        binding.tvMotherName.text = mother
        binding.tvPhoneNumber.text = phone
        binding.tvAlternatePhone.text = altPhone
        binding.tvStatBloodGroup.text = bloodGroup
        binding.tvInfoBloodGroup.text = bloodGroup
        binding.tvAddress.text = address
    }

    private fun setupListeners() {
        // Back Button
        binding.btnBack.setOnClickListener {
            finish()
        }

        // Edit Dialog Triggers
        val openEdit = { showEditProfileDialog() }
        binding.btnTopEdit.setOnClickListener { openEdit() }
        binding.btnEditPersonal.setOnClickListener { openEdit() }
        binding.btnEditParent.setOnClickListener { openEdit() }
        binding.btnEditContact.setOnClickListener { openEdit() }

        // Click to Dial Phone Numbers
        binding.tvPhoneNumber.setOnClickListener {
            val phone = binding.tvPhoneNumber.text.toString().trim()
            if (phone.isNotEmpty()) {
                val dialIntent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phone"))
                startActivity(dialIntent)
            }
        }

        binding.tvAlternatePhone.setOnClickListener {
            val phone = binding.tvAlternatePhone.text.toString().trim()
            if (phone.isNotEmpty()) {
                val dialIntent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:$phone"))
                startActivity(dialIntent)
            }
        }
    }

    private fun showEditProfileDialog() {
        val dialogBinding = DialogEditStudentProfileBinding.inflate(LayoutInflater.from(this))

        dialogBinding.etFatherName.setText(binding.tvFatherName.text.toString())
        dialogBinding.etMotherName.setText(binding.tvMotherName.text.toString())
        dialogBinding.etPhone.setText(binding.tvPhoneNumber.text.toString())
        dialogBinding.etAlternatePhone.setText(binding.tvAlternatePhone.text.toString())
        dialogBinding.etBloodGroup.setText(binding.tvStatBloodGroup.text.toString())
        dialogBinding.etAddress.setText(binding.tvAddress.text.toString())

        MaterialAlertDialogBuilder(this)
            .setTitle("🥋 Edit Student Profile")
            .setView(dialogBinding.root)
            .setPositiveButton("Save Changes") { _, _ ->
                val newFather = dialogBinding.etFatherName.text.toString().trim()
                val newMother = dialogBinding.etMotherName.text.toString().trim()
                val newPhone = dialogBinding.etPhone.text.toString().trim()
                val newAltPhone = dialogBinding.etAlternatePhone.text.toString().trim()
                val newBlood = dialogBinding.etBloodGroup.text.toString().trim()
                val newAddr = dialogBinding.etAddress.text.toString().trim()

                // Save to SharedPreferences
                val prefs = getSharedPreferences(PREFS_PROFILE, Context.MODE_PRIVATE)
                prefs.edit()
                    .putString(KEY_FATHER_NAME, newFather)
                    .putString(KEY_MOTHER_NAME, newMother)
                    .putString(KEY_PHONE, newPhone)
                    .putString(KEY_ALT_PHONE, newAltPhone)
                    .putString(KEY_BLOOD_GROUP, newBlood)
                    .putString(KEY_ADDRESS, newAddr)
                    .apply()

                // Update UI
                binding.tvFatherName.text = newFather
                binding.tvMotherName.text = newMother
                binding.tvPhoneNumber.text = newPhone
                binding.tvAlternatePhone.text = newAltPhone
                binding.tvStatBloodGroup.text = newBlood
                binding.tvInfoBloodGroup.text = newBlood
                // Sync with live cloud server asynchronously
                val studentId = sessionManager.getUserId()
                lifecycleScope.launch {
                    try {
                        val body = mapOf(
                            "student_id" to studentId.toString(),
                            "father_name" to newFather,
                            "mother_name" to newMother,
                            "phone" to newPhone,
                            "alternate_phone" to newAltPhone,
                            "blood_group" to newBlood,
                            "address" to newAddr
                        )
                        ApiClient.apiService.updateStudentProfile(body)
                    } catch (_: Exception) {
                        // Keep local persistence if offline
                    }
                }

                Toast.makeText(this, "Profile updated successfully!", Toast.LENGTH_SHORT).show()
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun fetchStudentProfile() {
        val studentId = sessionManager.getUserId()
        lifecycleScope.launch {
            try {
                val response = ApiClient.apiService.getStudentProfile(if (studentId > 0) studentId else null)
                if (response.isSuccessful && response.body()?.data != null) {
                    val profile = response.body()!!.data!!

                    if (!profile.name.isNullOrBlank()) {
                        binding.tvHeroStudentName.text = profile.name
                        binding.tvInfoFullName.text = profile.name
                    }
                    if (!profile.memberId.isNullOrBlank()) {
                        binding.tvHeroStudentId.text = profile.memberId
                    }
                    if (!profile.fatherName.isNullOrBlank()) {
                        binding.tvFatherName.text = profile.fatherName
                    }
                    if (!profile.motherName.isNullOrBlank()) {
                        binding.tvMotherName.text = profile.motherName
                    }
                    if (!profile.phone.isNullOrBlank()) {
                        binding.tvPhoneNumber.text = profile.phone
                    }
                    if (!profile.alternatePhone.isNullOrBlank()) {
                        binding.tvAlternatePhone.text = profile.alternatePhone
                    }
                    if (!profile.bloodGroup.isNullOrBlank()) {
                        binding.tvStatBloodGroup.text = profile.bloodGroup
                        binding.tvInfoBloodGroup.text = profile.bloodGroup
                    }
                    if (!profile.address.isNullOrBlank()) {
                        binding.tvAddress.text = profile.address
                    }

                    // Save back to SharedPreferences for offline caching
                    val prefs = getSharedPreferences(PREFS_PROFILE, Context.MODE_PRIVATE)
                    prefs.edit()
                        .putString(KEY_FATHER_NAME, binding.tvFatherName.text.toString())
                        .putString(KEY_MOTHER_NAME, binding.tvMotherName.text.toString())
                        .putString(KEY_PHONE, binding.tvPhoneNumber.text.toString())
                        .putString(KEY_ALT_PHONE, binding.tvAlternatePhone.text.toString())
                        .putString(KEY_BLOOD_GROUP, binding.tvStatBloodGroup.text.toString())
                        .putString(KEY_ADDRESS, binding.tvAddress.text.toString())
                        .apply()
                }
            } catch (_: Exception) {
                // Retain offline cache safely
            }
        }
    }
}
