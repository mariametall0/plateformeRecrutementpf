<?php
$php_exe = 'C:\\wamp64\\bin\\php\\php8.2.26\\php.exe';
$directory = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($directory);
$files = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$errors = [];
foreach ($files as $file) {
    $filename = $file[0];
    if (strpos($filename, '\\vendor\\') !== false) continue;
    if (strpos($filename, '\\libs\\') !== false) continue;
    
    $output = shell_exec("\"$php_exe\" -l \"$filename\" 2>&1");
    if (strpos($output, 'No syntax errors detected') === false) {
        $errors[] = $output;
    }
}

if (empty($errors)) {
    echo "Aucune erreur de syntaxe détectée.\n";
} else {
    echo "Erreurs de syntaxe trouvées :\n";
    echo implode("\n", $errors);
}
?>
