<?php

  include './zzImageConverter.php';
  include '../Libraries/Trie.php';

  $schemaFile = "./GameSchema.txt";
  $handler = fopen($schemaFile, "r");
  $rootName = trim(fgets($handler));

  while (!feof($handler)) {
    $line = fgets($handler);
    if ($line !== false) {
        // Process the line
        $line = str_replace(' ', '', $line);
        echo $line;
    }
  }

  fclose($handler);

  $rootPath = "./" . $rootName;
  if(!is_dir($rootPath)) mkdir($rootPath, 0755, true);
  exit;
  /*
  if(!is_dir($rootPath . "/WebpImages")) mkdir($rootPath . "/WebpImages", 0755, true);
  if(!is_dir($rootPath . "/concat")) mkdir($rootPath . "/concat", 0755, true);
  if(!is_dir($rootPath . "/crops")) mkdir($rootPath . "/crops", 0755, true);
  */

  for($i=0; $i<count($properties); ++$i) {
    $properties[$i] = trim($properties[$i]);
    ${$properties[$i]} = [];
  }

  //$subtitleTrie = [];

    $jsonUrl = "https://20lore.pro/api/";
    $curl = curl_init();
    $headers = array(
      "Content-Type: application/json",
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($curl, CURLOPT_URL, $jsonUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $apiData = curl_exec($curl);
    curl_close($curl);


    $response = json_decode($apiData);
    
    //$meta = $response->meta;

    for ($i = 0; $i < count($response->$cardArrayJson); ++$i)
    {
      echo("<BR>");
      $card = $response->$cardArrayJson[$i];
      for($j=0; $j<count($properties); ++$j) {
        array_push(${$properties[$j]}, $card->{$properties[$j]});
        echo(${$properties[$j]}[count(${$properties[$j]})-1] . " ");
      }
      echo("<BR>");
      
      $setNumber = $card->setId;
      if($setNumber < 10) $setNumber = "00" . $setNumber;
      else if($setNumber < 100) $setNumber = "0" . $setNumber;
      $cardId = $card->setCardId;
      if($cardId < 10) $cardId = "00" . $cardId;
      else if($cardId < 100) $cardId = "0" . $cardId;
      $cardID = $setNumber . "-" . $cardId;

      $imageUrl = "https://20lore.pro/images/" . $cardID . ".webp";
      echo($imageUrl . "<BR>");

      //$imageUrl = "https://swudb.com/cards/" . $set . "/" . $cardNumber . ".png";

      CheckImage($cardID, $imageUrl, "", "", rootPath:"./" . $rootName . "/");

      continue;
      if($card->artBack->data != null) {
        $type2 = $card->type2->data == null ? "" : $card->type2->data->attributes->name;
        if($type2 == "Leader Unit") $definedType = "Unit";
        $imageUrl = $card->artBack->data->attributes->formats->card->url;
        $arr = explode("_", $imageUrl);
        $arr = explode(".", $arr[count($arr)-1]);
        $uuid = $arr[0];
        CheckImage($uuid, $imageUrl, $definedType, isBack:true, set:$set);
        AddToTries($cardID, $uuid);
      }
    }

exit;
  if (!is_dir("./GeneratedCode")) mkdir("./GeneratedCode", 777, true);

  $generateFilename = "./GeneratedCode/GeneratedCardDictionaries.php";
  $handler = fopen($generateFilename, "w");

  fwrite($handler, "<?php\r\n");

  GenerateFunction($titleTrie, $handler, "CardTitle", true, "");
  GenerateFunction($subtitleTrie, $handler, "CardSubtitle", true, "");
  GenerateFunction($costTrie, $handler, "CardCost", false, -1);
  GenerateFunction($hpTrie, $handler, "CardHPDictionary", false, -1);
  GenerateFunction($powerTrie, $handler, "CardPower", false, -1);
  GenerateFunction($aspectsTrie, $handler, "CardAspects", true, "");
  GenerateFunction($traitsTrie, $handler, "CardTraits", true, "");
  GenerateFunction($arenasTrie, $handler, "CardArenas", true, "");
  GenerateFunction($typeTrie, $handler, "DefinedCardType", true, "");
  GenerateFunction($type2Trie, $handler, "DefinedCardType2", true, "");
  GenerateFunction($uniqueTrie, $handler, "CardIsUnique", false, 0);
  GenerateFunction($hasPlayTrie, $handler, "HasWhenPlayed", false, "false", 1);
  GenerateFunction($setTrie, $handler, "CardSet", true, "");
  GenerateFunction($uuidLookupTrie, $handler, "UUIDLookup", true, "");

  fwrite($handler, "?>");

  fclose($handler);


  $generateFilename = "./GeneratedCode/GeneratedCardDictionaries.js";
  $handler = fopen($generateFilename, "w");
  GenerateFunction($titleTrie, $handler, "CardTitle", true, "", language:"js");
  fclose($handler);

  function GenerateFunction($cardArray, $handler, $functionName, $isString, $defaultValue, $dataType = 0, $language = "PHP")
  {
    if($language == "PHP") fwrite($handler, "function " . $functionName . "(\$cardID) {\r\n");
    else if($language = "js") fwrite($handler, "function " . $functionName . "(cardID) {\r\n");
    TraverseTrie($cardArray, "", $handler, $isString, $defaultValue, $dataType, $language);
    fwrite($handler, "}\r\n\r\n");
  }

  function AddToTries($cardID, $uuid)
  {
    global $uuidLookupTrie, $titleTrie, $subtitleTrie, $costTrie, $hpTrie, $powerTrie, $typeTrie, $type2Trie, $uniqueTrie, $card;
    global $aspectsTrie, $traitsTrie, $arenasTrie, $hasPlayTrie, $setTrie;
    if($uuid != "8752877738" && $uuid != "2007868442") {
      AddToTrie($uuidLookupTrie, $cardID, 0, $uuid);
    }
    AddToTrie($titleTrie, $uuid, 0, str_replace('"', "'", $card->title));
    AddToTrie($subtitleTrie, $uuid, 0, str_replace('"', "'", $card->subtitle));
    AddToTrie($costTrie, $uuid, 0, $card->cost);
    AddToTrie($hpTrie, $uuid, 0, $card->hp);
    AddToTrie($powerTrie, $uuid, 0, $card->power);
    AddToTrie($typeTrie, $uuid, 0, $card->type->data->attributes->name);
    AddToTrie($setTrie, $uuid, 0, $card->expansion->data->attributes->code);
    if($card->type2->data != null) {
      $type2 = $card->type2->data->attributes->name;
      if($type2 == "Leader Unit") $type2 = "Unit";
      AddToTrie($type2Trie, $uuid, 0, $type2);
    }
    AddToTrie($uniqueTrie, $uuid, 0, $card->unique == "true" ? 1 : 0);
    if(str_contains($card->text, "When Played") || str_contains($card->text, "When played")) AddToTrie($hasPlayTrie, $uuid, 0, true);
    
    $aspects = "";
    for($j = 0; $j < count($card->aspects->data); ++$j)
    {
      if($aspects != "") $aspects .= ",";
      $aspects .= $card->aspects->data[$j]->attributes->name;
    }
    for($j = 0; $j < count($card->aspectDuplicates->data); ++$j)
    {
      if($aspects != "") $aspects .= ",";
      $aspects .= $card->aspectDuplicates->data[$j]->attributes->name;
    }
    AddToTrie($aspectsTrie, $uuid, 0, $aspects);

    $traits = "";
    for($j = 0; $j < count($card->traits->data); ++$j)
    {
      if($traits != "") $traits .= ",";
      $traits .= $card->traits->data[$j]->attributes->name;
    }
    AddToTrie($traitsTrie, $uuid, 0, $traits);

    $arenas = "";
    for($j = 0; $j < count($card->arenas->data); ++$j)
    {
      if($arenas != "") $arenas .= ",";
      $arenas .= $card->arenas->data[$j]->attributes->name;
    }
    AddToTrie($arenasTrie, $uuid, 0, $arenas);
  }

?>
