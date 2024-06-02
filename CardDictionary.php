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
  if(!$cardID || $cardID == "" || strlen($cardID) < 3) return "";
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

function NumResourcesAvailable($player) {
  $resources = &GetResourceCards($player);
  $numAvailable = 0;
  for($i=0; $i<count($resources); $i+=ResourcePieces()) {
    if($resources[$i+4] == 0) ++$numAvailable;
  }
  return $numAvailable;
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
    case "1611702639": $amount += $initiativePlayer == $player ? 2 : 0; break;//Consortium Starviper
    case "4405415770": $amount += 2; break;
    case "0827076106": $amount += 1; break;
    case "4919000710": $amount += 2; break;
    case "9412277544": $amount += 1; break;
    case "e2c6231b35": $amount += 2; break;
    case "7109944284": $amount += 3; break;
    case "8142386948": $amount += 2; break;//Razor Crest
    case "4327133297": $amount += 2; break;//Moisture Farmer
    case "5977238053": $amount += 2; break;//Sundari Peacekeeper
    case "9503028597": $amount += 1; break;//Clone Deserter
    default: break;
  }
  if($amount > 0 && $ally->LostAbilities()) return 0;
  return $amount;
}

function RaidAmount($cardID, $player, $index)
{
  global $currentTurnEffects, $combatChain;
  if(count($combatChain) == 0) return 0;
  $amount = 0;
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "8995892693"://Red Three
        if($index != $i && AspectContains($cardID, "Heroism", $player)) $amount += 1;
        break;
      case "fb475d4ea4"://IG-88
        if($index != $i) $amount += 1;
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
    case "87e8807695": $amount += 1; break;
    case "8395007579": $amount += $ally->MaxHealth() - $ally->Health(); break;//Fifth Brother
    case "6208347478": $amount += SearchCount(SearchAllies($player, trait:"Spectre")) > 1 ? 1 : 0; break;//Chopper
    case "3487311898": $amount += 3; break;//Clan Challengers
    case "5977238053": $amount += 2; break;//Sundari Peacekeeper
    case "1805986989": $amount += 2; break;//Modded Cohort
    default: break;
  }
  if($amount > 0 && $ally->LostAbilities()) return 0;
  return $amount;
}

function HasSentinel($cardID, $player, $index)
{
  global $initiativePlayer, $currentTurnEffects;
  $ally = new Ally("MYALLY-" . $index, $player);
  if($ally->LostAbilities()) return false;
  $hasSentinel = false;
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "8294130780": $hasSentinel = true; break;//Gladiator Star Destroyer
      case "3572356139": $hasSentinel = true; break;//Chewbacca, Walking Carpet
      case "9070397522": return false;//SpecForce Soldier
      default: break;
    }
  }
  if($hasSentinel) return true;
  $subcards = $ally->GetSubcards();
  for($i=0; $i<count($subcards); ++$i)
  {
    if($subcards[$i] == "4550121827") return true;//Protector
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
    case "8301e8d7ef":
    case "4786320542":
    case "3896582249":
    case "2855740390":
    case "1982478444"://Vigilant Pursuit Craft
    case "1747533523"://Village Protectors
    case "6585115122"://The Mandalorian
    case "2969011922"://Pyke Sentinel
    case "8552719712"://Pirate Battle Tank
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
    case "1780978508"://Emperor's Royal Guard
      return SearchCount(SearchAllies($player, trait:"Official")) > 0;
    case "9405733493"://Protector of the Throne
      $ally = new Ally("MYALLY-" . $index, $player);
      return $ally->IsUpgraded();
    default: return false;
  }
}

function HasGrit($cardID, $player, $index)
{
  $ally = new Ally("MYALLY-" . $index, $player);
  if($ally->LostAbilities()) return false;
  switch($cardID)
  {
    case "5335160564":
    case "9633997311":
    case "8098293047":
    case "5879557998":
    case "4599464590":
    case "8301e8d7ef":
    case "5557494276"://Death Watch Loyalist
    case "6878039039"://Hylobon Enforcer
    case "8190373087"://Gentle Giant
      return true;
    default: return false;
  }
}

