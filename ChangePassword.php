<?php
include_once 'MenuBar.php';
include_once './AccountFiles/AccountDatabaseAPI.php';

if (!isset($_SESSION['userid'])) {
  header('Location: ./MainMenu.php');
  die();
}

// Check if the user is banned
if (isset($_SESSION["userid"]) && IsBanned($_SESSION["userid"])) {
  header("Location: ./PlayerBanned.php");
  exit;
}
?>

<?php
include_once 'Header.php';
?>

<div class="core-wrapper">
<div class="flex-padder"></div>

<div class="flex-wrapper">
<div class='container bg-yellow'>

<section class="change-password-form">
  <h2>Change Your Password</h2>
  <div class="change-password-form-form">
    <form action="includes/change-password.inc.php" method="post">
      <div>
        <label for="currentpwd">Current Password</label>
        <input type="password" name="currentpwd" id="currentpwd">
      </div>
      <div>
        <label for="newpwd">New Password</label>
        <input type="password" name="newpwd" id="newpwd">
      </div>
      <div>
        <label for="confirmnewpwd">Confirm New Password</label>
        <input type="password" name="confirmnewpwd" id="confirmnewpwd">
      </div>
      <div style="text-align:center;">
        <button type="submit" name="submit">Change</button>
      </div>
    </form>
  </div>

  <?php
  // Error messages
  if (isset($_GET["error"])) {
    if ($_GET["error"] == "emptyinput") {
      echo "<h3 class='change-password-error-message'>Fill in all fields!</h3>";
    } else if ($_GET["error"] == "wrongpassword") {
      echo "<h3 class='change-password-error-message'>Incorrect current password!</h3>";
    } else if ($_GET["error"] == "passwordsdontmatch") {
      echo "<h3 class='change-password-error-message'>Passwords don't match!</h3>";
    } else if ($_GET["error"] == "stmtfailed") {
      echo "<h3 class='change-password-error-message'>Something went wrong!</h3>";
    } else if ($_GET["error"] == "none") {
      echo "<h3 class='change-password-error-message'>Your password has been changed!</h3>";
    }
  }
  ?>
</section>

</div>
</div>

<div class="flex-padder"></div>
</div>

<script>
    function showPopupAndRedirect() {
        alert("Your password has been successfully changed! Please log in with your new password.");
        window.location.href = "./AccountFiles/LogoutUser.php?redirect=login";
    }

    <?php if (isset($_GET["error"]) && $_GET["error"] == "none") { ?>
        window.onload = showPopupAndRedirect;
    <?php } ?>
</script>

<?php
include_once 'Disclaimer.php';
?>
