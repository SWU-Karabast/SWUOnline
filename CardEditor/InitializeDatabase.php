<?php

include_once '../includes/dbh.inc.php';

$query = "CREATE TABLE carddata (cardID varchar(32) PRIMARY KEY, ";

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
$filename = "./GeneratedCode/EditCard.php";
$editorHandler = fopen($filename, "w");
fwrite($editorHandler, "<form action='CardCreateEdit.php' method='post'>" . "\r\n");
fwrite($editorHandler, "<label for='cardID'>Card ID:</label>" . "\r\n");
fwrite($editorHandler, "<input type='text' id='cardID' name='cardID'><br><br>" . "\r\n");
for($i=0; $i<count($columnArr); ++$i) {
  $column = explode(" ", $columnArr[$i]);
  
}
fwrite($editorHandler, "<button type='submit'>Submit</button>" . "\r\n");
fwrite($editorHandler, "</form>" . "\r\n");
fclose($editorHandler);
/*
  <label for="hasGoAgain">Has Go Again:</label>
  <select id="hasGoAgain" name="hasGoAgain">
    <option value="true" selected>True</option>
    <option value="false">False</option>
  </select><br><br>

  <label for="playAbility">Play Ability:</label>
  <textarea id="playAbility" name="playAbility" rows="4" cols="50"></textarea><br><br>
*/
  


?>
