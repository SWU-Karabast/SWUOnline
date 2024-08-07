<?php

include 'Libraries/HTTPLibraries.php';

$returnDelim = "GSDELIM";

//We should always have a player ID as a URL parameter
$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("NaN" . $returnDelim);
  exit;
}
$playerID = TryGet("playerID", 3);
if (!is_numeric($playerID)) {
  echo ("NaN" . $returnDelim);
  exit;
}

if (!file_exists("./Games/" . $gameName . "/")) {
  header('HTTP/1.0 403 Forbidden');
  exit;
}

$authKey = TryGet("authKey", 3);
$lastUpdate = intval(TryGet("lastUpdate", 0));
$windowWidth = intval(TryGet("windowWidth", 0));
$windowHeight = intval(TryGet("windowHeight", 0));
$lastCurrentPlayer = intval(TryGet("lastCurrentPlayer", 0));

if (($playerID == 1 || $playerID == 2) && $authKey == "") {
  if (isset($_COOKIE["lastAuthKey"])) $authKey = $_COOKIE["lastAuthKey"];
}

include "HostFiles/Redirector.php";
include "Libraries/SHMOPLibraries.php";
include "WriteLog.php";

SetHeaders();

if($playerID == 3 && GetCachePiece($gameName, 9) != "1") {
  echo($playerID . " " . $gameName . " " . GetCachePiece($gameName, 9));
  header('HTTP/1.0 403 Forbidden');
  exit;
}

$isGamePlayer = $playerID == 1 || $playerID == 2;
$opponentDisconnected = false;
$opponentInactive = false;

$currentTime = round(microtime(true) * 1000);
if ($isGamePlayer) {
  $playerStatus = intval(GetCachePiece($gameName, $playerID + 3));
  if ($playerStatus == "-1") WriteLog("Player $playerID has connected.");
  SetCachePiece($gameName, $playerID + 1, $currentTime);
  SetCachePiece($gameName, $playerID + 3, "0");
  if ($playerStatus > 0) {
    WriteLog("Player $playerID has reconnected.");
    SetCachePiece($gameName, $playerID + 3, "0");
    GamestateUpdated($gameName);
  }
}
$count = 0;
$cacheVal = intval(GetCachePiece($gameName, 1));
while ($lastUpdate != 0 && $cacheVal <= $lastUpdate) {
  usleep(100000); //100 milliseconds
  $currentTime = round(microtime(true) * 1000);
  $readCache = ReadCache($gameName);
  if($readCache == "") break;
  $cacheArr = explode(SHMOPDelimiter(), $readCache);
  $cacheVal = intval($cacheArr[0]);
  if ($isGamePlayer) {
    SetCachePiece($gameName, $playerID + 1, $currentTime);
    $otherP = ($playerID == 1 ? 2 : 1);
    $oppLastTime = intval($cacheArr[$otherP]);
    $oppStatus = $cacheArr[$otherP + 2];
    if (($currentTime - $oppLastTime) > 20000 && ($oppStatus == "0")) {
      WriteLog("Opponent has disconnected.");
      $opponentDisconnected = true;
      SetCachePiece($gameName, $otherP + 3, "2");
      GamestateUpdated($gameName);
    }
    //Handle server timeout
    $lastUpdateTime = $cacheArr[5];
    if ($currentTime - $lastUpdateTime > 90000 && $cacheArr[11] != "1")//90 seconds
    {
      SetCachePiece($gameName, 12, "1");
      $opponentInactive = true;
      $lastUpdate = 0;
    }
  }
  ++$count;
  if ($count == 100) break;
}
$otherP = ($playerID == 1 ? 2 : 1);
$opponentDisconnected = GetCachePiece($gameName, $otherP + 3) == "2";

