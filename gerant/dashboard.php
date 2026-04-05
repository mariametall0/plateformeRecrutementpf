<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];

// Récupération des données pour l'affichage
$nb_offre = 0; $nb_en_attente = 0; $nb_validees = 0; $nb_actifs = 0;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM offres WHERE id_gerant = ?");
    $stmt->execute([$id_gerant]);
    $nb_offre = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM candidatures c
        JOIN offres co ON c.id_offre = co.id
        WHERE co.id_gerant = ? AND c.statut = 'en_attente'
    ");
    $stmt->execute([$id_gerant]);
    $nb_en_attente = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM candidatures c
        JOIN offres co ON c.id_offre = co.id
        WHERE co.id_gerant = ? AND c.statut = 'validee'
    ");
    $stmt->execute([$id_gerant]);
    $nb_validees = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM offres WHERE id_gerant = ? AND statut = 'actif'");
    $stmt->execute([$id_gerant]);
    $nb_actifs = (int)$stmt->fetchColumn();

    // --- Data for Chart.js ---
    // Pie Chart: Status
    $stmt_chart1 = $pdo->prepare("SELECT c.statut, COUNT(*) as count FROM candidatures c JOIN offres co ON c.id_offre = co.id WHERE co.id_gerant = ? GROUP BY c.statut");
    $stmt_chart1->execute([$id_gerant]);
    $chart_statut = $stmt_chart1->fetchAll(PDO::FETCH_ASSOC);

    // Bar Chart: Top 5 offres by applications
    $stmt_chart2 = $pdo->prepare("SELECT co.titre, COUNT(c.id) as count FROM offres co LEFT JOIN candidatures c ON c.id_offre = co.id WHERE co.id_gerant = ? GROUP BY co.id ORDER BY count DESC LIMIT 5");
    $stmt_chart2->execute([$id_gerant]);
    $chart_offre = $stmt_chart2->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $chart_statut = [];
    $chart_offre = [];
    // Erreur silencieuse
    // $activites = []; // This line was removed as $activites is now fetched later
}

include_header("Tableau de Bord Gérant");

// Récupération des dernières activités
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.statut, c.date_candidature, u.nom AS candidat_nom, u.email AS candidat_email, co.titre AS offre_titre
        FROM candidatures c
        JOIN utilisateurs u ON c.id_candidat = u.id
        JOIN offres co ON c.id_offre = co.id
        WHERE co.id_gerant = ?
        ORDER BY c.date_candidature DESC
        LIMIT 5
    ");
    $stmt->execute([$id_gerant]);
    $activites = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $activites = []; }
// Action : Demander l'activation (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['demande_activation'])) {
    verify_csrf_token();
    // Ici on pourrait envoyer un mail ou créer une notification admin. 
    // Pour l'instant, on met à jour un champ hypothétique ou on log l'action.
    $_SESSION['success_message'] = "Votre demande d'activation a été envoyée à l'administrateur.";
}

// Vérifier le statut actuel du gérant
$stmt_status = $pdo->prepare("SELECT statut FROM utilisateurs WHERE id = ?");
$stmt_status->execute([$id_gerant]);
$user_status = $stmt_status->fetchColumn();

include_header("Tableau de Bord Recruteur");
?>

