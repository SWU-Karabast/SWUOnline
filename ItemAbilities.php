<?php

function PutItemIntoPlay($item, $steamCounterModifier = 0)
{
  global $currentPlayer;
  PutItemIntoPlayForPlayer($item, $currentPlayer, $steamCounterModifier);
}

function PutItemIntoPlayForPlayer($item, $player, $steamCounterModifier = 0, $number = 1)
{
  $otherPlayer = ($player == 1 ? 2 : 1);
  if(!CardTypeContains($item, "ITEM")) return;
  $items = &GetItems($player);
  $myHoldState = ItemDefaultHoldTriggerState($item);
  if($myHoldState == 0 && HoldPrioritySetting($player) == 1) $myHoldState = 1;
  $theirHoldState = ItemDefaultHoldTriggerState($item);
  if($theirHoldState == 0 && HoldPrioritySetting($otherPlayer) == 1) $theirHoldState = 1;
  for($i = 0; $i < $number; ++$i) {
    $uniqueID = GetUniqueId();
    $steamCounters = SteamCounterLogic($item, $player, $uniqueID) + $steamCounterModifier;
    array_push($items, $item, $steamCounters, ItemEntersPlayState($item), ItemUses($item), $uniqueID, $myHoldState, $theirHoldState);
  }
  switch($item) {

    default: break;
  }
}

function ItemEntersPlayState($cardID)
{
  switch($cardID)
  {
    case "s23UHXgcZq": return 1;//Luxera's Map
    default: return 2;
  }
}

function ItemUses($cardID)
{
  switch($cardID) {
    default: return 1;
  }
}

function PayItemAbilityAdditionalCosts($cardID, $from)
{
  global $currentPlayer, $CS_PlayIndex, $combatChain;
  $index = GetClassState($currentPlayer, $CS_PlayIndex);
  switch($cardID) {

    default: break;
  }
}

function ItemBeginTurnEffects($player)
{
  $items = &GetItems($player);
  for($i=0; $i<count($items); $i+=ItemPieces())
  {
    if($items[$i+2] == 1) $items[$i+2] = 2;
  }
}

function ItemPlayAbilities($cardID, $from)
{
  global $currentPlayer;
  $items = &GetItems($currentPlayer);
  for($i = count($items) - ItemPieces(); $i >= 0; $i -= ItemPieces()) {
    $remove = false;
    switch($items[$i]) {
      default: break;
    }
    if($remove) DestroyItemForPlayer($currentPlayer, $i);
  }
}

function DestroyItemForPlayer($player, $index, $skipDestroy=false)
{
  $items = &GetItems($player);
  if(!$skipDestroy && CardType($items[$index]) != "T" && GoesWhereAfterResolving($items[$index], "PLAY", $player) == "GY") {
    AddGraveyard($items[$index], $player, "PLAY");
  }
  $cardID = $items[$index];
  for($i = $index + ItemPieces() - 1; $i >= $index; --$i) {
    if($items[$i] == "DYN492c") {
      $indexWeapon = FindCharacterIndex($player, "DYN492a");
      DestroyCharacter($player, $indexWeapon);
      $indexEquipment = FindCharacterIndex($player, "DYN492b");
      DestroyCharacter($player, $indexEquipment);
    }
    unset($items[$i]);
  }
  $items = array_values($items);
  switch($cardID) {
    case "klryvfq3hu"://Deployment Beacon
      if(IsClassBonusActive($player, "GUARDIAN")) PlayAlly("mu6gvnta6q", $player);//Automaton Drone
      break;
    default: break;
  }
  return $cardID;
}

function ItemCostModifiers($cardID)
{
  global $currentPlayer;
  $cost = 0;
  $items = &GetItems($currentPlayer);
  for($i=0; $i<count($items); $i+=ItemPieces()) {
    switch($items[$i]) {
      case "porhlq2kkv"://Wayfinder's Map
        $cardTypes = CardTypes($cardID);
        if(DelimStringContains($cardTypes, "DOMAIN")) $cost -= 1;
        break;
      default: break;
    }
  }
  return $cost;
}

function StealItem($srcPlayer, $index, $destPlayer)
{
  $srcItems = &GetItems($srcPlayer);
  $destItems = &GetItems($destPlayer);
  for($i = 0; $i < ItemPieces(); ++$i) {
    $destItems[] = $srcItems[$index+$i];
    unset($srcItems[$index+$i]);
  }
  $srcItems = array_values($srcItems);
}

