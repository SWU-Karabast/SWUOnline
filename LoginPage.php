<?php
include_once './MenuBar.php';
include_once './AccountFiles/AccountSessionAPI.php';

if (IsUserLoggedIn()) {
  header("Location: ./game/MainMenu.php");
}
?>

<div class="home-header">
  
  <h1>Karabast</h1>
  <h3>The Fan-Made, Open-Source <br>
  Star Wars Unlimited Simulator</h3>

  <div class="home-banner">
    <div class="banner block-1"></div>
    <div class="banner block-2"></div>
    <div class="banner block-3"></div>
    <div class="banner block-4"></div>
  </div>

</div>

<div class="home-wrapper">
<div class="flex-wrapper"></div>

<div class="flex-wrapper">
  <div class="login container bg-black">
    <h2>Login</h2>
    <h4> Enter your username, not your email </h4>
    <form action="./AccountFiles/AttemptPasswordLogin.php" method="post" class="LoginForm">
      <input type="text" name="userID" placeholder="Username">
      <input type="password" name="password" placeholder="Password">
      <div class="RemberMeContainer">
        <label for="rememberMe">Remember Me</label>
        <input type="checkbox" checked='checked' id="rememberMe" name="rememberMe" value="rememberMe">
      </div>
      <button type="submit" name="submit">Submit</button>
    </form>
    <form action="ResetPassword.php" method="post" style='text-align:center;'>
      <!-- <button type="submit" name="reset-password">Forgot Password?</button> -->
    </form>
    <p>By using the Remember Me function, you consent to a cookie being stored in your browser for purpose of identifying
      your account on future visits.</p>
    <a href='./MenuFiles/PrivacyPolicy.php'>Privacy Policy</a>
  </div>
</div>

<div class="flex-wrapper"></div>
</div>

<?php
include_once './Disclaimer.php';
?>