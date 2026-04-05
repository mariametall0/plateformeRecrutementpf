<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

// Récupération des données pour l'affichage
$id_candidat = $_SESSION["id"];
$nb_total = 0; $nb_attente = 0; $nb_validees = 0; $nb_ouverts = 0;

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidatures WHERE id_candidat = ?");
    $stmt->execute([$id_candidat]);
    $nb_total = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidatures WHERE id_candidat = ? AND statut = 'en_attente'");
    $stmt->execute([$id_candidat]);
    $nb_attente = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidatures WHERE id_candidat = ? AND statut = 'validee'");
    $stmt->execute([$id_candidat]);
    $nb_validees = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM offres WHERE statut = 'actif' AND date_cloture >= CURDATE()");
    $stmt->execute();
    $nb_ouverts = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Erreur silencieuse ou log
}

include_header("Tableau de bord Candidat");
?>

<div class="row mb-5 animate__animated animate__fadeIn">
    <div class="col-12">
        <div class="px-5 py-5 rounded-5 shadow-premium border-0 d-flex justify-content-between align-items-center position-relative overflow-hidden" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);">
            <!-- Decorative circle -->
            <div class="position-absolute rounded-circle opacity-10 bg-white" style="width: 250px; height: 250px; top: -50px; right: -50px;"></div>
            
            <div class="position-relative z-1">
                <h1 class="display-5 fw-extrabold text-white mb-2">Bonjour, <?php echo htmlspecialchars($_SESSION['nom']); ?> 👋</h1>
                <p class="text-white opacity-75 fs-5 mb-0">Suivez vos candidatures et explorez de nouvelles opportunités de carrière.</p>
            </div>
            <a href="liste_offres.php" class="btn btn-white btn-lg d-none d-md-flex align-items-center gap-2 rounded-pill px-5 fw-bold text-primary shadow-sm position-relative z-1" style="background: white;">
                <i class="bi bi-search"></i> Voir les offre
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Stat 1: Offre ouverts -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-4 animate__animated animate__fadeInUp animate__delay-1s">
            <div class="stat-card-icon bg-blue-50 text-blue-600">🚀</div>
            <div class="display-6 fw-bold text-dark mb-1"><?php echo $nb_ouverts; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-4 ls-1">Offre ouverts</div>
            <div class="mt-auto">
                <a href="liste_offres.php" class="btn btn-light w-100 rounded-pill fw-bold text-primary border">Voir tout →</a>
            </div>
        </div>
    </div>
    <!-- Stat 2: Mes candidatures -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-4 animate__animated animate__fadeInUp animate__delay-2s">
            <div class="stat-card-icon bg-indigo-50 text-primary">📄</div>
            <div class="display-6 fw-bold text-dark mb-1"><?php echo $nb_total; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-4 ls-1">Mes candidatures</div>
            <div class="mt-auto">
                <a href="mes_candidatures.php" class="btn btn-light w-100 rounded-pill fw-bold text-primary border">Détails →</a>
            </div>
        </div>
    </div>
    <!-- Stat 3: En attente -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-4 animate__animated animate__fadeInUp animate__delay-3s">
            <div class="stat-card-icon bg-amber-50 text-warning">🕒</div>
            <div class="display-6 fw-bold text-dark mb-1"><?php echo $nb_attente; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-4 ls-1">En attente</div>
            <div class="mt-auto">
                <span class="badge bg-amber-50 text-warning border border-amber-100 w-100 py-2">En cours d'étude</span>
            </div>
        </div>
    </div>
    <!-- Stat 4: Présélections -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="card h-100 p-4 animate__animated animate__fadeInUp animate__delay-4s">
            <div class="stat-card-icon bg-green-50 text-success">✅</div>
            <div class="display-6 fw-bold text-dark mb-1"><?php echo $nb_validees; ?></div>
            <div class="text-uppercase small fw-bold text-muted mb-4 ls-1">Présélections</div>
            <div class="mt-auto">
                <a href="preselections.php" class="btn btn-light w-100 rounded-pill fw-bold text-success border">Consulter →</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card p-4 h-100 glass hover-scale">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="stat-card-icon bg-primary bg-opacity-10 text-primary m-0" style="width: 50px; height: 50px; font-size: 1.25rem;">👤</div>
                <h4 class="fw-extrabold mb-0">Mon Profil</h4>
            </div>
            <p class="text-muted small mb-4">Mettez à jour vos informations personnelles, téléchargez vos nouveaux diplômes et gérez vos coordonnées.</p>
            <a href="modifier_profil.php" class="btn btn-primary rounded-pill px-4 align-self-start fw-bold shadow-sm">Modifier le profil</a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-4 h-100 glass hover-scale">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="stat-card-icon bg-dark bg-opacity-10 text-dark m-0" style="width: 50px; height: 50px; font-size: 1.25rem;">🔒</div>
                <h4 class="fw-extrabold mb-0">Sécurité</h4>
            </div>
            <p class="text-muted small mb-4">Changez votre mot de passe régulièrement pour assurer la protection maximale de votre compte.</p>
            <a href="../auth/changer_mdp.php" class="btn btn-dark rounded-pill px-4 align-self-start fw-bold shadow-sm">Changer le mot de passe</a>
        </div>
    </div>
</div>

<?php 
include_footer();
exit();
?>