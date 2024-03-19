<?php

  include "CardSetters.php";
  include "CardGetters.php";

function EvaluateCombatChain(&$totalAttack, &$totalDefense, &$attackModifiers=[])
{
  global $combatChain, $mainPlayer, $currentTurnEffects, $defCharacter, $playerID, $combatChainState, $CCS_LinkBaseAttack;
  global $CCS_WeaponIndex, $mainCharacter, $mainAuras;
    UpdateGameState($playerID);
    BuildMainPlayerGameState();
    $attackType = CardType($combatChain[0]);
    $canGainAttack = CanGainAttack();
    $snagActive = SearchCurrentTurnEffects("CRU182", $mainPlayer) && $attackType == "AA";
    for($i=1; $i<count($combatChain); $i+=CombatChainPieces())
    {
      $from = $combatChain[$i+1];
      $resourcesPaid = $combatChain[$i+2];

      if($combatChain[$i] == $mainPlayer)
      {
        if($i == 1) $attack = $combatChainState[$CCS_LinkBaseAttack];
        else $attack = AttackValue($combatChain[$i-1]);
        if($canGainAttack || $i == 1 || $attack < 0)
        {
          array_push($attackModifiers, $combatChain[$i-1]);
          array_push($attackModifiers, $attack);
          if($i == 1) $totalAttack += $attack;
          else AddAttack($totalAttack, $attack);
        }
        $attack = AttackModifier($combatChain[$i-1], $combatChain[$i+1], $combatChain[$i+2], $combatChain[$i+3]) + $combatChain[$i + 4];
        if(($canGainAttack && !$snagActive) || $attack < 0)
        {
          array_push($attackModifiers, $combatChain[$i-1]);
          array_push($attackModifiers, $attack);
          AddAttack($totalAttack, $attack);
        }
      }
      else
      {
        $totalDefense += BlockingCardDefense($i-1, $combatChain[$i+1], $combatChain[$i+2]);
      }
    }

    //Now check current turn effects
    for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces())
    {
      if(IsCombatEffectActive($currentTurnEffects[$i]) && !IsCombatEffectLimited($i))
      {
        if($currentTurnEffects[$i+1] == $mainPlayer)
        {
          $attack = EffectAttackModifier($currentTurnEffects[$i]);
          if(($canGainAttack || $attack < 0) && !($snagActive && $currentTurnEffects[$i] == $combatChain[0]))
          {
            array_push($attackModifiers, $currentTurnEffects[$i]);
            array_push($attackModifiers, $attack);
            AddAttack($totalAttack, $attack);
          }
        }
      }
    }

    if($combatChainState[$CCS_WeaponIndex] != -1)
    {
      $attack = 0;
      if($attackType == "W") $attack = $mainCharacter[$combatChainState[$CCS_WeaponIndex]+3];
      else if(DelimStringContains(CardSubtype($combatChain[0]), "Aura")) $attack = $mainAuras[$combatChainState[$CCS_WeaponIndex]+3];
      else if(CardTypeContains($combatChain[0], "ALLY", $mainPlayer))
      {
        $allies = &GetAllies($mainPlayer);
        $attack = $allies[$combatChainState[$CCS_WeaponIndex]+7];
      }
      if($canGainAttack || $attack < 0)
      {
        array_push($attackModifiers, "+1 Attack Counters");
        array_push($attackModifiers, $attack);
        AddAttack($totalAttack, $attack);
      }
    }
    $attack = MainCharacterAttackModifiers();
    if($canGainAttack || $attack < 0)
    {
      array_push($attackModifiers, "Character/Equipment");
      array_push($attackModifiers, $attack);
      AddAttack($totalAttack, $attack);
    }
    $attack = AuraAttackModifiers(0);
    if($canGainAttack || $attack < 0)
    {
      array_push($attackModifiers, "Aura Ability");
      array_push($attackModifiers, $attack);
      AddAttack($totalAttack, $attack);
    }
    $attack = ArsenalAttackModifier();
    if($canGainAttack || $attack < 0)
    {
      array_push($attackModifiers, "Arsenal Ability");
      array_push($attackModifiers, $attack);
      AddAttack($totalAttack, $attack);
    }
}

function CharacterLevel($player)
{
  global $CS_CachedCharacterLevel;
  return GetClassState($player, $CS_CachedCharacterLevel);
}

function CalculateCharacterLevel($player)
{
  $char = &GetPlayerCharacter($player);
  if(count($char) == 0) return 0;
  $level = CardLevel($char[0]);
  switch($char[0])
  {
    case "g92bHLtTNl": $level += SearchCount(SearchBanish($player, element:"ARCANE")); break;//Rai, Storm Seer
    default: break;
  }
  return $level + CurrentEffectLevelModifier($player) + AllyLevelModifiers($player) + ItemLevelModifiers($player);
}

function CacheCharacterLevel()
{
  global $CS_CachedCharacterLevel;
  SetClassState(1, $CS_CachedCharacterLevel, CalculateCharacterLevel(1));
  SetClassState(2, $CS_CachedCharacterLevel, CalculateCharacterLevel(2));
}

function AddAttack(&$totalAttack, $amount)
{
  global $combatChain;
  if($amount > 0 && $combatChain[0] == "OUT100") $amount += 1;
  if($amount > 0 && ($combatChain[0] == "OUT065" || $combatChain[0] == "OUT066" || $combatChain[0] == "OUT067") && ComboActive()) $amount += 1;
  if($amount > 0) $amount += PermanentAddAttackAbilities();
  $totalAttack += $amount;
}

function BlockingCardDefense($index, $from="", $resourcesPaid=-1)
{
  global $combatChain, $defPlayer, $mainPlayer, $currentTurnEffects;
  $from = $combatChain[$index+2];
  $resourcesPaid = $combatChain[$index+3];
  $defense = BlockValue($combatChain[$index]) + BlockModifier($combatChain[$index], $from, $resourcesPaid) + $combatChain[$index + 6];
  if(CardType($combatChain[$index]) == "E")
  {
    $defCharacter = &GetPlayerCharacter($defPlayer);
    $charIndex = FindDefCharacter($combatChain[$index]);
    $defense += $defCharacter[$charIndex+4];
  }
  for ($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnPieces()) {
    if (IsCombatEffectActive($currentTurnEffects[$i]) && !IsCombatEffectLimited($i)) {
      if ($currentTurnEffects[$i + 1] == $defPlayer) {
        $defense += EffectBlockModifier($currentTurnEffects[$i], index:$index);
      }
    }
  }
  if($defense < 0) $defense = 0;
  return $defense;
}

function AddCombatChain($cardID, $player, $from, $resourcesPaid)
{
  global $combatChain, $turn;
  $index = count($combatChain);
  array_push($combatChain, $cardID);
  array_push($combatChain, $player);
  array_push($combatChain, $from);
  array_push($combatChain, $resourcesPaid);
  array_push($combatChain, RepriseActive());
  array_push($combatChain, 0);//Attack modifier
  array_push($combatChain, 0);//Defense modifier
  if($turn[0] == "B" || CardType($cardID) == "DR") OnBlockEffects($index, $from);
  CurrentEffectAttackAbility();
  return $index;
}

function CombatChainPowerModifier($index, $amount)
{
  global $combatChain;
  $combatChain[$index+5] += $amount;
  ProcessPhantasmOnBlock($index);
}

function CacheCombatResult()
{
  global $combatChain, $combatChainState, $CCS_CachedTotalAttack, $CCS_CachedTotalBlock, $CCS_CachedDominateActive, $CCS_CachedNumBlockedFromHand, $CCS_CachedOverpowerActive;
  global $CSS_CachedNumActionBlocked, $CCS_CachedNumDefendedFromHand;
  if(count($combatChain) > 0)
  {
    $combatChainState[$CCS_CachedTotalAttack] = 0;
    $combatChainState[$CCS_CachedTotalBlock] = 0;
    EvaluateCombatChain($combatChainState[$CCS_CachedTotalAttack], $combatChainState[$CCS_CachedTotalBlock]);
    $combatChainState[$CCS_CachedDominateActive] = (IsDominateActive() ? "1" : "0");
    if ($combatChainState[$CCS_CachedNumBlockedFromHand] == 0) $combatChainState[$CCS_CachedNumBlockedFromHand] = NumBlockedFromHand();
    $combatChainState[$CCS_CachedOverpowerActive] = (IsOverpowerActive() ? "1" : "0");
    $combatChainState[$CSS_CachedNumActionBlocked] = NumActionBlocked();
    $combatChainState[$CCS_CachedNumDefendedFromHand] = NumDefendedFromHand(); //Reprise
  }
  CacheCharacterLevel();
}

function CachedTotalAttack()
{
  global $combatChainState, $CCS_CachedTotalAttack;
  return $combatChainState[$CCS_CachedTotalAttack];
}

function CachedTotalBlock()
{
  global $combatChainState, $CCS_CachedTotalBlock;
  return $combatChainState[$CCS_CachedTotalBlock];
}

function CachedDominateActive()
{
  global $combatChainState, $CCS_CachedDominateActive;
  return ($combatChainState[$CCS_CachedDominateActive] == "1" ? true : false);
}

function CachedOverpowerActive()
{
  global $combatChainState, $CCS_CachedOverpowerActive;
  return ($combatChainState[$CCS_CachedOverpowerActive] == "1" ? true : false);
}

function CachedNumBlockedFromHand() //Dominate
{
  global $combatChainState, $CCS_CachedNumBlockedFromHand;
  return $combatChainState[$CCS_CachedNumBlockedFromHand];
}

function CachedNumDefendedFromHand() //Reprise
{
  global $combatChainState, $CCS_CachedNumDefendedFromHand;
  return $combatChainState[$CCS_CachedNumDefendedFromHand];
}

function CachedNumActionBlocked()
{
  global $combatChainState, $CSS_CachedNumActionBlocked;
  return $combatChainState[$CSS_CachedNumActionBlocked];
}

function AddFloatingMemoryChoice($fromDQ=false)
{
  global $currentPlayer;
  if($fromDQ)
  {

  }
  else {
    $items = &GetItems($currentPlayer);
    for($i=0; $i<count($items); $i+=ItemPieces()) {
      switch($items[$i]) {
        case "h23qu7d6so"://Temporal Spectrometer
          AddDecisionQueue("YESNO", $currentPlayer, "if you want to sacrifice Temporal Spectrometer to reduce the cost");
          AddDecisionQueue("NOPASS", $currentPlayer, "-");
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYITEMS-" . $i, 1);
          AddDecisionQueue("MZBANISH", $currentPlayer, "PLAY," . $items[$i], 1);
          AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
          for($j=0; $j<$items[$i+1]; $j++) {
            AddDecisionQueue("DECDQVAR", $currentPlayer, "0", 1);
          }
          break;
        default: break;
      }
    }
    AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:floatingMemoryOnly=true");
    AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a floating memory card to banish", 1);
    AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
    AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
    AddDecisionQueue("MULTIBANISH", $currentPlayer, "GY,-", 1);
    AddDecisionQueue("DECDQVAR", $currentPlayer, "0", 1);
  }
}

//This is always called from the decision queue
function StartTurn()
{
  global $dqState, $currentPlayer, $mainPlayer, $turn, $firstPlayer, $currentTurn;
  $mainPlayer = $currentPlayer;
  $dqState[1] = "M";
  $turn[0] = "M";
  CharacterStartTurnAbility(0);
  AllyStartTurnAbilities($mainPlayer);
  if(!IsDecisionQueueActive()) ProcessDecisionQueue();
  ReturnAllMemoryToHand($currentPlayer);
  if($mainPlayer != $firstPlayer || $currentTurn > 1) Draw($currentPlayer);
}

//Recollection
function ReturnAllMemoryToHand($player)
{
  ItemBeginRecollectionAbilities();
  $memory = &GetMemory($player);
  for($i=count($memory)-MemoryPieces(); $i>=0; $i-=MemoryPieces())
  {
    AddHand($player, RemoveMemory($player, $i));
  }
  if(!IsDecisionQueueActive()) ProcessDecisionQueue();
}

function StartTurnAbilities()
{
  global $mainPlayer, $defPlayer;
  MZStartTurnMayAbilities();
  AuraStartTurnAbilities();
  ItemStartTurnAbilities();
}

function MZStartTurnMayAbilities()
{
  global $mainPlayer;
  AddDecisionQueue("FINDINDICES", $mainPlayer, "MZSTARTTURN");
  AddDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a start turn ability to activate (or pass)", 1);
  AddDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
  AddDecisionQueue("MZSTARTTURNABILITY", $mainPlayer, "-", 1);
}

function MZStartTurnIndices()
{
  global $mainPlayer;
  $mainDiscard = &GetDiscard($mainPlayer);
  $cards = "";
  for($i=0; $i<count($mainDiscard); $i+=DiscardPieces())
  {
    switch($mainDiscard[$i])
    {
      case "UPR086":
        if(ThawIndices($mainPlayer) != "")
        {
          $cards = CombineSearches($cards, SearchMultiZoneFormat($i, "MYDISCARD")); break;
        }
      default: break;
    }
  }
  return $cards;
}

function ArsenalStartTurnAbilities()
{
  global $mainPlayer;
  $arsenal = &GetArsenal($mainPlayer);
  for($i=0; $i<count($arsenal); $i+=ArsenalPieces())
  {
    switch($arsenal[$i])
    {
      case "MON404": case "MON405": case "MON406": case "MON407": case "DVR007": case "RVD007":
        if($arsenal[$i+1] == "DOWN")
        {
          AddDecisionQueue("YESNO", $mainPlayer, "if_you_want_to_turn_your_mentor_card_face_up");
          AddDecisionQueue("NOPASS", $mainPlayer, "-");
          AddDecisionQueue("PASSPARAMETER", $mainPlayer, $i, 1);
          AddDecisionQueue("TURNARSENALFACEUP", $mainPlayer, $i, 1);
        }
        break;
      default: break;
    }
  }
}

function ArsenalAttackAbilities()
{
  global $combatChain, $mainPlayer;
  $attackID = $combatChain[0];
  $attackType = CardType($attackID);
  $attackVal = AttackValue($attackID);
  $arsenal = GetArsenal($mainPlayer);
  for($i=0; $i<count($arsenal); $i+=ArsenalPieces())
  {
    switch($arsenal[$i])
    {

      default: break;
    }
  }
}

function ArsenalAttackModifier()
{
  global $combatChain, $mainPlayer;
  $attackID = $combatChain[0];
  $attackType = CardType($attackID);
  $arsenal = GetArsenal($mainPlayer);
  $modifier = 0;
  for($i=0; $i<count($arsenal); $i+=ArsenalPieces())
  {
    switch($arsenal[$i])
    {
      default: break;
    }
  }
  return $modifier;
}

function ArsenalHitEffects()
{
  global $combatChain, $mainPlayer;
  $attackID = $combatChain[0];
  $attackType = CardType($attackID);
  $attackSubType = CardSubType($attackID);
  $arsenal = GetArsenal($mainPlayer);
  $modifier = 0;
  for($i=0; $i<count($arsenal); $i+=ArsenalPieces())
  {
    switch($arsenal[$i])
    {

      default: break;
    }
  }
  return $modifier;
}

function CharacterPlayCardAbilities($cardID, $from)
{
  global $currentPlayer, $CS_NumLess3PowAAPlayed, $CS_NumAttacks;
  $character = &GetPlayerCharacter($currentPlayer);
  for($i=0; $i<count($character); $i+=CharacterPieces())
  {
    if($character[$i+1] != 2) continue;
    $characterID = ShiyanaCharacter($character[$i]);
    switch($characterID)
    {

      default:
        break;
    }
  }
  $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
  $otherCharacter = &GetPlayerCharacter($otherPlayer);
  for($i=0; $i<count($otherCharacter); $i+=CharacterPieces())
  {
    $characterID = $otherCharacter[$i];
    switch($characterID)
    {
      default:
        break;
    }
  }
}

function MainCharacterPlayCardAbilities($cardID, $from)
{
  global $currentPlayer, $mainPlayer, $CS_NumNonAttackCards, $CS_NumBoostPlayed;
  $character = &GetPlayerCharacter($currentPlayer);
  for($i = 0; $i < count($character); $i += CharacterPieces()) {
    if($character[$i+1] != 2) continue;
    switch($character[$i]) {
      case "zdIhSL5RhK": case "g92bHLtTNl": case "6ILtLfjQEe":
        if(ClassContains($cardID, "MAGE"))
        {
          PlayAura("ENLIGHTEN", $currentPlayer);
          $character[$i+1] = 1;
        }
        break;
      default:
        break;
    }
  }
}

function ArsenalPlayCardAbilities($cardID)
{
  global $currentPlayer;
  $cardType = CardType($cardID);
  $arsenal = GetArsenal($currentPlayer);
  for($i=0; $i<count($arsenal); $i+=ArsenalPieces())
  {
    switch($arsenal[$i])
    {
      default: break;
    }
  }
}

function HasIncreasedAttack()
{
  global $combatChain;
  if(count($combatChain) > 0)
  {
    $attack = 0;
    $defense = 0;
    EvaluateCombatChain($attack, $defense);
    if($attack > AttackValue($combatChain[0])) return true;
  }
  return false;
}

function DamageTrigger($player, $damage, $type, $source="NA", $canPass=false)
{
  AddDecisionQueue("DEALDAMAGE", $player, $damage . "-" . $source . "-" . $type, ($canPass ? 1 : "0"));
  return $damage;
}

function CanDamageBePrevented($player, $damage, $type, $source="-")
{
  global $mainPlayer;
  if($source == "aebjvwbciz" && IsClassBonusActive($mainPlayer, "GUARDIAN") && CharacterLevel($mainPlayer) >= 2) return false;
  return true;
}

function DealDamageAsync($player, $damage, $type="DAMAGE", $source="NA")
{
  global $CS_DamagePrevention, $combatChainState, $combatChain, $mainPlayer;
  global $CCS_AttackFused, $CS_ArcaneDamagePrevention, $currentPlayer, $dqVars, $dqState;

  $classState = &GetPlayerClassState($player);
  $Items = &GetItems($player);
  if($type == "COMBAT" && $damage > 0 && EffectPreventsHit()) HitEffectsPreventedThisLink();
  if($type == "COMBAT" || $type == "ATTACKHIT") $source = $combatChain[0];
  $otherPlayer = $player == 1 ? 2 : 1;
  $damage = $damage > 0 ? $damage : 0;
  $damageThreatened = $damage;
  $preventable = CanDamageBePrevented($player, $damage, $type, $source);
  if($preventable)
  {
    $damage = CurrentEffectPreventDamagePrevention($player, $type, $damage, $source);
    if(ConsumeDamagePrevention($player)) return 0;//If damage can be prevented outright, don't use up your limited damage prevention
    if($type == "ARCANE")
    {
      if($damage <= $classState[$CS_ArcaneDamagePrevention])
      {
        $classState[$CS_ArcaneDamagePrevention] -= $damage;
        $damage = 0;
      }
      else
      {
        $damage -= $classState[$CS_ArcaneDamagePrevention];
        $classState[$CS_ArcaneDamagePrevention] = 0;
      }
    }
    if($damage <= $classState[$CS_DamagePrevention])
    {
      $classState[$CS_DamagePrevention] -= $damage;
      $damage = 0;
    }
    else
    {
      $damage -= $classState[$CS_DamagePrevention];
      $classState[$CS_DamagePrevention] = 0;
    }
  }
  //else: CR 2.0 6.4.10h If damage is not prevented, damage prevention effects are not consumed
  $damage = $damage > 0 ? $damage : 0;
  $damage = CurrentEffectDamagePrevention($player, $type, $damage, $source, $preventable);
  $damage = AuraTakeDamageAbilities($player, $damage, $type);
  $damage = PermanentTakeDamageAbilities($player, $damage, $type);
  $damage = ItemTakeDamageAbilities($player, $damage, $type);
  if($damage == 1 && $preventable && SearchItemsForCard("EVR069", $player) != "") $damage = 0;//Must be last
  $dqVars[0] = $damage;
  if($type == "COMBAT") $dqState[6] = $damage;
  PrependDecisionQueue("FINALIZEDAMAGE", $player, $damageThreatened . "," . $type . "," . $source);
  if($damage > 0)
  {
    AddDamagePreventionSelection($player, $damage, $preventable);
  }
  return $damage;
}

