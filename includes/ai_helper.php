<?php
declare(strict_types=1);

/**
 * ai_helper.php – Gère les appels à l'API Gemini.
 */

require_once __DIR__ . "/../config/ai_config.php";

class GeminiClient {

    private string $api_key;
    private string $model;
    private array $fallback_models = [
        'gemini-2.5-flash',
        'gemini-2.5-flash-lite',
        'gemini-3-flash-preview',
        'gemini-2.0-flash',
        'gemini-3.1-pro-preview'
    ];

    public function __construct(string $model = '') {
        $this->api_key = GEMINI_API_KEY;
        $this->model   = $model ?: GEMINI_MODEL;
        // Ensure the preferred model is first in the rotation
        if ($this->model && !in_array($this->model, $this->fallback_models)) {
            array_unshift($this->fallback_models, $this->model);
        } elseif ($this->model && $this->fallback_models[0] !== $this->model) {
            $this->fallback_models = array_diff($this->fallback_models, [$this->model]);
            array_unshift($this->fallback_models, $this->model);
        }
    }

    public function setModel(string $model): void {
        $this->model = $model;
    }

    /**
     * Analyse un CV par rapport à un poste.
     */
    public function analyze_cv(string $cv_text, string $job_title, string $job_description = ""): array {
        $prompt = "Vous êtes un expert en recrutement de haut niveau. Analysez le CV suivant pour le poste de '{$job_title}'. 
        Description du poste : '{$job_description}'.
        Répondez UNIQUEMENT en français.

        Donnez votre réponse EXCLUSIVEMENT au format JSON avec les clés EXACTES suivantes :
        - 'score': (int) un score de compatibilité de 0 à 100.
        - 'points_forts': (array) liste des points de force clés.
        - 'points_faibles': (array) liste des points d'amélioration ou manques.
        - 'resume': (string) un court résumé professionnel (3 lignes max).
        - 'recommandation': (string) décision finale (ex: 'Top Profil', 'À surveiller', 'Non retenu').

        CV TEXTE :
        {$cv_text}";

        $data = [
            "contents" => [["parts" => [["text" => $prompt]]]],
            "generationConfig" => [
                "temperature" => 0.1,
                "topP" => 0.95,
                "topK" => 40,
                "maxOutputTokens" => 8192,
            ]
        ];

        return $this->call_api_with_fallback($data);
    }

    /**
     * Suggère des améliorations détaillées pour un profil de candidat.
     */
    public function suggest_cv_improvements(string $cv_text, string $job_title = "Profil Général"): array {
        $prompt = "Expert RH. Analysez ce profil.
        Répondez en français au format JSON :
        {
          \"score\": (0-100),
          \"diagnostic\": { \"experience\": \"...\", \"competences\": \"...\", \"presentation\": \"...\" },
          \"points_forts\": [],
          \"suggestions\": [],
          \"verdict_flash\": \"...\"
        }

        PROFIL :
        {$cv_text}";

        $data = [
            "contents" => [["parts" => [["text" => $prompt]]]]
        ];

        return $this->call_api_with_fallback($data);
    }

    /**
     * Parse un CV en données structurées.
     */
    public function parse_cv_to_structured_data(string $cv_text): array {
        $prompt = "Extraire les données structurées du CV en français au format JSON :
        {
          \"bio\": \"\",
          \"secteur_specialite\": \"\",
          \"formations\": [{\"diplome\": \"\", \"etablissement\": \"\", \"ville\": \"\", \"date_debut\": \"\", \"date_fin\": \"\", \"description\": \"\"}],
          \"experiences\": [{\"poste\": \"\", \"entreprise\": \"\", \"ville\": \"\", \"date_debut\": \"\", \"date_fin\": \"\", \"en_poste\": false, \"description\": \"\"}],
          \"hard_skills\": [],
          \"soft_skills\": [],
          \"langues\": [{\"langue\": \"\", \"niveau\": \"notions|intermediaire|avance|bilingue|maternel\"}],
          \"interets\": []
        }

        TEXTE :
        {$cv_text}";

        $data = [
            "contents" => [["parts" => [["text" => $prompt]]]]
        ];

        return $this->call_api_with_fallback($data);
    }

    /**
     * Parse un document de description de poste (offre d'emploi) pour créer une opportunité.
     */
    public function parse_opportunity_from_file(string $file_path, string $mime_type): array {
        $prompt = "Vous êtes un expert en recrutement.
        Extrayez les informations de cette offre d'emploi ou document de recrutement.
        Répondez EXCLUSIVEMENT en français au format JSON avec les clés exactes suivantes :
        {
          \"titre\": \"Le titre du poste ou de l'opportunité\",
          \"secteur\": \"Le domaine ou secteur d'activité principal (ex: Informatique, Finance)\",
          \"description\": \"La description complète du poste, les missions et le profil recherché (texte structuré)\"
        }";
        return $this->call_api_with_file_json($file_path, $mime_type, $prompt);
    }


    /**
     * Gère la rotation des modèles en cas d'erreur de quota.
     */
    private function call_api_with_fallback(array $payload): array {
        $last_error = "Aucun modèle disponible.";
        foreach ($this->fallback_models as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->api_key}";
            $result = $this->call_api($url, $payload);
            
            if (!isset($result['error'])) return $result;
            
            $last_error = $result['error'];
            // Si c'est une erreur de quota (429) ou modèle non trouvé (404), on continue la boucle
            if (strpos($last_error, '429') !== false || strpos($last_error, '404') !== false) {
                if (strpos($last_error, '429') !== false) {
                    sleep(1); // Petite pause pour laisser souffler le quota
                }
                continue; 
            }
            // Pour toute autre erreur (ex: 400), on arrête tout de suite
            break;
        }
        return ['error' => $last_error];
    }

    /**
     * Helper pour l'appel CURL et le parsing.
     */
    private function call_api(string $url, array $payload): array {
        $json_payload = json_encode($payload);
        if ($json_payload === false) {
            array_walk_recursive($payload, function(&$item) {
                if (is_string($item)) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
            });
            $json_payload = json_encode($payload);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            $this->log_error("API Error ($http_code) for URL: $url - Response: " . $response);
            return ["error" => "Erreur API $http_code"];
        }

        $result = json_decode($response, true);
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
        
        if ($text === null) {
            $this->log_error("API No Content. Response: " . $response);
            return ["error" => "L'IA n'a pas pu générer de contenu."];
        }

        // Nettoyage Markdown
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false) {
            $text = substr($text, $start, $end - $start + 1);
        }
        $data = json_decode(trim($text), true);
        
        if (!is_array($data)) {
            $this->log_error("Invalid JSON from IA: " . $text);
            return ["error" => "Format JSON invalide."];
        }

        return $data;
    }

    public function call_api_text(string $prompt): string {
        $data = ["contents" => [["parts" => [["text" => $prompt]]]]];
        $res = $this->call_api_with_fallback($data);
        return $res['error'] ?? "Erreur lors de la génération du texte.";
    }

    public function call_api_with_file_json(string $file_path, string $mime_type, string $prompt): array {
        $b64 = base64_encode(file_get_contents($file_path));
        $data = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt],
                        ["inlineData" => ["mimeType" => $mime_type, "data" => $b64]]
                    ]
                ]
            ]
        ];
        return $this->call_api_with_fallback($data);
    }

    private function log_error(string $msg): void {
        $log_file = __DIR__ . "/../logs/ai_error.log";
        if (!is_dir(dirname($log_file))) mkdir(dirname($log_file), 0777, true);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
    }
}
