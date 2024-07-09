<?php

error_reporting(E_ALL);
ob_start();

include "WriteLog.php";
include "GameLogic.php";
include "GameTerms.php";
include "HostFiles/Redirector.php";
include "Libraries/SHMOPLibraries.php";
include "Libraries/StatFunctions.php";
include "Libraries/UILibraries.php";
include "Libraries/PlayerSettings.php";
include "Libraries/NetworkingLibraries.php";
include "AI/CombatDummy.php";
include "AI/EncounterAI.php";
include "AI/PlayerMacros.php";
include "Libraries/HTTPLibraries.php";
require_once("Libraries/CoreLibraries.php");
include_once "./includes/dbh.inc.php";
include_once "./includes/functions.inc.php";
include_once "APIKeys/APIKeys.php";

//We should always have a player ID as a URL parameter
$gameName = TryGET("gameName", "");
if ($gameName == "" || !IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$playerID = $_GET["playerID"];
$authKey = $_GET["authKey"];

//We should also have some information on the type of command
$inputMode = $_GET["mode"];
$mode = $inputMode;
$buttonInput = $_GET["buttonInput"] ?? ""; //The player that is the target of the command - e.g. for changing health total
$cardID = $_GET["cardID"] ?? "";
$chkCount = $_GET["chkCount"] ?? 0;
$chkInput = [];
for ($i = 0; $i < $chkCount; ++$i) {
  $chk = $_GET[("chk" . $i)] ?? "";
  if ($chk != "") $chkInput[] = $chk;
}
$inputText = $_GET["inputText"] ?? "";

SetHeaders();

$numPass = 0;
if(IsReplay() && $mode == 99)
{
  $filename = "./Games/" . $gameName . "/replayCommands.txt";
  $file = file($filename);
  $line = $file[0];
  unset($file[0]);
  $params = explode(" ", $line);
  $playerID = $params[0];
  $mode = $params[1];
  $buttonInput = $params[2];
  $cardID = $params[3];
  $chkCount = $params[4];
  $chkInput = explode("|", $params[5]);
  for($i=0; $i<count($chkInput); ++$i)
  {
    $chkInput[$i] = trim($chkInput[$i]);
  }
  //Automate extra passes
  for($i=1; $i<count($file); ++$i)
  {
    $line = $file[$i];
    $params = explode(" ", $line);
    if(intval($mode) != 99 || intval($params[1]) != 99) break;
    ++$numPass;
    unset($file[$i]);
  }
  file_put_contents($filename, $file);
}

//First we need to parse the game state from the file
include "ParseGamestate.php";

$otherPlayer = $currentPlayer == 1 ? 2 : 1;
$skipWriteGamestate = false;
$mainPlayerGamestateStillBuilt = 0;
$makeCheckpoint = 0;
$makeBlockBackup = 0;
$MakeStartTurnBackup = false;
$MakeStartGameBackup = false;
$targetAuth = ($playerID == 1 ? $p1Key : $p2Key);
$conceded = false;
$randomSeeded = false;

if(!IsReplay()) {
  if (($playerID == 1 || $playerID == 2) && $authKey == "") {
    if (isset($_COOKIE["lastAuthKey"])) $authKey = $_COOKIE["lastAuthKey"];
  }
  if ($playerID != 3 && $authKey != $targetAuth) { echo("Invalid auth key"); exit; }
  if ($playerID == 3 && !IsModeAllowedForSpectators($mode)) ExitProcessInput();
  if (!IsModeAsync($mode) && $currentPlayer != $playerID) {
    $currentTime = round(microtime(true) * 1000);
    SetCachePiece($gameName, 2, $currentTime);
    SetCachePiece($gameName, 3, $currentTime);
    ExitProcessInput();
  }
}

$afterResolveEffects = [];

$animations = [];
$events = [];//Clear events each time so it's only updated ones that get sent

if ((IsPatron(1) || IsPatron(2)) && !IsReplay()) {
  $commandFile = fopen("./Games/" . $gameName . "/commandfile.txt", "a");
  fwrite($commandFile, $playerID . " " . $mode . " " . $buttonInput . " " . $cardID . " " . $chkCount . " " . implode("|", $chkInput) . "\r\n");
  fclose($commandFile);
}

if($initiativeTaken > 2 && $mode != 99 && $mode != 34 && !IsModeAsync($mode)) $initiativeTaken = 0;

//Now we can process the command
ProcessInput($playerID, $mode, $buttonInput, $cardID, $chkCount, $chkInput, false, $inputText);

ProcessMacros();
if ($inGameStatus == $GameStatus_Rematch) {
  $origDeck = "./Games/" . $gameName . "/p1DeckOrig.txt";
  if (file_exists($origDeck)) copy($origDeck, "./Games/" . $gameName . "/p1Deck.txt");
  $origDeck = "./Games/" . $gameName . "/p2DeckOrig.txt";
  if (file_exists($origDeck)) copy($origDeck, "./Games/" . $gameName . "/p2Deck.txt");
  include "MenuFiles/ParseGamefile.php";
  include "MenuFiles/WriteGamefile.php";
  $gameStatus = (IsPlayerAI(2) ? $MGS_ReadyToStart : $MGS_ChooseFirstPlayer);
  SetCachePiece($gameName, 14, $gameStatus);
  $firstPlayer = 1;
  $firstPlayerChooser = ($winner == 1 ? 2 : 1);
  $p1SideboardSubmitted = "0";
  $p2SideboardSubmitted = (IsPlayerAI(2) ? "1" : "0");
  WriteLog("Player $firstPlayerChooser lost and will choose first player for the rematch.");
  WriteGameFile();
  $turn[0] = "REMATCH";
  include "WriteGamestate.php";
  $currentTime = round(microtime(true) * 1000);
  SetCachePiece($gameName, 2, $currentTime);
  SetCachePiece($gameName, 3, $currentTime);
  GamestateUpdated($gameName);
  exit;
} else if ($winner != 0 && $turn[0] != "YESNO") {
  $inGameStatus = $GameStatus_Over;
  $turn[0] = "OVER";
  $currentPlayer = 1;
}

CacheCombatResult();
CombatDummyAI(); //Only does anything if applicable
//EncounterAI();

if (!IsGameOver()) {
  if ($playerID == 1) $p1TotalTime += time() - intval($lastUpdateTime);
  else if ($playerID == 2) $p2TotalTime += time() - intval($lastUpdateTime);
  $lastUpdateTime = time();
}

//Now write out the game state
if (!$skipWriteGamestate) {
  //if($mainPlayerGamestateStillBuilt) UpdateMainPlayerGamestate();
  //else UpdateGameState(1);
  if(!IsModeAsync($mode))
  {
    if(GetCachePiece($gameName, 12) == "1") WriteLog("Current player is active again.");
    SetCachePiece($gameName, 12, "0");
    $currentPlayerActivity = 0;
  }
  DoGamestateUpdate();
  include "WriteGamestate.php";
}

if ($makeCheckpoint) MakeGamestateBackup();
if ($makeBlockBackup) MakeGamestateBackup("preBlockBackup.txt");
if ($MakeStartTurnBackup) MakeStartTurnBackup();
if ($MakeStartGameBackup) MakeGamestateBackup("origGamestate.txt");

GamestateUpdated($gameName);

ExitProcessInput();
