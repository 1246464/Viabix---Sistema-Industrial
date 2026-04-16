-- Audit Logging Database Schema
-- Add to: BD/fanavid_db_logs_atividade.sql

-- Table: audit_logs - Comprehensive audit trail
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT,
  `category` VARCHAR(50) NOT NULL,              -- AUTH, CRUD, SECURITY, DATA_ACCESS, API, ERROR
  `action` VARCHAR(100) NOT NULL,               -- Specific action within category
  `ip_address` VARCHAR(45) NOT NULL,            -- IPv4 or IPv6
  `user_agent` VARCHAR(1000),                   -- Browser/client info
  `details` JSON,                               -- Additional context as JSON
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  
  -- Indexes for common queries
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_category` (`category`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_user_category_date` (`user_id`, `category`, `created_at`),
  INDEX `idx_ip_date` (`ip_address`, `created_at`),
  
  -- Partition by month for better performance on large datasets
  PARTITION BY RANGE (YEAR_MONTH(created_at)) (
    PARTITION p_2023_01 VALUES LESS THAN (202302),
    PARTITION p_2024_01 VALUES LESS THAN (202402),
    PARTITION p_2025_01 VALUES LESS THAN (202502),
    PARTITION p_2026_01 VALUES LESS THAN (202602),
    PARTITION p_2026_02 VALUES LESS THAN (202603),
    PARTITION p_2026_03 VALUES LESS THAN (202604),
    PARTITION p_2026_04 VALUES LESS THAN (202605),
    PARTITION p_2026_05 VALUES LESS THAN (202606),
    PARTITION p_2026_06 VALUES LESS THAN (202607),
    PARTITION p_2026_07 VALUES LESS THAN (202608),
    PARTITION p_2026_08 VALUES LESS THAN (202609),
    PARTITION p_2026_09 VALUES LESS THAN (202610),
    PARTITION p_2026_10 VALUES LESS THAN (202611),
    PARTITION p_2026_11 VALUES LESS THAN (202612),
    PARTITION p_future VALUES LESS THAN MAXVALUE
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: audit_events_summary - Pre-aggregated statistics (for performance)
CREATE TABLE IF NOT EXISTS `audit_events_summary` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `date` DATE NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `count` INT DEFAULT 0,
  `unique_users` INT DEFAULT 0,
  `unique_ips` INT DEFAULT 0,
  
  UNIQUE KEY `unique_event` (`date`, `category`, `action`),
  INDEX `idx_date` (`date`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: audit_retention_policy - GDPR compliance settings
CREATE TABLE IF NOT EXISTS `audit_retention_policy` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` VARCHAR(50),                     -- NULL = all categories
  `retention_days` INT DEFAULT 90,
  `archive_after_days` INT DEFAULT 180,       -- Archive instead of delete
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  UNIQUE KEY `unique_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default retention policies
INSERT IGNORE INTO `audit_retention_policy` (category, retention_days, archive_after_days) VALUES
('AUTH', 365, 730),           -- Keep auth logs for compliance
('SECURITY', 365, 730),       -- Keep security events long-term
('PERMISSIONS', 2555, 2555),  -- Keep permission changes 7 years (regulatory)
('DATA_ACCESS', 90, 180),     -- GDPR: Keep 90 days, archive 180
('CRUD_USUARIO', 90, 180),    -- User data changes
('ERROR', 90, 365),           -- Error logs 90 days active
(NULL, 90, 180);              -- Default: 90 days active, 180 days archived

-- Optional: Create table for archived logs (cold storage)
CREATE TABLE IF NOT EXISTS `audit_logs_archive` (
  `id` BIGINT,
  `user_id` INT,
  `category` VARCHAR(50) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(1000),
  `details` JSON,
  `created_at` TIMESTAMP,
  
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create event to auto-clean old logs (run daily at 2 AM)
CREATE EVENT IF NOT EXISTS `cleanup_old_audit_logs` ON SCHEDULE EVERY 1 DAY
STARTS CURDATE() + INTERVAL 2 HOUR
DO
BEGIN
  -- Move old logs to archive
  INSERT INTO audit_logs_archive 
  SELECT * FROM audit_logs
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY);
  
  -- Delete archived logs
  DELETE FROM audit_logs 
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY);
  
  -- Update summary
  INSERT INTO audit_events_summary (date, category, action, count)
  SELECT CAST(created_at AS DATE), category, action, COUNT(*)
  FROM audit_logs
  WHERE created_at >= CURDATE() - INTERVAL 1 DAY
  GROUP BY CAST(created_at AS DATE), category, action
  ON DUPLICATE KEY UPDATE count = VALUES(count);
END;

-- View for easy reporting
CREATE OR REPLACE VIEW `audit_logs_view` AS
SELECT 
  al.id,
  al.user_id,
  u.email as user_email,
  u.name as user_name,
  al.category,
  al.action,
  al.ip_address,
  al.user_agent,
  al.details,
  al.created_at,
  YEAR(al.created_at) as year,
  MONTH(al.created_at) as month,
  DATE(al.created_at) as date,
  HOUR(al.created_at) as hour
FROM audit_logs al
LEFT JOIN usuarios u ON al.user_id = u.id
ORDER BY al.created_at DESC;
