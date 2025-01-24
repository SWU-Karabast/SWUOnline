<?php

function LeaderPilotDeploy($player, $leader, $target) {
  $targetUnit = new Ally($target, $player);
  $targetUnit->AddSubcard(LeaderUnit($leader), $player);
}

?>
