package com.koms.app.ui.auth

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.view.LayoutInflater
import android.widget.EditText
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.GravityCompat
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.koms.app.R
import com.koms.app.databinding.ActivityPublicHomeBinding
import com.koms.app.ui.student.DojosActivity

class PublicHomeActivity : AppCompatActivity() {

    private lateinit var binding: ActivityPublicHomeBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityPublicHomeBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setupHeaderAndDrawer()
        setupQuickActionCards()
        setupUpdatesAndBanners()
        setupBottomNav()
    }

    private fun setupHeaderAndDrawer() {
        // Login button on top right
        binding.btnHeaderLogin.setOnClickListener {
            startActivity(Intent(this, LoginActivity::class.java))
        }

        // Hamburger Menu opens right drawer
        binding.btnPublicMenu.setOnClickListener {
            binding.publicDrawerLayout.openDrawer(GravityCompat.END)
        }

        // Drawer items
        binding.drawerItemLogin.setOnClickListener {
            binding.publicDrawerLayout.closeDrawer(GravityCompat.END)
            startActivity(Intent(this, LoginActivity::class.java))
        }

        binding.drawerItemRegister.setOnClickListener {
            binding.publicDrawerLayout.closeDrawer(GravityCompat.END)
            showRegistrationDialog()
        }

        binding.drawerItemWebPortal.setOnClickListener {
            binding.publicDrawerLayout.closeDrawer(GravityCompat.END)
            startActivity(Intent(this, LoginActivity::class.java))
        }

        binding.drawerItemAbout.setOnClickListener {
            binding.publicDrawerLayout.closeDrawer(GravityCompat.END)
            showAboutDialog()
        }
    }

    private fun setupQuickActionCards() {
        // 1. Our Dojos
        binding.cardNavDojos.setOnClickListener {
            startActivity(Intent(this, DojosActivity::class.java))
        }

        // 2. About Us
        binding.cardNavAbout.setOnClickListener {
            showAboutDialog()
        }

        // 3. Events & Tournaments
        binding.cardNavEvents.setOnClickListener {
            showEventsDialog()
        }

        // 4. Belt System
        binding.cardNavBelts.setOnClickListener {
            showBeltSystemDialog()
        }

        // 5. Contact Us
        binding.cardNavContact.setOnClickListener {
            showContactDialog()
        }
    }

    private fun setupUpdatesAndBanners() {
        // Discover The Art
        binding.cardDiscoverArt.setOnClickListener {
            showDiscoverArtDialog()
        }
        binding.btnLearnMore.setOnClickListener {
            showDiscoverArtDialog()
        }

        // View All Updates
        binding.btnViewAllUpdates.setOnClickListener {
            showEventsDialog()
        }

        // Update 1: Black Belt Awarding Ceremony
        binding.itemUpdate1.setOnClickListener {
            MaterialAlertDialogBuilder(this)
                .setTitle("🥋 Black Belt Awarding Ceremony")
                .setMessage("Date: 06 September 2026\nVenue: MASS DRAGON DOJO (Ranga Nagar Dojo)\n\nA special ceremony honoring students who demonstrated exceptional discipline, technical mastery, and internal peace to achieve their Black Belt promotion.")
                .setPositiveButton("Close", null)
                .show()
        }

        // Update 2: New Batch Registration Open
        binding.itemUpdate2.setOnClickListener {
            showRegistrationDialog()
        }

        // Update 3: Summer Camp 2026
        binding.itemUpdate3.setOnClickListener {
            MaterialAlertDialogBuilder(this)
                .setTitle("🏕️ Summer Camp 2026")
                .setMessage("Dates: 10 - 15 June 2026\nLocation: Bukenkan Training Grounds, Chennai\n\nIntensive training in Kata, Kumite, and Kobudo weaponry (Bo, Sai, Nunchaku) designed to build lifelong confidence and physical stamina.")
                .setPositiveButton("Close", null)
                .show()
        }

        // Gallery
        binding.cardGallery.setOnClickListener {
            showGalleryDialog()
        }
        binding.btnViewGallery.setOnClickListener {
            showGalleryDialog()
        }
    }

    private fun setupBottomNav() {
        binding.navTabHome.setOnClickListener {
            binding.publicScrollView.smoothScrollTo(0, 0)
        }

        binding.navTabDojo.setOnClickListener {
            startActivity(Intent(this, DojosActivity::class.java))
        }

        binding.navTabEvents.setOnClickListener {
            showEventsDialog()
        }

        binding.navTabGallery.setOnClickListener {
            showGalleryDialog()
        }

        binding.navTabMore.setOnClickListener {
            binding.publicDrawerLayout.openDrawer(GravityCompat.END)
        }
    }

    private fun showAboutDialog() {
        MaterialAlertDialogBuilder(this)
            .setTitle("Kobudo Renmei Bukenkan - India")
            .setMessage("Organization: Kobudo Renmei Bukenkan - India\nHeadquarters: MASS DRAGON DOJO, Chennai\n\nLineage: Shorin-Ryu Karate-Do (Matsubayashi)\n\nMotto: “Discipline. Strength. Internal Peace.”\n\nPreserving authentic Okinawan martial arts heritage through rigorous training, traditional weapons (Kobudo), and character building.")
            .setPositiveButton("Explore Dojos") { _, _ ->
                startActivity(Intent(this, DojosActivity::class.java))
            }
            .setNegativeButton("Close", null)
            .show()
    }

    private fun showBeltSystemDialog() {
        MaterialAlertDialogBuilder(this)
            .setTitle("🥋 Bukenkan Belt Ranking System")
            .setMessage("Traditional Shorin-Ryu Belt Order:\n\n" +
                    "⚪ White Belt (6th Kyu) - Pure beginner, fundamentals\n" +
                    "🟡 Yellow Belt (5th Kyu) - Basic stances & strikes\n" +
                    "🟠 Orange Belt (4th Kyu) - Kata integration\n" +
                    "🟢 Green Belt (3rd Kyu) - Intermediate weapon kata\n" +
                    "🔵 Blue Belt (2nd Kyu) - Advanced kumite & balance\n" +
                    "🟤 Brown Belt (1st Kyu) - Pre-dan mastery & leadership\n" +
                    "⚫ Black Belt (1st - 10th Dan) - Lifelong mastery\n\n" +
                    "Gradings are conducted quarterly by authorized Masters under Grand Master approval.")
            .setPositiveButton("Got It", null)
            .show()
    }

    private fun showEventsDialog() {
        val events = arrayOf(
            "🏆 Inter Dojo Championship (12 Oct 2026)",
            "🥋 Self Defence & Kobudo Seminar (20 Oct 2026)",
            "📋 Belt Grading Session (15 Dec 2026)",
            "🥋 Black Belt Awarding Ceremony (06 Sep 2026)",
            "⛺ Summer Intensive Camp (10-15 Jun 2026)"
        )
        MaterialAlertDialogBuilder(this)
            .setTitle("Events & Tournaments")
            .setItems(events) { _, which ->
                Toast.makeText(this, "Selected: ${events[which]}", Toast.LENGTH_SHORT).show()
            }
            .setPositiveButton("Close", null)
            .show()
    }

    private fun showContactDialog() {
        MaterialAlertDialogBuilder(this)
            .setTitle("Contact MASS DRAGON DOJO")
            .setMessage("Dojo: Ranga Nagar Dojo (Main Center)\nAddress: Ranga Nagar, Chromepet / Tambaram, Chennai, Tamil Nadu\nTraining Days: Saturday & Sunday\nTiming: 6:30 PM – 8:30 PM\nPhone: +91 98400 12345\nEmail: contact@massdragondojo.com\n\nDiscipline. Strength. Internal Peace.")
            .setPositiveButton("Call Dojo") { _, _ ->
                try {
                    val intent = Intent(Intent.ACTION_DIAL, Uri.parse("tel:+919840012345"))
                    startActivity(intent)
                } catch (e: Exception) {
                    Toast.makeText(this, "Phone: +91 98400 12345", Toast.LENGTH_LONG).show()
                }
            }
            .setNegativeButton("Close", null)
            .show()
    }

    private fun showDiscoverArtDialog() {
        MaterialAlertDialogBuilder(this)
            .setTitle("Tradition • Discipline • Excellence")
            .setMessage("At MASS DRAGON DOJO, martial arts is more than fighting; it is a transformative way of life.\n\n" +
                    "Under Kobudo Renmei Bukenkan - India, our students train in both classical empty-hand Karate (Kata, Bunkai, Kumite) and classical Kobudo weaponry (Bo, Sai, Nunchaku, Tonfa).\n\n" +
                    "Join our community and cultivate unshakeable discipline, physical resilience, and internal peace.")
            .setPositiveButton("Join New Batch") { _, _ ->
                showRegistrationDialog()
            }
            .setNegativeButton("Close", null)
            .show()
    }

    private fun showGalleryDialog() {
        MaterialAlertDialogBuilder(this)
            .setTitle("🥋 KOMS Dojo Gallery")
            .setMessage("Browse training highlights, tournament victories, weapon demonstrations, and black belt graduation ceremonies across our 5 Chennai dojos.\n\nDiscipline • Strength • Internal Peace")
            .setPositiveButton("OK", null)
            .show()
    }

    private fun showRegistrationDialog() {
        MaterialAlertDialogBuilder(this)
            .setTitle("🥋 New Student Registration")
            .setMessage("Join MASS DRAGON DOJO.\n\nDojo: Ranga Nagar Dojo, Chennai\nSchedule: Saturday & Sunday (6:30 PM - 8:30 PM)\nEntry Fee: ₹500 | Monthly Fee: ₹200\n\nPlease visit the dojo or login with your student credentials provided by Head Sensei.")
            .setPositiveButton("Go to Login") { _, _ ->
                startActivity(Intent(this, LoginActivity::class.java))
            }
            .setNegativeButton("Cancel", null)
            .show()
    }
}
