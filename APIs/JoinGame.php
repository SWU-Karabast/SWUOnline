<?php


include_once "../WriteLog.php";
include_once "../Libraries/HTTPLibraries.php";
include_once "../Libraries/SHMOPLibraries.php";
include_once "../APIKeys/APIKeys.php";
include_once '../includes/functions.inc.php';
include_once '../includes/dbh.inc.php';
include_once '../CoreLogic.php';
include_once '../Libraries/CoreLibraries.php';

include_once '../LZCompressor/LZContext.php';
include_once '../LZCompressor/LZData.php';
include_once '../LZCompressor/LZReverseDictionary.php';
include_once '../LZCompressor/LZString.php';
include_once '../LZCompressor/LZUtil.php';
include_once '../LZCompressor/LZUtil16.php';

use LZCompressor\LZString as LZString;


if (!function_exists("DelimStringContains")) {
  function DelimStringContains($str, $find, $partial=false)
  {
    $arr = explode(",", $str);
    for($i=0; $i<count($arr); ++$i)
    {
      if($partial && str_contains($arr[$i], $find)) return true;
      else if($arr[$i] == $find) return true;
    }
    return false;
  }
}

if (!function_exists("SubtypeContains")) {
  function SubtypeContains($cardID, $subtype, $player="")
  {
    $cardSubtype = CardSubtype($cardID);
    return DelimStringContains($cardSubtype, $subtype);
  }
}

if (!function_exists("TypeContains")) {
  function TypeContains($cardID, $type, $player="")
  {
    $cardType = CardType($cardID);
    return DelimStringContains($cardType, $type);
  }
}

SetHeaders();

$response = new stdClass();

session_start();
if (!isset($gameName)) {
  $_POST = json_decode(file_get_contents('php://input'), true);
  if($_POST == NULL) {
    $response->error = "Parameters were not passed";
    echo json_encode($response);
    exit;
  }
  $gameName = $_POST["gameName"];
}
if (!IsGameNameValid($gameName)) {
  $response->error = "Invalid game name.";
  echo (json_encode($response));
  exit;
}
if (!isset($playerID)) $playerID = intval($_POST["playerID"]);
if (!isset($deck)) $deck = TryPOST("deck"); //This is for limited game modes (see JoinGameInput.php)
if (!isset($decklink)) $decklink = TryPOST("fabdb", ""); //Deck builder decklink
if (!isset($decksToTry)) $decksToTry = TryPOST("decksToTry"); //This is only used if there's no favorite deck or decklink. 1 = ira
if (!isset($favoriteDeck)) $favoriteDeck = TryPOST("favoriteDeck", false); //Set this to true to save the provided deck link to your favorites
if (!isset($favoriteDeckLink)) $favoriteDeckLink = TryPOST("favoriteDecks", "0"); //This one is kind of weird. It's the favorite deck index, then the string "<fav>" then the favorite deck link
if (!isset($matchup)) $matchup = TryPOST("matchup", ""); //The matchup link
$starterDeck = false;

if ($matchup == "" && GetCachePiece($gameName, $playerID + 6) != "") {
  $response->error = "Another player has already joined the game.";
  echo (json_encode($response));
  exit;
}
if ($decklink == "" && $deck == "" && $favoriteDeckLink == "0") {
  $starterDeck = true;
    switch ($decksToTry) {
        case '1':
            $deck = "../lorraineStarter.txt";
            break;
        case '2':
            $deck = "../silvieStarter.txt";
            break;
        case '3':
            $deck = "../raiStarter.txt";
            break;
        default:
            $deck = "../lorraineStarter.txt";
            break;
  }
}

if ($favoriteDeckLink != "0" && $decklink == "") $decklink = $favoriteDeckLink;

//if ($deck == "" && !IsDeckLinkValid($decklink)) {
//  $response->error = "Deck URL is not valid: " . $decklink;
//  echo (json_encode($response));
//  exit;
//}

include "../HostFiles/Redirector.php";
include "../CardDictionary.php";
include "./APIParseGamefile.php";
include "../MenuFiles/WriteGamefile.php";

if ($matchup == "" && $playerID == 2 && $gameStatus >= $MGS_Player2Joined) {
  if ($gameStatus >= $MGS_GameStarted) {
    $response->gameStarted = true;
  } else {
    $response->error = "Another player has already joined the game.";
  }
  WriteGameFile();
  echo (json_encode($response));
  exit;
}

