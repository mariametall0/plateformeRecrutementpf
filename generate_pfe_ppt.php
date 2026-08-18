<?php
/**
 * Generation du fichier PPTX pour la soutenance PFE - Projet ADMISSIO
 * Utilise ZipArchive + OpenXML (format natif PowerPoint) sans dependance externe.
 */

$output_path = __DIR__ . '/soutenance_pfe.pptx';
$tmp_dir = sys_get_temp_dir() . '/pfe_pptx_' . time();
mkdir($tmp_dir, 0777, true);

// Couleurs
$C_GREEN      = '0F5132';
$C_SLATE      = '64748B';
$C_DARK       = '0F172A';
$C_LIGHT_BG   = 'F8FAFC';
$C_WHITE      = 'FFFFFF';
$C_BORDER     = 'E2E8F0';
$C_SUCCESS_BG = 'ECFDF5';

// Slides
$slides = [];

// Slide 1 - Page de Garde
$slides[] = slide_title_cover();

// Slide 2 - Contexte &amp; Problématique
$slides[] = slide_bullets(
    'Contexte &amp; Problematique',
    [
        ['head' => 'Processus traditionnels',   'body' => 'Gestion manuelle lente, dossiers papier encombrants et e-mails eparpilles.'],
        ['head' => 'Limites pour les Candidats', 'body' => 'Deplacements couteux, absence de suivi et frustration due aux delais.'],
        ['head' => 'Surcharges pour les RH',    'body' => 'Tri manuel de centaines de CV, perte de temps et difficulte a cibler.'],
        ['head' => 'Objectif de la Solution',   'body' => 'Centraliser, automatiser la decision et securiser les profils candidats.'],
    ],
    $C_LIGHT_BG
);

// Slide 3 - La Solution Admissio &amp; Ses Acteurs
$slides[] = slide_actors();

// Slide 4 - Modélisation UML (Le Cœur du Processus)
$slides[] = slide_bullets(
    'Modelisation UML : Sequence de l\'IA',
    [
        ['head' => 'Focalisation UML',       'body' => 'Mise en evidence du flux d\'extraction et de matching automatique du CV.'],
        ['head' => 'Etape 1 - Televersement', 'body' => 'Le Candidat importe son CV en PDF ➔ Le serveur PHP receptionne le fichier.'],
        ['head' => 'Etape 2 - Analyse IA',    'body' => 'Le serveur transmet le document a l\'API Gemini ➔ Extraction JSON structuree.'],
        ['head' => 'Etape 3 - Validation',    'body' => 'Affichage des competences pre-remplies au Candidat pour validation/correction.'],
    ],
    $C_LIGHT_BG
);

// Slide 5 - Conception &amp; Base de Données
$slides[] = slide_bullets(
    'Structure de la Base de Donnees (MySQL)',
    [
        ['head' => 'UTILISATEURS',      'body' => 'id, nom, email, mot_de_passe, role, statut, date_creation (Heritage parent).'],
        ['head' => 'PROFILS &amp; BOITES',  'body' => 'profils_candidats (#id_user, secteur, etudes) &amp; entreprises (#id_user, nom, registre).'],
        ['head' => 'OFFRES &amp; CANDIDATS','body' => 'offres (id_offre, titre, id_recruteur) &amp; candidatures (id_candidature, score_matching).'],
        ['head' => 'ENTRETIENS',        'body' => 'id_entretien, date, heure, lieu, statut, #id_candidature (Liaison finale).'],
    ],
    $C_LIGHT_BG
);

// Slide 6 - Stack Technique &amp; Integration de l'IA
$slides[] = slide_bullets(
    'Stack Technologique &amp; Moteur IA',
    [
        ['head' => 'Back-end &amp; Base SQL',  'body' => 'Architecture PHP MVC avec transactions PDO securisees et base relationnelle MySQL.'],
        ['head' => 'Front-end &amp; AJAX',     'body' => 'Interface HTML5/CSS3 (Bootstrap 5) avec Fetch API (AJAX) pour eviter les rechargements.'],
        ['head' => 'Moteur IA (Gemini API)', 'body' => 'Intégration de l\'API Google Gemini pour le parsing sémantique des CV candidats.'],
        ['head' => 'Prompt &amp; Resilience',  'body' => 'Temperature 0.1 pour le determinisme + cascade de modeles de secours et mode degrade.'],
    ],
    $C_LIGHT_BG
);

