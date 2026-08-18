<?php
declare(strict_types=1);

/**
 * mon_cv.php – Mon CV Numérique (Design Premium Superio).
 */
require_once "../includes/layout.php";

check_role('candidat');
$user_id = $_SESSION["id"];
$base_path = PROJECT_PATH;

// Récupération de toutes les données du CV
$profil = null; 
$formations = []; 
$experiences = []; 
$competences_tech = []; 
$competences_perso = []; 
$langues = []; 
$certifications = [];
$interets = [];

try {
    $stmt = $pdo->prepare("SELECT u.nom, u.email, u.telephone, u.adresse, u.pays, p.bio, p.masquer_photo, p.masquer_icones, p.secteur_specialite, p.linkedin FROM utilisateurs u LEFT JOIN profils_candidats p ON u.id = p.id_utilisateur WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $profil = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM cv_formations WHERE id_utilisateur = ? ORDER BY date_fin DESC, date_debut DESC");
    $stmt->execute([$user_id]);
    $formations = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM cv_experiences WHERE id_utilisateur = ? ORDER BY date_debut DESC");
    $stmt->execute([$user_id]);
    $experiences = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM cv_competences WHERE id_utilisateur = ? AND (type='technique' OR type IS NULL) ORDER BY nom");
    $stmt->execute([$user_id]);
    $competences_tech = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM cv_competences WHERE id_utilisateur = ? AND type='professionnelle' ORDER BY nom");
    $stmt->execute([$user_id]);
    $competences_perso = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM cv_langues WHERE id_utilisateur = ? ORDER BY langue");
    $stmt->execute([$user_id]);
    $langues = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM cv_certifications WHERE id_utilisateur = ? ORDER BY date_obtention DESC");
    $stmt->execute([$user_id]);
    $certifications = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM cv_interets WHERE id_utilisateur = ? ORDER BY nom");
    $stmt->execute([$user_id]);
    $interets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("DB Error in mon_cv.php: " . $e->getMessage());
}

$niveau_labels = [
    'notions' => 'Notions',
    'intermediaire' => 'Intermédiaire',
    'avance' => 'Avancé',
    'bilingue' => 'Bilingue',
    'maternel' => 'Langue maternelle'
];

$mode_epure = ($profil['masquer_icones'] ?? 0) == 1;
$masquer_photo = ($profil['masquer_photo'] ?? 0) == 1;

include_header("Mon CV Numérique");
?>

<style>
/* Page Layout */
/* Grid body removed for better space management */
.nav-link-premium {
    color: var(--dark, #334155);
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    border-left: 3px solid transparent;
    text-decoration: none;
}
.nav-link-premium:hover {
    color: #0f5132;
    background: rgba(15, 81, 50, 0.05);
    border-left-color: #0f5132;
    padding-left: 1.25rem !important;
}
.nav-link-premium.active {
    color: #0f5132;
    background: rgba(15, 81, 50, 0.08);
    border-left-color: #0f5132;
}

/* Glassmorphism & Cards */
.diagnostic-glass-card {
    background: linear-gradient(135deg, #0f5132 0%, #0a3622 100%);
    color: #fff;
    border-radius: 20px;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}
.diagnostic-glass-card::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse-glow 8s infinite alternate;
}
@keyframes pulse-glow {
    0% { opacity: 0.3; }
    100% { opacity: 0.6; }
}
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
.section-header-premium {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.75rem;
    margin-bottom: 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    position: relative;
}
.timeline-item-premium {
    position: relative;
    padding-left: 1.5rem;
    border-left: 2px solid #f1f5f9;
    margin-bottom: 1.5rem;
    transition: all 0.3s ease;
}
.timeline-item-premium:hover {
    border-left-color: #64748b;
}
.timeline-item-premium::before {
    content: '';
    position: absolute;
    left: -7px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #fff;
    border: 2px solid #64748b;
}

/* Timeline Polish */
.resume-timeline { position: relative; padding-left: 2rem; }
.resume-timeline::before {
    content: '';
    position: absolute;
    left: 7px; top: 0; bottom: 0;
    width: 2px;
    background: #e2e8f0;
}
.resume-item { position: relative; margin-bottom: 2.5rem; }
.resume-item::before {
    content: '';
    position: absolute;
    left: -27px; top: 5px;
    width: 14px; height: 14px;
    background: #fff;
    border: 3px solid #64748b;
    border-radius: 50%;
    z-index: 1;
}

/* Diagnostic Result Feedback */
.diagnostic-result-container { display: none; margin-bottom: 1.5rem; }
.diagnostic-active { display: block; animation: slideDown 0.5s ease forwards; }
@keyframes slideDown { from { opacity:0; transform: translateY(-20px); } to { opacity:1; transform: translateY(0); } }

/* Import Loader */
#import-loader {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    z-index: 9999;
    align-items: center; justify-content: center;
    flex-direction: column;
}
.loader-circle {
    width: 60px; height: 60px;
    border: 4px solid #e2e8f0;
    border-top: 4px solid #0f5132;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.save-floating-bar {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(135deg, #0f5132 0%, #0a3622 100%);
    padding: 0.8rem 1.5rem;
    border-radius: 50px;
    z-index: 900;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    border: 2px solid rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s ease;
}
.save-floating-bar:hover { transform: scale(1.05); }
.score-circle-lg {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    margin: 0 auto;
    font-family: 'Outfit', sans-serif;
    transition: all 0.5s ease;
}
.bg-success-light {
    background: rgba(25, 135, 84, 0.08);
}
.bg-warning-light {
    background: rgba(245, 158, 11, 0.08);
}
</style>

