<?php


include_once 'Assets/patreon-php-master/src/OAuth.php';
include_once 'Assets/patreon-php-master/src/API.php';
include_once 'Assets/patreon-php-master/src/PatreonLibraries.php';
include_once 'Assets/patreon-php-master/src/PatreonDictionary.php';
include_once 'includes/functions.inc.php';
include_once 'includes/dbh.inc.php';
include_once 'Libraries/HTTPLibraries.php';
include_once 'HostFiles/Redirector.php';
include_once "CardDictionary.php";
include_once "./Libraries/UILibraries2.php";
include_once './includes/functions.inc.php';
include_once "./includes/dbh.inc.php";
include_once "./APIKeys/APIKeys.php";

if (!isset($_SESSION["userid"])) {
  if (isset($_COOKIE["rememberMeToken"])) {
    loginFromCookie();
  }
}

$isPatron = isset($_SESSION["isPatron"]);

$isMobile = IsMobile();

/*
To stop the menu bar header from showing up on refresh, had to put all of the above code in this page from menubar.php rather than just include it so it doesn't show up on refresh but the session is maintained. kept mobile and patron incase user clicks something where it matters from inside this subpage somehow
*/

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}

if(!isset($forIndividual)) $forIndividual = TryGet("forIndividual", default: true);
$forIndividual = (bool)$forIndividual;//If it evaluates to true, explicitly cast it to boolean
$useruid = $_SESSION["useruid"];
$userID = $_SESSION["userid"];
if (!$forIndividual) exit;

/*
pull the date range from the AJAX button on ProfilePage.php. The one month back default is there.
*/


if (isset($_GET['startDate']) && isset($_GET['endDate'])) {
  $startDate = $_GET['startDate'];
  $endDate = $_GET['endDate'];
}

/*
if ($forIndividual && !isset($_SESSION["isPatron"])) {
  echo ("Please subscribe to our Patreon to access this page.");
  exit;
}

We can probably remove numDays at this point but I just want to get everything working before I break it again
*/

$numDays = TryGet("numDays", 365);

echo ("<script src=\"./jsInclude250314.js\"></script>");

echo ("<style>

table {
  border-radius: 10px;
  border-spacing: 0;
  border-collapse: collapse;
  font-size: 1em;
  line-height: 24px;
  margin-left:auto;
  margin-right:auto;
}

td {
  border-bottom: 1px solid black;
  text-align: center;
  vertical-align: middle;
  height: 50px;
  padding: 10px;
  font-size:0.95em;
}

tr:hover {
  background-color: darkred;
}

h3 {
  text-align: center;
  font-size: 1.15em;
}
</style>");
echo ("<div id=\"cardDetail\" style=\"z-index:100000; display:none; position:fixed;\"></div>");

$winnerQuery = ($forIndividual ? "where WinningPID = ?" : "where WinningHero<>\"DUMMY\" and LosingHero<>\"DUMMY\" and CompletionTime >= DATE(NOW() - INTERVAL ? DAY)");
$loserQuery = ($forIndividual ? "where LosingPID = ?" : "where WinningHero<>\"DUMMY\" and LosingHero<>\"DUMMY\" and CompletionTime >= DATE(NOW() - INTERVAL ? DAY)");
$winnerQuery .= " and numTurns>1  AND (CAST(CompletionTime AS DATE) BETWEEN ? AND ?) ";
$loserQuery .= " and numTurns>1  AND (CAST(CompletionTime AS DATE) BETWEEN ? AND ?) ";


$sql = "SELECT Hero,sum(Count) AS Total FROM
(
select WinningHero As Hero,count(WinningHero) AS Count
from completedgame " . $winnerQuery . " group by WinningHero
union all
select LosingHero As Hero,count(LosingHero) AS Count
from completedgame
 $loserQuery
group by LosingHero
) AS internalQuery
GROUP BY Hero
ORDER BY Total DESC";
$conn = GetDBConnection();
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $sql)) {
  //header("location: ../Signup.php?error=stmtfailed");
  echo ("ERROR");
  exit();
}

$param = ($forIndividual ? $userID : $numDays);
mysqli_stmt_bind_param($stmt, "ssssss", $param, $startDate, $endDate, $param, $startDate, $endDate);
mysqli_stmt_execute($stmt);


// "Get result" returns the results from a prepared statement
$playData = mysqli_stmt_get_result($stmt);



$sql = "SELECT WinningHero,count(WinningHero) AS Count
FROM completedgame
 $winnerQuery
GROUP by WinningHero
ORDER BY Count";
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $sql)) {
  //header("location: ../Signup.php?error=stmtfailed");
  echo ("ERROR");
  exit();
}

$param = ($forIndividual ? $userID : $numDays);
mysqli_stmt_bind_param($stmt, "sss", $param, $startDate, $endDate);
mysqli_stmt_execute($stmt);

// "Get result" returns the results from a prepared statement
$winData = mysqli_stmt_get_result($stmt);

$blitzPlays = 0;
$ccPlays = 0;
$gameData = [];
while ($row = mysqli_fetch_array($playData, MYSQLI_NUM)) {
  $gameData[] = [];
  $index = count($gameData) - 1;
  $gameData[$index][0] = $row[0];
  $gameData[$index][1] = $row[1];
  $ccPlays += $row[1];
}
if (!empty($gameData)) {
  // Little user friendly warning code
} else {
  echo "No game data available for the selected dates.";
}

while ($row = mysqli_fetch_array($winData, MYSQLI_NUM)) {
  $heroID = $row[0];
  for ($i = 0; $i < count($gameData) && $gameData[$i][0] != $heroID; ++$i);
  $gameData[$i][] = $row[1];
}
mysqli_close($conn);

echo ("<div id='wrapper' style='text-align: center; position:relative;'>");

if(!$forIndividual) echo ("<section class='game-stats'>");
echo ("<div><table>");
echo ("<tr><td>Hero</td><td>Num Wins</td><td>Num Plays</td><td>Win %</td><td>Played %</td></tr>");

foreach ($gameData as $row) {
  //while ($row = mysqli_fetch_array($playData, MYSQLI_NUM)) {
  if(strlen($row[0]) < 8) continue;
  //if (CardType($row[0]) != "C") continue;
  $formatDenominator = $ccPlays;
  $winPercent = (((count($row) > 2 ? $row[2] : 0) / $row[1]) * 100);
  $playPercent = ($row[1] / $formatDenominator * 100);
  echo ("<tr>");
  if($forIndividual) echo ("<td><a href='./zzPlayerHeroStats.php?heroID=$row[0]'>" . CardName($row[0]) . "</a></td>");
  else echo ("<td><a href='./zzHeroStats.php?heroID=$row[0]'>" . CardName($row[0]) . "</a></td>");
  //echo ("<td>" . CardLink($row[0], $row[0], true) . "</td>");
  echo ("<td>" . (count($row) > 2 ? $row[2] : 0) . "</td>");
  echo ("<td>" . $row[1] . "</td>");
  echo ("<td>" . number_format($winPercent, 2, ".", "") . "% </td>");
  echo ("<td>" . number_format($playPercent, 2, ".", "") . "% </td>");
  echo ("</tr>");
}
echo ("</table>");


echo ("<BR>");
echo ("<div>");
if(!$forIndividual) echo ("</section>");
echo ("</div>");
echo ("</div>");
echo ("</div>");