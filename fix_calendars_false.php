<?php

$dir = new RecursiveDirectoryIterator(__DIR__ . '/resources/views');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/.*\.blade\.php$/', RegexIterator::GET_MATCH);

foreach ($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);

    if (strpos($content, 'alwaysShowCalendars: true') !== false) {
        $newContent = str_replace('alwaysShowCalendars: true', 'alwaysShowCalendars: false', $content);
        file_put_contents($path, $newContent);
        echo "Updated: $path\n";
    }
}
