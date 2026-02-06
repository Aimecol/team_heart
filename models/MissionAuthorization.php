<?php
/**
 * Mission Authorization Model
 */

require_once __DIR__ . '/../config/database.php';

class MissionAuthorization {
    private $conn;
    private $table_name = "mission_authorizations";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create new mission authorization
     */
    public function create($data, $user_id) {
        // Generate authorization number
        $authorization_number = $this->generateAuthorizationNumber();
        
        // Calculate duration
        $departure = new DateTime($data['departure_date']);
        $return = new DateTime($data['return_date']);
        $duration = $return->diff($departure)->days + 1;

        $query = "INSERT INTO " . $this->table_name . "
                (user_id, member_id, authorization_number, mission_purpose, destination,
                departure_date, return_date, duration_days, status, created_by)
                VALUES
                (:user_id, :member_id, :authorization_number, :mission_purpose, :destination,
                :departure_date, :return_date, :duration_days, 'draft', :created_by)";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $mission_purpose = htmlspecialchars(strip_tags($data['mission_purpose']));
        $destination = htmlspecialchars(strip_tags($data['destination']));

        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":member_id", $data['member_id']);
        $stmt->bindParam(":authorization_number", $authorization_number);
        $stmt->bindParam(":mission_purpose", $mission_purpose);
        $stmt->bindParam(":destination", $destination);
        $stmt->bindParam(":departure_date", $data['departure_date']);
        $stmt->bindParam(":return_date", $data['return_date']);
        $stmt->bindParam(":duration_days", $duration);
        $stmt->bindParam(":created_by", $user_id);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Generate unique authorization number
     */
    private function generateAuthorizationNumber() {
        $year = date('Y');
        
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                WHERE YEAR(created_at) = :year";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":year", $year);
        $stmt->execute();
        
        $result = $stmt->fetch();
        $count = $result['count'] + 1;
        
        return 'MA-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get all authorizations for a user
     */
    public function getAllByUser($user_id) {
        $query = "SELECT ma.*, 
                        CONCAT(m.first_name, ' ', m.last_name) as traveler_name,
                        m.position as traveler_position
                FROM " . $this->table_name . " ma
                INNER JOIN members m ON ma.member_id = m.member_id
                WHERE ma.user_id = :user_id
                ORDER BY ma.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get authorization by ID
     */
    public function getById($authorization_id, $user_id = null) {
        $query = "SELECT ma.*, 
                        CONCAT(m.first_name, ' ', m.last_name) as traveler_name,
                        m.position as traveler_position,
                        m.first_name, m.last_name
                FROM " . $this->table_name . " ma
                INNER JOIN members m ON ma.member_id = m.member_id
                WHERE ma.authorization_id = :authorization_id";
        
        if ($user_id !== null) {
            $query .= " AND ma.user_id = :user_id";
        }
        
        $query .= " LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":authorization_id", $authorization_id);
        
        if ($user_id !== null) {
            $stmt->bindParam(":user_id", $user_id);
        }
        
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Update mission authorization
     */
    public function update($authorization_id, $data, $user_id) {
        // Calculate duration
        $departure = new DateTime($data['departure_date']);
        $return = new DateTime($data['return_date']);
        $duration = $return->diff($departure)->days + 1;

        $query = "UPDATE " . $this->table_name . "
                SET member_id = :member_id,
                    mission_purpose = :mission_purpose,
                    destination = :destination,
                    departure_date = :departure_date,
                    return_date = :return_date,
                    duration_days = :duration_days,
                    updated_by = :updated_by
                WHERE authorization_id = :authorization_id 
                AND user_id = :user_id
                AND status = 'draft'";

        $stmt = $this->conn->prepare($query);

        $mission_purpose = htmlspecialchars(strip_tags($data['mission_purpose']));
        $destination = htmlspecialchars(strip_tags($data['destination']));

        $stmt->bindParam(":member_id", $data['member_id']);
        $stmt->bindParam(":mission_purpose", $mission_purpose);
        $stmt->bindParam(":destination", $destination);
        $stmt->bindParam(":departure_date", $data['departure_date']);
        $stmt->bindParam(":return_date", $data['return_date']);
        $stmt->bindParam(":duration_days", $duration);
        $stmt->bindParam(":updated_by", $user_id);
        $stmt->bindParam(":authorization_id", $authorization_id);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    /**
     * Delete authorization
     */
    public function delete($authorization_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . "
                WHERE authorization_id = :authorization_id 
                AND user_id = :user_id
                AND status = 'draft'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":authorization_id", $authorization_id);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    /**
     * Approve authorization
     */
    public function approve($authorization_id, $approved_data, $user_id) {
        $query = "UPDATE " . $this->table_name . "
                SET status = 'approved',
                    authorized_by = :authorized_by,
                    authorized_by_position = :authorized_by_position,
                    authorization_date = :authorization_date,
                    updated_by = :updated_by
                WHERE authorization_id = :authorization_id";

        $stmt = $this->conn->prepare($query);

        $authorized_by = htmlspecialchars(strip_tags($approved_data['authorized_by']));
        $authorized_by_position = htmlspecialchars(strip_tags($approved_data['authorized_by_position']));

        $stmt->bindParam(":authorized_by", $authorized_by);
        $stmt->bindParam(":authorized_by_position", $authorized_by_position);
        $stmt->bindParam(":authorization_date", $approved_data['authorization_date']);
        $stmt->bindParam(":updated_by", $user_id);
        $stmt->bindParam(":authorization_id", $authorization_id);

        return $stmt->execute();
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats($user_id) {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN departure_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming
                FROM " . $this->table_name . "
                WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetch();
    }
}
?>