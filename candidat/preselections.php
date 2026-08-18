<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_candidat = $_SESSION["id"];
$search = trim($_GET['search'] ?? '');

try {
    $sql = "
        SELECT
            c.id AS candidature_id,
            c.date_candidature,
            co.titre AS concours_titre,
            co.id AS concours_id,
            u_gerant.nom AS gerant_nom
        FROM candidatures c
        JOIN concours co ON c.id_concours = co.id
        JOIN utilisateurs u_gerant ON co.id_gerant = u_gerant.id
        WHERE c.id_candidat = :id_candidat
          AND c.statut = 'validee'
    ";
    $params = [':id_candidat' => $id_candidat];

    if ($search !== '') {
        $sql .= " AND co.titre LIKE :search";
        $params[':search'] = "%$search%";
    }
    $sql .= " ORDER BY c.date_candidature DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $preselections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($preselections as &$p) {
        $p['date_candidature_fmt'] = format_date($p['date_candidature'], true);
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur base de données : " . $e->getMessage();
    $preselections = [];
}

include_header("Mes présélections");
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <span class="badge bg-success mb-2">Espace Candidat</span>
            <h1 class="display-6 fw-bold mb-2">Mes présélections</h1>
            <p class="text-muted mb-0">Retrouvez vos concours validés et les candidats présélectionnés.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary align-self-start"><i class="bi bi-arrow-left"></i> Retour au tableau</a>
    </div>

    <div class="row gy-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 text-uppercase text-muted mb-3">Présélections</h2>
                    <span class="display-5 fw-semibold"><?php echo count($preselections); ?></span>
                    <p class="text-muted mb-0">candidatures validées</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <form class="row gx-2 gy-2 align-items-end" method="get">
                        <div class="col-12">
                            <label class="form-label" for="search">Rechercher un concours</label>
                            <input id="search" name="search" type="text" class="form-control" placeholder="Titre du concours" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-success">Rechercher</button>
                        </div>
                        <div class="col-auto">
                            <a href="preselections.php" class="btn btn-outline-secondary">Réinitialiser</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($preselections)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-emoji-frown display-4 text-secondary mb-3"></i>
                <h3 class="h5">Aucune présélection n’a été trouvée.</h3>
                <p class="text-muted mb-0">Vérifiez votre recherche ou revenez plus tard.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Concours</th>
                            <th>Gérant</th>
                            <th>Date de candidature</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preselections as $pre): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pre['concours_titre']); ?></td>
                                <td><?php echo htmlspecialchars($pre['gerant_nom']); ?></td>
                                <td><?php echo htmlspecialchars($pre['date_candidature_fmt']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_footer(); exit(); ?>

