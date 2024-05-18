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
      for($i=0; $i<AllyPieces(); ++$i) array_push($this->allies, 9999);
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

  function Health() {
    return $this->allies[$this->index+2];
  }

  function Owner() {
    return $this->allies[$this->index+11];
  }

  function TurnsInPlay() {
    global $currentRound;
    if(IsLeader($this->CardID(), $this->PlayerID())) return $currentRound - 1;
    return $this->allies[$this->index+12];
  }

  function AddHealth($amount) {
    $this->allies[$this->index+2] += $amount;
  }

  function Heal($amount) {
    $healed = $amount;
    $this->AddHealth($amount);
    if($this->Health() > $this->MaxHealth()) {
      $healed = $amount - ($this->Health() - $this->MaxHealth());
      $this->allies[$this->index+2] = $this->MaxHealth();
    }
    return $healed;
  }

  function MaxHealth() {
    $max = CardHP($this->CardID());
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); ++$i) if($subcards[$i] != "-") $max += CardHP($subcards[$i]);
    $max += $this->allies[$this->index+9];
    return $max;
  }

  function IsDamaged() {
    return $this->Health() < $this->MaxHealth();
  }

  function IsExhausted() {
    return $this->allies[$this->index+1] == 1;
  }

  function Destroy() {
    if($this->index == -1) return;
    DestroyAlly($this->playerID, $this->index);
  }

  //Returns true if the ally is destroyed
  function DealDamage($amount, $bypassShield = false, $fromCombat = false, &$damageDealt = NULL) {
    if($this->index == -1 || $amount <= 0) return false;
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); ++$i) {
      if($subcards[$i] == "8752877738") {
        unset($subcards[$i]);
        $subcards = array_values($subcards);
        $this->allies[$this->index+4] = count($subcards) > 0 ? implode(",", $subcards) : "-";
        if(!$bypassShield) return false;//Cancel the damage if shield prevented it
      }
    }
    if($damageDealt != NULL) $damageDealt = $amount;
    $this->allies[$this->index+2] -= $amount;
    if($this->Health() <= 0 && $this->CardID() != "d1a7b76ae7") {
      DestroyAlly($this->playerID, $this->index, fromCombat:$fromCombat);
      return true;
    }
    return false;
  }

  function AddRoundHealthModifier($amount) {
    if($this->index == -1) return;
    $this->allies[$this->index+2] += $amount;
    $this->allies[$this->index+9] += $amount;
    if($this->Health() <= 0) {
      DestroyAlly($this->playerID, $this->index);
      return true;
    }
    return false;
  }

  function TempReduceHealth($amount) {
    if($this->index == -1) return;
    $this->allies[$this->index+2] -= $amount;
    if($this->Health() <= 0) {
      DestroyAlly($this->playerID, $this->index);
      return true;
    }
    return false;
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
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); ++$i) if($subcards[$i] != "-") $power += AttackValue($subcards[$i]);
    if(HasGrit($this->CardID(), $this->playerID, $this->index)) {
      $damage = $this->MaxHealth() - $this->Health();
      if($damage > 0) $power += $damage;
    }
    //Other ally buffs
    $otherAllies = &GetAllies($this->playerID);
    for($i=0; $i<count($otherAllies); $i+=AllyPieces()) {
      switch($otherAllies[$i]) {
        case "e2c6231b35"://Director Krennic
          if($this->Health() < $this->MaxHealth()) $power += 1;
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
        default: break;
      }
    }
    //Character buffs
    $myChar = &GetPlayerCharacter($this->playerID);
    for($i=0; $i<count($myChar); $i+=CharacterPieces()) {
      switch($myChar[$i]) {
        case "8560666697"://Director Krennic
          if($this->Health() < $this->MaxHealth()) $power += 1;
          break;
        default: break;
      }
    }
    //Current effect buffs
    for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces()) {
      if($currentTurnEffects[$i+1] != $this->playerID) continue;
      if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $this->UniqueID()) continue;
      $power += EffectAttackModifier($currentTurnEffects[$i]);
    }
    return $power;
  }

  //All the things that should happen at the end of a round
  function EndRound() {
    if($this->index == -1) return;
    $this->allies[$this->index+2] -= $this->allies[$this->index+9];
    $this->allies[$this->index+9] = 0;
    if($this->Health() <= 0) {
      DestroyAlly($this->playerID, $this->index);
      return true;
    }
    return false;
  }

  function Ready() {
    $this->allies[$this->index+1] = 2;
  }
  
  function Exhaust() {
    if($this->index == -1) return;
    $this->allies[$this->index+1] = 1;
  }

  function AddBuffCounter() {
    ++$this->allies[$this->index+2];
    ++$this->allies[$this->index+7];
  }

  function ModifyNamedCounters($type, $amount = 1) {
    $this->allies[$this->index+6] += $amount;
    return $this->allies[$this->index+6];//Return the amount of that type of counter
  }

  function Attach($cardID) {
    if($this->allies[$this->index + 4] == "-") $this->allies[$this->index + 4] = $cardID;
    else $this->allies[$this->index + 4] = $this->allies[$this->index + 4] . "," . $cardID;
    $this->AddHealth(CardHP($cardID));
  }

  function GetSubcards() {
    if($this->allies[$this->index + 4] == "-") return [];
    return explode(",", $this->allies[$this->index + 4]);
  }

  function ClearSubcards() {
    $this->allies[$this->index + 4] = "-";
  }

  function DefeatUpgrade($upgradeID) {
    if($this->index == -1) return;
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); ++$i) {
      if($subcards[$i] == $upgradeID) {
        unset($subcards[$i]);
        $subcards = array_values($subcards);
        $this->allies[$this->index + 4] = count($subcards) > 0 ? implode(",", $subcards) : "-";
        $this->allies[$this->index+2] -= CardHP($upgradeID);
        if($this->Health() <= 0) {
          DestroyAlly($this->playerID, $this->index);
          return true;
        }
        return;
      } 
    }
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
    return false;
  }

  function IsUpgraded() {
    return $this->allies[$this->index + 4] != "-";
  }

}

function LastAllyIndex($player) {
  $allies = &GetAllies($player);
  return count($allies) - AllyPieces();
}

?>
