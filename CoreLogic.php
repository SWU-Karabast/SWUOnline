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
          array_push($attackModifiers, $combatChain[$i-1], $attack);
          if($i == 1) $totalAttack += $attack;
          else AddAttack($totalAttack, $attack);
        }
      }
      else
      {
        $totalDefense += BlockingCardDefense($i-1, $combatChain[$i+1], $combatChain[$i+2]);
      }
    }

    if($combatChainState[$CCS_WeaponIndex] != -1)
    {
      $attack = 0;
      if($attackType == "W") $attack = $mainCharacter[$combatChainState[$CCS_WeaponIndex]+3];
      else if(DelimStringContains(CardSubtype($combatChain[0]), "Aura")) $attack = $mainAuras[$combatChainState[$CCS_WeaponIndex]+3];
      else if(IsAlly($combatChain[0]))
      {
        $allies = &GetAllies($mainPlayer);
        if(count($allies) > $combatChainState[$CCS_WeaponIndex]+7) $attack = $allies[$combatChainState[$CCS_WeaponIndex]+7];
      }
      if($canGainAttack || $attack < 0)
      {
        array_push($attackModifiers, "+1 Attack Counters", $attack);
        AddAttack($totalAttack, $attack);
      }
    }
    $attack = MainCharacterAttackModifiers();
    if($canGainAttack || $attack < 0)
    {
      array_push($attackModifiers, "Character/Equipment", $attack);
      AddAttack($totalAttack, $attack);
    }
    //$attack = AuraAttackModifiers(0);//FAB
    if($canGainAttack || $attack < 0)
    {
      array_push($attackModifiers, "Aura Ability", $attack);
      AddAttack($totalAttack, $attack);
    }
    $attack = ArsenalAttackModifier();
    if($canGainAttack || $attack < 0)
    {
      array_push($attackModifiers, "Arsenal Ability", $attack);
      AddAttack($totalAttack, $attack);
    }
}

// function CharacterLevel($player)//FAB
// {
//   global $CS_CachedCharacterLevel;
//   return GetClassState($player, $CS_CachedCharacterLevel);
// }

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

function AddCombatChain($cardID, $player, $from, $resourcesPaid, $upgradesWithMetadata)
{
  global $combatChain, $turn;
  $index = count($combatChain);
  $combatChain[] = $cardID;
  $combatChain[] = $player;
  $combatChain[] = $from;
  $combatChain[] = $resourcesPaid;
  $combatChain[] = RepriseActive();
  $combatChain[] = 0;//Attack modifier
  $combatChain[] = 0;//Defense modifier
  $combatChain[] = $upgradesWithMetadata;
  //if($turn[0] == "B" || CardType($cardID) == "DR") OnBlockEffects($index, $from);//FAB
  CurrentEffectAttackAbility();
  return $index;
}

//FAB
// function CombatChainPowerModifier($index, $amount)
// {
//   global $combatChain;
//   $combatChain[$index+5] += $amount;
//   ProcessPhantasmOnBlock($index);
// }

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
  return $combatChainState[$CCS_CachedDominateActive] == "1";
}

