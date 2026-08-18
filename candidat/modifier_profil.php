<?php
/**
 * modifier_profil.php – Modification du profil candidat.
 */
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$user_id = $_SESSION["id"];

// Traitement POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    $nom              = trim($_POST["nom"] ?? "");
    $email            = trim($_POST["email"] ?? "");
    $telephone        = trim($_POST["telephone"] ?? "");
    $adresse          = trim($_POST["adresse"] ?? "");
    $pays             = trim($_POST["pays"] ?? "");
    $secteur          = trim($_POST["secteur"] ?? "");
    $niveau_etude     = trim($_POST["niveau_etude"] ?? "");
    $date_naissance   = trim($_POST["date_naissance"] ?? "");
    $bio              = trim($_POST["bio"] ?? "");

    if (empty($nom) || empty($email)) {
        send_error("Le nom et l'email sont obligatoires pour valider votre profil.");
    } else {
        try {
            $params_profil = [];
            $cv_sql = "";
            $photo_sql = "";
            
            // Upload CV
            if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                $new_cv = handle_file_upload($_FILES['cv'], 'cvs');
                if ($new_cv) {
                    $cv_sql = ", cv_path = ?";
                    $params_profil['cv'] = $new_cv;
                }
            }

            // Upload Photo
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $new_photo = handle_file_upload($_FILES['photo'], 'photos');
                if ($new_photo) {
                    $photo_sql = ", photo_path = ?";
                    $params_profil['photo'] = $new_photo;
                }
            }

            // Vérification email unique
            $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $check->execute([$email, $user_id]);
            if ($check->rowCount() > 0) {
                send_error("Cette adresse email est déjà associée à un autre compte.");
            } else {
                $pdo->beginTransaction();
                
                // 1. Table Utilisateurs
                $stmt_u = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, telephone = ?, adresse = ?, pays = ? WHERE id = ?");
                $stmt_u->execute([$nom, $email, $telephone, $adresse, $pays, $user_id]);
                
                // 2. Table Profils Candidats
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM profils_candidats WHERE id_utilisateur = ?");
                $stmt_check->execute([$user_id]);
                $exists = $stmt_check->fetchColumn() > 0;
                
                if ($exists) {
                    $sql_p = "UPDATE profils_candidats SET secteur_specialite = ?, niveau_etude = ?, date_naissance = ?, bio = ?";
                    $params_p = [$secteur, $niveau_etude, $date_naissance, $bio];
                    
                    if (!empty($cv_sql)) { $sql_p .= $cv_sql; $params_p[] = $params_profil['cv']; }
                    if (!empty($photo_sql)) { $sql_p .= $photo_sql; $params_p[] = $params_profil['photo']; }
                    
                    $sql_p .= " WHERE id_utilisateur = ?";
                    $params_p[] = $user_id;
                    $pdo->prepare($sql_p)->execute($params_p);
                } else {
                    $cv_val = $params_profil['cv'] ?? null;
                    $photo_val = $params_profil['photo'] ?? null;
                    $sql_p = "INSERT INTO profils_candidats (id_utilisateur, secteur_specialite, niveau_etude, date_naissance, bio, cv_path, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $pdo->prepare($sql_p)->execute([$user_id, $secteur, $niveau_etude, $date_naissance, $bio, $cv_val, $photo_val]);
                }

                $pdo->commit();
                
                if (!empty($params_profil['photo'])) $_SESSION['photo_path'] = $params_profil['photo'];
                $_SESSION["nom"] = $nom;
                $_SESSION['success_message'] = "Votre profil a été mis à jour avec succès.";
                header("Location: modifier_profil.php");
                exit();
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            send_error("Une erreur est survenue lors de l'enregistrement de vos modifications.");
        }
    }
}

