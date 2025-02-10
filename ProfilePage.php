<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="./jsInclude.js"></script>
<?php
require "MenuBar.php";
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

include_once "CardDictionary.php";
include_once "./Libraries/UILibraries2.php";
include_once "./APIKeys/APIKeys.php";

  /*
  $badges = LoadBadges($_SESSION['userid']);
  echo ("<div class='ContentWindow' style='position:relative; width:50%; left:20px; top:20px; height:200px;'>");
  echo ("<h1>Your Badges</h1>");
  for ($i = 0; $i < count($badges); $i += 7) {
    $bottomText = str_replace("{0}", $badges[$i + 2], $badges[$i + 4]);
    $fullText = $badges[$i + 3] . "<br><br>" . $bottomText;
    if ($badges[$i + 6] != "") echo ("<a href='" . $badges[$i + 6] . "'>");
    echo ("<img style='margin:3px; width:120px; height:120px; object-fit: cover;' src='" . $badges[$i + 5] . "'></img>");
    if ($badges[$i + 6] != "") echo ("</a>");
  }
  echo ("</div>");
  */

// Calculate default dates
$startDate = date('Y-m-d', strtotime('-1 month'));
$endDate = date('Y-m-d');

?>

<script>
$(document).on('click', '#filterButton', function() {
    console.log("Filter button clicked!"); // Debugging line
    $.ajax({
        url: 'zzGameStats.php',
        type: 'GET',
        data: $('#filterForm').serialize(),
        success: function(data) {
            console.log("AJAX Response:", data); // Log the response data
            $('#statsContainer').html(data);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error: ' + status + ' ' + error);
        }
    });
});
</script>
<?php
include_once 'Header.php';
?>



<div id="cardDetail" style="z-index:100000; display:none; position:fixed;"></div>

<div class="core-wrapper">

<div class='fav-decks container bg-black'>
<div style="display:flex; justify-content:space-between;">
    <h2>Welcome <?php echo $_SESSION['useruid'] ?>!</h2>
    <a href="ChangeUsername.php">
        <button name="change-username" style="height: 40px">Change Username</button>
    </a>
    <a href="ChangePassword.php">
        <button name="change-password" style="height: 40px">Change Password</button>
    </a>
</div>
<?php
DisplayPatreon();

echo ("<h2>Favorite Decks</h2>");
$favoriteDecks = LoadFavoriteDecks($_SESSION["userid"]);
if (count($favoriteDecks) > 0) {
    echo ("<table>");
    echo ("<tr><td>Hero</td><td>Deck Name</td><td>Delete</td></tr>");
    for ($i = 0; $i < count($favoriteDecks); $i += 4) {
        echo ("<tr>");
        echo ("<td>" . CardLink($favoriteDecks[$i + 2], $favoriteDecks[$i + 2], true) . "</td>");
        echo ("<td>" . $favoriteDecks[$i + 1] . "</td>");
        echo ("<td><a style='text-underline-offset:5px;' href='./MenuFiles/DeleteDeck.php?decklink=" . $favoriteDecks[$i] . "'>Delete</a></td>");
        echo ("</tr>");
    }
    echo ("</table>");
}
?>
<h2>Block List</h2>
<form class="form-resetpwd" action="includes/BlockUser.php" method="post">
    <input class="block-input" type="text" name="userToBlock" placeholder="User to block">
    <button type="submit" name="block-user-submit">Block</button>
</form>
</div>

<div class='stats container bg-black'>
    <p>For stats tracking, build or import your deck to <a href="https://swustats.net" target="_blank">swustats.net</a> and use the swustats deck link to play on karabast.</p>
    <!--
<form id="filterForm">
    <input type="date" name="startDate" value="<?php echo $startDate; ?>">
    <input type="date" name="endDate" value="<?php echo $endDate; ?>">
    <button type="button" id="filterButton">Filter Stats</button>
</form>
  <?php
  echo ("<h2>Your Record</h2>");
  $forIndividual = true; ?>

 <div id="statsContainer">
    <p id="loadingMessage" style="display:none;">Loading stats...</p>
    <?php include "zzGameStats.php"; ?>
</div>
-->
</div>



</div>


<?php
function DisplayPatreon() {
    global $patreonClientID, $patreonClientSecret;
    $client_id = $patreonClientID;
    $client_secret = $patreonClientSecret;

    $redirect_uri = "https://www.karabast.net/SWUOnline/PatreonLogin.php";
    $href = 'https://www.patreon.com/oauth2/authorize?response_type=code&client_id=' . $client_id . '&redirect_uri=' . urlencode($redirect_uri);
    $state = array();
    $state['final_page'] = 'https://karabast.net/SWUOnline/MainMenu.php';
    $state_parameters = '&state=' . urlencode(base64_encode(json_encode($state)));
    $href .= $state_parameters;
    $scope_parameters = '&scope=identity%20identity.memberships';
    $href .= $scope_parameters;

    if (!isset($_SESSION["patreonAuthenticated"])) {
        echo '<a class="containerPatreon" href="' . $href . '">';
        echo ("<img class='imgPatreon' src='./Assets/patreon-php-master/assets/images/login_with_patreon.png' alt='Login via Patreon'>");
        echo '</a>';
    } else {
        include './zzPatreonDebug.php';
    }
}

require "Disclaimer.php";
?>
