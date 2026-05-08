CREATE DATABASE IF NOT EXISTS weagri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE weagri;

CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    location VARCHAR(160) NOT NULL,
    primary_crop VARCHAR(80) NOT NULL,
    soil_type VARCHAR(120) NOT NULL DEFAULT 'Not specified',
    common_issues TEXT NULL,
    farm_scale VARCHAR(40) NOT NULL DEFAULT 'smallholder',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS experts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    specialty VARCHAR(120) NOT NULL,
    status ENUM('online', 'busy', 'offline') NOT NULL DEFAULT 'online',
    response_minutes INT NOT NULL DEFAULT 10,
    bio VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'farmer', 'consultant') NOT NULL,
    linked_farmer_id INT NULL,
    linked_expert_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_farmer FOREIGN KEY (linked_farmer_id) REFERENCES farmers(id) ON DELETE SET NULL,
    CONSTRAINT fk_users_expert FOREIGN KEY (linked_expert_id) REFERENCES experts(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    crop VARCHAR(80) NOT NULL,
    category VARCHAR(80) NOT NULL,
    urgency ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status ENUM('ai_triage', 'expert_assigned', 'monitoring', 'resolved') NOT NULL DEFAULT 'ai_triage',
    location VARCHAR(160) NOT NULL,
    assigned_expert_id INT NULL,
    summary TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_consultations_farmer FOREIGN KEY (farmer_id) REFERENCES farmers(id),
    CONSTRAINT fk_consultations_expert FOREIGN KEY (assigned_expert_id) REFERENCES experts(id)
);

CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    sender_type ENUM('farmer', 'ai', 'expert') NOT NULL,
    sender_name VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    `references` TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_consultation FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS direct_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_user_id INT NOT NULL,
    receiver_user_id INT NOT NULL,
    message_text TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_direct_messages_sender (sender_user_id),
    KEY idx_direct_messages_receiver (receiver_user_id),
    KEY idx_direct_messages_created_at (created_at),
    CONSTRAINT fk_direct_messages_sender FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_direct_messages_receiver FOREIGN KEY (receiver_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    type ENUM('advisory', 'consultation', 'system', 'weather') NOT NULL DEFAULT 'consultation',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    consultation_id INT NULL,
    receiver_user_id INT NULL,
    source_user_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notifications_receiver (receiver_user_id),
    KEY idx_notifications_source (source_user_id),
    CONSTRAINT fk_notifications_consultation FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE SET NULL,
    CONSTRAINT fk_notifications_receiver FOREIGN KEY (receiver_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_source FOREIGN KEY (source_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS knowledge_base (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    topic VARCHAR(120) NOT NULL,
    content TEXT NOT NULL,
    recommendations TEXT NOT NULL,
    tags TEXT NOT NULL,
    source VARCHAR(180) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS knowledge_chunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    knowledge_base_id INT NOT NULL,
    chunk_index INT NOT NULL,
    chunk_type ENUM('content', 'recommendations') NOT NULL DEFAULT 'content',
    title VARCHAR(180) NOT NULL,
    topic VARCHAR(120) NOT NULL,
    chunk_text TEXT NOT NULL,
    keywords TEXT NOT NULL,
    source VARCHAR(180) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_knowledge_chunk (knowledge_base_id, chunk_index, chunk_type),
    KEY idx_knowledge_chunks_topic (topic),
    CONSTRAINT fk_knowledge_chunks_base FOREIGN KEY (knowledge_base_id) REFERENCES knowledge_base(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS consultation_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    farmer_id INT NOT NULL,
    advisor_id INT NULL,
    target_type ENUM('ai', 'advisor') NOT NULL DEFAULT 'ai',
    helpfulness_rating TINYINT NOT NULL,
    accuracy_rating TINYINT NOT NULL,
    comment TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_consultation_feedback (consultation_id),
    CONSTRAINT fk_feedback_consultation FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_farmer FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_advisor FOREIGN KEY (advisor_id) REFERENCES experts(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS platform_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    reviewer_name VARCHAR(140) NOT NULL,
    reviewer_role VARCHAR(40) NOT NULL DEFAULT 'guest',
    rating TINYINT NOT NULL,
    comment TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_platform_feedback_rating (rating),
    KEY idx_platform_feedback_created_at (created_at),
    CONSTRAINT fk_platform_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

