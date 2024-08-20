<?php
  include_once './Lorcana/GamestateParser.php';
  include_once './Lorcana/ZoneAccessors.php';
  include_once './Lorcana/ZoneClasses.php';

  InitializeGamestate();

  $obj = new Deck("abc123");
  array_push($p1Deck, $obj);

  $obj = new Discard("def456");
  array_push($p1Discard, $obj);

  $obj = new Hand("ghi789");
  array_push($p1Hand, $obj);

  $obj = new Inkwell("jkl012 mno345");
  array_push($p1Inkwell, $obj);
  $obj = new Inkwell("adghsfh ahafh");
  array_push($p1Inkwell, $obj);
  
  $gameName = 1;

  WriteGamestate();

  ParseGamestate();

  WriteGamestate();

?>