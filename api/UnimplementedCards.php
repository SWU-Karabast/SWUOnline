<?php
$data = [];

$files = scandir('../UnimplementedCards');
foreach($files as $file) {
    if($file == '.' || $file == '..') continue;
    $data[] = str_replace(".webp", "", $file);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
?>