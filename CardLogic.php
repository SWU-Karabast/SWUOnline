<?php

include "CardDictionary.php";
include "CoreLogic.php";
include "Libraries/MZOpHelpers.php";

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

function DefeatUpgrade($player, $may = false, $search="MYALLY:hasUpgradeOnly=true&THEIRALLY:hasUpgradeOnly=true",
  $upgradeFilter="", $to="DISCARD", $passable=false, $mzIndex="-")
{
  $verb = "";
  switch($to) {
    case "DISCARD": $verb = "defeat"; break;
    case "HAND": $verb = "bounce"; break;
  }
  if($mzIndex == "-") {
    if($passable) {
      AddDecisionQueue("MULTIZONEINDICES", $player, $search, 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to " . $verb . " an upgrade from", 1);
    }
    else {
      AddDecisionQueue("MULTIZONEINDICES", $player, $search);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to " . $verb . " an upgrade from");
    }
  } else {
    AddDecisionQueue("PASSPARAMETER", $player, $mzIndex);
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

function PlayCaptive($player, $target) {
  AddDecisionQueue("PASSPARAMETER", $player, $target);
  AddDecisionQueue("SETDQVAR", $player, 0);
  AddDecisionQueue("MZOP", $player, "GETCAPTIVES");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a captured unit to play");
  AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
  AddDecisionQueue("OP", $player, "PLAYCAPTIVE", 1);
}

function RescueUnit($player, $target="", $may=false)
{
  AddDecisionQueue("PASSPARAMETER", $player, $target);
  AddDecisionQueue("SETDQVAR", $player, 0);
  AddDecisionQueue("MZOP", $player, "GETCAPTIVES");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to rescue");
  if($may) AddDecisionQueue("MAYCHOOSECARD", $player, "<-", 1);
  else AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
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

//4 - Lasting type (1 = phase, 2 = round, 3 = permanent). Default: 1 (phase).
function AddCurrentTurnEffect($cardID, $player, $from = "", $uniqueID = -1, $lastingType = 1)
{
  global $currentTurnEffects, $combatChain;
  $card = explode("-", $cardID)[0];
  if(CardType($card) == "A" && count($combatChain) > 0 && IsCombatEffectActive($cardID) && !IsCombatEffectPersistent($cardID) && $from != "PLAY") {
    AddCurrentTurnEffectFromCombat($cardID, $player, $uniqueID);
    return;
  }
  array_push($currentTurnEffects, $cardID, $player, $uniqueID, CurrentTurnEffectUses($cardID), $lastingType);
}

function RemovePhaseEffects() {
  global $currentTurnEffects;
  for ($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $lastingType = $currentTurnEffects[$i + 4];
    if ($lastingType == 1) {
      RemoveCurrentTurnEffect($i);
    }
  }
}

function SwapTurnEffects() {
  global $currentTurnEffects, $nextTurnEffects;

  // Copy permanent effects to next turn effects
  for ($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $lastingType = $currentTurnEffects[$i + 4];
    if ($lastingType == 3) {
      for ($j = 0; $j < CurrentTurnEffectPieces(); $j++) {
        array_push($nextTurnEffects, $currentTurnEffects[$i + $j]);
      }
    }
  }

  $currentTurnEffects = $nextTurnEffects;
  $nextTurnEffects = [];
}

function AddRoundEffect($cardID, $player, $from = "", $uniqueID = -1)
{
  return AddCurrentTurnEffect($cardID, $player, $from, $uniqueID, 2);
}

function AddPermanentEffect($cardID, $player, $from = "", $uniqueID = -1)
{
  return AddCurrentTurnEffect($cardID, $player, $from, $uniqueID, 3);
}

function AddAfterResolveEffect($cardID, $player, $from = "", $uniqueID = -1)
{
  global $afterResolveEffects, $combatChain;
  $card = explode("-", $cardID)[0];
  if(CardType($card) == "A" && count($combatChain) > 0 && !IsCombatEffectPersistent($cardID) && $from != "PLAY") {
    AddCurrentTurnEffectFromCombat($cardID, $player, $uniqueID);
    return;
  }
  array_push($afterResolveEffects, $cardID, $player, $uniqueID, CurrentTurnEffectUses($cardID), 1);
}

function HasLeader($player) {
  return SearchCount(SearchAllies($player, definedType:"Leader")) > 0
    || HasLeaderPilotInPlay($player);
}

function HasLeaderPilotInPlay($player) {
  $allies = GetAllies($player);
  for($i = 0; $i < count($allies); $i+=AllyPieces()) {
    $ally = new Ally($allies[$i+5]);
    if($ally->HasPilotLeaderUpgrade()) return true;
  }

  return false;
}

function HasMoreUnits($player) {
  $allies = &GetAllies($player);
  $theirAllies = &GetTheirAllies($player);
  return count($allies) > count($theirAllies);
}

function HasFewerUnits($player) {
  $allies = &GetAllies($player);
  $theirAllies = &GetTheirAllies($player);
  return count($allies) < count($theirAllies);
}

function CopyCurrentTurnEffectsFromAfterResolveEffects()
{
  global $currentTurnEffects, $afterResolveEffects;
  for($i = 0; $i < count($afterResolveEffects); $i += CurrentTurnEffectPieces()) {
    for ($j = 0; $j < CurrentTurnEffectPieces(); $j++) {
      array_push($currentTurnEffects, $afterResolveEffects[$i + $j]);
    }
  }
  $afterResolveEffects = [];
}

//This is needed because if you add a current turn effect from combat, it could get deleted as part of the combat resolution
function AddCurrentTurnEffectFromCombat($cardID, $player, $uniqueID = -1)
{
  global $currentTurnEffectsFromCombat;
  array_push($currentTurnEffectsFromCombat, $cardID, $player, $uniqueID, CurrentTurnEffectUses($cardID), 1);
}

function CopyCurrentTurnEffectsFromCombat()
{
  global $currentTurnEffects, $currentTurnEffectsFromCombat;
  for($i = 0; $i < count($currentTurnEffectsFromCombat); $i += CurrentTurnEffectPieces()) {
    for ($j = 0; $j < CurrentTurnEffectPieces(); $j++) {
      array_push($currentTurnEffects, $currentTurnEffectsFromCombat[$i + $j]);
    }
  }
  $currentTurnEffectsFromCombat = [];
}

function RemoveCurrentTurnEffect($index)
{
  global $currentTurnEffects;
  for ($i = 0; $i < CurrentTurnEffectPieces(); $i++) {
    unset($currentTurnEffects[$index + $i]);
  }
  $currentTurnEffects = array_values($currentTurnEffects);
}

function CurrentTurnEffectPieces()
{
  return CurrentTurnPieces();
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

//4 - Lasting type (1 = phase, 2 = round, 3 = permanent). Default: 1 (phase).
function AddNextTurnEffect($cardID, $player, $uniqueID = -1, $lastingType = 1)
{
  global $nextTurnEffects;
  array_push($nextTurnEffects, $cardID, $player, $uniqueID, CurrentTurnEffectUses($cardID), $lastingType);
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
  return $cardID == "TRIGGER" || $cardID == "PLAYABILITY" || $cardID == "PLAYCARDABILITY" || $cardID == "ATTACKABILITY" || $cardID == "ACTIVATEDABILITY" || $cardID == "ALLYPLAYCARDABILITY";
}

function AddLayer($cardID, $player, $parameter, $target = "-", $additionalCosts = "-", $uniqueID = "-", $append = false)
{
  global $layers, $dqState;

  if ($append || $cardID == "TRIGGER") {
    array_push($layers, $cardID, $player, $parameter, $target, $additionalCosts, $uniqueID, GetUniqueId());
    if(IsAbilityLayer($cardID)) {
      $orderableIndex = intval($dqState[8]);
      if($orderableIndex == -1) $dqState[8] = LayerPieces();
    }
    return LayerPieces();
  }

  //Layers are on a stack, so you need to push things on in reverse order
  array_unshift($layers, GetUniqueId());
  array_unshift($layers, $uniqueID);
  array_unshift($layers, $additionalCosts);
  array_unshift($layers, $target);
  array_unshift($layers, $parameter);
  array_unshift($layers, $player);
  array_unshift($layers, $cardID);

  if (IsAbilityLayer($cardID)) {
    $orderableIndex = intval($dqState[8]);
    if($orderableIndex == -1) $dqState[8] = 0;
    else $dqState[8] += LayerPieces();
  } else $dqState[8] = -1; //If it's not a trigger, it's not orderable

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
    if($count < 3) $turn[2] = "-";
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
  global $CS_PlayedAsUpgrade;
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
  global $CS_ResolvingLayerUniqueID, $CS_PlayedWithExploit;

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
        // else if($cardID == "LAYER") ProcessLayer($player, $parameter);
        else if($cardID == "FINALIZECHAINLINK") FinalizeChainLink($parameter);
        else if($cardID == "DEFENDSTEP") { $turn[0] = "A"; $currentPlayer = $mainPlayer; }
        else if($cardID == "STARTREGROUPPHASE") StartRegroupPhase();
        else if($cardID == "ENDREGROUPPHASE") EndRegroupPhase();
        else if($cardID == "STARTACTIONPHASE") StartActionPhase();
        else if($cardID == "ENDACTIONPHASE") EndActionPhase();
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
              $oppCardActive = GetClassState($currentPlayer, $CS_OppCardActive) > 0;
              $layerName = $cardID;
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

              // PLAYCARDABILITY already adds ally play card ability layers at the same window.
              // Other layers like PLAYABILITY should be resolved before ally play card ability layers.
              if ($layerName != "PLAYCARDABILITY" && $from != "PLAY" && $from != "EQUIP" && $from != "CHAR") {
                AddAllyPlayCardAbilityLayers($cardID, $from, $uniqueID, $resourcesPaid);
              }

              $playText = PlayAbility($cardID, $from, $resourcesPaid, $target, $additionalCosts, $oppCardActive, uniqueId: $uniqueID);
              if($from != "PLAY") WriteLog("Resolving play ability of " . CardLink($cardID, $cardID) . ($playText != "" ? ": " : ".") . $playText);
              if($from == "EQUIP") EquipPayAdditionalCosts(FindCharacterIndex($player, $cardID), "EQUIP");
              ProcessDecisionQueue();
            }
          }
        }
        else {
          SetClassState($player, $CS_PlayIndex, $params[2]); //This is like a parameter to PlayCardEffect and other functions
          SetClassState($player, $CS_PlayedWithExploit, 0);
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
        $playedWithExploit = (bool)GetClassState($currentPlayer, $CS_PlayedWithExploit);
        $layerIndex = $playedWithExploit ? 0 : count($layers) - GetClassState($currentPlayer, $CS_LayerPlayIndex);
        $layers[$layerIndex + 2] = $params[1] . "|" . $params[2] . "|" . $params[3] . "|" . $params[4];
        $layers[$layerIndex + 4] = $additionalCosts;
        ProcessDecisionQueue();
        return;
      }
    } else if(count($decisionQueue) > 0 && $decisionQueue[0] == "RESUMEPAYING") {
      $player = $decisionQueue[1];
      $params = explode("!", $decisionQueue[2]); //Parameter
      if($lastResult == "") $lastResult = 0;
      CloseDecisionQueue();
      if($currentPlayer != $player) {
        $currentPlayer = $player;
        $otherPlayer = $currentPlayer == 1 ? 2 : 1;
        BuildMyGamestate($currentPlayer);
      }
      $prepaidResources = $params[3] ?? 0;
      PlayCard($params[0], $params[1], $lastResult, $params[2], prepaidResources: $prepaidResources);
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
    if(str_contains($parameter, "{1}") && isset($dqVars[1])) $parameter = str_replace("{1}", $dqVars[1], $parameter);
    if(str_contains($parameter, "{2}") && isset($dqVars[2])) $parameter = str_replace("{2}", $dqVars[2], $parameter);
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

//FAB
// function ProcessLayer($player, $parameter)
// {
//   switch ($parameter) {
//     case "PHANTASM":
//       PhantasmLayer();
//       break;
//     default: break;
//   }
// }

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
      $ally = new Ally($uniqueID);
      if (SearchCount(GetTargetsForAttack($ally, false)) > 0 && $ally->Exists() && $ally->Controller() == $player) {
        AddDecisionQueue("YESNO", $player, "if_you_want_to_resolve_the_ambush_attack");
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("PASSPARAMETER", $player, 1, 1);
        AddDecisionQueue("SETCOMBATCHAINSTATE", $player, $CCS_IsAmbush, 1);
        AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $ally->Index(), 1);
        AddDecisionQueue("MZOP", $player, "ATTACK", 1);
      }
      break;
    case "SHIELDED":
      $ally = new Ally($uniqueID);
      $ally->Attach("8752877738");//Shield Token
      break;
    case "ALLYPLAYCARDABILITY":
      $data = explode(",",$target); // $cardID, $player, $numUses, $playedCardID
      AllyPlayCardAbility($data[1], $data[0], $uniqueID, $data[2], $data[3], from:$additionalCosts);
      break;
    case "AFTERDESTROYTHEIRSABILITY":
      $data=explode(",",$target);
      for($i=0;$i<count($data);$i+=OtherDestroyedTriggerPieces()) {
        $cardID=$data[$i];
        $triggerPlayer=$data[$i+1];
        $uniqueID=$data[$i+2];
        switch($cardID) {
          case "1664771721"://Gideon Hask
            AddDecisionQueue("SETDQCONTEXT", $triggerPlayer, "Choose a unit to add an experience");
            AddDecisionQueue("MULTIZONEINDICES", $triggerPlayer, "MYALLY");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $triggerPlayer, "<-", 1);
            AddDecisionQueue("MZOP",  $triggerPlayer, "ADDEXPERIENCE", 1);
            break;
          case "b0dbca5c05"://Iden Versio
            Restore(1, $triggerPlayer);
            break;
          case "2649829005"://Agent Kallus
            $allyIndex = SearchAlliesForUniqueID($uniqueID, $triggerPlayer);
            AddDecisionQueue("SETDQCONTEXT", $triggerPlayer, "Choose if you want to draw for Agent Kallus");
            AddDecisionQueue("YESNO", $triggerPlayer, "-");
            AddDecisionQueue("NOPASS", $triggerPlayer, "-");
            AddDecisionQueue("DRAW", $triggerPlayer, "-", 1);
            if($allyIndex != "-1") {
              AddDecisionQueue("PASSPARAMETER", $triggerPlayer, "MYALLY-" . $allyIndex, 1);
              AddDecisionQueue("ADDMZUSES", $triggerPlayer, "-1", 1);
            }
            break;
          case "8687233791"://Punishing One
            $allyIndex = SearchAlliesForUniqueID($uniqueID, $triggerPlayer);
            if($allyIndex != "-1") {
              $ally = new Ally("MYALLY-$allyIndex", $triggerPlayer);
              AddDecisionQueue("YESNO", $triggerPlayer, "if you want to ready " . CardLink("", $ally->CardID()));
              AddDecisionQueue("NOPASS", $triggerPlayer, "-");
              AddDecisionQueue("PASSPARAMETER", $triggerPlayer, "MYALLY-" . $allyIndex, 1);
              AddDecisionQueue("MZOP", $triggerPlayer, "READY", 1);
              AddDecisionQueue("ADDMZUSES", $triggerPlayer, "-1", 1);
            }
            break;
          default: break;
        }
      }
      break;
    case "AFTERDESTROYFRIENDLYABILITY":
      $data = explode(",", $target);
      for($i=0;$i<count($data);$i+=OtherDestroyedTriggerPieces()) {
        $cardID=$data[$i];
        $triggerPlayer=$data[$i+1];
        $uniqueID=$data[$i+2];
        $upgradesWithOwnerData=$data[$i+3];
        switch($data[$i]) {
          case "9353672706"://General Krell
            AddDecisionQueue("SETDQCONTEXT", $triggerPlayer, "Choose if you want to draw for General Krell");
            AddDecisionQueue("YESNO", $triggerPlayer, "-");
            AddDecisionQueue("NOPASS", $triggerPlayer, "-");
            AddDecisionQueue("DRAW", $triggerPlayer, "-", 1);
            break;
          case "2649829005"://Agent Kallus
            $allyIndex = SearchAlliesForUniqueID($uniqueID, $triggerPlayer);
            AddDecisionQueue("SETDQCONTEXT", $triggerPlayer, "Choose if you want to draw for Agent Kallus");
            AddDecisionQueue("YESNO", $triggerPlayer, "-");
            AddDecisionQueue("NOPASS", $triggerPlayer, "-");
            AddDecisionQueue("DRAW", $triggerPlayer, "-", 1);
            if($allyIndex != "-1") {
              AddDecisionQueue("PASSPARAMETER", $triggerPlayer, "MYALLY-" . $allyIndex, 1);
              AddDecisionQueue("ADDMZUSES", $triggerPlayer, "-1", 1);
            }
            break;
          case "3feee05e13"://Gar Saxon Leader Unit
            $upgrades = explode(";",$upgradesWithOwnerData);
            if(count($upgrades) > 0) {
              $upgradesParams = "";
              for ($i = 0; $i < count($upgrades); $i += SubcardPieces()) {
                if(!IsToken($upgrades[$i])) {
                  if($upgradesParams != "") $upgradesParams .= ",";
                  $upgradesParams .= $upgrades[$i] . "-" . $upgrades[$i+1];
                }
              }
              if($upgradesParams == "") break;
              AddDecisionQueue("PASSPARAMETER", $player, $upgradesParams);
              AddDecisionQueue("SETDQCONTEXT", $player, "Choose an upgrade to bounce");
              AddDecisionQueue("MAYCHOOSECARD", $player, "<-", 1);
              AddDecisionQueue("OP", $player, "BOUNCEUPGRADE", 1);
            }
            break;
          case "f05184bd91"://Nala Se Leader Unit
            Restore(2, $player);
            break;
          case "1039828081"://Calculating MagnaGuard
            AddCurrentTurnEffect("1039828081", $player, "PLAY", $uniqueID);
            break;
          default: break;
        }
      }
      break;
    case "AFTERDESTROYABILITY":
      $data=explode("_",$additionalCosts);
      for($i=0;$i<DestroyTriggerPieces();++$i) {
        if(isset($data[$i]) && $data[$i] != "") {
          $arr=explode("=",$data[$i]);
          switch($arr[0]) {
            case "ALLYDESTROY":
              $dd=DeserializeAllyDestroyData($arr[1]);
              AllyDestroyedAbility($player, $target, $dd["UniqueID"], $dd["LostAbilities"],$dd["IsUpgraded"],$dd["Upgrades"],$dd["UpgradesWithOwnerData"],
                $dd["LastPower"],$dd["LastRemainingHP"]);
              CheckThrawnJTL($player, $arr[$i], $target);
              break;
            case "ALLYRESOURCE":
              $rd=DeserializeResourceData($arr[1]);
              AddResources($target, $player, $rd["From"],$rd["Facing"],$rd["Counters"],$rd["IsExhausted"],$rd["StealSource"]);
              break;
            case "ALLYBOUNTIES":
              $bd=DeserializeBountiesData($arr[1]);
              CollectBounties($player,$target,$bd["UniqueID"],$bd["IsExhausted"],$bd["Owner"],$bd["Upgrades"],$bd["ReportMode"],$bd["CapturerUniqueID"]);
              break;
            default:
              break;
          }
        }
      }
      break;
    case "8655450523": //Count Dooku (Fallen Jedi)
      $powers=explode(",", $target);
      for($i=0;$i<count($powers);++$i) {
        $power = $powers[$i];
        AddDecisionQueue("PASSPARAMETER", $player, $power, 1);
        AddDecisionQueue("SPECIFICCARD", $player, "COUNTDOOKU_TWI", 1);
      }
      break;
    case "9642863632": //Bounty Hunter's Quarry
      global $CS_AfterPlayedBy;
      AddDecisionQueue("SEARCHDECKTOPX", $player, $target . ";1;include-definedType-Unit&include-maxCost-3");
      AddDecisionQueue("SETDQVAR", $player, "0", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $player, "9642863632", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "9642863632", 1);
      AddDecisionQueue("SETCLASSSTATE", $player, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
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
      $targetArr = explode("_", $target);
      $target = $targetArr[0];
      $capturerUniqueID = $targetArr[1];
      AddDecisionQueue("YESNO", $player, "if you want to play " . CardLink($target, $target) . " for free off of " . CardLink("7270736993", "7270736993"));
      AddDecisionQueue("NOPASS", $player, "-");

      if ($capturerUniqueID != "-") {
        $ally = new Ally($capturerUniqueID);
        if ($ally != null) {
          AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex(), 1);
          AddDecisionQueue("SETDQVAR", $player, "0", 1);
          AddDecisionQueue("PASSPARAMETER", $player, $target, 1);
          AddDecisionQueue("OP", $player, "DISCARDCAPTIVE", 1);
        }
      }

      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRDISCARD:cardID=" . $target . ";maxCount=1", 1);
      AddDecisionQueue("SETDQVAR", $player, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "7270736993", 1);
      AddDecisionQueue("SETCLASSSTATE", $player, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $player, "7270736993", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      AddDecisionQueue("MZOP", $player, "PLAYCARD", 1);
      break;
    case "1384530409"://Cad Bane Leader ability
      $otherPlayer = ($player == 1 ? 2 : 1);
      if (SearchCount(SearchAllies($otherPlayer)) > 0) {
        AddDecisionQueue("YESNO", $player, "if you want use Cad Bane's ability");
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("EXHAUSTCHARACTER", $player, FindCharacterIndex($player, "1384530409"), 1);
        AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY", 1);
        AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to deal 1 damage to", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $otherPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $otherPlayer, DamageStringBuilder(1, $player), 1);
      }
      break;
    case "9005139831"://Mandalorian Leader Ability
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:maxHealth=4");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "REST", 1);
      AddDecisionQueue("EXHAUSTCHARACTER", $player, FindCharacterIndex($player, "9005139831"), 1);
      break;
    case "2358113881"://Quinlan Vos
      $allies = &GetAllies($player);
      if(count($allies) == 0) break;
      $cost = CardCost($allies[count($allies) - AllyPieces()]);
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:minCost=" . $cost . ";maxCost=" . $cost);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 1 damage", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, DamageStringBuilder(1, $player), 1);
      AddDecisionQueue("EXHAUSTCHARACTER", $player, FindCharacterIndex($player, "2358113881"), 1);
      break;
    case "3045538805"://Hondo Ohnaka Leader
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give an experience token", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
      AddDecisionQueue("EXHAUSTCHARACTER", $player, FindCharacterIndex($player, "3045538805"), 1);
      break;
    case "9334480612"://Boba Fett (Daimyo)
      PrependDecisionQueue("EXHAUSTCHARACTER", $player, FindCharacterIndex($player, "9334480612"), 1);
      PrependDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "9334480612,HAND", 1);
      PrependDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
      PrependDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      PrependDecisionQueue("SETDQCONTEXT", $player, "Choose a card to give +1 power");
      PrependDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
      break;
    case "0754286363"://The Mandalorian's Rifle
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
      AddDecisionQueue("MZFILTER", $player, "leader=1");
      AddDecisionQueue("MZFILTER", $player, "status=0");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to capture");
      AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "CAPTURE," . $uniqueID, 1);
      break;
    //Jump to Lightspeed
    case "9831674351":
      AddDecisionQueue("YESNO", $player, "if you want use Boba Fett's ability");
      AddDecisionQueue("NOPASS", $player, "-");
      AddDecisionQueue("EXHAUSTCHARACTER", $player, FindCharacterIndex($player, "9831674351"), 1);
      AddDecisionQueue("SPECIFICCARD", $player, "BOBA_FETT_LEADER_JTL", 1);
      break;
    case "9611596703"://Allegiant General Pryde
      $targetAlly = new Ally($target);
      DefeatUpgrade($player, may:true, upgradeFilter:"unique=1", mzIndex:$targetAlly->MZIndex());
      break;
    case "6032641503"://L3-37
      AddDecisionQueue("SETDQCONTEXT", $player, "Move L3's brain to a vehicle?");
      AddDecisionQueue("YESNO", $player, "-", 1);
      AddDecisionQueue("SPECIFICCARD", $player, "L337_JTL,$target", 1);
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

