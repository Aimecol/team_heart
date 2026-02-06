<?php
/**
 * Member Model
 */

require_once __DIR__ . '/../config/database.php';

class Member {
    private $conn;
    private $table_name = "members";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new member
     */
    public function create($data, $user_id) {
        $query = "INSERT INTO " . $this->table_name . "
                (user_id, first_name, last_name, middle_name, email, phone, 
                position, department, employee_id, status, created_by)
                VALUES
                (:user_id, :first_name, :last_name, :middle_name, :email, :phone,
                :position, :department, :employee_id, 'active', :created_by)";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        foreach ($data as $key => $value) {
            $data[$key] = htmlspecialchars(strip_tags($value));
        }

        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":first_name", $data['first_name']);
        $stmt->bindParam(":last_name", $data['last_name']);
        $stmt->bindParam(":middle_name", $data['middle_name']);
        $stmt->bindParam(":email", $data['email']);
        $stmt->bindParam(":phone", $data['phone']);
        $stmt->bindParam(":position", $data['position']);
        $stmt->bindParam(":department", $data['department']);
        $stmt->bindParam(":employee_id", $data['employee_id']);
        $stmt->bindParam(":created_by", $user_id);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Get all members for a user
     */
    public function getAllByUser($user_id) {
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE user_id = :user_id AND status = 'active'
                ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get member by ID
     */
    public function getById($member_id, $user_id = null) {
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE member_id = :member_id";
        
        if ($user_id !== null) {
            $query .= " AND user_id = :user_id";
        }
        
        $query .= " LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":member_id", $member_id);
        
        if ($user_id !== null) {
            $stmt->bindParam(":user_id", $user_id);
        }
        
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Update member
     */
    public function update($member_id, $data, $user_id) {
        $query = "UPDATE " . $this->table_name . "
                SET first_name = :first_name,
                    last_name = :last_name,
                    middle_name = :middle_name,
                    email = :email,
                    phone = :phone,
                    position = :position,
                    department = :department,
                    employee_id = :employee_id,
                    updated_by = :updated_by
                WHERE member_id = :member_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        foreach ($data as $key => $value) {
            $data[$key] = htmlspecialchars(strip_tags($value));
        }

        $stmt->bindParam(":first_name", $data['first_name']);
        $stmt->bindParam(":last_name", $data['last_name']);
        $stmt->bindParam(":middle_name", $data['middle_name']);
        $stmt->bindParam(":email", $data['email']);
        $stmt->bindParam(":phone", $data['phone']);
        $stmt->bindParam(":position", $data['position']);
        $stmt->bindParam(":department", $data['department']);
        $stmt->bindParam(":employee_id", $data['employee_id']);
        $stmt->bindParam(":updated_by", $user_id);
        $stmt->bindParam(":member_id", $member_id);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    /**
     * Delete member (soft delete)
     */
    public function delete($member_id, $user_id) {
        $query = "UPDATE " . $this->table_name . "
                SET status = 'inactive',
                    updated_by = :updated_by
                WHERE member_id = :member_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":updated_by", $user_id);
        $stmt->bindParam(":member_id", $member_id);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    /**
     * Check if employee ID exists
     */
    public function employeeIdExists($employee_id, $exclude_member_id = null) {
        $query = "SELECT member_id FROM " . $this->table_name . "
                WHERE employee_id = :employee_id";
        
        if ($exclude_member_id !== null) {
            $query .= " AND member_id != :exclude_member_id";
        }
        
        $query .= " LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":employee_id", $employee_id);
        
        if ($exclude_member_id !== null) {
            $stmt->bindParam(":exclude_member_id", $exclude_member_id);
        }
        
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    /**
     * Get member full name
     */
    public function getFullName($member_id) {
        $member = $this->getById($member_id);
        if ($member) {
            return $member['first_name'] . ' ' . $member['last_name'];
        }
        return '';
    }
}
?>