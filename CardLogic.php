<?php

include "CardDictionary.php";
include "CoreLogic.php";

function PummelHit($player = -1, $passable = false, $fromDQ = false, $context="", $may=false)
{
  global $defPlayer;
  if($player == -1) $player = $defPlayer;
  if($context == "") $context = "Choose a card to discard";
  if($fromDQ)
  {
    PrependDecisionQueue("CARDDISCARDED", $player, "-", 1);
    PrependDecisionQueue("ADDDISCARD", $player, "HAND", 1);
    PrependDecisionQueue("MULTIREMOVEHAND", $player, "-", 1);
    if($may) PrependDecisionQueue("MAYCHOOSEHAND", $player, "<-", 1);
    else PrependDecisionQueue("CHOOSEHAND", $player, "<-", 1);
    PrependDecisionQueue("SETDQCONTEXT", $player, $context, 1);
    PrependDecisionQueue("FINDINDICES", $player, "HAND", ($passable ? 1 : 0));
  }
  else {
    AddDecisionQueue("FINDINDICES", $player, "HAND", ($passable ? 1 : 0));
    AddDecisionQueue("SETDQCONTEXT", $player, $context, 1);
    if($may) AddDecisionQueue("MAYCHOOSEHAND", $player, "<-", 1);
    else AddDecisionQueue("CHOOSEHAND", $player, "<-", 1);
    AddDecisionQueue("MULTIREMOVEHAND", $player, "-", 1);
    AddDecisionQueue("ADDDISCARD", $player, "HAND", 1);
    AddDecisionQueue("CARDDISCARDED", $player, "-", 1);
  }
}

function DefeatUpgrade($player, $may = false, $search="MYALLY&THEIRALLY", $upgradeFilter="", $to="DISCARD", $passable=false) {
  $verb = "";
  switch($to) {
    case "DISCARD": $verb = "defeat"; break;
    case "HAND": $verb = "bounce"; break;
  }
  if($passable) {
    AddDecisionQueue("MULTIZONEINDICES", $player, $search, 1);
    AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to " . $verb . " an upgrade from", 1);
  }
  else {
    AddDecisionQueue("MULTIZONEINDICES", $player, $search);
    AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to " . $verb . " an upgrade from");
  }
  if($may) AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  else AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("SETDQVAR", $player, 0, 1);
  AddDecisionQueue("MZOP", $player, "GETUPGRADES", 1);
  if($upgradeFilter != "") AddDecisionQueue("MZFILTER", $player, $upgradeFilter, 1);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose an upgrade to defeat");
  if($may) AddDecisionQueue("MAYCHOOSECARD", $player, "<-", 1);
  else AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
  if($to == "DISCARD") AddDecisionQueue("OP", $player, "DEFEATUPGRADE", 1);
  else if($to == "HAND") AddDecisionQueue("OP", $player, "BOUNCEUPGRADE", 1);
}

function PlayCaptive($player, $target="")
{
  AddDecisionQueue("PASSPARAMETER", $player, $target);
  AddDecisionQueue("SETDQVAR", $player, 0);
  AddDecisionQueue("MZOP", $player, "GETCAPTIVES");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a captured unit to play");
  AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
  AddDecisionQueue("OP", $player, "PLAYCAPTIVE", 1);
}

function RescueUnit($player, $target="")
{
  AddDecisionQueue("PASSPARAMETER", $player, $target);
  AddDecisionQueue("SETDQVAR", $player, 0);
  AddDecisionQueue("MZOP", $player, "GETCAPTIVES");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to rescue");
  AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
  AddDecisionQueue("OP", $player, "RESCUECAPTIVE", 1);
}

function HandToTopDeck($player)
{
  AddDecisionQueue("FINDINDICES", $player, "HAND");
  AddDecisionQueue("MAYCHOOSEHAND", $player, "<-", 1);
  AddDecisionQueue("MULTIREMOVEHAND", $player, "-", 1);
  AddDecisionQueue("MULTIADDTOPDECK", $player, "-", 1);
}

function BottomDeck($player="", $mayAbility=false, $shouldDraw=false)
{
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  AddDecisionQueue("FINDINDICES", $player, "HAND");
  AddDecisionQueue("SETDQCONTEXT", $player, "Put_a_card_from_your_hand_on_the_bottom_of_your_deck.");
  if($mayAbility) AddDecisionQueue("MAYCHOOSEHAND", $player, "<-", 1);
  else AddDecisionQueue("CHOOSEHAND", $player, "<-", 1);
  AddDecisionQueue("REMOVEMYHAND", $player, "-", 1);
  AddDecisionQueue("ADDBOTDECK", $player, "-", 1);
  AddDecisionQueue("WRITELOG", $player, "A card was put on the bottom of the deck", 1);
  if($shouldDraw) AddDecisionQueue("DRAW", $player, "-", 1);
}

function BottomDeckMultizone($player, $zone1, $zone2)
{
  AddDecisionQueue("MULTIZONEINDICES", $player, $zone1 . "&" . $zone2, 1);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to sink (or Pass)", 1);
  AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZREMOVE", $player, "-", 1);
  AddDecisionQueue("ADDBOTDECK", $player, "-", 1);
}

