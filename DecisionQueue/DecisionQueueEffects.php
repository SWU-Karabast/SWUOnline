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
          PrependDecisionQueue("SWAPTURN", $player, "-");
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
            MZChooseAndDestroy($player, "MYALLY:maxHealth=3&THEIRALLY:maxHealth=3", may:true);
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
            DefeatUpgrade($player, may:true);
            DefeatUpgrade($player, may:true);
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
    case "LETTHEWOOKIEWIN":
      switch($lastResult) {
        case "Ready_Resources":
          ReadyResource($player, 6);
          break;
        case "Ready_Unit":
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to attack with");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "READY", 1);
          AddDecisionQueue("MZOP", $player, "ADDEFFECT,7578472075", 1);
          AddDecisionQueue("MZOP", $player, "ATTACK", 1);
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
      $searchLeftovers = explode(",", $deck->Top(true, 10 - count($cardArr)));
      shuffle($searchLeftovers);
      for($i=0; $i<count($searchLeftovers); ++$i) {
        AddBottomDeck($searchLeftovers[$i], $player, $parameter);
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
      $searchLeftovers = explode(",", $deck->Top(true, 10 - count($cardArr)));
      shuffle($searchLeftovers);
      for($i=0; $i<count($searchLeftovers); ++$i) {
        AddBottomDeck($searchLeftovers[$i], $player, $parameter);
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
    case "MEDALCEREMONY":
      if($lastResult == "PASS") {
        return $lastResult;
      }
      for($i=0; $i<count($lastResult); ++$i) {
        $ally = new Ally("MYALLY-" . $lastResult[$i], $player);
        $ally->Attach("2007868442");//Experience token
      }
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
        $ally = new Ally("MYALLY-" . SearchAlliesForCard($player, "9552605383"), $player);
        if($ally)
        $ally->Attach("8752877738");//Shield Token
      } else {
        $owner = MZPlayerID($player, $target);
        $ally = new Ally($target, $owner);
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
        $mzArr = explode(",", $lastResult);
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
    default: return "";
  }
}

?>
