<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_concours = (int)($_GET["id"] ?? 0);
$id_candidat = $_SESSION["id"];

if ($id_concours <= 0) {
    header("Location: liste_opportunites.php");
    exit();
}

try {
    // Récupérer l'opportunité (concours)
    $stmt = $pdo->prepare("SELECT * FROM concours WHERE id = ? AND statut = 'actif'");
    $stmt->execute([$id_concours]);
    $opportunite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$opportunite) {
        $_SESSION['error_message'] = "Cette opportunité n'est plus disponible.";
        header("Location: liste_opportunites.php");
        exit();
    }

    // Vérifier si déjà postulé
    $stmt = $pdo->prepare("SELECT id FROM candidatures WHERE id_candidat = ? AND id_concours = ?");
    $stmt->execute([$id_candidat, $id_concours]);
    $deja_postule = $stmt->fetch(PDO::FETCH_ASSOC);

    $today = date('Y-m-d');
    $ouvert = $opportunite['date_ouverture'] <= $today && $opportunite['date_cloture'] >= $today;

} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}

include_header(__t('details') . " : " . htmlspecialchars($opportunite['titre']));
?>

<div class="mesh-bg"></div>

<style>
.score-circle-lg {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin: 0 auto;
    font-family: 'Outfit', sans-serif;
    transition: all 0.5s ease;
}
</style>

