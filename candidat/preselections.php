<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_candidat = $_SESSION["id"];
$search = trim($_GET['search'] ?? '');

// Récupérer les candidatures validées du candidat (= présélections)
$sql = "
    SELECT
        c.id AS candidature_id,
        c.date_candidature,
        co.titre AS offre_titre,
        co.id AS offre_id,
        u_gerant.nom AS gerant_nom,
        e.date_entrevue, e.heure_entrevue, e.lieu_ou_lien, e.notes
    FROM candidatures c
    JOIN offres co ON c.id_offre = co.id
    JOIN utilisateurs u_gerant ON co.id_gerant = u_gerant.id
    LEFT JOIN entretiens e ON e.id_candidature = c.id
    WHERE c.id_candidat = :id_candidat
      AND c.statut = 'validee'
";
$params = [':id_candidat' => $id_candidat];

if ($search !== '') {
    $sql .= " AND (co.titre LIKE :search OR u_gerant.nom LIKE :search)";
    $params[':search'] = "%$search%";
}
$sql .= " ORDER BY c.date_candidature DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $preselections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $preselections = [];
}

include_header("Mes Présélections");
?>

<div class="row mb-5 animate__animated animate__fadeIn">
    <div class="col-12">
        <div class="p-5 rounded-5 shadow-premium border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center position-relative overflow-hidden mb-4" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
            <div class="position-absolute rounded-circle opacity-10 bg-white" style="width: 200px; height: 200px; top: -50px; right: -50px;"></div>
            <div class="position-relative z-1">
                <h1 class="display-5 fw-extrabold text-white mb-2">Félicitations ! 🎉</h1>
                <p class="text-white opacity-75 fs-5 mb-0">Retrouvez ici tous les offre pour lesquels vous avez été <strong>présélectionné</strong>.</p>
            </div>
            <div class="mt-4 mt-md-0 position-relative z-1">
                <form method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-0 ps-3 rounded-pill-start">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-0 rounded-pill-end ps-2 py-2" placeholder="Rechercher un offre..." value="<?php echo htmlspecialchars($search); ?>" style="min-width: 250px;">
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($preselections)): ?>
        <div class="col-12 text-center py-5">
            <div class="display-1 mb-4 opacity-10">🌟</div>
            <h3 class="fw-bold text-muted">Pas encore de présélection ?</h3>
            <p class="text-secondary mb-4">Continuez à postuler pour multiplier vos chances de réussite !</p>
            <a href="liste_offres.php" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow">
                Découvrir les offre ouverts
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($preselections as $p): ?>
            <div class="col-12 col-lg-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden transition-hover">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-2 rounded-pill small fw-bold">
                                <i class="bi bi-check-circle-fill me-1"></i> Dossier Validé
                            </div>
                            <span class="small text-muted"><?php echo format_date($p['date_candidature']); ?></span>
                        </div>
                        
                        <h4 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($p['offre_titre']); ?></h4>
                        <p class="text-muted small mb-4">Par : <span class="fw-semibold text-dark"><?php echo htmlspecialchars($p['gerant_nom']); ?></span></p>

                        <?php if (!empty($p['date_entrevue'])): ?>
                            <div class="bg-light rounded-4 p-3 mb-4 border-start border-primary border-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fs-3">📅</div>
                                    <div>
                                        <div class="fw-bold text-primary small text-uppercase ls-1">Entretien Planifié</div>
                                        <div class="text-dark fw-bold">
                                            Le <?php echo format_date($p['date_entrevue']); ?> à <?php echo substr($p['heure_entrevue'], 0, 5); ?>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-geo-alt-fill me-1 text-danger"></i> <?php echo htmlspecialchars($p['lieu_ou_lien']); ?>
                                        </div>
                                        <?php if (!empty($p['notes'])): ?>
                                            <div class="mt-2 small text-dark p-2 rounded bg-white shadow-sm border">
                                                <i class="bi bi-info-circle me-1 text-primary"></i> <strong>Note du recruteur :</strong><br>
                                                <?php echo nl2br(htmlspecialchars($p['notes'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info border-0 rounded-4 p-3 small mb-4">
                                <i class="bi bi-info-circle-fill me-1"></i> Votre dossier a été validé. Restez attentif, le recruteur vous contactera prochainement pour la suite.
                            </div>
                        <?php endif; ?>

                        <div class="d-flex gap-2">
                            <a href="messagerie.php?id=<?php echo $p['candidature_id']; ?>" class="btn btn-outline-primary rounded-pill px-4 flex-grow-1 fw-bold">
                                <i class="bi bi-chat-text me-2"></i> Contacter
                            </a>
                            <a href="details_offre.php?id=<?php echo $p['offre_id']; ?>" class="btn btn-light rounded-pill px-4 fw-bold">
                                Détails
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php 
include_footer();
?>
