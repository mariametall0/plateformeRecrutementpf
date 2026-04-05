<?php
require_once 'c:/wamp64/www/plateforme_recrutement/includes/ai_helper.php';

$client = new GeminiClient();
echo "Test de l'API Gemini...\n";
echo "Modèle : " . GEMINI_MODEL . "\n";

$result = $client->analyze_cv("Je suis un développeur PHP avec 5 ans d'expérience.", "Développeur PHP", "Poste de développeur senior.");

if (isset($result['error'])) {
    echo "ERREUR : " . $result['error'] . "\n";
} else {
    echo "SUCCÈS : Réponse reçue de Gemini.\n";
    print_r($result);
}
?>
