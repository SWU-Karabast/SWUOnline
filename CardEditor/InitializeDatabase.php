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
echo($query);

$conn = GetDBConnection();
$stmt = mysqli_stmt_init($conn);
if (!mysqli_stmt_prepare($stmt, $query)) {
  echo("Unable to prepare MySql statement");
  exit;
}
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);
mysqli_close($conn);
//include_once "CardEditorDatabase.php";
//include_once '../GeneratedCode/DatabaseGeneratedCardDictionaries.php';

/*
$sets = ["WTR", "ARC", "CRU", "MON", "ELE", "EVR", "UPR", "DYN", "OUT", "DVR", "RVD", "DTD", "LGS", "HER", "FAB", "TCC", "EVO", "HVY"];

foreach($sets as &$set) {
    for($i=0; $i<800; ++$i) {
      $cardID = $set;
      if($i<100) $cardID .= "0";
      if($i<10) $cardID .= "0";
      $cardID .= $i;
      if(GeneratedGoAgain($cardID)) CreateEditCard($cardID, 1);
    }
}
    */

?>
