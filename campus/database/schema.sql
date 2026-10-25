-- Medicina del Alma · Campus
-- MySQL 8+ / MariaDB 10.5+. Ejecutar dentro de la base de datos creada para el campus.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS campus_settings (
    setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO campus_settings (setting_key, setting_value)
VALUES ('setup_complete', '0')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

INSERT INTO campus_settings (setting_key, setting_value)
VALUES ('home_content_ready', '0')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

CREATE TABLE IF NOT EXISTS campus_users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'facilitator', 'member') NOT NULL DEFAULT 'member',
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_campus_users_status_role (status, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campus_sections (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    description TEXT NULL,
    status ENUM('published', 'archived') NOT NULL DEFAULT 'published',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_campus_sections_status_created (status, created_at),
    CONSTRAINT fk_campus_sections_creator FOREIGN KEY (created_by) REFERENCES campus_users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campus_section_members (
    section_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    section_role ENUM('facilitator', 'member') NOT NULL DEFAULT 'member',
    added_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (section_id, user_id),
    INDEX idx_campus_section_members_user (user_id, section_role),
    CONSTRAINT fk_campus_section_members_section FOREIGN KEY (section_id) REFERENCES campus_sections(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_campus_section_members_user FOREIGN KEY (user_id) REFERENCES campus_users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_campus_section_members_added_by FOREIGN KEY (added_by) REFERENCES campus_users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campus_posts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    section_id BIGINT UNSIGNED NOT NULL,
    author_id BIGINT UNSIGNED NOT NULL,
    post_type ENUM('post', 'announcement', 'activity') NOT NULL DEFAULT 'post',
    title VARCHAR(200) NOT NULL,
    body MEDIUMTEXT NOT NULL,
    due_at DATETIME NULL,
    status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_campus_posts_feed (section_id, status, created_at),
    INDEX idx_campus_posts_due (section_id, post_type, due_at),
    CONSTRAINT fk_campus_posts_section FOREIGN KEY (section_id) REFERENCES campus_sections(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_campus_posts_author FOREIGN KEY (author_id) REFERENCES campus_users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campus_post_attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_campus_post_attachments_post (post_id),
    CONSTRAINT fk_campus_post_attachments_post FOREIGN KEY (post_id) REFERENCES campus_posts(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campus_submissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    member_id BIGINT UNSIGNED NOT NULL,
    response MEDIUMTEXT NULL,
    stored_name VARCHAR(255) NULL,
    original_name VARCHAR(255) NULL,
    mime_type VARCHAR(120) NULL,
    file_size BIGINT UNSIGNED NULL,
    status ENUM('submitted', 'in_review', 'needs_changes', 'approved') NOT NULL DEFAULT 'submitted',
    feedback TEXT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_campus_submission_post_member (post_id, member_id),
    INDEX idx_campus_submissions_review (post_id, status, submitted_at),
    CONSTRAINT fk_campus_submissions_post FOREIGN KEY (post_id) REFERENCES campus_posts(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_campus_submissions_member FOREIGN KEY (member_id) REFERENCES campus_users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_campus_submissions_reviewer FOREIGN KEY (reviewed_by) REFERENCES campus_users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS campus_home_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    category ENUM('workshops', 'therapies', 'objects') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NULL,
    image_url VARCHAR(1000) NULL,
    target_url VARCHAR(1000) NULL,
    link_label VARCHAR(80) NOT NULL DEFAULT 'Saber más',
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('published', 'hidden') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_campus_home_items_category_title (category, title),
    INDEX idx_campus_home_items_feed (category, status, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS campus_public_pages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    eyebrow VARCHAR(160) NULL,
    headline VARCHAR(240) NOT NULL,
    `lead` TEXT NULL,
    image_url VARCHAR(1000) NULL,
    intro_title VARCHAR(200) NULL,
    intro_body MEDIUMTEXT NULL,
    topics_title VARCHAR(200) NULL,
    topics MEDIUMTEXT NULL,
    program_title VARCHAR(200) NULL,
    program_details MEDIUMTEXT NULL,
    modality VARCHAR(160) NULL,
    schedule VARCHAR(200) NULL,
    duration VARCHAR(120) NULL,
    next_date VARCHAR(160) NULL,
    price VARCHAR(120) NULL,
    cta_label VARCHAR(100) NOT NULL DEFAULT 'Consultar por WhatsApp',
    disclaimer TEXT NULL,
    status ENUM('published', 'hidden') NOT NULL DEFAULT 'published',
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_campus_public_pages_status (status, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
