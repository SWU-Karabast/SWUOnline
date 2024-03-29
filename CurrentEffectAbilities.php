<?php


//Return 1 if the effect should be removed
function EffectHitEffect($cardID)
{
  global $combatChainState, $CCS_GoesWhereAfterLinkResolves, $defPlayer, $mainPlayer, $CCS_WeaponIndex, $combatChain, $CCS_DamageDealt;
  $attackID = $combatChain[0];
  switch($cardID) {
    case "6954704048"://Heroic Sacrifice
      $ally = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
      WriteLog("Heroic Sacrifice defeated " . CardLink($ally->CardID(), $ally->CardID()));
      $ally->Destroy();
    default:
      break;
  }
  return 0;
}

function EffectAttackModifier($cardID)
{
  global $mainPlayer;
  $params = explode("-", $cardID);
  $cardID = $params[0];
  if(count($params) > 1) $subparam = $params[1];
  switch($cardID)
  {
    case "2587711125": return -4;//Disarm
    case "2569134232": return -4;//Jedha City
    case "1323728003": return -1;//Electrostaff
    case "2651321164": return 2;//Tactical Advantage
    case "1900571801": return 2;//Overwhelming Barrage
    case "3809048641": return 3;//Surprise Strike
    case "3038238423": return 2;//Fleet Lieutenant
    case "3208391441": return -2;//Make an Opening
    case "6432884726": return 2;//Steadfast Battalion
    case "8244682354": return -1;//Jyn Erso
    case "8600121285": return 1;//IG-88
    case "6954704048": return 2;//Heroic Sacrifice
    default: return 0;
  }
}

function EffectHasBlockModifier($cardID)
{
  switch($cardID)
  {
    default: return false;
  }
}

function EffectBlockModifier($cardID, $index)
{
  global $combatChain, $defPlayer, $mainPlayer;
  switch($cardID) {

    default:
      return 0;
  }
}

function RemoveEffectsOnChainClose()
{

}

function OnAttackEffects($attack)
{
  global $currentTurnEffects, $mainPlayer, $defPlayer;
  $attackType = CardType($attack);
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $mainPlayer) {
      switch($currentTurnEffects[$i]) {

        default:
          break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
}

function CurrentEffectBaseAttackSet($cardID)
{
  global $currentPlayer, $currentTurnEffects;
  $mod = -1;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    if($currentTurnEffects[$i + 1] == $currentPlayer && IsCombatEffectActive($currentTurnEffects[$i])) {
      switch($currentTurnEffects[$i]) {

        default: break;
      }
    }
  }
  return $mod;
}

function CurrentEffectCostModifiers($cardID, $from)
{
  global $currentTurnEffects, $currentPlayer, $CS_PlayUniqueID;
  $costModifier = 0;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch($currentTurnEffects[$i]) {
        case "5707383130"://Bendu
          if(!AspectContains($cardID, "Heroism", $currentPlayer) && !AspectContains($cardID, "Villainy", $currentPlayer)) {
            $costModifier -= 2;
            $remove = true;
          }
          break;
        case "4919000710"://Home One
          $costModifier -= 3;
          $remove = true;
          break;
        case "2756312994"://Alliance Dispatcher
          $costModifier -= 1;
          $remove = true;
          break;
        case "3509161777"://You're My Only Hope
          $costModifier -= PlayerRemainingHealth($player) <= 5 ? 99 : 5;
          $remove = true;
          break;
        case "5494760041"://Galactic Ambition
          $costModifier -= 99;
          $remove = true;
          break;
        default: break;
      }
      if($remove) RemoveCurrentTurnEffect($i);
    }
  }
  return $costModifier;
}