function CachedOverpowerActive()
{
  global $combatChainState, $CCS_CachedOverpowerActive;
  return $combatChainState[$CCS_CachedOverpowerActive] == "1";
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

function StartTurnAbilities()
{
  global $initiativePlayer;
  //AuraStartTurnAbilities();//FAB
  ItemStartTurnAbilities();
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

// function ArsenalHitEffects()//FAB
// {
//   global $combatChain, $mainPlayer;
//   $attackID = $combatChain[0];
//   $attackType = CardType($attackID);
//   $attackSubType = CardSubType($attackID);
//   $arsenal = GetArsenal($mainPlayer);
//   $modifier = 0;
//   for($i=0; $i<count($arsenal); $i+=ArsenalPieces())
//   {
//     switch($arsenal[$i])
//     {

//       default: break;
//     }
//   }
//   return $modifier;
// }

function CharacterPlayCardAbilities($cardID, $from)
{
  global $currentPlayer;
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
  global $currentPlayer, $mainPlayer, $CS_NumNonAttackCards;
  if(LeaderAbilitiesIgnored()) return;

  $character = &GetPlayerCharacter($currentPlayer);
  for($i = 0; $i < count($character); $i += CharacterPieces()) {
    if($character[$i+1] != 2) continue;
    switch($character[$i]) {
      case "3045538805"://Hondo Ohnaka
        if($from == "RESOURCES") {
          AddLayer("TRIGGER", $currentPlayer, "3045538805");
        }
        break;
      case "1384530409"://Cad Bane
        $otherPlayer = ($currentPlayer == 1 ? 2 : 1);
        if ($from != 'PLAY' && $from != 'EQUIP' && TraitContains($cardID, "Underworld", $currentPlayer) && SearchCount(SearchAllies($otherPlayer)) > 0) {
          // Note - this is a bit of a hack by sending the index in as the unique ID
          AddLayer("TRIGGER", $currentPlayer, "1384530409");
        }
        break;
      case "2358113881"://Quinlan Vos
        if($from != 'PLAY' && $from != "EQUIP" && DefinedTypesContains($cardID, "Unit", $currentPlayer)) {
          $cardCost = CardCost($cardID);
          $theirAllies = &GetTheirAllies($currentPlayer);
          $hasValidTarget = false;

          for ($j = 0; $j < count($theirAllies); $j += AllyPieces()) {
            if (CardCost($theirAllies[$j]) == $cardCost) {
              $hasValidTarget = true;
              break;
            }
          }

          if ($hasValidTarget) {
            AddLayer("TRIGGER", $currentPlayer, "2358113881");
          }
        }
        break;
      case "9005139831"://The Mandalorian Leader
        if(DefinedTypesContains($cardID, "Upgrade", $currentPlayer) || PilotWasPlayed($currentPlayer, $cardID)) {
          AddLayer("TRIGGER", $currentPlayer, "9005139831");
        }
        break;
      case "9334480612"://Boba Fett (Daimyo)
        if($from != "PLAY" && DefinedTypesContains($cardID, "Unit", $currentPlayer)
            && HasKeyword($cardID, "Any", $currentPlayer)
            && !SearchCurrentLayers("TRIGGER", $currentPlayer, "9334480612")) {
          AddLayer("TRIGGER", $currentPlayer, "9334480612");
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

//FAB
// function CanDamageBePrevented($player, $damage, $type, $source="-")
// {
//   global $mainPlayer;
//   if($source == "aebjvwbciz" && IsClassBonusActive($mainPlayer, "GUARDIAN") && CharacterLevel($mainPlayer) >= 2) return false;
//   return true;
// }

function DealDamageAsync($player, $damage, $type="DAMAGE", $source="NA")
{
  global $CS_DamagePrevention, $combatChain;
  global $CS_ArcaneDamagePrevention, $dqVars, $dqState;

  $classState = &GetPlayerClassState($player);
  if($type == "COMBAT" && $damage > 0 && EffectPreventsHit()) HitEffectsPreventedThisLink();
  if($type == "COMBAT" || $type == "ATTACKHIT") $source = $combatChain[0];
  $damage = max($damage, 0);
  $damageThreatened = $damage;
  // $preventable = CanDamageBePrevented($player, $damage, $type, $source);//FAB
  // if($preventable)
  // {
  //   $damage = CurrentEffectPreventDamagePrevention($player, $type, $damage, $source);
  //   if(ConsumeDamagePrevention($player)) return 0;//If damage can be prevented outright, don't use up your limited damage prevention
  //   if($type == "ARCANE")
  //   {
  //     if($damage <= $classState[$CS_ArcaneDamagePrevention])
  //     {
  //       $classState[$CS_ArcaneDamagePrevention] -= $damage;
  //       $damage = 0;
  //     }
  //     else
  //     {
  //       $damage -= $classState[$CS_ArcaneDamagePrevention];
  //       $classState[$CS_ArcaneDamagePrevention] = 0;
  //     }
  //   }
  //   if($damage <= $classState[$CS_DamagePrevention])
  //   {
  //     $classState[$CS_DamagePrevention] -= $damage;
  //     $damage = 0;
  //   }
  //   else
  //   {
  //     $damage -= $classState[$CS_DamagePrevention];
  //     $classState[$CS_DamagePrevention] = 0;
  //   }
  // }

  //else: CR 2.0 6.4.10h If damage is not prevented, damage prevention effects are not consumed
  $damage = max($damage, 0);
  //$damage = CurrentEffectDamagePrevention($player, $type, $damage, $source, $preventable);//FAB
  //$damage = AuraTakeDamageAbilities($player, $damage, $type);//FAB
  //$damage = PermanentTakeDamageAbilities($player, $damage, $type);//FAB
  //$damage = ItemTakeDamageAbilities($player, $damage, $type);
  //if($damage == 1 && $preventable && SearchItemsForCard("EVR069", $player) != "") $damage = 0;//Must be last//FAB
  $dqVars[0] = $damage;
  if($type == "COMBAT") $dqState[6] = $damage;
  PrependDecisionQueue("FINALIZEDAMAGE", $player, $damageThreatened . "," . $type . "," . $source);
  if($damage > 0)
  {
    //AddDamagePreventionSelection($player, $damage, $preventable);//FAB
    AddDamagePreventionSelection($player, $damage, false);
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
    }

    AllyDealDamageAbilities($otherPlayer, $damage, $type);
    $classState[$CS_DamageTaken] += $damage;
    if($player == $defPlayer && $type == "COMBAT" || $type == "ATTACKHIT") $combatChainState[$CCS_AttackTotalDamage] += $damage;
    // if($type == "ARCANE") $classState[$CS_ArcaneDamageTaken] += $damage;//FAB
    CurrentEffectDamageEffects($player, $source, $type, $damage);
  }
  PlayerLoseHealth($player, $damage);
  LogDamageStats($player, $damageThreatened, $damage);
  return $damage;
}

function ProcessDealDamageEffect($cardID)
{

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

// function AttackDamageAbilities($damageDone)//FAB
// {
//   global $combatChain, $defPlayer;
//   $attackID = $combatChain[0];
//   switch($attackID)
//   {
//     default: break;
//   }
// }

function LoseHealth($amount, $player)
{
  PlayerLoseHealth($player, $amount);
}

function Restore($amount, $player)
{
  if(SearchCurrentTurnEffects("7533529264", $player)) {
    WriteLog("<span style='color:red;'>Wolffe prevents the healing</span>");
    return false;
  }
  if(SearchAlliesForCard(1, "6277739341") != "" || SearchAlliesForCard(2, "6277739341") != "") {
    WriteLog("<span style='color:red;'>Confederate Tri-Fighter prevents the healing</span>");
    return false;
  }

  $health = &GetHealth($player);
  WriteLog("Player " . $player . " gained " . $amount . " health.");
  if($amount > $health) $amount = $health;
  $health -= $amount;
  AddEvent("RESTORE", "P" . $player . "BASE!" . $amount);
  return true;
}

function PlayerLoseHealth($player, $amount)
{
  $health = &GetHealth($player);
  //$amount = AuraLoseHealthAbilities($player, $amount);//FAB
  $char = &GetPlayerCharacter($player);
  if(count($char) == 0) return;
  $health += $amount;
  AddEvent("DAMAGE", "P" . $player . "BASE!" . $amount);
  if(PlayerRemainingHealth($player) <= 0)
  {
    PlayerWon(($player == 1 ? 2 : 1));
  }
}

function PlayerRemainingHealth($player) {
  $health = &GetHealth($player);
  $char = &GetPlayerCharacter($player);
  if($char[0] == "DUMMY") return 1000 - $health;
  return CardHP($char[0]) - $health;
}

function IsGameOver()
{
  global $inGameStatus, $GameStatus_Over;
  return $inGameStatus == $GameStatus_Over;
}

function PlayerWon($playerID)
{
  global $winner, $turn, $gameName, $p1id, $p2id, $p1uid, $p2uid, $p1IsChallengeActive, $p2IsChallengeActive, $conceded, $currentRound;
  global $p1DeckLink, $p2DeckLink, $inGameStatus, $GameStatus_Over, $firstPlayer, $p1deckbuilderID, $p2deckbuilderID;
  if($turn[0] == "OVER") return;
  include_once "./MenuFiles/ParseGamefile.php";

  $winner = $playerID;
  if ($playerID == 1 && $p1uid != "") WriteLog($p1uid . " wins!", $playerID);
  elseif ($playerID == 2 && $p2uid != "") WriteLog($p2uid . " wins!", $playerID);
  else WriteLog("Player " . $winner . " wins!");

  $inGameStatus = $GameStatus_Over;
  $turn[0] = "OVER";
  SetCachePiece($gameName, 14, 6);//$MGS_GameOverStatsLogged
  if(GetCachePiece($gameName, 14) == 7) return;//$MGS_StatsLoggedIrreversible
  try {
    logCompletedGameStats();
  } catch (Exception $e) {

  }

  try {
    SendSWUStatsResults();
  } catch (Exception $e) {

  }

  if(!$conceded || $currentRound>= 3) {
    //If this happens, they left a game in progress -- add disconnect logging?
  }
}

function SendSWUStatsResults() {
  global $gameName, $firstPlayer, $winner, $currentRound, $p1id, $p2id, $p1DeckLink, $p2DeckLink;

  $url = 'https://swustats.net/TCGEngine/APIs/SubmitGameResult.php';
	$loser = ($winner == 1 ? 2 : 1);
  $winHero = GetCachePiece($gameName, ($winner == 1 ? 7 : 8));
	$loseHero = GetCachePiece($gameName, ($winner == 1 ? 8 : 7));
  $winnerHealth = GetHealth($winner);
  $p1Char = &GetPlayerCharacter(1);
  $p1Hero = FindLeaderInPlay(1);
  $p1Base = DeduplicateBase($p1Char[0]);
  $p1BaseColor = AspectToColor(CardAspects($p1Base));
  $p2Char = &GetPlayerCharacter(2);
  $p2Hero = FindLeaderInPlay(2);
  $p2Base = DeduplicateBase($p2Char[0]);
  $p2BaseColor = AspectToColor(CardAspects($p2Base));
	$winnerDeck = file_get_contents("./Games/" . $gameName . "/p" . $winner . "Deck.txt");
	$loserDeck = file_get_contents("./Games/" . $gameName . "/p" . $loser . "Deck.txt");
  $data_json = json_encode([
    'gameName' => $gameName,
    'round' => $currentRound,
    'winner' => $winner,
    'winHero' => $winHero,
    'loseHero' => $loseHero,
    'firstPlayer' => $firstPlayer,
    'p1id' => $p1id,
    'p2id' => $p2id,
    'p1DeckLink' => $p1DeckLink,
    'p2DeckLink' => $p2DeckLink,
    'winnerHealth' => $winnerHealth,
    'winnerDeck' => $winnerDeck,
    'loserDeck' => $loserDeck,
    'player1' => SerializeGameResult(1, "", file_get_contents("./Games/" . $gameName . "/p1Deck.txt"), $gameName, $p2Hero, "", "", $p2BaseColor, $p1Hero, $p1Base),
    'player2' => SerializeGameResult(2, "", file_get_contents("./Games/" . $gameName . "/p2Deck.txt"), $gameName, $p1Hero, "", "", $p1BaseColor, $p2Hero, $p2Base)
  ]);

  // Initialize cURL session
  $ch = curl_init($url);

  // Set cURL options
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);

  // Execute cURL session and get the response
  $response = curl_exec($ch);

  // Check for errors
  if ($response === false) {
      $error = curl_error($ch);
      curl_close($ch);
      die('Curl error: ' . $error);
  }

  // Close cURL session
  curl_close($ch);
}

function DeduplicateBase($base)
{
  if(CardHP($base) != 30) return $base;//TODO: Add rarity check too?
  $baseAspect = CardAspects($base);
  switch($baseAspect) {
    case "Command": return "2055904747";
    case "Vigilance": return "7303722102";
    case "Aggression": return "8659924257";
    case "Cunning": return "4313706014";
    default: return $base;
  }
}

function AspectToColor($aspect)
{
  switch($aspect) {
    case "Command": return "Green";
    case "Vigilance": return "Blue";
    case "Aggression": return "Red";
    case "Cunning": return "Yellow";
    case "Heroism": return "White";
    case "Villainy": return "Black";
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

function UnsetDiscardModifier($player, $modifier, $newMod="-")
{
  $discard = &GetDiscard($player);
  for($i=0; $i<count($discard); $i+=DiscardPieces())
  {
    $cardModifier = explode("-", $discard[$i+1])[0];
    if($cardModifier == $modifier) $discard[$i+1] = $newMod;
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

function UnsetTurnModifiers()
{
  UnsetDiscardModifier(1, "TT");
  UnsetDiscardModifier(1, "TTOP"); // TTOP is the same as TT, but for the opponent
  UnsetDiscardModifier(1, "TTFREE");
  UnsetDiscardModifier(1, "TTOPFREE"); // TTOPFREE is the same as TTFREE, but for the opponent
  UnsetDiscardModifier(2, "TT");
  UnsetDiscardModifier(2, "TTOP"); // TTOP is the same as TT, but for the opponent
  UnsetDiscardModifier(2, "TTFREE");
  UnsetDiscardModifier(2, "TTOPFREE"); // TTOPFREE is the same as TTFREE, but for the opponent
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

//FAB
// function ChainLinkResolvedEffects()
// {
//   global $combatChain, $mainPlayer, $currentTurnEffects;
//   if($combatChain[0] == "MON245" && !ExudeConfidenceReactionsPlayable())
//   {
//     AddCurrentTurnEffect($combatChain[0], $mainPlayer, "CC");
//   }
//   switch($combatChain[0])
//   {
//     case "CRU051": case "CRU052":
//       EvaluateCombatChain($totalAttack, $totalBlock);
//       for ($i = CombatChainPieces(); $i < count($combatChain); $i += CombatChainPieces()) {
//         if (!($totalBlock > 0 && (intval(BlockValue($combatChain[$i])) + BlockModifier($combatChain[$i], "CC", 0) + $combatChain[$i + 6]) > $totalAttack)) {
//           UndestroyCurrentWeapon();
//         }
//       }
//       break;
//       default: break;
//   }
// }

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
              $deck[] = array_shift($deck);
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

function GetMultizoneIndicesForTitle($player, $title, $onlyReady=false) {
  $indices=[];
  $char = &GetPlayerCharacter($player);
  $leaderIndex = CharacterPieces();
  if(count($char) > $leaderIndex && CardTitle($char[$leaderIndex]) == $title && (!$onlyReady || $char[$leaderIndex+1] == 2))
    array_push($indices, "MYCHAR-$leaderIndex");
  $allies = SearchAlliesForTitle($player, $title);
  if($allies != "") {
    $allies = explode(",", $allies);
    for($i=0; $i<count($allies); ++$i) {
      $ally = new Ally("MYALLY-$allies[$i]", $player);
      if(!$onlyReady || !$ally->IsExhausted()) array_push($indices, "MYALLY-$allies[$i]");
    }
  }
  return implode(",", $indices);
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

//FAB
// function CanPlayAsInstant($cardID, $index=-1, $from="")
// {
//   global $currentPlayer, $CS_NextWizardNAAInstant, $CS_NextNAAInstant, $CS_CharacterIndex, $CS_ArcaneDamageTaken, $CS_NumWizardNonAttack;
//   global $mainPlayer, $CS_PlayedAsInstant;
//   $otherPlayer = $currentPlayer == 1 ? 2 : 1;
//   $cardType = CardType($cardID);
//   $otherCharacter = &GetPlayerCharacter($otherPlayer);
//   if($cardID == "MON034" && SearchItemsForCard("DYN066", $currentPlayer) != "") return true;
//   if(GetClassState($currentPlayer, $CS_NextWizardNAAInstant))
//   {
//     if(ClassContains($cardID, "WIZARD", $currentPlayer) && $cardType == "A") return true;
//   }
//   if(GetClassState($currentPlayer, $CS_NumWizardNonAttack) && ($cardID == "CRU174" || $cardID == "CRU175" || $cardID == "CRU176")) return true;
//   if($currentPlayer != $mainPlayer && ($cardID == "CRU165" || $cardID == "CRU166" || $cardID == "CRU167")) return true;
//   if(GetClassState($currentPlayer, $CS_NextNAAInstant))
//   {
//     if($cardType == "A") return true;
//   }
//   if($cardType == "C" || $cardType == "E" || $cardType == "W")
//   {
//     if($index == -1) $index = GetClassState($currentPlayer, $CS_CharacterIndex);
//     if(SearchCharacterEffects($currentPlayer, $index, "INSTANT")) return true;
//   }
//   if($from == "BANISH")
//   {
//     $banish = GetBanish($currentPlayer);
//     if($index < count($banish))
//     {
//       $mod = explode("-", $banish[$index+1])[0];
//       if(($cardType == "I" && ($mod == "TCL" || $mod == "TT" || $mod == "TCC" || $mod == "NT" || $mod == "MON212")) || $mod == "INST" || $mod == "ARC119") return true;
//     }
//   }
//   if(GetClassState($currentPlayer, $CS_PlayedAsInstant) == "1") return true;
//   if($cardID == "ELE106" || $cardID == "ELE107" || $cardID == "ELE108") { return PlayerHasFused($currentPlayer); }
//   if($cardID == "CRU143") { return GetClassState($otherPlayer, $CS_ArcaneDamageTaken) > 0; }
//   if($from == "ARS" && $cardType == "A" && $currentPlayer != $mainPlayer && PitchValue($cardID) == 3 && (SearchCharacterActive($currentPlayer, "EVR120") || SearchCharacterActive($currentPlayer, "UPR102") || SearchCharacterActive($currentPlayer, "UPR103") || (SearchCharacterActive($currentPlayer, "CRU097") && SearchCurrentTurnEffects($otherCharacter[0] . "-SHIYANA", $currentPlayer) && IsIyslander($otherCharacter[0])))) return true;
//   $isStaticType = IsStaticType($cardType, $from, $cardID);
//   $abilityType = "-";
//   if($isStaticType) $abilityType = GetAbilityType($cardID, $index, $from);
//   if(($cardType == "AR" || ($abilityType == "AR" && $isStaticType)) && IsReactionPhase() && $currentPlayer == $mainPlayer) return true;
//   if(($cardType == "DR" || ($abilityType == "DR" && $isStaticType)) && IsReactionPhase() && $currentPlayer != $mainPlayer && IsDefenseReactionPlayable($cardID, $from)) return true;
//   return false;
// }

function HasLostClass($player)
{
  if(SearchCurrentTurnEffects("UPR187", $player)) return true;//Erase Face
  return false;
}

//FAB
// function ClassOverride($cardID, $player="")
// {
//   global $currentTurnEffects;
//   $cardClass = CardClass($cardID);
//   if ($cardClass == "NONE") $cardClass = "";
//   $otherPlayer = ($player == 1 ? 2 : 1);
//   $otherCharacter = &GetPlayerCharacter($otherPlayer);

//   if(SearchCurrentTurnEffects("UPR187", $player)) return "NONE";//Erase Face
//   if(count($otherCharacter) > 0 && SearchCurrentTurnEffects($otherCharacter[0] . "-SHIYANA", $player)) {
//     if($cardClass != "") $cardClass .= ",";
//     $cardClass .= CardClass($otherCharacter[0]) . ",SHAPESHIFTER";
//   }

//   for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
//   {
//     if($currentTurnEffects[$i+1] != $player) continue;
//     $toAdd = "";
//     switch($currentTurnEffects[$i])
//     {
//       case "MON095": case "MON096": case "MON097":
//       case "EVR150": case "EVR151": case "EVR152":
//       case "UPR155": case "UPR156": case "UPR157": $toAdd = "ILLUSIONIST"; break;
//       default: break;
//     }
//     if($toAdd != "")
//     {
//       if($cardClass != "") $cardClass .= ",";
//       $cardClass .= $toAdd;
//     }
//   }
//   if($cardClass == "") return "NONE";
//   return $cardClass;
// }

function NameOverride($cardID, $player="")
{
  $name = CardName($cardID);
  if(SearchCurrentTurnEffects("OUT183", $player)) $name = "";
  return $name;
}

function DefinedTypesContains($cardID, $type, $player="")
{
  if(!$cardID || $cardID == "" || strlen($cardID) < 3) return "";
  $cardTypes = DefinedCardType($cardID);
  $cardTypes2 = DefinedCardType2Wrapper($cardID);
  return DelimStringContains($cardTypes, $type) || DelimStringContains($cardTypes2, $type);
}

//FAB
// function CardTypeContains($cardID, $type, $player="")
// {
//   $cardTypes = CardTypes($cardID);
//   return DelimStringContains($cardTypes, $type);
// }

// function ClassContains($cardID, $class, $player="")
// {
//   $cardClass = ClassOverride($cardID, $player);
//   return DelimStringContains($cardClass, $class);
// }

function AspectContains($cardID, $aspect, $player="")
{
  $cardAspect = CardAspects($cardID);
  return DelimStringContains($cardAspect, $aspect);
}

function TraitContainsAny($cardID, $traits, $player="", $index=-1) {
  $traitsArr = explode(",", $traits);
  for ($i = 0; $i < count($traitsArr); $i++) {
    if (TraitContains($cardID, $traitsArr[$i], $player, $index)) return true;
  }
  return false;
}

function TraitContainsAll($cardID, $traits, $player="", $index=-1) {
  $traitsArr = explode(",", $traits);
  for ($i = 0; $i < count($traitsArr); $i++) {
    if (!TraitContains($cardID, $traits[$i], $player, $index)) return false;
  }
  return true;
}

function TraitContains($cardID, $trait, $player="", $index=-1) {
  $trait = str_replace("_", " ", $trait); //"MZALLCARDTRAITORPASS" and possibly other decision queue options call this function with $trait having been underscoreified, so I undo that here.
  $isLeaderSide = CardIDIsLeader($cardID) && LeaderUndeployed($cardID) == "";
  if($index != -1 && !$isLeaderSide) {
    $ally = new Ally("MYALLY-" . $index, $player);

    // // Check for upgrades
    $upgrades = $ally->GetUpgrades();
    for($i=0; $i<count($upgrades); ++$i) {
      switch ($upgrades[$i]) {
        case "7687006104":
          if($trait == "Mandalorian") return true;
          break;
        default: break;
      }
    }

    if ($ally->IsCloned() && $trait == "Clone") return true;
  }
  $cardTrait = CardTraits($cardID);
  return DelimStringContains($cardTrait, $trait);
}

function AllyTraitContainsOrUpgradeTraitContains($allyUniqueID, $trait) {
  $ally = new Ally($allyUniqueID);
  $upgrades = $ally->GetUpgrades();
  for($i=0; $i<count($upgrades); ++$i) {
    if (TraitContains($upgrades[$i], $trait)) return true;
  }

  return TraitContains($ally->CardID(), $trait);
}

function HasKeyword($cardID, $keyword, $player="", $index=-1){
  switch($keyword){
    case "Smuggle": return SmuggleCost($cardID, $player, $index) > -1;
    case "Raid": return RaidAmount($cardID, $player, $index, true) > 0;
    case "Grit": return HasGrit($cardID, $player, $index);
    case "Restore": return RestoreAmount($cardID, $player, $index) > 0;
    case "Bounty": return CollectBounty($player, $cardID, $cardID, false, $player, true) > 0; // Since we don't have information about "exhausted" and "owner," this data may be imprecise in very rare cases.
    case "Overwhelm": return HasOverwhelm($cardID, $player, $index);
    case "Saboteur": return HasSaboteur($cardID, $player, $index);
    case "Shielded": return HasShielded($cardID, $player);
    case "Sentinel": return HasSentinel($cardID, $player, $index);
    case "Ambush": return HasAmbush($cardID, $player, $index,"");
    case "Coordinate": return HasCoordinate($cardID, $player, $index);
    case "Exploit": return ExploitAmount($cardID, $player, true) > 0;
    case "Piloting": return PilotingCost($cardID) > -1;//TODO: test Boba Daimyo trigger when piloting as upgrade vs unit
    case "Any":
      return SmuggleCost($cardID, $player, $index) > -1 ||
        RaidAmount($cardID, $player, $index, true) > 0 ||
        HasGrit($cardID, $player, $index) ||
        RestoreAmount($cardID, $player, $index) > 0 ||
        CollectBounty($player, $cardID, $cardID, false, $player, true) > 0 || // Since we don't have information about "exhausted" and "owner," this data may be imprecise in very rare cases.
        HasOverwhelm($cardID, $player, $index) ||
        HasSaboteur($cardID, $player, $index) ||
        HasShielded($cardID, $player) ||
        HasSentinel($cardID, $player, $index) ||
        HasAmbush($cardID, $player, $index, "") ||
        HasCoordinate($cardID, $player, $index) ||
        ExploitAmount($cardID, $player, true) > 0;
    default: return false;
  }
}

function ArenaContains($cardID, $arena, $player="")
{
  $cardArena = CardArenas($cardID);
  return DelimStringContains($cardArena, $arena);
}

function SubtypeContains($cardID, $subtype, $player="")
{
  $cardSubtype = CardSubtype($cardID);
  return DelimStringContains($cardSubtype, $subtype);
}

//FAB
// function ElementContains($cardID, $element, $player="")
// {
//   $cardElement = CardElement($cardID);
//   return DelimStringContains($cardElement, $element);
// }

function CardNameContains($cardID, $name, $player="")
{
  $cardName = NameOverride($cardID, $player);
  return DelimStringContains($cardName, $name);
}

//FAB
// function TalentOverride($cardID, $player="")
// {
//   global $currentTurnEffects;
//   $cardTalent = CardTalent($cardID);
//   //CR 2.2.1 - 6.3.6. Continuous effects that remove a property, or part of a property, from an object do not remove properties, or parts of properties, that were added by another effect.
//   if(SearchCurrentTurnEffects("UPR187", $player)) $cardTalent = "NONE";
//   for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
//   {
//     $toAdd = "";
//     if($currentTurnEffects[$i+1] != $player) continue;
//     switch($currentTurnEffects[$i])
//     {
//       case "UPR060": case "UPR061": case "UPR062": $toAdd = "DRACONIC";
//       default: break;
//     }
//     if($toAdd != "")
//     {
//       if($cardTalent == "NONE") $cardTalent = "";
//       if($cardTalent != "") $cardTalent .= ",";
//       $cardTalent .= $toAdd;
//     }
//   }
//   return $cardTalent;
// }

// function TalentContains($cardID, $talent, $player="")
// {
//   $cardTalent = TalentOverride($cardID, $player);
//   return DelimStringContains($cardTalent, $talent);
// }

//parameters: (comma delimited list of card ids, , )
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
    //AddEvent("REVEAL", $cardArray[$i]);
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
    default: break;
  }
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
  global $turn, $currentPlayer, $mainPlayer, $combatChainState, $CCS_AttackTarget;
  AddLayer("FINALIZECHAINLINK", $mainPlayer, $chainClosed);
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
      case "MULTICHOOSEUNIT": return 0;
      case "MULTICHOOSETHEIRUNIT": return 0;
      case "MULTICHOOSEOURUNITS": return 0;
      case "MULTICHOOSEMYUNITSANDBASE": return 0;
      case "MULTICHOOSETHEIRUNITSANDBASE": return 0;
      case "MULTICHOOSEOURUNITSANDBASE": return 0;
      case "CHOOSEMULTIZONE": return 0;
      case "CHOOSEBANISH": return 0;
      case "BUTTONINPUTNOPASS": return 0;
      case "CHOOSEFIRSTPLAYER": return 0;
      case "MULTICHOOSEDECK": return 0;
      case "CHOOSEPERMANENT": return 0;
      case "MULTICHOOSETEXT": return 0; // Deprecated, use CHOOSEOPTION instead
      case "CHOOSEOPTION": return 0;
      case "CHOOSEMYSOUL": return 0;
      case "OVER": return 0;
      default: return 1;
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
    $deck[] = $cardID;
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
    $target = GetAttackTarget();
    if($target == "NA") return false;
    $targetArr = explode("-", $target);
    return $targetArr[0] == "THEIRALLY";
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

  function AttackerAlly() {
    global $mainPlayer;
    $attackerMZ = AttackerMZID($mainPlayer);
    $ally = new Ally($attackerMZ, $mainPlayer);
    return $ally;
  }

  function AttackerMZID($player)
  {
    global $combatChainState, $CCS_WeaponIndex, $mainPlayer;
    if($player == $mainPlayer) return "MYALLY-" . $combatChainState[$CCS_WeaponIndex];
    else return "THEIRALLY-" . $combatChainState[$CCS_WeaponIndex];
  }

  function ClearAttacker() {
    global $combatChainState, $CCS_WeaponIndex;
    $combatChainState[$CCS_WeaponIndex] = -1;
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

//FAB
// function GetDamagePreventionIndices($player)
// {
//   $rv = "";
//   $auras = &GetAuras($player);
//   $indices = "";
//   for($i=0; $i<count($auras); $i+=AuraPieces())
//   {
//     if(AuraDamagePreventionAmount($player, $i) > 0)
//     {
//       if($indices != "") $indices .= ",";
//       $indices .= $i;
//     }
//   }
//   $mzIndices = SearchMultiZoneFormat($indices, "MYAURAS");

//   $char = &GetPlayerCharacter($player);
//   $indices = "";
//   for($i=0; $i<count($char); $i+=CharacterPieces())
//   {
//     if($char[$i+1] != 0 && WardAmount($char[$i]) > 0)
//     {
//       if($indices != "") $indices .= ",";
//       $indices .= $i;
//     }
//   }
//   $indices = SearchMultiZoneFormat($indices, "MYCHAR");
//   $mzIndices = CombineSearches($mzIndices, $indices);

//   $ally = &GetAllies($player);
//   $indices = "";
//   for($i=0; $i<count($ally); $i+=AllyPieces())
//   {
//     if($ally[$i+1] != 0 && WardAmount($ally[$i]) > 0)
//     {
//       if($indices != "") $indices .= ",";
//       $indices .= $i;
//     }
//   }
//   $indices = SearchMultiZoneFormat($indices, "MYALLY");
//   $mzIndices = CombineSearches($mzIndices, $indices);
//   $rv = $mzIndices;
//   return $rv;
// }

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
  // $theirAuras = SearchMultiZoneFormat(SearchAura($otherPlayer), "THEIRAURAS");//FAB
  // $rv = CombineSearches($rv, $theirAuras);
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

function IgnoreAspectPenalty($cardID, $player, $reportMode) {
  global $myClassState, $CS_NumClonesPlayed, $CS_LayerTarget, $currentTurnEffects;
  if(TraitContains($cardID, "Spectre")) {
    return !LeaderAbilitiesIgnored() && (HeroCard($player) == "7440067052" || SearchAlliesForCard($player, "80df3928eb") != ""); //Hera Syndulla (Spectre Two)
  }
  if (TraitContains($cardID, "Clone")) {
    return (SearchAlliesForCard($player, "1386874723") != "" && GetClassState($player, $CS_NumClonesPlayed) < 1) //Omega (Part of the Squad)
      || (!LeaderAbilitiesIgnored() && (HeroCard($player) == "2742665601" || SearchAlliesForCard($player, "f05184bd91") != "")); //Nala Se (Kaminoan Prime Minister)
  }
  if(TraitContains($cardID, "Lightsaber")) {
    $findGrievous = SearchAlliesForCard($player, "4776553531");//General Grievous  (Trophy Collector)
    return $findGrievous != "" && ($reportMode || $myClassState[$CS_LayerTarget] == "MYALLY-$findGrievous");
  }

  for($i=0;$i<count($currentTurnEffects);$i+=CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i+1] != $player) continue;
    switch($currentTurnEffects[$i]) {
      case "7895170711"://A Fine Addition
        RemoveCurrentTurnEffect($i);
        return true;
      default: break;
    }
  }

  return false;
}

function SelfCostModifier($cardID, $from, $reportMode=false)
{
  global $currentPlayer, $layers;
  global $CS_LastAttack, $CS_LayerTarget, $CS_NumClonesPlayed, $CS_PlayedAsUpgrade, $CS_NumWhenDefeatedPlayed;

  $modifier = 0;
  //Aspect Penalty
  $playerAspects = PlayerAspects($currentPlayer);
  $penalty = 0;
  if(!IgnoreAspectPenalty($cardID, $currentPlayer, $reportMode)) {
    $cardAspects = CardAspects($cardID);
    //Manually changing the aspects of cards played with smuggle that have different aspect requirements for smuggle.
    //Not a great solution; ideally we could define a whole smuggle ability in one place.
    if ($from == "RESOURCES") {
      $tech = SearchAlliesForCard($currentPlayer, "3881257511");
      if($tech != "") {
        $ally = new Ally("MYALLY-" . $tech, $currentPlayer);
        $techOnBoard = !$ally->LostAbilities();
      } else {
        $techOnBoard = false;
      }
      switch($cardID) {
        case "5169472456"://Chewbacca (Pykesbane)
          if(!$techOnBoard || $playerAspects["Aggression"] != 0) {
            //if tech is here and player is not aggression, tech will always be cheaper than aggression cost
            $cardAspects = "Heroism,Aggression";
          }
          break;
        case "9871430123"://Sugi
          //vigilance is always cheaper than vigilance,vigilance, do not use tech passive
          $cardAspects = "Vigilance";
          break;
        case "5874342508"://Hotshot DL-44 Blaster
          if(!$techOnBoard || ($playerAspects["Cunning"] != 0 && $playerAspects["Aggression"] == 0)) {
            //if tech is here, cunning smuggle is better only if player is cunning and not aggression
            $cardAspects = "Cunning";
          }
          break;
        case "4002861992"://DJ (Blatant Thief)
          if(!$techOnBoard) {
            //cunning will always be cheaper than cunning+cunning, do not add a cunning if tech is here
            $cardAspects = "Cunning,Cunning";
          }
          break;
        case "3010720738"://Tobias Beckett
          if(!$techOnBoard || $playerAspects["Vigilance"] != 0) {
            //if tech is here and player is not vigilance, tech will always be cheaper than vigilance cost
            $cardAspects = "Vigilance";
          }
          break;
        default: break;
      }
    }
    if($from == "HAND" && PilotingCost($cardID) > -1) {
      switch($cardID) {
        case "6421006753"://The Mandalorian
          if($reportMode) $cardAspects = "Cunning";
          else $cardAspects = GetClassState($currentPlayer, $CS_PlayedAsUpgrade) == "1" ? "Cunning" : "Cunning,Cunning";
          break;
      }
    }
    if($cardAspects != "") {
      $aspectArr = explode(",", $cardAspects);
      for($i=0; $i<count($aspectArr); ++$i)
      {
        --$playerAspects[$aspectArr[$i]];
        if($playerAspects[$aspectArr[$i]] < 0) {
          //We have determined that the player is liable for an aspect penalty
          //Now we need to determine if they are exempt
          switch($cardID) {
            case "6263178121"://Kylo Ren (Killing the Past)
              if($aspectArr[$i] != "Villainy" || !ControlsNamedCard($currentPlayer, "Rey")) ++$penalty;
              break;
            case "0196346374"://Rey (Keeping the Past)
              if($aspectArr[$i] != "Heroism" || !ControlsNamedCard($currentPlayer, "Kylo Ren")) ++$penalty;
              break;
            default:
              ++$penalty;
              break;
          }
        }
      }
      $modifier += $penalty * 2;
    }
  }
  //Self Cost Modifier
  switch($cardID) {
    case "2585318816"://Resolute
      $modifier -= floor(GetHealth($currentPlayer)/5);
      break;
    case "1446471743"://Force Choke
      if(SearchCount(SearchAllies($currentPlayer, trait:"Force")) > 0) $modifier -= 1;
      break;
    case "7884488904"://For The Republic
      if(SearchCount(SearchAllies($currentPlayer, trait:"Republic")) >= 3) $modifier -= 2;
      break;
    case "4111616117"://Volunteer Soldier
      if(SearchCount(SearchAllies($currentPlayer, trait:"Trooper")) > 0) $modifier -= 1;
      break;
    case "6905327595"://Reputable Hunter
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      $theirAllies = &GetAllies($otherPlayer);
      $hasBounty = false;
      for($i=0; $i<count($theirAllies) && !$hasBounty; $i+=AllyPieces())
      {
        $theirAlly = new Ally("MYALLY-" . $i, $otherPlayer);
        if($theirAlly->HasBounty()) { $hasBounty = true; $modifier -= 1; }
      }
      break;
    case "7212445649"://Bravado
      global $CS_NumAlliesDestroyed;
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      if(GetClassState($otherPlayer, $CS_NumAlliesDestroyed) > 0) $modifier -= 2;
      break;
    case "8380936981"://Jabba's Rancor
      if(ControlsNamedCard($currentPlayer, "Jabba the Hutt")) $modifier -= 1;
      break;
    case "6238512843"://Republic Defense Carrier
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      $modifier -= SearchCount(SearchAllies($otherPlayer));
      break;
    case "2443835595"://Republic Attack Pod
      if(SearchCount(SearchAllies($currentPlayer)) >= 3) $modifier -= 1;
      break;
    //Jump to Lightspeed
    case "1356826899"://Home One
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      if(SearchCount(SearchAllies($otherPlayer, arena:"Space")) >= 3) $modifier -= 3;
      break;
    case "3711891756"://Red Leader
      $modifier -= CountPilotUnitsAndPilotUpgrades($currentPlayer);
      break;
    case "9958088138"://Invincible
      $controlsSeparatist = CountUniqueAlliesOfTrait($currentPlayer, "Separatist") > 0;
      $controlsSeparatist = $controlsSeparatist || SearchCount(SearchCharacter($currentPlayer, trait:"Separatist")) > 0;
      $controlsSeparatist = $controlsSeparatist || SearchUpgrades($currentPlayer, trait:"Separatist", uniqueOnly:true) > 0;
      $modifier -= $controlsSeparatist ? 1 : 0;
    default: break;
  }
  //Target cost modifier
  if(count($layers) > 0) {
    $mzIndex = GetClassState($currentPlayer, $CS_LayerTarget);
    $targetID = GetMZCard($currentPlayer, $mzIndex);
  } else {
    if(SearchAlliesForCard($currentPlayer, "4166047484") != "") $targetID = "4166047484";
    else if(SearchAlliesForCard($currentPlayer, "fb7af4616c") != "") $targetID = "fb7af4616c";
    else if(SearchAlliesForCard($currentPlayer, "4776553531") != "") $targetID = "4776553531";
    else if($cardID == "3141660491") $targetID = "4088c46c4d";
    else $targetID = "";
  }
  if(DefinedTypesContains($cardID, "Upgrade", $currentPlayer)) {
    if($targetID == "4166047484") $modifier -= 1;//Guardian of the Whills
    if($cardID == "0875550518" && ($targetID == "fb7af4616c" || $targetID == "4776553531")) $modifier -= 2;//Grievous's Wheel Bike
    if($cardID == "3141660491" && $targetID != "" && $penalty > 0) {//The Darksaber
      $isMando = TraitContains($targetID, "Mandalorian", $currentPlayer, isset($mzIndex) && $mzIndex != "-" ? explode("-", $mzIndex)[1] : -1);
      if($isMando) {
        $modifier -= $penalty * 2;
      }
    }
  }
  //My ally cost modifier
  $allies = &GetAllies($currentPlayer);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    //Shadows of the Galaxy
    if($allies[$i+1] == 0) continue;
    switch($allies[$i]) {
      case "5035052619"://Jabba the Hutt
        if(DefinedTypesContains($cardID, "Event", $currentPlayer) && TraitContains($cardID, "Trick", $currentPlayer)) $modifier -= 1;
        break;
      //Jump to Lightspeed
      case "649c6a9dbd"://Admiral Piett
        if(TraitContains($cardID, "Capital Ship", $currentPlayer)) $modifier -= 2;
        break;
      case "6311662442"://Director Krennic
        if(GetClassState($currentPlayer, $CS_NumWhenDefeatedPlayed) == 0 && HasWhenDestroyed($cardID)) $modifier -= 1;
        break;
      default: break;
    }
  }
  //Opponent ally cost modifier
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $allies = &GetAllies($otherPlayer);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    if($allies[$i+1] == 0) continue;
    $allyUniqueID = $allies[$i+5];
    switch($allies[$i]) {
      case "9412277544"://Del Meeko
        if(DefinedTypesContains($cardID, "Event", $currentPlayer)) $modifier += 1;
        break;
      case "3503494534"://Regional Governor
        $turnEffect = GetCurrentTurnEffects("3503494534", $currentPlayer, uniqueID:$allyUniqueID);
        if ($turnEffect != null) {
          $cardTitle = GamestateUnsanitize(explode("_", $turnEffect[0])[1]);

          if (CardTitle($cardID) == $cardTitle) {
            $modifier += 999;
          }
        }
        break;
      case "7964782056"://Qi'Ra unit
        $turnEffect = GetCurrentTurnEffects("7964782056", $currentPlayer, uniqueID:$allyUniqueID);
        if ($turnEffect != null) {
          $cardTitle = GamestateUnsanitize(explode("_", $turnEffect[0])[1]);

          if (CardTitle($cardID) == $cardTitle) {
            $modifier += 3;
          }
        }
        break;
      default: break;
    }
  }
  return $modifier;
}

function PlayerAspects($player)
{
  $char = &GetPlayerCharacter($player);
  $aspects = [];
  $aspects["Vigilance"] = 0;
  $aspects["Command"] = 0;
  $aspects["Aggression"] = 0;
  $aspects["Cunning"] = 0;
  $aspects["Heroism"] = 0;
  $aspects["Villainy"] = 0;
  for($i=0; $i<count($char); $i+=CharacterPieces())
  {
    $cardAspects = explode(",", CardAspects($char[$i]));
    for($j=0; $j<count($cardAspects); ++$j) {
      ++$aspects[$cardAspects[$j]];
    }

    // Special case //TODO: look into Twin Suns rules around Flipatine leader
    if ($char[$i] == '0026166404') { //Chancellor Palpatine Leader
      $aspects["Villainy"] = 0;
    } else if ($char[$i] == 'ad86d54e97') { //Darth Sidious Leader
      $aspects["Heroism"] = 0;
    }
  }

  $allies = &GetAllies($player);
  $leaderIndices = explode(",", SearchAllies($player, definedType:"Leader"));
  if($leaderIndices[0] != "") {
    for($i=0; $i<count($leaderIndices); ++$i) {
      $cardAspects = explode(",", CardAspects($allies[$leaderIndices[$i]]));
      for($j=0; $j<count($cardAspects); ++$j) {
        ++$aspects[$cardAspects[$j]];
      }
    }
  }
  //check Leader upgrades for aspects
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    $ally = new Ally("MYALLY-" . $i, $player);
    $allyUpgrades = $ally->GetUpgrades();
    for($j=0; $j<count($allyUpgrades); ++$j)
    {
      if(CardIDIsLeader($allyUpgrades[$j])) {
        $cardAspects = explode(",", CardAspects($allyUpgrades[$j]));
        for($k=0; $k<count($cardAspects); ++$k) {
          ++$aspects[$cardAspects[$k]];
        }
      }
    }
  }
  //check they have a leader upgrade for your aspects
  $otherPlayer = $player == 1 ? 2 : 1;
  $theirAllies = &GetAllies($otherPlayer);
  for($i=0; $i<count($theirAllies); $i+=AllyPieces())
  {
    $ally = new Ally("MYALLY-" . $i, $otherPlayer);
    if($ally->IsUpgraded()) {
      $upgrades = $ally->GetUpgrades(withMetadata:true);
      for($j=0; $j<count($upgrades); $j+=SubcardPieces()) {
        if(CardIDIsLeader($upgrades[$j]) && $upgrades[$j+1 == $player]) {
          $cardAspects = explode(",", CardAspects($upgrades[$j]));
          for($k=0; $k<count($cardAspects); ++$k) {
            ++$aspects[$cardAspects[$k]];
          }
        }
      }
    }
  }

  return $aspects;
}

function IsAlternativeCostPaid($cardID, $from)
{
  global $currentTurnEffects, $currentPlayer;
  $isAlternativeCostPaid = false;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch($currentTurnEffects[$i]) {
        case "9644107128"://Bamboozle
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
  $names[] = CardName($combatChain[0]);
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnEffectPieces())
  {
    $effectArr = explode("-", $currentTurnEffects[$i]);
    $name = CurrentEffectNameModifier($effectArr[0], (count($effectArr) > 1 ? GamestateUnsanitize($effectArr[1]) : "N/A"));
    //You have to do this at the end, or you might have a recursive loop -- e.g. with OUT052
    if($name != "" && $currentTurnEffects[$i+1] == $mainPlayer && IsCombatEffectActive($effectArr[0]) && !IsCombatEffectLimited($i)) $names[] = $name;
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
  if(file_exists("./Games/" . $gameName . "/gamestateBackup.txt")) unlink("./Games/" . $gameName . "/gamestateBackup.txt");
  if(file_exists("./Games/" . $gameName . "/beginTurnGamestate.txt")) unlink("./Games/" . $gameName . "/beginTurnGamestate.txt");
  if(file_exists("./Games/" . $gameName . "/lastTurnGamestate.txt")) unlink("./Games/" . $gameName . "/lastTurnGamestate.txt");
}

//FAB
// function IsClassBonusActive($player, $class)
// {
//   $char = &GetPlayerCharacter($player);
//   if(count($char) == 0) return false;
//   if(ClassContains($char[0], $class, $player)) return true;
//   return false;
// }

function PlayAbility($cardID, $from, $resourcesPaid, $target = "-", $additionalCosts = "-", $theirCard = false, $uniqueId = "")
{
  global $currentPlayer, $layers, $CS_PlayIndex, $CS_OppIndex, $initiativePlayer, $CCS_CantAttackBase, $CS_NumAlliesDestroyed;
  global $CS_NumFighterAttacks, $CS_NumNonTokenVehicleAttacks, $CS_NumFirstOrderPlayed;
  global $CS_PlayedAsUpgrade, $CS_NumUsesLeaderUpgrade1, $CS_NumUsesLeaderUpgrade2;
  global $CS_CachedLeader1EpicAction, $CS_CachedLeader2EpicAction;
  $index = GetClassState($currentPlayer, $CS_PlayIndex);
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  if($from == "PLAY" && IsAlly($cardID, $currentPlayer)) {
    $playAlly = new Ally("MYALLY-" . $index);
    $abilityName = GetResolvedAbilityName($cardID, $from);
    if($abilityName == "Heroic Resolve") {
      $ally = new Ally("MYALLY-" . $index, $currentPlayer);
      $ownerId = $ally->DefeatUpgrade("4085341914");
      AddGraveyard("4085341914", $ownerId, "PLAY");
      AddCurrentTurnEffect("4085341914", $currentPlayer, "PLAY", $ally->UniqueID());
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYALLY-" . $index);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK");
      return "";
    } else if($abilityName == "Strategic Acumen") {
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "2397845395", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      return "";
    } else if ($abilityName == "Mill") { //Satine Kryze
      $ally = new Ally("MYALLY-" . $index, $currentPlayer);
      Mill($otherPlayer, ceil($ally->Health()/2));
      return "";
    } else if ($abilityName == "Poe Pilot") {
      DecrementClassState($currentPlayer, $CS_NumUsesLeaderUpgrade1, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "3eb545eb4b", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, ($theirCard ? "THEIRALLY-" : "MYALLY-") . $index, 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "1", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "2", 1);//set movingPilot to true
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "hasPilot=1", 1);
      AddDecisionQueue("PASSREVERT", $currentPlayer, "-");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to move <0> to.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "MOVEUPGRADE", 1);
    }
  }
  if($target != "-")
  {
    $targetArr = explode("-", $target);
    if($targetArr[0] == "LAYERUID") { $targetArr[0] = "LAYER"; $targetArr[1] = SearchLayersForUniqueID($targetArr[1]); }
    $target = count($targetArr) > 1 ? $targetArr[0] . "-" . $targetArr[1] : "-";
  }
  if ($from != "PLAY" && IsAlly($cardID, $currentPlayer)) {
    //LastAllyIndex does not work well when you play multiple unit on same times (Vader, U-Wing, Endless Legion ...)
     if ($uniqueId != "") {
       $playAlly = new Ally($uniqueId, $currentPlayer);
     } else {
       $playAlly = new Ally("MYALLY-" . LastAllyIndex($currentPlayer));
     }
  }

  if($from == "EQUIP" && DefinedTypesContains($cardID, "Leader", $currentPlayer)) {
    $abilityName = GetResolvedAbilityName($cardID, $from);
    if($abilityName == "Deploy" || $abilityName == "Pilot" || $abilityName == "") {
      $notEnoughResources = NumResources($currentPlayer) < CardCost($cardID);
      if($cardID == "8520821318") {//Poe Dameron JTL leader
        $notEnoughResources = $abilityName == "Deploy" && NumResources($currentPlayer) < 5;
      }
      if($notEnoughResources) {
        WriteLog("You don't control enough resources to deploy that leader; reverting the game state.");
        RevertGamestate();
        return "";
      }
      if($abilityName == "Deploy" || $abilityName == "") {
        $epicAction = $cardID != "3905028200" ? 1 : 0;//Admiral Trench leader (so far the only one)
        $playUniqueID = PlayAlly(LeaderUnit($cardID), $currentPlayer, epicAction:$epicAction);
        if (HasShielded(LeaderUnit($cardID), $currentPlayer)) {
          AddLayer("TRIGGER", $currentPlayer, "SHIELDED", "-", "-", $playUniqueID);
        }
        PlayAbility(LeaderUnit($cardID), "CHAR", 0, "-", "-", false, $uniqueId);
      }
      else if($cardID == "8520821318" && $abilityName == "Pilot") {//Poe Dameron JTL leader
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle");
        AddDecisionQueue("MZFILTER", $currentPlayer, "hasPilot=1");
        AddDecisionQueue("PASSREVERT", $currentPlayer, "-");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attach <0>");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SHOWSELECTEDTARGET", $currentPlayer, "-", 1);
        AddDecisionQueue("DEPLOYLEADERASUPGRADE", $currentPlayer, $cardID, 1);
      }
      else if($abilityName == "Pilot") {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle;canAddPilot=1");
        AddDecisionQueue("PASSREVERT", $currentPlayer, "-");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attach <0>");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SHOWSELECTEDTARGET", $currentPlayer, "-", 1);
        AddDecisionQueue("DEPLOYLEADERASUPGRADE", $currentPlayer, $cardID, 1);
      }
      //On Deploy ability / When Deployed ability
      if(!LeaderAbilitiesIgnored()) {
        switch($cardID) {
          case "5784497124"://Emperor Palpatine Leader flip
            AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:damagedOnly=true");
            AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
            AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a damaged unit to take control of", 1);
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
            AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
            AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
            break;
          case "2432897157"://Qi'Ra Leader flip
            $myAllies = &GetAllies($currentPlayer);
            for($i=0; $i<count($myAllies); $i+=AllyPieces())
            {
              $ally = new Ally("MYALLY-" . $i, $currentPlayer);
              $ally->Heal(9999);
              $ally->DealDamage(floor($ally->MaxHealth()/2));
            }
            $theirAllies = &GetAllies($otherPlayer);
            for($i=0; $i<count($theirAllies); $i+=AllyPieces())
            {
              $ally = new Ally("MYALLY-" . $i, $otherPlayer);
              $ally->Heal(9999);
              $ally->DealDamage(floor($ally->MaxHealth()/2));
            }
            break;
          case "0254929700"://Doctor Aphra Leader flip
            AddDecisionQueue("FINDINDICES", $currentPlayer, "GY");
            AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
            AddDecisionQueue("APPENDLASTRESULT", $currentPlayer, "-3", 1);
            AddDecisionQueue("MULTICHOOSEDISCARD", $currentPlayer, "<-", 1);
            AddDecisionQueue("SPECIFICCARD", $currentPlayer, "DOCTORAPHRA", 1);
            break;
          case "0622803599"://Jabba the Hutt Leader flip
            $jabbaMzIndex = explode(",", "MYALLY-" . SearchAlliesForCard($currentPlayer, "f928681d36"));//Jabba the Hutt leader unit
            $allyMzIndices = explode(",", SearchMultizone($currentPlayer, "MYALLY"));
            $alliesNotJabba = implode(",", array_diff($allyMzIndices, $jabbaMzIndex));
            if($alliesNotJabba != "") {
              AddDecisionQueue("PASSPARAMETER", $currentPlayer, $alliesNotJabba);
              AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture another unit");
              AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
              AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
              AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
              AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
              AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
              AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture", 1);
              AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
              AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE,{0}", 1);
            }
            break;
          case "4628885755"://Mace Windu Leader flip
            $theirAllies = &GetAllies($otherPlayer);
            for($i=count($theirAllies)-AllyPieces(); $i>=0; $i-=AllyPieces())
            {
              $ally = new Ally("MYALLY-" . $i, $otherPlayer);
              if($ally->IsDamaged()) {
                $ally->DealDamage(2);
              }
            }
            break;
          case "7734824762"://Captain Rex Leader flip
            CreateCloneTrooper($currentPlayer);
            break;
          case "2847868671"://Yoda Leader flip
          $deck = &GetDeck($currentPlayer);
          if(count($deck) > 0) {
            AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose if you want to discard a card to Yoda");
            AddDecisionQueue("YESNO", $currentPlayer, "-");
            AddDecisionQueue("NOPASS", $currentPlayer, "-");
            AddDecisionQueue("PASSPARAMETER", $currentPlayer, "1", 1);
            AddDecisionQueue("OP", $currentPlayer, "MILL", 1);
            AddDecisionQueue("MZOP", $currentPlayer, "GETCARDCOST", 1);
            AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
            AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxCost={0}", 1);
            AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
            AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to destroy");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
            AddDecisionQueue("MZOP", $currentPlayer, "DESTROY", 1);
          }
          break;
          case "3905028200"://Admiral Trench flip
            $deck = new Deck($currentPlayer);
            $cardsToReveal = min($deck->RemainingCards(), 4);
            $deck->Reveal($cardsToReveal);
            $cards = $deck->Top(remove:true, amount:$cardsToReveal);
            $cardArr = explode(",", $cards);
            if($cards != "") {
              AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cards, 1);
              AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
              AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Discard 2 cards.", 1);
              AddDecisionQueue("CHOOSECARD", $otherPlayer, "{0}", 1);//if there's one card, forced to choose
              AddDecisionQueue("SPECIFICCARD", $currentPlayer, "TRENCH_JTL_OPP", 1);
              AddDecisionQueue("MAYCHOOSECARD", $otherPlayer, "{0}", 1);//this is a may to account for edge cases where <4 cards left
              AddDecisionQueue("SPECIFICCARD", $currentPlayer, "TRENCH_JTL_OPP", 1);
              AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose card to draw.", 1);
              AddDecisionQueue("MAYCHOOSECARD", $currentPlayer, "{0}", 1);
              AddDecisionQueue("SPECIFICCARD", $currentPlayer, "TRENCH_JTL", 1);
            }
            break;
          default: break;
        }
      }
      RemoveCharacter($currentPlayer, CharacterPieces());
      if($epicAction == 1) SetClassState($currentPlayer, $CS_CachedLeader1EpicAction, $epicAction);
      //Base deploy ability
      $char = &GetPlayerCharacter($currentPlayer);
      $baseID = $char[0];
      switch($baseID) {
        case "8589863038"://Droid Manufactory
          CreateBattleDroid($currentPlayer);
          CreateBattleDroid($currentPlayer);
          WriteLog("Droid Manufactory deployed two Battle Droids.");
          break;
        case "6854189262"://Shadow Collective Camp
          Draw($currentPlayer);
          WriteLog("Shadow Collective Camp drew a card.");
          break;
        default: break;
      }
      //Ally when leader deployed effects
      $allies = &GetAllies($currentPlayer);
      for($i=0; $i<count($allies); $i+=AllyPieces())
      {
        $ally = new Ally("MYALLY-" . $i, $currentPlayer);
        switch($ally->CardID()) {
          //Jump to Lightspeed
          case "9958088138"://Invincible
            AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
            AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
            AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a non-leader unit that costs 3 or less to bounce");
            AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
            AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
            break;
          default: break;
        }
      }
      return CardLink($cardID, $cardID) . " was deployed.";
    }
  }
  switch($cardID)
  {
    case "8839068683"://Freelance Assassin
      if(GetResources($currentPlayer) >= 2) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Pay 2 resources to deal 2 damage to a unit?", 1);
        AddDecisionQueue("YESNO", $currentPlayer, "-", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("PAYRESOURCES", $currentPlayer, "2", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "4569767827"://Execute Order 66
      for ($p = 1; $p <= 2; $p++) {
        $jediUniqueIDs = explode(",", SearchAlliesUniqueIDForTrait($p, "Jedi"));

        foreach ($jediUniqueIDs as $jediUniqueID) {
          $ally = new Ally($jediUniqueID, $p);
          $enemyDamage = $p != $currentPlayer;
          $destroyed = $ally->DealDamage(6, enemyDamage:$enemyDamage);

          if ($destroyed) {
            CreateCloneTrooper($p);
          }
        }
      }
      break;
    case "5013139687"://Caught In The Crossfire
      if ($target != "-") {
        $ally = new Ally($target);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=" . CardArenas($ally->CardID()));
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=" . $target);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose another unit");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-");
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "CAUGHTINTHECROSSFIRE", 1);
      }
      break;
    case "7895170711"://A Fine Addition
      if(GetClassState($otherPlayer, $CS_NumAlliesDestroyed) > 0) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose where to play an upgrade from");
        AddDecisionQueue("BUTTONINPUT", $currentPlayer, "My Hand,My Discard,Opponent Discard", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "AFINEADDITION", 1);
      }
      break;
    case "0345124206"://Clone
      $mzIndex = "MYALLY-" . $playAlly->Index();
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("MZFILTER", $currentPlayer, "index=" . $mzIndex);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose which unit you want to clone", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      $playAbility = $from != "CAPTIVE" ? "true" : "false";
      AddDecisionQueue("PLAYALLY", $currentPlayer, "cloned=true;from=" . $from . ";playAbility=" . $playAbility, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $mzIndex, 1);
      AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      break;
    case "4721628683"://Patrolling V-Wing
      if($from != "PLAY") Draw($currentPlayer);
      break;
    case "2050990622"://Spark of Rebellion
      AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRHAND");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose which card you want your opponent to discard", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDISCARD", $currentPlayer, "HAND," . $currentPlayer, 1);
      AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      break;
    case "3377409249"://Rogue Squadron Skirmisher
      if($from != "PLAY") MZMoveCard($currentPlayer, "MYDISCARD:maxCost=2;definedType=Unit", "MYHAND", may:true);
      break;
    case "5335160564"://Guerilla Attack Pod
      if($from != "PLAY" && (GetHealth(1) >= 15 || GetHealth(2) >= 15)) {
        $playAlly->Ready();
      }
      break;
    case "7262314209"://Mission Briefing
      $player = $additionalCosts == "Yourself" ? $currentPlayer : $otherPlayer;
      Draw($player);
      Draw($player);
      break;
    case "6253392993"://Bright Hope
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to bounce");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    case "6702266551"://Smoke and Cinders
      $hand = &GetHand(1);
      for($i=0; $i<(count($hand)/HandPieces())-2; ++$i) PummelHit(1);
      $hand = &GetHand(2);
      for($i=0; $i<(count($hand)/HandPieces())-2; ++$i) PummelHit(2);
      break;
    case "8148673131"://Open Fire
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 4 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,4,$currentPlayer", 1);
      break;
    case "8429598559"://Black One
      if($from != "PLAY") BlackOne($currentPlayer);
      break;
    case "8986035098"://Viper Probe Droid
      if($from != "PLAY") {
        AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
        AddDecisionQueue("LOOKHAND", $currentPlayer, "-");
      }
      break;
    case "9266336818"://Grand Moff Tarkin
      if($from != "PLAY") {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;2;include-trait-Imperial");
        AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      }
      break;
    case "9459170449"://Cargo Juggernaut
      if($from != "PLAY" && SearchCount(SearchAllies($currentPlayer, aspect:"Vigilance")) > 1) {
        Restore(4, $currentPlayer);
      }
      break;
    case "7257556541"://Bodhi Rook
      if($from != "PLAY") {
        AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRHAND");
        AddDecisionQueue("MZFILTER", $currentPlayer, "definedType=Unit");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to discard");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      }
      break;
    case "6028207223"://Pirated Starfighter
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      }
      break;
    case "8981523525"://Moment of Peace
      if($target != "-") {
        $ally = new Ally($target);
        $ally->Attach("8752877738", $currentPlayer);//Shield
      }
      break;
    case "8679831560"://Repair
      $mzArr = explode("-", $target);
      if($mzArr[0] == "MYCHAR") Restore(3, $currentPlayer);
      else if($mzArr[0] == "MYALLY") {
        $ally = new Ally($target);
        $ally->Heal(3);
      }
      break;
    case "7533529264"://Wolffe
      AddCurrentTurnEffect($cardID, $currentPlayer);
      AddCurrentTurnEffect($cardID, $otherPlayer);
      break;
    case "7596515127"://Academy Walker
      if($from != "PLAY") {
        $allies = &GetAllies($currentPlayer);
        for($i=0; $i<count($allies); $i+=AllyPieces()) {
          $ally = new Ally("MYALLY-" . $i);
          if($ally->IsDamaged()) $ally->Attach("2007868442");//Experience token
        }
      }
      break;
    case "7235023816"://Guerilla Insurgency
      MZChooseAndDestroy($currentPlayer, "MYRESOURCES", context:"Choose a resource to destroy");
      MZChooseAndDestroy($otherPlayer, "MYRESOURCES", context:"Choose a resource to destroy");
      PummelHit($currentPlayer);
      PummelHit($currentPlayer);
      PummelHit($otherPlayer);
      PummelHit($otherPlayer);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "GUERILLAINSURGENCY");
      break;
    case "7202133736"://Waylay
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to return to hand");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "5283722046"://Spare the Target
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to return to hand");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "COLLECTBOUNTIES", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "7485151088"://Search your feelings
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDECK");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("SHUFFLEDECK", $currentPlayer, "-");
      break;
    case "0176921487"://Power of the Dark Side
      MZChooseAndDestroy($otherPlayer, "MYALLY");
      break;
    case "0827076106"://Admiral Ackbar
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to damage");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ADMIRALACKBAR", 1);
      }
      break;
    case "0867878280"://It Binds All Things
      $ally = new Ally($target);
      $amountHealed = $ally->Heal(3);
      if(SearchCount(SearchAllies($currentPlayer, trait:"Force")) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $amountHealed . " damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$amountHealed,$currentPlayer", 1);
      }
      break;
    case "1021495802"://Cantina Bouncer
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY&MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      }
      break;
    case "1353201082"://Superlaser Blast
      DestroyAllAllies();
      break;
    case "1705806419"://Force Throw
      if($additionalCosts == "Yourself") PummelHit($currentPlayer);
      else PummelHit($otherPlayer);
      if(SearchCount(SearchAllies($currentPlayer, trait:"Force")) > 0) {
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "FORCETHROW", 1);
      }
      break;
    case "1746195484"://Jedha Agitator
      if($from == "PLAY" && HasLeader($currentPlayer)){
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRCHAR:definedType=Base&MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose something to deal 2 damage", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "2587711125"://Disarm
      $ally = new Ally($target);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $ally->UniqueID());
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $ally->PlayerID(), "2587711125,HAND");
      break;
    case "5707383130"://Bendu
      if($from == "PLAY") {
        AddCurrentTurnEffect($cardID, $currentPlayer);
      }
      break;
    case "6472095064"://Vanquish
      MZChooseAndDestroy($currentPlayer, "MYALLY&THEIRALLY", filter:"leader=1");
      break;
    case "6663619377"://AT-AT Suppressor
      if($from != "PLAY"){
        ExhaustAllAllies("Ground", 1);
        ExhaustAllAllies("Ground", 2);
      }
      break;
    case "6931439330"://The Ghost
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Spectre");
      AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give a shield");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      break;
    case "8691800148"://Reinforcement Walker
      AddDecisionQueue("FINDINDICES", $currentPlayer, "TOPDECK");
      AddDecisionQueue("DECKCARDS", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose if you want to draw <0>", 1);
      AddDecisionQueue("YESNO", $currentPlayer, "-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "REINFORCEMENTWALKER", 1);
      break;
    case "9002021213"://Imperial Interceptor
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Space unit to deal 3 damage to");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space&THEIRALLY:arena=Space");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,3,$currentPlayer,1", 1);
      }
      break;
    case "9133080458"://Inferno Four
      if($from != "PLAY") PlayerOpt($currentPlayer, 2);
      break;
    case "9568000754"://R2-D2
      PlayerOpt($currentPlayer, 1);
      break;
    case "9624333142"://Count Dooku (Darth Tyranus)
      if($from != "PLAY") {
        MZChooseAndDestroy($currentPlayer, "MYALLY:maxHealth=4&THEIRALLY:maxHealth=4", may:true, filter:"index=MYALLY-" . $playAlly->Index());
      }
      break;
    case "9097316363"://Emperor Palpatine (Master of the Dark Side)
      if($from != "PLAY") {
        AddDecisionQueue("FINDINDICES", $currentPlayer, "ALLTHEIRUNITSMULTI");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose units to damage", 1);
        AddDecisionQueue("MULTICHOOSETHEIRUNIT", $currentPlayer, "<-", 1);
        AddDecisionQueue("MULTIDISTRIBUTEDAMAGE", $currentPlayer, "6,1", 1);
      }
      break;
    case "1208707254"://Rallying Cry
      AddCurrentTurnEffect($cardID, $currentPlayer);
      break;
    case "1446471743"://Force Choke
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 5 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "FORCECHOKE", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,5,$currentPlayer", 1);
      break;
    case "1047592361"://Ruthless Raider
      if($from != "PLAY") {
        DealDamageAsync($otherPlayer, 2, "DAMAGE", "1047592361");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "1862616109"://Snowspeeder
      if($from == "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground;trait=Vehicle");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
    case "2554951775"://Bail Organa
      if($from == "PLAY" && GetResolvedAbilityType($cardID) == "A") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $index);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to add an experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "3058784025"://Keep Fighting
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=3&THEIRALLY:maxAttack=3");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to ready");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      break;
    case "3613174521"://Outer Rim Headhunter
      if($from == "PLAY" && HasLeader($currentPlayer)) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
    case "3684950815"://Bounty Hunter Crew
      if($from != "PLAY") MZMoveCard($currentPlayer, "MYDISCARD:definedType=Event", "MYHAND", may:true, context:"Choose an event to return with " . CardLink("3684950815", "3684950815"));
      break;
    case "4092697474"://TIE Advanced
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Imperial");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "4536594859"://Medal Ceremony
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Rebel");
      AddDecisionQueue("MZFILTER", $currentPlayer, "numAttacks=0");
      AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 3 units that have attacked to give experience", 1);
      AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1, 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "MEDALCEREMONY");
      break;
    case "6515891401"://Karabast
      $ally = new Ally($target);
      $damage = $ally->Damage() + 1;
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $damage . " damage to");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$damage,$currentPlayer", 1);
      break;
    case "2359136621"://Guarding The Way
      $hasInitiative = $initiativePlayer == $currentPlayer;
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give Sentinel");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      if ($hasInitiative) {
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
      }
      AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2359136621_" . ($hasInitiative ? "2" : "0") . ",PLAY", 1);
      break;
    case "8022262805"://Bold Resistance
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 3 units that share the same trait", 1);
      AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "BOLDRESISTANCE", 1);
      break;
    case "7929181061"://General Tagge
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Trooper");
        AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES");
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 3 troopers to give experience");
        AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "MULTIGIVEEXPERIENCE", 1);
      }
      break;
    case "8240629990"://Avenger
      if(!$playAlly->LostAbilities()) {
        MZChooseAndDestroy($otherPlayer, "MYALLY", filter: "leader=1", context: "Choose a unit to destroy");
      }
      break;
    case "8294130780"://Gladiator Star Destroyer
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give Sentinel");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "8294130780,HAND", 1);
      }
      break;
    case "4919000710"://Home One
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:definedType=Unit;aspect=Heroism");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1); //Technically as written the trigger is not optional, but coding to get around the case where the only options are too expensive to play(which makes Home One unplayable because trying to play off the ability reverts the gamestate) doesn't seem worth it to cover the vanishingly rare case where a player should be forced to play something despite preferring not to.
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "4849184191"://Takedown
      MZChooseAndDestroy($currentPlayer, "MYALLY:maxHealth=5&THEIRALLY:maxHealth=5");
      break;
    case "4631297392"://Devastator
      if($from != "PLAY") {
        $resourceCards = &GetResourceCards($currentPlayer);
        $numResources = count($resourceCards)/ResourcePieces();
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $numResources . " damage");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$numResources,$currentPlayer,1", 1);
      }
      break;
    case "4599464590"://Rugged Survivors
      if($from == "PLAY" && HasLeader($currentPlayer)) {
        Draw($currentPlayer);
      }
      break;
    case "4299027717"://Mining Guild Tie Fighter
      if($from == "PLAY" && NumResourcesAvailable($currentPlayer) >= 2) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Do you want to pay 2 to draw a card?");
        AddDecisionQueue("YESNO", $currentPlayer, "-");
        AddDecisionQueue("NOPASS", $currentPlayer, "", 1);
        AddDecisionQueue("PAYRESOURCES", $currentPlayer, "2,1", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    case "3802299538"://Cartel Spacer
      if($from != "PLAY" && SearchCount(SearchAllies($currentPlayer, aspect:"Cunning")) > 1) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxCost=4");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
    case "3443737404"://Wing Leader
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Rebel");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to add experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "2756312994"://Alliance Dispatcher
      if($from == "PLAY" && GetResolvedAbilityType($cardID) == "A") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "2569134232"://Jedha City
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2569134232,HAND");
      break;
    case "1349057156"://Strike True
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal damage equal to it's power");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "POWER", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "DEALDAMAGE,", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to damage");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "{0},$currentPlayer,1", 1);
      break;
    case "1393827469"://Tarkintown
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 3 damage to");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:damagedOnly=true&THEIRALLY:damagedOnly=true");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,3,$currentPlayer", 1);
      break;
    case "1880931426"://Lothal Insurgent
      global $CS_NumCardsPlayed;
      if($from != "PLAY" && GetClassState($currentPlayer, $CS_NumCardsPlayed) > 1) {
        Draw($otherPlayer);
        DiscardRandom($otherPlayer, $cardID);
      }
      break;
    case "2429341052"://Security Complex
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give a shield");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      break;
    case "3018017739"://Vanguard Ace
      global $CS_NumCardsPlayed;
      if($from != "PLAY") {
        for($i=0; $i<(GetClassState($currentPlayer, $CS_NumCardsPlayed)-1); ++$i) {
          $playAlly->Attach("2007868442");//Experience token
        }
      }
      break;
    case "3401690666"://Relentless
      if($from != "PLAY") {
        global $CS_NumEventsPlayed;
        if(GetClassState($otherPlayer, $CS_NumEventsPlayed) == 0) {
          AddCurrentTurnEffect("3401690666", $otherPlayer, from:"PLAY");
        }
      }
      break;
    case "3407775126"://Recruit
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;1;include-definedType-Unit");
      AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      break;
    case "3498814896"://Mon Mothma
      if($from != "PLAY") {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;1;include-trait-Rebel");
        AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      }
      break;
    case "3509161777"://You're My Only Hope
      $deck = new Deck($currentPlayer);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $deck->Top());
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Do you want to play <0>?");
      AddDecisionQueue("YESNO", $currentPlayer, "-");
      AddDecisionQueue("NOPASS", $currentPlayer, "-");
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "3509161777", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYDECK-0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "3572356139"://Chewbacca (Walking Carpet)
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play Taunt") {
        global $CS_AfterPlayedBy;
        SetClassState($currentPlayer, $CS_AfterPlayedBy, $cardID);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;maxCost=3");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "2579145458"://Luke Skywalker
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Give Shield") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:aspect=Heroism");
        AddDecisionQueue("MZFILTER", $currentPlayer, "turns=>0");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "2912358777"://Grand Moff Tarkin
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Give Experience") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Imperial");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "3187874229"://Cassian Andor
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Draw Card") {
        global $CS_DamageTaken;
        $otherPlayer = $currentPlayer == 1 ? 2 : 1;
        if(GetClassState($otherPlayer, $CS_DamageTaken) >= 3) Draw($currentPlayer);
      }
      break;
    case "4841169874"://Sabine Wren
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage") {
        DealDamageAsync(1, 1, "DAMAGE", $cardID);
        DealDamageAsync(2, 1, "DAMAGE", $cardID);
      }
      break;
    case "5871074103"://Forced Surrender
      Draw($currentPlayer);
      Draw($currentPlayer);
      global $CS_DamageTaken;
      if(GetClassState($otherPlayer, $CS_DamageTaken) > 0) {
        PummelHit($otherPlayer);
        PummelHit($otherPlayer);
      }
      break;
    case "9250443409"://Lando Calrissian
      if($from != "PLAY") {
        for($i=0; $i<2; ++$i) {
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to two resource cards to return to your hand");
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYRESOURCES");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
        }
      }
      break;
    case "9070397522"://SpecForce Soldier
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to lose sentinel");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9070397522,HAND", 1);
      }
      break;
    case "6458912354"://Death Trooper
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "7109944284"://Luke Skywalker unit
      global $CS_NumAlliesDestroyed;
      if($from != "PLAY") {
        $amount = GetClassState($currentPlayer, $CS_NumAlliesDestroyed) > 0 ? 6 : 3;
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to debuff");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "7109944284-" . $amount . ",HAND", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REDUCEHEALTH," . $amount, 1);
      }
      break;
    case "7366340487"://Outmaneuver
      $options = "Space;Ground";
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an arena");
      AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("MODAL", $currentPlayer, "OUTMANEUVER");
      break;
    case "6901817734"://Asteroid Sanctuary
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give a shield token");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      break;
    case "0705773109"://Vader's Lightsaber
      if(CardTitle(GetMZCard($currentPlayer, $target)) == "Darth Vader") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 4 damage to");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,4,$currentPlayer", 1);
      }
      break;
    case "2048866729"://Iden Versio
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Heal") {
        global $CS_NumAlliesDestroyed;
        if(GetClassState($otherPlayer, $CS_NumAlliesDestroyed) > 0) {
          Restore(1, $currentPlayer);
        }
      }
      break;
    case "9680213078"://Leia Organa
      if($from != "PLAY") {
        $options = "Ready a resource;Exhaust a unit";
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose one");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
        AddDecisionQueue("MODAL", $currentPlayer, "LEIAORGANA");
      }
      break;
    case "7916724925"://Bombing Run
      $options = "Space;Ground";
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an arena to deal 3 damage to each unit");
      AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("MODAL", $currentPlayer, "BOMBINGRUN");
      break;
    case "6088773439"://Darth Vader
      global $CS_NumVillainyPlayed;
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage" && GetClassState($currentPlayer, $CS_NumVillainyPlayed) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
        DealDamageAsync($otherPlayer, 1, "DAMAGE", "6088773439");
      }
      break;
    case "3503494534"://Regional Governor
      if($from != "PLAY") {
        AddDecisionQueue("INPUTCARDNAME", $currentPlayer, "<-");
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "3503494534_{0},HAND," . $otherPlayer, 1);
      }
      break;
    case "0523973552"://I Am Your Father
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 7 damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Do you want your opponent to deal 7 damage to <1>?");
      AddDecisionQueue("YESNO", $otherPlayer, "-");
      AddDecisionQueue("NOPASS", $otherPlayer, "-", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,7,$currentPlayer", 1);
      AddDecisionQueue("ELSE", $otherPlayer, "-");
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      break;
    case "6903722220"://Luke's Lightsaber
      if(CardTitle(GetMZCard($currentPlayer, $target)) == "Luke Skywalker") {
        $ally = new Ally($target, $currentPlayer);
        $ally->Heal($ally->MaxHealth()-$ally->Health());
        $ally->Attach("8752877738");//Shield Token
      }
      break;
    case "5494760041"://Galactic Ambition
      global $CS_AfterPlayedBy;
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to play");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND");
      AddDecisionQueue("MZFILTER", $currentPlayer, "definedType!=Unit", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "aspect=Heroism", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "5494760041", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 1, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "2651321164"://Tactical Advantage
      $ally = new Ally($target);
      $ally->AddRoundHealthModifier(2);
      AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $ally->UniqueID());
      break;
    case "1701265931"://Moment of Glory
      $ally = new Ally($target);
      $ally->AddRoundHealthModifier(4);
      AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $ally->UniqueID());
      break;
    case "1900571801"://Overwhelming Barrage
      if ($target != "-") {
        include_once "Libraries/MZOpHelpers.php";
        $ally = new Ally($target);
        $ally->AddRoundHealthModifier(2);
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY", $ally->UniqueID());
        AddDecisionQueue("FINDINDICES", $currentPlayer, "ALLOURUNITSMULTI");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose units to damage", 1);
        AddDecisionQueue("MULTICHOOSEOURUNITS", $currentPlayer, "<-", 1);
        AddDecisionQueue("MULTIDISTRIBUTEDAMAGE", $currentPlayer,
          MultiDistributeDamageStringBuilder($ally->CurrentPower(),$currentPlayer,isUnitEffect:"1",zones:"OURALLIES"), 1);
      }
      break;
    case "3974134277"://Prepare for Takeoff
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "8;2;include-trait-Vehicle&include-definedType-Unit");
      AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      break;
    case "3896582249"://Redemption
      if($from != "PLAY") {
        for($i=0; $i<8; ++$i) {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", $i == 0 ? 0 : 1);
          AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "MYCHAR-0,THEIRCHAR-0,", $i == 0 ? 0 : 1);
          AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
          AddDecisionQueue("MZFILTER", $currentPlayer, "damaged=0");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to restore 1 (Remaining: " . (8-$i) . ")", $i == 0 ? 0 : 1);
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,1", 1);
          AddDecisionQueue("UNIQUETOMZ", $currentPlayer, $playAlly->UniqueID(), 1);
          AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
        }
      }
      break;
    case "7861932582"://The Force is With Me
      $ally = new Ally($target, $currentPlayer);
      $ally->Attach("2007868442");//Experience token
      $ally->Attach("2007868442");//Experience token
      if(SearchCount(SearchAllies($currentPlayer, trait:"Force")) > 0) {
        $ally->Attach("8752877738");//Shield Token
      }
      if(!$ally->IsExhausted()) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Do you want to attack with the unit?");
        AddDecisionQueue("YESNO", $currentPlayer, "-");
        AddDecisionQueue("NOPASS", $currentPlayer, "-");
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "9985638644"://Snapshot Reflexes
      $mzArr = explode("-", $target);
      if($mzArr[0] == "MYALLY") {
        $ally = new Ally($target);
        if(!$ally->IsExhausted()) {
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
          AddDecisionQueue("MZOP", $currentPlayer, "ATTACK");
        }
      }
      break;
    case "7728042035"://Chimaera
      if($from == "PLAY") {
        AddDecisionQueue("INPUTCARDNAME", $currentPlayer, "<-");
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRHAND:cardTitle={0}", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to discard", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      }
      break;
    case "3809048641"://Surprise Strike
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give +3");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "3809048641,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "3038238423"://Fleet Lieutenant
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "FLEETLIEUTENANT", 1);
      }
      break;
    case "3208391441"://Make an Opening
      Restore(2, $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give -2/-2", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "3208391441,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REDUCEHEALTH,2", 1);
      break;
    case "4036958275"://Hello There
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "turns=>0");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give -4/-4", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "4036958275,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REDUCEHEALTH,4", 1);
      break;
    case "5013214638"://Equalize
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give -2/-2", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 1, 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5013214638,PLAY", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REDUCEHEALTH,2", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "EQUALIZE", 1);
      break;
    case "2758597010"://Maximum Firepower
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      for($i=0; $i<2; ++$i) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Imperial");
        AddDecisionQueue("MZFILTER", $currentPlayer, "dqVar=0", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to deal damage", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, 0, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "POWER", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, 1, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,{1},$currentPlayer,1", 1);
      }
      break;
    case "4263394087"://Chirrut Imwe
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Buff HP") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give +2 hp");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "4263394087,HAND", 1);
      }
      break;
    case "5154172446"://ISB Agent
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to reveal");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Event");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer,1", 1);
      }
      break;
    case "4300219753"://Fett's Firespray
      if($from != "PLAY") {
        $ready = false;
        if(ControlsNamedCard($currentPlayer, "Boba Fett") || ControlsNamedCard($currentPlayer, "Jango Fett")) $ready = true;
        if($ready) {
          $playAlly->Ready();
        }
      } else {
        $abilityName = GetResolvedAbilityName($cardID, $from);
        if($abilityName == "Exhaust") {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
          AddDecisionQueue("MZFILTER", $currentPlayer, "unique=1");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
        }
      }
      break;
    case "0595607848"://Disaffected Senator
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if ($abilityName == "Deal Damage") {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYCHAR-0,THEIRCHAR-0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a base to deal 2 damage");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "8009713136"://C-3PO
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a number");
      AddDecisionQueue("BUTTONINPUTNOPASS", $currentPlayer, "0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20");
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "C3PO", 1);
      break;
    case "7911083239"://Grand Inquisitor
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage and ready");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=3");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      }
      break;
    case "5954056864"://Han Solo
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play Resource") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to resource");
        MZMoveCard($currentPlayer, "MYHAND", "MYRESOURCES", may:false, silent:true);
        AddNextTurnEffect($cardID, $currentPlayer);
      }
      break;
    case "5630404651"://MagnaGuard Wing Leader
      $ally = new Ally("MYALLY-" . $index);
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if ($abilityName == "Droid Attack") {
        if ($ally->NumUses() > 0) {
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYALLY-" . $index);
          AddDecisionQueue("ADDMZUSES", $currentPlayer, "-1");
          AddCurrentTurnEffect($cardID . "-1", $currentPlayer);
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Droid");
          AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a droid to attack with", 1);
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
        } else {
          WriteLog("<span style='color: red;'>You can use this ability only once each round. Reverting gamestate.</span>");
          RevertGamestate();
        }
      }
      break;
    case "6514927936"://Leia Organa Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Attack") {
        AddCurrentTurnEffect($cardID . "-1", $currentPlayer);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Rebel");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "8055390529"://Traitorous
      $mzArr = explode("-", $target);
      $ally = new Ally($target);
      if($mzArr[0] == "THEIRALLY" && !$ally->IsLeader()) {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID");
        AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL");
      }
      break;
    case "8244682354"://Jyn Erso
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Attack") {
        AddCurrentTurnEffect($cardID, $otherPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "8327910265"://Energy Conversion Lab (ECL)
      AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;maxCost=6");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "8600121285"://IG-88
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Attack") {
        if(HasMoreUnits($currentPlayer)) AddCurrentTurnEffect($cardID, $currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "6954704048"://Heroic Sacrifice
      Draw($currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "6954704048", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "4113123883"://Unnatural Life
      global $CS_AfterPlayedBy;
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:definedType=Unit;defeatedThisPhase=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "3426168686"://Sneak Attack
      global $CS_AfterPlayedBy;
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
      case "8800836530"://No Good To Me Dead
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "8800836530", 1);
        AddDecisionQueue("ADDLIMITEDNEXTTURNEFFECT", $otherPlayer, "8800836530", 1);
        break;  
    case "9097690846"://Snowtrooper Lieutenant
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        AddDecisionQueue("MZALLCARDTRAITORPASS", $currentPlayer, "Imperial", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9097690846", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}");
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "9210902604"://Precision Fire
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9210902604", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "6476609909"://Corner The Prey
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "6476609909", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "7870435409"://Bib Fortuna
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play Event") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an event to play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Event");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "8297630396"://Shoot First
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "8297630396", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "5767546527"://For a Cause I Believe In
      $deck = new Deck($currentPlayer);
      $deck->Reveal(4);
      $cards = $deck->Top(remove:true, amount:4);
      $cardArr = explode(",", $cards);
      $damage = 0;
      for($i=0; $i<count($cardArr); ++$i) {
        if(AspectContains($cardArr[$i], "Heroism", $currentPlayer)) {
          ++$damage;
        }
      }
      WriteLog(CardLink($cardID, $cardID) . " is dealing " . $damage . " damage. Pass to discard the rest of the cards.");
      DealDamageAsync($otherPlayer, $damage, "DAMAGE", "5767546527");
      if($cards != "") {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cards);
        AddDecisionQueue("SETDQVAR", $currentPlayer, 0);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Push pass (or Space) to discard the rest of the cards");
        AddDecisionQueue("MAYCHOOSETOP", $currentPlayer, $cards);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "FORACAUSEIBELIEVEIN");
      }
      break;
    case "5784497124"://Emperor Palpatine
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an ally to destroy");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DESTROY", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an ally to deal 1 damage");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      }
      break;
    case "8117080217"://Admiral Ozzel
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Play Imperial Unit") {
        global $CS_AfterPlayedBy;
        SetClassState($currentPlayer, $CS_AfterPlayedBy, $cardID);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;trait=Imperial");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "1626462639"://Change of Heart
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to take control of", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1626462639", 1);
      break;
    case "2855740390"://Lieutenant Childsen
      if($from != "PLAY") {
        AddDecisionQueue("FINDINDICES", $currentPlayer, "HANDASPECT,Vigilance");
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "4-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 4 cards to reveal", 1);
        AddDecisionQueue("MULTICHOOSEHAND", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "LTCHILDSEN", 1);
      }
      break;
    case "8506660490"://Darth Vader (Commanding the First Legion)
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose any number of units with combined cost 3 or less.");
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "10;99;include-definedType-Unit&include-maxCost-3&include-aspect-Villainy");
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "DARTHVADER", 1);
      }
      break;
    case "3789633661"://Cunning
      $options = "Return a non-leader unit with 4 or less power to its owner's hand;Give a unit +4/+0 for this phase;Exhaust up to 2 units;An opponent discards a random card from their hand";
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      for ($i = 0; $i < 2; ++$i) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose " . ($i == 0 ? "First" : "Second") . " Cunning Ability");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options&{0}");
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, "0");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("MODAL", $currentPlayer, "CUNNING", 1);
      }
      break;
    case "8615772965"://Vigilance
      $options = "Discard 6 cards from an opponent's deck;Heal 5 damage from a base;Defeat a unit with 3 or less remaining HP;Give a Shield token to a unit";
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      for ($i = 0; $i < 2; ++$i) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose " . ($i == 0 ? "First" : "Second") . " Vigilance Ability");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options&{0}");
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, "0");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("MODAL", $currentPlayer, "VIGILANCE", 1);
      }
      break;
    case "0073206444"://Command
      $options = "Give 2 Experience tokens to a unit;A friendly unit deals damage equal to its power to a non-unique enemy unit;Put this event into play as a resource;Return a unit from your discard pile to your hand";
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      for ($i = 0; $i < 2; ++$i) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose " . ($i == 0 ? "First" : "Second") . " Command Ability");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options&{0}");
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, "0");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("MODAL", $currentPlayer, "COMMAND", 1);
      }
      break;
    case "3736081333"://Aggression
      $options = "Draw a card;Defeat up to 2 upgrades;Ready a unit with 3 or less power;Deal 4 damage to a unit";
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      for ($i = 0; $i < 2; ++$i) {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose " . ($i == 0 ? "First" : "Second") . " Aggression Ability");
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options&{0}");
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, "0");
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("MODAL", $currentPlayer, "AGGRESSION", 1);
      }
      break;
    case "2471223947"://Frontline Shuttle
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Shuttle") {
        $ally = new Ally("MYALLY-" . $index);
        $ally->Destroy();
        AttackWithMyUnitEvenIfExhaustedNoBases($currentPlayer);
      }
      break;
    case "8968669390"://U-Wing Reinforcement
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "10;3;include-definedType-Unit&include-maxCost-7");
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "UWINGREINFORCEMENT", 1);
      break;
    case "7510418786"://Aid From The Innocent
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "10;2;exclude-definedType-Unit&include-aspect-Heroism");
      AddDecisionQueue("MULTIADDDISCARD", $currentPlayer, "HAND,TT-2", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      break;
    case "5950125325"://Confiscate
      DefeatUpgrade($currentPlayer);
      break;
    case "2668056720"://Disabling Fang Fighter
      if($from != "PLAY") DefeatUpgrade($currentPlayer, true);
      break;
    case "4323691274"://Power Failure
      DefeatUpgrade($currentPlayer);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "POWERFAILURE", 1);
      break;
    case "6087834273"://Restock
      AddDecisionQueue("FINDINDICES", $currentPlayer, "GY");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "4-");
      AddDecisionQueue("MULTICHOOSEDISCARD", $currentPlayer, "<-");
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "RESTOCK", 1);
      break;
    case "5035052619"://Jabba the Hutt
      if($from != "PLAY") {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "8;1;include-trait-Trick&include-definedType-Event");
        AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      }
      break;
    case "9644107128"://Bamboozle
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "BAMBOOZLE", 1);
      break;
    case "2639435822"://Force Lightning
      $damage = 2 * (intval($resourcesPaid) - 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to lose abilities and deal " . $damage . " damage");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "2639435822,PLAY", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$damage,$currentPlayer", 1);
      break;
    case "1951911851"://Grand Admiral Thrawn
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Exhaust") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose player to reveal top of deck");
        AddDecisionQueue("BUTTONINPUT", $currentPlayer, "Yourself,Opponent");
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "GRANDADMIRALTHRAWN", 1);
      }
      break;
    case "9785616387"://The Emperor's Legion
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "THEEMPERORSLEGION");
      break;
    case "1939951561"://Attack Pattern Delta
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      for($i=3; $i>0; --$i) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "dqVar=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give +" . $i . "/+" . $i, 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, 0, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH," . $i, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1939951561_" . $i . ",PLAY", 1);
      }
      break;
    case "2202839291"://Don't Get Cocky
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, 0);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "DONTGETCOCKY");
      break;
    case "2715652707"://I Had No Choice
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("OP", $otherPlayer, "SWAPDQPERSPECTIVE", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("OP", $otherPlayer, "SWAPDQPERSPECTIVE", 1);
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "{0},", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to return to hand");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $otherPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $otherPlayer, "IHADNOCHOICE", 1);
      break;
    case "8988732248"://Rebel Assault
      AddCurrentTurnEffect($cardID . "-1", $currentPlayer);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Rebel");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "0802973415"://Outflank
      AddCurrentTurnEffect($cardID, $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "5896817672"://Headhunting
      AddCurrentTurnEffect($cardID . "-1", $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, 1, 1);
      AddDecisionQueue("SETCOMBATCHAINSTATE", $currentPlayer, $CCS_CantAttackBase, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZALLCARDTRAITORPASS", $currentPlayer, "Bounty Hunter", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5896817672", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}");
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      AddDecisionQueue("REMOVECURRENTEFFECT", $currentPlayer, $cardID . "-1");
      break;
    case "8142386948"://Razor Crest
      MZMoveCard($currentPlayer, "MYDISCARD:definedType=Upgrade", "MYHAND", may:true);
      break;
    case "3228620062"://Cripple Authority
      Draw($currentPlayer);
      if(NumResources($otherPlayer) > NumResources($currentPlayer)) {
        PummelHit($otherPlayer);
      }
      break;
    case "6722700037"://Doctor Pershing
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Draw") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer,1", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    case "6536128825"://Grogu
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Exhaust") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
      case "3258646001"://Steadfast Senator
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Buff") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give +2");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "3258646001,HAND", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "9262288850"://Independent Senator
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Exhaust") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=4&THEIRALLY:maxAttack=4");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
    case "6585115122"://The Mandalorian unit
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=2&THEIRALLY:maxCost=2");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to heal and shield");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,999", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "3329959260"://Fell the Dragon
      MZChooseAndDestroy($currentPlayer, "MYALLY:minAttack=5&THEIRALLY:minAttack=5", filter:"leader=1");
      break;
    case "0282219568"://Clan Wren Rescuer
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to add experience");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "1081897816"://Mandalorian Warrior
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Mandalorian&THEIRALLY:trait=Mandalorian");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to add experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "0866321455"://Smuggler's Aid
      Restore(3, $currentPlayer);
      break;
    case "1090660242"://The Client
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Bounty") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give the bounty");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1090660242-2,PLAY", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICEFROMUNIQUE", 1);
      }
      break;
    case "1565760222"://Remnant Reserves
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;3;include-definedType-Unit");
      AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      break;
    case "2288926269"://Privateer Crew
      if($from == "RESOURCES") {
        for($i=0; $i<3; ++$i) $playAlly->Attach("2007868442");//Experience token
      }
      break;
    case "2470093702"://Wrecker
      MZChooseAndDestroy($currentPlayer, "MYRESOURCES", may:true, context:"Choose a resource to destroy");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a ground unit to deal 5 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,5,$currentPlayer,1", 1);
      break;
    case "1885628519"://Crosshair
      if($from != "PLAY") break;
      $ally = new Ally("MYALLY-" . $index);
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Buff") {
        AddCurrentTurnEffect("1885628519", $currentPlayer, $from, $ally->UniqueID());
      } else if($abilityName == "Snipe") {
        $currentPower = $ally->CurrentPower();
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a ground unit to deal " . $currentPower . " damage to", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$currentPower,$currentPlayer,1", 1);
      }
      break;
    case "3514010297"://Mandalorian Armor
      $ally = new Ally($target);
      if(TraitContains(GetMZCard($ally->PlayerID(), $target), "Mandalorian", $ally->PlayerID(), $ally->Index())) {
        $ally->Attach("8752877738");//Shield Token
      }
      break;
    case "1480894253"://Kylo Ren
      PummelHit($currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give +2 power", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1480894253,PLAY", 1);
      break;
    case "2995807621"://Trench Run
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Fighter");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2995807621,PLAY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "5834478243"://You're All Clear Kid
      MZChooseAndDestroy($currentPlayer, "THEIRALLY:arena=Space;maxHealth=3", context:"Choose a space unit with 3 or less HP to defeat");
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "YOUREALLCLEARKID", 1);
      break;
    case "5667308555"://I Have You Now
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a vehicle to attack with", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5667308555,PLAY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "8734471238"://Stay On Target
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a vehicle to attack with", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "8734471238,PLAY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "0931441928"://Ma Klounkee
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Underworld");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to bounce");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 3 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,3,$currentPlayer", 1);
      break;
    case "0302968596"://Calculated Lethality
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to defeat");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "CALCULATEDLETHALITY", 1);
      break;
    case "2503039837"://Moff Gideon Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Attack") {
        AddCurrentTurnEffect($cardID, $currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "9690731982"://Reckless Gunslinger
      if($from != "PLAY") {
        DealDamageAsync(1, 1, "DAMAGE", $cardID);
        DealDamageAsync(2, 1, "DAMAGE", $cardID);
      }
      break;
    case "8712779685"://Outland TIE Vanguard
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "5874342508"://Hotshot DL-44 Blaster
      if($from == "RESOURCES") {
        $ally = new Ally($target);
        if(!$ally->IsExhausted() && $ally->PlayerID() == $currentPlayer) {
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
          AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
        }
      }
      break;
    case "6884078296"://Greef Karga
      if($from != "PLAY") {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;1;include-definedType-Upgrade");
        AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      }
      break;
    case "1304452249"://Covetous Rivals
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasBountyOnly=true&THEIRALLY:hasBountyOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit with bounty to deal 2 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      break;
    case "2526288781"://Bossk
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage/Buff") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasBountyOnly=true&THEIRALLY:hasBountyOnly=true");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit with bounty to deal 1 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("YESNO", $currentPlayer, "if you want to give the unit +1 power", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2526288781", 1);
      }
      break;
    case "7424360283"://Bo-Katan Kryze
      global $CS_NumMandalorianAttacks;
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Deal Damage" && GetClassState($currentPlayer, $CS_NumMandalorianAttacks)) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      }
      break;
    case "0505904136"://Scanning Officer
      $resources = &GetResourceCards($otherPlayer);
      if(count($resources) == 0) break;
      $numDestroyed = 0;
      $cards = "";
      $indices = explode(",", GetIndices(count($resources), pieces:ResourcePieces()));
      $randomIndices = array_rand($indices, count($indices) >= 3 ? 3 : count($indices));
      rsort($randomIndices);
      foreach ($randomIndices as $randomIndex) {
        $index = $indices[$randomIndex];
        if ($cards != "") $cards .= ",";
        $cards .= $resources[$index];
        if (SmuggleCost($resources[$index], $otherPlayer, $index) >= 0) {
          AddGraveyard($resources[$index], $otherPlayer, 'ARS');
          for ($j = $index; $j < $index + ResourcePieces(); ++$j) unset($resources[$j]);
          $resources = array_values($resources);
          ++$numDestroyed;
        }
      }
      for($i=0; $i<$numDestroyed; ++$i) {
        AddTopDeckAsResource($otherPlayer);
      }
      RevealCards($cards);
      break;
    case "2560835268"://The Armorer
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Mandalorian");
        AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES");
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 3 mandalorians to give a shield");
        AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "MULTIGIVESHIELD", 1);
      }
      break;
    case "3622749641"://Krrsantan
      $numBounty = SearchCount(SearchAllies($otherPlayer, hasBountyOnly:true));
      if($numBounty > 0) {
        $playAlly->Ready();
      }
      break;
    case "9765804063"://Discerning Veteran
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $playAlly->UniqueID(), 1);
      break;
    case "3765912000"://Take Captive
      $targetAlly = new Ally($target, $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=" . CardArenas($targetAlly->CardID()));
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $targetAlly->UniqueID(), 1);
      break;
    case "8877249477"://Legal Authority
      $targetAlly = new Ally($target, $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxAttack=" . ($targetAlly->CurrentPower()-1));
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $targetAlly->UniqueID(), 1);
      break;
    case "5303936245"://Rival's Fall
      MZChooseAndDestroy($currentPlayer, "MYALLY&THEIRALLY");
      break;
    case "8818201543"://Midnight Repairs
      for($i=0; $i<8; ++$i) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", $i == 0 ? 0 : 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to restore 1 (Remaining: " . (8-$i) . ")", $i == 0 ? 0 : 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,1", 1);
      }
      break;
    case "3012322434"://Give In To Your Hate
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      AddDecisionQueue("WRITELOG", $currentPlayer, "This is a partially manual card. Make sure you attack a unit with this unit for your next action.", 1);
      break;
    case "2090698177"://Street Gang Recruiter
      MZMoveCard($currentPlayer, "MYDISCARD:trait=Underworld", "MYHAND", may:true, context:"Choose an underworld card to return with " . CardLink("2090698177", "2090698177"));
      break;
    case "7964782056"://Qi'Ra unit
      AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
      AddDecisionQueue("LOOKHAND", $currentPlayer, "-");
      AddDecisionQueue("INPUTCARDNAME", $currentPlayer, "<-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "7964782056_{0},HAND," . $otherPlayer, 1);
      break;
    case "8096748603"://Steela Gerrera
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Do you want to deal 2 damage to your base?");
      AddDecisionQueue("YESNO", $currentPlayer, "-");
      AddDecisionQueue("NOPASS", $currentPlayer, "-");
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYCHAR-0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "8;1;include-trait-Tactic", 1);
      AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      break;
    case "5157630261"://Compassionate Senator
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Heal") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "MYCHAR-0,THEIRCHAR-0,");
        AddDecisionQueue("MZFILTER", $currentPlayer, "damaged=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to heal", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,2", 1);
      }
      break;
    case "6570091935"://Tranquility
      MZMoveCard($currentPlayer, "MYDISCARD:trait=Republic;definedType=Unit", "MYHAND", may:true, context:"Choose a Republic unit to return to your hand");
      break;
    case "3388566378"://Ahsoka Tano JTL
      PummelHit($otherPlayer);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "AHSOKATANOJTL", 1);
      break;
    case "5751831621"://Red Squadron X-Wing
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Do you want to deal 2 damage to Red Squadron X-Wing to draw a card?");
      AddDecisionQueue("YESNO", $currentPlayer, "-");
      AddDecisionQueue("NOPASS", $currentPlayer, "-");
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYALLY-" . $playAlly->Index(), 1);
      AddDecisionQueue("MZOP", $currentPlayer, DamageStringBuilder(2, $currentPlayer, isUnitEffect:true), 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      break;
    case "7157369742"://TIE Dagger Vanguard
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:damagedOnly=true&THEIRALLY:damagedOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, DamageStringBuilder(2, $currentPlayer, isUnitEffect:true), 1);
      break;
    case "5830140660"://Bazine Netal
      AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRHAND");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to discard");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
      AddDecisionQueue("DRAW", $otherPlayer, "-", 1);
      break;
    case "8645125292"://Covert Strength
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to restore 2 and give a experience token to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,2", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      break;
    case "4783554451"://First Light
      if($from == "RESOURCES") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 4 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,4,$currentPlayer", 1);
      }
      break;
    case "5351496853"://Gideon's Light Cruiser
      if(ControlsNamedCard($currentPlayer, "Moff Gideon")) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:definedType=Unit;aspect=Villainy;maxCost=3&MYHAND:definedType=Unit;aspect=Villainy;maxCost=3");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "5440730550"://Lando Calrissian
      global $CS_AfterPlayedBy;
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYRESOURCES:keyword=Smuggle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      break;
    case "040a3e81f3"://Lando Calrissian Leader Unit
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Smuggle") {
        $mzIndex = "MYALLY-" . GetAllyIndex($cardID, $currentPlayer);
        $ally = new Ally($mzIndex, $currentPlayer);
        if($ally->NumUses() <= 0) {
          WriteLog("Smuggle ability was already used this turn. Game state reverted");
          RevertGamestate();
        } else {
          global $CS_AfterPlayedBy;
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYRESOURCES:keyword=Smuggle");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
          AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $mzIndex, 1);
          AddDecisionQueue("ADDMZUSES", $currentPlayer, -1, 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
          AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
          AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
          AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
          AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        }
      }
      break;
    case "0754286363"://The Mandalorian's Rifle
      $ally = new Ally($target, $currentPlayer);
      if(CardTitle($ally->CardID()) == "The Mandalorian") {
        AddLayer("TRIGGER", $currentPlayer, $cardID, uniqueID: $ally->UniqueID());
      }
      break;
    case "4643489029"://Palpatine's Return
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD:definedType=Unit");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "4717189843"://A New Adventure
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=6&THEIRALLY:maxCost=6");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to return to hand");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ANEWADVENTURE", 1);
      break;
    case "9757839764"://Adelphi Patrol Wing
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give +2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      if($initiativePlayer == $currentPlayer) {
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9757839764,HAND", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      }
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "7212445649"://Bravado
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to ready");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      break;
    case "2432897157"://Qi'Ra
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Shield") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage and give a shield");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "4352150438"://Rey
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Experience") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=2");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give an experience");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "5778949819"://Relentless Pursuit
      $ally = new Ally($target, $currentPlayer);
      if(TraitContains($ally->CardID(), "Bounty Hunter", $currentPlayer)) $ally->Attach("8752877738");//Shield Token
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxCost=" . (CardCost($ally->CardID())));
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $ally->UniqueID(), 1);
      break;
    case "6847268098"://Timely Intervention
      AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "1973545191"://Unexpected Escape
      $owner = MZPlayerID($currentPlayer, $target);
      $ally = new Ally($target, $owner);
      $ally->Exhaust();
      RescueUnit($currentPlayer, $target);
      break;
    case "9552605383"://L3-37
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to rescue from (or pass for shield)");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "L337," . $uniqueId);
      break;
    case "5818136044"://Xanadu Blood
      XanaduBlood($currentPlayer, $playAlly->Index());
      break;
    case "1312599620"://Smuggler's Starfighter
      if(SearchCount(SearchAllies($currentPlayer, trait:"Underworld")) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give -3 power");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1312599620,PLAY", 1);
      }
      break;
    case "6853970496"://Slaver's Freighter
      $theirAllies = &GetAllies($otherPlayer);
      $numUpgrades = 0;
      for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
        $ally = new Ally("MYALLY-" . $i, $otherPlayer);
        $numUpgrades += $ally->NumUpgrades();
      }
      if($numUpgrades > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxAttack=" . $numUpgrades . "&THEIRALLY:maxAttack=" . $numUpgrades);
        if($index > -1) AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to ready");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      }
      break;
    case "2143627819"://The Marauder
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYDISCARD");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card in your discard to resource");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "THEMARAUDER", 1);
      break;
    case "7642980906"://Stolen Landspeeder
      if ($from == "HAND" && $playAlly->Exists()) {
        AddDecisionQueue("PASSPARAMETER", $otherPlayer, $playAlly->UniqueID());
        AddDecisionQueue("MZOP", $otherPlayer, "TAKECONTROL");
      }
      break;
    case "2346145249"://Choose Sides
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to swap");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an enemy unit to swap", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
      break;
    case "0598830553"://Dryden Vos
      PlayCaptive($currentPlayer, $target);
      break;
    case "1477806735"://Wookiee Warrior
      if(SearchCount(SearchAllies($currentPlayer, trait:"Wookiee")) > 1) {
        Draw($currentPlayer);
      }
      break;
    case "5696041568"://Triple Dark Raid
      global $CS_AfterPlayedBy;
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "7;1;include-trait-Vehicle");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
      AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("OP", $currentPlayer, "PLAYCARD,DECK", 1);
      break;
    case "0911874487"://Fennec Shand
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Ambush") {
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;maxCost=4");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "2b13cefced"://Fennec Shand Leader Unit
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Ambush") {
        AddCurrentTurnEffect($cardID, $currentPlayer, "PLAY");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit;maxCost=4");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "9828896088"://Spark of Hope
      MZMoveCard($currentPlayer, "MYDISCARD:definedType=Unit;defeatedThisPhase=true", "MYRESOURCES", may:true);
      AddDecisionQueue("PAYRESOURCES", $currentPlayer, "1,1", 1);
      break;
    case "9845101935"://This is the Way
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "8;2;include-trait-Mandalorian|include-definedType-Upgrade");
      AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      break;
    case "8261033110"://Evacuate
      $p1Allies = &GetAllies(1);
      $p1Captives = [];
      for($i=count($p1Allies)-AllyPieces(); $i>=0; $i-=AllyPieces()) {
        $ally = new Ally("MYALLY-" . $i, 1);
        if(!$ally->IsLeader()) {
          $p1Captives = array_merge($p1Captives, $ally->GetCaptives());
          MZBounce(1, "MYALLY-" . $i);
        }
      }
      $p2Allies = &GetAllies(2);
      for($i=count($p2Allies)-AllyPieces(); $i>=0; $i-=AllyPieces()) {
        $ally = new Ally("MYALLY-" . $i, 2);
        if(in_array($ally->CardID(), $p1Captives)) {
          $index = array_search($ally->CardID(),$p1Captives);
          unset($p1Captives[$index]);
        } else if (!$ally->IsLeader()) {
          MZBounce(2, "MYALLY-" . $i);
        }
      }
      break;
    case "1910812527"://Final Showdown
      AddCurrentTurnEffect("1910812527", $currentPlayer);
      $myAllies = &GetAllies($currentPlayer);
      for($i=0; $i<count($myAllies); $i+=AllyPieces())
      {
        $ally = new Ally("MYALLY-" . $i, $currentPlayer);
        $ally->Ready();
      }
      break;
    case "a742dea1f1"://Han Solo Red Unit
    case "9226435975"://Han Solo Red
      $abilityName = GetResolvedAbilityName($cardID, $from);
      $choosePhase = $cardID == "9226435975" ? "MAYCHOOSEMULTIZONE" : "CHOOSEMULTIZONE";
      if($abilityName == "Play") {
        global $CS_AfterPlayedBy;
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to play");
        AddDecisionQueue($choosePhase, $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "9226435975", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
        AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "7354795397"://No Bargain
      PummelHit($otherPlayer);
      Draw($currentPlayer);
      break;
    case "9270539174"://Wild Rancor
      DamageAllAllies(2, "9270539174", arena: "Ground", except: "MYALLY-" . $playAlly->Index());
      break;
    case "2744523125"://Salacious Crumb
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Bounce") {
        $salaciousCrumbIndex = SearchAlliesForCard($currentPlayer, $cardID);
        MZBounce($currentPlayer, "MYALLY-" . $salaciousCrumbIndex);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer,1", 1);
      } else if($from != "PLAY") {
        Restore(1, $currentPlayer);
      }
      break;
    case "0622803599"://Jabba the Hutt Leader
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Bounty") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give bounty");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "0622803599-2,PLAY", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICEFROMUNIQUE", 1);
      }
      break;
    case "f928681d36"://Jabba the Hutt Leader Unit
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Bounty") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give bounty");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "f928681d36-2,PLAY", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICEFROMUNIQUE", 1);
      }
      break;
    case "8090818642"://The Chaos of War
      $p1Hand = &GetHand(1);
      DamageTrigger(1, count($p1Hand)/HandPieces(), "DAMAGE", "8090818642");
      $p2Hand = &GetHand(2);
      DamageTrigger(2, count($p2Hand)/HandPieces(), "DAMAGE", "8090818642");
      break;
    case "7826408293"://Daring Raid
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "MYCHAR-0,THEIRCHAR-0,");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose something to deal 2 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
      break;
    case "4772866341"://Pillage
      $player = $additionalCosts == "Yourself" ? $currentPlayer : $otherPlayer;
      PummelHit($player);
      PummelHit($player);
      break;
    case "5984647454"://Enforced Loyalty
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose something to sacrifice");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      break;
    case "6234506067"://Cassian Andor
      if($from == "RESOURCES") $playAlly->Ready();
      break;
    case "5169472456"://Chewbacca Pykesbane
      if($from != "PLAY") {
        MZChooseAndDestroy($currentPlayer, "MYALLY:maxHealth=5&THEIRALLY:maxHealth=5", may:true, filter:"index=MYALLY-" . $playAlly->Index());
      }
      break;
    case "6962053552"://Desperate Attack
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("MZFILTER", $currentPlayer, "damaged=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give +2");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "6962053552,HAND", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}");
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "3803148745"://Ruthless Assassin
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      break;
    case "4057912610"://Bounty Guild Initiate
      if($from != "PLAY" && SearchCount(SearchAllies($currentPlayer, trait:"Bounty Hunter")) > 1) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "6475868209"://Criminal Muscle
      if($from != "PLAY") {
        DefeatUpgrade($currentPlayer, may:true, upgradeFilter: "unique=1", to:"HAND");
      }
      break;
    case "1743599390"://Trandoshan Hunters
      if(SearchCount(SearchAllies($otherPlayer, hasBountyOnly:true)) > 0) $playAlly->Attach("2007868442");//Experience token
      break;
    case "1141018768"://Commission
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "10;1;include-trait-Bounty Hunter|include-trait-Item|include-trait-Transport");
      AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      break;
    case "9596662994"://Finn
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Shield") {
        DefeatUpgrade($currentPlayer, search:"MYALLY");
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    case "7578472075"://Let the Wookiee Win
      $options = "They ready up to 6 resources;They ready a friendly unit. If it's a Wookiee unit, attack with it. It gets +2/+0 for this attack";
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose one for your opponent");
      AddDecisionQueue("CHOOSEOPTION", $otherPlayer, "$cardID&$options");
      AddDecisionQueue("SHOWOPTIONS", $otherPlayer, "$cardID&$options");
      AddDecisionQueue("MODAL", $currentPlayer, "LETTHEWOOKIEEWIN");
      break;
    case "8380936981"://Jabba's Rancor
      JabbasRancor($currentPlayer, $playAlly->Index());
      break;
    case "2750823386"://Look the Other Way
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCARDID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("YESNO", $otherPlayer, "if you want to pay 2 to prevent <1> from being exhausted", 1);//Should have a CardLink, but doing SETDQVAR and adding <1> to the string for YESNO breaks the UI. Something to do with YESNO being processed outside normal DecisionQueue stuff I suspect.
      AddDecisionQueue("NOPASS", $otherPlayer, "-", 1);
      AddDecisionQueue("PAYRESOURCES", $otherPlayer, "2", 1);
      AddDecisionQueue("ELSE", $currentPlayer, "-");
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "4002861992"://DJ (Blatant Thief)
      if($from == "RESOURCES") {
        $djAlly = new Ally("MYALLY-" . LastAllyIndex($currentPlayer), $currentPlayer);
        // Try to get ready resources first
        $theirResourceIndices = GetArsenalFaceDownIndices($otherPlayer, 0);
        if ($theirResourceIndices == "") {
          // If no ready resources, get all resources
          $theirResourceIndices = GetArsenalFaceDownIndices($otherPlayer);
        }
        $theirResourceIndicesArr = explode(",", $theirResourceIndices);
        $theirResourceIndex = $theirResourceIndicesArr[GetRandom(0, count($theirResourceIndicesArr) - 1)]; // Pick a random resource. Important: remove this randomization if it breaks the game.
        $theirResources = &GetArsenal($otherPlayer);
        $isExhausted = $theirResources[$theirResourceIndex + 4];

        // Steal the resource
        $resourceCard = RemoveResource($otherPlayer, $theirResourceIndex);
        AddResources($resourceCard, $currentPlayer, "PLAY", "DOWN", isExhausted:$isExhausted, stealSource:$djAlly->UniqueID());

        // The new rules (v3) allow you to change the state of your resources immediately after smuggling the DJ, provided the total number of "ready" and "exhausted" resources remains the same.
        // So, we will exhaust the stolen resource and ready another.
        if (!$isExhausted) {
          $myResourceIndices = GetArsenalFaceDownIndices($currentPlayer, 1);
          if ($myResourceIndices != "") {
            $myResourceIndicesArr = explode(",", $myResourceIndices);
            $myResourceIndex = $myResourceIndicesArr[GetRandom(0, count($myResourceIndicesArr) - 1)]; // Pick a random resource. Important: remove this randomization if it breaks the game.
            $myResources = &GetArsenal($currentPlayer);
            $myResources[$myResourceIndex + 4] = "0"; // Ready a random resource
            $myResources[count($myResources) - ArsenalPieces() + 4] = "1"; // Exhaust the stolen resource
          }
        }
      }
      break;
    case "7718080954"://Frozen in Carbonite
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, $target);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "6117103324"://Jetpack
      $ally = new Ally($target, $currentPlayer);
      if ($ally->Exists()) {
        $upgradeUniqueID = $ally->Attach("8752877738");//Shield Token
        AddCurrentTurnEffect("6117103324", $currentPlayer, uniqueID:$upgradeUniqueID);
      }
      break;
    case "1386874723"://Omega (Part of the Squad)
      if($from != "PLAY") {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "5;1;include-trait-Clone");
        AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      }
      break;
    case "6151970296"://Bounty Posting
      MZMoveCard($currentPlayer, "MYDECK:trait=Bounty", "MYHAND", isReveal:true, may:true, context:"Choose a bounty to add to your hand");
      AddDecisionQueue("SHUFFLEDECK", $currentPlayer, "-");
      AddDecisionQueue("YESNO", $currentPlayer, "if you want to play the upgrade", 1);
      AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
      AddDecisionQueue("FINDINDICES", $currentPlayer, "MZLASTHAND", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "8576088385"://Detention Block Rescue
      $owner = MZPlayerID($currentPlayer, $target);
      $ally = new Ally($target, $owner);
      $damage = count($ally->GetCaptives()) > 0 ? 6 : 3;
      $ally->DealDamage($damage);
      break;
    case "9999079491"://Mystic Reflection
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to debuff", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "9999079491,HAND", 1);
      if(SearchCount(SearchAllies($currentPlayer, trait:"Force")) > 0) {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REDUCEHEALTH,2", 1);
      }
      break;
    case "5576996578"://Endless Legions
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "ENDLESSLEGIONS");
      break;
    case "8095362491"://Frontier Trader
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYRESOURCES");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a resource to return to hand", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
        AddDecisionQueue("YESNO", $currentPlayer, "if you want to add a resource from the top of your deck", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("OP", $currentPlayer, "ADDTOPDECKASRESOURCE", 1);
      }
        break;
    case "8709191884"://Hunter (Outcast Sergeant)
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Replace Resource") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYRESOURCES");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a resource to reveal", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "HUNTEROUTCASTSERGEANT", 1);
      }
      break;
    case "4663781580"://Swoop Down
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "4663781580,HAND", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $otherPlayer, "4663781580", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "4895747419"://Consolidation Of Power
      $allies = &GetAllies($currentPlayer);
      $totalAllies = count($allies) / AllyPieces();
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "$totalAllies-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose any number of friendly units", 1);
      AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "CONSOLIDATIONOFPOWER", 1);
      break;
    case "9752523457"://Finalizer
      $allies = &GetAllies($currentPlayer);
      for($i=0; $i<count($allies); $i+=AllyPieces()) {
        $ally = new Ally("MYALLY-" . $i, $currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit for " . CardLink($ally->CardID(), $ally->CardID()) . " to capture (must be in same arena)", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $ally->UniqueID(), 1);
      }
      break;
    case "6425029011"://Altering the Deal
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "hasCaptives=0", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to discard a captive from.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETCAPTIVES", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a captive to discard", 1);
      AddDecisionQueue("CHOOSECARD", $currentPlayer, "<-", 1);
      AddDecisionQueue("OP", $currentPlayer, "DISCARDCAPTIVE", 1);
      break;
    case "6452159858"://Evidence of the Crime
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasUpgradeOnly=true&THEIRALLY:hasUpgradeOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to take a 3-cost or less upgrade from.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUPGRADES", 1);
      AddDecisionQueue("FILTER", $currentPlayer, "LastResult-include-maxCost-3", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an upgrade to take.", 1);
      AddDecisionQueue("CHOOSECARD", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "canAttach={0}", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to move <0> to.", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "MOVEUPGRADE", 1);
      break;
    case "3399023235"://Fenn Rau
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Upgrade");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an upgrade to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);

      break;
    case "1503633301"://Survivors' Gauntlet
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasUpgradeOnly=true&THEIRALLY:hasUpgradeOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to move an upgrade from.", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUPGRADES", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an upgrade to move.", 1);
      AddDecisionQueue("CHOOSECARD", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "SURVIVORS'GAUNTLET", 1);
      break;
    case "3086868510"://Pre Vizsla
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasUpgradeOnly=true&THEIRALLY:hasUpgradeOnly=true");
      AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to steal an upgrade from.", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUPGRADES", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an upgrade to steal.", 1);
      AddDecisionQueue("CHOOSECARD", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "PREVIZSLA", 1);
      break;
    case "3671559022"://Echo
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "You may discard a card to Echo's ability", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDISCARD", $currentPlayer, "HAND," . $currentPlayer, 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:sameTitle={0}&THEIRALLY:sameTitle={0}", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give 2 experience tokens to.", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      }
      break;
    case "8080818347"://Rule with Respect
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to capture all enemy units that attacked your base this phase", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "RULEWITHRESPECT", 1);
    break;
    case "3468546373"://General Rieekan
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a target for " . CardLink($cardID, $cardID) . "'s ability", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "GENERALRIEEKAN", 1);
      break;
    case "3577961001"://Mercenary Gunship
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Take Control") {
        global $CS_OppCardActive;
        $oppIndex = GetClassState($currentPlayer, $CS_OppIndex);
        $ally = new Ally("THEIRALLY-" . $oppIndex, $otherPlayer);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $ally->UniqueID(), 1);
        AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, -1, 1);
        AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_OppCardActive, 1);
      }
      break;
    case "8552292852"://Kashyyyk Defender
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
      AddDecisionQueue("MZFILTER", $currentPlayer, "damaged=0");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to restore 2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,2", 1);
      AddDecisionQueue("UNIQUETOMZ", $currentPlayer, $playAlly->UniqueID(), 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
      break;
    case "7439418148"://Twice the Pride
      $ally = new Ally($target);
      $ally->DealDamage(2);
      break;
    case "7252148824"://501st Liberator
      if (SearchCount(SearchAllies($currentPlayer, trait:"Republic")) > 1) {
        Restore(3, $currentPlayer);
      }
      break;
    case "7280804443"://Hold-Out Blaster
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to deal 1 damage");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer,1", 1);
      break;
    case "6969421569"://Batch Brothers
      CreateCloneTrooper($currentPlayer);
      break;
    case "6826668370"://Droid Deployment
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      break;
    case "6401761275"://In Pursuit
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "5936350569"://Jesse
      CreateBattleDroid($otherPlayer);
      CreateBattleDroid($otherPlayer);
      break;
    case "5584601885"://Battle Droid Escort
      CreateBattleDroid($currentPlayer);
      break;
    case "5074877594"://Drop In
      CreateCloneTrooper($currentPlayer);
      CreateCloneTrooper($currentPlayer);
      break;
    case "4412828936"://Merciless Contest
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to destroy");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY", 1);
      AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $otherPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to destroy");
      AddDecisionQueue("CHOOSEMULTIZONE", $otherPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "DESTROY", 1);
      break;
    case "3840495762"://Old Access Codes
      if(TheyControlMoreUnits($currentPlayer)) {
        Draw($currentPlayer);
      }
      break;
    case "3357486161"://Political Pressure
      $options = "Discard a random card from your hand;Opponent creates 2 Battle Droid tokens";
      if (CountHand($otherPlayer) > 0) {
        AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose one");
        AddDecisionQueue("CHOOSEOPTION", $otherPlayer, "$cardID&$options");
        AddDecisionQueue("SHOWOPTIONS", $otherPlayer, "$cardID&$options");
      } else {
        AddDecisionQueue("PASSPARAMETER", $otherPlayer, 1); // Create 2 Battle Droid tokens
      }
      AddDecisionQueue("MODAL", $otherPlayer, "POLITICALPRESSURE");
      break;
    case "0511508627"://Captain Rex
      CreateCloneTrooper($currentPlayer);
      CreateCloneTrooper($currentPlayer);
      break;
    case "0598115741"://Royal Guard Attache
      $playAlly->DealDamage(2);
      break;
    case "0968965258"://Death By Droids
      MZChooseAndDestroy($currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      break;
    case "0036920495"://Elite P-38 Starfighter
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer,1", 1);
      break;
    case "2585318816"://Resolute
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "RESOLUTE", 1);
      break;
    case "0328412140"://Creative Thinking
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "unique=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      CreateCloneTrooper($currentPlayer);
      break;
    case "0959549331"://Unmasking the Conspiracy
      $hand = &GetHand($currentPlayer);
      if(count($hand) > 0) {
        PummelHit($currentPlayer);
        AddDecisionQueue("REVEALHANDCARDS", $otherPlayer, "-");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRHAND");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose which card you want your opponent to discard", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDISCARD", $currentPlayer, "HAND," . $currentPlayer, 1);
        AddDecisionQueue("MZREMOVE", $currentPlayer, "-", 1);
      }
      break;
    case "1192349217"://Manufactured Soldiers
      $options = "Create 2 Clone Trooper tokens;Create 3 Battle Droid tokens";
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose one");
      AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options");
      AddDecisionQueue("MODAL", $currentPlayer, "MANUFACTUREDSOLDIERS");
      break;
    case "1417180295"://Strategic Analysis
      Draw($currentPlayer);
      Draw($currentPlayer);
      Draw($currentPlayer);
      break;
    case "2103133661"://Blood Sport
      DamageAllAllies(2, "2103133661", arena: "Ground");
      break;
    case "2483302291"://On the Doorstep
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      $allies = &GetAllies($currentPlayer);
      for($i=0; $i<3; ++$i) {
        $ally = new Ally("MYALLY-" . (count($allies) - ($i+1)*AllyPieces()), $currentPlayer);
        $ally->Ready();
      }
      break;
    case "2761325938"://Devastating Gunship
      MZChooseAndDestroy($currentPlayer, "THEIRALLY:maxHealth=2");
      break;
    case "4824842849"://Subjugating Starfighter
      if($initiativePlayer == $currentPlayer) {
        CreateBattleDroid($currentPlayer);
      }
      break;
    case "6732988831"://Grievous Reassembly
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to restore 3");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,3", 1);
      CreateBattleDroid($currentPlayer);
      break;
    case "6700679522"://Tri-Droid Suppressor
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "9479767991"://Favorable Deligate
      Draw($currentPlayer);
      break;
    case "3348783048"://Geonosis Patrol Fighter
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to bounce");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "8777351722"://Anakin Skywalker
      DealDamageAsync($currentPlayer, 2, "DAMAGE", "8777351722");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "6410481716"://Mace Windu's Lightsaber
      if(CardTitle(GetMZCard($currentPlayer, $target)) == "Mace Windu") {
        Draw($currentPlayer);
        Draw($currentPlayer);
      }
      break;
    case "5616678900"://R2-D2
      PummelHit($currentPlayer, may:true);
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "3;1;", 1);
      AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
      break;
    case "4910017138"://Breaking In
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give +2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "4910017138,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "3799780905"://Prisoner of War
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture another unit");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE,{0}", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "PRISONEROFWAR", 1);
      break;
    case "3500129784"://Petition the Senate
      if(SearchCount(SearchAllies($currentPlayer, trait:"Official")) >= 3) {
        Draw($currentPlayer);
        Draw($currentPlayer);
        Draw($currentPlayer);
      }
      break;
    case "3476041913"://Low Altitude Gunship
      $damage = SearchCount(SearchAllies($currentPlayer, trait:"Republic"));
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $damage . " damage to");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$damage,$currentPlayer,1", 1);
      break;
    case "2784756758"://Obi-wan Kenobi
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:damagedOnly=true&THEIRALLY:damagedOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to heal");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,1", 1);
      break;
    case "8929774056"://Asajj Ventress
      global $CS_NumEventsPlayed;
      if(GetClassState($currentPlayer, $CS_NumEventsPlayed) > 0) AddCurrentTurnEffect("8929774056", $currentPlayer, "PLAY");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "9966134941"://Pelta Supply Frigate
      if(IsCoordinateActive($currentPlayer)) CreateCloneTrooper($currentPlayer);
      break;
    case "6461101372"://Maul
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "6461101372", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "2155351882"://Ahsoka Tano
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "2155351882", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "5081383630"://Pre Viszla
      global $CS_CardsDrawn;
      $cardsDrawn = GetClassState($currentPlayer, $CS_CardsDrawn);
      if($cardsDrawn > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $cardsDrawn . " damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$cardsDrawn,$currentPlayer", 1);
      }
      break;
    case "8061497086"://Perilous Position
      $ally = new Ally($target, MZPlayerID($currentPlayer, $target));
      $ally->Exhaust();
      $ally->DefeatIfNoRemainingHP();
      break;
    case "8345985976"://Trade Federation Shuttle
      if(SearchCount(SearchAllies($currentPlayer, damagedOnly:true))) CreateBattleDroid($currentPlayer);
      break;
    case "8060312086"://Self Destruct
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to sacrifice");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 4 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,4,$currentPlayer", 1);
      break;
    case "8540765053"://Savage Opress
      if(HasMoreUnits($otherPlayer)) $playAlly->Ready();
      break;
    case "9620454519"://Clear the Field
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:maxCost=3&THEIRALLY:maxCost=3");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to return to hand");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "CLEARTHEFIELD", 1);
      break;
    case "9832122703"://Luminara Unduli
      $healAmount = SearchCount(SearchAllies($currentPlayer));
      Restore($healAmount, $currentPlayer);
      break;
    case "1882027961"://Wolf Pack Escort
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to return to hand");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "1389085256"://Lethal Crackdown
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to destroy");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "LETHALCRACKDOWN", 1);
      break;
    case "5683908835"://Count Dooku
      AddCurrentTurnEffect("5683908835", $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:trait=Separatist");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      break;
    case "4628885755"://Mace Windu
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:damagedOnly=true");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal damage");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETDAMAGE", 1);
      AddDecisionQueue("LESSTHANPASS", $currentPlayer, "4"); // Check if the unit has at least 4 damage to take 2 damage
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
      AddDecisionQueue("ELSE", $currentPlayer, "-");
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      break;
    case "0026166404"://Chancellor Palpatine Leader
      if (SearchCount(SearchAlliesDestroyed($currentPlayer, aspect:"Heroism")) > 0) {
        Draw($currentPlayer);
        Restore(2, $currentPlayer);
        $char = &GetPlayerCharacter($currentPlayer);
        $char[CharacterPieces()] = "ad86d54e97";
        $char[CharacterPieces() + 1] = 1; // Ehxaust the flipped Leader. It's necessary to manually exhaust the Leader only if the Leader was flipped.
      }
      break;
    case "ad86d54e97"://Darth Sidious Leader
      global $CS_NumVillainyPlayed;
      if (GetClassState($currentPlayer, $CS_NumVillainyPlayed) > 0) {
        CreateCloneTrooper($currentPlayer);
        DealDamageAsync(($currentPlayer == 1 ? 2 : 1), 2, "DAMAGE", "ad86d54e97");
        $char = &GetPlayerCharacter($currentPlayer);
        $char[CharacterPieces()] = "0026166404"; // Chancellor Palpatine Leader
        $char[CharacterPieces() + 1] = 1; // Ehxaust the flipped Leader. It's necessary to manually exhaust the Leader only if the Leader was flipped.
      }
      break;
    case "7734824762"://Captain Rex
      global $CS_NumAttacks;
      if(GetClassState($currentPlayer, $CS_NumAttacks) > 0) {
        CreateCloneTrooper($currentPlayer);
      }
      break;
    case "3410014206"://Vanguard Droid Bomber
      if(SearchCount(SearchAllies($currentPlayer, trait:"Separatist")) > 1) {
        DealDamageAsync($currentPlayer == 1 ? 2 : 1, 2, "DAMAGE", "3410014206");
      }
      break;
    case "4210027426"://Heavy Persuader Tank
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      break;
    case "4512764429"://Sanctioner's Shuttle
      if(IsCoordinateActive($currentPlayer)) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxCost=3");
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $playAlly->UniqueID(), 1);
      }
      break;
    case "6849037019"://Now There Are Two of Them
      $allies = &GetAllies($currentPlayer);
      if(count($allies) == AllyPieces()) {
        $ally = new Ally("MYALLY-0");
        $traits = CardTraits($ally->CardID());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to put into play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
        AddDecisionQueue("MZFILTER", $currentPlayer, "trait=Vehicle");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("NOTSHARETRAITPASS", $currentPlayer, $traits, 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "7013591351"://Admiral Trench
      MZMoveCard($currentPlayer, "MYDISCARD:definedType=Unit;defeatedThisPhase=true", "MYHAND", may:true, context:"Return up to 3 units that were defeated this phase");
      MZMoveCard($currentPlayer, "MYDISCARD:definedType=Unit;defeatedThisPhase=true", "MYHAND", may:true, context:"Return up to 2 units that were defeated this phase", isSubsequent:1);
      MZMoveCard($currentPlayer, "MYDISCARD:definedType=Unit;defeatedThisPhase=true", "MYHAND", may:true, context:"Return 1 unit that was defeated this phase", isSubsequent:1);
      break;
    case "6648824001":
      ObiWansAethersprite($currentPlayer, $playAlly->Index());
      break;
    case "6669050232"://Grim Resolve
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give Grit");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "6669050232,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "2565830105"://Invasion of Christophsis
      DestroyAllAllies($otherPlayer);
      break;
    case "2535372432"://Aggrieved Parliamentarian
      $theirDiscard = &GetDiscard($otherPlayer);
      $deck = new Deck($otherPlayer);
      for($i=count($theirDiscard) - DiscardPieces(); $i>=0; $i-=DiscardPieces()) {
        $deck->Add(RemoveDiscard($otherPlayer, $i));
      }
      break;
    case "5184505570"://Chimaera JTL
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasWhenDefeatedOnly=true");
      AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to use a When Defeated ability");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("USEWHENDEFEATED", $currentPlayer, "-", 1);
      break;
    case "0398102006"://The Invisible Hand
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      break;
    case "1686059165"://Wat Tambor
      global $CS_NumAlliesDestroyed;
      if(GetClassState($currentPlayer, $CS_NumAlliesDestroyed) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give +2/+2");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1686059165,PLAY", 1);
      }
      break;
    case "2041344712"://Osi Sobeck
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena=Ground;maxCost=" . $resourcesPaid);
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $playAlly->UniqueID(), 1);
      break;
    case "2298508689"://Reckless Torrent
      if(IsCoordinateActive($currentPlayer)) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit to deal 2 damage (or pass)");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to (make sure it's same arena)", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
      }
      break;
    case "2395430106"://Republic Tactical Officer
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Republic");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack and give +2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2395430106,HAND", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}");
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "2267524398"://The Clone Wars
      for($i=0; $i<$resourcesPaid-2; ++$i) {
        CreateCloneTrooper($currentPlayer);
        CreateBattleDroid($otherPlayer);
      }
      break;
    case "1302133998"://Impropriety Among Thieves
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose their unit to take control of", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give control of", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "1302133998", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "1302133998", 1);
      break;
    case "2847868671"://Yoda Leader
      global $CS_NumLeftPlay;
      if(GetClassState($currentPlayer, $CS_NumLeftPlay) > 0 || GetClassState($otherPlayer, $CS_NumLeftPlay) > 0) {
        Draw($currentPlayer);
        AddDecisionQueue("HANDTOPBOTTOM", $currentPlayer, "-");
      }
      break;
    case "3381931079"://Malevolence
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give -4/-0", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "3381931079,HAND", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICEFROMUNIQUE", 1);
      break;
    case "5333016146"://Rune Haako
      global $CS_NumAlliesDestroyed;
      if(GetClassState($currentPlayer, $CS_NumAlliesDestroyed) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give -1/-1", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, 0, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $otherPlayer, "5333016146,HAND", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REDUCEHEALTH,1", 1);
      }
      break;
    case "6064906790"://Nute Gunray
      WriteLog(DefinedCardType($cardID));
      global $CS_NumAlliesDestroyed;
      if(GetClassState($currentPlayer, $CS_NumAlliesDestroyed) >= 2) {
        CreateBattleDroid($currentPlayer);
      }
      break;
    case "2872203891"://General Grievious
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give Sentinel");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Droid&THEIRALLY:trait=Droid");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "2872203891,HAND", 1);
      break;
    case "0693815329"://Cad Bane
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "8");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      for($i=0; $i<3; ++$i) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxHealth={0}", 1);
        AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to capture (Max HP: {0})", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETHEALTH", 1);
        AddDecisionQueue("DECDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "CAPTURE," . $playAlly->UniqueID(), 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("LESSTHANPASS", $currentPlayer, 1, 1);
      }
      break;
    case "8418001763"://Huyang
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give +2/+2");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "8418001763,PLAY", 1);
      break;
    case "0216922902"://The Zillo Beast
      $theirAllies = &GetTheirAllies($currentPlayer);
      for ($i = 0; $i < count($theirAllies); $i += AllyPieces()) {
        if (CardArenas($theirAllies[$i]) == "Ground") {
          AddCurrentTurnEffect("0216922902", $otherPlayer, "PLAY", $theirAllies[$i+5]);
        }
      }
      break;
    case "4916334670"://Encouraging Leadership
      $allies = &GetAllies($currentPlayer);
      for ($i = 0; $i < count($allies); $i += AllyPieces()) {
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYALLY-$i", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,1", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $allies[$i+5], 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "4916334670,PLAY", 1);
      }
      break;
    case "3596811933"://Disruptive Burst
      $theirAllies = &GetTheirAllies($currentPlayer);
      for ($i = 0; $i < count($theirAllies); $i += AllyPieces()) {
        $theirAlly = new Ally("THEIRALLY-" . $i);
        $theirAlly->AddRoundHealthModifier(-1);
        $theirAlly->AddEffect("3596811933", "PLAY");
      }
      break;
    case "2870878795"://Padme Amidala
      if(IsCoordinateActive($currentPlayer)) {
        AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, "3;1;include-trait-Republic");
        AddDecisionQueue("ADDHAND", $currentPlayer, "-", 1);
        AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      }
      break;
    case "4042866439"://Grenade Strike
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 2 damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETARENA", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "2", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena={2}&THEIRALLY:arena={2}", 1);
      AddDecisionQueue("MZFILTER", $currentPlayer, "uniqueID={1}", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      break;
    case "2483520485"://Private Manufacturing
      Draw($currentPlayer);
      Draw($currentPlayer);
      if(SearchCount(SearchAllies($currentPlayer, tokenOnly:true)) == 0) {
        MZMoveCard($currentPlayer, "MYHAND", "MYBOTDECK", context:"Choose a card to put on the bottom of your deck");
        MZMoveCard($currentPlayer, "MYHAND", "MYBOTDECK", context:"Choose a card to put on the bottom of your deck");
      }
      break;
    case "0633620454"://Synchronized Strike
      $damage = SearchCount(SearchAllies($currentPlayer, arena:$additionalCosts));
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $damage . " damage to", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$damage,$currentPlayer", 1);
      break;
    case "1039828081"://Calculating MagnaGuard
      AddCurrentTurnEffect("1039828081", $currentPlayer, "PLAY");
      break;
    case "0056489820"://Unlimited Power
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "-");
      AddDecisionQueue("SETDQVAR", $currentPlayer, 0);
      for($i=4; $i>=1; --$i) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "dqVar=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal " . $i . " damage to", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, 1, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("APPENDDQVAR", $currentPlayer, 0, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$i,$currentPlayer", 1);
      }
      break;
    case "0741296536"://Ahsoka's Padawan Lightsaber
      if(CardTitle(GetMZCard($currentPlayer, $target)) == "Ahsoka Tano") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "3033790509"://Captain Typho
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give Sentinel");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "3033790509,PLAY", 1);
      break;
    case "4489623180"://Ziro the Hutt
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to exhaust");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "7579458834"://Reprocess
      AddDecisionQueue("FINDINDICES", $currentPlayer, "GY");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "4-");
      AddDecisionQueue("MULTICHOOSEDISCARD", $currentPlayer, "<-");
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "RESTOCK", 1);
      for($i=0; $i<4; ++$i) {
        CreateBattleDroid($currentPlayer);
      }
      break;
    case "8414572243"://Enfys Nest (Champion of Justice)
      $enfyAlly = new Ally($uniqueId);
      $enfyBouncePower = $enfyAlly->CurrentPower() - 1;
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:maxAttack=$enfyBouncePower");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to bounce");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "BOUNCE", 1);
      break;
    case "7979348081"://Kraken
      CreateBattleDroid($currentPlayer);
      CreateBattleDroid($currentPlayer);
      break;
    case "1272825113"://In Defense of Kamino
      $allies = &GetAllies($currentPlayer);
      for ($i = 0; $i < count($allies); $i += AllyPieces()) {
        if (TraitContains($allies[$i], "Republic")) {
          AddCurrentTurnEffect("1272825113", $currentPlayer, "PLAY", $allies[$i+5]);
        }
      }
      break;
    case "9415708584"://Pyrrhic Assault
      $allies = &GetAllies($currentPlayer);
      for ($i = 0; $i < count($allies); $i += AllyPieces()) {
        AddCurrentTurnEffect("9415708584", $currentPlayer, "PLAY", $allies[$i+5]);
      }
      break;
    case "9399634203"://I Have the High Ground
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "WRITECHOICE", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "9399634203,HAND", 1);
      break;
    case "1167572655"://Planetary Invasion
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("OP", $currentPlayer, "MZTONORMALINDICES");
      AddDecisionQueue("PREPENDLASTRESULT", $currentPlayer, "3-", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose up to 3 units to ready", 1);
      AddDecisionQueue("MULTICHOOSEUNIT", $currentPlayer, "<-", 1, 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "PLANETARYINVASION", 1);
      break;
    case "4033634907"://No Disintegrations
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal all but one damage to", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "NODISINTEGRATIONS", 1);
      break;
    case "2012334456"://On Top of Things
      $ally = new Ally($target, $currentPlayer);
      $ally->AddEffect("2012334456", "PLAY");
      break;
    case "5610901450"://Heroes on Both Sides
      //Republic
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Republic&THEIRALLY:trait=Republic");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give +2/+2", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5610901450,PLAY", 1);
      //Separatist
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Separatist&THEIRALLY:trait=Separatist");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to give +2/+2", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5610901450,PLAY", 1);
      break;
    case "7732981122"://Sly Moore
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "token=0", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to take control of", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "7732981122", 1);
      break;
    case "8719468890"://Sword and Shield Maneuver
      AddCurrentTurnEffect("8719468890", $currentPlayer, "PLAY");
      break;
    case "3459567689"://Wartime Profiteering
      global $CS_NumAlliesDestroyed;
      $numDefeated = GetClassState(1, $CS_NumAlliesDestroyed) + GetClassState(2, $CS_NumAlliesDestroyed);
      AddDecisionQueue("SEARCHDECKTOPX", $currentPlayer, $numDefeated . ";1;");
      AddDecisionQueue("MULTIADDHAND", $currentPlayer, "-", 1);
      AddDecisionQueue("REVEALCARDS", $currentPlayer, "-", 1);
      break;
    //Jump to Lightspeed
    case "0425156332"://Planetary Bombardment
      $hasCapitalShip = SearchCount(SearchAllies($currentPlayer, trait:"Capital Ship")) > 0;
      $indirectAmount = $hasCapitalShip ? 12 : 8;
      IndirectDamage($otherPlayer, $indirectAmount);
      break;
    case "2778554011"://General Draven
      CreateXWing($currentPlayer);
      break;
    case "1303370295"://Death Space Skirmisher
      if (SearchCount(SearchAllies($currentPlayer, arena: "Space")) > 1) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      }
      break;
    case "1330473789"://Devastator
      IndirectDamage($otherPlayer, 4, true);
      break;
    case "2388374331"://Blue Leader
      AddDecisionQueue("YESNO", $currentPlayer, "Do you want to pay 2 to gain 2 experience tokens?", 1);
      AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
      AddDecisionQueue("PAYRESOURCES", $currentPlayer, "2", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "MYALLY-" . $playAlly->Index(), 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ADDEXPERIENCE", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "MOVEARENA,Ground", 1);
      break;
    case "4179470615"://Asajj Ventress
      $abilityName = GetResolvedAbilityName($cardID, $from);
      if($abilityName == "Damage") {
        AsajjVentressIWorkAlone($currentPlayer);
      }
      break;
    case "0926549684"://Resupply Carrier
      AddTopDeckAsResource($currentPlayer);
      break;
    case "8833191722"://Never Tell Me the Odds
      $damageAmount = 0;
      $cards = Mill(1, 3);
      if($cards != "") {
        $cards = explode(",", $cards);
        for($i=0; $i<count($cards); ++$i) {
          if(CardCostIsOdd($cards[$i])) ++$damageAmount;
        }
      }
      $cards = Mill(2, 3);
      if($cards != "") {
        $cards = explode(",", $cards);
        for($i=0; $i<count($cards); ++$i) {
          if(CardCostIsOdd($cards[$i])) ++$damageAmount;
        }
      }
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal $damageAmount damage to");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,$damageAmount,$currentPlayer,1", 1);
      break;
    case "4030832630"://Admiral Piett Leader
      if(GetResolvedAbilityName($cardID) == "Play") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:trait=Capital_Ship");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "0011262813"://Wedge Antilles Leader
      if(GetResolvedAbilityName($cardID) == "Play") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a pilot to play", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:keyword=Piloting", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, $cardID, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "3933322003"://Rose Tico Leader
      if(GetResolvedAbilityName($cardID) == "Heal") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle&THEIRALLY:trait=Vehicle");
        AddDecisionQueue("MZFILTER", $currentPlayer, "numAttacks=0");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a vehicle unit to heal");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "RESTORE,2", 1);
      }
      break;
    case "0616724418"://Han Solo Leader
      if(GetResolvedAbilityName($cardID) == "Odds") {
        $deck = new Deck($currentPlayer);
        if($deck->Reveal()) {
          $cardCost = CardCost($deck->Top());
        }
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardCost);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "HAN_SOLO_LEADER_JTL", 1);
      }
      break;
    case "3658069276"://Lando Calrissian Leader
      if(GetResolvedAbilityName($cardID) == "Play") {
        global $CS_AfterPlayedBy;
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to play");
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:definedType=Unit");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $cardID, 1);
        AddDecisionQueue("SETCLASSSTATE", $currentPlayer, $CS_AfterPlayedBy, 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "PLAYCARD", 1);
      }
      break;
    case "7514405173"://Admiral Ackbar Leader
      if(GetResolvedAbilityName($cardID) == "Exhaust") {
        AdmiralAckbarItsATrap($currentPlayer, flipped:false);
      }
      break;
    case "1519837763"://Shuttle ST-149
      if($from != "PLAY") {
        ShuttleST149($currentPlayer);
      }
      break;
    case "6648978613"://Fett's Firespray (Feared Silhouettte)
      $damage = ControlsNamedCard($currentPlayer, "Boba Fett") ? 2 : 1;
      IndirectDamage($otherPlayer, $damage, true);
      break;
    case "4819196588"://Electromagnetic Pulse
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle&THEIRALLY:trait=Vehicle&MYALLY:trait=Droid&THEIRALLY:trait=Droid");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a DROID or VEHICLE unit to deal 2 damage and exhaust");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
      break;
    case "3722493191"://IG-2000
       if($from != "PLAY") {
        AddDecisionQueue("FINDINDICES", $currentPlayer, "ALLTHEIRUNITSMULTI");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose units to damage", 1);
        AddDecisionQueue("MULTICHOOSETHEIRUNIT", $currentPlayer, "<-", 1);
        AddDecisionQueue("MULTIDISTRIBUTEDAMAGE", $currentPlayer, "3,1,1", 1);
      }
        break;
    case "0964312065"://It's A Trap!
      $spaceAllies = SearchAllies($currentPlayer, arena:"Space");
      $spaceEnemiesCount = SearchCount(SearchAllies($otherPlayer, arena:"Space"));
      if(SearchCount($spaceAllies) < $spaceEnemiesCount) {
        $spaceAllies = explode(",", $spaceAllies);
        for($i=0;$i<count($spaceAllies);++$i) {
          $ally = new Ally("MYALLY-" . $spaceAllies[$i]);
          $ally->Ready();
        }
      }
    case "6421006753"://The Mandalorian
      if($from != "PLAY" && SearchCount(SearchAlliesForCard($currentPlayer, "6421006753")) > 0) {
        for ($i = 0; $i < 2; $i++) {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Ground&THEIRALLY:arena=Ground");
          AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to exhaust");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "REST", 1);
        }
      }
      break;
    case "7924461681"://Leia Organa
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Pilot&MYALLY:hasPilotOnly=1");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card to attack with");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "LEIA_JTL", 1);
      }
      break;
    case "8105698374"://Commandeer
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle;maxCost=6&THEIRALLY:trait=Vehicle;maxCost=6");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("MZFILTER", $currentPlayer, "hasPilot=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to take control of", 1);
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "8105698374,HAND", 1);
      break;
    case "4334684518"://Tandem Assault
      AddCurrentTurnEffect($cardID . "-1", $currentPlayer);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
      AddDecisionQueue("MZFILTER", $currentPlayer, "status=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a space unit to attack with");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      break;
    case "5093056978"://Direct Hit
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:trait=Vehicle&MYALLY:trait=Vehicle");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a vehicle unit to defeat");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY", 1);
      break;
    case "5345999887"://Kijimi patrollers
      if($from != "PLAY") {
        CreateTieFighter($currentPlayer);
      }
      break;
    case "7072861308"://Profundity
      if($from != "PLAY") {
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose player to draw 1 card");
        AddDecisionQueue("BUTTONINPUT", $currentPlayer, "Yourself,Opponent");
        AddDecisionQueue("SPECIFICCARD", $currentPlayer, "PROFUNDITY", 1);
      }
      break;
    case "8656409691"://Rio Durant
      if(GetResolvedAbilityName($cardID) == "Attack") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to attack with");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("ADDCURRENTEFFECT", $currentPlayer, "8656409691", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "8943696478";//Admiral Holdo
      if(GetResolvedAbilityName($cardID) == "Buff") {
        AdmiralHoldoWereNotAlone($currentPlayer, flipped:false);
      }
      break;
    case "9695562265"://Koiogran Turn
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Fighter;maxAttack=6&MYALLY:trait=Transport;maxAttack=6&THEIRALLY:trait=Fighter;maxAttack=6&THEIRALLY:trait=Transport;maxAttack=6");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Fighter or Transport to ready");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      break;
    case "1965647391"://Blade Squadron B-Wing
      if($from != "PLAY") {
        $theirAllies = &GetAllies($otherPlayer);
        $numExhausted = 0;
        for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
        if($theirAllies[$i+1] == 1) ++$numExhausted;
        }
        if($numExhausted >= 3) {
          AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
          AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give a shield to");
          AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
          AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
        }
      }
      break;
    case "0766281795"://Luke Skywalker
      if(GetResolvedAbilityName($cardID) == "Deal Damage" && GetClassState($currentPlayer, $CS_NumFighterAttacks) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to deal 1 damage to");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,1,$currentPlayer", 1);
      }
      break;
    case "7661383869"://Darth Vader
      if(GetResolvedAbilityName($cardID) == "TIE Fighter" && GetClassState($currentPlayer, $CS_NumNonTokenVehicleAttacks) > 0) {
        CreateTieFighter($currentPlayer);
      }
      break;
    case "3132453342"://Captain Phasma
      if(GetResolvedAbilityName($cardID) == "Deal Damage" && GetClassState($currentPlayer, $CS_NumFirstOrderPlayed) > 0) {
        DealDamageAsync($otherPlayer, 1, "DAMAGE", "3132453342");
      }
      break;
    case "8174214418"://Turbolaser Salvo
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an arena to blast. ");
      AddDecisionQueue("BUTTONINPUT", $currentPlayer, "Ground,Space");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:arena=Space");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an attacking unit");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-");
      AddDecisionQueue("MZOP", $currentPlayer, "POWER");
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena={0}");
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "TURBOLASERSALVO", 1);
      break;
    case "9595057518"://Special Forces TIE Fighter
      if($from != "PLAY") {
        $theirSpaceCount = SearchCount(SearchAllies($otherPlayer, arena:"Space"));
        $mySpaceCount = SearchCount(SearchAllies($currentPlayer, arena:"Space"));
        if($theirSpaceCount > $mySpaceCount) {
          $playAlly->Ready();
        }
      }
      break;
    case "6854247423"://Tantive IV
      if($from != "PLAY") {
        CreateXWing($currentPlayer);
      }
      break;
    case "3427170256"://Captain Phasma Unit
      if($from != "PLAY") {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=First_Order&THEIRALLY:trait=First_Order");
        AddDecisionQueue("MZFILTER", $currentPlayer, "index=MYALLY-" . $playAlly->Index());
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a First Order unit to give +2/+2");
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDHEALTH,2", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "3427170256,PLAY", 1);
      }
      break;
    case "3885807284"://Fight Fire With Fire
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a friendly unit");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETARENA", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "1", 1);
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY:arena={1}", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose an enemy unit in the same arena", 1);
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "2", 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{0}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,3," . $currentPlayer . ",0,1,0" , 1);
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{2}", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,3," . $currentPlayer . ",0,1,0" , 1);
      break;
    case "0524529055"://Snap Wexley
      if($target == "-") AddCurrentTurnEffect("0524529055-P", $currentPlayer, from:$from);
      break;
    case "3567283316":
      if($from != "PLAY") {
        IndirectDamage($otherPlayer, 5, true);
      }
      break;
    case "0753794638"://Corvus
      if($from != "PLAY" && CountPilotUnitsAndPilotUpgrades($currentPlayer) > 0) {
        $options = "Move Pilot unit;Move Pilot upgrade;Pass";
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("CHOOSEOPTION", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("SHOWOPTIONS", $currentPlayer, "$cardID&$options", 1);
        AddDecisionQueue("MODAL", $currentPlayer, "CORVUS", 1);
      }
      break;
    case "8993849612"://Eject
      Draw($currentPlayer);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to eject a pilot from.");
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:hasPilotOnly=1&THEIRALLY:hasPilotOnly=1");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUPGRADES", 1);
      AddDecisionQueue("FILTER", $currentPlayer, "LastResult-include-trait-Pilot", 1);
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a pilot to eject.", 1);
      AddDecisionQueue("CHOOSECARD", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "MOVEPILOTUPGRADE", 1);
      break;
    case "0097256640"://TIE Ambush Squadron
      if($from != "PLAY") CreateTieFighter($currentPlayer);
      break;
    case "9810057689"://No Glory, Only Results
      AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "THEIRALLY");
      AddDecisionQueue("MZFILTER", $currentPlayer, "leader=1");
      AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to take control of");
      AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "TAKECONTROL", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "DESTROY", 1);
      break;
    case "2870117979"://Executor
      if($from != "PLAY") {
        CreateTieFighter($currentPlayer);
        CreateTieFighter($currentPlayer);
        CreateTieFighter($currentPlayer);
      }
      break;
    case "2711104544"://Guerilla Soldier
      if($from != "PLAY") {
        AddCurrentTurnEffect("2711104544", $currentPlayer, $from, $uniqueId);
        IndirectDamage($otherPlayer, 3, true);
        AddDecisionQueue("REMOVECURRENTEFFECT", $currentPlayer, "2711104544", 1);
      }
      break;
    case "7138400365"://The Invisible Hand JTL
      if($from != "PLAY") InvisibleHandJTL($currentPlayer);
      break;
    case "6600603122"://Massassi Tactical Officer
      if(GetResolvedAbilityName($cardID, $from) == "Fighter Attack") {
        AddCurrentTurnEffect($cardID, $currentPlayer);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Fighter");
        AddDecisionQueue("MZFILTER", $currentPlayer, "status=1", 1);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a Fighter to attack with", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ATTACK", 1);
      }
      break;
    case "9921128444"://General Hux
      if(GetResolvedAbilityName($cardID, $from) == "Draw" && GetClassState($currentPlayer, $CS_NumFirstOrderPlayed) > 0) {
        Draw($currentPlayer);
      }
      break;
    case "3436482269"://Dogfight
      AttackWithMyUnitEvenIfExhaustedNoBases($currentPlayer);
      break;
    case "8757741946"://Poe Dameron (One Hell of a Pilot)
      if($target == "-") {
        CreateXWing($currentPlayer);
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Attach Poe to a Vehicle?");
        AddDecisionQueue("YESNO", $currentPlayer, "-", 1);
        AddDecisionQueue("NOPASS", $currentPlayer, "-", 1);
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY:trait=Vehicle", 1);
        AddDecisionQueue("MZFILTER", $currentPlayer, "hasPilot=1", 1);
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
        AddDecisionQueue("SETDQVAR", $currentPlayer, "0", 1);
        AddDecisionQueue("PASSPARAMETER", $currentPlayer, $uniqueId, 1);
        AddDecisionQueue("MZOP", $currentPlayer, "MOVEPILOTUNIT", 1);
      }
      break;
    case "3905028200"://Admiral Trench
      if(GetResolvedAbilityName($cardID, $from) == "Rummage" && SearchCount(SearchHand($currentPlayer, minCost:3)) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYHAND:minCost=3");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a card costing 3 or more to discard");
        AddDecisionQueue("CHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZDESTROY", $currentPlayer, "-", 1);
        AddDecisionQueue("DRAW", $currentPlayer, "-", 1);
      }
      break;
    //PlayAbility End
    default: break;
  }
}

