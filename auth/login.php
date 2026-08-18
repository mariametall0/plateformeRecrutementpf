<?php
declare(strict_types=1);

/**
 * login.php - Connexion securisee a la plateforme Admissio.
 */
require_once "../includes/layout.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rawRole = $_GET["role"] ?? $_POST["role"] ?? $_SESSION["role"] ?? "";
$role = is_string($rawRole) ? trim($rawRole) : "candidat";
if (!in_array($role, ["candidat", "gerant", "admin"], true)) {
    $role = "candidat";
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        verify_csrf_token();
        $email    = trim((string)($_POST["email"] ?? ""));
        $password = (string)($_POST["password"] ?? "");
        if (empty($email) || empty($password)) {
            throw new Exception("Email et mot de passe requis.");
        }
        $stmt = $pdo->prepare("
            SELECT u.*, p.photo_path AS candidate_photo, u.photo_path AS user_photo 
            FROM utilisateurs u 
            LEFT JOIN profils_candidats p ON u.id = p.id_utilisateur 
            WHERE u.email = ? AND u.role = ?
        ");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, (string)$user["mot_de_passe"])) {
            if (in_array($role, ['gerant', 'admin'], true) && ($user['statut'] ?? '') !== 'actif') {
                throw new Exception("Ce compte est suspendu. Veuillez contacter l'administrateur.");
            }
            session_regenerate_id(true);
            $_SESSION["id"]    = (int)$user["id"];
            $_SESSION["nom"]   = (string)$user["nom"];
            $_SESSION["role"]  = (string)$user["role"];
            $_SESSION["email"] = (string)$user["email"];
            $_SESSION["photo_path"] = ($user['role'] === 'candidat') ? $user['candidate_photo'] : $user['user_photo'];
            
            // Génération du token d'authentification (pour plus de sécurité)
            $_SESSION["auth_token"] = bin2hex(random_bytes(32));
            
            $redirect = $_SESSION['redirect_after_login'] ?? "../{$user['role']}/dashboard.php";
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit();
        } else {
            throw new Exception("Identifiants de connexion incorrects.");
        }
    } catch (Exception $e) {
        $_SESSION['login_error'] = $e->getMessage();
    }
}

