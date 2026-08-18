<?php
require_once "../includes/layout.php";
require_once "../includes/cv_pdf_helper.php";

// Protection candidat
check_role('candidat');

$id_candidat = $_SESSION["id"];
$id_concours = (int)($_POST["id"] ?? $_GET["id"] ?? 0);

if ($id_concours <= 0) {
    send_error("ID du concours invalide.");
}

try {
    // Vérifier si la limite post_max_size a été dépassée (ce qui vide $_POST et $_FILES)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $max_size = ini_get('post_max_size');
        send_error("La taille totale des fichiers dépasse la limite globale autorisée par le serveur (max $max_size).", 413);
    }

    verify_csrf_token();
 
    // Vérifier si déjà postulé
    $stmt = $pdo->prepare("SELECT id FROM candidatures WHERE id_candidat = ? AND id_concours = ?");
    $stmt->execute([$id_candidat, $id_concours]);
    $exist = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exist) {
        send_error("Vous avez déjà postulé à ce concours.", 409);
    }

    // Vérifier le concours existe et est ouvert
    $stmt = $pdo->prepare("SELECT id FROM concours WHERE id = ? AND statut = 'actif' AND date_cloture >= CURDATE()");
    $stmt->execute([$id_concours]);
    if (!$stmt->fetch()) {
        send_error("Ce concours n'existe pas ou est fermé.", 404);
    }

    // Traitement des fichiers
    $cv_path = null;
    $lettre_path = null;
    $diplome_paths = [];
    
    $mode_cv_digital = isset($_POST['mode_cv_digital']) && $_POST['mode_cv_digital'] == '1';
    $mode_lettre_online = isset($_POST['mode_lettre_online']) && $_POST['mode_lettre_online'] == '1';

    // 1. CV Handling
    if ($mode_cv_digital) {
        $pdf_cv = build_cv_pdf_object($pdo, $id_candidat);
        if ($pdf_cv) {
            $filename = "CV_Digital_" . $id_candidat . "_" . time() . ".pdf";
            $dest_path = __DIR__ . "/../uploads/cvs/" . $filename;
            $pdf_cv->Output("F", $dest_path);
            $cv_path = "cvs/" . $filename;
        }
    } elseif (isset($_FILES['cv']) && $_FILES['cv']['error'] !== UPLOAD_ERR_NO_FILE) {
        $cv_path = handle_file_upload($_FILES['cv'], 'cvs');
        if (!$cv_path && isset($_SESSION['error_message'])) {
            send_error("Erreur CV : " . $_SESSION['error_message']);
        }
    }

    // 2. Motivation Letter Handling
    if ($mode_lettre_online) {
        $lettre_texte = trim($_POST['lettre_texte'] ?? '');
        if (!empty($lettre_texte)) {
            // Get concours title for the PDF header
            $st = $pdo->prepare("SELECT titre FROM concours WHERE id = ?");
            $st->execute([$id_concours]);
            $titre_concours = $st->fetchColumn() ?: "Opportunité";

            $pdf_lettre = generate_motivation_pdf($pdo, $id_candidat, $lettre_texte, $titre_concours);
            if ($pdf_lettre) {
                $filename = "Lettre_Online_" . $id_candidat . "_" . time() . ".pdf";
                $dest_path = __DIR__ . "/../uploads/applications/" . $filename;
                $pdf_lettre->Output("F", $dest_path);
                $lettre_path = "applications/" . $filename;
            }
        }
    } elseif (isset($_FILES['lettre']) && $_FILES['lettre']['error'] !== UPLOAD_ERR_NO_FILE) {
        $lettre_path = handle_file_upload($_FILES['lettre'], 'applications');
        if (!$lettre_path && isset($_SESSION['error_message'])) {
            send_error("Erreur Lettre : " . $_SESSION['error_message']);
        }
    }

    // Diplômes (multiples)
    if (isset($_FILES['diplome']) && is_array($_FILES['diplome']['error'])) {
        foreach ($_FILES['diplome']['error'] as $key => $error) {
            if ($error !== UPLOAD_ERR_NO_FILE) {
                $file = [
                    'name' => $_FILES['diplome']['name'][$key],
                    'type' => $_FILES['diplome']['type'][$key],
                    'tmp_name' => $_FILES['diplome']['tmp_name'][$key],
                    'error' => $error,
                    'size' => $_FILES['diplome']['size'][$key]
                ];
                $result = handle_file_upload($file, 'applications');
                if ($result) {
                    $diplome_paths[] = $result;
                } else if (isset($_SESSION['error_message'])) {
                    send_error("Erreur Diplôme : " . $_SESSION['error_message']);
                }
            }
        }
    }

    // Vérifier les fichiers obligatoires
    if (!$cv_path) {
        send_error("Le CV (PDF) est obligatoire.");
    }
    if (!$lettre_path) {
        send_error("La lettre de motivation est obligatoire.");
    }
    if (empty($diplome_paths)) {
        send_error("Au moins un diplôme/certificat est obligatoire.");
    }

    if (!isset($_SESSION['error_message'])) {
        $pdo->beginTransaction();
        try {
            // Insérer la candidature
            $stmt = $pdo->prepare("
                INSERT INTO candidatures 
                (id_candidat, id_concours, statut, date_candidature)
                VALUES (?, ?, 'en_attente', NOW())
            ");
            $stmt->execute([
                $id_candidat,
                $id_concours
            ]);
            $id_candidature = $pdo->lastInsertId();

            // Insérer les documents dans la table dossiers
            $stmt_doc = $pdo->prepare("INSERT INTO dossiers (id_candidature, type_document, nom_fichier, date_upload) VALUES (?, ?, ?, NOW())");
            
            // CV
            $stmt_doc->execute([$id_candidature, 'cv', $cv_path]);
            
            // Lettre de motivation
            $stmt_doc->execute([$id_candidature, 'lettre_motivation', $lettre_path]);
            
            // Diplômes
            foreach ($diplome_paths as $dp) {
                $stmt_doc->execute([$id_candidature, 'diplome', $dp]);
            }

            // Traiter les champs dynamiques
            $stmt_champs = $pdo->prepare("SELECT id, type_champ FROM champs_formulaire WHERE id_concours = ?");
            $stmt_champs->execute([$id_concours]);
            $champs = $stmt_champs->fetchAll(PDO::FETCH_ASSOC);

            foreach ($champs as $champ) {
                $champ_key = 'champ_' . $champ['id'];
                $valeur = null;

                if ($champ['type_champ'] === 'fichier' && isset($_FILES[$champ_key]) && $_FILES[$champ_key]['error'] === UPLOAD_ERR_OK) {
                    $result = handle_file_upload($_FILES[$champ_key], 'applications');
                    $valeur = $result;
                    // Aussi insérer ces fichiers dans dossiers ? 
                    // Pour simplifier on le garde dans reponses_candidature et on peut aussi l'ajouter à dossiers
                    $stmt_doc->execute([$id_candidature, 'autre', $valeur]);
                } elseif ($champ['type_champ'] === 'case_a_cocher') {
                    $valeur = isset($_POST[$champ_key]) ? '1' : '0';
                } else {
                    $valeur = trim($_POST[$champ_key] ?? '');
                }

                if ($valeur !== null && $valeur !== '') {
                    $stmt_reponse = $pdo->prepare("
                        INSERT INTO reponses_candidature 
                        (id_candidature, id_champ, valeur)
                        VALUES (?, ?, ?)
                    ");
                    $stmt_reponse->execute([$id_candidature, $champ['id'], $valeur]);
                }
            }

            $pdo->commit();

            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
                send_json([
                    'success' => true,
                    'message' => 'Votre candidature a été envoyée avec succès.',
                    'id_candidature' => $id_candidature
                ], 201);
            } else {
                $_SESSION['success_message'] = "Votre candidature a été envoyée avec succès. Suivez votre dossier depuis votre dashboard.";
                header("Location: mes_candidatures.php");
                exit();
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            send_error("Erreur lors de l'enregistrement : " . $e->getMessage(), 500);
        }
    }

} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}
exit();
?>