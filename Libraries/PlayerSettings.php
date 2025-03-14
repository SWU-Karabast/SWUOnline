<?php

$SET_AlwaysHoldPriority = 0;
$SET_TryUI2 = 1;
$SET_DarkMode = 2;
$SET_ManualMode = 3;

$SET_SkipARs = 4;
$SET_SkipDRs = 5;
$SET_PassDRStep = 6;

$SET_AutotargetArcane = 7; //Auto-target opponent with arcane damage
$SET_ColorblindMode = 8; //Colorblind mode settings
$SET_ShortcutAttackThreshold = 9; //Threshold to shortcut attacks
$SET_EnableDynamicScaling = 10; //Threshold to shortcut attacks
$SET_Mute = 11; //Mute sounds

$SET_Cardback = 12; //Card backs
$SET_IsPatron = 13; //Is Patron

$SET_MuteChat = 14; //Did this player mute chat

$SET_DisableStats = 15; //Did this player disable stats
$SET_CasterMode = 16; //Did this player enable caster mode
$SET_DisableAnimations = 17; //Did this player disable animations

//Menu settings
$SET_Format = 18; //What format did this player create a game for last?
$SET_Background = 19; //Background settings
$SET_FavoriteDeckIndex = 20; //What deck did this player play a game with last
$SET_GameVisibility = 21; //The visibility of the last game you created

$SET_StreamerMode = 22; //Did this player enable caster mode
$SET_Playmat = 23; //Playmat settings

function PlayerSettingsPieces()
{
  return 24;
}

function HoldPrioritySetting($player)
{
  return 4;
}

function UseNewUI($player)
{
  global $SET_TryUI2;
  $settings = GetSettings($player);
  return $settings[$SET_TryUI2] == 1;
}

function IsDarkMode($player)
{
  global $SET_DarkMode;
  $settings = GetSettings($player);
  return $settings[$SET_DarkMode] == 1 || $settings[$SET_DarkMode] == 3;
}

function IsPlainMode($player)
{
  global $SET_DarkMode;
  $settings = GetSettings($player);
  return $settings[$SET_DarkMode] == 2;
}

function IsDarkPlainMode($player)
{
  global $SET_DarkMode;
  $settings = GetSettings($player);
  return $settings[$SET_DarkMode] == 3;
}

function IsPatron($player)
{
  global $SET_IsPatron;
  $settings = GetSettings($player);
  if (count($settings) < $SET_IsPatron)
    return false;
  return $settings[$SET_IsPatron] == "1";
}

function IsLanguageJP($player)
{
  return false;
}

function GetBackground($player)
{
  global $SET_Background;
  $settings = GetSettings($player);
  switch ($settings[$SET_Background]) {
    case 0:
      return "Default";
    case 1:
      return "Death Star";
    case 2:
      return "Echo Base";
    case 3:
      return "AT-AT Sand";
    case 4:
      return "Overwhelming Barrage";
    case 5:
      return "The Darksaber";
    case 6:
      return "Space/Ground Battlefield 1";
    case 101:
      return "SOR Starfield";
    case 102:
      return "SHD Starfield";
    case 103:
      return "TWI Starfield";
    case 104:
      return "JTL Starfield";
    case 201:
      return "SOR Artwork";
    case 202:
      return "SHD Artwork";
    case 203:
      return "TWI Artwork";
    case 204:
      return "JTL Artwork";
  }
}

function BackgroundCode($name)
{
  switch ($name) {
    case "Default":
      return 0;
    case "Death Star":
      return 1;
    case "Echo Base":
      return 2;
    case "AT-AT Sand":
      return 3;
    case "Overwhelming Barrage":
      return 4;
    case "The Darksaber":
      return 5;
    case "Space/Ground Battlefield 1":
      return 6;
    case "SOR Starfield":
      return 101;
    case "SHD Starfield":
      return 102;
    case "TWI Starfield":
      return 103;
    case "JTL Starfield":
      return 104;
    case "SOR Artwork":
      return 201;
    case "SHD Artwork":
      return 202;
    case "TWI Artwork":
      return 203;
    case "JTL Artwork":
      return 204;
  }
}

