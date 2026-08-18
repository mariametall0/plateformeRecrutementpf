<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];

$id_concours = (int)($_GET["id"] ?? $_POST["id"] ?? 0);
if ($id_concours <= 0) {
    send_error("ID de concours invalide.");
}

// Récupérer les détails du concours
try {
    $stmt = $pdo->prepare("SELECT * FROM concours WHERE id = ? AND id_gerant = ?");
    $stmt->execute([$id_concours, $id_gerant]);
    $concours = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$concours) {
        send_error("Concours non trouvé ou accès refusé.", 404);
    }

    // POST : Prolonger la clôture
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        verify_csrf_token();
        
        $input = json_decode(file_get_contents("php://input"), true);
        $date_cloture  = $input["date_cloture"] ?? $_POST["date_cloture"] ?? "";
        $heure_cloture = $input["heure_cloture"] ?? $_POST["heure_cloture"] ?? "";

        if (empty($date_cloture)) {
            send_error("La nouvelle date de clôture est obligatoire.");
        } elseif ($date_cloture <= $concours['date_cloture']) {
            send_error("La nouvelle date de clôture doit être supérieure à la date actuelle (" . format_date($concours['date_cloture']) . ").");
        } else {
            $pdo->prepare("UPDATE concours SET date_cloture = ?, heure_cloture = ? WHERE id = ?")
                ->execute([$date_cloture, $heure_cloture, $id_concours]);
            
            send_json(['success' => true, 'message' => "Date de clôture prolongée avec succès."]);
        }
    }

    // GET : Détails actuels
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        send_json([
            'success' => true,
            'concours' => [
                'titre' => $concours['titre'],
                'date_cloture' => $concours['date_cloture'],
                'heure_cloture' => $concours['heure_cloture'],
                'date_cloture_fmt' => format_date($concours['date_cloture']) . ($concours['heure_cloture'] ? ' à ' . substr($concours['heure_cloture'], 0, 5) : '')
            ]
        ]);
    }

} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}
exit();
