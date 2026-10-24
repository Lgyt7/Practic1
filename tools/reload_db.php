<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/config.php';

$dump = dirname(__DIR__) . '/backend/database/dump.sql';
$sql  = is_file($dump) ? file_get_contents($dump) : false;

if ($sql === false) {
    exit("Не удалось прочитать backend/database/dump.sql\n");
}

try {
    $pdo = new PDO(
        'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec($sql);
} catch (Throwable $e) {
    exit('Ошибка применения dump.sql: ' . $e->getMessage() . "\n");
}

echo 'dump.sql применён, база «' . DB_NAME . '» пересоздана.' . PHP_EOL;

$constraints = [];

foreach ($pdo->query(
    "SELECT conname FROM pg_constraint WHERE conrelid = 'applications'::regclass ORDER BY conname"
) as $row) {
    $constraints[] = $row['conname'];
}

echo 'Ограничений на applications: ' . count($constraints) . PHP_EOL;
echo 'Теперь выполните: php backend/database/seed.php' . PHP_EOL;
