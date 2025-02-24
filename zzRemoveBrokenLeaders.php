<?php
    $file = 'leadersUID.txt';
    $folder = 'WebpImages2/FR';
    
    // Read the leadersUID.txt file
    $lines = file($file, FILE_IGNORE_NEW_LINES);

    // Loop through each line
    foreach ($lines as $line) {

        // Extract the value before the comma
        $value = explode(',', $line)[0];

        // Construct the file path
        $filePath = $folder . '/' . $value . '.webp';

        // Check if the file exists and delete it
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

