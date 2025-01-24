<?php

include_once "WriteLog.php";
include "Libraries/HTTPLibraries.php";
include "Libraries/SHMOPLibraries.php";

$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$playerID = $_GET["playerID"];
$action = $_GET["action"];
$authKey = $_GET["authKey"];

include "HostFiles/Redirector.php";
include "MenuFiles/ParseGamefile.php";
include "MenuFiles/WriteGamefile.php";

$targetAuth = ($playerID == 1 ? $p1Key : $p2Key);
if ($authKey != $targetAuth) {
  echo ("Invalid Auth Key");
  exit;
}

$firstPlayer = $action == "Go First" ? $playerName : $otherPlayerName;
WriteLog(FmtPlayer($firstPlayer, $playerID) . " will go first.");
$gameStatus = $MGS_P2Sideboard;
SetCachePiece($gameName, 14, $gameStatus);
GamestateUpdated($gameName);

WriteGameFile();

header("Location: " . $redirectPath . "/GameLobby.php?gameName=$gameName&playerID=$playerID");
