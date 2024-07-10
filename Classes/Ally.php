<?php
// Ally Class to handle interactions involving allies

class Ally {

  // Properties
  private $allies = [];
  private $playerID;
  private $index;

  // Constructor
  function __construct($MZIndex, $player="") {
    global $currentPlayer;
    $mzArr = explode("-", $MZIndex);
    if($player == "") $player = ($mzArr[0] == "MYALLY" ? $currentPlayer : ($currentPlayer == 1 ? 2 : 1));
    if($mzArr[1] == "") {
      for($i=0; $i<AllyPieces(); ++$i) $this->allies[] = 9999;
      $this->index = -1;
    } else {
      $this->index = intval($mzArr[1]);
      $this->allies = &GetAllies($player);
    }
    $this->playerID = $player;
  }

  // Methods
  function CardID() {
    return $this->allies[$this->index];
  }

  function UniqueID() {
    return $this->allies[$this->index+5];
  }

  //Controller
  function PlayerID() {
    return $this->playerID;
  }

  function Index() {
    return $this->index;
  }

  function Damage() {
    return $this->allies[$this->index+2];
  }

  function AddDamage($amount) {
    $this->allies[$this->index+2] += $amount;
  }

  function Owner() {
    return $this->allies[$this->index+11];
  }

  function TurnsInPlay() {
    global $currentRound;
    if(IsLeader($this->CardID(), $this->PlayerID())) return $currentRound - 1;
    return $this->allies[$this->index+12];
  }

  function Heal($amount) {
    $healed = $amount;
    if($amount > $this->Damage()) {
      $healed = $this->Damage();
      $this->allies[$this->index+2] = 0;
    } else {
      $this->allies[$this->index+2] -= $amount;
    }
    AddEvent("RESTORE", $this->UniqueID() . "!" . $healed);
    return $healed;
  }

