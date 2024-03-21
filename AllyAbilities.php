<?php

function PlayAlly($cardID, $player, $subCards = "-", $from="-")
{
  $allies = &GetAllies($player);
  array_push($allies, $cardID);
  array_push($allies, AllyEntersPlayState($cardID, $player, $from));
  array_push($allies, AllyHealth($cardID, $player));
  array_push($allies, 0); //Frozen
  array_push($allies, $subCards); //Subcards
  array_push($allies, GetUniqueId()); //Unique ID
  array_push($allies, AllyEnduranceCounters($cardID)); //Endurance Counters
  array_push($allies, 0); //Buff Counters
  array_push($allies, 1); //Ability/effect uses
  array_push($allies, 0); //Position
  array_push($allies, 0); //Fostered
  $index = count($allies) - AllyPieces();
  CurrentEffectAllyEntersPlay($player, $index);
  AllyEntersPlayAbilities($player);
  return $index;
}

function DealAllyDamage($targetPlayer, $index, $damage, $type="")
{
  $allies = &GetAllies($targetPlayer);
  if($allies[$index+6] > 0) {
    $damage -= 3;
    if($damage < 0) $damage = 0;
    --$allies[$index+6];
  }
  $allies[$index+2] -= $damage;
  if($damage > 0) AllyDamageTakenAbilities($targetPlayer, $index);
  if($allies[$index+2] <= 0) DestroyAlly($targetPlayer, $index, fromCombat:($type == "COMBAT" ? true : false));
}

function RemoveAlly($player, $index)
{
  return DestroyAlly($player, $index, true);
}

function DestroyAlly($player, $index, $skipDestroy = false, $fromCombat = false)
{
  global $combatChain, $mainPlayer;
  $allies = &GetAllies($player);
  if(!$skipDestroy) {
    AllyDestroyedAbility($player, $index);
  }
  AllyLeavesPlayAbility($player, $index);
  $cardID = $allies[$index];
  if(!$skipDestroy) {
    if($cardID == "8954587682") AddResources($cardID, $player, "PLAY", "DOWN");
    else AddGraveyard($cardID, $player, "PLAY");
  }
  for($j = $index + AllyPieces() - 1; $j >= $index; --$j) unset($allies[$j]);
  $allies = array_values($allies);
  //On Kill abilities
  if($fromCombat)
  {
    if(SearchCurrentTurnEffects("TJTeWcZnsQ", $mainPlayer)) Draw($mainPlayer);//Lorraine, Blademaster)
    if($combatChain[0] == "zcVjsVRBV8" && CharacterLevel($mainPlayer) >= 2 && (IsClassBonusActive($mainPlayer, "WARRIOR") || IsClassBonusActive($mainPlayer, "ASSASSIN")))//Combo Strike
    {
      $char = &GetPlayerCharacter($mainPlayer);
      $char[1] = 2;
    }
  }
  return $cardID;
}

function AllyAddGraveyard($player, $cardID, $subtype)
{
  if(CardType($cardID) != "T") {
    $set = substr($cardID, 0, 3);
    $number = intval(substr($cardID, 3, 3));
    $number -= 400;
    if($number < 0) return;
    $id = $number;
    if($number < 100) $id = "0" . $id;
    if($number < 10) $id = "0" . $id;
    $id = $set . $id;
    if(!SubtypeContains($id, $subtype, $player)) return;
    AddGraveyard($id, $player, "PLAY");
  }
}

function AllyEntersPlayState($cardID, $player, $from="-")
{
  return 2;//TODO:delete
  //if(SearchCurrentTurnEffects("dxAEI20h8F", $player)) return 1;
  //if(PlayerHasAlly($player == 1 ? 2 : 1, "TqCo3xlf93")) return 1;//Lunete, Frostbinder Priest
  switch($cardID)
  {
    default: return 1;
  }
}

function AllyEntersPlayAbilities($player)
{
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "cVRIUJdTW5"://Meadowbloom Dryad
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "BUFFALLY", 1);
        break;
      default: break;
    }
  }
}

