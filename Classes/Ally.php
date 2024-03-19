<?php
// Ally Class to handle interactions involving allies

class Ally {

  // Properties
  private $allies = [];
  private $playerID;
  private $index;

  // Constructor
  function __construct($MZIndex) {
    global $currentPlayer;
    $mzArr = explode("-", $MZIndex);
    $player = ($mzArr[0] == "MYALLY" ? $currentPlayer : ($currentPlayer == 1 ? 2 : 1));
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

  function SetDistant() {
    $this->allies[$this->index+9] = 1;
  }

  function IsDistant() {
    if(SearchCurrentTurnEffects("7dedg616r0", $this->playerID)) return true;
    return $this->allies[$this->index+9] == 1 ? true : false;
  }

  function IsFostered() {
    return $this->allies[$this->index+10] == 2 ? true : false;
  }

  function CurrentPower() {
    return AttackValue($this->CardID()) + $this->allies[$this->index+7];
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

  function AddBuffCounter() {
    ++$this->allies[$this->index+2];
    ++$this->allies[$this->index+7];
  }

  function ModifyNamedCounters($type, $amount = 1) {
    $this->allies[$this->index+6] += $amount;
    return $this->allies[$this->index+6];//Return the amount of that type of counter
  }

}

function LastAllyIndex($player) {
  $allies = &GetAllies($player);
  return count($allies) - AllyPieces();
}

?>
