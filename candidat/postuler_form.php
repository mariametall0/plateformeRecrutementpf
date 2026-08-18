<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_candidat = $_SESSION["id"];
$id_concours = (int)($_GET["id"] ?? 0);

if ($id_concours <= 0) {
    header("Location: liste_opportunites.php");
    exit();
}

try {
    // Vérifier l'opportunité (concours)
    $stmt = $pdo->prepare("SELECT * FROM concours WHERE id = ? AND statut = 'actif' AND date_cloture >= CURDATE() AND date_ouverture <= CURDATE()");
    $stmt->execute([$id_concours]);
    $opportunite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$opportunite) {
        $_SESSION['error_message'] = "Cette opportunité n'est plus ouverte aux candidatures.";
        header("Location: liste_opportunites.php");
        exit();
    }

    // Vérifier si déjà postulé
    $stmt = $pdo->prepare("SELECT id FROM candidatures WHERE id_candidat = ? AND id_concours = ?");
    $stmt->execute([$id_candidat, $id_concours]);
    if ($stmt->fetch()) {
        header("Location: details_opportunite.php?id=$id_concours");
        exit();
    }

    // Charger les champs dynamiques
    $stmt_champs = $pdo->prepare("SELECT * FROM champs_formulaire WHERE id_concours = ? ORDER BY ordre ASC");
    $stmt_champs->execute([$id_concours]);
    $champs = $stmt_champs->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    send_error("Erreur technique de base de données.");
}

