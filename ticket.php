<?php
require_once "includes/layout.php";

// Protection : utilisateur connecté
if (!isset($_SESSION["id"]) || !in_array($_SESSION["role"], ['candidat', 'gerant'])) {
    header("Location: choix_connexion.php");
    exit();
}

$id_utilisateur = $_SESSION["id"];
$id_ticket = $_GET['id'] ?? null;

if (!$id_ticket) {
    header("Location: support.php");
    exit();
}

// Vérifier que le ticket appartient à l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT * FROM tickets_support WHERE id = ? AND id_utilisateur = ?");
    $stmt->execute([$id_ticket, $id_utilisateur]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        header("Location: support.php");
        exit();
    }
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}

// Traitement de la réponse
if ($_SERVER["REQUEST_METHOD"] === "POST" && $ticket['statut'] === 'ouvert') {
    verify_csrf_token();
    
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages_support (id_ticket, id_emetteur, message) VALUES (?, ?, ?)");
            $stmt->execute([$id_ticket, $id_utilisateur, $message]);
            $_SESSION['success_message'] = "Votre réponse a été envoyée.";
            header("Location: ticket.php?id=$id_ticket");
            exit();
        } catch (PDOException $e) {
            send_error("Erreur d'envoi : " . $e->getMessage());
        }
    }
}

// Récupérer les messages
try {
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
    $messages = [];
}

include_header("Ticket : " . htmlspecialchars($ticket['sujet']));
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="mb-4">
        <a href="support.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left me-2"></i> Mes tickets</a>
    </div>

    <!-- Header du ticket -->
    <div class="bg-white p-4 p-md-5 rounded-4 shadow-premium mb-4 anim-up">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4 border-bottom pb-4 mb-4">
            <div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <?php if ($ticket['statut'] === 'ouvert'): ?>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="bi bi-envelope-open me-1"></i> Ouvert</span>
                    <?php else: ?>
                        <span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i> Fermé</span>
                    <?php endif; ?>
                    <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i> Créé le <?php echo date('d/m/Y à H:i', strtotime($ticket['date_creation'])); ?></span>
                </div>
                <h2 class="fw-black text-gray-900 mb-0"><?php echo htmlspecialchars($ticket['sujet']); ?></h2>
            </div>
            <?php if ($ticket['statut'] === 'ouvert'): ?>
                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Un administrateur vous répondra sous peu.
                </div>
            <?php endif; ?>
        </div>

        <!-- Conversation -->
        <div class="conversation-container" style="max-height: 500px; overflow-y: auto; padding-right: 15px;">
            <?php foreach ($messages as $msg): ?>
                <?php $is_me = ($msg['id_emetteur'] == $id_utilisateur); ?>
                <div class="d-flex mb-4 <?php echo $is_me ? 'justify-content-end' : 'justify-content-start'; ?>">
                    <div class="d-flex gap-3 max-w-75 <?php echo $is_me ? 'flex-row-reverse' : ''; ?>">
                        <div class="avatar-sm rounded-circle flex-shrink-0 d-flex align-items-center justify-content-center overflow-hidden shadow-sm" style="width: 40px; height: 40px;">
                            <?php if ($msg['role'] === 'admin'): ?>
                                <i class="bi bi-shield-lock-fill text-danger fs-5"></i>
                            <?php elseif (!empty($msg['photo_path'])): ?>
                                <img src="<?php echo htmlspecialchars($msg['photo_path']); ?>" alt="Photo" class="w-100 h-100 object-fit-cover">
                            <?php else: ?>
                                <i class="bi bi-person-fill text-primary fs-5"></i>
                            <?php endif; ?>
                        </div>
                        <div class="message-bubble <?php echo $is_me ? 'bg-primary text-white' : 'bg-light text-dark border'; ?> p-3 rounded-4 shadow-sm" style="border-radius: <?php echo $is_me ? '1rem 1rem 0 1rem' : '1rem 1rem 1rem 0'; ?>;">
                            <div class="small fw-bold mb-1 <?php echo $is_me ? 'text-white-50' : 'text-muted'; ?>">
                                <?php echo $is_me ? 'Moi' : 'Administrateur'; ?> <span class="fw-normal ms-2 text-xs"><?php echo date('d/m/Y H:i', strtotime($msg['date_envoi'])); ?></span>
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
                <form method="POST" action="ticket.php?id=<?php echo $id_ticket; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="mb-3">
                        <label for="message" class="form-label-pro">Ajouter un message</label>
                        <textarea class="form-control-pro" id="message" name="message" rows="3" required placeholder="Votre message..."></textarea>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary rounded-pill px-5 py-2">
                            Envoyer <i class="bi bi-send ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="mt-4 pt-4 border-top text-center">
                <div class="alert alert-secondary d-inline-block px-4 py-3 rounded-4">
                    <i class="bi bi-lock-fill me-2"></i> Ce ticket a été fermé par l'administration.
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
