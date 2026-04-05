<?php
require_once "includes/layout.php";

include_header("Choisir mon espace");
?>

<div class="auth-wrapper position-relative overflow-hidden min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);">
    <!-- Formes d'arrière-plan décoratives -->
    <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-primary opacity-10 blur-custom" style="width: 600px; height: 600px; filter: blur(80px);"></div>
    <div class="position-absolute bottom-0 end-0 translate-middle-y rounded-circle bg-info opacity-10 blur-custom" style="width: 400px; height: 400px; filter: blur(60px);"></div>

    <div class="container py-5 position-relative z-1">
        <div class="text-center mb-5 animate__animated animate__fadeInDown">
            <h1 class="display-4 fw-black text-dark mb-3" style="font-weight: 900; letter-spacing: -1px;">
                Bienvenue sur <span class="text-transparent bg-clip-text bg-gradient-primary-info" style="background: linear-gradient(90deg, #4f46e5, #0ea5e9); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Admissio</span>
            </h1>
            <p class="lead text-secondary">Sélectionnez votre espace pour continuer votre parcours.</p>
        </div>
        
        <div class="row g-4 justify-content-center animate__animated animate__fadeInUp animate__delay-1s">
            <!-- Espace Candidat -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-5 p-4 text-center hover-lift-lg bg-white bg-opacity-75 backdrop-blur border-bottom border-primary border-5" style="border-width: 0 0 5px 0 !important; transition: all 0.3s ease;">
                    <div class="mx-auto bg-primary bg-opacity-10 text-primary p-4 rounded-circle mb-4 icon-hover-bounce" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-person-badge fs-1"></i>
                    </div>
                    <h2 class="h4 fw-bold mb-3">Candidat</h2>
                    <p class="text-muted small mb-4 lh-lg">Consultez les offres et déposez vos dossiers de candidature en quelques clics.</p>
                    <div class="mt-auto">
                        <a href="auth/login.php?role=candidat" class="btn btn-primary w-100 rounded-pill py-2 fw-bold mb-3 shadow-sm custom-glow">Accéder à mon espace</a>
                        <p class="small mb-0">Nouveau ? <a href="auth/register_candidat.php" class="text-primary text-decoration-none fw-bold">Créer un compte</a></p>
                    </div>
                </div>
            </div>

            <!-- Espace Recruteur -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-5 p-4 text-center hover-lift-lg bg-white bg-opacity-75 backdrop-blur border-bottom border-success border-5" style="border-width: 0 0 5px 0 !important; transition: all 0.3s ease;">
                    <div class="mx-auto bg-success bg-opacity-10 text-success p-4 rounded-circle mb-4 icon-hover-bounce" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-briefcase fs-1"></i>
                    </div>
                    <h2 class="h4 fw-bold mb-3">Recruteur</h2>
                    <p class="text-muted small mb-4 lh-lg">Publiez vos offres, gérez vos formulaires et sélectionnez les meilleurs talents pour votre structure.</p>
                    <div class="mt-auto">
                        <a href="auth/login.php?role=gerant" class="btn btn-success w-100 rounded-pill py-2 fw-bold mb-3 shadow-sm text-white" style="box-shadow: 0 0 20px rgba(16, 185, 129, 0.4) !important;">Gérer mes recrutements</a>
                        <p class="small mb-0">Demander un accès ? <a href="auth/register_gerant.php" class="text-success text-decoration-none fw-bold">Espace Recruteur</a></p>
                    </div>
                </div>
            </div>

            <!-- Espace Administrateur -->
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-5 p-4 text-center hover-lift-lg bg-white bg-opacity-75 backdrop-blur border-bottom border-dark border-5" style="border-width: 0 0 5px 0 !important; transition: all 0.3s ease;">
                    <div class="mx-auto bg-dark bg-opacity-10 text-dark p-4 rounded-circle mb-4 icon-hover-bounce" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-shield-lock fs-1"></i>
                    </div>
                    <h2 class="h4 fw-bold mb-3">Administrateur</h2>
                    <p class="text-muted small mb-4 lh-lg">Supervision globale, gestion des utilisateurs et maintenance complète du système.</p>
                    <div class="mt-auto">
                        <a href="auth/login.php?role=admin" class="btn btn-dark w-100 rounded-pill py-2 fw-bold shadow-sm" style="box-shadow: 0 0 20px rgba(33, 37, 41, 0.4) !important;">Administration</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 mb-4 animate__animated animate__fadeInUp animate__delay-2s">
            <a href="index.php" class="btn btn-white bg-white text-muted text-decoration-none rounded-pill px-4 py-2 shadow-sm border border-light font-weight-bold hover-lift-lg">
                <i class="bi bi-arrow-left me-2"></i>Retour à l'accueil
            </a>
        </div>
    </div>
</div>
<style>
/* Utilities dynamiques importées d'index.php */
.hover-lift-lg { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.hover-lift-lg:hover { transform: translateY(-5px); box-shadow: 0 1rem 3rem rgba(0,0,0,.15)!important; }
.backdrop-blur { backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
.custom-glow { box-shadow: 0 0 20px rgba(79, 70, 229, 0.4) !important; }
.icon-hover-bounce:hover { transform: translateY(-5px); }
</style>

<?php 
include_footer();
exit();
?>