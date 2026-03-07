<?php
/**
 * AnnouncementsService - Handles all announcement-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class AnnouncementsService {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Get all announcements with their images
     * @param int $limit Optional limit for pagination
     * @param int $offset Optional offset for pagination
     * @return array Array of announcements with images
     */
    public function getAllAnnouncements($limit = null, $offset = 0) {
        $sql = "SELECT a.*, 
                       GROUP_CONCAT(ai.image_path) as images
                FROM Announcements a
                LEFT JOIN AnnouncementsImages ai ON a.announcement_id = ai.announcement_id
                GROUP BY a.announcement_id
                ORDER BY a.publish_date DESC";
        
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
        while ($row = $result->fetch_assoc()) {
            $row['images'] = $row['images'] ? explode(',', $row['images']) : [];
            $announcements[] = $row;
        }
        
        return $announcements;
    }
    
    /**
     * Get a single announcement by ID
     * @param int $id Announcement ID
     * @return array|null Announcement data or null if not found
     */
    public function getAnnouncementById($id) {
        $sql = "SELECT a.*, 
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
            return $row;
        }
        
        return null;
    }
    
    /**
     * Create a new announcement
     * @param string $title Announcement title
     * @param string $description Announcement description
     * @param string $publishDate Publish date (Y-m-d format)
     * @return int|false The new announcement ID or false on failure
     */
    public function createAnnouncement($title, $description, $publishDate) {
        $sql = "INSERT INTO Announcements (announcement_title, announcement_description, publish_date) 
                VALUES (?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sss", $title, $description, $publishDate);
        
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
     * @param string $publishDate Publish date (Y-m-d format)
     * @return bool True on success, false on failure
     */
    public function updateAnnouncement($id, $title, $description, $publishDate) {
        $sql = "UPDATE Announcements 
                SET announcement_title = ?, 
                    announcement_description = ?, 
                    publish_date = ?
                WHERE announcement_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $title, $description, $publishDate, $id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete an announcement
     * @param int $id Announcement ID
     * @return bool True on success, false on failure
     */
    public function deleteAnnouncement($id) {
        // Images will be deleted automatically due to CASCADE
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
        $sql = "INSERT INTO AnnouncementsImages (announcement_id, image_path) VALUES (?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $announcementId, $imagePath);
        
        return $stmt->execute();
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
                       GROUP_CONCAT(ai.image_path) as images
                FROM Announcements a
                LEFT JOIN AnnouncementsImages ai ON a.announcement_id = ai.announcement_id
                WHERE a.announcement_title LIKE ? OR a.announcement_description LIKE ?
                GROUP BY a.announcement_id
                ORDER BY a.publish_date DESC";
        
        $searchTerm = "%{$query}%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $announcements = [];
        while ($row = $result->fetch_assoc()) {
            $row['images'] = $row['images'] ? explode(',', $row['images']) : [];
            $announcements[] = $row;
        }
        
        return $announcements;
    }
}
?>
