<?php

include_once 'Assets/patreon-php-master/src/OAuth.php';
include_once 'Assets/patreon-php-master/src/API.php';
include_once 'Assets/patreon-php-master/src/PatreonLibraries.php';
include_once 'Assets/patreon-php-master/src/PatreonDictionary.php';
include_once 'includes/functions.inc.php';
include_once 'includes/dbh.inc.php';
include_once 'Libraries/HTTPLibraries.php';
include_once 'HostFiles/Redirector.php';
session_start();

if (!isset($_SESSION["userid"])) {
  if (isset($_COOKIE["rememberMeToken"])) {
    loginFromCookie();
  }
}

$isPatron = isset($_SESSION["isPatron"]);

$isMobile = IsMobile();

?>

<head>
  <meta charset="utf-8">
  <title>Karabast</title>
  <link rel="shortcut icon" type="image/png" href="Images/karabastTiny.png" />
  <link rel="stylesheet" href="./css/karabast.css">
  <!-- <link rel="stylesheet" href="./css/menuStyles2.css"> -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Teko:wght@700&display=swap" rel="stylesheet">
</head>

<body>

  <div class='nav-bar'>
    
    <div class='nav-bar-user'>
      <ul class='rightnav'>
        <?php //if($isPatron) echo "<li><a href='Replays.php'>Replays[BETA]</a></li>";
        ?>
        <?php
        if (isset($_SESSION["useruid"])) {
          echo "<li><a href='ProfilePage.php' class='NavBarItem'>Profile</a></li>";
          echo "<li><a href='./AccountFiles/LogoutUser.php' class='NavBarItem'>Log Out</a></li>";
        } else {
          echo "<li><a href='Signup.php' class='NavBarItem'>Sign Up</a></li>";
          echo "<li><a href='./LoginPage.php' class='NavBarItem'>Log In</a></li>";
        }
        ?>
      </ul>
    </div>
    
    <div class='nav-bar-links'>
      <ul>
          <?php
            echo '<li><a target="_blank" href="https://discord.gg/hKRaqHND4v"><img src="./Images/icons/discord.svg"></img></a></li>';
            echo '<li><a target="_blank" href="https://github.com/OotTheMonk/SWUOnline"><img src="./Images/icons/github.svg"></img></a></li>';
          ?>
      </ul>
    </div>

  </div>