<?php
/**
 * pdf_helper.php – Extraction de texte PDF robuste (pure PHP).
 */

function extract_text_from_pdf($filename) {
    if (!file_exists($filename)) return "";

    $content = @file_get_contents($filename);
    if (!$content) return "";

    $text = "";

    // Méthode 1 : Décompresser les flux FlateDecode (PDFs modernes compressés)
    preg_match_all('/stream(.*?)endstream/s', $content, $streams);
    foreach ($streams[1] as $stream) {
        $stream = ltrim($stream, "\r\n");
        $decoded = @gzuncompress($stream);
        if ($decoded !== false) {
            // Extraire le texte des opérateurs PDF Tj et TJ
            preg_match_all('/\(((?:[^()\\\\]|\\\\.|(?:\((?:[^()\\\\]|\\\\.)*\)))*)\)\s*Tj/', $decoded, $tj);
            foreach ($tj[1] as $t) {
                $text .= $t . " ";
            }
            preg_match_all('/\[(.*?)\]\s*TJ/s', $decoded, $tjs);
            foreach ($tjs[1] as $t) {
                preg_match_all('/\(([^)]*)\)/', $t, $parts);
                foreach ($parts[1] as $part) {
                    $text .= $part . " ";
                }
            }
        }
    }

    // Méthode 2 : Fallback – extraction depuis le contenu brut non compressé
    if (strlen(trim($text)) < 30) {
        preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)\s*Tj/', $content, $tj2);
        foreach ($tj2[1] as $t) {
            $text .= $t . " ";
        }
        preg_match_all('/\[(.*?)\]\s*TJ/s', $content, $tjs2);
        foreach ($tjs2[1] as $t) {
            preg_match_all('/\(([^)]*)\)/', $t, $parts2);
            foreach ($parts2[1] as $part) {
                $text .= $part . " ";
            }
        }
    }

    // Nettoyage
    $text = preg_replace('/\\\\[0-9]{3}/', '', $text);
    $text = preg_replace('/\\\\(.)/', '$1', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = @mb_convert_encoding($text, 'UTF-8', 'auto');

    return trim($text);
}
