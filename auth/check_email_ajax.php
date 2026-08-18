<?php
require_once "../includes/layout.php";

header('Content-Type: application/json');

$email = trim($_GET['email'] ?? '');

if (empty($email)) {
    echo json_encode(['available' => true]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $exists = $stmt->fetch();

    echo json_encode(['available' => !$exists]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
