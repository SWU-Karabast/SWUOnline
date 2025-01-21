<?php

include "Search.php";
include "CardLogic.php";
include "AuraAbilities.php";
include "ItemAbilities.php";
include "AllyAbilities.php";
include "PermanentAbilities.php";
include "CharacterAbilities.php";
include "WeaponLogic.php";
include "MZLogic.php";
include "Classes/Deck.php";
include "Classes/Ally.php";
include "DecisionQueue/DecisionQueueEffects.php";
include "CurrentEffectAbilities.php";
include "CombatChain.php";
include_once "WriteLog.php";


function DecisionQueueStaticEffect($phase, $player, $parameter, $lastResult)
{
  global $redirectPath, $playerID, $gameName;
  global $currentPlayer, $combatChain, $defPlayer;
  global $combatChainState;
  global $defCharacter, $otherPlayer;
  global $CS_NextNAACardGoAgain, $CCS_AttackTarget, $CS_NumLeftPlay;
  global $CS_LayerTarget, $dqVars, $mainPlayer, $lastPlayed, $dqState, $CS_AbilityIndex, $CS_CharacterIndex;
  global $CS_AdditionalCosts, $CS_AlluvionUsed, $CS_MaxQuellUsed, $CS_DamageDealt, $CS_ArcaneTargetsSelected, $inGameStatus;
  global $CS_ArcaneDamageDealt, $MakeStartTurnBackup, $CCS_AttackTargetUID, $chainLinkSummary, $chainLinks, $MakeStartGameBackup, $CCS_MultiAttackTargets;
  $rv = "";

  switch($phase) {
    case "SEARCHDECKTOPX":
      //The parameter for this is: the number of cards to search, the number of cards that may be chosen, then a list of filters(delimited with | or & for OR or AND logic(combinations of both are not supported)) using the syntax for the FILTER DecisionQueue option(excluding the initial source part), with each of these three(number to search, max choosable, filter list) separated by a semicolon.
      //For example, the parameter for Darth Vader unit's search("Search the top 10 cards of your deck for any number of Villainy Aspect units with combined cost 3 or less") would be:
      //"10;99;include-aspect-Villainy&include-definedType-Unit&include-maxCost-3"(the validity of the selection(in this case the combined cost) can be further checked after this step if necessary).
      $paramArray = explode(";", $parameter);
      if(count($paramArray) != 3) {WriteLog("SEARCHDECKTOPX called incorrectly. Please file a bug report."); return "PASS";}
      $numToSearch = $paramArray[0];
      $numToAllowChoosing = $paramArray[1];
      $deckIndicesToShow = DecisionQueueStaticEffect("FINDINDICES", $player, "DECKTOPXINDICES," . $numToSearch, "");
      if($deckIndicesToShow == "PASS") return "PASS";

      if(substr_count($paramArray[2], "|") > 0) { //OR: Any card that matches at least one filter should be selectable.
        $filterArray = explode("|", $paramArray[2]);
        $chooseableDeckIndices = [];
        foreach($filterArray as $filter) {
          $choosableDeckIndices .= DecisionQueueStaticEffect("FILTER", $player, "Deck-" . $filter, $deckIndicesToShow) . ",";
        }
        $choosableDeckIndicesArray = array_unique(explode(",", $choosableDeckIndices));
        $choosableDeckIndicesArray = array_filter($choosableDeckIndicesArray, function($a){return $a != "PASS" && $a != "";});
        natsort($choosableDeckIndicesArray);
        $choosableDeckIndices = implode(",", $choosableDeckIndicesArray);
      }

      else { //AND: Only cards that match every filter should be selectable.
        $filterArray = explode("&", $paramArray[2]);
        $choosableDeckIndices = $deckIndicesToShow;
        foreach($filterArray as $filter) {
          $choosableDeckIndices = DecisionQueueStaticEffect("FILTER", $player, "Deck-" . $filter, $choosableDeckIndices);
          if($choosableDeckIndices == "PASS") {$choosableDeckIndices = ""; break;}
        }
      }

      PrependDecisionQueue("PROCESSSEARCH", $player, $numToSearch); //We'll need to know how many cards were searched to figure out how many to shuffle and put on the bottom later.
      PrependDecisionQueue("MULTICHOOSESEARCHTARGETS", $player,
        $numToAllowChoosing . "-" . $deckIndicesToShow . "-" . "0-" . $choosableDeckIndices, //The MULTICHOOSE system(case 19 in ProcessInput()) is set up to use three params(- delimited): $maxSelect, $options(usually choosable indices, but in this case just indices to show), and $minSelect. I want to extend this with choosable indices, so they come after, at index 3. $minSelect should always be 0 for a search as a player can always choose to find nothing from a search(Comp Rules v2.0 section 8.27.1).
        0, 1);
      if($dqState[4] != "-") PrependDecisionQueue("SETDQCONTEXT", $player, $dqState[4]); //Pass on the DQCONTEXT message here so individual card logic can actually set it for the search screen.
      return "";
    case "PROCESSSEARCH":
      $searchLeftoversCount = $parameter - count($lastResult);
      $searchTargets = DecisionQueueStaticEffect("MULTIREMOVEDECK", $player, "-", $lastResult);
      $searchLeftovers = DecisionQueueStaticEffect("FINDINDICES", $player, "DECKTOPXREMOVE," . $searchLeftoversCount, "-");
      DecisionQueueStaticEffect("ALLRANDOMBOTTOM", $player, "-", $searchLeftovers);
      return $searchTargets == "" ? "PASS" : $searchTargets;
    case "FINDINDICES":
      UpdateGameState($currentPlayer);
      BuildMainPlayerGamestate();
      $parameters = explode(",", $parameter);
      $parameter = $parameters[0];
      if(count($parameters) > 1) $subparam = $parameters[1];
      else $subparam = "";
      switch($parameter) {
        case "MYDISCARD":
        case "THEIRDISCARD":
          $p = $parameter == "MYDISCARD" ? $player : ($player == 1 ? 2 : 1);

          if ($subparam == "") {
            $discard = &GetDiscard($p);
            $rv = GetIndices(count($discard), pieces:DiscardPieces());
          } else {
            $rv = SearchDiscardForCard($p, $subparam);
          }
          break;
        case "GETINDICES": $rv = GetIndices($subparam); break;
        case "ARCANETARGET": $rv = GetArcaneTargetIndices($player, $subparam); break;
        case "DAMAGEPREVENTION":
          $rv = GetDamagePreventionIndices($player);
          break;
        case "DAMAGEPREVENTIONTARGET": $rv = GetDamagePreventionTargetIndices(); break;
        case "DECK": $rv = SearchDeck($player); break;
        case "TOPDECK":
          $deck = &GetDeck($player);
          if(count($deck) > 0) $rv = "0";
          break;
        case "DECKTOPXINDICES":
          $deck = &GetDeck($player);
          for($i=0; $i<$subparam && $i<count($deck); ++$i)
          {
            if($rv != "") $rv .= ",";
            $rv .= $i;
          }
          break;
        case "GY":
          $discard = &GetDiscard($player);
          $rv = GetIndices(count($discard), pieces:DiscardPieces());
          break;
        case "STORMTYRANTSEYE":
          $deck = &GetDeck($player);
          $toReveal = "";
          $found = false;
          for($i=0; $i<count($deck) && !$found; ++$i)
          {
            if($toReveal != "") $toReveal .= ",";
            $toReveal .= $deck[$i];
            if($rv != "") $rv .= ",";
            $rv .= $i;
            if(CardElement($deck[$i]) == "ARCANE") $found = true;
          }
          RevealCards($toReveal);
          LoseHealth(SearchCount($rv), $player);
          break;
        case "DECKTOPXREMOVE":
          $deck = new Deck($player);
          $rv = $deck->Top(true, $subparam);
          break;
        case "PERMSUBTYPE":
          if($subparam == "Aura") $rv = SearchAura($player, "", $subparam);
          else $rv = SearchPermanents($player, "", $subparam);
          break;
        case "HAND":
          $hand = &GetHand($player);
          $rv = GetIndices(count($hand));
          break;
        case "HANDASPECT":
          $rv = SearchHand($player, aspect:$subparam);
          break;
        case "MATERIAL":
          $material = &GetMaterial($player);
          $rv = GetIndices(count($material));
          break;
        //This one requires CHOOSEMULTIZONECANCEL
        case "HANDPITCH": $rv = SearchHand($player, "", "", -1, -1, "", "", false, false, $subparam); break;
        case "HANDACTIONMAXCOST": $rv = CombineSearches(SearchHand($player, "A", "", $subparam), SearchHand($player, "AA", "", $subparam)); break;
        case "MULTIHAND":
          $hand = &GetHand($player);
          $rv = count($hand) . "-" . GetIndices(count($hand));
          break;
        case "MULTIHANDAA":
          $search = SearchHand($player, "AA");
          $rv = SearchCount($search) . "-" . $search;
          break;
        case "ARSENAL":
          $arsenal = &GetArsenal($player);
          $rv = GetIndices(count($arsenal), 0, ArsenalPieces());
          break;
        //These are needed because MZ search doesn't have facedown parameter
        case "ARSENALDOWN": $rv = GetArsenalFaceDownIndices($player, $subparam); break;
        case "ARSENALUP": $rv = GetArsenalFaceUpIndices($player, $subparam); break;
        case "ITEMSMAX": $rv = SearchItems($player, "", "", $subparam); break;
        case "EQUIP": $rv = GetEquipmentIndices($player); break;
        case "EQUIP0": $rv = GetEquipmentIndices($player, 0); break;
        case "EQUIPCARD": $rv = FindCharacterIndex($player, $subparam); break;
        case "EQUIPONCC": $rv = GetEquipmentIndices($player, onCombatChain:true); break;
        case "CCAA": $rv = SearchCombatChainLink($player, "AA"); break;
        case "CCDEFLESSX": $rv = SearchCombatChainLink($player, "", "", -1, -1, "", "", false, false, -1, false, -1, $subparam); break;
        case "HANDAAMAXCOST": $rv = SearchHand($player, "AA", "", $subparam); break;
        case "MYHANDAA": $rv = SearchHand($player, "AA"); break;
        case "MAINHAND":
          $hand = &GetHand($mainPlayer);
          $rv = GetIndices(count($hand)); break;
        case "BANISHTYPE": $rv = SearchBanish($player, $subparam); break;
        case "UNITS":
          $allies = &GetAllies($player);
          $rv = GetIndices(count($allies), 0 , AllyPieces());
          break;
        case "ALLTHEIRUNITSMULTI":
          $allies = &GetAllies($player == 1 ? 2 : 1);
          $rv = count($allies) . "-" . GetIndices(count($allies), 0 , AllyPieces());
          break;
        case "ALLTHEIRGROUNDUNITSMULTI":
          $allies = &GetAllies($player == 1 ? 2 : 1);
          $groundAllies = "";
          $groundCount = 0;
          for($i = 0; $i < count($allies); $i+=AllyPieces()) {
            if(ArenaContains($allies[$i], "Ground", $player)) {
              if($groundAllies != "") $groundAllies .= ",";
              $groundAllies .= $i;
              $groundCount++;
            }
          }
          $rv = $groundCount . "-" . $groundAllies;
          break;
        case "GYTYPE": $rv = SearchDiscard($player, $subparam); break;
        case "GYAA": $rv = SearchDiscard($player, "AA"); break;
        case "GYNAA": $rv = SearchDiscard($player, "A"); break;
        case "GYCLASSAA": $rv = SearchDiscard($player, "AA", "", -1, -1, $subparam); break;
        case "GYCLASSNAA": $rv = SearchDiscard($player, "A", "", -1, -1, $subparam); break;
        case "GYCARD": $rv = SearchDiscardForCard($player, $subparam); break;
        case "WEAPON": $rv = WeaponIndices($player, $player, $subparam); break;
        case "HEAVE": $rv = HeaveIndices(); break;
        case "AURACLASS": $rv = SearchAura($player, "", "", -1, -1, $subparam); break;
        case "DECKAURAMAXCOST": $rv = SearchDeck($player, "", "Aura", $subparam); break;
        case "QUELL": $rv = QuellIndices($player); break;
        case "MZLASTHAND":
          $hand = &GetHand($player);
          if(count($hand) > 0) $rv = "MYHAND-" . count($hand) - HandPieces();
          break;
        default: $rv = ""; break;
      }
      return ($rv == "" ? "PASS" : $rv);
    case "MULTIZONEINDICES":
      $rv = SearchMultizone($player, $parameter);
      return ($rv == "" ? "PASS" : $rv);
    case "MZMYDECKTOPX":
      $deck = &GetDeck($player);
      $rv = "";
      for($i=0; $i<$parameter; ++$i) {
        if($rv != "") $rv .= ",";
        $rv .= "MYDECK-" . $i*DeckPieces();
      }
      return ($rv == "" ? "PASS" : $rv);
    case "PUTPLAY":
      $subtype = CardSubType($lastResult);
      if($subtype == "Item") {
        PutItemIntoPlayForPlayer($lastResult, $player, ($parameter != "-" ? $parameter : 0));
      }
      else if(IsAlly($lastResult))
      {
        PlayAlly($lastResult, $player);
        PlayAbility($lastResult, "-", 0);
      }
      else {
        PlayAura($lastResult, $player);
      }
      return $lastResult;
    case "PLAYALLY":
      $params = explode(";", $parameter);
      $subCards = "-";
      $from = "-";
      $owner = null;
      $cloned = false;
      $playCardEffect = false;
      for ($i = 0; $i < count($params); $i++) {
        $param = explode("=", $params[$i]);
        switch ($param[0]) {
          case "subCards": $subCards = $param[1]; break;
          case "from": $from = $param[1]; break;
          case "owner": $owner = $param[1]; break;
          case "cloned": $cloned = in_array($param[1], [1, "true"]); break;
          case "playCardEffect": $playCardEffect = in_array($param[1], [1, "true"]); break;
          default: break;
        }
      }
      PlayAlly($lastResult, $player, $subCards, $from, $owner, $cloned, $playCardEffect);
      return $lastResult;
    case "DRAW":
      return Draw($player);
    case "MULTIBANISH":
      if($lastResult == "") return $lastResult;
      $cards = explode(",", $lastResult);
      $params = explode(",", $parameter);
      if(count($params) < 3) $params[] = "";
      $mzIndices = "";
      for ($i = 0; $i < count($cards); ++$i) {
        $index = BanishCardForPlayer($cards[$i], $player, $params[0], $params[1], $params[2]);
        if ($mzIndices != "") $mzIndices .= ",";
        $mzIndices .= "BANISH-" . $index;
      }
      $dqState[5] = $mzIndices;
      return $lastResult;
    case "REMOVECOMBATCHAIN":
      $cardID = $combatChain[$lastResult];
      RemoveCombatChain($lastResult);
      return $cardID;
    case "COMBATCHAINPOWERMODIFIER":
      CombatChainPowerModifier($lastResult, $parameter);
      return $lastResult;
    case "COMBATCHAINDEFENSEMODIFIER":
      if($parameter < 0) {
        $defense = BlockingCardDefense($lastResult);
        if($parameter < $defense * -1) $parameter = $defense * -1;
      }
      $combatChain[$lastResult+6] += $parameter;
      return $lastResult;
    case "COMBATCHAINCHARACTERDEFENSEMODIFIER":
      $character = &GetPlayerCharacter($player);
      $index = FindCharacterIndex($player, $combatChain[$parameter]);
      $character[$index + 4] += $lastResult;
      return $lastResult;
    case "REMOVEMYHAND":
      $hand = &GetHand($player);
      $cardID = $hand[$lastResult];
      unset($hand[$lastResult]);
      $hand = array_values($hand);
      return $cardID;
    case "HANDCARD":
      $hand = &GetHand($player);
      $cardID = $hand[$lastResult];
      return $cardID;
    case "MULTIBANISHSOUL":
      if(!is_array($lastResult)) $lastResult = explode(",", $lastResult);
      for($i = count($lastResult)-1; $i >= 0; --$i) BanishFromSoul($player, $lastResult[$i]);
      return $lastResult;
    case "ADDHAND":
      AddPlayerHand($lastResult, $player, "-");
      return $lastResult;
    case "ADDMEMORY":
      AddMemory($lastResult, $player, "HAND", "DOWN");
      return $lastResult;
    case "ADDARSENAL":
      $params = explode("-", $parameter);
      $from = (count($params) > 0 ? $params[0] : "-");
      $facing = (count($params) > 1 ? $params[1] : "DOWN");
      AddArsenal($lastResult, $player, $from, $facing);
      return $lastResult;
    case "TURNARSENALFACEUP":
      $arsenal = &GetArsenal($player);
      $arsenal[$lastResult + 1] = "UP";
      return $lastResult;
    case "REMOVEARSENAL":
      $index = $lastResult;
      $arsenal = &GetArsenal($player);
      $cardToReturn = $arsenal[$index];
      for($i = $index + ArsenalPieces() - 1; $i >= $index; --$i) {
        unset($arsenal[$i]);
      }
      $arsenal = array_values($arsenal);
      return $cardToReturn;
    case "MULTIADDHAND":
      $cards = explode(",", $lastResult);
      $hand = &GetHand($player);
      $log = "";
      for($i = 0; $i < count($cards); ++$i) {
        if($parameter == "1") {
          if($log != "") $log .= ", ";
          if($i != 0 && $i == count($cards) - 1) $log .= "and ";
          $log .= CardLink($cards[$i], $cards[$i]);
        }
        $hand[] = $cards[$i];
      }
      if($log != "") WriteLog($log . " added to hand");
      return $lastResult;
    case "MULTIREMOVEHAND":
      $cards = "";
      $hand = &GetHand($player);
      if(!is_array($lastResult)) $lastResult = explode(",", $lastResult);
      for($i = 0; $i < count($lastResult); ++$i) {
        if($cards != "") $cards .= ",";
        $cards .= $hand[$lastResult[$i]];
        unset($hand[$lastResult[$i]]);
      }
      $hand = array_values($hand);
      return $cards;
    case "DESTROYCHARACTER":
      DestroyCharacter($player, $lastResult);
      return $lastResult;
    case "DESTROYEQUIPDEF0":
      $character = &GetPlayerCharacter($defPlayer);
      if(BlockValue($character[$lastResult]) + $character[$lastResult+4] <= 0) {
        WriteLog(CardLink($character[$lastResult], $character[$lastResult]) . " was destroyed");
        DestroyCharacter($defPlayer, $lastResult);
      }
      return "";
    case "CHARFLAGDESTROY":
      $character = &GetPlayerCharacter($player);
      $character[$parameter+7] = 1;
      return $lastResult;
    case "ADDCHARACTEREFFECT":
      $characterEffects = &GetCharacterEffects($player);
      array_push($characterEffects, $lastResult, $parameter);
      return $lastResult;
    case "ADDMZBUFF":
      $lrArr = explode("-", $lastResult);
      $characterEffects = &GetCharacterEffects($player);
      array_push($characterEffects, $lrArr[1], $parameter);
      return $lastResult;
    case "ADDMZUSES":
      $lrArr = explode("-", $lastResult);
      switch($lrArr[0]) {
        case "MYCHAR": case "THEIRCHAR": AddCharacterUses($player, $lrArr[1], $parameter); break;
        case "MYALLY": case "THEIRALLY":
          $ally = new Ally($lastResult, $player);
          $ally->ModifyUses($parameter);
          break;
        default: break;
      }
      return $lastResult;
    case "ATTACKEROP":
      $mzID = AttackerMZID($currentPlayer);
      $type = GetMZType($mzID);
      switch($parameter) {
        case "ADDDURABILITY":
          if($type == "CHAR") {
            $character = &GetPlayerCharacter($currentPlayer);
            ++$character[GetMZIndex($mzID) + 2];
          }
          break;
        default: break;
      }
      return $lastResult;
    case "UIDOP":
      $parameterArr = explode(",", $parameter);
      switch ($parameterArr[0]) {
        case "REVERTCONTROL": // Revert control of a unit to its owner
          $ally = new Ally($lastResult);
          if ($ally->Exists() && $ally->Controller() != $ally->Owner()) {
            $owner = $ally->Owner();
            AllyTakeControl($owner, $ally->Index());
            WriteLog("Reverted control of " . CardLink($ally->CardID(), $ally->CardID()) . "back to player $owner");
          } else {
            return "PASS";
          }
      }
      return $lastResult;
    case "MZOP":
      $parameterArr = explode(",", $parameter);
      switch ($parameterArr[0])
      {
        case "FREEZE": MZFreeze($lastResult); break;
        case "GAINCONTROL": MZGainControl($player, $lastResult); break;
        case "GETCARDID": return GetMZCard($player, $lastResult);
        case "GETCARDCOST": return CardCost($lastResult);
        case "GETCARDTITLE":
          $cardTitle = CardTitle($lastResult);
          return GamestateSanitize($cardTitle);
        case "GETCARDINDEX": $mzArr = explode("-", $lastResult); return $mzArr[1];
        case "GETUNIQUEID":
          $mzArr = explode("-", $lastResult);
          if(str_starts_with($mzArr[0], "THEIR")) $zone = &GetMZZone(($player == 1 ? 2 : 1), $mzArr[0]);
          else $zone = &GetMZZone($player, $mzArr[0]);
          switch($mzArr[0]) {
            case "ALLY": case "MYALLY": case "THEIRALLY": return $zone[$mzArr[1] + 5];
            case "BANISH": case "MYBANISH": case "THEIRBANISH": return $zone[$mzArr[1] + 2];
            default: return "-1";
          }
        case "GETHEALTH":
          $ally = new Ally($lastResult);
          return $ally->Health();
        case "GETDAMAGE":
          $ally = new Ally($lastResult);
          return $ally->Damage();
        case "GETARENA": return CardArenas(GetMZCard($player, $lastResult));
        case "BOUNCE": return MZBounce($player, $lastResult);
        case "COLLECTBOUNTIES":
          $mzArr = explode("-", $lastResult);
          $ally = new Ally($lastResult);
          CollectBounties($mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1), $ally->CardID(), $ally->UniqueID(), $ally->IsExhausted(), $ally->Owner(), $ally->GetUpgrades());
          return $lastResult;
        case "SINK": MZSink($player, $lastResult); return $lastResult;
        case "SUPPRESS": MZSuppress($player, $lastResult); return $lastResult;
        case "REST": MZRest($player, $lastResult); return $lastResult;
        case "READY": MZWakeUp($player, $lastResult); return $lastResult;
        case "PLAYCARD": return MZPlayCard($player, $lastResult);
        case "ATTACK": return MZAttack($player, $lastResult);
        case "ADDHEALTH": MZAddHealth($player, $lastResult, count($parameterArr) > 1 ? $parameterArr[1] : 1); return $lastResult;
        case "ENDCOMBAT": MZEndCombat($player, $lastResult); return $lastResult;
        case "RESTORE":
          $mzArr = explode("-", $lastResult);
          if($mzArr[0] == "MYCHAR") {
            Restore(count($parameterArr) > 1 ? $parameterArr[1] : 1, $player);
          } else if($mzArr[0] == "THEIRCHAR") {
            Restore(count($parameterArr) > 1 ? $parameterArr[1] : 1, $player == 1 ? 2 : 1);
          } else if($mzArr[0] == "MYALLY") {
            MZHealAlly($player, $lastResult, count($parameterArr) > 1 ? $parameterArr[1] : 1);
          } else if($mzArr[0] == "THEIRALLY") {
            MZHealAlly($player == 1 ? 2 : 1, $lastResult, count($parameterArr) > 1 ? $parameterArr[1] : 1);
          }
          return $lastResult;
        case "CHANGEATTACKTARGET": SetAttackTarget($lastResult); return $lastResult;
        case "MULTICHOOSEATTACKTARGETS":
          $numOptions=count(explode(",",$lastResult));
          if($numOptions===1) {
            AddDecisionQueue("PASSPARAMETER", $player, "THEIRALLY-$lastResult", 1);
            AddDecisionQueue("PROCESSATTACKTARGET", $player, "-", 1);
            AddDecisionQueue("PASSPARAMETER", $player, 0, 1);
          } else {
            AddDecisionQueue("PREPENDLASTRESULT", $player, "$numOptions-", 1);
            AddDecisionQueue("SETDQCONTEXT", $player, "Choose up to $numOptions units to target");
            AddDecisionQueue("MULTICHOOSETHEIRUNIT", $player, "<-", 1);
            AddDecisionQueue("IMPLODELASTRESULT", $player, ",", 1);
            AddDecisionQueue("SETCOMBATCHAINSTATE", $player, $CCS_MultiAttackTargets, 1);
            AddDecisionQueue("PROCESSATTACKTARGET", $player, "MULTI", 1);
            //since we couldn't use the ability names for this, we need to pass 0 to the PlayCard function
            AddDecisionQueue("PASSPARAMETER", $player, 0, 1);
          }
          break;
        case "DEALDAMAGE":
          // Parameter structure:
          // 1. DEALDAMAGE
          // 2. Damage amount
          // 3. Player causing the damage
          // 4. Indicates if the damage is caused by unit effects (1 = yes, 0 = no)
          // 5. Indicates if the damage is preventable (1 = yes, 0 = no)
          $targetArr = explode("-", $lastResult);
          $targetPlayer = ($targetArr[0] == "MYCHAR" || $targetArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1));
          $preventable = count($parameterArr) > 4 ? $parameterArr[4] == 1 : 1;
          if($targetArr[0] == "MYALLY" || $targetArr[0] == "THEIRALLY") {
            $isAttackTarget = GetAttackTarget() == $lastResult;
            $isAttacker = AttackerMZID($player) == $lastResult;
            $ally = new Ally($lastResult);
            $attackerHasOverwhelm = HasOverwhelm($ally->CardID(), $ally->Controller(), $ally->Index());
            $destroyed = $ally->DealDamage($parameterArr[1],
                enemyDamage:(count($parameterArr) > 2 && $parameterArr[2] != $targetPlayer),
                fromUnitEffect: count($parameterArr) > 3 && (bool)$parameterArr[3],
                preventable: $preventable);

            if($destroyed) {
              if(($isAttackTarget || $isAttacker) && !$attackerHasOverwhelm) CloseCombatChain();
              return "";
            }
          } else {
            PrependDecisionQueue("TAKEDAMAGE", $targetPlayer, $parameterArr[1]);
            PrependDecisionQueue("PASSPARAMETER", $targetPlayer, "0");
          }
          return $lastResult;
        case "REDUCEHEALTH":
          MZAddHealth($player, $lastResult, count($parameterArr) > 1 ? -1 * $parameterArr[1] : 1); return $lastResult;
        case "DESTROY":
          $ally = new Ally($lastResult);
          $id = $ally->CardID();
          $ally->Destroy();
          return $id;
        case "EXPLOIT":
          global $CS_PlayedWithExploit;
          SetClassState($player, $CS_PlayedWithExploit, 1);
          $exploitedAllies = (array)$dqVars[0];
          if ($exploitedAllies == [])
          {
            return;
          }
          $numExploits = count($exploitedAllies);
          $explotingCardID = $dqVars[1];

          for($i=0; $i<$numExploits; ++$i) {
            AddDecisionQueue("ADDCURRENTEFFECT", $player, "6772128891", 1);//Exploit effect
          }

          for($i=0; $i<$numExploits; ++$i) {
            $ally = new Ally("MYALLY-" . $exploitedAllies[$i]);
            $exploitedUniqueID = $ally->UniqueID();
            AddDecisionQueue("PASSPARAMETER", $player, $exploitedUniqueID, 1);
            AddDecisionQueue("DESTROYALLY", $player, "-", 1);
          }

          if($explotingCardID == "8655450523") {//Count Dooku - Fallen Jedi
            $exploitedAlliesPowers = [];
            for($i=0;$i<$numExploits;++$i) {
              $ally = new Ally("MYALLY-" . $exploitedAllies[$i], $player);
              $exploitedAlliesPowers[$i] = $ally->CurrentPower();
            }
            AddLayer("TRIGGER", $player, "8655450523", implode(",", $exploitedAlliesPowers));
          }
          break;
        case "ADDEXPERIENCE":
          $ally = new Ally($lastResult);
          $ally->Attach("2007868442");//Experience token
          break;
        case "ADDSHIELD":
          $ally = new Ally($lastResult);
          $ally->Attach("8752877738");//Shield Token
          break;
        case "ADDEFFECT":
          $ally = new Ally($lastResult);
          $ally->AddEffect($parameterArr[1]);
          break;
        case "POWER":
          $ally = new Ally($lastResult);
          return $ally->CurrentPower();
        case "ADDDURABILITY":
          $mzArr = explode("-", $lastResult);
          $zone = &GetMZZone($player, $mzArr[0]);
          switch($mzArr[0]) {
            case "CHAR": case "MYCHAR": case "THEIRCHAR": $zone[$mzArr[1] + 2] += $dqVars[0]; return $lastResult;
            default: return $lastResult;
          }
        case "GETUPGRADES":
          $ally = new Ally($lastResult);
          $rv = implode(",", $ally->GetUpgrades());
          return $rv == "" ? "PASS" : $rv;
        case "MOVEUPGRADE":
          //DQVars[0]: upgrade card ID
          //DQVars[1]: mzSource (e.g. MYALLY-0, MYDISCARD-0)
          //LastResult: mzTarget/targetUniqueID (e.g. MYALLY-0, 12)
          $targetAlly = new Ally($lastResult);
          $upgradeID = $dqVars[0];
          $mzSource = $dqVars[1];
          $mzSourceArr = explode("-", $mzSource);
          $upgradeOwnerID = null;

          switch ($mzSourceArr[0]) {
            case "MYALLY": case "THEIRALLY":
              $sourceAlly = new Ally($mzSource);
              $upgradeOwnerID = $sourceAlly->RemoveSubcard($upgradeID);
              break;
            case "MYDISCARD": case "THEIRDISCARD":
              MZRemove($player, $mzSource);
              break;
            default: break;
          }

          $targetAlly->Attach($upgradeID, $upgradeOwnerID);
          return $lastResult;
        case "GETCAPTIVES":
          $ally = new Ally($lastResult);
          $rv = implode(",", $ally->GetCaptives());
          return $rv == "" ? "PASS" : $rv;
        case "GETMEMORYCOST":
          $mzArr = explode("-", $lastResult);
          $zone = &GetMZZone($player, $mzArr[0]);
          return MemoryCost($zone[$mzArr[1]], $player);
        case "TAKECONTROL":
          $mzArr = explode("-", $lastResult);
          $index = $mzArr[1];
          $uniqueID = AllyTakeControl($player, $index);
          return $uniqueID;
        case "CAPTURE":
          $uniqueID = $parameterArr[1];
          $cardID = GetMZCard($player, $lastResult);
          $otherPlayer = ($player == 1 ? 2 : 1);
          $targetPlayer = str_starts_with($lastResult, "MY") ? $player : $otherPlayer;
          $captured = new Ally($lastResult, $targetPlayer);
          $capturedCardID = $captured->IsCloned() ? "0345124206" : $cardID; //Clone - Replace the cloned card to the original one when being captured
          $ownerId = $captured->Owner();
          if($captured->AvoidsCapture()) {
            WriteLog(CardLink($cardID, $cardID) . " avoided capture.");
            return $cardID;
          }
          if($cardID == "3417125055") { //IG-11
            DestroyAlly($otherPlayer, explode("-", $lastResult)[1]);
            $allies = &GetAllies($player);
            for($i=count($allies)-AllyPieces(); $i>=0; $i-=AllyPieces())
            {
              $ally = new Ally("MYALLY-" . $i, $player);
              if(ArenaContains($ally->CardID(), "Ground", $player)) $ally->DealDamage(3);
            }
            WriteLog(CardLink($cardID, $cardID) . " resisted capture.");
            return $cardID;
          }
          $capturedCardID = $captured->CardID();
          $capturedUniqueID = $captured->UniqueID();
          $capturedExhausted = $captured->IsExhausted();
          $capturedOwner = $captured->Owner();
          $capturedUpgrades = $captured->GetUpgrades();
          $index = SearchAlliesForUniqueID($uniqueID, $player);
          if($index >= 0) {
            $ally = new Ally("MYALLY-" . $index, $player);
            $ally->AddSubcard($capturedCardID, $ownerId);
          }
          CollectBounties($targetPlayer, $capturedCardID, $capturedUniqueID, $capturedExhausted, $capturedOwner, $capturedUpgrades, capturerUniqueID:$uniqueID);
          MZRemove($player, $lastResult);
          return $cardID;
        case "WRITECHOICE":
          $ally = new Ally($lastResult);
          WriteLog(CardLink($ally->CardID(), $ally->CardID()) . " was chosen");
          return $lastResult;
        case "WRITECHOICEFROMUNIQUE":
          $controller = UnitUniqueIDController($lastResult);
          $controller = $controller != -1 ? $controller : 1;
          $index = SearchAlliesForUniqueID($lastResult, $controller);
          $ally = new Ally($controller == $currentPlayer ? "MYALLY-" . $index : "THEIRALLY-" . $index);
          WriteLog(CardLink($ally->CardID(), $ally->CardID()) . " was chosen");
          return $lastResult;
        default: break;
      }
      return $lastResult;
    case "OP":
      $paramArr = explode(",", $parameter);
      $parameter = $paramArr[0];
      switch($parameter)
      {
        case "DESTROYFROZENARSENAL": DestroyFrozenArsenal($player); return "";
        case "BOOST": return DoBoost($player);
        case "REMOVECARD":
          if($lastResult == "" || $lastResult == "PASS") return $dqVars[0];
          $cards = explode(",", $dqVars[0]);
          for($i = 0; $i < count($cards); ++$i) {
            if($cards[$i] == $lastResult) {
              unset($cards[$i]);
              $cards = array_values($cards);
              break;
            }
          }
          return implode(",", $cards);
        case "ADDTOPDECKASRESOURCE":
          AddTopDeckAsResource($player);
          return $lastResult;
        case "REMOVEPREPARATION":
          global $CS_PreparationCounters;
          DecrementClassState($player, $CS_PreparationCounters, $lastResult);
          return $lastResult;
        case "GETLASTALLYMZ":
          $allies = &GetAllies($player);
          if(count($allies) == 0) return "";
          return "MYALLY-" . count($allies)-AllyPieces();
        case "GETATTACK": return AttackValue($lastResult);
        case "DISCARDHAND": DiscardHand($player); return $lastResult;
        case "DISCARDRANDOM": DiscardRandom($player, isset($paramArr[1]) ? $paramArr[1] : ''); return $lastResult;
        case "MILL": return Mill($player, $lastResult);
        case "DEFEATUPGRADE":
          $upgradeID = $lastResult;
          $mzArr = explode("-", $dqVars[0]);
          $allyPlayer = $mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
          $ally = new Ally($dqVars[0], $allyPlayer);
          $ownerId = $ally->DefeatUpgrade($upgradeID);
          if(!IsToken($upgradeID)) AddGraveyard($upgradeID, $ownerId, "PLAY");
          return $lastResult;
        case "BOUNCEUPGRADE":
          $upgradeID = $lastResult;
          if(str_contains($upgradeID, "-")) {
            $upgradeDefinition = explode("-", $upgradeID);
            $upgradeID = $upgradeDefinition[0];
            $ownerId = $upgradeDefinition[1];
            global $myDiscard, $theirDiscard;
            if($ownerId == $player) $graveyard = $myDiscard;
            else $graveyard = $theirDiscard;
            for ($i = 0; $i < count($graveyard); $i += DiscardPieces()) {
              if($graveyard[$i] == $upgradeID) {
                RemoveGraveyard($ownerId, $i);
                break;
              }
            }
          } else {
            $mzArr = explode("-", $dqVars[0]);
            $allyPlayer = $mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
            $ally = new Ally($dqVars[0], $allyPlayer);
            $ownerId = $ally->DefeatUpgrade($upgradeID);
          }
          if(!IsToken($upgradeID)) AddHand($ownerId, $upgradeID);
          return $lastResult;
        case "RESCUECAPTIVE":
          $captiveID = $lastResult;
          $mzArr = explode("-", $dqVars[0]);
          $allyPlayer = $mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
          $ally = new Ally($dqVars[0], $allyPlayer);
          $ally->RescueCaptive($captiveID);
          return $lastResult;
        case "PLAYCAPTIVE":
          $captiveID = $lastResult;
          $mzArr = explode("-", $dqVars[0]);
          $allyPlayer = $mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
          $ally = new Ally($dqVars[0], $allyPlayer);
          $ally->RescueCaptive($captiveID, $player);
          return $lastResult;
        case "DISCARDCAPTIVE":
          $captiveID = $lastResult;
          $mzArr = explode("-", $dqVars[0]);
          $allyPlayer = $mzArr[0] == "MYALLY" ? $player : ($player == 1 ? 2 : 1);
          $ally = new Ally($dqVars[0], $allyPlayer);
          $ally->DiscardCaptive($captiveID);
          return $lastResult;
        case "PLAYCARD":
          PlayCard($lastResult, $paramArr[1], -1, -1);
          return $lastResult;
        case "SWAPDQPERSPECTIVE":
          $arr = explode(",", $lastResult);
          $output = "";
          for($i=0; $i<count($arr); ++$i) {
            if($output != "") $output .= ",";
            $mzArr = explode("-", $arr[$i]);
            $output .= ($mzArr[0] == "MYALLY" ? "THEIRALLY" : "MYALLY") . "-" . $mzArr[1];
          }
          return $output;
        case "MZTONORMALINDICES":
          $arr = explode(",", $lastResult);
          $output = "";
          for($i=0; $i<count($arr); ++$i) {
            if($output != "") $output .= ",";
            $mzArr = explode("-", $arr[$i]);
            $output .= $mzArr[1];
          }
          if($output == "") $output = "PASS";
          return $output;
        default: return $lastResult;
      }
    case "FILTER":
      $params = explode("-", $parameter);
      $from = $params[0];
      $relationship = $params[1];//exclude other or include
      $type = isset($params[2]) ? $params[2] : "";
      $compareValue = isset($params[3]) ? $params[3] : "";
      $input = [];
      switch($from)
      {
        case "LastResult": $input = explode(",", $lastResult); for($i=0; $i<count($input); ++$i) $input[$i] = $input[$i] . "-" . $input[$i]; break;
        case "CombatChain":
          $lastResultArr = explode(",", $lastResult);
          for($i=0; $i<count($lastResultArr); ++$i) $input[] = $combatChain[$lastResultArr[$i]+CCOffset($type)] . "-" . $lastResultArr[$i];
          break;
        case "Deck":
          $lastResultArr = explode(",", $lastResult);
          $deck = &GetDeck($player);
          for($i=0; $i<count($lastResultArr); ++$i) $input[] = $deck[$lastResultArr[$i] * DeckPieces()] . "-" . $lastResultArr[$i];
          break;
        default: break;
      }
      $output = [];
      for($i=0; $i<count($input); ++$i)
      {
        $inputArr = explode("-", $input[$i]);
        $passFilter = !($relationship == "include");
        switch($type)
        {
          case "type": if(CardType($inputArr[0]) == $compareValue) $passFilter = !$passFilter; break;
          case "subtype": if(SubtypeContains($inputArr[0], $compareValue, $player)) $passFilter = !$passFilter; break;
          case "trait": if(TraitContains($inputArr[0], $compareValue, $player)) $passFilter = !$passFilter; break;
          case "player": if($inputArr[0] == $compareValue) $passFilter = !$passFilter; break;
          case "definedType":
            if(DefinedTypesContains($inputArr[0], $compareValue, $player)) $passFilter = !$passFilter; break;
          case "aspect": if(AspectContains($inputArr[0], $compareValue, $player)) $passFilter = !$passFilter; break;
          case "maxCost": if(CardCost($inputArr[0]) <= $compareValue) $passFilter = !$passFilter; break;
          default: break;
        }
        if($passFilter) $output[] = $inputArr[1];
      }
      return (count($output) > 0 ? implode(",", $output) : "PASS");
    case "MZFILTER":
      $params = explode("=", $parameter);
      $arr = explode(",", $lastResult);
      if($params[0] == "canAttach") $params = explode("=", UpgradeFilter($params[1]));
      $invertedMatching = str_ends_with($params[0], "!");
      $params[0] = rtrim($params[0], "!");
      for($i=count($arr)-1; $i>=0; --$i) {
        $match = false;
        switch($params[0]) {
          case "index": if($arr[$i] == $params[1]) $match = true; break;
          case "trait": if(TraitContains(GetMZCard($player, $arr[$i]), $params[1], $player)) $match = true; break;
          case "aspect": if(AspectContains(GetMZCard($player, $arr[$i]), $params[1],$player)) $match = true; break;
          case "definedType": if(DefinedTypesContains(GetMZCard($player, $arr[$i]), $params[1], $player)) $match = true; break;
          case "token":
            $isToken = IsToken(GetMZCard($player, $arr[$i]));
            if($isToken && $params[1] == 1) $match = true;
            if(!$isToken && $params[1] == 0) $match = true;
            break;
          case "maxCost":
            $cardID = str_starts_with($arr[$i], "MY") || str_starts_with($arr[$i], "THEIR") ? GetMZCard($player, $arr[$i]) : $arr[$i];
            if(CardCost($cardID) > $params[1]) $match = true;
            break;
          case "dqVar": // Supports mzIndex or uniqueID (e.g. MYALLY-0,18,THEIRALLY-7,12)
            $mzArr = explode(",", $dqVars[$params[1]]);
            for($j=0; $j<count($mzArr); ++$j) {
              if($mzArr[$j] == "" || $mzArr[$j] == "-") continue;

              $ally = new Ally($arr[$i]);
              $filterAlly = new Ally($mzArr[$j]);
              if($ally->UniqueID() == $filterAlly->UniqueID()) {
                $match = true;
                break;
              }
            }
            break;
          case "status":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              if($params[1] == 1 && $ally->IsExhausted()) $match = true;
              else if($params[1] == 0 && !$ally->IsExhausted()) $match = true;
            } else if($mzArr[0] == "MYRESOURCES" || $mzArr[0] == "THEIRRESOURCES") {
              $resources = &GetResourceCards($player);
              if($params[1] == 1 && $resources[$mzArr[1]+4] == 1) $match = true;
              else if($params[1] == 0 && $resources[$mzArr[1]+4] != 1) $match = true;
            }
            break;
          case "damaged":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              if($params[1] == 1 && $ally->IsDamaged()) $match = true;
              else if($params[1] == 0 && !$ally->IsDamaged()) $match = true;
            } else if($mzArr[0] == "MYCHAR" || $mzArr[0] == "THEIRCHAR") {
              $health = GetHealth($mzArr[0] == "MYCHAR" ? $player : ($player == 1 ? 2 : 0));
              if($params[1] == 1 && $health > 0) $match = true;
              else if($params[1] == 0 && $health == 0) $match = true;
            }
            break;
          case "leader":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              $isLeader = DefinedTypesContains($ally->CardID(), "Leader", $player);
              if($params[1] == 1 && $isLeader) $match = true;
              else if($params[1] == 0 && !$isLeader) $match = true;
            }
            break;
          case "unique":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              $isUnique = CardIsUnique($ally->CardID());
              if($params[1] == 1 && $isUnique) $match = true;
              else if($params[1] == 0 && !$isUnique) $match = true;
            } else {
              $isUnique = CardIsUnique($mzArr[0]);
              if($params[1] == 1 && $isUnique) $match = true;
              elseif($params[1] == 0 && !$isUnique) $match = true;
            }
            break;
          case "turns":
            $mzArr = explode("-", $arr[$i]);
            $paramsArr = explode(">", $params[1]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              if($ally->TurnsInPlay() > $paramsArr[1]) $match = true;
            }
            break;
          case "numAttacks":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              if($ally->NumAttacks() == $params[1]) $match = true;
            }
            break;
          case "hasCaptives":
            $mzArr = explode("-", $arr[$i]);
            if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") {
              $ally = new Ally($arr[$i]);
              $hasCaptives = count($ally->GetCaptives()) > 0;
              if($params[1] == 1 && $hasCaptives) $match = true;
              else if($params[1] == 0 && !$hasCaptives) $match = true;
            }
            break;
          default: break;
        }
        if($invertedMatching && !$match) unset($arr[$i]);
        else if(!$invertedMatching && $match) unset($arr[$i]);
      }
      $rv = implode(",", $arr);
      return ($rv == "" ? "PASS" : $rv);
    case "PASSPARAMETER":
      return $parameter;
    case "DISCARDCARD":
      AddGraveyard($lastResult, $player, $parameter);
      CardDiscarded($player, $lastResult);
      WriteLog(CardLink($lastResult, $lastResult) . " was discarded");
      return $lastResult;
    case "ADDDISCARD":
      $paramArr = explode(",", $parameter);
      $modifier = count($paramArr) > 1 ? $paramArr[1] : "-";
      AddGraveyard($lastResult, $player, $paramArr[0], $modifier);
      return $lastResult;
    case "ADDBOTDECK":
      $deck = &GetDeck($player);
      $deck[] = $lastResult;
      return $lastResult;
    case "MULTIADDDECK":
      $deck = &GetDeck($player);
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) $deck[] = $cards[$i];
      return $lastResult;
    case "MULTIADDTOPDECK":
      $deck = &GetDeck($player);
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if($parameter == "1") WriteLog(CardLink($cards[$i], $cards[$i]));
        array_unshift($deck, $cards[$i]);
      }
      return $lastResult;
    case "MULTIADDDISCARD":
      $paramArr = explode(",", $parameter);
      $modifier = count($paramArr) > 1 ? $paramArr[1] : "-";
      $deck = &GetDeck($player);
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        AddGraveyard($cards[$i], $player, $paramArr[0], $modifier);
      }
      return $lastResult;
    case "MULTIREMOVEDECK":
      if(!is_array($lastResult)) $lastResult = ($lastResult == "" ? [] : explode(",", $lastResult));
      $cards = "";
      $deck = &GetDeck($player);
      for($i = 0; $i < count($lastResult); ++$i) {
        if($cards != "") $cards .= ",";
        $cards .= $deck[$lastResult[$i]];
        unset($deck[$lastResult[$i]]);
      }
      $deck = array_values($deck);
      return $cards;
    case "PLAYAURA":
      PlayAura($parameter, $player);
      break;
    case "DESTROYALLY":
      $ally = new Ally($lastResult);
      DestroyAlly($player, $ally->Index());
      break;
    case "PARAMDELIMTOARRAY":
      return explode(",", $parameter);
    case "ADDSOUL":
      AddSoul($lastResult, $player, $parameter);
      return $lastResult;
    case "SHUFFLEDECK":
      $deck = &GetDeck($player);
      $skipSeed = $parameter == "SKIPSEED";
      RandomizeArray($deck, $skipSeed);
      return $lastResult;
    case "EXHAUSTCHARACTER":
      $character = &GetPlayerCharacter($player);
      $character[$parameter+1] = 1;
      return $parameter;
    case "DECKCARDS":
      $indices = explode(",", $parameter);
      $deck = &GetDeck($player);
      $rv = "";
      for($i = 0; $i < count($indices); ++$i) {
        if(count($deck) <= $i) continue;
        if($rv != "") $rv .= ",";
        $rv .= $deck[$i];
      }
      return ($rv == "" ? "PASS" : $rv);
    case "MATERIALCARDS":
      $indices = $parameter;
      if(!is_array($indices)) $indices = explode(",", $parameter);
      $material = &GetMaterial($player);
      $rv = "";
      for($i = 0; $i < count($indices); ++$i) {
         if(count($material) <= $i) continue;
         if($rv != "") $rv .= ",";
        $rv .= $material[$i];
      }
      return ($rv == "" ? "PASS" : $rv);
    case "SHOWOPTIONS":
      $params = explode("&", $parameter);
      $cardID = $params[0];
      $options = explode(";", $params[1]);
      $selectedOption = str_replace("_", " ", $options[$lastResult]);
      WriteLog("Selected option for " . CardLink($parameter, $parameter) . " is: $selectedOption");
      return $lastResult;      
    case "SHOWMODES":
      if(is_array($lastResult)) $modes = $lastResult;
      else {
        $modes = [];
        $modes[] = $lastResult;
      }
      $text = "";
      for($i = 0; $i < count($modes); ++$i) {
        if($text != "") $text .= ", ";
        if($i > 0 && $i == count($modes)-1) $text .= " and ";
        $text .= implode(" ", explode("_", $modes[$i]));
      }
      WriteLog("Selected mode" . (count($modes) > 1 ? "s" : "") . " for " . CardLink($parameter, $parameter) . (count($modes) > 1 ? " are" : " is") . ": " . $text);
      return $lastResult;
    case "REVEALCARDS":
      $cards = (is_array($lastResult) ? implode(",", $lastResult) : $lastResult);
      $revealed = RevealCards($cards, $player);
      return ($revealed ? $lastResult : "PASS");
    case "REVEALHANDCARDS":
      $indices = (is_array($lastResult) ? $lastResult : explode(",", $lastResult));
      $hand = &GetHand($player);
      $cards = "";
      for($i = 0; $i < count($indices); ++$i) {
        if($cards != "") $cards .= ",";
        $cards .= $hand[$indices[$i]];
      }
      $revealed = RevealCards($cards, $player);
      return ($revealed ? $cards : "PASS");
    case "WRITELOG":
      WriteLog(implode(" ", explode("_", $parameter)));
      return $lastResult;
    case "ADDIMMEDIATECURRENTEFFECT":
      AddCurrentTurnEffect($parameter, $player, "PLAY");
      return "1";
    case "ADDCURRENTEFFECT":
      AddCurrentTurnEffect($parameter, $player);
      UpdateLinkAttack();
      return $lastResult;
    case "REMOVECURRENTEFFECT":
      SearchCurrentTurnEffects($parameter, $player, true);
      UpdateLinkAttack();
      return $lastResult;
    case "ADDCURRENTANDNEXTTURNEFFECT":
      AddCurrentTurnEffect($parameter, $player);
      UpdateLinkAttack();
      AddNextTurnEffect($parameter, $player);
      return "1";
    case "ADDLIMITEDCURRENTEFFECT":
      $uniqueID = $lastResult;
      $params = explode(",", $parameter);
      $controller = UnitUniqueIDController($uniqueID);
      $from = "";
      if ($controller == -1) {
        $controller = $player;
      }
      if (isset($params[1])) {
        $from = $params[1];
      }
      if (isset($params[2])) {
        $controller = $params[2]; // Override controller
      }
      AddCurrentTurnEffect($params[0], $controller, $from, $uniqueID);
      UpdateLinkAttack();
      return $lastResult;
    case "ADDLIMITEDNEXTTURNEFFECT":
      AddNextTurnEffect($parameter, $player, $lastResult);
      return $lastResult;
    case "ADDAIMCOUNTER":
      $arsenal = &GetArsenal($player);
      $arsenal[$lastResult+3] += 1;
      return $lastResult;
    case "ADDARSENALCURRENTEFFECT":
      $arsenal = &GetArsenal($player);
      $params = explode(",", $parameter);
      AddCurrentTurnEffect($params[0], $player, $params[1], $arsenal[$lastResult+5]);
      return $lastResult;
    case "OPTX":
      Opt("NA", $parameter);
      return $lastResult;
    case "SETCLASSSTATE":
      $data = is_array($lastResult) ? implode(",", $lastResult) : $lastResult;
      SetClassState($player, $parameter, $data);
      return $lastResult;
    case "GETCLASSSTATE":
      return GetClassState($player, $parameter);
    case "GAINACTIONPOINTS":
      GainActionPoints($parameter, $player);
      return $lastResult;
    case "EQUALPASS":
      if($lastResult == $parameter) return "PASS";
      return $lastResult;
    case "NOTEQUALPASS":
      if($lastResult != $parameter) return "PASS";
      return $lastResult;
    case "NOPASS":
      if($lastResult == "NO") return "PASS";
      return 1;
    case "YESPASS":
      if($lastResult == "YES") return "PASS";
      return 1;
    case "NOTSHARETRAITPASS":
      $mzArr = explode("-", $lastResult);
      if(str_starts_with($mzArr[0], "THEIR")) $zone = &GetMZZone($player == 1 ? 2 : 1, $mzArr[0]);
      else $zone = &GetMZZone($player, $mzArr[0]);
      $cardID = $zone[$mzArr[1]];
      return TraitContainsAny($cardID, $parameter) ? $lastResult : "PASS";
    case "NOALLYUNIQUEIDPASS":
      $index = SearchAlliesForUniqueID($parameter, $player);
      if($index == -1) return "PASS";
      return 1;
    case "NULLPASS":
      if($lastResult == "") return "PASS";
      return $lastResult;
    case "ELSE":
      if($lastResult == "PASS") return "0";
      else if($lastResult == "NO") return "NO";
      else return "PASS";
    case "FINDCURRENTEFFECTPASS":
      if(SearchCurrentTurnEffects($parameter, $player)) return "PASS";
      return $lastResult;
    case "LESSTHANPASS":
      if($lastResult < $parameter) return "PASS";
      return $lastResult;
    case "GREATERTHANPASS":
      if($lastResult > $parameter) return "PASS";
      return $lastResult;
    case "EQUIPDEFENSE":
      $char = &GetPlayerCharacter($player);
      $defense = BlockValue($char[$lastResult]) + $char[$lastResult + 4];
      if($defense < 0) $defense = 0;
      return $defense;
    case "ALLCARDTYPEORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(CardType($cards[$i]) != $parameter) return "PASS";
      }
      return $lastResult;
    case "MZALLCARDTRAITORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        $cardID = GetMZCard($player, $cards[$i]);
        if(!TraitContains($cardID, $parameter, $player)) return "PASS";
      }
      return $lastResult;
    case "MZNOCARDASPECTORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        $cardID = GetMZCard($player, $cards[$i]);
        if(AspectContains($cardID, $parameter, $player)) return "PASS";
      }
      return $lastResult;
    case "NONECARDTYPEORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(CardType($cards[$i]) == $parameter) return "PASS";
      }
      return $lastResult;
    case "ALLCARDSUBTYPEORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(!SubtypeContains($cards[$i], $parameter)) return "PASS";
      }
      return $lastResult;
    case "NONECARDDEFINEDTYPEORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(DefinedTypesContains($cards[$i], $parameter, $player)) return "PASS";
      }
      return $lastResult;
    case "ALLCARDELEMENTORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(CardElement($cards[$i]) != $parameter) return "PASS";
      }
      return $lastResult;
    case "ALLCARDSCOMBOORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(!HasCombo($cards[$i])) return "PASS";
      }
      return $lastResult;
    case "ALLCARDMAXCOSTORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(CardCost($cards[$i]) > $parameter) return "PASS";
      }
      return $lastResult;
    case "ALLCARDCLASSORPASS":
      $cards = explode(",", $lastResult);
      for($i = 0; $i < count($cards); ++$i) {
        if(!ClassContains($cards[$i], $parameter, $player)) return "PASS";
      }
      return $lastResult;
    case "CLASSSTATEGREATERORPASS":
      $parameters = explode("-", $parameter);
      $state = $parameters[0];
      $threshold = $parameters[1];
      if(GetClassState($player, $state) < $threshold) return "PASS";
      return 1;
    case "CHARREADYORPASS":
      $char = &GetPlayerCharacter($player);
      if($char[$parameter + 1] != 2) return "PASS";
      return 1;
    case "ATTACKMODIFIER":
      $amount = intval($parameter);
      WriteLog($amount);
      $combatChain[5] += $amount;
      return $parameter;
    case "DEALDAMAGE":
      $target = (is_array($lastResult) ? $lastResult : explode("-", $lastResult));
      $targetPlayer = ($target[0] == "MYCHAR" || $target[0] == "MYALLY" ? $player : ($player == 1 ? 1 : 2));
      $parameters = explode("-", $parameter);
      $damage = $parameters[0];
      $source = $parameters[1];
      $type = $parameters[2];
      if($target[0] == "THEIRALLY" || $target[0] == "MYALLY") {
        DealAllyDamage($targetPlayer, $target[1], $damage);
        return $damage;
      } else {
        PrependDecisionQueue("TAKEDAMAGE", $targetPlayer, $parameter);
        PrependDecisionQueue("PASSPARAMETER", $targetPlayer, "0");
      }
      return $damage;
    case "TAKEDAMAGE":
      $params = explode("-", $parameter);
      $damage = intval($params[0]);
      $source = (count($params) > 1 ? $params[1] : "-");
      $type = (count($params) > 2 ? $params[2] : "-");
      if(!CanDamageBePrevented($player, $damage, "DAMAGE")) $lastResult = 0;
      $damage -= intval($lastResult);
      if($type == "COMBAT")
      {
        $dqState[6] = $damage;
      }
      $damage = DealDamageAsync($player, $damage, $type, $source);
      return $damage;
    case "AFTERQUELL":
      $maxQuell = GetClassState($player, $CS_MaxQuellUsed);
      if($lastResult > 0) WriteLog("Player $player prevented $lastResult damage with Quell", $player);
      if($lastResult > $maxQuell) SetClassState($player, $CS_MaxQuellUsed, $lastResult);
      return $lastResult;
    case "SPELLVOIDCHOICES":
      $damage = $parameter;
      if($lastResult != "PASS") {
        $damage -= $prevented;
        if($damage < 0) $damage = 0;
        $dqVars[0] = $damage;
        //if($damage > 0) CheckSpellvoid($player, $damage);
      }
      PrependDecisionQueue("INCDQVAR", $player, "1", 1);
      return $prevented;
    case "COLLECTBOUNTY":
      $paramArr = explode(",", $parameter);
      $unitCardID = $paramArr[0];
      $bountyCardID = $paramArr[1];
      $isExhausted = $paramArr[2];
      $owner = $paramArr[3];
      $capturerUniqueID = $paramArr[4];
      CollectBounty($player, $unitCardID, $bountyCardID, $isExhausted, $owner, reportMode:false, capturerUniqueID:$capturerUniqueID);
      return $lastResult;
    case "ARCANECHOSEN":
      if($lastResult > 0) {
        if(SearchCharacterActive($player, "UPR166")) {
          $char = &GetPlayerCharacter($player);
          $index = FindCharacterIndex($player, "UPR166");
          if($char[$index+2] < 4 && GetClassState($player, $CS_AlluvionUsed) == 0) {
            ++$char[$index+2];
            SetClassState($player, $CS_AlluvionUsed, 1);
          }
        }
      }
      return $lastResult;
    case "TAKEARCANE":
      $parameters = explode("-", $parameter);
      $damage = $parameters[0];
      $source = $parameters[1];
      $playerSource = $parameters[2];
      if(!CanDamageBePrevented($player, $damage, "ARCANE")) $lastResult = 0;
      $damage = DealDamageAsync($player, $damage - $lastResult, "ARCANE", $source);
      if($damage < 0) $damage = 0;
      if($damage > 0) IncrementClassState($playerSource, $CS_ArcaneDamageDealt, $damage);
      WriteLog("Player " . $player . " took $damage arcane damage from " . CardLink($source, $source), $player);
      if(DelimStringContains(CardSubType($source), "Ally") && $damage > 0) ProcessDealDamageEffect($source); // Interaction with Burn Them All! + Nekria
      $dqVars[0] = $damage;
      return $damage;
    case "PAYRESOURCES":
      $paramArr = explode(",", $parameter);
      $skipChoice = count($paramArr) > 1 && $paramArr[1] == 1;
      $numResources = $paramArr[0];
      if($skipChoice == 1) { //Skip choice
        $resourceCards = &GetResourceCards($currentPlayer);
        for($i = 0; $i < count($resourceCards); $i += ResourcePieces()) {
          if($numResources == 0) break;
          if($resourceCards[$i+4] == "0") {
            $resourceCards[$i+4] = "1";
            --$numResources;
          }
        }
      } else for($i = 0; $i < $numResources; ++$i) {
        PrependDecisionQueue("MZOP", $player, "REST", 1);
        PrependDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $player, "Choose a resource to exhaust");
        PrependDecisionQueue("MZFILTER", $player, "status=1");
        PrependDecisionQueue("MULTIZONEINDICES", $player, "MYRESOURCES");
      }
      return $parameter;
    case "ADDCLASSSTATE":
      $parameters = explode("-", $parameter);
      IncrementClassState($player, $parameters[0], $parameters[1]);
      return 1;
    case "SUBTRACTCLASSSTATE":
      $parameters = explode("-", $parameter);
      DecrementClassState($player, $parameters[0], $parameters[1]);
      return $lastResult;
    case "APPENDCLASSSTATE":
      $parameters = explode("-", $parameter);
      AppendClassState($player, $parameters[0], $parameters[1]);
      return $lastResult;
    case "SUBPITCHVALUE":
      return $parameter - 1;
    case "LASTARSENALADDEFFECT":
      $params = explode(",", $parameter);
      $arsenal = &GetArsenal($player);
      if(count($arsenal) > 0 && count($params) == 2) AddCurrentTurnEffect($params[0], $player, $params[1], $arsenal[count($arsenal) - ArsenalPieces() + 5]);
      return $lastResult;
    case "PROCESSATTACKTARGET":
      if($parameter==="MULTI") {
        $targets=explode(",", $combatChainState[$CCS_MultiAttackTargets]);
        $lastResult="THEIRALLY-$targets[0]";
      }
      $combatChainState[$CCS_AttackTarget] = $lastResult;
      $mzArr = explode("-", $lastResult);
      $zone = &GetMZZone($defPlayer, $mzArr[0]);
      $uid = "-";
      switch($mzArr[0])
      {
        case "MYALLY": case "THEIRALLY": $uid = $zone[$mzArr[1]+5]; break;
        case "MYAURAS": case "THEIRAURAS": $uid = $zone[$mzArr[1]+6]; break;
        default: break;
      }
      $combatChainState[$CCS_AttackTargetUID] = $uid;
      WriteLog(GetMZCardLink($defPlayer, $lastResult) . " was chosen as the attack target");
      return 1;
    case "STARTTURNABILITIES":
      StartTurnAbilities();
      return 1;
    case "DRAWTOINTELLECT":
      $deck = &GetDeck($player);
      $hand = &GetHand($player);
      $char = &GetPlayerCharacter($player);
      for($i = 0; $i < CharacterIntellect($char[0]); ++$i) {
        $hand[] = array_shift($deck);
      }
      return 1;
    case "RESUMEROUNDPASS":
      ResumeRoundPass();
      return 1;
    case "ROLLDIE":
      $roll = RollDie($player, true, $parameter == "1");
      return $roll;
    case "SETCOMBATCHAINSTATE":
      $combatChainState[$parameter] = $lastResult;
      return $lastResult;
    case "BANISHADDMODIFIER":
      $banish = &GetBanish($player);
      $banish[$lastResult + 1] = $parameter;
      return $lastResult;
    case "SETLAYERTARGET":
      global $layers, $CS_LayerTarget;
      $target = $lastResult;
      $targetArr = explode("-", $target);
      if($targetArr[0] == "LAYER") $target = "LAYERUID-" . $layers[intval($targetArr[1]) + 6];
      for($i=0; $i<count($layers); $i+=LayerPieces())
      {
        if($layers[$i] == $parameter)
        {
          $layers[$i+3] = $target;
        }
      }
      SetClassState($player, $CS_LayerTarget, $target);
      return $lastResult;
    case "SHOWSELECTEDTARGET":
      if(str_starts_with($lastResult, "THEIR")) {
        $otherP = ($player == 1 ? 2 : 1);
        WriteLog(GetMZCardLink($otherP, $lastResult) . " was targeted");
      } else {
        WriteLog(GetMZCardLink($player, $lastResult) . " was targeted");
      }
      return $lastResult;
    case "MULTIZONEFORMAT":
      return SearchMultizoneFormat($lastResult, $parameter);
    case "MULTIZONETOKENCOPY":
      $mzArr = explode("-", $lastResult);
      $source = $mzArr[0];
      $index = $mzArr[1];
      switch($source) {
        case "MYAURAS": TokenCopyAura($player, $index); break;
        default: break;
      }
      return $lastResult;
    case "COUNTITEM":
      return CountItem($parameter, $player);
    case "FINDANDDESTROYITEM":
      $mzArr = explode("-", $parameter);
      $cardID = $mzArr[0];
      $number = $mzArr[1];
      for($i = 0; $i < $number; ++$i) {
        $index = GetItemIndex($cardID, $player);
        if($index != -1) DestroyItemForPlayer($player, $index);
      }
      return $lastResult;
    case "COUNTPARAM":
      $array = explode(",", $parameter);
      return count($array) . "-" . $parameter;
    case "VALIDATEALLSAMENAME":
      if($parameter == "DECK") {
        $zone = &GetDeck($player);
      }
      if(count($lastResult) == 0) return "PASS";
      $name = CardName($zone[$lastResult[0]]);
      for($i = 1; $i < count($lastResult); ++$i) {
        if(CardName($zone[$lastResult[$i]]) != $name) {
          WriteLog("You selected cards that do not have the same name. Reverting gamestate prior to that effect.");
          RevertGamestate();
          return "PASS";
        }
      }
      return $lastResult;
    case "GETITEMBYINDEX": // Get item by index, separated by comma. If the index is negative, it will be counted from the end of the array (e.g. -1 is the last item).
      if ($lastResult == "PASS" || $lastResult == "") return "PASS";
      $items = explode(",", $lastResult);
      $index = (int) $parameter;
      if ($index < 0) {
        $index = count($items) + $index;
      }
      if ($index >= count($items)) {
        return "PASS";
      }
      return $items[$index];
    case "PREPENDLASTRESULT":
      $rv = $lastResult == "PASS" ? $parameter : $parameter . $lastResult;
      $rv = rtrim($rv, ",");
      return $rv;
    case "APPENDLASTRESULT":
      return $lastResult . $parameter;
    case "LASTRESULTPIECE":
      $pieces = explode("-", $lastResult);
      return $pieces[$parameter];
    case "IMPLODELASTRESULT":
      if(!is_array($lastResult)) return $lastResult;
      return ($lastResult == "" ? "PASS" : implode($parameter, $lastResult));
    case "VALIDATECOUNT":
      if(count($lastResult) != $parameter) {
        WriteLog("The count from the last step is incorrect. Reverting gamestate prior to that effect.");
        RevertGamestate();
        return "PASS";
      }
      return $lastResult;
    case "ADDATTACKCOUNTERS":
      $lastResultArr = explode("-", $lastResult);
      $zone = $lastResultArr[0];
      $zoneDS = &GetMZZone($player, $zone);
      $index = $lastResultArr[1];
      if($zone == "MYCHAR" || $zone == "THEIRCHAR") $zoneDS[$index+3] += $parameter;
      else if($zone == "MYAURAS" || $zone == "THEIRAURAS") $zoneDS[$index+3] += $parameter;
      return $lastResult;
    case "MODDEFCOUNTER":
      if($lastResult == "") return $lastResult;
      $character = &GetPlayerCharacter($player);
      $character[$lastResult+4] = intval($character[$lastResult+4]) + $parameter;
      if($parameter < 0) WriteLog(CardLink($character[$lastResult], $character[$lastResult]) . " got a negative defense counter");
      return $lastResult;
    case "AFTERRESOURCE":
      LogPlayCardStats($player, $lastResult, $parameter, type:"RESOURCED");
      return $lastResult;
    case "REMOVECOUNTER":
      $character = &GetPlayerCharacter($player);
      $character[$lastResult+2] -= 1;
      WriteLog(CardLink($parameter, $parameter) . " removed a counter from " . CardLink($character[$lastResult], $character[$lastResult]));
      return $lastResult;
    case "FINALIZEDAMAGE":
      $params = explode(",", $parameter);
      $damage = $dqVars[0];
      $damageThreatened = $params[0];
      if($damage > $damageThreatened)//Means there was excess damage prevention prevention
      {
        $damage = $damageThreatened;
        $dqVars[0] = $damage;
        $dqState[6] = $damage;
      }
      return FinalizeDamage($player, $damage, $damageThreatened, $params[1], $params[2]);
    case "APPENDDQVAR":
      if($dqVars[$parameter] == "-") $dqVars[$parameter] = $lastResult;
      else $dqVars[$parameter] .= "," . $lastResult;
      return $lastResult;
    case "SETDQVAR":
      $dqVars[$parameter] = $lastResult;
      return $lastResult;
    case "INCDQVAR":
      $dqVars[$parameter] = intval($dqVars[$parameter]) + intval($lastResult);
      return $lastResult;
    case "DECDQVAR":
      $dqVars[$parameter] = intval($dqVars[$parameter]) - intval($lastResult);
      return $lastResult;
    case "DIVIDE":
      return floor($lastResult / $parameter);
    case "DQVARPASSIFSET":
      if ($dqVars[$parameter] == "1") return "PASS";
      return "PROCEED";
    case "ADDCARDTOCHAIN":
      AddCombatChain($lastResult, $player, $parameter, 0);
      return $lastResult;
    case "ATTACKWITHIT":
      PlayCardSkipCosts($lastResult, "DECK");
      return $lastResult;
    case "SETDQCONTEXT":
      $dqState[4] = implode("_", explode(" ", $parameter));
      return $lastResult;
    case "AFTERDIEROLL":
      AfterDieRoll($player);
      return $lastResult;
    case "MODAL":
      return ModalAbilities($player, $parameter, $lastResult);
    case "SETABILITYTYPE":
      global $CS_PlayIndex;
      $lastPlayed[2] = $lastResult;
      $index = GetAbilityIndex($parameter, GetClassState($player, $CS_PlayIndex), $lastResult);
      SetClassState($player, $CS_AbilityIndex, $index);
      if(IsAlly($parameter, $player) && AllyDoesAbilityExhaust($parameter, $index)) {
        $ally = new Ally("MYALLY-" . GetClassState($player, $CS_PlayIndex), $player);
        $ally->Exhaust();
      }
      $names = explode(",", GetAbilityNames($parameter, GetClassState($player, $CS_PlayIndex)));
      $ability = implode(" ", explode("_", $names[$index]));
      WriteLog("<b><span style='color:Gray'>{$ability}</span></b> ability was chosen.");
      return $lastResult;
      case "SETABILITYTYPEOPP"://For activating opponent's cards
        global $CS_OppIndex, $CS_OppCardActive;
        $lastPlayed[2] = $lastResult;
        $otherPlayer = ($player == 1 ? 2 : 1);
        $index = GetAbilityIndex($parameter, GetClassState($player, $CS_OppIndex), $lastResult, theirCard:true);
        SetClassState($player, $CS_AbilityIndex, $index);
        if(IsAlly($parameter, $otherPlayer) && TheirAllyDoesAbilityExhaust($parameter, $index)) {
          $ally = new Ally("MYALLY-" . GetClassState($player, $CS_PlayIndex), $player);
          $ally->Exhaust();
        }
        $names = explode(",", GetOpponentControlledAbilityNames($parameter));
        WriteLog(implode(" ", explode("_", $names[$index])) . " ability was chosen!");
        return $lastResult;
    case "MZSTARTTURNABILITY":
      MZStartTurnAbility($player, $lastResult);
      return "";
    case "MZDAMAGE":
      $lastResultArr = explode(",", $lastResult);
      $params = explode(",", $parameter);
      for($i = 0; $i < count($lastResultArr); ++$i) {
        $mzIndex = explode("-", $lastResultArr[$i]);
        $target = (str_starts_with($mzIndex[0], "MY")) ? $player : ($player == 1 ? 2 : 1);
        DamageTrigger($target, $params[0], $params[1], GetMZCard($target, $lastResultArr[$i]));
      }
      return $lastResult;
    case "MZDESTROY":
      return MZDestroy($player, $lastResult);
    case "MZUNDESTROY":
      return MZUndestroy($player, $parameter, $lastResult);
    case "MZBANISH":
      return MZBanish($player, $parameter, $lastResult);
    case "MZREMOVE":
      return MZRemove($player, $lastResult);
    case "MZDISCARD":
      return MZDiscard($player, $parameter, $lastResult);
    case "MZADDZONE":
      return MZAddZone($player, $parameter, $lastResult);
    case "GAINRESOURCES":
      GainResources($player, $parameter);
      return $lastResult;
    case "TRANSFORM":
      return "ALLY-" . ResolveTransform($player, $lastResult, $parameter);
    case "TRANSFORMPERMANENT":
      return "PERMANENT-" . ResolveTransformPermanent($player, $lastResult, $parameter);
    case "TRANSFORMAURA":
      return "AURA-" . ResolveTransformAura($player, $lastResult, $parameter);
    case "STARTGAME":
      global $initiativePlayer, $turn, $currentPlayer;
      $secondPlayer = ($initiativePlayer == 1 ? 2 : 1);
      $inGameStatus = "1";
      $MakeStartTurnBackup = true;
      $MakeStartGameBackup = true;
      for($i=0; $i<6; ++$i) {
        Draw(1);
        Draw(2);
      }
      if(!IsPlayerAI($initiativePlayer)) {
        AddDecisionQueue("SETDQCONTEXT", $initiativePlayer, "Would you like to mulligan?");
        AddDecisionQueue("YESNO", $initiativePlayer, "-");
        AddDecisionQueue("NOPASS", $initiativePlayer, "-");
        AddDecisionQueue("MULLIGAN", $initiativePlayer, "-", 1);
      }
      if(!IsPlayerAI($secondPlayer)) {
        AddDecisionQueue("SETDQCONTEXT", $secondPlayer, "Would you like to mulligan?");
        AddDecisionQueue("YESNO", $secondPlayer, "-");
        AddDecisionQueue("NOPASS", $secondPlayer, "-");
        AddDecisionQueue("MULLIGAN", $secondPlayer, "-", 1);
      }
      CharacterStartTurnAbility($initiativePlayer);
      CharacterStartTurnAbility($secondPlayer);
      MZMoveCard($initiativePlayer, "MYHAND", "MYRESOURCES", may:false, context:"Choose a card to resource", silent:true);
      AddDecisionQueue("AFTERRESOURCE", $initiativePlayer, "HAND", 1);
      MZMoveCard($initiativePlayer, "MYHAND", "MYRESOURCES", may:false, context:"Choose a card to resource", silent:true);
      AddDecisionQueue("AFTERRESOURCE", $initiativePlayer, "HAND", 1);
      MZMoveCard($secondPlayer, "MYHAND", "MYRESOURCES", may:false, context:"Choose a card to resource", silent:true);
      AddDecisionQueue("AFTERRESOURCE", $secondPlayer, "HAND", 1);
      MZMoveCard($secondPlayer, "MYHAND", "MYRESOURCES", may:false, context:"Choose a card to resource", silent:true);
      AddDecisionQueue("AFTERRESOURCE", $secondPlayer, "HAND", 1);
      AddDecisionQueue("STARTTURNABILITIES", $initiativePlayer, "-");
      AddDecisionQueue("SWAPFIRSTTURN", 1, "-");
      return 0;
    case "SWAPFIRSTTURN":
      global $isPass;
      $isPass = true;
      return 0;
    case "SWAPTURN":
      PassTurn();
      return 0;
    case "MULLIGAN":
      $hand = &GetHand($player);
      $deck = &GetDeck($player);
      for($i=0; $i<count($hand); $i+=HandPieces()) {
        AddBottomDeck($hand[$i], $player);
        PrependDecisionQueue("DRAW", $player, "-");
      }
      $hand = [];
      PrependDecisionQueue("SHUFFLEDECK", $player, "-");
      return 0;
    case "QUICKREMATCH":
      $currentTime = round(microtime(true) * 1000);
      SetCachePiece($gameName, 2, $currentTime);
      SetCachePiece($gameName, 3, $currentTime);
      ClearGameFiles($gameName);
      include "MenuFiles/ParseGamefile.php";
      $authKey = $playerID == 1 ? $p1Key : $p2Key;
      header("Location: " . $redirectPath . "/Start.php?gameName=$gameName&playerID=$playerID&authKey=$authKey");
      exit;
    case "REMATCH":
      global $GameStatus_Rematch, $inGameStatus;
      if($lastResult == "YES")
      {
        $inGameStatus = $GameStatus_Rematch;
        ClearGameFiles($gameName);
      }
      return 0;
    case "UNIQUETOMZ":
      return SearchUniqueMultizone($parameter, $player);
    case "PLAYERTARGETEDABILITY":
      PlayerTargetedAbility($player, $parameter, $lastResult);
      return "";
    case "DQPAYORDISCARD":
      PayOrDiscard($player, $parameter);
      return "";
    case "SPECIFICCARD":
      return SpecificCardLogic($player, $parameter, $lastResult);
    case "MZADDSTEAMCOUNTER":
      $lastResultArr = explode(",", $lastResult);
      $otherPlayer = ($player == 1 ? 2 : 1);
      $params = explode(",", $parameter);
      for($i = 0; $i < count($lastResultArr); ++$i) {
        $mzIndex = explode("-", $lastResultArr[$i]);
        switch($mzIndex[0]) {
          case "MYITEMS":
            $items = &GetItems($player);
            $items[$mzIndex[1] + 1 ] += 1;
            WriteLog(CardLink($items[$mzIndex[1]], $items[$mzIndex[1]]) . " gained a steam counter");
            break;
          default: break;
        }
      }
      return $lastResult;
    case "HITEFFECT":
      ProcessHitEffect($parameter);
      return $parameter;
    case "PASSREVERT":
      if($lastResult == "PASS") {
        WriteLog("That is not a valid action; reverting gamestate.");
        RevertGamestate();
      }
      return $lastResult;
    case "PROCESSDAMAGEPREVENTION":
      $mzIndex = explode("-", $lastResult);
      $params =  explode("-", $parameter);
      switch($mzIndex[0])
      {
        case "MYAURAS": $damage = AuraTakeDamageAbility($player, intval($mzIndex[1]), $params[0], $params[1]); break;
        case "MYCHAR": $damage = CharacterTakeDamageAbility($player, intval($mzIndex[1]), $params[0], $params[1]); break;
        case "MYALLY": $damage = AllyTakeDamageAbilities($player, intval($mzIndex[1]), $params[0], $params[1]); break;
        default: break;
      }
      if($damage < 0) $damage = 0;
      $dqVars[0] = $damage;
      $dqState[6] = $damage;
      if($damage > 0) AddDamagePreventionSelection($player, $damage, $params[1]);
      return $damage;
    case "ALLRANDOMBOTTOM":
      if($lastResult == "PASS") return "";
      $cards = explode(",", $lastResult);
      shuffle($cards);
      for($i=0; $i<count($cards); ++$i) {
        AddBottomDeck($cards[$i], $player);
      }
      return "";
    case "EQUIPCARD":
      EquipCard($player, $parameter);
      return "";
    case "ATTACK":
      global $CCS_WeaponIndex, $CS_PlayIndex;
      $params = explode(",", $parameter);
      if(CardType($params[0]) == "AA" || GetResolvedAbilityType($params[0], $params[1]) == "AA") {
        $combatChainState[$CCS_WeaponIndex] = GetClassState($player, $CS_PlayIndex);
        Attack($params[0]);
      }
      return $lastResult;
    case "STARTTURN":
      StartTurn();
      return $lastResult;
    case "MILL":
      Mill($player, $parameter);
      return "";
    case "RESERVABLE":
      $resources = &GetResources($player);
      $resources[0] += count($lastResult);
      $auras = &GetAuras($player);
      for($i = 0; $i < count($lastResult); ++$i) {
        $auras[$lastResult[$i] + 1] = 1;
      }
      return "";
    case "CARDDISCARDED":
      CardDiscarded($player, $lastResult);
      return $lastResult;
    case "NEGATE":
      NegateLayer($parameter);
      return "";
    case "DRAWINTOMEMORY":
      DrawIntoMemory($player);
      return "";
    case "MULTIDISTRIBUTEDAMAGE":
      if(!is_array($lastResult)) $lastResult = explode(",", $lastResult);
      if(!is_array($parameter)) $parameter = explode(",", $parameter);
      if($parameter[0] == "-") {
        $dqVars[0] = $dqVars[0] - $dqVars[1];
        $parameter[0] = $dqVars[0];
      }
      else {
        $dqVars[0] = $parameter[0];
      }
      $theirAllies = &GetAllies($player == 1 ? 2 : 1);
      $index = $lastResult[count($lastResult) - 1];
      unset($lastResult[count($lastResult) - 1]);
      $lastResult = array_values($lastResult);
      if(count($lastResult) > 0) {
        PrependDecisionQueue("MULTIDISTRIBUTEDAMAGE", $player, "-,$parameter[1]");
        PrependDecisionQueue("PASSPARAMETER", $player, implode(",", $lastResult));
      }
      PrependDecisionQueue("MZOP", $player, "DEALDAMAGE,{1},$player,$parameter[1]");
      PrependDecisionQueue("PASSPARAMETER", $player, "THEIRALLY-" . $index);
      PrependDecisionQueue("SETDQVAR", $player, "1");
      PrependDecisionQueue("BUTTONINPUTNOPASS", $player, GetIndices($parameter[0] + 1));
      PrependDecisionQueue("SETDQCONTEXT", $player, "Choose an amount of damage to deal to " . CardLink($theirAllies[$index], $theirAllies[$index]));
      return $lastResult;
    default:
      return "NOTSTATIC";
  }
}