// Slide 7 - Lancement Démo (Lien Direct)
$slides[] = slide_bullets(
    'Demonstration en Direct',
    [
        ['head' => 'Lancer l\'application',  'body' => 'Lien direct pour ouvrir et presenter le site web en conditions réelles.'],
        ['head' => 'URL Locale de test',    'body' => 'http://localhost/plateforme_recrutement/ (WampServer local).'],
        ['head' => 'Parcours a valider',    'body' => 'Candidat (upload CV, extraction, score) ➔ Recruteur (dashboard, convocation email).'],
        ['head' => 'SMTP PHPMailer',        'body' => 'Verification de la reception instantanee des e-mails lors de la planification.'],
    ],
    $C_LIGHT_BG
);

// Slide 8 - Sécurisation de la Plateforme (OWASP)
$slides[] = slide_bullets(
    'Mesures de Securite (OWASP)',
    [
        ['head' => 'Injections SQL',       'body' => 'Totalement ecartees par l\'utilisation systematique des requetes preparees PDO.'],
        ['head' => 'Failles XSS',           'body' => 'Nettoyage de toutes les variables et sorties utilisateur via la fonction htmlspecialchars().'],
        ['head' => 'Attaques CSRF',         'body' => 'Generation et controle de tokens uniques injectes dans les formulaires de session.'],
        ['head' => 'Hachage Bcrypt',        'body' => 'Mots de passe haches via password_hash() avec l\'algorithme Bcrypt.'],
        ['head' => 'Securite des Uploads',  'body' => 'Filtrage des extensions (.pdf uniquement), limite de taille (5 Mo) et renommage aleatoire.'],
    ],
    $C_LIGHT_BG
);

// Slide 9 - Tests &amp; Résultats obtenus
$slides[] = slide_bullets(
    'Tests &amp; Resultats',
    [
        ['head' => 'Tests fonctionnels',   'body' => '100% de conformite sur les flux d\'inscription, de postulation et de gestion des dossiers.'],
        ['head' => 'Precision de l\'IA',    'body' => '90% de taux de reussite pour l\'extraction sémantique des CV par l\'API Gemini.'],
        ['head' => 'Coherence du matching', 'body' => 'Ecart d\'evaluation moyen inferieur a 8% par rapport au tri manuel.'],
        ['head' => 'Performance globale',  'body' => 'Analyse IA et affichage des pages en moins de 3 secondes en moyenne.'],
    ],
    $C_LIGHT_BG
);

// Slide 10 - Conclusion &amp; Perspectives
$slides[] = slide_bullets(
    'Conclusion &amp; Perspectives',
    [
        ['head' => 'Bilan du projet',      'body' => 'Objectifs atteints. Plateforme Admissio fonctionnelle, moderne, intelligente et robuste.'],
        ['head' => 'Visio Integree',       'body' => 'Perspective : Integrer un module d\'entretien video natif sans quitter la plateforme.'],
        ['head' => 'Application Mobile',   'body' => 'Perspective : Developper une application compagnon iOS/Android.'],
        ['head' => 'Réseaux Professionnels','body' => 'Perspective : Synchroniser l\'importation de profils avec LinkedIn.'],
    ],
    $C_LIGHT_BG
);

// Slide 11 - Merci
$slides[] = slide_closing();

// ─────────────────────────────────────────────────────────────────────────────
// FONCTIONS DE GENERATION XML DES SLIDES

function xml_run($text, $font_size_pt, $bold = false, $color = '0F172A', $font = 'Calibri') {
    return "
        <a:r>
            <a:rPr lang=\"fr-FR\" sz=\"" . ($font_size_pt * 100) . "\" b=\"" . ($bold ? '1' : '0') . "\" dirty=\"0\">
                <a:solidFill><a:srgbClr val=\"{$color}\"/></a:solidFill>
                <a:latin typeface=\"{$font}\"/>
            </a:rPr>
            <a:t>" . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</a:t>
        </a:r>";
}

