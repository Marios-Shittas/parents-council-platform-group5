<?php
// Arxeio: app\services\EpikoinoniaPageService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
/**
 * EpikoinoniaPageService
 * Sxolio: voithitiko sxolio gia ton parakato kodika.
 */

require_once __DIR__ . '/../config/db.php';

class EpikoinoniaPageService
{
    private $conn;
    private $defaultSections;
    private $lastError = '';
// Arxikopoiei DB-vasismeno page-periexomeno ypiresia, fortwnei proepilegmena kai engyatai pinakas + arxikopoisi state.
    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
        $this->defaultSections = $this->buildDefaultSections();

        $this->ensureTable();
        $this->ensureDefaultSections();
    }
// Epistrefei merged sections tis selidas epikoinonias syndyazontas DB values me fallback proepilegmena.
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
// Epistrefei ena section tis selidas epikoinonias ana key apo ta merged dedomena.
    public function getSection($sectionKey)
    {
        $sections = $this->getAllSections();
        return $sections[$sectionKey] ?? null;
    }
// Kanei validate to section key, normalopoiei text/periexomeno encoding kai kanei upsert sto DB.
    public function updateSection($sectionKey, $title, $subtitle, array $content)
    {
        $this->lastError = '';

        if (!isset($this->defaultSections[$sectionKey])) {
            $this->lastError = 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ¿ section key.';
            return false;
        }

        $title = $this->normalizeUtf8($title);
        $subtitle = $this->normalizeUtf8($subtitle);
        $content = $this->normalizeUtf8($content);

        $contentJson = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($contentJson === false) {
            $this->lastError = 'Î‘Ï€Î¿Ï„Ï…Ï‡Î¯Î± Î¼ÎµÏ„Î±Ï„ÏÎ¿Ï€Î®Ï‚ Î´ÎµÎ´Î¿Î¼Î­Î½Ï‰Î½ ÏƒÎµ JSON.';
            return false;
        }

        $existsStmt = $this->conn->prepare('SELECT section_id FROM EpikoinoniaPageSections WHERE section_key = ? LIMIT 1');
        if (!$existsStmt) {
            $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± prepare lookup: ' . $this->conn->error;
            return false;
        }

        $existsStmt->bind_param('s', $sectionKey);
        if (!$existsStmt->execute()) {
            $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± execute lookup: ' . $existsStmt->error;
            return false;
        }

        $exists = $existsStmt->get_result()->num_rows > 0;

        if ($exists) {
            $sql = 'UPDATE EpikoinoniaPageSections
                    SET section_title = ?, section_subtitle = ?, content_json = ?
                    WHERE section_key = ?';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± prepare update: ' . $this->conn->error;
                return false;
            }

            $stmt->bind_param('ssss', $title, $subtitle, $contentJson, $sectionKey);
        } else {
            $sql = 'INSERT INTO EpikoinoniaPageSections (section_key, section_title, section_subtitle, content_json)
                    VALUES (?, ?, ?, ?)';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± prepare insert: ' . $this->conn->error;
                return false;
            }

            $stmt->bind_param('ssss', $sectionKey, $title, $subtitle, $contentJson);
        }

        if (!$stmt->execute()) {
            $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± Î±Ï€Î¿Î¸Î®ÎºÎµÏ…ÏƒÎ·Ï‚: ' . $stmt->error;
            return false;
        }

        return true;
    }
// Ekthenei to teleftaio ypiresia error gia admin UI feedback kai debugging.
    public function getLastError()
    {
        return $this->lastError;
    }
