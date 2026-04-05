<?php
/**
 * functions.php – Fonctions utilitaires partagées sécurisées (sans sortie HTML).
 */

if (!function_exists('format_date')) {
    function format_date(?string $date, bool $with_time = false): ?string {
        if (!$date) return null;
        try {
            $d = new DateTime($date);
            return $d->format($with_time ? 'd/m/Y H:i' : 'd/m/Y');
        } catch (Exception $e) {
            return $date;
        }
    }
}

/**
 * Envoie une réponse JSON
 */
if (!function_exists('send_json')) {
    function send_json(array $data, int $status = 200): void {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit();
    }
}
