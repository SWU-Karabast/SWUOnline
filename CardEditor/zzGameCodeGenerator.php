<?php

  include './zzImageConverter.php';
  include '../Libraries/Trie.php';

  $schemaFile = "./GameSchema.txt";
  $handler = fopen($schemaFile, "r");
  $rootName = trim(fgets($handler));

  $zones = [];
  //First parse the schema file to get the zones and their properties
  while (!feof($handler)) {
    $zone = fgets($handler);
    if ($zone !== false) {
        // Process the line
        $zone = str_replace(' ', '', $zone);
        $zoneArr = explode("-", $zone);
        $zoneName = $zoneArr[0];
        $zoneObj = new StdClass();
        $zoneObj->Name = $zoneName;
        $zoneObj->Properties = [];
        $propertyArr = explode(",", $zoneArr[1]);
        for($i=0; $i<count($propertyArr); ++$i) {
          $thisProperty = explode(":", $propertyArr[$i]);
          $propertyObj = new StdClass();
          $propertyObj->Name = $thisProperty[0];
          $propertyObj->Type = $thisProperty[1];
          array_push($zoneObj->Properties, $propertyObj);
        }
        array_push($zones, $zoneObj);
    }
  }

  fclose($handler);

  $rootPath = "./" . $rootName;
  if(!is_dir($rootPath)) mkdir($rootPath, 0755, true);

  //Write the zone accessors file
  $filename = $rootPath . "/ZoneAccessors.php";
  $handler = fopen($filename, "w");
  fwrite($handler, "<?php\r\n");
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $zoneName = $zone->Name;
    //Getter
    fwrite($handler, "function &Get" . $zoneName . "(\$player) {\r\n");
    fwrite($handler, "  global \$p1" . $zoneName . ", \$p2" . $zoneName . ";\r\n");
    fwrite($handler, "  if (\$player == 1) return \$p1" . $zoneName . ";\r\n");
    fwrite($handler, "  else return \$p2" . $zoneName . ";\r\n");
    fwrite($handler, "}\r\n\r\n");
    //Setter
    fwrite($handler, "function Add" . $zoneName . "(\$player");
      for($j=0; $j<count($zone->Properties); ++$j) {
        $property = $zone->Properties[$j];
        fwrite($handler, ", \$" . $property->Name);
      }
    fwrite($handler, ") {\r\n");

    fwrite($handler, "}\r\n\r\n");
  }

  fwrite($handler, "?>");
  fclose($handler);

  //Write the Gamestate parsing file
  $filename = $rootPath . "/GamestateParser.php";
  $handler = fopen($filename, "w");
  fwrite($handler, "<?php\r\n");
  //Write gamestate function
  fwrite($handler, "function WriteGamestate() {\r\n");
  fwrite($handler, GetZoneGlobals($zones) . "\r\n");

  fwrite($handler, "}\r\n\r\n");
  //Parse gamestate function
  fwrite($handler, "function ParseGamestate() {\r\n");
  fwrite($handler, GetZoneGlobals($zones) . "\r\n");

  fwrite($handler, "}\r\n\r\n");
  fwrite($handler, "?>");
  fclose($handler);

  echo("Game code generator completed successfully!");

  function GetZoneGlobals($zones) {
    $zoneGlobals = "";
    for($i=0; $i<count($zones); ++$i) {
      $zone = $zones[$i];
      $zoneName = $zone->Name;
      $zoneGlobals .= "  global \$p1" . $zoneName . ", \$p2" . $zoneName . ";\r\n";
    }
    return $zoneGlobals;
  }
?>
