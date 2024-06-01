<?php

//Player == currentplayer
function &GetMZZone($player, $zone)
{
  global $layers, $combatChain;
  $rv = "";
  if ($zone == "MYCHAR" || $zone == "THEIRCHAR") $rv = &GetPlayerCharacter($player);
  else if ($zone == "MYAURAS" || $zone == "THEIRAURAS") $rv = &GetAuras($player);
  else if ($zone == "ALLY" || $zone == "MYALLY" || $zone == "THEIRALLY") $rv = &GetAllies($player);
  else if ($zone == "MYARS" || $zone == "THEIRARS") $rv = &GetArsenal($player);
  else if ($zone == "MYHAND" || $zone == "THEIRHAND") $rv = &GetHand($player);
  else if ($zone == "MYPITCH" || $zone == "THEIRPITCH") $rv = &GetPitch($player);
  else if ($zone == "MYDISCARD" || $zone == "THEIRDISCARD") $rv = &GetDiscard($player);
  else if ($zone == "MYITEMS" || $zone == "THEIRITEMS") $rv = &GetItems($player);
  else if ($zone == "PERM" || $zone == "MYPERM" || $zone == "THEIRPERM") $rv = &GetPermanents($player);
  else if ($zone == "BANISH" || $zone == "MYBANISH" || $zone == "THEIRBANISH") $rv = &GetBanish($player);
  else if ($zone == "DECK" || $zone == "MYDECK" || $zone == "THEIRDECK") $rv = &GetDeck($player);
  else if ($zone == "RESOURCES" || $zone == "MYRESOURCES" || $zone == "THEIRRESOURCES") $rv = &GetArsenal($player);
  else if ($zone == "MEMORY" || $zone == "MYMEMORY" || $zone == "THEIRMEMORY") $rv = &GetMemory($player);
  else if ($zone == "LAYER") return $layers;
  else if ($zone == "CC") return $combatChain;
  return $rv;
}

/*
function GetMZPieces($zone)
{
  if($zone == "MYCHAR" || $zone == "THEIRCHAR") return CharacterPieces();
  else if($zone == "MYAURAS" || $zone == "THEIRAURAS") return AuraPieces();
}
*/

function &GetPlayerCharacter($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $mainCharacter, $defCharacter, $myCharacter, $theirCharacter;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainCharacter;
    else return $defCharacter;
  } else {
    if ($player == $myStateBuiltFor) return $myCharacter;
    else return $theirCharacter;
  }
}

function &GetCharacterEffects($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $mainCharacterEffects, $defCharacterEffects, $myCharacterEffects, $theirCharacterEffects;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainCharacterEffects;
    else return $defCharacterEffects;
  } else {
    if ($player == $myStateBuiltFor) return $myCharacterEffects;
    else return $theirCharacterEffects;
  }
}

function &GetPlayerClassState($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myClassState, $theirClassState, $mainClassState, $defClassState;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainClassState;
    else return $defClassState;
  } else {
    if ($player == $myStateBuiltFor) return $myClassState;
    else return $theirClassState;
  }
}

function GetClassState($player, $piece)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myClassState, $theirClassState, $mainClassState, $defClassState;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainClassState[$piece];
    else return $defClassState[$piece];
  } else {
    if ($player == $myStateBuiltFor) return $myClassState[$piece];
    else return $theirClassState[$piece];
  }
}

function &GetDeck($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myDeck, $theirDeck, $mainDeck, $defDeck;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainDeck;
    else return $defDeck;
  } else {
    if ($player == $myStateBuiltFor) return $myDeck;
    else return $theirDeck;
  }
}

function &GetHand($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myHand, $theirHand, $mainHand, $defHand;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainHand;
    else return $defHand;
  } else {
    if ($player == $myStateBuiltFor) return $myHand;
    else return $theirHand;
  }
}

function &GetBanish($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myBanish, $theirBanish, $mainBanish, $defBanish;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainBanish;
    else return $defBanish;
  } else {
    if ($player == $myStateBuiltFor) return $myBanish;
    else return $theirBanish;
  }
}

function &GetPitch($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myPitch, $theirPitch, $mainPitch, $defPitch;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainPitch;
    else return $defPitch;
  } else {
    if ($player == $myStateBuiltFor) return $myPitch;
    else return $theirPitch;
  }
}

function &GetHealth($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myHealth, $theirHealth, $mainHealth, $defHealth;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainHealth;
    else return $defHealth;
  } else {
    if ($player == $myStateBuiltFor) return $myHealth;
    else return $theirHealth;
  }
}

function &GetResources($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myResources, $theirResources, $mainResources, $defResources;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainResources;
    else return $defResources;
  } else {
    if ($player == $myStateBuiltFor) return $myResources;
    else return $theirResources;
  }
}

function &GetItems($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myItems, $theirItems, $mainItems, $defItems;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainItems;
    else return $defItems;
  } else {
    if ($player == $myStateBuiltFor) return $myItems;
    else return $theirItems;
  }
}

