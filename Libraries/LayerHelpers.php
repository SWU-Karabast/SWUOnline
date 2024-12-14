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
//2 - WasUnique
//3 - WasUpgraded
//4 - NumUses
//5 - UniqueID
function TheirsDestroyedTriggerPieces() {
  return 6;
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

function GetAllyWhenDestroyTheirsEffects($mainPlayer, $player, $wasUnique, $wasUpgraded) {
  global $combatChainState, $CCS_CachedLastDestroyed;
  $triggers=[];
  if($mainPlayer != $player) {
    $allies = &GetAllies($player);
    for($i=0;$i<count($allies);$i+=AllyPieces()) {
      if(HasWhenEnemyDestroyed($allies[$i])) {
        array_unshift($triggers, $allies[$i+5]);
        array_unshift($triggers, $allies[$i+8]);
        array_unshift($triggers, $wasUpgraded);
        array_unshift($triggers, $wasUnique);
        array_unshift($triggers, $player);
        array_unshift($triggers, $allies[$i]);
      };
    }
  } else {
    $allies = &GetAllies($player);
    for($i=0;$i<count($allies);$i+=AllyPieces()) {
      if(HasWhenEnemyDestroyed($allies[$i])) {
        array_unshift($triggers, $allies[$i+5]);
        array_unshift($triggers, $allies[$i+8]);
        array_unshift($triggers, $wasUpgraded);
        array_unshift($triggers, $wasUnique);
        array_unshift($triggers, $player);
        array_unshift($triggers, $allies[$i]);
      };
    }
    if($combatChainState[$CCS_CachedLastDestroyed] != "NA") {
      $ally = explode(";",$combatChainState[$CCS_CachedLastDestroyed]);
      if(HasWhenEnemyDestroyed($ally[$i])) {
        array_unshift($triggers, $ally[$i+5]);
        array_unshift($triggers, $ally[$i+8]);
        array_unshift($triggers, $wasUpgraded);
        array_unshift($triggers, $wasUnique);
        array_unshift($triggers, $player);
        array_unshift($triggers, $ally[$i]);
      };
    }
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