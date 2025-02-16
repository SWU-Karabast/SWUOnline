<?php

  include_once "../CoreLogic.php";
  include_once "../CardDictionary.php";
  include_once "../Libraries/CoreLibraries.php";

  $sets = ["SOR", "SHD", "TWI", "JTL"];

foreach ($sets as $set) {
    // Process each set (e.g., display the set's value)
    echo "Processing set: " . $set . "<BR>";
    for($i=0; $i<300; ++$i) {
        $number = $i;
        if($i < 10) $number = "00" . $number;
        else if($i < 100) $number = "0" . $number;
        $setID = $set . "_" . $number;
        $uuid = UUIDLookup($setID);
        if($uuid == "") continue;
        $concatOut = "./jpg/concat/{$uuid}.jpg";
        $fullOut   = "./jpg/fullsize/{$uuid}.jpg";

        // Convert the concat image if it doesn't exist
        if (!file_exists($concatOut)) {
            $srcConcat = "../concat/{$uuid}.webp";
            if (file_exists($srcConcat)) {
                $img = imagecreatefromwebp($srcConcat);
                if ($img) {
                    imagejpeg($img, $concatOut, 90);
                    imagedestroy($img);
                    echo "Created JPG for concat: {$uuid}.jpg<BR>";
                } else {
                    echo "Failed to convert image from {$srcConcat}<BR>";
                }
            } else {
                echo "Source file not found: {$srcConcat}<BR>";
            }
        }

        // Convert the fullsize image if it doesn't exist
        if (!file_exists($fullOut)) {
            $srcFull = "../WebpImages2/{$uuid}.webp";
            if (file_exists($srcFull)) {
                $img = imagecreatefromwebp($srcFull);
                if ($img) {
                    imagejpeg($img, $fullOut, 90);
                    imagedestroy($img);
                    echo "Created JPG for fullsize: {$uuid}.jpg<BR>";
                } else {
                    echo "Failed to convert image from {$srcFull}<BR>";
                }
            } else {
                echo "Source file not found: {$srcFull}<BR>";
            }
        }
    }
}


?>