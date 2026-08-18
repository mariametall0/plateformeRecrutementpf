<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

$search        = trim($_GET["search"] ?? "");
$filtre_statut = $_GET["statut"] ?? "";
$filtre_concours = $_GET["id_concours"] ?? "";

try {
    $sql = "
        SELECT
            c.id AS candidature_id,
            c.statut AS statut_candidature,
            c.date_candidature,
            u.nom  AS candidat_nom,
            u.email AS candidat_email,
            co.titre AS concours_titre,
            co.id AS concours_id,
            ug.nom AS gerant_nom
        FROM candidatures c
        JOIN utilisateurs u  ON c.id_candidat  = u.id
        JOIN concours co     ON c.id_concours  = co.id
        JOIN utilisateurs ug ON co.id_gerant   = ug.id
        WHERE 1=1
    ";
    $params = [];

    if ($search !== "") {
        $sql .= " AND (u.nom LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($filtre_statut !== "") {
        $sql .= " AND c.statut = ?";
        $params[] = $filtre_statut;
    }
    if ($filtre_concours !== "") {
        $sql .= " AND co.id = ?";
        $params[] = (int)$filtre_concours;
    }
    $sql .= " ORDER BY c.date_candidature DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}

include_header("Toutes les Candidatures");
?>

<div class="row animate__animated animate__fadeIn">
    <!-- En-tête -->
    <div class="col-12 mb-4">
        <div class="p-4 bg-white rounded-4 shadow-sm border-start border-info border-5 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Suivi Global des Dossiers 📋</h1>
                <p class="text-muted mb-0">Centralisation de toutes les candidatures pour analyse et audit.</p>
            </div>
            <div class="d-none d-md-block fs-1 opacity-25 text-info">📑</div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body p-4">
                <form method="GET" class="row g-4 align-items-end">
                    <div class="col-12 col-xl-7">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">Nom ou Email du candidat</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-info px-3"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control bg-white border-start-0 ps-0 fw-medium" placeholder="Rechercher un dossier par identité..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">État d'avancement du dossier</label>
                        <select name="statut" class="form-select form-select-lg bg-white shadow-sm fw-medium">
                            <option value="">Tous les états</option>
                            <option value="en_attente" <?php echo $filtre_statut === 'en_attente' ? 'selected' : ''; ?>>🕒 En attente d'étude</option>
                            <option value="validee" <?php echo $filtre_statut === 'validee' ? 'selected' : ''; ?>>✅ Validé / Présélectionné</option>
                            <option value="rejetee" <?php echo $filtre_statut === 'rejetee' ? 'selected' : ''; ?>>❌ Dossier Rejeté</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                        <button type="submit" class="btn btn-success text-white btn-lg flex-grow-1 fw-bold shadow-sm px-4">Filtrer</button>
                        <a href="liste_candidatures.php" class="btn btn-light btn-lg border px-3" title="RAZ">
                            <i class="bi bi-eraser"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Liste consolidée -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted text-uppercase small py-3">
                        <tr>
                            <th class="ps-4 py-3 border-0">Identité Candidat</th>
                            <th class="py-3 border-0">Opportunité Cible</th>
                            <th class="py-3 border-0 text-center">Date de dépôt</th>
                            <th class="py-3 border-0 text-center col-statut">État final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($candidatures)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="display-1 mb-3 opacity-10">📑</div>
                                    <p class="text-muted fw-bold">Aucune candidature trouvée.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($candidatures as $cand): ?>
                                <tr>
                                    <td class="ps-4 py-4">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-slate-100 text-slate-600 p-2 me-3 fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 0.8rem;">
                                                <?php echo strtoupper(substr($cand['candidat_nom'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($cand['candidat_nom']); ?></div>
                                                <div class="small text-muted font-monospace"><?php echo htmlspecialchars($cand['candidat_email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($cand['concours_titre']); ?></div>
                                        <div class="small text-muted mt-1 d-flex align-items-center gap-1">
                                            <i class="bi bi-person-badge opacity-50"></i> <?php echo htmlspecialchars($cand['gerant_nom']); ?>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <div class="small fw-semibold text-secondary">
                                            <?php echo format_date($cand['date_candidature']); ?>
                                        </div>
                                    </td>
                                    <td class="text-center py-4 col-statut">
                                        <?php 
                                            $badge_theme = 'blue'; $badge_label = 'En examen';
                                            if ($cand['statut_candidature'] === 'validee') { $badge_theme = 'success'; $badge_label = 'Validée'; }
                                            elseif ($cand['statut_candidature'] === 'rejetee') { $badge_theme = 'danger'; $badge_label = 'Rejetée'; }
                                        ?>
                                        <span class="badge bg-<?php echo $badge_theme; ?> bg-opacity-10 text-<?php echo $badge_theme; ?> border border-<?php echo $badge_theme; ?>-subtle rounded-pill">
                                            <?php echo $badge_label; ?>
                                        </span>
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
