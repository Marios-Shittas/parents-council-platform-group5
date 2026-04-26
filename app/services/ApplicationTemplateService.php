<?php
// Arxeio: app\services\ApplicationTemplateService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Prosoxi: afora aitiseis/templates kai uploads, ara ta paths kai ta validation einai simantika.
/**
 * ApplicationTemplateService
 * Diaxeirizetai reusable aitisi templates gia standard/epanalamvanomenes aitiseis
 */
class ApplicationTemplateService {
    private $conn;
    private $templatesTable = 'ApplicationTemplates';
    private $templatesTableChecked = false;
    private $templatesTableReady = false;
    
    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    public function __construct($conn) {
        $this->conn = $conn;
        $this->ensureTemplatesTableReady();
    }

    /**
     * Eksasfalizei to templates pinakas yparxei gia palaioteres egkatastaseis that have not run to
     * aitiseis v2 migration yet.
     */
    private function ensureTemplatesTableReady() {
        if ($this->templatesTableChecked) {
            return $this->templatesTableReady;
        }

        $this->templatesTableChecked = true;

        try {
            if (!$this->templatesTableExists()) {
                $this->createTemplatesTable();
            }

            $this->templatesTableReady = true;
        } catch (Throwable $e) {
            $this->templatesTableReady = false;
            error_log('ApplicationTemplateService initialization failed: ' . $e->getMessage());
        }

        return $this->templatesTableReady;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function templatesTableExists() {
        $tableName = $this->conn->real_escape_string($this->templatesTable);
        $result = $this->conn->query("SHOW TABLES LIKE '{$tableName}'");
        return $result instanceof mysqli_result && $result->num_rows > 0;
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function createTemplatesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `ApplicationTemplates` (
                    `template_id` INT NOT NULL AUTO_INCREMENT,
                    `template_key` VARCHAR(150) NOT NULL,
                    `name` VARCHAR(255) NOT NULL,
                    `description` TEXT DEFAULT NULL,
                    `category` VARCHAR(100) NOT NULL DEFAULT 'standard',
                    `form_schema` LONGTEXT DEFAULT NULL,
                    `is_system_template` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`template_id`),
                    UNIQUE KEY `uq_application_templates_template_key` (`template_key`),
                    KEY `idx_application_templates_category` (`category`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->conn->query($sql);
    }

    // Perigrafei ti leitourgia tou antistoixou tmimatos me emfasi sti statherotita kai tin egkyrotita dedomenon.
    private function seedDefaultTemplatesIfNeeded() {
        if (!$this->templatesTableReady) {
            return;
        }

        $result = $this->conn->query(
            "SELECT COUNT(*) AS total FROM `ApplicationTemplates` WHERE `is_system_template` = 1"
        );

        if (!$result) {
            return;
        }

        $row = $result->fetch_assoc();
        if ((int)($row['total'] ?? 0) > 0) {
            return;
        }

        try {
            $this->seedDefaultTemplates();
        } catch (Throwable $e) {
            error_log('ApplicationTemplateService seeding failed: ' . $e->getMessage());
        }
    }
    
    // ============================================================
    // Anaktisi dedomenon.
    // ============================================================
    
    /**
     * Get all protypa
     */
    public function getAllTemplates($isSystemOnly = false) {
        if (!$this->ensureTemplatesTableReady()) {
            return [];
        }

        $sql = "SELECT * FROM `ApplicationTemplates`";
        if ($isSystemOnly) {
            $sql .= " WHERE is_system_template = 1";
        }
        $sql .= " ORDER BY category, name";
        
        $result = $this->conn->query($sql);
        $templates = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row['form_schema']) {
                    $row['form_schema'] = json_decode($row['form_schema'], true);
                }
                $templates[] = $row;
            }
        }
        return $templates;
    }
    
    /**
     * Get protypo by ID
     */
    public function getTemplateById($templateId) {
        if (!$this->ensureTemplatesTableReady()) {
            return null;
        }

        $sql = "SELECT * FROM `ApplicationTemplates` WHERE template_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($row['form_schema']) {
                $row['form_schema'] = json_decode($row['form_schema'], true);
            }
            return $row;
        }
        return null;
    }
    
    /**
     * Get protypo by key
     */
    public function getTemplateByKey($templateKey) {
        if (!$this->ensureTemplatesTableReady()) {
            return null;
        }

        $sql = "SELECT * FROM `ApplicationTemplates` WHERE template_key = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $templateKey);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if ($row['form_schema']) {
                $row['form_schema'] = json_decode($row['form_schema'], true);
            }
            return $row;
        }
        return null;
    }
    
    /**
     * Get protypa by category
     */
    public function getTemplatesByCategory($category) {
        if (!$this->ensureTemplatesTableReady()) {
            return [];
        }

        $sql = "SELECT * FROM `ApplicationTemplates` WHERE category = ? ORDER BY name";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $category);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $templates = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['form_schema']) {
                $row['form_schema'] = json_decode($row['form_schema'], true);
            }
            $templates[] = $row;
        }
        return $templates;
    }
    
    // ============================================================
    // Leitourgies CRUD (kyrios gia custom protypa)
    // ============================================================
    
    /**
     * Dimiourgia a new protypo
     */
    public function createTemplate($templateKey, $name, $description, $category, $formSchema, $isSystemTemplate = false) {
        if (!$this->ensureTemplatesTableReady()) {
            return false;
        }

        if (empty($templateKey) || empty($name) || !is_array($formSchema)) {
            return false;
        }
        
        $formSchemaJson = json_encode($formSchema, JSON_UNESCAPED_UNICODE);
        
        $sql = "INSERT INTO `ApplicationTemplates` 
                (template_key, name, description, category, form_schema, is_system_template)
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sssssi",
            $templateKey,
            $name,
            $description,
            $category,
            $formSchemaJson,
            $isSystemTemplate
        );

        if (!$stmt->execute()) {
            return false;
        }

        return (int)$this->conn->insert_id;
    }
    
    /**
     * Enimerosi a custom protypo
     */
    public function updateTemplate($templateId, $name, $description, $category, $formSchema) {
        if (!$this->ensureTemplatesTableReady()) {
            return false;
        }

        $template = $this->getTemplateById($templateId);
        if (!$template || $template['is_system_template']) {
            return false;
        }
        
        // An to formSchema einai idi JSON string, to krata. allios kane encode.
        if (is_array($formSchema)) {
            $formSchemaJson = json_encode($formSchema, JSON_UNESCAPED_UNICODE);
        } else {
            $formSchemaJson = $formSchema;
        }
        
        $sql = "UPDATE `ApplicationTemplates` 
                SET name = ?, description = ?, category = ?, form_schema = ?, updated_at = NOW()
                WHERE template_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $description, $category, $formSchemaJson, $templateId);
        
        return $stmt->execute();
    }
    
    /**
     * Diagrafi a custom protypo (not system protypa)
     */
    public function deleteTemplate($templateId) {
        if (!$this->ensureTemplatesTableReady()) {
            return false;
        }

        $template = $this->getTemplateById($templateId);
        if (!$template || $template['is_system_template']) {
            return false;
        }
        
        $sql = "DELETE FROM `ApplicationTemplates` WHERE template_id = ? AND is_system_template = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $templateId);
        
        return $stmt->execute();
    }
    
    // ============================================================
    // Arxikopoisi me proepilegmena protypa.
    // ============================================================
    
    /**
     * Vazei arxika system templates mesa se vasi
     * Kaleitai mia fora kata tin egkatastasi
     */
    public function seedDefaultTemplates() {
        $templates = $this->getDefaultTemplates();
        
        foreach ($templates as $template) {
            // Elegxei an yparxei idi i eggrafi.
            $existing = $this->getTemplateByKey($template['key']);
            if ($existing) {
                continue;
            }
            
            $this->createTemplate(
                $template['key'],
                $template['name'],
                $template['description'],
                $template['category'],
                $template['schema'],
                true
            );
        }
    }
    
    /**
     * Get the proepilegmeno protypo definitions
     */
    private function getDefaultTemplates() {
        return [
            // Protypo 1: Syndromi / Asfalisi
            [
                'key' => 'subscription-insurance',
                'name' => 'Î£Ï…Î½Î´ÏÎ¿Î¼Î® / Î‘ÏƒÏ†Î¬Î»Î¹ÏƒÎ·',
                'description' => 'Î•Ï„Î®ÏƒÎ¹Î± ÏƒÏ…Î½Î´ÏÎ¿Î¼Î® ÎºÎ±Î¹ Î±ÏƒÏ†Î±Î»Î¹ÏƒÏ„Î¹ÎºÎ® ÎºÎ¬Î»Ï…ÏˆÎ· Î¼Î±Î¸Î·Ï„Î®',
                'category' => 'standard',
                'schema' => [
                    'sections' => [
                        [
                            'title' => 'Î£Ï„Î¿Î¹Ï‡ÎµÎ¯Î± ÎœÎ±Î¸Î·Ï„Î®',
                            'fields' => [
                                [
                                    'name' => 'student_name',
                                    'label' => 'ÎŸÎ½Î¿Î¼Î±Ï„ÎµÏ€ÏŽÎ½Ï…Î¼Î¿ ÎœÎ±Î¸Î·Ï„Î®',
                                    'type' => 'text',
                                    'required' => true,
                                    'help_text' => ''
                                ],
                                [
                                    'name' => 'student_birthdate',
                                    'label' => 'Î—Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î± Î“Î­Î½Î½Î·ÏƒÎ·Ï‚',
                                    'type' => 'date',
                                    'required' => true,
                                    'help_text' => ''
                                ],
                                [
                                    'name' => 'student_class',
                                    'label' => 'Î¤Î¼Î®Î¼Î± / Î¤Î¬Î¾Î·',
                                    'type' => 'select',
                                    'required' => true,
                                    'options' => ['A', 'B', 'C', 'Î“\''],
                                    'help_text' => ''
                                ]
                            ]
                        ],
                        [
                            'title' => 'Î£Ï„Î¿Î¹Ï‡ÎµÎ¯Î± ÎšÎ·Î´ÎµÎ¼ÏŒÎ½Î±',
                            'fields' => [
                                [
                                    'name' => 'guardian_name',
                                    'label' => 'ÎŸÎ½Î¿Î¼Î±Ï„ÎµÏ€ÏŽÎ½Ï…Î¼Î¿ ÎšÎ·Î´ÎµÎ¼ÏŒÎ½Î±',
                                    'type' => 'text',
                                    'required' => true,
                                    'help_text' => ''
                                ],
                                [
                                    'name' => 'guardian_phone',
                                    'label' => 'Î¤Î·Î»Î­Ï†Ï‰Î½Î¿ Î•Ï€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±Ï‚',
                                    'type' => 'tel',
                                    'required' => true,
                                    'help_text' => ''
                                ],
                                [
                                    'name' => 'guardian_email',
                                    'label' => 'Email Î•Ï€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±Ï‚',
                                    'type' => 'email',
                                    'required' => true,
                                    'help_text' => ''
                                ]
                            ]
                        ],
                        [
                            'title' => 'Î•Î³Î³ÏÎ±Ï†Î­Ï‚',
                            'fields' => [
                                [
                                    'name' => 'subscription_checkbox',
                                    'label' => 'Î£Ï…Î½Î´ÏÎ¿Î¼Î® ÏƒÏ„Î¿ Î£ÏÎ½Î´ÎµÏƒÎ¼Î¿',
                                    'type' => 'checkbox',
                                    'required' => false,
                                    'help_text' => 'Î‘Ï€Î¿Î´Î­Ï‡Î¿Î¼Î±Î¹ Ï„Î· ÏƒÏ…Î½Î´ÏÎ¿Î¼Î®'
                                ],
                                [
                                    'name' => 'insurance_checkbox',
                                    'label' => 'Î‘ÏƒÏ†Î±Î»Î¹ÏƒÏ„Î¹ÎºÎ® ÎšÎ¬Î»Ï…ÏˆÎ·',
                                    'type' => 'checkbox',
                                    'required' => false,
                                    'help_text' => 'Î‘Ï€Î¿Î´Î­Ï‡Î¿Î¼Î±Î¹ Ï„Î·Î½ Î±ÏƒÏ†Î±Î»Î¹ÏƒÏ„Î¹ÎºÎ® ÎºÎ¬Î»Ï…ÏˆÎ·'
                                ]
                            ]
                        ],
                        [
                            'title' => 'Î£Ï…Î½Î±Î¯Î½ÎµÏƒÎ· Î•Ï€Î¹ÎºÎ¿Î¹Î½Ï‰Î½Î¯Î±Ï‚',
                            'fields' => [
                                [
                                    'name' => 'consent_communication',
                                    'label' => 'Î›Î®ÏˆÎ· Î•Î¹Î´Î¿Ï€Î¿Î¹Î®ÏƒÎµÏ‰Î½',
                                    'type' => 'radio',
                                    'required' => true,
                                    'options' => ['ÎÎ±Î¹', 'ÎŒÏ‡Î¹'],
                                    'help_text' => 'Î‘Ï€Î¿Î´Î­Ï‡Î¿Î¼Î±Î¹ Î½Î± Î»Î±Î¼Î²Î¬Î½Ï‰ ÎµÎ¹Î´Î¿Ï€Î¿Î¹Î®ÏƒÎµÎ¹Ï‚'
                                ],
                                [
                                    'name' => 'consent_viber',
                                    'label' => 'Viber Community',
                                    'type' => 'radio',
                                    'required' => true,
                                    'options' => ['ÎÎ±Î¹', 'ÎŒÏ‡Î¹'],
                                    'help_text' => 'Î‘Ï€Î¿Î´Î­Ï‡Î¿Î¼Î±Î¹ ÏƒÏ…Î¼Î¼ÎµÏ„Î¿Ï‡Î® ÏƒÏ„Î·Î½ Î¿Î¼Î¬Î´Î± Viber'
                                ]
                            ]
                        ],
                        [
                            'title' => 'Î•Ï€Î¹Î²ÎµÎ²Î±Î¯Ï‰ÏƒÎ·',
                            'fields' => [
                                [
                                    'name' => 'signature',
                                    'label' => 'Î¥Ï€Î¿Î³ÏÎ±Ï†Î® ÎšÎ·Î´ÎµÎ¼ÏŒÎ½Î±',
                                    'type' => 'signature',
                                    'required' => true,
                                    'help_text' => ''
                                ],
                                [
                                    'name' => 'signature_date',
                                    'label' => 'Î—Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î±',
                                    'type' => 'date',
                                    'required' => true,
                                    'help_text' => ''
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            
            // Protypo 2: Synainesi ekdilosis
            [
                'key' => 'event-consent',
                'name' => 'Î£Ï…Î½Î±Î¯Î½ÎµÏƒÎ· Î£Ï…Î¼Î¼ÎµÏ„Î¿Ï‡Î®Ï‚ ÏƒÎµ Î•ÎºÎ´Î®Î»Ï‰ÏƒÎ·',
                'description' => 'ÎœÎ¿ÏÏ†Î® ÏƒÏ…Î½Î±Î¯Î½ÎµÏƒÎ·Ï‚ Î³Î¹Î± ÏƒÏ…Î¼Î¼ÎµÏ„Î¿Ï‡Î® ÏƒÎµ ÏƒÏ‡Î¿Î»Î¹ÎºÎ® ÎµÎºÎ´Î®Î»Ï‰ÏƒÎ· Î® Î´ÏÎ±ÏƒÏ„Î·ÏÎ¹ÏŒÏ„Î·Ï„Î±',
                'category' => 'event',
                'schema' => [
                    'sections' => [
                        [
                            'title' => 'Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚ ÎœÎ±Î¸Î·Ï„Î®',
                            'fields' => [
                                [
                                    'name' => 'student_name_event',
                                    'label' => 'ÎŸÎ½Î¿Î¼Î±Ï„ÎµÏ€ÏŽÎ½Ï…Î¼Î¿ ÎœÎ±Î¸Î·Ï„Î®',
                                    'type' => 'text',
                                    'required' => true,
                                    'help_text' => ''
                                ],
                                [
                                    'name' => 'student_class_event',
                                    'label' => 'Î¤Î¬Î¾Î·/Î¤Î¼Î®Î¼Î±',
                                    'type' => 'select',
                                    'required' => true,
                                    'options' => ['A', 'B', 'C', 'Î“\''],
                                    'help_text' => ''
                                ]
                            ]
                        ],
                        [
                            'title' => 'Î Î»Î·ÏÎ¿Ï†Î¿ÏÎ¯ÎµÏ‚ ÎšÎ·Î´ÎµÎ¼ÏŒÎ½Î±',
                            'fields' => [
                                [
                                    'name' => 'guardian_name_event',
                                    'label' => 'ÎŸÎ½Î¿Î¼Î±Ï„ÎµÏ€ÏŽÎ½Ï…Î¼Î¿ ÎšÎ·Î´ÎµÎ¼ÏŒÎ½Î±',
                                    'type' => 'text',
                                    'required' => true,
                                    'help_text' => ''
                                ]
                            ]
                        ],
                        [
                            'title' => 'Î£Ï…Î½Î±Î¯Î½ÎµÏƒÎ·',
                            'fields' => [
                                [
                                    'name' => 'consent',
                                    'label' => 'Î”Î·Î»ÏŽÎ½Ï‰ ÏŒÏ„Î¹:',
                                    'type' => 'radio',
                                    'required' => true,
                                    'options' => ['Î£Ï…Î½Î±Î¹Î½ÏŽ', 'Î”ÎµÎ½ Î£Ï…Î½Î±Î¹Î½ÏŽ'],
                                    'help_text' => ''
                                ],
                                [
                                    'name' => 'comments',
                                    'label' => 'Î£Ï‡ÏŒÎ»Î¹Î± / Î Î±ÏÎ±Ï„Î·ÏÎ®ÏƒÎµÎ¹Ï‚',
                                    'type' => 'textarea',
                                    'required' => false,
                                    'help_text' => 'Î ÏÎ¿Î±Î¹ÏÎµÏ„Î¹ÎºÏŒ'
                                ]
                            ]
                        ],
                        [
                            'title' => 'Î¥Ï€Î¿Î³ÏÎ±Ï†Î®',
                            'fields' => [
                                [
                                    'name' => 'signature_event',
                                    'label' => 'Î¥Ï€Î¿Î³ÏÎ±Ï†Î®',
                                    'type' => 'signature',
                                    'required' => true,
                                    'help_text' => ''
                                ],
                                [
                                    'name' => 'signature_date_event',
                                    'label' => 'Î—Î¼ÎµÏÎ¿Î¼Î·Î½Î¯Î±',
                                    'type' => 'date',
                                    'required' => true,
                                    'help_text' => ''
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
?>
