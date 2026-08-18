<?php
require_once __DIR__ . "/../config/database.php";

try {
    // Copy the city/adresse value to emplacement_exact for existing rows where it's empty
    $stmt = $pdo->prepare("UPDATE entreprises SET emplacement_exact = adresse WHERE emplacement_exact IS NULL OR emplacement_exact = ''");
    $stmt->execute();
    $count = $stmt->rowCount();
    
    echo "Successfully updated $count existing recruiter company locations with their city address.\n";
} catch (PDOException $e) {
    echo "Error updating existing recruiters: " . $e->getMessage() . "\n";
}