<div class="container-modern py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Breadcrumb-like Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 anim-up">
                <a href="liste_opportunites.php" class="btn-pro btn-pro-secondary">
                    <i class="bi bi-arrow-left me-2"></i> <?php echo __t('back_to_opportunities'); ?>
                </a>
                <?php if ($deja_postule): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold">✓ <?php echo __t('posted'); ?></span>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-premium rounded-4 overflow-hidden bg-white mb-5 anim-up anim-delay-1">
                <div class="p-5 border-bottom bg-light bg-opacity-50">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-8 fw-bold mb-3 ls-1"><?php echo __t('job_details_badge'); ?></span>
                            <h1 class="display-5 fw-black text-gray-900 mb-3"><?php echo htmlspecialchars($opportunite['titre']); ?></h1>
                            <div class="d-flex flex-wrap gap-4 text-muted small fw-semibold">
                                <span><i class="bi bi-clock-fill text-success me-2"></i><?php echo __t('closing_date'); ?> : <?php echo format_date($opportunite['date_cloture']); ?></span>
                                <span><i class="bi bi-geo-alt-fill text-success me-2"></i>Nouakchott, Mauritanie (HQ)</span>
                                <span><i class="bi bi-briefcase-fill text-success me-2"></i>Temps Plein / Hybride</span>
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                            <?php if ($deja_postule): ?>
                                <a href="mes_candidatures.php" class="btn-pro btn-pro-success">
                                    <?php echo __t('view_my_dossier'); ?>
                                </a>
                            <?php elseif ($ouvert): ?>
                                <a href="postuler_form.php?id=<?php echo $id_concours; ?>" class="btn-pro btn-pro-primary">
                                    <?php echo __t('apply_now'); ?>
                                </a>
                            <?php else: ?>
                                <button class="btn-pro btn-pro-ghost" disabled>
                                    <?php echo __t('applications_closed'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="p-5">
                    <div class="row g-5">
                        <div class="col-lg-7">
                            <h4 class="fw-black text-gray-900 mb-4"><?php echo __t('job_description'); ?></h4>
                            <div class="text-gray-600 fs-6 lh-lg mb-5">
                                <?php echo nl2br(htmlspecialchars($opportunite['description'])); ?>
                            </div>

                            <h4 class="fw-black text-gray-900 mb-4"><?php echo __t('required_criteria'); ?></h4>
                            <ul class="list-unstyled">
                                <li class="d-flex gap-3 mb-3">
                                    <div class="stat-icon-mini bg-success-light text-success"><i class="bi bi-check-lg"></i></div>
                                    <span class="text-gray-600">Diplôme universitaire dans le domaine concerné.</span>
                                </li>
                                <li class="d-flex gap-3 mb-3">
                                    <div class="stat-icon-mini bg-success-light text-success"><i class="bi bi-check-lg"></i></div>
                                    <span class="text-gray-600">Expérience pertinente de minimum 2 ans.</span>
                                </li>
                                <li class="d-flex gap-3">
                                    <div class="stat-icon-mini bg-success-light text-success"><i class="bi bi-check-lg"></i></div>
                                    <span class="text-gray-600">Maîtrise des outils collaboratifs modernes.</span>
                                </li>
                            </ul>
                        </div>

                        <div class="col-lg-5">
                            <div class="glass-premium p-4 rounded-4 sticky-top sticky-top-2rem">
                                <h5 class="fw-bold mb-4"><?php echo __t('quick_info'); ?></h5>
                                <div class="mb-4">
                                    <div class="small fw-bold text-muted text-uppercase mb-1 ls-1"><?php echo __t('opening_date'); ?></div>
                                    <div class="fw-black text-gray-900 fs-5"><?php echo format_date($opportunite['date_ouverture']); ?></div>
                                </div>
                                <div class="mb-4">
                                    <div class="small fw-bold text-muted text-uppercase mb-1 ls-1"><?php echo __t('closing_date'); ?></div>
                                    <div class="fw-black text-danger fs-5"><?php echo format_date($opportunite['date_cloture']); ?></div>
                                </div>
                                <div class="mb-4">
                                    <div class="small fw-bold text-muted text-uppercase mb-1 ls-1"><?php echo __t('contest_ref'); ?></div>
                                    <div class="fw-black text-gray-900">#ADM-OP-<?php echo str_pad($opportunite['id'], 3, '0', STR_PAD_LEFT); ?></div>
                                </div>
                                
                                <hr class="opacity-5 my-4">
                                
                                <div class="p-4 bg-success bg-opacity-5 rounded-3 border border-success border-opacity-10">
                                    <div class="small text-success fw-black mb-2"><i class="bi bi-cpu-fill me-2"></i><?php echo __t('admissio_advice'); ?></div>
                                    <p class="small text-muted mb-3">Testez la compatibilité de votre CV numérique avec cette offre d'emploi avant de postuler pour maximiser vos chances de sélection.</p>
                                    <button onclick="runJobAdvisor()" class="btn btn-success btn-sm w-100 rounded-pill fw-bold py-2">
                                        <i class="bi bi-magic me-2"></i>Lancer l'Advisor IA
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Advisor Compatibilité -->
<div class="modal fade" id="jobCompatibilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-success bg-opacity-10 p-4 border-0">
                <h5 class="modal-title fw-black text-success d-flex align-items-center gap-2">
                    <i class="bi bi-cpu fs-4"></i> Analyse de compatibilité avec l'offre
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4 align-items-center mb-4">
                    <div class="col-md-4 text-center">
                        <div class="position-relative d-inline-block">
                            <!-- Circular Score Indicator -->
                            <div class="score-circle-lg d-flex align-items-center justify-content-center" id="advisorScoreCircle" style="width: 140px; height: 140px; border-radius: 50%; border: 8px solid #f1f5f9; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                                <div>
                                    <span class="display-6 fw-black text-success" id="advisorScoreVal">0</span>
                                    <span class="text-muted small">%</span>
                                </div>
                            </div>
                            <div class="small fw-bold text-muted mt-2">Score de matching</div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h6 class="text-muted small fw-bold text-uppercase mb-2">Verdict Flash</h6>
                        <div class="p-3 bg-light rounded-4 border border-light">
                            <p class="mb-0 text-gray-700 italic fw-semibold" id="advisorVerdict">Analyse en cours...</p>
                        </div>
                    </div>
                </div>

                <hr class="opacity-5 my-4">

                <!-- Section Diagnostic -->
                <div class="mb-4">
                    <h6 class="fw-black text-gray-900 mb-3"><i class="bi bi-file-text text-success me-2"></i>Diagnostic pour ce poste</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border border-light h-100">
                                <div class="fw-bold small mb-1">Présentation</div>
                                <p class="text-muted small mb-0" id="advisorPresentation">—</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border border-light h-100">
                                <div class="fw-bold small mb-1">Expérience</div>
                                <p class="text-muted small mb-0" id="advisorExperience">—</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border border-light h-100">
                                <div class="fw-bold small mb-1">Compétences</div>
                                <p class="text-muted small mb-0" id="advisorCompetences">—</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Points Forts -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-success-light bg-opacity-50 p-4 rounded-4 h-100" style="background: rgba(25, 135, 84, 0.08);">
                            <h6 class="fw-bold text-success mb-3"><i class="bi bi-patch-check-fill me-2"></i>Vos Points Forts pour le poste</h6>
                            <ul class="list-unstyled mb-0" id="advisorPointsForts">
                                <!-- Dynamique -->
                            </ul>
                        </div>
                    </div>

                    <!-- Suggestions -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-warning-light bg-opacity-50 p-4 rounded-4 h-100" style="background: rgba(245, 158, 11, 0.08);">
                            <h6 class="fw-bold text-warning mb-3"><i class="bi bi-lightbulb-fill me-2"></i>Ajustements conseillés</h6>
                            <ul class="list-unstyled mb-0" id="advisorSuggestions">
                                <!-- Dynamique -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-secondary w-100 py-3 rounded-pill fw-bold" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
let advisorModal;
document.addEventListener('DOMContentLoaded', () => {
    advisorModal = new bootstrap.Modal(document.getElementById('jobCompatibilityModal'));
});

function runJobAdvisor() {
    Swal.fire({
        title: 'Analyse en cours...',
        text: 'Notre Advisor IA compare votre CV avec les critères de cette offre d\'emploi.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const fd = new FormData();
    fd.append('id_concours', '<?php echo $id_concours; ?>');
    fd.append('use_digital_profile', '1');
    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

    fetch('optimiser_cv_ajax.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        Swal.close();
        if (data.success && data.suggestions) {
            const sug = data.suggestions;
            
            // Score and circle color coding
            const score = sug.score || 0;
            document.getElementById('advisorScoreVal').textContent = score;
            const circle = document.getElementById('advisorScoreCircle');
            
            // Visual indicators for score
            if (score >= 70) {
                circle.style.borderColor = '#198754';
                document.getElementById('advisorScoreVal').className = 'display-6 fw-black text-success';
            } else if (score >= 40) {
                circle.style.borderColor = '#f59e0b';
                document.getElementById('advisorScoreVal').className = 'display-6 fw-black text-warning';
            } else {
                circle.style.borderColor = '#dc3545';
                document.getElementById('advisorScoreVal').className = 'display-6 fw-black text-danger';
            }

            // Text values
            document.getElementById('advisorVerdict').textContent = sug.verdict_flash || 'Aucun verdict disponible';
            document.getElementById('advisorPresentation').textContent = sug.diagnostic?.presentation || '—';
            document.getElementById('advisorExperience').textContent = sug.diagnostic?.experience || '—';
            document.getElementById('advisorCompetences').textContent = sug.diagnostic?.competences || '—';

            // Lists
            const ptsFortsList = document.getElementById('advisorPointsForts');
            ptsFortsList.innerHTML = '';
            if (sug.points_forts && sug.points_forts.length > 0) {
                sug.points_forts.forEach(pf => {
                    const li = document.createElement('li');
                    li.className = 'd-flex gap-2 mb-2 small text-gray-700';
                    li.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> <span>' + pf + '</span>';
                    ptsFortsList.appendChild(li);
                });
            } else {
                ptsFortsList.innerHTML = '<li class="small text-muted">Aucun point fort identifié.</li>';
            }

            const sugList = document.getElementById('advisorSuggestions');
            sugList.innerHTML = '';
            if (sug.suggestions && sug.suggestions.length > 0) {
                sug.suggestions.forEach(s => {
                    const li = document.createElement('li');
                    li.className = 'd-flex gap-2 mb-2 small text-gray-700';
                    li.innerHTML = '<i class="bi bi-arrow-right-circle-fill text-warning"></i> <span>' + s + '</span>';
                    sugList.appendChild(li);
                });
            } else {
                sugList.innerHTML = '<li class="small text-muted">Aucune suggestion d\'amélioration.</li>';
            }

            advisorModal.show();
        } else {
            Swal.fire('Erreur', data.error || 'Impossible d\'analyser la compatibilité.', 'error');
        }
    })
    .catch(err => {
        Swal.close();
        console.error(err);
        Swal.fire('Erreur', 'Erreur de connexion au serveur.', 'error');
    });
}
</script>

<?php 
include_footer();
exit();
?>

