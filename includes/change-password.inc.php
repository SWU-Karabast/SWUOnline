<?php

include_once '../AccountFiles/AccountSessionAPI.php';

if(!IsUserLoggedIn()) {
  header("Location: ../MainMenu.php");
}

if (isset($_POST["submit"])) {

  // First we get the form data from the URL
  $userID = LoggedInUser();
  $currentPwd = $_POST["currentpwd"];
  $newPwd = $_POST["newpwd"];
  $confirmNewPwd = $_POST["confirmnewpwd"];

  // Then we run a bunch of error handlers to catch any user mistakes we can (you can add more than I did)
  // These functions can be found in functions.inc.php

  require_once "./dbh.inc.php";
  require_once './functions.inc.php';

  $conn = GetDBConnection();

  // Do the two passwords match?
  if ($newPwd != $confirmNewPwd) {
    header("location: ../ChangePassword.php?error=passwordsdontmatch");
    exit();
  }

  // If we get to here, it means there are no user errors

  // Now we change the password in the database
  changePassword($conn, $userID, $newPwd);
  mysqli_close($conn);
} else {
  header("location: ../ChangePassword.php");
  exit();
}
