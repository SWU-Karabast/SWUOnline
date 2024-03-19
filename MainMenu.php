<?php

include_once 'MenuBar.php';
include "HostFiles/Redirector.php";
include_once "Libraries/PlayerSettings.php";
include_once 'Assets/patreon-php-master/src/PatreonDictionary.php';
include_once "APIKeys/APIKeys.php";

if (!empty($_SESSION['error'])) {
  $error = $_SESSION['error'];
  unset($_SESSION['error']);
  echo "<script>alert('" . $error . "')</script>";
}

$language = TryGet("language", 1);
$settingArray = [];
$defaultFormat = 0;
$defaultVisibility = (isset($_SESSION["useruid"]) ? 1 : 0);
if (isset($_SESSION["userid"])) {
  $savedSettings = LoadSavedSettings($_SESSION["userid"]);
  for ($i = 0; $i < count($savedSettings); $i += 2) {
    $settingArray[$savedSettings[intval($i)]] = $savedSettings[intval($i) + 1];
  }
  if (isset($_GET['language'])) {
    ChangeSetting("", $SET_Language, $language, $_SESSION["userid"]);
  } else if (isset($settingArray[$SET_Language])) $language = $settingArray[$SET_Language];
  if (isset($settingArray[$SET_Format])) $defaultFormat = $settingArray[$SET_Format];
  if (isset($settingArray[$SET_GameVisibility])) $defaultVisibility = $settingArray[$SET_GameVisibility];
}
$_SESSION['language'] = $language;
if (isset($_SESSION["isPatron"])) $isPatron = $_SESSION["isPatron"];
else $isPatron = false;

$createGameText = ($language == 1 ? "Create Game" : "ゲームを作る");
$languageText = ($language == 1 ? "Language" : "言語");
$createNewGameText = ($language == 1 ? "Create New Game" : "新しいゲームを作成する");
$starterDecksText = ($language == 1 ? "Starter Decks" : "おすすめデッキ");

$canSeeQueue = isset($_SESSION["useruid"]);

?>

<style>
</style>

<div style="text-align: center; padding-top: 45px;">
  <!--<div style="display: inline-block; width:400px; height:180px; background-size: contain; background-image: url('Images/TalisharLogo.webp');"></div>-->
  <h1 style='color:black;'>Clarent</h1>
  <h3 style='color:black;'>A fan-made Grand Archive TCG Simulator</h3>
</div>

<div class="ContentWindow" style='width:27%; left:20px; top:60px; bottom:30px; overflow-y:auto;'>
  <?php
  try {
    include "ServerChecker.php";
  } catch (\Exception $e) {
  }
  ?>
</div>

<?php

if (IsMobile()) echo ("<div class='ContentWindow' style='top:240px; left:32%; width:60%; bottom: 0px; overflow-y:visible'>");
else echo ("<div class='ContentWindow' style='top:225px; left:32%; width:36%; bottom: 30px; overflow-y:auto'>");

?>
<h5>Clarent is an open-source, fan-made platform. It's still a work in progress so let us know if you find any bugs :)</h5>

<h1><?php echo ($createNewGameText); ?></h1>

<?php
echo ("<form style='width:100%;display:inline-block;' action='" . $redirectPath . "/CreateGame.php'>");

$favoriteDecks = [];
if (isset($_SESSION["userid"])) {
  $favoriteDecks = LoadFavoriteDecks($_SESSION["userid"]);
  if (count($favoriteDecks) > 0) {
    $selIndex = -1;
    if (isset($settingArray[$SET_FavoriteDeckIndex])) $selIndex = $settingArray[$SET_FavoriteDeckIndex];
    echo ("<div class='SelectDeckInput'>Favorite Decks: ");
    echo ("<select style='height:34px; width:60%;' name='favoriteDecks' id='favoriteDecks'>");
    for ($i = 0; $i < count($favoriteDecks); $i += 4) {
      echo ("<option value='" . $i . "<fav>" . $favoriteDecks[$i] . "'" . ($i == $selIndex ? " selected " : "") . ">" . $favoriteDecks[$i + 1] . "</option>");
    }
    echo ("</select></div>");
  }
}
if (count($favoriteDecks) == 0) {
  echo ("<div><label class='SelectDeckInput'>" . $starterDecksText . ": </label>");
  echo ("<select name='decksToTry' id='decksToTry'>");
  echo ("<option value='1'>Lorraine Starter</option>");
  echo ("<option value='2'>Silvie Starter</option>");
  echo ("<option value='3'>Rai Starter</option>");
  echo ("</select></div>");
}
echo ("<br>");

