<?php
require_once "../includes/layout.php";
check_role('candidat');
$id_candidat = $_SESSION['id'];
$id_candidature = (int)($_GET['id'] ?? 0);

if ($id_candidature <= 0) send_error("ID invalide.");

// Vérifier permission candidat + Jointure Concours (au lieu de offres)
$stmt = $pdo->prepare("
    SELECT c.id, co.titre, co.id_gerant 
    FROM candidatures c
    JOIN concours co ON c.id_concours = co.id
    WHERE c.id = ? AND c.id_candidat = ?
");
$stmt->execute([$id_candidature, $id_candidat]);
$cand = $stmt->fetch(PDO::FETCH_ASSOC);

require_once "../includes/notification_helper.php";

if (!$cand) {
    $_SESSION['error_message'] = "Candidature introuvable.";
    header("Location: mes_candidatures.php");
    exit();
}

// Marquer comme lus les messages du gerant
$pdo->prepare("UPDATE messages_internes SET is_read = 1 WHERE candidature_id = ? AND emetteur_role = 'gerant'")->execute([$id_candidature]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    if (!empty($msg)) {
        $pdo->prepare("INSERT INTO messages_internes (candidature_id, emetteur_role, emetteur_id, contenu) VALUES (?, 'candidat', ?, ?)")
            ->execute([$id_candidature, $id_candidat, $msg]);
            
        // Notification pour le gérant (Recruteur)
        create_notification((int)$cand['id_gerant'], 'new_message', "Nouveau message de " . $_SESSION['nom'] . " pour l'offre: " . $cand['titre']);
            
        header("Location: messagerie.php?id=$id_candidature");
        exit();
    }
}

$msgs = $pdo->prepare("SELECT * FROM messages_internes WHERE candidature_id = ? ORDER BY date_envoi ASC");
$msgs->execute([$id_candidature]);
$messages = $msgs->fetchAll(PDO::FETCH_ASSOC);

include_header(__t('recruitment_discussion'));
?>
<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            <div class="card border-0 shadow-premium rounded-4 overflow-hidden bg-white anim-up">
                <!-- Header -->
                <div class="p-4 bg-success text-white d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white bg-opacity-20 rounded-circle d-flex align-items-center justify-content-center" style="width:48px; height:48px;">
                            <i class="bi bi-chat-left-text-fill fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-black mb-0"><?php echo __t('recruitment_team'); ?></h5>
                            <div class="small opacity-75"><?php echo __t('Titre'); ?> : <?php echo htmlspecialchars($cand['titre']); ?></div>
                        </div>
                    </div>
                    <a href="mes_candidatures.php" class="btn btn-sm btn-white rounded-pill px-3 fw-bold">
                        <i class="bi bi-arrow-left"></i> <?php echo __t('applications'); ?>
                    </a>
                </div>
                
                <!-- Chat Area -->
                <div class="messagerie-scroll-area p-4 bg-light bg-opacity-50" id="chatBox" style="height: 500px; overflow-y: auto;">
                    <div id="messagesContainer" class="d-flex flex-column gap-3">
                        <!-- Messages will be loaded here via AJAX -->
                    </div>
                </div>
                
                <!-- Input Area -->
                <div class="p-4 border-top bg-white">
                    <form method="POST" class="d-flex gap-2">
                        <input type="text" name="message" class="form-control form-control-lg border-0 bg-light rounded-pill px-4" 
                               placeholder="<?php echo __t('write_message_placeholder'); ?>" required autofocus autocomplete="off" style="font-size: 0.95rem;">
                        <button type="submit" class="btn btn-success rounded-circle d-flex align-items-center justify-content-center" style="width:48px; height:48px; flex-shrink:0;">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.max-w-75 { max-width: 75%; }
.message-bubble { 
    position: relative;
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
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
                <div class="d-flex ${m.is_me ? 'justify-content-end' : 'justify-content-start'}">
                    <div class="message-bubble ${m.is_me ? 'bg-success text-white' : 'bg-white shadow-sm border'} p-3 rounded-4 max-w-75">
                        <div class="lh-sm mb-1">${m.contenu.replace(/\n/g, '<br>')}</div>
                        <div class="d-flex align-items-center justify-content-end gap-1 opacity-50" style="font-size: 0.65rem;">
                            ${m.time}
                            ${m.is_me ? `<i class="bi ${m.is_read == 1 ? 'bi-check2-all' : 'bi-check2'}"></i>` : ''}
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

