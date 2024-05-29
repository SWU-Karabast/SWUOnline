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

echo '<title>Game Lobby</title> <meta http-equiv="content-type" content="text/html; charset=utf-8" > <meta name="viewport" content="width=device-width, initial-scale=1.0">';
echo '<link id="icon" rel="shortcut icon" type="image/png" href="./Images/' . $icon . '"/>';

$isMobile = IsMobile();

?>

<head>
  <meta charset="utf-8">
  <title>Karabast</title>
  <link rel="stylesheet" href="./css/karabast.css">
  <!-- <link rel="stylesheet" href="./css/menuStyles2.css"> -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Teko:wght@700&display=swap" rel="stylesheet">
</head>

<script>
  function copyText() {
    var gameLink = document.getElementById("gameLink");
    gameLink.select();
    gameLink.setSelectionRange(0, 99999);

    // Copy it to clipboard
    document.execCommand("copy");
  }
</script>

<script src="./jsInclude.js"></script>

</head>

<body onload='OnLoadCallback(<?php echo (filemtime("./Games/" . $gameName . "/gamelog.txt")); ?>)'>

  <audio id="playerJoinedAudio">
    <source src="./Assets/playerJoinedSound.mp3" type="audio/mpeg">
  </audio>

  <div id="cardDetail" style="display:none; position:absolute;"></div>

<div class="lobby-header">
  <h1>Game Lobby</h1>
  <a href='MainMenu.php'>Leave Lobby</a>
</div>

<div class="lobby-wrapper">
<?php
  if ($isMobile)
    echo '<div class="game-lobby">';
  else
    echo '<div class="game-lobby">';
  ?>

  <h2>Set Up</h2>
  <?php

  echo ("<div id='submitForm' style='display:none; width:100%; text-align: center;'>");
  echo ("<form action='./SubmitSideboard.php'>");
  echo ("<input type='hidden' id='gameName' name='gameName' value='$gameName'>");
  echo ("<input type='hidden' id='playerID' name='playerID' value='$playerID'>");
  echo ("<input type='hidden' id='playerCharacter' name='playerCharacter' value=''>");
  echo ("<input type='hidden' id='playerDeck' name='playerDeck' value=''>");
  echo ("<input type='hidden' id='authKey' name='authKey' value='$authKey'>");
  echo ("<input class='GameLobby_Button' type='submit' value='" . ($playerID == 1 ? "Start" : "Ready") . "'>");
  echo ("</form>");
  echo ("</div>");

  echo ("<div id='mainPanel' style='text-align:center;'>");
  echo ("</div>");

  echo ("<div id='chatbox'>");
  //echo ("<div id='chatbox' style='position:relative; left:3%; width:97%; margin-top:4px;'>");
  echo ("<input class='GameLobby_Input' style='display:inline;' type='text' id='chatText' name='chatText' value='' autocomplete='off' onkeypress='ChatKey(event)'>");
  echo ("<button class='GameLobby_Button' style='display:inline; margin-left:3px; cursor:pointer;' onclick='SubmitChat()'>Chat</button>");
  echo ("<input type='hidden' id='gameName' value='" . $gameName . "'>");
  echo ("<input type='hidden' id='playerID' value='" . $playerID . "'>");
  echo ("</div>");

  echo ("<script>");
  echo ("var prevGameState = " . $gameStatus . ";");
  echo ("function reload() { setInterval(function(){loadGamestate();}, 500); }");

  echo ("</script>");

?>
</div>

