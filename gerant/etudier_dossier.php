<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION['id'];
$id_candidature = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($id_candidature <= 0) {
    send_error("ID de candidature invalide.");
}

// Récupérer la candidature + vérifier appartenance au gérant
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.statut, c.date_candidature,
               u.nom AS candidat_nom, u.email AS candidat_email, u.telephone AS candidat_telephone,
               co.id AS id_offre, co.titre AS offre_titre, co.id_gerant
        FROM candidatures c
        JOIN utilisateurs u ON c.id_candidat = u.id
        JOIN offres co ON c.id_offre = co.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id_candidature]);
    $candidature = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidature || $candidature['id_gerant'] != $id_gerant) {
        send_error("Candidature non trouvée ou accès refusé.", 404);
    }
} catch (PDOException $e) {
    send_error("Erreur de base de données : " . $e->getMessage());
}

// --- ACTIONS (Modification pour support Redirect) ---

// Valider/Rejeter un document
if (isset($_GET['action_doc'], $_GET['id_doc'])) {
    $id_doc = (int)$_GET['id_doc'];
    $etat = ($_GET['action_doc'] === 'valider') ? 'valide' : 'rejete';
    
    $stmt_upd = $pdo->prepare("UPDATE dossiers SET etat = ? WHERE id = ? AND id_candidature = ?");
    $stmt_upd->execute([$etat, $id_doc, $id_candidature]);
    
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
        send_json(['success' => true, 'message' => "Document mis à jour."]);
    } else {
        $_SESSION['success_message'] = "Statut du document mis à jour.";
        header("Location: etudier_dossier.php?id=$id_candidature");
        exit();
    }
}

// Décision globale (POST)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    $input = json_decode(file_get_contents("php://input"), true);
    $statut_global = $input['statut_global'] ?? $_POST['statut_global'] ?? "";

    if (in_array($statut_global, ['en_attente', 'validee', 'rejetee'])) {
        $pdo->prepare("UPDATE candidatures SET statut = ? WHERE id = ?")->execute([$statut_global, $id_candidature]);
        
        // Envoi Email
        if ($statut_global !== 'en_attente' && $statut_global !== $candidature['statut']) {
            require_once "../includes/mailer.php";
            $offre_nom = htmlspecialchars($candidature['offre_titre']);
            $to = $candidature['candidat_email'];
            
            if ($statut_global === 'validee') {
                $subject = "Félicitations ! Votre candidature est présélectionnée";
                $body = "Bonjour,<br><br>Nous avons le plaisir de vous informer que votre candidature à l'offre <strong>$offre_nom</strong> a été étudiée et <strong>retenue pour l'étape suivante</strong>.<br><br>Connectez-vous sur votre espace candidat pour plus d'informations.<br><br>Cordialement,<br>L'équipe Admissio.";
            } else {
                $subject = "Information concernant votre candidature - Admissio";
                $body = "Bonjour,<br><br>Suite à l'étude attentive de votre dossier pour l'offre <strong>$offre_nom</strong>, nous avons le regret de vous informer que votre candidature n'a pas été retenue pour la phase suivante.<br><br>Nous vous remercions pour l'intérêt porté et vous souhaitons une très bonne continuation.<br><br>Cordialement,<br>L'équipe Admissio.";
            }
            exec_send_email($to, $subject, $body);
        }

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            send_json(['success' => true, 'message' => "Décision enregistrée."]);
        } else {
            $_SESSION['success_message'] = "Décision enregistrée avec succès.";
            header("Location: etudier_dossier.php?id=$id_candidature");
            exit();
        }
    } else {
        send_error("Statut invalide.");
    }
}

// Récupération finale pour affichage
$stmt_docs = $pdo->prepare("SELECT id, type_document, nom_fichier, etat, date_upload FROM dossiers WHERE id_candidature = ?");
$stmt_docs->execute([$id_candidature]);
$dossiers = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