  function MaxHealth() {
    $max = AllyHealth($this->CardID(), $this->PlayerID());
    $upgrades = $this->GetUpgrades();
    for($i=0; $i<count($upgrades); ++$i) if($upgrades[$i] != "-") $max += CardHP($upgrades[$i]);
    $max += $this->allies[$this->index+9];
    for($i=count($this->allies)-AllyPieces(); $i>=0; $i-=AllyPieces()) {
      if(AllyHasStaticHealthModifier($this->allies[$i])) {
        $max += AllyStaticHealthModifier($this->CardID(), $this->Index(), $this->PlayerID(), $this->allies[$i], $i, $this->PlayerID());
      }
    }
    $otherPlayer = $this->PlayerID() == 1 ? 2 : 1;
    $theirAllies = &GetAllies($otherPlayer);
    for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
      if(AllyHasStaticHealthModifier($theirAllies[$i])) {
        $max += AllyStaticHealthModifier($this->CardID(), $this->Index(), $this->PlayerID(), $theirAllies[$i], $i, $otherPlayer);
      }
    }
    $max += CharacterStaticHealthModifiers($this->CardID(), $this->Index(), $this->PlayerID());
    return $max;
  }

  function Health() {
    return $this->MaxHealth() - $this->Damage();
  }

  //Returns true if the ally is destroyed
  function DefeatIfNoRemainingHP() {
    if($this->Health() <= 0 && ($this->CardID() != "d1a7b76ae7" || $this->LostAbilities())) {
      DestroyAlly($this->playerID, $this->index);
      return true;
    }
    return false;
  }

  function IsDamaged() {
    return $this->Damage() > 0;
  }

  function IsExhausted() {
    return $this->allies[$this->index+1] == 1;
  }

  function Destroy() {
    if($this->index == -1) return "";
    if($this->CardID() == "1810342362") return "";//Lurking TIE Phantom
    return DestroyAlly($this->playerID, $this->index);
  }

  //Returns true if the ally is destroyed
  function DealDamage($amount, $bypassShield = false, $fromCombat = false, &$damageDealt = NULL) {
    if($this->index == -1 || $amount <= 0) return false;
    if(!$fromCombat && $this->CardID() == "1810342362") return;//Lurking TIE Phantom
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); ++$i) {
      if($subcards[$i] == "8752877738") {
        //Shield Token
        unset($subcards[$i]);
        $subcards = array_values($subcards);
        $this->allies[$this->index+4] = count($subcards) > 0 ? implode(",", $subcards) : "-";
        AddEvent("SHIELDDESTROYED", $this->UniqueID());
        if(!$bypassShield) return false;//Cancel the damage if shield prevented it
      }
      switch($subcards[$i]) {
        case "5738033724"://Boba Fett's Armor
          if(CardTitle($this->CardID()) == "Boba Fett") $amount -= 2;
          if($amount < 0) $amount = 0;
          break;
        default: break;
      }
    }
    switch($this->CardID()) {
      case "8862896760"://Maul
        $preventUniqueID = SearchLimitedCurrentTurnEffects("8862896760", $this->PlayerID(), remove:true);
        if($preventUniqueID != -1) {
          $preventIndex = SearchAlliesForUniqueID($preventUniqueID, $this->PlayerID());
          if($preventIndex > -1) {
            $preventAlly = new Ally("MYALLY-" . $preventIndex, $this->PlayerID());
            $preventAlly->DealDamage($amount, $bypassShield, $fromCombat, $damageDealt);
            return false;
          }
        }
        break;
      default: break;
    }
    if($damageDealt != NULL) $damageDealt = $amount;
    $this->AddDamage($amount);
    AddEvent("DAMAGE", $this->UniqueID() . "!" . $amount);
    if($this->Health() <= 0 && ($this->CardID() != "d1a7b76ae7" || $this->LostAbilities())) { //Chirrut Imwe
      DestroyAlly($this->playerID, $this->index, fromCombat:$fromCombat);
      return true;
    }
    AllyDamageTakenAbilities($this->playerID, $this->index, survived:true, damage:$amount, fromCombat:$fromCombat);
    switch($this->CardID())
    {
      case "4843225228"://Phase-III Dark Trooper
        if($fromCombat) $this->Attach("2007868442");//Experience token
        break;
      default: break;
    }
    return false;
  }

  function AddRoundHealthModifier($amount) {
    if($this->index == -1) return;
    $this->allies[$this->index+9] += $amount;
    $this->DefeatIfNoRemainingHP();
  }

  function NumAttacks() {
    return $this->allies[$this->index+10];
  }

  function IncrementTimesAttacked() {
    ++$this->allies[$this->index+10];
  }

  function CurrentPower() {
    global $currentTurnEffects;
    $power = AttackValue($this->CardID()) + $this->allies[$this->index+7];
    $power += AttackModifier($this->CardID(), $this->playerID, $this->index);
    $upgrades = $this->GetUpgrades();
    for($i=0; $i<count($upgrades); ++$i) if($upgrades[$i] != "-") $power += AttackValue($upgrades[$i]);
    if(HasGrit($this->CardID(), $this->playerID, $this->index)) {
      $damage = $this->Damage();
      if($damage > 0) $power += $damage;
    }
    //Other ally buffs
    $otherAllies = &GetAllies($this->playerID);
    for($i=0; $i<count($otherAllies); $i+=AllyPieces()) {
      switch($otherAllies[$i]) {
        case "6097248635"://4-LOM
          if(CardTitle($this->CardID()) == "Zuckuss") $power += 1;
          break;
        case "1690726274"://Zuckuss
          if(CardTitle($this->CardID()) == "4-LOM") $power += 1;
          break;
        case "e2c6231b35"://Director Krennic
          if($this->IsDamaged()) $power += 1;
          break;
        case "1557302740"://General Veers
          if($i != $this->index && TraitContains($this->CardID(), "Imperial", $this->PlayerID())) $power += 1;
          break;
        case "9799982630"://General Dodonna
          if($i != $this->index && TraitContains($this->CardID(), "Rebel", $this->PlayerID())) $power += 1;
          break;
        case "4339330745"://Wedge Antilles
          if(TraitContains($this->CardID(), "Vehicle", $this->PlayerID())) $power += 1;
          break;
        case "4484318969"://Moff Gideon Leader
          global $mainPlayer;
          //As defined on NetworkingLibraries.GetTargetOfAttack, $mainPlayer is always the attacker
          if(CardCost($this->CardID()) <= 3 && $mainPlayer == $this->playerID && AttackIndex() == $this->index && IsAllyAttackTarget()) {
            $power += 1;
          }
          break;
        case "3feee05e13"://Gar Saxon
          if($this->IsUpgraded()) $power += 1;
          break;
        case "919facb76d"://Boba Fett Green Leader
          if($i != $this->index) $power += 1;
          break;
        default: break;
      }
    }
    //Their ally modifiers
    $theirAllies = &GetAllies($this->playerID == 1 ? 2 : 1);
    for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
      switch($theirAllies[$i]) {
        case "3731235174"://Supreme Leader Snoke
          if(!IsLeader($this->CardID(), $this->playerID)) {
            $power -= 2;
          }
          break;
        default: break;
      }
    }
    //Character buffs
    $myChar = &GetPlayerCharacter($this->playerID);
    for($i=0; $i<count($myChar); $i+=CharacterPieces()) {
      switch($myChar[$i]) {
        case "8560666697"://Director Krennic
          if($this->IsDamaged()) $power += 1;
          break;
        case "9794215464"://Gar Saxon
          if($this->IsUpgraded()) $power += 1;
          break;
        default: break;
      }
    }
    //Current effect buffs
    for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
      if($currentTurnEffects[$i+1] != $this->playerID) continue;
      if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $this->UniqueID()) continue;
      $power += EffectAttackModifier($currentTurnEffects[$i], $this->PlayerID());
    }
    if($power < 0) $power = 0;
    return $power;
  }

  //All the things that should happen at the end of a round
  function EndRound() {
    if($this->index == -1) return;
    $roundHealthModifier = $this->allies[$this->index+9];
    if(is_int($roundHealthModifier)) $this->allies[$this->index+2] -= $roundHealthModifier;
    $this->allies[$this->index+9] = 0;
    $this->DefeatIfNoRemainingHP();
  }

  function Ready() {
    $upgrades = $this->GetUpgrades();
    for($i=0; $i<count($upgrades); ++$i) {
      switch($upgrades[$i]) {
        case "7718080954"://Frozen in Carbonite
          return false;
        default: break;
      }
    }
    if($this->allies[$this->index+3] == 1) return false;
    $this->allies[$this->index+1] = 2;
    return true;
  }

  function Exhaust() {
    if($this->index == -1) return;
    AddEvent("EXHAUST", $this->UniqueID());
    $this->allies[$this->index+1] = 1;
  }

  function AddSubcard($cardID) {
    if($this->allies[$this->index + 4] == "-") $this->allies[$this->index + 4] = $cardID;
    else $this->allies[$this->index + 4] = $this->allies[$this->index + 4] . "," . $cardID;
  }
  
  function RemoveSubcard($subcardID) {
    if($this->index == -1) return false;
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); ++$i) {
      if($subcards[$i] == $subcardID) {
        unset($subcards[$i]);
        $subcards = array_values($subcards);
        $this->allies[$this->index + 4] = count($subcards) > 0 ? implode(",", $subcards) : "-";
        return true;
      }
    }
    return false;
  }

  function AddEffect($effectID) {
    AddCurrentTurnEffect($effectID, $this->PlayerID(), uniqueID:$this->UniqueID());
  }

  function Attach($cardID) {
    if($this->allies[$this->index + 4] == "-") $this->allies[$this->index + 4] = $cardID;
    else $this->allies[$this->index + 4] = $this->allies[$this->index + 4] . "," . $cardID;
    if (CardIsUnique($cardID)) {
      $this->CheckUniqueUpgrade($cardID);
    }
  }

  function GetSubcards() {
    $subcards = $this->allies[$this->index + 4];
    if($subcards == null || $subcards == "" || $subcards == "-") return [];
    return explode(",", $this->allies[$this->index + 4]);
  }

  function GetUpgrades() {
    if($this->allies[$this->index + 4] == "-") return [];
    $subcards = $this->GetSubcards();
    $upgrades = [];
    for($i=0; $i<count($subcards); ++$i) {
      if(DefinedTypesContains($subcards[$i], "Upgrade", $this->PlayerID()) || DefinedTypesContains($subcards[$i], "Token Upgrade", $this->PlayerID())) $upgrades[] = $subcards[$i];
    }
    return $upgrades;
  }

  function GetCaptives() {
    if($this->allies[$this->index + 4] == "-") return [];
    $subcards = $this->GetSubcards();
    $capturedUnits = [];
    for($i=0; $i<count($subcards); ++$i) {
      if(DefinedTypesContains($subcards[$i], "Unit", $this->PlayerID())) $capturedUnits[] = $subcards[$i];
    }
    return $capturedUnits;
  }

  function ClearSubcards() {
    $this->allies[$this->index + 4] = "-";
  }

  function CheckUniqueUpgrade($cardID) {
    $firstCopy = "";
    $secondCopy = "";
    for($i=0; $i<count($this->allies); $i+=AllyPieces()) {
      $subcards = explode(",", $this->allies[$i + 4]);
      for($j=0; $j<count($subcards); ++$j) {
        if($subcards[$j] == $cardID) {
          if($firstCopy == "") $firstCopy = $i;
          else $secondCopy = $i;
        }
      }
    }

    if($firstCopy != "" && $firstCopy == $secondCopy && $this->index == $firstCopy) {
      $this->DefeatUpgrade($cardID);
      WriteLog("Existing copy of upgrade defeated due to unique rule.");
    } elseif ($firstCopy != "" && $secondCopy != "" && $firstCopy != $secondCopy) {
      $otherindex = $this->index == $firstCopy ? $secondCopy : $firstCopy;
      $otherAlly = new Ally("MYALLY-" . $otherindex);
      $otherAlly->DefeatUpgrade($cardID);
      WriteLog("Existing copy of upgrade defeated due to unique rule.");
    }
  }

  function HasUpgrade($upgradeID) {
    if($this->index == -1) return false;
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); ++$i) {
      if($subcards[$i] == $upgradeID) {
        return true;
      }
    }
    return false;
  }

  function DefeatUpgrade($upgradeID) {
    if($this->RemoveSubcard($upgradeID)) {
      $this->DefeatIfNoRemainingHP();
      return true;
    }
    else return false;
  }

  function RescueCaptive($captiveID, $newController=-1) {
    if($this->RemoveSubcard($captiveID)) {
      $otherPlayer = $this->PlayerID() == 1 ? 2 : 1;
      if($newController == -1) $newController = $otherPlayer;
      PlayAlly($captiveID, $newController, from:"CAPTIVE");
    }
    else return false;
  }

  function DiscardCaptive($captiveID) {
    if($this->RemoveSubcard($captiveID)) {
      $otherPlayer = $this->PlayerID() == 1 ? 2 : 1;
      AddGraveyard($captiveID, $otherPlayer, "CAPTIVE");
      return true;
    }
    else return false;
  }

  function NumUses() {
    return $this->allies[$this->index + 8];
  }

  function ModifyUses($amount) {
    $this->allies[$this->index + 8] += $amount;
  }

  function LostAbilities() {
    global $currentTurnEffects;
    for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
      if($currentTurnEffects[$i+1] != $this->PlayerID()) continue;
      if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $this->UniqueID()) continue;
      if($currentTurnEffects[$i] == "2639435822") return true;
    }
    $upgrades = $this->GetUpgrades();
    for($i=0; $i<count($upgrades); ++$i) {
      switch($upgrades[$i]) {
        case "1368144544"://Imprisoned
          return true;
        default: break;
      }
    }
    return false;
  }

  function IsUpgraded() {
    return $this->allies[$this->index + 4] != "-";
  }

  function NumUpgrades() {
    $upgrades = $this->GetUpgrades();
    return count($upgrades);
  }

  function HasBounty() {
    return CollectBounties($this->PlayerID(), $this->Index(), reportMode:true) > 0;
  }

}

function LastAllyIndex($player) {
  $allies = &GetAllies($player);
  return count($allies) - AllyPieces();
}

?>
