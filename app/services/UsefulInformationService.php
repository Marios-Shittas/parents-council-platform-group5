<?php
// Arxeio: app\services\UsefulInmorfiionService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
/**
 * UsefulInmorfiionService
 * Sxolio: voithitiko sxolio gia ton parakato kodika.
 */

require_once __DIR__ . '/../config/db.php';

class UsefulInformationService
{
    private $conn;
    private $defaultSections;
    private $lastError = '';
// Arxikopoiei to ypiresia state, eksasfalizei DB schema/proepilegmeno records kai efarmozei legacy data fixes.
    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
        $this->defaultSections = $this->buildDefaultSections();

        $this->ensureTable();
        $this->ensureDefaultSections();
        $this->applyLegacyContentAdjustments();
    }
// Epistrefei merged sections Useful Information opou ta valid DB data kanoun override sta proepilegmena.
    public function getAllSections()
    {
        $sections = $this->defaultSections;
        $result = $this->conn->query('SELECT * FROM UsefulInformationSections');

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
// Epistrefei ena section tis Useful Information ana key apo to merged section set.
    public function getSection($sectionKey)
    {
        $sections = $this->getAllSections();
        return $sections[$sectionKey] ?? null;
    }
// Elegxei section key kai payload, normalopoiei UTF-8, serialopoiei periexomeno JSON,
// kai meta kanei enimerosi/eisagogi apothikevontas perigrafika errors se apotyxia.
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

        $existsStmt = $this->conn->prepare('SELECT section_id FROM UsefulInformationSections WHERE section_key = ? LIMIT 1');
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
            $sql = 'UPDATE UsefulInformationSections
                    SET section_title = ?, section_subtitle = ?, content_json = ?
                    WHERE section_key = ?';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± prepare update: ' . $this->conn->error;
                return false;
            }

            $stmt->bind_param('ssss', $title, $subtitle, $contentJson, $sectionKey);
        } else {
            $sql = 'INSERT INTO UsefulInformationSections (section_key, section_title, section_subtitle, content_json)
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
// Epistrefei to pio prosfato validation/persistence error gia UI i logging.
    public function getLastError()
    {
        return $this->lastError;
    }
// Epanaferei ola ta configurable sections stis canonical proepilegmeno times.
    public function resetAllSectionsToDefaults()
    {
        $this->lastError = '';

        foreach ($this->defaultSections as $sectionKey => $section) {
            $saved = $this->updateSection(
                $sectionKey,
                $section['title'],
                $section['subtitle'],
                $section['content']
            );

            if (!$saved) {
                return false;
            }
        }

        return true;
    }
// Dimiourgei ton UsefulInformationSections pinakas kai uniqueness constraint sto section_key otan leipei.
    private function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS UsefulInformationSections (
                    section_id INT NOT NULL AUTO_INCREMENT,
                    section_key VARCHAR(100) NOT NULL,
                    section_title VARCHAR(255) NOT NULL,
                    section_subtitle TEXT DEFAULT NULL,
                    content_json LONGTEXT DEFAULT NULL,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (section_id),
                    UNIQUE KEY uq_useful_information_section_key (section_key)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->conn->query($sql);
    }
// Kanei arxikopoisi sta missing proepilegmeno sections xwris overwrite sta yparxonta customized records.
    private function ensureDefaultSections()
    {
        foreach ($this->defaultSections as $sectionKey => $section) {
            $stmt = $this->conn->prepare('SELECT section_id FROM UsefulInformationSections WHERE section_key = ? LIMIT 1');
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
// Efarmozei one-time compatibility migrations gia palia subtitles/titles/link items
// oste to legacy stored periexomeno na tairiazei me tin trexousa domi kai wording.
    private function applyLegacyContentAdjustments()
    {
        $legacyPageHeaderSubtitle = 'Î£Ï…Î³ÎºÎµÎ½Ï„ÏÏ‰Î¼Î­Î½ÎµÏ‚ Î²Î±ÏƒÎ¹ÎºÎ­Ï‚ Ï€Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚ Î³Î¹Î± Ï„Î· ÏƒÏ‡Î¿Î»Î¹ÎºÎ® Ï‡ÏÎ¿Î½Î¹Î¬, Ï„Î¹Ï‚ Î±ÏÎ³Î¯ÎµÏ‚, Ï„Î· ÏƒÏ„Î¿Î»Î®, Ï„Î·Î½ Î±ÏƒÏ†Î¬Î»ÎµÎ¹Î± ÎºÎ±Î¹ Ï„Î± Ï‡ÏÎ®ÏƒÎ¹Î¼Î± Î­Î½Ï„Ï…Ï€Î±.';
        $currentSymbolSubtitle = 'Î§ÏÎ®ÏƒÎ¹Î¼Î¿Î¹ Î£Ï…Î½Î´Î­ÏƒÎ¼Î¿Î¹ & Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚/ Î•Î½Î·Î¼ÎµÏÏ‰Ï„Î¹ÎºÏŒ Î¥Î»Î¹ÎºÏŒ/ ÎˆÎ½Ï„Ï…Ï€Î± & Î•Î½Î·Î¼ÎµÏÏŽÏƒÎµÎ¹Ï‚';
        $oldSpacedSubtitle = 'Î§ÏÎ®ÏƒÎ¹Î¼Î¿Î¹ ÏƒÏÎ½Î´ÎµÏƒÎ¼Î¿Î¹ ÎºÎ±Î¹ Ï€Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚, ÎµÎ½Î·Î¼ÎµÏÏ‰Ï„Î¹ÎºÏŒ Ï…Î»Î¹ÎºÏŒ, Î­Î½Ï„Ï…Ï€Î± ÎºÎ±Î¹ ÎµÎ½Î·Î¼ÎµÏÏŽÏƒÎµÎ¹Ï‚.';
        $newPageHeaderTitle = 'Î§ÏÎ®ÏƒÎ¹Î¼ÎµÏ‚ Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚ & Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Î¹';
        $newPageHeaderSubtitle = 'Î§ÏÎ®ÏƒÎ¹Î¼Î¿Î¹ ÏƒÏÎ½Î´ÎµÏƒÎ¼Î¿Î¹ ÎºÎ±Î¹ Ï€Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚, ÎµÎ½Î·Î¼ÎµÏÏ‰Ï„Î¹ÎºÏŒ Ï…Î»Î¹ÎºÏŒ, Î­Î½Ï„Ï…Ï€Î± ÎºÎ±Î¹ ÎµÎ½Î·Î¼ÎµÏÏŽÏƒÎµÎ¹Ï‚.';

        $pageHeader = $this->getSection('page_header');
        if (is_array($pageHeader)) {
            $currentTitle = (string)($pageHeader['title'] ?? '');
            $currentSubtitle = (string)($pageHeader['subtitle'] ?? '');
            $shouldUpdatePageHeader = (
                $currentSubtitle === $legacyPageHeaderSubtitle
                || $currentSubtitle === $currentSymbolSubtitle
                || $currentSubtitle === $oldSpacedSubtitle
                || $currentTitle === 'Î§ÏÎ®ÏƒÎ¹Î¼Î¿Î¹ Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Î¹ & Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚'
                || $currentTitle === 'Î§ÏÎ®ÏƒÎ¹Î¼Î¿Î¹ Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Î¹ ÎºÎ±Î¹ Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚'
                || $currentTitle === 'Î§ÏÎ®ÏƒÎ¹Î¼Î¿Î¹ Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚ & Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Î¹'
            );

            if ($shouldUpdatePageHeader) {
                $this->updateSection(
                    'page_header',
                    $newPageHeaderTitle,
                    $newPageHeaderSubtitle,
                    is_array($pageHeader['content'] ?? null) ? $pageHeader['content'] : []
                );
            }
        }

        $quickLinks = $this->getSection('quick_links');
        if (!is_array($quickLinks)) {
            return;
        }

        $content = is_array($quickLinks['content'] ?? null) ? $quickLinks['content'] : [];
        $items = $content['items'] ?? [];
        if (!is_array($items)) {
            return;
        }

        $changed = false;
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }

            $itemTitle = trim((string)($item['title'] ?? ''));
            if ($itemTitle !== 'Î•ÎºÏ€Î±Î¹Î´ÎµÏ…Ï„Î¹ÎºÎ¿Î¯ Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Î¹' && $itemTitle !== 'ÎˆÎ½Ï„Ï…Ï€Î± Î‘ÏƒÏ†Î¬Î»ÎµÎ¹Î±Ï‚') {
                continue;
            }

            $items[$index]['title'] = 'Î ÏÎ»Î· Î‘Ï€Î¿Ï…ÏƒÎ¹Î¿Î»Î¿Î³Î¯Î¿Ï…';
            $items[$index]['description'] = 'Î†Î¼ÎµÏƒÎ· Ï€ÏÏŒÏƒÎ²Î±ÏƒÎ· ÏƒÏ„Î·Î½ Ï€ÏÎ»Î· Î±Ï€Î¿Ï…ÏƒÎ¹Î¿Î»Î¿Î³Î¯Î¿Ï… Ï„Î¿Ï… ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï….';
            $items[$index]['url'] = 'http://www.gym-ag-athanasios-lem.eschoolsupport.com/';
            $changed = true;
        }

        if (!$changed) {
            return;
        }

        $content['items'] = $items;

        $this->updateSection(
            'quick_links',
            (string)($quickLinks['title'] ?? ''),
            (string)($quickLinks['subtitle'] ?? ''),
            $content
        );
    }
// Orizei to plires proepilegmeno periexomeno schema (header, links, school year, holidays, safety, uniform).
    private function buildDefaultSections()
    {
        return [
            'page_header' => [
                'title' => 'Î§ÏÎ®ÏƒÎ¹Î¼ÎµÏ‚ Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚ & Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Î¹',
                'subtitle' => 'Î§ÏÎ®ÏƒÎ¹Î¼Î¿Î¹ ÏƒÏÎ½Î´ÎµÏƒÎ¼Î¿Î¹ ÎºÎ±Î¹ Ï€Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚, ÎµÎ½Î·Î¼ÎµÏÏ‰Ï„Î¹ÎºÏŒ Ï…Î»Î¹ÎºÏŒ, Î­Î½Ï„Ï…Ï€Î± ÎºÎ±Î¹ ÎµÎ½Î·Î¼ÎµÏÏŽÏƒÎµÎ¹Ï‚.',
                'content' => [
                    'eyebrow' => 'ÎŸÎ´Î·Î³ÏŒÏ‚ Î“Î¿Î½Î­Ï‰Î½ ÎšÎ±Î¹ ÎœÎ±Î¸Î·Ï„ÏŽÎ½',
                ],
            ],
            'quick_links' => [
                'title' => 'Î“ÏÎ®Î³Î¿ÏÎ¿Î¹ Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Î¹',
                'subtitle' => 'Î†Î¼ÎµÏƒÎ· Ï€ÏÏŒÏƒÎ²Î±ÏƒÎ· ÏƒÏ„Î¹Ï‚ Ï€Î¹Î¿ Ï‡ÏÎ®ÏƒÎ¹Î¼ÎµÏ‚ ÎµÏ€Î¯ÏƒÎ·Î¼ÎµÏ‚ ÏƒÎµÎ»Î¯Î´ÎµÏ‚.',
                'content' => [
                    'items' => [
                        [
                            'title' => 'Î™ÏƒÏ„Î¿ÏƒÎµÎ»Î¯Î´Î± Î£Ï‡Î¿Î»ÎµÎ¯Î¿Ï…',
                            'description' => 'Î— ÎµÏ€Î¯ÏƒÎ·Î¼Î· Î¹ÏƒÏ„Î¿ÏƒÎµÎ»Î¯Î´Î± Ï„Î¿Ï… Î“Ï…Î¼Î½Î±ÏƒÎ¯Î¿Ï… Î‘Î³Î¯Î¿Ï… Î‘Î¸Î±Î½Î±ÏƒÎ¯Î¿Ï….',
                            'url' => 'https://gym-ag-athanasios-lem.schools.ac.cy/',
                            'icon' => 'fas fa-school',
                        ],
                        [
                            'title' => 'ÎˆÎ½Ï„Ï…Ï€Î± & Î•Î³Î³ÏÎ±Ï†Î­Ï‚',
                            'description' => 'Î£ÎµÎ»Î¯Î´Î± Î¼Îµ Ï‡ÏÎ®ÏƒÎ¹Î¼Î± Î­Î½Ï„Ï…Ï€Î± ÎµÎ³Î³ÏÎ±Ï†ÏŽÎ½, Î¼ÎµÏ„Î±ÎºÎ¹Î½Î®ÏƒÎµÏ‰Î½ ÎºÎ±Î¹ Î±Î½Î±ÎºÎ¿Î¹Î½ÏŽÏƒÎµÏ‰Î½.',
                            'url' => 'https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations',
                            'icon' => 'fas fa-file-download',
                        ],
                        [
                            'title' => 'Î ÏÎ»Î· Î‘Ï€Î¿Ï…ÏƒÎ¹Î¿Î»Î¿Î³Î¯Î¿Ï…',
                            'description' => 'Î†Î¼ÎµÏƒÎ· Ï€ÏÏŒÏƒÎ²Î±ÏƒÎ· ÏƒÏ„Î·Î½ Ï€ÏÎ»Î· Î±Ï€Î¿Ï…ÏƒÎ¹Î¿Î»Î¿Î³Î¯Î¿Ï… Ï„Î¿Ï… ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï….',
                            'url' => 'http://www.gym-ag-athanasios-lem.eschoolsupport.com/',
                            'icon' => 'fas fa-shield-alt',
                        ],
                    ],
                ],
            ],
            'school_year' => [
                'title' => 'Î£Ï‡Î¿Î»Î¹ÎºÎ® Î§ÏÎ¿Î½Î¹Î¬ 2025-2026',
                'subtitle' => 'Î’Î±ÏƒÎ¹ÎºÎ­Ï‚ Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯ÎµÏ‚ Î³Î¹Î± Ï„Î± Î´Î·Î¼ÏŒÏƒÎ¹Î± Î³Ï…Î¼Î½Î¬ÏƒÎ¹Î± ÏƒÏ„Î·Î½ ÎšÏÏ€ÏÎ¿.',
                'content' => [
                    'items' => [
                        [
                            'label' => "ÎˆÎ½Î±ÏÎ¾Î· Î‘' Î¤ÎµÏ„ÏÎ±Î¼Î®Î½Î¿Ï…",
                            'date' => '5 Î£ÎµÏ€Ï„ÎµÎ¼Î²ÏÎ¯Î¿Ï… 2025',
                            'description' => 'ÎˆÎ½Î±ÏÎ¾Î· Ï„Î·Ï‚ ÏƒÏ‡Î¿Î»Î¹ÎºÎ®Ï‚ Ï‡ÏÎ¿Î½Î¹Î¬Ï‚ Î³Î¹Î± Ï„Î· ÎœÎ­ÏƒÎ· Î•ÎºÏ€Î±Î¯Î´ÎµÏ…ÏƒÎ·.',
                        ],
                        [
                            'label' => "Î›Î®Î¾Î· Î‘' Î¤ÎµÏ„ÏÎ±Î¼Î®Î½Î¿Ï…",
                            'date' => '15 Î™Î±Î½Î¿Ï…Î±ÏÎ¯Î¿Ï… 2026',
                            'description' => 'ÎŸÎ»Î¿ÎºÎ»Î®ÏÏ‰ÏƒÎ· Ï„Î¿Ï… Ï€ÏÏŽÏ„Î¿Ï… Ï„ÎµÏ„ÏÎ±Î¼Î®Î½Î¿Ï….',
                        ],
                        [
                            'label' => "Î’' Î¤ÎµÏ„ÏÎ¬Î¼Î·Î½Î¿",
                            'date' => '16 Î™Î±Î½Î¿Ï…Î±ÏÎ¯Î¿Ï… 2026',
                            'description' => 'Î£Ï…Î½ÎµÏ‡Î¯Î¶ÎµÏ„Î±Î¹ Î¼Î­Ï‡ÏÎ¹ Ï„Î¿ Ï„Î­Î»Î¿Ï‚ Ï„Ï‰Î½ Ï€ÏÎ¿Î±Î³Ï‰Î³Î¹ÎºÏŽÎ½ ÎµÎ¾ÎµÏ„Î¬ÏƒÎµÏ‰Î½.',
                        ],
                    ],
                    'note' => 'Î— Î±ÎºÏÎ¹Î²Î®Ï‚ Ï„ÎµÎ»ÎµÏ…Ï„Î±Î¯Î± Î·Î¼Î­ÏÎ± Ï†Î¿Î¯Ï„Î·ÏƒÎ·Ï‚ ÎµÎ¾Î±ÏÏ„Î¬Ï„Î±Î¹ Î±Ï€ÏŒ Ï„Î¿ Ï€ÏÏŒÎ³ÏÎ±Î¼Î¼Î± Ï„Ï‰Î½ Ï€ÏÎ¿Î±Î³Ï‰Î³Î¹ÎºÏŽÎ½ ÎµÎ¾ÎµÏ„Î¬ÏƒÎµÏ‰Î½ ÎºÎ±Î¹ Ï„Î¹Ï‚ Î±Î½Î±ÎºÎ¿Î¹Î½ÏŽÏƒÎµÎ¹Ï‚ Ï„Î·Ï‚ ÏƒÏ‡Î¿Î»Î¹ÎºÎ®Ï‚ Î¼Î¿Î½Î¬Î´Î±Ï‚.',
                ],
            ],
            'holidays' => [
                'title' => 'Î•Ï€Î¯ÏƒÎ·Î¼ÎµÏ‚ Î‘ÏÎ³Î¯ÎµÏ‚',
                'subtitle' => 'ÎŸÎ¹ Î²Î±ÏƒÎ¹ÎºÎ­Ï‚ ÏƒÏ‡Î¿Î»Î¹ÎºÎ­Ï‚ Î±ÏÎ³Î¯ÎµÏ‚ Ï€Î¿Ï… Î¹ÏƒÏ‡ÏÎ¿Ï…Î½ Î³Î¹Î± Ï„Î± Î´Î·Î¼ÏŒÏƒÎ¹Î± Î³Ï…Î¼Î½Î¬ÏƒÎ¹Î±.',
                'content' => [
                    'rows' => [
                        ['date' => '1 ÎŸÎºÏ„Ï‰Î²ÏÎ¯Î¿Ï… 2025', 'name' => 'Î—Î¼Î­ÏÎ± Î‘Î½ÎµÎ¾Î±ÏÏ„Î·ÏƒÎ¯Î±Ï‚ Ï„Î·Ï‚ ÎšÏÏ€ÏÎ¿Ï…'],
                        ['date' => '28 ÎŸÎºÏ„Ï‰Î²ÏÎ¯Î¿Ï… 2025', 'name' => 'Î•Î¸Î½Î¹ÎºÎ® Î•Ï€Î­Ï„ÎµÎ¹Î¿Ï‚'],
                        ['date' => '11 Î”ÎµÎºÎµÎ¼Î²ÏÎ¯Î¿Ï… 2025', 'name' => 'Î—Î¼Î­ÏÎ± Î•ÎºÏ€Î±Î¹Î´ÎµÏ…Ï„Î¹ÎºÎ¿Ï'],
                        ['date' => '24 Î”ÎµÎºÎµÎ¼Î²ÏÎ¯Î¿Ï… 2025 - 6 Î™Î±Î½Î¿Ï…Î±ÏÎ¯Î¿Ï… 2026', 'name' => 'Î”Î¹Î±ÎºÎ¿Ï€Î­Ï‚ Î§ÏÎ¹ÏƒÏ„Î¿Ï…Î³Î­Î½Î½Ï‰Î½'],
                        ['date' => '30 Î™Î±Î½Î¿Ï…Î±ÏÎ¯Î¿Ï… 2026', 'name' => 'Î¤ÏÎ¹ÏŽÎ½ Î™ÎµÏÎ±ÏÏ‡ÏŽÎ½ ÎºÎ±Î¹ Î•Î»Î»Î·Î½Î¹ÎºÏŽÎ½ Î“ÏÎ±Î¼Î¼Î¬Ï„Ï‰Î½'],
                        ['date' => '10 Î¦ÎµÎ²ÏÎ¿Ï…Î±ÏÎ¯Î¿Ï… 2026', 'name' => 'Î—Î¼Î­ÏÎ± Î•ÎºÏ€Î±Î¹Î´ÎµÏ…Ï„Î¹ÎºÎ¿Ï'],
                        ['date' => '23 Î¦ÎµÎ²ÏÎ¿Ï…Î±ÏÎ¯Î¿Ï… 2026', 'name' => 'ÎšÎ±Î¸Î±ÏÎ¬ Î”ÎµÏ…Ï„Î­ÏÎ±'],
                        ['date' => '25 ÎœÎ±ÏÏ„Î¯Î¿Ï… 2026', 'name' => 'Î•Î¸Î½Î¹ÎºÎ® Î•Ï€Î­Ï„ÎµÎ¹Î¿Ï‚'],
                        ['date' => '1 Î‘Ï€ÏÎ¹Î»Î¯Î¿Ï… 2026', 'name' => 'Î•Î¸Î½Î¹ÎºÎ® Î•Ï€Î­Ï„ÎµÎ¹Î¿Ï‚ Î•ÎŸÎšÎ‘'],
                        ['date' => '6 Î‘Ï€ÏÎ¹Î»Î¯Î¿Ï… - 19 Î‘Ï€ÏÎ¹Î»Î¯Î¿Ï… 2026', 'name' => 'Î”Î¹Î±ÎºÎ¿Ï€Î­Ï‚ Î Î¬ÏƒÏ‡Î±'],
                        ['date' => '23 Î‘Ï€ÏÎ¹Î»Î¯Î¿Ï… 2026', 'name' => 'ÎŸÎ½Î¿Î¼Î±ÏƒÏ„Î®ÏÎ¹Î± Î‘ÏÏ‡Î¹ÎµÏ€Î¹ÏƒÎºÏŒÏ€Î¿Ï… ÎšÏÏ€ÏÎ¿Ï…'],
                        ['date' => '1 ÎœÎ±ÎÎ¿Ï… 2026', 'name' => 'Î ÏÏ‰Ï„Î¿Î¼Î±Î³Î¹Î¬'],
                        ['date' => '1 Î™Î¿Ï…Î½Î¯Î¿Ï… 2026', 'name' => 'Î‘Î³Î¯Î¿Ï… Î Î½ÎµÏÎ¼Î±Ï„Î¿Ï‚'],
                        ['date' => '11 Î™Î¿Ï…Î½Î¯Î¿Ï… 2026', 'name' => 'Î‘Ï€Î¿ÏƒÏ„ÏŒÎ»Î¿Ï… Î’Î±ÏÎ½Î¬Î²Î±'],
                    ],
                ],
            ],
            'safety' => [
                'title' => 'Î‘ÏƒÏ†Î¬Î»ÎµÎ¹Î± Î Î±Î¹Î´Î¹ÏŽÎ½ & Î§ÏÎ®ÏƒÎ¹Î¼Î± ÎˆÎ½Ï„Ï…Ï€Î±',
                'subtitle' => 'Î§ÏÎ®ÏƒÎ¹Î¼Î· ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ· Î³Î¹Î± Î±ÏƒÏ†Î¬Î»ÎµÎ¹Î± ÏƒÏ„Î¿ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿ ÎºÎ±Î¹ ÎµÏ€Î¯ÏƒÎ·Î¼ÎµÏ‚ Î»Î®ÏˆÎµÎ¹Ï‚ ÎµÎ½Ï„ÏÏ€Ï‰Î½.',
                'content' => [
                    'bullets' => [
                        'Î“Î¹Î± Î¸Î­Î¼Î±Ï„Î± Ï€ÏÏŒÎ»Î·ÏˆÎ·Ï‚, Î±ÏƒÏ†Î¬Î»ÎµÎ¹Î±Ï‚ ÎºÎ±Î¹ Ï…Î³ÎµÎ¯Î±Ï‚ ÏƒÏ„Î¿ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿, Î±ÏÎ¼ÏŒÎ´Î¹Î¿ ÎµÎ¯Î½Î±Î¹ Ï„Î¿ Î“ÏÎ±Ï†ÎµÎ¯Î¿ Î Î¿Î»Î¹Ï„Î¹ÎºÎ®Ï‚ Î†Î¼Ï…Î½Î±Ï‚, Î‘ÏƒÏ†Î¬Î»ÎµÎ¹Î±Ï‚ ÎºÎ±Î¹ Î¥Î³ÎµÎ¯Î±Ï‚ Ï„Î¿Ï… Î¥Î Î‘Î.',
                        'Î£Îµ Ï€ÎµÏÎ¯Ï€Ï„Ï‰ÏƒÎ· Ï€ÎµÏÎ¹ÏƒÏ„Î±Ï„Î¹ÎºÎ¿Ï Î® Î±Ï„Ï…Ï‡Î®Î¼Î±Ï„Î¿Ï‚, Î· ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ· Ï„Î·Ï‚ ÏƒÏ‡Î¿Î»Î¹ÎºÎ®Ï‚ Î¼Î¿Î½Î¬Î´Î±Ï‚ Ï€ÏÎ­Ï€ÎµÎ¹ Î½Î± Î³Î¯Î½ÎµÏ„Î±Î¹ Î¬Î¼ÎµÏƒÎ±, ÏŽÏƒÏ„Îµ Î½Î± Î±ÎºÎ¿Î»Î¿Ï…Î¸Î·Î¸ÎµÎ¯ Î· Ï€ÏÎ¿Î²Î»ÎµÏ€ÏŒÎ¼ÎµÎ½Î· Î´Î¹Î±Î´Î¹ÎºÎ±ÏƒÎ¯Î±.',
                        'Î“Î¹Î± ÎµÏ€Î¯ÏƒÎ·Î¼Î± Î­Î½Ï„Ï…Ï€Î± ÎºÎ±Ï„Î±Î³ÏÎ±Ï†Î®Ï‚ Î±Ï„Ï…Ï‡Î·Î¼Î¬Ï„Ï‰Î½ ÎºÎ±Î¹ Î¬Î»Î»Î± ÏƒÏ‡ÎµÏ„Î¹ÎºÎ¬ Î­Î³Î³ÏÎ±Ï†Î±, Ï‡ÏÎ·ÏƒÎ¹Î¼Î¿Ï€Î¿Î¹ÎµÎ¯Ï„Îµ Ï„Î± Î­Î½Ï„Ï…Ï€Î± Ï„Î¿Ï… Î¥Î Î‘Î.',
                        'Î“Î¹Î± ÎµÏ„Î®ÏƒÎ¹ÎµÏ‚ Î±Î½Î±ÎºÎ¿Î¹Î½ÏŽÏƒÎµÎ¹Ï‚ ÏƒÏ‡ÎµÏ„Î¹ÎºÎ¬ Î¼Îµ Ï€Î¹Î¸Î±Î½Î® Î±ÏƒÏ†Î±Î»Î¹ÏƒÏ„Î¹ÎºÎ® ÎºÎ¬Î»Ï…ÏˆÎ· Î¼Î±Î¸Î·Ï„ÏŽÎ½, Î¿Î¹ Î³Î¿Î½ÎµÎ¯Ï‚ Î¸Î± Ï€ÏÎ­Ï€ÎµÎ¹ Î½Î± Ï€Î±ÏÎ±ÎºÎ¿Î»Î¿Ï…Î¸Î¿ÏÎ½ Ï„Î¹Ï‚ Î±Î½Î±ÎºÎ¿Î¹Î½ÏŽÏƒÎµÎ¹Ï‚ Ï„Î¿Ï… ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï… ÎºÎ±Î¹ Ï„Î¿Ï… Î£Ï…Î½Î´Î­ÏƒÎ¼Î¿Ï… Î“Î¿Î½Î­Ï‰Î½.',
                    ],
                    'downloads' => [
                        [
                            'title' => 'ÎˆÎ½Ï„Ï…Ï€Î± Î‘ÏƒÏ†Î¬Î»ÎµÎ¹Î±Ï‚ ÎºÎ±Î¹ ÎšÎ±Ï„Î±Î³ÏÎ±Ï†Î®Ï‚ Î‘Ï„Ï…Ï‡Î·Î¼Î¬Ï„Ï‰Î½',
                            'url' => 'https://www.moec.gov.cy/politiki_amyna/ay_entypa.html',
                            'icon' => 'fas fa-download',
                        ],
                        [
                            'title' => 'Î•Ï€Î¹Î¼Î¿ÏÏ†Ï‰Ï„Î¹ÎºÏŒ Î¥Î»Î¹ÎºÏŒ Î‘ÏƒÏ†Î¬Î»ÎµÎ¹Î±Ï‚ ÎºÎ±Î¹ Î¥Î³ÎµÎ¯Î±Ï‚',
                            'url' => 'https://www.moec.gov.cy/politiki_amyna/ay_epimorfotiko_yliko.html',
                            'icon' => 'fas fa-book-open',
                        ],
                        [
                            'title' => 'ÎˆÎ½Ï„Ï…Ï€Î± ÎºÎ±Î¹ Î±Î½Î±ÎºÎ¿Î¹Î½ÏŽÏƒÎµÎ¹Ï‚ Ï„Î¿Ï… ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï…',
                            'url' => 'https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations',
                            'icon' => 'fas fa-folder-open',
                        ],
                    ],
                ],
            ],
            'uniform' => [
                'title' => 'ÎœÎ±Î¸Î·Ï„Î¹ÎºÎ® Î£Ï„Î¿Î»Î®',
                'subtitle' => 'Î£Ï…Î½Î¿Ï€Ï„Î¹ÎºÎ® Ï€Î±ÏÎ¿Ï…ÏƒÎ¯Î±ÏƒÎ· Î¼Îµ Î²Î¬ÏƒÎ· Ï„Î¿Ï…Ï‚ ÎµÏƒÏ‰Ï„ÎµÏÎ¹ÎºÎ¿ÏÏ‚ ÎºÎ±Î½Î¿Î½Î¹ÏƒÎ¼Î¿ÏÏ‚ Ï„Î¿Ï… ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï….',
                'content' => [
                    'cards' => [
                        [
                            'title' => 'Î‘Î³ÏŒÏÎ¹Î±',
                            'items' => [
                                'Î“ÎºÏÎ¯Î¶Î¿ Ï€Î±Î½Ï„ÎµÎ»ÏŒÎ½Î¹',
                                'Î†ÏƒÏ€ÏÎ¿ Ï€Î¿Ï…ÎºÎ¬Î¼Î¹ÏƒÎ¿, T-shirt Î® polo',
                                'ÎœÏ€Î»Îµ ÏƒÎºÎ¿ÏÏÎ¿ Ï€Î¿Ï…Î»ÏŒÎ²ÎµÏ',
                                'Î”ÎµÎ½ ÎµÏ€Î¹Ï„ÏÎ­Ï€Î¿Î½Ï„Î±Î¹ jeans Î® Î±Î¸Î»Î·Ï„Î¹ÎºÎ­Ï‚ Ï†ÏŒÏÎ¼ÎµÏ‚ ÏƒÏ„Î·Î½ ÎºÎ±Î¸Î·Î¼ÎµÏÎ¹Î½Î® ÏƒÏ„Î¿Î»Î®',
                            ],
                        ],
                        [
                            'title' => 'ÎšÎ¿ÏÎ¯Ï„ÏƒÎ¹Î±',
                            'items' => [
                                'Î“ÎºÏÎ¯Î¶Î± Ï†Î¿ÏÏƒÏ„Î± Î® Î³ÎºÏÎ¯Î¶Î¿ Ï€Î±Î½Ï„ÎµÎ»ÏŒÎ½Î¹',
                                'Î†ÏƒÏ€ÏÎ¿ Ï€Î¿Ï…ÎºÎ¬Î¼Î¹ÏƒÎ¿, T-shirt Î® polo',
                                'ÎœÏ€Î»Îµ ÏƒÎºÎ¿ÏÏÎ¿ Ï€Î¿Ï…Î»ÏŒÎ²ÎµÏ',
                                'Î”ÎµÎ½ ÎµÏ€Î¹Ï„ÏÎ­Ï€Î¿Î½Ï„Î±Î¹ jeans Î® ÎºÎ¿Î»Î¬Î½ ÏƒÏ„Î·Î½ ÎºÎ±Î¸Î·Î¼ÎµÏÎ¹Î½Î® ÏƒÏ„Î¿Î»Î®',
                            ],
                        ],
                        [
                            'title' => 'Î£Ï„Î¿Î»Î® Î“Ï…Î¼Î½Î±ÏƒÏ„Î¹ÎºÎ®Ï‚',
                            'items' => [
                                'ÎœÎ±ÏÏÎ¿ Î® Î¼Ï€Î»Îµ Ï€Î±Î½Ï„ÎµÎ»ÏŒÎ½Î¹ Ï†ÏŒÏÎ¼Î±Ï‚',
                                'Î†ÏƒÏ€ÏÎ·, Î³ÎºÏÎ¯Î¶Î± Î® ÏƒÏ‡Î¿Î»Î¹ÎºÎ® Ï†Î±Î½Î­Î»Î±',
                                'Î‘Î¸Î»Î·Ï„Î¹ÎºÎ¬ Ï€Î±Ï€Î¿ÏÏ„ÏƒÎ¹Î±',
                                'Î ÏÎ±ÎºÏ„Î¹ÎºÎ® ÎºÎ±Î¹ Î±ÏƒÏ†Î±Î»Î®Ï‚ ÎµÎ½Î´Ï…Î¼Î±ÏƒÎ¯Î± Î³Î¹Î± Ï„Î¿ Î¼Î¬Î¸Î·Î¼Î± Î¦Ï…ÏƒÎ¹ÎºÎ®Ï‚ Î‘Î³Ï‰Î³Î®Ï‚',
                            ],
                        ],
                    ],
                    'note' => 'Î“Î¹Î± Ï„Î¹Ï‚ Ï€Î»Î®ÏÎµÎ¹Ï‚ Î»ÎµÏ€Ï„Î¿Î¼Î­ÏÎµÎ¹ÎµÏ‚ Ï„Î·Ï‚ ÏƒÏ„Î¿Î»Î®Ï‚ ÎºÎ±Î¹ Ï„Ï‰Î½ ÎºÎ±Î½Î¿Î½Î¹ÏƒÎ¼ÏŽÎ½, Î´ÎµÎ¯Ï„Îµ Ï„Î¿Ï…Ï‚ ÎµÏ€Î¯ÏƒÎ·Î¼Î¿Ï…Ï‚ ÎµÏƒÏ‰Ï„ÎµÏÎ¹ÎºÎ¿ÏÏ‚ ÎºÎ±Î½Î¿Î½Î¹ÏƒÎ¼Î¿ÏÏ‚ Ï„Î¿Ï… ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï….',
                    'button_text' => 'Î ÏÎ¿Î²Î¿Î»Î® ÎšÎ±Î½Î¿Î½Î¹ÏƒÎ¼ÏŽÎ½',
                    'button_url' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/september/esoterikoi-kanonismoi-2025-2026.pdf',
                ],
            ],
        ];
    }
// Recursively epidiorthwnei/normalopoiei UTF-8 gia asfales JSON encoding kai DB writes.
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
// Epistrefei to holiday grammes array apo to holidays section me safe fallback se keni lista.
    public function getHolidayRows()
    {
        $sections = $this->getAllSections();
        $rows = $sections['holidays']['content']['rows'] ?? [];
        return is_array($rows) ? $rows : [];
    }
// Metatrepei ta holiday grammes se normalized calendar items taksinomimena me ISO date.
    public function getHolidayCalendarItems()
    {
        $items = [];

        foreach ($this->getHolidayRows() as $holiday) {
            $title = trim((string)($holiday['name'] ?? ''));
            $isoDate = $this->buildHolidaySortKey((string)($holiday['date'] ?? ''));

            if ($title === '' || $isoDate === '9999-99-99') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'description' => '',
                'date' => $isoDate,
                'type' => 'holiday',
            ];
        }

        usort($items, static function ($left, $right) {
            return strcmp((string)($left['date'] ?? ''), (string)($right['date'] ?? ''));
        });

        return $items;
    }
