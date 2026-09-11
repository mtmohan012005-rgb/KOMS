package com.koms.app.ui.auth

import android.R
import android.annotation.SuppressLint
import android.content.Intent
import android.os.Bundle
import android.view.animation.AccelerateDecelerateInterpolator
import android.view.animation.OvershootInterpolator
import androidx.appcompat.app.AppCompatActivity
import com.koms.app.databinding.ActivitySplashBinding
import com.koms.app.ui.student.StudentDashboardActivity
import com.koms.app.utils.SessionManager

@SuppressLint("CustomSplashScreen")
class SplashActivity : AppCompatActivity() {

    private lateinit var binding: ActivitySplashBinding
    private lateinit var sessionManager: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        binding = ActivitySplashBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sessionManager = SessionManager(this)

        startLogoEntranceAnimation()
    }

    private fun startLogoEntranceAnimation() {
        // Initial hidden & split states
        binding.ivLogoLeft.translationX = -350f
        binding.ivLogoLeft.alpha = 0f
        binding.ivLogoRight.translationX = 350f
        binding.ivLogoRight.alpha = 0f

        binding.ivLogoFull.alpha = 0f
        binding.ivLogoFull.scaleX = 0.8f
        binding.ivLogoFull.scaleY = 0.8f

        binding.flareView.scaleY = 0f
        binding.flareView.alpha = 0f

        binding.titleSection.alpha = 0f
        binding.titleSection.translationY = 30f

        // Step 1 & 2: Both halves slide inward smoothly with OvershootInterpolator
        binding.ivLogoLeft.animate()
            .translationX(0f)
            .alpha(1f)
            .setInterpolator(OvershootInterpolator(1.3f))
            .duration = 1000

        binding.ivLogoRight.animate()
            .translationX(0f)
            .alpha(1f)
            .setInterpolator(OvershootInterpolator(1.3f))
            .duration = 1000

        // Step 3: Golden vertical flare flash & convergence at 900ms
        binding.flareView.postDelayed({
            binding.flareView.animate()
                .scaleY(1.6f)
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
                .scaleX(1.1f)
                .scaleY(1.1f)
                .setDuration(400)
                .withEndAction {
                    binding.ivLogoFull.animate()
                        .scaleX(1f)
                        .scaleY(1f)
                        .setDuration(250)
                        .start()
                }.start()
        }, 900)

        // Step 4: Title section fades in smoothly
        binding.titleSection.postDelayed({
            binding.titleSection.animate()
                .alpha(1f)
                .translationY(0f)
                .setInterpolator(AccelerateDecelerateInterpolator())
                .duration = 800
        }, 1800)

        // Step 5: Transition to Login/Dashboard after intro animation (2.8s)
        binding.root.postDelayed({
            if (sessionManager.isLoggedIn()) {
                startActivity(Intent(this, StudentDashboardActivity::class.java))
            } else {
                startActivity(Intent(this, LoginActivity::class.java))
            }
            overridePendingTransition(R.anim.fade_in, R.anim.fade_out)
            finish()
        }, 2800)
    }
}
