<?php

if (isset($_POST["submit"])) {

  // First we get the form data from the URL
  $username = $_POST["uid"];
  $newUsername = $_POST["newuid"];
  $confirmNewUsername = $_POST["confirmnewuid"];

  // Then we run a bunch of error handlers to catch any user mistakes we can (you can add more than I did)
  // These functions can be found in functions.inc.php

  require_once "dbh.inc.php";
  require_once 'functions.inc.php';

	$conn = GetDBConnection();
  // We set the functions "!== false" since "=== true" has a risk of giving us the wrong outcome
  if (emptyInputChangeUsername($username, $newUsername, $confirmNewUsername) !== false) {
    header("location: ../ChangeUsername.php?error=emptyinput");
		exit();
  }

	// Proper username chosen
  if (invalidUid($newUsername) !== false) {
    header("location: ../ChangeUsername.php?error=invaliduid");
		exit();
  }
  // Do the two usernames match?
  if (pwdMatch($newUsername, $confirmNewUsername) !== false) {
    header("location: ../ChangeUsername.php?error=usernamesdontmatch");
		exit();
  }
  // Is the username taken already
  if (uidExists($conn, $newUsername) !== false) {
    header("location: ../ChangeUsername.php?error=usernametaken");
		exit();
  }

  // If we get to here, it means there are no user errors

  // Now we change the username in the database
  changeUsername($conn, $username, $newUsername);
  mysqli_close($conn);
} else {
	header("location: ../ChangeUsername.php");
  exit();
}
