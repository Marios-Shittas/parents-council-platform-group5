<?php
/**
 * ApplicationsService - Handles all application-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class ApplicationsService {
    private $conn;
    private $hasAdminSeenAtColumn = null;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->ensureSubmissionSeenColumn();
    }

    /**
     * Ensure submissions table supports persistent admin read state.
     * @return bool True when admin_seen_at exists
     */
    private function ensureSubmissionSeenColumn() {
        if ($this->hasAdminSeenAtColumn !== null) {
            return (bool)$this->hasAdminSeenAtColumn;
        }

        $check = $this->conn->query("SHOW COLUMNS FROM Submissions LIKE 'admin_seen_at'");
        if ($check && (int)$check->num_rows > 0) {
            $this->hasAdminSeenAtColumn = true;
            return true;
        }

        $alterSql = "ALTER TABLE Submissions ADD COLUMN admin_seen_at DATETIME NULL DEFAULT NULL AFTER submitted_at";
        if (!$this->conn->query($alterSql)) {
            $this->hasAdminSeenAtColumn = false;
            return false;
        }

        $recheck = $this->conn->query("SHOW COLUMNS FROM Submissions LIKE 'admin_seen_at'");
        $this->hasAdminSeenAtColumn = $recheck && (int)$recheck->num_rows > 0;
        return (bool)$this->hasAdminSeenAtColumn;
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

    /**
     * Update an existing document path.
     * @param int $documentId Document ID
     * @param string $filePath New file path
     * @return bool True on success, false on failure
     */
    public function updateDocumentPath($documentId, $filePath) {
        $sql = "UPDATE ApplicationsDocuments SET file_path = ? WHERE ap_document_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $filePath, $documentId);

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
        $hasSeenColumn = $this->ensureSubmissionSeenColumn();
        $adminSeenSelect = $hasSeenColumn ? 's.admin_seen_at' : 'NULL AS admin_seen_at';
        $sql = "SELECT 
                    s.application_id,
                    s.user_id,
                    s.file_path,
                    s.sub_status,
                    s.submission_data,
                    s.submitted_at,
                    {$adminSeenSelect},
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
     * Get count of submissions that are still waiting review.
     * @return int Total waiting submissions
     */
    public function getWaitingSubmissionCount() {
        if ($this->ensureSubmissionSeenColumn()) {
            $sql = "SELECT COUNT(*) AS count FROM Submissions WHERE sub_status = 'waiting' AND admin_seen_at IS NULL";
        } else {
            $sql = "SELECT COUNT(*) AS count FROM Submissions WHERE sub_status = 'waiting'";
        }

        $result = $this->conn->query($sql);
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int)($row['count'] ?? 0);
    }

    /**
     * Get unread waiting submissions count for a specific application.
     * @param int $applicationId Application ID
     * @return int Total waiting unread submissions for application
     */
    public function getWaitingSubmissionCountByApplication($applicationId) {
        if ($this->ensureSubmissionSeenColumn()) {
            $sql = "SELECT COUNT(*) AS count
                    FROM Submissions
                    WHERE application_id = ?
                      AND sub_status = 'waiting'
                      AND admin_seen_at IS NULL";
        } else {
            $sql = "SELECT COUNT(*) AS count
                    FROM Submissions
                    WHERE application_id = ?
                      AND sub_status = 'waiting'";
        }

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        return (int)($row['count'] ?? 0);
    }

    /**
     * Mark a waiting submission as seen by admin.
     * @param int $applicationId Application ID
     * @param int $userId User ID
     * @return bool True when query executes successfully
     */
    public function markSubmissionAsSeen($applicationId, $userId) {
        if (!$this->ensureSubmissionSeenColumn()) {
            return false;
        }

        $sql = "UPDATE Submissions
                SET admin_seen_at = NOW()
                WHERE application_id = ?
                  AND user_id = ?
                  AND sub_status = 'waiting'
                  AND admin_seen_at IS NULL";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ii", $applicationId, $userId);
        return $stmt->execute();
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

    // ============================================
    // APPLICATION FORM FIELDS METHODS
    // ============================================

    /**
     * Λήψη όλων των πεδίων φόρμας για μια αίτηση
     * @param int $applicationId ID της αίτησης
     * @return array Πίνακας με τα πεδία
     */
    public function getApplicationFormFields($applicationId) {
        $sql = "SELECT * FROM ApplicationsFormFields 
                WHERE application_id = ? 
                ORDER BY field_order ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $fields = [];
        while ($row = $result->fetch_assoc()) {
            $fields[] = $row;
        }
        return $fields;
    }

    /**
     * Προσθήκη νέου πεδίου φόρμας
     * @param int $applicationId ID της αίτησης
     * @param string $fieldName Όνομα του πεδίου
     * @param string $fieldType Τύπος του πεδίου (text, email, tel, κ.λπ.)
     * @param int $fieldOrder Σειρά εμφάνισης
     * @param bool $isRequired Απαιτείται ή όχι;
     * @return bool Επιτυχία ή αποτυχία
     */
    public function addFormField($applicationId, $fieldName, $fieldType = 'text', $fieldOrder = 0, $isRequired = true) {
        $sql = "INSERT INTO ApplicationsFormFields 
                (application_id, field_name, field_type, field_order, is_required) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $isReq = $isRequired ? 1 : 0;
        $stmt->bind_param("issii", $applicationId, $fieldName, $fieldType, $fieldOrder, $isReq);
        return $stmt->execute();
    }

    /**
     * Ενημέρωση πεδίου φόρμας
     * @param int $fieldId ID του πεδίου
     * @param string $fieldName Νέο όνομα
     * @param string $fieldType Νέος τύπος
     * @param bool $isRequired Απαιτείται ή όχι;
     * @return bool Επιτυχία ή αποτυχία
     */
    public function updateFormField($fieldId, $fieldName, $fieldType = 'text', $isRequired = true) {
        $sql = "UPDATE ApplicationsFormFields 
                SET field_name = ?, field_type = ?, is_required = ? 
                WHERE field_id = ?";
        $stmt = $this->conn->prepare($sql);
        $isReq = $isRequired ? 1 : 0;
        $stmt->bind_param("ssii", $fieldName, $fieldType, $isReq, $fieldId);
        return $stmt->execute();
    }

    /**
     * Διαγραφή πεδίου φόρμας
     * @param int $fieldId ID του πεδίου
     * @return bool Επιτυχία ή αποτυχία
     */
    public function deleteFormField($fieldId) {
        $sql = "DELETE FROM ApplicationsFormFields WHERE field_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $fieldId);
        return $stmt->execute();
    }

    /**
     * Αναδιάταξη των πεδίων
     * @param array $fieldIds Πίνακας με τα IDs των πεδίων στη σωστή σειρά
     * @return bool Επιτυχία ή αποτυχία
     */
    public function reorderFormFields($fieldIds) {
        foreach ($fieldIds as $order => $fieldId) {
            $sql = "UPDATE ApplicationsFormFields SET field_order = ? WHERE field_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $order, $fieldId);
            if (!$stmt->execute()) {
                return false;
            }
        }
        return true;
    }

    // ============================================================
    // NEW: Enhanced Application Management (v2)
    // ============================================================
    
    /**
     * Create application from template or blank
     */
    public function createApplicationFromTemplate($templateId, $title, $description, $academicYear, $openDate, $dueDate, $allowOnline = true, $allowFile = true, $requireSignature = false, $createdBy = null) {
        $formSchema = null;
        if ($templateId) {
            // Get template schema
            $sql = "SELECT form_schema FROM ApplicationTemplates WHERE template_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $templateId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $formSchema = $row['form_schema'];
            }
        }
        
        $sql = "INSERT INTO Applications 
               (template_id, application_title, title, application_description, description,
                academic_year, open_date, due_date, status,
                allow_online_submission, allow_file_submission, require_signature,
                form_schema, created_by)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "isssssssiiisi",
            $templateId, $title, $title, $description, $description,
            $academicYear, $openDate, $dueDate,
            $allowOnline, $allowFile, $requireSignature,
            $formSchema, $createdBy
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }
    
    /**
     * Get application with all details (new schema)
     */
    public function getApplicationWithDetails($applicationId) {
        $sql = "SELECT a.*, t.name as template_name, t.template_key
                FROM Applications a
                LEFT JOIN ApplicationTemplates t ON a.template_id = t.template_id
                WHERE a.application_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Parse JSON fields
            if ($row['form_schema']) {
                $row['form_schema'] = json_decode($row['form_schema'], true);
            }
            if ($row['target_audience']) {
                $row['target_audience'] = json_decode($row['target_audience'], true);
            }
            
            // Get attachments
            $row['attachments'] = $this->getApplicationAttachments($applicationId);
            
            return $row;
        }
        return null;
    }
    
    /**
     * Update application with new schema
     */
    public function updateApplicationDetails($applicationId, $title, $description, $academicYear, $openDate, $dueDate, $allowOnline, $allowFile, $requireSignature, $formSchema = null) {
        $formSchemaJson = $formSchema ? json_encode($formSchema, JSON_UNESCAPED_UNICODE) : null;
        
        $sql = "UPDATE Applications 
                SET application_title = ?, title = ?,
                    application_description = ?, description = ?,
                    academic_year = ?, open_date = ?, due_date = ?,
                    allow_online_submission = ?, allow_file_submission = ?,
                    require_signature = ?, form_schema = ?,
                    updated_at = NOW()
                WHERE application_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssssssiiis",
            $title, $title,
            $description, $description,
            $academicYear, $openDate, $dueDate,
            $allowOnline, $allowFile, $requireSignature,
            $formSchemaJson, $applicationId
        );
        
        return $stmt->execute();
    }
    
    /**
     * Publish application (change status from draft to published)
     */
    public function publishApplication($applicationId) {
        $sql = "UPDATE Applications SET status = 'published', updated_at = NOW() WHERE application_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $applicationId);
        return $stmt->execute();
    }
    
    /**
     * Close application (prevent new submissions)
     */
    public function closeApplication($applicationId) {
        $sql = "UPDATE Applications SET status = 'closed', updated_at = NOW() WHERE application_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $applicationId);
        return $stmt->execute();
    }
    
    /**
     * Add attachment to application
     */
    public function addApplicationAttachment($applicationId, $filePath, $originalFilename) {
        // Get highest order
        $sql = "SELECT MAX(upload_order) as max_order FROM ApplicationAttachments WHERE application_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = 0;
        if ($row = $result->fetch_assoc()) {
            $order = ($row['max_order'] ?? -1) + 1;
        }
        
        $sql = "INSERT INTO ApplicationAttachments (application_id, file_path, original_filename, upload_order)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issi", $applicationId, $filePath, $originalFilename, $order);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }
    
    /**
     * Get attachments for application
     */
    public function getApplicationAttachments($applicationId) {
        $sql = "SELECT * FROM ApplicationAttachments WHERE application_id = ? ORDER BY upload_order ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $attachments = [];
        while ($row = $result->fetch_assoc()) {
            $attachments[] = $row;
        }
        return $attachments;
    }
    
    /**
     * Delete attachment
     */
    public function deleteApplicationAttachment($attachmentId) {
        // Get file path first
        $sql = "SELECT file_path FROM ApplicationAttachments WHERE attachment_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $attachmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $filePath = null;
        if ($row = $result->fetch_assoc()) {
            $filePath = $row['file_path'];
        }
        
        // Delete from DB
        $sql = "DELETE FROM ApplicationAttachments WHERE attachment_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $attachmentId);
        $deleted = $stmt->execute();
        
        // Delete file
        if ($deleted && $filePath) {
            $fullPath = __DIR__ . '/../../' . $filePath;
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
        
        return $deleted;
    }
    
    /**
     * Create submission in new ApplicationSubmissions table
     */
    public function createApplicationSubmission($applicationId, $userId, $submissionType, $formData = null, $uploadedFiles = null, $signatureData = null) {
        $formDataJson = $formData ? json_encode($formData, JSON_UNESCAPED_UNICODE) : null;
        $uploadedFilesJson = $uploadedFiles ? json_encode($uploadedFiles, JSON_UNESCAPED_UNICODE) : null;
        
        $sql = "INSERT INTO ApplicationSubmissions 
                (application_id, user_id, submission_type, status, form_data, uploaded_files, signature_data)
                VALUES (?, ?, ?, 'submitted', ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "isssss",
            $applicationId, $userId, $submissionType,
            $formDataJson, $uploadedFilesJson, $signatureData
        );
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }
    
    /**
     * Get submissions for application (new table)
     */
    public function getApplicationSubmissions($applicationId) {
        $sql = "SELECT s.*, u.name, u.surname, u.email
                FROM ApplicationSubmissions s
                JOIN Users u ON s.user_id = u.user_id
                WHERE s.application_id = ?
                ORDER BY s.submitted_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $submissions = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['form_data']) {
                $row['form_data'] = json_decode($row['form_data'], true);
            }
            if ($row['uploaded_files']) {
                $row['uploaded_files'] = json_decode($row['uploaded_files'], true);
            }
            $submissions[] = $row;
        }
        return $submissions;
    }
    
    /**
     * Get published applications (for parent portal)
     */
    public function getPublishedApplications() {
        $sql = "SELECT a.* FROM Applications a 
                WHERE a.status = 'published'
                ORDER BY a.due_date ASC, a.open_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $applications = [];
        while ($row = $result->fetch_assoc()) {
            $applications[] = $row;
        }
        return $applications;
    }
}
?>
