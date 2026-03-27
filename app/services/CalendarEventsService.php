<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../services/EventsService.php';
require_once __DIR__ . '/../services/UsefulInformationService.php';

$eventsService = new EventsService();
$usefulInformationService = new UsefulInformationService();

$events = $eventsService->getAllEventsForCalendar();

$greekMonths = [
    'Ιανουαρίου' => '01', 'Φεβρουαρίου' => '02', 'Μαρτίου' => '03',
    'Απριλίου' => '04', 'Μαΐου' => '05', 'Ιουνίου' => '06',
    'Ιουλίου' => '07', 'Αυγούστου' => '08', 'Σεπτεμβρίου' => '09',
    'Οκτωβρίου' => '10', 'Νοεμβρίου' => '11', 'Δεκεμβρίου' => '12'
];

$sections = $usefulInformationService->getAllSections();
$holidays = $sections['holidays']['content']['rows'] ?? [];

foreach ($holidays as $holiday) {
    $dateStr = $holiday['date'];

    if (strpos($dateStr, '-') !== false && strpos($dateStr, ' - ') !== false) {
        continue;
    }

    foreach ($greekMonths as $greek => $num) {
        $dateStr = str_replace($greek, $num, $dateStr);
    }

    $parts = explode(' ', trim($dateStr));
    $formattedDate = count($parts) === 3
        ? $parts[2] . '-' . $parts[1] . '-' . str_pad($parts[0], 2, '0', STR_PAD_LEFT)
        : null;

    if ($formattedDate) {
        $events[] = [
            'title' => $holiday['name'],
            'description' => '',
            'date' => $formattedDate,
            'type' => 'holiday'
        ];
    }
}

echo json_encode($events);
?>