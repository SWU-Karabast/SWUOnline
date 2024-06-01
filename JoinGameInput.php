<?php

include "WriteLog.php";
include "Libraries/HTTPLibraries.php";
include "Libraries/SHMOPLibraries.php";
include "APIKeys/APIKeys.php";
include_once 'includes/functions.inc.php';
include_once 'includes/dbh.inc.php';
include_once 'CoreLogic.php';
include_once 'Libraries/CoreLibraries.php';

include_once 'LZCompressor/LZContext.php';
include_once 'LZCompressor/LZData.php';
include_once 'LZCompressor/LZReverseDictionary.php';
include_once 'LZCompressor/LZString.php';
include_once 'LZCompressor/LZUtil.php';
include_once 'LZCompressor/LZUtil16.php';

use LZCompressor\LZString as LZString;

session_start();
if (!isset($_SESSION["userid"])) {
  if (isset($_COOKIE["rememberMeToken"])) {
    include_once './Assets/patreon-php-master/src/PatreonLibraries.php';
    include_once './Assets/patreon-php-master/src/API.php';
    include_once './Assets/patreon-php-master/src/PatreonDictionary.php';
    loginFromCookie();
  }
}

$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$playerID = intval($_GET["playerID"]);
$deck = TryGet("deck");
$decklink = $_GET["fabdb"];
$decksToTry = TryGet("decksToTry");
$favoriteDeck = TryGet("favoriteDeck", "0");
$favoriteDeckLink = TryGet("favoriteDecks", "0");
$set = TryGet("set");
$matchup = TryGet("matchup", "");
$starterDeck = false;

if ($matchup == "" && GetCachePiece($gameName, $playerID + 6) != "") {
  $_SESSION['error'] = '⚠️ Another player has already joined the game.';
  header("Location: MainMenu.php");
  die();
}

include "HostFiles/Redirector.php";
include "CardDictionary.php";
include "MenuFiles/ParseGamefile.php";
include "MenuFiles/WriteGamefile.php";
if($playerID == 2 && isset($_SESSION["userid"])) {
  $isBlocked = false;
  $blockedPlayers = LoadBlockedPlayers($_SESSION["userid"]);
  for($i=0; $i<count($blockedPlayers); ++$i) {
    if($blockedPlayers[$i] == $p1id) {
      $isBlocked = true;
      break;
    }
  }
  if ($isBlocked) {
    $_SESSION['error'] = '⚠️ Another player has already joined the game.';
    header("Location: MainMenu.php");
    die();
  }

  if ($matchup == "" && GetCachePiece($gameName, $playerID + 6) != "") {
    $_SESSION['error'] = '⚠️ Another player has already joined the game.';
    header("Location: MainMenu.php");
    die();
  }
}

if ($decklink == "" && $deck == "" && $favoriteDeckLink == "0") {
  $starterDeck = true;
  switch($decksToTry) {

    default:
        $deck = "./test.txt";
      break;
  }
}

if ($favoriteDeckLink != "0" && $decklink == "") $decklink = $favoriteDeckLink;

if ($deck == "" && !IsDeckLinkValid($decklink)) {
  echo '<b>' . "⚠️ Deck URL is not valid: " . $decklink . '</b>';
  exit;
}

if ($matchup == "" && $playerID == 2 && $gameStatus >= $MGS_Player2Joined) {
  if ($gameStatus >= $MGS_GameStarted) {
    header("Location: " . $redirectPath . "/NextTurn4.php?gameName=$gameName&playerID=3");
  } else {
    header("Location: " . $redirectPath . "/MainMenu.php");
  }
  WriteGameFile();
  exit;
}

