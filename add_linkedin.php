<?php
require 'config/database.php';
try {
    $pdo->exec("ALTER TABLE profils_candidats ADD COLUMN linkedin VARCHAR(255) DEFAULT NULL");
    echo "Column added.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
