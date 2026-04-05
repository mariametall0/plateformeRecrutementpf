<?php
require_once "../includes/layout.php";
$log_file = __DIR__ . "/../debug.log";
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ACCÈS SCRIPT SUPPRESSION\n", FILE_APPEND);

// Protection candidat
check_role('candidat');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Debug log
    $log_file = __DIR__ . "/../debug.log";
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Début traitement suppression ID: " . ($_POST['id_candidature'] ?? 'N/A') . "\n", FILE_APPEND);
    
    try {
        verify_csrf_token();
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ERREUR CSRF: " . $e->getMessage() . "\n", FILE_APPEND);
        $_SESSION['error_message'] = "Erreur de sécurité (CSRF). Veuillez rafraîchir la page.";
        header("Location: mes_candidatures.php");
        exit();
    }
    
    $id_candidature = (int)($_POST["id_candidature"] ?? 0);
    $id_candidat = $_SESSION["id"];
    
    if ($id_candidature <= 0) {
        $_SESSION['error_message'] = "ID de candidature invalide.";
        header("Location: mes_candidatures.php");
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // 1. Vérifier que la candidature appartient au candidat et est encore "en_attente"
        $stmt = $pdo->prepare("SELECT statut FROM candidatures WHERE id = ? AND id_candidat = ?");
        $stmt->execute([$id_candidature, $id_candidat]);
        $candidature = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$candidature) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Candidature non trouvée ou accès refusé.";
            header("Location: mes_candidatures.php");
            exit();
        }
        
        if ($candidature['statut'] !== 'en_attente') {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Cette candidature ne peut plus être supprimée car elle est déjà en cours de traitement.";
            header("Location: mes_candidatures.php");
            exit();
        }
        
        // 2. Supprimer les fichiers physiques
        $stmt_docs = $pdo->prepare("SELECT nom_fichier FROM dossiers WHERE id_candidature = ?");
        $stmt_docs->execute([$id_candidature]);
        while ($doc = $stmt_docs->fetch(PDO::FETCH_ASSOC)) {
            $path = realpath(__DIR__ . "/../uploads/" . $doc['nom_fichier']);
            if ($path && file_exists($path)) {
                @unlink($path);
            }
        }
        
        // 3. Suppression en cascade dans la DB
        $pdo->prepare("DELETE FROM reponses_candidature WHERE id_candidature = ?")->execute([$id_candidature]);
        $pdo->prepare("DELETE FROM dossiers WHERE id_candidature = ?")->execute([$id_candidature]);
        $pdo->prepare("DELETE FROM messages_internes WHERE candidature_id = ?")->execute([$id_candidature]);
        $pdo->prepare("DELETE FROM candidatures WHERE id = ?")->execute([$id_candidature]);
        
        $pdo->commit();
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Succès suppression ID: $id_candidature\n", FILE_APPEND);
        
        $_SESSION['success_message'] = "Votre candidature a été retirée avec succès.";
        header("Location: mes_candidatures.php");
        exit();
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ERREUR SQL/PHP: " . $e->getMessage() . "\n", FILE_APPEND);
        $_SESSION['error_message'] = "Désolé, une erreur technique est survenue lors de la suppression.";
        header("Location: mes_candidatures.php");
        exit();
    }
} else {
    header("Location: mes_candidatures.php");
    exit();
}