$deckLoaded = false;
if(substr($decklink, 0, 9) == "DRAFTFAB-")
{
  $isDraftFaB = true;
  $deckFile = "../Games/" . $gameName . "/p" . $playerID . "Deck.txt";
  ParseDraftFab(substr($decklink, 9), $deckFile);
  $decklink = "";//Already loaded deck, so don't try to load again
  $deckLoaded = true;
}

if ($decklink != "") {
    if ($playerID == 1) $p1DeckLink = $decklink;
    else if ($playerID == 2) $p2DeckLink = $decklink;
    $curl = curl_init();
    $isSilvie = str_contains($decklink, "silvie");
    $isFaBMeta = str_contains($decklink, "fabmeta");
    if($isSilvie) {
        $decklinkArr = explode("/", $decklink);
        $uid = $decklinkArr[count($decklinkArr) - 2];
        $slug = $decklinkArr[count($decklinkArr) - 1];
        $apiLink = "https://api.silvie.org/api/build/decks/published?";//"@OotTheMonk/Ya7CqS207754CBvuLeB7
        $apiLink .= "id=" . $slug;
        $apiLink .= "&user=" . $uid;
    }

    curl_setopt($curl, CURLOPT_URL, $apiLink);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $apiDeck = curl_exec($curl);
    $apiInfo = curl_getinfo($curl);
    curl_close($curl);

    if ($apiDeck === FALSE) {
        if(is_array($decklink)) echo  '<b>' . "⚠️ Deckbuilder API for this deck returns no data: " . implode("/", $decklink) . '</b>';
        else echo  '<b>' . "⚠️ Deckbuilder API for this deck returns no data: " . $decklink . '</b>';
        WriteGameFile();
        exit;
    }
    $deckObj = json_decode($apiDeck);
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
    $cards = json_decode(LZString::decompressFromEncodedURIComponent($deckObj->deck->code));
    $deckName = $cards->{'name'};
    if (isset($deckObj->{'matchups'})) {
        if ($playerID == 1) $p1Matchups = $deckObj->{'matchups'};
        else if ($playerID == 2) $p2Matchups = $deckObj->{'matchups'};
    }
    $deckFormat = (isset($deckObj->{'format'}) ? $deckObj->{'format'} : "");
    //$cards = $deckObj->{'cards'};
    $deckCards = "";
    $sideboardCards = "";
    $materialCards = "";
    $totalCards = 0;

    foreach($cards as $key => $value) {
        if(str_contains($key, "-s")) continue;//TODO: Sideboard
        if(CardTypeContains($key, "REGALIA") || CardTypeContains($key, "CHAMPION"))
        {
            if($materialCards != "") $materialCards .= " ";
            $materialCards .= $key;
        }
        else
        {
            if(is_int($value) && $value > 0 && $value <= 4) for($i=0; $i<$value; ++$i)
            {
                if($deckCards != "") $deckCards .= " ";
                $deckCards .= $key;
            }
        }
    }

    //We have the decklist, now write to file
    $filename = "../Games/" . $gameName . "/p" . $playerID . "Deck.txt";
    $deckFile = fopen($filename, "w");
    fwrite($deckFile, $materialCards . "\r\n");
    fwrite($deckFile, $deckCards . "\r\n");
    fclose($deckFile);
    copy($filename, "../Games/" . $gameName . "/p" . $playerID . "DeckOrig.txt");

    if (isset($_SESSION["userid"])) {
        include_once '../includes/functions.inc.php';
        include_once "../includes/dbh.inc.php";
        /*
        $deckbuilderID = GetDeckBuilderId($_SESSION["userid"], $decklink);
        if ($deckbuilderID != "") {
          if ($playerID == 1) $p1deckbuilderID = $deckbuilderID;
          else $p2deckbuilderID = $deckbuilderID;
        }
        */
    }

    if ($favoriteDeck == "on" && isset($_SESSION["userid"])) {
        //Save deck
        include_once '../includes/functions.inc.php';
        include_once "../includes/dbh.inc.php";
        addFavoriteDeck($_SESSION["userid"], $decklink, $deckName, $character, $deckFormat);
    }
} else {
    $deckFile = $deck;
    copy($deckFile, "../Games/" . $gameName . "/p" . $playerID . "Deck.txt");
    copy($deckFile, "../Games/" . $gameName . "/p" . $playerID . "DeckOrig.txt");
}

