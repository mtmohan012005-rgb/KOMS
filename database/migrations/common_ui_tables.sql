-- KOMS Common UI & Master Requests Migration

CREATE TABLE IF NOT EXISTS common_ui_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) NULL,
    content_body TEXT NULL,
    metadata JSON NULL,
    status ENUM('draft', 'published', 'hidden', 'archived') DEFAULT 'published',
    display_order INT DEFAULT 0,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section_key),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Default Common UI content if not exists
INSERT INTO common_ui_content (section_key, title, subtitle, content_body, status, display_order)
SELECT 'hero', 'Explore the Karate Tradition', 'Learn about our history, philosophy, masters and the path to black belt.', 'More than a Martial Art... It\'s a Way of Life.', 'published', 1
WHERE NOT EXISTS (SELECT 1 FROM common_ui_content WHERE section_key = 'hero');

INSERT INTO common_ui_content (section_key, title, subtitle, content_body, status, display_order)
SELECT 'tradition', 'Our Tradition', 'Discover the rich history, philosophy and values of Karate and Kobudo.', 'Rooted in Matsubayashi Shorin-Ryu Karate-Do, our lineage balances physical conditioning, kata mastery, and inner peace.', 'published', 2
WHERE NOT EXISTS (SELECT 1 FROM common_ui_content WHERE section_key = 'tradition');

INSERT INTO common_ui_content (section_key, title, subtitle, content_body, status, display_order)
SELECT 'masters', 'Our Masters', 'Meet our respected instructors and learn from their experience and guidance.', 'Certified Senseis bringing decades of authentic Okinawan martial heritage and student development.', 'published', 3
WHERE NOT EXISTS (SELECT 1 FROM common_ui_content WHERE section_key = 'masters');

INSERT INTO common_ui_content (section_key, title, subtitle, content_body, status, display_order)
SELECT 'training_levels', 'Training Levels', 'From beginner to advanced — find the right path for your growth.', 'Systematic progression through Kyu and Dan ranks: White, Yellow, Orange, Green, Blue, Brown, and Black Belts.', 'published', 4
WHERE NOT EXISTS (SELECT 1 FROM common_ui_content WHERE section_key = 'training_levels');

INSERT INTO common_ui_content (section_key, title, subtitle, content_body, status, display_order)
SELECT 'events', 'Upcoming Events', 'Stay updated with competitions, seminars, camps and special activities.', 'Inter-Dojo Championships, Kobudo Seminars, and Dan grading examinations scheduled across the region.', 'published', 5
WHERE NOT EXISTS (SELECT 1 FROM common_ui_content WHERE section_key = 'events');

INSERT INTO common_ui_content (section_key, title, subtitle, content_body, status, display_order)
SELECT 'gallery', 'Photo Gallery', 'Relive our moments — training sessions, events, awards and more.', 'Visual chronicle of our seminars, tournaments, black belt convocations, and daily dojo discipline.', 'published', 6
WHERE NOT EXISTS (SELECT 1 FROM common_ui_content WHERE section_key = 'gallery');
