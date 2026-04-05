<?php
// Script to capture errors directly
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Fake session to bypass role checks dynamically
session_start();
$_SESSION['id'] = 1; 
$_SESSION['role'] = 'gerant';

ob_start();
try {
    include "gerant/candidatures.php";
} catch (Throwable $e) {
    echo "FATAL ERROR: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
}
$output = ob_get_clean();

if (empty($output)) {
    echo "EMPTY OUTPUT! Checking error_get_last()...\n";
    print_r(error_get_last());
} else {
    // If there is output but an error is hidden:
    if (preg_match('/Fatal error|Parse error|Warning/', $output)) {
        echo substr($output, 0, 1000);
    } else {
        echo "No explicit error caught, output starts with: \n" . substr($output, 0, 200);
    }
}
?>
