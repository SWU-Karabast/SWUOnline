<?php

include "Libraries/HTTPLibraries.php";
include "Libraries/SHMOPLibraries.php";
include "APIKeys/APIKeys.php";
include_once 'includes/functions.inc.php';
include_once 'includes/dbh.inc.php';
include_once 'CoreLogic.php';
include_once 'Libraries/CoreLibraries.php';
include_once "WriteLog.php";

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

$usesUuid = false;

if ($decklink != "") {
  if ($playerID == 1) $p1DeckLink = $decklink;
  else if ($playerID == 2) $p2DeckLink = $decklink;
  $originalLink = $decklink;

  if(str_contains($decklink, "swustats.net")) {
    $decklinkArr = explode("gameName=", $decklink);
    if(count($decklinkArr) > 1) {
      $deckLinkArr = explode("&", $decklinkArr[1]);
      $deckID = $deckLinkArr[0];
      $decklink = "https://swustats.net/TCGEngine/APIs/LoadDeck.php?deckID=" . $deckID . "&format=json";
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_URL, $decklink);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
      $apiDeck = curl_exec($curl);
      $apiInfo = curl_getinfo($curl);
      $errorMessage = curl_error($curl);
      curl_close($curl);
      $json = $apiDeck;
      echo($json);
      $usesUuid = true;
    }
  }
  else if(str_contains($decklink, "swudb.com/deck")) {
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
  else if(str_contains($decklink, "sw-unlimited-db.com/decks")) {
    $decklinkArr = explode("/", $decklink);
	  $deckId = trim($decklinkArr[count($decklinkArr) - 1]);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://sw-unlimited-db.com/umbraco/api/deckapi/get?id=" . $deckId);
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
    echo "API link: " . $decklink . "<BR>";
    echo "Error Message: " . $errorMessage . "<BR>";
    exit;
  }

  $deckObj = json_decode($json);
  $deckName = $deckObj->metadata->{"name"};
  $leader = !$usesUuid ? UUIDLookup($deckObj->leader->id) : $deckObj->leader->id;
  $character = $leader;//TODO: Change to leader name
  $deckFormat = 1;
  $base = !$usesUuid ? UUIDLookup($deckObj->base->id) : $deckObj->base->id;
  $deck = $deckObj->deck;
  $cards = "";
  $bannedSet = "JTL";
  $hasBannedCard = false;
  for($i=0; $i<count($deck); ++$i) {
    if($usesUuid) $deck[$i]->id = CardIDLookup($deck[$i]->id);
    $deck[$i]->id = CardIDOverride($deck[$i]->id);
    $cardID = UUIDLookup($deck[$i]->id);
    $cardID = CardUUIDOverride($cardID);
    if(CardSet($cardID) == $bannedSet) {
      $hasBannedCard = true;
    }
    for($j=0; $j<$deck[$i]->count; ++$j) {
      if($cards != "") $cards .= " ";
      $cards .= $cardID;
    }
  }
  $sideboard = $deckObj->sideboard ?? [];
  $sideboardCards = "";
  for($i=0; $i<count($sideboard); ++$i) {
    if($usesUuid) $sideboard[$i]->id = CardIDLookup($sideboard[$i]->id);
    $sideboard[$i]->id = CardIDOverride($sideboard[$i]->id);
    $cardID = CardUUIDOverride(UUIDLookup($sideboard[$i]->id));
    if(CardSet($cardID) == $bannedSet) {
      $hasBannedCard = true;
    }
    for($j=0; $j<$sideboard[$i]->count; ++$j) {
      if($sideboardCards != "") $sideboardCards .= " ";
      $sideboardCards .= $cardID;
    }
  }

  if ($format != "livinglegendscc" && $hasBannedCard) {
    $_SESSION['error'] = '⚠️ Unreleased cards must be played in the open format.';
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }

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
    $p1uid = ($_SESSION["useruid"] ?? "Player 1");
    $p1id = ($_SESSION["userid"] ?? "");
    $p1IsPatron = (isset($_SESSION["isPatron"]) ? "1" : "");
    $p1ContentCreatorID = ($_SESSION["patreonEnum"] ?? "");
  } else if ($playerID == 2) {
    $p2uid = ($_SESSION["useruid"] ?? "Player 2");
    $p2id = ($_SESSION["userid"] ?? "");
    $p2IsPatron = (isset($_SESSION["isPatron"]) ? "1" : "");
    $p2ContentCreatorID = ($_SESSION["patreonEnum"] ?? "");
  }

  if ($playerID == 2) $p2Key = hash("sha256", rand() . rand() . rand());

  WriteGameFile();
  SetCachePiece($gameName, $playerID + 1, strval(round(microtime(true) * 1000)));
  SetCachePiece($gameName, $playerID + 3, "0");
  SetCachePiece($gameName, $playerID + 6, $leader ?? "-");
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

function CardIDOverride($cardID) {
  switch($cardID) {
    case "SHD_030": return "SOR_033"; //Death Trooper
    case "SHD_063": return "SOR_066"; //System Patrol Craft
    case "SHD_066": return "SOR_068"; //Cargo Juggernaut
    case "SHD_070": return "SOR_069"; //Resilient
    case "SHD_081": return "SOR_080"; //General Tagge
    case "SHD_085": return "SOR_083"; //Superlaser Technician
    case "SHD_083": return "SOR_081"; //Seasoned Shoretrooper
    case "SHD_166": return "SOR_162"; //Disabling Fang Fighter
    case "SHD_223": return "SOR_215"; //Snapshot Reflexes
    case "SHD_231": return "SOR_220"; //Surprise Strike
    case "SHD_236": return "SOR_227"; //Snowtrooper Lieutenant
    case "SHD_238": return "SOR_229"; //Cell Block Guard
    case "SHD_257": return "SOR_247"; //Underworld Thug
    case "SHD_262": return "SOR_251"; //Confiscate
    case "SHD_121": return "SOR_117"; //Mercenary Company
    case "TWI_077": return "SOR_078"; //Vanquish
    case "TWI_107": return "SOR_111"; //Patrolling V-Wing
    case "TWI_123": return "SHD_128"; //Outflank
    case "TWI_124": return "SOR_124"; //Tactical Advantage
    case "TWI_127": return "SOR_126"; //Resupply
    case "TWI_128": return "SHD_131"; //Take Captive
    case "TWI_170": return "SHD_178"; //Daring Raid
    case "TWI_174": return "SOR_172"; //Open Fire
    case "TWI_226": return "SOR_222"; //Waylay
    case "TWI_254": return "SOR_248"; //Volunteer Soldier
    default: return $cardID;
  }
}

function CardUUIDOverride($cardID)
{
  switch ($cardID) {
    case "1706333706": return "8380936981";//Jabba's Rancor
    default: return $cardID;
  }
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