function GetGameBgSrc($code)
{
  switch ($code) {
    case 0:
      return ["gamebg.jpg", true];
    case 1:
      return ["bg-deathstar.jpg", false];
    case 2:
      return ["bg-echobase.jpg", false];
    case 3:
      return ["bg-atat-sand.jpg", false];
    case 4:
      return ["bg-ob.png", false];
    case 5:
      return ["bg-darksaber.png", false];
    case 6:
      return ["bg-battlefields.jpg", false];
    case 101:
      return ["SWUKeyArt/SWH01_Starfield.png", false];
    case 102:
      return ["SWUKeyArt/SWH02_Starfield.jpg", false];
    case 103:
      return ["SWUKeyArt/SWH03_Starfield.jpg", false];
    case 104:
      return ["SWUKeyArt/SWH04_Starfield.jpg", false];
    case 201:
      return ["SWUKeyArt/SWH01_KeyArt.jpg", false];
    case 202:
      return ["SWUKeyArt/SWH02_KeyArt.jpg", false];
    case 203:
      return ["SWUKeyArt/SWH03_KeyArt.png", false];
    case 204:
      return ["SWUKeyArt/SWH04_KeyArt.jpg", false];
  }
}

function GetCardBack($player)
{
  global $SET_Cardback;
  $settings = GetSettings($player);
  switch ($settings[$SET_Cardback]) {
    case 1:
      return "CBBlack";
    case 2:
      return "CBKTOD";
    case 3:
      return "CBRebelResource";
    case 4:
      return "CBRebelResourceDark";
    case 5:
      return "CBGDP";
    case 6:
      return "CBL8NightGaming";
    case 7:
      return "Mobyus1Simple";
    case 8:
      return "Mobyus1Titled";
    case 9:
      return "OutmaneuverPod";
    case 10:
      return "BNCardBack";
    case 11:
      return "PadawanUnlimited";
    case 12:
      return "RVA_SWU";
    case 13:
      return "BBCardBack";
    case 14:
      return "CBForceFam";
    case 15:
      return "holocron_card_club";
    case 16:
      return "maclunky_gaming";
    case 17:
      return "cantina_crew";
    case 18:
      return "Rajeux_TCG";
    case 19:
      return "under_the_twin_suns";
    case 20:
      return "too_many_hans";
    case 21:
      return "porg_depot";
    case 22:
      return "darth_players";
    case 23:
      return "MainedoKaraSleeves";
    case 24:
      return "galactic-gonks";
    case 25:
      return "fallen-order";
    case 26:
      return "mythic-force";
    case 27:
      return "mog-tcg";
    case 28:
      return "CBSWCGR";
    case 29:
      return "SWU-VIC";
    case 30:
      return "GonkGang";
    case 31:
      return "galactic-shuffle";
    case 32:
      return "tropa-do-boba";
    case 33:
      return "CB_OuterRimCCG";
    case 34:
      return "Central";
    case 35:
      return "CB_Enigma";
    case 36:
      return "PrairiePirates";
    case 37:
      return "CCC";
    default:
      return "CardBack";
  }
}

function IsManualMode($player)
{
  global $SET_ManualMode;
  $settings = GetSettings($player);
  return $settings[$SET_ManualMode];
}

function ShouldSkipARs($player)
{
  global $SET_SkipARs;
  $settings = GetSettings($player);
  return $settings[$SET_SkipARs];
}

function ShouldSkipDRs($player)
{
  global $SET_SkipDRs, $SET_PassDRStep;
  $settings = GetSettings($player);
  $skip = $settings[$SET_SkipDRs] || $settings[$SET_PassDRStep];
  ChangeSetting($player, $SET_PassDRStep, 0);
  return $skip;
}

function ShouldAutotargetOpponent($player)
{
  global $SET_AutotargetArcane;
  $settings = GetSettings($player);
  return $settings[$SET_AutotargetArcane] == "1";
}

