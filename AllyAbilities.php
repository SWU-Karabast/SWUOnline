<?php

function PlayAlly($cardID, $player, $subCards = "-", $from="-")
{
  $allies = &GetAllies($player);
  if(count($allies) < AllyPieces()) $allies = [];
  array_push($allies, $cardID);
  array_push($allies, AllyEntersPlayState($cardID, $player, $from));
  array_push($allies, AllyHealth($cardID, $player));
  array_push($allies, 0); //Frozen
  array_push($allies, $subCards); //Subcards
  array_push($allies, GetUniqueId()); //Unique ID
  array_push($allies, AllyEnduranceCounters($cardID)); //Endurance Counters
  array_push($allies, 0); //Buff Counters
  array_push($allies, 1); //Ability/effect uses
  array_push($allies, 0); //Round health modifier
  array_push($allies, 0); //Times attacked
  array_push($allies, $player); //Owner
  array_push($allies, 0); //Turns in play
  $index = count($allies) - AllyPieces();
  CurrentEffectAllyEntersPlay($player, $index);
  AllyEntersPlayAbilities($player);
  //Health modifiers this has that applies to other units
  if(AllyHasStaticHealthModifier($cardID)) {
    for($i = 0; $i < count($allies); $i += AllyPieces()) {
      $allies[$i+2] += AllyStaticHealthModifier($allies[$i], $i, $player, $cardID, $index);
    }
  }
  //Health modifiers other units have that apply to this
  for($i=count($allies)-AllyPieces(); $i>=0; $i-=AllyPieces()) {
    if(AllyHasStaticHealthModifier($allies[$i])) {
      $allies[$index+2] += AllyStaticHealthModifier($cardID, $index, $player, $allies[$i], $i);
    }
  }
  $allies[$index+2] += CharacterStaticHealthModifiers($cardID, $index, $player);
  CheckUnique($cardID, $player);
  return $index;
}

function CheckUnique($cardID, $player) {
  if(CardIsUnique($cardID) && SearchCount(SearchAlliesForCard($player, $cardID)) > 1) {
    PrependDecisionQueue("MZDESTROY", $player, "-", 1);
    PrependDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
    PrependDecisionQueue("SETDQCONTEXT", $player, "You have two of this unique unit; choose one to destroy");
    PrependDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:cardID=" . $cardID);
  }
}

function AllyHasStaticHealthModifier($cardID)
{
  switch($cardID)
  {
    case "1557302740"://General Veers
    case "9799982630"://General Dodonna
    case "4339330745"://Wedge Antilles
    case "9097316363"://Emperor Palpatine
    case "6c5b96c7ef"://Emperor Palpatine
      return true;
    default: return false;
  }
}

function AllyStaticHealthModifier($cardID, $index, $player, $myCardID, $myIndex)
{
  switch($myCardID)
  {
    case "1557302740"://General Veers
      if($index != $myIndex && TraitContains($cardID, "Imperial", $player)) return 1;
      break;
    case "9799982630"://General Dodonna
      if($index != $myIndex && TraitContains($cardID, "Rebel", $player)) return 1;
      break;
    case "4339330745"://Wedge Antilles
      if($index != $myIndex && TraitContains($cardID, "Vehicle", $player)) return 1;
      break;
    case "9097316363"://Emperor Palpatine
    case "6c5b96c7ef"://Emperor Palpatine
      if($cardID == "1780978508") { //Royal Guard
        $isEmperorPalpatineLeader = false;
        $character = &GetPlayerCharacter($player);
        for($i=0; $i<count($character); $i+=CharacterPieces()) {
          if($character[$i] == "5784497124") { //Emperor Palpatine
            $isEmperorPalpatineLeader = true;
            break;
          }
        }
        return $isEmperorPalpatineLeader ? 0 : 1;
      }
      break;
    default: break;
  }
  return 0;
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
  global $combatChain, $mainPlayer, $defPlayer, $CS_NumAlliesDestroyed, $CS_NumLeftPlay;
  $allies = &GetAllies($player);
  $cardID = $allies[$index];
  if(!$skipDestroy) {
    AllyDestroyedAbility($player, $index, $fromCombat);
    CollectBounties($player, $index);
    IncrementClassState($player, $CS_NumAlliesDestroyed);
  }
  if(!IsLeader($cardID, $player)) IncrementClassState($player, $CS_NumLeftPlay);
  AllyLeavesPlayAbility($player, $index);
  $ally = new Ally("MYALLY-" . $index, $player);
  $subcards = $ally->GetSubcards();
  for($i=0; $i<count($subcards); ++$i) {
    if($subcards[$i] == "8752877738" || $subcards[$i] == "2007868442") continue;
    AddGraveyard($subcards[$i], $player, "PLAY");
  }
  $owner = $allies[$index+11];
  if(!$skipDestroy) {
    if(DefinedTypesContains($cardID, "Leader", $player)) ;//If it's a leader it doesn't go in the discard
    else if($cardID == "8954587682") AddResources($cardID, $player, "PLAY", "DOWN");
    else AddGraveyard($cardID, $owner, "PLAY");
  }
  for($j = $index + AllyPieces() - 1; $j >= $index; --$j) unset($allies[$j]);
  $allies = array_values($allies);
  if(AllyHasStaticHealthModifier($cardID)) {
    for($i = count($allies)-AllyPieces(); $i >= 0; $i -= AllyPieces()) {
      //myIndex is -1 because the unit is destroyed
      $allies[$i+2] -= AllyStaticHealthModifier($allies[$i], $i, $player, $cardID, -1);
      if($allies[$i+2] <= 0) DestroyAlly($player, $i);
    }
  }
  if($player == $mainPlayer) UpdateAttacker();
  else UpdateAttackTarget();
  return $cardID;
}

