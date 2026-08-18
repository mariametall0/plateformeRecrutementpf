<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];

$search = trim($_GET['search'] ?? '');
$filtre_statut = $_GET['statut'] ?? '';

$opportunites = [];
try {
    $sql = "SELECT * FROM concours WHERE id_gerant = ?";
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
    $opportunites = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $opportunites = []; }

include_header("Gestion des Opportunités");
?>

<div class="mesh-bg"></div>

<style>
/* Gerant List Premium Styles */
.filter-glass {
    background: rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: var(--radius-xl);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.table-glass {
    background: white;
    border-radius: var(--radius-xl);
    border: 1px solid var(--color-gray-100);
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.02);
}

.table-premium th {
    background: var(--color-gray-50);
    color: var(--color-gray-500);
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 1px;
    padding: 1rem;
    border-bottom: 1px solid var(--color-gray-100);
}

.table-premium td {
    padding: 1.25rem 1rem;
    vertical-align: middle;
}

.opportunity-row:hover {
    background: var(--color-gray-50);
}
.btn-success-soft {
    background: rgba(25, 135, 84, 0.1);
    color: var(--color-success);
    border: 1px solid rgba(25, 135, 84, 0.2);
}
.btn-success-soft:hover {
    background: var(--color-success);
    color: white;
}
.btn-danger-soft {
    background: rgba(220, 53, 69, 0.1);
    color: var(--color-danger);
    border: 1px solid rgba(220, 53, 69, 0.2);
}
.btn-danger-soft:hover {
    background: var(--color-danger);
    color: white;
}
</style>

<div class="container-modern py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-5 anim-up">
        <div>
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-8 fw-bold mb-3 ls-1">MANAGEMENT HUB</span>
            <h1 class="display-6 fw-black text-gray-900 mb-1">Gérer les Opportunités</h1>
            <p class="text-muted mb-0">Vous avez <span class="text-success fw-bold"><?php echo count($opportunites); ?></span> opportunités enregistrées.</p>
        </div>
        <a href="ajouter_opportunite.php" class="btn-premium px-4 py-3 shadow-luminous">
            <i class="bi bi-plus-circle-fill me-2"></i> Nouvelle Opportunité
        </a>
    </div>

    <!-- Filters -->
    <div class="filter-glass anim-up anim-delay-1">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-muted text-uppercase">Rechercher</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Titre de l'opportunité..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted text-uppercase">Statut</label>
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="actif" <?php echo $filtre_statut === 'actif' ? 'selected' : ''; ?>>Actif</option>
                    <option value="inactif" <?php echo $filtre_statut === 'inactif' ? 'selected' : ''; ?>>Inactif</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-success px-4 fw-bold flex-grow-1">Filtrer</button>
                <a href="liste_opportunites.php" class="btn btn-light border px-4 fw-bold">Reset</a>
            </div>
        </form>
    </div>

    <!-- List -->
    <div class="table-glass anim-up anim-delay-2">
        <div class="table-responsive">
            <table class="table table-premium mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Titre / Réf</th>
                        <th>Période de validité</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($opportunites as $o): ?>
                        <tr class="opportunity-row">
                            <td class="ps-4">
                                <div class="fw-bold text-gray-900"><?php echo htmlspecialchars($o['titre']); ?></div>
                                <div class="small text-muted">Réf: #AUD-OP-<?php echo str_pad($o['id'], 3, '0', STR_PAD_LEFT); ?></div>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="text-muted">Du</span> <span class="fw-bold"><?php echo format_date($o['date_ouverture']); ?></span>
                                    <span class="text-muted">au</span> <span class="fw-bold text-danger"><?php echo format_date($o['date_cloture']); ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($o['statut'] === 'actif'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-2 rounded-pill small fw-bold">ACTIF</span>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-3 py-2 rounded-pill small fw-bold">INACTIF</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group gap-1">
                                    <a href="modifier_opportunite.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-white border rounded-pill px-3" title="Editer"><i class="bi bi-pencil-square"></i></a>
                                    <a href="formulaire_opportunite.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-white border rounded-pill px-3" title="Formulaire"><i class="bi bi-list-check"></i></a>
                                    <a href="candidatures.php?id_concours=<?php echo $o['id']; ?>" class="btn btn-sm btn-white border rounded-pill px-3" title="Candidats"><i class="bi bi-people-fill text-success"></i></a>
                                    
                                    <?php if ($o['statut'] === 'actif'): ?>
                                        <a href="desactiver_opportunite.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-danger-soft rounded-pill px-3" title="Désactiver" onclick="return confirm('Désactiver cette opportunité ?')"><i class="bi bi-pause-fill"></i></a>
                                    <?php else: ?>
                                        <a href="activer_opportunite.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-success-soft rounded-pill px-3" title="Activer"><i class="bi bi-play-fill"></i></a>
                                    <?php endif; ?>

                                    <a href="supprimer_opportunite.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-white border text-danger rounded-pill px-3" title="Supprimer" onclick="return confirm('Supprimer définitivement ?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($opportunites)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="display-1 opacity-5 mb-3">📁</div>
                                <h6 class="text-muted">Aucune opportunité trouvée.</h6>
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
