<?php

include "Constants.php";
include "GeneratedCode/GeneratedCardDictionaries.php";

/**
 * @param $cardName
 * @return string UUID of the card in question
 */
function CardIdFromName($cardName):string{
  return CardUUIDFromName(trim(strtolower($cardName)) . ";");
}

function CardType($cardID)
{
  if(!$cardID) return "";
  if(CardTypeContains($cardID, "ATTACK")) return "AA";
  else if(CardTypeContains($cardID, "CHAMPION")) return "C";
  else if(CardTypeContains($cardID, "WEAPON")) return "W";
  if($cardID == "DUMMY") return "C";
  return CardSpeed($cardID) == "1" ? "I" : "A";
}

function CardSubType($cardID)
{
  if(!$cardID) return "";
  return CardSubTypes($cardID);
}

function CharacterHealth($cardID)
{
  if($cardID == "DUMMY") return 1000;
  return CardLife($cardID);
}

function CharacterIntellect($cardID)
{
  switch($cardID) {
    default: return 4;
  }
}

function CardSet($cardID)
{
  if(!$cardID) return "";
  return substr($cardID, 0, 3);
}

function CardClass($cardID)
{
  return CardClasses($cardID);
}

function CardTalent($cardID)
{
  $set = substr($cardID, 0, 3);
  if($set == "MON") return MONCardTalent($cardID);
  else if($set == "ELE") return ELECardTalent($cardID);
  else if($set == "UPR") return UPRCardTalent($cardID);
  else if($set == "DYN") return DYNCardTalent($cardID);
  else if($set == "ROG") return ROGUECardTalent($cardID);
  return "NONE";
}

function HasEfficiency($cardID)
{
  global $currentPlayer;
  switch($cardID)
  {
    case "UfQh069mc3": return true;
    case "SSu2eQZFJV": return true;
    case "ZgA7cWNKGy": return true;
    case "WsunZX4IlW": return true;//Ravaging Tempest
    case "uTBsOYf15p": return true;//Purging Flames
    case "IyXuaLKjSA": return IsClassBonusActive($currentPlayer, "MAGE");//Frozen Nova
    case "4NkVdSx9ed": return true;//Careful Study
    case "pn9gQjV3Rb": return true;//Arcane Blast
    case "FhbVHkHQRb": return true;//Disintegrate
    case "4V6qKuM7xs": return true;//Hurricane Sweep
    case "G5E0PIUd0W": return IsClassBonusActive($currentPlayer, "TAMER");//Artificer's Opus
    default: return false;
  }
}

function HasCleave($cardID)
{
  switch($cardID)
  {
    case "4V6qKuM7xs": return true;//Hurricane Sweep
    case "FGvq4eQPbP": return true;//Flame Sweep
    case "GuDKuPKNgh": return true;//Tidal Sweep
    case "G5E0PIUd0W": return true;//Artificer's Opus
    default: return false;
  }
}

function HasVigor($cardID, $player, $index)
{
  $isAlly = IsAlly($cardID);
  if($isAlly && SearchCurrentTurnEffects("rxxwQT054x", $player)) return true;
  switch($cardID)
  {
    case "JEOxGQppTE"://Windrider Vanguard
      return IsClassBonusActive($player, "WARRIOR") || IsClassBonusActive($player, "GUARDIAN");
    case "3TfIePpuZO": return true;//Trained Hawk
    case "7NMFSRR5V3": return IsClassBonusActive($player, "TAMER");
    case "m4o98vn1vo": return IsClassBonusActive($player, "RANGER");//Winbless Arbalest
    case "mnu1xhs5jw"://Awakened Frostguard
      $allies = &GetAllies($player);
      return $allies[$index+10] == 2;
    default: return false;
  }
}

