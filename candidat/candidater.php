<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_candidat = $_SESSION["id"];
$id_offre = (int)($_GET["id"] ?? $_POST["id"] ?? 0);

if ($id_offre <= 0) {
    send_error("ID d'offre invalide.");
}

try {
    // Vérifier que l'offre est active et ouverte
    $stmt = $pdo->prepare("SELECT id, titre, date_cloture, date_ouverture FROM offres WHERE id = ? AND statut = 'actif' AND date_cloture >= CURDATE() AND date_ouverture <= CURDATE()");
    $stmt->execute([$id_offre]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offre) {
        send_error("Offre non trouvée, clôturée ou non encore ouverte.", 403);
    }

    // Vérifier si déjà candidaté
    $stmt = $pdo->prepare("SELECT id FROM candidatures WHERE id_candidat = ? AND id_offre = ?");
    $stmt->execute([$id_candidat, $id_offre]);
    $exist = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exist) {
        send_json([
            'success' => false,
            'message' => "Vous avez déjà postulé à cette offre.",
            'id_candidature' => $exist['id']
        ], 409);
    }

    // Charger les champs dynamiques
    $stmt_champs = $pdo->prepare("SELECT * FROM champs_formulaire WHERE id_offre = ? ORDER BY ordre ASC");
    $stmt_champs->execute([$id_offre]);
    $champs = $stmt_champs->fetchAll(PDO::FETCH_ASSOC);

    // --- GET : Retourner les champs du formulaire ---
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        send_json([
            'success' => true,
            'offre' => $offre,
            'champs' => $champs,
            'documents_standards' => [
                'cv' => ['label' => 'CV', 'obligatoire' => true],
                'lettre' => ['label' => 'Lettre de motivation', 'obligatoire' => true],
                'diplome' => ['label' => 'Diplôme / Relevé de notes', 'obligatoire' => true]
            ]
        ]);
    }

    // --- POST : Enregistrer la candidature ---
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        verify_csrf_token();

        $erreurs = [];
        // Validation documents standards
        if (empty($_FILES["cv"]["name"])) { $erreurs[] = "Le CV est obligatoire."; }
        if (empty($_FILES["lettre"]["name"])) { $erreurs[] = "La lettre de motivation est obligatoire."; }
        
        // Diplôme peut être un tableau (multiple)
        $diplome_empty = true;
        if (isset($_FILES["diplome"])) {
            if (is_array($_FILES["diplome"]["name"])) {
                foreach ($_FILES["diplome"]["name"] as $name) {
                    if (!empty($name)) { $diplome_empty = false; break; }
                }
            } else {
                if (!empty($_FILES["diplome"]["name"])) { $diplome_empty = false; }
            }
        }
        if ($diplome_empty) { $erreurs[] = "Le diplôme est obligatoire."; }

        // Validation champs obligatoires dynamiques
        foreach ($champs as $champ) {
            $cle = "champ_" . $champ["id"];
            if ($champ["obligatoire"]) {
                if ($champ["type_champ"] === "fichier") {
                    if (empty($_FILES[$cle]["name"])) {
                        $erreurs[] = "Le champ « {$champ['libelle']} » est obligatoire.";
                    }
                } elseif ($champ["type_champ"] === "case_a_cocher") {
                    if (!isset($_POST[$cle])) {
                        $erreurs[] = "Le champ « {$champ['libelle']} » est obligatoire.";
                    }
                } else {
                    if (empty(trim($_POST[$cle] ?? ""))) {
                        $erreurs[] = "Le champ « {$champ['libelle']} » est obligatoire.";
                    }
                }
            }
        }

        if (!empty($erreurs)) {
            send_error(implode(" ", $erreurs));
        }

        // Créer la candidature
        $pdo->prepare("INSERT INTO candidatures (id_candidat, id_offre, date_candidature) VALUES (?, ?, NOW())")
            ->execute([$id_candidat, $id_offre]);
        $id_candidature = $pdo->lastInsertId();

        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

        $ext_ok  = ["pdf","doc","docx","jpg","jpeg","png"];
        $taille_max = 5 * 1024 * 1024;
        $fichiers_std = ["cv" => "cv", "lettre" => "lettre_motivation", "diplome" => "diplome"];

        // Dossiers standards (CV & Lettre)
        foreach (["cv" => "cv", "lettre" => "lettre_motivation"] as $input => $type_doc) {
            if (!empty($_FILES[$input]["name"])) {
                $nom = handle_file_upload($_FILES[$input]);
                if ($nom) {
                    $pdo->prepare("INSERT INTO dossiers (id_candidature, type_document, nom_fichier) VALUES (?, ?, ?)")
                        ->execute([$id_candidature, $type_doc, $nom]);
                }
            }
        }

        // Diplôme(s) - Gestion multiple
        if (!empty($_FILES["diplome"]["name"])) {
            if (is_array($_FILES["diplome"]["name"])) {
                // Plusieurs fichiers
                for ($i = 0; $i < count($_FILES["diplome"]["name"]); $i++) {
                    if (empty($_FILES["diplome"]["name"][$i])) continue;
                    
                    $file_arr = [
                        'name'     => $_FILES["diplome"]["name"][$i],
                        'type'     => $_FILES["diplome"]["type"][$i],
                        'tmp_name' => $_FILES["diplome"]["tmp_name"][$i],
                        'error'    => $_FILES["diplome"]["error"][$i],
                        'size'     => $_FILES["diplome"]["size"][$i]
                    ];
                    $nom = handle_file_upload($file_arr);
                    if ($nom) {
                        $pdo->prepare("INSERT INTO dossiers (id_candidature, type_document, nom_fichier) VALUES (?, ?, ?)")
                            ->execute([$id_candidature, "diplome", $nom]);
                    }
                }
            } else {
                // Cas un seul fichier (si multiple non supporté par le navigateur par ex)
                $nom = handle_file_upload($_FILES["diplome"]);
                if ($nom) {
                    $pdo->prepare("INSERT INTO dossiers (id_candidature, type_document, nom_fichier) VALUES (?, ?, ?)")
                        ->execute([$id_candidature, "diplome", $nom]);
                }
            }
        }

        // Réponses aux champs dynamiques
        foreach ($champs as $champ) {
            $cle = "champ_" . $champ["id"];
            if ($champ["type_champ"] === "fichier") {
                if (!empty($_FILES[$cle]["name"])) {
                    $nom = handle_file_upload($_FILES[$cle]);
                    if ($nom) {
                        $pdo->prepare("INSERT INTO reponses_candidature (id_candidature, id_champ, valeur) VALUES (?, ?, ?)")
                            ->execute([$id_candidature, $champ["id"], $nom]);
                    }
                }
            } elseif ($champ["type_champ"] === "case_a_cocher") {
                $valeur = isset($_POST[$cle]) ? "1" : "0";
                $pdo->prepare("INSERT INTO reponses_candidature (id_candidature, id_champ, valeur) VALUES (?, ?, ?)")
                    ->execute([$id_candidature, $champ["id"], $valeur]);
            } else {
                $valeur = trim($_POST[$cle] ?? "");
                if ($valeur !== "") {
                    $pdo->prepare("INSERT INTO reponses_candidature (id_candidature, id_champ, valeur) VALUES (?, ?, ?)")
                        ->execute([$id_candidature, $champ["id"], $valeur]);
                }
            }
        }

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            send_json([
                'success' => true,
                'message' => "Votre candidature a été envoyée avec succès !",
                'id_candidature' => $id_candidature
            ]);
        } else {
            $_SESSION['success_message'] = "Félicitations ! Votre candidature a été transmise avec succès.";
            header("Location: dashboard.php");
            exit();
        }
    }

} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}
exit();