<?php

function GetStringArray($line)
{
  $line = trim($line);
  if($line == "") return [];
  return explode(" ", $line);
}

if(!isset($filename) || !str_contains($filename, "gamestate.txt")) $filename = "./Games/" . $gameName . "/gamestate.txt";
if(!isset($filepath)) $filepath = "./Games/" . $gameName . "/";

ParseGamestate();

function GamestateSanitize($input)
{
  $output = str_replace(",", "<44>", $input);
  $output = str_replace(" ", "_", $output);
  return $output;
}

function GamestateUnsanitize($input)
{
  $output = str_replace("<44>", ",", $input);
  $output = str_replace("_", " ", $output);
  return $output;
}

function ParseGamestate($useRedis = false)
{
  global $gameName, $playerHealths;
  global $p1Hand, $p1Deck, $p1CharEquip, $p1Resources, $p1Arsenal, $p1Items, $p1Auras, $p1Discard, $p1Pitch, $p1Banish;
  global $p1ClassState, $p1CharacterEffects, $p1Material, $p1CardStats, $p1TurnStats, $p1Allies, $p1Permanents, $p1Settings;
  global $p2Hand, $p2Deck, $p2CharEquip, $p2Resources, $p2Arsenal, $p2Items, $p2Auras, $p2Discard, $p2Pitch, $p2Banish;
  global $p2ClassState, $p2CharacterEffects, $p2Material, $p2CardStats, $p2TurnStats, $p2Allies, $p2Permanents, $p2Settings;
  global $landmarks, $winner, $firstPlayer, $currentPlayer, $currentRound, $turn, $actionPoints, $combatChain, $combatChainState;
  global $currentTurnEffects, $currentTurnEffectsFromCombat, $nextTurnEffects, $decisionQueue, $dqVars, $dqState;
  global $layers, $layerPriority, $mainPlayer, $defPlayer, $lastPlayed, $chainLinks, $chainLinkSummary, $p1Key, $p2Key;
  global $permanentUniqueIDCounter, $inGameStatus, $animations, $currentPlayerActivity;
  global $p1TotalTime, $p2TotalTime, $lastUpdateTime, $roguelikeGameID, $events, $lastUpdate, $EffectContext;
  global $mainPlayerGamestateStillBuilt, $mpgBuiltFor, $myStateBuiltFor, $playerID, $filename;
  global $initiativePlayer, $initiativeTaken;

  $mainPlayerGamestateStillBuilt = 0;
  $mpgBuiltFor = -1;
  $myStateBuiltFor = -1;

  $fileTries = 0;
  $targetTries = ($playerID == 1 ? 10 : 100);
  $waitTime = 1000000;
  while (!file_exists($filename) && $fileTries < $targetTries) {
    usleep($waitTime); //1 second
    ++$fileTries;
  }
  if ($fileTries == $targetTries) {
    $response = new stdClass();
    $response->error = "Unable to create the game after 10 seconds. Please try again.";
    echo(json_encode($response));
    exit;
  }

  if (!file_exists($filename)) exit;
  $handler = fopen($filename, "r");

  if (!$handler) {
    exit;
  } //Game does not exist

  $lockTries = 0;
  while (!flock($handler, LOCK_SH) && $lockTries < 10) {
    usleep(100000); //100ms
    ++$lockTries;
  }

  if ($lockTries == 10) exit;

  $gamestateContent = "";
  if($useRedis) $gamestateContent = ReadCache($gameName . "GS");
  if($gamestateContent == "") $gamestateContent = file_get_contents($filename);
  $gamestateContent = explode("\r\n", $gamestateContent);
  if(count($gamestateContent) < 60) exit;

  $playerHealths = GetStringArray($gamestateContent[0]); // 1

  //Player 1
  $p1Hand = GetStringArray($gamestateContent[1]); // 2
  $p1Deck = GetStringArray($gamestateContent[2]); // 3
  $p1CharEquip = GetStringArray($gamestateContent[3]); // 4
  $p1Resources = GetStringArray($gamestateContent[4]); // 5
  $p1Arsenal = GetStringArray($gamestateContent[5]); // 6
  $p1Items = GetStringArray($gamestateContent[6]); // 7
  $p1Auras = GetStringArray($gamestateContent[7]); // 8
  $p1Discard = GetStringArray($gamestateContent[8]); // 9
  $p1Pitch = GetStringArray($gamestateContent[9]); // 10
  $p1Banish = GetStringArray($gamestateContent[10]); // 11
  $p1ClassState = GetStringArray($gamestateContent[11]); // 12
  $p1CharacterEffects = GetStringArray($gamestateContent[12]); // 13
  $p1Material = GetStringArray($gamestateContent[13]); // 14
  $p1CardStats = GetStringArray($gamestateContent[14]); // 15
  $p1TurnStats = GetStringArray($gamestateContent[15]); // 16
  $p1Allies = GetStringArray($gamestateContent[16]); // 17
  $p1Permanents = GetStringArray($gamestateContent[17]); // 18
  $p1Settings = GetStringArray($gamestateContent[18]); // 19

  //Player 2
  $p2Hand = GetStringArray($gamestateContent[19]); // 20
  $p2Deck = GetStringArray($gamestateContent[20]); // 21
  $p2CharEquip = GetStringArray($gamestateContent[21]); // 22
  $p2Resources = GetStringArray($gamestateContent[22]); // 23
  $p2Arsenal = GetStringArray($gamestateContent[23]); // 24
  $p2Items = GetStringArray($gamestateContent[24]); // 25
  $p2Auras = GetStringArray($gamestateContent[25]); // 26
  $p2Discard = GetStringArray($gamestateContent[26]); // 27
  $p2Pitch = GetStringArray($gamestateContent[27]); // 28
  $p2Banish = GetStringArray($gamestateContent[28]); // 29
  $p2ClassState = GetStringArray($gamestateContent[29]); // 30
  $p2CharacterEffects = GetStringArray($gamestateContent[30]); // 31
  $p2Material = GetStringArray($gamestateContent[31]); // 32
  $p2CardStats = GetStringArray($gamestateContent[32]); // 33
  $p2TurnStats = GetStringArray($gamestateContent[33]); // 34
  $p2Allies = GetStringArray($gamestateContent[34]); // 35
  $p2Permanents = GetStringArray($gamestateContent[35]); // 36
  $p2Settings = GetStringArray($gamestateContent[36]); // 37

  $landmarks = GetStringArray($gamestateContent[37]);
  $winner = trim($gamestateContent[38]);
  $firstPlayer = trim($gamestateContent[39]);
  $currentPlayer = trim($gamestateContent[40]);
  $currentRound= trim($gamestateContent[41]);
  $turn = GetStringArray($gamestateContent[42]);
  $actionPoints = trim($gamestateContent[43]);
  $combatChain = GetStringArray($gamestateContent[44]);
  $combatChainState = GetStringArray($gamestateContent[45]);
  $currentTurnEffects = GetStringArray($gamestateContent[46]);
  $currentTurnEffectsFromCombat = GetStringArray($gamestateContent[47]);
  $nextTurnEffects = GetStringArray($gamestateContent[48]);
  $decisionQueue = GetStringArray($gamestateContent[49]);
  $dqVars = GetStringArray($gamestateContent[50]);
  $dqState = GetStringArray($gamestateContent[51]);
  $layers = GetStringArray($gamestateContent[52]);
  $layerPriority = GetStringArray($gamestateContent[53]);
  $mainPlayer = trim($gamestateContent[54]);
  $defPlayer = $mainPlayer == 1 ? 2 : 1;
  $lastPlayed = GetStringArray($gamestateContent[55]);
  $numChainLinks = trim($gamestateContent[56]);
  $chainLinks = array();
  for ($i = 0; $i < $numChainLinks; ++$i) {
    $chainLink = GetStringArray($gamestateContent[57+$i]);
    $chainLinks[] = $chainLink;
  }
  $chainLinkSummary = GetStringArray($gamestateContent[57+$numChainLinks]);
  $p1Key = trim($gamestateContent[58+$numChainLinks]);
  $p2Key = trim($gamestateContent[59+$numChainLinks]);
  $permanentUniqueIDCounter = trim($gamestateContent[60+$numChainLinks]);
  $inGameStatus = trim($gamestateContent[61+$numChainLinks]); //Game status -- 0 = START, 1 = PLAY, 2 = OVER
  $animations = GetStringArray($gamestateContent[62+$numChainLinks]); //Animations
  $currentPlayerActivity = trim($gamestateContent[63+$numChainLinks]); //Current Player activity status -- 0 = active, 2 = inactive
  //64 + numChainLinks unused
  //65 + numChainLinks unused
  $p1TotalTime = trim($gamestateContent[66+$numChainLinks]); //Player 1 total time
  $p2TotalTime = trim($gamestateContent[67+$numChainLinks]); //Player 2 total time
  $lastUpdateTime = trim($gamestateContent[68+$numChainLinks]); //Last update time
  $roguelikeGameID = trim($gamestateContent[69+$numChainLinks]); //Roguelike game id
  $events = GetStringArray($gamestateContent[70+$numChainLinks]); //Events
  $EffectContext = trim($gamestateContent[71+$numChainLinks]); //What update number the gamestate is for
  $initiativePlayer = trim($gamestateContent[72+$numChainLinks]); //The player that has initiative
  $initiativeTaken = trim($gamestateContent[73+$numChainLinks]); //If initiative is taken yet

  fclose($handler);
  BuildMyGamestate($playerID);
}

