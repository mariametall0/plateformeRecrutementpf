<?php
declare(strict_types=1);

require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        verify_csrf_token();
    } catch (Exception $e) {
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
        
        // 1. Vérifier la candidature et son statut
        $stmt = $pdo->prepare("SELECT statut FROM candidatures WHERE id = ? AND id_candidat = ?");
        $stmt->execute([$id_candidature, $id_candidat]);
        $candidature = $stmt->fetch();
        
        if (!$candidature) {
            throw new Exception("Candidature non trouvée ou accès refusé.");
        }
        
        if ($candidature['statut'] !== 'en_attente') {
            throw new Exception("Cette candidature est déjà en cours de traitement et ne peut plus être retirée.");
        }
        
        // 2. Nettoyage des fichiers physiques (Dossiers)
        $stmt_docs = $pdo->prepare("SELECT nom_fichier FROM dossiers WHERE id_candidature = ?");
        $stmt_docs->execute([$id_candidature]);
        while ($doc = $stmt_docs->fetch()) {
            $path = realpath(__DIR__ . "/../uploads/" . $doc['nom_fichier']);
            if ($path && file_exists($path) && is_file($path)) {
                @unlink($path);
            }
        }
        
        // 3. Suppression en cascade (La DB a des contraintes ON DELETE CASCADE, mais on assure le coup pour les messages)
        // La table 'messages_internes' utilise 'candidature_id'
        $pdo->prepare("DELETE FROM messages_internes WHERE candidature_id = ?")->execute([$id_candidature]);
        
        // Les tables 'reponses_candidature' et 'dossiers' ont ON DELETE CASCADE sur 'candidatures(id)'
        // via 'id_candidature'. La suppression de la candidature suffirait, mais on nettoie proprement.
        $pdo->prepare("DELETE FROM reponses_candidature WHERE id_candidature = ?")->execute([$id_candidature]);
        $pdo->prepare("DELETE FROM dossiers WHERE id_candidature = ?")->execute([$id_candidature]);
        
        // Enfin, la candidature elle-même
        $pdo->prepare("DELETE FROM candidatures WHERE id = ?")->execute([$id_candidature]);
        
        $pdo->commit();
        $_SESSION['success_message'] = "Votre candidature a été retirée avec succès.";
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("ERREUR SUPPRESSION CANDIDATURE (ID $id_candidature): " . $e->getMessage());
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header("Location: mes_candidatures.php");
    exit();
} else {
    header("Location: mes_candidatures.php");
    exit();
}
