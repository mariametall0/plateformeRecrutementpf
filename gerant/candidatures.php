<?php
/**
 * candidatures.php – Liste et suivi des dossiers candidats pour le gérant.
 */
require_once "../includes/layout.php";
require_once "../includes/matching_engine.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];

// Requête unifiée utilisant 'concours' (ex-offres)
$sql = "
    SELECT
        c.id AS candidature_id,
        c.statut AS statut_candidature,
        c.date_candidature,
        u.id AS candidat_id,
        u.nom AS candidat_nom,
        u.email AS candidat_email,
        co.id AS concours_id,
        co.titre AS concours_titre,
        co.description AS concours_desc,
        pc.secteur_specialite,
        pc.niveau_etude
    FROM candidatures c
    JOIN utilisateurs u ON c.id_candidat = u.id
    JOIN concours co ON c.id_concours = co.id
    LEFT JOIN profils_candidats pc ON u.id = pc.id_utilisateur
    LEFT JOIN (
        SELECT candidature_id, COUNT(*) as unread_count 
        FROM messages_internes 
        WHERE emetteur_role = 'candidat' AND is_read = 0 
        GROUP BY candidature_id
    ) m ON c.id = m.candidature_id
    WHERE co.id_gerant = :id_gerant
";
$params = [':id_gerant' => $id_gerant];

