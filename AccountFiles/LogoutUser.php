<?php
include_once './AccountSessionAPI.php';

ClearLoginSession();

// Check if the 'redirect' parameter is present in the URL and its value is 'login'
if (isset($_GET['redirect']) && $_GET['redirect'] === 'login') {
  // Redirect to the login page
  header("location: ../LoginPage.php");
} else {
  // Otherwise, redirect to the main menu page
  header("location: ../MainMenu.php");
}

exit;
?>