<div class="player-info">
  
  <h2>Players</h2>

  <?php
  if ($isMobile)
    echo '<div id="your-info">';
  else
    echo '<div id="your-info">';
  $contentCreator = ContentCreators::tryFrom(($playerID == 1 ? $p1ContentCreatorID : $p2ContentCreatorID));
  $nameColor = ($contentCreator != null ? $contentCreator->NameColor() : "");
  $displayName = "<span style='color:" . $nameColor . "'>" . ($yourName != "-" ? $yourName : "Playerssssss " . $playerID) . "</span>";
  if ($isMobile)
    echo ("<h3>$displayName</h3>");
  else
    echo ("<h3>$displayName</h3>");

  $deckFile = "./Games/" . $gameName . "/p" . $playerID . "Deck.txt";
  $handler = fopen($deckFile, "r");
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
    <?php
    if ($isMobile)
      echo '<div id="opponent-info">';
    else
      echo '<div id="opponent-info">';
    $theirDisplayName = ($theirName != "-" ? $theirName : "Player " . ($playerID == 1 ? 2 : 1));
    if ($isMobile)
      echo ("<h3>$theirDisplayName</h3>");
    else
      echo ("<h3>$theirDisplayName</h3>");

    $otherHero = "CardBack";
    echo ("<div>");
    echo (Card($otherHero, "CardImages", ($isMobile ? 100 : 250), 0, 1, 0, 0, 0, "", "", true));
    echo (Card($otherHero, "CardImages", ($isMobile ? 100 : 250), 0, 1, 0, 0, 0, "", "", true));
    echo ("</div>");
    ?>
    </div>

  </div>

