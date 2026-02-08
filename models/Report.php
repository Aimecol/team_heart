<?php
/**
 * Report Model
 * Manages professional reports with WYSIWYG content and file attachments
 */

require_once __DIR__ . '/../config/database.php';

class Report {
    private $conn;
    private $table = "reports";
    private $attachments_table = "report_attachments";
    private $approvals_table = "report_approvals";
    
    private $upload_dir = __DIR__ . '/../uploads/reports/';
    private $max_file_size = 10485760; // 10MB
    private $allowed_extensions = ['pdf', 'docx', 'xlsx', 'pptx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip'];

    public function __construct($db) {
        $this->conn = $db;
        $this->ensureUploadDirectory();
    }

    /**
     * Ensure upload directory exists
     */
    private function ensureUploadDirectory() {
        if (!is_dir($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

    /**
     * Generate unique report number
     */
    private function generateReportNumber() {
        $year = date('Y');
        $month = date('m');
        
        $query = "SELECT COUNT(*) as count FROM " . $this->table . "
                WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":year", $year);
        $stmt->bindParam(":month", $month);
        $stmt->execute();
        
        $result = $stmt->fetch();
        $count = $result['count'] + 1;
        
        return 'RPT-' . $year . $month . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create new report
     */
    public function create($data, $user_id, $member_id) {
        try {
            $report_number = $this->generateReportNumber();
            
            $query = "INSERT INTO " . $this->table . "
                    (user_id, member_id, report_number, title, description, content,
                     report_type, priority, report_date, period_start_date, period_end_date,
                     status, visibility, category, tags, keywords, created_by)
                    VALUES
                    (:user_id, :member_id, :report_number, :title, :description, :content,
                     :report_type, :priority, :report_date, :period_start_date, :period_end_date,
                     :status, :visibility, :category, :tags, :keywords, :created_by)";
            
            $stmt = $this->conn->prepare($query);
            
            // Sanitize inputs
            $title = htmlspecialchars(strip_tags($data['title']));
            $description = htmlspecialchars(strip_tags($data['description'] ?? ''));
            $content = $data['content']; // WYSIWYG content (keep HTML)
            $report_type = $data['report_type'] ?? 'activity';
            $priority = $data['priority'] ?? 'medium';
            $visibility = $data['visibility'] ?? 'team';
            $category = htmlspecialchars(strip_tags($data['category'] ?? ''));
            $status = $data['status'] ?? 'draft';
            
            // Handle tags if provided as JSON
            $tags = isset($data['tags']) ? json_encode($data['tags']) : null;
            $keywords = $data['keywords'] ?? '';
            
            // Report dates
            $report_date = $data['report_date'] ?? date('Y-m-d');
            $period_start_date = $data['period_start_date'] ?? null;
            $period_end_date = $data['period_end_date'] ?? null;
            
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":member_id", $member_id);
            $stmt->bindParam(":report_number", $report_number);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":content", $content);
            $stmt->bindParam(":report_type", $report_type);
            $stmt->bindParam(":priority", $priority);
            $stmt->bindParam(":report_date", $report_date);
            $stmt->bindParam(":period_start_date", $period_start_date);
            $stmt->bindParam(":period_end_date", $period_end_date);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":visibility", $visibility);
            $stmt->bindParam(":category", $category);
            $stmt->bindParam(":tags", $tags);
            $stmt->bindParam(":keywords", $keywords);
            $stmt->bindParam(":created_by", $user_id);
            
            if (!$stmt->execute()) {
                return false;
            }
            
            return $this->conn->lastInsertId();
        } catch (Exception $e) {
            error_log("Report creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update report
     */
    public function update($report_id, $data, $user_id) {
        try {
            $query = "UPDATE " . $this->table . "
                    SET title = :title,
                        description = :description,
                        content = :content,
                        report_type = :report_type,
                        priority = :priority,
                        report_date = :report_date,
                        period_start_date = :period_start_date,
                        period_end_date = :period_end_date,
                        visibility = :visibility,
                        category = :category,
                        tags = :tags,
                        keywords = :keywords,
                        updated_by = :updated_by
                    WHERE report_id = :report_id";
            
            $stmt = $this->conn->prepare($query);
            
            $title = htmlspecialchars(strip_tags($data['title']));
            $description = htmlspecialchars(strip_tags($data['description'] ?? ''));
            $content = $data['content'];
            $report_type = $data['report_type'] ?? 'activity';
            $priority = $data['priority'] ?? 'medium';
            $visibility = $data['visibility'] ?? 'team';
            $category = htmlspecialchars(strip_tags($data['category'] ?? ''));
            $report_date = $data['report_date'] ?? date('Y-m-d');
            $period_start_date = $data['period_start_date'] ?? null;
            $period_end_date = $data['period_end_date'] ?? null;
            $tags = isset($data['tags']) ? json_encode($data['tags']) : null;
            $keywords = $data['keywords'] ?? '';
            
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":content", $content);
            $stmt->bindParam(":report_type", $report_type);
            $stmt->bindParam(":priority", $priority);
            $stmt->bindParam(":report_date", $report_date);
            $stmt->bindParam(":period_start_date", $period_start_date);
            $stmt->bindParam(":period_end_date", $period_end_date);
            $stmt->bindParam(":visibility", $visibility);
            $stmt->bindParam(":category", $category);
            $stmt->bindParam(":tags", $tags);
            $stmt->bindParam(":keywords", $keywords);
            $stmt->bindParam(":updated_by", $user_id);
            $stmt->bindParam(":report_id", $report_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Report update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get report by ID
     */
    public function getById($report_id, $user_id = null) {
        try {
            $query = "SELECT r.*, 
                            CONCAT(m.first_name, ' ', m.last_name) as member_name,
                            m.employee_id,
                            m.position,
                            CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                            (SELECT COUNT(*) FROM " . $this->attachments_table . " WHERE report_id = r.report_id) as attachment_count
                    FROM " . $this->table . " r
                    INNER JOIN members m ON r.member_id = m.member_id
                    INNER JOIN users u ON r.created_by = u.user_id
                    WHERE r.report_id = :report_id";
            
            if ($user_id !== null) {
                $query .= " AND (r.user_id = :user_id OR r.visibility = 'public')";
            }
            
            $query .= " LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":report_id", $report_id);
            
            if ($user_id !== null) {
                $stmt->bindParam(":user_id", $user_id);
            }
            
            $stmt->execute();
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Report fetch error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all reports for a user
     */
    public function getAllByUser($user_id, $status = null) {
        try {
            $query = "SELECT r.report_id, r.report_number, r.title, r.description,
                            r.report_type, r.priority, r.status, r.report_date,
                            r.submission_date, r.visibility,
                            CONCAT(m.first_name, ' ', m.last_name) as member_name,
                            m.employee_id,
                            (SELECT COUNT(*) FROM " . $this->attachments_table . " WHERE report_id = r.report_id) as attachment_count
                    FROM " . $this->table . " r
                    INNER JOIN members m ON r.member_id = m.member_id
                    WHERE r.user_id = :user_id";
            
            if ($status) {
                $query .= " AND r.status = :status";
            }
            
            $query .= " ORDER BY r.created_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            
            if ($status) {
                $stmt->bindParam(":status", $status);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Reports fetch error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all reports (admin)
     */
    public function getAllReports($status = null, $report_type = null) {
        try {
            $query = "SELECT r.report_id, r.report_number, r.title, r.description,
                            r.report_type, r.priority, r.status, r.report_date,
                            r.submission_date, r.visibility,
                            CONCAT(m.first_name, ' ', m.last_name) as member_name,
                            m.employee_id,
                            m.position,
                            CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                            (SELECT COUNT(*) FROM " . $this->attachments_table . " WHERE report_id = r.report_id) as attachment_count
                    FROM " . $this->table . " r
                    INNER JOIN members m ON r.member_id = m.member_id
                    INNER JOIN users u ON r.created_by = u.user_id
                    WHERE 1=1";
            
            if ($status) {
                $query .= " AND r.status = :status";
            }
            
            if ($report_type) {
                $query .= " AND r.report_type = :report_type";
            }
            
            $query .= " ORDER BY r.submission_date DESC";
            
            $stmt = $this->conn->prepare($query);
            
            if ($status) {
                $stmt->bindParam(":status", $status);
            }
            
            if ($report_type) {
                $stmt->bindParam(":report_type", $report_type);
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("All reports fetch error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Upload file attachment
     */
    public function uploadAttachment($report_id, $file, $description = '') {
        try {
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'message' => 'File upload failed'];
            }
            
            // Validate file
            $filename = basename($file['name']);
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($file_ext, $this->allowed_extensions)) {
                return ['success' => false, 'message' => 'File type not allowed'];
            }
            
            if ($file['size'] > $this->max_file_size) {
                return ['success' => false, 'message' => 'File size exceeds limit (10MB)'];
            }
            
            // Generate unique filename
            $file_hash = sha256_file($file['tmp_name']);
            $stored_filename = uniqid() . '_' . time() . '.' . $file_ext;
            $file_path = $this->upload_dir . 'report_' . $report_id . '/' . $stored_filename;
            
            // Create directory if not exists
            $dir = dirname($file_path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                return ['success' => false, 'message' => 'Failed to move uploaded file'];
            }
            
            // Determine file type
            $mime_type = mime_content_type($file_path);
            $attachment_type = $this->determineAttachmentType($file_ext, $mime_type);
            
            // Save to database
            $query = "INSERT INTO " . $this->attachments_table . "
                    (report_id, original_filename, stored_filename, file_path, file_url,
                     file_type, file_size, mime_type, attachment_type, description,
                     file_hash, uploaded_by, scan_status)
                    VALUES
                    (:report_id, :original_filename, :stored_filename, :file_path, :file_url,
                     :file_type, :file_size, :mime_type, :attachment_type, :description,
                     :file_hash, :uploaded_by, 'clean')";
            
            $stmt = $this->conn->prepare($query);
            
            $file_url = '/uploads/reports/report_' . $report_id . '/' . $stored_filename;
            $description = htmlspecialchars(strip_tags($description));
            
            $stmt->bindParam(":report_id", $report_id);
            $stmt->bindParam(":original_filename", $filename);
            $stmt->bindParam(":stored_filename", $stored_filename);
            $stmt->bindParam(":file_path", $file_path);
            $stmt->bindParam(":file_url", $file_url);
            $stmt->bindParam(":file_type", $file_ext);
            $stmt->bindParam(":file_size", $file['size']);
            $stmt->bindParam(":mime_type", $mime_type);
            $stmt->bindParam(":attachment_type", $attachment_type);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":file_hash", $file_hash);
            $stmt->bindParam(":uploaded_by", $_SESSION['user_id'] ?? 0);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message' => 'File uploaded successfully',
                    'attachment_id' => $this->conn->lastInsertId(),
                    'file_url' => $file_url
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to save attachment to database'];
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error during file upload'];
        }
    }

    /**
     * Determine attachment type from extension
     */
    private function determineAttachmentType($extension, $mime_type) {
        $extension = strtolower($extension);
        
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'])) {
            return 'image';
        } elseif (in_array($extension, ['pdf', 'docx', 'doc', 'txt'])) {
            return 'document';
        } elseif (in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return 'spreadsheet';
        } elseif (in_array($extension, ['pptx', 'ppt'])) {
            return 'presentation';
        } elseif (in_array($extension, ['mp4', 'avi', 'mov', 'mkv'])) {
            return 'video';
        } elseif (in_array($extension, ['mp3', 'aac', 'wav', 'flac'])) {
            return 'audio';
        } elseif (in_array($extension, ['zip', 'rar', '7z', 'tar'])) {
            return 'archive';
        }
        
        return 'other';
    }

    /**
     * Get report attachments
     */
    public function getAttachments($report_id) {
        try {
            $query = "SELECT ra.*, CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name
                    FROM " . $this->attachments_table . " ra
                    INNER JOIN users u ON ra.uploaded_by = u.user_id
                    WHERE ra.report_id = :report_id
                    ORDER BY ra.uploaded_at DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":report_id", $report_id);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Attachments fetch error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment($attachment_id) {
        try {
            // Get attachment details
            $query = "SELECT * FROM " . $this->attachments_table . " WHERE attachment_id = :attachment_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":attachment_id", $attachment_id);
            $stmt->execute();
            $attachment = $stmt->fetch();
            
            if (!$attachment) {
                return false;
            }
            
            // Delete file
            if (file_exists($attachment['file_path'])) {
                unlink($attachment['file_path']);
            }
            
            // Delete from database
            $query = "DELETE FROM " . $this->attachments_table . " WHERE attachment_id = :attachment_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":attachment_id", $attachment_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Attachment delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete report
     */
    public function delete($report_id, $user_id) {
        try {
            // Delete attachments first
            $attachments = $this->getAttachments($report_id);
            foreach ($attachments as $attachment) {
                $this->deleteAttachment($attachment['attachment_id']);
            }
            
            // Delete report directory
            $report_dir = $this->upload_dir . 'report_' . $report_id;
            if (is_dir($report_dir)) {
                array_map('unlink', glob($report_dir . '/*'));
                rmdir($report_dir);
            }
            
            // Delete report record
            $query = "DELETE FROM " . $this->table . " 
                    WHERE report_id = :report_id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":report_id", $report_id);
            $stmt->bindParam(":user_id", $user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Report delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Submit report for review
     */
    public function submit($report_id, $user_id) {
        try {
            $query = "UPDATE " . $this->table . "
                    SET status = 'submitted',
                        submission_date = NOW(),
                        updated_by = :updated_by
                    WHERE report_id = :report_id AND user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":report_id", $report_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":updated_by", $user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Submit report error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Approve report
     */
    public function approve($report_id, $admin_user_id, $comments = '') {
        try {
            $query = "UPDATE " . $this->table . "
                    SET status = 'approved',
                        approved_by = :approved_by,
                        approved_date = NOW(),
                        review_notes = :review_notes,
                        updated_by = :updated_by
                    WHERE report_id = :report_id";
            
            $stmt = $this->conn->prepare($query);
            $comments = htmlspecialchars(strip_tags($comments));
            
            $stmt->bindParam(":report_id", $report_id);
            $stmt->bindParam(":approved_by", $admin_user_id);
            $stmt->bindParam(":review_notes", $comments);
            $stmt->bindParam(":updated_by", $admin_user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Approve report error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject report
     */
    public function reject($report_id, $admin_user_id, $reason = '') {
        try {
            $query = "UPDATE " . $this->table . "
                    SET status = 'rejected',
                        review_notes = :review_notes,
                        updated_by = :updated_by
                    WHERE report_id = :report_id";
            
            $stmt = $this->conn->prepare($query);
            $reason = htmlspecialchars(strip_tags($reason));
            
            $stmt->bindParam(":report_id", $report_id);
            $stmt->bindParam(":review_notes", $reason);
            $stmt->bindParam(":updated_by", $admin_user_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Reject report error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get pending reports count
     */
    public function getPendingCount() {
        try {
            $query = "SELECT COUNT(*) as count FROM " . $this->table . "
                    WHERE status IN ('draft', 'submitted', 'under-review')";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch();
            
            return $result['count'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get reports dashboard statistics
     */
    public function getDashboardStats($user_id) {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                        SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    FROM " . $this->table . "
                    WHERE user_id = :user_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            
            return $stmt->fetch();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
