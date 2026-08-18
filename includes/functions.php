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
 * Construit l'URL pour le changement de langue en conservant les paramètres GET
 */
if (!function_exists('build_lang_url')) {
    function build_lang_url(string $lang): string {
        $params = $_GET;
        $params['lang'] = $lang;
        return '?' . http_build_query($params);
    }
}

/**
 * Envoie une réponse JSON
 */
if (!function_exists('send_json')) {
    function send_json(array $data, int $status = 200): void {
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/json');
        http_response_code($status);
        $json = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            echo json_encode(['error' => 'Erreur d\'encodage JSON : ' . json_last_error_msg()]);
        } else {
            echo $json;
        }
        exit();
    }
}

/**
 * Envoie une erreur (JSON ou Session)
 */
if (!function_exists('send_error')) {
    function send_error(string $message, int $status = 400): void {
        // Log de l'erreur interne si nécessaire (sans l'afficher au client)
        if ($status >= 500) {
            error_log("Erreur interne ($status) : " . $message);
            $message = "Une erreur technique est survenue. Veuillez réessayer plus tard.";
        }

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
            send_json(['error' => $message], $status);
        } else {
            $_SESSION['error_message'] = $message;
            redirect_back();
        }
    }
}

/**
 * Redirige vers la page précédente ou vers l'index par défaut
 */
if (!function_exists('redirect_back')) {
    function redirect_back(string $default = null): void {
        if ($default === null) $default = PROJECT_PATH . 'index.php';
        $referer = $_SERVER['HTTP_REFERER'] ?? $default;
        header("Location: $referer");
        exit();
    }
}

/**
 * Récupère un paramètre global depuis la base de données
 */
if (!function_exists('get_setting')) {
    function get_setting(string $key, $default = null) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT valeur FROM parametres_globaux WHERE cle = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? $val : $default;
        } catch (PDOException $e) {
            return $default;
        }
    }
}
