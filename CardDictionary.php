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

function CardName($cardID) {
  return CardTitle($cardID) . " " . CardSubtitle($cardID);
}

function CardType($cardID)
{
  if(!$cardID) return "";
  $definedCardType = DefinedCardType($cardID);
  if($definedCardType == "Leader") return "C";
  else if($definedCardType == "Base") return "W";
  return "A";
}

function CardSubType($cardID)
{
  if(!$cardID) return "";
  return "";
  //return CardSubTypes($cardID);
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

function NumResources($player) {
  $resources = &GetResourceCards($player);
  return count($resources)/ResourcePieces();
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

function RestoreAmount($cardID, $player, $index)
{
  global $initiativePlayer;
  $amount = 0;
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "4919000710"://Home One
        if($index != $i) $amount += 1;
        break;
      default: break;
    }
  }
  $ally = new Ally("MYALLY-" . $index, $player);
  $subcards = $ally->GetSubcards();
  for($i=0; $i<count($subcards); ++$i)
  {
    if($subcards[$i] == "8788948272") $amount += 2;
  }
  switch($cardID)
  {
    case "0074718689": $amount += 1; break;
    case "1081012039": $amount += 2; break;
    case "1611702639": $amount += $initiativePlayer == $player ? 2 : 0; break;
    case "4405415770": $amount += 2; break;
    case "0827076106": $amount += 1; break;
    case "4919000710": $amount += 2; break;
    default: break;
  }
  return $amount;
}

function RaidAmount($cardID, $player, $index)
{
  global $currentTurnEffects;
  $amount = 0;
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "8995892693"://Red One
        if($index != $i && AspectContains($cardID, "Aggression", $player)) $amount += 1;
        break;
      default: break;
    }
  }
  $ally = new Ally("MYALLY-" . $index, $player);
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "0256267292"://Benthic "Two Tubes"
        $amount += 2;
        break;
      case "1208707254"://Rallying Cry
        $amount += 2;
        break;
      default: break;
    }
  }
  switch($cardID)
  {
    case "1017822723": $amount += 2; break;
    case "2404916657": $amount += 2; break;
    case "7495752423": $amount += 2; break;
    case "4642322279": $amount += SearchCount(SearchAllies($player, aspect:"Aggression")) > 1 ? 2 : 0; break;
    case "6028207223": $amount += 1; break;
    case "8995892693": $amount += 1; break;
    case "3613174521": $amount += 1; break;
    case "4111616117": $amount += 1; break;
    default: break;
  }
  return $amount;
}

function HasSentinel($cardID, $player, $index)
{
  global $initiativePlayer, $currentTurnEffects;
  $ally = new Ally("MYALLY-" . $index, $player);
  $subcards = $ally->GetSubcards();
  for($i=0; $i<count($subcards); ++$i)
  {
    if($subcards[$i] == "4550121827") return true;//Protector
  }
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "8294130780": return true;//Gladiator Star Destroyer
      default: break;
    }
  }
  switch($cardID)
  {
    case "2524528997":
    case "6385228745":
    case "6912684909":
    case "7751685516":
    case "9702250295":
    case "6253392993":
    case "7596515127":
    case "5707383130":
    case "8918765832":
    case "4631297392":
      return true;
    case "2739464284"://Gamorrean Guards
      return SearchCount(SearchAllies($player, aspect:"Cunning")) > 1;
    case "3138552659"://Homestead Militia
      return NumResources($player) >= 6;
    case "7622279662"://Vigilant Honor Guards
      $ally = new Ally("MYALLY-" . $index, $player);
      return !$ally->IsDamaged();
    case "5879557998"://Baze Melbus
      return $initiativePlayer == $player;
    default: return false;
  }
}

function HasGrit($cardID, $player, $index)
{
  switch($cardID)
  {
    case "5335160564":
    case "9633997311":
    case "8098293047":
    case "5879557998":
    case "4599464590":
      return true;
    default: return false;
  }
}

