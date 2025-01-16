<?php
$playerColors = <<<CSS
.p1-label {
    color: #228ef5;
}

.p2-label {
    color: #fb4242;
}
CSS;

if ($playerID == 2) {
    $playerColors = str_replace("p1", "p#", $playerColors);
    $playerColors = str_replace("p2", "p1", $playerColors);
    $playerColors = str_replace("p#", "p2", $playerColors);
}

echo $playerColors;
?>