function AllyTakeControl($player, $index) {
  global $currentTurnEffects;
  $otherPlayer = $player == 1 ? 2 : 1;
  $myAllies = &GetAllies($player);
  $theirAllies = &GetAllies($otherPlayer);
  $cardID = $theirAllies[$index];
  $uniqueID = $theirAllies[$index+5];
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i+1] != $otherPlayer) continue;
    if($currentTurnEffects[$i+2] == -1 || $currentTurnEffects[$i+2] != $uniqueID) continue;
    $currentTurnEffects[$i+1] = $player;
  }
  for($i=$index; $i<$index+AllyPieces(); ++$i) {
    array_push($myAllies, $theirAllies[$i]);
  }
  RemoveAlly($otherPlayer, $index);
  CheckUnique($cardID, $player);
  return $uniqueID;
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
      
      default: break;
    }
  }
}

function AllyPlayableExhausted($cardID) {
  switch($cardID) {
    case "4300219753"://Fett's Firespray
    case "2471223947"://Frontline Shuttle
    case "1885628519"://Crosshair
      return true;
    default: return false;
  }
}

function AllyDoesAbilityExhaust($cardID, $abilityIndex) {
  switch($cardID) {
    case "4300219753"://Fett's Firespray
      return $abilityIndex == 1;
    case "2471223947"://Frontline Shuttle
      return $abilityIndex == 1;
    case "1885628519"://Crosshair
      return $abilityIndex == 1 || $abilityIndex == 2;
    default: return true;
  }
}

function AllyHealth($cardID, $playerID="")
{
  $health = CardHP($cardID);
  switch($cardID)
  {
    case "7648077180"://97th Legion
      $health += NumResources($playerID);
      break;
    default: break;
  }
  return $health;
}

function AllyLeavesPlayAbility($player, $index)
{
  $allies = &GetAllies($player);
  $cardID = $allies[$index];
  $leaderUndeployed = LeaderUndeployed($cardID);
  if($leaderUndeployed != "") {
    AddCharacter($leaderUndeployed, $player, counters:1, status:1);
  }
  switch($cardID)
  {
    case "3401690666"://Relentless
      $otherPlayer = ($player == 1 ? 2 : 1);
      SearchCurrentTurnEffects("3401690666", $otherPlayer, remove:true);
      break;
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
        if($char[$i+1] == 2 && NumResourcesAvailable($otherPlayer) < NumResources($otherPlayer)) {
          $char[$i+1] = 1;
          ReadyResource($otherPlayer);
        }
        break;
      default: break;
    }
  }
}

