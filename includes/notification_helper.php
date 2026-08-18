<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/mailer.php";

/**
 * Enregistre une notification en BDD et envoie un email si nécessaire.
 */
function create_notification(int $user_id, string $type, string $content, bool $send_email = false) {
    global $pdo;
    
    try {
        // 1. Insertion en BDD
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, content) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $type, $content]);
        
        // 2. Envoi d'email si demandé
        if ($send_email) {
            $stmt_user = $pdo->prepare("SELECT nom, email FROM utilisateurs WHERE id = ?");
            $stmt_user->execute([$user_id]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
            
            if ($user && !empty($user['email'])) {
                $nom = htmlspecialchars($user['nom']);
                $content_safe = htmlspecialchars($content);
                $link_dashboard = 'http://localhost/plateforme_recrutement/candidat/liste_opportunites.php';

                // Adapter couleur, icône et sujet selon le type
                if ($type === 'offre_modifiee') {
                    $color      = '#d97706';
                    $color_bg   = '#fffbeb';
                    $color_bord = '#fde68a';
                    $emoji      = '✏️';
                    $subject    = "✏️ Une offre que vous suivez a été modifiée";
                    $cta_label  = "Voir les modifications →";
                } elseif ($type === 'offre_cloturee') {
                    $color      = '#dc2626';
                    $color_bg   = '#fef2f2';
                    $color_bord = '#fecaca';
                    $emoji      = '🔒';
                    $subject    = "🔒 Une offre à laquelle vous avez postulé est clôturée";
                    $cta_label  = "Voir mes candidatures →";
                    $link_dashboard = 'http://localhost/plateforme_recrutement/candidat/mes_candidatures.php';
                } else {
                    $color      = '#2563eb';
                    $color_bg   = '#eff6ff';
                    $color_bord = '#bfdbfe';
                    $emoji      = '🔔';
                    $subject    = "🔔 Notification Admissio : " . ucfirst(strtr($type, '_', ' '));
                    $cta_label  = "Accéder à mon espace →";
                }

                $body = "
<div style='margin:0;padding:0;background:#f1f5f9;font-family:Calibri,Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0' style='max-width:620px;margin:30px auto;'>
    <!-- Header -->
    <tr>
      <td style='background:{$color};border-radius:16px 16px 0 0;padding:32px 40px;text-align:center;'>
        <h1 style='margin:0;color:#ffffff;font-size:26px;font-weight:900;letter-spacing:-0.5px;'>ADMISSIO</h1>
        <p style='margin:6px 0 0;color:rgba(255,255,255,0.75);font-size:13px;'>Plateforme de Recrutement Intelligente</p>
      </td>
    </tr>
    <!-- Body -->
    <tr>
      <td style='background:#ffffff;padding:36px 40px;'>
        <p style='margin:0 0 6px;color:#64748b;font-size:13px;text-transform:uppercase;letter-spacing:1px;font-weight:700;'>Bonjour {$nom},</p>
        <h2 style='margin:0 0 20px;color:#0f172a;font-size:20px;font-weight:900;'>{$emoji} Nouvelle notification</h2>

        <div style='background:{$color_bg};border:2px solid {$color_bord};border-radius:12px;padding:20px 24px;margin-bottom:28px;'>
          <p style='margin:0;color:#0f172a;font-size:15px;line-height:1.7;'>{$content_safe}</p>
        </div>

        <div style='text-align:center;margin-bottom:28px;'>
          <a href='{$link_dashboard}' style='display:inline-block;background:{$color};color:#ffffff;text-decoration:none;padding:13px 32px;border-radius:50px;font-size:14px;font-weight:700;'>{$cta_label}</a>
        </div>
      </td>
    </tr>
    <!-- Footer -->
    <tr>
      <td style='background:#f8fafc;border-radius:0 0 16px 16px;padding:18px 40px;text-align:center;border-top:1px solid #e2e8f0;'>
        <p style='margin:0;color:#94a3b8;font-size:11px;'>Message automatique — merci de ne pas y répondre.</p>
      </td>
    </tr>
  </table>
</div>";

                exec_send_email($user['email'], $subject, $body);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur Notification : " . $e->getMessage());
        return false;
    }
}

/**
 * Notifie tous les candidats dont le secteur correspond à une offre nouvellement activée.
 * Pour chaque candidat : notification BDD + e-mail HTML premium.
 *
 * @param int    $concours_id  L'ID de l'offre activée
 * @param string $titre        Titre de l'offre
 * @param string $secteur      Secteur de l'offre
 */
function notify_new_offer(int $concours_id, string $titre, string $secteur): void {
    global $pdo;

    if (empty($secteur)) return;

    try {
        // 1. Trouver les candidats dont le secteur correspond (matching bidirectionnel, insensible à la casse)
        $stmt_cands = $pdo->prepare("
            SELECT pc.id_utilisateur, u.nom, u.email
            FROM profils_candidats pc
            JOIN utilisateurs u ON u.id = pc.id_utilisateur AND u.role = 'candidat' AND u.statut = 'actif'
            WHERE pc.secteur_specialite IS NOT NULL
              AND pc.secteur_specialite != ''
              AND (
                LOWER(?) LIKE CONCAT('%', LOWER(pc.secteur_specialite), '%')
                OR LOWER(pc.secteur_specialite) LIKE CONCAT('%', LOWER(?), '%')
              )
        ");
        $stmt_cands->execute([$secteur, $secteur]);
        $candidats = $stmt_cands->fetchAll(PDO::FETCH_ASSOC);

        if (empty($candidats)) return;

        $link_offre = 'http://localhost/plateforme_recrutement/candidat/details_opportunite.php?id=' . $concours_id;
        $link_login  = 'http://localhost/plateforme_recrutement/candidat/liste_opportunites.php';
        $titre_safe  = htmlspecialchars($titre);
        $secteur_safe = htmlspecialchars($secteur);

        $stmt_notif = $pdo->prepare("INSERT INTO notifications (user_id, type, content) VALUES (?, 'nouvelle_offre', ?)");

        foreach ($candidats as $cand) {
            $user_id = (int)$cand['id_utilisateur'];
            $nom     = htmlspecialchars($cand['nom']);
            $email   = $cand['email'];

            // A. Notification en base de données
            $content = "Nouvelle opportunité dans votre domaine \"$secteur_safe\" : $titre_safe";
            $stmt_notif->execute([$user_id, $content]);

            // B. E-mail HTML premium
            if (!empty($email)) {
                $subject = "🎯 Nouvelle offre pour vous : $titre";
                $body = "
<div style='margin:0;padding:0;background:#f1f5f9;font-family:Calibri,Arial,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0' style='max-width:620px;margin:30px auto;'>

    <!-- Header -->
    <tr>
      <td style='background:linear-gradient(135deg,#0f5132 0%,#198754 100%);border-radius:16px 16px 0 0;padding:36px 40px;text-align:center;'>
        <h1 style='margin:0;color:#ffffff;font-size:28px;font-weight:900;letter-spacing:-0.5px;'>ADMISSIO</h1>
        <p style='margin:6px 0 0;color:#a7f3d0;font-size:13px;'>Plateforme de Recrutement Intelligente</p>
      </td>
    </tr>

    <!-- Body -->
    <tr>
      <td style='background:#ffffff;padding:36px 40px;'>
        <p style='margin:0 0 6px;color:#64748b;font-size:13px;text-transform:uppercase;letter-spacing:1px;font-weight:700;'>Bonjour {$nom},</p>
        <h2 style='margin:0 0 16px;color:#0f172a;font-size:22px;font-weight:900;'>Une offre correspond à votre profil 🎯</h2>
        <p style='margin:0 0 24px;color:#475569;font-size:15px;line-height:1.7;'>
          Une nouvelle opportunité vient d'être publiée dans le secteur <strong style='color:#0f5132;'>« {$secteur_safe} »</strong>
          et correspond à vos compétences.
        </p>

        <!-- Offer Card -->
        <div style='background:#f0fdf4;border:2px solid #bbf7d0;border-radius:12px;padding:24px;margin-bottom:28px;'>
          <p style='margin:0 0 4px;color:#64748b;font-size:11px;text-transform:uppercase;letter-spacing:1px;'>Opportunité</p>
          <h3 style='margin:0 0 10px;color:#0f5132;font-size:20px;font-weight:900;'>{$titre_safe}</h3>
          <p style='margin:0;color:#16a34a;font-size:13px;font-weight:600;'>📂 Secteur : {$secteur_safe}</p>
        </div>

        <!-- CTA -->
        <div style='text-align:center;margin-bottom:28px;'>
          <a href='{$link_offre}' style='display:inline-block;background:linear-gradient(135deg,#0f5132,#198754);color:#ffffff;text-decoration:none;padding:14px 36px;border-radius:50px;font-size:15px;font-weight:700;letter-spacing:0.3px;'>Voir l'offre et postuler →</a>
        </div>

        <p style='margin:0;color:#94a3b8;font-size:13px;text-align:center;'>
          Ou consultez toutes vos offres recommandées sur
          <a href='{$link_login}' style='color:#0f5132;font-weight:700;text-decoration:none;'>votre espace candidat</a>.
        </p>
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td style='background:#f8fafc;border-radius:0 0 16px 16px;padding:20px 40px;text-align:center;border-top:1px solid #e2e8f0;'>
        <p style='margin:0;color:#94a3b8;font-size:11px;'>Ceci est un message automatique généré par la plateforme Admissio.<br>Merci de ne pas y répondre directement.</p>
      </td>
    </tr>

  </table>
</div>";
                exec_send_email($email, $subject, $body);
            }
        }
    } catch (Exception $e) {
        error_log("[notify_new_offer] Erreur : " . $e->getMessage());
    }
}

/**
 * Récupère les notifications non lues pour un utilisateur.
 */
function get_unread_notifications_count(int $user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}
?>