function AllyPride($cardID)
{
  switch($cardID)
  {
    case "hJ2xh9lNMR": return 2;//Gray Wolf
    case "GXeEa0pe3B": return 3;//Rebellious Bull
    case "MmbQQdsRhi": return 5;//Enraged Boars
    case "1Sl4Gq2OuV": return 4;//Blue Slime
    case "gKVMTAeLXQ": return 5;//Blazing Direwolf
    case "dZ960Hnkzv": return 10;//Vertus, Gaia's Roar
    case "HWFWO0TB8l": return 5;//Tempest Silverback
    case "krgjMyVHRd": return 6;//Lakeside Serpent
    case "075L8pLihO": return 5;//Arima, Gaia's Wings
    case "wFH1kBLrWh": return 7;//Arcane Elemental
    case "p3nq0ymvdd": return 2;//Ordinary Bear
    case "mttsvbgl6f": return 3;//Red Slime
    default: return -1;
  }
}

function AllyHealth($cardID, $playerID="")
{
  $health = CardHP($cardID);
  switch($cardID)
  {
    default: break;
  }
  return $health;
}

function AllyLeavesPlayAbility($player, $index)
{
  $allies = &GetAllies($player);
  $cardID = $allies[$index];
  switch($cardID)
  {
    case "XZFXOE9sEV"://Zephyr Assistant
      PlayAura("ENLIGHTEN", $player);
      break;
    default: break;
  }
}

function AllyDestroyedAbility($player, $index)
{
  global $mainPlayer, $initiativePlayer;
  $allies = &GetAllies($player);
  $cardID = $allies[$index];
  OnKillAbility();
  switch($cardID) {
    case "4405415770"://Yoda, Old Master
      WriteLog("Player $player drew a card from Yoda, Old Master");
      Draw($player);
      break;
    case "8429598559"://Black One
      BlackOne($player);
      break;
    case "9996676854"://Admiral Motti
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to ready");
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:aspect=Villainy");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "READY", 1);
      break;
    case "7517208605"://Star Wing Scout
      if($player == $initiativePlayer) { Draw($player); Draw($player); }
      break;
    default: break;
  }
}

function OnKillAbility()
{
  global $combatChain, $mainPlayer;
  if(count($combatChain) == 0) return;
  switch($combatChain[0])
  {
    case "5230572435"://Mace Windu, Party Crasher
      $ally = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
      $ally->Ready();
      break;
    default: break;
  }
}

function AllyStartTurnAbilities($player)
{
  global $CS_NumMaterializations;
  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    switch($allies[$i]) {
      case "075L8pLihO": BuffAlly($player, $i, 3); break;
      case "CvvgJR4fNa": AddCurrentTurnEffect("CvvgJR4fNa", $player, "PLAY", $allies[$i+5]); break;//Patient Rogue
      case "6gN5KjqRW5": if(IsClassBonusActive($player, "WARRIOR")) AddDurabilityCounters($player, 1); break;//Weaponsmith
      case "jlAc0wWlDZ"://Eager Page
        if(GetClassState($player, $CS_NumMaterializations) == 0) BuffAlly($player, $i);
        break;
      case "ZfCtSldRIy"://Windrider Mage
        AddDecisionQueue("YESNO", $player, "if you want to return Windrider Mage");
        AddDecisionQueue("NOPASS", $player, "-", 1);
        AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $i, 1);
        AddDecisionQueue("MZOP", $player, "BOUNCE", 1);
        AddDecisionQueue("PASSPARAMETER", $player, "ENLIGHTEN", 1);
        AddDecisionQueue("PUTPLAY", $player, "-", 1);
        break;
      case "FWnxKjSeB1"://Spark Fairy
        AddDecisionQueue("YESNO", $player, "if the chosen object is still alive");
        AddDecisionQueue("NOPASS", $player, "-", 1);
        DamageTrigger(($player == 1 ? 2 : 1), 1, "DAMAGE", "FWnxKjSeB1");
        break;
      default: break;
    }
  }
}

function BuffAlly($player, $index, $amount=1)
{
  $allies = &GetAllies($player);
  $allies[$index+7] += $amount;//Buff counters
  $allies[$index+2] += $amount;//Life
}

