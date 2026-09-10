/* =========================================================
   MASS DRAGON DOJO / KOMS
   Student Account Seed Import
   Source: Karate_Personal_Information.xlsx
   =========================================================
   Default User ID: name + birth year + ".koms"
   Default Password: DOB exactly as stored in the sheet (DD.MM.YYYY)
   This is a ONE-TIME default password.
   `must_change_password = 1` marks the account for a password change.
   ========================================================= */

USE koms;

START TRANSACTION;

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS blood_group VARCHAR(30) NULL AFTER gender,
    ADD COLUMN IF NOT EXISTS father_name VARCHAR(150) NULL AFTER blood_group,
    ADD COLUMN IF NOT EXISTS mother_name VARCHAR(150) NULL AFTER father_name,
    ADD COLUMN IF NOT EXISTS alternate_phone VARCHAR(20) NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS date_of_joining DATE NULL AFTER alternate_phone,
    ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

SET @master_id := (
    SELECT id FROM users
    WHERE member_id = 'master.rubeshwaran2004.koms'
       OR email = 'rubeshwaran.t@koms.local'
    LIMIT 1
);

SET @dojo_id := (
    SELECT id FROM dojos
    WHERE master_id = @master_id
      AND status = 'active'
    LIMIT 1
);

/* Student data generated from the uploaded Karate_Personal_Information.xlsx. */

/* Şai Rohan L */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'sairohan2012.koms','Sai','Rohan L','sairohan2012@koms.local','$2y$12$KQH8QtgZAeMk1NsiRrq/fu5KeMAOSiX3L7I1xMB5nVMcQcaiSPIS2','student','2012-10-20','male','A-ve','Lingadhurai S.','Patturani L','8939319656','9841882666','J.K Builders, 2nd Floor, Ranganagar, 1st Main Road, old Perungalathur, Chennai-63','2026-08-01','active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='sairohan2012@koms.local' OR member_id='sairohan2012.koms');

/* D. Guhan */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'dguhan2015.koms','Guhan','','dguhan2015@koms.local','$2y$12$wEofuVkvsunFeY2jHOb/QuWI3NPn/eZgqQp1t1Inayaqn9xpZF2iq','student','2015-09-25','male','A+','Lingadhurai S.','Pattusari L.','8939319656','9841882666','J.K Builders, 2nd Floor, Ranganagar, 1st Main Road, old Perungalathur, Chennai','2026-08-02','active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='dguhan2015@koms.local' OR member_id='dguhan2015.koms');

/* Harshini.S */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'harshini2012.koms','Harshini','S','harshini2012@koms.local','$2y$12$8LysjQA0pyIqBbGF6OOmru/Scqbu1kkzMsjiJwamLO1Vutg9yigqa','student','2012-07-31',NULL,'O+','Sathish Kurman D.','Jayachithra.S','9994318107','9551581505','4/444, Govindhan Street, Ranga Nagar, Mudichur, Chennai-600048',NULL,'active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='harshini2012@koms.local' OR member_id='harshini2012.koms');

/* S. Varshini */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'svarshini2012.koms','Varshini','S','svarshini2012@koms.local','$2y$12$QDbRc/H.gBBPhlKgIZtpROg670Jn7yY8joc6qmqESqWxcZAxeaHJe','student','2012-10-30',NULL,'O+','D. Sathish Kumar','S. Jaya Chithra','9994318107','9551581505','4/444, Govindhan Street, Ranga Nagar, Mudichur, Chennai-600048','2026-06-16','active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='svarshini2012@koms.local' OR member_id='svarshini2012.koms');

/* G.P. Prathyuminan */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'gpprathyuminan2020.koms','Prathyuminan','G P','gpprathyuminan2020@koms.local','$2y$12$b9P361i6N4lt55zsVf0zYuYDVVB9Kp8LWwJG9xHk9/X7FmehU2pRa','student','2020-09-25',NULL,'B+','R. Prabu','N. Gayathri','9952978876','9677283219','No. 36/24A, CTO Colony 2nd Street, Lakshmipuram, West Tambaram, Chennai-600045, Chengalpattu District','2026-05-30','active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='gpprathyuminan2020@koms.local' OR member_id='gpprathyuminan2020.koms');

/* R. Harshitha */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'rharshitha2016.koms','Harshitha','R','rharshitha2016@koms.local','$2y$12$Tm18rYQllCpDoqzTaD4B3e6Xvd0sm5zp.fvgeIqj3aBflDswJh/eq','student','2016-08-04',NULL,'O','R. Rewikanth','M. Shanthi Charles Mary','9790897178','9840591792','No. 2, Arivu Street, MKB Nagar, New Perungalathur, Chennai-600063','2026-07-18','active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='rharshitha2016@koms.local' OR member_id='rharshitha2016.koms');

/* R. Darshitha */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'rdarshitha2016.koms','Darshitha','R','rdarshitha2016@koms.local','$2y$12$OJHOipAkLz13alymRwe9cOuf33qmTwwLciDrONFh4MYvIEy3irBz6','student','2016-08-04',NULL,'O+','R. Ravikanth','M. Shanthi Charles Mary','9790897178','9840591792','No. 2, Arivu Street, M.K.B. Nagar, New Perungalathur, Chennai-600063','2026-07-18','active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='rdarshitha2016@koms.local' OR member_id='rdarshitha2016.koms');

