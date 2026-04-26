<?php
// Arxeio: app\services\EventsService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
/**
 * EventsService - Xeirizetai ola ekdilosi-related vasi operations
 */

require_once __DIR__ . '/../config/db.php';

class EventsService {
    private $conn;
    private $lastOperationError = '';
    
    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getMaxImagesPerEvent() {
        return 6;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getLastOperationError() {
        return $this->lastOperationError;
    }
    
    /**
     * Pairnei ola ekdiloseis me tis eikones
     * @param int $limit - Proairetiko orio gia pagination
     * @param int $offset - Proairetiko offset gia pagination
     * @return array - Pinakas me ekdiloseis me eikones
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
     * Pairnei upcoming ekdiloseis (future ekdiloseis only)
     * @return array - Pinakas me upcoming ekdiloseis
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
     * Pairnei past ekdiloseis
     * @return array - Pinakas me past ekdiloseis
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
     * Pairnei mia ekdilosi apo ID
     * @param int $id - Ekdilosi ID
     * @return array|null - Ekdilosi dedomena i null an den vrethei
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
     * Dimiourgei mia nea ekdilosi
     * @param string $title - Ekdilosi titlos
     * @param string $description - Ekdilosi perigrafi
     * @param string $eventDate - Ekdilosi imerominia kai time (Y-m-d H:i:s morfi)
     * @param string $publishDate - Dimosievei imerominia (Y-m-d morfi)
     * @return int|false - To neo ekdilosi ID i false se apotixia
     */
    public function createEvent($title, $description, $eventDate, $publishDate, $gdprNotice = '') {
        $sql = "INSERT INTO Events (event_title, event_description, gdpr_notice, event_date, publish_date) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssss", $title, $description, $gdprNotice, $eventDate, $publishDate);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Enimeronei mia yparxousa ekdilosi
     * @param int $id - Ekdilosi ID
     * @param string $title - Ekdilosi titlos
     * @param string $description - Ekdilosi perigrafi
     * @param string $eventDate - Ekdilosi imerominia kai time (Y-m-d H:i:s morfi)
     * @param string $publishDate - Dimosievei imerominia (Y-m-d morfi)
     * @return bool - True se epitixia, false se apotixia
     */
    public function updateEvent($id, $title, $description, $eventDate, $publishDate, $gdprNotice = '') {
        $sql = "UPDATE Events 
                SET event_title = ?, 
                    event_description = ?, 
                    gdpr_notice = ?,
                    event_date = ?,
                    publish_date = ?
                WHERE event_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssi", $title, $description, $gdprNotice, $eventDate, $publishDate, $id);
        
        return $stmt->execute();
    }
    
    /**
     * Diagrafei mia ekdilosi
     * @param int $id - Ekdilosi ID
     * @return bool - True se epitixia, false se apotixia
     */
    public function deleteEvent($id) {
        // Oi eikones diagrafontai aytomata logo CASCADE.
        $sql = "DELETE FROM Events WHERE event_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    /**
     * Prosthetei mia eikona se mia ekdilosi
     * @param int $eventId - Ekdilosi ID
     * @param string $imagePath - Path pros to eikona arxeio
     * @return bool - True se epitixia, false se apotixia
     */
    public function addImage($eventId, $imagePath) {
        $currentImagesCount = $this->countImages($eventId);
        if ($currentImagesCount >= $this->getMaxImagesPerEvent()) {
            $this->lastOperationError = 'ÎœÏ€Î¿ÏÎ¿ÏÎ½ Î½Î± Î±Ï€Î¿Î¸Î·ÎºÎµÏ…Ï„Î¿ÏÎ½ Î­Ï‰Ï‚ ' . $this->getMaxImagesPerEvent() . ' Ï†Ï‰Ï„Î¿Î³ÏÎ±Ï†Î¯ÎµÏ‚ Î±Î½Î¬ ÎµÎºÎ´Î®Î»Ï‰ÏƒÎ·.';
            return false;
        }

        $sql = "INSERT INTO EventsImages (event_id, image_path) VALUES (?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $eventId, $imagePath);

        $executed = $stmt->execute();
        $this->lastOperationError = $executed ? '' : 'Î£Ï†Î¬Î»Î¼Î± ÎºÎ±Ï„Î¬ Ï„Î·Î½ Î±Ï€Î¿Î¸Î®ÎºÎµÏ…ÏƒÎ· Ï„Î·Ï‚ ÎµÎ¹ÎºÏŒÎ½Î±Ï‚ ÏƒÏ„Î· Î²Î¬ÏƒÎ· Î´ÎµÎ´Î¿Î¼Î­Î½Ï‰Î½.';

        return $executed;
    }
    
    /**
     * Diagrafei mia eikona apo an ekdilosi
     * @param int $imageId - Eikona ID
     * @return bool - True se epitixia, false se apotixia
     */
    public function deleteImage($imageId) {
        $sql = "DELETE FROM EventsImages WHERE ev_image_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $imageId);
        
        return $stmt->execute();
    }
    
    /**
     * Pairnei eikones gia an ekdilosi
     * @param int $eventId - Ekdilosi ID
     * @return array - Pinakas me eikona dedomena
     */
    public function getImages($eventId) {
        $sql = "SELECT * FROM EventsImages WHERE event_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function countImages($eventId) {
        $sql = "SELECT COUNT(*) AS total FROM EventsImages WHERE event_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;

        return (int)($row['total'] ?? 0);
    }
    
    /**
     * Pairnei synoliko metrisis gia ekdiloseis
     * @return int - Synoliki metrisi
     */
    public function getTotalCount() {
        $sql = "SELECT COUNT(*) as count FROM Events";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return (int)$row['count'];
    }
    
    /**
     * Psaxnei ekdiloseis apo titlos i perigrafi
     * @param string $query - Psaxnei query
     * @return array - Pinakas me tairiazouses ekdiloseis
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function get5LatestEvents() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $eventsQuery = "SELECT event_id, event_title, event_description, event_date FROM Events ORDER BY event_date DESC LIMIT 5";
        $result = $this->conn->query($eventsQuery);

        if (!$result) {
            echo json_encode(["error" => "Query failed: " . $this->conn->error]);
            exit;
        }

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        echo json_encode($events);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getAllEventsForCalendar() {
        $eventsQuery = "SELECT event_title as title, event_description as description, event_date as date, 'event' as type FROM Events ORDER BY event_date ASC";
        $result = $this->conn->query($eventsQuery);

        if (!$result) {
            echo json_encode(["error" => "Query failed: " . $this->conn->error]);
            exit;
        }

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        return $events;
    }

}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
        $service = new EventsService();
        $service->get5LatestEvents();
}

?>
