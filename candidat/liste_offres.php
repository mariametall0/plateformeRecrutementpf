<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_candidat = $_SESSION["id"];
$search = trim($_GET['search'] ?? '');

// Récupérer les offre actifs + indiquer si déjà postulé
$sql = "
    SELECT co.*,
           (SELECT COUNT(*) FROM candidatures WHERE id_candidat = :id_candidat AND id_offre = co.id) AS deja_postule
    FROM offres co
    WHERE co.statut = 'actif'
";
$params = [':id_candidat' => $id_candidat];

if ($search !== '') {
    $sql .= " AND co.titre LIKE :search";
    $params[':search'] = "%$search%";
}
$sql .= " ORDER BY co.date_cloture ASC";

$offres = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $offres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Erreur silencieuse
}

include_header("Liste des Offres");
?>

<div class="row mb-5 animate__animated animate__fadeIn align-items-center">
    <div class="col-12 col-xl-7 mb-4 mb-xl-0">
        <h1 class="display-5 fw-bold text-dark mb-2">Catalogue des Offres 🚀</h1>
        <p class="text-muted fs-5 mb-0">Découvrez et postulez aux meilleures opportunités de carrière.</p>
    </div>
    <div class="col-12 col-xl-5">
        <form method="GET" class="shadow-sm rounded-pill overflow-hidden bg-white border d-flex p-1">
            <div class="input-group input-group-lg border-0">
                <span class="input-group-text bg-white border-0 ps-4 text-primary"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control border-0 py-3 fw-medium" style="box-shadow: none;" placeholder="Métier, titre ou mots-clés..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold rounded-pill shadow-sm">Trouver</button>
        </form>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($offres)): ?>
        <div class="col-12 text-center py-5">
            <div class="display-1 mb-4">🔍</div>
            <p class="h5 text-muted">Aucune offre ne correspond à vos critères.</p>
            <a href="liste_offres.php" class="btn btn-outline-primary rounded-pill mt-3 px-4">Voir tout le catalogue</a>
        </div>
    <?php else: ?>
        <?php foreach ($offres as $c): 
            $today = new DateTime();
            $cloture = new DateTime($c['date_cloture']);
            $ouverture = new DateTime($c['date_ouverture']);
            
            $is_ouvert = ($cloture >= $today && $ouverture <= $today);
            $is_expire = ($cloture < $today);
            $days_left = $cloture->diff($today)->days;
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 transition-hover overflow-hidden bg-white">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <?php if ($is_expire): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill px-3">Clos</span>
                            <?php elseif (!$is_ouvert): ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle rounded-pill px-3">À venir</span>
                            <?php else: ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 animate__animated animate__pulse animate__infinite">Ouvert</span>
                            <?php endif; ?>

                            <?php if ($c['deja_postule']): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3"><i class="bi bi-check-circle-fill me-1"></i>Déjà postulé</span>
                            <?php endif; ?>
                        </div>

                        <h5 class="fw-bold mb-2 text-dark" style="min-height: 3rem; line-height: 1.4;"><?php echo htmlspecialchars($c['titre']); ?></h5>
                        <p class="text-muted small mb-4 opacity-75">
                            <?php echo htmlspecialchars(mb_strimwidth(strip_tags($c['description']), 0, 100, "...")); ?>
                        </p>

                        <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted" style="font-size: 0.7rem; text-uppercase: uppercase; font-weight: 700; letter-spacing: 0.05em;">Fermeture</div>
                                <div class="fw-bold text-dark small"><?php echo format_date($c['date_cloture']); ?></div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="details_offre.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold">Détails</a>
                                <?php if (!$c['deja_postule'] && $is_ouvert): ?>
                                    <a href="postuler_form.php?id=<?php echo $c['id']; ?>" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold border-0">Postuler</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php 
include_footer();
exit();
?>