function AddDamagePreventionSelection($player, $damage, $preventable)
{
  PrependDecisionQueue("PROCESSDAMAGEPREVENTION", $player, $damage . "-" . $preventable, 1);
  PrependDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  PrependDecisionQueue("SETDQCONTEXT", $player, "Choose a card to prevent damage", 1);
  PrependDecisionQueue("FINDINDICES", $player, "DAMAGEPREVENTION");
}

function FinalizeDamage($player, $damage, $damageThreatened, $type, $source)
{
  global $otherPlayer, $CS_DamageTaken, $combatChainState, $CCS_AttackTotalDamage, $CS_ArcaneDamageTaken, $defPlayer, $mainPlayer;
  global $CCS_AttackFused;
  $classState = &GetPlayerClassState($player);
  $otherPlayer = $player == 1 ? 2 : 1;
  if($damage > 0)
  {
    if($source != "NA")
    {
      $damage += CurrentEffectDamageModifiers($player, $source, $type);
      if($type == "COMBAT" && HasCleave($source)) DamagePlayerAllies($player, $damage, $source, $type);
    }

    AuraDamageTakenAbilities($player, $damage);
    ItemDamageTakenAbilities($player, $damage);
    CharacterDamageTakenAbilities($player, $damage);
    CharacterDealDamageAbilities($otherPlayer, $damage);
    $classState[$CS_DamageTaken] += $damage;
    if($player == $defPlayer && $type == "COMBAT" || $type == "ATTACKHIT") $combatChainState[$CCS_AttackTotalDamage] += $damage;
    if($type == "ARCANE") $classState[$CS_ArcaneDamageTaken] += $damage;
    CurrentEffectDamageEffects($player, $source, $type, $damage);
  }
  PlayerLoseHealth($player, $damage);
  LogDamageStats($player, $damageThreatened, $damage);
  return $damage;
}

function DoQuell($targetPlayer, $damage)
{
  $quellChoices = QuellChoices($targetPlayer, $damage);
  if ($quellChoices != "0") {
    PrependDecisionQueue("PAYRESOURCES", $targetPlayer, "<-", 1);
    PrependDecisionQueue("AFTERQUELL", $targetPlayer, "-", 1);
    PrependDecisionQueue("BUTTONINPUT", $targetPlayer, $quellChoices);
    PrependDecisionQueue("SETDQCONTEXT", $targetPlayer, "Choose an amount to pay for Quell");
  } else {
    PrependDecisionQueue("PASSPARAMETER", $targetPlayer, "0"); //If no quell, we need to discard the previous last result
  }
}

function ProcessDealDamageEffect($cardID)
{
  $set = CardSet($cardID);
  if($set == "UPR") {
    return UPRDealDamageEffect($cardID);
  }
}

function ArcaneDamagePrevented($player, $cardMZIndex)
{
  $prevented = 0;
  $params = explode("-", $cardMZIndex);
  $zone = $params[0];
  $index = $params[1];
  switch($zone)
  {
    case "MYCHAR": $source = &GetPlayerCharacter($player); break;
    case "MYITEMS": $source = &GetItems($player); break;
    case "MYAURAS": $source = &GetAuras($player); break;
  }
  if($zone == "MYCHAR" && $source[$index+1] == 0) return;
  $cardID = $source[$index];
  $spellVoidAmount = SpellVoidAmount($cardID, $player);
  if($spellVoidAmount > 0)
  {
    if($zone == "MYCHAR") DestroyCharacter($player, $index);
    else if($zone == "MYITEMS") DestroyItemForPlayer($player, $index);
    else if($zone == "MYAURAS") DestroyAura($player, $index);
    $prevented += $spellVoidAmount;
    WriteLog(CardLink($cardID, $cardID) . " was destroyed and prevented " . $spellVoidAmount . " arcane damage.");
  }
  return $prevented;
}

function CurrentEffectDamageModifiers($player, $source, $type)
{
  global $currentTurnEffects;
  $modifier = 0;
  for($i=count($currentTurnEffects)-CurrentTurnPieces(); $i >= 0; $i-=CurrentTurnPieces())
  {
    $remove = 0;
    switch($currentTurnEffects[$i])
    {
      default: break;
    }
    if($remove == 1) RemoveCurrentTurnEffect($i);
  }
  return $modifier;
}

function CurrentEffectDamageEffects($target, $source, $type, $damage)
{
  global $currentTurnEffects;
  if(CardType($source) == "AA" && (SearchAuras("CRU028", 1) || SearchAuras("CRU028", 2))) return;
  for($i=count($currentTurnEffects)-CurrentTurnPieces(); $i >= 0; $i-=CurrentTurnPieces())
  {
    if($currentTurnEffects[$i+1] == $target) continue;
    if($type == "COMBAT" && HitEffectsArePrevented()) continue;
    $remove = 0;
    switch($currentTurnEffects[$i])
    {

      default: break;
    }
    if($remove == 1) RemoveCurrentTurnEffect($i);
  }
}

function AttackDamageAbilities($damageDone)
{
  global $combatChain, $defPlayer;
  $attackID = $combatChain[0];
  switch($attackID)
  {
    default: break;
  }
}

function LoseHealth($amount, $player)
{
  PlayerLoseHealth($player, $amount);
}

function GainHealth($amount, $player)
{
  $otherPlayer = ($player == 1 ? 2 : 1);
  $health = &GetHealth($player);
  $otherHealth = &GetHealth($otherPlayer);
  if(SearchCurrentTurnEffects("MON229", $player)) { WriteLog(CardLink("MON229","MON229") . " prevented you from gaining health."); return; }
  if(SearchCharacterForCard($player, "CRU140") || SearchCharacterForCard($otherPlayer, "CRU140"))
  {
    if($health > $otherHealth)
    {
      WriteLog("Reaping Blade prevented player " . $player . " from gaining " . $amount . " health.");
      return false;
    }
  }
  WriteLog("Player " . $player . " gained " . $amount . " health.");
  $health += $amount;
  return true;
}

function PlayerLoseHealth($player, $amount)
{
  $health = &GetHealth($player);
  $amount = AuraLoseHealthAbilities($player, $amount);
  $char = &GetPlayerCharacter($player);
  if(count($char) == 0) return;
  $health += $amount;
  if($health >= CharacterHealth($char[0]))
  {
    PlayerWon(($player == 1 ? 2 : 1));
  }
}

function IsGameOver()
{
  global $inGameStatus, $GameStatus_Over;
  return $inGameStatus == $GameStatus_Over;
}

function PlayerWon($playerID)
{
  global $winner, $turn, $gameName, $p1id, $p2id, $p1uid, $p2uid, $p1IsChallengeActive, $p2IsChallengeActive, $conceded, $currentTurn;
  global $p1DeckLink, $p2DeckLink, $inGameStatus, $GameStatus_Over, $firstPlayer, $p1deckbuilderID, $p2deckbuilderID;
  if($turn[0] == "OVER") return;
  include_once "./MenuFiles/ParseGamefile.php";

  $winner = $playerID;
  if ($playerID == 1 && $p1uid != "") WriteLog($p1uid . " wins!", $playerID);
  elseif ($playerID == 2 && $p2uid != "") WriteLog($p2uid . " wins!", $playerID);
  else WriteLog("Player " . $winner . " wins!");

  $inGameStatus = $GameStatus_Over;
  $turn[0] = "OVER";
  try {
    logCompletedGameStats();
  } catch (Exception $e) {

  }

  if(!$conceded || $currentTurn >= 3) {
    //If this happens, they left a game in progress -- add disconnect logging?
  }
}

function UnsetBanishModifier($player, $modifier, $newMod="DECK")
{
  $banish = &GetBanish($player);
  for($i=0; $i<count($banish); $i+=BanishPieces())
  {
    $cardModifier = explode("-", $banish[$i+1])[0];
    if($cardModifier == $modifier) $banish[$i+1] = $newMod;
  }
}

function UnsetChainLinkBanish()
{
  UnsetBanishModifier(1, "TCL");
  UnsetBanishModifier(2, "TCL");
}

function UnsetCombatChainBanish()
{
  UnsetBanishModifier(1, "TCC");
  UnsetBanishModifier(2, "TCC");
  UnsetBanishModifier(1, "TCL");
  UnsetBanishModifier(2, "TCL");
}

function ReplaceBanishModifier($player, $oldMod, $newMod)
{
  UnsetBanishModifier($player, $oldMod, $newMod);
}

function UnsetTurnBanish()
{
  global $defPlayer;
  UnsetBanishModifier(1, "TT");
  UnsetBanishModifier(1, "INST");
  UnsetBanishModifier(2, "TT");
  UnsetBanishModifier(2, "INST");
  UnsetBanishModifier(1, "ARC119");
  UnsetBanishModifier(2, "ARC119");
  UnsetCombatChainBanish();
  ReplaceBanishModifier($defPlayer, "NT", "TT");
}

function GetChainLinkCards($playerID="", $cardType="", $exclCardTypes="")
{
  global $combatChain;
  $pieces = "";
  $exclArray=explode(",", $exclCardTypes);
  for($i=0; $i<count($combatChain); $i+=CombatChainPieces())
  {
    $thisType = CardType($combatChain[$i]);
    if(($playerID == "" || $combatChain[$i+1] == $playerID) && ($cardType == "" || $thisType == $cardType))
    {
      $excluded = false;
      for($j=0; $j<count($exclArray); ++$j)
      {
        if($thisType == $exclArray[$j]) $excluded = true;
      }
      if($excluded) continue;
      if($pieces != "") $pieces .= ",";
      $pieces .= $i;
    }
  }
  return $pieces;
}

function GetTheirEquipmentChoices()
{
  global $currentPlayer;
  return GetEquipmentIndices(($currentPlayer == 1 ? 2 : 1));
}

function FindMyCharacter($cardID)
{
  global $currentPlayer;
  return FindCharacterIndex($currentPlayer, $cardID);
}

function FindDefCharacter($cardID)
{
  global $defPlayer;
  return FindCharacterIndex($defPlayer, $cardID);
}

function ChainLinkResolvedEffects()
{
  global $combatChain, $mainPlayer, $currentTurnEffects;
  if($combatChain[0] == "MON245" && !ExudeConfidenceReactionsPlayable())
  {
    AddCurrentTurnEffect($combatChain[0], $mainPlayer, "CC");
  }
  switch($combatChain[0])
  {
    case "CRU051": case "CRU052":
      EvaluateCombatChain($totalAttack, $totalBlock);
      for ($i = CombatChainPieces(); $i < count($combatChain); $i += CombatChainPieces()) {
        if (!($totalBlock > 0 && (intval(BlockValue($combatChain[$i])) + BlockModifier($combatChain[$i], "CC", 0) + $combatChain[$i + 6]) > $totalAttack)) {
          UndestroyCurrentWeapon();
        }
      }
      break;
      default: break;
  }
}

function CombatChainClosedMainCharacterEffects()
{
  global $chainLinks, $chainLinkSummary, $combatChain, $mainPlayer;
  $character = &GetPlayerCharacter($mainPlayer);
  for($i=0; $i<count($chainLinks); ++$i)
  {
    for($j=0; $j<count($chainLinks[$i]); $j += ChainLinksPieces())
    {
      if($chainLinks[$i][$j+1] != $mainPlayer) continue;
      $charIndex = FindCharacterIndex($mainPlayer, $chainLinks[$i][$j]);
      if($charIndex == -1) continue;
      switch($chainLinks[$i][$j])
      {
        case "CRU051": case "CRU052":
          if($character[$charIndex+7] == "1") DestroyCharacter($mainPlayer, $charIndex);
          break;
        default: break;
      }
    }
  }
}

function CombatChainClosedCharacterEffects()
{
  global $chainLinks, $defPlayer, $chainLinkSummary, $combatChain;
  $character = &GetPlayerCharacter($defPlayer);
  for($i=0; $i<count($chainLinks); ++$i)
  {
    $nervesOfSteelActive = $chainLinkSummary[$i*ChainLinkSummaryPieces()+1] <= 2 && SearchAuras("EVR023", $defPlayer);
    for($j=0; $j<count($chainLinks[$i]); $j += ChainLinksPieces())
    {
      if($chainLinks[$i][$j+1] != $defPlayer) continue;
      $charIndex = FindCharacterIndex($defPlayer, $chainLinks[$i][$j]);
      if($charIndex == -1) continue;
      if(!$nervesOfSteelActive)
      {
        if(HasTemper($chainLinks[$i][$j]))
        {
          $character[$charIndex+4] -= 1;//Add -1 block counter
          if((BlockValue($character[$charIndex]) + $character[$charIndex + 4] + BlockModifier($character[$charIndex], "CC", 0) + $chainLinks[$i][$j + 5]) <= 0)
          {
            DestroyCharacter($defPlayer, $charIndex);
          }
        }
        if(HasBattleworn($chainLinks[$i][$j]))
        {
          $character[$charIndex+4] -= 1;//Add -1 block counter
        }
        else if(HasBladeBreak($chainLinks[$i][$j]))
        {
          DestroyCharacter($defPlayer, $charIndex);
        }
      }
      switch($chainLinks[$i][$j])
      {
        case "MON089":
          if(!DelimStringContains($chainLinkSummary[$i*ChainLinkSummaryPieces()+3], "ILLUSIONIST") && $chainLinkSummary[$i*ChainLinkSummaryPieces()+1] >= 6)
          {
            $character[FindCharacterIndex($defPlayer, "MON089")+1] = 0;
          }
          break;
        case "RVD003":
          Writelog("Processing " . Cardlink($chainLinks[$i][$j], $chainLinks[$i][$j]) . " trigger: ");
          $deck = &GetDeck($defPlayer);
          $rv = "";
          if (count($deck) == 0) $rv .= "Your deck is empty. No card is revealed.";
          $wasRevealed = RevealCards($deck[0]);
          if ($wasRevealed) {
            if (AttackValue($deck[0]) < 6) {
              WriteLog("The card was put on the bottom of your deck.");
              array_push($deck, array_shift($deck));
            }
          }
          break;
        default: break;
      }
    }
  }
}

// CR 2.1 - 5.3.4c A card with the type defense reaction becomes a defending card and is moved onto the current chain link instead of being moved to the graveyard.
function NumDefendedFromHand() //Reprise
{
  global $combatChain, $defPlayer;
  $num = 0;
  for($i=0; $i<count($combatChain); $i += CombatChainPieces())
  {
    if($combatChain[$i+1] == $defPlayer)
    {
      $type = CardType($combatChain[$i]);
      if($type != "I" && $combatChain[$i+2] == "HAND") ++$num;
    }
  }
  return $num;
}

function NumBlockedFromHand() //Dominate
{
  global $combatChain, $defPlayer, $layers;
  $num = 0;
  for ($i = 0; $i < count($combatChain); $i += CombatChainPieces()) {
    if ($combatChain[$i + 1] == $defPlayer) {
      $type = CardType($combatChain[$i]);
      if ($type != "I" && $combatChain[$i + 2] == "HAND") ++$num;
    }
  }
  for ($i = 0; $i < count($layers); $i += LayerPieces()) {
    $params = explode("|", $layers[$i + 2]);
    if ($params[0] == "HAND" && CardType($layers[$i]) == "DR") ++$num;
  }
  return $num;
}

function NumActionBlocked()
{
  global $combatChain, $defPlayer;
  $num = 0;
  for ($i = 0; $i < count($combatChain); $i += CombatChainPieces()) {
    if ($combatChain[$i + 1] == $defPlayer) {
      $type = CardType($combatChain[$i]);
      if ($type == "A" || $type == "AA") ++$num;
    }
  }
  return $num;
}

function NumCardsBlocking()
{
  global $combatChain, $defPlayer;
  $num = 0;
  for($i=0; $i<count($combatChain); $i += CombatChainPieces())
  {
    if($combatChain[$i+1] == $defPlayer)
    {
      $type = CardType($combatChain[$i]);
      if($type != "I" && $type != "C") ++$num;
    }
  }
  return $num;
}

function NumCardsNonEquipBlocking()
{
  global $combatChain, $defPlayer;
  $num = 0;
  for ($i = 0; $i < count($combatChain); $i += CombatChainPieces()) {
    if ($combatChain[$i + 1] == $defPlayer) {
      $type = CardType($combatChain[$i]);
      if ($type != "E" && $type != "I" && $type != "C") ++$num;
    }
  }
  return $num;
}

function NumAttacksBlocking()
{
  global $combatChain, $defPlayer;
  $num = 0;
  for($i=0; $i<count($combatChain); $i += CombatChainPieces())
  {
    if($combatChain[$i+1] == $defPlayer)
    {
      if(CardType($combatChain[$i]) == "AA") ++$num;
    }
  }
  return $num;
}

function NumActionsBlocking()
{
  global $combatChain, $defPlayer;
  $num = 0;
  for($i=0; $i<count($combatChain); $i += CombatChainPieces())
  {
    if($combatChain[$i+1] == $defPlayer)
    {
      $cardType = CardType($combatChain[$i]);
      if($cardType == "A" || $cardType == "AA") ++$num;
    }
  }
  return $num;
}

function NumNonAttackActionBlocking()
{
  global $combatChain, $defPlayer;
  $num = 0;
  for($i=0; $i<count($combatChain); $i += CombatChainPieces())
  {
    if($combatChain[$i+1] == $defPlayer)
    {
      $type = CardType($combatChain[$i]);
      if($type == "A") ++$num;
    }
  }
  return $num;
}

function NumReactionBlocking()
{
  global $combatChain, $defPlayer;
  $num = 0;
  for($i=0; $i<count($combatChain); $i += CombatChainPieces())
  {
    if($combatChain[$i+1] == $defPlayer)
    {
      $type = CardType($combatChain[$i]);
      if($type == "AR" || $type == "DR") ++$num;
    }
  }
  return $num;
}

function IHaveLessHealth()
{
  global $currentPlayer;
  return PlayerHasLessHealth($currentPlayer);
}

function DefHasLessHealth()
{
  global $defPlayer;
  return PlayerHasLessHealth($defPlayer);
}

function PlayerHasLessHealth($player)
{
  $otherPlayer = ($player == 1 ? 2 : 1);
  return GetHealth($player) < GetHealth($otherPlayer);
}

function PlayerHasFewerEquipment($player)
{
  $otherPlayer = ($player == 1 ? 2 : 1);
  $thisChar = &GetPlayerCharacter($player);
  $thatChar = &GetPlayerCharacter($otherPlayer);
  $thisEquip = 0;
  $thatEquip = 0;
  for($i=0; $i<count($thisChar); $i+=CharacterPieces())
  {
    if($thisChar[$i+1] != 0 && CardType($thisChar[$i]) == "E") ++$thisEquip;
  }
  for($i=0; $i<count($thatChar); $i+=CharacterPieces())
  {
    if($thatChar[$i+1] != 0 && CardType($thatChar[$i]) == "E") ++$thatEquip;
  }
  return $thisEquip < $thatEquip;
}

