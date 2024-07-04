<?php

function BanishCardForPlayer($cardID, $player, $from, $modifier = "-", $banishedBy = "")
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myBanish, $theirBanish, $mainBanish, $defBanish;
  global $myClassState, $theirClassState, $mainClassState, $defClassState;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return BanishCard($mainBanish, $mainClassState, $cardID, $modifier, $player, $from, $banishedBy);
    else return BanishCard($defBanish, $defClassState, $cardID, $modifier, $player, $from, $banishedBy);
  } else {
    if ($player == $myStateBuiltFor) return BanishCard($myBanish, $myClassState, $cardID, $modifier, $player, $from, $banishedBy);
    else return BanishCard($theirBanish, $theirClassState, $cardID, $modifier, $player, $from, $banishedBy);
  }
}

function BanishCard(&$banish, &$classState, $cardID, $modifier, $player = "", $from = "", $banishedBy = "")
{
  global $CS_CardsBanished, $actionPoints, $CS_Num6PowBan, $currentPlayer, $mainPlayer;
  $rv = -1;
  if ($player == "") $player = $currentPlayer;
  if(CardType($cardID) != "T") { //If you banish a token, the token ceases to exist.
    $rv = count($banish);
    array_push($banish, $cardID);
    array_push($banish, $modifier);
    array_push($banish, GetUniqueId());
  }
  ++$classState[$CS_CardsBanished];
  return $rv;
}

function RemoveBanish($player, $index)
{
  $banish = &GetBanish($player);
  for ($i = $index + BanishPieces() - 1; $i >= $index; --$i) {
    unset($banish[$i]);
  }
  $banish = array_values($banish);
}

function AddBottomDeck($cardID, $player, $from)
{
  $deck = &GetDeck($player);
  array_push($deck, $cardID);
}

function AddTopDeck($cardID, $player, $from)
{
  $deck = &GetDeck($player);
  array_unshift($deck, $cardID);
}

function AddPlayerHand($cardID, $player, $from)
{
  $hand = &GetHand($player);
  array_push($hand, $cardID);
}

function RemoveHand($player, $index)
{
  $hand = &GetHand($player);
  if(count($hand) == 0) return "";
  $cardID = $hand[$index];
  for($j = $index + HandPieces() - 1; $j >= $index; --$j) unset($hand[$j]);
  $hand = array_values($hand);
  return $cardID;
}

function GainResources($player, $amount)
{
  $resources = &GetResources($player);
  $resources[0] += $amount;
}

function AddResourceCost($player, $amount)
{
  $resources = &GetResources($player);
  $resources[1] += $amount;
}

function RemovePitch($player, $index)
{
  $pitch = &GetPitch($player);
  $cardID = $pitch[$index];
  unset($pitch[$index]);
  $pitch = array_values($pitch);
  return $cardID;
}

function AddCharacter($cardID, $player, $counters=0, $status=2)
{
  $char = &GetPlayerCharacter($player);
  array_push($char, $cardID);
  array_push($char, $status);
  array_push($char, $counters);
  array_push($char, 0);
  array_push($char, 0);
  array_push($char, 1);
  array_push($char, 0);
  array_push($char, 0);
  array_push($char, 0);
  array_push($char, 2);
  array_push($char, 0);
}

function AddMemory($cardID, $player, $from, $facing, $counters=0)
{
  $arsenal = &GetArsenal($player);
  array_push($arsenal, $cardID);
  array_push($arsenal, $facing);
  array_push($arsenal, 1); //Num uses - currently always 1
  array_push($arsenal, $counters); //Counters
  array_push($arsenal, "0"); //Is Frozen (1 = Frozen)
  array_push($arsenal, GetUniqueId()); //Unique ID
}

function AddResources($cardID, $player, $from, $facing, $counters=0, $isExhausted="0")
{
  $arsenal = &GetArsenal($player);
  array_push($arsenal, $cardID);
  array_push($arsenal, $facing);
  array_push($arsenal, 1); //Num uses - currently always 1
  array_push($arsenal, $counters); //Counters
  array_push($arsenal, $isExhausted); //Is Frozen (1 = Frozen)
  array_push($arsenal, GetUniqueId()); //Unique ID
}

