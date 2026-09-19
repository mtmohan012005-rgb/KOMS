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
    private var isReadOnly: Boolean = false
    private var targetStudentId: Int = -1

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

        val role = sessionManager.getUserRole() ?: "student"
        val loggedInUserId = sessionManager.getUserId()
        targetStudentId = intent.getIntExtra("student_id", -1)
        if (targetStudentId <= 0) {
            targetStudentId = loggedInUserId
        }

        val isMasterOrAdmin = role.equals("master", ignoreCase = true) || role.equals("super_admin", ignoreCase = true) || role.equals("grand_master", ignoreCase = true)
        isReadOnly = isMasterOrAdmin || (targetStudentId > 0 && targetStudentId != loggedInUserId)

        setupStudentData()
        setupListeners()
        fetchStudentProfile()
    }

    private fun setupStudentData() {
        val rawName = sessionManager.getUserName()
        val studentName = if (!rawName.isNullOrBlank() && !rawName.equals("Student Demo", ignoreCase = true) && !isReadOnly) {
            rawName
        } else {
            "L. Sai Rohan"
        }

        binding.tvHeroStudentName.text = studentName
        binding.tvInfoFullName.text = studentName

        val code = if (targetStudentId > 0) "MD-%05d".format(targetStudentId) else "MD-00010"
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

        if (isReadOnly) {
            // Master or non-owner cannot edit student-controlled personal details
            binding.btnTopEdit.visibility = android.view.View.GONE
            binding.btnEditPersonal.visibility = android.view.View.GONE
            binding.btnEditParent.visibility = android.view.View.GONE
            binding.btnEditContact.visibility = android.view.View.GONE
            binding.tvTopSubtitle.text = "Student Records (Read-Only • Master View)"
        } else {
            // Student owns and controls their personal profile and contact details
            binding.btnTopEdit.visibility = android.view.View.VISIBLE
            binding.btnEditPersonal.visibility = android.view.View.VISIBLE
            binding.btnEditParent.visibility = android.view.View.VISIBLE
            binding.btnEditContact.visibility = android.view.View.VISIBLE

            val openEdit = { showEditProfileDialog() }
            binding.btnTopEdit.setOnClickListener { openEdit() }
            binding.btnEditPersonal.setOnClickListener { openEdit() }
            binding.btnEditParent.setOnClickListener { openEdit() }
            binding.btnEditContact.setOnClickListener { openEdit() }
        }

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
        if (isReadOnly) {
            Toast.makeText(this, "Access Denied: Masters cannot modify student personal details.", Toast.LENGTH_LONG).show()
            return
        }
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
        val queryStudentId = if (targetStudentId > 0) targetStudentId else sessionManager.getUserId()
        lifecycleScope.launch {
            try {
                val response = ApiClient.apiService.getStudentProfile(if (queryStudentId > 0) queryStudentId else null)
                if (response.isSuccessful && response.body()?.data != null) {
                    val profile = response.body()!!.data!!

                    // 1. Hero Student Name & IDs
                    if (!profile.name.isNullOrBlank()) {
                        binding.tvHeroStudentName.text = profile.name
                        binding.tvInfoFullName.text = profile.name
                    }
                    if (!profile.memberId.isNullOrBlank()) {
                        binding.tvHeroStudentId.text = profile.memberId
                    }
                    if (!profile.role.isNullOrBlank()) {
                        binding.tvHeroRoleBadge.text = profile.role.replaceFirstChar { it.uppercase() }
                    }

                    // 2. Dates & Age
                    val dobText = profile.dob ?: "2012-10-20"
                    val ageVal = if (profile.age > 0) profile.age else 13
                    binding.tvHeroAgeMeta.text = "Age: $ageVal years | DOB: $dobText"
                    binding.tvInfoDob.text = "$dobText ($ageVal years)"

                    if (!profile.dateOfJoining.isNullOrBlank()) {
                        binding.tvHeroJoinedMeta.text = "Joined On: ${profile.dateOfJoining}"
                        binding.tvInfoJoined.text = profile.dateOfJoining
                    }

                    // 3. Gender & Blood Group
                    if (!profile.gender.isNullOrBlank()) {
                        binding.tvStatGender.text = profile.gender
                        binding.tvInfoGender.text = profile.gender
                    }
                    if (!profile.bloodGroup.isNullOrBlank()) {
                        binding.tvStatBloodGroup.text = profile.bloodGroup
                        binding.tvInfoBloodGroup.text = profile.bloodGroup
                    }

                    // 4. Dojo & Master
                    if (!profile.dojoName.isNullOrBlank()) {
                        binding.tvStatDojoName.text = profile.dojoName
                        val loc = profile.dojoLocation ?: "Dojo Center"
                        val master = profile.masterName ?: "Dojo Master"
                        binding.tvStatDojoSub.text = "$loc • $master"
                    }

                    // 5. Belts & Rank
                    if (!profile.currentBelt.isNullOrBlank()) {
                        binding.tvStatBelt.text = profile.currentBelt
                    }
                    val target = profile.targetBelt ?: "Next Belt"
                    val level = profile.trainingLevel ?: "Training"
                    binding.tvStatTargetBelt.text = "Target: $target ($level)"

                    // 6. Parent & Contact Details
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
                    if (!profile.address.isNullOrBlank()) {
                        binding.tvAddress.text = profile.address
                    }

                    // 7. Monthly Dojo Progress Bars & Stats (Dynamic from DB)
                    binding.tvProgressAttendanceLabel.text = "Classes Attended (${profile.classesAttended} / ${profile.totalClasses})"
                    binding.tvProgressAttendancePercent.text = "${profile.attendancePercentage}%"
                    binding.pbProgressAttendance.progress = profile.attendancePercentage.coerceIn(0, 100)

                    val paidAmt = profile.feesPaid.toInt()
                    val totalAmt = profile.feesTotal.toInt()
                    binding.tvProgressFeesLabel.text = "Fees Paid (₹ $paidAmt / ₹ $totalAmt)"
                    binding.tvProgressFeesPercent.text = "${profile.feesPercentage}%"
                    binding.pbProgressFees.progress = profile.feesPercentage.coerceIn(0, 100)

                    binding.tvProgressAchievementsLabel.text = "Achievements (${profile.achievementsCount} Active)"
                    val achPercent = if (profile.achievementsCount > 0) 100 else 0
                    binding.tvProgressAchievementsPercent.text = "$achPercent%"
                    binding.pbProgressAchievements.progress = achPercent

                    binding.tvProgressCertificatesLabel.text = "Certificates Issued (${profile.certificatesCount})"
                    val certPercent = if (profile.certificatesCount > 0) 100 else 0
                    binding.tvProgressCertificatesPercent.text = "$certPercent%"
                    binding.pbProgressCertificates.progress = certPercent

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
