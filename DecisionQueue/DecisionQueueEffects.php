<?php

function ModalAbilities($player, $parameter, $lastResult)
{
  global $combatChain, $defPlayer;
  $paramArr = explode(",", $parameter);
  switch($paramArr[0])
  {
    case "K2SO":
      $otherPlayer = ($player == 1 ? 2 : 1);
      switch($lastResult) {
        case 0: // Deal damage
          DealDamageAsync($otherPlayer, 3, "DAMAGE", "3232845719", sourcePlayer:$player);
          break;
        case 1: // Discard a card
          PummelHit($otherPlayer);
          break;
        default: break;
      }
      return $lastResult;
    case "OUTMANEUVER":
      $arena = $lastResult == 0 ? "Space" : "Ground";
      ExhaustAllAllies($arena, 1);
      ExhaustAllAllies($arena, 2);
      return $lastResult;
    case "EZRABRIDGER":
      switch($lastResult) {
        case 0: // Play it
          PrependDecisionQueue("SWAPTURN", $player, "-");
          MZPlayCard($player, "MYDECK-0");
          break;
        case 1: // Discard it
          Mill($player, 1);
          break;
        case 2: // Leave it
          break;
        default: break;
      }
      return $lastResult;
    case "LEIAORGANA":
      switch($lastResult) {
        case 0: // Ready a resource
          ReadyResource($player);
          break;
        case 1: // Exhaust a unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);
          break;
        default: break;
      }
      return $lastResult;
    case "BOMBINGRUN":
      $arena = $lastResult == 0 ? "Space" : "Ground";
      DamageAllAllies(3, "7916724925", arena:$arena);
      return 1;
    case "POEDAMERON":
      switch($lastResult) {
        case 0: // Deal damage
          PummelHit($player, may:true, context:"Discard a card to deal 2 damage to a unit or base");
          $otherPlayer = ($player == 1 ? 2 : 1);
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY", 1);
          AddDecisionQueue("PREPENDLASTRESULT", $player, "MYCHAR-0,THEIRCHAR-0,", 1);
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit or base to deal 2 damage to", 1);
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player", 1);
          break;
        case 1: // Defeat an upgrade
          PummelHit($player, may:true, context:"Discard a card to defeat an upgrade");
          DefeatUpgrade($player, passable:true);
          break;
        case 2: // Opponent discards a card
          PummelHit($player, may:true, context:"Discard a card to force your opponent to discard a card");
          $otherPlayer = ($player == 1 ? 2 : 1);
          PummelHit($otherPlayer, passable:true);
          break;
        default: break;
      }
      return $lastResult;
    case "VIGILANCE":
      switch($lastResult) {
        case 0: // Mill opponent
          $otherPlayer = ($player == 1 ? 2 : 1);
          AddDecisionQueue("MILL", $otherPlayer, "6");
          break;
        case 1: // Heal base
          AddDecisionQueue("PASSPARAMETER", $player, "MYCHAR-0");
          AddDecisionQueue("MZOP", $player, "RESTORE,5");
          break;
        case 2: // Defeat a unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxHealth=3&THEIRALLY:maxHealth=3");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to defeat");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DESTROY,$player", 1);
          break;
        case 3: // Give a Shield token
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give a shield");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "ADDSHIELD", 1);
          break;
        default: break;
      }
      return $lastResult;
    case "COMMAND":
      switch($lastResult) {
        case 0: // Give two experience tokens
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give two experience");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
          AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
          break;
        case 1: // Deal damage
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal damage equal to it's power");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "POWER", 1);
          AddDecisionQueue("PREPENDLASTRESULT", $player, "DEALDAMAGE,", 1);
          AddDecisionQueue("SETDQVAR", $player, "0", 1);
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
          AddDecisionQueue("MZFILTER", $player, "unique=1");
          AddDecisionQueue("MZFILTER", $player, "definedType=Leader");//are leaders not already marked as unique?
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to damage");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "{0},$player,1", 1);
          break;
        case 2: // Resource
          $discard = &GetDiscard($player);
          $discardIndex = 0;
          for ($i = count($discard) - 1; $i >= 0; --$i) {
            if ($discard[$i] == "0073206444") { //Command
              $discardIndex = $i;
              break;
            }
          }
          RemoveDiscard($player, $discardIndex);
          AddResources("0073206444", $player, "GY", "DOWN", isExhausted:1); //Command
          break;
        case 3: // Return a unit
          MZMoveCard($player, "MYDISCARD:definedType=Unit", "MYHAND", may:false);
          break;
        default: break;
      }
      return $lastResult;
    case "CUNNING":
      switch($lastResult) {
        case 0: // Return unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxAttack=4&THEIRALLY:maxAttack=4");
          AddDecisionQueue("MZFILTER", $player, "leader=1");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to return", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "BOUNCE", 1);
          break;
        case 1: // Buff unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to buff", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
          AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "3789633661,HAND");
          break;
        case 2: // Exhaust units
          for ($i = 0; $i < 2; $i++) {
            AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
            AddDecisionQueue("MZFILTER", $player, "status=1");
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
            AddDecisionQueue("MZOP", $player, "REST", 1);
          }
          break;
        case 3: // Discard a card
          $otherPlayer = ($player == 1 ? 2 : 1);
          AddDecisionQueue("OP", $otherPlayer, "DISCARDRANDOM,3789633661");
          break;
        default: break;
      }
      return $lastResult;
    case "AGGRESSION":
      switch($lastResult) {
        case 0: // Draw
          Draw($player);
          break;
        case 1: // Defeat upgrades
          DefeatUpgrade($player, may:true);
          DefeatUpgrade($player, may:true);
          break;
        case 2: // Ready a unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxAttack=3&THEIRALLY:maxAttack=3");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to ready");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "READY", 1);
          break;
        case 3: // Deal damage
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 4 damage to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,4,$player", 1);
          break;
        default: break;
      }
      return $lastResult;
    case "LETTHEWOOKIEEWIN":
      switch($lastResult) {
        case 0: // Ready resources
          ReadyResource($player, 6);
          break;
        case 1: // Ready a unit
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to attack with");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "READY", 1);
          AddDecisionQueue("MZALLCARDTRAITORPASS", $player, "Wookiee", 1);
          AddDecisionQueue("MZOP", $player, "ADDEFFECT,7578472075", 1);
          AddDecisionQueue("MZOP", $player, "ATTACK", 1);
          break;
        default: break;
      }
      return $lastResult;
    case "POLITICALPRESSURE":
      switch($lastResult) {
        case 0: // Discard a random card
          DiscardRandom($player, "3357486161");
          break;
        case 1: // Create Battle Droid tokens
          $otherPlayer = ($player == 1 ? 2 : 1);
          CreateBattleDroid($otherPlayer);
          CreateBattleDroid($otherPlayer);
          break;
        default: break;
      }
      return $lastResult;
    case "MANUFACTUREDSOLDIERS":
      switch($lastResult) {
        case 0: // Create Clone Trooper tokens
          CreateCloneTrooper($player);
          CreateCloneTrooper($player);
          break;
        case 1: // Create Battle Droid tokens
          CreateBattleDroid($player);
          CreateBattleDroid($player);
          CreateBattleDroid($player);
          break;
        default: break;
      }
      return $lastResult;
    case "CORVUS":
      switch($lastResult) {
        case 0: // Move Pilot unit to Corvus
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Pilot");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a Pilot unit to attach");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
          AddDecisionQueue("MZOP", $player, "MOVEPILOTUNIT", 1);
          break;
        case 1: // Move Pilot upgrade to Corvus
          global $dqVars, $CS_PlayedAsUpgrade;
          $uniqueID = $dqVars[0];
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:hasPilotOnly=1");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to move a Pilot from.");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("SETDQVAR", $player, "1", 1);
          AddDecisionQueue("MZOP", $player, "GETUPGRADES", 1);
          AddDecisionQueue("FILTER", $player, "LastResult-include-trait-Pilot", 1);
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a pilot upgrade to move.", 1);
          AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
          AddDecisionQueue("SETDQVAR", $player, "0", 1);
          AddDecisionQueue("PASSPARAMETER", $player, "1", 1);
          AddDecisionQueue("SETCLASSSTATE", $player, $CS_PlayedAsUpgrade, 1);
          AddDecisionQueue("PASSPARAMETER", $player, $uniqueID, 1);
          AddDecisionQueue("MZOP", $player, "MOVEUPGRADE", 1);
          break;
        default: break;
      }
      return $lastResult;
    case "YULAREN_JTL":
      $effectType = intval($lastResult);
      $effectName = "3148212344_" . match($effectType) {
        0 => "Grit",
        1 => "Restore_1",
        2 => "Sentinel",
        3 => "Shielded",
      };
      $yularenUniqueID = $paramArr[1];
      AddDecisionQueue("PASSPARAMETER", $player, $yularenUniqueID, 1);
      AddDecisionQueue("ADDLIMITEDPERMANENTEFFECT", $player, "$effectName,HAND," . $player, 1);
      return $yularenUniqueID;
    default: return "";
  }
  //ModalAbilities end
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

