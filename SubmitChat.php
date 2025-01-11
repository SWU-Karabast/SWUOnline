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
$filteredChatText = $chatText;
$naughtyWords = ["shit", "piss", "fuck", "cunt", "cock", "cocksucker", "motherfucker", "tit", "tits", "fart", "turd", "twat", "bitch", "retard", "fag", "faggot", "kill yourself", "die in a fire", "skank", "hoe", "whore", "sh!t", "p!ss", "c0ck", "c0cksucker", "f@g", "f@ggot", ];
for($i=0; $i<count($naughtyWords); ++$i) {
    $regexBuilder = "/";
    for($j=0; $j<count(str_split($naughtyWords[$i])); ++$j) {
      $letter = $naughtyWords[$i][$j];
      $upperLetter = strtoupper($letter);
      $twoLetters = $upperLetter . $letter;
      $regexBuilder = $regexBuilder . "[" . $twoLetters . "](\s|\.{1,2})?";
    }
    $regexBuilder = $regexBuilder . "/";

    $filteredChatText = preg_replace($regexBuilder, "****", $filteredChatText);
}

if (GetCachePiece($gameName, 11) >= 3) {
  WriteLog("The lobby is reactivated");
}
WriteLog("<span class='player$playerID-label bold'>$displayName</span>: $filteredChatText");

GamestateUpdated($gameName);
if ($playerID == 1) SetCachePiece($gameName, 11, 0);

if(GetCachePiece($gameName, $playerID + 14) > 0) {
  exit("refresh");
}
