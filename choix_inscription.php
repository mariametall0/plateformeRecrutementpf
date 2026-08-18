<?php
require_once "includes/layout.php";

// If already logged in, redirect with clear message or allow logout
if (isset($_SESSION["role"])) {
    $role = $_SESSION["role"];
    $dashboard_url = PROJECT_PATH . $role . "/dashboard.php";
} else {
    $role = null;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>S'inscrire – Admissio</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Inter', sans-serif; background: #fff; overflow-x: hidden; }
:root {
  --blue: #0f5132; --blue-dark: #0a3622; --green: #198754;
  --dark: #0f172a; --muted: #64748b; --border: rgba(0,0,0,.07);
}
/* Navbar */
.nav-admissio {
    position: sticky; top: 0; z-index: 999;
    background: rgba(255,255,255,.88);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    padding: 14px 0;
}
.nav-inner { max-width: 1200px; margin: 0 auto; padding: 0 2rem; display: flex; align-items: center; justify-content: space-between; }
.brand { display: flex; align-items: center; gap: 10px; text-decoration: none; font-weight: 900; font-size: 1.25rem; color: var(--blue); }
.brand-icon { width: 32px; height: 32px; background: var(--blue); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; font-weight: 900; }
.btn-back { display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: .9rem; font-weight: 600; text-decoration: none; transition: color .2s; }
.btn-back:hover { color: var(--dark); }

/* Page layout */
.choice-page {
    min-height: calc(100vh - 64px);
    background: linear-gradient(160deg, #f0fdf4 0%, #d1fae5 50%, #ecfeff 100%);
    display: flex; align-items: center; justify-content: center;
    padding: 4rem 2rem; position: relative; overflow: hidden;
}
/* Blobs */
.blob { position: absolute; border-radius: 50%; filter: blur(100px); pointer-events: none; }
.blob-1 { width: 600px; height: 600px; background: radial-gradient(circle, rgba(110,168,254,.28) 0%, transparent 70%); top: -180px; right: -180px; animation: drift 20s ease-in-out infinite alternate; }
.blob-2 { width: 450px; height: 450px; background: radial-gradient(circle, rgba(13,202,240,.14) 0%, transparent 70%); bottom: -120px; left: -120px; animation: drift 15s ease-in-out infinite alternate-reverse; }
.blob-3 { width: 350px; height: 350px; background: radial-gradient(circle, rgba(25,135,84,.08) 0%, transparent 70%); top: 30%; left: 40%; animation: drift 18s ease-in-out infinite alternate; }
@keyframes drift { 0% { transform: translate(0,0) scale(1); } 100% { transform: translate(50px, 35px) scale(1.07); } }

.choice-inner { position: relative; z-index: 1; text-align: center; width: 100%; max-width: 860px; }

/* Badge */
.page-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(15,81,50,.08); color: var(--blue);
    border-radius: 100px; padding: 6px 16px;
    font-weight: 600; font-size: .82rem;
    border: 1px solid rgba(15,81,50,.15);
    margin-bottom: 1.5rem;
}
.pulse-dot { width: 8px; height: 8px; background: var(--blue); border-radius: 50%; display: inline-block; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(15,81,50,.5); } 50% { box-shadow: 0 0 0 5px rgba(15,81,50,0); } }

