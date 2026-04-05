<?php
/**
 * layout.php – Utilitaires et structure HTML du Backend.
 */

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/functions.php";

/**
 * Envoie une réponse au format JSON (pour AJAX)
 */

/**
 * Inclusion du Header HTML
 */
function include_header(string $title = "Admissio"): void {
    $script_name = $_SERVER['SCRIPT_NAME'];
    // On extrait le chemin jusqu'à plateforme_recrutement inclus
    $root_pos = strpos($script_name, '/plateforme_recrutement/');
    if ($root_pos !== false) {
        $base_path = '/plateforme_recrutement/';
    } else {
        // Fallback plus générique si le nom de dossier change (moins probable ici)
        $base_path = (strpos($script_name, '/candidat/') !== false || strpos($script_name, '/gerant/') !== false || strpos($script_name, '/auth/') !== false) 
                     ? '../' : './';
        // En fait sur ce projet, on va forcer la racine connue
        $base_path = '/plateforme_recrutement/';
    }
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> – Admissio</title>
        <!-- Bootstrap 5 CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
        <!-- Animate.css pour des animations fluides -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <!-- Custom CSS -->
        <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
        <style>
            :root {
                --primary: #4f46e5;
                --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
                --secondary: #64748b;
                --background: #fdfcfc;
                --surface: rgba(255, 255, 255, 0.85);
                --text-main: #0f172a;
                --text-muted: #64748b;
                --border: rgba(0,0,0,0.06);
                --radius: 1rem;
                --shadow-premium: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            }

            body { 
                font-family: 'Inter', sans-serif; 
                background: linear-gradient(180deg, #fdfcfc 0%, #f1f5f9 100%) !important;
                color: var(--text-main);
                min-height: 100vh;
                letter-spacing: -0.01em;
            }

            .container-fluid { max-width: 1600px; margin: 0 auto; }

            h1, h2, h3, h4, h5, h6, .navbar-brand { 
                font-family: 'Outfit', sans-serif !important; 
                font-weight: 800 !important;
                letter-spacing: -0.02em;
                color: #0f172a;
            }

            /* --- Glassmorphism Navbar --- */
            .glass-navbar {
                background: rgba(255, 255, 255, 0.7) !important;
                backdrop-filter: blur(12px) saturate(180%);
                -webkit-backdrop-filter: blur(12px) saturate(180%);
                border-bottom: 1px solid var(--border);
                margin-top: 1rem;
                border-radius: 1.5rem;
                margin-left: 1rem;
                margin-right: 1rem;
            }

            /* --- Premium Cards --- */
            .card {
                border: 1px solid var(--border) !important;
                border-radius: 1.5rem !important;
                background: var(--surface);
                backdrop-filter: blur(8px);
                box-shadow: var(--shadow-premium) !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .card:hover { 
                transform: translateY(-4px); 
                box-shadow: 0 20px 30px -10px rgba(0,0,0,0.1) !important;
            }

            /* --- Modern Buttons --- */
            .btn {
                border-radius: 1rem !important;
                padding: 0.8rem 1.8rem;
                font-weight: 700;
                transition: all 0.2s ease;
                letter-spacing: -0.01em;
            }
            .btn-primary {
                background: var(--primary-gradient) !important;
                border: none !important;
                box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25) !important;
            }
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35) !important;
            }

            /* --- Inputs --- */
            .form-control, .form-select {
                border-radius: 1rem !important;
                border: 1px solid #e2e8f0 !important;
                padding: 0.75rem 1.25rem;
                background-color: #fff;
                transition: all 0.2s ease;
            }
            .form-control:focus {
                border-color: var(--primary) !important;
                box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1) !important;
            }

            /* --- Tables Re-imagined --- */
            .table-responsive { border-radius: 1.5rem; overflow: hidden; }
            .table { border-collapse: separate; border-spacing: 0 0.5rem; margin-top: -0.5rem; }
            .table thead th {
                background: transparent;
                border: none;
                color: var(--text-muted);
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.1em;
                padding: 1.5rem 1rem;
            }
            .table tbody tr {
                background: white;
                box-shadow: 0 2px 5px rgba(0,0,0,0.02);
                border-radius: 1rem;
                transition: background 0.2s;
            }
            .table tbody tr td { 
                border-top: 1px solid #f1f5f9; 
                border-bottom: 1px solid #f1f5f9;
                padding: 1.5rem 1rem;
            }
            .table tbody tr td:first-child { border-left: 1px solid #f1f5f9; border-top-left-radius: 1rem; border-bottom-left-radius: 1rem; }
            .table tbody tr td:last-child { border-right: 1px solid #f1f5f9; border-top-right-radius: 1rem; border-bottom-right-radius: 1rem; }
            
            /* --- Decorative Elements --- */
            .stat-card-icon {
                width: 64px; height: 64px;
                display: flex; align-items: center; justify-content: center;
                border-radius: 1.25rem; font-size: 2rem;
                margin-bottom: 1.5rem;
            }

            .badge {
                padding: 0.6em 1.2em;
                border-radius: 2rem;
                font-weight: 700;
                text-transform: uppercase;
                font-size: 0.7rem;
                letter-spacing: 0.05em;
            }

            /* Transitions standards */
            .transition-hover { transition: all 0.3s ease; }
            .transition-hover:hover { transform: scale(1.02); }
        </style>
    </head>
    <body class="d-flex flex-column min-vh-100">
    <?php if (isset($_SESSION['role'])): ?>
        <nav class="navbar navbar-expand-lg navbar-light glass-navbar sticky-top shadow-sm py-3">
            <div class="container-fluid px-2 px-md-3">
                <a class="navbar-brand fw-extrabold text-primary fs-4 d-flex align-items-center gap-2" href="<?php echo $base_path; ?>index.php">
                    <div class="bg-primary text-white rounded p-1 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="bi bi-briefcase-fill fs-5"></i>
                    </div>
                    Admissio
                </a>
                <button class="navbar-toggler border-0 shadow-none focus-ring focus-ring-primary" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-center gap-3">
                        <li class="nav-item">
                            <a class="nav-link px-3 d-flex align-items-center gap-2" href="<?php echo $base_path . $_SESSION['role']; ?>/dashboard.php">
                                <i class="bi bi-grid-1x2"></i> Tableau de bord
                            </a>
                        </li>
                        <?php if ($_SESSION['role'] === 'candidat'): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 d-flex align-items-center gap-2" href="<?php echo $base_path; ?>candidat/mon_cv.php">
                                <i class="bi bi-file-earmark-medical"></i> Mon CV numérique
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="nav-item ms-lg-2">
                            <a class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2" href="<?php echo $base_path; ?>auth/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Déconnexion
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

    <?php endif; ?>
    
    <main class="container-fluid px-2 px-md-3 py-3">
    <?php
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . 
             htmlspecialchars($_SESSION['error_message']) . 
             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['error_message']);
    }
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . 
             htmlspecialchars($_SESSION['success_message']) . 
             '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['success_message']);
    }
}

