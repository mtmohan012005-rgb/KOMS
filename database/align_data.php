<?php
// database/align_data.php - Aligns seed data with UI reference screenshots
require_once __DIR__ . '/../config/database.php';

try {
    // 1. Ensure Master Thirukailash exists
    $password_hash = password_hash('password123', PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'master@gmail.com'");
    $stmt->execute();
    $masterId = $stmt->fetchColumn();

    if ($masterId) {
        $pdo->prepare("UPDATE users SET first_name = 'R.N.', last_name = 'Thirukailash', role = 'master', status = 'active' WHERE id = ?")
            ->execute([$masterId]);
    } else {
        $pdo->prepare("INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, status) VALUES ('master.koms', 'R.N.', 'Thirukailash', 'master@gmail.com', ?, 'master', 'active')")
            ->execute([$password_hash]);
        $masterId = $pdo->lastInsertId();
    }

    // 2. Ensure Dojo 1 is "Main Dojo" in Chennai belonging to Master Thirukailash
    $stmt = $pdo->prepare("SELECT id FROM dojos WHERE id = 1");
    $stmt->execute();
    if ($stmt->fetchColumn()) {
        $pdo->prepare("UPDATE dojos SET name = 'Main Dojo', location = 'Chennai', master_id = ?, training_days = 'Tuesday, Thursday, Saturday', training_timings = '06:00 PM - 07:30 PM (Tue, Thu), 05:00 PM - 06:30 PM (Sat)', status = 'approved' WHERE id = 1")
            ->execute([$masterId]);
    } else {
        $pdo->prepare("INSERT INTO dojos (id, name, master_id, location, training_days, training_timings, status) VALUES (1, 'Main Dojo', ?, 'Chennai', 'Tuesday, Thursday, Saturday', '06:00 PM - 07:30 PM (Tue, Thu), 05:00 PM - 06:30 PM (Sat)', 'approved')")
            ->execute([$masterId]);
    }

    // 3. Ensure Master B exists for Dojo 2 (Cross-Dojo Isolation Test)
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'master_b@gmail.com'");
    $stmt->execute();
    $masterBId = $stmt->fetchColumn();
    if (!$masterBId) {
        $pdo->prepare("INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, status) VALUES ('master_b.koms', 'Master', 'Kenji', 'master_b@gmail.com', ?, 'master', 'active')")
            ->execute([$password_hash]);
        $masterBId = $pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("SELECT id FROM dojos WHERE id = 2");
    $stmt->execute();
    if ($stmt->fetchColumn()) {
        $pdo->prepare("UPDATE dojos SET name = 'Okinawa Central Dojo', location = 'Naha City', master_id = ?, status = 'approved' WHERE id = 2")
            ->execute([$masterBId]);
    } else {
        $pdo->prepare("INSERT INTO dojos (id, name, master_id, location, status) VALUES (2, 'Okinawa Central Dojo', ?, 'Naha City', 'approved')")
            ->execute([$masterBId]);
    }

    // 4. Ensure Student Sai Rohan exists with exact reference data
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'sairohan2012@koms.local' OR member_id = 'sairohan2012.koms'");
    $stmt->execute();
    $saiId = $stmt->fetchColumn();
    if ($saiId) {
        $pdo->prepare("UPDATE users SET 
            first_name = 'Sai', 
            last_name = 'Rohan L', 
            email = 'sairohan2012@koms.local', 
            member_id = 'sairohan2012.koms',
            password_hash = ?,
            dob = '2012-10-20', 
            gender = 'male', 
            blood_group = 'A1+ve', 
            father_name = 'Lingadhurai. S', 
            mother_name = 'Patturani. L', 
            phone = '8939319656', 
            alternate_phone = '9841882666', 
            address = 'J.K. builders 2nd floor, Rangangar 1st main, Old Perungalathur, Chennai.', 
            date_of_joining = '2026-08-01', 
            role = 'student', 
            status = 'active' 
            WHERE id = ?")->execute([$password_hash, $saiId]);
    } else {
        $pdo->prepare("INSERT INTO users (member_id, first_name, last_name, email, password_hash, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, role, status) 
            VALUES ('sairohan2012.koms', 'Sai', 'Rohan L', 'sairohan2012@koms.local', ?, '2012-10-20', 'male', 'A1+ve', 'Lingadhurai. S', 'Patturani. L', '8939319656', '9841882666', 'J.K. builders 2nd floor, Rangangar 1st main, Old Perungalathur, Chennai.', '2026-08-01', 'student', 'active')")
            ->execute([$password_hash]);
        $saiId = $pdo->lastInsertId();
    }

    // Also link student@gmail.com to same password
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'student@gmail.com'")->execute([$password_hash]);

    // Ensure Sai Rohan membership in Dojo 1
    $pdo->prepare("DELETE FROM dojo_memberships WHERE student_id = ? AND dojo_id = 1")->execute([$saiId]);
    $pdo->prepare("INSERT INTO dojo_memberships (student_id, dojo_id, status, joined_at) VALUES (?, 1, 'approved', '2026-08-01 10:00:00')")
        ->execute([$saiId]);

    // 5. Ensure Pending Student Requests exist: K. Arjun, S. Divya, M. Karthik
    $pendingStudents = [
        ['first_name' => 'K.', 'last_name' => 'Arjun', 'email' => 'arjun@koms.local', 'member_id' => 'arjun2013.koms', 'dob' => '2013-05-14'],
        ['first_name' => 'S.', 'last_name' => 'Divya', 'email' => 'divya@koms.local', 'member_id' => 'divya2014.koms', 'dob' => '2014-08-22'],
        ['first_name' => 'M.', 'last_name' => 'Karthik', 'email' => 'karthik@koms.local', 'member_id' => 'karthik2013.koms', 'dob' => '2013-11-10']
    ];

    foreach ($pendingStudents as $p) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$p['email']]);
        $pId = $stmt->fetchColumn();
        if (!$pId) {
            $pdo->prepare("INSERT INTO users (member_id, first_name, last_name, email, password_hash, dob, role, status) VALUES (?, ?, ?, ?, ?, ?, 'student', 'active')")
                ->execute([$p['member_id'], $p['first_name'], $p['last_name'], $p['email'], $password_hash, $p['dob']]);
            $pId = $pdo->lastInsertId();
        }
        // Link as pending request in Dojo 1
        $pdo->prepare("DELETE FROM dojo_memberships WHERE student_id = ? AND dojo_id = 1")->execute([$pId]);
        $pdo->prepare("INSERT INTO dojo_memberships (student_id, dojo_id, status, created_at) VALUES (?, 1, 'pending', NOW())")
            ->execute([$pId]);
    }

    // 6. Ensure Registered Students in Dojo 1 (matching reference table: L. Sai Rohan, K. Pavithra, M. Karthik, R. Deepika)
    $roster = [
        ['first_name' => 'K.', 'last_name' => 'Pavithra', 'email' => 'pavithra@koms.local', 'member_id' => 'pavithra.koms', 'belt' => 'Yellow Belt'],
        ['first_name' => 'R.', 'last_name' => 'Deepika', 'email' => 'deepika@koms.local', 'member_id' => 'deepika.koms', 'belt' => 'Green Belt']
    ];
    foreach ($roster as $r) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$r['email']]);
        $rId = $stmt->fetchColumn();
        if (!$rId) {
            $pdo->prepare("INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, 'student', 'active')")
                ->execute([$r['member_id'], $r['first_name'], $r['last_name'], $r['email'], $password_hash]);
            $rId = $pdo->lastInsertId();
        }
        $pdo->prepare("DELETE FROM dojo_memberships WHERE student_id = ? AND dojo_id = 1")->execute([$rId]);
        $pdo->prepare("INSERT INTO dojo_memberships (student_id, dojo_id, status, joined_at) VALUES (?, 1, 'approved', '2026-08-01 10:00:00')")
            ->execute([$rId]);
        
        // Belt record
        $stmt = $pdo->prepare("SELECT id FROM grading_history WHERE student_id = ? AND new_belt = ?");
        $stmt->execute([$rId, $r['belt']]);
        if (!$stmt->fetchColumn()) {
            $pdo->prepare("INSERT INTO grading_history (student_id, dojo_id, previous_belt, new_belt, exam_date, grade, instructor_id) VALUES (?, 1, 'White Belt', ?, '2026-08-10', 'A', ?)")
                ->execute([$rId, $r['belt'], $masterId]);
        }
    }

    // Belt record for Sai Rohan: White Belt
    $pdo->prepare("DELETE FROM grading_history WHERE student_id = ?")->execute([$saiId]);
    $pdo->prepare("INSERT INTO grading_history (student_id, dojo_id, previous_belt, new_belt, exam_date, grade, instructor_id) VALUES (?, 1, 'White Belt', 'White Belt', '2026-08-01', 'A', ?)")
        ->execute([$saiId, $masterId]);

    // 7. Ensure Fee Structure for Dojo 1
    $stmt = $pdo->prepare("SELECT id FROM fee_structures WHERE dojo_id = 1 AND status = 'active'");
    $stmt->execute();
    $feeStructId = $stmt->fetchColumn();
    if (!$feeStructId) {
        $pdo->prepare("INSERT INTO fee_structures (dojo_id, fee_name, amount, frequency, effective_from, status) VALUES (1, 'Monthly Training Fee', 3000.00, 'monthly', '2026-01-01', 'active')")
            ->execute();
        $feeStructId = $pdo->lastInsertId();
    }

    // Ensure Fee Records and Payments for Sai Rohan (₹2,000 paid / ₹3,000 due, pending ₹1,000)
    $pdo->prepare("DELETE FROM payments WHERE student_id = ?")->execute([$saiId]);
    $pdo->prepare("DELETE FROM fee_records WHERE student_id = ?")->execute([$saiId]);
    $pdo->prepare("INSERT INTO fee_records (student_id, fee_structure_id, billing_month, amount_due, due_date, status) VALUES (?, ?, '2026-08-01', 3000.00, '2026-08-10', 'partially_paid')")
        ->execute([$saiId, $feeStructId]);
    $feeRecId = $pdo->lastInsertId();
    
    $pdo->prepare("INSERT INTO payments (fee_record_id, student_id, amount, payment_method, transaction_ref, payment_date, recorded_by) VALUES (?, ?, 2000.00, 'UPI', 'TXN-KOMS-001', '2026-08-11', ?)")
        ->execute([$feeRecId, $saiId, $masterId]);

    // 8. Ensure Attendance session & exactly 12 present out of 16 for Sai Rohan (75%)
    $pdo->prepare("DELETE FROM attendance_entries WHERE student_id = ?")->execute([$saiId]);
    $pdo->prepare("INSERT INTO attendance_sessions (dojo_id, instructor_id, session_date, scheduled_day, start_time, end_time, created_by) VALUES (1, ?, '2026-08-12', 'Wednesday', '18:00:00', '19:30:00', ?)")
        ->execute([$masterId, $masterId]);
    $sessId = $pdo->lastInsertId();

    for ($i = 1; $i <= 16; $i++) {
        $status = ($i <= 12) ? 'present' : 'absent';
        $pdo->prepare("INSERT INTO attendance_entries (session_id, student_id, status, marked_by, marked_at) VALUES (?, ?, ?, ?, NOW())")
            ->execute([$sessId, $saiId, $status, $masterId]);
    }

    // 9. Ensure Achievements for Sai Rohan (Count: 2)
    $pdo->prepare("DELETE FROM achievements WHERE student_id = ?")->execute([$saiId]);
    $pdo->prepare("INSERT INTO achievements (student_id, title, description, competition_event, position_result, achievement_date, added_by) VALUES 
        (?, 'Gold Medal - District Kata Championship', 'First place in Junior Kata division', 'Tamil Nadu State Karate Championship', '1st Place', '2026-07-20', ?),
        (?, 'Silver Medal - Kumite Sparring', 'Runner up in under-14 Kumite', 'Chennai Open Martial Arts Meet', '2nd Place', '2026-06-15', ?)")
        ->execute([$saiId, $masterId, $saiId, $masterId]);

    // 10. Ensure Certificates table & Certificates for Sai Rohan (Count: 1)
    $pdo->exec("CREATE TABLE IF NOT EXISTS certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        dojo_id INT NOT NULL,
        certificate_type VARCHAR(100) NOT NULL,
        title VARCHAR(180) NOT NULL,
        certificate_number VARCHAR(100) NOT NULL,
        issue_date DATE NOT NULL,
        notes TEXT NULL,
        issued_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_cert_student (student_id),
        INDEX idx_cert_dojo (dojo_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->prepare("DELETE FROM certificates WHERE student_id = ?")->execute([$saiId]);
    $pdo->prepare("INSERT INTO certificates (student_id, dojo_id, certificate_type, title, certificate_number, issue_date, notes, issued_by) VALUES 
        (?, 1, 'Participation Certificate', 'National Martial Arts Camp 2026', 'CERT-2026-089', '2026-08-09', 'Completed 3-day rigorous Okinawan Karate immersion camp', ?)")
        ->execute([$saiId, $masterId]);

    // 11. Ensure Activity Logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(50) NOT NULL,
        module VARCHAR(50) NOT NULL,
        record_id INT,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $logs = [
        [$masterId, 'NEW_REQUEST', 'dojo', $saiId, 'New student registration request from K. Arjun'],
        [$masterId, 'ATTENDANCE_MARKED', 'attendance', $saiId, 'Attendance marked for L. Sai Rohan'],
        [$masterId, 'FEE_PAYMENT', 'fees', $saiId, 'Fee payment received from M. Karthik (₹2,000)'],
        [$masterId, 'BELT_UPDATED', 'grading', $saiId, 'Belt updated for L. Sai Rohan to White Belt'],
        [$masterId, 'ACHIEVEMENT_ADDED', 'achievements', $saiId, 'Achievement added for R. Deepika (Gold Medal)'],
        [$masterId, 'CERTIFICATE_ISSUED', 'certificates', $saiId, 'Certificate added for S. Vignesh (Participation Certificate)']
    ];
    foreach ($logs as $l) {
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, module, record_id, description) VALUES (?, ?, ?, ?, ?)")
            ->execute($l);
    }

    echo "KOMS Data alignment successfully completed.\n";

} catch (Throwable $e) {
    echo "Alignment Error: " . $e->getMessage() . "\n";
}
