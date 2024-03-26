<?php

function ModalAbilities($player, $card, $lastResult)
{
  global $combatChain, $defPlayer;
  switch($card)
  {
    case "K2SO":
      $otherPlayer = ($player == 1 ? 2 : 1);
      switch($lastResult[0]) {
        case "Discard": PummelHit($otherPlayer); break;
        case "Deal_3_damage": PlayerLoseHealth($otherPlayer, 3); break;
        default: break;
      }
      return $lastResult;
    case "OUTMANEUVER":
      ExhaustAllAllies($lastResult[0], 1);
      ExhaustAllAllies($lastResult[0], 2);
      return $lastResult;
    case "EZRABRIDGER":
      switch($lastResult[0]) {
        case "Leave": break;
        case "Play": MZPlayCard($player, "MYDECK-0"); break;
        case "Discard": Mill($player, 1); break;
        default: break;
      }
      return 1;
    case "LEIAORGANA":
      switch($lastResult[0]) {
        case "Ready_Resource": ReadyResource($player); break;
        case "Exhaust_Unit":
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);
          break;
        default: break;
      }
      return 1;
    default: return "";
  }
}

function PlayerTargetedAbility($player, $card, $lastResult)
{
  global $dqVars;
  $target = ($lastResult == "Target_Opponent" ? ($player == 1 ? 2 : 1) : $player);
  switch($card)
  {

    default: return $lastResult;
  }
}

function SpecificCardLogic($player, $card, $lastResult)
{
  global $dqVars, $CS_DamageDealt;
  switch($card)
  {
    case "FORCETHROW":
      DealArcane(CardCost($lastResult), 2, "PLAYCARD", "1705806419");
      break;
    case "REINFORCEMENTWALKER":
      if($lastResult == "YES") Draw($player);
      else {
        Mill($player, 1);
        Restore(3, $player);
      }
      break;
    case "OBIWANKENOBI":
      $cardID = GetMZCard($player, $lastResult);
      if(TraitContains($cardID, "Force", $player)) Draw($player);
      break;
    default: return "";
  }
}

?>
