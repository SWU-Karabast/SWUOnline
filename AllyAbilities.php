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
  global $combatChain, $mainPlayer, $CS_NumAlliesDestroyed;
  $allies = &GetAllies($player);
  if(!$skipDestroy) {
    AllyDestroyedAbility($player, $index);
    IncrementClassState($player, $CS_NumAlliesDestroyed);
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
  //if(SearchCurrentTurnEffects("dxAEI20h8F", $player)) return 1;
  //if(PlayerHasAlly($player == 1 ? 2 : 1, "TqCo3xlf93")) return 1;//Lunete, Frostbinder Priest
  if(DefinedTypesContains($cardID, "Leader", $player)) return 2;
  switch($cardID)
  {
    case "1785627279": return 2;//Millennium Falcon
    case "4300219753"://Fett's Firespray
      return 2;
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
    default: break;
  }
  //Opponent character abilities
  $otherPlayer = ($player == 1 ? 2 : 1);
  $char = &GetPlayerCharacter($otherPlayer);
  for($i=0; $i<count($char); $i+=CharacterPieces())
  {
    switch($char[$i])
    {
      case "4626028465"://Boba Fett
        if($char[$i+1] == 2) {
          $char[$i+1] = 1;
          ReadyResource($otherPlayer);
        }
        break;
      default: break;
    }
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
    case "5575681343"://Vanguard Infantry
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to add an experience");
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
      break;
    case "9133080458"://Inferno Four
      PlayerOpt($player, 2);
      break;
    case "1047592361"://Ruthless Raider
      DealArcane(2, 1, "PLAYCARD", $cardID, player:$player);
      DealArcane(2, 2, "PLAYCARD", $cardID, player:$player);
      break;
    case "0949648290"://Greedo
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose if you want to discard a card to Greedo");
      AddDecisionQueue("YESNO", $player, "-", 1);
      AddDecisionQueue("NOPASS", $player, "-");
      AddDecisionQueue("PASSPARAMETER", $player, "1", 1);
      AddDecisionQueue("OP", $player, "MILL", 1);
      AddDecisionQueue("NONECARDDEFINEDTYPEORPASS", $player, "Unit", 1);
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:arena=Ground", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2", 1);
      break;
    case "3232845719"://K-2SO
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a mode for K-2SO");
      AddDecisionQueue("MULTICHOOSETEXT", $player, "1-Deal 3 damage,Discard-1");
      AddDecisionQueue("SHOWMODES", $player, $cardID, 1);
      AddDecisionQueue("MODAL", $player, "K2SO", 1);
      break;
    case "8333567388"://Distant Patroller
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give a shield");
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:aspect=Vigilance");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "ADDSHIELD", 1);
      break;
    case "4786320542"://Obi-Wan Kenobi
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to add an experience");
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
      AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
      AddDecisionQueue("SPECIFICCARD", $player, "OBIWANKENOBI", 1);
      break;
    default: break;
  }
  //Abilities that trigger when a different ally is destroyed
  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    if($i == $index) continue;
    switch($allies[$i]) {
      case "9353672706"://General Krell
        Draw($player);
        WriteLog("Drew a card from General Krell");
        break;
      default: break;
    }
  }
  //Abilities that trigger when an opposing ally is destroyed
  $otherPlayer = ($player == 1 ? 2 : 1);
  $allies = &GetAllies($otherPlayer);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    switch($allies[$i]) {
      case "1664771721"://Gideon Hask
        AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to add an experience");
        AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $otherPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $otherPlayer, "ADDEXPERIENCE", 1);
        break;
      case "b0dbca5c05"://Iden Versio
        Restore(1, $otherPlayer);
        break;
      case "2649829005"://Agent Kallus
        if($allies[$i+8] > 0) {
          --$allies[$i+8];
          Draw($otherPlayer);
        }
        break;
      default: break;
    }
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
    case "9647945674"://Zeb Orrelios
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 4 damage to");
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:arena=Ground");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,4", 1);
      break;
    default: break;
  }
}

