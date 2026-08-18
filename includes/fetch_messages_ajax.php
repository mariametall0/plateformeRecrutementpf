<?php
require_once "../config/session.php";
require_once "../config/database.php";
header('Content-Type: application/json');

$id_candidature = (int)($_GET['id'] ?? 0);
if ($id_candidature <= 0) exit(json_encode(['error' => 'ID invalide']));

$user_id = $_SESSION['id'];
$role = $_SESSION['role'];

// Vérifier accès
if ($role === 'candidat') {
    $stmt = $pdo->prepare("SELECT id FROM candidatures WHERE id = ? AND id_candidat = ?");
    $stmt->execute([$id_candidature, $user_id]);
} else {
    $stmt = $pdo->prepare("SELECT c.id FROM candidatures c JOIN concours co ON c.id_concours = co.id WHERE c.id = ? AND co.id_gerant = ?");
    $stmt->execute([$id_candidature, $user_id]);
}

if (!$stmt->fetch()) exit(json_encode(['error' => 'Accès refusé']));

// Marquer comme lus les messages de l'autre partie
$other_role = ($role === 'candidat') ? 'gerant' : 'candidat';
$pdo->prepare("UPDATE messages_internes SET is_read = 1 WHERE candidature_id = ? AND emetteur_role = ?")
    ->execute([$id_candidature, $other_role]);

// Récupérer messages
$msgs = $pdo->prepare("SELECT * FROM messages_internes WHERE candidature_id = ? ORDER BY date_envoi ASC");
$msgs->execute([$id_candidature]);
$messages = $msgs->fetchAll(PDO::FETCH_ASSOC);

// Formater pour le JS
foreach($messages as &$m) {
    $m['is_me'] = ($m['emetteur_role'] === $role);
    $m['time'] = date('H:i', strtotime($m['date_envoi']));
}

echo json_encode($messages);
