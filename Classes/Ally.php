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

  //Returns true if the ally is destroyed
  function DealDamage($amount) {
    $this->allies[$this->index+2] -= $amount;
    if($this->Health() <= 0 && $this->CardID() != "d1a7b76ae7") {
      DestroyAlly($this->playerID, $this->index);
      return true;
    }
    return false;
  }

  function CurrentPower() {
    $power = AttackValue($this->CardID()) + $this->allies[$this->index+7];
    $subcards = $this->GetSubcards();
    for($i=0; $i<count($subcards); ++$i) if($subcards[$i] != "-") $power += AttackValue($subcards[$i]);
    if(HasGrit($this->CardID(), $this->playerID, $this->index)) $power += $this->MaxHealth() - $this->Health();
    return $power;
  }

  function OnFoster() {
    $fosterActive = $this->allies[$this->index+10] == 0;
    $this->allies[$this->index+10] = 2;
    if($fosterActive) {
      switch($this->allies[$this->index]) {
        case "22tk3ir1o0"://Novice Mechanist
          PlayAlly("mu6gvnta6q", $this->playerID);
          break;
        case "kuz07nk45s"://Forgelight Shieldmaiden
          Draw($this->playerID);
          Draw($this->playerID);
          PummelHit($this->playerID);
          AddDecisionQueue("ALLCARDELEMENTORPASS", $this->playerID, "FIRE", 1);
          AddDecisionQueue("PASSPARAMETER", $this->playerID, "MYALLY-" . $this->index, 1);
          AddDecisionQueue("MZOP", $this->playerID, "BUFFALLY", 1);
          break;
        case "mnu1xhs5jw"://Awakened Frostguard
          if(!IsClassBonusActive($this->playerID, "GUARDIAN")) break;
          for($i=0; $i<2; ++$i) {
            AddFloatingMemoryChoice();
            AddDecisionQueue("DRAW", $this->playerID, "-", 1);
            AddDecisionQueue("PASSPARAMETER", $this->playerID, "MYALLY-" . $this->index, 1);
            AddDecisionQueue("MZOP", $this->playerID, "BUFFALLY", 1);
          }
          break;
        default: break;
      }
    }
    return $fosterActive;
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

}

function LastAllyIndex($player) {
  $allies = &GetAllies($player);
  return count($allies) - AllyPieces();
}

?>
