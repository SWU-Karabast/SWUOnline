<?php

// Parameter structure:
// 0 - DEALDAMAGE
// 1 - Damage amount
// 2? - Player causing the damage
// 3? - Indicates if the damage is caused by unit effects (1 = yes, 0 = no)
// 4? - Indicates if the damage is preventable (1 = yes, 0 = no) (not preventable = indirect)
function DamageStringBuilder($damage, $player, $isUnitEffect = 0, $isPreventable = 1, $alsoExhausts = 0) {
  $isUnitEffect = $isUnitEffect ? 1 : 0;
  $isPreventable = $isPreventable ? 1 : 0;
  $alsoExhausts = $alsoExhausts ? 1 : 0;
  return "DEALDAMAGE,$damage,$player,$isUnitEffect,$isPreventable,$alsoExhausts";
}

// Parameter structure:
//0 - total damage
//1 - from unit effect (1 = yes, 0 = no)
//2 - max damage per target (0 means no max)
//3 - source player
//4 - preventable (1 = yes, 0 = no); default is yes (not preventable = indirect)
//6 - zones (THEIRALLY, MYALLY, OURALLIES, OURALLIESANDBASES, MYALLIESANDBASE, THEIRALLIESANDBASE); default is THEIRALLY
function MultiDistributeDamageStringBuilder($totalDamage, $sourcePlayer,
    $isUnitEffect = 0, $maxDamagePerTarget = 0, $isPreventable = 1, $alsoExhausts = 0,
    $zones = "THEIRALLY") {
  $isUnitEffect = $isUnitEffect ? 1 : 0;
  $maxDamagePerTarget = $maxDamagePerTarget ? $maxDamagePerTarget : 0;
  $isPreventable = $isPreventable ? 1 : 0;
  $alsoExhausts = $alsoExhausts ? 1 : 0;
  return "$totalDamage,$isUnitEffect,$maxDamagePerTarget,$sourcePlayer,$isPreventable,$alsoExhausts,$zones";
}

?>