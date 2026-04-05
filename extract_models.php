<?php
$content = file_get_contents('c:/wamp64/www/plateforme_recrutement/models_list.json');
$content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
$data = json_decode(substr($content, strpos($content, '{')), true);

foreach ($data['models'] as $model) {
    if (in_array('generateContent', $model['supportedGenerationMethods'])) {
        echo $model['name'] . "\n";
    }
}
?>
