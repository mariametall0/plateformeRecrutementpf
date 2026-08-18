<?php
require_once "../includes/layout.php";

// Protection admin
check_role('admin');

// Approuver ou refuser une demande
// Approuver ou refuser une demande (Logic mise à jour pour support Redirect)
if (isset($_GET["action"], $_GET["id"])) {
    $id_demande = (int)$_GET["id"];
    $action = $_GET["action"];
    if (in_array($action, ["approuver", "refuser"])) {
        try {
            $statut = ($action === "approuver") ? "approuvee" : "refusee";
            $pdo->prepare("UPDATE demandes_session SET statut = ? WHERE id = ?")->execute([$statut, $id_demande]);

            $msg = "Demande refusée.";
            if ($action === "approuver") {
                $stmt_dem = $pdo->prepare("SELECT * FROM demandes_session WHERE id = ?");
                $stmt_dem->execute([$id_demande]);
                $dem = $stmt_dem->fetch(PDO::FETCH_ASSOC);
                if ($dem) {
                    $pdo->prepare("INSERT INTO sessions_concours (id_concours, date_debut, date_fin, statut) VALUES (?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active')")->execute([$dem["id_concours"]]);
                }
                $msg = "Demande approuvée et session créée.";
            }

            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
                send_json(['success' => true, 'message' => $msg]);
            } else {
                $_SESSION['success_message'] = $msg;
                header("Location: demandes_session.php");
                exit();
            }
        } catch (PDOException $e) {
            send_error("Erreur base de données", 500);
        }
    }
}

// Récupération pour affichage
$demandes = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { /* Silencieux */ }

include_header("Demandes de Session");
?>

<div style="max-width: 1200px; margin: 0 auto;">
    <header style="margin-bottom: 2rem;">
        <h1>Demandes de Session Concours</h1>
        <p style="color: var(--text-muted);">Activez les nouvelles sessions de recrutement demandées par les gérants.</p>
    </header>

    <div class="stat-card" style="margin-bottom: 2rem; padding: 1rem;">
        <form method="GET" style="display: flex; gap: 1rem; align-items: flex-end;">
            <div style="width: 250px;">
                <label class="form-label">Statut</label>
                <select name="statut" class="form-control">
                    <option value="">Toutes les demandes</option>
                    <option value="en_attente" <?php echo $filtre === 'en_attente' ? 'selected' : ''; ?>>🕒 En attente</option>
                    <option value="approuvee" <?php echo $filtre === 'approuvee' ? 'selected' : ''; ?>>✅ Approuvées</option>
                    <option value="refusee" <?php echo $filtre === 'refusee' ? 'selected' : ''; ?>>❌ Refusées</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success" style="width: auto;">Filtrer</button>
            <a href="demandes_session.php" class="btn" style="width: auto; background: var(--border);">Réinitialiser</a>
        </form>
    </div>

    <div class="stat-card" style="padding: 0; overflow: hidden;">
        <?php if (empty($demandes)): ?>
            <div style="padding: 4rem; text-align: center;">
                <p style="color: var(--text-muted);">Aucune demande en attente.</p>
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--background); border-bottom: 1px solid var(--border);">
                        <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.875rem; color: var(--text-muted);">CONCOURS / GÉRANT</th>
                        <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.875rem; color: var(--text-muted);">DATE DEMANDE</th>
                        <th style="padding: 1rem 1.5rem; text-align: left; font-size: 0.875rem; color: var(--text-muted);">STATUT</th>
                        <th style="padding: 1rem 1.5rem; text-align: right; font-size: 0.875rem; color: var(--text-muted);">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($demandes as $d): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1.25rem 1.5rem;">
                                <div style="font-weight: 600;"><?php echo htmlspecialchars($d['concours_titre']); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($d['gerant_nom']); ?> (<?php echo htmlspecialchars($d['gerant_email']); ?>)</div>
                            </td>
                            <td style="padding: 1.25rem 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
                                <?php echo format_date($d['date_demande']); ?>
                            </td>
                            <td style="padding: 1.25rem 1.5rem;">
                                <?php 
                                    $class = 'alert-info'; $label = 'En attente';
                                    if ($d['statut'] === 'approuvee') { $class = 'alert-success'; $label = 'Approuvée'; }
                                    elseif ($d['statut'] === 'refusee') { $class = 'alert-danger'; $label = 'Refusée'; }
                                ?>
                                <span class="alert <?php echo $class; ?>" style="padding: 0.25rem 0.5rem; font-size: 0.75rem; margin: 0; display: inline-block;">
                                    <?php echo $label; ?>
                                </span>
                            </td>
                            <td style="padding: 1.25rem 1.5rem; text-align: right;">
                                <?php if ($d['statut'] === 'en_attente'): ?>
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                        <a href="?action=approuver&id=<?php echo $d['id']; ?>" class="btn" style="width: auto; background: var(--success); color: white; font-size: 0.75rem;">Approuver</a>
                                        <a href="?action=refuser&id=<?php echo $d['id']; ?>" class="btn" style="width: auto; background: var(--danger); color: white; font-size: 0.75rem;">Refuser</a>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: var(--text-muted); font-style: italic;">Traitée</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php 
include_footer();
exit();
?>

