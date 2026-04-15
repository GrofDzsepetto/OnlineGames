<?php
declare(strict_types=1);

/**
 * Schema export script MySQL / MariaDB-hez
 * - csak struktúrát exportál
 * - adatok nélkül
 * - táblák, nézetek, triggerek
 *
 * Futtatás:
 * php export_schema.php
 */

$config = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'dzsepetto_local_quiz',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4',
    'output' => __DIR__ . '/schema_export.sql',
];

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['dbname'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $dbName = $config['dbname'];
    $sql = [];

    $sql[] = "-- Schema export";
    $sql[] = "-- Database: `{$dbName}`";
    $sql[] = "-- Generated at: " . date('Y-m-d H:i:s');
    $sql[] = "";
    $sql[] = "SET FOREIGN_KEY_CHECKS=0;";
    $sql[] = "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';";
    $sql[] = "SET time_zone = '+00:00';";
    $sql[] = "";
    $sql[] = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    $sql[] = "USE `{$dbName}`;";
    $sql[] = "";

    /**
     * 1) Táblák
     */
    $stmt = $pdo->query("
        SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = " . $pdo->quote($dbName) . "
          AND TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
    ");

    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();

        if (!$createStmt || !isset($createStmt['Create Table'])) {
            throw new RuntimeException("Nem sikerült lekérni a tábla definícióját: {$table}");
        }

        $sql[] = "-- --------------------------------------------------------";
        $sql[] = "-- Table structure for table `{$table}`";
        $sql[] = "-- --------------------------------------------------------";
        $sql[] = "DROP TABLE IF EXISTS `{$table}`;";
        $sql[] = $createStmt['Create Table'] . ";";
        $sql[] = "";
    }

    /**
     * 2) Nézetek
     */
    $stmt = $pdo->query("
        SELECT TABLE_NAME
        FROM information_schema.VIEWS
        WHERE TABLE_SCHEMA = " . $pdo->quote($dbName) . "
        ORDER BY TABLE_NAME
    ");

    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($views as $view) {
        $createStmt = $pdo->query("SHOW CREATE VIEW `{$view}`")->fetch();

        if (!$createStmt || !isset($createStmt['Create View'])) {
            throw new RuntimeException("Nem sikerült lekérni a nézet definícióját: {$view}");
        }

        $sql[] = "-- --------------------------------------------------------";
        $sql[] = "-- View structure for view `{$view}`";
        $sql[] = "-- --------------------------------------------------------";
        $sql[] = "DROP VIEW IF EXISTS `{$view}`;";
        $sql[] = $createStmt['Create View'] . ";";
        $sql[] = "";
    }

    /**
     * 3) Triggerek
     */
    $stmt = $pdo->query("
        SELECT TRIGGER_NAME
        FROM information_schema.TRIGGERS
        WHERE TRIGGER_SCHEMA = " . $pdo->quote($dbName) . "
        ORDER BY TRIGGER_NAME
    ");

    $triggers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($triggers as $trigger) {
        $createStmt = $pdo->query("SHOW CREATE TRIGGER `{$trigger}`")->fetch();

        if (!$createStmt || !isset($createStmt['SQL Original Statement'])) {
            continue;
        }

        $timing = $createStmt['Timing'] ?? '';
        $event = $createStmt['Event'] ?? '';
        $table = $createStmt['Table'] ?? '';
        $statement = $createStmt['SQL Original Statement'];

        $sql[] = "-- --------------------------------------------------------";
        $sql[] = "-- Trigger structure for trigger `{$trigger}`";
        $sql[] = "-- --------------------------------------------------------";
        $sql[] = "DROP TRIGGER IF EXISTS `{$trigger}`;";
        $sql[] = "DELIMITER $$";
        $sql[] = "CREATE TRIGGER `{$trigger}` {$timing} {$event} ON `{$table}`";
        $sql[] = "FOR EACH ROW {$statement} $$";
        $sql[] = "DELIMITER ;";
        $sql[] = "";
    }

    $sql[] = "SET FOREIGN_KEY_CHECKS=1;";
    $sql[] = "";

    file_put_contents($config['output'], implode(PHP_EOL, $sql));

    echo "Kész: " . $config['output'] . PHP_EOL;

} catch (Throwable $e) {
    fwrite(STDERR, "Hiba: " . $e->getMessage() . PHP_EOL);
    exit(1);
}