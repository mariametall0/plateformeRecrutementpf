<?php
require_once "../includes/layout.php";

if (!isset($_GET["token"])) {
    send_error("Token manquant.");
}

$token = $_GET["token"];

try {
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE reset_token = ? AND token_expire > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        send_error("Lien expiré ou invalide.", 403);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $input = json_decode(file_get_contents("php://input"), true);
        $password = $input["password"] ?? $_POST["password"] ?? "";
        $confirm  = $input["confirm_password"] ?? $_POST["confirm_password"] ?? "";

        if (empty($password)) {
            send_error("Mot de passe requis.");
        } elseif ($password !== $confirm) {
            send_error("Les mots de passe ne correspondent pas.");
        } elseif (strlen($password) < 8) {
            send_error("Le mot de passe doit contenir au moins 8 caractères.");
        }

        if (!isset($_SESSION['error_message'])) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ?, reset_token = NULL, token_expire = NULL WHERE id = ?");
            $stmt->execute([$hash, $user["id"]]);

            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
                send_json(['success' => true, 'message' => "Mot de passe changé."]);
            } else {
                $_SESSION['success_message'] = "Mot de passe changé avec succès. Connectez-vous maintenant.";
                header("Location: login.php?role=candidat");
                exit();
            }
        }
    }

    include_header("Réinitialisation");
    ?>
    <div class="auth-wrapper py-5 d-flex align-items-center justify-content-center bg-light vh-100">
        <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5" style="max-width: 500px; width: 100%;">
            <div class="text-center mb-4">
                <div class="display-4 text-primary mb-2">🔑</div>
                <h2 class="fw-bold h4">Nouveau mot de passe</h2>
                <p class="text-muted small">Veuillez définir votre nouvel accès sécurisé.</p>
            </div>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase">Nouveau mot de passe</label>
                    <div class="input-group">
                        <input type="password" name="password" id="reset_password" class="form-control bg-light py-2" required autofocus placeholder="Minimum 8 caractères">
                        <button class="btn btn-outline-secondary border-0 bg-light" type="button" onclick="togglePassword('reset_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold small text-uppercase">Confirmer</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control bg-light py-2" required placeholder="Répéter le mot de passe">
                        <button class="btn btn-outline-secondary border-0 bg-light" type="button" onclick="togglePassword('confirm_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-sm border-0">
                    Changer le mot de passe
                </button>
            </form>
        </div>
    </div>
    <?php
    include_footer();
} catch (PDOException $e) {
    send_error("Erreur DB : " . $e->getMessage(), 500);
}
exit();
?>