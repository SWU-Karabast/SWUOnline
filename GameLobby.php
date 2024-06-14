<?php
ob_start();
include "WriteLog.php";
include "CardDictionary.php";
include "HostFiles/Redirector.php";
include "Libraries/UILibraries2.php";
include "Libraries/SHMOPLibraries.php";
include_once "Libraries/PlayerSettings.php";
include_once "Libraries/HTTPLibraries.php";
include_once "Assets/patreon-php-master/src/PatreonDictionary.php";
ob_end_clean();

session_start();

$authKey = "";
$gameName = $_GET["gameName"];
$playerID = $_GET["playerID"];
if ($playerID == 1 && isset($_SESSION["p1AuthKey"]))
  $authKey = $_SESSION["p1AuthKey"];
else if ($playerID == 2 && isset($_SESSION["p2AuthKey"]))
  $authKey = $_SESSION["p2AuthKey"];
else if (isset($_GET["authKey"]))
  $authKey = $_GET["authKey"];

session_write_close();

if (($playerID == 1 || $playerID == 2) && $authKey == "") {
  if (isset($_COOKIE["lastAuthKey"]))
    $authKey = $_COOKIE["lastAuthKey"];
}

if (!file_exists("./Games/" . $gameName . "/GameFile.txt")) {
  header("Location: " . $redirectPath . "/MainMenu.php"); //If the game file happened to get deleted from inactivity, redirect back to the main menu instead of erroring out
  exit;
}

ob_start();
include "MenuFiles/ParseGamefile.php";
ob_end_clean();

$targetAuth = ($playerID == 1 ? $p1Key : $p2Key);
if (!isset($authKey) || $authKey != $targetAuth) {
  echo ("Invalid Auth Key");
  exit;
}

$yourName = ($playerID == 1 ? $p1uid : $p2uid);
$theirName = ($playerID == 1 ? $p2uid : $p1uid);

if ($gameStatus == $MGS_GameStarted) {
  $authKey = ($playerID == 1 ? $p1Key : $p2Key);
  if (isset($gameUIPath))
    header("Location: " . $gameUIPath . "?gameName=$gameName&playerID=$playerID");
  else
    header("Location: " . $redirectPath . "/NextTurn4.php?gameName=$gameName&playerID=$playerID");
  exit;
}

$icon = "ready.png";

if ($gameStatus == $MGS_ChooseFirstPlayer)
  $icon = $playerID == $firstPlayerChooser ? "ready.png" : "notReady.png";
else if ($playerID == 1 && $gameStatus < $MGS_ReadyToStart)
  $icon = "notReady.png";
else if ($playerID == 2 && $gameStatus >= $MGS_ReadyToStart)
  $icon = "notReady.png";

$isMobile = IsMobile();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="content-type" content="text/html; charset=utf-8" >
  <title>Game Lobby</title>
  <link id="icon" rel="shortcut icon" type="image/png" href="./Images/<?= $icon ?>"/>
  <link rel="stylesheet" href="./css/karabast.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Teko:wght@700&display=swap" rel="stylesheet">
</head>



