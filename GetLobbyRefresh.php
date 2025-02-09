<?php

include "CardDictionary.php";
include 'Libraries/HTTPLibraries.php';
include_once "Libraries/PlayerSettings.php";
include_once "Assets/patreon-php-master/src/PatreonDictionary.php";

//We should always have a player ID as a URL parameter
$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$playerID = TryGet("playerID", 3);
$lastUpdate = TryGet("lastUpdate", 0);
$authKey = TryGet("authKey", 0);

if(!file_exists("./Games/" . $gameName . "/")) { header('HTTP/1.0 403 Forbidden'); exit; }

if($lastUpdate == "NaN") $lastUpdate = 0;
if ($lastUpdate > 10000000) $lastUpdate = 0;

include "HostFiles/Redirector.php";
include "Libraries/UILibraries2.php";
include "Libraries/SHMOPLibraries.php";
include_once "WriteLog.php";

$data = array();
$currentTime = round(microtime(true) * 1000);
SetCachePiece($gameName, $playerID + 1, $currentTime);

$isMobile = IsMobile();

$count = 0;
$cacheVal = GetCachePiece($gameName, 1);
if ($cacheVal > 10000000) {
  SetCachePiece($gameName, 1, 1);
  $lastUpdate = 0;
}
$kickPlayerTwo = false;
while ($lastUpdate != 0 && $cacheVal <= $lastUpdate) {
  usleep(100000); //100 milliseconds
  $currentTime = round(microtime(true) * 1000);
  $cacheVal = GetCachePiece($gameName, 1);
  SetCachePiece($gameName, $playerID + 1, $currentTime);
  ++$count;
  if ($count == 100) break;
  $otherP = ($playerID == 1 ? 2 : 1);
  $oppLastTime = GetCachePiece($gameName, $otherP + 1);
  $oppStatus = strval(GetCachePiece($gameName, $otherP + 3));

  if ($oppStatus != "-1" && $oppLastTime != "") {
    if (($currentTime - $oppLastTime) > 8000 && $oppStatus == "0") {
      include "MenuFiles/ParseGamefile.php";
      WriteLog("$otherPlayerName has disconnected.");
      GamestateUpdated($gameName);
      SetCachePiece($gameName, $otherP + 3, "-1");
      if($otherP == 2) SetCachePiece($gameName, $otherP + 6, "");
      $kickPlayerTwo = true;
    }
  }
}

include "MenuFiles/ParseGamefile.php";
include "MenuFiles/WriteGamefile.php";

$targetAuth = ($playerID == 1 ? $p1Key : $p2Key);
if ($authKey != $targetAuth) {
  echo ("Invalid Auth Key");
  exit;
}


if ($kickPlayerTwo) {

  $numP2Disconnects = IncrementCachePiece($gameName, 11);
  if($numP2Disconnects >= 3)
  {
    WriteLog("This lobby is now hidden due to inactivity. Type in chat to unhide the lobby.");
  }
  if (file_exists("./Games/" . $gameName . "/p2Deck.txt")) unlink("./Games/" . $gameName . "/p2Deck.txt");
  if (file_exists("./Games/" . $gameName . "/p2DeckOrig.txt")) unlink("./Games/" . $gameName . "/p2DeckOrig.txt");
  $gameStatus = $MGS_Initial;
  SetCachePiece($gameName, 14, $gameStatus);
  $p2Data = [];
  WriteGameFile();
}

