<?php
/**
 * modifier_profil.php – Modification du profil du gérant / recruteur.
 */
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$user_id = $_SESSION["id"];

// Traitement POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    $nom       = trim($_POST["nom"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $pays      = trim($_POST["pays"] ?? "");
    $adresse   = trim($_POST["adresse"] ?? "");
    $secteur   = trim($_POST["secteur"] ?? "");
    $emplacement_exact = trim($_POST["emplacement_exact"] ?? "");

    if (empty($nom) || empty($email)) {
        send_error("Le nom et l'email professionnel sont obligatoires.");
    } else {
        try {
            // Vérifier email unique
            $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $check->execute([$email, $user_id]);
            
            if ($check->rowCount() > 0) {
                send_error("Cette adresse email est déjà utilisée.");
            } else {
                $photo_sql = "";
                $params_u = [$nom, $email, $telephone, $pays, $adresse];

                // Upload Photo
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $new_photo = handle_file_upload($_FILES['photo'], 'photos');
                    if ($new_photo) {
                        $photo_sql = ", photo_path = ?";
                        $params_u[] = $new_photo;
                        $_SESSION['photo_path'] = $new_photo;
                    }
                }
                
                $params_u[] = $user_id;
                
                $pdo->beginTransaction();
                
                $sql_u = "UPDATE utilisateurs SET nom = ?, email = ?, telephone = ?, pays = ?, adresse = ? $photo_sql WHERE id = ?";
                $stmt = $pdo->prepare($sql_u);
                $stmt->execute($params_u);
                
                // Mise à jour Entreprise (si existe)
                $stmt_ent = $pdo->prepare("UPDATE entreprises SET secteur_activite = ?, emplacement_exact = ? WHERE id_utilisateur = ?");
                $stmt_ent->execute([$secteur, $emplacement_exact, $user_id]);

                $pdo->commit();
                
                $_SESSION["nom"] = $nom;
                $_SESSION['success_message'] = "Vos informations de profil ont été mises à jour.";
                header("Location: modifier_profil.php");
                exit();
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            send_error("Erreur technique lors de la mise à jour.");
        }
    }
}

// Récupération initiale
try {
    $stmt = $pdo->prepare("
        SELECT u.*, e.secteur_activite AS secteur, e.emplacement_exact 
        FROM utilisateurs u
        LEFT JOIN entreprises e ON u.id = e.id_utilisateur
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $user = null; }

include_header("Modifier mon Profil Recruteur");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header Banner -->
            <div class="glass-premium p-5 rounded-4 d-flex justify-content-between align-items-center mb-5 anim-up">
                <div>
                    <h1 class="display-6 fw-black text-gray-900 mb-2">Profil Recruteur</h1>
                    <p class="text-muted mb-0">Gérez vos informations professionnelles et votre identité visuelle.</p>
                </div>
                <div class="stat-icon-luminous">
                    <i class="bi bi-building"></i>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="anim-up anim-delay-1">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="row g-4">
                    <!-- Photo & Statut -->
                    <div class="col-lg-4">
                        <div class="bg-white p-4 rounded-4 shadow-premium text-center">
                            <div class="position-relative d-inline-block mb-4">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center shadow-inner overflow-hidden" style="width: 160px; height: 160px; border: 6px solid #f8fafc;">
                                    <?php if (!empty($user['photo_path'])): ?>
                                        <img src="../uploads/<?php echo $user['photo_path']; ?>" class="w-100 h-100 object-fit-cover" id="img-preview">
                                    <?php else: ?>
                                        <i class="bi bi-building-fill text-gray-300 display-4" id="placeholder-icon"></i>
                                        <img src="" class="w-100 h-100 object-fit-cover d-none" id="img-preview">
                                    <?php endif; ?>
                                </div>
                                <label for="photo-upload" class="btn btn-success btn-sm rounded-circle position-absolute bottom-0 end-0 p-2 shadow-sm">
                                    <i class="bi bi-camera-fill"></i>
                                </label>
                                <input type="file" id="photo-upload" name="photo" class="d-none" accept="image/*">
                            <p class="mt-3 small text-muted">Format JPG/PNG • Max 5Mo</p>
                        </div>
                            <h5 class="fw-black text-gray-900 mb-1"><?php echo htmlspecialchars($user['nom']); ?></h5>
                            <span class="badge bg-emerald-50 text-emerald-600 rounded-pill px-3 py-2 fw-bold small mb-4">Recruteur Certifié</span>
                        </div>
                    </div>

                    <!-- Formulaire -->
                    <div class="col-lg-8">
                        <div class="bg-white p-5 rounded-4 shadow-premium">
                            <h5 class="fw-black text-gray-900 mb-4 border-bottom pb-3 small text-uppercase ls-1">Informations Générales</h5>
                            <div class="row g-4">
                                <div class="col-12">
                                    <label class="form-label-pro">Nom de la structure ou Nom complet</label>
                                    <input type="text" name="nom" class="form-control-pro" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-pro">Email Professionnel</label>
                                    <input type="email" name="email" class="form-control-pro" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-pro">Téléphone</label>
                                    <input type="tel" name="telephone" class="form-control-pro" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-pro">Secteur d'Activité</label>
                                    <input type="text" name="secteur" class="form-control-pro" value="<?php echo htmlspecialchars($user['secteur'] ?? ''); ?>" placeholder="RH, IT, Santé...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-pro">Pays</label>
                                    <input type="text" name="pays" class="form-control-pro" value="<?php echo htmlspecialchars($user['pays'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-pro">Ville</label>
                                    <input type="text" name="adresse" class="form-control-pro" value="<?php echo htmlspecialchars($user['adresse'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-pro">Emplacement exact de l'entreprise</label>
                                    <input type="text" name="emplacement_exact" class="form-control-pro" value="<?php echo htmlspecialchars($user['emplacement_exact'] ?? ''); ?>" placeholder="Ex: Bureau 12, Immeuble A, Rue 45...">
                                </div>
                            </div>

                            <div class="d-flex gap-3 mt-5 pt-3 border-top">
                                <button type="submit" class="btn-pro btn-pro-primary px-5 py-3">
                                    Enregistrer les modifications <i class="bi bi-save ms-2"></i>
                                </button>
                                <a href="dashboard.php" class="btn-pro btn-pro-secondary px-5 py-3">Annuler</a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('photo-upload').addEventListener('change', function(e) {
    const reader = new FileReader();
    reader.onload = (e) => {
        const preview = document.getElementById('img-preview');
        preview.src = e.target.result;
        preview.classList.remove('d-none');
        if (document.getElementById('placeholder-icon')) document.getElementById('placeholder-icon').classList.add('d-none');
    };
    if (this.files[0]) reader.readAsDataURL(this.files[0]);
});
</script>

<?php include_footer(); ?>

