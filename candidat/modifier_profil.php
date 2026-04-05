<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$user_id = $_SESSION["id"];

// POST : Mettre à jour le profil
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    $nom       = trim($_POST["nom"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");
    $adresse   = trim($_POST["adresse"] ?? "");
    $pays      = trim($_POST["pays"] ?? "");
    $secteur   = trim($_POST["secteur"] ?? "");
    $bio       = trim($_POST["bio"] ?? "");

    if (empty($nom) || empty($email)) {
        send_error("Le nom et l'email sont obligatoires.");
    } else {
        try {
            // Mise à jour CV si présent
            $cv_sql = "";
            $params_profil = [];
            if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
                $new_cv = handle_file_upload($_FILES['cv'], 'cvs');
                if ($new_cv) {
                    $cv_sql = ", cv_path = ?";
                    $params_profil[] = $new_cv;
                }
            }

            // Vérifier si l'email est déjà utilisé
            $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $check->execute([$email, $user_id]);
            if ($check->rowCount() > 0) {
                send_error("Cet email est déjà utilisé par un autre compte.");
            } else {
                $pdo->beginTransaction();
                
                // 1. Mise à jour table utilisateurs (Base)
                $stmt = $pdo->prepare("
                    UPDATE utilisateurs 
                    SET nom = ?, email = ?, telephone = ?, adresse = ?, pays = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nom, $email, $telephone, $adresse, $pays, $user_id]);
                
                // 2. Mise à jour table profils_candidats (Secteur, CV & Bio)
                $sql_p = "UPDATE profils_candidats SET secteur_specialite = ?, bio = ?";
                $params_p = [$secteur, $bio];
                
                if (!empty($cv_sql)) {
                    $sql_p .= ", cv_path = ?";
                    $params_p[] = $params_profil[0];
                }
                
                $sql_p .= " WHERE id_utilisateur = ?";
                $params_p[] = $user_id;
                
                $pdo->prepare($sql_p)->execute($params_p);

                $pdo->commit();
                
                $_SESSION["nom"] = $nom;
                $_SESSION['success_message'] = "Votre profil a été mis à jour avec succès.";
                header("Location: modifier_profil.php");
                exit();
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            send_error("Erreur lors de la mise à jour : " . $e->getMessage(), 500);
        }
    }
}

// Récupération initiale pour GET
try {
    $stmt = $pdo->prepare("
        SELECT u.*, p.secteur_specialite AS secteur, p.bio 
        FROM utilisateurs u
        LEFT JOIN profils_candidats p ON u.id = p.id_utilisateur
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer statistiques du CV
    $cv_stats = [
        'formations' => $pdo->query("SELECT COUNT(*) FROM cv_formations WHERE id_utilisateur = $user_id")->fetchColumn(),
        'experiences' => $pdo->query("SELECT COUNT(*) FROM cv_experiences WHERE id_utilisateur = $user_id")->fetchColumn(),
        'competences' => $pdo->query("SELECT COUNT(*) FROM cv_competences WHERE id_utilisateur = $user_id")->fetchColumn(),
    ];
} catch (PDOException $e) { $user = null; $cv_stats=[]; }

include_header("Modifier mon Profil");
?>

<div class="row justify-content-center animate__animated animate__fadeIn">
    <div class="col-lg-10">
        <div class="p-4 bg-white rounded-4 shadow-sm mb-4 border-start border-primary border-5 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Mon Profil Candidat 👤</h1>
                <p class="text-muted mb-0">Gérez vos informations personnelles et professionnelles.</p>
            </div>
            <div class="d-none d-md-block text-primary display-6">👤</div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 py-4 px-4 px-md-5">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="row g-4 mb-5">
                    <div class="col-12">
                        <label class="form-label small fw-bold text-uppercase text-muted">Nom complet</label>
                        <input type="text" name="nom" class="form-control form-control-lg bg-light border-0 px-3" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-muted">Adresse Email</label>
                        <input type="email" name="email" class="form-control bg-light border-0 px-3" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-muted">Téléphone</label>
                        <input type="tel" name="telephone" class="form-control bg-light border-0 px-3" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>" placeholder="+212 ...">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-muted">Pays</label>
                        <input type="text" name="pays" class="form-control bg-light border-0 px-3" value="<?php echo htmlspecialchars($user['pays'] ?? ''); ?>" placeholder="Ex: Maroc">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-muted">Ville / Quartier</label>
                        <input type="text" name="adresse" class="form-control bg-light border-0 px-3" value="<?php echo htmlspecialchars($user['adresse'] ?? ''); ?>" placeholder="Casablanca, Agdal...">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-uppercase text-muted">Secteur d'activité / Spécialité</label>
                        <input type="text" name="secteur" class="form-control bg-light border-0 px-3" value="<?php echo htmlspecialchars($user['secteur'] ?? ''); ?>" placeholder="ex: Informatique, Finance, RH...">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-uppercase text-muted">Ma présentation (Bio)</label>
                        <textarea name="bio" class="form-control bg-light border-0 px-3" rows="4" placeholder="Votre accroche professionnelle..."><?php echo htmlspecialchars((string)($user['bio'] ?? '')); ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-uppercase text-muted">Mon CV Actuel (Fichier PDF)</label>
                        <div class="input-group">
                            <input type="file" name="cv" class="form-control bg-light border-0 px-3" accept=".pdf,.doc,.docx">
                        </div>
                        <?php 
                        $stmt_cv = $pdo->prepare("SELECT cv_path FROM profils_candidats WHERE id_utilisateur = ?");
                        $stmt_cv->execute([$user_id]);
                        $cv_path = $stmt_cv->fetchColumn();
                        if ($cv_path): ?>
                            <div class="mt-2 small text-muted">
                                <i class="bi bi-file-earmark-check text-success"></i> 
                                CV actuel : <a href="../uploads/<?php echo $cv_path; ?>" target="_blank" class="text-decoration-none">Voir le document</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row gap-2 border-top pt-4">
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold rounded-pill shadow-sm">Enregistrer les changements</button>
                    <a href="dashboard.php" class="btn btn-light px-5 py-2 fw-bold rounded-pill border">Annuler</a>
                </div>
            </form>

            <div class="mt-4 p-4 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-25">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-primary mb-0"><i class="bi bi-mortarboard-fill me-2"></i>État de mon CV numérique</h5>
                    <a href="mon_cv.php" class="btn btn-primary btn-sm rounded-pill fw-bold px-3">Gérer mon CV →</a>
                </div>
                <div class="row g-3">
                    <div class="col-4">
                        <div class="p-3 bg-white rounded-3 shadow-xs text-center border">
                            <div class="h4 fw-bold mb-0"><?php echo $cv_stats['formations']; ?></div>
                            <div class="small text-muted">Formations</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-white rounded-3 shadow-xs text-center border">
                            <div class="h4 fw-bold mb-0"><?php echo $cv_stats['experiences']; ?></div>
                            <div class="small text-muted">Expériences</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-white rounded-3 shadow-xs text-center border">
                            <div class="h4 fw-bold mb-0"><?php echo $cv_stats['competences']; ?></div>
                            <div class="small text-muted">Compétences</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-4 bg-light rounded-4 text-center">
                <p class="small text-muted mb-3">Sécurité de votre compte</p>
                <a href="../auth/changer_mdp.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 fw-bold">Modifier mon mot de passe →</a>
            </div>
        </div>
    </div>
</div>

<?php 
include_footer();
exit();
