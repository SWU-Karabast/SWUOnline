<?php

function LeaderPilotDeploy($player, $leader, $target) {
  global $CS_CachedLeader1EpicAction;
  $targetUnit = new Ally($target, $player);
  $cardID = LeaderUnit($leader);
  $epicAction = $leader != "8520821318" ? 1 : 0;//Poe Dameron JTL leader
  if($epicAction == 1) SetClassState($player, $CS_CachedLeader1EpicAction, $epicAction);
  $targetUnit->Attach($cardID, $player, epicAction:$epicAction);

  switch($cardID) {
    //Jump to Lightspeed
    case "f6eb711cf3"://Boba Fett
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
      AddDecisionQueue("PREPENDLASTRESULT", $player, "4-");
      AddDecisionQueue("SETDQCONTEXT", $player, "Deal 4 damage divided as you choose");
      AddDecisionQueue("MAYMULTIDAMAGEMULTIZONE", $player, "<-");
      AddDecisionQueue("MZOP", $player, MultiDamageStringBuilder(4, $player));
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

?>