function GetIndices($count, $add=0, $pieces=1)
{
  $indices = "";
  for($i=0; $i<$count; $i+=$pieces)
  {
    if($indices != "") $indices .= ",";
    $indices .= ($i + $add);
  }
  return $indices;
}

function GetMyHandIndices()
{
  global $currentPlayer;
  return GetIndices(count(GetHand($currentPlayer)));
}

function GetDefHandIndices()
{
  global $defPlayer;
  return GetIndices(count(GetHand($defPlayer)));
}

function CurrentAttack()
{
  global $combatChain;
  if(count($combatChain) == 0) return "";
  return $combatChain[0];
}

function RollDie($player, $fromDQ=false, $subsequent=false)
{
  global $CS_DieRoll;
  $numRolls = 1 + CountCurrentTurnEffects("EVR003", $player);
  $highRoll = 0;
  for($i=0; $i<$numRolls; ++$i)
  {
    $roll = GetRandom(1, 6);
    WriteLog($roll . " was rolled.");
    if($roll > $highRoll) $highRoll = $roll;
  }
  AddEvent("ROLL", $highRoll);
  SetClassState($player, $CS_DieRoll, $highRoll);
  $GGActive = HasGamblersGloves(1) || HasGamblersGloves(2);
  if($GGActive)
  {
    if($fromDQ && !$subsequent) PrependDecisionQueue("AFTERDIEROLL", $player, "-");
    GamblersGloves($player, $player, $fromDQ);
    GamblersGloves(($player == 1 ? 2 : 1), $player, $fromDQ);
    if(!$fromDQ && !$subsequent) AddDecisionQueue("AFTERDIEROLL", $player, "-");
  }
  else
  {
    if(!$subsequent) AfterDieRoll($player);
  }
}

function AfterDieRoll($player)
{
  global $CS_DieRoll, $CS_HighestRoll;
  $roll = GetClassState($player, $CS_DieRoll);
  $skullCrusherIndex = FindCharacterIndex($player, "EVR001");
  if($skullCrusherIndex > -1 && IsCharacterAbilityActive($player, $skullCrusherIndex))
  {
    if($roll == 1) { WriteLog("Skull Crushers was destroyed."); DestroyCharacter($player, $skullCrusherIndex); }
    if($roll == 5 || $roll == 6) { WriteLog("Skull Crushers gives +1 this turn."); AddCurrentTurnEffect("EVR001", $player); }
  }
  if($roll > GetClassState($player, $CS_HighestRoll)) SetClassState($player, $CS_HighestRoll, $roll);
}

function HasGamblersGloves($player)
{
  $gamblersGlovesIndex = FindCharacterIndex($player, "CRU179");
  return $gamblersGlovesIndex != -1 && IsCharacterAbilityActive($player, $gamblersGlovesIndex);
}

function GamblersGloves($player, $origPlayer, $fromDQ)
{
  $gamblersGlovesIndex = FindCharacterIndex($player, "CRU179");
  if(HasGamblersGloves($player))
  {
    if($fromDQ)
    {
      PrependDecisionQueue("ROLLDIE", $origPlayer, "1", 1);
      PrependDecisionQueue("DESTROYCHARACTER", $player, "-", 1);
      PrependDecisionQueue("PASSPARAMETER", $player, $gamblersGlovesIndex, 1);
      PrependDecisionQueue("NOPASS", $player, "-");
      PrependDecisionQueue("YESNO", $player, "if_you_want_to_destroy_Gambler's_Gloves_to_reroll_the_result");
    }
    else
    {
      AddDecisionQueue("YESNO", $player, "if_you_want_to_destroy_Gambler's_Gloves_to_reroll_the_result");
      AddDecisionQueue("NOPASS", $player, "-");
      AddDecisionQueue("PASSPARAMETER", $player, $gamblersGlovesIndex, 1);
      AddDecisionQueue("DESTROYCHARACTER", $player, "-", 1);
      AddDecisionQueue("ROLLDIE", $origPlayer, "1", 1);
    }
  }
}

function IsCharacterAbilityActive($player, $index, $checkGem=false)
{
  $character = &GetPlayerCharacter($player);
  if($checkGem && $character[$index+9] == 0) return false;
  return $character[$index+1] == 2;
}

function GetDieRoll($player)
{
  global $CS_DieRoll;
  return GetClassState($player, $CS_DieRoll);
}

function ClearDieRoll($player)
{
  global $CS_DieRoll;
  return SetClassState($player, $CS_DieRoll, 0);
}

function CanPlayAsInstant($cardID, $index=-1, $from="")
{
  global $currentPlayer, $CS_NextWizardNAAInstant, $CS_NextNAAInstant, $CS_CharacterIndex, $CS_ArcaneDamageTaken, $CS_NumWizardNonAttack;
  global $mainPlayer, $CS_PlayedAsInstant;
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $cardType = CardType($cardID);
  $otherCharacter = &GetPlayerCharacter($otherPlayer);
  if($cardID == "MON034" && SearchItemsForCard("DYN066", $currentPlayer) != "") return true;
  if(GetClassState($currentPlayer, $CS_NextWizardNAAInstant))
  {
    if(ClassContains($cardID, "WIZARD", $currentPlayer) && $cardType == "A") return true;
  }
  if(GetClassState($currentPlayer, $CS_NumWizardNonAttack) && ($cardID == "CRU174" || $cardID == "CRU175" || $cardID == "CRU176")) return true;
  if($currentPlayer != $mainPlayer && ($cardID == "CRU165" || $cardID == "CRU166" || $cardID == "CRU167")) return true;
  if(GetClassState($currentPlayer, $CS_NextNAAInstant))
  {
    if($cardType == "A") return true;
  }
  if($cardType == "C" || $cardType == "E" || $cardType == "W")
  {
    if($index == -1) $index = GetClassState($currentPlayer, $CS_CharacterIndex);
    if(SearchCharacterEffects($currentPlayer, $index, "INSTANT")) return true;
  }
  if($from == "BANISH")
  {
    $banish = GetBanish($currentPlayer);
    if($index < count($banish))
    {
      $mod = explode("-", $banish[$index+1])[0];
      if(($cardType == "I" && ($mod == "TCL" || $mod == "TT" || $mod == "TCC" || $mod == "NT" || $mod == "MON212")) || $mod == "INST" || $mod == "ARC119") return true;
    }
  }
  if(GetClassState($currentPlayer, $CS_PlayedAsInstant) == "1") return true;
  if($cardID == "ELE106" || $cardID == "ELE107" || $cardID == "ELE108") { return PlayerHasFused($currentPlayer); }
  if($cardID == "CRU143") { return GetClassState($otherPlayer, $CS_ArcaneDamageTaken) > 0; }
  if($from == "ARS" && $cardType == "A" && $currentPlayer != $mainPlayer && PitchValue($cardID) == 3 && (SearchCharacterActive($currentPlayer, "EVR120") || SearchCharacterActive($currentPlayer, "UPR102") || SearchCharacterActive($currentPlayer, "UPR103") || (SearchCharacterActive($currentPlayer, "CRU097") && SearchCurrentTurnEffects($otherCharacter[0] . "-SHIYANA", $currentPlayer) && IsIyslander($otherCharacter[0])))) return true;
  $isStaticType = IsStaticType($cardType, $from, $cardID);
  $abilityType = "-";
  if($isStaticType) $abilityType = GetAbilityType($cardID, $index, $from);
  if(($cardType == "AR" || ($abilityType == "AR" && $isStaticType)) && IsReactionPhase() && $currentPlayer == $mainPlayer) return true;
  if(($cardType == "DR" || ($abilityType == "DR" && $isStaticType)) && IsReactionPhase() && $currentPlayer != $mainPlayer && IsDefenseReactionPlayable($cardID, $from)) return true;
  return false;
}

function HasLostClass($player)
{
  if(SearchCurrentTurnEffects("UPR187", $player)) return true;//Erase Face
  return false;
}

function ClassOverride($cardID, $player="")
{
  global $currentTurnEffects;
  $cardClass = CardClass($cardID);
  if ($cardClass == "NONE") $cardClass = "";
  $otherPlayer = ($player == 1 ? 2 : 1);
  $otherCharacter = &GetPlayerCharacter($otherPlayer);

  if(SearchCurrentTurnEffects("UPR187", $player)) return "NONE";//Erase Face
  if(count($otherCharacter) > 0 && SearchCurrentTurnEffects($otherCharacter[0] . "-SHIYANA", $player)) {
    if($cardClass != "") $cardClass .= ",";
    $cardClass .= CardClass($otherCharacter[0]) . ",SHAPESHIFTER";
  }

  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
  {
    if($currentTurnEffects[$i+1] != $player) continue;
    $toAdd = "";
    switch($currentTurnEffects[$i])
    {
      case "MON095": case "MON096": case "MON097": $toAdd = "ILLUSIONIST";
      case "EVR150": case "EVR151": case "EVR152": $toAdd = "ILLUSIONIST";
      case "UPR155": case "UPR156": case "UPR157": $toAdd = "ILLUSIONIST";
      default: break;
    }
    if($toAdd != "")
    {
      if($cardClass != "") $cardClass .= ",";
      $cardClass .= $toAdd;
    }
  }
  if($cardClass == "") return "NONE";
  return $cardClass;
}

function NameOverride($cardID, $player="")
{
  $name = CardName($cardID);
  if(SearchCurrentTurnEffects("OUT183", $player)) $name = "";
  return $name;
}

function CardTypeContains($cardID, $type, $player="")
{
  $cardTypes = CardTypes($cardID);
  return DelimStringContains($cardTypes, $type);
}

function ClassContains($cardID, $class, $player="")
{
  $cardClass = ClassOverride($cardID, $player);
  return DelimStringContains($cardClass, $class);
}

function SubtypeContains($cardID, $subtype, $player="")
{
  $cardSubtype = CardSubtype($cardID);
  return DelimStringContains($cardSubtype, $subtype);
}

function ElementContains($cardID, $element, $player="")
{
  $cardElement = CardElement($cardID);
  return DelimStringContains($cardElement, $element);
}

function CardNameContains($cardID, $name, $player="")
{
  $cardName = NameOverride($cardID, $player);
  return DelimStringContains($cardName, $name);
}

function TalentOverride($cardID, $player="")
{
  global $currentTurnEffects;
  $cardTalent = CardTalent($cardID);
  //CR 2.2.1 - 6.3.6. Continuous effects that remove a property, or part of a property, from an object do not remove properties, or parts of properties, that were added by another effect.
  if(SearchCurrentTurnEffects("UPR187", $player)) $cardTalent = "NONE";
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
  {
    $toAdd = "";
    if($currentTurnEffects[$i+1] != $player) continue;
    switch($currentTurnEffects[$i])
    {
      case "UPR060": case "UPR061": case "UPR062": $toAdd = "DRACONIC";
      default: break;
    }
    if($toAdd != "")
    {
      if($cardTalent == "NONE") $cardTalent = "";
      if($cardTalent != "") $cardTalent .= ",";
      $cardTalent .= $toAdd;
    }
  }
  return $cardTalent;
}

function TalentContains($cardID, $talent, $player="")
{
  $cardTalent = TalentOverride($cardID, $player);
  return DelimStringContains($cardTalent, $talent);
}

function RevealCards($cards, $player="", $from="HAND")
{
  global $currentPlayer;
  if($player == "") $player = $currentPlayer;
  if(!CanRevealCards($player)) return false;
  $cardArray = explode(",", $cards);
  $string = "";
  for($i=count($cardArray)-1; $i>=0; --$i)
  {
    if($string != "") $string .= ", ";
    $string .= CardLink($cardArray[$i], $cardArray[$i]);
    AddEvent("REVEAL", $cardArray[$i]);
    OnRevealEffect($player, $cardArray[$i], $from, $i);
  }
  $string .= (count($cardArray) == 1 ? " is" : " are");
  $string .= " revealed.";
  WriteLog($string);
  return true;
}

function OnRevealEffect($player, $cardID, $from, $index)
{
  switch($cardID)
  {
    case "uwnHTLG3fL"://Luxem Sight
      if($from != "MEMORY") break;
      WriteLog("Player $player recovered 3 from revealing Luxem Sight");
      Recover($player, 3);
      break;
    case "zxB4tzy9iy"://Lightweaver's Assault
      if($from != "MEMORY") break;
      if(IsClassBonusActive($player, "ASSASSIN")) DealArcane(2, 2, "TRIGGER", $cardID, fromQueue:true, player:$player);
      break;
    case "qufoIF014c"://Gleaming Cut
      if($from != "MEMORY" || !IsClassBonusActive($player, "ASSASSIN")) break;
      AddDecisionQueue("YESNO", $player, "if you want to banish gleaming cut");
      AddDecisionQueue("NOPASS", $player, "-");
      AddDecisionQueue("PASSPARAMETER", $player, "MYMEMORY-" . ($index * MemoryPieces()), 1);
      AddDecisionQueue("MZBANISH", $player, "MEMORY,-," . $player, 1);
      AddDecisionQueue("MZREMOVE", $player, "-", 1);
      AddDecisionQueue("DRAW", $player, "-", 1);
      AddDecisionQueue("DRAW", $player, "-", 1);
      break;
    case "VAFTR5taNG"://Corhazi Infiltrator
      if($from != "MEMORY" || !IsClassBonusActive($player, "ASSASSIN")) break;
      AddDecisionQueue("YESNO", $player, "if you want to put Corhazi Infiltrator into play");
      AddDecisionQueue("NOPASS", $player, "-");
      AddDecisionQueue("PASSPARAMETER", $player, "MYMEMORY-" . ($index * MemoryPieces()), 1);
      AddDecisionQueue("SETDQVAR", $player, "0", 1);
      AddDecisionQueue("MZOP", $player, "GETCARDID", 1);
      AddDecisionQueue("PUTPLAY", $player, "-", 1);
      AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
      AddDecisionQueue("MZREMOVE", $player, "-", 1);
      break;
    default: break;
  }
}

function DoesAttackHaveGoAgain()
{
  global $combatChain, $combatChainState, $CCS_CurrentAttackGainedGoAgain, $mainPlayer, $defPlayer, $CS_NumRedPlayed, $CS_NumNonAttackCards;
  global $CS_NumAuras, $CS_ArcaneDamageTaken, $myDeck, $CS_AnotherWeaponGainedGoAgain;

  if(count($combatChain) == 0) return false;//No combat chain, so no
  $attackType = CardType($combatChain[0]);
  $attackSubtype = CardSubType($combatChain[0]);
  if(CurrentEffectPreventsGoAgain()) return false;
  if(HasGoAgain($combatChain[0])) return true;
  if($combatChainState[$CCS_CurrentAttackGainedGoAgain] == 1 || CurrentEffectGrantsGoAgain() || MainCharacterGrantsGoAgain()) return true;
  switch($combatChain[0])
  {

    default: break;
  }
  return false;
}

function IsEquipUsable($player, $index)
{
  $character = &GetPlayerCharacter($player);
  if($index >= count($character) || $index < 0) return false;
  return $character[$index + 1] == 2;
}


function UndestroyCurrentWeapon()
{
  global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  $index = $combatChainState[$CCS_WeaponIndex];
  $char = &GetPlayerCharacter($mainPlayer);
  $char[$index+7] = "0";
}

function DestroyCurrentWeapon()
{
  global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  $index = $combatChainState[$CCS_WeaponIndex];
  $char = &GetPlayerCharacter($mainPlayer);
  $char[$index+7] = "1";
}

function AttackDestroyed($attackID)
{
  global $mainPlayer, $combatChainState, $CCS_GoesWhereAfterLinkResolves;
  $type = CardType($attackID);
  $character = &GetPlayerCharacter($mainPlayer);
  switch($attackID)
  {

    default: break;
  }
  AttackDestroyedEffects($attackID);
}

function AttackDestroyedEffects($attackID)
{
  global $currentTurnEffects, $mainPlayer;
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
  {
    switch($currentTurnEffects[$i])
    {
      default: break;
    }
  }
}

function CloseCombatChain($chainClosed="true")
{
  global $turn, $currentPlayer, $mainPlayer, $combatChainState, $CCS_AttackTarget, $layers;
  $layers = [];//In case there's another combat chain related layer like defense step
  PrependLayer("FINALIZECHAINLINK", $mainPlayer, $chainClosed);
  $turn[0] = "M";
  $currentPlayer = $mainPlayer;
  $combatChainState[$CCS_AttackTarget] = "NA";
}

function UndestroyCharacter($player, $index)
{
  $char = &GetPlayerCharacter($player);
  $char[$index+1] = 2;
  $char[$index+4] = 0;
}

function DestroyCharacter($player, $index)
{
  $char = &GetPlayerCharacter($player);
  $char[$index+1] = 0;
  $char[$index+4] = 0;
  $cardID = $char[$index];
  if($char[$index+6] == 1) RemoveCombatChain(GetCombatChainIndex($cardID, $player));
  $char[$index+6] = 0;
  AddGraveyard($cardID, $player, "CHAR");
  CharacterDestroyEffect($cardID, $player);
  return $cardID;
}

function RemoveCharacter($player, $index)
{
  $char = &GetPlayerCharacter($player);
  $cardID = $char[$index];
  for($i=$index+CharacterPieces()-1; $i>=$index; --$i)
  {
    unset($char[$i]);
  }
  $char = array_values($char);
  return $cardID;
}

function AddDurabilityCounters($player, $amount=1)
{
  AddDecisionQueue("PASSPARAMETER", $player, $amount);
  AddDecisionQueue("SETDQVAR", $player, "0");
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYCHAR:type=WEAPON");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a weapon to add durability counter" . ($amount > 1 ? "s" : ""), 1);
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZOP", $player, "ADDDURABILITY", 1);
}

function RemoveCombatChain($index)
{
  global $combatChain;
  if($index < 0) return;
  for($i = CombatChainPieces() - 1; $i >= 0; --$i) {
    unset($combatChain[$index + $i]);
  }
  $combatChain = array_values($combatChain);
}

function RemoveArsenalEffects($player, $cardToReturn){
  SearchCurrentTurnEffects("EVR087", $player, true);
  SearchCurrentTurnEffects("ARC042", $player, true);
  if($cardToReturn == "ARC057" ){SearchCurrentTurnEffects("ARC057", $player, true);}
  if($cardToReturn == "ARC058" ){SearchCurrentTurnEffects("ARC058", $player, true);}
  if($cardToReturn == "ARC059" ){SearchCurrentTurnEffects("ARC059", $player, true);}
}

function LookAtHand($player)
{
  $hand = &GetHand($player);
  $cards = "";
  for($i=0; $i<count($hand); $i+=HandPieces())
  {
    if($cards != "") $cards .= ",";
    $cards .= $hand[$i];
  }
  RevealCards($cards, $player);
}

function GainActionPoints($amount=1, $player=0)
{
  global $actionPoints, $mainPlayer, $currentPlayer;
  if($player == 0) $player = $currentPlayer;
  if($player == $mainPlayer) $actionPoints += $amount;
}

function AddCharacterUses($player, $index, $numToAdd)
{
  $character = &GetPlayerCharacter($player);
  if($character[$index+1] == 0) return;
  $character[$index+1] = 2;
  $character[$index+5] += $numToAdd;
}

