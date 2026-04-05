<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

// Actions de maintenance
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    $action = $_POST["action"] ?? "";
    $msg = "";

    try {
        if ($action === "optimize") {
            $pdo->exec("OPTIMIZE TABLE utilisateurs, offre, candidatures, dossiers, reponses_candidature");
            $msg = "Base de données optimisée.";
        } elseif ($action === "clear_uploads") {
            // Logique simplifiée : Supprimer les fichiers non référencés
            $files = glob("../uploads/*.*");
            $count = 0;
            foreach ($files as $file) {
                $basename = basename($file);
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM dossiers WHERE nom_fichier = ?");
                $stmt->execute([$basename]);
                $in_db = $stmt->fetchColumn();

                if (!$in_db) {
                    unlink($file);
                    $count++;
                }
            }
            $msg = "$count fichiers orphelins supprimés.";
        }

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
            send_json(['success' => true, 'message' => $msg]);
        } else {
            $_SESSION['success_message'] = $msg;
            header("Location: maintenance.php");
            exit();
        }
    } catch (Exception $e) {
        send_error("Erreur : " . $e->getMessage());
    }
}

// Récupération des stats pour l'affichage (GET)
try {
    $stats = [];
    $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
    $stats['gerants'] = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'gerant'")->fetchColumn();
    $stats['candidats'] = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'candidat'")->fetchColumn();
    $stats['offre'] = (int)$pdo->query("SELECT COUNT(*) FROM offres")->fetchColumn();
    $stats['candidatures'] = (int)$pdo->query("SELECT COUNT(*) FROM candidatures")->fetchColumn();

    $upload_dir = "../uploads/";
    $upload_size = 0;
    if (is_dir($upload_dir)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($upload_dir));
        foreach ($files as $file) {
            if ($file->isFile()) $upload_size += $file->getSize();
        }
    }
    $upload_size_mb = round($upload_size / 1024 / 1024, 2);

} catch (PDOException $e) { $stats = []; }

include_header("Maintenance Système");
?>

<div class="row g-4 animate__animated animate__fadeIn">
    <div class="col-12 mb-2">
        <div class="p-4 bg-white rounded-4 shadow-sm border-start border-danger border-5 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Panel de Maintenance 🛠️</h1>
                <p class="text-muted mb-0">Outils d'optimisation et de nettoyage système.</p>
            </div>
            <div class="d-none d-md-block fs-1 opacity-25 text-danger">⚙️</div>
        </div>
    </div>

    <!-- Stats Système -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4 border-bottom pb-2">Informations Système</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-3 d-flex justify-content-between">
                        <span class="text-muted">PHP Version</span>
                        <span class="fw-bold"><?php echo PHP_VERSION; ?></span>
                    </li>
                    <li class="mb-3 d-flex justify-content-between">
                        <span class="text-muted">Total Stockage</span>
                        <span class="fw-bold text-primary"><?php echo $upload_size_mb; ?> Mo</span>
                    </li>
                    <li class="mb-3 d-flex justify-content-between">
                        <span class="text-muted">Utilisateurs</span>
                        <span class="fw-bold"><?php echo $stats['users']; ?></span>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span class="text-muted">Candidatures</span>
                        <span class="fw-bold"><?php echo $stats['candidatures']; ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4 border-bottom pb-2">Actions Rapides</h5>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-4 bg-light rounded-4 text-center border h-100 d-flex flex-column align-items-center">
                            <div class="fs-2 mb-2 text-primary">💾</div>
                            <h6 class="fw-bold">Optimisation SQL</h6>
                            <p class="small text-muted flex-grow-1">Réorganise les tables pour améliorer les performances de lecture/écriture.</p>
                            <form method="POST" class="w-100">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="optimize">
                                <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Optimiser</button>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-4 bg-light rounded-4 text-center border h-100 d-flex flex-column align-items-center">
                            <div class="fs-2 mb-2 text-warning">🗑️</div>
                            <h6 class="fw-bold">Nettoyage Fichiers</h6>
                            <p class="small text-muted flex-grow-1">Supprime les documents dans 'uploads' qui ne sont plus liés à un dossier.</p>
                            <form method="POST" class="w-100" onsubmit="return confirm('Cette action est irréversible. Continuer ?');">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="clear_uploads">
                                <button type="submit" class="btn btn-warning w-100 rounded-pill fw-bold">Purger</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4 border-0 rounded-4 d-flex align-items-center">
                    <span class="fs-4 me-3">ℹ️</span>
                    <div class="small">L'optimisation des tables peut prendre du temps si vous avez des milliers d'entrées. Il est conseillé de la faire hors heures de pointe.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
include_footer();
exit();
?>
