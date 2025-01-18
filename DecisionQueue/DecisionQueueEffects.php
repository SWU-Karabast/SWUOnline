<?php

function ModalAbilities($player, $card, $lastResult)
{
  global $combatChain, $defPlayer;
  switch($card)
  {
    case "K2SO":
      $otherPlayer = ($player == 1 ? 2 : 1);
      switch($lastResult) {
        case 0: // Deal damage
          DealDamageAsync($otherPlayer, 3, "DAMAGE", "3232845719"); 
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
          AddDecisionQueue("MZOP", $player, "DESTROY", 1);
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
          AddDecisionQueue("MZFILTER", $player, "definedType=Leader");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to damage");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "{0}", 1);
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
          AddDecisionQueue("MZFILTER", $player, "definedType=Leader");
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
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,4", 1);
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

function SpecificCardLogic($player, $parameter, $lastResult)
{
  global $dqVars;
  $parameterArr = explode(",", $parameter);
  $card = $parameterArr[0];
  switch($card)
  {
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
      $otherPlayer = $player == 1 ? 2 : 1;
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
      $otherPlayer = $player == 1 ? 2 : 1;
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
      DealDamageAsync($player, CardCost($lastResult), "DAMAGE", "5494760041");
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
      MZMoveCard($player, "MYDISCARD:definedType=Unit", "MYHAND", may:true, context:"Choose ONLY units defeated this phase then pass");
      AddDecisionQueue("SPECIFICCARD", $player, "THEEMPERORSLEGION", 1);
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
      $owner = MZPlayerID($player, $lastResult);
      $ally = new Ally($lastResult, $owner);
      $upgrades = $ally->GetUpgrades(true);
      for($i=0; $i<count($upgrades); $i+=SubcardPieces()) {
        $ally->RemoveSubcard($upgrades[$i]);
        if(!IsToken($upgrades[$i])) AddHand($upgrades[$i+1], $upgrades[$i]);
      }
      /*$ally->ClearSubcards();
      for($i=0; $i<count($upgradesReturned); ++$i) {
        UpgradeDetached($upgradesReturned[$i], $ally->PlayerID(), "MYALLY-" . $ally->Index());
      }*/
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
          AddDecisionQueue("MZOP", $player, "DESTROY", 1);
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
      $owner = MZPlayerID($player, $lastResult);
      $target = new Ally($lastResult, $owner);
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
      AddDecisionQueue("MZFILTER", $player, "canAttach={0}", 1);
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
        if(DecisionQueueStaticEffect("MZFILTER", $player, "canAttach=" . $upgradeID, $preIndex) != "PASS") {
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
        if($targetMZIndex == "THEIRALLY--1" || IsLeader(GetMZCard($player, $targetMZIndex))) continue;
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
    case "YODAOLDMASTER":
      if($lastResult == "Both") {
        WriteLog("Both player drew a card from Yoda, Old Master");
        $otherPlayer = $player == 1 ? 2 : 1;
        Draw($player);
        Draw($otherPlayer);
      } else if($lastResult == "Yourself") {
        WriteLog("Player $player drew a card from Yoda, Old Master");
        Draw($player);
      } else {
        $otherPlayer = $player == 1 ? 2 : 1;
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
      AddDecisionQueue("MZOP", $player, "DEALDAMAGE," . $power, 1);
      break;
    case "LETHALCRACKDOWN":
      DealDamageAsync($player, CardPower($lastResult), "DAMAGE", "1389085256");
      break;
    case "TWI_PALPATINE_HERO":
      Draw($player);
      Restore(2, $player);
      $char = &GetPlayerCharacter($player);
      $char[CharacterPieces()] = "ad86d54e97";
      break;
    case "TWI_DARTHSIDIOUS_HERO":
      CreateCloneTrooper($player);
      DealDamageAsync(($player == 1 ? 2 : 1), 2, "DAMAGE", "ad86d54e97");
      $char = &GetPlayerCharacter($player);
      $char[CharacterPieces()] = "0026166404"; // Chancellor Palpatine Leader
      break;
    case "LUXBONTERI":
      $ally = new Ally($lastResult, MZPlayerID($player, $lastResult));
      if($ally->IsExhausted()) $ally->Ready();
      else $ally->Exhaust();
      break;
    default: return "";
  }
}

?>
