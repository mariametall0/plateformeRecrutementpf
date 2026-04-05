<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

// Actions rapides : activer / désactiver un offre
if ($_SERVER["REQUEST_METHOD"] === "POST" || isset($_GET["action"])) {
    $input = json_decode(file_get_contents("php://input"), true);
    $id     = (int)($input["id"] ?? $_GET["id"] ?? 0);
    $action = $input["action"] ?? $_GET["action"] ?? "";

    if ($id > 0 && in_array($action, ["activer", "desactiver"])) {
        $new_statut = ($action === "activer") ? "actif" : "inactif";
        try {
            $stmt = $pdo->prepare("UPDATE offres SET statut = ? WHERE id = ?");
            $stmt->execute([$new_statut, $id]);
            send_json(['success' => true, 'message' => "Offre " . ($action === 'activer' ? 'activé' : 'désactivé') . " avec succès."]);
        } catch (PDOException $e) {
            send_error("Erreur base de données : " . $e->getMessage(), 500);
        }
    }
}

// Filtres pour la liste
$search        = trim($_GET["search"] ?? "");
$filtre_statut = $_GET["statut"] ?? "";
$filtre_gerant = $_GET["id_gerant"] ?? "";

try {
    $sql = "
        SELECT co.*, u.nom AS gerant_nom,
               (SELECT COUNT(*) FROM candidatures c WHERE c.id_offre = co.id) AS nb_candidatures
        FROM offres co
        JOIN utilisateurs u ON co.id_gerant = u.id
        WHERE 1=1
    ";
    $params = [];

    if ($search !== "") {
        $sql .= " AND co.titre LIKE ?";
        $params[] = "%$search%";
    }
    if ($filtre_statut !== "") {
        $sql .= " AND co.statut = ?";
        $params[] = $filtre_statut;
    }
    if ($filtre_gerant !== "") {
        $sql .= " AND co.id_gerant = ?";
        $params[] = (int)$filtre_gerant;
    }
    $sql .= " ORDER BY co.date_creation DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $offre = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}

include_header("Supervision des Offre");
?>

<div class="row animate__animated animate__fadeIn">
    <!-- En-tête -->
    <div class="col-12 mb-4">
        <div class="p-4 bg-white rounded-4 shadow-sm border-start border-primary border-5 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Supervision des Offre 🏆</h1>
                <p class="text-muted mb-0">Contrôlez la visibilité et l'attractivité des offres sur la plateforme.</p>
            </div>
            <div class="d-none d-md-block fs-1 opacity-25 text-primary">📊</div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body p-4">
                <form method="GET" class="row g-4 align-items-end">
                    <div class="col-12 col-xl-7">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">Recherche globale par titre ou mots-clés</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-primary px-3"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control bg-white border-start-0 ps-0 fw-medium" placeholder="Ex: Développeur PHP, Ingénieur Civil, Technicien..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">Statut du offre</label>
                        <select name="statut" class="form-select form-select-lg bg-white shadow-sm fw-medium">
                            <option value="">Tous les statuts</option>
                            <option value="actif" <?php echo $filtre_statut === 'actif' ? 'selected' : ''; ?>>✅ Actif / Public</option>
                            <option value="inactif" <?php echo $filtre_statut === 'inactif' ? 'selected' : ''; ?>>🚫 Suspendu / Masqué</option>
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
    </div>

    <!-- Liste -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted text-uppercase small py-3">
                        <tr>
                            <th class="ps-4 py-3 border-0">Offre / Gérant</th>
                            <th class="py-3 border-0 text-center">Période</th>
                            <th class="py-3 border-0 text-center">Inscrits</th>
                            <th class="py-3 border-0 text-center col-statut">Visibilité</th>
                            <th class="pe-4 py-3 border-0 text-end col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($offre)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="display-1 mb-3 opacity-10">🏆</div>
                                    <p class="text-muted fw-bold">Aucun offre disponible.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($offre as $c): ?>
                                <tr>
                                    <td class="ps-4 py-4">
                                        <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($c['titre']); ?></div>
                                        <div class="small text-muted mt-1 d-flex align-items-center gap-1">
                                            <i class="bi bi-person-badge opacity-50"></i> <?php echo htmlspecialchars($c['gerant_nom']); ?>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <div class="d-flex flex-wrap justify-content-center gap-2 small fw-semibold">
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill"><?php echo format_date($c['date_ouverture']); ?></span>
                                            <span class="text-muted opacity-50">→</span>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill"><?php echo format_date($c['date_cloture']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="h5 mb-0 fw-bold text-indigo-600"><?php echo $c['nb_candidatures']; ?></div>
                                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.05em;">Dossiers</div>
                                    </td>
                                    <td class="py-4 text-center col-statut">
                                        <?php if ($c['statut'] === 'actif'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill">Public</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle rounded-pill">Masqué</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 py-4 text-end col-actions">
                                        <div class="d-inline-flex gap-2">
                                            <?php if ($c['statut'] === 'actif'): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Suspendre ce offre ?');">
                                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                    <input type="hidden" name="action" value="desactiver">
                                                    <button type="submit" class="btn btn-outline-danger border-0 rounded-4" title="Suspendre"><i class="bi bi-eye-slash-fill fs-5"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                                    <input type="hidden" name="action" value="activer">
                                                    <button type="submit" class="btn btn-outline-success border-0 rounded-4" title="Publier"><i class="bi bi-eye-fill fs-5"></i></button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="liste_candidatures.php?id_offre=<?php echo $c['id']; ?>" class="btn btn-light bg-light rounded-4 border-0 p-2 shadow-sm" title="Voir dossiers">
                                                <i class="bi bi-folder2-open fs-5 text-primary"></i>
                                            </a>
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

<?php 
include_footer();
exit();
