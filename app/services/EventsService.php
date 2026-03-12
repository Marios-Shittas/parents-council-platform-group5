<?php
/**
 * EventsService - Handles all event-related database operations
 */

require_once __DIR__ . '/../config/db.php';

class EventsService {
    private $conn;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }
    
    /**
     * Get all events with their images
     * @param int $limit Optional limit for pagination
     * @param int $offset Optional offset for pagination
     * @return array Array of events with images
     */
    public function getAllEvents($limit = null, $offset = 0) {
        $sql = "SELECT e.*, 
                       GROUP_CONCAT(ei.image_path) as images
                FROM Events e
                LEFT JOIN EventsImages ei ON e.event_id = ei.event_id
                GROUP BY e.event_id
                ORDER BY e.event_date DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
        } else {
            $stmt = $this->conn->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $row['images'] = $row['images'] ? explode(',', $row['images']) : [];
            $events[] = $row;
        }
        
        return $events;
    }
    
    /**
     * Get upcoming events (future events only)
     * @return array Array of upcoming events
     */
    public function getUpcomingEvents() {
        $sql = "SELECT e.*, 
                       GROUP_CONCAT(ei.image_path) as images
                FROM Events e
                LEFT JOIN EventsImages ei ON e.event_id = ei.event_id
                WHERE DATE(e.event_date) >= CURDATE()
                GROUP BY e.event_id
                ORDER BY e.event_date ASC";
        
        $result = $this->conn->query($sql);
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $row['images'] = $row['images'] ? explode(',', $row['images']) : [];
            $events[] = $row;
        }
        
        return $events;
    }

    /**
     * Get past events
     * @return array Array of past events
     */
    public function getPastEvents() {
        $sql = "SELECT e.*, 
                       GROUP_CONCAT(ei.image_path) as images
                FROM Events e
                LEFT JOIN EventsImages ei ON e.event_id = ei.event_id
                WHERE DATE(e.event_date) < CURDATE()
                GROUP BY e.event_id
                ORDER BY e.event_date DESC";

        $result = $this->conn->query($sql);

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $row['images'] = $row['images'] ? explode(',', $row['images']) : [];
            $events[] = $row;
        }

        return $events;
    }
    
    /**
     * Get a single event by ID
     * @param int $id Event ID
     * @return array|null Event data or null if not found
     */
    public function getEventById($id) {
        $sql = "SELECT e.*, 
                       GROUP_CONCAT(ei.image_path) as images
                FROM Events e
                LEFT JOIN EventsImages ei ON e.event_id = ei.event_id
                WHERE e.event_id = ?
                GROUP BY e.event_id";
        
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
     * Create a new event
     * @param string $title Event title
     * @param string $description Event description
     * @param string $eventDate Event date and time (Y-m-d H:i:s format)
     * @param string $publishDate Publish date (Y-m-d format)
     * @return int|false The new event ID or false on failure
     */
    public function createEvent($title, $description, $eventDate, $publishDate) {
        $sql = "INSERT INTO Events (event_title, event_description, event_date, publish_date) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $title, $description, $eventDate, $publishDate);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update an existing event
     * @param int $id Event ID
     * @param string $title Event title
     * @param string $description Event description
     * @param string $eventDate Event date and time (Y-m-d H:i:s format)
     * @param string $publishDate Publish date (Y-m-d format)
     * @return bool True on success, false on failure
     */
    public function updateEvent($id, $title, $description, $eventDate, $publishDate) {
        $sql = "UPDATE Events 
                SET event_title = ?, 
                    event_description = ?, 
                    event_date = ?,
                    publish_date = ?
                WHERE event_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssi", $title, $description, $eventDate, $publishDate, $id);
        
        return $stmt->execute();
    }
    
    /**
     * Delete an event
     * @param int $id Event ID
     * @return bool True on success, false on failure
     */
    public function deleteEvent($id) {
        // Images will be deleted automatically due to CASCADE
        $sql = "DELETE FROM Events WHERE event_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Add an image to an event
     * @param int $eventId Event ID
     * @param string $imagePath Path to the image file
     * @return bool True on success, false on failure
     */
    public function addImage($eventId, $imagePath) {
        $sql = "INSERT INTO EventsImages (event_id, image_path) VALUES (?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $eventId, $imagePath);
        
        return $stmt->execute();
    }
    
    /**
     * Delete an image from an event
     * @param int $imageId Image ID
     * @return bool True on success, false on failure
     */
    public function deleteImage($imageId) {
        $sql = "DELETE FROM EventsImages WHERE ev_image_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $imageId);
        
        return $stmt->execute();
    }
    
    /**
     * Get images for an event
     * @param int $eventId Event ID
     * @return array Array of image data
     */
    public function getImages($eventId) {
        $sql = "SELECT * FROM EventsImages WHERE event_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get total count of events
     * @return int Total count
     */
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as count FROM Events";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
    
    /**
     * Search events by title or description
     * @param string $query Search query
     * @return array Array of matching events
     */
    public function searchEvents($query) {
        $sql = "SELECT e.*, 
                       GROUP_CONCAT(ei.image_path) as images
                FROM Events e
                LEFT JOIN EventsImages ei ON e.event_id = ei.event_id
                WHERE e.event_title LIKE ? OR e.event_description LIKE ?
                GROUP BY e.event_id
                ORDER BY e.event_date DESC";
        
        $searchTerm = "%{$query}%";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $row['images'] = $row['images'] ? explode(',', $row['images']) : [];
            $events[] = $row;
        }
        
        return $events;
    }
}
?>
