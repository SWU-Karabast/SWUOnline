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
    $this->index = $mzArr[1];
    $this->allies = &GetAllies($player);
    $this->playerID = $player;
  }

  // Methods
  function CardID() {
    return $this->allies[$this->index];
  }

  function UniqueID() {
    return $this->allies[$this->index+5];
  }

  function PlayerID() {
    return $this->playerID;
  }

  function Index() {
    return $this->index;
  }

  function Health() {
    return $this->allies[$this->index+2];
  }

  function SetDistant() {
    $this->allies[$this->index+9] = 1;
  }

  function AddHealth($amount) {
    $this->allies[$this->index+2] += $amount;
  }

  function Heal($amount) {
    $this->AddHealth($amount);
    if($this->Health() > $this->MaxHealth()) $this->allies[$this->index+2] = $this->MaxHealth();
  }

  function MaxHealth() {
    $max = CardHP($this->CardID());
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); ++$i) if($subcards[$i] != "-") $max += CardHP($subcards[$i]);
    return $max;
  }

  function IsDamaged() {
    return $this->Health() < $this->MaxHealth();
  }

  function IsExhausted() {
    return $this->allies[$this->index+1] == 1;
  }

  function Destroy() {
    DestroyAlly($this->playerID, $this->index);
  }

  //Returns true if the ally is destroyed
  function DealDamage($amount) {
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); ++$i) {
      if($subcards[$i] == "8752877738") {
        unset($subcards[$i]);
        $subcards = array_values($subcards);
        $this->allies[$this->index+4] = count($subcards) > 0 ? implode(",", $subcards) : "-";
        return false;//Cancel the damage if shield prevented it
      }
    }
    $this->allies[$this->index+2] -= $amount;
    if($this->Health() <= 0 && $this->CardID() != "d1a7b76ae7") {
      DestroyAlly($this->playerID, $this->index);
      return true;
    }
    return false;
  }

  function CurrentPower() {
    global $currentTurnEffects;
    $power = AttackValue($this->CardID()) + $this->allies[$this->index+7];
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); ++$i) if($subcards[$i] != "-") $power += AttackValue($subcards[$i]);
    if(HasGrit($this->CardID(), $this->playerID, $this->index)) $power += $this->MaxHealth() - $this->Health();
    //Other ally buffs
    $otherAllies = &GetAllies($this->playerID);
    for($i=0; $i<count($otherAllies); $i+=AllyPieces()) {
      switch($otherAllies[$i]) {
        case "e2c6231b35"://Director Krennig
          if($this->Health() > $this->MaxHealth()) $power += 1;
          break;
        default: break;
      }
    }
    //Character buffs
    $myChar = &GetPlayerCharacter($this->playerID);
    for($i=0; $i<count($myChar); $i+=CharacterPieces()) {
      switch($myChar[$i]) {
        case "8560666697"://Director Krennig
          if($this->Health() > $this->MaxHealth()) $power += 1;
          break;
        default: break;
      }
    }
    //Current effect buffs
    for($i=0; $i<count($currentTurnEffects); ++$i) {
      if($currentTurnEffects[$i+1] != $this->playerID) continue;
      if($currentTurnEffects[$i+2] != -1 && $currentTurnEffects[$i+2] != $this->UniqueID()) continue;
      $power += EffectAttackModifier($currentTurnEffects[$i]);
    }
    return $power;
  }

  function Ready() {
    $this->allies[$this->index+1] = 2;
  }
  
  function Exhaust() {
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
    return explode(",", $this->allies[$this->index + 4]);
  }

  function NumUses() {
    return $this->allies[$this->index + 8];
  }

  function ModifyUses($amount) {
    $this->allies[$this->index + 8] += $amount;
  }

}

function LastAllyIndex($player) {
  $allies = &GetAllies($player);
  return count($allies) - AllyPieces();
}

?>