if ($matchup == "") {
  if ($playerID == 2) {

    $gameStatus = $MGS_Player2Joined;
    if (file_exists("../Games/" . $gameName . "/gamestate.txt")) unlink("../Games/" . $gameName . "/gamestate.txt");

    $firstPlayerChooser = 1;
    $p1roll = 0;
    $p2roll = 0;
    $tries = 10;
    while ($p1roll == $p2roll && $tries > 0) {
      $p1roll = rand(1, 6) + rand(1, 6);
      $p2roll = rand(1, 6) + rand(1, 6);
      WriteLog("🎲 Player 1 rolled $p1roll and Player 2 rolled $p2roll.", path: "../");
      --$tries;
    }
    $firstPlayerChooser = ($p1roll > $p2roll ? 1 : 2);
    WriteLog("Player $firstPlayerChooser chooses who goes first.", path: "../");
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
  $domain = (!empty(getenv("DOMAIN")) ? getenv("DOMAIN") : "talishar.net");
  if ($playerID == 1) {
    $_SESSION["p1AuthKey"] = $p1Key;
    setcookie("lastAuthKey", $p1Key, time() + 86400, "/", $domain);
  } else if ($playerID == 2) {
    $_SESSION["p2AuthKey"] = $p2Key;
    setcookie("lastAuthKey", $p2Key, time() + 86400, "/", $domain);
  }
}

$response->message = "success";
$response->gameName = $gameName;
$response->playerID = $playerID;
$response->authKey = $playerID == 1 ? $p1Key : ($playerID == 2 ? $p2Key : '');
echo (json_encode($response));

session_write_close();


function ParseDraftFab($deck, $filename)
{
  global $character;
  $character = "DYN001";
  $deckCards = "";
  $headSideboard = "";
  $chestSideboard = "";
  $armsSideboard = "";
  $legsSideboard = "";
  $offhandSideboard = "";
  $weaponSideboard = "";
  $sideboardCards = "";
  $quiverSideboard = "";

  $cards = explode(",", $deck);
  for ($i = 0; $i < count($cards); ++$i) {
    $card = explode(":", $cards[$i]);
    $cardID = $card[0];
    $quantity = $card[2];
    $type = CardType($cardID);
    switch ($type) {
      case TypeContains($cardID, "T"):
        break;
      case TypeContains($cardID, "C"):
        $character = $cardID;
        break;
      case TypeContains($cardID, "W"):
        if ($weaponSideboard != "") $weaponSideboard .= " ";
        $weaponSideboard .= $cardID;
        break;
      case TypeContains($cardID, "E"):
        if (SubtypeContains($cardID, "Head")) {
          if ($headSideboard != "") $headSideboard .= " ";
          $headSideboard .= $cardID;
        } else if (SubtypeContains($cardID, "Chest")) {
          if ($chestSideboard != "") $chestSideboard .= " ";
          $chestSideboard .= $cardID;
        } else if (SubtypeContains($cardID, "Arms")) {
          if ($armsSideboard != "") $armsSideboard .= " ";
          $armsSideboard .= $cardID;
        } else if (SubtypeContains($cardID, "Legs")) {
          if ($legsSideboard != "") $legsSideboard .= " ";
          $legsSideboard .= $cardID;
        } else if (SubtypeContains($cardID, "Off-Hand")) {
          if ($offhandSideboard != "") $offhandSideboard .= " ";
          $offhandSideboard .= $cardID;
        } else if (SubtypeContains($cardID, "Quiver")) {
          if ($quiverSideboard != "") $quiverSideboard .= " ";
          $quiverSideboard .= $cardID;
        }
        break;
      default:
        for ($j = 0; $j < $quantity; ++$j) {
          if ($card[1] == "S") {
            if ($sideboardCards != "") $sideboardCards .= " ";
            $sideboardCards .= GetAltCardID($cardID);
          } else {
            if ($deckCards != "") $deckCards .= " ";
            $deckCards .= GetAltCardID($cardID);
          }
        }
        break;
    }
  }


  $deckFile = fopen($filename, "w");
  $charString = $character;

  fwrite($deckFile, $charString . "\r\n");
  fwrite($deckFile, $deckCards . "\r\n");
  fwrite($deckFile, $headSideboard . "\r\n");
  fwrite($deckFile, $chestSideboard . "\r\n");
  fwrite($deckFile, $armsSideboard . "\r\n");
  fwrite($deckFile, $legsSideboard . "\r\n");
  fwrite($deckFile, $offhandSideboard . "\r\n");
  fwrite($deckFile, $weaponSideboard . "\r\n");
  fwrite($deckFile, $sideboardCards);
  fclose($deckFile);
}

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
    case "ARC218": case "UPR224": case "MON306": case "ELE237": return "WTR224";
    case "DYN238": return "MON401";
    case "LGS157": return "DTD155";
    case "LGS158": return "DTD156";
    case "LGS159": return "DTD157";
    case "HER085": return "DTD134";
    case "DTD013": return "MON007";
    case "FAB161": return "DTD048";
    case "FAB162": return "DTD049";
    case "FAB163": return "DTD050";
    case "LGS179": return "DTD054";
    case "LGS180": return "DTD055";
    case "LGS181": return "DTD056";
    case "EVO038": return "TCC007";
    case "EVO039": return "TCC008";
    case "EVO040": return "TCC009";
    case "EVO041": return "TCC010";
    case "EVO064"; return "TCC012";
    case "EVO099": return "ARC036";
    case "EVO159": return "TCC019";
    case "EVO160": return "TCC022";
    case "EVO161": return "TCC026";
    case "EVO216": return "TCC016";
    case "TCC003": return "EVO022";
    case "TCC004": return "EVO023";
    case "TCC005": return "EVO024";
    case "TCC006": return "EVO025";
    case "DRO026": return "WTR173";
  }
  return $cardID;
}

