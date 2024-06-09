<?php
include_once 'MenuBar.php';
include "HostFiles/Redirector.php";
include_once "Libraries/PlayerSettings.php";
include_once 'Assets/patreon-php-master/src/PatreonDictionary.php';

$gameName = $_GET["gameName"];
if (!IsGameNameValid($gameName)) {
  echo ("Invalid game name.");
  exit;
}
$playerID = $_GET["playerID"];
if ($playerID == "1") {
  echo ("Player 1 should not use JoinGame.php");
  exit;
}

$settingArray = [];
if (isset($_SESSION["userid"])) {
  $savedSettings = LoadSavedSettings($_SESSION["userid"]);
  for ($i = 0; $i < count($savedSettings); $i += 2) {
    $settingArray[$savedSettings[intval($i)]] = $savedSettings[intval($i) + 1];
  }
}

?>

<?php
include_once 'Header.php';
?>

<div class="core-wrapper">
<div class="flex-padder"></div>
<div class="flex-wrapper">
  <div class='game-invite container bg-black'>
    <h2>Join Game</h2>
    <?php
    echo ("<form action='" . $redirectPath . "/JoinGameInput.php'>");
    echo ("<input type='hidden' id='gameName' name='gameName' value='$gameName'>");
    echo ("<input type='hidden' id='playerID' name='playerID' value='$playerID'>");
    ?>

    <?php
    echo ("<form style='display:inline-block;' action='" . $redirectPath . "/CreateGame.php'>");

    $favoriteDecks = [];
    if (isset($_SESSION["userid"])) {
      $favoriteDecks = LoadFavoriteDecks($_SESSION["userid"]);
      if (count($favoriteDecks) > 0) {
        $selIndex = -1;
        if (isset($settingArray[$SET_FavoriteDeckIndex])) $selIndex = $settingArray[$SET_FavoriteDeckIndex];
        echo ("<label for='favoriteDecks'>Favorite Decks");
        echo ("<select name='favoriteDecks' id='favoriteDecks'>");
        for ($i = 0; $i < count($favoriteDecks); $i += 4) {
          echo ("<option value='" . $favoriteDecks[$i] . "'" . ($i == $selIndex ? " selected " : "") . ">" . $favoriteDecks[$i + 1] . "</option>");
        }
        echo ("</select></label>");
      }
    }
    /*
    if (count($favoriteDecks) == 0) {
      echo ("<div><label class='SelectDeckInput'>Starter Decks: </label>");
      echo ("<select name='decksToTry' id='decksToTry'>");
      
      echo ("</select></div>");
    }
    */

    ?>
    <label for="fabdb"><u><a style='color:lightblue;' href='https://www.swudb.com/' target='_blank'>SWUDB</a></u> Deck Link <span class="secondary">(use the url or 'Deck Link' button)</span></label>
    <input type="text" id="fabdb" name="fabdb">
    <?php
    if (isset($_SESSION["userid"])) {
      echo ("<span class='save-deck'>");
      echo ("<label for='favoriteDeck'><input title='Save deck to Favorites' class='inputFavoriteDeck' type='checkbox' id='favoriteDeck' name='favoriteDeck' />");
      echo ("Save Deck to Favorites</label>");
      echo ("</span>");
    }
    ?>
    <div style='text-align:center;'><input class="JoinGame_Button" type="submit" value="Join Game"></div>
    </form>
  </div>
  <div class="container bg-blue">
      <h3>Instructions</h3>
      <p>Choose a deck, then click 'Join Game' to be taken to the game lobby.</p>
      <p>Once in the game lobby, the player who win the dice roll choose if the go first. Then the host can start the game.</p>
      <p>Have Fun!</p>
  </div>
</div>
<div class="flex-padder"></div>
</div>

<?php
include_once 'Disclaimer.php'
?>
