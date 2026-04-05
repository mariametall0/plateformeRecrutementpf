<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];

// POST : Envoyer une nouvelle demande
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    $input = json_decode(file_get_contents("php://input"), true);
    $id_offre = (int)($input["id_offre"] ?? $_POST["id_offre"] ?? 0);
    $msg_demande = trim($input["message"] ?? $_POST["message"] ?? "");

    if ($id_offre <= 0) {
        send_error("ID de offre invalide.");
    }

    // Vérifier que le offre appartient au gérant
    try {
        $stmt_check = $pdo->prepare("SELECT id, titre FROM offres WHERE id = ? AND id_gerant = ?");
        $stmt_check->execute([$id_offre, $id_gerant]);
        $offre_ok = $stmt_check->fetch();

        if (!$offre_ok) {
            send_error("Offre non trouvé ou accès refusé.", 404);
        } else {
            // Vérifier qu'il n'y a pas déjà une demande en attente pour ce offre
            $stmt_exist = $pdo->prepare("SELECT id FROM demandes_session WHERE id_offre = ? AND statut = 'en_attente'");
            $stmt_exist->execute([$id_offre]);
            if ($stmt_exist->fetch()) {
                send_error("Une demande est déjà en attente pour ce offre.", 409);
            } else {
                $pdo->prepare("INSERT INTO demandes_session (id_offre, id_gerant, message) VALUES (?, ?, ?)")
                    ->execute([$id_offre, $id_gerant, $msg_demande ?: null]);
                send_json(['success' => true, 'message' => "Demande d'activation de session envoyée avec succès."]);
            }
        }
    } catch (PDOException $e) {
        send_error("Erreur base de données : " . $e->getMessage(), 500);
    }
}

// GET : Historique des demandes + liste des offre (pour le formulaire)
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        // Récupérer les offre du gérant
        $stmt_c = $pdo->prepare("SELECT id, titre, statut FROM offres WHERE id_gerant = ? ORDER BY titre");
        $stmt_c->execute([$id_gerant]);
        $mes_offre = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les demandes du gérant
        $stmt_d = $pdo->prepare("
            SELECT d.*, co.titre AS offre_titre
            FROM demandes_session d
            JOIN offres co ON d.id_offre = co.id
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
            'mes_offre' => $mes_offre,
            'demandes' => $demandes
        ]);
    } catch (PDOException $e) {
        send_error("Erreur base de données : " . $e->getMessage(), 500);
    }
}
exit();