.choice-inner h1 { font-size: 2.8rem; font-weight: 900; color: var(--dark); line-height: 1.15; letter-spacing: -1.5px; margin-bottom: .75rem; }
.choice-inner h1 .grad { background: linear-gradient(135deg, var(--blue), #0dcaf0); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.choice-inner .sub { font-size: 1.05rem; color: var(--muted); margin-bottom: 3rem; line-height: 1.6; }

/* Portal cards */
.portals { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; max-width: 720px; margin: 0 auto 2.5rem; }

.portal-btn {
    display: flex; flex-direction: column; align-items: center; gap: 1rem;
    background: rgba(255,255,255,.8);
    backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
    border: 1.5px solid rgba(255,255,255,.95);
    border-radius: 24px; padding: 2.5rem 2rem;
    text-decoration: none; color: var(--dark);
    box-shadow: 0 10px 40px rgba(0,0,0,.05), 0 1px 0 rgba(255,255,255,.8) inset;
    transition: all .3s cubic-bezier(.25,.46,.45,.94);
}
.portal-btn:hover {
    transform: translateY(-8px);
    box-shadow: 0 24px 60px rgba(15,81,50,.13);
    border-color: rgba(15,81,50,.3);
    color: var(--dark);
}

.portal-icon { width: 68px; height: 68px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: .25rem; }
.portal-icon-blue  { background: rgba(15,81,50,.08); color: var(--blue); }
.portal-icon-green { background: rgba(25,135,84,.08);  color: var(--green); }

.portal-title { font-size: 1.15rem; font-weight: 800; color: var(--dark); }
.portal-desc  { font-size: .85rem; color: var(--muted); line-height: 1.5; text-align: center; }

.portal-cta {
    display: inline-flex; align-items: center; gap: 6px;
    font-weight: 700; font-size: .85rem; margin-top: .25rem;
    padding: 8px 20px; border-radius: 100px;
    transition: all .2s;
}
.portal-cta-blue  { background: rgba(15,81,50,.08); color: var(--blue); }
.portal-btn:hover .portal-cta-blue  { background: var(--blue); color: #fff; }
.portal-cta-green { background: rgba(25,135,84,.08); color: var(--green); }
.portal-btn:hover .portal-cta-green { background: var(--green); color: #fff; }

/* Admin link */
.admin-link {
    display: inline-flex; align-items: center; gap: 6px;
    color: rgba(100,116,139,.5); font-size: .8rem; font-weight: 600;
    text-decoration: none; transition: color .2s;
}
.admin-link:hover { color: var(--muted); }

@media (max-width: 640px) {
    .portals { grid-template-columns: 1fr; max-width: 380px; }
    .choice-inner h1 { font-size: 2rem; }
}
</style>
</head>
<body>
<nav class="nav-admissio">
    <div class="nav-inner">
        <a href="<?= PROJECT_PATH ?>index.php" class="brand">
            <div class="brand-icon">A</div>
            Admissio
        </a>
        <a href="<?= PROJECT_PATH ?>index.php" class="btn-back">
            <i class="bi bi-arrow-left"></i> Retour à l'accueil
        </a>
    </div>
</nav>

<div class="choice-page">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="choice-inner">
        <div class="page-badge">
            <span class="pulse-dot"></span>
            Plateforme certifiée &amp; sécurisée
        </div>

        <h1>Créez votre<br><span class="grad">compte Admissio</span></h1>
        <p class="sub">Sélectionnez votre profil pour vous inscrire et accéder aux fonctionnalités dédiées.</p>

        <div class="portals">
            <?php if ($role): ?>
                <div class="portal-btn" style="grid-column: span 2;">
                    <div class="portal-icon portal-icon-blue">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <div class="portal-title">Vous êtes déjà connecté</div>
                    <p class="portal-desc">Vous êtes actuellement connecté en tant que <strong><?php echo htmlspecialchars($role); ?></strong>.</p>
                    <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                        <a href="<?php echo $dashboard_url; ?>" class="portal-cta portal-cta-blue">Continuer vers mon espace <i class="bi bi-arrow-right"></i></a>
                        <a href="<?= PROJECT_PATH ?>auth/logout.php" class="portal-cta portal-cta-green" style="background:#fee2e2; color:#dc2626;">Se déconnecter <i class="bi bi-box-arrow-right"></i></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= PROJECT_PATH ?>auth/register_candidat.php" class="portal-btn">
                    <div class="portal-icon portal-icon-blue">
                        <i class="bi bi-person-bounding-box"></i>
                    </div>
                    <div class="portal-title">Je suis Candidat</div>
                    <p class="portal-desc">Créez votre CV, découvrez des opportunités et suivez vos candidatures.</p>
                    <span class="portal-cta portal-cta-blue">S'inscrire <i class="bi bi-arrow-right"></i></span>
                </a>

                <a href="<?= PROJECT_PATH ?>auth/register_gerant.php" class="portal-btn">
                    <div class="portal-icon portal-icon-green">
                        <i class="bi bi-buildings"></i>
                    </div>
                    <div class="portal-title">Je suis Recruteur</div>
                    <p class="portal-desc">Publiez vos offres, évaluez les candidats et planifiez vos entretiens sereinement.</p>
                    <span class="portal-cta portal-cta-green">S'inscrire <i class="bi bi-arrow-right"></i></span>
                </a>
            <?php endif; ?>
        </div>

        <a href="<?= PROJECT_PATH ?>choix_connexion.php" class="admin-link mt-3" style="color: var(--blue);">
            <i class="bi bi-person-check"></i> Vous avez déjà un compte ? Connectez-vous
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