function StartRegroupPhase() {
  global $initiativePlayer, $mainPlayer;

  // 5.5.1 A) Start of the regroup phase.
  // Any lasting effects that expire when the regroup phase starts expire now.
  // Any abilities or effects that trigger at the start of the regroup phase trigger now.

  // Reset class states
  ResetClassState(1);
  ResetClassState(2);

  // Trigger abilities
  CharacterStartRegroupPhaseAbilities(1);
  CharacterStartRegroupPhaseAbilities(2);
  AllyStartRegroupPhaseAbilities(1);
  AllyStartRegroupPhaseAbilities(2);
  CurrentEffectStartRegroupPhaseAbilities();
  ProcessDecisionQueue();

  // Draw cards and resource them
  $otherPlayer = $initiativePlayer == 1 ? 2 : 1;
  AddDecisionQueue("DRAW", $initiativePlayer, "0");
  AddDecisionQueue("DRAW", $initiativePlayer, "0");
  AddDecisionQueue("DRAW", $otherPlayer, "0");
  AddDecisionQueue("DRAW", $otherPlayer, "0");
  MZMoveCard($initiativePlayer, "MYHAND", "MYRESOURCES", may:true, context:"Choose a card to resource", silent:true);
  MZMoveCard($otherPlayer, "MYHAND", "MYRESOURCES", may:true, context:"Choose a card to resource", silent:true);
  AddDecisionQueue("AFTERRESOURCE", $initiativePlayer, "HAND", 1);
  AddDecisionQueue("AFTERRESOURCE", $otherPlayer, "HAND", 1);
  ProcessDecisionQueue();

  // End regroup phase
  AddLayer("ENDREGROUPPHASE", $mainPlayer, "-");
  ProcessDecisionQueue();
}

