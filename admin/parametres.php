<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

// Si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf_token();
    
    if (isset($_POST['settings']) && is_array($_POST['settings'])) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE parametres_globaux SET valeur = ? WHERE cle = ?");
            
            foreach ($_POST['settings'] as $cle => $valeur) {
                // Pour les champs checkbox (booléens), s'ils ne sont pas cochés, ils ne sont pas envoyés
                // Donc on gèrera les booléens plus bas.
                $stmt->execute([$valeur, $cle]);
            }
            
            // Gestion des champs booléens (si non présents dans $_POST, alors 0)
            $stmtBool = $pdo->prepare("SELECT cle FROM parametres_globaux WHERE type_champ = 'boolean'");
            $stmtBool->execute();
            $booleans = $stmtBool->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($booleans as $boolCle) {
                $val = isset($_POST['settings'][$boolCle]) ? '1' : '0';
                $stmt->execute([$val, $boolCle]);
            }

            $pdo->commit();
            $_SESSION['success_message'] = "Les paramètres ont été mis à jour avec succès.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            send_error("Erreur lors de la mise à jour des paramètres : " . $e->getMessage());
        }
    }
    header("Location: parametres.php");
    exit();
}

// Récupérer les paramètres
try {
    $stmt = $pdo->query("SELECT * FROM parametres_globaux ORDER BY cle ASC");
    $parametres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $parametres = [];
}

include_header("Paramètres Globaux");
?>

<div class="mesh-bg"></div>

<div class="container-modern py-5">
    <!-- Header -->
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-4 mb-5 anim-up">
        <div>
            <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-8 fw-bold mb-3 ls-1">CONFIGURATION</span>
            <h1 class="display-6 fw-black text-gray-900 mb-1">Paramètres du Système</h1>
            <p class="text-muted mb-0">Gérez les configurations globales de la plateforme Admissio.</p>
        </div>
    </div>

    <!-- Formulaire des paramètres -->
    <form method="POST" action="parametres.php" class="anim-up anim-delay-1">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="bg-white p-5 rounded-4 shadow-premium">
                    <h5 class="fw-black text-gray-900 mb-4 border-bottom pb-3 small text-uppercase ls-1">Configuration Générale</h5>
                    
                    <div class="row g-4">
                        <?php foreach ($parametres as $param): ?>
                            <div class="col-12 <?php echo $param['type_champ'] === 'text' || $param['type_champ'] === 'number' ? 'col-md-6' : ''; ?>">
                                <?php if ($param['type_champ'] === 'boolean'): ?>
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" role="switch" 
                                               id="param_<?php echo $param['cle']; ?>" 
                                               name="settings[<?php echo $param['cle']; ?>]" 
                                               value="1" <?php echo $param['valeur'] === '1' ? 'checked' : ''; ?>
                                               style="width: 2.5rem; height: 1.25rem; cursor: pointer;">
                                        <label class="form-check-label ms-2 mt-1" for="param_<?php echo $param['cle']; ?>">
                                            <strong class="d-block text-gray-900"><?php echo htmlspecialchars($param['description']); ?></strong>
                                            <span class="small text-muted">Clé système : <?php echo $param['cle']; ?></span>
                                        </label>
                                    </div>
                                <?php elseif ($param['type_champ'] === 'textarea'): ?>
                                    <label class="form-label-pro" for="param_<?php echo $param['cle']; ?>"><?php echo htmlspecialchars($param['description']); ?></label>
                                    <textarea class="form-control-pro" 
                                              id="param_<?php echo $param['cle']; ?>" 
                                              name="settings[<?php echo $param['cle']; ?>]" 
                                              rows="3"><?php echo htmlspecialchars($param['valeur']); ?></textarea>
                                <?php else: ?>
                                    <label class="form-label-pro" for="param_<?php echo $param['cle']; ?>"><?php echo htmlspecialchars($param['description']); ?></label>
                                    <input type="<?php echo $param['type_champ'] === 'number' ? 'number' : 'text'; ?>" 
                                           class="form-control-pro" 
                                           id="param_<?php echo $param['cle']; ?>" 
                                           name="settings[<?php echo $param['cle']; ?>]" 
                                           value="<?php echo htmlspecialchars($param['valeur']); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex gap-3 mt-5 pt-4 border-top">
                        <button type="submit" class="btn-green-pill px-5 py-3 border-0 rounded-pill">
                            Enregistrer les paramètres <i class="bi bi-save ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="bg-blue-light border border-success border-opacity-25 p-4 rounded-4 shadow-sm h-100">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                            <i class="bi bi-info-circle-fill"></i>
                        </div>
                        <h6 class="fw-bold mb-0 text-gray-900">À propos des paramètres</h6>
                    </div>
                    <p class="small text-muted mb-3">
                        Ces réglages s'appliquent à l'ensemble de la plateforme Admissio.
                    </p>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-2"><strong>Mode maintenance</strong> : Coupe l'accès aux pages publiques pour les candidats et visiteurs.</li>
                        <li class="mb-2"><strong>Taille max</strong> : Limite la taille des fichiers uploadés par les candidats (CV, lettres de motivation).</li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>

<?php 
include_footer(); 
exit();
?>
