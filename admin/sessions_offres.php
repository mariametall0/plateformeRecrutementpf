<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

// Actions : Activer / Désactiver une session
if (isset($_GET['action'], $_GET['id'])) {
    $id_session = (int)$_GET['id'];
    $new_statut = ($_GET['action'] === 'activer') ? 'active' : 'inactive';
    try {
        $pdo->prepare("UPDATE sessions_offres SET statut = ? WHERE id = ?")
            ->execute([$new_statut, $id_session]);
        send_json(['success' => true, 'message' => "Session " . ($_GET['action'] === 'activer' ? 'activée' : 'désactivée') . "."]);
    } catch (PDOException $e) {
        send_error("Erreur base de données : " . $e->getMessage(), 500);
    }
}

// Action : Créer une session (POST JSON ou Form)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    $input = json_decode(file_get_contents("php://input"), true);
    $id_offre = (int)($input['id_offre'] ?? $_POST['id_offre'] ?? 0);
    $date_debut  = $input['date_debut'] ?? $_POST['date_debut'] ?? "";
    $date_fin    = $input['date_fin'] ?? $_POST['date_fin'] ?? "";

    if ($id_offre <= 0 || empty($date_debut) || empty($date_fin) || $date_fin < $date_debut) {
        send_error("Données de session invalides (vérifiez les dates).");
    } else {
        try {
            $pdo->prepare("INSERT INTO sessions_offres (id_offre, date_debut, date_fin, statut) VALUES (?, ?, ?, 'inactive')")
                ->execute([$id_offre, $date_debut, $date_fin]);
            send_json(['success' => true, 'message' => "Session créée avec succès (inactive par défaut)."], 201);
        } catch (PDOException $e) {
            send_error("Erreur base de données : " . $e->getMessage(), 500);
        }
    }
}

// Liste des sessions
try {
    $stmt = $pdo->prepare("
        SELECT s.*, co.titre AS offre_titre
        FROM sessions_offres s
        JOIN offres co ON s.id_offre = co.id
        ORDER BY s.date_debut DESC
    ");
    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les dates
    foreach ($sessions as &$s) {
        $s['date_debut'] = format_date($s['date_debut']);
        $s['date_fin']   = format_date($s['date_fin']);
    }

    send_json([
        'success' => true,
        'count' => count($sessions),
        'sessions' => $sessions
    ]);
} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}
exit();