function DoGamestateUpdate()
{
  global $mainPlayerGamestateStillBuilt, $myStateBuiltFor;
  if ($mainPlayerGamestateStillBuilt == 1) UpdateMainPlayerGameStateInner();
  else if ($myStateBuiltFor != -1) UpdateGameStateInner();
}

function BuildMyGamestate($playerID)
{
  global $p1Deck, $p1Hand, $p1Resources, $p1CharEquip, $p1Arsenal, $playerHealths, $p1Auras, $p1Pitch, $p1Banish, $p1ClassState, $p1Items;
  global $p1CharacterEffects, $p1Discard, $p1CardStats, $p1TurnStats;
  global $p2Deck, $p2Hand, $p2Resources, $p2CharEquip, $p2Arsenal, $p2Auras, $p2Pitch, $p2Banish, $p2ClassState, $p2Items;
  global $p2CharacterEffects, $p2Discard, $p2CardStats, $p2TurnStats;
  global $myDeck, $myHand, $myResources, $myCharacter, $myArsenal, $myHealth, $myAuras, $myPitch, $myBanish, $myClassState, $myItems;
  global $myCharacterEffects, $myDiscard, $myCardStats, $myTurnStats;
  global $theirDeck, $theirHand, $theirResources, $theirCharacter, $theirArsenal, $theirHealth, $theirAuras, $theirPitch, $theirBanish, $theirClassState, $theirItems;
  global $theirCharacterEffects, $theirDiscard, $theirCardStats, $theirTurnStats;
  global $p1Material, $p2Material, $myMaterial, $theirMaterial;
  global $myStateBuiltFor, $mainPlayerGamestateStillBuilt;
  DoGamestateUpdate();
  $mainPlayerGamestateStillBuilt = 0;
  $myStateBuiltFor = $playerID;
  $myHand = $playerID == 1 ? $p1Hand : $p2Hand;
  $myDeck = $playerID == 1 ? $p1Deck : $p2Deck;
  $myResources = $playerID == 1 ? $p1Resources : $p2Resources;
  $myCharacter = $playerID == 1 ? $p1CharEquip : $p2CharEquip;
  $myArsenal = $playerID == 1 ? $p1Arsenal : $p2Arsenal;
  $myHealth = $playerID == 1 ? $playerHealths[0] : $playerHealths[1];
  $myItems = $playerID == 1 ? $p1Items : $p2Items;
  $myAuras = $playerID == 1 ? $p1Auras : $p2Auras;
  $myDiscard = $playerID == 1 ? $p1Discard : $p2Discard;
  $myPitch = $playerID == 1 ? $p1Pitch : $p2Pitch;
  $myBanish = $playerID == 1 ? $p1Banish : $p2Banish;
  $myClassState = $playerID == 1 ? $p1ClassState : $p2ClassState;
  $myCharacterEffects = $playerID == 1 ? $p1CharacterEffects : $p2CharacterEffects;
  $myMaterial = $playerID == 1 ? $p1Material : $p2Material;
  $myCardStats = $playerID == 1 ? $p1CardStats : $p2CardStats;
  $myTurnStats = $playerID == 1 ? $p1TurnStats : $p2TurnStats;
  $theirHand = $playerID == 1 ? $p2Hand : $p1Hand;
  $theirDeck = $playerID == 1 ? $p2Deck : $p1Deck;
  $theirResources = $playerID == 1 ? $p2Resources : $p1Resources;
  $theirCharacter = $playerID == 1 ? $p2CharEquip : $p1CharEquip;
  $theirArsenal = $playerID == 1 ? $p2Arsenal : $p1Arsenal;
  $theirHealth = $playerID == 1 ? $playerHealths[1] : $playerHealths[0];
  $theirItems = $playerID == 1 ? $p2Items : $p1Items;
  $theirAuras = $playerID == 1 ? $p2Auras : $p1Auras;
  $theirDiscard = $playerID == 1 ? $p2Discard : $p1Discard;
  $theirPitch = $playerID == 1 ? $p2Pitch : $p1Pitch;
  $theirBanish = $playerID == 1 ? $p2Banish : $p1Banish;
  $theirClassState = $playerID == 1 ? $p2ClassState : $p1ClassState;
  $theirCharacterEffects = $playerID == 1 ? $p2CharacterEffects : $p1CharacterEffects;
  $theirMaterial = $playerID == 1 ? $p2Material : $p1Material;
  $theirCardStats = $playerID == 1 ? $p2CardStats : $p1CardStats;
  $theirTurnStats = $playerID == 1 ? $p2TurnStats : $p1TurnStats;
}

