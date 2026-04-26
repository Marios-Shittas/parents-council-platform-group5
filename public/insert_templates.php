<?php
require_once __DIR__ . '/../app/config/db.php';

$templates = [
    [
        'key' => 'event_consent_' . time(),
        'name' => 'Δήλωση Συμμετοχής Εκδήλωσης',
        'desc' => 'Template για συμμετοχή σε εκδηλώσεις',
        'cat' => 'event',
        'schema' => json_encode([
            ['name' => 'Ονοματεπώνυμο Μαθητή', 'type' => 'text', 'required' => true],
            ['name' => 'Τάξη', 'type' => 'text', 'required' => true],
            ['name' => 'ΑΜΚΑ', 'type' => 'text', 'required' => false]
        ], JSON_UNESCAPED_UNICODE)
    ],
    [
        'key' => 'insurance_' . time(),
        'name' => 'Ασφάλιση Μαθητών',
        'desc' => 'Template για στοιχεία ασφάλισης',
        'cat' => 'insurance',
        'schema' => json_encode([
            ['name' => 'ΑΜΚΑ', 'type' => 'text', 'required' => true],
            ['name' => 'Email', 'type' => 'email', 'required' => true],
            ['name' => 'Τηλέφωνο', 'type' => 'phone', 'required' => true]
        ], JSON_UNESCAPED_UNICODE)
    ],
    [
        'key' => 'special_form_' . time(),
        'name' => 'Ειδική Αίτηση',
        'desc' => 'Template για ειδικές αιτήσεις',
        'cat' => 'special',
        'schema' => json_encode([
            ['name' => 'Σκοπός Αίτησης', 'type' => 'textarea', 'required' => true],
            ['name' => 'Ημερομηνία Υποβολής', 'type' => 'date', 'required' => true]
        ], JSON_UNESCAPED_UNICODE)
    ]
];

foreach($templates as $t) {
    $sql = "INSERT INTO ApplicationTemplates (template_key, name, description, category, form_schema, is_system_template) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssss', $t['key'], $t['name'], $t['desc'], $t['cat'], $t['schema']);
    if($stmt->execute()) {
        echo "✓ Προστέθηκε: " . $t['name'] . "\n";
    } else {
        echo "✗ Σφάλμα: " . $stmt->error . "\n";
    }
}

echo "\nΣύνολο templates: ";
$result = $conn->query("SELECT COUNT(*) FROM ApplicationTemplates");
$row = $result->fetch_row();
echo $row[0] . "\n";

echo "\nΛίστα templates:\n";
$result = $conn->query("SELECT template_id, name, category FROM ApplicationTemplates");
while($row = $result->fetch_assoc()) {
    echo "- ID: {$row['template_id']}, Name: {$row['name']}, Category: {$row['category']}\n";
}
?>
