<?php
require_once "../includes/layout.php";
require_once "../includes/notification_helper.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION['id'];
$id_candidature = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id_candidature <= 0) {
    send_error("ID de candidature invalide.");
}

try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.statut, c.date_candidature, c.id_candidat,
               u.nom AS candidat_nom, u.email AS candidat_email, u.telephone AS candidat_telephone,
               co.titre AS concours_titre, co.id_gerant, co.id AS id_concours
        FROM candidatures c
        JOIN utilisateurs u ON c.id_candidat = u.id
        JOIN concours co ON c.id_concours = co.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id_candidature]);
    $candidature = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidature || $candidature['id_gerant'] != $id_gerant) {
        send_error("Candidature non trouvée ou accès refusé.", 404);
    }
    
    // Check for unread messages
    $stmt_msg = $pdo->prepare("SELECT COUNT(*) FROM messages_internes WHERE candidature_id = ? AND emetteur_role = 'candidat' AND is_read = 0");
    $stmt_msg->execute([$id_candidature]);
    $unread_count = $stmt_msg->fetchColumn();

} catch (PDOException $e) { send_error("Erreur DB: " . $e->getMessage()); }

// --- ACTIONS ---
if (isset($_GET['action_doc'], $_GET['id_doc'])) {
    $id_doc = (int)$_GET['id_doc'];
    $etat = ($_GET['action_doc'] === 'valider') ? 'valide' : 'rejete';
    $pdo->prepare("UPDATE dossiers SET etat = ? WHERE id = ? AND id_candidature = ?")->execute([$etat, $id_doc, $id_candidature]);
    header("Location: etudier_dossier.php?id=$id_candidature");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    $statut_global = $_POST['statut_global'] ?? "";
    if (in_array($statut_global, ['en_attente', 'validee', 'rejetee'])) {
        $pdo->prepare("UPDATE candidatures SET statut = ? WHERE id = ?")->execute([$statut_global, $id_candidature]);
        $msg = "Votre candidature pour '" . $candidature['concours_titre'] . "' a été mise à jour : Statut " . ucfirst($statut_global);
        create_notification($candidature['id_candidat'], 'status_change', $msg, true);
        $_SESSION['success_message'] = "Décision enregistrée.";
        header("Location: etudier_dossier.php?id=$id_candidature");
        exit();
    }
}

// Data fetch
$stmt_docs = $pdo->prepare("SELECT * FROM dossiers WHERE id_candidature = ?");
$stmt_docs->execute([$id_candidature]);
$dossiers_raw = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

$candidature['cv_path'] = null;
$candidature['lettre_path'] = null;
$dossiers = [];

foreach($dossiers_raw as $doc) {
    if ($doc['type_document'] === 'cv' || $doc['type_document'] === 'curriculum_vitae') {
        $candidature['cv_path'] = $doc['nom_fichier'];
    } elseif ($doc['type_document'] === 'lettre_motivation') {
        $candidature['lettre_path'] = $doc['nom_fichier'];
    } else {
        $dossiers[] = $doc;
    }
}

$stmt_rep = $pdo->prepare("SELECT r.valeur, cf.libelle, cf.type_champ FROM reponses_candidature r JOIN champs_formulaire cf ON r.id_champ = cf.id WHERE r.id_candidature = ? ORDER BY cf.ordre ASC");
$stmt_rep->execute([$id_candidature]);
$reponses = $stmt_rep->fetchAll(PDO::FETCH_ASSOC);

include_header("Étude : " . $candidature['candidat_nom']);
?>

<div class="mesh-bg"></div>

<style>
.dossier-card { background: white; border-radius: var(--radius-xl); border: 1px solid var(--color-gray-100); padding: 2.5rem; }
.doc-item { background: var(--color-gray-50); border: 1px solid var(--color-gray-100); border-radius: var(--radius-lg); padding: 1.25rem; transition: all 0.2s; }
.doc-item:hover { border-color: var(--color-primary); background: white; }
.sticky-decision { position: sticky; top: 2rem; }
</style>

