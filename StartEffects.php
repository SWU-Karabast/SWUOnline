<?php

//include "ParseGamestate.php";
//include "WriteLog.php";

array_push($layerPriority, ShouldHoldPriority(1), ShouldHoldPriority(2));

$mainPlayer = $firstPlayer;
$currentPlayer = $firstPlayer;
$otherPlayer = ($currentPlayer == 1 ? 2 : 1);
StatsStartTurn();

$MakeStartTurnBackup = false;
$MakeStartGameBackup = false;

$p1Material = &GetMaterial(1);
AddCharacter($p1Material[0], 1);
AddCharacter($p1Material[MaterialPieces()], 1);

$p2Material = &GetMaterial(2);
if(count($p2Material) == 1 && $p2Material[0] == "DUMMY")
{
  AddCharacter("DUMMY", 2);
} else {
  AddCharacter($p2Material[0], 2);
  AddCharacter($p2Material[MaterialPieces()], 2);
}

/*
AI TODO: Set this if it's an AI
if($p2CharEquip[0] == "DUMMY") {
  SetCachePiece($gameName, 3, "99999999999999");
}
*/

//Start of game effects go here
$initiativePlayer = $firstPlayer;
$intiativeTaken = 0;

for ($i = 0; $i < 3; $i++) {
  AddDecisionQueue("SHUFFLEDECK", 1, "SKIPSEED");
  AddDecisionQueue("SHUFFLEDECK", 2, "SKIPSEED");
}
AddDecisionQueue("STARTGAME", $initiativePlayer, "-");

ProcessDecisionQueue();

DoGamestateUpdate();
include "WriteGamestate.php";

if($MakeStartTurnBackup) MakeStartTurnBackup();
if($MakeStartGameBackup) MakeGamestateBackup("origGamestate.txt");

?>

Something is wrong with the XAMPP installation :-(
