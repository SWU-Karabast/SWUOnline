<?php

include "Libraries/SHMOPLibraries.php";
include "HostFiles/Redirector.php";
include "CardDictionary.php";
include_once 'MenuBar.php';
include_once "./AccountFiles/AccountDatabaseAPI.php";
include_once './includes/functions.inc.php';
include_once './includes/dbh.inc.php';

define('ROOTPATH', __DIR__);

$path = ROOTPATH . "/Games";

$currentlyActiveGames = "";
$spectateLinks = "";
$blitzLinks = "";
$compBlitzLinks = "";
$ccLinks = "";
$compCCLinks = "";
$otherFormatsLinks = "";
$livingLegendsCCLinks = "";
// TODO: Have as a global variable.
$reactFE = "https://fe.talishar.net/game/play";

$isShadowBanned = false;
if (isset($_SESSION["isBanned"]))
  $isShadowBanned = (intval($_SESSION["isBanned"]) == 1 ? true : false);
else if (isset($_SESSION["useruid"]))
  $isShadowBanned = IsBanned($_SESSION["useruid"]);

$canSeeQueue = isset($_SESSION["useruid"]);

echo ("<div class='SpectatorContainer'>");
echo ("<h2>Public Games</h2>");
$gameInProgressCount = 0;
if ($handle = opendir($path)) {
  while (false !== ($folder = readdir($handle))) {
    if ('.' === $folder)
      continue;
    if ('..' === $folder)
      continue;
    $gameToken = $folder;
    $folder = $path . "/" . $folder . "/";
    $gs = $folder . "gamestate.txt";
    $currentTime = round(microtime(true) * 1000);
    if (file_exists($gs)) {
      $lastGamestateUpdate = intval(GetCachePiece($gameToken, 6));
      if ($currentTime - $lastGamestateUpdate < 30000) {
        $p1Hero = GetCachePiece($gameToken, 7);
        $p2Hero = GetCachePiece($gameToken, 8);
        //if($p2Hero != "") $gameInProgressCount += 1;
        $gameInProgressCount += 1;
        $visibility = GetCachePiece($gameToken, 9);


        if ($p2Hero != "" && $visibility == "1") {
          $spectateLinks .= <<<HTML
            <style>

            .hero-container {
              display: flex;
              align-items: center;
              column-gap: 10px
            }

            .hero-image {
              height: 50px;
              max-width: inherit;
            }

            </style>
            
            <form class='spectate-form' action='https://karabast.net/SWUOnline/NextTurn4.php?gameName=$gameToken&playerID=3'>
                <div class='spectate-container'>
                    <div class='hero-container'>
          HTML;

          if ($p1Hero == "") {
            $spectateLinks .= "<label for='joinGame' class='last-update-label'>Last Update " . intval(($currentTime - $lastGamestateUpdate) / 1000) . " seconds ago </label>";
          } else {
            $spectateLinks .= <<<HTML
              <img class='hero-image' src='./WebpImages2/$p1Hero.webp' alt='Player 1 Hero Image' />
              <span class='versus'>vs</span>
              <img class='hero-image' src='./WebpImages2/$p2Hero.webp' alt='Player 2 Hero Image' />
            HTML;
          }

          $spectateLinks .= <<<HTML
                    </div>
                    <input class='spectate-button' type='submit' id='joinGame' value='Spectate' />
                    <input type='hidden' name='gameName' value='$gameToken' />
                    <input type='hidden' name='playerID' value='3' />
                </div>
            </form>
          HTML;
        }
      } else if ($currentTime - $lastGamestateUpdate > 900000) //~1 hour
      {
        if ($autoDeleteGames) {
          deleteDirectory($folder);
          DeleteCache($gameToken);
        }
      }
      continue;
    }

    $gf = $folder . "GameFile.txt";
    $gameName = $gameToken;
    $lineCount = 0;
    $status = -1;
    if (file_exists($gf)) {
      $lastRefresh = intval(GetCachePiece($gameName, 2)); //Player 1 last connection time
      if ($lastRefresh != "" && $currentTime - $lastRefresh < 500) {
        include 'MenuFiles/ParseGamefile.php';
        $status = $gameStatus;
        UnlockGamefile();
      } else if ($lastRefresh == "" || $currentTime - $lastRefresh > 900000) //1 hour
      {
        deleteDirectory($folder);
        DeleteCache($gameToken);
      }
    }

    if ($status == 0 && $visibility == "public" && intval(GetCachePiece($gameName, 11)) < 3) {
      $p1Hero = GetCachePiece($gameName, 7);
      $formatName = "";
      if ($format == "commoner")
        $formatName = "Commoner ";
      else if ($format == "livinglegendscc")
        $formatName = "Open Format ";
      else if ($format == "clash")
        $formatName = "Clash";

      $link = "<form style='text-align:center;' action='" . $redirectPath . "/JoinGame.php'>";
      $link .= "<table class='game-item' cellspacing='0'><tr>";
      $link .= "<td class='game-name'>";
      if ($formatName != "") {
          $link .= "<p class='format-title'>" . $formatName . "</p>";
      }
      $description = ($gameDescription == "" ? "Game #" . $gameName : $gameDescription);
      $link .= "<p>" . $description . "</p></td>";
      $link .= "<td><input class='ServerChecker_Button' type='submit' id='joinGame' value='Join Game' /></td></tr>";
      $link .= "</table>";
      $link .= "<input type='hidden' name='gameName' value='$gameToken' />";
      $link .= "<input type='hidden' name='playerID' value='2' />";
      $link .= "</form>";

      if (!$isShadowBanned) {
        switch ($format) {
          case "blitz":
            $blitzLinks .= $link;
            break;
          case "compblitz":
            $compBlitzLinks .= $link;
            break;
          case "cc":
            $ccLinks .= $link;
            break;
          case "compcc":
            $compCCLinks .= $link;
            break;
          default:
            if ($format != "shadowblitz" && $format != "shadowcc")
              $otherFormatsLinks .= $link;
            break;
        }
      } else {
        if ($format == "shadowblitz")
          $blitzLinks .= $link;
        else if ($format == "shadowcc")
          $ccLinks .= $link;
      }
    }
  }
  closedir($handle);
}
if ($canSeeQueue) {
  echo ("<h3>Premier</h3>");
  echo ("<hr/>");
  echo ($ccLinks);
  echo ("<h3>Request-Undo Premier</h3>");
  echo ("<hr/>");
  echo ($compCCLinks);
  echo ("<h3>Other Formats</h3>");
  echo ("<hr/>");
  echo ($otherFormatsLinks);
}
if (!$canSeeQueue) {
  echo ("<p class='login-notice'>&#10071;<a href='./LoginPage.php'>Log In</a> to use matchmaking and see open matches</p>");
}
echo ("<div class='progress-title-wrapper'>");
echo ("<h3 class='progress-header'>Games In Progress</h3>");
echo ("<h3 class='progress-count'>$gameInProgressCount</h3>");
echo ("</div>");
echo ("<hr/>");
//if (!IsMobile()) {
echo ($spectateLinks);
//}
echo ("</div>");

function deleteDirectory($dir)
{
  if (!file_exists($dir)) {
    return true;
  }

  if (!is_dir($dir)) {
    $handler = fopen($dir, "w");
    fwrite($handler, "");
    fclose($handler);
    return unlink($dir);
  }

  foreach (scandir($dir) as $item) {
    if ($item == '.' || $item == '..') {
      continue;
    }
    if (!deleteDirectory($dir . "/" . $item)) {
      return false;
    }
  }
  return rmdir($dir);
}