<?php


//Return 1 if the effect should be removed
function EffectHitEffect($cardID)
{
  global $combatChainState, $CCS_GoesWhereAfterLinkResolves, $defPlayer, $mainPlayer, $CCS_WeaponIndex, $combatChain, $CCS_DamageDealt;
  $attackID = $combatChain[0];
  switch($cardID) {
    case "6954704048"://Heroic Sacrifice
      $ally = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
      if(!$ally->LostAbilities()) {
        WriteLog("Heroic Sacrifice defeated " . CardLink($ally->CardID(), $ally->CardID()));
        $ally->Destroy();
      }
      break;
    case "8734471238"://Stay On Target
      $ally = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
      if (GetAttackTarget() == "THEIRCHAR-0" && !$ally->LostAbilities()) {
        Draw($mainPlayer);
      }
      break;
    case "8988732248-1"://Rebel Assault
      AddCurrentTurnEffect("8988732248-2", $mainPlayer);
      break;
    case "0802973415"://Outflank
      AddCurrentTurnEffect("0802973415-1", $mainPlayer);
      break;
    case "5896817672-1"://Headhunting
    case "5896817672-2":
      AddCurrentTurnEffect("5896817672" . (str_ends_with($cardID, "-1") ? "-2" : "-3"), $mainPlayer);
      break;
    case "6514927936-1"://Leia Organa
      AddCurrentTurnEffectFromCombat("6514927936-2", $mainPlayer);
      break;
    case "5630404651-1"://MagnaGuard Wing Leader
      AddCurrentTurnEffectFromCombat("5630404651-2", $mainPlayer);
      break;
    case "4334684518-1"://Tandem Assault
      AddCurrentTurnEffectFromCombat("4334684518-2", $mainPlayer);
     break;
    case "1355075014"://Attack Run
      AddCurrentTurnEffect("1355075014-1", $mainPlayer);
      break;
    default:
      break;
  }
  return 0;
}