function HasOverwhelm($cardID, $player, $index)
{
  switch($cardID)
  {
    case "6072239164":
    case "6577517407":
    case "6718924441":
    case "9097316363":
    case "3232845719":
    case "4631297392":
      return true;
    default: return false;
  }
}

function HasAmbush($cardID, $player, $index)
{
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "4566580942"://Admiral Piett
        if(CardCost($cardID) >= 6) return true;
        break;
      default: break;
    }
  }
  switch($cardID)
  {
    case "5346983501":
    case "6718924441":
    case "7285270931":
    case "3377409249":
    case "5230572435":
    case "0052542605":
    case "1862616109":
    case "3684950815":
      return true;
    case "2027289177"://Escort Skiff
      return SearchCount(SearchAllies($player, aspect:"Command")) > 1;
    case "4685993945"://Frontier AT-RT
      return SearchCount(SearchAllies($player, trait:"Vehicle")) > 1;
    default: return false;
  }
}

function HasShielded($cardID, $player, $index)
{
  switch($cardID)
  {
    case "0700214503":
    case "5264521057":
    case "9950828238":
    case "9459170449":
    case "6931439330":
    case "9624333142":
      return true;
    default: return false;
  }
}

function HasSaboteur($cardID, $player, $index)
{
  $ally = new Ally("MYALLY-" . $index, $player);
  $subcards = $ally->GetSubcards();
  for($i=0; $i<count($subcards); ++$i)
  {
    if($subcards[$i] == "0797226725") return true;//Infiltrator's Skill
  }
  switch($cardID)
  {
    case "1017822723":
    case "9859536518":
    case "0046930738":
    case "7533529264":
    case "1746195484":
    case "5907868016":
      return true;
    default: return false;
  }
}

function HasCleave($cardID)
{
  switch($cardID)
  {
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
    default: return false;
  }
}


//Critical only applies to combat damage, so you can assume player/attacker
function CriticalAmount($cardID)
{
  global $mainPlayer;
  switch($cardID)
  {
    default: return 0;
  }
}

function HasStealth($cardID, $player, $index)
{
  $allies = &GetAllies($player);
  if(CurrentEffectGrantsStealth($player, $allies[$index+5])) return true;
  switch($cardID)
  {

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

function AbilityCost($cardID)
{
  global $currentPlayer;
  switch($cardID) {

    default: break;
  }
  if(IsAlly($cardID)) return 0;
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
  if($from == "PLAY" && IsAlly($cardID)) return "AA";
  switch($cardID)
  {
    case "2569134232"://Jedha City
      return "A";
    case "1393827469"://Tarkin Town
      return "A";
    default: return "";
  }
}

function GetAbilityTypes($cardID)
{
  switch($cardID) {
    case "2554951775"://Bail Organa
      return "A,AA";
    case "2756312994"://Alliance Dispatcher
      return "A,AA";
    default: return "";
  }
}

function GetAbilityNames($cardID, $index = -1)
{
  global $currentPlayer;
  switch ($cardID) {
    case "2554951775"://Bail Organa
      return "Give Experience,Attack";
    case "2756312994"://Alliance Dispatcher
      return "Play Unit,Attack";
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
  if(DefinedTypesContains($cardID, "Upgrade", $currentPlayer)) return "ATTACHTARGET"; 
  if(IsAlly($cardID)) return "ALLY";
  switch($cardID) {
    case "2703877689": return "RESOURCE";
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
  if ($cardID == "" && count($combatChain) > 0) $cardID = $combatChain[0];
  if ($cardID == "") return false;
  if(count($chainLinkSummary) == 0) return false;//No combat active if no previous chain links
  $lastAttackNames = explode(",", $chainLinkSummary[count($chainLinkSummary)-ChainLinkSummaryPieces()+4]);
  for($i=0; $i<count($lastAttackNames); ++$i)
  {
    $lastAttackName = GamestateUnsanitize($lastAttackNames[$i]);
    switch ($cardID) {

      default: break;
    }
  }
  return false;
}

function HasBloodDebt($cardID)
{
  switch ($cardID) {
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
