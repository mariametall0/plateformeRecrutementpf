<?php
declare(strict_types=1);

/**
 * optimiser_cv_ajax.php – Point de terminaison AJAX pour l'aide au diagnostic.
 */

require_once "../includes/layout.php";
require_once "../includes/analysis_helper.php";

header('Content-Type: application/json');

// Protection candidat
check_role('candidat');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée.", 405);
    }

    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $max_size = ini_get('post_max_size');
        throw new Exception("La taille totale dépasse la limite autorisée par le serveur (max $max_size).", 413);
    }

    // Sécurité CSRF
    verify_csrf_token();

    $id_concours = (int)($_POST['id_concours'] ?? 0);

    $use_digital_profile = ($_POST['use_digital_profile'] ?? '') === '1';
    $cv_text = "";

    if ($use_digital_profile) {
        $user_id = (int)$_SESSION['id'];
        
        // 1. Bio
        $stmt = $pdo->prepare("SELECT bio FROM profils_candidats WHERE id_utilisateur = ?");
        $stmt->execute([$user_id]);
        $cv_text .= "BIO: " . ($stmt->fetchColumn() ?: "Non renseignée") . "\n";

        // 2. Expériences
        $st = $pdo->prepare("SELECT poste, entreprise, description FROM cv_experiences WHERE id_utilisateur = ?");
        $st->execute([$user_id]);
        foreach($st->fetchAll() as $e) {
            $cv_text .= "EXPÉRIENCE: {$e['poste']} chez {$e['entreprise']}: {$e['description']}\n";
        }

        // 3. Compétences
        $st = $pdo->prepare("SELECT nom FROM cv_competences WHERE id_utilisateur = ?");
        $st->execute([$user_id]);
        $skills = array_column($st->fetchAll(), 'nom');
        $cv_text .= "SKILLS: " . implode(', ', $skills) . "\n";
    } elseif (isset($_FILES['cv'])) {
        if ($_FILES['cv']['error'] === UPLOAD_ERR_OK) {
            // Mode ANALYSE PAR FICHIER
            require_once "../includes/pdf_helper.php";
            $tmp_path = $_FILES['cv']['tmp_name'];
            $cv_text = extract_text_from_pdf($tmp_path);
            
            if (mb_strlen($cv_text) < 50) {
                throw new Exception("Le PDF est vide ou protégé contre l'extraction de texte.");
            }
        } else {
            $error_code = $_FILES['cv']['error'];
            if ($error_code === UPLOAD_ERR_NO_FILE) {
                throw new Exception("Veuillez sélectionner un CV à analyser.");
            }
            $messages = [
                UPLOAD_ERR_INI_SIZE   => "Le fichier dépasse la taille autorisée par le serveur.",
                UPLOAD_ERR_FORM_SIZE  => "Le fichier dépasse la taille autorisée par le formulaire.",
                UPLOAD_ERR_PARTIAL    => "Le fichier n'a été que partiellement téléchargé.",
                UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant.",
                UPLOAD_ERR_CANT_WRITE => "Échec de l'écriture du fichier sur le disque.",
                UPLOAD_ERR_EXTENSION  => "Une extension PHP a arrêté le téléchargement."
            ];
            throw new Exception($messages[$error_code] ?? "Erreur lors du téléchargement du fichier.");
        }
    } else {
        throw new Exception("Veuillez sélectionner un CV à analyser.");
    }

    // 4. Récupérer les infos de l'opportunité
    $job_title = "Profil Général";
    if ($id_concours > 0) {
        $stmt = $pdo->prepare("SELECT titre FROM concours WHERE id = ?");
        $stmt->execute([$id_concours]);
        $job_title = (string)($stmt->fetchColumn() ?: "Poste sans titre");
    }

    // 5. Expertise IA
    $client = new AnalysisClient();
    $suggestions = $client->suggest_profile_optimizations($cv_text, $job_title);

    if (isset($suggestions['error'])) {
        throw new Exception($suggestions['error']);
    }

    send_json(['success' => true, 'suggestions' => $suggestions]);

} catch (Exception $e) {
    error_log("DIAGNOSTIC ERROR: " . $e->getMessage());
    send_json(['error' => $e->getMessage()], (int)($e->getCode() ?: 400));
}
