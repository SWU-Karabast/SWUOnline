<?php
require __DIR__ . "/Libraries/LayerHelpers.php";

function CreateCloneTrooper($player, $from = "-") {
  return PlayAlly("3941784506", $player, from:$from); //Clone Trooper
}

function CreateBattleDroid($player, $from = "-") {
  return PlayAlly("3463348370", $player, from:$from); //Battle Droid
}

function PlayAlly($cardID, $player, $subCards = "-", $from = "-", $owner = null, $cloned = false, $playCardEffect = false)
{
  $uniqueID = GetUniqueId();
  $allies = &GetAllies($player);
  if(count($allies) < AllyPieces()) $allies = [];
  $allies[] = $cardID;
  $allies[] = AllyEntersPlayState($cardID, $player, $from);
  $allies[] = 0; //Damage
  $allies[] = 0; //Frozen
  $allies[] = $subCards; //Subcards
  $allies[] = $uniqueID; //Unique ID
  $allies[] = AllyEnduranceCounters($cardID); //Endurance Counters
  $allies[] = 0; //Buff Counters
  $allies[] = 1; //Ability/effect uses
  $allies[] = 0; //Round health modifier
  $allies[] = 0; //Times attacked
  $allies[] = $owner ?? $player; //Owner
  $allies[] = 0; //Turns in play
  $allies[] = $cloned ? 1 : 0; //Cloned
  $index = count($allies) - AllyPieces();
  CurrentEffectAllyEntersPlay($player, $index);
  CheckUniqueAlly($uniqueID);

  if ($playCardEffect || $cardID == "0345124206") { //Clone - Ensure that the Clone will always choose a unit to clone whenever it enters play.
    if(HasShielded($cardID, $player, $index)) {
      AddLayer("TRIGGER", $player, "SHIELDED", "-", "-", $uniqueID);
    }
    if(HasAmbush($cardID, $player, $index, $from)) {
      AddLayer("TRIGGER", $player, "AMBUSH", "-", "-", $uniqueID);
    }
    PlayAbility($cardID, $from, 0, uniqueId:$uniqueID);
  }

  if (AllyHasStaticHealthModifier($cardID)) {
    CheckHealthAllAllies();
  }
  // Verify if the Token has enough HP, accounting for other abilities in play.
  // Non-token units are excluded as they are validated elsewhere.
  if (IsToken($cardID)) {
    $ally = new Ally("MYALLY-" . $index, $player);
    $ally->DefeatIfNoRemainingHP();
  }
  return $index;
}

function CheckHealthAllAllies() {
  for ($player = 1; $player <= 2; $player++) {
    $allies = &GetAllies($player);
    for ($i = 0; $i < count($allies); $i += AllyPieces()) {
      $ally = new Ally("MYALLY-" . $i, $player);
      $defeated = $ally->DefeatIfNoRemainingHP();

      if ($defeated) {
        $i -= AllyPieces(); // Decrement to account for the removed ally
      }
    }
  }
}

function CheckUniqueAlly($uniqueID) {
  $ally = new Ally($uniqueID);
  $cardID = $ally->CardID();
  $player = $ally->PlayerID();
  if (CardIsUnique($cardID) && SearchCount(SearchAlliesForCard($player, $cardID)) > 1 && !$ally->IsCloned()) {
    PrependDecisionQueue("MZDESTROY", $player, "-", 1);
    PrependDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
    PrependDecisionQueue("SETDQCONTEXT", $player, "You have two of this unique unit; choose one to destroy");
    PrependDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:cardID=" . $cardID);
  }
}

function LeaderAbilitiesIgnored() {
  return AnyPlayerHasAlly("4602353389");//Brain Invaders
}

function HasWhenEnemyDestroyed($cardID, $numUses, $wasUnique, $wasUpgraded) {
  switch($cardID) {
    case "1664771721"://Gideon Hask
    case "b0dbca5c05"://Iden Versio Leader Unit
      return true;
    case "2649829005"://Agent Kallus
      return $wasUnique && $numUses > 0;
    case "8687233791"://Punishing One
      return $wasUpgraded && $numUses > 0;
    default: return false;
  }
}

function HasWhenFriendlyDestroyed($player, $cardID, $numUses, $uniqueID,
    $destroyedCardID, $destroyedUniqueID, $destroyedWasUnique, $destroyedWasUpgraded) {
  switch($cardID) {
    case "2649829005"://Agent Kallus //goes hand-in-hand with the enemy destroyed ability
      return $numUses > 0 && $destroyedWasUnique && $uniqueID != $destroyedUniqueID;
    case "9353672706"://General Krell
      return $uniqueID != $destroyedUniqueID;
    case "3feee05e13"://Gar Saxon Leader Unit
      return !LeaderAbilitiesIgnored() && $destroyedWasUpgraded;
    case "f05184bd91"://Nala Se Leader Unit
      return !LeaderAbilitiesIgnored() && TraitContains($destroyedCardID, "Clone", $player) || IsCloned($destroyedUniqueID);
    case "1039828081"://Calculating MagnaGuard
      if(SearchCurrentTurnEffects("1039828081", $player)) return false;
      return $uniqueID != $destroyedUniqueID;//while not specifically stated, it is implied that it will not be the destroyed unit
    default: return false;
  }
}

function AllyIsMultiAttacker($cardID) {
  switch($cardID) {
    case "8613680163"://Darth Maul (Revenge At Last)
      return true;
    default:
      return false;
  }
}

function AllyHasStaticHealthModifier($cardID)
{
  switch($cardID)
  {
    case "1557302740"://General Veers
    case "9799982630"://General Dodonna
    case "4339330745"://Wedge Antilles
    case "4511413808"://Follower of the Way
    case "3731235174"://Supreme Leader Snoke
    case "8418001763"://Huyang
    case "6097248635"://4-LOM
    case "1690726274"://Zuckuss
    case "2260777958"://41st Elite Corps
    case "2265363405"://Echo
    case "1209133362"://332nd Stalwart
    case "47557288d6"://Captain Rex
    case "0268657344"://Admiral Yularen
    case "4718895864"://Padawan Starfighter
    case "9017877021"://Clone Commander Cody
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
    case "4511413808"://Follower of the Way
      if($index == $myIndex && $player == $myPlayer) {
        $ally = new Ally("MYALLY-" . $index, $player);
        if($ally->IsUpgraded()) return 1;
      }
      break;
    case "2260777958"://41st Elite Corps
      if($index == $myIndex && $player == $myPlayer) {
        if(IsCoordinateActive($player)) return 3;
      }
      break;
    case "2265363405"://Echo
      if($index == $myIndex && $player == $myPlayer) {
        if(IsCoordinateActive($player)) return 2;
      }
      break;
    case "1209133362"://332nd Stalwart
      if($index == $myIndex && $player == $myPlayer) {
        if(IsCoordinateActive($player)) return 1;
      }
      break;
    case "4718895864"://Padawan Starfighter
      if($index == $myIndex && $player == $myPlayer) {
        if(SearchCount(SearchAllies($player, trait:"Force"))) return 1;
      }
      break;
    case "3731235174"://Supreme Leader Snoke
      return $player != $myPlayer && !IsLeader($cardID, $player) ? -2 : 0;
    case "8418001763"://Huyang
      if ($player == $myPlayer) {
        $ally = new Ally("MYALLY-" . $index, $player);
        return SearchLimitedCurrentTurnEffects($myCardID, $player) == $ally->UniqueID() ? 2 : 0;
      }
      return 0;
    case "6097248635"://4-LOM
      return ($player == $myPlayer && CardTitle($cardID) == "Zuckuss") ? 1 : 0;
    case "1690726274"://Zuckuss
      return ($player == $myPlayer && CardTitle($cardID) == "4-LOM") ? 1 : 0;
    case "47557288d6"://Captain Rex
      if($index != $myIndex && $player == $myPlayer && TraitContains($cardID, "Trooper", $player)) return 1;
      break;
    case "0268657344"://Admiral Yularen
      if($index != $myIndex && $player == $myPlayer && AspectContains($cardID, "Heroism", $player)) return 1;
      break;
    case "9017877021"://Clone Commander Cody
      if($index != $myIndex && $player == $myPlayer && IsCoordinateActive($player)) return 1;
      break;
    default: break;
  }
  return 0;
}

