CREATE DATABASE IF NOT EXISTS weagri_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE weagri_db;

CREATE TABLE IF NOT EXISTS agri_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    temperature DECIMAL(5,2) NOT NULL,
    soil_moisture DECIMAL(5,2) NOT NULL,
    crop_health DECIMAL(5,2) NOT NULL,
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS market_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crop_name VARCHAR(120) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    trend ENUM('up', 'down') NOT NULL DEFAULT 'up',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO agri_metrics (temperature, soil_moisture, crop_health)
SELECT 28.40, 64.50, 91.00
WHERE NOT EXISTS (SELECT 1 FROM agri_metrics);

INSERT INTO market_prices (crop_name, price, trend)
SELECT 'Rice', 52.40, 'up'
WHERE NOT EXISTS (SELECT 1 FROM market_prices WHERE crop_name = 'Rice');

INSERT INTO market_prices (crop_name, price, trend)
SELECT 'Corn', 31.75, 'down'
WHERE NOT EXISTS (SELECT 1 FROM market_prices WHERE crop_name = 'Corn');

INSERT INTO market_prices (crop_name, price, trend)
SELECT 'Tomato', 68.20, 'up'
WHERE NOT EXISTS (SELECT 1 FROM market_prices WHERE crop_name = 'Tomato');

