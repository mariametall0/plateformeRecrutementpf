<?php
require_once "../includes/layout.php";
require_once "../includes/ai_evaluator.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];

$sql = "
    SELECT
        c.id AS candidature_id,
        c.statut AS statut_candidature,
        c.date_candidature,
        u.id AS candidat_id,
        u.nom AS candidat_nom,
        u.email AS candidat_email,
        co.id AS offre_id,
        co.titre AS offre_titre,
        co.description AS offre_desc,
        pc.secteur_specialite,
        pc.niveau_etude
    FROM candidatures c
    JOIN utilisateurs u ON c.id_candidat = u.id
    JOIN offres co ON c.id_offre = co.id
    LEFT JOIN profils_candidats pc ON u.id = pc.id_utilisateur
    WHERE co.id_gerant = :id_gerant
";
$params = [':id_gerant' => $id_gerant];

if (!empty($_GET['id_offre'])) {
    $sql .= " AND co.id = :id_offre";
    $params[':id_offre'] = (int)$_GET['id_offre'];
}
if (!empty($_GET['search'])) {
    $sql .= " AND (u.nom LIKE :search OR u.email LIKE :search)";
    $params[':search'] = '%' . $_GET['search'] . '%';
}
if (!empty($_GET['statut'])) {
    $sql .= " AND c.statut = :statut";
    $params[':statut'] = $_GET['statut'];
}
$sql .= " ORDER BY c.date_candidature DESC";

$candidatures = [];
$liste_offre = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // AI Scoring Computation
    foreach ($candidatures as &$cand) {
        $cand_profil = [
            'secteur_specialite' => $cand['secteur_specialite'] ?? '',
            'niveau_etude' => $cand['niveau_etude'] ?? ''
        ];
        $cand_offre = [
            'titre' => $cand['offre_titre'],
            'description' => $cand['offre_desc']
        ];
        $cand['score_ia'] = calculer_score_matching($cand_profil, $cand_offre);
    }
    unset($cand);

    $stmt_offre = $pdo->prepare("SELECT id, titre FROM offres WHERE id_gerant = ? ORDER BY titre");
    $stmt_offre->execute([$id_gerant]);
    $liste_offre = $stmt_offre->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Erreur silencieuse
}

include_header("Suivi des candidatures");
?>

<div class="row animate__animated animate__fadeIn">
    <div class="col-12 mb-4">
        <div class="p-4 bg-white rounded-4 shadow-sm border-start border-primary border-5 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Suivi des Candidatures</h1>
                <p class="text-muted mb-0">Total : <strong><?php echo count($candidatures); ?></strong> dossier(s) reçu(s)</p>
            </div>
            <div class="d-none d-md-flex align-items-center gap-3">
                <a href="export_candidats.php?<?php echo http_build_query($_GET); ?>" class="btn btn-outline-success fw-bold rounded-pill shadow-sm py-2 px-4">
                    <i class="bi bi-file-earmark-spreadsheet me-2"></i> Exporter CSV
                </a>
                <div class="text-primary display-6 mb-0">👥</div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body p-4">
                <form method="GET" class="row g-4 align-items-end">
                    <div class="col-12 col-xl-5">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">Rechercher un candidat</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-primary px-3"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control bg-white border-start-0 ps-0 fw-medium" placeholder="Nom ou Email..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">Offre visée</label>
                        <select name="id_offre" class="form-select form-select-lg bg-white shadow-sm fw-medium">
                            <option value="">Toutes les offres</option>
                            <?php foreach ($liste_offre as $lc): ?>
                                <option value="<?php echo $lc['id']; ?>" <?php echo ($_GET['id_offre'] ?? '') == $lc['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($lc['titre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-2">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">État du dossier</label>
                        <select name="statut" class="form-select form-select-lg bg-white shadow-sm fw-medium">
                            <option value="">Tous les états</option>
                            <option value="en_attente" <?php echo ($_GET['statut'] ?? '') === 'en_attente' ? 'selected' : ''; ?>>🕒 En attente</option>
                            <option value="validee" <?php echo ($_GET['statut'] ?? '') === 'validee' ? 'selected' : ''; ?>>✅ Validée</option>
                            <option value="rejetee" <?php echo ($_GET['statut'] ?? '') === 'rejetee' ? 'selected' : ''; ?>>❌ Rejetée</option>
                        </select>
                    </div>
                    <div class="col-12 col-xl-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg flex-grow-1 fw-bold shadow-sm px-4">Filtrer</button>
                        <a href="candidatures.php" class="btn btn-light btn-lg border px-3" title="Réinitialiser"><i class="bi bi-arrow-counterclockwise"></i></a>
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
                            <th class="ps-4 py-3 border-0">Candidat / Profil</th>
                            <th class="py-3 border-0">Annonce ciblée</th>
                            <th class="py-3 border-0 text-center">Appliqué le</th>
                            <th class="py-3 border-0 text-center">Score IA</th>
                            <th class="py-3 border-0 text-center col-statut">État</th>
                            <th class="pe-4 py-3 border-0 text-end col-actions">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($candidatures)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="display-3 opacity-10 mb-3">📂</div>
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
                                                <div class="small text-muted"><i class="bi bi-envelope fs-7"></i> <?php echo htmlspecialchars($cand['candidat_email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($cand['offre_titre']); ?></div>
                                    </td>
                                    <td class="py-4 text-center">
                                        <div class="small fw-semibold text-secondary">
                                            <?php echo format_date($cand['date_candidature']); ?>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <?php 
                                            // Progress bar logic
                                            $score = $cand['score_ia'];
                                            $score_color = 'danger';
                                            if ($score >= 70) $score_color = 'success';
                                            elseif ($score >= 40) $score_color = 'warning';
                                        ?>
                                        <div class="d-flex flex-column align-items-center" title="Score de compatibilité Profil / Offre (Alpha NLP v1)">
                                            <div class="fw-bold text-<?php echo $score_color; ?> fs-6 mb-1"><?php echo $score; ?>%</div>
                                            <div class="progress w-100" style="height: 6px; max-width: 80px; border-radius: 10px; background-color: #f1f5f9;">
                                                <div class="progress-bar bg-<?php echo $score_color; ?>" role="progressbar" style="width: <?php echo $score; ?>%" aria-valuenow="<?php echo $score; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-4 col-statut">
                                        <?php 
                                            $badge_theme = 'blue'; $badge_label = 'En attente';
                                            if ($cand['statut_candidature'] === 'validee') { $badge_theme = 'success'; $badge_label = 'Validée'; }
                                            elseif ($cand['statut_candidature'] === 'rejetee') { $badge_theme = 'danger'; $badge_label = 'Rejetée'; }
                                        ?>
                                        <span class="badge bg-<?php echo $badge_theme; ?> bg-opacity-10 text-<?php echo $badge_theme; ?> border border-<?php echo $badge_theme; ?>-subtle rounded-pill">
                                            <?php echo $badge_label; ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 py-4 text-end col-actions">
                                        <a href="etudier_dossier.php?id=<?php echo $cand['candidature_id']; ?>" class="btn btn-primary btn-sm px-3 rounded-pill fw-bold border-0">
                                            Étudier
                                        </a>
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
?>
