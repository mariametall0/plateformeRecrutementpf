<?php
declare(strict_types=1);

/**
 * dashboard.php – Tableau de bord Gérant / Recruteur (Optimisé).
 */
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = (int)($_SESSION["id"] ?? 0);
$nom_gerant = (string)($_SESSION["nom"] ?? "Recruteur");

// Récupération des données
$stats = ['offres_total' => 0, 'actifs' => 0, 'en_attente' => 0, 'validees' => 0];
$activites = [];
$chart_statut = [];
$mes_opportunites = [];

try {
    // 1. Stats globales simplifiées
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM concours WHERE id_gerant = ?");
    $stmt->execute([$id_gerant]);
    $stats['offres_total'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM concours WHERE id_gerant = ? AND statut = 'actif'");
    $stmt->execute([$id_gerant]);
    $stats['actifs'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidatures c JOIN concours co ON c.id_concours = co.id WHERE co.id_gerant = ? AND c.statut = 'en_attente'");
    $stmt->execute([$id_gerant]);
    $stats['en_attente'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidatures c JOIN concours co ON c.id_concours = co.id WHERE co.id_gerant = ? AND c.statut = 'validee'");
    $stmt->execute([$id_gerant]);
    $stats['validees'] = (int)$stmt->fetchColumn();

    // 2. Chart data
    $stmt = $pdo->prepare("SELECT c.statut, COUNT(*) as count FROM candidatures c JOIN concours co ON c.id_concours = co.id WHERE co.id_gerant = ? GROUP BY c.statut");
    $stmt->execute([$id_gerant]);
    $chart_statut = $stmt->fetchAll();

    // 3. Mes Opportunités
    $stmt = $pdo->prepare("SELECT id, titre, statut, date_cloture FROM concours WHERE id_gerant = ? ORDER BY date_creation DESC LIMIT 5");
    $stmt->execute([$id_gerant]);
    $mes_opportunites = $stmt->fetchAll();

    // 4. Candidatures récentes avec notifications messages
    $stmt = $pdo->prepare("
        SELECT c.id, c.statut, u.nom as candidat_nom, co.titre as offre_titre,
               (SELECT COUNT(*) FROM messages_internes WHERE candidature_id = c.id AND emetteur_role = 'candidat' AND is_read = 0) as unread_count
        FROM candidatures c 
        JOIN utilisateurs u ON c.id_candidat = u.id 
        JOIN concours co ON c.id_concours = co.id 
        WHERE co.id_gerant = ? 
        ORDER BY c.date_candidature DESC LIMIT 5
    ");
    $stmt->execute([$id_gerant]);
    $activites = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = $e->getMessage();
}

include_header(__t('recruiter_dashboard'));
?>

<!-- Style spécifique au Dashboard -->
<style>
/* Unification styles similar to candidate pages */
.hero-banner {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}
.hero-banner:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    border-color: #0f5132;
}
.hero-banner::after {
    content: '';
    position: absolute; top: -50px; right: -50px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(15,81,50,0.02) 0%, transparent 70%);
    border-radius: 50%;
}

.kpi-tile {
    background: white;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    height: 100%;
    transition: all 0.3s ease;
}
.kpi-tile:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    border-color: #0f5132;
}

.icon-box-lg {
    width: 44px; height: 44px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    margin-bottom: 0.75rem;
    background: #f1f5f9 !important;
    color: #334155 !important;
}

.quick-action-link {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.85rem 1.25rem;
    background: #fff;
    border-radius: 12px;
    text-decoration: none;
    color: #475569;
    font-weight: 600;
    margin-bottom: 0.6rem;
    border: 1px solid rgba(0,0,0,0.08);
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    transition: all 0.3s ease;
}
.quick-action-link:hover {
    background: #fff;
    border-color: #0f5132;
    color: #0f5132;
    transform: translateX(4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
}

.card {
    border: 1px solid rgba(0,0,0,0.08) !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03) !important;
    background: #fff;
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06) !important;
    border-color: #0f5132 !important;
}
</style>