// Modifiers Based on Name, whether Ally or Leader
function NameBasedHealthModifiers($cardID, $index, $player, $stackingBuff = false) {
  $modifier = 0;
  $foundBuff = false;
  $char = &GetPlayerCharacter($player);
  for($i=0; $i<count($char); $i+=CharacterPieces()) {
    switch($char[$i])
    {
      case "5784497124"://Emperor Palpatine
        if($cardID == "1780978508") {
          $modifier += 1;//Emperor's Royal Guard
          $foundBuff = true;
        }
        break;
      default: break;
    }
  }
  if($foundBuff && !$stackingBuff) return $modifier;

  $allies = GetAllies($player);
  for($i=count($allies)-AllyPieces(); $i>=0; $i-=AllyPieces()) {
    if($foundBuff && !$stackingBuff) break;
    switch($allies[$i]) {
      case "9097316363"://Emperor Palpatine (Red Unit)
      case "6c5b96c7ef"://Emperor Palpatine (Deployed Leader Unit)
        if($cardID == "1780978508") { //Emperor's Royal Guard
          $foundBuff = true;
          $modifier += 1;
        }
        break;
    }
  }
  return $modifier;
}

// Modifiers from Base
function BaseHealthModifiers($cardID, $index, $player, $stackingBuff = false) {
  $modifier = 0;
  $char = &GetPlayerCharacter($player);
  switch($char[0]) {
    case "6594935791"://Pau City
      $modifier += IsLeader($cardID) ? 1 : 0;
      break;
    default: break;
  }
  return $modifier;
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

function GivesWhenDestroyedToAllies($cardID) {
  switch($cardID) {
    case "9353672706"://General Krell gives "When Defeated" to others
    case "3feee05e13"://Gar Saxon Leader Unit gives "When Defeated" to himself and others
    case "f05184bd91"://Nala Se Leader Unit gives "When Defeated" to others that are Clone traits
      return true;
    default: return false;
  }
}

function DestroyAlly($player, $index, $skipDestroy = false, $fromCombat = false, $skipRescue = false)
{
  global $mainPlayer, $combatChainState, $CS_NumAlliesDestroyed, $CS_NumLeftPlay, $CCS_CachedLastDestroyed;

  $allies = &GetAllies($player);
  $ally = new Ally("MYALLY-" . $index, $player);
  $cardID = $ally->CardID();
  $owner = $ally->Owner();
  $uniqueID = $ally->UniqueID();
  $lostAbilities = $ally->LostAbilities();
  $isUpgraded = $ally->IsUpgraded();
  $upgrades = $ally->GetUpgrades();
  $upgradesWithOwnerData = $ally->GetUpgrades(true);
  $isExhausted = $ally->IsExhausted();
  $hasBounty = $ally->HasBounty();
  $isSuperlaserTech = $cardID === "8954587682";
  $discardPileModifier = "-";
  if(!$skipDestroy) {
    OnKillAbility($player, $uniqueID);
    $whenDestroyData="";$whenResourceData="";$whenBountiedData="";
    if((HasWhenDestroyed($cardID)
        && !$isSuperlaserTech
        && !GivesWhenDestroyedToAllies($cardID))
        || UpgradesContainWhenDefeated($upgrades)
        || CurrentEffectsContainWhenDefeated($player))
      $whenDestroyData=SerializeAllyDestroyData($uniqueID,$lostAbilities,$isUpgraded,$upgrades,$upgradesWithOwnerData);
    if($isSuperlaserTech && !$lostAbilities)
      $whenResourceData=SerializeResourceData("PLAY","DOWN",0,"0","-1");
    if(($hasBounty && !$lostAbilities) || UpgradesContainBounty($upgrades))
      $whenBountiedData=SerializeBountiesData($uniqueID, $isExhausted, $owner, $upgrades);
    if($whenDestroyData || $whenResourceData || $whenBountiedData)
      LayerDestroyTriggers($player, $cardID, $uniqueID, $whenDestroyData, $whenResourceData, $whenBountiedData);
    $wasUnique = CardIsUnique($cardID);
    $triggers = GetAllyWhenDestroyFriendlyEffects($player, $cardID, $uniqueID, $wasUnique, $isUpgraded, $upgradesWithOwnerData);
    if(count($triggers) > 0) {
      LayerFriendlyDestroyedTriggers($player, $triggers);
    }
    if($mainPlayer != $player && !$ally->LostAbilities()) {
      $combatChainState[$CCS_CachedLastDestroyed] = $ally->Serialize();
    }
    $otherPlayer = $player == 1 ? 2 : 1;
    $triggers = GetAllyWhenDestroyTheirsEffects($mainPlayer, $otherPlayer, $uniqueID, $wasUnique, $isUpgraded, $upgradesWithOwnerData);
    if(count($triggers) > 0) {
      LayerTheirsDestroyedTriggers($player, $triggers);
    }
    IncrementClassState($player, $CS_NumAlliesDestroyed);
  }

  IncrementClassState($player, $CS_NumLeftPlay);
  AllyLeavesPlayAbility($player, $index);
  for($i=0; $i<count($upgradesWithOwnerData); $i+=SubcardPieces()) {
    if($upgradesWithOwnerData[$i] == "8752877738" || $upgradesWithOwnerData[$i] == "2007868442") continue;
    if($upgradesWithOwnerData[$i] == "6911505367") $discardPileModifier = "TTFREE";//Second Chance
    AddGraveyard($upgradesWithOwnerData[$i], $upgradesWithOwnerData[$i+1], "PLAY");
  }
  $captives = $ally->GetCaptives(true);
  if(!$skipDestroy) {
    if(DefinedTypesContains($cardID, "Leader", $player)) ;//If it's a leader it doesn't go in the discard
    else if(isToken($cardID)) ; // If it's a token, it doesn't go in the discard
    else if($isSuperlaserTech) ; //SLT is auto-added to resources
    else {
      $graveyardCardID = $ally->IsCloned() ? "0345124206" : $cardID; //Clone - Replace the cloned card with the original one in the graveyard
      AddGraveyard($graveyardCardID, $owner, "PLAY", $discardPileModifier);
    }
  }
  for($j = $index + AllyPieces() - 1; $j >= $index; --$j) unset($allies[$j]);
  $allies = array_values($allies);
  if(!$skipRescue) {
    for($i=0; $i<count($captives); $i+=SubcardPieces()) {
      PlayAlly($captives[$i], $captives[$i+1], from:"CAPTIVE");
    }
  }
  if(AllyHasStaticHealthModifier($cardID)) {
    CheckHealthAllAllies();
  }
  if($player == $mainPlayer) UpdateAttacker();
  else UpdateAttackTarget();
  return $cardID;
}

function CurrentEffectsContainWhenDefeated($player) {
  global $currentTurnEffects;
  for($i=0;$i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
    switch($currentTurnEffects[$i]) {
      case "1272825113"://In Defense of Kamino
      case "9415708584": //Pyrrhic Assault
        return $currentTurnEffects[$i+1] == $player;
      default: return false;
    }
  }
}

function UpgradesContainWhenDefeated($upgrades) {
  for($i=0;$i<count($upgrades);++$i)  {
    if (HasWhenDestroyed($upgrades[$i])) return true;
  }

  return false;
}

function UpgradesContainBounty($upgrades) {
  for($i=0;$i<count($upgrades);++$i)  {
    switch($upgrades[$i]) {
      case "2178538979"://Price on Your Head
      case "2740761445"://Guild Target
      case "4282425335"://Top Target
      case "3074091930"://Rich Reward
      case "1780014071"://Public Enemy
      case "9642863632"://Bounty Hunter's Quarry
      case "0807120264"://Death Mark
      case "4117365450"://Wanted
      case "6420322033"://Enticing Reward
      case "7270736993"://Unrefusable Offer
        return true;
    }
  }

  return false;
}

function AllyTakeControl($player, $index) {
  global $currentTurnEffects;
  if($index == "") return -1;
  $otherPlayer = $player == 1 ? 2 : 1;
  $myAllies = &GetAllies($player);
  $theirAllies = &GetAllies($otherPlayer);
  $uniqueID = $theirAllies[$index+5];
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i+2] == -1 || $currentTurnEffects[$i+2] != $uniqueID) continue;
    $currentTurnEffects[$i+1] = $currentTurnEffects[$i+1] == 1 ? 2 : 1; // Swap players
  }
  for($i=$index; $i<$index+AllyPieces(); ++$i) {
    $myAllies[] = $theirAllies[$i];
  }
  for ($i=$index+AllyPieces()-1; $i>=$index; $i--) {
    unset($theirAllies[$i]);
  }
  $theirAllies = array_values($theirAllies); // Reindex the array

  CheckHealthAllAllies();
  CheckUniqueAlly($uniqueID);
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
  if(DefinedTypesContains($cardID, "Leader", $player)) return 2;
  if(IsToken($cardID) && SearchAlliesForCard($player, "0038286155")) return 2;//Chancellor Palpatine
  switch($cardID)
  {
    case "1785627279": return 2;//Millennium Falcon
    default: return 1;
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

function TheirAllyPlayableExhausted($cardID) {
  switch($cardID) {
    case "3577961001"://Mercenary Gunship
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

function TheirAllyDoesAbilityExhaust($cardID, $abilityIndex) {
  switch($cardID) {
    case "3577961001"://Mercenary Gunship
      return false;
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
  $uniqueID = $allies[$index + 5];
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
    case "8418001763"://Huyang
      SearchCurrentTurnEffects("8418001763", $player, remove:true);
      break;
    case "7964782056"://Qi'Ra unit
      $otherPlayer = $player == 1 ? 2 : 1;
      SearchLimitedCurrentTurnEffects("7964782056", $otherPlayer, uniqueID:$uniqueID, remove:true);
      break;
    case "3503494534"://Regional Governor
      $otherPlayer = $player == 1 ? 2 : 1;
      SearchLimitedCurrentTurnEffects("3503494534", $otherPlayer, uniqueID:$uniqueID, remove:true);
      break;
    case "4002861992"://DJ (Blatant Thief)
      $djAlly = new Ally("MYALLY-" . $index, $player);
      $arsenal = &GetArsenal($player);
      for ($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
        if ($arsenal[$i + 6] == $djAlly->UniqueID()) {
          $otherPlayer = $player == 1 ? 2 : 1;
          $resourceCard = RemoveResource($player, $i);
          AddResources($resourceCard, $otherPlayer, "PLAY", "DOWN", isExhausted:($arsenal[$i+4] == 1));
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

function AllyDestroyedAbility($player, $cardID, $uniqueID, $lostAbilities,
  $isUpgraded, $upgrades, $upgradesWithOwnerData)
{
  global $initiativePlayer, $currentTurnEffects;

  if(!$lostAbilities) {
    switch($cardID) {
      case "4405415770"://Yoda (Old Master)
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose player to draw 1 card");
        AddDecisionQueue("BUTTONINPUT", $player, "Yourself,Opponent,Both");
        AddDecisionQueue("SPECIFICCARD", $player, "YODAOLDMASTER", 1);
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
        AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player,1", 1);
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
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player,1", 1);
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
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1,$player,1" , 1);
        break;
      case "9151673075"://Cobb Vanth
        AddDecisionQueue("SEARCHDECKTOPX", $player, "10;1;include-definedType-Unit&include-maxCost-2");
        AddDecisionQueue("ADDDISCARD", $player, "HAND,TTFREE", 1);
        AddDecisionQueue("REVEALCARDS", $player, "-", 1);
        break;
      case "9637610169"://Bo Katan
        if(GetHealth(1) >= 15) Draw($player);
        if(GetHealth(2) >= 15) Draw($player);
        break;
      case "7204838421"://Enterprising Lackeys
        $discardID = SearchDiscardForCard($player, $cardID);
        MZChooseAndDestroy($player, "MYRESOURCES", may:true, context:"Choose a resource to destroy");
        AddDecisionQueue("PASSPARAMETER", $player, "MYDISCARD-$discardID", 1);
        AddDecisionQueue("MZADDZONE", $player, "MYRESOURCESEXHAUSTED", 1);
        AddDecisionQueue("PASSPARAMETER", $player, "MYDISCARD-$discardID", 1);
        AddDecisionQueue("MZREMOVE", $player, "-", 1);
        break;
      case "8919416985"://Outspoken Representative
        CreateCloneTrooper($player, from:"ABILITY");
        break;
      case "6404471739"://Senatorial Corvette
        $otherPlayer = $player == 1 ? 2 : 1;
        PummelHit($otherPlayer);
        break;
      case "5584601885"://Battle Droid Escort
        CreateBattleDroid($player);
        break;
      case "5350889336"://AT-TE Vanguard
        CreateCloneTrooper($player);
        CreateCloneTrooper($player);
        break;
      case "8096748603"://Steela Gerrera
        AddDecisionQueue("SETDQCONTEXT", $player, "Do you want to deal 2 damage to your base?");
        AddDecisionQueue("YESNO", $player, "-");
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("PASSPARAMETER", $player, "MYCHAR-0", 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player,1", 1);
        AddDecisionQueue("SEARCHDECKTOPX", $player, "8;1;include-trait-Tactic", 1);
        AddDecisionQueue("ADDHAND", $player, "-", 1);
        AddDecisionQueue("REVEALCARDS", $player, "-", 1);
        break;
      case "3680942691"://Confederate Courier
        CreateBattleDroid($player);
        break;
      case "0036920495"://Elite P-38 Starfighter
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 1 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1,$player,1", 1);
        break;
      case "6022703929"://OOM-Series Officer
        $otherPlayer = $player == 1 ? 2 : 1;
        DealDamageAsync($otherPlayer, 2, "DAMAGE", "6022703929");
        break;
      case "9479767991"://Favorable Deligate
        PummelHit($player);
        break;
      case "1083333786"://Battle Droid Legion
        CreateBattleDroid($player);
        CreateBattleDroid($player);
        CreateBattleDroid($player);
        break;
      case "0677558416"://Wartime Trade Official
        CreateBattleDroid($player);
        break;
      case "0683052393"://Hevy
        $otherPlayer = $player == 1 ? 2 : 1;
        DamagePlayerAllies($otherPlayer, 1, "0683052393", "ATTACKABILITY", arena:"Ground");
        break;
      case "0249398533"://Obedient Vanguard
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Trooper");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give +2/+2");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "ADDHEALTH,2", 1);
        AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "0249398533,PLAY", 1);
        break;
      default: break;
    }

    for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
      if($currentTurnEffects[$i+1] != $player) continue;//each friendly unit
      if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $uniqueID) continue;
      switch($currentTurnEffects[$i]) {
        case "1272825113"://In Defense of Kamino
          if(TraitContains($cardID, "Republic", $player)) CreateCloneTrooper($player);
          break;
        case "9415708584"://Pyrrhic Assault
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 2 damage to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player,1", 1);
          break;
        default: break;
      }
    }

    for($i=0; $i<count($upgrades); ++$i) {
      switch($upgrades[$i]) {
        case "6775521270"://Inspiring Mentor
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to give an experience");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
          break;
        case "2007876522"://Clone Cohort
          CreateCloneTrooper($player);
          break;
        case "7547538214"://Droid Cohort
          CreateBattleDroid($player);
          break;
      }
    }
  }
}

function CollectBounty($player, $unitCardID, $bountyCardID, $isExhausted, $owner, $reportMode=false, $capturerUniqueID="-") {
  $opponent = $player == 1 ? 2 : 1;
  $numBounties = 1;

  switch($bountyCardID) {
    case "1090660242-2"://The Client
      if($reportMode) break;
      Restore(5, $opponent);
      break;
    case "0622803599-2"://Jabba the Hutt
      if($reportMode) break;
      AddCurrentTurnEffect("0622803599-3", $opponent);
      break;
    case "f928681d36-2"://Jabba the Hutt Leader Unit
      if($reportMode) break;
      AddCurrentTurnEffect("f928681d36-3", $opponent);
      break;
    case "2178538979"://Price on Your Head
      if($reportMode) break;
      AddTopDeckAsResource($opponent);
      break;
    case "2740761445"://Guild Target
      if($reportMode) break;
      $damage = CardIsUnique($unitCardID) ? 3 : 2;
      DealDamageAsync($player, $damage, "DAMAGE", "2740761445");
      break;
    case "4117365450"://Wanted
      if($reportMode) break;
      ReadyResource($opponent);
      ReadyResource($opponent);
      break;
    case "4282425335"://Top Target
      if($reportMode) break;
      $amount = CardIsUnique($unitCardID) ? 6 : 4;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY&THEIRALLY");
      AddDecisionQueue("PREPENDLASTRESULT", $opponent, "MYCHAR-0,THEIRCHAR-0,");
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose a card to restore ".$amount, 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $opponent, "<-", 1);
      AddDecisionQueue("MZOP", $opponent, "RESTORE,".$amount, 1);
      break;
    case "3074091930"://Rich Reward
      if($reportMode) break;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY");
      AddDecisionQueue("OP", $opponent, "MZTONORMALINDICES");
      AddDecisionQueue("PREPENDLASTRESULT", $opponent, "3-", 1);
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose up to 2 units to give experience");
      AddDecisionQueue("MULTICHOOSEUNIT", $opponent, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $opponent, "MULTIGIVEEXPERIENCE", 1);
      break;
    case "1780014071"://Public Enemy
      if($reportMode) break;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose a unit to give a shield");
      AddDecisionQueue("CHOOSEMULTIZONE", $opponent, "<-", 1);
      AddDecisionQueue("MZOP", $opponent, "ADDSHIELD", 1);
      break;
    case "6135081953"://Doctor Evazan
      if($reportMode) break;
      for($i=0; $i<12; ++$i) {
        ReadyResource($opponent);
      }
      break;
    case "6420322033"://Enticing Reward
      if($reportMode) break;
      AddDecisionQueue("SEARCHDECKTOPX", $opponent, "10;2;exclude-definedType-Unit");
      AddDecisionQueue("MULTIADDHAND", $opponent, "-", 1);
      AddDecisionQueue("REVEALCARDS", $opponent, "-", 1);
      if(!CardIsUnique($unitCardID)) PummelHit($opponent);
      break;
    case "9503028597"://Clone Deserter
    case "9108611319"://Cartel Turncoat
    case "6878039039"://Hylobon Enforcer
      if($reportMode) break;
      Draw($opponent);
      break;
    case "8679638018"://Wanted Insurgents
      if($reportMode) break;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose a unit to deal 2 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $opponent, "<-", 1);
      AddDecisionQueue("MZOP", $opponent, "DEALDAMAGE,2,$player,1", 1);
      break;
    case "3503780024"://Outlaw Corona
      if($reportMode) break;
      AddTopDeckAsResource($opponent);
      break;
    case "6947306017"://Fugitive Wookie
      if($reportMode) break;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose a card to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $opponent, "<-", 1);
      AddDecisionQueue("MZOP", $opponent, "REST", 1);
      break;
    case "0252207505"://Synara San
      if ($isExhausted) {
        if ($reportMode) break;
        DealDamageAsync($player, 5, "DAMAGE", "0252207505");
        break;
      }
    case "2965702252"://Unlicensed Headhunter
      if ($isExhausted) {
        if($reportMode) break;
        Restore(5, $opponent);
        break;
      }
    case "7642980906"://Stolen Landspeeder
      if($reportMode) break;
      if($owner == $opponent) AddLayer("TRIGGER", $opponent, "7642980906");
      break;
    case "7270736993"://Unrefusable Offer
      if($reportMode) break;
      AddLayer("TRIGGER", $opponent, "7270736993", $unitCardID . "_" . $capturerUniqueID);//Passing the cardID of the bountied unit as $target in order to search for it from discard/subgroup
      break;
    case "9642863632"://Bounty Hunter's Quarry
      if($reportMode) break;
      $amount = CardIsUnique($unitCardID) ? 10 : 5;
      $deck = &GetDeck($opponent);
      if(count($deck)/DeckPieces() < $amount) $amount = count($deck)/DeckPieces();
      AddLayer("TRIGGER", $opponent, "9642863632", target:$amount);
      break;
    case "0807120264"://Death Mark
      if($reportMode) break;
      Draw($opponent);
      Draw($opponent);
      break;
    case "2151430798."://Guavian Antagonizer
      if($reportMode) break;
      Draw($opponent);
      break;
    case "0474909987"://Val
      if($reportMode) break;
      AddDecisionQueue("MULTIZONEINDICES", $opponent, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $opponent, "Choose a unit to deal 3 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $opponent, "<-", 1);
      AddDecisionQueue("MZOP", $opponent, "DEALDAMAGE,3,$opponent,1", 1);
      break;
    default:
      $numBounties--;
      break;
  }

  if ($numBounties > 0 && isBountyRecollectable($bountyCardID) && !$reportMode) {
    $bosskIndex = SearchAlliesForCard($opponent, "d2bbda6982");

    if ($bosskIndex != "") {
      $bossk = new Ally("MYALLY-" . $bosskIndex, $opponent);

      if ($bossk->NumUses() > 0) {
        AddDecisionQueue("NOALLYUNIQUEIDPASS", $opponent, $bossk->UniqueID());
        AddDecisionQueue("PASSPARAMETER", $opponent, $bountyCardID, 1);
        AddDecisionQueue("SETDQVAR", $opponent, 0, 1);
        AddDecisionQueue("SETDQCONTEXT", $opponent, "Do you want to collect the bounty for <0> again with Bossk?", 1);
        AddDecisionQueue("YESNO", $opponent, "-", 1);
        AddDecisionQueue("NOPASS", $opponent, "-", 1);
        AddDecisionQueue("PASSPARAMETER", $opponent, "MYALLY-" . $bosskIndex, 1);
        AddDecisionQueue("ADDMZUSES", $opponent, "-1", 1);
        AddDecisionQueue("COLLECTBOUNTY", $player, implode(",", [$unitCardID, $bountyCardID, $isExhausted, $owner, $capturerUniqueID]), 1);
      }
    }
  }

  return $numBounties;
}

//Bounty abilities
function CollectBounties($player, $cardID, $uniqueID, $isExhausted, $owner, $upgrades, $reportMode=false, $capturerUniqueID="-") {
  global $currentTurnEffects;
  $numBounties = 0;

  //Current turn effect bounties
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    if($currentTurnEffects[$i+2] != $uniqueID) continue;
    $numBounties += CollectBounty($player, $cardID,  $currentTurnEffects[$i], $isExhausted, $owner, $reportMode, capturerUniqueID:$capturerUniqueID);
  }

  //Upgrade bounties
  for($i=0; $i<count($upgrades); ++$i)
  {
    $numBounties += CollectBounty($player, $cardID,  $upgrades[$i], $isExhausted, $owner, $reportMode, capturerUniqueID:$capturerUniqueID);
  }

  $numBounties += CollectBounty($player, $cardID,  $cardID, $isExhausted, $owner, $reportMode, capturerUniqueID:$capturerUniqueID);
  return $numBounties;
}

function OnKillAbility($player, $uniqueID)
{
  global $combatChain, $mainPlayer, $defPlayer;
  if(count($combatChain) == 0) return;
  $attackerAlly = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
  if($attackerAlly->UniqueID() == $uniqueID && $attackerAlly->PlayerID() == $player) return;
  if($attackerAlly->LostAbilities()) return;
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
    case "5230572435"://Mace Windu (Party Crasher)
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
  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    switch($allies[$i]) {
      case "3401690666"://Relentless
        $otherPlayer = ($player == 1 ? 2 : 1);
        AddCurrentTurnEffect("3401690666", $otherPlayer, from:"PLAY");
        break;
      case "02199f9f1e"://Grand Admiral Thrawn
        AddDecisionQueue("PASSPARAMETER", $player, "MYDECK-0");
        AddDecisionQueue("MZOP", $player, "GETCARDID");
        AddDecisionQueue("SETDQVAR", $player, "0");
        AddDecisionQueue("PASSPARAMETER", $player, "THEIRDECK-0");
        AddDecisionQueue("MZOP", $player, "GETCARDID");
        AddDecisionQueue("SETDQVAR", $player, "1");
        AddDecisionQueue("SETDQCONTEXT", $player, "The top of your deck is <0> and the top of their deck is <1>.");
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
          if($aspects[$j] != "") $aspectArr[$aspects[$j]] = 1;
        }
      }
      return count($aspectArr) < 3;
    case "2843644198"://Sabine Wren
      $ally = new Ally("MYALLY-" . $index, $player);
      return !$ally->IsExhausted();
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
      case "3693364726"://Aurra Sing
        if(GetAttackTarget() == "THEIRCHAR-0" && CardArenas($attackID) == "Ground") {
          $me = new Ally("MYALLIES-" . $i, $defPlayer);
          $me->Ready();
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
    case "8918765832"://Chewbacca (Loyal Companion)
      $ally = new Ally("MYALLY-" . $index, $defPlayer);
      $ally->Ready();
      break;
    case "8228196561"://Clan Saxon Gauntlet
      AddDecisionQueue("MULTIZONEINDICES", $defPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $defPlayer, "Choose a unit to give an experience token", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $defPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $defPlayer, "ADDEXPERIENCE", 1);
      break;
    case "4541556921"://Knight of the Republic
      CreateCloneTrooper($defPlayer);
      break;
    case "3876951742"://General's Guardian
      CreateBattleDroid($defPlayer);
      break;
    default: break;
  }
}

