<?php
require_once "../includes/layout.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    $nom           = trim($input["nom"] ?? $_POST["nom"] ?? "");
    $email         = trim($input["email"] ?? $_POST["email"] ?? "");
    $pays          = trim($input["pays"] ?? $_POST["pays"] ?? "");
    $telephone     = trim($input["telephone"] ?? $_POST["telephone"] ?? "");
    $adresse       = trim($input["adresse"] ?? $_POST["adresse"] ?? "");
    $secteur       = trim($input["secteur"] ?? $_POST["secteur"] ?? "");
    $niveau_etude  = trim($input["niveau_etude"] ?? $_POST["niveau_etude"] ?? "");
    $date_naissance= trim($input["date_naissance"] ?? $_POST["date_naissance"] ?? "");
    $password      = $input["password"] ?? $_POST["password"] ?? "";
    $confirm       = $input["confirm_password"] ?? $_POST["confirm_password"] ?? "";

    if (empty($nom) || empty($email) || empty($password)) {
        send_error("Nom, email et mot de passe requis.");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_error("Format d'email invalide.");
    } elseif (strlen($password) < 8) {
        send_error("Le mot de passe doit contenir au moins 8 caractères.");
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        send_error("Le mot de passe doit contenir au moins une lettre et un chiffre.");
    } elseif ($password != $confirm) {
        send_error("Les mots de passe ne correspondent pas.");
    }

    $cv_path = null;
    if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
        $result = handle_file_upload($_FILES['cv'], 'cvs');
        if ($result) {
            $cv_path = $result;
        } else {
            // L'erreur est déjà dans $_SESSION['error_message'] par handle_file_upload
        }
    }

    if (!isset($_SESSION['error_message'])) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            send_error("Cet email existe déjà.", 409);
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO utilisateurs
                    (nom, email, mot_de_passe, telephone, adresse, pays, role, statut)
                    VALUES (?, ?, ?, ?, ?, ?, 'candidat', 'actif')
                ");
                $stmt->execute([htmlspecialchars($nom), $email, $password_hash, htmlspecialchars($telephone), htmlspecialchars($adresse), htmlspecialchars($pays)]);
                $user_id = $pdo->lastInsertId();

                $stmt_profil = $pdo->prepare("
                    INSERT INTO profils_candidats 
                    (id_utilisateur, secteur_specialite, niveau_etude, date_naissance, cv_path)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_profil->execute([
                    $user_id,
                    htmlspecialchars($secteur),
                    htmlspecialchars($niveau_etude),
                    $date_naissance ?: null,
                    $cv_path
                ]);

                $pdo->commit();

            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
                send_json(['success' => true, 'message' => 'Compte créé.'], 201);
            } else {
                $_SESSION["id"]   = $user_id;
                $_SESSION["nom"]  = htmlspecialchars($nom);
                $_SESSION["role"] = 'candidat';
                $_SESSION["email"] = $email;
                
                $redirect = $_SESSION['redirect_after_login'] ?? "../candidat/dashboard.php";
                unset($_SESSION['redirect_after_login']);
                
                $_SESSION['success_message'] = "Bienvenue ! Votre compte a été créé avec succès.";
                header("Location: $redirect");
                exit();
            }
            } catch (Exception $e) {
                $pdo->rollBack();
                send_error("Erreur lors de l'inscription : " . $e->getMessage(), 500);
            }
        }
    }
}

// Affichage du formulaire
include_header("Inscription Candidat");
?>

