# Karate Organization Management System (KOMS)
### Enterprise Architectural Blueprint & Software Requirements Specification Implementation
**Featuring the Mass Dragon Dojo Traditional Okinawan Martial Arts Theme**

---

## 🥋 Executive Overview

The **Karate Organization Management System (KOMS)** is a centralized, multi-tenant Software-as-a-Service (SaaS) organizational management platform designed to digitize martial arts operations across a network of dojos under a strict hierarchical governance model.

KOMS eliminates minimum viable product compromises by implementing:
1. **Multi-Tenant Logical Isolation**: Shared Database, Shared Schema model with row-level isolation (`dojo_id` tenant scoping).
2. **DPDP Act 2023 Regulatory Compliance**: Dynamic age verification with mandatory **Verifiable Parental Consent (VPC)** for minors under 18 (Aadhaar-based OTP, card validation, Video KYC) prior to account activation.
3. **SCD Type 2 Temporal Fee Management**: Non-destructive fee configuration utilizing `valid_from` and `valid_to` timestamps, guaranteeing that historical accounting reports are mathematically immune to future pricing adjustments.
4. **Cryptographically Sealed Attendance Sessions**: Lockable attendance instances (`is_locked = 1`) mathematically sealing student session records to preserve belt grading prerequisites.
5. **Algorithmic Tournament Elimination Brackets**: Single-elimination tree generation with $2^k - N$ bye calculation and geographic dojo separation (preventing same-dojo matchups in preliminary rounds).
6. **Shorin Ryu Grading Syllabus**: Kyu (10th down to 1st) and Dan (1st up to 10th) append-only promotion ledger recording demonstrated Katas (*Fukyugata*, *Pinan*, *Naihanchi*).

---

## 🎨 Mass Dragon Dojo Entrance & Aesthetic Palette

The frontend architecture honors traditional Okinawan martial arts aesthetics:
- **Palette**: Deep Obsidian (`#030303`), Vibrant Gold (`#f4bd17`), Stark Crimson (`#e50914`).
- **Environmental Shaders**: SVG fractal noise grain overlay combined with hardware-accelerated particle canvas embers.
- **Choreographed Logo Entrance**: Two distinct halves of the Shorin Ryu logo emerge from the left (`-100vw`) and right (`+100vw`) viewports, converging at the exact center. Upon intersection, a central light beam and radial burst emit, cascading into the typography reveal (*"MASS DRAGON DOJO"* / *少林流空手道*) and the interactive login card.
- **Micro-Interactions**: ARIA-compliant password toggling, tactile CSS keyframe shake animations on credential validation failure, and luminous golden glow on verified entry.

---

## 👥 Four-Tier Role Hierarchy & Navigation Matrix

| Role | Interface Focus | Capabilities |
|---|---|---|
| **Grand Master (Super Admin)** | Global Oversight & Governance | Global Dojo Management, Master Assignments, Algorithmic Tournament Bracket Generation, Aggregate Revenue Ledger, DPDP Audit Logs. |
| **Master (Dojo Admin / Sensei)** | Localized Dojo Command Center | Student Roster, Join Request Approvals, Training Session Sealing, SCD Type 2 Fee Definition, Shorin Ryu Belt Grading Promotions. |
| **Senior (Sub-Admin / Assistant)** | Tactical Operational Execution | Daily Attendance Logging for assigned sessions, Student Safety Monitoring. Financial & Grading modification strictly disabled. |
| **Student (User / Practitioner)** | Personal Progression Portal | Belt Timeline with Kata Records, Real-time Dojo Discovery, Attendance Rate Calendar, Payment Ledger, Championship Enlistment. |

---

## ⚡ Quick Start & Deployment Guide

### Option 1: Instant Client-Side SPA Experience (No Setup Required)
Open `index.html` directly in any modern web browser to interact with the full Mass Dragon Dojo entrance sequence, 4-tier dashboard switcher, algorithmic tournament bracket generator, and DPDP VPC flow.

### Option 2: 1-Click Database Bootstrapper (For Localhost / XAMPP / Production)
1. Ensure your local MySQL server is running (e.g. via XAMPP).
2. Open `http://localhost/koms/setup.php` in your browser.
3. Click **Run 1-Click Database Setup**. The bootstrapper automatically creates the database, applies `schema.sql`, and seeds all test data and demo accounts.
4. Access the full application at `http://localhost/koms/index.html` or `http://localhost/koms/index.php`.

---

## 🔑 Seeded Demo Credentials

All test accounts share the password: `password123`

