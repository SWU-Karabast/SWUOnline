<?php


//Return 1 if the effect should be removed
function EffectHitEffect($cardID)
{
  global $combatChainState, $CCS_GoesWhereAfterLinkResolves, $defPlayer, $mainPlayer, $CCS_WeaponIndex, $combatChain, $CCS_DamageDealt;
  $attackID = $combatChain[0];
  switch($cardID) {
    case "mj3WSrghUH"://Poised Strike
      $char = &GetPlayerCharacter($mainPlayer);
      $char[1] = 2;
      break;
    case "XLbCBxla8K"://Thousand Refractions
      WakeUpChampion($mainPlayer);
      $combatChainState[$CCS_GoesWhereAfterLinkResolves] = "HAND";
      break;
    case "7t9m4muq2r"://Thieving Cut
      Draw($mainPlayer);
      break;
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
    case "dZ960Hnkzv": return SearchCount(SearchAllies($mainPlayer, "", "BEAST")) + SearchCount(SearchAllies($mainPlayer, "", "ANIMAL"));//Vertus, Gaia's Roar
    case "FCbKYZcbNq": return 2;
    case "4hbA9FT56L": return 1;
    case "At1UNRG7F0": return 4;
    case "CvvgJR4fNa": return 3;//Patient Rogue
    case "W1vZwOXfG3": return 2;//Embertail Squirrel
    case "rPpLwLPGaL": return 1;//Phalanx Captain
    case "k71PE3clOI": return 1;//Inspiring Call
    case "Huh1DljE0j": return 1;//Second Wind
    case "IAkuSSnzYB": return 1;//Banner Knight
    case "XMb6pSHFJg": return 2;//Embersong
    case "qyQLlDYBlr": return 1;//Ornamental Greatsword
    case "OofVX5hX8X": return 2;//Poisoned Coating Oil
    case "TJTeWcZnsQ": return 2;//Lorraine, Blademaster
    case "F1t18omUlx": return 1;//Beastbond Paws
    case "fMv7tIOZwLAttack": return 1;//Aqueous Enchanting
    case "GRkBQ1Uvir": return 2;//Ignited Strike
    case "qufoIF014c": return 2;//Gleaming Cut
    case "rxxwQT054x": return 2;//Command the Hunt
    case "vcZSHNHvKX": return IsAlly($cardID) ? 0 : 1;//Spirit Blade: Ghost Strike
    case "5kt3q2svd5": return $subparam;//Amorphous Strike
    case "659ytyj2s3": return $subparam;//Imperious Highlander
    case "i1f0ht2tsn": return 1;//Strategic Warfare
    case "huqj5bbae3": return 2;//Winds of Retribution
    case "r0zadf9q1w": return -2;//Conjure Downpour
    case "fzcyfrzrpl": return 1;//Heatwave Generator
    case "44vm5kt3q2"://Battlefield Spotter
      $mzID = AttackerMZID($mainPlayer);
      $ally = new Ally($mzID);
      return $ally->IsDistant() ? 1 : 0;
    case "lx6xwr42i6": return 3;//Windrider Invoker
    case "n0wpbhigka": return -3;//Wand of Frost
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
        case "6e7lRnczfL"://Horn of Beastcalling
          if(SubtypeContains($cardID, "BEAST")) { $costModifier -= 3; $remove = true; }
          break;
        case "EBWWwvSxr3"://Channeling Stone
          $costModifier -= 2;
          break;
        case "llQe0cg4xJ"://Orb of Choking Fumes
          $costModifier += 1;
          break;
        case "wPKxvzTmqq"://Ensnaring Fumes
          $costModifier -= 5;
          $remove = true;
          break;
        case "ir99sx6q3p"://Plea for Peace
          if(CardTypeContains($cardID, "AA", $currentPlayer) || ($from == "PLAY" && IsAlly($cardID, $currentPlayer))) $costModifier += 1;
          break;
        case "sbierp5k1v"://Steady Verse
          if(SubtypeContains($cardID, "HARMONY")) { $costModifier -= 1; $remove = true; }
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
      case "wzh973fdt8"://Develop Mana
        AddNextTurnEffect($currentTurnEffects[$i], $currentTurnEffects[$i + 1]);
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
    case "dZ960Hnkzv": return IsAlly($attackID);
    case "FCbKYZcbNq": return true;
    case "4hbA9FT56L": return IsAlly($attackID);
    case "At1UNRG7F0": return true;//Devastating Blow
    case "CvvgJR4fNa": return true;//Patient Rogue
    case "W1vZwOXfG3": return true;//Embertail Squirrel
    case "rPpLwLPGaL": return true;//Phalanx Captain
    case "k71PE3clOI": return IsAlly($attackID);//Inspiring Call
    case "Huh1DljE0j": return true;//Second Wind
    case "IAkuSSnzYB": return true;//Banner Knight
    case "XMb6pSHFJg": return true;//Embersong
    case "qyQLlDYBlr": return true;//Ornamental Greatsword
    case "OofVX5hX8X": return true;//Poisoned Coating Oil
    case "TJTeWcZnsQ": return !IsAlly($attackID);//Lorraine, Blademaster
    case "F1t18omUlx": return true;//Beastbond Paws
    case "fMv7tIOZwLAttack": return IsAlly($attackID);//Aqueous Enchanting
    case "GRkBQ1Uvir": return true;//Ignited Strike
    case "mj3WSrghUH": return true;//Poised Strike
    case "XLbCBxla8K": return true;//Thousand Refractions
    case "qufoIF014c": return true;//Gleaming Cut
    case "5qWWpkgQLl": return true;//Coup de Grace
    case "2Ch1Gp3jEL": return true;//Corhazi Lightblade
    case "rxxwQT054x": return true;//Command the Hunt
    case "vcZSHNHvKX": return true;//Spirit Blade: Ghost Strike
    case "7t9m4muq2r": return true;//Thieving Cut
    case "5kt3q2svd5": return true;//Amorphous Strike
    case "659ytyj2s3": return true;//Imperious Highlander
    case "i1f0ht2tsn": return IsAlly($attackID);//Strategic Warfare
    case "huqj5bbae3": return IsAlly($attackID);//Winds of Retribution
    case "r0zadf9q1w": return true;//Conjure Downpour
    case "fzcyfrzrpl": return true;//Heatwave Generator
    case "44vm5kt3q2": return true;//Battlefield Spotter
    case "lx6xwr42i6": return true;//Windrider Invoker
    case "n0wpbhigka": return true;//Wand of Frost
    default: return false;
  }
}

function IsCombatEffectPersistent($cardID)
{
  global $currentPlayer;
  $effectArr = explode(",", $cardID);
  $cardID = ShiyanaCharacter($effectArr[0]);
  switch($cardID) {
    case "dZ960Hnkzv": return true;
    case "4hbA9FT56L": return true;
    case "CvvgJR4fNa": return true;//Patient Rogue
    case "W1vZwOXfG3": return true;//Embertail Squirrel
    case "k71PE3clOI": return true;//Inspiring Call
    case "XMb6pSHFJg": return true;//Embersong
    case "qyQLlDYBlr": return true;//Ornamental Greatsword
    case "F1t18omUlx": return true;//Beastbond Paws
    case "fMv7tIOZwLAttack": return true;//Aqueous Enchanting
    case "rxxwQT054x": return true;//Command the Hunt
    case "vcZSHNHvKX": return true;//Spirit Blade: Ghost Strike
    case "i1f0ht2tsn": return true;//Strategic Warfare
    case "huqj5bbae3": return true;//Winds of Retribution
    case "r0zadf9q1w": return true;//Conjure Downpour
    case "n0wpbhigka": return true;//Wand of Frost
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
