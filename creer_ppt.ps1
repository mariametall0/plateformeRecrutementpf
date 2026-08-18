# Script PowerShell - Soutenance PFE ADMISSIO - Design Premium
# Utilise l'objet COM PowerPoint pour generer un vrai fichier PPTX valide

$OutputPath = "C:\wamp64\www\plateforme_recrutement\soutenance_admissio.pptx"

function RGB-To-Long($r, $g, $b) { return $r + ($g * 256) + ($b * 256 * 256) }

# Palette de couleurs professionnelle
$C_VERT       = RGB-To-Long 15 81 50      # Vert foret (titre/accent)
$C_VERT_LIGHT = RGB-To-Long 236 253 245   # Vert tres clair (fond cartes)
$C_VERT_MED   = RGB-To-Long 16 185 129    # Vert vif (accent ligne)
$C_BLANC      = RGB-To-Long 255 255 255
$C_FOND       = RGB-To-Long 248 250 252   # Fond clair
$C_GRIS       = RGB-To-Long 100 116 139   # Texte secondaire
$C_GRIS_BORD  = RGB-To-Long 226 232 240   # Bordure
$C_NOIR       = RGB-To-Long 15 23 42      # Texte principal
$C_ORANGE     = RGB-To-Long 234 88 12     # Accent orange (demo)
$C_BLEU       = RGB-To-Long 37 99 235     # Accent bleu (lien)

# Lancer PowerPoint
try {
    $ppt = New-Object -ComObject PowerPoint.Application
    $ppt.Visible = [Microsoft.Office.Core.MsoTriState]::msoTrue
} catch {
    Write-Error "PowerPoint n'est pas installe. Erreur: $_"; exit 1
}

$pres = $ppt.Presentations.Add($true)
$pres.PageSetup.SlideWidth  = 960
$pres.PageSetup.SlideHeight = 540

function Add-Slide($pres) {
    $slide = $pres.Slides.Add($pres.Slides.Count + 1, 12)
    return $slide
}

function Add-TextBox($slide, $left, $top, $width, $height, $text, $size, $bold, $r, $g, $b, $align = 1) {
    $tb = $slide.Shapes.AddTextbox(1, $left, $top, $width, $height)
    $tf = $tb.TextFrame
    $tf.WordWrap = $true
    $tf.AutoSize = 0
    $tr = $tf.TextRange
    $tr.Text = $text
    $tr.Font.Size = $size
    $tr.Font.Bold = $bold
    $tr.Font.Color.RGB = RGB-To-Long $r $g $b
    $tr.Font.Name = "Calibri"
    $tf.TextRange.ParagraphFormat.Alignment = $align
    return $tb
}

function Add-Rect($slide, $left, $top, $width, $height, $r, $g, $b) {
    $shape = $slide.Shapes.AddShape(1, $left, $top, $width, $height)
    $shape.Fill.ForeColor.RGB = RGB-To-Long $r $g $b
    $shape.Fill.Solid()
    $shape.Line.Visible = 0
    return $shape
}

function Add-RoundedRect($slide, $left, $top, $width, $height, $r, $g, $b) {
    $shape = $slide.Shapes.AddShape(5, $left, $top, $width, $height)
    $shape.Fill.ForeColor.RGB = RGB-To-Long $r $g $b
    $shape.Fill.Solid()
    $shape.Line.Visible = 0
    return $shape
}

# Fond et barre laterale pour les slides de contenu
function Init-ContentSlide($pres, $titre, $sous_titre = "") {
    $s = Add-Slide $pres
    $s.Background.Fill.ForeColor.RGB = RGB-To-Long 248 250 252
    $s.Background.Fill.Solid()

    # Barre verticale gauche verte epaisse
    Add-Rect $s 0 0 8 540 15 81 50 | Out-Null

    # Bloc titre en haut
    Add-Rect $s 8 0 952 80 255 255 255 | Out-Null
    Add-TextBox $s 28 12 860 45 $titre 26 $true 15 81 50 1 | Out-Null

    if ($sous_titre -ne "") {
        Add-TextBox $s 28 54 860 22 $sous_titre 13 $false 100 116 139 1 | Out-Null
    }

    # Ligne de separation fine
    Add-Rect $s 8 80 952 3 16 185 129 | Out-Null

    # Pied de page
    Add-Rect $s 0 520 960 20 15 81 50 | Out-Null
    Add-TextBox $s 30 522 900 16 "ADMISSIO  |  Soutenance PFE 2026  |  Sup Management  |  M. Tourad" 9 $false 203 213 225 2 | Out-Null

    return $s
}

# ─────────────────────────────────────────────────────────────────────
# SLIDE 1 : PAGE DE GARDE
# ─────────────────────────────────────────────────────────────────────
$s1 = Add-Slide $pres
$s1.Background.Fill.ForeColor.RGB = RGB-To-Long 15 23 42
$s1.Background.Fill.Solid()

# Bande verte en haut
Add-Rect $s1 0 0 960 6 16 185 129 | Out-Null

# Grand bloc vert a gauche
Add-Rect $s1 0 0 400 540 15 81 50 | Out-Null

