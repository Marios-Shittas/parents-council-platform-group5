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
        $sql = "SELECT 1 FROM Submissions WHERE application_id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $applicationId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    /**
     * Create a new submission (legacy file-upload path)
     */
    public function createSubmission($applicationId, $userId, $filePath) {
        $sql = "INSERT INTO Submissions (application_id, user_id, file_path, sub_status)
                VALUES (?, ?, ?, 'waiting')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $applicationId, $userId, $filePath);
        return $stmt->execute();
    }

    /**
     * Create a new submission with JSON form data (no file required)
     * @param int $applicationId Application ID
     * @param int $userId User ID
     * @param string $submissionDataJson JSON-encoded form fields
     * @return bool True on success, false on failure
     */
    public function createSubmissionWithData($applicationId, $userId, $submissionDataJson) {
        return $this->createSubmissionWithDataAndFile($applicationId, $userId, $submissionDataJson, null);
    }

    /**
     * Create a new submission with JSON form data and optional uploaded file path.
     * @param int $applicationId Application ID
     * @param int $userId User ID
     * @param string $submissionDataJson JSON-encoded form fields
     * @param string|null $filePath Optional uploaded file path
     * @return bool True on success, false on failure
     */
    public function createSubmissionWithDataAndFile($applicationId, $userId, $submissionDataJson, $filePath = null) {
        if ($filePath === null || $filePath === '') {
            $sql = "INSERT INTO Submissions (application_id, user_id, file_path, submission_data, sub_status)
                    VALUES (?, ?, NULL, ?, 'waiting')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iis", $applicationId, $userId, $submissionDataJson);
            return $stmt->execute();
        }

        $sql = "INSERT INTO Submissions (application_id, user_id, file_path, submission_data, sub_status)
                VALUES (?, ?, ?, ?, 'waiting')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiss", $applicationId, $userId, $filePath, $submissionDataJson);
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
                    s.sub_status,
                    s.submission_data,
                    s.submitted_at,
                    a.application_title
                FROM Submissions s
                INNER JOIN Applications a ON s.application_id = a.application_id
                WHERE s.user_id = ?
                ORDER BY s.submitted_at DESC";
        
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
                    s.submission_data,
                    s.submitted_at,
                    a.application_title,
                    u.name,
                    u.surname,
                    u.email
                FROM Submissions s
                INNER JOIN Applications a ON s.application_id = a.application_id
                INNER JOIN Users u ON s.user_id = u.user_id
                ORDER BY s.submitted_at DESC, u.surname ASC, u.name ASC";
        
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
     * Delete a submission so user can submit again.
     * @param int $applicationId Application ID
     * @param int $userId User ID
     * @return bool True on success, false on failure
     */
    public function deleteSubmission($applicationId, $userId) {
        $sql = "DELETE FROM Submissions WHERE application_id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $applicationId, $userId);
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
