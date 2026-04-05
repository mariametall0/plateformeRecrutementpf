<?php
require_once "../includes/layout.php";
check_role('gerant');
$id_gerant = $_SESSION['id'];
$id_candidature = (int)($_GET['id'] ?? 0);

if ($id_candidature <= 0) send_error("ID de candidature invalide.");

// Vérifier permission gérant
$stmt = $pdo->prepare("
    SELECT c.id, u.nom, co.titre 
    FROM candidatures c
    JOIN utilisateurs u ON c.id_candidat = u.id
    JOIN offres co ON c.id_offre = co.id
    WHERE c.id = ? AND co.id_gerant = ?
");
$stmt->execute([$id_candidature, $id_gerant]);
$cand = $stmt->fetch(PDO::FETCH_ASSOC);

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
            <div class="bg-primary text-white p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1 text-white fw-bold"><i class="bi bi-chat-left-dots me-2"></i> Chat avec <?php echo htmlspecialchars($cand['nom']); ?></h5>
                    <div class="small opacity-75">Offre : <?php echo htmlspecialchars($cand['titre']); ?></div>
                </div>
                <a href="etudier_dossier.php?id=<?php echo $id_candidature; ?>" class="btn btn-sm btn-light text-primary rounded-pill fw-bold px-3 shadow-none">
                    <i class="bi bi-person-lines-fill me-1"></i> Dossier
                </a>
            </div>
            
            <div class="card-body p-4" style="height: 500px; overflow-y: auto; background: #f8fafc;" id="chatBox">
                <?php if (empty($messages)): ?>
                    <div class="text-center text-muted my-5">
                        <i class="bi bi-chat-dots display-1 opacity-25"></i>
                        <p class="mt-3 fw-medium">Aucun message pour le moment. Démarrez la conversation !</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $m): 
                        $is_me = ($m['emetteur_role'] === 'gerant');
                    ?>
                        <div class="d-flex <?php echo $is_me ? 'justify-content-end' : 'justify-content-start'; ?> mb-3 animate__animated animate__fadeInUp" style="animation-duration: 0.3s;">
                            <div class="p-3 rounded-4 shadow-sm" style="max-width: 75%; <?php echo $is_me ? 'background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); color: white; border-bottom-right-radius: 4px;' : 'background: white; border: 1px solid rgba(0,0,0,0.05); border-bottom-left-radius: 4px;'; ?>">
                                <div class="mb-1 fw-medium" style="line-height: 1.4;"><?php echo nl2br(htmlspecialchars($m['contenu'])); ?></div>
                                <div class="small mt-2 text-end d-flex align-items-center justify-content-end gap-1" style="font-size: 0.65rem; <?php echo $is_me ? 'color: rgba(255,255,255,0.7);' : 'color: #94a3b8;'; ?>">
                                    <?php echo format_date($m['date_envoi'], true); ?>
                                    <?php if ($is_me): ?> 
                                        <i class="bi <?php echo $m['is_read'] ? 'bi-check2-all text-white' : 'bi-check2'; ?> ms-1" style="font-size:0.9rem;"></i> 
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="card-footer bg-white border-top p-3 px-4">
                <form method="POST" class="d-flex gap-2">
                    <input type="text" name="message" class="form-control form-control-lg rounded-pill bg-light border-0 px-4 shadow-none fs-6" placeholder="Tapez votre message ici..." required autofocus autocomplete="off">
                    <button type="submit" class="btn btn-primary rounded-circle shadow hover-lift-lg" style="width: 50px; height: 50px; flex-shrink:0; display:flex; align-items:center; justify-content:center;">
                        <i class="bi bi-send-fill ms-1"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
const chatBox = document.getElementById('chatBox');
chatBox.scrollTop = chatBox.scrollHeight;
</script>
<?php include_footer(); ?>