<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-5">
        <div>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fw-bold mb-2"><?php echo __t('MON ESPACE'); ?></span>
            <h1 class="fw-black text-gray-900 mb-1" style="font-size: 2.2rem;"><?php echo __t('mon_cv_numerique'); ?></h1>
            <p class="text-muted mb-0"><?php echo __t('mon_cv_subtext'); ?></p>
        </div>
        <div class="text-md-end">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end align-items-center">
                <input type="file" id="cv-import-file" style="display:none;" accept=".pdf" onchange="handleImport(this)">
                <button onclick="deleteEntireCV()" class="btn btn-outline-danger px-4 rounded-pill">
                    <i class="bi bi-trash-fill me-2"></i> <?php echo __t('Réinitialiser'); ?>
                </button>
                <button onclick="triggerImport()" class="btn btn-outline-secondary px-4 rounded-pill">
                    <i class="bi bi-cloud-arrow-up-fill me-2"></i> <?php echo __t('Importer PDF'); ?>
                </button>
                <a href="generer_cv_pdf.php" target="_blank" class="btn btn-outline-secondary px-4 rounded-pill">
                    <i class="bi bi-file-earmark-pdf-fill me-2"></i> <?php echo __t('Exporter'); ?>
                </a>
            </div>
            <div class="small text-muted mt-2 text-md-end text-center" style="font-size: 0.75rem;"><?php echo __t('import_pdf_subtext'); ?></div>
        </div>
    </div>

    <div id="import-loader" style="display:none; position:fixed; inset:0; background:rgba(255,255,255,0.9); backdrop-filter:blur(10px); z-index:9999; align-items:center; justify-content:center; flex-direction:column;">
        <div class="spinner-border text-success mb-3" style="width: 3rem; height: 3rem;"></div>
        <h4 class="fw-black mb-1"><?php echo __t('extraction_in_progress'); ?></h4>
        <p class="text-muted"><?php echo __t('extraction_subtext'); ?></p>
    </div>

    <div class="row justify-content-center">
        <!-- Centered single column layout to align everything and eliminate empty spaces -->
        <div class="col-lg-10 col-xl-9">
            <div class="row g-4 mb-4">
                <!-- Progress Card -->
                <div class="col-md-6">
                    <div class="cv-section-card shadow-sm h-100 p-4 mb-0">
                        <h6 class="fw-black mb-3 small text-uppercase text-muted"><?php echo __t('profil_completion'); ?></h6>
                        <?php
                            $steps = 0;
                            if(!empty($profil['bio'])) $steps++;
                            if(!empty($experiences)) $steps++;
                            if(!empty($formations)) $steps++;
                            if(!empty($competences_tech) || !empty($competences_perso)) $steps++;
                            if(!empty($langues)) $steps++;
                            $progress = ($steps / 5) * 100;
                        ?>
                        <div class="progress mb-2" style="height: 10px; border-radius: 10px; background: #f1f5f9;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: <?php echo $progress; ?>%; border-radius: 10px;"></div>
                        </div>
                        <div class="d-flex justify-content-between small fw-bold mt-2">
                            <span class="text-muted"><?php echo $steps; ?>/5 <?php echo __t('steps'); ?></span>
                            <span class="text-success"><?php echo (int)$progress; ?>%</span>
                        </div>
                    </div>
                </div>
                
                <!-- AI Diagnostic Card -->
                <div class="col-md-6">
                    <div class="cv-section-card shadow-sm h-100 p-4 mb-0">
                        <h6 class="fw-black mb-3 small text-uppercase text-muted"><i class="bi bi-cpu text-success me-2"></i>Diagnostic IA de mon CV</h6>
                        <p class="small text-muted mb-3">Analysez la qualité globale de votre CV numérique et obtenez des conseils personnalisés.</p>
                        <button class="btn btn-success w-100 rounded-pill fw-bold" onclick="runAIDiagnostic()">
                            <i class="bi bi-magic me-2"></i>Lancer le Diagnostic
                        </button>
                    </div>
                </div>
            </div>

            <!-- CV Sections Stack -->
            <div class="d-flex flex-column gap-4">
                <section id="section-bio" class="cv-section-card p-4">
                    <div class="section-header-premium">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2 bg-light rounded-circle text-dark" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-person-vcard fs-5"></i>
                            </div>
                            <h5 class="fw-black mb-0"><?php echo __t('presentation_pro'); ?></h5>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-4 border-bottom pb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Prénom et Nom</label>
                            <input type="text" id="nom" class="form-control rounded-pill px-3" placeholder="Votre nom complet" value="<?php echo htmlspecialchars($profil['nom'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Adresse e-mail</label>
                            <input type="email" id="email" class="form-control rounded-pill px-3" placeholder="prenom.nom@email.com" value="<?php echo htmlspecialchars($profil['email'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Numéro de téléphone</label>
                            <input type="tel" id="telephone" class="form-control rounded-pill px-3" placeholder="+212 ..." value="<?php echo htmlspecialchars($profil['telephone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Adresse postale</label>
                            <input type="text" id="adresse" class="form-control rounded-pill px-3" placeholder="Ville, Code postal" value="<?php echo htmlspecialchars($profil['adresse'] ?? ''); ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Lien LinkedIn ou Portfolio</label>
                            <input type="url" id="linkedin" class="form-control rounded-pill px-3" placeholder="https://linkedin.com/in/..." value="<?php echo htmlspecialchars($profil['linkedin'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Titre du CV (Poste visé)</label>
                            <input type="text" id="secteur_specialite" class="form-control rounded-pill px-3" placeholder="Ex: Développeur Full-Stack PHP/React" value="<?php echo htmlspecialchars($profil['secteur_specialite'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-4 pb-4 border-bottom">
                        <label class="form-label small fw-bold text-muted text-uppercase mb-3">Options d'affichage du profil</label>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check form-switch p-2 border rounded-pill px-3 bg-light">
                                <input class="form-check-input ms-0 me-2" type="checkbox" id="toggle-photo" <?php echo $masquer_photo ? 'checked' : ''; ?> onchange="toggleVisibility('photo', this.checked)">
                                <label class="form-check-label small fw-bold" for="toggle-photo"><?php echo __t('masquer_photo'); ?></label>
                            </div>
                            <div class="form-check form-switch p-2 border rounded-pill px-3 bg-light">
                                <input class="form-check-input ms-0 me-2" type="checkbox" id="toggle-epure" <?php echo $mode_epure ? 'checked' : ''; ?> onchange="toggleVisibility('icones', this.checked)">
                                <label class="form-check-label small fw-bold" for="toggle-epure"><?php echo __t('masquer_icones'); ?></label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="form-label small fw-bold text-muted text-uppercase"><?php echo __t('bio_label'); ?></label>
                        <textarea id="bio-content" class="form-control rounded-4 p-3 border-light" rows="5" placeholder="<?php echo __t('bio_placeholder'); ?>"><?php echo htmlspecialchars((string)($profil['bio'] ?? '')); ?></textarea>
                    </div>
                         <section id="section-experience" class="cv-section-card shadow-sm">
                        <div class="section-header-premium">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 bg-light rounded-circle text-dark" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-briefcase fs-5"></i>
                                </div>
                                <h5 class="fw-black mb-0"><?php echo __t('experiences'); ?></h5>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="showModal('experience')">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        
                        <div>
                            <?php if(empty($experiences)): ?>
                                <div class="text-center py-5 text-muted"><i class="bi bi-inbox d-block display-4 opacity-10 mb-2"></i><?php echo __t('no_experience'); ?></div>
                            <?php else: ?>
                                <div class="resume-timeline">
                                    <?php foreach($experiences as $exp): ?>
                                        <div class="resume-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <span class="badge bg-light text-secondary border mb-2">
                                                        <?php 
                                                            $start = (!empty($exp['date_debut']) && strpos((string)$exp['date_debut'], '0000') !== 0) ? date('M Y', strtotime((string)$exp['date_debut'])) : 'N/A';
                                                            $end = $exp['en_poste'] ? __t('present') : ((!empty($exp['date_fin']) && strpos((string)$exp['date_fin'], '0000') !== 0) ? date('M Y', strtotime((string)$exp['date_fin'])) : '...');
                                                            echo $start . ' — ' . $end;
                                                        ?>
                                                    </span>
                                                    <h6 class="fw-black mb-1"><?php echo htmlspecialchars((string)($exp['poste'] ?? '')); ?></h6>
                                                    <div class="text-muted small mb-2"><span class="text-dark fw-bold"><?php echo htmlspecialchars((string)($exp['entreprise'] ?? '')); ?></span> | <?php echo htmlspecialchars((string)($exp['ville'] ?? '')); ?></div>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-light" onclick="editItem('experience',<?php echo $exp['id']; ?>)"><i class="bi bi-pencil"></i></button>
                                                    <button class="btn btn-sm btn-light text-danger" onclick="deleteItem('experience',<?php echo $exp['id']; ?>)"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </div>
                                            <p class="text-muted small mb-0 mt-2"><?php echo nl2br(htmlspecialchars((string)($exp['description'] ?? ''))); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section id="section-formation" class="cv-section-card shadow-sm">
                        <div class="section-header-premium">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 bg-light rounded-circle text-dark" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-mortarboard fs-5"></i>
                                </div>
                                <h5 class="fw-black mb-0"><?php echo __t('formations'); ?></h5>
                            </div>
                            <button class="btn btn-outline-secondary btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" onclick="showModal('formation')">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                        
                        <div>
                            <?php if(empty($formations)): ?>
                                <div class="text-center py-5 text-muted"><i class="bi bi-inbox d-block display-4 opacity-10 mb-2"></i><?php echo __t('no_formation'); ?></div>
                            <?php else: ?>
                                <div class="resume-timeline">
                                    <?php foreach($formations as $f): ?>
                                        <div class="resume-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <span class="badge bg-light text-secondary border mb-2">
                                                        <?php 
                                                            $f_start = (!empty($f['date_debut']) && strpos((string)$f['date_debut'], '0000') !== 0) ? date('Y', strtotime((string)$f['date_debut'])) : 'N/A';
                                                            $f_end = (!empty($f['date_fin']) && strpos((string)$f['date_fin'], '0000') !== 0) ? date('Y', strtotime((string)$f['date_fin'])) : __t('present');
                                                            echo $f_start . ' — ' . $f_end;
                                                        ?>
                                                    </span>
                                                    <h6 class="fw-black mb-1"><?php echo htmlspecialchars((string)($f['diplome'] ?? '')); ?></h6>
                                                    <div class="text-muted small"><span class="text-dark fw-bold"><?php echo htmlspecialchars((string)($f['etablissement'] ?? '')); ?></span> | <?php echo htmlspecialchars((string)($f['ville'] ?? '')); ?></div>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-light" onclick="editItem('formation',<?php echo $f['id']; ?>)"><i class="bi bi-pencil"></i></button>
                                                    <button class="btn btn-sm btn-light text-danger" onclick="deleteItem('formation',<?php echo $f['id']; ?>)"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>             </section>

                <section id="section-skills" class="cv-section-card shadow-sm">
                        <div class="section-header-premium">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 bg-light rounded-circle text-dark" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-cpu fs-5"></i>
                                </div>
                                <h5 class="fw-black mb-0"><?php echo __t('competences'); ?></h5>
                            </div>
                        </div>
                        
                        <div>
                            <div class="mb-4">
                                <button class="btn btn-sm btn-outline-secondary w-100 rounded-pill mb-3" onclick="showModal('competence','technique')">+ <?php echo __t('technique'); ?></button>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach($competences_tech as $c): ?>
                                        <div class="badge bg-light text-dark p-2 px-3 rounded-pill fw-semibold small border d-flex align-items-center gap-2"><?php echo htmlspecialchars((string)($c['nom'] ?? '')); ?> <span class="text-muted cursor-pointer" onclick="deleteItem('competence',<?php echo $c['id']; ?>)">✕</span></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-secondary w-100 rounded-pill mb-3" onclick="showModal('competence','professionnelle')">+ <?php echo __t('soft_skills'); ?></button>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach($competences_perso as $c): ?>
                                        <div class="badge bg-light text-dark p-2 px-3 rounded-pill fw-semibold small border d-flex align-items-center gap-2"><?php echo htmlspecialchars((string)($c['nom'] ?? '')); ?> <span class="text-muted cursor-pointer" onclick="deleteItem('competence',<?php echo $c['id']; ?>)">✕</span></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                </section>

                <section id="section-lang" class="cv-section-card shadow-sm">
                        <div class="section-header-premium">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 bg-light rounded-circle text-dark" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-translate fs-5"></i>
                                </div>
                                <h5 class="fw-black mb-0"><?php echo __t('langues_certifs'); ?></h5>
                            </div>
                        </div>
                        
                        <div>
                            <button class="btn btn-sm btn-outline-secondary w-100 rounded-pill mb-3" onclick="showModal('langue')">+ <?php echo __t('ajouter_langue'); ?></button>
                            <?php foreach($langues as $l): ?>
                                <div class="p-2 mb-2 bg-light rounded d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold small"><?php echo htmlspecialchars((string)($l['langue'] ?? '')); ?></div>
                                        <div class="smaller text-secondary"><?php echo $niveau_labels[$l['niveau']] ?? $l['niveau']; ?></div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-light" onclick="editItem('langue',<?php echo $l['id']; ?>)"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-sm btn-light text-danger" onclick="deleteItem('langue',<?php echo $l['id']; ?>)"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="mt-4 pt-3 border-top">
                                <button class="btn btn-sm btn-outline-secondary w-100 rounded-pill mb-3" onclick="showModal('certification')">+ <?php echo __t('ajouter_certif'); ?></button>
                                <?php foreach($certifications as $c): ?>
                                    <div class="p-2 mb-2 bg-light rounded d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold small"><?php echo htmlspecialchars((string)($c['nom'] ?? '')); ?></div>
                                            <div class="smaller text-muted"><?php echo htmlspecialchars((string)($c['organisme'] ?? '')); ?> <?php echo !empty($c['date_obtention']) ? '| ' . date('Y', strtotime((string)$c['date_obtention'])) : ''; ?></div>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-light" onclick="editItem('certification',<?php echo $c['id']; ?>)"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-light text-danger" onclick="deleteItem('certification',<?php echo $c['id']; ?>)"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                </section>

                <section id="section-interets" class="cv-section-card shadow-sm">
                        <div class="section-header-premium">
                            <div class="d-flex align-items-center gap-3">
                                <div class="p-2 bg-light rounded-circle text-dark" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-heart fs-5"></i>
                                </div>
                                <h5 class="fw-black mb-0"><?php echo __t('interets'); ?></h5>
                            </div>
                        </div>
                        
                        <div>
                            <button class="btn btn-sm btn-outline-secondary w-100 rounded-pill mb-3" onclick="showModal('interet')">+ <?php echo __t('ajouter_loisir'); ?></button>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach($interets as $i): ?>
                                    <div class="badge bg-light text-dark p-2 px-3 rounded-pill fw-semibold small border d-flex align-items-center gap-2">
                                        <?php echo htmlspecialchars((string)($i['nom'] ?? '')); ?> 
                                        <span class="text-muted cursor-pointer" onclick="deleteItem('interet',<?php echo $i['id']; ?>)">✕</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cvModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light p-4">
                <h5 class="modal-title fw-black" id="modalTitle">Compléter la section</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cvForm" onsubmit="submitForm(event)">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="type" id="formType">
                    <input type="hidden" name="id" id="formId">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div id="formFields" class="row g-3"></div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-success w-100 py-3 rounded-pill fw-bold">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Diagnostic IA -->
