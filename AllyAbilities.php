<?php
require __DIR__ . "/Libraries/LayerHelpers.php";

function CreateCloneTrooper($player, $from = "-") {
  return PlayAlly("3941784506", $player, from:$from); //Clone Trooper
}

function CreateBattleDroid($player, $from = "-") {
  return PlayAlly("3463348370", $player, from:$from); //Battle Droid
}

function CreateXWing($player, $from = "-") {
  return PlayAlly("9415311381", $player, from:$from); //X-Wing
}

function CreateTieFighter($player, $from = "-") {
  return PlayAlly("7268926664", $player, from:$from); //Tie Fighter
}

// This function put an ally into play for a player, which means no when played abilities are triggered.
function PlayAlly($cardID, $player, $subCards = "-", $from = "-",
  $owner = null, $cloned = false, $playAbility = false,
  $epicAction = false, $playedAsUnit = true, $turnsInPlay = 0) {
  if($from == "TGY") {
    $owner = $player == 1 ? 2 : 1;
  }
  $uniqueID = GetUniqueId();
  $allies = &GetAllies($player);
  if(count($allies) < AllyPieces()) $allies = [];
  $allies[] = $cardID;
  $allies[] = AllyEntersPlayState($cardID, $player, $from);
  $allies[] = 0; //Damage
  $allies[] = 0; //Frozen
  $allies[] = $subCards; //Subcards
  $allies[] = $uniqueID; //Unique ID
  $allies[] = $playedAsUnit ? 1 : 0;//Played as unit
  $allies[] = 0; //Unused
  $allies[] = 1; //Ability/effect uses
  $allies[] = 0; //Round health modifier
  $allies[] = 0; //Times attacked
  $allies[] = $owner ?? $player; //Owner
  $allies[] = $turnsInPlay; //Turns in play
  $allies[] = $cloned ? 1 : 0; //Cloned
  $allies[] = 0; //Healed this turn
  $allies[] = "NA";//Arena Override
  $allies[] = $epicAction ? 1 : 0; //Epic Action
  $index = count($allies) - AllyPieces();
  CurrentEffectAllyEntersPlay($player, $index);
  CheckUniqueAlly($uniqueID);

  if ($playAbility || $cardID == "0345124206") { //Clone - Ensure that the Clone will always choose a unit to clone whenever it enters play.
    if(HasShielded($cardID, $player)) {
      AddLayer("TRIGGER", $player, "SHIELDED", "-", "-", $uniqueID);
    }
    if(HasAmbush($cardID, $player, $index, $from)) {
      AddLayer("TRIGGER", $player, "AMBUSH", "-", "-", $uniqueID);
    }

    PlayAbility($cardID, $from, 0, uniqueId:$uniqueID);
  }

  // Check if any units will be destroyed due to cascading effects
  CheckHealthAllAllies();

  return $uniqueID;
}


function DefeatUpgradeForUniqueID($subcardUniqueID, $player = "") {
  $initialPlayer = ($player == 1 || $player == 2) ? $player : 1;
  $players = [$initialPlayer, ($initialPlayer % 2) + 1];
  foreach ($players as $p) {
    $allies = &GetAllies($p);
    for ($i = 0; $i < count($allies); $i += AllyPieces()) {
      $allySubcardsDelimited = $allies[$i + 4];
      if ($allySubcardsDelimited == null || $allySubcardsDelimited == "" || $allySubcardsDelimited == "-") {
        continue;
      }

      $allySubcards = explode(",", $allySubcardsDelimited);
      for ($j = 0; $j < count($allySubcards); $j += SubcardPieces()) {
        if ($allySubcards[$j + 3] == $subcardUniqueID) {
          $ally = new Ally("MYALLY-" . $i, $p);
          $ally->DefeatUpgrade($allySubcards[$j], $subcardUniqueID);
          break;
        }
      }
    }
  }
}

function CheckHealthAllAllies() {
  foreach ([1, 2] as $player) {
    $allies = &GetAllies($player);

    for ($i = 0; $i < count($allies); $i += AllyPieces()) {
      $ally = new Ally("MYALLY-" . $i, $player);
      $defeated = $ally->DefeatIfNoRemainingHP();

      // If an ally was defeated, we don't need to check the rest of the allies because the DefeatAlly function will call this function again.
      if ($defeated) {
        break;
      }
    }
  }
}

// Returns true if there is more than one unique unit in play, false otherwise.
function CheckUniqueAlly($uniqueID, $reportMode = false) {
  $ally = new Ally($uniqueID);
  if (!$ally->Exists()) return;
  $cardID = $ally->CardID();
  $player = $ally->PlayerID();

  if (CardIsUnique($cardID) && !$ally->IsCloned()) {
    // Check if there are any other unique allies in play, ignoring cloned units
    $allies = &GetAllies($player);
    $uniqueAllyInPlay = false;
    for ($i = 0; $i < count($allies); $i += AllyPieces()) {
      $otherAlly = new Ally("MYALLY-" . $i, $player);
      if ($otherAlly->Exists() && $otherAlly->UniqueID() != $uniqueID) {
        if($otherAlly->CardID() == $cardID && !$otherAlly->IsCloned()) {
          $uniqueAllyInPlay = true;
          break;
        } else {//check for pilots
          $upgrades = $otherAlly->GetUpgrades(withMetadata:true);
          for ($j = 0; $j < count($upgrades); $j+=SubcardPieces()) {
            if ($upgrades[$j] == $cardID) {
              if (!$reportMode) {
                WriteLog(CardLink($cardID, $cardID) . " upgrade has been defeated due to unique rule.");
                DefeatUpgradeForUniqueID($upgrades[$j + 3]);
              }
              return true;
            }
          }
        }
      }
    }

    if (!$reportMode && $uniqueAllyInPlay) {
      PrependDecisionQueue("MZDESTROY", $player, "-", 1);
      PrependDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
      PrependDecisionQueue("SETDQCONTEXT", $player, "You have two of this unique unit; choose one to destroy", 1);
      PrependDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:cardID=" . $cardID, 1);
      PrependDecisionQueue("NOPASS", $player, "-");
      // Double check that there is more than one unique unit in play, in case any were defeated during the resolution.
      PrependDecisionQueue("MZOP", $player, "CHECKUNIQUEALLY");
      PrependDecisionQueue("PASSPARAMETER", $player, $uniqueID);
    }

    return $uniqueAllyInPlay;
  }
}

function LeaderAbilitiesIgnored() {
  return AnyPlayerHasAlly("4602353389");//Brain Invaders
}

function HasWhenEnemyDestroyed($cardID, $uniqueID, $numUses, $wasUnique, $wasUpgraded) {
  switch($cardID) {
    case "1664771721"://Gideon Hask
    case "b0dbca5c05"://Iden Versio Leader Unit
      return true;
    case "2649829005"://Agent Kallus
      return $wasUnique && $numUses > 0;
    case "8687233791"://Punishing One
      $ally = new Ally($uniqueID);
      return $ally->IsExhausted() && $wasUpgraded && $numUses > 0;
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
    case "3666212779"://Captain Tarkin
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
    case "9811031405"://Victor Leader
    case "5052103576"://Resistance X-Wing
      return true;
    default: return false;
  }
}

