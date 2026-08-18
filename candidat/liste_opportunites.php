<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_candidat = $_SESSION["id"];
$search = trim($_GET['search'] ?? '');

// Récupérer les opportunités (concours) actives + indiquer si déjà postulé
$sql = "
    SELECT co.*, e.secteur_activite,
           (SELECT COUNT(*) FROM candidatures WHERE id_candidat = :id_candidat AND id_concours = co.id) AS deja_postule
    FROM concours co
    LEFT JOIN utilisateurs u ON co.id_gerant = u.id
    LEFT JOIN entreprises e ON u.id = e.id_utilisateur
    WHERE co.statut = 'actif'
      AND (co.date_cloture >= CURDATE() OR co.date_cloture IS NULL)
";
$params = [':id_candidat' => $id_candidat];

if ($search !== '') {
    $sql .= " AND co.titre LIKE :search";
    $params[':search'] = "%$search%";
}
$sql .= " ORDER BY co.date_cloture ASC";

$opportunites = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $opportunites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les secteurs pour le filtre
    $s_sec = $pdo->prepare("
        SELECT DISTINCT secteur FROM concours WHERE secteur IS NOT NULL AND secteur != ''
        UNION
        SELECT DISTINCT secteur_activite FROM entreprises WHERE secteur_activite IS NOT NULL AND secteur_activite != ''
    ");
    $s_sec->execute();
    $secteurs_raw = $s_sec->fetchAll(PDO::FETCH_COLUMN);
    
    // Normalisation pour éviter les doublons de casse
    $secteurs = array_unique(array_map('mb_strtolower', $secteurs_raw));
    $secteurs = array_map(function($s) { return mb_convert_case($s, MB_CASE_TITLE, "UTF-8"); }, $secteurs);
    
    $defauts = ["Informatique", "Commerce", "Santé", "Éducation", "BTP", "Industrie"];
    foreach($defauts as $d) { if(!in_array($d, $secteurs)) $secteurs[] = $d; }
    sort($secteurs);

} catch (PDOException $e) {
    send_error("Erreur lors du chargement des opportunités : " . $e->getMessage(), 500);
}

include_header(__t('opportunities'));
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="row animate__animated animate__fadeIn">
        <div class="col-12 mb-5">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4">
                <div>
                    <h1 class="display-6 fw-black text-gray-900 mb-1"><?php echo __t('featured_opportunities'); ?></h1>
                    <p class="text-muted mb-0"><?php echo __t('discover_posts_subtext'); ?></p>
                </div>
                
                <div class="d-flex gap-3 align-items-center flex-wrap">
                    <div class="search-premium" style="min-width: 200px;">
                        <i class="bi bi-funnel text-muted"></i>
                        <select id="liveSearchCategory" class="border-0 bg-transparent small fw-bold" style="outline:none; cursor:pointer;" onchange="liveSearch()">
                            <option value=""><?php echo __t('all_sectors'); ?></option>
                            <?php foreach($secteurs as $sec): ?>
                                <option value="<?php echo htmlspecialchars((string)$sec); ?>"><?php echo htmlspecialchars((string)$sec); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <form class="search-premium" onsubmit="event.preventDefault();">
                        <i class="bi bi-search text-muted"></i>
                        <input type="text" id="liveSearchInput" placeholder="<?php echo __t('search_placeholder'); ?>" value="<?php echo htmlspecialchars($search); ?>" onkeyup="liveSearch()">
                    </form>
                </div>
            </div>
        </div>

        <?php if (empty($opportunites)): ?>
            <div class="col-12 text-center py-5">
                <div class="display-1 mb-4 opacity-10">🔍</div>
                <h3 class="fw-bold text-gray-900"><?php echo __t('no_opportunity_found'); ?></h3>
                <p class="text-muted"><?php echo __t('try_modifying_search'); ?></p>
                <a href="liste_opportunites.php" class="btn-pro btn-pro-secondary btn-pro-sm mt-3"><?php echo __t('Réinitialiser'); ?></a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($opportunites as $index => $o): 
                    $today = new DateTime();
                    $cloture = new DateTime($o['date_cloture']);
                    $is_expire = ($cloture < $today);
                    $delay_class = 'card-anim-delay-' . min($index + 1, 6);
                ?>
                    <div class="col-md-6 col-lg-4 anim-up <?php echo $delay_class; ?> index-offer-item" data-category="<?php echo htmlspecialchars((string)($o['secteur'] ?: $o['secteur_activite'] ?: '')); ?>">
                        <div class="opportunity-card">
                            <div class="opportunity-status">
                                <?php if ($o['deja_postule']): ?>
                                    <span class="opportunity-badge opportunity-badge-posted">✓ <?php echo __t('posted'); ?></span>
                                <?php elseif ($is_expire): ?>
                                    <span class="opportunity-badge opportunity-badge-closed"><?php echo __t('closed'); ?></span>
                                <?php else: ?>
                                    <span class="opportunity-badge opportunity-badge-active"><?php echo __t('active'); ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 class="opportunity-title mt-4"><?php echo htmlspecialchars($o['titre']); ?></h3>
                            <p class="opportunity-desc">
                                <?php echo htmlspecialchars(mb_strimwidth($o['description'], 0, 120, "...")); ?>
                            </p>

                            <div class="opportunity-meta">
                                <div class="small fw-bold text-gray-500">
                                    <i class="bi bi-calendar-event me-1"></i> <?php echo __t('ends_on'); ?> <?php echo format_date($o['date_cloture']); ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="details_opportunite.php?id=<?php echo $o['id']; ?>" class="btn-pro btn-pro-secondary btn-pro-sm"><?php echo __t('details'); ?></a>
                                    <?php if (!$o['deja_postule'] && !$is_expire): ?>
                                        <a href="postuler_form.php?id=<?php echo $o['id']; ?>" class="btn-pro btn-pro-primary btn-pro-sm"><?php echo __t('apply'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function liveSearch() {
    let input = document.getElementById('liveSearchInput').value.toLowerCase().trim();
    let category = document.getElementById('liveSearchCategory').value.toLowerCase().trim();
    let cards = document.querySelectorAll('.index-offer-item');
    
    cards.forEach(function(cardCol) {
        let card = cardCol.querySelector('.opportunity-card');
        let title = card.querySelector('.opportunity-title').innerText.toLowerCase();
        let desc = card.querySelector('.opportunity-desc').innerText.toLowerCase();
        let cardCat = cardCol.getAttribute('data-category').toLowerCase().trim();
        
        // Match conditions
        let matchText = title.includes(input) || desc.includes(input);
        let matchCat = category === "" || cardCat === category;
        
        if (matchText && matchCat) {
            cardCol.style.display = '';
        } else {
            cardCol.style.display = 'none';
        }
    });
}
</script>

<?php 
include_footer();
exit();
?>