function BuildMainPlayerGameState()
{
  global $mainPlayer, $mainPlayerGamestateStillBuilt, $playerHealths, $mpgBuiltFor, $defPlayer;
  global $mainHand, $mainDeck, $mainResources, $mainCharacter, $mainArsenal, $mainHealth, $mainAuras, $mainPitch, $mainBanish, $mainClassState, $mainItems;
  global $mainCharacterEffects, $mainDiscard;
  global $defHand, $defDeck, $defResources, $defCharacter, $defArsenal, $defHealth, $defAuras, $defPitch, $defBanish, $defClassState, $defItems;
  global $defCharacterEffects, $defDiscard;
  global $p1Deck, $p1Hand, $p1Resources, $p1CharEquip, $p1Arsenal, $p1Auras, $p1Pitch, $p1Banish, $p1ClassState, $p1Items, $p1CharacterEffects, $p1Discard;
  global $p2Deck, $p2Hand, $p2Resources, $p2CharEquip, $p2Arsenal, $p2Auras, $p2Pitch, $p2Banish, $p2ClassState, $p2Items, $p2CharacterEffects, $p2Discard;
  global $p1Material, $p2Material, $mainMaterial, $defMaterial;
  global $p1CardStats, $p2CardStats, $mainCardStats, $defCardStats;
  global $p1TurnStats, $p2TurnStats, $mainTurnStats, $defTurnStats;
  DoGamestateUpdate();
  $mpgBuiltFor = $mainPlayer;
  $mainHand = $mainPlayer == 1 ? $p1Hand : $p2Hand;
  $mainDeck = $mainPlayer == 1 ? $p1Deck : $p2Deck;
  $mainResources = $mainPlayer == 1 ? $p1Resources : $p2Resources;
  $mainCharacter = $mainPlayer == 1 ? $p1CharEquip : $p2CharEquip;
  $mainArsenal = $mainPlayer == 1 ? $p1Arsenal : $p2Arsenal;
  $mainHealth = $mainPlayer == 1 ? $playerHealths[0] : $playerHealths[1];
  $mainItems = $mainPlayer == 1 ? $p1Items : $p2Items;
  $mainAuras = $mainPlayer == 1 ? $p1Auras : $p2Auras;
  $mainPitch = $mainPlayer == 1 ? $p1Pitch : $p2Pitch;
  $mainBanish = $mainPlayer == 1 ? $p1Banish : $p2Banish;
  $mainClassState = $mainPlayer == 1 ? $p1ClassState : $p2ClassState;
  $mainCharacterEffects = $mainPlayer == 1 ? $p1CharacterEffects : $p2CharacterEffects;
  $mainDiscard = $mainPlayer == 1 ? $p1Discard : $p2Discard;
  $mainMaterial = $mainPlayer == 1 ? $p1Material : $p2Material;
  $mainCardStats = $mainPlayer == 1 ? $p1CardStats : $p2CardStats;
  $mainTurnStats = $mainPlayer == 1 ? $p1TurnStats : $p2TurnStats;
  $defHand = $mainPlayer == 1 ? $p2Hand : $p1Hand;
  $defDeck = $mainPlayer == 1 ? $p2Deck : $p1Deck;
  $defResources = $mainPlayer == 1 ? $p2Resources : $p1Resources;
  $defCharacter = $mainPlayer == 1 ? $p2CharEquip : $p1CharEquip;
  $defArsenal = $mainPlayer == 1 ? $p2Arsenal : $p1Arsenal;
  $defHealth = $mainPlayer == 1 ? $playerHealths[1] : $playerHealths[0];
  $defItems = $mainPlayer == 1 ? $p2Items : $p1Items;
  $defAuras = $mainPlayer == 1 ? $p2Auras : $p1Auras;
  $defPitch = $mainPlayer == 1 ? $p2Pitch : $p1Pitch;
  $defBanish = $mainPlayer == 1 ? $p2Banish : $p1Banish;
  $defClassState = $mainPlayer == 1 ? $p2ClassState : $p1ClassState;
  $defCharacterEffects = $mainPlayer == 1 ? $p2CharacterEffects : $p1CharacterEffects;
  $defDiscard = $mainPlayer == 1 ? $p2Discard : $p1Discard;
  $defMaterial = $mainPlayer == 1 ? $p2Material : $p1Material;
  $defCardStats = $mainPlayer == 1 ? $p2CardStats : $p1CardStats;
  $defTurnStats = $mainPlayer == 1 ? $p2TurnStats : $p1TurnStats;

  $mainPlayerGamestateStillBuilt = 1;
}

