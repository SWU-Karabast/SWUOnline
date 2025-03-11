<?php

//0 - Card ID
//1 - Status (2=ready, 1=unavailable, 0=destroyed)
//2 - Num counters (used for epic action on leaders)
//3 - Num attack counters
//4 - Num defense counters
//5 - Num uses
//6 - On chain (1 = yes, 0 = no)
//7 - Flagged for destruction (1 = yes, 0 = no)
//8 - Frozen (1 = yes, 0 = no)
//9 - Is Active (2 = always active, 1 = yes, 0 = no)
//10 - Counters (damage/healing counters)
class Character {
  // property declaration
  private $characters = [];
  private $playerID;
  private $index;

  public function __construct($mzIndexOrUniqueID, $player = "") {
    global $currentPlayer;

    $c = $mzIndexOrUniqueID[0];
    if ($c == "B") {
      $this->index = 0;
      $player = $mzIndexOrUniqueID[1];
    } else if ($c == "L") {
      $this->index = CharacterPieces();
      $player = $mzIndexOrUniqueID[1];
    } else {
      $mzArr = explode("-", $mzIndexOrUniqueID);
      $player = $player == "" ? $currentPlayer : $player;
      $player = $mzArr[0] == "MYCHAR" ? $player : ($player == 1 ? 2 : 1); // Unlike the Ally class, Character doesn't ignore the mzIndex's prefix

      if ($mzArr[1] == "") {
        for($i=0; $i<CharacterPieces(); ++$i) $this->characters[] = 9999;
        $this->index = -1;
      } else {
        $this->index = intval($mzArr[1]);
      }
    }

    $this->playerID = $player;
    $this->characters = &GetPlayerCharacter($player);
  }

  // Returns the unique ID of the character
  // B<playerID> for base character
  // L<playerID> for leader character
  public function UniqueId() {
    $c = $this->index === 0 ? "B" : "L";
    return $c . $this->playerID;
  }

  public function CardId() {
    return $this->characters[$this->index];
  }

  public function Status() {
    return $this->characters[$this->index + 1];
  }

  public function Counters() {
    return $this->characters[$this->index + 10];
  }

  public function SetCounters($amount) {
    $this->characters[$this->index + 10] = $amount;
  }

  public function IncreaseCounters() {
    $this->characters[$this->index + 10]++;
  }

  public function DecreaseCounters() {
    $this->characters[$this->index + 10]--;
  }

  public function PlayerID() {
    return $this->playerID;
  }

  public function Index() {
    return $this->index;
  }

  public function MZIndex() {
    global $currentPlayer;
    return ($currentPlayer == $this->playerID ? "MYCHAR-" : "THEIRCHAR-") . $this->index;
  }  
}

function PutCharacterIntoPlayForPlayer($cardID, $player)
{
  $char = &GetPlayerCharacter($player);
  $index = count($char);
  $char[] = $cardID;
  $char[] = 2;
  $char[] = CharacterCounters($cardID);
  $char[] = 0;
  $char[] = 0;
  $char[] = 1;
  $char[] = 0;
  $char[] = 0;
  $char[] = 0;
  $char[] = 2;
  $char[] = 0;
  return $index;
}

function CharacterCounters ($cardID)
{
  switch($cardID) {
    case "DYN492a": return 8;
    default: return 0;
  }
}

function CharacterTakeDamageAbility($player, $index, $damage, $preventable)
{
  // This code is commented out because it is not currently used
  // $char = &GetPlayerCharacter($player);
  // $otherPlayer = $player == 1 ? 1 : 2;
  // $type = "-";//Add this if it ever matters
  // switch ($char[$index]) {

  //   default:
  //     break;
  // }
  // if ($remove == 1) {
  //   DestroyCharacter($player, $index);
  // }
  if ($damage <= 0) $damage = 0;
  return $damage;
}

function CharacterStartRegroupPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex
  $character = &GetPlayerCharacter($player);

  for ($i = 0; $i < count($character); $i += CharacterPieces()) {
    if ($character[$i + 1] == 0 || $character[$i + 1] == 1) continue; //Do not process ability if it is destroyed
    switch($character[$i]) {
      case "0254929700"://Doctor Aphra
        Mill($player, 1);
        break;
      default:
        break;
    }
  }
}

function CharacterEndRegroupPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex
}

function CharacterStartActionPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex
  $character = &GetPlayerCharacter($player);

  for($i = 0; $i < count($character); $i += CharacterPieces()) {
    if($character[$i + 1] == 0 || $character[$i + 1] == 1) continue; //Do not process ability if it is destroyed/exhausted
    switch($character[$i]) {
      case "1951911851"://Grand Admiral Thrawn
        AddDecisionQueue("PASSPARAMETER", $player, "MYDECK-0");
        AddDecisionQueue("MZOP", $player, "GETCARDID");
        AddDecisionQueue("SETDQVAR", $player, "0");
        AddDecisionQueue("PASSPARAMETER", $player, "THEIRDECK-0");
        AddDecisionQueue("MZOP", $player, "GETCARDID");
        AddDecisionQueue("SETDQVAR", $player, "1");
        AddDecisionQueue("SETDQCONTEXT", $player, "The top of your deck is <0> and the top of their deck is <1>.");
        AddDecisionQueue("OK", $player, "-");
        break;
      default:
        break;
    }
  }
}

