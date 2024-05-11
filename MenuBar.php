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
  <title>Karabast</title>
  <link rel="shortcut icon" type="image/png" href="Images/karabastTiny.png" />
  <link rel="stylesheet" href="./css/menuStyles2.css">
  <style>
    body {
      background-image: url('Images/UnderDevBackground.webp');
      background-position: top center;
      background-repeat: no-repeat;
      background-size: cover;
      overflow: hidden;
      height: 100vh;
      height: 100dvh;
    }

    li {
      display: flex;
    }

    ul {
      display: flex;
      margin: 0px;
      column-gap: 15px;
      align-items: center;
    }

    a {
      color: white;
      background-color: transparent;
      text-decoration: none;
      font-family: helvetica;
    }

    .ContentWindow {
      padding: 0 1em;
      background-color: rgba(70, 20, 20, .7);
      font-family: helvetica;
      color: white;
      position: absolute;
      border-radius: 8px;
      border: 2px solid rgb(255, 87, 51);
    }

    .NavBarDiv {
      font-size: 1.5rem;
      position: fixed;
      left: 0px;
      top: 0px;
      height: 45px;
      width: 100%;
      z-index: 100;
      background-color: rgba(40, 5, 5, .8);
      display: flex;
      justify-content: space-between;
    }

    .NavBarDiv ul {
      padding-left: 2rem;
      padding-right: 2rem;
      column-gap: 10px;
    }

    .NavBarDiv img {
      width: 25px;
      height: 25px;
    }

    .NavBarItem {
      font-size: 18px;
      font-weight: 600;
    }

    .NavBarItemDivider {
      font-size: 14px;
      font-weight: 600;
      height: 27px;
    }

    h1,
    h2,
    h3,
    h4,
    h5 {
      text-align: center;
      margin-top: 12px;
      margin-bottom: 12px;
    }

    td {
      color: white;
      padding-top: 2px;
      padding-bottom: 2px;
    }

    span {
      color: white;
    }

    table {
      margin-bottom: 0px;
    }

    form {
      margin-bottom: 4px;
    }


    div::-webkit-scrollbar-track {
      -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, 0.3);
      border-radius: 10px;
      background-color: #F5F5F5;
    }

    div::-webkit-scrollbar {
      width: 12px;
      background-color: #F5F5F5;
    }

    div::-webkit-scrollbar-thumb {
      border-radius: 10px;
      -webkit-box-shadow: inset 0 0 6px rgba(0, 0, 0, .3);
      background-color: #555;
    }
  </style>
</head>

<body style="background-image: url('./Images/UnderDevBackground.webp');">

  <div style='width: 100%'>
    <nav class='NavBarDiv'>
      <ul>
        <?php
        if (!$isMobile)
          echo '<li><a target="_blank" href="https://discord.gg/hKRaqHND4v"><img src="./Images/icons/discord.svg"></img></a></li>';
        echo '<li><a target="_blank" href="https://github.com/OotTheMonk/SWUOnline"><img src="./Images/icons/github.svg"></img></a></li>';
        ?>
        <li><a target="_blank" href="https://www.patreon.com/OotTheMonk"><img
              src="./Images/icons/patreon.svg"></img></a></li>
      </ul>

      <ul class='rightnav'>
        <li><a href="MainMenu.php" class="NavBarItem">Home Page</a></li>
        <span class="NavBarItemDivider">⟡</span>
        <?php //if($isPatron) echo "<li><a href='Replays.php'>Replays[BETA]</a></li>";
        ?>
        <?php
        if (isset($_SESSION["useruid"])) {
          echo "<li><a href='ProfilePage.php' class='NavBarItem'>Profile</a></li>";
          echo "<span class='NavBarItemDivider'>⟡</span>";
          echo "<li><a href='./AccountFiles/LogoutUser.php' class='NavBarItem'>Logout</a></li>";
        } else {
          echo "<li><a href='Signup.php' class='NavBarItem'>Sign up</a></li>";
          echo "<span class='NavBarItemDivider'>⟡</span>";
          echo "<li><a href='./LoginPage.php' class='NavBarItem'>Log in</a></li>";
        }
        ?>
      </ul>
    </nav>
  </div>