<div class="container-modern py-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-5 anim-up">
        <div>
            <a href="candidatures.php?id_concours=<?php echo $candidature['id_concours']; ?>" class="btn btn-sm btn-white border rounded-pill px-3 mb-3 fw-bold"><i class="bi bi-arrow-left"></i> Retour à la liste</a>
            <h1 class="display-6 fw-black text-gray-900 mb-1">Étude du Dossier Talents</h1>
            <p class="text-muted mb-0">Évaluation de <span class="text-success fw-bold"><?php echo htmlspecialchars($candidature['candidat_nom']); ?></span> pour <span class="fw-bold"><?php echo htmlspecialchars($candidature['concours_titre']); ?></span></p>
        </div>
        <div class="stat-icon-luminous">
            <i class="bi bi-person-badge-fill"></i>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8 anim-up anim-delay-1">
            <div class="dossier-card mb-4">
                <h4 class="fw-black text-gray-900 mb-4 lh-1 d-flex align-items-center gap-2">
                    <i class="bi bi-info-circle text-success"></i> Informations Candidat
                </h4>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted text-uppercase ls-1">Nom Complet</label>
                        <div class="fs-5 fw-black text-gray-900"><?php echo htmlspecialchars($candidature['candidat_nom']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted text-uppercase ls-1">Email de contact</label>
                        <div class="text-success fw-bold"><?php echo htmlspecialchars($candidature['candidat_email']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted text-uppercase ls-1">Téléphone</label>
                        <div class="fw-bold"><?php echo htmlspecialchars($candidature['candidat_telephone'] ?? 'Non fourni'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="small fw-bold text-muted text-uppercase ls-1">Date de Candidature</label>
                        <div class="fw-bold"><?php echo format_date($candidature['date_candidature'], true); ?></div>
                    </div>
                </div>
            </div>

            <div class="dossier-card mb-4">
                <h4 class="fw-black text-gray-900 mb-4 lh-1 d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-check text-success"></i> Pièces Justificatives
                </h4>
                <div class="row g-3">
                    <?php if(empty($dossiers) && empty($candidature['cv_path']) && empty($candidature['lettre_path'])): ?>
                        <div class="col-12 py-4 text-center text-danger fw-bold">Aucun document téléversé.</div>
                    <?php else: ?>
                        <!-- DOCUMENTS MAJEURS -->
                        <?php if($candidature['cv_path']): ?>
                        <div class="col-12">
                            <div class="p-3 border-start border-success border-4 bg-success bg-opacity-5 rounded-3 mb-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small fw-bold text-success mb-0"><i class="bi bi-file-earmark-person-fill"></i> CURRICULUM VITAE (MAJEUR)</div>
                                    <div class="smaller text-muted">Document principal envoyé pour ce poste.</div>
                                </div>
                                <a href="../uploads/<?php echo $candidature['cv_path']; ?>" target="_blank" class="btn btn-pro btn-pro-primary btn-pro-sm shadow-sm">CONSULTER LE CV</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if($candidature['lettre_path']): ?>
                        <div class="col-12">
                            <div class="p-3 border-start border-info border-4 bg-info bg-opacity-5 rounded-3 mb-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small fw-bold text-info mb-0"><i class="bi bi-file-earmark-medical-fill"></i> LETTRE DE MOTIVATION (MAJEUR)</div>
                                    <div class="smaller text-muted">Lettre spécifique à cette opportunité.</div>
                                </div>
                                <a href="../uploads/<?php echo $candidature['lettre_path']; ?>" target="_blank" class="btn btn-pro btn-pro-secondary btn-pro-sm shadow-sm">LIRE LA LETTRE</a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-12"><hr class="opacity-5 my-2"></div>

                        <!-- DOCUMENTS SECONDAIRES (Diplômes, etc) -->
                        <?php foreach($dossiers as $doc): ?>
                            <div class="col-12 col-md-6">
                                <div class="doc-item h-100 d-flex flex-column justify-content-between">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="fw-bold text-capitalize text-gray-900" style="font-size: 0.85rem;">
                                            <i class="bi bi-file-earmark-pdf me-1"></i><?php echo str_replace('_', ' ', $doc['type_document']); ?>
                                        </div>
                                        <?php if($doc['etat'] === 'valide'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill" style="font-size: 0.6rem;">VALIDÉ</span>
                                        <?php elseif($doc['etat'] === 'rejete'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill" style="font-size: 0.6rem;">REJETÉ</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="../uploads/<?php echo $doc['nom_fichier']; ?>" target="_blank" class="btn btn-sm btn-white border px-3 rounded-pill fw-bold">Ouvrir</a>
                                        <div class="ms-auto d-flex gap-1">
                                            <a href="?id=<?php echo $id_candidature; ?>&action_doc=valider&id_doc=<?php echo $doc['id']; ?>" class="btn btn-sm btn-success rounded-circle" style="width:28px; height:28px; padding:0;"><i class="bi bi-check" style="font-size: 1.2rem;"></i></a>
                                            <a href="?id=<?php echo $id_candidature; ?>&action_doc=rejeter&id_doc=<?php echo $doc['id']; ?>" class="btn btn-sm btn-danger rounded-circle" style="width:28px; height:28px; padding:0;"><i class="bi bi-x" style="font-size: 1.2rem;"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <?php if(!empty($reponses)): ?>
            <div class="dossier-card">
                <h4 class="fw-black text-gray-900 mb-4 lh-1 d-flex align-items-center gap-2">
                    <i class="bi bi-list-stars text-success"></i> Questionnaire de l'Opportunité
                </h4>
                <div class="row g-4">
                    <?php foreach($reponses as $rep): ?>
                        <div class="col-12">
                            <label class="small fw-bold text-muted text-uppercase mb-1"><?php echo htmlspecialchars($rep['libelle']); ?></label>
                            <div class="p-3 bg-light rounded-3 border fw-semibold text-gray-800">
                                <?php if ($rep['type_champ'] === 'fichier'): ?>
                                    <a href="../uploads/<?php echo $rep['valeur']; ?>" target="_blank" class="text-success"><i class="bi bi-file-earmark-arrow-down me-2"></i>Télécharger la pièce jointe</a>
                                <?php else: ?>
                                    <?php echo nl2br(htmlspecialchars($rep['valeur'])); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar / Decision -->
        <div class="col-lg-4 anim-up anim-delay-2">
            <div class="glass-premium p-4 rounded-4 sticky-top" style="top: 2rem;">
                <h5 class="fw-black text-gray-900 mb-4">Décision de Recrutement</h5>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $id_candidature; ?>">
                    
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-2">Statut du dossier</label>
                        <select name="statut_global" class="form-select border-2 fw-black text-gray-900">
                            <option value="en_attente" <?php echo $candidature['statut'] === 'en_attente' ? 'selected' : ''; ?>>🕒 En attente</option>
                            <option value="validee" <?php echo $candidature['statut'] === 'validee' ? 'selected' : ''; ?>>🎉 Validée (Shortlist)</option>
                            <option value="rejetee" <?php echo $candidature['statut'] === 'rejetee' ? 'selected' : ''; ?>>🚫 Rejetée</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-3 rounded-pill fw-black shadow-premium">
                        Mettre à jour le statut <i class="bi bi-check-all ms-2"></i>
                    </button>
                    
                    <?php if ($candidature['statut'] === 'validee'): ?>
                        <div class="mt-3 p-3 bg-light rounded-4 border border-warning border-opacity-25" style="background: linear-gradient(to bottom, #fffcf0, #fff7d6) !important;">
                            <div class="small fw-black text-warning mb-2"><i class="bi bi-calendar-event me-1"></i> ÉTAPE SUIVANTE</div>
                            <p class="smaller text-muted mb-3">Le candidat est dans votre shortlist. Vous pouvez maintenant planifier une épreuve ou un entretien.</p>
                            <a href="planifier_entretien.php?id=<?php echo $id_candidature; ?>" class="btn btn-sm btn-warning w-100 fw-black py-2 rounded-pill shadow-sm">
                                <i class="bi bi-calendar-plus me-2"></i> Planifier l'entretien
                            </a>
                        </div>
                    <?php endif; ?>

                    <hr class="opacity-5 my-4">
                    
                    <a href="messagerie.php?id=<?php echo $id_candidature; ?>" class="btn btn-outline-primary w-100 py-3 rounded-pill fw-bold shadow-sm position-relative d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-chat-dots-fill fs-5"></i> 
                        Discuter avec le candidat
                        <?php if ($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="z-index: 5;">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
include_footer();
exit();
?>

