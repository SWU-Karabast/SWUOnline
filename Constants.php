<?php

$GameStatus_Over = 2;
$GameStatus_Rematch = 3;

function DeckPieces()
{
  return 1;
}

function HandPieces()
{
  return 1;
}

//0 - Card ID
//1 - Discard Pile Modifier (e.g. TTFREE). Default: "-".
//2 - From (HAND, DECK, PLAY, RESOURCES)
//3 - Round discarded
function DiscardPieces()
{
  return 4;
}

//0 - Card ID
//1 - Status (2=ready, 1=unavailable, 0=destroyed)
//2 - Num counters
//3 - Num attack counters
//4 - Num defense counters
//5 - Num uses
//6 - On chain (1 = yes, 0 = no)
//7 - Flagged for destruction (1 = yes, 0 = no)
//8 - Frozen (1 = yes, 0 = no)
//9 - Is Active (2 = always active, 1 = yes, 0 = no)
//10 - Position (0 = normal, 1 = distant)
function CharacterPieces()
{
  return 11;
}

//TODO(TwinSuns): look into how many places are calling charArr[CharacterPieces()] to get the leader cardID

//0 - Card ID
//1 - Mods (INT == Intimidated)
//2 - Unique ID?
function BanishPieces()
{
  return 3;
}

//0 - Card ID
//1 - Player
//2 - From
//3 - Resources Paid
//4 - Reprise Active? (Or other class effects?)
//5 - Attack Modifier
//6 - Defense Modifier
function CombatChainPieces()
{
  return 7;
}

//0 - Card ID
//1 - Status (2=ready, 1=unavailable, 0=destroyed)
//2 - Num counters
//3 - Num attack counters
//4 - Is Token (1 = yes, 0 = no)
//5 - Number of ability uses (triggered or activated)
//6 - Unique ID
//7 - My Hold priority for triggers (2 = always hold, 1 = hold, 0 = don't hold)
//8 - Opponent Hold priority for triggers (2 = always hold, 1 = hold, 0 = don't hold)
function AuraPieces()
{
  return 9;
}

//0 - Item ID
//1 - Counters/Steam Counters
//2 - Status (2=ready, 1=unavailable, 0=destroyed)
//3 - Num Uses
//4 - Unique ID
//5 - My Hold priority for triggers (2 = always hold, 1 = hold, 0 = don't hold)
//6 - Opponent Hold priority for triggers (2 = always hold, 1 = hold, 0 = don't hold)
function ItemPieces()
{
  return 7;
}

function PitchPieces()
{
  return 1;
}

//0 - Effect ID
//1 - Player ID
//2 - Applies to Unique ID
//3 - Number of uses remaining
function CurrentTurnPieces()
{
  return 4;
}

//0 - ?
//1 - Effect Card ID
function CharacterEffectPieces()
{
  return 2;
}

//0 - Card ID
//1 - Face up/down
//2 - ?
//3 - Counters
//4 - Exhausted: 0 = no, 1 = yes
//5 - Unique ID
//6 - Steal Source (i.e. DJ or Arquitens)
function ArsenalPieces()
{
  return 7;
}
function MemoryPieces() { return ArsenalPieces(); }
function ResourcePieces() { return ArsenalPieces(); }

//0 - Card ID
//1 - Status: 2 = ready
//2 - Health
//3 - Frozen - 0 = no, 1 = yes
//4 - Subcards , delimited
//5 - Unique ID
//6 - Counters
//7 - Buff Counters
//8 - Ability/effect Uses
//9 - Round health modifier
//10 - Times Attacked
//11 - Owner
//12 - Turns in play
//13 - Cloned - 0 = no, 1 = yes
//14 - Healed
//15 - Arena Override
//16 - Unused
function AllyPieces()
{
  return 17;
}

//Card ID
function PermanentPieces()
{
  return 1;
}

//0 - Card ID/Layer type
//1 - Player
//2 - Parameter (For play card | Delimited, piece 0 = $from)
//3 - Target
//4 - Additional Costs
//5 - Unique ID (the unique ID of the object that created the layer)
//6 - Layer Unique ID (the unique ID of the layer)
function LayerPieces()
{
  return 7;
}

