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
  <h1 style='color:white;'>Karabast</h1>
  <h3 style='color:white;'>A fan-made Star Wars Unlimited TCG Simulator</h3>
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
<h5>Karabast is an open-source, fan-made platform. It is an educational tool only, meant to facilitate researching decks and strategies that is supportive of in-person play. As such, direct competition through the form of automated tournaments or rankings will not be added. This tool is free to use and is published non-commercially. Payment is not required to access any functionality.</h5>

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
/*
if (count($favoriteDecks) == 0) {
  echo ("<div><label class='SelectDeckInput'>" . $starterDecksText . ": </label>");
  echo ("<select name='decksToTry' id='decksToTry'>");

  echo ("</select></div>");
}
*/
echo ("<br>");

?>
<label for="fabdb">Deck Link (use the url or "Deck Link" button from <u><a style='color:lightblue;' href='https://www.swudb.com/' target='_blank'>SWUDB</a></u>):</label>
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
  echo ("<option value='cc' " . ($defaultFormat == 0 ? " selected" : "") . ">Premier</option>");
  echo ("<option value='compcc' " . ($defaultFormat == 0 ? " selected" : "") . ">Request-Undo Premier</option>");
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
} else {
  echo '&#10071;Log in to be able to create public games.';
}
?>
<label for="private">
  <input type="radio" class='SelectDeckInput' id="private" name="visibility" value="private" <?php if ($defaultVisibility == 0) echo 'checked="checked"'; ?> />
  Private</label>
  <!--
<label for="deckTestMode">
  <input class='SelectDeckInput' type="checkbox" id="deckTestMode" name="deckTestMode" value="deckTestMode">
  Single Player</label>
-->
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
    All set 1 cards now live!
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
