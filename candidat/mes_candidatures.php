<?php
declare(strict_types=1);

/**
 * mes_candidatures.php – Historique et suivi des candidatures.
 */
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$user_id = (int)$_SESSION["id"];
$candidatures = [];

try {
    $stmt = $pdo->prepare("
        SELECT c.id AS candidature_id, c.id_concours, c.statut, c.date_candidature,
               co.titre AS concours_titre, co.date_cloture,
               e.date_entrevue, e.heure_entrevue, e.lieu_ou_lien
        FROM candidatures c
        JOIN concours co ON c.id_concours = co.id
        LEFT JOIN entretiens e ON e.id_candidature = c.id
        WHERE c.id_candidat = ?
        ORDER BY c.date_candidature DESC
    ");
    $stmt->execute([$user_id]);
    $candidatures = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Candidatures Error: " . $e->getMessage());
}

// Calcul des statistiques pour les cartes KPI
$total_candidatures = count($candidatures);
$en_attente_count = 0;
$validee_count = 0;
$entretiens_count = 0;

foreach ($candidatures as $c) {
    if (!empty($c['date_entrevue'])) {
        $entretiens_count++;
    }
    if ($c['statut'] === 'en_attente') {
        $en_attente_count++;
    } elseif ($c['statut'] === 'validee') {
        $validee_count++;
    }
}

include_header(__t('applications'));
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="row anim-up">
        <div class="col-12 mb-5">
            <div class="glass-premium p-5 rounded-4 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 fw-black text-gray-900 mb-2"><?php echo __t('application_tracking'); ?></h1>
                    <p class="text-muted mb-0"><?php echo __t('application_tracking_subtext'); ?></p>
                </div>
                <div class="d-none d-md-block">
                    <div class="stat-icon-luminous"><i class="bi bi-folder2-open"></i></div>
                </div>
            </div>
        </div>

        <!-- Cartes KPI de résumé -->
        <div class="col-12 mb-4">
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="kpi-card shadow-sm border-0">
                        <div class="kpi-icon kpi-blue"><i class="bi bi-folder-fill"></i></div>
                        <div>
                            <div class="kpi-value text-primary"><?php echo $total_candidatures; ?></div>
                            <div class="kpi-label"><?php echo __t('applications'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="kpi-card shadow-sm border-0">
                        <div class="kpi-icon kpi-amber"><i class="bi bi-hourglass-split"></i></div>
                        <div>
                            <div class="kpi-value text-warning"><?php echo $en_attente_count; ?></div>
                            <div class="kpi-label"><?php echo __t('En Attente'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="kpi-card shadow-sm border-0">
                        <div class="kpi-icon kpi-green"><i class="bi bi-patch-check-fill"></i></div>
                        <div>
                            <div class="kpi-value text-success"><?php echo $validee_count; ?></div>
                            <div class="kpi-label"><?php echo __t('Validées'); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="kpi-card shadow-sm border-0">
                        <div class="kpi-icon kpi-indigo"><i class="bi bi-calendar-event-fill"></i></div>
                        <div>
                            <div class="kpi-value text-indigo" style="color: var(--indigo);"><?php echo $entretiens_count; ?></div>
                            <div class="kpi-label"><?php echo __t('interviews'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-premium rounded-4 overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-premium mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3"><?php echo __t('opportunity_ref'); ?></th>
                                <th class="py-3"><?php echo __t('deposit_date'); ?></th>
                                <th class="py-3 text-center"><?php echo __t('Statut'); ?></th>
                                <th class="pe-4 py-3 text-end"><?php echo __t('Action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($candidatures)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="display-1 mb-3 opacity-10">📁</div>
                                        <p class="text-muted fw-bold"><?php echo __t('no_active_application'); ?></p>
                                        <a href="liste_opportunites.php" class="btn-pro btn-pro-primary btn-pro-sm mt-3"><?php echo __t('see_offers'); ?></a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($candidatures as $c): 
                                    $s = (string)$c['statut'];
                                    $badge = match($s) {
                                        'validee' => 'bg-success-subtle text-success border-success',
                                        'rejetee' => 'bg-danger-subtle text-danger border-danger',
                                        'preselectionne' => 'bg-info-subtle text-info border-info',
                                        default => 'bg-warning-subtle text-warning border-warning'
                                    };
                                    $label = match($s) {
                                        'validee' => __t('Validée'),
                                        'rejetee' => __t('Rejetée'),
                                        'preselectionne' => __t('Présélectionnée'),
                                        default => __t('En attente')
                                    };
                                ?>
                                    <tr>
                                        <td class="ps-4 py-4">
                                            <div class="fw-bold text-gray-900"><?php echo htmlspecialchars((string)$c['concours_titre']); ?></div>
                                            <div class="small text-muted">Réf: #AUD-<?php echo str_pad((string)$c['candidature_id'], 4, '0', STR_PAD_LEFT); ?></div>
                                        </td>
                                        <td class="py-4">
                                            <div class="small text-gray-600">
                                                <i class="bi bi-calendar3 me-1"></i> <?php echo format_date((string)$c['date_candidature']); ?>
                                            </div>
                                        </td>
                                        <td class="text-center py-4">
                                            <span class="badge <?php echo $badge; ?> border rounded-pill px-3 py-2 fw-bold text-uppercase">
                                                <?php echo htmlspecialchars($label); ?>
                                            </span>
                                            
                                            <?php if (!empty($c['date_entrevue'])): ?>
                                            <div class="mt-2 p-2 rounded-3 border border-success-subtle bg-success-subtle bg-opacity-10 text-start" style="max-width: 220px; margin: 0 auto;">
                                                <div class="small text-success fw-bold mb-1 d-flex align-items-center gap-1">
                                                    <i class="bi bi-calendar-check-fill"></i>
                                                    <?php echo __t('interview_scheduled'); ?>
                                                </div>
                                                <div class="small text-gray-700 mb-1">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php echo format_date((string)$c['date_entrevue']); ?> à <?php echo substr((string)$c['heure_entrevue'], 0, 5); ?>
                                                </div>
                                                <?php if (!empty($c['lieu_ou_lien'])): ?>
                                                <div class="small text-muted text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars((string)$c['lieu_ou_lien']); ?>">
                                                    <i class="bi bi-geo-alt-fill me-1"></i>
                                                    <?php if (filter_var($c['lieu_ou_lien'], FILTER_VALIDATE_URL)): ?>
                                                        <a href="<?php echo htmlspecialchars((string)$c['lieu_ou_lien']); ?>" target="_blank" class="text-success text-decoration-underline"><?php echo __t('join_meeting'); ?></a>
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars((string)$c['lieu_ou_lien']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 py-4 text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <a href="messagerie.php?id=<?php echo $c['candidature_id']; ?>" class="btn-pro btn-pro-ghost btn-pro-sm" title="Messages">
                                                    <i class="bi bi-chat-left-dots-fill"></i>
                                                </a>
                                                <?php if ($s === 'en_attente'): ?>
                                                <button class="btn-pro btn-pro-ghost btn-pro-sm text-danger" 
                                                        onclick="confirmSuppression(<?php echo $c['candidature_id']; ?>)" 
                                                        title="Retirer">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<form id="form-supprimer" action="supprimer_candidature.php" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="id_candidature" id="input-id-suppr">
</form>

<script>

function confirmSuppression(id) {
    Swal.fire({
        title: '<?php echo __t('withdraw_application_q'); ?>',
        text: "<?php echo __t('action_is_final'); ?>",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<?php echo __t('yes_withdraw'); ?>',
        cancelButtonText: '<?php echo __t('cancel'); ?>',
        customClass: { confirmButton: 'btn-pro btn-pro-danger', cancelButton: 'btn-pro btn-pro-secondary' }
    }).then((res) => {
        if (res.isConfirmed) {
            document.getElementById('input-id-suppr').value = id;
            document.getElementById('form-supprimer').submit();
        }
    });
}
</script>

<?php 
include_footer();
?>
