<?php
require_once "../includes/layout.php";
require_once "../includes/mailer.php";
require_once "../includes/notification_helper.php";

check_role('gerant');
$id_gerant = $_SESSION['id'];
$id_candidature = (int)($_GET['id'] ?? 0);

if ($id_candidature <= 0) send_error("ID de candidature invalide.");

// Query to check access and fetch applicant info
$stmt = $pdo->prepare("
    SELECT c.id, c.statut, c.id_candidat, u.nom, u.email, co.titre
    FROM candidatures c
    JOIN utilisateurs u ON c.id_candidat = u.id
    JOIN concours co ON c.id_concours = co.id
    WHERE c.id = ? AND co.id_gerant = ? AND c.statut = 'validee'
");
$stmt->execute([$id_candidature, $id_gerant]);
$cand = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cand) {
    $_SESSION['error_message'] = "Candidature invalide, non validée, ou accès refusé.";
    header("Location: candidatures.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = trim($_POST['date'] ?? '');
    $heure = trim($_POST['heure'] ?? '');
    $lieu = trim($_POST['lieu'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if ($date && $heure && $lieu) {
        $pdo->prepare("DELETE FROM entretiens WHERE id_candidature = ?")->execute([$id_candidature]);
        $pdo->prepare("INSERT INTO entretiens (id_candidature, date_entrevue, heure_entrevue, lieu_ou_lien, notes) VALUES (?, ?, ?, ?, ?)")->execute([$id_candidature, $date, $heure, $lieu, $notes]);
        
        // Notification sur le site ET par Email (unifié)
        $notif_msg = "Nouvelle convocation pour l'opportunité '" . $cand['titre'] . "' le " . format_date($date) . " à $heure. Lieu : $lieu. Notes : $notes";
        create_notification($cand['id_candidat'], 'entretien', $notif_msg, true);

        $_SESSION['success_message'] = "Rendez-vous planifié avec succès et convocation envoyée au candidat par email.";
        header("Location: etudier_dossier.php?id=" . $id_candidature);
        exit();
    } else {
        $error = "Veuillez remplir tous les champs obligatoires.";
    }
}

// Fetch existing interview if any
$stmt_ent = $pdo->prepare("SELECT * FROM entretiens WHERE id_candidature = ?");
$stmt_ent->execute([$id_candidature]);
$entretien = $stmt_ent->fetch(PDO::FETCH_ASSOC);

// Fetch company's exact location to default to if no interview is yet scheduled
$stmt_comp = $pdo->prepare("SELECT emplacement_exact FROM entreprises WHERE id_utilisateur = ?");
$stmt_comp->execute([$id_gerant]);
$comp = $stmt_comp->fetch(PDO::FETCH_ASSOC);
$default_lieu = $comp['emplacement_exact'] ?? '';

include_header("Planifier une Convocation");
?>
<div class="row justify-content-center animate__animated animate__fadeIn">
    <div class="col-lg-7">
        <div class="card p-4 border-0 shadow-sm rounded-4">
            <h3 class="fw-bold mb-4"><i class="bi bi-calendar-event text-success me-2"></i> Convoquer le candidat</h3>
            <div class="alert alert-primary bg-success bg-opacity-10 border-0 mb-4 rounded-3 d-flex gap-3 align-items-center">
                <i class="bi bi-info-circle-fill text-success fs-3"></i>
                <div class="small">
                    <div>Candidat : <strong><?php echo htmlspecialchars($cand['nom']); ?></strong></div>
                    <div>Opportunité ciblée : <strong><?php echo htmlspecialchars($cand['titre']); ?></strong></div>
                </div>
            </div>
            
            <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Date de convocation <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="<?php echo $entretien['date_entrevue'] ?? ''; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted small text-uppercase">Heure <span class="text-danger">*</span></label>
                        <input type="time" name="heure" class="form-control" value="<?php echo $entretien['heure_entrevue'] ?? ''; ?>" required>
                    </div>
                    <div class="col-12 mt-4">
                        <label class="form-label fw-bold text-muted small text-uppercase">Lieu ou Lien Visio <span class="text-danger">*</span></label>
                        <input type="text" name="lieu" class="form-control" placeholder="Ex: Bureau 12, Siège principal, ou lien Google Meet..." value="<?php echo htmlspecialchars($entretien['lieu_ou_lien'] ?? $default_lieu); ?>" required>
                    </div>
                    <div class="col-12 mt-4">
                        <label class="form-label fw-bold text-muted small text-uppercase">Consignes supplémentaires</label>
                        <textarea name="notes" class="form-control" rows="4" placeholder="Informations complémentaires, documents obligatoires à apporter le jour J..."><?php echo htmlspecialchars($entretien['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="mt-5 d-flex justify-content-end gap-2 border-top pt-4">
                    <a href="etudier_dossier.php?id=<?php echo $id_candidature; ?>" class="btn btn-light fw-bold px-4">Annuler</a>
                    <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm">
                        <i class="bi bi-send-fill me-2"></i> Envoyer Convocation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include_footer(); ?>

