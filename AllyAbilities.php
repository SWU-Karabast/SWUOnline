<?php

function PlayAlly($cardID, $player, $subCards = "-", $from = "-", $owner = null)
{
  $allies = &GetAllies($player);
  if(count($allies) < AllyPieces()) $allies = [];
  $allies[] = $cardID;
  $allies[] = AllyEntersPlayState($cardID, $player, $from);
  $allies[] = 0; //Damage
  $allies[] = 0; //Frozen
  $allies[] = $subCards; //Subcards
  $allies[] = GetUniqueId(); //Unique ID
  $allies[] = AllyEnduranceCounters($cardID); //Endurance Counters
  $allies[] = 0; //Buff Counters
  $allies[] = 1; //Ability/effect uses
  $allies[] = 0; //Round health modifier
  $allies[] = 0; //Times attacked
  $allies[] = $owner ?? $player; //Owner
  $allies[] = 0; //Turns in play
  $index = count($allies) - AllyPieces();
  CurrentEffectAllyEntersPlay($player, $index);
  AllyEntersPlayAbilities($player);
  $otherPlayer = $player == 1 ? 2 : 1;
  $theirAllies = &GetAllies($otherPlayer);

  if(AllyHasStaticHealthModifier($cardID)) {
    CheckHealthAllAllies($player);
  }
  CheckUnique($cardID, $player);
  return $index;
}

function CheckHealthAllAllies($player)
{
  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    if (!isset($allies[$i])) continue;
    $ally = new Ally("MYALLY-" . $i, $player);
    $ally->DefeatIfNoRemainingHP();
  }
  $otherPlayer = $player == 1 ? 2 : 1;
  $theirAllies = &GetAllies($otherPlayer);
  for($i = count($theirAllies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    if (!isset($theirAllies[$i])) continue;
    $ally = new Ally("THEIRALLY-" . $i, $otherPlayer);
    $ally->DefeatIfNoRemainingHP();
  }
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
    case "4511413808"://Follower of the Way
    case "3731235174"://Supreme Leader Snoke
    case "6097248635"://4-LOM
    case "1690726274"://Zuckuss
      return true;
    default: return false;
  }
}

