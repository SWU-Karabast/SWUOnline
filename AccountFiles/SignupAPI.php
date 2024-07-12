<?php

  require_once "../Libraries/HTTPLibraries.php";

  $_POST = json_decode(file_get_contents('php://input'), true);

  $username = $_POST["userId"];
  $email = $_POST["email"];
  $pwd = $_POST["password"];
  $pwdRepeat = $_POST["passwordRepeat"];

  SetHeaders();

  require_once "../includes/dbh.inc.php";
  require_once '../includes/functions.inc.php';

  $response = new stdClass();

  // We set the functions "!== false" since "=== true" has a risk of giving us the wrong outcome
  if (empty($username) || empty($email) || empty($pwd) || empty($pwdRepeat)) {
    $response->error = "One or more required fields is empty.";
    echo(json_encode($response));
    exit;
  }

	// Proper username chosen
  if (!ctype_alnum($username)) {
    $response->error = "The username must contain only letters or numbers.";
    echo(json_encode($response));
    exit;
  }
  // Proper email chosen
  if (invalidEmail($email) !== false) {
    $response->error = "The provided email is invalid.";
    echo(json_encode($response));
    exit;
  }
  // Do the two passwords match?
  if ($pwd !== $pwdRepeat) {
    $response->error = "The passwords do not match.";
    echo(json_encode($response));
    exit;
  }
  $conn = GetDBConnection();
  // Is the username taken already
  if (uidExists($conn, $username) !== false) {
    $response->error = "The chosen username is taken.";
    echo(json_encode($response));
    mysqli_close($conn);
    exit;
  }

  CreateUserAPI($conn, $username, $email, $pwd);

  $response->message = "Success!";
  echo(json_encode($response));
  mysqli_close($conn);
  exit;
?>
