-- ============================================
-- TEAM HEART MISSION AUTHORIZATION SYSTEM
-- MySQL Database Schema
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS team_heart_missions CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE team_heart_missions;

-- Drop existing tables (in reverse order of dependencies)
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS report_attachments;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS mission_authorizations;
DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS users;

-- ============================================
-- USERS TABLE
-- Stores user account information
-- ============================================
CREATE TABLE users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'member') DEFAULT 'member',
    status ENUM('pending', 'active', 'rejected', 'suspended') DEFAULT 'pending',
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    password_reset_token VARCHAR(255),
    password_reset_expires DATETIME,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED,
    
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_role (role),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MEMBERS TABLE
-- Stores organization members/travelers
-- ============================================
CREATE TABLE members (
    member_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,  -- Each user is also a member
    
    -- Personal Information
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    nationality VARCHAR(100) DEFAULT 'Rwanda',
    
    -- Contact Information
    email VARCHAR(255),
    phone VARCHAR(20),
    alternate_phone VARCHAR(20),
    address_line1 VARCHAR(255),
    address_line2 VARCHAR(255),
    city VARCHAR(100),
    state_province VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'Rwanda',
    
    -- Professional Information
    employee_id VARCHAR(50) UNIQUE,
    position VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    employment_status ENUM('full-time', 'part-time', 'contract', 'volunteer') DEFAULT 'full-time',
    date_hired DATE,
    
    -- Emergency Contact
    emergency_contact_name VARCHAR(255),
    emergency_contact_relationship VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    
    -- Documents
    passport_number VARCHAR(50),
    passport_expiry DATE,
    id_number VARCHAR(50),
    profile_photo_url VARCHAR(500),
    
    -- Status
    status ENUM('active', 'inactive', 'on-leave', 'terminated') DEFAULT 'active',
    
    -- Metadata
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (updated_by) REFERENCES users(user_id),
    
    INDEX idx_user_id (user_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_status (status),
    INDEX idx_position (position),
    INDEX idx_full_name (last_name, first_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- MISSION AUTHORIZATIONS TABLE
-- Stores mission authorization records
-- ============================================
CREATE TABLE mission_authorizations (
    authorization_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    
    -- Authorization Details
    authorization_number VARCHAR(50) UNIQUE NOT NULL,
    
    -- Mission Information
    mission_purpose TEXT NOT NULL,
    destination TEXT NOT NULL,
    departure_date DATE NOT NULL,
    return_date DATE NOT NULL,
    duration_days INT UNSIGNED NOT NULL,
    
    -- Authorization
    authorized_by VARCHAR(255),
    authorized_by_position VARCHAR(255),
    authorized_by_signature_url VARCHAR(500),
    authorization_date DATE,
    
    -- Budget & Finance
    estimated_budget DECIMAL(10, 2),
    currency VARCHAR(10) DEFAULT 'RWF',
    budget_approved BOOLEAN DEFAULT FALSE,
    budget_approved_by INT UNSIGNED,
    budget_approved_date DATE,
    
    -- Status Tracking
    status ENUM('draft', 'pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    approval_notes TEXT,
    rejection_reason TEXT,
    
    -- Travel Details
    transportation_mode ENUM('flight', 'vehicle', 'bus', 'train', 'other'),
    accommodation_required BOOLEAN DEFAULT FALSE,
    accommodation_details TEXT,
    
    -- Documents
    document_url VARCHAR(500),
    supporting_documents JSON,
    
    -- Metadata
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (updated_by) REFERENCES users(user_id),
    FOREIGN KEY (budget_approved_by) REFERENCES users(user_id),
    
    INDEX idx_authorization_number (authorization_number),
    INDEX idx_user_id (user_id),
    INDEX idx_member_id (member_id),
    INDEX idx_status (status),
    INDEX idx_departure_date (departure_date),
    INDEX idx_return_date (return_date),
    INDEX idx_created_at (created_at),
    
    -- Ensure return date is after departure date
    CONSTRAINT chk_dates CHECK (return_date >= departure_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- REPORTS TABLE
-- Stores mission reports with rich-text content
-- ============================================
CREATE TABLE reports (
    report_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    authorization_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    member_id INT UNSIGNED NOT NULL,
    
    -- Report Content
    title VARCHAR(500) NOT NULL,
    content LONGTEXT NOT NULL,  -- HTML from WYSIWYG editor
    summary TEXT,
    
    -- Metadata
    status ENUM('draft', 'submitted', 'under-review', 'approved', 'rejected') DEFAULT 'draft',
    submission_date DATETIME,
    approval_notes TEXT,
    rejection_reason TEXT,
    
    -- Tracking
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    submitted_at DATETIME,
    created_by INT UNSIGNED NOT NULL,
    updated_by INT UNSIGNED,
    
    FOREIGN KEY (authorization_id) REFERENCES mission_authorizations(authorization_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (updated_by) REFERENCES users(user_id),
    
    INDEX idx_authorization_id (authorization_id),
    INDEX idx_user_id (user_id),
    INDEX idx_member_id (member_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- REPORT ATTACHMENTS TABLE
-- Stores file uploads for reports
-- ============================================
CREATE TABLE report_attachments (
    attachment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_id INT UNSIGNED NOT NULL,
    
    -- File Information
    original_filename VARCHAR(500) NOT NULL,
    stored_filename VARCHAR(500) NOT NULL UNIQUE,
    file_path VARCHAR(1000) NOT NULL,  -- Relative path from uploads directory
    file_size INT UNSIGNED NOT NULL,
    mime_type VARCHAR(100),
    file_extension VARCHAR(20),
    
    -- Metadata
    description TEXT,
    uploaded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (report_id) REFERENCES reports(report_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
    
    INDEX idx_report_id (report_id),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_created_at (created_at),
    INDEX idx_stored_filename (stored_filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AUDIT LOGS TABLE
-- Tracks all changes for compliance
-- ============================================
CREATE TABLE audit_logs (
    log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    table_name VARCHAR(100) NOT NULL,
    record_id INT UNSIGNED NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT') NOT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_table_name (table_name),
    INDEX idx_record_id (record_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- Insert sample admin user (password: Admin@123)
-- Password hash generated with: password_hash('Admin@123', PASSWORD_BCRYPT)
INSERT INTO users (email, password_hash, first_name, last_name, role, status, email_verified) VALUES 
    ('admin@teamheartrw.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', 'active', TRUE),
    ('member1@teamheartrw.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mugumanyi', 'Faustin', 'member', 'active', TRUE),
    ('member2@teamheartrw.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', 'member', 'pending', TRUE);

-- Insert sample members (linked to users)
INSERT INTO members (
    user_id, first_name, last_name, email, phone, position, 
    department, employee_id, status, created_by
) VALUES 
    (2, 'Mugumanyi', 'Faustin', 'member1@teamheartrw.org', '+250788123456', 
     'Cardiac Nurse Educator and Mentor', 'Medical', 'TH-2025-001', 'active', 1),
    (3, 'Jane', 'Smith', 'member2@teamheartrw.org', '+250788654321', 
     'Field Coordinator', 'Operations', 'TH-2025-002', 'active', 1);

-- Insert sample mission authorization
INSERT INTO mission_authorizations (
    user_id, member_id, authorization_number, mission_purpose, destination,
    departure_date, return_date, duration_days, status, created_by
) VALUES (
    2, 1, 'MA-2025-0001', 'October pre-screening activities', 
    'Kigali – Kibogora – Kabyayi', '2025-09-10', '2025-09-12', 3, 'pending', 2
);

-- ============================================
-- CREATE VIEWS
-- ============================================

-- Active Members View
CREATE OR REPLACE VIEW v_active_members AS
SELECT 
    m.*,
    CONCAT(m.first_name, ' ', m.last_name) AS full_name,
    u.email AS user_email,
    u.role AS user_role
FROM members m
INNER JOIN users u ON m.user_id = u.user_id
WHERE m.status = 'active' AND u.status = 'active';

-- Mission Authorizations with Member Details
CREATE OR REPLACE VIEW v_mission_authorizations_full AS
SELECT 
    ma.authorization_id,
    ma.authorization_number,
    ma.mission_purpose,
    ma.destination,
    ma.departure_date,
    ma.return_date,
    ma.duration_days,
    ma.status,
    ma.authorized_by,
    ma.authorization_date,
    
    -- Member Information
    CONCAT(m.first_name, ' ', m.last_name) AS traveler_name,
    m.position AS traveler_position,
    m.email AS traveler_email,
    m.phone AS traveler_phone,
    
    -- User Information
    CONCAT(u.first_name, ' ', u.last_name) AS created_by_name,
    u.email AS created_by_email,
    
    ma.created_at,
    ma.updated_at
FROM mission_authorizations ma
INNER JOIN members m ON ma.member_id = m.member_id
INNER JOIN users u ON ma.created_by = u.user_id;

-- ============================================
-- ADDITIONAL INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX idx_member_user_status ON members(user_id, status);
CREATE INDEX idx_mission_member_status ON mission_authorizations(member_id, status);
CREATE INDEX idx_mission_dates ON mission_authorizations(departure_date, return_date);

-- ============================================
-- SAMPLE QUERIES (commented out)
-- ============================================

-- Get all missions for a specific member
-- SELECT * FROM v_mission_authorizations_full WHERE traveler_name LIKE '%Faustin%';

-- Get upcoming missions
-- SELECT * FROM v_mission_authorizations_full WHERE departure_date >= CURDATE() ORDER BY departure_date;

-- Get missions by status
-- SELECT * FROM v_mission_authorizations_full WHERE status = 'approved';

-- Get member's mission history
-- SELECT authorization_number, mission_purpose, destination, departure_date, return_date, status
-- FROM mission_authorizations WHERE member_id = 1 ORDER BY departure_date DESC;

-- ============================================
-- END OF SCHEMA
-- ============================================