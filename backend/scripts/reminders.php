<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use MensaTogether\Delivery\ReminderWorker;
use MensaTogether\Infrastructure\Database;

$watch = in_array('--watch', $argv, true);
do {
    try {
        $result = (new ReminderWorker(Database::connect()))->run();
        echo json_encode($result, JSON_THROW_ON_ERROR) . PHP_EOL;
    } catch (Throwable) {
        fwrite(STDERR, "Reminder worker unavailable; retrying without logging private payloads.\n");
        if (!$watch) {
            exit(1);
        }
    }
    if ($watch) {
        sleep(15);
    }
} while ($watch);
