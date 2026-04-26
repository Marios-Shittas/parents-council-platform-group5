<?php
// Arxeio: app\services\CalendarEventsService.php
// Rolos: PHP arxeio tou project pou syndeei backend logiki me tin efarmogi.
// Simeiosi: Allages edo mporoun na epireasoun tin antistoixi selida i service pou to kanei include.
// Epistrefei JSON me ekdiloseis, anakoinoseis, argies kai sxolikes imerominies gia to calendar.
// Xrisimopoieitai apo React calendar, opote to response prepei na einai panta JSON.
header('Content-Type: application/json');
require_once __DIR__ . '/../services/EventsService.php';
require_once __DIR__ . '/../services/AnnouncementsService.php';
require_once __DIR__ . '/../services/UsefulInformationService.php';

// Fortonoume ta services pou dinoun ta diaforetika items tou calendar.
$eventsService = new EventsService();
$announcementsService = new AnnouncementsService();
$usefulInformationService = new UsefulInformationService();

$events = $eventsService->getAllEventsForCalendar();

// Metatrepoume tis anakoinoseis sto idio shape me ta ekdiloseis gia na ta diavasei ena component.
$announcements = array_map(static function ($announcement) {
    return [
        'title' => (string)($announcement['announcement_title'] ?? ''),
        'description' => (string)($announcement['announcement_description'] ?? ''),
        'date' => (string)($announcement['announcement_date'] ?? $announcement['publish_date'] ?? ''),
        'type' => 'announcement',
    ];
}, $announcementsService->getAllAnnouncements());

$events = array_merge($events, $announcements);
// Prosthetoume sto idio feed tis sxolikes imerominies kai tis argies.
$events = array_merge($events, $usefulInformationService->getSchoolYearCalendarItems());
$events = array_merge($events, $usefulInformationService->getHolidayCalendarItems());

// Stelnei ola ta items sto frontend se morfi JSON.
echo json_encode($events);
?>
