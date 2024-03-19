<?php

//include "ParseGamestate.php";
//include "WriteLog.php";

array_push($layerPriority, ShouldHoldPriority(1));
array_push($layerPriority, ShouldHoldPriority(2));

$mainPlayer = $firstPlayer;
$currentPlayer = $firstPlayer;
$otherPlayer = ($currentPlayer == 1 ? 2 : 1);
StatsStartTurn();

$MakeStartTurnBackup = false;
$MakeStartGameBackup = false;

$p2Material = &GetMaterial(2);
if(count($p2Material) == 1 && $p2Material[0] == "DUMMY")
{
  AddCharacter("DUMMY", 2);
}

if($p2Char[0] == "DUMMY") {
  SetCachePiece($gameName, 3, "99999999999999");
}

//Start of game effects go here


AddDecisionQueue("SHUFFLEDECK", 1, "SKIPSEED");
AddDecisionQueue("SHUFFLEDECK", 2, "SKIPSEED");
AddDecisionQueue("STARTGAME", $mainPlayer, "-");
AddDecisionQueue("STARTTURNABILITIES", $mainPlayer, "-");

ProcessDecisionQueue();

DoGamestateUpdate();
include "WriteGamestate.php";

if($MakeStartTurnBackup) MakeStartTurnBackup();
if($MakeStartGameBackup) MakeGamestateBackup("origGamestate.txt");

?>

Something is wrong with the XAMPP installation :-(