function AddCurrentTurnEffectNextAttack($cardID, $player, $from = "", $uniqueID = -1)
{
  global $combatChain;
  if(count($combatChain) > 0) AddCurrentTurnEffectFromCombat($cardID, $player, $uniqueID);
  else AddCurrentTurnEffect($cardID, $player, $from, $uniqueID);
}

function AddCurrentTurnEffect($cardID, $player, $from = "", $uniqueID = -1)
{
  global $currentTurnEffects, $combatChain;
  $card = explode("-", $cardID)[0];
  if(CardType($card) == "A" && count($combatChain) > 0 && IsCombatEffectActive($cardID) && !IsCombatEffectPersistent($cardID) && $from != "PLAY") {
    AddCurrentTurnEffectFromCombat($cardID, $player, $uniqueID);
    return;
  }
  array_push($currentTurnEffects, $cardID, $player, $uniqueID, CurrentTurnEffectUses($cardID));
}

function AddAfterResolveEffect($cardID, $player, $from = "", $uniqueID = -1)
{
  global $afterResolveEffects, $combatChain;
  $card = explode("-", $cardID)[0];
  if(CardType($card) == "A" && count($combatChain) > 0 && !IsCombatEffectPersistent($cardID) && $from != "PLAY") {
    AddCurrentTurnEffectFromCombat($cardID, $player, $uniqueID);
    return;
  }
  array_push($afterResolveEffects, $cardID, $player, $uniqueID, CurrentTurnEffectUses($cardID));
}

function HasLeader($player) {
  return SearchCount(SearchAllies($player, definedType:"Leader")) > 0;
}

function HasMoreUnits($player) {
  $allies = &GetAllies($player);
  $theirAllies = &GetAllies($player == 1 ? 2 : 1);
  return count($allies) > count($theirAllies);
}

function CopyCurrentTurnEffectsFromAfterResolveEffects()
{
  global $currentTurnEffects, $afterResolveEffects;
  for($i = 0; $i < count($afterResolveEffects); $i += CurrentTurnEffectPieces()) {
    array_push($currentTurnEffects, $afterResolveEffects[$i], $afterResolveEffects[$i+1], $afterResolveEffects[$i+2], $afterResolveEffects[$i+3]);
  }
  $afterResolveEffects = [];
}

//This is needed because if you add a current turn effect from combat, it could get deleted as part of the combat resolution
function AddCurrentTurnEffectFromCombat($cardID, $player, $uniqueID = -1)
{
  global $currentTurnEffectsFromCombat;
  array_push($currentTurnEffectsFromCombat, $cardID, $player, $uniqueID, CurrentTurnEffectUses($cardID));
}

function CopyCurrentTurnEffectsFromCombat()
{
  global $currentTurnEffects, $currentTurnEffectsFromCombat;
  for($i = 0; $i < count($currentTurnEffectsFromCombat); $i += CurrentTurnEffectPieces()) {
    array_push($currentTurnEffects, $currentTurnEffectsFromCombat[$i], $currentTurnEffectsFromCombat[$i+1], $currentTurnEffectsFromCombat[$i+2], $currentTurnEffectsFromCombat[$i+3]);
  }
  $currentTurnEffectsFromCombat = [];
}

function RemoveCurrentTurnEffect($index)
{
  global $currentTurnEffects;
  unset($currentTurnEffects[$index+3]);
  unset($currentTurnEffects[$index+2]);
  unset($currentTurnEffects[$index+1]);
  unset($currentTurnEffects[$index]);
  $currentTurnEffects = array_values($currentTurnEffects);
}

function CurrentTurnEffectPieces()
{
  return 4;
}

function CurrentTurnEffectUses($cardID)
{
  switch ($cardID) {
    case "EVR033": return 6;
    case "EVR034": return 5;
    case "EVR035": return 4;
    case "UPR000": return 3;
    case "UPR088": return 4;
    case "UPR221": return 4;
    case "UPR222": return 3;
    case "UPR223": return 2;
    default: return 1;
  }
}

function AddNextTurnEffect($cardID, $player, $uniqueID = -1)
{
  global $nextTurnEffects;
  array_push($nextTurnEffects, $cardID, $player, $uniqueID, CurrentTurnEffectUses($cardID));
}

function IsCombatEffectLimited($index)
{
  global $currentTurnEffects, $combatChain, $mainPlayer, $combatChainState, $CCS_WeaponIndex, $CCS_AttackUniqueID;
  if(count($combatChain) == 0 || $currentTurnEffects[$index + 2] == -1) return false;
  $attackSubType = CardSubType($combatChain[0]);
  if(DelimStringContains($attackSubType, "Ally")) {
    $allies = &GetAllies($mainPlayer);
    if(count($allies) < $combatChainState[$CCS_WeaponIndex] + 5) return false;
    if($allies[$combatChainState[$CCS_WeaponIndex] + 5] != $currentTurnEffects[$index + 2]) return true;
  } else {
    return $combatChainState[$CCS_AttackUniqueID] != $currentTurnEffects[$index + 2];
  }
  return false;
}