function HasTrueSight($cardID, $player, $index)
{
  global $currentTurnEffects;
  $allies = &GetAllies($player);
  $uniqueID = $allies[$index+5];
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces())
  {
    if($currentTurnEffects[$i+2] != $uniqueID) continue;
    switch($currentTurnEffects[$i])
    {
      case "i1f0ht2tsn-TRUE": return true;
      default: break;
    }
  }
  switch($cardID)
  {
    case "3TfIePpuZO": return true;//Trained Hawk
    case "LNSRQ5xW6E": return true;//Stillwater Patrol
    case "Dz8I0eJzaf": return IsClassBonusActive($player, "WARRIOR");//Sword of Seeking
    case "du50pcescf": return CharacterLevel($player) >= 2;//Gawain, Chivalrous Thief
    default: return false;
  }
}

function HasReservable($cardID, $player, $index)
{
  switch($cardID)
  {
    case "1d47o7eanl": return true;//Explosive Fractal
    case "1lw9n0wpbh": return true;//Protective Fractal
    case "cfpwakb1k0": return true;//Fractal of Intrusion
    case "hjdu50pces": return true;//Deep Sea Fractal
    case "igka5av43e": return true;//Incendiary Fractal
    case "rp5k1vt1cn": return true;//Fractal of Insight
    default: return false;
  }
}


//Critical only applies to combat damage, so you can assume player/attacker
function CriticalAmount($cardID)
{
  global $mainPlayer;
  switch($cardID)
  {
    case "kT8CeTFj82": return IsClassBonusActive($mainPlayer, "ASSASSIN") ? 1 : 0;//Bushwhack Bandit
    case "5qWWpkgQLl": return SearchCurrentTurnEffects("5qWWpkgQLl", $mainPlayer) ? 4 : 0;//Coup de Grace
    case "2Ch1Gp3jEL": return SearchCurrentTurnEffects("2Ch1Gp3jEL", $mainPlayer) ? 1 : 0;//Corhazi Lightblade
    default: return 0;
  }
}

function HasStealth($cardID, $player, $index)
{
  $allies = &GetAllies($player);
  if(CurrentEffectGrantsStealth($player, $allies[$index+5])) return true;
  switch($cardID)
  {
    case "aKgdkLSBza": return IsClassBonusActive($player, "TAMER");//Wilderness Harpist
    case "CvvgJR4fNa": return $allies[$index+1] == 2 && IsClassBonusActive($player, "ASSASSIN");//Patient Rogue
    case "hHVf5xyjob": return GetClassState($player, $CS_PreparationCounters) >= 3;//Blackmarket Broker
    case "zPC4Yqo9Fs": return true;//Kingdom Informant
    case "YqQsXwEvv5": return true;//Corhazi Courier
    case "UVAb8CmjtL": return true;//Dream Fairy
    case "VAFTR5taNG": return true;//Corhazi Infiltrator
    case "4s0c9XgLg7": return true;//Snow Fairy
    case "ZfCtSldRIy": return true;//Windrider Mage
    case "FWnxKjSeB1": return true;//Spark Fairy
    case "wklzjmwuir": return true;//Shimmercloak Assassin
    case "oy34bro89w": return true;//Cunning Broker
    default: return false;
  }
}

function HasSteadfast($cardID, $player, $index)
{
  switch($cardID)
  {
    case "8lrj52215u": return true;//Vaporjet Shieldbearer
    case "23yfzk96yd": return IsClassBonusActive($player, "GUARDIAN");//Veteran Blazebearer
    default: return false;
  }
}

function HasTaunt($cardID, $player, $index)
{
  if(CardTypeContains($cardID, "CHAMPION")) {
    if(SearchCurrentTurnEffects("098kmoi0a5", $player)) return true;//Take Point
  }
  switch($cardID)
  {
    case "23yfzk96yd": return SearchCurrentTurnEffects($cardID, $player);//Veteran Blazebearer
    case "eifnz0fgm3": return true;//Stalwart Shieldmate
    case "pufyoz23yf": return IsClassBonusActive($player, "GUARDIAN");//Waverider Protector
    case "y5ttkk39i1": return true;//Winbless Gatekeeper
    default: return false;
  }
}

