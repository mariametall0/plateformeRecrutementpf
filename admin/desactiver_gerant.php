<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

$input = json_decode(file_get_contents("php://input"), true);
$id = (int)($input["id"] ?? $_GET["id"] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = 'inactif' WHERE id = ? AND role = 'gerant'");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            $msg = 'Compte gérant désactivé.';
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                send_json(['success' => true, 'message' => $msg]);
            } else {
                $_SESSION['success_message'] = $msg;
                header("Location: liste_gerants.php");
            }
        } else {
            $err = "Gérant non trouvé ou déjà inactif.";
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                send_error($err, 404);
            } else {
                $_SESSION['error_message'] = $err;
                header("Location: liste_gerants.php");
            }
        }
    } catch (PDOException $e) {
        $msg = "Erreur base de données : " . $e->getMessage();
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            send_error($msg, 500);
        } else {
            $_SESSION['error_message'] = $msg;
            header("Location: liste_gerants.php");
        }
    }
} else {
    $err = "ID gérant invalide.";
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        send_error($err);
    } else {
        $_SESSION['error_message'] = $err;
        header("Location: liste_gerants.php");
    }
}
exit();