//Return true if there's a chained action
function FinalizeChainLinkEffects()
{
  global $mainPlayer, $currentTurnEffects;
  for($i=0; $i<count($currentTurnEffects); $i+=CurrentTurnPieces()) {
    $cardID = $currentTurnEffects[$i];
    switch($cardID) {
      case "8988732248-2"://Rebel Assault
        PrependDecisionQueue("REMOVECURRENTEFFECT", $mainPlayer, $currentTurnEffects[$i]);
        PrependDecisionQueue("SWAPTURN", $mainPlayer, "-");
        PrependDecisionQueue("ELSE", $mainPlayer, "-");
        PrependDecisionQueue("MZOP", $mainPlayer, "ATTACK", 1);
        PrependDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        PrependDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to attack with");
        PrependDecisionQueue("MZFILTER", $mainPlayer, "status=1");
        PrependDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Rebel");
        return true;
      case "0802973415-1"://Outflank
        PrependDecisionQueue("REMOVECURRENTEFFECT", $mainPlayer, $currentTurnEffects[$i]);
        PrependDecisionQueue("SWAPTURN", $mainPlayer, "-");
        PrependDecisionQueue("ELSE", $mainPlayer, "-");
        PrependDecisionQueue("MZOP", $mainPlayer, "ATTACK", 1);
        PrependDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to attack with");
        PrependDecisionQueue("MZFILTER", $mainPlayer, "status=1");
        PrependDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
        return true;
      case "5896817672-2"://Headhunting
      case "5896817672-3":
        global $CCS_CantAttackBase;
        PrependDecisionQueue("REMOVECURRENTEFFECT", $mainPlayer, $currentTurnEffects[$i]);
        PrependDecisionQueue("MZOP", $mainPlayer, "ATTACK", 1);
        PrependDecisionQueue("PASSPARAMETER", $mainPlayer, "{0}");
        PrependDecisionQueue("ADDLIMITEDCURRENTEFFECT", $mainPlayer, "5896817672", 1);
        PrependDecisionQueue("MZOP", $mainPlayer, "GETUNIQUEID", 1);
        PrependDecisionQueue("MZALLCARDTRAITORPASS", $mainPlayer, "Bounty Hunter", 1);
        PrependDecisionQueue("PASSPARAMETER", $mainPlayer, "{0}", 1);
        PrependDecisionQueue("SETCOMBATCHAINSTATE", $mainPlayer, $CCS_CantAttackBase, 1);
        PrependDecisionQueue("PASSPARAMETER", $mainPlayer, 1, 1);
        PrependDecisionQueue("SETDQVAR", $mainPlayer, "0");
        PrependDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to attack with");
        PrependDecisionQueue("MZFILTER", $mainPlayer, "status=1");
        PrependDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY");
        return true;
      case "5630404651-2"://MagnaGuard Wing Leader
        PrependDecisionQueue("SWAPTURN", $mainPlayer, "-");
        PrependDecisionQueue("ELSE", $mainPlayer, "-");
        PrependDecisionQueue("MZOP", $mainPlayer, "ATTACK", 1);
        PrependDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to attack with", 1);
        PrependDecisionQueue("MZFILTER", $mainPlayer, "status=1", 1);
        PrependDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Droid");
        return true;
      case "6514927936-2"://Leia Organa
        PrependDecisionQueue("SWAPTURN", $mainPlayer, "-");
        PrependDecisionQueue("ELSE", $mainPlayer, "-");
        PrependDecisionQueue("MZOP", $mainPlayer, "ATTACK", 1);
        PrependDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to attack with");
        PrependDecisionQueue("MZFILTER", $mainPlayer, "status=1");
        PrependDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Rebel");
        return true;
      case "87e8807695"://Leia Organa Leader Unit
        SearchCurrentTurnEffects("87e8807695", $mainPlayer, remove:true);
        PrependDecisionQueue("SWAPTURN", $mainPlayer, "-");
        PrependDecisionQueue("ELSE", $mainPlayer, "-");
        PrependDecisionQueue("MZOP", $mainPlayer, "ATTACK", 1);
        PrependDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to attack with");
        PrependDecisionQueue("MZFILTER", $mainPlayer, "status=1");
        PrependDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:trait=Rebel");
        return true;
      case "9560139036"://Ezra Bridger
        SearchCurrentTurnEffects("9560139036", $mainPlayer, remove:true);
        $options = "Play it;Discard it;Leave it on top of your deck";
        PrependDecisionQueue("MODAL", $mainPlayer, "EZRABRIDGER");
        PrependDecisionQueue("SHOWOPTIONS", $mainPlayer, "$cardID&$options");
        PrependDecisionQueue("CHOOSEOPTION", $mainPlayer, "$cardID&$options");
        PrependDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose one for <0>");
        PrependDecisionQueue("SETDQVAR", $mainPlayer, "0");
        PrependDecisionQueue("DECKCARDS", $mainPlayer, "0");
        return true;
      case "4334684518-2"://Tandem Assault
        PrependDecisionQueue("REMOVECURRENTEFFECT", $mainPlayer, "4334684518+2");
        PrependDecisionQueue("REMOVECURRENTEFFECT", $mainPlayer, $currentTurnEffects[$i]);
        PrependDecisionQueue("SWAPTURN", $mainPlayer, "-");
        PrependDecisionQueue("ELSE", $mainPlayer, "-");
        PrependDecisionQueue("MZOP", $mainPlayer, "ATTACK", 1);
        PrependDecisionQueue("ADDCURRENTEFFECT", $mainPlayer, "4334684518+2", 1);
        PrependDecisionQueue("MAYCHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a ground unit to attack with");
        PrependDecisionQueue("MZFILTER", $mainPlayer, "status=1");
        PrependDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Ground");
       return true;
      case "1355075014-1"://Attack Run
        PrependDecisionQueue("REMOVECURRENTEFFECT", $mainPlayer, $currentTurnEffects[$i]);
        PrependDecisionQueue("SWAPTURN", $mainPlayer, "-");
        PrependDecisionQueue("ELSE", $mainPlayer, "-");
        PrependDecisionQueue("MZOP", $mainPlayer, "ATTACK", 1);
        PrependDecisionQueue("CHOOSEMULTIZONE", $mainPlayer, "<-", 1);
        PrependDecisionQueue("SETDQCONTEXT", $mainPlayer, "Choose a unit to attack with");
        PrependDecisionQueue("MZFILTER", $mainPlayer, "status=1");
        PrependDecisionQueue("MULTIZONEINDICES", $mainPlayer, "MYALLY:arena=Space");
        return true;
      default: break;
    }
  }
  return false;
}