?>
<label for="fabdb" class='SelectDeckInput'>Deck Link:</label>
<input type="text" id="fabdb" name="fabdb">
<?php
if (isset($_SESSION["userid"])) {
  echo ("<span style='display:inline;'>");
  echo ("<labelfor='favoriteDeck'><input class='inputFavoriteDeck' type='checkbox' id='favoriteDeck' name='favoriteDeck' />");
  echo ("Save deck to ❤️ favorites</label>");
  echo ("</span>");
}
echo ("<br>");
?>
<br>
<label for="gameDescription" class='SelectDeckInput'>Game Name:</label>
<input type="text" id="gameDescription" name="gameDescription" placeholder="Game #"><br><br>

<?php
echo ("<label for='format' class='SelectDeckInput'>Format: </label>");
echo ("<select name='format' id='format'>");
if ($canSeeQueue) {
  echo ("<option value='cc' " . ($defaultFormat == 0 ? " selected" : "") . ">Standard Constructed</option>");
}
echo ("<option value='livinglegendscc'" . ($defaultFormat == 4 ? " selected" : "") . ">Open Format</option>");
echo ("</select>");
?>
<BR>
<BR>

<?php
if ($canSeeQueue) {
  echo '<label for="public"><input class="SelectDeckInput" type="radio" id="public" name="visibility" value="public" ' . ($defaultVisibility == 1 ? 'checked="checked"' : "") . '>';
  echo (' Public</label>');
}
?>
<label for="private">
  <input type="radio" class='SelectDeckInput' id="private" name="visibility" value="private" <?php if ($defaultVisibility == 0) echo 'checked="checked"'; ?> />
  Private</label>
<label for="deckTestMode">
  <input class='SelectDeckInput' type="checkbox" id="deckTestMode" name="deckTestMode" value="deckTestMode">
  Single Player</label>
<div style=' text-align:center;'>
  <input type="submit" style="font-size:28px;" value="<?php echo ($createGameText); ?>">
</div>
<BR>
</form>

</div>
</div>

<div class="ContentWindow" style='right:20px; top:60px; bottom:30px; width:27%; <?php if (IsMobile()) echo ("display:none; "); ?>'>
  <h1>News</h1>
  <div style="position: relative;">
    <div style='vertical-align:middle; text-align:center;'>
      <h3>All cards supported except:</h3>
        Poisoned Dagger<BR>
        Bubble Mage<BR>
        Galatine, Sword of Sunlight<BR>
        Conduit of the Mad Mage<BR>
        Assassin's Ripper<BR>
        Spirit Blade: Ensoul<BR>
        Arthur, Young Heir<BR>
        Spirit Blade: Ascension<BR>
        Nia, Mistveiled Scout<BR>
        Freeze Stiff<BR>
        Intrepid Highwayman<BR>
        Flute of Taming<BR>
        Advent of the Stormcaller<BR>
        Intangible Geist<BR>
        Discordia, Harp of Malice<BR>
        Rai, Mana Weaver<BR>
        Beseech the Winds<BR>
        Avalon, Cursed Isle<BR>
        Carnwennan, Shrouded Edge<BR>
        Arcanist's Prism<BR>
        Galahad, Court Knight<BR>
        Harness Mana<BR>
        Silvie, Loved by All<BR>
        Zander, Corhazi's Chosen<BR>
        Bestial Frenzy<BR>
        Mist Resonance<BR>
        Triskit, Guidance Angel<BR>
        Varuck, Smoldering Spire<BR>
        Tristan, Grim Stalker<BR>
        Nimue, Cursed Touch<BR>
        Clarent, Sword of Peace<BR>
        Call the Pack<BR>
        Tome of Sacred Lightning<BR>
        Lorraine, Spirit Ruler<BR>
        Camelot, Impenetrable<BR>
        Endura, Scepter of Ignition<BR>
        Merlin, Memory Thief<BR>
        Nullifying Lantern<BR>
        Mordred, Flawless Blade<BR>
        Barrier Servant<BR>
        Gaia's Blessing<BR>
        Allen, Beast Beckoner<BR>
    </div>
  </div>
  <?php
  /*
  if (!$isPatron) {
    echo '<div>
      <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8442966023291783"
          crossorigin="anonymous"></script>
      <!-- MainMenu -->
      <ins class="adsbygoogle"
          style="display:block"
          data-ad-client="ca-pub-8442966023291783"
          data-ad-slot="5060625180"
          data-ad-format="auto"
          data-full-width-responsive="true"></ins>
      <script>
          (adsbygoogle = window.adsbygoogle || []).push({});
      </script>
    </div>';
  }
  */
  ?>
  <BR>
</div>
<script>
  function changeLanguage() {
    window.location.search = '?language=' + document.getElementById('languageSelect').value;
  }
</script>
<?php
include_once 'Disclaimer.php';
?>