function PrependLayer($cardID, $player, $parameter, $target = "-", $additionalCosts = "-", $uniqueID = "-")
{
    global $layers;
    array_push($layers, $cardID, $player, $parameter, $target, $additionalCosts, $uniqueID, GetUniqueId());
    return count($layers);//How far it is from the end
}

function IsAbilityLayer($cardID)
{
  return $cardID == "TRIGGER" || $cardID == "PLAYABILITY" || $cardID == "ATTACKABILITY" || $cardID == "ACTIVATEDABILITY" || $cardID == "AFTERPLAYABILITY";
}

function AddLayer($cardID, $player, $parameter, $target = "-", $additionalCosts = "-", $uniqueID = "-", $append = false)
{
  global $layers, $dqState;
  //Layers are on a stack, so you need to push things on in reverse order
  if($append) {
    array_push($layers, $cardID, $player, $parameter, $target, $additionalCosts, $uniqueID, GetUniqueId());
    if(IsAbilityLayer($cardID))
    {
      $orderableIndex = intval($dqState[8]);
      if($orderableIndex == -1) $dqState[8] = LayerPieces();
    }
    return LayerPieces();
  }
  array_unshift($layers, GetUniqueId());
  array_unshift($layers, $uniqueID);
  array_unshift($layers, $additionalCosts);
  array_unshift($layers, $target);
  array_unshift($layers, $parameter);
  array_unshift($layers, $player);
  array_unshift($layers, $cardID);
  if(IsAbilityLayer($cardID))
  {
    $orderableIndex = intval($dqState[8]);
    if($orderableIndex == -1) $dqState[8] = 0;
    else $dqState[8] += LayerPieces();
  }
  else $dqState[8] = -1;//If it's not a trigger, it's not orderable
  return count($layers);//How far it is from the end
}

function AddDecisionQueue($phase, $player, $parameter, $subsequent = 0, $makeCheckpoint = 0)
{
  global $decisionQueue;
  if(count($decisionQueue) == 0) $insertIndex = 0;
  else {
    $insertIndex = count($decisionQueue) - DecisionQueuePieces();
    if(!IsGamePhase($decisionQueue[$insertIndex])) //Stack must be clear before you can continue with the step
    {
      $insertIndex = count($decisionQueue);
    }
  }

  $parameter = str_replace(" ", "_", $parameter);
  array_splice($decisionQueue, $insertIndex, 0, $phase);
  array_splice($decisionQueue, $insertIndex + 1, 0, $player);
  array_splice($decisionQueue, $insertIndex + 2, 0, $parameter);
  array_splice($decisionQueue, $insertIndex + 3, 0, $subsequent);
  array_splice($decisionQueue, $insertIndex + 4, 0, $makeCheckpoint);
}

function PrependDecisionQueue($phase, $player, $parameter, $subsequent = 0, $makeCheckpoint = 0)
{
  global $decisionQueue;
  if($parameter == null || $parameter == "") return;
  $parameter = str_replace(" ", "_", $parameter);
  array_unshift($decisionQueue, $makeCheckpoint);
  array_unshift($decisionQueue, $subsequent);
  array_unshift($decisionQueue, $parameter);
  array_unshift($decisionQueue, $player);
  array_unshift($decisionQueue, $phase);
}

function IsDecisionQueueActive()
{
  global $dqState;
  return $dqState[0] == "1";
}

function ProcessDecisionQueue()
{
  global $turn, $decisionQueue, $dqState;
  if($dqState[0] != "1") {
    $count = count($turn);
    if(count($turn) < 3) $turn[2] = "-";
    $dqState[0] = "1"; //If the decision queue is currently active/processing
    $dqState[1] = $turn[0];
    $dqState[2] = $turn[1];
    $dqState[3] = $turn[2];
    $dqState[4] = "-"; //DQ helptext initial value
    $dqState[5] = "-"; //Decision queue multizone indices
    $dqState[6] = "0"; //Damage dealt
    $dqState[7] = "0"; //Target
    ContinueDecisionQueue("");
  }
}

function CloseDecisionQueue()
{
  global $turn, $decisionQueue, $dqState, $combatChain, $currentPlayer, $mainPlayer;
  $dqState[0] = "0";
  $turn[0] = $dqState[1];
  $turn[1] = $dqState[2];
  $turn[2] = $dqState[3];
  $dqState[4] = "-"; //Clear the context, just in case
  $dqState[5] = "-"; //Clear Decision queue multizone indices
  $dqState[6] = "0"; //Damage dealt
  $dqState[7] = "0"; //Target
  $dqState[8] = "-1"; //Orderable index (what layer after which triggers can be reordered)
  $decisionQueue = [];
  if(($turn[0] == "D" || $turn[0] == "A") && count($combatChain) == 0) {
    $currentPlayer = $mainPlayer;
    $turn[0] = "M";
  }
}

function ShouldHoldPriorityNow($player)
{
  global $layerPriority, $layers, $currentPlayer, $dqState;
  if($player != $currentPlayer) return false;
  if(count($layers) == LayerPieces()) return false;
  return $dqState[8] > 0;
}

