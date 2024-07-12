<?php
  include_once 'MenuBar.php';

  include_once './includes/functions.inc.php';
  include_once "./includes/dbh.inc.php";
  
  if (!isset($_SESSION["useruid"])) {
    echo ("Please login to view this page.");
    exit;
  }
  $useruid = $_SESSION["useruid"];
  if ($useruid != "OotTheMonk" && $useruid != "love" && $useruid != "Cazargar") {
    echo ("You must log in to use this page.");
    exit;
  }
  $bugReport = $_GET["bugReport"];
  
  $filename = "./BugReports/" . $bugReport . "/beginTurnGamestate.txt";

  echo("<div style='background-color:rgba(0,0,0,.9); text-color:black; top:10%;left:3%; width:94%; height:85%; position:absolute; overflow-y:scroll;'>");

  echo(implode("<BR>", explode("\r\n", file_get_contents($filename))));

  echo("</div>");

?>