function xml_para($runs_xml, $align = 'l', $space_before = 0, $space_after = 0) {
    $spcBef = $space_before > 0 ? "<a:spcBef><a:spcPts val=\"{$space_before}\"/></a:spcBef>" : '';
    $spcAft = $space_after  > 0 ? "<a:spcAft><a:spcPts val=\"{$space_after}\"/></a:spcAft>"  : '';
    return "
        <a:p>
            <a:pPr algn=\"{$align}\" indent=\"0\" marL=\"0\">{$spcBef}{$spcAft}</a:pPr>
            {$runs_xml}
        </a:p>";
}

function xml_txBody($content_xml, $bodyPr_extra = '') {
    return "
        <p:txBody>
            <a:bodyPr wrap=\"square\" rtlCol=\"0\" {$bodyPr_extra}/>
            <a:lstStyle/>
            {$content_xml}
        </p:txBody>";
}

function xml_sp($x, $y, $cx, $cy, $txBody_xml, $fill_xml = '', $line_xml = '') {
    static $id_counter = 200;
    $id_counter++;
    $fill = $fill_xml ?: '<p:noFill/>';
    $line = $line_xml ?: '<a:ln><a:noFill/></a:ln>';
    return "
    <p:sp>
        <p:nvSpPr>
            <p:cNvPr id=\"{$id_counter}\" name=\"Shape{$id_counter}\"/>
            <p:cNvSpPr><a:spLocks noGrp=\"1\"/></p:cNvSpPr>
            <p:nvPr/>
        </p:nvSpPr>
        <p:spPr>
            <a:xfrm><a:off x=\"{$x}\" y=\"{$y}\"/><a:ext cx=\"{$cx}\" cy=\"{$cy}\"/></a:xfrm>
            <a:prstGeom prst=\"rect\"><a:avLst/></a:prstGeom>
            {$fill}
            {$line}
        </p:spPr>
        {$txBody_xml}
    </p:sp>";
}

function slide_bg_fill($hex) {
    return "<p:bg><p:bgPr><a:solidFill><a:srgbClr val=\"{$hex}\"/></a:solidFill><a:effectLst/></p:bgPr></p:bg>";
}

function make_slide($body_xml, $bg_hex) {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
       xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"
       xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <p:cSld>' . slide_bg_fill($bg_hex) . '
        <p:spTree>
            <p:nvGrpSpPr>
                <p:cNvPr id="1" name="Title Slide"/>
                <p:cNvGrpSpPr/>
                <p:nvPr/>
            </p:nvGrpSpPr>
            <p:grpSpPr>
                <a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm>
            </p:grpSpPr>
            ' . $body_xml . '
        </p:spTree>
    </p:cSld>
    <p:clrMapOvr><a:masterClr/></p:clrMapOvr>
</p:sld>';
}

function emu($inches) { return (int)($inches * 914400); }

// ─── SLIDE 1 : COVER ───
function slide_title_cover() {
    $shapes = '';

    // Titre principal
    $tx = xml_txBody(
        xml_para(xml_run('ADMISSIO', 60, true, 'FFFFFF', 'Calibri'), 'l', 0, 300) .
        xml_para(xml_run('Plateforme Web de Recrutement et de Gestion des Candidatures', 24, false, 'CBD5E1', 'Calibri'), 'l', 0, 0)
    );
    $shapes .= xml_sp(emu(0.8), emu(1.5), emu(11.5), emu(2.5), $tx);

    // Ligne de separation
    $shapes .= "
    <p:sp>
        <p:nvSpPr><p:cNvPr id=\"20\" name=\"line\"/><p:cNvSpPr><a:spLocks noGrp=\"1\"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr>
            <a:xfrm><a:off x=\"" . emu(0.8) . "\" y=\"" . emu(4.1) . "\"/><a:ext cx=\"" . emu(3.5) . "\" cy=\"" . emu(0.02) . "\"/></a:xfrm>
            <a:prstGeom prst=\"rect\"><a:avLst/></a:prstGeom>
            <a:solidFill><a:srgbClr val=\"20C997\"/></a:solidFill>
        </p:spPr>
        <p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>
    </p:sp>";

    // Informations soutenance
    $tx2 = xml_txBody(
        xml_para(xml_run('Presente par : [Votre Nom Complet]', 22, true,  'FFFFFF', 'Calibri'), 'l', 0, 150) .
        xml_para(xml_run('Encadrant : M. Tourad',              18, false, 'CBD5E1', 'Calibri'), 'l', 0, 100) .
        xml_para(xml_run('Sup Management  |  Soutenance PFE  |  Juin 2026', 15, false, '94A3B8', 'Calibri'), 'l', 200, 0)
    );
    $shapes .= xml_sp(emu(0.8), emu(4.3), emu(11.5), emu(2.5), $tx2);

    return make_slide($shapes, '0F5132');
}