function AddAllyPlayAbilityLayers($cardID, $from, $uniqueID = "-") {
  global $currentPlayer;
  $allies = &GetAllies($currentPlayer);
  for($i=0; $i<count($allies); $i+=AllyPieces()) {
    if(AllyHasPlayCardAbility($cardID, $uniqueID, $from, $allies[$i], $currentPlayer, $i)) AddLayer("TRIGGER", $currentPlayer, "AFTERPLAYABILITY", $cardID, $from, $allies[$i] . "," . $allies[$i+5]);
  }
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $theirAllies = &GetAllies($otherPlayer);
  for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
    if(AllyHasPlayCardAbility($cardID, $uniqueID, $from, $theirAllies[$i], $otherPlayer, $i)) AddLayer("TRIGGER", $currentPlayer, "AFTERPLAYABILITY", $cardID, $from, $theirAllies[$i] . "," . $allies[$i+5]);
  }
}

function AllyHasPlayCardAbility($playedCardID, $playedCardUniqueID, $from, $cardID, $player, $index): bool
{
  global $currentPlayer, $CS_NumCardsPlayed;
  $thisAlly = new Ally("MYALLY-" . $index, $player);
  if($thisAlly->LostAbilities($playedCardID)) return false;
  $thisIsNewlyPlayedAlly = $thisAlly->UniqueID() == $playedCardUniqueID;
  if($player == $currentPlayer) {
    switch($cardID) {
      case "415bde775d"://Hondo Ohnaka
        return $from == "RESOURCES";
      case "3434956158"://Fives
      case "0052542605"://Bossk
        return DefinedTypesContains($playedCardID, "Event");
      case "9850906885"://Maz Kanata
        return !$thisIsNewlyPlayedAlly && DefinedTypesContains($playedCardID, "Unit");
      case "3952758746"://Toro Calican
        return !$thisIsNewlyPlayedAlly && TraitContains($playedCardID, "Bounty Hunter", $player);
      case "724979d608"://Cad Bane Leader Unit
      case "0981852103"://Lady Proxima
        return !$thisIsNewlyPlayedAlly && TraitContains($playedCardID, "Underworld", $player);
      case "4088c46c4d"://The Mandalorian Leader Unit
      case "8031540027"://Dengar
        return DefinedTypesContains($playedCardID, "Upgrade");
      case "0961039929"://Colonel Yularen
        return AspectContains($playedCardID, "Command") && DefinedTypesContains($playedCardID, "Unit");
      case "5907868016"://Fighters for Freedom
        return !$thisIsNewlyPlayedAlly && AspectContains($cardID, "Aggression");
      case "3010720738"://Tobias Beckett
        return !DefinedTypesContains($playedCardID, "Unit");
      case "3f7f027abd"://Quinlan Vos
        return DefinedTypesContains($playedCardID, "Unit");
      case "0142631581"://Mas Amedda
      case "9610332938"://Poggle the Lesser
        return !$thisIsNewlyPlayedAlly && DefinedTypesContains($playedCardID, "Unit");
      case "3589814405"://tactical droid commander
        return !$thisIsNewlyPlayedAlly && DefinedTypesContains($playedCardID, "Unit") && TraitContains($playedCardID, "Separatist", $player);
      default: break;
    }
  } else {
    switch ($cardID) {
      case "5555846790"://Saw Gerrera
        return DefinedTypesContains($playedCardID, "Event", $currentPlayer);
      case "7200475001"://Ki-Adi Mundi
        return IsCoordinateActive($player) && GetClassState($currentPlayer, $CS_NumCardsPlayed) == 2;
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
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player,1", 1);
      }
      break;
    case "3434956158"://Fives
      if(DefinedTypesContains($cardID, "Event", $player)) {
        MZMoveCard($currentPlayer, "MYDISCARD:trait=Clone;definedType=Unit", "MYBOTDECK", may:true, context:"Choose a Clone unit to put on the bottom of your deck");
        AddDecisionQueue("DRAW", $player, "-", 1);
      }
      break;
    case "0961039929"://Colonel Yularen
      if(DefinedTypesContains($cardID, "Unit", $player) && AspectContains($cardID, "Command", $player)) {
        Restore(1, $player);
      }
      break;
    case "3f7f027abd"://Quinlan Vos
      $cost = CardCost($cardID);
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:maxCost=" . $cost);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 1 damage", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1,$player", 1);
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
    case "9610332938"://Poggle the Lesser
      $me = new Ally("MYALLY-" . $index, $player);
      if (!$me->IsExhausted()) {
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose if you want to create a Battle Droid token");
        AddDecisionQueue("YESNO", $player, "-");
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("PASSPARAMETER", $player, $me->MZIndex(), 1);
        AddDecisionQueue("MZOP", $player, "REST", 1);
        AddDecisionQueue("PASSPARAMETER", $player, "3463348370", 1);
        AddDecisionQueue("PLAYALLY", $player, "", 1);
      }
      break;
    case "0142631581"://Mas Amedda
      $me = new Ally("MYALLY-" . $index, $player);
      if(!$me->IsExhausted()) {
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose if you want to search for a unit");
        AddDecisionQueue("YESNO", $player, "-");
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("PASSPARAMETER", $player, $me->MZIndex(), 1);
        AddDecisionQueue("MZOP", $player, "REST", 1);
        AddDecisionQueue("SEARCHDECKTOPX", $player, "4;1;include-definedType-Unit");
        AddDecisionQueue("ADDHAND", $player, "-", 1);
        AddDecisionQueue("REVEALCARDS", $player, "-", 1);
      }
      break;
    case "8031540027"://Dengar
      if(DefinedTypesContains($cardID, "Upgrade", $player)) {
        global $CS_LayerTarget;
        $target = GetClassState($player, $CS_LayerTarget);
        AddDecisionQueue("YESNO", $player, "Do you want to deal 1 damage from " . CardLink($allies[$index], $allies[$index]) . "?");
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("PASSPARAMETER", $player, $target, 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1,$player,1", 1);
      }
      break;
    case "0981852103"://Lady Proxima
      if(TraitContains($cardID, "Underworld", $player)) {
        $otherPlayer = $player == 1 ? 2 : 1;
        DealDamageAsync($otherPlayer, 1, "DAMAGE", "0981852103");
      }
      break;
    case "3589814405"://tactical droid commander
      if(TraitContains($cardID, "Separatist", $player)) {
        AddLayer("TRIGGER", $currentPlayer, "3589814405", CardCost($cardID));
      }
      break;
    case "724979d608"://Cad Bane Leader Unit
      $cadIndex = SearchAlliesForCard($player, "724979d608");
      if($cadIndex != "") {
        $cadbane = new Ally("MYALLY-" . $cadIndex, $player);
        if(!LeaderAbilitiesIgnored() && $from != 'PLAY' && $cadbane->NumUses() > 0 && TraitContains($cardID, "Underworld", $currentPlayer)) {
          AddLayer("TRIGGER", $currentPlayer, "724979d608");
        }
      }
      break;
    case "4088c46c4d"://The Mandalorian Leader Unit
      if(!LeaderAbilitiesIgnored() && DefinedTypesContains($cardID, "Upgrade", $player)) {
        AddLayer("TRIGGER", $currentPlayer, "4088c46c4d");
      }
      break;
    case "3952758746"://Toro Calican
      $toroIndex = SearchAlliesForCard($player, "3952758746");
      if($toroIndex != "") {
        $toroCalican = new Ally("MYALLY-" . $toroIndex, $player);
        if(TraitContains($cardID, "Bounty Hunter", $currentPlayer) && $toroCalican->NumUses() > 0){
          AddLayer("TRIGGER", $currentPlayer, "3952758746");
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
  switch($abilityID)
  {

    case "7200475001"://Ki-Adi Mundi
      $opponent = $currentPlayer == 1 ? 2 : 1;
      Draw($opponent);
      Draw($opponent);
      break;
    case "5555846790"://Saw Gerrera
      DealDamageAsync($player, 2, "DAMAGE", "5555846790");
      break;
    case "4935319539"://Krayt Dragon
      AddLayer("TRIGGER", $currentPlayer, "4935319539", $cardID);
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
  global $mainPlayer, $defPlayer, $combatChainState, $CCS_WeaponIndex, $initiativePlayer;
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
          AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2,$mainPlayer,1", 1);
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
        AddDecisionQueue("FINDINDICES", $mainPlayer, "ALLTHEIRGROUNDUNITSMULTI");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose units to damage", 1);
        AddDecisionQueue("MULTICHOOSETHEIRUNIT", $mainPlayer, "<-", 1);
        AddDecisionQueue("MULTIDISTRIBUTEDAMAGE", $mainPlayer, "3,1", 1);
        break;
      case "3141660491"://The Darksaber
        $allies = &GetAllies($mainPlayer);
        for($j=0; $j<count($allies); $j+=AllyPieces()) {
          if($j == $attackerAlly->Index()) continue;
          $ally = new Ally("MYALLY-" . $j, $mainPlayer);
          if(TraitContains($ally->CardID(), "Mandalorian", $mainPlayer, $j)) $ally->Attach("2007868442");//Experience token
        }
        break;
      case "1938453783"://Armed to the Teeth
        //Adapted from Benthic Two-Tubes
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
      case "0414253215"://General's Blade
        if(TraitContains($attackerAlly->CardID(), "Jedi", $mainPlayer)) AddCurrentTurnEffect($upgrades[$i], $mainPlayer, from:"PLAY");
        break;
      default: break;
    }
  }
  if($attackerAlly->LostAbilities()) return;
  $allies = &GetAllies($mainPlayer);
  switch($allies[$attackerIndex]) {
    case "0256267292"://Benthic 'Two Tubes'
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
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2,$mainPlayer,1", 1);
      break;
    case "0dcb77795c"://Luke Skywalker Leader Unit
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give a shield");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "ADDSHIELD", 1);
      break;
    case "59cd013a2d"://Grand Moff Tarkin Leader Unit
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
      AddDecisionQueue("MZOP", $mainPlayer, "RESTORE,2", 1);
      break;
    case "8307804692"://Padme Admidala
      if(IsCoordinateActive($mainPlayer)) {
        $otherPlayer = $mainPlayer == 1 ? 2 : 1;
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give -3/-0 for this phase",1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $mainPlayer, 0, 1);
        AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "8307804692,HAND", 1);
      }
      break;
    // case "6570091935"://Tranquility
    //   // AddCurrentTurnEffect();
    //   break;
    case "51e8757e4c"://Sabine Wren
      DealDamageAsync($defPlayer, 1, "DAMAGE", "51e8757e4c");
      break;
    case "8395007579"://Fifth Brother
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Do you want to deal 1 damage to Fifth Brother?");
      AddDecisionQueue("YESNO", $mainPlayer, "-");
      AddDecisionQueue("NOPASS", $mainPlayer, "-");
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, "MYALLY-" . $attackerIndex, 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1,$mainPlayer,1", 1);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground", 1);
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1,$mainPlayer,1", 1);
      break;
    case "6827598372"://Grand Inquisitor
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:maxAttack=3");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1,$mainPlayer", 1);
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
          AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,3,$mainPlayer,1", 1);
        }
      }
      break;
    case "3417125055"://IG-11
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:damagedOnly=true;arena=Ground");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a damaged unit to deal 3 damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,3,$mainPlayer,1", 1);
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
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1,$mainPlayer,1", 1);
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
    case "6c5b96c7ef"://Emperor Palpatine Leader Unit
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to destroy");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DESTROY", 1);
      AddDecisionQueue("DRAW", $mainPlayer, "-", 1);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1,$mainPlayer", 1);
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
    case "5445166624"://Clone Dive Trooper
      if (IsCoordinateActive($mainPlayer)) {
        AddCurrentTurnEffect("5445166624", $defPlayer, from:"PLAY");
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
      $damage = $attackerAlly->Damage();
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to heal " . $damage);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "RESTORE," . $damage, 1);
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
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, $attackerAlly->UniqueID(), 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "4721657243,PLAY", 1);
      break;
    case "9951020952"://Koska Reeves
      if($attackerAlly->IsUpgraded()) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 2 damage", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2,$mainPlayer,1", 1);
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
      AddDecisionQueue("MZOP", $defPlayer, "DEALDAMAGE,2,$mainPlayer,1", 1);
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
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1,$mainPlayer", 1);
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
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,$damage,$mainPlayer,1", 1);
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
    case "2585318816"://Resolute
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 2 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $mainPlayer, "RESOLUTE", 1);
      break;
    case "1039176181"://Kalani
      $totalUnits = $mainPlayer == $initiativePlayer ? 2 : 1;
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, $attackerAlly->MZIndex(), 1);
      AddDecisionQueue("SETDQVAR", $mainPlayer, 0, 1);
      for ($i = 0; $i < $totalUnits; $i++) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MZFILTER", $mainPlayer, "dqVar=0");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give +2/+2", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("APPENDDQVAR", $mainPlayer, 0, 1);
        AddDecisionQueue("MZOP", $mainPlayer, "ADDHEALTH,2", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "1039176181,PLAY", 1);
      }
      break;
    case "5966087637"://Poe Dameron
      PummelHit($mainPlayer, may:true, context:"Choose a card to discard to defeat an upgrade (or pass)");
      DefeatUpgrade($mainPlayer, passable:true);
      PummelHit($mainPlayer, may:true, context:"Choose a card to discard to deal damage (or pass)");
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $mainPlayer, "THEIRCHAR-0,", 1);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to deal 2 damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2,$mainPlayer,1", 1);
      PummelHit($mainPlayer, may:true, context:"Choose a card to discard to make opponent discard (or pass)");
      PummelHit($defPlayer, passable:true);
      break;
    case "1320229479"://Multi-Troop Transport
      CreateBattleDroid($mainPlayer);
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
    case "7922308768"://Valiant Assault Ship
      AddCurrentTurnEffect("7922308768", $mainPlayer, 'PLAY', $attackerAlly->UniqueID());
      break;
    case "7789777396"://Mister Bones
      $hand = &GetHand($mainPlayer);
      if(count($hand) == 0) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose something to deal 3 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,3,$mainPlayer,1", 1);
      }
      break;
    case "0ee1e18cf4"://Obi-wan Kenobi
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to heal");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "RESTORE,1", 1);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1,$mainPlayer,1", 1);
      break;
    case "6412545836"://Morgan Elsbeth
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to sacrifice to draw a card");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DESTROY", 1);
      AddDecisionQueue("DRAW", $mainPlayer, "-", 1);
      break;
    case "6436543702"://Providence Destroyer
      $otherPlayer = $mainPlayer == 1 ? 2 : 1;
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:arena=Space");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give -2/-2", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $mainPlayer, 0, 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "6436543702,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "REDUCEHEALTH,2", 1);
      break;
    case "7000286964"://Vulture Interceptor Wing
      $otherPlayer = $mainPlayer == 1 ? 2 : 1;
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give -1/-1", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $mainPlayer, 0, 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "7000286964,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "REDUCEHEALTH,1", 1);
      break;
    case "2282198576"://Anakin Skywalker
      if(IsCoordinateActive($mainPlayer)) Draw($mainPlayer);
      break;
    case "6fa73a45ed"://Count Dooku Leader Unit
      AddCurrentTurnEffect("6fa73a45ed", $mainPlayer);
      break;
    case "0038286155"://Chancellor Palpatine
      global $CS_NumLeftPlay;
      if(GetClassState($mainPlayer, $CS_NumLeftPlay) > 0) {
        CreateCloneTrooper($mainPlayer);
      }
      break;
    case "0354710662"://Saw Gerrera
      if(GetHealth($mainPlayer) >= 15) {
        $otherPlayer = $mainPlayer == 1 ? 2 : 1;
        DamagePlayerAllies($otherPlayer, 1, "0354710662", "ATTACKABILITY", arena:"Ground");
      }
      break;
    case "0021045666"://San Hill
      global $CS_NumAlliesDestroyed;
      for($i=0; $i<GetClassState($mainPlayer, $CS_NumAlliesDestroyed); ++$i) {
        ReadyResource($mainPlayer);
      }
      break;
    case "1314547987"://Shaak Ti
      CreateCloneTrooper($mainPlayer);
      break;
    case "9964112400"://Rush Clovis
      $otherPlayer = $mainPlayer == 1 ? 2 : 1;
      if(NumResourcesAvailable($otherPlayer) == 0) {
        CreateBattleDroid($mainPlayer);
      }
      break;
    case "6648824001":
      ObiWansAethersprite($mainPlayer, $attackerIndex);
      break;
    case "1641175580"://Kit Fisto
      if(IsCoordinateActive($mainPlayer)) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 3 damage");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,3,$mainPlayer,1", 1);
      }
      break;
    case "12122bc0b1"://Wat Tambor
      global $CS_NumAlliesDestroyed;
      if(GetClassState($mainPlayer, $CS_NumAlliesDestroyed) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give +2/+2");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "ADDHEALTH,2", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "12122bc0b1,PLAY", 1);
      }
      break;
    case "b7caecf9a3"://Nute Gunray
      CreateBattleDroid($mainPlayer);
      break;
    case "fb7af4616c"://General Grievious
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give Sentinel");
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Droid&THEIRALLY:trait=Droid");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "WRITECHOICE", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "fb7af4616c,HAND", 1);
      break;
    case "3556557330"://Asajj Ventress
      AddDecisionQueue("YESNO", $mainPlayer, "Have you attacked with another Separatist?");
      AddDecisionQueue("NOPASS", $mainPlayer, "-");
      AddDecisionQueue("NOPASS", $mainPlayer, "-");
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, $attackerAlly->UniqueID(), 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "3556557330,PLAY", 1);
      break;
    case "2843644198"://Sabine Wren
      $card = Mill($mainPlayer, 1);
      if(!SharesAspect($card, GetPlayerBase($mainPlayer))) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 2 damage");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2,$mainPlayer,1", 1);
      }
      break;
    case "0693815329"://Cad Bane (Hostage Taker)
      RescueUnit($mainPlayer == 1 ? 2 : 1, "THEIRALLY-" . $attackerIndex, may:true);
      AddDecisionQueue("DRAW", $mainPlayer, "-", 1);
      AddDecisionQueue("DRAW", $mainPlayer, "-", 1);
      break;
    case "4ae6d91ddc"://Padme Amidala
      if(IsCoordinateActive($mainPlayer)) {
        AddDecisionQueue("SEARCHDECKTOPX", $mainPlayer, "3;1;include-trait-Republic");
        AddDecisionQueue("ADDHAND", $mainPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $mainPlayer, "-", 1);
      }
      break;
    case "3033790509"://Captain Typho
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give Sentinel");
      AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "3033790509,PLAY", 1);
      break;
    case "4489623180"://Ziro the Hutt
      ExhaustResource($defPlayer);
      break;
    case "9216621233"://Jar Jar Binks
      $targets = ["MYCHAR-0", "THEIRCHAR-0"];
      for ($i = 1; $i <= 2; $i++) {
        $prefix = $i == $mainPlayer ? "MYALLY" : "THEIRALLY";
        $allies = &GetAllies($i);
        for ($j = 0; $j < count($allies); $j += AllyPieces()) {
          $targets[] = $prefix . "-" . $j;
        }
      }
      $randomIndex = GetRandom(0, count($targets) - 1);
      $targetMZIndex = $targets[$randomIndex];
      $attackerCardLink = CardLink("9216621233", "9216621233");

      if (str_starts_with($targetMZIndex, "MYCHAR")) {
        WriteLog($attackerCardLink . " deals 2 damage to the attacker's base.");
      } else if (str_starts_with($targetMZIndex, "THEIRCHAR")) {
        WriteLog($attackerCardLink . " deals 2 damage to the defender's base.");
      } else {
        $ally = new Ally($targetMZIndex);
        WriteLog($attackerCardLink . " deals 2 damage to " . CardLink($ally->CardID(), $ally->CardID()) . ".");
      }

      AddDecisionQueue("PASSPARAMETER", $mainPlayer, $targetMZIndex);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2,$mainPlayer,1");
      break;
    case "8414572243"://Enfys Nest
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:maxAttack=" . $attackerAlly->CurrentPower() - 1);
      AddDecisionQueue("MZFILTER", $mainPlayer, "definedType=Leader");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to bounce");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "BOUNCE", 1);
      break;
    case "7979348081"://Kraken
      $allies = &GetAllies($mainPlayer);
      for($i=0; $i<count($allies); $i+=AllyPieces()) {
        if(IsToken($allies[$i])) {
          $ally = new Ally("MYALLY-" . $i, $mainPlayer);
          $ally->AddRoundHealthModifier(1);
          AddCurrentTurnEffect("7979348081", $mainPlayer, "PLAY", $ally->UniqueID());
        }
      }
      break;
    case "4776553531"://General Grievous (Trophy Collector)
      $findGrievous = SearchAlliesForCard($mainPlayer, "4776553531");
      if($findGrievous !== "") {
        $numLightsabers = 0;
        $ally=new Ally("MYALLY-$findGrievous", $mainPlayer);
        $upgrades = $ally->GetUpgrades();
        if(count($upgrades) >= 4) {
          for($i=0; $i<count($upgrades); ++$i) {
            if(TraitContains($upgrades[$i], "Lightsaber", $mainPlayer)) ++$numLightsabers;
          }
        }
        if($numLightsabers >= 4) {
          for($i=0; $i<4;++$i) {
            AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY", 1);
            AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to defeat", 1);//not optional
            AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
            AddDecisionQueue("MZOP", $mainPlayer, "DESTROY", 1);
          }
        }
      }
      break;
    case "6406254252"://Soulless One (Customized for Grievous)
      if(ControlsNamedCard($mainPlayer, "General Grievous") || SearchCount(SearchMultizone($mainPlayer, "MYALLY:trait=Droid")) > 0) {
        $mzIndices = GetMultizoneIndicesForTitle($mainPlayer, "General Grievous", true);
        $droids = explode(",", SearchMultizone($mainPlayer, "MYALLY:trait=Droid"));
        for($i=0; $i<count($droids); ++$i) {
          $ally = new Ally($droids[$i], $mainPlayer);
          if(!$ally->IsExhausted()) $mzIndices .= "," . $droids[$i];
        }
        if($mzIndices != "") {
          AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to exhaust", 1);
          AddDecisionQueue("PASSPARAMETER", $mainPlayer, $mzIndices);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $mainPlayer, "REST", 1);
          AddDecisionQueue("PASSPARAMETER", $mainPlayer, $attackerAlly->UniqueID(), 1);
          AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "6406254252,PLAY", 1);
        }
      }
      break;
    default: break;
  }
  //SpecificAllyAttackAbilities End
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