- **Grand Master (Super Admin)**: `admin@gmail.com`
- **Master Sensei (Mass Dragon Dojo)**: `master@gmail.com`
- **Senior Belt (Assistant Instructor)**: `senior@gmail.com`
- **Student Practitioner (Adult)**: `student@gmail.com`
- **Minor Student (<18 DPDP Compliance Demo)**: `minor@gmail.com`

---

## 📂 Architecture & Directory Structure

```
koms/
├── index.html               # Flagship Mass Dragon Dojo SPA & Entrance Sequence
├── style.css                # Martial arts Okinawan design system & animations
├── script.js               # Particle engine, router, algorithmic brackets, VPC
├── setup.php                # 1-Click automated database installer & bootstrapper
├── index.php                # Server-rendered home gateway with role-routing
├── login.php                # Accessible login with demo selector & Argon2/hash auth
├── register.php             # DPDP Act 2023 compliant registration with dynamic age
├── profile.php              # User profile management & security settings
├── find_dojo.php            # Real-time searchable dojo directory with day filters
├── dojo_details.php         # Dojo public profile & join request submission
├── logout.php               # Secure session termination & cookie revocation
├── admin/                   # Grand Master Super Admin module
│   ├── dashboard.php        # Global metrics, recent logs & quick actions
│   ├── audit.php            # Security audit trail with module & action filters
│   ├── dojos.php            # Affiliated dojo approvals & state transitions
│   ├── tournaments.php      # Championship creation & bracket management
│   ├── users.php            # Global role management & status toggles
│   └── announcements.php    # Organization-wide broadcasts
├── master/                  # Sensei Dojo Admin module
│   ├── dashboard.php        # Dojo command center (fixed fetchColumn cursor bug)
│   ├── edit_dojo.php        # Dojo training schedule & details editor
│   ├── process_request.php  # Student join request controller
│   ├── requests.php         # Pending student join requests review
│   ├── students.php         # Student roster with current belt badges
│   ├── attendance.php       # Training session creator & history
│   ├── mark_attendance.php  # Batch attendance marker with session sealing
│   ├── fees.php             # SCD Type 2 fee structures & outstanding billing
│   ├── record_payment.php   # Transaction ledger & payment recording
│   ├── grading.php          # Shorin Ryu Kyu/Dan Kata promotion ledger
│   └── tournaments.php      # Student tournament application reviews
├── senior/                  # Senior Assistant Instructor module
│   └── dashboard.php        # Attendance logging & student monitoring
├── student/                 # Student Practitioner module
│   ├── dashboard.php        # Personal dashboard with dynamic announcements
│   ├── my_dojo.php          # Enrolled dojo details, instructor & classmates
│   ├── attendance.php       # Personal attendance calendar & percentage
│   ├── fees.php             # Payment history & receipts
│   ├── grading.php          # Shorin Ryu belt history & Kata requirements
│   ├── tournaments.php      # Championship registration
│   └── announcements.php    # Global and dojo bulletin board
├── api/                     # REST API endpoints (sync'd from koms_repo)
│   ├── auth/login.php       # JSON API login endpoint
│   ├── attendance/          # Session & entry endpoints
│   ├── dojos/               # Dojo discovery endpoints
│   ├── fees/                # Fee query endpoints
│   └── tournaments/         # Tournament endpoints
├── config/
│   ├── config.php           # Global environment & path definitions
│   └── database.php         # PDO connection handler with graceful error catch
├── database/
│   ├── schema.sql           # 3NF schema, DPDP tables, SCD Type 2, FSM triggers
│   └── seed.sql             # Demo data, Shorin Ryu syllabi, Mass Dragon Dojo
├── uploads/                 # Uploaded certificates and profile assets
└── assets/
    ├── css/style.css        # Enterprise stylesheet
    └── js/main.js           # Global micro-interactions & live search library
```

---

## 🛡️ Regulatory Compliance: DPDP Act 2023 Implementation Notes

Under Section 9 of India's Digital Personal Data Protection (DPDP) Act of 2023:
1. **Age Derivation**: Age is calculated on the server and client directly from Date of Birth (`dob`). Manual input of conflicting age values is prohibited.
2. **Parental Verification**: If `age < 18`, registration cannot complete without capturing legal guardian details (`parent_name`, `parent_contact`, `parent_email`) and executing a Verifiable Parental Consent protocol.
3. **Consent Artifact**: Cryptographic proof of consent (`parent_consent_artifact`) is generated and stamped with a timestamp in `student_profiles` to fulfill statutory audit obligations.
4. **Prohibition of Tracking**: Minor accounts are insulated from behavioral profiling or tracking.

---
*Developed for Mass Dragon Dojo & the International Shorin Ryu Karate-Do Federation.*
