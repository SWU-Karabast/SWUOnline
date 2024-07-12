<?php

  function BackgroundColor($darkMode)
  {
    if($darkMode) return "rgba(20, 20, 20, 0.7)";
    else return "rgba(255, 255, 255, 0.7)";
  }

  function Card($cardNumber, $folder, $maxHeight, $action=0, $showHover=0, $overlay=0, $borderColor=0,$counters=0,$actionDataOverride="",$id="",$rotate=false)
  {//
    global $playerID, $gameName, $darkMode;
    if($darkMode == null) $darkMode = false;
    $fileExt = ".png";
    if($cardNumber == "ENDSTEP" || $cardNumber == "ENDTURN" || $cardNumber == "RESUMETURN")
    {
      $folder = str_replace("CardImages", "Images", $folder);
    }
    else if(mb_strpos($folder, "CardImages") !== false)
    {
      $folder = str_replace("CardImages", "WebpImages2", $folder);
      $fileExt = ".webp";
    }
    $actionData = $actionDataOverride != "" ? $actionDataOverride : $cardNumber;
    //Enforce 375x523 aspect ratio as exported (.71)
    $margin = "margin:0px;";
    if($borderColor != -1) $margin = $borderColor > 0 ? "margin:2px;" : "margin:5px;";
    $rv = "<a style='" . $margin . " position:relative; display:inline-block;'" . ($showHover > 0 ? " onmouseover='ShowCardDetail(event, this)' onmouseout='HideCardDetail()'" : "") . ($action > 0 ? " href=\"./ProcessInput.php?gameName=$gameName&playerID=$playerID&mode=$action&cardID=" . $actionData . "\" " : "") . ">";
    $border = $borderColor > 0 ? "border-radius:20px; border:3px solid " . BorderColorMap($borderColor) . ";" : "";
    if($rotate == false) { $height = $maxHeight; $width = ($maxHeight * .71); }
    else { $height = ($maxHeight * .71); $width = $maxHeight; }
    $rv .= "<img " . ($id != "" ? "id='".$id."-img' ":"") . "style='" . $border . " height:" . $height . "; width:" . $width . "px;' src='./" . $folder . "/" . $cardNumber . $fileExt . "' />";
    $rv .= "<div " . ($id != "" ? "id='".$id."-ovr' ":"") . "style='visibility:" . ($overlay == 1 ? "visible" : "hidden") . "; width:100%; height:100%; top:0px; left:0px; position:absolute; background: rgba(0, 0, 0, 0.5); z-index: 1;'></div>";
    if($counters != 0) $rv .= "<div style='top:45%; left:45%; position:absolute; z-index: 10; background:" . BackgroundColor($darkMode) . "; font-size:30px;'>" . $counters . "</div>";
    $rv .= "</a>";
    return $rv;
  }

  function BorderColorMap($code)
  {
    switch($code)
    {
      case 1: return "DeepSkyBlue";
      case 2: return "red";
      case 3: return "yellow";
      case 4: return "Gray";
      case 5: return "Tan";
      case 6: return "#00FF66";
    }
  }

  function CreateButton($playerID, $caption, $mode, $input, $size="", $image="", $tooltip="")
  {
    global $gameName;
    if($image != "")
    {
      $rv = "<img style='cursor:pointer;' src='" . $image . "' onclick=\"document.location.href = './ProcessInput.php?gameName=$gameName&playerID=$playerID&mode=$mode&buttonInput=$input'\">";
    }
    else $rv = "<button title='$tooltip' " . ($size != "" ? "style='font-size:$size;' " : "") . "onclick=\"document.location.href = './ProcessInput.php?gameName=$gameName&playerID=$playerID&mode=$mode&buttonInput=$input'\">" . $caption . "</button>";
    return $rv;
  }

  function ProcessInputLink($player, $mode, $input, $event='onmousedown')
  {
    global $gameName;
    return " " . $event . "='SubmitInput(\"" . $mode . "\", \"&buttonInput=" . $input . "\");'";
  }

  function CreateForm($playerID, $caption, $mode, $count)
  {
    global $gameName;
    $rv = "<form action='./ProcessInput.php'>";
    $rv .= "<input type='submit' value='" . $caption . "'>";
    $rv .= "<input type='hidden' id='gameName' name='gameName' value='" . $gameName . "'>";
    $rv .= "<input type='hidden' id='playerID' name='playerID' value='" . $playerID . "'>";
    $rv .= "<input type='hidden' id='mode' name='mode' value='" . $mode . "'>";
    $rv .= "<input type='hidden' id='chkCount' name='chkCount' value='" . $count . "'>";
    return $rv;
  }

  //input = ?
  //value = ?
  //immediateSubmitMode = If set, add onchange event to submit immediately instead of form submit
  //defaultChecked = Will be checked by default if true
  //label = label to display
  function CreateCheckbox($input, $value, $immediateSubmitMode=-1, $defaultChecked=false, $label="")
  {
    global $playerID;
    $submitLink = "";
    $check = "";
    if($immediateSubmitMode != -1) $submitLink = ProcessInputLink($playerID, $immediateSubmitMode, $input, "onchange");
    if($defaultChecked) $check = " checked='checked'";
    $rv = "<input type='checkbox' " . $submitLink . " id='chk" . $input . "' name='chk" . $input . "' value='" . $value . "' " . $check . ">";
    $rv .= "<label for='chk" . $input . "'>" . $label . "</label>";
    return $rv;
  }

  function CreatePopup($id, $fromArr, $canClose, $defaultState=0, $title="", $arrElements=1,$customInput="",$path="./", $big=false)
  {
    global $combatChain, $darkMode;
    if($darkMode == null) $darkMode = false;
    $top = "50%"; $left = "20%"; $width = "60%"; $height = "40%";
    if($big) { $top = "5%"; $left = "5%";  $width = "80%"; $height = "90%"; }
    $rv = "<div id='" . $id . "' style='overflow-y: auto; background-color:" . BackgroundColor($darkMode) . "; z-index:10; position: absolute; top:" . $top . "; left:" . $left . "; width:" . $width . "; height:" . $height . ";" . ($defaultState == 0 ? " display:none;" : "") . "'>";
    if($title != "") $rv .= "<h1>" . $title . "</h1>";
    if($canClose == 1) $rv .= "<div style='position:absolute; cursor:pointer; top:0px; right:0px; font-size:48px; color:red; border:2px solid black;' onclick='(function(){ document.getElementById(\"" . $id . "\").style.display = \"none\";})();'>X</div>";
    for($i=0; $i<count($fromArr); $i += $arrElements)
    {
      $rv .= Card($fromArr[$i], $path . "CardImages", 150, 0, 1);
    }
    $rv .= $customInput;
    $rv .= "</div>";
    return $rv;
  }

  function CardStatsUI($player)
  {
    $rv = "<div id='cardStats' style='background-color: rgba(255,255,255,0.80); z-index:100; position: absolute; top:120px; left: 50px; right: 250px; bottom:50px;'>";
    $rv .= CardStats($player);
    $rv .= "</div>";
    return $rv;
  }

  function CardStats($player)
  {
    global $TurnStats_DamageThreatened, $TurnStats_DamageDealt, $TurnStats_CardsPlayedOffense, $TurnStats_CardsPlayedDefense, $TurnStats_CardsPitched, $TurnStats_CardsBlocked, $firstPlayer;
    global $TurnStats_ResourcesUsed, $TurnStats_CardsLeft, $TurnStats_DamageBlocked;
    $cardStats = &GetCardStats($player);
    $rv = "<div style='float:left;'>";
    $rv .= "<h2>Card Play Stats</h2>";
    $rv .= "<table><tr><td>Card ID</td><td>Times Played</td><td>Times Blocked</td><td>Times Pitched</td></tr>";
    for($i=0; $i<count($cardStats); $i+=CardStatPieces())
    {
      $pitch = PitchValue($cardStats[$i]);
      $timesPlayed = $cardStats[$i+1];
      $playStyle = "";
      if($pitch == 3 && $timesPlayed > 1) $playStyle = "font-weight: bold; color:red;";
      else if($pitch == 3 && $timesPlayed > 0) $playStyle = "font-weight: bold; color:gold;";
      else if($pitch == 2 && $timesPlayed > 4) $playStyle = "font-weight: bold; color:red;";
      else if($pitch == 2 && $timesPlayed > 2) $playStyle = "font-weight: bold; color:gold;";
      $timesPitched = $cardStats[$i+3];
      $pitchStyle = "";
      if($pitch == 1 && $timesPitched > 1) $pitchStyle = "font-weight: bold; color:red;";
      else if($pitch == 1 && $timesPitched > 0) $pitchStyle = "font-weight: bold; color:gold;";
      else if($pitch == 2 && $timesPitched > 4) $pitchStyle = "font-weight: bold; color:red;";
      else if($pitch == 2 && $timesPitched > 2) $pitchStyle = "font-weight: bold; color:gold;";
      $rv .= "<tr><td>" . CardLink($cardStats[$i], $cardStats[$i]) . "</td><td style='" . $playStyle . "'>" . $timesPlayed . "</td><td>" . $cardStats[$i+2] . "</td><td style='" . $pitchStyle . "'>" . $timesPitched . "</td></tr>";
    }
    $rv .= "</table>";
    $rv .= "</div>";
    $turnStats = &GetTurnStats($player);
    $rv .= "<div style='float:left;'>";
    $rv .= "<h2>Turn Stats</h2>";
    if($player == $firstPlayer) $rv .= "<i>First turn omitted for first player.</i><br>";
    //Damage stats
    $totalDamageThreatened = 0;
    $totalDamageDealt = 0;
    $totalResourcesUsed = 0;
    $totalCardsLeft = 0;
    $totalDefensiveCards = 0;
    $totalBlocked = 0;
    $numTurns = 0;
    $start = ($player == $firstPlayer ? TurnStatPieces() : 0);//Skip first turn for first player
    if(count($turnStats) > 0)
    {
      for($i=$start; $i<count($turnStats); $i+=TurnStatPieces())
      {
        $totalDamageThreatened += $turnStats[$i + $TurnStats_DamageThreatened];
        $totalDamageDealt += $turnStats[$i + $TurnStats_DamageDealt];
        $totalResourcesUsed += $turnStats[$i + $TurnStats_ResourcesUsed];
        $totalCardsLeft += $turnStats[$i + $TurnStats_CardsLeft];
        $totalDefensiveCards += ($turnStats[$i+$TurnStats_CardsPlayedDefense] + $turnStats[$i+$TurnStats_CardsBlocked]);
        $totalBlocked += $turnStats[$i+$TurnStats_DamageBlocked];
        ++$numTurns;
      }
    }
    if($numTurns > 0)
    {
      $rv .= "Total Damage Threatened: " . $totalDamageThreatened . "<br>";
      $rv .= "Total Damage Dealt: " . $totalDamageDealt . "<br>";
      $rv .= "Average Damage Threatened per turn: " . round($totalDamageThreatened/$numTurns, 2) . "<br>";
      $rv .= "Average Damage Dealt per turn: " . round($totalDamageDealt/$numTurns, 2) . "<br>";
      $totalOffensiveCards = 4*$numTurns - $totalDefensiveCards;
      if($totalOffensiveCards > 0) $rv .= "Average damage threatened per offensive card: " . round($totalDamageThreatened/$totalOffensiveCards, 2) . "<br>";
      $rv .= "Average Resources Used per turn: " . round($totalResourcesUsed/$numTurns, 2) . "<br>";
      $rv .= "Average Cards Left Over per turn: " . round($totalCardsLeft/$numTurns, 2) . "<br>";
      $rv .= "Average Value per turn (Damage threatened + block): " . round(($totalDamageThreatened + $totalBlocked)/$numTurns, 2) . "<br>";
      //Cards per turn stats
      $rv .= "<table><tr><td>Round Number</td><td>Cards Played</td><td>Cards Blocked</td><td>Cards Pitched</td><td>Resources Used</td><td>Cards Left</td></tr>";
      for($i=0; $i<count($turnStats); $i+=TurnStatPieces())
      {
        $rv .= "<tr><td>" . (($i / TurnStatPieces()) + 1) . "</td><td>" . ($turnStats[$i+$TurnStats_CardsPlayedOffense] + $turnStats[$i+$TurnStats_CardsPlayedDefense]) . "</td><td>" . $turnStats[$i+$TurnStats_CardsBlocked] . "</td><td>" . $turnStats[$i+$TurnStats_CardsPitched] . "</td><td>" . $turnStats[$i+$TurnStats_ResourcesUsed] . "</td><td>" . $turnStats[$i+$TurnStats_CardsLeft] . "</td></tr>";
      }
      $rv .= "</table>";
    }
    $rv .= "</div>";
    return $rv;
  }

  function AttackModifiers($attackModifiers)
  {
    $rv = "";
    for($i=0; $i<count($attackModifiers); $i += 2)
    {
      $idArr = explode("-", $attackModifiers[$i]);
      $cardID = $idArr[0];
      $bonus = $attackModifiers[$i+1];
      if($bonus == 0) continue;
      $cardLink = CardLink($cardID, $cardID);
      $rv .= ($cardLink != "" ? $cardLink : $cardID) . " gives " . ($bonus > 0 ? "+" : "") . $bonus . "<BR>";
    }
    return $rv;
  }

  function PitchColor($pitch)
  {
    switch($pitch)
    {
      case 1: return "red";
      case 2: return "GoldenRod";
      case 3: return "blue";
      default: return "LightSlateGrey";
    }
  }

  function BanishUI($from="")
  {
    global $turn, $currentPlayer, $playerID, $cardSize;
    $rv = "";
    $size = ($from == "HAND" ? $cardSize : 180);
    $banish = GetBanish($playerID);
    for($i=0; $i<count($banish); $i+=BanishPieces()) {
      $action = $currentPlayer == $playerID && IsPlayable($banish[$i], $turn[0], "BANISH", $i) ? 14 : 0;
      $border = CardBorderColor($banish[$i], "BANISH", $action > 0);
      $mod = explode("-", $banish[$i+1])[0];
      if($mod == "INT") $rv .= Card($banish[$i], "CardImages", $size, 0, 1, 1);//Display intimidated cards grayed out and unplayable
      else if($mod == "TCL" || $mod == "TT" || $mod == "TCC" || $mod == "NT" || $mod == "INST" || $mod == "MON212" || $mod == "ARC119")
        $rv .= Card($banish[$i], "CardImages", $size, $action, 1, 0, $border, 0, strval($i));//Display banished cards that are playable
      else if($from != "HAND")
      {
        if(PlayableFromBanish($banish[$i], $banish[$i+1]) || AbilityPlayableFromBanish($banish[$i]))
          $rv .= Card($banish[$i], "CardImages", $size, $action, 1, 0, $border, 0, strval($i));
        else
          $rv .= Card($banish[$i], "CardImages", $size, 0, 1, 0, $border);
      }
    }
    return $rv;
  }

  function CardBorderColor($cardID, $from, $isPlayable)
  {
    global $playerID, $currentPlayer, $turn;
    if($playerID != $currentPlayer) return 0;
    if($turn[0] == "B") return ($isPlayable ? 6 : 0);
    if($from == "BANISH")
    {
      if($isPlayable || PlayableFromBanish($cardID)) return 4;
      return 0;
    }
    if($isPlayable && ComboActive($cardID)) return 3;
    if($isPlayable && HasReprise($cardID) && RepriseActive()) return 3;
    else if($isPlayable) return 6;
    return 0;
  }

  // TODO: need to have {{Card}} notation if the client is react as this is broken on React FE.
  function CardLink($caption, $cardNumber)
  {
    global $playerID;
    //$file = "'./" . "CardImages" . "/" . $cardNumber . ".png'";
    $name = CardName($cardNumber);
    if($name == "") return "";
      $color = "Gray";
    //$file = "'./" . "BigCardImages" . "/" . $cardNumber . ".png'";
    $file = "'./" . "WebpImages2" . "/" . $cardNumber . ".webp'";
    return "<b><span style='color:" . $color . "; cursor:default;' onmouseover=\"ShowDetail(event," . $file . ")\" onmouseout='HideCardDetail()'>" . $name . "</span></b>";
  }

?>
