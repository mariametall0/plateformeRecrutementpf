<?php
require_once "config/database.php";
$tables = ['utilisateurs', 'profils_candidats', 'cv_experiences', 'cv_formations', 'cv_competences'];
foreach ($tables as $t) {
    echo "<h3>Table: $t</h3>";
    try {
        $stmt = $pdo->query("DESCRIBE $t");
        echo "<pre>";
        print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red'>Erreur sur $t : " . $e->getMessage() . "</p>";
    }
}
