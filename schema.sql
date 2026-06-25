-- Profilo schema (MySQL side)
-- MySQL owns identity/auth data only. Extended profile fields
-- (age, dob, contact, address) live in MongoDB — see mongo/init.js.
--
-- Run this once against your MySQL server:
--   mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS profilo_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE profilo_db;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100)  NOT NULL,
    username      VARCHAR(50)   NOT NULL,
    email         VARCHAR(100)  NOT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
