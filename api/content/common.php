<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/api_auth.php';

handle_api_cors();

try {
    // 1. Fetch Common UI dynamic sections
    $contentMap = [];
    try {
        $stmt = $pdo->query("SELECT section_key, title, subtitle, content_body, metadata FROM common_ui_content WHERE status = 'published' ORDER BY display_order ASC");
        while ($row = $stmt->fetch()) {
            $contentMap[$row['section_key']] = [
                'title' => $row['title'],
                'subtitle' => $row['subtitle'],
                'body' => $row['content_body'],
                'metadata' => $row['metadata'] ? json_decode($row['metadata'], true) : null
            ];
        }
    } catch (Throwable $e) {
        // Fallback if table not yet migrated
    }

    // Default fallbacks matching Image 1
    $hero = $contentMap['hero'] ?? [
        'title' => 'Explore the Karate Tradition',
        'subtitle' => 'Learn about our history, philosophy, masters and the path to black belt.',
        'body' => "More than a Martial Art...\nIt's a Way of Life."
    ];

    $tradition = $contentMap['tradition'] ?? [
        'title' => 'Our Tradition',
        'subtitle' => 'Discover the rich history, philosophy and values of Karate and Kobudo.',
        'body' => 'Matsubayashi Shorin-Ryu Karate-Do (少林流空手道) preserves authentic Okinawan self-defense, foundational katas, and mental discipline.'
    ];

    $mastersContent = $contentMap['masters'] ?? [
        'title' => 'Our Masters',
        'subtitle' => 'Meet our respected instructors and learn from their experience and guidance.',
        'body' => 'Our Senseis hold internationally certified Dan ranks with decades of devoted training and mentoring.'
    ];

    $trainingLevels = $contentMap['training_levels'] ?? [
        'title' => 'Training Levels',
        'subtitle' => 'From beginner to advanced — find the right path for your growth.',
        'body' => 'White, Yellow, Orange, Green, Blue, Brown, and Black Belts.'
    ];

    $eventsContent = $contentMap['events'] ?? [
        'title' => 'Upcoming Events',
        'subtitle' => 'Stay updated with competitions, seminars, camps and special activities.',
        'body' => 'Train • Compete • Grow'
    ];

    $galleryContent = $contentMap['gallery'] ?? [
        'title' => 'Photo Gallery',
        'subtitle' => 'Relive our moments — training sessions, events, awards and more.',
        'body' => 'Moments captured across our training halls and tournaments.'
    ];

    // 2. Fetch approved public dojos
    $dojos = [];
    try {
        $dojoStmt = $pdo->query("SELECT d.id, d.name, d.location, d.training_days, d.training_timings,
                                        CONCAT(u.first_name, ' ', u.last_name) AS master_name,
                                        u.email AS master_email
                                 FROM dojos d
                                 LEFT JOIN users u ON d.master_id = u.id
                                 WHERE d.status = 'approved'
                                 ORDER BY d.id ASC");
        $dojos = $dojoStmt->fetchAll();
    } catch (Throwable $e) {}

    if (empty($dojos)) {
        $dojos = [
            ['id' => 1, 'name' => 'Ranga Nagar Dojo', 'location' => 'Ranga Nagar, Chennai', 'master_name' => 'S. Senthil Kumar', 'training_days' => 'Tue, Thu, Sat', 'training_timings' => '6:00 PM - 8:00 PM'],
            ['id' => 2, 'name' => 'Old Perungalathur Dojo', 'location' => 'Old Perungalathur, Chennai', 'master_name' => 'R. Prakash', 'training_days' => 'Mon, Wed, Fri', 'training_timings' => '5:30 PM - 7:30 PM'],
            ['id' => 3, 'name' => 'Tambaram Dojo', 'location' => 'Tambaram, Chennai', 'master_name' => 'V. Arul', 'training_days' => 'Mon - Sat', 'training_timings' => '6:00 PM - 8:00 PM'],
            ['id' => 4, 'name' => 'Velachery Dojo', 'location' => 'Velachery, Chennai', 'master_name' => 'K. Mani', 'training_days' => 'Saturday & Sunday', 'training_timings' => '7:00 AM - 9:30 AM'],
            ['id' => 5, 'name' => 'Anna Nagar Dojo', 'location' => 'Anna Nagar, Chennai', 'master_name' => 'M. Rajesh', 'training_days' => 'Tue, Thu, Sat', 'training_timings' => '6:30 PM - 8:30 PM']
        ];
    }

    // 3. Belt Syllabus structure
    $belts = [
        ['level' => 'Beginner', 'belt' => 'White Belt', 'kyu' => '6th Kyu', 'color_hex' => '#FFFFFF'],
        ['level' => 'Beginner', 'belt' => 'Yellow Belt', 'kyu' => '5th Kyu', 'color_hex' => '#FBBF24'],
        ['level' => 'Intermediate', 'belt' => 'Orange Belt', 'kyu' => '4th Kyu', 'color_hex' => '#FB923C'],
        ['level' => 'Intermediate', 'belt' => 'Green Belt', 'kyu' => '3rd Kyu', 'color_hex' => '#22C55E'],
        ['level' => 'Advanced', 'belt' => 'Blue Belt', 'kyu' => '2nd Kyu', 'color_hex' => '#3B82F6'],
        ['level' => 'Advanced', 'belt' => 'Brown Belt', 'kyu' => '1st Kyu', 'color_hex' => '#92400E'],
        ['level' => 'Black Belt', 'belt' => 'Black Belt', 'kyu' => '1st - 10th Dan', 'color_hex' => '#18181B']
    ];

    // 4. Upcoming Events
    $events = [
        ['id' => 1, 'title' => 'Karate Championship 2026', 'date' => '25 Oct 2026', 'venue' => 'Jawaharlal Nehru Indoor Stadium, Chennai', 'category' => 'Tournament'],
        ['id' => 2, 'title' => 'Kobudo Weaponry Seminar (Bo, Sai)', 'date' => '12 Nov 2026', 'venue' => 'Ranga Nagar Central Dojo', 'category' => 'Seminar'],
        ['id' => 3, 'title' => 'Annual Dan Grading Convocations', 'date' => '18 Dec 2026', 'venue' => 'Mass Dragon Headquarters', 'category' => 'Grading']
    ];

    send_api_response([
        'hero' => $hero,
        'tradition' => $tradition,
        'masters' => $mastersContent,
        'training_levels' => $trainingLevels,
        'events' => $eventsContent,
        'gallery' => $galleryContent,
        'dojos' => $dojos,
        'belts' => $belts,
        'upcoming_events' => $events
    ], "Common UI content retrieved successfully");

} catch (Throwable $e) {
    error_log("Common UI API Exception: " . $e->getMessage());
    send_api_error("Internal server error: " . $e->getMessage(), [], 500);
}
