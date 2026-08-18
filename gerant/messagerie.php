<?php
require_once "../includes/layout.php";
check_role('gerant');
$id_gerant = $_SESSION['id'];
$id_candidature = (int)($_GET['id'] ?? 0);

if ($id_candidature <= 0) send_error("ID de candidature invalide.");

// Vérifier permission gérant
$stmt = $pdo->prepare("
    SELECT c.id, u.nom, co.titre, c.id_candidat 
    FROM candidatures c
    JOIN utilisateurs u ON c.id_candidat = u.id
    JOIN concours co ON c.id_concours = co.id
    WHERE c.id = ? AND co.id_gerant = ?
");
$stmt->execute([$id_candidature, $id_gerant]);
$cand = $stmt->fetch(PDO::FETCH_ASSOC);

require_once "../includes/notification_helper.php";

if (!$cand) {
    $_SESSION['error_message'] = "Candidature introuvable ou accès refusé.";
    header("Location: candidatures.php");
    exit();
}

// Marquer comme lus les messages du candidat
$pdo->prepare("UPDATE messages_internes SET is_read = 1 WHERE candidature_id = ? AND emetteur_role = 'candidat'")->execute([$id_candidature]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    if (!empty($msg)) {
        $pdo->prepare("INSERT INTO messages_internes (candidature_id, emetteur_role, emetteur_id, contenu) VALUES (?, 'gerant', ?, ?)")
            ->execute([$id_candidature, $id_gerant, $msg]);
            
        // Notification pour le candidat
        create_notification((int)$cand['id_candidat'], 'new_message', "L'équipe recrutement vous a envoyé un message concernant l'offre: " . $cand['titre']);
            
        header("Location: messagerie.php?id=$id_candidature");
        exit();
    }
}

$msgs = $pdo->prepare("SELECT * FROM messages_internes WHERE candidature_id = ? ORDER BY date_envoi ASC");
$msgs->execute([$id_candidature]);
$messages = $msgs->fetchAll(PDO::FETCH_ASSOC);

include_header("Discussion Candidature");
?>
<div class="row justify-content-center pt-4 mb-5 animate__animated animate__fadeIn">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="bg-success text-white p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 text-white fw-bold"><i class="bi bi-chat-left-dots me-2"></i> Chat avec <?php echo htmlspecialchars($cand['nom']); ?></h5>
                    <div class="small opacity-75">Opportunité : <?php echo htmlspecialchars($cand['titre']); ?></div>
                </div>
                <a href="etudier_dossier.php?id=<?php echo $id_candidature; ?>" class="btn btn-sm btn-light text-success rounded-pill fw-bold px-3 shadow-none">
                    <i class="bi bi-person-lines-fill me-1"></i> Dossier
                </a>
            </div>
            
            <div class="card-body p-4 messagerie-scroll-area" id="chatBox" style="height: 500px; overflow-y: auto;">
                <div id="messagesContainer" class="d-flex flex-column gap-3">
                    <!-- Messages loaded via AJAX -->
                </div>
            </div>
            
            <div class="card-footer bg-white border-top p-3 px-4">
                <form method="POST" class="d-flex gap-2">
                    <input type="text" name="message" class="form-control form-control-lg rounded-pill bg-light border-0 px-4 shadow-none fs-6" placeholder="Tapez votre message ici..." required autofocus autocomplete="off">
                    <button type="submit" class="btn btn-success rounded-circle shadow hover-lift-lg btn-circle-fixed">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<style>
.message-bubble { padding: 12px 18px; border-radius: 20px; max-width: 75%; }
.message-bubble-sent { background: var(--brand); color: #fff; border-bottom-right-radius: 4px; }
.message-bubble-received { background: #f1f5f9; color: var(--text); border-bottom-left-radius: 4px; border: 1px solid rgba(0,0,0,0.05); }
.messagerie-scroll-area { scroll-behavior: smooth; }
</style>
<script>
const chatBox = document.getElementById('chatBox');
const container = document.getElementById('messagesContainer');
const idCand = <?php echo $id_candidature; ?>;
let lastMsgCount = 0;

function refreshMessages() {
    fetch(`../includes/fetch_messages_ajax.php?id=${idCand}`)
        .then(r => r.json())
        .then(data => {
            if (data.error) return;
            if (data.length === lastMsgCount) return;

            container.innerHTML = data.map(m => `
                <div class="d-flex ${m.is_me ? 'justify-content-end' : 'justify-content-start'} mb-3">
                    <div class="message-bubble ${m.is_me ? 'message-bubble-sent' : 'message-bubble-received'}">
                        <div class="mb-1 fw-medium" style="line-height: 1.4;">${m.contenu.replace(/\n/g, '<br>')}</div>
                        <div class="d-flex align-items-center justify-content-end gap-1 opacity-50" style="font-size: 0.65rem;">
                            ${m.time}
                            ${m.is_me ? `<i class="bi ${m.is_read == 1 ? 'bi-check2-all' : 'bi-check2'}" style="font-size: 0.8rem;"></i>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');

            if (data.length > lastMsgCount) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
            lastMsgCount = data.length;
        });
}

setInterval(refreshMessages, 3000);
refreshMessages();
</script>
<?php include_footer(); ?>