# Ligne d'accent diagonale simulee avec rectangle incline (horizontal)
Add-Rect $s1 0 380 400 4 16 185 129 | Out-Null

# Cote gauche : Nom du projet + Logo text
Add-TextBox $s1 30 60 340 100 "ADMISSIO" 60 $true 255 255 255 1 | Out-Null
Add-Rect $s1 30 170 280 3 16 185 129 | Out-Null
Add-TextBox $s1 30 180 340 50 "Plateforme Web de Recrutement" 16 $false 203 213 225 1 | Out-Null
Add-TextBox $s1 30 212 340 30 "Securisee et Intelligente" 16 $false 16 185 129 1 | Out-Null

Add-TextBox $s1 30 400 340 25 "Presente par : [Votre Nom Complet]" 13 $true 255 255 255 1 | Out-Null
Add-TextBox $s1 30 428 340 22 "Encadrant : M. Tourad" 12 $false 203 213 225 1 | Out-Null
Add-TextBox $s1 30 455 340 22 "Sup Management  |  Juin 2026" 11 $false 148 163 184 1 | Out-Null

# Cote droit : Informations contextuelles dans des cartes
$info_data = @(
    @("CONTEXTE", "Transformation numerique du recrutement"),
    @("TECHNOLOGIE", "PHP | MySQL | Bootstrap | Gemini AI"),
    @("OBJECTIF", "Centraliser et automatiser le recrutement"),
    @("ENCADRANT", "M. Tourad - Sup Management 2026")
)
$card_y = 60
foreach ($info in $info_data) {
    $card = $s1.Shapes.AddShape(5, 420, $card_y, 510, 85)
    $card.Fill.ForeColor.RGB = RGB-To-Long 30 41 59
    $card.Fill.Solid()
    $card.Line.ForeColor.RGB = RGB-To-Long 16 185 129
    $card.Line.Weight = 1.5

    $p1 = $card.TextFrame.TextRange.InsertAfter($info[0] + "`r`n")
    $p1.Font.Bold = $true
    $p1.Font.Size = 10
    $p1.Font.Color.RGB = RGB-To-Long 16 185 129
    $p1.Font.Name = "Calibri"

    $p2 = $card.TextFrame.TextRange.InsertAfter($info[1])
    $p2.Font.Bold = $false
    $p2.Font.Size = 13
    $p2.Font.Color.RGB = RGB-To-Long 226 232 240
    $p2.Font.Name = "Calibri"

    $card_y += 100
}

Write-Host "Slide 1 (Page de Garde) : OK"

# ─────────────────────────────────────────────────────────────────────
# SLIDE 2 : CONTEXTE & PROBLEMATIQUE
# ─────────────────────────────────────────────────────────────────────
$s2 = Init-ContentSlide $pres "Contexte & Problematique" "Pourquoi Admissio ? Quelle est la situation actuelle ?"

$problems = @(
    @("Processus Traditionnels", "Gestion manuelle lente, dossiers papier encombrants et e-mails eparpilles.", 255 237 213, 234 88 12),
    @("Limites pour les Candidats", "Absence de suivi en temps reel, frustration face aux longs delais de traitement.", 219 234 254, 37 99 235),
    @("Surcharges pour les RH", "Tri manuel de centaines de CV, ciblage de profils inadequat et perte de temps.", 255 237 213, 234 88 12),
    @("Solution Admissio", "Centraliser, automatiser le tri par IA et securiser les echanges.", 220 252 231, 15 81 50)
)

$box_y = 95
foreach ($p in $problems) {
    $label = $p[0]; $desc = $p[1]
    $bg_r = [int]$p[2]; $bg_g = [int]$p[3]; $bg_b = [int]$p[4]
    $ac_r = [int]$p[5]; $ac_g = [int]$p[6]; $ac_b = [int]$p[7]

    # Fond de la carte
    $card = $s2.Shapes.AddShape(5, 18, $box_y, 920, 90)
    $card.Fill.ForeColor.RGB = RGB-To-Long $bg_r $bg_g $bg_b
    $card.Fill.Solid()
    $card.Line.Visible = 0

    # Barre coloree gauche
    $bar = $s2.Shapes.AddShape(1, 18, $box_y, 6, 90)
    $bar.Fill.ForeColor.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $bar.Fill.Solid()
    $bar.Line.Visible = 0

    # Texte
    $tb_title = $s2.Shapes.AddTextbox(1, 35, $box_y + 8, 900, 30)
    $tb_title.TextFrame.TextRange.Text = $label
    $tb_title.TextFrame.TextRange.Font.Size = 15
    $tb_title.TextFrame.TextRange.Font.Bold = $true
    $tb_title.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $tb_title.TextFrame.TextRange.Font.Name = "Calibri"

    $tb_body = $s2.Shapes.AddTextbox(1, 35, $box_y + 38, 900, 40)
    $tb_body.TextFrame.TextRange.Text = $desc
    $tb_body.TextFrame.TextRange.Font.Size = 13
    $tb_body.TextFrame.TextRange.Font.Bold = $false
    $tb_body.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 30 41 59
    $tb_body.TextFrame.TextRange.Font.Name = "Calibri"

    $box_y += 100
}