// ─── SLIDE AVEC BULLETS ───
function slide_bullets($title, $bullets, $bg_hex) {
    $shapes = '';

    // Barre verte laterale
    $shapes .= "
    <p:sp>
        <p:nvSpPr><p:cNvPr id=\"30\" name=\"bar\"/><p:cNvSpPr><a:spLocks noGrp=\"1\"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr>
            <a:xfrm><a:off x=\"0\" y=\"0\"/><a:ext cx=\"" . emu(0.08) . "\" cy=\"" . emu(7.5) . "\"/></a:xfrm>
            <a:prstGeom prst=\"rect\"><a:avLst/></a:prstGeom>
            <a:solidFill><a:srgbClr val=\"0F5132\"/></a:solidFill>
        </p:spPr>
        <p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>
    </p:sp>";

    // Titre
    $tx_title = xml_txBody(xml_para(xml_run($title, 32, true, '0F5132', 'Calibri'), 'l'));
    $shapes .= xml_sp(emu(0.8), emu(0.4), emu(11.5), emu(0.9), $tx_title);

    // Ligne de separation
    $shapes .= "
    <p:sp>
        <p:nvSpPr><p:cNvPr id=\"31\" name=\"div\"/><p:cNvSpPr><a:spLocks noGrp=\"1\"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr>
            <a:xfrm><a:off x=\"" . emu(0.8) . "\" y=\"" . emu(1.4) . "\"/><a:ext cx=\"" . emu(11.5) . "\" cy=\"" . emu(0.015) . "\"/></a:xfrm>
            <a:prstGeom prst=\"rect\"><a:avLst/></a:prstGeom>
            <a:solidFill><a:srgbClr val=\"E2E8F0\"/></a:solidFill>
        </p:spPr>
        <p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>
    </p:sp>";

    // Contenu bullets
    $bullet_xml = '';
    foreach ($bullets as $b) {
        $bullet_xml .= xml_para(
            xml_run('>> ', 16, true,  '0F5132', 'Calibri') .
            xml_run($b['head'] . ' : ', 16, true,  '0F172A', 'Calibri') .
            xml_run($b['body'], 14, false, '64748B', 'Calibri'),
            'l', 150, 300
        );
    }
    $tx = xml_txBody($bullet_xml, 'anchor="t"');
    $shapes .= xml_sp(emu(0.8), emu(1.6), emu(11.5), emu(5.5), $tx);

    // Pied de page
    $tx_n = xml_txBody(xml_para(xml_run('Admissio - Soutenance PFE 2026', 10, false, 'CBD5E1', 'Calibri'), 'r'));
    $shapes .= xml_sp(emu(0.8), emu(7.1), emu(11.5), emu(0.3), $tx_n);

    return make_slide($shapes, $bg_hex);
}