CREATE TABLE IF NOT EXISTS agri_metrics_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sensor_node_id VARCHAR(80) NOT NULL,
    temperature DECIMAL(5,2) NOT NULL,
    soil_moisture DECIMAL(5,2) NOT NULL,
    crop_health_index DECIMAL(5,2) NOT NULL,
    `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_agri_metrics_log_timestamp (`timestamp`),
    INDEX idx_agri_metrics_log_recorded_at (recorded_at)
);

CREATE TABLE IF NOT EXISTS market_hub_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    crop_name VARCHAR(120) NOT NULL,
    price_per_kg DECIMAL(10,2) NOT NULL,
    trend_direction ENUM('up', 'down', 'stable') NOT NULL DEFAULT 'stable',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_market_hub_prices_crop (crop_name)
);

CREATE TABLE IF NOT EXISTS experts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    specialty VARCHAR(120) NOT NULL,
    email VARCHAR(160) NULL,
    location VARCHAR(160) NOT NULL DEFAULT 'Remote consultation',
    status ENUM('online', 'busy', 'offline') NOT NULL DEFAULT 'online',
    response_minutes INT NOT NULL DEFAULT 10,
    bio VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expert_id INT NOT NULL,
    farmer_name VARCHAR(120) NOT NULL,
    farmer_email VARCHAR(160) NOT NULL,
    crop_name VARCHAR(120) NOT NULL,
    location VARCHAR(160) NOT NULL,
    concern TEXT NOT NULL,
    appointment_date DATETIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_expert FOREIGN KEY (expert_id) REFERENCES experts(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS consultants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    specialty VARCHAR(120) NOT NULL,
    is_online TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message_text TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_messages_participants (sender_id, receiver_id),
    INDEX idx_messages_created_at (created_at)
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('farmer', 'consultant') NOT NULL,
    specialty_tags VARCHAR(255) NULL,
    is_online TINYINT(1) NOT NULL DEFAULT 0,
    last_active DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_online (is_online, last_active)
);

SET @messages_has_is_read := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'messages'
      AND COLUMN_NAME = 'is_read'
);
SET @messages_add_is_read_sql := IF(
    @messages_has_is_read = 0,
    'ALTER TABLE messages ADD COLUMN is_read TINYINT(1) NOT NULL DEFAULT 0 AFTER created_at',
    'SELECT 1'
);
PREPARE messages_add_is_read_stmt FROM @messages_add_is_read_sql;
EXECUTE messages_add_is_read_stmt;
DEALLOCATE PREPARE messages_add_is_read_stmt;

SET @messages_has_sender_idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'messages'
      AND INDEX_NAME = 'idx_messages_sender'
);
SET @messages_add_sender_idx_sql := IF(
    @messages_has_sender_idx = 0,
    'ALTER TABLE messages ADD INDEX idx_messages_sender (sender_id)',
    'SELECT 1'
);
PREPARE messages_add_sender_idx_stmt FROM @messages_add_sender_idx_sql;
EXECUTE messages_add_sender_idx_stmt;
DEALLOCATE PREPARE messages_add_sender_idx_stmt;

SET @messages_has_receiver_idx := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'messages'
      AND INDEX_NAME = 'idx_messages_receiver'
);
SET @messages_add_receiver_idx_sql := IF(
    @messages_has_receiver_idx = 0,
    'ALTER TABLE messages ADD INDEX idx_messages_receiver (receiver_id)',
    'SELECT 1'
);
PREPARE messages_add_receiver_idx_stmt FROM @messages_add_receiver_idx_sql;
EXECUTE messages_add_receiver_idx_stmt;
DEALLOCATE PREPARE messages_add_receiver_idx_stmt;

INSERT INTO agri_metrics_log (sensor_node_id, temperature, soil_moisture, crop_health_index, recorded_at)
SELECT *
FROM (
    SELECT 'north-field', 27.80, 67.20, 92.00, DATE_SUB(NOW(), INTERVAL 50 MINUTE)
    UNION ALL SELECT 'north-field', 28.10, 65.90, 91.40, DATE_SUB(NOW(), INTERVAL 40 MINUTE)
    UNION ALL SELECT 'north-field', 28.70, 64.10, 90.80, DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    UNION ALL SELECT 'north-field', 29.20, 62.70, 89.60, DATE_SUB(NOW(), INTERVAL 20 MINUTE)
    UNION ALL SELECT 'north-field', 28.60, 63.80, 90.20, DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    UNION ALL SELECT 'north-field', 28.40, 64.50, 91.00, NOW()
) AS seed_metrics
WHERE NOT EXISTS (SELECT 1 FROM agri_metrics_log);

INSERT INTO market_hub_prices (crop_name, price_per_kg, trend_direction, updated_at)
SELECT *
FROM (
    SELECT 'Rice', 52.40, 'up', NOW()
    UNION ALL SELECT 'Corn', 31.75, 'down', NOW()
    UNION ALL SELECT 'Tomato', 68.20, 'up', NOW()
    UNION ALL SELECT 'Eggplant', 58.00, 'stable', NOW()
) AS seed_prices
WHERE NOT EXISTS (SELECT 1 FROM market_hub_prices);

INSERT INTO experts (full_name, specialty, email, location, status, response_minutes, bio)
SELECT *
FROM (
    SELECT 'Dr. Liza Santos', 'Plant Pathology', 'liza.santos@weagri.local', 'Laguna', 'online', 8, 'Helps diagnose crop disease symptoms and safe field management steps.'
    UNION ALL SELECT 'Marco Reyes', 'Soil Health', 'marco.reyes@weagri.local', 'Nueva Ecija', 'online', 12, 'Advises on soil testing, composting, fertilizer timing, and water management.'
    UNION ALL SELECT 'Ana Villanueva', 'Pest Management', 'ana.villanueva@weagri.local', 'Batangas', 'busy', 18, 'Supports integrated pest management for vegetable and grain farms.'
) AS seed_experts
WHERE NOT EXISTS (SELECT 1 FROM experts);

INSERT INTO consultants (name, specialty, is_online)
SELECT *
FROM (
    SELECT 'Dr. Liza Santos', 'Plant Pathology', 1
    UNION ALL SELECT 'Marco Reyes', 'Soil Health', 1
    UNION ALL SELECT 'Ana Villanueva', 'Pest Management', 0
    UNION ALL SELECT 'Rafael Cruz', 'Irrigation Planning', 1
) AS seed_consultants
WHERE NOT EXISTS (SELECT 1 FROM consultants);

INSERT INTO users (full_name, email, password_hash, role, specialty_tags, is_online, last_active)
SELECT 'Isiah Farmer', 'farmer@weagri.local', '$2y$10$KzhsDxhvVmwv7MPcsugDMueIriRyTPu0Nhcx7WX3zKSfK1c8OUUzG', 'farmer', NULL, 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'farmer@weagri.local');

INSERT INTO users (full_name, email, password_hash, role, specialty_tags, is_online, last_active)
SELECT 'Dr. Liza Santos', 'liza@weagri.local', '$2y$10$KzhsDxhvVmwv7MPcsugDMueIriRyTPu0Nhcx7WX3zKSfK1c8OUUzG', 'consultant', 'Plant Pathology, Tomatoes, Disease Diagnosis', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'liza@weagri.local');

INSERT INTO users (full_name, email, password_hash, role, specialty_tags, is_online, last_active)
SELECT 'Marco Reyes', 'marco@weagri.local', '$2y$10$KzhsDxhvVmwv7MPcsugDMueIriRyTPu0Nhcx7WX3zKSfK1c8OUUzG', 'consultant', 'Soil Health, Compost, Fertilizer Timing', 1, NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'marco@weagri.local');

INSERT INTO users (full_name, email, password_hash, role, specialty_tags, is_online, last_active)
SELECT 'Ana Villanueva', 'ana@weagri.local', '$2y$10$KzhsDxhvVmwv7MPcsugDMueIriRyTPu0Nhcx7WX3zKSfK1c8OUUzG', 'consultant', 'Pest Management, Vegetables, IPM', 0, NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'ana@weagri.local');

INSERT INTO messages (sender_id, receiver_id, message_text, created_at, is_read)
SELECT *
FROM (
    SELECT
        (SELECT id FROM users WHERE email = 'liza@weagri.local' LIMIT 1),
        (SELECT id FROM users WHERE email = 'farmer@weagri.local' LIMIT 1),
        'Hello, I am Dr. Liza. Share the crop, symptoms, and how long the issue has been visible.',
        DATE_SUB(NOW(), INTERVAL 12 MINUTE),
        1
    UNION ALL
    SELECT
        (SELECT id FROM users WHERE email = 'farmer@weagri.local' LIMIT 1),
        (SELECT id FROM users WHERE email = 'liza@weagri.local' LIMIT 1),
        'I am checking tomato leaves with yellowing and brown spots.',
        DATE_SUB(NOW(), INTERVAL 10 MINUTE),
        1
    UNION ALL
    SELECT
        (SELECT id FROM users WHERE email = 'liza@weagri.local' LIMIT 1),
        (SELECT id FROM users WHERE email = 'farmer@weagri.local' LIMIT 1),
        'Please upload or describe whether the spots have yellow halos. That helps separate fungal leaf spot from nutrient stress.',
        DATE_SUB(NOW(), INTERVAL 8 MINUTE),
        0
) AS seed_messages
WHERE NOT EXISTS (SELECT 1 FROM messages);

INSERT INTO messages (sender_id, receiver_id, message_text, created_at, is_read)
SELECT *
FROM (
    SELECT
        (SELECT id FROM users WHERE email = 'liza@weagri.local' LIMIT 1),
        (SELECT id FROM users WHERE email = 'farmer@weagri.local' LIMIT 1),
        'Good morning. Send a close description of the leaves and how quickly the spotting spread.',
        DATE_SUB(NOW(), INTERVAL 14 MINUTE),
        1
    UNION ALL
    SELECT
        (SELECT id FROM users WHERE email = 'farmer@weagri.local' LIMIT 1),
        (SELECT id FROM users WHERE email = 'liza@weagri.local' LIMIT 1),
        'The tomato leaves started yellowing three days ago and now there are brown spots on the older leaves.',
        DATE_SUB(NOW(), INTERVAL 11 MINUTE),
        1
    UNION ALL
    SELECT
        (SELECT id FROM users WHERE email = 'liza@weagri.local' LIMIT 1),
        (SELECT id FROM users WHERE email = 'farmer@weagri.local' LIMIT 1),
        'Check whether the spots have rings or yellow halos, then avoid wetting the leaves while you scout the rest of the plants.',
        DATE_SUB(NOW(), INTERVAL 9 MINUTE),
        0
) AS seeded_user_messages
WHERE NOT EXISTS (
    SELECT 1
    FROM messages
    WHERE sender_id = (SELECT id FROM users WHERE email = 'liza@weagri.local' LIMIT 1)
      AND receiver_id = (SELECT id FROM users WHERE email = 'farmer@weagri.local' LIMIT 1)
);

DELETE FROM messages
WHERE sender_id = 0
   OR receiver_id = 0;

SET @messages_has_sender_fk := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'messages'
      AND CONSTRAINT_NAME = 'fk_messages_sender_user'
);
SET @messages_add_sender_fk_sql := IF(
    @messages_has_sender_fk = 0,
    'ALTER TABLE messages ADD CONSTRAINT fk_messages_sender_user FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE messages_add_sender_fk_stmt FROM @messages_add_sender_fk_sql;
EXECUTE messages_add_sender_fk_stmt;
DEALLOCATE PREPARE messages_add_sender_fk_stmt;

SET @messages_has_receiver_fk := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'messages'
      AND CONSTRAINT_NAME = 'fk_messages_receiver_user'
);
SET @messages_add_receiver_fk_sql := IF(
    @messages_has_receiver_fk = 0,
    'ALTER TABLE messages ADD CONSTRAINT fk_messages_receiver_user FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE',
    'SELECT 1'
);
PREPARE messages_add_receiver_fk_stmt FROM @messages_add_receiver_fk_sql;
EXECUTE messages_add_receiver_fk_stmt;
DEALLOCATE PREPARE messages_add_receiver_fk_stmt;
