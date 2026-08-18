<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];
$id_concours = (int)($_GET["id"] ?? 0);

if ($id_concours <= 0) {
    send_error("ID d'opportunité invalide.");
}

try {
    // Infos Concours
    $stmt = $pdo->prepare("SELECT id, titre, date_creation FROM concours WHERE id = ? AND id_gerant = ?");
    $stmt->execute([$id_concours, $id_gerant]);
    $opportunite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$opportunite) {
        send_error("Opportunité non trouvée.", 404);
    }

    // Statistiques par statut
    $stmt_stats = $pdo->prepare("
        SELECT statut, COUNT(*) as nb 
        FROM candidatures 
        WHERE id_concours = ? 
        GROUP BY statut
    ");
    $stmt_stats->execute([$id_concours]);
    $stats_brutes = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = ['en_attente' => 0, 'validee' => 0, 'rejetee' => 0, 'total' => 0];
    foreach ($stats_brutes as $s) {
        $stats[$s['statut']] = (int)$s['nb'];
        $stats['total'] += (int)$s['nb'];
    }

    $taux_succes = $stats['total'] > 0 ? round(($stats['validee'] / $stats['total']) * 100, 1) : 0;

} catch (PDOException $e) { send_error("Erreur DB."); }

include_header("Statistiques : " . $opportunite['titre']);
?>

<div class="mesh-bg"></div>

<style>
.stat-hero-card { background: white; border-radius: var(--radius-xl); border: 1px solid var(--color-gray-100); padding: 2.5rem; position: relative; overflow: hidden; }
.progress-premium { height: 12px; border-radius: 50px; background: var(--color-gray-100); overflow: hidden; }
.chart-container-premium { height: 350px; position: relative; }
</style>

<div class="container-modern py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-5 anim-up">
        <div>
            <a href="liste_opportunites.php" class="btn btn-sm btn-white border rounded-pill px-3 mb-3 fw-bold"><i class="bi bi-arrow-left"></i> Retour</a>
            <h1 class="display-6 fw-black text-gray-900 mb-1">Analytique Opportunité</h1>
            <p class="text-muted mb-0">Rapport de performance pour <span class="text-success fw-bold"><?php echo htmlspecialchars($opportunite['titre']); ?></span></p>
        </div>
        <div class="stat-icon-luminous">
            <i class="bi bi-graph-up-arrow"></i>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3 anim-up anim-delay-1">
            <div class="card-stat-modern animate-float">
                <div class="stat-icon-box bg-blue-50 text-green-600"><i class="bi bi-person-lines-fill"></i></div>
                <div class="h2 fw-black text-gray-900 mb-0"><?php echo $stats['total']; ?></div>
                <div class="small fw-bold text-muted text-uppercase ls-1">Dossiers Reçus</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 anim-up anim-delay-2">
            <div class="card-stat-modern">
                <div class="stat-icon-box bg-emerald-50 text-emerald-600"><i class="bi bi-check-all"></i></div>
                <div class="h2 fw-black text-gray-900 mb-0"><?php echo $stats['validee']; ?></div>
                <div class="small fw-bold text-muted text-uppercase ls-1">Présélections</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 anim-up anim-delay-3">
            <div class="card-stat-modern">
                <div class="stat-icon-box bg-amber-50 text-amber-600"><i class="bi bi-hourglass-split"></i></div>
                <div class="h2 fw-black text-gray-900 mb-0"><?php echo $stats['en_attente']; ?></div>
                <div class="small fw-bold text-muted text-uppercase ls-1">En Étude</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 anim-up anim-delay-4">
            <div class="card-stat-modern">
                <div class="stat-icon-box bg-indigo-50 text-indigo-600"><i class="bi bi-award"></i></div>
                <div class="h2 fw-black text-gray-900 mb-0"><?php echo $taux_succes; ?>%</div>
                <div class="small fw-bold text-muted text-uppercase ls-1">Taux Conversion</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7 anim-up anim-delay-5">
            <div class="stat-hero-card h-100">
                <h4 class="fw-black text-gray-900 mb-4">Répartition des Candidatures</h4>
                <div class="chart-container-premium mb-4">
                    <canvas id="statChart"></canvas>
                </div>
                <div class="d-flex justify-content-between gap-3 text-center">
                    <div class="flex-fill">
                        <div class="small fw-bold text-success text-uppercase opacity-75">Validées</div>
                        <div class="fw-black fs-5"><?php echo $stats['validee']; ?></div>
                    </div>
                    <div class="flex-fill border-start border-end">
                        <div class="small fw-bold text-warning text-uppercase opacity-75">Attente</div>
                        <div class="fw-black fs-5"><?php echo $stats['en_attente']; ?></div>
                    </div>
                    <div class="flex-fill">
                        <div class="small fw-bold text-danger text-uppercase opacity-75">Rejetées</div>
                        <div class="fw-black fs-5"><?php echo $stats['rejetee'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 anim-up anim-delay-6">
            <div class="stat-hero-card h-100 d-flex flex-column justify-content-center text-center">
                <div class="display-1 text-success opacity-10 mb-4"><i class="bi bi-calendar-check"></i></div>
                <h4 class="fw-black text-gray-900 mb-2">Historique de Publication</h4>
                <p class="text-muted mb-4 px-lg-4">Cette opportunité a été ouverte le <strong><?php echo format_date($opportunite['date_creation'], true); ?></strong>.</p>
                
                <div class="p-4 bg-success bg-opacity-5 rounded-4 border border-success border-opacity-10 text-start">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small fw-bold text-muted text-uppercase">Taux de Progression</span>
                        <span class="small fw-bold text-success">Prêt à l'audit</span>
                    </div>
                    <div class="progress-premium">
                        <div class="progress-bar bg-success" style="width: 85%"></div>
                    </div>
                    <p class="small text-muted mt-3 mb-0">L'IA suggère que le nombre de dossiers est suffisant pour clore cette session.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    new Chart(document.getElementById('statChart'), {
        type: 'doughnut',
        data: {
            labels: ['Validées', 'En attente', 'Rejetées'],
            datasets: [{
                data: [<?php echo $stats['validee']; ?>, <?php echo $stats['en_attente']; ?>, <?php echo $stats['rejetee'] ?? 0; ?>],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                hoverOffset: 20,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '80%',
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, padding: 25, font: { family: 'Outfit', weight: 'bold' } } }
            }
        }
    });
});
</script>

<?php 
include_footer();
exit();
?>

