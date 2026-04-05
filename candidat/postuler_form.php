<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_candidat = $_SESSION["id"];
$id_offre = (int)($_GET["id"] ?? 0);

if ($id_offre <= 0) {
    header("Location: liste_offres.php");
    exit();
}

try {
    // Vérifier l'offre
    $stmt = $pdo->prepare("SELECT * FROM offres WHERE id = ? AND statut = 'actif' AND date_cloture >= CURDATE() AND date_ouverture <= CURDATE()");
    $stmt->execute([$id_offre]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offre) {
        $_SESSION['error_message'] = "Cette offre n'est plus ouverte aux candidatures.";
        header("Location: liste_offres.php");
        exit();
    }

    // Vérifier si déjà postulé
    $stmt = $pdo->prepare("SELECT id FROM candidatures WHERE id_candidat = ? AND id_offre = ?");
    $stmt->execute([$id_candidat, $id_offre]);
    if ($stmt->fetch()) {
        header("Location: details_offre.php?id=$id_offre");
        exit();
    }

    // Charger les champs dynamiques
    $stmt_champs = $pdo->prepare("SELECT * FROM champs_formulaire WHERE id_offre = ? ORDER BY ordre ASC");
    $stmt_champs->execute([$id_offre]);
    $champs = $stmt_champs->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    send_error("Erreur technique.");
}

include_header("Postuler : " . $offre['titre']);
?>

<div class="row justify-content-center animate__animated animate__fadeIn">
    <div class="col-lg-9 col-xl-8">
        <div class="px-4 py-4 rounded-4 shadow-premium mb-4 d-flex justify-content-between align-items-center glass">
            <div>
                <a href="details_offre.php?id=<?php echo $id_offre; ?>" class="btn btn-sm btn-light rounded-pill px-3 border mb-2 mb-md-0 me-2"><i class="bi bi-arrow-left"></i> Détails</a>
                <h1 class="h3 mb-0 fw-extrabold text-dark d-inline-block align-middle mt-2">Dossier de Candidature</h1>
                <p class="text-muted mb-0 ms-0 ms-md-5 ps-0 ps-md-2 mt-1 fw-medium" style="color: var(--p-indigo) !important;"><?php echo htmlspecialchars($offre['titre']); ?></p>
            </div>
            <div class="d-none d-md-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 60px; height: 60px;">
                <i class="bi bi-envelope-paper fs-3"></i>
            </div>
        </div>

        <form action="candidater.php" method="POST" enctype="multipart/form-data" class="card border-0 shadow-premium rounded-5 overflow-hidden mb-5">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="id" value="<?php echo $id_offre; ?>">

            <div class="card-body p-4 p-md-5">
                <!-- Documents Standards -->
                <div class="mb-5 animate__animated animate__fadeInUp animate__delay-1s">
                    <h5 class="fw-extrabold mb-4 text-dark d-flex align-items-center gap-2">
                        <span class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-size: 1rem;">1</span>
                        Documents Obligatoires
                    </h5>
                    <div class="row g-4 p-4 bg-light bg-opacity-50 rounded-4 border">
                        <div class="col-md-6 field-with-ia">
                            <label class="form-label fw-bold text-muted small text-uppercase ls-1">Curriculum Vitae (CV) <span class="text-danger">*</span></label>
                            <input type="file" name="cv" id="cv-input" class="form-control" required accept=".pdf">
                            <div class="form-text small opacity-75 mt-2"><i class="bi bi-info-circle me-1"></i> Format PDF recommandé (Max 5Mo).</div>
                            
                            <!-- Assistant IA Admissio -->
                            <div id="ia-assistant-wrapper" class="mt-3 p-3 rounded-4 border d-none animate__animated animate__fadeIn" style="background: #f8fafc;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="small fw-bold text-primary align-items-center gap-1 d-flex">
                                        <i class="bi bi-robot"></i> Assistant Conseil IA Admissio
                                    </div>
                                    <button type="button" id="btn-analyze-ia" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold">Analyser mon CV</button>
                                </div>
                                <div id="ia-loader" class="text-center py-2 d-none">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                    <span class="small text-muted ms-2 italic">Gemini étudie votre document...</span>
                                </div>
                                <div id="ia-result" class="d-none">
                                    <div class="d-flex align-items-center gap-3 p-2 bg-white rounded-3 shadow-ssm border mb-2">
                                        <div id="ia-score" class="fs-4 fw-black text-primary">0%</div>
                                        <div class="small text-muted fw-medium lh-sm" id="ia-verdict">Vérification de compatibilité en cours...</div>
                                    </div>
                                    <div class="small text-dark mb-1 fw-bold"><i class="bi bi-lightbulb text-warning"></i> Conseils d'optimisation :</div>
                                    <ul id="ia-suggestions" class="ps-3 mb-0" style="font-size: 0.8rem; line-height: 1.4;"></ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small text-uppercase ls-1">Lettre de Motivation <span class="text-danger">*</span></label>
                            <input type="file" name="lettre" class="form-control" required accept=".pdf,.doc,.docx">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold text-muted small text-uppercase ls-1">Diplôme(s) / Relevé(s) de notes <span class="text-danger">*</span></label>
                            <input type="file" name="diplome[]" class="form-control" required accept=".pdf,.jpg,.jpeg,.png" multiple>
                            <div class="form-text small opacity-75 mt-1"><i class="bi bi-info-circle me-1"></i> Vous pouvez sélectionner plusieurs fichiers à la fois.</div>
                        </div>
                    </div>
                </div>

                <!-- Champs Dynamiques -->
                <?php if (!empty($champs)): ?>
                    <div class="mb-4 animate__animated animate__fadeInUp animate__delay-2s">
                        <h5 class="fw-extrabold mb-4 text-dark d-flex align-items-center gap-2">
                            <span class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-size: 1rem;">2</span>
                            Informations Complémentaires
                        </h5>
                        <div class="row g-4 p-4 rounded-4 border-dashed" style="border: 2px dashed #e2e8f0;">
                            <?php foreach ($champs as $c): ?>
                                <div class="<?php echo ($c['type_champ'] === 'textarea') ? 'col-12' : 'col-md-6'; ?>">
                                    <label class="form-label fw-bold text-muted small text-uppercase ls-1">
                                        <?php echo htmlspecialchars($c['libelle']); ?>
                                        <?php if ($c['obligatoire']): ?><span class="text-danger">*</span><?php endif; ?>
                                    </label>
                                    
                                    <?php if ($c['type_champ'] === 'texte'): ?>
                                        <input type="text" name="champ_<?php echo $c['id']; ?>" class="form-control" placeholder="Entrez la réponse..." <?php echo $c['obligatoire'] ? 'required' : ''; ?>>
                                    
                                    <?php elseif ($c['type_champ'] === 'nombre'): ?>
                                        <input type="number" name="champ_<?php echo $c['id']; ?>" class="form-control" <?php echo $c['obligatoire'] ? 'required' : ''; ?>>
                                    
                                    <?php elseif ($c['type_champ'] === 'date'): ?>
                                        <input type="date" name="champ_<?php echo $c['id']; ?>" class="form-control" <?php echo $c['obligatoire'] ? 'required' : ''; ?>>
                                    
                                    <?php elseif ($c['type_champ'] === 'textarea'): ?>
                                        <textarea name="champ_<?php echo $c['id']; ?>" class="form-control" rows="3" placeholder="..." <?php echo $c['obligatoire'] ? 'required' : ''; ?>></textarea>
                                    
                                    <?php elseif ($c['type_champ'] === 'liste'): ?>
                                        <select name="champ_<?php echo $c['id']; ?>" class="form-select" <?php echo $c['obligatoire'] ? 'required' : ''; ?>>
                                            <option value="">Sélectionner une option...</option>
                                            <?php 
                                            $options = explode("\n", $c['options_liste']); 
                                            foreach ($options as $opt): $opt = trim($opt); if ($opt !== ""): ?>
                                                <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                                            <?php endif; endforeach; ?>
                                        </select>
                                    
                                    <?php elseif ($c['type_champ'] === 'case_a_cocher'): ?>
                                        <div class="form-check form-switch mt-2 p-3 bg-light rounded-3 border">
                                            <input class="form-check-input ms-0 me-3" type="checkbox" name="champ_<?php echo $c['id']; ?>" id="chk_<?php echo $c['id']; ?>">
                                            <label class="form-check-label fw-bold" for="chk_<?php echo $c['id']; ?>">Confirmer / Accepter</label>
                                        </div>

                                    <?php elseif ($c['type_champ'] === 'fichier'): ?>
                                        <input type="file" name="champ_<?php echo $c['id']; ?>" class="form-control" <?php echo $c['obligatoire'] ? 'required' : ''; ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info border-0 rounded-4 d-flex align-items-center mt-5 p-4 glass">
                    <span class="fs-2 me-4">✅</span>
                    <div class="small fw-medium">En soumettant ce formulaire, vous certifiez l'exactitude des informations fournies. Votre dossier sera examiné par nos services dans les plus brefs délais.</div>
                </div>
            </div>

            <div class="card-footer bg-white p-5 text-center border-0">
                <button type="submit" class="btn btn-primary btn-lg px-5 py-3 fw-extrabold rounded-pill shadow-premium transition-hover w-100 w-md-auto">
                    Soumettre mon dossier <i class="bi bi-rocket-takeoff ms-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    if (e.target.method.toUpperCase() === 'POST') {
        const btn = e.target.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi en cours...';
    }
});

