<?php

function MZAttach($player, $mzIndex, $cardID) {
  $mzArr = explode("-", $mzIndex);
  $otherPlayer = ($player == 1 ? 2 : 1);
  switch($mzArr[0]) {
    case "MYALLY":
      $ally = new Ally($mzIndex, $player);
      $ally->Attach($cardID, $player);
      break;
    case "THEIRALLY":
      $ally = new Ally($mzIndex, $otherPlayer);
      $ally->Attach($cardID, $player);
      break;
    default: break;
  }
}

function MZDestroy($player, $lastResult)
{
  $lastResultArr = explode(",", $lastResult);
  $otherPlayer = ($player == 1 ? 2 : 1);
  for ($i = 0; $i < count($lastResultArr); ++$i) {
    $mzIndex = explode("-", $lastResultArr[$i]);
    switch ($mzIndex[0]) {
      case "MYHAND": $lastResult = DiscardCard($player, $mzIndex[1]); break;
      case "THEIRHAND": $lastResult = DiscardCard($otherPlayer, $mzIndex[1]); break;
      case "MYCHAR": $lastResult = DestroyCharacter($player, $mzIndex[1]); break;
      case "THEIRCHAR": $lastResult = DestroyCharacter($otherPlayer, $mzIndex[1]); break;
      case "MYALLY":
        $ally = new Ally("MYALLY-" . $mzIndex[1], $player);
        $lastResult = $ally->Destroy();
        break;
      case "THEIRALLY":
        $ally = new Ally("MYALLY-" . $mzIndex[1], $otherPlayer);
        $lastResult = $ally->Destroy();
        break;
      case "MYAURAS": $lastResult = DestroyAura($player, $mzIndex[1]); break;
      case "THEIRAURAS": $lastResult = DestroyAura($otherPlayer, $mzIndex[1]); break;
      case "MYITEMS": $lastResult = DestroyItemForPlayer($player, $mzIndex[1]); break;
      case "THEIRITEMS": $lastResult = DestroyItemForPlayer($otherPlayer, $mzIndex[1]); break;
      case "MYARS": case "MYRESOURCES": $lastResult = DestroyArsenal($player, $mzIndex[1]); break;
      case "THEIRARS": $lastResult = DestroyArsenal($otherPlayer, $mzIndex[1]); break;
      default: break;
    }
  }
  return $lastResult;
}

function MZRemove($player, $lastResult)
{
  $lastResultArr = explode(",", $lastResult);
  $otherPlayer = ($player == 1 ? 2 : 1);
  for($i = 0; $i < count($lastResultArr); ++$i) {
    $mzIndex = explode("-", $lastResultArr[$i]);
    switch($mzIndex[0]) {
      case "MYCHAR": $lastResult = RemoveCharacter($player, $mzIndex[1]); break;
      case "MYITEMS": $lastResult = DestroyItemForPlayer($player, $mzIndex[1], true); break;
      case "MYDISCARD": $lastResult = RemoveGraveyard($player, $mzIndex[1]); break;
      case "THEIRDISCARD": $lastResult = RemoveGraveyard($otherPlayer, $mzIndex[1]); break;
      case "THEIRITEMS": $lastResult = DestroyItemForPlayer($otherPlayer, $mzIndex[1], true); break;
      case "MYBANISH": RemoveBanish($player, $mzIndex[1]); break;
      case "THEIRBANISH": RemoveBanish($otherPlayer, $mzIndex[1]); break;
      case "MYALLY": $lastResult = RemoveAlly($player, $mzIndex[1]); break;
      case "THEIRALLY": $lastResult = RemoveAlly($otherPlayer, $mzIndex[1]); break;
      case "MYRESOURCES": $lastResult = RemoveArsenal($player, $mzIndex[1]); break;
      case "THEIRRESOURCES": $lastResult = RemoveArsenal($otherPlayer, $mzIndex[1]); break;
      case "MYPITCH": RemovePitch($player, $mzIndex[1]); break;
      case "THEIRPITCH": RemovePitch($otherPlayer, $mzIndex[1]); break;
      case "MYHAND": $lastResult = RemoveHand($player, $mzIndex[1]); break;
      case "THEIRHAND": $lastResult = RemoveHand($otherPlayer, $mzIndex[1]); break;
      case "THEIRAURAS": RemoveAura($otherPlayer, $mzIndex[1]); break;
      case "MYMEMORY": RemoveMemory($player, $mzIndex[1]); break;
      case "THEIRMEMORY": RemoveMemory($otherPlayer, $mzIndex[1]); break;
      case "MYDECK":
        $deck = new Deck($player);
        return $deck->Remove($mzIndex[1]);
        break;
      default: break;
    }
  }
  return $lastResult;
}