function SkipHoldingPriorityNow($player)
{
  global $layerPriority;
  $layerPriority[$player - 1] = "0";
}

function IsGamePhase($phase)
{
  switch ($phase) {
    case "RESUMEPAYING":
    case "RESUMEPLAY":
    case "RESOLVECHAINLINK":
    case "RESOLVECOMBATDAMAGE":
    case "PASSTURN":
      return true;
    default: return false;
  }
}

//Must be called with the my/their context
function ContinueDecisionQueue($lastResult = "")
{
  global $decisionQueue, $turn, $currentPlayer, $mainPlayerGamestateStillBuilt, $makeCheckpoint, $otherPlayer;
  global $layers, $layerPriority, $dqVars, $dqState, $CS_PlayIndex, $CS_AdditionalCosts, $mainPlayer, $CS_LayerPlayIndex, $CS_OppCardActive;
  global $CS_ResolvingLayerUniqueID;

  if(count($decisionQueue) == 0 || IsGamePhase($decisionQueue[0])) {
    if($mainPlayerGamestateStillBuilt) UpdateMainPlayerGameState();
    else if(count($decisionQueue) > 0 && $currentPlayer != $decisionQueue[1]) {
      UpdateGameState($currentPlayer);
    }
    if(count($decisionQueue) == 0 && count($layers) > 0) {
      $priorityHeld = 0;
      if($mainPlayer == 1) {
        if(ShouldHoldPriorityNow(1)) {
          AddDecisionQueue("INSTANT", 1, "-");
          $priorityHeld = 1;
          $layerPriority[0] = 0;
        }
        if(ShouldHoldPriorityNow(2)) {
          AddDecisionQueue("INSTANT", 2, "-");
          $priorityHeld = 1;
          $layerPriority[1] = 0;
        }
      } else {
        if(ShouldHoldPriorityNow(2)) {
          AddDecisionQueue("INSTANT", 2, "-");
          $priorityHeld = 1;
          $layerPriority[1] = 0;
        }
        if(ShouldHoldPriorityNow(1)) {
          AddDecisionQueue("INSTANT", 1, "-");
          $priorityHeld = 1;
          $layerPriority[0] = 0;
        }
      }
      global $combatChain;
      if($priorityHeld) {
        ContinueDecisionQueue("");
      } else {
        CloseDecisionQueue();
        $cardID = array_shift($layers);
        $player = array_shift($layers);
        $parameter = array_shift($layers);
        $target = array_shift($layers);
        $additionalCosts = array_shift($layers);
        $uniqueID = array_shift($layers);
        $layerUniqueID = array_shift($layers);
        //WriteLog("CardID:" . $cardID . " Player:" . $player . " Param:" . $parameter . " UniqueID:" . $uniqueID);//Uncomment this to visualize layer execution
        SetClassState($player, $CS_ResolvingLayerUniqueID, $layerUniqueID);
        $params = explode("|", $parameter);
        if($currentPlayer != $player) {
          if($mainPlayerGamestateStillBuilt) UpdateMainPlayerGameState();
          else UpdateGameState($currentPlayer);
          $currentPlayer = $player;
          $otherPlayer = $currentPlayer == 1 ? 2 : 1;
          BuildMyGamestate($currentPlayer);
        }
        $layerPriority[0] = ShouldHoldPriority(1);
        $layerPriority[1] = ShouldHoldPriority(2);
        if($cardID == "ENDTURN") EndStep();
        else if($cardID == "ENDSTEP") FinishTurnPass();
        else if($cardID == "RESUMETURN") $turn[0] = "M";
        else if($cardID == "LAYER") ProcessLayer($player, $parameter);
        else if($cardID == "FINALIZECHAINLINK") FinalizeChainLink($parameter);
        else if($cardID == "DEFENDSTEP") { $turn[0] = "A"; $currentPlayer = $mainPlayer; }
        else if(IsAbilityLayer($cardID)) {
          if(count($combatChain) > 0) {
            AddAfterCombatLayer($cardID, $player, $parameter, $target, $additionalCosts, $uniqueID);
            ProcessDecisionQueue();
          } else {
            global $CS_AbilityIndex;
            if($cardID == "TRIGGER") {
              ProcessTrigger($player, $parameter, $uniqueID, $additionalCosts, $target);
              ProcessDecisionQueue();
            }
            else {
              $oppCardActive = GetClassState($currentPlayer, $CS_OppCardActive) >= 0;

              $cardID = $parameter;
              $subparamArr = explode("!", $target);
              $from = $subparamArr[0];
              $resourcesPaid = $subparamArr[1];
              $target = count($subparamArr) > 2 ? $subparamArr[2] : "-";
              $additionalCosts = count($subparamArr) > 3 ? $subparamArr[3] : "-";
              $abilityIndex = count($subparamArr) > 4 ? $subparamArr[4] : -1;
              $playIndex = count($subparamArr) > 5 ? $subparamArr[5] : -1;
                SetClassState($player, $CS_AbilityIndex, $abilityIndex);
                SetClassState($player, $CS_PlayIndex, $playIndex);
                $playText = PlayAbility($cardID, $from, $resourcesPaid, $target, $additionalCosts, $oppCardActive);
                if($from != "PLAY") WriteLog("Resolving play ability of " . CardLink($cardID, $cardID) . ($playText != "" ? ": " : ".") . $playText);
                if($from == "EQUIP") {
                  EquipPayAdditionalCosts(FindCharacterIndex($player, $cardID), "EQUIP");
                }
                ProcessDecisionQueue();
            }
          }
        }
        else {
          SetClassState($player, $CS_PlayIndex, $params[2]); //This is like a parameter to PlayCardEffect and other functions
          PlayCardEffect($cardID, $params[0], $params[1], $target, $additionalCosts, $params[3], $params[2]);
          ClearDieRoll($player);
        }
      }
    } else if(count($decisionQueue) > 0 && $decisionQueue[0] == "RESUMEPLAY") {
      if($currentPlayer != $decisionQueue[1]) {
        $currentPlayer = $decisionQueue[1];
        $otherPlayer = $currentPlayer == 1 ? 2 : 1;
        BuildMyGamestate($currentPlayer);
      }
      $params = explode("|", $decisionQueue[2]);
      CloseDecisionQueue();
      if($turn[0] == "B" && count($layers) == 0) //If a layer is not created
      {
        PlayCardEffect($params[0], $params[1], $params[2], "-", $params[3], $params[4]);
      } else {
        //params 3 = ability index
        //params 4 = Unique ID
        $additionalCosts = GetClassState($currentPlayer, $CS_AdditionalCosts);
        if($additionalCosts == "") $additionalCosts = "-";
        $layerIndex = count($layers) - GetClassState($currentPlayer, $CS_LayerPlayIndex);
        $layers[$layerIndex + 2] = $params[1] . "|" . $params[2] . "|" . $params[3] . "|" . $params[4];
        $layers[$layerIndex + 4] = $additionalCosts;
        ProcessDecisionQueue();
        return;
      }
    } else if(count($decisionQueue) > 0 && $decisionQueue[0] == "RESUMEPAYING") {
      $player = $decisionQueue[1];
      $params = explode("-", $decisionQueue[2]); //Parameter
      if($lastResult == "") $lastResult = 0;
      CloseDecisionQueue();
      if($currentPlayer != $player) {
        $currentPlayer = $player;
        $otherPlayer = $currentPlayer == 1 ? 2 : 1;
        BuildMyGamestate($currentPlayer);
      }
      PlayCard($params[0], $params[1], $lastResult, $params[2]);
    } else if(count($decisionQueue) > 0 && $decisionQueue[0] == "RESOLVECHAINLINK") {
      CloseDecisionQueue();
      ResolveChainLink();
    } else if(count($decisionQueue) > 0 && $decisionQueue[0] == "RESOLVECOMBATDAMAGE") {
      $parameter = $decisionQueue[2];
      if($parameter != "-") $damageDone = $parameter;
      else $damageDone = $dqState[6];
      CloseDecisionQueue();
      ResolveCombatDamage($damageDone);
    } else if(count($decisionQueue) > 0 && $decisionQueue[0] == "PASSTURN") {
      CloseDecisionQueue();
      PassTurn();
    } else {
      CloseDecisionQueue();
      FinalizeAction();
    }
    return;
  }
  $phase = array_shift($decisionQueue);
  $player = array_shift($decisionQueue);
  $parameter = array_shift($decisionQueue);
  // WriteLog("->" . $phase . " " . $player . " Param:" . $parameter . " LR:" . $lastResult);//Uncomment this to visualize decision queue execution
  $parameter = str_replace("{I}", $dqState[5], $parameter);
  if(count($dqVars) > 0) {
    if(str_contains($parameter, "{0}")) $parameter = str_replace("{0}", $dqVars[0], $parameter);
    if(str_contains($parameter, "<0>")) $parameter = str_replace("<0>", CardLink($dqVars[0], $dqVars[0]), $parameter);
    if(str_contains($parameter, "{1}")) $parameter = str_replace("{1}", $dqVars[1], $parameter);
    if(str_contains($parameter, "{2}")) $parameter = str_replace("{2}", $dqVars[2], $parameter);
  }
  if(count($dqVars) > 1) $parameter = str_replace("<1>", CardLink($dqVars[1], $dqVars[1]), $parameter);
  $parameter = str_replace(" ", "_", $parameter);//CardLink()s contain spaces, which can break things if this $parameter makes it to WriteGamestate.php(such as if $phase is YESNO). But CardLink() is also used in some cases where the underscores would show up directly, so I fix this here.
  $subsequent = array_shift($decisionQueue);
  $makeCheckpoint = array_shift($decisionQueue);
  $turn[0] = $phase;
  $turn[1] = $player;
  $currentPlayer = $player;
  $turn[2] = ($parameter == "<-" ? $lastResult : $parameter);
  $return = "PASS";
  if($subsequent != 1 || is_array($lastResult) || strval($lastResult) != "PASS") $return = DecisionQueueStaticEffect($phase, $player, ($parameter == "<-" ? $lastResult : $parameter), $lastResult);
  if($parameter == "<-" && !is_array($lastResult) && $lastResult == "-1") $return = "PASS"; //Collapse the rest of the queue if this decision point has invalid parameters
  if(is_array($return) || strval($return) != "NOTSTATIC") {
    if($phase != "SETDQCONTEXT") $dqState[4] = "-"; //Clear out context for static states -- context only persists for one choice
    ContinueDecisionQueue($return);
  } else {
    if($mainPlayerGamestateStillBuilt) UpdateMainPlayerGameState();
  }
}

