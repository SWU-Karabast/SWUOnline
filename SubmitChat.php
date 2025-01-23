<?php

include "Libraries/HTTPLibraries.php";
include "Libraries/SHMOPLibraries.php";
include_once "WriteLog.php";

SetHeaders();

$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$playerID = $_GET["playerID"];
$chatText = htmlspecialchars($_GET["chatText"]);
$authKey = $_GET["authKey"];

session_start();

if ($authKey == "") $authKey = $_COOKIE["lastAuthKey"];

$targetAuthKey = "";
if($playerID == 1 && isset($_SESSION["p1AuthKey"])) $targetAuthKey = $_SESSION["p1AuthKey"];
else if($playerID == 2 && isset($_SESSION["p2AuthKey"])) $targetAuthKey = $_SESSION["p2AuthKey"];
if($targetAuthKey != "" && $authKey != $targetAuthKey) exit;

$uid = "-";
if (isset($_SESSION['useruid'])) $uid = $_SESSION['useruid'];
$displayName = ($uid != "-" ? $uid : "Player " . $playerID);

//array for contributors
$contributors = array("sugitime", "OotTheMonk", "Launch", "LaustinSpayce", "Star_Seraph", "Tower", "Etasus", "scary987", "Celenar");

//its sort of sloppy, but it this will fail if you're in the contributors array because we want to give you the contributor icon, not the patron icon.
if (isset($_SESSION["isPatron"]) && isset($_SESSION['useruid']) && !in_array($_SESSION['useruid'], $contributors)) $displayName = "<img title='Patron' style='margin-bottom:-2px; margin-right:-4px; height:18px;' src='./images/patronHeart.webp' /> " . $displayName;

//This is the code for Contributor's icon.
if (isset($_SESSION['useruid']) && in_array($_SESSION['useruid'], $contributors)) $displayName = "<img title='Contributor' style='margin-bottom:-2px; margin-right:-4px; height:18px;' src='./images/copper.webp' /> " . $displayName;
//profanity filter
$filteredChatText = explode(" ", $chatText);
$naughtyWords = ["shit", "piss", "fuck", "cunt", "cock", "cocksucker", "motherfucker", "tit", "tits", "fart", "turd", "twat", "bitch", "retard", "fag", "faggot", "skank", "hoe", "whore", "sh!t", "p!ss", "c0ck", "c0cksucker", "f@g", "f@ggot", "pussy", "dildo", "ass", "asshole", "dick", "dicks", ];
$meanPhrases = ["kill yourself", "die in a fire", "can you just die irl", ];

//for each word in filterChatText, if they equal any naughty words, then replace with "*****"
for($i=0; $i<count($filteredChatText);++$i) {
  $chatWord = $filteredChatText[$i];
  if(in_array(strtolower($chatWord), $naughtyWords)) {
    $filteredChatText[$i] = "*****";
  }
}

$filteredChatText = implode(" ", $filteredChatText);

for($i=0;$i<count($meanPhrases);++$i) {
  if(str_contains($filteredChatText, $meanPhrases[$i])) {
    $filteredChatText = str_replace($meanPhrases[$i], "*****", $filteredChatText);
  }
}

if (GetCachePiece($gameName, 11) >= 3) {
  WriteLog("The lobby is reactivated");
}
WriteLog("<span class='p$playerID-label bold'>$displayName</span>: $filteredChatText");

GamestateUpdated($gameName);
if ($playerID == 1) SetCachePiece($gameName, 11, 0);

if(GetCachePiece($gameName, $playerID + 14) > 0) {
  exit("refresh");
}