function AddArsenal($cardID, $player, $from, $facing, $counters=0)
{
  global $mainPlayer;
  $arsenal = &GetArsenal($player);
  $character = &GetPlayerCharacter($player);
  $cardSubType = CardSubType($cardID);
  WriteLog("Warning: Deprecated function AddArsenal called. Please report a bug.");
  AddMemory($cardID, $player, $from, $facing, $counters);
}

function ArsenalEndTurn($player)
{
  $arsenal = &GetArsenal($player);
  for($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
    $arsenal[$i + 2] = 1;//Num uses - currently always 1
  }
}

function SetArsenalFacing($facing, $player)
{
  $arsenal = &GetArsenal($player);
  for ($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
    if ($facing == "UP" && $arsenal[$i + 1] == "DOWN") {
      $arsenal[$i + 1] = "UP";
      ArsenalTurnFaceUpAbility($arsenal[$i], $player);
      return $arsenal[$i];
    }
  }
  return "";
}

function ArsenalTurnFaceUpAbility($cardID, $player)
{
  switch($cardID)
  {
    default: break;
  }
}

function AddHand($player, $cardID)
{
  $hand = &GetHand($player);
  array_push($hand, $cardID);
  return count($hand) - 1;
}

function RemoveResource($player, $index)
{
  $arsenal = &GetArsenal($player);
  if(count($arsenal) == 0) return "";
  $cardID = $arsenal[$index];
  for($i = $index + ArsenalPieces() - 1; $i >= $index; --$i) {
    unset($arsenal[$i]);
  }
  $arsenal = array_values($arsenal);
  return $cardID;
}

function RemoveArsenal($player, $index)
{
  $arsenal = &GetArsenal($player);
  if(count($arsenal) == 0) return "";
  $cardID = $arsenal[$index];
  for($i = $index + ArsenalPieces() - 1; $i >= $index; --$i) {
    unset($arsenal[$i]);
  }
  $arsenal = array_values($arsenal);
  return $cardID;
}

function DestroyArsenal($player, $index=-1)
{
  $arsenal = &GetArsenal($player);
  $cardIDs = "";
  for($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
    if($index > -1 && $index != $i) continue;
    if($cardIDs != "") $cardIDs .= ",";
    $cardIDs .= $arsenal[$i];
    WriteLog(CardLink($arsenal[$i], $arsenal[$i]) . " was destroyed from the arsenal");
    AddGraveyard($arsenal[$i], $player, "ARS");
    for($j=$i+ArsenalPieces()-1; $j>=$i; --$j) unset($arsenal[$j]);
  }
  $arsenal = array_values($arsenal);
  return $cardIDs;
}

function SetCCAttackModifier($index, $amount)
{
  global $combatChain;
  $combatChain[$index + 5] += $amount;
}

function AddMaterial($cardID, $player, $from)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  $material = &GetMaterial($player);
  array_push($material, $cardID);
}

function RemoveMaterial($player, $index)
{
  $material = &GetMaterial($player);
  $cardID = $material[$index];
  for($i=$index+MaterialPieces()-1; $i>=$index; --$i)
  {
    unset($material[$i]);
  }
  $material = array_values($material);
  return $cardID;
}

function EffectArcaneBonus($cardID)
{
  $idArr = explode("-", $cardID);
  $cardID = $idArr[0];
  $modifier = (count($idArr) > 1 ? $idArr[1] : 0);
  switch($cardID)
  {
    case "ARC115": return 1;
    case "ARC122": return 1;
    case "ARC123": case "ARC124": case "ARC125": return 2;
    case "ARC129": return 3;
    case "ARC130": return 2;
    case "ARC131": return 1;
    case "ARC132": case "ARC133": case "ARC134": return intval($modifier);
    case "CRU161": return 1;
    case "CRU165": case "CRU166": case "CRU167": return 1;
    case "CRU171": case "CRU172": case "CRU173": return 1;
    case "DYN200": return 3;
    case "DYN201": return 2;
    case "DYN202": return 1;
    case "DYN209": case "DYN210": case "DYN211": return 1;
    default: return 0;
  }
}

