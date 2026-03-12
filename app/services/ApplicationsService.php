<?php
/**
 * ApplicationsService - Handles all application-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class ApplicationsService {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    // ============================================
    // APPLICATION METHODS
    // ============================================
    
    /**
     * Get all applications with document count
     * @return array Array of applications
     */
    public function getAllApplications() {
        $sql = "SELECT 
                    a.application_id,
                    a.application_title,
                    a.application_description,
                    a.submission_type,
                    COUNT(ad.ap_document_id) AS document_count
                FROM Applications a
                LEFT JOIN ApplicationsDocuments ad ON a.application_id = ad.application_id
                GROUP BY a.application_id
                ORDER BY a.application_id DESC";
        
        $result = $this->conn->query($sql);
        $applications = [];
        
        while ($row = $result->fetch_assoc()) {
            $applications[] = $row;
        }
        
        return $applications;
    }
    
    /**
     * Get a single application by ID
     * @param int $id Application ID
     * @return array|null Application data or null if not found
     */
    public function getApplicationById($id) {
        $sql = "SELECT * FROM Applications WHERE application_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Create a new application
     * @param string $title Application title
     * @param string $description Application description
     * @return int|false The new application ID or false on failure
     */
    public function createApplication($title, $description) {
        $sql = "INSERT INTO Applications (application_title, application_description) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $title, $description);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update an existing application
     * @param int $id Application ID
     * @param string $title Application title
     * @param string $description Application description
     * @return bool True on success, false on failure
     */
    public function updateApplication($id, $title, $description) {
        $sql = "UPDATE Applications 
                SET application_title = ?, application_description = ?
                WHERE application_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssi", $title, $description, $id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete an application
     * @param int $id Application ID
     * @return bool True on success, false on failure
     */
    public function deleteApplication($id) {
        $sql = "DELETE FROM Applications WHERE application_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    // ============================================
    // DOCUMENT METHODS
    // ============================================
    
    /**
     * Get all documents grouped by application
     * @return array Array of documents grouped by application_id
     */
    public function getDocumentsByApplication() {
        $sql = "SELECT ap_document_id, application_id, file_path 
                FROM ApplicationsDocuments 
                ORDER BY ap_document_id DESC";
        
        $result = $this->conn->query($sql);
        $documentsByApplication = [];
        
        while ($doc = $result->fetch_assoc()) {
            $documentsByApplication[$doc['application_id']][] = $doc;
        }
        
        return $documentsByApplication;
    }
    
    /**
     * Get documents for a specific application
     * @param int $applicationId Application ID
     * @return array Array of documents
     */
    public function getDocuments($applicationId) {
        $sql = "SELECT * FROM ApplicationsDocuments WHERE application_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get all documents with application details
     * @return array Array of documents
     */
    public function getAllDocuments() {
        $sql = "SELECT 
                    ad.ap_document_id,
                    ad.application_id,
                    ad.file_path,
                    a.application_title
                FROM ApplicationsDocuments ad
                INNER JOIN Applications a ON ad.application_id = a.application_id
                ORDER BY ad.ap_document_id DESC";
        
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Add a document to an application
     * @param int $applicationId Application ID
     * @param string $filePath Path to the document file
     * @return bool True on success, false on failure
     */
    public function addDocument($applicationId, $filePath) {
        $sql = "INSERT INTO ApplicationsDocuments (application_id, file_path) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $applicationId, $filePath);
        
        return $stmt->execute();
    }
    
    /**
     * Get document by ID
     * @param int $documentId Document ID
     * @return array|null Document data or null if not found
     */
    public function getDocumentById($documentId) {
        $sql = "SELECT * FROM ApplicationsDocuments WHERE ap_document_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $documentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Delete a document from an application
     * @param int $documentId Document ID
     * @return bool True on success, false on failure
     */
    public function deleteDocument($documentId) {
        $sql = "DELETE FROM ApplicationsDocuments WHERE ap_document_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $documentId);
        
        return $stmt->execute();
    }
    
    // ============================================
    // SUBMISSION METHODS
    // ============================================
    
    /**
     * Check if user already submitted an application
     * @param int $applicationId Application ID
     * @param int $userId User ID
     * @return bool True if already submitted, false otherwise
     */
    public function hasUserSubmitted($applicationId, $userId) {
        $sql = "SELECT submission_id FROM Submissions WHERE application_id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $applicationId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Create a new submission (file upload or text)
     * @param int $applicationId Application ID
     * @param int $userId User ID
     * @param string|null $filePath Path to the submission file (null for text submissions)
     * @param string|null $textContent Text content (null for file submissions)
     * @return bool True on success, false on failure
     */
    public function createSubmission($applicationId, $userId, $filePath, $textContent = null) {
        $sql = "INSERT INTO Submissions (application_id, user_id, file_path, text_content, sub_status)
                VALUES (?, ?, ?, ?, 'waiting')";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiss", $applicationId, $userId, $filePath, $textContent);
        
        return $stmt->execute();
    }
    
    /**
     * Get user's submissions
     * @param int $userId User ID
     * @return array Array of submissions
     */
    public function getUserSubmissions($userId) {
        $sql = "SELECT 
                    s.application_id,
                    s.file_path,
                    s.text_content,
                    s.sub_status,
                    a.application_title,
                    a.submission_type
                FROM Submissions s
                INNER JOIN Applications a ON s.application_id = a.application_id
                WHERE s.user_id = ?
                ORDER BY a.application_title ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get all submissions with user and application details
     * @return array Array of submissions
     */
    public function getAllSubmissions() {
        $sql = "SELECT 
                    s.application_id,
                    s.user_id,
                    s.file_path,
                    s.sub_status,
                    a.application_title,
                    u.name,
                    u.surname,
                    u.email
                FROM Submissions s
                INNER JOIN Applications a ON s.application_id = a.application_id
                INNER JOIN Users u ON s.user_id = u.user_id
                ORDER BY a.application_title ASC, u.surname ASC, u.name ASC";
        
        $result = $this->conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Update submission status
     * @param int $applicationId Application ID
     * @param int $userId User ID
     * @param string $status Status (waiting, approved, rejected)
     * @return bool True on success, false on failure
     */
    public function updateSubmissionStatus($applicationId, $userId, $status) {
        $sql = "UPDATE Submissions 
                SET sub_status = ?
                WHERE application_id = ? AND user_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sii", $status, $applicationId, $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Get total count of applications
     * @return int Total count
     */
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as count FROM Applications";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
}
?>