$role_labels = ['candidat' => 'Candidat', 'gerant' => 'Recruteur', 'admin' => 'Administrateur'];
$role_icons  = ['candidat' => 'bi-person-fill', 'gerant' => 'bi-building', 'admin' => 'bi-shield-lock-fill'];
$role_label  = $role_labels[$role] ?? 'Candidat';
$role_icon   = $role_icons[$role] ?? 'bi-person-fill';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion <?php echo $role_label; ?> - Admissio</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= PROJECT_PATH ?>assets/css/admissio_design.css">
<style>
.login-wrap {
    min-height: 100vh;
    background: linear-gradient(160deg, #f0fdf4 0%, #d1fae5 50%, #ecfeff 100%);
    display: flex; align-items: center; justify-content: center;
    position: relative; overflow: hidden; padding: 2rem 1rem;
}
.blob { position: absolute; border-radius: 50%; filter: blur(100px); pointer-events: none; }
.blob-1 { width: 600px; height: 600px; background: radial-gradient(circle, rgba(110,168,254,.28) 0%, transparent 70%); top: -180px; right: -180px; animation: drift 20s ease-in-out infinite alternate; }
.blob-2 { width: 450px; height: 450px; background: radial-gradient(circle, rgba(13,202,240,.15) 0%, transparent 70%); bottom: -120px; left: -120px; animation: drift 16s ease-in-out infinite alternate-reverse; }
@keyframes drift { 0% { transform: translate(0,0) scale(1); } 100% { transform: translate(50px, 35px) scale(1.07); } }

.login-card {
    background: rgba(255,255,255,.88);
    backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255,255,255,.95);
    border-radius: 28px;
    padding: 3rem;
    box-shadow: 0 30px 80px rgba(15,81,50,.12), 0 2px 0 rgba(255,255,255,.8) inset;
    width: 100%; max-width: 460px;
    position: relative; z-index: 1;
}
@media (max-width: 480px) {
    .login-card { padding: 2rem 1.5rem; border-radius: 20px; }
    .login-card h1 { font-size: 1.5rem; }
}
.role-pill {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(15,81,50,.08); color: #0f5132;
    padding: 6px 16px; border-radius: 100px;
    font-weight: 700; font-size: .82rem;
    border: 1px solid rgba(15,81,50,.15);
    margin-bottom: 1.5rem;
}
.login-card h1 { font-size: 1.8rem; font-weight: 900; color: #0f172a; margin-bottom: .4rem; }
.login-card .sub { font-size: .92rem; color: #64748b; margin-bottom: 2rem; }
.form-floating-group { position: relative; margin-bottom: 1.25rem; }
.form-floating-group .fi {
    position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: 1rem; pointer-events: none;
}
.form-floating-group input {
    width: 100%;
    border: 1.5px solid rgba(0,0,0,.1);
    border-radius: 14px;
    padding: .85rem 1rem .85rem 2.75rem;
    font-size: .95rem; font-family: 'Inter', sans-serif;
    color: #0f172a; background: rgba(255,255,255,.7);
    transition: border-color .2s, box-shadow .2s;
    outline: none;
}
.form-floating-group input:focus {
    border-color: #0f5132;
    box-shadow: 0 0 0 3px rgba(15,81,50,.1);
    background: #fff;
}
.form-floating-group .eye-btn {
    position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: #94a3b8; cursor: pointer; padding: .25rem;
    transition: color .2s;
}
.form-floating-group .eye-btn:hover { color: #0f5132; }
.btn-login {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: .9rem;
    background: #0f5132; color: #fff;
    border: none; border-radius: 100px;
    font-weight: 700; font-size: 1rem; font-family: 'Inter', sans-serif;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(15,81,50,.35);
    transition: all .25s;
    text-decoration: none; margin-top: .5rem;
}
.btn-login:hover { background: #0a3622; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15,81,50,.4); }
.btn-secondary-login {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: .8rem;
    background: transparent; color: #0f172a;
    border: 1.5px solid rgba(0,0,0,.1); border-radius: 100px;
    font-weight: 600; font-size: .9rem; font-family: 'Inter', sans-serif;
    cursor: pointer; transition: all .25s; text-decoration: none;
    margin-top: .75rem;
}
.btn-secondary-login:hover { background: #f8fafc; color: #0f172a; transform: translateY(-1px); }
.divider { display: flex; align-items: center; gap: 1rem; margin: 1.5rem 0; }
.divider hr { flex: 1; border: none; border-top: 1px solid rgba(0,0,0,.08); }
.divider span { font-size: .8rem; color: #94a3b8; font-weight: 600; white-space: nowrap; }
.alert-err {
    background: rgba(239,68,68,.08); color: #dc2626;
    border: 1px solid rgba(239,68,68,.15); border-radius: 14px;
    padding: .875rem 1.25rem; font-size: .88rem; font-weight: 600;
    display: flex; align-items: center; gap: .75rem;
    margin-bottom: 1.25rem;
}
.nav-back {
    position: absolute; top: 1.5rem; left: 1.5rem; z-index: 10;
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.8); backdrop-filter: blur(10px);
    border: 1px solid rgba(0,0,0,.08); border-radius: 100px;
    padding: 8px 16px; color: #0f172a; font-weight: 600; font-size: .85rem;
    text-decoration: none; transition: all .2s;
}
.nav-back:hover { background: #fff; color: #0f5132; }
</style>
</head>
<body>
<div class="login-wrap">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <a href="<?= PROJECT_PATH ?>index.php" class="nav-back">
        <i class="bi bi-arrow-left"></i> Accueil
    </a>

    <div class="login-card">
        <div class="role-pill">
            <i class="bi <?php echo $role_icon; ?>"></i>
            <?php echo $role_label; ?>
        </div>
        
        <p class="sub">Connectez-vous a votre espace <strong><?php echo $role_label; ?></strong></p>

        <?php 
        $error_to_show = $_SESSION['login_error'] ?? $_SESSION['error_message'] ?? null;
        if (!empty($error_to_show)): 
        ?>
        <div class="alert-err">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?php 
            echo htmlspecialchars($error_to_show); 
            unset($_SESSION['login_error'], $_SESSION['error_message']); 
            ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">

            <div class="form-floating-group">
                <i class="bi bi-envelope fi"></i>
                <input type="email" name="email" id="login_email" placeholder="votre@email.com" required autofocus>
            </div>

            <div class="form-floating-group">
                <i class="bi bi-lock fi"></i>
                <input type="password" name="password" id="login_password" placeholder="Mot de passe" required>
                <button type="button" class="eye-btn" onclick="togglePassword('login_password', this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <a href="forgot_password.php" style="font-size:.85rem; color:#0f5132; font-weight:600; text-decoration:none;">Mot de passe oublie ?</a>
            </div>

            <button type="submit" class="btn-login">
                Se connecter <i class="bi bi-arrow-right"></i>
            </button>
        </form>

        <?php if (in_array($role, ['candidat', 'gerant'])): ?>
        <div class="divider">
            <hr><span>Ou</span><hr>
        </div>
        <div class="text-center mt-3" style="font-size: .95rem; font-weight: 500; color: #64748b;">
            Vous n'avez pas de compte ? 
            <?php if ($role === 'candidat'): ?>
                <a href="register_candidat.php" style="color: #0f5132; font-weight: 700; text-decoration: none;">S'inscrire</a>
            <?php else: ?>
                <a href="register_gerant.php" style="color: #0f5132; font-weight: 700; text-decoration: none;">S'inscrire</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.type = (input.type === 'password') ? 'text' : 'password';
    btn.querySelector('i').className = (input.type === 'password') ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
</body>
</html>