function HasOverwhelm($cardID, $player, $index)
{
  global $defPlayer;
  $ally = new Ally("MYALLY-" . $index, $player);
  if($ally->LostAbilities()) return false;
  switch($cardID)
  {
    case "6072239164":
    case "6577517407":
    case "6718924441":
    case "9097316363":
    case "3232845719":
    case "4631297392":
    case "6432884726":
    case "5557494276"://Death Watch Loyalist
    case "2470093702"://Wrecker
      return true;
    case "4619930426"://First Legion Snowtrooper
      $target = GetAttackTarget();
      if($target == "THEIRCHAR-0") return false;
      $ally = new Ally($target, $defPlayer);
      return $ally->IsDamaged();
    case "3487311898"://Clan Challengers
      return $ally->IsUpgraded();
    default: return false;
  }
}

function HasAmbush($cardID, $player, $index, $from)
{
  global $currentTurnEffects;
  $ally = new Ally("MYALLY-" . $index, $player);
  for($i=count($currentTurnEffects)-CurrentTurnPieces(); $i>=0; $i-=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "8327910265":
        RemoveCurrentTurnEffect($i);
        return true;//Energy Conversion Lab (ECL)
      default: break;
    }
  }
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "4566580942"://Admiral Piett
        if(CardCost($cardID) >= 6) return true;
        break;
      case "4339330745"://Wedge Antilles
        if(TraitContains($cardID, "Vehicle", $player)) return true;
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
    case "2649829005":
    case "1862616109":
    case "3684950815":
    case "9500514827":
    case "8506660490":
    case "1805986989"://Modded Cohort
      return true;
    case "2027289177"://Escort Skiff
      return SearchCount(SearchAllies($player, aspect:"Command")) > 1;
    case "4685993945"://Frontier AT-RT
      return SearchCount(SearchAllies($player, trait:"Vehicle")) > 1;
    case "5752414373"://Millennium Falcon
      return $from == "HAND";
    default: return false;
  }
}

function HasShielded($cardID, $player, $index)
{
  switch($cardID)
  {
    case "0700214503":
    case "5264521057":
    case "9950828238"://Seventh Fleet Defender
    case "9459170449"://Cargo Juggernaut
    case "6931439330":
    case "9624333142":
    case "b0dbca5c05":
    case "3280523224":
    case "7728042035":
    case "7870435409":
    case "6135081953"://Doctor Evazan
    case "1747533523"://Village Protectors
    case "1090660242"://The Client
      return true;
    default: return false;
  }
}

function HasSaboteur($cardID, $player, $index)
{
  global $currentTurnEffects;
  $ally = new Ally("MYALLY-" . $index, $player);
  if($ally->LostAbilities()) return false;
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "9210902604": return true;//Precision Fire
      default: break;
    }
  }
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
    case "0828695133":
    case "9250443409":
    case "3c60596a7a":
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
  switch($cardID)
  {
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
    default: return 0;
  }
}

function AbilityCost($cardID)
{
  global $currentPlayer;
  switch($cardID) {
    case "2579145458"://Luke Skywalker
      return GetResolvedAbilityName($cardID) == "Give Shield" ? 1 : 0;
    case "2912358777"://Grand Moff Tarkin
      return GetResolvedAbilityName($cardID) == "Give Experience" ? 1 : 0;
    case "3187874229"://Cassian Andor
      return GetResolvedAbilityName($cardID) == "Draw Card" ? 1 : 0;
    case "4300219753"://Fett's Firespray
      return GetResolvedAbilityName($cardID) == "Exhaust" ? 2 : 0;
    case "5784497124"://Emperor Palpatine
      return GetResolvedAbilityName($cardID) == "Deal Damage" ? 1 : 0;
    case "6088773439"://Darth Vader
      return GetResolvedAbilityName($cardID) == "Deal Damage" ? 1 : 0;
    case "1951911851"://Grand Admiral Thrawn
      return GetResolvedAbilityName($cardID) == "Exhaust" ? 1 : 0;
    case "1885628519"://Crosshair
      return GetResolvedAbilityName($cardID) == "Buff" ? 2 : 0;
    default: break;
  }
  if(IsAlly($cardID)) return 0;
  return 0;
}

