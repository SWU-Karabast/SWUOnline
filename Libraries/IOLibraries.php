<?php

  function GetArray($handler)
  {
    if (!$handler)
      return false;
    $line = trim(fgets($handler));
    if ($line == "")
      return [];
    return explode(" ", $line);
  }

?>
