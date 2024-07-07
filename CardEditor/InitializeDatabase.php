<?php

include_once '../includes/dbh.inc.php';

$query = "CREATE TABLE carddata (";

$filename = "./CardSchema.txt";
$schemaContent = file_get_contents($filename);
$columnArr = explode("\r\n", $schemaContent);
for($i=0; $i<count($columnArr); ++$i) {
  $column = explode(" ", $columnArr[$i]);
  $query .= $column[0] . " " . $column[1];
  if($i<count($columnArr)-1) $query .= ", ";
}
$query .= ");";
echo("Query: " . $query . "<br>");

//Create the database table
$conn = GetDBConnection();
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, "DROP TABLE carddata;")) {
  echo("Unable to prepare drop table statement");
  exit;
}
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $query)) {
  echo("Unable to prepare create table statement");
  exit;
}
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conn);

//Now generate the card editor page
$filename = "./EditCard.php";


?>