function AllyDamageTakenAbilities($player, $index, $survived, $damage, $fromCombat=false, $enemyDamage=false, $fromUnitEffect=false/*, $indirectDamage=false*/)
{
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces()) {
    switch($allies[$i]) {
      case "7022736145"://Tarfful
        if($survived && $fromCombat && TraitContains($allies[$index], "Wookiee", $player)) {
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:arena=Ground");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal " . $damage . " damage to");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,$damage,$player,1", 1);
        }
        break;
      default: break;
    }
  }
  switch($allies[$index]) {
    default: break;
  }
  $otherPlayer = $player == 1 ? 2 : 1;
  $theirAllies = &GetAllies($otherPlayer);
  for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
    switch($theirAllies[$i]) {
      case "cfdcbd005a"://Jango Fett Leader Unit
        if(!LeaderAbilitiesIgnored() && ($fromCombat || ($enemyDamage && $fromUnitEffect))) {
          PrependDecisionQueue("MZOP", $player, "REST", 1);
          PrependDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $index, 1);
          PrependDecisionQueue("NOPASS", $otherPlayer, "-");
          PrependDecisionQueue("YESNO", $otherPlayer, "if you want use Jango Fett's ability");
        }
        break;
      default: break;
    }
  }
  $theirCharacter = &GetPlayerCharacter($otherPlayer);
  for($i=0; $i<count($theirCharacter); $i+=CharacterPieces()) {
    switch($theirCharacter[$i]) {
      case "9155536481"://Jango Fett Leader
        if(!LeaderAbilitiesIgnored() && ($theirCharacter[$i+1] == 2 && ($fromCombat || ($enemyDamage && $fromUnitEffect)))) {
          PrependDecisionQueue("MZOP", $player, "REST", 1);
          PrependDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $index, 1);
          PrependDecisionQueue("EXHAUSTCHARACTER", $otherPlayer, FindCharacterIndex($otherPlayer, "9155536481"), 1);
          PrependDecisionQueue("NOPASS", $otherPlayer, "-");
          PrependDecisionQueue("YESNO", $otherPlayer, "if you want use Jango Fett's ability");
        }
        break;
      default: break;
    }
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
      case "0216922902"://The Zillo Beast
        $ally->Heal(5);
        break;
      default: break;
    }
    $ally->EndRound();
    switch ($allies[$i]) {
      case "d1a7b76ae7"://Chirrut Imwe
        if($ally->Health() <= 0) DestroyAlly($player, $i);
        break;
      default:
        break;
    }
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
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player,1", 1);
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
          AddDecisionQueue("MZOP", $otherPlayer, "DEALDAMAGE,2,$player,1", 1);
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
  AddDecisionQueue("BUTTONINPUTNOPASS", $player, "Unit,Resource", 1);
  AddDecisionQueue("SPECIFICCARD", $player, "XANADUBLOOD", 1);
}

function JabbasRancor($player, $index=-1) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:arena=Ground");
  if($index > -1) AddDecisionQueue("MZFILTER", $player, "index=MYALLY-" . $index);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose something to deal 3 damage to");
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "DEALDAMAGE,3,$player,1", 1);
  AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:arena=Ground");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose something to deal 3 damage to");
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "DEALDAMAGE,3,$player,1", 1);
}
