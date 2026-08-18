<?php
declare(strict_types=1);

/**
 * analysis_helper.php – Gère l'analyse automatisée des dossiers via le moteur Gemini.
 * Utilise GeminiClient pour toutes les interactions API.
 */

require_once __DIR__ . "/ai_helper.php";

class AnalysisClient {

    private GeminiClient $client;

    public function __construct() {
        $this->client = new GeminiClient();
    }

    /**
     * Réécrit un texte de manière professionnelle et percutante.
     */
    public function rewrite_professionally(string $text): string {
        if (mb_strlen($text) < 10) {
            return $text;
        }

        $prompt = "En tant qu'expert en personal branding, réécrivez ce texte de CV pour le rendre plus professionnel et percutant.
        IMPORTANT : Ne changez pas les faits. Répondez UNIQUEMENT avec le nouveau texte en français.
        
        TEXTE : '{$text}'";

        return $this->client->call_api_text($prompt);
    }

    /**
     * Analyse un dossier de candidature par rapport à un poste.
     */
    public function analyze_candidature(string $cv_text, string $job_title, string $job_description = ""): array {
        return $this->client->analyze_cv($cv_text, $job_title, $job_description);
    }

    /**
     * Analyse un profil candidat pour suggérer des optimisations (ALIAS pour compatibilité).
     */
    public function suggest_profile_optimizations(string $cv_text, string $job_title = ""): array {
        return $this->client->suggest_cv_improvements($cv_text, $job_title);
    }

    /**
     * Extrait les données structurées depuis un texte brut (Analyse sémantique).
     */
    public function extract_structured_data(string $text): array {
        if (mb_strlen($text) < 50) {
            return ['error' => 'Le texte extrait est trop court pour être analysé.'];
        }
        return $this->client->parse_cv_to_structured_data($text);
    }

    /**
     * Extrait les données structurées depuis un fichier PDF (Scanné ou non).
     */
    public function extract_from_pdf_file(string $file_path): array {
        if (!file_exists($file_path)) {
            return ['error' => 'Fichier introuvable'];
        }

        $prompt = "Extrayez TOUTES les informations de ce CV PDF en JSON strict :
        {
          \"bio\": \"Résumé professionnel court\",
          \"secteur_specialite\": \"Titre du profil (ex: Développeur PHP)\",
          \"formations\": [{\"diplome\": \"\", \"etablissement\": \"\", \"ville\": \"\", \"date_debut\": \"YYYY-MM-DD\", \"date_fin\": \"\"}],
          \"experiences\": [{\"poste\": \"\", \"entreprise\": \"\", \"ville\": \"\", \"date_debut\": \"\", \"date_fin\": \"\", \"en_poste\": false, \"description\": \"\"}],
          \"hard_skills\": [\"compétence1\"],
          \"soft_skills\": [\"qualité1\"],
          \"langues\": [{\"langue\": \"Français\", \"niveau\": \"avance\"}],
          \"certifications\": [{\"nom\": \"\", \"organisme\": \"\", \"date_obtention\": \"YYYY-MM-DD\"}],
          \"interets\": [\"Loisir 1\"]
        }
        Pour le champ niveau des langues, utiliser UNIQUEMENT : notions, intermediaire, avance, bilingue, maternel.
        IMPORTANT : JSON pur uniquement, aucun commentaire.";

        return $this->client->call_api_with_file_json($file_path, 'application/pdf', $prompt);
    }
}