function LandmarkPieces()
{
  return 2;
}

//0 - Card ID
//1 - Player ID
//2 - Still on chain? 1 = yes, 0 = no
//3 - From
//4 - Attack Modifier
//5 - Defense Modifier
function ChainLinksPieces()
{
  return 6;
}

//0 - Damage Dealt
//1 - Total Attack
//2 - Talents
//3 - Class
//4 - List of names
//5 - Hit on link
function ChainLinkSummaryPieces()
{
  return 6;
}

function DecisionQueuePieces()
{
  return 5;
}

//0 - Card ID
function MaterialPieces()
{
  return 1;
}

//0 - Event type
//1 - Event Value
function EventPieces()
{
  return 2;
}

//0 - cardId
//1 - ownerId
//2 - isPilot
//3 - subcard uniqueId
function SubcardPieces(){
  return 4;
}

$SHMOP_CURRENTPLAYER = 9;
$SHMOP_ISREPLAY = 10;//0 = not replay, 1 = replay

//Class State (one for each player)
$CS_NumVillainyPlayed = 0;
$CS_PlayedAsUpgrade = 1;
$CS_AtksWWeapon = 2;                    //free//number
$CS_NumNonTokenVehicleAttacks = 3;
$CS_DamagePrevention = 4;
$CS_CardsDrawn = 5;
$CS_DamageTaken = 6;
$CS_NumActionsPlayed = 7;               //free//number
$CS_ArsenalFacing = 8;                  //free//number
$CS_CharacterIndex = 9;
$CS_PlayIndex = 10;
$CS_NumNonAttackCards = 11;             //free//number
$CS_CachedCharacterLevel = 12;          //free//number
$CS_PreparationCounters = 13;           //free//number
$CS_NextNAACardGoAgain = 14;            //free//number
$CS_NumAlliesDestroyed = 15;
$CS_NumWhenDefeatedPlayed = 16;
$CS_ResolvingLayerUniqueID = 17;        //free//number
$CS_NextWizardNAAInstant = 18;          //free//number
$CS_ArcaneDamageTaken = 19;             //free//number
$CS_NextNAAInstant = 20;                //free//number
$CS_NextDamagePrevented = 21;           //free//number
$CS_LastAttack = 22;
$CS_NumLeftPlay = 23;
$CS_NumUsesLeaderUpgrade1 = 24;
$CS_NumUsesLeaderUpgrade2 = 25;
$CS_AfterPlayedBy = 26;
$CS_PlayCCIndex = 27;                   //free//number
$CS_NumAttackCards = 28;                //free/number
$CS_NumPlayedFromBanish = 29;           //free//number
$CS_NumAttacks = 30;
$CS_DieRoll = 31;
$CS_NumMandalorianAttacks = 32;
$CS_NumFighterAttacks = 33;
$CS_LayerTarget = 34;
$CS_NumSwordAttacks = 35;               //free//number
$CS_HitsWithWeapon = 36;
$CS_ArcaneDamagePrevention = 37;        //free//number
$CS_DynCostResolved = 38;
$CS_CardsEnteredGY = 39;
$CS_HighestRoll = 40;
$CS_NumMelodyPlayed = 41;               //free//number
$CS_NumAuras = 42;                      //free//number
$CS_AbilityIndex = 43;
$CS_AdditionalCosts = 44;
$CS_NumRedPlayed = 45;                  //free//number
$CS_PlayUniqueID = 46;
$CS_NumPhantasmAADestroyed = 47;        //free//number
$CS_NumEventsPlayed = 48;
$CS_AlluvionUsed = 49;
$CS_MaxQuellUsed = 50;                  //free//number
$CS_DamageDealt = 51; //Only includes damage dealt by the hero. CR 2.1 8.2.8f If an ally deals damage, the controlling player and their hero are not considered to have dealt damage.
$CS_ArcaneTargetsSelected = 52;
$CS_NumDragonAttacks = 53;             //free//number
$CS_NumIllusionistAttacks = 54;       //free//number
$CS_LastDynCost = 55;
$CS_NumIllusionistActionCardAttacks = 56;//free//number
$CS_ArcaneDamageDealt = 57;           //free//number
$CS_LayerPlayIndex = 58;
$CS_NumCardsPlayed = 59; //Amulet of Ignition
$CS_NamesOfCardsPlayed = 60;          //free//string;default "-"
$CS_NumFirstOrderPlayed = 61;
$CS_PlayedAsInstant = 62; //If the card was played as an instant -- some things like banish we lose memory of as soon as it is removed from the zone
$CS_LeaderUpgradeAbilityID1 = 63;
$CS_LeaderUpgradeAbilityID2 = 64;
$CS_HitsWithSword = 65;
$CS_NumClonesPlayed = 66;
$CS_UnitsThatAttackedBase = 67;
$CS_OppIndex = 68;
$CS_OppCardActive = 69;
$CS_PlayedWithExploit = 70;
$CS_SeparatistUnitsThatAttacked = 71;
$CS_AlliesDestroyed = 72; // List of allies (CardID) destroyed concatenated with a comma
$CS_NumBountyHuntersPlayed = 73;
$CS_NumPilotsPlayed = 74;


