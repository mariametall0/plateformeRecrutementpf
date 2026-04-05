<?php
/**
 * ai_evaluator.php
 * Algorithme intelligent de matching (Local)
 * Compare les informations du candidat avec le offre ciblé.
 */

function calculer_score_matching($candidat_profil, $offre_data) {
    if (!$candidat_profil || !$offre_data) return 0;

    $score = 0;
    $max_score = 100;
    
    // 1. Préparation des textes (Normalisation)
    $texte_candidat = strtolower($candidat_profil['secteur_specialite'] . " " . $candidat_profil['niveau_etude'] . " " . ($candidat_profil['experiences'] ?? ''));
    $texte_offre = strtolower($offre_data['titre'] . " " . $offre_data['description']);

    // Suppression des accents basique
    $unwanted_array = ['é'=>'e', 'è'=>'e', 'ê'=>'e', 'à'=>'a', 'â'=>'a', 'ô'=>'o', 'î'=>'i', 'ï'=>'i', 'ç'=>'c'];
    $texte_candidat = strtr($texte_candidat, $unwanted_array);
    $texte_offre = strtr($texte_offre, $unwanted_array);

    // Mots exclus (Stop words)
    $stopwords = ['le', 'la', 'les', 'de', 'du', 'des', 'un', 'une', 'et', 'ou', 'dans', 'pour', 'avec', 'sans', 'en', 'sur', 'par', 'ce', 'cette', 'au', 'aux', 'qui', 'que', 'est', 'sont'];
    
    $mots_candidat = array_filter(str_word_count(preg_replace('/[^a-z0-9]+/i', ' ', $texte_candidat), 1), function($mot) use ($stopwords) {
        return strlen($mot) > 3 && !in_array($mot, $stopwords);
    });
    
    $mots_offre = array_filter(str_word_count(preg_replace('/[^a-z0-9]+/i', ' ', $texte_offre), 1), function($mot) use ($stopwords) {
        return strlen($mot) > 3 && !in_array($mot, $stopwords);
    });

    // 2. Calcul des intersections de mots (Matching direct)
    $mots_communs = array_intersect(array_unique($mots_candidat), array_unique($mots_offre));
    $nombre_communs = count($mots_communs);
    $nombre_offre = count(array_unique($mots_offre));

    if ($nombre_offre > 0) {
        // Base score = up to 70 points from keywords matching
        $ratio = $nombre_communs / min(10, $nombre_offre); // We expect around 10 strong key terms
        if ($ratio > 1) $ratio = 1;
        $score += $ratio * 70;
    }

    // 3. Bonus Niveau d'étude
    if (strpos($texte_offre, 'bac') !== false && strpos($texte_candidat, 'bac') !== false) {
        $score += 10;
    }
    if ((strpos($texte_offre, 'master') !== false || strpos($texte_offre, 'ingénieur') !== false) && 
        (strpos($texte_candidat, 'master') !== false || strpos($texte_candidat, 'ingénieur') !== false)) {
        $score += 20;
    }
    
    // 4. Bonus Technologique/Commercial métier
    $hard_skills = ['php', 'sql', 'marketing', 'vente', 'commerce', 'java', 'python', 'rh', 'recrutement', 'comptabilité', 'finance'];
    foreach ($hard_skills as $skill) {
        if (strpos($texte_offre, $skill) !== false && strpos($texte_candidat, $skill) !== false) {
            $score += 15;
            break; // Max 1 bonus of this type
        }
    }

    // Sécurisation
    $score = min(max(round($score), 10), 98); // Score entre 10 et 98 (jamais 100 ni 0 pour du réalisme)

    return $score;
}
?>