<div class="modal fade" id="aiDiagnosticModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-success bg-opacity-10 p-4 border-0">
                <h5 class="modal-title fw-black text-success d-flex align-items-center gap-2" id="modalDiagTitle">
                    <i class="bi bi-cpu fs-4"></i> Diagnostic Intelligent de votre CV
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4 align-items-center mb-4">
                    <div class="col-md-4 text-center">
                        <div class="position-relative d-inline-block">
                            <!-- Circular Score Indicator -->
                            <div class="score-circle-lg d-flex align-items-center justify-content-center" id="diagScoreCircle" style="width: 140px; height: 140px; border-radius: 50%; border: 8px solid #f1f5f9; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                                <div>
                                    <span class="display-6 fw-black text-success" id="diagScoreVal">0</span>
                                    <span class="text-muted small">/100</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h6 class="text-muted small fw-bold text-uppercase mb-2">Verdict de l'IA</h6>
                        <div class="p-3 bg-light rounded-4 border border-light">
                            <p class="mb-0 text-gray-700 italic fw-semibold" id="diagVerdict">Analyse en cours...</p>
                        </div>
                    </div>
                </div>

                <hr class="opacity-5 my-4">

                <!-- Section Diagnostic -->
                <div class="mb-4">
                    <h6 class="fw-black text-gray-900 mb-3"><i class="bi bi-file-text text-success me-2"></i>Analyse détaillée</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border border-light h-100">
                                <div class="fw-bold small mb-1">Présentation</div>
                                <p class="text-muted small mb-0" id="diagPresentation">—</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border border-light h-100">
                                <div class="fw-bold small mb-1">Expérience</div>
                                <p class="text-muted small mb-0" id="diagExperience">—</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded-3 border border-light h-100">
                                <div class="fw-bold small mb-1">Compétences</div>
                                <p class="text-muted small mb-0" id="diagCompetences">—</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Points Forts -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-success-light bg-opacity-50 p-4 rounded-4 h-100">
                            <h6 class="fw-bold text-success mb-3"><i class="bi bi-patch-check-fill me-2"></i>Points Forts</h6>
                            <ul class="list-unstyled mb-0" id="diagPointsForts">
                                <!-- Dynamique -->
                            </ul>
                        </div>
                    </div>

                    <!-- Suggestions -->
                    <div class="col-md-6">
                        <div class="card border-0 bg-warning-light bg-opacity-50 p-4 rounded-4 h-100">
                            <h6 class="fw-bold text-warning mb-3"><i class="bi bi-lightbulb-fill me-2"></i>Pistes d'amélioration</h6>
                            <ul class="list-unstyled mb-0" id="diagSuggestions">
                                <!-- Dynamique -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-secondary w-100 py-3 rounded-pill fw-bold" data-bs-dismiss="modal">Fermer le diagnostic</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let modal;