<div class="auth-wrapper position-relative overflow-hidden min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);">
    <!-- Formes d'arrière-plan décoratives -->
    <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-primary opacity-10 blur-custom" style="width: 600px; height: 600px; filter: blur(80px);"></div>
    <div class="position-absolute bottom-0 end-0 translate-middle-y rounded-circle bg-info opacity-10 blur-custom" style="width: 400px; height: 400px; filter: blur(60px);"></div>

    <div class="container py-5 position-relative z-1 d-flex justify-content-center">
        <div class="card border-0 shadow-lg rounded-5 p-4 p-md-5 animate__animated animate__fadeInUp bg-white bg-opacity-75 backdrop-blur border-top border-primary border-5" style="max-width: 700px; width: 100%; border-width: 5px 0 0 0 !important;">
            <div class="text-center mb-4">
                <div class="mx-auto bg-primary bg-opacity-10 text-primary p-3 rounded-circle mb-3 icon-hover-bounce" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-person-plus-fill fs-1"></i>
                </div>
                <h2 class="fw-black h3 mb-1" style="font-family: 'Outfit', sans-serif;">Inscription Candidat</h2>
                <p class="text-muted small">Créez votre compte pour postuler aux offre en quelques clics.</p>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Nom complet</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" name="nom" class="form-control bg-white border-start-0 ps-0 py-2" placeholder="Prénom et Nom" required>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Adresse Email</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control bg-white border-start-0 ps-0 py-2" placeholder="nom@exemple.ma" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Téléphone</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-telephone"></i></span>
                            <input type="tel" name="telephone" class="form-control bg-white border-start-0 ps-0 py-2" placeholder="+212 ...">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Pays</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-globe"></i></span>
                            <input type="text" name="pays" class="form-control bg-white border-start-0 ps-0 py-2" placeholder="Ex: Maroc">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Date de naissance</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-calendar"></i></span>
                            <input type="date" name="date_naissance" class="form-control bg-white border-start-0 ps-0 py-2">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Adresse Complète</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-geo-alt"></i></span>
                            <input type="text" name="adresse" class="form-control bg-white border-start-0 ps-0 py-2" placeholder="Quartier, Ville">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Secteur / Spécialité</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-briefcase"></i></span>
                            <input type="text" name="secteur" class="form-control bg-white border-start-0 ps-0 py-2" placeholder="Ex: Finance, Informatique...">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Niveau d'étude</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-mortarboard"></i></span>
                            <input type="text" name="niveau_etude" class="form-control bg-white border-start-0 ps-0 py-2" placeholder="Ex: Master, Ingénieur...">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Upload CV (Optionnel, format PDF/Docx)</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-file-earmark-pdf"></i></span>
                            <input type="file" name="cv" accept=".pdf,.doc,.docx" class="form-control bg-white border-start-0 ps-0 py-2">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Mot de passe</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-key"></i></span>
                            <input type="password" name="password" id="password" class="form-control bg-white border-start-0 border-end-0 ps-0 py-2" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary border-start-0 bg-white text-muted" type="button" onclick="togglePassword('password', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-uppercase text-muted mb-2" style="letter-spacing: 1px; font-size: 0.75rem;">Confirmer</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-check2-all"></i></span>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control bg-white border-start-0 border-end-0 ps-0 py-2" placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary border-start-0 bg-white text-muted" type="button" onclick="togglePassword('confirm_password', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold shadow-sm rounded-pill mt-3 hover-lift-lg btn-lg fs-6 custom-glow">
                    Créer mon compte <i class="bi bi-arrow-right ms-2"></i>
                </button>
            </form>

            <div class="mt-4 pt-4 border-top text-center small">
                <span class="text-muted">Déjà inscrit ?</span> 
                <a href="login.php?role=candidat" class="text-primary fw-bold text-decoration-none ms-1">Se connecter</a>
                
                <div class="mt-4">
                    <a href="../choix_connexion.php" class="btn btn-sm btn-light rounded-pill px-4 text-muted border fw-semibold hover-lift-lg">
                        <i class="bi bi-arrow-left me-1"></i> Retour aux rôles
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Utilities dynamiques */
.hover-lift-lg { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.hover-lift-lg:hover { transform: translateY(-3px); box-shadow: 0 1rem 3rem rgba(0,0,0,.15)!important; }
.backdrop-blur { backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); }
.custom-glow { box-shadow: 0 0 15px rgba(79, 70, 229, 0.4) !important; }
.icon-hover-bounce { transition: transform 0.3s ease; }
.icon-hover-bounce:hover { transform: translateY(-5px) scale(1.05); }
.fw-black { font-weight: 900; }
</style>

<?php 
include_footer();
exit();
?>