// Kanei map ta school-year milestones se calendar event items kai ta taksinomei chronologically.
    public function getSchoolYearCalendarItems()
    {
        $section = $this->getSection('school_year');
        if (!is_array($section)) {
            return [];
        }

        $rawItems = $section['content']['items'] ?? [];
        if (!is_array($rawItems)) {
            return [];
        }

        $items = [];
        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title = trim((string)($item['label'] ?? ''));
            $description = trim((string)($item['description'] ?? ''));
            $isoDate = $this->convertSchoolYearDisplayDateToIso((string)($item['date'] ?? ''));

            if ($title === '' || $isoDate === null) {
                continue;
            }

            $items[] = [
                'title' => $title,
                'description' => $description,
                'date' => $isoDate,
                'type' => 'event',
            ];
        }

        usort($items, static function ($left, $right) {
            $leftDate = (string)($left['date'] ?? '');
            $rightDate = (string)($right['date'] ?? '');

            if ($leftDate !== $rightDate) {
                return strcmp($leftDate, $rightDate);
            }

            return strcmp((string)($left['title'] ?? ''), (string)($right['title'] ?? ''));
        });

        return $items;
    }
// Prosthetei nea argia apo ISO date + title meta apo validation, duplicate detection,
// display-date formatting, sorting kai persisted enimerosi tou section.
    public function addHolidayFromIsoDate($isoDate, $name)
    {
        $this->lastError = '';

        $isoDate = trim((string)$isoDate);
        $name = trim((string)$name);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $isoDate)) {
            $this->lastError = 'ÎœÎ· Î­Î³ÎºÏ…ÏÎ· Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î±ÏÎ³Î¯Î±Ï‚.';
            return false;
        }

        if ($name === '') {
            $this->lastError = 'Î¤Î¿ ÏŒÎ½Î¿Î¼Î± Ï„Î·Ï‚ Î±ÏÎ³Î¯Î±Ï‚ ÎµÎ¯Î½Î±Î¹ Ï…Ï€Î¿Ï‡ÏÎµÏ‰Ï„Î¹ÎºÏŒ.';
            return false;
        }

        $formattedDate = $this->convertIsoDateToHolidayDisplayDate($isoDate);
        if ($formattedDate === null) {
            $this->lastError = 'Î”ÎµÎ½ Î®Ï„Î±Î½ Î´Ï…Î½Î±Ï„Î® Î· Î¼Î¿ÏÏ†Î¿Ï€Î¿Î¯Î·ÏƒÎ· Ï„Î·Ï‚ Î·Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î±Ï‚ Î±ÏÎ³Î¯Î±Ï‚.';
            return false;
        }

        $section = $this->getSection('holidays');
        if (!is_array($section)) {
            $this->lastError = 'Î¤Î¿ section Ï„Ï‰Î½ Î±ÏÎ³Î¹ÏŽÎ½ Î´ÎµÎ½ Î²ÏÎ­Î¸Î·ÎºÎµ.';
            return false;
        }

        $rows = $section['content']['rows'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        foreach ($rows as $row) {
            $existingDate = trim((string)($row['date'] ?? ''));
            $existingName = trim((string)($row['name'] ?? ''));

            if ($existingDate === $formattedDate && mb_strtolower($existingName) === mb_strtolower($name)) {
                return true;
            }
        }

        $rows[] = [
            'date' => $formattedDate,
            'name' => $name,
        ];

        $rows = $this->sortHolidayRows($rows);

        return $this->updateSection(
            'holidays',
            (string)($section['title'] ?? $this->defaultSections['holidays']['title']),
            (string)($section['subtitle'] ?? $this->defaultSections['holidays']['subtitle']),
            ['rows' => $rows]
        );
    }
