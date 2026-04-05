<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "config/database.php";

session_start();
$_SESSION['id'] = 1;
$_SESSION['role'] = 'gerant';
$_SESSION['nom'] = 'Test';

// Emulate checking role but do nothing
function check_role($role) { return true; }

try {
    ob_start();
    include "gerant/candidatures.php";
    $output = ob_get_clean();
    echo "SUCCESS, length: " . strlen($output);
} catch (Throwable $t) {
    echo "FATAL: " . $t->getMessage() . " at " . $t->getFile() . ':' . $t->getLine();
}
?>
