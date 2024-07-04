<?php


function ProcessHitEffect($cardID)
{
  global $mainPlayer, $combatChainState, $CCS_DamageDealt, $defPlayer;
  if(HitEffectsArePrevented()) return;
  switch($cardID)
  {
    case "0828695133"://Seventh Sister
      if(GetAttackTarget() == "THEIRCHAR-0") {
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a card to deal 3 damage", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,3", 1);
      }
      break;
    case "3280523224"://Rukh
      if(IsAllyAttackTarget() && $combatChainState[$CCS_DamageDealt] > 0) {
        $ally = new Ally(GetAttackTarget(), $defPlayer);
        if(!DefinedTypesContains($ally->CardID(), "Leader", $defPlayer)) {
          DestroyAlly($defPlayer, $ally->Index());
        }
      }
      break;
    case "87e8807695"://Leia Organa
      AddCurrentTurnEffect("87e8807695", $mainPlayer);
      break;
    default: break;
  }
  AllyHitEffects();
}

function CompletesAttackEffect($cardID) {
  global $mainPlayer, $defPlayer, $CS_NumLeftPlay;
  switch($cardID)
  {
    case "9560139036"://Ezra Bridger
      AddCurrentTurnEffect("9560139036", $mainPlayer);
      break;
    case "0e65f012f5"://Boba Fett
      if(GetClassState($defPlayer, $CS_NumLeftPlay) > 0) ReadyResource($mainPlayer, 2);
      break;
    case "9647945674"://Zeb Orrelios
      if(GetAttackTarget() == "NA") {//This means the target was defeated
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to deal 4 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "DEALDAMAGE,4", 1);
      }
      break;
    case "0518313150"://Embo
      if(GetAttackTarget() == "NA") {//This means the target was defeated
        AddDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to restore 2 damage");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $mainPlayer, "RESTORE,2", 1);
      }
      break;
    case "1086021299"://Arquitens Assault Cruiser
      if(GetAttackTarget() == "NA") {//This means the target was defeated
        $discard = &GetDiscard($defPlayer);
        $defeatedCard = RemoveDiscard($defPlayer, count($discard)-DiscardPieces());
        AddResources($defeatedCard, $mainPlayer, "PLAY", "DOWN");
      }
      break;
    default: break;
  }
}

function AttackModifier($cardID, $player, $index)
{
  global $mainPlayer, $defPlayer, $initiativePlayer, $combatChain, $combatChainState, $CS_NumLeftPlay;
  $modifier = 0;
  if($player == $mainPlayer) {
    //Raid is only for attackers
    $attacker = AttackerMZID($mainPlayer);
    $mzArr = explode("-", $attacker);
    if($mzArr[1] == $index) $modifier = RaidAmount($cardID, $mainPlayer, $mzArr[1]);
  }
  switch($cardID) {
    case "3988315236"://Seasoned Shoretrooper
      $modifier += NumResources($player) >= 6 ? 2 : 0;
      break;
    case "7922308768"://Valiant Assault Ship
      $modifier += $player == $mainPlayer && NumResources($mainPlayer) < NumResources($defPlayer) ? 2 : 0;
      break;
    case "6348804504"://Ardent Sympathizer
      $modifier += $initiativePlayer == $player ? 2 : 0;
      break;
    case "4619930426"://First Legion Snowtrooper
      if(count($combatChain) == 0 || $player == $defPlayer) break;
      $target = GetAttackTarget();
      if($target == "THEIRCHAR-0") break;
      $ally = new Ally($target, $defPlayer);
      $modifier += $ally->IsDamaged() ? 2 : 0;
      break;
    case "7648077180"://97th Legion
      $modifier += NumResources($player);
      break;
    case "8def61a58e"://Kylo Ren
      $hand = &GetHand($player);
      $modifier -= count($hand)/HandPieces();
      break;
    case "7486516061"://Concord Dawn Interceptors
      if($player == $defPlayer && GetAttackTarget() == "THEIRALLY-" . $index) $modifier += 2;
      break;
    case "6769342445"://Jango Fett
      if(IsAllyAttackTarget() && $player == $mainPlayer) {
        $ally = new Ally(GetAttackTarget(), $defPlayer);
        if($ally->HasBounty()) $modifier += 3;
      }
      break;
    case "4511413808"://Follower of the Way
      $ally = new Ally("MYALLY-" . $index, $player);
      if($ally->NumUpgrades() > 0) $modifier += 1;
      break;
    case "58f9f2d4a0"://Dr. Aphra
      $discard = &GetDiscard($player);
      $costs = [];
      for($i = 0; $i < count($discard); $i += DiscardPieces()) {
        $cost = CardCost($discard[$i]);
        $costs[$cost] = true;
      }
      if(count($costs) >= 5) $modifier += 3;
      break;
    case "8305828130"://Warbird Stowaway
        $modifier += $initiativePlayer == $player ? 2 : 0;
        break;
    default: break;
  }
  return $modifier;
}