function AttackWithMyUnitEvenIfExhaustedNoBases($player) {
  global $CCS_CantAttackBase;
  AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY", 1);
  AddDecisionQueue("SETDQCONTEXT", $player, "Choose an ally to attack with");
  AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("SETDQVAR", $player, "0", 1);
  AddDecisionQueue("PASSPARAMETER", $player, 1, 1);
  AddDecisionQueue("SETCOMBATCHAINSTATE", $player, $CCS_CantAttackBase, 1);
  AddDecisionQueue("PASSPARAMETER", $player, "{0}", 1);
  AddDecisionQueue("MZOP", $player, "ATTACK", 1);
}

function ReadyResource($player, $amount=1) {
  $resourceCards = &GetResourceCards($player);
  $numReadied = 0;
  for($i=0; $i<count($resourceCards) && $numReadied < $amount; $i+=ResourcePieces()) {
    if($resourceCards[$i + 4] == 1) {
      ++$numReadied;
      $resourceCards[$i + 4] = 0;
    }
  }
}

function ExhaustResource($player, $amount=1) {
  $resourceCards = &GetResourceCards($player);
  $numExhausted = 0;
  for($i=0; $i<count($resourceCards) && $numExhausted < $amount; $i+=ResourcePieces()) {
    if($resourceCards[$i + 4] == 0) {
      ++$numExhausted;
      $resourceCards[$i + 4] = 1;
    }
  }
}