function UpdateGameState($activePlayer)
{
}

function UpdateGameStateInner()
{
  global $myStateBuiltFor;
  global $p1Deck, $p1Hand, $p1Resources, $p1CharEquip, $p1Arsenal, $playerHealths, $p1Auras, $p1Pitch, $p1Banish, $p1ClassState, $p1Items;
  global $p1CharacterEffects, $p1Discard, $p1CardStats, $p1TurnStats;
  global $p2Deck, $p2Hand, $p2Resources, $p2CharEquip, $p2Arsenal, $p2Auras, $p2Pitch, $p2Banish, $p2ClassState, $p2Items;
  global $p2CharacterEffects, $p2Discard, $p2CardStats, $p2TurnStats;
  global $myDeck, $myHand, $myResources, $myCharacter, $myArsenal, $myHealth, $myAuras, $myPitch, $myBanish, $myClassState, $myItems;
  global $myCharacterEffects, $myDiscard, $myCardStats, $myTurnStats;
  global $theirDeck, $theirHand, $theirResources, $theirCharacter, $theirArsenal, $theirHealth, $theirAuras, $theirPitch, $theirBanish, $theirClassState, $theirItems;
  global $theirCharacterEffects, $theirDiscard, $theirCardStats, $theirTurnStats;
  global $p1Material, $p2Material, $myMaterial, $theirMaterial;
  $activePlayer = $myStateBuiltFor;
  if ($activePlayer == 1) {
    $p1Deck = $myDeck;
    $p1Hand = $myHand;
    $p1Resources = $myResources;
    $p1CharEquip = $myCharacter;
    $p1Arsenal = $myArsenal;
    $playerHealths[0] = $myHealth;
    $p1Items = $myItems;
    $p1Auras = $myAuras;
    $p1Pitch = $myPitch;
    $p1Banish = $myBanish;
    $p1ClassState = $myClassState;
    $p1CharacterEffects = $myCharacterEffects;
    $p1Discard = $myDiscard;
    $p1Material = $myMaterial;
    $p1CardStats = $myCardStats;
    $p1TurnStats = $myTurnStats;
    $p2Deck = $theirDeck;
    $p2Hand = $theirHand;
    $p2Resources = $theirResources;
    $p2CharEquip = $theirCharacter;
    $p2Arsenal = $theirArsenal;
    $playerHealths[1] = $theirHealth;
    $p2Items = $theirItems;
    $p2Auras = $theirAuras;
    $p2Pitch = $theirPitch;
    $p2Banish = $theirBanish;
    $p2ClassState = $theirClassState;
    $p2CharacterEffects = $theirCharacterEffects;
    $p2Discard = $theirDiscard;
    $p2Material = $theirMaterial;
    $p2CardStats = $theirCardStats;
    $p2TurnStats = $theirTurnStats;
  } else {
    $p2Deck = $myDeck;
    $p2Hand = $myHand;
    $p2Resources = $myResources;
    $p2CharEquip = $myCharacter;
    $p2Arsenal = $myArsenal;
    $playerHealths[1] = $myHealth;
    $p2Items = $myItems;
    $p2Auras = $myAuras;
    $p2Pitch = $myPitch;
    $p2Banish = $myBanish;
    $p2ClassState = $myClassState;
    $p2CharacterEffects = $myCharacterEffects;
    $p2Discard = $myDiscard;
    $p2Material = $myMaterial;
    $p2CardStats = $myCardStats;
    $p2TurnStats = $myTurnStats;
    $p1Deck = $theirDeck;
    $p1Hand = $theirHand;
    $p1Resources = $theirResources;
    $p1CharEquip = $theirCharacter;
    $p1Arsenal = $theirArsenal;
    $playerHealths[0] = $theirHealth;
    $p1Items = $theirItems;
    $p1Auras = $theirAuras;
    $p1Pitch = $theirPitch;
    $p1Banish = $theirBanish;
    $p1ClassState = $theirClassState;
    $p1CharacterEffects = $theirCharacterEffects;
    $p1Discard = $theirDiscard;
    $p1Material = $theirMaterial;
    $p1CardStats = $theirCardStats;
    $p1TurnStats = $theirTurnStats;
  }
}

