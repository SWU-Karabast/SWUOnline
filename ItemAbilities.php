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
    array_push($items, $item);
    array_push($items, $steamCounters);
    array_push($items, ItemEntersPlayState($item));
    array_push($items, ItemUses($item));
    array_push($items, $uniqueID);
    array_push($items, $myHoldState);
    array_push($items, $theirHoldState);
  }
  switch($item) {
    case "pv4n1n3gyg"://Cleric Robes
      if(IsClassBonusActive($player, "CLERIC")) Draw($player);
      break;
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
    case "LROrzTmh55"://Fire Resonance Bauble
    case "2gv7DC0KID"://Grand Crusader's Ring
    case "bHGUNMFLg9"://Wind Resonance Bauble
    case "dSSRtNnPtw"://Water Resonance Bauble
    case "Z9TCpaMJTc"://Bauble of Abundance
    case "yDARN8eV6B"://Tome of Knowledge
    case "UiohpiTtgs"://Chalice of Blood
    case "P7hHZBVScB"://Orb of Glitter
    case "6e7lRnczfL"://Horn of Beastcalling
    case "BY0E8si926"://Orb of Regret
    case "dmfoA7jOjy"://Crystal of Empowerment
    case "IC3OU6vCnF"://Mana Limiter
    case "hLHpI5rHIK"://Bauble of Mending
    case "WAFNy2lY5t"://Melodious Flute
    case "AKA19OwaCh"://Jewel of Englightenment
    case "j5iQQPd2m5"://Crystal of Argus
    case "ybdj1Db9jz"://Seed of Nature
    case "EBWWwvSxr3"://Channeling Stone
    case "kk46Whz7CJ"://Surveillance Stone
    case "1XegCUjBnY"://Life Essence Amulet
    case "OofVX5hX8X"://Poisoned Coating Oil
    case "Tx6iJQNSA6"://Majestic Spirit's Crest
    case "qYH9PJP7uM"://Blinding Orb
    case "iiZtKTulPg"://Eye of Argus
    case "llQe0cg4xJ"://Orb of Choking Fumes
    case "ScGcOmkoQt"://Smoke Bombs
    case "F1t18omUlx"://Beastbond Paws
    case "2bzajcZZRD"://Map of Hidden Passage
    case "usb5FgKvZX"://Sharpening Stone
    case "xjuCkODVRx"://Beastbond Boots
    case "yj2rJBREH8"://Safeguard Amulet
    case "s23UHXgcZq"://Luxera's Map
    case "EQZZsiUDyl"://Storm Tyrant's Eye
    case "1bqry41lw9"://Explosive Rune
    case "fp66pv4n1n"://Rusted Warshield
    case "73fdt8ptrz"://Windwalker Boots
    case "af098kmoi0"://Orb of Hubris
    case "jxhkurfp66"://Charged Manaplate
    case "lq2kkvoqk1"://Necklace of Foresight
    case "ettczb14m4"://Alchemist's Kit
    case "isxy5lh23q"://Flash Grenade
    case "96659ytyj2"://Crimson Protective Trinket
    case "m3pal7cpvn"://Azure Protective Trinket
    case "n0wpbhigka"://Wand of Frost
    case "ojwk0pw0y6"://Crest of the Alliance
    case "porhlq2kkv"://Wayfinder's Map
      DestroyItemForPlayer($currentPlayer, $index, true);
      BanishCardForPlayer($cardID, $currentPlayer, $from, "-", $currentPlayer);
      break;
    case "i0a5uhjxhk"://Blightroot (1)
    case "5joh300z2s"://Mana Root (2)
    case "bd7ozuj68m"://Silvershine (3)
    case "soporhlq2k"://Fraysia (4)
    case "jnltv5klry"://Razorvine (5)
    case "69iq4d5vet"://Springleaf (6)
      DestroyItemForPlayer($currentPlayer, $index);
      break;
    case "0z2snsdwmx"://Scale of Souls
    case "2ha4dk88zq"://Cloak of Stillwater
    case "xy5lh23qu7"://Obelisk of Fabrication
    case "d6soporhlq"://Obelisk of Protection
    case "wk0pw0y6is"://Obelisk of Armaments
    case "j68m69iq4d"://Sentinel Fabricator
    case "8c9htu9agw"://Prototype Staff
    case "h23qu7d6so"://Temporal Spectrometer
    case "pv4n1n3gyg"://Cleric's Robe
    case "u7d6soporh"://Ingredient Pouch
      $items = &GetItems($currentPlayer);
      $items[$index+2] = 1;
      break;
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
    array_push($destItems, $srcItems[$index+$i]);
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

function ItemHitEffects($attackID)
{
  global $mainPlayer;
  $attackSubType = CardSubType($attackID);
  $items = &GetItems($mainPlayer);
  for($i = count($items) - ItemPieces(); $i >= 0; $i -= ItemPieces()) {
    switch($items[$i]) {
      case "DYN094":
        if($attackSubType == "Gun" && ClassContains($attackID, "MECHANOLOGIST", $mainPlayer)) {
          AddLayer("TRIGGER", $mainPlayer, $items[$i], "-", "-", $items[$i+4]);
        }
        break;
      default: break;
    }
  }
}

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
      case "6gvnta6qse"://Fatal Timepiece
        if(GetClassState($mainPlayer, $CS_NumMaterializations) == 0) {
          WriteLog("Fatal Timepiece deals 2 damage to player " . $mainPlayer . " for not having any materializations");
          DealDamageAsync($mainPlayer, 2, "TRIGGER", $mainItems[$i]);
        }
        break;
      case "0z2snsdwmx"://Scale of Souls
        $hand = &GetHand($mainPlayer);
        $memory = &GetMemory($mainPlayer);
        if(count($hand)/HandPieces() == count($memory)/MemoryPieces()) {
          WriteLog("Scale of Souls recovers 2 health for player " . $mainPlayer);
          Recover($mainPlayer, 2);
        }
        break;
      default: break;
    }
  }
  $defItems = &GetItems($defPlayer);
  for($i=0; $i<count($defItems); $i+=ItemPieces()) {
    switch($defItems[$i]) {
      case "6gvnta6qse"://Fatal Timepiece
        if(GetClassState($mainPlayer, $CS_NumMaterializations) == 0) {
          WriteLog("Fatal Timepiece deals 2 damage to player " . $mainPlayer . " for not having any materializations");
          DealDamageAsync($mainPlayer, 2, "TRIGGER", $defItems[$i]);
        }
        break;
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

function ItemDamageTakenAbilities($player, $damage)
{
  $otherPlayer = ($player == 1 ? 2 : 1);
  $items = &GetItems($otherPlayer);
  for($i = count($items) - ItemPieces(); $i >= 0; $i -= ItemPieces()) {
    $remove = false;
    switch($items[$i]) {
      case "EVR193":
        if(IsHeroAttackTarget() && $damage == 2) {
          WriteLog("Talisman of Warfare destroyed both player's arsenal");
          DestroyArsenal(1);
          DestroyArsenal(2);
          $remove = true;
        }
        break;
      default: break;
    }
    if($remove) DestroyItemForPlayer($otherPlayer, $i);
  }
}

function SteamCounterLogic($item, $playerID, $uniqueID)
{
  global $CS_NumBoosted;
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