// Taksinomei ta holiday grammes me vasi computed date key kai deuteron me lowercase onoma argias.
    private function sortHolidayRows(array $rows)
    {
        usort($rows, function ($left, $right) {
            $leftKey = $this->buildHolidaySortKey((string)($left['date'] ?? ''));
            $rightKey = $this->buildHolidaySortKey((string)($right['date'] ?? ''));

            if ($leftKey === $rightKey) {
                return strcmp(
                    mb_strtolower(trim((string)($left['name'] ?? ''))),
                    mb_strtolower(trim((string)($right['name'] ?? '')))
                );
            }

            return strcmp($leftKey, $rightKey);
        });

        return $rows;
    }
// Ftiaxnei sortable ISO-like key apo single-date i date-range display text.
    private function buildHolidaySortKey($dateText)
    {
        $dateText = trim((string)$dateText);
        if ($dateText === '') {
            return '9999-99-99';
        }

        if (strpos($dateText, ' - ') !== false) {
            [$startPart, $endPart] = array_pad(explode(' - ', $dateText, 2), 2, '');
            $startIso = $this->convertHolidayDisplayDateToIsoWithFallbackYear($startPart, $endPart);

            if ($startIso !== null) {
                return $startIso;
            }
        }

        $singleIso = $this->convertHolidayDisplayDateToIso($dateText);
        return $singleIso ?? '9999-99-99';
    }