function BlockModifier($cardID, $from, $resourcesPaid)
{
  global $defPlayer, $CS_CardsBanished, $mainPlayer, $CS_ArcaneDamageTaken, $combatChain, $chainLinks;
  $blockModifier = 0;
  switch($cardID) {

    default: break;
  }
  return $blockModifier;
}

function PlayBlockModifier($cardID)
{
  switch($cardID) {

    default: return 0;
  }
}

function OnDefenseReactionResolveEffects()
{
  global $currentTurnEffects, $defPlayer, $combatChain;
  switch($combatChain[0])
  {
      default: break;
  }
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $defPlayer) {
      switch($currentTurnEffects[$i]) {

        default: break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
}

function OnBlockResolveEffects()
{

  
}

function BeginningReactionStepEffects()
{
  global $combatChain, $mainPlayer, $defPlayer;
  switch($combatChain[0])
  {
    case "OUT050":
      if(ComboActive())
      {
        $blockingCards = GetChainLinkCards($defPlayer);
        if($blockingCards != "")
        {
          $blockArr = explode(",", $blockingCards);
          $index = $blockArr[GetRandom(0, count($blockArr) - 1)];
          AddDecisionQueue("PASSPARAMETER", $defPlayer, $index, 1);
          AddDecisionQueue("REMOVECOMBATCHAIN", $defPlayer, "-", 1);
          AddDecisionQueue("MULTIBANISH", $defPlayer, "CC,-", 1);
        }
      }
  }
}

function ModifyBlockForType($type, $amount)
{
  global $combatChain, $defPlayer;
  $count = 0;
  for($i=CombatChainPieces(); $i<count($combatChain); $i+=CombatChainPieces())
  {
    if($combatChain[$i+1] != $defPlayer) continue;
    if(CardType($combatChain[$i]) != $type) continue;
    ++$count;
    $combatChain[$i+6] += $amount;
  }
  return $count;
}

function OnBlockEffects($index, $from)
{
  global $currentTurnEffects, $combatChain, $currentPlayer, $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  $cardType = CardType($combatChain[$index]);
  $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch($currentTurnEffects[$i]) {
        case "WTR092": case "WTR093": case "WTR094":
          if(HasCombo($combatChain[$index])) {
            $combatChain[$index + 6] += 2;
          }
          $remove = true;
          break;
        case "ELE004":
          if($cardType == "DR") {
            PlayAura("ELE111", $currentPlayer);
          }
          break;
        case "DYN042": case "DYN043": case "DYN044":
          if(ClassContains($combatChain[$index], "GUARDIAN", $currentPlayer) && CardSubType($combatChain[$index]) == "Off-Hand")
          {
            if($currentTurnEffects[$i] == "DYN042") $amount = 6;
            else if($currentTurnEffects[$i] == "DYN043") $amount = 5;
            else $amount = 4;
            $combatChain[$index + 6] += $amount;
            $remove = true;
          }
          break;
        case "DYN115": case "DYN116":
          if($cardType == "AA") $combatChain[$index + 6] -= 1;
          break;
        case "OUT005": case "OUT006":
          if($cardType == "AR") $combatChain[$index + 6] -= 1;
          break;
        case "OUT007": case "OUT008":
          if($cardType == "A") $combatChain[$index + 6] -= 1;
          break;
        case "OUT009": case "OUT010":
          if($cardType == "E") $combatChain[$index + 6] -= 1;
          break;
        default:
          break;
      }
    } else if($currentTurnEffects[$i + 1] == $otherPlayer) {
      switch($currentTurnEffects[$i]) {
        case "MON113": case "MON114": case "MON115":
          if($cardType == "AA" && NumAttacksBlocking() == 1) {
              AddCharacterEffect($otherPlayer, $combatChainState[$CCS_WeaponIndex], $currentTurnEffects[$i]);
              WriteLog(CardLink($currentTurnEffects[$i], $currentTurnEffects[$i]) . " gives your weapon +1 for the rest of the turn.");
          }
          break;
        default:
          break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
  $currentTurnEffects = array_values($currentTurnEffects);
  switch($combatChain[0]) {
    case "CRU079": case "CRU080":
      if($cardType == "AA" && NumAttacksBlocking() == 1) {
        AddCharacterEffect($otherPlayer, $combatChainState[$CCS_WeaponIndex], $combatChain[0]);
        WriteLog(CardLink($combatChain[0], $combatChain[0]) . " got +1 for the rest of the turn.");
      }
      break;
    default:
      break;
  }
}

function CombatChainCloseAbilities($player, $cardID, $chainLink)
{
  global $chainLinkSummary, $mainPlayer, $defPlayer, $chainLinks;
  switch($cardID) {
    case "EVR002":
      if($chainLinkSummary[$chainLink*ChainLinkSummaryPieces()] == 0 && $chainLinks[$chainLink][0] == $cardID) {
        PlayAura("WTR225", $defPlayer);
      }
      break;
    case "UPR189":
      if($chainLinkSummary[$chainLink*ChainLinkSummaryPieces()+1] <= 2) {
        Draw($player);
        WriteLog(CardLink($cardID, $cardID) . " drew a card");
      }
      break;
    case "DYN121":
      if($player == $mainPlayer) PlayerLoseHealth($mainPlayer, GetHealth($mainPlayer));
      break;
    default:
      break;
  }
}

function NumNonEquipmentDefended()
{
  global $combatChain, $defPlayer;
  $number = 0;
  for($i = 0; $i < count($combatChain); $i += CombatChainPieces()) {
    $cardType = CardType($combatChain[$i]);
    if($combatChain[$i + 1] == $defPlayer && $cardType != "E" && $cardType != "C") ++$number;
  }
  return $number;
}

function CombatChainPlayAbility($cardID)
{
  global $combatChain, $defPlayer;
  for($i = 0; $i < count($combatChain); $i += CombatChainPieces()) {
    switch($combatChain[$i]) {
      case "EVR122":
        if(ClassContains($cardID, "WIZARD", $defPlayer)) {
          $combatChain[$i + 6] += 2;
          WriteLog(CardLink($combatChain[$i], $combatChain[$i]) . " gets +2 defense");
        }
        break;
      default: break;
    }
  }
}

function IsDominateActive()
{
  global $currentTurnEffects, $mainPlayer, $CCS_WeaponIndex, $combatChain, $combatChainState;
  global $CS_NumAuras, $CCS_NumBoosted, $chainLinks, $chainLinkSummary;
  if(count($combatChain) == 0) return false;
  if(SearchCurrentTurnEffectsForCycle("EVR097", "EVR098", "EVR099", $mainPlayer)) return false;
  $characterEffects = GetCharacterEffects($mainPlayer);
  for($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i + 1] == $mainPlayer && IsCombatEffectActive($currentTurnEffects[$i]) && !IsCombatEffectLimited($i) && DoesEffectGrantDominate($currentTurnEffects[$i])) return true;
  }
  for($i = 0; $i < count($characterEffects); $i += CharacterEffectPieces()) {
    if($characterEffects[$i] == $combatChainState[$CCS_WeaponIndex]) {
      switch($characterEffects[$i + 1]) {
        case "WTR122": return true;
        default: break;
      }
    }
  }
  switch($combatChain[0]) {
    case "WTR095": case "WTR096": case "WTR097": return (ComboActive() ? true : false);
    case "WTR179": case "WTR180": case "WTR181": return true;
    case "ARC080": return true;
    case "MON004": return true;
    case "MON023": case "MON024": case "MON025": return true;
    case "MON246": return SearchDiscard($mainPlayer, "AA") == "";
    case "MON275": case "MON276": case "MON277": return true;
    case "ELE209": case "ELE210": case "ELE211": return HasIncreasedAttack();
    case "EVR027": case "EVR028": case "EVR029": return true;
    case "EVR038": return (ComboActive() ? true : false);
    case "EVR076": case "EVR077": case "EVR078": return $combatChainState[$CCS_NumBoosted] > 0;
    case "EVR110": case "EVR111": case "EVR112": return GetClassState($mainPlayer, $CS_NumAuras) > 0;
    case "EVR138":
      $hasDominate = false;
      for($i = 0; $i < count($chainLinks); ++$i)
      {
        for($j = 0; $j < count($chainLinks[$i]); $j += ChainLinksPieces())
        {
          $isIllusionist = ClassContains($chainLinks[$i][$j], "ILLUSIONIST", $mainPlayer) || ($j == 0 && DelimStringContains($chainLinkSummary[$i*ChainLinkSummaryPieces()+3], "ILLUSIONIST"));
          if($chainLinks[$i][$j+2] == "1" && $chainLinks[$i][$j] != "EVR138" && $isIllusionist && CardType($chainLinks[$i][$j]) == "AA")
          {
              if(!$hasDominate) $hasDominate = HasDominate($chainLinks[$i][$j]);
          }
        }
      }
      return $hasDominate;
    case "OUT027": case "OUT028": case "OUT029": return true;
    default: break;
  }
  return false;
}

function IsOverpowerActive()
{
  global $combatChain, $mainPlayer;
  if(count($combatChain) == 0) return false;
  switch($combatChain[0]) {
    case "DYN068": return SearchCurrentTurnEffects("DYN068", $mainPlayer);
    case "DYN088": return true;
    case "DYN227": case "DYN228": case "DYN229": return SearchCurrentTurnEffects("DYN227", $mainPlayer);
    case "DYN492a": return true;
    default: break;
  }
  return false;
}


?>
