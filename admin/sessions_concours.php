<?php
declare(strict_types=1);

/**
 * sessions_concours.php – Planning et gestion des sessions de recrutement.
 */
require_once "../includes/layout.php";

// Protection admin
check_role('admin');// 1. Action : Mise à jour du statut (POST + CSRF) - Forcer la clôture ou suspendre
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action_type']) && $_POST['action_type'] === 'toggle_status') {
    verify_csrf_token();
    
    $id_concours = (int)($_POST['id_concours'] ?? 0);
    $new_statut = ($_POST['new_statut'] ?? '') === 'actif' ? 'actif' : 'inactif';
    
    if ($id_concours > 0) {
        try {
            // L'administrateur peut suspendre ou réactiver une offre directement
            $pdo->prepare("UPDATE concours SET statut = ? WHERE id = ?")->execute([$new_statut, $id_concours]);
            $_SESSION['success_message'] = "Le statut de l'offre a été mis à jour par l'administrateur.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Erreur technique lors de la mise à jour.";
        }
    }
    header("Location: sessions_concours.php");
    exit();
}

// 2. Récupération des données : Supervision de tous les concours
$concours_a_superviser = [];
try {
    $stmt = $pdo->prepare("
        SELECT co.*, u.nom AS gerant_nom
        FROM concours co
        JOIN utilisateurs u ON co.id_gerant = u.id
        ORDER BY co.date_cloture ASC
    ");
    $stmt->execute();
    $concours_a_superviser = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Supervision Error: " . $e->getMessage());
}

include_header("Supervision : Calendrier & Clôtures");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="row mb-5 anim-up">
        <div class="col-12">
            <div class="glass-premium p-5 rounded-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                <div>
                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill fw-bold mb-3 ls-1">SUPERVISION SYSTÈME</span>
                    <h1 class="display-6 fw-black text-gray-900 mb-1">Contrôle des Clôtures</h1>
                    <p class="text-muted mb-0">Surveillez les dates fixées par les recruteurs et assurez-vous de la clôture des dépôts.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row anim-up anim-delay-1">
        <div class="col-12">
            <div class="card border-0 shadow-premium rounded-4 overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-premium mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3">Concours & Recruteur</th>
                                <th class="py-3 text-center">Dates de Validité</th>
                                <th class="py-3 text-center">État Actuel</th>
                                <th class="pe-4 py-3 text-end">Modération Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($concours_a_superviser)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="display-1 opacity-10 mb-3">📅</div>
                                        <p class="text-muted fw-bold">Aucune opportunité à superviser pour le moment.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($concours_a_superviser as $c): 
                                    $is_actif = ($c['statut'] === 'actif');
                                    $cloture_depassee = (strtotime($c['date_cloture']) < time());
                                ?>
                                    <tr class="<?php echo $cloture_depassee ? 'bg-light-subtle opacity-75' : ''; ?>">
                                        <td class="ps-4 py-4">
                                            <div class="fw-bold text-gray-900"><?php echo htmlspecialchars((string)$c['titre']); ?></div>
                                            <div class="small text-muted"><i class="bi bi-person"></i> <?php echo htmlspecialchars((string)$c['gerant_nom']); ?></div>
                                        </td>
                                        <td class="text-center py-4">
                                            <div class="d-flex align-items-center justify-content-center gap-3">
                                                <div class="date-badge-premium"><?php echo format_date((string)$c['date_ouverture']); ?></div>
                                                <i class="bi bi-arrow-right text-muted small"></i>
                                                <div class="date-badge-premium <?php echo $cloture_depassee ? 'highlight-danger' : 'highlight'; ?>">
                                                    <?php echo format_date((string)$c['date_cloture']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center py-4">
                                            <?php if($cloture_depassee): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger rounded-pill px-3 py-2 fw-bold">EXPIRÉ</span>
                                            <?php elseif($is_actif): ?>
                                                <span class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-2 fw-bold">OUVERT</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border rounded-pill px-3 py-2 fw-bold">SUSPENDU</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 py-4 text-end">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="action_type" value="toggle_status">
                                                <input type="hidden" name="id_concours" value="<?php echo $c['id']; ?>">
                                                <input type="hidden" name="new_statut" value="<?php echo $is_actif ? 'inactif' : 'actif'; ?>">
                                                
                                                <?php if($is_actif): ?>
                                                    <button type="submit" class="btn-pro btn-pro-ghost btn-pro-sm text-danger" title="Assurer la clôture / Suspendre">
                                                        <i class="bi bi-lock-fill fs-5 me-1"></i> Fermer / Suspendre
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn-pro btn-pro-ghost btn-pro-sm text-success" title="Réactiver">
                                                        <i class="bi bi-unlock-fill fs-5 me-1"></i> Réouvrir
                                                    </button>
                                                <?php endif; ?>
                                            </form>
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
<?php 
include_footer();
?>