function CharacterEndActionPhaseAbilities($player) {
  // To function correctly, use uniqueID instead of MZIndex
}

function DefCharacterStartTurnAbilities()
{
  global $defPlayer, $mainPlayer;
  $character = &GetPlayerCharacter($defPlayer);
  for($i = 0; $i < count($character); $i += CharacterPieces()) {
    if($character[$i + 1] == 0 || $character[$i + 1] == 1) continue; //Do not process ability if it is destroyed
    switch($character[$i]) {

      default:
        break;
    }
  }
}

function CharacterStaticHealthModifiers($cardID, $index, $player)
{
  $modifier = 0;
  $char = &GetPlayerCharacter($player);
  for($i=0; $i<count($char); $i+=CharacterPieces()) {
    switch($char[$i])
    {
      default: break;
    }
  }
  return $modifier;
}

function CharacterDestroyEffect($cardID, $player)
{
  switch($cardID) {

    default:
      break;
  }
}

function ResetCharacter($player) {
  $char = &GetPlayerCharacter($player);
  for ($i = 0; $i < count($char); $i += CharacterPieces()) {
    if ($char[$i+7] == 1) $char[$i+1] = 0; //Destroy if it was flagged for destruction
    if ($char[$i+1] != 0) {
      $char[$i+1] = 2;
    }
    $char[$i+5] = CharacterNumUsesPerTurn($char[$i]);
    $char[$i+10] = 0;
  }
}

// function MainCharacterHitAbilities()//FAB
// {
//   global $combatChain, $combatChainState, $CCS_WeaponIndex, $mainPlayer;
//   $attackID = $combatChain[0];
//   $mainCharacter = &GetPlayerCharacter($mainPlayer);

//   // This code is commented out because it is not currently used
//   // for($i = 0; $i < count($mainCharacter); $i += CharacterPieces()) {
//   //   switch($characterID) {

//   //     default:
//   //       break;
//   //   }
//   // }
// }

function MainCharacterAttackModifiers($index = -1, $onlyBuffs = false)
{
  global $combatChainState, $CCS_WeaponIndex, $mainPlayer, $CS_NumAttacks, $combatChain;
  $modifier = 0;
  $mainCharacterEffects = &GetMainCharacterEffects($mainPlayer);
  $mainCharacter = &GetPlayerCharacter($mainPlayer);
  if($index == -1) $index = $combatChainState[$CCS_WeaponIndex];
  for($i = 0; $i < count($mainCharacterEffects); $i += CharacterEffectPieces()) {
    if($mainCharacterEffects[$i] == $index) {
      switch($mainCharacterEffects[$i + 1]) {
        case "QQaOgurnjX": $modifier += 2; break;//Imbue in Frost
        case "usb5FgKvZX": $modifier += 1; break;//Sharpening Stone
        case "CgyJxpEgzk": $modifier += 3; break;//Spirit Blade: Infusion
        default:
          break;
      }
    }
  }
  if($onlyBuffs) return $modifier;

  $mainCharacter = &GetPlayerCharacter($mainPlayer);
  for($i = 0; $i < count($mainCharacter); $i += CharacterPieces()) {
    switch($mainCharacter[$i]) {
      //case "NfbZ0nouSQ": if(!IsAlly($combatChain[0])) $modifier += SearchCount(SearchBanish($mainPlayer,type:"WEAPON")); break;
      default: break;
    }
  }
  return $modifier;
}

// function MainCharacterHitEffects()//FAB
// {
//   global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
//   $modifier = 0;
//   $mainCharacterEffects = &GetMainCharacterEffects($mainPlayer);
//   for($i = 0; $i < count($mainCharacterEffects); $i += 2) {
//     if($mainCharacterEffects[$i] == $combatChainState[$CCS_WeaponIndex]) {
//       switch($mainCharacterEffects[$i + 1]) {
//         case "CgyJxpEgzk"://Spirit Blade: Infusion
//           Draw($mainPlayer);
//           break;
//         default: break;
//       }
//     }
//   }
//   return $modifier;
// }

function MainCharacterGrantsGoAgain()
{
  global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  if($combatChainState[$CCS_WeaponIndex] == -1) return false;
  $mainCharacterEffects = &GetMainCharacterEffects($mainPlayer);
  for($i = 0; $i < count($mainCharacterEffects); $i += 2) {
    if($mainCharacterEffects[$i] == $combatChainState[$CCS_WeaponIndex]) {
      switch($mainCharacterEffects[$i + 1]) {

        default: break;
      }
    }
  }
  return false;
}

