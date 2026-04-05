<?php
// config/session.php

// Buffet de sortie pour éviter "headers already sent"
if (ob_get_level() === 0) ob_start();

// Paramètres de session stricts avant le démarrage
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

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
            http_response_code(403);
            echo json_encode(['error' => 'Token CSRF invalide ou manquant.']);
            exit();
        }
    }
}

/**
 * Protection contre les accès non autorisés (vérification du rôle).
 */
function check_role(string $required_role): void {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $required_role) {
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
            http_response_code(401);
            echo json_encode(['error' => 'Accès non autorisé. Veuillez vous connecter.']);
            exit();
        } else {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header("Location: /plateforme_recrutement/auth/login.php?role=" . urlencode($required_role));
            exit();
        }
    }
}
?>