function &GetMaterial($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myMaterial, $theirMaterial, $mainMaterial, $defMaterial;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainMaterial;
    else return $defMaterial;
  } else {
    if ($player == $myStateBuiltFor) return $myMaterial;
    else return $theirMaterial;
  }
}

function &GetDiscard($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myDiscard, $theirDiscard, $mainDiscard, $defDiscard;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainDiscard;
    else return $defDiscard;
  } else {
    if ($player == $myStateBuiltFor) return $myDiscard;
    else return $theirDiscard;
  }
}

function &GetResourceCards($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myArsenal, $theirArsenal, $mainArsenal, $defArsenal;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainArsenal;
    else return $defArsenal;
  } else {
    if ($player == $myStateBuiltFor) return $myArsenal;
    else return $theirArsenal;
  }
}

function &GetMemory($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myArsenal, $theirArsenal, $mainArsenal, $defArsenal;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainArsenal;
    else return $defArsenal;
  } else {
    if ($player == $myStateBuiltFor) return $myArsenal;
    else return $theirArsenal;
  }
}

function &GetArsenal($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myArsenal, $theirArsenal, $mainArsenal, $defArsenal;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainArsenal;
    else return $defArsenal;
  } else {
    if ($player == $myStateBuiltFor) return $myArsenal;
    else return $theirArsenal;
  }
}

function &GetAuras($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myAuras, $theirAuras, $mainAuras, $defAuras;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainAuras;
    else return $defAuras;
  } else {
    if ($player == $myStateBuiltFor) return $myAuras;
    else return $theirAuras;
  }
}

function &GetCardStats($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myCardStats, $theirCardStats, $mainCardStats, $defCardStats;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainCardStats;
    else return $defCardStats;
  } else {
    if ($player == $myStateBuiltFor) return $myCardStats;
    else return $theirCardStats;
  }
}

function &GetTurnStats($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myTurnStats, $theirTurnStats, $mainTurnStats, $defTurnStats;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainTurnStats;
    else return $defTurnStats;
  } else {
    if ($player == $myStateBuiltFor) return $myTurnStats;
    else return $theirTurnStats;
  }
}

function &GetAllies($player)
{
  global $p1Allies, $p2Allies;
  if ($player == 1) return $p1Allies;
  else return $p2Allies;
}

function &GetPermanents($player)
{
  global $p1Permanents, $p2Permanents;
  if ($player == 1) return $p1Permanents;
  else return $p2Permanents;
}

function &GetSettings($player)
{
  global $p1Settings, $p2Settings;
  if ($player == 1) return $p1Settings;
  else return $p2Settings;
}

function &GetMainCharacterEffects($player)
{
  global $currentPlayer, $mainPlayer, $mainPlayerGamestateStillBuilt;
  global $myCharacterEffects, $theirCharacterEffects, $mainCharacterEffects, $defCharacterEffects;
  global $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt) {
    if ($player == $mainPlayer) return $mainCharacterEffects;
    else return $defCharacterEffects;
  } else {
    if ($player == $myStateBuiltFor) return $myCharacterEffects;
    else return $theirCharacterEffects;
  }
}

function HeroCard($player) {
  $character = &GetPlayerCharacter($player);
  return count($character) > CharacterPieces() ? $character[CharacterPieces()] : "";
}

function HasTakenDamage($player)
{
  global $CS_DamageTaken;
  return GetClassState($player, $CS_DamageTaken) > 0;
}

function ArsenalFaceDownCard($player)
{
  $arsenal = &GetArsenal($player);
  for ($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
    if ($arsenal[$i + 1] == "DOWN") return $arsenal[$i];
  }
  return "";
}

function ArsenalHasFaceDownCard($player)
{
  $arsenal = &GetArsenal($player);
  for ($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
    if ($arsenal[$i + 1] == "DOWN") return true;
  }
  return false;
}

function ArsenalHasFaceUpCard($player)
{
  $arsenal = &GetArsenal($player);
  for ($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
    if ($arsenal[$i + 1] == "UP") return true;
  }
  return false;
}

function ArsenalHasFaceUpArrowCard($player)
{
  $arsenal = &GetArsenal($player);
  for ($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
    if (CardSubType($arsenal[$i]) == "Arrow" && $arsenal[$i + 1] == "UP") return true;
  }
  return false;
}

function GetPlayerBase($player)
{
  $character = &GetPlayerCharacter($player);
  return $character[0];
}

function ArsenalFull($player)
{
  $arsenal = &GetArsenal($player);
  $fullCount = SearchCharacterActive($player, "ELE213") && ArsenalHasFaceUpCard($player) ? ArsenalPieces() * 2 : ArsenalPieces();
  return count($arsenal) >= $fullCount;
}

function ArsenalEmpty($player)
{
  $arsenal = &GetArsenal($player);
  return count($arsenal) == 0;
}

function ActiveCharacterEffects($player, $index)
{
  $effects = "";
  $characterEffects = GetCharacterEffects($player);
  for ($i = 0; $i < count($characterEffects); $i += CharacterEffectPieces()) {
    if ($characterEffects[$i] == $index) {
      if ($effects != "") $effects .= ", ";
      $effects .= CardName($characterEffects[$i + 1]);
    }
  }
  return $effects;
}