function AllyStaticHealthModifier($cardID, $index, $player, $myCardID, $myIndex, $myPlayer)
{
  switch($myCardID)
  {
    case "1557302740"://General Veers
      if($index != $myIndex && $player == $myPlayer && TraitContains($cardID, "Imperial", $player)) return 1;
      break;
    case "9799982630"://General Dodonna
      if($index != $myIndex && $player == $myPlayer && TraitContains($cardID, "Rebel", $player)) return 1;
      break;
    case "4339330745"://Wedge Antilles
      if($index != $myIndex && $player == $myPlayer && TraitContains($cardID, "Vehicle", $player)) return 1;
      break;
    case "9097316363"://Emperor Palpatine
    case "6c5b96c7ef"://Emperor Palpatine
      if($cardID == "1780978508" && $player == $myPlayer) { //Royal Guard
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
    case "4511413808"://Follower of the Way
      if($index == $myIndex && $player == $myPlayer) {
        $ally = new Ally("MYALLY-" . $index, $player);
        if($ally->NumUpgrades() > 0) return 1;
      }
      break;
    case "3731235174"://Supreme Leader Snoke
      return $player != $myPlayer && !IsLeader($cardID, $player) ? -2 : 0;
    case "6097248635"://4-LOM
      return ($player == $myPlayer && CardTitle($cardID) == "Zuckuss") ? 1 : 0;
    case "1690726274"://Zuckuss
      return ($player == $myPlayer && CardTitle($cardID) == "4-LOM") ? 1 : 0;
    default: break;
  }
  return 0;
}

// Health update: Leaving this for now. Not sure it is used and may be removed in a more
// comprehensive cleanup to ensure everything is going through the ally class method.
function DealAllyDamage($targetPlayer, $index, $damage, $type="")
{
  $allies = &GetAllies($targetPlayer);
  if($allies[$index+6] > 0) {
    $damage -= 3;
    if($damage < 0) $damage = 0;
    --$allies[$index+6];
  }
  $allies[$index+2] -= $damage;
  if($allies[$index+2] <= 0) DestroyAlly($targetPlayer, $index, fromCombat: $type == "COMBAT");
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
  $owner = $allies[$index+11];
  $otherPlayer = $player == 1 ? 2 : 1;
  $discardPileModifier = "-";
  if(!$skipDestroy) {
    AllyDestroyedAbility($player, $index, $fromCombat);
    CollectBounties($player, $index);
    IncrementClassState($player, $CS_NumAlliesDestroyed);
  }
  IncrementClassState($player, $CS_NumLeftPlay);
  AllyLeavesPlayAbility($player, $index);
  $ally = new Ally("MYALLY-" . $index, $player);
  $upgrades = $ally->GetUpgrades(true);
  for($i=0; $i<count($upgrades); $i+=SubcardPieces()) {
    if($upgrades[$i] == "8752877738" || $upgrades[$i] == "2007868442") continue;
    if($upgrades[$i] == "6911505367") $discardPileModifier = "TTFREE";//Second Chance
    AddGraveyard($upgrades[$i], $upgrades[$i+1], "PLAY");
  }
  $captives = $ally->GetCaptives(true);
  if(!$skipDestroy) {
    if(DefinedTypesContains($cardID, "Leader", $player)) ;//If it's a leader it doesn't go in the discard
    else if($cardID == "8954587682" && !$ally->LostAbilities()) AddResources($cardID, $player, "PLAY", "DOWN");//Superlaser Technician
    else if($cardID == "7204838421" && !$ally->LostAbilities()) AddResources($cardID, $player, "PLAY", "DOWN");//Enterprising Lackeys
    else AddGraveyard($cardID, $owner, "PLAY", $discardPileModifier);
  }
  for($j = $index + AllyPieces() - 1; $j >= $index; --$j) unset($allies[$j]);
  $allies = array_values($allies);
  for($i=0; $i<count($captives); $i+=SubcardPieces()) {
    PlayAlly($captives[$i], $captives[$i+1], from:"CAPTIVE");
  }
  if(AllyHasStaticHealthModifier($cardID)) {
    CheckHealthAllAllies($player);
  }
  if($player == $mainPlayer) UpdateAttacker();
  else UpdateAttackTarget();
  return $cardID;
}

function AllyTakeControl($player, $index) {
  global $currentTurnEffects;
  if($index == "") return -1;
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
    $myAllies[] = $theirAllies[$i];
  }
  for ($i=$index+AllyPieces()-1; $i>=$index; $i--) {
    unset($theirAllies[$i]);
  }
  CheckHealthAllAllies($otherPlayer);
  CheckHealthAllAllies($player);
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
    case "040a3e81f3"://Lando Leader Unit
    case "2b13cefced"://Fennec Shand Unit
    case "a742dea1f1"://Han Solo Red Unit
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
    case "040a3e81f3"://Lando Leader Unit
      return $abilityIndex == 1;
    case "2b13cefced"://Fennec Shand Unit
      return $abilityIndex == 1;
    case "a742dea1f1"://Han Solo Red Unit
      return $abilityIndex == 1;
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
    case "4002861992"://DJ (Blatant Thief)
      $DJTurnEffect = &GetCurrentTurnEffects("4002861992", $player, remove: true);
      if ($DJTurnEffect !== false) {
        $cardIndex = &GetCardIndexInResources($player, $DJTurnEffect[2]);
        if ($cardIndex >= 0) {
          $otherPlayer = $player == 1 ? 2 : 1;
          $resourceCard = RemoveResource($player, $cardIndex);
          AddResources($resourceCard, $otherPlayer, "PLAY", "DOWN");
        }
      }
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
      case "0474909987"://Val
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to add two experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
        AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
        break;
      case "7351946067"://Rhokai Gunship
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("PREPENDLASTRESULT", $player, "MYCHAR-0,THEIRCHAR-0,");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose something to deal 1 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1", 1);
        break;
      case "9151673075"://Cobb Vanth
        AddDecisionQueue("FINDINDICES", $player, "DECKTOPXREMOVE," . 10);
        AddDecisionQueue("SETDQVAR", $player, "0", 1);
        AddDecisionQueue("FILTER", $player, "LastResult-include-definedType-Unit", 1);
        AddDecisionQueue("FILTER", $player, "LastResult-include-maxCost-2", 1);
        AddDecisionQueue("CHOOSECARD", $player, "<-", 1);
        AddDecisionQueue("ADDDISCARD", $player, "HAND,TTFREE", 1);
        AddDecisionQueue("REVEALCARDS", $player, "-", 1);
        AddDecisionQueue("OP", $player, "REMOVECARD", 1);
        AddDecisionQueue("ALLRANDOMBOTTOM", $player, "DECK");
        break;
      case "9637610169"://Bo Katan
        if(GetHealth(1) >= 15) Draw($player);
        if(GetHealth(2) >= 15) Draw($player);
        break;
      case "7204838421"://Enterprising Lackeys
        $discard = &GetDiscard($player);
        MZMoveCard($player, "MYRESOURCES", "MYDISCARD,RESOURCES", may:false);
        break;
      default: break;
    }
    $upgrades = $destroyedAlly->GetUpgrades();
    for($i=0; $i<count($upgrades); ++$i) {
      switch($upgrades[$i]) {
        case "6775521270"://Inspiring Mentor
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to give an experience");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
          break;
      }
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
      case "3feee05e13"://Gar Saxon
        $upgrades = $destroyedAlly->GetUpgrades();
        for($j=0; $j<count($upgrades); ++$j) {
          if(!IsToken($upgrades[$j])) {
            AddHand($player, $upgrades[$j]);
            break;
          }
        }
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
      case "8687233791"://Punishing One
        $thisAlly = new Ally("MYALLY-" . $i, $otherPlayer);
        if($destroyedAlly->IsUpgraded() && $thisAlly->IsExhausted() && $thisAlly->NumUses() > 0) {
          AddDecisionQueue("YESNO", $otherPlayer, "if you want to ready " . CardLink("", $thisAlly->CardID()));
          AddDecisionQueue("NOPASS", $otherPlayer, "-");
          AddDecisionQueue("PASSPARAMETER", $otherPlayer, "MYALLY-" . $i, 1);
          AddDecisionQueue("MZOP", $otherPlayer, "READY", 1);
          AddDecisionQueue("ADDMZUSES", $otherPlayer, "-1", 1);
        }
        break;
      default: break;
    }
  }
}

