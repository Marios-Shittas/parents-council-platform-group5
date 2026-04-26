<?php
/**
 * AnnouncementsService - Handles all announcement-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class AnnouncementsService {
    private $conn;
    private $lastOperationError = '';
    private $announcementAttachmentsTableChecked = false;
    private $announcementAttachmentsTableExists = false;
// Arxikopoiei to AnnouncementsService me to koinoxristo DB connection.
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
// Epistrefei ton megisto arithmo eikonon pou epitrepontai ana announcement.
    public function getMaxImagesPerAnnouncement() {
        return 6;
    }
// Epistrefei to teleftaio operation error sxetika me attachment/image gia UI feedback.
    public function getLastOperationError() {
        return $this->lastOperationError;
    }

    /**
     * Get all announcements with their images
     * @param int $limit Optional limit for pagination
     * @param int $offset Optional offset for pagination
     * @return array Array of announcements with images
     */
    public function getAllAnnouncements($limit = null, $offset = 0) {
        $sql = "SELECT a.*,
                       COALESCE(a.announcement_date, a.publish_date) as announcement_date,
                       GROUP_CONCAT(ai.image_path) as images
                FROM Announcements a
                LEFT JOIN AnnouncementsImages ai ON a.announcement_id = ai.announcement_id
                GROUP BY a.announcement_id
                ORDER BY COALESCE(a.announcement_date, a.publish_date) DESC, a.publish_date DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
        } else {
            $stmt = $this->conn->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $announcements = [];
        $announcementIds = [];
        while ($row = $result->fetch_assoc()) {
            $row['images'] = $row['images'] ? explode(',', $row['images']) : [];
            $row['attachments'] = [];
            $announcements[] = $row;
            $announcementIds[] = (int)($row['announcement_id'] ?? 0);
        }

        $attachmentsByAnnouncement = $this->getAttachmentsGroupedByAnnouncementIds($announcementIds);
        foreach ($announcements as &$announcement) {
            $announcementId = (int)($announcement['announcement_id'] ?? 0);
            $announcement['attachments'] = $attachmentsByAnnouncement[$announcementId] ?? [];
        }
        unset($announcement);

        return $announcements;
    }
    
    /**
     * Get a single announcement by ID
     * @param int $id Announcement ID
     * @return array|null Announcement data or null if not found
     */
    public function getAnnouncementById($id) {
        $sql = "SELECT a.*,
                       COALESCE(a.announcement_date, a.publish_date) as announcement_date,
                       GROUP_CONCAT(ai.image_path) as images
                FROM Announcements a
                LEFT JOIN AnnouncementsImages ai ON a.announcement_id = ai.announcement_id
                WHERE a.announcement_id = ?
                GROUP BY a.announcement_id";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $row['images'] = $row['images'] ? explode(',', $row['images']) : [];
            $row['attachments'] = $this->getAttachments($id);
            return $row;
        }
        
        return null;
    }
    
    /**
     * Create a new announcement
     * @param string $title Announcement title
     * @param string $description Announcement description
     * @param string $announcementDate Announcement date (Y-m-d format)
     * @param string $publishDate Publish date (Y-m-d format)
     * @return int|false The new announcement ID or false on failure
     */
    public function createAnnouncement($title, $description, $announcementDate, $publishDate, $gdprNotice = '') {
        $sql = "INSERT INTO Announcements (announcement_title, announcement_date, announcement_description, gdpr_notice, publish_date) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssss", $title, $announcementDate, $description, $gdprNotice, $publishDate);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update an existing announcement
     * @param int $id Announcement ID
     * @param string $title Announcement title
     * @param string $description Announcement description
     * @param string $announcementDate Announcement date (Y-m-d format)
     * @param string $publishDate Publish date (Y-m-d format)
     * @return bool True on success, false on failure
     */
    public function updateAnnouncement($id, $title, $description, $announcementDate, $publishDate, $gdprNotice = '') {
        $sql = "UPDATE Announcements 
                SET announcement_title = ?, 
                    announcement_date = ?,
                    announcement_description = ?, 
                    gdpr_notice = ?,
                    publish_date = ?
                WHERE announcement_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssi", $title, $announcementDate, $description, $gdprNotice, $publishDate, $id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete an announcement
     * @param int $id Announcement ID
     * @return bool True on success, false on failure
     */
    public function deleteAnnouncement($id) {
        // Oi eikones diagrafontai aytomata logo CASCADE.
        $sql = "DELETE FROM Announcements WHERE announcement_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Add an image to an announcement
     * @param int $announcementId Announcement ID
     * @param string $imagePath Path to the image file
     * @return bool True on success, false on failure
     */
    public function addImage($announcementId, $imagePath) {
        $currentImagesCount = $this->countImages($announcementId);
        if ($currentImagesCount >= $this->getMaxImagesPerAnnouncement()) {
            $this->lastOperationError = 'Μπορούν να αποθηκευτούν έως ' . $this->getMaxImagesPerAnnouncement() . ' εικόνες ανά ανακοίνωση.';
            return false;
        }

        $sql = "INSERT INTO AnnouncementsImages (announcement_id, image_path) VALUES (?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $announcementId, $imagePath);

        $executed = $stmt->execute();
        $this->lastOperationError = $executed ? '' : 'Σφάλμα κατά την αποθήκευση της εικόνας στη βάση δεδομένων.';

        return $executed;
    }
    
    /**
     * Delete an image from an announcement
     * @param int $imageId Image ID
     * @return bool True on success, false on failure
     */
    public function deleteImage($imageId) {
        $sql = "DELETE FROM AnnouncementsImages WHERE an_image_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $imageId);
        
        return $stmt->execute();
    }
    
    /**
     * Get images for an announcement
     * @param int $announcementId Announcement ID
     * @return array Array of image data
     */
    public function getImages($announcementId) {
        $sql = "SELECT * FROM AnnouncementsImages WHERE announcement_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $announcementId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
// Metraei tis apothikevmenes eikones ana announcement gia enforcement tou upload cap.
    public function countImages($announcementId) {
        $sql = "SELECT COUNT(*) AS total FROM AnnouncementsImages WHERE announcement_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $announcementId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;

        return (int)($row['total'] ?? 0);
    }
// Fortonei ola ta file attachments enos announcement otan o pinaka einai diathesimos.
    public function getAttachments($announcementId) {
        if (!$this->isAnnouncementAttachmentsTableAvailable()) {
            return [];
        }

        $sql = "SELECT * FROM AnnouncementAttachments WHERE announcement_id = ? ORDER BY attachment_id DESC";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $announcementId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (mysqli_sql_exception $e) {
            return [];
        }
    }
// Apothikevei metadata eggrafi enos attachment kai krataei perigrafiko error se apotyxia.
    public function addAttachment($announcementId, $filePath, $originalName = null) {
        if (!$this->isAnnouncementAttachmentsTableAvailable()) {
            $this->lastOperationError = 'Ο πίνακας συνημμένων ανακοινώσεων δεν είναι διαθέσιμος.';
            return false;
        }

        $sql = "INSERT INTO AnnouncementAttachments (announcement_id, file_path, original_name) VALUES (?, ?, ?)";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iss", $announcementId, $filePath, $originalName);

            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            $this->lastOperationError = 'Σφάλμα κατά την αποθήκευση του συνημμένου.';
            return false;
        }
    }
// Epistrefei ena attachment row me vasi to id tou.
    public function getAttachmentById($attachmentId) {
        if (!$this->isAnnouncementAttachmentsTableAvailable()) {
            return null;
        }

        $sql = "SELECT * FROM AnnouncementAttachments WHERE attachment_id = ?";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $attachmentId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result->fetch_assoc();
        } catch (mysqli_sql_exception $e) {
            return null;
        }
    }
// Diagrafei ena attachment row me id otan o attachments pinakas yparxei.
    public function deleteAttachment($attachmentId) {
        if (!$this->isAnnouncementAttachmentsTableAvailable()) {
            return false;
        }

        $sql = "DELETE FROM AnnouncementAttachments WHERE attachment_id = ?";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $attachmentId);

            return $stmt->execute();
        } catch (mysqli_sql_exception $e) {
            return false;
        }
    }
    
    /**
     * Get total count of announcements
     * @return int Total count
     */
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as count FROM Announcements";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
    
    /**
     * Search announcements by title or description
     * @param string $query Search query
     * @return array Array of matching announcements
     */
    public function searchAnnouncements($query) {
        $sql = "SELECT a.*,
                       COALESCE(a.announcement_date, a.publish_date) as announcement_date,
                       GROUP_CONCAT(ai.image_path) as images
                FROM Announcements a
                LEFT JOIN AnnouncementsImages ai ON a.announcement_id = ai.announcement_id
                WHERE a.announcement_title LIKE ? OR a.announcement_description LIKE ?
                GROUP BY a.announcement_id
                ORDER BY COALESCE(a.announcement_date, a.publish_date) DESC, a.publish_date DESC";
        
        $searchTerm = "%{$query}%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $announcements = [];
        $announcementIds = [];
        while ($row = $result->fetch_assoc()) {
            $row['images'] = $row['images'] ? explode(',', $row['images']) : [];
            $row['attachments'] = [];
            $announcements[] = $row;
            $announcementIds[] = (int)($row['announcement_id'] ?? 0);
        }

        $attachmentsByAnnouncement = $this->getAttachmentsGroupedByAnnouncementIds($announcementIds);
        foreach ($announcements as &$announcement) {
            $announcementId = (int)($announcement['announcement_id'] ?? 0);
            $announcement['attachments'] = $attachmentsByAnnouncement[$announcementId] ?? [];
        }
        unset($announcement);

        return $announcements;
    }
