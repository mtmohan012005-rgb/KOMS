-- seed.sql - Enterprise Initial Test Data for KOMS (Mass Dragon Dojo)

USE koms;

-- Passwords are hashed using password_hash('password123', PASSWORD_BCRYPT)
-- 1. Grand Master / Super Admin
INSERT INTO users (id, first_name, last_name, email, password_hash, role, dob, gender, phone, status) VALUES 
(1, 'Grand Master', 'Higa', 'admin@gmail.com', '$2y$10$WWZq3fLA837RsyrJPECLgOQHIQ2vvt1KP2gAXlQ7vzUNQ1dPG43vW', 'super_admin', '1965-04-12', 'male', '+1-555-0100', 'active');

-- 2. Master / Dojo Admin (Mass Dragon Dojo)
INSERT INTO users (id, first_name, last_name, email, password_hash, role, dob, gender, phone, status) VALUES 
(2, 'John', 'Sensei', 'master@gmail.com', '$2y$10$WWZq3fLA837RsyrJPECLgOQHIQ2vvt1KP2gAXlQ7vzUNQ1dPG43vW', 'master', '1980-08-20', 'male', '+1-555-0101', 'active');

-- 3. Senior / Assistant Instructor
INSERT INTO users (id, first_name, last_name, email, password_hash, role, dob, gender, phone, status) VALUES 
(3, 'Mike', 'Senior', 'senior@gmail.com', '$2y$10$WWZq3fLA837RsyrJPECLgOQHIQ2vvt1KP2gAXlQ7vzUNQ1dPG43vW', 'senior', '1995-11-05', 'male', '+1-555-0102', 'active');

-- 4. Student (Adult) - Primary demo student
INSERT INTO users (id, first_name, last_name, email, password_hash, role, dob, gender, phone, status) VALUES 
(4, 'Jane', 'Doe', 'student@gmail.com', '$2y$10$WWZq3fLA837RsyrJPECLgOQHIQ2vvt1KP2gAXlQ7vzUNQ1dPG43vW', 'student', '2001-03-15', 'female', '+1-555-0103', 'active');

-- 5. Student (Minor < 18 for DPDP VPC Testing)
INSERT INTO users (id, first_name, last_name, email, password_hash, role, dob, gender, phone, status) VALUES 
(5, 'Kenji', 'Sato', 'minor@gmail.com', '$2y$10$WWZq3fLA837RsyrJPECLgOQHIQ2vvt1KP2gAXlQ7vzUNQ1dPG43vW', 'student', '2012-06-10', 'male', '+1-555-0104', 'active');

-- 6. Additional competitors for tournament bracket generation
INSERT INTO users (id, first_name, last_name, email, password_hash, role, dob, gender, phone, status) VALUES 
(6, 'Ryu', 'Hoshi', 'ryu@gmail.com', '$2y$10$WWZq3fLA837RsyrJPECLgOQHIQ2vvt1KP2gAXlQ7vzUNQ1dPG43vW', 'student', '1999-07-21', 'male', '+1-555-0105', 'active'),
(7, 'Ken', 'Masters', 'ken@gmail.com', '$2y$10$WWZq3fLA837RsyrJPECLgOQHIQ2vvt1KP2gAXlQ7vzUNQ1dPG43vW', 'student', '1999-02-14', 'male', '+1-555-0106', 'active'),
(8, 'Chun', 'Li', 'chunli@gmail.com', '$2y$10$WWZq3fLA837RsyrJPECLgOQHIQ2vvt1KP2gAXlQ7vzUNQ1dPG43vW', 'student', '2000-03-01', 'female', '+1-555-0107', 'active');

-- Seed DPDP Student Profiles
INSERT INTO student_profiles (user_id, is_minor, parent_name, parent_contact, parent_email, parent_consent_status, parent_consent_method, parent_consent_artifact, parent_consent_timestamp) VALUES 
(4, FALSE, NULL, NULL, NULL, 'not_applicable', NULL, NULL, NULL),
(5, TRUE, 'Akira Sato (Father)', '+1-555-0199', 'akira.sato@example.com', 'verified', 'aadhaar_otp', 'VPC-AUTH-984214-SHA256', NOW());

-- Seed Dojos (Mass Dragon Dojo & Central Dojo)
INSERT INTO dojos (id, name, master_id, location, contact_number, email, training_days, training_timings, description, status) VALUES
(1, 'Mass Dragon Dojo', 2, 'Okinawa Traditional Arts Center, Suite 4', '+1-555-0101', 'massdragon@gmail.com', 'Mon, Wed, Fri', '5:00 PM - 8:00 PM', 'Home of traditional Shorin Ryu Karate, teaching discipline, kata excellence, and self-defense.', 'approved'),
(2, 'Okinawa Shorin Ryu Central', 1, 'Naha City Budo Hall, Okinawa', '+1-555-0100', 'okinawa@gmail.com', 'Tue, Thu, Sat', '6:00 PM - 9:00 PM', 'Headquarters dojo overseen by Grand Master Higa.', 'approved');