Write-Host "Slide 2 (Contexte & Problematique) : OK"

# ─────────────────────────────────────────────────────────────────────
# SLIDE 3 : LA SOLUTION ADMISSIO & SES ACTEURS
# ─────────────────────────────────────────────────────────────────────
$s3 = Init-ContentSlide $pres "La Solution Admissio & Ses Acteurs" "Trois espaces distincts, securises et cloisonnes par roles (RBAC)"

$actors = @(
    @("CANDIDAT", "Cree son profil, importe son CV en PDF, suit l'etat de ses candidatures en temps reel.", 220 252 231, 15 81 50, "Inscription > Profil > CV > Postulation > Suivi"),
    @("RECRUTEUR (Gerant)", "Publie des offres, evalue les dossiers grace aux scores IA de matching, planifie les entretiens.", 219 234 254, 37 99 235, "Offre > Candidatures > Score IA > Entretien > Email"),
    @("ADMINISTRATEUR", "Supervise le systeme, valide les comptes entreprises et assure la securite globale.", 254 226 226, 185 28 28, "Gestion > Validation > Stats > Securite")
)

$cx = 20
foreach ($a in $actors) {
    $label = $a[0]; $desc = $a[1]
    $bg_r = [int]$a[2]; $bg_g = [int]$a[3]; $bg_b = [int]$a[4]
    $ac_r = [int]$a[5]; $ac_g = [int]$a[6]; $ac_b = [int]$a[7]
    $flux = $a[8]

    $card = $s3.Shapes.AddShape(5, $cx, 90, 300, 400)
    $card.Fill.ForeColor.RGB = RGB-To-Long 255 255 255
    $card.Fill.Solid()
    $card.Line.ForeColor.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $card.Line.Weight = 2
    $card.Line.Visible = $true

    # En-tete coloree
    $header = $s3.Shapes.AddShape(1, $cx, 90, 300, 60)
    $header.Fill.ForeColor.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $header.Fill.Solid()
    $header.Line.Visible = 0

    $tb_head = $s3.Shapes.AddTextbox(1, ($cx + 8), 97, 284, 46)
    $tb_head.TextFrame.TextRange.Text = $label
    $tb_head.TextFrame.TextRange.Font.Size = 14
    $tb_head.TextFrame.TextRange.Font.Bold = $true
    $tb_head.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 255 255 255
    $tb_head.TextFrame.TextRange.Font.Name = "Calibri"
    $tb_head.TextFrame.TextRange.ParagraphFormat.Alignment = 2

    $tb_desc = $s3.Shapes.AddTextbox(1, ($cx + 8), 160, 284, 180)
    $tb_desc.TextFrame.WordWrap = $true
    $tb_desc.TextFrame.TextRange.Text = $desc
    $tb_desc.TextFrame.TextRange.Font.Size = 13
    $tb_desc.TextFrame.TextRange.Font.Bold = $false
    $tb_desc.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 30 41 59
    $tb_desc.TextFrame.TextRange.Font.Name = "Calibri"

    # Flux bas de carte
    $flux_bg = $s3.Shapes.AddShape(1, $cx, 400, 300, 90)
    $flux_bg.Fill.ForeColor.RGB = RGB-To-Long $bg_r $bg_g $bg_b
    $flux_bg.Fill.Solid()
    $flux_bg.Line.Visible = 0

    $tb_flux = $s3.Shapes.AddTextbox(1, ($cx + 8), 405, 284, 80)
    $tb_flux.TextFrame.WordWrap = $true
    $tb_flux.TextFrame.TextRange.Text = $flux
    $tb_flux.TextFrame.TextRange.Font.Size = 11
    $tb_flux.TextFrame.TextRange.Font.Bold = $false
    $tb_flux.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $tb_flux.TextFrame.TextRange.Font.Name = "Calibri"

    $cx += 320
}

Write-Host "Slide 3 (La Solution & Acteurs) : OK"

# ─────────────────────────────────────────────────────────────────────
# SLIDE 4 : MODELISATION UML - SEQUENCE CLEE
# ─────────────────────────────────────────────────────────────────────
$s4 = Init-ContentSlide $pres "Modelisation UML : Diagramme de Sequence" "Focus sur le flux intelligent d'analyse de CV (Chapitre 3)"

# Etapes du diagramme de sequence
$etapes = @(
    @("1", "CANDIDAT", "Selectionne une offre et importe son CV (PDF/DOCX).", 15 81 50),
    @("2", "SERVEUR PHP", "Receptionne le fichier, valide le format et transmet a l'API Gemini.", 37 99 235),
    @("3", "API GEMINI (IA)", "Analyse semantiquement le CV, extrait : competences, diplomes, experiences. Retour JSON.", 124 58 237),
    @("4", "VALIDATION", "Formulaire pre-rempli affiche au candidat. Calcul du score de matching vs l'offre.", 15 81 50),
    @("5", "ENREGISTREMENT", "Donnees + score sauvegardes en BDD MySQL. Notification AJAX + e-mail PHPMailer au recruteur.", 37 99 235)
)