function AssignArcaneBonus($playerID)
{
  global $currentTurnEffects, $layers;
  $layerIndex = 0;
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces())
  {
    if($currentTurnEffects[$i+1] == $playerID && EffectArcaneBonus($currentTurnEffects[$i]) > 0)
    {
      $skip = intval($currentTurnEffects[$i+2]) != -1;
      switch($currentTurnEffects[$i])
      {
        case "DYN209": if(CardCost($layers[$layerIndex]) > 2) $skip = true; break;
        case "DYN210": if(CardCost($layers[$layerIndex]) > 1) $skip = true; break;
        case "DYN211": if(CardCost($layers[$layerIndex]) > 0) $skip = true; break;
        default: break;
      }
      if(!$skip)
      {
        WriteLog("Arcane bonus from " . CardLink($currentTurnEffects[$i], $currentTurnEffects[$i]) . " associated with " . CardLink($layers[$layerIndex], $layers[$layerIndex]));
        $uniqueID = $layers[$layerIndex+6];
        $currentTurnEffects[$i+2] = $uniqueID;
      }
    }
  }
}

function ClearNextCardArcaneBuffs($player, $playedCard="", $from="")
{
  global $currentTurnEffects;
  $layerIndex = 0;
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces())
  {
    $remove = 0;
    if($currentTurnEffects[$i+1] == $player)
    {
      switch($currentTurnEffects[$i])
      {
        default: break;
      }
    }
    if ($remove == 1) RemoveCurrentTurnEffect($i);
  }
}

function ConsumeArcaneBonus($player)
{
  global $currentTurnEffects, $CS_ResolvingLayerUniqueID;
  $uniqueID = GetClassState($player, $CS_ResolvingLayerUniqueID);
  $totalBonus = 0;
  for ($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces())
  {
    $remove = 0;
    if ($currentTurnEffects[$i + 1] == $player && $currentTurnEffects[$i+2] == $uniqueID)
    {
      $bonus = EffectArcaneBonus($currentTurnEffects[$i]);
      if($bonus > 0)
      {
        $totalBonus += $bonus;
        $remove = 1;
      }
    }
    if ($remove == 1) RemoveCurrentTurnEffect($i);
  }
  return $totalBonus;
}

function ConsumeDamagePrevention($player)
{
  global $CS_NextDamagePrevented;
  $prevention = GetClassState($player, $CS_NextDamagePrevented);
  SetClassState($player, $CS_NextDamagePrevented, 0);
  return $prevention;
}

function IncrementClassState($player, $piece, $amount = 1)
{
  SetClassState($player, $piece, (GetClassState($player, $piece) + $amount));
}

function DecrementClassState($player, $piece, $amount = 1)
{
  SetClassState($player, $piece, (GetClassState($player, $piece) - $amount));
}

function AppendClassState($player, $piece, $value)
{
  $currentState = GetClassState($player, $piece);
  if ($currentState == "-") $currentState = "";
  if ($currentState != "") $currentState .= ",";
  $currentState .= $value;
  SetClassState($player, $piece, $currentState);
}

function SetClassState($player, $piece, $value)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myClassState, $theirClassState, $mainClassState, $defClassState;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) $mainClassState[$piece] = $value;
    else $defClassState[$piece] = $value;
  } else {
    if ($player == $myStateBuiltFor) $myClassState[$piece] = $value;
    else $theirClassState[$piece] = $value;
  }
}

function AddCharacterEffect($player, $index, $effect)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myCharacterEffects, $theirCharacterEffects, $mainCharacterEffects, $defCharacterEffects;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) {
      array_push($mainCharacterEffects, $index);
      array_push($mainCharacterEffects, $effect);
    } else {
      array_push($defCharacterEffects, $index);
      array_push($defCharacterEffects, $effect);
    }
  } else {
    if ($player == $myStateBuiltFor) {
      array_push($myCharacterEffects, $index);
      array_push($myCharacterEffects, $effect);
    } else {
      array_push($theirCharacterEffects, $index);
      array_push($theirCharacterEffects, $effect);
    }
  }
}

