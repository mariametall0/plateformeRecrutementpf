<?php
/**
 * mon_cv_ajax.php – Gestionnaire AJAX pour le CV Numérique.
 */
require_once "../includes/layout.php";
ob_start();
require_once "../includes/analysis_helper.php";
require_once "../includes/pdf_helper.php";

header('Content-Type: application/json');

// Protection candidat
check_role('candidat');

// Protection CSRF systématique pour POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Vérifier dépassement post_max_size
    if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $max_size = ini_get('post_max_size');
        send_json(['error' => "La taille totale dépasse la limite autorisée par le serveur (max $max_size)."], 413);
    }
    verify_csrf_token();
}

function clean_db_date(?string $date, bool $is_month_only = false): ?string {
    if (!$date) return null;
    $date = trim($date);
    if (empty($date) || strpos($date, '0000') === 0) return null;
    if ($is_month_only && strlen($date) === 7) {
        return $date . "-01";
    }
    return $date;
}

$user_id = $_SESSION["id"];
$action = $_POST["action"] ?? $_GET["action"] ?? "";
$type = $_POST["type"] ?? "";

// Mapping des tables
$tables = [
    'formation' => 'cv_formations',
    'experience' => 'cv_experiences',
    'competence' => 'cv_competences',
    'langue' => 'cv_langues',
    'certification' => 'cv_certifications',
    'interet' => 'cv_interets'
];

