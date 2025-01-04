<?php

function LogPath($gameName, $path="./")
{
  return "{$path}Games/$gameName/gamelog.txt";
}

function CreateLog($gameName, $path="./")
{
  fclose(fopen(LogPath($gameName, $path), "w")); 
}

function WriteLog($text, $player = 0, $highlight=false, $path="./")
{
  global $gameName;

  if(!($handler = fopen(LogPath($gameName, $path), "a"))) {
    //File does not exist
    return;
  }
  
  $output = $highlight ? "<mark style='background-color: brown; color:azure;'>$text</mark>" : $text;
  $output = $player != 0 ? "<span class='player$player-label'>$output</span>" : $output;
  $output = "<p class='log-entry'>$output</p>";
  $output = $output . "\r\n";
  
  fwrite($handler, $output);
  fclose($handler);
}

function ClearLog($n=20)
{
  global $gameName;
  /*
  $filename = "./Games/" . $gameName . "/gamelog.txt";
  $handler = fopen($filename, "w");
  fclose($handler);
  */

  $filename = LogPath($gameName);
  $handle = fopen($filename, "r");
  $lines = array_fill(0, $n-1, '');
  if ($handle) {
    while (!feof($handle)) {
        $buffer = fgets($handle);
        $lines[] = $buffer;
        array_shift($lines);
    }
    fclose($handle);
  }

  $handle = fopen($filename, "w");
  fwrite($handle, implode("", $lines));
  fclose($handle);

}

function WriteError($text)
{
  WriteLog("ERROR: " . $text);
}

function EchoLog($gameName, $playerID)
{
  $filename = LogPath($gameName);
  $filesize = filesize($filename);
  if ($filesize > 0) {
    $handler = fopen($filename, "r");
    $line = fread($handler, $filesize);
    echo ($line);
    fclose($handler);
  }
}

function JSONLog($gameName, $playerID, $path="./")
{
  $response = "";
  $filename = LogPath($gameName, $path);
  $filesize = filesize($filename);
  if ($filesize > 0) {
    $handler = fopen($filename, "r");
    $line = str_replace("\r\n", "<br>", fread($handler, $filesize));
    //$line = str_replace("<PLAYER1COLOR>", $playerID==1 ? "Blue" : "Red", $line);
    //$line = str_replace("<PLAYER2COLOR>", $playerID==2 ? "Blue" : "Red", $line);
    $red = "#cb0202";
    $blue = "#128ee5";
    $line = str_replace("<PLAYER1COLOR>", $playerID == 1 || $playerID == 3 ? $blue : $red, $line);
    $line = str_replace("<PLAYER2COLOR>", $playerID == 2 ? $blue : $red, $line);
    $response = $line;
    fclose($handler);
  }
  return $response;
}
