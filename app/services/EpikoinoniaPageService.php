<?php
/**
 * EpikoinoniaPageService
 * Αποθηκεύει και ανακτά το περιεχόμενο της δημόσιας σελίδας "Επικοινωνία".
 */

require_once __DIR__ . '/../config/db.php';

class EpikoinoniaPageService
{
    private $conn;
    private $defaultSections;
    private $lastError = '';

    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
        $this->defaultSections = $this->buildDefaultSections();

        $this->ensureTable();
        $this->ensureDefaultSections();
    }

    public function getAllSections()
    {
        $sections = $this->defaultSections;
        $result = $this->conn->query('SELECT * FROM EpikoinoniaPageSections');

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $sectionKey = $row['section_key'] ?? '';
                if (!isset($sections[$sectionKey])) {
                    continue;
                }

                $decodedContent = json_decode($row['content_json'] ?? '', true);
                if (!is_array($decodedContent)) {
                    $decodedContent = $sections[$sectionKey]['content'];
                }

                $sections[$sectionKey] = [
                    'title' => (string)($row['section_title'] ?? ''),
                    'subtitle' => (string)($row['section_subtitle'] ?? ''),
                    'content' => $decodedContent,
                ];
            }
        }

        return $sections;
    }

    public function getSection($sectionKey)
    {
        $sections = $this->getAllSections();
        return $sections[$sectionKey] ?? null;
    }

    public function updateSection($sectionKey, $title, $subtitle, array $content)
    {
        $this->lastError = '';

        if (!isset($this->defaultSections[$sectionKey])) {
            $this->lastError = 'Μη έγκυρο section key.';
            return false;
        }

        $title = $this->normalizeUtf8($title);
        $subtitle = $this->normalizeUtf8($subtitle);
        $content = $this->normalizeUtf8($content);

        $contentJson = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($contentJson === false) {
            $this->lastError = 'Αποτυχία μετατροπής δεδομένων σε JSON.';
            return false;
        }

        $existsStmt = $this->conn->prepare('SELECT section_id FROM EpikoinoniaPageSections WHERE section_key = ? LIMIT 1');
        if (!$existsStmt) {
            $this->lastError = 'Σφάλμα prepare lookup: ' . $this->conn->error;
            return false;
        }

        $existsStmt->bind_param('s', $sectionKey);
        if (!$existsStmt->execute()) {
            $this->lastError = 'Σφάλμα execute lookup: ' . $existsStmt->error;
            return false;
        }

        $exists = $existsStmt->get_result()->num_rows > 0;

        if ($exists) {
            $sql = 'UPDATE EpikoinoniaPageSections
                    SET section_title = ?, section_subtitle = ?, content_json = ?
                    WHERE section_key = ?';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Σφάλμα prepare update: ' . $this->conn->error;
                return false;
            }

            $stmt->bind_param('ssss', $title, $subtitle, $contentJson, $sectionKey);
        } else {
            $sql = 'INSERT INTO EpikoinoniaPageSections (section_key, section_title, section_subtitle, content_json)
                    VALUES (?, ?, ?, ?)';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Σφάλμα prepare insert: ' . $this->conn->error;
                return false;
            }

            $stmt->bind_param('ssss', $sectionKey, $title, $subtitle, $contentJson);
        }

        if (!$stmt->execute()) {
            $this->lastError = 'Σφάλμα αποθήκευσης: ' . $stmt->error;
            return false;
        }

        return true;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS EpikoinoniaPageSections (
                    section_id INT NOT NULL AUTO_INCREMENT,
                    section_key VARCHAR(100) NOT NULL,
                    section_title VARCHAR(255) NOT NULL,
                    section_subtitle TEXT DEFAULT NULL,
                    content_json LONGTEXT DEFAULT NULL,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (section_id),
                    UNIQUE KEY uq_epikoinonia_page_section_key (section_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->conn->query($sql);
    }

    private function ensureDefaultSections()
    {
        foreach ($this->defaultSections as $sectionKey => $section) {
            $stmt = $this->conn->prepare('SELECT section_id FROM EpikoinoniaPageSections WHERE section_key = ? LIMIT 1');
            if (!$stmt) {
                continue;
            }

            $stmt->bind_param('s', $sectionKey);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                continue;
            }

            $this->updateSection($sectionKey, $section['title'], $section['subtitle'], $section['content']);
        }
    }

    private function buildDefaultSections()
    {
        return [
            'page_header' => [
                'title' => 'Επικοινωνία',
                'subtitle' => 'Επικοινωνήστε μαζί μας για οποιαδήποτε ερώτηση ή πληροφορία.',
                'content' => [
                    'eyebrow' => 'Υποστήριξη Και Στοιχεία',
                    'icon' => 'fas fa-envelope',
                ],
            ],
            'contact_info' => [
                'title' => 'Πληροφορίες Επικοινωνίας',
                'subtitle' => 'Βρείτε τη διεύθυνση, τα τηλέφωνα, το email και το ωράριο της σχολικής μονάδας.',
                'content' => [
                    'cards' => [
                        [
                            'title' => 'Διεύθυνση',
                            'text' => "ΑΡΓΟΛΙΔΟΣ 45, 4007 ΜΕΣΑ ΓΕΙΤΟΝΙΑ, Λεμεσός",
                            'icon' => 'fas fa-map-marker-alt',
                            'link_label' => '',
                            'link_url' => '',
                        ],
                        [
                            'title' => 'Τηλέφωνο',
                            'text' => "Τηλέφωνο: 25694570\nΤηλεομοιότυπο: 25694575",
                            'icon' => 'fas fa-phone',
                            'link_label' => '',
                            'link_url' => '',
                        ],
                        [
                            'title' => 'Email',
                            'text' => '',
                            'icon' => 'fas fa-envelope',
                            'link_label' => 'dim-mesa-geitonia29-lem@schools.ac.cy',
                            'link_url' => 'mailto:dim-mesa-geitonia29-lem@schools.ac.cy',
                        ],
                        [
                            'title' => 'Ώρες Λειτουργίας',
                            'text' => 'Δευ-Παρ - 7.30-13.05',
                            'icon' => 'fas fa-clock',
                            'link_label' => '',
                            'link_url' => '',
                        ],
                    ],
                ],
            ],
            'map_section' => [
                'title' => 'Βρείτε μας στο Χάρτη',
                'subtitle' => 'Η τοποθεσία της σχολικής μονάδας στο Google Maps.',
                'content' => [
                    'embed_url' => 'https://maps.google.com/maps?q=%CE%94%CE%97%CE%9C%CE%9F%CE%A4%CE%99%CE%9A%CE%9F%20%CE%A3%CE%A7%CE%9F%CE%9B%CE%95%CE%99%CE%9F%20%CE%9C%CE%95%CE%A3%CE%91%20%CE%93%CE%95%CE%99%CE%A4%CE%9F%CE%9D%CE%99%CE%91%CE%A3%20%CE%9A%CE%98%27%20-%20%CE%93.%CE%9D.%20%CE%9A%CE%91%CE%9B%CE%9F%CE%93%CE%95%CE%A1%CE%9F%CE%A0%CE%9F%CE%A5%CE%9B%CE%9F%CE%A5%2C%20%CE%91%CF%81%CE%B3%CE%BF%CE%BB%CE%AF%CE%B4%CE%BF%CF%82%2045%2C%20%CE%9C%CE%AD%CF%83%CE%B1%20%CE%93%CE%B5%CE%B9%CF%84%CE%BF%CE%BD%CE%B9%CE%AC&z=17&output=embed',
                ],
            ],
            'form_section' => [
                'title' => 'Στείλτε μας Μήνυμα',
                'subtitle' => 'Συμπληρώστε τη φόρμα και θα επικοινωνήσουμε μαζί σας το συντομότερο δυνατό.',
                'content' => [
                    'description' => '',
                    'button_text' => 'Αποστολή Μηνύματος',
                    'success_message' => 'Το μήνυμά σας λήφθηκε. Θα σας απαντήσουμε το συντομότερο δυνατό.',
                ],
            ],
            'social_section' => [
                'title' => 'Βρείτε μας στα social networks',
                'subtitle' => 'Ακολουθήστε τις επίσημες σελίδες μας για νέα και ενημερώσεις.',
                'content' => [
                    'items' => [
                        [
                            'title' => 'Facebook',
                            'url' => 'https://www.facebook.com/ypourgeiopaideias',
                            'icon' => 'fab fa-facebook-f',
                        ],
                        [
                            'title' => 'X',
                            'url' => 'https://x.com/cymoec',
                            'icon' => 'fab fa-twitter',
                        ],
                        [
                            'title' => 'YouTube',
                            'url' => 'https://www.youtube.com/cymoec',
                            'icon' => 'fab fa-youtube',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function normalizeUtf8($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->normalizeUtf8($item);
            }

            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        if (function_exists('mb_scrub')) {
            return mb_scrub($value, 'UTF-8');
        }

        return @iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: $value;
    }
}
