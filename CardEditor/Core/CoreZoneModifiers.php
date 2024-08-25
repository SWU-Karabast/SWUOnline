<?php

  function Draw($player, $amount=1) {
    $zone = &GetDeck($player);
    $hand = &GetHand($player);
    for($i=0; $i<$amount; ++$i) {
      if(count($zone) == 0) {
        return;
      }
      $card = array_shift($zone);
      array_push($hand, $card);
    }
  }

?>