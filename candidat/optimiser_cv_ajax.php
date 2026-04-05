<?php
/**
 * optimiser_cv_ajax.php – Point de terminaison AJAX pour les conseils candidats.
 */

require_once "../includes/layout.php";
require_once "../includes/pdf_helper.php";
require_once "../includes/ai_helper.php";

// Protection candidat
check_role('candidat');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json(['error' => "Méthode non autorisée."], 405);
}

$id_offre = (int)($_POST['id_offre'] ?? 0);

if ($id_offre <= 0) {
    send_json(['error' => "ID de offre invalide."], 400);
}

// Vérifier si un fichier est envoyé
if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
    send_json(['error' => "Veuillez sélectionner un fichier CV (PDF) valide."], 400);
}

$file_tmp = $_FILES['cv']['tmp_name'];
$file_type = $_FILES['cv']['type'];

if ($file_type !== 'application/pdf') {
    send_json(['error' => "Seuls les fichiers PDF sont acceptés pour l'analyse."], 400);
}

try {
    // 1. Récupérer les infos du offre
    $stmt = $pdo->prepare("SELECT titre, description FROM offres WHERE id = ?");
    $stmt->execute([$id_offre]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offre) {
        send_json(['error' => "Offre non trouvé."], 404);
    }

    // 2. Extraire le texte du PDF temporaire
    $cv_text = extract_text_from_pdf($file_tmp);
    
    // Fallback : si l'extraction échoue, utiliser un texte générique pour quand même aider
    if (empty($cv_text) || strlen($cv_text) < 50) {
        $cv_text = "[Contenu du CV non lisible - format non standard ou scanné] " . 
                   "Le candidat postule pour : " . $offre['titre'];
    }

    // 3. Appeler Gemini pour des conseils
    $gemini = new GeminiClient();
    $suggestions = $gemini->suggest_cv_improvements($cv_text, $offre['titre'], $offre['description']);

    if (isset($suggestions['error'])) {
        file_put_contents(__DIR__ . "/../debug.log", "[" . date('Y-m-d H:i:s') . "] ERREUR IA OPTIMISATION: " . $suggestions['error'] . "\n", FILE_APPEND);
        send_json(['error' => $suggestions['error']], 500);
    }

    send_json(['success' => true, 'suggestions' => $suggestions]);

} catch (Exception $e) {
    send_json(['error' => "Erreur technique : " . $e->getMessage()], 500);
}