function AfterPlayedByAbility($cardID) {
  global $currentPlayer, $CS_AfterPlayedBy;
  SetClassState($currentPlayer, $CS_AfterPlayedBy, "-");
  $index = LastAllyIndex($currentPlayer);
  $ally = new Ally("MYALLY-" . $index, $currentPlayer);
  switch($cardID) {
    case "040a3e81f3"://Lando Calrissian Leader Unit
    case "5440730550"://Lando Calrissian
      AddDecisionQueue("OP", $currentPlayer, "ADDTOPDECKASRESOURCE");
      MZChooseAndDestroy($currentPlayer, "MYRESOURCES", context:"Choose a resource to destroy");
      break;
    case "9226435975"://Han Solo Red
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer", 1);
      break;
    case "a742dea1f1"://Han Solo Red Unit
        AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
        AddDecisionQueue("MZOP", $currentPlayer, "DEALDAMAGE,2,$currentPlayer,1", 1);
        break;
    case "3572356139"://Chewbacca (Walking Carpet)
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "3572356139,PLAY", 1);
      break;
    case "5494760041"://Galactic Ambition
      AddDecisionQueue("PASSPARAMETER", $currentPlayer, "{1}", 1);
      AddDecisionQueue("SPECIFICCARD", $currentPlayer, "GALACTICAMBITION", 1);
      break;
    case "4113123883"://Unnatural Life
    case "7270736993"://Unrefusable Offer
    case "3426168686"://Sneak Attack
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ");
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, $cardID . "-2,PLAY", 1);
      break;
    case "8117080217"://Admiral Ozzel
      $ally->Ready();
      $otherPlayer = $currentPlayer == 1 ? 2 : 1;
      AddDecisionQueue("SETDQCONTEXT", $otherPlayer, "Choose a unit to ready");
      AddDecisionQueue("MULTIZONEINDICES", $otherPlayer, "MYALLY");
      AddDecisionQueue("MAYCHOOSEMULTIZONE", $otherPlayer, "<-", 1);
      AddDecisionQueue("MZOP", $otherPlayer, "READY", 1);
      break;
    case "5696041568"://Triple Dark Raid
      AddDecisionQueue("OP", $currentPlayer, "GETLASTALLYMZ", 1);//TODO: this is breaking for Grievous Wheel Bike
      AddDecisionQueue("MZOP", $currentPlayer, "READY", 1);
      AddDecisionQueue("MZOP", $currentPlayer, "GETUNIQUEID", 1);
      AddDecisionQueue("ADDLIMITEDCURRENTEFFECT", $currentPlayer, "5696041568-2,HAND", 1);
      break;
    //Jump to Lightspeed
    case "3658069276"://Lando Calrissian Leader
      if(SearchCount(SearchAllies($currentPlayer, arena:"Space")) > 0 && SearchCount(SearchAllies($currentPlayer, arena:"Ground")) > 0) {
        AddDecisionQueue("MULTIZONEINDICES", $currentPlayer, "MYALLY&THEIRALLY");
        AddDecisionQueue("SETDQCONTEXT", $currentPlayer, "Choose a unit to give a shield", 1);
        AddDecisionQueue("MAYCHOOSEMULTIZONE", $currentPlayer, "<-", 1);
        AddDecisionQueue("MZOP", $currentPlayer, "ADDSHIELD", 1);
      }
      break;
    default: break;
  }
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

