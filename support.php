<?php
require_once "includes/layout.php";

// Protection : utilisateur connecté uniquement
if (!isset($_SESSION["id"]) || !in_array($_SESSION["role"], ['candidat', 'gerant'])) {
    $_SESSION["error_message"] = "Vous devez être connecté pour accéder au support.";
    header("Location: choix_connexion.php");
    exit();
}

$id_utilisateur = $_SESSION["id"];

// Traitement du formulaire de création de ticket
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($sujet) && !empty($message)) {
        try {
            $pdo->beginTransaction();
            
            // Créer le ticket
            $stmt = $pdo->prepare("INSERT INTO tickets_support (id_utilisateur, sujet) VALUES (?, ?)");
            $stmt->execute([$id_utilisateur, $sujet]);
            $id_ticket = $pdo->lastInsertId();
            
            // Ajouter le premier message
            $stmt_msg = $pdo->prepare("INSERT INTO messages_support (id_ticket, id_emetteur, message) VALUES (?, ?, ?)");
            $stmt_msg->execute([$id_ticket, $id_utilisateur, $message]);
            
            $pdo->commit();
            $_SESSION['success_message'] = "Votre demande de support a été envoyée avec succès.";
            header("Location: support.php");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            send_error("Erreur lors de la création du ticket : " . $e->getMessage());
        }
    } else {
        send_error("Veuillez remplir tous les champs.");
    }
}

// Récupérer les tickets de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT * FROM tickets_support WHERE id_utilisateur = ? ORDER BY date_creation DESC");
    $stmt->execute([$id_utilisateur]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tickets = [];
}

include_header("Support Assistance");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-5 anim-up">
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-8 fw-bold mb-3 ls-1">ASSISTANCE</span>
            <h1 class="display-6 fw-black text-gray-900 mb-1">Centre de Support</h1>
            <p class="text-muted mb-0">Contactez l'administration ou suivez vos demandes en cours.</p>
        </div>
        <div>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                <i class="bi bi-plus-circle me-2"></i> Nouveau Ticket
            </button>
        </div>
    </div>

    <!-- Liste des tickets -->
    <div class="row g-4">
        <?php if (empty($tickets)): ?>
            <div class="col-12 text-center py-5">
                <div class="bg-white p-5 rounded-4 shadow-sm border border-opacity-10 d-inline-block">
                    <i class="bi bi-headset fs-1 text-primary mb-3 d-block"></i>
                    <h5 class="fw-bold mb-2">Vous n'avez aucune demande en cours</h5>
                    <p class="text-muted mb-4">Besoin d'aide ? N'hésitez pas à nous contacter.</p>
                    <button class="btn btn-outline-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#newTicketModal">Ouvrir un ticket</button>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($tickets as $ticket): ?>
                <div class="col-md-6 col-lg-4 anim-up anim-delay-1">
                    <a href="ticket.php?id=<?php echo $ticket['id']; ?>" class="text-decoration-none">
                        <div class="bg-white p-4 rounded-4 shadow-sm border border-opacity-10 h-100 hover-lift">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <?php if ($ticket['statut'] === 'ouvert'): ?>
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="bi bi-envelope-open me-1"></i> Ouvert</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i> Fermé</span>
                                <?php endif; ?>
                                <small class="text-muted fw-bold"><?php echo date('d/m/Y', strtotime($ticket['date_creation'])); ?></small>
                            </div>
                            <h5 class="fw-bold text-gray-900 mb-2"><?php echo htmlspecialchars($ticket['sujet']); ?></h5>
                            <p class="text-muted small mb-0">Cliquez pour voir les messages et répondre.</p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nouveau Ticket -->
<div class="modal fade" id="newTicketModal" tabindex="-1" aria-labelledby="newTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-premium">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="newTicketModalLabel"><i class="bi bi-chat-square-text text-primary me-2"></i> Ouvrir un Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="support.php">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <div class="mb-3">
                        <label for="sujet" class="form-label-pro">Sujet de votre demande</label>
                        <input type="text" class="form-control-pro" id="sujet" name="sujet" required placeholder="Ex: Problème de connexion, Question sur une candidature...">
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label-pro">Votre message</label>
                        <textarea class="form-control-pro" id="message" name="message" rows="5" required placeholder="Décrivez votre problème en détail..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Envoyer <i class="bi bi-send ms-2"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_footer(); ?>