function AddAfterCombatLayer($cardID, $player, $parameter, $target = "-", $additionalCosts = "-", $uniqueID = "-") {
  global $combatChainState, $CCS_AfterLinkLayers;
  if($combatChainState[$CCS_AfterLinkLayers] == "NA") $combatChainState[$CCS_AfterLinkLayers] = $cardID . "~" . $player . "~" . $parameter . "~" . $target . "~" . $additionalCosts . "~" . $uniqueID;
  else $combatChainState[$CCS_AfterLinkLayers] .= "|" . $cardID . "~" . $player . "~" . $parameter . "~" . $target . "~" . $additionalCosts . "~" . $uniqueID;
}

function ProcessAfterCombatLayer() {
  global $combatChainState, $CCS_AfterLinkLayers;
  if($combatChainState[$CCS_AfterLinkLayers] == "NA") return;
  $layers = explode("|", $combatChainState[$CCS_AfterLinkLayers]);
  $combatChainState[$CCS_AfterLinkLayers] = "NA";
  for($i = 0; $i < count($layers); $i++) {
    $layer = explode("~", $layers[$i]);
    AddLayer($layer[0], $layer[1], $layer[2], $layer[3], $layer[4], $layer[5], append:true);
  }
}

function ProcessLayer($player, $parameter)
{
  switch ($parameter) {
    case "PHANTASM":
      PhantasmLayer();
      break;
    default: break;
  }
}

