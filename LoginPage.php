<?php
include_once './MenuBar.php';
include_once './AccountFiles/AccountSessionAPI.php';

if (IsUserLoggedIn()) {
  header("Location: ./game/MainMenu.php");
}
?>

<style>
  body {
    background-image: url('./Images/welcome-to-rathe.jpg');
    background-position: top center;
    background-repeat: no-repeat;
    background-size: cover;
    overflow: hidden;
    height: 100vh;
    height: 100dvh;
  }

  .LoginForm {
    display: flex;
    flex-direction: column;
    column-gap: 15px;
    row-gap: 15px;
  }

  .RemberMeContainer {
    display: flex;
    column-gap: 10px;
    align-items: center;
    align-content: center;
  }

  .RemberMeContainer input {
    margin-bottom: .25rem;
  }
</style>

<div class="ContentWindow" style='width:30%; height:40%; top:200px; left:35%;'>
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
</div>

<div class='ContentWindow' style='width:250px; height:400px; bottom:30px; right:30px; padding: 5px;'>
  <i>By using the Remember Me function, you consent to a cookie being stored in your browser for purpose of identifying
    your account on future visits.</i>
  <div style='width:100%; text-align:center'><a href='./MenuFiles/PrivacyPolicy.php'>Privacy Policy</a></div>
</div>

<?php
include_once './Disclaimer.php';
?>