<div class="container-modern py-4">

    <!-- Top Banner -->
    <div class="hero-banner">
        <div class="row align-items-center position-relative" style="z-index: 10;">
            <div class="col-md-8">
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-bold mb-3"><?php echo __t('PORTAIL RECRUTEUR'); ?></span>
                <h1 class="display-5 fw-black text-gray-900 mb-2"><?php echo __t('welcome'); ?>, <span class="text-grad"><?php echo htmlspecialchars(explode(' ', $nom_gerant)[0]); ?></span>.</h1>
                <p class="text-muted fs-5 mb-0"><?php echo __t('Voici l\'aperçu de vos offres et candidatures reçues.'); ?></p>
            </div>
            <div class="col-md-4 text-md-end mt-4 mt-md-0 d-flex flex-column gap-2 align-items-md-end">
                <a href="ajouter_opportunite.php" class="btn-pill btn-pill-success px-4 py-3 fw-bold shadow-sm" style="font-size: 0.95rem; border-radius: 100px; display: inline-flex; align-items: center;">
                    <i class="bi bi-plus-circle-fill me-2"></i> <?php echo __t('Publier une Opportunité'); ?>
                </a>
                <a href="export_dashboard.php" target="_blank" class="btn-pill btn-pill-outline px-4 py-2 fw-bold" style="font-size: 0.85rem; width: max-content; border-radius: 100px; display: inline-flex; align-items: center;">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> <?php echo __t('Exporter le Rapport (PDF)'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="kpi-tile">
                <div class="icon-box-lg bg-light text-dark"><i class="bi bi-briefcase"></i></div>
                <div class="small fw-bold text-muted text-uppercase mb-1"><?php echo __t('Opportunités'); ?></div>
                <div class="h2 fw-black mb-0"><?php echo $stats['offres_total']; ?></div>
                <div class="mt-2 small text-success fw-bold"><i class="bi bi-patch-check-fill me-1"></i><?php echo $stats['actifs']; ?> <?php echo __t('Actifs'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-tile">
                <div class="icon-box-lg bg-light text-dark"><i class="bi bi-people"></i></div>
                <div class="small fw-bold text-muted text-uppercase mb-1"><?php echo __t('Candidats'); ?></div>
                <div class="h2 fw-black mb-0"><?php echo ($stats['en_attente'] + $stats['validees']); ?></div>
                <div class="mt-2 small text-muted"><?php echo __t('Dossiers reçus'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-tile">
                <div class="icon-box-lg bg-light text-dark"><i class="bi bi-hourglass-top"></i></div>
                <div class="small fw-bold text-muted text-uppercase mb-1"><?php echo __t('En Attente'); ?></div>
                <div class="h2 fw-black mb-0"><?php echo $stats['en_attente']; ?></div>
                <div class="mt-2 small text-warning fw-bold"><?php echo __t('Action requise'); ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-tile">
                <div class="icon-box-lg bg-light text-dark"><i class="bi bi-check2-all"></i></div>
                <div class="small fw-bold text-muted text-uppercase mb-1"><?php echo __t('Validées'); ?></div>
                <div class="h2 fw-black mb-0"><?php echo $stats['validees']; ?></div>
                <div class="mt-2 small text-success fw-bold"><?php echo __t('Prêt pour entretien'); ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Area (Left Column) -->
        <div class="col-lg-8">
            
            <!-- Mes Opportunités -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="p-4 d-flex justify-content-between align-items-center border-bottom bg-white">
                    <h5 class="fw-bold mb-0"><?php echo __t('Mes Opportunités Actives'); ?></h5>
                    <a href="liste_opportunites.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold"><?php echo __t('Gérer'); ?></a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b;"><?php echo __t('Titre'); ?></th>
                                <th class="text-center" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b;"><?php echo __t('Statut'); ?></th>
                                <th class="text-end pe-4" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b;"><?php echo __t('Action'); ?></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach($mes_opportunites as $opp): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-gray-900"><?php echo htmlspecialchars((string)$opp['titre']); ?></div>
                                    <div class="small text-muted"><?php echo __t('closing_date'); ?> : <?php echo date('d/m/Y', strtotime($opp['date_cloture'])); ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill px-3 py-2 <?php echo $opp['statut']==='actif'?'bg-success bg-opacity-10 text-success':'bg-secondary bg-opacity-10 text-secondary'; ?> fw-bold" style="font-size: 0.65rem;">
                                        <?php echo __t($opp['statut']); ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="formulaire_opportunite.php?id=<?php echo $opp['id']; ?>" class="btn btn-sm btn-white border rounded-pill px-3 fw-bold"><?php echo __t('Éditer Form.'); ?></a>
                                </td>
                            </tr>
                            <?php endforeach; if(empty($mes_opportunites)): ?>
                            <tr><td colspan="3" class="text-center py-5 text-muted"><?php echo __t('no_opportunity_found'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Candidatures Récentes -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="p-4 d-flex justify-content-between align-items-center border-bottom bg-white">
                    <h5 class="fw-bold mb-0"><?php echo __t('Candidatures Reçues'); ?></h5>
                    <a href="candidatures.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold"><?php echo __t('Voir tout'); ?></a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b;"><?php echo __t('Candidat'); ?></th>
                                <th style="font-size: 0.75rem; text-transform: uppercase; color: #64748b;"><?php echo __t('Opportunité'); ?></th>
                                <th class="text-end pe-4" style="font-size: 0.75rem; text-transform: uppercase; color: #64748b;"><?php echo __t('Action'); ?></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach($activites as $a): ?>
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="fw-bold text-gray-900"><?php echo htmlspecialchars((string)$a['candidat_nom']); ?></div>
                                    <span class="badge rounded-pill bg-warning bg-opacity-10 text-warning fw-bold" style="font-size: 0.6rem;"><?php echo strtoupper($a['statut']); ?></span>
                                </td>
                                <td><div class="small fw-semibold text-muted"><?php echo htmlspecialchars((string)$a['offre_titre']); ?></div></td>
                                <td class="text-end pe-4">
                                    <a href="etudier_dossier.php?id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold position-relative">
                                        <?php echo __t('Étudier'); ?>

                                        <?php if (!empty($a['unread_count'])): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; z-index: 5;">
                                                <?php echo $a['unread_count']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; if(empty($activites)): ?>
                            <tr><td colspan="3" class="text-center py-5 text-muted"><?php echo __t('En attente de nouvelles candidatures...'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Sidebar / Tools (Right Column) -->
        <div class="col-lg-4">
            <!-- Agenda des Entretiens -->
            <?php
            $stmt_next = $pdo->prepare("
                SELECT e.*, u.nom as candidat_nom, co.titre as concours_titre 
                FROM entretiens e 
                JOIN candidatures c ON e.id_candidature = c.id 
                JOIN utilisateurs u ON c.id_candidat = u.id 
                JOIN concours co ON c.id_concours = co.id 
                WHERE co.id_gerant = ? AND e.date_entrevue >= CURDATE() 
                ORDER BY e.date_entrevue ASC LIMIT 3
            ");
            $stmt_next->execute([$id_gerant]);
            $next_entretiens = $stmt_next->fetchAll();
            ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
                <div class="p-4 d-flex justify-content-between align-items-center border-bottom bg-white">
                    <h5 class="fw-bold mb-0"><?php echo __t('Agenda des Entretiens'); ?></h5>
                    <a href="liste_entretiens.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold"><?php echo __t('Calendrier'); ?></a>
                </div>
                <div class="p-4 d-flex flex-column gap-3">
                    <?php foreach($next_entretiens as $ne): 
                        $timestamp = strtotime($ne['date_entrevue']);
                        $jour = date('d', $timestamp);
                        $mois_en = date('M', $timestamp);
                        $mois_fr_map = [
                            'Jan' => 'Janv.', 'Feb' => 'Févr.', 'Mar' => 'Mars', 'Apr' => 'Avr.', 
                            'May' => 'Mai', 'Jun' => 'Juin', 'Jul' => 'Juil.', 'Aug' => 'Août', 
                            'Sep' => 'Sept.', 'Oct' => 'Oct.', 'Nov' => 'Nov.', 'Dec' => 'Déc.'
                        ];
                        $mois = $mois_fr_map[$mois_en] ?? $mois_en;
                    ?>
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 border bg-light bg-opacity-50">
                        <div class="text-center bg-white border rounded px-2 py-1" style="min-width: 60px;">
                            <div class="small fw-bold text-success text-uppercase" style="font-size: 0.7rem;"><?php echo $mois; ?></div>
                            <div class="h4 fw-black mb-0 text-dark"><?php echo $jour; ?></div>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-bold text-truncate text-gray-900" style="font-size: 0.9rem;"><?php echo htmlspecialchars((string)$ne['candidat_nom']); ?></div>
                            <div class="small text-muted text-truncate" style="font-size: 0.8rem;"><?php echo htmlspecialchars((string)$ne['concours_titre']); ?></div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                <span class="badge bg-white text-secondary border rounded-pill px-2 py-1" style="font-size: 0.7rem;"><i class="bi bi-clock me-1"></i><?php echo substr($ne['heure_entrevue'], 0, 5); ?></span>
                                <span class="badge bg-white text-secondary border rounded-pill px-2 py-1 text-truncate" style="font-size: 0.7rem; max-width: 140px;" title="<?php echo htmlspecialchars((string)$ne['lieu_ou_lien']); ?>"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars((string)$ne['lieu_ou_lien']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; if(empty($next_entretiens)): ?>
                    <div class="text-center py-4 text-muted small"><?php echo __t('Aucun entretien prévu prochainement.'); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statistiques Globales & Outils -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h6 class="fw-bold mb-4"><?php echo __t('Statistiques Globales'); ?></h6>
                <div style="height: 250px;"><canvas id="statChart"></canvas></div>
                
                <hr class="my-4 opacity-5">
                
                <h6 class="small fw-bold text-muted mb-3 text-uppercase"><?php echo __t('Outils Gérant'); ?></h6>
                <div class="d-grid gap-2">
                    <a href="liste_opportunites.php" class="quick-action-link">
                        <span><i class="bi bi-collection-fill me-2"></i> <?php echo __t('Gérer mes opportunités'); ?></span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                    <a href="preselections.php" class="quick-action-link">
                        <span><i class="bi bi-stars me-2"></i> <?php echo __t('Voir les présélectionnés'); ?></span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                    <a href="messagerie.php" class="quick-action-link">
                        <span><i class="bi bi-chat-left-dots-fill me-2"></i> <?php echo __t('Messagerie interne'); ?></span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                </div>
            </div>
            
            <!-- Mode Sécurisé -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-light">
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-box-lg bg-light text-success mb-0 shadow-sm" style="background: #f1f5f9 !important; color: #198754 !important;"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="fw-bold"><?php echo __t('Mode Sécurisé'); ?></div>
                        <div class="small text-muted"><?php echo __t('Accès restreint aux données.'); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const rawData = <?php echo json_encode($chart_statut); ?>;
    if(rawData.length > 0) {
        new Chart(document.getElementById('statChart'), {
            type: 'doughnut',
            data: {
                labels: rawData.map(d => d.statut.toUpperCase()),
                datasets: [{
                    data: rawData.map(d => d.count),
                    backgroundColor: ['#94a3b8', '#0f5132', '#198754', '#475569'],
                    borderWidth: 0,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 25, font: { family: 'Inter', weight: 'bold' } } } },
                cutout: '75%'
            }
        });
    }
});
</script>

<?php include_footer(); ?>
