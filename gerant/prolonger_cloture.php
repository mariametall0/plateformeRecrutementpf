<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];

$id_offre = (int)($_GET["id"] ?? $_POST["id"] ?? 0);
if ($id_offre <= 0) {
    send_error("ID de offre invalide.");
}

// Récupérer les détails du offre
try {
    $stmt = $pdo->prepare("SELECT * FROM offres WHERE id = ? AND id_gerant = ?");
    $stmt->execute([$id_offre, $id_gerant]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offre) {
        send_error("Offre non trouvé ou accès refusé.", 404);
    }

    // POST : Prolonger la clôture
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        verify_csrf_token();
        
        $input = json_decode(file_get_contents("php://input"), true);
        $date_cloture  = $input["date_cloture"] ?? $_POST["date_cloture"] ?? "";
        $heure_cloture = $input["heure_cloture"] ?? $_POST["heure_cloture"] ?? "";

        if (empty($date_cloture)) {
            send_error("La nouvelle date de clôture est obligatoire.");
        } elseif ($date_cloture <= $offre['date_cloture']) {
            send_error("La nouvelle date de clôture doit être supérieure à la date actuelle (" . format_date($offre['date_cloture']) . ").");
        } else {
            $pdo->prepare("UPDATE offres SET date_cloture = ?, heure_cloture = ? WHERE id = ?")
                ->execute([$date_cloture, $heure_cloture, $id_offre]);
            
            send_json(['success' => true, 'message' => "Date de clôture prolongée avec succès."]);
        }
    }

    // GET : Détails actuels
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        send_json([
            'success' => true,
            'offre' => [
                'titre' => $offre['titre'],
                'date_cloture' => $offre['date_cloture'],
                'heure_cloture' => $offre['heure_cloture'],
                'date_cloture_fmt' => format_date($offre['date_cloture']) . ($offre['heure_cloture'] ? ' à ' . substr($offre['heure_cloture'], 0, 5) : '')
            ]
        ]);
    }

} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}
exit();