function SetAfterPlayedBy($player, $cardID)
{
  global $CS_AfterPlayedBy;
  SetClassState($player, $CS_AfterPlayedBy, $cardID);
}


//Combat Chain State (State for the current combat chain)
$CCS_CurrentAttackGainedGoAgain = 0;
$CCS_WeaponIndex = 1;
$CCS_IsAmbush = 2;
$CCS_NumHits = 3;//Deprecated -- use HitsInCombatChain() or NumAttacksHit() instead
$CCS_DamageDealt = 4;
$CCS_HitsInRow = 5;//Deprecated -- use HitsInRow() instead
$CCS_HitsWithWeapon = 6;
$CCS_GoesWhereAfterLinkResolves = 7;
$CCS_AttackPlayedFrom = 8;
$CCS_ChainAttackBuff = 9;//Deprecated -- Use persistent combat effect with RemoveEffectsOnChainClose instead
$CCS_ChainLinkHitEffectsPrevented = 10;
$CCS_NumBoosted = 11;
$CCS_NextBoostBuff = 12;//Deprecated -- use $CCS_IsBoosted now.
$CCS_AttackFused = 13;
$CCS_AttackTotalDamage = 14;//Deprecated -- use chain link summary instead, it has all of them
$CCS_NumChainLinks = 15;//Deprecated -- use NumChainLinks() instead
$CCS_AttackTarget = 16;
$CCS_LinkTotalAttack = 17;
$CCS_LinkBaseAttack = 18;
$CCS_BaseAttackDefenseMax = 19;
$CCS_ResourceCostDefenseMin = 20;
$CCS_AfterLinkLayers = 21;
$CCS_CachedTotalAttack = 22;
$CCS_CachedTotalBlock = 23;
$CCS_CombatDamageReplaced = 24; //CR 6.5.3, CR 6.5.4 (CR 2.0)
$CCS_AttackUniqueID = 25;
$CCS_RequiredEquipmentBlock = 26;
$CCS_CachedDominateActive = 27;
$CCS_CachedNumBlockedFromHand = 28;
$CCS_IsBoosted = 29;
$CCS_AttackTargetUID = 30;
$CCS_CachedOverpowerActive = 31;
$CSS_CachedNumActionBlocked = 32;
$CCS_CachedNumDefendedFromHand = 33;
$CCS_HitThisLink = 34;
$CCS_CantAttackBase = 35;
$CCS_CachedLastDestroyed = 36;
$CCS_MultiAttackTargets = 37;

