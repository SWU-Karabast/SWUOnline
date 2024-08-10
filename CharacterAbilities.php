<?php

//0 - Card ID
//1 - Status (2=ready, 1=unavailable, 0=destroyed)
//2 - Num counters
//3 - Num attack counters
//4 - Num defense counters
//5 - Num uses
//6 - On chain (1 = yes, 0 = no)
//7 - Flagged for destruction (1 = yes, 0 = no)
//8 - Frozen (1 = yes, 0 = no)
//9 - Is Active (2 = always active, 1 = yes, 0 = no)
//10 - Position (0 = normal, 1 = distant)
class Character
{
    // property declaration
    public $cardID = "";
    public $status = 2;
    public $numCounters = 0;
    public $numAttackCounters = 0;
    public $numDefenseCounters = 0;
    public $numUses = 0;
    public $onChain = 0;
    public $flaggedForDestruction = 0;
    public $frozen = 0;
    public $isActive = 2;
    public $position = 0;

    private $player = null;
    private $arrIndex = -1;

    public function __construct($player, $index)
    {
      $this->player = $player;
      $this->arrIndex = $index;
      $array = &GetPlayerCharacter($player);

      $this->cardID = $array[$index];
      $this->status = $array[$index+1];
      $this->numCounters = $array[$index+2];
      $this->numAttackCounters = $array[$index+3];
      $this->numDefenseCounters = $array[$index+4];
      $this->numUses = $array[$index+5];
      $this->onChain = $array[$index+6];
      $this->flaggedForDestruction = $array[$index+7];
      $this->frozen = $array[$index+8];
      $this->isActive = $array[$index+9];
      $this->position = $array[$index+10];
    }

    public function Finished()
    {
      $array = &GetPlayerCharacter($this->player);
      $array[$this->arrIndex] = $this->cardID;
      $array[$this->arrIndex+1] = $this->status;
      $array[$this->arrIndex+2] = $this->numCounters;
      $array[$this->arrIndex+3] = $this->numAttackCounters;
      $array[$this->arrIndex+4] = $this->numDefenseCounters;
      $array[$this->arrIndex+5] = $this->numUses;
      $array[$this->arrIndex+6] = $this->onChain;
      $array[$this->arrIndex+7] = $this->flaggedForDestruction;
      $array[$this->arrIndex+8] = $this->frozen;
      $array[$this->arrIndex+9] = $this->isActive;
      $array[$this->arrIndex+10] = $this->position;
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
  $char = &GetPlayerCharacter($player);
  $otherPlayer = $player == 1 ? 1 : 2;
  $type = "-";//Add this if it ever matters
  switch ($char[$index]) {

    default:
      break;
  }
  if ($remove == 1) {
    DestroyCharacter($player, $index);
  }
  if ($damage <= 0) $damage = 0;
  return $damage;
}

function CharacterStartTurnAbility($player)
{
  $character = &GetPlayerCharacter($player);
  for($i = 0; $i < count($character); $i += CharacterPieces()) {
    if($character[$i + 1] == 0 || $character[$i + 1] == 1) continue; //Do not process ability if it is destroyed
    switch($character[$i]) {
      case "1951911851"://Grand Admiral Thrawn
        $myDeck = &GetDeck($player);
        $theirDeck = &GetDeck($player == 1 ? 2 : 1);
        AddDecisionQueue("SETDQCONTEXT", $player, "The top of your deck is " . CardLink($myDeck[0], $myDeck[0]) . " and the top of their deck is " . CardLink($theirDeck[0], $theirDeck[0]));
        AddDecisionQueue("OK", $player, "-");
        break;
      default:
        break;
    }
  }
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
  for ($i = 1; $i < count($char); $i += CharacterPieces()) {
    if ($char[$i + 6] == 1) $char[$i] = 0; //Destroy if it was flagged for destruction
    if ($char[$i] != 0) {
      $char[$i] = 2;
      $char[$i + 4] = CharacterNumUsesPerTurn($char[$i - 1]);
    }
  }
}

function MainCharacterHitAbilities()
{
  global $combatChain, $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  $attackID = $combatChain[0];
  $mainCharacter = &GetPlayerCharacter($mainPlayer);

  for($i = 0; $i < count($mainCharacter); $i += CharacterPieces()) {
    switch($characterID) {

      default:
        break;
    }
  }
}

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
      case "NfbZ0nouSQ": if(!IsAlly($combatChain[0])) $modifier += SearchCount(SearchBanish($mainPlayer,type:"WEAPON")); break;
      default: break;
    }
  }
  return $modifier;
}

function MainCharacterHitEffects()
{
  global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  $modifier = 0;
  $mainCharacterEffects = &GetMainCharacterEffects($mainPlayer);
  for($i = 0; $i < count($mainCharacterEffects); $i += 2) {
    if($mainCharacterEffects[$i] == $combatChainState[$CCS_WeaponIndex]) {
      switch($mainCharacterEffects[$i + 1]) {
        case "CgyJxpEgzk"://Spirit Blade: Infusion
          Draw($mainPlayer);
          break;
        default: break;
      }
    }
  }
  return $modifier;
}

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

function CharacterCostModifier($cardID, $from)
{
  global $currentPlayer, $CS_NumSwordAttacks;
  $modifier = 0;
  if(CardSubtype($cardID) == "Sword" && GetClassState($currentPlayer, $CS_NumSwordAttacks) == 1 && SearchCharacterActive($currentPlayer, "CRU077")) {
    --$modifier;
  }
  return $modifier;
}

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

function AllyDealDamageAbilities($player, $damage) {
  $allies = &GetAllies($player);
  for($i = count($allies) - AllyPieces(); $i >= 0; $i -= AllyPieces()) {
    switch($allies[$i]) {
      case "3c60596a7a":
        $ally = new Ally("MYALLY-" . $i, $player);
        if ($ally->NumUses() > 0) {
          $ally->ModifyUses(-1);
          Draw($player);
        } 
        break;
    }
  }
}
?>
