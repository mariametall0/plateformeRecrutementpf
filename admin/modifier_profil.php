<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

$user_id = $_SESSION["id"];

// GET : Récupérer les informations du profil
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        $stmt = $pdo->prepare("SELECT id, nom, email, telephone, role, statut, date_creation FROM utilisateurs WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $user['date_creation'] = format_date($user['date_creation']);
            send_json(['success' => true, 'user' => $user]);
        } else {
            send_error("Utilisateur non trouvé.", 404);
        }
    } catch (PDOException $e) {
        send_error("Erreur base de données : " . $e->getMessage(), 500);
    }
}

// POST : Mettre à jour le profil
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    $input = json_decode(file_get_contents("php://input"), true);
    $nom   = trim($input["nom"] ?? $_POST["nom"] ?? "");
    $email = trim($input["email"] ?? $_POST["email"] ?? "");

    if (empty($nom) || empty($email)) {
        send_error("Le nom et l'email sont obligatoires.");
    } else {
        try {
            // Vérifier si l'email est déjà utilisé par un autre compte
            $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $check->execute([$email, $user_id]);
            if ($check->rowCount() > 0) {
                send_error("Cet email est déjà utilisé par un autre compte.");
            } else {
                $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ? WHERE id = ?");
                $stmt->execute([$nom, $email, $user_id]);
                
                $_SESSION["nom"] = $nom;
                send_json(['success' => true, 'message' => "Profil mis à jour avec succès.", 'user' => ['nom' => $nom, 'email' => $email]]);
            }
        } catch (PDOException $e) {
            send_error("Erreur base de données : " . $e->getMessage(), 500);
        }
    }
}
exit();
