<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];

$search = trim($_GET['search'] ?? '');
$filtre_statut = $_GET['statut'] ?? '';

$offre = [];
try {
    $sql = "SELECT * FROM offres WHERE id_gerant = ?";
    $params = [$id_gerant];

    if ($search !== '') {
        $sql .= " AND titre LIKE ?";
        $params[] = "%$search%";
    }
    if ($filtre_statut !== '') {
        $sql .= " AND statut = ?";
        $params[] = $filtre_statut;
    }

    $sql .= " ORDER BY date_creation DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $offre = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Erreur silencieuse
}

include_header("Gestion des Offres");
?>

<div class="row mb-4 animate__animated animate__fadeIn">
    <div class="col-12">
        <div class="p-4 bg-white rounded-4 shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1 fw-bold text-primary">Espace Recruteur : Mes Offres</h1>
                <p class="text-muted mb-0">Total : <strong><?php echo count($offre); ?></strong> offres créées</p>
            </div>
            <a href="ajouter_offre.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                ➕ Créer une Offre
            </a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form method="GET" class="row g-4 align-items-end">
            <div class="col-12 col-xl-7">
                <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">Recherche par titre</label>
                <div class="input-group input-group-lg shadow-sm">
                    <span class="input-group-text bg-white border-end-0 text-primary px-3"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-white border-start-0 ps-0 fw-medium" placeholder="Titre de l'offre ou mots-clés..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-12 col-md-6 col-xl-3">
                <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">Filtrer par statut</label>
                <select name="statut" class="form-select form-select-lg bg-white shadow-sm fw-medium">
                    <option value="">Tous les statuts</option>
                    <option value="actif" <?php echo $filtre_statut === 'actif' ? 'selected' : ''; ?>>✅ Actif / Public</option>
                    <option value="inactif" <?php echo $filtre_statut === 'inactif' ? 'selected' : ''; ?>>🚫 Inactif / Brouillon</option>
                </select>
            </div>
            <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-lg flex-grow-1 fw-bold shadow-sm px-4">Filtrer</button>
                <a href="liste_offres.php" class="btn btn-light btn-lg border px-3" title="Réinitialiser">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-5">
    <div class="table-responsive" style="overflow: visible !important;">
        <table class="table table-hover align-middle">
            <thead class="bg-light text-muted text-uppercase small">
                <tr>
                    <th class="ps-4 py-3 border-0">Informations Offre</th>
                    <th class="py-3 border-0 text-center">Dates Clés</th>
                    <th class="py-3 border-0 text-center col-statut">Visibilité</th>
                    <th class="pe-4 py-3 border-0 text-end col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($offre as $c): ?>
                    <tr>
                        <td class="ps-4 py-4">
                            <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($c['titre']); ?></div>
                            <div class="small text-muted mt-1 d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark fw-normal border">Créé le <?php echo format_date($c['date_creation']); ?></span>
                            </div>
                        </td>
                        <td class="py-4 text-center">
                            <div class="d-flex flex-wrap justify-content-center gap-2 small fw-semibold">
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill"><?php echo format_date($c['date_ouverture']); ?></span>
                                <span class="text-muted opacity-50">→</span>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill"><?php echo format_date($c['date_cloture']); ?></span>
                            </div>
                        </td>
                        <td class="py-4 text-center col-statut">
                            <?php if ($c['statut'] === 'actif'): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill">Public</span>
                            <?php else: ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle rounded-pill">Brouillon</span>
                            <?php endif; ?>
                        </td>
                        <td class="pe-4 py-4 text-end col-actions">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="candidatures.php?id_offre=<?php echo $c['id']; ?>" class="btn btn-sm btn-primary px-3 shadow-sm fw-bold" title="Candidatures">Les Favoris 👥</a>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle fw-bold" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-lg border p-2">
                                        <a class="dropdown-item py-2 fw-bold" href="modifier_offre.php?id=<?php echo $c['id']; ?>"><i class="bi bi-pencil me-2 text-warning"></i> Modifier</a>
                                        <a class="dropdown-item py-2 fw-bold" href="formulaire_offre.php?id=<?php echo $c['id']; ?>"><i class="bi bi-list-task me-2 text-info"></i> Formulaire</a>
                                        <a class="dropdown-item py-2 fw-bold" href="statistiques_offre.php?id=<?php echo $c['id']; ?>"><i class="bi bi-bar-chart me-2 text-primary"></i> Statistiques</a>
                                        <div class="dropdown-divider"></div>
                                        <?php if ($c['statut'] === 'actif'): ?>
                                            <a class="dropdown-item py-2 fw-bold text-danger" href="desactiver_offre.php?id=<?php echo $c['id']; ?>"><i class="bi bi-slash-circle me-2"></i> Désactiver</a>
                                        <?php else: ?>
                                            <a class="dropdown-item py-2 fw-bold text-success" href="activer_offre.php?id=<?php echo $c['id']; ?>"><i class="bi bi-check-circle me-2"></i> Activer cette Offre</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($offre)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted fw-bold">
                            <i class="bi bi-emoji-frown display-1 opacity-10 d-block mb-3"></i>
                            Aucune offre trouvée.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
include_footer();
exit();
?>