-- Two-Factor Authentication Database Schema
-- Add to: BD/fanavid_db_usuarios.sql

-- Table: usuarios_2fa - 2FA configuration
CREATE TABLE IF NOT EXISTS `usuarios_2fa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `totp_secret` VARCHAR(32),
  `method` ENUM('totp', 'email', 'sms') DEFAULT 'totp',
  `backup_codes` JSON,
  `enabled` TINYINT(1) DEFAULT 0,
  `last_verified_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_2fa` (`user_id`, `enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: usuarios_2fa_otp - Email OTP codes
CREATE TABLE IF NOT EXISTS `usuarios_2fa_otp` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `otp_hash` VARCHAR(255) NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `expires_at` TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_otp` (`user_id`, `used`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: usuarios_2fa_sessions - Partial auth sessions
CREATE TABLE IF NOT EXISTS `usuarios_2fa_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_session` (`user_id`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add 2FA status column to usuarios table (optional, for efficiency)
ALTER TABLE `usuarios` ADD COLUMN `totp_enabled` TINYINT(1) DEFAULT 0 AFTER `password` IF NOT EXISTS;
ALTER TABLE `usuarios` ADD COLUMN `two_factor_method` ENUM('none', 'totp', 'email', 'sms') DEFAULT 'none' AFTER `totp_enabled` IF NOT EXISTS;

-- Index for faster 2FA checks
ALTER TABLE `usuarios` ADD INDEX `idx_2fa_status` (`totp_enabled`, `two_factor_method`);
