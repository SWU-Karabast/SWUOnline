<?php

function TryGET($key, $default = "")
{
  return $_GET[$key] ?? $default;
}

function TryPOST($key, $default = "")
{
  return $_POST[$key] ?? $default;
}

function IsGameNameValid($gameName)
{
  return is_numeric($gameName);
}


?>