// ─── SLIDE 4 : ACTEURS ───
function slide_actors() {
    $shapes = '';

    // Barre verte
    $shapes .= "
    <p:sp>
        <p:nvSpPr><p:cNvPr id=\"40\" name=\"bar\"/><p:cNvSpPr><a:spLocks noGrp=\"1\"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr>
            <a:xfrm><a:off x=\"0\" y=\"0\"/><a:ext cx=\"" . emu(0.08) . "\" cy=\"" . emu(7.5) . "\"/></a:xfrm>
            <a:prstGeom prst=\"rect\"><a:avLst/></a:prstGeom>
            <a:solidFill><a:srgbClr val=\"0F5132\"/></a:solidFill>
        </p:spPr>
        <p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>
    </p:sp>";

    // Titre
    $tx_title = xml_txBody(xml_para(xml_run('2. Les Acteurs du Systeme', 32, true, '0F5132', 'Calibri'), 'l'));
    $shapes .= xml_sp(emu(0.8), emu(0.4), emu(11.5), emu(0.9), $tx_title);

    $actors = [
        ['label' => 'CANDIDAT',            'desc' => 'Cree son profil, construit son CV numerique, postule aux offres, suit ses candidatures et communique avec le recruteur.'],
        ['label' => 'RECRUTEUR (Gerant)',   'desc' => 'Publie les offres, evalue les dossiers via les scores IA, planifie les entretiens et consulte les statistiques.'],
        ['label' => 'ADMINISTRATEUR',       'desc' => 'Supervise la plateforme, valide les comptes des recruteurs, gere les utilisateurs et traite les tickets de support.'],
    ];

    $col_width  = emu(3.8);
    $col_height = emu(4.2);
    $col_top    = emu(1.7);
    $gap        = emu(0.22);
    $left_start = emu(0.8);

    foreach ($actors as $i => $actor) {
        $cx = $left_start + $i * ($col_width + $gap);
        $card_id = 50 + $i;

        $shapes .= "
        <p:sp>
            <p:nvSpPr><p:cNvPr id=\"{$card_id}\" name=\"card{$i}\"/><p:cNvSpPr><a:spLocks noGrp=\"1\"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
            <p:spPr>
                <a:xfrm><a:off x=\"{$cx}\" y=\"{$col_top}\"/><a:ext cx=\"{$col_width}\" cy=\"{$col_height}\"/></a:xfrm>
                <a:prstGeom prst=\"roundRect\"><a:avLst><a:gd name=\"adj\" fmla=\"val 20000\"/></a:avLst></a:prstGeom>
                <a:solidFill><a:srgbClr val=\"FFFFFF\"/></a:solidFill>
                <a:ln w=\"12700\"><a:solidFill><a:srgbClr val=\"0F5132\"/></a:solidFill></a:ln>
            </p:spPr>
            <p:txBody>
                <a:bodyPr wrap=\"square\" lIns=\"180000\" rIns=\"180000\" tIns=\"180000\" anchor=\"t\"/>
                <a:lstStyle/>
                " . xml_para(xml_run('[ ' . $actor['label'] . ' ]', 18, true, '0F5132', 'Calibri'), 'ctr', 0, 500) .
                   xml_para(xml_run($actor['desc'], 13, false, '475569', 'Calibri'), 'ctr', 200, 0) . "
            </p:txBody>
        </p:sp>";
    }

    $tx_n = xml_txBody(xml_para(xml_run('Admissio - Soutenance PFE 2026', 10, false, 'CBD5E1', 'Calibri'), 'r'));
    $shapes .= xml_sp(emu(0.8), emu(7.1), emu(11.5), emu(0.3), $tx_n);

    return make_slide($shapes, 'F8FAFC');
}

