<?php

function extractCreateTables($filePath) {
    $sql = file_get_contents($filePath);

    preg_match_all('/CREATE TABLE.*?;/si', $sql, $matches);

    $tables = [];

    foreach ($matches[0] as $createStmt) {

        // normalize whitespace
        $normalized = preg_replace('/AUTO_INCREMENT=\d+/i', '', $createStmt);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        // get table name
        preg_match('/CREATE TABLE `?(.*?)`? /i', $createStmt, $nameMatch);
        $tableName = $nameMatch[1] ?? 'unknown';

        $tables[$tableName] = trim($normalized);
    }

    return $tables;
}

$localTables = extractCreateTables('local.sql');
$prodTables  = extractCreateTables('prod.sql');

echo "=== TABLE DIFFERENCES ===\n\n";

$missingInProd = array_diff_key($localTables, $prodTables);
$missingInLocal = array_diff_key($prodTables, $localTables);

if ($missingInProd) {
    echo "Tables missing in PROD:\n";
    print_r(array_keys($missingInProd));
}

if ($missingInLocal) {
    echo "Tables missing in LOCAL:\n";
    print_r(array_keys($missingInLocal));
}

$common = array_intersect_key($localTables, $prodTables);

foreach ($common as $table => $localCreate) {
    if ($localCreate !== $prodTables[$table]) {
        echo "\nStructure difference in table: $table\n";
    }
}

echo "\nComparison finished.\n";