function ProcessTrigger($player, $parameter, $uniqueID, $additionalCosts, $target="-")
{
  global $combatChain, $CS_NumNonAttackCards, $CS_ArcaneDamageDealt, $CS_NumRedPlayed, $CS_DamageTaken, $EffectContext;
  global $CID_BloodRotPox, $CID_Inertia, $CID_Frailty, $combatChainState, $CCS_IsAmbush;
  $items = &GetItems($player);
  $character = &GetPlayerCharacter($player);
  $auras = &GetAuras($player);
  $parameter = ShiyanaCharacter($parameter);
  $EffectContext = $parameter;
  switch ($parameter) {
    case "AMBUSH":
      $index = SearchAlliesForUniqueID($uniqueID, $player);
      AddDecisionQueue("YESNO", $player, "if_you_want_to_resolve_the_ambush_attack");
      AddDecisionQueue("NOPASS", $player, "-");
      AddDecisionQueue("PASSPARAMETER", $player, 1, 1);
      AddDecisionQueue("SETCOMBATCHAINSTATE", $player, $CCS_IsAmbush, 1);
      AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $index, 1);
      AddDecisionQueue("MZOP", $player, "ATTACK", 1);
      break;
    case "SHIELDED":
      $index = SearchAlliesForUniqueID($uniqueID, $player);
      $ally = new Ally("MYALLY-" . $index, $player);
      $ally->Attach("8752877738");//Shield Token
      break;
    case "PLAYALLY":
      PlayAlly($target, $player, from:"CAPTIVE");
      break;
    case "AFTERPLAYABILITY":
      $arr = explode(",", $uniqueID);
      $abilityID = $arr[0];
      $uniqueID = $arr[1];
      AllyPlayCardAbility($target, $player, from: $additionalCosts, abilityID:$abilityID, uniqueID:$uniqueID);
      break;
    case "9642863632":
      global $CS_AfterPlayedBy;
      AddDecisionQueue("FINDINDICES", $player, "DECKTOPXREMOVE," . $target);
      AddDecisionQueue("SETDQVAR", $player, "0", 1);
      AddDecisionQueue("FILTER", $player, "LastResult-include-maxCost-3", 1);
      AddDecisionQueue("FILTER", $player, "LastResult-include-definedType-Unit", 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to play");
      AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
      AddDecisionQueue("SETDQVAR", $player, "1");
      AddDecisionQueue("OP", $player, "REMOVECARD");
      AddDecisionQueue("ALLRANDOMBOTTOM", $player, "DECK");
      AddDecisionQueue("PASSPARAMETER", $player, "{1}");
      AddDecisionQueue("ADDCURRENTEFFECT", $player, "9642863632", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "9642863632", 1);
      AddDecisionQueue("SETCLASSSTATE", $player, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("PASSPARAMETER", $player, "{1}", 1);
      AddDecisionQueue("OP", $player, "PLAYCARD,DECK", 1);
      break;
    case "7642980906"://Stolen Landspeeder
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYDISCARD:cardID=" . "7642980906");
      AddDecisionQueue("SETDQCONTEXT", $player, "Click the Stolen Landspeeder to play it for free.", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $player, "7642980906", 1);//Cost discount and experience adding.
      AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      AddDecisionQueue("REMOVECURRENTEFFECT", $player, "7642980906");
      break;
    case "7270736993"://Unrefusable Offer
      //There's in theory a minor bug with this implementation: if there's a second copy of the bountied unit in the discard
      //it can be played even if the original unit is somehow removed from the discard before this trigger resolves.
      //I can't think of a way to prevent this without adding functionality to track a specific card between zones.
      global $CS_AfterPlayedBy;
      AddDecisionQueue("YESNO", $player, "if you want to play " . CardLink($target, $target) . " for free off of " . CardLink("7270736993", "7270736993"));
      AddDecisionQueue("NOPASS", $player, "-");
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRDISCARD:cardID=" . $target . ";maxCount=1", 1);
      AddDecisionQueue("SETDQVAR", $player, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "7270736993", 1);
      AddDecisionQueue("SETCLASSSTATE", $player, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $player, "7270736993", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      break;
    case "724979d608"://Cad Bane Unit
      $cadIndex = SearchAlliesForCard($player, "724979d608");
      $otherPlayer = ($player == 1 ? 2 : 1);
      AddDecisionQueue("YESNO", $player, "if you want use Cad Bane's ability");
      AddDecisionQueue("NOPASS", $player, "-");
      AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $cadIndex, 1);
      AddDecisionQueue("ADDMZUSES", $player, "-1", 1);
      AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to deal 2 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $otherPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "DEALDAMAGE,2", 1);
      break;
    case "1384530409"://Cad Bane Leader ability
      $otherPlayer = ($player == 1 ? 2 : 1);
      AddDecisionQueue("YESNO", $player, "if you want use Cad Bane's ability");
      AddDecisionQueue("NOPASS", $player, "-");
      AddDecisionQueue("EXHAUSTCHARACTER", $player, FindCharacterIndex($player, "1384530409"), 1);
      AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to deal 1 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $otherPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "DEALDAMAGE,1", 1);
      break;
    case "4088c46c4d"://Mandalorian Leader Unit
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:maxHealth=6");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "REST", 1);
      break;
    case "9005139831"://Mandalorian Leader Ability
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:maxHealth=4");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "REST", 1);
      AddDecisionQueue("EXHAUSTCHARACTER", $player, FindCharacterIndex($player, "9005139831"), 1);
      break;
    case "4935319539"://Krayt Dragon
      $otherPlayer = ($player == 1 ? 2 : 1);
      $damage = CardCost($target);
      AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "THEIRALLY:arena=Ground");
      AddDecisionQueue("PREPENDLASTRESULT", $otherPlayer, "THEIRCHAR-0,");
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a card to deal " . $damage . " damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $otherPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "DEALDAMAGE," . $damage, 1);
      break;
    case "8506660490"://Darth Vader unit
      AddDecisionQueue("FINDINDICES", $player, "DECKTOPXINDICES,10");
      AddDecisionQueue("FILTER", $player, "Deck-include-aspect-Villainy", 1);
      AddDecisionQueue("FILTER", $player, "Deck-include-maxCost-3", 1);
      AddDecisionQueue("FILTER", $player, "Deck-include-definedType-Unit", 1);
      AddDecisionQueue("SETDQVAR", $player, "0");
      AddDecisionQueue("PREPENDLASTRESULT", $player, "10-", 1);
      AddDecisionQueue("MULTICHOOSEDECK", $player, "<-", 1);
      AddDecisionQueue("MULTIREMOVEDECK", $player, "-", 1);
      AddDecisionQueue("SPECIFICCARD", $player, "DARTHVADER", 1);
      break;
    default: break;
  }
}

