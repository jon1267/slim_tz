<?php

use App\Service\PostalIndexImporter;

require __DIR__ . '/../vendor/autoload.php';

try {
    // Временно создаем прямое подключение PDO к вашей базе данных
    $dsn = 'mysql:host=MySQL-8.0;dbname=slim;charset=utf8mb4';
    $username = 'test';
    $password = 'test';

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);


    $importer = new PostalIndexImporter($pdo);

    // $zipPath = __DIR__ . '/../var/imports/postindex_dp_tz.zip'; // Path to the ZIP archive
    // $csvFileName = 'postindex_dp_tz.csv'; // Name of the CSV file inside the archive

    $zipPath = __DIR__ . '/../var/imports/postindex_tz.zip'; // Path to the ZIP archive
    $csvFileName = 'postindex_tz.csv'; // Name of the CSV file inside the archive


    echo "=== Start of postcode synchronization ===\n";
    $startTime = microtime(true);

    $importer->import($zipPath, $csvFileName);

    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);

    echo "Synchronization completed successfully!\n";
    echo "Execution time: {$executionTime} sec.\n";
    echo "Memory usage: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB\n";

} catch (Exception $e) {
    fwrite(STDERR, "Критическая ошибка: " . $e->getMessage() . "\n");
    exit(1);
}

