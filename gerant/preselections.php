<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION['id'];

$sql = "
    SELECT
        c.id AS candidature_id,
        c.date_candidature,
        u.nom AS candidat_nom,
        u.email AS candidat_email,
        co.id AS offre_id,
        co.titre AS offre_titre
    FROM candidatures c
    JOIN utilisateurs u ON c.id_candidat = u.id
    JOIN offres co ON c.id_offre = co.id
    WHERE co.id_gerant = :id_gerant
      AND c.statut = 'validee'
";
$params = [':id_gerant' => $id_gerant];

if (!empty($_GET['id_offre'])) {
    $sql .= " AND co.id = :id_offre";
    $params[':id_offre'] = (int)$_GET['id_offre'];
}
$sql .= " ORDER BY co.titre ASC, u.nom ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $preselections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les dates
    foreach ($preselections as &$pre) {
        $pre['date_candidature_fmt'] = format_date($pre['date_candidature'], true);
    }

    // Récupérer la liste des offre pour les filtres
    $stmt_offre = $pdo->prepare("SELECT id, titre FROM offres WHERE id_gerant = ? ORDER BY titre");
    $stmt_offre->execute([$id_gerant]);
    $liste_offre = $stmt_offre->fetchAll(PDO::FETCH_ASSOC);

    send_json([
        'success' => true,
        'count' => count($preselections),
        'preselections' => $preselections,
        'liste_offre' => $liste_offre,
        'filters' => [
            'id_offre' => $_GET['id_offre'] ?? ''
        ]
    ]);
} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}
exit();