if ($lastUpdate != 0 && $cacheVal <= $lastUpdate) {
  echo "0";
  exit;
} else {
  //First we need to parse the game state from the file
  include "ParseGamestate.php";
  include 'GameLogic.php';
  include "GameTerms.php";
  include "Libraries/UILibraries2.php";
  include "Libraries/StatFunctions.php";
  include "Libraries/PlayerSettings.php";

  if ($turn[0] == "REMATCH" && intval($playerID) != 3) {
    include "MenuFiles/ParseGamefile.php";
    include "MenuFiles/WriteGamefile.php";
    if ($gameStatus == $MGS_GameStarted) {
      include "AI/CombatDummy.php";
      $origDeck = "./Games/" . $gameName . "/p1DeckOrig.txt";
      if (file_exists($origDeck)) copy($origDeck, "./Games/" . $gameName . "/p1Deck.txt");
      $origDeck = "./Games/" . $gameName . "/p2DeckOrig.txt";
      if (file_exists($origDeck)) copy($origDeck, "./Games/" . $gameName . "/p2Deck.txt");
      $gameStatus = (IsPlayerAI(2) ? $MGS_ReadyToStart : $MGS_ChooseFirstPlayer);
      SetCachePiece($gameName, 14, $gameStatus);
      $firstPlayer = 1;
      $firstPlayerChooser = ($winner == 1 ? 2 : 1);
      unlink("./Games/" . $gameName . "/gamestate.txt");

      $errorFileName = "./BugReports/CreateGameFailsafe.txt";
      $errorHandler = fopen($errorFileName, "a");
      date_default_timezone_set('America/Chicago');
      $errorDate = date('m/d/Y h:i:s a');
      $errorOutput = "Rematch failsafe hit for game $gameName at $errorDate";
      fwrite($errorHandler, $errorOutput . "\r\n");
      fclose($errorHandler);

      WriteLog("Player $firstPlayerChooser lost and will choose first player for the rematch.");
    }
    WriteGameFile();
    $currentTime = round(microtime(true) * 1000);
    SetCachePiece($gameName, 2, $currentTime);
    SetCachePiece($gameName, 3, $currentTime);
    echo ("1234REMATCH");
    exit;
  }

  $targetAuth = ($playerID == 1 ? $p1Key : $p2Key);
  if ($playerID != 3 && $authKey != $targetAuth) {
    echo ("999999" . $returnDelim);
    exit;
  }

  echo($cacheVal . $returnDelim);
  echo(implode("~", $events) . $returnDelim);
  
  if ($currentPlayer == $playerID) {
    $icon = "ready.png";
    $readyText = "You are the player with priority.";
  } else {
    $icon = "notReady.png";
    $readyText = "The other player has priority.";
  }

  if (count($turn) == 0) {
    RevertGamestate();
    GamestateUpdated($gameName);
    exit();
  }

  echo ("<div id='iconHolder'>" . $icon . "</div>");

  if ($windowWidth / 16 > $windowHeight / 9) $windowWidth = $windowHeight / 9 * 16;

  $cardSize = ($windowWidth != 0 ? intval($windowWidth / 13) : 120);
  //$cardSize = ($windowWidth != 0 ? intval($windowWidth / 16) : 120);
  if (!IsDynamicScalingEnabled($playerID)) $cardSize = 120; //Temporarily disable dynamic scaling
  $rightSideWidth = (IsDynamicScalingEnabled($playerID) ? intval($windowWidth * 0.15) : (IsStreamerMode($playerID) ? 300 : 210));
  $cardSizeAura = intval($cardSize * .8); //95;
  $cardSizeEquipment = intval($cardSize * .8);
  $cardEquipmentWidth = intval($cardSizeEquipment * 0.71);
  $cardWidth = intval($cardSize * 0.73);
  $cardHeight = $cardWidth;
  $cardIconSize = intval($cardSize / 2.7); //40
  $cardIconLeft = intval($cardSize / 4.2); //30
  $cardIconTop = intval($cardSize / 4.2); //30
  $bigCardSize = intval($cardSize * 1.667); //200;
  $permLeft = intval(GetCharacterLeft("E", "Arms")) + $cardWidth + 20;
  $permWidth = "calc(50% - " . ($cardWidth * 2 + 30 + $permLeft) . "px)";
  $permHeight = $cardSize * 2 + 20;
  $counterHeight = IsDynamicScalingEnabled($playerID) ? intval($cardSize / 4.6) : 28;

  $darkMode = IsDarkMode($playerID);
  $manualMode = IsManualMode($playerID);

  if ($darkMode) $backgroundColor = "rgba(74, 74, 74, 0.9)";
  else $backgroundColor = "rgba(0, 0, 0, 0)";

  $blankZone = ($darkMode ? "blankZoneDark" : "blankZone");
  $borderColor = ($darkMode ? "#DDD" : "rgba(0, 0, 0, 0)");
  $fontColor = ($darkMode ? "#1a1a1a" : "white");
  $bordelessFontColor = "#DDD";

  //Choose Cardback
  $MyCardBack = GetCardBack($playerID);
  $TheirCardBack = GetCardBack($playerID == 1 ? 2 : 1);
  $otherPlayer = ($playerID == 1 ? 2 : 1);

  //Display background
  echo ("<div class='container game-bg'><img src='./Images/gamebg.jpg'/></div>");

  //Base Damage Numbers
  echo ("<div class='base-dmg-wrapper'><div class='base-dmg-position'><span class='base-my-dmg'>$myHealth</span>");
      echo (($manualMode ? "<span class='base-my-dmg-manual'>" . CreateButton($playerID, "+1", 10006, 0, "20px") . CreateButton($playerID, "-1", 10005, 0, "20px") . "</span>" : ""));
  echo ("<span class='base-their-dmg'>$theirHealth</span>");
  echo (($manualMode ? "<span class='base-their-dmg-manual'>" . CreateButton($playerID, "+1", 10008, 0, "20px") . CreateButton($playerID, "-1", 10007, 0, "20px") . "</span>" : ""));
  echo ("</div></div>");
  echo ("<div class='base-their-dmg-manual'></div>");
  if ($turn[0] == "ARS" || (count($layers) > 0 && $layers[0] == "ENDTURN")) {
    $passLabel = "End Turn";
    $fontSize = 30;
    $left = 65;
    $top = 20;
  } else if(IsReplay())
  {
    $passLabel = "Next";
    $fontSize = 36;
    $left = 85;
    $top = 15;
  } else {
    $passLabel = "Pass";
    $fontSize = 36;
    $left = 85;
    $top = 15;
  }

  if($initiativePlayer == $playerID || ($playerID == 3 && $initiativePlayer == 2)) {
    echo ("<div class='my-initiative'><span>Initiative</span>");
  } else {
    echo ("<div class='their-initiative'><span>Initiative</span>");
  }
  echo ("</div>");

  //Now display the screen for this turn
  echo ("<div class='display-game-screen'>");
  echo ("<div class='status-wrapper'>");

  echo (($manualMode ? "<span style='color: " . $fontColor . "; text-shadow: 2px 0 0 " . $borderColor . ", 0 -2px 0 " . $borderColor . ", 0 2px 0 " . $borderColor . ", -2px 0 0 " . $borderColor . ";'>Add to hand: </span><input id='manualAddCardToHand' type='text' /><input class='manualAddCardToHand-button' type='button' value='Add' onclick='AddCardToHand()' />&nbsp;" : ""));

  //Tell the player what to pick
  if ($turn[0] != "OVER") {
    $helpText = ($currentPlayer != $playerID ? " Waiting for other player to choose " . TypeToPlay($turn[0]) . "&nbsp" : " " . GetPhaseHelptext() . "&nbsp;");

    echo ("<span class='playerpick-span'><img class='playerpick-img' title='" . $readyText . "' src='./Images/" . $icon . "'/>");
    if ($currentPlayer == $playerID) {
      echo ($helpText);
      if ($turn[0] == "P" || $turn[0] == "CHOOSEHANDCANCEL" || $turn[0] == "CHOOSEDISCARDCANCEL") echo ("(" . ($turn[0] == "P" ? $myResources[0] . " of " . $myResources[1] . " " : "") . "or " . CreateButton($playerID, "Cancel", 10000, 0, "18px") . ")");
      if (CanPassPhase($turn[0])) {
        if ($turn[0] == "B") echo (CreateButton($playerID, "Undo Block", 10001, 0, "18px") . " " . CreateButton($playerID, "Pass", 99, 0, "18px") . " " . CreateButton($playerID, "Pass Block and Reactions", 101, 0, "16px", "", "Reactions will not be skipped if the opponent reacts"));
      }
      if ($opponentDisconnected == true && $playerID != 3) {
        echo (CreateButton($playerID, "Claim Victory", 100007, 0, "18px", "", "claimVictoryButton"));
      }
    } else {
      if (($currentPlayerActivity == 2 || $opponentDisconnected == true) && $playerID != 3) {
        echo ("Opponent is inactive " . CreateButton($playerID, "Claim Victory", 100007, 0, "18px", "", "claimVictoryButton"));
      } else {
        echo ($helpText);
      }
    }
    echo ("</span>");
  }
  if (IsManualMode($playerID)) echo ("&nbsp;" . CreateButton($playerID, "Turn Off Manual Mode", 26, $SET_ManualMode . "-0", "18px", "", "", true));

  if ((CanPassPhase($turn[0]) && $currentPlayer == $playerID) || (IsReplay() && $playerID == 3)) {
    $prompt = "";
    // Pass Button - Active then Inactive (which is hidden) 
?>
    <div title='Space is the shortcut to pass.' <?= ProcessInputLink($playerID, 99, 0, prompt: $prompt) ?> class='passButton'>
    <span class='pass-label'>
        <?= $passLabel ?>
      </span>
      <span class='pass-tag'>
        [Space]
      </span>
    </div>

  <?php
  }
  
  if($turn[0] == "M" && $initiativeTaken != 1 && $currentPlayer == $playerID) echo ("&nbsp;" . CreateButton($playerID, "Claim Initiative", 34, "-", "18px"));
  
  echo ("</div>");
  echo ("</div>");

  //Deduplicate current turn effects
  $friendlyEffects = "";
  $opponentEffects = "";
  for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnPieces()) {
    $cardID = explode("-", $currentTurnEffects[$i])[0];
    $cardID = explode(",", $cardID)[0];
    $cardID = explode("_", $cardID)[0];
    $isFriendly = ($playerID == $currentTurnEffects[$i + 1] || $playerID == 3 && $otherPlayer != $currentTurnEffects[$i + 1]);
    $color = ($isFriendly ? "#00BAFF" : "#FB0007"); // Me : Opponent
    $effect = "<div class='effect-display' style='border:1px solid " . $color . ";'>";
    $effect .= Card($cardID, "crops", 65, 0, 1);
    $effect .= "</div>";
    if ($isFriendly) $friendlyEffects .= $effect;
    else $opponentEffects .= $effect;
  }

  $groundLeft = "53%";
  $arenaWidth = "32%";

  //Effects UI
  echo ("<div class='opponent-effects'>");
  echo ($opponentEffects);
  echo ("</div>");
  echo ("<div class='friendly-effects'>");
  echo ($friendlyEffects);
  echo ("</div>");

  //Space Arena
  echo ("<div id='spaceArena'>");
  echo ("</div>");

  //Ground Arena
  echo ("<div id='groundArena' ondragover='dragOver(event)' ondrop='drop(event)'>");
  echo ("</div>");

  //Ground Arena Dimmer
  echo ("<div class='groundArena-dimmer'>");
  echo ("</div>");

  // Triggers

  if ($turn[0] == "INSTANT" && count($layers) > 0) {
      $content = "";
      
      // Add a title and instructions for triggers
      $content .= "<div class='trigger-order'><h2>Choose Trigger Order</h2></div>";
      
      // Function to get the caption based on layer type
      function getCaption($layer) {
          $captions = [
              "PLAYABILITY" => "When Played",
              "ATTACKABILITY" => "On Attack",
              "ACTIVATEDABILITY" => "Ability"
          ];
          return $captions[$layer] ?? ""; // Return the caption if it exists, otherwise return an empty string
      }
  
      // Check if the first layer is an attack or weapon, and if so, get and display the attack target
      if (CardType($layers[0]) == "AA" || IsWeapon($layers[0])) {
          $attackTarget = GetAttackTarget();
          if ($attackTarget != "NA") {
              $content .= "&nbsp;Attack Target: " . GetMZCardLink($defPlayer, $attackTarget);
          }
      }
  
      // Add a note about trigger resolution if applicable
      if ($dqState[8] != -1) {
        $content .= "<div class='trigger-order'><p>Use the arrows below to set the order abilities trigger in</p></div>";
      }
  
      // Start the container for the tiles and labels using flexbox
      $content .= "<div class='tiles-wrapper' >";
  
      $totalLayers = count($layers); // Total number of layers
      $layerPieces = LayerPieces();  // Number of pieces per layer
  
      for ($i = 0; $i < $totalLayers; $i += $layerPieces) {
          if ($i == 0) {
              // Add 'First' text before the first tile
              $content .= "<div class='trigger-first'><p>First</p></div>";
          }
  
          $layerName = IsAbilityLayer($layers[$i]) ? $layers[$i + 2] : $layers[$i]; // Get the layer name
          $layerController = $layers[$i + 1]; // Get the layer controller
          $layerColor = ($layerController == $playerID) ? 1 : 2; // Determine the color based on the controller
          
          if ($playerID == 3) { // Special case for playerID 3
              $layerColor = ($layerController == $otherPlayer) ? 2 : 1;
          }
  
          // Count the number of tiles with the same name if the layer is tileable
          $nbTiles = IsTileable($layerName) ? array_reduce($layers, function($count, $layer, $index) use ($layerName, $layerPieces) {
              $name = ($layer == "LAYER" || IsAbilityLayer($layer)) ? $layers[$index + 2] : $layer;
              return $name == $layerName ? $count + 1 : $count;
          }, 0) : 0;
  
          // Get the caption for the current layer
          $caption = getCaption($layers[$i]);
          
          // Determine counters for the card, using number of tiles if tileable, otherwise using the caption
          $counters = IsTileable($layerName) && $nbTiles > 1 ? $nbTiles : ($caption ?: 0);
  
          // Add the card to the content
          $cardId = $layerName;
          if($cardId == "AFTERPLAYABILITY") $cardId = explode(',', $layers[$i+5])[0];
          $content .= "<div class='tile' style='max-width:{$cardSize}px;'>" . Card($cardId, "concat", $cardSize, 0, 1, 0, $layerColor, $counters, controller: $layerController);
  
          // Add reorder buttons for ability layers if applicable
          if (IsAbilityLayer($layers[$i]) && $dqState[8] >= $i && $playerID == $mainPlayer) {
              if ($i < $dqState[8]) {
                  $content .= "<span class='reorder-button'>" . CreateButton($playerID, ">", 31, $i, "18px", useInput:true) . "</span>";
              }
              if ($i > 0) {
                  $content .= "<span class='reorder-button'>" . CreateButton($playerID, "<", 32, $i, "18px", useInput:true) . "</span>";
              }
          }
          
          $content .= "</div>"; // Close the tile container
  
          if ($i + $layerPieces >= $totalLayers) {
              // Add 'Last' text after the last tile
              $content .= "<div class='trigger-last'><p>Last</p></div>";
          }
      }
  
      // Close the container for the tiles and labels
      $content .= "</div>"; // Close the tiles-wrapper
  
      echo CreatePopup("INSTANT", [], 0, 1, "", 1, $content, "./", false, true); // Output the content in a popup
  }
  
  if ($turn[0] == "OVER") {
    if ($roguelikeGameID != "") {
      $caption = (GetHealth($playerID) > 0 ? "Continue Adventure" : "Game Over");
      if(GetHealth($playerID) > 0) $content = CreateButton($playerID, "Continue Adventure", 100011, 0, "24px", "", "", false, true);
      else $content = CreateButton($playerID, "Game Over!", 100001, 0, "24px", "", "", false, true);
    } else {
      $content = CreateButton($playerID, "Main Menu", 100001, 0, "24px", "", "", false, true);
      if ($playerID == 1 && $theirCharacter[0] != "DUMMY") $content .= "&nbsp;" . CreateButton($playerID, "Rematch", 100004, 0, "24px");
      if ($playerID == 1) $content .= "&nbsp;" . CreateButton($playerID, "Quick Rematch", 100000, 0, "24px");
      //if ($playerID != 3 && IsPatron($playerID)) $content .= "&nbsp;" . CreateButton($playerID, "Save Replay", 100012, 0, "24px");
      if ($playerID != 3) {
        $time = ($playerID == 1 ? $p1TotalTime : $p2TotalTime);
        $totalTime = $p1TotalTime + $p2TotalTime;
        $content .= "<BR><span class='Time-Span'>Your Play Time: " . intval($time / 60) . "m" . $time % 60 . "s - Game Time: " . intval($totalTime / 60) . "m" . $totalTime % 60 . "s</span>";
      }
    }

    $content .= "</div>";
    $content .= CardStats($playerID);
    echo CreatePopup("OVER", [], 1, 1, "Player " . $winner . " Won! ", 1, $content, "./", true);
  }

  if ($turn[0] == "DYNPITCH" && $turn[1] == $playerID) {
    $content = "<div display:inline;'>";
    $options = explode(",", $turn[2]);
    for ($i = 0; $i < count($options); ++$i) {
      $content .= CreateButton($playerID, $options[$i], 7, $options[$i], "24px");
    }
    $content .= "</div>";
    echo CreatePopup("DYNPITCH", [], 0, 1, "Choose " . TypeToPlay($turn[0]), 1, $content);
  }

  if (($turn[0] == "BUTTONINPUT" || $turn[0] == "CHOOSEARCANE" || $turn[0] == "BUTTONINPUTNOPASS") && $turn[1] == $playerID) {
    $content = "<div display:inline;'>";
    if ($turn[0] == "CHOOSEARCANE") {
      $vars = explode("-", $dqVars[0]);
      $content .= "<div>Source: " . CardLink($vars[1], $vars[1]) . " Total Damage: " . $vars[0] . "</div>";
    }
    $options = explode(",", $turn[2]);
    for ($i = 0; $i < count($options); ++$i) {
      $content .= CreateButton($playerID, str_replace("_", " ", $options[$i]), 17, strval($options[$i]), "24px");
    }
    $content .= "</div>";
    echo CreatePopup("BUTTONINPUT", [], 0, 1, GetPhaseHelptext(), 1, $content);
  }

  if ($turn[0] == "YESNO" && $turn[1] == $playerID) {
    $content = CreateButton($playerID, "Yes", 20, "YES", "20px");
    $content .= CreateButton($playerID, "No", 20, "NO", "20px");
    if (GetDQHelpText() != "-") $caption = implode(" ", explode("_", GetDQHelpText()));
    else $caption = "Choose " . TypeToPlay($turn[0]);
    echo CreatePopup("YESNO", [], 0, 1, $caption, 1, $content);
  }

  if ($turn[0] == "OK" && $turn[1] == $playerID) {
    $content = CreateButton($playerID, "Ok", 99, "OK", "20px");
    if (GetDQHelpText() != "-") $caption = implode(" ", explode("_", GetDQHelpText()));
    else $caption = "Choose " . TypeToPlay($turn[0]);
    echo CreatePopup("OK", [], 0, 1, $caption, 1, $content);
  }

  if (($turn[0] == "OPT" || $turn[0] == "CHOOSETOP" || $turn[0] == "MAYCHOOSETOP" || $turn[0] == "CHOOSEBOTTOM" || $turn[0] == "CHOOSECARD" || $turn[0] == "MAYCHOOSECARD") && $turn[1] == $playerID) {
    $content = "<table><tr>";
    $options = explode(",", $turn[2]);
    for ($i = 0; $i < count($options); ++$i) {
      $content .= "<td>";
      $content .= "<table><tr><td>";
      $content .= Card($options[$i], "concat", $cardSize, 0, 1);
      $content .= "</td></tr><tr><td>";
      if ($turn[0] == "CHOOSETOP"  || $turn[0] == "MAYCHOOSETOP" || $turn[0] == "OPT") $content .= CreateButton($playerID, "Top", 8, $options[$i], "20px");
      if ($turn[0] == "CHOOSEBOTTOM" || $turn[0] == "OPT") $content .= CreateButton($playerID, "Bottom", 9, $options[$i], "20px");
      if ($turn[0] == "CHOOSECARD" || $turn[0] == "MAYCHOOSECARD") $content .= CreateButton($playerID, "Choose", 23, $options[$i], "20px");
      $content .= "</td></tr>";
      $content .= "</table>";
      $content .= "</td>";
    }
    $content .= "</tr></table>";
    echo CreatePopup("OPT", [], 0, 1, GetPhaseHelptext(), 1, $content);
  }

  if (($turn[0] == "CHOOSETOPOPPONENT") && $turn[1] == $playerID) { //Use when you have to reorder the top of your opponent library e.g. Righteous Cleansing
    $otherPlayer = ($playerID == 1 ? 2 : 1);
    $content = "<table><tr>";
    $options = explode(",", $turn[2]);
    for ($i = 0; $i < count($options); ++$i) {
      $content .= "<td>";
      $content .= "<table><tr><td>";
      $content .= Card($options[$i], "concat", $cardSize, 0, 1);
      $content .= "</td></tr><tr><td>";
      if ($turn[0] == "CHOOSETOPOPPONENT") $content .= CreateButton($otherPlayer, "Top", 29, $options[$i], "20px");
      $content .= "</td></tr>";
      $content .= "</table>";
      $content .= "</td>";
    }
    $content .= "</tr></table>";
    echo CreatePopup("CHOOSETOPOPPONENT", [], 0, 1, "Choose " . TypeToPlay($turn[0]), 1, $content);
  }

  if ($turn[0] == "HANDTOPBOTTOM" && $turn[1] == $playerID) {
    $content = "<table><tr>";
    for ($i = 0; $i < count($myHand); ++$i) {
      $content .= "<td>";
      $content .= Card($myHand[$i], "concat", $cardSize, 0, 1);
      $content .= "</td>";
    }
    $content .= "</tr><tr>";
    for ($i = 0; $i < count($myHand); ++$i) {
      $content .= "<td><span>";
      $content .= CreateButton($playerID, "Top", 12, $i, "20px");
      $content .= "</span><span>";
      $content .= CreateButton($playerID, "Bottom", 13, $i, "20px");
      $content .= "</span>";
      $content .= "</td>";
    }
    $content .= "</tr></table>";
    echo CreatePopup("HANDTOPBOTTOM", [], 0, 1, "Choose " . TypeToPlay($turn[0]), 1, $content);
  }

  $mzChooseFromPlay = false;
  $optionsIndex = [];
  if(($turn[0] == "MAYCHOOSEMULTIZONE" || $turn[0] == "CHOOSEMULTIZONE") && $turn[1] == $playerID) {
    $content = "<div display:inline;'>";
    $options = explode(",", $turn[2]);
    $mzChooseFromPlay = true;
    $otherPlayer = $playerID == 2 ? 1 : 2;
    $theirAllies = &GetAllies($otherPlayer);
    $myAllies = &GetAllies($playerID);
    for ($i = 0; $i < count($options); ++$i) {
      $optionsIndex[] = $options[$i];
      $option = explode("-", $options[$i]);
      if ($option[0] == "MYCHAR") $source = $myCharacter;
      else if ($option[0] == "THEIRCHAR") $source = $theirCharacter;
      else if ($option[0] == "LAYER") $source = $layers;
      else if ($option[0] == "MYHAND") $source = $myHand;
      else if ($option[0] == "THEIRHAND") $source = $theirHand;
      else if ($option[0] == "MYDISCARD") $source = $myDiscard;
      else if ($option[0] == "THEIRDISCARD") $source = $theirDiscard;
      else if ($option[0] == "MYBANISH") $source = $myBanish;
      else if ($option[0] == "THEIRBANISH") $source = $theirBanish;
      else if ($option[0] == "MYALLY") $source = $myAllies;
      else if ($option[0] == "THEIRALLY") $source = $theirAllies;
      else if ($option[0] == "MYARS") $source = $myArsenal;
      else if ($option[0] == "THEIRARS") $source = $theirArsenal;
      else if ($option[0] == "MYPITCH") $source = $myPitch;
      else if ($option[0] == "THEIRPITCH") $source = $theirPitch;
      else if ($option[0] == "MYDECK") $source = $myDeck;
      else if ($option[0] == "THEIRDECK") $source = $theirDeck;
      else if ($option[0] == "MYMATERIAL") $source = $myMaterial;
      else if ($option[0] == "THEIRMATERIAL") $source = $theirMaterial;
      else if ($option[0] == "MYRESOURCES") $source = &GetMemory($playerID);
      else if ($option[0] == "THEIRRESOURCES") $source = &GetMemory($playerID == 1 ? 2 : 1);
      else if ($option[0] == "LANDMARK") $source = $landmarks;
      else if ($option[0] == "CC") $source = $combatChain;
      else if ($option[0] == "COMBATCHAINLINK") $source = $combatChain;

      if($option[0] != "MYCHAR" && $option[0] != "THEIRCHAR" && $option[0] != "MYALLY" && $option[0] != "THEIRALLY" && $option[0] != "MYHAND") $mzChooseFromPlay = false;

      $counters = 0;
      $lifeCounters = 0;
      $enduranceCounters = 0;
      $atkCounters = 0;

      $index = intval($option[1]);
      $card = $source[$index];
      if ($option[0] == "LAYER" && (IsAbilityLayer($card))) $card = $source[$index + 2];
      $playerBorderColor = 0;

      if (str_starts_with($option[0], "MY")) $playerBorderColor = 1;
      else if (str_starts_with($option[0], "THEIR")) $playerBorderColor = 2;
      else if ($option[0] == "CC") $playerBorderColor = ($combatChain[$index + 1] == $playerID ? 1 : 2);
      else if ($option[0] == "LAYER") {
        $playerBorderColor = ($layers[$index + 1] == $playerID ? 1 : 2);
      }

      if ($option[0] == "THEIRARS" && $theirArsenal[$index + 1] == "DOWN") $card = $TheirCardBack;

      $overlay = 0;
      $attackCounters = -1;
      //NRA TODO
      //Show attack and hp counters on allies in the popups
      if ($option[0] == "THEIRALLY") {
        $ally = new Ally("MYALLY-" . $index, $otherPlayer);
        $lifeCounters = $ally->Health();
        $enduranceCounters = $theirAllies[$index + 6];
        $attackCounters = $ally->CurrentPower();
        if($ally->IsExhausted()) $overlay = 1;
      } elseif ($option[0] == "MYALLY") {
        $ally = new Ally("MYALLY-" . $index, $playerID);
        $lifeCounters = $ally->Health();
        $enduranceCounters = $myAllies[$index + 6];
        $attackCounters = $ally->CurrentPower();
        if($ally->IsExhausted()) $overlay = 1;
      } elseif($option[0] == "MYRESOURCES") {
        if($myArsenal[$index + 4] == 1) $overlay = 1;
      }
      $content .= Card($card, "concat", $cardSize, 16, 1, $overlay, $playerBorderColor, $counters, $options[$i], "", false, $lifeCounters, $enduranceCounters, $attackCounters, controller: $playerBorderColor);
    }
    $content .= "</div>";
    if(!$mzChooseFromPlay) echo CreatePopup("CHOOSEMULTIZONE", [], 0, 1, GetPhaseHelptext(), 1, $content);
  }

  if (($turn[0] == "MAYCHOOSEDECK" || $turn[0] == "CHOOSEDECK") && $turn[1] == $playerID) {
    ChoosePopup($myDeck, $turn[2], 11, "Choose a card from your deck");
  }

  if ($turn[0] == "CHOOSEBANISH" && $turn[1] == $playerID) {
    ChoosePopup($myBanish, $turn[2], 16, "Choose a card from your banish", BanishPieces());
  }

  if (($turn[0] == "CHOOSETHEIRHAND") && $turn[1] == $playerID) {
    ChoosePopup($theirHand, $turn[2], 16, "Choose a card from your opponent's hand");
  }

  if (($turn[0] == "CHOOSEDISCARD" || $turn[0] == "MAYCHOOSEDISCARD" || $turn[0] == "CHOOSEDISCARDCANCEL") && $turn[1] == $playerID) {
    $caption = "Choose a card from your discard";
    if (GetDQHelpText() != "-") $caption = implode(" ", explode("_", GetDQHelpText()));
    ChoosePopup($myDiscard, $turn[2], 16, $caption, zoneSize:DiscardPieces());
  }

  if (($turn[0] == "MAYCHOOSETHEIRDISCARD") && $turn[1] == $playerID) {
    ChoosePopup($theirDiscard, $turn[2], 16, "Choose a card from your opponent's graveyard", zoneSize:DiscardPieces());
  }

  if (($turn[0] == "CHOOSECOMBATCHAIN" || $turn[0] == "MAYCHOOSECOMBATCHAIN") && $turn[1] == $playerID) {
    ChoosePopup($combatChain, $turn[2], 16, "Choose a card from the combat chain", CombatChainPieces());
  }

  if ($turn[0] == "CHOOSECHARACTER" && $turn[1] == $playerID) {
    ChoosePopup($myCharacter, $turn[2], 16, "Choose a card from your character/equipment", CharacterPieces());
  }

  if ($turn[0] == "CHOOSETHEIRCHARACTER" && $turn[1] == $playerID) {
    ChoosePopup($theirCharacter, $turn[2], 16, "Choose a card from your opponent character/equipment", CharacterPieces());
  }

  if (($turn[0] == "MULTICHOOSETHEIRDISCARD" || $turn[0] == "MULTICHOOSEDISCARD" || $turn[0] == "MULTICHOOSEHAND" || $turn[0] == "MAYMULTICHOOSEHAND" || $turn[0] == "MULTICHOOSEUNIT" || $turn[0] == "MULTICHOOSETHEIRUNIT" || $turn[0] == "MULTICHOOSEDECK" || $turn[0] == "MULTICHOOSETEXT" || $turn[0] == "MAYMULTICHOOSETEXT" || $turn[0] == "MULTICHOOSETHEIRDECK" || $turn[0] == "MAYMULTICHOOSEAURAS") && $currentPlayer == $playerID) {
    $content = "";
    $multiAllies = &GetAllies($playerID);
    echo ("<div 'display:inline; width: 100%;'>");
    $params = explode("-", $turn[2]);
    $options = explode(",", $params[1]);
    $caption = "<div>Choose up to " . $params[0] . " card" . ($params[0] > 1 ? "s." : ".") . "</div>";
    if (GetDQHelpText() != "-") $caption = "<div>" . implode(" ", explode("_", GetDQHelpText())) . "</div>";
    $content .= CreateForm($playerID, "Submit", 19, count($options));
    $content .= "<table class='table-border-a'><tr>";
    for ($i = 0; $i < count($options); ++$i) {
      $content .= "<td>";
      $content .= CreateCheckbox($i, strval($i));
      $content .= "</td>";
    }
    $content .= "</tr><tr>";
    for ($i = 0; $i < count($options); ++$i) {
      $content .= "<td>";
      $content .= "<div class='container'>";
      if ($turn[0] == "MULTICHOOSEDISCARD") $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($myDiscard[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
      else if ($turn[0] == "MULTICHOOSETHEIRDISCARD") $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($theirDiscard[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
      else if ($turn[0] == "MULTICHOOSEHAND" || $turn[0] == "MAYMULTICHOOSEHAND") $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($myHand[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
      else if ($turn[0] == "MULTICHOOSEUNIT") $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($multiAllies[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
      else if ($turn[0] == "MULTICHOOSETHEIRUNIT") {
        $multiTheirAllies = &GetAllies($playerID == 1 ? 2 : 1);
        $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($multiTheirAllies[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
      }
      else if ($turn[0] == "MULTICHOOSEDECK") $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($myDeck[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
      else if ($turn[0] == "MULTICHOOSETHEIRDECK") $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($theirDeck[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
      else if ($turn[0] == "MULTICHOOSETEXT" || $turn[0] == "MAYMULTICHOOSETEXT") $content .= implode(" ", explode("_", strval($options[$i])));
      $content .= "<div class='overlay'><div class='text'>Select</div></div></div>";
      $content .= "</td>";
    }
    $content .= "</tr></table></form></div>";
    echo CreatePopup("MULTICHOOSE", [], 0, 1, $caption, 1, $content);
  }

  if($turn[0] == "INPUTCARDNAME" && $turn[1] == $playerID)
  {
    $caption = "<div>Enter a card name or ID</div>";
    $content = CreateTextForm($playerID, "Submit", 30);
    echo CreatePopup("INPUTCARDNAME", [], 0, 1, $caption, 1, $content);
  }

  //Opponent hand
  $handContents = "";
  $chatboxWidth = "238px";
  echo ("<div class='their-hand-wrapper' style='width: calc(100% - $chatboxWidth);'>");
  echo ("<div id='theirHand'>");
  for ($i = 0; $i < count($theirHand); ++$i) {
    if ($handContents != "") $handContents .= "|";
    if ($playerID == 3 && IsCasterMode()) $handContents .= ClientRenderedCard(cardNumber: $theirHand[$i], controller: ($playerID == 1 ? 2 : 1));
    else $handContents .= ClientRenderedCard(cardNumber: $TheirCardBack, controller: ($playerID == 1 ? 2 : 1));
  }
  echo ($handContents);
  $banishUI = TheirBanishUIMinimal("HAND");
  if ($handContents != "" && $banishUI != "") echo ("|");
  echo ($banishUI);
  echo ("</div>");
  echo ("</div>");

  //Show deck, discard, pitch, banish
  //Display Their Discard
  if (count($theirDiscard) > 0) {
    echo ("<div class= 'their-discard' title='Click to view the cards in your opponent's Graveyard.' onclick='TogglePopup(\"theirDiscardPopup\");'>");
    echo (Card($theirDiscard[count($theirDiscard) - DiscardPieces()], "concat", $cardSizeAura, 0, 0, 0, 0, count($theirDiscard)/DiscardPieces(), controller: $otherPlayer));
  } else {
    //Empty Discard div
    echo ("<div class= 'their-discard' style='padding:" . $cardSizeAura / 2 . "px;'>");
    echo ("<div class= 'their-discard-empty' style='color: " . $bordelessFontColor . ";'>Discard</div>");
  }
  echo ("</div>");

  //Display Their Deck
  if (count($theirDeck) > 0) {
    echo ("<div class= 'their-deck'>");
    echo (Card($TheirCardBack, "concat", $cardSizeAura, 0, 0, 0, 0, count($theirDeck)));
  } else {
    //Empty Deck div
    echo ("<div class= 'their-deck empty their-deck-empty-pos' style='right:" . GetZoneRight("DECK") . "; top:" . GetZoneTop("THEIRDECK") . "; padding:" . $cardSizeAura / 2 . "px;'>");
    echo ("<div class= 'their-deck-empty' style='color: " . $bordelessFontColor . ";'>Deck</div>");
  }
  echo (($manualMode ? "<span class= 'their-deck-manual'>" . CreateButton($playerID, "Draw", 10010, 0, "20px") . "</span>" : ""));
  echo ("</div>");
  echo ("</div>");
  echo ("</div>");
  if ($playerID == 3) {
    $otherPlayer = $playerID == 2 ? 2 : 1;
  } else {
    $otherPlayer = $playerID == 2 ? 1 : 2;
  }
  $theirAllies = GetAllies($otherPlayer);
  $spaceAllies = "";
  $groundAllies = "";
  if (count($theirAllies) > 0) {
    for ($i = 0; $i+AllyPieces()-1 < count($theirAllies); $i += AllyPieces()) {
      $mzIndex = "THEIRALLY-" . $i;
      $inOptions = in_array($mzIndex, $optionsIndex);
      $action = $mzChooseFromPlay && $inOptions ? 16 : 0;
      $actionDataOverride = $mzChooseFromPlay && $inOptions ? $mzIndex : 0;
      $border = CardBorderColor($theirAllies[$i], "ALLY", $action == 16, "THEIRS");

      $ally = new Ally($mzIndex, $otherPlayer);
      $opts = array(
        'action' => $action,
        'actionOverride' => $actionDataOverride,
        'border' => $border,
        'currentHP' => $ally->Health(),
        'maxHP' => $ally->MaxHealth(),
        'enduranceCounters' => $theirAllies[$i + 6],
        'subcard' => $theirAllies[$i + 4],
        'subcards' => $theirAllies[$i + 4] != "-" ? explode(",", $theirAllies[$i + 4]) : [],
        'currentPower' => $ally->CurrentPower(),
        'hasSentinel' => HasSentinel($theirAllies[$i], $otherPlayer, $i),
        'overlay' => $theirAllies[$i + 1] != 2 ? 1 : 0
      );
      $cardArena = CardArenas($theirAllies[$i]);
      //Their Unit Spacing
      if($cardArena == "Ground") $cardText = '<div id="unique-' . $theirAllies[$i+5] . '" class="cardContainer ' . ($theirAllies[$i + 1] != 2 ? 'exhausted' : '') . '">';
      else $cardText = '<div id="unique-' . $theirAllies[$i+5] . '" class="cardContainer ' . ($theirAllies[$i + 1] != 2 ? 'exhausted' : '') . '">';
      //card render their units
      $cardText .= (Card($theirAllies[$i], "concat", $cardSizeAura, $opts));
      $cardText .= ("</div>");
      if($cardArena == "Ground") $groundAllies .= $cardText;
      else $spaceAllies .= $cardText;
    }
  }
  //Now display their Leader and Base
  $numWeapons = 0;
  echo ("<div id='theirChar'>");
  $characterContents = "";
  for ($i = 0; $i < count($theirCharacter); $i += CharacterPieces()) {
    if ($i > 0 && $inGameStatus == "0") continue;
    $mzIndex = "THEIRCHAR-" . $i;
    $inOptions = in_array($mzIndex, $optionsIndex);
    $action = $mzChooseFromPlay && $inOptions ? 16 : 0;
    $actionDataOverride = $mzChooseFromPlay && $inOptions ? $mzIndex : 0;
    $border = CardBorderColor($theirCharacter[$i], "CHAR", $action == 16, "THEIRS");
    $atkCounters = 0;
    $counters = 0;
    $epicActionUsed = 0;
    $overlay = $theirCharacter[$i + 1] != 2 ? 1 : 0;
    $type = CardType($theirCharacter[$i]);
    $sType = CardSubType($theirCharacter[$i]);
    if ($type == "W") { //Base
      $epicActionUsed = $theirCharacter[$i + 1] == 0 ? 1 : 0;
      $overlay = 0;
    } else if ($type == "C") {
      $epicActionUsed = $theirCharacter[$i + 2] > 0 ? 1 : 0;
    }
    if ($characterContents != "") $characterContents .= "|";
    $characterContents .= ClientRenderedCard(cardNumber: $theirCharacter[$i], action:$action, actionDataOverride:$actionDataOverride, borderColor:$border, overlay: $overlay, counters: $counters, defCounters: 0, atkCounters: $atkCounters, controller: $otherPlayer, type: $type, sType: $sType, isFrozen: ($theirCharacter[$i + 8] == 1), onChain: ($theirCharacter[$i + 6] == 1), isBroken: ($theirCharacter[$i + 1] == 0), rotate:0, landscape:1, epicActionUsed: $epicActionUsed);
  }
  echo ($characterContents);

  echo ("</div>");

  //Their Space Allies
  echo ("<div class='spaceEnemiesContainer' style='max-height:" . $permHeight . "px;'>");
  echo ($spaceAllies);
  echo ("</div>");

  //Their Ground Allies
  echo ("<div class='groundEnemiesContainer' style='max-height:" . $permHeight . "px;'>");
  echo ($groundAllies);
  echo ("</div>");

  //Now display their resources
  $arsenalLeft = "calc(50% - " . (count($theirArsenal)/ArsenalPieces()/2 * intval($cardWidth) + 14) . "px)";
  $numReady = 0;
  $total = 0;
  for ($i = 0; $i < count($theirArsenal); $i += ArsenalPieces()) {
    ++$total;
    if($theirArsenal[$i + 4] != 1) ++$numReady;
  }

  echo ("<div class='their-resources'>");
  echo ("<div class='resources' title='Opponent resources'><img src='./Images/Resource.png' /><span>" . $numReady . "/" . $total . "</span></div>");
  echo ("</div>");

  echo ("</div>");
  echo ("</div>");

  $restriction = "";
  $actionType = $turn[0] == "ARS" ? 4 : 27;
  if (str_contains($turn[0], "CHOOSEHAND") && ($turn[0] != "" || $turn[0] != "MAYMULTICHOOSEHAND")) $actionType = 16;
  $handLeft = "calc(50% - " . ((count($myHand) * ($cardWidth + 15)) / 2) . "px - 119px)";
  echo ("<div id='myHand' style='left:" . $handLeft . ";'>"); //Hand div
  $handContents = "";
  for ($i = 0; $i < count($myHand); ++$i) {
    if ($handContents != "") $handContents .= "|";
    if ($playerID == 3) {
      if (IsCasterMode()) $handContents .= ClientRenderedCard(cardNumber: $myHand[$i], controller: 2);
      else $handContents .= ClientRenderedCard(cardNumber: $MyCardBack, controller: 2);
    } else {
      if($mzChooseFromPlay) {
        $mzIndex = "MYHAND-" . $i;
        $inOptions = in_array($mzIndex, $optionsIndex);
        $actionTypeOut = $inOptions ? 16 : 0;
        $actionDataOverride = $inOptions ? $mzIndex : 0;
        $border = CardBorderColor($myHand[$i], "HAND", $actionTypeOut == 16);
      } else {
        if ($playerID == $currentPlayer) $playable = $turn[0] == "ARS" || ($actionType == 16 && str_contains("," . $turn[2] . ",", "," . $i . ",")) || ($turn[0] == "M" || $turn[0] == "INSTANT") && IsPlayable($myHand[$i], $turn[0], "HAND", -1, $restriction);
        else $playable = false;
        $border = CardBorderColor($myHand[$i], "HAND", $playable);
        $actionTypeOut = (($currentPlayer == $playerID) && $playable == 1 ? $actionType : 0);
        if ($restriction != "") $restriction = implode("_", explode(" ", $restriction));
        $actionDataOverride = (($actionType == 16 || $actionType == 27) ? strval($i) : "");
      }
      $handContents .= ClientRenderedCard(cardNumber: $myHand[$i], action: $actionTypeOut, borderColor: $border, actionDataOverride: $actionDataOverride, controller: $playerID, restriction: $restriction);
    }
  }
  echo ($handContents);
  $banishUI = BanishUIMinimal("HAND");
  if ($handContents != "" && $banishUI != "") echo ("|");
  echo ($banishUI);
  echo ("</div>"); //End hand div

  $myAllies = GetAllies($playerID);
  $spaceAllies = "";
  $groundAllies = "";
  if (count($myAllies) > 0) {
    for ($i = 0; $i < count($myAllies); $i += AllyPieces()) {
      if($i > count($myAllies) - AllyPieces()) break;
      $ally = new Ally("MYALLY-" . $i, $playerID);

      if($mzChooseFromPlay) {
        $mzIndex = "MYALLY-" . $i;
        $inOptions = in_array($mzIndex, $optionsIndex);
        $action = $inOptions ? 16 : 0;
        $actionDataOverride = $inOptions ? $mzIndex : 0;
        $border = CardBorderColor($myAllies[$i], "PLAY", $action == 16);
      } else {
        $playable = IsPlayable($myAllies[$i], $turn[0], "PLAY", $i, $restriction) && ($myAllies[$i + 1] == 2 || AllyPlayableExhausted($myAllies[$i]));
        $border = CardBorderColor($myAllies[$i], "PLAY", $playable);
        $action = $currentPlayer == $playerID && $turn[0] != "P" && $playable ? 24 : 0;
        $actionDataOverride = strval($i);
      }
      
      $opts = array(
        'currentHP' => $ally->Health(),
        'maxHP' => $ally->MaxHealth(),
        'enduranceCounters' => $myAllies[$i + 6],
        'subcard' => $myAllies[$i + 4],
        'subcards' => $myAllies[$i + 4] != "-" ? explode(",", $myAllies[$i + 4]) : [],
        'currentPower' => $ally->CurrentPower(),
        'hasSentinel' => HasSentinel($myAllies[$i], $playerID, $i),
        'action' => $action,
        'actionOverride' => $actionDataOverride,
        'border' => $border,
        'overlay' => $myAllies[$i + 1] != 2 ? 1 : 0
      );
      $cardArena = CardArenas($myAllies[$i]);
      //My Unit Spacing
      if($cardArena == "Ground") $cardText = '<div id="unique-' . $myAllies[$i+5] . '" class="cardContainer ' . ($myAllies[$i + 1] != 2 ? 'exhausted' : '') . '">';
      else $cardText = '<div id="unique-' . $myAllies[$i+5] . '" class="cardContainer ' . ($myAllies[$i + 1] != 2 ? 'exhausted' : '') . '">';
      if($manualMode) {
        $cardText .= "<div class='my-units-manual'>";
        $cardText .= CreateButton($playerID, "+", 10012, $i, "20px");
        $cardText .= CreateButton($playerID, "-", 10013, $i, "20px");
        $cardText .= "</div>";
      }
      $cardText .= (Card($myAllies[$i], "concat", $cardSizeAura, $opts));
      $cardText .= ("</div>");
      if($cardArena == "Ground") $groundAllies .= $cardText;
      else $spaceAllies .= $cardText;
    }
  }

  //Space allies
  echo ("<div class='spaceAlliesContainer' style='max-height:" . $permHeight . "px;'>");
  echo ($spaceAllies);
  echo ("</div>");

  //Ground allies
  echo ("<div class='groundAlliesContainer' style='max-height:" . $permHeight . "px;'>");
  echo ($groundAllies);
  echo ("</div>");

  //Now display my Leader and Base
  $numWeapons = 0;
  $myCharData = "";
  for ($i = 0; $i < count($myCharacter); $i += CharacterPieces()) {
    $restriction = "";
    $counters = 0;
    $atkCounters = 0;
    $epicActionUsed = 0;
    $overlay = $myCharacter[$i + 1] != 2 ? 1 : 0;
    $type = CardType($myCharacter[$i]);
    $sType = CardSubType($myCharacter[$i]);
    if ($type == "W") { //Base
      $epicActionUsed = $myCharacter[$i + 1] == 0 ? 1 : 0;
      $overlay = 0;
    } else if ($type == "C") { // Leader
      $epicActionUsed = $myCharacter[$i + 2] > 0 ? 1 : 0;
    }

    if($mzChooseFromPlay) {
      $mzIndex = "MYCHAR-" . $i;
      $inOptions = in_array($mzIndex, $optionsIndex);
      $action = $inOptions ? 16 : 0;
      $actionDataOverride = $inOptions ? $mzIndex : 0;
      $border = CardBorderColor($myCharacter[$i], "CHAR", $action == 16);
    } else {
      $playable = $playerID == $currentPlayer && IsPlayable($myCharacter[$i], $turn[0], "CHAR", $i, $restriction) && ($myCharacter[$i + 1] == 2 || $epicActionUsed == 0);
      $border = CardBorderColor($myCharacter[$i], "CHAR", $playable);
      $action = $currentPlayer == $playerID && $playable ? 3 : 0;
      $actionDataOverride = strval($i);
    }

    if ($myCharData != "") $myCharData .= "|";
    $restriction = implode("_", explode(" ", $restriction));
    $myCharData .= ClientRenderedCard($myCharacter[$i], $action, $myCharacter[$i + 1] != 2 ? 1 : 0, $border, $myCharacter[$i + 1] != 0 ? $counters : 0, $actionDataOverride, 0, 0, $atkCounters, $playerID, $type, $sType, $restriction, $myCharacter[$i + 1] == 0, $myCharacter[$i + 6] == 1, $myCharacter[$i + 8] == 1, gem:0, rotate:0, landscape:1, epicActionUsed:$epicActionUsed);
  }
  echo ("<div id='myChar' style='display:none;'>");
  echo ($myCharData);
  echo ("</div>");


  //Display my resources
  $numReady = 0;
  $total = 0;
  $arsenalLeft = "calc(50% - " . (count($myArsenal)/ArsenalPieces()/2 * intval($cardWidth) + 14) . "px)";
  echo ("<div style='position:fixed; left:" . $arsenalLeft . "; bottom:" . (intval(GetCharacterBottom("C", "")) - $cardSize + 15) . "px;'>"); //arsenal div
  for ($i = 0; $i < count($myArsenal); $i += ArsenalPieces()) {
    ++$total;
    if($myArsenal[$i + 4] != 1) ++$numReady;
  }
  echo ("<div class='resource-wrapper my-resources'>");
  echo ("<div class='resources' title='Click to see your resources.' onclick='TogglePopup(\"myResourcePopup\");'><img src='./Images/Resource.png' /><span>" . $numReady . "/" . $total . "</span></div>");
  echo ("</div>");
  echo ("</div>"); //End resource div

  //Show deck, discard
  //Display My Discard
  if (count($myDiscard) > 0) {
    echo ("<div class='my-discard my-discard-fill' title='Click to view the cards in your Graveyard.' onclick='TogglePopup(\"myDiscardPopup\");'>");
    echo (Card($myDiscard[count($myDiscard) - DiscardPieces()], "concat", $cardSizeAura, 0, 0, 0, 0, count($myDiscard)/DiscardPieces(), controller: $playerID));
  } else {
    //Empty Discard div
    echo ("<div class='my-discard my-discard-empty' style='padding:" . $cardSizeAura / 2 . "px;'>");
    echo ("<div class='my-discard-empty-label' style='color: " . $bordelessFontColor . ";'>Discard</div>");
  }
  echo ("</div>");

  //Display My Deck
  if (count($myDeck) > 0) {
    $playerDeck = new Deck($playerID);
    if ($turn[0] == "OVER") echo ("<div class= 'my-deck my-deck-fill' title='Click to view the cards in your Deck.' style='" . GetZoneRight("DECK") . "; bottom:" . GetZoneBottom("MYDECK") . "' onclick='TogglePopup(\"myDeckPopup\");'>");
    else echo ("<div class='my-deck'>");
    echo (Card($MyCardBack, "concat", $cardSizeAura, 0, 0, 0, 0, $playerDeck->RemainingCards()));
  } else {
    //Empty Deck div
    echo ("<div class= 'my-deck my-deck-empty' style='padding:" . $cardSizeAura / 2 . "px;'>");
    echo ("<div class= 'my-deck-empty-label' style='color: " . $bordelessFontColor . ";'>Deck</div>");
  }
  echo (($manualMode ? "<span class='my-deck-manual'>" . CreateButton($playerID, "Draw", 10009, 0, "20px") . "</span>" : ""));
  echo ("</div>");
  echo ("</div>");


  echo ("</div>");
  //End play area div

  //Display the log
  echo ("<div id='sidebarWrapper'>");

  echo ("<div class='menu-buttons-wrapper-a'><div class='menu-buttons-wrapper-b'><table><tr>");
  if (IsPatron($playerID)) {
    echo ("<td><div class='MenuButtons' title='Click to view stats.' onclick='TogglePopup(\"myStatsPopup\");'><img class='stats-icon' src='./Images/stats.png' /></div></td>");
    echo ("<td></td><td>");
    echo ("<div class='MenuButtons' title='Click to view the menu. (Hotkey: M)' onclick='TogglePopup(\"menuPopup\");'><img class='menu-icon' src='./Images/menuicon.png' /></div>");
  } else {
    echo ("<td><div class='MenuButtons' title='Click to view the menu. (Hotkey: M)' onclick='TogglePopup(\"menuPopup\");'><img class='settings-icon' src='./Images/cog.png' /></div>");
    echo ("<td><div class='MenuButtons' title='Click to view the menu. (Hotkey: M)' onclick='TogglePopup(\"leaveGame\");'><img class='exit-icon' src='./Images/close.png' /></div>");
  }
  echo ("</td></tr></table></div></div>");

  //Turn title
  echo ("<div class='round-title'>Round " . $currentRound . "</div>");
  echo ("<div class='last-played-title'>Last Played</div>");
  echo ("<div class='last-played-card'>");
  if (count($lastPlayed) == 0) echo Card($MyCardBack, "CardImages", intval($rightSideWidth * 1.3));
  else {
    echo Card($lastPlayed[0], "CardImages", intval($rightSideWidth * 1.3), controller: $lastPlayed[1]);
    if (count($lastPlayed) >= 4) {
      if ($lastPlayed[3] == "FUSED") echo ("<img class='fused-card' title='This card was fused.' src='./Images/fuse2.png' />");
    }
  }
  echo ("</div>");

  echo ("<div id='gamelog'>");
  EchoLog($gameName, $playerID);
  echo ("</div>");
  if ($playerID != 3) {
    echo ("<div id='chatPlaceholder'></div>");
    echo ("</div>");
  }

  echo ("<div id='lastCurrentPlayer' style='display:none;'>" . $currentPlayer . "</div>");
  echo ("<div id='passConfirm' style='display:none;'>" . ($turn[0] == "ARS" && count($myHand) > 0 && !ArsenalFull($playerID) ? "true" : "false") . "</div>");
}

function PlayableCardBorderColor($cardID)
{
  if (HasReprise($cardID) && RepriseActive()) return 3;
  return 0;
}

function ItemOverlay($item, $isReady, $numUses)
{
  if($item == "EVR070" && $numUses < 3) return 1;
  return ($isReady != 2 ? 1 : 0);
}

function ChoosePopup($zone, $options, $mode, $caption = "", $zoneSize = 1)
{
  global $cardSize;
  $content = "";
  $options = explode(",", $options);

  $content .= "<table class='choosepopup-table'><tr>";
  for ($i = 0; $i < count($options); ++$i) {
    $content .= "<td class='choosepopup-table-td'>";
    $content .= "<div class='container'>";
    $content .= "<label class='multichoose'>" . Card($zone[$options[$i]], "concat", $cardSize, $mode, 1, 0, 0, 0, strval($options[$i])) . "</label>";
    $content .= "<div class='overlay'><div class='text'>Select</div></div></div></td>";
  }
  $content .= "</tr></table>";
  echo CreatePopup("CHOOSEZONE", [], 0, 1, $caption, 1, $content);
}

function GetCharacterLeft($cardType, $cardSubType)
{
  global $cardWidth;
  switch ($cardType) {
    case "C": case "W":
      return "calc(50% - 172px)";
    default:
      break;
  }
  switch ($cardSubType) {
    case "Head":
      return "95px";
    case "Chest":
      return "95px";
    case "Arms":
      return ($cardWidth + 115) . "px";
    case "Legs":
      return "95px";
    case "Off-Hand":
      return "calc(50% + " . ($cardWidth / 2 + 15) . "px)";
  }
}

function GetCharacterBottom($cardType, $cardSubType)
{
  global $cardSize;
  switch ($cardType) {
    case "C":
      return ($cardSize * 2 - 25) . "px";
    case "W":
      return ($cardSize * 2 - 25) . "px";
    default:
      break;
  }
  switch ($cardSubType) {
    case "Head":
      return ($cardSize * 2 - 25) . "px";
    case "Chest":
      return ($cardSize - 10) . "px";
    case "Arms":
      return ($cardSize - 10) . "px";
    case "Legs":
      return "5px";
    case "Off-Hand":
      return ($cardSize * 2 - 25) . "px";
  }
}

function GetCharacterTop($cardType, $cardSubType)
{
  global $cardSize;
  switch ($cardType) {
    case "C":
      return "52px";
    case "W":
      return "52px";
    default:
      break;
  }
  switch ($cardSubType) {
    case "Head":
      return "5px";
    case "Chest":
      return ($cardSize - 10) . "px";
    case "Arms":
      return ($cardSize - 10) . "px";
    case "Legs":
      return ($cardSize * 2 - 25) . "px";
    case "Off-Hand":
      return "52px";
  }
}

function GetZoneRight($zone)
{
  global $cardWidth, $rightSideWidth;
  switch ($zone) {
    case "DISCARD":
      return intval($rightSideWidth * 1.05) . "px";
    case "DECK":
      return intval($rightSideWidth * 1.05) . "px";
    case "BANISH":
      return intval($rightSideWidth * 1.05) . "px";
    case "PITCH":
      return (intval($rightSideWidth * 1.14) + $cardWidth) . "px";
  }
}

function GetZoneBottom($zone)
{
  global $cardSize;
  switch ($zone) {
    case "MYBANISH":
      return ($cardSize * 2 - 25) . "px";
    case "MYDECK":
      return ($cardSize - 10) . "px";
    case "MYDISCARD":
      return (5) . "px";
    case "MYPITCH":
      return ($cardSize - 10);
  }
}

function GetZoneTop($zone)
{
  global $cardSize;
  switch ($zone) {
    case "THEIRBANISH":
      return ($cardSize * 2 - 25) . "px";
    case "THEIRDECK":
      return ($cardSize - 10) . "px";
    case "THEIRDISCARD":
      return (5) . "px";
    case "THEIRPITCH":
      return ($cardSize - 10);
  }
}

function IsTileable($cardID)
{
  switch ($cardID) {
    case "ENLIGHTEN":
      return true;
    default:
      return false;
  }
}

function DisplayTiles($player)
{
  global $cardSizeAura, $playerID, $turn;
  $auras = GetAuras($player);

  $count = 0;
  $first = -1;
  $playable = false;
  $actionIndex = -1;
  for($i = 0; $i < count($auras); $i += AuraPieces()) {
    if($auras[$i] == "ENLIGHTEN") {
      if ($count == 0) $first = $i;
      ++$count;

      if($player == $playerID && $first > -1) {
        $actionIndex = $i;
        $playable = IsPlayable($auras[$i], $turn[0], "PLAY", $i);
      }
    }
  }
  if ($count > 0) {
    $border = CardBorderColor("CRU197", "PLAY", $playable);
    echo ("<div class='tile-display'>");
    echo (Card("ENLIGHTEN", "concat", $cardSizeAura, $playable ? 22 : 0, 1, 0, $border, ($count > 1 ? $count : 0), strval($actionIndex)) . "&nbsp");
    DisplayPriorityGem(($player == $playerID ? $auras[$first + 7] : $auras[$first + 8]), "AURAS-" . $first, ($player != $playerID ? 1 : 0));
    echo ("</div>");
  }
}

function GetPhaseHelptext()
{
  global $turn;
  $defaultText = "Choose " . TypeToPlay($turn[0]);
  return (GetDQHelpText() != "-" ? implode(" ", explode("_", GetDQHelpText())) : $defaultText);
}

function DisplayPriorityGem($setting, $MZindex, $otherPlayer = 0)
{
  global $cardWidth, $playerID;
  if ($otherPlayer != 0) {
    $position = "top: 60px;";
  } else {
    $position = "bottom: 3px;";
  }
  if ($setting != 2 && $playerID != 3) {
    $gem = ($setting == 1 ? "hexagonRedGem.png" : "hexagonGrayGem.png");
    if ($setting == 0) echo ("<img " . ProcessInputLink($playerID, ($otherPlayer ? 104 : 103), $MZindex) . " title='Not holding priority' class='priority-gem' style='" . $position . " left:" . $cardWidth / 2 - 13 . "px;' src='./Images/$gem' />");
    else if ($setting == 1) echo ("<img " . ProcessInputLink($playerID, ($otherPlayer ? 104 : 103), $MZindex) . " title='Holding priority' class='priority-gem' style='" . $position . " left:" . $cardWidth / 2 - 13 . "px;' src='./Images/$gem' />");
  }
}
