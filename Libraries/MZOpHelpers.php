<?php

// Parameter structure:
// 0 - DEALDAMAGE
// 1 - Damage amount
// 2? - Player causing the damage
// 3? - Indicates if the damage is caused by unit effects (1 = yes, 0 = no)
// 4? - Indicates if the damage is preventable (1 = yes, 0 = no) (not preventable = indirect)
function DealDamageBuilder($damage, $sourcePlayer, $isUnitEffect = 0, $isPreventable = 1) {
  $isUnitEffect = $isUnitEffect ? 1 : 0;
  $isPreventable = $isPreventable ? 1 : 0;
  return "DEALDAMAGE,$damage,$sourcePlayer,$isUnitEffect,$isPreventable";
}

/**
 * Builds a string for dealing damage to multiple targets
 * 
 * Parameter structure:
 * 0 - DEALMULTIDAMAGE
 * 1 - Damage amount
 * 2 - Player causing the damage
 * 3 - Indicates if the damage is caused by unit effects (1 = yes, 0 = no)
 * 4 - Indicates if the damage is preventable (1 = yes, 0 = no) (not preventable = indirect)
 * 
 * @param int $damage The amount of damage to deal
 * @param int $sourcePlayer The player causing the damage
 * @param int $isUnitEffect Whether the damage is caused by unit effects (1 = yes, 0 = no)
 * @param int $isPreventable Whether the damage is preventable (1 = yes, 0 = no)
 * @return string The formatted damage string for the decision queue
 */
function DealMultiDamageBuilder($sourcePlayer, $isUnitEffect = 0, $isPreventable = 1) {
  $isUnitEffect = $isUnitEffect ? 1 : 0;
  $isPreventable = $isPreventable ? 1 : 0;
  return "DEALMULTIDAMAGE,$sourcePlayer,$isUnitEffect,$isPreventable";
}

// Parameter structure:
//0 - total damage
//1 - from unit effect (1 = yes, 0 = no)
//2 - max damage per target (0 means no max)
//3 - source player
//4 - preventable (1 = yes, 0 = no); default is yes (not preventable = indirect)
//6 - zones (THEIRALLY, MYALLY, OURALLIES, OURALLIESANDBASES, MYALLIESANDBASE, THEIRALLIESANDBASE); default is THEIRALLY
function MultiDistributeDamageStringBuilder($totalDamage, $sourcePlayer, $isUnitEffect = 0, $maxDamagePerTarget = 0, $isPreventable = 1, $zones = "THEIRALLY") {
  $isUnitEffect = $isUnitEffect ? 1 : 0;
  $maxDamagePerTarget = $maxDamagePerTarget ? $maxDamagePerTarget : 0;
  $isPreventable = $isPreventable ? 1 : 0;
  return "$totalDamage,$isUnitEffect,$maxDamagePerTarget,$sourcePlayer,$isPreventable,$zones";
}
?>