// Logique Assistant IA
const cvInput = document.getElementById('cv-input');
const iaWrapper = document.getElementById('ia-assistant-wrapper');
const btnAnalyze = document.getElementById('btn-analyze-ia');
const iaLoader = document.getElementById('ia-loader');
const iaResult = document.getElementById('ia-result');

cvInput.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        iaWrapper.classList.remove('d-none');
        iaResult.classList.add('d-none');
    } else {
        iaWrapper.classList.add('d-none');
    }
});

btnAnalyze.addEventListener('click', function() {
    const file = cvInput.files[0];
    if (!file) return;

    btnAnalyze.classList.add('d-none');
    iaLoader.classList.remove('d-none');
    iaResult.classList.add('d-none');

    const formData = new FormData();
    formData.append('cv', file);
    formData.append('id_offre', <?php echo $id_offre; ?>);

    fetch('optimiser_cv_ajax.php', {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        iaLoader.classList.add('d-none');
        btnAnalyze.classList.remove('d-none');
        btnAnalyze.textContent = "Ré-analyser";

        if (data.error) {
            alert(data.error);
            return;
        }

        const res = data.suggestions;
        document.getElementById('ia-score').textContent = res.score_estime + '%';
        document.getElementById('ia-verdict').textContent = res.verdict_flash;
        
        const suggList = document.getElementById('ia-suggestions');
        suggList.innerHTML = '';
        res.suggestions.slice(0, 3).forEach(s => {
            const li = document.createElement('li');
            li.textContent = s;
            suggList.appendChild(li);
        });

        iaResult.classList.remove('d-none');
    })
    .catch(err => {
        iaLoader.classList.add('d-none');
        btnAnalyze.classList.remove('d-none');
        alert("Erreur de connexion : " + err.message);
    });
});
</script>

<?php 
include_footer();
exit();
?>
