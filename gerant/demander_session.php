<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];

// POST : Envoyer une nouvelle demande
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    $input = json_decode(file_get_contents("php://input"), true);
    $id_concours = (int)($input["id_concours"] ?? $_POST["id_concours"] ?? 0);
    $msg_demande = trim($input["message"] ?? $_POST["message"] ?? "");

    if ($id_concours <= 0) {
        send_error("ID de concours invalide.");
    }

    // Vérifier que le concours appartient au gérant
    try {
        $stmt_check = $pdo->prepare("SELECT id, titre FROM concours WHERE id = ? AND id_gerant = ?");
        $stmt_check->execute([$id_concours, $id_gerant]);
        $concours_ok = $stmt_check->fetch();

        if (!$concours_ok) {
            send_error("Concours non trouvé ou accès refusé.", 404);
        } else {
            // Vérifier qu'il n'y a pas déjà une demande en attente pour ce concours
            $stmt_exist = $pdo->prepare("SELECT id FROM demandes_session WHERE id_concours = ? AND statut = 'en_attente'");
            $stmt_exist->execute([$id_concours]);
            if ($stmt_exist->fetch()) {
                send_error("Une demande est déjà en attente pour ce concours.", 409);
            } else {
                $pdo->prepare("INSERT INTO demandes_session (id_concours, id_gerant, message) VALUES (?, ?, ?)")
                    ->execute([$id_concours, $id_gerant, $msg_demande ?: null]);
                send_json(['success' => true, 'message' => "Demande d'activation de session envoyée avec succès."]);
            }
        }
    } catch (PDOException $e) {
        send_error("Erreur base de données : " . $e->getMessage(), 500);
    }
}

// GET : Historique des demandes + liste des concours (pour le formulaire)
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        // Récupérer les concours du gérant
        $stmt_c = $pdo->prepare("SELECT id, titre, statut FROM concours WHERE id_gerant = ? ORDER BY titre");
        $stmt_c->execute([$id_gerant]);
        $mes_concours = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les demandes du gérant
        $stmt_d = $pdo->prepare("
            SELECT d.*, co.titre AS concours_titre
            FROM demandes_session d
            JOIN concours co ON d.id_concours = co.id
            WHERE d.id_gerant = ?
            ORDER BY d.date_demande DESC
        ");
        $stmt_d->execute([$id_gerant]);
        $demandes = $stmt_d->fetchAll(PDO::FETCH_ASSOC);

        // Formater les dates
        foreach ($demandes as &$d) {
            $d['date_demande_fmt'] = format_date($d['date_demande'], true);
        }

        send_json([
            'success' => true,
            'mes_concours' => $mes_concours,
            'demandes' => $demandes
        ]);
    } catch (PDOException $e) {
        send_error("Erreur base de données : " . $e->getMessage(), 500);
    }
}
exit();