function EndRegroupPhase() {
  global $mainPlayer;

  // Reset characters, allies, and resources
  ResetCharacter(1);
  ResetCharacter(2);
  ResetAllies(1);
  ResetAllies(2);
  ResetResources(1);
  ResetResources(2);

  // Trigger abilities
  CharacterEndRegroupPhaseAbilities(1);
  CharacterEndRegroupPhaseAbilities(2);
  AllyEndRegroupPhaseAbilities(1);
  AllyEndRegroupPhaseAbilities(2);
  CurrentEffectEndRegroupPhaseAbilities();
  ProcessDecisionQueue();

  // End turn procedure
  AddDecisionQueue("ENDTURN", $mainPlayer, "-");
  ProcessDecisionQueue();

  // Start action phase
  AddLayer("STARTACTIONPHASE", $mainPlayer, "-");
  ProcessDecisionQueue();
}

function StartActionPhase() {
  global $mainPlayer, $currentTurnEffects, $nextTurnEffects;

  // Reset class states
  ResetClassState(1);
  ResetClassState(2);

  // Reset turn modifiers
  UnsetTurnModifiers();

  // Trigger abilities
  CharacterStartActionPhaseAbilities(1);
  CharacterStartActionPhaseAbilities(2);
  AllyStartActionPhaseAbilities(1);
  AllyStartActionPhaseAbilities(2);
  CurrentEffectStartActionPhaseAbilities();
  ProcessDecisionQueue();

  // Resume round pass
  AddDecisionQueue("RESUMEROUNDPASS", $mainPlayer, "-");
  ProcessDecisionQueue();
}

