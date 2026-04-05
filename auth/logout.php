<?php
require_once "../includes/layout.php";

session_unset();
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time() - 42000, '/');
}

session_destroy();

if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    send_json(['success' => true, 'message' => 'Déconnexion réussie.']);
} else {
    header("Location: ../index.php");
}
exit();
?>