function HasIntercept($cardID, $player, $index)
{
  switch($cardID)
  {
    case "c9p4lpnvx7": return SearchCount(SearchAuras($player, type:"PHANTASIA"));//Awakened Deacon
    case "x7u6wzh973": return true;//Frostbinder Apostle
    case "urfp66pv4n": return true;//Caretaker Drone
    default: return false;
  }
}

function MemoryCost($cardID, $player)
{
  $cost = CardMemoryCost($cardID);
  switch($cardID)
  {
    case "s23UHXgcZq": if(IsClassBonusActive($player, "ASSASSIN")) --$cost; break;//Luxera's Map
    default: break;
  }
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "kk39i1f0ht": if(CardType($cardID) == "C") --$cost; break;//Academy Guide
      default: break;
    }
  }
  return $cost;
}

function PrepareAmount($cardID)
{
  switch($cardID)
  {
    case "5X5W2Uda5a": return 1;//Planted Explosives
    case "GRkBQ1Uvir": return 1;//Ignited Stab
    case "mj3WSrghUH": return 1;//Poised Strike
    case "XLbCBxla8K": return 1;//Thousand Refractions
    case "uoQGe5xGDQ": return 1;//Arrow Trap
    case "5qWWpkgQLl": return 4;//Coup de Grace
    case "RRx0KK6g6D": return 2;//Fishing Accident
    case "7t9m4muq2r": return 1;//Thieving Cut
    default: return 0;
  }
}

//Minimum cost of the card
function CardCost($cardID)
{
  return CardTypeContains($cardID, "REGALIA") ? CardMemoryCost($cardID) : CardReserveCost($cardID);
}

function AbilityCost($cardID)
{
  global $currentPlayer;
  switch($cardID) {
    case "8kmoi0a5uh"://Bulwark Sword
      return 2;
    case "0z2snsdwmx"://Scale of Souls
      return 2;
    case "5swaf8urrq"://Whirlwind Vizier
      $abilityType = GetResolvedAbilityType($cardID);
      if($abilityType == "A") return 3;
      break;
    case "xy5lh23qu7"://Obelisk of Fabrication
      $cost = 6 - SearchCount(SearchAura($currentPlayer, "DOMAIN"));
      if($cost < 0) $cost = 0;
      return $cost;
    case "d6soporhlq"://Obelisk of Protection
      $cost = 4 - SearchCount(SearchAura($currentPlayer, "DOMAIN"));
      if($cost < 0) $cost = 0;
      return $cost;
    case "wk0pw0y6is"://Obelisk of Armaments
      $cost = 5 - SearchCount(SearchAura($currentPlayer, "DOMAIN"));
      if($cost < 0) $cost = 0;
      return $cost;
    case "j68m69iq4d"://Sentinel Fabricator
      return 3;
    case "pv4n1n3gyg": return 1;//Cleric's Robe
    case "u7d6soporh": return 1;//Ingredient Pouch
    default: break;
  }
  if(CardTypeContains($cardID, "ALLY", $currentPlayer)) return 0;
  return 0;
}

function DynamicCost($cardID)
{
  global $currentPlayer;
  switch($cardID) {
    case "P9Y1Q5cQ0F":
      return "0,2";
    default: return "";
  }
}

function PitchValue($cardID)
{
  if(!$cardID) return "";
  $set = CardSet($cardID);
  if($set != "ROG" && $set != "DUM") {
    $number = intval(substr($cardID, 3));
    if($number < 400) return GeneratedPitchValue($cardID);
  }
  if($set == "ROG") return ROGUEPitchValue($cardID);
}

function BlockValue($cardID)
{
  global $defPlayer;
  if(!$cardID) return "";
  $set = CardSet($cardID);
  if($cardID == "MON191") return SearchPitchForNumCosts($defPlayer) * 2;
  else if($cardID == "EVR138") return FractalReplicationStats("Block");
  if($set != "ROG" && $set != "DUM") {
    $number = intval(substr($cardID, 3));
    if($number < 400) return GeneratedBlockValue($cardID);
  }
  $class = CardClass($cardID);
  if($set == "ROG") return ROGUEBlockValue($cardID);
  switch($cardID) {
    case "MON400": case "MON401": case "MON402": return 0;
    case "DYN492a": return -1;
    case "DYN492b": return 5;
    case "DUMMYDISHONORED": return -1;
    default: return 3;
  }
}

