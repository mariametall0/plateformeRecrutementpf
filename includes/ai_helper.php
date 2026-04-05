<?php
/**
 * ai_helper.php – Gère les appels à l'API Gemini.
 */

require_once __DIR__ . "/../config/ai_config.php";

class GeminiClient {

    private $api_key;
    private $model;

    public function __construct() {
        $this->api_key = GEMINI_API_KEY;
        $this->model   = GEMINI_MODEL;
    }

    /**
     * Analyse un CV par rapport à un poste.
     */
    public function analyze_cv(string $cv_text, string $job_title, string $job_description = "") {
        // Passage en v1 pour plus de stabilité si v1beta échoue
        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->api_key}";

        $prompt = "Vous êtes un expert en recrutement. Analysez le CV suivant pour le poste de '{$job_title}'. 
        Description du poste (si disponible) : '{$job_description}'.
        Répondez UNIQUEMENT en français.

        Donnez votre réponse EXCLUSIVEMENT au format JSON avec les clés EXACTES suivantes :
        - 'score': (int) un score de compatibilité de 0 à 100.
        - 'points_forts': (array) liste des points forts du candidat.
        - 'points_faibles': (array) liste des points faibles, manques ou points à améliorer.
        - 'resume': (string) un court résumé professionnel (3 lignes max).
        - 'recommandation': (string) une recommandation finale (ex: 'À rencontrer absolument', 'À surveiller', 'Profil décalé').

        CV TEXTE :
        {$cv_text}";

        $data = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix SSL local WAMP
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            $err_msg = "ERREUR GEMINI (analyze_cv - $http_code) : " . $response;
            @file_put_contents(__DIR__ . "/../debug.log", "[" . date('Y-m-d H:i:s') . "] $err_msg\n", FILE_APPEND);
            return ["error" => "L'assistant IA est temporairement indisponible (Quota ou Maintenance)."];
        }

        $result = json_decode($response, true);
        $json_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? "{}";
        
        // Nettoyage des balises Markdown ```json ... ``` si le modèle les ajoute
        $json_text = preg_replace('/```json\s*/i', '', $json_text);
        $json_text = preg_replace('/```\s*/', '', $json_text);
        
        $data = json_decode(trim($json_text), true);
        
        // Robustesse sur les clés (Gemini peut parfois varier selon le modèle)
        if (isset($data['points_weak']) && !isset($data['points_faibles'])) $data['points_faibles'] = $data['points_weak'];
        
        return $data;
    }

    /**
     * Suggère des améliorations pour un CV de candidat.
     */
    public function suggest_cv_improvements(string $cv_text, string $job_title, string $job_description = "") {
        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->api_key}";

        $prompt = "Vous êtes un coach de carrière expert. Un candidat souhaite postuler pour le poste de '{$job_title}'. 
        Description du poste : '{$job_description}'.
        Répondez UNIQUEMENT en français.

        Analysez son CV et donnez des conseils BIENVEILLANTS mais PRÉCIS pour l'aider à augmenter ses chances de réussite.
        Donnez votre réponse EXCLUSIVEMENT au format JSON avec les clés EXACTES suivantes :
        - 'score_estime': (int) score de compatibilité estimé (0-100).
        - 'suggestions': (array) liste d'actions concrètes à faire (ex: 'Ajouter une certification X', 'Préciser votre niveau de Y').
        - 'mots_cles_manquants': (array) mots-clés importants de l'offre qui ne sont pas assez visibles dans le CV.
        - 'commentaire_motivation': (string) une suggestion pour sa lettre ou son message de motivation.
        - 'verdict_flash': (string) une conclusion rapide d'encouragement.

        CV TEXTE :
        {$cv_text}";

        $data = [
            "contents" => [["parts" => [["text" => $prompt]]]]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Fix SSL local WAMP
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            $err_msg = "ERREUR GEMINI (suggest_cv_improvements - $http_code) : " . $response;
            @file_put_contents(__DIR__ . "/../debug.log", "[" . date('Y-m-d H:i:s') . "] $err_msg\n", FILE_APPEND);
            return ["error" => "L'assistant IA est temporairement indisponible (Quota ou Maintenance)."];
        }

        $result = json_decode($response, true);
        $json_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? "{}";
        
        // Nettoyage Markdown
        $json_text = preg_replace('/```json\s*/i', '', $json_text);
        $json_text = preg_replace('/```\s*/', '', $json_text);
        
        $data = json_decode(trim($json_text), true);

        if (isset($data['missing_keywords']) && !isset($data['mots_cles_manquants'])) $data['mots_cles_manquants'] = $data['missing_keywords'];

        return $data;
    }

    /**
     * Analyse un texte de CV et le transforme en données structurées JSON.
     */
    public function parse_cv_to_structured_data(string $cv_text) {
        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->api_key}";

        $prompt = "Vous êtes une IA experte en traitement de documents RH. Analysez le texte brut suivant extrait d'un CV PDF et extrayez-en les informations structurées.
        IMPORTANT : Répondez UNIQUEMENT en français. Les dates doivent être au format YYYY-MM-DD (si possible, sinon laissez vide).

        Donnez votre réponse EXCLUSIVEMENT au format JSON avec cette structure :
        {
          \"bio\": \"Résumé professionnel court\",
          \"formations\": [
            {\"diplome\": \"Nom\", \"etablissement\": \"Nom\", \"ville\": \"Ville\", \"date_debut\": \"YYYY-MM-DD\", \"date_fin\": \"YYYY-MM-DD\", \"description\": \"Détails\"}
          ],
          \"experiences\": [
            {\"poste\": \"Intitulé\", \"entreprise\": \"Nom\", \"ville\": \"Ville\", \"date_debut\": \"YYYY-MM-DD\", \"date_fin\": \"YYYY-MM-DD\", \"en_poste\": boolean, \"description\": \"Missions\"}
          ],
          \"hard_skills\": [\"Saisie\", \"Java\", ...],
          \"soft_skills\": [\"Management\", \"Esprit d'équipe\", ...]
        }

        TEXTE DU CV :
        {$cv_text}";

        $data = [
            "contents" => [["parts" => [["text" => $prompt]]]]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) return ["error" => "Erreur extraction IA ($http_code)"];

        $result = json_decode($response, true);
        $json_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? "{}";
        
        // Nettoyage Markdown
        $json_text = preg_replace('/```json\s*/i', '', $json_text);
        $json_text = preg_replace('/```\s*/', '', $json_text);
        
        return json_decode(trim($json_text), true);
    }
}