function CurrentEffectPreventDamagePrevention($player, $type, $damage, $source)
{
  global $currentTurnEffects;
  for($i = count($currentTurnEffects) - CurrentTurnEffectPieces(); $i >= 0; $i -= CurrentTurnEffectPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $player) {
      switch ($currentTurnEffects[$i]) {

        default: break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
  return $damage;
}

function CurrentEffectDamagePrevention($player, $type, $damage, $source, $preventable, $uniqueID=-1)
{
  global $currentPlayer, $currentTurnEffects;
  for($i = count($currentTurnEffects) - CurrentTurnEffectPieces(); $i >= 0 && $damage > 0; $i -= CurrentTurnEffectPieces()) {
    if($uniqueID != -1 && $currentTurnEffects[$i + 2] != $uniqueID) continue;
    $remove = false;
    if($currentTurnEffects[$i + 1] == $player || $uniqueID != -1) {
      $effects = explode("-", $currentTurnEffects[$i]);
      switch($effects[0]) {
        case "RUqtU0Lczf"://Spellshield: Arcane
          if($preventable)
          {
            PlayAura("ENLIGHTEN", $player, $damage);
            $damage = 0;
          }
          $remove = true;
          break;
        case "xWJND68I8X"://Water Barrier
          if($preventable) $damage = 1;
          $remove = true;
          break;
        case "yj2rJBREH8"://Safeguard Amulet
          if($preventable && $type != "COMBAT") $damage -= 4;
          break;
        case "KoF3AMSlUe"://Veiling Breeze
          if($preventable) $damage -= $effects[1];
          break;
        case "1lw9n0wpbh"://Protective Fractal
          if($preventable) $damage -= 1;
          $remove = true;
          break;
        case "2ha4dk88zq"://Cloak of Stillwater
          if($preventable) $damage -= 3;
          $remove = true;
          break;
        case "1n3gygojwk"://Evasive Maneuvers
          if($preventable) $damage -= 2;
          $remove = true;
          break;
        case "99sx6q3p6i"://Spellshield: Wind
          if($preventable) {
            if($damage >= 3) {
              AddDecisionQueue("MULTIZONEINDICES", $player, "MYALLY");
              AddDecisionQueue("CHOOSEMULTIZONE", $player, "<-", 1);
              AddDecisionQueue("MZOP", $player, "BUFFALLY", 1);
            }
            $damage -= 3;
          }
          $remove = true;
          break;
        case "fp66pv4n1n"://Rusted Warshield
          if($preventable) $damage -= 2;
          $remove = true;
          break;
        case "d6soporhlq"://Obelisk of Protection
          if($preventable) $damage -= 2;
          $remove = true;
          break;
        case "isxy5lh23q"://Flash Grenade
          if($preventable) $damage -= 3;
          break;
        case "nmp5af098k"://Spellshield: Astra
          if($preventable) {
            PlayerOpt($player, $damage);
            $damage -= $damage;
          }
          $remove = true;
          break;
        case "nsdwmxz1vd"://Martial Guard
          if($preventable) $damage -= 2;
          $remove = true;
          break;
        case "o7eanl1gxr"://Diffusive Block
          if($preventable) $damage -= 2;
          $remove = true;
          break;
        case "pv4n1n3gyg"://Cleric's Robe
          if($preventable) $damage -= 1;
          $remove = true;
          break;
        default: break;
      }
      if($remove) RemoveCurrentTurnEffect($i);
    }
  }
  return $damage;
}

function CurrentEffectAttackAbility()
{
  global $currentTurnEffects, $combatChain, $mainPlayer;
  global $CS_PlayIndex;
  if(count($combatChain) == 0) return;
  $attackID = $combatChain[0];
  $attackType = CardType($attackID);
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $mainPlayer) {
      switch ($currentTurnEffects[$i]) {
        case "Tx6iJQNSA6"://Majestic Spirit's Crest
          if(!IsAlly($attackID)) Draw($mainPlayer);
          break;
        default:
          break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
}

function CurrentEffectPlayAbility($cardID, $from)
{
  global $currentTurnEffects, $currentPlayer, $actionPoints, $CS_LastDynCost;

  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch($currentTurnEffects[$i]) {

        default:
          break;
      }
      if($remove) RemoveCurrentTurnEffect($i);
    }
  }
  return false;
}

function CurrentEffectPlayOrActivateAbility($cardID, $from)
{
  global $currentTurnEffects, $currentPlayer;

  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch($currentTurnEffects[$i]) {

        default:
          break;
      }
      if($remove) RemoveCurrentTurnEffect($i);
    }
  }
  $currentTurnEffects = array_values($currentTurnEffects); //In case any were removed
  return false;
}

function CurrentEffectGrantsNonAttackActionGoAgain($cardID)
{
  global $currentTurnEffects, $currentPlayer;
  $hasGoAgain = false;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch($currentTurnEffects[$i]) {

        default:
          break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
  return $hasGoAgain;
}

function CurrentEffectGrantsGoAgain()
{
  global $currentTurnEffects, $mainPlayer, $combatChainState, $CCS_AttackFused;
  for($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i + 1] == $mainPlayer && IsCombatEffectActive($currentTurnEffects[$i]) && !IsCombatEffectLimited($i)) {
      switch ($currentTurnEffects[$i]) {

        default:
          break;
      }
    }
  }
  return false;
}

function CurrentEffectPreventsGoAgain()
{
  global $currentTurnEffects, $mainPlayer;
  for($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i + 1] == $mainPlayer) {
      switch($currentTurnEffects[$i]) {
        default: break;
      }
    }
  }
  return false;
}

function CurrentEffectPreventsDefenseReaction($from)
{
  global $currentTurnEffects, $currentPlayer;
  $reactionPrevented = false;
  for($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch($currentTurnEffects[$i]) {

        default:
          break;
      }
    }
  }
  return $reactionPrevented;
}

