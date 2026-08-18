<?php
require_once "../includes/layout.php";

require_once "../includes/mailer.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lecture des données (POST traditionnel ou JSON)
    $input = json_decode(file_get_contents("php://input"), true);
    $email = trim($input["email"] ?? $_POST["email"] ?? "");

    if (empty($email)) {
        send_error("Email requis.");
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, reset_last_request FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Vérifier flood (3 min)
                if (!empty($user["reset_last_request"])) {
                    $last = strtotime($user["reset_last_request"]);
                    if ((time() - $last) < 180) {
                        send_error("Veuillez attendre 3 minutes avant une nouvelle demande.", 429);
                    }
                }

                if (!isset($_SESSION['error_message'])) {
                    // Générer token
                    $token = bin2hex(random_bytes(32));
                    $expire = date("Y-m-d H:i:s", strtotime("+30 minutes"));
                    $now = date("Y-m-d H:i:s");

                    $stmt = $pdo->prepare("UPDATE utilisateurs SET reset_token = ?, token_expire = ?, reset_last_request = ? WHERE email = ?");
                    $stmt->execute([$token, $expire, $now, $email]);

                    $link = "http://localhost/plateforme_recrutement/auth/reset_password.php?token=" . $token;

                    $subject = 'Réinitialisation de votre mot de passe';
                    $body = "Bonjour,<br><br>Cliquez sur ce lien pour réinitialiser votre mot de passe : <br><a href='$link'>$link</a><br><br>Ce lien expire dans 30 minutes.";
                    
                    $mail_success = exec_send_email($email, $subject, $body);
                    if (!$mail_success) throw new Exception("Erreur PHPMailer.");

                    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
                        send_json(['success' => true, 'message' => "Email envoyé."]);
                    } else {
                        $_SESSION['success_message'] = "Si cet email existe, un lien de réinitialisation a été envoyé.";
                        header("Location: forgot_password.php");
                        exit();
                    }
                }
            } else {
                // Même message pour sécurité
                $_SESSION['success_message'] = "Si cet email existe, un lien de réinitialisation a été envoyé.";
                header("Location: forgot_password.php");
                exit();
            }
        } catch (Exception $e) {
            send_error("Erreur envoi mail : " . $e->getMessage(), 500);
        } catch (PDOException $e) {
            send_error("Erreur DB : " . $e->getMessage(), 500);
        }
    }
}

// Affichage du formulaire
include_header("Mot de passe oublié");
?>

<div class="auth-wrapper">
    <!-- Formes d'arrière-plan décoratives -->
    <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-warning opacity-10 blur-custom" style="width: 600px; height: 600px;"></div>
    <div class="position-absolute bottom-0 end-0 translate-middle-y rounded-circle bg-success opacity-10 blur-custom" style="width: 400px; height: 400px;"></div>

    <div class="container py-5 position-relative z-1 d-flex justify-content-center">
        <div class="card border-0 shadow-lg rounded-5 p-4 p-md-5 animate__animated animate__zoomIn bg-white bg-opacity-75 backdrop-blur" style="max-width: 450px; width: 100%;">
            <div class="text-center mb-4">
                <div class="mx-auto bg-warning bg-opacity-10 text-warning p-3 rounded-circle mb-3 icon-hover-bounce d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                    <i class="bi bi-key-fill fs-2"></i>
                </div>
                <h2 class="fw-black h3 mb-2">Récupération</h2>
                <p class="text-muted small">Entrez votre adresse email pour recevoir un lien de réinitialisation sécurisé.</p>
            </div>

            <form method="POST">
                <div class="mb-4 mt-2">
                    <label class="form-label fw-bold small text-uppercase text-muted mb-2">Adresse Email</label>
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control bg-white border-start-0 ps-0 py-2" placeholder="exemple@mail.com" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-warning w-100 py-3 fw-bold rounded-pill text-dark hover-lift-lg btn-lg fs-6 shadow-sm mt-2">
                    <i class="bi bi-send me-2"></i> Envoyer le lien
                </button>
            </form>
            
            <div class="mt-4 pt-4 border-top text-center small">
                <a href="../choix_connexion.php" class="btn btn-sm btn-light rounded-pill px-4 text-muted border fw-semibold hover-lift-lg shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Retour à la connexion
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
include_footer();
exit();
?>