</div>

  <div class="deck-info">
    <div id="deckTab"
      style="cursor:pointer;"
      onclick="TabClick('DECK');">

      <?php
      if (isset($deck))
        echo ("<h2 class='deck-title'>Your Deck</h2>");
        echo ("<h2 class='deck-count'>" . count($deck) . "/" . (count($deck) + count($deckSB)) . "</h2>");
      ?>
    </div>

    <div class="deck-display">

      <h4>Click Cards to Select/Unselect</h4>

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

    <script>
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
        if (IsEquipType(idArr[0])) {
          var count = 0;
          var overlay = document.getElementById(idArr[0] + "-" + count + "-ovr");
          while (!!overlay) {
            if (count != idArr[1]) overlay.style.visibility = "visible";
            else overlay.style.visibility = (overlay.style.visibility == "visible" ? "hidden" : "visible");
            //overlay.style.visibility = (count != idArr[1] ? "visible" : "hidden");
            ++count;
            var overlay = document.getElementById(idArr[0] + "-" + count + "-ovr");
          }
        } else if (idArr[0] == "DECK") {
          var overlay = document.getElementById(id + "-ovr");
          overlay.style.visibility = (overlay.style.visibility == "hidden" ? "visible" : "hidden");
          var mbCount = document.getElementById("mbCount");
          mbCount.innerText = parseInt(mbCount.innerText) + (overlay.style.visibility == "hidden" ? 1 : -1);
        } else if (idArr[0] == "WEAPONS") {
          var overlay = document.getElementById(id + "-ovr");
          overlay.style.visibility = (overlay.style.visibility == "hidden" ? "visible" : "hidden");
        }
        UpdateFormInputs();
      }

      function IsEquipType(type) {
        switch (type) {
          case "HEAD":
            return true;
          case "CHEST":
            return true;
          case "ARMS":
            return true;
          case "LEGS":
            return true;
          case "OFFHAND":
            return true;
          case "QUIVER":
            return true;
          default:
            return false;
        }
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
                var responseArr = this.responseText.split("ENDTIMESTAMP");
                document.getElementById("mainPanel").innerHTML = responseArr[1];
                CheckReloadNeeded(parseInt(responseArr[0]));
                var playAudio = document.getElementById("playAudio");
                if (!!playAudio && playAudio.innerText == 1 && !audioPlayed) {
                  var audio = document.getElementById('playerJoinedAudio');
                  audio.play();
                  audioPlayed = true;
                }
                var otherHero = document.getElementById("otherHero");
                if (!!otherHero) document.getElementById("oppHero").innerHTML = otherHero.innerHTML;
                document.getElementById("icon").href = "./Images/" + document.getElementById("iconHolder").innerText;
                var log = document.getElementById('gamelog');
                if (log !== null) log.scrollTop = log.scrollHeight;
                document.getElementById("submitForm").style.display = document.getElementById("submitDisplay").innerHTML;
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

  <?php

  function DisplayEquipRow($equip, $equipSB, $name)
  {
    $cardSize = 110;
    $count = 0;
    if ($equip != "" || count($equipSB) > 0)
      echo ("<tr><td>");
    if ($equip != "") {
      $id = $name . "-" . $count;
      echo ("<div style='display:inline; width:" . $cardSize . ";' onclick='CardClick(\"" . $id . "\")'>");
      echo ("<span style='cursor:pointer; padding-bottom:5px; padding-left:3px;'>" . Card($equip, "concat", $cardSize, 0, 1, 0, 0, 0, "", $id) . "</span>");
      echo ("</div>");
      ++$count;
    }
    for ($i = 0; $i < count($equipSB); ++$i) {
      $id = $name . "-" . $count;
      echo ("<div style='display:inline; width:" . $cardSize . ";' onclick='CardClick(\"" . $id . "\")'>");
      echo ("<span style='cursor:pointer; padding-bottom:5px; padding-left:3px;'>" . Card($equipSB[$i], "concat", $cardSize, 0, 1, 1, 0, 0, "", $id) . "</span>");
      echo ("</div>");
      ++$count;
    }

    if ($equip != "" || count($equipSB) > 0)
      echo ("</td></tr>");
  }

  function DisplayWeaponRow($weapon1, $weapon2, $weaponSB, $name)
  {
    $cardSize = 110;
    $count = 0;
    if ($weapon1 != "" || $weapon2 != "" || count($weaponSB) > 0)
      echo ("<tr><td>");
    if ($weapon1 != "") {
      $id = $name . "-" . $count;
      echo ("<div style='display:inline; width:" . $cardSize . ";' onclick='CardClick(\"" . $id . "\")'>");
      echo ("<span style='cursor:pointer; padding-bottom:5px; padding-left:3px;'>" . Card($weapon1, "concat", $cardSize, 0, 1, 0, 0, 0, "", $id) . "</span>");
      echo ("</div>");
      ++$count;
    }
    if ($weapon2 != "") {
      if (HasReverseArt($weapon1) && $weapon2 == $weapon1) {
        $weapon2 = ReverseArt($weapon1);
      }
      $id = $name . "-" . $count;
      echo ("<div style='display:inline; width:" . $cardSize . ";' onclick='CardClick(\"" . $id . "\")'>");
      echo ("<span style='cursor:pointer; padding-bottom:5px; padding-left:3px;'>" . Card($weapon2, "concat", $cardSize, 0, 1, 0, 0, 0, "", $id) . "</span>");
      echo ("</div>");
      ++$count;
    }
    for ($i = 0; $i < count($weaponSB); ++$i) {
      if (isset($weaponSB[$i + 1])) {
        if (HasReverseArt($weaponSB[$i]) && $weaponSB[$i + 1] == $weaponSB[$i]) {
          $weaponSB[$i + 1] = ReverseArt($weaponSB[$i]);
        }
      }
      $id = $name . "-" . $count;
      echo ("<div style='display:inline; width:" . $cardSize . ";' onclick='CardClick(\"" . $id . "\")'>");
      echo ("<span style='cursor:pointer; padding-bottom:5px; padding-left:3px;'>" . Card($weaponSB[$i], "concat", $cardSize, 0, 1, 1, 0, 0, "", $id) . "</span>");
      echo ("</div>");
      ++$count;
    }

    if ($weapon1 != "" || $weapon2 != "" || count($weaponSB) > 0)
      echo ("</td></tr>");
  }

  function HasReverseArt($cardID)
  {
    switch ($cardID) {
      case "WTR078":
        return true;
      case "CRU004":
      case "CRU051":
      case "CRU079":
        return true;
      case "DYN069":
      case "DYN115":
        return true;
      case "OUT005":
      case "OUT007":
      case "OUT009":
        return true;
      default:
        return false;
        break;
    }
  }

  function ReverseArt($cardID)
  {
    switch ($cardID) {
      case "WTR078":
        return "CRU049";
      case "CRU004":
        return "CRU005";
      case "CRU051":
        return "CRU052";
      case "CRU079":
        return "CRU080";
      case "DYN069":
        return "DYN070";
      case "DYN115":
        return "DYN116";
      case "OUT005":
        return "OUT006";
      case "OUT007":
        return "OUT008";
      case "OUT009":
        return "OUT010";
      default:
        break;
    }
  }
  ?>

<?php
include_once 'Disclaimer.php';
?>