function IsColorblindMode($player)
{
  global $SET_ColorblindMode;
  $settings = GetSettings($player);
  if ($settings == null)
    return false;
  return $settings[$SET_ColorblindMode] == "1";
}

function IsScreenReaderMode($player)
{
  global $SET_ColorblindMode;
  if (!function_exists("GetSettings"))
    return false;
  $settings = GetSettings($player);
  if ($settings == null)
    return false;
  return $settings[$SET_ColorblindMode] == "1";
}

function ShortcutAttackThreshold($player)
{
  global $SET_ShortcutAttackThreshold;
  $settings = GetSettings($player);
  if (count($settings) < $SET_ShortcutAttackThreshold)
    return "0";
  return $settings[$SET_ShortcutAttackThreshold];
}

function IsDynamicScalingEnabled($player)
{
  if (!function_exists("GetSettings"))
    return false;
  global $SET_EnableDynamicScaling;
  $settings = GetSettings($player);
  if ($settings == null)
    return false;
  return $settings[$SET_EnableDynamicScaling] == "1";
}

function IsMuted($player)
{
  global $SET_Mute;
  $settings = GetSettings($player);
  if ($settings == null)
    return false;
  return $settings[$SET_Mute] == "1";
}

function IsChatMuted()
{
  global $SET_MuteChat;
  $p1Settings = GetSettings(1);
  $p2Settings = GetSettings(2);
  return $p1Settings[$SET_MuteChat] == "1" || $p2Settings[$SET_MuteChat] == "1";
}

function AreStatsDisabled($player)
{
  global $SET_DisableStats;
  $settings = GetSettings($player);
  if ($settings == null)
    return false;
  return $settings[$SET_DisableStats] == "1";
}

function AreAnimationsDisabled($player)
{
  global $SET_DisableAnimations;
  $settings = GetSettings($player);
  if ($settings == null)
    return false;
  return $settings[$SET_DisableAnimations] == "1";
}

function IsCasterMode()
{
  global $SET_CasterMode;
  $settings1 = GetSettings(1);
  $settings2 = GetSettings(2);
  if ($settings1 == null || $settings2 == null)
    return false;
  return $settings1[$SET_CasterMode] == "1" && $settings2[$SET_CasterMode] == "1";
}

function IsStreamerMode($player)
{
  global $SET_StreamerMode;
  $settings = GetSettings($player);
  if ($settings == null)
    return false;
  return $settings[$SET_StreamerMode] == "1";
}

function ParseSettingsStringValueToIdInt(string $value)
{
  //TODO NOTE: use array_flip to turn it the other way around (int -> string);
  $settingsToId = array(
    "HoldPrioritySetting" => 0,
    "TryReactUI" => 1,
    "DarkMode" => 2,
    "ManualMode" => 3,
    "SkipARWindow" => 4,
    "SkipDRWindow" => 5,
    "AutoTargetOpponent" => 7,
    "ColorblindMode" => 8,
    "ShortcutAttackThreshold" => 9,
    "MuteSound" => 11,
    "CardBack" => 12,
    "IsPatron" => 13,
    "MuteChat" => 14,
    "DisableStats" => 15,
    "IsCasterMode" => 16,
    "IsStreamerMode" => 22,
    "Playmat" => 23,
  );
  return $settingsToId[$value];
}

function ChangeSetting($player, $setting, $value, $playerId = "")
{
  global $SET_MuteChat, $SET_AlwaysHoldPriority, $layerPriority;
  if ($player != "") {
    $settings = &GetSettings($player);
    $settings[$setting] = $value;
    if ($setting == $SET_MuteChat) {
      if ($value == "1") {
        ClearLog(1);
        WriteLog("Chat disabled by player " . $player);
      } else {
        WriteLog("Chat enabled by player " . $player);
      }
    } else if ($setting == $SET_AlwaysHoldPriority) {
      $layerPriority[$player - 1] = "1";
    }
  }
  if ($playerId != "" && SaveSettingInDatabase($setting))
    SaveSetting($playerId, $setting, $value);
}