function EndActionPhase() {
  global $mainPlayer;

  // 4.1 C) End of the action phase
  // Any lasting effects that expire when the action phase ends expire now (e.g. “for this phase”).
  // Any abilities or effects that trigger at the end of the action phase trigger now.

  // Trigger abilities
  CharacterEndActionPhaseAbilities(1);
  CharacterEndActionPhaseAbilities(2);
  AllyEndActionPhaseAbilities(1);
  AllyEndActionPhaseAbilities(2);
  CurrentEffectEndActionPhaseAbilities();
  ProcessDecisionQueue();

  // Remove phase effects
  AddDecisionQueue("REMOVEPHASEEFFECTS", $mainPlayer, "-");
  ProcessDecisionQueue();

  // Start regroup phase
  AddLayer("STARTREGROUPPHASE", $mainPlayer, "-");
  ProcessDecisionQueue();
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

function NumDiscards($player) {
  $discard = &GetDiscard($player);
  return count($discard)/DiscardPieces();
}

// Returns the MZ index of the last card discarded
function GetLastDiscardedMZ($player) {
  global $mainPlayer;
  $numDiscards = NumDiscards($player);
  $indice = ($numDiscards - 1) * DiscardPieces();
  return $mainPlayer == $player ? "MYDISCARD-" . $indice : "THEIRDISCARD-" . $indice;
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

function TheyControlMoreUnits($player) {
  $otherPlayer = $player == 1 ? 2 : 1;
  if(count(GetAllies($player)) < count(GetAllies($otherPlayer))) return true;
  return false;
}

function IsCoordinateActive($player) {
  return GetAllyCount($player) >= 3;
}

function IsExploitWhenPlayed($cardID) {
  switch($cardID) {
    case "8655450523"://Count Dooku (Fallen Jedi)
      return true;
    default:
      return false;
  }
}

function AdmiralHoldoWereNotAlone($player, $flipped) {
  $otherPlayer = $player == 1 ? 2 : 1;
  $indices = [];
  $myAllies = GetAllies($player);
  $theirAllies = GetAllies($otherPlayer);
  for($i=0; $i<count($myAllies); $i+=AllyPieces()) {
    if(AllyTraitContainsOrUpgradeTraitContains($myAllies[$i+5], "Resistance")) {
      $indices[] = "MYALLY-" . $i;
    }
  }
  for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
    if(AllyTraitContainsOrUpgradeTraitContains($theirAllies[$i+5], "Resistance")) {
      $indices[] = "THEIRALLY-" . $i;
    }
  }
  AddDecisionQueue("PASSPARAMETER", $player, implode(",", $indices));
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give +2/+2");
  if(!$flipped) AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  else AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "ADDHEALTH,2", 1);
  AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
  AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "8943696478,PLAY", 1);
}

