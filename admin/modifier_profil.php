<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

$user_id = $_SESSION["id"];

// POST : Mettre à jour le profil
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    $nom   = trim($_POST["nom"] ?? "");
    $email = trim($_POST["email"] ?? "");

    if (empty($nom) || empty($email)) {
        send_error("Le nom et l'email sont obligatoires.");
    } else {
        try {
            // Vérifier si l'email est déjà utilisé par un autre compte
            $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $check->execute([$email, $user_id]);
            if ($check->rowCount() > 0) {
                send_error("Cet email est déjà utilisé par un autre compte.");
            } else {
                $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ? WHERE id = ?");
                $stmt->execute([$nom, $email, $user_id]);
                
                $_SESSION["nom"] = $nom;
                $_SESSION['success_message'] = "Votre profil a été mis à jour avec succès.";
                header("Location: modifier_profil.php");
                exit();
            }
        } catch (PDOException $e) {
            send_error("Erreur base de données : " . $e->getMessage(), 500);
        }
    }
}

// Récupération initiale
try {
    $stmt = $pdo->prepare("SELECT nom, email FROM utilisateurs WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $user = ['nom' => '', 'email' => ''];
}

include_header("Modifier mon Profil Administrateur");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="glass-premium p-5 rounded-4 d-flex justify-content-between align-items-center mb-5 anim-up">
                <div>
                    <h1 class="display-6 fw-black text-gray-900 mb-2">Profil Administrateur</h1>
                    <p class="text-muted mb-0">Gérez vos informations personnelles.</p>
                </div>
                <div class="stat-icon-luminous">
                    <i class="bi bi-person-circle"></i>
                </div>
            </div>

            <form method="POST" class="anim-up anim-delay-1">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="bg-white p-5 rounded-4 shadow-premium">
                    <h5 class="fw-black text-gray-900 mb-4 border-bottom pb-3 small text-uppercase ls-1">Informations Générales</h5>
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label-pro">Nom complet</label>
                            <input type="text" name="nom" class="form-control-pro" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label-pro">Email Professionnel</label>
                            <input type="email" name="email" class="form-control-pro" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-5 pt-3 border-top">
                        <button type="submit" class="btn-green-pill px-5 py-3 border-0 rounded-pill">
                            Enregistrer les modifications <i class="bi bi-save ms-2"></i>
                        </button>
                        <a href="dashboard.php" class="btn-pill-outline px-5 py-3">Annuler</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_footer(); ?>