function HaveUnblockedEquip($player)
{
  $char = &GetPlayerCharacter($player);
  for($i=CharacterPieces(); $i<count($char); $i+=CharacterPieces())
  {
    if($char[$i+1] == 0) continue;//If broken
    if($char[$i+6] == 1) continue;//On combat chain
    if(CardType($char[$i]) != "E") continue;
    if(BlockValue($char[$i]) == -1) continue;
    return true;
  }
  return false;
}

function NumEquipBlock()
{
  global $combatChain, $defPlayer;
  $numEquipBlock = 0;
  for($i=CombatChainPieces(); $i<count($combatChain); $i+=CombatChainPieces())
  {
    if(CardType($combatChain[$i]) == "E" && $combatChain[$i + 1] == $defPlayer) ++$numEquipBlock;
  }
  return $numEquipBlock;
}

  function CanPassPhase($phase)
  {
    global $combatChainState, $CCS_RequiredEquipmentBlock, $currentPlayer;
    if($phase == "B" && HaveUnblockedEquip($currentPlayer) && NumEquipBlock() < $combatChainState[$CCS_RequiredEquipmentBlock]) return false;
    switch($phase)
    {
      case "P": return 0;
      case "PDECK": return 0;
      case "CHOOSEDECK": return 0;
      case "HANDTOPBOTTOM": return 0;
      case "CHOOSECOMBATCHAIN": return 0;
      case "CHOOSECHARACTER": return 0;
      case "CHOOSEHAND": return 0;
      case "CHOOSEHANDCANCEL": return 0;
      case "MULTICHOOSEDISCARD": return 0;
      case "CHOOSEDISCARDCANCEL": return 0;
      case "CHOOSEARCANE": return 0;
      case "CHOOSEARSENAL": return 0;
      case "CHOOSEDISCARD": return 0;
      case "MULTICHOOSEHAND": return 0;
      case "MULTICHOOSEMATERIAL": return 0;
      case "CHOOSEMULTIZONE": return 0;
      case "CHOOSEBANISH": return 0;
      case "BUTTONINPUTNOPASS": return 0;
      case "CHOOSEFIRSTPLAYER": return 0;
      case "MULTICHOOSEDECK": return 0;
      case "CHOOSEPERMANENT": return 0;
      case "MULTICHOOSETEXT": return 0;
      case "CHOOSEMYSOUL": return 0;
      case "OVER": return 0;
      default: return 1;
    }
  }

  //Returns true if done for that player
  function EndTurnPitchHandling($player)
  {
    global $currentPlayer, $turn;
    $pitch = &GetPitch($player);
    if(count($pitch) == 0)
    {
      return true;
    }
    else if(count($pitch) == 1)
    {
      PitchDeck($player, 0);
      return true;
    }
    else
    {
      $currentPlayer = $player;
      $turn[0] = "PDECK";
      return false;
    }
  }

  function ResolveGoAgain($cardID, $player, $from)
  {
    global $actionPoints;
    ++$actionPoints;
  }

  function PitchDeck($player, $index)
  {
    $deck = &GetDeck($player);
    $cardID = RemovePitch($player, $index);
    array_push($deck, $cardID);
  }

  function GetUniqueId()
  {
    global $permanentUniqueIDCounter;
    ++$permanentUniqueIDCounter;
    return $permanentUniqueIDCounter;
  }

  function IsHeroAttackTarget()
  {
    $target = explode("-", GetAttackTarget());
    return $target[0] == "THEIRCHAR";
  }

  function IsAllyAttackTarget()
  {
    $target = explode("-", GetAttackTarget());
    return $target[0] == "THEIRALLY";
  }

  function AttackIndex()
  {
    global $combatChainState, $CCS_WeaponIndex;
    return $combatChainState[$CCS_WeaponIndex];
  }

  function IsAttackTargetRested()
  {
    global $defPlayer;
    $target = GetAttackTarget();
    $mzArr = explode("-", $target);
    if($mzArr[0] == "ALLY" || $mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY")
    {
      $allies = &GetAllies($defPlayer);
      return $allies[$mzArr[1]+1] == 1;
    }
    else
    {
      $char = &GetPlayerCharacter($defPlayer);
      return $char[1] == 1;
    }
  }

  function IsSpecificAllyAttackTarget($player, $index)
  {
    $mzTarget = GetAttackTarget();
    $mzArr = explode("-", $mzTarget);
    if($mzArr[0] == "ALLY" || $mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY")
    {
      return $index == intval($mzArr[1]);
    }
    return false;
  }

  function IsAllyAttacking()
  {
    global $combatChain;
    if(count($combatChain) == 0) return false;
    return IsAlly($combatChain[0]);
  }

  function IsSpecificAllyAttacking($player, $index)
  {
    global $combatChain, $combatChainState, $CCS_WeaponIndex, $mainPlayer;
    if(count($combatChain) == 0) return false;
    if($mainPlayer != $player) return false;
    $weaponIndex = intval($combatChainState[$CCS_WeaponIndex]);
    if($weaponIndex == -1) return false;
    if($weaponIndex != $index) return false;
    if(!IsAlly($combatChain[0])) return false;
    return true;
  }

  function AttackerMZID($player)
  {
    global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
    if($player == $mainPlayer) return "MYALLY-" . $combatChainState[$CCS_WeaponIndex];
    else return "THEIRALLY-" . $combatChainState[$CCS_WeaponIndex];
  }

function IsSpecificAuraAttacking($player, $index)
{
  global $combatChain, $combatChainState, $CCS_WeaponIndex, $mainPlayer;
  if (count($combatChain) == 0) return false;
  if ($mainPlayer != $player) return false;
  $weaponIndex = intval($combatChainState[$CCS_WeaponIndex]);
  if ($weaponIndex == -1) return false;
  if ($weaponIndex != $index) return false;
  if (!DelimStringContains(CardSubtype($combatChain[0]), "Aura")) return false;
  return true;
}

function RevealMemory($player)
{
  $memory = &GetMemory($player);
  $toReveal = "";
  for($i=0; $i<count($memory); $i += MemoryPieces())
  {
    if($toReveal != "") $toReveal .= ",";
    $toReveal .= $memory[$i];
  }
  return RevealCards($toReveal, $player, "MEMORY");
}

  function CanRevealCards($player)
  {
    return true;
  }

  function BaseAttackModifiers($attackValue)
  {
    global $combatChainState, $CCS_LinkBaseAttack, $currentTurnEffects, $mainPlayer;
    for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
    {
      if($currentTurnEffects[$i+1] != $mainPlayer) continue;
      if(!IsCombatEffectActive($currentTurnEffects[$i])) continue;
      switch($currentTurnEffects[$i])
      {
        case "EVR094": case "EVR095": case "EVR096": $attackValue = ceil($attackValue/2); break;
        default: break;
      }
    }
    return $attackValue;
  }

  function GetDefaultLayerTarget()
  {
    global $layers, $combatChain, $currentPlayer;
    if(count($combatChain) > 0) return $combatChain[0];
    if(count($layers) > 0)
    {
      for($i=count($layers)-LayerPieces(); $i>=0; $i-=LayerPieces())
      {
        if($layers[$i+1] != $currentPlayer) return $layers[$i];
      }
    }
    return "-";
  }

function GetDamagePreventionIndices($player)
{
  $rv = "";
  $auras = &GetAuras($player);
  $indices = "";
  for($i=0; $i<count($auras); $i+=AuraPieces())
  {
    if(AuraDamagePreventionAmount($player, $i) > 0)
    {
      if($indices != "") $indices .= ",";
      $indices .= $i;
    }
  }
  $mzIndices = SearchMultiZoneFormat($indices, "MYAURAS");

  $char = &GetPlayerCharacter($player);
  $indices = "";
  for($i=0; $i<count($char); $i+=CharacterPieces())
  {
    if($char[$i+1] != 0 && WardAmount($char[$i]) > 0)
    {
      if($indices != "") $indices .= ",";
      $indices .= $i;
    }
  }
  $indices = SearchMultiZoneFormat($indices, "MYCHAR");
  $mzIndices = CombineSearches($mzIndices, $indices);

  $ally = &GetAllies($player);
  $indices = "";
  for($i=0; $i<count($ally); $i+=AllyPieces())
  {
    if($ally[$i+1] != 0 && WardAmount($ally[$i]) > 0)
    {
      if($indices != "") $indices .= ",";
      $indices .= $i;
    }
  }
  $indices = SearchMultiZoneFormat($indices, "MYALLY");
  $mzIndices = CombineSearches($mzIndices, $indices);
  $rv = $mzIndices;
  return $rv;
}

function GetDamagePreventionTargetIndices()
{
  global $combatChain, $currentPlayer;
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $rv = "";

  $rv = SearchMultizone($otherPlayer, "LAYER");
  if (count($combatChain) > 0) {
    if ($rv != "") $rv .= ",";
    $rv .= "CC-0";
  }
  if (SearchLayer($otherPlayer, "W") == "" && (count($combatChain) == 0 || CardType($combatChain[0]) != "W")) {
    $theirWeapon = SearchMultiZoneFormat(SearchCharacter($otherPlayer, type: "W"), "THEIRCHAR");
    $rv = CombineSearches($rv, $theirWeapon);
  }
  $theirAllies = SearchMultiZoneFormat(SearchAllies($otherPlayer), "THEIRALLY");
  $rv = CombineSearches($rv, $theirAllies);
  $theirAuras = SearchMultiZoneFormat(SearchAura($otherPlayer), "THEIRAURAS");
  $rv = CombineSearches($rv, $theirAuras);
  $theirHero = SearchMultiZoneFormat(SearchCharacter($otherPlayer, type: "C"), "THEIRCHAR");
  $rv = CombineSearches($rv, $theirHero);
  return $rv;
}

function SameWeaponEquippedTwice()
{
  global $mainPlayer;
  $char = &GetPlayerCharacter($mainPlayer);
  $weaponIndex = explode(",", SearchCharacter($mainPlayer, "W"));
  if (count($weaponIndex) > 1 && $char[$weaponIndex[0]] == $char[$weaponIndex[1]]) return true;
  return false;
}

function SelfCostModifier($cardID)
{
  global $currentPlayer, $CS_NumAttacks, $CS_LastAttack;
  $modifier = HasEfficiency($cardID) ? -1 * CharacterLevel($currentPlayer) : 0;
  switch($cardID) {
    case "145y6KBhxe": $modifier += (IsClassBonusActive($currentPlayer, "MAGE") ? -1 : 0); break;//Focused Flames
    case "RIVahUIQVD": $modifier += (IsClassBonusActive($currentPlayer, "MAGE") ? -2 : 0); break;//Fireball
    case "MwXulmKsIg": $modifier += (IsClassBonusActive($currentPlayer, "TAMER") ? -1 : 0); break;//Song of Return
    case "DBJ4DuLABr": $modifier += (IsClassBonusActive($currentPlayer, "ASSASSIN") ? -2 : 0); break;//Shroud in Mist
    case "Uxn14UqyQg": $modifier += (IsClassBonusActive($currentPlayer, "ASSASSIN") ? -2 : 0); break;//Immolation Trap
    case "rPpLwLPGaL": $modifier += (IsClassBonusActive($currentPlayer, "WARRIOR") ? -1*SearchCount(SearchAllies($currentPlayer, subtype:"HUMAN")) : 0); break;//Phalanx Captain
    case "k71PE3clOI": $modifier += GetClassState($currentPlayer, $CS_NumAttacks) > 0 ? -2 : 0; break;//Inspiring Call
    case "wFH1kBLrWh": $modifier -= (IsClassBonusActive($currentPlayer, "MAGE") ? SearchBanish($currentPlayer, element:"ARCANE") : 0); break;//Arcane Elemental
    case "RUqtU0Lczf": $modifier -= (IsClassBonusActive($currentPlayer, "MAGE") ? 1 : 0); break;//Spellshield: Arcane
    case "g7uDOmUf2u": $modifier += (SearchCount(SearchCharacter($currentPlayer, subtype:"SWORD")) > 0 ? -1 : 0); break;//Deflecting Edge
    case "wPKxvzTmqq": $modifier += (DelimStringContains($additionalCosts, "PREPARE") ? -5 : 0); //Ensnaring Fumes
    case "rxxwQT054x": $modifier += (GetClassState($currentPlayer, $CS_LastAttack) == "NA" ? -2 : 0);//Command the Hunt
    case "CgyJxpEgzk": $modifier += (GetClassState($currentPlayer, $CS_AtksWWeapon) > 0 || GetClassState($currentPlayer, $CS_NumAttackCards) > 0 ? -2 : 0);
    case "2ugmnmp5af": $modifier += (IsClassBonusActive($currentPlayer, "RANGER") ? -1 : 0); break;//Take Cover
    case "5tlzsmw3rr": $modifier -= (IsClassBonusActive($currentPlayer, "GUARDIAN") ? SearchCount(SearchAura($currentPlayer, "DOMAIN")) : 0); break;//Summon Sentinels
    case "215upufyoz": $modifier -= (IsClassBonusActive($currentPlayer, "CLERIC") ? 2 : 0); break;//Tether in Flames
    case "99sx6q3p6i": $modifier -= (IsClassBonusActive($currentPlayer, "CLERIC") ? 1 : 0); break;//Spellshield: Wind
    case "ao8bls6g7x": $modifier -= (IsClassBonusActive($currentPlayer, "CLERIC") ? 1 : 0); break;//Healing Aura
    case "huqj5bbae3": $modifier -= (IsClassBonusActive($currentPlayer, "GUARDIAN") && CharacterLevel($currentPlayer) >= 2 ? 2 : 0); break;//Winds of Retribution
    case "kvoqk1l75t": $modifier -= (IsClassBonusActive($currentPlayer, "GUARDIAN") ? 2 : 0); break;//Heavy Swing
    case "xhs5jwsl7d": $modifier -= (IsClassBonusActive($currentPlayer, "CLERIC") ? 1 : 0); break;//Enchaining Gale
    case "fzcyfrzrpl": $modifier -= (IsClassBonusActive($currentPlayer, "GUARDIAN") ? 1 : 0); break;//Heatwave Generator
    case "lq2kkvoqk1": $modifier -= (IsClassBonusActive($currentPlayer, "CLERIC") ? 1 : 0); break;//Necklace of Foresight
    case "ht2tsn0ye3": $modifier -= (IsClassBonusActive($currentPlayer, "CLERIC") ? 1 : 0); break;//Meltdown
    case "ls6g7xgwve": $modifier -= (IsClassBonusActive($currentPlayer, "MAGE") ? 1 : 0); break;//Excoriate
    case "k2c7wklzjm": $modifier -= (SearchCount(SearchItems($currentPlayer, subtype:"SHIELD")) > 0 ? 2 : 0); break;//Frigid Bash
    case "mxqsm4o98v"://Seasprite Diver
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      $oppGY = &GetDiscard($otherPlayer);
      $modifier -= (count($oppGY)/DiscardPieces() >= 4 ? 1 : 0);
      break;
    case "nmp5af098k": $modifier -= (IsClassBonusActive($currentPlayer, "CLERIC") ? 2 : 0); break;//Spellshield: Astra
    case "o7eanl1gxr": $modifier -= (SearchCount(SearchItems($currentPlayer, subtype:"SHIELD")) > 0 ? 1 : 0); break;//Diffusive Block
    case "rqtjot4nmx": $modifier -= (IsClassBonusActive($currentPlayer, "CLERIC") ? 1 : 0); break;//Scavenge the Distillery
    default: break;
  }
  return $modifier;
}

function IsAlternativeCostPaid($cardID, $from)
{
  global $currentTurnEffects, $currentPlayer;
  $isAlternativeCostPaid = false;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch($currentTurnEffects[$i]) {
        case "ARC185": case "CRU188": case "MON199": case "MON257": case "EVR161":
          $isAlternativeCostPaid = true;
          $remove = true;
          break;
        default:
          break;
      }
      if($remove) RemoveCurrentTurnEffect($i);
    }
  }
  return $isAlternativeCostPaid;
}

function BanishCostModifier($from, $index)
{
  global $currentPlayer;
  if($from != "BANISH") return 0;
  $banish = GetBanish($currentPlayer);
  $mod = explode("-", $banish[$index + 1]);
  switch($mod[0]) {
    case "ARC119": return -1 * intval($mod[1]);
    default: return 0;
  }
}

function IsCurrentAttackName($name)
{
  $names = GetCurrentAttackNames();
  for($i=0; $i<count($names); ++$i)
  {
    if($name == $names[$i]) return true;
  }
  return false;
}

function IsCardNamed($player, $cardID, $name)
{
  global $currentTurnEffects;
  if(CardName($cardID) == $name) return true;
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
  {
    $effectArr = explode("-", $currentTurnEffects[$i]);
    $name = CurrentEffectNameModifier($effectArr[0], (count($effectArr) > 1 ? GamestateUnsanitize($effectArr[1]) : "N/A"));
    //You have to do this at the end, or you might have a recursive loop -- e.g. with OUT052
    if($name != "" && $currentTurnEffects[$i+1] == $player) return true;
  }
  return false;
}

function GetCurrentAttackNames()
{
  global $combatChain, $currentTurnEffects, $mainPlayer;
  $names = [];
  if(count($combatChain) == 0) return $names;
  array_push($names, CardName($combatChain[0]));
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
  {
    $effectArr = explode("-", $currentTurnEffects[$i]);
    $name = CurrentEffectNameModifier($effectArr[0], (count($effectArr) > 1 ? GamestateUnsanitize($effectArr[1]) : "N/A"));
    //You have to do this at the end, or you might have a recursive loop -- e.g. with OUT052
    if($name != "" && $currentTurnEffects[$i+1] == $mainPlayer && IsCombatEffectActive($effectArr[0]) && !IsCombatEffectLimited($i)) array_push($names, $name);
  }
  return $names;
}

function SerializeCurrentAttackNames()
{
  $names = GetCurrentAttackNames();
  $serializedNames = "";
  for($i=0; $i<count($names); ++$i)
  {
    if($serializedNames != "") $serializedNames .= ",";
    $serializedNames .= GamestateSanitize($names[$i]);
  }
  return $serializedNames;
}

function HasAttackName($name)
{
  global $chainLinkSummary;
  for($i=0; $i<count($chainLinkSummary); $i+=ChainLinkSummaryPieces())
  {
    $names = explode(",", $chainLinkSummary[$i+4]);
    for($j=0; $j<count($names); ++$j)
    {
      if($name == GamestateUnsanitize($names[$j])) return true;
    }
  }
  return false;
}

function HasPlayedAttackReaction()
{
  global $combatChain, $mainPlayer;
  for($i=CombatChainPieces(); $i<count($combatChain); $i+=CombatChainPieces())
  {
    if($combatChain[$i+1] != $mainPlayer) continue;
    if(CardType($combatChain[$i]) == "AR" || GetResolvedAbilityType($combatChain[$i])) return true;
  }
  return false;
}

function HitEffectsArePrevented()
{
  global $combatChainState, $CCS_ChainLinkHitEffectsPrevented;
  return $combatChainState[$CCS_ChainLinkHitEffectsPrevented];
}

function HitEffectsPreventedThisLink()
{
  global $combatChainState, $CCS_ChainLinkHitEffectsPrevented;
  $combatChainState[$CCS_ChainLinkHitEffectsPrevented] = 1;
}

