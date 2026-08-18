<?php
/**
 * layout.php – Structure globale et composants UI.
 */

require_once __DIR__ . "/../config/session.php";
require_once __DIR__ . "/functions.php";
require_once __DIR__ . "/translations.php";
require_once __DIR__ . "/../config/database.php";

// Gestion de la langue
if (isset($_GET['lang'])) {
    $lang = in_array($_GET['lang'], ['en', 'fr', 'ar']) ? $_GET['lang'] : 'fr';
    $_SESSION['lang'] = $lang;
}
$current_lang = $_SESSION['lang'] ?? 'fr';

/**
 * Fonction centralisée pour la gestion des uploads sécurisée
 */
function handle_file_upload(array $file, string $target_subfolder = ""): ?string {
    $upload_dir = __DIR__ . "/../uploads/" . $target_subfolder;
    if ($target_subfolder && substr($upload_dir, -1) !== DIRECTORY_SEPARATOR && substr($upload_dir, -1) !== '/') {
        $upload_dir .= '/';
    }
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Sécurisation du nom d'origine
    $filename = basename($file['name']);
    $tmp_name = $file['tmp_name'];
    $size = $file['size'];
    $error = $file['error'];

    if ($error !== UPLOAD_ERR_OK) {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => "Le fichier dépasse la taille autorisée par le serveur.",
            UPLOAD_ERR_FORM_SIZE  => "Le fichier dépasse la taille autorisée par le formulaire.",
            UPLOAD_ERR_PARTIAL    => "Le fichier n'a été que partiellement téléchargé.",
            UPLOAD_ERR_NO_FILE    => "Aucun fichier n'a été téléchargé.",
            UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant.",
            UPLOAD_ERR_CANT_WRITE => "Échec de l'écriture du fichier sur le disque.",
            UPLOAD_ERR_EXTENSION  => "Une extension PHP a arrêté le téléchargement."
        ];
        $_SESSION['error_message'] = $messages[$error] ?? "Erreur lors du téléchargement du fichier.";
        return null;
    }

    $ext_ok = ["pdf", "doc", "docx", "jpg", "jpeg", "png"];
    $max_size = 5 * 1024 * 1024; // 5 Mo

    if ($size > $max_size) {
        $_SESSION['error_message'] = "Le fichier est trop volumineux (max 5Mo).";
        return null;
    }

    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, $ext_ok)) {
        $_SESSION['error_message'] = "Format de fichier non autorisé : " . $ext;
        return null;
    }

    // Génération d'un nom unique et sécurisé (time + random)
    $new_name = time() . "_" . bin2hex(random_bytes(8)) . "." . $ext;
    $destination = $upload_dir . $new_name;

    if (move_uploaded_file($tmp_name, $destination)) {
        return ($target_subfolder ? $target_subfolder . "/" : "") . $new_name;
    }

    return null;
}

/**
 * Affiche les alertes (succès/erreur) stockées en session
 */
function render_alerts(): void {
    if (isset($_SESSION['success_message'])) {
        ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4 animate__animated animate__fadeInDown" role="alert">
            <div class="d-flex align-items-center gap-3">
                <div class="alert-icon-circle bg-success text-white">
                    <i class="bi bi-check-lg"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold">Succès</div>
                    <div class="small op-8"><?php echo $_SESSION['success_message']; ?></div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4 animate__animated animate__shakeX" role="alert">
            <div class="d-flex align-items-center gap-3">
                <div class="alert-icon-circle bg-danger text-white">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-bold">Erreur</div>
                    <div class="small op-8"><?php echo $_SESSION['error_message']; ?></div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php
        unset($_SESSION['error_message']);
    }
}

/**
 * Inclusion du Header HTML
 */
