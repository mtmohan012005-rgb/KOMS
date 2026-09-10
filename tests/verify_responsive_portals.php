<?php
/**
 * Comprehensive Portal & Responsive Theme Verification Test Suite
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "========================================================================================\n";
echo "      COMPREHENSIVE PORTAL & RESPONSIVE DESIGN VERIFICATION (PHP & DOM CHECKS)          \n";
echo "========================================================================================\n\n";

$pass_count = 0;
$total_count = 0;

function assert_test($description, $condition, $details = '') {
    global $pass_count, $total_count;
    $total_count++;
    if ($condition) {
        $pass_count++;
        echo "✅ PASS: $description\n";
    } else {
        echo "❌ FAIL: $description" . ($details ? " ($details)" : "") . "\n";
    }
}

// 1. Check Theme Files
assert_test("Theme CSS file exists (assets/css/koms-portal-theme.css)", file_exists(__DIR__ . '/../assets/css/koms-portal-theme.css'));
assert_test("Theme JS file exists (assets/js/koms-portal.js)", file_exists(__DIR__ . '/../assets/js/koms-portal.js'));
assert_test("Brand crest asset exists (assets/images/shorin_ryu_crest.jpg)", file_exists(__DIR__ . '/../assets/images/shorin_ryu_crest.jpg'));
assert_test("Hero banner artwork exists (assets/images/mass_dragon_hero.jpg)", file_exists(__DIR__ . '/../assets/images/mass_dragon_hero.jpg'));

// 2. Check index.php Content & Mockup Matching Elements
$index_html = file_get_contents(__DIR__ . '/../index.php');
assert_test("index.php includes koms-portal-theme.css", strpos($index_html, 'assets/css/koms-portal-theme.css') !== false);
assert_test("index.php includes Shorin Ryu crest", strpos($index_html, 'shorin_ryu_crest.jpg') !== false);
assert_test("index.php includes 'Discipline. Strength. Internal Peace.' slogan", strpos($index_html, 'Discipline.') !== false && strpos($index_html, 'Internal Peace.') !== false);
assert_test("index.php includes 4 Pillar Badges (TRAIN, GROW, ACHIEVE, EXCEL)", strpos($index_html, 'TRAIN') !== false && strpos($index_html, 'GROW') !== false && strpos($index_html, 'ACHIEVE') !== false && strpos($index_html, 'EXCEL') !== false);
assert_test("index.php includes 'BUILDING STRONGER MINDS & BODIES' hero script", strpos($index_html, 'BUILDING STRONGER') !== false && strpos($index_html, 'MINDS &amp; BODIES') !== false);
assert_test("index.php includes mobile bottom navigation bar", strpos($index_html, 'koms-bottom-nav') !== false);
assert_test("index.php login drawer accepts User ID or Gmail", strpos($index_html, 'User ID or Gmail Address') !== false);
assert_test("index.php login drawer has DOB password guidance", strpos($index_html, 'DOB: DD.MM.YYYY for students') !== false);

// 3. Check student-portal.css & student/dashboard.php
$student_css = file_get_contents(__DIR__ . '/../assets/css/student-portal.css');
assert_test("student-portal.css uses dark martial obsidian palette (#07080a)", strpos($student_css, '#07080a') !== false);
assert_test("student-portal.css has radiant gold accents (#ffcc00)", strpos($student_css, '#ffcc00') !== false);
assert_test("student-portal.css has mobile bottom nav styles (.sp-bottom-nav)", strpos($student_css, 'sp-bottom-nav') !== false);

$student_dash = file_get_contents(__DIR__ . '/../student/dashboard.php');
assert_test("student/dashboard.php includes Shorin Ryu crest", strpos($student_dash, 'shorin_ryu_crest.jpg') !== false);
assert_test("student/dashboard.php includes 'Discipline. Strength. Internal Peace.' slogan", strpos($student_dash, 'Discipline.') !== false && strpos($student_dash, 'Internal Peace.') !== false);
assert_test("student/dashboard.php includes sp-bottom-nav for mobile phone", strpos($student_dash, 'sp-bottom-nav') !== false);
assert_test("student/dashboard.php includes 1-time password change notice banner", strpos($student_dash, '1-Time Password Change Notice') !== false);

// 4. Check master/index.php
$master_index = file_get_contents(__DIR__ . '/../master/index.php');
assert_test("master/index.php includes Shorin Ryu crest", strpos($master_index, 'shorin_ryu_crest.jpg') !== false);
assert_test("master/index.php includes 'Discipline. Strength. Internal Peace.' slogan", strpos($master_index, 'Discipline.') !== false && strpos($master_index, 'Internal Peace.') !== false);
assert_test("master/index.php includes Password Requests in sidebar nav", strpos($master_index, 'password_requests.php') !== false);
assert_test("master/index.php includes mobile bottom navigation bar", strpos($master_index, 'master-bottom-nav') !== false);

// 5. Check includes/header.php & includes/footer.php
$header_html = file_get_contents(__DIR__ . '/../includes/header.php');
assert_test("includes/header.php includes koms-portal-theme.css", strpos($header_html, 'assets/css/koms-portal-theme.css') !== false);
assert_test("includes/header.php includes Shorin Ryu crest", strpos($header_html, 'shorin_ryu_crest.jpg') !== false);
assert_test("includes/header.php has Password Requests badge query", strpos($header_html, 'password_reset_requests') !== false);

$footer_html = file_get_contents(__DIR__ . '/../includes/footer.php');
assert_test("includes/footer.php includes koms-bottom-nav", strpos($footer_html, 'koms-bottom-nav') !== false);
assert_test("includes/footer.php includes koms-portal.js", strpos($footer_html, 'assets/js/koms-portal.js') !== false);

echo "\n========================================================================================\n";
echo "SUMMARY: $pass_count / $total_count TESTS PASSED\n";
echo "========================================================================================\n";

if ($pass_count === $total_count) {
    echo "🎉 ALL RESPONSIVE PORTAL & MOCKUP THEME CHECKS PASSED PERFECTLY!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED!\n";
    exit(1);
}