function EffectPreventsHit()
{
  global $currentTurnEffects, $mainPlayer, $combatChain;
  $preventsHit = false;
  for($i=count($currentTurnEffects)-CurrentTurnPieces(); $i >= 0; $i-=CurrentTurnPieces())
  {
    if($currentTurnEffects[$i+1] != $mainPlayer) continue;
    $remove = 0;
    switch($currentTurnEffects[$i])
    {
      case "OUT108": if(CardType($combatChain[0]) == "AA") { $preventsHit = true; $remove = 1; } break;
      default: break;
    }
    if($remove == 1) RemoveCurrentTurnEffect($i);
  }
  return $preventsHit;
}

function HitsInRow()
{
  global $chainLinkSummary;
  $numHits = 0;
  for($i=count($chainLinkSummary)-ChainLinkSummaryPieces(); $i>=0 && intval($chainLinkSummary[$i+5]) > 0; $i-=ChainLinkSummaryPieces())
  {
    ++$numHits;
  }
  return $numHits;
}

function HitsInCombatChain()
{
  global $chainLinkSummary, $combatChainState, $CCS_HitThisLink;
  $numHits = intval($combatChainState[$CCS_HitThisLink]);
  for($i=count($chainLinkSummary)-ChainLinkSummaryPieces(); $i>=0; $i-=ChainLinkSummaryPieces())
  {
    $numHits += intval($chainLinkSummary[$i+5]);
  }
  return $numHits;
}

function NumAttacksHit()
{
    global $chainLinkSummary;
    $numHits = 0;
    for($i=count($chainLinkSummary)-ChainLinkSummaryPieces(); $i>=0; $i-=ChainLinkSummaryPieces())
    {
      if($chainLinkSummary[$i] > 0) ++$numHits;
    }
    return $numHits;
}

function NumChainLinks()
{
  global $chainLinkSummary, $combatChain;
  $numLinks = count($chainLinkSummary)/ChainLinkSummaryPieces();
  if(count($combatChain) > 0) ++$numLinks;
  return $numLinks;
}

function ClearGameFiles($gameName)
{
  unlink("./Games/" . $gameName . "/gamestateBackup.txt");
  unlink("./Games/" . $gameName . "/beginTurnGamestate.txt");
  unlink("./Games/" . $gameName . "/lastTurnGamestate.txt");
}

function IsClassBonusActive($player, $class)
{
  $char = &GetPlayerCharacter($player);
  if(count($char) == 0) return false;
  if(ClassContains($char[0], $class, $player)) return true;
  return false;
}

