<?php
require_once "config/database.php";
try {
    $pdo->exec("ALTER TABLE utilisateurs ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL AFTER email");
    echo "Column photo_path added to utilisateurs table successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column photo_path already exists in utilisateurs table.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