function ExhaustAllAllies($arena, $player)
{
  $allies = &GetAllies($player);
  for($i=0; $i<count($allies); $i+=AllyPieces())
  {
    $ally = new Ally("MYALLY-" . $i, $player);
    if($ally->CurrentArena() == $arena) {
      $ally->Exhaust();
    }
  }
}

function DestroyAllAllies($player="")
{
  //To avoid problems to do with allies entering play in the middle of things(i.e. captives), we first note the uniqueID of every ally in play and then destroy only those noted.
  global $currentPlayer;
  //Get all uniqueIDs of allies that are on board right now.
  $currentPlayerAllies = &GetAllies($currentPlayer);
  $currentPlayerAlliesUniqueIDs = [];
  if($player == "" || $player == $currentPlayer) {
    for($i = 0; $i < count($currentPlayerAllies); $i += AllyPieces()) {
      $currentPlayerAlliesUniqueIDs[] = $currentPlayerAllies[$i+5];
    }
  }
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $otherPlayerAllies = &GetAllies($otherPlayer);
  $otherPlayerAlliesUniqueIDs = [];
  if($player == "" || $player != $currentPlayer) {
    for($i  = 0; $i < count($otherPlayerAllies); $i += AllyPieces()) {
      $otherPlayerAlliesUniqueIDs[] = $otherPlayerAllies[$i+5];
    }
  }

  //Destroy all those allies.
  $cacheTriggers = [];

  foreach ($currentPlayerAlliesUniqueIDs as $UID) {
    $ally = new Ally($UID, $currentPlayer);
    $triggers = GetAllyWhenDestroyTheirsEffects($player, $otherPlayer, $ally->UniqueID(), $ally->IsUnique(), $ally->IsUpgraded(), $ally->GetUpgrades(withMetadata:true));
    if(count($triggers) > 0) {
      $cacheTriggers[] = $triggers;
    }
  }

  foreach ($otherPlayerAlliesUniqueIDs as $UID) {
    $ally = new Ally($UID, $otherPlayer);
    $ally->Destroy();
  }
  foreach ($currentPlayerAlliesUniqueIDs as $UID) {
    $ally = new Ally($UID, $currentPlayer);
    $ally->Destroy();
  }

  if(count($cacheTriggers) > 0) {
    foreach ($cacheTriggers as $triggers) {
      LayerTheirsDestroyedTriggers($otherPlayer, $triggers);
    }
  }
}

