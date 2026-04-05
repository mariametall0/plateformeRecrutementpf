<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

$search  = trim($_GET['search'] ?? '');
$filtre  = $_GET['statut'] ?? '';

$gerants = [];
try {
    $sql = "SELECT id, nom, email, telephone, statut, date_creation FROM utilisateurs WHERE role = 'gerant'";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (nom LIKE :search OR email LIKE :search OR telephone LIKE :search)";
        $params[':search'] = "%$search%";
    }

    if (!empty($filtre)) {
        $sql .= " AND statut = :statut";
        $params[':statut'] = $filtre;
    }

    $sql .= " ORDER BY date_creation DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $gerants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Erreur silencieuse
}

include_header("Gestion des Recruteurs");
?>

<div class="row mb-4 animate__animated animate__fadeIn">
    <div class="col-12">
        <div class="p-4 bg-white rounded-4 shadow-sm border-start border-primary border-5 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Gestion des Recruteurs / Partenaires</h1>
                <p class="text-muted mb-0">Total : <strong><?php echo count($gerants); ?></strong> structures enregistrées</p>
            </div>
            <div class="d-none d-md-block text-primary display-6">🏢</div>
        </div>
    </div>
</div>

    <!-- Filtres -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body p-4">
                <form method="GET" class="row g-4 align-items-end">
                    <div class="col-12 col-xl-7">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">Rechercher une structure ou un contact</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-primary px-3"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control bg-white border-start-0 ps-0 fw-medium" placeholder="Nom de l'entreprise, Email ou Téléphone..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">État du compte partenaire</label>
                        <select name="statut" class="form-select form-select-lg bg-white shadow-sm fw-medium">
                            <option value="">Tous les états</option>
                            <option value="actif" <?php echo $filtre === 'actif' ? 'selected' : ''; ?>>✅ Compte Actif / Vérifié</option>
                            <option value="inactif" <?php echo $filtre === 'inactif' ? 'selected' : ''; ?>>🕒 Inactif / En attente</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg flex-grow-1 fw-bold shadow-sm px-4">Filtrer</button>
                        <a href="liste_gerants.php" class="btn btn-light btn-lg border px-3" title="Réinitialiser">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Liste -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted text-uppercase small py-3">
                        <tr>
                            <th class="ps-4 py-3 border-0">Structure & Contact</th>
                            <th class="py-3 border-0 text-center">Inscription</th>
                            <th class="py-3 border-0 text-center col-statut">État</th>
                            <th class="pe-4 py-3 border-0 text-end col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gerants as $g): ?>
                            <tr>
                                <td class="ps-4 py-4">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 me-3 fw-bold d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; font-size: 0.9rem;">
                                            <?php echo strtoupper(substr($g['nom'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($g['nom']); ?></div>
                                            <div class="small text-muted d-flex flex-wrap gap-2 mt-1">
                                                <span class="d-flex align-items-center gap-1"><i class="bi bi-envelope fs-7"></i> <?php echo htmlspecialchars($g['email']); ?></span>
                                                <?php if($g['telephone']): ?>
                                                    <span class="text-slate-300">•</span>
                                                    <span class="d-flex align-items-center gap-1"><i class="bi bi-telephone fs-7"></i> <?php echo htmlspecialchars($g['telephone']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 text-center">
                                    <div class="small fw-semibold text-secondary">
                                        <?php echo format_date($g['date_creation']); ?>
                                    </div>
                                </td>
                                <td class="text-center py-4 col-statut">
                                    <?php if ($g['statut'] === 'actif'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill">Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle rounded-pill">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 py-4 text-end col-actions">
                                    <?php if ($g['statut'] === 'actif'): ?>
                                        <a href="desactiver_gerant.php?id=<?php echo $g['id']; ?>" class="btn btn-light btn-sm rounded-pill px-3 text-danger fw-bold border">Bloquer</a>
                                    <?php else: ?>
                                        <a href="activer_gerant.php?id=<?php echo $g['id']; ?>" class="btn btn-primary btn-sm px-3 rounded-pill fw-bold border-0">Activer</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($gerants)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted fw-bold">
                                    <i class="bi bi-building display-3 opacity-10 d-block mb-3"></i>
                                    Aucune structure / recruteur trouvé.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php 
include_footer();
exit();
?>