function AddGraveyard($cardID, $player, $from, $modifier="-")
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myDiscard, $theirDiscard, $mainDiscard, $defDiscard;
  global $myStateBuiltFor, $CS_CardsEnteredGY;
  IncrementClassState($player, $CS_CardsEnteredGY);
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) AddSpecificGraveyard($cardID, $mainDiscard, $from, $player, $modifier);
    else AddSpecificGraveyard($cardID, $defDiscard, $from, $player, $modifier);
  } else {
    if ($player == $myStateBuiltFor) AddSpecificGraveyard($cardID, $myDiscard, $from, $player, $modifier);
    else AddSpecificGraveyard($cardID, $theirDiscard, $from, $player, $modifier);
  }
}

function RemoveDiscard($player, $index)
{
  return RemoveGraveyard($player, $index);
}

function RemoveGraveyard($player, $index)
{
  if($index == "") return "-";
  $discard = &GetDiscard($player);
  $cardID = $discard[$index];
  for($i=$index; $i<$index+DiscardPieces(); ++$i) { unset($discard[$i]); }
  $discard = array_values($discard);
  return $cardID;
}

function SearchCharacterAddUses($player, $uses, $type = "", $subtype = "")
{
  $character = &GetPlayerCharacter($player);
  for ($i = 0; $i < count($character); $i += CharacterPieces()) {
    if ($character[$i + 1] != 0 && ($type == "" || CardType($character[$i]) == $type) && ($subtype == "" || $subtype == CardSubtype($character[$i]))) {
      $character[$i + 1] = 2;
      $character[$i + 5] += $uses;
    }
  }
}

function SearchCharacterAddEffect($player, $effect, $type = "", $subtype = "")
{
  $character = &GetPlayerCharacter($player);
  for ($i = 0; $i < count($character); $i += CharacterPieces()) {
    if ($character[$i + 1] != 0 && ($type == "" || CardType($character[$i]) == $type) && ($subtype == "" || $subtype == CardSubtype($character[$i]))) {
      AddCharacterEffect($player, $i, $effect);
    }
  }
}

function RemoveCharacterEffects($player, $index, $effect)
{
  $effects = &GetCharacterEffects($player);
  for ($i = count($effects) - CharacterEffectPieces(); $i >= 0; $i -= CharacterEffectPieces()) {
    if ($effects[$i] == $index && $effects[$i + 1] == $effect) {
      unset($effects[$i + 1]);
      unset($effects[$i]);
    }
  }
  $effects = array_values($effects);
  return false;
}

function AddSpecificGraveyard($cardID, &$graveyard, $from, $player, $modifier="-")
{
  if($cardID == "3991112153" && ($from == "HAND" || $from == "DECK")) $modifier = "TT";
  array_push($graveyard, $cardID);
  array_push($graveyard, $modifier);
}

function NegateLayer($MZIndex, $goesWhere = "GY")
{
  global $layers;
  $params = explode("-", $MZIndex);
  $index = $params[1];
  $cardID = $layers[$index];
  $player = $layers[$index + 1];
  for ($i = $index + LayerPieces()-1; $i >= $index; --$i) {
    unset($layers[$i]);
  }
  $layers = array_values($layers);
  switch ($goesWhere) {
    case "GY":
      AddGraveyard($cardID, $player, "LAYER");
      break;
    case "HAND":
      AddPlayerHand($cardID, $player, "LAYER");
      break;
    default:
      break;
  }
}

function AddAdditionalCost($player, $value)
{
  global $CS_AdditionalCosts;
  AppendClassState($player, $CS_AdditionalCosts, $value);
}

function ClearAdditionalCosts($player)
{
  global $CS_AdditionalCosts;
  SetClassState($player, $CS_AdditionalCosts, "-");
}

function FaceDownArsenalBotDeck($player)
{
  if(ArsenalHasFaceDownCard($player)) {
    AddDecisionQueue("FINDINDICES", $player, "ARSENALDOWN");
    AddDecisionQueue("CHOOSEARSENAL", $player, "<-", 1);
    AddDecisionQueue("REMOVEARSENAL", $player, "-", 1);
    AddDecisionQueue("ADDBOTDECK", $player, "-", 1);
  }
}