function AllyBeginRoundAbilities($player)
{
  global $CS_NumMaterializations;
  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    switch($allies[$i]) {
      case "3401690666"://Relentless
        $otherPlayer = ($player == 1 ? 2 : 1);
        AddCurrentTurnEffect("3401690666", $otherPlayer, from:"PLAY");
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
  global $mainPlayer, $combatChainState, $CCS_AttackUniqueID, $defPlayer;
  $index = SearchAlliesForUniqueID($combatChainState[$CCS_AttackUniqueID], $mainPlayer);
  $restoreAmount = RestoreAmount($attackID, $mainPlayer, $index);
  if($restoreAmount > 0) Restore($restoreAmount, $mainPlayer);
  $allies = &GetAllies($mainPlayer);
  switch($attackID) {
    default: break;
  }
  for($i = 0; $i < count($allies); $i += AllyPieces()) {
    switch($allies[$i]) {
      case "20f21b4948"://Jyn Erso
        AddCurrentTurnEffect("20f21b4948", $defPlayer);
        break;
      default: break;
    }
  }
}

function AllyAttackedAbility($attackTarget, $index) {
  global $mainPlayer, $defPlayer;
  $ally = new Ally("MYALLY-" . $index, $defPlayer);
  $subcards = $ally->GetSubcards();
  for($i=0; $i<count($subcards); ++$i) {
    switch($subcards[$i]) {
      case "1323728003"://Electrostaff
        AddCurrentTurnEffect("1323728003", $mainPlayer, from:"PLAY");
        break;
      default: break;
    }
  }
  switch($attackTarget) {
    case "8918765832"://Chewbacca
      $ally = new Ally("MYALLY-" . $index, $defPlayer);
      $ally->Ready();
      break;
    default: break;
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
      case "5907868016"://Fighters for Freedom
        if(AspectContains($cardID, "Aggression", $player)) {
          //TODO: Fix the target
          DealArcane(1, 2, "TRIGGER", "5907868016", player:$player);
        }
        break;
      default: break;
    }
  }
  $otherPlayer = ($player == 1 ? 2 : 1);
  $allies = &GetAllies($otherPlayer);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    switch($allies[$i])
    {
      case "5555846790"://Saw Gerrera
        if(DefinedTypesContains($cardID, "Event", $player)) DealArcane(2, 1, "TRIGGER", "5555846790", player:$otherPlayer);
        break;
      default: break;
    }
  }
}

function IsAlly($cardID, $player="")
{
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  return DefinedTypesContains($cardID, "Unit", $player);
}

