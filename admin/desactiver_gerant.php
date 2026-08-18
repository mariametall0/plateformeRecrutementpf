<?php
declare(strict_types=1);

/**
 * desactiver_gerant.php – Désactivation sécurisée d'un compte recruteur.
 */
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée.", 405);
    }

    $input = json_decode(file_get_contents("php://input"), true);
    
    // Vérification CSRF
    $token = $input['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        throw new Exception("Erreur de sécurité : Jeton CSRF invalide.", 403);
    }

    $id = (int)($input["id"] ?? 0);
    if ($id <= 0) {
        throw new Exception("ID gérant invalide.");
    }

    $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = 'inactif' WHERE id = ? AND role = 'gerant'");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        send_json(['success' => true, 'message' => 'Compte recruteur désactivé.']);
    } else {
        throw new Exception("Recruteur non trouvé ou déjà inactif.");
    }

} catch (Exception $e) {
    error_log("DEACTIVATE ERROR: " . $e->getMessage());
    send_json(['error' => $e->getMessage()], (int)($e->getCode() ?: 400));
}
