<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnostic de l'environnement PHP</h1>";
echo "Chemin du script : " . __FILE__ . "<br>";
echo "PHP version : " . phpversion() . "<br>";
echo "Interface SAPI : " . php_sapi_name() . "<br>";

echo "<h2>Test de session</h2>";
if (session_status() === PHP_SESSION_NONE) {
    echo "Démarrage de la session...<br>";
    $res = session_start();
    echo "Résultat de session_start() : " . ($res ? "SUCCÈS" : "ÉCHEC") . "<br>";
} else {
    echo "La session est déjà active.<br>";
}

echo "État de \$_SESSION : " . (isset($_SESSION) ? "DÉFINIE" : "NON DÉFINIE") . "<br>";
if (isset($_SESSION)) {
    echo "Contenu de \$_SESSION : <pre>";
    print_r($_SESSION);
    echo "</pre>";
}

echo "<h2>Test de variables</h2>";
$test_role = "admin";
echo "Variable \$test_role : $test_role<br>";
echo "ucfirst(\$test_role) : " . ucfirst($test_role) . "<br>";

echo "<h2>Inclusion de layout.php</h2>";
$layout_path = __DIR__ . "/includes/layout.php";
if (file_exists($layout_path)) {
    echo "layout.php trouvé.<br>";
    require_once $layout_path;
    echo "layout.php inclus avec succès.<br>";
} else {
    echo "layout.php NON TROUVÉ à $layout_path<br>";
}
?>
