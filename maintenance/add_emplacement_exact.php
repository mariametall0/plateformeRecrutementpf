<?php
require_once __DIR__ . "/../config/database.php";

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM entreprises LIKE 'emplacement_exact'");
    $column = $stmt->fetch();

    if (!$column) {
        $pdo->exec("ALTER TABLE entreprises ADD COLUMN emplacement_exact VARCHAR(255) NULL AFTER adresse");
        echo "Column 'emplacement_exact' successfully added to 'entreprises' table.\n";
    } else {
        echo "Column 'emplacement_exact' already exists in 'entreprises' table.\n";
    }
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
