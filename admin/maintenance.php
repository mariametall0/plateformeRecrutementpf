<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

try {
    // Statistiques simples
    $stats = [];
    $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
    $stats['gerants'] = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'gerant'")->fetchColumn();
    $stats['candidats'] = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'candidat'")->fetchColumn();
    $stats['concours'] = (int)$pdo->query("SELECT COUNT(*) FROM concours")->fetchColumn();
    $stats['candidatures'] = (int)$pdo->query("SELECT COUNT(*) FROM candidatures")->fetchColumn();

    // Taille des uploads (approximation)
    $upload_dir = "../uploads/";
    $upload_size = 0;
    if (is_dir($upload_dir)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($upload_dir));
        foreach ($files as $file) {
            if ($file->isFile()) {
                $upload_size += $file->getSize();
            }
        }
    }
    $upload_size_mb = round($upload_size / 1024 / 1024, 2);

    // Taille des uploads (approximation)
    $upload_dir = __DIR__ . "/../uploads/";
    $upload_size = 0;
    if (is_dir($upload_dir)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($upload_dir));
        foreach ($files as $file) {
            if ($file->isFile()) {
                $upload_size += $file->getSize();
            }
        }
    }
    $upload_size_mb = round($upload_size / 1024 / 1024, 2);

} catch (PDOException $e) {
    $error = "Erreur technique : " . $e->getMessage();
}

include_header("Hub de Maintenance");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <!-- Hero Maintenance -->
    <div class="recap-panel anim-up mb-5">
        <div class="row align-items-center p-3 p-lg-5">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-8 fw-bold ls-1">PANEL TECHNIQUE</span>
                    <span class="text-muted small fw-bold"><i class="bi bi-clock-history me-1"></i> Dernière vérification : <?php echo date('H:i'); ?></span>
                </div>
                <h1 class="display-5 fw-black text-gray-900 mb-3">Maintenance & <span class="text-grad">Santé Système</span></h1>
                <p class="text-gray-600 fs-5 mb-0">Outil de diagnostic global pour assurer la stabilité et l'intégrité des données Admissio.</p>
            </div>
            <div class="col-lg-5 text-lg-end mt-4 mt-lg-0">
                <button onclick="window.location.reload()" class="btn-pro btn-pro-primary px-4 py-3">
                    <i class="bi bi-arrow-clockwise me-2"></i> ACTUALISER LE DIAGNOSTIC
                </button>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <!-- Colonne Gauche : Système -->
        <div class="col-lg-8">
            <div class="row g-4">
                <!-- Stockage Card -->
                <div class="col-md-6 anim-up anim-delay-1">
                    <div class="glass-premium p-4 h-100 shadow-premium border-0">
                        <div class="d-flex justify-content-between align-items-start mb-4">
                            <div class="stat-icon-luminous text-success"><i class="bi bi-hdd-stack"></i></div>
                            <div class="text-end">
                                <div class="h3 fw-black mb-0"><?php echo $upload_size_mb; ?> MB</div>
                                <div class="small text-muted fw-bold">Stockage Utilisé</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small fw-bold mb-2">
                                <span>Capacité Uploads</span>
                                <span class="text-success">Quota Non Limité</span>
                            </div>
                            <div class="progress rounded-pill" style="height: 8px; background: rgba(0,0,0,0.05);">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: <?php echo min(100, $upload_size_mb); ?>%"></div>
                            </div>
                        </div>
                        <p class="small text-muted mb-0"><i class="bi bi-info-circle me-1"></i> Comprend les CV, diplômes et photos de profil.</p>
                    </div>
                </div>

                <!-- Serveur Card -->
                <div class="col-md-6 anim-up anim-delay-2">
                    <div class="glass-premium p-4 h-100 shadow-premium border-0">
                        <h6 class="fw-black text-uppercase ls-1 mb-4 text-gray-700">Identité Serveur</h6>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-center justify-content-between border-bottom pb-2 border-light">
                                <span class="text-muted small fw-bold">Version PHP</span>
                                <span class="badge bg-light text-dark border rounded-pill"><?php echo PHP_VERSION; ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between border-bottom pb-2 border-light">
                                <span class="text-muted small fw-bold">Serveur Web</span>
                                <span class="small fw-bold text-gray-600"><?php echo explode(' ', $_SERVER['SERVER_SOFTWARE'])[0]; ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="text-muted small fw-bold">Base de Données</span>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill fw-bold">MySQL (PDO)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Globales -->
                <div class="col-12 anim-up anim-delay-3">
                    <div class="card border-0 shadow-premium rounded-4 bg-white p-4">
                        <h6 class="fw-black text-uppercase ls-1 mb-4 text-gray-700"><i class="bi bi-database-check me-2"></i>Intégrité de la Base de Données</h6>
                        <div class="row g-4 text-center">
                            <div class="col-6 col-md-3">
                                <div class="h4 fw-black text-success mb-1"><?php echo $stats['users']; ?></div>
                                <div class="small text-muted fw-bold text-uppercase">Comptes</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="h4 fw-black text-success mb-1"><?php echo $stats['concours']; ?></div>
                                <div class="small text-muted fw-bold text-uppercase">Opportunités</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="h4 fw-black text-info mb-1"><?php echo $stats['candidatures']; ?></div>
                                <div class="small text-muted fw-bold text-uppercase">Dossiers</div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="h4 fw-black text-warning mb-1"><?php echo $stats['gerants']; ?></div>
                                <div class="small text-muted fw-bold text-uppercase">Gérants</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne Droite : Actions -->
        <div class="col-lg-4 anim-up anim-delay-4">
            <div class="card border-0 shadow-premium rounded-4 bg-gray-900 text-white p-4 h-100">
                <h6 class="fw-black text-uppercase ls-1 mb-4 text-white-50">Actions Systèmes</h6>
                <div class="d-grid gap-3">
                    <button class="btn btn-success p-3 rounded-4 shadow-sm border-0 transition-all hover-translate-x fw-black" onclick="Swal.fire('Information', 'L\'audit de sécurité est en cours d\'analyse.', 'info')">
                        <i class="bi bi-shield-check me-2"></i> LANCER UN AUDIT DE SÉCURITÉ
                    </button>
                    <button class="btn btn-outline-light border-0 bg-white bg-opacity-10 text-start p-3 rounded-4 transition-all hover-translate-x">
                        <i class="bi bi-shield-lock me-2"></i> Audit de sécurité
                    </button>
                    <button class="btn btn-outline-light border-0 bg-white bg-opacity-10 text-start p-3 rounded-4 transition-all hover-translate-x">
                        <i class="bi bi-trash3 me-2"></i> Nettoyer les fichiers orphelins
                    </button>
                    <button class="btn btn-outline-light border-0 bg-white bg-opacity-10 text-start p-3 rounded-4 transition-all hover-translate-x">
                        <i class="bi bi-lightning-charge me-2"></i> Optimiser les tables SQL
                    </button>
                </div>
                
                <div class="mt-auto pt-5">
                    <div class="p-3 rounded-4 bg-white bg-opacity-5 border border-white border-opacity-10 small">
                        <div class="fw-bold mb-1 text-white">Zone Administrateur</div>
                        <p class="text-white-50 mb-0">Ces actions sont irréversibles. Soyez prudent lors du nettoyage de la base.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-translate-x:hover { transform: translateX(5px); background: rgba(255,255,255,0.15) !important; }
.highlight-danger { background: rgba(220, 38, 38, 0.1); color: #dc2626; }
</style>

<?php 
include_footer();
exit();