function AllyStaticHealthModifier($cardID, $index, $player, $myCardID, $myIndex, $myPlayer)
{
  $ally = new Ally("MYALLY-" . $index, $player);
  if (!$ally->Exists() || $ally->LostAbilities()) {
    return 0;
  }

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
      if($player != $myPlayer) {
        $ally = new Ally("MYALLY-" . $index, $player);
        return !$ally->IsLeader() ? -2 : 0;
      }
      break;
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
    case "9811031405"://Victor Leader
      if($index != $myIndex && $player == $myPlayer && CardArenas($cardID) == "Space") return 1;
      break;
    case "5052103576"://Resistance X-Wing
      if($index == $myIndex && $player == $myPlayer) {
        $ally = new Ally("MYALLY-" . $index, $player);
        if($ally->HasPilot()) return 1;
      }
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
      case "9097316363"://Emperor Palpatine (Master of the Dark Side)
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
      $ally = new Ally("MYALLY-" . $index, $player);
      $modifier += $ally->IsLeader() ? 1 : 0;
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

function RemoveAlly($player, $index, $removedFromPlay = true)
{
  return DestroyAlly($player, $index, true, removedFromPlay: $removedFromPlay);
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

function DestroyAlly($player, $index,
  $skipDestroy = false, $fromCombat = false, $skipRescue = false,
  $removedFromPlay = true, $skipSpecialCase = false)
{
  global $mainPlayer, $combatChainState, $CS_AlliesDestroyed, $CS_NumAlliesDestroyed, $CS_NumLeftPlay, $CCS_CachedLastDestroyed;

  $allies = &GetAllies($player);
  $ally = new Ally("MYALLY-" . $index, $player);
  $cardID = $ally->CardID();
  $owner = $ally->Owner();
  $controller = $ally->Controller();
  $uniqueID = $ally->UniqueID();
  $lostAbilities = $ally->LostAbilities();
  $isUpgraded = $ally->IsUpgraded();
  $upgrades = $ally->GetUpgrades();
  $upgradesWithOwnerData = $ally->GetUpgrades(true);
  $isExhausted = $ally->IsExhausted();
  $hasBounty = $ally->HasBounty();
  $lastPower = $ally->CurrentPower();
  $lastRemainingHP = $ally->Health();
  $isSuperlaserTech = $cardID === "8954587682";
  $isL337JTL = $cardID == "6032641503";
  $discardPileModifier = "-";
  if(!$skipDestroy && !($isL337JTL && !$skipSpecialCase)) {
    OnKillAbility($player, $uniqueID);
    $whenDestroyData="";$whenResourceData="";$whenBountiedData="";
    $shouldLayerDestroyTriggers = (HasWhenDestroyed($cardID) && !$isSuperlaserTech && !GivesWhenDestroyedToAllies($cardID))
      || UpgradesContainWhenDefeated($upgrades)
      || CurrentEffectsContainWhenDefeated($player, $uniqueID);
    if(!$lostAbilities && $shouldLayerDestroyTriggers)
      $whenDestroyData=SerializeAllyDestroyData($uniqueID,$lostAbilities,$isUpgraded,$upgrades,$upgradesWithOwnerData,$lastPower,$lastRemainingHP);
    if($isSuperlaserTech && !$lostAbilities)
      $whenResourceData=SerializeResourceData("PLAY","DOWN",0,"0","-1");
    if(!$lostAbilities && ($hasBounty || UpgradesContainBounty($upgrades)))
      $whenBountiedData=SerializeBountiesData($uniqueID, $isExhausted, $owner, $upgrades);
    if($whenDestroyData || $whenResourceData || $whenBountiedData)
      LayerDestroyTriggers($player, $cardID, $uniqueID, $whenDestroyData, $whenResourceData, $whenBountiedData);
    $wasUnique = CardIsUnique($cardID);
    $triggers = GetAllyWhenDestroyFriendlyEffects($player, $cardID, $uniqueID, $wasUnique, $isUpgraded, $upgradesWithOwnerData);
    if(count($triggers) > 0) {
      LayerFriendlyDestroyedTriggers($player, $triggers);
    }
    if($mainPlayer != $player && !$ally->LostAbilities() && GetAttackTarget() == "THEIRALLY-" . $ally->Index()) {
      $combatChainState[$CCS_CachedLastDestroyed] = $ally->Serialize();
    }
    $otherPlayer = $player == 1 ? 2 : 1;
    $triggers = GetAllyWhenDestroyTheirsEffects($mainPlayer, $otherPlayer, $uniqueID, $wasUnique, $isUpgraded, $upgradesWithOwnerData);
    if(count($triggers) > 0) {
      LayerTheirsDestroyedTriggers($player, $triggers);
    }
    IncrementClassState($player, $CS_NumAlliesDestroyed);
    AppendClassState($player, $CS_AlliesDestroyed, $cardID);
  } else if (!$skipDestroy && $isL337JTL && !$skipSpecialCase) {
    if(SearchCount(SearchAllies($player, trait:"Vehicle")) > 0) {
      AddDecisionQueue("SETDQCONTEXT", $player, "Move L3's brain to a vehicle?", 1);
      AddDecisionQueue("YESNO", $player, "-", 1);
      AddDecisionQueue("SPECIFICCARD", $player, "L337_JTL", 1);
    } else {
      DestroyAlly($player, $index, skipSpecialCase:true);
    }
    return;
  }

  if($removedFromPlay) {
    IncrementClassState($player, $CS_NumLeftPlay);
    AllyLeavesPlayAbility($player, $index);
  }

  // Discard upgrades
  for($i=0; $i<count($upgradesWithOwnerData); $i+=SubcardPieces()) {
    if($upgradesWithOwnerData[$i] == "8752877738" || $upgradesWithOwnerData[$i] == "2007868442") continue; // Skip Shield and Experience tokens
    if($upgradesWithOwnerData[$i] == "6911505367") $discardPileModifier = "TTFREE";//Second Chance
    if($upgradesWithOwnerData[$i] == "5942811090") {
      $subcard = new SubCard($ally, $i);
      LukePilotPlotArmor($subcard->Owner(), $subcard->TurnsInPlay());
    }
    if(!CardIdIsLeader($upgradesWithOwnerData[$i]))
      AddGraveyard($upgradesWithOwnerData[$i], $upgradesWithOwnerData[$i+1], "PLAY");
  }

  $captives = $ally->GetCaptives(true);

  // Discard the ally
  if(!$skipDestroy) {
    if(DefinedTypesContains($cardID, "Leader", $player)) ;//If it's a leader it doesn't go in the discard
    else if(isToken($cardID)) ; // If it's a token, it doesn't go in the discard
    else if($isSuperlaserTech) ; //SLT is auto-added to resources
    else {
      $graveyardCardID = $ally->IsCloned() ? "0345124206" : $cardID; //Clone - Replace the cloned card with the original one in the graveyard
      if($cardID == "6272475624" && !$ally->LostAbilities()) {//Stolen AT Hauler
        $discardPileModifier = $owner == $controller ? "TTOPFREE" : "TTFREE";
      }
      AddGraveyard($graveyardCardID, $owner, "PLAY", $discardPileModifier);
    }
  }

  // Remove the ally from the allies array
  for($j = $index + AllyPieces() - 1; $j >= $index; --$j) unset($allies[$j]);

  // Rescue captives
  $allies = array_values($allies);
  if(!$skipRescue) {
    for($i=0; $i<count($captives); $i+=SubcardPieces()) {
      PlayAlly($captives[$i], $captives[$i+1], from:"CAPTIVE");
    }
  }

  // Check if any units will be destroyed due to cascading effects (e.g. Coordinate)
  CheckHealthAllAllies();

  if($player == $mainPlayer) UpdateAttacker();
  else UpdateAttackTarget();
  return $cardID;
}

function CurrentEffectsContainWhenDefeated($player, $uniqueID) {
  global $currentTurnEffects;
  for($i=0;$i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
    if ($currentTurnEffects[$i+1] != $player) continue;
    if ($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $uniqueID) continue;
    switch($currentTurnEffects[$i]) {
      case "1272825113"://In Defense of Kamino
      case "9415708584": //Pyrrhic Assault
        return true;
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

function AllyTakeControl($player, $uniqueID) {
  global $currentTurnEffects;
  if ($uniqueID == "" || $uniqueID == -1) return -1;

  $otherPlayer = $player == 1 ? 2 : 1;
  $ally = new Ally($uniqueID, $otherPlayer);
  if (!$ally->Exists()) return -1;
  if($ally->IsLeader()) {
    $ally->Destroy();
    return $uniqueID;
  }

  $allyIndex = $ally->Index();
  $allyController = $ally->Controller();

  // Return if the ally is already controlled by the player
  if ($allyController == $player) {
    return $uniqueID;
  }

  $myAllies = &GetAllies($player);
  $theirAllies = &GetAllies($otherPlayer);

  // Swap current turn effects
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i+2] == -1 || $currentTurnEffects[$i+2] != $uniqueID) continue;
    $effectCardID = explode("_", $currentTurnEffects[$i])[0];

    // Skip the swap for specific cards
    $skipSwap = false;
    switch($effectCardID) {
      case "3503494534"://Regional Governor
      case "7964782056"://Qi'Ra unit
        $skipSwap = true;
        break;
      default: break;
    }

    if ($skipSwap) continue;
    $currentTurnEffects[$i+1] = $currentTurnEffects[$i+1] == 1 ? 2 : 1; // Swap players
  }

  // Swap ally
  for ($i = $allyIndex; $i < $allyIndex + AllyPieces(); $i++) {
    $myAllies[] = $theirAllies[$i];
  }
  for ($i= $allyIndex + AllyPieces() - 1; $i >= $allyIndex; $i--) {
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
  if(IsToken($cardID) && SearchAlliesForCard($player, "0038286155") != "") return 2;//Chancellor Palpatine
  switch($cardID)
  {
    case "1785627279": return 2;//Millennium Falcon
    default: return 1;
  }
}

function AllyPlayableExhausted(Ally $ally) {
  $playable = false;

  $cardID = $ally->CardID();
  switch($cardID) {
    case "5630404651"://MagnaGuard Wing Leader
    case "040a3e81f3"://Lando Leader Unit
      return $ally->NumUses() > 0;
    case "4300219753"://Fett's Firespray
    case "2471223947"://Frontline Shuttle
    case "1885628519"://Crosshair
    case "2b13cefced"://Fennec Shand Leader Unit
    case "a742dea1f1"://Han Solo Red Leader Unit
      return true;
    default: break;
  }
  if($ally->IsUpgraded()) {
    $playable = $playable || CheckForUpgradesPlayableExhausted($ally);
  }

  return $playable;
}

function TheirAllyPlayableExhausted(Ally $ally) {
  $playable = false;

  $cardID = $ally->CardID();
  switch($cardID) {
    case "3577961001"://Mercenary Gunship
      return true;
    default: break;
  }
  if($ally->IsUpgraded()) {
    $playable = $playable || CheckForUpgradesPlayableExhausted($ally);
  }

  return $playable;
}

function CheckForUpgradesPlayableExhausted(Ally $ally, $theirCard=false) {
  global $currentPlayer, $CS_NumUsesLeaderUpgrade1, $CS_NumUsesLeaderUpgrade2;
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $playableBy = $theirCard ? $otherPlayer : $currentPlayer;
  if($ally->IsUpgraded()) {
    $upgrades = $ally->GetUpgrades(withMetadata:true);
    for($i=0; $i<count($upgrades); $i+=SubcardPieces()) {
      switch($upgrades[$i]) {
        case "3eb545eb4b"://Poe Dameron JTL leader
          return $upgrades[$i+1] == $playableBy && GetClassState($playableBy, $CS_NumUsesLeaderUpgrade1) > 0;
          break;
        default: break;
      }
    }
  }

  return false;
}

function AllyDoesAbilityExhaust($cardID) {
  global $currentPlayer;
  $abilityName = GetResolvedAbilityName($cardID);
  if($abilityName == "Poe Pilot") return false;
  switch($cardID) {
    case "5630404651"://MagnaGuard Wing Leader
      return $abilityName != "Droid Attack";
    case "4300219753"://Fett's Firespray
      return $abilityName != "Exhaust";
    case "2471223947"://Frontline Shuttle
      return $abilityName != "Shuttle";
    case "1885628519"://Crosshair
      return $abilityName != "Buff";
    case "040a3e81f3"://Lando Leader Unit
      return $abilityName != "Smuggle";
    case "2b13cefced"://Fennec Shand Leader Unit
      return $abilityName != "Ambush";
    case "a742dea1f1"://Han Solo Red Leader Unit
      return $abilityName != "Play";
    default: break;
  }

  return true;
}

function TheirAllyDoesAbilityExhaust($cardID) {
  $abilityName = GetResolvedAbilityName($cardID);
  if($abilityName == "Poe Pilot") return false;
  switch($cardID) {
    case "3577961001"://Mercenary Gunship
      return $abilityName != "Take Control";
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
  global $CS_CachedLeader1EpicAction, $CS_CachedLeader2EpicAction;
  $cachedEpicAction1 = GetClassState($player, $CS_CachedLeader1EpicAction) == 1;
  $ally = new Ally("MYALLY-" . $index, $player);
  $leaderUndeployed = LeaderUndeployed($ally->CardID());
  if($leaderUndeployed != "") {
    $usedEpicAction = $ally->FromEpicAction() || $cachedEpicAction1;
    AddCharacter($leaderUndeployed, $ally->Owner(), counters:$usedEpicAction ? 1 : 0, status:1);
  }
  //Pilot leader upgrades
  $subcardsArr = $ally->GetSubcards();
  for($i=0;$i<count($subcardsArr);$i+=SubcardPieces()) {
    $subcard = new SubCard($ally, $i);
    if(CardIDIsLeader($subcard->CardID())) {
      $leaderUndeployed = LeaderUndeployed($subcard->CardID());
      if($leaderUndeployed != "") {
        $usedEpicAction = $subcard->FromEpicAction() || $cachedEpicAction1;
        AddCharacter($leaderUndeployed, $subcard->Owner(), counters:$usedEpicAction ? 1 : 0, status:1);
      }
    }
  }

  switch($ally->CardID())
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
      SearchLimitedCurrentTurnEffects("7964782056", $otherPlayer, uniqueID:$ally->UniqueID(), remove:true);
      break;
    case "3503494534"://Regional Governor
      $otherPlayer = $player == 1 ? 2 : 1;
      SearchLimitedCurrentTurnEffects("3503494534", $otherPlayer, uniqueID:$ally->UniqueID(), remove:true);
      break;
    case "4002861992"://DJ (Blatant Thief)
      $djAlly = new Ally("MYALLY-" . $index, $player);
      $resourceFound = false;
      for ($p = 1; $p <= 2; $p++) { // Iterate over both players (useful when DJ changes sides)
        $arsenal = &GetArsenal($p);
        for ($i = 0; $i < count($arsenal); $i += ArsenalPieces()) {
          if ($arsenal[$i + 6] == $djAlly->UniqueID()) {
            $otherPlayer = $p == 1 ? 2 : 1;
            $isExhausted = $arsenal[$i + 4];
            $resourceCard = RemoveResource($p, $i);
            AddResources($resourceCard, $otherPlayer, "PLAY", "DOWN", isExhausted:$isExhausted);
            $resourceFound = true;
            break;
          }
        }
        if ($resourceFound) break;
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
      case "4626028465"://Boba Fett Leader
        if($char[$i+1] == 2 && NumResourcesAvailable($otherPlayer) < NumResources($otherPlayer)) {
          $char[$i+1] = 1;
          ReadyResource($otherPlayer);
        }
        break;
      default: break;
    }
  }
}

function AllyDestroyedAbility($player, $cardID, $uniqueID, $lostAbilities, $isUpgraded, $upgrades, $upgradesWithOwnerData, $lastPower, $lastRemainingHp)
{
  global $initiativePlayer, $currentTurnEffects;

  if (!$lostAbilities) {
    $otherPlayer = $player == 1 ? 2 : 1;
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
        $options = "Deal 3 damage to opponent's base;Opponent discards a card from their hand";
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose one");
        AddDecisionQueue("CHOOSEOPTION", $player, "$cardID&$options");
        AddDecisionQueue("SHOWOPTIONS", $player, "$cardID&$options");
        AddDecisionQueue("MODAL", $player, "K2SO");
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
      case "8582806124"://The Annihilator JTL
        TheAnnihilatorJTL($player);
        break;
      case "5184505570"://Chimaera JTL
        CreateTieFighter($player);
        CreateTieFighter($player);
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
        CreateCloneTrooper($player);
        break;
      case "6404471739"://Senatorial Corvette
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
        DamagePlayerAllies($otherPlayer, 1, "0683052393", "AFTERDESTROYEDABILITY", arena:"Ground");
        break;
      case "0249398533"://Obedient Vanguard
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Trooper");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give +2/+2");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "ADDHEALTH,2", 1);
        AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "0249398533,PLAY", 1);
        break;
      case "0235116526"://Fleet Interdictor
        MZChooseAndDestroy($player, "MYALLY:maxCost=3;arena=Space&THEIRALLY:maxCost=3;arena=Space", may:true);
        break;
      case "0596500013"://Landing Shuttle
        AddDecisionQueue("SETDQCONTEXT", $player, "Do you want to draw a card?");
        AddDecisionQueue("YESNO", $player, "-");
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("DRAW", $player, "-", 1);
        break;
      case "1164297413"://Onyx Squadron Brute
        Restore(2, $player);
        break;
      case "6861397107"://First Order Stormtrooper
        IndirectDamage($otherPlayer, 1, true);
        break;
      case "8287246260"://Droid Missile Platform
        IndirectDamage($otherPlayer, 3, true);
        break;
      case "7389195577"://Zygerrian Starhopper
        IndirectDamage($otherPlayer, 2, true);
        break;
      case "1519837763"://Shuttle ST-149
        ShuttleST149($player);
        break;
      case "1397553238"://Desperate Commando
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to give -1/-1", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("SETDQVAR", $player, 0, 1);
        AddDecisionQueue("MZOP", $player, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $player, "1397553238,PLAY", 1);
        AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
        AddDecisionQueue("MZOP", $player, "REDUCEHEALTH,1", 1);
        break;
      case "8779760486"://Raddus
        AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal " . $lastPower . " damage");
        AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, DamageStringBuilder($lastPower, $player, isUnitEffect:1), 1);
        break;
      case "0097256640"://TIE Ambush Squadron
        CreateTieFighter($player);
        break;
      case "2870117979"://Executor
        CreateTieFighter($player);
        CreateTieFighter($player);
        CreateTieFighter($player);
        break;
      case "7610382003"://CR90 Relief Runner
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("PREPENDLASTRESULT", $player, "MYCHAR-0,THEIRCHAR-0,");
        AddDecisionQueue("MZFILTER", $player, "damaged=0");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit or base to heal", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "RESTORE,3", 1);
        break;
      case "7072861308"://Profundity
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose player to draw 1 card");
        AddDecisionQueue("BUTTONINPUT", $player, "Yourself,Opponent");
        AddDecisionQueue("SPECIFICCARD", $player, "PROFUNDITY", 1);
        break;
      case "5177897609"://Skyway Cloud Car
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxAttack=2&THEIRALLY:maxAttack=2");
        AddDecisionQueue("MZFILTER", $player, "leader=1");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to bounce");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "BOUNCE", 1);
      break;
      //AllyDestroyedAbility End
      default: break;
    }

    for($i=count($currentTurnEffects)-CurrentTurnPieces(); $i>=0; $i-=CurrentTurnPieces()) {
      if($currentTurnEffects[$i+1] != $player) continue;//each friendly unit
      if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $uniqueID) continue;
      $remove = false;
      switch($currentTurnEffects[$i]) {
        case "1272825113"://In Defense of Kamino
          $remove = true;
          CreateCloneTrooper($player);
          break;
        case "9415708584"://Pyrrhic Assault
          $remove = true;
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 2 damage to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player,1", 1);
          break;
        default: break;
      }
      if ($remove) {
        RemoveCurrentTurnEffect($i);
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
        case "1555775184"://Roger Roger
          AddDecisionQueue("PASSPARAMETER", $player, $upgrades[$i]);
          AddDecisionQueue("SETDQVAR", $player, "0");
          AddDecisionQueue("FINDINDICES", $player, "MYDISCARD," . $upgrades[$i]);
          AddDecisionQueue("SETDQVAR", $player, "1");
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:cardID=3463348370");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to attach <0>", 1);
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "MOVEUPGRADE", 1);
          break;
        case "3291001746"://Grim Valor
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);
          break;
        default: break;
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
      $deck = &GetDeck($player);
      if(count($deck) > 0) {
        Draw($opponent);
      }
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
      $deck = &GetDeck($player);
      if(count($deck) > 0) {//bounties are optional
        Draw($opponent);
        Draw($opponent);
      }
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

