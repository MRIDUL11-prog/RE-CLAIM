USE campus_lost_found;

CREATE TABLE IF NOT EXISTS feedback (
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
) ENGINE=InnoDB;
