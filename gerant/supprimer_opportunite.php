<?php
require_once "../config/session.php";
require_once "../config/database.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] != "gerant") {
    header("Location: ../auth/login.php");
    exit();
}

if (isset($_GET["id"])) {

    $id = $_GET["id"];

    // supprimer seulement si l'opportunité appartient au gérant
    $stmt = $pdo->prepare("DELETE FROM concours WHERE id = ? AND id_gerant = ?");
    $stmt->execute([$id, $_SESSION["id"]]);
}

header("Location: liste_opportunites.php");
exit();