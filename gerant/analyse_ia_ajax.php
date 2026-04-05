<?php
/**
 * analyse_ia_ajax.php – Point de terminaison AJAX pour l'analyse IA.
 */

require_once "../includes/layout.php";
require_once "../includes/pdf_helper.php";
require_once "../includes/ai_helper.php";

// Protection gérant
check_role('gerant');

$id_candidature = (int)($_POST['id_candidature'] ?? 0);

if ($id_candidature <= 0) {
    send_json(['error' => "ID de candidature invalide."], 400);
}

try {
    // 1. Récupérer les infos du offre et de la candidature
    $stmt = $pdo->prepare("
        SELECT co.titre AS job_title, co.description AS job_desc, u.nom AS candidat_nom, c.id_candidat
        FROM candidatures c
        JOIN offres co ON c.id_offre = co.id
        JOIN utilisateurs u ON c.id_candidat = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id_candidature]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$info) {
        send_json(['error' => "Candidature non trouvée."], 404);
    }

    // 2. Trouver le CV (format PDF attendu)
    $stmt_doc = $pdo->prepare("SELECT nom_fichier FROM dossiers WHERE id_candidature = ? AND (type_document = 'cv' OR type_document = 'curriculum_vitae') LIMIT 1");
    $stmt_doc->execute([$id_candidature]);
    $cv = $stmt_doc->fetchColumn();

    if (!$cv) {
        send_json(['error' => "Aucun CV trouvé pour cette candidature."], 404);
    }

    $cv_path = "../uploads/" . $cv;
    if (!file_exists($cv_path)) {
        send_json(['error' => "Fichier CV introuvable sur le serveur."], 404);
    }

    // 3. Extraire le texte du PDF
    $cv_text = extract_text_from_pdf($cv_path);
    // 4. Récupérer aussi le CV Numérique (Structuré) pour enrichir l'analyse
    $id_candidat = $info['id_candidat'];
    $structured_data = "";
    
    $stmt_bio = $pdo->prepare("SELECT bio FROM profils_candidats WHERE id_utilisateur = ?");
    $stmt_bio->execute([$id_candidat]);
    $bio = $stmt_bio->fetchColumn();
    if ($bio) $structured_data .= "\nBIO/PRÉSENTATION : $bio\n";

    $stmt_f = $pdo->prepare("SELECT diplome, etablissement, date_debut, date_fin FROM cv_formations WHERE id_utilisateur = ?");
    $stmt_f->execute([$id_candidat]);
    foreach ($stmt_f->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $structured_data .= "FORMATION : {$f['diplome']} à {$f['etablissement']} ({$f['date_debut']} - {$f['date_fin']})\n";
    }

    $stmt_e = $pdo->prepare("SELECT poste, entreprise, date_debut, date_fin, en_poste FROM cv_experiences WHERE id_utilisateur = ?");
    $stmt_e->execute([$id_candidat]);
    foreach ($stmt_e->fetchAll(PDO::FETCH_ASSOC) as $e) {
        $structured_data .= "EXPÉRIENCE : {$e['poste']} chez {$e['entreprise']} ({$e['date_debut']} - " . ($e['en_poste'] ? 'Présent' : $e['date_fin']) . ")\n";
    }

    // Fusionner les données
    $full_cv_text = "--- DONNÉES PDF EXTRAITES ---\n$cv_text\n\n--- DONNÉES STRUCTURÉES CANDIDAT ---\n$structured_data";

    // 5. Appeler Gemini
    $gemini = new GeminiClient();
    $analysis = $gemini->analyze_cv($full_cv_text, $info['job_title'], $info['job_desc']);

    if (isset($analysis['error'])) {
        send_json(['error' => $analysis['error']], 500);
    }

    // Optionnel : Sauvegarder l'analyse en cache
    // $pdo->prepare("UPDATE candidatures SET analyse_ia = ? WHERE id = ?")->execute([json_encode($analysis), $id_candidature]);

    send_json(['success' => true, 'analysis' => $analysis]);

} catch (Exception $e) {
    send_json(['error' => "Erreur serveur : " . $e->getMessage()], 500);
}
