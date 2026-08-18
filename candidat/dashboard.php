<?php
declare(strict_types=1);

/**
 * dashboard.php – Tableau de bord Candidat.
 */
require_once "../includes/layout.php";

check_role('candidat');

$user_id = (int)$_SESSION["id"];

// Initialisation des compteurs
$stats = [
    'total' => 0,
    'en_attente' => 0,
    'validee' => 0,
    'ouverts' => 0
];
$candidatures_recentes = [];

try {
    // Optimisation : Groupement des comptes en une seule requête si possible, 
    // mais ici on garde séparé pour la clarté ou on utilise des sous-requêtes.
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM candidatures WHERE id_candidat = :uid1) as total,
            (SELECT COUNT(*) FROM candidatures WHERE id_candidat = :uid2 AND statut = 'en_attente') as en_attente,
            (SELECT COUNT(*) FROM candidatures WHERE id_candidat = :uid3 AND statut = 'validee') as validee,
            (SELECT COUNT(*) FROM concours WHERE statut = 'actif' AND date_ouverture <= CURDATE() AND (date_cloture >= CURDATE() OR date_cloture IS NULL)) as ouverts
    ");
    $stmt->execute([
        ':uid1' => $user_id,
        ':uid2' => $user_id,
        ':uid3' => $user_id
    ]);
    $res = $stmt->fetch();
    if ($res) {
        $stats = [
            'total' => (int)$res['total'],
            'en_attente' => (int)$res['en_attente'],
            'validee' => (int)$res['validee'],
            'ouverts' => (int)$res['ouverts']
        ];
    }

    // Candidatures récentes
    $stmt = $pdo->prepare("
        SELECT c.id as cand_id, c.statut, c.date_candidature,
               cn.titre, cn.date_cloture
        FROM candidatures c
        JOIN concours cn ON c.id_concours = cn.id
        WHERE c.id_candidat = ?
        ORDER BY c.date_candidature DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $candidatures_recentes = $stmt->fetchAll();

    // Récupérer le secteur du candidat
    $stmt = $pdo->prepare("SELECT secteur_specialite FROM profils_candidats WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);
    $user_secteur = $stmt->fetchColumn() ?: '';

    // Offres recommandées (Même secteur)
    $offres_recommandees = [];
    if (!empty($user_secteur)) {
        $stmt = $pdo->prepare("
            SELECT c.*, u.nom as entreprise 
            FROM concours c 
            JOIN utilisateurs u ON c.id_gerant = u.id 
            WHERE c.statut = 'actif' 
              AND c.date_ouverture <= CURDATE() 
              AND (c.date_cloture >= CURDATE() OR c.date_cloture IS NULL)
              AND (c.secteur LIKE ? OR c.titre LIKE ? OR c.description LIKE ?)
            ORDER BY c.date_creation DESC 
            LIMIT 3
        ");
        $search_term = "%$user_secteur%";
        $stmt->execute([$search_term, $search_term, $search_term]);
        $offres_recommandees = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}

$first_name = explode(' ', (string)$_SESSION['nom'])[0];

include_header("Tableau de bord");
?>
<style>
/* Dashboard Elite Refinement */
.recap-panel {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
    transition: all 0.3s ease;
}
.recap-panel:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    border-color: #0f5132;
}
.recap-panel::before {
    content: '';
    position: absolute;
    top: -100px;
    right: -100px;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(15,81,50,0.02) 0%, transparent 70%);
    border-radius: 50%;
}
.stat-box-mini {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    padding: 1.5rem;
    height: 100%;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}
.stat-box-mini:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    border-color: #0f5132;
}
.text-grad {
    color: #0f5132;
    font-weight: 800;
}
.quick-action-card {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}
.quick-action-card:hover {
    background: #fff;
    border-color: #0f5132;
    transform: translateX(4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
}
.stat-icon-circle {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    margin-bottom: 0.75rem;
    background: #f1f5f9;
    color: #334155;
}
.stat-icon-luminous {
    width: 44px;
    height: 44px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    background: #f1f5f9;
    color: #334155;
    flex-shrink: 0;
}
.bg-blue-soft, .bg-green-soft, .bg-amber-soft, .bg-purple-soft {
    background: #f1f5f9 !important;
    color: #334155 !important;
}

.offer-card-premium {
    background: #fff;
    border: 1px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    padding: 1.5rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}
.offer-card-premium:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    border-color: #0f5132;
}
.table-card {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid rgba(0,0,0,0.08);
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}
.status-pill {
    padding: 0.3rem 0.8rem;
    border-radius: 100px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.status-pending { background: #fff8eb; color: #b45309; }
.status-approved { background: #ecfdf5; color: #047857; }
.status-rejected { background: #fef2f2; color: #b91c1c; }
.status-active { background: #d1fae5; color: #1d4ed8; }

.mesh-bg {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    z-index: -1;
    background-color: #f8fafc;
}
</style>
<div class="mesh-bg"></div>

<div class="container-modern py-4">
    <!-- Header Hero -->
    <div class="recap-panel anim-up" data-intro="Bienvenue sur votre nouveau tableau de bord ! Voici un résumé de votre activité." data-step="1">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-8 fw-bold"><?php echo __t('PORTAIL CANDIDAT'); ?></span>
                    <span class="text-muted small fw-semibold"><i class="bi bi-shield-check text-success"></i> <?php echo __t('Accès Sécurisé'); ?></span>
                </div>
                <h1 class="display-5 fw-black text-gray-900 mb-2"><?php echo __t('welcome'); ?>, <span class="text-grad"><?php echo htmlspecialchars($first_name); ?></span></h1>
                <p class="text-gray-600 fs-5"><?php echo __t('Prêt à décrocher votre prochaine opportunité ?'); ?></p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0" data-intro="Cliquez ici pour découvrir toutes les opportunités de recrutement disponibles." data-step="2">
                <a href="liste_opportunites.php" class="btn btn-success px-4 py-3 rounded-pill fw-bold">
                    <i class="bi bi-rocket-takeoff-fill me-2"></i> <?php echo __t('Explorer les opportunités'); ?>

                </a>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-3 anim-up anim-delay-1" data-intro="Nombre d'offres d'emploi actives correspondant à votre profil." data-step="3">
            <div class="stat-box-mini">
                <div class="stat-icon-circle bg-blue-soft"><i class="bi bi-briefcase"></i></div>
                <div class="small fw-bold text-muted text-uppercase mb-2"><?php echo __t('Offres Ouvertes'); ?></div>
                <div class="h2 fw-black mb-0"><?php echo $stats['ouverts']; ?></div>

            </div>
        </div>
        <div class="col-md-3 anim-up anim-delay-2" data-intro="Le total de vos candidatures envoyées." data-step="4">
            <div class="stat-box-mini">
                <div class="stat-icon-circle bg-purple-soft"><i class="bi bi-send"></i></div>
                <div class="small fw-bold text-muted text-uppercase mb-2"><?php echo __t('Mes Candidatures'); ?></div>
                <div class="h2 fw-black mb-0"><?php echo $stats['total']; ?></div>

            </div>
        </div>
        <div class="col-md-3 anim-up anim-delay-3">
            <div class="stat-box-mini">
                <div class="stat-icon-circle bg-amber-soft"><i class="bi bi-hourglass-split"></i></div>
                <div class="small fw-bold text-muted text-uppercase mb-2"><?php echo __t('En Attente'); ?></div>
                <div class="h2 fw-black mb-0"><?php echo $stats['en_attente']; ?></div>

            </div>
        </div>
        <div class="col-md-3 anim-up anim-delay-4">
            <div class="stat-box-mini">
                <div class="stat-icon-circle bg-green-soft"><i class="bi bi-check-all"></i></div>
                <div class="small fw-bold text-muted text-uppercase mb-2"><?php echo __t('Validées'); ?></div>
                <div class="h2 fw-black mb-0"><?php echo $stats['validee']; ?></div>

            </div>
        </div>
    </div>
    
    <!-- Recommended Offers Section -->
    <?php if (!empty($offres_recommandees)): ?>
    <div class="mb-5 anim-up anim-delay-5" data-intro="Notre IA vous suggère ces offres car elles correspondent à votre spécialité." data-step="5">
        <div class="section-header mb-3">
            <div>
                <div class="section-title text-success"><i class="bi bi-magic me-2"></i> <?php echo __t('Opportunités suggérées pour vous'); ?></div>
                <div class="section-subtitle"><?php echo __t('Basé sur votre spécialité :'); ?> <strong><?php echo htmlspecialchars($user_secteur); ?></strong></div>
            </div>
            <a href="liste_opportunites.php" class="btn-see-all"><?php echo __t('Toutes les offres'); ?> <i class="bi bi-arrow-right"></i></a>

        </div>
        <div class="row g-4">
            <?php foreach ($offres_recommandees as $offre): ?>
            <div class="col-md-4">
                <div class="offer-card-premium">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="stat-icon-luminous mb-0" style="width: 40px; height: 40px; font-size: 1.2rem;">
                            <i class="bi bi-briefcase-fill"></i>
                        </div>
                        <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 small fw-bold"><?php echo __t('Nouveau'); ?></span>

                    </div>
                    <h5 class="fw-black text-gray-900 mb-1"><?php echo htmlspecialchars($offre['titre']); ?></h5>
                    <p class="text-muted small mb-3"><i class="bi bi-building me-1"></i> <?php echo htmlspecialchars($offre['entreprise']); ?></p>
                    
                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            <i class="bi bi-calendar-event me-1"></i> <?php echo __t('Expire le'); ?> <?php echo !empty($offre['date_cloture']) ? date('d/m/Y', strtotime((string)$offre['date_cloture'])) : '—'; ?>
                        </div>
                        <a href="postuler_form.php?id=<?php echo $offre['id']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold"><?php echo __t('apply'); ?></a>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

<!-- Recent Candidatures -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="section-header mb-3" data-intro="Suivez ici l'état d'avancement de vos dossiers (En attente, Validé, ou Entretien)." data-step="6">
            <div>
                <div class="section-title"><?php echo __t('Mes dernières candidatures'); ?></div>
                <div class="section-subtitle"><?php echo __t('Suivez l\'avancement de vos dossiers'); ?></div>
            </div>
            <a href="mes_candidatures.php" class="btn-see-all"><?php echo __t('Tout voir'); ?> <i class="bi bi-arrow-right"></i></a>

        </div>

        <div class="table-card">
            <?php if (empty($candidatures_recentes)): ?>
                <div class="empty-state">
                    <i class="bi bi-journal-x"></i>
                    <p><?php echo __t('Vous n\'avez pas encore postulé.'); ?><br>
                    <a href="liste_opportunites.php" class="btn-pro btn-pro-primary btn-pro-sm mt-3"><?php echo __t('Explorer les opportunités'); ?></a></p>
                </div>

            <?php else: ?>
                <table class="table table-premium mb-0">
                    <thead>
                        <tr>
                            <th><?php echo __t('Opportunité'); ?></th>
                            <th><?php echo __t('Date de clôture'); ?></th>
                            <th><?php echo __t('Statut'); ?></th>
                        </tr>

                    </thead>
                    <tbody>
                        <?php foreach ($candidatures_recentes as $cand):
                            $status_map = [
                                'en_attente' => ['class'=>'status-pending',  'label'=>__t('En attente')],
                                'validee'    => ['class'=>'status-approved', 'label'=>__t('Validée')],
                                'rejetee'    => ['class'=>'status-rejected', 'label'=>__t('Rejetée')],
                                'preselectionne' => ['class'=>'status-active', 'label'=>__t('Présélectionnée')],
                            ];

                            $s = $status_map[$cand['statut']] ?? ['class'=>'status-pending', 'label'=>$cand['statut']];
                        ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($cand['titre']); ?></div>
                                    <div class="small text-muted">
                                        Postulé le <?php echo !empty($cand['date_candidature']) ? date('d/m/Y', strtotime((string)$cand['date_candidature'])) : '—'; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo !empty($cand['date_cloture']) ? date('d/m/Y', strtotime((string)$cand['date_cloture'])) : '—'; ?>
                                </td>
                                <td><span class="status-pill <?php echo $s['class']; ?>"><?php echo $s['label']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions Sidebar -->
    <div class="col-lg-4" data-intro="Accès rapide à votre messagerie, vos notifications et la modification de votre profil." data-step="7">
        <div class="section-title mb-3"><?php echo __t('Actions rapides'); ?></div>
        <div class="d-flex flex-column gap-3">

            <a href="liste_opportunites.php" class="quick-action-card">
                <div class="stat-icon-luminous"><i class="bi bi-briefcase-fill"></i></div>
                <div>
                    <div class="quick-action-title"><?php echo __t('Explorer les offres'); ?></div>
                    <div class="quick-action-subtitle"><?php echo $stats['ouverts']; ?> <?php echo __t('opportunités disponibles'); ?></div>
                </div>
                <i class="bi bi-chevron-right ms-auto quick-action-arrow"></i>
            </a>

            <a href="mon_cv.php" class="quick-action-card">
                <div class="stat-icon-luminous"><i class="bi bi-file-earmark-person-fill"></i></div>
                <div>
                    <div class="quick-action-title"><?php echo __t('Mon CV numérique'); ?></div>
                    <div class="quick-action-subtitle"><?php echo __t('Mettez à jour votre profil'); ?></div>
                </div>
                <i class="bi bi-chevron-right ms-auto quick-action-arrow"></i>
            </a>

            <a href="notifications.php" class="quick-action-card">
                <div class="stat-icon-luminous"><i class="bi bi-bell-fill"></i></div>
                <div>
                    <div class="quick-action-title"><?php echo __t('notifications'); ?></div>
                    <div class="quick-action-subtitle"><?php echo __t('Vos alertes et mises à jour'); ?></div>
                </div>
                <i class="bi bi-chevron-right ms-auto quick-action-arrow"></i>
            </a>

            <a href="modifier_profil.php" class="quick-action-card">
                <div class="stat-icon-luminous"><i class="bi bi-person-circle"></i></div>
                <div>
                    <div class="quick-action-title"><?php echo __t('Mon Profil'); ?></div>
                    <div class="quick-action-subtitle"><?php echo __t('Modifier vos informations'); ?></div>
                </div>
                <i class="bi bi-chevron-right ms-auto quick-action-arrow"></i>
            </a>
        </div>
    </div>
</div>
</div> <!-- End container-modern -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (!localStorage.getItem('admissio_tour_done')) {
        introJs().setOptions({
            steps: [
                {
                    element: document.querySelector('.recap-panel'),
                    intro: `
                        <div class="text-center">
                            <h5 class="fw-black mb-1">Guide de démarrage</h5>
                            <p class="small text-muted">Découvrez comment utiliser votre nouvel espace Admissio.</p>
                        </div>
                        <div class="modern-video-box">
                            <div class="play-btn-modern">
                                <i class="bi bi-play-fill"></i>
                            </div>
                        </div>
                        <p class="small text-muted">Cliquez pour lancer la vidéo de présentation.</p>
                    `,
                    position: 'bottom'
                },
                {
                    element: document.querySelector('.sidebar-pro'),
                    intro: "Utilisez ce menu pour naviguer entre vos candidatures, vos documents et votre messagerie.",
                    position: 'right'
                },
                {
                    element: document.querySelector('.theme-toggle-btn'),
                    intro: "Vous pouvez basculer entre le mode sombre et le mode clair à tout moment ici.",
                    position: 'left'
                },
                {
                    element: document.querySelector('.topbar-right .dropdown'),
                    intro: "Changez la langue (Français, Anglais, Arabe) ou gérez votre compte ici.",
                    position: 'left'
                },
                {
                    element: document.querySelector('.btn-pro-primary'),
                    intro: "Cliquez ici pour découvrir toutes les opportunités de recrutement disponibles.",
                    position: 'left'
                },
                {
                    element: document.querySelector('.row.g-4.mb-5'),
                    intro: "Consultez vos statistiques clés en un coup d'œil.",
                    position: 'bottom'
                },
                {
                    element: document.querySelector('.table-card'),
                    intro: "Suivez ici l'état d'avancement de vos dossiers (En attente, Validé, ou Entretien).",
                    position: 'top'
                }
            ],
            nextLabel: 'Suivant',
            prevLabel: 'Précédent',
            doneLabel: 'Terminer',
            skipLabel: 'Passer',
            overlayOpacity: 0.8,
            showStepNumbers: false,
            showBullets: true,
            exitOnOverlayClick: false,
            scrollToElement: true
        }).start().oncomplete(function() {
            localStorage.setItem('admissio_tour_done', 'true');
        }).onexit(function() {
            localStorage.setItem('admissio_tour_done', 'true');
        });
    }
});
</script>

<?php include_footer(); ?>
