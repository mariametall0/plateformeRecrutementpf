<?php
declare(strict_types=1);
require_once "config/database.php";
require_once "config/session.php";
require_once "includes/functions.php";

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$current_lang = $_SESSION['lang'] ?? 'fr';
require_once "includes/translations.php";

$stats = ['candidats' => 210, 'offres' => 45];
$offres = [];
try {
    global $pdo;
    require_once "config/database.php";
    
    // Stats : Uniquement les concours actifs ET non expirés
    $s = $pdo->prepare("SELECT (SELECT COUNT(*) FROM utilisateurs WHERE role='candidat') as c, (SELECT COUNT(*) FROM concours WHERE statut='actif' AND date_ouverture <= CURDATE() AND date_cloture >= CURDATE()) as o");
    $s->execute();
    $r = $s->fetch();
    if ($r) { $stats['candidats'] = (int)$r['c']; $stats['offres'] = (int)$r['o']; }
    
    // Offres Hero (3 dernières) : Actives ET non expirées
    $s2 = $pdo->prepare("SELECT c.titre, u.nom FROM concours c JOIN utilisateurs u ON c.id_gerant=u.id WHERE c.statut='actif' AND c.date_ouverture <= CURDATE() AND c.date_cloture >= CURDATE() ORDER BY c.date_creation DESC LIMIT 3");
    $s2->execute(); $offres_hero = $s2->fetchAll();
    
    // Toutes les offres pour la recherche : Actives ET non expirées
    $s3 = $pdo->prepare("SELECT c.titre, u.nom, COALESCE(c.secteur, e.secteur_activite) as secteur_activite, c.date_ouverture, c.date_cloture FROM concours c JOIN utilisateurs u ON c.id_gerant=u.id LEFT JOIN entreprises e ON u.id=e.id_utilisateur WHERE c.statut='actif' AND c.date_ouverture <= CURDATE() AND c.date_cloture >= CURDATE() ORDER BY c.date_creation DESC");
    $s3->execute(); $toutes_offres = $s3->fetchAll();
    
    // Récupérer TOUS les secteurs d'activité existants pour nourrir le filtre (même sans offre active)
    $s4 = $pdo->prepare("SELECT DISTINCT secteur_activite FROM entreprises WHERE secteur_activite IS NOT NULL AND secteur_activite != ''");
    $s4->execute();
    $secteurs = $s4->fetchAll(PDO::FETCH_COLUMN);
    
    // Ajout de quelques secteurs par défaut si la liste est trop courte (pour un rendu pro PFE)
    $defauts = ["Informatique", "Commerce", "Santé", "Éducation", "BTP", "Industrie"];
    foreach($defauts as $d) {
        if(!in_array($d, $secteurs)) $secteurs[] = $d;
    }
    
    sort($secteurs);
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admissio – Plateforme de Recrutement Moderne</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ════════════════════════
   RESET & BASE
════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --blue:    #0f5132;
    --blue-l:  #20c997;
    --green:   #198754;
    --cyan:    #0dcaf0;
    --bg:      #f0fdf4; /* Mint très clair comme dans la vidéo */
    --white:   #ffffff;
    --dark:    #0f172a;
    --muted:   #64748b;
    --border:  rgba(0,0,0,0.07);
    --bs-primary: #198754;
    --bs-primary-rgb: 25, 135, 84;
}
html, body {
    font-family: 'Inter', sans-serif;
    background: var(--white);
    color: var(--dark);
    overflow-x: hidden;
}

/* ════════════════════════
   NAVBAR
════════════════════════ */
.nav-admissio {
    position: sticky;
    top: 0; z-index: 999;
    background: rgba(255,255,255,0.88);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    padding: 14px 0;
}
.nav-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
}
.brand {
    display: flex; align-items: center; gap: 10px;
    text-decoration: none;
    font-weight: 900; font-size: 1.3rem; color: var(--blue);
}
.brand-icon {
    width: 32px; height: 32px;
    background: var(--blue);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 14px; font-weight: 900;
}
.nav-links {
    display: flex; gap: 2rem; list-style: none;
}
.nav-links a {
    color: var(--muted); text-decoration: none;
    font-size: .95rem; font-weight: 500;
    transition: color .2s;
}
.nav-links a:hover { color: var(--dark); }
.nav-actions { display: flex; gap: 10px; }