function DynamicCost($cardID)
{
  global $currentPlayer;
  switch($cardID) {
    case "2639435822"://Force Lightning
      if(SearchCount(SearchAllies($currentPlayer, trait:"Force")) > 0) return "1,2,3,4,5,6,7,8,9,10";
      return "1";
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
    case "1393827469"://Tarkintown
      return "A";
    case "2429341052"://Security Complex
      return "A";
    case "8327910265"://Energy Conversion Lab (ECL)
      return "A";
    case "4626028465"://Boba Fett
    case "7440067052"://Hera Syndulla
    case "8560666697"://Director Krennic
      $char = &GetPlayerCharacter($currentPlayer);
      return $char[CharacterPieces() + 2] == 0 ? "A" : "";
    default: return "";
  }
}

function GetAbilityTypes($cardID)
{
  global $currentPlayer;
  $abilityTypes = "";
  switch($cardID) {
    case "2554951775"://Bail Organa
      $abilityTypes = "A,AA";
      break;
    case "2756312994"://Alliance Dispatcher
      $abilityTypes = "A,AA";
      break;
    case "3572356139"://Chewbacca, Walking Carpet
      $abilityTypes = "A";
      break;
    case "2579145458"://Luke Skywalker
      $abilityTypes = "A";
      break;
    case "2912358777"://Grand Moff Tarkin
      $abilityTypes = "A";
      break;
    case "3187874229"://Cassian Andor
      $abilityTypes = "A";
      break;
    case "4841169874"://Sabine Wren
      $abilityTypes = "A";
      break;
    case "2048866729"://Iden Versio
      $abilityTypes = "A";
      break;
    case "6088773439"://Darth Vader
      $abilityTypes = "A";
      break;
    case "4263394087"://Chirrut Imwe
      $abilityTypes = "A";
      break;
    case "4300219753"://Fett's Firespray
      $abilityTypes = "A,AA";
      break;
    case "7911083239"://Grand Inquisitor
      $abilityTypes = "A";
      break;
    case "5954056864"://Han Solo
      $abilityTypes = "A";
      break;
    case "6514927936"://Leia Organa
      $abilityTypes = "A";
      break;
    case "8244682354"://Jyn Erso
      $abilityTypes = "A";
      break;
    case "8600121285"://IG-88
      $abilityTypes = "A";
      break;
    case "7870435409"://Bib Fortuna
      $abilityTypes = "A,AA";
      break;
    case "5784497124"://Emperor Palpatine
      $allies = &GetAllies($currentPlayer);
      if(count($allies) == 0) break;
      $abilityTypes = "A";
      break;
    case "8117080217"://Admiral Ozzel
      $abilityTypes = "A,AA";
      break;
    case "2471223947"://Frontline Shuttle
      $abilityTypes = "A,AA";
      break;
    case "1951911851"://Grand Admiral Thrawn
      $abilityTypes = "A";
      break;
    case "6722700037"://Doctor Pershing
      $abilityTypes = "A,AA";
      break;
    case "6536128825"://Grogu
      $abilityTypes = "A,AA";
      break;
    case "1090660242"://The Client
      $abilityTypes = "A,AA";
      break;
    case "1885628519"://Crosshair
      $abilityTypes = "A,A,AA";
      break;
    default: break;
  }
  if(DefinedTypesContains($cardID, "Leader", $currentPlayer) && !IsAlly($cardID, $currentPlayer)) {
    $char = &GetPlayerCharacter($currentPlayer);
    if($char[CharacterPieces() + 1] == 1) $abilityTypes = "";
    if($char[CharacterPieces() + 2] == 0) {
      if($abilityTypes != "") $abilityTypes .= ",";
      $abilityTypes .= "A";
    }
  }
  return $abilityTypes;
}

