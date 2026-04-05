<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

// Statistiques pour l'affichage
try {
    $stats = [
        'gerants' => [
            'total' => (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'gerant'")->fetchColumn(),
            'actifs' => (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'gerant' AND statut = 'actif'")->fetchColumn()
        ],
        'candidats' => (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'candidat'")->fetchColumn(),
        'offre' => (int)$pdo->query("SELECT COUNT(*) FROM offres")->fetchColumn(),
        'candidatures' => (int)$pdo->query("SELECT COUNT(*) FROM candidatures")->fetchColumn(),
        'sessions' => [
            'actives' => (int)$pdo->query("SELECT COUNT(*) FROM sessions_offres WHERE statut = 'active'")->fetchColumn()
        ]
    ];

    $stmt_chart_roles = $pdo->query("SELECT role, COUNT(*) as count FROM utilisateurs GROUP BY role");
    $chart_roles = $stmt_chart_roles->fetchAll(PDO::FETCH_ASSOC);

    $stmt_chart_candidatures_date = $pdo->query("SELECT DATE(date_candidature) as date, COUNT(*) as count FROM candidatures GROUP BY DATE(date_candidature) ORDER BY date DESC LIMIT 10");
    $chart_dates = array_reverse($stmt_chart_candidatures_date->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    // Erreur silencieuse
}

include_header("Administration");
?>

<div class="row mb-5 animate__animated animate__fadeIn">
    <div class="col-12">
        <div class="px-5 py-5 rounded-5 shadow-premium border-0 d-flex justify-content-between align-items-center position-relative overflow-hidden" style="background: var(--primary-gradient);">
            <!-- Decorative circle -->
            <div class="position-absolute rounded-circle opacity-10 bg-white" style="width: 300px; height: 300px; top: -100px; right: -50px;"></div>
            
            <div class="position-relative z-1">
                <h1 class="display-5 fw-extrabold text-white mb-2">Dashboard Administratif 🛡️</h1>
                <p class="text-white opacity-75 fs-5 mb-0">Supervisez l'écosystème Admissio et gérez la santé du système en temps réel.</p>
            </div>
            <a href="maintenance.php" class="btn btn-light btn-lg d-none d-md-flex align-items-center gap-2 rounded-pill px-5 fw-bold text-primary shadow-sm position-relative z-1">
                <i class="bi bi-gear-wide-connected"></i> Maintenance
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Stat 1: Gérants -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-4 animate__animated animate__fadeInUp animate__delay-1s">
            <div class="stat-card-icon bg-indigo-50 text-primary">🏢</div>
            <div class="display-6 fw-bold text-dark mb-1"><?php echo $stats['gerants']['total']; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-3 ls-1">Gérants inscrits</div>
            <div class="badge bg-success bg-opacity-10 text-success border border-success-subtle mb-4 w-fit">
                <?php echo $stats['gerants']['actifs']; ?> Actifs
            </div>
            <div class="mt-auto">
                <a href="liste_gerants.php" class="btn btn-light w-100 rounded-pill fw-bold text-primary border">Gérer</a>
            </div>
        </div>
    </div>
    <!-- Stat 2: Candidats -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-4 animate__animated animate__fadeInUp animate__delay-2s">
            <div class="stat-card-icon bg-blue-50 text-blue-600">🎓</div>
            <div class="display-6 fw-bold text-dark mb-1"><?php echo $stats['candidats']; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-4 ls-1">Candidats actifs</div>
            <div class="mt-auto">
                <a href="gestion_utilisateurs.php?role=candidat" class="btn btn-light w-100 rounded-pill fw-bold text-primary border">Superviser</a>
            </div>
        </div>
    </div>
    <!-- Stat 3: Offre -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-4 animate__animated animate__fadeInUp animate__delay-3s">
            <div class="stat-card-icon bg-purple-50 text-purple-600">📄</div>
            <div class="display-6 fw-bold text-dark mb-1"><?php echo $stats['offre']; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-4 ls-1">Total Offre</div>
            <div class="mt-auto">
                <a href="liste_offres.php" class="btn btn-light w-100 rounded-pill fw-bold text-primary border">Voir tout</a>
            </div>
        </div>
    </div>
    <!-- Stat 4: Sessions -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-4 animate__animated animate__fadeInUp animate__delay-4s">
            <div class="stat-card-icon bg-amber-50 text-amber-600">📅</div>
            <div class="display-6 fw-bold text-dark mb-1"><?php echo $stats['sessions']['actives']; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-4 ls-1">Sessions Actives</div>
            <div class="mt-auto">
                <a href="sessions_offres.php" class="btn btn-light w-100 rounded-pill fw-bold text-primary border">Planifier</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <h5 class="fw-bold mb-4">Répartition des Rôles</h5>
            <canvas id="rolesChart" height="250"></canvas>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
            <h5 class="fw-bold mb-4">Croissance des Candidatures (10 jrs)</h5>
            <canvas id="datesChart" height="250"></canvas>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 transition-hover">
            <div class="display-4 text-primary mb-3">🏢</div>
            <h5 class="fw-bold">Demandes de Session</h5>
            <p class="text-muted small">Validez et planifiez les sessions de candidatures demandées par les gérants pour leurs offre.</p>
            <a href="demandes_session.php" class="btn btn-primary rounded-pill px-4 mt-auto">Ouvrir les demandes</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 transition-hover">
            <div class="display-4 text-danger mb-3">🛠️</div>
            <h5 class="fw-bold">Maintenance Système</h5>
            <p class="text-muted small">Purgez les documents obsolètes, gérez les logs et assurez-vous de la santé de la base de données.</p>
            <a href="maintenance.php" class="btn btn-outline-danger rounded-pill px-4 mt-auto">Panel de contrôle</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center h-100 transition-hover">
            <div class="display-4 text-dark mb-3">👤</div>
            <h5 class="fw-bold">Mon Profil Admin</h5>
            <p class="text-muted small">Gérez vos accès personnels et vos informations de contact administrateur.</p>
            <div class="d-flex justify-content-center gap-2 mt-auto">
                <a href="modifier_profil.php" class="btn btn-light border btn-sm px-3 rounded-pill">Profil</a>
                <a href="../auth/changer_mdp.php" class="btn btn-light border btn-sm px-3 rounded-pill">Mot de passe</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Roles
    const rawRoles = <?php echo json_encode($chart_roles ?? []); ?>;
    const roleLabels = rawRoles.map(r => r.role.toUpperCase());
    const roleCounts = rawRoles.map(r => parseInt(r.count));
    const roleColors = ['#f59e0b', '#3b82f6', '#10b981'];

    if(rawRoles.length > 0) {
        new Chart(document.getElementById('rolesChart'), {
            type: 'pie',
            data: {
                labels: roleLabels,
                datasets: [{ data: roleCounts, backgroundColor: roleColors, borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // 2. Dates
    const rawDates = <?php echo json_encode($chart_dates ?? []); ?>;
    const dateLabels = rawDates.map(r => r.date);
    const dateCounts = rawDates.map(r => parseInt(r.count));

    if(rawDates.length > 0) {
        new Chart(document.getElementById('datesChart'), {
            type: 'line',
            data: {
                labels: dateLabels,
                datasets: [{
                    label: 'Nouvelles candidatures',
                    data: dateCounts,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }
});
</script>

<?php 
include_footer();
exit();
?>
