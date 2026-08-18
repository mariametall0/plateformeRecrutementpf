<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    $titre           = trim($_POST["titre"] ?? "");
    $description     = trim($_POST["description"] ?? "");
    $date_ouverture  = $_POST["date_ouverture"] ?? "";
    $heure_ouverture = $_POST["heure_ouverture"] ?? "";
    $date_cloture    = $_POST["date_cloture"] ?? "";
    $heure_cloture   = $_POST["heure_cloture"] ?? "";

    if (empty($titre) || empty($description) || empty($date_ouverture) || empty($date_cloture)) {
        $errors[] = "Tous les champs obligatoires doivent être remplis.";
    } elseif ($date_cloture < $date_ouverture) {
        $errors[] = "La date de clôture ne peut pas être avant la date d'ouverture.";
    } else {
        $secteur = trim($_POST["secteur"] ?? "");
        try {
            $stmt = $pdo->prepare("
                INSERT INTO concours (titre, description, secteur, date_ouverture, heure_ouverture, date_cloture, heure_cloture, id_gerant, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'inactif')
            ");
            $stmt->execute([$titre, $description, $secteur, $date_ouverture, $heure_ouverture, $date_cloture, $heure_cloture, $id_gerant]);
            
            $_SESSION['success_message'] = "Nouvelle opportunité créée avec succès ! Vous pouvez maintenant la configurer.";
            header("Location: liste_opportunites.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Erreur base de données : " . $e->getMessage();
        }
    }
}

include_header("Nouvelle Opportunité");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header Banner -->
            <div class="glass-premium p-5 rounded-4 d-flex justify-content-between align-items-center mb-5 anim-up">
                <div>
                    <a href="liste_opportunites.php" class="btn btn-sm btn-white border rounded-pill px-3 mb-3 fw-bold"><i class="bi bi-arrow-left"></i> Retour</a>
                    <h1 class="display-6 fw-black text-gray-900 mb-2">Publier une Opportunité</h1>
                    <p class="text-muted mb-0">Lancez une nouvelle campagne de recrutement en quelques clics.</p>
                </div>
                <div class="stat-icon-luminous">
                    <i class="bi bi-plus-circle"></i>
                </div>
            </div>

            <!-- Import IA Section -->
            <div class="glass-premium p-4 rounded-4 shadow-sm mb-5 anim-up">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
                        <i class="bi bi-file-earmark-arrow-up fs-4"></i>
                    </div>
                    <div>
                        <h5 class="fw-black text-gray-900 mb-1">Importer un fichier</h5>
                        <p class="text-muted small mb-0">Téléchargez une offre d'emploi (PDF, DOCX) pour pré-remplir le formulaire automatiquement.</p>
                    </div>
                </div>
                <div class="border border-2 border-dashed border-success border-opacity-25 rounded-4 p-4 text-center hover-shadow transition-all bg-white" id="upload-zone" style="cursor: pointer;">
                    <input type="file" id="document_opportunite" class="d-none" accept=".pdf,.doc,.docx,.txt">
                    <div id="upload-content">
                        <i class="bi bi-cloud-arrow-up text-success display-4 mb-2"></i>
                        <h6 class="fw-bold">Cliquez ou glissez votre document ici</h6>
                        <span class="text-muted small">PDF, DOC, DOCX ou TXT (Max 5MB)</span>
                    </div>
                    <div id="upload-loading" class="d-none py-3">
                        <div class="spinner-border text-success mb-2" role="status"></div>
                        <h6 class="fw-bold text-success mb-0">Extraction des données du document...</h6>
                        <span class="text-muted small">Cela peut prendre quelques secondes</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4">
                    <ul class="mb-0 fw-bold small">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="bg-white shadow-premium rounded-4 overflow-hidden mb-5 anim-up anim-delay-1">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="p-5">
                    <div class="mb-5">
                        <h5 class="fw-black text-gray-900 mb-4 d-flex align-items-center gap-3">
                            <span class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:30px; height:30px; font-size:0.8rem;">1</span>
                            Détails de l'Opportunité
                        </h5>
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Titre du poste <span class="text-danger">*</span></label>
                                <input type="text" name="titre" class="form-control form-control-lg rounded-3 fw-bold" placeholder="Ex: Ingénieur Système Senior" required value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Secteur / Domaine</label>
                                <input type="text" name="secteur" class="form-control form-control-lg rounded-3" placeholder="Ex: Informatique, Finance..." value="<?php echo htmlspecialchars($_POST['secteur'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Description complète <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control rounded-3" rows="8" placeholder="Décrivez le rôle, les missions et le profil recherché..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <hr class="opacity-5 mb-5">

                    <div class="mb-4">
                        <h5 class="fw-black text-gray-900 mb-4 d-flex align-items-center gap-3">
                            <span class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:30px; height:30px; font-size:0.8rem;">2</span>
                            Calendrier de Publication
                        </h5>
                        <div class="row g-4 p-4 bg-light rounded-4 border">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Ouverture (Date & Heure) <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <input type="date" name="date_ouverture" class="form-control rounded-3" required value="<?php echo htmlspecialchars($_POST['date_ouverture'] ?? date('Y-m-d')); ?>">
                                    <input type="time" name="heure_ouverture" class="form-control rounded-3" value="08:00">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2">Clôture (Date & Heure) <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <input type="date" name="date_cloture" class="form-control rounded-3" required value="<?php echo htmlspecialchars($_POST['date_cloture'] ?? ''); ?>">
                                    <input type="time" name="heure_cloture" class="form-control rounded-3" value="18:00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <button type="submit" class="btn-premium px-5 py-3 shadow-luminous">
                            Créer l'Opportunité <i class="bi bi-rocket-takeoff-fill ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadZone = document.getElementById('upload-zone');
    const fileInput = document.getElementById('document_opportunite');
    const uploadContent = document.getElementById('upload-content');
    const uploadLoading = document.getElementById('upload-loading');

    // Trigger file input on click
    uploadZone.addEventListener('click', () => fileInput.click());

    // Handle drag and drop
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('bg-success', 'bg-opacity-10');
    });

    uploadZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('bg-success', 'bg-opacity-10');
    });

    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('bg-success', 'bg-opacity-10');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileUpload();
        }
    });

    fileInput.addEventListener('change', function() {
        if (this.files.length) handleFileUpload();
    });

    function handleFileUpload() {
        const file = fileInput.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('document_opportunite', file);

        uploadContent.classList.add('d-none');
        uploadLoading.classList.remove('d-none');

        fetch('parse_opportunite_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            uploadLoading.classList.add('d-none');
            uploadContent.classList.remove('d-none');

            if (data.success && data.data) {
                // Remplir les champs
                if (data.data.titre) {
                    document.querySelector('input[name="titre"]').value = data.data.titre;
                    document.querySelector('input[name="titre"]').classList.add('border-success', 'bg-success', 'bg-opacity-10');
                }
                if (data.data.secteur) {
                    document.querySelector('input[name="secteur"]').value = data.data.secteur;
                    document.querySelector('input[name="secteur"]').classList.add('border-success', 'bg-success', 'bg-opacity-10');
                }
                if (data.data.description) {
                    document.querySelector('textarea[name="description"]').value = data.data.description;
                    document.querySelector('textarea[name="description"]').classList.add('border-success', 'bg-success', 'bg-opacity-10');
                }

                // Afficher un toast ou message
                alert("Analyse réussie ! Les champs ont été pré-remplis.");
                
                // Retirer les effets après 3 secondes
                setTimeout(() => {
                    document.querySelectorAll('.border-success.bg-opacity-10').forEach(el => {
                        el.classList.remove('border-success', 'bg-success', 'bg-opacity-10');
                    });
                }, 3000);
            } else {
                alert("Erreur : " + (data.error || "Impossible d'analyser le document."));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            uploadLoading.classList.add('d-none');
            uploadContent.classList.remove('d-none');
            alert("Erreur lors de la communication avec le serveur.");
        });
        
        // Clear input so same file can be selected again if needed
        fileInput.value = '';
    }
});
</script>

<?php 
include_footer();
exit();
?>

