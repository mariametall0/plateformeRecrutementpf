<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_candidat = $_SESSION["id"];
$id_offre = (int)($_GET["id"] ?? $_POST["id"] ?? 0);

if ($id_offre <= 0) {
    send_error("ID de l'offre invalide.");
}

try {
    // Vérifier si déjà postulé
    $stmt = $pdo->prepare("SELECT id FROM candidatures WHERE id_candidat = ? AND id_offre = ?");
    $stmt->execute([$id_candidat, $id_offre]);
    $exist = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exist) {
        send_json([
            'success' => false,
            'message' => "Vous avez déjà postulé à cette offre.",
            'id_candidature' => $exist['id']
        ], 409);
    }

    // Vérification de sécurité : une candidature ne peut pas avoir offre ET concours
    $id_concours_check = (int)($_POST["id_concours"] ?? $_GET["id_concours"] ?? 0);
    if ($id_offre && $id_concours_check) {
        die("Erreur logique");
    }

    // Insérer la candidature
    $stmt = $pdo->prepare("INSERT INTO candidatures (id_candidat, id_concours, id_offre, statut, date_candidature) VALUES (?, NULL, ?, 'en_attente', NOW())");
    $stmt->execute([$id_candidat, $id_offre]);
    $id_candidature = $pdo->lastInsertId();

    send_json([
        'success' => true,
        'message' => "Votre candidature a été envoyée avec succès.",
        'id_candidature' => $id_candidature
    ]);

} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}
exit();