function GetSettingsUI($player)
{
  global $SET_AlwaysHoldPriority, $SET_DarkMode, $SET_ManualMode, $SET_SkipARs, $SET_SkipDRs, $SET_AutotargetArcane, $SET_ColorblindMode;
  global $SET_ShortcutAttackThreshold, $SET_EnableDynamicScaling, $SET_Mute, $SET_Cardback, $SET_Playmat, $SET_Background, $SET_MuteChat, $SET_DisableStats;
  global $SET_CasterMode, $SET_StreamerMode, $SET_DisableAnimations;
  $rv = "";
  $settings = GetSettings($player);
  //if ($settings[$SET_AutotargetArcane] == 0) $rv .= CreateCheckbox($SET_AutotargetArcane . "-1", "Arcane Manual Targetting", 26, true, "Manual Targetting");
  //else $rv .= CreateCheckbox($SET_AutotargetArcane . "-0", "Arcane Manual Targetting", 26, false, "Manual Targetting");
  //$rv .= "<BR>";
  //$rv .= "<h3>In-Game Theme:</h3>";
  //$rv .= CreateRadioButton($SET_DarkMode . "-0", "Normal Mode", 26, $SET_DarkMode . "-" . $settings[$SET_DarkMode], "Normal Mode");
  //$rv .= CreateRadioButton($SET_DarkMode . "-1", "Dark Mode", 26, $SET_DarkMode . "-" . $settings[$SET_DarkMode], "Dark Mode");
  //$rv .= "<BR>";
  //$rv .= CreateRadioButton($SET_DarkMode . "-2", "Plain Mode", 26, $SET_DarkMode . "-" . $settings[$SET_DarkMode], "Plain Mode");
  //$rv .= CreateRadioButton($SET_DarkMode . "-3", "Dark Plain Mode", 26, $SET_DarkMode . "-" . $settings[$SET_DarkMode], "Dark Plain Mode");

  $rv .= "<h3>Card Backs (public available: 25)</h3>";
  $submitLink = ProcessInputLink($player, 26, "select", "onchange", true);
  $rv .= "<select id='cardbacksSelect' class='settingsSelect'" . $submitLink . ">";
  $rv .= CreateSelectOption($SET_Cardback . "-" . 0, "", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 0, "Default", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 3, "Rebel Resource", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 4, "Rebel Resource Dark", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 5, "Golden Dice Podcast", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 6, "L8 Night Gaming", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 7, "Mobyus1 Simple", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 8, "Mobyus1 Titled", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 9, "Outmaneuver", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 10, "Bothan Network", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 11, "Padawan Unlimited", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 12, "RVA SWU", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 13, "Baddest Batch", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 15, "Holocron Card Hub", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 16, "Maclunky Gaming", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 17, "The Cantina Crew", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 18, "Rajeux TCG", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 19, "Under The Twin Suns", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 20, "Too Many Hans", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 21, "Porg Depot", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 22, "Darth Players", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 23, "Mainedalorians", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 24, "Galactic Gonks", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 25, "Fallen Order", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 26, "Mythic Force", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 27, "MoG TCG", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 28, "SWCGR", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 29, "SWU VIC", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 30, "GonkGang", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 31, "Galactic Shuffle", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 32, "Tropa do Boba", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 33, "Outer Rim CCG", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 34, "Central Spacers", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 35, "Enigma", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 36, "PrairiePirates", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= CreateSelectOption($SET_Cardback . "-" . 37, "Colorado Cantina Crew", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  $rv .= "</select>";
  $rv .= "<BR>";

  $stage = getenv('STAGE') ?: 'prod';
  $isDev = $stage === 'dev';
  $patreonCases = $isDev ? [PatreonCampaign::ForceFam] : PatreonCampaign::cases();
  $rv .= "<h3>Patreon Card Backs</h3>";
  $submitLink = ProcessInputLink($player, 26, "select", "onchange", true);
  $rv .= "<select id='cardbacksPatreonSelect' class='settingsSelect'" . $submitLink . ">";
  $rv .= CreateSelectOption($SET_Cardback . "-" . 0, "", $SET_Cardback . "-" . $settings[$SET_Cardback]);
  foreach ($patreonCases as $campaign) {
    if (isset($_SESSION[$campaign->SessionID()]) || (isset($_SESSION["useruid"]) && $campaign->IsTeamMember($_SESSION["useruid"]))) {
      $cardBacks = $campaign->CardBacks();
      $cardBacks = explode(",", $cardBacks);
      for ($i = 0; $i < count($cardBacks); ++$i) {
        $name = $campaign->CampaignName() . (count($cardBacks) > 1 ? " " . $i + 1 : "");
        $rv .= CreateSelectOption($SET_Cardback . "-" . $cardBacks[$i], $name, $SET_Cardback . "-" . $settings[$SET_Cardback]);
      }
    }
  }
  $rv .= "</select>";
  $rv .= "<BR>";

  $rv .= "<h3>Backgrounds (public available: 14)</h3>";
  $submitLink = ProcessInputLink($player, 26, "select", "onchange", true);
  $rv .= "<select id='backgroundSelect' class='settingsSelect'" . $submitLink . ">";
  $rv .= CreateSelectOption($SET_Background . "-" . 0, "Default", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 1, "Death Star", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 2, "Echo Base", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 3, "AT-AT Sand", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 4, "Overwhelming Barrage", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 5, "The Darksaber", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 6, "Space/Ground Battlefield 1", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 101, "SOR Starfield", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 102, "SHD Starfield", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 103, "TWI Starfield", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 104, "JTL Starfield", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 201, "SOR Artwork", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 202, "SHD Artwork", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 203, "TWI Artwork", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= CreateSelectOption($SET_Background . "-" . 204, "JTL Artwork", $SET_Background . "-" . $settings[$SET_Background]);
  $rv .= "</select>";
  $rv .= "<BR>";
  if ($settings[$SET_ManualMode] == 0)
    $rv .= CreateCheckbox($SET_ManualMode . "-1", "Manual Mode", 26, false, "Manual Mode");
  else
    $rv .= CreateCheckbox($SET_ManualMode . "-0", "Manual Mode", 26, true, "Manual Mode");
  $rv .= "<BR>";

  if ($settings[$SET_ColorblindMode] == 0)
    $rv .= CreateCheckbox($SET_ColorblindMode . "-1", "Accessibility Mode", 26, false, "Accessibility Mode");
  else
    $rv .= CreateCheckbox($SET_ColorblindMode . "-0", "Accessibility Mode", 26, true, "Accessibility Mode");
  $rv .= "<BR>";

  if ($settings[$SET_EnableDynamicScaling] == 0)
    $rv .= CreateCheckbox($SET_EnableDynamicScaling . "-1", "Dynamic Scaling (Under Dev)", 26, false, "Dynamic Scaling (Under Dev)", true);
  else
    $rv .= CreateCheckbox($SET_EnableDynamicScaling . "-0", "Dynamic Scaling (Under Dev)", 26, true, "Dynamic Scaling (Under Dev)", true);
  $rv .= "<BR>";

  if ($settings[$SET_Mute] == 0)
    $rv .= CreateCheckbox($SET_Mute . "-1", "Mute", 26, false, "Mute", true);
  else
    $rv .= CreateCheckbox($SET_Mute . "-0", "Unmute", 26, true, "Unmute", true);
  $rv .= "<BR>";

  if ($settings[$SET_MuteChat] == 0)
    $rv .= CreateCheckbox($SET_MuteChat . "-1", "Disable Chat", 26, false, "Disable Chat", true);
  else
    $rv .= CreateCheckbox($SET_MuteChat . "-0", "Disable Chat", 26, true, "Disable Chat", true);
  $rv .= "<BR>";

  if ($settings[$SET_DisableStats] == 0)
    $rv .= CreateCheckbox($SET_DisableStats . "-1", "Disable Stats", 26, false, "Disable Stats", true);
  else
    $rv .= CreateCheckbox($SET_DisableStats . "-0", "Disable Stats", 26, true, "Disable Stats", true);
  $rv .= "<BR>";

  if ($settings[$SET_DisableAnimations] == 0)
    $rv .= CreateCheckbox($SET_DisableAnimations . "-1", "Disable Animations", 26, false, "Disable Animations", true);
  else
    $rv .= CreateCheckbox($SET_DisableAnimations . "-0", "Disable Animations", 26, true, "Disable Animations", true);
  $rv .= "<BR>";

  if ($settings[$SET_CasterMode] == 0)
    $rv .= CreateCheckbox($SET_CasterMode . "-1", "Caster Mode", 26, false, "Caster Mode", true);
  else
    $rv .= CreateCheckbox($SET_CasterMode . "-0", "Caster Mode", 26, true, "Caster Mode", true);
  $rv .= "<BR>";

  if ($settings[$SET_StreamerMode] == 0)
    $rv .= CreateCheckbox($SET_StreamerMode . "-1", "Streamer Mode", 26, false, "Streamer Mode", true);
  else
    $rv .= CreateCheckbox($SET_StreamerMode . "-0", "Streamer Mode", 26, true, "Streamer Mode", true);
  $rv .= "<BR>";

  return $rv;
}

function SaveSettingInDatabase($setting)
{
  global $SET_DarkMode, $SET_ColorblindMode, $SET_Mute, $SET_Cardback, $SET_DisableStats;
  global $SET_Format, $SET_FavoriteDeckIndex, $SET_GameVisibility, $SET_AlwaysHoldPriority, $SET_ManualMode;
  global $SET_StreamerMode, $SET_AutotargetArcane, $SET_Playmat, $SET_Background, $SET_DisableAnimations;
  switch ($setting) {
    case $SET_DarkMode:
    case $SET_ColorblindMode:
    case $SET_Mute:
    case $SET_Cardback:
    case $SET_DisableStats:
    case $SET_Format:
    case $SET_FavoriteDeckIndex:
    case $SET_GameVisibility:
    case $SET_AlwaysHoldPriority:
    case $SET_ManualMode:
    case $SET_StreamerMode:
    case $SET_AutotargetArcane:
    case $SET_Playmat:
    case $SET_Background:
    case $SET_DisableAnimations:
      return true;
    default:
      return false;
  }
}

function FormatCode($format)
{
  switch ($format) {
    case "premierf":
      return 0;
    case "prstrict":
      return 1;
    case "previewf":
      return 2;
    case "sndcrawl":
      return 3;
    case "openform":
      return 4;
    case "padawanf":
      return 5;
    case "sealedfm":
      return 6;
    case "draftfmt":
      return 7;
    case "civilwar":
      return 8;
    case "clonewar":
      return 9;
    default:
      return -1;
  }
}

function IsTeamCardAdvantage($userID)
{
  switch ($userID) {
    case "JacobK":
    case "Pastry Boi":
    case "Brotworst":
    case "1nigoMontoya (Cody)":
    case "Motley":
    case "jimmyhl1329":
    case "Stilltzkin":
    case "krav":
    case "infamousb":
    case "FatFabJesus":
    case "MisterPNP":
      return true;
    default:
      break;
  }
  return false;
}

function IsTeamSecondCycle($userID)
{
  switch ($userID) {
    case "The4thAWOL":
    case "Beserk":
    case "Dudebroski":
    case "deathstalker182":
    case "TryHardYeti":
    case "Fledermausmann":
    case "Loganninty7":
    case "flamedog3":
    case "Swankypants":
    case "Blazing For Lethal?":
    case "Jeztus":
    case "gokkar":
    case "Kernalxklink":
    case "Kymo13":
      return true;
    default:
      break;
  }
  return false;
}
