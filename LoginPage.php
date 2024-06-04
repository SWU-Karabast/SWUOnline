<?php
include_once './MenuBar.php';
include_once './AccountFiles/AccountSessionAPI.php';

if (IsUserLoggedIn()) {
  header("Location: ./game/MainMenu.php");
}
?>

<?php
include_once 'Header.php';
?>

<div class="core-wrapper">
<div class="flex-padder"></div>

<div class="flex-wrapper">
  <div class="login container bg-black">
    <h2>Log In</h2>
    <p class="login-message">Make sure to use your username, not your email!</i></p>
    
    <form action="./AccountFiles/AttemptPasswordLogin.php" method="post" class="LoginForm">
      <label>Username</label>
      <input class="username" type="text" name="userID">
      <label>Password</label>
      <input class="password" type="password" name="password">
      <div class="remember-me">
      <input type="checkbox" checked='checked' id="rememberMe" name="rememberMe" value="rememberMe">
      <label for="rememberMe">Remember Me</label> 
      </div>
      <button type="submit" name="submit">Submit</button>
    </form>
    <form action="ResetPassword.php" method="post" style='text-align:center;'>
      <!-- <button type="submit" name="reset-password">Forgot Password?</button> -->
    </form>
  </div>

  <div class="container bg-blue">
    <p>By using the Remember Me function, you consent to a cookie being stored in your browser for the purpose of identifying
      your account on future visits.</p>
    <a href='./MenuFiles/PrivacyPolicy.php'>Privacy Policy</a>
  </div>
    
</div>

<div class="flex-padder"></div>
</div>

<?php
include_once './Disclaimer.php';
?>