function ResetCombatChainState()
{
  global $combatChainState, $CCS_CurrentAttackGainedGoAgain, $CCS_WeaponIndex, $CCS_DamageDealt;
  global $CCS_HitsWithWeapon, $CCS_GoesWhereAfterLinkResolves, $CCS_AttackPlayedFrom, $CCS_ChainLinkHitEffectsPrevented;
  global $CCS_NumBoosted, $CCS_AttackFused, $CCS_AttackTotalDamage, $CCS_AttackTarget;
  global $CCS_LinkTotalAttack, $CCS_LinkBaseAttack, $CCS_BaseAttackDefenseMax, $CCS_ResourceCostDefenseMin, $CCS_AfterLinkLayers;
  global $CCS_CachedTotalAttack, $CCS_CachedTotalBlock, $CCS_CombatDamageReplaced, $CCS_AttackUniqueID, $CCS_RequiredEquipmentBlock;
  global $mainPlayer, $defPlayer, $CCS_CachedDominateActive, $CCS_CachedNumBlockedFromHand, $CCS_IsBoosted, $CCS_AttackTargetUID, $CCS_CachedOverpowerActive, $CSS_CachedNumActionBlocked;
  global $layers, $chainLinks, $chainLinkSummary, $CCS_CachedNumDefendedFromHand, $CCS_HitThisLink, $CCS_IsAmbush, $CCS_CantAttackBase, $CCS_CachedLastDestroyed, $CCS_MultiAttackTargets;

  $combatChainState[$CCS_CurrentAttackGainedGoAgain] = 0;   //0
  $combatChainState[$CCS_WeaponIndex] = -1;                 //1
  $combatChainState[$CCS_IsAmbush] = 0;                     //2
  $combatChainState[$CCS_DamageDealt] = 0;                  //4
  $combatChainState[$CCS_HitsWithWeapon] = 0;               //6
  $combatChainState[$CCS_GoesWhereAfterLinkResolves] = "GY";//7
  $combatChainState[$CCS_AttackPlayedFrom] = "NA";          //8
  $combatChainState[$CCS_ChainLinkHitEffectsPrevented] = 0; //10
  $combatChainState[$CCS_NumBoosted] = 0;                   //11
  $combatChainState[$CCS_AttackFused] = 0;                  //13
  $combatChainState[$CCS_AttackTotalDamage] = 0;            //14
  $combatChainState[$CCS_AttackTarget] = "NA";              //16
  $combatChainState[$CCS_LinkTotalAttack] = 0;              //17
  $combatChainState[$CCS_LinkBaseAttack] = 0;               //18
  $combatChainState[$CCS_BaseAttackDefenseMax] = -1;        //19
  $combatChainState[$CCS_ResourceCostDefenseMin] = -1;      //20
  $combatChainState[$CCS_AfterLinkLayers] = "NA";           //21
  $combatChainState[$CCS_CachedTotalAttack] = 0;            //22
  $combatChainState[$CCS_CachedTotalBlock] = 0;             //23
  $combatChainState[$CCS_CombatDamageReplaced] = 0;         //24
  $combatChainState[$CCS_AttackUniqueID] = -1;              //25
  $combatChainState[$CCS_RequiredEquipmentBlock] = 0;       //26
  $combatChainState[$CCS_CachedDominateActive] = 0;         //27
  $combatChainState[$CCS_CachedNumBlockedFromHand] = 0;     //28
  $combatChainState[$CCS_IsBoosted] = 0;                    //29
  $combatChainState[$CCS_AttackTargetUID] = "-";            //30
  $combatChainState[$CCS_CachedOverpowerActive] = 0;        //31
  $combatChainState[$CSS_CachedNumActionBlocked] = 0;       //32
  $combatChainState[$CCS_CachedNumDefendedFromHand] = 0;    //33
  $combatChainState[$CCS_HitThisLink] = 0;                  //34
  $combatChainState[$CCS_CantAttackBase] = 0;               //35
  $combatChainState[$CCS_CachedLastDestroyed] = "NA";       //36
  $combatChainState[$CCS_MultiAttackTargets] = "-";         //37
  $defCharacter = &GetPlayerCharacter($defPlayer);
  for ($i = 0; $i < count($defCharacter); $i += CharacterPieces()) {
    $defCharacter[$i + 6] = 0;
  }
  for ($i = 0; $i < count($chainLinks); ++$i) {
    for ($j = 0; $j < count($chainLinks[$i]); $j += ChainLinksPieces()) {
      if ($chainLinks[$i][$j + 2] != "1") continue;
      $cardType = CardType($chainLinks[$i][$j]);
      if ($cardType != "AA" && $cardType != "DR" && $cardType != "AR" && $cardType != "A") continue;
      $goesWhere = GoesWhereAfterResolving($chainLinks[$i][$j], "CHAINCLOSING", $chainLinks[$i][$j + 1], $chainLinks[$i][$j + 3]);
      switch ($goesWhere) {
        case "GY":
          AddGraveyard($chainLinks[$i][$j], $chainLinks[$i][$j + 1], "CC");
          break;
        case "BOTDECK":
          AddBottomDeck($chainLinks[$i][$j], $mainPlayer);
          break;
        case "HAND":
          AddPlayerHand($chainLinks[$i][$j], $mainPlayer, "CC");
          break;
        // case "SOUL"://FAB
        //   AddSoul($chainLinks[$i][$j], $chainLinks[$i][$j + 1], "CC");
          break;
        default:
          break;
      }
    }
  }
  UnsetCombatChainBanish();
  CombatChainClosedCharacterEffects();
  CombatChainClosedMainCharacterEffects();
  RemoveEffectsOnChainClose();
  $chainLinks = [];
  $chainLinkSummary = [];
}