let diagModal;
document.addEventListener('DOMContentLoaded', () => { 
    modal = new bootstrap.Modal(document.getElementById('cvModal')); 
    diagModal = new bootstrap.Modal(document.getElementById('aiDiagnosticModal'));
});

function runAIDiagnostic() {
    Swal.fire({
        title: 'Diagnostic IA en cours...',
        text: 'Veuillez patienter pendant que notre IA analyse votre profil numérique.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const fd = new FormData();
    fd.append('use_digital_profile', '1');
    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

    fetch('optimiser_cv_ajax.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        Swal.close();
        if (data.success && data.suggestions) {
            const sug = data.suggestions;
            
            // Score and circle color coding
            const score = sug.score || 0;
            document.getElementById('diagScoreVal').textContent = score;
            const circle = document.getElementById('diagScoreCircle');
            
            // Visual indicators for score
            if (score >= 70) {
                circle.style.borderColor = '#198754';
                document.getElementById('diagScoreVal').className = 'display-6 fw-black text-success';
            } else if (score >= 40) {
                circle.style.borderColor = '#f59e0b';
                document.getElementById('diagScoreVal').className = 'display-6 fw-black text-warning';
            } else {
                circle.style.borderColor = '#dc3545';
                document.getElementById('diagScoreVal').className = 'display-6 fw-black text-danger';
            }

            // Text values
            document.getElementById('diagVerdict').textContent = sug.verdict_flash || 'Aucun verdict disponible';
            document.getElementById('diagPresentation').textContent = sug.diagnostic?.presentation || '—';
            document.getElementById('diagExperience').textContent = sug.diagnostic?.experience || '—';
            document.getElementById('diagCompetences').textContent = sug.diagnostic?.competences || '—';

            // Lists
            const ptsFortsList = document.getElementById('diagPointsForts');
            ptsFortsList.innerHTML = '';
            if (sug.points_forts && sug.points_forts.length > 0) {
                sug.points_forts.forEach(pf => {
                    const li = document.createElement('li');
                    li.className = 'd-flex gap-2 mb-2 small text-gray-700';
                    li.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> <span>' + pf + '</span>';
                    ptsFortsList.appendChild(li);
                });
            } else {
                ptsFortsList.innerHTML = '<li class="small text-muted">Aucun point fort identifié.</li>';
            }

            const sugList = document.getElementById('diagSuggestions');
            sugList.innerHTML = '';
            if (sug.suggestions && sug.suggestions.length > 0) {
                sug.suggestions.forEach(s => {
                    const li = document.createElement('li');
                    li.className = 'd-flex gap-2 mb-2 small text-gray-700';
                    li.innerHTML = '<i class="bi bi-arrow-right-circle-fill text-warning"></i> <span>' + s + '</span>';
                    sugList.appendChild(li);
                });
            } else {
                sugList.innerHTML = '<li class="small text-muted">Aucune suggestion d\'amélioration.</li>';
            }

            diagModal.show();
        } else {
            Swal.fire('Erreur', data.error || 'Impossible d\'analyser le profil.', 'error');
        }
    })
    .catch(err => {
        Swal.close();
        console.error(err);
        Swal.fire('Erreur', 'Erreur de connexion au serveur.', 'error');
    });
}

function showModal(type, subtype) {
    document.getElementById('formType').value = type;
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('modalTitle').textContent = 'Ajouter : ' + type;
    renderFields(type, {}, subtype);
    modal.show();
}

function editItem(type, id) {
    const fd = new FormData();
    fd.append('action', 'getItem');
    fd.append('type', type);
    fd.append('id', id);
    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

    fetch('mon_cv_ajax.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.success && d.data) {
            document.getElementById('formType').value = type;
            document.getElementById('formAction').value = 'edit';
            document.getElementById('formId').value = id;
            document.getElementById('modalTitle').textContent = 'Modifier : ' + type;
            renderFields(type, d.data);
            modal.show();
        } else {
            Swal.fire('Erreur', 'Impossible de récupérer les données.', 'error');
        }
    });
}

