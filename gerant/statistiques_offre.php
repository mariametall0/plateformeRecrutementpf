<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];
$id_offre = (int)($_GET["id"] ?? 0);

if ($id_offre <= 0) {
    send_error("ID de offre invalide.");
}

try {
    // Vérifier appartenance et infos offre
    $stmt = $pdo->prepare("SELECT id, titre, date_creation FROM offres WHERE id = ? AND id_gerant = ?");
    $stmt->execute([$id_offre, $id_gerant]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offre) {
        send_error("Offre non trouvé.", 404);
    }

    // Statistiques par statut
    $stmt_stats = $pdo->prepare("
        SELECT statut, COUNT(*) as nb 
        FROM candidatures 
        WHERE id_offre = ? 
        GROUP BY statut
    ");
    $stmt_stats->execute([$id_offre]);
    $stats_brutes = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = ['en_attente' => 0, 'validee' => 0, 'rejetee' => 0, 'total' => 0];
    foreach ($stats_brutes as $s) {
        $stats[$s['statut']] = (int)$s['nb'];
        $stats['total'] += (int)$s['nb'];
    }

    // Calculer taux de succès
    $taux_succes = $stats['total'] > 0 ? round(($stats['validee'] / $stats['total']) * 100, 1) : 0;

} catch (PDOException $e) {
    send_error("Erreur base de données.");
}

include_header("Statistiques du Offre");
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <header style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <a href="liste_offres.php" class="link">← Retour aux offre</a>
            <h1>Statistiques : <?php echo htmlspecialchars($offre['titre']); ?> 📊</h1>
            <p style="color: var(--text-muted);">Analyse détaillée des performances du offre.</p>
        </div>
    </header>

    <div class="dashboard-grid">
        <div class="stat-card" style="border-top: 5px solid var(--primary);">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Candidatures totales</div>
        </div>
        <div class="stat-card" style="border-top: 5px solid var(--warning);">
            <div class="stat-value" style="color: var(--warning);"><?php echo $stats['en_attente']; ?></div>
            <div class="stat-label">En attente d'étude</div>
        </div>
        <div class="stat-card" style="border-top: 5px solid var(--success);">
            <div class="stat-value" style="color: var(--success);"><?php echo $stats['validee']; ?></div>
            <div class="stat-label">Candidats présélectionnés</div>
        </div>
        <div class="stat-card" style="border-top: 5px solid var(--primary);">
            <div class="stat-value" style="color: var(--primary);"><?php echo $taux_succes; ?>%</div>
            <div class="stat-label">Taux de présélection</div>
        </div>
    </div>

    <div style="margin-top: 3rem;" class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
        <div class="stat-card">
            <h3>Visualisation de la répartition</h3>
            <div style="margin-top: 2rem;">
                <?php if ($stats['total'] > 0): ?>
                    <div style="display: flex; height: 30px; border-radius: 15px; overflow: hidden; background: var(--border);">
                        <div style="width: <?php echo ($stats['validee']/$stats['total'])*100; ?>%; background: var(--success);" title="Validées"></div>
                        <div style="width: <?php echo ($stats['en_attente']/$stats['total'])*100; ?>%; background: var(--warning);" title="En attente"></div>
                        <div style="width: <?php echo ($stats['rejetee']/$stats['total'])*100; ?>%; background: var(--danger);" title="Rejetées"></div>
                    </div>
                    <div style="margin-top: 1rem; display: flex; justify-content: center; gap: 1.5rem; font-size: 0.8rem;">
                        <span style="display: flex; align-items: center; gap: 0.4rem;"><span style="width:10px; height:10px; background:var(--success); border-radius:50%;"></span> Validées</span>
                        <span style="display: flex; align-items: center; gap: 0.4rem;"><span style="width:10px; height:10px; background:var(--warning); border-radius:50%;"></span> En attente</span>
                        <span style="display: flex; align-items: center; gap: 0.4rem;"><span style="width:10px; height:10px; background:var(--danger); border-radius:50%;"></span> Rejetées</span>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-muted); padding: 2rem;">Aucune donnée à afficher.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="stat-card">
            <h3>Détails temporels</h3>
            <p style="margin-top: 1rem; font-size: 0.875rem; color: var(--text-main);">
                Offre créé le : <strong><?php echo format_date($offre['date_creation'], true); ?></strong>
            </p>
            <p style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-main);">
                Moyenne par jour : <strong><?php echo $stats['total'] > 0 ? round($stats['total'] / max(1, (time() - strtotime($offre['date_creation'])) / 86400), 2) : 0; ?></strong> candidatures
            </p>
        </div>
    </div>
</div>

<?php 
include_footer();
exit();
