-- KOMS Complete MySQL Database Schema

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(100) UNIQUE NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'master', 'senior', 'student') NOT NULL,
    dob DATE,
    gender ENUM('male', 'female', 'other'),
    blood_group VARCHAR(30) NULL,
    father_name VARCHAR(150) NULL,
    mother_name VARCHAR(150) NULL,
    phone VARCHAR(20),
    alternate_phone VARCHAR(20) NULL,
    date_of_joining DATE NULL,
    address TEXT,
    emergency_contact VARCHAR(100),
    profile_photo VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    password_change_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Dojos Table
CREATE TABLE IF NOT EXISTS dojos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    master_id INT NOT NULL,
    location TEXT NOT NULL,
    contact_number VARCHAR(20),
    email VARCHAR(150),
    experience TEXT,
    achievements TEXT,
    description TEXT,
    training_days VARCHAR(255),
    training_timings VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected', 'inactive') DEFAULT 'pending',
    approval_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (master_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Student Dojo Memberships (Requests & Active Members)
CREATE TABLE IF NOT EXISTS dojo_memberships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    dojo_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled', 'inactive') DEFAULT 'pending',
    joined_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dojo_id) REFERENCES dojos(id) ON DELETE CASCADE
);

-- Attendance Sessions
CREATE TABLE IF NOT EXISTS attendance_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dojo_id INT NOT NULL,
    instructor_id INT NOT NULL,
    session_date DATE NOT NULL,
    scheduled_day VARCHAR(20),
    start_time TIME,
    end_time TIME,
    is_locked BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dojo_id) REFERENCES dojos(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id)
);

-- Attendance Entries
CREATE TABLE IF NOT EXISTS attendance_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    remarks TEXT,
    marked_by INT NOT NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Fee Structures (SCD Type 2 Temporal Pattern)
CREATE TABLE IF NOT EXISTS fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dojo_id INT NOT NULL,
    fee_name VARCHAR(100) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    frequency ENUM('monthly', 'quarterly', 'yearly', 'one-time') DEFAULT 'monthly',
    effective_from DATE NOT NULL,
    effective_until DATE NULL,
    valid_from DATETIME DEFAULT CURRENT_TIMESTAMP,
    valid_to DATETIME NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dojo_id) REFERENCES dojos(id) ON DELETE CASCADE
);

-- Monthly Fee Records
CREATE TABLE IF NOT EXISTS fee_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_structure_id INT NOT NULL,
    billing_month DATE NOT NULL,
    amount_due DECIMAL(10, 2) NOT NULL,
    due_date DATE NOT NULL,
    status ENUM('pending', 'partially_paid', 'paid', 'overdue', 'waived') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id)
);

-- Payments
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fee_record_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_ref VARCHAR(100),
    payment_date DATE NOT NULL,
    recorded_by INT,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fee_record_id) REFERENCES fee_records(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tournaments
CREATE TABLE IF NOT EXISTS tournaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    venue TEXT NOT NULL,
    registration_deadline DATE NOT NULL,
    status ENUM('draft', 'published', 'registration_open', 'registration_closed', 'completed', 'cancelled') DEFAULT 'draft',
    target_type ENUM('all', 'specific') DEFAULT 'all',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Tournament Registrations
CREATE TABLE IF NOT EXISTS tournament_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT NOT NULL,
    student_id INT NOT NULL,
    dojo_id INT NOT NULL,
    status ENUM('pending_review', 'selected', 'rejected') DEFAULT 'pending_review',
    reviewed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dojo_id) REFERENCES dojos(id) ON DELETE CASCADE
);

-- Grading History
CREATE TABLE IF NOT EXISTS grading_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    dojo_id INT NOT NULL,
    previous_belt VARCHAR(50),
    new_belt VARCHAR(50) NOT NULL,
    exam_date DATE NOT NULL,
    grade VARCHAR(20),
    instructor_id INT NOT NULL,
    remarks TEXT,
    certificate_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dojo_id) REFERENCES dojos(id) ON DELETE CASCADE
);

-- Achievements
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    competition_event VARCHAR(150),
    position_result VARCHAR(50),
    achievement_date DATE NOT NULL,
    certificate_file VARCHAR(255),
    added_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Announcements
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    level ENUM('global', 'dojo') NOT NULL,
    dojo_id INT NULL,
    publish_date DATE NOT NULL,
    expiry_date DATE NULL,
    status ENUM('active', 'archived') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Audit Logs
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    module VARCHAR(50) NOT NULL,
    record_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- DPDP Act 2023 Student Profiles & Verifiable Parental Consent (VPC)
CREATE TABLE IF NOT EXISTS student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    is_minor BOOLEAN DEFAULT FALSE,
    parent_name VARCHAR(150),
    parent_contact VARCHAR(50),
    parent_email VARCHAR(150),
    parent_consent_status ENUM('pending', 'verified', 'rejected', 'not_applicable') DEFAULT 'pending',
    parent_consent_method ENUM('aadhaar_otp', 'card_verification', 'video_kyc', 'manual_affidavit') NULL,
    parent_consent_artifact VARCHAR(255) NULL,
    parent_consent_timestamp TIMESTAMP NULL,
    medical_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- State Transitions Whitelist for Finite State Machine (FSM)
CREATE TABLE IF NOT EXISTS state_transitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    from_state VARCHAR(50) NOT NULL,
    to_state VARCHAR(50) NOT NULL,
    allowed_roles VARCHAR(100) NOT NULL,
    description VARCHAR(255)
);

-- Single-Elimination Tournament Match Brackets
CREATE TABLE IF NOT EXISTS tournament_brackets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT NOT NULL,
    round_number INT NOT NULL,
    match_number INT NOT NULL,
    participant1_id INT NULL,
    participant2_id INT NULL,
    winner_id INT NULL,
    score1 VARCHAR(20) DEFAULT '0',
    score2 VARCHAR(20) DEFAULT '0',
    is_bye BOOLEAN DEFAULT FALSE,
    status ENUM('scheduled', 'in_progress', 'completed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE
);

-- Password Reset Requests (Enforces 1 self-service change rule)
CREATE TABLE IF NOT EXISTS password_reset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    dojo_id INT NULL,
    reason TEXT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    master_notes TEXT NULL,
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
);