if ($lastUpdate != 0 && $cacheVal < $lastUpdate) {
  $data["timestamp"] = GetCachePiece($gameName, 1);
  exit;
} else if ($gameStatus == $MGS_GameStarted) {
  echo ("1");
  exit;
} else {
  $data["timestamp"] = GetCachePiece($gameName, 1);

  $setupContent = "";
  $showSubmit = false;
  if ($playerID == 1 && $gameStatus < $MGS_Player2Joined) {
    if($visibility == "private") {
      if($p1id == "") {
        $setupContent .= "<p>&#10071;This is a private lobby. You need to log in for matchmaking.</p>";
      }
      else {
        $setupContent .= "<p>&#10071;This is a private lobby. You will need to invite an opponent.</p>";
      }
    }

    $setupContent .= "<p>Waiting for another player to join.</p>";
    $setupContent .= "<div class='invite-link-wrapper'>";
    $setupContent .= "<input class='GameLobby_Input invite-link' onclick='copyText()' type='text' id='gameLink' value='" . $redirectPath . "/JoinGame.php?gameName=$gameName&playerID=2'>";
    $setupContent .= "<button class='GameLobby_Button' onclick='copyText()'>Copy Invite Link</button>";
    $setupContent .= "</div>";
  } else if ($gameStatus == $MGS_ChooseFirstPlayer) {
    if ($playerID == $firstPlayerChooser) {
      $setupContent .= "<p>You won the initiative choice.</p>";
      $setupContent .= "<input class='GameLobby_Button' type='button' name='action' value='Go First' onclick='SubmitFirstPlayer(1)' style='margin-right:20px; text-align:center;'>";
      $setupContent .= "<input class='GameLobby_Button' type='button' name='action' value='Go Second' onclick='SubmitFirstPlayer(2)' style='text-align:center;'>";
    } else {
      $setupContent .= "<p>Waiting for other player to choose who goes first.</p>";
    }
  } else if ($gameStatus > $MGS_ChooseFirstPlayer && ($playerID == 2 || $p1SideboardSubmitted != "1") && ($playerID == 1 || $p2SideboardSubmitted != "1")) {
    $showSubmit = true;
  }
  $data["setupContent"] = $setupContent;
  $data["showSubmit"] = $showSubmit;

  // Chat Log
  $data["logContent"] = JSONLog($gameName);

  // Player Joined Audio
  $data["playerJoinAudio"] = $playerID == 1 && $gameStatus == $MGS_ChooseFirstPlayer;

  // Other player info
  $theirInfo = "";
  $otherHero = "CardBack";
  $otherBase = "CardBack";
  $otherPlayer = $playerID == 1 ? 2 : 1;
  $deckFile = "./Games/" . $gameName . "/p" . $otherPlayer . "Deck.txt";
  if (file_exists($deckFile)) {
    $handler = fopen($deckFile, "r");
    $otherCharacter = GetArray($handler);
    $otherHero = $otherCharacter[1];
    $otherBase = $otherCharacter[0];
    fclose($handler);
  }
  $contentCreator = ContentCreators::tryFrom(($playerID == 1 ? $p2ContentCreatorID : $p1ContentCreatorID));
  $nameColor = ($contentCreator != null ? $contentCreator->NameColor() : "");
  $theirDisplayName = "<span style='color:$nameColor'>$otherPlayerName</span>";
  $overlayURL = ($contentCreator != null ? $contentCreator->HeroOverlayURL($otherHero) : "");
  $channelLink = ($contentCreator != null ? $contentCreator->ChannelLink() : "");

  $theirInfo .= "<h3>$theirDisplayName</h3>";
  $theirInfo .= Card($otherHero, "CardImages", ($isMobile ? 100 : 250), 0, 1, 0, 0, 0, "", "", true);
  $theirInfo .= Card($otherBase, "CardImages", ($isMobile ? 100 : 250), 0, 1, 0, 0, 0, "", "", true);
  if($channelLink != "") $theirInfo .= "<a href='" . $channelLink . "' target='_blank'>";
  if($overlayURL != "") $theirInfo .= "<img title='Portrait' style='position:absolute; z-index:1001; top: 87px; left: 18px; cursor:pointer; height:" . ($isMobile ? 100 : 250) . "; width:" . ($isMobile ? 100 : 250) . ";' src='" . $overlayURL . "' />";
  if($channelLink != "") $theirInfo .= "</a>";
  $theirInfo .= "</div>";
  $data["theirInfo"] = $theirInfo;

  // $icon = "ready.png";
  // if ($gameStatus == $MGS_ChooseFirstPlayer) $icon = $playerID == $firstPlayerChooser ? "ready.png" : "notReady.png";
  // else if ($playerID == 1 && $gameStatus < $MGS_ReadyToStart) $icon = "notReady.png";
  // else if ($playerID == 2 && $gameStatus >= $MGS_ReadyToStart) $icon = "notReady.png";
  // echo ("<div id='iconHolder' style='display:none;'>" . $icon . "</div>");

  echo json_encode($data);
}
