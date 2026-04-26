<?php
// Arxeio: app\services\HomePageService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
/**
 * HomePageService
 * Sxolio: voithitiko sxolio gia ton parakato kodika.
 */

require_once __DIR__ . '/../config/db.php';

class HomePageService
{
    private $conn;
    private $defaultSections;
    private $lastError = '';
// Kanei bootstrap tou ypiresia: fortwnei DB handle, etoimazei proepilegmeno section schema,
// eksasfalizei oti yparxei pinakas kai kanei arxikopoisi sta missing proepilegmena.
    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
        $this->defaultSections = $this->buildDefaultSections();

        $this->ensureTable();
        $this->ensureDefaultSections();
    }
// Epistrefei merged homepage sections opou to DB periexomeno kanei override sta proepilegmena ana section key.
    public function getAllSections()
    {
        $sections = $this->defaultSections;
        $result = $this->conn->query('SELECT * FROM HomePageSections');

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
// Epistrefei ena section ana key apo to merged section map.
    public function getSection($sectionKey)
    {
        $sections = $this->getAllSections();
        return $sections[$sectionKey] ?? null;
    }
// Kanei validate to section key, normalopoiei UTF-8 payload, serialopoiei JSON periexomeno,
// kai ekteli upsert (enimerosi i eisagogi) gia to target homepage section.
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

        $existsStmt = $this->conn->prepare('SELECT section_id FROM HomePageSections WHERE section_key = ? LIMIT 1');
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
            $sql = 'UPDATE HomePageSections
                    SET section_title = ?, section_subtitle = ?, content_json = ?
                    WHERE section_key = ?';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Σφάλμα prepare update: ' . $this->conn->error;
                return false;
            }

            $stmt->bind_param('ssss', $title, $subtitle, $contentJson, $sectionKey);
        } else {
            $sql = 'INSERT INTO HomePageSections (section_key, section_title, section_subtitle, content_json)
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
// Epistrefei to teleftaio human-readable validation i persistence error tou ypiresia.
    public function getLastError()
    {
        return $this->lastError;
    }
// Dimiourgei ton HomePageSections pinakas kai uniqueness constraint sto section_key.
    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS HomePageSections (
                    section_id INT NOT NULL AUTO_INCREMENT,
                    section_key VARCHAR(100) NOT NULL,
                    section_title VARCHAR(255) NOT NULL,
                    section_subtitle TEXT DEFAULT NULL,
                    content_json LONGTEXT DEFAULT NULL,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (section_id),
                    UNIQUE KEY uq_home_page_section_key (section_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->conn->query($sql);
    }
// Kanei eisagogi ta proepilegmeno sections mono otan leipoun, xwris na peirazei hdh customized periexomeno.
    private function ensureDefaultSections()
    {
        foreach ($this->defaultSections as $sectionKey => $section) {
            $stmt = $this->conn->prepare('SELECT section_id FROM HomePageSections WHERE section_key = ? LIMIT 1');
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
// Orizei to canonical homepage periexomeno blueprint gia first-time setup kai fallback reads.
    private function buildDefaultSections()
    {
        return [
            'banner_section' => [
                'title' => 'Κεντρικές Εικόνες Αρχικής',
                'subtitle' => '',
                'content' => [
                    'slides' => [
                        [
                            'src' => '/parents-council-platform-group5/public/assets/img/home-school-banner.png',
                            'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Εικόνα 1',
                        ],
                        [
                            'src' => '/parents-council-platform-group5/public/assets/img/home-school-banner-2.png',
                            'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Εικόνα 2',
                        ],
                        [
                            'src' => '/parents-council-platform-group5/public/assets/img/home-school-banner-3.png',
                            'alt' => 'Γυμνάσιο Αγίου Αθανασίου - Εικόνα 3',
                        ],
                    ],
                ],
            ],
            'hero_section' => [
                'title' => 'Σύνδεσμος Γονέων & Κηδεμόνων Γυμνασίου Αγίου Αθανασίου',
                'subtitle' => 'Στην ιστοσελίδα μας μπορείτε να ενημερώνεστε για όλες τις ανακοινώσεις, δράσεις και εκδηλώσεις του Συνδέσμου Γονέων. Μπορείτε να βρείτε χρήσιμες πληροφορίες, αιτήσεις, φωτογραφικό υλικό και πρωτοβουλίες που συμβάλλουν στη δημιουργία ενός καλύτερου σχολικού περιβάλλοντος για τα παιδιά μας.',
                'content' => [
                    'kicker' => 'Καλωσορίσατε στην επίσημη ιστοσελίδα',
                    'announcements_button_label' => 'Ανακοινώσεις',
                    'events_button_label' => 'Εκδηλώσεις',
                ],
            ],
            'calendar_section' => [
                'title' => 'Ημερολόγιο',
                'subtitle' => '',
                'content' => [],
            ],
            'announcements_section' => [
                'title' => 'Τελευταίες Ανακοινώσεις',
                'subtitle' => '',
                'content' => [
                    'button_label' => 'Όλες οι Ανακοινώσεις',
                ],
            ],
            'events_section' => [
                'title' => 'Τελευταίες Εκδηλώσεις',
                'subtitle' => '',
                'content' => [
                    'button_label' => 'Όλες οι Εκδηλώσεις',
                ],
            ],
        ];
    }
// Recursively katharizei arrays/strings se valid UTF-8 gia na apofeygontai DB/JSON encoding failures.
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