function getMonthOptions() {
    return `<option value="">Mois</option>
            <option value="01">Janvier</option>
            <option value="02">Février</option>
            <option value="03">Mars</option>
            <option value="04">Avril</option>
            <option value="05">Mai</option>
            <option value="06">Juin</option>
            <option value="07">Juillet</option>
            <option value="08">Août</option>
            <option value="09">Septembre</option>
            <option value="10">Octobre</option>
            <option value="11">Novembre</option>
            <option value="12">Décembre</option>`;
}

function getYearOptions() {
    const currentYear = new Date().getFullYear();
    let yearOptions = '<option value="">Année</option>';
    for (let y = currentYear + 5; y >= 1970; y--) {
        yearOptions += `<option value="${y}">${y}</option>`;
    }
    return yearOptions;
}

function updateDateInput(prefix) {
    const mois = document.getElementById(`date_${prefix}_mois`).value;
    const annee = document.getElementById(`date_${prefix}_annee`).value;
    const hidden = document.getElementById(`date_${prefix}_hidden`);
    if (mois && annee) {
        hidden.value = `${annee}-${mois}`;
    } else {
        hidden.value = '';
    }
}

function setMonthYearSelects(prefix, dateStr) {
    const hidden = document.getElementById(`date_${prefix}_hidden`);
    const moisSelect = document.getElementById(`date_${prefix}_mois`);
    const anneeSelect = document.getElementById(`date_${prefix}_annee`);
    
    hidden.value = dateStr || '';
    if (dateStr && !dateStr.startsWith('0000')) {
        const parts = dateStr.split('-');
        if (parts.length >= 2) {
            anneeSelect.value = parts[0];
            moisSelect.value = parts[1];
            return;
        }
    }
    anneeSelect.value = '';
    moisSelect.value = '';
}

