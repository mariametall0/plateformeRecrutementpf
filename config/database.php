<?php
declare(strict_types=1);

// Détection de l'environnement (Local vs Production)
$is_local = (php_sapi_name() === 'cli' || ($_SERVER['SERVER_NAME'] ?? '') === 'localhost' || ($_SERVER['SERVER_ADDR'] ?? '') === '127.0.0.1');

if ($is_local) {
    // Configuration Locale (WAMP)
    $host = 'localhost';
    $dbname = 'plateforme_recrutement';
    $username = 'root';
    $password = '';
} else {
    // Configuration Production (InfinityFree)
    $host = 'sql208.infinityfree.com';
    $dbname = 'if0_41786272_recrutementplateformesite_db';
    $username = 'if0_41786272';
    $password = 'Mm43195103';
}

// Optionnel: Surclassement par variables d'environnement si présentes
$host = getenv('DB_HOST') ?: $host;
$dbname = getenv('DB_NAME') ?: $dbname;
$username = getenv('DB_USER') ?: $username;
$password = getenv('DB_PASS') ?: $password;

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Erreur de connexion à la base de données.");
}