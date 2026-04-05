<?php
require_once "config/database.php";
$id_gerant = 4;
$stmt = $pdo->prepare("SELECT * FROM offre WHERE id_gerant = ?");
$stmt->execute([$id_gerant]);
$offre = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($offre as $c) {
    echo "OFFRE: " . $c['titre'] . " | STATUT: " . $c['statut'] . "<br>";
    ?>
    <div class="dropdown">
        <button class="btn dropdown-toggle">Actions</button>
        <div class="dropdown-menu show">
            <a class="dropdown-item" href="modifier_offre.php?id=<?php echo $c['id']; ?>">Modifier</a>
            <a class="dropdown-item" href="formulaire_offre.php?id=<?php echo $c['id']; ?>">Formulaire</a>
            <a class="dropdown-item" href="statistiques_offre.php?id=<?php echo $c['id']; ?>">Statistiques</a>
            <div class="dropdown-divider"></div>
            <?php if ($c['statut'] === 'actif'): ?>
                <a class="dropdown-item text-danger" href="desactiver_offre.php?id=<?php echo $c['id']; ?>">Désactiver</a>
            <?php else: ?>
                <a class="dropdown-item text-success" href="activer_offre.php?id=<?php echo $c['id']; ?>">Activer le Offre</a>
            <?php endif; ?>
        </div>
    </div>
    <hr>
    <?php
}
?>
