<?php

//Every single function with the exception of the PlayCardAttempt() functions at the bottom of this file are entirely used to make sure the card/ability the AI wants to play is actually legal before it attempts anything.
//This might be a bit to dig through, but you don't really need to understand any of this to create priority arrays.
//If you do have questions though, just ping me, @Etasus, and I can help you out.

function CardIsBlockable($storedPriorityNode)
{
  global $combatChain, $combatChainState, $CCS_NumChainLinks, $currentPlayer;
  if($storedPriorityNode[1] == "Character")
  {
    $character = &GetPlayerCharacter($currentPlayer);
    if($character[$storedPriorityNode[2]+6] == 1 || $character[$storedPriorityNode[2]+1] != 2) return false;
    //WriteLog("character[i+6]->".$character[$storedPriorityNode[2]+6]);
  }
  switch($combatChain[0])
  {
    case "CRU054": return !(ComboActive() && CardCost($storedPriorityNode[0]) < $combatChainState[$CCS_NumChainLinks]);
    case "CRU056": return false; //I have no idea how to make Heron's Flight work, so I'm just gonna say it's unblockable. This is so edge case that no one will know for a while lmfaooooo
    case "CRU057":
    case "CRU058":
    case "CRU059": return !(ComboActive() && AttackValue($storedPriorityNode[0]) > $combatChainState[$CCS_NumChainLinks]);
    default: return true;
  }
}

function CardIsPlayable($storedPriorityNode, $hand, $resources)
{
  if(CardIsPrevented($storedPriorityNode[0])) return false;
  switch($storedPriorityNode[1])
  {
    case "Hand":
      $index = $storedPriorityNode[2];
      $baseCost = CardCost($storedPriorityNode[0]);
      break;
    case "Arsenal":
      if(ArsenalIsFrozen($storedPriorityNode)) return false;
      $index = -1;
      $baseCost = CardCost($storedPriorityNode[0]);
      break;
    case "Character":
      if(CharacterIsUsed($storedPriorityNode)) return false;
      $index = -1;
      $baseCost = AbilityCost($storedPriorityNode[0]);
      break;
    case "Item":
      $index = -1;
      $baseCost = AbilityCost($storedPriorityNode[0]);
      break;
    case "Ally":
      $index = -1;
      $baseCost = AbilityCost($storedPriorityNode[0]);
      break;
    default:
      WriteLog("ERROR: AI is storedPriorityNode an uncheckable card for playability. Please log a bug report.");
      return false;
  }
  $finalCost = $baseCost + RogSelfCostMod($storedPriorityNode[0]) + RogCharacterCostMod($storedPriorityNode[0]) + RogAuraCostMod($storedPriorityNode[0]) + RogEffectCostMod($storedPriorityNode[0]);
  $totalPitch = $resources[0];
  for($i = 0; $i < count($hand); ++$i)
  {
    if($i != $index) $totalPitch = $totalPitch + PitchValue($hand[$i]);
  }
  return $finalCost <= $totalPitch;
}

function ReactionCardIsPlayable($storedPriorityNode, $hand, $resources)
{
  return CardIsPlayable($storedPriorityNode, $hand, $resources) && ReactionRequirementsMet($storedPriorityNode);
}

function CardIsPitchable($storedPriorityNode)
{
  global $currentTurnEffects, $currentPlayer, $CS_PlayUniqueID, $turn;
  for ($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    if ($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch ($currentTurnEffects[$i]) {
        case "ELE035": return CardCost($storedPriorityNode[0]) != 0;
        default:
          break;
      }
    }
  }
  return true;
}

function CardIsArsenalable($storedPriorityNode)
{
  return true; //currently there are no cards in the game that prevent something from being arsenaled. When there is, this is an easy entry point to set it up.
}

function RogSelfCostMod($cardID)
{
  global $CS_NumCharged, $currentPlayer, $combatChain, $CS_LayerTarget;
  switch ($cardID) {
    case "ARC080":
      return (-1 * NumRunechants($currentPlayer));
    case "ARC082":
      return (-1 * NumRunechants($currentPlayer));
    case "ARC088":
    case "ARC089":
    case "ARC090":
      return (-1 * NumRunechants($currentPlayer));
    case "ARC094":
    case "ARC095":
    case "ARC096":
      return (-1 * NumRunechants($currentPlayer));
    case "ARC097":
    case "ARC098":
    case "ARC099":
      return (-1 * NumRunechants($currentPlayer));
    case "ARC100":
    case "ARC101":
    case "ARC102":
      return (-1 * NumRunechants($currentPlayer));
    case "MON032":
      return (-1 * (2 * GetClassState($currentPlayer, $CS_NumCharged)));
    case "MON084":
    case "MON085":
    case "MON086":
      return TalentContains($combatChain[GetClassState($currentPlayer, $CS_LayerTarget)], "SHADOW") ? -1 : 0;
    case "DYN104":
    case "DYN105":
    case "DYN106":
      $numHypers = 0;
      $numHypers += CountItem("ARC036", $currentPlayer);
      $numHypers += CountItem("DYN111", $currentPlayer);
      $numHypers += CountItem("DYN112", $currentPlayer);
      return $numHypers > 0 ? -1 : 0;
    case "WTR206": case "WTR207": case "WTR208":
      if(GetPlayerCharacter($currentPlayer)[0] == "ROGUE030"){
        return -1;
      }
    default:
      return 0;
  }
}

