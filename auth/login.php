<?php
require_once "../includes/layout.php";

// Sécurité supplémentaire pour la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialisation robuste du rôle (évite les warnings si $role n'est pas défini ou null)
$rawRole = $_GET["role"] ?? $_POST["role"] ?? $_SESSION["role"] ?? "";
$role = is_string($rawRole) ? trim($rawRole) : "";
if ($role === "" || !in_array($role, ["candidat", "gerant", "admin"], true)) {
    $role = "candidat";
}

// Token CSRF si manquant
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

error_log("DEBUG LOGIN: role=" . ($role ?? 'NULL') . ", session_id=" . session_id());
error_log("DEBUG LOGIN: session_csrf=" . ($_SESSION['csrf_token'] ?? 'NULL'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifier le token CSRF (formulaires & JSON)
    if (function_exists('verify_csrf_token')) {
        verify_csrf_token();
    }

    // Supporte à la fois les formulaires HTML standards et les requêtes JSON (AJAX)
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    if (!is_array($input)) {
        $input = [];
    }

    $email    = trim($input["email"] ?? $_POST["email"] ?? "");
    $password = $input["password"] ?? $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        send_error("Email et mot de passe requis.");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["mot_de_passe"])) {
            if (in_array($role, ['gerant', 'admin']) && $user['statut'] !== 'actif') {
                send_error("Compte inactif. Contactez l'administrateur.", 403);
            } else {
                session_regenerate_id(true);
                $_SESSION["id"]   = $user["id"];
                $_SESSION["nom"]  = $user["nom"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["email"] = $user["email"];

                if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
                    $redirect = $_SESSION['redirect_after_login'] ?? "../{$user['role']}/dashboard.php";
                    unset($_SESSION['redirect_after_login']);
                    send_json([
                        'success' => true,
                        'message' => 'Connexion réussie.',
                        'user' => [
                            'id' => $user['id'],
                            'nom' => $user['nom'],
                            'role' => $user['role'],
                            'email' => $user['email']
                        ],
                        'redirect_hint' => $redirect
                    ]);
                } else {
                    $redirect = $_SESSION['redirect_after_login'] ?? "../{$user['role']}/dashboard.php";
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                    exit();
                }
            }
        } else {
            send_error("Identifiants incorrects.", 401);
        }
    }
}

// Affichage du formulaire
include_header("Connexion " . ucfirst($role));
$role_colors = [
    'candidat' => 'primary',
    'gerant' => 'success',
    'admin' => 'dark'
];
$color = $role_colors[$role] ?? 'primary';
?>

<div class="auth-wrapper position-relative overflow-hidden min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);">
    <!-- Formes d'arrière-plan décoratives -->
    <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-<?php echo $color; ?> opacity-10 blur-custom" style="width: 600px; height: 600px; filter: blur(80px);"></div>
    <div class="position-absolute bottom-0 end-0 translate-middle-y rounded-circle bg-info opacity-10 blur-custom" style="width: 400px; height: 400px; filter: blur(60px);"></div>

    <div class="container py-5 position-relative z-1 d-flex justify-content-center">
        <div class="card border-0 shadow-lg rounded-5 p-4 p-md-5 animate__animated animate__zoomIn bg-white bg-opacity-75 backdrop-blur border-top border-<?php echo $color; ?> border-5" style="max-width: 450px; width: 100%; border-width: 5px 0 0 0 !important;">
            <div class="text-center mb-4">
                <div class="mx-auto bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?> p-3 rounded-circle mb-3 icon-hover-bounce" style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi <?php echo $role === 'candidat' ? 'bi-person-badge' : ($role === 'gerant' ? 'bi-briefcase' : 'bi-shield-lock'); ?> fs-2"></i>
                </div>
                <h2 class="fw-black h3 mb-1" style="font-family: 'Outfit', sans-serif;">Admissio</h2>
                <p class="text-muted small">Espace <strong class="text-<?php echo $color; ?>"><?php echo ($role === 'gerant' ? 'Recruteur' : ucfirst($role)); ?></strong></p>
            </div>

            <form method="POST" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Adresse Email</label>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control bg-white border-start-0 ps-0 fs-6" placeholder="nom@exemple.ma" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-0" style="letter-spacing: 1px; font-size: 0.75rem;">Mot de passe</label>
                        <a href="forgot_password.php" class="text-<?php echo $color; ?> text-decoration-none small fw-semibold">Oublié ?</a>
                    </div>
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-key"></i></span>
                        <input type="password" name="password" id="login_password" class="form-control bg-white border-start-0 border-end-0 ps-0 fs-6" placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary border-start-0 bg-white text-muted" type="button" onclick="togglePassword('login_password', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-<?php echo $color; ?> w-100 py-3 fw-bold shadow-sm rounded-pill mt-2 hover-lift-lg btn-lg fs-6" style="box-shadow: 0 0 15px rgba(var(--bs-<?php echo $color; ?>-rgb), 0.4) !important;">
                    Se connecter <i class="bi bi-box-arrow-in-right ms-2"></i>
                </button>
            </form>

            <div class="mt-4 pt-4 border-top text-center small">
                <?php if ($role === 'candidat'): ?>
                    <span class="text-muted">Pas encore de compte ?</span> 
                    <a href="register_candidat.php" class="text-<?php echo $color; ?> fw-bold text-decoration-none">S'inscrire</a>
                <?php elseif ($role === 'gerant'): ?>
                    <span class="text-muted">Une structure, une école ou une entreprise ?</span> 
                    <a href="register_gerant.php" class="text-<?php echo $color; ?> fw-bold text-decoration-none">Demander un compte</a>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="../choix_connexion.php" class="btn btn-sm btn-light rounded-pill px-4 text-muted border fw-semibold hover-lift-lg">
                        <i class="bi bi-arrow-left me-1"></i> Retour aux rôles
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Utilities */
.hover-lift-lg { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.hover-lift-lg:hover { transform: translateY(-3px); box-shadow: 0 1rem 3rem rgba(0,0,0,.15)!important; }
.backdrop-blur { backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); }
.custom-glow { box-shadow: 0 0 15px rgba(var(--bs-<?php echo $color; ?>-rgb), 0.4) !important; }
.icon-hover-bounce { transition: transform 0.3s ease; }
.icon-hover-bounce:hover { transform: translateY(-5px) scale(1.05); }
.fw-black { font-weight: 900; }
</style>

<?php 
include_footer();
exit();
?>