// Données initiales
try {
    $stmt = $pdo->prepare("
        SELECT u.*, p.id AS profil_id, p.secteur_specialite AS secteur, p.niveau_etude, p.date_naissance, p.bio, p.cv_path, p.photo_path
        FROM utilisateurs u
        LEFT JOIN profils_candidats p ON u.id = p.id_utilisateur
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Stats CV numérique (Requêtes préparées)
    $stmt_f = $pdo->prepare("SELECT COUNT(*) FROM cv_formations WHERE id_utilisateur = ?"); $stmt_f->execute([$user_id]);
    $stmt_e = $pdo->prepare("SELECT COUNT(*) FROM cv_experiences WHERE id_utilisateur = ?"); $stmt_e->execute([$user_id]);
    $stmt_c = $pdo->prepare("SELECT COUNT(*) FROM cv_competences WHERE id_utilisateur = ?"); $stmt_c->execute([$user_id]);
    $cv_stats = [
        'formations' => $stmt_f->fetchColumn(),
        'experiences' => $stmt_e->fetchColumn(),
        'competences' => $stmt_c->fetchColumn()
    ];
} catch (PDOException $e) { $user = null; $cv_stats = ['formations'=>0, 'experiences'=>0, 'competences'=>0]; }

include_header(__t('modifier_profil'));
?>

<style>
.cv-section-card {
    border-radius: 12px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    background: #fff;
    border: 1px solid rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}
.cv-section-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    border-color: #0f5132;
}
</style>

<div class="mesh-bg"></div>

