<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Vérifier CSRF (standard ou JSON)
    if (isset($_POST['csrf_token'])) {
        if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die("Erreur CSRF");
        }
    } else {
        verify_csrf_token();
    }
    
    // Support JSON et Form standard
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    $titre       = trim($input["titre"] ?? $_POST["titre"] ?? "");
    $description = trim($input["description"] ?? $_POST["description"] ?? "");
    $date_ouverture = $input["date_ouverture"] ?? $_POST["date_ouverture"] ?? "";
    $heure_ouverture = $input["heure_ouverture"] ?? $_POST["heure_ouverture"] ?? "08:00";
    $date_cloture   = $input["date_cloture"] ?? $_POST["date_cloture"] ?? "";
    $heure_cloture  = $input["heure_cloture"] ?? $_POST["heure_cloture"] ?? "17:00";

    if (empty($titre) || empty($description) || empty($date_ouverture) || empty($date_cloture)) {
        $errors[] = "Tous les champs obligatoires (titre, description, dates) doivent être remplis.";
    } elseif ($date_cloture < $date_ouverture) {
        $errors[] = "La date de clôture ne peut pas être avant la date d'ouverture.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO offres (titre, description, date_ouverture, heure_ouverture, date_cloture, heure_cloture, id_gerant, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'inactif')
            ");
            $stmt->execute([$titre, $description, $date_ouverture, $heure_ouverture, $date_cloture, $heure_cloture, $_SESSION["id"]]);
            $new_id = $pdo->lastInsertId();
            
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                send_json(['success' => true, 'message' => "Offre créée avec succès.", 'id' => $new_id], 201);
            } else {
                $_SESSION['success_message'] = "L'offre a été créée avec succès en tant que brouillon.";
                header("Location: liste_offres.php");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur base de données : " . $e->getMessage();
        }
    }
}

include_header("Nouvelle Offre");
?>

<div class="row justify-content-center animate__animated animate__fadeIn">
    <div class="col-lg-10 col-xl-8">
        <!-- Header Banner -->
        <div class="px-5 py-5 rounded-5 shadow-premium border-0 d-flex justify-content-between align-items-center position-relative overflow-hidden mb-5" style="background: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);">
            <div class="position-absolute rounded-circle opacity-10 bg-white" style="width: 200px; height: 200px; bottom: -50px; right: -50px;"></div>
            <div class="position-relative z-1">
                <a href="liste_offres.php" class="btn btn-sm btn-white rounded-pill px-3 mb-3 fw-bold text-primary bg-white shadow-sm border-0"><i class="bi bi-arrow-left"></i> Retour</a>
                <h1 class="display-6 fw-extrabold text-white mb-2">Publier une Nouvelle Offre 📄</h1>
                <p class="text-white opacity-75 fs-5 mb-0">Définissez les critères et les dates pour votre prochaine session de recrutement.</p>
            </div>
            <div class="d-none d-md-flex align-items-center justify-content-center bg-white bg-opacity-10 text-white rounded-circle" style="width: 80px; height: 80px;">
                <i class="bi bi-plus-circle fs-1"></i>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 animate__animated animate__shakeX">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" class="card border-0 shadow-premium rounded-5 overflow-hidden mb-5">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="card-body p-4 p-md-5">
                <div class="mb-5">
                    <h5 class="fw-extrabold mb-4 text-dark d-flex align-items-center gap-2">
                        <span class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-size: 1rem;">1</span>
                        Informations Générales
                    </h5>
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small text-uppercase ls-1">Titre de l'Offre <span class="text-danger">*</span></label>
                            <input type="text" name="titre" class="form-control form-control-lg border-2 shadow-none fw-bold" placeholder="Ex: Développeur Fullstack Junior" required value="<?php echo htmlspecialchars($_POST['titre'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold text-muted small text-uppercase ls-1">Description Détaillée <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control border-2 shadow-none" rows="6" placeholder="Objectifs, prérequis, missions..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h5 class="fw-extrabold mb-4 text-dark d-flex align-items-center gap-2">
                        <span class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-size: 1rem;">2</span>
                        Calendrier des Candidatures
                    </h5>
                    <div class="row g-4 p-4 bg-light bg-opacity-50 rounded-4 border">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small text-uppercase ls-1">Date d'Ouverture <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-2 border-end-0"><i class="bi bi-calendar-event text-success"></i></span>
                                <input type="date" name="date_ouverture" class="form-control border-2 border-start-0 ps-0" required value="<?php echo htmlspecialchars($_POST['date_ouverture'] ?? date('Y-m-d')); ?>">
                                <input type="time" name="heure_ouverture" class="form-control border-2" value="<?php echo htmlspecialchars($_POST['heure_ouverture'] ?? '08:00'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-muted small text-uppercase ls-1">Date de Clôture <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-2 border-end-0"><i class="bi bi-calendar-x text-danger"></i></span>
                                <input type="date" name="date_cloture" class="form-control border-2 border-start-0 ps-0" required value="<?php echo htmlspecialchars($_POST['date_cloture'] ?? ''); ?>">
                                <input type="time" name="heure_cloture" class="form-control border-2" value="<?php echo htmlspecialchars($_POST['heure_cloture'] ?? '17:00'); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info border-0 rounded-4 d-flex align-items-center mt-5 p-4 glass">
                    <span class="fs-2 me-4">💡</span>
                    <div class="small fw-medium">Par défaut, l'offre sera créée en mode <strong>Brouillon (Inactif)</strong>. Vous devrez l'activer manuellement depuis votre tableau de bord après avoir configuré le formulaire.</div>
                </div>
            </div>

            <div class="card-footer bg-white p-5 text-center border-0">
                <button type="submit" class="btn btn-primary btn-lg px-5 py-3 fw-extrabold rounded-pill shadow-premium transition-hover w-100 w-md-auto">
                    Créer l'Offre <i class="bi bi-check2-circle ms-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
include_footer();
exit();
?>