<body onload='OnLoadCallback(<?php echo (filemtime("./Games/" . $gameName . "/gamelog.txt")); ?>)'>
  <div class="lobby-container">
    <div id="cardDetail" style="display:none; position:absolute;"></div>
    <div class="lobby-header">
      <h1>Game Lobby</h1>
      <p class="leave-lobby"><a href='MainMenu.php'>Leave Lobby</a></p>
    </div>
    <div class="lobby-wrapper">
      <div class="game-lobby">
        <div id='mainPanel' style='text-align:center;'>
          <div class='game-set-up container bg-blue'>
            <h2>Set Up</h2>
            <div id="setup-content"></div>
            <div id='submitForm' style='width:100%; text-align: center; display: none;'>
              <form action='./SubmitSideboard.php'>
                <input type='hidden' id='gameName' name='gameName' value='<?= $gameName ?>'>
                <input type='hidden' id='playerID' name='playerID' value='<?= $playerID ?>'>
                <input type='hidden' id='playerCharacter' name='playerCharacter' value=''>
                <input type='hidden' id='playerDeck' name='playerDeck' value=''>
                <input type='hidden' id='authKey' name='authKey' value='<?= $authKey ?>'>
                <input class='GameLobby_Button' type='submit' value='<?= $playerID == 1 ? "Start" : "Ready" ?>'>
              </form>
            </div>
          </div>
        </div>
        <div class='chat-log container bg-black'>
          <h2>Chat</h2>
          <div id='gamelog' class="gamelog"></div>
          <div id='chatbox' class="chatbox">
            <div class="lobby-chat-input">
              <input class='GameLobby_Input' type='text' id='chatText' name='chatText' value='' autocomplete='off' onkeypress='ChatKey(event)'>
              <button class='GameLobby_Button' style='cursor:pointer;' onclick='SubmitChat()'>Chat</button>
            </div>
            
            
          </div>
        </div>
      </div>

      <div class="player-info container bg-black">
        
        <h2>Players</h2>
        <div id="my-info">
          <?php
          $contentCreator = ContentCreators::tryFrom(($playerID == 1 ? $p1ContentCreatorID : $p2ContentCreatorID));
          $nameColor = ($contentCreator != null ? $contentCreator->NameColor() : "");
          $displayName = "<span style='color:" . $nameColor . "'>" . ($yourName != "-" ? $yourName : "Player " . $playerID) . "</span>";
          $deckFile = "./Games/" . $gameName . "/p" . $playerID . "Deck.txt";
          $handler = fopen($deckFile, "r");

          echo ("<h3>$displayName</h3>");
          if ($handler) {
            $material = GetArray($handler);

            echo ("<div style='position:relative; display: inline-block;'>");
            $overlayURL = ($contentCreator != null ? $contentCreator->HeroOverlayURL($material[1]) : "");
            echo (Card($material[1], "CardImages", ($isMobile ? 100 : 250), 0, 1, 0, 0, 0, "", "", true));
            if ($overlayURL != "")
              echo ("<img title='Portrait' style='position:absolute; z-index:1001; top: 27px; left: 0px; cursor:pointer; height:" . ($isMobile ? 100 : 250) . "; width:100%;' src='" . $overlayURL . "' />");
            echo ("</div>");

            echo ("<div style='position:relative; display: inline-block;'>");
            $overlayURL = ($contentCreator != null ? $contentCreator->HeroOverlayURL($material[0]) : "");
            echo (Card($material[0], "CardImages", ($isMobile ? 100 : 250), 0, 1, 0, 0, 0, "", "", true));
            if ($overlayURL != "")
              echo ("<img title='Portrait' style='position:absolute; z-index:1001; top: 27px; left: 0px; cursor:pointer; height:" . ($isMobile ? 100 : 250) . "; width:" . ($isMobile ? 100 : 250) . ";' src='" . $overlayURL . "' />");
            echo ("</div>");

            $deck = GetArray($handler);
            $deckSB = GetArray($handler);

            fclose($handler);
          }
          ?>
        </div>