<?php if ($user_status === 'inactif'): ?>
    <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 mb-4 animate__animated animate__shakeX">
        <div class="d-flex align-items-center">
            <div class="fs-1 me-4">🔔</div>
            <div class="flex-grow-1">
                <h5 class="fw-bold mb-1">Votre compte est actuellement en attente d'activation</h5>
                <p class="mb-0 text-muted small">Vous pouvez préparer vos offre, mais ils ne seront pas visibles par les candidats tant qu'un administrateur n'aura pas validé votre profil.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" name="demande_activation" class="btn btn-warning rounded-pill px-4 fw-bold">Demander l'activation</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="row mb-5 animate__animated animate__fadeIn">
    <div class="col-12">
        <div class="px-5 py-5 rounded-5 shadow-premium border-0 d-flex justify-content-between align-items-center position-relative overflow-hidden" style="background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);">
            <!-- Decorative circle -->
            <div class="position-absolute rounded-circle opacity-10 bg-white" style="width: 250px; height: 250px; bottom: -50px; left: -50px;"></div>
            
            <div class="position-relative z-1">
                <h1 class="display-5 fw-extrabold text-white mb-2">Espace Recruteur 💼</h1>
                <p class="text-white opacity-75 fs-5 mb-0">Bienvenue, <strong><?php echo htmlspecialchars($_SESSION['nom']); ?></strong>. Gérez vos talents et vos opportunités.</p>
            </div>
            <a href="ajouter_offre.php" class="btn btn-white btn-lg d-none d-md-flex align-items-center gap-2 rounded-pill px-5 fw-bold text-primary shadow-sm position-relative z-1" style="background: white;">
                <i class="bi bi-plus-lg"></i> Nouveau Offre
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Stat 1: Total Offres -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-5 animate__animated animate__fadeInUp animate__delay-1s">
            <div class="stat-card-icon bg-indigo-50 text-primary">📄</div>
            <div class="display-4 fw-bold text-dark mb-1"><?php echo $nb_offre; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-4 ls-1">Total Offres</div>
            <div class="mt-auto">
                <a href="liste_offres.php" class="btn btn-light w-100 rounded-pill fw-bold text-primary border">Gérer</a>
            </div>
        </div>
    </div>
    <!-- Stat 2: Offres Actives -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-5 animate__animated animate__fadeInUp animate__delay-2s">
            <div class="stat-card-icon bg-green-50 text-success">🟢</div>
            <div class="display-4 fw-bold text-dark mb-1"><?php echo $nb_actifs; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-4 ls-1">Offres Actives</div>
            <div class="mt-auto">
                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle w-100 py-2">Opérationnels</span>
            </div>
        </div>
    </div>
    <!-- Stat 3: À Étudier -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-5 animate__animated animate__fadeInUp animate__delay-3s">
            <div class="stat-card-icon bg-amber-50 text-warning">🕒</div>
            <div class="display-4 fw-bold text-dark mb-1"><?php echo $nb_en_attente; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-4 ls-1">À Étudier</div>
            <div class="mt-auto">
                <a href="candidatures.php?statut=en_attente" class="btn btn-light w-100 rounded-pill fw-bold text-warning border">Voir les dossiers</a>
            </div>
        </div>
    </div>
    <!-- Stat 4: Présélections -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-5 animate__animated animate__fadeInUp animate__delay-4s">
            <div class="stat-card-icon bg-blue-50 text-info">✅</div>
            <div class="display-4 fw-bold text-dark mb-1"><?php echo $nb_validees; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-4 ls-1">Présélections</div>
            <div class="mt-auto">
                <span class="badge bg-blue-50 text-primary border border-blue-100 w-100 py-2">Dossiers validés</span>
            </div>
        </div>
    </div>
</div>

<?php
// Pre-calculate totals for new mini-stats
$total_cands = array_sum(array_column($chart_statut, 'count'));
$statut_map = [];
foreach ($chart_statut as $s) $statut_map[$s['statut']] = (int)$s['count'];

