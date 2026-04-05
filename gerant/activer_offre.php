<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id = (int)($_GET["id"] ?? $_POST["id"] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("
            UPDATE offres 
            SET statut = 'actif' 
            WHERE id = ? AND id_gerant = ?
        ");
        $stmt->execute([$id, $_SESSION["id"]]);

        if ($stmt->rowCount() > 0) {
            $msg = "Offre activée avec succès.";
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                send_json(['success' => true, 'message' => $msg]);
            } else {
                $_SESSION['success_message'] = $msg;
                header("Location: liste_offres.php");
                exit();
            }
        } else {
            $err = "Offre non trouvée ou déjà active.";
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                send_error($err, 404);
            } else {
                $_SESSION['error_message'] = $err;
                header("Location: liste_offres.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $msg = "Erreur base de données : " . $e->getMessage();
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            send_error($msg, 500);
        } else {
            $_SESSION['error_message'] = $msg;
            header("Location: liste_offres.php");
            exit();
        }
    }
} else {
    $err = "ID d'offre invalide.";
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        send_error($err);
    } else {
        $_SESSION['error_message'] = $err;
        header("Location: liste_offres.php");
        exit();
    }
}
exit();
