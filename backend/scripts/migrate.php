<?php

declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
$db = \MensaTogether\Infrastructure\Database::connect();
$files = glob(dirname(__DIR__).'/database/migrations/*.sql');
if (!$files) {
    throw new RuntimeException('Migration files are missing. Build the image or mount backend/database before starting.');
}
$locked = $db->query("SELECT GET_LOCK('mensatogether_schema', 30) AS acquired")->fetch_assoc();
if ((int) $locked['acquired'] !== 1) {
    throw new RuntimeException('Another migration is running. Retry startup.');
}
$db->query('CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(190) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT UTC_TIMESTAMP())');
foreach ($files as $file) {
    $name = basename($file);
    if ($db->execute_query('SELECT name FROM schema_migrations WHERE name=?', [$name])->num_rows) {
        continue;
    }
    $db->multi_query(file_get_contents($file));
    do {
        if ($result = $db->store_result()) {
            $result->free();
        }
    } while ($db->more_results() && $db->next_result());
    $db->execute_query('INSERT INTO schema_migrations(name) VALUES(?)', [$name]);
    echo "Applied $name\n";
}
$db->query("SELECT RELEASE_LOCK('mensatogether_schema')");
echo "Migrations ready.\n";
