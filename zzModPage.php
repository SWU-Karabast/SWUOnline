<meta name="viewport" content="width=device-width, initial-scale=0.67">
<?php

include_once 'MenuBar.php';

include_once './includes/functions.inc.php';
include_once "./includes/dbh.inc.php";

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}
$useruid = $_SESSION["useruid"];
if ($useruid != "OotTheMonk" && $useruid != "love" && $useruid != "ninin" && $useruid != "Brubraz") {
  echo ("You must log in to use this page.");
  exit;
}
$sectionStyles = "style='position:relative; top: 10%; padding:16px; margin: 16px auto; z-index:1; width:500px; height:240px;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px; overflow-y: scroll;'";
$sectionStylesSingle = "style='position:relative; top: 10%; padding:16px; margin: 16px auto; z-index:1; width:500px; height:180px;
  background-color:rgba(74, 74, 74, 0.9); border: 2px solid #1a1a1a; border-radius: 5px;'";
echo("<div $sectionStyles>");
echo("<h2>Ban Player</h2>");
echo ("<form  action='./BanPlayer.php'>");
?>
<label for="playerToBan" style='font-weight:bolder; margin-left:10px;'>Player to ban:</label>
<input type="text" id="playerToBan" name="playerToBan" value="">
<input type="submit" value="Ban">
</form>

<form action='./BanPlayer.php'>
<label for="playerToUnban" style='font-weight:bolder; margin-left:10px;'>Player to unban:</label>
<input type="text" id="playerToUnban" name="playerToUnban" value="">
<input type="submit" value="Unban">
</form>
<?php
$countRecent = 20;
echo ("<h2>$countRecent Most Recent Accounts:</h2>");
$conn = GetDBConnection();
$sql = "SELECT usersUid FROM users ORDER BY usersId DESC LIMIT $countRecent";
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $sql)) {
  //header("location: ../Signup.php?error=stmtfailed");
  echo ("ERROR");
  exit();
}

//mysqli_stmt_bind_param($stmt, "ss", $username, $email);
mysqli_stmt_execute($stmt);

// "Get result" returns the results from a prepared statement
$userData = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_array($userData, MYSQLI_NUM)) {
  echo ($row[0] . "<BR>");
}
mysqli_close($conn);

echo ("</div>");
echo ("<div $sectionStyles>");
echo ("<h2>Banned IPs:</h2>");
$banfileHandler = fopen("./HostFiles/bannedIPs.txt", "r");
while (!feof($banfileHandler)) {
  $bannedIP = fgets($banfileHandler);
  echo ($bannedIP . "<BR>");
}
fclose($banfileHandler);
?>
<form action='./BanPlayer.php'>
  <label for="ipToBan" style='font-weight:bolder; margin-left:10px;'>Game to IP ban from:</label>
  <input type="text" id="ipToBan" name="ipToBan" value="">
  <br/><label for="playerNumberToBan" style='font-weight:bolder; margin-left:10px;'>Player to ban? (1 or 2):</label>
  <input type="text" id="playerNumberToBan" name="playerNumberToBan" value="">
  <input type="submit" value="Ban">
</form>
<?php
echo ("</div>");
echo ("<div $sectionStylesSingle>");
echo ("<h2>Close Game:</h2>");
?>
<form action='./CloseGame.php'>
  <label for="gameToClose" style='font-weight:bolder; margin-left:10px;'>Game to close:</label>
  <input type="text" id="gameToClose" name="gameToClose" value="">
  <input style="margin-left:16px;" type="submit" value="Close Game">
</form>
</div>
<?php
echo ("<div $sectionStylesSingle>");
echo ("<h2>Boot Player:</h2>");
?>
<form action='./BootPlayer.php'>
  <label for="gameToClose" style='font-weight:bolder; margin-left:10px;'>Game:</label>
  <input type="text" id="gameToClose" name="gameToClose" value="">
  </form>
  <label for="playerToBoot" style='font-weight:bolder; margin-left:10px;'>Player to boot:</label>
  <input type="text" id="playerToBoot" name="playerToBoot" value="">
  <input type="submit" value="Boot Player">
</form>
</div>
<?php
  $banfileHandler = fopen("./HostFiles/bannedPlayers.txt", "r");
  echo ("<div $sectionStyles>");
  echo ("<h2>Banned Players:</h2>");
  while (!feof($banfileHandler)) {
    $bannedPlayer = fgets($banfileHandler);
    echo ($bannedPlayer . "<br/>");
  }
  fclose($banfileHandler);
  echo("</div>");
?>