if (!empty($_GET['id_concours'])) {
    $sql .= " AND co.id = :id_concours";
    $params[':id_concours'] = (int)$_GET['id_concours'];
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
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Évaluation de pertinence
    foreach ($candidatures as &$cand) {
        $cand_profil = [
            'secteur_specialite' => $cand['secteur_specialite'] ?? '',
            'niveau_etude' => $cand['niveau_etude'] ?? ''
        ];
        $cand_offre = [
            'titre' => $cand['concours_titre'],
            'description' => $cand['concours_desc']
        ];
        $cand['score_pertinence'] = calculate_relevance_score($cand_profil, $cand_offre);
    }
    unset($cand);

    // Liste des concours pour le filtre
    $stmt_opportunites = $pdo->prepare("SELECT id, titre FROM concours WHERE id_gerant = ? ORDER BY titre");
    $stmt_opportunites->execute([$id_gerant]);
    $liste_opportunites = $stmt_opportunites->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { 
    error_log("Erreur candidatures.php : " . $e->getMessage());
}

include_header("Suivi des Candidatures");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-5 anim-up">
        <div>
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-8 fw-bold mb-3 ls-1">PIPELINE DE RECRUTEMENT</span>
            <h1 class="display-6 fw-black text-gray-900 mb-1">Suivi des Talents</h1>
            <p class="text-muted mb-0">Gestion de <span class="text-success fw-bold"><?php echo count($candidatures); ?></span> dossiers actifs.</p>
        </div>
        <!-- Actions removed -->
    </div>

    <!-- Quick JS Filters -->
    <div class="d-flex flex-wrap gap-2 mb-4 anim-up anim-delay-1">
        <button class="btn btn-dark btn-sm px-4 rounded-pill fw-bold quick-filter-btn" onclick="filterTable('all', this)">Toutes</button>
        <button class="btn btn-outline-warning btn-sm px-4 rounded-pill fw-bold quick-filter-btn" onclick="filterTable('en_attente', this)">En attente</button>
        <button class="btn btn-outline-success btn-sm px-4 rounded-pill fw-bold quick-filter-btn" onclick="filterTable('validee', this)">Validées</button>
        <button class="btn btn-outline-danger btn-sm px-4 rounded-pill fw-bold quick-filter-btn" onclick="filterTable('rejetee', this)">Rejetées</button>
    </div>

    <!-- DB Filters -->
    <div class="filter-card-premium mb-5 anim-up anim-delay-1">
        <form method="GET" class="row g-4 align-items-end">
            <div class="col-lg-4">
                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Recherche instantanée</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                    <input type="text" id="liveSearchRecruteur" class="form-control-pro border-start-0 ps-0" placeholder="Nom ou email..." onkeyup="tableLiveSearch()">
                </div>
            </div>
            <div class="col-lg-5">
                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Opportunité (Base de données)</label>
                <select name="id_concours" class="form-select-pro" onchange="this.form.submit()">
                    <option value="">Toutes les opportunités</option>
                    <?php foreach ($liste_opportunites as $lo): ?>
                        <option value="<?php echo $lo['id']; ?>" <?php echo ($_GET['id_concours'] ?? '') == $lo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lo['titre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <a href="candidatures.php" class="btn-pro btn-pro-secondary w-100">Réinitialiser</a>
            </div>
        </form>
    </div>

    <!-- Candidates Table -->
    <div class="card border-0 shadow-premium rounded-4 overflow-hidden bg-white anim-up anim-delay-2">
        <div class="table-responsive">
            <table class="table table-premium mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Candidat</th>
                        <th>Opportunité</th>
                        <th class="text-center">Adéquation</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody id="candidaturesTableBody">
                    <?php if (empty($candidatures)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <h6 class="text-muted">Aucune candidature trouvée.</h6>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($candidatures as $cand): 
                            $score = $cand['score_pertinence'];
                            $score_class = 'score-low';
                            if($score >= 75) $score_class = 'score-high';
                            elseif($score >= 45) $score_class = 'score-medium';
                            
                            $s = $cand['statut_candidature'];
                            $status_badge = 'bg-warning-subtle text-warning border-warning';
                            $status_label = 'En attente';
                            if($s === 'validee') { $status_badge = 'bg-success-subtle text-success border-success'; $status_label = 'VALIDÉE'; }
                            elseif($s === 'rejetee') { $status_badge = 'bg-danger-subtle text-danger border-danger'; $status_label = 'REJETÉE'; }
                        ?>
                            <tr class="candidat-row" data-status="<?php echo htmlspecialchars($s); ?>">
                                <td class="ps-4 py-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 0.9rem;">
                                            <?php echo strtoupper(substr($cand['candidat_nom'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-gray-900"><?php echo htmlspecialchars($cand['candidat_nom']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($cand['candidat_email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-gray-700"><?php echo htmlspecialchars($cand['concours_titre']); ?></div>
                                    <div class="small text-muted italic" style="font-size: 0.7rem;">Postulé le <?php echo format_date($cand['date_candidature']); ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="score-badge <?php echo $score_class; ?>">
                                        <?php echo $score; ?> %
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?php echo $status_badge; ?> border rounded-pill px-3 py-2 fw-bold" style="font-size: 0.65rem;">
                                        <?php echo $status_label; ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="etudier_dossier.php?id=<?php echo $cand['candidature_id']; ?>" class="btn-pro btn-pro-secondary btn-pro-sm position-relative">
                                        Étudier &rarr;
                                        <?php if (!empty($cand['unread_count'])): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="z-index: 5;">
                                                <?php echo $cand['unread_count']; ?>
                                            </span>
                                        <?php endif; ?>
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

<script>
function filterTable(status, btnElement) {
    // Update button styles
    let buttons = document.querySelectorAll('.quick-filter-btn');
    buttons.forEach(b => {
        b.classList.remove('btn-dark');
        b.classList.remove('text-white');
        b.classList.add('btn-outline-secondary'); // Default outline for inactive
        if(b.innerText.includes('En attente')) b.classList.add('btn-outline-warning');
        if(b.innerText.includes('Validées')) b.classList.add('btn-outline-success');
        if(b.innerText.includes('Rejetées')) b.classList.add('btn-outline-danger');
    });

    // Make the clicked button solid
    btnElement.className = btnElement.className.replace(/btn-outline-[a-z]+/g, '');
    btnElement.classList.add('btn-dark', 'text-white');

    // Filter the rows
    let rows = document.querySelectorAll('.candidat-row');
    rows.forEach(row => {
        let rowStatus = row.getAttribute('data-status');
        if (status === 'all' || rowStatus === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function tableLiveSearch() {
    let input = document.getElementById('liveSearchRecruteur').value.toLowerCase();
    let rows = document.querySelectorAll('.candidat-row');
    
    rows.forEach(row => {
        let textContent = row.innerText.toLowerCase();
        if (textContent.includes(input)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    // Reset filter buttons to "Toutes" visually when searching
    if(input.length > 0) {
        document.querySelectorAll('.quick-filter-btn')[0].click();
    }
}
</script>

<?php 
include_footer();
exit();
?>