function CurrentEffectPreventsDraw($player, $isMainPhase)
{
  global $currentTurnEffects;
  for($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i + 1] == $player) {
      switch ($currentTurnEffects[$i]) {
        default: break;
      }
    }
  }
  return false;
}

function CurrentEffectIntellectModifier()
{
  global $currentTurnEffects, $mainPlayer;
  $intellectModifier = 0;
  for($i = count($currentTurnEffects) - CurrentTurnEffectPieces(); $i >= 0; $i -= CurrentTurnEffectPieces()) {
    if($currentTurnEffects[$i + 1] == $mainPlayer) {
      switch($currentTurnEffects[$i]) {

        default: break;
      }
    }
  }
  return $intellectModifier;
}

function CurrentEffectEndTurnAbilities()
{
  global $currentTurnEffects, $mainPlayer;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    $cardID = substr($currentTurnEffects[$i], 0, 6);
    if(SearchCurrentTurnEffects($cardID . "-UNDER", $currentTurnEffects[$i + 1])) {
      AddNextTurnEffect($currentTurnEffects[$i], $currentTurnEffects[$i + 1]);
    }
    switch($currentTurnEffects[$i]) {
      case "5954056864": case "5e90bd91b0"://Han Solo
        MZChooseAndDestroy($currentTurnEffects[$i+1], "MYRESOURCES");
        break;
      case "2651321164"://Tactical Advantage
        $ally = new Ally("MYALLY-" . SearchAlliesForUniqueID($currentTurnEffects[$i+2], $currentTurnEffects[$i+1]), $currentTurnEffects[$i+1]);
        $ally->DealDamage(2);
        break;
      case "1900571801"://Overwhelming Barrage
        $ally = new Ally("MYALLY-" . SearchAlliesForUniqueID($currentTurnEffects[$i+2], $currentTurnEffects[$i+1]), $currentTurnEffects[$i+1]);
        $ally->DealDamage(2);
        break;
      case "4263394087"://Chirrut Imwe
        $ally = new Ally("MYALLY-" . SearchAlliesForUniqueID($currentTurnEffects[$i+2], $currentTurnEffects[$i+1]), $currentTurnEffects[$i+1]);
        $ally->DealDamage(2);
        break;
      case "6432884726"://Steadfast Battalion
        $ally = new Ally("MYALLY-" . SearchAlliesForUniqueID($currentTurnEffects[$i+2], $currentTurnEffects[$i+1]), $currentTurnEffects[$i+1]);
        $ally->DealDamage(2);
        break;
      default: break;
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
}

function IsCombatEffectActive($cardID)
{
  global $combatChain, $currentPlayer;
  if(count($combatChain) == 0) return;
  $attackID = $combatChain[0];
  $effectArr = explode("-", $cardID);
  $cardID = $effectArr[0];
  switch($cardID)
  {
    case "2587711125": return true;//Disarm
    case "2569134232": return true;//Jedha City
    case "1323728003": return true;//Electrostaff
    case "3809048641": return true;//Surprise Strike
    case "3038238423": return true;//Fleet Lieutenant
    case "8244682354": return true;//Jyn Erso
    case "8600121285": return true;//IG-88
    case "6954704048": return true;//Heroic Sacrifice
    default: return false;
  }
}

function IsCombatEffectPersistent($cardID)
{
  global $currentPlayer;
  $effectArr = explode(",", $cardID);
  switch($cardID) {
    case "2587711125": return true;//Disarm
    case "2569134232": return true;//Jedha City
    default:
      return false;
  }
}

function IsEffectPersistent($cardID)
{
  global $currentPlayer;
  $effectArr = explode(",", $cardID);
  switch($cardID) {
    case "7dedg616r0": return true;//Freydis, Master Tactician
    default:
      return false;
  }
}

function BeginEndPhaseEffects()
{
  global $currentTurnEffects, $mainPlayer, $EffectContext;
  for($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnPieces()) {
    $EffectContext = $currentTurnEffects[$i];
    if(IsEffectPersistent($EffectContext)) AddNextTurnEffect($EffectContext, $currentTurnEffects[$i+1]);
    switch($currentTurnEffects[$i]) {
      default:
        break;
    }
  }
}

function BeginEndPhaseEffectTriggers()
{
  global $currentTurnEffects, $mainPlayer;
  for($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnPieces()) {
    switch($currentTurnEffects[$i]) {
      case "blq7qXGvWH":
        DiscardHand($mainPlayer);
        WriteLog("Arcane Disposition discarded your hand");
        break;
      default: break;
    }
  }
}

function ActivateAbilityEffects()
{
  global $currentPlayer, $currentTurnEffects;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $currentPlayer) {
      switch($currentTurnEffects[$i]) {

        default:
          break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
  $currentTurnEffects = array_values($currentTurnEffects);
}

function CurrentEffectNameModifier($effectID, $effectParameter)
{
  $name = "";
  switch($effectID)
  {

    default: break;
  }
  return $name;
}

function CurrentEffectAllyEntersPlay($player, $index)
{
  global $currentTurnEffects;
  $allies = &GetAllies($player);
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $player) {
      switch($currentTurnEffects[$i]) {
        case "RfPP8h16Wv":
          if(SubtypeContains($allies[$index], "BEAST", $player) || SubtypeContains($allies[$index], "ANIMAL", $player))
          {
            ++$allies[$index+2];
            ++$allies[$index+7];
            $remove = 1;
          }
          break;
        default:
          break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
  $currentTurnEffects = array_values($currentTurnEffects);
}

function CurrentEffectLevelModifier($player)
{
  global $currentTurnEffects;
  $levelModifier = 0;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $player) {
      $arr = explode("-", $currentTurnEffects[$i]);
      $subparam = count($arr) > 1 ? $arr[1] : 0;
      switch($arr[0]) {
        case "MECS7RHRZ8": $levelModifier += 1; break;
        case "XLrHaYV9VB": $levelModifier += 1; break;
        case "9GWxrTMfBz": $levelModifier += 1; break;
        case "gvXQa57cxe": $levelModifier += 1; break;
        case "PLljzdiMmq": $levelModifier += 3; break;
        case "zpkcFs72Ah": $levelModifier += 1; break;
        case "aKgdkLSBza": $levelModifier += 1; break;//Wilderness Harpist
        case "dmfoA7jOjy": $levelModifier += 2; break;//Crystal of Empowerment
        case "Kc5Bktw0yK": $levelModifier += 2; break;//Empowering Harmony
        case "raG5r85ieO": $levelModifier += 1; break;//Piper's Lullaby
        case "j5iQQPd2m5": $levelModifier += $subparam; break;//Crystal of Argus
        case "ybdj1Db9jz": $levelModifier += 2; break;//Seed of Nature
        case "dBAdWMoPEz": $levelModifier += 1; break;//Erupting Rhapsody
        case "AnEPyfFfHj": $levelModifier += $subparam;//Power Overwhelming
        case "i0a5uhjxhk": $levelModifier += 1; break;//Blightroot (1)
        case "5joh300z2s": $levelModifier += 1; break;//Mana Root (2)
        case "wzh973fdt8": $levelModifier += 1;//Develop Mana
        default:
          break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
  $currentTurnEffects = array_values($currentTurnEffects);
  return $levelModifier;
}

function CurrentEffectGrantsAllStealth($player)
{
  global $currentTurnEffects;
  $stealthActive = false;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $player) {
      $arr = explode("-", $currentTurnEffects[$i]);
      $subparam = count($arr) > 1 ? $arr[1] : 0;
      switch($arr[0]) {
        case "8nbmykyXcw": $stealthActive = true; break;//Conceal
        case "DBJ4DuLABr": $stealthActive = true; break;//Shroud in Mist
        default:
          break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
  $currentTurnEffects = array_values($currentTurnEffects);
  return $stealthActive;
}


function CurrentEffectGrantsStealth($player, $uniqueID="")
{
  global $currentTurnEffects;
  $grantsStealth = false;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $player) {
      $arr = explode("-", $currentTurnEffects[$i]);
      $subparam = count($arr) > 1 ? $arr[1] : 0;
      switch($arr[0]) {
        case "ScGcOmkoQt": if($uniqueID != "" && $currentTurnEffects[$i + 2] == $uniqueID) $grantsStealth = true; break;//Smoke Bombs
        default:
          break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
  $currentTurnEffects = array_values($currentTurnEffects);
  return $grantsStealth;
}

function CurrentEffectGrantsTrueSight($player, $uniqueID="")
{
  global $currentTurnEffects;
  $grantsTrueSight = false;
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $player) {
      $arr = explode("-", $currentTurnEffects[$i]);
      $subparam = count($arr) > 1 ? $arr[1] : 0;
      switch($arr[0]) {
        case "iiZtKTulPg": if($uniqueID != "" && $currentTurnEffects[$i + 2] == $uniqueID) $grantsTrueSight = true; break;//Eye of Argus
        case "F1t18omUlx": if($uniqueID != "" && $currentTurnEffects[$i + 2] == $uniqueID) $grantsTrueSight = true; break;//Beastbond Paws
        default:
          break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
  $currentTurnEffects = array_values($currentTurnEffects);
  return $grantsTrueSight;
}

?>
