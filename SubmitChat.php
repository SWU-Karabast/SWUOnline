<?php
include "Libraries/Constants.php";
include "Libraries/HTTPLibraries.php";
include "Libraries/SHMOPLibraries.php";
include "Libraries/NetworkingLibraries.php";
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
$contributors = array("OotTheMonk", "love", "ninin", "Brubraz", "Mobyus1");

//its sort of sloppy, but it this will fail if you're in the contributors array because we want to give you the contributor icon, not the patron icon.
//TODO: see about content creator icons for Patreon
//if (isset($_SESSION["isPatron"]) && isset($_SESSION['useruid']) && !in_array($_SESSION['useruid'], $contributors)) $displayName = "<img style='margin-bottom:-4px; margin-right:-6px; height:18px;' src='./Images/greenPhaseMarker.png' /> " . $displayName;

//This is the code for Contributor's icon.
if (isset($_SESSION['useruid']) && in_array($_SESSION['useruid'], $contributors)) $displayName = "<img title='Contributor' style='margin-bottom:-4px; margin-right:-4px; height:18px;' src='./Images/beskar-tiny.png' /> " . $displayName;
//profanity filter
$filteredChatText = explode(" ", $chatText);
$meanPhrases = [
  "kill yourself", "die in a fire", "can you just die irl", "hit by a bus", "fake and gay", "ass monkey", "carpet muncher", "f u c k", "f u c k e r", "go to hell",
  "motha fucker, motha fuker, motha fukkah, motha fukker, mother fucker, mother fukah, mother fuker, mother fukkah, mother fukker, mutha fucker, mutha fukah, mutha fuker, mutha fukkah, mutha fukker",
  "kys", "eat a gun"
];

for($i=0; $i<count($filteredChatText);++$i) {
  $chatWord = $filteredChatText[$i];
  if(in_array(strtolower($chatWord), $PROFANITY_FILTER)) {
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
if(str_contains($filteredChatText, "*****")) {
  $sugarSpiceAndEverythingNice = GetKindQuote();
  WriteLog(ArenabotSpan() . "As a reminder, please be kind to each other and refrain from using hateful language. This is just a game, and there is no reason to display this level of toxic behavior.");
  WriteLog($sugarSpiceAndEverythingNice);
}

GamestateUpdated($gameName);
if ($playerID == 1) SetCachePiece($gameName, 11, 0);

if(GetCachePiece($gameName, $playerID + 14) > 0) {
  exit("refresh");
}

function GetKindQuote() {
  $quotes = [
    'Kind peoople are my kind of people.',
    'Be the change you wish to see in the world.',
    'A single act of kindness throws out roots in all directions.',
    'Kindness is the language which the deaf can hear and the blind can see.',
    'No act of kindness, no matter how small, is ever wasted.',
    'What wisdom can you find that is greater than kindness?',
    'Kindness is free to give but priceless to receive.',
    'The best way to find yourself is to lose yourself in the service of others.',
    'We rise by lifting others.',
    'The smallest act of kindness is worth more than the grandest intention.',
    'Your greatness is not what you have, but what you give.',
    'Every good deed brings light to the world.',
    'Be kind whenever possible. It is always possible.',
    'Wherever there is a human being, there is an opportunity for kindness.',
    'The most beautiful things in life are not things. They are people and places and memories and smiles.',
    'In a world where you can be anything, be kind.',
    'Sometimes it takes only one act of kindness to change someone\'s life.',
    'A kind gesture can reach a wound that only compassion can heal.',
    'Life is mostly froth and bubble, but two things stand like stone: kindness in another\'s trouble, courage in your own.',
    'When we give cheerfully and accept gratefully, everyone is blessed.',
    'The most important trip you may take in life is meeting people halfway.',
    'Never look down on anybody unless you\'re helping them up.',
    'Let us be kind to one another, for most of us are fighting a hard battle.',
    'We can all make a difference in the lives of others in both big and small ways.',
    'A candle loses nothing by lighting another candle.',
    'Kindness is the way of the Jedi.',
    'Do, or do not, but always choose kindness.',
    'The Force flows through all living things, connecting us in compassion.',
    'Like a lightsaber, kindness can illuminate the darkest paths.',
    'Even a Wookiee knows the value of a gentle heart.',
    'This is the way - to treat others with respect and dignity.',
    'Strong you may be with the Force, but stronger still with kindness.',
    'Not all victories come from the battlefield - some come from acts of compassion.',
    'The greatest power in the galaxy is not the Death Star, but the power to lift others up.',
    'Help others you must, for that is the way of the Jedi.',
    'Through kindness, we bring balance to the Force.',
    'Even the smallest droid can show the greatest compassion.',
    'Like the twin suns of Tatooine, let your kindness shine bright.',
    'The Force binds us all - through kindness we honor that connection.',
    'A true Rebel fights not with hatred, but with hope and compassion.',
    'Trust in the Force, and in the kindness of others.',
    'Luminous beings are we - let your inner light guide others.',
    'The dark side clouds everything - kindness brings clarity.',
    'Even in the outer rim, a kind word can change a life.',
    'Much to learn you still have about the power of compassion.',
    'Like the Jedi Council, seek wisdom in showing kindness to others.',
    'Size matters not - small acts of kindness can change the galaxy.',
    'Through compassion, victory we find.',
    'A Jedi uses the Force for knowledge and defense, never for attack.',
    'Let your actions bring hope to the galaxy, like the Rebellion.',
    'The Force will be with you, always - as will the power of kindness.',
    'From Coruscant to Endor, kindness knows no boundaries.'
  ];

  return $quotes[array_rand($quotes)];
}