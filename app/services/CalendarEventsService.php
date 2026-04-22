<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../services/EventsService.php';
require_once __DIR__ . '/../services/AnnouncementsService.php';
require_once __DIR__ . '/../services/UsefulInformationService.php';

$eventsService = new EventsService();
$announcementsService = new AnnouncementsService();
$usefulInformationService = new UsefulInformationService();

$events = $eventsService->getAllEventsForCalendar();

$announcements = array_map(static function ($announcement) {
    return [
        'title' => (string)($announcement['announcement_title'] ?? ''),
        'description' => (string)($announcement['announcement_description'] ?? ''),
        'date' => (string)($announcement['announcement_date'] ?? $announcement['publish_date'] ?? ''),
        'type' => 'announcement',
    ];
}, $announcementsService->getAllAnnouncements());

$events = array_merge($events, $announcements);
$events = array_merge($events, $usefulInformationService->getHolidayCalendarItems());

echo json_encode($events);
?>
