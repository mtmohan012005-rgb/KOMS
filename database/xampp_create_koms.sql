-- KOMS XAMPP database bootstrap
-- Run this first in phpMyAdmin if the local database does not exist.

CREATE DATABASE IF NOT EXISTS `koms`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `koms`;

-- After this file succeeds, import database/schema.sql
-- Then import database/seed.sql if demo data is required.