<div class="container-modern py-4">
    <div class="row justify-content-center">
        <!-- Centered single column layout to align everything and eliminate empty spaces -->
        <div class="col-lg-10 col-xl-9">
            
            <!-- Header -->
            <div class="cv-section-card shadow-sm d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 fw-black text-gray-900 mb-1"><?php echo __t('my_candidate_profile'); ?> <span class="badge bg-light text-secondary border fs-6 ms-2">Elite</span></h1>
                    <p class="text-muted mb-0 small"><?php echo __t('optimize_visibility_subtext'); ?></p>
                </div>
                <div class="stat-icon-luminous bg-light text-dark" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 1.5rem;"><i class="bi bi-person-circle"></i></div>
            </div>

            <!-- Completion Bar -->
            <div class="cv-section-card shadow-sm p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-bold text-uppercase ls-1"><?php echo __t('profile_completion_rate'); ?></span>
                    <span class="text-dark h5 fw-black mb-0" id="completionPercent">0%</span>
                </div>
                <div class="progress" style="height: 8px; background: #f1f5f9; border-radius: 10px;">
                    <div class="progress-bar bg-success" id="completionBar" style="width: 0%; border-radius: 10px; transition: width 0.6s ease;"></div>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" id="profileForm" class="anim-up anim-delay-2">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <!-- Contact & Bio Card -->
                <div class="cv-section-card shadow-sm p-4 mb-4">
                    <!-- Visual Identity Header -->
                    <div class="d-flex flex-column flex-md-row align-items-center gap-4 border-bottom pb-4 mb-4">
                        <div class="position-relative">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center shadow-sm overflow-hidden" style="width: 120px; height: 120px; border: 3px solid #fff;">
                                <?php if (!empty($user['photo_path'])): ?>
                                    <img src="../uploads/<?php echo $user['photo_path']; ?>" alt="Photo Profile" class="w-100 h-100 object-fit-cover shadow-inner" id="img-preview">
                                <?php else: ?>
                                    <i class="bi bi-person text-gray-300" style="font-size: 3rem;" id="placeholder-icon"></i>
                                    <img src="" alt="Aperçu" class="w-100 h-100 object-fit-cover d-none" id="img-preview">
                                <?php endif; ?>
                            </div>
                            <label for="photo-upload" class="btn btn-sm btn-light border rounded-circle position-absolute bottom-0 end-0 p-2 shadow-sm" style="cursor: pointer;">
                                <i class="bi bi-camera"></i>
                            </label>
                            <input type="file" id="photo-upload" name="photo" class="d-none" accept="image/*">
                        </div>
                        <div class="flex-grow-1 text-center text-md-start">
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($user['nom'] ?? 'Votre nom'); ?></h6>
                            <p class="text-muted small mb-0"><?php echo __t('img_upload_subtext'); ?></p>
                        </div>
                    </div>

                    <h5 class="fw-black mb-4 small text-uppercase text-muted border-bottom pb-2"><?php echo __t('contact_info'); ?></h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label-pro"><?php echo __t('full_name'); ?></label>
                            <input type="text" name="nom" class="form-control-pro" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-pro"><?php echo __t('pro_email'); ?></label>
                            <input type="email" name="email" class="form-control-pro" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-pro"><?php echo __t('Pays'); ?></label>
                            <select name="pays" id="pays_select" class="form-control-pro">
                                <option value=""><?php echo __t('choose_country'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-pro"><?php echo __t('Ville'); ?></label>
                            <select name="adresse" id="ville_select" class="form-control-pro">
                                <option value=""><?php echo __t('choose_city'); ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-pro"><?php echo __t('Téléphone'); ?></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0" id="indicatif_display">+212</span>
                                <input type="tel" name="telephone" id="telephone" class="form-control-pro border-start-0" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-pro"><?php echo __t('sector_job'); ?></label>
                            <input type="text" name="secteur" class="form-control-pro" value="<?php echo htmlspecialchars($user['secteur'] ?? ''); ?>" placeholder="<?php echo __t('sector_placeholder'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-pro"><?php echo __t('study_level'); ?></label>
                            <select name="niveau_etude" class="form-control-pro">
                                <option value=""><?php echo __t('Select'); ?></option>
                                <option value="Bac" <?php echo ($user['niveau_etude'] ?? '') === 'Bac' ? 'selected' : ''; ?>>Baccalauréat</option>
                                <option value="Licence" <?php echo ($user['niveau_etude'] ?? '') === 'Licence' ? 'selected' : ''; ?>>Licence / Bachelor</option>
                                <option value="Master" <?php echo ($user['niveau_etude'] ?? '') === 'Master' ? 'selected' : ''; ?>>Master / Ingénieur</option>
                                <option value="Doctorat" <?php echo ($user['niveau_etude'] ?? '') === 'Doctorat' ? 'selected' : ''; ?>>Doctorat</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-pro"><?php echo __t('birth_date'); ?></label>
                            <input type="date" name="date_naissance" class="form-control-pro" value="<?php echo htmlspecialchars($user['date_naissance'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label-pro"><?php echo __t('bio_label'); ?></label>
                            <textarea name="bio" class="form-control-pro" rows="4" placeholder="<?php echo __t('bio_summary_placeholder'); ?>"><?php echo htmlspecialchars((string)($user['bio'] ?? '')); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- CV Document Upload Card -->
                <div class="cv-section-card shadow-sm p-4 mb-4">
                    <h5 class="fw-black mb-4 small text-uppercase text-muted border-bottom pb-2"><?php echo __t('cv_document_pdf'); ?></h5>
                    <div class="border-2 border-dashed border-secondary bg-light rounded-4 p-4 text-center cursor-pointer" onclick="document.getElementById('cvInput').click()">
                        <i class="bi bi-cloud-arrow-up display-6 text-muted mb-2 d-block"></i>
                        <h6 class="fw-bold small"><?php echo __t('drag_click_cv'); ?></h6>
                        <p class="text-muted small mb-0" style="font-size: 0.8rem;">PDF, DOC, DOCX (Max 5Mo)</p>
                        <input type="file" name="cv" class="d-none" id="cvInput" accept=".pdf,.doc,.docx">
                    </div>
                    <?php if (!empty($user['cv_path'])): ?>
                        <div class="mt-3 p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-file-earmark-pdf-fill h4 text-danger mb-0"></i>
                                <div>
                                    <div class="small fw-bold">Mon_CV_Admissio.pdf</div>
                                    <a href="../uploads/<?php echo $user['cv_path']; ?>" target="_blank" class="small text-secondary text-decoration-underline"><?php echo __t('view_document'); ?></a>
                                </div>
                            </div>
                            <span class="badge bg-light text-secondary border rounded-pill px-3"><?php echo __t('valid'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Digital CV Status Card -->
                <div class="cv-section-card shadow-sm p-4 mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <h6 class="fw-bold mb-1"><i class="bi bi-file-person me-2 text-secondary"></i>Statut de mon CV Numérique</h6>
                            <p class="text-muted small mb-0">Votre CV contient <?php echo $cv_stats['formations']; ?> formations, <?php echo $cv_stats['experiences']; ?> expériences et <?php echo $cv_stats['competences']; ?> compétences.</p>
                        </div>
                        <a href="mon_cv.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 fw-bold"><?php echo __t('edit_expert_cv'); ?></a>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-flex gap-3 mt-4 justify-content-end">
                    <button type="submit" class="btn btn-success px-5 py-3 rounded-pill fw-bold"><?php echo __t('save_profile'); ?> <i class="bi bi-check-circle-fill ms-2"></i></button>
                    <a href="dashboard.php" class="btn btn-outline-secondary px-5 py-3 rounded-pill fw-bold"><?php echo __t('cancel'); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_footer(); ?>

<script>
// Base de données : Pays & Villes
const countriesData = [
    { name: "Mauritanie", code: "+222", flag: "🇲🇷", cities: ["Nouakchott", "Nouadhibou", "Kiffa", "Rosso", "Atar"] },
    { name: "Maroc", code: "+212", flag: "🇲🇦", cities: ["Casablanca", "Rabat", "Marrakech", "Tanger", "Agadir", "Fès"] },
    { name: "Sénégal", code: "+221", flag: "🇸🇳", cities: ["Dakar", "Saint-Louis", "Thiès", "Kaolack"] },
    { name: "France", code: "+33", flag: "🇫🇷", cities: ["Paris", "Lyon", "Marseille", "Toulouse"] }
];

function initForm() {
    const paysSel = document.getElementById('pays_select');
    const villeSel = document.getElementById('ville_select');
    const currP = '<?php echo $user['pays'] ?? ''; ?>';
    const currV = '<?php echo $user['adresse'] ?? ''; ?>';

    countriesData.forEach(c => {
        paysSel.add(new Option(c.flag + ' ' + c.name, c.name));
    });

    if (currP) {
        paysSel.value = currP;
        updateVilles(currP, currV);
    }

    paysSel.addEventListener('change', function() { updateVilles(this.value); });
    
    // Auto Update Progress
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(i => i.addEventListener('input', updateProgress));
    updateProgress();
}

function updateVilles(name, sel = '') {
    const c = countriesData.find(x => x.name === name);
    const villeSel = document.getElementById('ville_select');
    villeSel.innerHTML = '<option value=""><?php echo __t('choose_city'); ?></option>';
    if (c) {
        document.getElementById('indicatif_display').textContent = c.code;
        c.cities.forEach(v => villeSel.add(new Option(v, v)));
        if (sel) villeSel.value = sel;
    }
}

function updateProgress() {
    let filled = 0;
    const targets = ['nom', 'email', 'pays', 'adresse', 'telephone', 'secteur', 'niveau_etude', 'date_naissance', 'bio'];
    targets.forEach(n => {
        const el = document.querySelector(`[name="${n}"]`);
        if (el && el.value.trim() !== '') filled++;
    });
    const photo = document.getElementById('img-preview').src !== '' ? 1 : 0;
    const total = 10;
    const pc = Math.round(((filled + photo) / total) * 100);
    document.getElementById('completionBar').style.width = pc + '%';
    document.getElementById('completionPercent').textContent = pc + '%';
}

document.getElementById('photo-upload').addEventListener('change', function(e) {
    const reader = new FileReader();
    reader.onload = (e) => {
        document.getElementById('img-preview').src = e.target.result;
        document.getElementById('img-preview').classList.remove('d-none');
        if (document.getElementById('placeholder-icon')) document.getElementById('placeholder-icon').classList.add('d-none');
        updateProgress();
    };
    if (this.files[0]) reader.readAsDataURL(this.files[0]);
});

document.addEventListener('DOMContentLoaded', initForm);
</script>