function MZGetUniqueID($mzIndex, $player)
{
  $mzArr = explode("-", $mzIndex);
  $zone = &GetMZZone($player, $mzArr[0]);
  switch($mzArr[0]) {
    case "ALLY": case "MYALLY": case "THEIRALLY": return $zone[$mzArr[1] + 5];
    case "BANISH": case "MYBANISH": case "THEIRBANISH": return $zone[$mzArr[1] + 2];
    default: return "-";
  }
}

function MZDiscard($player, $parameter, $lastResult)
{
  $lastResultArr = explode(",", $lastResult);
  $otherPlayer = ($player == 1 ? 2 : 1);
  $params = explode(",", $parameter);
  $handDiscard = false;
  for($i = 0; $i < count($lastResultArr); ++$i) {
    $mzIndex = explode("-", $lastResultArr[$i]);
    $cardOwner = (str_starts_with($mzIndex[0], "MY") ? $player : $otherPlayer);
    $zone = &GetMZZone($cardOwner, $mzIndex[0]);
    $cardID = $zone[$mzIndex[1]];
    AddGraveyard($cardID, $cardOwner, $params[0]);
    WriteLog(CardLink($cardID, $cardID) . " was discarded");
    if(!$handDiscard && str_ends_with($mzIndex[0], "HAND")) {
      $handDiscard = true;
    }
  }
  //At the moment discardedID is not used anywhere
  if($handDiscard) AllyCardDiscarded($player, "");
  return $lastResult;
}

function MZAddZone($player, $parameter, $lastResult)
{
  //TODO: Add "from", add more zones
  $lastResultArr = explode(",", $lastResult);
  $otherPlayer = ($player == 1 ? 2 : 1);
  $params = explode(",", $parameter);
  $cardIDs = [];
  for($i = 0; $i < count($lastResultArr); ++$i) {
    $mzIndex = explode("-", $lastResultArr[$i]);
    $cardOwner = (str_starts_with($mzIndex[0], "MY") ? $player : $otherPlayer);
    $zone = &GetMZZone($cardOwner, $mzIndex[0]);
    $cardIDs[] = $zone[$mzIndex[1]];
  }
  for($i=0; $i<count($cardIDs); ++$i)
  {
    switch($params[0])
    {
      case "MYBANISH": BanishCardForPlayer($cardIDs[$i], $player, $params[1], $params[2]); break;
      case "MYHAND": AddPlayerHand($cardIDs[$i], $player, "-"); break;
      case "MYRESOURCES": AddResources($cardIDs[$i], $player, "HAND", "DOWN"); break;
      case "MYTOPDECK": AddTopDeck($cardIDs[$i], $player, "-"); break;
      case "MYBOTDECK": AddBottomDeck($cardIDs[$i], $player, "-"); break;
      case "THEIRBOTDECK": AddBottomDeck($cardIDs[$i], $otherPlayer, "-"); break;
      case "MYMEMORY": AddMemory($cardIDs[$i], $player, $params[1], $params[2]); break;
      case "THEIRMATERIAL": AddMaterial($cardIDs[$i], $otherPlayer, $params[1]); break;
      case "THEIRRESOURCES": AddResources($cardIDs[$i], $player, "HAND", "DOWN"); break;
      case "THEIRBANISH": BanishCardForPlayer($cardIDs[$i], $otherPlayer, $params[1], $params[2]); break;
      case "MYDISCARD":
        $from = $params[1];
        AddGraveyard($cardIDs[$i], $player, "-", $from);
        if($from == "HAND") CardDiscarded($player, $cardIDs[$i]);
        break;
      case "THEIRDISCARD":
        $from = $params[1];
        AddGraveyard($cardIDs[$i], $otherPlayer, "-", $from);
        if($from == "HAND") CardDiscarded($otherPlayer, $cardIDs[$i]);
        break;
      case "MYALLY": PlayAlly($cardIDs[$i], $player, from:$from); break;
      case "THEIRALLY": PlayAlly($cardIDs[$i], $otherPlayer); break;
      default: break;
    }
  }
  return $lastResult;
}

