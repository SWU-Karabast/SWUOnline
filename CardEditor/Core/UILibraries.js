
      //Rotate is deprecated
      function Card(cardNumber, folder, maxHeight, action = 0, showHover = 0, overlay = 0, borderColor = 0, counters = 0, actionDataOverride = "", id = "", rotate = 0, lifeCounters = 0, defCounters = 0, atkCounters = 0, controller = 0, restriction = "", isBroken = 0, onChain = 0, isFrozen = 0, gem = 0, landscape = 0, epicActionUsed = 0) {
        if (folder == "crops") {
          cardNumber += "_cropped";
        }
        fileExt = ".png";
        folderPath = folder;
        var pathArr = folder.split("/");
        folder = pathArr[pathArr.length - 1];

        if (cardNumber == "ENDSTEP" || cardNumber == "ENDTURN" || cardNumber == "RESUMETURN" || cardNumber == "PHANTASM" || cardNumber == "FINALIZECHAINLINK" || cardNumber == "DEFENDSTEP") {
          showHover = 0;
          borderColor = 0;
        } else if (folder == "concat") {
          fileExt = ".webp";
        } else if (folder == "WebpImages") {
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

        //var altText = " alt='" + CardTitle(cardNumber) + "' ";//TODO:Fix screenreader mode
        var altText = " alt='Card' ";
        rv += "<img " + (id != "" ? "id='" + id + "-img' " : "") + altText + orientation + "style='" + border + " height:" + height + "; width:" + width + "px; position:relative;' src='./" + folderPath + "/" + cardNumber + fileExt + "' />";
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
        /*
        if (gem != 0) {
          var playerID = <?php echo ($playerID); ?>;
           //Note: 96 = Card Size
          var cardWidth = 96;
          gemImg = (gem == 1 ? "hexagonRedGem.png" : "hexagonGrayGem.png");
          if (gem == 1) rv += "<img " + ProcessInputLink(playerID, 102, actionDataOverride) + " title='Effect Active' style='position:absolute; z-index:1001; bottom:3px; left:" + (cardWidth / 2 - 18) + "px; width:40px; height:40px; cursor:pointer;' src='./Images/" + gemImg + "' />";
          else if (gem == 2) rv += "<img " + ProcessInputLink(playerID, 102, actionDataOverride) + " title='Effect Inactive' style='position:absolute; z-index:1001; bottom:3px; left:" + (cardWidth / 2 - 18) + "px; width:40px; height:40px; cursor:pointer;' src='./Images/" + gemImg + "' />";
        }
          */
        return rv;
      }

      
      function Hotkeys(event) {
        if (event.keyCode === 32) { if(document.getElementById("passConfirm").innerText == "false" || confirm("Do you want to skip arsenal?")) SubmitInput(99, ""); } //Space = pass
        if (event.keyCode === 117) SubmitInput(10000, ""); //U = undo
        if (event.keyCode === 104) SubmitInput(3, "&cardID=0"); //H = hero ability
        if (event.keyCode === 109) TogglePopup("menuPopup"); //M = open menu
        //TODO: Add schema file to define hotkeys
      }

      function ProcessInputLink(player, mode, input, event = 'onmousedown', fullRefresh = false) {
        return " " + event + "='SubmitInput(\"" + mode + "\", \"&buttonInput=" + input + "\", " + fullRefresh + ");'";
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

      // Function to handle drag start event
      function dragStart(e) {
          // Set the drag's data and styling
          var id = e.target.id;
          var element = e.target;
          var tries = 0;
          while(id == "" && tries < 20) {
            element = element.parentNode;
            id = element.id;
            ++tries;
          }
          e.dataTransfer.setData("text/plain", id);
          e.target.style.opacity = "0.4";
          HideCardDetail();
          //Now show the droppable areas
          var dropArea = document.getElementById("groundArena");
          dropArea.classList.add("droppable");
      }

      // Function to handle drag end event
      function dragEnd(e) {
          // Reset the element's opacity after drag
          e.target.style.opacity = "1";
          var dropArea = document.getElementById("groundArena");
          dropArea.classList.remove("droppable");
      }

      // Function to handle drag over event
      function dragOver(e) {
          e.preventDefault(); // Allow drop
      }

      // Function to handle drop event
      function drop(e) {
          e.preventDefault(); // Prevent default action (open as link for some elements)
          var destination = e.target.id;

          // Get the card being dragged
          var draggedCard = e.dataTransfer.getData("text/plain");
          alert(draggedCard + " " + destination);

          // Send the action input to the server
          SubmitInput("10014", "&cardID=" + draggedCard + "!" + destination);

          //var draggedElement = document.getElementById(draggedCard);
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
      function PopulateZone(zone, zoneData, size = 96, folder = "concat", row = 1) {
          zoneData = zoneData.trim();
          var newHTML = "<span id='" + zone + " ondragover='dragOver(event)' ondrop='drop(event)' style='display: flex; flex-wrap: wrap; justify-content: center;'>";
          var zoneArr = (zoneData.length == 0 ? [] : zoneData.split("<|>"));
          for (var i = 0; i < zoneArr.length; ++i) {
              cardArr = zoneArr[i].split(" ");
              var id = zone + "-" + i;
              var positionStyle = "relative";
              var type = cardArr[10];
              var substype = cardArr[11];
              var className = "";

              var styles = " style='position:" + positionStyle + "; margin:1px;'";
              var droppable = " class='draggable " + className + "' draggable='true' ondragstart='dragStart(event)' ondragend='dragEnd(event)'";
              if (id != "-") newHTML += "<span id='" + id + "' " + styles + droppable + ">";
              else newHTML += "<span " + styles + droppable + ">";
              if (type == "C") {
                  folder = "WebpImages";
                  // User Tags
                  if (zone == "myChar") {
                      var fontColor = "#DDD";
                      var borderColor = "#1a1a1a";
                      var backgroundColor = "#DDD";
                      newHTML += "<div class='player-name'>Player</div>";//TODO:Fix player name
                  } else if (zone == "theirChar") {
                      var fontColor = "#DDD";
                      var borderColor = "#1a1a1a";
                      var backgroundColor = "#DDD";
                      newHTML += "<div class='player-name'>Player</div>";//TODO:Fix player name
                  }
              }
              var restriction = cardArr[12];
              if (typeof restriction != "string") restriction = "";
              restriction = restriction.replace(/_/g, ' ');
              newHTML += Card(cardArr[0], folder, size, cardArr[1], 1, cardArr[2], cardArr[3], cardArr[4], cardArr[5], "", cardArr[17], cardArr[6], cardArr[7], cardArr[8], cardArr[9], restriction, cardArr[13], cardArr[14], cardArr[15], cardArr[16], cardArr[18], cardArr[19]);
              newHTML += "</span>";
          }
          newHTML += "</span>";
          return newHTML;
      }

      function RenderRows(myRows, theirRows) {
        var theirStuff = "";
        var myStuff = "";
        theirRows.forEach(element => {
          theirStuff += element + "<br>";
        });
        myRows.forEach(element => {
          myStuff += element + "<br>";
        });

        var theirDiv = document.getElementById("theirStuff");
        var myDiv = document.getElementById("myStuff");
        theirDiv.innerHTML = theirStuff;
        myDiv.innerHTML = myStuff;
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