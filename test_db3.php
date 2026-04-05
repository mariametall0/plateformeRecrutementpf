<?php
require_once "config/database.php";
try {
    $stmt = $pdo->query("SELECT statut, COUNT(*) FROM candidatures c JOIN offre co ON c.id_offre = co.id GROUP BY statut");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
