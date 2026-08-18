<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$user_id = $_SESSION["id"];

// Marquer tout comme lu si demandé
if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    header("Location: notifications.php");
    exit();
}

// Supprimer une notification
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?")->execute([(int)$_GET['delete'], $user_id]);
    header("Location: notifications.php");
    exit();
}

// Récupération des notifications
$stmt = $pdo->prepare("SELECT *, DATE_FORMAT(created_at, '%d %M %Y à %H:%i') as date_fmt, DATE(created_at) as raw_date FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Groupement des notifications
$notifications_grouped = [
    'Aujourd\'hui' => [],
    'Hier' => [],
    'Plus ancien' => []
];

$today = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

foreach ($notifications as $n) {
    if ($n['raw_date'] === $today) {
        $notifications_grouped['Aujourd\'hui'][] = $n;
    } elseif ($n['raw_date'] === $yesterday) {
        $notifications_grouped['Hier'][] = $n;
    } else {
        $notifications_grouped['Plus ancien'][] = $n;
    }
}

include_header(__t('notifications'));
?>

<div class="premium-dashboard bg-light-soft min-vh-100 p-4 p-md-5">
    <div class="max-w-800 mx-auto">
        
        <!-- Header Section -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-5 gap-3 animate__animated animate__fadeIn">
            <div>
                <h1 class="fw-black text-dark mb-1" style="font-family: 'Outfit', sans-serif;"><?php echo __t('alerts_notifications'); ?></h1>
                <p class="text-muted small mb-0"><?php echo __t('stay_informed_subtext'); ?></p>
            </div>
            <?php if(!empty($notifications)): ?>
                <a href="?action=mark_all_read" class="btn btn-white shadow-premium rounded-pill px-4 fw-bold border-0 transition-all hover-scale">
                    <i class="bi bi-check2-all text-success me-2"></i> <?php echo __t('mark_all_read'); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if(empty($notifications)): ?>
            <div class="text-center py-5 animate__animated animate__fadeIn">
                <div class="empty-state-orb mx-auto mb-4 d-flex align-items-center justify-content-center bg-white shadow-sm border border-light rounded-circle" style="width: 120px; height: 120px;">
                    <i class="bi bi-bell-slash fs-1 text-muted opacity-25"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2"><?php echo __t('all_is_quiet'); ?></h4>
                <p class="text-muted small mb-4"><?php echo __t('no_notifications_subtext'); ?></p>
                <a href="dashboard.php" class="btn btn-success rounded-pill px-5 py-3 fw-bold shadow-premium border-0"><?php echo __t('back_to_dashboard'); ?></a>
            </div>
        <?php else: ?>
            <div class="notifications-container animate__animated animate__fadeInUp">
                <?php foreach($notifications_grouped as $group_title => $group_notifs): ?>
                    <?php if(!empty($group_notifs)): ?>
                        <div class="mb-5">
                            <h5 class="fw-bold text-muted mb-4 fs-6 text-uppercase ls-wide d-flex align-items-center gap-3">
                                <?php echo $group_title; ?>
                                <span class="flex-grow-1 border-top border-light border-2"></span>
                            </h5>
                            <div class="notifications-inbox bg-white border border-light-subtle rounded-4 shadow-sm overflow-hidden">
                                <?php foreach($group_notifs as $n): 
                                    $icon = "bi-info-circle-fill";
                                    $accent = "#3b82f6"; // var(--blue)
                                    if($n['type'] === 'new_message') { $icon = "bi-chat-dots-fill"; $accent = "#10b981"; }
                                    elseif($n['type'] === 'entretien') { $icon = "bi-calendar-check-fill"; $accent = "#f59e0b"; }
                                    elseif($n['type'] === 'status_change') { $icon = "bi-arrow-left-right"; $accent = "#8b5cf6"; }
                                    elseif($n['type'] === 'candidature_validee') { $icon = "bi-check-circle-fill"; $accent = "#10b981"; }
                                    elseif($n['type'] === 'candidature_refusee') { $icon = "bi-x-circle-fill"; $accent = "#ef4444"; }
                                    elseif($n['type'] === 'nouvelle_offre') { $icon = "bi-briefcase-fill"; $accent = "#0f5132"; }
                                    elseif($n['type'] === 'offre_cloturee') { $icon = "bi-lock-fill"; $accent = "#ef4444"; }
                                    elseif($n['type'] === 'offre_modifiee') { $icon = "bi-pencil-square"; $accent = "#f59e0b"; }

                                    // Parse content for structured display
                                    $title = htmlspecialchars($n['content']);
                                    $details = null;
                                    $link = "";
                                    $titre_offre = "";

                                    if ($n['type'] === 'entretien') {
                                        if (preg_match("/Nouvelle convocation pour l'opportunité '(.*?)' le (.*?) à (.*?)\. Lieu : (.*?)\. Notes : (.*)/u", $n['content'], $matches)) {
                                            $title = "Convocation pour l'opportunité : <strong>" . htmlspecialchars($matches[1]) . "</strong>";
                                            $details = [
                                                'date' => $matches[2] . ' à ' . $matches[3],
                                                'lieu' => $matches[4],
                                                'notes' => $matches[5]
                                            ];
                                            $titre_offre = $matches[1];
                                        } elseif (preg_match("/Nouvelle convocation pour l'opportunité '(.*?)' le (.*?) à (.*?)\. Lieu : (.*)/u", $n['content'], $matches)) {
                                            $title = "Convocation pour l'opportunité : <strong>" . htmlspecialchars($matches[1]) . "</strong>";
                                            $details = [
                                                'date' => $matches[2] . ' à ' . $matches[3],
                                                'lieu' => $matches[4]
                                            ];
                                            $titre_offre = $matches[1];
                                        }
                                        $link = "mes_candidatures.php";
                                    } elseif ($n['type'] === 'status_change' || $n['type'] === 'candidature_validee' || $n['type'] === 'candidature_refusee') {
                                        if (preg_match("/Votre candidature pour '(.*?)' a été mise à jour : Statut (.*)/u", $n['content'], $matches)) {
                                            $title = "Mise à jour de votre candidature pour <strong>" . htmlspecialchars($matches[1]) . "</strong>";
                                            $details = [
                                                'statut' => $matches[2]
                                            ];
                                            $titre_offre = $matches[1];
                                        }
                                        $link = "mes_candidatures.php";
                                    } elseif ($n['type'] === 'new_message') {
                                        if (preg_match("/concernant l['’]offre:\s*(.*)/ui", $n['content'], $matches)) {
                                            $title = "Nouveau message de l'équipe de recrutement";
                                            $details = [
                                                'sujet' => "Concernant l'offre : " . $matches[1]
                                            ];
                                            $titre_offre = trim($matches[1]);
                                        }
                                    }

                                    // Resolve link with candidature ID if possible
                                    if (!empty($titre_offre)) {
                                        $stmt_cand = $pdo->prepare("
                                            SELECT c.id 
                                            FROM candidatures c
                                            JOIN concours co ON c.id_concours = co.id
                                            WHERE c.id_candidat = ? AND (TRIM(co.titre) = ? OR co.titre LIKE ?)
                                            LIMIT 1
                                        ");
                                        $stmt_cand->execute([$user_id, $titre_offre, '%' . $titre_offre . '%']);
                                        $cand_id = $stmt_cand->fetchColumn();
                                        
                                        if ($cand_id) {
                                            if ($n['type'] === 'new_message') {
                                                $link = "messagerie.php?id=" . $cand_id;
                                            } else {
                                                $link = "mes_candidatures.php";
                                            }
                                        } else {
                                            $link = "mes_candidatures.php";
                                        }
                                    } else {
                                        if ($n['type'] === 'new_message' || $n['type'] === 'entretien' || $n['type'] === 'status_change' || $n['type'] === 'candidature_validee' || $n['type'] === 'candidature_refusee') {
                                            $link = "mes_candidatures.php";
                                        }
                                    }
                                ?>
                                    <div class="inbox-row d-flex align-items-center gap-3 py-3 px-3 border-bottom <?php echo $n['is_read'] == 0 ? 'unread-row' : ''; ?>">
                                        <!-- Left Icon & Status Dot -->
                                        <div class="d-flex align-items-center gap-2" style="flex-shrink: 0;">
                                            <?php if($n['is_read'] == 0): ?>
                                                <span class="unread-dot bg-success" style="width: 8px; height: 8px; border-radius: 50%; display: inline-block;" title="Nouveau"></span>
                                            <?php else: ?>
                                                <span class="unread-dot bg-transparent" style="width: 8px; height: 8px; border-radius: 50%; display: inline-block;"></span>
                                            <?php endif; ?>
                                            
                                            <div class="inbox-icon d-flex align-items-center justify-content-center rounded-3" style="background: <?php echo $accent; ?>12; color: <?php echo $accent; ?>; width: 36px; height: 36px;">
                                                <i class="bi <?php echo $icon; ?> fs-6"></i>
                                            </div>
                                        </div>

                                        <!-- Middle Content (Clickable) -->
                                        <a href="<?php echo $link ?: '#'; ?>" class="flex-grow-1 min-width-0 text-decoration-none inbox-link" <?php if(!$link) echo 'style="pointer-events: none; cursor: default;"'; ?>>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                <span class="inbox-badge-type" style="background: <?php echo $accent; ?>15; color: <?php echo $accent; ?>; font-size: 0.65rem; font-weight: 800; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;">
                                                    <?php echo strtr($n['type'], '_', ' '); ?>
                                                </span>
                                                <span class="text-muted d-none d-md-inline" style="font-size: 0.75rem;">•</span>
                                                <span class="inbox-subject text-dark" style="font-size: 0.9rem;">
                                                    <?php echo $title; ?>
                                                </span>
                                            </div>

                                            <?php if (!empty($details)): ?>
                                                <div class="inbox-details d-flex flex-wrap gap-x-3 gap-y-1 text-muted mt-1" style="font-size: 0.8rem;">
                                                    <?php if (isset($details['date'])): ?>
                                                        <span class="d-flex align-items-center gap-1">
                                                            <i class="bi bi-calendar-event text-secondary"></i>
                                                            <strong>Date :</strong> <?php echo htmlspecialchars($details['date']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (isset($details['lieu']) && $details['lieu'] !== ''): ?>
                                                        <span class="d-flex align-items-center gap-1">
                                                            <i class="bi bi-geo-alt text-secondary"></i>
                                                            <strong>Lieu :</strong> <?php echo htmlspecialchars($details['lieu']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (isset($details['notes']) && $details['notes'] !== ''): ?>
                                                        <span class="d-flex align-items-center gap-1 w-100 mt-1 text-secondary">
                                                            <i class="bi bi-chat-left-text text-secondary"></i>
                                                            <strong>Consignes :</strong> <span class="text-muted"><?php echo htmlspecialchars($details['notes']); ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (isset($details['statut'])): ?>
                                                        <?php 
                                                            $status_color = "text-secondary";
                                                            if (stripos($details['statut'], 'valide') !== false) $status_color = "text-success fw-bold";
                                                            elseif (stripos($details['statut'], 'rejete') !== false || stripos($details['statut'], 'refuse') !== false) $status_color = "text-danger fw-bold";
                                                        ?>
                                                        <span class="d-flex align-items-center gap-1">
                                                            <i class="bi bi-info-circle text-secondary"></i>
                                                            <strong>Nouveau statut :</strong> <span class="<?php echo $status_color; ?>"><?php echo htmlspecialchars($details['statut']); ?></span>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (isset($details['sujet'])): ?>
                                                        <span class="d-flex align-items-center gap-1 text-truncate">
                                                            <i class="bi bi-chat-right-text text-secondary"></i>
                                                            <?php echo htmlspecialchars($details['sujet']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="inbox-details-raw text-muted text-truncate" style="font-size: 0.8rem;">
                                                    <?php echo htmlspecialchars($n['content']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </a>

                                        <!-- Right Date & Delete -->
                                        <div class="d-flex align-items-center gap-3 text-nowrap" style="flex-shrink: 0;">
                                            <span class="text-muted small d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                                <i class="bi bi-clock"></i>
                                                <?php echo $n['date_fmt']; ?>
                                            </span>
                                            <a href="?delete=<?php echo $n['id']; ?>" class="inbox-btn-delete" title="Supprimer">
                                                <i class="bi bi-trash3-fill"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
.bg-light-soft { background: #f8fafc; }
.max-w-800 { max-width: 800px; }
.fw-black { font-weight: 950; }

.notifications-inbox {
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.02);
}
.inbox-row {
    transition: all 0.2s ease;
    border-color: #f1f5f9 !important;
}
.inbox-row:last-child {
    border-bottom: 0 !important;
}
.inbox-row:hover {
    background-color: #f8fafc;
}
.unread-row {
    background-color: #f4fbf7 !important; /* Soft green accent background */
    border-left: 4px solid #10b981 !important;
}
.unread-row:hover {
    background-color: #ecfdf5 !important;
}
.inbox-subject {
    letter-spacing: -0.1px;
}
.inbox-link {
    color: inherit !important;
    display: block;
}
.inbox-link:hover .inbox-subject {
    text-decoration: underline;
    color: #198754 !important; /* Admissio Green */
}
.inbox-btn-delete {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    color: #94a3b8;
    transition: all 0.2s;
    text-decoration: none;
    font-size: 0.95rem;
}
.inbox-row:hover .inbox-btn-delete {
    background: #fee2e2;
    color: #ef4444;
}
.inbox-btn-delete:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 6px rgba(239,68,68,0.15);
}

.hover-scale:hover { transform: translateY(-2px); }
.empty-state-orb { animation: float 6s ease-in-out infinite; }
@keyframes float { 0% { transform: translateY(0px); } 50% { transform: translateY(-10px); } 100% { transform: translateY(0px); } }
</style>

<?php include_footer(); ?>