function DamagePlayerAllies($player, $damage, $source, $type="-", $arena="")
{
  $enemyDamage = false;
  $fromUnitEffect = false;
  switch($source) {
    case "0160548661"://Fallen Lightsaber
    case "0683052393"://Hevy
    case "0354710662"://Saw Gerrera (Resistance Is Not Terrorism)
      $enemyDamage = true;
      $fromUnitEffect = true;
      break;
    default: break;
  }

  $allies = &GetAllies($player);
  for($i=count($allies)-AllyPieces(); $i>=0; $i-=AllyPieces())
  {
    if($arena != "" && !ArenaContains($allies[$i], $arena, $player)) continue;
    $ally = new Ally("MYALLY-" . $i, $player);
    $ally->DealDamage($damage, enemyDamage: $enemyDamage, fromUnitEffect: $fromUnitEffect);
  }
}

function DamageAllAllies($amount, $source, $alsoRest=false, $alsoFreeze=false, $arena="", $except="")
{
  global $currentPlayer;
  $otherPlayer = $currentPlayer == 1 ? 2 : 1;
  $theirAllies = &GetAllies($otherPlayer);
  for($i=count($theirAllies) - AllyPieces(); $i>=0; $i-=AllyPieces())
  {
    if(!ArenaContains($theirAllies[$i], $arena, $otherPlayer)) continue;
    if($alsoRest) $theirAllies[$i+1] = 1;
    if($alsoFreeze) $theirAllies[$i+3] = 1;
    $ally = new Ally("THEIRALLY-$i");
    $ally->DealDamage($amount);
  }
  $allies = &GetAllies($currentPlayer);
  for($i=count($allies) - AllyPieces(); $i>=0; $i-=AllyPieces())
  {
    if(!ArenaContains($allies[$i], $arena, $currentPlayer)) continue;
    if($except != "" && $except == ("MYALLY-" . $i)) continue;
    if($alsoRest) $allies[$i+1] = 1;
    if($alsoFreeze) $allies[$i+3] = 1;
    $ally = new Ally("MYALLY-$i");
    $ally->DealDamage($amount);
  }
}



