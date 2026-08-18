<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

$id_ticket = $_GET['id'] ?? null;
if (!$id_ticket) {
    header("Location: support.php");
    exit();
}

$id_admin = $_SESSION['id'];

// Actions : Répondre ou Fermer
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    if (isset($_POST['action']) && $_POST['action'] === 'repondre' && !empty($_POST['message'])) {
        $message = trim($_POST['message']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO messages_support (id_ticket, id_emetteur, message) VALUES (?, ?, ?)");
            $stmt->execute([$id_ticket, $id_admin, $message]);
            $_SESSION['success_message'] = "Votre réponse a été envoyée.";
            header("Location: ticket_detail.php?id=$id_ticket");
            exit();
        } catch (PDOException $e) {
            send_error("Erreur d'envoi : " . $e->getMessage());
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'fermer') {
        try {
            $stmt = $pdo->prepare("UPDATE tickets_support SET statut = 'ferme' WHERE id = ?");
            $stmt->execute([$id_ticket]);
            $_SESSION['success_message'] = "Le ticket a été fermé.";
            header("Location: ticket_detail.php?id=$id_ticket");
            exit();
        } catch (PDOException $e) {
            send_error("Erreur : " . $e->getMessage());
        }
    }
}

// Récupérer les infos du ticket
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nom, u.email, u.role, u.photo_path 
        FROM tickets_support t 
        JOIN utilisateurs u ON t.id_utilisateur = u.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$id_ticket]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        header("Location: support.php");
        exit();
    }
    
    // Récupérer les messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.nom, u.role, u.photo_path 
        FROM messages_support m
        JOIN utilisateurs u ON m.id_emetteur = u.id
        WHERE m.id_ticket = ?
        ORDER BY m.date_envoi ASC
    ");
    $stmt->execute([$id_ticket]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

include_header("Ticket : " . htmlspecialchars($ticket['sujet']));
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="mb-4">
        <a href="support.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left me-2"></i> Retour aux tickets</a>
    </div>

    <!-- Header du ticket -->
    <div class="bg-white p-4 p-md-5 rounded-4 shadow-premium mb-4 anim-up">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 border-bottom pb-4 mb-4">
            <div class="d-flex align-items-center gap-4">
                <div class="avatar-xl bg-light rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden shadow-sm" style="width: 80px; height: 80px;">
                    <?php if (!empty($ticket['photo_path'])): ?>
                        <img src="../<?php echo htmlspecialchars($ticket['photo_path']); ?>" alt="Photo" class="w-100 h-100 object-fit-cover">
                    <?php else: ?>
                        <i class="bi bi-person-fill fs-1 text-muted"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <h2 class="fw-black text-gray-900 mb-2"><?php echo htmlspecialchars($ticket['sujet']); ?></h2>
                    <div class="text-muted d-flex align-items-center gap-3 flex-wrap">
                        <span><strong>De :</strong> <?php echo htmlspecialchars($ticket['nom']); ?> (<?php echo ucfirst($ticket['role']); ?>)</span>
                        <span><i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($ticket['email']); ?></span>
                        <span><i class="bi bi-calendar3 me-1"></i> <?php echo date('d/m/Y H:i', strtotime($ticket['date_creation'])); ?></span>
                        <?php if ($ticket['statut'] === 'ouvert'): ?>
                            <span class="badge bg-warning text-dark px-2 py-1 rounded-pill"><i class="bi bi-envelope-open me-1"></i> Ouvert</span>
                        <?php else: ?>
                            <span class="badge bg-secondary px-2 py-1 rounded-pill"><i class="bi bi-check-circle me-1"></i> Fermé</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($ticket['statut'] === 'ouvert'): ?>
                <form method="POST" action="ticket_detail.php?id=<?php echo $id_ticket; ?>" onsubmit="return confirm('Voulez-vous vraiment fermer ce ticket ?');">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="fermer">
                    <button type="submit" class="btn btn-outline-danger rounded-pill px-4">
                        <i class="bi bi-x-circle me-2"></i> Fermer le ticket
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Conversation -->
        <div class="conversation-container" style="max-height: 500px; overflow-y: auto; padding-right: 15px;">
            <?php foreach ($messages as $msg): ?>
                <?php $is_admin = ($msg['role'] === 'admin'); ?>
                <div class="d-flex mb-4 <?php echo $is_admin ? 'justify-content-end' : 'justify-content-start'; ?>">
                    <div class="d-flex gap-3 max-w-75 <?php echo $is_admin ? 'flex-row-reverse' : ''; ?>">
                        <div class="avatar-sm rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden shadow-sm" style="width: 40px; height: 40px;">
                            <?php if (!empty($msg['photo_path'])): ?>
                                <img src="../<?php echo htmlspecialchars($msg['photo_path']); ?>" alt="Photo" class="w-100 h-100 object-fit-cover">
                            <?php else: ?>
                                <i class="bi <?php echo $is_admin ? 'bi-shield-lock-fill text-primary' : 'bi-person-fill text-secondary'; ?> fs-5"></i>
                            <?php endif; ?>
                        </div>
                        <div class="message-bubble <?php echo $is_admin ? 'bg-primary text-white' : 'bg-light text-dark border'; ?> p-3 rounded-4 shadow-sm" style="border-radius: <?php echo $is_admin ? '1rem 1rem 0 1rem' : '1rem 1rem 1rem 0'; ?>;">
                            <div class="small fw-bold mb-1 <?php echo $is_admin ? 'text-white-50' : 'text-muted'; ?>">
                                <?php echo htmlspecialchars($msg['nom']); ?> <span class="fw-normal ms-2 text-xs"><?php echo date('d/m/Y H:i', strtotime($msg['date_envoi'])); ?></span>
                            </div>
                            <div style="white-space: pre-wrap;"><?php echo htmlspecialchars($msg['message']); ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Formulaire de réponse -->
        <?php if ($ticket['statut'] === 'ouvert'): ?>
            <div class="mt-4 pt-4 border-top">
                <form method="POST" action="ticket_detail.php?id=<?php echo $id_ticket; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="repondre">
                    <div class="mb-3">
                        <label for="message" class="form-label-pro">Votre réponse</label>
                        <textarea class="form-control-pro" id="message" name="message" rows="4" required placeholder="Tapez votre message ici..."></textarea>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-5 py-2">
                            Envoyer la réponse <i class="bi bi-send ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="mt-4 pt-4 border-top text-center">
                <div class="alert alert-secondary d-inline-block px-4 py-3 rounded-4">
                    <i class="bi bi-info-circle me-2"></i> Ce ticket est fermé. Aucune réponse supplémentaire ne peut être envoyée.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-scroll to bottom of conversation
document.addEventListener("DOMContentLoaded", function() {
    var container = document.querySelector('.conversation-container');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
});
</script>

<?php include_footer(); ?>