function AttackValue($cardID)
{
  global $combatChainState, $CCS_NumBoosted, $mainPlayer, $currentPlayer;
  if(!$cardID) return "";
  return CardPower($cardID);
}

function HasGoAgain($cardID)
{
  return true;
}

function GetAbilityType($cardID, $index = -1, $from="-")
{
  global $currentPlayer;
  if(CardTypeContains($cardID, "ALLY", $currentPlayer) || CardTypeContains($cardID, "WEAPON", $currentPlayer)) return "AA";
  switch($cardID)
  {
    case "s23UHXgcZq": return "A";//Luxera's Map
    case "ENLIGHTEN": return "I";//Enlighten Counters
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
    case "h23qu7d6so"://Temporal Spectrometer
    case "m3pal7cpvn"://Azure Protective Trinket
    case "n0wpbhigka"://Wand of Frost
    case "ojwk0pw0y6"://Crest of the Alliance
    case "porhlq2kkv"://Wayfinder's Map
      return "I";
    case "i0a5uhjxhk"://Blightroot (1)
    case "5joh300z2s"://Mana Root (2)
    case "bd7ozuj68m"://Silvershine (3)
    case "soporhlq2k"://Fraysia (4)
    case "jnltv5klry"://Razorvine (5)
    case "69iq4d5vet"://Springleaf (6)
      return "I";
    case "1lw9n0wpbh"://Protective Fractal
    case "xy5lh23qu7"://Obelisk of Fabrication
    case "d6soporhlq"://Obelisk of Protection
    case "wk0pw0y6is"://Obelisk of Armaments
    case "j68m69iq4d"://Sentinel Fabricator
    case "8c9htu9agw"://Prototype Staff
    case "n1voy5ttkk"://Shatterfall Keep
    case "pv4n1n3gyg"://Cleric's Robe
      return "I";
    case "0z2snsdwmx"://Scale of Souls
    case "2ha4dk88zq"://Cloak of Stillwater
      return "I";
    case "u7d6soporh"://Ingredient Pouch
      return IsClassBonusActive($currentPlayer, "CLERIC") ? "I" : "";
    default: return "";
  }
}

function GetAbilityTypes($cardID)
{
  switch($cardID) {
    case "7dedg616r0"://Freydis, Master Tactician
      return "A,AA";
    case "5swaf8urrq"://Whirlwind Vizier
      return "A,AA";
    case "nl1gxrpx8j"://Perse, Relentless Raptor
      return "I,AA";
    case "oy34bro89w"://Cunning Broker
      return "I,AA";
    default: return "";
  }
}

function GetAbilityNames($cardID, $index = -1)
{
  global $currentPlayer;
  switch ($cardID) {
    case "7dedg616r0"://Freydis, Master Tactician
      return "Remove Counters,Attack";
    case "5swaf8urrq"://Whirlwind Vizier
      return "Sacrifice,Attack";
    case "nl1gxrpx8j"://Perse, Relentless Raptor
      return "Suppress,Attack";
    case "oy34bro89w"://Cunning Broker
      return "Broker,Attack";
    default: return "";
  }
}

function GetAbilityIndex($cardID, $index, $abilityName)
{
  $names = explode(",", GetAbilityNames($cardID, $index));
  for($i = 0; $i < count($names); ++$i) {
    if($abilityName == $names[$i]) return $i;
  }
  return 0;
}

