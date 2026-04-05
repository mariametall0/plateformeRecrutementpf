<?php
/**
 * changer_mdp.php — Changement de mot de passe (commun à tous les rôles)
 * Inclure depuis le dossier du rôle concerné.
 * Usage: require_once "../auth/changer_mdp.php";
 * Variables attendues: $redirect_url (URL vers laquelle rediriger après succès)
 */

require_once "../includes/layout.php";

if (!isset($_SESSION["id"])) {
    send_error("Non autorisé.", 401);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();

    $input = json_decode(file_get_contents("php://input"), true);
    $actuel    = $input["mdp_actuel"] ?? $_POST["mdp_actuel"] ?? "";
    $nouveau   = $input["mdp_nouveau"] ?? $_POST["mdp_nouveau"] ?? "";
    $confirmer = $input["mdp_confirmer"] ?? $_POST["mdp_confirmer"] ?? "";

    if (empty($actuel) || empty($nouveau) || empty($confirmer)) {
        send_error("Tous les champs sont obligatoires.");
    } else {
        $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
        $stmt->execute([$_SESSION["id"]]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($actuel, $user["mdp_actuel"] ?? $user["mot_de_passe"])) {
            send_error("Le mot de passe actuel est incorrect.", 403);
        } elseif (strlen($nouveau) < 8) {
            send_error("Le nouveau mot de passe doit contenir au moins 8 caractères.");
        } elseif ($nouveau !== $confirmer) {
            send_error("La confirmation du mot de passe ne correspond pas.");
        } elseif ($nouveau === $actuel) {
            send_error("Le nouveau mot de passe doit être différent de l'actuel.");
        }

        if (!isset($_SESSION['error_message'])) {
            $hash = password_hash($nouveau, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?")
                ->execute([$hash, $_SESSION["id"]]);
            
            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
                send_json(['success' => true, 'message' => 'Mot de passe modifié avec succès !']);
            } else {
                $_SESSION['success_message'] = "Mot de passe modifié avec succès !";
                $role = $_SESSION['role'];
                header("Location: ../$role/dashboard.php");
                exit();
            }
        }
    }
}

// Affichage du formulaire
include_header("Changer le mot de passe");
?>

<div class="row justify-content-center animate__animated animate__fadeIn">
    <div class="col-lg-6">
        <div class="p-4 bg-white rounded-4 shadow-sm mb-4 border-start border-warning border-5 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Sécurité du compte 🔐</h1>
                <p class="text-muted mb-0">Modifier votre mot de passe pour protéger votre accès.</p>
            </div>
        </div>

        <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">Mot de passe actuel</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0">🔑</span>
                        <input type="password" name="mdp_actuel" id="mdp_actuel" class="form-control bg-light border-0 py-2" required placeholder="Votre mot de passe actuel">
                        <button class="btn btn-outline-secondary border-0 bg-light text-muted" type="button" onclick="togglePassword('mdp_actuel', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <hr class="my-4 opacity-50">

                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-muted">Nouveau mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0">🔒</span>
                        <input type="password" name="mdp_nouveau" id="mdp_nouveau" class="form-control bg-light border-0 py-2" minlength="8" required placeholder="Minimum 8 caractères">
                        <button class="btn btn-outline-secondary border-0 bg-light text-muted" type="button" onclick="togglePassword('mdp_nouveau', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label small fw-bold text-uppercase text-muted">Confirmer le nouveau mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0">🛡️</span>
                        <input type="password" name="mdp_confirmer" id="mdp_confirmer" class="form-control bg-light border-0 py-2" required placeholder="Répéter le mot de passe">
                        <button class="btn btn-outline-secondary border-0 bg-light text-muted" type="button" onclick="togglePassword('mdp_confirmer', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="d-grid gap-2 mt-5">
                    <button type="submit" class="btn btn-primary py-3 fw-bold rounded-pill shadow-sm border-0">
                        Mettre à jour le mot de passe
                    </button>
                    <a href="../<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-light py-2 fw-bold rounded-pill border">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
        
        <div class="mt-4 text-center">
            <p class="text-muted small">
                <i class="opacity-50 text-dark">Conseil :</i> Utilisez un mélange de lettres, chiffres et caractères spéciaux.
            </p>
        </div>
    </div>
</div>

<?php 
include_footer();
exit();
?>