// Kanei batch-load attachments kai ta omadopoiei ana announcement_id gia list pages.
    private function getAttachmentsGroupedByAnnouncementIds(array $announcementIds) {
        if (!$this->isAnnouncementAttachmentsTableAvailable()) {
            return [];
        }

        $announcementIds = array_values(array_filter(array_map('intval', $announcementIds), static function ($id) {
            return $id > 0;
        }));

        if (empty($announcementIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($announcementIds), '?'));
        $types = str_repeat('i', count($announcementIds));
        $sql = "SELECT * FROM AnnouncementAttachments WHERE announcement_id IN ({$placeholders}) ORDER BY attachment_id DESC";
        try {
            $stmt = $this->conn->prepare($sql);
        } catch (mysqli_sql_exception $e) {
            return [];
        }

        if ($stmt === false) {
            return [];
        }

        try {
            $stmt->bind_param($types, ...$announcementIds);
            $stmt->execute();
            $result = $stmt->get_result();
        } catch (mysqli_sql_exception $e) {
            return [];
        }

        $attachmentsByAnnouncement = [];
        while ($row = $result->fetch_assoc()) {
            $announcementId = (int)($row['announcement_id'] ?? 0);
            $attachmentsByAnnouncement[$announcementId][] = $row;
        }

        return $attachmentsByAnnouncement;
    }
