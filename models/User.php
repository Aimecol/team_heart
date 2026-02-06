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
        $query = "INSERT INTO " . $this->table_name . "
                (email, password_hash, first_name, last_name, phone, role, status)
                VALUES
                (:email, :password_hash, :first_name, :last_name, :phone, :role, 'active')";

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
        $stmt->bindParam(":role", $this->role);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Login user
     */
    public function login() {
        $query = "SELECT user_id, email, password_hash, first_name, last_name, role, status
                FROM " . $this->table_name . "
                WHERE email = :email AND status = 'active'
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();

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
}
?>