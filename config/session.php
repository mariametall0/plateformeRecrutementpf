<?php
// config/session.php
// Détection du chemin du projet (Local vs Production)
$is_local = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_ADDR'] === '127.0.0.1');
define('PROJECT_PATH', $is_local ? '/plateforme_recrutement/' : '/');


// Buffet de sortie pour éviter "headers already sent"
if (ob_get_level() === 0) ob_start();

// Paramètres de session stricts avant le démarrage
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// Empêcher la mise en cache des pages protégées par le navigateur
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date dans le passé

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Génération d'un token CSRF général pour la session s'il n'existe pas
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Vérifie le token CSRF pour les requêtes POST.
 */
function verify_csrf_token(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            send_error("Erreur de sécurité : Jeton CSRF invalide ou manquant.", 403);
        }
    }
}

/**
 * Protection contre les accès non autorisés (vérification du rôle).
 */
function check_role(string $required_role): void {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role || !isset($_SESSION['auth_token'])) {
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
            http_response_code(401);
            echo json_encode(['error' => 'Accès non autorisé. Veuillez vous connecter.']);
            exit();
        } else {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header("Location: " . PROJECT_PATH . "auth/login.php?role=" . urlencode($required_role));
            exit();
        }
    }
}
?>
