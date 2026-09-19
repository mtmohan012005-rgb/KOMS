<?php
// database/remove_all_students.php - Complete removal of all student records
require_once __DIR__ . '/../config/database.php';

echo "============================================================\n";
echo "REMOVING ALL STUDENT DETAILS & RECORDS FROM KOMS DATABASE\n";
echo "============================================================\n\n";

try {
    $pdo->beginTransaction();

    // 1. Delete all students from users table
    $delUsers = $pdo->query("DELETE FROM users WHERE role = 'student'");
    echo "Removed {$delUsers->rowCount()} student record(s) from users table.\n";

    // 2. Clean up all student-related operational tables
    $childTables = [
        'attendance_entries',
        'payments',
        'fee_records',
        'tournament_registrations',
        'grading_history',
        'achievements',
        'certificates',
        'dojo_memberships'
    ];

    foreach ($childTables as $table) {
        try {
            $delStmt = $pdo->query("DELETE FROM $table");
            $affected = $delStmt->rowCount();
            echo "Cleaned $table: $affected record(s) removed.\n";
        } catch (Throwable $e) {
            echo "Note on $table: " . $e->getMessage() . "\n";
        }
    }

    $pdo->commit();

    // 3. Verify counts
    $totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $totalMemberships = (int)$pdo->query("SELECT COUNT(*) FROM dojo_memberships")->fetchColumn();
    $totalAttendance = (int)$pdo->query("SELECT COUNT(*) FROM attendance_entries")->fetchColumn();
    $totalFees = (int)$pdo->query("SELECT COUNT(*) FROM fee_records")->fetchColumn();

    echo "\n=== VERIFICATION ===\n";
    echo "Total Students in DB: $totalStudents\n";
    echo "Total Memberships in DB: $totalMemberships\n";
    echo "Total Attendance Entries in DB: $totalAttendance\n";
    echo "Total Fee Records in DB: $totalFees\n";
    echo "============================================================\n";
    echo "ALL STUDENT DETAILS HAVE BEEN PERMANENTLY REMOVED.\n";
    echo "============================================================\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR during removal: " . $e->getMessage() . "\n";
}