function PlayAbility($cardID, $from, $resourcesPaid, $target = "-", $additionalCosts = "-")
{
  global $currentPlayer, $layers, $CS_NumAttacks, $CS_PlayIndex;
  $cardID = ShiyanaCharacter($cardID);
  $set = CardSet($cardID);
  $class = CardClass($cardID);
  if($target != "-")
  {
    $targetArr = explode("-", $target);
    if($targetArr[0] == "LAYERUID") { $targetArr[0] = "LAYER"; $targetArr[1] = SearchLayersForUniqueID($targetArr[1]); }
    $target = $targetArr[0] . "-" . $targetArr[1];
  }
  switch($cardID)
  {
    case "ENLIGHTEN":
      DestroyNumThisAura($currentPlayer, "ENLIGHTEN", 3);
      Draw($currentPlayer);
      break;
    case "7VxRE6HgZC"://Juggle Knives
      DamageTrigger(($currentPlayer == 1 ? 2 : 1), 1, "PLAYCARD", $cardID);
      if(IsClassBonusActive($currentPlayer, "ASSASSIN") || IsClassBonusActive($currentPlayer, "RANGER")) Draw($currentPlayer);
      break;
    case "145y6KBhxe"://Focused Flames
      DealArcane(ArcaneDamage($cardID), 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      break;
    case "776yt8UxhU"://Benevolent Battle Priest
      if($from == "PLAY") Recover($currentPlayer, 1);
      break;
    case "G42RDwb3Ko"://Training Session
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BUFFALLY", 1);
      break;
    case "heq49UQGvQ"://Aesan Protector
      if($from != "PLAY")
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      }
      break;
    case "LZ8JpWj27h"://Clumsy Apprentice
      if($from != "PLAY")
      {
        DamageTrigger($currentPlayer, 2, "PLAYCARD", $cardID);
        Draw($currentPlayer);
      }
      break;
    case "MECS7RHRZ8"://Impassioned Tutor
      if($from == "PLAY") AddCurrentTurnEffect("MECS7RHRZ8", $currentPlayer);
      break;
    case "RIVahUIQVD"://Fireball
      DealArcane(ArcaneDamage($cardID), 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      break;
    case "rXHo9fLU32"://Ignite the Soul
      DealArcane(ArcaneDamage($cardID), 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      break;
    case "rWhFC8XBaH"://Idle Thoughts
      $deck = &GetDeck($currentPlayer);
      $amount = count($deck) < 4 ? count($deck) : 4;
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, GetIndices($amount));
      AddDecisionQueue("MULTIREMOVEDECK", $currentPlayer, "-");
      AddDecisionQueue("CHOOSETOP", $currentPlayer, "<-");
      break;
    case "UfQh069mc3"://Disorienting Winds
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      Draw($currentPlayer);
      break;
    case "WShYN9M3lU"://Owl Familiar
      if($from != "PLAY") PlayerOpt($currentPlayer, 2);
      break;
    case "1BkfdFqCrG"://Revitalizing Cleanse
      if(CanRevealCards($currentPlayer))
      {
        $numWater = 0;
        $cards = "";
        $memory = &GetMemory($currentPlayer);
        for($i=0; $i<count($memory); $i+=MemoryPieces())
        {
          if(CardElement($memory[$i]) == "WATER") ++$numWater;
          if($cards != "") $cards .= ",";
          $cards .= $memory[$i];
        }
        RevealCards($cards, $currentPlayer, "MEM");
        Recover($currentPlayer, $numWater);
        WriteLog("Recovered " . $numWater);
      }
      Draw($currentPlayer);
      break;
    case "2Ojrn7buPe"://Tera Sight
      Draw($currentPlayer);
      break;
    case "a8I89SP24E"://Sink into Oblivion
      Mill($currentPlayer, 3);//TODO: Should be able to target
      break;
    case "BqDw4Mei4C"://Creative Shock
      Draw($currentPlayer);
      Draw($currentPlayer);
      PummelHit($currentPlayer);
      if(IsClassBonusActive($currentPlayer, "MAGE")) AddDecisionQueue("SPECIFICCARD", $currentPlayer, "CREATIVESHOCK", 1);
      break;
    case "F9POfB5Nah"://Scry the Skies
      PlayerOpt($currentPlayer, CharacterLevel($currentPlayer));
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "SCRYTHESKIES");
      break;
    case "6e7lRnczfL"://Horn of Beastcalling
      AddCurrentTurnEffect("6e7lRnczfL", $currentPlayer);
      Draw($currentPlayer);
      break;
    case "BY0E8si926"://Orb of Regret
      $indices = GetMyHandIndices();
      if($indices == "") return "";
      AddDecisionQueue("FINDINDICES", $currentPlayer, "MULTIHAND");
      AddDecisionQueue("MULTICHOOSEHAND", $currentPlayer, "<-", 1);
      AddDecisionQueue("MULTIREMOVEHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("MULTIADDDECK", $currentPlayer, "-", 1);
      AddDecisionQueue("SHUFFLEDECK", $currentPlayer, "-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ORBOFREGRET", 1);
      break;
    case "UiohpiTtgs"://Chalice of Blood
      Draw($currentPlayer);
      Draw($currentPlayer);
      break;
    case "dmfoA7jOjy"://Crystal of Empowerment
      AddCurrentTurnEffect("dmfoA7jOjy", $currentPlayer);
      break;
    case "hLHpI5rHIK"://Bauble of Mending
      Draw($currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH", 1);
      break;
    case "AKA19OwaCh"://Jewel of Englightenment
      PlayAura("ENLIGHTEN", $currentPlayer);
      break;
    case "EBWWwvSxr3"://Channeling Stone
      AddCurrentTurnEffect("EBWWwvSxr3", $currentPlayer);
      break;
    case "j5iQQPd2m5"://Crystal of Argus
      AddCurrentTurnEffect("j5iQQPd2m5-" . intval(CountAura("ENLIGHTEN", $currentPlayer)/3), $currentPlayer);
      break;
    case "llQe0cg4xJ"://Orb of Choking Fumes
      AddCurrentTurnEffect("llQe0cg4xJ", ($currentPlayer == 1 ? 2 : 1));
      if(IsClassBonusActive($currentPlayer, "ASSASSIN")) Draw($currentPlayer);
      break;
    case "OofVX5hX8X"://Poisoned Coating Oil
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "OofVX5hX8X,HAND", 1);
      break;
    case "F1t18omUlx"://Beastbond Paws
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:subtype=ANIMAL&MYALLY:subtype=BEAST");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "F1t18omUlx,HAND", 1);
      break;
    case "iiZtKTulPg"://Eye of Argus
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "iiZtKTulPg,HAND", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-");
      break;
    case "Tx6iJQNSA6"://Majestic Spirit's Crest
      AddCurrentTurnEffect("Tx6iJQNSA6", $currentPlayer);
      break;
    case "qYH9PJP7uM"://Blinding Orb
      $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
      HandIntoMemory($otherPlayer);
      HandIntoMemory($otherPlayer);
      if(IsClassBonusActive($currentPlayer, "ASSASSIN")) Draw($currentPlayer);
      break;
    case "ScGcOmkoQt"://Smoke Bombs
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "ScGcOmkoQt,HAND", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-");
      break;
    case "2bzajcZZRD"://Map of Hidden Passage
      AddCurrentTurnEffect("2bzajcZZRD", $currentPlayer);
      break;
    case "xjuCkODVRx"://Beastbond Boots
      WriteLog("Manually enforce spellshround");
      AddCurrentTurnEffect("xjuCkODVRx", $currentPlayer);
      break;
    case "yj2rJBREH8"://Safeguard Amulet
      AddCurrentTurnEffect("yj2rJBREH8", $currentPlayer);
      break;
    case "EQZZsiUDyl"://Storm Tyrant's Eye
      AddDecisionQueue("FINDINDICES", $currentPlayer, "STORMTYRANTSEYE");
      AddDecisionQueue("CHOOSEDECK", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDHAND", $currentPlayer, "DECK", 1);
      AddDecisionQueue("SHUFFLEDECK", $currentPlayer, "-", 1);
      break;
    case "LROrzTmh55"://Fire Resonance Bauble
    case "2gv7DC0KID"://Grand Crusader's Ring
    case "bHGUNMFLg9"://Wind Resonance Bauble
    case "dSSRtNnPtw"://Water Resonance Bauble
    case "yDARN8eV6B"://Tome of Knowledge
    case "P7hHZBVScB"://Orb of Glitter
    case "IC3OU6vCnF"://Mana Limiter
    case "kk46Whz7CJ"://Surveillance Stone
    case "1XegCUjBnY"://Life Essence Amulet
    case "73fdt8ptrz"://Windwalker Boots
    case "jxhkurfp66"://Charged Manaplate
    case "ojwk0pw0y6"://Crest of the Alliance
    case "porhlq2kkv"://Wayfinder's Map
      Draw($currentPlayer);
      break;
    case "YOjdZJpOO1"://Blissful Calling
      AddDecisionQueue("FINDINDICES", $currentPlayer, "DECKTOPXREMOVE," . 5);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("FILTER", $currentPlayer, "LastResult-include-subtype-BEAST&ANIMAL", 1);
      AddDecisionQueue("CHOOSECARD", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("OP", $currentPlayer, "REMOVECARD");
      AddDecisionQueue("CHOOSEBOTTOM", $currentPlayer, "<-");
      break;
    case "SrBA7h2a1N"://Freezing Hail
      DealArcane(ArcaneDamage($cardID), 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      MZFreeze($target);
      break;
    case "Z9TCpaMJTc"://Bauble of Abundance
      Draw($currentPlayer);
      Draw(($currentPlayer == 1 ? 2 : 1));
      break;
    case "XLrHaYV9VB"://Arcane Sight
      AddCurrentTurnEffect("XLrHaYV9VB", $currentPlayer);
      Draw($currentPlayer);
      break;
    case "zrBBvgIvt6"://Tide Diviner
      if($from != "PLAY")
      {
        AddDecisionQueue("FINDINDICES", $currentPlayer, "DECKTOPXREMOVE," . CharacterLevel($currentPlayer)+1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("CHOOSECARD", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("OP", $currentPlayer, "REMOVECARD");
        AddDecisionQueue("MULTIADDDISCARD", $currentPlayer, "<-");
      }
      break;
    case "9GWxrTMfBz"://Cram Session
      AddCurrentTurnEffect("9GWxrTMfBz", $currentPlayer);
      break;
    case "dZ960Hnkzv"://Vertus, Gaia's Roar
      AddCurrentTurnEffect("dZ960Hnkzv", $currentPlayer);
      break;
    case "dsAqxMezGb"://Favorable Winds
      GiveAlliesHealthBonus($currentPlayer, 1);
      break;
    case "dY36bObi9p"://Reckless Researcher
      if($from != "PLAY")
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:element=FIRE");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZBANISH", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      }
      break;
    case "FCbKYZcbNq"://Trusty Steed
      if($from != "PLAY")
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID");
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "FCbKYZcbNq,HAND");
      }
      break;
    case "gvXQa57cxe"://Shout at Your Pets
      if(SearchCount(SearchAllies($currentPlayer, "", "BEAST")) + SearchCount(SearchAllies($currentPlayer, "", "ANIMAL")) > 0) AddCurrentTurnEffect("gvXQa57cxe", $currentPlayer);
      if(IsClassBonusActive($currentPlayer, "TAMER"))
      {
        PummelHit($currentPlayer, true);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    case "hDUP6BY5Cx"://Cemetery Sentry
      if($from != "PLAY")
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:element=FIRE");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDISCARD", $currentPlayer, "HAND", 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    case "PLljzdiMmq"://Invoke Dominance
      AddCurrentTurnEffect("PLljzdiMmq", $currentPlayer);
      break;
    case "blq7qXGvWH"://Arcane Disposition
      Draw($currentPlayer);
      Draw($currentPlayer);
      if(IsClassBonusActive($currentPlayer, "MAGE")) Draw($currentPlayer);
      AddCurrentTurnEffect("blq7qXGvWH", $currentPlayer);
      break;
    case "e8nFGSSvgc"://Restorative Slash
      Recover($currentPlayer, 3);
      break;
    case "F2wp1v0Tyk"://Reclaim
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "zpkcFs72Ah"://Smack with Flute
      AddCurrentTurnEffect("zpkcFs72Ah", $currentPlayer);
      break;
    case "ZgA7cWNKGy"://Summon Gale
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "WsunZX4IlW"://Ravaging Tempest
      $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
      $theirAllies = &GetAllies($otherPlayer);
      for($i=count($theirAllies)-AllyPieces(); $i>=0; $i-=AllyPieces())
      {
        $cardID = RemoveAlly($otherPlayer, $i);
        BanishCardForPlayer($cardID, $otherPlayer, "PLAY", "-", $currentPlayer);
        Draw($otherPlayer);
      }
      $myAllies = &GetAllies($currentPlayer);
      for($i=count($myAllies)-AllyPieces(); $i>=0; $i-=AllyPieces())
      {
        $cardID = RemoveAlly($currentPlayer, $i);
        BanishCardForPlayer($cardID, $currentPlayer, "PLAY", "-", $currentPlayer);
        Draw($currentPlayer);
      }
      break;
    case "sHzSmygjWY"://Gaia's Songbird
      $deck = &GetDeck($currentPlayer);
      $toReveal = "";
      for($i=0; $i<count($deck); ++$i)
      {
        $card = array_shift($deck);
        if($toReveal != "") $toReveal .= ",";
        $toReveal .= $card;
        if(SubtypeContains($card, "BEAST", $currentPlayer)) { AddHand($currentPlayer, $card); break; }
        else array_push($deck, $card);
      }
      RevealCards($toReveal, $currentPlayer);
      break;
    case "SPESFtKHLw"://Rallied Advance
      if(IsClassBonusActive($currentPlayer, "GUARDIAN") || IsClassBonusActive($currentPlayer, "WARRIOR"))
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "WAKEUP", 1);
      }
      break;
    case "soO3hjaVfN"://Rending Flames
      if(SearchCount(SearchDiscard($currentPlayer, element:"FIRE")) >= 3 && (IsClassBonusActive($currentPlayer, "ASSASSIN") || IsClassBonusActive($currentPlayer, "WARRIOR")))
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:element=FIRE");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZBANISH", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        for($i=0; $i<2; ++$i)
        {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:element=FIRE", 1);
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZBANISH", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        }
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "soO3hjaVfN", 1);
      }
      break;
    case "uTBsOYf15p"://Purge in Flames
      $amount = IsClassBonusActive($currentPlayer, "MAGE") ? 3 : 2;
      DealArcane($amount, source:"uTBsOYf15p", resolvedTarget:"THEIRCHAR-0");
      DamageAllAllies($amount, "uTBsOYf15p");
      break;
    case "NwswAHojeq"://Young Beastbonder
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BUFFALLY", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      AddDecisionQueue("ALLCARDSUBTYPEORPASS", $currentPlayer, "BEAST", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BUFFALLY", 1);
      break;
    case "n8wyfG9hbY"://Fairy Whispers
      PlayerOpt($currentPlayer, 3);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "FAIRYWHISPERS");
      break;
    case "MwXulmKsIg"://Song of Return
      for($i=0; $i<2; ++$i) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      }
      break;
    case "4hbA9FT56L"://Song of Nurturing
      GiveAlliesHealthBonus($currentPlayer, 2);
      if(IsClassBonusActive($currentPlayer, "TAMER")) AddCurrentTurnEffect("4hbA9FT56L", $currentPlayer);
      break;
    case "7tUvIHeo0i"://Increasing Danger
      Draw($currentPlayer);
      DrawIntoMemory(1);
      DrawIntoMemory(2);
      break;
    case "7UXGwC7lSO"://Tactful Seargeant
      if($from != "PLAY" && GetClassState($currentPlayer, $CS_NumAttacks) > 0)
      {
        DrawIntoMemory($currentPlayer);
      }
      break;
    case "8nbmykyXcw"://Conceal
      AddCurrentTurnEffect("8nbmykyXcw", $currentPlayer);
      break;
    case "914hZjxDL0"://Peer into Mana
      PlayAura("ENLIGHTEN", $currentPlayer, 2+CharacterLevel($currentPlayer));
      break;
    case "At1UNRG7F0"://Devastating Blow
      if(CharacterLevel($currentPlayer) >= 3 && (IsClassBonusActive($currentPlayer, "GUARDIAN") || IsClassBonusActive($currentPlayer, "WARRIOR"))) AddCurrentTurnEffect("At1UNRG7F0", $currentPlayer);
      break;
    case "cQlxapCsxQ"://Spontaneous Combustion
      if(IsAllyAttacking()) DealArcane(4, source:"cQlxapCsxQ", resolvedTarget:"THEIRALLY-" . AttackIndex());
      break;
    case "DBJ4DuLABr"://Shroud in Mist
      AddCurrentTurnEffect("DBJ4DuLABr", $currentPlayer);
      break;
    case "Kc5Bktw0yK"://Empowering Harmony
      AddCurrentTurnEffect("Kc5Bktw0yK", $currentPlayer);
      if(IsHarmonizeActive($currentPlayer)) Draw($currentPlayer);
      break;
    case "L9yBqoOshh"://Spark Alight
      DealArcane(ArcaneDamage($cardID), 2, "PLAYCARD", $cardID, resolvedTarget:$target);
      break;
    case "W1vZwOXfG3"://Embertail Squirrel
      if($from == "PLAY")
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:element=FIRE");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDISCARD", $currentPlayer, "HAND", 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "W1vZwOXfG3", 1);
      }
      break;
    case "WAFNy2lY5t"://Melodious Flute
      global $CS_NumMelodyPlayed;
      IncrementClassState($currentPlayer, $CS_NumMelodyPlayed);//TODO: not really right but fine for now
      break;
    case "uZCyXDNJ6I"://Accepted Contract
      AddPreparationCounters($currentPlayer, 3);
      break;
    case "wOKw0q4SZR"://Anger the Skies
      $amount = IsClassBonusActive($currentPlayer, "MAGE") ? 4 : 3;
      DamageAllAllies($amount, "wOKw0q4SZR");
      break;
    case "Uxn14UqyQg"://Immolation Trap
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      break;
    case "raG5r85ieO"://Piper's Lullaby
      if(SearchCount(SearchAllies($currentPlayer, "", "BEAST")) + SearchCount(SearchAllies($currentPlayer, "", "ANIMAL")) > 0) AddCurrentTurnEffect("raG5r85ieO", $currentPlayer);
      if(IsClassBonusActive($currentPlayer, "TAMER"))
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
    case "LRsgl92Iqa"://Mark the Target
      DealArcane(ArcaneDamage($cardID), 2, "PLAYCARD", $cardID, resolvedTarget:$target);
      if(IsClassBonusActive($currentPlayer, "ASSASSIN") || IsClassBonusActive($currentPlayer, "RANGER")) AddPreparationCounters($currentPlayer, 1);
      break;
    case "k71PE3clOI"://Inspiring Call
      AddCurrentTurnEffect("k71PE3clOI", $currentPlayer);
      DrawIntoMemory($currentPlayer);
      break;
    case "IyXuaLKjSA"://Frozen Nova
      DamageAllAllies(1, "IyXuaLKjSA", true, true);
      break;
    case "ify06tSEVC"://Attune with the Winds
      $allies = &GetAllies($currentPlayer);
      for($i=0; $i<count($allies); $i+=AllyPieces())
      {
        BuffAlly($currentPlayer, $i);
      }
      if(IsHarmonizeActive($currentPlayer) && IsClassBonusActive($currentPlayer, "TAMER")) Draw($currentPlayer);
      break;
    case "Huh1DljE0j"://Second Wind
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "WAKEUP", 1);
      if(IsClassBonusActive($currentPlayer, "WARRIOR"))
      {
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID");
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "Huh1DljE0j,HAND");//TODO Bug; can't buff opponent's thing
      }
      break;
    case "EtIGAJ8sxw"://Strategic Planning
      PlayerOpt($currentPlayer, 2);
      AddPreparationCounters($currentPlayer, 1);
      break;
    case "4NkVdSx9ed"://Careful Study
      PlayAura("ENLIGHTEN", $currentPlayer, 5);
      break;
    case "1o0tKizBZ6"://Windstream Mutt
      if($from != "PLAY" && IsClassBonusActive($currentPlayer, "TAMER"))
      {
        if(CardElement(MemoryRevealRandom($currentPlayer)) == "WIND")
        {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "BUFFALLY", 1);
        }
      }
      break;
    case "4K2pT3RmTJ"://Chilling Touch
      BanishRandomMemory($currentPlayer == 1 ? 2 : 1, "INT");
      break;
    case "6IOxuftyVv"://Glacial Guidance
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ENDCOMBAT", 1);
      if(IsClassBonusActive($currentPlayer, "MAGE")) PlayAura("ENLIGHTEN", $currentPlayer);
    case "6YiMaCGsfV"://Channel the Wind
      PlayAura("ENLIGHTEN", $currentPlayer);
      break;
    case "dxAEI20h8F"://Sudden Snow
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:floatingMemoryOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a floating memory card to banish", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      AddDecisionQueue("MULTIBANISH", $currentPlayer, "GY,-", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      AddCurrentTurnEffect("dxAEI20h8F", $currentPlayer);
      AddCurrentTurnEffect("dxAEI20h8F", $currentPlayer == 1 ? 2 : 1);
      break;
    case "gJ2dsgywEs"://Reckless Conversion
      DrawIntoMemory($currentPlayer);
      DrawIntoMemory($currentPlayer);
      for($i=0; $i<4; ++$i) BanishRandomMemory($currentPlayer);
      if(IsClassBonusActive($currentPlayer, "MAGE")) ReturnAllMemoryToHand($currentPlayer);
      break;
    case "hHVf5xyjob"://Blackmarket Broker
      AddPreparationCounters($currentPlayer, 1);
      break;
    case "L9o11y7yfa"://Mind Freeze
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      for($i=0; $i<CharacterLevel($currentPlayer); ++$i) BanishRandomMemory($otherPlayer, "INT");
      break;
    case "pn9gQjV3Rb"://Arcane Blast
      DealArcane(ArcaneDamage($cardID), 2, "PLAYCARD", $cardID, resolvedTarget:$target);
      break;
    case "xipHhhsgJy"://Set the Traps
      Mill($currentPlayer, 2);//TODO: Should be target player
      if(IsClassBonusActive($currentPlayer, "ASSASSIN")) AddPreparationCounters($currentPlayer, 1);
      break;
    case "XZFXOE9sEV"://Zephyr Assistant
      if($from != "PLAY") PlayAura("ENLIGHTEN", $currentPlayer);
      break;
    case "ybdj1Db9jz"://Seed of Nature
      AddCurrentTurnEffect("ybdj1Db9jz", $currentPlayer);
      break;
    case "FhbVHkHQRb"://Disintegrate
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY&THEIRITEMS&THEIRCHAR:type=W");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to destroy", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDESTROY", $currentPlayer, "<-", 1);
      break;
    case "Pr48kXnasw"://Cremation Ritual
      $allies = &GetAllies($currentPlayer);
      if(count($allies) == 0) { WriteLog("Cannot pay cost"); return ""; }
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to sacrifice", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDESTROY", $currentPlayer, "<-", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      break;
    case "RUqtU0Lczf"://Spellshield: Arcane
      AddCurrentTurnEffect("RUqtU0Lczf", $currentPlayer);
      break;
    case "XeXek4dKav"://Give Bath
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an ally to heal", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "HEALALLY", 1);
      break;
    case "XMb6pSHFJg"://Embersong
      DealArcane(ArcaneDamage($cardID), 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      if(IsClassBonusActive($currentPlayer, "TAMER"))
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID");
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "XMb6pSHFJg,HAND");
      }
      break;
    case "xWJND68I8X"://Water Barrier
      AddCurrentTurnEffect("xWJND68I8X", $currentPlayer);
      break;
    case "P9Y1Q5cQ0F"://Crux Sight
      if($resourcesPaid == "2") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:element=CRUX");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZADDZONE", $currentPlayer, "MYHAND", 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      }
      AddDecisionQueue("DRAW", $currentPlayer, "-");
      break;
    case "b43adsk77Y"://Refurbish
      AddDurabilityCounters($currentPlayer, 2);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "REFURBISH", 1);
      break;
    case "vyRjDql0TR"://Tempered Steel
      AddDurabilityCounters($currentPlayer, 1);
      break;
    case "DqtlaMGMvd"://Erratic Bolt
      DealArcane(CharacterLevel($currentPlayer), 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      if(IsClassBonusActive($currentPlayer, "MAGE"))
      {
        AddDecisionQueue("YESNO", $currentPlayer, "if you want to banish two cards from your memory");
        AddDecisionQueue("NOPASS", $currentPlayer, "-");
        AddDecisionQueue("BANISHRANDOMMEMORY", $currentPlayer, "-", 1);
        AddDecisionQueue("BANISHRANDOMMEMORY", $currentPlayer, "-", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    case "em6eEh9q8y"://Dungeon Guide
      $memory = &GetMemory($currentPlayer);
      if(count($memory)/MemoryPieces() < 2) break;
      AddDecisionQueue("YESNO", $currentPlayer, "if_you_want_to_banish_two_cards_from_your_memory");
      AddDecisionQueue("BANISHRANDOMMEMORY", $currentPlayer, "-", 1);
      AddDecisionQueue("BANISHRANDOMMEMORY", $currentPlayer, "-", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYMATERIAL:type=CHAMPION", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose your next level", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDQFinishMaterialize($currentPlayer, true);
      break;
    case "ErH0lIBq4z"://Spurn to Ash
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYITEMS:type=REGALIA;maxCost=1&THEIRITEMS:type=REGALIA;maxCost=1&MYCHAR:type=REGALIA;maxCost=1&THEIRCHAR:type=REGALIA;maxCost=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a regalia to destroy", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      break;
    case "XQKyUqsMUg"://Seer's Sword
      if($from == "EQUIP") PlayerOpt($currentPlayer, 2);
      break;
    case "rpOaAjgtue"://Frostsworn Paladin
      AddFloatingMemoryChoice();
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      $allies = &GetAllies($currentPlayer);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, count($allies)-AllyPieces(), 1);
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "MYALLY-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BUFFALLY", 1);
      break;
    case "iohZMWh5v5"://Blazing Throw
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYCHAR:type=WEAPON");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a weapon to sacrifice", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      DealArcane(ArcaneDamage("iohZMWh5v5"), 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      break;
    case "g7uDOmUf2u"://Deflecting Edge
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYCHAR:type=CHAMPION&MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to prevent 3 damage for", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "DEFLECTINGEDGE", 1);
      break;
    case "fMv7tIOZwL"://Aqueous Enchanting
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a mode", 1);
      AddDecisionQueue("BUTTONINPUT", $currentPlayer, "+1_Health,+1_Attack", 1);
      AddDecisionQueue("SHOWMODES", $currentPlayer, $cardID, 1);
      AddDecisionQueue("MODAL", $currentPlayer, "AQUEOUSENCHANTING", 1);
      break;
    case "dBAdWMoPEz"://Erupting Rhapsody
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ERUPTINGRHAPSODY");
      if(IsHarmonizeActive($currentPlayer)) AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ERUPTINGRHAPSODYHARMONIZE");
      break;
    case "5X5W2Uda5a"://Planted Explosives
      $damage = (DelimStringContains($additionalCosts, "PREPARE") ? 4 : 2);
      DealArcane($damage, 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      break;
    case "AnEPyfFfHj"://Power Overwhelming
      $numEnlighten = SearchCount(SearchAurasForCard("ENLIGHTEN", $currentPlayer));
      if($numEnlighten > 0)
      {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose how many englighten counters to remove");
        AddDecisionQueue("BUTTONINPUT", $currentPlayer, GetIndices($numEnlighten+1));
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "POWEROVERWHELMING");
      }
      break;
    case "GRkBQ1Uvir"://Ignited Stab
      if(DelimStringContains($additionalCosts, "PREPARE")) AddCurrentTurnEffect("GRkBQ1Uvir", $currentPlayer);
      break;
    case "mj3WSrghUH"://Poised Strike
      if(DelimStringContains($additionalCosts, "PREPARE")) AddCurrentTurnEffect("mj3WSrghUH", $currentPlayer);
      break;
    case "QQaOgurnjX"://Imbue in Frost
      AddCurrentTurnEffect($cardID, $currentPlayer);
      AddDecisionQueue("FINDINDICES", $currentPlayer, "WEAPON");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose_target_weapon");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDMZBUFF", $currentPlayer, $cardID, 1);
      break;
    case "usb5FgKvZX"://Sharpening Stone
      AddCurrentTurnEffect($cardID, $currentPlayer);
      AddDecisionQueue("FINDINDICES", $currentPlayer, "WEAPON");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose_target_weapon");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDMZBUFF", $currentPlayer, $cardID, 1);
      break;
    case "wPKxvzTmqq"://Ensnaring Fumes
      $allies = &GetAllies($currentPlayer);
      for($i=count($allies)-AllyPieces(); $i>=0; $i-=AllyPieces()) MZBounce($currentPlayer, "MYALLY-" . $i);
      $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
      $theirAllies = &GetAllies($otherPlayer);
      for($i=count($theirAllies)-AllyPieces(); $i>=0; $i-=AllyPieces()) MZBounce($otherPlayer, "MYALLY-" . $i);
      break;
    case "XLbCBxla8K"://Thousand Refractions
      if(DelimStringContains($additionalCosts, "PREPARE")) AddCurrentTurnEffect("XLbCBxla8K", $currentPlayer);
      break;
    case "KoF3AMSlUe"://Veiling Breeze
      $memory = &GetMemory($currentPlayer);
      $toReveal = "";
      $numWind = 0;
      for($i=0; $i<count($memory); $i+=MemoryPieces())
      {
        if(CardElement($memory[$i]) == "WIND")
        {
          if($toReveal != "") $toReveal .= ",";
          $toReveal .= $memory[$i];
          ++$numWind;
        }
      }
      if(RevealCards($toReveal))
      {
        AddCurrentTurnEffect("KoF3AMSlUe-" . $numWind, $currentPlayer);
        WriteLog("Veiling Breeze prevents " . $numWind . " damage.");
      }
      break;
    case "nIKhHFa0rK"://Cry for Help
      if(IsHeroAttackTarget())
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "CHANGEATTACKTARGET", 1);
        if(IsClassBonusActive($currentPlayer, "TAMER")) AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH", 1);
      }
      break;
    case "okDVkV1l76"://Hymn of Gaia's Grace
      PlayerOpt($currentPlayer, 3);
      AddDecisionQueue("DRAW", $currentPlayer, "-");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:subtype=BEAST&MYHAND:subtype=ANIMAL");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      AddDecisionQueue("PUTPLAY", $currentPlayer, "-", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CHANGEATTACKTARGET", 1);
      break;
    case "qtRBz9azeZ"://Excalibur, Cleansing Light
      if(IsClassBonusActive($currentPlayer, "WARRIOR")) WriteLog("Manually enforce element restriction");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRITEM&THEIRCHARACTER:type=WEAPON&THEIRALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
    case "uoQGe5xGDQ"://Arrow Trap
      if(IsAllyAttacking())
      {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, AttackerMZID($currentPlayer));
        if(DelimStringContains($additionalCosts, "PREPARE") && (IsClassBonusActive($currentPlayer, "ASSASSIN") || IsClassBonusActive($currentPlayer, "RANGER"))) AddDecisionQueue("MZDESTROY", $currentPlayer, "-");
        else AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE");
      }
      break;
    case "uwnHTLG3fL"://Luxem Sight
      Draw($currentPlayer);
      break;
    case "UVAb8CmjtL"://Dream Fairy
      WriteLog("Enforce play restriction manually");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "zxB4tzy9iy"://Lightweaver's Assault
      if(RevealMemory($currentPlayer))
      {
        $numReveal = SearchCount(SearchMemory($currentPlayer));
        for($i=0; $i<$numReveal; ++$i) DealArcane(1, 2, "TRIGGER", $cardID, player:$currentPlayer);
      }
      break;
    case "qufoIF014c"://Gleaming Cut
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYMEMORY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      AddDecisionQueue("ALLCARDELEMENTORPASS", $currentPlayer, "LUXEM", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "qufoIF014c", 1);
      break;
    case "s23UHXgcZq"://Luxera's Map
      if($from == "PLAY")
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDECK");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZADDZONE", $currentPlayer, "MYMEMORY,HAND,DOWN", 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      }
      break;
    case "4zkTRt8qXn"://Uncover the Plot
      RevealMemory($currentPlayer);
      Draw($currentPlayer);
      if(IsClassBonusActive($currentPlayer, "ASSASSIN")) AddPreparationCounters($currentPlayer, 2);
      break;
    case "5qWWpkgQLl"://Coup de Grace
      if(DelimStringContains($additionalCosts, "PREPARE")) AddCurrentTurnEffect("5qWWpkgQLl", $currentPlayer);
      break;
    case "2Ch1Gp3jEL"://Corhazi Lightblade
      if($from == "PLAY" && IsClassBonusActive($currentPlayer, "ASSASSIN"))
      {
        if(CardElement(MemoryRevealRandom($currentPlayer)) == "LUXEM") AddCurrentTurnEffect("2Ch1Gp3jEL", $currentPlayer, "PLAY");
      }
      break;
    case "SkAe1hsw5H"://Ghosts of Pendragon
      if($from != "PLAY")
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYITEMS:type=REGALIA&MYCHAR:type=REGALIA");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a regalia to return", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZADDZONE", $currentPlayer, "MYMATERIAL", 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    case "RRx0KK6g6D"://Fishing Accident
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY&MYALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      if(DelimStringContains($additionalCosts, "PREPARE")) AddDecisionQueue("MZOP", $currentPlayer, "SINK", 1);
      else AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "7Rsid05Cf6"://Spirit Blade: Dispersion
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "SPIRITBLADEDISPERSION");
      break;
    case "4s0c9XgLg7"://Snow Fairy
      WriteLog("Snow Fairy is a partially manual card, enforce the persistent rest mechanic manually");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "rxxwQT054x"://Command the Hunt
      AddCurrentTurnEffect($cardID, $currentPlayer);
      break;
    case "WdkZU2wwnw"://Extortion Scheme
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "SUPPRESS", 1);
      if(IsClassBonusActive($currentPlayer, "ASSASSIN")) AddPreparationCounters($currentPlayer, 1);
      break;
    case "jOqyx96kse"://Scattering Gusts
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "SUPPRESS", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "SUPPRESS", 1);
      if(IsClassBonusActive($currentPlayer, "MAGE")) PlayAura("ENLIGHTEN", $currentPlayer);
      break;
    case "idaRe7y3In"://Zephyr
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY&THEIRITEMS:type=REGALIA&THEIRCHAR:type=REGALIA");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "SUPPRESS", 1);
      break;
    case "FWnxKjSeB1"://Spark Fairy
      if($from != "PLAY")
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY&THEIRITEMS:type=REGALIA&THEIRCHAR:type=REGALIA");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("WRITELOG", $currentPlayer, "Each turn you will be asked if <0> is still alive", 1);
      }
      break;
    case "qaA3sXFRFY"://Spirit's Blessing
      $char = &GetPlayerCharacter($currentPlayer);
      $char[1] = 2;
      Draw($currentPlayer);
      break;
    case "CgyJxpEgzk"://Spirit Blade: Infusion
      AddCurrentTurnEffect($cardID, $currentPlayer);
      AddDecisionQueue("FINDINDICES", $currentPlayer, "WEAPON");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose_target_weapon");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDMZBUFF", $currentPlayer, $cardID, 1);
      break;
    case "vcZSHNHvKX"://Spirit Blade: Ghost Strike
      MZMoveCard($currentPlayer, "MYMATERIAL", "MYBANISH,MATERIAL,-");
      AddCurrentTurnEffect($cardID, $currentPlayer);
      break;
    case "0ye3aebjvw"://Study the Fables
      $target = ($currentPlayer == 1 ? 2 : 1);
      AddDecisionQueue("FINDINDICES", $target, "MATERIAL");
      AddDecisionQueue("PREPENDLASTRESULT", $target, 6 . "-", 1);
      AddDecisionQueue("APPENDLASTRESULT", $target, "-" . 6, 1);
      AddDecisionQueue("SETDQCONTEXT", $target, "Choose " . 6 . " card" . (6 > 1 ? "s" : ""), 1);
      AddDecisionQueue("MULTICHOOSEMATERIAL", $target, "<-", 1);
      AddDecisionQueue("MATERIALCARDS", $target, "<-", 1);
      AddDecisionQueue("REVEALCARDS", $target, "-", 1);
      DrawIntoMemory($currentPlayer);
      break;
    case "0ymvddv1au"://Illuminate Secrets
      $target = ($currentPlayer == 1 ? 2 : 1);
      $damage = PlayerInfluence($target) - PlayerInfluence($currentPlayer);
      DealArcane($damage, 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      break;
    case "3oda2ha4dk"://Fast Cure
      $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
      if(PlayerInfluence($otherPlayer) > PlayerInfluence($currentPlayer)) Recover($currentPlayer, 4);
      break;
    case "4n1n3gygoj"://Neos Sight
      Draw($currentPlayer);
      $numObjects = 1;//Character?
      $allies = &GetAllies($currentPlayer);
      $items = &GetItems($currentPlayer);
      $auras = &GetAuras($currentPlayer);
      $numObjects += count($allies)/AllyPieces();
      $numObjects += count($items)/ItemPieces();
      $numObjects += count($auras)/AuraPieces();
      if($numObjects >= 8) DrawIntoMemory($currentPlayer);
      break;
    case "7t9m4muq2r"://Thieving Cut
      if(DelimStringContains($additionalCosts, "PREPARE")) AddCurrentTurnEffect("7t9m4muq2r", $currentPlayer);
      break;
    case "8jypwc8tuh"://Navigate the Streets
      PlayerOpt($currentPlayer, 1+SearchCount(SearchAura($currentPlayer, "DOMAIN")));
      break;
    case "ddv1au7t9m"://Gentle Respite
      $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
      if(PlayerInfluence($otherPlayer) > PlayerInfluence($currentPlayer)) DrawIntoMemory($currentPlayer);
      break;
    case "1tzgcxyky2"://Riptide Slash
      if(IsClassBonusActive($currentPlayer, "WARRIOR")) PlayerOpt($currentPlayer, 2);
      break;
    case "1d47o7eanl"://Explosive Fractal
      $memory = &GetMemory($currentPlayer);
      if(IsClassBonusActive($currentPlayer, "CLERIC") && (count($memory)/MemoryPieces() >= 4)) DealArcane(2, 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      break;
    case "i0a5uhjxhk"://Blightroot (1)
      if($from == "PLAY") AddCurrentTurnEffect("i0a5uhjxhk", $currentPlayer);
      break;
    case "5joh300z2s"://Mana Root (2)
       if($from == "PLAY") AddCurrentTurnEffect("5joh300z2s", $currentPlayer);
       break;
    case "bd7ozuj68m"://Silvershine (3)
      if($from == "PLAY") Recover($currentPlayer, 1);
      break;
    case "soporhlq2k"://Fraysia (4)
      if($from == "PLAY") Recover($currentPlayer, 1);
      break;
    case "jnltv5klry"://Razorvine (5)
      if($from == "PLAY") BottomDeck($currentPlayer, false, shouldDraw:true);
      break;
    case "69iq4d5vet"://Springleaf (6)
      if($from == "PLAY") BottomDeck($currentPlayer, false, shouldDraw:true);
      break;
    case "0pw0y6isxy"://Foraging Servant
      Gather($currentPlayer, 1);
      break;
    case "1lw9n0wpbh"://Protective Fractal
      if($from == "PLAY") AddCurrentTurnEffect("1lw9n0wpbh", $currentPlayer);
      break;
    case "2ha4dk88zq"://Cloak of Stillwater
      if($from == "PLAY") {
        AddFloatingMemoryChoice();
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "2ha4dk88zq", 1);
      }
      else if(IsClassBonusActive($currentPlayer, "ASSASSIN")) Draw($currentPlayer);
      break;
    case "6ffqsuo6gb"://Refracting Missile
      $fractalCount = SearchCount(SearchAura($currentPlayer, subtype:"FRACTAL"));
      DealArcane(1 + $fractalCount, 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      break;
    case "1bqry41lw9"://Explosive Rune
      $damage = 1;
      if(IsClassBonusActive($currentPlayer, "MAGE") && AttackerMZID($currentPlayer) == $target) $damage += 1;
      DealArcane($damage, 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      break;
    case "6i0iqmyn2r"://Raze the Land
      MZChooseAndDestroy($currentPlayer, "THEIRAURAS:type=DOMAIN", may:false);
      break;
    case "96yd609g44"://Unearth Revelations
      Draw($currentPlayer);
      Draw($currentPlayer);
      BottomDeck($currentPlayer);
      BottomDeck($currentPlayer);
      break;
    case "1n3gygojwk"://Evasive Maneuvers
      $type = GetMZType($target);
      if($type == "ALLY") {
        $ally = new Ally($target);
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAYCARD", $ally->UniqueID());
        if(ClassContains($ally->CardID(), "RANGER", $currentPlayer)) $ally->SetDistant();
      }
      else {
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAYCARD");
        if(IsClassBonusActive($currentPlayer, "RANGER")) {
          $char = new Character($currentPlayer, 0);
          $char->SetDistant();
        }
      }
      break;
    case "2ugmnmp5af"://Take Cover
      $type = GetMZType($target);
      if($type == "ALLY") {
        $ally = new Ally($target);
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAYCARD", $ally->UniqueID());
        $ally->SetDistant();
      }
      else {
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAYCARD");
        $char = new Character($currentPlayer, 0);
        $char->SetDistant();
      }
      break;
    case "3p6i0iqmyn"://Krustallan Archer
      if($from == "PLAY") {
        AddFloatingMemoryChoice();
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
        AddDecisionQueue("ATTACKEROP", $currentPlayer, "SETDISTANT", 1);
      }
      break;
    case "2tsn0ye3ae"://Allied Warpriestess
      if($from == "PLAY") {
        $memory = &GetMemory($currentPlayer);
        if(count($memory)/MemoryPieces() >= 4) Recover($currentPlayer, 2);
      }
      break;
    case "5tlzsmw3rr"://Summon Sentinels
      $index = PlayAlly("mu6gvnta6q", $currentPlayer);//Automaton Drone
      $ally = new Ally("MYALLY-" . $index);
      $ally->AddBuffCounter();
      $index = PlayAlly("mu6gvnta6q", $currentPlayer);//Automaton Drone
      $ally = new Ally("MYALLY-" . $index);
      $ally->AddBuffCounter();
      break;
    case "17fzcyfrzr"://Imperial Rifleman
      if(IsClassBonusActive($currentPlayer, "RANGER")) {
        $ally = new Ally("MYALLY-" . LastAllyIndex($currentPlayer));
        $ally->SetDistant();
      }
      break;
    case "7dedg616r0"://Freydis, Master Tactician
      if(GetResolvedAbilityType($cardID) == "A") {
        AddCurrentTurnEffect($cardID, $currentPlayer);
        AddNextTurnEffect($cardID, $currentPlayer);
        $ally = new Ally("MYALLY-" . GetClassState($currentPlayer, $CS_PlayIndex));
        $ally->ModifyNamedCounters("TACTIC", -3);
      }
      break;
    case "7xgwve1d47"://Dahlia, Idyllic Dreamer
      if($from == "PLAY" && IsClassBonusActive($currentPlayer, "RANGER")) {
        $deck = new Deck($currentPlayer);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $deck->Top());
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        if(ElementContains($deck->Top(), "WATER", $currentPlayer)) {
          AddDecisionQueue("YESNO", $currentPlayer, "if you want to put <0> in your discard");
          AddDecisionQueue("NOPASS", $currentPlayer, "-");
          AddDecisionQueue("FINDINDICES", $currentPlayer, "DECKTOPXREMOVE,1", 1);
          AddDecisionQueue("ADDDISCARD", $currentPlayer, "DECK", 1);
        }
        else {
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "The top card of your deck is <0>");
          AddDecisionQueue("OK", $currentPlayer, "-");
        }
      }
      break;
    case "5swaf8urrq"://Whirlwind Vizier
      if(GetResolvedAbilityType($cardID) == "A") {
        DestroyAlly($currentPlayer, GetClassState($currentPlayer, $CS_PlayIndex), skipDestroy:true);
        MZChooseAndDestroy($currentPlayer, "THEIRAURAS:type=PHANTASIA", may:true);
      }
      break;
    case "0z2snsdwmx"://Scale of Souls
      if($from == "PLAY") {
        MZMoveCard($currentPlayer, "MYMEMORY", "MYHAND", silent:true);
      }
      break;
    case "4xippor7ch"://Repelling Palmblast
      $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
      $allies = &GetAllies($currentPlayer);
      for($i=count($allies)-AllyPieces(); $i>=0; $i-=AllyPieces()) {
        if(AttackValue($allies[$i]) <= 2) {
          $ally = DestroyAlly($currentPlayer, $i, skipDestroy:true);
          AddMemory($ally, $currentPlayer, "PLAY", "DOWN");
        }
      }
      $theirAllies = &GetAllies($otherPlayer);
      for($i=count($theirAllies)-AllyPieces(); $i>=0; $i-=AllyPieces()) {
        if(AttackValue($theirAllies[$i]) <= 2) {
          $ally = DestroyAlly($otherPlayer, $i, skipDestroy:true);
          AddMemory($ally, $otherPlayer, "PLAY", "DOWN");
        }
      }
      break;
    case "5kt3q2svd5"://Amorphous Strike
      if(IsClassBonusActive($currentPlayer, "GUARDIAN")) {
        MZMoveCard($currentPlayer, "MYDISCARD:type=ATTACK", "MYBANISH,GY,-", may:true);
        AddDecisionQueue("OP", $currentPlayer, "GETATTACK", 1);
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "5kt3q2svd5-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "<-", 1);
      }
      break;
    case "23yfzk96yd"://Veteran Blazebearer
      AddCurrentTurnEffect($cardID, $currentPlayer);
      AddNextTurnEffect($cardID, $currentPlayer);
      break;
    case "66pv4n1n3g"://Airship Engineer
      if(HasDistantUnit($currentPlayer)) DrawIntoMemory($currentPlayer);
      break;
    case "215upufyoz"://Tether in Flames
      $damage = CharacterLevel($currentPlayer) + 1;
      $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
      AddDecisionQueue("YESNO", $otherPlayer, "if you want to take " . $damage . " damage to prevent negation");
      AddDecisionQueue("NOPASS", $otherPlayer, "-");
      AddDecisionQueue("DEALARCANE", $currentPlayer, $damage . "-" . $cardID . "-PLAY", 1);
      AddDecisionQueue("ELSE", $otherPlayer, "-");
      AddDecisionQueue("NEGATE", $otherPlayer, $target, 1);
      break;
    case "99sx6q3p6i"://Spellshield: Wind
      AddCurrentTurnEffect($cardID, $currentPlayer);
      break;
    case "098kmoi0a5"://Take Point
      AddCurrentTurnEffect($cardID, $currentPlayer);
      AddNextTurnEffect($cardID, $currentPlayer);
      break;
    case "659ytyj2s3"://Imperious Highlander
      $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
      $myAllies = &GetAllies($currentPlayer);
      $theirAllies = &GetAllies($otherPlayer);
      $bonus = (count($theirAllies) - count($myAllies)) / AllyPieces();
      if($bonus > 0) AddCurrentTurnEffect($cardID . "-" . $bonus, $currentPlayer);
      break;
    case "b1k0zi5h8a"://Dematerialize
      MZMoveCard($currentPlayer, "THEIRITEMS:type=REGALIA&THEIRCHAR:type=REGALIA", "THEIRMATERIAL,PLAY");
      break;
    case "bro89w0ejc"://Displace
      $mzArr = explode("-", $target);
      if($mzArr[0] != "MYALLY" && $mzArr[0] != "THEIRALLY") {
        WriteLog("Invalid target for Displace");
        break;
      }
      $player = $mzArr[0] == "MYALLY" ? $currentPlayer : ($currentPlayer == 1 ? 2 : 1);
      $cardID = RemoveAlly($player, $mzArr[1]);
      $index = BanishCardForPlayer($cardID, $player, "PLAY", "-", $currentPlayer);
      RemoveBanish($player, $index);
      PlayAlly($cardID, $player, from:"BANISH");
      PlayAbility($cardID, "BANISH", 0);
      if(IsClassBonusActive($currentPlayer, "CLERIC") || IsClassBonusActive($currentPlayer, "MAGE")) {
        PlayAura("ENLIGHTEN", $currentPlayer);
      }
      break;
    case "cfpwakb1k0"://Fractal of Intrusion
      AddFloatingMemoryChoice();
      MZMoveCard($currentPlayer, "THEIRMEMORY", "THEIRDISCARD,MEMORY", isSubsequent:true);
      break;
    case "ch2bbmoqk2"://Organize the Alliance
      $type = GetMZType($target);
      if($type == "ALLY") {
        $ally = new Ally($target);
        $ally->OnFoster();
      }
      break;
    case "fdt8ptrz1b"://Scavenging Raccoon
      if($from != "PLAY") {
        MZMoveCard($currentPlayer, "THEIRDISCARD", "THEIRBANISH,GY,-", may:true);
        MZMoveCard($currentPlayer, "THEIRDISCARD", "THEIRBANISH,GY,-", may:true);
      }
      break;
    case "fp66pv4n1n"://Rusted Warshield
      AddCurrentTurnEffect($cardID, $currentPlayer);
      DrawIntoMemory($currentPlayer);
      break;
    case "hjdu50pces"://Deep Sea Fractal
      Mill(1, 1);
      Mill(2, 1);
      break;
    case "i1f0ht2tsn"://Strategic Warfare
      AddCurrentTurnEffect($cardID, $currentPlayer);
      if(CharacterLevel($currentPlayer) >= 2) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "i1f0ht2tsn-TRUE,HAND", 1);
      }
      break;
    case "igka5av43e"://Incendiary Fractal
      $damage = 2;
      if(IsClassBonusActive($currentPlayer, "MAGE")) $damage = 4;
      DealArcane($damage, 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      break;
    case "huqj5bbae3"://Winds of Retribution
      AddCurrentTurnEffect($cardID, $currentPlayer);
      break;
    case "r0zadf9q1w"://Conjure Downpour
      AddCurrentTurnEffect($cardID, $currentPlayer);
      if(IsClassBonusActive($currentPlayer, "CLERIC") && MemoryCount($currentPlayer) >= 4) DrawIntoMemory($currentPlayer);
      break;
    case "zadf9q1wl8"://Harvest Herbs
      Gather($currentPlayer, 1);
      break;
    case "zi5h8asbie"://Scatter Essence
      MZChooseAndDestroy($currentPlayer, "THEIRAURAS:type=PHANTASIA");
      break;
    case "xy5lh23qu7"://Obelisk of Fabrication
      $index = PlayAlly("mu6gvnta6q", $currentPlayer);//Automaton Drone
      $ally = new Ally("MYALLY-" . $index);
      $ally->AddBuffCounter();
      break;
    case "y5ttkk39i1"://Winbless Gatekeeper
      if($from != "PLAY") {
        AddDecisionQueue("YESNO", $currentPlayer, "if you want to pay 2 to buff an ally", 0, 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-");
        AddDecisionQueue("PAYRESOURCES", $currentPlayer, "2", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:class=GUARDIAN", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BUFFALLY", 1);
      }
      break;
    case "xhs5jwsl7d"://Enchaining Gale
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "SUPPRESS", 1);
      break;
    case "wzh973fdt8"://Develop Mana
      AddCurrentTurnEffect($cardID, $currentPlayer);
      break;
    case "x7u6wzh973"://Frostbinder Apostle
      if(CharacterLevel($currentPlayer) >= 2) {
        DealArcane(4, 1, "PLAYCARD", $cardID, resolvedTarget: $target);
      }
      break;
    case "a4dk88zq9o"://Varuckan Acolyte
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYITEMS:type=REGALIA;maxCost=0&THEIRITEMS:type=REGALIA;maxCost=0&MYCHAR:type=REGALIA;maxCost=0&THEIRCHAR:type=REGALIA;maxCost=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a regalia to destroy", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      }
      break;
    case "1gxrpx8jyp"://Fanatical Devotee
      $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
      if(SearchCount(SearchDiscard($currentPlayer, element:"FIRE")) >= 2 && (IsClassBonusActive($currentPlayer, "CLERIC") || IsClassBonusActive($currentPlayer, "TAMER")))
      {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:element=FIRE");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZBANISH", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        for($i=0; $i<2; ++$i)
        {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:element=FIRE", 1);
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZBANISH", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        }
        AddDecisionQueue("TAKEDAMAGE", $otherPlayer, "3-1gxrpx8jyp-ONDEATH", 1);
      }
      break;
    case "af098kmoi0"://Orb of Hubris
      Draw($currentPlayer);
      Draw($currentPlayer);
      Draw($currentPlayer);
      AddDecisionQueue("FINDINDICES", $currentPlayer, "HAND");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-");
      AddDecisionQueue("APPENDLASTRESULT", $currentPlayer, "-3");
      AddDecisionQueue("MULTICHOOSEHAND", $currentPlayer, "<-", 1);
      AddDecisionQueue("MULTIREMOVEHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("MULTIADDDECK", $currentPlayer, "-", 1);
      AddDecisionQueue("SHUFFLEDECK", $currentPlayer, "-", 1);
      break;
    case "d6soporhlq"://Obelisk of Protection
      if($from == "PLAY") {
        AddCurrentTurnEffect($cardID, $currentPlayer);
      }
      break;
    case "j68m69iq4d"://Sentinel Fabricator
      if($from == "PLAY") {
        $index = PlayAlly("mu6gvnta6q", $currentPlayer);//Automaton Drone
        $ally = new Ally("MYALLY-" . $index);
        $ally->AddBuffCounter();
      }
      break;
    case "lq2kkvoqk1"://Necklace of Foresight
      if($from == "PLAY") {
        PlayerOpt($currentPlayer, 4);
      }
      break;
    case "8c9htu9agw"://Prototype Staff
      if(CharacterLevel($currentPlayer) >= 4) {
        BottomDeck($currentPlayer, true, shouldDraw:false);
        AddDecisionQueue("DRAWINTOMEMORY", $currentPlayer, "-", 1);
      }
      break;
    case "44vm5kt3q2"://Battlefield Spotter
      if(IsClassBonusActive($currentPlayer, "RANGER")) {
        $ally = new Ally($target);
        $ally->SetDistant();
      }
      break;
    case "d53zc9p4lp"://Airship Cannoneer
      if($from == "PLAY" && SearchCount(SearchDiscard($currentPlayer, element:"FIRE")) >= 3) {
        MZMoveCard($currentPlayer, "MYDISCARD:element=FIRE", "MYBANISH,GY,-", may:true);
        MZMoveCard($currentPlayer, "MYDISCARD:element=FIRE", "MYBANISH,GY,-", may:true);
        MZMoveCard($currentPlayer, "MYDISCARD:element=FIRE", "MYBANISH,GY,-", may:true);
        AddDecisionQueue("ATTACKEROP", $currentPlayer, "SETDISTANT", 1);
      }
      break;
    case "ettczb14m4"://Alchemist's Kit
      $index = GetClassState($currentPlayer, $CS_PlayIndex);
      $items = &GetItems($currentPlayer);
      $draws = floor($items[$index+1]/4);
      for($i=0; $i<$draws; ++$i) Draw($currentPlayer);
      break;
    case "ht2tsn0ye3"://Meltdown
      MZChooseAndDestroy($currentPlayer, "THEIRAURAS:type=DOMAIN&THEIRITEMS&THEIRCHAR:type=WEAPON", may:false);
      break;
    case "isxy5lh23q"://Flash Grenade
      if($from != "PLAY") {
        Draw($currentPlayer);
      }
      else {
        AddCurrentTurnEffect($cardID, $currentPlayer);
      }
      break;
    case "klryvfq3hu"://Deployment Beacon
      PlayAlly("mu6gvnta6q", $currentPlayer);//Automaton Drone
      break;
    case "96659ytyj2"://Crimson Protective Trinket
      if($from == "PLAY") {
        $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
        $memory = &GetMemory($otherPlayer);
        $amount = count($memory)/MemoryPieces() > 1 ? 2 : count($memory)/MemoryPieces();
        for($i=0; $i<$amount; ++$i) {
          $index = MemoryRevealRandom($otherPlayer);
          if($index > -1 && ElementContains($memory[$index], "WIND", $otherPlayer)) {
            $cardID = $memory[$index];
            RemoveMemory($otherPlayer, $index);
            BanishCardForPlayer($cardID, $otherPlayer, "MEMORY", "-", "MEMORY");
          }
        }
      }
      break;
    case "m3pal7cpvn"://Azure Protective Trinket
      if($from == "PLAY") {
        MZMoveCard($currentPlayer, "THEIRDISCARD:element=FIRE", "THEIRBANISH,GY,-", may:true, isSubsequent: false);
        MZMoveCard($currentPlayer, "THEIRDISCARD:element=FIRE", "THEIRBANISH,GY,-", may:true, isSubsequent: true);
        MZMoveCard($currentPlayer, "THEIRDISCARD:element=FIRE", "THEIRBANISH,GY,-", may:true, isSubsequent: true);
      }
      break;
    case "h23qu7d6so"://Temporal Spectrometer
      if($from == "PLAY") {
        $items = &GetItems($currentPlayer);
        $index = GetClassState($currentPlayer, $CS_PlayIndex);
        ++$items[$index+1];
      }
      break;
    case "ir99sx6q3p"://Plea for Peace
      $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
      AddCurrentTurnEffect($cardID, $currentPlayer);
      AddNextTurnEffect($cardID, $currentPlayer);
      AddCurrentTurnEffect($cardID, $otherPlayer);
      AddNextTurnEffect($cardID, $otherPlayer);
      break;
    case "j4lx6xwr42"://Firetongue
      if($from == "EQUIP" && SearchCount(SearchDiscard($currentPlayer, element:"FIRE")) >= 1) {
        MZMoveCard($currentPlayer, "MYDISCARD:element=FIRE", "MYBANISH,GY,-", may:true);
        AddDecisionQueue("ATTACKEROP", $currentPlayer, "ADDDURABILITY", 1);
      }
      break;
    case "ls6g7xgwve"://Excoriate
      MZChooseAndDestroy($currentPlayer, "THEIRALLY:maxCost=4", may:false);
      break;
    case "m6h38lrj52"://Rococo, Explosive Maven
      $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
      if(PlayerInfluence($otherPlayer) <= 4) {
        DealArcane(2, $otherPlayer, "TRIGGER", $cardID, resolvedTarget:"THEIRCHAR-0", fromQueue:false, player:$player);
      }
      break;
    case "mxqsm4o98v"://Seasprite Diver
      if($from != "PLAY") {
        MZMoveCard($currentPlayer, "THEIRDISCARD", "THEIRBANISH,GY,-", may:true);
      }
      break;
      $numEnlighten = SearchCount(SearchAurasForCard("ENLIGHTEN", $currentPlayer));
    case "lx6xwr42i6"://Windrider Invoker
      if($from != "PLAY") {
        $numEnlighten = SearchCount(SearchAurasForCard("ENLIGHTEN", $currentPlayer));
        if($numEnlighten >= 2)
        {
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Do you want to destroy 2 ENLIGHTEN for Windrider Invoker?");
          AddDecisionQueue("YESNO", $currentPlayer, "-");
          AddDecisionQueue("NOPASS", $currentPlayer, "-");
          AddDecisionQueue("SPECIFICCARD", $currentPlayer, "WINDRIDERINVOKER", 1);
        }
      }
      break;
    case "n0wpbhigka"://Wand of Frost
      if($from != "PLAY") {
        if(IsClassBonusActive($currentPlayer, "MAGE") || IsClassBonusActive($currentPlayer, "CLERIC")) Draw($currentPlayer);
      }
      else {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "n0wpbhigka,HAND", 1);
      }
      break;
    case "n1voy5ttkk"://Shatterfall Keep
      if($from == "PLAY") {
        Mill($currentPlayer, 2);
      }
      break;
    case "nl1gxrpx8j"://Perse, Relentless Raptor
      if(GetResolvedAbilityType($cardID) == "I") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "SUPPRESS", 1);
      }
      break;
    case "nmp5af098k"://Spellshield: Astra
      AddCurrentTurnEffect($cardID, $currentPlayer);
      break;
    case "nsdwmxz1vd"://Martial Guard
      AddCurrentTurnEffect($cardID, $currentPlayer);
      break;
    case "o7eanl1gxr"://Diffusive Block
      AddCurrentTurnEffect($cardID, $currentPlayer);
      break;
    case "o98vn1voy5"://Lavaheated Brew
      Draw($currentPlayer);
      PummelHit($currentPlayer);
      AddDecisionQueue("ALLCARDSUBTYPEORPASS", $currentPlayer, "POTION", 1);
      AddDecisionQueue("ELSE", $currentPlayer, "-");
      DamageTrigger($currentPlayer, 3, "DAMAGE", "o98vn1voy5", canPass:true);
      break;
    case "oy34bro89w"://Cunning Broker
      if(GetResolvedAbilityType($cardID) == "I") {
        Draw($currentPlayer);
      }
      break;
    case "pv4n1n3gyg"://Cleric's Robe
      if($from == "PLAY") AddCurrentTurnEffect($cardID, $currentPlayer);
      break;
    case "rp5k1vt1cn"://Fractal of Insight
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Do you want to rest Fractal of Insight?");
        AddDecisionQueue("YESNO", $currentPlayer, "-", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYAURAS-" . SearchGetLast(SearchAurasForCard($cardID, $currentPlayer)), 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
        AddDecisionQueue("OPTX", $currentPlayer, 2, 1);
      }
      break;
    case "rqtjot4nmx"://Scavenge the Distillery
      MZMoveCard($currentPlayer, "MYDISCARD:subtype=POTION", "MYHAND", may:true);
      break;
    case "sbierp5k1v"://Steady Verse
      AddCurrentTurnEffect($cardID, $currentPlayer);
      DrawIntoMemory($currentPlayer);
      break;
    case "vfq3huqj5b"://Reposition
      $type = GetMZType($target);
      if($type == "ALLY") {
        $ally = new Ally($target);
        $ally->SetDistant();
      }
      else {
        $char = new Character($currentPlayer, 0);
        $char->SetDistant();
      }
      break;
    case "u7d6soporh"://Ingredient Pouch
      if($from == "PLAY") Gather($currentPlayer, 1);
      break;
    case "wa4x7e22tk"://Stream of Consciousness
      if(IsClassBonusActive($currentPlayer, "CLERIC") && MemoryCount($currentPlayer) >= 4) PlayerOpt($currentPlayer, 3);
      DrawIntoMemory($currentPlayer);
      break;
    case "wk0pw0y6is"://Obelisk of Armaments
      if($from == "PLAY") AddCharacter("hkurfp66pv", $currentPlayer);//Aurousteel Greatsword
      break;
    default: break;
  }
}

