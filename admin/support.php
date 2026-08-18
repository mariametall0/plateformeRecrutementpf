<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

// Récupérer les tickets
$statut_filtre = $_GET['statut'] ?? 'tous';
$query = "
    SELECT t.*, u.nom, u.email, u.role, u.photo_path 
    FROM tickets_support t 
    JOIN utilisateurs u ON t.id_utilisateur = u.id 
";

if ($statut_filtre === 'ouvert') {
    $query .= " WHERE t.statut = 'ouvert' ";
} elseif ($statut_filtre === 'ferme') {
    $query .= " WHERE t.statut = 'ferme' ";
}
$query .= " ORDER BY t.date_creation DESC";

try {
    $stmt = $pdo->query($query);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tickets = [];
}

include_header("Support Utilisateurs");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-5 anim-up">
        <div>
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-8 fw-bold mb-3 ls-1">SUPPORT</span>
            <h1 class="display-6 fw-black text-gray-900 mb-1">Tickets de Support</h1>
            <p class="text-muted mb-0">Gérez les demandes d'assistance des candidats et gérants.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="support.php?statut=tous" class="btn <?php echo $statut_filtre === 'tous' ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill px-4">Tous</a>
            <a href="support.php?statut=ouvert" class="btn <?php echo $statut_filtre === 'ouvert' ? 'btn-warning' : 'btn-outline-warning'; ?> rounded-pill px-4">Ouverts</a>
            <a href="support.php?statut=ferme" class="btn <?php echo $statut_filtre === 'ferme' ? 'btn-secondary' : 'btn-outline-secondary'; ?> rounded-pill px-4">Fermés</a>
        </div>
    </div>

    <!-- Liste des tickets -->
    <div class="row g-4">
        <?php if (empty($tickets)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                <h5 class="text-muted">Aucun ticket trouvé.</h5>
            </div>
        <?php else: ?>
            <?php foreach ($tickets as $ticket): ?>
                <div class="col-12 anim-up anim-delay-1">
                    <div class="bg-white p-4 rounded-4 shadow-sm border border-opacity-10 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 hover-lift">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-lg bg-light rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden" style="width: 60px; height: 60px;">
                                <?php if (!empty($ticket['photo_path'])): ?>
                                    <img src="../<?php echo htmlspecialchars($ticket['photo_path']); ?>" alt="Photo" class="w-100 h-100 object-fit-cover">
                                <?php else: ?>
                                    <i class="bi bi-person-fill fs-3 text-muted"></i>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-1 text-gray-900">
                                    <a href="ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($ticket['sujet']); ?>
                                    </a>
                                </h5>
                                <div class="small text-muted d-flex align-items-center gap-3 flex-wrap">
                                    <span><i class="bi bi-person me-1"></i> <?php echo htmlspecialchars($ticket['nom']); ?> (<?php echo ucfirst($ticket['role']); ?>)</span>
                                    <span><i class="bi bi-calendar3 me-1"></i> <?php echo date('d/m/Y H:i', strtotime($ticket['date_creation'])); ?></span>
                                    <?php if ($ticket['statut'] === 'ouvert'): ?>
                                        <span class="badge bg-warning text-dark px-2 py-1 rounded-pill"><i class="bi bi-envelope-open me-1"></i> Ouvert</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary px-2 py-1 rounded-pill"><i class="bi bi-check-circle me-1"></i> Fermé</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-md-end">
                            <a href="ticket_detail.php?id=<?php echo $ticket['id']; ?>" class="btn btn-outline-primary rounded-pill px-4">
                                Voir le ticket <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php 
include_footer(); 
?>