function GetResolvedAbilityType($cardID, $from="-")
{
  global $currentPlayer, $CS_AbilityIndex;
  if($from == "HAND") return "";
  $abilityIndex = GetClassState($currentPlayer, $CS_AbilityIndex);
  $abilityTypes = GetAbilityTypes($cardID);
  if($abilityTypes == "" || $abilityIndex == "-") return GetAbilityType($cardID, -1, $from);
  $abilityTypes = explode(",", $abilityTypes);
  return $abilityTypes[$abilityIndex];
}

function GetResolvedAbilityName($cardID, $from="-")
{
  global $currentPlayer, $CS_AbilityIndex;
  $abilityIndex = GetClassState($currentPlayer, $CS_AbilityIndex);
  $abilityNames = GetAbilityNames($cardID);
  if($abilityNames == "" || $abilityIndex == "-") return "";
  $abilityNames = explode(",", $abilityNames);
  return $abilityNames[$abilityIndex];
}

function IsPlayable($cardID, $phase, $from, $index = -1, &$restriction = null, $player = "")
{
  global $currentPlayer, $CS_NumActionsPlayed, $combatChainState, $CCS_BaseAttackDefenseMax, $CS_NumNonAttackCards, $CS_NumAttackCards;
  global $CCS_ResourceCostDefenseMin, $CCS_CardTypeDefenseRequirement, $actionPoints, $mainPlayer, $defPlayer;
  global $combatChain;
  if($from == "ARS" || $from == "BANISH") return false;
  if($player == "") $player = $currentPlayer;
  if($from == "PLAY")
  {
    $pride = AllyPride($cardID);
    if($pride >= 0 && CharacterLevel($player) < $pride) return false;
    if(CardTypeContains($cardID, "ITEM"))
    {
      $items = &GetItems($player);
      if($items[$index+2] < 2) return false;
    }
  }
  if($phase == "P" && $from == "HAND") return true;
  if(IsPlayRestricted($cardID, $restriction, $from, $index, $player)) return false;
  $cardType = CardType($cardID);
  if($cardType == "W" || $cardType == "AA")
  {
    $char = &GetPlayerCharacter($player);
    if($char[1] != 2) return false;//Can't attack if rested
  }
  if($phase == "M" && $from == "HAND") return true;
  $isStaticType = IsStaticType($cardType, $from, $cardID);
  if($isStaticType) $cardType = GetAbilityType($cardID, $index, $from);
  if($phase == "M" && ($cardType == "A" || $cardType == "AA" || $cardType == "I")) return true;
  if($cardType == "I" && ($phase == "INSTANT" || $phase == "A" || $phase == "D")) return true;
  return false;

}

//Preserve
function GoesWhereAfterResolving($cardID, $from = null, $player = "", $playedFrom="", $resourcesPaid="")
{
  global $currentPlayer, $mainPlayer;
  if($player == "") $player = $currentPlayer;
  $otherPlayer = $player == 2 ? 1 : 2;
  if(CardTypeContains($cardID, "ALLY", $currentPlayer)) return "ALLY";
  switch($cardID) {
    case "2Ojrn7buPe": return "MATERIAL";//Tera Sight
    case "PLljzdiMmq": return "MATERIAL";//Invoke Dominance
    case "cVRIUJdTW5": return "MATERIAL";//Meadowbloom Dryad
    case "P9Y1Q5cQ0F": return $resourcesPaid == "2" ? "BANISH" : "GY"; //Crux Sight
    default: return "GY";
  }
}

function CanPlayInstant($phase)
{
  if($phase == "M") return true;
  if($phase == "A") return true;
  if($phase == "D") return true;
  if($phase == "INSTANT") return true;
  return false;
}

function IsPitchRestricted($cardID, &$restriction, $from = "", $index = -1)
{
  global $playerID;
  return false;
}

function IsPlayRestricted($cardID, &$restriction, $from = "", $index = -1, $player = "")
{
  global $currentPlayer, $mainPlayer;
  if($player == "") $player = $currentPlayer;
  $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
  switch($cardID) {
    case "ENLIGHTEN": return CountAura("ENLIGHTEN", $player) < 3;
    default: return false;
  }
}

