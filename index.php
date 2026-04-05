<?php
require_once "config/database.php";
require_once "includes/layout.php";

$search_q = trim($_GET['q'] ?? '');
$offre_actifs = [];
$total_actifs = 0;
try {
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM offres WHERE statut = 'actif' AND (date_cloture >= CURDATE() OR date_cloture IS NULL)");
    $total_actifs = $total_stmt->fetchColumn();

    if ($total_actifs > 0) {
        $sql = "SELECT id, titre, date_ouverture, date_cloture FROM offres WHERE statut = 'actif' AND (date_cloture >= CURDATE() OR date_cloture IS NULL)";
        $params = [];
        if ($search_q !== '') {
            $sql .= " AND titre LIKE ?";
            $params[] = "%$search_q%";
        }
        $sql .= " ORDER BY date_cloture ASC LIMIT 6";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $offre_actifs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Erreur silencieuse sur l'accueil
}

include_header("Accueil");
?>

<div class="main-hero position-relative overflow-hidden min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);">
    <!-- Formes d'arrière-plan décoratives -->
    <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-primary opacity-10 blur-custom" style="width: 600px; height: 600px; filter: blur(80px);"></div>
    <div class="position-absolute bottom-0 end-0 translate-middle-y rounded-circle bg-info opacity-10 blur-custom" style="width: 400px; height: 400px; filter: blur(60px);"></div>

    <div class="container py-5 position-relative z-1 mt-5">
        <div class="row align-items-center justify-content-between g-5">
            <!-- Texte Hero -->
            <div class="col-lg-6 text-center text-lg-start animate__animated animate__fadeInLeft">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill bg-white shadow-sm border mb-4">
                    <span class="badge bg-success rounded-pill animate__animated animate__pulse animate__infinite">Nouveau</span>
                    <span class="small fw-semibold text-muted">Recrutement 2.0 est là !</span>
                </div>
                
                <h1 class="display-4 fw-black text-dark mb-4 lh-sm" style="font-weight: 900; letter-spacing: -1px;">
                    Révolutionnez vos embauches avec <br>
                    <span class="text-transparent bg-clip-text bg-gradient-primary-info" style="background: linear-gradient(90deg, #4f46e5, #0ea5e9); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Admissio</span>
                </h1>
                
                <p class="lead text-secondary mb-5 fs-4 lh-base" style="max-width: 500px;">
                    La plateforme cloud la plus avancée pour dénicher, évaluer et intégrer les meilleurs talents. Fini les CVs perdus par email.
                </p>
                
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
                    <a href="choix_connexion.php" class="btn btn-primary btn-lg rounded-pill px-5 py-3 fw-bold shadow-lg hover-lift-lg custom-glow">
                        Démarrer l'expérience <i class="bi bi-arrow-right ms-2 fs-5 align-middle"></i>
                    </a>
                </div>
                
                <div class="mt-5 pt-3 d-flex align-items-center justify-content-center justify-content-lg-start gap-4 text-muted small fw-semibold">
                    <span><i class="bi bi-check-circle-fill text-success me-1"></i> Sans carte de crédit</span>
                    <span><i class="bi bi-shield-lock-fill text-primary me-1"></i> Données sécurisées</span>
                </div>
            </div>
            
            <!-- Image / Illustration -->
            <div class="col-lg-5 d-none d-lg-block animate__animated animate__fadeInRight animate__delay-1s">
                <div class="position-relative">
                    <!-- Glassmorphism Card (Decorative) -->
                    <div class="p-2 bg-white bg-opacity-50 backdrop-blur rounded-5 shadow-lg border border-white">
                        <img src="https://img.freepik.com/free-vector/job-interview-conversation_74855-7566.jpg" class="img-fluid rounded-4 shadow-sm" alt="HR Solutions" style="mix-blend-mode: multiply;">
                    </div>
                    
                    <!-- Floating Badge -->
                    <div class="position-absolute bg-white p-3 rounded-4 shadow-lg d-flex align-items-center gap-3 animate__animated animate__bounceIn" style="bottom: 30px; left: -40px; border: 1px solid rgba(0,0,0,0.05);">
                        <div class="bg-success bg-opacity-10 p-2 rounded-circle text-success">
                            <i class="bi bi-briefcase-fill fs-3"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold text-dark"><?php echo $total_actifs; ?> Offre<?php echo $total_actifs > 1 ? 's' : ''; ?></h6>
                            <small class="text-muted fw-semibold">Actives actuellement</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div id="features" class="row g-4 mt-5 pt-5 relative bg-white bg-opacity-50 rounded-5 p-4 shadow-sm backdrop-blur border border-white" style="margin-top: 6rem !important;">
            <div class="col-12 text-center mb-4">
                <h2 class="fw-bold fs-3 mt-2">Pourquoi choisir Admissio ?</h2>
                <div class="mx-auto bg-primary rounded-pill mb-4" style="width: 60px; height: 4px;"></div>
            </div>
            
            <div class="col-md-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <div class="card h-100 border-0 shadow-none bg-transparent p-3 text-center group">
                    <div class="mx-auto bg-white text-primary p-4 rounded-circle shadow-sm mb-4 icon-hover-spin" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                        <i class="bi bi-lightning-charge-fill fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-3 fs-5">Rapidité Fulgurante</h5>
                    <p class="text-muted small mb-0 lh-lg">Créez des offres et collectez des dossiers en quelques minutes grâce à notre constructeur de formulaires.</p>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                <div class="card h-100 border-0 shadow-none bg-transparent p-3 text-center group">
                    <div class="mx-auto bg-white text-info p-4 rounded-circle shadow-sm mb-4 icon-hover-bounce" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                        <i class="bi bi-ui-radios fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-3 fs-5">Gestion Centralisée</h5>
                    <p class="text-muted small mb-0 lh-lg">Filtrez, étudiez et notez les candidatures directement depuis un tableau de bord intuitif et collaboratif.</p>
                </div>
            </div>
            <div class="col-md-4 animate__animated animate__fadeInUp" style="animation-delay: 0.6s;">
                <div class="card h-100 border-0 shadow-none bg-transparent p-3 text-center group">
                    <div class="mx-auto bg-white text-danger p-4 rounded-circle shadow-sm mb-4 icon-hover-pulse" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                        <i class="bi bi-shield-lock-fill fs-1"></i>
                    </div>
                    <h5 class="fw-bold mb-3 fs-5">Sécurité Absolue</h5>
                    <p class="text-muted small mb-0 lh-lg">Chiffrement de bout en bout, protection CSRF/XSS et hachage fort pour une conformité rgpd totale.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section Offre Actifs -->
<?php if ($total_actifs > 0): ?>
<div class="bg-white py-5" id="offre">
    <div class="container mt-2 mb-5 relative">
            <div class="text-center mb-5">
                <h2 class="fw-bold fs-2 text-dark">Offres Récentes & Ouvertes</h2>
                <div class="mx-auto bg-primary rounded-pill mb-4" style="width: 60px; height: 4px;"></div>
                <p class="text-muted fs-5">Découvrez les opportunités actuellement disponibles et postulez dès maintenant.</p>
                
                <form method="GET" action="index.php#offre" class="mx-auto mt-4" style="max-width: 600px;">
                    <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border">
                        <span class="input-group-text bg-white border-0 text-primary px-4"><i class="bi bi-search"></i></span>
                        <input type="text" name="q" class="form-control border-0 bg-white shadow-none" placeholder="Chercher une offre..." value="<?php echo htmlspecialchars($search_q); ?>">
                        <button class="btn btn-primary px-4 fw-bold" type="submit">Rechercher</button>
                    </div>
                </form>
            </div>
            
            <div class="row g-4">
                <?php if (empty($offre_actifs) && $search_q !== ''): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-search display-1 text-muted opacity-25 mb-3 d-block"></i>
                        <h4 class="text-muted">Aucune offre ne correspond à votre recherche "<?php echo htmlspecialchars($search_q); ?>"</h4>
                        <a href="index.php#offre" class="btn btn-outline-primary mt-3 rounded-pill">Effacer la recherche</a>
                    </div>
                <?php else: ?>
                <?php foreach ($offre_actifs as $c): ?>
                    <div class="col-md-4 animate__animated animate__fadeInUp">
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 hover-lift-lg bg-white group">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-bold border border-success-subtle">
                                    <span class="spinner-grow spinner-grow-sm align-middle me-1" style="width: 0.5rem; height: 0.5rem;" role="status"></span> En cours
                                </span>
                                <i class="bi bi-award text-primary fs-4 opacity-50"></i>
                            </div>
                            <h4 class="fw-bold mb-3 ls-tight text-dark" style="font-family: 'Outfit', sans-serif;"><?php echo htmlspecialchars($c['titre']); ?></h4>
                            <div class="mb-4 text-muted small d-flex flex-column gap-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-calendar-check me-2 text-primary fs-5"></i> 
                                    <span><strong>Ouverture :</strong> <?php echo date('d/m/Y', strtotime($c['date_ouverture'])); ?></span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-hourglass-split me-2 text-danger fs-5"></i> 
                                    <span><strong>Clôture :</strong> <?php echo $c['date_cloture'] ? date('d/m/Y', strtotime($c['date_cloture'])) : 'Non définie'; ?></span>
                                </div>
                            </div>
                            <div class="mt-auto pt-3 border-top">
                                <a href="candidat/postuler_form.php?id_offre=<?php echo $c['id']; ?>" class="btn btn-primary rounded-pill w-100 fw-bold shadow-sm hover-lift-lg">
                                    Postuler maintenant <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
/* Utilities dynamiques pour index.php */
.hover-lift-lg { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.hover-lift-lg:hover { transform: translateY(-5px); box-shadow: 0 1rem 3rem rgba(0,0,0,.15)!important; }
.backdrop-blur { backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
.custom-glow { box-shadow: 0 0 20px rgba(79, 70, 229, 0.4) !important; }
.icon-hover-spin:hover { transform: rotate(15deg) scale(1.1); }
.icon-hover-bounce:hover { transform: translateY(-10px); }
.icon-hover-pulse:hover { transform: scale(1.1); }
.fw-black { font-weight: 900; }
</style>

<?php
include_footer();
exit();
?>