// function IsHarmonizeActive($player)//FAB
// {
//   global $CS_NumMelodyPlayed;
//   return GetClassState($player, $CS_NumMelodyPlayed) > 0;
// }

function IsMultiTargetAttackActive() {
  global $combatChainState, $CCS_MultiAttackTargets;
  //TODO: look into why SubmitSideboard.php is not initializing this
  return isset($combatChainState[$CCS_MultiAttackTargets]) && $combatChainState[$CCS_MultiAttackTargets]!=="-";
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
  $cards = "";
  $deck = &GetDeck($player);
  if($amount > count($deck)) $amount = count($deck);
  for($i=0; $i<$amount; ++$i)
  {
    $card = array_shift($deck);
    if($cards != "") $cards .= ",";
    $cards .= $card;
    AddGraveyard($card, $player, "DECK");
  }
  return $cards;
}

function AddTopDeckAsResource($player, $isExhausted=true)
{
  $deck = &GetDeck($player);
  if(count($deck) > 0) {
    $card = array_shift($deck);
    AddResources($card, $player, "DECK", "DOWN", isExhausted:($isExhausted ? 1 : 0));
    return true;
  }

  return false;
}

//target type return values
//-1: no target
// 0: My Hero + Their Hero
// 1: Their Hero only
// 2: Any Target
// 3: Their Hero + Their Allies
// 4: My Hero only (For afflictions)
// 6: Any unit
// 7: Friendly unit
// 8: Any Non-Leader + Non-Vehicle unit
function PlayRequiresTarget($cardID)
{
  global $currentPlayer;
  switch($cardID)
  {
    case "8679831560": return 2;//Repair
    case "8981523525": return 6;//Moment of Peace
    case "0867878280": return 6;//It Binds All Things
    case "2587711125": return 6;//Disarm
    case "6515891401": return 7;//Karabast
    case "2651321164": return 6;//Tactical Advantage
    case "1900571801": return 7;//Overwhelming Barrage
    case "5013139687": return 3;//Caught In The Crossfire
    case "7861932582": return 6;//The Force is With Me
    case "2758597010": return 6;//Maximum Firepower
    case "2202839291": return 6;//Don't Get Cocky
    case "1701265931": return 6;//Moment of Glory
    case "3765912000": return 7;//Take Captive
    case "5778949819": return 7;//Relentless Pursuit
    case "1973545191": return 6;//Unexpected Escape
    case "0598830553": return 7;//Dryden Vos
    case "8576088385": return 6;//Detention Block Rescue
    default: return -1;
  }
}

  //target type return values
  //-1: no target
  // 0: My Hero + Their Hero
  // 1: Their Hero only
  // 2: Any Target
  // 3: Their Units
  // 4: My Hero only (For afflictions)
  // 6: Any unit
  // 7: Friendly unit
  // 8: Any Non-Leader + Non-Vehicle unit
  function GetArcaneTargetIndices($player, $target)
  {
    global $CS_ArcaneTargetsSelected;
    $otherPlayer = ($player == 1 ? 2 : 1);

    if ($target == 8) {
      $rvArr = [];
      $theirAllies = &GetAllies($otherPlayer);
      for($i=0; $i<count($theirAllies); $i+=AllyPieces()) {
        $cardID = $theirAllies[$i];
        if (CardIDIsLeader($cardID) || TraitContains($cardID, "Vehicle")) {
          continue;
        }

        $rvArr[] = "THEIRALLY-" . $i;
      }

      $myAllies = &GetAllies($player);
      for($i=0; $i<count($myAllies); $i+=AllyPieces()) {
        $cardID = $myAllies[$i];
        if (CardIDIsLeader($cardID) || TraitContains($cardID, "Vehicle")) {
          continue;
        }
        $rvArr[] = "MYALLY-" . $i;
      }

      return implode(",", $rvArr);
    } else if ($target == 4) return "MYCHAR-0";

    if($target != 3 && $target != 6 && $target != 7) $rv = "THEIRCHAR-0";
    else $rv = "";

    if(($target == 0 && !ShouldAutotargetOpponent($player)) || $target == 2)
    {
      $rv .= ",MYCHAR-0";
    }
    if($target == 2 || $target == 6)
    {
      $theirAllies = &GetAllies($otherPlayer);
      for($i=0; $i<count($theirAllies); $i+=AllyPieces())
      {
        if($rv != "") $rv .= ",";
        $rv .= "THEIRALLY-" . $i;
      }
      $myAllies = &GetAllies($player);
      for($i=0; $i<count($myAllies); $i+=AllyPieces())
      {
        if($rv != "") $rv .= ",";
        $rv .= "MYALLY-" . $i;
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
    } else if($target == 7) {
      $myAllies = &GetAllies($player);
      for($i=0; $i<count($myAllies); $i+=AllyPieces())
      {
        if($rv != "") $rv .= ",";
        $rv .= "MYALLY-" . $i;
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

function Draw($player, $mainPhase = true)
{
  global $EffectContext, $mainPlayer, $CS_CardsDrawn;
  $otherPlayer = ($player == 1 ? 2 : 1);
  $deck = &GetDeck($player);
  $hand = &GetHand($player);
  if(count($deck) == 0) {
    $char = &GetPlayerCharacter($player);
    if(count($char) > CharacterPieces() && $char[CharacterPieces()] != "DUMMY") WriteLog("Player " . $player . " took 3 damage for having no cards left in their deck.");
    DealDamageAsync($player, 3, "DAMAGE", "DRAW");
    return -1;
  }
  if(CurrentEffectPreventsDraw($player, $mainPhase)) return -1;
  $hand[] = array_shift($deck);
  PermanentDrawCardAbilities($player);
  $hand = array_values($hand);
  if($mainPhase) IncrementClassState($player, $CS_CardsDrawn);
  return $hand[count($hand) - 1];
}

function WakeUpChampion($player)
{
  $char = &GetPlayerCharacter($player);
  $char[1] = 2;
}