// function CharacterCostModifier($cardID, $from)
// {
//   global $currentPlayer, $CS_NumSwordAttacks;
//   $modifier = 0;
//   if(CardSubtype($cardID) == "Sword" && GetClassState($currentPlayer, $CS_NumSwordAttacks) == 1 && SearchCharacterActive($currentPlayer, "CRU077")) {
//     --$modifier;
//   }
//   return $modifier;
// }

function EquipCard($player, $card)
{
  $char = &GetPlayerCharacter($player);
  $lastWeapon = 0;
  $replaced = 0;
  $numHands = 0;
  //Replace the first destroyed weapon; if none you can't re-equip
  for($i=CharacterPieces(); $i<count($char) && !$replaced; $i+=CharacterPieces())
  {
    if(CardType($char[$i]) == "W")
    {
      $lastWeapon = $i;
      if($char[$i+1] == 0)
      {
        $char[$i] = $card;
        $char[$i+1] = 2;
        $char[$i+2] = 0;
        $char[$i+3] = 0;
        $char[$i+4] = 0;
        $char[$i+5] = 1;
        $char[$i+6] = 0;
        $char[$i+7] = 0;
        $char[$i+8] = 0;
        $char[$i+9] = 2;
        $char[$i+10] = 0;
        $replaced = 1;
      }
      else if(Is1H($char[$i])) ++$numHands;
      else $numHands += 2;
    }
  }
  if($numHands < 2 && !$replaced)
  {
    $insertIndex = $lastWeapon + CharacterPieces();
    array_splice($char, $insertIndex, 0, $card);
    array_splice($char, $insertIndex+1, 0, 2);
    array_splice($char, $insertIndex+2, 0, 0);
    array_splice($char, $insertIndex+3, 0, 0);
    array_splice($char, $insertIndex+4, 0, 0);
    array_splice($char, $insertIndex+5, 0, 1);
    array_splice($char, $insertIndex+6, 0, 0);
    array_splice($char, $insertIndex+7, 0, 0);
    array_splice($char, $insertIndex+8, 0, 0);
    array_splice($char, $insertIndex+9, 0, 2);
    array_splice($char, $insertIndex+10, 0, 0);
  }
}

function ShiyanaCharacter($cardID, $player="")
{
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  if($cardID == "CRU097") {
    $otherPlayer = ($player == 1 ? 2 : 1);
    $otherCharacter = &GetPlayerCharacter($otherPlayer);
    if(SearchCurrentTurnEffects($otherCharacter[0] . "-SHIYANA", $player)) $cardID = $otherCharacter[0];
  }
  return $cardID;
}

function EquipPayAdditionalCosts($cardIndex, $from)
{
  global $currentPlayer;
  if($cardIndex == -1) return;//TODO: Add error handling
  $character = &GetPlayerCharacter($currentPlayer);
  $cardID = $character[$cardIndex];
  switch($cardID) {
    case "1393827469"://Tarkintown
    case "2569134232"://Jedha City
    case "2429341052"://Security Complex
    case "8327910265"://Energy Conversion Lab (ECL)
      $character[$cardIndex+1] = 0;
      break;
    default:
      --$character[$cardIndex+5];
      if($character[$cardIndex+5] == 0) $character[$cardIndex+1] = 1; //By default, if it's used, set it to used
      break;
  }
}

function CharacterTriggerInGraveyard($cardID)
{
  switch($cardID) {
    default: return false;
  }
}

function AllyDealDamageAbilities($player, $damage, $type) {
  global $currentTurnEffects;
  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    switch($allies[$i]) {
      case "3c60596a7a"://Cassian Andor Leader Unit
        $ally = new Ally("MYALLY-" . $i, $player);
        if ($ally->NumUses() > 0) {
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose if you want to draw a card (Cassian's ability)");
          AddDecisionQueue("YESNO", $player, "-");
          AddDecisionQueue("NOPASS", $player, "-");
          AddDecisionQueue("PASSPARAMETER", $player, "MYALLY-" . $i, 1);
          AddDecisionQueue("ADDMZUSES", $player, "-1", 1);
          AddDecisionQueue("DRAW", $player, "-", 1);
        }
        break;
    }
  }

  //currentt turn effects from allies
  for($i=0;$i<count($currentTurnEffects);$i+=CurrentTurnPieces()) {
    switch($currentTurnEffects[$i]) {
      case "6228218834"://Tactical Heavy Bomber
        if($type != "COMBAT") Draw($currentTurnEffects[$i+1]);
        break;
      case "2711104544"://Guerilla Soldier
        if($type != "COMBAT") {
          $ally = new Ally($currentTurnEffects[$i+2]);
          $ally->Ready();
        }
        break;
      default: break;
      }
    }
}
?>
