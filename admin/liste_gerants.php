<?php
declare(strict_types=1);

/**
 * liste_gerants.php – Gestion des comptes recruteurs/partenaires.
 */
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

$search  = trim((string)($_GET['search'] ?? ''));
$filtre  = (string)($_GET['statut'] ?? '');

$gerants = [];
try {
    $sql = "SELECT id, nom, email, telephone, statut, date_creation FROM utilisateurs WHERE role = 'gerant'";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (nom LIKE :search OR email LIKE :search OR telephone LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if ($filtre !== '') {
        $sql .= " AND statut = :statut";
        $params[':statut'] = $filtre;
    }

    $sql .= " ORDER BY date_creation DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $gerants = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Admin List Gerants Error: " . $e->getMessage());
}

include_header("Gestion des Recruteurs");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="row mb-5 anim-up">
        <div class="col-12">
            <div class="glass-premium p-5 rounded-4 d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 fw-black text-gray-900 mb-2">Recruteurs & Partenaires</h1>
                    <p class="text-muted mb-0">Contrôle des accès et supervision des structures enregistrées.</p>
                </div>
                <div class="d-none d-md-block">
                    <div class="stat-icon-luminous"><i class="bi bi-person-badge-fill"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="row mb-4 anim-up anim-delay-1">
        <div class="col-12">
            <div class="card border-0 shadow-premium rounded-4 bg-white">
                <div class="card-body p-4">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-lg-6">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-2">Rechercher</label>
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-white border-end-0 text-success"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Nom, Email ou Téléphone..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label small fw-bold text-muted text-uppercase mb-2">État du compte</label>
                            <select name="statut" class="form-select form-select-lg shadow-sm">
                                <option value="">Tous les états</option>
                                <option value="actif" <?php echo $filtre === 'actif' ? 'selected' : ''; ?>>✅ Actif</option>
                                <option value="inactif" <?php echo $filtre === 'inactif' ? 'selected' : ''; ?>>🕒 En attente / Inactif</option>
                            </select>
                        </div>
                        <div class="col-lg-2 d-flex gap-2 mt-lg-0">
                            <button type="submit" class="btn-pro btn-pro-primary flex-grow-1">Filtrer</button>
                            <a href="liste_gerants.php" class="btn-pro btn-pro-secondary px-3" title="Réinitialiser">
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste -->
    <div class="row anim-up anim-delay-2">
        <div class="col-12">
            <div class="card border-0 shadow-premium rounded-4 overflow-hidden bg-white">
                <div class="table-responsive">
                    <table class="table table-premium mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4 py-3">Structure & Contact</th>
                                <th class="py-3 text-center">Inscription</th>
                                <th class="py-3 text-center">État</th>
                                <th class="pe-4 py-3 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gerants as $g): ?>
                                <tr>
                                    <td class="ps-4 py-4">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 me-3 fw-bold d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                <?php echo strtoupper(substr((string)$g['nom'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-gray-900"><?php echo htmlspecialchars((string)$g['nom']); ?></div>
                                                <div class="small text-muted d-flex gap-3 mt-1">
                                                    <span><i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars((string)$g['email']); ?></span>
                                                    <?php if($g['telephone']): ?>
                                                        <span><i class="bi bi-telephone me-1"></i> <?php echo htmlspecialchars((string)$g['telephone']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <div class="small fw-semibold text-gray-600">
                                            <?php echo format_date((string)$g['date_creation']); ?>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <?php if ($g['statut'] === 'actif'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success rounded-pill px-3 py-2 fw-bold">ACTIF</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning rounded-pill px-3 py-2 fw-bold">EN ATTENTE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 py-4 text-end">
                                        <?php if ($g['statut'] === 'actif'): ?>
                                            <button onclick="toggleStatus(<?php echo $g['id']; ?>, 'deactivate')" class="btn-pro btn-pro-ghost btn-pro-sm text-danger">
                                                <i class="bi bi-shield-lock me-1"></i> Bloquer
                                            </button>
                                        <?php else: ?>
                                            <button onclick="toggleStatus(<?php echo $g['id']; ?>, 'activate')" class="btn-pro btn-pro-primary btn-pro-sm px-4">
                                                Activer
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($gerants)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div class="display-1 opacity-10 mb-3">🏢</div>
                                        <p class="text-muted fw-bold">Aucun recruteur trouvé.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const csrfToken = '<?php echo $_SESSION['csrf_token']; ?>';

function toggleStatus(id, action) {
    const isActivating = (action === 'activate');
    const title = isActivating ? 'Activer ce compte ?' : 'Bloquer ce compte ?';
    const text = isActivating ? 'Le recruteur pourra publier des offres immédiatement.' : 'Le recruteur ne pourra plus accéder à son espace.';
    
    Swal.fire({
        title: title,
        text: text,
        icon: isActivating ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonText: isActivating ? 'Oui, Activer' : 'Oui, Bloquer',
        cancelButtonText: 'Annuler',
        customClass: {
            confirmButton: isActivating ? 'btn-pro btn-pro-primary' : 'btn-pro btn-pro-danger',
            cancelButton: 'btn-pro btn-pro-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const url = isActivating ? 'activer_gerant.php' : 'desactiver_gerant.php';
            
            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, csrf_token: csrfToken })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Succès', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Erreur', data.error || 'Une erreur est survenue', 'error');
                }
            })
            .catch(() => Swal.fire('Erreur', 'Problème de connexion au serveur', 'error'));
        }
    });
}
</script>

<?php 
include_footer();
?>

