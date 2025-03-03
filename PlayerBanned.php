<?php
ob_start();
include "HostFiles/Redirector.php";
include "Libraries/HTTPLibraries.php";
include_once "./AccountFiles/AccountDatabaseAPI.php";
ob_end_clean();

session_start();

$isUserBanned = isset($_SESSION["userid"]) ? IsBanned($_SESSION["userid"]) : false;
if(!$isUserBanned) {
  header("Location: MainMenu.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>You Have Been Banned</title>
  <link rel="stylesheet" href="./css/petranaki250301.css">
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;700&display=swap" rel="stylesheet">
  <style>
    body {
      background-size: cover;
      background-position: center;
      background-image: url('./Images/gamebg.jpg');
      width: 100%;
      min-height: 100vh;
      margin: 0;
      background-repeat: no-repeat;
      background-attachment: fixed;
    }
    .ban-container {
      text-align: center;
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    .ban-image {
      max-width: 400px;
      margin: 20px auto;
    }
    .ban-text {
      font-size: 1.2em;
      line-height: 1.6;
      margin: 20px 0;
    }
    .quote {
      font-style: italic;
      color: #666;
      font-size: 1.1em;
      margin: 30px 0;
    }
  </style>
</head>
<body>
  <div class="ban-container">
    <h1 style="margin-top: 80px;">You Have Been Banned</h1>
    <img src="./Images/vader.webp" alt="Darth Vader" height="200px" class="ban-image" style="border-radius: 50%; box-shadow: 0 0 20px 5px rgba(255, 0, 0, 0.3); filter: drop-shadow(0 0 10px rgba(255, 0, 0, 0.5));">
    <div class="ban-text">
      <p>"I find your lack of fair play disturbing."</p>
      <p>You have been banned from the game for violating our community guidelines.</p>
      <p>Like the Rebel Alliance against the Empire, we stand firm against those who would disrupt the peace and harmony of our gaming community.</p>
    </div>
    <div class="quote">
      "If you strike me down in anger, I shall become more powerful than you can possibly imagine."<br>
      - Unfortunately, this doesn't apply to banned accounts
    </div>
    <p>If you believe this is an error, please contact our support team.</p>
    <p style="margin-bottom: 10px;">May the Force be with you... but our servers won't.</p>
  </div>
</body>
</html>
