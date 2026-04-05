<?php
require_once "../includes/layout.php";

// Protection gérant
check_role('gerant');

$id_gerant = $_SESSION["id"];

$id_offre = (int)($_GET["id"] ?? $_POST["id"] ?? 0);
if ($id_offre <= 0) {
    send_error("ID de offre invalide.");
}

// Vérifier appartenance
try {
    $stmt = $pdo->prepare("SELECT id, titre FROM offres WHERE id = ? AND id_gerant = ?");
    $stmt->execute([$id_offre, $id_gerant]);
    $offre = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$offre) {
        send_error("Offre non trouvé ou accès refusé.", 404);
    }

    // --- ACTIONS ---

    // 1. Ajouter un champ
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_GET["action"]) && $_GET["action"] === "ajouter") {
        verify_csrf_token();
        $input = json_decode(file_get_contents("php://input"), true);
        $libelle     = trim($input["libelle"] ?? $_POST["libelle"] ?? "");
        $type_champ  = $input["type_champ"] ?? $_POST["type_champ"] ?? "";
        $obligatoire = (isset($input["obligatoire"]) && $input["obligatoire"]) || isset($_POST["obligatoire"]) ? 1 : 0;
        $options     = trim($input["options_liste"] ?? $_POST["options_liste"] ?? "");

        $types_valides = ["texte","textarea","date","nombre","liste","case_a_cocher","fichier"];

        if (empty($libelle) || !in_array($type_champ, $types_valides)) {
            send_error("Libellé obligatoire et type de champ valide requis.");
        } else {
            // Calculer le prochain ordre
            $ordre_stmt = $pdo->prepare("SELECT COALESCE(MAX(ordre),0)+1 FROM champs_formulaire WHERE id_offre = ?");
            $ordre_stmt->execute([$id_offre]);
            $prochain_ordre = $ordre_stmt->fetchColumn();

            $pdo->prepare("INSERT INTO champs_formulaire (id_offre, libelle, type_champ, options_liste, obligatoire, ordre)
                            VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$id_offre, $libelle, $type_champ, $options ?: null, $obligatoire, $prochain_ordre]);

            send_json(['success' => true, 'message' => "Champ ajouté avec succès."]);
        }
    }

    // 2. Supprimer un champ
    if (isset($_GET["supprimer"])) {
        $id_champ = (int)$_GET["supprimer"];
        $pdo->prepare("DELETE FROM champs_formulaire WHERE id = ? AND id_offre = ?")
            ->execute([$id_champ, $id_offre]);
        send_json(['success' => true, 'message' => "Champ supprimé."]);
    }

    // 3. Remonter/Descendre ordre
    if (isset($_GET["monter"]) || isset($_GET["descendre"])) {
        $id_champ = (int)($_GET["monter"] ?? $_GET["descendre"]);
        $direction = isset($_GET["monter"]) ? "monter" : "descendre";

        $stmt_champ = $pdo->prepare("SELECT id, ordre FROM champs_formulaire WHERE id = ? AND id_offre = ?");
        $stmt_champ->execute([$id_champ, $id_offre]);
        $champ_actuel = $stmt_champ->fetch(PDO::FETCH_ASSOC);

        if ($champ_actuel) {
            if ($direction === "monter") {
                $stmt_voisin = $pdo->prepare("SELECT id, ordre FROM champs_formulaire WHERE id_offre = ? AND ordre < ? ORDER BY ordre DESC LIMIT 1");
            } else {
                $stmt_voisin = $pdo->prepare("SELECT id, ordre FROM champs_formulaire WHERE id_offre = ? AND ordre > ? ORDER BY ordre ASC LIMIT 1");
            }
            $stmt_voisin->execute([$id_offre, $champ_actuel["ordre"]]);
            $voisin = $stmt_voisin->fetch(PDO::FETCH_ASSOC);

            if ($voisin) {
                $pdo->prepare("UPDATE champs_formulaire SET ordre = ? WHERE id = ?")->execute([$voisin["ordre"], $champ_actuel["id"]]);
                $pdo->prepare("UPDATE champs_formulaire SET ordre = ? WHERE id = ?")->execute([$champ_actuel["ordre"], $voisin["id"]]);
                send_json(['success' => true, 'message' => "Ordre mis à jour."]);
            } else {
                send_error("Aucun voisin trouvé pour changer l'ordre.");
            }
        } else {
            send_error("Champ non trouvé.");
        }
    }

    // --- LECTURE ---

    $stmt_champs = $pdo->prepare("SELECT * FROM champs_formulaire WHERE id_offre = ? ORDER BY ordre ASC");
    $stmt_champs->execute([$id_offre]);
    $champs = $stmt_champs->fetchAll(PDO::FETCH_ASSOC);

    send_json([
        'success' => true,
        'offre' => $offre,
        'champs' => $champs
    ]);

} catch (PDOException $e) {
    send_error("Erreur base de données : " . $e->getMessage(), 500);
}
exit();