function AllyEnduranceCounters($cardID)
{
  switch($cardID) {
    case "UPR417": return 1;
    default: return 0;
  }
}

function AllyDamagePrevention($player, $index, $damage)
{
  $allies = &GetAllies($player);
  $canBePrevented = CanDamageBePrevented($player, $damage, "");
  if($damage > $allies[$index+6])
  {
    if($canBePrevented) $damage -= $allies[$index+6];
    $allies[$index+6] = 0;
  }
  else
  {
    $allies[$index+6] -= $damage;
    if($canBePrevented) $damage = 0;
  }
  return $damage;
}

//NOTE: This is for ally abilities that trigger when any ally attacks (for example miragai GRANTS an ability)
function AllyAttackAbilities($attackID)
{
  global $mainPlayer, $combatChainState, $CCS_AttackUniqueID;
  $index = SearchAlliesForUniqueID($combatChainState[$CCS_AttackUniqueID], $mainPlayer);
  Restore(RestoreAmount($attackID, $mainPlayer, $index), $mainPlayer);
  $allies = &GetAllies($mainPlayer);
  for($i = 0; $i < count($allies); $i += AllyPieces()) {
    switch($allies[$i]) {
      case "rPpLwLPGaL": if($allies[$i+5] != $combatChainState[$CCS_AttackUniqueID] && SubtypeContains($attackID, "HUMAN", $mainPlayer)) AddCurrentTurnEffect("rPpLwLPGaL", $mainPlayer, "PLAY"); break;//Phalanx Captain
      case "IAkuSSnzYB"://Banner Knight
        if($allies[$i+5] != $combatChainState[$CCS_AttackUniqueID] && IsClassBonusActive($mainPlayer, "WARRIOR") && CharacterLevel($mainPlayer) >= 2) AddCurrentTurnEffect("IAkuSSnzYB", $mainPlayer, "PLAY");
        break;
      case "44vm5kt3q2"://Battlefield Spotter
        if(CharacterLevel($mainPlayer) >= 2 && $allies[$i+5] != $combatChainState[$CCS_AttackUniqueID]) AddCurrentTurnEffect("44vm5kt3q2", $mainPlayer, "PLAY");
        break;
      default: break;
    }
  }
}

function AllyPlayCardAbility($cardID, $player="")
{
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "0052542605"://Bossk
        if(DefinedTypesContains($cardID, "Event", $player)) {
          DealArcane(2, 2, "TRIGGER", "0052542605", player:$player);
        }
        break;
      case "0961039929"://Colonel Yularen
        if(AspectContains($cardID, "Command", $player)) {
          Restore(1, $player);
        }
        break;
      default: break;
    }
  }
}

function IsAlly($cardID, $player="")
{
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  return DefinedCardType($cardID) == "Unit";
}

//NOTE: This is for the actual attack abilities that allies have
function SpecificAllyAttackAbilities($attackID)
{
  global $mainPlayer, $combatChainState, $CCS_WeaponIndex;
  $allies = &GetAllies($mainPlayer);
  $i = $combatChainState[$CCS_WeaponIndex];
  switch($allies[$i]) {
    case "DsiRzt0trX"://Hasty Messenger
      PummelHit($mainPlayer, true);
      AddDecisionQueue("DRAW", $mainPlayer, "-", 1);
      break;
    case "gKVMTAeLXQ"://Blazing Direwolf
      if(IsClassBonusActive($mainPlayer, "TAMER")) DealArcane(2, 2, "PLAYCARD", "gKVMTAeLXQ", true, $mainPlayer);
      break;
    case "wFH1kBLrWh"://Arcane Elemental
      AddCurrentTurnEffect("wFH1kBLrWh", $mainPlayer);
      break;
    default: break;
  }
}

function AllyDamageTakenAbilities($player, $i)
{
  $allies = &GetAllies($player);
  switch($allies[$i]) {
    case "1Sl4Gq2OuV"://Blue slime
      $allies[$i+2] += 1;
      $allies[$i+7] += 1;
      WriteLog(CardLink($allies[$i], $allies[$i]) . " got a buff counter");
      break;
    default: break;
  }
}

