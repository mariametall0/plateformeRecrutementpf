<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$user_id = $_SESSION["id"];

// Récupération des données
try {
    $stmt = $pdo->prepare("SELECT bio FROM profils_candidats WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);
    $profil = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_formations WHERE id_utilisateur = ? ORDER BY date_debut DESC");
    $stmt->execute([$user_id]);
    $formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_experiences WHERE id_utilisateur = ? ORDER BY date_debut DESC");
    $stmt->execute([$user_id]);
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_competences WHERE id_utilisateur = ? ORDER BY type, nom");
    $stmt->execute([$user_id]);
    $competences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_langues WHERE id_utilisateur = ? ORDER BY langue");
    $stmt->execute([$user_id]);
    $langues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_certifications WHERE id_utilisateur = ? ORDER BY date_obtention DESC");
    $stmt->execute([$user_id]);
    $certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    send_error("Erreur de base de données : " . $e->getMessage());
}

include_header("Mon CV Numérique");
?>

<style>
/* Style Thème Superio Job Board (Inspiré d'Efficasys) */
body {
    background-color: #F5F7FC !important;
    font-family: 'Poppins', 'Roboto', sans-serif;
}

/* === SIDEBAR MENU === */
.dashboard-sidebar {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0px 6px 15px rgba(64, 79, 104, 0.05);
    padding: 30px 0;
    position: sticky;
    top: 90px;
}
.sidebar-user-info {
    text-align: center;
    padding: 0 20px 20px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    margin-bottom: 20px;
}
.sidebar-user-info .user-avatar {
    width: 80px;
    height: 80px;
    background: rgba(25, 103, 210, 0.1);
    color: #1967D2;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    margin-bottom: 15px;
}
.sidebar-user-info h5 {
    font-weight: 700;
    color: #202124;
    font-size: 18px;
    margin-bottom: 5px;
}
.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sidebar-menu li a {
    display: flex;
    align-items: center;
    padding: 12px 30px;
    color: #696969;
    font-weight: 500;
    font-size: 15px;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}
.sidebar-menu li a i {
    font-size: 18px;
    margin-right: 15px;
    color: #a0a0a0;
    transition: all 0.3s ease;
}
.sidebar-menu li a:hover, 
.sidebar-menu li.active a {
    color: #1967D2;
    background-color: rgba(25, 103, 210, 0.05);
    border-left: 3px solid #1967D2;
}
.sidebar-menu li a:hover i, 
.sidebar-menu li.active a i {
    color: #1967D2;
}


/* === MAIN CONTENT === */
.superio-card {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0px 6px 15px rgba(64, 79, 104, 0.05);
    padding: 35px 40px;
    margin-bottom: 30px;
    border: none;
}
.superio-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}
.superio-title {
    font-size: 19px;
    font-weight: 700;
    color: #202124;
    margin-bottom: 0;
}
.superio-btn {
    border-radius: 30px;
    font-weight: 500;
    padding: 10px 24px;
    font-size: 15px;
    transition: all 0.3s ease;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.superio-btn-primary {
    background-color: #1967D2;
    color: white;
}
.superio-btn-primary:hover {
    background-color: #1455AB;
    color: white;
}
.superio-btn-light {
    background-color: rgba(25, 103, 210, 0.08);
    color: #1967D2;
    font-weight: 600;
}
.superio-btn-light:hover {
    background-color: #1967D2;
    color: white;
}
.superio-btn-success {
    background-color: rgba(52, 168, 83, 0.1);
    color: #34A853;
    font-weight: 600;
}
.superio-btn-success:hover {
    background-color: #34A853;
    color: white;
}
.timeline-block {
    position: relative;
    padding-left: 35px;
    border-left: 2px solid #e0e6f7;
    margin-bottom: 35px;
    padding-bottom: 15px;
}
.timeline-block:last-child {
    margin-bottom: 0;
    border-left: 2px solid transparent;
}
.timeline-block::before {
    content: "";
    position: absolute;
    left: -8px;
    top: 5px;
    width: 14px;
    height: 14px;
    background: white;
    border: 3px solid #1967D2;
    border-radius: 50%;
}
.timeline-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.timeline-title {
    font-size: 17px;
    font-weight: 600;
    color: #202124;
    margin-bottom: 6px;
}
.timeline-meta {
    color: #1967D2;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 12px;
    background: rgba(25, 103, 210, 0.07);
    padding: 5px 15px;
    border-radius: 20px;
    display: inline-block;
}
.timeline-text {
    color: #696969;
    font-size: 15px;
    line-height: 1.8;
}
.action-btns button {
    width: 35px;
    height: 35px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(25, 103, 210, 0.07);
    color: #1967D2;
    border: none;
    transition: 0.3s;
    margin-left: 8px;
}
.action-btns button:hover {
    background: #1967D2;
    color: #fff;
}
.action-btns button.btn-delete {
    color: #d93025;
    background: rgba(217, 48, 37, 0.1);
}
.action-btns button.btn-delete:hover {
    background: #d93025;
    color: #fff;
}
h5.section-subtitle {
    font-size: 16px;
    font-weight: 600;
    color: #202124;
    margin-bottom: 20px;
    margin-top: 15px;
}
.skill-item {
    margin-bottom: 25px;
}
.skill-item .d-flex {
    font-size: 15px;
    font-weight: 500;
    color: #202124;
    margin-bottom: 10px;
}
.progress-superio {
    height: 8px;
    border-radius: 4px;
    background: #e0e6f7;
    overflow: hidden;
}
.progress-superio .progress-bar {
    background-color: #1967D2;
    border-radius: 4px;
}
.tag-superio {
    background: #F5F7FC;
    color: #696969;
    padding: 8px 20px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 500;
    margin-right: 10px;
    margin-bottom: 10px;
    display: inline-flex;
    align-items: center;
    border: 1px solid #e0e6f7;
    transition: 0.3s;
}
.tag-superio:hover {
    background: rgba(25, 103, 210, 0.07);
    color: #1967D2;
    border-color: rgba(25, 103, 210, 0.2);
}
.tag-superio .btn-delete-tag {
    color: #d93025;
    background: none;
    border: none;
    margin-left: 8px;
    padding: 0;
    display: inline-flex;
    cursor: pointer;
}
.tag-superio .btn-delete-tag:hover {
    color: #a51d14;
}

textarea.form-control-superio {
    border: 1px solid #e0e6f7;
    border-radius: 8px;
    padding: 15px;
    font-size: 15px;
    color: #696969;
    background: #F5F7FC;
    transition: 0.3s;
}
textarea.form-control-superio:focus {
    background: #fff;
    border-color: #1967D2;
    box-shadow: 0 0 0 0.2rem rgba(25, 103, 210, 0.1);
}

.ia-card {
    background: linear-gradient(135deg, #1967D2 0%, #1455AB 100%);
    color: white;
}
.ia-card .superio-title {
    color: white;
}
.ia-result-box {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
    text-align: left;
}
.ia-result-box .score {
    font-size: 48px;
    font-weight: 700;
    color: #ffc107;
    line-height: 1;
}

/* Utilities */
.page-title-box {
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
.page-title-box h2 {
    font-weight: 700;
    color: #202124;
    font-size: 26px;
}
</style>

<div class="container-fluid py-5 animate__animated animate__fadeIn" style="max-width: 1400px;">
    <div class="row g-4">
        
        <!-- SIDEBAR GAUCHE (Navigation Dashboard Candidate) -->
        <div class="col-lg-3">
            <div class="dashboard-sidebar">
                <div class="sidebar-user-info">
                    <div class="user-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <h5><?php echo htmlspecialchars((string)$_SESSION['nom']); ?></h5>
                    <span class="text-muted small">Candidat Admissio</span>
                </div>
                
                <ul class="sidebar-menu">
                    <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> Tableau de bord</a></li>
                    <li><a href="modifier_profil.php"><i class="bi bi-person"></i> Mon Profil</a></li>
                    <li class="active"><a href="mon_cv.php"><i class="bi bi-file-earmark-person"></i> Mon CV Numérique</a></li>
                    <li><a href="mes_candidatures.php"><i class="bi bi-briefcase"></i> Mes Candidatures</a></li>
                    <li><a href="messagerie.php"><i class="bi bi-chat-dots"></i> Messagerie</a></li>
                    <li><a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
                </ul>
                
                <div class="p-4 mt-3">
                    <div class="bg-primary bg-opacity-10 rounded-3 p-3 text-center border border-primary border-opacity-10">
                        <i class="bi bi-file-pdf fs-1 text-primary mb-2"></i>
                        <h6 class="fw-bold fs-6">Besoin d'un PDF ?</h6>
                        <p class="small text-muted mb-3">Téléchargez votre CV généré au format premium.</p>
                        <a href="generer_cv_pdf.php" target="_blank" class="superio-btn superio-btn-primary w-100 justify-content-center" style="font-size: 13px;">Télécharger le CV</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLONNE PRINCIPALE DROITE -->
        <div class="col-lg-9">
            <div class="page-title-box d-flex justify-content-between align-items-center">
                <h2>Mon CV Numérique</h2>
                <button onclick="document.getElementById('importFile').click()" class="superio-btn superio-btn-light shadow-sm">
                    <i class="bi bi-cloud-arrow-up text-primary"></i> Importer un CV (PDF)
                </button>
                <input type="file" id="importFile" class="d-none" accept=".pdf" onchange="importByIA(this)">
            </div>

            <!-- Section Bio -->
            <div class="superio-card">
                <div class="superio-header">
                    <h3 class="superio-title">Résumé Professionnel</h3>
                    <button type="button" class="superio-btn superio-btn-success" onclick="document.getElementById('formBio').dispatchEvent(new Event('submit'))">
                        <i class="bi bi-check2"></i> Enregistrer
                    </button>
                </div>
                <form id="formBio" onsubmit="saveBio(event)">
                    <textarea name="bio" class="form-control form-control-superio w-100" rows="5" placeholder="Décrivez votre parcours, vos objectifs et ce qui vous motive..."><?php echo htmlspecialchars((string)($profil['bio'] ?? '')); ?></textarea>
                </form>
            </div>

            <!-- Section Éducation -->
            <div class="superio-card">
                <div class="superio-header">
                    <h3 class="superio-title">Formation / Études</h3>
                    <button class="superio-btn superio-btn-light" onclick="showAddModal('formation')"><i class="bi bi-plus-lg"></i> Ajouter</button>
                </div>
                <div class="timeline-container ps-2">
                    <?php if(empty($formations)): ?>
                        <p class="text-muted italic">Aucune formation ajoutée.</p>
                    <?php endif; ?>
                    <?php foreach($formations as $f): ?>
                        <div class="timeline-block">
                            <div class="timeline-header">
                                <div>
                                    <h4 class="timeline-title"><?php echo htmlspecialchars((string)$f['diplome']); ?></h4>
                                    <div class="timeline-meta">
                                        <?php echo htmlspecialchars((string)$f['etablissement']); ?> <?php if(!empty($f['ville'])) echo " • " . htmlspecialchars((string)$f['ville']); ?>
                                        | <i class="bi bi-calendar3 ms-1 me-1"></i> <?php echo date('Y', strtotime($f['date_debut'])); ?> - <?php echo $f['date_fin'] ? date('Y', strtotime($f['date_fin'])) : 'Présent'; ?>
                                    </div>
                                </div>
                                <div class="action-btns">
                                    <button onclick="editItem('formation', <?php echo $f['id']; ?>)"><i class="bi bi-pencil"></i></button>
                                    <button class="btn-delete" onclick="deleteItem('formation', <?php echo $f['id']; ?>)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <p class="timeline-text mb-0"><?php echo nl2br(htmlspecialchars((string)($f['description'] ?? ''))); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Section Expériences -->
            <div class="superio-card">
                <div class="superio-header">
                    <h3 class="superio-title">Expériences Professionnelles</h3>
                    <button class="superio-btn superio-btn-light" onclick="showAddModal('experience')"><i class="bi bi-plus-lg"></i> Ajouter</button>
                </div>
                <div class="timeline-container ps-2">
                    <?php if(empty($experiences)): ?>
                        <p class="text-muted italic">Aucune expérience ajoutée.</p>
                    <?php endif; ?>
                    <?php foreach($experiences as $e): ?>
                        <div class="timeline-block">
                            <div class="timeline-header">
                                <div>
                                    <h4 class="timeline-title"><?php echo htmlspecialchars((string)$e['poste']); ?></h4>
                                    <div class="timeline-meta">
                                        <?php echo htmlspecialchars((string)$e['entreprise']); ?> <?php if(!empty($e['ville'])) echo " • " . htmlspecialchars((string)$e['ville']); ?>
                                        | <i class="bi bi-calendar3 ms-1 me-1"></i> <?php echo date('Y', strtotime($e['date_debut'])); ?> - <?php echo $e['en_poste'] ? 'Présent' : date('Y', strtotime($e['date_fin'])); ?>
                                    </div>
                                </div>
                                <div class="action-btns">
                                    <button onclick="editItem('experience', <?php echo $e['id']; ?>)"><i class="bi bi-pencil"></i></button>
                                    <button class="btn-delete" onclick="deleteItem('experience', <?php echo $e['id']; ?>)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <p class="timeline-text mb-0"><?php echo nl2br(htmlspecialchars((string)($e['description'] ?? ''))); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Section Compétences, Langues, Certifications -->
            <div class="row">
                <!-- Compétences -->
                <div class="col-md-6">
                    <div class="superio-card h-100 mb-4">
                        <div class="superio-header flex-wrap mb-4">
                            <h3 class="superio-title mb-2">Compétences</h3>
                            <button class="superio-btn superio-btn-light btn-sm" onclick="showAddModal('competence')"><i class="bi bi-plus-lg"></i></button>
                        </div>

                        <h5 class="section-subtitle">Aptitudes Techniques</h5>
                        <?php 
                        $techs = array_filter($competences, fn($c) => $c['type'] === 'technique');
                        if(empty($techs)) echo '<p class="text-muted small">Aucune compétence technique.</p>';
                        foreach($techs as $c): 
                        ?>
                            <div class="skill-item">
                                <div class="d-flex justify-content-between">
                                    <span><?php echo htmlspecialchars((string)$c['nom']); ?> <button class="btn btn-link text-danger p-0 ms-1" style="font-size:12px;" onclick="deleteItem('competence', <?php echo $c['id']; ?>)"><i class="bi bi-trash"></i></button></span>
                                    <span class="text-muted small"><?php echo $c['niveau']; ?>%</span>
                                </div>
                                <div class="progress progress-superio">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $c['niveau']; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <h5 class="section-subtitle border-top pt-3 mt-4">Savoir-être (Soft Skills)</h5>
                        <div class="d-flex flex-wrap">
                            <?php 
                            $softs = array_filter($competences, fn($c) => $c['type'] === 'professionnelle');
                            if(empty($softs)) echo '<p class="text-muted small">Aucun soft skill renseigné.</p>';
                            foreach($softs as $c): 
                            ?>
                                <div class="tag-superio">
                                    <?php echo htmlspecialchars((string)$c['nom']); ?> 
                                    <button class="btn-delete-tag" onclick="deleteItem('competence', <?php echo $c['id']; ?>)"><i class="bi bi-x-circle-fill"></i></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Langues & Certifs -->
                <div class="col-md-6">
                    <!-- Langues -->
                    <div class="superio-card mb-4" style="margin-bottom: 24px;">
                        <div class="superio-header mb-4">
                            <h3 class="superio-title">Langues</h3>
                            <button class="superio-btn superio-btn-light btn-sm" onclick="showAddModal('langue')"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <?php if(empty($langues)): ?>
                            <p class="text-muted small">Aucune langue définie.</p>
                        <?php endif; ?>
                        <?php foreach($langues as $l): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars((string)$l['langue']); ?></div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill border border-primary border-opacity-25 shadow-sm"><?php echo ucfirst(htmlspecialchars((string)$l['niveau'])); ?></span>
                                    <button class="btn btn-link text-danger p-0 border-0 ms-2" onclick="deleteItem('langue', <?php echo $l['id']; ?>)"><i class="bi bi-trash"></i></button>
                                </div>
                            </div>
                            <hr class="text-muted opacity-25 m-0 mb-3">
                        <?php endforeach; ?>
                    </div>

                    <!-- Certifications -->
                    <div class="superio-card mb-4">
                        <div class="superio-header mb-4">
                            <h3 class="superio-title">Certifications</h3>
                            <button class="superio-btn superio-btn-light btn-sm" onclick="showAddModal('certification')"><i class="bi bi-plus-lg"></i></button>
                        </div>
                        <?php if(empty($certifications)): ?>
                            <p class="text-muted small">Aucune certification.</p>
                        <?php endif; ?>
                        <div class="d-flex flex-wrap">
                            <?php foreach($certifications as $cert): ?>
                                <div class="tag-superio w-100 justify-content-between align-items-center ps-4 mb-2">
                                    <div class="py-2">
                                        <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars((string)$cert['nom']); ?></div>
                                        <div class="small opacity-75 mt-1"><i class="bi bi-award text-success me-1"></i> <?php echo htmlspecialchars((string)$cert['organisme']); ?> • <?php echo date('Y', strtotime($cert['date_obtention'])); ?></div>
                                    </div>
                                    <button class="btn-delete-tag" onclick="deleteItem('certification', <?php echo $cert['id']; ?>)"><i class="bi bi-trash fs-5"></i></button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Assistant AI -->
            <div class="superio-card ia-card text-center mb-5">
                <div class="display-4 text-warning mb-3"><i class="bi bi-stars"></i></div>
                <h3 class="superio-title">Coaching de Carrière par IA</h3>
                <p class="mb-4" style="color: rgba(255,255,255,0.85);">
                    Faites examiner votre profil par notre Intelligence Artificielle (Gemini 1.5) qui vous dévoilera comment attirer l'œil des recruteurs les plus exigeants.
                </p>
                <button class="superio-btn fw-bold px-4 text-dark bg-warning" id="btn-analyze" onclick="analyzeMyProfile()">
                    <i class="bi bi-magic text-dark"></i> Découvrir l'analyse
                </button>
                
                <div id="ia-result" class="ia-result-box d-none animate__animated animate__fadeInUp">
                    <div class="d-flex align-items-center gap-4 mb-3 border-bottom pb-3 border-white border-opacity-10">
                        <div class="score" id="ia-score">...</div>
                        <div class="text-white">
                            <h5 class="fw-bold mb-1">Score d'Attractivité</h5>
                            <p class="small mb-0 text-white-50">Évaluation globale de la force de votre CV par rapport aux standards du marché.</p>
                        </div>
                    </div>
                    <h6 class="fw-bold mb-3 text-warning">Suggestions pour vous améliorer :</h6>
                    <div id="ia-suggestions" class="small mb-3 lh-lg text-white"></div>
                    <div id="ia-verdict" class="fst-italic border-start border-3 border-warning ps-3 mt-3"></div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Unifié (Resté Bootstrap Standard mais adapté visuellement) -->
<div class="modal fade" id="cvModal" tabindex="-1" style="backdrop-filter: blur(5px);">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-header border-bottom-0 p-4" style="background:#F5F7FC;">
                <h5 class="modal-title fw-bold text-dark" id="cvModalTitle">Gestion Profil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <form id="modalForm">
                    <input type="hidden" name="action" id="modalAction" value="add">
                    <input type="hidden" name="type" id="modalType">
                    <input type="hidden" name="id" id="itemId">
                    
                    <div id="modalFields" class="row g-3"></div>
                    
                    <div class="d-flex justify-content-end gap-3 mt-4 pt-3 border-top pb-2">
                        <button type="button" class="superio-btn btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="superio-btn superio-btn-primary"><i class="bi bi-save"></i> Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let cvModal;
document.addEventListener('DOMContentLoaded', function() {
    cvModal = new bootstrap.Modal(document.getElementById('cvModal'));
});

/** MODAL & RENDU **/
function showAddModal(type) {
    document.getElementById('cvModalTitle').innerText = "Nouvel élément : " + type.charAt(0).toUpperCase() + type.slice(1);
    document.getElementById('modalType').value = type;
    document.getElementById('modalAction').value = 'add';
    document.getElementById('itemId').value = '';
    renderFields(type, {});
    cvModal.show();
}

function editItem(type, id) {
    const fd = new FormData(); fd.append('action', 'getItem'); fd.append('type', type); fd.append('id', id);
    fetch('mon_cv_ajax.php', { method: 'POST', body: fd })
    .then(r=>r.json())
    .then(d=>{
        if(d.success) {
            document.getElementById('cvModalTitle').innerText = "Modification : " + type.charAt(0).toUpperCase() + type.slice(1);
            document.getElementById('modalType').value = type;
            document.getElementById('modalAction').value = 'edit';
            document.getElementById('itemId').value = id;
            renderFields(type, d.data);
            cvModal.show();
        } else {
            alert("Erreur de chargement des données.");
        }
    });
}

function renderFields(type, data) {
    let h = '';
    const styleInput = "form-control form-control-superio";
    
    if(type==='formation') {
        h = `<div class="col-12"><label class="small fw-bold mb-1 text-muted">Intitulé du diplôme</label><input type="text" name="diplome" class="${styleInput}" value="${data.diplome||''}" required></div>
             <div class="col-md-6"><label class="small fw-bold mb-1 text-muted">Établissement</label><input type="text" name="etablissement" class="${styleInput}" value="${data.etablissement||''}" required></div>
             <div class="col-md-6"><label class="small fw-bold mb-1 text-muted">Ville</label><input type="text" name="ville" class="${styleInput}" value="${data.ville||''}"></div>
             <div class="col-md-6"><label class="small fw-bold mb-1 text-muted">Date de début</label><input type="date" name="date_debut" class="${styleInput}" value="${data.date_debut||''}" required></div>
             <div class="col-md-6"><label class="small fw-bold mb-1 text-muted">Date de fin</label><input type="date" name="date_fin" class="${styleInput}" value="${data.date_fin||''}"></div>
             <div class="col-12"><label class="small fw-bold mb-1 text-muted">Description / Détails</label><textarea name="description" class="${styleInput}" rows="3">${data.description||''}</textarea></div>`;
    }
    else if(type==='experience') {
        const check = data.en_poste == 1 ? 'checked' : '';
        const disabled = data.en_poste == 1 ? 'disabled' : '';
        h = `<div class="col-12"><label class="small fw-bold mb-1 text-muted">Poste / Intitulé</label><input type="text" name="poste" class="${styleInput}" value="${data.poste||''}" required></div>
             <div class="col-md-6"><label class="small fw-bold mb-1 text-muted">Entreprise</label><input type="text" name="entreprise" class="${styleInput}" value="${data.entreprise||''}" required></div>
             <div class="col-md-6"><label class="small fw-bold mb-1 text-muted">Ville</label><input type="text" name="ville" class="${styleInput}" value="${data.ville||''}"></div>
             <div class="col-md-5"><label class="small fw-bold mb-1 text-muted">Début</label><input type="date" name="date_debut" class="${styleInput}" value="${data.date_debut||''}" required></div>
             <div class="col-md-5"><label class="small fw-bold mb-1 text-muted">Fin</label><input type="date" id="f_date_fin" name="date_fin" class="${styleInput}" value="${data.date_fin||''}" ${disabled}></div>
             <div class="col-md-2 d-flex align-items-end mb-2 pt-2">
                 <div class="form-check form-switch ps-4">
                     <input class="form-check-input" type="checkbox" name="en_poste" value="1" id="f_en_poste" ${check} onchange="document.getElementById('f_date_fin').disabled=this.checked"> 
                     <label class="form-check-label small fw-bold mt-1 ms-1 text-dark" for="f_en_poste">En poste</label>
                 </div>
             </div>
             <div class="col-12"><label class="small fw-bold mb-1 text-muted">Missions et réalisations</label><textarea name="description" class="${styleInput}" rows="4">${data.description||''}</textarea></div>`;
    } 
    else if(type==='competence') {
        h = `<div class="col-12"><label class="small fw-bold mb-1 text-muted">Nom (ex: Anglais, Python...)</label><input type="text" name="nom" class="${styleInput}" value="${data.nom||''}" required></div>
             <div class="col-md-6"><label class="small fw-bold mb-1 text-muted">Type</label><select name="type" class="form-select" style="border-radius:8px; padding:12px;"><option value="technique" ${data.type==='technique'?'selected':''}>Hard Skill (Technique)</option><option value="professionnelle" ${data.type==='professionnelle'?'selected':''}>Soft Skill (Savoir-être)</option></select></div>
             <div class="col-md-6"><label class="small fw-bold mb-1 text-muted">Niveau (1 à 100%)</label><input type="number" name="niveau" class="${styleInput}" value="${data.niveau||75}" min="1" max="100"></div>`;
    }
    else if(type==='langue') {
        h = `<div class="col-12"><label class="small fw-bold mb-1 text-muted">Langue</label><input type="text" name="langue" class="${styleInput}" value="${data.langue||''}" required></div>
             <div class="col-12"><label class="small fw-bold mb-1 text-muted">Niveau de maîtrise</label><select name="niveau" class="form-select" style="border-radius:8px; padding:12px;">${['notions','intermediaire','avance','bilingue','maternel'].map(v=>`<option value="${v}" ${data.niveau===v?'selected':''}>${v.toUpperCase()}</option>`).join('')}</select></div>`;
    }
    else if(type==='certification') {
        h = `<div class="col-12"><label class="small fw-bold mb-1 text-muted">Nom du certificat</label><input type="text" name="nom" class="${styleInput}" value="${data.nom||''}" required></div>
             <div class="col-12"><label class="small fw-bold mb-1 text-muted">Organisme émetteur</label><input type="text" name="organisme" class="${styleInput}" value="${data.organisme||''}" required></div>
             <div class="col-12"><label class="small fw-bold mb-1 text-muted">Date d'obtention</label><input type="date" name="date_obtention" class="${styleInput}" value="${data.date_obtention||''}"></div>`;
    }
    
    document.getElementById('modalFields').innerHTML = h;
}

/** AJAX OPS **/
document.getElementById('modalForm').onsubmit = function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const oldText = btn.innerHTML;
    btn.disabled = true; 
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sauvegarde...';
    
    fetch('mon_cv_ajax.php', { method: 'POST', body: new FormData(this) })
    .then(r=>r.json())
    .then(d=>{ 
        if(d.success) {
            location.reload(); 
        } else { 
            btn.disabled=false; 
            btn.innerHTML=oldText; 
            alert(d.error || "Erreur de traitement."); 
        } 
    })
    .catch(err => {
        btn.disabled=false; 
        btn.innerHTML=oldText;
        alert("Erreur réseau");
    });
};

function saveBio(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button'); 
    const formEle = document.getElementById('formBio');
    const oldHtml = btn ? btn.innerHTML : '';
    if(btn) { btn.disabled=true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ...'; }
    
    const fd = new FormData(formEle); 
    fd.append('action', 'save_bio');
    
    fetch('mon_cv_ajax.php', { method: 'POST', body: fd })
    .then(r=>r.json())
    .then(d=>{ 
        if(btn) { btn.disabled=false; btn.innerHTML = oldHtml; }
        
        if(d.success) {
            const textarea = formEle.querySelector('textarea');
            textarea.style.borderColor = '#34A853';
            setTimeout(() => textarea.style.borderColor = '#e0e6f7', 2000);
        } else {
            alert(d.error); 
        }
    });
}

function deleteItem(t, id) {
    if(confirm("Confirmer la suppression automatique de cet élément ?")){
        const fd = new FormData(); fd.append('action', 'delete'); fd.append('type', t); fd.append('id', id);
        fetch('mon_cv_ajax.php', { method: 'POST', body: fd })
        .then(r=>r.json())
        .then(d=>{ 
            if(d.success) location.reload(); 
            else alert(d.error); 
        });
    }
}

function analyzeMyProfile() {
    const btn = document.getElementById('btn-analyze'); 
    const oldHtml = btn.innerHTML;
    btn.disabled = true; 
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Scan AI en cours...';
    
    const resDiv = document.getElementById('ia-result');
    resDiv.classList.add('d-none');
    
    fetch('mon_cv_ajax.php', { method: 'POST', body: new URLSearchParams('action=analyze_profile') })
    .then(r=>r.json())
    .then(d=>{
        btn.disabled = false; 
        btn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i> Actualiser le diagnostic';
        
        if(d.success && d.advice && !d.advice.error) {
            resDiv.classList.remove('d-none');
            const a = d.advice;
            document.getElementById('ia-score').innerText = (a.score_estime || a.score || 0) + '%';
            
            const suggMap = Array.isArray(a.suggestions) ? a.suggestions : [];
            document.getElementById('ia-suggestions').innerHTML = suggMap.map(s=>`<div class="mb-2"><i class="bi bi-check-circle-fill text-warning me-2"></i>${s}</div>`).join('');
            document.getElementById('ia-verdict').innerText = a.verdict_flash || a.resume || 'Analyse terminée.';
        } else {
            alert(d.advice?.error || d.error || "L'assistant IA est mal configuré ou indisponible (Quota atteint).");
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
        alert("Erreur de connexion avec le serveur IA Google.");
    });
}

function importByIA(input) {
    if (!input.files || !input.files[0]) return;
    if (!confirm("Attention : Cette action va lire le PDF et remplacer automatiquement toutes vos compétences/expériences actuelles. Lancer la magie de l'IA ?")) {
        input.value = "";
        return;
    }
    
    const btn = input.previousElementSibling;
    const oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Extraction...';
    
    const fd = new FormData();
    fd.append('action', 'import_pdf');
    fd.append('cv_file', input.files[0]);
    
    fetch('mon_cv_ajax.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert("Incroyable ! Votre profil a été reconstruit à partir de votre PDF.");
            location.reload();
        } else {
            alert("Échec de l'extraction : " + (d.error || "Document corrompu"));
            btn.disabled = false;
            btn.innerHTML = oldHtml;
            input.value = "";
        }
    })
    .catch(err => {
        alert("Erreur réseau pendant l'upload.");
        btn.disabled = false;
        btn.innerHTML = oldHtml;
        input.value = "";
    });
}
</script>

<?php include_footer(); ?>
