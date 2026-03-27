<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../services/EventsService.php';
require_once __DIR__ . '/../services/UsefulInformationService.php';

$eventsService = new EventsService();
$usefulInformationService = new UsefulInformationService();

$events = $eventsService->getAllEventsForCalendar();

$events = array_merge($events, $usefulInformationService->getHolidayCalendarItems());

echo json_encode($events);
?>
