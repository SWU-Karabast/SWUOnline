<?php

include "../AccountFiles/AccountSessionAPI.php";
include_once '../includes/functions.inc.php';
include_once "../includes/dbh.inc.php";
include_once "../Libraries/PlayerSettings.php";
include_once "../Libraries/HTTPLibraries.php";
include_once "../Assets/patreon-php-master/src/PatreonDictionary.php";

session_start();

SetHeaders();

$response = new stdClass();
$response->cardBacks = [];

//Add default card back
$cardBack = new stdClass();
$cardBack->name = "Default";
$cardBack->id = 0;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Rebel Resource";
$cardBack->id = 3;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Rebel Resource Dark";
$cardBack->id = 4;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "L8 Night Gaming";
$cardBack->id = 6;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Mobyus1 Simple";
$cardBack->id = 7;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Mobyus1 Titled";
$cardBack->id = 8;
$response->cardBacks[] = $cardBack;
$cardBack = new stdClass();
$cardBack->name = "Outmaneuver";
$cardBack->id = 9;
$response->cardBacks[] = $cardBack;
$cardBack->name = "Bothan Network";
$cardBack->id = 10;
$response->cardBacks[] = $cardBack;
$cardBack->name = "Padawan Unlimited";
$cardBack->id = 11;
$response->cardBacks[] = $cardBack;
$cardBack->name = "RVA SWU";
$cardBack->id = 12;
$response->cardBacks[] = $cardBack;

$response->playmats = [];
if(IsUserLoggedIn()) {
  foreach(PatreonCampaign::cases() as $campaign) {
    if(isset($_SESSION[$campaign->SessionID()]) || (isset($_SESSION["useruid"]) && $campaign->IsTeamMember($_SESSION["useruid"]))) {
      //Check card backs first
      $cardBacks = $campaign->CardBacks();
      $cardBacks = explode(",", $cardBacks);
      for($i = 0; $i < count($cardBacks); ++$i) {
        $cardBack = new stdClass();
        $cardBack->name = $campaign->CampaignName() . (count($cardBacks) > 1 ? " " . $i + 1 : "");
        $cardBack->id = $cardBacks[$i];
        $response->cardBacks[] = $cardBack;
      }
    }
  }

  for ($i = 0; $i < 17; ++$i) {
    if($i == 7) continue;
    $playmat = new stdClass();
    $playmat->id = $i;
    $playmat->name = GetPlaymatName($i);
    $response->playmats[] = $playmat;
  }
}

session_write_close();
echo json_encode($response);

function GetPlaymatName($id)
{
  switch ($id) {
    case 0:
      return "Plain";
    case 1:
      return "Demonastery";
    case 2:
      return "Metrix";
    case 3:
      return "Misteria";
    case 4:
      return "Pits";
    case 5:
      return "Savage";
    case 6:
      return "Solana";
    case 7:
      return "Volcor";
    case 8:
      return "Data-Doll";
    case 9:
      return "Aria";
    case 10:
      return "Bare-Fangs-AHS";
    case 11:
      return "Erase-Face-AHS";
    case 12:
      return "Dusk-Till-Dawn-AHS";
    case 13:
      return "Exude-Confidence-AHS";
    case 14:
      return "Command-and-Conquer-AHS";
    case 15:
      return "Swarming-Gloomveil-AHS";
    case 16:
      return "FindCenter";
    default:
      return "N/A";
  }
}