$ey = 90
foreach ($e in $etapes) {
    $num = $e[0]; $label = $e[1]; $desc = $e[2]
    $er = [int]$e[3]; $eg = [int]$e[4]; $eb = [int]$e[5]

    # Badge numero
    $badge = $s4.Shapes.AddShape(9, 15, $ey, 36, 36)
    $badge.Fill.ForeColor.RGB = RGB-To-Long $er $eg $eb
    $badge.Fill.Solid()
    $badge.Line.Visible = 0
    $badge.TextFrame.TextRange.Text = $num
    $badge.TextFrame.TextRange.Font.Bold = $true
    $badge.TextFrame.TextRange.Font.Size = 14
    $badge.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 255 255 255
    $badge.TextFrame.TextRange.Font.Name = "Calibri"
    $badge.TextFrame.TextRange.ParagraphFormat.Alignment = 2

    # Etiquette de l'acteur
    $tag = $s4.Shapes.AddShape(5, 58, ($ey + 2), 190, 30)
    $tag.Fill.ForeColor.RGB = RGB-To-Long $er $eg $eb
    $tag.Fill.Solid()
    $tag.Line.Visible = 0
    $tag.TextFrame.TextRange.Text = $label
    $tag.TextFrame.TextRange.Font.Bold = $true
    $tag.TextFrame.TextRange.Font.Size = 11
    $tag.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 255 255 255
    $tag.TextFrame.TextRange.Font.Name = "Calibri"
    $tag.TextFrame.TextRange.ParagraphFormat.Alignment = 2

    # Description
    $tb_d = $s4.Shapes.AddTextbox(1, 260, ($ey + 2), 680, 36)
    $tb_d.TextFrame.TextRange.Text = $desc
    $tb_d.TextFrame.TextRange.Font.Size = 13
    $tb_d.TextFrame.TextRange.Font.Bold = $false
    $tb_d.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 30 41 59
    $tb_d.TextFrame.TextRange.Font.Name = "Calibri"

    # Fleche de connection (sauf dernier)
    if ([int]$num -lt 5) {
        $arr = $s4.Shapes.AddShape(1, 33, ($ey + 38), 4, 30)
        $arr.Fill.ForeColor.RGB = RGB-To-Long 200 200 200
        $arr.Fill.Solid()
        $arr.Line.Visible = 0
    }

    $ey += 76
}

Write-Host "Slide 4 (Modelisation UML - Sequence) : OK"

# ─────────────────────────────────────────────────────────────────────
# SLIDE 5 : STRUCTURE DE LA BASE DE DONNEES
# ─────────────────────────────────────────────────────────────────────
$s5 = Init-ContentSlide $pres "Structure de la Base de Donnees (MySQL)" "Modele Logique de Donnees (MLD) - Chapitre 4"

$tables = @(
    @("UTILISATEURS", "id, nom, email, mot_de_passe, role, statut, date_creation", 15 81 50, 220 252 231),
    @("PROFILS_CANDIDATS", "#id_user, secteur_specialite, niveau_etude, date_naissance, cv_path", 37 99 235, 219 234 254),
    @("ENTREPRISES", "#id_user, nom_entreprise, registre_commerce, secteur_activite, ville", 124 58 237, 237 233 254),
    @("OFFRES", "id_offre, titre, description, type_contrat, date_cloture, #id_recruteur", 234 88 12, 255 237 213),
    @("CANDIDATURES", "id_candidature, date_postulation, statut, score_matching, #id_user, #id_offre", 15 81 50, 220 252 231),
    @("ENTRETIENS", "id_entretien, date, heure, lieu, statut, #id_candidature", 185 28 28, 254 226 226)
)

$tx = 15; $ty = 90; $col = 0
foreach ($t in $tables) {
    $tname = $t[0]; $tfields = $t[1]
    $h_r = [int]$t[2]; $h_g = [int]$t[3]; $h_b = [int]$t[4]
    $bg_r = [int]$t[5]; $bg_g = [int]$t[6]; $bg_b = [int]$t[7]

    $card = $s5.Shapes.AddShape(5, $tx, $ty, 300, 200)
    $card.Fill.ForeColor.RGB = RGB-To-Long $bg_r $bg_g $bg_b
    $card.Fill.Solid()
    $card.Line.ForeColor.RGB = RGB-To-Long $h_r $h_g $h_b
    $card.Line.Weight = 1.5
    $card.Line.Visible = $true

    $head = $s5.Shapes.AddShape(1, $tx, $ty, 300, 38)
    $head.Fill.ForeColor.RGB = RGB-To-Long $h_r $h_g $h_b
    $head.Fill.Solid()
    $head.Line.Visible = 0

    $tb_n = $s5.Shapes.AddTextbox(1, ($tx + 6), ($ty + 6), 288, 26)
    $tb_n.TextFrame.TextRange.Text = $tname
    $tb_n.TextFrame.TextRange.Font.Size = 12
    $tb_n.TextFrame.TextRange.Font.Bold = $true
    $tb_n.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 255 255 255
    $tb_n.TextFrame.TextRange.Font.Name = "Calibri"

    $tb_f = $s5.Shapes.AddTextbox(1, ($tx + 8), ($ty + 45), 280, 148)
    $tb_f.TextFrame.WordWrap = $true
    $tb_f.TextFrame.TextRange.Text = $tfields
    $tb_f.TextFrame.TextRange.Font.Size = 11
    $tb_f.TextFrame.TextRange.Font.Bold = $false
    $tb_f.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 30 41 59
    $tb_f.TextFrame.TextRange.Font.Name = "Calibri"

    $col++
    if ($col -eq 3) { $tx = 15; $ty += 215; $col = 0 }
    else { $tx += 315 }
}

