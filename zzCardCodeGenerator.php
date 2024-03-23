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
  while ($hasMoreData)
  {
    $jsonUrl = "https://admin.starwarsunlimited.com/api/cards?pagination[page]=" . $page;
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
      $cardID = "SOR_" . $cardNumber;
      AddToTries($cardID, $card->cardUid);

      $aspects = "";
      for($j = 0; $j < count($card->aspects->data); ++$j)
      {
        if($aspects != "") $aspects .= ",";
        $aspects .= $card->aspects->data[$j]->attributes->name;
      }
      AddToTrie($aspectsTrie, $card->cardUid, 0, $aspects);

      $traits = "";
      for($j = 0; $j < count($card->traits->data); ++$j)
      {
        if($traits != "") $traits .= ",";
        $traits .= $card->traits->data[$j]->attributes->name;
      }
      AddToTrie($traitsTrie, $card->cardUid, 0, $traits);

      $arenas = "";
      for($j = 0; $j < count($card->arenas->data); ++$j)
      {
        if($arenas != "") $arenas .= ",";
        $arenas .= $card->arenas->data[$j]->attributes->name;
      }
      AddToTrie($arenasTrie, $card->cardUid, 0, $arenas);

      $imageUrl = $card->artFront->data->attributes->formats->card->url;
      CheckImage($card->cardUid, $imageUrl);
      if($card->artBack->data != null) {
        $imageUrl = $card->artBack->data->attributes->formats->card->url;
        $arr = explode("_", $imageUrl);
        $arr = explode(".", $arr[count($arr)-1]);
        $uuid = $arr[0];
        CheckImage($uuid, $imageUrl, isBack:true);
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
  GenerateFunction($hpTrie, $handler, "CardHP", false, -1);
  GenerateFunction($powerTrie, $handler, "CardPower", false, -1);
  GenerateFunction($aspectsTrie, $handler, "CardAspects", true, "");
  GenerateFunction($traitsTrie, $handler, "CardTraits", true, "");
  GenerateFunction($arenasTrie, $handler, "CardArenas", true, "");
  GenerateFunction($typeTrie, $handler, "DefinedCardType", true, "");
  GenerateFunction($type2Trie, $handler, "DefinedCardType2", true, "");

  GenerateFunction($uuidLookupTrie, $handler, "UUIDLookup", true, "");

  fwrite($handler, "?>");

  fclose($handler);

  function GenerateFunction($cardArray, $handler, $functionName, $isString, $defaultValue, $dataType = 0)
  {
    fwrite($handler, "function " . $functionName . "(\$cardID) {\r\n");
    TraverseTrie($cardArray, "", $handler, $isString, $defaultValue, $dataType);
    fwrite($handler, "}\r\n\r\n");
  }

  function AddToTries($cardID, $uuid)
  {
    global $uuidLookupTrie, $titleTrie, $subtitleTrie, $costTrie, $hpTrie, $powerTrie, $typeTrie, $type2Trie, $card;
    AddToTrie($uuidLookupTrie, $cardID, 0, $uuid);
    AddToTrie($titleTrie, $uuid, 0, str_replace('"', "'", $card->title));
    AddToTrie($subtitleTrie, $uuid, 0, str_replace('"', "'", $card->subtitle));
    AddToTrie($costTrie, $uuid, 0, $card->cost);
    AddToTrie($hpTrie, $uuid, 0, $card->hp);
    AddToTrie($powerTrie, $uuid, 0, $card->power);
    AddToTrie($typeTrie, $uuid, 0, $card->type->data->attributes->name);
    if($card->type2->data != null) AddToTrie($type2Trie, $uuid, 0, $card->type2->data->attributes->name);
  }

?>