function IsLeader($cardID, $playerID) {
  return DefinedTypesContains($cardID, "Leader", $playerID);
}

function GetAbilityNames($cardID, $index = -1, $validate=false)
{
  global $currentPlayer;
  $abilityNames = "";
  switch ($cardID) {
    case "2554951775"://Bail Organa
      $abilityNames = "Give Experience,Attack";
      break;
    case "2756312994"://Alliance Dispatcher
      $abilityNames = "Play Unit,Attack";
      break;
    case "3572356139"://Chewbacca, Walking Carpet
      $abilityNames = "Play Taunt";
      break;
    case "2579145458"://Luke Skywalker
      $abilityNames = "Give Shield";
      break;
    case "2912358777"://Grand Moff Tarkin
      $abilityNames = "Give Experience";
      break;
    case "3187874229"://Cassian Andor
      $abilityNames = "Draw Card";
      break;
    case "4841169874"://Sabine Wren
      $abilityNames = "Deal Damage";
      break;
    case "2048866729"://Iden Versio
      $abilityNames = "Heal";
      break;
    case "6088773439"://Darth Vader
      $abilityNames = "Deal Damage";
      break;
    case "4263394087"://Chirrut Imwe
      $abilityNames = "Buff HP";
      break;
    case "4300219753"://Fett's Firespray
      $ally = new Ally("MYALLY-" . $index, $currentPlayer);
      if($validate) $abilityNames = $ally->IsExhausted() ? "Exhaust" : "Exhaust,Attack";
      else $abilityNames = "Exhaust,Attack";
      break;
    case "7911083239"://Grand Inquisitor
      $abilityNames = "Deal Damage";
      break;
    case "5954056864"://Han Solo
      $abilityNames = "Play Resource";
      break;
    case "6514927936"://Leia Organa
      $abilityNames = "Attack";
      break;
    case "8244682354"://Jyn Erso
      $abilityNames = "Attack";
      break;
    case "8600121285"://IG-88
      $abilityNames = "Attack";
      break;
    case "7870435409"://Bib Fortuna
      $abilityNames = "Play Event,Attack";
      break;
    case "5784497124"://Emperor Palpatine
      $allies = &GetAllies($currentPlayer);
      if(count($allies) == 0) break;
      $abilityNames = "Deal Damage";
      break;
    case "8117080217"://Admiral Ozzel
      $abilityNames = "Play Imperial Unit,Attack";
      break;
    case "2471223947"://Frontline Shuttle
      $abilityNames = "Shuttle,Attack";
      break;
    case "1951911851"://Grand Admiral Thrawn
      $abilityNames = "Exhaust";
      break;
    case "6722700037"://Doctor Pershing
      $abilityNames = "Draw,Attack";
      break;
    case "6536128825"://Grogu
      $abilityNames = "Exhaust,Attack";
      break;
    case "1090660242"://The Client
      $abilityNames = "Bounty,Attack";
      break;
    case "1885628519"://Crosshair
      $abilityNames = "Buff,Snipe,Attack";
      break;
    default: break;
  }
  if(DefinedTypesContains($cardID, "Leader", $currentPlayer) && !IsAlly($cardID, $currentPlayer)) {
    $char = &GetPlayerCharacter($currentPlayer);
    if($char[CharacterPieces() + 1] == 1) $abilityNames = "";
    if($char[CharacterPieces() + 2] == 0) {
      if($abilityNames != "") $abilityNames .= ",";
      $abilityNames .= "Deploy";
    }
  }
  return $abilityNames;
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
  if($from != "PLAY" && $from != "EQUIP" && $from != "-") return "";
  $abilityIndex = GetClassState($currentPlayer, $CS_AbilityIndex);
  $abilityNames = GetAbilityNames($cardID);
  if($abilityNames == "" || $abilityIndex == "-") return "";
  $abilityNames = explode(",", $abilityNames);
  return $abilityNames[$abilityIndex];
}

