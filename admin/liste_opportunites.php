<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

// Actions rapides : activer / désactiver
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $id     = (int)($_POST["id"] ?? 0);
    $action = $_POST["action"] ?? "";

    if ($id > 0 && in_array($action, ["activer", "desactiver"])) {
        $new_statut = ($action === "activer") ? "actif" : "inactif";
        try {
            $stmt = $pdo->prepare("UPDATE concours SET statut = ? WHERE id = ?");
            $stmt->execute([$new_statut, $id]);
            $_SESSION['success_message'] = "Statut mis à jour.";
        } catch (PDOException $e) { /* Error handle */ }
        header("Location: liste_opportunites.php");
        exit();
    }
}

// Filtres
$search        = trim($_GET["search"] ?? "");
$filtre_statut = $_GET["statut"] ?? "";

try {
    $sql = "
        SELECT co.*, u.nom AS gerant_nom,
               (SELECT COUNT(*) FROM candidatures c WHERE c.id_concours = co.id) AS nb_candidatures
        FROM concours co
        JOIN utilisateurs u ON co.id_gerant = u.id
        WHERE 1=1
    ";
    $params = [];

    if ($search !== "") {
        $sql .= " AND (co.titre LIKE ? OR u.nom LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($filtre_statut !== "") {
        $sql .= " AND co.statut = ?";
        $params[] = $filtre_statut;
    }
    $sql .= " ORDER BY co.date_creation DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $liste = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $liste = []; }

include_header("Supervision : Opportunités");
?>

<div class="mesh-bg"></div>

<style>
.admin-card-glass { background: white; border-radius: var(--radius-xl); border: 1px solid var(--color-gray-100); padding: 2rem; }
.table-premium-admin tbody tr:hover { background: var(--color-gray-50); }
.badge-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px; }
</style>

<div class="container-modern py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-5 anim-up">
        <div>
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-8 fw-bold mb-3 ls-1">ADMINISTRATION</span>
            <h1 class="display-6 fw-black text-gray-900 mb-1">Audit des Opportunités</h1>
            <p class="text-muted mb-0">Contrôle global des <span class="text-success fw-bold"><?php echo count($liste); ?></span> sessions de recrutement.</p>
        </div>
        <div>
            <a href="sessions_concours.php" class="btn btn-success rounded-pill px-4 fw-black shadow-premium">
                <i class="bi bi-calendar-plus me-2"></i> Gérer les Sessions
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="admin-card-glass mb-5 anim-up anim-delay-1">
        <form method="GET" class="row g-4 align-items-end">
            <div class="col-lg-6">
                <label class="form-label small fw-bold text-muted text-uppercase ls-1 mb-2">Rechercher</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Titre, Gérant..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-lg-4">
                <label class="form-label small fw-bold text-muted text-uppercase ls-1 mb-2">Visibilité</label>
                <select name="statut" class="form-select">
                    <option value="">Tous les états</option>
                    <option value="actif" <?php echo $filtre_statut === 'actif' ? 'selected' : ''; ?>>Publics</option>
                    <option value="inactif" <?php echo $filtre_statut === 'inactif' ? 'selected' : ''; ?>>Suspendus</option>
                </select>
            </div>
            <div class="col-lg-2">
                <button type="submit" class="btn btn-white border w-100 fw-bold py-2 rounded-8">Filtrer</button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-premium rounded-4 overflow-hidden bg-white anim-up anim-delay-2">
        <div class="table-responsive">
            <table class="table table-premium-admin align-middle mb-0">
                <thead class="bg-light text-muted small fw-bold text-uppercase">
                    <tr>
                        <th class="ps-4 py-3">Rédacteur & Titre</th>
                        <th class="text-center">Dates Clés</th>
                        <th class="text-center">Dossiers</th>
                        <th class="text-center">Statut</th>
                        <th class="pe-4 text-end">Modération</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($liste)): ?>
                        <tr><td colspan="5" class="text-center py-5">Aucune opportunité trouvée.</td></tr>
                    <?php else: ?>
                        <?php foreach($liste as $c): ?>
                            <tr>
                                <td class="ps-4 py-4">
                                    <div class="fw-bold text-gray-900"><?php echo htmlspecialchars($c['titre']); ?></div>
                                    <div class="small text-muted"><i class="bi bi-person-circle small"></i> <?php echo htmlspecialchars($c['gerant_nom']); ?></div>
                                </td>
                                <td class="text-center">
                                    <div class="small fw-semibold"><?php echo format_date($c['date_ouverture']); ?></div>
                                    <div class="small text-muted italic">jusqu'au <?php echo format_date($c['date_cloture']); ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-indigo-50 text-indigo-700 rounded-pill px-3"><?php echo $c['nb_candidatures']; ?> inscrits</span>
                                </td>
                                <td class="text-center">
                                    <?php if ($c['statut'] === 'actif'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill"><span class="badge-status-dot bg-success"></span>PUBLIC</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill"><span class="badge-status-dot bg-danger"></span>SUSPENDU</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                        <?php if ($c['statut'] === 'actif'): ?>
                                            <input type="hidden" name="action" value="desactiver">
                                            <button type="submit" class="btn btn-sm btn-outline-danger border-0 rounded-circle" title="Suspendre"><i class="bi bi-shield-slash fs-5"></i></button>
                                        <?php else: ?>
                                            <input type="hidden" name="action" value="activer">
                                            <button type="submit" class="btn btn-sm btn-outline-success border-0 rounded-circle" title="Activer"><i class="bi bi-shield-check fs-5"></i></button>
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

<?php 
include_footer();
exit();
?>