function GetDQHelpText()
{
  global $dqState;
  if(count($dqState) < 5) return "-";
  return $dqState[4];
}

function FinalizeAction()
{
  global $currentPlayer, $mainPlayer, $actionPoints, $turn, $combatChain, $defPlayer, $makeBlockBackup, $mainPlayerGamestateStillBuilt;
  global $isPass, $inputMode;
  if(!$mainPlayerGamestateStillBuilt) UpdateGameState(1);
  BuildMainPlayerGamestate();
  if($turn[0] == "M") {
    if(count($combatChain) > 0) //Means we initiated a chain link
    {
      $turn[0] = "B";
      $currentPlayer = $defPlayer;
      $turn[2] = "";
      $makeBlockBackup = 1;
    } else {
      $turn[0] = "M";
      $currentPlayer = $mainPlayer;
      $turn[2] = "";
      if(!$isPass || $inputMode == 99) SwapTurn();
      $isPass = false;
    }
  } else if($turn[0] == "A") {
    $currentPlayer = $mainPlayer;
    $turn[2] = "";
  } else if($turn[0] == "D") {
    $turn[0] = "A";
    $currentPlayer = $mainPlayer;
    $turn[2] = "";
  } else if($turn[0] == "B") {
    $turn[0] = "B";
  }
  return 0;
}

