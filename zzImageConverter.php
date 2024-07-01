<?php

function CheckImage($cardID, $url, $definedType, $isBack=false, $set="SOR")
{
  $filename = "./WebpImages/" . $cardID . ".webp";
  $filename = "./WebpImages2/" . $cardID . ".webp";
  $filenameNew = "./UnimplementedCards/" . $cardID . ".webp";
  $concatFilename = "./concat/" . $cardID . ".webp";
  $cropFilename = "./crops/" . $cardID . "_cropped.png";
  $isNew = false;
  if(!file_exists($filename))
  {
    $imageURL = $url;
    echo("Image for " . $cardID . " does not exist.<BR>");
    $handler = fopen($filename, "w");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $imageURL);
    curl_setopt($ch, CURLOPT_FILE, $handler);
    curl_exec($ch);
    curl_close($ch);
    //if(filesize($filename) < 10000) { unlink($filename); return; }
    if(file_exists($filename)) echo("Image for " . $cardID . " successfully retrieved.<BR>");
    if(file_exists($filename))
    {
      echo("Normalizing file size for " . $cardID . ".<BR>");
      echo("Defined Type: " . $definedType . "<BR>");
      $image = imagecreatefrompng($filename);
      //$image = imagecreatefromjpeg($filename);
      if($definedType == "Base" || $definedType == "Leader") {
        if(imagesy($image) > imagesx($image)) $image = imagerotate($image, -90, 0);
        $image = imagescale($image, 628, 450);
      }
      else $image = imagescale($image, 450, 628);
      imagewebp($image, $filename);
      // Free up memory
      imagedestroy($image);
    }
    $isNew = true;
  }
  if($isNew && !file_exists($filenameNew)) {
    echo("Converting image for " . $cardID . " to new format.<BR>");
    try {
      $image = imagecreatefromwebp($filename);
    } catch(Exception $e) {
      $image = imagecreatefrompng($filename);
    }
    imagewebp($image, $filenameNew);
    imagedestroy($image);
  }
  if(!file_exists($concatFilename))
  {
    echo("Concat image for " . $cardID . " does not exist. Converting: $filename<BR>");
    if(file_exists($filename))
    {
      echo("Attempting to convert image for " . $cardID . " to concat.<BR>");
      
      $image = imagecreatefromwebp($filename);
      //$image = imagecreatefrompng($filename);
      
      if($definedType == "Event") {
        $imageTop = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => 450, 'height' => 110]);
        $imageBottom = imagecrop($image, ['x' => 0, 'y' => 320, 'width' => 450, 'height' => 628]);
  
        $dest = imagecreatetruecolor(450, 450);
        imagecopy($dest, $imageTop, 0, 0, 0, 0, 450, 110);
        imagecopy($dest, $imageBottom, 0, 111, 0, 0, 450, 404);
      }
      else {
        $imageTop = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => 450, 'height' => 372]);
        $imageBottom = imagecrop($image, ['x' => 0, 'y' => 570, 'width' => 450, 'height' => 628]);
  
        $dest = imagecreatetruecolor(450, 450);
        imagecopy($dest, $imageTop, 0, 0, 0, 0, 450, 372);
        imagecopy($dest, $imageBottom, 0, 373, 0, 0, 450, 78);
      }

      imagewebp($dest, $concatFilename);
      // Free up memory
      imagedestroy($image);
      imagedestroy($dest);
      imagedestroy($imageTop);
      imagedestroy($imageBottom);
      if(file_exists($concatFilename)) echo("Image for " . $cardID . " successfully converted to concat.<BR>");
    }
  }
  if(!file_exists($cropFilename))
  {
    echo("Crop image for " . $cardID . " does not exist.<BR>");
    if(file_exists($filename))
    {
      echo("Attempting to convert image for " . $cardID . " to crops.<BR>");
      try {
        $image = imagecreatefromwebp($filename);
      } catch(Exception $e) {
        $image = imagecreatefrompng($filename);
      }
      //$image = imagecreatefrompng($filename);
      if($definedType == "Event") $image = imagecrop($image, ['x' => 50, 'y' => 326, 'width' => 350, 'height' => 246]);
      else $image = imagecrop($image, ['x' => 50, 'y' => 100, 'width' => 350, 'height' => 270]);
      imagepng($image, $cropFilename);
      imagedestroy($image);
      if(file_exists($cropFilename)) echo("Image for " . $cardID . " successfully converted to crops.<BR>");
    }
  }
}


?>