function renderFields(type, d = {}, subtype = '') {
    let h = '';
    if (type === 'formation') {
        h = `<div class="col-12"><label class="form-label small fw-bold">Diplôme</label><input name="diplome" class="form-control" value="${d.diplome||''}" required></div>
             <div class="col-12"><label class="form-label small fw-bold">Établissement</label><input name="etablissement" class="form-control" value="${d.etablissement||''}" required></div>
             <div class="col-md-12"><label class="form-label small fw-bold">Ville</label><input name="ville" class="form-control" value="${d.ville||''}"></div>
             <div class="col-md-6"><label class="form-label small fw-bold">Début</label>
                 <div class="d-flex gap-2">
                     <select id="date_debut_mois" class="form-select" onchange="updateDateInput('debut')">${getMonthOptions()}</select>
                     <select id="date_debut_annee" class="form-select" onchange="updateDateInput('debut')">${getYearOptions()}</select>
                 </div>
                 <input type="hidden" name="date_debut" id="date_debut_hidden" value="${d.date_debut||''}">
             </div>
             <div class="col-md-6"><label class="form-label small fw-bold">Fin</label>
                 <div class="d-flex gap-2">
                     <select id="date_fin_mois" class="form-select" onchange="updateDateInput('fin')">${getMonthOptions()}</select>
                     <select id="date_fin_annee" class="form-select" onchange="updateDateInput('fin')">${getYearOptions()}</select>
                 </div>
                 <input type="hidden" name="date_fin" id="date_fin_hidden" value="${d.date_fin||''}">
             </div>`;
    } else if (type === 'experience') {
        h = `<div class="col-12"><label class="form-label small fw-bold">Poste</label><input name="poste" class="form-control" value="${d.poste||''}" required></div>
             <div class="col-12"><label class="form-label small fw-bold">Entreprise</label><input name="entreprise" class="form-control" value="${d.entreprise||''}" required></div>
             <div class="col-md-12"><label class="form-label small fw-bold">Ville</label><input name="ville" class="form-control" value="${d.ville||''}"></div>
             <div class="col-md-6"><label class="form-label small fw-bold">Début</label>
                 <div class="d-flex gap-2">
                     <select id="date_debut_mois" class="form-select" onchange="updateDateInput('debut')">${getMonthOptions()}</select>
                     <select id="date_debut_annee" class="form-select" onchange="updateDateInput('debut')">${getYearOptions()}</select>
                 </div>
                 <input type="hidden" name="date_debut" id="date_debut_hidden" value="${d.date_debut||''}">
             </div>
             <div class="col-md-6"><label class="form-label small fw-bold">Fin</label>
                 <div class="d-flex gap-2">
                     <select id="date_fin_mois" class="form-select" onchange="updateDateInput('fin')">${getMonthOptions()}</select>
                     <select id="date_fin_annee" class="form-select" onchange="updateDateInput('fin')">${getYearOptions()}</select>
                 </div>
                 <input type="hidden" name="date_fin" id="date_fin_hidden" value="${d.date_fin||''}">
             </div>
             <div class="col-12"><textarea name="description" class="form-control" rows="3" placeholder="Missions...">${d.description||''}</textarea></div>`;
    } else if (type === 'competence') {
        h = `<div class="col-12"><label class="form-label small fw-bold">Compétence</label><input name="nom" class="form-control" value="${d.nom||''}" required></div>
             <input type="hidden" name="comp_type" value="${subtype||d.type||'technique'}">`;
    } else if (type === 'langue') {
        h = `<div class="col-12"><label class="form-label small fw-bold"><?php echo __t('ajouter_langue'); ?></label><input name="langue" class="form-control" value="${d.langue||''}" required></div>
             <div class="col-12"><select name="niveau" class="form-select">
                <option value="notions" ${d.niveau=='notions'?'selected':''}>Notions</option>
                <option value="intermediaire" ${d.niveau=='intermediaire'?'selected':''}>Intermédiaire</option>
                <option value="avance" ${d.niveau=='avance'?'selected':''}>Avancé</option>
                <option value="bilingue" ${d.niveau=='bilingue'?'selected':''}>Bilingue</option>
                <option value="maternel" ${d.niveau=='maternel'?'selected':''}>Maternel</option>
             </select></div>`;
    } else if (type === 'certification') {
        h = `<div class="col-12"><label class="form-label small fw-bold">Certification / Diplôme</label><input name="nom" class="form-control" value="${d.nom||''}" required></div>
             <div class="col-12"><label class="form-label small fw-bold">Organisme</label><input name="organisme" class="form-control" value="${d.organisme||''}"></div>
             <div class="col-12"><label class="form-label small fw-bold">Date d'obtention</label><input type="date" name="date_obtention" class="form-control" value="${d.date_obtention||''}"></div>`;
    } else if (type === 'interet') {
        h = `<div class="col-12"><label class="form-label small fw-bold">Activité / Loisir</label><input name="nom" class="form-control" value="${d.nom||''}" required placeholder="ex: Voyages, Photographie, Football..."></div>`;
    }
    document.getElementById('formFields').innerHTML = h;
    if (type === 'formation' || type === 'experience') {
        setMonthYearSelects('debut', d.date_debut);
        setMonthYearSelects('fin', d.date_fin);
    }
}