function CollectBounty($player, $index, $cardID, $reportMode=false, $bountyUnitOverride="-") {
  $ally = new Ally("MYALLY-" . $index, $player);
  $bountyUnit = $bountyUnitOverride == "-" ? $ally->CardID() : $bountyUnitOverride;
  $opponent = $player == 1 ? 2 : 1;
  $numBounties = 0;
  switch($cardID) {
    case "1090660242-2"://The Client
      ++$numBounties;
      if($reportMode) break;
      Restore(5, $opponent);
      break;
    case "0622803599-2"://Jabba the Hutt
      ++$numBounties;
      if($reportMode) break;
      AddCurrentTurnEffect("0622803599-3", $opponent);
      break;
    case "f928681d36-2"://Jabba the Hutt Leader Unit
      ++$numBounties;
      if($reportMode) break;
      AddCurrentTurnEffect("f928681d36-3", $opponent);
      break;
    case "2178538979"://Price on Your Head
      ++$numBounties;
      if($reportMode) break;
      AddTopDeckAsResource($opponent);
      break;
    case "2740761445"://Guild Target
      ++$numBounties;
      if($reportMode) break;
      $damage = CardIsUnique($bountyUnit) ? 3 : 2;
      DealDamageAsync($player, $damage, "DAMAGE", "2740761445");
      break;
    case "4117365450"://Wanted
      ++$numBounties;
      if($reportMode) break;
      ReadyResource($opponent);
      ReadyResource($opponent);
      break;
    case "4282425335"://Top Target
      ++$numBounties;
      if($reportMode) break;
      $amount = CardIsUnique($bountyUnit) ? 6 : 4;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY&THEIRALLY");
      AddDecisionQueue("PREPENDLASTRESULT", $opponent, "THEIRCHAR-0,", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $opponent, "MYCHAR-0,", 1);
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose a card to restore ".$amount, 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $opponent, "<-", 1);
      AddDecisionQueue("MZOP", $opponent, "RESTORE,".$amount, 1);
      break;
    case "3074091930"://Rich Reward
      ++$numBounties;
      if($reportMode) break;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY");
      AddDecisionQueue("OP", $opponent, "MZTONORMALINDICES");
      AddDecisionQueue("PREPENDLASTRESULT", $opponent, "3-", 1);
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose up to 2 units to give experience");
      AddDecisionQueue("MULTICHOOSEUNIT", $opponent, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $opponent, "MULTIGIVEEXPERIENCE", 1);
      break;
    case "1780014071"://Public Enemy
      ++$numBounties;
      if($reportMode) break;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose a unit to give a shield");
      AddDecisionQueue("CHOOSEMULTIZONE", $opponent, "<-", 1);
      AddDecisionQueue("MZOP", $opponent, "ADDSHIELD", 1);
      break;
    case "6135081953"://Doctor Evazan
      ++$numBounties;
      if($reportMode) break;
      for($i=0; $i<12; ++$i) {
        ReadyResource($opponent);
      }
      break;
    case "6420322033"://Enticing Reward
      ++$numBounties;
      if($reportMode) break;
      AddDecisionQueue("FINDINDICES", $opponent, "DECKTOPXREMOVE," . 10);
      AddDecisionQueue("SETDQVAR", $opponent, "0", 1);
      AddDecisionQueue("FILTER", $opponent, "LastResult-exclude-definedType-Unit", 1);
      AddDecisionQueue("MAYCHOOSECARD", $opponent, "<-", 1);
      AddDecisionQueue("ADDHAND", $opponent, "-", 1);
      AddDecisionQueue("REVEALCARDS", $opponent, "-", 1);
      AddDecisionQueue("OP", $opponent, "REMOVECARD");
      AddDecisionQueue("SETDQVAR", $opponent, "0", 1);
      AddDecisionQueue("FILTER", $opponent, "LastResult-exclude-definedType-Unit", 1);
      AddDecisionQueue("MAYCHOOSECARD", $opponent, "<-", 1);
      AddDecisionQueue("ADDHAND", $opponent, "-", 1);
      AddDecisionQueue("REVEALCARDS", $opponent, "-", 1);
      AddDecisionQueue("OP", $opponent, "REMOVECARD");
      AddDecisionQueue("ALLRANDOMBOTTOM", $opponent, "DECK");
      if(!CardIsUnique($bountyUnit)) PummelHit($opponent);
      break;
    case "9503028597"://Clone Deserter
    case "9108611319"://Cartel Turncoat
    case "6878039039"://Hylobon Enforcer
      ++$numBounties;
      if($reportMode) break;
      Draw($opponent);
      break;
    case "8679638018"://Wanted Insurgents
      ++$numBounties;
      if($reportMode) break;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose a unit to deal 2 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $opponent, "<-", 1);
      AddDecisionQueue("MZOP", $opponent, "DEALDAMAGE,2", 1);
      break;
    case "3503780024"://Outlaw Corona
      ++$numBounties;
      if($reportMode) break;
      AddTopDeckAsResource($opponent);
      break;
    case "6947306017"://Fugitive Wookie
      ++$numBounties;
      if($reportMode) break;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose a card to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $opponent, "<-", 1);
      AddDecisionQueue("MZOP", $opponent, "REST", 1);
      break;
    case "0252207505"://Synara San
      if($bountyUnitOverride != "-" || $ally->IsExhausted()) {
        ++$numBounties;
        if($reportMode) break;
        DealDamageAsync($player, 5, "DAMAGE", "0252207505");
      }
      break;
    case "2965702252"://Unlicensed Headhunter
      if($bountyUnitOverride != "-" || $ally->IsExhausted()) {
        ++$numBounties;
        if($reportMode) break;
        Restore(5, $opponent);
      }
      break;
    case "7642980906"://Stolen Landspeeder
      ++$numBounties;
      if($reportMode) break;
      if($ally->Owner() == $opponent) AddLayer("TRIGGER", $opponent, "7642980906");
      break;
    case "7270736993"://Unrefusable Offer
      ++$numBounties;
      if($reportMode) break;
      AddLayer("TRIGGER", $opponent, "7270736993", $bountyUnit);//Passing the cardID of the bountied unit as $target in order to search for it from discard
      break;
    case "9642863632"://Bounty Hunter's Quarry
      ++$numBounties;
      if($reportMode) break;
      $amount = CardIsUnique($bountyUnit) ? 10 : 5;
      $deck = &GetDeck($opponent);
      if(count($deck)/DeckPieces() < $amount) $amount = count($deck)/DeckPieces();
      AddLayer("TRIGGER", $opponent, "9642863632", target:$amount);
      break;
    case "0807120264"://Death Mark
      ++$numBounties;
      if($reportMode) break;
      Draw($opponent);
      Draw($opponent);
      break;
    case "2151430798."://Guavian Antagonizer
      ++$numBounties;
      if($reportMode) break;
      Draw($opponent);
      break;
    case "0474909987"://Val
      ++$numBounties;
      if($reportMode) break;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose a unit to deal 3 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $opponent, "<-", 1);
      AddDecisionQueue("MZOP", $opponent, "DEALDAMAGE,3", 1);
      break;
    default: break;
  }
  if($numBounties > 0 && !$reportMode) {
    $bosskIndex = SearchAlliesForCard($opponent, "d2bbda6982"); 
    if($bosskIndex != "") {
      $bossk = new Ally("MYALLY-" . $bosskIndex, $opponent);
      if($bossk->NumUses() > 0) {
        AddDecisionQueue("NOALLYUNIQUEIDPASS", $opponent, $bossk->UniqueID());
        AddDecisionQueue("PASSPARAMETER", $opponent, $cardID, 1);
        AddDecisionQueue("SETDQVAR", $opponent, 0, 1);
        AddDecisionQueue("SETDQCONTEXT", $opponent, "Do you want to collect the bounty for <0> again with Bossk?", 1);
        AddDecisionQueue("YESNO", $opponent, "-", 1);
        AddDecisionQueue("NOPASS", $opponent, "-", 1);
        AddDecisionQueue("PASSPARAMETER", $opponent, "MYALLY-" . $bosskIndex, 1);
        AddDecisionQueue("ADDMZUSES", $opponent, "-1", 1);
        AddDecisionQueue("COLLECTBOUNTY", $player, $cardID . "," . $bountyUnit, 1);
      }
    }
  }
  return $numBounties;
}