function include_header(string $title = "Admissio"): void {
    global $current_lang;
    $base_path = PROJECT_PATH;
    $current_path = $_SERVER['SCRIPT_NAME'] ?? '';
    
    $role = $_SESSION['role'] ?? null;
    $user_name = $_SESSION['nom'] ?? '';
    $user_photo = $_SESSION['photo_path'] ?? '';

    // Check visibility preference for candidates
    if ($role === 'candidat') {
        global $pdo;
        try {
            $stmt = $pdo->prepare("SELECT masquer_photo FROM profils_candidats WHERE id_utilisateur = ?");
            $stmt->execute([$_SESSION['id']]);
            if ($stmt->fetchColumn()) {
                $user_photo = ''; // Force hide photo if preference is set
            }
        } catch (PDOException $e) {
            // Ignore if table/column does not exist yet to prevent fatal crash
        }
    }

    $user_initials = strtoupper(substr($user_name, 0, 2));

    // Define nav items per role
    $nav_items = [];
    if ($role === 'candidat') {
        $nav_items = [
            ['url' => 'candidat/dashboard.php',          'icon' => 'bi-grid-fill',             'label' => __t('dashboard')],
            ['url' => 'candidat/liste_opportunites.php', 'icon' => 'bi-briefcase-fill',        'label' => __t('opportunities')],
            ['url' => 'candidat/mes_candidatures.php',   'icon' => 'bi-stack',                 'label' => __t('applications'), 'badge' => true],
            ['url' => 'candidat/mon_cv.php',             'icon' => 'bi-file-person-fill',      'label' => __t('Mon CV Expert')],
            ['url' => 'candidat/notifications.php',      'icon' => 'bi-bell-fill',             'label' => __t('notifications')],
            ['url' => 'candidat/modifier_profil.php',    'icon' => 'bi-person-circle',         'label' => __t('profile')],
            ['url' => 'support.php',                     'icon' => 'bi-headset',               'label' => 'Support'],
        ];
    } elseif ($role === 'gerant') {
        $nav_items = [
            ['url' => 'gerant/dashboard.php',           'icon' => 'bi-grid-fill',             'label' => __t('dashboard')],
            ['url' => 'gerant/liste_opportunites.php',  'icon' => 'bi-briefcase-fill',        'label' => __t('opportunities')],
            ['url' => 'gerant/candidatures.php',        'icon' => 'bi-people-fill',           'label' => __t('applications'), 'badge' => true],
            ['url' => 'gerant/liste_entretiens.php',  'icon' => 'bi-calendar-check-fill',   'label' => __t('interviews')],
            ['url' => 'gerant/modifier_profil.php',     'icon' => 'bi-person-circle',         'label' => __t('profile')],
            ['url' => 'support.php',                     'icon' => 'bi-headset',               'label' => 'Support'],
        ];
    } elseif ($role === 'admin') {
        $nav_items = [
            ['url' => 'admin/dashboard.php',            'icon' => 'bi-grid-fill',             'label' => __t('dashboard')],
            ['url' => 'admin/liste_opportunites.php',   'icon' => 'bi-briefcase-fill',        'label' => __t('opportunities')],
            ['url' => 'admin/liste_gerants.php',        'icon' => 'bi-building',              'label' => __t('Gérants')],
            ['url' => 'admin/liste_candidatures.php',   'icon' => 'bi-stack',                 'label' => __t('applications')],
            ['url' => 'admin/sessions_concours.php',    'icon' => 'bi-calendar-event-fill',   'label' => __t('Calendrier')],
            ['url' => 'admin/gestion_utilisateurs.php', 'icon' => 'bi-shield-lock-fill',      'label' => __t('Utilisateurs')],
            ['url' => 'admin/parametres.php',           'icon' => 'bi-gear-fill',             'label' => __t('Paramètres')],
            ['url' => 'admin/support.php',              'icon' => 'bi-headset',               'label' => 'Support'],
            ['url' => 'admin/modifier_profil.php',      'icon' => 'bi-person-circle',         'label' => __t('profile')],
        ];
    }

    $role_labels = ['candidat' => __t('Candidat'), 'gerant' => __t('Gérant'), 'admin' => __t('Administrateur')];
    $role_label = $role_labels[$role] ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo $current_lang; ?>" <?php echo $current_lang === 'ar' ? 'dir="rtl"' : ''; ?> data-bs-theme="light">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Admissio – Plateforme de recrutement et de gestion des candidatures">
        <title><?php echo htmlspecialchars($title); ?> – Admissio</title>
        <script>
            // Prevent flash of unstyled content for dark mode
            (function() {
                const savedTheme = localStorage.getItem('admissio_theme') || 'light';
                document.documentElement.setAttribute('data-bs-theme', savedTheme);
            })();
        </script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intro.js/minified/introjs.min.css">
        <script src="https://cdn.jsdelivr.net/npm/intro.js/minified/intro.min.js"></script>
        <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css?v=<?php echo time(); ?>">
        <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/elite_design.css?v=<?php echo time(); ?>">
    </head>
    <body>

    <?php 
    $is_index = (strpos($current_path, 'index.php') !== false);
    if ($role && !$is_index): // ── AUTHENTICATED LAYOUT (with sidebar) ── ?>

    <div class="admissio-layout">
        <!-- Sidebar Overlay (mobile) -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- ─── SIDEBAR ─── -->
        <aside class="admissio-sidebar" id="admissioSidebar">
            <!-- Brand -->
            <a href="<?php echo $base_path; ?>index.php" class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 7H4C2.9 7 2 7.9 2 9V19C2 20.1 2.9 21 4 21H20C21.1 21 22 20.1 22 19V9C22 7.9 21.1 7 20 7Z" fill="white" opacity=".9"/>
                        <path d="M16 7V5C16 3.9 15.1 3 14 3H10C8.9 3 8 3.9 8 5V7" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="sidebar-brand-name">Admissio</span>
            </a>

            <!-- User Card -->
            <div class="sidebar-user">
                <div class="sidebar-user-avatar"><?php echo $user_initials; ?></div>
                <div>
                    <div class="sidebar-user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="sidebar-user-role"><?php echo $role_label; ?></div>
                </div>
            </div>

            <!-- Navigation -->
            <span class="sidebar-section-label"><?php echo __t('Navigation'); ?></span>
            <nav class="sidebar-nav">
                <?php foreach ($nav_items as $item):
                    $is_active = strpos($current_path, $item['url']) !== false;
                ?>
                    <a href="<?php echo $base_path . $item['url']; ?>"
                       class="sidebar-link <?php echo $is_active ? 'active' : ''; ?>">
                        <i class="bi <?php echo $item['icon']; ?>"></i>
                        <?php echo $item['label']; ?>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="sidebar-badge sidebar-notif-count" style="display:none;">0</span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Footer -->
            <div class="sidebar-footer">
                <a href="<?php echo $base_path; ?>auth/logout.php" class="sidebar-logout">
                    <i class="bi bi-box-arrow-left"></i>
                    <?php echo __t('logout'); ?>
                </a>
            </div>
        </aside>

        <!-- ─── MAIN CONTENT ─── -->
        <div class="admissio-main">
            <!-- Topbar -->
            <div class="admissio-topbar">
                <div class="topbar-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    <div>
                        <div class="topbar-title"><?php echo htmlspecialchars(__t($title) ?: $title); ?></div>
                        <div class="topbar-subtitle">
                            <?php 
                                // Formatage de la date selon la langue
                                if ($current_lang === 'en') {
                                    echo date('l, F j, Y');
                                } else {
                                    // Simplicité pour le français (PHP date 'l' est en anglais par défaut)
                                    setlocale(LC_TIME, 'fr_FR.UTF8', 'fra');
                                    echo date('d/m/Y'); 
                                }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="topbar-right">
                    <!-- Theme Toggle -->
                    <button type="button" class="theme-toggle-btn btn btn-sm text-gray-700 bg-gray-100 border-0 rounded-circle me-3" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Mode Sombre/Clair" onclick="toggleTheme()">
                        <i class="bi bi-moon-fill"></i>
                    </button>
                    <!-- Language Switcher -->
                    <div class="dropdown me-3">
                        <button class="btn btn-sm dropdown-toggle fw-bold text-gray-700 bg-gray-100 border-0 rounded-pill px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-translate me-1"></i> <?php echo strtoupper($current_lang); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 mt-2">
                            <li><a class="dropdown-item py-2 fw-bold <?php echo $current_lang === 'fr' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_lang_url('fr')); ?>">🇫🇷 Français</a></li>
                            <li><a class="dropdown-item py-2 fw-bold <?php echo $current_lang === 'en' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_lang_url('en')); ?>">🇺🇸 English</a></li>
                            <li><a class="dropdown-item py-2 fw-bold <?php echo $current_lang === 'ar' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_lang_url('ar')); ?>">🇦🇪 العربية</a></li>
                        </ul>
                    </div>
                    <?php if ($role === 'candidat'): ?>
                    <a href="<?php echo $base_path; ?>candidat/notifications.php" class="topbar-btn me-2" title="Notifications">
                        <i class="bi bi-bell"></i>
                        <span class="notif-dot d-none" id="topbar-notif-dot"></span>
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo $base_path . $role; ?>/modifier_profil.php" class="topbar-avatar" title="Mon profil">
                        <?php if ($user_photo): ?>
                            <img src="<?php echo $base_path; ?>uploads/<?php echo htmlspecialchars($user_photo); ?>" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        <?php else: ?>
                            <?php echo $user_initials; ?>
                        <?php endif; ?>
                    </a>
                </div>
            </div>

            <!-- Page Content Wrapper -->
            <main class="admissio-content">
                <?php render_alerts(); ?>

    <?php else: // ── PUBLIC LAYOUT (with top navbar) ── ?>

    <!-- Public Navbar classique -->
    <nav class="public-navbar">
        <div class="container-modern" style="display:flex;align-items:center;justify-content:space-between;">
            <a href="<?php echo $base_path; ?>index.php" class="navbar-brand-admissio">
                <div class="brand-logo-dot">A</div>
                Admissio
            </a>
            <ul class="pub-nav-links d-none d-lg-flex">
                <li><a href="<?php echo $base_path; ?>index.php"><?php echo __t('Accueil'); ?></a></li>
                <li><a href="<?php echo $base_path; ?>index.php#pourquoi"><?php echo __t('Plateforme'); ?></a></li>
                <li><a href="<?php echo $base_path; ?>index.php#opportunites"><?php echo __t('Opportunités'); ?></a></li>
            </ul>
            <div class="d-flex gap-2 align-items-center">
                <!-- Theme Toggle Public -->
                <button type="button" class="theme-toggle-btn btn btn-sm text-gray-700 bg-gray-100 border-0 rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Mode Sombre/Clair" onclick="toggleTheme()">
                    <i class="bi bi-moon-fill"></i>
                </button>
                <a href="<?php echo $base_path; ?>choix_connexion.php" class="btn-pill btn-pill-outline px-3 py-2" style="font-size:0.9rem;"><?php echo __t('Se connecter'); ?></a>
            </div>
        </div>
    </nav>
    <main class="container-modern py-4">
        <?php render_alerts(); ?>

    <?php endif; ?>
    <?php
}

