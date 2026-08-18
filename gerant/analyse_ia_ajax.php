<?php
declare(strict_types=1);

/**
 * analyse_ia_ajax.php – Point de terminaison AJAX pour l'analyse IA (côté Gérant).
 */

require_once "../includes/layout.php";
require_once "../includes/analysis_helper.php";

header('Content-Type: application/json');

// Protection gérant
check_role('gerant');

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        verify_csrf_token();
    }

    $id_candidature = (int)($_POST['id_candidature'] ?? 0);
    if ($id_candidature <= 0) {
        throw new Exception("ID de candidature invalide.");
    }

    // 1. Récupérer les infos du concours et de la candidature
    $stmt = $pdo->prepare("
        SELECT co.titre AS job_title, co.description AS job_desc, u.nom AS candidat_nom, c.id_candidat
        FROM candidatures c
        JOIN concours co ON c.id_concours = co.id
        JOIN utilisateurs u ON c.id_candidat = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id_candidature]);
    $info = $stmt->fetch();

    if (!$info) {
        throw new Exception("Candidature non trouvée ou liée à une offre inexistante.");
    }

    // 2. Récupérer les données du CV Numérique (Structuré)
    $id_candidat = (int)$info['id_candidat'];
    $cv_parts = [];
    
    $bio = $pdo->prepare("SELECT bio FROM profils_candidats WHERE id_utilisateur = ?");
    $bio->execute([$id_candidat]);
    $cv_parts[] = "BIO: " . ($bio->fetchColumn() ?: "Non renseignée");

    $stmt_f = $pdo->prepare("SELECT diplome, etablissement, date_debut, date_fin FROM cv_formations WHERE id_utilisateur = ?");
    $stmt_f->execute([$id_candidat]);
    foreach ($stmt_f->fetchAll() as $f) {
        $cv_parts[] = "FORMATION: {$f['diplome']} ({$f['etablissement']}) [{$f['date_debut']} - {$f['date_fin']}]";
    }

    $stmt_e = $pdo->prepare("SELECT poste, entreprise, date_debut, date_fin, en_poste, description FROM cv_experiences WHERE id_utilisateur = ?");
    $stmt_e->execute([$id_candidat]);
    foreach ($stmt_e->fetchAll() as $e) {
        $status = $e['en_poste'] ? 'Présent' : ($e['date_fin'] ?: 'Non spécifié');
        $cv_parts[] = "EXPÉRIENCE: {$e['poste']} ({$e['entreprise']}) [{$e['date_debut']} - {$status}]: {$e['description']}";
    }

    $stmt_s = $pdo->prepare("SELECT nom, type FROM cv_competences WHERE id_utilisateur = ?");
    $stmt_s->execute([$id_candidat]);
    foreach ($stmt_s->fetchAll() as $s) {
        $cv_parts[] = "SKILL: {$s['nom']} ({$s['type']})";
    }

    $full_profile_text = implode("\n", $cv_parts);

    // 3. Appel à l'Expert IA
    $analysis_client = new AnalysisClient();
    $analysis = $analysis_client->analyze_candidature(
        $full_profile_text, 
        (string)$info['job_title'], 
        (string)$info['job_desc']
    );

    if (isset($analysis['error'])) {
        throw new Exception($analysis['error']);
    }

    send_json(['success' => true, 'analysis' => $analysis]);

} catch (Exception $e) {
    error_log("IA ANALYSIS ERROR: " . $e->getMessage());
    send_json(['success' => false, 'error' => $e->getMessage()], 400);
}