// ─── SLIDE DEMO ───
function slide_demo() {
    $shapes = '';

    $shapes .= "
    <p:sp>
        <p:nvSpPr><p:cNvPr id=\"80\" name=\"bar\"/><p:cNvSpPr><a:spLocks noGrp=\"1\"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr>
            <a:xfrm><a:off x=\"0\" y=\"0\"/><a:ext cx=\"" . emu(0.08) . "\" cy=\"" . emu(7.5) . "\"/></a:xfrm>
            <a:prstGeom prst=\"rect\"><a:avLst/></a:prstGeom>
            <a:solidFill><a:srgbClr val=\"0F5132\"/></a:solidFill>
        </p:spPr>
        <p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody>
    </p:sp>";

    $tx_title = xml_txBody(xml_para(xml_run('Demonstration Technique', 32, true, '0F5132', 'Calibri'), 'l'));
    $shapes .= xml_sp(emu(0.8), emu(0.4), emu(11.5), emu(0.9), $tx_title);

    $bullet_xml =
        xml_para(xml_run('>> Scenario Candidat : ', 16, true, '0F172A', 'Calibri') . xml_run('Creation de profil, remplissage du CV par IA, recherche d\'offre et depot de dossier.', 14, false, '64748B', 'Calibri'), 'l', 200, 500) .
        xml_para(xml_run('>> Scenario Recruteur : ', 16, true, '0F172A', 'Calibri') . xml_run('Tableau de bord, etude des dossiers, score IA, planification entretien, messagerie.', 14, false, '64748B', 'Calibri'), 'l', 200, 500) .
        xml_para(xml_run('>> Securite : ', 16, true, '0F172A', 'Calibri') . xml_run('Controle des roles PHP, requetes preparees PDO, bcrypt, anti-XSS sur toutes les sorties.', 14, false, '64748B', 'Calibri'), 'l', 200, 0);

    $tx = xml_txBody($bullet_xml, 'anchor="t"');
    $shapes .= xml_sp(emu(0.8), emu(1.7), emu(5.5), emu(5.0), $tx);

    // Carte demo
    $shapes .= "
    <p:sp>
        <p:nvSpPr><p:cNvPr id=\"81\" name=\"democard\"/><p:cNvSpPr><a:spLocks noGrp=\"1\"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr>
            <a:xfrm><a:off x=\"" . emu(6.6) . "\" y=\"" . emu(1.7) . "\"/><a:ext cx=\"" . emu(5.9) . "\" cy=\"" . emu(5.1) . "\"/></a:xfrm>
            <a:prstGeom prst=\"roundRect\"><a:avLst><a:gd name=\"adj\" fmla=\"val 20000\"/></a:avLst></a:prstGeom>
            <a:solidFill><a:srgbClr val=\"ECFDF5\"/></a:solidFill>
            <a:ln w=\"25400\"><a:solidFill><a:srgbClr val=\"0F5132\"/></a:solidFill></a:ln>
        </p:spPr>
        <p:txBody>
            <a:bodyPr wrap=\"square\" lIns=\"360000\" rIns=\"360000\" tIns=\"900000\" anchor=\"ctr\"/>
            <a:lstStyle/>
            " . xml_para(xml_run('[ DEMO LIVE ]', 28, true, '0F5132', 'Calibri'), 'ctr', 0, 400) .
               xml_para(xml_run('Interfaces Candidat et Recruteur', 18, false, '64748B', 'Calibri'), 'ctr', 0, 300) .
               xml_para(xml_run('sur localhost / WampServer', 15, false, '94A3B8', 'Calibri'), 'ctr', 0, 0) . "
        </p:txBody>
    </p:sp>";

    $tx_n = xml_txBody(xml_para(xml_run('Admissio - Soutenance PFE 2026', 10, false, 'CBD5E1', 'Calibri'), 'r'));
    $shapes .= xml_sp(emu(0.8), emu(7.1), emu(11.5), emu(0.3), $tx_n);

    return make_slide($shapes, 'F8FAFC');
}

// ─── SLIDE CLOSING ───
function slide_closing() {
    $shapes = '';

    $tx = xml_txBody(
        xml_para(xml_run('Merci pour votre attention !', 52, true, 'FFFFFF', 'Calibri'), 'ctr', 0, 600) .
        xml_para(xml_run('Place aux questions du jury.', 26, false, 'CBD5E1', 'Calibri'), 'ctr', 0, 400) .
        xml_para(xml_run('M. Tourad  |  Sup Management  |  PFE 2026', 16, false, '94A3B8', 'Calibri'), 'ctr', 300, 0),
        'anchor="ctr"'
    );
    $shapes .= xml_sp(emu(1.0), emu(1.5), emu(11.333), emu(4.5), $tx);

    // Bande du bas
    $shapes .= "
    <p:sp>
        <p:nvSpPr><p:cNvPr id=\"100\" name=\"strip\"/><p:cNvSpPr><a:spLocks noGrp=\"1\"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr>
            <a:xfrm><a:off x=\"0\" y=\"" . emu(6.8) . "\"/><a:ext cx=\"" . emu(13.333) . "\" cy=\"" . emu(0.7) . "\"/></a:xfrm>
            <a:prstGeom prst=\"rect\"><a:avLst/></a:prstGeom>
            <a:solidFill><a:srgbClr val=\"0A3622\"/></a:solidFill>
        </p:spPr>
        <p:txBody>
            <a:bodyPr wrap=\"square\" lIns=\"360000\" tIns=\"100000\" anchor=\"ctr\"/>
            <a:lstStyle/>
            " . xml_para(xml_run('ADMISSIO  |  Plateforme de Recrutement  |  Soutenance PFE 2026', 13, false, '94A3B8', 'Calibri'), 'ctr') . "
        </p:txBody>
    </p:sp>";

    return make_slide($shapes, '0F5132');
}

