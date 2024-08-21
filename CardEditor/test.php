<?php
  include_once './Lorcana/GamestateParser.php';
  include_once './Lorcana/ZoneAccessors.php';
  include_once './Lorcana/ZoneClasses.php';

  InitializeGamestate();

  //Load the deck
  $decklist = "UmFwdW56ZWxfR2lmdGVkIHdpdGggSGVhbGluZyQ0fEFuZCBUaGVuIEFsb25nIENhbWUgWmV1cyQzfE1yLiBTbWVlX0J1bWJsaW5nIE1hdGUkM3xBbGFuLWEtRGFsZV9Sb2NraW4nIFJvb3N0ZXIkMnxQZXRlX0dhbWVzIFJlZmVyZWUkMnxQcmluY2UgTmF2ZWVuX1VrdWxlbGUgUGxheWVyJDJ8QSBXaG9sZSBOZXcgV29ybGQkNHxUaGUgQmFyZSBOZWNlc3NpdGllcyQyfEFyaWVsX1NwZWN0YWN1bGFyIFNpbmdlciQ0fFBlcmRpdGFfRGV2b3RlZCBNb3RoZXIkMnxEYWlzeSBEdWNrX0RvbmFsZCdzIERhdGUkM3xSb2JpbiBIb29kX0JlbG92ZWQgT3V0bGF3JDR8R3JhYiBZb3VyIFN3b3JkJDF8SSBGaW5kICdFbSwgSSBGbGF0dGVuICdFbSQxfENpbmRlcmVsbGFfQmFsbHJvb20gU2Vuc2F0aW9uJDR8U3RyZW5ndGggb2YgYSBSYWdpbmcgRmlyZSQ0fFJvYmluIEhvb2RfQ2hhbXBpb24gb2YgU2hlcndvb2QkNHxXb3JsZCdzIEdyZWF0ZXN0IENyaW1pbmFsIE1pbmQkMnxMYXdyZW5jZV9KZWFsb3VzIE1hbnNlcnZhbnQkMnxMZXQgdGhlIFN0b3JtIFJhZ2UgT24kM3xVcnN1bGFfVmFuZXNzYSQyfENpbmRlcmVsbGFfU3RvdXRoZWFydGVkJDJ8";

  $yourJsonObject = array(
    'decklistAsPbString' => $decklist
  );

  $formData = json_encode($yourJsonObject);

  $options = array(
    'http' => array(
      'header'  => "Content-type: application/json\r\n",
      'method'  => 'POST',
      'content' => $formData,
      'FormData' => $formData,
    ),
  );

  $context  = stream_context_create($options);
  $result = file_get_contents('https://20lore.pro/api/getDecklistFromPbString.php', false, $context);

  if ($result === FALSE) {
    // Handle error
  } else {
    echo($result);
    // Process the result
    // $result contains the response from the API
  }


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