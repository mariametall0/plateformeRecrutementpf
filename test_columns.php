<?php
require_once "config/database.php";
$stmt = $pdo->query("SHOW COLUMNS FROM profils_candidats");
echo "Table: profils_candidats\n";
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
$stmt = $pdo->query("SHOW COLUMNS FROM utilisateurs");
echo "\nTable: utilisateurs\n";
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}
