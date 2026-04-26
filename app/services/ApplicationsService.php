<?php
// Arxeio: app\services\ApplicationsService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora aitiseis/templates kai uploads, ara ta paths kai ta validation einai simantika.
/**
 * ApplicationsService - xeirizetai oles tis leitourgies aitiseon sti vasi
 */

require_once __DIR__ . '/../config/db.php';

class ApplicationsService {
    private $conn;
    private $hasAdminSeenAtColumn = null;
    
    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->ensureSubmissionSeenColumn();
    }

    /**
     * Eksasfalizei ypovoles pinakas ypostirizei monimi admin diavasma state.
     * @return bool - True otan admin_seen_at yparxei
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
    // Methodoi diaxeirisis aitiseon.
    // ============================================
    
    /**
     * Pairnei ola aitiseis me document count
     * @return array - Pinakas me aitiseis
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
     * Pairnei mia aitisi apo ID
     * @param int $id - Aitisi ID
     * @return array|null - Aitisi dedomena i null an den vrethei
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
     * Dimiourgei mia nea aitisi
     * @param string $title - Aitisi titlos
     * @param string $description - Aitisi perigrafi
     * @return int|false - To neo id aitisis ID i false se apotixia
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
     * Enimeronei mia yparxousa aitisi
     * @param int $id - Aitisi ID
     * @param string $title - Aitisi titlos
     * @param string $description - Aitisi perigrafi
     * @return bool - True se epitixia, false se apotixia
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
     * Diagrafei mia aitisi
     * @param int $id - Aitisi ID
     * @return bool - True se epitixia, false se apotixia
     */
    public function deleteApplication($id) {
        $sql = "DELETE FROM Applications WHERE application_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    // ============================================
    // Methodoi diaxeirisis eggrafon.
    // ============================================
    
    /**
     * Pairnei ola documents grouped apo aitisi
     * @return array - Pinakas me documents grouped apo application_id
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
     * Pairnei documents gia a sigkekrimeno aitisi
     * @param int $applicationId - Aitisi ID
     * @return array - Pinakas me documents
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
     * Pairnei ola documents me aitisi leptomereies
     * @return array - Pinakas me documents
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
     * Prosthetei a document se mia aitisi
     * @param int $applicationId - Aitisi ID
     * @param string $filePath - Path pros to document arxeio
     * @return bool - True se epitixia, false se apotixia
     */
    public function addDocument($applicationId, $filePath) {
        $sql = "INSERT INTO ApplicationsDocuments (application_id, file_path) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $applicationId, $filePath);
        
        return $stmt->execute();
    }
    
    /**
     * Pairnei document apo ID
     * @param int $documentId - Document ID
     * @return array|null - Document dedomena i null an den vrethei
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
     * Diagrafei a document apo mia aitisi
     * @param int $documentId - Document ID
     * @return bool - True se epitixia, false se apotixia
     */
    public function deleteDocument($documentId) {
        $sql = "DELETE FROM ApplicationsDocuments WHERE ap_document_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $documentId);
        
        return $stmt->execute();
    }

    /**
     * Enimeronei mia yparxousa document path.
     * @param int $documentId - Document ID
     * @param string $filePath - Neo arxeio path
     * @return bool - True se epitixia, false se apotixia
     */
    public function updateDocumentPath($documentId, $filePath) {
        $sql = "UPDATE ApplicationsDocuments SET file_path = ? WHERE ap_document_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $filePath, $documentId);

        return $stmt->execute();
    }
    
    // ============================================
    // Methodoi diaxeirisis ypovolon.
    // ============================================
    
    /**
     * Elegxei an xristis aldiavasmay submitted mia aitisi
     * @param int $applicationId - Aitisi ID
     * @param int $userId - Xristis ID
     * @return bool - True an aldiavasmay submitted, false otherwise
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
     * Dimiourgia a new submission (legacy file-upload path)
     */
    public function createSubmission($applicationId, $userId, $filePath) {
        $sql = "INSERT INTO Submissions (application_id, user_id, file_path, sub_status)
                VALUES (?, ?, ?, 'waiting')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iis", $applicationId, $userId, $filePath);
        return $stmt->execute();
    }

    /**
     * Dimiourgei mia nea ypovoli me JSON forma dedomena (xoris ypoxreotiko arxeio)
     * @param int $applicationId - Aitisi ID
     * @param int $userId - Xristis ID
     * @param string $submissionDataJson - JSON-encoded forma pedia
     * @return bool - True se epitixia, false se apotixia
     */
    public function createSubmissionWithData($applicationId, $userId, $submissionDataJson) {
        return $this->createSubmissionWithDataAndFile($applicationId, $userId, $submissionDataJson, null);
    }

    /**
     * Dimiourgei mia nea ypovoli me JSON forma dedomena kai proairetiko uploaded arxeio path.
     * @param int $applicationId - Aitisi ID
     * @param int $userId - Xristis ID
     * @param string $submissionDataJson - JSON-encoded forma pedia
     * @param string|null $filePath - Proairetiko uploaded arxeio path
     * @return bool - True se epitixia, false se apotixia
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
     * Pairnei xristis's ypovoles
     * @param int $userId - Xristis ID
     * @return array - Pinakas me ypovoles
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
     * Pairnei ola ypovoles me xristis kai aitisi leptomereies
     * @return array - Pinakas me ypovoles
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
     * Pairnei metrisis gia ypovoles that are still anamoni elegxo.
     * @return int - Synoliko anamoni ypovoles
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
     * Pairnei undiavasma anamoni ypovoles count gia a sigkekrimeno aitisi.
     * @param int $applicationId - Aitisi ID
     * @return int - Synoliko anamoni undiavasma ypovoles gia aitisi
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
     * Mark a anamoni ypovoli as seen apo admin.
     * @param int $applicationId - Aitisi ID
     * @param int $userId - Xristis ID
     * @return bool - True otan query executes successfully
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
     * Enimeronei ypovoli status
     * @param int $applicationId - Aitisi ID
     * @param int $userId - Xristis ID
     * @param string $status - Status (anamoni, approved, rejected)
     * @return bool - True se epitixia, false se apotixia
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
     * Diagrafei a ypovoli so xristis can submit again.
     * @param int $applicationId - Aitisi ID
     * @param int $userId - Xristis ID
     * @return bool - True se epitixia, false se apotixia
     */
    public function deleteSubmission($applicationId, $userId) {
        $sql = "DELETE FROM Submissions WHERE application_id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $applicationId, $userId);
        return $stmt->execute();
    }
    
    /**
     * Pairnei synoliko metrisis gia aitiseis
     * @return int - Synoliki metrisi
     */
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as count FROM Applications";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }

    // ============================================
    // Methodoi gia ta pedia tis formas aitisis.
    // ============================================

    /**
     * Sxolio: voithitiko sxolio gia ton parakato kodika.
     * @param int $applicationId - Parametros tis leitourgias.
     * @return array - Epistrofi tis leitourgias.
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
     * Sxolio: voithitiko sxolio gia ton parakato kodika.
     * @param int $applicationId - Parametros tis leitourgias.
     * @param string $fieldName - Parametros tis leitourgias.
     * @param string $fieldType - Parametros tis leitourgias.
     * @param int $fieldOrder - Parametros tis leitourgias.
     * @param bool $isRequired - Parametros tis leitourgias.
     * @return bool - Epistrofi tis leitourgias.
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
     * Sxolio: voithitiko sxolio gia ton parakato kodika.
     * @param int $fieldId - Parametros tis leitourgias.
     * @param string $fieldName - Parametros tis leitourgias.
     * @param string $fieldType - Parametros tis leitourgias.
     * @param bool $isRequired - Parametros tis leitourgias.
     * @return bool - Epistrofi tis leitourgias.
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
     * Sxolio: voithitiko sxolio gia ton parakato kodika.
     * @param int $fieldId - Parametros tis leitourgias.
     * @return bool - Epistrofi tis leitourgias.
     */
    public function deleteFormField($fieldId) {
        $sql = "DELETE FROM ApplicationsFormFields WHERE field_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $fieldId);
        return $stmt->execute();
    }

    /**
     * Sxolio: voithitiko sxolio gia ton parakato kodika.
     * @param array $fieldIds - Parametros tis leitourgias.
     * @return bool - Epistrofi tis leitourgias.
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
    // Nea enisxymeni diaxeirisi aitiseon (v2)
    // ============================================================
    
    /**
     * Dimiourgei aitisi apo protypo i kena
     */
    public function createApplicationFromTemplate($templateId, $title, $description, $academicYear, $openDate, $dueDate, $allowOnline = true, $allowFile = true, $requireSignature = false, $createdBy = null) {
        $formSchema = null;
        if ($templateId) {
            // Fortonei to schema tou protypou.
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
     * Epistrefei aitisi me ola ta stoixeia (neo schema)
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
            // Kanei parse ta pedia JSON.
            if ($row['form_schema']) {
                $row['form_schema'] = json_decode($row['form_schema'], true);
            }
            if ($row['target_audience']) {
                $row['target_audience'] = json_decode($row['target_audience'], true);
            }
            
            // Anakta ta synimmmena arxeia.
            $row['attachments'] = $this->getApplicationAttachments($applicationId);
            
            return $row;
        }
        return null;
    }
    
    /**
     * Enimeronei aitisi me neo schema
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
     * Dimosievei aitisi (change status apo draft to dimosievmenes)
     */
    public function publishApplication($applicationId) {
        $sql = "UPDATE Applications SET status = 'published', updated_at = NOW() WHERE application_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $applicationId);
        return $stmt->execute();
    }
    
    /**
     * Kleisimo application (prevent new submissions)
     */
    public function closeApplication($applicationId) {
        $sql = "UPDATE Applications SET status = 'closed', updated_at = NOW() WHERE application_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $applicationId);
        return $stmt->execute();
    }
    
    /**
     * Prosthetei attachment to aitisi
     */
    public function addApplicationAttachment($applicationId, $filePath, $originalFilename) {
        // Anakta tin megalyteri seira taksinomisis.
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
     * Pairnei ta attachments gia aitisi
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
     * Diagrafi attachment
     */
    public function deleteApplicationAttachment($attachmentId) {
        // Anakta prwta to path tou arxeiou.
        $sql = "SELECT file_path FROM ApplicationAttachments WHERE attachment_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $attachmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $filePath = null;
        if ($row = $result->fetch_assoc()) {
            $filePath = $row['file_path'];
        }
        
        // Diagrafei tin eggrafi apo ti vasi dedomenon.
        $sql = "DELETE FROM ApplicationAttachments WHERE attachment_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $attachmentId);
        $deleted = $stmt->execute();
        
        // Diagrafei to arxeio apo to apothikeftiko meso.
        if ($deleted && $filePath) {
            $fullPath = __DIR__ . '/../../' . $filePath;
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
        
        return $deleted;
    }
    
    /**
     * Dimiourgia submission in new ApplicationSubmissions pinakas
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
     * Get submissions for application (new pinakas)
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
     * Pairnei dimosievmenes aitiseis (gia goneas portal)
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
