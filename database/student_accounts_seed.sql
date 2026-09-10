/*
 * KOMS STUDENT DATA IMPORT
 * Source: Karate_Personal_Information(1).xlsx
 *
 * This file intentionally creates NO default/demo passwords.
 * Student records are imported only after a real Master/Dojo exists.
 * Login credentials must be created through the KOMS account workflow.
 *
 * IMPORTANT:
 * - Do not commit raw passwords to GitHub.
 * - Age is NOT stored from the spreadsheet; calculate age from dob in the UI.
 * - Minor/parental-consent verification must be completed by the dojo workflow.
 */

USE koms;

START TRANSACTION;

/* Add fields needed by the uploaded student information sheet. */
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS blood_group VARCHAR(30) NULL AFTER gender,
    ADD COLUMN IF NOT EXISTS father_name VARCHAR(150) NULL AFTER blood_group,
    ADD COLUMN IF NOT EXISTS mother_name VARCHAR(150) NULL AFTER father_name,
    ADD COLUMN IF NOT EXISTS alternate_phone VARCHAR(20) NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS date_of_joining DATE NULL AFTER alternate_phone,
    ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

/*
 * No INSERT statements are included here.
 * The 13 spreadsheet records must be imported through the KOMS admin workflow,
 * where a real dojo is selected and credentials are assigned securely.
 */

COMMIT;
