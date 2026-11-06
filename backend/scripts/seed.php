<?php

declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
$db = \MensaTogether\Infrastructure\Database::connect();
foreach (['alex' => 'Alex Campus','sam' => 'Sam Rivera','jules' => 'Jules Park'] as $slug => $alias) {
    $db->execute_query('INSERT IGNORE INTO users(alias,email,password_hash) VALUES(?,?,?)', [$alias,"$slug@example.test",password_hash('CampusLunch!2026', PASSWORD_DEFAULT)]);
}
echo "Fictional evaluation accounts ready; existing data preserved.\n";