function AttackReplaced()
{
  global $combatChainState;
  global $CCS_CurrentAttackGainedGoAgain, $CCS_GoesWhereAfterLinkResolves, $CCS_AttackPlayedFrom, $CCS_LinkBaseAttack;
  $combatChainState[$CCS_CurrentAttackGainedGoAgain] = 0;
  $combatChainState[$CCS_GoesWhereAfterLinkResolves] = "GY";
  $combatChainState[$CCS_AttackPlayedFrom] = "BANISH";//Right now only Uzuri can do this
  $combatChainState[$CCS_LinkBaseAttack] = 0;
  CleanUpCombatEffects(true);
}

function ResetChainLinkState()
{
  global $combatChainState, $CCS_CurrentAttackGainedGoAgain, $CCS_WeaponIndex, $CCS_IsAmbush, $CCS_DamageDealt, $CCS_GoesWhereAfterLinkResolves;
  global $CCS_AttackPlayedFrom, $CCS_ChainLinkHitEffectsPrevented, $CCS_AttackFused, $CCS_AttackTotalDamage, $CCS_AttackTarget;
  global $CCS_LinkTotalAttack, $CCS_LinkBaseAttack, $CCS_BaseAttackDefenseMax, $CCS_ResourceCostDefenseMin, $CCS_AfterLinkLayers;
  global $CCS_CachedTotalAttack, $CCS_CachedTotalBlock, $CCS_CombatDamageReplaced, $CCS_AttackUniqueID, $CCS_RequiredEquipmentBlock;
  global $CCS_CachedDominateActive, $CCS_CachedNumBlockedFromHand, $CCS_IsBoosted, $CCS_AttackTargetUID, $CCS_CachedOverpowerActive, $CSS_CachedNumActionBlocked;
  global $CCS_CachedNumDefendedFromHand, $CCS_HitThisLink, $CCS_CantAttackBase, $CCS_CachedLastDestroyed, $CCS_MultiAttackTargets;
  WriteLog("The chain link was closed.");
  $combatChainState[$CCS_CurrentAttackGainedGoAgain] = 0;
  $combatChainState[$CCS_WeaponIndex] = -1;
  $combatChainState[$CCS_IsAmbush] = 0;
  $combatChainState[$CCS_DamageDealt] = 0;
  $combatChainState[$CCS_GoesWhereAfterLinkResolves] = "GY";
  $combatChainState[$CCS_AttackPlayedFrom] = "NA";
  $combatChainState[$CCS_ChainLinkHitEffectsPrevented] = 0;
  $combatChainState[$CCS_AttackFused] = 0;
  $combatChainState[$CCS_AttackTotalDamage] = 0;
  $combatChainState[$CCS_AttackTarget] = "NA";
  $combatChainState[$CCS_LinkTotalAttack] = 0;
  $combatChainState[$CCS_LinkBaseAttack] = 0;
  $combatChainState[$CCS_BaseAttackDefenseMax] = -1;
  $combatChainState[$CCS_ResourceCostDefenseMin] = -1;
  $combatChainState[$CCS_AfterLinkLayers] = "NA";
  $combatChainState[$CCS_CachedTotalAttack] = 0;
  $combatChainState[$CCS_CachedTotalBlock] = 0;
  $combatChainState[$CCS_CombatDamageReplaced] = 0;
  $combatChainState[$CCS_AttackUniqueID] = -1;
  $combatChainState[$CCS_RequiredEquipmentBlock] = 0;
  $combatChainState[$CCS_CachedDominateActive] = 0;
  $combatChainState[$CCS_CachedNumBlockedFromHand] = 0;
  $combatChainState[$CCS_IsBoosted] = 0;
  $combatChainState[$CCS_AttackTargetUID] = "-";
  $combatChainState[$CCS_CachedOverpowerActive] = 0;
  $combatChainState[$CSS_CachedNumActionBlocked] = 0;
  $combatChainState[$CCS_CachedNumDefendedFromHand] = 0;
  $combatChainState[$CCS_HitThisLink] = 0;
  $combatChainState[$CCS_CantAttackBase] = 0;
  $combatChainState[$CCS_CachedLastDestroyed] = "NA";
  $combatChainState[$CCS_MultiAttackTargets] = "-";
  UnsetChainLinkBanish();
}