/* M.P. Niranjana Sri */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'mpniranjanasri2019.koms','Niranjana Sri','M P','mpniranjanasri2019@koms.local','$2y$12$JfPLuZ5ofgCv0c3rfpteweMPRHBMNOZrdP0w.93kAf2tIsRlhuba.','student','2019-10-30',NULL,'B Negative','B. Mathan','M. Praveena','9600103987','8056507676','No. 13, Velu Street, M.K.B Nagar, New Perungalathur, Chennai-600063','2026-07-04','active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='mpniranjanasri2019@koms.local' OR member_id='mpniranjanasri2019.koms');

/* M. Krish Charan */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'mkrishcharan2017.koms','Krish Charan','M','mkrishcharan2017@koms.local','$2y$12$kmEGvQ781zNwvbuzfKqRCerWI1BvQppx5QjcH6dPH7I22ZjM71Plq','student','2017-08-08',NULL,'B Positive','B. Mathan','M. Praveena','9600103987','8056507676','No. 13, Velu Street, M.K.B Nagar, New Perungalathur, Chennai-600063','2026-07-04','active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='mkrishcharan2017@koms.local' OR member_id='mkrishcharan2017.koms');

/* Thejasri A. */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'thejasri2019.koms','Thejasri','A','thejasri2019@koms.local','$2y$12$ehUTHEqMCFR1YuNAstvpyutCupFbPO3RVNgid1hKuz93klEm1J3Tu','student','2019-05-02',NULL,'A+','Ajith Kumar','A. Usha','7845033821','9566748689','No. 2, Arivu Street, Mahakavi Bharathiyar Nagar, New Perungalathur', '2026-07-04','active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='thejasri2019@koms.local' OR member_id='thejasri2019.koms');

/* Karunesh M. */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'karunesh2014.koms','Karunesh','M','karunesh2014@koms.local','$2y$12$PFm3kM71IlrO.fq1YgrmV.1NjCbROwNGf7Sc6K7WiK/Msa43gIm82','student','2014-08-20',NULL,'O+','Mohan T.','Komalavalli M.','9176223876','9380056410','6B, GE Properties, Muthusamy Cross Street, New Perungalathur, Chennai-600063','2026-08-01','active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='karunesh2014@koms.local' OR member_id='karunesh2014.koms');

/* V. Pragatheeshwaran */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'vpragatheeshwaran2016.koms','Pragatheeshwaran','V','vpragatheeshwaran2016@koms.local','$2y$12$WYT2e4u7Ij4BzdFo3AiEn.lUKngBCDIo7kYZ.3LpivpWfSgZsGs42','student','2016-02-25',NULL,'A+','K. Vinothkumar','K. Chitra','9677017473','9841748800','3/24, Ranganagar, Nehru Street, Old Perungalathur, Chennai-600063','2026-08-01','active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='vpragatheeshwaran2016@koms.local' OR member_id='vpragatheeshwaran2016.koms');

/* Advick A. */
INSERT INTO users (member_id, first_name, last_name, email, password_hash, role, dob, gender, blood_group, father_name, mother_name, phone, alternate_phone, address, date_of_joining, status, must_change_password)
SELECT 'advick2016.koms','Advick','A','advick2016@koms.local','$2y$12$5ZpOVrW6yyozDdiNZfOOcOdLX2FCcjNkLSrKu4F5JupehB6ZewCFW','student','2016-07-02',NULL,'A+','A.Arun','Anitha','7418737343','8148783837','No. 6 (St), Dhinagar, Old Perungalathur',NULL,'active',1
WHERE @master_id IS NOT NULL AND @dojo_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM users WHERE email='advick2016@koms.local' OR member_id='advick2016.koms');

/* Put every imported student into the active Master's dojo. */
INSERT INTO dojo_memberships (student_id, dojo_id, status, joined_at)
SELECT u.id, @dojo_id, 'approved', COALESCE(u.date_of_joining, CURRENT_TIMESTAMP)
FROM users u
WHERE u.role = 'student'
  AND u.member_id IN (
    'sairohan2012.koms','dguhan2015.koms','harshini2012.koms','svarshini2012.koms',
    'gpprathyuminan2020.koms','rharshitha2016.koms','rdarshitha2016.koms','mpniranjanasri2019.koms',
    'mkrishcharan2017.koms','thejasri2019.koms','karunesh2014.koms','vpragatheeshwaran2016.koms','advick2016.koms'
  )
  AND @dojo_id IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM dojo_memberships m
      WHERE m.student_id = u.id AND m.dojo_id = @dojo_id
  );

COMMIT;

/* Verification: 13 imported accounts and their Master's dojo membership. */
SELECT u.member_id, u.first_name, u.last_name, u.email, u.dob,
       u.blood_group, u.father_name, u.mother_name, u.phone,
       u.alternate_phone, u.date_of_joining, u.must_change_password,
       dm.dojo_id, dm.status AS membership_status
FROM users u
LEFT JOIN dojo_memberships dm
  ON dm.student_id = u.id AND dm.dojo_id = @dojo_id
WHERE u.member_id IN (
    'sairohan2012.koms','dguhan2015.koms','harshini2012.koms','svarshini2012.koms',
    'gpprathyuminan2020.koms','rharshitha2016.koms','rdarshitha2016.koms','mpniranjanasri2019.koms',
    'mkrishcharan2017.koms','thejasri2019.koms','karunesh2014.koms','vpragatheeshwaran2016.koms','advick2016.koms'
)
ORDER BY u.member_id;