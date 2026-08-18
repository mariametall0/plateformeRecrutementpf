<?php
/**
 * pdf_helper.php – Extraction de texte PDF robuste (pure PHP).
 * Supporte les flux FlateDecode (compressés) et les PDFs en texte brut.
 */

function extract_text_from_pdf($filename) {
    if (!file_exists($filename)) return "";
    $content = @file_get_contents($filename);
    if (!$content) return "";

    $text = "";
    $streams = [];

    // 1. Extraction de tous les flux possibles
    preg_match_all('/stream(.*?)endstream/s', $content, $m);
    foreach ($m[1] as $s) {
        $data = trim($s);
        // Tentative de décompression multiple
        $decoded = @gzuncompress($data);
        if ($decoded === false) $decoded = @gzinflate(substr($data, 2));
        if ($decoded === false) $decoded = @gzinflate($data);
        if ($decoded !== false) $streams[] = $decoded;
    }
    
    // Ajout du contenu brut pour les PDF non compressés
    $streams[] = $content;

    foreach ($streams as $data) {
        // Recherche agressive de texte entre parenthèses (...) ou chevrons <...>
        // On cible les opérateurs de texte PDF standards
        preg_match_all('/(?:\((.*?)\)|<([0-9a-fA-F]+)>)\s*(?:Tj|TJ|\'|")/s', $data, $matches);
        
        foreach ($matches[1] as $key => $val) {
            if (!empty($val)) {
                // Texte entre parenthèses
                $text .= $val . " ";
            } elseif (!empty($matches[2][$key])) {
                // Texte en hexadécimal
                $hex = $matches[2][$key];
                if (strlen($hex) % 2 === 0) {
                    $text .= pack("H*", $hex) . " ";
                }
            }
        }
    }

    // 2. Nettoyage profond
    $text = preg_replace('/\\\\[0-7]{3}/', '', $text); // Octal
    $text = str_replace(['\\(', '\\)', '\\\\', '\\n', '\\r', '\\t'], ['(', ')', '\\', "\n", "\r", "\t"], $text);
    
    // Normalisation
    $text = preg_replace('/[ \t]+/', ' ', $text);
    $text = preg_replace('/\n\s*/', "\n", $text);
    $text = preg_replace('/\s{4,}/', "\n\n", $text);

    // Si le texte est toujours vide, on tente un dernier recours : extraction brute de toutes les parenthèses de plus de 3 caractères
    if (strlen(trim($text)) < 10) {
        preg_match_all('/\(([^)]{4,})\)/', $content, $fallback);
        $text = implode(" ", $fallback[1]);
    }

    // Final safety: remove non-printable characters and ensure UTF-8
    $text = preg_replace('/[[:cntrl:]]/', ' ', $text);
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

    return trim($text);
}
