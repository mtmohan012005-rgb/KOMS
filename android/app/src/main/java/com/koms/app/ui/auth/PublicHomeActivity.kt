package com.koms.app.ui.auth

import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.core.view.GravityCompat
import androidx.lifecycle.lifecycleScope
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.koms.app.databinding.ActivityPublicHomeBinding
import com.koms.app.ui.student.DojosActivity
import kotlinx.coroutines.launch

class PublicHomeActivity : AppCompatActivity() {

    private lateinit var binding: ActivityPublicHomeBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityPublicHomeBinding.inflate(layoutInflater)
        setContentView(binding.root)

        setupHeaderAndDrawer()
        setupHeroAndCards()
        setupBottomNav()
    }

    private fun setupHeaderAndDrawer() {
        // Red Login button on top right
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

        binding.drawerItemDojos.setOnClickListener {
            binding.publicDrawerLayout.closeDrawer(GravityCompat.END)
            startActivity(Intent(this, DojosActivity::class.java))
        }
    }

    private fun setupHeroAndCards() {
        // Discover Our Tradition button & Card 1
        binding.btnDiscoverTradition.setOnClickListener {
            showTraditionDialog()
        }

        binding.cardTradition.setOnClickListener {
            showTraditionDialog()
        }
        binding.btnLearnMoreTradition.setOnClickListener {
            showTraditionDialog()
        }

        // Card 2: Our Masters
        binding.cardMasters.setOnClickListener {
            showMastersDialog()
        }
        binding.btnLearnMoreMasters.setOnClickListener {
            showMastersDialog()
        }

        // Card 3: Training Levels
        binding.cardTrainingLevels.setOnClickListener {
            showTrainingLevelsDialog()
        }
        binding.btnLearnMoreLevels.setOnClickListener {
            showTrainingLevelsDialog()
        }

        // Card 4: Upcoming Events
        binding.cardEvents.setOnClickListener {
            showEventsDialog()
        }
        binding.btnViewAllEvents.setOnClickListener {
            showEventsDialog()
        }

        // Card 5: Photo Gallery
        binding.cardGallery.setOnClickListener {
            showGalleryDialog()
        }
        binding.btnViewGallery.setOnClickListener {
            showGalleryDialog()
        }
    }

    private fun setupBottomNav() {
        // Tab 1: Home (Selected)
        binding.navTabHome.setOnClickListener {
            binding.publicScrollView.smoothScrollTo(0, 0)
        }

        // Tab 2: Dojo
        binding.navTabDojo.setOnClickListener {
            startActivity(Intent(this, DojosActivity::class.java))
        }

        // Tab 3: Events
        binding.navTabEvents.setOnClickListener {
            showEventsDialog()
        }

        // Tab 4: Gallery
        binding.navTabGallery.setOnClickListener {
            showGalleryDialog()
        }

        // Tab 5: Training Level
        binding.navTabTrainingLevel.setOnClickListener {
            showTrainingLevelsDialog()
        }
    }

    private fun showTraditionDialog() {
        MaterialAlertDialogBuilder(this)
            .setTitle("🥋 Karate Tradition & Lineage")
            .setMessage("Organization: Kobudo Renmei Bukenkan - India\nLineage: Shorin-Ryu Karate-Do (Matsubayashi)\n\nMotto: “Discipline. Strength. Internal Peace.”\n\nPreserving classical empty-hand Karate (Kata, Bunkai, Kumite) alongside authentic Okinawan Kobudo weaponry (Bo, Sai, Nunchaku, Tonfa). Our path cultivates mental fortitude, unshakeable character, and physical excellence.")
            .setPositiveButton("Explore Dojos") { _, _ ->
                startActivity(Intent(this, DojosActivity::class.java))
            }
            .setNegativeButton("Close", null)
            .show()
    }

    private fun showMastersDialog() {
        val masters = arrayOf(
            "🥋 Sensei S. Senthil Kumar (6th Dan) — Ranga Nagar Central Dojo",
            "🥋 Sensei R. Prakash (5th Dan) — Old Perungalathur Dojo",
            "🥋 Sensei V. Arul (5th Dan) — Tambaram Dojo",
            "🥋 Sensei K. Mani (4th Dan) — Velachery Dojo",
            "🥋 Sensei M. Rajesh (4th Dan) — Anna Nagar Dojo"
        )
        MaterialAlertDialogBuilder(this)
            .setTitle("Respected Masters & Instructors")
            .setItems(masters) { _, which ->
                Toast.makeText(this, "Master selected: ${masters[which]}", Toast.LENGTH_SHORT).show()
                startActivity(Intent(this, DojosActivity::class.java))
            }
            .setPositiveButton("Close", null)
            .show()
    }

    private fun showTrainingLevelsDialog() {
        MaterialAlertDialogBuilder(this)
            .setTitle("🥋 Belt Ranking & Training Levels")
            .setMessage("Traditional Shorin-Ryu Belt Syllabus:\n\n" +
                    "⚪ White Belt (6th Kyu) - Pure beginner, stances & basic blocks\n" +
                    "🟡 Yellow Belt (5th Kyu) - Kihon fundamentals & Fukyugata I\n" +
                    "🟠 Orange Belt (4th Kyu) - Fukyugata II & introduction to Bo\n" +
                    "🟢 Green Belt (3rd Kyu) - Pinan Shodan & Pinan Nidan\n" +
                    "🔵 Blue Belt (2nd Kyu) - Pinan Sandan & advanced Kumite\n" +
                    "🟤 Brown Belt (1st Kyu) - Pinan Yondan, Godan & Sai kata\n" +
                    "⚫ Black Belt (1st - 10th Dan) - Lifelong mastery & leadership\n\n" +
                    "Formal Dan exams are regulated directly under Grand Master oversight.")
            .setPositiveButton("Got It", null)
            .show()
    }

    private fun showEventsDialog() {
        val events = arrayOf(
            "🏆 Karate Championship 2026 (25 Oct 2026)\n   Venue: JN Indoor Stadium, Chennai",
            "🥋 Kobudo Weaponry Seminar (12 Nov 2026)\n   Venue: Ranga Nagar Central Dojo",
            "📋 Annual Dan Grading Convocation (18 Dec 2026)\n   Venue: Headquarters",
            "⛺ Summer Martial Intensive Camp (10-15 Jun 2026)\n   Venue: Training Grounds, Chennai"
        )
        MaterialAlertDialogBuilder(this)
            .setTitle("Upcoming Organization Events")
            .setItems(events) { _, which ->
                Toast.makeText(this, "Event: ${events[which].substringBefore("\n")}", Toast.LENGTH_SHORT).show()
            }
            .setPositiveButton("Close", null)
            .show()
    }

    private fun showGalleryDialog() {
        MaterialAlertDialogBuilder(this)
            .setTitle("📷 KOMS Photo Gallery")
            .setMessage("Featuring training highlights, championship podiums, weapon demonstrations, and black belt graduation ceremonies across all affiliated dojos.\n\nTradition • Discipline • Excellence")
            .setPositiveButton("View", null)
            .show()
    }

    private fun showRegistrationDialog() {
        MaterialAlertDialogBuilder(this)
            .setTitle("🥋 Student Registration")
            .setMessage("Join Kobudo Renmei Bukenkan.\n\nPlease visit your nearest affiliated dojo or sign up through your Sensei.")
            .setPositiveButton("Member Login") { _, _ ->
                startActivity(Intent(this, LoginActivity::class.java))
            }
            .setNegativeButton("Cancel", null)
            .show()
    }
}
