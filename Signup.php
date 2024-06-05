<?php
include_once 'MenuBar.php';
?>

<?php
include_once 'Header.php';
?>

<div class="core-wrapper">
<div class="flex-padder"></div>

<div class="flex-wrapper">
<div class='signup-wrapper container bg-black'>

<section class="signup-form">
  <h2>Sign Up</h2>
  <div class="signup-form-form">
    <form action="includes/signup.inc.php" method="post">
      <label for="uid">Username</label>
        <input type="text" name="uid">
      <label for="email">Email</label>
        <input type="text" name="email" placeholder="name@example.com">
      <label for="pwd">Password</label>
        <input type="password" name="pwd">
      <label for="pwdrepeat">Repeat Password</label>
        <input type="password" name="pwdrepeat">
      <div style="text-align:center;">
        <button type="submit" name="submit">Sign Up</button>
      </div>
    </form>
  </div>

  <?php
  // Error messages
  if (isset($_GET["error"])) {
    if ($_GET["error"] == "emptyinput") {
      echo "<p>Fill in all fields!</p>";
    } else if ($_GET["error"] == "invaliduid") {
      echo "<p>Choose a username without any special characters</p>";
    } else if ($_GET["error"] == "invalidemail") {
      echo "<p>Choose a valid email</p>";
    } else if ($_GET["error"] == "passwordsdontmatch") {
      echo "<p>Passwords doesn't match!</p>";
    } else if ($_GET["error"] == "stmtfailed") {
      echo "<p>Something went wrong!</p>";
    } else if ($_GET["error"] == "usernametaken") {
      echo "<p>Username already taken!</p>";
    } else if ($_GET["error"] == "none") {
      echo "<h2>You've signed up!</h2>";
    }
  }
  ?>
</section>

</div>
</div>

<div class="flex-padder"></div>
</div>

<?php
include_once 'Disclaimer.php';
?>
