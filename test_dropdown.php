<?php
session_start();
require_once "config/database.php";
// Simuler une ligne de offre inactif
$c = ['id' => 1, 'statut' => 'inactif', 'titre' => 'Test'];
?>
<div class="dropdown">
    <button class="btn btn-sm btn-light border dropdown-toggle px-3" type="button" data-bs-toggle="dropdown">Plus</button>
    <ul class="dropdown-menu show dropdown-menu-end shadow-lg border-0 rounded-4 p-2">
        <li><a class="dropdown-item rounded-3 py-2" href="modifier_offre.php?id=<?php echo $c['id']; ?>"><i class="bi bi-pencil me-2"></i> Modifier</a></li>
        <li><a class="dropdown-item rounded-3 py-2" href="formulaire_offre.php?id=<?php echo $c['id']; ?>"><i class="bi bi-list-task me-2"></i> Formulaire</a></li>
        <li><a class="dropdown-item rounded-3 py-2 text-primary" href="statistiques_offre.php?id=<?php echo $c['id']; ?>"><i class="bi bi-bar-chart me-2"></i> Statistiques</a></li>
        <li><hr class="dropdown-divider"></li>
        <?php if ($c['statut'] === 'actif'): ?>
            <li><a class="dropdown-item rounded-3 py-2 text-danger" href="desactiver_offre.php?id=<?php echo $c['id']; ?>"><i class="bi bi-slash-circle me-2"></i> Désactiver</a></li>
        <?php else: ?>
            <li><a class="dropdown-item rounded-3 py-2 text-success" href="activer_offre.php?id=<?php echo $c['id']; ?>"><i class="bi bi-check-circle me-2"></i> Activer</a></li>
        <?php endif; ?>
    </ul>
</div>
