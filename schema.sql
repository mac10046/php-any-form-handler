-- PHP Any Form Handler - Database Schema
-- Run this SQL for each tenant's database

CREATE TABLE IF NOT EXISTS submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_name VARCHAR(255) DEFAULT 'default',
    form_data JSON NOT NULL,
    sender_ip VARCHAR(45),
    user_agent TEXT,
    referer_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_form_name (form_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