function GetItemGemState($player, $cardID)
{
  global $currentPlayer;
  $items = &GetItems($player);
  $offset = ($currentPlayer == $player ? 5 : 6);
  $state = 0;
  for ($i = 0; $i < count($items); $i += ItemPieces()) {
    if ($items[$i] == $cardID && $items[$i + $offset] > $state) $state = $items[$i + $offset];
  }
  return $state;
}

// function ItemHitEffects($attackID)//FAB
// {
//   global $mainPlayer;
//   $attackSubType = CardSubType($attackID);
//   $items = &GetItems($mainPlayer);
//   for($i = count($items) - ItemPieces(); $i >= 0; $i -= ItemPieces()) {
//     switch($items[$i]) {
//       default: break;
//     }
//   }
// }

function ItemTakeDamageAbilities($player, $damage, $type)
{
  $otherPlayer = ($player == 1 ? 2 : 1);
  $items = &GetItems($player);
  $preventable = CanDamageBePrevented($otherPlayer, $damage, $type);
  for($i=count($items) - ItemPieces(); $i >= 0 && $damage > 0; $i -= ItemPieces()) {
    switch($items[$i]) {
      case "CRU104":
        if($damage > $items[$i+1]) { if($preventable) $damage -= $items[$i+1]; $items[$i+1] = 0; }
        else { $items[$i+1] -= $damage; if($preventable) $damage = 0; }
        if($items[$i+1] <= 0) DestroyItemForPlayer($player, $i);
    }
  }
  return $damage;
}

function ItemStartTurnAbilities()
{
  global $mainPlayer;
  $mainItems = &GetItems($mainPlayer);
  for($i=0; $i<count($mainItems); $i+=ItemPieces()) {
    switch($mainItems[$i]) {
      case "P7hHZBVScB"://Orb of Glitter
        PlayerOpt($mainPlayer, 1);
        break;
      case "fzcyfrzrpl"://Heatwave Generator
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
        AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "fzcyfrzrpl-TRUE,HAND", 1);
        break;
      default: break;
    }
  }
}

function ItemBeginRecollectionAbilities() {
  global $mainPlayer, $defPlayer, $CS_NumMaterializations;
  $mainItems = &GetItems($mainPlayer);
  for($i=0; $i<count($mainItems); $i+=ItemPieces()) {
    switch($mainItems[$i]) {
      default: break;
    }
  }
  $defItems = &GetItems($defPlayer);
  for($i=0; $i<count($defItems); $i+=ItemPieces()) {
    switch($defItems[$i]) {
      default: break;
    }
  }
}

function ItemEndTurnAbilities()
{
  global $mainPlayer;
  $items = &GetItems($mainPlayer);
  for($i = count($items) - ItemPieces(); $i >= 0; $i -= ItemPieces()) {
    $remove = false;
    switch($items[$i]) {
      case "73fdt8ptrz"://Windwalker Boots
        $char = &GetPlayerCharacter($mainPlayer);
        if(IsClassBonusActive($mainPlayer, "ASSASSIN") && $char[1] == "2") {
          WriteLog("Windwalker Boots adds a preparation counter for $mainPlayer");
          AddPreparationCounters($mainPlayer, 1);
        }
        break;
      default: break;
    }
    if($remove) DestroyItemForPlayer($mainPlayer, $i);
  }
}

function SteamCounterLogic($item, $playerID, $uniqueID)
{
  $counters = ETASteamCounters($item);
  return $counters;
}

function ItemLevelModifiers($player)
{
  $items = &GetItems($player);
  $modifier = 0;
  for($i=0; $i<count($items); $i+=ItemPieces())
  {
    switch($items[$i])
    {
      case "JPcFmCpdiF": if(SearchCount(SearchAllies($player, "", "BEAST")) + SearchCount(SearchAllies($player, "", "ANIMAL")) > 0) ++$modifier; break;//Beastbond Ears
      case "WAFNy2lY5t": if(SearchCount(SearchAllies($player, "", "BEAST")) + SearchCount(SearchAllies($player, "", "ANIMAL")) > 0) ++$modifier; break;//Melodious Flute
      case "8c9htu9agw": if(IsClassBonusActive($player, "CLERIC") && MemoryCount($player) >= 4) ++$modifier; break;//Prototype Staff
      default: break;
    }
  }
  return $modifier;
}


?>