function IsReactionPhase()
{
  global $turn, $dqState;
  if($turn[0] == "A" || $turn[0] == "D") return true;
  if(count($dqState) >= 2 && ($dqState[1] == "A" || $dqState[1] == "D")) return true;
  return false;
}

//Return whether priority should be held for the player by default/settings
function ShouldHoldPriority($player, $layerCard = "")
{
  global $mainPlayer;
  $prioritySetting = HoldPrioritySetting($player);
  if($prioritySetting == 0 || $prioritySetting == 1) return 1;
  if(($prioritySetting == 2 || $prioritySetting == 3) && $player != $mainPlayer) return 1;
  return 0;
}

function EndTurnProcedure($player) {
  $allies = &GetAllies($player);
  for($i = 0; $i < count($allies); $i += AllyPieces()) {
    $ally = new Ally("MYALLY-" . $i, $player);
    $ally->Ready();
  }
  $resources = &GetResourceCards($player);
  for($i=0; $i<count($resources); $i+=ResourcePieces()) {
    $resources[$i+4] = "0";
  }
  Draw($player);
  Draw($player);
  MZMoveCard($player, "MYHAND", "MYRESOURCES", may:true, context:"Choose a card to resource", silent:true);
  AddDecisionQueue("AFTERRESOURCE", $player, "HAND", 1);
}

function TopDeckToArsenal($player)
{
  $deck = &GetDeck($player);
  if(ArsenalFull($player) || count($deck) == 0) return;
  AddArsenal(array_shift($deck), $player, "DECK", "DOWN");
  WriteLog("The top card of player " . $player . "'s deck was put in their arsenal");
}

function DiscardHand($player)
{
  $hand = &GetHand($player);
  for($i = count($hand)-HandPieces(); $i>=0; $i-=HandPieces()) {
    DiscardCard($player, $i);
  }
}

function Opt($cardID, $amount)
{
  global $currentPlayer;
  PlayerOpt($currentPlayer, $amount);
}

function PlayerOpt($player, $amount, $optKeyword = true)
{
  AddDecisionQueue("FINDINDICES", $player, "DECKTOPXREMOVE," . $amount);
  AddDecisionQueue("OPT", $player, "<-", 1);
}

function DiscardRandom($player = "", $source = "")
{
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  $hand = &GetHand($player);
  if(count($hand) == 0) return "";
  $index = GetRandom() % count($hand);
  $discarded = $hand[$index];
  unset($hand[$index]);
  $hand = array_values($hand);
  AddGraveyard($discarded, $player, "HAND");
  WriteLog(CardLink($discarded, $discarded) . " was randomly discarded");
  CardDiscarded($player, $discarded, $source);
  DiscardedAtRandomEffects($player, $discarded, $source);
  return $discarded;
}

function DiscardedAtRandomEffects($player, $discarded, $source) {
  switch($discarded) {
    default: break;
  }
}

function DiscardCard($player, $index)
{
  $hand = &GetHand($player);
  $discarded = RemoveHand($player, $index);
  AddGraveyard($discarded, $player, "HAND");
  CardDiscarded($player, $discarded);
  return $discarded;
}

function CardDiscarded($player, $discarded, $source = "")
{
  global $mainPlayer;
  AllyCardDiscarded($player, $discarded);
  AddEvent("DISCARD", $discarded);
}

function DestroyFrozenArsenal($player)
{
  $arsenal = &GetArsenal($player);
  for($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
    if($arsenal[$i + 4] == "1") {
      DestroyArsenal($player);
    }
  }
}

function CanGainAttack()
{
  global $combatChain, $mainPlayer;
  if(SearchCurrentTurnEffects("OUT102", $mainPlayer)) return false;
  return !SearchCurrentTurnEffects("CRU035", $mainPlayer) || CardType($combatChain[0]) != "AA";
}

function IsWeaponGreaterThanTwiceBasePower()
{
  global $combatChainState, $CCS_CachedTotalAttack, $combatChain;
  return count($combatChain) > 0 && CardType($combatChain[0]) == "W" && CachedTotalAttack() > (AttackValue($combatChain[0]) * 2);
}

function HasEnergyCounters($array, $index)
{
  switch($array[$index]) {
    case "WTR150": case "UPR166": return $array[$index+2] > 0;
    default: return false;
  }
}

function SharesAspect($card1, $card2)
{
  $c1Aspects = explode(",", CardAspects($card1));
  $c2Aspects = explode(",", CardAspects($card2));
  for($i=0; $i<count($c1Aspects); $i++) {
    for($j=0; $j<count($c2Aspects); $j++) {
      if($c1Aspects[$i] == $c2Aspects[$j]) return true;
    }
  }
  return false;
}

function BlackOne($player) {
  AddDecisionQueue("SETDQCONTEXT", $player, "Do you want to discard your hand to draw 3?");
  AddDecisionQueue("YESNO", $player, "-");
  AddDecisionQueue("NOPASS", $player, "-");
  AddDecisionQueue("OP", $player, "DISCARDHAND", 1);
  AddDecisionQueue("DRAW", $player, "-", 1);
  AddDecisionQueue("DRAW", $player, "-", 1);
  AddDecisionQueue("DRAW", $player, "-", 1);
  
}