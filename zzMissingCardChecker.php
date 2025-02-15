<?php
$webpimages2Path = 'WebpImages2';
$folders = ['DE', 'ES', 'FR', 'IT'];

$webpimages2Files = scandir($webpimages2Path);
$missingCards = [];

foreach ($webpimages2Files as $file) {
    if ($file !== '.' && $file !== '..') {
        $found = false;
        foreach ($folders as $folder) {
            $folderPath = $webpimages2Path . '/' . $folder;
            if (file_exists($folderPath . '/' . $file)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $missingCards[] = $file;
        }
    }
}

foreach ($folders as $folder) {
    $folderPath = $webpimages2Path . '/' . $folder;
    $missingInFolder = array_diff($missingCards, scandir($folderPath));
    echo "Missing cards in $folder: " . implode(', ', $missingInFolder) . "\n";
}