/**
 * Inclusion du Footer HTML
 */
function include_footer(): void {
    $base_path = PROJECT_PATH;
    $role = $_SESSION['role'] ?? null;
    ?>
    <?php if ($role): ?>
            </main><!-- /.admissio-content -->
            
            <!-- Footer interne pour utilisateurs connectés -->
            <footer style="background: rgba(0,0,0,0.02); border-top: 1px solid rgba(0,0,0,0.08); padding: 2rem 0; margin-top: 2rem; text-align: center; font-size: 0.85rem; color: #666;">
                <div class="container-modern">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-md-4">
                            <div style="font-weight:600; color:#333;">Admissio</div>
                            <div style="font-size:0.8rem;"><?php echo __t('Plateforme de recrutement'); ?></div>
                        </div>
                        <div class="col-md-4 py-3 py-md-0">
                            <div style="font-weight:600; color:#333;"><?php echo __t('Support & Contact'); ?></div>
                            <div style="margin-top:0.5rem;"><i class="bi bi-telephone-fill" style="color:#2563eb;"></i> Mauritel: <strong>42519122</strong></div>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div>© <?php echo date('Y'); ?> Admissio</div>
                            <div style="font-size:0.8rem;"><?php echo __t('Tous droits réservés'); ?></div>
                        </div>
                    </div>
                </div>
            </footer>
        </div><!-- /.admissio-main -->
    </div><!-- /.admissio-layout -->

    <?php else: ?>
    </main>

    <!-- Public Footer -->
    <footer style="background: #ffffff; color: #64748b; border-top: 1px solid rgba(0,0,0,0.08); padding: 3rem 0 1.5rem;">
        <div class="container-modern">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div>
                    <div style="font-family:var(--font-heading); font-weight:900; font-size:1.25rem; color:#0f5132; margin-bottom:.25rem;">Admissio</div>
                    <div style="font-size:.85rem;"><?php echo __t('La plateforme de recrutement d\'excellence.'); ?></div>
                </div>
                <div style="font-size:.85rem;">© <?php echo date('Y'); ?> Admissio. <?php echo __t('Tous droits réservés'); ?>.</div>
            </div>
        </div>
    </footer>

    <?php endif; ?>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;" id="admissioToastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo $base_path; ?>assets/js/main.js"></script>
    <script>
    const PROJECT_PATH = '<?= PROJECT_PATH ?>';
    function showToast(message, title = "Notification") {
        const container = document.getElementById('admissioToastContainer');
        if (!container) return;
        const id = 'toast-' + Date.now();
        const html = `
            <div id="${id}" class="toast align-items-center text-white bg-success border-0 shadow-lg mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <div class="fw-bold mb-1"><i class="bi bi-bell-fill me-1"></i> ${title}</div>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        const toastEl = document.getElementById(id);
        const bsToast = new bootstrap.Toast(toastEl, { delay: 5000 });
        bsToast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    // Elite Alert System using SweetAlert2
    function showAlert(message, type = 'success') {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: type,
            title: message
        });
    }

    // Detect session alerts from DOM and show SweetAlert2 version too if desired
    document.addEventListener('DOMContentLoaded', () => {
        const successAlert = document.querySelector('.alert-success');
        const errorAlert = document.querySelector('.alert-danger');
        
        if (successAlert) {
            const msg = successAlert.querySelector('.small')?.textContent || successAlert.textContent;
            showAlert(msg.trim(), 'success');
        }
        if (errorAlert) {
            const msg = errorAlert.querySelector('.small')?.textContent || errorAlert.textContent;
            showAlert(msg.trim(), 'error');
        }
    });

    function toggleTheme() {
        const html = document.documentElement;
        let newTheme = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('admissio_theme', newTheme);
        
        document.querySelectorAll('.theme-toggle-btn i').forEach(i => {
            if(newTheme === 'dark') {
                i.classList.remove('bi-moon-fill');
                i.classList.add('bi-sun-fill');
            } else {
                i.classList.remove('bi-sun-fill');
                i.classList.add('bi-moon-fill');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const currentTheme = localStorage.getItem('admissio_theme') || 'light';
        if(currentTheme === 'dark') {
            document.querySelectorAll('.theme-toggle-btn i').forEach(i => {
                i.classList.remove('bi-moon-fill');
                i.classList.add('bi-sun-fill');
            });
        }
    });

    // Sidebar toggle (mobile)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('admissioSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar && overlay) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
            overlay.classList.toggle('is-active');
        });
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-active');
        });
    }

    // Notifications count badge & real-time toasts
    let lastNotifCount = -1;
    function loadNotifCount() {
        const badges = document.querySelectorAll('.sidebar-notif-count');
        const dotEl = document.getElementById('topbar-notif-dot');
        if (badges.length === 0 && !dotEl) return;
        
        fetch(PROJECT_PATH + 'includes/notification_ajax.php?action=count')
            .then(r => r.json())
            .then(d => {
                if (d && typeof d.count !== 'undefined') {
                    const currentCount = parseInt(d.count);
                    // If count increased, show a toast
                    if (lastNotifCount !== -1 && currentCount > lastNotifCount) {
                        fetch(PROJECT_PATH + 'includes/notification_ajax.php?action=latest')
                            .then(r => r.json())
                            .then(notif => {
                                if (notif && notif.message) {
                                    showToast(notif.message, "Nouveau message");
                                }
                            });
                    }
                    lastNotifCount = currentCount;
                    
                    if (currentCount > 0) {
                        badges.forEach(b => { b.textContent = currentCount; b.style.display = 'block'; });
                        if (dotEl) dotEl.classList.remove('d-none');
                    } else {
                        badges.forEach(b => { b.style.display = 'none'; });
                        if (dotEl) dotEl.classList.add('d-none');
                    }
                }
            }).catch(() => {});
    }
    loadNotifCount();
    setInterval(loadNotifCount, 5000); // Poll every 5 seconds

    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        input.type = (input.type === 'password') ? 'text' : 'password';
        btn.innerHTML = (input.type === 'password') ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    }
    </script>
    </body>
    </html>
    <?php
}