Write-Host "Slide 5 (Base de Donnees) : OK"

# ─────────────────────────────────────────────────────────────────────
# SLIDE 6 : STACK TECHNOLOGIQUE & MOTEUR IA
# ─────────────────────────────────────────────────────────────────────
$s6 = Init-ContentSlide $pres "Stack Technologique & Moteur IA" "Choix techniques justifies - Chapitre 5 & 6"

$techs = @(
    @("BACK-END (PHP + PDO)", "Architecture MVC en PHP natif. Requetes preparees PDO pour securite totale. MySQL pour la persistance.", 15 81 50, 220 252 231),
    @("FRONT-END & AJAX", "Interface HTML5/CSS3 avec Bootstrap 5 et Elite UI. JavaScript asynchrone (Fetch API) sans rechargements.", 37 99 235, 219 234 254),
    @("MOTEUR IA - GEMINI API", "Parsing semantique du CV via l'API Google Gemini. Scoring de matching deterministe (Temperature 0.1).", 124 58 237, 237 233 254),
    @("RESILIENCE & SERVICES", "Cascade de modeles (2.5-flash > 2.0-flash). Mode degrade local. PHPMailer (SMTP) pour convocations.", 234 88 12, 255 237 213)
)

$ty6 = 95
foreach ($tech in $techs) {
    $label = $tech[0]; $desc = $tech[1]
    $ac_r = [int]$tech[2]; $ac_g = [int]$tech[3]; $ac_b = [int]$tech[4]
    $bg_r = [int]$tech[5]; $bg_g = [int]$tech[6]; $bg_b = [int]$tech[7]

    $card = $s6.Shapes.AddShape(5, 15, $ty6, 928, 95)
    $card.Fill.ForeColor.RGB = RGB-To-Long $bg_r $bg_g $bg_b
    $card.Fill.Solid()
    $card.Line.Visible = 0

    $bar = $s6.Shapes.AddShape(1, 15, $ty6, 8, 95)
    $bar.Fill.ForeColor.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $bar.Fill.Solid()
    $bar.Line.Visible = 0

    $tb_t = $s6.Shapes.AddTextbox(1, 32, ($ty6 + 8), 900, 28)
    $tb_t.TextFrame.TextRange.Text = $label
    $tb_t.TextFrame.TextRange.Font.Size = 14
    $tb_t.TextFrame.TextRange.Font.Bold = $true
    $tb_t.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $tb_t.TextFrame.TextRange.Font.Name = "Calibri"

    $tb_d = $s6.Shapes.AddTextbox(1, 32, ($ty6 + 42), 900, 46)
    $tb_d.TextFrame.WordWrap = $true
    $tb_d.TextFrame.TextRange.Text = $desc
    $tb_d.TextFrame.TextRange.Font.Size = 13
    $tb_d.TextFrame.TextRange.Font.Bold = $false
    $tb_d.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 30 41 59
    $tb_d.TextFrame.TextRange.Font.Name = "Calibri"

    $ty6 += 104
}

Write-Host "Slide 6 (Stack & IA) : OK"

# ─────────────────────────────────────────────────────────────────────
# SLIDE 7 : DEMONSTRATION EN DIRECT
# ─────────────────────────────────────────────────────────────────────
$s7 = Add-Slide $pres
$s7.Background.Fill.ForeColor.RGB = RGB-To-Long 15 23 42
$s7.Background.Fill.Solid()

# Bande verte top
Add-Rect $s7 0 0 960 5 16 185 129 | Out-Null

# Titre
Add-TextBox $s7 50 25 860 50 "Demonstration en Direct" 32 $true 255 255 255 2 | Out-Null
Add-TextBox $s7 50 72 860 25 "Lancement de l'application Admissio sur WampServer" 15 $false 148 163 184 2 | Out-Null

# Ligne separatrice
Add-Rect $s7 50 105 860 2 16 185 129 | Out-Null

# Bloc central URL
$url_box = $s7.Shapes.AddShape(5, 100, 125, 760, 100)
$url_box.Fill.ForeColor.RGB = RGB-To-Long 30 41 59
$url_box.Fill.Solid()
$url_box.Line.ForeColor.RGB = RGB-To-Long 16 185 129
$url_box.Line.Weight = 2
$url_box.Line.Visible = $true

Add-TextBox $s7 115 135 730 30 "URL DE LANCEMENT :" 11 $true 16 185 129 2 | Out-Null
$tb = Add-TextBox $s7 115 162 730 45 "http://localhost/plateforme_recrutement/" 22 $true 255 255 255 2
$tb.ActionSettings.Item(1).Hyperlink.Address = "http://localhost/plateforme_recrutement/"