function ResetClassState($player)
{
  global $CS_NumVillainyPlayed, $CS_PlayedAsUpgrade, $CS_AtksWWeapon, $CS_NumNonTokenVehicleAttacks, $CS_DamagePrevention, $CS_CardsDrawn, $CS_NumBountyHuntersPlayed, $CS_NumPilotsPlayed;
  global $CS_DamageTaken, $CS_NumActionsPlayed, $CS_CharacterIndex, $CS_PlayIndex, $CS_OppIndex, $CS_OppCardActive, $CS_NumNonAttackCards;
  global $CS_PreparationCounters, $CS_NextNAACardGoAgain, $CS_NumAlliesDestroyed, $CS_NumWhenDefeatedPlayed, $CS_ResolvingLayerUniqueID, $CS_NextWizardNAAInstant;
  global $CS_ArcaneDamageTaken, $CS_NextNAAInstant, $CS_NextDamagePrevented, $CS_LastAttack, $CS_PlayCCIndex;
  global $CS_NumLeftPlay, $CS_NumUsesLeaderUpgrade1, $CS_NumUsesLeaderUpgrade2, $CS_AfterPlayedBy, $CS_NumAttackCards, $CS_NumPlayedFromBanish;
  global $CS_NumAttacks, $CS_DieRoll, $CS_NumMandalorianAttacks, $CS_SeparatistUnitsThatAttacked, $CS_NumFighterAttacks, $CS_LayerTarget, $CS_NumSwordAttacks;
  global $CS_HitsWithWeapon, $CS_ArcaneDamagePrevention, $CS_DynCostResolved, $CS_CardsEnteredGY, $CS_CachedCharacterLevel, $CS_ArsenalFacing;
  global $CS_HighestRoll, $CS_NumAuras, $CS_AbilityIndex, $CS_AdditionalCosts, $CS_NumRedPlayed, $CS_PlayUniqueID, $CS_AlluvionUsed;
  global $CS_NumPhantasmAADestroyed, $CS_NumEventsPlayed, $CS_MaxQuellUsed, $CS_DamageDealt, $CS_ArcaneTargetsSelected, $CS_NumDragonAttacks, $CS_NumIllusionistAttacks;
  global $CS_LastDynCost, $CS_NumIllusionistActionCardAttacks, $CS_ArcaneDamageDealt, $CS_LayerPlayIndex, $CS_NumCardsPlayed, $CS_NamesOfCardsPlayed;
  global $CS_PlayedAsInstant, $CS_LeaderUpgradeAbilityID1, $CS_LeaderUpgradeAbilityID2, $CS_HitsWithSword, $CS_NumMelodyPlayed,
    $CS_NumClonesPlayed, $CS_UnitsThatAttackedBase, $CS_PlayedWithExploit, $CS_AlliesDestroyed, $CS_NumFirstOrderPlayed;

  $classState = &GetPlayerClassState($player);
  $classState[$CS_NumVillainyPlayed] = 0;
  $classState[$CS_PlayedAsUpgrade] = 0;
  $classState[$CS_AtksWWeapon] = 0;
  $classState[$CS_NumNonTokenVehicleAttacks] = 0;
  $classState[$CS_DamagePrevention] = 0;
  $classState[$CS_CardsDrawn] = 0;
  $classState[$CS_DamageTaken] = 0;
  $classState[$CS_NumActionsPlayed] = 0;
  $classState[$CS_ArsenalFacing] = 0;
  $classState[$CS_CharacterIndex] = 0;
  $classState[$CS_PlayIndex] = -1;
  $classState[$CS_NumNonAttackCards] = 0;
  $classState[$CS_CachedCharacterLevel] = 0;
  $classState[$CS_PreparationCounters] = 0;
  $classState[$CS_NextNAACardGoAgain] = 0;
  $classState[$CS_NumAlliesDestroyed] = 0;
  $classState[$CS_NumWhenDefeatedPlayed] = 0;
  $classState[$CS_ResolvingLayerUniqueID] = -1;
  $classState[$CS_NextWizardNAAInstant] = 0;
  $classState[$CS_ArcaneDamageTaken] = 0;
  $classState[$CS_NextNAAInstant] = 0;
  $classState[$CS_NextDamagePrevented] = 0;
  $classState[$CS_LastAttack] = "NA";
  $classState[$CS_NumLeftPlay] = 0;
  $classState[$CS_NumUsesLeaderUpgrade1] = 1;
  $classState[$CS_NumUsesLeaderUpgrade2] = 1;
  $classState[$CS_AfterPlayedBy] = "-";
  $classState[$CS_PlayCCIndex] = -1;
  $classState[$CS_NumAttackCards] = 0;
  $classState[$CS_NumPlayedFromBanish] = 0;
  $classState[$CS_NumAttacks] = 0;
  $classState[$CS_DieRoll] = 0;
  $classState[$CS_NumMandalorianAttacks] = 0;
  $classState[$CS_SeparatistUnitsThatAttacked] = "-";
  $classState[$CS_NumFighterAttacks] = 0;
  $classState[$CS_LayerTarget] = "-";
  $classState[$CS_NumSwordAttacks] = 0;
  $classState[$CS_HitsWithWeapon] = 0;
  $classState[$CS_ArcaneDamagePrevention] = 0;
  $classState[$CS_DynCostResolved] = 0;
  $classState[$CS_CardsEnteredGY] = 0;
  $classState[$CS_HighestRoll] = 0;
  $classState[$CS_NumMelodyPlayed] = 0;
  $classState[$CS_NumAuras] = 0;
  $classState[$CS_AbilityIndex] = "-";
  $classState[$CS_AdditionalCosts] = "-";
  $classState[$CS_NumRedPlayed] = 0;
  $classState[$CS_PlayUniqueID] = -1;
  $classState[$CS_NumPhantasmAADestroyed] = 0;
  $classState[$CS_NumEventsPlayed] = 0;
  $classState[$CS_AlluvionUsed] = 0;
  $classState[$CS_MaxQuellUsed] = 0;
  $classState[$CS_DamageDealt] = 0;
  $classState[$CS_ArcaneTargetsSelected] = "-";
  $classState[$CS_NumDragonAttacks] = 0;
  $classState[$CS_NumIllusionistAttacks] = 0;
  $classState[$CS_LastDynCost] = 0;
  $classState[$CS_NumIllusionistActionCardAttacks] = 0;
  $classState[$CS_ArcaneDamageDealt] = 0;
  $classState[$CS_LayerPlayIndex] = -1;
  $classState[$CS_NumCardsPlayed] = 0;
  $classState[$CS_NamesOfCardsPlayed] = "-";
  $classState[$CS_NumFirstOrderPlayed] = 0;
  $classState[$CS_PlayedAsInstant] = 0;
  $classState[$CS_LeaderUpgradeAbilityID1] = "-";
  $classState[$CS_LeaderUpgradeAbilityID2] = "-";
  $classState[$CS_HitsWithSword] = 0;
  $classState[$CS_NumClonesPlayed] = 0;
  $classState[$CS_UnitsThatAttackedBase] = "-";
  $classState[$CS_OppIndex] = -1;
  $classState[$CS_OppCardActive] = 0;
  $classState[$CS_PlayedWithExploit] = 0;
  $classState[$CS_AlliesDestroyed] = "-";
  $classState[$CS_NumBountyHuntersPlayed] = 0;
  $classState[$CS_NumPilotsPlayed] = 0;
}

