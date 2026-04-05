<?php
require_once "../config/session.php";
require_once "../config/database.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "gerant") {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET["id"])) {

    $id = $_GET["id"];

    $stmt = $pdo->prepare("
        UPDATE offres 
        SET statut = 'inactif' 
        WHERE id = ? AND id_gerant = ?
    ");

    $stmt->execute([$id, $_SESSION["id"]]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "L'offre a été désactivée.";
    }
}

header("Location: liste_offres.php");
exit();