function EffectAttackModifier($cardID, $playerID="")
{
  global $mainPlayer, $defPlayer;
  $params = explode("_", $cardID);
  if(count($params) == 1) {
    $params = explode("-", $cardID);
  }
  $cardID = $params[0];
  if(count($params) > 1) $subparam = $params[1];
  switch($cardID)
  {
    case "8022262805": return 2;//Bold Resistance
    case "2587711125": return -4;//Disarm
    case "2569134232": return -4;//Jedha City
    case "1323728003": return -1;//Electrostaff
    case "2651321164": return 2;//Tactical Advantage
    case "1701265931": return 4;//Moment of Glory
    case "1900571801": return 2;//Overwhelming Barrage
    case "3809048641": return 3;//Surprise Strike
    case "8734471238": return 2;//Stay On Target
    case "3038238423": return 2;//Fleet Lieutenant
    case "3258646001": return 2;//Steadfast Senator
    case "9757839764": return 2;//Adelphi Patrol Wing
    case "3208391441": return -2;//Make an Opening
    case "4036958275": return -4;//Hello There
    case "5013214638": return -2;//Equalize
    case "9999079491": return -2;//Mystic Reflection
    case "6432884726": return 2;//Steadfast Battalion
    case "8244682354": return -1;//Jyn Erso
    case "8600121285": return 1;//IG-88
    case "0616724418": return 1;//Han Solo Leader JTL
    case "6954704048": return 2;//Heroic Sacrifice
    case "20f21b4948": return -1;//Jyn Erso
    case "9097690846": return 2;//Snowtrooper Lieutenant
    case "9210902604"://Precision Fire
      $attacker = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
      return TraitContains($attacker->CardID(), "Trooper", $mainPlayer) ? 2 : 0;
    case "6476609909"://Corner The Prey
      $attackTarget = GetAttackTarget();
      if(!IsAllyAttackTarget()) return 0;
      $ally = new Ally($attackTarget, $defPlayer);
      return $ally->Damage();
    case "5896817672": if(!$subparam) return 2; else return 0;//Headhunting
    case "2359136621": return $subparam;//Guarding The Way
    case "8297630396": return 1;//Shoot First
    case "5464125379": return -2;//Strafing Gunship
    case "5445166624": return -2;//Clone Dive Trooper
    case "8495694166": return -2;//Jedi Lightsaber
    case "3789633661": return 4;//Cunning
    case "1939951561": return $subparam;//Attack Pattern Delta
    case "1039176181": return 2;//Kalani
    case "8988732248": return 1;//Rebel Assault
    case "7922308768": return NumResources($mainPlayer) < NumResources($defPlayer) ? 2 : 0;//Valiant Assault Ship
    case "7109944284": return -1* $subparam;//Luke Skywalker unit
    case "1885628519": return 1;//Crosshair
    case "1480894253": return 2;//Kylo Ren
    case "2503039837": return IsAllyAttackTarget() ? 1 : 0;//Moff Gideon Leader
    case "4534554684": return 2;//Freetown Backup
    case "4721657243": return 3;//Kihraxz Heavy Fighter
    case "7171636330": return -4;//Chain Code Collector
    case "2526288781": return 1;//Bossk
    case "1312599620": return -3;//Smuggler's Starfighter
    case "8107876051": return -3;//Enfys Nest
    case "9334480612": return 1;//Boba Fett (Daimyo)
    case "6962053552": return 2;//Desperate Attack
    case "2995807621": return 4;//Trench Run
    case "4085341914": return 4;//Heroic Resolve
    case "1938453783": return 2;//Armed to the Teeth
    case "6263178121": return 2;//Kylo Ren (Killing the Past)
    case "8307804692": return -3;//Padme Admidala
    case "1167572655": return 1;//Planetary Invasion
    case "5610901450": return 2;//Heroes on Both Sides
    case "7578472075"://Let the Wookie Win
      $attacker = new Ally(AttackerMZID($mainPlayer), $mainPlayer);
      return TraitContains($attacker->CardID(), "Wookiee", $mainPlayer) ? 2 : 0;
    case "4663781580"://Swoop Down
      $attackTarget = GetAttackTarget();
      if(!IsAllyAttackTarget()) return 0;
      $ally = new Ally($attackTarget, $defPlayer);
      $modifier = $playerID == $defPlayer ? -2 : 2;
      return CardArenas($ally->CardID()) == "Ground" ? $modifier : 0;
    case "3399023235": return isset($subparam) && $subparam == "2" ? -2 : 0;//Fenn Rau
    case "8777351722": return IsAllyAttackTarget() ? 2 : 0;//Anakin Skywalker Leader
    case "4910017138": return 2;//Breaking In
    case "8929774056": return 1;//Asajj Ventress (undeployed)
    case "f8e0c65364": return 1;//Asajj Ventress (deployed)
    case "2155351882": return 1;//Ahsoka Tano
    case "6436543702": return -2;//Providence Destroyer
    case "7000286964": return -1;//Vulture Interceptor Wing
    case "0249398533": return 2;//Obedient Vanguard
    case "1686059165": return 2;//Wat Tambor
    case "12122bc0b1": return 2;//Wat Tambor
    case "2395430106": return 2;//Republic Tactical Officer
    case "3381931079": return -4;//Malevolence
    case "5333016146": return -1;//Rune Haako
    case "fb7af4616c": return 1;//General Grievous
    case "3556557330": return 3;//Asajj Ventress
    case "8418001763": return 2;//Huyang
    case "0216922902": return -5;//The Zillo Beast
    case "4916334670": return 1;//Encouraging Leadership
    case "3596811933": return -1;//Disruptive Burst
    case "7979348081": return 1;//Kraken
    case "6406254252": return 2;//Soulless One
    //Jump to Lightspeed
    case "6300552434": return -1;//Gold Leader
    case "7924461681": return 1;//Leia Organa
    case "4334684518+2": return 2;//Tandem Assault
    case "8656409691": return 1;//Rio Durant
    case "8943696478": return 2;//Admiral Holdo
    case "1397553238": return -1;//Desperate Commando
    case "0086781673": return -1;//Tam Ryvora
    case "3427170256": return 2;//Captain Phasma Unit
    case "6600603122": return 1;//Massassi Tactical Officer
    case "2922063712": return SearchCount(SearchAllies($defPlayer, damagedOnly:true));//Sith Trooper
    case "6413979593": return 2;//Punch it
    case "9763190770": return 1;//Major Vonreg
    case "d8a5bf1a15": return 1;//Major Vonreg pilot
    case "3782661648": return -5;//Out the Airlock
    case "9595202461": return 2;//Coordinated Front
    case "3858069945": return $subparam;//Power From Pain
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

function CurrentEffectCostModifiers($cardID, $from, $reportMode=false)
{
  global $currentTurnEffects, $currentPlayer, $CS_PlayUniqueID, $CS_PlayedAsUpgrade;
  $costModifier = 0;
  $uniqueEffectsActivated = [];
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    $effectCardID = $currentTurnEffects[$i];
    if($currentTurnEffects[$i + 1] == $currentPlayer) {
      if (str_starts_with($effectCardID, "TT") && strlen($effectCardID) > 2) {
        if ($effectCardID == "TTFREE" || $effectCardID == "TTOPFREE") { //Free
          $costModifier -= 99;
          $remove = true;
        } else { // TT* modifier for dynamic cost adjustments. E.g TT-2 reduces the card's cost by 2, TT+3 increases it by 3.
          $costModifier += (int) substr($effectCardID, 2);
          $remove = true;
        }
      } else {
        switch($effectCardID) {
          case "5707383130"://Bendu
            if($from != "PLAY" && !AspectContains($cardID, "Heroism", $currentPlayer) && !AspectContains($cardID, "Villainy", $currentPlayer)) {
              $costModifier -= 2;
              $remove = true;
            }
            break;
          case "4919000710"://Home One
            $costModifier -= 3;
            $remove = true;
            break;
          case "5351496853"://Gideon's Light Cruiser
            $costModifier -= 99;
            $remove = true;
            break;
          case "2756312994"://Alliance Dispatcher
            $costModifier -= 1;
            $remove = true;
            break;
          case "3509161777"://You're My Only Hope
            $costModifier -= PlayerRemainingHealth($currentPlayer) <= 5 ? 99 : 5;
            $remove = true;
            break;
          case "5494760041"://Galactic Ambition
            $costModifier -= 99;
            $remove = true;
            break;
          case "4113123883"://Unnatural Life
            if($from != "PLAY") {
              $costModifier -= 2;
              $remove = true;
            }
            break;
          case "7461173274"://They Hate That Ship
            if($from != "PLAY") {
              $costModifier -= 3;
              $remove = true;
            }
            break;
          case "3426168686"://Sneak Attack
            if($from != "PLAY") {
              $costModifier -= 3;
              $remove = true;
            }
            break;
          case "2397845395"://Strategic Acumen
            $costModifier -= 1;
            $remove = true;
            break;
          case "4895747419"://Consolidation Of Power
            $costModifier -= 99;
            $remove = true;
            break;
          case "5696041568"://Triple Dark Raid
            $costModifier -= 5;
            $remove = true;
            break;
          case "7870435409"://Bib Fortuna
            $costModifier -= 1;
            $remove = true;
            break;
          case "8506660490"://Darth Vader (Commanding the First Legion)
            $costModifier -= 99;
            $remove = true;
            break;
          case "8968669390"://U-Wing Reinforcement
            $costModifier -= 99;
            $remove = true;
            break;
          case "5440730550"://Lando Calrissian Leader
          case "040a3e81f3"://Lando Calrissian Leader Unit
            $costModifier -= 3;
            $remove = true;
            break;
          case "4643489029"://Palpatine's Return
            $costModifier -= TraitContains($cardID, "Force", $currentPlayer) ? 8 : 6;
            $remove = true;
            break;
          case "7270736993"://Unrefusable Offer
          case "4717189843"://A New Adventure
            $costModifier -= 99;
            $remove = true;
            break;
          case "9642863632"://Bounty Hunter's Quarry
            $costModifier -= 99;
            $remove = true;
            break;
          case "9226435975"://Han Solo Red
            $costModifier -= 1;
            $remove = true;
            break;
          case "0622803599-3"://Jabba the Hutt
            if($from != "PLAY" && DefinedTypesContains($cardID, "Unit", $currentPlayer)) {
              $costModifier -= 1;
              $remove = true;
            }
            break;
          case "f928681d36-3"://Jabba the Hutt Leader Unit
            if($from != "PLAY" && DefinedTypesContains($cardID, "Unit", $currentPlayer)) {
              $costModifier -= 2;
              $remove = true;
            }
            break;
          case "5576996578"://Endless Legions
            $costModifier -= 99;
            $remove = true;
            break;
          case "3399023235"://Fenn Rau
            $costModifier -= 2;
            $remove = true;
            break;
          case "7642980906"://Stolen Landspeeder
            $costModifier -= 99;
            $remove = false;
            break;
          case "6772128891"://Exploit Effect
            $costModifier -= 2;
            $remove = true;
            break;
          case "6849037019"://Now There Are Two of Them
            $costModifier -= 5;
            $remove = true;
            break;
          case "6570091935"://Tranquility
            if($from != "PLAY" && TraitContains($cardID, "Republic") && !in_array($effectCardID, $uniqueEffectsActivated)) {
              $costModifier -= 1;
              $remove = true;
              $uniqueEffectsActivated[] = $effectCardID;
            }
            break;
          case "0414253215"://General's Blade
            if ($from != "PLAY" && $from != "EQUIP" && DefinedTypesContains($cardID, "Unit", $currentPlayer)) {
              $costModifier -= 2;
              $remove = true;
            }
            break;
          //Jump to Lightspeed
          case "4030832630"://Admiral Piett
            $costModifier -= 1;
            $remove = true;
            break;
          case "5329736697"://Jump to Lightspeed card
            $discountedID = $currentTurnEffects[$i + 2];
            if($from != "PLAY" && $discountedID == $cardID) {
              $costModifier -= 99;
              $remove = true;
            }
            break;
          case "0011262813"://Wedge Antilles Leader
            $costModifier -= 1;
            $remove = true;
            break;
          case "6414788e89"://Wedge Antillies Leader unit
            if ($from != "PLAY" && $from != "EQUIP" && TraitContains($cardID, "Pilot", $currentPlayer)) {
              $costModifier -= 1;
              $remove = true;
            }
            break;
          case "0524529055-P"://Snap Wexly on Play
            if ($from != "PLAY" && $from != "EQUIP" && TraitContains($cardID, "Resistance", $currentPlayer)) {
              $costModifier -= 1;
              $remove = true;
            }
            break;
          case "0524529055-A"://Snap Wexly on Attack
            if ($from != "PLAY" && $from != "EQUIP" && TraitContains($cardID, "Resistance", $currentPlayer)) {
              $costModifier -= 1;
              $remove = true;
            }
            break;
          case "7312183744"://Moff Gideon
            if ($from != "PLAY" && $from != "EQUIP" && DefinedTypesContains($cardID, "Unit", $currentPlayer) && GetClassState($currentPlayer, $CS_PlayedAsUpgrade) == "0") {
              $costModifier += 1;
            }
            break;
          case "7138400365"://The Invisible Hand
            $costModifier -= 99;
            $remove = true;
            break;
          default: break;
        }
      }
      if($remove && !$reportMode) RemoveCurrentTurnEffect($i);
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
        case "8736422150"://Close the Shield Gate
          $remove = true;
          $damage = 0;
          break;
        default: break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
  return $damage;
}

//FAB
// function CurrentEffectDamagePrevention($player, $type, $damage, $source, $preventable, $uniqueID=-1)
// {
//   global $currentPlayer, $currentTurnEffects;
//   for($i = count($currentTurnEffects) - CurrentTurnEffectPieces(); $i >= 0 && $damage > 0; $i -= CurrentTurnEffectPieces()) {
//     if($uniqueID != -1 && $currentTurnEffects[$i + 2] != $uniqueID) continue;
//     $remove = false;
//     if($currentTurnEffects[$i + 1] == $player || $uniqueID != -1) {
//       $effects = explode("-", $currentTurnEffects[$i]);
//       switch($effects[0]) {
//         case "pv4n1n3gyg"://Cleric's Robe
//           if($preventable) $damage -= 1;
//           $remove = true;
//           break;
//         default: break;
//       }
//       if($remove) RemoveCurrentTurnEffect($i);
//     }
//   }
//   return $damage;
// }

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

function CurrentEffectStartRegroupPhaseAbilities() {
  // To function correctly, use uniqueID instead of MZIndex
  global $currentTurnEffects, $currentPlayer;

  // Current turn effects
  for ($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $params = explode("_", $currentTurnEffects[$i]);
    $cardID = $params[0];
    $player = $currentTurnEffects[$i+1];
    $uniqueID = $currentTurnEffects[$i+2];
    if (count($params) > 1) {
      $subparam = $params[1];
    }

    switch($cardID) {
      case "4113123883-2"://Unnatural Life
      case "3426168686-2"://Sneak Attack
      case "7270736993-2"://Unrefusable Offer
        $ally = new Ally($uniqueID, $player);
        if ($ally->Exists()) {
          $ally->Destroy(false);
        }
        break;
      case "1302133998"://Impropriety Among Thieves
      case "7732981122"://Sly Moore
      case "1626462639"://Change of Heart
        AddDecisionQueue("PASSPARAMETER", $player , $uniqueID);
        AddDecisionQueue("UIDOP", $player , "REVERTCONTROL");
        break;
      case "6117103324"://Jetpack
        DefeatUpgradeForUniqueID($uniqueID, $player);
        break;
      case "2522489681"://Zorii Bliss
        PummelHit($player);
        break;
      case "1910812527"://Final Showdown
        DealDamageAsync($player, 999999);
        break;
      //Jump to Lightspeed
      case "8105698374"://Commandeer
        AddDecisionQueue("PASSPARAMETER", $player , $uniqueID);
        AddDecisionQueue("UIDOP", $player , "BOUNCE");
        break;
      default: break;
    }
  }
}

function CurrentEffectEndRegroupPhaseAbilities() {
  // To function correctly, use uniqueID instead of MZIndex
  global $currentTurnEffects;

  for ($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $params = explode("_", $currentTurnEffects[$i]);
    $cardID = $params[0];
    $player = $currentTurnEffects[$i+1];
    $uniqueID = $currentTurnEffects[$i+2];
    $remove = false;
    if (count($params) > 1) {
      $subparam = $params[1];
    }

    switch($cardID) {
      case "8800836530"://No Good To Me Dead
        $ally = new Ally($uniqueID, $player);
        $ally->Exhaust();
        break;
      default: break;
    }

    if ($remove) RemoveCurrentTurnEffect($i);
  }
}

function CurrentEffectStartActionPhaseAbilities() {
  // To function correctly, use uniqueID instead of MZIndex
  global $currentTurnEffects;

  for ($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $params = explode("_", $currentTurnEffects[$i]);
    $cardID = $params[0];
    $player = $currentTurnEffects[$i+1];
    $uniqueID = $currentTurnEffects[$i+2];
    $remove = false;
    if (count($params) > 1) {
      $subparam = $params[1];
    }

    switch($cardID) {
      case "5954056864": case "5e90bd91b0"://Han Solo
        MZChooseAndDestroy($player, "MYRESOURCES", context:"Choose a resource to destroy");
        $remove = true;
        break;
      default: break;
    }

    if ($remove) RemoveCurrentTurnEffect($i);
  }
}

function CurrentEffectEndActionPhaseAbilities() {
  // To function correctly, use uniqueID instead of MZIndex
  global $currentTurnEffects;

  for ($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $params = explode("_", $currentTurnEffects[$i]);
    $cardID = $params[0];
    $player = $currentTurnEffects[$i+1];
    $uniqueID = $currentTurnEffects[$i+2];
    if (count($params) > 1) {
      $subparam = $params[1];
    }

    switch($cardID) {
      case "5696041568-2"://Triple Dark Raid
        AddDecisionQueue("PASSPARAMETER", $player , $uniqueID);
        AddDecisionQueue("UIDOP", $player , "BOUNCE");
        break;
      default: break;
    }
  }
}

function IsCombatEffectActive($cardID)
{
  global $combatChain;
  if(count($combatChain) == 0) return;
  $effectArr = explode("-", $cardID);
  $cardID = $effectArr[0];
  switch($cardID)
  {
    case "2587711125"://Disarm
    case "2569134232"://Jedha City
    case "1323728003"://Electrostaff
    case "3809048641"://Surprise Strike
    case "8734471238"://Stay On Target
    case "9757839764"://Adelphi Patrol Wing
    case "3038238423"://Fleet Lieutenant
    case "8244682354"://Jyn Erso
    case "8600121285"://IG-88
    case "6954704048"://Heroic Sacrifice
    case "20f21b4948"://Jyn Erso
    case "9097690846"://Snowtrooper Lieutenant
    case "9210902604"://Precision Fire
    case "8297630396"://Shoot First
    case "5667308555"://I Have You Now
    case "5464125379"://Strafing Gunship
    case "5445166624"://Clone Dive Trooper
    case "8495694166"://Jedi Lightsaber
    case "3789633661"://Cunning
    case "8988732248"://Rebel Assault
    case "7922308768"://Valiant Assault Ship
    case "6514927936"://Leia Organa
    case "5630404651"://MagnaGuard Wing Leader
    case "0802973415"://Outflank
    case "1480894253"://Kylo Ren
    case "2503039837"://Moff Gideon Leader
    case "4721657243"://Kihraxz Heavy Fighter
    case "7171636330"://Chain Code Collector
    case "8107876051"://Enfys Nest
    case "7578472075"://Let the Wookie Win
    case "4663781580"://Swoop Down
    case "2995807621"://Trench Run
    case "4085341914"://Heroic Resolve
    case "5896817672"://Headhunting
    case "6962053552"://Desperate attack
    case "3399023235"://Fenn Rau
    case "8777351722"://Anakin Skywalker Leader
    case "4910017138"://Breaking In
    case "8929774056"://Asajj Ventress TWI (undeployed)
    case "f8e0c65364"://Asajj Ventress TWI (deployed)
    case "2155351882"://Ahsoka Tano
    case "6669050232"://Grim Resolve
    case "2395430106"://Republic Tactical Officer
    case "6406254252"://Soulless One
    //Jump to Lightspeed
    case "0616724418"://Han Solo Leader
    case "6300552434"://Gold Leader
    case "7924461681"://Leia Organa
    case "4334684518"://Tandem Assault
    case "4334684518+2"://Tandem Assault
    case "8656409691"://Rio Durant
    case "6720065735"://Han Solo (Has His Moments)
    case "6228218834"://Tactival Heavy Bomber
    case "6600603122"://Massassi Tactical Officer
    case "6413979593"://Punch it
    case "1355075014"://Air Assault
      return true;
    default: return false;
  }
}

function IsCombatEffectPersistent($cardID)
{
  switch($cardID) {
    case "2587711125": return true;//Disarm
    case "2569134232": return true;//Jedha City
    case "3789633661": return true;//Cunning
    case "1480894253": return true;//Kylo Ren
    default: return false;
  }
}

// function IsEffectPersistent($cardID)//FAB
// {
//   global $currentPlayer;
//   $effectArr = explode(",", $cardID);
//   switch($cardID) {
//     case "7dedg616r0": return true;//Freydis (Master Tactician)
//     default:
//       return false;
//   }
// }

// function BeginEndPhaseEffects()//FAB
// {
//   global $currentTurnEffects, $mainPlayer, $EffectContext;
//   for($i = 0; $i < count($currentTurnEffects); $i += CurrentTurnPieces()) {
//     $EffectContext = $currentTurnEffects[$i];
//     if(IsEffectPersistent($EffectContext)) AddNextTurnEffect($EffectContext, $currentTurnEffects[$i+1]);
//     switch($currentTurnEffects[$i]) {
//       default:
//         break;
//     }
//   }
// }

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
  for($i = count($currentTurnEffects) - CurrentTurnPieces(); $i >= 0; $i -= CurrentTurnPieces()) {
    $remove = false;
    if($currentTurnEffects[$i + 1] == $player) {
      switch($currentTurnEffects[$i]) {
        case "7642980906"://Stolen Landspeeder
          $remove = true;
          $ally = new Ally("MYALLY-" . $index, $player);
          $ally->Attach("2007868442");//Experience token
          break;
        default:
          break;
      }
    }
    if($remove) RemoveCurrentTurnEffect($i);
  }
  $currentTurnEffects = array_values($currentTurnEffects);
}

?>