// Kanei parse to Greek holiday display date text kai to metatrepei se ISO yyyy-mm-dd otan einai valid.
    private function convertHolidayDisplayDateToIso($dateText)
    {
        $dateText = trim((string)$dateText);
        if ($dateText === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateText) === 1) {
            return $dateText;
        }

        if (strpos($dateText, '-') !== false) {
            return null;
        }

        $dateText = preg_replace('/\s+/u', ' ', $dateText);

        $greekMonths = [
            'Î¹Î±Î½Î¿Ï…Î±ÏÎ¯Î¿Ï…' => '01',
            'Ï†ÎµÎ²ÏÎ¿Ï…Î±ÏÎ¯Î¿Ï…' => '02',
            'Î¼Î±ÏÏ„Î¯Î¿Ï…' => '03',
            'Î±Ï€ÏÎ¹Î»Î¯Î¿Ï…' => '04',
            'Î¼Î±ÎÎ¿Ï…' => '05',
            'Î¹Î¿Ï…Î½Î¯Î¿Ï…' => '06',
            'Î¹Î¿Ï…Î»Î¯Î¿Ï…' => '07',
            'Î±Ï…Î³Î¿ÏÏƒÏ„Î¿Ï…' => '08',
            'ÏƒÎµÏ€Ï„ÎµÎ¼Î²ÏÎ¯Î¿Ï…' => '09',
            'Î¿ÎºÏ„Ï‰Î²ÏÎ¯Î¿Ï…' => '10',
            'Î½Î¿ÎµÎ¼Î²ÏÎ¯Î¿Ï…' => '11',
            'Î´ÎµÎºÎµÎ¼Î²ÏÎ¯Î¿Ï…' => '12',
        ];

        if (preg_match('/^(\d{1,2})\s*([^\d\s]+)\s*(\d{4})$/u', $dateText, $matches) !== 1) {
            return null;
        }

        $day = str_pad((string)(int)$matches[1], 2, '0', STR_PAD_LEFT);
        $monthText = mb_strtolower(trim((string)$matches[2]), 'UTF-8');
        $year = trim((string)$matches[3]);

        if (!isset($greekMonths[$monthText])) {
            return null;
        }

        if (!preg_match('/^\d{4}$/', $year)) {
            return null;
        }

        return $year . '-' . $greekMonths[$monthText] . '-' . $day;
    }