/* Bouton Pilule */
.btn-pill {
    display: inline-flex; align-items: center; gap: 8px;
    border-radius: 100px;
    font-weight: 700; font-size: .9rem;
    padding: 10px 22px;
    text-decoration: none;
    cursor: pointer; border: none;
    transition: all .25s cubic-bezier(.25,.46,.45,.94);
    white-space: nowrap;
    line-height: 1;
}
.btn-blue { background: var(--blue); color: #fff; box-shadow: 0 4px 14px rgba(15,81,50,.3); }
.btn-blue:hover { background: #0a3622; color: #fff; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(15,81,50,.4); }
.btn-ghost { background: transparent; color: var(--dark); border: 1.5px solid var(--border); }
.btn-ghost:hover { background: #f8fafc; color: var(--dark); transform: translateY(-2px); }
.btn-green { background: var(--green); color: #fff; box-shadow: 0 4px 14px rgba(25,135,84,.3); }
.btn-green:hover { background: #146c43; color: #fff; transform: translateY(-2px); }

/* ════════════════════════
   HERO SECTION
════════════════════════ */
.hero {
    position: relative;
    overflow: hidden;
    padding: 100px 0 80px;
    background: linear-gradient(160deg, #f0fdf4 0%, #d1fae5 50%, #ecfeff 100%);
}
/* Blobs Aurora */
.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
}
.blob-1 { width: 700px; height: 700px; background: radial-gradient(circle, rgba(15,81,50,.2) 0%, transparent 70%); top: -200px; right: -200px; animation: drift 20s ease-in-out infinite alternate; }
.blob-2 { width: 500px; height: 500px; background: radial-gradient(circle, rgba(13,202,240,.2) 0%, transparent 70%); bottom: -150px; left: -100px; animation: drift 15s ease-in-out infinite alternate-reverse; }
.blob-3 { width: 400px; height: 400px; background: radial-gradient(circle, rgba(25,135,84,.12) 0%, transparent 70%); top: 30%; left: 40%; animation: drift 18s ease-in-out infinite alternate; }
@keyframes drift {
    0% { transform: translate(0,0) scale(1); }
    100% { transform: translate(60px, 40px) scale(1.08); }
}

.hero-inner {
    max-width: 1200px;
    margin: 0 auto; padding: 0 2rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: center;
    gap: 5rem;
    position: relative; z-index: 1;
}

/* Badge Pill */
.hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(15,81,50,.08);
    color: var(--blue);
    border-radius: 100px;
    padding: 6px 16px;
    font-weight: 600; font-size: .82rem;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(15,81,50,.15);
}
.pulse-dot {
    width: 8px; height: 8px;
    background: var(--blue);
    border-radius: 50%;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(15,81,50,.5); }
    50% { box-shadow: 0 0 0 5px rgba(15,81,50,0); }
}

.hero h1 {
    font-size: 3.5rem; font-weight: 900;
    line-height: 1.1; letter-spacing: -2px;
    color: var(--dark); margin-bottom: 1.5rem;
}
.hero h1 .grad {
    background: linear-gradient(135deg, var(--blue), var(--cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.hero-sub {
    font-size: 1.1rem; color: var(--muted);
    line-height: 1.7; max-width: 480px;
    margin-bottom: 2.5rem;
}
.hero-cta { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 2rem; }
.hero-trust {
    display: flex; gap: 1.5rem; flex-wrap: wrap;
}
.trust-item {
    display: flex; align-items: center; gap: 6px;
    font-size: .84rem; color: var(--muted); font-weight: 500;
}

/* Carte flottante Droite */
.hero-card {
    background: rgba(255,255,255,.75);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(255,255,255,.9);
    border-radius: 28px;
    padding: 2.5rem;
    box-shadow: 0 30px 80px rgba(15,81,50,.1), 0 1px 0 rgba(255,255,255,.8) inset;
    animation: float-y 7s ease-in-out infinite;
}
@keyframes float-y {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-14px); }
}
.hero-card-header {
    display: flex; align-items: center; gap: 14px;
    padding-bottom: 1.25rem; margin-bottom: 1.25rem;
    border-bottom: 1px solid var(--border);
}
.card-avatar {
    width: 48px; height: 48px; border-radius: 14px;
    background: linear-gradient(135deg, var(--blue), var(--blue-l));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.2rem;
}
.status-dot {
    width: 10px; height: 10px;
    background: var(--green);
    border-radius: 50%;
    display: inline-block;
    animation: pulse-green 2s infinite;
}
@keyframes pulse-green {
    0%,100% { box-shadow: 0 0 0 0 rgba(25,135,84,.5); }
    50% { box-shadow: 0 0 0 5px rgba(25,135,84,0); }
}
.stat-row {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    margin-bottom: 1.25rem;
}
.stat-box {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 14px 18px;
    display: flex; gap: 12px; align-items: center;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}
.stat-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
}
.stat-num { font-size: 1.4rem; font-weight: 900; line-height: 1; }
.stat-lbl { font-size: .72rem; color: var(--muted); font-weight: 600; }
.offer-row {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border);
}
.offer-row:last-child { border-bottom: none; }
.offer-icon {
    width: 30px; height: 30px;
    border-radius: 8px;
    background: rgba(15,81,50,.08);
    color: var(--blue); font-size: .75rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.offer-title { font-size: .83rem; font-weight: 600; color: var(--dark); }

/* ════════════════════════
   SECTION "Pourquoi ?"
════════════════════════ */
.section-tag {
    display: inline-flex; align-items: center; gap: 6px;
    background: #f8fafc; border: 1px solid var(--border);
    border-radius: 100px; padding: 5px 14px;
    font-size: .8rem; font-weight: 600;
    color: var(--muted); margin-bottom: 1rem;
}
.section-title { font-size: 2.5rem; font-weight: 800; letter-spacing: -1px; color: var(--dark); }

.portal-card {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: 24px;
    padding: 2.5rem;
    box-shadow: 0 10px 40px rgba(0,0,0,.05);
    transition: transform .3s ease, box-shadow .3s ease;
    height: 100%;
}
.portal-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 60px rgba(15,81,50,.1);
}
.portal-icon-wrap {
    width: 64px; height: 64px;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 1.5rem;
}

/* <?php echo __t('process_nav'); ?> */
.step-circle {
    width: 56px; height: 56px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
    font-weight: 800;
    margin: 0 auto 1rem;
    transition: all .3s;
}
.step-wrap:hover .step-circle { transform: scale(1.1); }

/* Reveal animation */
.reveal {
    opacity: 0; transform: translateY(30px);
    transition: opacity .8s ease, transform .8s ease;
}
.reveal.visible { opacity: 1; transform: translateY(0); }

/* ════════════════════════
   FOOTER
════════════════════════ */
.footer-clean {
    background: #f8fafc;
    border-top: 1px solid var(--border);
    padding: 2.5rem 0;
}

/* Responsive */
@media (max-width: 768px) {
    .hero-inner { grid-template-columns: 1fr; gap: 3rem; }
    .hero h1 { font-size: 2.2rem; }
    .nav-links, .btn-ghost { display: none; }
    .hero { padding: 60px 0 50px; }
}

/* Bootstrap Overrides */
.dropdown-item.active, .dropdown-item:active {
    background-color: var(--green) !important;
    color: #fff !important;
}
</style>
</head>
<body>

<!-- ══════════ NAVBAR ══════════ -->
<nav class="nav-admissio">
    <div class="nav-inner">
        <a href="<?= PROJECT_PATH ?>index.php" class="brand">
            <div class="brand-icon">A</div>
            Admissio
        </a>
        <ul class="nav-links d-none d-lg-flex">
            <li><a href="#pourquoi"><?php echo __t('about_nav'); ?></a></li>
            <li><a href="#processus"><?php echo __t('process_nav'); ?></a></li>
            <li><a href="#offres"><?php echo __t('offers_nav'); ?></a></li>
            <li><a href="#contact"><?php echo __t('contact_nav'); ?></a></li>
        </ul>
        <div class="nav-actions align-items-center">
            <div class="dropdown me-3">
                <a class="nav-link dropdown-toggle text-dark fw-bold" href="#" data-bs-toggle="dropdown" style="text-decoration:none; font-size:.9rem;">
                    <i class="bi bi-translate me-1"></i> <?php echo strtoupper($current_lang); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                    <li><a class="dropdown-item py-2 fw-bold <?php echo $current_lang === 'fr' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_lang_url('fr')); ?>">🇫🇷 Français</a></li>
                    <li><a class="dropdown-item py-2 fw-bold <?php echo $current_lang === 'en' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_lang_url('en')); ?>">🇺🇸 English</a></li>
                    <li><a class="dropdown-item py-2 fw-bold <?php echo $current_lang === 'ar' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars(build_lang_url('ar')); ?>">🇦🇪 العربية</a></li>
                </ul>
            </div>
            <a href="<?= PROJECT_PATH ?>choix_connexion.php" class="btn-pill btn-ghost d-none d-sm-flex"><?php echo __t('Se connecter'); ?></a>
            <a href="<?= PROJECT_PATH ?>choix_inscription.php" class="btn-pill btn-blue"><?php echo __t('S\'inscrire'); ?> <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</nav>

<!-- ══════════ HERO ══════════ -->
<section class="hero">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="hero-inner">
        <!-- GAUCHE -->
        <div>
            <div class="hero-badge">
                <span class="pulse-dot"></span>
                <?php echo __t('certified_platform'); ?>
            </div>
            <h1><?php echo __t('hero_title'); ?></h1>
            <p class="hero-sub"><?php echo __t('hero_subtitle'); ?></p>
            <div class="hero-cta">
                <a href="<?= PROJECT_PATH ?>choix_inscription.php" class="btn-pill btn-blue" style="font-size:1rem; padding: 14px 30px;">
                    <?php echo __t('start_experience'); ?> <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="hero-trust">
                <div class="trust-item"><i class="bi bi-shield-check-fill" style="color:var(--green);"></i> <?php echo __t('no_credit_card'); ?></div>
                <div class="trust-item"><i class="bi bi-lock-fill" style="color:var(--green);"></i> <?php echo __t('secure_data'); ?></div>
                <div class="trust-item"><i class="bi bi-lightning-charge-fill" style="color: #ffc107;"></i> <?php echo __t('instant_matching'); ?></div>
            </div>
        </div>

        <!-- DROITE : Carte flottante -->
        <div class="d-none d-lg-block">
            <div class="hero-card">
                <div class="hero-card-header">
                    <div class="card-avatar"><i class="bi bi-graph-up-arrow"></i></div>
                    <div>
                        <div style="font-weight:700; font-size:1rem;"><?php echo __t('dashboard'); ?></div>
                        <div style="font-size:.8rem; color:var(--muted);"><?php echo __t('active_network'); ?></div>
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <span class="status-dot"></span>
                        <span style="font-size:.8rem; font-weight:600; color:var(--green);"><?php echo __t('online'); ?></span>
                    </div>
                </div>

                <div class="stat-row">
                    <div class="stat-box">
                        <div class="stat-icon" style="background:rgba(15,81,50,.08); color:var(--blue);"><i class="bi bi-briefcase-fill"></i></div>
                        <div><div class="stat-num" style="color:var(--blue);"><?php echo $stats['offres']; ?>+</div><div class="stat-lbl"><?php echo __t('offers_nav'); ?> actives</div></div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-icon" style="background:rgba(25,135,84,.08); color:var(--green);"><i class="bi bi-people-fill"></i></div>
                        <div><div class="stat-num" style="color:var(--green);"><?php echo $stats['candidats']; ?>+</div><div class="stat-lbl"><?php echo __t('Candidats'); ?></div></div>
                    </div>
                </div>

                <div style="font-size:.75rem; font-weight:700; color:var(--muted); text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px;"><?php echo __t('latest_opportunities'); ?></div>
                <?php if (!empty($offres_hero)): foreach ($offres_hero as $o): ?>
                <div class="offer-row">
                    <div class="offer-icon"><i class="bi bi-building"></i></div>
                    <div class="offer-title"><?php echo htmlspecialchars((string)$o['titre']); ?></div>
                    <span class="ms-auto" style="background:rgba(25,135,84,.1); color:var(--green); font-size:.72rem; font-weight:700; padding:3px 10px; border-radius:100px;"><?php echo __t("active"); ?></span>
                </div>
                <?php endforeach; else: ?>
                <div style="color:var(--muted); font-size:.85rem; text-align:center; padding:1rem 0;"><?php echo __t('no_offers_moment'); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ POURQUOI ADMISSIO ══════════ -->
<section id="pourquoi" style="padding: 100px 0; background: #fff;">
    <div style="max-width:1200px; margin:0 auto; padding:0 2rem;">
        
        <!-- En-tête de section -->
        <div class="text-center reveal" style="margin-bottom:4rem;">
            <div class="section-tag"><i class="bi bi-stars"></i> <?php echo __t('our_platform'); ?></div>
            <h2 class="section-title"><?php echo __t('why_choose_admissio'); ?></h2>
            <p style="color:var(--muted); font-size:1.05rem; max-width:650px; margin:.75rem auto 0; line-height:1.7;">
                <?php echo __t('discover_modern_platform'); ?>
            </p>
        </div>

        <!-- PRÉSENTATION COMPLÈTE DE LA PLATEFORME (À PROPOS) -->
        <div class="row g-5 align-items-center mb-5 reveal">
            <!-- Colonne Gauche : Texte de présentation d'Admissio -->
            <div class="col-lg-6">
                <h3 style="font-weight: 800; font-size: 1.8rem; color: var(--dark); margin-bottom: 1.5rem;">
                    <?php echo __t('modern_bridge'); ?>
                </h3>
                <p style="color: var(--muted); font-size: 1rem; line-height: 1.8; margin-bottom: 1.5rem; text-align: justify;">
                    <?php echo __t('admissio_desc'); ?>
                </p>
                
                <!-- Points clés -->
                <div class="d-flex flex-column gap-3 mb-2">
                    <div class="d-flex align-items-start gap-3">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(15,81,50,.08); color: var(--blue); display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="bi bi-folder-check"></i></div>
                        <div>
                            <strong style="color: var(--dark);"><?php echo __t('zero_data_loss'); ?></strong>
                            <p style="color: var(--muted); font-size: 0.9rem; margin: 0;"><?php echo __t('complete_centralization'); ?></p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(25,135,84,.08); color: var(--green); display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="bi bi-bell"></i></div>
                        <div>
                            <strong style="color: var(--dark);"><?php echo __t('real_time_tracking'); ?></strong>
                            <p style="color: var(--muted); font-size: 0.9rem; margin: 0;"><?php echo __t('instant_notifications'); ?></p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(13,202,240,.1); color: var(--cyan); display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><i class="bi bi-lightning-charge"></i></div>
                        <div>
                            <strong style="color: var(--dark);"><?php echo __t('simplicity_ergonomics'); ?></strong>
                            <p style="color: var(--muted); font-size: 0.9rem; margin: 0;"><?php echo __t('clean_interface'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Colonne Droite : Visuel interactif moderne représentant un suivi de dossier -->
            <div class="col-lg-6">
                <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1.5px solid var(--border); border-radius: 24px; padding: 2.5rem; box-shadow: 0 15px 35px rgba(0,0,0,0.03);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem;">
                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: #ef4444;"></span>
                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: #eab308;"></span>
                        <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background: #22c55e;"></span>
                        <span style="margin-left: auto; font-size: 0.8rem; font-weight: 700; color: var(--muted); text-transform: uppercase;"><i class="bi bi-window me-1"></i> <?php echo __t('candidate_preview'); ?></span>
                    </div>
                    
                    <div style="background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
                        <div style="font-weight: 800; font-size: 1rem; color: var(--dark); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-bezier2" style="color: var(--blue);"></i> <?php echo __t('track_application'); ?>
                        </div>
                        
                        <div style="position: relative; padding-left: 28px;">
                            <!-- Ligne verticale -->
                            <div style="position: absolute; left: 8px; top: 8px; bottom: 8px; width: 2px; background: #e2e8f0;"></div>
                            
                            <!-- Étape 1 -->
                            <div style="position: relative; margin-bottom: 1.25rem;">
                                <div style="position: absolute; left: -26px; top: 2px; width: 14px; height: 14px; border-radius: 50%; background: var(--green); display: flex; align-items: center; justify-content: center; color: white; font-size: 8px;"><i class="bi bi-check"></i></div>
                                <div style="font-weight: 700; font-size: 0.85rem; color: var(--dark);"><?php echo __t('application_submitted'); ?></div>
                                <div style="font-size: 0.75rem; color: var(--muted);"><?php echo __t('profile_documents_saved'); ?></div>
                            </div>
                            
                            <!-- Étape 2 -->
                            <div style="position: relative; margin-bottom: 1.25rem;">
                                <div style="position: absolute; left: -26px; top: 2px; width: 14px; height: 14px; border-radius: 50%; background: var(--green); display: flex; align-items: center; justify-content: center; color: white; font-size: 8px;"><i class="bi bi-check"></i></div>
                                <div style="font-weight: 700; font-size: 0.85rem; color: var(--dark);"><?php echo __t('information_verification'); ?></div>
                                <div style="font-size: 0.75rem; color: var(--muted);"><?php echo __t('application_validated'); ?></div>
                            </div>
                            
                            <!-- Étape 3 -->
                            <div style="position: relative;">
                                <div style="position: absolute; left: -28px; top: 0; width: 18px; height: 18px; border-radius: 50%; background: rgba(15,81,50,.15); display: flex; align-items: center; justify-content: center;">
                                    <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--blue); animation: pulse 2s infinite;"></div>
                                </div>
                                <div style="font-weight: 700; font-size: 0.85rem; color: var(--blue);"><?php echo __t('interview_planning'); ?></div>
                                <div style="font-size: 0.75rem; color: var(--muted);"><?php echo __t('interview_email_date'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border); margin: 4.5rem 0;">

        <!-- LES DEUX PORTAILS D'ACCÈS -->
        <div class="row g-4 justify-content-center">
            <div class="col-md-5 reveal" style="transition-delay:.1s">
                <div class="portal-card">
                    <div class="portal-icon-wrap" style="background:rgba(15,81,50,.08); color:var(--blue);"><i class="bi bi-person-bounding-box"></i></div>
                    <h4 style="font-weight:800; margin-bottom:.75rem;"><?php echo __t('candidate_space'); ?></h4>
                    <p style="color:var(--muted); font-size:.93rem; line-height:1.7; margin-bottom:2rem;"><?php echo __t('candidate_space_desc'); ?></p>
                    <a href="<?= PROJECT_PATH ?>auth/login.php?role=candidat" class="btn-pill btn-blue" style="width:100%; justify-content:center;"><?php echo __t('access_my_space'); ?> <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            <div class="col-md-5 reveal" style="transition-delay:.2s">
                <div class="portal-card">
                    <div class="portal-icon-wrap" style="background:rgba(25,135,84,.08); color:var(--green);"><i class="bi bi-buildings"></i></div>
                    <h4 style="font-weight:800; margin-bottom:.75rem;"><?php echo __t('company_space'); ?></h4>
                    <p style="color:var(--muted); font-size:.93rem; line-height:1.7; margin-bottom:2rem;"><?php echo __t('company_space_desc'); ?></p>
                    <a href="<?= PROJECT_PATH ?>auth/login.php?role=gerant" class="btn-pill btn-green" style="width:100%; justify-content:center;"><?php echo __t('recruiter_space'); ?> <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ PROCESSUS ══════════ -->
<section id="processus" style="padding:100px 0; background:linear-gradient(160deg, #f0fdf4 0%, #d1fae5 100%);">
    <div style="max-width:1200px; margin:0 auto; padding:0 2rem;">
        <div class="text-center reveal" style="margin-bottom:4rem;">
            <div class="section-tag"><i class="bi bi-diagram-3"></i> <?php echo __t('simple_and_fast'); ?></div>
            <h2 class="section-title"><?php echo __t('recruitment_4_steps'); ?></h2>
        </div>
        <div class="row g-4 text-center reveal">
            <?php
            $steps = [
                ['n'=>'1','icon'=>'bi-person-plus','bg'=>'rgba(15,81,50,.08)','c'=>'var(--blue)','t'=>__t('registration'),'d'=>__t('create_account_desc')],
                ['n'=>'2','icon'=>'bi-search','bg'=>'rgba(13,202,240,.1)','c'=>'#0dcaf0','t'=>__t('exploration'),'d'=>__t('browse_offers_desc')],
                ['n'=>'3','icon'=>'bi-send-check','bg'=>'rgba(15,81,50,.08)','c'=>'var(--blue)','t'=>__t('application_step'),'d'=>__t('submit_application_desc')],
                ['n'=>'4','icon'=>'bi-trophy','bg'=>'rgba(25,135,84,.1)','c'=>'var(--green)','t'=>__t('validation'),'d'=>__t('land_interview_desc')],
            ];
            foreach ($steps as $s): ?>
            <div class="col-md-3 step-wrap">
                <div class="step-circle" style="background:<?php echo $s['bg']; ?>; color:<?php echo $s['c']; ?>;"><?php echo $s['n']; ?></div>
                <div style="font-size:1.75rem; color:<?php echo $s['c']; ?>; margin-bottom:.75rem;"><i class="bi <?php echo $s['icon']; ?>"></i></div>
                <h6 style="font-weight:800; color:var(--dark); margin-bottom:.5rem;"><?php echo $s['t']; ?></h6>
                <p style="color:var(--muted); font-size:.88rem; line-height:1.6;"><?php echo $s['d']; ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ══════════ OPPORTUNITÉS ══════════ -->
<?php if (!empty($toutes_offres)): ?>
<section id="offres" style="padding:100px 0; background:#fff;">
    <div style="max-width:1200px; margin:0 auto; padding:0 2rem;">
        <div style="display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-end; gap:20px; margin-bottom:3rem;" class="reveal">
            <div>
                <div class="section-tag"><i class="bi bi-briefcase"></i> <?php echo __t('all_our_opportunities'); ?></div>
                <h2 class="section-title" style="margin:0;"><?php echo __t('featured_opportunities_h2'); ?></h2>
            </div>
            <div style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
                <div style="background:#f8fafc; border:1px solid var(--border); border-radius:100px; padding:6px 15px; display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-funnel" style="color:var(--muted)"></i>
                    <select id="liveSearchCategory" style="border:none; outline:none; background:transparent; font-size:.9rem; color:var(--dark); cursor:pointer;" onchange="indexLiveSearch()">
                        <option value=""><?php echo __t('sectors_all'); ?></option>
                        <?php foreach($secteurs as $sec): ?>
                        <option value="<?php echo htmlspecialchars((string)$sec); ?>"><?php echo htmlspecialchars((string)$sec); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="background:#f8fafc; border:1px solid var(--border); border-radius:100px; padding:10px 20px; display:flex; align-items:center; gap:10px;">
                    <i class="bi bi-search" style="color:var(--muted)"></i>
                    <input type="text" id="liveSearchIndex" placeholder="<?php echo __t('search_placeholder_ex'); ?>" style="border:none; outline:none; background:transparent; font-size:.95rem; width:200px;" onkeyup="indexLiveSearch()">
                </div>
                <a href="<?= PROJECT_PATH ?>choix_connexion.php" class="btn-pill btn-ghost"><?php echo __t('register_login'); ?> <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="row g-4 reveal">
            <?php foreach ($toutes_offres as $o): ?>
            <div class="col-md-4 index-offer-item" data-category="<?php echo htmlspecialchars((string)($o['secteur_activite'] ?? '')); ?>">
                <div class="portal-card h-100">
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:1.5rem;">
                        <div style="width:42px;height:42px;border-radius:12px;background:rgba(15,81,50,.08);color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;"><i class="bi bi-building"></i></div>
                        <div>
                            <div class="index-offer-titre" style="font-weight:700;font-size:.95rem;color:var(--dark);"><?php echo htmlspecialchars((string)$o['titre']); ?></div>
                            <div class="index-offer-nom" style="font-size:.8rem;color:var(--muted);"><?php echo htmlspecialchars((string)$o['nom']); ?></div>
                        </div>
                        <span style="margin-left:auto;background:rgba(25,135,84,.1);color:var(--green);font-size:.75rem;font-weight:700;padding:4px 12px;border-radius:100px;"><?php echo __t("active"); ?></span>
                    </div>
                    <div style="background:#f8fafc; border-radius:12px; padding:12px; margin-bottom:1.5rem; display:flex; flex-direction:column; gap:8px;">
                        <div style="font-size: .8rem; color: var(--muted); display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-calendar-check text-blue"></i>
                            <span><?php echo __t('opened_on'); ?> <strong><?php echo date('d/m/Y', strtotime($o['date_ouverture'])); ?></strong></span>
                        </div>
                        <div style="font-size: .8rem; color: var(--muted); display: flex; align-items: center; gap: 8px;">
                            <i class="bi bi-calendar-x text-danger"></i>
                            <span><?php echo __t('expires_on'); ?> <strong><?php echo date('d/m/Y', strtotime($o['date_cloture'])); ?></strong></span>
                        </div>
                    </div>
                    <a href="<?= PROJECT_PATH ?>auth/login.php?role=candidat" class="btn-pill btn-blue mt-auto" style="width:100%;justify-content:center;"><?php echo __t('apply'); ?> <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════ CONTACT ══════════ -->
<section id="contact" style="padding: 100px 0; background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);">
    <div style="max-width:1200px; margin:0 auto; padding:0 2rem;">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 reveal">
                <div class="section-tag"><i class="bi bi-envelope"></i> <?php echo __t('contact_nav'); ?>ez-nous</div>
                <h2 class="section-title"><?php echo __t('question_team_here'); ?></h2>
                <p style="color:var(--muted); font-size:1.05rem; line-height:1.7; margin-bottom:2.5rem;"><?php echo __t('need_help_desc'); ?></p>
                
                <div class="d-flex flex-column gap-4">
                    <div class="d-flex align-items-center gap-4">
                        <div style="width:50px; height:50px; border-radius:15px; background:rgba(15,81,50,.08); color:var(--blue); display:flex; align-items:center; justify-content:center; font-size:1.2rem;"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <div style="font-weight:700; color:var(--dark);"><?php echo __t('our_headquarters'); ?></div>
                            <div style="color:var(--muted); font-size:.9rem;">Tevragh Zeina, Nouakchott, Mauritanie</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <div style="width:50px; height:50px; border-radius:15px; background:rgba(25,135,84,.08); color:var(--green); display:flex; align-items:center; justify-content:center; font-size:1.2rem;"><i class="bi bi-telephone"></i></div>
                        <div>
                            <div style="font-weight:700; color:var(--dark);"><?php echo __t('phone_number'); ?></div>
                            <div style="color:var(--muted); font-size:.9rem;">+222 45 25 10 10</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-4">
                        <div style="width:50px; height:50px; border-radius:15px; background:rgba(13,202,240,.1); color:var(--cyan); display:flex; align-items:center; justify-content:center; font-size:1.2rem;"><i class="bi bi-chat-dots"></i></div>
                        <div>
                            <div style="font-weight:700; color:var(--dark);"><?php echo __t('support_email'); ?></div>
                            <div style="color:var(--muted); font-size:.9rem;">contact@admissio.mr</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 reveal" style="transition-delay:.2s">
                <div class="glass-premium p-5 rounded-4 shadow-premium" style="background: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                    <form onsubmit="event.preventDefault(); alert('<?php echo __t('message_label'); ?> envoyé ! (Démonstration PFE)');">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2"><?php echo __t('full_name_label'); ?></label>
                                <input type="text" class="form-control" style="border-radius:12px; padding:12px 18px; border:1px solid #e2e8f0; font-size:.95rem;" placeholder="<?php echo __t('moussa_ahmed'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2"><?php echo __t('email_label'); ?></label>
                                <input type="email" class="form-control" style="border-radius:12px; padding:12px 18px; border:1px solid #e2e8f0; font-size:.95rem;" placeholder="<?php echo __t('moussa_mail'); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2"><?php echo __t('subject_label'); ?></label>
                                <input type="text" class="form-control" style="border-radius:12px; padding:12px 18px; border:1px solid #e2e8f0; font-size:.95rem;" placeholder="<?php echo __t('info_request'); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted text-uppercase mb-2"><?php echo __t('message_label'); ?></label>
                                <textarea class="form-control" style="border-radius:12px; padding:12px 18px; border:1px solid #e2e8f0; font-size:.95rem;" rows="4" placeholder="<?php echo __t('your_message_here'); ?>"></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn-pill btn-blue w-100 justify-content-center"><?php echo __t('send_message'); ?> <i class="bi bi-send ms-2"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════ FOOTER ══════════ -->
<footer class="footer-clean">
    <div style="max-width:1200px; margin:0 auto; padding:0 2rem; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:1.5rem;">
        <a href="<?= PROJECT_PATH ?>index.php" class="brand">
            <div class="brand-icon">A</div>
            Admissio
        </a>
        <div style="display:flex; gap:2rem; flex-wrap:wrap;">
            <a href="<?= PROJECT_PATH ?>auth/login.php?role=candidat" style="color:var(--muted);text-decoration:none;font-size:.9rem;font-weight:600;"><?php echo __t('candidate_space'); ?></a>
            <a href="<?= PROJECT_PATH ?>auth/login.php?role=gerant" style="color:var(--muted);text-decoration:none;font-size:.9rem;font-weight:600;"><?php echo __t('recruiter_space'); ?></a>
        </div>
        <a href="<?= PROJECT_PATH ?>auth/login.php?role=admin" style="color:var(--muted);text-decoration:none;font-size:.78rem;opacity:.5;"><i class="bi bi-shield-lock me-1"></i>Administration</a>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach(el => obs.observe(el));

function indexLiveSearch() {
    let input = document.getElementById('liveSearchIndex').value.toLowerCase();
    let category = document.getElementById('liveSearchCategory').value.toLowerCase();
    let cards = document.querySelectorAll('.index-offer-item');
    
    cards.forEach(function(card) {
        let title = card.querySelector('.index-offer-titre').innerText.toLowerCase();
        let company = card.querySelector('.index-offer-nom').innerText.toLowerCase();
        let cardCat = card.getAttribute('data-category').toLowerCase();
        
        // Match conditions
        let matchText = title.includes(input) || company.includes(input);
        let matchCat = category === "" || cardCat === category;
        
        if (matchText && matchCat) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
</body>
</html>

