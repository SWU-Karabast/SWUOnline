<?php

function SearchDeck($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $otherPlayer = ($player == 1 ? 2 : 1);
  if (SearchAurasForCard("UPR138", $otherPlayer) != "") {
    WriteLog("Deck search prevented by Channel the Bleak Expanse.");
    return "";
  }
  $deck = &GetDeck($player);
  return SearchInner($deck, $player, "DECK", DeckPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchHand($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $hand = &GetHand($player);
  return SearchInner($hand, $player, "HAND", HandPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchCharacter($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $character = &GetPlayerCharacter($player);
  return SearchInner($character, $player, "CHAR", CharacterPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchPitch($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $searchPitch = &GetPitch($player);
  return SearchInner($searchPitch, $player, "PITCH", PitchPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchDiscard($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $discard = &GetDiscard($player);
  return SearchInner($discard, $player, "DISCARD", DiscardPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchBanish($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $banish = &GetBanish($player);
  return SearchInner($banish, $player, "BANISH", BanishPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchCombatChainLink($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  global $combatChain;
  return SearchInner($combatChain, $player, "CC", CombatChainPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchResources($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $arsenal = &GetMemory($player);
  return SearchInner($arsenal, $player, "MEM", MemoryPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchAura($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $auras = &GetAuras($player);
  return SearchInner($auras, $player, "AURAS", AuraPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchItems($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $items = &GetItems($player);
  return SearchInner($items, $player, "ITEMS", ItemPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchAllies($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $allies = &GetAllies($player);
  return SearchInner($allies, $player, "ALLY", AllyPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchPermanents($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $permanents = &GetPermanents($player);
  return SearchInner($permanents, $player, "PERM", PermanentPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchLayer($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  global $layers;
  return SearchInner($layers, $player, "LAYER", LayerPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchLandmarks($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  global $landmarks;
  return SearchInner($landmarks, $player, "LANDMARK", LandmarkPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}

function SearchMaterial($player, $type = "", $definedType = "", $maxCost = -1, $minCost = -1, $aspect = "", $arena = "", $hasBountyOnly = false, $hasUpgradeOnly = false, $trait = -1, $damagedOnly = false, $maxAttack = -1, $maxHealth = -1, $frozenOnly = false, $hasNegCounters = false, $hasEnergyCounters = false, $comboOnly = false, $minAttack = false)
{
  $material = &GetMaterial($player);
  return SearchInner($material, $player, "MATERIAL", MaterialPieces(), $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
}


function SearchInner(&$array, $player, $zone, $count, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack)
{
  $cardList = "";
  for ($i = 0; $i < count($array); $i += $count) {
    if($zone == "CHAR" && $array[$i+1] == 0) continue;
    $cardID = $array[$i];
    if(!isPriorityStep($cardID)) {
      if(($type == "" || CardTypeContains($cardID, $type, $player))
        && ($definedType == "" || DefinedTypesContains($cardID, $definedType))
        && ($maxCost == -1 || CardCost($cardID) <= $maxCost)
        && ($minCost == -1 || CardCost($cardID) >= $minCost)
        && ($aspect == "" || AspectContains($cardID, $aspect, $player))
        && ($arena == "" || ArenaContains($cardID, $arena, $player))
        && ($trait == -1 || TraitContains($cardID, $trait, $player, $i))
        && ($maxAttack == -1 || AttackValue($cardID) <= $maxAttack)
        && ($minAttack == -1 || AttackValue($cardID) >= $minAttack)
      ) {
        if($maxAttack > -1 && $zone == "ALLY") {
          $ally = new Ally("MYALLY-" . $i, $player);
          if($ally->CurrentPower() > $maxAttack) continue;
        }
        if($maxHealth > -1 && $zone == "ALLY") {
          $ally = new Ally("MYALLY-" . $i, $player);
          if($ally->Health() > $maxHealth) continue;
        }
        if($hasBountyOnly && $zone == "ALLY") {
          $ally = new Ally("MYALLY-" . $i, $player);
          if(!$ally->HasBounty()) continue;
        }
        if($hasUpgradeOnly && $zone == "ALLY") {
          $ally = new Ally("MYALLY-" . $i, $player);
          if(!$ally->IsUpgraded()) continue;
        }
        if($damagedOnly && $zone == "ALLY") {
          $ally = new Ally("MYALLY-" . $i, $player);
          if(!$ally->IsDamaged()) continue;
        }
        if($frozenOnly && !IsFrozenMZ($array, $zone, $i)) continue;
        if($hasNegCounters && $array[$i+4] == 0) continue;
        if($hasEnergyCounters && !HasEnergyCounters($array, $i)) continue;
        if($comboOnly && !HasCombo($cardID)) continue;
        if($cardList != "") $cardList = $cardList . ",";
        $cardList = $cardList . $i;
      }
    }
  }
  return $cardList;
}

function isPriorityStep($cardID)
{
  switch ($cardID) {
    case "ENDTURN": case "RESUMETURN": case "PHANTASM": case "FINALIZECHAINLINK": case "DEFENDSTEP": case "ENDSTEP":
      return true;
    default: return false;
  }
}

function SearchHandForCard($player, $card)
{
  $hand = &GetHand($player);
  $indices = "";
  for ($i = 0; $i < count($hand); $i += HandPieces()) {
    if ($hand[$i] == $card) {
      if ($indices != "") $indices .= ",";
      $indices .= $i;
    }
  }
  return $indices;
}

function SearchDeckForCard($player, $card1, $card2 = "", $card3 = "")
{
  $deck = &GetDeck($player);
  $cardList = "";
  for ($i = 0; $i < count($deck); $i += DeckPieces()) {
    $id = $deck[$i];
    if (($id == $card1 || $id == $card2 || $id == $card3) && $id != "") {
      if ($cardList != "") $cardList = $cardList . ",";
      $cardList = $cardList . $i;
    }
  }
  return $cardList;
}

function SearchDeckByName($player, $name)
{
  $deck = &GetDeck($player);
  $cardList = "";
  for ($i = 0; $i < count($deck); $i += DeckPieces()) {
    if (CardName($deck[$i]) == $name) {
      if ($cardList != "") $cardList = $cardList . ",";
      $cardList = $cardList . $i;
    }
  }
  return $cardList;
}

function SearchDiscardByName($player, $name)
{
  $discard = &GetDiscard($player);
  $cardList = "";
  for ($i = 0; $i < count($discard); $i += DiscardPieces()) {
    if (CardName($discard[$i]) == $name) {
      if ($cardList != "") $cardList = $cardList . ",";
      $cardList = $cardList . $i;
    }
  }
  return $cardList;
}

function SearchDiscardForCard($player, $card1, $card2 = "", $card3 = "")
{
  $discard = &GetDiscard($player);
  $cardList = "";
  for ($i = 0; $i < count($discard); $i += DiscardPieces()) {
    $id = $discard[$i];
    if (($id == $card1 || $id == $card2 || $id == $card3) && $id != "") {
      if ($cardList != "") $cardList = $cardList . ",";
      $cardList = $cardList . $i;
    }
  }
  return $cardList;
}

function PlayerHasAlly($player, $cardID)
{
  return SearchAlliesForCard($player, $cardID) != "";
}

function SearchAlliesForCard($player, $card1, $card2 = "", $card3 = "")
{
  $allies = &GetAllies($player);
  $cardList = "";
  for ($i = 0; $i < count($allies); $i += AllyPieces()) {
    $id = $allies[$i];
    if (($id == $card1 || $id == $card2 || $id == $card3) && $id != "") {
      if ($cardList != "") $cardList = $cardList . ",";
      $cardList = $cardList . $i;
    }
  }
  return $cardList;
}

function SearchAlliesForTitle($player, $title)
{
  $allies = &GetAllies($player);
  $cardList = "";
  for ($i = 0; $i < count($allies); $i += AllyPieces()) {
    if (CardTitle($allies[$i]) == $title) {
      if ($cardList != "") $cardList = $cardList . ",";
      $cardList = $cardList . $i;
    }
  }
  return $cardList;
}

function SearchAlliesActive($player, $card1, $card2 = "", $card3 = "")
{
  $allies = &GetAllies($player);
  $cardList = "";
  for ($i = 0; $i < count($allies); $i += AllyPieces()) {
    $id = $allies[$i];
    if (($id == $card1 || $id == $card2 || $id == $card3) && $id != "") {
      if ($cardList != "") $cardList = $cardList . ",";
      $cardList = $cardList . $i;
    }
  }
  return $cardList != "";
}

function SearchPermanentsForCard($player, $card)
{
  $permanents = &GetPermanents($player);
  $indices = "";
  for ($i = 0; $i < count($permanents); $i += PermanentPieces()) {
    if ($permanents[$i] == $card) {
      if ($indices != "") $indices .= ",";
      $indices .= $i;
    }
  }
  return $indices;
}

function SearchEquipNegCounter(&$character)
{
  $equipList = "";
  for ($i = 0; $i < count($character); $i += CharacterPieces()) {
    if (CardType($character[$i]) == "E" && $character[$i + 4] < 0 && $character[$i + 1] != 0) {
      if ($equipList != "") $equipList = $equipList . ",";
      $equipList = $equipList . $i;
    }
  }
  return $equipList;
}

function SearchCharacterActive($player, $cardID, $checkGem=false)
{
  $index = FindCharacterIndex($player, $cardID);
  if ($index == -1) return false;
  return IsCharacterAbilityActive($player, $index, $checkGem);
}

function SearchCharacterForCard($player, $cardID)
{
  $character = &GetPlayerCharacter($player);
  for ($i = 0; $i < count($character); $i += CharacterPieces()) {
    if ($character[$i] == $cardID) return true;
  }
  return false;
}

function SearchCharacterAliveSubtype($player, $subtype)
{
  $character = &GetPlayerCharacter($player);
  for ($i = 0; $i < count($character); $i += CharacterPieces()) {
    if ($character[$i+1] != 0 && CardSubType($character[$i]) == $subtype) return true;
  }
  return false;
}

function FindCharacterIndex($player, $cardID)
{
  $character = &GetPlayerCharacter($player);
  for ($i = 0; $i < count($character); $i += CharacterPieces()) {
    if ($character[$i] == $cardID) return $i;
  }
  return -1;
}

function CombineSearches($search1, $search2)
{
  if ($search2 == "") return $search1;
  else if ($search1 == "") return $search2;
  return $search1 . "," . $search2;
}

function SearchRemoveDuplicates($search)
{
  $indices = explode(",", $search);
  for ($i = count($indices) - 1; $i >= 0; --$i) {
    for ($j = $i - 1; $j >= 0; --$j) {
      if ($indices[$j] == $indices[$i]) unset($indices[$i]);
    }
  }
  return implode(",", array_values($indices));
}

function SearchCount($search)
{
  if ($search == "") return 0;
  return count(explode(",", $search));
}

function SearchMultizoneFormat($search, $zone)
{
  if ($search == "") return "";
  $searchArr = explode(",", $search);
  for ($i = 0; $i < count($searchArr); ++$i) {
    $searchArr[$i] = $zone . "-" . $searchArr[$i];
  }
  return implode(",", $searchArr);
}

function SearchCurrentTurnEffects($cardID, $player, $remove = false)
{
  global $currentTurnEffects;
  for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if ($currentTurnEffects[$i] == $cardID && $currentTurnEffects[$i + 1] == $player) {
      if ($remove) RemoveCurrentTurnEffect($i);
      return true;
    }
  }
  return false;
}

function SearchLimitedCurrentTurnEffects($cardID, $player, $remove = false)
{
  global $currentTurnEffects;
  for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if ($currentTurnEffects[$i] == $cardID && $currentTurnEffects[$i + 1] == $player) {
      $uniqueID = $currentTurnEffects[$i + 2];
      if ($remove) RemoveCurrentTurnEffect($i);
      return $uniqueID;
    }
  }
  return -1;
}

function SearchCurrentTurnEffectsForCycle($card1, $card2, $card3, $player)
{
  global $currentTurnEffects;
  for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if ($currentTurnEffects[$i] == $card1 && $currentTurnEffects[$i + 1] == $player) return true;
    if ($currentTurnEffects[$i] == $card2 && $currentTurnEffects[$i + 1] == $player) return true;
    if ($currentTurnEffects[$i] == $card3 && $currentTurnEffects[$i + 1] == $player) return true;
  }
  return false;
}

function CountCurrentTurnEffects($cardID, $player, $remove = false)
{
  global $currentTurnEffects;
  $count = 0;
  for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if ($currentTurnEffects[$i] == $cardID && $currentTurnEffects[$i + 1] == $player) {
      if ($remove) RemoveCurrentTurnEffect($i);
      ++$count;
    }
  }
  return $count;
}

function SearchPitchHighestAttack(&$pitch)
{
  $highest = 0;
  for ($i = 0; $i < count($pitch); ++$i) {
    $av = AttackValue($pitch[$i]);
    if ($av > $highest) $highest = $av;
  }
  return $highest;
}

function SearchPitchForColor($player, $color)
{
  $count = 0;
  $pitch = &GetPitch($player);
  for ($i = 0; $i < count($pitch); $i += PitchPieces()) {
    if (PitchValue($pitch[$i]) == $color) ++$count;
  }
  return $count;
}

//For e.g. Mutated Mass
function SearchPitchForNumCosts($player)
{
  $count = 0;
  $countArr = [];
  $pitch = &GetPitch($player);
  for ($i = 0; $i < count($pitch); $i += PitchPieces()) {
    $cost = CardCost($pitch[$i]);
    while (count($countArr) <= $cost) array_push($countArr, 0);
    if ($countArr[$cost] == 0) ++$count;
    ++$countArr[$cost];
  }
  return $count;
}

function SearchPitchForCard($playerID, $cardID)
{
  $pitch = GetPitch($playerID);
  for ($i = 0; $i < count($pitch); ++$i) {
    if ($pitch[$i] == $cardID) return $i;
  }
  return -1;
}

function SearchBanishForCard($playerID, $cardID)
{
  $banish = GetBanish($playerID);
  for ($i = 0; $i < count($banish); $i+=BanishPieces()) {
    if ($banish[$i] == $cardID) return $i;
  }
  return -1;
}

function SearchBanishForCardMulti($playerID, $card1, $card2="", $card3="")
{
  $cardList = "";
  $banish = GetBanish($playerID);
  for ($i = 0; $i < count($banish); ++$i) {
    if ($banish[$i] == $card1 || $banish[$i] == $card2 || $banish[$i] == $card3)
    {
      if($cardList != "") $cardList .= ",";
      $cardList .= $i;
    }
  }
  return $cardList;
}

function SearchItemsForCardMulti($playerID, $card1, $card2 = "", $card3 = "")
{
  $cardList = "";
  $items = GetItems($playerID);
  for ($i = 0; $i < count($items); ++$i) {
    if ($items[$i] == $card1 || $items[$i] == $card2 || $items[$i] == $card3) {
      if ($cardList != "") $cardList .= ",";
      $cardList .= $i;
    }
  }
  return $cardList;
}

function SearchHighestAttackDefended()
{
  global $combatChain, $defPlayer;
  $highest = 0;
  for ($i = 0; $i < count($combatChain); $i += CombatChainPieces()) {
    if ($combatChain[$i + 1] == $defPlayer) {
      $av = AttackValue($combatChain[$i]);
      if ($av > $highest) $highest = $av;
    }
  }
  return $highest;
}

function SearchCharacterEffects($player, $index, $effect)
{
  $effects = &GetCharacterEffects($player);
  for ($i = 0; $i < count($effects); $i += CharacterEffectPieces()) {
    if ($effects[$i] == $index && $effects[$i + 1] == $effect) return true;
  }
  return false;
}

function GetArsenalFaceDownIndices($player)
{
  $arsenal = &GetArsenal($player);
  $indices = "";
  for ($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
    if ($arsenal[$i + 1] == "DOWN") {
      if ($indices != "") $indices .= ",";
      $indices .= $i;
    }
  }
  return $indices;
}

function GetArsenalFaceUpIndices($player)
{
  $arsenal = &GetArsenal($player);
  $indices = "";
  for ($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
    if ($arsenal[$i + 1] == "UP") {
      if ($indices != "") $indices .= ",";
      $indices .= $i;
    }
  }
  return $indices;
}

function GetEquipmentIndices($player, $maxBlock = -1, $onCombatChain = false)
{
  $character = &GetPlayerCharacter($player);
  $indices = "";
  for ($i = 0; $i < count($character); $i += CharacterPieces()) {
    if ($character[$i + 1] != 0 && CardType($character[$i]) == "E" && ($maxBlock == -1 || (BlockValue($character[$i]) + $character[$i + 4]) <= $maxBlock) && ($onCombatChain == false || $character[$i + 6] > 0)) {
      if ($indices != "") $indices .= ",";
      $indices .= $i;
    }
  }
  return $indices;
}

function SearchAuras($cardID, $player)
{
  $auras = &GetAuras($player);
  $count = 0;
  for ($i = 0; $i < count($auras); $i += AuraPieces()) {
    if ($auras[$i] == $cardID) return true;
  }
  return false;
}

function SearchAurasForCard($cardID, $player, $justFirst=false)
{
  $auras = &GetAuras($player);
  $indices = "";
  for ($i = 0; $i < count($auras); $i += AuraPieces()) {
    if ($auras[$i] == $cardID) {
      if($justFirst) return $i;
      if ($indices != "") $indices .= ",";
      $indices .= $i;
    }
  }
  return $indices;
}

function SearchZoneForUniqueID($uniqueID, $player, $zone)
{
  switch($zone)
  {
    case "MYALLY": case "THEIRALLY": return SearchAlliesForUniqueID($uniqueID, $player);
    case "MYAURAS": case "THEIRAURAS": return SearchAurasForUniqueID($uniqueID, $player);
    default: return -1;
  }
}

function SearchUniqueMultizone($uniqueID, $player) {
  $index = SearchAlliesForUniqueID($uniqueID, $player);
  if($index >= 0) return "MYALLY-" . $index;
  $otherPlayer = $player == 1 ? 2 : 1;
  $index = SearchAlliesForUniqueID($uniqueID, $otherPlayer);
  if($index >= 0) return "THEIRALLY-" . $index;
  return "";
}

function SearchForUniqueID($uniqueID, $player)
{
  $index = SearchAurasForUniqueID($uniqueID, $player);
  if ($index == -1) $index = SearchItemsForUniqueID($uniqueID, $player);
  if ($index == -1) $index = SearchAlliesForUniqueID($uniqueID, $player);
  if ($index == -1) $index = SearchLayersForUniqueID($uniqueID);
  return $index;
}

function SearchLayersForUniqueID($uniqueID)
{
  global $layers;
  for($i=0; $i<count($layers); $i+=LayerPieces())
  {
    if($layers[$i+6] == $uniqueID) return $i;
  }
  return -1;
}

function SearchAurasForUniqueID($uniqueID, $player)
{
  $auras = &GetAuras($player);
  for ($i = 0; $i < count($auras); $i += AuraPieces()) {
    if ($auras[$i + 6] == $uniqueID) return $i;
  }
  return -1;
}

function SearchItemsForUniqueID($uniqueID, $player)
{
  $items = &GetItems($player);
  for ($i = 0; $i < count($items); $i += ItemPieces()) {
    if ($items[$i + 4] == $uniqueID) return $i;
  }
  return -1;
}

function UnitUniqueIDController($uniqueID) {
  if(SearchAlliesForUniqueID($uniqueID, 1) > -1) return 1;
  if(SearchAlliesForUniqueID($uniqueID, 2) > -1) return 2;
  //TODO: This is an error; handle more gracefully?
  return 1;
}

function SearchAlliesForUniqueID($uniqueID, $player)
{
  $allies = &GetAllies($player);
  for ($i = 0; $i < count($allies); $i += AllyPieces()) {
    if ($allies[$i + 5] == $uniqueID) return $i;
  }
  return -1;
}

function SearchCurrentTurnEffectsForUniqueID($uniqueID)
{
  global $currentTurnEffects;
  for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if ($currentTurnEffects[$i + 2] == $uniqueID) return $i;
  }
  return -1;
}

function SearchUniqueIDForCurrentTurnEffects($index)
{
  global $currentTurnEffects;
  for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if ($currentTurnEffects[$i+2] == $index) return $currentTurnEffects[$i];
  }
  return -1;
}

function SearchItemsForCard($cardID, $player)
{
  $items = &GetItems($player);
  $indices = "";
  for($i = 0; $i < count($items); $i += ItemPieces()) {
    if($items[$i] == $cardID) {
      if($indices != "") $indices .= ",";
      $indices .= $i;
    }
  }
  return $indices;
}

function SearchLandmark($cardID)
{
  global $landmarks;
  for($i = 0; $i < count($landmarks); $i += LandmarkPieces()) {
    if($landmarks[$i] == $cardID) return true;
  }
  return false;
}

function CountAura($cardID, $player)
{
  $auras = &GetAuras($player);
  $count = 0;
  for($i = 0; $i < count($auras); $i += AuraPieces()) {
    if($auras[$i] == $cardID) ++$count;
  }
  return $count;
}

function GetItemIndex($cardID, $player)
{
  $items = &GetItems($player);
  for($i = 0; $i < count($items); $i += ItemPieces()) {
    if($items[$i] == $cardID) return $i;
  }
  return -1;
}

function GetCombatChainIndex($cardID, $player)
{
  global $combatChain;
  for($i=0; $i<count($combatChain); $i+=CombatChainPieces())
  {
    if($combatChain[$i] == $cardID && $combatChain[$i+1] == $player) return $i;
  }
  return -1;
}

function GetAuraIndex($cardID, $player)
{
  $auras = &GetAuras($player);
  for($i = 0; $i < count($auras); $i += AuraPieces()) {
    if($auras[$i] == $cardID) return $i;
  }
  return -1;
}

function GetAllyIndex($cardID, $player)
{
  $Allies = &GetAllies($player);
  for($i = 0; $i < count($Allies); $i += AllyPieces()) {
    if($Allies[$i] == $cardID) return $i;
  }
  return -1;
}

function CountItem($cardID, $player)
{
  $items = &GetItems($player);
  $count = 0;
  for ($i = 0; $i < count($items); $i += ItemPieces()) {
    if ($items[$i] == $cardID) ++$count;
  }
  return $count;
}

function SearchArcaneReplacement($player, $zone)
{
  $cardList = "";
  switch ($zone) {
    case "MYCHAR":
      $array = &GetPlayerCharacter($player);
      $count = CharacterPieces();
      break;
    case "MYITEMS":
      $array = &GetItems($player);
      $count = ItemPieces();
      break;
    case "MYAURAS":
      $array = &GetAuras($player);
      $count = AuraPieces();
      break;
  }
  for ($i = 0; $i < count($array); $i += $count) {
    if ($zone == "MYCHAR" && !IsCharacterAbilityActive($player, $i)) continue;
    $cardID = $array[$i];
    if (SpellVoidAmount($cardID, $player) > 0 && IsCharacterActive($player, $i)) {
      if ($cardList != "") $cardList = $cardList . ",";
      $cardList = $cardList . $i;
    } elseif (SpellVoidAmount($cardID, $player) > 0 && $zone != "MYCHAR") {
      if ($cardList != "") $cardList = $cardList . ",";
      $cardList = $cardList . $i;
    }
  }
  return $cardList;
}

function SearchChainLinks($minPower = -1, $maxPower = -1, $cardType = "")
{
  global $chainLinks;
  $links = "";
  for ($i = 0; $i < count($chainLinks); ++$i) {
    $power = AttackValue($chainLinks[$i][0]);
    $type = CardType($chainLinks[$i][0]);
    if ($chainLinks[$i][2] == "1" && ($minPower == -1 || $power >= $minPower) && ($maxPower == -1 || $power <= $maxPower) && ($cardType == "" || $type == $cardType)) {
      if ($links != "") $links .= ",";
      $links .= $i;
    }
  }
  return $links;
}

function GetMZCardLink($player, $MZ)
{
  $params = explode("-", $MZ);
  $zoneDS = &GetMZZone($player, $params[0]);
  $index = $params[1];
  if($index == "") return "";
  return CardLink($zoneDS[$index], $zoneDS[$index]);
}

//$searches is the following format:
//Each search is delimited by &, which means a set UNION
//Each search is the format <zone>:<condition 1>;<condition 2>,...
//Each condition is format <search parameter name>=<parameter value>
//Example: AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:maxAttack=3;type=AA");
function SearchMultizone($player, $searches)
{
  $unionSearches = explode("&", $searches);
  $rv = "";
  for($i = 0; $i < count($unionSearches); ++$i) {
    $type = "";
    $definedType = "";
    $maxCost = -1;
    $minCost = -1;
    $aspect = "";
    $arena = "";
    $hasBountyOnly = false;
    $hasUpgradeOnly = false;
    $trait = -1;
    $damagedOnly = false;
    $maxAttack = -1;
    $minAttack = -1;
    $maxHealth = -1;
    $frozenOnly = false;
    $hasNegCounters = false;
    $hasEnergyCounters = false;
    $comboOnly = false;
    $searchArr = explode(":", $unionSearches[$i]);
    $zone = $searchArr[0];
    $isCardID = false;
    $isSameName = false;
    if(count($searchArr) > 1) //Means there are conditions
    {
      $conditions = explode(";", $searchArr[1]);
      for ($j = 0; $j < count($conditions); ++$j) {
        $condition = explode("=", $conditions[$j]);
        switch ($condition[0]) {
          case "type":
            $type = $condition[1];
            break;
          case "definedType":
            $definedType = $condition[1];
            break;
          case "maxCost":
            $maxCost = $condition[1];
            break;
          case "minCost":
            $minCost = $condition[1];
            break;
          case "aspect":
            $aspect = $condition[1];
            break;
          case "arena":
            $arena = $condition[1];
            break;
          case "hasBountyOnly":
            $hasBountyOnly = $condition[1];
            break;
          case "hasUpgradeOnly":
            $hasUpgradeOnly = $condition[1];
            break;
          case "trait":
            $trait = $condition[1];
            break;
          case "damagedOnly":
            $damagedOnly = $condition[1];
            break;
          case "maxAttack":
            $maxAttack = $condition[1];
            break;
          case "minAttack":
            $minAttack = $condition[1];
            break;
          case "maxHealth":
            $maxHealth = $condition[1];
            break;
          case "frozenOnly":
            $frozenOnly = $condition[1];
            break;
          case "hasNegCounters":
            $hasNegCounters = $condition[1];
            break;
          case "hasEnergyCounters":
            $hasEnergyCounters = $condition[1];
            break;
          case "comboOnly":
            $comboOnly = $condition[1];
            break;
          case "cardID":
            $cards = explode(",", $condition[1]);
            switch($zone)
            {
              case "MYALLY":
                if(count($cards) == 1) $searchResult = SearchAlliesForCard($player, $cards[0]);
                else if(count($cards) == 2) $searchResult = SearchAlliesForCard($player, $cards[0], $cards[1]);
                else if(count($cards) == 3) $searchResult = SearchAlliesForCard($player, $cards[0], $cards[1], $cards[2]);
                else WriteLog("Ally multizone search only supports 3 cards -- report bug.");
                break;
              case "MYDECK":
                if(count($cards) == 1) $searchResult = SearchDeckForCard($player, $cards[0]);
                else if(count($cards) == 2) $searchResult = SearchDeckForCard($player, $cards[0], $cards[1]);
                else if(count($cards) == 3) $searchResult = SearchDeckForCard($player, $cards[0], $cards[1], $cards[2]);
                else WriteLog("Deck multizone search only supports 3 cards -- report bug.");
                break;
              case "MYDISCARD":
                if(count($cards) == 1) $searchResult = SearchDiscardForCard($player, $cards[0]);
                else if(count($cards) == 2) $searchResult = SearchDiscardForCard($player, $cards[0], $cards[1]);
                else if(count($cards) == 3) $searchResult = SearchDiscardForCard($player, $cards[0], $cards[1], $cards[2]);
                else WriteLog("Discard multizone search only supports 3 cards -- report bug.");
                break;
              case "MYBANISH":
                if(count($cards) == 1) $searchResult = SearchBanishForCardMulti($player, $cards[0]);
                else if(count($cards) == 2) $searchResult = SearchBanishForCardMulti($player, $cards[0], $cards[1]);
                else if(count($cards) == 3) $searchResult = SearchBanishForCardMulti($player, $cards[0], $cards[1], $cards[2]);
                else WriteLog("Banish multizone search only supports 3 cards -- report bug.");
                break;
              case "MYITEMS":
                if (count($cards) == 1) $searchResult = SearchItemsForCardMulti($player, $cards[0]);
                else if (count($cards) == 2) $searchResult = SearchItemsForCardMulti($player, $cards[0], $cards[1]);
                else if (count($cards) == 3) $searchResult = SearchItemsForCardMulti($player, $cards[0], $cards[1], $cards[2]);
                else WriteLog("Item multizone search only supports 3 cards -- report bug.");
                break;
              default: break;
            }
            $searchResult = SearchMultiZoneFormat($searchResult, $zone);
            $rv = CombineSearches($rv, $searchResult);
            $isCardID = true;
            break;
          case "sameName":
            $name = CardName($condition[1]);
            switch($zone)
            {
              case "MYDECK": $searchResult = SearchDeckByName($player, $name); break;
              case "MYDISCARD": $searchResult = SearchDiscardByName($player, $name); break;
              default: break;
            }
            $rv = SearchMultiZoneFormat($searchResult, $zone);
            //$rv = CombineSearches($rv, $searchResult);
            $isSameName = true;
            break;
          default:
            break;
        }
      }
    }
    $searchPlayer = (substr($zone, 0, 2) == "MY" ? $player : ($player == 1 ? 2 : 1));
    $searchResult = "";
    if(!$isCardID && !$isSameName)
    {
      switch ($zone) {
        case "MYDECK": case "THEIRDECK":
          $searchResult = SearchDeck($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "MYHAND": case "THEIRHAND":
          $searchResult = SearchHand($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "MYDISCARD": case "THEIRDISCARD":
          $searchResult = SearchDiscard($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "MYAURAS": case "THEIRAURAS":
          $searchResult = SearchAura($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "MYCHAR": case "THEIRCHAR":
          $searchResult = SearchCharacter($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "MYITEMS": case "THEIRITEMS":
          $searchResult = SearchItems($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "MYALLY": case "THEIRALLY":
          $searchResult = SearchAllies($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "MYPERM": case "THEIRPERM":
          $searchResult = SearchPermanents($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "MYBANISH": case "THEIRBANISH":
          $searchResult = SearchBanish($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "MYPITCH": case "THEIRPITCH":
          $searchResult = SearchPitch($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "MYMATERIAL": case "THEIRMATERIAL":
          $searchResult = SearchMaterial($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "MYRESOURCES": case "THEIRRESOURCES":
          $searchResult = SearchResources($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "COMBATCHAINLINK":
          $searchResult = SearchCombatChainLink($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "LAYER":
          $searchResult = SearchLayer($searchPlayer, $type, $definedType, $maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        case "LANDMARK":
          $searchResult = SearchLandmarks($searchPlayer, $type, $definedType,$maxCost, $minCost, $aspect, $arena, $hasBountyOnly, $hasUpgradeOnly, $trait, $damagedOnly, $maxAttack, $maxHealth, $frozenOnly, $hasNegCounters, $hasEnergyCounters, $comboOnly, $minAttack);
          break;
        default:
          break;
      }
    }
    $searchResult = SearchMultiZoneFormat($searchResult, $zone);
    $rv = CombineSearches($rv, $searchResult);
  }
  return $rv;
}

function ControlsNamedCard($player, $name) {
  $char = &GetPlayerCharacter($player);
  if(count($char) > CharacterPieces() && CardTitle($char[CharacterPieces()]) == $name) return true;
  if(SearchCount(SearchAlliesForTitle($player, $name)) > 0) return true;
  return false;
}

function ReservableIndices($player)
{
  $indices = "";
  $auras = &GetAuras($player);
  for($i = 0; $i < count($auras); $i += AuraPieces())
  {
    if($auras[$i+1] == 2 && HasReservable($auras[$i], $player, $i)) {
      if($indices != "") $indices .= ",";
      $indices .= $i;
    }
  }
  return $indices;
}

function SearchGetLast($search) {
  $indices = explode(",", $search);
  return $indices[count($indices) - 1];
}

function UnitCardSharesName($cardID, $player)
{
  $title = CardTitle($cardID);
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    if($title == CardTitle($allies[$i])) return true;
  }
  return false;
}