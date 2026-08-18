<?php
require_once "includes/layout.php";

$message_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sujet = trim($_POST['sujet'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (!empty($sujet) && !empty($email) && !empty($message)) {
        // En vrai: envoyer à l'administrateur
        require_once "includes/mailer.php";
        exec_send_email('admin@admissio.ma', "Nouveau Message Contact: $sujet", "De: $email<br><br>" . nl2br(htmlspecialchars($message)));
        $message_success = true;
    }
}

include_header("Contact & Assistance");
?>
<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Information de Contact -->
            <div class="p-5 bg-success text-white shadow-sm rounded-4 mb-4">
                <h2 class="fw-black mb-3"><i class="bi bi-headset me-2"></i>Nos Informations de Contact</h2>
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div style="font-size:1.5rem;"><i class="bi bi-telephone-fill"></i></div>
                            <div>
                                <h5 class="fw-bold mb-1">Téléphone Mauritel</h5>
                                <p class="mb-0" style="font-size:1.1rem;"><strong>42519122</strong></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div style="font-size:1.5rem;"><i class="bi bi-envelope-fill"></i></div>
                            <div>
                                <h5 class="fw-bold mb-1">Email Support</h5>
                                <p class="mb-0"><a href="mailto:admin@admissio.ma" style="color:#fff; text-decoration:underline;">admin@admissio.ma</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-5 bg-white shadow-sm rounded-4 border-top border-success border-5">
                <h1 class="fw-black mb-1"><i class="bi bi-headset text-success me-2"></i> Support & Contact</h1>
                <p class="text-muted mb-4">Un problème technique ? Une question sur votre dossier ? Nous sommes là pour vous aider.</p>

                <?php if ($message_success): ?>
                    <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                        <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                        <div>Votre message a bien été envoyé à notre équipe. Nous vous répondrons dans les plus brefs délais.</div>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Adresse Email</label>
                            <input type="email" name="email" class="form-control form-control-lg bg-light border-0" placeholder="vous@exemple.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Sujet / Problème</label>
                            <select name="sujet" class="form-select form-select-lg bg-light border-0" required>
                                <option value="">--- Sélectionnez ---</option>
                                <option value="Problème de compte">Problème de compte / mot de passe</option>
                                <option value="Impossible de postuler">Je n'arrive pas à postuler</option>
                                <option value="Question sur le offre">Question sur un offre précis</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-uppercase text-muted">Message Détaillé</label>
                            <textarea name="message" class="form-control form-control-lg bg-light border-0" rows="5" placeholder="Décrivez votre souci avec un maximum de détails..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg rounded-pill w-100 fw-bold shadow-sm">
                            <i class="bi bi-send-fill me-2"></i> Envoyer ma demande
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include_footer(); ?>