function AllyDestroyedAbility($player, $index, $fromCombat)
{
  global $mainPlayer, $initiativePlayer;
  $allies = &GetAllies($player);
  $cardID = $allies[$index];
  OnKillAbility($fromCombat);
  $destroyedAlly = new Ally("MYALLY-" . $index, $player);
  if(!$destroyedAlly->LostAbilities()) {
    switch($cardID) {
      case "4405415770"://Yoda, Old Master
        WriteLog("Player $player drew a card from Yoda, Old Master");
        Draw($player);
        break;
      case "8429598559"://Black One
        BlackOne($player);
        break;
      case "9996676854"://Admiral Motti
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:aspect=Villainy");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to ready");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "READY", 1);
        break;
      case "7517208605"://Star Wing Scout
        if($player == $initiativePlayer) { Draw($player); Draw($player); }
        break;
      case "5575681343"://Vanguard Infantry
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to add an experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
        break;
      case "9133080458"://Inferno Four
        PlayerOpt($player, 2);
        break;
      case "1047592361"://Ruthless Raider
        $otherPlayer = $player == 1 ? 2 : 1;
        DealDamageAsync($otherPlayer, 2, "DAMAGE", "1047592361");
        AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 2 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2", 1);
        break;
      case "0949648290"://Greedo
        $deck = &GetDeck($player);
        if(count($deck) > 0) {
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose if you want to discard a card to Greedo");
          AddDecisionQueue("YESNO", $player, "-");
          AddDecisionQueue("NOPASS", $player, "-");
          AddDecisionQueue("PASSPARAMETER", $player, "1", 1);
          AddDecisionQueue("OP", $player, "MILL", 1);
          AddDecisionQueue("NONECARDDEFINEDTYPEORPASS", $player, "Unit", 1);
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:arena=Ground&THEIRALLY:arena=Ground", 1);
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 2 damage");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2", 1);
        }
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
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to add two experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
        AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
        AddDecisionQueue("SPECIFICCARD", $player, "OBIWANKENOBI", 1);
        break;
      default: break;
    }
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

function CollectBounties($player, $index) {
  global $currentTurnEffects;
  $ally = new Ally("MYALLY-" . $index, $player);
  $opponent = $player == 1 ? 2 : 1;
  //Current turn effect bounties
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "1090660242"://The Client
        Restore(5, $opponent);
        break;
      default: break;
    }
  }
  //Subcard bounties
  $subcards = $ally->GetSubcards();
  for($i=0; $i<count($subcards); ++$i)
  {
    switch($subcards[$i]) {
      case "2178538979"://Price on Your Head
        AddTopDeckAsResource($opponent);
        break;
      case "2740761445"://Guild Target
        $damage = CardIsUnique($ally->CardID()) ? 3 : 2;
        DealDamageAsync($player, $damage, "DAMAGE", "2740761445");
        break;
      case "4117365450"://Wanted
        ReadyResource($opponent);
        ReadyResource($opponent);
        break;
      case "4282425335"://Top Target
        $amount = CardIsUnique($ally->CardID()) ? 6 : 4;
        Restore($amount, $opponent);
        break;
      case "3074091930"://Rich Reward
        AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY");
        AddDecisionQueue("OP", $opponent, "MZTONORMALINDICES");
        AddDecisionQueue("PREPENDLASTRESULT", $opponent, "3-", 1);
        AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose up to 2 units to give experience");
        AddDecisionQueue("MULTICHOOSEUNIT", $opponent, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $opponent, "MULTIGIVEEXPERIENCE", 1);
        break;
      case "1780014071"://Public Enemy
        AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose a unit to give a shield");
        AddDecisionQueue("CHOOSEMULTIZONE", $opponent, "<-", 1);
        AddDecisionQueue("MZOP", $opponent, "ADDSHIELD", 1);
        break;
      default: break;
    }
  }
  switch($ally->CardID()) {
    case "6135081953"://Doctor Evazan
      for($i=0; $i<12; ++$i) {
        ReadyResource($opponent);
      }
      break;
    case "6878039039"://Hylobon Enforcer
      Draw($opponent);
      break;
    case "9503028597"://Clone Deserter
      Draw($opponent);
      break;
    case "9108611319"://Cartel Turncoat
      Draw($opponent);
      break;
    default: break;
  }
}

function OnKillAbility($fromCombat)
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
      case "02199f9f1e"://Grand Admiral Thrawn
        $myDeck = &GetDeck($player);
        $theirDeck = &GetDeck($player == 1 ? 2 : 1);
        AddDecisionQueue("SETDQCONTEXT", $player, "The top of your deck is " . CardLink($myDeck[0], $myDeck[0]) . " and the top of their deck is " . CardLink($theirDeck[0], $theirDeck[0]));
        AddDecisionQueue("OK", $player, "-");
        break;
      default: break;
    }
  }
}