function HasDistantUnit($player) {
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces()) {
    if($allies[$i+9] == 1) return true;
  }
  return false;
}

function SpiritBladeDispersion($player)
{
  if(IsDecisionQueueActive())
  {
    PrependDecisionQueue("SPECIFICCARD", $player, "SPIRITBLADEDISPERSION", 1);
    PrependDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
    PrependDecisionQueue("MULTIZONEINDICES", $player, "MYCHAR:type=WEAPON");
  }
  else
  {
    AddDecisionQueue("MULTIZONEINDICES", $player, "MYCHAR:type=WEAPON");
    AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
    AddDecisionQueue("SPECIFICCARD", $player, "SPIRITBLADEDISPERSION", 1);
  }
}

function Chill($player, $amount=1)
{

}

function MemoryCount($player) {
  $memory = &GetMemory($player);
  return count($memory)/MemoryPieces();
}

function MemoryRevealRandom($player, $returnIndex=false)
{
  $memory = &GetMemory($player);
  $rand = GetRandom()%(count($memory)/MemoryPieces());
  $index = $rand*MemoryPieces();
  $toReveal = $memory[$index];
  $wasRevealed = RevealCards($toReveal);
  return $wasRevealed ? ($returnIndex ? $toReveal : $index) : ($returnIndex ? -1 : "");
}