function AdmiralAckbarItsATrap($player, $flipped) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
  if(!$flipped) AddDecisionQueue("MZFILTER", $player, "leader=1");
  AddDecisionQueue("MZFILTER", $player, "status=1");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
  if(!$flipped) AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  else AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "REST", 1);
  AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
  AddDecisionQueue("SPECIFICCARD", $player, "ACKBAR_JTL", 1);
}

function AsajjVentressIWorkAlone($player) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY", 1);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a friendly unit to damage", 1);
  AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1,$player", 1);
  AddDecisionQueue("SETDQVAR", $player, "1", 1);
  AddDecisionQueue("MZOP", $player, "GETARENA", 1);
  AddDecisionQueue("SETDQVAR", $player, "2", 1);
  AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:arena={2}", 1);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose an opposing unit to damage", 1);
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1,$player", 1);
}

function KazudaXionoBestPilotInTheGalaxy($player) {
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose units to lose abilities");
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
  AddDecisionQueue("OP", $player, "MZTONORMALINDICES", 1);
  AddDecisionQueue("PREPENDLASTRESULT", $player, SearchCount(SearchAllies($player)) . "-", 1);
  AddDecisionQueue("MULTICHOOSEUNIT", $player, "<-", 1);
  AddDecisionQueue("SPECIFICCARD", $player, "KAZUDA_JTL", 1);
}