function IsCardBanned($cardID, $format)
{
  $set = substr($cardID, 0, 3);
  //Ban spoiler cards in non-open-format
  //if($format != "livinglegendscc" && ($set == "HVY")) return true;
  switch($format) {
    case "blitz": case "compblitz":
      switch($cardID) {
        case "ARC076": case "ARC077": // Viserai | Nebula Black
        case "ELE006": // Awakening
        case "ELE186": case "ELE187": case "ELE188": // Ball Lightning
        case "ELE223": // Duskblade
        case "WTR152": // Heartened Cross Strap
        case "CRU174": case "CRU175": case "CRU176": // Snapback
        case "MON239": // Stubby Hammers
        case "CRU141": // Bloodsheath Skeleta
        case "EVR037": // Mask of the Pouncing Lynx
        case "UPR103": case "EVR120": case "EVR121": // Iyslander | Kraken's Aethervein
        case "ELE002": case "ELE003": // Oldhim | Winter's Wail
        case "MON154": case "MON155": // Chane | Galaxxi Black
        case "ARC114": case "ARC115": case "CRU159": // Kano | Crucible of Aetherweave
        case "CRU077":// Kassai, Cintari Sellsword
        case "CRU046": case "CRU050": // Ira, Crimson Haze | Edge of Autumn
          return true;
        default: return false;
      }
    case "cc": case "compcc":
      switch($cardID) {
        case "MON001": case "MON003": // Prism Sculptor of Arc Light | Luminaris
        case "EVR017": // Bravo, Star of the Show
        case "MON153": case "MON155": // Chane, Bound by Shadow | Galaxxi Black
        case "ELE006": // Awakening
        case "ELE186": case "ELE187": case "ELE188": // Ball Lightning
        case "WTR164": case "WTR165": case "WTR166": // Drone of Brutality
        case "ELE223":  // Duskblade
        case "ARC170": case "ARC171": case "ARC172": // Plunder Run
        case "MON239": // Stubby Hammers
        case "CRU141": // Bloodsheath Skeleta
        case "ELE114": // Pulse of Isenloft
        case "ELE031": case "ELE034": // Lexi, Livewire | Voltaire, Strike Twice
        case "ELE062": case "ELE222": // Briar, Warden of Thorns | Rosetta Thorn
        case "ELE001": case "ELE003": // Oldhim, Grandfather of Eternity | Winter's Wail
        case "UPR102": case "EVR121": // Iyslander, Stormbind | Kraken's Aethervein
          return true;
        default: return false;
      }
    case "commoner":
      switch($cardID) {
        case "ELE186": case "ELE187": case "ELE188": // Ball Lightning
        case "MON266": case "MON267": case "MON268": // Belittle
          return true;
        default: return false;
      }
    default: return false;
  }
}


function ReverseArt($cardID)
{
  switch ($cardID) {
    case "WTR078": return "CRU049";
    case "CRU004": return "CRU005";
    case "CRU051": return "CRU052";
    case "CRU079": return "CRU080";
    case "DYN069": return "DYN070";
    case "DYN115": return "DYN116";
    case "OUT005": return "OUT006";
    case "OUT007": return "OUT008";
    case "OUT009": return "OUT010";
    default:
      return $cardID;
  }
}