function AllyPlayedAsUpgradeAbility($cardID, $player, $targetAlly) {
  switch($cardID) {
    //Jump to Lightspeed
    case "5673100759"://Boshek
      $cards = explode(",",Mill($player, 2));
      for($i=0; $i<count($cards); ++$i) {
        WriteLog(CardLink($cards[$i], $cards[$i]) . " was discarded.");
        if(CardCostIsOdd($cards[$i])) {
          WriteLog(CardLink("5673100759", "5673100759") . "returns " . CardLink($cards[$i], $cards[$i]) . " to hand.");
          $discard = &GetDiscard($player);
          RemoveDiscard($player, count($discard) - DiscardPieces());
          AddHand($player, $cards[$i]);
        }
      }
      break;
    case "6421006753"://The Mandalorian
      $arena = $targetAlly->CurrentArena();
      AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:arena=$arena");
      AddDecisionQueue("MZFILTER", $player, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust");
      AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "REST", 1);
      break;
    case "7700932371"://Boba Fett
      $damage = TraitContains($targetAlly->CardID(), "Transport") ? 2 : 1;
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal $damage damage", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, DamageStringBuilder($damage, $player), 1);
      break;
    case "2283726359"://BB-8
      if(GetResources($player) >= 2) {
        AddDecisionQueue("SETDQCONTEXT", $player, "Pay 2 resources to ready a Resistance unit?", 1);
        AddDecisionQueue("YESNO", $player, "-", 1);
        AddDecisionQueue("NOPASS", $player, "-", 1);
        AddDecisionQueue("PAYRESOURCES", $player, "2", 1);
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:trait=Resistance&THEIRALLY:trait=Resistance", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "READY", 1);
      }
      break;
    case "6720065735"://Han Solo (Has His Moments)
      if(!$targetAlly->IsExhausted()) {
        AddDecisionQueue("YESNO", $player, "Do you want to attack with " . CardLink($targetAlly->CardID(), $targetAlly->CardID()) . "?");
        AddDecisionQueue("NOPASS", $player, "-");
        AddDecisionQueue("PASSPARAMETER", $player, $targetAlly->MZIndex(), 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $player, "6720065735", 1);
        AddDecisionQueue("MZOP", $player, "ATTACK", 1);
      }
      break;
    case "4921363233"://Wingman Victor Two
      CreateTieFighter($player);
      break;
    case "9325037410"://Iden Versio
      $targetAlly->Attach("8752877738");//Shield Token
      break;
    case "0514089787"://Frisk
      DefeatUpgrade($player, true, upgradeFilter:"maxCost=2");
      break;
    case "0524529055"://Snap Wexley
      AddDecisionQueue("SEARCHDECKTOPX", $player, "5;1;include-trait-Resistance");
      AddDecisionQueue("MULTIADDHAND", $player, "-", 1);
      AddDecisionQueue("REVEALCARDS", $player, "-", 1);
      break;
    case "1911230033"://Wingman Victor Three
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $player, "index=" . $targetAlly->MZIndex());
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give an experience");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
      break;
    case "0511138070"://Astromech Pilot
      AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:damagedOnly=true&THEIRALLY:damagedOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to heal 2 damage");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("MZOP", $player, "RESTORE,2", 1);
      break;
    default: break;
  }
}

function AllyStartRegroupPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex

  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    $ally = new Ally("MYALLY-" . $i, $player);
    if ($ally->LostAbilities()) continue;

    // Check ally abilities
    switch ($allies[$i]) {
      case "d1a7b76ae7"://Chirrut Imwe
        if ($ally->Health() <= 0) {
          DestroyAlly($player, $i);
        }
        break;
      case "0216922902"://The Zillo Beast
        $ally->Heal(5);
        break;
      case "4240570958"://Fireball
        $ally->DealDamage(1);
        break;
      case "7489502985"://Contracted Hunter
        $ally->Destroy();
        break;
      default: break;
    }

    // Check upgrades abilities
    $upgrades = $ally->GetUpgrades();
    for ($j = 0; $j < count($upgrades); $j++) {
      $upgradeCardID = $upgrades[$j];
      $processedUpgrades = [];

      // Prevent duplicated upgrades
      if (in_array($upgradeCardID, $processedUpgrades)) {
        continue;
      }

      switch($upgradeCardID) {
        case "3962135775"://Foresight
          AddDecisionQueue("INPUTCARDNAME", $player, "<-");
          AddDecisionQueue("SETDQVAR", $player, "0", 1);
          AddDecisionQueue("PASSPARAMETER", $player, "MYDECK-0", 1);
          AddDecisionQueue("MZOP", $player, "GETCARDID", 1);
          AddDecisionQueue("SETDQVAR", $player, "1", 1);
          AddDecisionQueue("MZOP", $player, "GETCARDTITLE", 1);
          AddDecisionQueue("NOTEQUALPASS", $player, "{0}");
          AddDecisionQueue("DRAW", $player, "-", 1);
          AddDecisionQueue("REVEALCARDS", $player, "-", 1);
          AddDecisionQueue("ELSE", $player, "-");
          AddDecisionQueue("SETDQCONTEXT", $player, "The top card of your deck is <1>");
          AddDecisionQueue("OK", $player, "-");
          break;
        default: break;
      }

      $processedUpgrades[] = $upgradeCardID;
    }
  }
}

function AllyEndRegroupPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex
  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    $ally = new Ally("MYALLY-" . $i, $player);
    if ($ally->LostAbilities()) continue;

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
      case "9720757803"://Rampart
        if($ally->CurrentPower() < 4) {
          AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $i);
          AddDecisionQueue("MZOP", $player, "REST");
        }
        break;
      default: break;
    }
  }
}

function AllyStartActionPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex

  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    $ally = new Ally("MYALLY-" . $i, $player);
    if ($ally->LostAbilities()) continue;

    switch($allies[$i]) {
      case "3401690666"://Relentless
        $otherPlayer = ($player == 1 ? 2 : 1);
        AddCurrentTurnEffect("3401690666", $otherPlayer, from:"PLAY");
        break;
      case "02199f9f1e"://Grand Admiral Thrawn Leader Unit
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

function AllyEndActionPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex
}

