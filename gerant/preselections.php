<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION['id'];
$selected_concours = $_GET['id_concours'] ?? '';

try {
    $stmt_concours = $pdo->prepare("SELECT id, titre FROM concours WHERE id_gerant = ? ORDER BY titre");
    $stmt_concours->execute([$id_gerant]);
    $liste_concours = $stmt_concours->fetchAll(PDO::FETCH_ASSOC);

    $sql = "
        SELECT
            c.id AS candidature_id,
            c.date_candidature,
            u.nom AS candidat_nom,
            u.email AS candidat_email,
            co.id AS concours_id,
            co.titre AS concours_titre
        FROM candidatures c
        JOIN utilisateurs u ON c.id_candidat = u.id
        JOIN concours co ON c.id_concours = co.id
        WHERE co.id_gerant = :id_gerant
          AND c.statut = 'validee'
    ";
    $params = [':id_gerant' => $id_gerant];

    if (!empty($selected_concours)) {
        $sql .= " AND co.id = :id_concours";
        $params[':id_concours'] = (int)$selected_concours;
    }

    $sql .= " ORDER BY co.titre ASC, u.nom ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $preselections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($preselections as &$pre) {
        $pre['date_candidature_fmt'] = format_date($pre['date_candidature'], true);
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur base de données : " . $e->getMessage();
    $preselections = [];
    $liste_concours = [];
}

include_header("Présélections validées");
?>
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
        <div>
            <span class="badge bg-info text-dark mb-2">Espace Gérant</span>
            <h1 class="display-6 fw-bold mb-2">Présélections validées</h1>
            <p class="text-muted mb-0">Consultez les candidats présélectionnés pour vos opportunités validées.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Retour</a>
            <button type="button" class="btn btn-success" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
        </div>
    </div>

    <div class="row gy-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 text-uppercase text-muted mb-3">Total présélections</h2>
                    <div class="d-flex align-items-end justify-content-between">
                        <div>
                            <span class="display-5 fw-semibold"><?php echo count($preselections); ?></span>
                            <p class="text-muted mb-0">résultats</p>
                        </div>
                        <div class="bg-success text-white rounded-3 p-3">
                            <i class="bi bi-check2-circle fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 text-uppercase text-muted mb-3">Filtrer par opportunité</h2>
                    <form class="row gx-2 gy-2 align-items-end" method="get">
                        <div class="col-12">
                            <label class="form-label visually-hidden" for="id_concours">Opportunité</label>
                            <select id="id_concours" name="id_concours" class="form-select">
                                <option value="">Toutes les opportunités</option>
                                <?php foreach ($liste_concours as $concours): ?>
                                    <option value="<?php echo htmlspecialchars($concours['id']); ?>" <?php if ($selected_concours == $concours['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($concours['titre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-success">Appliquer</button>
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
                <i class="bi bi-clipboard-check display-4 text-secondary mb-3"></i>
                <h3 class="h5">Aucune présélection trouvée.</h3>
                <p class="text-muted mb-0">Essayez un autre filtre ou revenez plus tard.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Opportunité</th>
                            <th>Candidat</th>
                            <th>Email</th>
                            <th>Date de candidature</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preselections as $pre): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pre['concours_titre']); ?></td>
                                <td><?php echo htmlspecialchars($pre['candidat_nom']); ?></td>
                                <td><?php echo htmlspecialchars($pre['candidat_email']); ?></td>
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