function SpecificCardLogic($player, $parameter, $lastResult)
{
  global $dqVars;
  $parameterArr = explode(",", $parameter);
  $card = $parameterArr[0];
  $otherPlayer = $player == 1 ? 2 : 1;
  switch($card)
  {
    case "SABINEWREN_TWI":
      $card = Mill($player, 1);
      if (!SharesAspect($card, GetPlayerBase($player))) {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 2 damage");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player,1", 1);
      }
      break;
    case "CAUGHTINTHECROSSFIRE":
      $cardArr = explode(",", $dqVars[0]);
      rsort($cardArr); // Sort the cards by index, with the highest first, to prevent errors caused by index changes after defeat.
      $ally1 = new Ally($cardArr[0]);
      $ally1Power = $ally1->CurrentPower();
      $ally2 = new Ally($cardArr[1]);
      $ally1->DealDamage($ally2->CurrentPower(), fromUnitEffect:true);
      $ally2->DealDamage($ally1Power, fromUnitEffect:true);
      break;
    case "MAUL_TWI":
      if ($lastResult==="Units") {
        $dqVars[0]=str_replace("THEIRCHAR-0,", "", $dqVars[0]);
        AddDecisionQueue("PASSPARAMETER", $player, $dqVars[0], 1);
        AddDecisionQueue("OP", $player, "MZTONORMALINDICES");
        AddDecisionQueue("MZOP", $player, "MULTICHOOSEATTACKTARGETS", 1);
      }
      break;
    case "AFINEADDITION":
      switch($lastResult)
      {
        case "My_Hand": AddDecisionQueue("MULTIZONEINDICES", $player, "MYHAND:definedType=Upgrade");
          break;
        case "My_Discard": AddDecisionQueue("MULTIZONEINDICES", $player, "MYDISCARD:definedType=Upgrade");
          break;
        case "Opponent_Discard": AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRDISCARD:definedType=Upgrade");
          break;
      }
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $player, "7895170711", 1);
      AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      break;
    case "CLEARTHEFIELD":
      $ally = new Ally($lastResult);
      $cardTitle = CardTitle($ally->CardID());
      MZBounce($player, $ally->MZIndex());
      $targetCards = SearchAlliesUniqueIDForTitle($otherPlayer, $cardTitle);
      $targetCardsArr = $targetCards ? explode(",", $targetCards) : [];

      for ($i = 0; $i < count($targetCardsArr); ++$i) {
        $targetAlly = new Ally($targetCardsArr[$i]);
        if (!$targetAlly->IsLeader()) {
          MZBounce($player, $targetAlly->MZIndex());
        }
      }
      break;
    case "RESOLUTE":
      $cardID = GetMZCard($player, $lastResult);
      $cardTitle = CardTitle($cardID);
      $targetCards = SearchAlliesUniqueIDForTitle($otherPlayer, $cardTitle);
      $targetCardsArr = explode(",", $targetCards);

      for($i=0; $i<count($targetCardsArr); ++$i) {
        $targetAlly = new Ally($targetCardsArr[$i]);
        $targetAlly->DealDamage(amount:2, enemyDamage:true);
      }
      break;
    case "FORCETHROW"://Force Throw
      $damage = CardCost($lastResult);
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal " . $damage . " damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "DEALDAMAGE,$damage,$player", 1);
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
      DealDamageAsync($player, CardCost($lastResult), "DAMAGE", "5494760041", sourcePlayer:$player);
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
    case "EQUALIZE":
      if (HasFewerUnits($player)) {
        $ally = new Ally($lastResult);
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        if ($ally->Exists()) {
          AddDecisionQueue("MZFILTER", $player, "index=" . $ally->MZIndex());
        }
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give -2/-2", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("SETDQVAR", $player, 0, 1);
        AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
        AddDecisionQueue("SETDQVAR", $player, 1, 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "5013214638,PLAY", 1);
        AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
        AddDecisionQueue("MZOP", $player, "REDUCEHEALTH,2", 1);
      }
      break;
    case "FORCECHOKE":
      $mzArr = explode("-", $lastResult);
      if($mzArr[0] == "MYALLY") Draw($player);
      else Draw($player == 1 ? 2 : 1);
      return $lastResult;
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
      $search = SearchDiscard($player, definedType:"Unit", defeatedThisPhase:true);
      if (SearchCount($search) > 0) {
        $indices = explode(",", $search);
        for ($i = count($indices) - 1; $i >= 0; $i--) {
          MZMoveCard($player, "", "MYHAND", mzIndex:"MYDISCARD-" . $indices[$i]);
        }
      }
      break;
    case "YOUREALLCLEARKID":
      $totalEnemySpaceUnits = SearchCount(SearchAllies($otherPlayer, arena:"Space"));
      if ($totalEnemySpaceUnits == 0) {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give an experience token");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
      }
      break;
    case "AHSOKATANOJTL":
      if (DefinedTypesContains($lastResult, "Unit")) {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("MZFILTER", $player, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "REST", 1);
      }
      break;
    case "UWINGREINFORCEMENT":
      $totalCost = 0;
      $cardArr = explode(",", $lastResult);
      if($lastResult == "") $cardArr = [];
      for($i=0; $i<count($cardArr); ++$i) {
        AddCurrentTurnEffect("8968669390", $player);
        PlayCard($cardArr[$i], "DECK");
        $totalCost += CardCost($cardArr[$i]);
      }
      if($totalCost > 7) {
        WriteLog("<span style='color:red;'>Too many units played. Let's just say we'd like to avoid any Imperial entanglements. Reverting gamestate.</span>");
        RevertGamestate();
        return "";
      }
      $deck = new Deck($player);
      $searchLeftovers = explode(",", $deck->Bottom(true, 10 - count($cardArr)));
      shuffle($searchLeftovers);
      for($i=0; $i<count($searchLeftovers); ++$i) {
        AddBottomDeck($searchLeftovers[$i], $player);
      }
      break;
    case "DARTHVADER":
      $totalCost = 0;
      $cardArr = explode(",", $lastResult);
      if($lastResult == "") $cardArr = [];
      for($i=0; $i<count($cardArr); ++$i) {
        AddCurrentTurnEffect("8506660490", $player);
        PlayCard($cardArr[$i], "DECK");
        $totalCost += CardCost($cardArr[$i]);
      }
      if($totalCost > 3) {
        WriteLog("<span style='color:red;'>Too many units played. I find your lack of faith disturbing. Reverting gamestate.</span>");
        RevertGamestate();
        return "";
      }
      $deck = new Deck($player);
      $searchLeftovers = explode(",", $deck->Bottom(true, 10 - count($cardArr)));
      shuffle($searchLeftovers);
      for($i=0; $i<count($searchLeftovers); ++$i) {
        AddBottomDeck($searchLeftovers[$i], $player);
      }
      break;
    case "POWERFAILURE":
      PrependDecisionQueue("SPECIFICCARD", $player, "POWERFAILURE", 1);
      PrependDecisionQueue("OP", $player, "DEFEATUPGRADE", 1);
      PrependDecisionQueue("MAYCHOOSECARD", $player, "<-", 1);
      PrependDecisionQueue("SETDQCONTEXT", $player, "Choose an upgrade to defeat", 1);
      PrependDecisionQueue("MZOP", $player, "GETUPGRADES", 1);
      PrependDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      break;
    case "RESTOCK":
      $arr = [];
      for($i = count($lastResult); $i >= 0; --$i) {
        if($lastResult[$i] != "") $arr[] = RemoveGraveyard($player, $lastResult[$i]);
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
      $upgradesReturned = [];
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      $upgrades = $ally->GetUpgrades(true);
      for($i=0; $i<count($upgrades); $i+=SubcardPieces()) {
        $ally->RemoveSubcard($upgrades[$i], skipDestroy:true);
        if(!IsToken($upgrades[$i]) && !CardIDIsLeader($upgrades[$i])) AddHand($upgrades[$i+1], $upgrades[$i]);
      }
      return $lastResult;
    case "JUMPTOLIGHTSPEED":
      $upgradesReturned = [];
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      $upgrades = $ally->GetUpgrades(true);
      for($i=0; $i<count($upgrades); $i+=SubcardPieces()) {
        $ally->RemoveSubcard($upgrades[$i], skipDestroy:true);
        if(!IsToken($upgrades[$i]) && !CardIDIsLeader($upgrades[$i])) AddHand($upgrades[$i+1], $upgrades[$i]);
      }
      AddCurrentTurnEffect("5329736697", $player, "EFFECT", $ally->CardID());
      return $lastResult;
    case "SHOOTDOWN":
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      $wasDestroyed = $ally->DealDamage(3);
      if($wasDestroyed) {
        DealDamageAsync($otherPlayer, 2, "DAMAGE", "7730475388", sourcePlayer:$player);
      }
      break;
    case "PIERCINGSHOT":
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      foreach ($ally->GetUpgrades(true) as $upgrade) {
        if ($upgrade == "8752877738") { // Shield token
          $ally->DefeatUpgrade($upgrade);
        }
      }
      $ally->DealDamage(3, enemyDamage:$ally->Controller() != $player);
      break;
    case "SUPERHEAVYIONCANNON":
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      IndirectDamage("5016817239", $controller, $ally->CurrentPower(), true);
      break;
    case "THEANNIHILATOR":
      $otherPlayer = $player == 1 ? 2 : 1;
      $destroyedID = $lastResult;
      $hand = &GetHand($otherPlayer);
      for($i = count($hand) - 1; $i >= 0; $i -= HandPieces()) {
        if($hand[$i] == $destroyedID) {
          DiscardCard($otherPlayer, $i);
        }
      }
      $deck = &GetDeck($otherPlayer);
      $deckClass = new Deck($otherPlayer);
      for ($i = count($deck) - 1; $i >= 0; $i -= DeckPieces()) {
        if ($deck[$i] == $destroyedID) {
          $deckClass->Remove($i);
          AddGraveyard($destroyedID, $otherPlayer, "DECK");
        }
      }
      break;
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
      PrependDecisionQueue("MZOP", $player, "DEALDAMAGE," . $dqVars[1] . ",$player", 1);
      PrependDecisionQueue("PASSPARAMETER", $player, $dqVars[0], 1);
      PrependDecisionQueue("ELSE", $player, "-");
      PrependDecisionQueue("SPECIFICCARD", $player, "DONTGETCOCKY", 1);
      PrependDecisionQueue("NOPASS", $player, "-");
      PrependDecisionQueue("YESNO", $player, "-");
      PrependDecisionQueue("SETDQCONTEXT", $player, "Do you want to continue? (Damage: " . $dqVars[1] . ")");
      return $lastResult;
    case "ADMIRALACKBAR":
      $targetAlly = new Ally($lastResult, MZPlayerID($player, $lastResult));
      $damage = SearchCount(SearchAllies($player, arena:$targetAlly->CurrentArena()));
      AddDecisionQueue("PASSPARAMETER", $player, $lastResult);
      AddDecisionQueue("MZOP", $player, DealDamageBuilder($damage,$player,isUnitEffect:1), 1);
      return $lastResult;
    case "LIGHTSPEEDASSAULT":
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $otherPlayer);
      $currentPower = $ally->CurrentPower();
      $ally->Destroy();
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:arena=Space");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose an enemy space unit to deal " . $currentPower . " damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("SETDQVAR", $player, 0, 1);
      AddDecisionQueue("SPECIFICCARD", $player, "LIGHTSPEEDASSAULT2", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      AddDecisionQueue("MZOP", $player, "DEALDAMAGE," . $currentPower, 1);
      return $lastResult;
    case "LIGHTSPEEDASSAULT2":
      $controller = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $controller);
      $power = $ally->CurrentPower();
      IndirectDamage("8606123385", ($player == 1 ? 2 : 1), $power, false);
      return $lastResult;
    case "ALLWINGSREPORTIN":
      foreach ($lastResult as $index) {
        $ally = new Ally("MYALLY-" . $index, $player);
        $ally->Exhaust();
        CreateXWing($player);
      }
      return $lastResult;
    case "GUERILLAINSURGENCY":
      DamageAllAllies(4, "7235023816", arena: "Ground");
      return $lastResult;
    case "MEDALCEREMONY":
      if($lastResult == "PASS") {
        return $lastResult;
      }
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        $ally->Attach("2007868442");//Experience token
      }
      return $lastResult;
    case "PLANETARYINVASION":
      if($lastResult == "PASS") {
        return $lastResult;
      }

      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        $ally->Ready();
        $ally->AddEffect("1167572655");//Planetary Invasion
      }
      return $lastResult;
    case "NODISINTEGRATIONS":
      $ally = new Ally($lastResult, MZPlayerID($player, $lastResult));
      $ally->DealDamage($ally->Health() - 1);
      return $lastResult;
    case "LTCHILDSEN":
      if($lastResult == "PASS" || $lastResult == []) {
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
    case "CONSOLIDATIONOFPOWER":
      if (count($lastResult) > 0) {
        $totalPower = 0;
        $uniqueIDs = [];
        for ($i=0; $i<count($lastResult); ++$i) {
          $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
          $totalPower += $ally->CurrentPower();
          $uniqueIDs[] = $ally->UniqueID();
        }

        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to put into play");
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYHAND:definedType=Unit;maxCost=" . $totalPower);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $player, "4895747419", 1);
        AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);

        foreach ($uniqueIDs as $uniqueID) {
          AddDecisionQueue("PASSPARAMETER", $player, $uniqueID, 1);
          AddDecisionQueue("MZOP", $player, "DESTROY,$player", 1);
        }
      }
      return $lastResult;
    case "BOLDRESISTANCE":
      if (count($lastResult) == 0) {
        return $lastResult;
      } else if (count($lastResult) > 1) { // If there are multiple units, check if they share a trait
        $firstAlly = new Ally("MYALLY-" . $lastResult[0], $player);
        $traits = CardTraits($firstAlly->CardID());
        for ($i = 1; $i < count($lastResult); $i++) {
          $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
          if (!DelimStringShares($traits, CardTraits($ally->CardID()))) {
            WriteLog("<span style='color:red;'>You must choose units that share the same trait. Reverting gamestate.</span>");
            RevertGamestate();
            return "PASS";
          }
        }
      }

      for ($i=0; $i<count($lastResult); $i++) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        AddCurrentTurnEffect("8022262805", $player, uniqueID:$ally->UniqueID()); //Bold Resistance
      }
      return $lastResult;
    case "MULTIGIVEEXPERIENCE":
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        $ally->Attach("2007868442");//Experience token
      }
      return $lastResult;
    case "MULTIGIVESHIELD":
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        $ally->Attach("8752877738");//Shield Token
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
    case "CALCULATEDLETHALITY":
      $controller = MZPlayerID($player, $lastResult);
      $target = new Ally($lastResult, $controller);
      $numUpgrades = $target->NumUpgrades();
      $target->Destroy();
      if($numUpgrades > 0) {
        for($i=0; $i<$numUpgrades; ++$i) PrependDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
        PrependDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give " . $numUpgrades . " experience");
        PrependDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
      }
      return $lastResult;
    case "L337":
      $target = $lastResult;
      if($target == "PASS") {
        $ally = new Ally($parameterArr[1]);
        $ally->Attach("8752877738");//Shield Token
      } else {
        RescueUnit($player, $target);
      }
      return $lastResult;
    case "XANADUBLOOD":
      if($lastResult == "Resource") {
        WriteLog(CardLink("5818136044", "5818136044") . " exhausts a resource");
        ExhaustResource($player == 1 ? 2 : 1, 1);
      } else {
        WriteLog(CardLink("5818136044", "5818136044") . " exhausts a unit");
        PrependDecisionQueue("MZOP", $player, "REST", 1);
        PrependDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
        PrependDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
      }
      return $lastResult;
    case "THEMARAUDER":
      $cardID = GetMZCard($player, $lastResult);
      if(UnitCardSharesName($cardID, $player))
      {
        $mzArr = explode("-", $lastResult);
        RemoveDiscard($player, $mzArr[1]);
        AddResources($cardID, $player, "GY", "DOWN", isExhausted:1);
      }
      return $lastResult;
    case "ROSETICO":
      $ally = new Ally($lastResult, $player);
      if($ally->HasUpgrade("8752877738"))//Shield token
      {
        $ally->DefeatUpgrade("8752877738");//Shield token
        $ally->Attach("2007868442");//Experience token
        $ally->Attach("2007868442");//Experience token
      }
      return $lastResult;
    case "DOCTORAPHRA":
      $index = GetRandom() % count($lastResult);
      $cardID = RemoveDiscard($player, $lastResult[$index]);
      WriteLog(CardLink($cardID, $cardID) . " is returned by " . CardLink("0254929700", "0254929700"));
      AddHand($player, $cardID);
      return $lastResult;
    case "ENDLESSLEGIONS":
      $resources = &GetResourceCards($player);
      $cardsToPlay = [];
      AddCurrentTurnEffect("5576996578", $player);
      for($i=count($resources)-ResourcePieces(); $i>=0; $i-=ResourcePieces()) {
        if(DefinedTypesContains($resources[$i], "Unit", $player)) {
          $resourceCard = RemoveResource($player, $i);
          $cardsToPlay[] = $resourceCard;
        }
      }
      for($i=0; $i<count($cardsToPlay); ++$i) {
        PlayCard($cardsToPlay[$i], "RESOURCES");
      }
      return 1;
    case "HUNTEROUTCASTSERGEANT":
      $chosenResourceIndex = explode("-", $lastResult)[1];
      $resourceCardID = &GetResourceCards($player)[$chosenResourceIndex];
      $resourceTitle = CardTitle($resourceCardID);
      RevealCards($resourceCardID, $player, "RESOURCES");
      if(CardIsUnique($resourceCardID) && SearchAlliesForTitle($player, $resourceTitle) != "") {
        //Technically only the ally in play needs to be unique, but I'm going to assume that if the resource card is unique
        //and the ally in play shares a name with it then the ally in play is unique.
        //If for some reason cards are printed that make this not guaranteed we can make the check more rigorous.
        MZBounce($player, $lastResult);
        AddTopDeckAsResource($player);
      }
      return 1;
    case "SURVIVORS'GAUNTLET":
      $prefix = str_starts_with($dqVars[1], "MY") ? "MY" : "THEIR";
      AddDecisionQueue("MULTIZONEINDICES", $player, $prefix . "ALLY", 1);
      AddDecisionQueue("MZFILTER", $player, "filterUpgradeEligible={0}", 1);
      AddDecisionQueue("MZFILTER", $player, "index=" . $dqVars[1], 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to move <0> to.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "MOVEUPGRADE", 1);
      return 1;
    case "PREVIZSLA":
      $upgradeID = $dqVars[0];
      $upgradeCost = CardCost($upgradeID);
      if(NumResourcesAvailable($player) >= $upgradeCost) {
        AddDecisionQueue("YESNO", $player, "if you want to pay " . $upgradeCost . " to steal " . CardName($upgradeID), 1);
        AddDecisionQueue("NOPASS", $player, "-", 1);
        AddDecisionQueue("PAYRESOURCES", $player, $upgradeCost . ",1", 1);
        $preIndex = "MYALLY-" . SearchAlliesForCard($player, "3086868510");
        if(DecisionQueueStaticEffect("MZFILTER", $player, "filterUpgradeEligible=" . $upgradeID, $preIndex) != "PASS") {
          AddDecisionQueue("PASSPARAMETER", $player, $preIndex, 1);
          AddDecisionQueue("MZOP", $player, "MOVEUPGRADE", 1);
        }
        else {
          AddDecisionQueue("PASSPARAMETER", $player, $dqVars[1], 1);
          AddDecisionQueue("SETDQVAR", $player, "0", 1);
          AddDecisionQueue("PASSPARAMETER", $player, $upgradeID, 1);
          AddDecisionQueue("OP", $player, "DEFEATUPGRADE", 1);
        }
      }
      return 1;
    case "GENERALRIEEKAN":
      $targetAlly = new Ally($lastResult, $player);
      AddDecisionQueue("PASSPARAMETER", $player, $lastResult, 1);
      if(HasSentinel($targetAlly->CardID(), $player, $targetAlly->Index())) {
        AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
      }
      else {
        AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "3468546373,PLAY", 1);
      }
      return 1;
    case "RULEWITHRESPECT":
      global $CS_UnitsThatAttackedBase;
      $unitsThatAttackedBase = explode(",", GetClassState($player, $CS_UnitsThatAttackedBase));
      $opponent = $player == 1 ? 2: 1;
      for($i = 0; $i < count($unitsThatAttackedBase); ++$i) {
        $targetMZIndex = "THEIRALLY-" . SearchAlliesForUniqueID($unitsThatAttackedBase[$i], $opponent);
        $ally = new Ally($targetMZIndex, $player);
        if($targetMZIndex == "THEIRALLY--1" || $ally->IsLeader()) continue;
        DecisionQueueStaticEffect("MZOP", $player, "CAPTURE," . $lastResult, $targetMZIndex);
      }
      return 1;
    case "ANEWADVENTURE":
      $owner = str_starts_with($lastResult, "MY") ? $player : ($player == 1 ? 2 : 1);
      $lastResult = str_replace("THEIR", "MY", $lastResult);
      $cardID = &GetHand($owner)[explode("-", $lastResult)[1]];
      PrependDecisionQueue("REMOVECURRENTEFFECT", $owner, "4717189843");
      PrependDecisionQueue("MZOP", $owner, "PLAYCARD", 1);
      PrependDecisionQueue("PASSPARAMETER", $owner, $lastResult, 1);
      PrependDecisionQueue("ADDCURRENTEFFECT", $owner, "4717189843", 1);
      PrependDecisionQueue("NOPASS", $owner, "-", 1);
      PrependDecisionQueue("YESNO", $owner, "if you want to play " . CardLink($cardID, $cardID) . " for free");
      return 1;
    case "FLEETLIEUTENANT":
      $ally = new Ally($lastResult, $player);

      if (TraitContains($ally->CardID(), "Rebel", $player)) {
        AddDecisionQueue("PASSPARAMETER", $player, $ally->UniqueID());
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "3038238423,HAND"); //Fleet Lieutenant
      }

      AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex());
      AddDecisionQueue("MZOP", $player, "ATTACK");
      break;
    case "COMMENCEPATROL":
      if ($lastResult == "Yours") {
        $search = "MYDISCARD";
        $where = "MYBOTDECK";
        $filter = "index=" . GetLastDiscardedMZ($player);
      } else {
        $search = "THEIRDISCARD";
        $where = "THEIRBOTDECK";
        $filter = "";
      }
      MZMoveCard($player, $search, $where, filter:$filter, context:"Choose a card to put on the bottom of its owner's deck");
      AddDecisionQueue("CREATEXWING", $player, "-", 1);
      break;
    case "REDEMPTION":
      $ally = new Ally($parameterArr[1]);
      $healedTargets = explode(",", $lastResult);

      $totalHealAmount = 0;
      foreach ($healedTargets as $healedTarget) {
        $healAmount = explode("-", $healedTarget)[0];
        $totalHealAmount += $healAmount;
      }
      if ($totalHealAmount > 0) {
        $ally->DealDamage($totalHealAmount, fromUnitEffect:true);
      }
      break;
    case "YODAOLDMASTER":
      if($lastResult == "Both") {
        WriteLog("Both player drew a card from Yoda, Old Master");
        Draw($player);
        Draw($otherPlayer);
      } else if($lastResult == "Yourself") {
        WriteLog("Player $player drew a card from Yoda, Old Master");
        Draw($player);
      } else {
        WriteLog("Player $otherPlayer drew a card from Yoda, Old Master");
        Draw($otherPlayer);
      }
      break;
    case "PRISONEROFWAR":
      $capturer = new Ally("MYALLY-" . SearchAlliesForUniqueID($dqVars[0], $player), $player);
      if(CardCost($lastResult) < CardCost($capturer->CardID())) {
        CreateBattleDroid($player);
        CreateBattleDroid($player);
      }
      break;
    case "COUNTDOOKU_TWI":
      $power = $lastResult;
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal " . $power . " damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, DealDamageBuilder($power, $player, isUnitEffect:1), 1);
      break;
    case "LETHALCRACKDOWN":
      DealDamageAsync($player, CardPower($lastResult), "DAMAGE", "1389085256", sourcePlayer:$player);
      break;
    case "LUXBONTERI":
      $ally = new Ally($lastResult, MZPlayerID($player, $lastResult));
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose to exhaust or ready " . CardLink($ally->CardID(), $ally->CardID()));
      AddDecisionQueue("BUTTONINPUT", $player, "Exhaust,Ready", 1);
      AddDecisionQueue("PASSPARAMETER", $player, $lastResult, 1);
      AddDecisionQueue("MZOP", $player, ($dqVars[0] == "Exhaust" ? "REST" : "READY"), 1);
      break;
    //Jump to Lightspeed
    case "KIMOGILAHEAVYFIGHTER":
      $targets = explode(",", $lastResult);
      for ($i=0; $i<count($targets); $i++) {
        if (str_starts_with("B", $targets[$i])) continue; // Skip base

        $ally = new Ally($targets[$i]);
        if ($ally->Exists()) {
          $ally->Exhaust();
        }
      }
      break;
    case "BOBA_FETT_LEADER_JTL":
      IndirectDamage("9831674351", $otherPlayer, 1);
      break;
    case "HAN_SOLO_LEADER_JTL":
      $ally = new Ally($lastResult, $player);
      $attackerCost = CardCost($ally->CardID());
      $attackerCostIsOdd = $attackerCost % 2 == 1;
      $odds = $dqVars[0];
      $oddsIsOdd = $odds % 2 == 1;
      if($attackerCostIsOdd && $oddsIsOdd && $attackerCost != $odds) {
        AddCurrentTurnEffect("0616724418", $player);
      }
      AddDecisionQueue("MZOP", $player, "ATTACK", 1);
      return $lastResult;
    case "LEIA_JTL":
      $ally = new Ally($lastResult, $player);
      AddDecisionQueue("PASSPARAMETER", $player, $ally->UniqueID());
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "7924461681,HAND");
      AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex());
      AddDecisionQueue("MZOP", $player, "ATTACK");
      break;
    case "SWEEPTHEAREA":
      $totalCost = 0;
      for($i=count($lastResult)-1; $i>=0; --$i) {
        $owner = MZPlayerID($player, $lastResult[$i]);
        $ally = new Ally($lastResult[$i], $owner);
        $totalCost += CardCost($ally->CardID());
      }
      if($totalCost > 3) {
        WriteLog("<span style='color:red;'>The unit cost was too high. Reverting gamestate.</span>");
        RevertGamestate();
        return "";
      } else {
        for($i=count($lastResult)-1; $i>=0; --$i) {
          $owner = MZPlayerID($player, $lastResult[$i]);
          MZBounce($player, $lastResult[$i]);
        }
      }
      break;
    case "THRAWN_JTL":
      $data = explode(";", $dqVars[1]);
      $target = $data[0];
      $leaderUnitSide = $data[1];
      $trigger = $data[2];
      $dd=DeserializeAllyDestroyData($trigger);
      AllyDestroyedAbility($player, $target, $dd["UniqueID"], $dd["LostAbilities"],$dd["IsUpgraded"],$dd["Upgrades"],$dd["UpgradesWithOwnerData"],
        $dd["LastPower"], $dd["LastRemainingHP"]);
      if($leaderUnitSide == "1") {
        $thrawnLeaderUnit = new Ally("MYALLY-" . SearchAlliesForCard($player, "53207e4131"));
        if($thrawnLeaderUnit->Exists()) {
          $thrawnLeaderUnit->SumNumUses(-1);
        }
      }
      break;
    case "ACKBAR_JTL":
      $ally = new Ally($lastResult);
      CreateXWing($ally->Controller());
      break;
    case "PROFUNDITY":
      $playerChosen = $lastResult == "Yourself" ? $player : $otherPlayer;
      WriteLog("Player $playerChosen discarded a card from Profundity");
      PummelHit($playerChosen);

      if($playerChosen == $otherPlayer && (CountHand($player) < (CountHand($otherPlayer) - 1))) {
        WriteLog("Player $otherPlayer discarded another card from Profundity");
        PummelHit($otherPlayer);
      }
      break;
    case "TURBOLASERSALVO":
      $arena = $dqVars[0];
      $damage = $dqVars[1];
      $otherPlayer = $player == 1 ? 2 : 1;
      DamagePlayerAllies($otherPlayer, $damage, "8174214418", arena:$arena);
      break;
    case "SABINES_MP_CUNNING":
      if($lastResult == "Exhaust_Theirs") ExhaustResource($otherPlayer, 1);
      else if ($lastResult == "Ready_Mine") ReadyResource($player, 1);
      break;
    case "INVISIBLE_HAND_JTL":
      $cardCost = CardCost($lastResult);
      if($cardCost <= 2) {
        AddDecisionQueue("SETDQCONTEXT", $player, "Do you want to play " . CardLink($lastResult, $lastResult) . " for free?");
        AddDecisionQueue("YESNO", $player, "-", 1);
        AddDecisionQueue("NOPASS", $player, "-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $player, "7138400365", 1);
        AddDecisionQueue("FINDINDICES", $player, "MZLASTHAND", 1);
        AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      }
      break;
    case "TRENCH_JTL_OPP":
      if($dqVars[0] == "") break;
      $cards = explode(",",$dqVars[0]);
      $index = array_search($lastResult, $cards);
      unset($cards[$index]);
      array_values($cards);
      $dqVars[0] = implode(",", $cards);
      AddGraveyard($lastResult, $player, "DECK");
      break;
    case "TRENCH_JTL":
      if($dqVars[0] == "") break;
      $cards = explode(",",$dqVars[0]);
      $index = array_search($lastResult, $cards);
      unset($cards[$index]);
      $cardLeft = array_values($cards)[0];
      $dqVars[0] = implode(",", $cards);
      AddHand($player, $lastResult);
      AddGraveyard($cardLeft, $player, "DECK");
      break;
    case "CAT_AND_MOUSE":
      $enemyAlly = new Ally($lastResult);
      $enemyArena = $enemyAlly->CurrentArena();
      $enemyPower = $enemyAlly->CurrentPower();
      $enemyAlly->Exhaust();
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:arena=" . $enemyArena . ";maxAttack=" . $enemyPower);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit in the same arena to ready", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "READY", 1);
      break;
    case "KAZUDA_JTL":
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        AddRoundEffect("c1700fc85b", $player, "c1700fc85b", $ally->UniqueID());
      }
      break;
    case "FOCUS_FIRE":
      $target = new Ally($lastResult);
      $targetArena = CardArenas($target->CardID());
      $allies = &GetAllies($player);
      $damage = 0;
      for ($i = 0; $i < count($allies); $i += AllyPieces()) {
        if (TraitContains($allies[$i], "Vehicle", $player) && CardArenas($allies[$i]) == $targetArena) {
          $ally = new Ally($allies[$i+5], $player);
          $damage += $ally->CurrentPower();
        }
      }
      AddDecisionQueue("PASSPARAMETER", $player, $lastResult, 1);
      AddDecisionQueue("MZOP", $player, DealDamageBuilder($damage, $player, isUnitEffect:1), 1);
      break;
    case "L337_JTL":
      $L3Ally = Ally::FromUniqueId($parameterArr[1]);
      if($lastResult == "YES") {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Vehicle");
        AddDecisionQueue("MZFILTER", $player, "hasPilot=1");
        AddDecisionQueue("PASSREVERT", $player, "-");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a vehicle to move L3's brain to");
        AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
        AddDecisionQueue("SETDQVAR", $player, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $player, $L3Ally->UniqueID());
        AddDecisionQueue("MZOP", $player, "MOVEPILOTUNIT", 1);
      } else if ($lastResult == "NO") {
        DestroyAlly($player, $L3Ally->Index(), skipSpecialCase:true);
      }
      break;
    case "VADER_UNIT_JTL":
      $pingedAlly = new Ally($lastResult);
      $enemyDamage = str_starts_with($lastResult, "MYALLY-") ? false : true;
      $defeated = $pingedAlly->DealDamage(1, enemyDamage: $enemyDamage, fromUnitEffect:true);
      if($defeated) {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("PREPENDLASTRESULT", $player, "MYCHAR-0,THEIRCHAR-0,");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose something to deal 1 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, DealDamageBuilder(1, $player, isUnitEffect:1), 1);
      }
      break;
    case "PAY_READY_TAX":
      $tax = $parameterArr[1];
      if(NumResourcesAvailable($player) >= $tax) {
        $affectedUid = $parameterArr[2];
        $ally = Ally::FromUniqueId($affectedUid);
        $cardID = $ally->CardID();
        AddDecisionQueue("YESNO", $player, "Pay $tax resources to ready " . CardLink($cardID, $cardID) . "?", 1);
        AddDecisionQueue("NOPASS", $player, "-", 1);
        AddDecisionQueue("PAYRESOURCES", $player, $tax, 1);
        AddDecisionQueue("SPECIFICCARD", $player, "PAID_READY_TAX,$affectedUid", 1);
      }
      break;
    case "PAID_READY_TAX":
      Ally::FromUniqueId($parameterArr[1])->Ready(resolvedSpecialCase:true);
      break;
    case "HEARTLESSTACTICS":
      $ally = Ally::FromUniqueId($lastResult);
      if(!$ally->IsLeader() && $ally->CurrentPower() == 0) {
        AddDecisionQueue("SETDQCONTEXT", $player, "Bounce " . CardLink($ally->CardID(), $ally->CardID()) . "?");
        AddDecisionQueue("YESNO", $player, "-", 1);
        AddDecisionQueue("NOPASS", $player, "-", 1);
        AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex(), 1);
        AddDecisionQueue("MZOP", $player, "BOUNCE", 1);
      }
      break;
    case "SYSTEMSHOCK":
      $targetAllyUID = Ally::FromUniqueId($lastResult)->UniqueID();
      AddDecisionQueue("PASSPARAMETER", $player, $targetAllyUID, 1);
      AddDecisionQueue("SETDQVAR", $player, "0", 1);
      AddDecisionQueue("MZOP", $player, "GETUPGRADES", 1);
      AddDecisionQueue("FILTER", $player, "LastResult-exclude-isLeader", 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a non-leader upgrade to defeat.", 1);
      AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
      AddDecisionQueue("OP", $player, "DEFEATUPGRADE", 1);
      AddDecisionQueue("UNIQUETOMZ", $player, $targetAllyUID, 1);
      AddDecisionQueue("MZOP", $player, DealDamageBuilder(1, $player), 1);
      break;
    case "THEREISNOESCAPE":
      foreach($lastResult as $index) {
        $mzArr = explode("-", $index);
        $allyPlayer = $mzArr[0] == "MYALLY" ? $player : $otherPlayer;
        $ally = new Ally("MYALLY-" . $mzArr[1], $allyPlayer);
        AddRoundEffect("9184947464", $allyPlayer, "PLAY", $ally->UniqueID());
      }
      break;
    case "UWINGLANDER":
      $ally = Ally::FromUniqueId($parameterArr[1]);
      AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex(), 1);
      AddDecisionQueue("SETDQVAR", $player, "1", 1);
      if(PilotingCost($lastResult) > -1) {
        global $CS_PlayedAsUpgrade;
        SetClassState($player, $CS_PlayedAsUpgrade, 1);
        AddDecisionQueue("PASSPARAMETER", $player, "1", 1);
        AddDecisionQueue("SETDQVAR", $player, "2", 1);//set movingPilot to true
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Vehicle", 1);
        AddDecisionQueue("MZFILTER", $player, "hasPilot=1", 1);
        AddDecisionQueue("PASSREVERT", $player, "-");
      } else {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Vehicle");
        AddDecisionQueue("MZFILTER", $player, "filterUpgradeEligible={0}", 1);
      }
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to move <0> to.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "MOVEUPGRADE", 1);
    //SpecificCardLogic End
    default: return "";
  }
}

?>
