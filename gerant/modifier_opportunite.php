<?php
/**
 * modifier_opportunite.php – Formulaire de modification d'un concours pour le gérant.
 */
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];
$id = (int)($_GET["id"] ?? $_POST["id"] ?? 0);

if ($id <= 0) {
    redirect_back();
}

$errors = [];
$opportunite = null;

// Chargement sécurisé des données
try {
    $stmt = $pdo->prepare("SELECT * FROM concours WHERE id = ? AND id_gerant = ?");
    $stmt->execute([$id, $id_gerant]);
    $opportunite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$opportunite) {
        send_error("Cette opportunité n'existe pas ou vous n'avez pas les droits de modification.", 403);
    }
} catch (PDOException $e) {
    send_error("Erreur d'accès à la base de données.");
}

// Traitement de la mise à jour
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    $titre           = trim($_POST["titre"] ?? "");
    $description     = trim($_POST["description"] ?? "");
    $date_ouverture  = $_POST["date_ouverture"] ?? "";
    $heure_ouverture = $_POST["heure_ouverture"] ?? "";
    $date_cloture    = $_POST["date_cloture"] ?? "";
    $heure_cloture   = $_POST["heure_cloture"] ?? "";

    if (empty($titre) || empty($description) || empty($date_ouverture) || empty($date_cloture)) {
        $errors[] = "Tous les champs marqués d'une étoile sont obligatoires.";
    } elseif ($date_cloture < $date_ouverture) {
        $errors[] = "La date de clôture ne peut pas être antérieure à la date d'ouverture.";
    } else {
        try {
            $secteur = trim($_POST["secteur"] ?? "");
            $stmt = $pdo->prepare("
                UPDATE concours
                SET titre = ?, description = ?, secteur = ?, date_ouverture = ?, heure_ouverture = ?, date_cloture = ?, heure_cloture = ?
                WHERE id = ? AND id_gerant = ?
            ");
            $stmt->execute([$titre, $description, $secteur, $date_ouverture, $heure_ouverture, $date_cloture, $heure_cloture, $id, $id_gerant]);

            // Notifier les candidats ayant postulé à cette offre
            try {
                require_once "../includes/notification_helper.php";
                $stmt_cands = $pdo->prepare("
                    SELECT DISTINCT id_candidat FROM candidatures WHERE id_concours = ?
                ");
                $stmt_cands->execute([$id]);
                $candidats = $stmt_cands->fetchAll(PDO::FETCH_COLUMN);

                $content = "L'offre \"" . $titre . "\" a été mise à jour. Consultez les nouvelles informations avant la date de clôture.";
                foreach ($candidats as $user_id) {
                    create_notification((int)$user_id, 'offre_modifiee', $content, true);
                }
            } catch (Exception $e_notif) {
                error_log("[modifier_opportunite] Erreur notifications : " . $e_notif->getMessage());
            }

            $_SESSION['success_message'] = "L'opportunité a été mise à jour avec succès.";
            header("Location: liste_opportunites.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Une erreur est survenue lors de l'enregistrement.";
        }
    }
}

include_header("Modifier : " . htmlspecialchars($opportunite['titre']));
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header Banner -->
            <div class="glass-premium p-5 rounded-4 d-flex justify-content-between align-items-center mb-5 anim-up">
                <div>
                    <a href="liste_opportunites.php" class="btn btn-sm btn-white border rounded-pill px-3 mb-3 fw-bold"><i class="bi bi-arrow-left"></i> Retour</a>
                    <h1 class="display-6 fw-black text-gray-900 mb-2">Modifier l'Opportunité</h1>
                    <p class="text-muted mb-0">Révisez les détails et le calendrier de votre annonce.</p>
                </div>
                <div class="stat-icon-luminous">
                    <i class="bi bi-pencil-square"></i>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 animate__animated animate__shakeX">
                    <ul class="mb-0 fw-bold small">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo htmlspecialchars($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="bg-white shadow-premium rounded-4 overflow-hidden mb-5 anim-up anim-delay-1">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="p-5">
                    <div class="mb-5">
                        <h5 class="fw-black text-gray-900 mb-4 d-flex align-items-center gap-3">
                            <span class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:30px; height:30px; font-size:0.8rem;">1</span>
                            Détails de l'Opportunité
                        </h5>
                        <div class="row g-4">
                            <div class="col-md-8">
                                <label class="form-label-pro">Titre du poste <span class="text-danger">*</span></label>
                                <input type="text" name="titre" class="form-control-pro fw-bold" placeholder="Ex: Chef de Projet Digital" required value="<?php echo htmlspecialchars($_POST['titre'] ?? $opportunite['titre']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-pro">Secteur / Domaine</label>
                                <input type="text" name="secteur" class="form-control-pro" placeholder="Ex: Informatique, Finance..." value="<?php echo htmlspecialchars($_POST['secteur'] ?? $opportunite['secteur']); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label-pro">Description détaillée <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control-pro" rows="8" placeholder="Décrivez les missions et compétences attendues..." required><?php echo htmlspecialchars($_POST['description'] ?? $opportunite['description']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <hr class="opacity-5 mb-5">

                    <div class="mb-4">
                        <h5 class="fw-black text-gray-900 mb-4 d-flex align-items-center gap-3">
                            <span class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:30px; height:30px; font-size:0.8rem;">2</span>
                            Paramètres de Publication
                        </h5>
                        <div class="row g-4 p-4 bg-light rounded-4 border">
                            <div class="col-md-6">
                                <label class="form-label-pro">Ouverture (Date & Heure) <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <input type="date" name="date_ouverture" class="form-control-pro" required value="<?php echo htmlspecialchars($_POST['date_ouverture'] ?? $opportunite['date_ouverture']); ?>">
                                    <input type="time" name="heure_ouverture" class="form-control-pro" value="<?php echo htmlspecialchars($_POST['heure_ouverture'] ?? $opportunite['heure_ouverture']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-pro">Clôture (Date & Heure) <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <input type="date" name="date_cloture" class="form-control-pro" required value="<?php echo htmlspecialchars($_POST['date_cloture'] ?? $opportunite['date_cloture']); ?>">
                                    <input type="time" name="heure_cloture" class="form-control-pro" value="<?php echo htmlspecialchars($_POST['heure_cloture'] ?? $opportunite['heure_cloture']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <button type="submit" class="btn-pro btn-pro-primary px-5 py-3">
                            Sauvegarder les modifications <i class="bi bi-check-circle ms-2"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
include_footer();
exit();
?>