// Kanei cache kai epistrefei an o AnnouncementAttachments pinakas yparxei sto schema.
    private function isAnnouncementAttachmentsTableAvailable() {
        if ($this->announcementAttachmentsTableChecked) {
            return $this->announcementAttachmentsTableExists;
        }

        $this->announcementAttachmentsTableChecked = true;

        try {
            $result = $this->conn->query("SHOW TABLES LIKE 'AnnouncementAttachments'");
            $this->announcementAttachmentsTableExists = $result && $result->num_rows > 0;
        } catch (mysqli_sql_exception $e) {
            $this->announcementAttachmentsTableExists = false;
        }

        return $this->announcementAttachmentsTableExists;
    }
// API-style helper pou kanei output tis teleutaies 5 announcements ws JSON response.
    public function get5LatestAnnouncements() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $announcementsQuery = "SELECT announcement_id, announcement_title, announcement_description, COALESCE(announcement_date, publish_date) AS announcement_date FROM Announcements ORDER BY COALESCE(announcement_date, publish_date) DESC, publish_date DESC LIMIT 5";
        $result = $this->conn->query($announcementsQuery);

        if (!$result) {
            echo json_encode(["error" => "Query failed: " . $this->conn->error]);
            exit;
        }

        $announcements = [];
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
        echo json_encode($announcements);
    }

}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
        $service = new AnnouncementsService();
        $service->get5LatestAnnouncements();
    }
?>
