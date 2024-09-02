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
        $display = fgets($handler);
        if($display !== false) {
          $displayArr = explode(":", $display);
          if($displayArr[0] == "Display") {
            $displayArr = explode(",", $displayArr[1]);
            $zoneObj->Visibility = trim($displayArr[0]);
            $zoneObj->DisplayMode = trim($displayArr[1]);
          }
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
    fwrite($handler, "  function Serialize() {\r\n");
    fwrite($handler, "    \$rv = \"\";\r\n");
    for($j=0; $j<count($zone->Properties); ++$j) {
      $property = $zone->Properties[$j];
      $propertyName = $property->Name;
      if($j > 0) fwrite($handler, "    \$rv .= \" \";\r\n");
      fwrite($handler, "    \$rv .= \$this->" . $propertyName . ";\r\n");
    }
    fwrite($handler, "    return \$rv;\r\n");
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
  fwrite($handler, GetCoreGlobals() . "\r\n");
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $zoneName = $zone->Name;
    fwrite($handler, "  \$p1" . $zoneName . " = [];\r\n");
    fwrite($handler, "  \$p2" . $zoneName . " = [];\r\n");
  }
  fwrite($handler, "  \$currentPlayer = 1;\r\n");//TODO: Change this to startPlayer (needs to be linked up w/ lobby code)
  fwrite($handler, "  \$updateNumber = 1;\r\n");//TODO: Change this to startPlayer (needs to be linked up w/ lobby code)
  fwrite($handler, "}\r\n\r\n");
  //Write gamestate function
  fwrite($handler, "function WriteGamestate(\$filepath=\"./\") {\r\n");
  fwrite($handler, GetZoneGlobals($zones) . "\r\n");
  fwrite($handler, GetCoreGlobals() . "\r\n");
  fwrite($handler, AddWriteGamestate() . "\r\n");

  fwrite($handler, "}\r\n\r\n");
  //Parse gamestate function
  fwrite($handler, "function ParseGamestate(\$filepath=\"./\") {\r\n");
  fwrite($handler, GetZoneGlobals($zones) . "\r\n");
  fwrite($handler, GetCoreGlobals() . "\r\n");
  fwrite($handler, AddReadGamestate() . "\r\n");

  fwrite($handler, "}\r\n\r\n");
  fwrite($handler, "?>");
  fclose($handler);

  //Write the Gamestate network file
  $filename = $rootPath . "/GetNextTurn.php";
  $handler = fopen($filename, "w");
  fwrite($handler, "<?php\r\n");
  fwrite($handler, "include '../Core/UILibraries.php';\r\n");
  fwrite($handler, "include '../Core/NetworkingLibraries.php';\r\n");
  fwrite($handler, "include './GamestateParser.php';\r\n");
  fwrite($handler, "include './ZoneAccessors.php';\r\n");
  fwrite($handler, "include './ZoneClasses.php';\r\n");
  //TODO: Validate these inputs
  fwrite($handler, "\$gameName = TryGet(\"gameName\");\r\n");
  fwrite($handler, "\$playerID = TryGet(\"playerID\");\r\n");
  fwrite($handler, "\$lastUpdate = TryGet(\"lastUpdate\", 0);\r\n");
  fwrite($handler, "\$count = 0;\r\n");
  fwrite($handler, "while(!CheckUpdate(\$gameName, \$lastUpdate) && \$count < 100) {\r\n");
  fwrite($handler, "  usleep(100000); //100 milliseconds\r\n");
  fwrite($handler, "  ++\$count;\r\n");
  fwrite($handler, "}\r\n");
  fwrite($handler, "ParseGamestate();\r\n");
  fwrite($handler, "SetCachePiece(\$gameName, 1, \$updateNumber);\r\n");
  fwrite($handler, "echo(\$updateNumber . \"<~>\");\r\n");

  fwrite($handler, AddGetNextTurnForPlayer(1) . "\r\n");
  fwrite($handler, AddGetNextTurnForPlayer(2) . "\r\n");

  fwrite($handler, "?>");
  
  //Write the main game file
  /*
  $filename = $rootPath . "/NextTurn.php";
  $handler = fopen($filename, "w");
  fwrite($handler, "<?php\r\n");
  fwrite($handler, AddNextTurn() . ";\r\n");
  fwrite($handler, "?>");
  */


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

  function GetCoreGlobals() {
    $coreGlobals = "";
    $coreGlobals .= "  global \$currentPlayer, \$updateNumber;\r\n";
    return $coreGlobals;
  }

  function AddReadGamestate() {
    $readGamestate = "";
    global $zones;
    $readGamestate .= "  InitializeGamestate();\r\n";
    $readGamestate .= "  global \$gameName;\r\n";
    $readGamestate .= "  \$filename = \$filepath . \"Games/\$gameName/Gamestate.txt\";\r\n";
    $readGamestate .= "  \$handler = fopen(\$filename, \"r\");\r\n";
    $readGamestate .= "  \$currentPlayer = intval(fgets(\$handler));\r\n";
    $readGamestate .= "  \$updateNumber = intval(fgets(\$handler));\r\n";
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
    global $zones;
    $writeGamestate = "";
    $writeGamestate .= "  global \$gameName;\r\n";
    $writeGamestate .= "  \$filename = \$filepath . \"Games/\$gameName/Gamestate.txt\";\r\n";
    $writeGamestate .= "  \$handler = fopen(\$filename, \"w\");\r\n";
    //First write global data
    $writeGamestate .= "  fwrite(\$handler, \$currentPlayer . \"\\r\\n\");\r\n";
    $writeGamestate .= "  fwrite(\$handler, \$updateNumber . \"\\r\\n\");\r\n";
    //Then write player zones
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
    $rv .= "    fwrite(\$handler, trim(\$p" . $player . $zoneName . "[\$i]->Serialize()) . \"\\r\\n\");\r\n";
    $rv .= "  }\r\n";
    return $rv;
  }

  function AddGetNextTurnForPlayer($player) {
    global $zones;
    $getNextTurn = "";
    for($i=0; $i<count($zones); ++$i) {
      $zone = $zones[$i];
      $zoneName = "p" . $player . $zone->Name;
      echo($zoneName . "<BR>");
      if($i > 0) $getNextTurn .= "echo(\"<~>\");\r\n";
      if($zone->DisplayMode == "Single") {
        if($zone->Visibility == "Public") {
          //$getNextTurn .= "echo \"Single Public\";\r\n";
          $getNextTurn .= "  \$arr = &Get" . $zone->Name . "(" . $player . ");\r\n";
          $getNextTurn .= "  echo(count(\$arr) > 0 ? ClientRenderedCard(\$arr[0], counters:count(\$" . $zoneName . ")) : \"Empty\");\r\n";
        } else if($zone->Visibility == "Private") {
          //Single Private
          $getNextTurn .= "  echo(ClientRenderedCard(\"CardBack\", counters:count(\$" . $zoneName . ")));\r\n";

        } else if ($zone->Visibility == "Self") {
          //$getNextTurn .= "echo \"Single Self\";\r\n";
        }
      } else if($zone->DisplayMode == "All") {
        $getNextTurn .= "  \$arr = &Get" . $zone->Name . "(" . $player . ");\r\n";
        $getNextTurn .= "  for(\$i=0; \$i<count(\$arr); ++\$i) {\r\n";
        $getNextTurn .= "    if(\$i > 0) echo(\"<|>\");\r\n";
        $getNextTurn .= "    \$obj = \$arr[\$i];\r\n";
        if($zone->Visibility == "Public") {
          $getNextTurn .= "    echo(ClientRenderedCard(\$obj->CardID));\r\n";
        } else if($zone->Visibility == "Private") {
          $getNextTurn .= "    echo(ClientRenderedCard(\"CardBack\"));\r\n";
        } else if ($zone->Visibility == "Self") {
          $getNextTurn .= "    if(\$playerID == " . $player . ") echo(ClientRenderedCard(\$obj->CardID));\r\n";
          $getNextTurn .= "    else echo(ClientRenderedCard(\"CardBack\"));\r\n";
        }
        $getNextTurn .= "  }\r\n";
      } else if($zone->DisplayMode == "Count") {
        if($zone->Visibility == "Public") {
          //$getNextTurn .= "echo \"Count Public\";\r\n";
        } else if($zone->Visibility == "Private") {
          //$getNextTurn .= "echo \"Count Private\";\r\n";
        } else if ($zone->Visibility == "Self") {
          //$getNextTurn .= "echo \"Count Self\";\r\n";
        }
      }
    }
    return $getNextTurn;
  }

  function AddNextTurn() {
    $nextTurn = "";
    return $nextTurn;
  }
?>
