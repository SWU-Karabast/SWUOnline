<?php

function ModalAbilities($player, $card, $lastResult)
{
  global $combatChain, $defPlayer;
  switch($card)
  {
    case "AQUEOUSENCHANTING":
      WriteLog($lastResult);
      if($lastResult == "1_Attack") AddCurrentTurnEffect("fMv7tIOZwLAttack", $player);
      else GiveAlliesHealthBonus($player, 1);
      return $lastResult;
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
    default: return "";
  }
}

?>