// Dimiourgei ton EpikoinoniaPageSections pinakas me unique section_key an leipei.
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
// Kanei arxikopoisi ta proepilegmeno sections mono otan leipoun, afhnontas anepheraxto to yparxon configured periexomeno.
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
// Dilwnei ti vasi domis/periexomenou gia ola ta editable sections tis selidas epikoinonias.
    private function buildDefaultSections()
    {
        return [
            'page_header' => [
                'title' => 'Î•Ï€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±',
                'subtitle' => 'Î•Ï€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î®ÏƒÏ„Îµ Î¼Î±Î¶Î¯ Î¼Î±Ï‚ Î³Î¹Î± Î¿Ï€Î¿Î¹Î±Î´Î®Ï€Î¿Ï„Îµ ÎµÏÏŽÏ„Î·ÏƒÎ· Î® Ï€Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯Î±.',
                'content' => [
                    'eyebrow' => 'Î¥Ï€Î¿ÏƒÏ„Î®ÏÎ¹Î¾Î· ÎšÎ±Î¹ Î£Ï„Î¿Î¹Ï‡ÎµÎ¯Î±',
                    'icon' => 'fas fa-envelope',
                ],
            ],
            'contact_info' => [
                'title' => 'Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚ Î•Ï€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±Ï‚',
                'subtitle' => 'Î’ÏÎµÎ¯Ï„Îµ Ï„Î· Î´Î¹ÎµÏÎ¸Ï…Î½ÏƒÎ·, Ï„Î± Ï„Î·Î»Î­Ï†Ï‰Î½Î±, Ï„Î¿ email ÎºÎ±Î¹ Ï„Î¿ Ï‰ÏÎ¬ÏÎ¹Î¿ Ï„Î·Ï‚ ÏƒÏ‡Î¿Î»Î¹ÎºÎ®Ï‚ Î¼Î¿Î½Î¬Î´Î±Ï‚.',
                'content' => [
                    'cards' => [
                        [
                            'title' => 'Î”Î¹ÎµÏÎ¸Ï…Î½ÏƒÎ·',
                            'text' => "Î§ÏÎ¯ÏƒÏ„Î¿Ï… Î Î±Ï€Î±Î´Î¿ÏÏÎ· 50\n4105 Î†Î³Î¹Î¿Ï‚ Î‘Î¸Î±Î½Î¬ÏƒÎ¹Î¿Ï‚, Î›ÎµÎ¼ÎµÏƒÏŒÏ‚",
                            'icon' => 'fas fa-map-marker-alt',
                            'link_label' => '',
                            'link_url' => '',
                        ],
                        [
                            'title' => 'Î¤Î·Î»Î­Ï†Ï‰Î½Î¿',
                            'text' => "Î¤Î·Î»Î­Ï†Ï‰Î½Î±: 25694750, 25694752\nÎ¤Î·Î»ÎµÎ¿Î¼Î¿Î¹ÏŒÏ„Ï…Ï€Î¿: 25694755",
                            'icon' => 'fas fa-phone',
                            'link_label' => '',
                            'link_url' => '',
                        ],
                        [
                            'title' => 'Email',
                            'text' => '',
                            'icon' => 'fas fa-envelope',
                            'link_label' => 'gym-ag-athanasios-lem@schools.ac.cy',
                            'link_url' => 'mailto:gym-ag-athanasios-lem@schools.ac.cy',
                        ],
                        [
                            'title' => 'ÎÏÎµÏ‚ Î›ÎµÎ¹Ï„Î¿Ï…ÏÎ³Î¯Î±Ï‚',
                            'text' => 'Î”ÎµÏ…-Î Î±Ï - 7.30-13.35',
                            'icon' => 'fas fa-clock',
                            'link_label' => '',
                            'link_url' => '',
                        ],
                    ],
                ],
            ],
            'map_section' => [
                'title' => 'Î’ÏÎµÎ¯Ï„Îµ Î¼Î±Ï‚ ÏƒÏ„Î¿ Î§Î¬ÏÏ„Î·',
                'subtitle' => 'Î— Ï„Î¿Ï€Î¿Î¸ÎµÏƒÎ¯Î± Ï„Î·Ï‚ ÏƒÏ‡Î¿Î»Î¹ÎºÎ®Ï‚ Î¼Î¿Î½Î¬Î´Î±Ï‚ ÏƒÏ„Î¿ Google Maps.',
                'content' => [
                    'embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3279.4575341666614!2d33.0611131!3d34.7188599!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14e734bc13013dc9%3A0x9c01ea2ef75a5b4d!2zzpPPhc68zr3OrM-DzrnOvyDOkc6zzq_Ov8-FIM6RzrjOsc69zrHPg86vzr_PhQ!5e0!3m2!1sel!2s!4v1773496500123!5m2!1sel!2s',
                ],
            ],
            'form_section' => [
                'title' => 'Î£Ï„ÎµÎ¯Î»Ï„Îµ Î¼Î±Ï‚ ÎœÎ®Î½Ï…Î¼Î±',
                'subtitle' => 'Î£Ï…Î¼Ï€Î»Î·ÏÏŽÏƒÏ„Îµ Ï„Î· Ï†ÏŒÏÎ¼Î± ÎºÎ±Î¹ Î¸Î± ÎµÏ€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î®ÏƒÎ¿Ï…Î¼Îµ Î¼Î±Î¶Î¯ ÏƒÎ±Ï‚ Ï„Î¿ ÏƒÏ…Î½Ï„Î¿Î¼ÏŒÏ„ÎµÏÎ¿ Î´Ï…Î½Î±Ï„ÏŒ.',
                'content' => [
                    'description' => '',
                    'button_text' => 'Î‘Ï€Î¿ÏƒÏ„Î¿Î»Î® ÎœÎ·Î½ÏÎ¼Î±Ï„Î¿Ï‚',
                    'success_message' => 'Î¤Î¿ Î¼Î®Î½Ï…Î¼Î¬ ÏƒÎ±Ï‚ Î»Î®Ï†Î¸Î·ÎºÎµ. Î˜Î± ÏƒÎ±Ï‚ Î±Ï€Î±Î½Ï„Î®ÏƒÎ¿Ï…Î¼Îµ Ï„Î¿ ÏƒÏ…Î½Ï„Î¿Î¼ÏŒÏ„ÎµÏÎ¿ Î´Ï…Î½Î±Ï„ÏŒ.',
                ],
            ],
            'social_section' => [
                'title' => 'Î’ÏÎµÎ¯Ï„Îµ Î¼Î±Ï‚ ÏƒÏ„Î± social networks',
                'subtitle' => 'Î‘ÎºÎ¿Î»Î¿Ï…Î¸Î®ÏƒÏ„Îµ Ï„Î¹Ï‚ ÎµÏ€Î¯ÏƒÎ·Î¼ÎµÏ‚ ÏƒÎµÎ»Î¯Î´ÎµÏ‚ Î¼Î±Ï‚ Î³Î¹Î± Î½Î­Î± ÎºÎ±Î¹ ÎµÎ½Î·Î¼ÎµÏÏŽÏƒÎµÎ¹Ï‚.',
                'content' => [
                    'items' => [
                        [
                            'title' => 'Facebook',
                            'url' => 'https://www.facebook.com/profile.php?id=100085835704152',
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
// Recursively normalopoiei to periexomeno payload se UTF-8-safe times prin tin apothikefsi.
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
