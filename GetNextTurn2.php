<?php

include 'Libraries/HTTPLibraries.php';
include 'Libraries/NetworkingLibraries.php';

$stage = getenv('STAGE') ?: 'prod';
$isDev = $stage === 'dev';

$ReturnDelim = "GSDELIM";
$DisconnectFirstWarningMS = $isDev ? 1e9 : 30e3;
$DisconnectFinalWarningMS = $isDev ? 1e9 : 55e3;
$DisconnectTimeoutMS = $isDev ? 1e9 : 60e3;
$ServerTimeoutMS = $isDev ? 1e9 : 90e3;
$InputWarningMS = $isDev ? 1e9 : 60e3;
$InputTimeoutMS = $isDev ? 1e9 : 90e3;

//We should always have a player ID as a URL parameter
$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("NaN" . $ReturnDelim);
  exit;
}
$playerID = TryGet("playerID", 3);
if (!is_numeric($playerID)) {
  echo ("NaN" . $ReturnDelim);
  exit;
}

if (!file_exists("./Games/" . $gameName . "/")) {
  header('HTTP/1.0 403 Forbidden');
  exit;
}

if (isset($_COOKIE['selectedLanguage'])) {
  $selectedLanguage = $_COOKIE['selectedLanguage'];
}else {
  $selectedLanguage = 'EN';
}

$authKey = TryGet("authKey", 3);
$lastUpdate = intval(TryGet("lastUpdate", 0));
$windowWidth = intval(TryGet("windowWidth", 0));
$windowHeight = intval(TryGet("windowHeight", 0));
$lastCurrentPlayer = intval(TryGet("lastCurrentPlayer", 0));

if (($playerID == 1 || $playerID == 2) && $authKey == "") {
  if (isset($_COOKIE["lastAuthKey"]))
    $authKey = $_COOKIE["lastAuthKey"];
}

include "HostFiles/Redirector.php";
include "Libraries/SHMOPLibraries.php";
include_once "WriteLog.php";

SetHeaders();

if ($playerID == 3 && GetCachePiece($gameName, 9) != "1") {
  echo ($playerID . " " . $gameName . " " . GetCachePiece($gameName, 9));
  header('HTTP/1.0 403 Forbidden');
  exit;
}

$isGamePlayer = $playerID == 1 || $playerID == 2;
$opponentDisconnected = false;
$opponentInactive = false;
$currentPlayerInputTimeout = false;

