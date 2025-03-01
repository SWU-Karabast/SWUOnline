<?php
    $file = 'leadersUID.txt';
    $folder = 'crops/IT';
    
    // Read the leadersUID.txt file
    $lines = file($file, FILE_IGNORE_NEW_LINES);

    // Loop through each line
    foreach ($lines as $line) {

        // Extract the value before the comma
        $value = explode(',', $line)[0];

        // webpimages concat
        // $filePath = $folder . '/' . $value . '.webp';
        
        //crops
        $filePath = $folder . '/' . $value . '_cropped.png';

        // Check if the file exists and delete it
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

