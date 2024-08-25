<?php

//TODO: Fix all of this to be more general
//TODO: Change counters to be an array of enum:count
//TODO: Overlays should also be an array of enum:count
//TODO: type and stype as implemented here will not work generically (although most games have this concept, there's no way to map it)

//0 Card number = card ID (e.g. WTR000 = Heart of Fyendal)
//1 action = (ProcessInput2 mode)
//2 overlay = 0 is none, 1 is grayed out/disabled
//3 borderColor = Border Color
//4 Counters = number of counters
//5 actionDataOverride = The value to give to ProcessInput2
//6 lifeCounters = Number of life counters
//7 defCounters = Number of defense counters
//8 atkCounters = Number of attack counters
//9 controller = Player that controls it
//10 type = card type
//11 sType = card subtype
//12 restriction = something preventing the card from being played (or "" if nothing)
//13 isBroken = 1 if card is destroyed
//14 onChain = 1 if card is on combat chain (mostly for equipment)
//15 isFrozen = 1 if frozen
//16 shows gem = (0, 1, 2) (0 off, 1 active, 2 inactive)
function ClientRenderedCard($cardNumber, $action = 0, $overlay = 0, $borderColor = 0, $counters = 0, $actionDataOverride = "-", $lifeCounters = 0, $defCounters = 0, $atkCounters = 0, $controller = 0, $type = "", $sType = "", $restriction = "", $isBroken = 0, $onChain = 0, $isFrozen = 0, $gem = 0, $rotate = 0, $landscape = 0, $epicActionUsed = 0)
{
  $rv = $cardNumber . " " . $action . " " . $overlay . " " . $borderColor . " " . $counters . " " . $actionDataOverride . " " . $lifeCounters . " " . $defCounters . " " . $atkCounters . " ";
  $rv .= $controller . " " . $type . " " . $sType . " " . $restriction . " " . $isBroken . " " . $onChain . " " . $isFrozen . " " . $gem . " " . $rotate . " " . $landscape . " " . $epicActionUsed;
  return $rv;
}


?>