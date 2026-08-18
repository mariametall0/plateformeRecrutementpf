<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];
$id_concours = (int)($_GET["id"] ?? $_POST["id"] ?? 0);

if ($id_concours <= 0) {
    send_error("ID de l'opportunité invalide.");
}

// Vérifier appartenance
try {
    $stmt = $pdo->prepare("SELECT id, titre FROM concours WHERE id = ? AND id_gerant = ?");
    $stmt->execute([$id_concours, $id_gerant]);
    $opportunite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$opportunite) {
        send_error("Opportunité non trouvée ou accès refusé.", 404);
    }

    // --- ACTIONS POST ---
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
        verify_csrf_token();
        
        if ($_POST["action"] === "ajouter") {
            $libelle     = trim($_POST["libelle"] ?? "");
            $type_champ  = $_POST["type_champ"] ?? "";
            $obligatoire = isset($_POST["obligatoire"]) ? 1 : 0;
            $options     = trim($_POST["options_liste"] ?? "");

            $types_valides = ["texte","textarea","date","nombre","liste","case_a_cocher","fichier"];

            if (!empty($libelle) && in_array($type_champ, $types_valides)) {
                $ordre_stmt = $pdo->prepare("SELECT COALESCE(MAX(ordre),0)+1 FROM champs_formulaire WHERE id_concours = ?");
                $ordre_stmt->execute([$id_concours]);
                $prochain_ordre = $ordre_stmt->fetchColumn();

                $pdo->prepare("INSERT INTO champs_formulaire (id_concours, libelle, type_champ, options_liste, obligatoire, ordre)
                                VALUES (?, ?, ?, ?, ?, ?)")
                    ->execute([$id_concours, $libelle, $type_champ, $options ?: null, $obligatoire, $prochain_ordre]);
                
                $_SESSION['success_message'] = "Champ ajouté.";
            } else {
                $_SESSION['error_message'] = "Données invalides.";
            }
        }
        
        header("Location: formulaire_opportunite.php?id=$id_concours");
        exit();
    }

    // --- ACTIONS GET (Suppression, Ordre) ---
    if (isset($_GET["action"])) {
        if ($_GET["action"] === "supprimer" && isset($_GET["id_champ"])) {
            $id_champ = (int)$_GET["id_champ"];
            $pdo->prepare("DELETE FROM champs_formulaire WHERE id = ? AND id_concours = ?")
                ->execute([$id_champ, $id_concours]);
            $_SESSION['success_message'] = "Champ supprimé.";
        }
        
        if ((isset($_GET["monter"]) || isset($_GET["descendre"])) && isset($_GET["id_champ"])) {
            $id_champ = (int)$_GET["id_champ"];
            $direction = isset($_GET["monter"]) ? "monter" : "descendre";

            $stmt_champ = $pdo->prepare("SELECT id, ordre FROM champs_formulaire WHERE id = ? AND id_concours = ?");
            $stmt_champ->execute([$id_champ, $id_concours]);
            $champ_actuel = $stmt_champ->fetch(PDO::FETCH_ASSOC);

            if ($champ_actuel) {
                if ($direction === "monter") {
                    $stmt_voisin = $pdo->prepare("SELECT id, ordre FROM champs_formulaire WHERE id_concours = ? AND ordre < ? ORDER BY ordre DESC LIMIT 1");
                } else {
                    $stmt_voisin = $pdo->prepare("SELECT id, ordre FROM champs_formulaire WHERE id_concours = ? AND ordre > ? ORDER BY ordre ASC LIMIT 1");
                }
                $stmt_voisin->execute([$id_concours, $champ_actuel["ordre"]]);
                $voisin = $stmt_voisin->fetch(PDO::FETCH_ASSOC);

                if ($voisin) {
                    $pdo->prepare("UPDATE champs_formulaire SET ordre = ? WHERE id = ?")->execute([$voisin["ordre"], $champ_actuel["id"]]);
                    $pdo->prepare("UPDATE champs_formulaire SET ordre = ? WHERE id = ?")->execute([$champ_actuel["ordre"], $voisin["id"]]);
                }
            }
        }
        
        header("Location: formulaire_opportunite.php?id=$id_concours");
        exit();
    }

    // --- LECTURE ---
    $stmt_champs = $pdo->prepare("SELECT * FROM champs_formulaire WHERE id_concours = ? ORDER BY ordre ASC");
    $stmt_champs->execute([$id_concours]);
    $champs = $stmt_champs->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}

