<?php

include "Libraries/HTTPLibraries.php";
include "Libraries/SHMOPLibraries.php";
include "Libraries/GameFormats.php";
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
    $decklink = "https://swudb.com/api/getDeckJson/" . trim($decklinkArr[count($decklinkArr) - 1]);
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
  if($leader == "") {
    $_SESSION['error'] = "<div>⚠️ Error: Deck link not supported. <br/>Make sure it is not private and that the deck link is correct.</div>";
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }
  $deckFormat = 1;
  $base = !$usesUuid ? UUIDLookup($deckObj->base->id) : $deckObj->base->id;
  $deck = $deckObj->deck;
  $sideboard = $deckObj->sideboard;
  if(IsNotAllowed($leader, $format)) {
    $_SESSION['error'] = "<div>⚠️ Your deck contains a leader that is not allowed in this format.</div>";
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }
  if(IsNotAllowed($base, $format)) {
    $_SESSION['error'] = "<div>⚠️ Your deck contains a base that is not allowed in this format.</div>";
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }
  $validation = ValidateDeck($format, $usesUuid, $leader, $base, $deck, $sideboard);
  if (!$validation->IsValid()) {
    $_SESSION['error'] = "<div>" . $validation->Error($format) . "</div>";
    if(count($validation->InvalidCards()) > 0) {
      $rejectionDetail = $validation->RejectionDetail($format);
      $_SESSION['error'] .= "<div><div><h3>" . $rejectionDetail . "</h3><h2>Invalid Cards:</h2></div><ul>"
        . implode("", array_map(function($x) {
          return "<li>" . JsHtmlTitleAndSub($x) . "</li>";
        }, $validation->InvalidCards())) . "</ul></div>";
    }
    header("Location: " . $redirectPath . "/MainMenu.php");
    WriteGameFile();
    exit;
  }
  $cards = $validation->CardString();
  $sideboardCards = $validation->SideboardString();
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
    addFavoriteDeck($_SESSION["userid"], $saveLink, $deckName, $leader, $deckFormat);
  }
} else {
  $_SESSION['error'] = '⚠️ Deck link is empty. Did you maybe copy your deck link into the Game Name field?';
  header("Location: " . $redirectPath . "/MainMenu.php");
  WriteGameFile();
  exit;
  // copy($deckFile, "./Games/" . $gameName . "/p" . $playerID . "Deck.txt");
  // copy($deckFile, "./Games/" . $gameName . "/p" . $playerID . "DeckOrig.txt");
}

if ($playerID == 1) {
  $p1uid = ($_SESSION["useruid"] ?? "Player 1");
  $p1id = ($_SESSION["userid"] ?? "");
  $p1IsPatron = (isset($_SESSION["isPatron"]) ? "1" : "");
  $p1ContentCreatorID = ($_SESSION["patreonEnum"] ?? "");
  $playerNames[1] = $p1uid;
} else if ($playerID == 2) {
  $p2uid = ($_SESSION["useruid"] ?? "Player 2");
  $p2id = ($_SESSION["userid"] ?? "");
  $p2IsPatron = (isset($_SESSION["isPatron"]) ? "1" : "");
  $p2ContentCreatorID = ($_SESSION["patreonEnum"] ?? "");
  $playerNames[2] = $p2uid;
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
      WriteLog("$p1uid rolled $p1roll and $p2uid rolled $p2roll.");
      --$tries;
    }
    $firstPlayerChooser = ($p1roll > $p2roll ? 1 : 2);
    $playerName = $playerNames[$firstPlayerChooser];
    WriteLog("$playerName chooses who goes first.");
    $gameStatus = $MGS_ChooseFirstPlayer;
    $joinerIP = $_SERVER['REMOTE_ADDR'];
  }

  if ($playerID == 2) $p2Key = hash("sha256", rand() . rand() . rand());

  WriteGameFile();
  SetCachePiece($gameName, $playerID + 1, strval(round(microtime(true) * 1000)));
  SetCachePiece($gameName, $playerID + 3, "0");
  SetCachePiece($gameName, $playerID + 6, $leader ?? "-");
  SetCachePiece($gameName, $playerID + 19, $base ?? "-");
  SetCachePiece($gameName, 14, $gameStatus);
  GamestateUpdated($gameName);

  //$authKey = ($playerID == 1 ? $p1Key : $p2Key);
  //$_SESSION["authKey"] = $authKey;
  $domain = (!empty(getenv("DOMAIN")) ? getenv("DOMAIN") : "petranaki.net");
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

