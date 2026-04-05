<?php
// gerant/test2.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $x = file_get_contents("http://localhost/plateforme_recrutement/gerant/candidatures.php?statut=en_attente");
    if (!$x) {
        $error = error_get_last();
        echo "Failed to fetch. " . print_r($error, true);
    } else {
        if (preg_match('/Fatal error|Parse error|Warning/', $x)) {
            echo "ERROR CAUGHT: \n" . strip_tags($x);
        } else {
            echo "Successfully loaded, length=" . strlen($x);
            // Let's print out the first 200 chars to see if it's JSON or Redirect or HTML
            echo "\nStart of output:\n" . substr($x, 0, 500);
        }
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
?>
