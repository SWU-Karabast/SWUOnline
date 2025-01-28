<?php

include "./Libraries/HTTPLibraries.php";
include_once './includes/functions.inc.php';
include_once "./includes/dbh.inc.php";
include "Libraries/SHMOPLibraries.php";

session_start();

if (!isset($_SESSION["useruid"])) {
  echo ("Please login to view this page.");
  exit;
}
$useruid = $_SESSION["useruid"];
if ($useruid != "OotTheMonk" && $useruid != "love") {
  echo ("You must log in to use this page.");
  exit;
}

$gameToken = TryGET("gameToClose", "");
$playerToBoot = TryGET("playerToBoot", "");

SetCachePiece($gameToken, $playerToBoot+3, 2);//internet connection status
SetCachePiece($gameToken, $playerToBoot+14, 3);//forced disconnect status

header("Location: ./zzModPage.php");


?>