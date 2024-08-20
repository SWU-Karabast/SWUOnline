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

  //Write the class file
  $filename = $rootPath . "/ZoneClasses.php";
  $handler = fopen($filename, "w");
  fwrite($handler, "<?php\r\n");
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $zoneName = $zone->Name;
    fwrite($handler, "class " . $zoneName . " {\r\n");
    for($j=0; $j<count($zone->Properties); ++$j) {
      $property = $zone->Properties[$j];
      fwrite($handler, "  public \$" . $property->Name . ";\r\n");
    }
    fwrite($handler, "  function __construct(\$line) {\r\n");
    fwrite($handler, "    \$arr = explode(\" \", \$line);\r\n");
    for($j=0; $j<count($zone->Properties); ++$j) {
      $property = $zone->Properties[$j];
      $propertyName = $property->Name;
      $propertyType = $property->Type;
      fwrite($handler, "    \$this->" . $propertyName . " = ");
      if($propertyType == "int" || $propertyType == "number") fwrite($handler, "intval(\$arr[" . $j . "]);\r\n");
      else if($propertyType == "float") fwrite($handler, "floatval(\$arr[" . $j . "]);\r\n");
      else fwrite($handler, "\$arr[" . $j . "];\r\n");
    }
    fwrite($handler, "  }\r\n");
    fwrite($handler, "}\r\n\r\n");
  }
  fwrite($handler, "?>");

  //Write the Gamestate parsing file
  $filename = $rootPath . "/GamestateParser.php";
  $handler = fopen($filename, "w");
  fwrite($handler, "<?php\r\n");
  //Initialize gamestate function
  fwrite($handler, "function InitializeGamestate() {\r\n");
  fwrite($handler, GetZoneGlobals($zones) . "\r\n");
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $zoneName = $zone->Name;
    fwrite($handler, "  \$p1" . $zoneName . " = [];\r\n");
    fwrite($handler, "  \$p2" . $zoneName . " = [];\r\n");
  }
  fwrite($handler, "}\r\n\r\n");
  //Write gamestate function
  fwrite($handler, "function WriteGamestate() {\r\n");
  fwrite($handler, GetZoneGlobals($zones) . "\r\n");
  fwrite($handler, AddWriteGamestate() . "\r\n");

  fwrite($handler, "}\r\n\r\n");
  //Parse gamestate function
  fwrite($handler, "function ParseGamestate() {\r\n");
  fwrite($handler, GetZoneGlobals($zones) . "\r\n");
  fwrite($handler, AddReadGamestate() . "\r\n");

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

  function AddReadGamestate() {
    $readGamestate = "";
    global $rootPath, $zones;
    $readGamestate .= "  global \$gameName;\r\n";
    $readGamestate .= "  \$filename = \"" . $rootPath . "/Games/\$gameName/Gamestate.php\";\r\n";
    $readGamestate .= "  \$handler = fopen(\$filename, \"r\");\r\n";
    $readGamestate .= "  while (!feof(\$handler)) {\r\n";
    for($i=0; $i<count($zones); ++$i) {
      $zone = $zones[$i];
      $zoneName = $zone->Name;
      $readGamestate .= AddReadZone($zoneName, 1);
      $readGamestate .= AddReadZone($zoneName, 2);
    }
    $readGamestate .= "  }\r\n";
    $readGamestate .= "  fclose(\$handler);\r\n";
    return $readGamestate;
  }

  function AddReadZone($zoneName, $player) {
    global $rootPath;
    $rv = "";
    $rv .= "    \$line = fgets(\$handler);\r\n";
    $rv .= "    if (\$line !== false) {\r\n";
    $rv .= "      \$num = intval(\$line);\r\n";
    $rv .= "      for(\$i=0; \$i<\$num; ++\$i) {\r\n";
    $rv .= "        \$line = fgets(\$handler);\r\n";
    $rv .= "        if (\$line !== false) {\r\n";
    $rv .= "          \$obj = new " . $zoneName . "(\$line);\r\n";
    $rv .= "          array_push(\$p" . $player . $zoneName . ", \$obj);\r\n";
    $rv .= "        }\r\n";
    $rv .= "      }\r\n";
    $rv .= "    }\r\n";
    return $rv;
  }

  function AddWriteGamestate() {
    $writeGamestate = "";
    global $zones;
    for($i=0; $i<count($zones); ++$i) {
      $zone = $zones[$i];
      $zoneName = $zone->Name;
      $writeGamestate .= AddWriteZone($zoneName, 1);
      $writeGamestate .= AddWriteZone($zoneName, 2);
    }
    return $writeGamestate;
  }

  function AddWriteZone($zoneName, $player) {
    $rv = "";
    $rv .= "  fwrite(\$handler, count(\$p" . $player . $zoneName . ") . \"\\r\\n\");\r\n";
    $rv .= "  for(\$i=0; \$i<count(\$p" . $player . $zoneName . "); ++\$i) {\r\n";
    $rv .= "    fwrite(\$handler, \$p" . $player . $zoneName . "[\$i]->Serialize() . \"\\r\\n\");\r\n";
    $rv .= "  }\r\n";
    return $rv;
  }
?>