//NOTE: This is for the actual attack abilities that allies have
function SpecificAllyAttackAbilities($attackID)
{
  global $mainPlayer, $defPlayer, $combatChainState, $CCS_WeaponIndex;
  $attackerAlly = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
  $subcards = $attackerAlly->GetSubcards();
  for($i=0; $i<count($subcards); ++$i) {
    switch($subcards[$i]) {
      case "7280213969"://Smuggling Compartment
        ReadyResource($mainPlayer);
        break;
      case "3987987905"://Hardpoint Heavy Blaster
        if(GetAttackTarget() != "THEIRCHAR-0") {
          AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 2 damage to");
          AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:arena=" . CardArenas($attackID));
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2", 1);
        }
        break;
      case "0160548661"://Fallen Lightsaber
        if(TraitContains($attackID, "Force", $mainPlayer)) {
          WriteLog("Fallen Lightsaber deals 1 damage to all defending ground units");
          DamagePlayerAllies($defPlayer, 1, "0160548661", "DAMAGE", arena:"Ground");
        }
        break;
      default: break;
    }
  }
  $allies = &GetAllies($mainPlayer);
  $i = $combatChainState[$CCS_WeaponIndex];
  switch($allies[$i]) {
    case "1662196707"://Kanan Jarrus
      $amount = SearchCount(SearchAllies($mainPlayer, trait:"Spectre"));
      $cardsMilled = Mill($defPlayer, $amount);
      $cardArr = explode(",", $cardsMilled);
      $aspectArr = [];
      for($j = 0; $j < count($cardArr); ++$j) {
        $aspects = explode(",", CardAspects($cardArr[$j]));
        for($k=0; $k<count($aspects); ++$k) {
          if($aspects[$k] == "") break;
          $aspectArr[$aspects[$k]] = 1;
        }
      }
      Restore(count($aspectArr), $mainPlayer);
      break;
    case "0ca1902a46"://Darth Vader
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2", 1);
      break;
    case "0dcb77795c"://Luke Skywalker
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDSHIELD", 1);
      break;
    case "59cd013a2d"://Grand Moff Tarkin
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Imperial");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDEXPERIENCE", 1);
      break;
    case "51e8757e4c"://Sabine Wren
      DealArcane(1, 1, "PLAYCARD", "51e8757e4c");
      break;
    case "8395007579"://Fifth Brother
      $ally = new Ally("MYALLY-" . $i, $mainPlayer);
      $ally->DealDamage(1);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage to");
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:arena=Ground");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1", 1);
      break;
    case "6827598372"://Grand Inquisitor
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage to");
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:maxPower=3");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "READY", 1);
      break;
    case "80df3928eb"://Hera Sykulla
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDEXPERIENCE", 1);
      break;
    case "4156799805"://Boba Fett
      $target = GetAttackTarget();
      $ally = new Ally($target, $mainPlayer);
      if($ally->IsExhausted()) {
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Did the unit enter play this turn?");
        AddDecisionQueue("YESNO", $mainPlayer, "-");
        AddDecisionQueue("YESPASS", $mainPlayer, "-");
        AddDecisionQueue("PASSPARAMETER", $mainPlayer, $target, 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,3", 1);
      }
      break;
    case "6208347478"://Chopper
      $card = Mill($defPlayer, 1);
      if(DefinedTypesContains($card, "Event", $defPlayer)) ExhaustResource($defPlayer);
      break;
    case "3646264648"://Sabine Wren
      DealArcane(1, 3, "PLAYCARD", "3646264648");
      break;
    case "6432884726"://Steadfast Battalion
      if($hasLeader($mainPlayer)) {
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give +2/+2");
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "6432884726,HAND", 1);
      }
      break;
    case "5e90bd91b0"://Han Solo
      $deck = new Deck($mainPlayer);
      $card = $deck->Top(remove:true);
      AddResources($card, $mainPlayer, "DECK", "DOWN");
      AddCurrentTurnEffect("5e90bd91b0", $mainPlayer);
      break;
    case "6c5b96c7ef"://Emperor Palpatine
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose an ally to destroy");
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DESTROY", 1);
      AddDecisionQueue("DRAW", $mainPlayer, "-", 1);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose an ally to deal 1 damage");
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1", 1);
      break;
    case "5464125379"://Strafing Gunship
      $target = GetAttackTarget();
      $ally = new Ally($target, $defPlayer);
      if(CardArenas($ally->CardID()) == "Ground") {
        AddCurrentTurnEffect("5464125379", $defPlayer, from:"PLAY");
      }
      break;
    default: break;
  }
}

function AllyDamageTakenAbilities($player, $i)
{
  $allies = &GetAllies($player);
  switch($allies[$i]) {
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
      case "1785627279"://Millennium Falcon
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Do you want to pay 1 to keep Millennium Falcon running?");
        AddDecisionQueue("YESNO", $mainPlayer, "-", 0, 1);
        AddDecisionQueue("NOPASS", $mainPlayer, "-");
        AddDecisionQueue("PASSPARAMETER", $mainPlayer, "1", 1);
        AddDecisionQueue("PAYRESOURCES", $mainPlayer, "<-", 1);
        AddDecisionQueue("ELSE", $mainPlayer, "-");
        AddDecisionQueue("PASSPARAMETER", $mainPlayer, "MYALLY-" . $i, 1);
        AddDecisionQueue("MZOP", $mainPlayer, "BOUNCE", 1);
        break;
      case "d1a7b76ae7"://Chirrut Imwe
        $ally = new Ally("MYALLY-" . $i, $mainPlayer);
        if($ally->Health() <= 0) DestroyAlly($mainPlayer, $i);
        break;
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