function UpdateMainPlayerGameState()
{
}

function UpdateMainPlayerGameStateInner()
{
  global $mainPlayerGamestateStillBuilt, $mpgBuiltFor;
  global $mainHand, $mainDeck, $mainResources, $mainCharacter, $mainArsenal, $mainHealth, $mainAuras, $mainPitch, $mainBanish, $mainClassState, $mainItems;
  global $mainCharacterEffects, $mainDiscard;
  global $defHand, $defDeck, $defResources, $defCharacter, $defArsenal, $defHealth, $defAuras, $defPitch, $defBanish, $defClassState, $defItems;
  global $defCharacterEffects, $defDiscard;
  global $p1Deck, $p1Hand, $p1Resources, $p1CharEquip, $p1Arsenal, $playerHealths, $p1Auras, $p1Pitch, $p1Banish, $p1ClassState, $p1Items;
  global $p1CharacterEffects, $p1Discard;
  global $p2Deck, $p2Hand, $p2Resources, $p2CharEquip, $p2Arsenal, $p2Auras, $p2Pitch, $p2Banish, $p2ClassState, $p2Items;
  global $p2CharacterEffects, $p2Discard;
  global $p1Material, $p2Material, $mainMaterial, $defMaterial;
  global $p1CardStats, $p2CardStats, $mainCardStats, $defCardStats;
  global $p1TurnStats, $p2TurnStats, $mainTurnStats, $defTurnStats;

  $p1Deck = $mpgBuiltFor == 1 ? $mainDeck : $defDeck;
  $p1Hand = $mpgBuiltFor == 1 ? $mainHand : $defHand;
  $p1Resources = $mpgBuiltFor == 1 ? $mainResources : $defResources;
  $p1CharEquip = $mpgBuiltFor == 1 ? $mainCharacter : $defCharacter;
  $p1Arsenal = $mpgBuiltFor == 1 ? $mainArsenal : $defArsenal;
  $playerHealths[0] = $mpgBuiltFor == 1 ? $mainHealth : $defHealth;
  $p1Items = $mpgBuiltFor == 1 ? $mainItems : $defItems;
  $p1Auras = $mpgBuiltFor == 1 ? $mainAuras : $defAuras;
  $p1Pitch = $mpgBuiltFor == 1 ? $mainPitch : $defPitch;
  $p1Banish = $mpgBuiltFor == 1 ? $mainBanish : $defBanish;
  $p1ClassState = $mpgBuiltFor == 1 ? $mainClassState : $defClassState;
  $p1CharacterEffects = $mpgBuiltFor == 1 ? $mainCharacterEffects : $defCharacterEffects;
  $p1Discard = $mpgBuiltFor == 1 ? $mainDiscard : $defDiscard;
  $p1Material = $mpgBuiltFor == 1 ? $mainMaterial : $defMaterial;
  $p1CardStats = $mpgBuiltFor == 1 ? $mainCardStats : $defCardStats;
  $p1TurnStats = $mpgBuiltFor == 1 ? $mainTurnStats : $defTurnStats;
  $p2Deck = $mpgBuiltFor == 2 ? $mainDeck : $defDeck;
  $p2Hand = $mpgBuiltFor == 2 ? $mainHand : $defHand;
  $p2Resources = $mpgBuiltFor == 2 ? $mainResources : $defResources;
  $p2CharEquip = $mpgBuiltFor == 2 ? $mainCharacter : $defCharacter;
  $p2Arsenal = $mpgBuiltFor == 2 ? $mainArsenal : $defArsenal;
  $playerHealths[1] = $mpgBuiltFor == 2 ? $mainHealth : $defHealth;
  $p2Items = $mpgBuiltFor == 2 ? $mainItems : $defItems;
  $p2Auras = $mpgBuiltFor == 2 ? $mainAuras : $defAuras;
  $p2Pitch = $mpgBuiltFor == 2 ? $mainPitch : $defPitch;
  $p2Banish = $mpgBuiltFor == 2 ? $mainBanish : $defBanish;
  $p2ClassState = $mpgBuiltFor == 2 ? $mainClassState : $defClassState;
  $p2CharacterEffects = $mpgBuiltFor == 2 ? $mainCharacterEffects : $defCharacterEffects;
  $p2Discard = $mpgBuiltFor == 2 ? $mainDiscard : $defDiscard;
  $p2Material = $mpgBuiltFor == 2 ? $mainMaterial : $defMaterial;
  $p2CardStats = $mpgBuiltFor == 2 ? $mainCardStats : $defCardStats;
  $p2TurnStats = $mpgBuiltFor == 2 ? $mainTurnStats : $defTurnStats;
}

