<?php

$servername = (!empty(getenv("MYSQL_SERVER_NAME")) ? getenv("MYSQL_SERVER_NAME") : "localhost");
$dBUsername = (!empty(getenv("MYSQL_SERVER_USER_NAME")) ? getenv("MYSQL_SERVER_USER_NAME") : "root");
$dBPassword = (!empty(getenv("MYSQL_ROOT_PASSWORD")) ? getenv("MYSQL_ROOT_PASSWORD") : "");
$dBName = "swuonline";
/*
$conn = GetDBConnection();

if (!$conn) {
	die("Connection failed: " . mysqli_connect_error());
}
*/

function GetDBConnection()
{
	global $servername, $dBUsername, $dBPassword, $dBName;
	try {
		$conn = mysqli_connect($servername, $dBUsername, $dBPassword, $dBName);
	} catch (\Exception $e) {
		$conn = false;
	}

	return $conn;
}
