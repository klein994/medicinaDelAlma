CREATE TABLE IF NOT EXISTS campus_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    section_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NULL,
    location VARCHAR(255) NULL,
    meeting_url VARCHAR(1000) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_campus_events_agenda (section_id, starts_at),
    CONSTRAINT fk_campus_events_section FOREIGN KEY (section_id) REFERENCES campus_sections(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_campus_events_creator FOREIGN KEY (created_by) REFERENCES campus_users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
