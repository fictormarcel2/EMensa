<?php

declare(strict_types=1);

namespace MensaTogether\Infrastructure;

use mysqli;
use RuntimeException;

final class Database
{
    public static function connect(): mysqli
    {
        $settings = [];
        foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $key) {
            $value = getenv($key);
            if ($value === false || $value === '') {
                throw new RuntimeException('Database environment is incomplete.');
            }
            $settings[$key] = $value;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = mysqli_init();
        $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
        $connection->real_connect(
            $settings['DB_HOST'],
            $settings['DB_USERNAME'],
            $settings['DB_PASSWORD'],
            $settings['DB_DATABASE'],
            (int) $settings['DB_PORT']
        );
        $connection->set_charset('utf8mb4');
        return $connection;
    }
}