function submitForm(e) {
    e.preventDefault();
    const fd = new FormData(document.getElementById('cvForm'));
    fetch('mon_cv_ajax.php', { method:'POST', body:fd })
    .then(r=>r.json()).then(d=>{ 
        if(d.success) location.reload(); 
        else Swal.fire('Erreur', d.error || 'Erreur inconnue', 'error'); 
    }).catch(e => {
        console.error(e);
        Swal.fire('Erreur', 'Erreur de communication avec le serveur', 'error');
    });
}

function deleteItem(type, id) {
    Swal.fire({ title: 'Supprimer ?', icon: 'warning', showCancelButton: true }).then(r => {
        if(r.isConfirmed) {
            const fd = new FormData();
            fd.append('action','delete'); fd.append('type',type); fd.append('id',id);
            fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            fetch('mon_cv_ajax.php', { method:'POST', body:fd }).then(r=>r.json()).then(d=>location.reload());
        }
    });
}

function saveBio() {
    const fd = new FormData();
    fd.append('action','save_bio');
    fd.append('bio',document.getElementById('bio-content').value);
    fd.append('secteur_specialite', document.getElementById('secteur_specialite').value);
    fd.append('nom', document.getElementById('nom').value);
    fd.append('email', document.getElementById('email').value);
    fd.append('telephone', document.getElementById('telephone').value);
    fd.append('adresse', document.getElementById('adresse').value);
    fd.append('linkedin', document.getElementById('linkedin').value);
    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
    fetch('mon_cv_ajax.php', { method:'POST', body:fd }).then(r=>r.json()).then(d=>{
        if(d.success) Swal.fire({ title: 'Enregistré', icon: 'success', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false });
        else Swal.fire('Erreur', d.error || 'Impossible de sauvegarder', 'error');
    }).catch(e => console.error(e));
}

