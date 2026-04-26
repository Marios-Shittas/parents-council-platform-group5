<?php
/**
 * UsefulInformationService
 * Αποθηκεύει και ανακτά το περιεχόμενο της σελίδας "Χρήσιμες Πληροφορίες".
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

        $existsStmt = $this->conn->prepare('SELECT section_id FROM UsefulInformationSections WHERE section_key = ? LIMIT 1');
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
            $sql = 'UPDATE UsefulInformationSections
                    SET section_title = ?, section_subtitle = ?, content_json = ?
                    WHERE section_key = ?';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                $this->lastError = 'Σφάλμα prepare update: ' . $this->conn->error;
                return false;
            }

            $stmt->bind_param('ssss', $title, $subtitle, $contentJson, $sectionKey);
        } else {
            $sql = 'INSERT INTO UsefulInformationSections (section_key, section_title, section_subtitle, content_json)
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
        $legacyPageHeaderSubtitle = 'Συγκεντρωμένες βασικές πληροφορίες για τη σχολική χρονιά, τις αργίες, τη στολή, την ασφάλεια και τα χρήσιμα έντυπα.';
        $currentSymbolSubtitle = 'Χρήσιμοι Συνδέσμοι & Πληροφορίες/ Ενημερωτικό Υλικό/ Έντυπα & Ενημερώσεις';
        $oldSpacedSubtitle = 'Χρήσιμοι σύνδεσμοι και πληροφορίες, ενημερωτικό υλικό, έντυπα και ενημερώσεις.';
        $newPageHeaderTitle = 'Χρήσιμες Πληροφορίες & Σύνδεσμοι';
        $newPageHeaderSubtitle = 'Χρήσιμοι σύνδεσμοι και πληροφορίες, ενημερωτικό υλικό, έντυπα και ενημερώσεις.';

        $pageHeader = $this->getSection('page_header');
        if (is_array($pageHeader)) {
            $currentTitle = (string)($pageHeader['title'] ?? '');
            $currentSubtitle = (string)($pageHeader['subtitle'] ?? '');
            $shouldUpdatePageHeader = (
                $currentSubtitle === $legacyPageHeaderSubtitle
                || $currentSubtitle === $currentSymbolSubtitle
                || $currentSubtitle === $oldSpacedSubtitle
                || $currentTitle === 'Χρήσιμοι Σύνδεσμοι & Πληροφορίες'
                || $currentTitle === 'Χρήσιμοι Σύνδεσμοι και Πληροφορίες'
                || $currentTitle === 'Χρήσιμοι Πληροφορίες & Σύνδεσμοι'
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
            if ($itemTitle !== 'Εκπαιδευτικοί Σύνδεσμοι' && $itemTitle !== 'Έντυπα Ασφάλειας') {
                continue;
            }

            $items[$index]['title'] = 'Πύλη Απουσιολογίου';
            $items[$index]['description'] = 'Άμεση πρόσβαση στην πύλη απουσιολογίου του σχολείου.';
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
                'title' => 'Χρήσιμες Πληροφορίες & Σύνδεσμοι',
                'subtitle' => 'Χρήσιμοι σύνδεσμοι και πληροφορίες, ενημερωτικό υλικό, έντυπα και ενημερώσεις.',
                'content' => [
                    'eyebrow' => 'Οδηγός Γονέων Και Μαθητών',
                ],
            ],
            'quick_links' => [
                'title' => 'Γρήγοροι Σύνδεσμοι',
                'subtitle' => 'Άμεση πρόσβαση στις πιο χρήσιμες επίσημες σελίδες.',
                'content' => [
                    'items' => [
                        [
                            'title' => 'Ιστοσελίδα Σχολείου',
                            'description' => 'Η επίσημη ιστοσελίδα του Γυμνασίου Αγίου Αθανασίου.',
                            'url' => 'https://gym-ag-athanasios-lem.schools.ac.cy/',
                            'icon' => 'fas fa-school',
                        ],
                        [
                            'title' => 'Έντυπα & Εγγραφές',
                            'description' => 'Σελίδα με χρήσιμα έντυπα εγγραφών, μετακινήσεων και ανακοινώσεων.',
                            'url' => 'https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations',
                            'icon' => 'fas fa-file-download',
                        ],
                        [
                            'title' => 'Πύλη Απουσιολογίου',
                            'description' => 'Άμεση πρόσβαση στην πύλη απουσιολογίου του σχολείου.',
                            'url' => 'http://www.gym-ag-athanasios-lem.eschoolsupport.com/',
                            'icon' => 'fas fa-shield-alt',
                        ],
                    ],
                ],
            ],
            'school_year' => [
                'title' => 'Σχολική Χρονιά 2025-2026',
                'subtitle' => 'Βασικές ημερομηνίες για τα δημόσια γυμνάσια στην Κύπρο.',
                'content' => [
                    'items' => [
                        [
                            'label' => "Έναρξη Α' Τετραμήνου",
                            'date' => '5 Σεπτεμβρίου 2025',
                            'description' => 'Έναρξη της σχολικής χρονιάς για τη Μέση Εκπαίδευση.',
                        ],
                        [
                            'label' => "Λήξη Α' Τετραμήνου",
                            'date' => '15 Ιανουαρίου 2026',
                            'description' => 'Ολοκλήρωση του πρώτου τετραμήνου.',
                        ],
                        [
                            'label' => "Β' Τετράμηνο",
                            'date' => '16 Ιανουαρίου 2026',
                            'description' => 'Συνεχίζεται μέχρι το τέλος των προαγωγικών εξετάσεων.',
                        ],
                    ],
                    'note' => 'Η ακριβής τελευταία ημέρα φοίτησης εξαρτάται από το πρόγραμμα των προαγωγικών εξετάσεων και τις ανακοινώσεις της σχολικής μονάδας.',
                ],
            ],
            'holidays' => [
                'title' => 'Επίσημες Αργίες',
                'subtitle' => 'Οι βασικές σχολικές αργίες που ισχύουν για τα δημόσια γυμνάσια.',
                'content' => [
                    'rows' => [
                        ['date' => '1 Οκτωβρίου 2025', 'name' => 'Ημέρα Ανεξαρτησίας της Κύπρου'],
                        ['date' => '28 Οκτωβρίου 2025', 'name' => 'Εθνική Επέτειος'],
                        ['date' => '11 Δεκεμβρίου 2025', 'name' => 'Ημέρα Εκπαιδευτικού'],
                        ['date' => '24 Δεκεμβρίου 2025 - 6 Ιανουαρίου 2026', 'name' => 'Διακοπές Χριστουγέννων'],
                        ['date' => '30 Ιανουαρίου 2026', 'name' => 'Τριών Ιεραρχών και Ελληνικών Γραμμάτων'],
                        ['date' => '10 Φεβρουαρίου 2026', 'name' => 'Ημέρα Εκπαιδευτικού'],
                        ['date' => '23 Φεβρουαρίου 2026', 'name' => 'Καθαρά Δευτέρα'],
                        ['date' => '25 Μαρτίου 2026', 'name' => 'Εθνική Επέτειος'],
                        ['date' => '1 Απριλίου 2026', 'name' => 'Εθνική Επέτειος ΕΟΚΑ'],
                        ['date' => '6 Απριλίου - 19 Απριλίου 2026', 'name' => 'Διακοπές Πάσχα'],
                        ['date' => '23 Απριλίου 2026', 'name' => 'Ονομαστήρια Αρχιεπισκόπου Κύπρου'],
                        ['date' => '1 Μαΐου 2026', 'name' => 'Πρωτομαγιά'],
                        ['date' => '1 Ιουνίου 2026', 'name' => 'Αγίου Πνεύματος'],
                        ['date' => '11 Ιουνίου 2026', 'name' => 'Αποστόλου Βαρνάβα'],
                    ],
                ],
            ],
            'safety' => [
                'title' => 'Ασφάλεια Παιδιών & Χρήσιμα Έντυπα',
                'subtitle' => 'Χρήσιμη ενημέρωση για ασφάλεια στο σχολείο και επίσημες λήψεις εντύπων.',
                'content' => [
                    'bullets' => [
                        'Για θέματα πρόληψης, ασφάλειας και υγείας στο σχολείο, αρμόδιο είναι το Γραφείο Πολιτικής Άμυνας, Ασφάλειας και Υγείας του ΥΠΑΝ.',
                        'Σε περίπτωση περιστατικού ή ατυχήματος, η ενημέρωση της σχολικής μονάδας πρέπει να γίνεται άμεσα, ώστε να ακολουθηθεί η προβλεπόμενη διαδικασία.',
                        'Για επίσημα έντυπα καταγραφής ατυχημάτων και άλλα σχετικά έγγραφα, χρησιμοποιείτε τα έντυπα του ΥΠΑΝ.',
                        'Για ετήσιες ανακοινώσεις σχετικά με πιθανή ασφαλιστική κάλυψη μαθητών, οι γονείς θα πρέπει να παρακολουθούν τις ανακοινώσεις του σχολείου και του Συνδέσμου Γονέων.',
                    ],
                    'downloads' => [
                        [
                            'title' => 'Έντυπα Ασφάλειας και Καταγραφής Ατυχημάτων',
                            'url' => 'https://www.moec.gov.cy/politiki_amyna/ay_entypa.html',
                            'icon' => 'fas fa-download',
                        ],
                        [
                            'title' => 'Επιμορφωτικό Υλικό Ασφάλειας και Υγείας',
                            'url' => 'https://www.moec.gov.cy/politiki_amyna/ay_epimorfotiko_yliko.html',
                            'icon' => 'fas fa-book-open',
                        ],
                        [
                            'title' => 'Έντυπα και ανακοινώσεις του σχολείου',
                            'url' => 'https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations',
                            'icon' => 'fas fa-folder-open',
                        ],
                    ],
                ],
            ],
            'uniform' => [
                'title' => 'Μαθητική Στολή',
                'subtitle' => 'Συνοπτική παρουσίαση με βάση τους εσωτερικούς κανονισμούς του σχολείου.',
                'content' => [
                    'cards' => [
                        [
                            'title' => 'Αγόρια',
                            'items' => [
                                'Γκρίζο παντελόνι',
                                'Άσπρο πουκάμισο, T-shirt ή polo',
                                'Μπλε σκούρο πουλόβερ',
                                'Δεν επιτρέπονται jeans ή αθλητικές φόρμες στην καθημερινή στολή',
                            ],
                        ],
                        [
                            'title' => 'Κορίτσια',
                            'items' => [
                                'Γκρίζα φούστα ή γκρίζο παντελόνι',
                                'Άσπρο πουκάμισο, T-shirt ή polo',
                                'Μπλε σκούρο πουλόβερ',
                                'Δεν επιτρέπονται jeans ή κολάν στην καθημερινή στολή',
                            ],
                        ],
                        [
                            'title' => 'Στολή Γυμναστικής',
                            'items' => [
                                'Μαύρο ή μπλε παντελόνι φόρμας',
                                'Άσπρη, γκρίζα ή σχολική φανέλα',
                                'Αθλητικά παπούτσια',
                                'Πρακτική και ασφαλής ενδυμασία για το μάθημα Φυσικής Αγωγής',
                            ],
                        ],
                    ],
                    'note' => 'Για τις πλήρεις λεπτομέρειες της στολής και των κανονισμών, δείτε τους επίσημους εσωτερικούς κανονισμούς του σχολείου.',
                    'button_text' => 'Προβολή Κανονισμών',
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
            $this->lastError = 'Μη έγκυρη ημερομηνία αργίας.';
            return false;
        }

        if ($name === '') {
            $this->lastError = 'Το όνομα της αργίας είναι υποχρεωτικό.';
            return false;
        }

        $formattedDate = $this->convertIsoDateToHolidayDisplayDate($isoDate);
        if ($formattedDate === null) {
            $this->lastError = 'Δεν ήταν δυνατή η μορφοποίηση της ημερομηνίας αργίας.';
            return false;
        }

        $section = $this->getSection('holidays');
        if (!is_array($section)) {
            $this->lastError = 'Το section των αργιών δεν βρέθηκε.';
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
            'ιανουαρίου' => '01',
            'φεβρουαρίου' => '02',
            'μαρτίου' => '03',
            'απριλίου' => '04',
            'μαΐου' => '05',
            'ιουνίου' => '06',
            'ιουλίου' => '07',
            'αυγούστου' => '08',
            'σεπτεμβρίου' => '09',
            'οκτωβρίου' => '10',
            'νοεμβρίου' => '11',
            'δεκεμβρίου' => '12',
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
            '01' => 'Ιανουαρίου',
            '02' => 'Φεβρουαρίου',
            '03' => 'Μαρτίου',
            '04' => 'Απριλίου',
            '05' => 'Μαΐου',
            '06' => 'Ιουνίου',
            '07' => 'Ιουλίου',
            '08' => 'Αυγούστου',
            '09' => 'Σεπτεμβρίου',
            '10' => 'Οκτωβρίου',
            '11' => 'Νοεμβρίου',
            '12' => 'Δεκεμβρίου',
        ];

        if (!isset($months[$month])) {
            return null;
        }

        return ((int)$day) . ' ' . $months[$month] . ' ' . $year;
    }
}
?>
