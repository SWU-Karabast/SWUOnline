<?php
$playerColors = file_get_contents("css/playerColors.css");
if ($playerID == 2) {
    $playerColors = str_replace("p1", "p#", $playerColors);
    $playerColors = str_replace("p2", "p1", $playerColors);
    $playerColors = str_replace("p#", "p2", $playerColors);
}
echo $playerColors;
?>
