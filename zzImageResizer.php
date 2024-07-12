<?php


$directory = './WebpImages2/KR/';

// Get all files and directories in the specified directory
$files = scandir($directory);

// Iterate over the array
foreach ($files as $file) {
  // Exclude current directory (.) and parent directory (..)
  if ($file != '.' && $file != '..') {
    // Process the file here
    $imagePath = $directory . $file;
    echo($imagePath."<BR>");
    
    try {
      $image = imagecreatefrompng($imagePath);
      if($image) {
        imagewebp($image, $imagePath);
        imagedestroy($image);
      }
    } catch(Exception $e) {
      

    }
  }
}

?>
