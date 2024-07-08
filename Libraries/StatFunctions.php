<?php

function CardStatPieces()
{
  return 4;
}

function TurnStatPieces()
{
  return 12;
}

$CardStats_TimesPlayed = 1;
$CardStats_TimesActivated = 2;
$CardStats_TimesResourced = 3;

$TurnStats_DamageThreatened = 0;
$TurnStats_DamageDealt = 1;
$TurnStats_CardsPlayedOffense = 2;
$TurnStats_CardsPlayedDefense = 3;
$TurnStats_CardsPitched = 4;
$TurnStats_CardsBlocked = 5;
$TurnStats_ResourcesUsed = 6;
$TurnStats_ResourcesLeft = 7;
$TurnStats_CardsLeft = 8;
$TurnStats_DamageBlocked = 9;
$TurnStats_Overblock = 10;

function LogPlayCardStats($player, $cardID, $from, $type="")
{
  global $turn, $currentRound, $CardStats_TimesPlayed, $CardStats_TimesActivated, $CardStats_TimesResourced;
  global $TurnStats_CardsPitched, $TurnStats_CardsBlocked, $mainPlayer;
  if($type == "") $type = $turn[0];
  $cardStats = &GetCardStats($player);
  $turnStats = &GetTurnStats($player);
  $baseIndex = ($currentRound-1) * TurnStatPieces();
  $found = 0;
  $i = 0;
  for($i = 0; $i<count($cardStats) && !$found; $i += CardStatPieces())
  {
    if($cardStats[$i] == $cardID) { $found = 1; $i -= CardStatPieces(); }
  }
  if(!$found) array_push($cardStats, $cardID, 0, 0, 0);
  switch($type)
  {
    case "P": ++$cardStats[$i + $CardStats_TimesResourced]; ++$turnStats[$baseIndex + $TurnStats_CardsPitched]; break;
    case "B": if($from != "PLAY" && $from != "EQUIP") ++$turnStats[$baseIndex + $TurnStats_CardsBlocked]; break;
    case "RESOURCED": ++$cardStats[$i + $CardStats_TimesResourced]; break;
    default:
      if ($from != "PLAY")
      {
        // From "PLAY" means it was already played, don't account for it a second time.
        ++$cardStats[$i + $CardStats_TimesPlayed];
      }
      else ++$cardStats[$i + $CardStats_TimesActivated];
      break;
  }
}

function LogResourcesUsedStats($player, $resourcesUsed)
{
  global $currentRound, $TurnStats_ResourcesUsed;
  $turnStats = &GetTurnStats($player);
  $baseIndex = ($currentRound-1) * TurnStatPieces();
  if(count($turnStats) <= $baseIndex) StatsStartTurn();
  $turnStats[$baseIndex + $TurnStats_ResourcesUsed] += $resourcesUsed;
}

function LogDamageStats($player, $damageThreatened, $damageDealt)
{
  global $currentRound, $TurnStats_DamageThreatened, $TurnStats_DamageDealt;
  $baseIndex = ($currentRound-1) * TurnStatPieces();
  $damagerStats = &GetTurnStats($player == 1 ? 2 : 1);
  if(count($damagerStats) <= $baseIndex) StatsStartTurn();
  $damagerStats[$baseIndex + $TurnStats_DamageThreatened] += $damageThreatened;
  $damagerStats[$baseIndex + $TurnStats_DamageDealt] += $damageDealt;
}

function LogCombatResolutionStats($damageThreatened, $damageBlocked)
{
  global $currentRound, $mainPlayer, $defPlayer, $TurnStats_DamageThreatened, $TurnStats_DamageBlocked, $TurnStats_Overblock;
  $baseIndex = ($currentRound-1) * TurnStatPieces();
  $mainStats = &GetTurnStats($mainPlayer);
  $defStats = &GetTurnStats($defPlayer);
  if(count($mainStats) <= $baseIndex) StatsStartTurn();
  if(count($defStats) <= $baseIndex) StatsStartTurn();
  $mainStats[$baseIndex + $TurnStats_DamageThreatened] += min($damageThreatened, $damageBlocked);//Excess is logged in the damage function
  $defStats[$baseIndex + $TurnStats_DamageBlocked] += $damageBlocked;
  $defStats[$baseIndex + $TurnStats_Overblock] += $damageBlocked > $damageThreatened ? $damageBlocked - $damageThreatened : 0;
}

function LogEndTurnStats($player)
{
  global $currentRound, $TurnStats_ResourcesLeft, $TurnStats_CardsLeft;
  $turnStats = &GetTurnStats($player);
  $baseIndex = ($currentRound-1) * TurnStatPieces();
  if(count($turnStats) <= $baseIndex) StatsStartTurn();
  $resources = &GetResources($player);
  $turnStats[$baseIndex + $TurnStats_ResourcesLeft] = $resources[0];
  $hand = &GetHand($player);
  $turnStats[$baseIndex + $TurnStats_CardsLeft] = count($hand);
}

function StatsStartTurn()
{
  $p1Stats = &GetTurnStats(1);
  $p2Stats = &GetTurnStats(2);
  for($i=0; $i<TurnStatPieces(); ++$i)
  {
    $p1Stats[] = 0;
    $p2Stats[] = 0;
  }
}

?>
