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
    case "BOMBINGRUN":
      DamageAllAllies(3, "7916724925", arena:$lastResult[0]);
      return 1;
    case "VIGILANCE":
      $params = explode(",", $lastResult);
      for($i = 0; $i < count($params); ++$i) {
        switch($params[$i]) {
          case "Mill":
            $otherPlayer = ($player == 1 ? 2 : 1);
            Mill($otherPlayer, 6);
            break;
          case "Heal":
            Restore(5, $player);
            break;
          case "Defeat":
            MZChooseAndDestroy($player, "THEIRALLY", may:true);
            break;
          case "Shield":
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give a shield");
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "ADDSHIELD", 1);
            break;
          default: break;
        }
      }
      return 1;
    case "COMMAND":
      $params = explode(",", $lastResult);
      for($i = 0; $i < count($params); ++$i) {
        switch($params[$i]) {
          case "Experience":
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give two experience");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
            break;
          case "Deal_Damage":
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal damage equal to it's power");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "POWER", 1);
            AddDecisionQueue("PREPENDLASTRESULT", $player, "DEALDAMAGE,", 1);
            AddDecisionQueue("SETDQVAR", $player, "0", 1);
            AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to damage");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "{0}", 1);
            break;
          case "Resource"://Handled Elsewhere
            break;
          case "Return_Unit":
            MZMoveCard($player, "MYDISCARD:definedType=Unit", "MYHAND", may:false);
            break;
          default: break;
        }
      }
      return 1;
    case "CUNNING":

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
    case "FORCETHROW"://Force Throw
      DealArcane(CardCost($lastResult), 6, "PLAYCARD", "1705806419", player:$player);
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
    case "GALACTICAMBITION":
      DealArcane(CardCost($lastResult), 4, "PLAYCARD", "5494760041", player:$player);
      break;
    case "C3PO":
      $deck = new Deck($player);
      AddDecisionQueue("PASSPARAMETER", $player, $deck->Top());
      AddDecisionQueue("SETDQVAR", $player, 0);
      if(CardCost($deck->Top()) == $lastResult) {
        AddDecisionQueue("SETDQCONTEXT", $player, "Do you want to draw <0>?");
        AddDecisionQueue("YESNO", $player, "-");
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("DRAW", $player, "-", 1);
      }
      else {
        AddDecisionQueue("SETDQCONTEXT", $player, "The top card of your deck is <0>");
        AddDecisionQueue("OK", $player, "-");
      }
      break;
    case "FORACAUSEIBELIEVEIN":
      $cardArr = explode(",", $dqVars[0]);
      for($i=0; $i<count($cardArr); ++$i) {
        AddGraveyard($cardArr[$i], $player, "DECK");
      }
      break;
    case "FORCECHOKE":
      $mzArr = explode("-", $lastResult);
      if($mzArr[0] == "MYALLY") Draw($player);
      else Draw($player == 1 ? 2 : 1);
      break;
    case "UWINGREINFORCEMENT":
      $hand = &GetHand($player);
      PrependDecisionQueue("REMOVECURRENTEFFECT", $player, "8968669390", 1);
      PrependDecisionQueue("ELSE", $player, "-");
      PrependDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      PrependDecisionQueue("PASSPARAMETER", $player, "MYHAND-" . count($hand), 1);
      PrependDecisionQueue("SETDQVAR", $player, "0", 1);
      PrependDecisionQueue("OP", $player, "REMOVECARD", 1);
      PrependDecisionQueue("ADDHAND", $player, "-", 1);
      PrependDecisionQueue("MAYCHOOSECARD", $player, "<-", 1);
      PrependDecisionQueue("FILTER", $player, "LastResult-include-definedType-Unit", 1);
      PrependDecisionQueue("PASSPARAMETER", $player, "{0}");
      break;
    default: return "";
  }
}

?>
