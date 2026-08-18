<?php
require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$user_id = $_SESSION['id'];
$action = $_GET['action'] ?? '';

try {
    if ($action === 'count') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        echo json_encode(['count' => $stmt->fetchColumn()]);
    } 
    elseif ($action === 'list') {
        $stmt = $pdo->prepare("SELECT *, DATE_FORMAT(created_at, '%d/%m %H:%i') as date_creation FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['notifications' => $notifications]);
    }
    elseif ($action === 'latest') {
        $stmt = $pdo->prepare("SELECT content as message FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: ['message' => '']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
