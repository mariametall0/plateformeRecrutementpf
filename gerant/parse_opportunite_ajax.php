<?php
require_once "../includes/layout.php";
require_once "../includes/ai_helper.php";

header('Content-Type: application/json');

check_role('gerant');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit();
}

if (!isset($_FILES['document_opportunite']) || $_FILES['document_opportunite']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Veuillez télécharger un document valide.']);
    exit();
}

$file = $_FILES['document_opportunite'];
$tmp_path = $file['tmp_name'];
$mime_type = mime_content_type($tmp_path);

$allowed_mimes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain'
];

if (!in_array($mime_type, $allowed_mimes)) {
    echo json_encode(['success' => false, 'error' => 'Format de fichier non supporté. Veuillez utiliser PDF, DOC, DOCX ou TXT.']);
    exit();
}

try {
    $ai = new GeminiClient();
    $result = $ai->parse_opportunity_from_file($tmp_path, $mime_type);

    if (isset($result['error'])) {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'analyse : ' . $e->getMessage()]);
}
exit();