$stmt_rep = $pdo->prepare("
    SELECT r.valeur, cf.libelle, cf.type_champ
    FROM reponses_candidature r
    JOIN champs_formulaire cf ON r.id_champ = cf.id
    WHERE r.id_candidature = ?
    ORDER BY cf.ordre ASC
");
$stmt_rep->execute([$id_candidature]);
$reponses = $stmt_rep->fetchAll(PDO::FETCH_ASSOC);

// Formater les réponses fichiers
// 1b. Récupérer le CV Numérique (Structuré)
$id_candidat = $candidature['id_candidat'] ?? 0;
if ($id_candidat <= 0) {
    // Si non trouvé dans le premier SELECT, on le récupère
    $stmt_c = $pdo->prepare("SELECT id_candidat FROM candidatures WHERE id = ?");
    $stmt_c->execute([$id_candidature]);
    $id_candidat = $stmt_c->fetchColumn();
}

try {
    $stmt = $pdo->prepare("SELECT bio FROM profils_candidats WHERE id_utilisateur = ?");
    $stmt->execute([$id_candidat]);
    $cv_bio = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM cv_formations WHERE id_utilisateur = ? ORDER BY date_debut DESC");
    $stmt->execute([$id_candidat]);
    $cv_formations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_experiences WHERE id_utilisateur = ? ORDER BY date_debut DESC");
    $stmt->execute([$id_candidat]);
    $cv_experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_competences WHERE id_utilisateur = ? ORDER BY type, nom");
    $stmt->execute([$id_candidat]);
    $cv_competences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_langues WHERE id_utilisateur = ? ORDER BY langue");
    $stmt->execute([$id_candidat]);
    $cv_langues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM cv_certifications WHERE id_utilisateur = ? ORDER BY date_obtention DESC");
    $stmt->execute([$id_candidat]);
    $cv_certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cv_error = $e->getMessage();
}

include_header("Étude de dossier");
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <header style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h1>Étude du dossier #<?php echo $id_candidature; ?></h1>
            <p style="color: var(--text-muted);">Candidat : <strong><?php echo htmlspecialchars($candidature['candidat_nom']); ?></strong> | Offre : <strong><?php echo htmlspecialchars($candidature['offre_titre']); ?></strong></p>
        </div>
        <div style="display: flex; gap: 1rem;">
            <?php if ($candidature['statut'] === 'validee'): ?>
                <a href="generer_contrat_pdf.php?id=<?php echo $id_candidature; ?>" target="_blank" class="btn" style="width: auto; background: var(--success); color: white;">📄 Générer Lettre (PDF)</a>
            <?php endif; ?>
            <button id="btnAnalyseIA" class="btn" style="width: auto; background: var(--primary-gradient); color: white; border: none;">
                <i class="bi bi-robot me-1"></i> Analyse IA Profonde
            </button>
            <a href="messagerie.php?id=<?php echo $id_candidature; ?>" class="btn" style="width: auto; background: var(--primary); color: white;">💬 Discuter</a>
            <a href="candidatures.php?id_offre=<?php echo $candidature['id_offre']; ?>" class="btn" style="width: auto; background: var(--border);">← Retour</a>
        </div>
    </header>

    <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr; align-items: start;">
        <!-- Infos Candidat & Questionnaire -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <div class="stat-card">
                <h3>👤 Coordonnées</h3>
                <div style="margin-top: 1rem; display: grid; gap: 0.5rem; font-size: 0.875rem;">
                    <div><strong>Email :</strong> <?php echo htmlspecialchars($candidature['candidat_email']); ?></div>
                    <div><strong>Téléphone :</strong> <?php echo htmlspecialchars($candidature['candidat_telephone'] ?? '–'); ?></div>
                    <div><strong>Postulé le :</strong> <?php echo format_date($candidature['date_candidature'], true); ?></div>
                </div>
            </div>

            <div class="stat-card">
                <h3>📝 Réponses au formulaire</h3>
                <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <?php if (empty($reponses)): ?>
                        <p style="color: var(--text-muted); font-style: italic;">Aucune réponse supplémentaire.</p>
                    <?php else: ?>
                        <?php foreach ($reponses as $rep): ?>
                            <div style="padding-bottom: 0.75rem; border-bottom: 1px solid var(--border);">
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;"><?php echo htmlspecialchars($rep['libelle']); ?></div>
                                <div style="margin-top: 0.25rem;">
                                    <?php if ($rep['type_champ'] === 'fichier'): ?>
                                        <a href="../uploads/<?php echo $rep['valeur']; ?>" target="_blank" class="link">📄 Ouvrir le fichier</a>
                                    <?php else: ?>
                                        <?php echo nl2br(htmlspecialchars($rep['valeur'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            </div>

            <!-- NOUVEAU : CV Numérique Structuré -->
            <div class="stat-card" style="border-left: 5px solid var(--success);">
                <h3 class="text-success"><i class="bi bi-file-earmark-person me-2"></i>CV Numérique Structuré</h3>
                <div style="margin-top: 1rem;">
                    <?php if ($cv_bio): ?>
                        <div class="mb-4">
                            <h6 class="small fw-bold text-muted text-uppercase mb-1">Bio / Présentation</h6>
                            <p class="small bg-light p-3 rounded-3"><?php echo nl2br(htmlspecialchars((string)$cv_bio)); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($cv_formations)): ?>
                        <div class="mb-4">
                            <h6 class="small fw-bold text-muted text-uppercase mb-2">Formations</h6>
                            <?php foreach ($cv_formations as $f): ?>
                                <div class="mb-2 p-2 border-start border-primary border-4 bg-light rounded-2">
                                    <div class="fw-bold small"><?php echo htmlspecialchars((string)$f['diplome']); ?></div>
                                    <div class="text-muted" style="font-size: 0.7rem;"><?php echo htmlspecialchars((string)$f['etablissement']); ?> (<?php echo format_date($f['date_debut']); ?> - <?php echo $f['date_fin'] ? format_date($f['date_fin']) : 'Présent'; ?>)</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($cv_experiences)): ?>
                        <div class="mb-4">
                            <h6 class="small fw-bold text-muted text-uppercase mb-2">Expériences</h6>
                            <?php foreach ($cv_experiences as $e): ?>
                                <div class="mb-2 p-2 border-start border-success border-4 bg-light rounded-2">
                                    <div class="fw-bold small"><?php echo htmlspecialchars((string)$e['poste']); ?></div>
                                    <div class="text-muted" style="font-size: 0.7rem;"><?php echo htmlspecialchars((string)$e['entreprise']); ?> (<?php echo format_date($e['date_debut']); ?> - <?php echo $e['en_poste'] ? 'Présent' : format_date($e['date_fin']); ?>)</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($cv_formations) && empty($cv_experiences) && empty($cv_bio)): ?>
                        <p class="text-muted small italic">Le candidat n'a pas encore renseigné son CV numérique complet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Documents & Décision -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <div class="stat-card">
                <h3>📄 Pièces justificatives</h3>
                <div style="margin-top: 1rem; display: flex; flex-direction: column; gap: 1rem;">
                    <?php if (empty($dossiers)): ?>
                        <p style="color: var(--danger);">Aucun document téléversé.</p>
                    <?php else: ?>
                        <?php foreach ($dossiers as $doc): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border: 1px solid var(--border); border-radius: 0.5rem; background: var(--background);">
                                <div>
                                    <div style="font-weight: 600; text-transform: capitalize;"><?php echo str_replace('_', ' ', $doc['type_document']); ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Reçu le <?php echo format_date($doc['date_upload']); ?></div>
                                </div>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <?php if ($doc['etat'] === 'valide'): ?>
                                        <span style="color: var(--success); font-weight: 700; font-size: 0.75rem; margin-right: 0.5rem;">✓ Validé</span>
                                    <?php elseif ($doc['etat'] === 'rejete'): ?>
                                        <span style="color: var(--danger); font-weight: 700; font-size: 0.75rem; margin-right: 0.5rem;">✘ Rejeté</span>
                                    <?php endif; ?>

                                    <a href="../uploads/<?php echo $doc['nom_fichier']; ?>" target="_blank" class="btn" style="width:auto; padding:0.4rem; background:white;">👁️</a>
                                    <a href="?id=<?php echo $id_candidature; ?>&action_doc=valider&id_doc=<?php echo $doc['id']; ?>" class="btn" style="width:auto; padding:0.4rem; background: var(--success); color:white;">✅</a>
                                    <a href="?id=<?php echo $id_candidature; ?>&action_doc=rejeter&id_doc=<?php echo $doc['id']; ?>" class="btn" style="width:auto; padding:0.4rem; background: var(--danger); color:white;">❌</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="stat-card" style="border-top: 5px solid var(--primary);">
                <h3>⚖️ Décision finale</h3>
                <form method="POST" style="margin-top: 1.5rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $id_candidature; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Changer le statut du dossier</label>
                        <select name="statut_global" class="form-control" style="font-weight: 600;">
                            <option value="en_attente" <?php echo $candidature['statut'] === 'en_attente' ? 'selected' : ''; ?>>🕒 En attente</option>
                            <option value="validee" <?php echo $candidature['statut'] === 'validee' ? 'selected' : ''; ?>>🎉 Valider (Présélection)</option>
                            <option value="rejetee" <?php echo $candidature['statut'] === 'rejetee' ? 'selected' : ''; ?>>🚫 Rejeter</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Enregistrer la décision</button>
                    <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem; text-align: center;">
                        <i class="bi bi-envelope me-1"></i> Le candidat sera automatiquement notifié par email de ce changement de statut.
                    </p>
                </form>
                
                <?php if ($candidature['statut'] === 'validee'): ?>
                <div class="mt-4 pt-4 border-top text-center">
                    <a href="planifier_entretien.php?id=<?php echo $id_candidature; ?>" class="btn btn-success fw-bold w-100 shadow-sm" style="border-radius: 1rem; padding: 0.8rem; background: #10b981; border: none;">
                        <i class="bi bi-calendar-check me-2"></i> Convoquer ce candidat
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<!-- Modal Analyse IA -->
<div class="modal fade" id="modalIA" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.5rem;">
            <div class="modal-header border-0 pb-0 ps-4 pt-4">
                <h5 class="modal-title fw-bold fs-4 d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle text-primary" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-robot fs-5"></i>
                    </div>
                    Analyse de l'Assistant IA Admissio
                </h5>
                <button type="button" class="btn-close me-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="contentIA">
                <!-- Chargement -->
                <div id="loaderIA" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;"></div>
                    <p class="mt-3 text-muted fw-semibold">Gemini analyse le profil du candidat par rapport à l'offre...</p>
                </div>
                <!-- Résultats -->
                <div id="resultIA" class="d-none">
                    <div class="row g-4">
                        <div class="col-md-4 text-center">
                            <div class="p-4 rounded-4 bg-light border d-flex flex-column align-items-center h-100">
                                <span class="small text-muted fw-bold text-uppercase mb-2">Score de Match</span>
                                <div id="scoreIA" class="display-4 fw-black text-primary">0%</div>
                                <div class="progress w-100 mt-2" style="height: 6px; border-radius: 10px;">
                                    <div id="scoreBarIA" class="progress-bar bg-primary" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="p-3 rounded-4 bg-primary bg-opacity-5 border border-primary border-opacity-10 h-100">
                                <span class="small text-primary fw-bold text-uppercase d-block mb-1">Résumé du profil</span>
                                <p id="resumeIA" class="mb-0 text-dark" style="font-size: 0.95rem; line-height: 1.6;"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card h-100 p-3 mb-0" style="background: #f0fdf4; border-color: #bbf7d0;">
                                <h6 class="fw-bold text-success mb-2"><i class="bi bi-check2-circle me-1"></i> Points Forts</h6>
                                <ul id="fortsIA" class="small mb-0 ps-3"></ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card h-100 p-3 mb-0" style="background: #fff1f2; border-color: #fecdd3;">
                                <h6 class="fw-bold text-danger mb-2"><i class="bi bi-exclamation-triangle me-1"></i> Points Faibles / Manques</h6>
                                <ul id="faiblesIA" class="small mb-0 ps-3"></ul>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 rounded-4 border-start border-5 border-info bg-light">
                                <h6 class="fw-bold mb-1">Recommandation du Recruteur Virtuel :</h6>
                                <div id="recIA" class="fw-bold text-dark fs-5"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('btnAnalyseIA').addEventListener('click', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalIA'));
    modal.show();
    
    const loader = document.getElementById('loaderIA');
    const result = document.getElementById('resultIA');
    loader.classList.remove('d-none');
    result.classList.add('d-none');

    const formData = new FormData();
    formData.append('id_candidature', <?php echo $id_candidature; ?>);

    fetch('analyse_ia_ajax.php', {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            loader.classList.add('d-none');
            alert("Erreur IA : " + data.error);
            modal.hide();
            return;
        }
        
        loader.classList.add('d-none');
        const ia = data.analysis;
        document.getElementById('scoreIA').textContent = ia.score + '%';
        document.getElementById('scoreBarIA').style.width = ia.score + '%';
        document.getElementById('resumeIA').textContent = ia.resume;
        document.getElementById('recIA').textContent = ia.recommandation;

        const listForts = document.getElementById('fortsIA');
        listForts.innerHTML = '';
        if(ia.points_forts) ia.points_forts.forEach(p => { const li = document.createElement('li'); li.textContent = p; listForts.appendChild(li); });

        const listFaibles = document.getElementById('faiblesIA');
        listFaibles.innerHTML = '';
        if(ia.points_faibles) ia.points_faibles.forEach(p => { const li = document.createElement('li'); li.textContent = p; listFaibles.appendChild(li); });

        result.classList.remove('d-none');
    })
    .catch(err => {
        loader.classList.add('d-none');
        alert("Erreur de connexion : " + err.message);
        modal.hide();
    });
});
</script>

<?php 
include_footer();
exit();
?>
