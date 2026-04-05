<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

// Action : Changer statut (Activer/Désactiver)
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_statut = ($_GET['action'] === 'activer') ? 'actif' : 'inactif';
    try {
        $pdo->prepare("UPDATE utilisateurs SET statut = ? WHERE id = ?")->execute([$new_statut, $id]);
        $_SESSION['success_message'] = "Statut de l'utilisateur mis à jour.";
        header("Location: gestion_utilisateurs.php");
        exit();
    } catch (PDOException $e) { send_error("Erreur serveur."); }
}

$search = trim($_GET['search'] ?? '');
$filtre_role = $_GET['role'] ?? '';

try {
    $sql = "SELECT id, nom, email, role, statut, date_creation FROM utilisateurs WHERE 1=1";
    $params = [];
    if (!empty($search)) {
        $sql .= " AND (nom LIKE ? OR email LIKE ?)";
        $params[] = "%$search%"; $params[] = "%$search%";
    }
    if (!empty($filtre_role)) {
        $sql .= " AND role = ?";
        $params[] = $filtre_role;
    }
    $sql .= " ORDER BY date_creation DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $users = []; }

include_header("Gestion des Utilisateurs");
?>

<div class="row animate__animated animate__fadeIn">
    <!-- En-tête -->
    <div class="col-12 mb-4">
        <div class="p-4 bg-white rounded-4 shadow-sm border-start border-dark border-5 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Répertoire des Acteurs 👥</h1>
                <p class="text-muted mb-0">Contrôlez les accès et supervisez tous les profils de la plateforme.</p>
            </div>
            <div class="d-none d-md-block fs-1 opacity-25">🛡️</div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="col-12 mb-4">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body p-4">
                <form method="GET" class="row g-4 align-items-end">
                    <div class="col-12 col-xl-7">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">Recherche globale d'utilisateur</label>
                        <div class="input-group input-group-lg shadow-sm">
                            <span class="input-group-text bg-white border-end-0 text-dark px-3"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control bg-white border-start-0 ps-0 fw-medium expand-on-focus" placeholder="Rechercher par nom ou email adress..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label small fw-bold text-uppercase text-muted mb-2 ls-1">Filtrage par type de compte</label>
                        <select name="role" class="form-select form-select-lg bg-white shadow-sm fw-medium">
                            <option value="">Tous les rôles</option>
                            <option value="admin" <?php echo $filtre_role === 'admin' ? 'selected' : ''; ?>>🛡️ Administrateurs</option>
                            <option value="gerant" <?php echo $filtre_role === 'gerant' ? 'selected' : ''; ?>>💼 Recruteurs</option>
                            <option value="candidat" <?php echo $filtre_role === 'candidat' ? 'selected' : ''; ?>>🎓 Candidats</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-2 d-flex gap-2">
                        <button type="submit" class="btn btn-dark btn-lg flex-grow-1 fw-bold shadow-sm rounded-pill">Filtrer</button>
                        <a href="gestion_utilisateurs.php" class="btn btn-light btn-lg border rounded-pill px-3" title="Reset">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Liste des Utilisateurs -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted text-uppercase small py-3">
                        <tr>
                            <th class="ps-4 py-3 border-0">Identité & Contact</th>
                            <th class="py-3 border-0 text-center">Rôle</th>
                            <th class="py-3 border-0 text-center">Inscrit le</th>
                            <th class="py-3 border-0 text-center col-statut">État</th>
                            <th class="pe-4 py-3 border-0 text-end col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="ps-4 py-4">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 me-3 fw-bold shadow-sm" style="width: 48px; height: 48px; display: flex; align-items:center; justify-content:center;">
                                            <?php echo strtoupper(substr($u['nom'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($u['nom']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($u['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 text-center">
                                    <?php 
                                        $role_icon = '🎓'; $role_color = 'slate';
                                        if ($u['role'] === 'admin') { $role_icon = '🛡️'; $role_color = 'indigo'; }
                                        elseif ($u['role'] === 'gerant') { $role_icon = '💼'; $role_color = 'blue'; }
                                    ?>
                                    <span class="badge bg-<?php echo $role_color; ?> bg-opacity-10 text-<?php echo $role_color; ?> border border-<?php echo $role_color; ?>-subtle rounded-pill">
                                        <?php echo $role_icon . ' ' . ($u['role'] === 'gerant' ? 'Recruteur' : ucfirst($u['role'])); ?>
                                    </span>
                                </td>
                                <td class="py-4 text-center">
                                    <div class="small fw-semibold text-secondary">
                                        <?php echo format_date($u['date_creation']); ?>
                                    </div>
                                </td>
                                <td class="text-center py-4 col-statut">
                                    <?php if ($u['statut'] === 'actif'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill">Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle rounded-pill">Bloqué</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 py-4 text-end col-actions">
                                    <?php if ($u['id'] != $_SESSION['id']): ?>
                                        <?php if ($u['statut'] === 'actif'): ?>
                                            <a href="?action=desactiver&id=<?php echo $u['id']; ?>" class="btn btn-light btn-sm rounded-pill px-3 text-danger fw-bold border">
                                                Bloquer
                                            </a>
                                        <?php else: ?>
                                            <a href="?action=activer&id=<?php echo $u['id']; ?>" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold border-0">
                                                Activer
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-3 py-1 rounded-pill small fw-bold">MOI</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="display-3 opacity-10 mb-3">👥</div>
                                    <p class="text-muted fw-bold">Aucun utilisateur trouvé.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
include_footer();
exit();
