<?php

function LeaderPilotDeploy($player, $leader, $target) {
  $targetUnit = new Ally($target, $player);
  $cardID = LeaderUnit($leader);
  $epicAction = $leader != "8520821318";
  $targetUnit->Attach($cardID, $player, epicAction:$epicAction);

  switch($cardID) {
    //Jump to Lightspeed
    case "f6eb711cf3"://Boba Fett
      include_once "Libraries/MZOpHelpers.php";
      AddDecisionQueue("FINDINDICES", $player, "ALLOURUNITSMULTI");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose units to damage", 1);
      AddDecisionQueue("MULTICHOOSEOURUNITS", $player, "<-", 1);
      AddDecisionQueue("MULTIDISTRIBUTEDAMAGE", $player,
        MultiDistributeDamageStringBuilder(4,$player,zones:"OURALLIES"), 1);
      break;
    case "a015eb5c5e"://Han Solo
      HanSoloPilotLeaderJTL($player);
      break;
    case "3064aff14f"://Lando Calrissian
      $otherArena = $targetUnit->CurrentArena() == "Ground" ? "Space" : "Ground";
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:arena=$otherArena");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give a Shield token");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "ADDSHIELD", 1);
      break;
    case "fb0da8985e"://Darth Vader
      CreateTieFighter($player);
      CreateTieFighter($player);
      break;
    default: break;
  }
}

function HanSoloPilotLeaderJTL($player) {
  $odds = 0;
  $allies = GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces()) {
    $ally = new Ally($allies[$i+5], $player);
    if(CardCostIsOdd($ally->CardID())) {
      $odds++;
    }
    $upgrades = $ally->GetUpgrades(withMetadata:false);
    for($j=0; $j<count($upgrades); ++$j) {
      if(CardCostIsOdd($upgrades[$j])) {
        $odds++;
      }
    }
  }

  ReadyResource($player, $odds);
}

function CheckForLeaderUpgradeAbilities($ally) {
  global $CS_LeaderUpgradeAbilityID1;
  $upgrades = $ally->GetUpgrades(withMetadata:false);
  for($i=0; $i<count($upgrades); ++$i) {
    switch($upgrades[$i]) {
      case "3eb545eb4b":
        SetClassState($ally->Controller(), $CS_LeaderUpgradeAbilityID1, $upgrades[$i]);
        break;
      default: break;
    }
  }
}

?>