function AllyCanBeAttackTarget($player, $index, $cardID)
{
  global $currentTurnEffects;
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    $ally = new Ally("MYALLY-" . $index, $player);
    if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $ally->UniqueID()) continue;
    switch($currentTurnEffects[$i]) {
      case "2012334456"://On Top of Things
        return false;
      default: break;
    }
  }
  switch($cardID)
  {
    case "3646264648"://Sabine Wren (Explosive Artist)
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

// function AllyEnduranceCounters($cardID)//FAB
// {
//   switch($cardID) {
//     case "UPR417": return 1;
//     default: return 0;
//   }
// }

//FAB
// function AllyDamagePrevention($player, $index, $damage)
// {
//   $allies = &GetAllies($player);
//   $canBePrevented = CanDamageBePrevented($player, $damage, "");
//   if($damage > $allies[$index+6])
//   {
//     if($canBePrevented) $damage -= $allies[$index+6];
//     $allies[$index+6] = 0;
//   }
//   else
//   {
//     $allies[$index+6] -= $damage;
//     if($canBePrevented) $damage = 0;
//   }
//   return $damage;
// }

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
      case "20f21b4948"://Jyn Erso Leader Unit
        AddCurrentTurnEffect("20f21b4948", $defPlayer);
        break;
      case "8107876051"://Enfys Nest (Marauder)
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
          $me = new Ally("MYALLY-" . $i, $defPlayer);
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
    case "6300552434"://Gold Leader
      AddCurrentTurnEffect("6300552434", $mainPlayer, from:"PLAY");
      break;
    default: break;
  }
}

function AddAllyPlayCardAbilityLayers($cardID, $from, $uniqueID = "-", $resourcesPaid=-1) {
  global $currentPlayer;

  foreach ([1, 2] as $p) {
    $allies = &GetAllies($p);
    for ($i = 0; $i < count($allies); $i += AllyPieces()) {
      if (AllyHasPlayCardAbility($cardID, $uniqueID, $from, $allies[$i], $p, $i, $resourcesPaid)) {
        $targetData = implode(",", [$allies[$i], $p, $allies[$i + 8], $cardID]); // $cardID, $player, $numUses, $playedCardID
        AddLayer("TRIGGER", $currentPlayer, "ALLYPLAYCARDABILITY", $targetData, $from, $allies[$i + 5]);
      }
    }
  }
}

function AllyHasPlayCardAbility($playedCardID, $playedCardUniqueID, $from, $cardID, $player, $index, $resourcesPaid): bool
{
  global $currentPlayer, $CS_NumCardsPlayed, $CS_PlayedAsUpgrade;
  $thisAlly = new Ally("MYALLY-" . $index, $player);
  if($thisAlly->LostAbilities($playedCardID)) return false;
  $thisIsNewlyPlayedAlly = $thisAlly->UniqueID() == $playedCardUniqueID;

  // When you play a card
  if ($player == $currentPlayer) {
    switch($cardID) {
      case "415bde775d"://Hondo Ohnaka Leader Unit
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
        return DefinedTypesContains($playedCardID, "Upgrade") || PilotWasPlayed($currentPlayer, $playedCardID);
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
        return !$thisIsNewlyPlayedAlly && !$thisAlly->IsExhausted() && DefinedTypesContains($playedCardID, "Unit");
      case "3589814405"://tactical droid commander
        return !$thisIsNewlyPlayedAlly && DefinedTypesContains($playedCardID, "Unit") && TraitContains($playedCardID, "Separatist", $player);
      default: break;
    }
  } else { // When an opponent plays a card
    switch ($cardID) {
      case "5555846790"://Saw Gerrera
        return DefinedTypesContains($playedCardID, "Event", $currentPlayer);
      case "7200475001"://Ki-Adi Mundi
        return IsCoordinateActive($player) && $thisAlly->NumUses() > 0 && GetClassState($currentPlayer, $CS_NumCardsPlayed) == 2;
      case "4935319539"://Krayt Dragon
        return true;
      case "0199085444"://Lux Bonteri
        return $resourcesPaid < CardCost($playedCardID);
      default: break;
    }
  }

  // When anyone plays a card
  switch($cardID) {
    default: break;
  }

  return false;
}