function deleteEntireCV() {
    Swal.fire({ title: 'Tout réinitialiser ?', text: 'Action irréversible !', icon: 'warning', showCancelButton: true }).then(r => {
        if(r.isConfirmed) {
            const fd = new FormData(); fd.append('action','delete_full_cv'); fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            fetch('mon_cv_ajax.php', { method:'POST', body:fd }).then(() => location.reload());
        }
    });
}

function toggleVisibility(t,v) {
    const fd = new FormData(); fd.append('action','toggle_visibility'); fd.append('target',t); fd.append('value',v?1:0);
    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
    fetch('mon_cv_ajax.php', { method:'POST', body:fd }).then(()=>location.reload());
}

function triggerImport() { document.getElementById('cv-import-file').click(); }

function handleImport(input) {
    if (!input.files || !input.files[0]) return;
    const loader = document.getElementById('import-loader');
    loader.style.display = 'flex';
    const fd = new FormData();
    fd.append('action', 'import_pdf');
    fd.append('cv_file', input.files[0]);
    fd.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
    fetch('mon_cv_ajax.php', { method: 'POST', body: fd })
    .then(r => {
        if (!r.ok) return r.text().then(t => { throw new Error(t || 'Erreur serveur'); });
        return r.json();
    }).then(d => {
        loader.style.display = 'none';
        if (d.success) {
            Swal.fire({ title: 'Importation réussie', text: d.message, icon: 'success' }).then(() => location.reload());
        } else {
            Swal.fire('Erreur', d.error, 'error');
        }
    }).catch(e => {
        loader.style.display = 'none';
        console.error(e);
        Swal.fire('Erreur', "Erreur de communication : " + e.message, 'error');
    });
}
</script>

    <div class="save-floating-bar shadow-lg">
        <div class="text-white d-none d-lg-block" style="font-size: 0.85rem;">
            <div class="fw-bold"><?php echo __t('ready_to_save'); ?></div>
        </div>
        <button onclick="saveBio()" class="btn btn-light text-success fw-bold btn-sm rounded-pill px-4 py-2 shadow-sm">
            <i class="bi bi-save-fill me-2"></i> <?php echo __t('save'); ?>
        </button>
    </div>

    <?php include_footer(); ?>

