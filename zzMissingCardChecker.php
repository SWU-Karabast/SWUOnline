<?php
$webpimages2Path = 'WebpImages2';
$folders = ['DE', 'ES', 'FR', 'IT'];

$webpimages2Files = scandir($webpimages2Path);
$missingCards = [];

$webpimages2Files = array_filter($webpimages2Files, function($file) {
    return strlen($file) === 15 && 
           pathinfo($file, PATHINFO_EXTENSION) === 'webp' && 
           $file !== 'porg_depot.webp';
});

foreach ($webpimages2Files as $file) {
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

foreach ($folders as $folder) {
    $folderPath = $webpimages2Path . '/' . $folder;
    $missingInFolder = array_diff($missingCards, scandir($folderPath));
    echo "Missing cards in $folder: " . implode(', ', $missingInFolder) . "\n";
}