include_header(__t('form_builder'));
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-5 anim-up">
        <div>
            <a href="liste_opportunites.php" class="btn btn-sm btn-white border rounded-pill px-3 mb-3 fw-bold"><i class="bi bi-arrow-left"></i> <?php echo __t('back_to_opportunities'); ?></a>
            <h1 class="display-6 fw-black text-gray-900 mb-1"><?php echo __t('customize_form'); ?></h1>
            <p class="text-muted mb-0"><?php echo __t('customize_form_subtext'); ?> <span class="text-success fw-bold"><?php echo htmlspecialchars($opportunite['titre']); ?></span></p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar : Ajouter un champ -->
        <div class="col-lg-4 anim-up anim-delay-1">
            <div class="glass-premium p-4 rounded-4 shadow-sm border border-white border-opacity-20">
                <h5 class="fw-black text-gray-900 mb-4"><i class="bi bi-plus-square text-success me-2"></i><?php echo __t('new_field'); ?></h5>
                
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="ajouter">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase"><?php echo __t('question_label'); ?></label>
                        <input type="text" name="libelle" class="form-control" placeholder="<?php echo __t('question_label_placeholder'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase"><?php echo __t('response_type'); ?></label>
                        <select name="type_champ" id="type_champ" class="form-select" onchange="toggleOptions()">
                            <option value="texte"><?php echo __t('short_text'); ?></option>
                            <option value="textarea"><?php echo __t('paragraph'); ?></option>
                            <option value="nombre"><?php echo __t('number'); ?></option>
                            <option value="date"><?php echo __t('Date'); ?></option>
                            <option value="liste"><?php echo __t('choice_list'); ?></option>
                            <option value="case_a_cocher"><?php echo __t('checkbox'); ?></option>
                            <option value="fichier"><?php echo __t('file_upload'); ?></option>
                        </select>
                    </div>

                    <div id="options_container" class="mb-3 d-none">
                        <label class="form-label small fw-bold text-muted text-uppercase"><?php echo __t('options_per_line'); ?></label>
                        <textarea name="options_liste" class="form-control" rows="3" placeholder="Option 1&#10;Option 2..."></textarea>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="obligatoire" id="obligatoire" checked>
                        <label class="form-check-label small fw-bold text-muted" for="obligatoire"><?php echo __t('required_response'); ?></label>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-3 rounded-pill fw-black shadow-premium">
                        <?php echo __t('add_to_form'); ?> <i class="bi bi-plus-lg ms-1"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Main : Liste des champs -->
        <div class="col-lg-8 anim-up anim-delay-2">
            <div class="glass-premium p-4 rounded-4 shadow-sm border border-white border-opacity-20 overflow-hidden">
                <h5 class="fw-black text-gray-900 mb-4"><i class="bi bi-list-ul text-success me-2"></i><?php echo __t('current_fields'); ?></h5>
                
                <?php if (empty($champs)): ?>
                    <div class="text-center py-5">
                        <div class="display-1 opacity-5 mb-3">📋</div>
                        <p class="text-muted italic"><?php echo __t('no_custom_field_subtext'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($champs as $index => $c): ?>
                            <div class="p-3 bg-white bg-opacity-50 border rounded-4 d-flex align-items-center justify-content-between hover-shadow transition-all">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center fw-black" style="width:32px; height:32px; font-size: 0.8rem;">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-gray-900"><?php echo htmlspecialchars($c['libelle']); ?></div>
                                        <div class="smaller text-muted text-uppercase fw-bold ls-1">
                                            <?php echo __t('Type:'); ?> <span class="text-success"><?php echo __t($c['type_champ']); ?></span> 
                                            <?php if($c['obligatoire']): ?>
                                                <span class="ms-2 badge bg-danger bg-opacity-10 text-danger rounded-pill" style="font-size:0.6rem;"><?php echo __t('OBLIGATORY'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <div class="btn-group">
                                        <a href="?id=<?php echo $id_concours; ?>&id_champ=<?php echo $c['id']; ?>&monter=1" class="btn btn-sm btn-white border px-2" title="<?php echo __t('Move Up'); ?>"><i class="bi bi-chevron-up"></i></a>
                                        <a href="?id=<?php echo $id_concours; ?>&id_champ=<?php echo $c['id']; ?>&descendre=1" class="btn btn-sm btn-white border px-2" title="<?php echo __t('Move Down'); ?>"><i class="bi bi-chevron-down"></i></a>
                                    </div>
                                    <a href="?id=<?php echo $id_concours; ?>&action=supprimer&id_champ=<?php echo $c['id']; ?>" class="btn btn-sm btn-danger rounded-circle d-flex align-items-center justify-content-center" style="width:30px; height:30px;" onclick="return confirm('<?php echo __t('confirm_delete_field'); ?>')" title="<?php echo __t('Delete'); ?>">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleOptions() {
    const type = document.getElementById('type_champ').value;
    const container = document.getElementById('options_container');
    if (type === 'liste' || type === 'case_a_cocher') {
        container.classList.remove('d-none');
    } else {
        container.classList.add('d-none');
    }
}
</script>

<?php 
include_footer();
?>