// Kanei parse to start date enos range danizomeno to year apo to end date otan leipei.
    private function convertHolidayDisplayDateToIsoWithFallbackYear($startText, $endText)
    {
        $directIso = $this->convertHolidayDisplayDateToIso((string)$startText);
        if ($directIso !== null) {
            return $directIso;
        }

        $startText = trim((string)$startText);
        $endText = trim((string)$endText);

        if ($startText === '' || $endText === '') {
            return null;
        }

        if (preg_match('/^\d{1,2}\s*\S+$/u', $startText) !== 1) {
            return null;
        }

        if (preg_match('/(\d{4})\s*$/u', $endText, $matches) !== 1) {
            return null;
        }

        return $this->convertHolidayDisplayDateToIso($startText . ' ' . $matches[1]);
    }
// Metatrepei school-year display date se ISO; dexetai direct ISO i kanei delegate se Greek parser.
    private function convertSchoolYearDisplayDateToIso($dateText)
    {
        $dateText = trim((string)$dateText);
        if ($dateText === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateText) === 1) {
            return $dateText;
        }

        return $this->convertHolidayDisplayDateToIso($dateText);
    }
// Morfopoiei ISO yyyy-mm-dd se Greek human-readable imerominia pou xrisimopoieitai sta holiday grammes.
    private function convertIsoDateToHolidayDisplayDate($isoDate)
    {
        $parts = explode('-', (string)$isoDate);
        if (count($parts) !== 3) {
            return null;
        }

        [$year, $month, $day] = $parts;
        $months = [
            '01' => 'Î™Î±Î½Î¿Ï…Î±ÏÎ¯Î¿Ï…',
            '02' => 'Î¦ÎµÎ²ÏÎ¿Ï…Î±ÏÎ¯Î¿Ï…',
            '03' => 'ÎœÎ±ÏÏ„Î¯Î¿Ï…',
            '04' => 'Î‘Ï€ÏÎ¹Î»Î¯Î¿Ï…',
            '05' => 'ÎœÎ±ÎÎ¿Ï…',
            '06' => 'Î™Î¿Ï…Î½Î¯Î¿Ï…',
            '07' => 'Î™Î¿Ï…Î»Î¯Î¿Ï…',
            '08' => 'Î‘Ï…Î³Î¿ÏÏƒÏ„Î¿Ï…',
            '09' => 'Î£ÎµÏ€Ï„ÎµÎ¼Î²ÏÎ¯Î¿Ï…',
            '10' => 'ÎŸÎºÏ„Ï‰Î²ÏÎ¯Î¿Ï…',
            '11' => 'ÎÎ¿ÎµÎ¼Î²ÏÎ¯Î¿Ï…',
            '12' => 'Î”ÎµÎºÎµÎ¼Î²ÏÎ¯Î¿Ï…',
        ];

        if (!isset($months[$month])) {
            return null;
        }

        return ((int)$day) . ' ' . $months[$month] . ' ' . $year;
    }
}
?>