function MZPlayCard($player, $mzIndex) {
  global $CS_CharacterIndex, $CS_PlayIndex;
  $mzArr = explode("-", $mzIndex);
  //GetMZZone doesn't respect MY/THEIR differences, and changing it to do so messes up attacking, so I'm adding this check here for now.
  if(substr($mzArr[0], 0, 5) == "THEIR") $zone = &GetMZZone($player == 1 ? 2 : 1, $mzArr[0]);
  else $zone = &GetMZZone($player, $mzArr[0]);
  $cardID = $zone[$mzArr[1]];
  $from = preg_replace('/^(MY|THEIR)/', '', $mzArr[0]);
  MZRemove($player, $mzIndex);
  SetClassState($player, $CS_CharacterIndex, $mzArr[1]);
  SetClassState($player, $CS_PlayIndex, $mzArr[1]);
  PlayCard($cardID, $from, -1, $mzArr[1]);
  return $cardID;
}

function MZAttack($player, $mzIndex)
{
  global $CS_CharacterIndex, $CS_PlayIndex, $CS_AbilityIndex, $currentPlayer, $mainPlayer, $defPlayer;
  $currentPlayer = $player;
  $mainPlayer = $player;
  $defPlayer = ($player == 1 ? 2 : 1);
  $ally = new Ally($mzIndex, $player);
  $ally->Exhaust();
  SetClassState($player, $CS_CharacterIndex, $ally->Index());
  SetClassState($player, $CS_PlayIndex, $ally->Index());
  $abilityIndex = GetAbilityIndex($ally->CardID(), $ally->Index(), "Attack");
  SetClassState($player, $CS_AbilityIndex, $abilityIndex);
  PlayCard($ally->CardID(), "PLAY", -1, $ally->Index(), $ally->UniqueID(), skipAbilityType:true);
}

function MZUndestroy($player, $parameter, $lastResult)
{
  $lastResultArr = explode(",", $lastResult);
  $params = explode(",", $parameter);
  $otherPlayer = ($player == 1 ? 2 : 1);
  for($i = 0; $i < count($lastResultArr); ++$i) {
    $mzIndex = explode("-", $lastResultArr[$i]);
    switch ($mzIndex[0]) {
      case "MYCHAR":
        UndestroyCharacter($player, $mzIndex[1]);
        break;
      default: break;
    }
  }
  return $lastResult;
}

function MZBanish($player, $parameter, $lastResult)
{
  $lastResultArr = explode(",", $lastResult);
  $params = explode(",", $parameter);
  $otherPlayer = ($player == 1 ? 2 : 1);
  for($i = 0; $i < count($lastResultArr); ++$i) {
    $mzIndex = explode("-", $lastResultArr[$i]);
    $cardOwner = (str_starts_with($mzIndex[0], "MY") ? $player : $otherPlayer);
    $zone = &GetMZZone($cardOwner, $mzIndex[0]);
    BanishCardForPlayer($zone[$mzIndex[1]], $cardOwner, $params[0], $params[1], $params[2]);
  }
  if(count($params) <= 3) WriteLog(CardLink($zone[$mzIndex[1]], $zone[$mzIndex[1]]) . " was banished");
  return $lastResult;
}

function MZGainControl($player, $target)
{
  $targetArr = explode("-", $target);
  switch($targetArr[0])
  {
    case "MYITEMS": case "THEIRITEMS": StealItem(($player == 1 ? 2 : 1), $targetArr[1], $player); break;
    default: break;
  }
}

function MZHealAlly($player, $target, $amount)
{
  $ally = new Ally($target, $player);
  $ally->Heal($amount);
}

function MZAddHealth($player, $target, $amount=1)
{
  $mzArr = explode("-", $target);
  if($mzArr[0] == "MYALLY") $ally = new Ally($target, $player);
  else $ally = new Ally($target, $player == 1 ? 2 : 1);
  $ally->AddRoundHealthModifier($amount);
}

function MZFreeze($target)
{
  global $currentPlayer;
  $pieces = explode("-", $target);
  $player = (str_starts_with($pieces[0], "MY") ? $currentPlayer : ($currentPlayer == 1 ? 2 : 1));
  $zone = &GetMZZone($player, $pieces[0]);
  switch ($pieces[0]) {
    case "THEIRCHAR": case "MYCHAR":
      $zone[$pieces[1]+8] = 1;
      break;
    case "THEIRALLY": case "MYALLY":
      $zone[$pieces[1]+3] = 1;
      break;
    case "THEIRARS": case "MYARS":
      $zone[$pieces[1]+4] = 1;
      break;
    default:
      break;
  }
}

function MZRest($player, $target)
{
  $pieces = explode("-", $target);
  $player = (str_starts_with($pieces[0], "MY") ? $player : ($player == 1 ? 2 : 1));
  $zone = &GetMZZone($player, $pieces[0]);
  switch($pieces[0]) {
    case "MYCHAR": case "THEIRCHAR":
      $zone[$pieces[1]+1] = 1;
      break;
    case "THEIRALLY": case "MYALLY":
      $zone[$pieces[1]+1] = 1;
      break;
    case "MYITEMS": case "THEIRITEMS":
      $zone[$pieces[1]+2] = 1;
      break;
    case "MYAURAS": case "THEIRAURAS":
      $zone[$pieces[1]+1] = 1;
      break;
    case "MYRESOURCES": case "THEIRRESOURCES":
      $zone[$pieces[1]+4] = 1;
      break;
    default: break;
  }
}

