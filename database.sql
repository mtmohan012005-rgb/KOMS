/* =========================================================
   MASS DRAGON DOJO / KOMS
   Master Account Database Setup
   File: database.sql

   IMPORTANT:
   KOMS already uses the `koms` database and the shared `users`
   table for Super Admin, Master, Senior and Student accounts.
   This file intentionally does NOT create a separate
   `mass_dragon_dojo` database or `masters` table, so the Master
   Portal remains connected to the same KOMS database.
   ========================================================= */

USE koms;

/* Make sure the KOMS member_id field exists. */
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS member_id VARCHAR(100) UNIQUE AFTER id;

/* ---------------------------------------------------------
   MASTER ACCOUNT

   Login User ID : master.rubeshwaran2004.koms
   Login Email   : rubeshwaran.t@koms.local
   Password      : 123456

   The password is stored as a PHP password_hash() value.
   --------------------------------------------------------- */

INSERT INTO users (
    member_id,
    first_name,
    last_name,
    email,
    password_hash,
    role,
    dob,
    gender,
    phone,
    address,
    status
)
SELECT
    'master.rubeshwaran2004.koms',
    'Rubeshwaran',
    'T',
    'rubeshwaran.t@koms.local',
    '$2y$12$UzZDUih0n28Z31WbelXz7OIcFJKfw3J24APqb7jf4iCb2udUFy0Yi',
    'master',
    '2004-07-04',
    'male',
    '8838343872',
    'Old Perungalathur',
    'active'
WHERE NOT EXISTS (
    SELECT 1
    FROM users
    WHERE email = 'rubeshwaran.t@koms.local'
       OR member_id = 'master.rubeshwaran2004.koms'
);

/* ---------------------------------------------------------
   OPTIONAL MASTER PROFILE DETAILS

   These fields are maintained in student_profiles only for
   students, so Master-specific training details stay in the
   normal KOMS `users` / `dojos` structure.
   --------------------------------------------------------- */