∆
        <div id="their-info">
        </div>
      </div>

      <div class="deck-info container bg-black">
        <div id="deckTab" class="deck-header">
          <?php if (isset($deck)): ?>
            <h2 class='deck-title'>Your Deck</h2>
            <h2 class='deck-count'><span id='mbCount'><?= count($deck) ?></span>/<?= count($deck) + count($deckSB) ?></h2>
          <?php endif; ?>
        </div>

        <h4>Click Cards to Select/Unselect</h4>
        <div class="deck-display">
          <?php
          if (isset($deck)) {
            $cardSize = 110;
            $count = 0;
            sort($deck);
            for ($i = 0; $i < count($deck); ++$i) {
              $id = "DECK-" . $count;
              echo ("<span style='cursor:pointer; padding-bottom:5px; padding-left:3px;' onclick='CardClick(\"" . $id . "\")'>" . Card($deck[$i], "concat", $cardSize, 0, 1, 0, 0, 0, "", $id) . "</span>");
              ++$count;
            }
            for ($i = 0; $i < count($deckSB); ++$i) {
              $id = "DECK-" . $count;
              echo ("<span style='cursor:pointer; padding-bottom:5px; padding-left:3px;' onclick='CardClick(\"" . $id . "\")'>" . Card($deckSB[$i], "concat", $cardSize, 0, 1, 1, 0, 0, "", $id) . "</span>");
              ++$count;
            }
          }
          ?>
        </div>
      </div>

    </div>
    <div class="lobby-footer">
      <?php include_once 'Disclaimer.php'; ?>
    </div>
  </div>
  <audio id="playerJoinedAudio">
    <source src="./Assets/playerJoinedSound.mp3" type="audio/mpeg">
  </audio>
  <script src="./jsInclude.js"></script>
  <script>
    function copyText() {
      var gameLink = document.getElementById("gameLink");
      gameLink.select();
      gameLink.setSelectionRange(0, 99999);

      // Copy it to clipboard
      document.execCommand("copy");
    }
    function OnLoadCallback(lastUpdate) {
      <?php
      if ($playerID == "1" && $gameStatus == $MGS_ChooseFirstPlayer) {
        echo ("var audio = document.getElementById('playerJoinedAudio');");
        echo ("audio.play();");
      }
      ?>
      UpdateFormInputs();
      var log = document.getElementById('gamelog');
      if (log !== null) log.scrollTop = log.scrollHeight;
      CheckReloadNeeded(0);
    }

    function UpdateFormInputs() {
      var playerCharacter = document.getElementById("playerCharacter");
      if (!!playerCharacter) playerCharacter.value = GetCharacterCards();
      var playerDeck = document.getElementById("playerDeck");
      if (!!playerDeck) playerDeck.value = GetDeckCards();
    }

    function CardClick(id) {
      var idArr = id.split("-");
      if (idArr[0] == "DECK") {
        var overlay = document.getElementById(id + "-ovr");
        overlay.style.visibility = (overlay.style.visibility == "hidden" ? "visible" : "hidden");
        var mbCount = document.getElementById("mbCount");
        mbCount.innerText = parseInt(mbCount.innerText) + (overlay.style.visibility == "hidden" ? 1 : -1);
      }
      UpdateFormInputs();
    }

    function GetCharacterCards() {
      var types = ["WEAPONS", "OFFHAND", "QUIVER", "HEAD", "CHEST", "ARMS", "LEGS"];
      var returnValue = "<?php echo (isset($material) ? implode(",", $material) : ""); ?>";
      return returnValue;
    }

    function GetDeckCards() {
      var count = 0;
      var returnValue = "";
      var overlay = document.getElementById("DECK-" + count + "-ovr");
      while (!!overlay) {
          if (overlay.style.visibility == "hidden") {
          var imageSrc = document.getElementById("DECK-" + count + "-img").src;
          if (returnValue != "") returnValue += ",";
          var splitArr = imageSrc.split("/");
          returnValue += splitArr[splitArr.length-1].split(".")[0];
        }
        ++count;
        var overlay = document.getElementById("DECK-" + count + "-ovr");
      }
      return returnValue;
    }

    var audioPlayed = false;

    function CheckReloadNeeded(lastUpdate) {
      var xmlhttp = new XMLHttpRequest();
      xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          if (parseInt(this.responseText) != 0) {
            if (parseInt(this.responseText) == 1) location.reload();
            else {
              response = JSON.parse(this.responseText);
              document.getElementById("setup-content").innerHTML = response["setupContent"];
              document.getElementById("gamelog").innerHTML = response["logContent"];
              document.getElementById("their-info").innerHTML = response["theirInfo"];
              document.getElementById("submitForm").style.display = response["showSubmit"] ? "block" : "none";
              if (response["playerJoinAudio"] === true && !audioPlayed) {
                var audio = document.getElementById('playerJoinedAudio');
                audio.play();
                audioPlayed = true;
              } else if (response["playerJoinAudio"] === false && audioPlayed) {
                //reset audio if player left
                audioPlayed = false;
              }
              // document.getElementById("icon").href = "./Images/" + document.getElementById("iconHolder").innerText;
              var log = document.getElementById('gamelog');
              if (log !== null) log.scrollTop = log.scrollHeight;
              CheckReloadNeeded(parseInt(response["timestamp"]));
            }
          }
        }
      };
      xmlhttp.open("GET", "GetLobbyRefresh.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo ($playerID); ?>&lastUpdate=" + lastUpdate + "&authKey=<?php echo ($authKey); ?>", true);
      xmlhttp.send();
    }

    function SubmitFirstPlayer(action) {
       if (action == 1) action = "Go First";
      else action = "Go Second";
      var xmlhttp = new XMLHttpRequest();
      xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {}
      }
      var ajaxLink = "ChooseFirstPlayer.php?gameName=" + <?php echo ($gameName); ?>;
      ajaxLink += "&playerID=" + <?php echo ($playerID); ?>;
      ajaxLink += "&action=" + action;
      ajaxLink += <?php echo ("\"&authKey=" . $authKey . "\""); ?>;
        xmlhttp.open("GET", ajaxLink, true);
      xmlhttp.send();
    }
  </script>
</body>
</html>