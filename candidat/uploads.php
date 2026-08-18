<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_candidat = $_SESSION["id"];
$id_candidature = (int)($_GET["id"] ?? $_POST["id"] ?? 0);

if ($id_candidature <= 0) {
    send_error("ID de candidature invalide.");
}

try {
    // Vérifier appartenance
    $stmt = $pdo->prepare("
        SELECT c.id, co.titre AS offre_titre, c.statut
        FROM candidatures c
        JOIN concours co ON c.id_concours = co.id
        WHERE c.id = ? AND c.id_candidat = ?
    ");
    $stmt->execute([$id_candidature, $id_candidat]);
    $candidature = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidature) {
        send_error("Candidature non trouvée ou accès refusé.", 404);
    }

    // --- ACTIONS ---

    // 1. Télécharger un document (Force download)
    if (isset($_GET['action']) && $_GET['action'] === 'download' && isset($_GET['file'])) {
        $filename = basename($_GET['file']);
        $filepath = "../uploads/" . $filename;

        // Vérifier que le fichier appartient bien au candidat via une requête directe
        $stmt_check = $pdo->prepare("
            SELECT d.nom_fichier 
            FROM dossiers d
            JOIN candidatures c ON d.id_candidature = c.id
            WHERE c.id_candidat = ? AND d.nom_fichier = ?
        ");
        $stmt_check->execute([$id_candidat, $filename]);
        
        if ($stmt_check->fetch() && file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit();
        } else {
            $_SESSION['error_message'] = "Fichier introuvable ou accès refusé.";
        }
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        verify_csrf_token();
        $upload_dir = "../uploads/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

        $erreurs = [];
        $types_fichiers = [
            'cv'      => 'cv',
            'lettre'  => 'lettre_motivation',
            'diplome' => 'diplome',
        ];
        $extensions_ok = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $taille_max = 5 * 1024 * 1024;

        foreach ($types_fichiers as $input_name => $type_doc) {
            if (!empty($_FILES[$input_name]['name'])) {
                $file = $_FILES[$input_name];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $extensions_ok)) {
                    $erreurs[] = "Format non autorisé pour le document '$input_name'."; continue;
                }
                if ($file['size'] > $taille_max) {
                    $erreurs[] = "Fichier '$input_name' trop volumineux."; continue;
                }

                $nom_fichier = time() . '_' . uniqid() . '.' . $ext;
                $taille = $file['size'];
                $mime_type = $file['type'];

                if (move_uploaded_file($file['tmp_name'], $upload_dir . $nom_fichier)) {
                    $stmt_old = $pdo->prepare("SELECT nom_fichier FROM dossiers WHERE id_candidature = ? AND type_document = ?");
                    $stmt_old->execute([$id_candidature, $type_doc]);
                    $old = $stmt_old->fetch(PDO::FETCH_ASSOC);
                    if ($old && file_exists($upload_dir . $old['nom_fichier'])) { unlink($upload_dir . $old['nom_fichier']); }
                    
                    $pdo->prepare("DELETE FROM dossiers WHERE id_candidature = ? AND type_document = ?")->execute([$id_candidature, $type_doc]);
                    $pdo->prepare("INSERT INTO dossiers (id_candidature, type_document, nom_fichier, taille_fichier, type_fichier, date_upload) VALUES (?, ?, ?, ?, ?, NOW())")
                        ->execute([$id_candidature, $type_doc, $nom_fichier, $taille, $mime_type]);
                }
            }
        }

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            if (!empty($erreurs)) send_error(implode(" ", $erreurs));
            else send_json(['success' => true, 'message' => "Mis à jour."]);
        } else {
            if (!empty($erreurs)) $_SESSION['error_message'] = implode(" ", $erreurs);
            else $_SESSION['success_message'] = "Vos documents ont été mis à jour.";
            header("Location: uploads.php?id=$id_candidature");
            exit();
        }
    }

    $stmt_docs = $pdo->prepare("SELECT * FROM dossiers WHERE id_candidature = ?");
    $stmt_docs->execute([$id_candidature]);
    $dossiers_db = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);
    $docs_map = [];
    foreach ($dossiers_db as $d) { $docs_map[$d['type_document']] = $d; }

    include_header("Mes documents");
    ?>
    <div class="page-container">
        <div class="page-header">
            <div>
                <h1>Documents du dossier</h1>
                <p class="text-muted mb-0">Candidature pour : <strong><?php echo htmlspecialchars($candidature['offre_titre']); ?></strong></p>
            </div>
            <a href="mes_candidatures.php" class="btn" style="background: var(--border);">← Mes candidatures</a>
        </div>

        <div class="stat-card">
            <h3 class="mb-4">📁 Gérer mes pièces jointes</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $id_candidature; ?>">

                <div class="form-section-group">
                    <?php 
                    $fields = ['cv' => 'CV', 'lettre' => 'Lettre de motivation', 'diplome' => 'Diplôme'];
                    $db_keys = ['cv' => 'cv', 'lettre' => 'lettre_motivation', 'diplome' => 'diplome'];

                    foreach ($fields as $input => $label): 
                        $db_key = $db_keys[$input];
                        $has_file = isset($docs_map[$db_key]);
                    ?>
                        <div class="document-section">
                            <div class="document-header">
                                <label class="form-label document-label"><?php echo $label; ?></label>
                                <?php if ($has_file): ?>
                                    <span class="document-status">✓ En ligne</span>
                                <?php endif; ?>
                            </div>
                            <div class="document-form">
                                <input type="file" name="<?php echo $input; ?>" class="form-control" style="background: white;">
                                <?php if ($has_file): ?>
                                    <div class="document-actions">
                                        <a href="../uploads/<?php echo $docs_map[$db_key]['nom_fichier']; ?>" target="_blank" class="btn" style="padding: 0.4rem 0.8rem; background: var(--background);">👁️ Voir</a>
                                        <a href="?id=<?php echo $id_candidature; ?>&action=download&file=<?php echo $docs_map[$db_key]['nom_fichier']; ?>" class="btn" style="padding: 0.4rem 0.8rem; background: var(--primary); color: white;">📥 Télécharger</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <p class="small text-muted mt-2">Formats acceptés : PDF, DOC, JPG, PNG. Taille maximale : 5 Mo par fichier.</p>
                </div>
                <button type="submit" class="btn btn-success mt-4">Enregistrer</button>
            </form>
        </div>
    </div>
    <?php 
    include_footer();
} catch (PDOException $e) {
    send_error("Erreur DB : " . $e->getMessage(), 500);
}
exit();
?>
