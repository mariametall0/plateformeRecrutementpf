<?php
require_once "../config/database.php";
session_start();

// Protection gérant
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== 'gerant') {
    die("Accès refusé.");
}

$id_gerant = $_SESSION["id"];

$sql = "
    SELECT
        c.statut AS statut_candidature,
        c.date_candidature,
        u.nom AS candidat_nom,
        u.email AS candidat_email,
        u.telephone AS telephone,
        co.titre AS offre_titre
    FROM candidatures c
    JOIN utilisateurs u ON c.id_candidat = u.id
    JOIN offres co ON c.id_offre = co.id
    WHERE co.id_gerant = :id_gerant
";
$params = [':id_gerant' => $id_gerant];

if (!empty($_GET['id_offre'])) {
    $sql .= " AND co.id = :id_offre";
    $params[':id_offre'] = (int)$_GET['id_offre'];
}
if (!empty($_GET['search'])) {
    $sql .= " AND (u.nom LIKE :search OR u.email LIKE :search)";
    $params[':search'] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['statut'])) {
    $sql .= " AND c.statut = :statut";
    $params[':statut'] = $_GET['statut'];
}
$sql .= " ORDER BY c.date_candidature DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Forcer le téléchargement en CSV
    $filename = "export_candidats_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Output BOM pour compatibilité Excel UTF-8
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');

    // Headers colonnes
    fputcsv($output, ['Nom Candidat', 'Email', 'Téléphone', 'Offre', 'Statut', 'Date de Candidature'], ';');

    foreach ($candidatures as $cand) {
        $statut = 'En attente';
        if ($cand['statut_candidature'] === 'validee') $statut = 'Validée';
        if ($cand['statut_candidature'] === 'rejetee') $statut = 'Rejetée';

        fputcsv($output, [
            $cand['candidat_nom'],
            $cand['candidat_email'],
            $cand['telephone'] ?? '',
            $cand['offre_titre'],
            $statut,
            date('d/m/Y H:i', strtotime($cand['date_candidature']))
        ], ';');
    }
    
    fclose($output);
    exit();
} catch (PDOException $e) {
    die("Erreur d'exportation de la base de données.");
}