function JsHtmlTitleAndSub($cardID) {
  $forJS = CardTitle($cardID);
  if(CardSubtitle($cardID) != "") $forJS .= " (" . CardSubtitle($cardID) . ")";
  return str_replace("'", "\'", $forJS);
}

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
    case "C24_001": return "SOR_038"; //Count Dooku (Darth Tyranus)
    case "C24_002": return "SOR_087"; //Darth Vader (Commanding the First Legion)
    case "C24_003": return "SOR_135"; //Emperor Palpatine (Master of the Dark Side)
    case "C24_004": return "SHD_141"; //Kylo Ren (Killing the Past)
    case "C24_005": return "TWI_134"; //Asajj Ventress (Count Dooku's Assassin)
    case "C24_006": return "TWI_135"; //Darth Maul (Revenge at Last)
    case "J24_001": return "SOR_040"; //Avenger
    case "J24_002": return "SOR_145"; //K-2SO
    case "J24_003": return "SHD_037"; //Supreme Leader Snoke
    case "J24_004": return "SHD_090"; //Maul
    case "J24_005": return "SHD_154"; //Wrecker
    case "J24_006": return "SHD_248"; //Tech
    case "GG_001": return "SOR_021"; //Dagobah Swamp
    case "GG_002": return "SOR_024"; //Echo Base
    case "GG_003": return "SOR_026"; //Catacombs of Cadera
    case "GG_004": return "SOR_026"; //Jabba's Palace
    case "GG_005": return "SOR_001"; //Experience (Token Upgrade)
    case "GG_006": return "SOR_002"; //Shield (Token Upgrade)
    case "JTL_258": return "SOR_250"; //Corellian Freighter
    case "JTL_113": return "SOR_113"; //Homestead Militia
    case "JTL_167": return "SOR_165"; //Occupier Siege Tank
    case "JTL_128": return "SOR_125"; //Prepare Prepare for Takeoff
    case "JTL_075": return "SOR_074"; //Repair
    default: return $cardID;
  }
}

function CardUUIDOverride($cardID)
{
  switch ($cardID) {
    case "1706333706": return "8380936981";//Jabba's Rancor
    //TODO: left here just in case we need these IDs
    //case "1401885853"://con exclusive 2024 Count Dooku (Darth Tyranus)
      //return "9624333142";
    //case "8292269690"://con exclusive 2024 Darth Vader (Commanding the First Legion)
      //return "8506660490";
    //case "9954244145"://con exclusive 2024 Emperor Palpatine (Master of the Dark Side)
      //return "9097316363";
    //case "3038397952"://con exclusive 2024 Kylo Ren (Killing the Past)
      //return "6263178121";
    //case "7315203824"://con exclusive 2024 Asajj Ventress (Count Dooku's Assassin)
      //return "3556557330";
    //case "5866567543"://con exclusive 2024 Darth Maul (Revenge at Last)
      //return "8613680163";
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
    case "premierf":
    // case "reqsundo":
    //   switch ($cardID) {
    //     case "WTR152"://maybe add Boba Fett leader?
    //       return true;
    //     default:
    //       return false;
    //   }
      break;
    case "commoner":
      switch ($cardID) {
        case "WTR152"://TODO: this could be a fun format to implement
          return true;
        default:
          return false;
      }
      break;
    default:
      return false;
  }
}