//Bounty abilities
function CollectBounties($player, $index, $reportMode=false) {
  global $currentTurnEffects;
  $ally = new Ally("MYALLY-" . $index, $player);
  $opponent = $player == 1 ? 2 : 1;
  $numBounties = 0;
  //Current turn effect bounties
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    $numBounties += CollectBounty($player, $index, $currentTurnEffects[$i], $reportMode);
  }
  //Upgrade bounties
  $upgrades = $ally->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i)
  {
    $numBounties += CollectBounty($player, $index, $upgrades[$i], $reportMode);
  }
  $numBounties += CollectBounty($player, $index, $ally->CardID(), $reportMode);
  return $numBounties;
}

function OnKillAbility($fromCombat)
{
  global $combatChain, $mainPlayer, $defPlayer;
  if(count($combatChain) == 0) return;
  $attackerAlly = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
  $upgrades = $attackerAlly->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i) {
    switch($upgrades[$i]) {
      case "4897501399"://Ruthlessness
        WriteLog("Ruthlessness deals 2 damage to the defender's base");
        DealDamageAsync($defPlayer, 2, "DAMAGE", $attackerAlly->CardID());
        break;
      default: break;
    }
  }
  switch($combatChain[0])
  {
    case "5230572435"://Mace Windu, Party Crasher
      $attackerAlly->Ready();
      break;
    case "6769342445"://Jango Fett
      Draw($mainPlayer);
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

//NOTE: This is for ally abilities that trigger when any ally attacks
function AllyAttackAbilities($attackID)
{
  global $mainPlayer, $combatChainState, $CCS_AttackUniqueID, $defPlayer, $CCS_IsAmbush;
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
      case "8107876051"://Enfys Nest
        if($combatChainState[$CCS_IsAmbush] == 1) {
          $target = new Ally(GetAttackTarget(), $defPlayer);
          AddCurrentTurnEffect("8107876051", $defPlayer, "PLAY", $target->UniqueID());
        }
        break;
      default: break;
    }
  }
  $defAllies = &GetAllies($defPlayer);
  for($i=0; $i<count($defAllies); $i+=AllyPieces()) {
    switch($defAllies[$i]) {
      case "7674544152"://Kragan Gorr
        if(GetAttackTarget() == "THEIRCHAR-0") {
          AddDecisionQueue("MULTIZONEINDICES", $defPlayer, "MYALLY:arena=" . CardArenas($attackID));
          AddDecisionQueue("SETDQCONTEXT", $defPlayer, "Choose a unit to give a shield");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $defPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $defPlayer, "ADDSHIELD", 1);
        }
        break;
      default: break;
    }
  }
}

