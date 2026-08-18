<?php
/**
 * analyse_profil_ajax.php – Point de terminaison AJAX pour l'analyse de pertinence des dossiers.
 */

require_once "../includes/layout.php";
require_once "../includes/pdf_helper.php";
require_once "../includes/analysis_helper.php";

// Protection gérant
check_role('gerant');

$id_candidature = (int)($_POST['id_candidature'] ?? 0);

if ($id_candidature <= 0) {
    send_json(['error' => "ID de candidature invalide."], 400);
}

try {
    // 1. Récupérer les infos du concours et de la candidature
    // Note : On utilise 'concours' car 'offres' est obsolète/remplacé.
    $stmt = $pdo->prepare("
        SELECT co.titre AS job_title, co.description AS job_desc, u.nom AS candidat_nom, c.id_candidat
        FROM candidatures c
        JOIN concours co ON c.id_concours = co.id
        JOIN utilisateurs u ON c.id_candidat = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id_candidature]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        send_json(['error' => "Candidature non trouvée ou lien concours rompu."], 404);
    }

    // 2. Trouver le CV (PDF)
    // On cherche dans les dossiers liés à la candidature
    $stmt_doc = $pdo->prepare("SELECT nom_fichier FROM dossiers WHERE id_candidature = ? AND (type_document = 'cv' OR type_document = 'curriculum_vitae') LIMIT 1");
    $stmt_doc->execute([$id_candidature]);
    $cv = $stmt_doc->fetchColumn();

    // Fallback : Si non trouvé dans 'dossiers'
    if (!$cv) {
        $cv = null;
    }

    $cv_text = "";
    if ($cv) {
        $cv_path = "../uploads/" . $cv;
        if (file_exists($cv_path)) {
            $cv_text = extract_text_from_pdf($cv_path);
        }
    }

    // 3. Récupérer aussi le CV Numérique (Structuré)
    $id_candidat = $info['id_candidat'];
    $structured_data = "";
    
    $stmt_bio = $pdo->prepare("SELECT bio FROM profils_candidats WHERE id_utilisateur = ?");
    $stmt_bio->execute([$id_candidat]);
    $bio = $stmt_bio->fetchColumn();
    if ($bio) $structured_data .= "\nBIO/PRÉSENTATION : $bio\n";

    $stmt_f = $pdo->prepare("SELECT diplome, etablissement FROM cv_formations WHERE id_utilisateur = ?");
    $stmt_f->execute([$id_candidat]);
    foreach ($stmt_f->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $structured_data .= "FORMATION : {$f['diplome']} à {$f['etablissement']}\n";
    }

    $stmt_e = $pdo->prepare("SELECT poste, entreprise, description FROM cv_experiences WHERE id_utilisateur = ?");
    $stmt_e->execute([$id_candidat]);
    foreach ($stmt_e->fetchAll(PDO::FETCH_ASSOC) as $e) {
        $structured_data .= "EXPÉRIENCE : {$e['poste']} chez {$e['entreprise']} - {$e['description']}\n";
    }

    // 4. Fusionner les données pour l'analyse (Tronquer TRÈS court pour forcer le passage du quota)
    $full_profile_text = "--- TEXTE CV ---\n$cv_text\n\n--- DONNÉES ---\n$structured_data";
    $full_profile_text = mb_substr($full_profile_text, 0, 2000);

    // 5. Appeler le moteur d'analyse
    $client = new AnalysisClient();
    $analysis = $client->analyze_candidature($full_profile_text, $info['job_title'], $info['job_desc']);

    if (isset($analysis['error'])) {
        send_json(['error' => $analysis['error']], 500);
    }

    send_json(['success' => true, 'analysis' => $analysis]);

} catch (Exception $e) {
    error_log("ERREUR ANALYSE PROFIL : " . $e->getMessage());
    send_json(['error' => "Une erreur est survenue lors de l'analyse du dossier."], 500);
}
