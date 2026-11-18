-- Create database
CREATE DATABASE IF NOT EXISTS campus_lost_found;

USE campus_lost_found;

-- 1. USERS TABLE
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    student_id VARCHAR(50) UNIQUE,
    department VARCHAR(100),
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. CATEGORIES TABLE
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

-- 3. ITEMS TABLE
CREATE TABLE items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    description TEXT,
    item_type ENUM('lost', 'found') NOT NULL,
    location VARCHAR(200) NOT NULL,
    item_date DATE NOT NULL,
    image VARCHAR(255),
    status ENUM(
        'pending',
        'approved',
        'rejected',
        'claimed',
        'returned'
    ) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    FOREIGN KEY (category_id)
        REFERENCES categories(category_id)
);

-- 4. CLAIMS TABLE
CREATE TABLE claims (
    claim_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    claimant_id INT NOT NULL,
    message TEXT,
    claim_status ENUM(
        'pending',
        'approved',
        'rejected'
    ) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (item_id)
        REFERENCES items(item_id)
        ON DELETE CASCADE,

    FOREIGN KEY (claimant_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

-- 5. FEEDBACK
CREATE TABLE feedback (
    feedback_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    feedback_type VARCHAR(40) NOT NULL,
    email VARCHAR(254) NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feedback_user_created (user_id, created_at),
    CONSTRAINT fk_feedback_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);

-- 6. PASSWORD RESET REQUESTS
CREATE TABLE password_resets (
    reset_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    email_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    token_hash CHAR(64) NULL,
    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    INDEX idx_password_resets_email_requested (email_hash, requested_at),
    INDEX idx_password_resets_ip_requested (ip_hash, requested_at),
    UNIQUE INDEX idx_password_resets_token (token_hash),
    INDEX idx_password_resets_user (user_id),
    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

-- Insert item categories
INSERT INTO categories (category_name) VALUES
('Mobile Phone'),
('Laptop'),
('Books'),
('Wallet'),
('ID Card'),
('Keys'),
('Bag'),
('Watch'),
('Documents'),
('Other');