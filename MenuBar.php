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
    <title>Petranaki</title>
    <link rel="icon" type="image/png" href="Images/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="Images/favicon.svg" />
    <link rel="shortcut icon" href="Images/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="Images/apple-touch-icon.png" />
    <link rel="manifest" href="site.webmanifest" />
    <link rel="stylesheet" href="./css/petranaki250313.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Teko:wght@700&display=swap" rel="stylesheet">
</head>

<body>

    <div class='nav-bar' style="display: block;">
        <div style="display: flex;">
            <div class='nav-bar-user'>
                <ul class='rightnav'>
                    <?php
                    if (isset($_SESSION["useruid"])) {
                        echo "<li><a href='UnimplementedCards.php' class='NavBarItem'>Preview Cards</a></li>";
                        echo "<li><a href='https://swustats.net/TCGEngine/SharedUI/MainMenu.php' target='_blank' class='NavBarItem'>SWU Stats</a></li>";
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
                    echo '<li><a target="_blank" href="https://discord.gg/ep9fj8Vj3F"><img src="./Images/icons/discord.svg" alt="Discord"></a></li>';
                    echo '<li><a target="_blank" href="https://github.com/SWU-Petranaki/SWUOnline"><img src="./Images/icons/github.svg" alt="GitHub"></a></li>';
                    echo '<li>
                    <a href="javascript:void(0);" onclick="toggleLanguages()">
                        <img src="./Images/icons/globe.svg" alt="Languages">
                    </a>
                    <ul id="languageList" style="display: none;">';

                    $languages = [
                        'EN' => 'English',
                        'DE' => 'German',
                        'FR' => 'French',
                        'ES' => 'Spanish',
                        'IT' => 'Italian',
                    ];

                    foreach ($languages as $code => $lang) {
                        echo "<li onclick=\"setLanguage('$code')\"><img src='./Images/icons/$code.svg' alt='$lang' class='language-icon'>   $lang</li>";
                    }

                    echo '</ul>
                </li>';
                    ?>
                </ul>
            </div>
        </div>
        <div class="nav-bar-karabast">
            Looking for <a href="https://karabast.net">new Karabast</a>?
        </div>
    </div>


    <script>
        function toggleLanguages() {
            var languageList = document.getElementById("languageList");
            if (languageList.style.display === "none" || languageList.style.display === "") {
                languageList.style.display = "block";
            } else {
                languageList.style.display = "none";
            }
        }

        function setLanguage(langCode) {
            console.log("Selected language: " + langCode); // Log the selected language
            document.cookie = "selectedLanguage=" + langCode + "; path=/";
            location.reload();
        }
    </script>
</body>