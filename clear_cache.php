<?php
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "OPcache a été réinitialisé avec succès.";
    } else {
        echo "Échec de la réinitialisation d'OPcache.";
    }
} else {
    echo "L'extension OPcache n'est pas activée.";
}
?>