function MakeGamestateBackup($filename = "gamestateBackup.txt")
{
  global $filepath;
  if(!file_exists($filepath . "gamestate.txt")) WriteLog("Cannot copy gamestate file; it does not exist.");
  $result = copy($filepath . "gamestate.txt", $filepath . $filename);
  if(!$result) WriteLog("Copy of gamestate into " . $filename . " failed.");
}

function RevertGamestate($filename = "gamestateBackup.txt")
{
  global $gameName, $skipWriteGamestate, $useRedis, $filepath;
  if($useRedis)
  {
    $gamestate = file_get_contents($filepath . $filename);
    WriteCache($gameName . "GS", $gamestate);
  }
  copy($filepath . $filename, $filepath . "gamestate.txt");
  $skipWriteGamestate = true;
}

function MakeStartTurnBackup()
{
  global $mainPlayer, $currentRound, $filepath;
  $lastTurnFN = $filepath . "lastTurnGamestate.txt";
  $thisTurnFN = $filepath . "beginTurnGamestate.txt";
  if (file_exists($thisTurnFN)) copy($thisTurnFN, $lastTurnFN);
  copy($filepath . "gamestate.txt", $thisTurnFN);
  $startGameFN = $filepath . "startGamestate.txt";
  if ((IsPatron(1) || IsPatron(2)) && $currentRound== 1 && !file_exists($startGameFN)) {
    copy($filepath . "gamestate.txt", $startGameFN);
  }
}
