-- SkillSwap Database Schema
-- Works with local 'skillswap' or cloud 'defaultdb'

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    bio TEXT,
    profile_picture VARCHAR(255) DEFAULT NULL,
    role ENUM('student','admin') DEFAULT 'student',
    is_verified TINYINT(1) DEFAULT 0,           -- becomes 1 once admin approves a certificate
    status ENUM('active','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================
-- SKILLS (master list, e.g. "PHP", "Public Speaking")
-- ============================
CREATE TABLE skills (
    skill_id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB;

-- ============================
-- USER_SKILLS (which skills a user can teach vs wants to learn)
-- ============================
CREATE TABLE user_skills (
    user_skill_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    type ENUM('teach','learn') NOT NULL,
    proficiency ENUM('beginner','intermediate','advanced','expert') DEFAULT 'beginner',
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_skill_type (user_id, skill_id, type)
) ENGINE=InnoDB;

-- ============================
-- CERTIFICATES (proof of skill, needs admin verification)
-- ============================
CREATE TABLE certificates (
    certificate_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_remarks VARCHAR(255) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- EXCHANGE REQUESTS
-- ============================
CREATE TABLE exchange_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    offered_skill_id INT NOT NULL,   -- skill sender will teach
    requested_skill_id INT NOT NULL, -- skill sender wants to learn
    message VARCHAR(500) DEFAULT NULL,
    status ENUM('pending','accepted','rejected','cancelled','completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (offered_skill_id) REFERENCES skills(skill_id),
    FOREIGN KEY (requested_skill_id) REFERENCES skills(skill_id)
) ENGINE=InnoDB;

-- ============================
-- SESSIONS (scheduled learning meetups tied to an accepted request)
-- ============================
CREATE TABLE sessions_schedule (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    scheduled_date DATETIME NOT NULL,
    mode ENUM('online','in-person') DEFAULT 'online',
    location_or_link VARCHAR(255) DEFAULT NULL,
    status ENUM('scheduled','completed','cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES exchange_requests(request_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- RATINGS & REVIEWS
-- ============================
CREATE TABLE ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    rater_id INT NOT NULL,
    rated_user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES exchange_requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (rater_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (rated_user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- NOTIFICATIONS
-- ============================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
-- ADMIN ACCOUNT SETUP
-- Do NOT hardcode a password hash here. Instead:
--   1. Go to your site and register a normal account (e.g. your own email).
--   2. Run this command to promote it to admin:
--      UPDATE users SET role='admin', is_verified=1 WHERE email='your-email@example.com';
-- This guarantees the password hash is generated correctly by PHP's own
-- password_hash() function at registration time, so the login will actually work.
-- ============================

-- SEED: some common skills to get started
INSERT INTO skills (skill_name, category) VALUES
('PHP Programming', 'Technology'),
('Graphic Design', 'Design'),
('Public Speaking', 'Soft Skills'),
('Python Programming', 'Technology'),
('Video Editing', 'Design'),
('Content Writing', 'Communication'),
('Guitar', 'Music'),
('Spanish Language', 'Language');
