#!/usr/bin/env php
<?php

declare(strict_types=1);

// Arxeio: app\scripts\run_scheduled_maintenance.php
// Rolos: CLI script pou trexei programmatismenes ergasies syntirisis gia to project.
// Simeiosi: To shebang kai to declare(strict_types=1) prepei na menoun stin arxi gia sosto CLI/PHP parsing.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "This script can only run from CLI." . PHP_EOL;
    exit(1);
}

$projectRoot = dirname(__DIR__, 2);
$lockPath = $projectRoot . '/storage/scheduled_maintenance.lock';
$lockHandle = fopen($lockPath, 'c');

if ($lockHandle === false) {
    fwrite(STDERR, "Could not create/open lock file: {$lockPath}" . PHP_EOL);
    exit(1);
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, "Maintenance is already running." . PHP_EOL);
    fclose($lockHandle);
    exit(0);
}

try {
    require_once $projectRoot . '/app/services/UsersService.php';
    $usersService = new UsersService();
    $report = $usersService->runScheduledMaintenanceWithReport();

    $output = [
        'timestamp' => date('Y-m-d H:i:s'),
        'deleted_users' => (int)($report['deleted_users'] ?? 0),
        'deleted_submissions' => (int)($report['deleted_submissions'] ?? 0),
    ];

    echo json_encode($output, JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, 'Scheduled maintenance failed: ' . $exception->getMessage() . PHP_EOL);
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    exit(1);
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);
exit(0);
