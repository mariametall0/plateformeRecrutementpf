<?php
$directory = "C:/wamp64/www/plateforme_recrutement/";

function removeBOM($dir) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $path = $file->getPathname();
            $content = file_get_contents($path);
            if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                $content = substr($content, 3);
                file_put_contents($path, $content);
                echo "Removed BOM from: " . $path . "\n";
            }
        }
    }
}

removeBOM($directory);
echo "Done.";
?>
