<?php
// Arxeio: app\services\ParentsPageService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
/**
 * ParentsPageService
 * Sxolio: voithitiko sxolio gia ton parakato kodika.
 * Sxolio: voithitiko sxolio gia ton parakato kodika.
 */

require_once __DIR__ . '/../config/db.php';

class ParentsPageService
{
    private $conn;
    private $defaultSections;
    private $defaultGalleryImages;
    private $lastError = '';

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct()
    {
        global $conn;
        $this->conn = $conn;
        $this->defaultSections = $this->buildDefaultSections();
        $this->defaultGalleryImages = $this->buildDefaultGalleryImages();

        $this->ensureTables();
        $this->ensureDefaultSections();
        $this->ensureDefaultGalleryImages();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getAllSections()
    {
        $sections = $this->defaultSections;
        $result = $this->conn->query('SELECT * FROM ParentsPageSections');

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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getSection($sectionKey)
    {
        $sections = $this->getAllSections();
        return $sections[$sectionKey] ?? null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
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

        $existsStmt = $this->conn->prepare('SELECT section_id FROM ParentsPageSections WHERE section_key = ? LIMIT 1');
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
            $sql = 'UPDATE ParentsPageSections
                    SET section_title = ?, section_subtitle = ?, content_json = ?
                    WHERE section_key = ?';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± prepare update: ' . $this->conn->error;
                return false;
            }

            $stmt->bind_param('ssss', $title, $subtitle, $contentJson, $sectionKey);
        } else {
            $sql = 'INSERT INTO ParentsPageSections (section_key, section_title, section_subtitle, content_json)
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getGalleryImages()
    {
        $images = [];
        $result = $this->conn->query('SELECT * FROM ParentsPageGalleryImages ORDER BY sort_order ASC, image_id ASC');

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $images[] = $row;
            }
        }

        return $images;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getGalleryImageById($imageId)
    {
        $stmt = $this->conn->prepare('SELECT * FROM ParentsPageGalleryImages WHERE image_id = ? LIMIT 1');
        if (!$stmt) {
            $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± prepare image lookup: ' . $this->conn->error;
            return null;
        }

        $stmt->bind_param('i', $imageId);
        if (!$stmt->execute()) {
            $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± execute image lookup: ' . $stmt->error;
            return null;
        }

        $result = $stmt->get_result();
        return $result ? ($result->fetch_assoc() ?: null) : null;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function addGalleryImage($fullImagePath, $thumbImagePath = '', $altText = '')
    {
        $this->lastError = '';

        $fullImagePath = $this->normalizeUtf8(trim((string)$fullImagePath));
        $thumbImagePath = $this->normalizeUtf8(trim((string)$thumbImagePath));
        $altText = $this->normalizeUtf8(trim((string)$altText));

        if ($fullImagePath === '') {
            $this->lastError = 'Î”ÎµÎ½ Î´ÏŒÎ¸Î·ÎºÎµ path ÎµÎ¹ÎºÏŒÎ½Î±Ï‚.';
            return false;
        }

        if ($thumbImagePath === '') {
            $thumbImagePath = $fullImagePath;
        }

        $sortOrder = $this->getNextGallerySortOrder();
        $stmt = $this->conn->prepare('INSERT INTO ParentsPageGalleryImages (full_image_path, thumb_image_path, alt_text, sort_order) VALUES (?, ?, ?, ?)');
        if (!$stmt) {
            $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± prepare insert image: ' . $this->conn->error;
            return false;
        }

        $stmt->bind_param('sssi', $fullImagePath, $thumbImagePath, $altText, $sortOrder);
        if (!$stmt->execute()) {
            $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± Î±Ï€Î¿Î¸Î®ÎºÎµÏ…ÏƒÎ·Ï‚ ÎµÎ¹ÎºÏŒÎ½Î±Ï‚: ' . $stmt->error;
            return false;
        }

        return true;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function deleteGalleryImage($imageId)
    {
        $this->lastError = '';

        $stmt = $this->conn->prepare('DELETE FROM ParentsPageGalleryImages WHERE image_id = ?');
        if (!$stmt) {
            $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± prepare delete image: ' . $this->conn->error;
            return false;
        }

        $stmt->bind_param('i', $imageId);
        if (!$stmt->execute()) {
            $this->lastError = 'Î£Ï†Î¬Î»Î¼Î± Î´Î¹Î±Î³ÏÎ±Ï†Î®Ï‚ ÎµÎ¹ÎºÏŒÎ½Î±Ï‚: ' . $stmt->error;
            return false;
        }

        return $stmt->affected_rows > 0;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getLastError()
    {
        return $this->lastError;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function getBoardArchiveReferenceRows(): array
    {
        return $this->buildDefaultBoardArchiveRows();
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function ensureTables()
    {
        $sectionsSql = "CREATE TABLE IF NOT EXISTS ParentsPageSections (
                            section_id INT NOT NULL AUTO_INCREMENT,
                            section_key VARCHAR(100) NOT NULL,
                            section_title VARCHAR(255) NOT NULL,
                            section_subtitle TEXT DEFAULT NULL,
                            content_json LONGTEXT DEFAULT NULL,
                            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (section_id),
                            UNIQUE KEY uq_parents_page_section_key (section_key)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $gallerySql = "CREATE TABLE IF NOT EXISTS ParentsPageGalleryImages (
                           image_id INT NOT NULL AUTO_INCREMENT,
                           full_image_path VARCHAR(255) NOT NULL,
                           thumb_image_path VARCHAR(255) DEFAULT NULL,
                           alt_text VARCHAR(255) DEFAULT NULL,
                           sort_order INT NOT NULL DEFAULT 0,
                           created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                           PRIMARY KEY (image_id)
                       ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->conn->query($sectionsSql);
        $this->conn->query($gallerySql);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function ensureDefaultSections()
    {
        foreach ($this->defaultSections as $sectionKey => $section) {
            $stmt = $this->conn->prepare('SELECT section_id FROM ParentsPageSections WHERE section_key = ? LIMIT 1');
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

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function ensureDefaultGalleryImages()
    {
        $result = $this->conn->query('SELECT COUNT(*) AS total FROM ParentsPageGalleryImages');
        $count = 0;

        if ($result) {
            $row = $result->fetch_assoc();
            $count = (int)($row['total'] ?? 0);
        }

        if ($count > 0) {
            return;
        }

        foreach ($this->defaultGalleryImages as $image) {
            $this->addGalleryImage(
                $image['full_image_path'] ?? '',
                $image['thumb_image_path'] ?? '',
                $image['alt_text'] ?? ''
            );
        }
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function getNextGallerySortOrder()
    {
        $result = $this->conn->query('SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_sort_order FROM ParentsPageGalleryImages');
        if ($result) {
            $row = $result->fetch_assoc();
            return (int)($row['next_sort_order'] ?? 1);
        }

        return 1;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function buildDefaultSections()
    {
        return [
            'page_header' => [
                'title' => 'Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î“Î¿Î½Î­Ï‰Î½',
                'subtitle' => 'Î§ÏÎ®ÏƒÎ¹Î¼ÎµÏ‚ Ï€Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚ ÎºÎ±Î¹ ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± ÎµÏ€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±Ï‚ Î³Î¹Î± Ï„Î¿Î½ Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿ Î“Î¿Î½Î­Ï‰Î½.',
                'content' => [
                    'public_eyebrow' => 'Î”Î·Î¼ÏŒÏƒÎ¹Î± Î ÏÎ»Î·',
                    'parent_eyebrow' => 'Î§ÏŽÏÎ¿Ï‚ Î“Î¿Î½Î­Î±',
                    'icon' => 'fas fa-users',
                ],
            ],
            'history_section' => [
                'title' => 'Î£ÏÎ½Ï„Î¿Î¼Î± Î³Î¹Î± Ï„Î¿ Î“Ï…Î¼Î½Î¬ÏƒÎ¹Î¿ Î‘Î³Î¯Î¿Ï… Î‘Î¸Î±Î½Î±ÏƒÎ¯Î¿Ï…',
                'subtitle' => '',
                'content' => [
                    'eyebrow' => 'Î™ÏƒÏ„Î¿ÏÎ¹ÎºÏŒ Î£Ï‡Î¿Î»ÎµÎ¯Î¿Ï…',
                    'items' => [
                        'Î¤Î¿ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿ Î¬ÏÏ‡Î¹ÏƒÎµ Ï„Î· Î»ÎµÎ¹Ï„Î¿Ï…ÏÎ³Î¯Î± Ï„Î¿Ï… Ï„Î¿Î½ Î£ÎµÏ€Ï„Î­Î¼Î²ÏÎ¹Î¿ Ï„Î¿Ï… 1999 ÎºÎ±Î¹ Î±Ï€ÏŒ Ï„Î¿ 2000-2001 Î»ÎµÎ¹Ï„Î¿Ï…ÏÎ³Î¿ÏÎ½ ÎºÎ±Î¹ Î¿Î¹ Ï„ÏÎµÎ¹Ï‚ Ï„Î¬Î¾ÎµÎ¹Ï‚.',
                        'Î¦Î­ÏÎµÎ¹ Ï„Î¿ ÏŒÎ½Î¿Î¼Î± Ï„Î¿Ï… Î‘Î³Î¯Î¿Ï… Î‘Î¸Î±Î½Î±ÏƒÎ¯Î¿Ï… ÎºÎ±Î¹ ÎµÎ¾Ï…Ï€Î·ÏÎµÏ„ÎµÎ¯ Î¼Î±Î¸Î·Ï„Î­Ï‚ Î±Ï€ÏŒ Ï„Î¿Î½ Î”Î®Î¼Î¿ ÎºÎ±Î¹ Ï€Î¿Î»Î»Î­Ï‚ ÎºÎ¿Î¹Î½ÏŒÏ„Î·Ï„ÎµÏ‚ Ï„Î·Ï‚ ÎµÏ…ÏÏÏ„ÎµÏÎ·Ï‚ Ï€ÎµÏÎ¹Î¿Ï‡Î®Ï‚ Î›ÎµÎ¼ÎµÏƒÎ¿Ï.',
                        'Î¦Î¿Î¹Ï„Î¿ÏÎ½ ÎµÏ€Î¯ÏƒÎ·Ï‚ Î¼Î±Î¸Î·Ï„Î­Ï‚ Î±Ï€ÏŒ Ï€Î¿Î»Î»Î­Ï‚ Ï‡ÏŽÏÎµÏ‚, ÎµÎ½ÏŽ Î±Ï€ÏŒ Ï„Î· ÏƒÏ‡Î¿Î»Î¹ÎºÎ® Ï‡ÏÎ¿Î½Î¹Î¬ 2024-2025 Î»ÎµÎ¹Ï„Î¿Ï…ÏÎ³ÎµÎ¯ ÎºÎ±Î¹ Ï„Î¼Î®Î¼Î± Î¼Î±Î¸Î·Ï„ÏŽÎ½ Î¼Îµ Î¼ÎµÏ„Î±Î½Î±ÏƒÏ„ÎµÏ…Ï„Î¹ÎºÎ® Î²Î¹Î¿Î³ÏÎ±Ï†Î¯Î±.',
                        'Î”Î¹Î±Î¸Î­Ï„ÎµÎ¹ Ï€Î»Î®ÏÎµÎ¹Ï‚ ÎºÏ„Î·ÏÎ¹Î±ÎºÎ­Ï‚ ÎºÎ±Î¹ ÎµÏÎ³Î±ÏƒÏ„Î·ÏÎ¹Î±ÎºÎ­Ï‚ ÎµÎ³ÎºÎ±Ï„Î±ÏƒÏ„Î¬ÏƒÎµÎ¹Ï‚ (ÎµÏÎ³Î±ÏƒÏ„Î®ÏÎ¹Î±, Î²Î¹Î²Î»Î¹Î¿Î¸Î®ÎºÎ·, Î±Î¯Î¸Î¿Ï…ÏƒÎµÏ‚ ÎµÎ¹Î´Î¹ÎºÎ¿Ï„Î®Ï„Ï‰Î½ ÎºÎ±Î¹ Î±Î¸Î»Î·Ï„Î¹ÎºÎ¿ÏÏ‚ Ï‡ÏŽÏÎ¿Ï…Ï‚).',
                        'ÎŸÎ¹ ÎµÎºÏ€Î±Î¹Î´ÎµÏ…Ï„Î¹ÎºÎ¿Î¯ Ï…Î»Î¿Ï€Î¿Î¹Î¿ÏÎ½ Î´ÏÎ¬ÏƒÎµÎ¹Ï‚ ÎºÎ±Î¹ Ï€ÏÎ¿Î³ÏÎ¬Î¼Î¼Î±Ï„Î± (ÏŒÏ€Ï‰Ï‚ Erasmus+) Î¼Îµ ÏƒÏ„ÏŒÏ‡Î¿ Ï„Î·Î½ ÎºÎ±Î»Î»Î¹Î­ÏÎ³ÎµÎ¹Î± Î´Î·Î¼Î¿ÎºÏÎ±Ï„Î¹ÎºÎ®Ï‚ ÎºÎ±Î¹ ÎºÏÎ¹Ï„Î¹ÎºÎ®Ï‚ ÏƒÎºÎ­ÏˆÎ·Ï‚.',
                    ],
                ],
            ],
            'association_section' => [
                'title' => 'Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î“Î¿Î½Î­Ï‰Î½',
                'subtitle' => 'Î— ÎµÎ½ÏŒÏ„Î·Ï„Î± Î±Ï…Ï„Î® ÏƒÏ…Î³ÎºÎµÎ½Ï„ÏÏŽÎ½ÎµÎ¹ Ï„Î¿Î½ Ï‡Î±Î¹ÏÎµÏ„Î¹ÏƒÎ¼ÏŒ, Ï„Î¿Î½ ÏƒÎºÎ¿Ï€ÏŒ ÎºÎ±Î¹ Î²Î±ÏƒÎ¹ÎºÎ¬ ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± Î³Î¹Î± Ï„Î· Î´ÏÎ¬ÏƒÎ· Ï„Î¿Ï… Î£Ï…Î½Î´Î­ÏƒÎ¼Î¿Ï… Î“Î¿Î½Î­Ï‰Î½.',
                'content' => [
                    'eyebrow' => 'Î£Ï…Î½ÎµÏÎ³Î±ÏƒÎ¯Î± ÎŸÎ¹ÎºÎ¿Î³Î­Î½ÎµÎ¹Î±Ï‚ ÎšÎ±Î¹ Î£Ï‡Î¿Î»ÎµÎ¯Î¿Ï…',
                    'greeting_title' => 'Î§Î±Î¹ÏÎµÏ„Î¹ÏƒÎ¼ÏŒÏ‚',
                    'greeting_body' => "ÎŸ Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î“Î¿Î½Î­Ï‰Î½ ÎºÎ±Î¹ ÎšÎ·Î´ÎµÎ¼ÏŒÎ½Ï‰Î½ ÎºÎ±Î»Ï‰ÏƒÎ¿ÏÎ¯Î¶ÎµÎ¹ Ï„Î¹Ï‚ Î¿Î¹ÎºÎ¿Î³Î­Î½ÎµÎ¹ÎµÏ‚ Ï„Î·Ï‚ ÏƒÏ‡Î¿Î»Î¹ÎºÎ®Ï‚ ÎºÎ¿Î¹Î½ÏŒÏ„Î·Ï„Î±Ï‚ Ï„Î¿Ï… Î“Ï…Î¼Î½Î±ÏƒÎ¯Î¿Ï… Î‘Î³Î¯Î¿Ï… Î‘Î¸Î±Î½Î±ÏƒÎ¯Î¿Ï….\nÎ£Ï„ÏŒÏ‡Î¿Ï‚ Î¼Î±Ï‚ ÎµÎ¯Î½Î±Î¹ Î· ÏƒÏ„ÎµÎ½Î® ÏƒÏ…Î½ÎµÏÎ³Î±ÏƒÎ¯Î± Î¼Îµ Ï„Î· Î”Î¹ÎµÏÎ¸Ï…Î½ÏƒÎ·, Ï„Î¿ Ï€ÏÎ¿ÏƒÏ‰Ï€Î¹ÎºÏŒ ÎºÎ±Î¹ Ï„Î¿Ï…Ï‚ Î³Î¿Î½ÎµÎ¯Ï‚, ÏŽÏƒÏ„Îµ Î½Î± ÏƒÏ„Î·ÏÎ¯Î¶Î¿Î½Ï„Î±Î¹ Î­Î¼Ï€ÏÎ±ÎºÏ„Î± Î¿Î¹ Î¼Î±Î¸Î·Ï„Î­Ï‚ ÎºÎ±Î¹ Î¿Î¹ Î´ÏÎ¬ÏƒÎµÎ¹Ï‚ Ï„Î¿Ï… ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï….",
                    'purpose_title' => 'Î£ÎºÎ¿Ï€ÏŒÏ‚ Ï„Î¿Ï… Î£.Î“.',
                    'purpose_body' => "ÎŸ Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î»ÎµÎ¹Ï„Î¿Ï…ÏÎ³ÎµÎ¯ Ï…Ï€Î¿ÏƒÏ„Î·ÏÎ¹ÎºÏ„Î¹ÎºÎ¬ Ï€ÏÎ¿Ï‚ Ï„Î¿ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿ ÎºÎ±Î¹ ÎµÏ€Î¹Î´Î¹ÏŽÎºÎµÎ¹ Ï„Î·Î½ ÎµÎ½Î¯ÏƒÏ‡Ï…ÏƒÎ· Ï„Î·Ï‚ ÎµÏ€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±Ï‚ Î±Î½Î¬Î¼ÎµÏƒÎ± ÏƒÏ„Î¿Ï…Ï‚ Î³Î¿Î½ÎµÎ¯Ï‚, Ï„Î· ÏƒÏ‡Î¿Î»Î¹ÎºÎ® Î¼Î¿Î½Î¬Î´Î± ÎºÎ±Î¹ Ï„Î¿Ï…Ï‚ Î¼Î±Î¸Î·Ï„Î­Ï‚.\nÎœÎ­ÏƒÎ± Î±Ï€ÏŒ Î´ÏÎ¬ÏƒÎµÎ¹Ï‚, ÎµÎ½Î·Î¼ÎµÏÏŽÏƒÎµÎ¹Ï‚ ÎºÎ±Î¹ Î¿ÏÎ³Î±Î½Ï‰Î¼Î­Î½Î· ÏƒÏ…Î¼Î¼ÎµÏ„Î¿Ï‡Î® ÏƒÏ…Î¼Î²Î¬Î»Î»ÎµÎ¹ ÏƒÏ„Î· Î²ÎµÎ»Ï„Î¯Ï‰ÏƒÎ· Ï„Î·Ï‚ ÏƒÏ‡Î¿Î»Î¹ÎºÎ®Ï‚ Î¶Ï‰Î®Ï‚ ÎºÎ±Î¹ ÏƒÏ„Î·Î½ Ï€ÏÎ¿ÏŽÎ¸Î·ÏƒÎ· Ï€ÏÏ‰Ï„Î¿Î²Î¿Ï…Î»Î¹ÏŽÎ½ Ï€Î¿Ï… Ï‰Ï†ÎµÎ»Î¿ÏÎ½ Ï„Î± Ï€Î±Î¹Î´Î¹Î¬.",
                    'history_title' => 'Î™ÏƒÏ„Î¿ÏÎ¹ÎºÏŒ Ï„Î¿Ï… Î£Ï…Î½Î´Î­ÏƒÎ¼Î¿Ï…',
                    'history_body' => "ÎŸ Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î“Î¿Î½Î­Ï‰Î½ ÎºÎ±Î¹ ÎšÎ·Î´ÎµÎ¼ÏŒÎ½Ï‰Î½ Î´ÏÎ± Î´Î¹Î±Ï‡ÏÎ¿Î½Î¹ÎºÎ¬ Ï‰Ï‚ Î²Î±ÏƒÎ¹ÎºÏŒÏ‚ Ï€Ï…Î»ÏŽÎ½Î±Ï‚ ÏƒÏ…Î½ÎµÏÎ³Î±ÏƒÎ¯Î±Ï‚ Î±Î½Î¬Î¼ÎµÏƒÎ± ÏƒÏ„Î·Î½ Î¿Î¹ÎºÎ¿Î³Î­Î½ÎµÎ¹Î± ÎºÎ±Î¹ Ï„Î¿ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿.\nÎœÎµ Ï„Î·Î½ ÎµÏ„Î®ÏƒÎ¹Î± ÏƒÏ…Î¼Î¼ÎµÏ„Î¿Ï‡Î® Ï„Ï‰Î½ Î³Î¿Î½Î­Ï‰Î½ ÎºÎ±Î¹ Ï„Î· ÏƒÏ„Î®ÏÎ¹Î¾Î· Ï„Ï‰Î½ Î¼ÎµÎ»ÏŽÎ½ Ï„Î¿Ï…, ÎµÎ½Î¹ÏƒÏ‡ÏÎµÎ¹ Î´ÏÎ¬ÏƒÎµÎ¹Ï‚, ÎµÎºÎ´Î·Î»ÏŽÏƒÎµÎ¹Ï‚ ÎºÎ±Î¹ Î±Î½Î¬Î³ÎºÎµÏ‚ Ï„Î·Ï‚ ÏƒÏ‡Î¿Î»Î¹ÎºÎ®Ï‚ ÎºÎ¿Î¹Î½ÏŒÏ„Î·Ï„Î±Ï‚, Î´Î¹Î±Ï„Î·ÏÏŽÎ½Ï„Î±Ï‚ ÎµÎ½ÎµÏÎ³ÏŒ ÏÏŒÎ»Î¿ ÏƒÏ„Î·Î½ ÎºÎ±Î¸Î·Î¼ÎµÏÎ¹Î½ÏŒÏ„Î·Ï„Î± Ï„Î¿Ï… ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï….",
                    'contact_label' => 'Email Î£Ï…Î½Î´Î­ÏƒÎ¼Î¿Ï…',
                    'contact_value' => 'sg.ag.athanasiou@gmail.com',
                ],
            ],
            'attendance_portal_section' => [
                'title' => 'Î ÏÎ»Î· Î‘Ï€Î¿Ï…ÏƒÎ¹Î¿Î»Î¿Î³Î¯Î¿Ï…',
                'subtitle' => 'Î— Ï€ÏÎ»Î· Î±Ï€Î¿Ï…ÏƒÎ¹Î¿Î»Î¿Î³Î¯Î¿Ï… Ï€ÏÎ¿ÏƒÏ†Î­ÏÎµÎ¹ Î¬Î¼ÎµÏƒÎ· Ï€ÏÏŒÏƒÎ²Î±ÏƒÎ· ÏƒÏ„Î·Î½ Î·Î»ÎµÎºÏ„ÏÎ¿Î½Î¹ÎºÎ® ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ· Î³Î¹Î± Ï„Î¹Ï‚ Î±Ï€Î¿Ï…ÏƒÎ¯ÎµÏ‚ Ï„Ï‰Î½ Î¼Î±Î¸Î·Ï„ÏŽÎ½ ÎºÎ±Î¹ ÏƒÎµ ÏƒÏ‡ÎµÏ„Î¹ÎºÎ­Ï‚ Ï€Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚ Ï†Î¿Î¯Ï„Î·ÏƒÎ·Ï‚.',
                'content' => [
                    'eyebrow' => 'Î—Î»ÎµÎºÏ„ÏÎ¿Î½Î¹ÎºÎ® Î•Î½Î·Î¼Î­ÏÏ‰ÏƒÎ·',
                    'link_label' => 'ÎœÎµÏ„Î¬Î²Î±ÏƒÎ· ÏƒÏ„Î·Î½ Î ÏÎ»Î·',
                    'link_url' => 'http://www.gym-ag-athanasios-lem.eschoolsupport.com/',
                ],
            ],
            'schedule_section' => [
                'title' => 'Î•ÏƒÏ‰Ï„ÎµÏÎ¹ÎºÎ¿Î¯ ÎšÎ±Î½Î¿Î½Î¹ÏƒÎ¼Î¿Î¯ - Î©ÏÎ¬ÏÎ¹Î¿',
                'subtitle' => '',
                'content' => [
                    'eyebrow' => 'Î£Ï‡Î¿Î»Î¹ÎºÎ® Î§ÏÎ¿Î½Î¹Î¬ 2025 - 2026',
                    'period_label' => 'Î ÎµÏÎ¯Î¿Î´Î¿Ï‚',
                    'time_label' => 'ÎÏÎ±',
                    'blocks' => [
                        [
                            'title' => 'Î”ÎµÏ…Ï„Î­ÏÎ± - Î¤ÏÎ¯Ï„Î· - Î Î­Î¼Ï€Ï„Î· (8Ï‰ÏÎ¿)',
                            'rows' => [
                                ['period' => '1Î·', 'time' => '07:30 - 08:10'],
                                ['period' => '2Î·', 'time' => '08:10 - 08:50'],
                                ['period' => 'Î”Î¹Î¬Î»ÎµÎ¹Î¼Î¼Î±', 'time' => '08:50 - 09:10'],
                                ['period' => '3Î·', 'time' => '09:10 - 09:50'],
                                ['period' => '4Î·', 'time' => '09:50 - 10:30'],
                                ['period' => 'Î”Î¹Î¬Î»ÎµÎ¹Î¼Î¼Î±', 'time' => '10:30 - 10:45'],
                                ['period' => '5Î·', 'time' => '10:45 - 11:25'],
                                ['period' => '6Î·', 'time' => '11:25 - 12:05'],
                                ['period' => 'Î”Î¹Î¬Î»ÎµÎ¹Î¼Î¼Î±', 'time' => '12:05 - 12:15'],
                                ['period' => '7Î·', 'time' => '12:15 - 12:55'],
                                ['period' => '8Î·', 'time' => '12:55 - 13:35'],
                            ],
                        ],
                        [
                            'title' => 'Î¤ÎµÏ„Î¬ÏÏ„Î· - Î Î±ÏÎ±ÏƒÎºÎµÏ…Î® (7Ï‰ÏÎ¿)',
                            'rows' => [
                                ['period' => '1Î·', 'time' => '07:30 - 08:15'],
                                ['period' => '2Î·', 'time' => '08:15 - 09:00'],
                                ['period' => 'Î”Î¹Î¬Î»ÎµÎ¹Î¼Î¼Î±', 'time' => '09:00 - 09:20'],
                                ['period' => '3Î·', 'time' => '09:20 - 10:05'],
                                ['period' => '4Î·', 'time' => '10:05 - 10:50'],
                                ['period' => 'Î”Î¹Î¬Î»ÎµÎ¹Î¼Î¼Î±', 'time' => '10:50 - 11:10'],
                                ['period' => '5Î·', 'time' => '11:10 - 11:55'],
                                ['period' => '6Î·', 'time' => '11:55 - 12:40'],
                                ['period' => 'Î”Î¹Î¬Î»ÎµÎ¹Î¼Î¼Î±', 'time' => '12:40 - 12:50'],
                                ['period' => '7Î·', 'time' => '12:50 - 13:35'],
                            ],
                        ],
                    ],
                ],
            ],
            'board_section' => [
                'title' => 'Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿Ï‚ Î“Î¿Î½Î­Ï‰Î½',
                'subtitle' => 'Î£Ï„Î·Î½ ÎµÎ½ÏŒÏ„Î·Ï„Î± Î±Ï…Ï„Î® Î¸Î± Î²ÏÎµÎ¯Ï„Îµ Ï„Î· ÏƒÏÎ½Î¸ÎµÏƒÎ· Ï„Î¿Ï… Î”Î¹Î¿Î¹ÎºÎ·Ï„Î¹ÎºÎ¿Ï Î£Ï…Î¼Î²Î¿Ï…Î»Î¯Î¿Ï… Ï„Î¿Ï… Î£Ï…Î½Î´Î­ÏƒÎ¼Î¿Ï… Î“Î¿Î½Î­Ï‰Î½, Î²Î±ÏƒÎ¹ÎºÎ¬ ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± ÎµÏ€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±Ï‚ ÎºÎ±Î¹ Ï‡ÏÎ®ÏƒÎ¹Î¼Î¿Ï…Ï‚ ÏƒÏ…Î½Î´Î­ÏƒÎ¼Î¿Ï…Ï‚ Î³Î¹Î± Î¬Î¼ÎµÏƒÎ· ÎµÎ½Î·Î¼Î­ÏÏ‰ÏƒÎ·.',
                'content' => [
                    'eyebrow' => 'Î£Ï‡Î¿Î»Î¹ÎºÎ® Î§ÏÎ¿Î½Î¹Î¬ 2025 - 2026',
                    'current_board_label' => 'Î¤ÏÎ­Ï‡Î¿Î½ Î”Î¹Î¿Î¹ÎºÎ·Ï„Î¹ÎºÏŒ Î£Ï…Î¼Î²Î¿ÏÎ»Î¹Î¿',
                    'position_label' => 'Î˜Î­ÏƒÎ·',
                    'name_label' => 'ÎŸÎ½Î¿Î¼Î±Ï„ÎµÏ€ÏŽÎ½Ï…Î¼Î¿',
                    'committee_label' => 'ÎœÎ­Î»Î·',
                    'contact_email_label' => 'Email',
                    'contact_email_value' => 'sg.ag.athanasiou@gmail.com',
                    'board_members' => [
                        ['role' => 'Î Î¡ÎŸÎ•Î”Î¡ÎŸÎ£', 'name' => 'ÎœÎ¹Ï‡Î¬Î»Î·Ï‚ Î‘ÏÎ¹ÏƒÏ„ÎµÎ¯Î´Î¿Ï…'],
                        ['role' => 'Î‘ÎÎ¤Î™Î Î¡ÎŸÎ•Î”Î¡ÎŸÎ£', 'name' => 'ÎœÎ¬ÏÎ¹Î¿Ï‚ Î“Î±Î²ÏÎ¹Î·Î»Î¯Î´Î·Ï‚'],
                        ['role' => 'Î“Î¡Î‘ÎœÎœÎ‘Î¤Î•Î‘Î£', 'name' => 'Î’Î¬ÏƒÎ¹Î± ÎœÎ­Î¶Î¿Ï…'],
                        ['role' => 'Î’ÎŸÎ—Î˜ÎŸÎ£ Î“Î¡Î‘ÎœÎœÎ‘Î¤Î•Î‘Î£', 'name' => 'Î£Ï€Ï…ÏÎ¿ÏÎ»Î± Î§Î±ÏÎ±Î»Î¬Î¼Ï€Î¿Ï…Ï‚'],
                        ['role' => 'Î¤Î‘ÎœÎ™Î‘Î£', 'name' => 'Î“Î¹Î¬Î½Î½Î± Î Î±Ï€Î±ÏŠÏ‰Î¬Î½Î½Î¿Ï…'],
                        ['role' => 'Î’ÎŸÎ—Î˜ÎŸÎ£ Î¤Î‘ÎœÎ™Î‘Î£', 'name' => 'Î‘ÏÎ¯ÏƒÏ„Î· Î˜ÎµÎ¿Î´Î¿ÏƒÎ¯Î¿Ï…'],
                    ],
                    'committee_members' => [
                        'Î§Î±ÏÎ¬ Î§ÏÎ¹ÏƒÏ„Î¿Î´Î¿ÏÎ»Î¿Ï…',
                        'Î§ÏÎ¯ÏƒÏ„Î¿Ï‚ Î‘ÏÎ¹ÏƒÏ„Î¿Î´Î®Î¼Î¿Ï…',
                        'Î Î­Ï„ÏÎ¿Ï‚ ÎšÎ¿Î½Ï„Î¿Î³Î¹Î¬Î½Î½Î·Ï‚',
                    ],
                ],
            ],
            'board_archive_section' => [
                'title' => 'Î£Ï…Î¼Î²Î¿ÏÎ»Î¹Î± Î±Î½Î¬ Î£Ï‡Î¿Î»Î¹ÎºÎ® Î§ÏÎ¿Î½Î¹Î¬',
                'subtitle' => 'Î‘ÏÏ‡ÎµÎ¯Î¿ Ï€ÏÎ¿Î·Î³Î¿ÏÎ¼ÎµÎ½Ï‰Î½ ÎºÎ±Î¹ Ï„ÏÎµÏ‡Î¿Ï…ÏƒÏŽÎ½ ÏƒÏ…Î½Î¸Î­ÏƒÎµÏ‰Î½ Ï„Î¿Ï… Î”Î¹Î¿Î¹ÎºÎ·Ï„Î¹ÎºÎ¿Ï Î£Ï…Î¼Î²Î¿Ï…Î»Î¯Î¿Ï….',
                'content' => [
                    'eyebrow' => 'Î™ÏƒÏ„Î¿ÏÎ¹ÎºÏŒ Î”Î¹Î¿Î¹ÎºÎ·Ï„Î¹ÎºÏŽÎ½ Î£Ï…Î¼Î²Î¿Ï…Î»Î¯Ï‰Î½',
                    'year_label' => 'Î£Ï‡Î¿Î»Î¹ÎºÎ® Î§ÏÎ¿Î½Î¹Î¬',
                    'position_label' => 'Î˜Î­ÏƒÎ·',
                    'name_label' => 'ÎŸÎ½Î¿Î¼Î±Ï„ÎµÏ€ÏŽÎ½Ï…Î¼Î¿',
                    'rows' => $this->buildDefaultBoardArchiveRows(),
                ],
            ],
            'class_responsibles_section' => [
                'title' => 'Î¥Ï€ÎµÏÎ¸Ï…Î½Î¿Î¹ Î¤Î¼Î·Î¼Î¬Ï„Ï‰Î½',
                'subtitle' => 'Î¥Î Î•Î¥Î˜Î¥ÎÎŸÎ™ Î¤ÎœÎ—ÎœÎ‘Î¤Î©Î ÎšÎ‘Î™ Î¥Î Î•Î¥Î˜Î¥ÎÎŸÎ™ Î’ÎŸÎ—Î˜ÎŸÎ™ Î”Î™Î•Î¥Î˜Î¥ÎÎ¤Î•Î£',
                'content' => [
                    'modal_title' => 'Î¥Î Î•Î¥Î˜Î¥ÎÎŸÎ™ Î¤ÎœÎ—ÎœÎ‘Î¤Î©Î ÎšÎ‘Î™ Î¥Î Î•Î¥Î˜Î¥ÎÎŸÎ™ Î’ÎŸÎ—Î˜ÎŸÎ™ Î”Î™Î•Î¥Î˜Î¥ÎÎ¤Î•Î£',
                    'class_label' => 'Î¤ÎœÎ—ÎœÎ‘',
                    'responsible_label' => 'Î¥Î Î•Î¥Î˜Î¥ÎÎŸÎ£ Î¤ÎœÎ—ÎœÎ‘Î¤ÎŸÎ£',
                    'assistant_label' => 'Î¥Î Î•Î¥Î˜Î¥ÎÎŸÎ£ Î’ÎŸÎ—Î˜ÎŸÎ£ Î”Î™Î•Î¥Î˜Î¥ÎÎ¤Î—Î£',
                    'room_label' => 'Î‘Î™Î˜ÎŸÎ¥Î£Î‘',
                    'rows' => [
                        ['class' => 'Î‘1', 'responsible' => 'Î‘Î›Î•ÎžÎ‘ÎÎ”Î¡ÎŸÎ£ ÎšÎŸÎ¥ÎÎ¤ÎŸÎ¥Î¡Î™Î©Î¤Î—Î£', 'assistant' => 'ÎšÎ©ÎÎ£Î¤Î‘ÎÎ¤Î™ÎÎŸÎ£ Î‘ÎÎ¤Î©ÎÎ™ÎŸÎ¥', 'room' => '107'],
                        ['class' => 'Î‘2', 'responsible' => 'Î“Î•Î©Î¡Î“Î™Î‘ Î§Î‘Î¤Î–Î—Î’Î‘Î£Î™Î›Î—', 'assistant' => 'Î’Î‘Î£Î™Î›Î™ÎšÎ— ÎšÎ‘Î Î Î‘Î—', 'room' => '103'],
                        ['class' => 'Î‘3', 'responsible' => 'Î§Î¡Î™Î£Î¤Î™ÎÎ‘ Î¡Î—Î“Î‘', 'assistant' => 'Î“Î™Î©Î¡Î“ÎŸÎ£ Î“Î•Î©Î¡Î“Î™ÎŸÎ¥', 'room' => '102'],
                        ['class' => 'Î‘4', 'responsible' => 'Î“Î•Î©Î¡Î“Î™Î‘ Î’Î‘Î¡Î£Î‘ÎœÎ—', 'assistant' => 'Î’Î‘Î£Î™Î›Î™ÎšÎ— ÎšÎ‘Î Î Î‘Î—', 'room' => '108'],
                        ['class' => 'Î‘5', 'responsible' => 'Î§Î¡Î™Î£Î¤Î™ÎÎ‘ ÎšÎ‘ÎœÎ•ÎÎŸÎ¥', 'assistant' => 'Î•Î›Î›Î— ÎœÎ•Î›Î•Î¤Î™ÎŸÎ¥', 'room' => '109'],
                        ['class' => 'Î‘6', 'responsible' => 'Î‘ÎÎ¤Î–Î•Î›Î‘ Î£ÎŸÎ¥Î‘Î', 'assistant' => 'ÎšÎ©ÎÎ£Î¤Î‘ÎÎ¤Î™ÎÎŸÎ£ Î‘ÎÎ¤Î©ÎÎ™ÎŸÎ¥', 'room' => '104'],
                        ['class' => 'Î‘7', 'responsible' => 'ÎœÎ‘Î¡Î™ÎŸÎ£ Î‘ÎÎ”Î¡Î•ÎŸÎ¥', 'assistant' => 'Î Î‘ÎÎ‘Î“Î™Î©Î¤Î—Î£ Î Î‘ÎÎ¤Î•Î›Î™Î”Î—Î£', 'room' => '110'],
                        ['class' => 'Î‘8', 'responsible' => 'Î’Î¡Î¥Î©ÎÎŸÎ¥Î›Î›Î‘ Î˜Î•ÎŸÎ¦Î¥Î›Î‘ÎšÎ¤ÎŸÎ¥', 'assistant' => 'Î Î‘ÎÎ‘Î“Î™Î©Î¤Î‘ ÎšÎ©ÎÎ£Î¤Î‘ÎÎ¤Î™ÎÎŸÎ¥ ÎšÎ‘Î¤Î–Î—', 'room' => '101'],
                        ['class' => 'Î‘9', 'responsible' => 'Î•Î›Î•ÎÎ— Î Î‘Î Î‘Î“Î•Î©Î¡Î“Î™ÎŸÎ¥', 'assistant' => 'Î”Î•Î£Î Î© Î›ÎŸÎ¦Î™Î¤ÎŸÎ¥ (Î“Î™Î©Î¡Î“ÎŸÎ£ Î“Î•Î©Î¡Î“Î™ÎŸÎ¥)', 'room' => '201'],
                        ['class' => 'Î‘Î•1', 'responsible' => 'â€”', 'assistant' => 'ÎšÎ¥Î¡Î™Î‘ÎšÎ— Î Î‘Î Î‘ÎÎ™ÎšÎŸÎ›Î‘ÎŸÎ¥ / ÎšÎ©ÎÎ£Î¤Î‘ÎÎ¤Î™ÎÎ‘ Î›ÎŸÎ¥Î¤Î£Î™ÎŸÎ¥', 'room' => 'Î‘Î™Î˜. Î¨Î¥Î§Î‘Î“Î©Î“Î™Î‘Î£'],
                        ['class' => 'Î’1', 'responsible' => 'Î§Î‘Î¡Î—Î£ Î£Î™Î‘ÎšÎ‘Î›Î›Î—Î£', 'assistant' => 'Î’Î‘Î£Î™Î›Î™ÎšÎ— ÎšÎ‘Î Î Î‘Î—', 'room' => '106'],
                        ['class' => 'Î’2', 'responsible' => 'Î‘ÎÎ¤Î¡Î— ÎœÎ—ÎÎ‘', 'assistant' => 'Î”Î•Î£Î Î© Î›ÎŸÎ¦Î™Î¤ÎŸÎ¥ (ÎšÎ¡Î™Î£Î¤Î™Î‘ Î‘Î ÎŸÎ£Î¤ÎŸÎ›Î™Î”ÎŸÎ¥ (Î‘Î½Î¬Î¸ÎµÏƒÎ·))', 'room' => '215'],
                        ['class' => 'Î’3', 'responsible' => 'ÎšÎ¡Î™Î£Î¤Î™Î‘ Î‘Î ÎŸÎ£Î¤ÎŸÎ›Î™Î”ÎŸÎ¥', 'assistant' => 'Î”Î•Î£Î Î© Î›ÎŸÎ¦Î™Î¤ÎŸÎ¥ (ÎšÎ¡Î™Î£Î¤Î™Î‘ Î‘Î ÎŸÎ£Î¤ÎŸÎ›Î™Î”ÎŸÎ¥ (Î‘Î½Î¬Î¸ÎµÏƒÎ·))', 'room' => '105'],
                        ['class' => 'Î’4', 'responsible' => 'Î Î‘ÎÎ‘Î“Î™Î©Î¤Î‘ Î’Î‘Î£Î™Î›Î•Î™ÎŸÎ¥', 'assistant' => 'Î•Î›Î›Î— ÎœÎ•Î›Î•Î¤Î™ÎŸÎ¥', 'room' => '111'],
                        ['class' => 'Î’5', 'responsible' => 'Î‘ÎÎ”Î¡Î•Î‘Î£ Î–Î•ÎÎ™ÎŸÎ¥', 'assistant' => 'Î“Î™Î©Î¡Î“ÎŸÎ£ Î“Î•Î©Î¡Î“Î™ÎŸÎ¥', 'room' => '202'],
                        ['class' => 'Î’6', 'responsible' => 'ÎœÎ‘Î¡Î™Î‘ Î™Î©Î‘ÎÎÎŸÎ¥', 'assistant' => 'Î Î‘ÎÎ‘Î“Î™Î©Î¤Î—Î£ Î Î‘ÎÎ¤Î•Î›Î™Î”Î—Î£', 'room' => 'Î‘Î™Î˜. Î’Î™ÎŸÎ›ÎŸÎ“Î™Î‘Î£'],
                        ['class' => 'Î’7', 'responsible' => 'Î—Î›Î™Î‘ÎÎ‘ Î›ÎŸÎªÎ–Î™Î”ÎŸÎ¥', 'assistant' => 'ÎšÎ©ÎÎ£Î¤Î‘ÎÎ¤Î™ÎÎŸÎ£ Î‘ÎÎ¤Î©ÎÎ™ÎŸÎ¥', 'room' => '209'],
                        ['class' => 'Î’8', 'responsible' => 'Î£Î©Î¤Î—Î¡Î™Î‘ Î›Î‘Î–Î‘Î¡Î™Î”ÎŸÎ¥', 'assistant' => 'Î Î‘ÎÎ‘Î“Î™Î©Î¤Î‘ ÎšÎ©ÎÎ£Î¤Î‘ÎÎ¤Î™ÎÎŸÎ¥ ÎšÎ‘Î¤Î–Î—', 'room' => '203'],
                        ['class' => 'Î’Î•2', 'responsible' => 'Î‘ÎÎ¤Î–Î•Î›Î™ÎÎ‘ Î Î‘Î Î‘Î“Î•Î©Î¡Î“Î™ÎŸÎ¥', 'assistant' => 'ÎšÎ©ÎÎ£Î¤Î‘ÎÎ¤Î™ÎÎ‘ Î›ÎŸÎ¥Î¤Î£Î™ÎŸÎ¥', 'room' => 'Î‘Î™Î˜. Î¨Î¥Î§Î‘Î“Î©Î“Î™Î‘Î£'],
                        ['class' => 'Î“1', 'responsible' => 'Î£Î©Î¤Î—Î¡Î™Î‘ Î˜Î•ÎœÎ™Î£Î¤ÎŸÎšÎ›Î•ÎŸÎ¥Î£', 'assistant' => 'Î“Î™Î©Î¡Î“ÎŸÎ£ Î“Î•Î©Î¡Î“Î™ÎŸÎ¥', 'room' => '210'],
                        ['class' => 'Î“2', 'responsible' => 'ÎšÎ©ÎÎ£Î¤Î‘ÎÎ¤Î™Î‘ ÎšÎšÎ™ÎœÎ—', 'assistant' => 'Î’Î‘Î£Î™Î›Î™ÎšÎ— ÎšÎ‘Î Î Î‘Î—', 'room' => '204'],
                        ['class' => 'Î“3', 'responsible' => 'Î˜Î•ÎŸÎ¦Î‘ÎÎ—Î£ ÎšÎ•ÎÎ¤Î¡Î©Î¤Î—Î£', 'assistant' => 'Î•Î›Î›Î— ÎœÎ•Î›Î•Î¤Î™ÎŸÎ¥', 'room' => '205'],
                        ['class' => 'Î“4', 'responsible' => 'Î§Î¡Î™Î£Î¤Î™Î‘ÎÎ‘ Î§Î¡Î™Î£Î¤ÎŸÎ¥', 'assistant' => 'ÎšÎ©ÎÎ£Î¤Î‘ÎÎ¤Î™ÎÎ‘ Î›ÎŸÎ¥Î¤Î£Î™ÎŸÎ¥', 'room' => '206'],
                        ['class' => 'Î“5', 'responsible' => 'ÎœÎ‘Î¡Î™Î‘ ÎŸÎ™ÎšÎŸÎÎŸÎœÎŸÎ¥', 'assistant' => 'Î Î‘ÎÎ‘Î“Î™Î©Î¤Î—Î£ Î Î‘ÎÎ¤Î•Î›Î™Î”Î—Î£', 'room' => '208'],
                        ['class' => 'Î“6', 'responsible' => 'Î‘Î›ÎšÎ—Î£ Î Î‘Î Î—Î£', 'assistant' => 'ÎšÎ©ÎÎ£Î¤Î‘ÎÎ¤Î™ÎÎŸÎ£ Î‘ÎÎ¤Î©ÎÎ™ÎŸÎ¥', 'room' => '212'],
                        ['class' => 'Î“7', 'responsible' => 'Î™ÎŸÎ¡Î”Î‘ÎÎ—Î£ Î™ÎŸÎ¡Î”Î‘ÎÎŸÎ¥', 'assistant' => 'Î Î‘ÎÎ‘Î“Î™Î©Î¤Î‘ ÎšÎ©ÎÎ£Î¤Î‘ÎÎ¤Î™ÎÎŸÎ¥ ÎšÎ‘Î¤Î–Î—', 'room' => '216'],
                    ],
                ],
            ],
            'electronic_admin_section' => [
                'title' => 'Î—Î»ÎµÎºÏ„ÏÎ¿Î½Î¹ÎºÎ® Î”Î¹Î¿Î¯ÎºÎ·ÏƒÎ·',
                'subtitle' => 'Î—Î›Î•ÎšÎ¤Î¡ÎŸÎÎ™ÎšÎ— Î”Î™ÎŸÎ™ÎšÎ—Î£Î—',
                'content' => [
                    'modal_title' => 'Î—Î›Î•ÎšÎ¤Î¡ÎŸÎÎ™ÎšÎ— Î”Î™ÎŸÎ™ÎšÎ—Î£Î—',
                    'registration_heading' => 'ÎŸÎ´Î·Î³Î¯ÎµÏ‚ Î³Î¹Î± ÎµÎ³Î³ÏÎ±Ï†Î® ÏƒÏ„Î¿ Î£ÏÏƒÏ„Î·Î¼Î± Î—Î»ÎµÎºÏ„ÏÎ¿Î½Î¹ÎºÎ®Ï‚ Î”Î¹Î¿Î¯ÎºÎ·ÏƒÎ·Ï‚',
                    'registration_intro' => 'Î‘Î½ ÎµÏ€Î¹Î¸Ï…Î¼ÎµÎ¯Ï„Îµ Î½Î± ÎµÎ³Î³ÏÎ±Ï†ÎµÎ¯Ï„Îµ ÏƒÏ„Î¿ Î£ÏÏƒÏ„Î·Î¼Î± Î—Î»ÎµÎºÏ„ÏÎ¿Î½Î¹ÎºÎ®Ï‚ Î”Î¹Î¿Î¯ÎºÎ·ÏƒÎ·Ï‚ Î³Î¹Î± Ï€Î±ÏÎ±ÎºÎ¿Î»Î¿ÏÎ¸Î·ÏƒÎ· Ï„Î¿Ï… Ï€ÏÎ¿Î³ÏÎ¬Î¼Î¼Î±Ï„Î¿Ï‚ Î´Î¹Î±Î³Ï‰Î½Î¹ÏƒÎ¼Î¬Ï„Ï‰Î½ Ï„Ï‰Î½ Ï€Î±Î¹Î´Î¹ÏŽÎ½ ÏƒÎ±Ï‚, Î±ÎºÎ¿Î»Î¿Ï…Î¸Î®ÏƒÏ„Îµ Ï„Î± Ï€Î¹Î¿ ÎºÎ¬Ï„Ï‰ Î²Î®Î¼Î±Ï„Î±:',
                    'registration_steps' => [
                        'Î”Î·Î»ÏŽÎ½ÎµÏ„Îµ ÎµÎ½Î´Î¹Î±Ï†Î­ÏÎ¿Î½ ÏƒÏ„Î¿ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿ Î³Î¹Î± Î½Î± ÏƒÎ±Ï‚ Î´Î·Î¼Î¹Î¿Ï…ÏÎ³Î·Î¸ÎµÎ¯ ÎºÏ‰Î´Î¹ÎºÏŒÏ‚ ÎµÏ€Î±Î»Î®Î¸ÎµÏ…ÏƒÎ·Ï‚, Î¿ Î¿Ï€Î¿Î¯Î¿Ï‚ Î¸Î± ÏƒÎ±Ï‚ Î±Ï€Î¿ÏƒÏ„Î±Î»ÎµÎ¯ Î¼Îµ SMS Î® Î¸Î± Î´Î¿Î¸ÎµÎ¯ ÎµÎºÏ„Ï…Ï€Ï‰Î¼Î­Î½Î¿Ï‚ ÏƒÏ„Î¿ Ï€Î±Î¹Î´Î¯ ÏƒÎ±Ï‚.',
                        'Î‘Ï†Î¿Ï Î»Î¬Î²ÎµÏ„Îµ Ï„Î¿Î½ ÎºÏ‰Î´Î¹ÎºÏŒ ÎµÏ€Î±Î»Î®Î¸ÎµÏ…ÏƒÎ·Ï‚, ÎµÏ€Î¹ÏƒÎºÎµÏ†Î¸ÎµÎ¯Ï„Îµ Ï„Î·Î½ Î¹ÏƒÏ„Î¿ÏƒÎµÎ»Î¯Î´Î± www.eschoolsupport.com.',
                        'Î£Ï…Î¼Ï€Î»Î·ÏÏŽÎ½ÎµÏ„Îµ Ï„Î± ÏƒÏ„Î¿Î¹Ï‡ÎµÎ¯Î± Ï€Î¿Ï… Î¸Î± ÏƒÎ±Ï‚ Î¶Î·Ï„Î·Î¸Î¿ÏÎ½ ÎºÎ±Î¹ ÏƒÏ„Î¿ Ï„Î­Î»Î¿Ï‚ ÎµÎ¹ÏƒÎ¬Î³ÎµÏ„Îµ Ï„Î¿Î½ ÎºÏ‰Î´Î¹ÎºÏŒ ÎµÏ€Î±Î»Î®Î¸ÎµÏ…ÏƒÎ·Ï‚ Ï€Î¿Ï… ÏƒÎ±Ï‚ Î´ÏŒÎ¸Î·ÎºÎµ Î±Ï€ÏŒ Ï„Î¿ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿.',
                        'Î£Ï„Î¿ email Ï€Î¿Ï… Î´Î·Î»ÏŽÏƒÎ±Ï„Îµ Î¸Î± Î±Ï€Î¿ÏƒÏ„Î±Î»ÎµÎ¯ Î¼Î®Î½Ï…Î¼Î± ÎºÎ±Î¹ Î¸Î± Ï€ÏÎ­Ï€ÎµÎ¹ Î½Î± ÎµÏ€Î¹Î»Î­Î¾ÎµÏ„Îµ Â«Î•Ï€Î¹Î²ÎµÎ²Î±Î¯Ï‰ÏƒÎ· emailÂ».',
                        'ÎœÎµÏ„Î¬ Ï„Î·Î½ ÎµÏ€Î¹Î²ÎµÎ²Î±Î¯Ï‰ÏƒÎ·, Î¼Ï€Î¿ÏÎµÎ¯Ï„Îµ Î½Î± Ï€ÏÎ¿Ï‡Ï‰ÏÎ®ÏƒÎµÏ„Îµ Î¼Îµ Ï„Î·Î½ ÎµÎ¯ÏƒÎ¿Î´ÏŒ ÏƒÎ±Ï‚ ÏƒÏ„Î¿ ÏƒÏÏƒÏ„Î·Î¼Î±.',
                    ],
                    'login_heading' => 'ÎŸÎ´Î·Î³Î¯ÎµÏ‚ Î³Î¹Î± Ï„Î·Î½ ÎµÎ¯ÏƒÎ¿Î´Î¿ ÏƒÏ„Î¿ Î£ÏÏƒÏ„Î·Î¼Î± Î—Î»ÎµÎºÏ„ÏÎ¿Î½Î¹ÎºÎ®Ï‚ Î”Î¹Î¿Î¯ÎºÎ·ÏƒÎ·Ï‚',
                    'login_steps' => [
                        'Î‘Î½ ÎºÎ±Ï„Î¬ Ï„Î·Î½ ÎµÎ¯ÏƒÎ¿Î´Î¿ ÎµÎ¼Ï†Î±Î½Î¯Î¶ÎµÏ„Î±Î¹ Î»Î¬Î¸Î¿Ï‚ ÏƒÏÎ½Î´ÎµÏƒÎ·Ï‚, ÎºÎ±Î¸Î±ÏÎ¯ÏƒÏ„Îµ Ï„Î¿ Î¹ÏƒÏ„Î¿ÏÎ¹ÎºÏŒ Ï„Î¿Ï… browser ÏƒÎ±Ï‚ (Clear Browser History). Î‘Ï…Ï„ÏŒ Ï‡ÏÎµÎ¹Î¬Î¶ÎµÏ„Î±Î¹ ÏƒÏ…Î½Î®Î¸Ï‰Ï‚ Î¼ÏŒÎ½Î¿ Ï„Î·Î½ Ï€ÏÏŽÏ„Î· Ï†Î¿ÏÎ¬ Ï€Î¿Ï… Î¸Î± Ï€Î±ÏÎ¿Ï…ÏƒÎ¹Î±ÏƒÏ„ÎµÎ¯ Ï„Î¿ Ï€ÏÏŒÎ²Î»Î·Î¼Î±.',
                        'Î‘Î½ Ï‡ÏÎ·ÏƒÎ¹Î¼Î¿Ï€Î¿Î¹ÎµÎ¯Ï„Îµ Microsoft Edge, ÎºÎ¬Î½Ï„Îµ Ï„Î± ÎµÎ¾Î®Ï‚:',
                        'Î‘Î½ Ï‡ÏÎ·ÏƒÎ¹Î¼Î¿Ï€Î¿Î¹ÎµÎ¯Ï„Îµ Google Chrome, ÎºÎ¬Î½Ï„Îµ Ï„Î± ÎµÎ¾Î®Ï‚:',
                    ],
                    'edge_heading' => 'Î’Î®Î¼Î±Ï„Î± Î³Î¹Î± Microsoft Edge',
                    'edge_steps' => [
                        'Î•Ï€Î¹Î»Î­Î¾Ï„Îµ ÏƒÏ„Î¿ Ï€Î¬Î½Ï‰ Î´ÎµÎ¾Î¯ Î¼Î­ÏÎ¿Ï‚ Ï„Î·Ï‚ Î¿Î¸ÏŒÎ½Î·Ï‚ Ï„Î¿ ÎµÎ¹ÎºÎ¿Î½Î¯Î´Î¹Î¿ Tools.',
                        'Î•Ï€Î¹Î»Î­Î¾Ï„Îµ Internet Options.',
                        'Î£Ï„Î¿ Browsing History ÎµÏ€Î¹Î»Î­Î¾Ï„Îµ Delete.',
                        'Î£Ï„Î¿ Ï€Î±ÏÎ¬Î¸Ï…ÏÎ¿ Ï€Î¿Ï… Î±Î½Î¿Î¯Î³ÎµÎ¹ Î²ÎµÎ²Î±Î¹Ï‰Î¸ÎµÎ¯Ï„Îµ ÏŒÏ„Î¹ ÎµÎ¯Î½Î±Î¹ ÎµÏ€Î¹Î»ÎµÎ³Î¼Î­Î½Î¿ Ï„Î¿ History ÎºÎ±Î¹ ÎµÏ€Î¹Î»Î­Î¾Ï„Îµ Delete.',
                        'ÎœÎµÏ„Î¬ ÎµÏ€Î¹ÏƒÎºÎµÏ†Î¸ÎµÎ¯Ï„Îµ Î¾Î±Î½Î¬ Ï„Î¿Î½ ÏƒÏÎ½Î´ÎµÏƒÎ¼Î¿.',
                    ],
                    'chrome_heading' => 'Î’Î®Î¼Î±Ï„Î± Î³Î¹Î± Google Chrome',
                    'chrome_steps' => [
                        'Î•Ï€Î¹Î»Î­Î¾Ï„Îµ Ï„Î¿ ÎµÎ¹ÎºÎ¿Î½Î¯Î´Î¹Î¿ Î¼Îµ Ï„Î¹Ï‚ Ï„ÏÎµÎ¹Ï‚ ÎºÎ¬Î¸ÎµÏ„ÎµÏ‚ Ï„ÎµÎ»ÎµÎ¯ÎµÏ‚.',
                        'Î•Ï€Î¹Î»Î­Î¾Ï„Îµ Settings.',
                        'Î£Ï„Î¿ ÎºÎ¬Ï„Ï‰ Î¼Î­ÏÎ¿Ï‚ ÎµÏ€Î¹Î»Î­Î¾Ï„Îµ Advanced.',
                        'Î•Ï€Î¹Î»Î­Î¾Ï„Îµ Clear Browsing data.',
                        'Î•Ï€Î¹Î»Î­Î¾Ï„Îµ All time ÎºÎ±Î¹ Î¿Î»Î¿ÎºÎ»Î·ÏÏŽÏƒÏ„Îµ Ï„Î· Î´Î¹Î±Î³ÏÎ±Ï†Î®.',
                        'ÎœÎµÏ„Î¬ ÎµÏ€Î¹ÏƒÎºÎµÏ†Î¸ÎµÎ¯Ï„Îµ Î¾Î±Î½Î¬ Ï„Î¿Î½ ÏƒÏÎ½Î´ÎµÏƒÎ¼Î¿.',
                    ],
                    'link_label' => 'ÎœÎµÏ„Î¬Î²Î±ÏƒÎ· ÏƒÏ„Î¿ Î£ÏÏƒÏ„Î·Î¼Î± Î—Î»ÎµÎºÏ„ÏÎ¿Î½Î¹ÎºÎ®Ï‚ Î”Î¹Î¿Î¯ÎºÎ·ÏƒÎ·Ï‚',
                    'link_url' => 'http://www.gym-ag-athanasios-lem.eschoolsupport.com/',
                ],
            ],
            'gallery_section' => [
                'title' => 'Î¦Ï‰Ï„Î¿Î³ÏÎ±Ï†Î¹ÎºÏŒ Î¥Î»Î¹ÎºÏŒ',
                'subtitle' => '',
                'content' => [
                    'empty_message' => 'Î”ÎµÎ½ Î­Ï‡Î¿Ï…Î½ Ï€ÏÎ¿ÏƒÏ„ÎµÎ¸ÎµÎ¯ Î±ÎºÏŒÎ¼Î· Ï†Ï‰Ï„Î¿Î³ÏÎ±Ï†Î¯ÎµÏ‚.',
                ],
            ],
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function buildDefaultGalleryImages()
    {
        return [
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/7/3.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/7/i18npic.C240x240.3.jpg',
                'alt_text' => 'Î¦Ï‰Ï„Î¿Î³ÏÎ±Ï†Î¹ÎºÏŒ Ï…Î»Î¹ÎºÏŒ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï…',
            ],
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/6/2.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/6/i18npic.C240x240.2.jpg',
                'alt_text' => 'Î¦Ï‰Ï„Î¿Î³ÏÎ±Ï†Î¹ÎºÏŒ Ï…Î»Î¹ÎºÏŒ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï…',
            ],
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/5/21.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/5/i18npic.C240x240.21.jpg',
                'alt_text' => 'Î¦Ï‰Ï„Î¿Î³ÏÎ±Ï†Î¹ÎºÏŒ Ï…Î»Î¹ÎºÏŒ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï…',
            ],
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/8.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/i18npic.C240x240.8.jpg',
                'alt_text' => 'Î¦Ï‰Ï„Î¿Î³ÏÎ±Ï†Î¹ÎºÏŒ Ï…Î»Î¹ÎºÏŒ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï…',
            ],
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/1/1.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/1/i18npic.C240x240.1.jpg',
                'alt_text' => 'Î¦Ï‰Ï„Î¿Î³ÏÎ±Ï†Î¹ÎºÏŒ Ï…Î»Î¹ÎºÏŒ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï…',
            ],
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/1/2.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/1/i18npic.C240x240.2.jpg',
                'alt_text' => 'Î¦Ï‰Ï„Î¿Î³ÏÎ±Ï†Î¹ÎºÏŒ Ï…Î»Î¹ÎºÏŒ ÏƒÏ‡Î¿Î»ÎµÎ¯Î¿Ï…',
            ],
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function buildDefaultBoardArchiveRows(): array
    {
        return [
            ['year' => '2025-2026', 'role' => 'Î Î¡ÎŸÎ•Î”Î¡ÎŸÎ£', 'name' => 'ÎœÎ¹Ï‡Î¬Î»Î·Ï‚ Î‘ÏÎ¹ÏƒÏ„ÎµÎ¯Î´Î¿Ï…'],
            ['year' => '2025-2026', 'role' => 'Î‘ÎÎ¤Î™Î Î¡ÎŸÎ•Î”Î¡ÎŸÎ£', 'name' => 'ÎœÎ¬ÏÎ¹Î¿Ï‚ Î“Î±Î²ÏÎ¹Î·Î»Î¯Î´Î·Ï‚'],
            ['year' => '2025-2026', 'role' => 'Î“Î¡Î‘ÎœÎœÎ‘Î¤Î•Î‘Î£', 'name' => 'Î’Î¬ÏƒÎ¹Î± ÎœÎ­Î¶Î¿Ï…'],
            ['year' => '2025-2026', 'role' => 'Î’ÎŸÎ—Î˜ÎŸÎ£ Î“Î¡Î‘ÎœÎœÎ‘Î¤Î•Î‘Î£', 'name' => 'Î£Ï€Ï…ÏÎ¿ÏÎ»Î± Î§Î±ÏÎ±Î»Î¬Î¼Ï€Î¿Ï…Ï‚'],
            ['year' => '2025-2026', 'role' => 'Î¤Î‘ÎœÎ™Î‘Î£', 'name' => 'Î“Î¹Î¬Î½Î½Î± Î Î±Ï€Î±ÏŠÏ‰Î¬Î½Î½Î¿Ï…'],
            ['year' => '2025-2026', 'role' => 'Î’ÎŸÎ—Î˜ÎŸÎ£ Î¤Î‘ÎœÎ™Î‘Î£', 'name' => 'Î‘ÏÎ¯ÏƒÏ„Î· Î˜ÎµÎ¿Î´Î¿ÏƒÎ¯Î¿Ï…'],
            ['year' => '2025-2026', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'Î§Î±ÏÎ¬ Î§ÏÎ¹ÏƒÏ„Î¿Î´Î¿ÏÎ»Î¿Ï…'],
            ['year' => '2025-2026', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'Î§ÏÎ¯ÏƒÏ„Î¿Ï‚ Î‘ÏÎ¹ÏƒÏ„Î¿Î´Î®Î¼Î¿Ï…'],
            ['year' => '2025-2026', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'Î Î­Ï„ÏÎ¿Ï‚ ÎšÎ¿Î½Ï„Î¿Î³Î¹Î¬Î½Î½Î·Ï‚'],

            ['year' => '2024-2025', 'role' => 'Î Î¡ÎŸÎ•Î”Î¡ÎŸÎ£', 'name' => 'Î‘Î½Î´ÏÎ­Î±Ï‚ ÎšÏ‰Î½ÏƒÏ„Î±Î½Ï„Î¯Î½Î¿Ï…'],
            ['year' => '2024-2025', 'role' => 'Î‘ÎÎ¤Î™Î Î¡ÎŸÎ•Î”Î¡ÎŸÎ£', 'name' => 'Î•Î»Î­Î½Î· ÎÎ¹ÎºÎ¿Î»Î¬Î¿Ï…'],
            ['year' => '2024-2025', 'role' => 'Î“Î¡Î‘ÎœÎœÎ‘Î¤Î•Î‘Î£', 'name' => 'ÎœÎ±ÏÎ¯Î± Î“ÎµÏ‰ÏÎ³Î¯Î¿Ï…'],
            ['year' => '2024-2025', 'role' => 'Î’ÎŸÎ—Î˜ÎŸÎ£ Î“Î¡Î‘ÎœÎœÎ‘Î¤Î•Î‘Î£', 'name' => 'Î§ÏÎ¯ÏƒÏ„Î¿Ï‚ Î”Î·Î¼Î·Ï„ÏÎ¯Î¿Ï…'],
            ['year' => '2024-2025', 'role' => 'Î¤Î‘ÎœÎ™Î‘Î£', 'name' => 'Î†Î½Î½Î± Î£Ï‰Ï„Î·ÏÎ¯Î¿Ï…'],
            ['year' => '2024-2025', 'role' => 'Î’ÎŸÎ—Î˜ÎŸÎ£ Î¤Î‘ÎœÎ™Î‘Î£', 'name' => 'Î Î±Î½Î±Î³Î¹ÏŽÏ„Î± ÎšÏ…ÏÎ¹Î¬ÎºÎ¿Ï…'],
            ['year' => '2024-2025', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'ÎÎµÏŒÏ†Ï…Ï„Î¿Ï‚ Î‘Î½Ï„Ï‰Î½Î¯Î¿Ï…'],
            ['year' => '2024-2025', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'Î”Î­ÏƒÏ€Î¿Î¹Î½Î± Î™Ï‰Î¬Î½Î½Î¿Ï…'],
            ['year' => '2024-2025', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'Î£Ï„Î­Î»Î»Î± Î§Î±ÏÎ±Î»Î¬Î¼Ï€Î¿Ï…Ï‚'],

            ['year' => '2023-2024', 'role' => 'Î Î¡ÎŸÎ•Î”Î¡ÎŸÎ£', 'name' => 'Î“Î¹ÏŽÏÎ³Î¿Ï‚ Î˜ÎµÎ¿Î´ÏŽÏÎ¿Ï…'],
            ['year' => '2023-2024', 'role' => 'Î‘ÎÎ¤Î™Î Î¡ÎŸÎ•Î”Î¡ÎŸÎ£', 'name' => 'ÎˆÎ»ÎµÎ½Î± Î§ÏÎ¹ÏƒÏ„Î¿Ï†Î®'],
            ['year' => '2023-2024', 'role' => 'Î“Î¡Î‘ÎœÎœÎ‘Î¤Î•Î‘Î£', 'name' => 'Î‘Î¸Î·Î½Î¬ Î Î±Ï€Î±Î´Î¿Ï€Î¿ÏÎ»Î¿Ï…'],
            ['year' => '2023-2024', 'role' => 'Î’ÎŸÎ—Î˜ÎŸÎ£ Î“Î¡Î‘ÎœÎœÎ‘Î¤Î•Î‘Î£', 'name' => 'ÎÎ¹ÎºÏŒÎ»Î±Ï‚ Î£Ï„Ï…Î»Î¹Î±Î½Î¿Ï'],
            ['year' => '2023-2024', 'role' => 'Î¤Î‘ÎœÎ™Î‘Î£', 'name' => 'Î™Ï‰Î¬Î½Î½Î± ÎœÎ¹Ï‡Î±Î®Î»'],
            ['year' => '2023-2024', 'role' => 'Î’ÎŸÎ—Î˜ÎŸÎ£ Î¤Î‘ÎœÎ™Î‘Î£', 'name' => 'ÎœÎ¬ÏÎ¹Î¿Ï‚ Î§ÏÎ¯ÏƒÏ„Î¿Ï…'],
            ['year' => '2023-2024', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'Î§ÏÏ…ÏƒÏ„Î¬Î»Î»Î± Î‘Î½Î´ÏÎ­Î¿Ï…'],
            ['year' => '2023-2024', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'Î£Î¬Î²Î²Î±Ï‚ ÎÎµÎ¿ÎºÎ»Î­Î¿Ï…Ï‚'],
            ['year' => '2023-2024', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'Î•ÏÎ· ÎœÎ±Ï„Î¸Î±Î¯Î¿Ï…'],

            ['year' => '2022-2023', 'role' => 'Î Î¡ÎŸÎ•Î”Î¡ÎŸÎ£', 'name' => 'Î Î­Ï„ÏÎ¿Ï‚ Î Î­Ï„ÏÎ¿Ï…'],
            ['year' => '2022-2023', 'role' => 'Î‘ÎÎ¤Î™Î Î¡ÎŸÎ•Î”Î¡ÎŸÎ£', 'name' => 'ÎšÎ±Ï„ÎµÏÎ¯Î½Î± Î£Î¬Î²Î²Î±'],
            ['year' => '2022-2023', 'role' => 'Î“Î¡Î‘ÎœÎœÎ‘Î¤Î•Î‘Î£', 'name' => 'ÎœÎ±ÏÎ¯Î½Î± Î—ÏÎ±ÎºÎ»Î­Î¿Ï…Ï‚'],
            ['year' => '2022-2023', 'role' => 'Î’ÎŸÎ—Î˜ÎŸÎ£ Î“Î¡Î‘ÎœÎœÎ‘Î¤Î•Î‘Î£', 'name' => 'Î‘Î»Î­Î¾Î±Î½Î´ÏÎ¿Ï‚ ÎœÎ¬ÏÎºÎ¿Ï…'],
            ['year' => '2022-2023', 'role' => 'Î¤Î‘ÎœÎ™Î‘Î£', 'name' => 'ÎˆÏ†Î· Î‘Î½Ï„Ï‰Î½Î¯Î¿Ï…'],
            ['year' => '2022-2023', 'role' => 'Î’ÎŸÎ—Î˜ÎŸÎ£ Î¤Î‘ÎœÎ™Î‘Î£', 'name' => 'ÎšÏŽÏƒÏ„Î±Ï‚ Î£Î¿Î»Ï‰Î¼Î¿Ï'],
            ['year' => '2022-2023', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'Î§ÏÎ¹ÏƒÏ„Î¯Î½Î± Î›Î¿ÎÎ¶Î¿Ï…'],
            ['year' => '2022-2023', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'Î†Î½Ï„ÏÎ· Î Î±Î½Î±Î³Î¹ÏŽÏ„Î¿Ï…'],
            ['year' => '2022-2023', 'role' => 'ÎœÎ•Î›ÎŸÎ£', 'name' => 'ÎœÎ¹Ï‡Î¬Î»Î·Ï‚ ÎšÏ…ÏÎ¹Î±ÎºÎ¯Î´Î·Ï‚'],
        ];
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function normalizeUtf8($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->normalizeUtf8($item);
            }

            return $value;
        }

        return trim((string)$value);
    }
}
