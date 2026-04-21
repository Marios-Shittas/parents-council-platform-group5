<?php
/**
 * ParentsPageService
 * Αποθηκεύει και ανακτά το περιεχόμενο της σελίδας "Γονείς"
 * μαζί με το φωτογραφικό υλικό της σελίδας.
 */

require_once __DIR__ . '/../config/db.php';

class ParentsPageService
{
    private $conn;
    private $defaultSections;
    private $defaultGalleryImages;
    private $lastError = '';

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

        $existsStmt = $this->conn->prepare('SELECT section_id FROM ParentsPageSections WHERE section_key = ? LIMIT 1');
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
            $sql = 'UPDATE ParentsPageSections
                    SET section_title = ?, section_subtitle = ?, content_json = ?
                    WHERE section_key = ?';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Σφάλμα prepare update: ' . $this->conn->error;
                return false;
            }

            $stmt->bind_param('ssss', $title, $subtitle, $contentJson, $sectionKey);
        } else {
            $sql = 'INSERT INTO ParentsPageSections (section_key, section_title, section_subtitle, content_json)
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

    public function getGalleryImageById($imageId)
    {
        $stmt = $this->conn->prepare('SELECT * FROM ParentsPageGalleryImages WHERE image_id = ? LIMIT 1');
        if (!$stmt) {
            $this->lastError = 'Σφάλμα prepare image lookup: ' . $this->conn->error;
            return null;
        }

        $stmt->bind_param('i', $imageId);
        if (!$stmt->execute()) {
            $this->lastError = 'Σφάλμα execute image lookup: ' . $stmt->error;
            return null;
        }

        $result = $stmt->get_result();
        return $result ? ($result->fetch_assoc() ?: null) : null;
    }

    public function addGalleryImage($fullImagePath, $thumbImagePath = '', $altText = '')
    {
        $this->lastError = '';

        $fullImagePath = $this->normalizeUtf8(trim((string)$fullImagePath));
        $thumbImagePath = $this->normalizeUtf8(trim((string)$thumbImagePath));
        $altText = $this->normalizeUtf8(trim((string)$altText));

        if ($fullImagePath === '') {
            $this->lastError = 'Δεν δόθηκε path εικόνας.';
            return false;
        }

        if ($thumbImagePath === '') {
            $thumbImagePath = $fullImagePath;
        }

        $sortOrder = $this->getNextGallerySortOrder();
        $stmt = $this->conn->prepare('INSERT INTO ParentsPageGalleryImages (full_image_path, thumb_image_path, alt_text, sort_order) VALUES (?, ?, ?, ?)');
        if (!$stmt) {
            $this->lastError = 'Σφάλμα prepare insert image: ' . $this->conn->error;
            return false;
        }

        $stmt->bind_param('sssi', $fullImagePath, $thumbImagePath, $altText, $sortOrder);
        if (!$stmt->execute()) {
            $this->lastError = 'Σφάλμα αποθήκευσης εικόνας: ' . $stmt->error;
            return false;
        }

        return true;
    }

    public function deleteGalleryImage($imageId)
    {
        $this->lastError = '';

        $stmt = $this->conn->prepare('DELETE FROM ParentsPageGalleryImages WHERE image_id = ?');
        if (!$stmt) {
            $this->lastError = 'Σφάλμα prepare delete image: ' . $this->conn->error;
            return false;
        }

        $stmt->bind_param('i', $imageId);
        if (!$stmt->execute()) {
            $this->lastError = 'Σφάλμα διαγραφής εικόνας: ' . $stmt->error;
            return false;
        }

        return $stmt->affected_rows > 0;
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    public function getBoardArchiveReferenceRows(): array
    {
        return $this->buildDefaultBoardArchiveRows();
    }

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

    private function getNextGallerySortOrder()
    {
        $result = $this->conn->query('SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_sort_order FROM ParentsPageGalleryImages');
        if ($result) {
            $row = $result->fetch_assoc();
            return (int)($row['next_sort_order'] ?? 1);
        }

        return 1;
    }

    private function buildDefaultSections()
    {
        return [
            'page_header' => [
                'title' => 'Σύνδεσμος Γονέων',
                'subtitle' => 'Χρήσιμες πληροφορίες και στοιχεία επικοινωνίας για τον Σύνδεσμο Γονέων.',
                'content' => [
                    'public_eyebrow' => 'Δημόσια Πύλη',
                    'parent_eyebrow' => 'Χώρος Γονέα',
                    'icon' => 'fas fa-users',
                ],
            ],
            'history_section' => [
                'title' => 'Σύντομα για το Γυμνάσιο Αγίου Αθανασίου',
                'subtitle' => '',
                'content' => [
                    'eyebrow' => 'Ιστορικό Σχολείου',
                    'items' => [
                        'Το σχολείο άρχισε τη λειτουργία του τον Σεπτέμβριο του 1999 και από το 2000-2001 λειτουργούν και οι τρεις τάξεις.',
                        'Φέρει το όνομα του Αγίου Αθανασίου και εξυπηρετεί μαθητές από τον Δήμο και πολλές κοινότητες της ευρύτερης περιοχής Λεμεσού.',
                        'Φοιτούν επίσης μαθητές από πολλές χώρες, ενώ από τη σχολική χρονιά 2024-2025 λειτουργεί και τμήμα μαθητών με μεταναστευτική βιογραφία.',
                        'Διαθέτει πλήρεις κτηριακές και εργαστηριακές εγκαταστάσεις (εργαστήρια, βιβλιοθήκη, αίθουσες ειδικοτήτων και αθλητικούς χώρους).',
                        'Οι εκπαιδευτικοί υλοποιούν δράσεις και προγράμματα (όπως Erasmus+) με στόχο την καλλιέργεια δημοκρατικής και κριτικής σκέψης.',
                    ],
                ],
            ],
            'association_section' => [
                'title' => 'Σύνδεσμος Γονέων',
                'subtitle' => 'Η ενότητα αυτή συγκεντρώνει τον χαιρετισμό, τον σκοπό και βασικά στοιχεία για τη δράση του Συνδέσμου Γονέων.',
                'content' => [
                    'eyebrow' => 'Συνεργασία Οικογένειας Και Σχολείου',
                    'greeting_title' => 'Χαιρετισμός',
                    'greeting_body' => "Ο Σύνδεσμος Γονέων και Κηδεμόνων καλωσορίζει τις οικογένειες της σχολικής κοινότητας του Γυμνασίου Αγίου Αθανασίου.\nΣτόχος μας είναι η στενή συνεργασία με τη Διεύθυνση, το προσωπικό και τους γονείς, ώστε να στηρίζονται έμπρακτα οι μαθητές και οι δράσεις του σχολείου.",
                    'purpose_title' => 'Σκοπός του Σ.Γ.',
                    'purpose_body' => "Ο Σύνδεσμος λειτουργεί υποστηρικτικά προς το σχολείο και επιδιώκει την ενίσχυση της επικοινωνίας ανάμεσα στους γονείς, τη σχολική μονάδα και τους μαθητές.\nΜέσα από δράσεις, ενημερώσεις και οργανωμένη συμμετοχή συμβάλλει στη βελτίωση της σχολικής ζωής και στην προώθηση πρωτοβουλιών που ωφελούν τα παιδιά.",
                    'history_title' => 'Ιστορικό του Συνδέσμου',
                    'history_body' => "Ο Σύνδεσμος Γονέων και Κηδεμόνων δρα διαχρονικά ως βασικός πυλώνας συνεργασίας ανάμεσα στην οικογένεια και το σχολείο.\nΜε την ετήσια συμμετοχή των γονέων και τη στήριξη των μελών του, ενισχύει δράσεις, εκδηλώσεις και ανάγκες της σχολικής κοινότητας, διατηρώντας ενεργό ρόλο στην καθημερινότητα του σχολείου.",
                    'contact_label' => 'Email Συνδέσμου',
                    'contact_value' => 'sg.ag.athanasiou@gmail.com',
                ],
            ],
            'attendance_portal_section' => [
                'title' => 'Πύλη Απουσιολογίου',
                'subtitle' => 'Η πύλη απουσιολογίου προσφέρει άμεση πρόσβαση στην ηλεκτρονική ενημέρωση για τις απουσίες των μαθητών και σε σχετικές πληροφορίες φοίτησης.',
                'content' => [
                    'eyebrow' => 'Ηλεκτρονική Ενημέρωση',
                    'link_label' => 'Μετάβαση στην Πύλη',
                    'link_url' => 'http://www.gym-ag-athanasios-lem.eschoolsupport.com/',
                ],
            ],
            'schedule_section' => [
                'title' => 'Εσωτερικοί Κανονισμοί - Ωράριο',
                'subtitle' => '',
                'content' => [
                    'eyebrow' => 'Σχολική Χρονιά 2025 - 2026',
                    'period_label' => 'Περίοδος',
                    'time_label' => 'Ώρα',
                    'blocks' => [
                        [
                            'title' => 'Δευτέρα - Τρίτη - Πέμπτη (8ωρο)',
                            'rows' => [
                                ['period' => '1η', 'time' => '07:30 - 08:10'],
                                ['period' => '2η', 'time' => '08:10 - 08:50'],
                                ['period' => 'Διάλειμμα', 'time' => '08:50 - 09:10'],
                                ['period' => '3η', 'time' => '09:10 - 09:50'],
                                ['period' => '4η', 'time' => '09:50 - 10:30'],
                                ['period' => 'Διάλειμμα', 'time' => '10:30 - 10:45'],
                                ['period' => '5η', 'time' => '10:45 - 11:25'],
                                ['period' => '6η', 'time' => '11:25 - 12:05'],
                                ['period' => 'Διάλειμμα', 'time' => '12:05 - 12:15'],
                                ['period' => '7η', 'time' => '12:15 - 12:55'],
                                ['period' => '8η', 'time' => '12:55 - 13:35'],
                            ],
                        ],
                        [
                            'title' => 'Τετάρτη - Παρασκευή (7ωρο)',
                            'rows' => [
                                ['period' => '1η', 'time' => '07:30 - 08:15'],
                                ['period' => '2η', 'time' => '08:15 - 09:00'],
                                ['period' => 'Διάλειμμα', 'time' => '09:00 - 09:20'],
                                ['period' => '3η', 'time' => '09:20 - 10:05'],
                                ['period' => '4η', 'time' => '10:05 - 10:50'],
                                ['period' => 'Διάλειμμα', 'time' => '10:50 - 11:10'],
                                ['period' => '5η', 'time' => '11:10 - 11:55'],
                                ['period' => '6η', 'time' => '11:55 - 12:40'],
                                ['period' => 'Διάλειμμα', 'time' => '12:40 - 12:50'],
                                ['period' => '7η', 'time' => '12:50 - 13:35'],
                            ],
                        ],
                    ],
                ],
            ],
            'board_section' => [
                'title' => 'Σύνδεσμος Γονέων',
                'subtitle' => 'Στην ενότητα αυτή θα βρείτε τη σύνθεση του Διοικητικού Συμβουλίου του Συνδέσμου Γονέων, βασικά στοιχεία επικοινωνίας και χρήσιμους συνδέσμους για άμεση ενημέρωση.',
                'content' => [
                    'eyebrow' => 'Σχολική Χρονιά 2025 - 2026',
                    'current_board_label' => 'Τρέχον Διοικητικό Συμβούλιο',
                    'position_label' => 'Θέση',
                    'name_label' => 'Ονοματεπώνυμο',
                    'committee_label' => 'Μέλη',
                    'contact_email_label' => 'Email',
                    'contact_email_value' => 'sg.ag.athanasiou@gmail.com',
                    'board_members' => [
                        ['role' => 'ΠΡΟΕΔΡΟΣ', 'name' => 'Μιχάλης Αριστείδου'],
                        ['role' => 'ΑΝΤΙΠΡΟΕΔΡΟΣ', 'name' => 'Μάριος Γαβριηλίδης'],
                        ['role' => 'ΓΡΑΜΜΑΤΕΑΣ', 'name' => 'Βάσια Μέζου'],
                        ['role' => 'ΒΟΗΘΟΣ ΓΡΑΜΜΑΤΕΑΣ', 'name' => 'Σπυρούλα Χαραλάμπους'],
                        ['role' => 'ΤΑΜΙΑΣ', 'name' => 'Γιάννα Παπαϊωάννου'],
                        ['role' => 'ΒΟΗΘΟΣ ΤΑΜΙΑΣ', 'name' => 'Αρίστη Θεοδοσίου'],
                    ],
                    'committee_members' => [
                        'Χαρά Χριστοδούλου',
                        'Χρίστος Αριστοδήμου',
                        'Πέτρος Κοντογιάννης',
                    ],
                ],
            ],
            'board_archive_section' => [
                'title' => 'Συμβούλια ανά Σχολική Χρονιά',
                'subtitle' => 'Αρχείο προηγούμενων και τρεχουσών συνθέσεων του Διοικητικού Συμβουλίου.',
                'content' => [
                    'eyebrow' => 'Ιστορικό Διοικητικών Συμβουλίων',
                    'year_label' => 'Σχολική Χρονιά',
                    'position_label' => 'Θέση',
                    'name_label' => 'Ονοματεπώνυμο',
                    'rows' => $this->buildDefaultBoardArchiveRows(),
                ],
            ],
            'class_responsibles_section' => [
                'title' => 'Υπεύθυνοι Τμημάτων',
                'subtitle' => 'ΥΠΕΥΘΥΝΟΙ ΤΜΗΜΑΤΩΝ ΚΑΙ ΥΠΕΥΘΥΝΟΙ ΒΟΗΘΟΙ ΔΙΕΥΘΥΝΤΕΣ',
                'content' => [
                    'modal_title' => 'ΥΠΕΥΘΥΝΟΙ ΤΜΗΜΑΤΩΝ ΚΑΙ ΥΠΕΥΘΥΝΟΙ ΒΟΗΘΟΙ ΔΙΕΥΘΥΝΤΕΣ',
                    'class_label' => 'ΤΜΗΜΑ',
                    'responsible_label' => 'ΥΠΕΥΘΥΝΟΣ ΤΜΗΜΑΤΟΣ',
                    'assistant_label' => 'ΥΠΕΥΘΥΝΟΣ ΒΟΗΘΟΣ ΔΙΕΥΘΥΝΤΗΣ',
                    'room_label' => 'ΑΙΘΟΥΣΑ',
                    'rows' => [
                        ['class' => 'Α1', 'responsible' => 'ΑΛΕΞΑΝΔΡΟΣ ΚΟΥΝΤΟΥΡΙΩΤΗΣ', 'assistant' => 'ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ', 'room' => '107'],
                        ['class' => 'Α2', 'responsible' => 'ΓΕΩΡΓΙΑ ΧΑΤΖΗΒΑΣΙΛΗ', 'assistant' => 'ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ', 'room' => '103'],
                        ['class' => 'Α3', 'responsible' => 'ΧΡΙΣΤΙΝΑ ΡΗΓΑ', 'assistant' => 'ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ', 'room' => '102'],
                        ['class' => 'Α4', 'responsible' => 'ΓΕΩΡΓΙΑ ΒΑΡΣΑΜΗ', 'assistant' => 'ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ', 'room' => '108'],
                        ['class' => 'Α5', 'responsible' => 'ΧΡΙΣΤΙΝΑ ΚΑΜΕΝΟΥ', 'assistant' => 'ΕΛΛΗ ΜΕΛΕΤΙΟΥ', 'room' => '109'],
                        ['class' => 'Α6', 'responsible' => 'ΑΝΤΖΕΛΑ ΣΟΥΑΝ', 'assistant' => 'ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ', 'room' => '104'],
                        ['class' => 'Α7', 'responsible' => 'ΜΑΡΙΟΣ ΑΝΔΡΕΟΥ', 'assistant' => 'ΠΑΝΑΓΙΩΤΗΣ ΠΑΝΤΕΛΙΔΗΣ', 'room' => '110'],
                        ['class' => 'Α8', 'responsible' => 'ΒΡΥΩΝΟΥΛΛΑ ΘΕΟΦΥΛΑΚΤΟΥ', 'assistant' => 'ΠΑΝΑΓΙΩΤΑ ΚΩΝΣΤΑΝΤΙΝΟΥ ΚΑΤΖΗ', 'room' => '101'],
                        ['class' => 'Α9', 'responsible' => 'ΕΛΕΝΗ ΠΑΠΑΓΕΩΡΓΙΟΥ', 'assistant' => 'ΔΕΣΠΩ ΛΟΦΙΤΟΥ (ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ)', 'room' => '201'],
                        ['class' => 'ΑΕ1', 'responsible' => '—', 'assistant' => 'ΚΥΡΙΑΚΗ ΠΑΠΑΝΙΚΟΛΑΟΥ / ΚΩΝΣΤΑΝΤΙΝΑ ΛΟΥΤΣΙΟΥ', 'room' => 'ΑΙΘ. ΨΥΧΑΓΩΓΙΑΣ'],
                        ['class' => 'Β1', 'responsible' => 'ΧΑΡΗΣ ΣΙΑΚΑΛΛΗΣ', 'assistant' => 'ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ', 'room' => '106'],
                        ['class' => 'Β2', 'responsible' => 'ΑΝΤΡΗ ΜΗΝΑ', 'assistant' => 'ΔΕΣΠΩ ΛΟΦΙΤΟΥ (ΚΡΙΣΤΙΑ ΑΠΟΣΤΟΛΙΔΟΥ (Ανάθεση))', 'room' => '215'],
                        ['class' => 'Β3', 'responsible' => 'ΚΡΙΣΤΙΑ ΑΠΟΣΤΟΛΙΔΟΥ', 'assistant' => 'ΔΕΣΠΩ ΛΟΦΙΤΟΥ (ΚΡΙΣΤΙΑ ΑΠΟΣΤΟΛΙΔΟΥ (Ανάθεση))', 'room' => '105'],
                        ['class' => 'Β4', 'responsible' => 'ΠΑΝΑΓΙΩΤΑ ΒΑΣΙΛΕΙΟΥ', 'assistant' => 'ΕΛΛΗ ΜΕΛΕΤΙΟΥ', 'room' => '111'],
                        ['class' => 'Β5', 'responsible' => 'ΑΝΔΡΕΑΣ ΖΕΝΙΟΥ', 'assistant' => 'ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ', 'room' => '202'],
                        ['class' => 'Β6', 'responsible' => 'ΜΑΡΙΑ ΙΩΑΝΝΟΥ', 'assistant' => 'ΠΑΝΑΓΙΩΤΗΣ ΠΑΝΤΕΛΙΔΗΣ', 'room' => 'ΑΙΘ. ΒΙΟΛΟΓΙΑΣ'],
                        ['class' => 'Β7', 'responsible' => 'ΗΛΙΑΝΑ ΛΟΪΖΙΔΟΥ', 'assistant' => 'ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ', 'room' => '209'],
                        ['class' => 'Β8', 'responsible' => 'ΣΩΤΗΡΙΑ ΛΑΖΑΡΙΔΟΥ', 'assistant' => 'ΠΑΝΑΓΙΩΤΑ ΚΩΝΣΤΑΝΤΙΝΟΥ ΚΑΤΖΗ', 'room' => '203'],
                        ['class' => 'ΒΕ2', 'responsible' => 'ΑΝΤΖΕΛΙΝΑ ΠΑΠΑΓΕΩΡΓΙΟΥ', 'assistant' => 'ΚΩΝΣΤΑΝΤΙΝΑ ΛΟΥΤΣΙΟΥ', 'room' => 'ΑΙΘ. ΨΥΧΑΓΩΓΙΑΣ'],
                        ['class' => 'Γ1', 'responsible' => 'ΣΩΤΗΡΙΑ ΘΕΜΙΣΤΟΚΛΕΟΥΣ', 'assistant' => 'ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ', 'room' => '210'],
                        ['class' => 'Γ2', 'responsible' => 'ΚΩΝΣΤΑΝΤΙΑ ΚΚΙΜΗ', 'assistant' => 'ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ', 'room' => '204'],
                        ['class' => 'Γ3', 'responsible' => 'ΘΕΟΦΑΝΗΣ ΚΕΝΤΡΩΤΗΣ', 'assistant' => 'ΕΛΛΗ ΜΕΛΕΤΙΟΥ', 'room' => '205'],
                        ['class' => 'Γ4', 'responsible' => 'ΧΡΙΣΤΙΑΝΑ ΧΡΙΣΤΟΥ', 'assistant' => 'ΚΩΝΣΤΑΝΤΙΝΑ ΛΟΥΤΣΙΟΥ', 'room' => '206'],
                        ['class' => 'Γ5', 'responsible' => 'ΜΑΡΙΑ ΟΙΚΟΝΟΜΟΥ', 'assistant' => 'ΠΑΝΑΓΙΩΤΗΣ ΠΑΝΤΕΛΙΔΗΣ', 'room' => '208'],
                        ['class' => 'Γ6', 'responsible' => 'ΑΛΚΗΣ ΠΑΠΗΣ', 'assistant' => 'ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ', 'room' => '212'],
                        ['class' => 'Γ7', 'responsible' => 'ΙΟΡΔΑΝΗΣ ΙΟΡΔΑΝΟΥ', 'assistant' => 'ΠΑΝΑΓΙΩΤΑ ΚΩΝΣΤΑΝΤΙΝΟΥ ΚΑΤΖΗ', 'room' => '216'],
                    ],
                ],
            ],
            'electronic_admin_section' => [
                'title' => 'Ηλεκτρονική Διοίκηση',
                'subtitle' => 'ΗΛΕΚΤΡΟΝΙΚΗ ΔΙΟΙΚΗΣΗ',
                'content' => [
                    'modal_title' => 'ΗΛΕΚΤΡΟΝΙΚΗ ΔΙΟΙΚΗΣΗ',
                    'registration_heading' => 'Οδηγίες για εγγραφή στο Σύστημα Ηλεκτρονικής Διοίκησης',
                    'registration_intro' => 'Αν επιθυμείτε να εγγραφείτε στο Σύστημα Ηλεκτρονικής Διοίκησης για παρακολούθηση του προγράμματος διαγωνισμάτων των παιδιών σας, ακολουθήστε τα πιο κάτω βήματα:',
                    'registration_steps' => [
                        'Δηλώνετε ενδιαφέρον στο σχολείο για να σας δημιουργηθεί κωδικός επαλήθευσης, ο οποίος θα σας αποσταλεί με SMS ή θα δοθεί εκτυπωμένος στο παιδί σας.',
                        'Αφού λάβετε τον κωδικό επαλήθευσης, επισκεφθείτε την ιστοσελίδα www.eschoolsupport.com.',
                        'Συμπληρώνετε τα στοιχεία που θα σας ζητηθούν και στο τέλος εισάγετε τον κωδικό επαλήθευσης που σας δόθηκε από το σχολείο.',
                        'Στο email που δηλώσατε θα αποσταλεί μήνυμα και θα πρέπει να επιλέξετε «Επιβεβαίωση email».',
                        'Μετά την επιβεβαίωση, μπορείτε να προχωρήσετε με την είσοδό σας στο σύστημα.',
                    ],
                    'login_heading' => 'Οδηγίες για την είσοδο στο Σύστημα Ηλεκτρονικής Διοίκησης',
                    'login_steps' => [
                        'Αν κατά την είσοδο εμφανίζεται λάθος σύνδεσης, καθαρίστε το ιστορικό του browser σας (Clear Browser History). Αυτό χρειάζεται συνήθως μόνο την πρώτη φορά που θα παρουσιαστεί το πρόβλημα.',
                        'Αν χρησιμοποιείτε Microsoft Edge, κάντε τα εξής:',
                        'Αν χρησιμοποιείτε Google Chrome, κάντε τα εξής:',
                    ],
                    'edge_heading' => 'Βήματα για Microsoft Edge',
                    'edge_steps' => [
                        'Επιλέξτε στο πάνω δεξί μέρος της οθόνης το εικονίδιο Tools.',
                        'Επιλέξτε Internet Options.',
                        'Στο Browsing History επιλέξτε Delete.',
                        'Στο παράθυρο που ανοίγει βεβαιωθείτε ότι είναι επιλεγμένο το History και επιλέξτε Delete.',
                        'Μετά επισκεφθείτε ξανά τον σύνδεσμο.',
                    ],
                    'chrome_heading' => 'Βήματα για Google Chrome',
                    'chrome_steps' => [
                        'Επιλέξτε το εικονίδιο με τις τρεις κάθετες τελείες.',
                        'Επιλέξτε Settings.',
                        'Στο κάτω μέρος επιλέξτε Advanced.',
                        'Επιλέξτε Clear Browsing data.',
                        'Επιλέξτε All time και ολοκληρώστε τη διαγραφή.',
                        'Μετά επισκεφθείτε ξανά τον σύνδεσμο.',
                    ],
                    'link_label' => 'Μετάβαση στο Σύστημα Ηλεκτρονικής Διοίκησης',
                    'link_url' => 'http://www.gym-ag-athanasios-lem.eschoolsupport.com/',
                ],
            ],
            'gallery_section' => [
                'title' => 'Φωτογραφικό Υλικό',
                'subtitle' => '',
                'content' => [
                    'empty_message' => 'Δεν έχουν προστεθεί ακόμη φωτογραφίες.',
                ],
            ],
        ];
    }

    private function buildDefaultGalleryImages()
    {
        return [
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/7/3.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/7/i18npic.C240x240.3.jpg',
                'alt_text' => 'Φωτογραφικό υλικό σχολείου',
            ],
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/6/2.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/6/i18npic.C240x240.2.jpg',
                'alt_text' => 'Φωτογραφικό υλικό σχολείου',
            ],
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/5/21.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/5/i18npic.C240x240.21.jpg',
                'alt_text' => 'Φωτογραφικό υλικό σχολείου',
            ],
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/8.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/i18npic.C240x240.8.jpg',
                'alt_text' => 'Φωτογραφικό υλικό σχολείου',
            ],
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/1/1.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/1/i18npic.C240x240.1.jpg',
                'alt_text' => 'Φωτογραφικό υλικό σχολείου',
            ],
            [
                'full_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/1/2.jpg',
                'thumb_image_path' => 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/1/i18npic.C240x240.2.jpg',
                'alt_text' => 'Φωτογραφικό υλικό σχολείου',
            ],
        ];
    }

    private function buildDefaultBoardArchiveRows(): array
    {
        return [
            ['year' => '2025-2026', 'role' => 'ΠΡΟΕΔΡΟΣ', 'name' => 'Μιχάλης Αριστείδου'],
            ['year' => '2025-2026', 'role' => 'ΑΝΤΙΠΡΟΕΔΡΟΣ', 'name' => 'Μάριος Γαβριηλίδης'],
            ['year' => '2025-2026', 'role' => 'ΓΡΑΜΜΑΤΕΑΣ', 'name' => 'Βάσια Μέζου'],
            ['year' => '2025-2026', 'role' => 'ΒΟΗΘΟΣ ΓΡΑΜΜΑΤΕΑΣ', 'name' => 'Σπυρούλα Χαραλάμπους'],
            ['year' => '2025-2026', 'role' => 'ΤΑΜΙΑΣ', 'name' => 'Γιάννα Παπαϊωάννου'],
            ['year' => '2025-2026', 'role' => 'ΒΟΗΘΟΣ ΤΑΜΙΑΣ', 'name' => 'Αρίστη Θεοδοσίου'],
            ['year' => '2025-2026', 'role' => 'ΜΕΛΟΣ', 'name' => 'Χαρά Χριστοδούλου'],
            ['year' => '2025-2026', 'role' => 'ΜΕΛΟΣ', 'name' => 'Χρίστος Αριστοδήμου'],
            ['year' => '2025-2026', 'role' => 'ΜΕΛΟΣ', 'name' => 'Πέτρος Κοντογιάννης'],

            ['year' => '2024-2025', 'role' => 'ΠΡΟΕΔΡΟΣ', 'name' => 'Ανδρέας Κωνσταντίνου'],
            ['year' => '2024-2025', 'role' => 'ΑΝΤΙΠΡΟΕΔΡΟΣ', 'name' => 'Ελένη Νικολάου'],
            ['year' => '2024-2025', 'role' => 'ΓΡΑΜΜΑΤΕΑΣ', 'name' => 'Μαρία Γεωργίου'],
            ['year' => '2024-2025', 'role' => 'ΒΟΗΘΟΣ ΓΡΑΜΜΑΤΕΑΣ', 'name' => 'Χρίστος Δημητρίου'],
            ['year' => '2024-2025', 'role' => 'ΤΑΜΙΑΣ', 'name' => 'Άννα Σωτηρίου'],
            ['year' => '2024-2025', 'role' => 'ΒΟΗΘΟΣ ΤΑΜΙΑΣ', 'name' => 'Παναγιώτα Κυριάκου'],
            ['year' => '2024-2025', 'role' => 'ΜΕΛΟΣ', 'name' => 'Νεόφυτος Αντωνίου'],
            ['year' => '2024-2025', 'role' => 'ΜΕΛΟΣ', 'name' => 'Δέσποινα Ιωάννου'],
            ['year' => '2024-2025', 'role' => 'ΜΕΛΟΣ', 'name' => 'Στέλλα Χαραλάμπους'],

            ['year' => '2023-2024', 'role' => 'ΠΡΟΕΔΡΟΣ', 'name' => 'Γιώργος Θεοδώρου'],
            ['year' => '2023-2024', 'role' => 'ΑΝΤΙΠΡΟΕΔΡΟΣ', 'name' => 'Έλενα Χριστοφή'],
            ['year' => '2023-2024', 'role' => 'ΓΡΑΜΜΑΤΕΑΣ', 'name' => 'Αθηνά Παπαδοπούλου'],
            ['year' => '2023-2024', 'role' => 'ΒΟΗΘΟΣ ΓΡΑΜΜΑΤΕΑΣ', 'name' => 'Νικόλας Στυλιανού'],
            ['year' => '2023-2024', 'role' => 'ΤΑΜΙΑΣ', 'name' => 'Ιωάννα Μιχαήλ'],
            ['year' => '2023-2024', 'role' => 'ΒΟΗΘΟΣ ΤΑΜΙΑΣ', 'name' => 'Μάριος Χρίστου'],
            ['year' => '2023-2024', 'role' => 'ΜΕΛΟΣ', 'name' => 'Χρυστάλλα Ανδρέου'],
            ['year' => '2023-2024', 'role' => 'ΜΕΛΟΣ', 'name' => 'Σάββας Νεοκλέους'],
            ['year' => '2023-2024', 'role' => 'ΜΕΛΟΣ', 'name' => 'Εύη Ματθαίου'],

            ['year' => '2022-2023', 'role' => 'ΠΡΟΕΔΡΟΣ', 'name' => 'Πέτρος Πέτρου'],
            ['year' => '2022-2023', 'role' => 'ΑΝΤΙΠΡΟΕΔΡΟΣ', 'name' => 'Κατερίνα Σάββα'],
            ['year' => '2022-2023', 'role' => 'ΓΡΑΜΜΑΤΕΑΣ', 'name' => 'Μαρίνα Ηρακλέους'],
            ['year' => '2022-2023', 'role' => 'ΒΟΗΘΟΣ ΓΡΑΜΜΑΤΕΑΣ', 'name' => 'Αλέξανδρος Μάρκου'],
            ['year' => '2022-2023', 'role' => 'ΤΑΜΙΑΣ', 'name' => 'Έφη Αντωνίου'],
            ['year' => '2022-2023', 'role' => 'ΒΟΗΘΟΣ ΤΑΜΙΑΣ', 'name' => 'Κώστας Σολωμού'],
            ['year' => '2022-2023', 'role' => 'ΜΕΛΟΣ', 'name' => 'Χριστίνα Λοΐζου'],
            ['year' => '2022-2023', 'role' => 'ΜΕΛΟΣ', 'name' => 'Άντρη Παναγιώτου'],
            ['year' => '2022-2023', 'role' => 'ΜΕΛΟΣ', 'name' => 'Μιχάλης Κυριακίδης'],
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

        return trim((string)$value);
    }
}
