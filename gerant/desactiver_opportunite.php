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
        UPDATE concours 
        SET statut = 'inactif' 
        WHERE id = ? AND id_gerant = ?
    ");

    $stmt->execute([$id, $_SESSION["id"]]);
    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "L'opportunité a été désactivée.";

        // Notifier les candidats qui ont postulé à cette offre
        try {
            require_once "../includes/notification_helper.php";

            // Récupérer le titre de l'offre
            $stmt_titre = $pdo->prepare("SELECT titre FROM concours WHERE id = ?");
            $stmt_titre->execute([$id]);
            $titre = $stmt_titre->fetchColumn();

            if ($titre) {
                // Récupérer tous les candidats ayant postulé
                $stmt_cands = $pdo->prepare("
                    SELECT DISTINCT c.id_candidat
                    FROM candidatures c
                    WHERE c.id_concours = ?
                ");
                $stmt_cands->execute([$id]);
                $candidats = $stmt_cands->fetchAll(PDO::FETCH_COLUMN);

                $content = "L'offre \"" . $titre . "\" a été clôturée et n'accepte plus de nouvelles candidatures.";
                foreach ($candidats as $user_id) {
                    create_notification((int)$user_id, 'offre_cloturee', $content, true);
                }
            }
        } catch (Exception $e_notif) {
            error_log("[desactiver_opportunite] Erreur notifications : " . $e_notif->getMessage());
        }
    }
}

header("Location: liste_opportunites.php");
exit();