function IsDefenseReactionPlayable($cardID, $from)
{
  global $combatChain, $mainPlayer;
  if(CurrentEffectPreventsDefenseReaction($from)) return false;
  return true;
}

function IsAction($cardID)
{
  $cardType = CardType($cardID);
  if($cardType == "A" || $cardType == "AA") return true;
  $abilityType = GetAbilityType($cardID);
  if($abilityType == "A" || $abilityType == "AA") return true;
  return false;
}

function GoesOnCombatChain($phase, $cardID, $from)
{
  global $layers;
  if($phase != "B" && $from == "EQUIP" || $from == "PLAY") $cardType = GetResolvedAbilityType($cardID, $from);
  else if($phase == "M" && $cardID == "MON192" && $from == "BANISH") $cardType = GetResolvedAbilityType($cardID, $from);
  else $cardType = CardType($cardID);
  if($cardType == "I") return false; //Instants as yet never go on the combat chain
  if($phase == "B" && count($layers) == 0) return true; //Anything you play during these combat phases would go on the chain
  if(($phase == "A" || $phase == "D") && $cardType == "A") return false; //Non-attacks played as instants never go on combat chain
  if($cardType == "AR") return true;
  if($cardType == "DR") return true;
  if(($phase == "M" || $phase == "ATTACKWITHIT") && $cardType == "AA") return true; //If it's an attack action, it goes on the chain
  return false;
}

function IsStaticType($cardType, $from = "", $cardID = "")
{
  if($cardType == "C" || $cardType == "E" || $cardType == "W") return true;
  if($from == "PLAY") return true;
  if($cardID != "" && $from == "BANISH" && AbilityPlayableFromBanish($cardID)) return true;
  return false;
}

function HasBladeBreak($cardID)
{
  global $defPlayer;
  switch($cardID) {

    default: return false;
  }
}

function HasBattleworn($cardID)
{
  switch($cardID) {

    default: return false;
  }
}

function HasTemper($cardID)
{
  switch($cardID) {

    default: return false;
  }
}

function RequiresDiscard($cardID)
{
  switch($cardID) {

    default: return false;
  }
}

function ETASteamCounters($cardID)
{
  switch ($cardID) {

    default: return 0;
  }
}

function AbilityHasGoAgain($cardID)
{
  return true;
}

function DoesEffectGrantDominate($cardID)
{
  global $combatChainState;
  switch ($cardID) {

    default: return false;
  }
}

function CharacterNumUsesPerTurn($cardID)
{
  switch ($cardID) {

    default: return 1;
  }
}

//Active (2 = Always Active, 1 = Yes, 0 = No)
function CharacterDefaultActiveState($cardID)
{
  switch ($cardID) {

    default: return 2;
  }
}

//Hold priority for triggers (2 = Always hold, 1 = Hold, 0 = Don't Hold)
function AuraDefaultHoldTriggerState($cardID)
{
  switch ($cardID) {

    default: return 2;
  }
}

function ItemDefaultHoldTriggerState($cardID)
{
  switch($cardID) {

    default: return 2;
  }
}

function IsCharacterActive($player, $index)
{
  $character = &GetPlayerCharacter($player);
  return $character[$index + 9] == "1";
}

function HasReprise($cardID)
{
  switch($cardID) {

    default: return false;
  }
}

//Is it active AS OF THIS MOMENT?
function RepriseActive()
{
  global $currentPlayer, $mainPlayer;
  return 0;
}