if ($decklink != "") {
  if ($playerID == 1) $p1DeckLink = $decklink;
  else if ($playerID == 2) $p2DeckLink = $decklink;
  $originalLink = $decklink;

  if(str_contains($decklink, "swudb.com/deck")) {
    $decklinkArr = explode("/", $decklink);
    $decklink = "https://swudb.com/deck/view/" . trim($decklinkArr[count($decklinkArr) - 1]) . "?handler=JsonFile";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $decklink);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $apiDeck = curl_exec($curl);
    $apiInfo = curl_getinfo($curl);
    $errorMessage = curl_error($curl);
    curl_close($curl);
    $json = $apiDeck;
    echo($json);
  }
  else $json = $decklink;

  if($json == "") {
    echo "Failed to retrieve deck from API. Check to make sure you have a valid deckbuilder link. If it's a SWUDB link, make sure it's not a private deck.<BR>";
    echo "Your link: " . $originalLink . "<BR>";
    echo "Error Message: " . $errorMessage . "<BR>";
    exit;
  }

  $deckObj = json_decode($json);
  $deckName = $deckObj->metadata->{"name"};
  $leader = UUIDLookup($deckObj->leader->id);
  $character = $leader;//TODO: Change to leader name
  $deckFormat = 1;
  $base = UUIDLookup($deckObj->base->id);
  $deck = $deckObj->deck;
  $cards = "";
  for($i=0; $i<count($deck); ++$i) {
    for($j=0; $j<$deck[$i]->count; ++$j) {
      if($cards != "") $cards .= " ";
      $cards .= UUIDLookup($deck[$i]->id);
    }
  }
  $sideboard = isset($deckObj->sideboard) ? $deckObj->sideboard : [];
  $sideboardCards = "";
  for($i=0; $i<count($sideboard); ++$i) {
    for($j=0; $j<$sideboard[$i]->count; ++$j) {
      if($sideboardCards != "") $sideboardCards .= " ";
      $sideboardCards .= UUIDLookup($sideboard[$i]->id);
    }
  }

  /*
  // if has message forbidden error out.
  if ($apiInfo['http_code'] == 403) {
    $_SESSION['error'] =
      "API FORBIDDEN! Invalid or missing token to access API: " . $apiLink . " The response from the deck hosting service was: " . $apiDeck;
    header("Location: MainMenu.php");
    die();
  }
  if($deckObj == null)
  {
    echo 'Deck object is null. Failed to retrieve deck from API.';
    exit;
  }
  if (isset($deckObj->{'matchups'})) {
    if ($playerID == 1) $p1Matchups = $deckObj->{'matchups'};
    else if ($playerID == 2) $p2Matchups = $deckObj->{'matchups'};
  }
  $deckName = $deckObj->{'name'};
  $deckFormat = (isset($deckObj->{'format'}) ? $deckObj->{'format'} : "");
  $deckCards = "";
  $sideboardCards = "";
  $materialCards = "";
  $totalCards = 0;
  foreach($deckObj->{'cards'}->{'main'} as $key => $value) {
    $cardID = $value->{'uuid'};
    $quantity = $value->{'quantity'};
    for($i=0; $i<$quantity; ++$i)
    {
      if($deckCards != "") $deckCards .= " ";
      $deckCards .= $cardID;
    }
  }

  foreach($deckObj->{'cards'}->{'material'} as $key => $value) {
    $cardID = $value->{'uuid'};
    if($materialCards != "") $materialCards .= " ";
    $materialCards .= $cardID;
  }
  */

  //We have the decklist, now write to file
  $filename = "./Games/" . $gameName . "/p" . $playerID . "Deck.txt";
  $deckFile = fopen($filename, "w");
  fwrite($deckFile, $base . " " . $leader . "\r\n");
  fwrite($deckFile, $cards . "\r\n");
  fwrite($deckFile, $sideboardCards . "\r\n");
  fclose($deckFile);
  copy($filename, "./Games/" . $gameName . "/p" . $playerID . "DeckOrig.txt");

  if ($favoriteDeck == "on" && isset($_SESSION["userid"])) {
    //Save deck
    include_once './includes/functions.inc.php';
    include_once "./includes/dbh.inc.php";
    $saveLink = explode("https://", $originalLink);
    $saveLink = count($saveLink) > 1 ? $saveLink[1] : $originalLink;
    addFavoriteDeck($_SESSION["userid"], $saveLink, $deckName, $character, $deckFormat);
  }
} else {
  $deckFile = $deck;
  copy($deckFile, "./Games/" . $gameName . "/p" . $playerID . "Deck.txt");
  copy($deckFile, "./Games/" . $gameName . "/p" . $playerID . "DeckOrig.txt");
}

if ($matchup == "") {
  if ($playerID == 2) {

    $gameStatus = $MGS_Player2Joined;
    if (file_exists("./Games/" . $gameName . "/gamestate.txt")) unlink("./Games/" . $gameName . "/gamestate.txt");

    $firstPlayerChooser = 1;
    $p1roll = 0;
    $p2roll = 0;
    $tries = 10;
    while ($p1roll == $p2roll && $tries > 0) {
      $p1roll = rand(1, 6) + rand(1, 6);
      $p2roll = rand(1, 6) + rand(1, 6);
      WriteLog("Player 1 rolled $p1roll and Player 2 rolled $p2roll.");
      --$tries;
    }
    $firstPlayerChooser = ($p1roll > $p2roll ? 1 : 2);
    WriteLog("Player $firstPlayerChooser chooses who goes first.");
    $gameStatus = $MGS_ChooseFirstPlayer;
    $joinerIP = $_SERVER['REMOTE_ADDR'];
  }

  if ($playerID == 1) {
    $p1uid = (isset($_SESSION["useruid"]) ? $_SESSION["useruid"] : "Player 1");
    $p1id = (isset($_SESSION["userid"]) ? $_SESSION["userid"] : "");
    $p1IsPatron = (isset($_SESSION["isPatron"]) ? "1" : "");
    $p1ContentCreatorID = (isset($_SESSION["patreonEnum"]) ? $_SESSION["patreonEnum"] : "");
  } else if ($playerID == 2) {
    $p2uid = (isset($_SESSION["useruid"]) ? $_SESSION["useruid"] : "Player 2");
    $p2id = (isset($_SESSION["userid"]) ? $_SESSION["userid"] : "");
    $p2IsPatron = (isset($_SESSION["isPatron"]) ? "1" : "");
    $p2ContentCreatorID = (isset($_SESSION["patreonEnum"]) ? $_SESSION["patreonEnum"] : "");
  }

  if ($playerID == 2) $p2Key = hash("sha256", rand() . rand() . rand());

  WriteGameFile();
  SetCachePiece($gameName, $playerID + 1, strval(round(microtime(true) * 1000)));
  SetCachePiece($gameName, $playerID + 3, "0");
  SetCachePiece($gameName, $playerID + 6, $character);
  SetCachePiece($gameName, 14, $gameStatus);
  GamestateUpdated($gameName);

  //$authKey = ($playerID == 1 ? $p1Key : $p2Key);
  //$_SESSION["authKey"] = $authKey;
  $domain = (!empty(getenv("DOMAIN")) ? getenv("DOMAIN") : "karabast.net");
  if ($playerID == 1) {
    $_SESSION["p1AuthKey"] = $p1Key;
    setcookie("lastAuthKey", $p1Key, time() + 86400, "/", $domain);
  } else if ($playerID == 2) {
    $_SESSION["p2AuthKey"] = $p2Key;
    setcookie("lastAuthKey", $p2Key, time() + 86400, "/", $domain);
  }
}