function AllyCanBeAttackTarget($player, $index, $cardID)
{
  switch($cardID)
  {
    case "3646264648"://Sabine Wren
      $allies = &GetAllies($player);
      $aspectArr = [];
      for($i=0; $i<count($allies); $i+=AllyPieces())
      {
        if($i == $index) continue;
        $aspects = explode(",", CardAspects($allies[$i]));
        for($j=0; $j<count($aspects); ++$j) {
          $aspectArr[$aspects[$j]] = 1;
        }
      }
      return count($aspectArr) < 3;
    default: return true;
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

function AllyPlayCardAbility($cardID, $player="", $reportMode=false)
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
          if($reportMode) return true;
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 2 damage to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2", 1);
        }
        break;
      case "0961039929"://Colonel Yularen
        if(DefinedTypesContains($cardID, "Unit", $player) && AspectContains($cardID, "Command", $player)) {
          if($reportMode) return true;
          Restore(1, $player);
        }
        break;
      case "5907868016"://Fighters for Freedom
        if($i != LastAllyIndex($player) && AspectContains($cardID, "Aggression", $player)) {
          if($reportMode) return true;
          $otherPlayer = ($player == 1 ? 2 : 1);
          DealDamageAsync($otherPlayer, 1, "DAMAGE", "5907868016");
          WriteLog(CardLink("5907868016", "5907868016") . " is dealing 1 damage.");
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
        if(DefinedTypesContains($cardID, "Event", $player)) {
          if($reportMode) return true;
          DealDamageAsync($player, 2, "DAMAGE", "5555846790");
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
  return DefinedTypesContains($cardID, "Unit", $player) && LeaderUnit($cardID) == "";
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
      case "8495694166"://Jedi Lightsaber
        if(TraitContains($attackID, "Force", $mainPlayer) && IsAllyAttackTarget()) {
          WriteLog("Jedi Lightsaber gives the defending unit -2/-2");
          $target = GetAttackTarget();
          $ally = new Ally($target);
          $ally->AddRoundHealthModifier(-2);
          AddCurrentTurnEffect("8495694166", $defPlayer, from:"PLAY");
        }
        break;
      case "3525325147"://Vambrace Grappleshot
        if(IsAllyAttackTarget()) {
          WriteLog("Vambrace Grappleshot exhausts the defender");
          $target = GetAttackTarget();
          $ally = new Ally($target);
          $ally->Exhaust();
        }
        break;
      default: break;
    }
  }
  if($attackerAlly->LostAbilities()) return;
  $allies = &GetAllies($mainPlayer);
  $i = $combatChainState[$CCS_WeaponIndex];
  switch($allies[$i]) {
    case "6931439330"://The Ghost
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Spectre");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give a shield");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDSHIELD", 1);
      break;
    case "0256267292"://Benthic 'Two Tubes'
      $ally = new Ally("MYALLY-" . $i, $mainPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:aspect=Aggression");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $i);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give Raid 2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "0256267292,HAND", 1);
      break;
    case "02199f9f1e"://Grand Admiral Thrawn
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose player to reveal top of deck");
      AddDecisionQueue("BUTTONINPUT", $mainPlayer, "Yourself,Opponent");
      AddDecisionQueue("SPECIFICCARD", $mainPlayer, "GRANDADMIRALTHRAWN", 1);
      break;
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
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 2 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2", 1);
      break;
    case "0dcb77795c"://Luke Skywalker
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give a shield");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDSHIELD", 1);
      break;
    case "59cd013a2d"://Grand Moff Tarkin
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Imperial");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give experience");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDEXPERIENCE", 1);
      break;
    case "5449704164"://2-1B Surgical Droid
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to heal 2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "HEALALLY,2", 1);
      break;
    case "51e8757e4c"://Sabine Wren
      DealDamageAsync($defPlayer, 1, "DAMAGE", "51e8757e4c");
      break;
    case "8395007579"://Fifth Brother
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Do you want to deal 1 damage to Fifth Brother?");
      AddDecisionQueue("YESNO", $mainPlayer, "-");
      AddDecisionQueue("NOPASS", $mainPlayer, "-");
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, "MYALLY-" . $i, 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1", 1);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground", 1);
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1", 1);
      break;
    case "6827598372"://Grand Inquisitor
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:maxAttack=3");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "READY", 1);
      break;
    case "80df3928eb"://Hera Syndulla
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("MZFILTER", $mainPlayer, "unique=0");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDEXPERIENCE", 1);
      break;
    case "4156799805"://Boba Fett
      if(IsAllyAttackTarget()) {
        $target = GetAttackTarget();
        $ally = new Ally($target, $defPlayer);
        if($ally->IsExhausted() && $ally->TurnsInPlay() > 0) {
          AddDecisionQueue("PASSPARAMETER", $mainPlayer, $target, 1);
          AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,3", 1);
        }
      }
      break;
    case "6208347478"://Chopper
      $card = Mill($defPlayer, 1);
      if(DefinedTypesContains($card, "Event", $defPlayer)) ExhaustResource($defPlayer);
      break;
    case "3646264648"://Sabine Wren
      $attackTarget = GetAttackTarget();
      $options = $attackTarget == "THEIRCHAR-0" ? "THEIRCHAR-0" : "THEIRCHAR-0," . $attackTarget;
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose something to deal 1 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, $options, 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1", 1);
      break;
    case "6432884726"://Steadfast Battalion
      if(HasLeader($mainPlayer)) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give +2/+2");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "ADDHEALTH,2", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "6432884726,PLAY", 1);
      }
      break;
    case "5e90bd91b0"://Han Solo
      $deck = new Deck($mainPlayer);
      $card = $deck->Top(remove:true);
      AddResources($card, $mainPlayer, "DECK", "DOWN");
      AddNextTurnEffect("5e90bd91b0", $mainPlayer);
      break;
    case "8240629990"://Avenger
      $otherPlayer = $mainPlayer == 1 ? 2 : 1;
      MZChooseAndDestroy($otherPlayer, "MYALLY", filter:"definedType=Leader", context:"Choose a unit to destroy");
      break;
    case "6c5b96c7ef"://Emperor Palpatine
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to destroy");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DESTROY", 1);
      AddDecisionQueue("DRAW", $mainPlayer, "-", 1);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose an ally to deal 1 damage", 1);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY", 1);
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
    case "8190373087"://Gentle Giant
      $power = $attackerAlly->CurrentPower();
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to heal " . $power);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "HEALALLY," . $power, 1);
      break;
    default: break;
  }
}