function HasCombo($cardID)
{
  switch ($cardID) {
    case "WTR081": case "WTR083": case "WTR084": case "WTR085": case "WTR086": case "WTR087":
    case "WTR088": case "WTR089": case "WTR090": case "WTR091": case "WTR095": case "WTR096":
    case "WTR097": case "WTR104": case "WTR105": case "WTR106": case "WTR110": case "WTR111": case "WTR112":
      return true;
    case "CRU054": case "CRU055": case "CRU056": case "CRU057": case "CRU058": case "CRU059":
    case "CRU060": case "CRU061": case "CRU062":
      return true;
    case "EVR038": case "EVR040": case "EVR041": case "EVR042": case "EVR043":
      return true;
    case "DYN047":
    case "DYN056": case "DYN057": case "DYN058":
    case "DYN059": case "DYN060": case "DYN061":
      return true;
    case "OUT050":
    case "OUT051":
    case "OUT056": case "OUT057": case "OUT058":
    case "OUT059": case "OUT060": case "OUT061":
    case "OUT062": case "OUT063": case "OUT064":
    case "OUT065": case "OUT066": case "OUT067":
    case "OUT074": case "OUT075": case "OUT076":
    case "OUT080": case "OUT081": case "OUT082":
      return true;
  }
  return false;
}

function ComboActive($cardID = "")
{
  global $combatChainState, $combatChain, $chainLinkSummary, $mainPlayer;
  if(SearchCurrentTurnEffects("OUT183", $mainPlayer)) return false;
  if ($cardID == "" && count($combatChain) > 0) $cardID = $combatChain[0];
  if ($cardID == "") return false;
  if(count($chainLinkSummary) == 0) return false;//No combat active if no previous chain links
  $lastAttackNames = explode(",", $chainLinkSummary[count($chainLinkSummary)-ChainLinkSummaryPieces()+4]);
  for($i=0; $i<count($lastAttackNames); ++$i)
  {
    $lastAttackName = GamestateUnsanitize($lastAttackNames[$i]);
    switch ($cardID) {
      case "WTR081":
        if($lastAttackName == "Mugenshi: RELEASE") return true;
        break;
      case "WTR083":
        if($lastAttackName == "Whelming Gustwave") return true;
        break;
      case "WTR084":
        if($lastAttackName == "Rising Knee Thrust") return true;
        break;
      case "WTR085":
        if($lastAttackName == "Open the Center") return true;
        break;
      case "WTR086": case "WTR087": case "WTR088":
        if($lastAttackName == "Open the Center") return true;
        break;
      case "WTR089": case "WTR090": case "WTR091":
        if($lastAttackName == "Rising Knee Thrust") return true;
        break;
      case "WTR095": case "WTR096": case "WTR097":
        if($lastAttackName == "Head Jab") return true;
        break;
      case "WTR104": case "WTR105": case "WTR106":
        if($lastAttackName == "Leg Tap") return true;
        break;
      case "WTR110": case "WTR111": case "WTR112":
        if($lastAttackName == "Surging Strike") return true;
        break;
      case "CRU054":
        if($lastAttackName == "Crane Dance") return true;
        break;
      case "CRU055":
        if($lastAttackName == "Rushing River" || $lastAttackName == "Flood of Force") return true;
        break;
      case "CRU056":
        if($lastAttackName == "Crane Dance") return true;
        break;
      case "CRU057": case "CRU058": case "CRU059":
        if($lastAttackName == "Soulbead Strike") return true;
        break;
      case "CRU060": case "CRU061": case "CRU062":
        if($lastAttackName == "Torrent of Tempo") return true;
        break;
      case "EVR038":
        if($lastAttackName == "Rushing River" || $lastAttackName == "Flood of Force") return true;
        break;
      case "EVR040":
        if($lastAttackName == "Hundred Winds") return true;
        break;
      case "EVR041": case "EVR042": case "EVR043":
        if($lastAttackName == "Hundred Winds") return true;
        break;
      case "DYN047":
      case "DYN056": case "DYN057": case "DYN058":
      case "DYN059": case "DYN060": case "DYN061":
        if($lastAttackName == "Crouching Tiger") return true;
        break;
      case "OUT050":
        if($lastAttackName == "Spinning Wheel Kick") return true;
        break;
      case "OUT051":
        if($lastAttackName == "Bonds of Ancestry") return true;
        break;
      case "OUT056": case "OUT057": case "OUT058":
        if(str_contains($lastAttackName, "Gustwave")) return true;
        break;
      case "OUT059": case "OUT060": case "OUT061":
        if($lastAttackName == "Head Jab") return true;
        break;
      case "OUT062": case "OUT063": case "OUT064":
        if($lastAttackName == "Twin Twisters" || $lastAttackName == "Spinning Wheel Kick") return true;
        break;
      case "OUT065": case "OUT066": case "OUT067":
        if($lastAttackName == "Twin Twisters") return true;
        break;
      case "OUT074": case "OUT075": case "OUT076":
        if($lastAttackName == "Surging Strike") return true;
        break;
      case "OUT080": case "OUT081": case "OUT082":
        if($lastAttackName == "Head Jab") return true;
        break;
      default: break;
    }
  }
  return false;
}