/**
 * Fonction centralisée pour la gestion des uploads
 */
function handle_file_upload(array $file, string $target_subfolder = ""): ?string {
    $upload_dir = __DIR__ . "/../uploads/" . $target_subfolder;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $ext_ok = ["pdf", "doc", "docx", "jpg", "jpeg", "png"];
    $max_size = 5 * 1024 * 1024; // 5 Mo

    $filename = $file['name'];
    $tmp_name = $file['tmp_name'];
    $size = $file['size'];
    $error = $file['error'];

    if ($error !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($size > $max_size) {
        $_SESSION['error_message'] = "Le fichier est trop volumineux (max 5Mo).";
        return null;
    }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, $ext_ok)) {
        $_SESSION['error_message'] = "Format de fichier non autorisé.";
        return null;
    }

    $new_name = time() . "_" . uniqid() . "." . $ext;
    $destination = $upload_dir . $new_name;

    if (move_uploaded_file($tmp_name, $destination)) {
        // Retourner le chemin relatif à partir du dossier uploads/
        return ($target_subfolder ? $target_subfolder . "/" : "") . $new_name;
    }

    return null;
}

/**
 * Inclusion du Footer HTML
 */
function include_footer(): void {
    ?>
    </main>
    <footer class="mt-auto py-5 bg-white border-top">
        <div class="container d-flex flex-column align-items-center">
            <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                <div class="bg-primary text-white rounded p-1 d-flex align-items-center justify-content-center shadow-sm" style="width: 24px; height: 24px;">
                    <i class="bi bi-briefcase-fill fs-7" style="font-size: 0.75rem;"></i>
                </div>
                <span class="fw-bold text-dark fs-5" style="font-family: 'Outfit', sans-serif;">Admissio</span>
            </div>
            
            <div class="d-flex flex-wrap justify-content-center gap-4 mb-4 small text-uppercase fw-bold" style="letter-spacing: 0.5px;">
                <a href="/plateforme_recrutement/contact.php" class="text-decoration-none text-muted transition-hover text-hover-primary">Support & Assistance</a>
                <a href="/plateforme_recrutement/cgu.php" class="text-decoration-none text-muted transition-hover text-hover-primary">CGU</a>
                <a href="/plateforme_recrutement/confidentialite.php" class="text-decoration-none text-muted transition-hover text-hover-primary">Confidentialité</a>
            </div>
            
            <p class="text-muted small mb-0">&copy; <?php echo date('Y'); ?> Admissio – La plateforme RH de nouvelle génération.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            if (icon) icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            if (icon) icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }
    </script>
    </body>
    </html>
    <?php
}