function ResetCharacterEffects()
{
  global $mainCharacterEffects, $defCharacterEffects;
  $mainCharacterEffects = [];
  $defCharacterEffects = [];
}

function SetAttackTarget($mzTarget)
{
  global $combatChainState, $CCS_AttackTarget, $CCS_AttackTargetUID, $defPlayer, $combatChain;
  if($mzTarget == "") return;
  $mzArr = explode("-", $mzTarget);
  $combatChainState[$CCS_AttackTarget] = $mzTarget;
  $combatChainState[$CCS_AttackTargetUID] = MZGetUniqueID($mzTarget, $defPlayer);
}

function UpdateAttacker() {
  global $combatChainState, $CCS_WeaponIndex, $CCS_AttackUniqueID, $mainPlayer;
  $index = SearchAlliesForUniqueID($combatChainState[$CCS_AttackUniqueID], $mainPlayer);
  $combatChainState[$CCS_WeaponIndex] = $index == -1 ? $combatChainState[$CCS_WeaponIndex] : $index;
}

function UpdateAttackTarget() {
  global $combatChainState, $CCS_AttackTarget, $CCS_AttackTargetUID, $defPlayer;
  $mzArr = explode("-", $combatChainState[$CCS_AttackTarget]);
  if($mzArr[0] = "THEIRCHAR") return;
  $index = SearchAlliesForUniqueID($combatChainState[$CCS_AttackTargetUID], $defPlayer);
  $combatChainState[$CCS_AttackTarget] = $index == -1 ? "NA" : $mzArr[0] . "-" . $index;
}

function GetAttackTarget()
{
  global $combatChainState, $CCS_AttackTarget, $CCS_AttackTargetUID, $defPlayer;
  $uid = $combatChainState[$CCS_AttackTargetUID];
  if($uid == "-") return $combatChainState[$CCS_AttackTarget];
  $mzArr = explode("-", $combatChainState[$CCS_AttackTarget]);
  $index = SearchZoneForUniqueID($uid, $defPlayer, $mzArr[0]);
  return $mzArr[0] . "-" . $index;
}

function ClearAttackTarget() {
  global $combatChainState, $CCS_AttackTarget, $CCS_AttackTargetUID;
  $combatChainState[$CCS_AttackTarget] = "NA";
  $combatChainState[$CCS_AttackTargetUID] = "-";
}

function GetDamagePrevention($player)
{
  global $CS_DamagePrevention;
  return GetClassState($player, $CS_DamagePrevention);
}

function AttackPlayedFrom()
{
  global $CCS_AttackPlayedFrom, $combatChainState;
  return $combatChainState[$CCS_AttackPlayedFrom];
}

function CCOffset($piece)
{
  switch($piece)
  {
    case "player": return 1;
    default: return 0;
  }
}
