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
        case "Deal_3_damage": DealDamageAsync($otherPlayer, 3, "DAMAGE", "3232845719"); break;
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
        case "Play":
          PrependDecisionQueue("SWAPTURN", $mainPlayer, "-");
          MZPlayCard($player, "MYDECK-0");
          break;
        case "Discard": Mill($player, 1); break;
        default: break;
      }
      return 1;
    case "LEIAORGANA":
      switch($lastResult[0]) {
        case "Ready_Resource": ReadyResource($player); break;
        case "Exhaust_Unit":
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
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
            MZChooseAndDestroy($player, "THEIRALLY:maxHealth=3", may:true);
            break;
          case "Shield":
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give a shield");
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
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
            AddDecisionQueue("MZFILTER", $player, "unique=1");
            AddDecisionQueue("MZFILTER", $player, "definedType=Leader");
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
      $params = explode(",", $lastResult);
      for($i = 0; $i < count($params); ++$i) {
        switch($params[$i]) {
          case "Return_Unit":
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxAttack=4&THEIRALLY:maxAttack=4");
            AddDecisionQueue("MZFILTER", $player, "definedType=Leader");
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to return", 1);
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "BOUNCE", 1);
            break;
          case "Buff_Unit":
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to buff", 1);
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
            AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "3789633661,HAND");
            break;
          case "Exhaust_Units":
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to exhaust");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "REST", 1);
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY", 1);
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to exhaust");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "REST", 1);
            break;
          case "Discard_Random":
            $otherPlayer = ($player == 1 ? 2 : 1);
            DiscardRandom($otherPlayer, "3789633661");
            break;
          default: break;
        }
      }
      return 1;
    case "AGGRESSION":
      $params = explode(",", $lastResult);
      for($i = 0; $i < count($params); ++$i) {
        switch($params[$i]) {
          case "Draw":
            Draw($player);
            break;
          case "Defeat_Upgrades":
            DefeatUpgrade($player);
            DefeatUpgrade($player);
            break;
          case "Ready_Unit":
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxAttack=3&THEIRALLY:maxAttack=3");
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to ready");
            AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "READY", 1);
            break;
          case "Deal_Damage":
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 4 damage to");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "DEALDAMAGE,4", 1);
            break;
          default: break;
        }
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
    case "FORCETHROW"://Force Throw
      $damage = CardCost($lastResult);
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal " . $damage . " damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "DEALDAMAGE," . $damage, 1);
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
        AddDecisionQueue("REVEALCARDS", $player, "-", 1);
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
    case "GRANDADMIRALTHRAWN":
      $targetPlayer = ($lastResult == "Yourself" ? $player : ($player == 1 ? 2 : 1));
      $deck = new Deck($targetPlayer);
      if($deck->Reveal()) {
        $cardCost = CardCost($deck->Top());
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxCost=" . $cardCost . "&THEIRALLY:maxCost=" . $cardCost);
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "REST", 1);
      }
      break;
    case "THEEMPERORSLEGION":
      MZMoveCard($player, "MYDISCARD:definedType=Unit", "MYHAND", may:true, context:"Choose ONLY units defeated this phase then pass");
      AddDecisionQueue("SPECIFICCARD", $player, "THEEMPERORSLEGION", 1);
      break;
    case "UWINGREINFORCEMENT":
      $totalCost = 0;
      $cardArr = explode(",", $lastResult);
      for($i=0; $i<count($cardArr); ++$i) {
        PlayCard($cardArr[$i], "DECK");
        if($i == count($cardArr)-1) SetAfterPlayedBy($player, "8968669390");
        $totalCost += CardCost($cardArr[$i]);
      }
      if($totalCost > 7) {
        WriteLog("<span style='color:red;'>Too many units played. Let's just say we'd like to avoid any Imperial entanglements. Reverting gamestate.</span>");
        RevertGamestate();
        return "";
      }
      break;
    case "DARTHVADER":
      $totalCost = 0;
      $cardArr = explode(",", $lastResult);
      for($i=0; $i<count($cardArr); ++$i) {
        PlayCard($cardArr[$i], "DECK");
        if($i == count($cardArr)-1) SetAfterPlayedBy($player, "8506660490");
        $totalCost += CardCost($cardArr[$i]);
      }
      if($totalCost > 3) {
        WriteLog("<span style='color:red;'>Too many units played. I find your lack of faith disturbing. Reverting gamestate.</span>");
        RevertGamestate();
        return "";
      }
      break;
    case "POWERFAILURE":
      PrependDecisionQueue("SPECIFICCARD", $player, "POWERFAILURE", 1);
      PrependDecisionQueue("OP", $player, "DEFEATUPGRADE", 1);
      PrependDecisionQueue("MAYCHOOSECARD", $player, "<-", 1);
      PrependDecisionQueue("SETDQCONTEXT", $player, "Choose an upgrade to defeat", 1);
      PrependDecisionQueue("MZOP", $player, "GETSUBCARDS", 1);
      PrependDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      break;
    case "RESTOCK":
      $arr = [];
      for($i = count($lastResult) - DiscardPieces(); $i >= 0; $i -= DiscardPieces()) {
        array_push($arr, RemoveGraveyard($player, $lastResult[$i]));
      }
      RevealCards(implode(",", $arr), $player);
      if(count($arr) > 0) {
        RandomizeArray($arr);
        $deck = new Deck($player);
        for($i=0; $i<count($arr); ++$i) {
          $deck->Add($arr[$i]);
        }
      }
      break;
    case "BAMBOOZLE":
      $upgrades = [];
      $owner = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $owner);
      $subcards = $ally->GetSubcards();
      for($i=0; $i<count($subcards); ++$i) {
        if(!IsToken($subcards[$i])) AddHand($owner, $subcards[$i]);
        $ally->DealDamage(CardHP($subcards[$i]));
        array_push($upgrades, $subcards[$i]);
      }
      $ally->ClearSubcards();
      for($i=0; $i<count($upgrades); ++$i) {
        UpgradeLeftPlay($upgrades[$i], $ally->PlayerID(), $ally->Index());
      }
      return $lastResult;
    case "DONTGETCOCKY":
      $deck = new Deck($player);
      $deck->Reveal();
      $card = $deck->Remove(0);
      $dqVars[1] += CardCost($card);
      $deck->Add($card);
      if($dqVars[1] > 7) {
        WriteLog("<span style='color:goldenrod;'>Great Kid, Don't Get Cocky...</span>");
        return "";
      }
      PrependDecisionQueue("MZOP", $player, "DEALDAMAGE," . $dqVars[1], 1);
      PrependDecisionQueue("PASSPARAMETER", $player, $dqVars[0], 1);
      PrependDecisionQueue("ELSE", $player, "-");
      PrependDecisionQueue("SPECIFICCARD", $player, "DONTGETCOCKY", 1);
      PrependDecisionQueue("NOPASS", $player, "-");
      PrependDecisionQueue("YESNO", $player, "-");
      PrependDecisionQueue("SETDQCONTEXT", $player, "Do you want to continue? (Damage: " . $dqVars[1] . ")");
      return $lastResult;
    case "ADMIRALACKBAR":
      $targetCard = GetMZCard($player, $lastResult);
      $damage = SearchCount(SearchAllies($player, arena:CardArenas($targetCard)));
      AddDecisionQueue("PASSPARAMETER", $player, $lastResult);
      AddDecisionQueue("MZOP", $player, "DEALDAMAGE," . $damage, 1);
      return $lastResult;
    case "MEDALCEREMONY":
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        $ally->Attach("2007868442");//Experience token
      }
      return $lastResult;
    case "LTCHILDSEN":
      if ($lastResult == []) {
        return $lastResult;
      }
      $hand = &GetHand($player);
      $reveal = "";
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . LastAllyIndex($player), $player);
        $ally->Attach("2007868442");//Experience token
        $reveal .= $hand[$lastResult[$i]] . ",";
      }
      $reveal = rtrim($reveal, ",");
      RevealCards($reveal, $player);
      return $lastResult;
    case "MULTIGIVEEXPERIENCE":
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        $ally->Attach("2007868442");//Experience token
      }
      return $lastResult;
    case "IHADNOCHOICE":
      $cards = explode(",", MZSort($dqVars[0]));
      for($i=count($cards)-1; $i>=0; --$i) {
        if($cards[$i] == $lastResult) {
          MZBounce($player, $cards[$i]);
        } else {
          MZSink($player, $cards[$i]);
        }
      }
      return $lastResult;
    default: return "";
  }
}

?>
