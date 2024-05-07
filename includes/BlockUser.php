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






/*

  $sql = "SELECT * FROM pwdReset WHERE pwdResetSelector=? AND pwdResetExpires >= ?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $sql)) {
    echo "There was an error finding your password reset selector.";
    exit();
  } else {
    mysqli_stmt_bind_param($stmt, "ss", $selector, $currentDate);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (!$row = mysqli_fetch_assoc($result)) {
      echo "You need to re-submit your reset request.";
      exit();
    } else {

      $tokenBin = hex2bin($validator);
      $tokenCheck = password_verify($tokenBin, $row['pwdResetToken']);

      // Then if they match we grab the users e-mail from the database.
      if ($tokenCheck === false) {
        echo "There was an error with your token.";
      } elseif ($tokenCheck === true) {

        // Before we get the users info from the user table we need to store the token email for later.
        $tokenEmail = $row['pwdResetEmail'];

        // Here we query the user table to check if the email we have in our pwdReset table exists.
        $sql = "SELECT * FROM users WHERE usersEmail=?";
        $stmt = mysqli_stmt_init($conn);
        if (!mysqli_stmt_prepare($stmt, $sql)) {
          echo "There was an error preparing the email query.";
          exit();
        } else {
          mysqli_stmt_bind_param($stmt, "s", $tokenEmail);
          mysqli_stmt_execute($stmt);
          $result = mysqli_stmt_get_result($stmt);
          if (!$row = mysqli_fetch_assoc($result)) {
            echo "Your email could not be found in the database.";
            exit();
          } else {

            // Finally we update the users table with the newly created password.
            $sql = "UPDATE users SET usersPwd=? WHERE usersEmail=?";
            $stmt = mysqli_stmt_init($conn);
            if (!mysqli_stmt_prepare($stmt, $sql)) {
              echo "There was an issue updating your password.";
              exit();
            } else {
              $newPwdHash = password_hash($password, PASSWORD_DEFAULT);
              mysqli_stmt_bind_param($stmt, "ss", $newPwdHash, $tokenEmail);
              mysqli_stmt_execute($stmt);

              // Then we delete any leftover tokens from the pwdReset table.
              $sql = "DELETE FROM pwdReset WHERE pwdResetEmail=?";
              $stmt = mysqli_stmt_init($conn);
              if (!mysqli_stmt_prepare($stmt, $sql)) {
                echo "There was an issue deleting the password request.";
                exit();
              } else {
                mysqli_stmt_bind_param($stmt, "s", $tokenEmail);
                mysqli_stmt_execute($stmt);
                header("Location: ../Signup.php?newpwd=passwordupdated");
              }

            }

          }
        }

      }

    }
  }

} else {
  header("Location: ../MainMenu.php");
  exit();
}
*/