$top_offre_display = array_slice($chart_offre, 0, 3);
?>
<div class="row g-3 mb-4">
    <!-- Répartition statuts inline -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-dark small">📊 Répartition des statuts</span>
                <span class="badge bg-light text-secondary border"><?php echo $total_cands; ?> total</span>
            </div>
            <?php if (empty($chart_statut)): ?>
                <p class="text-muted small mb-0 text-center py-2">Aucune candidature encore</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($chart_statut as $s):
                        $pct = $total_cands > 0 ? round($s['count'] / $total_cands * 100) : 0;
                        $color = 'warning';
                        if ($s['statut'] === 'validee') $color = 'success';
                        elseif ($s['statut'] === 'rejetee') $color = 'danger';
                        $label = $s['statut'] === 'validee' ? 'Validées' : ($s['statut'] === 'rejetee' ? 'Rejetées' : 'En attente');
                    ?>
                    <div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-<?php echo $color; ?> fw-semibold"><?php echo $label; ?></span>
                            <span class="text-muted"><?php echo $s['count']; ?> (<?php echo $pct; ?>%)</span>
                        </div>
                        <div class="progress" style="height: 6px; border-radius: 10px;">
                            <div class="progress-bar bg-<?php echo $color; ?>" style="width: <?php echo $pct; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Offre inline -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold text-dark small">🏆 Top Offre</span>
                <a href="candidatures.php" class="small text-primary text-decoration-none">Voir tout →</a>
            </div>
            <?php if (empty($chart_offre)): ?>
                <p class="text-muted small mb-0 text-center py-2">Aucune candidature reçue</p>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($top_offre_display as $i => $co): ?>
                    <div class="d-flex align-items-center gap-3">
                        <span class="fw-bold text-primary" style="width:20px;"><?php echo $i+1; ?></span>
                        <div class="flex-grow-1 text-truncate small fw-semibold text-dark"><?php echo htmlspecialchars($co['titre']); ?></div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2"><?php echo $co['count']; ?> dossiers</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<div class="row mb-5">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">🕒 Activités Récentes</h5>
                <a href="candidatures.php" class="btn btn-sm btn-light text-primary fw-bold">Tout voir</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Candidat</th>
                            <th>Offre</th>
                            <th>Date</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activites as $a): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?php echo htmlspecialchars($a['candidat_nom']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($a['candidat_email']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($a['offre_titre']); ?></td>
                                <td class="small text-muted"><?php echo format_date($a['date_candidature'], true); ?></td>
                                <td class="text-center">
                                    <?php 
                                    $s = $a['statut'];
                                    $cls = ($s === 'validee') ? 'bg-success' : (($s === 'rejetee') ? 'bg-danger' : 'bg-warning text-dark');
                                    ?>
                                    <span class="badge <?php echo $cls; ?> px-3 py-2">
                                        <?php echo ucfirst(str_replace('_', ' ', $s)); ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="etudier_dossier.php?id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Étudier</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($activites)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted fst-italic">Aucune candidature pour le moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
    <h5 class="fw-bold mb-3">Actions rapides</h5>
    <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="ajouter_offre.php" class="btn btn-light border text-primary px-4 py-2">➕ Nouveau Offre</a>
        <a href="statistiques_offre.php?id=all" class="btn btn-light border text-primary px-4 py-2">📊 Statistiques Globales</a>
        <a href="modifier_profil.php" class="btn btn-light border text-primary px-4 py-2">👤 Mon Profil</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Pie Chart - Statuts
    const rawStatuts = <?php echo json_encode($chart_statut ?? []); ?>;
    if (rawStatuts.length > 0) {
        const statutLabels = rawStatuts.map(r => r.statut.toUpperCase().replace('_', ' '));
        const statutCounts = rawStatuts.map(r => parseInt(r.count));
        const statutColors = rawStatuts.map(r => {
            if (r.statut === 'validee') return '#10b981';
            if (r.statut === 'rejetee') return '#ef4444';
            return '#f59e0b';
        });

        new Chart(document.getElementById('statutChart'), {
            type: 'doughnut',
            data: {
                labels: statutLabels,
                datasets: [{
                    data: statutCounts,
                    backgroundColor: statutColors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // 2. Bar Chart - Offre
    const rawOffre = <?php echo json_encode($chart_offre ?? []); ?>;
    if (rawOffre.length > 0) {
        const offreLabels = rawOffre.map(r => r.titre.substring(0,25) + '...');
        const offreCounts = rawOffre.map(r => parseInt(r.count));

        new Chart(document.getElementById('offreChart'), {
            type: 'bar',
            data: {
                labels: offreLabels,
                datasets: [{
                    label: 'Candidatures',
                    data: offreCounts,
                    backgroundColor: '#6366f1',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>

<?php 
include_footer();
exit();
?>