function IsPlayable($cardID, $phase, $from, $index = -1, &$restriction = null, $player = "")
{
  global $currentPlayer, $CS_NumActionsPlayed, $combatChainState, $CCS_BaseAttackDefenseMax, $CS_NumNonAttackCards, $CS_NumAttackCards;
  global $CCS_ResourceCostDefenseMin, $actionPoints, $mainPlayer, $defPlayer;
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
  $otherPlayer = ($player == 1 ? 2 : 1);
  if($from == "HAND" && ((CardCost($cardID) + SelfCostModifier($cardID)) > NumResourcesAvailable($currentPlayer)) && !HasAlternativeCost($cardID)) return false;
  if($from == "RESOURCES") {
    if(!PlayableFromResources($cardID, index:$index)) return false;
    if((SmuggleCost($cardID, index:$index) + SelfCostModifier($cardID)) > NumResourcesAvailable($currentPlayer) && !HasAlternativeCost($cardID)) return false;
  }
  if(DefinedTypesContains($cardID, "Upgrade", $player) && SearchCount(SearchAllies($player)) == 0 && SearchCount(SearchAllies($otherPlayer)) == 0) return false;
  if($phase == "M" && $from == "HAND") return true;
  $isStaticType = IsStaticType($cardType, $from, $cardID);
  if($isStaticType) {
    $cardType = GetAbilityType($cardID, $index, $from);
    if($cardType == "") {
      $abilityTypes = GetAbilityTypes($cardID);
      $typeArr = explode(",", $abilityTypes);
      $cardType = $typeArr[0];
    }
  }
  if($phase == "M" && ($cardType == "A" || $cardType == "AA" || $cardType == "I")) return true;
  if($cardType == "I" && ($phase == "INSTANT" || $phase == "A" || $phase == "D")) return true;
  return false;

}

function HasAlternativeCost($cardID) {
  switch($cardID) {
    case "9644107128"://Bamboozle
      return true;
    default: return false;
  }
}

//Preserve
function GoesWhereAfterResolving($cardID, $from = null, $player = "", $playedFrom="", $resourcesPaid="", $additionalCosts="")
{
  global $currentPlayer, $mainPlayer;
  if($player == "") $player = $currentPlayer;
  if(DefinedTypesContains($cardID, "Upgrade", $currentPlayer)) return "ATTACHTARGET"; 
  if(IsAlly($cardID)) return "ALLY";
  switch($cardID) {
    case "2703877689": return "RESOURCE";//Resupply
    case "0073206444"://Command
      return str_contains($additionalCosts, "Resource") ? "RESOURCE" : "GY";
    default: return "GY";
  }
}


