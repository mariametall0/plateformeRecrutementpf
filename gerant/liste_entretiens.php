<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];

$sql = "
    SELECT 
        e.id, 
        e.id_candidature, 
        e.date_entrevue, 
        e.heure_entrevue, 
        e.lieu_ou_lien, 
        u.nom AS candidat_nom, 
        u.email AS candidat_email,
        co.titre AS concours_titre,
        (SELECT COUNT(*) FROM messages_internes mi WHERE mi.candidature_id = c.id AND mi.emetteur_role = 'candidat' AND mi.is_read = 0) as unread_count
    FROM entretiens e
    JOIN candidatures c ON e.id_candidature = c.id
    JOIN utilisateurs u ON c.id_candidat = u.id
    JOIN concours co ON c.id_concours = co.id
    WHERE co.id_gerant = :id_gerant
    ORDER BY e.date_entrevue ASC, e.heure_entrevue ASC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_gerant' => $id_gerant]);
    $entretiens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur liste_entretiens.php : " . $e->getMessage());
    $entretiens = [];
}

include_header("Mes Entretiens & Convocations");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-5 anim-up">
        <div>
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-8 fw-bold mb-3 ls-1">PLANIFICATION</span>
            <h1 class="display-6 fw-black text-gray-900 mb-1">Agenda des Entretiens</h1>
            <p class="text-muted mb-0">Retrouvez toutes les épreuves et entretiens planifiés pour vos opportunités.</p>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-premium rounded-4 overflow-hidden bg-white anim-up anim-delay-1">
        <div class="table-responsive">
            <table class="table table-premium mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Date & Heure</th>
                        <th>Candidat</th>
                        <th>Opportunité</th>
                        <th>Lieu / Lien</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entretiens)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="bi bi-calendar-x display-4 text-muted opacity-25 mb-3"></i>
                                <h6 class="text-muted">Aucun entretien planifié pour le moment.</h6>
                                <p class="small text-muted">Validez une candidature pour pouvoir planifier un rendez-vous.</p>
                                <a href="candidatures.php" class="btn btn-sm btn-success rounded-pill px-4 fw-bold mt-2">Voir les candidatures</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entretiens as $ent): 
                            $date_obj = new DateTime($ent['date_entrevue']);
                            $is_past = $date_obj < new DateTime('today');
                        ?>
                            <tr class="<?php echo $is_past ? 'opacity-50' : ''; ?>">
                                <td class="ps-4 py-4">
                                    <div class="fw-bold text-gray-900"><?php echo format_date($ent['date_entrevue']); ?></div>
                                    <div class="badge bg-light text-dark border fw-bold" style="font-size: 0.7rem;">
                                        <i class="bi bi-clock me-1"></i><?php echo substr($ent['heure_entrevue'], 0, 5); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-gray-900"><?php echo htmlspecialchars($ent['candidat_nom']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($ent['candidat_email']); ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold text-success"><?php echo htmlspecialchars($ent['concours_titre']); ?></div>
                                </td>
                                <td>
                                    <div class="small text-truncate" style="max-width: 200px;">
                                        <i class="bi bi-geo-alt-fill text-muted me-1"></i>
                                        <?php echo htmlspecialchars($ent['lieu_ou_lien']); ?>
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="planifier_entretien.php?id=<?php echo $ent['id_candidature']; ?>" class="btn btn-sm btn-white border px-3 rounded-pill fw-bold" title="Modifier">
                                            <i class="bi bi-pencil me-1"></i> Éditer
                                        </a>
                                        <a href="etudier_dossier.php?id=<?php echo $ent['id_candidature']; ?>" class="btn btn-sm btn-success px-3 rounded-pill fw-bold position-relative">
                                            Dossier
                                            <?php if (!empty($ent['unread_count'])): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem; z-index: 5;">
                                                    <?php echo $ent['unread_count']; ?>
                                                </span>
                                            <?php endif; ?>
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

<?php 
include_footer();
exit();
?>