try {

    // ================= BIO =================
    if ($action === "save_bio") {
        $bio = trim($_POST["bio"] ?? "");
        $specialite = trim($_POST["secteur_specialite"] ?? "");
        $linkedin = trim($_POST["linkedin"] ?? "");
        $nom = trim($_POST["nom"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $telephone = trim($_POST["telephone"] ?? "");
        $adresse = trim($_POST["adresse"] ?? "");
        
        $stmt_u = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, telephone = ?, adresse = ? WHERE id = ?");
        $stmt_u->execute([$nom, $email, $telephone, $adresse, $user_id]);

        $stmt = $pdo->prepare("INSERT INTO profils_candidats (id_utilisateur, bio, secteur_specialite, linkedin) 
                               VALUES (?, ?, ?, ?) 
                               ON DUPLICATE KEY UPDATE bio = VALUES(bio), secteur_specialite = VALUES(secteur_specialite), linkedin = VALUES(linkedin)");
        $stmt->execute([$user_id, $bio, $specialite, $linkedin]);

        send_json(['success' => true]);
    }

    // ================= GET ITEM =================
    if ($action === "getItem") {
        $id = (int)($_POST["id"] ?? 0);
        $table = $tables[$type] ?? null;

        if (!$table) send_json(['error' => 'Type de document invalide'], 400);

        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ? AND id_utilisateur = ?");
        $stmt->execute([$id, $user_id]);

        send_json(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
    }

    // ================= DELETE =================
    if ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);
        $table = $tables[$type] ?? null;

        if (!$table) send_json(['error' => 'Type de document invalide'], 400);

        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ? AND id_utilisateur = ?");
        $stmt->execute([$id, $user_id]);

        send_json(['success' => true]);
    }

    // ================= ADD / EDIT =================
    if ($action === "add" || $action === "edit") {
        $id = isset($_POST["id"]) ? (int)$_POST["id"] : null;

        if ($type === 'formation') {
            $diplome = trim($_POST["diplome"] ?? '');
            if (empty($diplome)) send_json(['error' => 'Le nom du diplôme est requis'], 400);

            $etablissement = trim($_POST["etablissement"] ?? '');
            $ville = trim($_POST["ville"] ?? '');
            $date_debut = clean_db_date($_POST["date_debut"] ?? '', true);
            $date_fin = clean_db_date($_POST["date_fin"] ?? '', true);

            if ($action === "add") {
                $stmt = $pdo->prepare("INSERT INTO cv_formations (id_utilisateur, diplome, etablissement, ville, date_debut, date_fin) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $diplome, $etablissement, $ville, $date_debut, $date_fin]);
            } else {
                $stmt = $pdo->prepare("UPDATE cv_formations SET diplome=?, etablissement=?, ville=?, date_debut=?, date_fin=? WHERE id=? AND id_utilisateur=?");
                $stmt->execute([$diplome, $etablissement, $ville, $date_debut, $date_fin, $id, $user_id]);
            }
        }
        elseif ($type === 'experience') {
            $poste = trim($_POST["poste"] ?? '');
            if (empty($poste)) send_json(['error' => 'L\'intitulé du poste est requis'], 400);

            $entreprise = trim($_POST["entreprise"] ?? '');
            $ville = trim($_POST["ville"] ?? '');
            $description = trim($_POST["description"] ?? '');
            $date_debut = clean_db_date($_POST["date_debut"] ?? '', true);
            $en_poste = isset($_POST["en_poste"]) ? 1 : 0;
            $date_fin = $en_poste ? null : clean_db_date($_POST["date_fin"] ?? '', true);

            if ($action === "add") {
                $stmt = $pdo->prepare("INSERT INTO cv_experiences (id_utilisateur, poste, entreprise, ville, date_debut, date_fin, en_poste, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $poste, $entreprise, $ville, $date_debut, $date_fin, $en_poste, $description]);
            } else {
                $stmt = $pdo->prepare("UPDATE cv_experiences SET poste=?, entreprise=?, ville=?, date_debut=?, date_fin=?, en_poste=?, description=? WHERE id=? AND id_utilisateur=?");
                $stmt->execute([$poste, $entreprise, $ville, $date_debut, $date_fin, $en_poste, $description, $id, $user_id]);
            }
        }
        elseif ($type === 'competence') {
            $nom = trim($_POST["nom"] ?? '');
            if (empty($nom)) send_json(['error' => 'Le nom de la compétence est requis'], 400);

            $niveau = (int)($_POST["niveau"] ?? 50);
            $comp_type = trim($_POST["comp_type"] ?? 'technique');

            if ($action === "add") {
                $stmt = $pdo->prepare("INSERT INTO cv_competences (id_utilisateur, nom, niveau, type) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $nom, $niveau, $comp_type]);
            } else {
                $stmt = $pdo->prepare("UPDATE cv_competences SET nom=?, niveau=?, type=? WHERE id=? AND id_utilisateur=?");
                $stmt->execute([$nom, $niveau, $comp_type, $id, $user_id]);
            }
        }
        elseif ($type === 'langue') {
            $langue = trim($_POST["langue"] ?? '');
            $niveau = trim($_POST["niveau"] ?? 'intermediaire');
            if (empty($langue)) send_json(['error' => 'Le nom de la langue est requis'], 400);

            if ($action === "add") {
                $stmt = $pdo->prepare("INSERT INTO cv_langues (id_utilisateur, langue, niveau) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $langue, $niveau]);
            } else {
                $stmt = $pdo->prepare("UPDATE cv_langues SET langue=?, niveau=? WHERE id=? AND id_utilisateur=?");
                $stmt->execute([$langue, $niveau, $id, $user_id]);
            }
        }
        elseif ($type === 'certification') {
            $nom = trim($_POST["nom"] ?? '');
            $organisme = trim($_POST["organisme"] ?? '');
            $date_obtention = clean_db_date($_POST["date_obtention"] ?? '', false);
            if (empty($nom)) send_json(['error' => 'Le nom de la certification est requis'], 400);

            if ($action === "add") {
                $stmt = $pdo->prepare("INSERT INTO cv_certifications (id_utilisateur, nom, organisme, date_obtention) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $nom, $organisme, $date_obtention]);
            } else {
                $stmt = $pdo->prepare("UPDATE cv_certifications SET nom=?, organisme=?, date_obtention=? WHERE id=? AND id_utilisateur=?");
                $stmt->execute([$nom, $organisme, $date_obtention, $id, $user_id]);
            }
        }
        elseif ($type === 'interet') {
            $nom = trim($_POST["nom"] ?? '');
            if (empty($nom)) send_json(['error' => 'Le nom du centre d\'intérêt est requis'], 400);

            if ($action === "add") {
                $stmt = $pdo->prepare("INSERT INTO cv_interets (id_utilisateur, nom) VALUES (?, ?)");
                $stmt->execute([$user_id, $nom]);
            } else {
                $stmt = $pdo->prepare("UPDATE cv_interets SET nom=? WHERE id=? AND id_utilisateur=?");
                $stmt->execute([$nom, $id, $user_id]);
            }
        }
        send_json(['success' => true]);
    }

    // ================= DELETE FULL CV =================
    if ($action === "delete_full_cv") {
        $pdo->beginTransaction();
        foreach ($tables as $t) {
            $pdo->prepare("DELETE FROM $t WHERE id_utilisateur = ?")->execute([$user_id]);
        }
        $pdo->prepare("UPDATE profils_candidats SET bio = NULL WHERE id_utilisateur = ?")->execute([$user_id]);
        $pdo->commit();
        send_json(['success' => true]);
    }

    // ================= VISIBILITÉ =================
    if ($action === "toggle_visibility") {
        $target = $_POST["target"] ?? "";
        $value = (int)($_POST["value"] ?? 0);
        
        $col = ($target === 'photo') ? 'masquer_photo' : (($target === 'icones') ? 'masquer_icones' : null);
        if (!$col) send_json(['error' => 'Cible invalide'], 400);

        $stmt = $pdo->prepare("UPDATE profils_candidats SET $col = ? WHERE id_utilisateur = ?");
        $stmt->execute([$value, $user_id]);
        
        send_json(['success' => true]);
    }

    // ================= IMPORT INTELLIGENT (IA) =================
    if ($action === "import_pdf") {
        if (!isset($_FILES['cv_file'])) send_json(['error' => 'Aucun fichier sélectionné'], 400);

        $file_path = handle_file_upload($_FILES['cv_file'], "temp_imports");
        if (!$file_path) {
            $msg = $_SESSION['error_message'] ?? 'Erreur lors de l\'envoi du fichier';
            unset($_SESSION['error_message']);
            send_json(['error' => $msg], 400);
        }

        $abs_path = __DIR__ . "/../uploads/" . $file_path;
        
        try {
            $analysis = new AnalysisClient();
            $data = $analysis->extract_from_pdf_file($abs_path);
            @unlink($abs_path);

            if (isset($data['error'])) {
                 // Fallback local si l'IA échoue
                 $text = extract_text_from_pdf($abs_path);
                 $alphanumeric = preg_match_all('/[a-zA-Z0-9éèêàçùôî]/u', $text);
                 $total = mb_strlen(str_replace(' ', '', $text));
                 
                 if (!empty($text) && $total > 0 && ($alphanumeric / $total) > 0.3) {
                     $stmt = $pdo->prepare("INSERT INTO profils_candidats (id_utilisateur, bio) VALUES (?, ?) ON DUPLICATE KEY UPDATE bio = VALUES(bio)");
                     $stmt->execute([$user_id, mb_substr($text, 0, 50000)]);
                     send_json(['success' => true, 'message' => "L'IA est momentanément indisponible. Le texte a été extrait brut dans votre présentation."]);
                 } else {
                     send_json(['error' => "L'IA est indisponible et votre PDF utilise un format illisible localement. Veuillez utiliser un PDF plus standard ou réessayer plus tard."], 400);
                 }
            }

            // Insertion des données structurées
            $pdo->beginTransaction();

            // 1. Bio & Spécialité
            if (!empty($data['bio']) || !empty($data['secteur_specialite'])) {
                $stmt = $pdo->prepare("INSERT INTO profils_candidats (id_utilisateur, bio, secteur_specialite) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE bio = VALUES(bio), secteur_specialite = VALUES(secteur_specialite)");
                $stmt->execute([$user_id, $data['bio'] ?? '', $data['secteur_specialite'] ?? '']);
            }

            // 2. Formations
            if (!empty($data['formations']) && is_array($data['formations'])) {
                foreach ($data['formations'] as $f) {
                    $stmt = $pdo->prepare("INSERT INTO cv_formations (id_utilisateur, diplome, etablissement, ville, date_debut, date_fin) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $f['diplome'] ?? 'Diplôme', $f['etablissement'] ?? '', $f['ville'] ?? '', !empty($f['date_debut']) ? $f['date_debut'] : null, !empty($f['date_fin']) ? $f['date_fin'] : null]);
                }
            }

            // 3. Expériences
            if (!empty($data['experiences']) && is_array($data['experiences'])) {
                foreach ($data['experiences'] as $e) {
                    $en_poste = ($e['en_poste'] ?? false) ? 1 : 0;
                    $stmt = $pdo->prepare("INSERT INTO cv_experiences (id_utilisateur, poste, entreprise, ville, date_debut, date_fin, en_poste, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $e['poste'] ?? 'Poste', $e['entreprise'] ?? '', $e['ville'] ?? '', !empty($e['date_debut']) ? $e['date_debut'] : null, !empty($e['date_fin']) ? $e['date_fin'] : null, $en_poste, $e['description'] ?? '']);
                }
            }

            // 4. Compétences
            if (!empty($data['hard_skills']) && is_array($data['hard_skills'])) {
                foreach ($data['hard_skills'] as $s) {
                    if (!empty($s)) {
                        $stmt = $pdo->prepare("INSERT INTO cv_competences (id_utilisateur, nom, type) VALUES (?, ?, 'technique')");
                        $stmt->execute([$user_id, $s]);
                    }
                }
            }
            if (!empty($data['soft_skills']) && is_array($data['soft_skills'])) {
                foreach ($data['soft_skills'] as $s) {
                    if (!empty($s)) {
                        $stmt = $pdo->prepare("INSERT INTO cv_competences (id_utilisateur, nom, type) VALUES (?, ?, 'professionnelle')");
                        $stmt->execute([$user_id, $s]);
                    }
                }
            }

            // 5. Langues
            if (!empty($data['langues']) && is_array($data['langues'])) {
                foreach ($data['langues'] as $l) {
                    $stmt = $pdo->prepare("INSERT INTO cv_langues (id_utilisateur, langue, niveau) VALUES (?, ?, ?)");
                    $stmt->execute([$user_id, $l['langue'] ?? '', $l['niveau'] ?? 'intermediaire']);
                }
            }

            // 6. Certifications
            if (!empty($data['certifications']) && is_array($data['certifications'])) {
                foreach ($data['certifications'] as $c) {
                    $stmt = $pdo->prepare("INSERT INTO cv_certifications (id_utilisateur, nom, organisme, date_obtention) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user_id, $c['nom'] ?? 'Certification', $c['organisme'] ?? '', $c['date_obtention'] ?? null]);
                }
            }

            // 7. Centres d'intérêt
            if (!empty($data['interets']) && is_array($data['interets'])) {
                foreach ($data['interets'] as $i) {
                    if (!empty($i)) {
                        $stmt = $pdo->prepare("INSERT INTO cv_interets (id_utilisateur, nom) VALUES (?, ?)");
                        $stmt->execute([$user_id, $i]);
                    }
                }
            }

            $pdo->commit();
            send_json(['success' => true, 'message' => "CV analysé avec succès ! Toutes les sections ont été remplies."]);

        } catch (Exception $e) {
            @unlink($abs_path);
            error_log("IMPORT AI ERROR: " . $e->getMessage());
            send_json(['error' => "Erreur lors de l'analyse IA. Veuillez réessayer."], 500);
        }
    }

    send_json(['error' => 'Action non reconnue'], 400);

} catch (Exception $e) {
    error_log("ERREUR mon_cv_ajax.php : " . $e->getMessage());
    send_json(['error' => 'Une erreur technique est survenue'], 500);
}