function AllyPlayCardAbility($player, $cardID, $uniqueID, $numUses, $playedCardID, $from) {
  global $currentPlayer, $CS_PlayedAsUpgrade;
  $otherPlayer = $player == 1 ? 2 : 1;
  $ally = new Ally($uniqueID); // Important: ally could be defeated by the time this function is called. So, use $ally->Exists() to check if the ally still exists.

  // When you play a card
  if ($player == $currentPlayer) {
    switch($cardID) {
      case "415bde775d"://Hondo Ohnaka Leader Unit
        if($from == "RESOURCES") {
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to give an experience token", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
        }
        break;
      case "0052542605"://Bossk
        if(DefinedTypesContains($playedCardID, "Event", $player)) {
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 2 damage to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,2,$player,1", 1);
        }
        break;
      case "3434956158"://Fives
        if(DefinedTypesContains($playedCardID, "Event", $player)) {
          MZMoveCard($player, "MYDISCARD:trait=Clone;definedType=Unit", "MYBOTDECK", may:true, context:"Choose a Clone unit to put on the bottom of your deck");
          AddDecisionQueue("DRAW", $player, "-", 1);
        }
        break;
      case "0961039929"://Colonel Yularen
        if(DefinedTypesContains($playedCardID, "Unit", $player) && AspectContains($playedCardID, "Command", $player)) {
          Restore(1, $player);
        }
        break;
      case "3f7f027abd"://Quinlan Vos
        $cost = CardCost($playedCardID);
        AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:maxCost=" . $cost);
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal 1 damage", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1,$player", 1);
        break;
      case "9850906885"://Maz Kanata
        if ($ally->Exists() && DefinedTypesContains($playedCardID, "Unit", $player)) {
          $ally->Attach("2007868442");//Experience token
        }
        break;
      case "5907868016"://Fighters for Freedom
        if(AspectContains($playedCardID, "Aggression", $player)) {
          DealDamageAsync($otherPlayer, 1, "DAMAGE", "5907868016");
          WriteLog(CardLink("5907868016", "5907868016") . " is dealing 1 damage.");
        }
        break;
      case "9610332938"://Poggle the Lesser
        if ($ally->Exists() && !$ally->IsExhausted()) {
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose if you want to create a Battle Droid token");
          AddDecisionQueue("YESNO", $player, "-");
          AddDecisionQueue("NOPASS", $player, "-");
          AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex(), 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);
          AddDecisionQueue("PASSPARAMETER", $player, "3463348370", 1);
          AddDecisionQueue("PLAYALLY", $player, "", 1);
        }
        break;
      case "0142631581"://Mas Amedda
        if($ally->Exists() && !$ally->IsExhausted()) {
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose if you want to search for a unit");
          AddDecisionQueue("YESNO", $player, "-");
          AddDecisionQueue("NOPASS", $player, "-");
          AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex(), 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);
          AddDecisionQueue("SEARCHDECKTOPX", $player, "4;1;include-definedType-Unit");
          AddDecisionQueue("ADDHAND", $player, "-", 1);
          AddDecisionQueue("REVEALCARDS", $player, "-", 1);
        }
        break;
      case "8031540027"://Dengar
        if(DefinedTypesContains($playedCardID, "Upgrade", $player) || PilotWasPlayed($player, $playedCardID)) {
          global $CS_LayerTarget;
          $target = GetClassState($player, $CS_LayerTarget);
          AddDecisionQueue("YESNO", $player, "if you want to deal 1 damage from " . CardLink($cardID, $cardID) . "?");
          AddDecisionQueue("NOPASS", $player, "-");
          AddDecisionQueue("PASSPARAMETER", $player, $target, 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1,$player,1", 1);
        }
        break;
      case "0981852103"://Lady Proxima
        if(TraitContains($playedCardID, "Underworld", $player)) {
          DealDamageAsync($otherPlayer, 1, "DAMAGE", "0981852103");
        }
        break;
      case "3589814405"://Tactical Droid Commander
        if(TraitContains($playedCardID, "Separatist", $player)) {
          $playedCardCost = CardCost($playedCardID);
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:maxCost=".$playedCardCost);
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);
        }
        break;
      case "724979d608"://Cad Bane Leader Unit
        if (!LeaderAbilitiesIgnored() && $from != 'PLAY' && $numUses > 0 && TraitContains($playedCardID, "Underworld", $player)) {
          AddDecisionQueue("YESNO", $player, "if you want use Cad Bane's ability");
          AddDecisionQueue("NOPASS", $player, "-");
          AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY", 1);
          AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to deal 2 damage to", 1);
          AddDecisionQueue("CHOOSEMULTIZONE", $otherPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $otherPlayer, DamageStringBuilder(2, $player, isUnitEffect:1), 1);

          if ($ally->Exists()) {
            AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex(), 1);
            AddDecisionQueue("ADDMZUSES", $player, "-1", 1);
          }
        }
        break;
      case "4088c46c4d"://The Mandalorian Leader Unit
        if(!LeaderAbilitiesIgnored() && DefinedTypesContains($playedCardID, "Upgrade", $player) || PilotWasPlayed($player, $playedCardID)) {
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:maxHealth=6");
          AddDecisionQueue("MZFILTER", $player, "status=1");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);
        }
        break;
      case "3952758746"://Toro Calican
        if (TraitContains($playedCardID, "Bounty Hunter", $player) && $numUses > 0){
          AddDecisionQueue("YESNO", $player, "if you want to use Toro Calican's ability");
          AddDecisionQueue("NOPASS", $player, "-");
          AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . LastAllyIndex($player), 1);
          AddDecisionQueue("MZOP", $player, DamageStringBuilder(1, $player), 1);

          if ($ally->Exists()) {
            AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex(), 1);
            AddDecisionQueue("MZOP", $player, "READY", 1);
            AddDecisionQueue("ADDMZUSES", $player, "-1", 1);
          }
        }
        break;
      case "3010720738"://Tobias Beckett
        if ($numUses > 0 && !DefinedTypesContains($playedCardID, "Unit", $player)) {
          $playedCardCost = CardCost($playedCardID);
          AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY:maxCost=" . $playedCardCost . "&THEIRALLY:maxCost=" . $playedCardCost);
          AddDecisionQueue("MZFILTER", $player, "status=1", 1);
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to exhaust with Tobias Beckett", 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "REST", 1);

          if ($ally->Exists()) {
            AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex(), 1);
            AddDecisionQueue("ADDMZUSES", $player, "-1", 1);
          }
        }
        break;
      default: break;
    }
  } else { // When an oponent plays a card
    switch($cardID) {
      case "7200475001"://Ki-Adi Mundi
        if ($numUses > 0) {
          AddDecisionQueue("YESNO", $player, "if you want use Ki-Adi Mundi's ability");
          AddDecisionQueue("NOPASS", $player, "-");
          AddDecisionQueue("DRAW", $player, "-", 1);
          AddDecisionQueue("DRAW", $player, "-", 1);

          if ($ally->Exists()) {
            AddDecisionQueue("PASSPARAMETER", $player, $ally->MZIndex(), 1);
            AddDecisionQueue("ADDMZUSES", $player, "-1", 1);
          }
        }
        break;
      case "5555846790"://Saw Gerrera
        DealDamageAsync($otherPlayer, 2, "DAMAGE", "5555846790");
        break;
      case "4935319539"://Krayt Dragon
        if ($playedCardID == "0345124206") break; //Clone - When Clone is played, Krayt Dragon's ability is not triggered. It'll be triggered later after the Clone's resolution with the new printed attributes.
        $damage = CardCost($playedCardID);
        AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:arena=Ground");
        AddDecisionQueue("PREPENDLASTRESULT", $player, "THEIRCHAR-0,");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to deal " . $damage . " damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, DamageStringBuilder($damage, $player, isUnitEffect:1), 1);
        break;
      case "0199085444"://Lux Bonteri
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to ready or exhaust");
        AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $player, "LUXBONTERI", 1);
        break;
      default: break;
    }
  }

  // When anyone plays a card
  switch($cardID) {
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
  global $mainPlayer, $defPlayer, $combatChainState, $CCS_WeaponIndex, $initiativePlayer, $currentTurnEffects;
  $attackerIndex = $combatChainState[$CCS_WeaponIndex];
  $attackerAlly = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
  if($attackerAlly->LostAbilities()) return;
  // Upgrade Abilities
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
          DamagePlayerAllies($defPlayer, 1, "0160548661", "ATTACKABILITY", arena:"Ground");
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
      case "5016817239"://Superheavy Ion Cannon
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:leader=0");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a non-leader unit to exhaust");
        AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "REST", 1);
        AddDecisionQueue("SPECIFICCARD", $mainPlayer, "SUPERHEAVYIONCANNON", 1);
        break;
      case "0414253215"://General's Blade
        if(TraitContains($attackerAlly->CardID(), "Jedi", $mainPlayer)) AddCurrentTurnEffect($upgrades[$i], $mainPlayer, from:"PLAY");
        break;
      //Jump to Lightspeed
      case "3f0b5622a7"://Asajj pilot Leader Unit
        AsajjVentressIWorkAlone($mainPlayer);
        break;
      case "3282713547"://Dengar pilot
        $damage = TraitContains($attackerAlly->CardID(), "Underworld", $mainPlayer) ? 3 : 2;
        IndirectDamage($defPlayer, $damage, true);
        break;
      case "4573745395"://Bossk pilot
        if(IsAllyAttackTarget()) {
          $target = GetAttackTarget();
          $ally = new Ally($target, $defPlayer);
          $ally->Exhaust();
          $ally->DealDamage(1, fromUnitEffect:true);
        }
        break;
      case "6414788e89"://Wedged Antilles pilot Leader Unit
        AddCurrentTurnEffect($upgrades[$i], $mainPlayer, from:"PLAY");
        break;
      case "3475471540"://Cassian Andor
        $discarded = Mill($defPlayer, 1);
        if($discarded != "" && CardCost($discarded) <= 3) Draw($mainPlayer);
        break;
      case "11e54776e9"://Luke Skywalker pilot Leader Unit
        if(TraitContains($attackerAlly->CardID(), "Fighter", $mainPlayer)) {
          AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 3 damage to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,3,$mainPlayer,1", 1);
        }
        break;
      case "1039444094"://Paige Tico pilot
        $attackerAlly->Attach("2007868442");//Experience token
        $attackerAlly->DealDamage(1, enemyDamage:false, fromUnitEffect:true);
        break;
      case "9981313319"://Twin Laser Turret
        $arena = $attackerAlly->CurrentArena();
        $targetZones = "ALLTHEIRGROUNDUNITSMULTILIMITED,2";
        if($arena == "Space") $targetZones = "ALLTHEIRSPACEUNITSMULTILIMITED,2";
        AddDecisionQueue("FINDINDICES", $mainPlayer, $targetZones);
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose up to 2 units to damage", 1);
        AddDecisionQueue("MULTICHOOSETHEIRUNIT", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "MAPTHEIRINDICES", 1);
        AddDecisionQueue("MULTIDAMAGE", $mainPlayer, DamageStringBuilder(1, $mainPlayer, isUnitEffect:1), 1);
        break;
      case "c1700fc85b"://Kazuda pilot Leader unit
        KazudaXionoBestPilotInTheGalaxy($mainPlayer);
        break;
      case "d8a5bf1a15"://Major Vonreg pilot Leader unit
        $arena = $attackerAlly->CurrentArena();
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=$arena&THEIRALLY:arena=$arena");
        AddDecisionQueue("MZFILTER", $mainPlayer, "uniqueID=" . $attackerAlly->UniqueID());
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give +1/+0");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "d8a5bf1a15,PLAY", 1);
        break;
      case "0086781673"://Tam Ryvora pilot
        $arena = $attackerAlly->CurrentArena();
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:arena=$arena");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give -1/-1", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $mainPlayer, 0, 1);
        AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "0086781673,PLAY", 1);
        AddDecisionQueue("PASSPARAMETER", $mainPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "REDUCEHEALTH,1", 1);
        break;
      case "2532510371"://Trace Martez pilot
        for($i=0; $i<2;++$i) {
          AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY", $i == 0 ? 0 : 1);
          AddDecisionQueue("MZFILTER", $mainPlayer, "damaged=0");
          AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to restore 1 (Remaining: " . (2-$i) . ")", $i == 0 ? 0 : 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $mainPlayer, "RESTORE,1", 1);
        }
        break;
      case "6079255999"://Darth Vader pilot unit
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $mainPlayer, "VADER_UNIT_JTL", 1);
        break;
      default: break;
    }
  }

  // Ally Abilities
  $attackerCardID = $attackerAlly->CardID();
  switch($attackerCardID) {
    case "0256267292"://Benthic 'Two Tubes'
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:aspect=Aggression");
      AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerIndex);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give Raid 2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "0256267292,HAND", 1);
      break;
    case "02199f9f1e"://Grand Admiral Thrawn Leader Unit
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
    case "0ca1902a46"://Darth Vader Leader Unit
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
    case "8240629990"://Avenger
      MZChooseAndDestroy($defPlayer, "MYALLY", filter: "leader=1", context: "Choose a unit to destroy");
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
    case "6570091935"://Tranquility
      AddCurrentTurnEffect("6570091935", $mainPlayer, from:"PLAY");
      AddCurrentTurnEffect("6570091935", $mainPlayer, from:"PLAY");
      AddCurrentTurnEffect("6570091935", $mainPlayer, from:"PLAY");
      break;
    case "51e8757e4c"://Sabine Wren Leader Unit
      DealDamageAsync($defPlayer, 1, "DAMAGE", "51e8757e4c");
      break;
    case "3389903389"://Black One JTL
      if (ControlsNamedCard($mainPlayer, "Poe Dameron")) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, DamageStringBuilder(1, $mainPlayer, isUnitEffect:true), 1);
      }
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
    case "6827598372"://Grand Inquisitor Leader Unit
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
    case "4156799805"://Boba Fett (Disintegrator)
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
    case "3646264648"://Sabine Wren (Explosives Artist)
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
    case "5e90bd91b0"://Han Solo Leader Unit
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
      AddRoundEffect("2522489681", $mainPlayer, from:"PLAY");
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
    case "5511838014"://Kuiil
      $card = Mill($mainPlayer, 1);
      if(SharesAspect($card, GetPlayerBase($mainPlayer))) {
        WriteLog("Kuiil returns " . CardLink($card, $card) . " to hand");
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
    case "e091d2a983"://Rey Leader Unit
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
    case "0398102006"://The Invisible Hand
      $totalUnits = SearchCount(SearchAllies($mainPlayer, trait:"Separatist"));
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, "-");
      for ($i = 0; $i < $totalUnits; $i++) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Separatist",1 );
        AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index(), 1);
        AddDecisionQueue("MZFILTER", $mainPlayer, "status=1",1 );
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to exhaust", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "REST", 1);
        AddDecisionQueue("PASSPARAMETER", $mainPlayer, "THEIRCHAR-0", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1,$mainPlayer,1", 1);
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
      $optionsOrder = ["First", "Second", "Third"];
      $options = "Deal 2 damage to a unit or base;Defeat an upgrade;An opponent discards a card from their hand";
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, "-");
      AddDecisionQueue("SETDQVAR", $mainPlayer, "0");
      for ($i = 0; $i < 3; ++$i) {
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose Poe Dameron's " . $optionsOrder[$i] . " Ability (or pass)", 1);
        AddDecisionQueue("MAYCHOOSEOPTION", $mainPlayer, "$attackerCardID&$options&{0}", 1);
        AddDecisionQueue("APPENDDQVAR", $mainPlayer, "0", 1);
        AddDecisionQueue("SHOWOPTIONS", $mainPlayer, "$attackerCardID&$options", 1);
        AddDecisionQueue("MODAL", $mainPlayer, "POEDAMERON", 1);
      }
      break;
    case "1320229479"://Multi-Troop Transport
      CreateBattleDroid($mainPlayer);
      break;
    case "8862896760"://Maul
      if (GetAttackTarget() != "THEIRCHAR-0") {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Underworld");
        AddDecisionQueue("MZFILTER", $mainPlayer, "index=MYALLY-" . $attackerAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to take the damage for Maul", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "8862896760,HAND", 1);
      }
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
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, "{1}", 1);//TODO: this is bugged. sending "Ground" instead of "THEIRALLY-*"
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
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:damagedOnly=true&THEIRALLY:damagedOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to heal 1 damage", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $mainPlayer, "0", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "RESTORE,1", 1);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY", 1);
      AddDecisionQueue("MZFILTER", $mainPlayer, "index={0}", 1);
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage", 1);
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
      $otherPlayer = $mainPlayer == 1 ? 2 : 1;
      if(GetClassState($mainPlayer, $CS_NumLeftPlay) > 0 || GetClassState($otherPlayer, $CS_NumLeftPlay) > 0) {
        CreateCloneTrooper($mainPlayer);
      }
      break;
    case "0354710662"://Saw Gerrera (Resistance Is Not Terrorism)
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
    case "3556557330"://Asajj Ventress (Count Dooku's Assassin)
      if(AnotherSeparatistUnitHasAttacked($attackerAlly->UniqueID(), $mainPlayer)) {
        AddDecisionQueue("PASSPARAMETER", $mainPlayer, $attackerAlly->UniqueID(), 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "3556557330,PLAY", 1);
      }
      break;
    case "f8e0c65364"://Asajj Ventress (deployed leader)
      global $CS_NumEventsPlayed;
      if(GetClassState($mainPlayer, $CS_NumEventsPlayed) > 0) AddCurrentTurnEffect("f8e0c65364", $mainPlayer, "PLAY");
      break;
    case "2843644198"://Sabine Wren (You Can Count On Me)
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Do you want to discard a card from your deck?");
      AddDecisionQueue("YESNO", $mainPlayer, "-");
      AddDecisionQueue("NOPASS", $mainPlayer, "-");
      AddDecisionQueue("SPECIFICCARD", $mainPlayer, "SABINEWREN_TWI", 1);
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
    case "8414572243"://Enfys Nest (Champion of Justice)
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:maxAttack=" . $attackerAlly->CurrentPower() - 1);
      AddDecisionQueue("MZFILTER", $mainPlayer, "leader=1");
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
    //Jump to Lightspeed
    case "2778554011"://General Draven
      CreateXWing($mainPlayer);
      break;
    case "2657417747"://Quasar TIE Carrier
      CreateTieFighter($mainPlayer);
      break;
    case "6390089966"://Banshee
      AddDecisionQueue("PASSPARAMETER", $mainPlayer, $attackerAlly->MZIndex());
      AddDecisionQueue("MZOP", $mainPlayer, "GETDAMAGE");
      AddDecisionQueue("SETDQVAR", $mainPlayer, 0);
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal {0} damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, DamageStringBuilder("{0}", $mainPlayer, isUnitEffect:1), 1);
      break;
    case "7831643253"://Red Squadron Y-Wing
      IndirectDamage($defPlayer, 3, true);
      break;
    case "6861397107"://First Order Stormtrooper
      IndirectDamage($defPlayer, 1, true);
      break;
    case "3504944818"://Tie Bomber
      IndirectDamage($defPlayer, 3, true);
      break;
    case "1990020761"://Shuttle Tydirium
      $card = Mill($mainPlayer, 1);
      if(CardCostIsOdd($card)) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $mainPlayer, "index=" . $attackerAlly->MZIndex());
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to give an experience");
        AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "6648978613"://Fett's Firespray (Feared Silhouettte)
      $damage = ControlsNamedCard($mainPlayer, "Boba Fett") ? 2 : 1;
      IndirectDamage($defPlayer, $damage, true);
      break;
    case "4573745395"://Bossk
      if(IsAllyAttackTarget()) {
        $target = GetAttackTarget();
        $ally = new Ally($target, $defPlayer);
        $ally->Exhaust();
        $ally->DealDamage(1, fromUnitEffect:true);
      }
      break;
    case "3278986026"://Rafa Martez
      RafaMartezJTL($mainPlayer);
      break;
    case "7192849828"://Mist Hunter
      global $CS_NumBountyHuntersPlayed;
      global $CS_NumPilotsPlayed;
      if(GetClassState($mainPlayer, $CS_NumPilotsPlayed) > 0 || GetClassState($mainPlayer, $CS_NumBountyHuntersPlayed) > 0) {
        Draw($mainPlayer);
      }
      break;
    case "9611596703"://Allegiant General Pryde
      if($initiativePlayer == $mainPlayer) {
        IndirectDamage($defPlayer, 2, true);
      }
      break;
    case "590b638b18"://Rose Tico leader unit
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Vehicle&THEIRALLY:trait=Vehicle");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a Vehicle unit to heal 2 damage");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "RESTORE,2", 1);
      break;
    case "8500401413"://Red Five
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:damagedOnly=1&THEIRALLY:damagedOnly=1");
      AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 2 damage");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,2,$mainPlayer,1", 1);
      break;
    case "36859e7ec4"://Admiral Ackbar leader unit
      AdmiralAckbarItsATrap($mainPlayer, flipped:true);
      break;
    case "ccf9474416"://Admiral Holdo leader unit
      AdmiralHoldoWereNotAlone($mainPlayer, flipped:true);
      break;
    default: break;
    case "fda7bdc316"://Captain Phasma
      global $CS_NumFirstOrderPlayed;
      if(GetClassState($mainPlayer, $CS_NumFirstOrderPlayed) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 1 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1,$mainPlayer,1", 1);
        AddDecisionQueue("PASSPARAMETER", $mainPlayer, "THEIRCHAR-0", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,1,$mainPlayer,1", 1);
      }
      break;
    case "0524529055"://Snap Wexley
      AddCurrentTurnEffect("0524529055-A", $mainPlayer, from:"PLAY");
      break;
    case "7325248681"://Sabine's Masterpiece
      if(SearchCount(SearchAllies($mainPlayer, aspect:"Vigilance")) > 0) {
        Restore(2, $mainPlayer);
      }
      if(SearchCount(SearchAllies($mainPlayer, aspect:"Command")) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to give experience");
        AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "ADDEXPERIENCE", 1);
      }
      if(SearchCount(SearchAllies($mainPlayer, aspect:"Aggression")) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("PREPENDLASTRESULT", $mainPlayer, "THEIRCHAR-0,", 1);
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to deal 1 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, DamageStringBuilder(1, $mainPlayer, 1), 1);
      }
      if(SearchCount(SearchAllies($mainPlayer, aspect:"Cunning")) > 0) {
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose one");
        AddDecisionQueue("BUTTONINPUT", $mainPlayer, "Exhaust_Theirs,Ready_Mine", 1);
        AddDecisionQueue("SPECIFICCARD", $mainPlayer, "SABINES_MP_CUNNING", 1);
      }
      break;
    case "2870117979"://Executor
      CreateTieFighter($mainPlayer);
      CreateTieFighter($mainPlayer);
      CreateTieFighter($mainPlayer);
      break;
    case "6228218834"://Tactical Heavy Bomber
      AddCurrentTurnEffect("6228218834", $mainPlayer, 'PLAY');
      IndirectDamage($defPlayer, $attackerAlly->CurrentPower(), true);
      break;
    case "4147863169"://Relentless Firespray
      if($attackerAlly->Exists() && $attackerAlly->NumUses() > 0) {
        $attackerAlly->Ready();
        $attackerAlly->SumNumUses(-1);
      }
      break;
    case "3427170256"://Captain Phasma Unit
      CaptainPhasmaUnit($mainPlayer, $attackerAlly->Index());
      break;
    case "2922063712"://Sith Trooper
      AddCurrentTurnEffect("2922063712", $mainPlayer, 'PLAY', $attackerAlly->UniqueID());
      break;
    case "c1700fc85b"://Kazuda pilot Leader unit
      KazudaXionoBestPilotInTheGalaxy($mainPlayer);
      break;
    case "3310100725"://Insurgent Saboteurs
      DefeatUpgrade($mainPlayer, true);
      break;
    case "7232609585"://Supporting Eta-2
      AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "WRITECHOICE", 1);
      AddDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "7232609585,HAND", 1);
      break;
  }

  // Current Effect Abilities
  for ($i =  0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    switch ($currentTurnEffects[$i]) {
      case "2995807621"://Trench Run
        if(!$attackerAlly->LostAbilities()) {
          $cardIDs = Mill($defPlayer, 2);
          $cardIDs = explode(",", $cardIDs);
          if (count($cardIDs) > 0) {
            $damage = CardCost($cardIDs[0]);
            if (count($cardIDs) > 1) {
              $damage = abs($damage - CardCost($cardIDs[1]));
            }
          }
          AddDecisionQueue("PASSPARAMETER", $mainPlayer, $attackerAlly->MZIndex());
          AddDecisionQueue("MZOP", $mainPlayer, DamageStringBuilder($damage, $mainPlayer, isUnitEffect:true, isPreventable:false));
        }
        break;
      default: break;
    }
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

function AllyDamageTakenAbilities($player, $index, $damage, $fromCombat=false, $enemyDamage=false,
  $fromUnitEffect=false, $preventable=true)
{
  $damagedAlly = new Ally("MYALLY-" . $index, $player);

  // Friendly unit abilities
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces()) {
    switch($allies[$i]) {
      case "7022736145"://Tarfful
        if ($fromCombat && TraitContains($damagedAlly->CardID(), "Wookiee", $player)) {
          AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY:arena=Ground");
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to deal " . $damage . " damage to");
          AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          AddDecisionQueue("MZOP", $player, "DEALDAMAGE,$damage,$player,1", 1);
        }
        break;
      default: break;
    }
  }

  // Enemy unit abilities
  $otherPlayer = $player == 1 ? 2 : 1;
  $theirAllies = &GetAllies($otherPlayer);
  for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
    switch($theirAllies[$i]) {
      case "cfdcbd005a"://Jango Fett Leader Unit
        if(!LeaderAbilitiesIgnored() && !$damagedAlly->IsExhausted() && ($fromCombat || ($enemyDamage && $fromUnitEffect))) {
          PrependDecisionQueue("MZOP", $player, "REST", 1);
          PrependDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $index, 1);
          PrependDecisionQueue("NOPASS", $otherPlayer, "-");
          PrependDecisionQueue("YESNO", $otherPlayer, "if you want use Jango Fett's ability");
        }
        break;
      //Jump to Lightspeed
      case "9611596703"://Allegiant General Pryde
        if(!$preventable) {
          global $layers;
          $skipLayer = false;
          for($i=0; $i<count($layers); $i+=LayerPieces()) {
            if($layers[$i] == "TRIGGER" && $layers[$i+1] == $otherPlayer && $layers[$i+2] == "9611596703"
                && $layers[$i+3] == $damagedAlly->UniqueID()) $skipLayer = true;
          }
          if(!$skipLayer) AddLayer("TRIGGER", $otherPlayer, "9611596703", $damagedAlly->UniqueID());
        }
        break;
      default: break;
    }
  }

  // Enemy leader abilities
  $theirCharacter = &GetPlayerCharacter($otherPlayer);
  for($i=0; $i<count($theirCharacter); $i+=CharacterPieces()) {
    switch($theirCharacter[$i]) {
      case "9155536481"://Jango Fett Leader
        if(!LeaderAbilitiesIgnored() && !$damagedAlly->IsExhausted() && $theirCharacter[$i+1] == 2 && ($fromCombat || ($enemyDamage && $fromUnitEffect))) {
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

function OpponentUnitDrawEffects($player) {
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces()) {
    switch($allies[$i]) {
      case "8247495024"://Seasoned Fleet Admiral
        AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to add an experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
        AddDecisionQueue("MZOP", $player, "ADDEXPERIENCE", 1);
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
  // $preventable = CanDamageBePrevented($otherPlayer, $damage, $type);//FAB
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

function ResetAllies($player) {
  global $mainPlayer, $defPlayer;

  //Reset allies variables
  $allies = &GetAllies($player);
  for ($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    if ($allies[$i+1] != 0) {
      $allies[$i+3] = 0;
      $allies[$i+8] = 1;
      $allies[$i+10] = 0;//Reset times attacked
      ++$allies[$i+12];//Increase number of turns in play
      $allies[$i+14] = 0;//Reset was healed
      $upgrades = $allies[$i+4];
      if($upgrades != "-") {
        $upgrades = explode(",", $upgrades);
        for($j=0; $j<count($upgrades); $j+=SubcardPieces()) {
          $upgrades[$j+5] += 1;
        }
        $allies[$i+4] = implode(",", $upgrades);
      }

      // Ready ally
      $ally = new Ally("MYALLY-" . $i, $player);
      $ally->Ready();
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

function InvisibleHandJTL($player) {
  AddDecisionQueue("SEARCHDECKTOPX", $player, "8;1;include-trait-Droid&include-definedType-Unit");
  AddDecisionQueue("ADDHAND", $player, "-", 1);
  AddDecisionQueue("REVEALCARDS", $player, "-", 1);
  AddDecisionQueue("SPECIFICCARD", $player, "INVISIBLE_HAND_JTL", 1);
}

function LukePilotPlotArmor($player, $turnsInPlay) {
  AddDecisionQueue("SETDQCONTEXT", $player, "Move Luke to the ground arena?");
  AddDecisionQueue("YESNO", $player, "-");
  AddDecisionQueue("NOPASS", $player, "-");
  AddDecisionQueue("PASSPARAMETER", $player, "5942811090,$turnsInPlay", 1);//Luke Skywalker (You Still With Me?)
  AddDecisionQueue("MZOP", $player, "FALLENPILOTUPGRADE", 1);
}

function TheAnnihilatorJTL($player) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "THEIRALLY");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a unit to destroy");
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "DESTROY", 1);
  AddDecisionQueue("SPECIFICCARD", $player, "THEANNIHILATOR", 1);
}

function RafaMartezJTL($player) {
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a friendly unit to deal 1 damage to");
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "DEALDAMAGE,1,$player,1", 1);
  ReadyResource($player);
}