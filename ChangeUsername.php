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
<div class='container bg-black'>

<section class="change-username-form">
  <h2>Change Your Username</h2>
  <div class="change-username-form-form">
    <form action="includes/change-username.inc.php" method="post">
      <label for="uid">Username</label>
        <input type="text" disabled name="uid" value="<?php echo $_SESSION['useruid'] ?>">
        <input type="hidden" name="uid" value="<?php echo $_SESSION['useruid']; ?>">
      <label for="newuid">New Username</label>
        <input type="text" name="newuid">
      <label for="confirmnewuid">Confirm New Username</label>
        <input type="text" name="confirmnewuid">
      <div style="text-align:center;">
        <button type="submit" name="submit">Change</button>
      </div>
    </form>
  </div>

  <?php
  // Error messages
  if (isset($_GET["error"])) {
    if ($_GET["error"] == "emptyinput") {
      echo "<h3 class='changer-username-error-message'>Fill in all fields!</h3>";
    } else if ($_GET["error"] == "invaliduid") {
      echo "<h3 class='changer-username-error-message'>Choose a username without any special characters</h3>";
    } else if ($_GET["error"] == "usernamesdontmatch") {
      echo "<h3 class='changer-username-error-message'>Usernames doesn't match!</h3>";
    } else if ($_GET["error"] == "stmtfailed") {
      echo "<h3 class='changer-username-error-message'>Something went wrong!</h3>";
    } else if ($_GET["error"] == "usernametaken") {
      echo "<h3 class='changer-username-error-message'>Username already taken!</h3>";
    } else if ($_GET["error"] == "none") {
      echo "<h3 class='changer-username-error-message'>Your username have changed!</h3>";
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
        alert("Your username has been successfully changed! Please log in with your new username.");
        window.location.href = "./AccountFiles/LogoutUser.php?redirect=login";
    }

    <?php if (isset($_GET["error"]) && $_GET["error"] == "none") { ?>
        window.onload = showPopupAndRedirect;
    <?php } ?>
</script>

<?php
include_once 'Disclaimer.php';
?>
