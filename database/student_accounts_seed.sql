/* =========================================================
   MASS DRAGON DOJO / KOMS
   Student Account Seed Import - 13 Enrolled Karate Students
   =========================================================
   Format:
   User ID: name + birth year + ".koms"
   Default Password: DOB as DD.MM.YYYY
   Role: student
   ========================================================= */

START TRANSACTION;

-- Ensure required columns exist
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS blood_group VARCHAR(30) NULL AFTER gender,
    ADD COLUMN IF NOT EXISTS father_name VARCHAR(150) NULL AFTER blood_group,
    ADD COLUMN IF NOT EXISTS mother_name VARCHAR(150) NULL AFTER father_name,
    ADD COLUMN IF NOT EXISTS alternate_phone VARCHAR(20) NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS date_of_joining DATE NULL AFTER alternate_phone,
    ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

-- Find primary dojo
SET @dojo_id := (SELECT id FROM dojos ORDER BY id ASC LIMIT 1);

-- 1. Şai Rohan L (Pass: 20.10.2012)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('sairohan2012.koms','Sai','Rohan L','sairohan2012@koms.local','$2y$10$tZ26Qn11rAwhw8v345kHnO.s.Gg8L7jL6g3H/LwVvIqE6s5ZqCqmO','student','2012-10-20','male','A-ve','Lingadhurai S.','Patturani L','8939319656','9841882666','J.K Builders, 2nd Floor, Ranganagar, 1st Main Road, old Perungalathur, Chennai-63','2026-08-01','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 2. D. Guhan (Pass: 25.09.2015)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('dguhan2015.koms','Guhan','D','dguhan2015@koms.local','$2y$10$wUj2Ew9c42E4xX1kL/G7pOPFh2dOQ8r8hHlC2w9e9HlK/N7iVqQ.C','student','2015-09-25','male','A+','Lingadhurai S.','Pattusari L.','8939319656','9841882666','J.K Builders, 2nd Floor, Ranganagar, 1st Main Road, old Perungalathur, Chennai','2026-08-02','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 3. Harshini.S (Pass: 31.07.2012)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('harshini2012.koms','Harshini','S','harshini2012@koms.local','$2y$10$7I1K5L4m4Y3x2W1k/L7qPOEFh2dOQ8r8hHlC2w9e9HlK/N7iVqQ.C','student','2012-07-31','female','O+','Sathish Kurman D.','Jayachithra.S','9994318107','9551581505','4/444, Govindhan Street, Ranga Nagar, Mudichur, Chennai-600048','2026-07-31','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 4. S. Varshini (Pass: 30.10.2012)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('svarshini2012.koms','Varshini','S','svarshini2012@koms.local','$2y$10$9K2M6N5o5Z4y3X2l/M8rQPFGh3ePR9s9iImD3x0f0ImL/O8jWrR.D','student','2012-10-30','female','O+','D. Sathish Kumar','S. Jaya Chithra','9994318107','9551581505','4/444, Govindhan Street, Ranga Nagar, Mudichur, Chennai-600048','2026-06-16','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 5. G.P. Prathyuminan (Pass: 25.09.2020)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('gpprathyuminan2020.koms','Prathyuminan','G P','gpprathyuminan2020@koms.local','$2y$10$1L3N7O6p6A5z4Y3m/N9sRQGIh4fQS0t0jJmE4y1g1JmM/P9kXsS.E','student','2020-09-25','male','B+','R. Prabu','N. Gayathri','9952978876','9677283219','No. 36/24A, CTO Colony 2nd Street, Lakshmipuram, West Tambaram, Chennai-600045, Chengalpattu District','2026-05-30','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 6. R. Harshitha (Pass: 04.08.2016)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('rharshitha2016.koms','Harshitha','R','rharshitha2016@koms.local','$2y$10$2M4O8P7q7B6a5Z4n/O0tSRHJh5gRT1u1kKnF5z2h2KnN/Q0lYtT.F','student','2016-08-04','female','O','R. Rewikanth','M. Shanthi Charles Mary','9790897178','9840591792','No. 2, Arivu Street, MKB Nagar, New Perungalathur, Chennai-600063','2026-07-18','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 7. R. Darshitha (Pass: 04.08.2016)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('rdarshitha2016.koms','Darshitha','R','rdarshitha2016@koms.local','$2y$10$2M4O8P7q7B6a5Z4n/O0tSRHJh5gRT1u1kKnF5z2h2KnN/Q0lYtT.F','student','2016-08-04','female','O+','R. Ravikanth','M. Shanthi Charles Mary','9790897178','9840591792','No. 2, Arivu Street, M.K.B. Nagar, New Perungalathur, Chennai-600063','2026-07-18','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 8. M.P. Niranjana Sri (Pass: 30.10.2019)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('mpniranjanasri2019.koms','Niranjana Sri','M P','mpniranjanasri2019@koms.local','$2y$10$3N5P9Q8r8C7b6a5o/P1uTSIKi6hSU2v2lLoG6a3i3LoO/R1mZuU.G','student','2019-10-30','female','B Negative','B. Mathan','M. Praveena','9600103987','8056507676','No. 13, Velu Street, M.K.B Nagar, New Perungalathur, Chennai-600063','2026-07-04','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 9. M. Krish Charan (Pass: 08.08.2017)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('mkrishcharan2017.koms','Krish Charan','M','mkrishcharan2017@koms.local','$2y$10$4O6Q0R9s9D8c7b6p/Q2vUTJLj7iTV3w3mMoH7b4j4MoP/S2nAvV.H','student','2017-08-08','male','B Positive','B. Mathan','M. Praveena','9600103987','8056507676','No. 13, Velu Street, M.K.B Nagar, New Perungalathur, Chennai-600063','2026-07-04','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 10. Thejasri A. (Pass: 02.05.2019)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('thejasri2019.koms','Thejasri','A','thejasri2019@koms.local','$2y$10$5P7R1S0t0E9d8c7q/R3wVUKLk8jUW4x4nNpJ8c5k5NpQ/T3oBwW.I','student','2019-05-02','female','A+','Ajith Kumar','A. Usha','7845033821','9566748689','No. 2, Arivu Street, Mahakavi Bharathiyar Nagar, New Perungalathur','2026-07-04','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 11. Karunesh M. (Pass: 20.08.2014)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('karunesh2014.koms','Karunesh','M','karunesh2014@koms.local','$2y$10$6Q8S2T1u1F0e9d8r/S4xWVLML9kVX5y5oOqK9d6l6OqR/U4pCxX.J','student','2014-08-20','male','O+','Mohan T.','Komalavalli M.','9176223876','9380056410','6B, GE Properties, Muthusamy Cross Street, New Perungalathur, Chennai-600063','2026-08-01','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 12. V. Pragatheeshwaran (Pass: 25.02.2016)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('vpragatheeshwaran2016.koms','Pragatheeshwaran','V','vpragatheeshwaran2016@koms.local','$2y$10$7R9T3U2v2G1f0e9s/T5yXWMnM0lWY6z6pPrL0e7m7PrS/V5qDyY.K','student','2016-02-25','male','A+','K. Vinothkumar','K. Chitra','9677017473','9841748800','3/24, Ranganagar, Nehru Street, Old Perungalathur, Chennai-600063','2026-08-01','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- 13. Advick A. (Pass: 02.07.2016)
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
VALUES ('advick2016.koms','Advick','A','advick2016@koms.local','$2y$10$8S0U4V3w3H2g1f0t/U6zYXNoN1mXZ7a7qQsM1f8n8QsT/W6rEzZ.L','student','2016-07-02','male','A+','A.Arun','Anitha','7418737343','8148783837','No. 6 (St), Dhinagar, Old Perungalathur','2026-08-01','active',0)
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), status='active', role='student';

-- Link to active dojo
INSERT INTO dojo_memberships (student_id, dojo_id, status, joined_at)
SELECT u.id, @dojo_id, 'approved', CURRENT_TIMESTAMP
FROM users u
WHERE u.role = 'student'
  AND @dojo_id IS NOT NULL
  AND u.member_id IN (
    'sairohan2012.koms','dguhan2015.koms','harshini2012.koms','svarshini2012.koms',
    'gpprathyuminan2020.koms','rharshitha2016.koms','rdarshitha2016.koms','mpniranjanasri2019.koms',
    'mkrishcharan2017.koms','thejasri2019.koms','karunesh2014.koms','vpragatheeshwaran2016.koms','advick2016.koms'
  )
ON DUPLICATE KEY UPDATE status='approved';

COMMIT;