$currentTime = round(microtime(true) * 1000);
if ($isGamePlayer) {
  $playerStatus = intval(GetCachePiece($gameName, $playerID + 3));
  if ($playerStatus == "-1") {
    SetCachePiece($gameName, $playerID + 14, 0);
    WriteLog("Player $playerID has connected.");
  }
  SetCachePiece($gameName, $playerID + 1, $currentTime);
  SetCachePiece($gameName, $playerID + 3, "0");
  if ($playerStatus > 0 || GetCachePiece($gameName, $playerID + 14) > 0) {
    if (GetCachePiece($gameName, 19) != $playerID) {
      WriteLog("Player $playerID has reconnected.");
      SetCachePiece($gameName, $playerID + 3, "0");
      SetCachePiece($gameName, $playerID + 14, 0);
      GamestateUpdated($gameName);
    }
  }
}
$count = 0;
$cacheVal = intval(GetCachePiece($gameName, 1));
while ($lastUpdate != 0 && $cacheVal <= $lastUpdate) {
  usleep(100_000); //100 milliseconds
  $currentTime = round(microtime(true) * 1000);
  $readCache = ReadCache($gameName);
  if ($readCache == "")
    break;
  $cacheArr = explode(SHMOPDelimiter(), $readCache);
  $cacheVal = intval($cacheArr[0]);
  if ($isGamePlayer) {
    SetCachePiece($gameName, $playerID + 1, $currentTime);
    $otherP = ($playerID == 1 ? 2 : 1);
    $oppLastTime = intval($cacheArr[$otherP]);
    $oppStatus = $cacheArr[$otherP + 2];
    $timeDiff = $currentTime - $oppLastTime;
    $otherPlayerDisconnectStatus = GetCachePiece($gameName, $otherP + 14);
    $gameState = intval($cacheArr[13]);
    $lastActionTime = intval($cacheArr[16]);
    $lastActionWarning = intval($cacheArr[17]);
    $finalWarning = intval($cacheArr[18]);
    if ($gameState == 6 && $timeDiff > 10_000 && $oppStatus == "0") {
      WriteLog("Player $otherP has disconnected.");
      $opponentDisconnected = true;
      SetCachePiece($gameName, $otherP + 3, "2");
      SetCachePiece($gameName, 14, 7);//$MGS_StatsLoggedIrreversible
      GamestateUpdated($gameName);
    } else {
      if ($gameState == 5 && $timeDiff > $DisconnectFirstWarningMS && $otherPlayerDisconnectStatus == 0 && ($oppStatus == "0")) {
        $warningSeconds = ($DisconnectTimeoutMS - $DisconnectFirstWarningMS) / 1000;
        WriteLog(ArenabotSpan() . "Player $otherP, are you still there? Your opponent will be allowed to claim victory in $warningSeconds seconds if no activity is detected.");
        IncrementCachePiece($gameName, $otherP + 14);
        GamestateUpdated($gameName);
      }
      if ($gameState == 5 && $timeDiff > $DisconnectFinalWarningMS && $otherPlayerDisconnectStatus == 1 && ($oppStatus == "0")) {
        $finalWarningSeconds = ($DisconnectTimeoutMS - $DisconnectFinalWarningMS) / 1000;
        WriteLog(ArenabotSpan() . "$finalWarningSeconds seconds left, Player $otherP...");
        IncrementCachePiece($gameName, $otherP + 14);
        GamestateUpdated($gameName);
      }
      if ($gameState == 5 && $timeDiff > $DisconnectTimeoutMS && $otherPlayerDisconnectStatus == 2 && ($oppStatus == "0")) {
        WriteLog("Player $otherP has disconnected.");
        $opponentDisconnected = true;
        SetCachePiece($gameName, $otherP + 3, "2");
        IncrementCachePiece($gameName, $otherP + 14);
        GamestateUpdated($gameName);
      }
      //Handle server timeout
      $lastUpdateTime = intval($cacheArr[5]);
      if ($currentTime - $lastUpdateTime > $ServerTimeoutMS && $cacheArr[11] != "1")//90 seconds
      {
        SetCachePiece($gameName, 12, "1");
        $opponentInactive = true;
        $lastUpdate = 0;
      }

      if ($gameState == 5 && $lastCurrentPlayer == $playerID && ($currentTime - $lastActionTime) > $InputWarningMS && $lastActionWarning === 0 && $finalWarning == 0) {
        $inputWarningSeconds = $InputWarningMS / 1000;
        $inputWarningSecondsLeft = ($InputTimeoutMS - $InputWarningMS) / 1000;
        WriteLog(ArenabotSpan() . "No input in over $inputWarningSeconds seconds; Player $playerID has $inputWarningSecondsLeft more seconds to take an action or the turn will be passed");
        SetCachePiece($gameName, 18, $playerID);
        GamestateUpdated($gameName);
      }

      if ($gameState == 5 && $lastCurrentPlayer == $playerID && ($currentTime - $lastActionTime) > $InputTimeoutMS && $lastActionWarning > 0) {
        $currentPlayerInputTimeout = true;
        $lastUpdate = 0;
      } else if ($gameState == 5 && $lastCurrentPlayer == $otherP && ($currentTime - $lastActionTime) > $InputTimeoutMS && $lastActionWarning == $otherP && $finalWarning == $otherP) {
        WriteLog("Player $otherP is inactive.");
        SetCachePiece($gameName, $otherP + 14, 3);
        GamestateUpdated($gameName);
      }
    }
  }
  ++$count;
  if ($count == 100)
    break;
}
$otherP = ($playerID == 1 ? 2 : 1);
$opponentDisconnected = GetCachePiece($gameName, $otherP + 3) == "2" || GetCachePiece($gameName, $otherP + 14) == "3";

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
  
  $isActivePlayer = $turn[1] == $playerID;

  if ($turn[0] == "REMATCH" && intval($playerID) != 3) {
    include "MenuFiles/ParseGamefile.php";
    include "MenuFiles/WriteGamefile.php";
    if ($gameStatus == $MGS_GameStarted) {
      include "AI/CombatDummy.php";
      $origDeck = "./Games/" . $gameName . "/p1DeckOrig.txt";
      if (file_exists($origDeck))
        copy($origDeck, "./Games/" . $gameName . "/p1Deck.txt");
      $origDeck = "./Games/" . $gameName . "/p2DeckOrig.txt";
      if (file_exists($origDeck))
        copy($origDeck, "./Games/" . $gameName . "/p2Deck.txt");
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
    echo ("999999" . $ReturnDelim);
    exit;
  }

  echo ($cacheVal . $ReturnDelim);
  echo (implode("~", $events) . $ReturnDelim);

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

  if ($lastCurrentPlayer == $playerID && $currentPlayerInputTimeout && !$opponentInactive) {
    if (GetCachePiece($gameName, 18) == $playerID && GetCachePiece($gameName, 19) != $playerID) {
      SetCachePiece($gameName, 17, $currentTime);
      SetCachePiece($gameName, 19, $playerID);
      PassInput();
      CacheCombatResult();
      DoGamestateUpdate();
      include "WriteGamestate.php";
      GamestateUpdated($gameName);
      ExitProcessInput();
      exit();
    }
  }

  echo ("<div id='iconHolder'>" . $icon . "</div>");

  if ($windowWidth / 16 > $windowHeight / 9)
    $windowWidth = $windowHeight / 9 * 16;

  $cardSize = ($windowWidth != 0 ? intval($windowWidth / 13) : 120);
  //$cardSize = ($windowWidth != 0 ? intval($windowWidth / 16) : 120);
  if (!IsDynamicScalingEnabled($playerID))
    $cardSize = 120; //Temporarily disable dynamic scaling
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

  if ($darkMode)
    $backgroundColor = "rgba(74, 74, 74, 0.9)";
  else
    $backgroundColor = "rgba(0, 0, 0, 0)";

  $blankZone = ($darkMode ? "blankZoneDark" : "blankZone");
  $borderColor = ($darkMode ? "#DDD" : "rgba(0, 0, 0, 0)");
  $fontColor = ($darkMode ? "#1a1a1a" : "white");
  $bordelessFontColor = "#DDD";

  //Choose Cardback
  $MyCardBack = GetCardBack($playerID);
  $TheirCardBack = GetCardBack($playerID == 1 ? 2 : 1);
  $gameBackground = GetBackground($playerID);
  [$gameBgSrc, $noDim] = GetGameBgSrc(BackgroundCode($gameBackground));
  $otherPlayer = ($playerID == 1 ? 2 : 1);

  //Display background
  echo ("<div class='container game-bg'><img src='./Images/$gameBgSrc'/></div>");
  if(!$noDim) echo ("<div class='game-bg-dimmer'>");
  echo ("</div>");

  //Base Damage Numbers
  echo ("<div class='base-dmg-wrapper'><div class='base-dmg-position'><span class='base-my-dmg'>$myHealth</span>");
  echo (($manualMode ? "<span class='base-my-dmg-manual'>" . CreateButton($playerID, "+1", 10006, 0, "20px") . CreateButton($playerID, "-1", 10005, 0, "20px") . "</span>" : ""));
  echo ("<span class='base-their-dmg'>$theirHealth</span>");
  //  echo (($manualMode ? "<span class='base-their-dmg-manual'>" . CreateButton($playerID, "+1", 10008, 0, "20px") . CreateButton($playerID, "-1", 10007, 0, "20px") . "</span>" : ""));
  echo ("</div></div>");
  echo ("<div class='base-their-dmg-manual'></div>");
  if ($turn[0] == "ARS" || (count($layers) > 0 && $layers[0] == "ENDTURN")) {
    $passLabel = "End Turn";
    $fontSize = 30;
    $left = 65;
    $top = 20;
  } else if (IsReplay()) {
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

  $initiativeSuffix = $initiativeTaken ? "-taken" : "";
  if ($initiativePlayer == $playerID || ($playerID == 3 && $initiativePlayer == 2)) {
    echo ("<div class='my-initiative$initiativeSuffix'><span>Initiative</span>");
  } else {
    echo ("<div class='their-initiative$initiativeSuffix'><span>Initiative</span>");
  }
  echo ("</div>");

  //Now display the screen for this turn
  echo ("<div class='display-game-screen'>");
  echo ("<div class='status-wrapper'>");

  echo (($manualMode ? "<span style='color: " . $fontColor . "; text-shadow: 2px 0 0 " . $borderColor . ", 0 -2px 0 " . $borderColor . ", 0 2px 0 " . $borderColor . ", -2px 0 0 " . $borderColor . ";'>Add to hand: </span><input id='manualAddCardToHand' type='text' /><input class='manualAddCardToHand-button' type='button' value='Add' onclick='AddCardToHand()' />&nbsp;" : ""));

  //Tell the player what to pick
  if ($turn[0] != "OVER") {
    $helpText = ($currentPlayer != $playerID ? " Waiting for other player to " . VerbToPlay($turn[0]) . " " . TypeToPlay($turn[0]) . "&nbsp" : " " . GetPhaseHelptext() . "&nbsp;");

    echo ("<span class='playerpick-span'><img class='playerpick-img' title='" . $readyText . "' src='./Images/" . $icon . "'/>");
    if ($currentPlayer == $playerID) {
      echo ($helpText);
      if ($turn[0] == "P" || $turn[0] == "CHOOSEHANDCANCEL" || $turn[0] == "CHOOSEDISCARDCANCEL")
        echo ("(" . ($turn[0] == "P" ? $myResources[0] . " of " . $myResources[1] . " " : "") . "or " . CreateButton($playerID, "Cancel", 10000, 0, "18px") . ")");
      if (CanPassPhase($turn[0])) {
        if ($turn[0] == "B")
          echo (CreateButton($playerID, "Undo Block", 10001, 0, "18px") . " " . CreateButton($playerID, "Pass", 99, 0, "18px") . " " . CreateButton($playerID, "Pass Block and Reactions", 101, 0, "16px", "", "Reactions will not be skipped if the opponent reacts"));
      }
      if ($opponentDisconnected && $playerID != 3) {
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
  if (IsManualMode($playerID))
    echo ("&nbsp;" . CreateButton($playerID, "Turn Off Manual Mode", 26, $SET_ManualMode . "-0", "18px", "", "", true));

  if ((CanPassPhase($turn[0]) && $currentPlayer == $playerID) || (IsReplay() && $playerID == 3)) {
    $prompt = "";
    // Pass Button - Active then Inactive (which is hidden)
    ?>
    <div title='Space is the shortcut to pass.' <?= ProcessInputLink($playerID, 99, 0, prompt: $prompt) ?>
      class='passButton'>
      <span class='pass-label'>
        <?= $passLabel ?>
      </span>
      <span class='pass-tag'>
        [Space]
      </span>
    </div>

    <?php
  }

  if ($currentPlayer == $playerID && CanConfirmPhase($turn[0]))
    echo ("&nbsp;" . CreateButton($playerID, "Confirm", 38, "-", "18px"));

  if ($turn[0] == "M" && $initiativeTaken != 1 && $currentPlayer == $playerID)
    echo ("&nbsp;" . CreateButton($playerID, "Claim Initiative", 34, "-", "18px"));

  echo ("</div>");
  echo ("</div>");

  //Deduplicate current turn effects
  $friendlyEffects = "";
  $opponentEffects = "";

  foreach ([$currentTurnEffects, $nextTurnEffects] as $turnEffects) {
    for ($i = 0; $i < count($turnEffects); $i += CurrentTurnPieces()) {
      $cardID = explode("-", $turnEffects[$i])[0];
      $cardID = explode(",", $cardID)[0];
      $cardID = explode("_", $cardID)[0];
      $isFriendly = ($playerID == $turnEffects[$i + 1] || $playerID == 3 && $otherPlayer != $turnEffects[$i + 1]);
      $color = ($isFriendly ? "#00BAFF" : "#FB0007"); // Me : Opponent
      $effect = "<div class='effect-display' style='border:1px solid " . $color . ";'>";
      $effect .= Card($cardID, "crops", 65, 0, 1);
      $effect .= "</div>";
      if ($isFriendly)
        $friendlyEffects .= $effect;
      else
        $opponentEffects .= $effect;
    }
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

  //Space Arena Dimmer
  echo ("<div class='spaceArena-dimmer'>");
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
    function getCaption($layer, $cardID)
    {
      $captions = [
        "PLAYABILITY" => "When Played",
        "PLAYCARDABILITY" => "When Played",
        "ATTACKABILITY" => "On Attack",
        "ACTIVATEDABILITY" => "Ability"
      ];

      if ($layer == "TRIGGER") {
        switch ($cardID) {
          case "AFTERDESTROYABILITY":
          case "AFTERDESTROYFRIENDLYABILITY":
          case "AFTERDESTROYTHEIRSABILITY":
            return "When Defeated";
          case "ALLYPLAYCARDABILITY":
          case "AMBUSH":
          case "SHIELDED":
            return "When Played";
          default: break;
        }
      }

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
      $layerColor = str_starts_with($layers[$i + 4], "ALLYBOUNTIES") ? ($layerColor == 1 ? 2 : 1) : $layerColor; // Special case for ally collect bounties
      if ($playerID == 3) { // Special case for playerID 3
        $layerColor = ($layerController == $otherPlayer) ? 2 : 1;
      }

      // Count the number of tiles with the same name if the layer is tileable
      $nbTiles = IsTileable($layerName) ? array_reduce($layers, function ($count, $layer, $index) use ($layerName, $layers) {
        $name = ($layer == "LAYER" || IsAbilityLayer($layer)) ? $layers[$index + 2] : $layer;//TODO: look into hoow this gets called
        return $name == $layerName ? $count + 1 : $count;
      }, 0) : 0;

      // Get the caption for the current layer
      $caption = getCaption($layers[$i], $layers[$i + 2]);

      // Determine counters for the card, using number of tiles if tileable, otherwise using the caption
      $counters = IsTileable($layerName) && $nbTiles > 1 ? $nbTiles : ($caption ?: 0);

      // Add the card to the content
      $cardId = $layerName;
      if ($cardId == "ALLYPLAYCARDABILITY")
        $cardId = explode(',', $layers[$i + 3])[0];
      if ($cardId == "AFTERDESTROYABILITY")
        $cardId = $layers[$i + 3];
      if ($cardId == "AFTERDESTROYFRIENDLYABILITY")
        $cardId = explode(",", $layers[$i + 3])[0];
      if ($cardId == "AFTERDESTROYTHEIRSABILITY") {
        $cardId = explode(",", $layers[$i + 3])[0];
        $layerColor = $layerColor == 1 ? 2 : 1;
      }
      $content .= "<div class='tile' style='max-width:{$cardSize}px;'>" . Card($cardId, "concat", $cardSize, 0, 1, 0, $layerColor, $counters, controller: $layerController);

      // Add reorder buttons for ability layers if applicable
      if (IsAbilityLayer($layers[$i]) && ($dqState[8] >= $i || LayersHaveTriggersToResolve()) && $playerID == $mainPlayer) {
        if ($i < $dqState[8]) {
          $content .= "<span class='reorder-button'>" . CreateButton($playerID, ">", 31, $i, "18px", useInput: true) . "</span>";
        }
        if ($i > 0) {
          $content .= "<span class='reorder-button'>" . CreateButton($playerID, "<", 32, $i, "18px", useInput: true) . "</span>";
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
      if (GetHealth($playerID) > 0)
        $content = CreateButton($playerID, "Continue Adventure", 100011, 0, "24px", "", "", false, true);
      else
        $content = CreateButton($playerID, "Game Over!", 100001, 0, "24px", "", "", false, true);
    } else {
      $content = CreateButton($playerID, "Main Menu", 100001, 0, "24px", "", "", false, true);
      if ($playerID == 1 && $theirCharacter[0] != "DUMMY")
        $content .= "&nbsp;" . CreateButton($playerID, "Rematch", 100004, 0, "24px");
      if ($playerID == 1)
        $content .= "&nbsp;" . CreateButton($playerID, "Quick Rematch", 100000, 0, "24px");
      //if ($playerID != 3 && IsPatron($playerID)) $content .= "&nbsp;" . CreateButton($playerID, "Save Replay", 100012, 0, "24px");
      if ($playerID != 3) {
        $time = ($playerID == 1 ? $p1TotalTime : $p2TotalTime);
        $totalTime = $p1TotalTime + $p2TotalTime;
        $content .= "<BR><BR><b style='font-size: 24px;'>Import your deck on <a href='https://swustats.net'>swustats.net</a> to track your deck stats over time!</b><BR>";
        $content .= "<i>(You will need to use the SWU Stats link to play for stats to track)</i><br>";
        $content .= "<BR><span class='Time-Span'>Your Play Time: " . intval($time / 60) . "m" . $time % 60 . "s - Game Time: " . intval($totalTime / 60) . "m" . $totalTime % 60 . "s</span>";
      }
    }

    $content .= "</div>";
    $content .= CardStats($playerID);
    $verb = $playerID == $winner ? "Won!" : "Lost";
    echo CreatePopup("OVER", [], 1, 1, "You {$verb}", 1, $content, "./", true);
  }

  if ($turn[0] == "DYNPITCH" && $isActivePlayer) {
    $content = "<div display:inline;'>";
    $options = explode(",", $turn[2]);
    for ($i = 0; $i < count($options); ++$i) {
      $content .= CreateButton($playerID, $options[$i], 7, $options[$i], "24px");
    }
    $content .= "</div>";
    echo CreatePopup("DYNPITCH", [], 0, 1, Capitalize(VerbToPlay($turn[0])) . " " . TypeToPlay($turn[0]), 1, $content);
  }

  if (($turn[0] == "BUTTONINPUT" || $turn[0] == "CHOOSEARCANE" || $turn[0] == "BUTTONINPUTNOPASS") && $isActivePlayer) {
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

  if ($turn[0] == "YESNO" && $isActivePlayer) {
    $content = "<div style='display:flex;justify-content:center;margin-top:24px;'>";
    $content .= CreateButton($playerID, "Yes", 20, "YES", "20px");
    $content .= CreateButton($playerID, "No", 20, "NO", "20px");
    $content .= "</div>";
    if (GetDQHelpText() != "-")
      $caption = implode(" ", explode("_", GetDQHelpText()));
    else
      $caption = Capitalize(VerbToPlay($turn[0])) . " " . TypeToPlay($turn[0]);
    echo CreatePopup("YESNO", [], 0, 1, $caption, 1, $content);
  }

  if ($turn[0] == "OK" && $isActivePlayer) {
    $description = $turn[2];
    if ($description == "-" || $description == "<-") {
      $description = "";
    }

    $content = "";
    if ($description != "") {
      $description = implode(" ", explode("_", $description));
      $content .= "<div style='text-align: center; margin-bottom: 24px; color: rgba(255, 255, 255, 0.8);'>" . $description . "</div>";
    }

    $content .= CreateButton($playerID, "Ok", 99, "OK", "20px");
    if (GetDQHelpText() != "-")
      $caption = implode(" ", explode("_", GetDQHelpText()));
    else
      $caption = Capitalize(VerbToPlay($turn[0])) . " " . TypeToPlay($turn[0]);
    echo CreatePopup("OK", [], 0, 1, $caption, 1, $content);
  }

  if (($turn[0] == "OPT" || $turn[0] == "CHOOSETOP" || $turn[0] == "MAYCHOOSETOP" || $turn[0] == "CHOOSEBOTTOM" || $turn[0] == "CHOOSECARD" || $turn[0] == "MAYCHOOSECARD") && $isActivePlayer) {
    $content = "<table style='margin: 0 auto;'><tr>";
    $options = isset($turn[2]) ? explode(",", $turn[2]) : [];
    for ($i = 0; $i < count($options); ++$i) {
      $content .= "<td>";
      $content .= "<table><tr><td>";
      if (str_contains($options[$i], "-")) {
        $cardDefinition = explode("-", $options[$i]);
        $border = $playerID == $cardDefinition[1] ? 6 : 2;
        $content .= Card($cardDefinition[0], "concat", $cardSize, 0, 1, borderColor: $border);
      } else {
        $content .= Card($options[$i], "concat", $cardSize, 0, 1);
      }
      $content .= "</td></tr><tr><td>";
      if ($turn[0] == "CHOOSETOP" || $turn[0] == "MAYCHOOSETOP" || $turn[0] == "OPT")
        $content .= CreateButton($playerID, "Top", 8, $options[$i], "20px");
      if ($turn[0] == "CHOOSEBOTTOM" || $turn[0] == "OPT")
        $content .= CreateButton($playerID, "Bottom", 9, $options[$i], "20px");
      if ($turn[0] == "CHOOSECARD" || $turn[0] == "MAYCHOOSECARD")
        $content .= CreateButton($playerID, "Choose", 23, $options[$i], "20px");
      $content .= "</td></tr>";
      $content .= "</table>";
      $content .= "</td>";
    }
    $content .= "</tr></table>";
    echo CreatePopup("OPT", [], 0, 1, GetPhaseHelptext(), 1, $content);
  }

  if (($turn[0] == "CHOOSETOPOPPONENT") && $isActivePlayer) { //Use when you have to reorder the top of your opponent library e.g. Righteous Cleansing
    $otherPlayer = ($playerID == 1 ? 2 : 1);
    $content = "<table><tr>";
    $options = explode(",", $turn[2]);
    for ($i = 0; $i < count($options); ++$i) {
      $content .= "<td>";
      $content .= "<table><tr><td>";
      $content .= Card($options[$i], "concat", $cardSize, 0, 1);
      $content .= "</td></tr><tr><td>";
      if ($turn[0] == "CHOOSETOPOPPONENT")
        $content .= CreateButton($otherPlayer, "Top", 29, $options[$i], "20px");
      $content .= "</td></tr>";
      $content .= "</table>";
      $content .= "</td>";
    }
    $content .= "</tr></table>";
    echo CreatePopup("CHOOSETOPOPPONENT", [], 0, 1, Capitalize(VerbToPlay($turn[0])) . " " . TypeToPlay($turn[0]), 1, $content);
  }

  if ($turn[0] == "LOOKHAND" && $isActivePlayer) {
    $content = "<table style='margin: 0 auto;'><tr>";
    for ($i = 0; $i < count($theirHand); ++$i) {
      $content .= "<td>";
      $content .= Card($theirHand[$i], "concat", $cardSize, 0, 1);
      $content .= "</td>";
    }
    $content .= "</tr></table>";
    $content .= "<div style='text-align: center;'>";
    $content .= CreateButton($playerID, "Ok", 99, "OK", "20px");
    $content .= "</div>";
    echo CreatePopup("LOOKHAND", [], 0, 1, "Opponent's hand", 1, $content);
  }

  if ($turn[0] == "HANDTOPBOTTOM" && $isActivePlayer) {
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
    echo CreatePopup("HANDTOPBOTTOM", [], 0, 1, Capitalize(VerbToPlay($turn[0])) . " " . TypeToPlay($turn[0]), 1, $content);
  }

  $mzChooseFromPlay = false;
  $optionsIndex = [];
  if (($turn[0] == "MAYCHOOSEMULTIZONE" || $turn[0] == "CHOOSEMULTIZONE") && $isActivePlayer) {
    $content = "<div display:inline;'>";
    $options = explode(",", $turn[2]);
    $mzChooseFromPlay = true;
    $otherPlayer = $playerID == 2 ? 1 : 2;
    $theirAllies = &GetAllies($otherPlayer);
    $myAllies = &GetAllies($playerID);
    for ($i = 0; $i < count($options); ++$i) {
      $optionsIndex[] = $options[$i];
      $option = explode("-", $options[$i]);
      if ($option[0] == "MYCHAR")
        $source = $myCharacter;
      else if ($option[0] == "THEIRCHAR")
        $source = $theirCharacter;
      else if ($option[0] == "LAYER")
        $source = $layers;
      else if ($option[0] == "MYHAND")
        $source = $myHand;
      else if ($option[0] == "THEIRHAND")
        $source = $theirHand;
      else if ($option[0] == "MYDISCARD")
        $source = $myDiscard;
      else if ($option[0] == "THEIRDISCARD")
        $source = $theirDiscard;
      else if ($option[0] == "MYBANISH")
        $source = $myBanish;
      else if ($option[0] == "THEIRBANISH")
        $source = $theirBanish;
      else if ($option[0] == "MYALLY")
        $source = $myAllies;
      else if ($option[0] == "THEIRALLY")
        $source = $theirAllies;
      else if ($option[0] == "MYARS")
        $source = $myArsenal;
      else if ($option[0] == "THEIRARS")
        $source = $theirArsenal;
      else if ($option[0] == "MYPITCH")
        $source = $myPitch;
      else if ($option[0] == "THEIRPITCH")
        $source = $theirPitch;
      else if ($option[0] == "MYDECK")
        $source = $myDeck;
      else if ($option[0] == "THEIRDECK")
        $source = $theirDeck;
      // else if ($option[0] == "MYMATERIAL") $source = $myMaterial;//FAB
      // else if ($option[0] == "THEIRMATERIAL") $source = $theirMaterial;//FAB
      else if ($option[0] == "MYRESOURCES")
        $source = &GetMemory($playerID);
      else if ($option[0] == "THEIRRESOURCES")
        $source = &GetMemory($playerID == 1 ? 2 : 1);
      else if ($option[0] == "LANDMARK")
        $source = $landmarks;
      else if ($option[0] == "CC")
        $source = $combatChain;
      else if ($option[0] == "COMBATCHAINLINK")
        $source = $combatChain;

      if ($option[0] != "MYCHAR" && $option[0] != "THEIRCHAR" && $option[0] != "MYALLY" && $option[0] != "THEIRALLY" && $option[0] != "MYHAND")
        $mzChooseFromPlay = false;

      $counters = 0;
      $lifeCounters = 0;
      $defCounters = 0;
      $atkCounters = 0;

      $index = intval($option[1]);
      $card = $source[$index];
      if ($option[0] == "LAYER" && (IsAbilityLayer($card)))
        $card = $source[$index + 2];
      $playerBorderColor = 0;

      if (str_starts_with($option[0], "MY"))
        $playerBorderColor = 1;
      else if (str_starts_with($option[0], "THEIR"))
        $playerBorderColor = 2;
      else if ($option[0] == "CC")
        $playerBorderColor = ($combatChain[$index + 1] == $playerID ? 1 : 2);
      else if ($option[0] == "LAYER") {
        $playerBorderColor = ($layers[$index + 1] == $playerID ? 1 : 2);
      }

      // Overwrite the $playerBorderColor for MYRESOURCES to highlight stolen cards
      if ($option[0] == "MYRESOURCES" && $myArsenal[$index + 6] != "-1")
        $playerBorderColor = 2;

      if ($option[0] == "THEIRARS" && $theirArsenal[$index + 1] == "DOWN")
        $card = $TheirCardBack;

      $overlay = 0;
      $attackCounters = -1;
      //NRA TODO
      //Show attack and hp counters on allies in the popups
      if ($option[0] == "THEIRALLY") {
        $ally = new Ally("MYALLY-" . $index, $otherPlayer);
        $lifeCounters = $ally->Health();
        $defCounters = 0;
        $attackCounters = $ally->CurrentPower();
        if ($ally->IsExhausted())
          $overlay = 1;
      } elseif ($option[0] == "MYALLY") {
        $ally = new Ally("MYALLY-" . $index, $playerID);
        $lifeCounters = $ally->Health();
        $defCounters = 0;
        $attackCounters = $ally->CurrentPower();
        if ($ally->IsExhausted())
          $overlay = 1;
      } elseif ($option[0] == "MYRESOURCES") {
        if ($myArsenal[$index + 4] == 1)
          $overlay = 1;
      }
      $content .= Card($card, "concat", $cardSize, 16, 1, $overlay, $playerBorderColor, $counters, $options[$i], "", false, $lifeCounters, $defCounters, $attackCounters, controller: $playerBorderColor);
    }
    $content .= "</div>";
    if (!$mzChooseFromPlay)
      echo CreatePopup("CHOOSEMULTIZONE", [], 0, 1, GetPhaseHelptext(), 1, $content);
  }

  $mzMultiDamage = false;
  $mzMultiHeal = false;
  $canOverkillUnits = false;
  $counterLimitReached = false;
  $mzMultiAllies = [];
  $mzMultiCharacters = [];
  if (($turn[0] == "INDIRECTDAMAGEMULTIZONE" || $turn[0] == "MULTIDAMAGEMULTIZONE" || $turn[0] == "MAYMULTIDAMAGEMULTIZONE" || $turn[0] == "PARTIALMULTIDAMAGEMULTIZONE" || $turn[0] == "PARTIALMULTIHEALMULTIZONE" || $turn[0] == "MAYMULTIHEALMULTIZONE" || $turn[0] == "MULTIHEALMULTIZONE")) {
    $mzMultiDamage = str_contains($turn[0], "DAMAGE");
    $mzMultiHeal = str_contains($turn[0], "HEAL");
    $canOverkillUnits = $mzMultiDamage && $turn[0] != "INDIRECTDAMAGEMULTIZONE";
    $parsedParams = ParseDQParameter($turn[0], $turn[1], $turn[2]);
    $counterLimit = $parsedParams["counterLimit"];
    $mzMultiAllies = $parsedParams["allies"];
    $mzMultiCharacters = $parsedParams["characters"];

    // Get the total counters of the allies and bases
    $totalCounters = 0;
    foreach ($mzMultiAllies as $ally) {
      $ally = new Ally($ally);
      $totalCounters += $ally->Counters();
    }
    foreach ($mzMultiCharacters as $base) {
      $base = new Character($base);
      $totalCounters += $base->Counters();
    }

    $counterLimitReached = $totalCounters >= $counterLimit;
  }

  if (($turn[0] == "MAYCHOOSEDECK" || $turn[0] == "CHOOSEDECK") && $isActivePlayer) {
    ChoosePopup($myDeck, $turn[2], 11, "Choose a card from your deck");
  }

  if ($turn[0] == "CHOOSEBANISH" && $isActivePlayer) {
    ChoosePopup($myBanish, $turn[2], 16, "Choose a card from your banish", BanishPieces());
  }

  if (($turn[0] == "CHOOSETHEIRHAND") && $isActivePlayer) {
    ChoosePopup($theirHand, $turn[2], 16, "Choose a card from your opponent's hand");
  }

  if (($turn[0] == "CHOOSEDISCARD" || $turn[0] == "MAYCHOOSEDISCARD" || $turn[0] == "CHOOSEDISCARDCANCEL") && $isActivePlayer) {
    $caption = "Choose a card from your discard";
    if (GetDQHelpText() != "-")
      $caption = implode(" ", explode("_", GetDQHelpText()));
    ChoosePopup($myDiscard, $turn[2], 16, $caption, zoneSize: DiscardPieces());
  }

  if (($turn[0] == "MAYCHOOSETHEIRDISCARD") && $isActivePlayer) {
    ChoosePopup($theirDiscard, $turn[2], 16, "Choose a card from your opponent's graveyard", zoneSize: DiscardPieces());
  }

  if (($turn[0] == "CHOOSECOMBATCHAIN" || $turn[0] == "MAYCHOOSECOMBATCHAIN") && $isActivePlayer) {
    ChoosePopup($combatChain, $turn[2], 16, "Choose a card from the combat chain", CombatChainPieces());
  }

  if ($turn[0] == "CHOOSECHARACTER" && $isActivePlayer) {
    ChoosePopup($myCharacter, $turn[2], 16, "Choose a card from your character/equipment", CharacterPieces());
  }

  if ($turn[0] == "CHOOSETHEIRCHARACTER" && $isActivePlayer) {
    ChoosePopup($theirCharacter, $turn[2], 16, "Choose a card from your opponent character/equipment", CharacterPieces());
  }

  if (($turn[0] == "CHOOSEOPTION" || $turn[0] == "MAYCHOOSEOPTION") && $currentPlayer == $playerID) {
    $caption = "<div>" . Capitalize(VerbToPlay($turn[0])) . " " . TypeToPlay($turn[0]) . "</div>";
    if (GetDQHelpText() != "-")
      $caption = "<div>" . implode(" ", explode("_", GetDQHelpText())) . "</div>";
    $params = explode("&", $turn[2]);
    $cardID = $params[0];
    $options = explode(";", $params[1]);
    $hiddenOptions = isset($params[2]) && $params[2] != "" ? explode(",", $params[2]) : [];
    $content = "<style>
      .card-container {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 75%;
        gap: 16px;
        margin-left: -5px;
        margin-top: 3%;
      }
      .card {
        display: flex;
        justify-content: center;
        position: relative;
        height: 100%;
        aspect-ratio: 0.71;
        overflow: hidden;
        cursor: pointer;
        border-radius: 0.375rem;
        border: 2px solid #00FF66;
        transition: background 0.3s ease;
      }
      .card.hidden {
        display: none;
      }
      .card img {
        height: 270%;
        width: 270%;
        position: absolute;
        left: 50%;
      }
      .card.event img {
        bottom: 0;
        transform: translate(-50%, 10%);
      }
      .card.non-event img {
        top: 0;
        transform: translate(-50%, -15%);
      }
      .card-content {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        z-index: 2;
        color: white;
        text-align: center;
        font-size: 16px;
        font-weight: 500;
        padding: 8px;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.3) 70%);
        transition: background 0.3s ease;
      }
      .card:hover .card-content {
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.5) 70%);
      }
    </style>";

    $content .= "<div class='card-container'>";
    for ($i = 0; $i < count($options); ++$i) {
      $submitLink = ProcessInputLink($playerID, 36, $i, "onclick");
      $cardTypeClass = DefinedTypesContains($cardID, "Event") ? "event" : "non-event";
      $content .= "<div class='card $cardTypeClass" . (in_array($i, $hiddenOptions) ? " hidden" : "") . "' $submitLink>";
      $content .= "<img src='./WebpImages2/$cardID.webp' />";
      $content .= "<div class='card-content'>";
      $content .= str_replace("_", " ", $options[$i]);
      $content .= "</div>";
      $content .= "</div>";
    }
    $content .= "</div>";
    echo CreatePopup("CHOOSEOPTION", [], 0, 1, $caption, 1, $content, height: "35%", width: "50%");
  }

  // MULTICHOOSETEXT and MAYMULTICHOOSETEXT are deprecated, use MULTICHOOSE and MAYMULTICHOOSE instead
  if (($turn[0] == "MULTICHOOSETHEIRDISCARD" || $turn[0] == "MULTICHOOSEDISCARD"
      || $turn[0] == "MULTICHOOSEHAND" || $turn[0] == "MAYMULTICHOOSEHAND"
      || $turn[0] == "MULTICHOOSEUNIT" || $turn[0] == "MULTICHOOSETHEIRUNIT" || $turn[0] == "MULTICHOOSEOURUNITS"
      || $turn[0] == "MULTICHOOSEDECK" || $turn[0] == "MULTICHOOSETEXT" || $turn[0] == "MAYMULTICHOOSETEXT" || $turn[0] == "MULTICHOOSETHEIRDECK"
      || $turn[0] == "MULTICHOOSEMYUNITSANDBASE" || $turn[0] == "MULTICHOOSETHEIRUNITSANDBASE" || $turn[0] == "MULTICHOOSEOURUNITSANDBASE"
      || $turn[0] == "MAYMULTICHOOSEAURAS" || $turn[0] == "MULTICHOOSEMULTIZONE") && $currentPlayer == $playerID) {
    $content = "";
    $multiTheirAllies = &GetAllies($playerID == 1 ? 2 : 1);
    $multiAllies = &GetAllies($playerID);
    $theirBaseCardID = "";
    $myBaseCardID = "";
    echo ("<div 'display:inline; width: 100%;'>");
    $sets = explode("&", $turn[2]);
    $all = count($sets) == 2;
    $options = [];
    if(!$all) {
      $params = explode("-", $sets[0]);
      $optionParams = implode("-", array_slice($params, 1));
      if($turn[0] == "MULTICHOOSEMYUNITSANDBASE") {
        if($params[0] == "0;0") {
          $options[] = "BASE";
          $myBaseCardID = GetPlayerCharacter($playerID)[0];
        } else {
          $pieces = explode(";", $params[0]);
          $options[] = "BASE";
          $myBaseCardID = GetPlayerCharacter($playerID)[$pieces[0]];
          $params[0] = $pieces[1];
        }
      }
      if($turn[0] == "MULTICHOOSETHEIRUNITSANDBASE") {
        if($params[0] == "0;0") {
          $options[] = "BASE";
          $theirBaseCardID = GetPlayerCharacter($playerID == 1 ? 2 : 1)[0];
        } else {
          $pieces = explode(";", $params[0]);
          $options[] = "BASE";
          $theirBaseCardID = GetPlayerCharacter($playerID == 1 ? 2 : 1)[$pieces[0]];
          $params[0] = $pieces[1];
        }
      }
      $options = array_merge($options, $optionParams != "" ? explode(",", $optionParams) : []);
    } else {
      //TODO: Redemption
      $paramsTheirs = explode("-", $sets[0]);
      $paramsMine = explode("-", $sets[1]);
      $params = [$paramsTheirs, $paramsMine];

      $optionsTheirs = $paramsTheirs[1] == "" ? [] : explode(",", $paramsTheirs[1]);
      $optionsMine = $paramsMine[1] == "" ? [] : explode(",", $paramsMine[1]);
      $options = [$optionsTheirs, $optionsMine];
    }
    if(!$all) {
      $otherPlayer = $playerID == 2 ? 1 : 2;
      $theirAllies = &GetAllies($otherPlayer);
      $myAllies = &GetAllies($playerID);
      $caption = "<div>Choose up to " . $params[0] . " card" . ($optionParams > 1 ? "s." : ".") . "</div>";
      $content .= CreateForm($playerID, "Submit", 19, count($options));
      $content .= "<table class='table-border-a'><tr>";
      for ($i = 0; $i < count($options); ++$i) {
        $content .= "<td>";
        $content .= CreateCheckbox($i, strval($i));
        $content .= "</td>";
      }
      $content .= "</tr><tr>";
      for ($i = 0; $i < count($options); ++$i) {
        $content .= "<td style='text-align:center;vertical-align:top;'>";
        $content .= "<div class='container'>";
        if ($turn[0] == "MULTICHOOSEDISCARD")
          $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($myDiscard[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
        else if ($turn[0] == "MULTICHOOSETHEIRDISCARD")
          $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($theirDiscard[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
        else if ($turn[0] == "MULTICHOOSEHAND" || $turn[0] == "MAYMULTICHOOSEHAND")
          $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($myHand[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
        else if ($turn[0] == "MULTICHOOSEUNIT")
          $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($multiAllies[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
        else if ($turn[0] == "MULTICHOOSETHEIRUNIT")
          $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($multiTheirAllies[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
        else if($turn[0] == "MULTICHOOSEMULTIZONE") {
          $option = explode("-", $options[$i]);
          switch($option[0]) {
            case "MYCHAR":
              $source = $myCharacter;
              break;
            case "THEIRCHAR":
              $source = $theirCharacter;
              break;
            case "MYALLY":
              $source = $myAllies;
              break;
            case "THEIRALLY":
              $source = $theirAllies;
              break;
            default: break;
          }
          $subcards = "";
          if($option[0] == "MYALLY" || $option[0] == "THEIRALLY") {
            $ally = new Ally("MYALLY-" . $option[1], $option[0] == "MYALLY" ? $playerID : $otherPlayer);
            $subcards = $ally->GetSubcards();
            $subtitle = "<div>";
            $subtitle .= "<div>" . ($option[0] == "MYALLY" ? "Mine" : "Theirs") . "</div>";
            if(count($subcards) > 0) {
              $subtitle .= "<ul>";
              for($j = 0; $j < count($subcards); $j+=SubcardPieces()) {
                $subcardStyle = "list-style: none;margin: 0 0 0 -40px;". subcardBorder(getSubcardAspect($subcards[$j])) . ";
                  border-radius:16px;text-transform: uppercase;background-size: 120px;line-height: 1.2;font-size: 14px;";
                $subtitle .= "<li style='$subcardStyle'>" . CardTitle($subcards[$j]) . "</li>";
              }
              $subtitle .= "</ul>";
            }
            $subtitle .= "</div>";
          }
          $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($source[$option[1]], "concat", $cardSize, 0, 1) . $subtitle . "</label>";
        }
        else if ($turn[0] == "MULTICHOOSEMYUNITSANDBASE")
          $content .= "<label class='multichoose' for=chk" . $i . ">" . (
            $options[$i] == "BASE"
              ? Card($myBaseCardID, "concat", $cardSize, 0, 1)
              : Card($multiAllies[$options[$i]], "concat", $cardSize, 0, 1)
            )
            . "</label>";
        else if ($turn[0] == "MULTICHOOSETHEIRUNITSANDBASE")
          $content .= "<label class='multichoose' for=chk" . $i . ">" . (
            $options[$i] == "BASE"
              ? Card($theirBaseCardID, "concat", $cardSize, 0, 1)
              : Card($multiTheirAllies[$options[$i]], "concat", $cardSize, 0, 1)
            )
            . "</label>";
        else if ($turn[0] == "MULTICHOOSEDECK")
          $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($myDeck[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
        else if ($turn[0] == "MULTICHOOSETHEIRDECK")
          $content .= "<label class='multichoose' for=chk" . $i . ">" . Card($theirDeck[$options[$i]], "concat", $cardSize, 0, 1) . "</label>";
        else if ($turn[0] == "MULTICHOOSETEXT" || $turn[0] == "MAYMULTICHOOSETEXT")
          $content .= implode(" ", explode("_", strval($options[$i])));
        $content .= "<div class='overlay'><div class='text'>Select</div></div></div>";
        $content .= "</td>";
      }
    }
    else {
      //TODO: Redemption
      $content .= CreateForm($playerID, "Submit", 19, count($options[0]), count($options[1]));
      $content .= "<table class='table-border-a'><tr>";
      for($i = 0; $i < count($options[0]); ++$i) {
        if($i == floor(count($options[0]) / 2))
          $content .= "<td style='color:white;'>Theirs</td>";
        else
          $content .= "<td></td>";
      }
      $content .= "</tr><tr>";
      for ($i = 0; $i < count($options[0]); ++$i) {
        $content .= "<td>";
        $content .= CreateCheckbox("t" . $i, strval($i));
        $content .= "</td>";
      }
      $content .= "</tr><tr>";
      for($i = 0; $i < count($options[0]); ++$i) {
        $content .= "<td>";
        $content .= "<div class='container'>";
        $content .= "<label class='multichoose' for=chkt" . $i . ">" . Card($multiTheirAllies[$options[0][$i]], "concat", $cardSize, 0, 1) . "</label>";
        $content .= "<div class='overlay'><div class='text'>Select</div></div></div>";
        $content .= "</td>";
      }
      $content .= "</tr></table>";
      $content .= "<table class='table-border-a'><tr>";
      for($i = 0; $i < count($options[1]); ++$i) {
        if($i == floor(count($options[1]) / 2))
          $content .= "<td style='color:white;'>Mine</td>";
        else
          $content .= "<td></td>";
      }
      $content .= "</tr><tr>";
      for ($i = 0; $i < count($options[1]); ++$i) {
        $content .= "<td>";
        $content .= CreateCheckbox("m" . $i, strval($i));
        $content .= "</td>";
      }
      $content .= "</tr><tr>";
      for($i = 0; $i < count($options[1]); ++$i) {
        $content .= "<td>";
        $content .= "<div class='container'>";
        $content .= "<label class='multichoose' for=chkm" . $i . ">" . Card($multiAllies[$options[1][$i]], "concat", $cardSize, 0, 1) . "</label>";
        $content .= "<div class='overlay'><div class='text'>Select</div></div></div>";
        $content .= "</td>";
      }
    }

    $content .= "</tr></table></form></div>";
    if (GetDQHelpText() != "-")
      $caption = "<div>" . implode(" ", explode("_", GetDQHelpText())) . "</div>";
    echo CreatePopup("MULTICHOOSE", [], 0, 1, $caption, 1, $content, height: $all ? "55%" : "40%");
  }

  if ($turn[0] == "MULTICHOOSESEARCHTARGETS" && $currentPlayer == $playerID) { //Widely copied from the above MULTICHOOSE cases, but incorporating the fact that only some options shown are selectable.
    $content = "";
    echo ("<div 'display:inline; width: 100%;'>");
    $params = explode("-", $turn[2]);
    $searchIndices = explode(",", $params[1]);
    $validTargetIndices = explode(",", $params[3]);
    if ($validTargetIndices[0] == "")
      $validTargetIndices = []; //Fixing how no hits(failed search) is represented so count() accurately represents the situation.
    $caption = "<div>Choose up to " . $params[0] . " card" . ($params[0] > 1 ? "s." : ".") . "</div>";
    if (GetDQHelpText() != "-")
      $caption = "<div>" . implode(" ", explode("_", GetDQHelpText())) . "</div>";
    $content .= CreateForm($playerID, "Submit", 19, count($validTargetIndices));
    $content .= "<table class='table-border-a'><tr>";
    $checkboxCount = 0; //function chkSumbmit() called by the Submit button relies on knowing the number of checkboxes and them being numbered sequentially, so I can't simply skip those corresponding to the unselectable cards outright. I had no luck with hiding the checkboxes I wanted gone so instead I do create only those that should be usable, but use this variable to track their indices, seperate from the indices of the displayed cards.
    for ($i = 0; $i < count($searchIndices); ++$i) {
      $selectable = array_search($searchIndices[$i], $validTargetIndices) !== false;
      $content .= "<td>";
      if ($selectable) {
        $content .= CreateCheckbox($checkboxCount++, strval($i));
      }
      $content .= "</td>";
    }
    $content .= "</tr><tr>";
    $checkboxCount = 0;
    for ($i = 0; $i < count($searchIndices); ++$i) {
      $selectable = array_search($searchIndices[$i], $validTargetIndices) !== false;
      $content .= "<td>";
      $content .= "<div class='container'>";
      $forAttribute = $selectable ? "for=chk" . $checkboxCount++ : "";
      $content .= "<label class='multichoose' " . $forAttribute . ">" . Card($myDeck[$searchIndices[$i]], "concat", $cardSize, 0, 1) . "</label>";
      if ($selectable)
        $content .= "<div class='overlay'><div class='text'>Select</div></div>";
      $content .= "</div></td>";
    }
    $content .= "</tr></table></form></div>";
    echo CreatePopup("MULTICHOOSE", [], 0, 1, $caption, 1, $content);
  }

  if ($turn[0] == "INPUTCARDNAME" && $isActivePlayer) {
    $caption = "<div>Name a card</div>";
    $content = CreateAutocompleteForm($playerID, "Submit", 30, explode("|", CardTitles()));
    echo CreatePopup("INPUTCARDNAME", [], 0, 1, $caption, 1, $content);
  }

  //Opponent hand
  $handContents = "";
  $chatboxWidth = "238px";
  echo ("<div class='their-hand-wrapper' style='width: calc(100% - $chatboxWidth);'>");
  echo ("<div id='theirHand'>");
  for ($i = 0; $i < count($theirHand); ++$i) {
    if ($handContents != "")
      $handContents .= "|";
    if ($playerID == 3 && IsCasterMode())
      $handContents .= ClientRenderedCard(cardNumber: $theirHand[$i], controller: ($playerID == 1 ? 2 : 1));
    else
      $handContents .= ClientRenderedCard(cardNumber: $TheirCardBack, controller: ($playerID == 1 ? 2 : 1));
  }
  echo ($handContents);
  $banishUI = TheirBanishUIMinimal("HAND");
  if ($handContents != "" && $banishUI != "")
    echo ("|");
  echo ($banishUI);
  echo ("</div>");
  echo ("</div>");

  //Show deck, discard, pitch, banish
  //Display Their Discard
  if (count($theirDiscard) > 0) {
    echo ("<div class= 'their-discard' title='Click to view the cards in your opponent's Graveyard.' onclick='TogglePopup(\"theirDiscardPopup\");'>");
    echo (Card($theirDiscard[count($theirDiscard) - DiscardPieces()], "concat", $cardSizeAura, 0, 0, 0, 0, count($theirDiscard) / DiscardPieces(), controller: $otherPlayer));
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
    for ($i = 0; $i + AllyPieces() - 1 < count($theirAllies); $i += AllyPieces()) {
      $mzIndex = "THEIRALLY-" . $i;
      $inOptions = in_array($mzIndex, $optionsIndex);
      $action = $mzChooseFromPlay && $inOptions ? 16 : 0;
      $actionDataOverride = $mzChooseFromPlay && $inOptions ? $mzIndex : 0;
      $border = CardBorderColor($theirAllies[$i], "ALLY", $action == 16, "THEIRS");

      $ally = new Ally($mzIndex, $otherPlayer);
      $playable = GetOpponentControlledAbilityNames($ally->CardID()) != "";

      $showCounterControls = false;
      $counters = 0;
      $counterType = 0;
      $counterLimit = 0;

      if (!$mzChooseFromPlay && $playable && TheirAllyPlayableExhausted($ally)) {
        $border = CardBorderColor($theirAllies[$i], "PLAY", $playable);
        $action = $currentPlayer == $playerID && $turn[0] != "P" && $playable ? 105 : 0; // 105 is the Ally Ability for opponent-controlled abilities like Mercenary Gunship
        $actionDataOverride = strval($i);
      } else if ($mzMultiDamage || $mzMultiHeal) {
        $isTarget = in_array($ally->UniqueID(), $mzMultiAllies);
        if ($isTarget) {
          $showCounterControls = $isActivePlayer;
          $border = $showCounterControls ? 4 : 0;
          $actionDataOverride = $ally->UniqueID();
          $counters = $ally->Counters();
          $counterType = $mzMultiDamage ? 1 : 2;
          if ($mzMultiDamage) {
            $counterLimit = ($ally->HasShield() || $canOverkillUnits) ? 0 : $ally->Health();
          }
        }
      }

      $opts = array(
        'action' => $action,
        'actionOverride' => $actionDataOverride,
        'border' => $border,
        'currentHP' => $ally->Health(),
        'maxHP' => $ally->MaxHealth(),
        'subcard' => $theirAllies[$i + 4],
        'subcards' => $theirAllies[$i + 4] != "-" ? explode(",", $theirAllies[$i + 4]) : [],
        'currentPower' => $ally->CurrentPower(),
        'hasSentinel' => HasSentinel($theirAllies[$i], $otherPlayer, $i),
        'overlay' => $theirAllies[$i + 1] != 2 ? 1 : 0,
        'cloned' => $theirAllies[$i + 13] == 1,
        'counters' => $counters,
        'counterType' => $counterType,
        'showCounterControls' => $showCounterControls,
        'counterLimit' => $counterLimit,
        'counterLimitReached' => $counterLimitReached,
      );
      $isUnimplemented = IsUnimplemented($theirAllies[$i]);
      $cardArena = $ally->CurrentArena();
      //Their Unit Spacing
      if ($cardArena == "Ground")
        $cardText = '<div id="unique-' . $theirAllies[$i + 5] . '" class="cardContainer ' . ($theirAllies[$i + 1] != 2 ? 'exhausted' : '') . '">';
      else
        $cardText = '<div id="unique-' . $theirAllies[$i + 5] . '" class="cardContainer ' . ($theirAllies[$i + 1] != 2 ? 'exhausted' : '') . '">';
      //card render their units
      $cardText .= (Card($theirAllies[$i], "concat", $cardSizeAura, $opts, isUnimplemented: $isUnimplemented));
      $cardText .= ("</div>");
      if ($cardArena == "Ground")
        $groundAllies .= $cardText;
      else
        $spaceAllies .= $cardText;
    }
  }
  //Now display their Leader and Base
  $numWeapons = 0;
  echo ("<div id='theirChar'>");
  $characterContents = "";
  for ($i = 0; $i < count($theirCharacter); $i += CharacterPieces()) {
    if ($i > 0 && $inGameStatus == "0")
      continue;
    $mzIndex = "THEIRCHAR-" . $i;
    $inOptions = in_array($mzIndex, $optionsIndex);
    $action = $mzChooseFromPlay && $inOptions ? 16 : 0;
    $actionDataOverride = $mzChooseFromPlay && $inOptions ? $mzIndex : 0;
    $border = CardBorderColor($theirCharacter[$i], "CHAR", $action == 16, "THEIRS");
    $atkCounters = 0;
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

    $showCounterControls = false;
    $counters = 0;
    $counterType = 0;

    if ($mzMultiDamage || $mzMultiHeal) {
      $character = new Character("THEIRCHAR-" . $i, $playerID);
      $isTarget = in_array($character->UniqueID(), $mzMultiCharacters);
      if ($isTarget) {
        $showCounterControls = $isActivePlayer;
        $actionDataOverride = $character->UniqueID();
        $border = $showCounterControls ? 4 : 0;
        $counters = $character->Counters();
        $counterType = $mzMultiDamage ? 1 : 2;
      }
    }

    if ($characterContents != "")
      $characterContents .= "|";
    $characterContents .= ClientRenderedCard(cardNumber: $theirCharacter[$i], action: $action, actionDataOverride: $actionDataOverride, borderColor: $border, overlay: $overlay, counters: $counters, defCounters: 0, atkCounters: $atkCounters, controller: $otherPlayer, type: $type, sType: $sType, isFrozen: ($theirCharacter[$i + 8] == 1), onChain: ($theirCharacter[$i + 6] == 1), isBroken: ($theirCharacter[$i + 1] == 0), rotate: 0, landscape: 1, epicActionUsed: $epicActionUsed, showCounterControls: $showCounterControls, counterType: $counterType, counterLimitReached: $counterLimitReached);
  }
  echo ($characterContents);

  echo ("</div>");

  //Their Space Allies
  echo ("<div class='spaceEnemiesContainer'>");
  echo ($spaceAllies);
  echo ("</div>");

  //Their Ground Allies
  echo ("<div class='groundEnemiesContainer'>");
  echo ($groundAllies);
  echo ("</div>");

  //Now display their resources
  $arsenalLeft = "calc(50% - " . (count($theirArsenal) / ArsenalPieces() / 2 * intval($cardWidth) + 14) . "px)";
  $numReady = 0;
  $total = 0;
  for ($i = 0; $i < count($theirArsenal); $i += ArsenalPieces()) {
    ++$total;
    if ($theirArsenal[$i + 4] != 1)
      ++$numReady;
  }

  echo ("<div class='their-resources'>");
  echo ("<div class='resources' title='Opponent resources'><img src='./Images/Resource.png' /><span>" . $numReady . "/" . $total . "</span></div>");
  echo ("</div>");

  echo ("</div>");
  echo ("</div>");

  $restriction = "";
  $actionType = $turn[0] == "ARS" ? 4 : 27;
  if (str_contains($turn[0], "CHOOSEHAND") && ($turn[0] != "" || $turn[0] != "MAYMULTICHOOSEHAND"))
    $actionType = 16;
  $handLeft = "calc(50% - " . ((count($myHand) * ($cardWidth + 15)) / 2) . "px - 119px)";
  echo ("<div id='myHand' style='left:" . $handLeft . ";'>"); //Hand div
  $handContents = "";
  for ($i = 0; $i < count($myHand); ++$i) {
    if ($handContents != "")
      $handContents .= "|";
    if ($playerID == 3) {
      if (IsCasterMode())
        $handContents .= ClientRenderedCard(cardNumber: $myHand[$i], controller: 2);
      else
        $handContents .= ClientRenderedCard(cardNumber: $MyCardBack, controller: 2);
    } else {
      if ($mzChooseFromPlay) {
        $mzIndex = "MYHAND-" . $i;
        $inOptions = in_array($mzIndex, $optionsIndex);
        $actionTypeOut = $inOptions ? 16 : 0;
        $actionDataOverride = $inOptions ? $mzIndex : 0;
        $border = CardBorderColor($myHand[$i], "HAND", $actionTypeOut == 16);
      } else {
        if ($playerID == $currentPlayer)
          $playable = $turn[0] == "ARS" || ($actionType == 16 && str_contains("," . $turn[2] . ",", "," . $i . ",")) || ($turn[0] == "M" || $turn[0] == "INSTANT") && IsPlayable($myHand[$i], $turn[0], "HAND", -1, $restriction);
        else
          $playable = false;
        $border = CardBorderColor($myHand[$i], "HAND", $playable);
        $actionTypeOut = (($currentPlayer == $playerID) && $playable == 1 ? $actionType : 0);
        if ($restriction != "")
          $restriction = implode("_", explode(" ", $restriction));
        $actionDataOverride = (($actionType == 16 || $actionType == 27) ? strval($i) : "");
      }
      $handContents .= ClientRenderedCard(cardNumber: $myHand[$i], action: $actionTypeOut, borderColor: $border, actionDataOverride: $actionDataOverride, controller: $playerID, restriction: $restriction);
    }
  }
  echo ($handContents);
  $banishUI = BanishUIMinimal("HAND");
  if ($handContents != "" && $banishUI != "")
    echo ("|");
  echo ($banishUI);
  echo ("</div>"); //End hand div

  $myAllies = GetAllies($playerID);
  $spaceAllies = "";
  $groundAllies = "";
  if (count($myAllies) > 0) {
    for ($i = 0; $i < count($myAllies); $i += AllyPieces()) {
      if ($i > count($myAllies) - AllyPieces())
        break;
      $ally = new Ally("MYALLY-" . $i, $playerID);

      $action = 0;
      $border = 0;
      $actionDataOverride = 0;
      $showCounterControls = false;
      $counters = 0;
      $counterType = 0;
      $counterLimit = 0;

      if ($mzChooseFromPlay) {
        $mzIndex = "MYALLY-" . $i;
        $inOptions = in_array($mzIndex, $optionsIndex);
        $action = $inOptions ? 16 : 0;
        $actionDataOverride = $inOptions ? $mzIndex : 0;
        $border = CardBorderColor($myAllies[$i], "PLAY", $action == 16);
      } else if ($mzMultiDamage || $mzMultiHeal) {
        $isTarget = in_array($ally->UniqueID(), $mzMultiAllies);
        if ($isTarget) {
          $showCounterControls = $isActivePlayer;
          $border = $showCounterControls ? 4 : 0;
          $actionDataOverride = $ally->UniqueID();
          $counters = $ally->Counters();
          $counterType = $mzMultiDamage ? 1 : 2;
          if ($mzMultiDamage) {
            $counterLimit = ($ally->HasShield() || $canOverkillUnits) ? 0 : $ally->Health();
          }
        }
      } else {
        $playable = IsPlayable($myAllies[$i], $turn[0], "PLAY", $i, $restriction) && (!$ally->IsExhausted() || AllyPlayableExhausted($ally));
        $border = CardBorderColor($myAllies[$i], "PLAY", $playable);
        $action = $currentPlayer == $playerID && $turn[0] != "P" && $playable ? 24 : 0;
        $actionDataOverride = strval($i);
      }

      $opts = array(
        'currentHP' => $ally->Health(),
        'maxHP' => $ally->MaxHealth(),
        'subcard' => $myAllies[$i + 4],
        'subcards' => $myAllies[$i + 4] != "-" ? explode(",", $myAllies[$i + 4]) : [],
        'currentPower' => $ally->CurrentPower(),
        'hasSentinel' => HasSentinel($myAllies[$i], $playerID, $i),
        'action' => $action,
        'actionOverride' => $actionDataOverride,
        'border' => $border,
        'overlay' => $myAllies[$i + 1] != 2 ? 1 : 0,
        'cloned' => $myAllies[$i + 13] == 1,
        'counters' => $counters,
        'counterType' => $counterType,
        'showCounterControls' => $showCounterControls,
        'counterLimit' => $counterLimit,
        'counterLimitReached' => $counterLimitReached,
      );
      $isUnimplemented = IsUnimplemented($myAllies[$i]);
      $cardArena = $ally->CurrentArena();
      //My Unit Spacing
      if ($cardArena == "Ground")
        $cardText = '<div id="unique-' . $myAllies[$i + 5] . '" class="cardContainer ' . ($myAllies[$i + 1] != 2 ? 'exhausted' : '') . '">';
      else
        $cardText = '<div id="unique-' . $myAllies[$i + 5] . '" class="cardContainer ' . ($myAllies[$i + 1] != 2 ? 'exhausted' : '') . '">';
      if ($manualMode) {
        $cardText .= "<div class='my-units-manual'>";
        $cardText .= CreateButton($playerID, "+", 10012, $i, "20px");
        $cardText .= CreateButton($playerID, "-", 10013, $i, "20px");
        $cardText .= "</div>";
      }
      $cardText .= (Card($myAllies[$i], "concat", $cardSizeAura, $opts, isUnimplemented: $isUnimplemented));
      $cardText .= ("</div>");
      if ($cardArena == "Ground")
        $groundAllies .= $cardText;
      else
        $spaceAllies .= $cardText;
    }
  }

  //Space allies
  echo ("<div class='spaceAlliesContainer'>");
  echo ($spaceAllies);
  echo ("</div>");

  //Ground allies
  echo ("<div class='groundAlliesContainer'>");
  echo ($groundAllies);
  echo ("</div>");

  //Now display my Leader and Base
  $numWeapons = 0;
  $myCharData = "";
  for ($i = 0; $i < count($myCharacter); $i += CharacterPieces()) {
    $restriction = "";
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

    $showCounterControls = false;
    $counters = 0;
    $counterType = 0;
    $border = 0;

    if ($mzChooseFromPlay) {
      $mzIndex = "MYCHAR-" . $i;
      $inOptions = in_array($mzIndex, $optionsIndex);
      $action = $inOptions ? 16 : 0;
      $actionDataOverride = $inOptions ? $mzIndex : 0;
      $border = CardBorderColor($myCharacter[$i], "CHAR", $action == 16);
    } else if ($mzMultiDamage || $mzMultiHeal) {
      $character = new Character("MYCHAR-" . $i, $playerID);
      $isTarget = in_array($character->UniqueID(), $mzMultiCharacters);
      if ($isTarget) {
        $showCounterControls = $isActivePlayer;
        $actionDataOverride = $character->UniqueID();
        $border = $showCounterControls ? 4 : 0;
        $counters = $character->Counters();
        $counterType = $mzMultiDamage ? 1 : 2;
      }
    } else {
      $playable = $playerID == $currentPlayer && IsPlayable($myCharacter[$i], $turn[0], "CHAR", $i, $restriction) && ($myCharacter[$i + 1] == 2 || $epicActionUsed == 0);
      $border = CardBorderColor($myCharacter[$i], "CHAR", $playable);
      $action = $currentPlayer == $playerID && $playable ? 3 : 0;
      $actionDataOverride = strval($i);
    }

    if ($myCharData != "")
      $myCharData .= "|";
    $restriction = implode("_", explode(" ", $restriction));
    $myCharData .= ClientRenderedCard($myCharacter[$i], $action, $myCharacter[$i + 1] != 2 ? 1 : 0, $border, $counters, $actionDataOverride, 0, 0, $atkCounters, $playerID, $type, $sType, $restriction, $myCharacter[$i + 1] == 0, $myCharacter[$i + 6] == 1, $myCharacter[$i + 8] == 1, gem: 0, rotate: 0, landscape: 1, epicActionUsed: $epicActionUsed, showCounterControls: $showCounterControls, counterType: $counterType, counterLimitReached: $counterLimitReached);
  }
  echo ("<div id='myChar' style='display:none;'>");
  echo ($myCharData);
  echo ("</div>");


  //Display my resources
  $numReady = 0;
  $total = 0;
  $arsenalLeft = "calc(50% - " . (count($myArsenal) / ArsenalPieces() / 2 * intval($cardWidth) + 14) . "px)";
  echo ("<div style='position:fixed; left:" . $arsenalLeft . "; bottom:" . (intval(GetCharacterBottom("C", "")) - $cardSize + 15) . "px;'>"); //arsenal div
  for ($i = 0; $i < count($myArsenal); $i += ArsenalPieces()) {
    ++$total;
    if ($myArsenal[$i + 4] != 1)
      ++$numReady;
  }
  echo ("<div class='resource-wrapper my-resources'>");
  echo ("<div class='resources' title='Click to see your resources.' onclick='TogglePopup(\"myResourcePopup\");'><img src='./Images/Resource.png' /><span>" . $numReady . "/" . $total . "</span></div>");
  echo ("</div>");
  echo ("</div>"); //End resource div

  //Show deck, discard
  //Display My Discard
  if (count($myDiscard) > 0) {
    echo ("<div class='my-discard my-discard-fill' title='Click to view the cards in your Graveyard.' onclick='TogglePopup(\"myDiscardPopup\");'>");
    echo (Card($myDiscard[count($myDiscard) - DiscardPieces()], "concat", $cardSizeAura, 0, 0, 0, 0, count($myDiscard) / DiscardPieces(), controller: $playerID));
  } else {
    //Empty Discard div
    echo ("<div class='my-discard my-discard-empty' style='padding:" . $cardSizeAura / 2 . "px;'>");
    echo ("<div class='my-discard-empty-label' style='color: " . $bordelessFontColor . ";'>Discard</div>");
  }
  echo ("</div>");

  //Display My Deck
  if (count($myDeck) > 0) {
    $playerDeck = new Deck($playerID);
    if ($turn[0] == "OVER")
      echo ("<div class= 'my-deck my-deck-fill' title='Click to view the cards in your Deck.' style='" . GetZoneRight("DECK") . "; bottom:" . GetZoneBottom("MYDECK") . "' onclick='TogglePopup(\"myDeckPopup\");'>");
    else
      echo ("<div class='my-deck'>");
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
  if (count($lastPlayed) == 0)
    echo Card($MyCardBack, "CardImages", intval($rightSideWidth * 1.3));
  else {
    echo Card($lastPlayed[0], "CardImages", intval($rightSideWidth * 1.3), controller: $lastPlayed[1]);
    if (count($lastPlayed) >= 4) {
      if ($lastPlayed[3] == "FUSED")
        echo ("<img class='fused-card' title='This card was fused.' src='./Images/fuse2.png' />");
    }
  }
  echo ("</div>");

  if ($playerID != 3) {
    echo ("<div id='gamelog'>");
    EchoLog($gameName);
    echo ("</div>");
    echo ("<div id='chatPlaceholder'></div>");
    echo ("</div>");
  }

  echo ("<div id='lastCurrentPlayer' style='display:none;'>" . $currentPlayer . "</div>");
  echo ("<div id='passConfirm' style='display:none;'>" . ($turn[0] == "ARS" && count($myHand) > 0 && !ArsenalFull($playerID) ? "true" : "false") . "</div>");
}

function PlayableCardBorderColor($cardID)
{
  if (HasReprise($cardID) && RepriseActive())
    return 3;
  return 0;
}

function ItemOverlay($item, $isReady, $numUses)
{
  if ($item == "EVR070" && $numUses < 3)
    return 1;
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
    case "C":
    case "W":
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
  for ($i = 0; $i < count($auras); $i += AuraPieces()) {
    if ($auras[$i] == "ENLIGHTEN") {
      if ($count == 0)
        $first = $i;
      ++$count;

      if ($player == $playerID && $first > -1) {
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

function Capitalize($string) {
  return ucfirst(strtolower($string));
}

function GetPhaseHelptext()
{
  global $turn;
  $defaultText = Capitalize(VerbToPlay($turn[0])) . " " . TypeToPlay($turn[0]);
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
    if ($setting == 0)
      echo ("<img " . ProcessInputLink($playerID, ($otherPlayer ? 104 : 103), $MZindex) . " title='Not holding priority' class='priority-gem' style='" . $position . " left:" . $cardWidth / 2 - 13 . "px;' src='./Images/$gem' />");
    else if ($setting == 1)
      echo ("<img " . ProcessInputLink($playerID, ($otherPlayer ? 104 : 103), $MZindex) . " title='Holding priority' class='priority-gem' style='" . $position . " left:" . $cardWidth / 2 - 13 . "px;' src='./Images/$gem' />");
  }
}
