-- ============================================
-- TEAM HEART MISSION AUTHORIZATION SYSTEM
-- MySQL Database Schema
-- ============================================

-- Drop existing tables (in reverse order of dependencies)
DROP TABLE IF EXISTS mission_authorizations;
DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS audit_logs;

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
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
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
    user_id INT UNSIGNED NOT NULL,
    
    -- Personal Information
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    nationality VARCHAR(100),
    
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
    status ENUM('draft', 'pending', 'approved', 'rejected', 'completed', 'cancelled') DEFAULT 'draft',
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
-- VIEWS FOR COMMON QUERIES
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

-- Pending Authorizations View
CREATE OR REPLACE VIEW v_pending_authorizations AS
SELECT 
    ma.*,
    CONCAT(m.first_name, ' ', m.last_name) AS traveler_name,
    m.position AS traveler_position
FROM mission_authorizations ma
INNER JOIN members m ON ma.member_id = m.member_id
WHERE ma.status IN ('draft', 'pending')
ORDER BY ma.departure_date ASC;

-- ============================================
-- STORED PROCEDURES
-- ============================================

-- Procedure to create a new mission authorization
DELIMITER //
CREATE PROCEDURE sp_create_mission_authorization(
    IN p_user_id INT UNSIGNED,
    IN p_member_id INT UNSIGNED,
    IN p_mission_purpose TEXT,
    IN p_destination TEXT,
    IN p_departure_date DATE,
    IN p_return_date DATE,
    OUT p_authorization_id INT UNSIGNED,
    OUT p_authorization_number VARCHAR(50)
)
BEGIN
    DECLARE v_duration INT;
    DECLARE v_year VARCHAR(4);
    DECLARE v_count INT;
    
    -- Calculate duration
    SET v_duration = DATEDIFF(p_return_date, p_departure_date) + 1;
    
    -- Generate authorization number (e.g., MA-2025-0001)
    SET v_year = YEAR(CURDATE());
    
    SELECT COUNT(*) + 1 INTO v_count
    FROM mission_authorizations
    WHERE YEAR(created_at) = v_year;
    
    SET p_authorization_number = CONCAT('MA-', v_year, '-', LPAD(v_count, 4, '0'));
    
    -- Insert the record
    INSERT INTO mission_authorizations (
        user_id,
        member_id,
        authorization_number,
        mission_purpose,
        destination,
        departure_date,
        return_date,
        duration_days,
        status,
        created_by
    ) VALUES (
        p_user_id,
        p_member_id,
        p_authorization_number,
        p_mission_purpose,
        p_destination,
        p_departure_date,
        p_return_date,
        v_duration,
        'draft',
        p_user_id
    );
    
    SET p_authorization_id = LAST_INSERT_ID();
END //
DELIMITER ;

-- Procedure to approve mission authorization
DELIMITER //
CREATE PROCEDURE sp_approve_mission_authorization(
    IN p_authorization_id INT UNSIGNED,
    IN p_approved_by_user_id INT UNSIGNED,
    IN p_authorized_by_name VARCHAR(255),
    IN p_authorized_by_position VARCHAR(255),
    IN p_approval_notes TEXT
)
BEGIN
    UPDATE mission_authorizations
    SET 
        status = 'approved',
        authorized_by = p_authorized_by_name,
        authorized_by_position = p_authorized_by_position,
        authorization_date = CURDATE(),
        approval_notes = p_approval_notes,
        updated_by = p_approved_by_user_id,
        updated_at = CURRENT_TIMESTAMP
    WHERE authorization_id = p_authorization_id;
END //
DELIMITER ;

-- ============================================
-- TRIGGERS FOR AUDIT LOGGING
-- ============================================

DELIMITER //

-- Trigger for mission authorization INSERT
CREATE TRIGGER trg_mission_auth_insert
AFTER INSERT ON mission_authorizations
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, table_name, record_id, action, new_values)
    VALUES (NEW.created_by, 'mission_authorizations', NEW.authorization_id, 'INSERT', 
            JSON_OBJECT(
                'authorization_number', NEW.authorization_number,
                'member_id', NEW.member_id,
                'status', NEW.status
            ));
END //

-- Trigger for mission authorization UPDATE
CREATE TRIGGER trg_mission_auth_update
AFTER UPDATE ON mission_authorizations
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, table_name, record_id, action, old_values, new_values)
    VALUES (NEW.updated_by, 'mission_authorizations', NEW.authorization_id, 'UPDATE',
            JSON_OBJECT(
                'status', OLD.status,
                'authorized_by', OLD.authorized_by
            ),
            JSON_OBJECT(
                'status', NEW.status,
                'authorized_by', NEW.authorized_by
            ));
END //

DELIMITER ;

-- ============================================
-- SAMPLE DATA INSERTION
-- ============================================

-- Insert sample admin user (password: Admin@123 - should be properly hashed in production)
INSERT INTO users (email, password_hash, first_name, last_name, role, status, email_verified)
VALUES 
    ('admin@teamheartrw.org', '$2y$10$examplehash', 'System', 'Administrator', 'admin', 'active', TRUE),
    ('manager@teamheartrw.org', '$2y$10$examplehash', 'John', 'Doe', 'manager', 'active', TRUE),
    ('staff@teamheartrw.org', '$2y$10$examplehash', 'Jane', 'Smith', 'staff', 'active', TRUE);

-- Insert sample member
INSERT INTO members (
    user_id, first_name, last_name, email, phone, position, 
    department, employee_id, status, created_by
)
VALUES 
    (2, 'Mugumanyi', 'Faustin', 'faustin@teamheartrw.org', '+250788123456', 
     'Cardiac Nurse Educator and Mentor', 'Medical', 'TH-2025-001', 'active', 1);

-- Insert sample mission authorization
CALL sp_create_mission_authorization(
    2, -- user_id
    1, -- member_id
    'October pre-screening activities',
    'Kigali – Kibogora – Kabyayi',
    '2025-09-10',
    '2025-09-12',
    @auth_id,
    @auth_number
);

-- Approve the sample authorization
CALL sp_approve_mission_authorization(
    @auth_id,
    1,
    'MUREKEZI Dan Rene',
    'Finance and Administration Officer',
    'Approved for mission activities'
);

-- ============================================
-- USEFUL QUERIES
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
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Additional composite indexes for common queries
CREATE INDEX idx_member_user_status ON members(user_id, status);
CREATE INDEX idx_mission_member_status ON mission_authorizations(member_id, status);
CREATE INDEX idx_mission_dates ON mission_authorizations(departure_date, return_date);

-- ============================================
-- END OF SCHEMA
-- ============================================