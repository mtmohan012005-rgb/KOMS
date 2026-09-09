-- seed.sql - Initial Test Data

USE koms;

-- Passwords are hashed using password_hash('password123', PASSWORD_DEFAULT)
-- Default Super Admin
INSERT INTO users (first_name, last_name, email, password_hash, role, status) VALUES 
('Super', 'Admin', 'admin@koms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'active');

-- Test Master
INSERT INTO users (first_name, last_name, email, password_hash, role, status) VALUES 
('John', 'Sensei', 'master@koms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'master', 'active');

-- Test Senior
INSERT INTO users (first_name, last_name, email, password_hash, role, status) VALUES 
('Mike', 'Senior', 'senior@koms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'senior', 'active');

-- Test Student
INSERT INTO users (first_name, last_name, email, password_hash, role, status) VALUES 
('Jane', 'Doe', 'student@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 'active');

-- Seed a Dojo
INSERT INTO dojos (name, master_id, location, status) VALUES
('Cobra Kai Dojo', 2, 'Reseda, California', 'approved');

-- Seed Student Membership
INSERT INTO dojo_memberships (student_id, dojo_id, status) VALUES
(4, 1, 'approved');