# Les 3 parcours a montrer
$parcours = @(
    @("PARCOURS CANDIDAT", "Inscription > Import CV > Extraction IA > Soumission candidature", 16 185 129),
    @("PARCOURS RECRUTEUR", "Dashboard > Score matching > Messagerie > Convocation e-mail", 37 99 235),
    @("VERIFICATION EMAIL", "Reception instantanee de la convocation via SMTP PHPMailer", 234 88 12)
)

$py = 240; $px = 30
foreach ($par in $parcours) {
    $pt = $par[0]; $pd = $par[1]
    $pr = [int]$par[2]; $pg = [int]$par[3]; $pb = [int]$par[4]

    $pcard = $s7.Shapes.AddShape(5, $px, $py, 295, 120)
    $pcard.Fill.ForeColor.RGB = RGB-To-Long 30 41 59
    $pcard.Fill.Solid()
    $pcard.Line.ForeColor.RGB = RGB-To-Long $pr $pg $pb
    $pcard.Line.Weight = 2
    $pcard.Line.Visible = $true

    $bar_p = $s7.Shapes.AddShape(1, $px, $py, 295, 8)
    $bar_p.Fill.ForeColor.RGB = RGB-To-Long $pr $pg $pb
    $bar_p.Fill.Solid()
    $bar_p.Line.Visible = 0

    $tb_pt = $s7.Shapes.AddTextbox(1, ($px + 8), ($py + 18), 279, 25)
    $tb_pt.TextFrame.TextRange.Text = $pt
    $tb_pt.TextFrame.TextRange.Font.Size = 12
    $tb_pt.TextFrame.TextRange.Font.Bold = $true
    $tb_pt.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long $pr $pg $pb
    $tb_pt.TextFrame.TextRange.Font.Name = "Calibri"

    $tb_pd = $s7.Shapes.AddTextbox(1, ($px + 8), ($py + 50), 279, 62)
    $tb_pd.TextFrame.WordWrap = $true
    $tb_pd.TextFrame.TextRange.Text = $pd
    $tb_pd.TextFrame.TextRange.Font.Size = 12
    $tb_pd.TextFrame.TextRange.Font.Bold = $false
    $tb_pd.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 203 213 225
    $tb_pd.TextFrame.TextRange.Font.Name = "Calibri"

    $px += 325
}

# Pied de page
Add-Rect $s7 0 520 960 20 15 81 50 | Out-Null
Add-TextBox $s7 30 522 900 16 "ADMISSIO  |  Soutenance PFE 2026  |  Sup Management  |  M. Tourad" 9 $false 203 213 225 2 | Out-Null

Write-Host "Slide 7 (Demo en Direct) : OK"

# ─────────────────────────────────────────────────────────────────────
# SLIDE 8 : SECURISATION DE LA PLATEFORME
# ─────────────────────────────────────────────────────────────────────
$s8 = Init-ContentSlide $pres "Securisation de la Plateforme (OWASP)" "Application des normes OWASP Top 10 - Chapitre 7"

$secu = @(
    @("Injections SQL", "Requetes preparees PDO avec parametres types. Aucune concatenation directe dans les requetes.", 185 28 28, 254 226 226),
    @("Failles XSS", "Nettoyage de toutes les sorties utilisateur via htmlspecialchars(). Encodage systematique.", 234 88 12, 255 237 213),
    @("Attaques CSRF", "Jetons de session cryptographiques uniques generes et valides a chaque formulaire.", 124 58 237, 237 233 254),
    @("Hachage Bcrypt", "Mots de passe haches via password_hash() avec Bcrypt. Jamais stockes en clair.", 37 99 235, 219 234 254),
    @("Securite des Uploads", "Filtrage MIME strict (.pdf uniquement), taille max 5 Mo et renommage aleatoire des fichiers.", 15 81 50, 220 252 231)
)

$sy = 90
foreach ($sec in $secu) {
    $st = $sec[0]; $sd = $sec[1]
    $ac_r = [int]$sec[2]; $ac_g = [int]$sec[3]; $ac_b = [int]$sec[4]
    $bg_r = [int]$sec[5]; $bg_g = [int]$sec[6]; $bg_b = [int]$sec[7]

    $scard = $s8.Shapes.AddShape(5, 15, $sy, 928, 76)
    $scard.Fill.ForeColor.RGB = RGB-To-Long $bg_r $bg_g $bg_b
    $scard.Fill.Solid()
    $scard.Line.Visible = 0

    $sbar = $s8.Shapes.AddShape(1, 15, $sy, 6, 76)
    $sbar.Fill.ForeColor.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $sbar.Fill.Solid()
    $sbar.Line.Visible = 0

    $tb_st = $s8.Shapes.AddTextbox(1, 30, ($sy + 5), 200, 26)
    $tb_st.TextFrame.TextRange.Text = $st
    $tb_st.TextFrame.TextRange.Font.Size = 13
    $tb_st.TextFrame.TextRange.Font.Bold = $true
    $tb_st.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $tb_st.TextFrame.TextRange.Font.Name = "Calibri"

    $tb_sd = $s8.Shapes.AddTextbox(1, 240, ($sy + 5), 700, 60)
    $tb_sd.TextFrame.WordWrap = $true
    $tb_sd.TextFrame.TextRange.Text = $sd
    $tb_sd.TextFrame.TextRange.Font.Size = 13
    $tb_sd.TextFrame.TextRange.Font.Bold = $false
    $tb_sd.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 30 41 59
    $tb_sd.TextFrame.TextRange.Font.Name = "Calibri"

    $sy += 84
}

