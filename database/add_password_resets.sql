USE campus_lost_found;

CREATE TABLE IF NOT EXISTS password_resets (
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
) ENGINE=InnoDB;
