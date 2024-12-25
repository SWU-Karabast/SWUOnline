<?php

const LAYER_DATA_SEPARATOR="$";
const LAYER_PIECE_SEPARATOR="_";

//0 - WhenDefeated data
//1 - WhenDefeated->AddResource data
//2 - Bounties data
function DestroyTriggerPieces() {
  return 3;
}

//0 - CardID
//1 - Player
//2 - UniqueID
//3 - UpgradesWithOwnerData
function OtherDestroyedTriggerPieces() {
  return 4;
}

function LayerDestroyTriggers($player, $cardID, $uniqueID,
    $serializedDestroyData,
    $serializedResourceData,
    $serializedBountyData) {
  $dataBuilder="";
  if($serializedDestroyData!=="") $dataBuilder = $dataBuilder . "ALLYDESTROY=$serializedDestroyData" . LAYER_PIECE_SEPARATOR;
  if($serializedResourceData!=="") $dataBuilder = $dataBuilder . "ALLYRESOURCE=$serializedResourceData" . LAYER_PIECE_SEPARATOR;
  if($serializedBountyData!=="") $dataBuilder = $dataBuilder . "ALLYBOUNTIES=$serializedBountyData" . LAYER_PIECE_SEPARATOR;
  AddLayer("TRIGGER", $player, "AFTERDESTROYABILITY", $cardID, $dataBuilder, $uniqueID);
}

function LayerTheirsDestroyedTriggers($player, $arr) {
  $data=implode(",", $arr);
  AddLayer("TRIGGER", $player, "AFTERDESTROYTHEIRSABILITY", $data);
}

function LayerFriendlyDestroyedTriggers($player, $arr) {
  global $layers;
  //Calculating MagnaGuard - avoid unnecessary dupes
  if($arr[0] == "1039828081") {
    if(SearchCurrentTurnEffects("1039828081", $player)) return;
    for($i=0;$i<count($layers);$i+=LayerPieces()) {
      if($layers[$i] == "TRIGGER" && $layers[$i+1] == $player && $layers[$i+2] == "AFTERDESTROYFRIENDLYABILITY") {
        $pieces=explode(",", $layers[$i+3]);
        for($j=0;$j<count($pieces);$j+=OtherDestroyedTriggerPieces()) {
          if($pieces[$j] == "1039828081") return;
        }
      }
    }
  };

  $data=implode(",", $arr);
  AddLayer("TRIGGER", $player, "AFTERDESTROYFRIENDLYABILITY", $data);
}

function GetAllyWhenDestroyTheirsEffects($mainPlayer, $player,
    $destroyedUniqueID, $destroyedWasUnique, $destroyedWasUpgraded, $destroyedUpgradesWithOwnerData) {
  global $combatChainState, $CCS_CachedLastDestroyed;
  $triggers=[];
  if($mainPlayer != $player) {
    $allies = &GetAllies($player);
    for($i=0;$i<count($allies);$i+=AllyPieces()) {
      $ally = new Ally("MYALLY-$i", $player);
      if(!$ally->LostAbilities() && HasWhenEnemyDestroyed($ally->CardID(), $ally->NumUses(), $destroyedWasUnique, $destroyedWasUpgraded)) {
        array_unshift($triggers, implode(";",$destroyedUpgradesWithOwnerData));
        array_unshift($triggers, $ally->UniqueID());
        array_unshift($triggers, $player);
        array_unshift($triggers, $ally->CardID());
      }
    }
  } else {
    $allies = &GetAllies($player);
    for($i=0;$i<count($allies);$i+=AllyPieces()) {
      $ally = new Ally("MYALLY-$i", $player);
      if(!$ally->LostAbilities() && HasWhenEnemyDestroyed($ally->CardID(), $ally->NumUses(), $destroyedWasUnique, $destroyedWasUpgraded)) {
        array_unshift($triggers, implode(";",$destroyedUpgradesWithOwnerData));
        array_unshift($triggers, $ally->UniqueID());
        array_unshift($triggers, $player);
        array_unshift($triggers, $ally->CardID());
      };
    }
    if($combatChainState[$CCS_CachedLastDestroyed] != "NA") {
      $ally = explode(";",$combatChainState[$CCS_CachedLastDestroyed]);
      if(HasWhenEnemyDestroyed($ally[0], $ally[8], $destroyedWasUnique, $destroyedWasUpgraded)) {
        array_unshift($triggers, implode(";",$destroyedUpgradesWithOwnerData));
        array_unshift($triggers, $ally[5]);
        array_unshift($triggers, $player);
        array_unshift($triggers, $ally[0]);
      };
    }
  }

  return $triggers;
}

