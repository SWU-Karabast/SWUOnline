<?php


// First we check if the form was submitted.
if (isset($_POST['block-user-submit'])) {
  session_start();
  if (!isset($_SESSION['userid'])) {
    header('Location: ./MainMenu.php');
    die();
  }

  // Here we grab the data from the form.
  $userToBlock = $_POST['userToBlock'];

  if (empty($userToBlock) || $userToBlock == "") {
    header("Location: ../ProfilePage.php");
    exit();
  }

  require 'dbh.inc.php';

  $conn = GetDBConnection();
  $sql = "SELECT usersId FROM users WHERE usersUid=?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $sql)) {
    echo "There was an error preparing the blocked user lookup query.";
    exit();
  } else {
    mysqli_stmt_bind_param($stmt, "s", $userToBlock);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$row = mysqli_fetch_assoc($result)) {
      echo "The user you are trying to block could not be found in the database.";
      exit();
    } else {
      

      $sql = "INSERT INTO blocklist (blockingPlayer, blockedPlayer) VALUES (?, ?)";
      $stmt = mysqli_stmt_init($conn);
      if (!mysqli_stmt_prepare($stmt, $sql)) {
        echo "There was an error preparing the blocklist insert query.";
        exit();
      } else {
        mysqli_stmt_bind_param($stmt, "ss", $_SESSION['userid'], $row['usersId']);
        mysqli_stmt_execute($stmt);
        echo "You have successfully blocked " . $userToBlock . ".";
        exit();
      }
    
      mysqli_stmt_close($stmt);




    }
  }

  mysqli_close($conn);

}