function HasBloodDebt($cardID)
{
  switch ($cardID) {
    case "MON123"; case "MON124"; case "MON125"; case "MON126": case "MON127": case "MON128"; case "MON129":
    case "MON130": case "MON131"; case "MON135": case "MON136": case "MON137"; case "MON138": case "MON139":
    case "MON140"; case "MON141": case "MON142": case "MON143"; case "MON144": case "MON145": case "MON146";
    case "MON147": case "MON148": case "MON149"; case "MON156": case "MON158": case "MON159": case "MON160":
    case "MON161": case "MON165": case "MON166": case "MON167": case "MON168": case "MON169": case "MON170":
    case "MON171": case "MON172": case "MON173": case "MON174": case "MON175": case "MON176": case "MON177":
    case "MON178": case "MON179": case "MON180": case "MON181": case "MON182": case "MON183": case "MON184":
    case "MON185": case "MON187": case "MON191": case "MON192": case "MON194": case "MON200": case "MON201":
    case "MON202": case "MON203": case "MON204": case "MON205": case "MON209": case "MON210": case "MON211":
      return true;
    default: return false;
  }
}

function PlayableFromBanish($cardID, $mod="")
{
  global $currentPlayer, $CS_NumNonAttackCards, $CS_Num6PowBan;
  $mod = explode("-", $mod)[0];
  if($mod == "TCL" || $mod == "TT" || $mod == "TCC" || $mod == "NT" || $mod == "INST") return true;
  switch($cardID) {

    default: return false;
  }
}

function AbilityPlayableFromBanish($cardID)
{
  global $currentPlayer, $mainPlayer;
  switch($cardID) {
    default: return false;
  }
}

function RequiresDieRoll($cardID, $from, $player)
{
  global $turn;
  if(GetDieRoll($player) > 0) return false;
  if($turn[0] == "B") return false;
  return false;
}

function SpellVoidAmount($cardID, $player)
{
  if($cardID == "ARC112" && SearchCurrentTurnEffects("DYN171", $player)) return 1;
  switch($cardID) {
    default: return 0;
  }
}

function IsSpecialization($cardID)
{
  switch ($cardID) {

    default:
      return false;
  }
}

function Is1H($cardID)
{
  switch ($cardID) {

    default:
      return false;
  }
}

function AbilityPlayableFromCombatChain($cardID)
{
  switch($cardID) {

    default: return false;
  }
}

function CardHasAltArt($cardID)
{
  switch ($cardID) {

  default:
      return false;
  }
}

function IsIyslander($character)
{
  switch($character) {
    default: return false;
  }
}

function WardAmount($cardID)
{
  switch($cardID)
  {
    default: return 0;
  }
}

function HasWard($cardID)
{
  switch ($cardID) {

    default: return false;
  }
}

function HasDominate($cardID)
{
  global $mainPlayer, $combatChainState;
  global $CS_NumAuras, $CCS_NumBoosted;
  switch ($cardID)
  {

    default: break;
  }
  return false;
}

function Rarity($cardID)
{
  $set = CardSet($cardID);
  if($set != "ROG" && $set != "DUM")
  {
    $number = intval(substr($cardID, 3));
    if($number < 400) return GeneratedRarity($cardID);
  }
  if ($set == "ROG") {
    return ROGUERarity($cardID);
  }
}