function ShuttleST149($player) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:hasUpgradeOnly=token&THEIRALLY:hasUpgradeOnly=token");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to move a token upgrade from.", 1);
  AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("SETDQVAR", $player, "1", 1);
  AddDecisionQueue("MZOP", $player, "GETUPGRADES", 1);
  AddDecisionQueue("FILTER", $player, "LastResult-include-isToken-true", 1);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a token upgrade to move.", 1);
  AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
  AddDecisionQueue("SETDQVAR", $player, "0", 1);
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY", 1);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to move <0> to.", 1);
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "MOVEUPGRADE", 1);
}

function CaptainPhasmaUnit($player, $phasmaIndex) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=First_Order&THEIRALLY:trait=First_Order");
  AddDecisionQueue("MZFILTER", $player, "index=MYALLY-" . $phasmaIndex);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a First Order unit to give +2/+2");
  AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "ADDHEALTH,2", 1);
  AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
  AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "3427170256,PLAY", 1);
}

function CountPilotUnitsAndPilotUpgrades($player, $other=false) {
  $count = $other ? -1 : 0;
  $count += SearchCount(SearchAllies($player, trait:"Pilot"));
  $alliesWithUpgrades = explode(",", SearchAllies($player, hasUpgradeOnly:true));
  if($alliesWithUpgrades[0] == "") return $count;
  for($i=0; $i<count($alliesWithUpgrades); ++$i) {
    $ally = new Ally("MYALLY-" . $alliesWithUpgrades[$i], $player);
    $upgrades = $ally->GetUpgrades();
    for($j=0; $j<count($upgrades); ++$j) {
      if(TraitContains($upgrades[$j], "Pilot", $player)) $count += 1;
    }
  }
  return $count;
}

