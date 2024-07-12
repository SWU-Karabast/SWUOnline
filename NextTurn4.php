  <head>

    <style>
      @keyframes move {
        from {margin-top: 0;}
        to {margin-top: -50px;}
      }
    </style>

    <?php

    include 'Libraries/HTTPLibraries.php';

    //We should always have a player ID as a URL parameter
    $gameName = TryGet("gameName", "");
    if (!IsGameNameValid($gameName)) {
      echo ("Invalid game name.");
      exit;
    }
    $playerID = TryGet("playerID", 3);
    if (!is_numeric($playerID)) {
      echo ("Invalid player ID.");
      exit;
    }

    if (!file_exists("./Games/" . $gameName . "/")) {
      echo ("Game does not exist");
      exit;
    }

    session_start();
    if ($playerID == 1 && isset($_SESSION["p1AuthKey"])) $authKey = $_SESSION["p1AuthKey"];
    else if ($playerID == 2 && isset($_SESSION["p2AuthKey"])) $authKey = $_SESSION["p2AuthKey"];
    else $authKey = TryGet("authKey", "");
    session_write_close();

    if(($playerID == 1 || $playerID == 2) && $authKey == "")
    {
      if(isset($_COOKIE["lastAuthKey"])) $authKey = $_COOKIE["lastAuthKey"];
    }

    //First we need to parse the game state from the file
    include "Libraries/SHMOPLibraries.php";
    include "WriteLog.php";
    include "ParseGamestate.php";
    include "GameTerms.php";
    include "GameLogic.php";
    include "HostFiles/Redirector.php";
    include "Libraries/UILibraries2.php";
    include "Libraries/StatFunctions.php";
    include "Libraries/PlayerSettings.php";
    include "MenuFiles/ParseGamefile.php";
    include_once 'includes/functions.inc.php';
    include_once 'includes/dbh.inc.php';

    if ($currentPlayer == $playerID) {
      $icon = "ready.png";
      $readyText = "You are the player with priority.";
    } else {
      $icon = "notReady.png";
      $readyText = "The other player has priority.";
    }
    echo '<link id="icon" rel="shortcut icon" type="image/png" href="./Images/' . $icon . '"/>';

    $darkMode = IsDarkMode($playerID);

    if ($darkMode) $backgroundColor = "rgba(20,20,20,0.70)";
    else $backgroundColor = "rgba(255,255,255,0.70)";

    $borderColor = ($darkMode ? "#DDD" : "#1a1a1a");
    ?>


    <head>
      <meta charset="utf-8">
      <title>Karabast</title>
      <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="css/gamestyle062424.css">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Gemunu+Libre:wght@200..800&display=swap" rel="stylesheet">
    </head>

    <script>
      var IsDynamicScalingEnabled = <?php echo (IsDynamicScalingEnabled($playerID) ? "true" : "false"); ?>;
      var cardSize = IsDynamicScalingEnabled == 1 ? window.innerWidth / 13 : 96;
      //Note: 96 = Card Size

      function Hotkeys(event) {
        if (event.keyCode === 32) { if(document.getElementById("passConfirm").innerText == "false" || confirm("Do you want to skip arsenal?")) SubmitInput(99, ""); } //Space = pass
        if (event.keyCode === 117) SubmitInput(10000, ""); //U = undo
        if (event.keyCode === 104) SubmitInput(3, "&cardID=0"); //H = hero ability
        if (event.keyCode === 109) TogglePopup("menuPopup"); //M = open menu
        <?php
        if (count($myCharacter) > CharacterPieces() && CardType($myCharacter[CharacterPieces()]) == "W") echo ("if(event.keyCode === 108) SubmitInput(3, '&cardID=" . CharacterPieces() . "');"); //L = left weapon
        if (count($myCharacter) > (CharacterPieces() * 2) && CardType($myCharacter[CharacterPieces() * 2]) == "W") echo ("if(event.keyCode === 114) SubmitInput(3, '&cardID=" . (CharacterPieces() * 2) . "');"); //R = right weapon
        ?>
      }

      function ProcessInputLink(player, mode, input, event = 'onmousedown', fullRefresh = false) {
        return " " + event + "='SubmitInput(\"" + mode + "\", \"&buttonInput=" + input + "\", " + fullRefresh + ");'";
      }

      //Rotate is deprecated
      function Card(cardNumber, folder, maxHeight, action = 0, showHover = 0, overlay = 0, borderColor = 0, counters = 0, actionDataOverride = "", id = "", rotate = 0, lifeCounters = 0, defCounters = 0, atkCounters = 0, controller = 0, restriction = "", isBroken = 0, onChain = 0, isFrozen = 0, gem = 0, landscape = 0, epicActionUsed = 0) {
        if (folder == "crops") {
          cardNumber += "_cropped";
        }
        fileExt = ".png";
        folderPath = folder;

        var LanguageJP = <?php echo ((IsLanguageJP($playerID) ? "true" : "false")); ?>;
        LanguageJP = LanguageJP && TranslationExist('JP', cardNumber);

        if (cardNumber == "ENDSTEP" || cardNumber == "ENDTURN" || cardNumber == "RESUMETURN" || cardNumber == "PHANTASM" || cardNumber == "FINALIZECHAINLINK" || cardNumber == "DEFENDSTEP") {
          showHover = 0;
          borderColor = 0;
        } else if (folder == "concat" && LanguageJP) { // Japanese
          folderPath = "concat/JP";
          fileExt = ".webp";
        } else if (folder == "WebpImages2" && LanguageJP) { // Japanese
          folderPath = "WebpImages/JP";
          fileExt = ".webp";
        } else if (folder == "concat") {
          fileExt = ".webp";
        } else if (folder == "WebpImages2") {
          fileExt = ".webp";
        }
        var actionData = actionDataOverride != "" ? actionDataOverride : cardNumber;
        //Enforce 375x523 aspect ratio as exported (.71)
        margin = "margin:0px;";
        border = "";
        if (borderColor != -1) margin = borderColor > 0 ? "margin:0px;" : "margin:1px;";
        if (folder == "crops") margin = "0px;";

        var rv = "<a style='" + margin + " position:relative; display:inline-block;" + (action > 0 ? "cursor:pointer;" : "") + "'" + (showHover > 0 ? " onmouseover='ShowCardDetail(event, this)' onmouseout='HideCardDetail()'" : "") + (action > 0 ? " onclick='SubmitInput(\"" + action + "\", \"&cardID=" + actionData + "\");'" : "") + ">";

        if (borderColor > 0) {
          border = "border-radius:8px; border:2px solid " + BorderColorMap(borderColor) + ";";
        } else if (folder == "concat") {
          border = "border-radius:8px; border:1px solid transparent;";
        } else {
          border = "border: 1px solid transparent;";
        }

        var orientation = landscape == 1 ? "data-orientation='landscape'" : "";
        if(rotate == 1 || landscape == 1) {
          height = (maxHeight);
          width = (maxHeight * 1.29);
        }
        else if (folder == "crops") {
          height = maxHeight;
          width = (height * 1.29);
        } else if (folder == "concat") {
          height = maxHeight;
          width = maxHeight;
        } else {
          height = maxHeight;
          width = (maxHeight * .71);
        }

        <?php
        if (IsPatron(1)) echo ("if(controller == 1 && CardHasAltArt(cardNumber)) folderPath = 'PatreonImages/' + folderPath;");
        if (IsPatron(2)) echo ("if(controller == 2 && CardHasAltArt(cardNumber)) folderPath = 'PatreonImages/' + folderPath;");
        ?>

        rv += "<img " + (id != "" ? "id='" + id + "-img' " : "") + orientation + "style='" + border + " height:" + height + "; width:" + width + "px; position:relative;' src='./" + folderPath + "/" + cardNumber + fileExt + "' />";
        rv += "<div " + (id != "" ? "id='" + id + "-ovr' " : "") + "style='visibility:" + (overlay == 1 ? "visible" : "hidden") + "; width:calc(100% - 4px); height:calc(100% - 4px); top:2px; left:2px; border-radius:10px; position:absolute; background: rgba(0, 0, 0, 0.5); z-index: 1;'></div>";

        var darkMode = false;
        counterHeight = 28;
        imgCounterHeight = 42;
        //Attacker Label Style
        if (counters == "Attacker" || counters == "Arsenal") {
          rv += "<div style='margin: 0px; top: 80%; left: 50%; margin-right: -50%; border-radius: 7px; width: fit-content; text-align: center; line-height: 16px; height: 16px; padding: 5px; border: 3px solid " + PopupBorderColor(darkMode) + ";";
          rv += "transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); position:absolute; z-index: 10; background:" + BackgroundColor(darkMode) + "; font-size:20px; font-weight:800; color:" + PopupBorderColor(darkMode) + ";'>" + counters + "</div>";
        }
        //Equipments, Hero and default counters style
        else if (counters != 0) {
          var left = "72%";
          if (lifeCounters == 0 && defCounters == 0 && atkCounters == 0) {
            left = "50%";
          }
          rv += "<div style='margin: 0px; top: 50%; left:" + left + "; margin-right: -50%; border-radius: 50%; width:" + counterHeight + "px; height:" + counterHeight + "px; padding: 5px; border: 3px solid " + PopupBorderColor(darkMode) + "; text-align: center; line-height:" + imgCounterHeight / 1.5 + "px;";
          rv += "transform: translate(-50%, -50%); -ms-transform: translate(-50%, -50%); position:absolute; z-index: 10; background:" + BackgroundColor(darkMode) + "; font-family: Helvetica; font-size:" + (counterHeight - 2) + "px; font-weight:550; color:" + TextCounterColor(darkMode) + "; text-shadow: 2px 0 0 " + PopupBorderColor(darkMode) + ", 0 -2px 0 " + PopupBorderColor(darkMode) + ", 0 2px 0 " + PopupBorderColor(darkMode) + ", -2px 0 0 " + PopupBorderColor(darkMode) + ";'>" + counters + "</div>";
        }
        //-1 Defense & Endurance Counters style
        if (defCounters != 0 && isBroken != 1) {
          var left = "-42%";
          if (lifeCounters == 0 && counters == 0) {
            left = "0px";
          }
          rv += "<div style=' position:absolute; margin: auto; top: 0; left:" + left + "; right: 0; bottom: 0;width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; display: flex;justify-content: center; z-index: 5; text-align: center; vertical-align: middle; line-height:" + imgCounterHeight + "px;";
          rv += "font-size:" + (imgCounterHeight - 17) + "px; font-weight: 600;  color: #EEE; text-shadow: 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000, -2px 0 0 #000;'>" + defCounters + "<img style='position:absolute; top: -2px; width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; opacity: 0.9; z-index:-1;' src='./Images/Defense.png'></div>";
        }

        //Health Counters style
        if (lifeCounters != 0) {
          var left = "45%";
          if (defCounters == 0 && atkCounters == 0) {
            left = "0px";
          }
          rv += "<div style=' position:absolute; margin: auto; top: 0; left:" + left + "; right: 0; bottom: 0;width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; display: flex; justify-content: center; z-index: 5; text-align: center; vertical-align: middle; line-height:" + imgCounterHeight + "px;";
          rv += "font-size:" + (imgCounterHeight - 17) + "+px; font-weight: 600;  color: #EEE; text-shadow: 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000, -2px 0 0 #000;'>" + lifeCounters + "<img style='position:absolute; top: -2px; width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; opacity: 0.9; z-index:-1;' src='./Images/Life.png'></div>";
        }

        //Attack Counters style
        if (atkCounters != 0) {
          var left = "-45%";
          if (lifeCounters == 0 && counters == 0) {
            left = "0px";
          }
          rv += "<div style=' position:absolute; margin: auto; top: 0; left:" + left + "; right: 0; bottom: 0;width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; display: flex; justify-content: center; z-index: 5; text-align: center; vertical-align: middle; line-height:" + imgCounterHeight + "px;";
          rv += "font-size:" + (imgCounterHeight - 17) + "px; font-weight: 600;  color: #EEE; text-shadow: 2px 0 0 #000, 0 -2px 0 #000, 0 2px 0 #000, -2px 0 0 #000;'>" + atkCounters + "<img style='position:absolute; top: -2px; width:" + imgCounterHeight + "px; height:" + imgCounterHeight + "px; opacity: 0.9; z-index:-1;' src='./Images/AttackIcon.png'></div>";
        }

        if (restriction != "") {
          //$restrictionName = CardName($restriction);
          rv += "<img title='Restricted by: " + restriction + "' style='position:absolute; z-index:100; top:26px; left:26px;' src='./Images/restricted.png' />";
        }
        if (epicActionUsed == 1) rv += "<img title='Epic Action Used' style='position:absolute; z-index:100; border-radius:5px; top: -3px; right: -2px; height:26px; width:26px; filter:drop-shadow(1px 1px 1px rgba(0, 0, 0, 0.50));' src='./Images/ExhaustToken.png' />";
        rv += "</a>";

        if (gem != 0) {
          var playerID = <?php echo ($playerID); ?>;
           //Note: 96 = Card Size
          var cardWidth = 96;
          gemImg = (gem == 1 ? "hexagonRedGem.png" : "hexagonGrayGem.png");
          if (gem == 1) rv += "<img " + ProcessInputLink(playerID, 102, actionDataOverride) + " title='Effect Active' style='position:absolute; z-index:1001; bottom:3px; left:" + (cardWidth / 2 - 18) + "px; width:40px; height:40px; cursor:pointer;' src='./Images/" + gemImg + "' />";
          else if (gem == 2) rv += "<img " + ProcessInputLink(playerID, 102, actionDataOverride) + " title='Effect Inactive' style='position:absolute; z-index:1001; bottom:3px; left:" + (cardWidth / 2 - 18) + "px; width:40px; height:40px; cursor:pointer;' src='./Images/" + gemImg + "' />";
        }
        return rv;
      }

      function BackgroundColor(darkMode) {
        if (darkMode) return "rgba(74, 74, 74, 0.9)";
        else return "rgba(235, 235, 235, 0.9)";
      }

      function PopupBorderColor(darkMode) {
        if (darkMode) return "#DDD";
        else return "#1a1a1a";
      }

      function TextCounterColor(darkMode) {
        if (darkMode) return "#1a1a1a";
        else return "#EDEDED";
      }

      function CardHasAltArt(cardID) {
        switch (cardID) {
          case "WTR002": case "WTR150": case "WTR162": case "WTR224":
          case "MON155": case "MON215": case "MON216": case "MON217": case "MON219": case "MON220":
          case "ELE146":
          case "UPR006": case "UPR007": case "UPR008": case "UPR009": case "UPR010": case "UPR011": case "UPR012":
          case "UPR013": case "UPR014": case "UPR015": case "UPR016": case "UPR017": case "UPR042": case "UPR043":
          case "UPR169": case "UPR406": case "UPR407": case "UPR408": case "UPR409": case "UPR410": case "UPR411":
          case "UPR412": case "UPR413": case "UPR414": case "UPR415": case "UPR416": case "UPR417":
          case "DYN234":
            return true;
          default:
            return false;
        }
      }

      function TranslationExist(Language, cardID)
      {
        switch (Language) {
          case "JP": //Japanese
            switch (cardID) {
              case "CRU046":
              case "CRU050":
              case "CRU063":
              case "CRU069":
              case "CRU072":
              case "CRU073":
              case "CRU074":
              case "CRU186":
              case "CRU187":
              case "CRU194":
              case "WTR100":
              case "WTR191":
                return true;
              default:
                return false;
            }
            break;
          default:
            return false;
        }
      }

      function BorderColorMap(code) {
        code = parseInt(code);
        switch (code) {
          case 1:
            return "DeepSkyBlue";
          case 2:
            return "red";
          case 3:
            return "yellow";
          case 4:
            return "Gray";
          case 5:
            return "Tan";
          case 6:
            return "#00FF66";
          case 7:
            return "Orchid";
          default:
            return "Black";
        }
      }

      //Note: 96 = Card Size
      function PopulateZone(zone, size = 96, folder = "concat") {
        var zoneEl = document.getElementById(zone);
        var zoneData = zoneEl.innerHTML;
        if (zoneData == "") return;
        var zoneArr = zoneData.split("|");
        var newHTML = "";
        for (var i = 0; i < zoneArr.length; ++i) {
          cardArr = zoneArr[i].split(" ");
          var id = "-";
          var positionStyle = "relative";
          var type = cardArr[10];
          var substype = cardArr[11];
          if (type != "") {
            folder = "WebpImages2";
            if (zone == "myChar") {
              var charLeft = GetCharacterLeft(type, substype);
              var charBottom = GetCharacterBottom(type, substype);
              positionStyle = "fixed; left:" + charLeft + "; bottom:" + charBottom;
              var id = type == "W" ? "P<?php echo ($playerID); ?>BASE" : "P<?php echo ($playerID); ?>LEADER";
            } else if (zone == "theirChar") {
              var charLeft = GetCharacterLeft(type, substype);
              var charTop = GetCharacterTop(type, substype);
              positionStyle = "fixed; left:" + charLeft + "; top:" + charTop;
              var id = type == "W" ? "P<?php echo ($playerID == 1 ? 2 : 1); ?>BASE" : "P<?php echo ($playerID == 1 ? 2 : 1); ?>LEADER";
            }
          }
          if(id != "-") newHTML += "<span id='" + id + "' style='position:" + positionStyle + "; margin:1px;'>";
          else newHTML += "<span style='position:" + positionStyle + "; margin:1px;'>";
          if (type == "C") {
            folder = "WebpImages2";
            var mySoulCountEl = document.getElementById("mySoulCount");
            if (!!mySoulCountEl && zone == "myChar") {
              var fontColor = "#DDD";
              var borderColor = "#1a1a1a";
              newHTML += "<div onclick='TogglePopup(\"mySoulPopup\");' style='cursor:pointer; position:absolute; user-select: none;top:-23px; left: 17px; font-size:20px; font-weight: 600; color: " + fontColor + "; text-shadow: 2px 0 0 " + borderColor + ", 0 -2px 0 " + borderColor + ", 0 2px 0 " + borderColor + ", -2px 0 0 " + borderColor + ";'>Soul: " + mySoulCountEl.innerHTML + "</div>";
              mySoulCountEl.innerHTML = "";
            }
            var theirSoulCountEl = document.getElementById("theirSoulCount");
            if (!!theirSoulCountEl && zone == "theirChar") {
              var fontColor = "#DDD";
              var borderColor = "#1a1a1a";
              newHTML += "<div onclick='TogglePopup(\"theirSoulPopup\");' style='cursor:pointer; position:absolute; user-select: none; bottom:-25px; left: 17px; font-size:20px; font-weight: 600; color: " + fontColor + "; text-shadow: 2px 0 0 " + borderColor + ", 0 -2px 0 " + borderColor + ", 0 2px 0 " + borderColor + ", -2px 0 0 " + borderColor + ";'>Soul: " + theirSoulCountEl.innerHTML + "</div>";
              theirSoulCountEl.innerHTML = "";
            }
            <?php
            echo ("var p1uid = '" . ($p1uid == "-" ? "Player 1" : $p1uid) . "';");
            echo ("var p2uid = '" . ($p2uid == "-" ? "Player 2" : $p2uid) . "';");
            ?>

            // User Tags

            if (zone == "myChar") {
              var fontColor = "#DDD";
              var borderColor = "#1a1a1a";
              var backgroundColor = "#DDD";
              //var myName = document.getElementById("myUsername").innerHTML;
              newHTML += "<div style='cursor:default; margin: 0px; top: 85%; left: 50%; margin-right: -50%; border-radius: 5px 5px 0 0; text-align: center; line-height: 12px; height: 15px; padding: 5px; transform: translate(-50%, -50%); position: absolute; z-index: 10; background:black; font-size: 16px; font-weight: 500; color:white; user-select: none;'>" + <?php echo ($playerID == 1 ? "p1uid" : "p2uid"); ?> + "</div>";
            } else if (zone == "theirChar") {
              var fontColor = "#DDD";
              var borderColor = "#1a1a1a";
              var backgroundColor = "#DDD";
              //var theirName = document.getElementById("theirUsername").innerHTML;
              newHTML += "<div style='cursor:default; margin: 0px; top: 85%; left: 50%; margin-right: -50%; border-radius: 5px 5px 0 0; text-align: center; line-height: 12px; height: 15px; padding: 5px; transform: translate(-50%, -50%); position: absolute; z-index: 10; background:black; font-size: 16px; font-weight: 500; color:white; user-select: none;'>" + <?php echo ($playerID == 1 ? "p2uid" : "p1uid"); ?> + "</div>";
            }

          }
          var restriction = cardArr[12];
          if(typeof restriction != "string") restriction = "";
          restriction = restriction.replace(/_/g, ' ');
          newHTML += Card(cardArr[0], folder, size, cardArr[1], 1, cardArr[2], cardArr[3], cardArr[4], cardArr[5], "", cardArr[17], cardArr[6], cardArr[7], cardArr[8], cardArr[9], restriction, cardArr[13], cardArr[14], cardArr[15], cardArr[16], cardArr[18], cardArr[19]);
          newHTML += "</span>";
        }
        zoneEl.innerHTML = newHTML;
        zoneEl.style.display = "inline";
      }

      function GetCharacterLeft(cardType, cardSubType) {
        switch (cardType) {
          case "C": case "W":
            return "calc(50% - 183px)";
          default:
            break;
        }
        switch (cardSubType) {
          case "Head":
            return "95px";
          case "Chest":
            return "95px";
          case "Arms":
            return (cardSize + 105) + "px";
          case "Legs":
            return "95px";
          case "Off-Hand": case "Quiver":
            return "calc(50% + " + (cardSize / 2 + 10) + "px)";
        }
      }

      function GetCharacterBottom(cardType, cardSubType) {
        switch (cardType) {
          case "C":
            return "219px";
          case "W":
            return "329px";
          default:
            break;
        }
        switch (cardSubType) {
          case "Head":
            return (cardSize * 2 + 25) + "px";
          case "Chest":
            return (cardSize + 15) + "px";
          case "Arms":
            return (cardSize + 15) + "px";
          case "Legs":
            return "5px";
          case "Off-Hand": case "Quiver":
            return (cardSize * 2 + 25) + "px";
        }
      }

      function GetCharacterTop(cardType, cardSubType) {
        switch (cardType) {
          case "C":
            return "159px";
          case "W":
            return "269px";
          default:
            break;
        }
        switch (cardSubType) {
          case "Head":
            return "5px";
          case "Chest":
            return (cardSize + 15) + "px";
          case "Arms":
            return (cardSize + 15) + "px";
          case "Legs":
            return (cardSize * 2 + 25) + "px";
          case "Off-Hand": case "Quiver":
            return (cardSize * 2 + 25) + "px";
        }
      }

      function copyText() {
        var gameLink = document.getElementById("gameLink");
        gameLink.select();
        gameLink.setSelectionRange(0, 99999);

        // Copy it to clipboard
        document.execCommand("copy");
      }
    </script>

    <script src="./jsInclude.js"></script>

    <?php
    ?>
    <style>
      :root {
        <?php if (IsDarkMode($playerID)) echo ("color-scheme: dark;");
        else echo ("color-scheme: light;");

        ?>
      }

      div,
      span {
        font-family: "Barlow", sans-serif;
        color: white;
      }

      td {
        text-align: center;
      }

      .passButton {
        background-color: #292929;
        transition: 150ms ease-in-out;
        margin: 0 3px 0 7px;
      }

      .passButton:hover {
        background-color: #292929;
        -webkit-transform: scale(1.1);
        -ms-transform: scale(1.1);
        transform: scale(1.1);
      }

      .passButton:active {
        background-color: #292929;
        background-size: contain;
      }

      .passInactive {
        background-color: #292929;
        background-size: contain;
      }


      .claimButton {
        background: linear-gradient(180deg, #292929 0%, #292929 100%) padding-box,
                    linear-gradient(180deg, #454545 0%, #394B51 40%, #0080ad 100%) border-box;
        border-radius: 5px;
        border: 1px solid transparent;
        height:40px;
        padding: 8px 19px 10px;
        box-shadow: none;
        position: relative;
        bottom: -1px;
      }

      .breakChain {
        background: url("./Images/chainLinkRight.png") no-repeat;
        background-size: contain;
        transition: 150ms ease-in-out;
      }

      .breakChain:hover {
        background: url("./Images/chainLinkBreak.png") no-repeat;
        background-size: contain;
        cursor: pointer;
        -webkit-transform: scale(1.3);
        -ms-transform: scale(1.3);
        transform: scale(1.3);
      }

      .breakChain:focus {
        outline: none;
      }

      .chainSummary {
        cursor: pointer;
        transition: 150ms ease-in-out;
      }

      .chainSummary:hover {
        -webkit-transform: scale(1.4);
        -ms-transform: scale(1.4);
        transform: scale(1.4);
      }

      .chainSummary:focus {
        outline: none;
      }

      .MenuButtons {
        cursor: pointer;
        transition: 150ms ease-in-out;
        margin-right: 6px;
      }

      .MenuButtons:hover {
        -webkit-transform: scale(1.2);
        -ms-transform: scale(1.2);
        transform: scale(1.2);
      }

      .MenuButtons:focus {
        outline: none;
      }
    </style>

  </head>

  <body onkeypress='Hotkeys(event)' onload='OnLoadCallback(<?php echo (filemtime("./Games/" . $gameName . "/gamelog.txt")); ?>)'>

    <?php echo (CreatePopup("inactivityWarningPopup", [], 0, 0, "⚠️ Inactivity Warning ⚠️", 1, "", "", true, true, "Interact with the screen in the next 30 seconds or you could be kicked for inactivity.")); ?>
    <?php echo (CreatePopup("inactivePopup", [], 0, 0, "⚠️ You are Inactive ⚠️", 1, "", "", true, true, "You are inactive. Your opponent is able to claim victory. Interact with the screen to clear this.")); ?>

    <script>
      var IDLE_TIMEOUT = 30; //seconds
      var _idleSecondsCounter = 0;
      var _idleState = 0; //0 = not idle, 1 = idle warning, 2 = idle
      var _lastUpdate = 0;

      var activityFunction = function() {
        var oldIdleState = _idleState;
        _idleSecondsCounter = 0;
        _idleState = 0;
        var inactivityPopup = document.getElementById('inactivityWarningPopup');
        if (inactivityPopup) inactivityPopup.style.display = "none";
        var inactivePopup = document.getElementById('inactivePopup');
        if (inactivePopup) inactivePopup.style.display = "none";
        if (oldIdleState == 2) SubmitInput("100005", "");
      };

      document.onclick = activityFunction;

      document.onmousemove = activityFunction;

      document.onkeydown = activityFunction;

      window.setInterval(CheckIdleTime, 1000);

      function CheckIdleTime() {
        if (document.getElementById("iconHolder") == null || document.getElementById("iconHolder").innerText != "ready.png") return;
        _idleSecondsCounter++;
        if (_idleSecondsCounter >= IDLE_TIMEOUT) {
          if (_idleState == 0) {
            _idleState = 1;
            _idleSecondsCounter = 0;
            var inactivityPopup = document.getElementById('inactivityWarningPopup');
            if (inactivityPopup) inactivityPopup.style.display = "inline";
          } else if (_idleState == 1) {
            _idleState = 2;
            var inactivityPopup = document.getElementById('inactivityWarningPopup');
            if (inactivityPopup) inactivityPopup.style.display = "none";
            var inactivePopup = document.getElementById('inactivePopup');
            if (inactivePopup) inactivePopup.style.display = "inline";
            SubmitInput("100006", "");
          }
        }
      }
    </script>

    <audio id="yourTurnSound" src="./Assets/prioritySound.wav"></audio>

    <script>
      function reload() {
        CheckReloadNeeded(0);
      }

      function CheckReloadNeeded(lastUpdate) {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
            if (this.responseText == "NaN") {} //Do nothing, game is invalid
            else if (this.responseText.split("REMATCH")[0] == "1234") {
              location.replace('GameLobby.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo ($playerID); ?>&authKey=<?php echo ($authKey); ?>');
            } else if (parseInt(this.responseText) != 0) {
              HideCardDetail();
              var responseArr = this.responseText.split("GSDELIM");
              var update = parseInt(responseArr[0]);
              if (update != "NaN") CheckReloadNeeded(update);
              if(update < _lastUpdate) return;
              //An update was received, begin processing it
              _lastUpdate = update;

              //Handle events; they may need a delay in the card rendering
              var events = responseArr[1];
              if(<?php echo(AreAnimationsDisabled($playerID) ? 'false' : 'events != ""'); ?>) {
                var eventsArr = events.split("~");
                if(eventsArr.length > 0) {
                  var popup = document.getElementById("CHOOSEMULTIZONE");
                  if(!popup) popup = document.getElementById("MAYCHOOSEMULTIZONE");
                  if(popup) popup.style.display = "none";
                  var timeoutAmount = 0;
                  for(var i=0; i<eventsArr.length; i+=2) {
                    var eventType = eventsArr[i];//DAMAGE
                    if(eventType == "DAMAGE") {
                      var eventArr = eventsArr[i+1].split("!");
                      //Now do the animation
                      if(eventArr[0] == "P1BASE" || eventArr[0] == "P2BASE") var element = document.getElementById(eventArr[0]);
                      else var element = document.getElementById("unique-" + eventArr[0]);
                      if(!!element) {
                        if(timeoutAmount < 500) timeoutAmount = 500;
                        element.innerHTML += "<div class='dmg-animation' style='position:absolute; text-align:center; font-size:36px; top: 0px; left:-2px; width:100%; height: calc(100% - 8px); padding: 0 2px; border-radius:12px; background-color:rgba(255,0,0,0.5); z-index:1000;'><div style='padding: 25px 0; width:100%; height:100%:'></div></div>";
                        element.innerHTML += "<div style='position:absolute; text-align:center; animation-name: move; animation-duration: 0.6s; font-size:34px; font-weight: 600; text-shadow: 1px 1px 0px rgba(0, 0, 0, 0.60); top:0px; left:0px; width:100%; height:100%; background-color:rgba(0,0,0,0); z-index:1000;'><div style='padding: 25px 0; width:100%; height:100%:'>-" + eventArr[1] + "</div></div>";
                      }
                    } else if(eventType == "RESTORE") {
                      var eventArr = eventsArr[i+1].split("!");
                      //Now do the animation
                      if(eventArr[0] == "P1BASE" || eventArr[0] == "P2BASE") var element = document.getElementById(eventArr[0]);
                      else var element = document.getElementById("unique-" + eventArr[0]);
                      if(!!element) {
                        if(timeoutAmount < 500) timeoutAmount = 500;
                        element.innerHTML += "<div class='dmg-animation' style='position:absolute; text-align:center; font-size:36px; top: 0px; left:-2px; width:100%; height: calc(100% - 8px); padding: 0 2px; border-radius:12px; background-color:rgba(95,167,219,0.5); z-index:1000;'><div style='padding: 25px 0; width:100%; height:100%:'></div></div>";
                        element.innerHTML += "<div style='position:absolute; text-align:center; animation-name: move; animation-duration: 0.6s; font-size:34px; font-weight: 600; text-shadow: 1px 1px 0px rgba(0, 0, 0, 0.60); top:0px; left:0px; width:100%; height:100%; background-color:rgba(0,0,0,0); z-index:1000;'><div style='padding: 25px 0; width:100%; height:100%:'>+" + eventArr[1] + "</div></div>";
                      }
                    } else if(eventType == "EXHAUST") {
                      var eventArr = eventsArr[i+1].split("!");
                      //Now do the animation
                      if(eventArr[0] == "P1BASE" || eventArr[0] == "P2BASE") var element = document.getElementById(eventArr[0]);
                      else var element = document.getElementById("unique-" + eventArr[0]);
                      const timing = {
                          duration: 60,
                          iterations: 1,
                        };
                        const exhaustAnimation = [
                        { transform: "rotate(0deg) scale(1)" },
                        { transform: "rotate(5deg) scale(1)" },
                      ];
                      if(!!element) {
                        if(timeoutAmount < 60) timeoutAmount = 60;
                        element.animate(exhaustAnimation,timing);
                        element.innerHTML += "<div style='position:absolute; text-align:center; font-size:36px; top: 0px; left:-2px; width:100%; height: calc(100% - 16px); padding: 0 2px; border-radius:12px; background-color:rgba(0,0,0,0.5);'><div style='width:100%; height:100%:'></div></div>";
                        element.className += "exhausted";
                      }
                    }
                  }
                  if(timeoutAmount > 0) setTimeout(RenderUpdate, timeoutAmount, responseArr[2]);
                  else RenderUpdate(responseArr[2]);
                }
              }
              else RenderUpdate(responseArr[2]);
            } else {
              CheckReloadNeeded(lastUpdate);
            }
          }
        };
        var dimensions = "&windowWidth=" + window.innerWidth + "&windowHeight=" + window.innerHeight;
        var lcpEl = document.getElementById("lastCurrentPlayer");
        var lastCurrentPlayer = "&lastCurrentPlayer=" + (!lcpEl ? "0" : lcpEl.innerHTML);
        if (lastUpdate == "NaN") window.location.replace("https://www.karabast.net/game/MainMenu.php");
        else xmlhttp.open("GET", "GetNextTurn2.php?gameName=<?php echo ($gameName); ?>&playerID=<?php echo ($playerID); ?>&lastUpdate=" + lastUpdate + lastCurrentPlayer + "&authKey=<?php echo ($authKey); ?>" + dimensions, true);
        xmlhttp.send();
      }

      function RenderUpdate(updatedHTML) {
        //Update the main div
        document.getElementById("mainDiv").innerHTML = updatedHTML;

        //Update the icon, game log, and play ready sound if needed
        var readyIcon = document.getElementById("iconHolder").innerText;
        document.getElementById("icon").href = "./Images/" + readyIcon;
        var log = document.getElementById('gamelog');
        if(log !== null) log.scrollTop = log.scrollHeight;
        if(readyIcon == "ready.png") {
          try {
            var audio = document.getElementById('yourTurnSound');
            <?php if (!IsMuted($playerID)) echo ("audio.play();");
             ?>
          } catch (e) {

          }
        }

        //Now begin populating the cards
        PopulateZone("myHand", cardSize);
        PopulateZone("theirHand", cardSize);
        PopulateZone("myChar", cardSize);
        PopulateZone("theirChar", cardSize);
        var sidebarWrapper = document.getElementById("sidebarWrapper");
        if(sidebarWrapper)
        {
          var sidebarWrapperWidth = sidebarWrapper.style.width;
          var chatbox = document.getElementById("chatbox");
          if(chatbox) chatbox.style.width = (parseInt(sidebarWrapperWidth)-10) + "px";
          var chatText = document.getElementById("chatText");
          if(chatText) chatText.style.width = (parseInt(sidebarWrapperWidth)-100) + "px";
        }
      }

      function chkSubmit(mode, count) {
        var input = "";
        input += "&gameName=" + document.getElementById("gameName").value;
        input += "&playerID=" + document.getElementById("playerID").value;
        input += "&chkCount=" + count;
        for (var i = 0; i < count; ++i) {
          var el = document.getElementById("chk" + i);
          if (el.checked) input += "&chk" + i + "=" + el.value;
        }
        SubmitInput(mode, input);
      }

      function textSubmit(mode) {
        var input = "";
        input += "&gameName=" + document.getElementById("gameName").value;
        input += "&playerID=" + document.getElementById("playerID").value;
        input += "&inputText=" + document.getElementById("inputText").value;
        SubmitInput(mode, input);
      }

      function suppressEventPropagation(e)
      {
        e.stopPropagation();
      }
    </script>

    <?php
    // Display hidden elements and Chat UI
    ?>
    <div id='popupContainer'></div>
    <div id="cardDetail" style="z-index:100000; display:none; position:fixed;"></div>
    <div id='mainDiv' style='position:fixed; z-index:20; left:0; top:0; width:100%; height:100%;'></div>
    <div id='chatbox' style='z-index:40; position:fixed; bottom:20px; right:18px; display:flex;'>
        <?php if ($playerID != 3 && !IsChatMuted()): ?>
            <input id='chatText'
                  style='background: black; color: white; font-size:16px; font-family:barlow; margin-left: 8px; height: 32px; border: 1px solid #454545; border-radius: 5px 0 0 5px;'
                  type='text'
                  name='chatText'
                  value=''
                  autocomplete='off'
                  onkeypress='ChatKey(event)'>
            <button style='border: 1px solid #454545; border-radius: 0 5px 5px 0; width:55px; height:32px; color: white; margin: 0 0 0 -1px; padding: 0 5px; font-size:16px; font-weight:600; box-shadow: none;'
                    onclick='SubmitChat()'>Chat
            </button>
            <button title='Disable Chat'
                    <?= ProcessInputLink($playerID, 26, $SET_MuteChat . "-1", fullRefresh:true); ?>
                    style='border: 1px solid #454545; color: #1a1a1a; padding: 0; box-shadow: none;'>
                <img style='height:16px; width:16px; float:left; margin: 7px;' src='./Images/disable.png' />
            </button>
        <?php else: ?>
            <button title='Re-enable Chat'
                    <?= ProcessInputLink($playerID, 26, $SET_MuteChat . "-0", fullRefresh:true); ?>
                    style='border: 1px solid #454545; width: 100%; padding: 0 0 4px 0; height: 32px; font: inherit; box-shadow: none;'>
                ⌨️ Re-enable Chat
            </button>
        <?php endif; ?>
    </div>

    <input type='hidden' id='gameName' value='<?= htmlspecialchars($gameName, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='playerID' value='<?= htmlspecialchars($playerID, ENT_QUOTES, 'UTF-8'); ?>'>
    <input type='hidden' id='authKey' value='<?= htmlspecialchars($authKey, ENT_QUOTES, 'UTF-8'); ?>'>


  </body>
