<?php

function PutPermanentIntoPlay($player, $cardID)
{
  $permanents = &GetPermanents($player);
  $permanents[] = $cardID;
  return count($permanents) - PermanentPieces();
}

function RemovePermanent($player, $index)
{
  $index = intval($index);
  $permanents = &GetPermanents($player);
  $cardID = $permanents[$index];
  for ($j = $index + PermanentPieces() - 1; $j >= $index; --$j) {
    unset($permanents[$j]);
  }
  $permanents = array_values($permanents);
  return $cardID;
}

function DestroyPermanent($player, $index)
{
  if($index == -1) return;
  $index = intval($index);
  $permanents = &GetPermanents($player);
  $cardID = $permanents[$index];
  $isToken = $permanents[$index + 4] == 1;
  PermanentDestroyed($player, $cardID, $isToken);
  for ($j = $index + PermanentPieces() - 1; $j >= $index; --$j) {
    unset($permanents[$j]);
  }
  $permanents = array_values($permanents);
}

function PermanentDestroyed($player, $cardID, $isToken = false)
{
  $permanents = &GetPermanents($player);
  for ($i = 0; $i < count($permanents); $i += PermanentPieces()) {
    switch ($permanents[$i]) {
      default:
        break;
    }
  }
  $goesWhere = GoesWhereAfterResolving($cardID);
  if (CardType($cardID) == "T" || $isToken) return; //Don't need to add to anywhere if it's a token
  switch ($goesWhere) {
    case "GY":
      AddGraveyard($cardID, $player, "PLAY");
      break;
    case "SOUL":
      AddSoul($cardID, $player, "PLAY");
      break;
    case "BANISH":
      BanishCardForPlayer($cardID, $player, "PLAY", "NA");
      break;
    default:
      break;
  }
}

function PermanentBeginEndPhaseEffects()
{
  global $mainPlayer, $defPlayer;
  $permanents = &GetPermanents($mainPlayer);
  for ($i = count($permanents) - PermanentPieces(); $i >= 0; $i -= PermanentPieces()) {
    $remove = 0;
    switch ($permanents[$i]) {

      default:
        break;
    }
    if ($remove == 1) DestroyPermanent($mainPlayer, $i);
  }

  $permanents = &GetPermanents($defPlayer);
  for ($i = count($permanents) - PermanentPieces(); $i >= 0; $i -= PermanentPieces()) {
    $remove = 0;
    switch ($permanents[$i]) {

      default:
        break;
    }
    if ($remove == 1) DestroyPermanent($defPlayer, $i);
  }
}

function PermanentTakeDamageAbilities($player, $damage, $type)
{
  $permanents = &GetPermanents($player);
  $otherPlayer = $player == 1 ? 1 : 2;
  //CR 2.1 6.4.10f If an effect states that a prevention effect can not prevent the damage of an event, the prevention effect still applies to the event but its prevention amount is not reduced. Any additional modifications to the event by the prevention effect still occur.
  $preventable = CanDamageBePrevented($otherPlayer, $damage, $type);
  for ($i = count($permanents) - PermanentPieces(); $i >= 0; $i -= PermanentPieces()) {
    $remove = 0;
    switch ($permanents[$i]) {
      case "UPR439":
        if ($damage > 0) {
          if ($preventable) $damage -= 4;
          $remove = 1;
        }
        break;
      case "UPR440":
        if ($damage > 0) {
          if ($preventable) $damage -= 3;
          $remove = 1;
        }
        break;
      case "UPR441":
        if ($damage > 0) {
          if ($preventable) $damage -= 2;
          $remove = 1;
        }
        break;
      default:
        break;
    }
    if ($remove == 1) {
      if (HasWard($permanents[$i]) && SearchCharacterActive($player, "DYN213") && CardType($permanents[$i]) != "T") {
        $index = FindCharacterIndex($player, "DYN213");
        $char[$index + 1] = 1;
        GainResources($player, 1);
      }
      DestroyPermanent($player, $i);
    }
  }
  if ($damage <= 0) $damage = 0;
  return $damage;
}

function PermanentStartTurnAbilities()
{
  global $mainPlayer, $defPlayer;

}

function PermanentPlayAbilities($attackID, $from="")
{
  global $mainPlayer, $actionPoints;
  $permanents = &GetPermanents($mainPlayer);
  for ($i = count($permanents) - PermanentPieces(); $i >= 0; $i -= PermanentPieces()) {
    $remove = 0;
    switch($permanents[$i]) {

      default:
        break;
    }
  }
}

function PermanentAddAttackAbilities()
{
  global $mainPlayer;
  $amount = 0;
  $permanents = &GetPermanents($mainPlayer);
  for ($i = count($permanents) - PermanentPieces(); $i >= 0; $i -= PermanentPieces()) {
    switch($permanents[$i]) {
      case "ROGUE705":
        $amount += 1;
        break;
      default:
        break;
    }
  }
  return $amount;
}

function PermanentDrawCardAbilities($player)
{
  global $mainPlayer, $defPlayer, $currentPlayer;
  $permanents = &GetPermanents($mainPlayer);
  for($i = count($permanents) - PermanentPieces(); $i >= 0; $i -= PermanentPieces()) {
    switch($permanents[$i]) {
      case "ROGUE601":
        if($mainPlayer == $player) AddCurrentTurnEffect($permanents[$i], $mainPlayer);
        break;
      default:
        break;
    }
  }
}

?>
