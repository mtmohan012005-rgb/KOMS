package com.koms.app.ui.auth

import android.content.Intent
import android.os.Bundle
import android.text.method.HideReturnsTransformationMethod
import android.text.method.PasswordTransformationMethod
import android.view.View
import android.view.animation.AccelerateDecelerateInterpolator
import android.view.animation.OvershootInterpolator
import android.widget.Toast
import androidx.activity.viewModels
import androidx.appcompat.app.AppCompatActivity
import com.koms.app.databinding.ActivityLoginBinding
import com.koms.app.ui.student.StudentDashboardActivity
import com.koms.app.utils.SessionManager

class LoginActivity : AppCompatActivity() {

    private lateinit var binding: ActivityLoginBinding
    private val viewModel: LoginViewModel by viewModels()
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)
        
        sessionManager = SessionManager(this)

        if (sessionManager.isLoggedIn()) {
            navigateToDashboard(sessionManager.getUserRole() ?: "")
        }

        setupCinematicStoryboardAnimations()
        setupObservers()

        // Password visibility toggle
        var isPasswordVisible = false
        binding.ivTogglePassword.setOnClickListener {
            isPasswordVisible = !isPasswordVisible
            if (isPasswordVisible) {
                binding.etPassword.transformationMethod = HideReturnsTransformationMethod.getInstance()
                binding.ivTogglePassword.animate().alpha(0.5f).duration = 150
            } else {
                binding.etPassword.transformationMethod = PasswordTransformationMethod.getInstance()
                binding.ivTogglePassword.animate().alpha(1f).duration = 150
            }
            binding.etPassword.setSelection(binding.etPassword.text.length)
        }
        
        binding.btnLogin.setOnClickListener {
            val email = binding.etEmail.text.toString().trim()
            val pass = binding.etPassword.text.toString().trim()
            
            if (email.isNotEmpty() && pass.isNotEmpty()) {
                viewModel.login(email, pass)
            } else {
                Toast.makeText(this, "Please enter email and password", Toast.LENGTH_SHORT).show()
            }
        }

        // 1-Click Demo Buttons
        binding.btnDemoStudent.setOnClickListener {
            binding.etEmail.setText("student@gmail.com")
            binding.etPassword.setText("password123")
            binding.btnLogin.performClick()
        }

        binding.btnDemoMaster.setOnClickListener {
            binding.etEmail.setText("master@koms.com")
            binding.etPassword.setText("masterpass")
            binding.btnLogin.performClick()
        }

        binding.btnDemoAdmin.setOnClickListener {
            binding.etEmail.setText("admin@koms.com")
            binding.etPassword.setText("adminpass")
            binding.btnLogin.performClick()
        }
    }

    private fun setupCinematicStoryboardAnimations() {
        // Initial hidden & split states (Steps 1 & 2)
        binding.ivLogoLeft.translationX = -300f
        binding.ivLogoLeft.alpha = 0f
        binding.ivLogoRight.translationX = 300f
        binding.ivLogoRight.alpha = 0f

        binding.ivLogoFull.alpha = 0f
        binding.ivLogoFull.scaleX = 0.8f
        binding.ivLogoFull.scaleY = 0.8f

        binding.flareView.scaleY = 0f
        binding.flareView.alpha = 0f

        binding.titleSection.alpha = 0f
        binding.titleSection.translationY = 40f
        binding.titleSection.scaleX = 0.95f
        binding.titleSection.scaleY = 0.95f

        binding.loginFormCard.alpha = 0f
        binding.loginFormCard.translationY = 60f

        // Step 2: Both halves slide inward smoothly with OvershootInterpolator
        binding.ivLogoLeft.animate()
            .translationX(0f)
            .alpha(1f)
            .setInterpolator(OvershootInterpolator(1.2f))
            .duration = 1100

        binding.ivLogoRight.animate()
            .translationX(0f)
            .alpha(1f)
            .setInterpolator(OvershootInterpolator(1.2f))
            .duration = 1100

        // Step 3: Golden vertical flare flash & merge at 950ms
        binding.flareView.postDelayed({
            binding.flareView.animate()
                .scaleY(1.5f)
                .alpha(1f)
                .setDuration(250)
                .withEndAction {
                    binding.flareView.animate()
                        .scaleY(0f)
                        .alpha(0f)
                        .setDuration(250)
                        .start()
                }.start()

            // Crossfade split halves into glowing full crest
            binding.logoHalvesContainer.animate().alpha(0f).duration = 300
            binding.ivLogoFull.animate()
                .alpha(1f)
                .scaleX(1.05f)
                .scaleY(1.05f)
                .setDuration(400)
                .withEndAction {
                    binding.ivLogoFull.animate()
                        .scaleX(1f)
                        .scaleY(1f)
                        .setDuration(200)
                        .start()
                }.start()
        }, 950)

        // Step 4: Logo settles and Title section fades in smoothly at 2.0s
        binding.titleSection.postDelayed({
            binding.titleSection.animate()
                .alpha(1f)
                .translationY(0f)
                .scaleX(1f)
                .scaleY(1f)
                .setInterpolator(AccelerateDecelerateInterpolator())
                .duration = 900
        }, 2000)

        // Steps 5 & 6: Login form card fades & slides up into view at 2.6s
        binding.loginFormCard.postDelayed({
            binding.loginFormCard.animate()
                .alpha(1f)
                .translationY(0f)
                .setInterpolator(AccelerateDecelerateInterpolator())
                .duration = 900
        }, 2600)
    }

    private fun setupObservers() {
        viewModel.isLoading.observe(this) { isLoading ->
            binding.progressBar.visibility = if (isLoading) View.VISIBLE else View.GONE
            binding.btnLogin.isEnabled = !isLoading
        }

        viewModel.loginResult.observe(this) { response ->
            if (response.success && (response.data != null)) {
                val user = response.data
                sessionManager.saveAuthToken(user.token)
                sessionManager.saveUserDetails(user.userId, user.name, user.email, user.role)
                
                Toast.makeText(this, "Welcome ${user.name}!", Toast.LENGTH_SHORT).show()
                navigateToDashboard(user.role)
            } else {
                Toast.makeText(this, response.message, Toast.LENGTH_LONG).show()
            }
        }
    }

    private fun navigateToDashboard(role: String) {
        val intent = when (role) {
            "student" -> Intent(this, StudentDashboardActivity::class.java)
            else -> Intent(this, StudentDashboardActivity::class.java)
        }
        startActivity(intent)
        finish()
    }
}
