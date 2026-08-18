<?php
declare(strict_types=1);

/**
 * dashboard.php – Tableau de bord de l'administrateur système.
 */
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

$id_admin = (int)$_SESSION["id"];

// Statistiques Centralisées
$stats = [
    'gerants' => ['total' => 0, 'actifs' => 0],
    'candidats' => 0,
    'opportunites' => 0,
    'candidatures' => 0
];
$chart_roles = [];
$chart_dates = [];
$chart_top_opps = [];

try {
    // Optimisation SQL : Agrégation globale en une seule requête
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM utilisateurs WHERE role = 'gerant') as total_gerants,
            (SELECT COUNT(*) FROM utilisateurs WHERE role = 'gerant' AND statut = 'actif') as actifs_gerants,
            (SELECT COUNT(*) FROM utilisateurs WHERE role = 'candidat') as total_candidats,
            (SELECT COUNT(*) FROM concours) as total_offres,
            (SELECT COUNT(*) FROM candidatures) as total_candidatures
    ");
    $stmt->execute();
    $res = $stmt->fetch();
    if ($res) {
        $stats = [
            'gerants' => ['total' => (int)$res['total_gerants'], 'actifs' => (int)$res['actifs_gerants']],
            'candidats' => (int)$res['total_candidats'],
            'opportunites' => (int)$res['total_offres'],
            'candidatures' => (int)$res['total_candidatures']
        ];
    }

    // Répartition par rôle pour le graphique
    $stmt_roles = $pdo->prepare("SELECT role, COUNT(*) as count FROM utilisateurs GROUP BY role");
    $stmt_roles->execute();
    $chart_roles = $stmt_roles->fetchAll();

    // Activité récente (10 derniers jours)
    $stmt_dates = $pdo->prepare("
        SELECT DATE(date_candidature) as date, COUNT(*) as count 
        FROM candidatures 
        GROUP BY DATE(date_candidature) 
        ORDER BY date DESC 
        LIMIT 10
    ");
    $stmt_dates->execute();
    $chart_dates = array_reverse($stmt_dates->fetchAll(PDO::FETCH_ASSOC));

    // Top 5 opportunités les plus demandées
    $stmt_top = $pdo->prepare("
        SELECT co.titre, COUNT(c.id) as total_candidatures
        FROM concours co
        LEFT JOIN candidatures c ON co.id = c.id_concours
        GROUP BY co.id
        ORDER BY total_candidatures DESC
        LIMIT 5
    ");
    $stmt_top->execute();
    $chart_top_opps = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
}

include_header("Administration Admissio");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <!-- Hero Section -->
    <div class="recap-panel anim-up mb-5">
        <div class="row align-items-center p-2 p-lg-4">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-8 fw-bold"><?php echo __t('PORTAIL ADMINISTRATION'); ?></span>
                    <span class="text-muted small fw-bold"><i class="bi bi-circle-fill text-success me-1"></i> <?php echo __t('active_system'); ?></span>
                </div>
                <h1 class="display-4 fw-black mb-3 text-gray-900"><?php echo __t('Centre de Contrôle'); ?> <span class="text-grad">Admissio</span></h1>
                <p class="fs-5 text-gray-600"><?php echo __t('Supervision globale et pilotage de l\'écosystème de recrutement.'); ?></p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <a href="maintenance.php" class="btn-pro btn-pro-primary px-5 py-3 shadow-md">
                    <i class="bi bi-cpu me-2"></i> <?php echo __t('MAINTENANCE SYSTÈME'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3 anim-up anim-delay-1">
            <div class="glass-premium p-4 h-100 border-0 shadow-premium">
                <div class="stat-icon-luminous mb-3 text-success"><i class="bi bi-building"></i></div>
                <div class="h2 fw-black mb-0 text-gray-900"><?php echo $stats['gerants']['total']; ?></div>
                <div class="small fw-bold text-muted text-uppercase mb-2"><?php echo __t('Recruteurs'); ?></div>
                <div class="small text-success fw-bold"><i class="bi bi-check2-circle me-1"></i> <?php echo $stats['gerants']['actifs']; ?> <?php echo __t('Actifs'); ?></div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 anim-up anim-delay-2">
            <div class="glass-premium p-4 h-100 border-0 shadow-premium">
                <div class="stat-icon-luminous mb-3 text-success"><i class="bi bi-people"></i></div>
                <div class="h2 fw-black mb-0 text-gray-900"><?php echo $stats['candidats']; ?></div>
                <div class="small fw-bold text-muted text-uppercase mb-2"><?php echo __t('Candidats'); ?></div>
                <div class="small text-muted"><?php echo __t('Base de talents unifiée'); ?></div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 anim-up anim-delay-3">
            <div class="glass-premium p-4 h-100 border-0 shadow-premium">
                <div class="stat-icon-luminous mb-3 text-info"><i class="bi bi-briefcase"></i></div>
                <div class="h2 fw-black mb-0 text-gray-900"><?php echo $stats['opportunites']; ?></div>
                <div class="small fw-bold text-muted text-uppercase mb-2"><?php echo __t('Opportunités'); ?></div>
                <div class="small text-muted"><?php echo __t('Postes publiés'); ?></div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3 anim-up anim-delay-4">
            <div class="glass-premium p-4 h-100 border-0 shadow-premium">
                <div class="stat-icon-luminous mb-3 text-warning"><i class="bi bi-file-earmark-check"></i></div>
                <div class="h2 fw-black mb-0 text-gray-900"><?php echo $stats['candidatures']; ?></div>
                <div class="small fw-bold text-muted text-uppercase mb-2"><?php echo __t('Dossiers'); ?></div>
                <div class="small text-muted"><?php echo __t('Candidatures reçues'); ?></div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row g-5 mb-5 reveal">
        <div class="col-lg-4">
            <div class="card border-0 shadow-premium p-4 rounded-4 bg-white h-100">
                <h5 class="fw-black mb-4"><i class="bi bi-pie-chart text-success me-2"></i><?php echo __t('Répartition Utilisateurs'); ?></h5>
                <div style="height: 300px;"><canvas id="rolesChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-premium p-4 rounded-4 bg-white h-100">
                <h5 class="fw-black mb-4"><i class="bi bi-graph-up text-success me-2"></i><?php echo __t('Activité (10 jours)'); ?></h5>
                <div style="height: 300px;"><canvas id="datesChart"></canvas></div>
            </div>
        </div>
        <div class="col-12 mt-4">
            <div class="card border-0 shadow-premium p-4 rounded-4 bg-white h-100">
                <h5 class="fw-black mb-4"><i class="bi bi-bar-chart-fill text-success me-2"></i><?php echo __t('Top 5 des Opportunités'); ?></h5>
                <div style="height: 350px;"><canvas id="topOppsChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Management Links -->
    <div class="row g-4 reveal">
        <div class="col-md-4">
            <a href="liste_gerants.php" class="quick-action-card p-4 h-100">
                <div class="icon-circle-box bg-light mb-3"><i class="bi bi-person-check"></i></div>
                <h6 class="fw-bold mb-2 text-gray-900"><?php echo __t('Validation Recruteurs'); ?></h6>
                <p class="text-muted small mb-0"><?php echo __t('Contrôlez les accès et les statuts des structures partenaires.'); ?></p>
            </a>
        </div>
        <div class="col-md-4">
            <a href="liste_opportunites.php" class="quick-action-card p-4 h-100">
                <div class="icon-circle-box bg-light mb-3"><i class="bi bi-journals"></i></div>
                <h6 class="fw-bold mb-2 text-gray-900"><?php echo __t('Catalogue National'); ?></h6>
                <p class="text-muted small mb-0"><?php echo __t('Supervision globale de toutes les opportunités actives.'); ?></p>
            </a>
        </div>
        <div class="col-md-4">
            <a href="sessions_concours.php" class="quick-action-card p-4 h-100">
                <div class="icon-circle-box bg-light mb-3"><i class="bi bi-calendar-event"></i></div>
                <h6 class="fw-bold mb-2 text-gray-900"><?php echo __t('Gestion des Sessions'); ?></h6>
                <p class="text-muted small mb-0"><?php echo __t('Planification et suivi des périodes de recrutement.'); ?></p>
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const rawRoles = <?php echo json_encode($chart_roles); ?>;
    if(rawRoles.length > 0) {
        new Chart(document.getElementById('rolesChart'), {
            type: 'doughnut',
            data: {
                labels: rawRoles.map(r => r.role.toUpperCase()),
                datasets: [{ 
                    data: rawRoles.map(r => r.count), 
                    backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6'],
                    borderWidth: 0
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                cutout: '75%', 
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } } 
            }
        });
    }

    const rawDates = <?php echo json_encode($chart_dates); ?>;
    if(rawDates.length > 0) {
        new Chart(document.getElementById('datesChart'), {
            type: 'line',
            data: {
                labels: rawDates.map(r => r.date),
                datasets: [{
                    label: 'Candidatures',
                    data: rawDates.map(r => r.count),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#2563eb'
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { display: false } }, 
                    x: { grid: { display: false } } 
                } 
            }
        });
    }

    const rawTopOpps = <?php echo json_encode($chart_top_opps); ?>;
    if(rawTopOpps.length > 0) {
        new Chart(document.getElementById('topOppsChart'), {
            type: 'bar',
            data: {
                labels: rawTopOpps.map(r => r.titre.length > 30 ? r.titre.substring(0,30) + '...' : r.titre),
                datasets: [{
                    label: 'Candidatures',
                    data: rawTopOpps.map(r => r.total_candidatures),
                    backgroundColor: 'rgba(25, 135, 84, 0.8)',
                    borderColor: '#198754',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }, 
                    x: { grid: { display: false } } 
                } 
            }
        });
    }
});
</script>

<?php 
include_footer();
?>