// ─────────────────────────────────────────────────────────────────────────────
// ASSEMBLAGE DU FICHIER PPTX (ZIP)

$slide_count = count($slides);

foreach ($slides as $i => $xml) {
    $n = $i + 1;
    file_put_contents("{$tmp_dir}/slide{$n}.xml", $xml);
}

// [Content_Types].xml
$ct_slides = '';
for ($i = 1; $i <= $slide_count; $i++) {
    $ct_slides .= "<Override PartName=\"/ppt/slides/slide{$i}.xml\" ContentType=\"application/vnd.openxmlformats-officedocument.presentationml.slide+xml\"/>\n";
}
file_put_contents("{$tmp_dir}/[Content_Types].xml", '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml"  ContentType="application/xml"/>
    <Override PartName="/ppt/presentation.xml"           ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>
    <Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>
    <Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/>
    <Override PartName="/ppt/theme/theme1.xml"           ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>
    ' . $ct_slides . '
</Types>');

// _rels/.rels
file_put_contents("{$tmp_dir}/_rels.rels", '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/>
</Relationships>');

// ppt/presentation.xml
$sldIdLst = '';
$base_id = 256;
for ($i = 1; $i <= $slide_count; $i++) {
    $sldIdLst .= "<p:sldId id=\"" . ($base_id + $i) . "\" r:id=\"rId{$i}\"/>\n";
}
file_put_contents("{$tmp_dir}/presentation.xml", '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
                xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"
                xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
                saveSubsetFonts="1">
    <p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rIdM1"/></p:sldMasterIdLst>
    <p:sldSz cx="12192000" cy="6858000"/>
    <p:notesSz cx="6858000" cy="9144000"/>
    <p:sldIdLst>' . $sldIdLst . '</p:sldIdLst>
    <p:defaultTextStyle>
        <a:defPPr><a:defRPr lang="fr-FR"/></a:defPPr>
    </p:defaultTextStyle>
</p:presentation>');

// ppt/_rels/presentation.xml.rels
$slide_rels = '';
for ($i = 1; $i <= $slide_count; $i++) {
    $slide_rels .= "<Relationship Id=\"rId{$i}\" Type=\"http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide\" Target=\"slides/slide{$i}.xml\"/>\n";
}
file_put_contents("{$tmp_dir}/presentation.xml.rels",
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    ' . $slide_rels . '
    <Relationship Id="rIdM1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>
</Relationships>');

// Slide rels
for ($i = 1; $i <= $slide_count; $i++) {
    file_put_contents("{$tmp_dir}/slide{$i}.xml.rels",
    '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>
</Relationships>');
}

// Slide Master
file_put_contents("{$tmp_dir}/slideMaster1.xml",
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
             xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"
             xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <p:cSld><p:bg><p:bgRef idx="1001"><a:schemeClr val="bg1"/></p:bgRef></p:bg><p:spTree>
        <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
        <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
    </p:spTree></p:cSld>
    <p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/>
    <p:sldLayoutIdLst><p:sldLayoutId id="2147483649" r:id="rId1"/></p:sldLayoutIdLst>
    <p:txStyles>
        <p:titleStyle><a:lvl1pPr><a:defRPr lang="fr-FR" sz="3200" b="1"><a:solidFill><a:srgbClr val="0F5132"/></a:solidFill><a:latin typeface="Calibri"/></a:defRPr></a:lvl1pPr></p:titleStyle>
        <p:bodyStyle><a:lvl1pPr><a:defRPr lang="fr-FR" sz="1800"><a:solidFill><a:srgbClr val="0F172A"/></a:solidFill><a:latin typeface="Calibri"/></a:defRPr></a:lvl1pPr></p:bodyStyle>
        <p:otherStyle><a:lvl1pPr><a:defRPr lang="fr-FR" sz="1600"><a:solidFill><a:srgbClr val="64748B"/></a:solidFill><a:latin typeface="Calibri"/></a:defRPr></a:lvl1pPr></p:otherStyle>
    </p:txStyles>
</p:sldMaster>');

// Slide Master rels
file_put_contents("{$tmp_dir}/slideMaster1.xml.rels",
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/>
</Relationships>');

// Slide Layout
file_put_contents("{$tmp_dir}/slideLayout1.xml",
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
             xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"
             xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" type="blank" preserve="1">
    <p:cSld name="Blank"><p:spTree>
        <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
        <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
    </p:spTree></p:cSld>
    <p:clrMapOvr><a:masterClr/></p:clrMapOvr>
</p:sldLayout>');

// Slide Layout rels
file_put_contents("{$tmp_dir}/slideLayout1.xml.rels",
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/>
</Relationships>');

// Theme
file_put_contents("{$tmp_dir}/theme1.xml",
'<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Admissio">
  <a:themeElements>
    <a:clrScheme name="Admissio">
      <a:dk1><a:srgbClr val="0F172A"/></a:dk1><a:lt1><a:srgbClr val="FFFFFF"/></a:lt1>
      <a:dk2><a:srgbClr val="0F5132"/></a:dk2><a:lt2><a:srgbClr val="F8FAFC"/></a:lt2>
      <a:accent1><a:srgbClr val="0F5132"/></a:accent1><a:accent2><a:srgbClr val="198754"/></a:accent2>
      <a:accent3><a:srgbClr val="64748B"/></a:accent3><a:accent4><a:srgbClr val="0EA5E9"/></a:accent4>
      <a:accent5><a:srgbClr val="E2E8F0"/></a:accent5><a:accent6><a:srgbClr val="F8FAFC"/></a:accent6>
      <a:hlink><a:srgbClr val="0F5132"/></a:hlink><a:folHlink><a:srgbClr val="198754"/></a:folHlink>
    </a:clrScheme>
    <a:fontScheme name="Admissio">
      <a:majorFont><a:latin typeface="Calibri"/><a:ea typeface=""/><a:cs typeface=""/></a:majorFont>
      <a:minorFont><a:latin typeface="Calibri"/><a:ea typeface=""/><a:cs typeface=""/></a:minorFont>
    </a:fontScheme>
    <a:fmtScheme name="Office">
      <a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:fillStyleLst>
      <a:lnStyleLst><a:ln w="6350"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln><a:ln w="12700"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln><a:ln w="19050"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln></a:lnStyleLst>
      <a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle><a:effectStyle><a:effectLst/></a:effectStyle><a:effectStyle><a:effectLst/></a:effectStyle></a:effectStyleLst>
      <a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:bgFillStyleLst>
    </a:fmtScheme>
  </a:themeElements>
</a:theme>');

// ─── ZIP Assembly ───
$zip = new ZipArchive();
if ($zip->open($output_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Impossible de creer le fichier ZIP / PPTX : {$output_path}");
}

$zip->addFile("{$tmp_dir}/[Content_Types].xml",    '[Content_Types].xml');
$zip->addFile("{$tmp_dir}/_rels.rels",             '_rels/.rels');
$zip->addFile("{$tmp_dir}/presentation.xml",       'ppt/presentation.xml');
$zip->addFile("{$tmp_dir}/presentation.xml.rels",  'ppt/_rels/presentation.xml.rels');
$zip->addFile("{$tmp_dir}/slideMaster1.xml",       'ppt/slideMasters/slideMaster1.xml');
$zip->addFile("{$tmp_dir}/slideMaster1.xml.rels",  'ppt/slideMasters/_rels/slideMaster1.xml.rels');
$zip->addFile("{$tmp_dir}/slideLayout1.xml",       'ppt/slideLayouts/slideLayout1.xml');
$zip->addFile("{$tmp_dir}/slideLayout1.xml.rels",  'ppt/slideLayouts/_rels/slideLayout1.xml.rels');
$zip->addFile("{$tmp_dir}/theme1.xml",             'ppt/theme/theme1.xml');

for ($i = 1; $i <= $slide_count; $i++) {
    $zip->addFile("{$tmp_dir}/slide{$i}.xml",      "ppt/slides/slide{$i}.xml");
    $zip->addFile("{$tmp_dir}/slide{$i}.xml.rels", "ppt/slides/_rels/slide{$i}.xml.rels");
}

$zip->close();

// Nettoyage
array_map('unlink', glob("{$tmp_dir}/*"));
rmdir($tmp_dir);

echo "Fichier PPTX genere avec succes !\n";
echo "Chemin : {$output_path}\n";
echo "Nombre de slides : {$slide_count}\n";