Write-Host "Slide 8 (Securite OWASP) : OK"

# ─────────────────────────────────────────────────────────────────────
# SLIDE 9 : TESTS & RESULTATS
# ─────────────────────────────────────────────────────────────────────
$s9 = Init-ContentSlide $pres "Tests & Resultats" "Validation experimentale de la plateforme - Chapitre 8"

# 4 metriques en grandes cartes
$metrics = @(
    @("100%", "Tests Fonctionnels", "Tous les flux critiques valides : inscription, postulation, messagerie, convocations.", 15 81 50, 220 252 231),
    @("90%", "Precision de l'IA", "Taux de reussite de l'extraction semantique des CV par l'API Google Gemini.", 124 58 237, 237 233 254),
    @("< 8%", "Ecart vs Humain", "Deviation entre le score IA et l'evaluation manuelle d'un recruteur sur le meme dossier.", 37 99 235, 219 234 254),
    @("< 3s", "Temps de Reponse", "Duree moyenne entre l'import du CV et l'affichage du score de matching.", 234 88 12, 255 237 213)
)

$mx = 18
foreach ($m in $metrics) {
    $val = $m[0]; $title = $m[1]; $desc = $m[2]
    $ac_r = [int]$m[3]; $ac_g = [int]$m[4]; $ac_b = [int]$m[5]
    $bg_r = [int]$m[6]; $bg_g = [int]$m[7]; $bg_b = [int]$m[8]

    $mcard = $s9.Shapes.AddShape(5, $mx, 90, 222, 400)
    $mcard.Fill.ForeColor.RGB = RGB-To-Long 255 255 255
    $mcard.Fill.Solid()
    $mcard.Line.ForeColor.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $mcard.Line.Weight = 2
    $mcard.Line.Visible = $true

    $mhead = $s9.Shapes.AddShape(1, $mx, 90, 222, 130)
    $mhead.Fill.ForeColor.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $mhead.Fill.Solid()
    $mhead.Line.Visible = 0

    $tb_val = $s9.Shapes.AddTextbox(1, ($mx + 5), 100, 212, 80)
    $tb_val.TextFrame.TextRange.Text = $val
    $tb_val.TextFrame.TextRange.Font.Size = 52
    $tb_val.TextFrame.TextRange.Font.Bold = $true
    $tb_val.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 255 255 255
    $tb_val.TextFrame.TextRange.Font.Name = "Calibri"
    $tb_val.TextFrame.TextRange.ParagraphFormat.Alignment = 2

    $tb_mt = $s9.Shapes.AddTextbox(1, ($mx + 5), 235, 212, 60)
    $tb_mt.TextFrame.TextRange.Text = $title
    $tb_mt.TextFrame.TextRange.Font.Size = 14
    $tb_mt.TextFrame.TextRange.Font.Bold = $true
    $tb_mt.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $tb_mt.TextFrame.TextRange.Font.Name = "Calibri"
    $tb_mt.TextFrame.TextRange.ParagraphFormat.Alignment = 2

    $tb_md = $s9.Shapes.AddTextbox(1, ($mx + 8), 305, 206, 178)
    $tb_md.TextFrame.WordWrap = $true
    $tb_md.TextFrame.TextRange.Text = $desc
    $tb_md.TextFrame.TextRange.Font.Size = 12
    $tb_md.TextFrame.TextRange.Font.Bold = $false
    $tb_md.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 71 85 105
    $tb_md.TextFrame.TextRange.Font.Name = "Calibri"

    $mx += 236
}

Write-Host "Slide 9 (Tests & Resultats) : OK"

# ─────────────────────────────────────────────────────────────────────
# SLIDE 10 : CONCLUSION & PERSPECTIVES
# ─────────────────────────────────────────────────────────────────────
$s10 = Init-ContentSlide $pres "Conclusion & Perspectives" "Bilan du projet et axes d'amelioration futurs"

# Bilan
$bilan_box = $s10.Shapes.AddShape(5, 15, 90, 928, 130)
$bilan_box.Fill.ForeColor.RGB = RGB-To-Long 220 252 231
$bilan_box.Fill.Solid()
$bilan_box.Line.ForeColor.RGB = RGB-To-Long 15 81 50
$bilan_box.Line.Weight = 2
$bilan_box.Line.Visible = $true

Add-TextBox $s10 28 97 900 28 "BILAN DU PROJET" 14 $true 15 81 50 1 | Out-Null
Add-TextBox $s10 28 128 900 80 "Objectifs pleinement atteints. La plateforme Admissio est fonctionnelle, securisee et intelligente. Elle automatise le tri par IA avec 90% de precision et assure une gestion complete du cycle de recrutement." 13 $false 30 41 59 1 | Out-Null