function UpgradeFilter($cardID)
{
  switch($cardID) {
    case "0160548661"://Fallen Lightsaber
    case "8495694166"://Jedi Lightsaber
    case "0705773109"://Vader's Lightsaber
    case "6903722220"://Luke's Lightsaber
    case "1323728003"://Electrostaff
    case "3514010297"://Mandalorian Armor
    case "3525325147"://Vambrace Grappleshot
      return "trait=Vehicle";
    case "8055390529"://Traitorous
      return "maxCost=3";
    default: return "";
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

function IsToken($cardID)
{
  switch($cardID) {
    case "8752877738": return true;
    case "2007868442": return true;
    default: return false;
  }
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
    case "0523973552"://I Am Your Father
      $theirAllies = &GetAllies($otherPlayer);
      return count($theirAllies) == 0;
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

function LeaderUnit($cardID) {
  switch($cardID) {
    //Spark of Rebellion
    case "3572356139"://Chewbacca, Walking Carpet
      return "8301e8d7ef";
    case "2579145458"://Luke Skywalker
      return "0dcb77795c";
    case "2912358777"://Grand Moff Tarkin
      return "59cd013a2d";
    case "3187874229"://Cassian Andor
      return "3c60596a7a";
    case "4841169874"://Sabine Wren
      return "51e8757e4c";
    case "2048866729"://Iden Versio
      return "b0dbca5c05";
    case "6088773439"://Darth Vader
      return "0ca1902a46";
    case "4263394087"://Chirrut Imwe
      return "d1a7b76ae7";
    case "4626028465"://Boba Fett
      return "0e65f012f5";
    case "7911083239"://Grand Inquisitor
      return "6827598372";
    case "5954056864"://Han Solo
      return "5e90bd91b0";
    case "6514927936"://Leia Organa
      return "87e8807695";
    case "8244682354"://Jyn Erso
      return "20f21b4948";
    case "8600121285"://IG-88
      return "fb475d4ea4";
    case "5784497124"://Emperor Palpatine
      return "6c5b96c7ef";
    case "8560666697"://Director Krennic
      return "e2c6231b35";
    case "7440067052"://Hera Syndulla
      return "80df3928eb";
    case "1951911851"://Grand Admiral Thrawn
      return "02199f9f1e";
    //Shadows of the Galaxy
    default: return "";
  }
}

function LeaderUndeployed($cardID) {
  switch($cardID) {
    //Spark of Rebellion
    case "8301e8d7ef"://Chewbacca, Walking Carpet
      return "3572356139";
    case "0dcb77795c"://Luke Skywalker
      return "2579145458";
    case "59cd013a2d"://Grand Moff Tarkin
      return "2912358777";
    case "3c60596a7a"://Cassian Andor
      return "3187874229";
    case "51e8757e4c"://Sabine Wren
      return "4841169874";
    case "b0dbca5c05"://Iden Versio
      return "2048866729";
    case "0ca1902a46"://Darth Vader
      return "6088773439";
    case "d1a7b76ae7"://Chirrut Imwe
      return "4263394087";
    case "0e65f012f5"://Boba Fett
      return "4626028465";
    case "6827598372"://Grand Inquisitor
      return "7911083239";
    case "5e90bd91b0"://Han Solo
      return "5954056864";
    case "87e8807695"://Leia Organa
      return "6514927936";
    case "20f21b4948"://Jyn Erso
      return "8244682354";
    case "fb475d4ea4"://IG-88
      return "8600121285";
    case "6c5b96c7ef"://Emperor Palpatine
      return "5784497124";
    case "e2c6231b35"://Director Krennic
      return "8560666697";
    case "80df3928eb"://Hera Syndulla
      return "7440067052";
    case "02199f9f1e"://Grand Admiral Thrawn
      return "1951911851";
    //Shadows of the Galaxy
    default: return "";
  }
}

function HasAttackAbility($cardID) {
  switch($cardID) {
    case "1746195484"://Jedha Agitator
    case "5707383130"://Bendu
    case "1862616109"://Snowspeeder
    case "3613174521"://Outer Rim Headhunter
    case "4599464590"://Rugged Survivors
    case "4299027717"://Mining Guild Tie Fighter
    case "7728042035"://Chimaera
    case "8691800148"://Reinforcement Walker
    case "9568000754"://R2-D2
    case "8009713136"://C-3PO
    case "7533529264"://Wolffe
      return true;
    default: return false;
  }
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

function SmuggleCost($cardID, $player="", $index="")
{
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  switch($cardID) {
    case "1982478444": return 7;//Vigilant Pursuit Craft
    case "0866321455": return 3;//Smuggler's Aid
    case "6037778228": return 5;//Night Owl Skirmisher
    case "2288926269": return 6;//Privateer Crew
    case "5752414373": return 6;//Millennium Falcon
    case "8552719712": return 7;//Pirate Battle Tank
    default: return -1;
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

function PlayableFromResources($cardID, $player="", $index="") {
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  if(SmuggleCost($cardID, $player, $index) > 0) return true;
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
