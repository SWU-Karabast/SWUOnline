<?php

function LeaderPilotDeploy($player, $leader, $target) {
  $targetUnit = new Ally($target, $player);
  $cardID = LeaderUnit($leader);
  $targetUnit->AddSubcard($cardID, $player, asPilot: true);

  switch($cardID) {
    //Jump to Lightspeed
    case "f6eb711cf3"://Boba Fett
      AddDecisionQueue("FINDINDICES", $player, "ALLTHEIRUNITSMULTI");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose units to damage", 1);
      AddDecisionQueue("MULTICHOOSETHEIRUNIT", $player, "<-", 1);
      AddDecisionQueue("MULTIDISTRIBUTEDAMAGE", $player, "4,1", 1);
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

?>