function RogCharacterCostMod($cardID) //this currently serves no purpose except to give us an entry point for future effects as we make them, like Kassai.
{
  global $currentPlayer;
  $modifier = 0;
  return $modifier;
}

function RogAuraCostMod($cardID)
{
  global $currentPlayer;
  $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
  $myAuras = &GetAuras($currentPlayer);
  $theirAuras = &GetAuras($otherPlayer);
  $modifier = 0;
  for ($i = count($myAuras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    switch ($myAuras[$i]) {
      case "ELE111":
        $modifier += 1;
        break;
      default:
        break;
    }
  }

  for ($i = count($theirAuras) - AuraPieces(); $i >= 0; $i -= AuraPieces()) {
    switch ($theirAuras[$i]) {
      case "ELE146":
        $modifier += 1;
        break;
      default:
        break;
    }
  }
  return $modifier;
}

function RogEffectCostMod($cardID)
{
  global $currentTurnEffects, $currentPlayer, $CS_PlayUniqueID;
  $from = "-"; //I currently don't want to figure out how "from" works, so I'm just gonna do this and hope for the best. If something breaks, we'll fix it.
  $costModifier = 0;

  return $costModifier;
}

function CardIsPrevented($cardID)
{
  global $currentTurnEffects, $currentPlayer, $CS_PlayUniqueID, $turn;
  for ($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    if ($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch ($currentTurnEffects[$i]) {

        default:
          break;
      }
    }
  }
  return false;
}

function CharacterIsUsed($storedPriorityNode)
{
  global $currentPlayer;
  $character = &GetPlayerCharacter($currentPlayer);
  if($character[$storedPriorityNode[2]+5] < 1 || $character[$storedPriorityNode[2]+1] != 2) return true;
  else return false;
}

function ArsenalIsFrozen($storedPriorityNode)
{
  global $currentPlayer;
  $arsenal = &GetArsenal($currentPlayer);
  if($arsenal[$storedPriorityNode[2]+4] == 1) return true;
  else return false;
}

function ReactionRequirementsMet($storedPriorityNode)
{
  global $combatChain, $combatChainState, $CCS_NumChainLinks, $mainPlayer, $currentPlayer, $CS_NumNonAttackCards, $CS_AtksWWeapon;
  switch($storedPriorityNode[0])
  {

    default: return false;
  }
}


//This is just a way for me to seperate the actual act of playing a card from the main AI function block. They basically just check where the card is being played from, and then make the relevant inputs.
function BlockCardAttempt($storedPriorityNode)
{
  global $currentPlayer;
  switch($storedPriorityNode[1])
  {
    case "Hand":
      ProcessInput($currentPlayer, 27, "", $storedPriorityNode[2], 0, "");
      CacheCombatResult();
      break;
    case "Character":
      ProcessInput($currentPlayer, 3, "", $storedPriorityNode[2], 0, "");
      CacheCombatResult();
      break;
    default: WriteLog("ERROR: AI attempting to block with an unblockable card. Please log a bug report."); break;
  }
}

function PlayCardAttempt($storedPriorityNode)
{
  global $currentPlayer;
  switch($storedPriorityNode[1])
  {
    case "Hand":
      ProcessInput($currentPlayer, 27, "", $storedPriorityNode[2], 0, "");
      CacheCombatResult();
      break;
    case "Arsenal":
      ProcessInput($currentPlayer, 5, "", $storedPriorityNode[2], 0, "");
      CacheCombatResult();
      break;
    case "Character":
      ProcessInput($currentPlayer, 3, "", $storedPriorityNode[2], 0, "");
      CacheCombatResult();
      break;
    case "Item":
      ProcessInput($currentPlayer, 10, "", $storedPriorityNode[2], 0, "");
      CacheCombatResult();
      break;
    case "Ally":
      ProcessInput($currentPlayer, 24, "", $storedPriorityNode[2], 0, "");
      CacheCombatResult();
      break;
    default: WriteLog("ERROR: AI attempting to play an unplayable card. Please log a bug report."); PassInput(); break;
  }
}

function PitchCardAttempt($storedPriorityNode)
{
  global $currentPlayer;
  switch($storedPriorityNode[1])
  {
    case "Hand":
      ProcessInput($currentPlayer, 27, "", $storedPriorityNode[2], 0, "");
      CacheCombatResult();
      break;
    default: WriteLog("ERROR: AI attempting to pitch an unpitchable card. Please log a bug report."); break;
  }
}

function ArsenalCardAttempt($storedPriorityNode)
{
  global $currentPlayer;
  switch($storedPriorityNode[1])
  {
    case "Hand":
      ProcessInput($currentPlayer, 4, "", $storedPriorityNode[0], 0, "");
      CacheCombatResult();
      break;
    default: WriteLog("ERROR: AI attempting to arsenal an unarsenalable card. Please log a bug report."); break;
  }
}

function FixHand($currentPlayer)
{
  $hand = &GetHand($currentPlayer);
  $fix = [];
  for($i = 0; $i < count($hand); ++$i)
  {
    if($hand[$i] != "") array_push($fix, $hand[$i]);
  }
  $hand = $fix;
}
?>