function AllyAttackedAbility($attackTarget, $index) {
  global $mainPlayer, $defPlayer;
  $ally = new Ally("MYALLY-" . $index, $defPlayer);
  $upgrades = $ally->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i) {
    switch($upgrades[$i]) {
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
    case "8228196561"://Clan Saxon Gauntlet
      AddDecisionQueue("MULTIZONEINDICES", $defPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $defPlayer, "Choose a unit to give an experience token", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $defPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $defPlayer, "ADDEXPERIENCE", 1);
      break;
    default: break;
  }
}

function AddAllyPlayAbilityLayers($cardID, $from) {
  global $currentPlayer;
  $allies = &GetAllies($currentPlayer);
  for($i=0; $i<count($allies); $i+=AllyPieces()) {
    if(AllyHasPlayCardAbility($cardID, $allies[$i], $currentPlayer, $i)) AddLayer("TRIGGER", $currentPlayer, "AFTERPLAYABILITY", $cardID, $from, $allies[$i] . "," . $allies[$i+5], append:true);
  }
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $theirAllies = &GetAllies($otherPlayer);
  for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
    if(AllyHasPlayCardAbility($cardID, $theirAllies[$i], $otherPlayer, $i)) AddLayer("TRIGGER", $currentPlayer, "AFTERPLAYABILITY", $cardID, $from, $theirAllies[$i] . "," . $allies[$i+5], append:true);
  }
}

function AllyHasPlayCardAbility($playedCard, $cardID, $player, $index) {
  global $currentPlayer;
  
  if($player == $currentPlayer) {
    switch($cardID) {
      case "415bde775d"://Hondo Ohnaka
      case "0052542605"://Bossk
        return true;
      case "9850906885"://Maz Kanata
        return $playedCard != $cardID && DefinedTypesContains($cardID, "Unit");
      case "3952758746"://Toro Calican
        return $playedCard == $cardID && $index == LastAllyIndex($player) ? false : true;
      case "724979d608"://Cad Bane Leader
      case "0981852103"://Lady Proxima
        return $playedCard != $cardID && TraitContains($playedCard, "Underworld", $player);
      case "4088c46c4d"://The Mandalorian
      case "8031540027"://Dengar
        return DefinedTypesContains($playedCard, "Upgrade");
      case "0961039929"://Colonel Yularen
        return AspectContains($cardID, "Command") && DefinedTypesContains($cardID, "Unit");
      case "5907868016"://Fighters for Freedom
        return $playedCard != $cardID && AspectContains($cardID, "Aggression");
      case "3010720738"://Tobias Beckett
        return !DefinedTypesContains($cardID, "Unit");
      default: break;
    }
  } else {
    switch($cardID) {
      case "5555846790"://Saw Gerrera
      case "4935319539"://Krayt Dragon
        return true;
      default: break;
    }
  }
  return false;
}