function CountUniqueAlliesOfTrait($player, $trait) {
  $count = 0;
  $traited = explode(",",SearchAllies($player, trait:$trait));
  if($traited[0] == "") return $count;
  for($i=0;$i<count($traited);++$i) {
    $ally = new Ally("MYALLY-" . $traited[$i], $player);
    if($ally->IsUnique()) $count += 1;
  }

  return $count;
}

function ObiWansAethersprite($player, $index) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:arena=Space&THEIRALLY:arena=Space", 1);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 2 damage to (or pass)", 1);
  AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player", 1);
  AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $index, 1);
  AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1,$player", 1);
}

function UIDIsAffectedByMalevolence($uniqueID) {
  global $currentTurnEffects;

  $found = false;
  for($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnPieces()) {
    $found = $found || ($currentTurnEffects[$i] == "3381931079" && $currentTurnEffects[$i+2] == $uniqueID);
  }

  return $found;
}

function PilotWasPlayed($player, $cardID) {
  global $CS_PlayedAsUpgrade;
  return TraitContains($cardID, "Pilot", $player) && GetClassState($player, $CS_PlayedAsUpgrade) == 1;
}

function TupleFirstUpgradeWithCardID($upgrades, $cardID) {
  for($i=0; $i<count($upgrades); $i+=SubcardPieces()) {
    if($upgrades[$i] == $cardID) {
      return [$upgrades[$i+4] == 1, $upgrades[$i+5]];//tuple [epicAction, turnsInPlay]
    }
  }
}