# Perspectives
$perspectives = @(
    @("Entretiens Video", "Integration d'un module WebRTC natif pour realiser les entretiens directement sur la plateforme.", 37 99 235, 219 234 254),
    @("Application Mobile", "Application compagnon iOS/Android pour les candidats avec notifications push.", 124 58 237, 237 233 254),
    @("Integration LinkedIn", "Synchronisation avec l'API LinkedIn pour importer un profil professionnel en 1 clic.", 15 81 50, 220 252 231)
)

$persp_x = 15
foreach ($persp in $perspectives) {
    $pt = $persp[0]; $pd = $persp[1]
    $ac_r = [int]$persp[2]; $ac_g = [int]$persp[3]; $ac_b = [int]$persp[4]
    $bg_r = [int]$persp[5]; $bg_g = [int]$persp[6]; $bg_b = [int]$persp[7]

    $pcard = $s10.Shapes.AddShape(5, $persp_x, 235, 302, 250)
    $pcard.Fill.ForeColor.RGB = RGB-To-Long $bg_r $bg_g $bg_b
    $pcard.Fill.Solid()
    $pcard.Line.ForeColor.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $pcard.Line.Weight = 2
    $pcard.Line.Visible = $true

    $ph = $s10.Shapes.AddShape(1, $persp_x, 235, 302, 50)
    $ph.Fill.ForeColor.RGB = RGB-To-Long $ac_r $ac_g $ac_b
    $ph.Fill.Solid()
    $ph.Line.Visible = 0

    $ptb1 = $s10.Shapes.AddTextbox(1, ($persp_x + 8), 245, 286, 32)
    $ptb1.TextFrame.TextRange.Text = "PERSPECTIVE > " + $pt
    $ptb1.TextFrame.TextRange.Font.Size = 12
    $ptb1.TextFrame.TextRange.Font.Bold = $true
    $ptb1.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 255 255 255
    $ptb1.TextFrame.TextRange.Font.Name = "Calibri"

    $ptb2 = $s10.Shapes.AddTextbox(1, ($persp_x + 8), 298, 286, 180)
    $ptb2.TextFrame.WordWrap = $true
    $ptb2.TextFrame.TextRange.Text = $pd
    $ptb2.TextFrame.TextRange.Font.Size = 13
    $ptb2.TextFrame.TextRange.Font.Bold = $false
    $ptb2.TextFrame.TextRange.Font.Color.RGB = RGB-To-Long 30 41 59
    $ptb2.TextFrame.TextRange.Font.Name = "Calibri"

    $persp_x += 317
}

Write-Host "Slide 10 (Conclusion & Perspectives) : OK"

# ─────────────────────────────────────────────────────────────────────
# SLIDE 11 : MERCI
# ─────────────────────────────────────────────────────────────────────
$s11 = Add-Slide $pres
$s11.Background.Fill.ForeColor.RGB = RGB-To-Long 15 23 42
$s11.Background.Fill.Solid()

# Bande verte top et bottom
Add-Rect $s11 0 0 960 6 16 185 129 | Out-Null
Add-Rect $s11 0 534 960 6 16 185 129 | Out-Null

# Bloc vert central
$center = $s11.Shapes.AddShape(5, 230, 90, 500, 320)
$center.Fill.ForeColor.RGB = RGB-To-Long 15 81 50
$center.Fill.Solid()
$center.Line.ForeColor.RGB = RGB-To-Long 16 185 129
$center.Line.Weight = 3
$center.Line.Visible = $true

Add-TextBox $s11 230 110 500 100 "Merci pour votre attention !" 34 $true 255 255 255 2 | Out-Null
Add-Rect $s11 280 220 400 3 16 185 129 | Out-Null
Add-TextBox $s11 230 230 500 40 "Place aux questions du jury." 20 $false 203 213 225 2 | Out-Null
Add-TextBox $s11 230 310 500 35 "M. Tourad  |  Sup Management  |  Juin 2026" 14 $false 148 163 184 2 | Out-Null

Add-TextBox $s11 50 435 860 40 "ADMISSIO  |  Plateforme Web de Recrutement Securisee et Intelligente" 13 $false 100 116 139 2 | Out-Null

Write-Host "Slide 11 (Merci) : OK"

# ─────────────────────────────────────────────────────────────────────
# SAUVEGARDE
# ─────────────────────────────────────────────────────────────────────
Write-Host "`nSauvegarde en cours..."
Start-Sleep -Seconds 2
$pres.SaveAs($OutputPath, 24)
$pres.Close()
$ppt.Quit()

[System.Runtime.Interopservices.Marshal]::ReleaseComObject($pres) | Out-Null
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($ppt)  | Out-Null
[System.GC]::Collect()
[System.GC]::WaitForPendingFinalizers()

Write-Host "`n=========================================="
Write-Host "Fichier genere avec succes !"
Write-Host "Emplacement : $OutputPath"
Write-Host "Nombre de slides : 11"
Write-Host "=========================================="
