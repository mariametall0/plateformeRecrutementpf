<?php
require_once "../includes/layout.php";
require_once "../includes/ai_helper.php";
require_once "../includes/pdf_helper.php";

header('Content-Type: application/json');

// Protection candidat
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'candidat') {
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

$user_id = $_SESSION["id"];
$action = $_POST["action"] ?? "";
$type = $_POST["type"] ?? "";

try {
    if ($action === "save_bio") {
        $bio = $_POST["bio"] ?? "";
        $check = $pdo->prepare("SELECT id FROM profils_candidats WHERE id_utilisateur = ?");
        $check->execute([$user_id]);
        if ($check->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE profils_candidats SET bio = ? WHERE id_utilisateur = ?");
            $stmt->execute([$bio, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO profils_candidats (id_utilisateur, bio) VALUES (?, ?)");
            $stmt->execute([$user_id, $bio]);
        }
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === "getItem") {
        $id = (int)$_POST["id"];
        $table = "";
        if ($type === 'formation') $table = "cv_formations";
        elseif ($type === 'experience') $table = "cv_experiences";
        elseif ($type === 'competence') $table = "cv_competences";
        elseif ($type === 'langue') $table = "cv_langues";
        elseif ($type === 'certification') $table = "cv_certifications";

        if ($table) {
            $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ? AND id_utilisateur = ?");
            $stmt->execute([$id, $user_id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $item]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Type invalide']);
        }
        exit();
    }

    if ($action === "delete") {
        $id = (int)$_POST["id"];
        $table = "";
        if ($type === 'formation') $table = "cv_formations";
        elseif ($type === 'experience') $table = "cv_experiences";
        elseif ($type === 'competence') $table = "cv_competences";
        elseif ($type === 'langue') $table = "cv_langues";
        elseif ($type === 'certification') $table = "cv_certifications";

        if ($table) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ? AND id_utilisateur = ?");
            $stmt->execute([$id, $user_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Type invalide']);
        }
        exit();
    }

    if ($action === "add" || $action === "edit") {
        $id = isset($_POST["id"]) ? (int)$_POST["id"] : null;
        
        if ($type === 'formation') {
            if ($action === "add") {
                $stmt = $pdo->prepare("INSERT INTO cv_formations (id_utilisateur, diplome, etablissement, ville, date_debut, date_fin, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $_POST["diplome"] ?? '', $_POST["etablissement"] ?? '', $_POST["ville"] ?? null, ($_POST["date_debut"] ?? null) ?: null, ($_POST["date_fin"] ?? null) ?: null, $_POST["description"] ?? null]);
            } else {
                $stmt = $pdo->prepare("UPDATE cv_formations SET diplome=?, etablissement=?, ville=?, date_debut=?, date_fin=?, description=? WHERE id=? AND id_utilisateur=?");
                $stmt->execute([$_POST["diplome"] ?? '', $_POST["etablissement"] ?? '', $_POST["ville"] ?? null, ($_POST["date_debut"] ?? null) ?: null, ($_POST["date_fin"] ?? null) ?: null, $_POST["description"] ?? null, $id, $user_id]);
            }
        } elseif ($type === 'experience') {
            $en_poste = isset($_POST["en_poste"]) ? 1 : 0;
            $date_fin = $en_poste ? null : (($_POST["date_fin"] ?? null) ?: null);
            if ($action === "add") {
                $stmt = $pdo->prepare("INSERT INTO cv_experiences (id_utilisateur, poste, entreprise, ville, date_debut, date_fin, en_poste, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $_POST["poste"] ?? '', $_POST["entreprise"] ?? '', $_POST["ville"] ?? null, ($_POST["date_debut"] ?? null) ?: null, $date_fin, $en_poste, $_POST["description"] ?? null]);
            } else {
                $stmt = $pdo->prepare("UPDATE cv_experiences SET poste=?, entreprise=?, ville=?, date_debut=?, date_fin=?, en_poste=?, description=? WHERE id=? AND id_utilisateur=?");
                $stmt->execute([$_POST["poste"] ?? '', $_POST["entreprise"] ?? '', $_POST["ville"] ?? null, ($_POST["date_debut"] ?? null) ?: null, $date_fin, $en_poste, $_POST["description"] ?? null, $id, $user_id]);
            }
        } elseif ($type === 'competence') {
            if ($action === "add") {
                $stmt = $pdo->prepare("INSERT INTO cv_competences (id_utilisateur, nom, niveau, type) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $_POST["nom"], (int)$_POST["niveau"], $_POST["type"]]);
            } else {
                $stmt = $pdo->prepare("UPDATE cv_competences SET nom=?, niveau=?, type=? WHERE id=? AND id_utilisateur=?");
                $stmt->execute([$_POST["nom"], (int)$_POST["niveau"], $_POST["type"], $id, $user_id]);
            }
        } elseif ($type === 'langue') {
            if ($action === "add") {
                $stmt = $pdo->prepare("INSERT INTO cv_langues (id_utilisateur, langue, niveau) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $_POST["langue"], $_POST["niveau"]]);
            } else {
                $stmt = $pdo->prepare("UPDATE cv_langues SET langue=?, niveau=? WHERE id=? AND id_utilisateur=?");
                $stmt->execute([$_POST["langue"], $_POST["niveau"], $id, $user_id]);
            }
        } elseif ($type === 'certification') {
            if ($action === "add") {
                $stmt = $pdo->prepare("INSERT INTO cv_certifications (id_utilisateur, nom, organisme, date_obtention) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $_POST["nom"], $_POST["organisme"] ?? null, $_POST["date_obtention"] ?: null]);
            } else {
                $stmt = $pdo->prepare("UPDATE cv_certifications SET nom=?, organisme=?, date_obtention=? WHERE id=? AND id_utilisateur=?");
                $stmt->execute([$_POST["nom"], $_POST["organisme"] ?? null, $_POST["date_obtention"] ?: null, $id, $user_id]);
            }
        }
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === "analyze_profile") {
        // Collecte des données du profil pour l'IA
        $stmt = $pdo->prepare("SELECT bio FROM profils_candidats WHERE id_utilisateur = ?");
        $stmt->execute([$user_id]);
        $bio = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT * FROM cv_formations WHERE id_utilisateur = ?");
        $stmt->execute([$user_id]);
        $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT * FROM cv_experiences WHERE id_utilisateur = ?");
        $stmt->execute([$user_id]);
        $exps = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $profil_complet = [
            'description' => $bio,
            'formations' => $forms,
            'experiences' => $exps
        ];

        $client = new GeminiClient();
        // Utiliser une version adaptée de l'analyse pour le profil général
        $cv_text = json_encode($profil_complet, JSON_UNESCAPED_UNICODE);
        $result = $client->suggest_cv_improvements($cv_text, "Profil Général", "Analyse globale pour optimisation de carrière");
        
        echo json_encode(['success' => true, 'advice' => $result]);
        exit();
    }

    if ($action === "import_pdf") {
        if (!isset($_FILES['cv_file'])) {
            echo json_encode(['success' => false, 'error' => 'Aucun fichier reçu']);
            exit();
        }

        // 1. Upload temporaire
        $file_path = handle_file_upload($_FILES['cv_file'], "temp_imports/");
        if (!$file_path) {
            echo json_encode(['success' => false, 'error' => "Erreur lors de l'upload du fichier"]);
            exit();
        }
        $abs_path = __DIR__ . "/../uploads/" . $file_path;

        // 2. Extraction texte
        $text = extract_text_from_pdf($abs_path);
        if (strlen($text) < 50) {
            echo json_encode(['success' => false, 'error' => "Le texte du CV n'a pas pu être extrait correctement (PDF protégé ou image)"]);
            exit();
        }

        // 3. Parsing IA
        $client = new GeminiClient();
        $data = $client->parse_cv_to_structured_data($text);

        if (isset($data['error'])) {
            echo json_encode(['success' => false, 'error' => $data['error']]);
            exit();
        }

        // 4. Persistence (On vide l'ancien pour un import propre si désiré)
        // Note: On pourrait aussi proposer d'ajouter seulement. Ici on remplace pour éviter les doublons.
        $pdo->prepare("DELETE FROM cv_formations WHERE id_utilisateur = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM cv_experiences WHERE id_utilisateur = ?")->execute([$user_id]);
        $pdo->prepare("DELETE FROM cv_competences WHERE id_utilisateur = ?")->execute([$user_id]);

        if (!empty($data['bio'])) {
            $pdo->prepare("UPDATE profils_candidats SET bio = ? WHERE id_utilisateur = ?")->execute([$data['bio'], $user_id]);
        }

        if (!empty($data['formations'])) {
            $stmt = $pdo->prepare("INSERT INTO cv_formations (id_utilisateur, diplome, etablissement, ville, date_debut, date_fin, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($data['formations'] as $f) {
                $stmt->execute([$user_id, $f['diplome'], $f['etablissement'], $f['ville'] ?? null, $f['date_debut'] ?: null, $f['date_fin'] ?: null, $f['description'] ?? null]);
            }
        }

        if (!empty($data['experiences'])) {
            $stmt = $pdo->prepare("INSERT INTO cv_experiences (id_utilisateur, poste, entreprise, ville, date_debut, date_fin, en_poste, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($data['experiences'] as $e) {
                $en_poste = ($e['en_poste'] ?? false) ? 1 : 0;
                $stmt->execute([$user_id, $e['poste'], $e['entreprise'], $e['ville'] ?? null, $e['date_debut'] ?: null, $en_poste ? null : ($e['date_fin'] ?: null), $en_poste, $e['description'] ?? null]);
            }
        }

        // Compétences
        if (!empty($data['hard_skills'])) {
            $stmt = $pdo->prepare("INSERT INTO cv_competences (id_utilisateur, nom, niveau, type) VALUES (?, ?, 80, 'technique')");
            foreach ($data['hard_skills'] as $s) $stmt->execute([$user_id, $s]);
        }
        if (!empty($data['soft_skills'])) {
            $stmt = $pdo->prepare("INSERT INTO cv_competences (id_utilisateur, nom, niveau, type) VALUES (?, ?, 90, 'professionnelle')");
            foreach ($data['soft_skills'] as $s) $stmt->execute([$user_id, $s]);
        }

        @unlink($abs_path); // Nettoyage
        echo json_encode(['success' => true]);
        exit();
    }

    echo json_encode(['success' => false, 'error' => 'Action non reconnue']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur : ' . $e->getMessage()]);
}
?>
