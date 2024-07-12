<?php

include_once 'MenuBar.php';

require_once './Assets/patreon-php-master/src/OAuth.php';
require_once './Assets/patreon-php-master/src/API.php';
require_once './Assets/patreon-php-master/src/PatreonLibraries.php';
include_once './Assets/patreon-php-master/src/PatreonDictionary.php';
include_once './includes/functions.inc.php';
include_once "./includes/dbh.inc.php";
include_once "./Libraries/HTTPLibraries.php";

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}
$debugMode = false;
$useruid = $_SESSION["useruid"];
if ($useruid == "OotTheMonk") {
  $userName = TryGet("userName", "");
  if($userName == "") $userName = $_SESSION["useruid"];
  else $debugMode = true;
} else {
  $userName = $_SESSION["useruid"];
}

$conn = GetDBConnection();
$sql = "SELECT * FROM users where usersUid='$userName'";
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $sql)) {
  echo ("ERROR");
  exit();
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);
mysqli_close($conn);
$access_token = $row["patreonAccessToken"];

try {
  PatreonLogin($access_token, false, $debugMode);
} catch (\Exception $e) { }