function MZWakeUp($player, $target)
{
  $pieces = explode("-", $target);
  $player = (str_starts_with($pieces[0], "MY") ? $player : ($player == 1 ? 2 : 1));
  $zone = &GetMZZone($player, $pieces[0]);

  if(SearchLimitedCurrentTurnEffects("8800836530", $player) == $target) { // No Good to me Dead
    return;
  }

  switch($pieces[0]) {
    case "MYCHAR": case "THEIRCHAR":
    case "THEIRALLY": case "MYALLY":
      $zone[$pieces[1]+1] = 2;
      break;
    default: break;
  }
}

function MZBounce($player, $target)
{
  global $CS_NumLeftPlay;
  $mzArr = explode("-", $target);
  $controller = (str_starts_with($mzArr[0], "MY") ? $player : ($player == 1 ? 2 : 1));
  $zone = &GetMZZone($controller, $mzArr[0]);
  switch($mzArr[0]) {
    case "THEIRALLY": case "MYALLY":
      $allies = &GetAllies($controller);
      $owner = $allies[$mzArr[1]+11];
      $cardID = RemoveAlly($controller, $mzArr[1]);
      IncrementClassState($controller, $CS_NumLeftPlay);
      $index = AddHand($owner, $cardID);
      return $player == $owner ? "MYHAND-" . $index : "THEIRHAND-" . $index;
    case "MYRESOURCES": case "THEIRRESOURCES":
      $cardID = RemoveResource($controller, $mzArr[1]);
      //TODO : to fix opponent card in my resources (Traitorous + SLT) we need to add owner information on resources
      $owner = $player;
      $index = AddHand($owner, $cardID);
      return str_starts_with($mzArr[0], "MY") ? "MYHAND-" . $index : "THEIRHAND-" . $index;
    default: break;
  }
  return -1;
}

function MZSink($player, $target)
{
  $pieces = explode("-", $target);
  $player = (str_starts_with($pieces[0], "MY") ? $player : ($player == 1 ? 2 : 1));
  $zone = &GetMZZone($player, $pieces[0]);
  switch($pieces[0]) {
    case "THEIRALLY": case "MYALLY":
      $cardID = RemoveAlly($player, $pieces[1]);
      AddBottomDeck($cardID, $player, "PLAY");
      break;
    default: break;
  }
}

function MZSuppress($player, $target)
{
  $pieces = explode("-", $target);
  $player = (str_starts_with($pieces[0], "MY") ? $player : ($player == 1 ? 2 : 1));
  $zone = &GetMZZone($player, $pieces[0]);
  switch($pieces[0]) {
    case "THEIRALLY": case "MYALLY":
      $cardID = RemoveAlly($player, $pieces[1]);
      BanishCardForPlayer($cardID, $player, "PLAY", "SUPPRESS", $player);
      break;
    case "THEIRITEMS": case "MYITEMS":
      $cardID = DestroyItemForPlayer($player, $pieces[1], true);
      BanishCardForPlayer($cardID, $player, "PLAY", "SUPPRESS", $player);
      break;
    case "THEIRCHAR": case "MYCHAR":
      $cardID = RemoveCharacter($player, $pieces[1]);
      BanishCardForPlayer($cardID, $player, "PLAY", "SUPPRESS", $player);
      break;
    default: break;
  }
}

function MZSort($mzIndices)
{
  $output = "";
  $mzArr = explode(",", $mzIndices);
  $lowest = -1;
  $lowestIndex = 0;
  while(count($mzArr) > 0) {
    for($i=0; $i<count($mzArr); ++$i) {
      $mzIndex = explode("-", $mzArr[$i]);
      if($lowest == -1 || $mzIndex[1] < $lowest) {
        $lowest = $mzIndex[1];
        $lowestIndex = $i;
      }
    }
    if($output != "") $output .= ",";
    $output .= $mzArr[$lowestIndex];
    unset($mzArr[$lowestIndex]);
    $mzArr = array_values($mzArr);
    $lowest = -1;
    $lowestIndex = 0;
  }
  return $output;
}

