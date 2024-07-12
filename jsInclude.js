var _openPopup = null;

function OnLoadCallback(lastUpdate) {
  var log = document.getElementById("gamelog");
  if (log !== null) log.scrollTop = log.scrollHeight;
  reload();
}

function ShowCardDetail(e, that) {
  if (e.target.hasAttribute("data-subcard-id")) {
    var subCardID = e.target.getAttribute("data-subcard-id");
    ShowDetail(e, `${window.location.origin}/SWUOnline/WebpImages2/${subCardID}.webp`);
  } else {
    ShowDetail(e, that.getElementsByTagName("IMG")[0].src);
  }
}


function ShowDetail(e, imgSource) {
  imgSource = imgSource.replace("_cropped", "");
  imgSource = imgSource.replace("/crops/", "/WebpImages2/");
  imgSource = imgSource.replace("_concat", "");
  imgSource = imgSource.replace("/concat/", "/WebpImages2/");
  imgSource = imgSource.replace(".png", ".webp");
  var el = document.getElementById("cardDetail");
  el.innerHTML = e.target.getAttribute("data-orientation") == "landscape" ?
    "<img style='height:375px; width:523px;' src='" + imgSource + "' />":
    "<img style='height:523px; width:375px;' src='" + imgSource + "' />";
  el.style.left =
    (e.clientX < window.innerWidth / 2 ? e.clientX + 30 : e.clientX - 400) + 'px';
  el.style.top =
    (e.clientY > window.innerHeight / 2 ? e.clientY - 523 - 20 : e.clientY + 30) + 'px';
  if (parseInt(el.style.top) + 523 >= window.innerHeight) {
    el.style.top = (window.innerHeight - 530) + 'px';
    el.style.left =
      (e.clientX < window.innerWidth / 2 ? e.clientX + 30 : e.clientX - 400) + 'px';
  } else if (parseInt(el.style.top) <= 0) {
    el.style.top = '5px';
    el.style.left =
      (e.clientX < window.innerWidth / 2 ? e.clientX + 30 : e.clientX - 400) + 'px';
  }
  el.style.zIndex = 100000;
  el.style.display = "inline";
}

function HideCardDetail() {
  var el = document.getElementById("cardDetail");
  el.style.display = "none";
}

function ChatKey(event) {
  if (event.keyCode === 13) {
    event.preventDefault();
    SubmitChat();
  }
  event.stopPropagation();
}

function SubmitChat() {
  var chatBox = document.getElementById("chatText");
  if (chatBox.value == "") return;
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function () {
    if (this.readyState == 4 && this.status == 200) {
    }
  };
  var ajaxLink =
    "SubmitChat.php?gameName=" + document.getElementById("gameName").value;
  ajaxLink +=
    "&playerID=" + document.getElementById("playerID").value +
    "&chatText=" + encodeURI(chatBox.value) +
    "&authKey=" + document.getElementById("authKey").value;
  xmlhttp.open("GET", ajaxLink, true);
  xmlhttp.send();
  chatBox.value = "";
}

function AddCardToHand() {
  var card = document.getElementById("manualAddCardToHand").value;
  SubmitInput(10011, "&cardID=" + card);
}

function SubmitInput(mode, params, fullRefresh = false) {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function () {
    if (this.readyState == 4 && this.status == 200) {
      if (fullRefresh) location.reload();
    }
  };
  var ajaxLink =
    "ProcessInput2.php?gameName=" + document.getElementById("gameName").value;
  ajaxLink += "&playerID=" + document.getElementById("playerID").value;
  ajaxLink += "&authKey=" + document.getElementById("authKey").value;
  ajaxLink += "&mode=" + mode;
  ajaxLink += params;
  xmlhttp.open("GET", ajaxLink, true);
  xmlhttp.send();
}

function TogglePopup(name) {
  if (document.getElementById(name)?.style.display == "inline") {
    document.getElementById(name).style.display = "none"
  } else {
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
      if (this.readyState == 4 && this.status == 200) {
        document.getElementById("popupContainer").innerHTML = this.responseText;
        document.getElementById(name).style.display = "inline";
      }
    };
    var ajaxLink =
      "./GetPopupContent.php?gameName=" +
      document.getElementById("gameName").value;
    ajaxLink += "&playerID=" + document.getElementById("playerID").value;
    ajaxLink += "&authKey=" + document.getElementById("authKey").value;
    ajaxLink += "&popupType=" + name;
    xmlhttp.open("GET", ajaxLink, true);
    xmlhttp.send();
  }
}