function AllyPlayCardAbility($cardID, $player="", $from="-", $abilityID="-", $uniqueID='-')
{
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  $allies = &GetAllies($player);
  $index = SearchAlliesForUniqueID($uniqueID, $player);
  switch($abilityID)
  {
    case "415bde775d"://Hondo Ohnaka
      if($from == "RESOURCES") {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give an experience token", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
      }
      break;
    case "0052542605"://Bossk
      if(DefinedTypesContains($cardID, "Event", $player)) {
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 2 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2", 1);
      }
      break;
    case "0961039929"://Colonel Yularen
      if(DefinedTypesContains($cardID, "Unit", $player) && AspectContains($cardID, "Command", $player)) {
        Restore(1, $player);
      }
      break;
    case "9850906885"://Maz Kanata
      if(DefinedTypesContains($cardID, "Unit", $player)) {
        $me = new Ally("MYALLY-" . $index, $player);
        $me->Attach("2007868442");//Experience token
      }
      break;
    case "5907868016"://Fighters for Freedom
      if(AspectContains($cardID, "Aggression", $player)) {
        $otherPlayer = ($player == 1 ? 2 : 1);
        DealDamageAsync($otherPlayer, 1, "DAMAGE", "5907868016");
        WriteLog(CardLink("5907868016", "5907868016") . " is dealing 1 damage.");
      }
      break;
    case "8031540027"://Dengar
      if(DefinedTypesContains($cardID, "Upgrade", $player)) {
        global $CS_LayerTarget;
        $target = GetClassState($player, $CS_LayerTarget);
        AddDecisionQueue("YESNO", $player, "Do you want to deal 1 damage from " . CardLink($allies[$index], $allies[$index]) . "?");
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("PASSPARAMETER", $player, $target, 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1", 1);
      }
      break;
    case "0981852103"://Lady Proxima
      if(TraitContains($cardID, "Underworld", $player)) {
        $otherPlayer = $player == 1 ? 2 : 1;
        DealDamageAsync($otherPlayer, 1, "DAMAGE", "0981852103");
      }
      break;
    case "724979d608"://Cad Bane Leader 
      $cadIndex = SearchAlliesForCard($player, "724979d608");
      if($cadIndex != "") {
        $cadbane = new Ally("MYALLY-" . $cadIndex, $player);
        if($from != 'PLAY' && $cadbane->NumUses() > 0 && TraitContains($cardID, "Underworld", $currentPlayer)) {
          AddLayer("TRIGGER", $currentPlayer, "724979d608", append:true);
        }
      }
      break;
    case "4088c46c4d"://The Mandalorian
      if(DefinedTypesContains($cardID, "Upgrade", $player)) {
        AddLayer("TRIGGER", $currentPlayer, "4088c46c4d", append:true);
      }
      break;
    case "3952758746"://Toro Calican
      $toroIndex = SearchAlliesForCard($player, "3952758746");
      if($toroIndex != "") {
        $toroCalican = new Ally("MYALLY-" . $toroIndex, $player);
        if(TraitContains($cardID, "Bounty Hunter", $currentPlayer) && $toroCalican->NumUses() > 0){
          AddDecisionQueue("YESNO", $player, "if you want to use Toro Calican's ability");
          AddDecisionQueue("NOPASS", $player, "-");
          AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . LastAllyIndex($player), 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1", 1);
          AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $toroIndex, 1);
          AddDecisionQueue("MZOP", $player, "READY", 1);
          AddDecisionQueue("ADDMZUSES", $player, "-1", 1);
        }
      }
      break;
    case "3010720738"://Tobias Beckett
      $tobiasBeckett = New Ally("MYALLY-" . $index, $player);
      if($tobiasBeckett->NumUses() > 0 && !DefinedTypesContains($cardID, "Unit", $player)) {
        $playedCardCost = CardCost($cardID);
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxCost=" . $playedCardCost . "&THEIRALLY:maxCost=" . $playedCardCost);
        AddDecisionQueue("MZFILTER", $player, "status=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust with Tobias Beckett", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "REST", 1);
        AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $index, 1);
        AddDecisionQueue("ADDMZUSES", $player, -1, 1);
      }
      break;
    default: break;
  }
  $otherPlayer = ($player == 1 ? 2 : 1);
  switch($abilityID)
  {
    case "5555846790"://Saw Gerrera
      if(DefinedTypesContains($cardID, "Event", $player)) {
        DealDamageAsync($player, 2, "DAMAGE", "5555846790");
      }
      break;
    case "4935319539"://Krayt Dragon
      $damage = CardCost($cardID);
      AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "THEIRALLY:arena=Ground");
      AddDecisionQueue("PREPENDLASTRESULT", $otherPlayer, "THEIRCHAR-0,");
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a card to deal " . $damage . " damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $otherPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "DEALDAMAGE," . $damage, 1);
      break;
    default: break;
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
  $attackerIndex = $combatChainState[$CCS_WeaponIndex];
  $attackerAlly = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
  $upgrades = $attackerAlly->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i) {
    switch($upgrades[$i]) {
      case "7280213969"://Smuggling Compartment
        ReadyResource($mainPlayer);
        break;
      case "3987987905"://Hardpoint Heavy Blaster
        $attackTarget = GetAttackTarget();
        $target = new Ally($attackTarget, $defPlayer);
        if($attackTarget != "THEIRCHAR-0") {
          AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 2 damage to");
          AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:arena=" . CardArenas($target->CardID()));
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
      case "6471336466"://Vambrace Flamethrower
        AddDecisionQueue("FINDINDICES", $mainPlayer, "ALLTHEIRUNITSMULTI");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose units to damage", 1);
        AddDecisionQueue("MULTICHOOSETHEIRUNIT", $mainPlayer, "<-", 1);
        AddDecisionQueue("MULTIDISTRIBUTEDAMAGE", $mainPlayer, 3, 1);
        break;
      case "3141660491"://The Darksaber
        $allies = &GetAllies($mainPlayer);
        for($j=0; $j<count($allies); $j+=AllyPieces()) {
          if($j == $attackerAlly->Index()) continue;
          $ally = new Ally("MYALLY-" . $j, $mainPlayer);
          if(TraitContains($ally->CardID(), "Mandalorian", $mainPlayer)) $ally->Attach("2007868442");//Experience token
        }
        break;
      case "1938453783"://Armed to the Teeth
        //Adapted from Benthic Two-Tubes
        $ally = new Ally("MYALLY-" . $attackerIndex, $mainPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerIndex);
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give +2/+0");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "1938453783,HAND", 1);
        break;
      case "6775521270"://Inspiring Mentor
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerIndex);
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give an experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "ADDEXPERIENCE", 1);
        break;
      default: break;
    }
  }
  if($attackerAlly->LostAbilities()) return;
  $allies = &GetAllies($mainPlayer);
  switch($allies[$attackerIndex]) {
    case "0256267292"://Benthic 'Two Tubes'
      $ally = new Ally("MYALLY-" . $attackerIndex, $mainPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:aspect=Aggression");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerIndex);
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
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, "MYALLY-" . $attackerIndex, 1);
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
    case "3417125055"://IG-11
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:damagedOnly=true;arena=Ground");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a damaged unit to deal 3 damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,3", 1);
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
    case "6c5b96c7ef"://Emperor Palpatine
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to destroy");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DESTROY", 1);
      AddDecisionQueue("DRAW", $mainPlayer, "-", 1);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1", 1);
      break;
    case "5464125379"://Strafing Gunship
      if(IsAllyAttackTarget()) {
        $target = GetAttackTarget();
        $ally = new Ally($target, $defPlayer);
        if(CardArenas($ally->CardID()) == "Ground") {
          AddCurrentTurnEffect("5464125379", $defPlayer, from:"PLAY");
        }
      }
      break;
    case "9725921907"://Kintan Intimidator
      if(IsAllyAttackTarget()) {
        $target = GetAttackTarget();
        $ally = new Ally($target, $defPlayer);
        $ally->Exhaust();
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
    case "2522489681"://Zorii Bliss
      Draw($mainPlayer);
      AddCurrentTurnEffect("2522489681", $mainPlayer, from:"PLAY");
      break;
    case "4534554684"://Freetown Backup
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerIndex);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give +2/+2", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDHEALTH,2", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "4534554684,PLAY", 1);
      break;
    case "4721657243"://Kihraxz Heavy Fighter
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to exhaust to give this +3 power", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "REST", 1);
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, $allies[$i+5], 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "4721657243,PLAY", 1);
      break;
    case "9951020952"://Koska Reeves
      if($attackerAlly->IsUpgraded()) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 2 damage", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2", 1);
      }
      break;
    case "5511838014"://Kuil
      $card = Mill($mainPlayer, 1);
      if(SharesAspect($card, GetPlayerBase($mainPlayer))) {
        WriteLog("Kuil returns " . CardLink($card, $card) . " to hand");
        $discard = &GetDiscard($mainPlayer);
        RemoveDiscard($mainPlayer, count($discard) - DiscardPieces());
        AddHand($mainPlayer, $card);
      }
      break;
    case "9472541076"://Grey Squadron Y-Wing
      AddDecisionQueue("MULTIZONEINDICES", $defPlayer, "MYALLY");
      AddDecisionQueue("PREPENDLASTRESULT", $defPlayer, "MYCHAR-0,");
      AddDecisionQueue("SETDQCONTEXT", $defPlayer, "Choose something to deal 2 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $defPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $defPlayer, "DEALDAMAGE,2", 1);
      break;
    case "7291903225"://Rickety Quadjumper
      $deck = &GetDeck($mainPlayer);
      if(count($deck) > 0 && RevealCards($deck[0])) {
        AddDecisionQueue("PASSPARAMETER", $mainPlayer, $deck[0], 1);
        AddDecisionQueue("NONECARDDEFINEDTYPEORPASS", $mainPlayer, "Unit", 1);
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY", 1);
        AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give an experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "7171636330"://Chain Code Collector
      if(IsAllyAttackTarget()) {
        $target = GetAttackTarget();
        $ally = new Ally($target, $defPlayer);
        if($ally->HasBounty()) {
          AddCurrentTurnEffect("7171636330", $defPlayer, "PLAY", $ally->UniqueID());
          UpdateLinkAttack();
        }
      }
      break;
    case "a579b400c0"://Bo-Katan Kryze
      global $CS_NumMandalorianAttacks;
      $number = GetClassState($mainPlayer, $CS_NumMandalorianAttacks) > 1 ? 2 : 1;
      for($i=0; $i<$number; ++$i) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1", 1);
      }
      break;
    case "7982524453"://Fennec Shand
      if(IsAllyAttackTarget()) {
        $discard = &GetDiscard($mainPlayer);
        $numDistinct = 0;
        $costMap = [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
        for($i=0; $i<count($discard); $i+=DiscardPieces()) {
          $cost = CardCost($discard[$i]);
          if($cost == "") continue;
          ++$costMap[$cost];
          if($costMap[$cost] == 1) ++$numDistinct;
        }
        if($numDistinct > 0) {
          $defender = new Ally(GetAttackTarget(), $defPlayer);
          $defender->DealDamage($numDistinct);
        }
      }
      break;
    case "3622749641"://Krrsantan
      $damage = $attackerAlly->Damage();
      if($damage > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal " . $damage . " damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE," . $damage, 1);
      }
      break;
    case "9115773123"://Coruscant Dissident
      ReadyResource($mainPlayer);
      break;
    case "e091d2a983"://Rey
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:maxAttack=2");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give an experience");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDEXPERIENCE", 1);
      break;
    case "5632569775"://Lom Pyke
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give a shield");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDSHIELD", 1);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give a shield", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDSHIELD", 1);
      break;
    case "4595532978"://Ketsu Onyo
      //TODO ADD OVERWHELM
      if(GetAttackTarget() == "THEIRCHAR-0") {
        DefeatUpgrade($mainPlayer, true, upgradeFilter: "maxCost=2");
      }
      break;
    case "5966087637"://Poe Dameron
      PummelHit($mainPlayer, may:true, context:"Choose a card to discard to defeat an upgrade (or pass)");
      DefeatUpgrade($mainPlayer, passable:true);
      PummelHit($mainPlayer, may:true, context:"Choose a card to discard to deal damage (or pass)");
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $mainPlayer, "THEIRCHAR-0,", 1);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to deal 2 damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2", 1);
      PummelHit($mainPlayer, may:true, context:"Choose a card to discard to make opponent discard (or pass)");
      PummelHit($defPlayer, passable:true);
      break;
    case "8862896760"://Maul
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Underworld");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to take the damage for Maul", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "8862896760,HAND", 1);
      break;
    case "5080989992"://Rose Tico
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to defeat a shield from (or pass)");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $mainPlayer, "ROSETICO", 1);
      break;
    case "9040137775"://Principled Outlaw
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "REST", 1);
      break;
    case "0196346374"://Rey (Keeping the Past)
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to heal");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "RESTORE,2", 1);
      AddDecisionQueue("MZNOCARDASPECTORPASS", $mainPlayer, "Heroism", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDSHIELD", 1);
      break;
    case "6263178121"://Kylo Ren (Killing the Past)
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give +2/+0");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDEFFECT,6263178121", 1);
      AddDecisionQueue("MZNOCARDASPECTORPASS", $mainPlayer, "Villainy", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDEXPERIENCE", 1);
      break;
    case "8903067778"://Finn leader unit
      DefeatUpgrade($mainPlayer, may:true, search:"MYALLY");
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDSHIELD", 1);
      break;
    case "c9ff9863d7"://Hunter (Outcast Sergeant)
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYRESOURCES");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a resource to reveal", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $mainPlayer, "HUNTEROUTCASTSERGEANT", 1);
      break;
    case "9734237871"://Ephant Mon
      $unitsThatAttackedBaseMZIndices = GetUnitsThatAttackedBaseMZIndices($mainPlayer);
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, $unitsThatAttackedBaseMZIndices);
      AddDecisionQueue("MZFILTER", $mainPlayer, "definedType=Leader");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to capture", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $mainPlayer, "1", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETARENA", 1);
      AddDecisionQueue("SETDQVAR", $mainPlayer, "2", 1);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena={2}", 1);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a friendly unit to capture the target", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $mainPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, "{1}", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "CAPTURE,{0}", 1);
      break;
    default: break;
  }
}