function MZEndCombat($player, $mzIndex)
{
  global $mainPlayer;
  $mzArr = explode("-", $mzIndex);
  if($mzArr[0] == "MYALLY") $controllingPlayer = $player;
  else if($mzArr[0] == "THEIRALLY") $controllingPlayer = ($player == 1 ? 2 : 1);
  else return;
  if(IsSpecificAllyAttacking($controllingPlayer, $mzArr[1])) CloseCombatChain();
}

function IsFrozenMZ(&$array, $zone, $i)
{
  $offset = FrozenOffsetMZ($zone);
  if($offset == -1) return false;
  return $array[$i + $offset] == "1";
}

function UnfreezeMZ($player, $zone, $index)
{
  $offset = FrozenOffsetMZ($zone);
  if($offset == -1) return false;
  $array = &GetMZZone($player, $zone);
  $array[$index + $offset] = "0";
}

function FrozenOffsetMZ($zone)
{
  switch ($zone) {
    case "ARS": case "MYARS": case "THEIRARS": return 4;
    case "ALLY": case "MYALLY": case "THEIRALLY": return 3;
    case "CHAR": case "MYCHAR": case "THEIRCHAR": return 8;
    default: return -1;
  }
}

function MZIsPlayer($MZIndex)
{
  $indexArr = explode("-", $MZIndex);
  if ($indexArr[0] == "MYCHAR" || $indexArr[0] == "THEIRCHAR") return true;
  return false;
}

function MZPlayerID($me, $MZIndex)
{
  $indexArr = explode("-", $MZIndex);
  if ($indexArr[0] == "MYCHAR") return $me;
  if ($indexArr[0] == "THEIRCHAR") return ($me == 1 ? 2 : 1);
  if ($indexArr[0] == "MYALLY") return $me;
  if ($indexArr[0] == "THEIRALLY") return ($me == 1 ? 2 : 1);
  return -1;
}

function GetMZCard($player, $MZIndex)
{
  $params = explode("-", $MZIndex);
  if(count($params) < 2) return "";
  if(str_starts_with($params[0], "THEIR")) $player = ($player == 1 ? 2 : 1);
  $zoneDS = &GetMZZone($player, $params[0]);
  $index = $params[1];
  if($index == "") return "";
  return $zoneDS[$index];
}

function MZStartTurnAbility($player, $MZIndex)
{
  $cardID = GetMZCard($player, $MZIndex);
  switch($cardID)
  {
    case "UPR086":
      AddDecisionQueue("PASSPARAMETER", $player, $MZIndex);
      AddDecisionQueue("MZREMOVE", $player, "-", 1);
      AddDecisionQueue("MULTIBANISH", $player, "GY,-", 1);
      AddDecisionQueue("FINDINDICES", $player, "UPR086");
      AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
      AddDecisionQueue("AFTERTHAW", $player, "<-", 1);
      break;
    default: break;
  }
}

function MZMoveCard($player, $search, $where, $may=false, $isReveal=false, $silent=false, $isSubsequent=false, $context="", $filter="")
{
  AddDecisionQueue("MULTIZONEINDICES", $player, $search, ($isSubsequent ? 1 : 0));
  if($filter != "") AddDecisionQueue("MZFILTER", $player, $filter);
  if($context != "") AddDecisionQueue("SETDQCONTEXT", $player, $context);
  if($may) AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  else AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZADDZONE", $player, $where, 1);
  AddDecisionQueue("MZREMOVE", $player, "-", 1);
  AddDecisionQueue("SETDQVAR", $player, "0", 1);
  if($silent);
  else if($isReveal) AddDecisionQueue("REVEALCARDS", $player, "-", 1);
  else AddDecisionQueue("WRITELOG", $player, "Card chosen: <0>", 1);
}

function MZChooseAndDestroy($player, $search, $may=false, $filter="", $context="")
{
  AddDecisionQueue("MULTIZONEINDICES", $player, $search);
  if($context != "") AddDecisionQueue("SETDQCONTEXT", $player, $context);
  if($filter != "") AddDecisionQueue("MZFILTER", $player, $filter);
  if($may) AddDecisionQueue("MAYCHOOSEMULTIZONE", $player, "<-", 1);
  else AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
  AddDecisionQueue("MZDESTROY", $player, "-", 1);
}

function GetMZType($mzIndex) {
  $mzArr = explode("-", $mzIndex);
  if($mzArr[0] == "MYALLY" || $mzArr[0] == "THEIRALLY") return "ALLY";
  else if($mzArr[0] == "MYCHAR" || $mzArr[0] == "THEIRCHAR") return "CHAR";
  return "";
}

function GetMZIndex($mzIndex) {
  $mzArr = explode("-", $mzIndex);
  return $mzArr[1];
}
