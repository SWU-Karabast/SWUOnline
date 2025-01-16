<?php

  include './zzImageConverter.php';
  include './Libraries/Trie.php';

  $hasMoreData = true;
  $page = 1;
  $titleTrie = [];
  $subtitleTrie = [];
  $costTrie = [];
  $hpTrie = [];
  $powerTrie = [];
  $aspectsTrie = [];
  $traitsTrie = [];
  $arenasTrie = [];
  $uuidLookupTrie = [];
  $typeTrie = [];
  $type2Trie = [];
  $uniqueTrie = [];
  $hasPlayTrie = [];
  $hasDestroyedTrie = [];
  $setTrie = [];
  $cardIDTrie = [];
  while ($hasMoreData)
  {
    $jsonUrl = "https://admin.starwarsunlimited.com/api/cards?locale=en&pagination[page]=" . $page . "&pagination[pageSize]=100&filters[variantOf][id][\$null]=true";
    $curl = curl_init();
    $headers = array(
      "Content-Type: application/json",
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($curl, CURLOPT_URL, $jsonUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $cardData = curl_exec($curl);
    curl_close($curl);


    $response = json_decode($cardData);
    $meta = $response->meta;

    for ($i = 0; $i < count($response->data); ++$i)
    {
      $card = $response->data[$i];
      $card = $card->attributes;

      if($card->variantOf->data != null) continue;

      $cardNumber = $card->cardNumber;
      if($cardNumber < 10) $cardNumber = "00" . $cardNumber;
      else if($cardNumber < 100) $cardNumber = "0" . $cardNumber;
      $set = $card->expansion->data->attributes->code;
      $cardID= $set . "_" . $cardNumber;
      switch($card->cardUid) {
        case "3463348370"://Battle droid
          $cardID = "TWI_T01";
          break;
        case "3941784506"://Clone Trooper
          $cardID = "TWI_T02";
          break;
      }
      AddToTries($cardID, $card->cardUid);

      $definedType = $card->type->data->attributes->name;
      if($definedType == "Token Unit") $definedType = "Unit";
      $imageUrl = $card->artFront->data->attributes->formats->card->url;

      //$imageUrl = "https://swudb.com/cards/" . $set . "/" . $cardNumber . ".png";

      CheckImage($card->cardUid, $imageUrl, $definedType, set:$set);
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

    echo("Page: " . $meta->pagination->page . "/" . $meta->pagination->pageCount . "<BR>");
    ++$page;
    $hasMoreData = $page <= $meta->pagination->pageCount;
  }
  /*
  subtypes - array
  */


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
  GenerateFunction($hasDestroyedTrie, $handler, "HasWhenDestroyed", false, "false", 1);
  GenerateFunction($setTrie, $handler, "CardSet", true, "");
  GenerateFunction($uuidLookupTrie, $handler, "UUIDLookup", true, "");
  GenerateFunction($cardIDTrie, $handler, "CardIDLookup", true, "");

  fwrite($handler, "?>");

  fclose($handler);


  $generateFilename = "./GeneratedCode/GeneratedCardDictionaries.js";
  $handler = fopen($generateFilename, "w");
  GenerateFunction($titleTrie, $handler, "CardTitle", true, "", language:"js");
  fclose($handler);

  function GenerateFunction($cardArray, $handler, $functionName, $isString, $defaultValue, $dataType = 0, $language = "PHP")
  {
    if($language == "PHP") fwrite($handler, "function " . $functionName . "(\$cardID) {\r\n  if(strlen(\$cardID) < 6) return \"\";\r\n");
    else if($language = "js") fwrite($handler, "function " . $functionName . "(cardID) {\r\n");
    TraverseTrie($cardArray, "", $handler, $isString, $defaultValue, $dataType, $language);
    fwrite($handler, "}\r\n\r\n");
  }

  function AddToTries($cardID, $uuid)
  {
    global $uuidLookupTrie, $titleTrie, $subtitleTrie, $costTrie, $hpTrie, $powerTrie, $typeTrie, $type2Trie, $uniqueTrie, $card;
    global $aspectsTrie, $traitsTrie, $arenasTrie, $hasPlayTrie, $hasDestroyedTrie, $setTrie, $cardIDTrie;
    if($uuid != "8752877738" && $uuid != "2007868442") {
      AddToTrie($uuidLookupTrie, $cardID, 0, $uuid);
    }
    AddToTrie($titleTrie, $uuid, 0, str_replace('"', "'", $card->title));
    AddToTrie($subtitleTrie, $uuid, 0, str_replace('"', "'", $card->subtitle));
    AddToTrie($costTrie, $uuid, 0, $card->cost);
    $definedType = $card->type->data->attributes->name;
    if($definedType == "Token Unit") $definedType = "Unit";
    else if($definedType == "Token Upgrade") $definedType = "Upgrade";
    AddToTrie($typeTrie, $uuid, 0, $definedType);
    AddToTrie($hpTrie, $uuid, 0, $definedType == "Upgrade" ? $card->upgradeHp : $card->hp);
    AddToTrie($powerTrie, $uuid, 0, $definedType == "Upgrade" ? $card->upgradePower : $card->power);
    AddToTrie($setTrie, $uuid, 0, $card->expansion->data->attributes->code);
    AddToTrie($cardIDTrie, $uuid, 0, $cardID);
    if($card->type2->data != null) {
      $type2 = $card->type2->data->attributes->name;
      if($type2 == "Leader Unit") $type2 = "Unit";
      AddToTrie($type2Trie, $uuid, 0, $type2);
    }
    AddToTrie($uniqueTrie, $uuid, 0, $card->unique == "true" ? 1 : 0);
    if($card->text != null && (str_contains($card->text, "When Played") || str_contains($card->text, "When played"))) AddToTrie($hasPlayTrie, $uuid, 0, true);
    if($card->text != null && (str_contains($card->text, "When Defeated") || str_contains($card->text, "When defeated"))) AddToTrie($hasDestroyedTrie, $uuid, 0, true);

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