function AllyHitEffects() {
  global $mainPlayer;
  $allies = &GetAllies($mainPlayer);
  for($i=0; $i<count($allies); $i+=AllyPieces()) {
    switch($allies[$i]) {
      default: break;
    }
  }
}

function AllyDamageTakenAbilities($player, $index, $survived, $damage, $fromCombat=false)
{
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces()) {
    switch($allies[$i]) {
      case "7022736145"://Tarfful
        if($survived && $fromCombat && TraitContains($allies[$index], "Wookiee", $player)) {
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:arena=Ground");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal " . $damage . " damage to");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE," . $damage, 1);
        }
        break;
      default: break;
    }
  }
  switch($allies[$index]) {
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

function AllyBeginEndTurnEffects()
{
  global $mainPlayer, $defPlayer;
  //Reset health for all allies
  $mainAllies = &GetAllies($mainPlayer);
  for($i = count($mainAllies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    if($mainAllies[$i+1] != 0) {
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

function CharacterEndTurnAbilities($player){
  $character = &GetPlayerCharacter($player);
  for($i = 0; $i < count($character); $i += CharacterPieces()) {
    if($character[$i + 1] == 0 || $character[$i + 1] == 1) continue; //Do not process ability if it is destroyed
    switch($character[$i]) {
      case "0254929700"://Doctor Aphra
        Mill($player, 1);
        break;
      default:
        break;
    }
  }
}

function AllyCardDiscarded($player, $discardedID) {
  //My allies card discarded effects
  $allies = &GetAllies($player);
  for($i = 0; $i < count($allies); $i += AllyPieces()) {
    switch($allies[$i]) {
      case "6910883839"://Migs Mayfield
        $ally = new Ally("MYALLY-" . $i, $player);
        if($ally->NumUses() > 0) {
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("PREPENDLASTRESULT", $player, "MYCHAR-0,THEIRCHAR-0,");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose something to deal 2 damage to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2", 1);
          AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $i, 1);
          AddDecisionQueue("ADDMZUSES", $player, "-1", 1);
        }
        break;
      default: break;
    }
  }
  $otherPlayer = $player == 1 ? 2 : 1;
  $allies = &GetAllies($otherPlayer);
  for($i = 0; $i < count($allies); $i += AllyPieces()) {
    switch($allies[$i]) {
      case "6910883839"://Migs Mayfield
        $ally = new Ally("MYALLY-" . $i, $otherPlayer);
        if($ally->NumUses() > 0) {
          AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY&THEIRALLY");
          AddDecisionQueue("PREPENDLASTRESULT", $otherPlayer, "MYCHAR-0,THEIRCHAR-0,");
          AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose something to deal 2 damage to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $otherPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $otherPlayer, "DEALDAMAGE,2", 1);
          AddDecisionQueue("PASSPARAMETER", $otherPlayer, "MYALLY-" . $i, 1);
          AddDecisionQueue("ADDMZUSES", $otherPlayer, "-1", 1);
        }
        break;
      default: break;
    }
  }
}

function XanaduBlood($player, $index=-1) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Underworld");
  if($index > -1) AddDecisionQueue("MZFILTER", $player, "index=MYALLY-" . $index);
  AddDecisionQueue("MZFILTER", $player, "leader=1");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose an underworld unit to bounce");
  AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "BOUNCE", 1);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose what you want to exhaust", 1);
  AddDecisionQueue("BUTTONINPUT", $player, "Unit,Resource", 1);
  AddDecisionQueue("SPECIFICCARD", $player, "XANADUBLOOD", 1);
}

function JabbasRancor($player, $index=-1) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:arena=Ground");
  if($index > -1) AddDecisionQueue("MZFILTER", $player, "index=MYALLY-" . $index);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose something to deal 3 damage to");
  AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "DEALDAMAGE,3", 1);
  AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:arena=Ground");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose something to deal 3 damage to");
  AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "DEALDAMAGE,3", 1);
}
