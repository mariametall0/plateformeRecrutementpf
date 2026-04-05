<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_offre = (int)($_GET["id"] ?? 0);
$id_candidat = $_SESSION["id"];

if ($id_offre <= 0) {
    header("Location: liste_offres.php");
    exit();
}

try {
    // Récupérer l'offre
    $stmt = $pdo->prepare("SELECT * FROM offres WHERE id = ? AND statut = 'actif'");
    $stmt->execute([$id_offre]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offre) {
        $_SESSION['error_message'] = "Cette offre n'est plus disponible.";
        header("Location: liste_offres.php");
        exit();
    }

    // Vérifier si déjà postulé
    $stmt = $pdo->prepare("SELECT * FROM candidatures WHERE id_candidat = ? AND id_offre = ?");
    $stmt->execute([$id_candidat, $id_offre]);
    $deja_postule = $stmt->fetch(PDO::FETCH_ASSOC);

    $today = date('Y-m-d');
    $ouvert = $offre['date_ouverture'] <= $today && $offre['date_cloture'] >= $today;

} catch (PDOException $e) {
    die("Erreur base de données : " . $e->getMessage());
}

include_header("Détails : " . htmlspecialchars($offre['titre']));
?>

<div class="row justify-content-center animate__animated animate__fadeIn">
    <div class="col-lg-10 col-xl-8">
        <!-- Hero Section -->
        <div class="card border-0 shadow-premium rounded-5 overflow-hidden mb-5">
            <div class="card-body p-0">
                <div class="p-5 text-white" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); position: relative; overflow: hidden;">
                    <div class="position-absolute opacity-10" style="bottom: -20px; right: -20px; font-size: 10rem;">🚀</div>
                    <div class="position-relative z-1 text-center py-4">
                        <a href="liste_offres.php" class="btn btn-sm btn-white rounded-pill px-3 mb-4 fw-bold text-primary bg-white shadow-sm border-0"><i class="bi bi-arrow-left"></i> Retour au catalogue</a>
                        <h1 class="display-5 fw-extrabold mb-3"><?php echo htmlspecialchars($offre['titre']); ?></h1>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <span class="badge bg-white bg-opacity-20 text-white border border-white border-opacity-25 rounded-pill px-3 py-2">
                                <i class="bi bi-calendar-event me-2"></i>Du <?php echo format_date($offre['date_ouverture']); ?> au <?php echo format_date($offre['date_cloture']); ?>
                            </span>
                            <?php if ($ouvert): ?>
                                <span class="badge bg-success text-white rounded-pill px-3 py-2 shadow-sm"><i class="bi bi-patch-check-fill me-2"></i>Ouvert aux candidatures</span>
                            <?php else: ?>
                                <span class="badge bg-danger text-white rounded-pill px-3 py-2 shadow-sm"><i class="bi bi-clock-history me-2"></i>Clôturé</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="p-5">
                    <div class="row g-5">
                        <div class="col-md-8">
                            <h5 class="fw-bold text-dark mb-4 d-flex align-items-center">
                                <i class="bi bi-text-left text-primary me-2"></i> Description du Poste & Conditions
                            </h5>
                            <div class="text-muted fs-5 lh-lg" style="white-space: pre-line;">
                                <?php echo htmlspecialchars($offre['description']); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-4 rounded-4 bg-light bg-opacity-50 border border-light-subtle h-100">
                                <h6 class="fw-bold text-dark mb-4">Informations Clés</h6>
                                
                                <div class="mb-4">
                                    <div class="small fw-bold text-muted text-uppercase ls-1 mb-1">Status Actuel</div>
                                    <div class="fw-bold <?php echo $ouvert ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $ouvert ? '🟢 Inscriptions Ouvertes' : '🔴 Inscriptions Closes'; ?>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="small fw-bold text-muted text-uppercase ls-1 mb-1">Date d'ouverture</div>
                                    <div class="fw-bold text-dark"><?php echo format_date($offre['date_ouverture'], true); ?></div>
                                </div>

                                <div class="mb-4">
                                    <div class="small fw-bold text-muted text-uppercase ls-1 mb-1">Date limite</div>
                                    <div class="fw-bold text-danger"><?php echo format_date($offre['date_cloture'], true); ?></div>
                                </div>

                                <hr class="my-4">

                                <?php if ($deja_postule): ?>
                                    <div class="alert alert-primary border-0 rounded-4 p-3 shadow-premium text-center">
                                        <div class="fs-1 mb-2">✅</div>
                                        <div class="fw-bold small mb-1">Candidature Déjà Déposée</div>
                                        <a href="mes_candidatures.php" class="btn btn-sm btn-primary rounded-pill mt-2">Suivre mon dossier</a>
                                    </div>
                                <?php elseif ($ouvert): ?>
                                    <a href="postuler_form.php?id=<?php echo $id_offre; ?>" class="btn btn-primary w-100 py-3 fw-extrabold rounded-pill shadow-premium mb-3">
                                        Postuler Maintenant <i class="bi bi-arrow-right ms-2"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100 py-3 fw-extrabold rounded-pill shadow-none" disabled>
                                        Inscriptions Closes
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include_footer();
exit();
?>