include_header("Postuler : " . $opportunite['titre']);
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <!-- Form Header -->
            <div class="glass-premium p-4 rounded-4 mb-5 d-flex justify-content-between align-items-center anim-up">
                <div>
                    <h1 class="h3 fw-black text-gray-900 mb-1"><?php echo __t('Dossier de Candidature'); ?></h1>
                    <p class="text-success small fw-bold mb-0"><?php echo htmlspecialchars($opportunite['titre']); ?></p>
                </div>
                <div class="stat-icon-mini bg-success-light text-success">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
            </div>

            <form action="candidater.php" method="POST" enctype="multipart/form-data" class="bg-white shadow-premium rounded-4 overflow-hidden anim-up anim-delay-1">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $id_concours; ?>">

                <div class="p-5">
                    <!-- Standard Documents Section -->
                    <div class="mb-5">
                        <h5 class="fw-black text-gray-900 mb-4 d-flex align-items-center gap-3">
                            <span class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:30px; height:30px; font-size:0.8rem;">1</span>
                            <?php echo __t('Documents de Base'); ?>
                        </h5>
                        
                        <div class="row g-4">
                            <!-- CV SECTION -->
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-0"><?php echo __t('Curriculum Vitae'); ?> <span class="text-danger">*</span></label>
                                    <div class="form-check form-switch small">
                                        <input class="form-check-input" type="checkbox" id="mode-cv-digital" name="mode_cv_digital" value="1" onchange="toggleCVMode(this.checked)">
                                        <label class="form-check-label fw-bold text-success" for="mode-cv-digital"><?php echo __t('Utiliser mon Profil'); ?></label>
                                    </div>
                                </div>
                                <div id="cv-upload-area">
                                    <input type="file" name="cv" id="cv-input" class="form-control form-control-lg rounded-3" required accept=".pdf">
                                    <div class="form-text smaller italic">Format PDF uniquement (Max 5Mo).</div>
                                </div>
                                <div id="cv-digital-area" class="d-none p-3 rounded-4 bg-success bg-opacity-5 border border-success border-opacity-10 anim-fadeIn">
                                    <div class="d-flex align-items-center gap-2 text-success small fw-black">
                                        <i class="bi bi-person-check-fill fs-5"></i> Mon CV Numérique sera généré
                                    </div>
                                    <p class="smaller text-muted mb-0 mt-1">Vos dernières modifications seront incluses automatiquement.</p>
                                </div>
                                
                                
                            </div>

                            <!-- MOTIVATION SECTION -->
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-0"><?php echo __t('Lettre de Motivation'); ?> <span class="text-danger">*</span></label>
                                    <div class="form-check form-switch small">
                                        <input class="form-check-input" type="checkbox" id="mode-lettre-online" name="mode_lettre_online" value="1" onchange="toggleMotivationMode(this.checked)">
                                        <label class="form-check-label fw-bold text-success" for="mode-lettre-online"><?php echo __t('Rédiger en ligne'); ?></label>
                                    </div>
                                </div>
                                <div id="lettre-upload-area">
                                    <input type="file" name="lettre" id="lettre-input" class="form-control form-control-lg rounded-3" required accept=".pdf,.doc,.docx">
                                    <div class="form-text smaller italic">PDF, DOC ou DOCX (Max 5Mo).</div>
                                </div>
                                <div id="lettre-online-area" class="d-none anim-fadeIn">
                                    <textarea name="lettre_texte" id="lettre-texte" class="form-control rounded-4" rows="6" placeholder="Spécifiez vos motivations ici..."></textarea>
                                    <div class="form-text smaller italic mt-1">Un PDF professionnel sera généré pour le recruteur.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2"><?php echo __t('Diplôme(s) & Certificats'); ?> <span class="text-danger">*</span></label>
                                <input type="file" name="diplome[]" class="form-control form-control-lg rounded-3" required accept=".pdf,.jpg,.jpeg,.png" multiple>
                                <div class="form-text small italic mt-2">Vous pouvez sélectionner plusieurs fichiers (diplômes, relevés de notes, etc). Max 5Mo par fichier.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Fields Section -->
                    <?php if (!empty($champs)): ?>
                    <hr class="opacity-5 mb-5">
                    <div class="mb-5">
                        <h5 class="fw-black text-gray-900 mb-4 d-flex align-items-center gap-3">
                            <span class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:30px; height:30px; font-size:0.8rem;">2</span>
                            <?php echo __t('Critères Spécifiques'); ?>
                        </h5>
                        <div class="row g-4">
                            <?php foreach ($champs as $c): ?>
                                <div class="<?php echo ($c['type_champ'] === 'textarea') ? 'col-12' : 'col-md-6'; ?>">
                                    <label class="form-label small fw-bold text-muted text-uppercase mb-2">
                                        <?php echo htmlspecialchars($c['libelle']); ?>
                                        <?php if ($c['obligatoire']): ?><span class="text-danger">*</span><?php endif; ?>
                                    </label>
                                    
                                    <?php if ($c['type_champ'] === 'texte'): ?>
                                        <input type="text" name="champ_<?php echo $c['id']; ?>" class="form-control rounded-3" <?php echo $c['obligatoire'] ? 'required' : ''; ?>>
                                    <?php elseif ($c['type_champ'] === 'nombre'): ?>
                                        <input type="number" name="champ_<?php echo $c['id']; ?>" class="form-control rounded-3" <?php echo $c['obligatoire'] ? 'required' : ''; ?>>
                                    <?php elseif ($c['type_champ'] === 'textarea'): ?>
                                        <textarea name="champ_<?php echo $c['id']; ?>" class="form-control rounded-3" rows="3" <?php echo $c['obligatoire'] ? 'required' : ''; ?>></textarea>
                                    <?php elseif ($c['type_champ'] === 'case_a_cocher'): ?>
                                        <div class="form-check p-3 bg-light rounded-3 border">
                                            <input class="form-check-input ms-0 me-3" type="checkbox" name="champ_<?php echo $c['id']; ?>" id="chk_<?php echo $c['id']; ?>">
                                            <label class="form-check-label fw-bold" for="chk_<?php echo $c['id']; ?>">Je confirme cette information</label>
                                        </div>
                                    <?php elseif ($c['type_champ'] === 'fichier'): ?>
                                        <input type="file" name="champ_<?php echo $c['id']; ?>" class="form-control rounded-3" <?php echo $c['obligatoire'] ? 'required' : ''; ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="p-4 bg-light rounded-4 border text-center mt-5">
                        <p class="small text-muted mb-4 italic">En cliquant sur "Envoyer ma candidature", vous certifiez sur l'honneur l'exactitude des informations fournies.</p>
                        <button type="submit" id="btn-submit" class="btn-premium px-5 py-3 shadow-luminous w-100">
                            <?php echo __t('Envoyer mon dossier'); ?> <i class="bi bi-rocket-takeoff-fill ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Gestion des modes (Digital vs Upload)
function toggleCVMode(isDigital) {
    const uploadArea = document.getElementById('cv-upload-area');
    const digitalArea = document.getElementById('cv-digital-area');
    const cvInput = document.getElementById('cv-input');

    if (isDigital) {
        uploadArea.classList.add('d-none');
        digitalArea.classList.remove('d-none');
        cvInput.required = false;
    } else {
        uploadArea.classList.remove('d-none');
        digitalArea.classList.add('d-none');
        cvInput.required = true;
    }
}

function toggleMotivationMode(isOnline) {
    const uploadArea = document.getElementById('lettre-upload-area');
    const onlineArea = document.getElementById('lettre-online-area');
    const uploadInput = document.getElementById('lettre-input');
    const onlineInput = document.getElementById('lettre-texte');

    if (isOnline) {
        uploadArea.classList.add('d-none');
        onlineArea.classList.remove('d-none');
        uploadInput.required = false;
        onlineInput.required = true;
    } else {
        uploadArea.classList.remove('d-none');
        onlineArea.classList.add('d-none');
        uploadInput.required = true;
        onlineInput.required = false;
    }
}

document.querySelector('form').addEventListener('submit', function() {
    document.getElementById('btn-submit').disabled = true;
    document.getElementById('btn-submit').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + '<?php echo __t("Envoi en cours..."); ?>';
});
</script>

<?php 
include_footer();
exit();
?>