function CheckBobaFettJTL($targetPlayer, $enemyDamage, $fromCombat) {
  if($fromCombat) {
    return;
  }
  $playerToCheck = $enemyDamage
    ? ($targetPlayer == 1 ? 2 : 1)
    : $targetPlayer;
  $charArr = &GetPlayerCharacter($playerToCheck);
    for($i=0; $i<count($charArr); $i+=CharacterPieces()) {
      switch($charArr[$i]) {
        case "9831674351"://Boba Fett Leader
          if(!LeaderAbilitiesIgnored() && $charArr[$i+1] == 2) {
            if(!SearchCurrentLayers("TRIGGER", $playerToCheck, "9831674351")) {
              AddLayer("TRIGGER", $playerToCheck, "9831674351");
            }
          }
          break;
        default: break;
      }
    }
}

function CheckThrawnJTL($player, $serializedAllyDestroyData, $target) {
  $charArr = &GetPlayerCharacter($player);
  for($i=0; $i<count($charArr); $i+=CharacterPieces()) {
    if($charArr[$i] == "5846322081") {//Grand Admiral Thrawn leader
      if(!LeaderAbilitiesIgnored() && $charArr[$i+1] == 2) {
        AddDecisionQueue("YESNO", $player, "if you want use Thrawn's ability for " . CardLink($target, $target));
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("EXHAUSTCHARACTER", $player, FindCharacterIndex($player, "5846322081"), 1);
        //using semi-colin (;) since comma (,) is used for upgrade data
        AddDecisionQueue("PASSPARAMETER", $player, "$target;0;$serializedAllyDestroyData", 1);
        AddDecisionQueue("SETDQVAR", $player, "1", 1);
        AddDecisionQueue("SPECIFICCARD", $player, "THRAWN_JTL", 1);
      }
    }
  }
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces()) {
    if($allies[$i] == "53207e4131") {//Grand Admiral Thrawn leader unit
      $ally = new Ally("MYALLY-" . $i, $player);
      if(!LeaderAbilitiesIgnored() && $ally->NumUses() > 0) {//doubly check num uses
        AddDecisionQueue("YESNO", $player, "if you want use Thrawn's ability for " . CardLink($target, $target));
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("PASSPARAMETER", $player, "$target;1;$serializedAllyDestroyData", 1);
        AddDecisionQueue("SETDQVAR", $player, "1", 1);
        AddDecisionQueue("SPECIFICCARD", $player, "THRAWN_JTL", 1);
      }
    }
  }
}

function IndirectDamage($player, $amount, $fromUnitEffect=false, $uniqueID="", $alsoExhausts=false)
{
  global $CS_NumIndirectDamageGiven;
  $sourcePlayer = $player == 1 ? 2 : 1;
  $amount += SearchCount(SearchAlliesForCard($sourcePlayer, "4560739921"));//Hunting Aggressor
  if(SearchCount(SearchAlliesForCard($sourcePlayer, "1330473789")) > 0) $sourcePlayerTargets = true;
  IncrementClassState($sourcePlayer, $CS_NumIndirectDamageGiven);
  if(!$sourcePlayerTargets && $uniqueID != "") {
    $sourceIndex = SearchAlliesForUniqueID($uniqueID, $sourcePlayer);
    $ally = new Ally("MYALLY-" . $sourceIndex, $sourcePlayer);
    $upgrades = $ally->GetUpgrades();
    for ($i = 0; $i < count($upgrades); $i += SubcardPieces()) {
      switch($upgrades[$i]) {
        case "7021680131"://Targeting Computer
          $sourcePlayerTargets = true;
          break;
        default: break;
      }
    }
  }
  if($sourcePlayerTargets) { //Devastator
    AddDecisionQueue("FINDINDICES", $sourcePlayer, "THEIRUNITSANDBASE");
    AddDecisionQueue("SETDQCONTEXT", $sourcePlayer, "Choose units and/or base to damage (any remaining will go to base)", 1);
    AddDecisionQueue("MULTICHOOSETHEIRUNITSANDBASE", $sourcePlayer, "<-", 1);
    AddDecisionQueue("MULTIDISTRIBUTEDAMAGE", $sourcePlayer,
      MultiDistributeDamageStringBuilder($amount, $sourcePlayer, $fromUnitEffect ? 1 : 0, isPreventable: 0, alsoExhausts:$alsoExhausts, zones:"THEIRALLIESANDBASE"), 1);
  } else {
    AddDecisionQueue("FINDINDICES", $player, "UNITSANDBASE");
    AddDecisionQueue("SETDQCONTEXT", $player, "Choose units and/or base to damage (any remaining will go to base)", 1);
    AddDecisionQueue("MULTICHOOSEMYUNITSANDBASE", $player, "<-", 1);
    AddDecisionQueue("MULTIDISTRIBUTEDAMAGE", $player,
      MultiDistributeDamageStringBuilder($amount, $sourcePlayer, $fromUnitEffect ? 1 : 0, isPreventable: 0, alsoExhausts:$alsoExhausts, zones:"MYALLIESANDBASE"), 1);
  }
}

function CardCostIsOdd($cardID) {
  return CardCost($cardID) % 2 == 1;
}

function PlayerIsUsingNabatVillage($player) {
  return GetPlayerCharacter($player)[0] == "9586661707";//Nabat Village
}