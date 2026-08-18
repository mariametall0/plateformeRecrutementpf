<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id = (int)($_GET["id"] ?? $_POST["id"] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("
            UPDATE concours 
            SET statut = 'actif' 
            WHERE id = ? AND id_gerant = ?
        ");
        $stmt->execute([$id, $_SESSION["id"]]);

        if ($stmt->rowCount() > 0) {
            $msg = "Opportunité activée avec succès.";
            
            // Notifications automatiques aux candidats du même secteur
            try {
                require_once "../includes/notification_helper.php";
                $stmt_info = $pdo->prepare("SELECT titre, secteur FROM concours WHERE id = ?");
                $stmt_info->execute([$id]);
                $offre_data = $stmt_info->fetch(PDO::FETCH_ASSOC);
                if ($offre_data) {
                    notify_new_offer($id, $offre_data['titre'], $offre_data['secteur'] ?? '');
                }
            } catch (Exception $e_notif) {
                error_log("[activer_opportunite] Erreur notifications : " . $e_notif->getMessage());
            }

            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                send_json(['success' => true, 'message' => $msg]);
            } else {
                $_SESSION['success_message'] = $msg;
                header("Location: liste_opportunites.php");
                exit();
            }
        } else {
            $err = "Opportunité non trouvée ou déjà active.";
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                send_error($err, 404);
            } else {
                $_SESSION['error_message'] = $err;
                header("Location: liste_opportunites.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $msg = "Erreur base de données : " . $e->getMessage();
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            send_error($msg, 500);
        } else {
            $_SESSION['error_message'] = $msg;
            header("Location: liste_opportunites.php");
            exit();
        }
    }
} else {
    $err = "ID d'offre invalide.";
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        send_error($err);
    } else {
        $_SESSION['error_message'] = $err;
        header("Location: liste_opportunites.php");
        exit();
    }
}
exit();