function AllyHitEffects() {
  global $mainPlayer;
  $allies = &GetAllies($mainPlayer);
  for($i=0; $i<count($allies); $i+=AllyPieces()) {
    switch($allies[$i]) {
      case "3c60596a7a"://Cassian Andor
        $ally = new Ally("MYALLY-" . $i, $mainPlayer);
        if($ally->NumUses() > 0) {
          $targetArr = explode("-", GetAttackTarget());
          if($targetArr[0] == "THEIRCHAR") {
            $ally->ModifyUses(-1);
            Draw($mainPlayer);
          }
        }
        break;
      default: break;
    }
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
      $mainAllies[$i+10] = 0;//Reset times attacked
      ++$mainAllies[$i+12];//Increase number of turns in play
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
      $defAllies[$i+10] = 0;//Reset times attacked
      ++$defAllies[$i+12];//Increase number of turns in play
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

function AllyEndTurnAbilities($player)
{
  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    $ally = new Ally("MYALLY-" . $i, $player);
    switch($allies[$i]) {
      case "1785627279"://Millennium Falcon
        AddDecisionQueue("SETDQCONTEXT", $player, "Do you want to pay 1 to keep Millennium Falcon running?");
        AddDecisionQueue("YESNO", $player, "-", 0, 1);
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("PASSPARAMETER", $player, "1", 1);
        AddDecisionQueue("PAYRESOURCES", $player, "<-", 1);
        AddDecisionQueue("ELSE", $player, "-");
        AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $i, 1);
        AddDecisionQueue("MZOP", $player, "BOUNCE", 1);
        AddDecisionQueue("WRITELOG", $player, "Millennium Falcon bounced back to hand", 1);
        break;
      case "d1a7b76ae7"://Chirrut Imwe
        if($ally->Health() <= 0) DestroyAlly($player, $i);
        break;
      default: break;
    }
    $ally->EndRound();
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
