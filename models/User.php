<?php
/**
 * User Model
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $email;
    public $password_hash;
    public $first_name;
    public $last_name;
    public $phone;
    public $role;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Register new user
     */
    public function register() {
        // Start transaction
        $this->conn->beginTransaction();
        
        try {
            $query = "INSERT INTO " . $this->table_name . "
                    (email, password_hash, first_name, last_name, phone, role, status)
                    VALUES
                    (:email, :password_hash, :first_name, :last_name, :phone, 'member', 'pending')";

            $stmt = $this->conn->prepare($query);

            // Sanitize
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->first_name = htmlspecialchars(strip_tags($this->first_name));
            $this->last_name = htmlspecialchars(strip_tags($this->last_name));
            $this->phone = htmlspecialchars(strip_tags($this->phone));

            // Hash password
            $password_hash = password_hash($this->password_hash, PASSWORD_BCRYPT);

            // Bind values
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password_hash", $password_hash);
            $stmt->bindParam(":first_name", $this->first_name);
            $stmt->bindParam(":last_name", $this->last_name);
            $stmt->bindParam(":phone", $this->phone);

            $stmt->execute();
            $user_id = $this->conn->lastInsertId();
            
            // Generate employee ID
            $employee_id = $this->generateEmployeeId();
            
            // Create member record
            $member_query = "INSERT INTO members 
                    (user_id, first_name, last_name, email, phone, position, employee_id, created_by)
                    VALUES
                    (:user_id, :first_name, :last_name, :email, :phone, 'Team Member', :employee_id, :user_id)";
            
            $member_stmt = $this->conn->prepare($member_query);
            $member_stmt->bindParam(":user_id", $user_id);
            $member_stmt->bindParam(":first_name", $this->first_name);
            $member_stmt->bindParam(":last_name", $this->last_name);
            $member_stmt->bindParam(":email", $this->email);
            $member_stmt->bindParam(":phone", $this->phone);
            $member_stmt->bindParam(":employee_id", $employee_id);
            $member_stmt->execute();
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    /**
     * Login user
     */
    public function login() {
        $query = "SELECT user_id, email, password_hash, first_name, last_name, role, status
                FROM " . $this->table_name . "
                WHERE email = :email
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();

            // Check if user is pending, rejected, or suspended
            if ($row['status'] === 'pending') {
                return ['error' => 'Your account is pending approval. Please wait for admin activation.'];
            } elseif ($row['status'] === 'rejected') {
                return ['error' => 'Your account has been rejected. Please contact the administrator.'];
            } elseif ($row['status'] === 'suspended') {
                return ['error' => 'Your account has been suspended. Please contact the administrator.'];
            }

            if (password_verify($this->password_hash, $row['password_hash'])) {
                // Update last login
                $this->updateLastLogin($row['user_id']);

                return $row;
            }
        }
        return false;
    }

    /**
     * Check if email exists
     */
    public function emailExists() {
        $query = "SELECT user_id FROM " . $this->table_name . "
                WHERE email = :email LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Get user by ID
     */
    public function getUserById($user_id) {
        $query = "SELECT user_id, email, first_name, last_name, phone, role, status, created_at
                FROM " . $this->table_name . "
                WHERE user_id = :user_id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Update last login
     */
    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_name . "
                SET last_login = NOW()
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
    }

    /**
     * Get all users
     */
    public function getAllUsers() {
        $query = "SELECT user_id, email, first_name, last_name, phone, role, status, created_at
                FROM " . $this->table_name . "
                ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Update user profile
     */
    public function updateProfile($user_id) {
        $query = "UPDATE " . $this->table_name . "
                SET first_name = :first_name,
                    last_name = :last_name,
                    phone = :phone
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));

        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    /**
     * Get all members (for admin)
     */
    public function getAllMembers() {
        $query = "SELECT u.*, m.member_id, m.position, m.department, m.employee_id
                FROM " . $this->table_name . " u
                LEFT JOIN members m ON u.user_id = m.user_id
                WHERE u.role = 'member'
                ORDER BY u.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Update user status (for admin)
     */
    public function updateStatus($user_id, $status) {
        $query = "UPDATE " . $this->table_name . "
                SET status = :status
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    /**
     * Get pending members count
     */
    public function getPendingCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                WHERE role = 'member' AND status = 'pending'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch();

        return $result['count'];
    }

    /**
     * Update user password
     */
    public function updatePassword($user_id, $new_password) {
        $query = "UPDATE " . $this->table_name . "
                SET password_hash = :password_hash
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    /**
     * Verify password
     */
    public function verifyPassword($user_id, $password) {
        $query = "SELECT password_hash FROM " . $this->table_name . "
                WHERE user_id = :user_id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            return password_verify($password, $row['password_hash']);
        }
        return false;
    }

    /**
     * Generate unique employee ID
     */
    private function generateEmployeeId() {
        // Get the highest ID number from existing employee IDs
        $query = "SELECT employee_id FROM members
                WHERE employee_id IS NOT NULL
                ORDER BY member_id DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch();
        $nextNumber = 1;
        
        if ($result && !empty($result['employee_id'])) {
            // Extract the numeric part from the last ID (TH-YYYY-XXXX)
            $lastId = $result['employee_id'];
            $parts = explode('-', $lastId);
            if (count($parts) >= 3) {
                $lastNumber = intval(end($parts));
                $nextNumber = $lastNumber + 1;
            }
        }
        
        $year = date('Y');
        return 'TH-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
?>