function AllyTakeDamageAbilities($player, $index, $damage, $preventable)
{
  $allies = &GetAllies($player);
  $otherPlayer = ($player == 1 ? 2 : 1);
  //CR 2.1 6.4.10f If an effect states that a prevention effect can not prevent the damage of an event, the prevention effect still applies to the event but its prevention amount is not reduced. Any additional modifications to the event by the prevention effect still occur.
  $type = "-";//Add this if it ever matters
  $preventable = CanDamageBePrevented($otherPlayer, $damage, $type);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    $remove = false;
    switch($allies[$i]) {
      default: break;
    }
    if($remove) DestroyAlly($player, $i);
  }
  if($damage <= 0) $damage = 0;
  return $damage;
}

//Ally Recollection
function AllyBeginTurnEffects()
{
  global $mainPlayer;
  $mainAllies = &GetAllies($mainPlayer);
  for($i = 0; $i < count($mainAllies); $i += AllyPieces()) {
    if($mainAllies[$i+1] != 0) {
      if($mainAllies[$i+3] != 1) $mainAllies[$i+1] = 2;
    }
    $ally = new Ally("MYALLY-" . $i);
    $ally->OnFoster();
    switch($ally->CardID()) {
      case "7dedg616r0"://Freydis, Master Tactician
        if(IsClassBonusActive($mainPlayer, "RANGER")) {
          $amount = $ally->ModifyNamedCounters("TACTIC", 1);
          PlayerOpt($mainPlayer, $amount);
        }
        break;
      default: break;
    }
  }
}

function AllyBeginEndTurnEffects()
{
  global $mainPlayer, $defPlayer;
  //Reset health for all allies
  $mainAllies = &GetAllies($mainPlayer);
  for($i = count($mainAllies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    if($mainAllies[$i+1] != 0) {
      if(HasVigor($mainAllies[$i], $mainPlayer, $i)) $mainAllies[$i+1] = 2;
      $mainAllies[$i+3] = 0;
      $mainAllies[$i+8] = 1;
      $mainAllies[$i+9] = 0;//Reset distant -> normal
      if($mainAllies[$i+10] == 1) $mainAllies[$i+10] = 0;//Reset damage taken for foster mechanic
    }
    switch($mainAllies[$i])
    {
      
      default: break;
    }
  }
  $defAllies = &GetAllies($defPlayer);
  for($i = 0; $i < count($defAllies); $i += AllyPieces()) {
    if($defAllies[$i+1] != 0) {
      $defAllies[$i+8] = 1;
    }
  }
}

function AllyLevelModifiers($player)
{
  $allies = &GetAllies($player);
  $levelModifier = 0;
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    $remove = false;
    switch($allies[$i]) {
      case "qxbdXU7H4Z": if(SearchCount(SearchAllies($player, "", "BEAST")) + SearchCount(SearchAllies($player, "", "ANIMAL")) > 0) ++$levelModifier; break;
      case "yDARN8eV6B": if(IsClassBonusActive($player, "MAGE")) ++$levelModifier; break;//Tome of Knowledge
      case "izGEjxBPo9": if(SearchCount(SearchAllies($player, "", "BEAST")) + SearchCount(SearchAllies($player, "", "ANIMAL")) > 0) ++$levelModifier; break;
      case "q2okpDFJw5": if(SearchCount(SearchAllies($player, "", "BEAST")) + SearchCount(SearchAllies($player, "", "ANIMAL")) > 0) ++$levelModifier; break; //Energetic Beastbonder
      case "pnDhApDNvR": ++$levelModifier; break;//Magus Disciple
      case "1i6ierdDjq": if(SearchCount(SearchAllies($player, "", "BEAST")) + SearchCount(SearchAllies($player, "", "ANIMAL")) > 0) ++$levelModifier; break;//Flamelash Subduer
      default: break;
    }
    if($remove) DestroyAlly($player, $i);
  }
  return $levelModifier;
}

function AllyEndTurnAbilities()
{
  global $mainPlayer;
  $allies = &GetAllies($mainPlayer);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    switch($allies[$i]) {

      default: break;
    }
  }
}

function GiveAlliesHealthBonus($player, $amount)
{
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    $allies[$i+2] += $amount;
  }
}
