<?php

function PlayerInfluence($player) {
  $hand = &GetHand($player);
  $memory = &GetMemory($player);
  return count($hand)/HandPieces() + count($memory)/MemoryPieces();
}

function Gather($player, $amount) {
  for($i=0; $i<$amount; $i++) {
    $herb = "";
    $herbNumber = (GetRandom() % 6) + 1;
    switch($herbNumber) {
      case 1: $herb = "i0a5uhjxhk"; break;//Blightroot (1)
      case 2: $herb = "5joh300z2s"; break;//Mana Root (2)
      case 3: $herb = "bd7ozuj68m"; break;//Silvershine (3)
      case 4: $herb = "soporhlq2k"; break;//Fraysia (4)
      case 5: $herb = "jnltv5klry"; break;//Razorvine (5)
      case 6: $herb = "69iq4d5vet"; break;//Springleaf (6)
      default: break;
    }
    if($herb != "") PutItemIntoPlay($herb);
    WriteLog("Gathered " . CardLink($herb, $herb));
    $items = &GetItems($player);
    for($i=0; $i<count($items); $i+=ItemPieces()) {
      switch($items[$i]) {
        case "ettczb14m4": ++$items[$i+1]; break;//Alchemist's Kit
        default: break;
      }
    }
  }
}

?>