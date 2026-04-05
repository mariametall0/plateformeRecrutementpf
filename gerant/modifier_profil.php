<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$user_id = $_SESSION["id"];

// POST : Mettre à jour le profil
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    $nom       = trim($_POST["nom"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $telephone = trim($_POST["telephone"] ?? "");

    if (empty($nom) || empty($email)) {
        send_error("Le nom et l'email sont obligatoires.");
    } else {
        try {
            // Vérifier si l'email est déjà utilisé par un autre
            $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $check->execute([$email, $user_id]);
            if ($check->rowCount() > 0) {
                send_error("Cet email est déjà utilisé par un autre compte.");
            } else {
                $secteur = trim($_POST["secteur"] ?? "");
                
                $pdo->beginTransaction();
                
                // 1. Utilisateurs
                $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, telephone = ? WHERE id = ?");
                $stmt->execute([$nom, $email, $telephone, $user_id]);
                
                // 2. Entreprise
                $stmt_ent = $pdo->prepare("UPDATE entreprises SET secteur_activite = ? WHERE id_utilisateur = ?");
                $stmt_ent->execute([$secteur, $user_id]);

                $pdo->commit();
                
                $_SESSION["nom"] = $nom;
                $_SESSION['success_message'] = "Profil mis à jour.";
                header("Location: modifier_profil.php");
                exit();
            }
        } catch (PDOException $e) {
            send_error("Erreur base de données", 500);
        }
    }
}

// Récupération initiale
try {
    $stmt = $pdo->prepare("
        SELECT u.*, e.secteur_activite AS secteur 
        FROM utilisateurs u
        LEFT JOIN entreprises e ON u.id = e.id_utilisateur
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $user = null; }

include_header("Modifier mon Profil Gérant");
?>

<div class="row justify-content-center animate__animated animate__fadeIn">
    <div class="col-lg-8">
        <div class="p-4 bg-white rounded-4 shadow-sm mb-4 border-start border-success border-5 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Mon Profil Gérant 🏢</h1>
                <p class="text-muted mb-0">Informations de contact et expertise de recrutement.</p>
            </div>
            <div class="d-none d-md-block text-success display-6">🏢</div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 py-4 px-4 px-md-5">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="row g-4 mb-5">
                    <div class="col-12">
                        <label class="form-label small fw-bold text-uppercase text-muted">Nom complet ou Structure</label>
                        <input type="text" name="nom" class="form-control form-control-lg bg-light border-0 px-3" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-uppercase text-muted">Email professionnel</label>
                        <input type="email" name="email" class="form-control bg-light border-0 px-3" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-muted">Téléphone</label>
                        <input type="tel" name="telephone" class="form-control bg-light border-0 px-3" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>" placeholder="+212 ...">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-uppercase text-muted">Secteur d'expertise / Activité</label>
                        <input type="text" name="secteur" class="form-control bg-light border-0 px-3" value="<?php echo htmlspecialchars($user['secteur'] ?? ''); ?>" placeholder="ex: Recrutement Tech, Finance...">
                    </div>
                </div>

                <div class="d-flex flex-column flex-md-row gap-2 border-top pt-4">
                    <button type="submit" class="btn btn-success px-5 py-2 fw-bold rounded-pill shadow-sm border-0">Enregistrer les changements</button>
                    <a href="dashboard.php" class="btn btn-light px-5 py-2 fw-bold rounded-pill border">Annuler</a>
                </div>
            </form>

            <div class="mt-5 p-4 bg-light rounded-4 text-center">
                <p class="small text-muted mb-3">Sécurité de votre compte</p>
                <a href="../auth/changer_mdp.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4 fw-bold">Modifier mon mot de passe →</a>
            </div>
        </div>
    </div>
</div>

<?php 
include_footer();
exit();