function GetAllyWhenDestroyFriendlyEffects($player, $destroyedCardID, $destroyedUniqueID, $destroyedWasUnique, $destroyedWasUpgraded, $upgradesWithOwnerData) {
  $triggers=[];
  $allies = &GetAllies($player);
    for($i=0;$i<count($allies);$i+=AllyPieces()) {
      $ally = new Ally("MYALLY-$i", $player);
      if(!$ally->LostAbilities() && HasWhenFriendlyDestroyed($player, $ally->CardID(), $ally->NumUses(), $ally->UniqueID(),
          $destroyedCardID, $destroyedUniqueID, $destroyedWasUnique, $destroyedWasUpgraded)) {
        array_unshift($triggers, implode(";",$upgradesWithOwnerData));
        array_unshift($triggers, $ally->UniqueID());
        array_unshift($triggers, $player);
        array_unshift($triggers, $ally->CardID());
      };
    }
  return $triggers;
}


function SerializeAllyDestroyData($uniqueID, $lostAbilities, $isUpgraded, $upgrades, $upgradesWithOwnerData) {
    $upgradesSerialized = implode(",",$upgrades);
    foreach($upgradesWithOwnerData as $key => $value) if(!($key&1)) unset($upgradesWithOwnerData[$key]);
    $upgradeOwnersSerialized = implode(",", $upgradesWithOwnerData);

    return implode(LAYER_DATA_SEPARATOR,[$uniqueID, $lostAbilities, $isUpgraded, $upgradesSerialized, $upgradeOwnersSerialized]);
}

function DeserializeAllyDestroyData($data) {
    $arr=explode(LAYER_DATA_SEPARATOR,$data);
    $uniqueID=$arr[0];
    $lostAbilities=$arr[1];
    $isUpgraded=$arr[2];
    $upgrades=explode(",",$arr[3]);
    $upgradeOwners=explode(",",$arr[4]);
    $upgradesWithOwnerData=[];
    for($i=0;$i<count($upgrades);++$i) {
        $upgradesWithOwnerData[2*$i]=$upgrades[$i];
        $upgradesWithOwnerData[2*$i+1]=$upgradeOwners[$i];
    }

    return [
        "UniqueID" => $uniqueID,
        "LostAbilities" => $lostAbilities,
        "IsUpgraded" => $isUpgraded,
        "Upgrades" => $upgrades,
        "UpgradesWithOwnerData" => $upgradesWithOwnerData,
    ];
}

function SerializeResourceData($from, $facing, $counters, $isExhausted, $stealSource) {
  return implode(LAYER_DATA_SEPARATOR,[$from,$facing,$counters,$isExhausted,$stealSource]);
}

function DeserializeResourceData($data) {
    $arr=explode(LAYER_DATA_SEPARATOR,$data);
    $from=$arr[0];
    $facing=$arr[1];
    $counters=$arr[2];
    $isExhausted=$arr[3];
    $stealSource=$arr[4];

    return [
        "From" => $from,
        "Facing" => $facing,
        "Counters" => $counters,
        "IsExhausted" => $isExhausted,
        "StealSource" => $stealSource,
    ];
}

function SerializeBountiesData($uniqueID, $isExhausted, $bountysOwner, $upgrades, $reportMode=false, $bountyUnitOverride="-", $capturerUniqueID="-") {
  return implode(LAYER_DATA_SEPARATOR, [
    $uniqueID, $isExhausted, $bountysOwner, implode(",",$upgrades), $reportMode,
    $bountyUnitOverride,
    $capturerUniqueID,
  ]);
}

function DeserializeBountiesData($data) {
  $arr=explode(LAYER_DATA_SEPARATOR, $data);
  return [
    "UniqueID" => $arr[0],
    "IsExhausted" => $arr[1],
    "Owner" => $arr[2],
    "Upgrades" => explode(",",$arr[3]),
    "ReportMode" => $arr[4],
    "BountyUnitOverride" => str_replace("^","-", $arr[5]),
    "CapturerUniqueID" => str_replace("^","-", $arr[6]),
  ];
}