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
        if($uuid != "") {
            $leaderUnit = LeaderUnit($uuid);
            if ($leaderUnit != "") {
                echo "Leader found: " . $setID . "<BR>";
                $src = "../concat/" . $leaderUnit . ".webp";
                $dest = "./LeaderImages/" . $uuid . ".webp";
                if (copy($src, $dest)) {
                    echo "Image copied: " . $uuid . ".webp<BR>";
                } else {
                    echo "Failed to copy image for: " . $setID . "<BR>";
                }
            }
        }
    }
}


?>