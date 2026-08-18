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
    <div class="reset-password-v2">
        <div class="reset-card">
            <a href="login.php?role=candidat" class="back-link">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            
            <div class="reset-icon">
                <i class="bi bi-key-fill"></i>
            </div>
            
            <h2 class="reset-title">Nouveau mot de passe</h2>
            <p class="reset-subtitle">Veuillez définir votre nouvel accès sécurisé.</p>
            
            <form method="POST">
                <div class="input-field">
                    <label class="form-label">
                        <i class="bi bi-lock"></i> Nouveau mot de passe
                    </label>
                    <div class="input-group form-control-pro">
                        <i class="bi bi-shield-lock"></i>
                        <input type="password" name="password" id="reset_password" required autofocus placeholder="Minimum 8 caractères">
                        <button class="btn border-0 p-0 text-muted" type="button" onclick="togglePassword('reset_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="input-field">
                    <label class="form-label">
                        <i class="bi bi-check-circle"></i> Confirmer
                    </label>
                    <div class="input-group form-control-pro">
                        <i class="bi bi-shield-check"></i>
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder="Répéter le mot de passe">
                        <button class="btn border-0 p-0 text-muted" type="button" onclick="togglePassword('confirm_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-pro btn-pro-primary reset-button">
                    <i class="bi bi-send"></i> Changer le mot de passe
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