function DamagePlayerAllies($player, $damage, $source, $type)
{
  $allies = &GetAllies($player);
  for($i=count($allies)-AllyPieces(); $i>=0; $i-=AllyPieces())
  {
    DealAllyDamage($player, $i, $damage, $type);
  }
}

function DamageAllAllies($amount, $source, $alsoRest=false, $alsoFreeze=false)
{
  global $currentPlayer;
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $theirAllies = &GetAllies($otherPlayer);
  for($i=count($theirAllies) - AllyPieces(); $i>=0; $i-=AllyPieces())
  {
    if($alsoRest) $theirAllies[$i+1] = 1;
    if($alsoFreeze) $theirAllies[$i+3] = 1;
    DealArcane($amount, source:$source, resolvedTarget:"THEIRALLY-$i");
  }
  $allies = &GetAllies($currentPlayer);
  for($i=count($allies) - AllyPieces(); $i>=0; $i-=AllyPieces())
  {
    if($alsoRest) $allies[$i+1] = 1;
    if($alsoFreeze) $allies[$i+3] = 1;
    DealArcane($amount, source:$source, resolvedTarget:"MYALLY-$i");
  }
}

function IsHarmonizeActive($player)
{
  global $CS_NumMelodyPlayed;
  return GetClassState($player, $CS_NumMelodyPlayed) > 0;
}

function AddPreparationCounters($player, $amount=1)
{
  global $CS_PreparationCounters;
  IncrementClassState($player, $CS_PreparationCounters, $amount);
}

function DrawIntoMemory($player)
{
  $deck = &GetDeck($player);
  if(count($deck) > 0) AddMemory(array_shift($deck), $player, "DECK", "DOWN");
}

function Mill($player, $amount)
{
  $deck = &GetDeck($player);
  if($amount > count($deck)) $amount = count($deck);
  for($i=0; $i<$amount; ++$i)
  {
    AddGraveyard(array_shift($deck), $player, "DECK");
  }
}

function Recover($player, $amount)
{
  $health = &GetHealth($player);
  if($amount > $health) $health = 0;
  else $health -= $amount;
}


//target type return values
//-1: no target
// 0: My Hero + Their Hero
// 1: Their Hero only
// 2: Any Target
// 3: Their Hero + Their Allies
// 4: My Hero only (For afflictions)
function PlayRequiresTarget($cardID)
{
  switch($cardID)
  {
    case "145y6KBhxe": return 3;//Focused Flames
    case "RIVahUIQVD": return 2;//Fireball
    case "rXHo9fLU32": return 2;//Ignite the Soul
    case "SrBA7h2a1N": return 2;//Freezing Hail
    case "L9yBqoOshh": return 2;//Spark Alight
    case "LRsgl92Iqa": return 2;//Mark the Target
    case "pn9gQjV3Rb": return 0;//Arcane Blast
    case "XMb6pSHFJg": return 3;//Embersong
    case "DqtlaMGMvd": return 2;//Erratic Bolt
    case "iohZMWh5v5": return 2;//BLazing Throw
    case "5X5W2Uda5a": return 2;//Planted Explosives
    case "0ymvddv1au": return 2;//Illuminate Secrets
    case "6ffqsuo6gb": return 2;//Refracting Missile
    case "1bqry41lw9": return 2;//Explosive Rune
    case "1n3gygojwk": return 2;//Evasive Maneuvers
    case "2ugmnmp5af": return 2;//Take Cover
    case "bro89w0ejc": return 2;//Displace
    case "ch2bbmoqk2": return 2;//Organize the Alliance
    case "igka5av43e": return 3;//Incendiary Fractal
    case "x7u6wzh973": return 2;//Frostbinder Apostle
    case "44vm5kt3q2": return 2;//Battlefield Spotter
    case "vfq3huqj5b": return 2;//Reposition
    default: return -1;
  }
}

  function ArcaneDamage($cardID)
  {
    global $currentPlayer;
    switch($cardID)
    {
      case "145y6KBhxe": return 4;//Focused Flames
      case "RIVahUIQVD": return 1+CharacterLevel($currentPlayer);
      case "rXHo9fLU32": return 1;//Ignite the Soul
      case "SrBA7h2a1N": return 2;//Freezing Hail
      case "L9yBqoOshh": return (IsClassBonusActive($currentPlayer, "MAGE") ? 3 : 2);//Spark Alight
      case "LRsgl92Iqa": return 1;//Mark the Target
      case "pn9gQjV3Rb": return 11;//Arcane Blast
      case "XMb6pSHFJg": return 2;//Embersong
      case "iohZMWh5v5": return 4;//BLazing Throw
      return 0;
    }
  }

  //Parameters:
  //Player = Player controlling the arcane effects
  //target = See function PlayRequiresTarget
  function DealArcane($damage, $target=0, $type="PLAYCARD", $source="NA", $fromQueue=false, $player=0, $mayAbility=false, $limitDuplicates=false, $skipHitEffect=false, $resolvedTarget="", $nbArcaneInstance=1)
  {
    global $currentPlayer, $CS_ArcaneTargetsSelected;
    if ($player == 0) $player = $currentPlayer;
    if ($damage > 0) {
      //$damage += CurrentEffectArcaneModifier($source, $player) * $nbArcaneInstance;
      if ($type != "PLAYCARD") WriteLog(CardLink($source, $source) . " is dealing " . $damage . " arcane damage.");
      if ($fromQueue) {
        if (!$limitDuplicates) {
          PrependDecisionQueue("PASSPARAMETER", $player, "{0}");
          PrependDecisionQueue("SETCLASSSTATE", $player, $CS_ArcaneTargetsSelected); //If already selected for arcane multiselect (e.g. Singe/Azvolai)
          PrependDecisionQueue("PASSPARAMETER", $player, "-");
        }
        if (!$skipHitEffect) PrependDecisionQueue("ARCANEHITEFFECT", $player, $source, 1);
        PrependDecisionQueue("DEALARCANE", $player, $damage . "-" . $source . "-" . $type, 1);
        if ($resolvedTarget != "") {
          PrependDecisionQueue("PASSPARAMETER", $currentPlayer, $resolvedTarget);
        } else {
          PrependDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
          if ($mayAbility) {
            PrependDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          } else {
            PrependDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          }
          PrependDecisionQueue("SETDQCONTEXT", $player, "Choose a target for <0>");
          PrependDecisionQueue("FINDINDICES", $player, "ARCANETARGET," . $target);
          PrependDecisionQueue("SETDQVAR", $currentPlayer, "0");
          PrependDecisionQueue("PASSPARAMETER", $currentPlayer, $source);
        }
      } else {
        if ($resolvedTarget != "") {
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $resolvedTarget);
        } else {
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $source);
          AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
          AddDecisionQueue("FINDINDICES", $player, "ARCANETARGET," . $target);
          AddDecisionQueue("SETDQCONTEXT", $player, "Choose a target for <0>");
          if ($mayAbility) {
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
          } else {
            AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
          }
          AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        }
        AddDecisionQueue("DEALARCANE", $player, $damage . "-" . $source . "-" . $type, 1);
        if (!$skipHitEffect) AddDecisionQueue("ARCANEHITEFFECT", $player, $source, 1);
        if (!$limitDuplicates) {
          AddDecisionQueue("PASSPARAMETER", $player, "-");
          AddDecisionQueue("SETCLASSSTATE", $player, $CS_ArcaneTargetsSelected);
          AddDecisionQueue("PASSPARAMETER", $player, "{0}");
        }
      }
    }
  }

  function ArcaneHitEffect($player, $source, $target, $damage)
  {

  }

  //target type return values
  //-1: no target
  // 0: My Hero + Their Hero
  // 1: Their Hero only
  // 2: Any Target
  // 3: Their Allies
  // 4: My Hero only (For afflictions)
  function GetArcaneTargetIndices($player, $target)
  {
    global $CS_ArcaneTargetsSelected;
    $otherPlayer = ($player == 1 ? 2 : 1);
    if ($target == 4) return "MYCHAR-0";
    if($target != 3) $rv = "THEIRCHAR-0";
    else $rv = "";
    if(($target == 0 && !ShouldAutotargetOpponent($player)) || $target == 2)
    {
      $rv .= ",MYCHAR-0";
    }
    if($target == 2)
    {
      $theirAllies = &GetAllies($otherPlayer);
      for($i=0; $i<count($theirAllies); $i+=AllyPieces())
      {
        $rv .= ",THEIRALLY-" . $i;
      }
      $myAllies = &GetAllies($player);
      for($i=0; $i<count($myAllies); $i+=AllyPieces())
      {
        $rv .= ",MYALLY-" . $i;
      }
    }
    elseif($target == 3 || $target == 5)
    {
      $theirAllies = &GetAllies($otherPlayer);
      for($i=0; $i<count($theirAllies); $i+=AllyPieces())
      {
        if($rv != "") $rv .= ",";
        $rv .= "THEIRALLY-" . $i;
      }
    }
    $targets = explode(",", $rv);
    $targetsSelected = GetClassState($player, $CS_ArcaneTargetsSelected);
    for($i=count($targets)-1; $i>=0; --$i)
    {
      if(DelimStringContains($targetsSelected, $targets[$i])) unset($targets[$i]);
    }
    return implode(",", $targets);
  }

function CountPitch(&$pitch, $min = 0, $max = 9999)
{
  $pitchCount = 0;
  for($i = 0; $i < count($pitch); ++$i) {
    $cost = CardCost($pitch[$i]);
    if($cost >= $min && $cost <= $max) ++$pitchCount;
  }
  return $pitchCount;
}

function HandIntoMemory($player)
{
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYHAND");
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose a card to put into memory", 1);
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZADDZONE", $player, "MYMEMORY,HAND,DOWN", 1);
  AddDecisionQueue("MZREMOVE", $player, "-", 1);
}

function Draw($player, $mainPhase = true, $fromCardEffect = true)
{
  global $EffectContext, $mainPlayer;
  $otherPlayer = ($player == 1 ? 2 : 1);
  $deck = &GetDeck($player);
  $hand = &GetHand($player);
  if(count($deck) == 0) return -1;
  if(CurrentEffectPreventsDraw($player, $mainPhase)) return -1;
  array_push($hand, array_shift($deck));
  PermanentDrawCardAbilities($player);
  $hand = array_values($hand);
  return $hand[count($hand) - 1];
}

function WakeUpChampion($player)
{
  $char = &GetPlayerCharacter($player);
  $char[1] = 2;
}

function IsTrueSightActive($attackID)
{
  global $CS_PlayIndex, $mainPlayer;
  $index = GetClassState($mainPlayer, $CS_PlayIndex);
  if(HasTrueSight($attackID, $mainPlayer, $index)) return true;
  if(IsAlly($attackID))
  {
    $allies = &GetAllies($mainPlayer);
    $uniqueID = $allies[$index+5];
    if(CurrentEffectGrantsTrueSight($mainPlayer, $uniqueID)) return true;
  }
  return false;
}