-- Seed Student Memberships
INSERT INTO dojo_memberships (student_id, dojo_id, status, joined_at) VALUES
(4, 1, 'approved', '2024-01-15 10:00:00'),
(5, 1, 'approved', '2024-03-01 11:30:00'),
(6, 1, 'approved', '2024-02-10 09:00:00'),
(7, 2, 'approved', '2024-01-20 14:00:00'),
(8, 2, 'approved', '2024-02-05 16:00:00');

-- Seed SCD Type 2 Temporal Fee Structures
INSERT INTO fee_structures (id, dojo_id, fee_name, amount, frequency, effective_from, valid_from, valid_to, status) VALUES
(1, 1, 'Standard Monthly Dojo Fee', 75.00, 'monthly', '2024-01-01', '2024-01-01 00:00:00', NULL, 'active'),
(2, 1, 'Annual Federation Affiliation', 120.00, 'yearly', '2024-01-01', '2024-01-01 00:00:00', NULL, 'active');

-- Seed Fee Records
INSERT INTO fee_records (id, student_id, fee_structure_id, billing_month, amount_due, due_date, status) VALUES
(1, 4, 1, '2026-09-01', 75.00, '2026-09-15', 'pending'),
(2, 5, 1, '2026-09-01', 75.00, '2026-09-15', 'paid');

-- Seed Payment Ledger
INSERT INTO payments (fee_record_id, student_id, amount, payment_method, transaction_ref, payment_date, recorded_by, remarks) VALUES
(2, 5, 75.00, 'Credit Card', 'TXN-9021481', '2026-09-02', 2, 'September tuition received with verifiable parental billing.');

-- Seed Shorin Ryu Belt Grading Progression Ledger
INSERT INTO grading_history (student_id, dojo_id, previous_belt, new_belt, exam_date, grade, instructor_id, remarks) VALUES
(4, 1, 'White', 'Yellow (9th Kyu)', '2024-04-10', 'Pass with Honors', 2, 'Demonstrated Fukyugata Ichi with strong stances.'),
(4, 1, 'Yellow', 'Orange (8th Kyu)', '2024-08-15', 'A', 2, 'Demonstrated Fukyugata Ni and Kihon drills.'),
(4, 1, 'Orange', 'Green (6th Kyu)', '2025-02-20', 'A+', 2, 'Demonstrated Pinan Shodan & Nidan.'),
(4, 1, 'Green', 'Blue (4th Kyu)', '2025-09-10', 'Pass', 2, 'Demonstrated Pinan Sandan & Yondan with excellent kiai.'),
(4, 1, 'Blue', 'Purple (3rd Kyu)', '2026-03-12', 'Pass', 2, 'Demonstrated Pinan Godan and Naihanchi Shodan.');

-- Seed Tournaments & Algorithmic Elimination Candidates
INSERT INTO tournaments (id, name, description, event_date, venue, registration_deadline, status, target_type, created_by) VALUES
(1, 'All-Valley Shorin Ryu Championship', 'Annual regional championship featuring Kata and Kumite divisions across all rank categories.', '2026-10-25', 'Grand Arena Hall, Los Angeles', '2026-10-15', 'registration_open', 'all', 1);

-- Seed Tournament Entries
INSERT INTO tournament_registrations (tournament_id, student_id, dojo_id, status, reviewed_by) VALUES
(1, 4, 1, 'selected', 2),
(1, 5, 1, 'selected', 2),
(1, 6, 1, 'selected', 2),
(1, 7, 2, 'selected', 1),
(1, 8, 2, 'selected', 1);

-- Seed Announcements
INSERT INTO announcements (title, content, level, dojo_id, publish_date, created_by) VALUES
('Welcome to the KOMS Central Organization', 'The Karate Organization Management System has officially integrated Mass Dragon Dojo and all affiliated centers. All registrations, syllabus tracking, and tournament brackets are now managed through this unified portal.', 'global', NULL, '2026-09-01', 1),
('Mass Dragon Belt Testing Date Announced', 'Next Kyu grading exam will be held at Mass Dragon Dojo on Friday 6:00 PM. Review your Fukyugata, Pinan, and Naihanchi katas.', 'dojo', 1, '2026-09-05', 2);

-- Seed Finite State Machine Permitted Transitions
INSERT INTO state_transitions (entity_type, from_state, to_state, allowed_roles, description) VALUES
('dojo_membership', 'pending', 'parent_verification', 'system', 'Minors must complete parental consent before approval'),
('dojo_membership', 'parent_verification', 'approved', 'master', 'Master can approve once consent is verified'),
('dojo_membership', 'pending', 'approved', 'master', 'Direct approval for adult students'),
('dojo_membership', 'pending', 'rejected', 'master', 'Master rejects student application'),
('dojo', 'pending', 'approved', 'super_admin', 'Grand Master approves dojo affiliation'),
('dojo', 'pending', 'rejected', 'super_admin', 'Grand Master rejects dojo affiliation'),
('attendance_session', 'open', 'locked', 'master', 'Master seals session mathematically');