session_write_close();
header("Location: " . $redirectPath . "/GameLobby.php?gameName=$gameName&playerID=$playerID");

function GetAltCardID($cardID)
{
  switch ($cardID) {
    case "OXO001": return "WTR155";
    case "OXO002": return "WTR156";
    case "OXO003": return "WTR157";
    case "OXO004": return "WTR158";
    case "BOL002": return "MON405";
    case "BOL006": return "MON400";
    case "CHN002": return "MON407";
    case "CHN006": return "MON401";
    case "LEV002": return "MON406";
    case "LEV005": return "MON400";
    case "PSM002": return "MON404";
    case "PSM007": return "MON402";
    case "FAB015": return "WTR191";
    case "FAB016": return "WTR162";
    case "FAB023": return "MON135";
    case "FAB024": return "ARC200";
    case "FAB030": return "DYN030";
    case "FAB057": return "EVR063";
    case "DVR026": return "WTR182";
    case "RVD008": return "WTR006";
    case "UPR209": return "WTR191";
    case "UPR210": return "WTR192";
    case "UPR211": return "WTR193";
    case "HER075": return "DYN025";
    case "LGS112": return "DYN070";
    case "LGS116": return "DYN200";
    case "LGS117": return "DYN201";
    case "LGS118": return "DYN202";
    case "ARC218":
    case "UPR224":
    case "MON306":
    case "ELE237": //Cracked Baubles
      return "WTR224";
    case "DYN238": return "MON401";
    case "RVD004": return "DVR004";
    case "OUT077": return "WTR098";
    case "OUT078": return "WTR099";
    case "OUT079": return "WTR100";
    case "OUT083": return "WTR107";
    case "OUT084": return "WTR108";
    case "OUT085": return "WTR109";
    case "OUT086": return "EVR047";
    case "OUT087": return "EVR048";
    case "OUT088": return "EVR049";
    case "OUT213": return "ARC191";
    case "OUT214": return "ARC192";
    case "OUT215": return "ARC193";
    case "OUT216": return "MON251";
    case "OUT217": return "MON252";
    case "OUT218": return "MON253";
    case "OUT222": return "ARC203";
    case "OUT223": return "ARC204";
    case "OUT224": return "ARC205";
    case "WIN022": return "OUT091";
  }
  return $cardID;
}

function IsBanned($cardID, $format)
{
  switch ($format) {
    case "blitz":
    case "compblitz":
      switch ($cardID) {
        case "WTR152":
        case "ARC076": case "ARC077": //Viserai
        case "ARC129": case "ARC130": case "ARC131":
        case "ELE006":
        case "ELE186": case "ELE187": case "ELE188":
        case "ELE223":
        case "CRU141":
        case "CRU174": case "CRU175": case "CRU176":
        case "MON239":
        case "MON183": case "MON184": case "MON185":
        case "EVR037":
        case "EVR123": // Aether Wildfire
        case "UPR103": case "EVR120": case "ELE002": case "ELE003": case "EVR121":
          return true;
        default:
          return false;
      }
      break;
    case "cc":
    case "compcc":
      switch ($cardID) {
        case "WTR164": case "WTR165": case "WTR166": //Drone of Brutality
        case "ARC170": case "ARC171": case "ARC172": //Plunder Run
        case "CRU141":
        case "MON001": //Prism
        case "MON003": //Luminaris
        case "MON153":
        case "MON155":
        case "MON239":
        case "MON266": case "MON267": case "MON268": //Belittle
        case "ELE003":
        case "ELE006":
        case "ELE114":
        case "ELE172":
        case "ELE186": case "ELE187": case "ELE188":
        case "ELE223":
        case "EVR017":
        case "UPR139":
          return true;
        default:
          return false;
      }
      break;
    case "commoner":
      switch ($cardID) {
        case "ELE186": //Ball Lightning
        case "ELE187":
        case "ELE188":
        case "MON266": //Belittle
        case "MON267":
        case "MON268":
          return true;
        default:
          return false;
      }
      break;
    default:
      return false;
  }
}
