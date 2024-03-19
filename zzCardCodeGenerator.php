<?php

  include './zzImageConverter.php';
  include './Libraries/Trie.php';

  $hasMoreData = true;
  $page = 1;
  $typeTrie = [];
  $classTrie = [];
  $subtypeTrie = [];
  $nameTrie = [];
  $nameToUUIDTrie = [];
  $elementTrie = [];
  $memoryCostTrie = [];
  $reserveCostTrie = [];
  $levelTrie = [];
  $powerTrie = [];
  $lifeTrie = [];
  $durabilityTrie = [];
  $speedTrie = [];
  $floatingMemoryTrie = [];
  $interceptTrie = [];
  while ($hasMoreData)
  {
    $jsonUrl = "https://api.gatcg.com/cards/search?name=&page=" . $page;
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


    for ($i = 0; $i < count($response->data); ++$i)
    {
      $card = $response->data[$i];

      echo($card->name . " " . $card->element . " " . $card->uuid . " " . $card->speed . "<BR>\n");

      AddToTrie($typeTrie, $card->uuid, 0, implode(",", $card->types));
      AddToTrie($classTrie, $card->uuid, 0, implode(",", $card->classes));
      AddToTrie($subtypeTrie, $card->uuid, 0, implode(",", $card->subtypes));
      AddToTrie($elementTrie, $card->uuid, 0, $card->element);
      AddToTrie($nameTrie, $card->uuid, 0, $card->name);
      AddToTrie($nameToUUIDTrie, strtolower($card->name) . ";", 0, $card->uuid);
      AddToTrie($memoryCostTrie, $card->uuid, 0, ($card->cost_memory == null ? -1 : $card->cost_memory));
      AddToTrie($reserveCostTrie, $card->uuid, 0, ($card->cost_reserve == null ? -1 : $card->cost_reserve));
      AddToTrie($levelTrie, $card->uuid, 0, ($card->level == null ? 0 : $card->level));
      AddToTrie($powerTrie, $card->uuid, 0, ($card->power === null ? -1 : $card->power));
      AddToTrie($lifeTrie, $card->uuid, 0, ($card->life == null ? -1 : $card->life));
      AddToTrie($durabilityTrie, $card->uuid, 0, ($card->durability == null ? -1 : $card->durability));
      AddToTrie($speedTrie, $card->uuid, 0, ($card->speed == null ? -1 : $card->speed));
      if (str_contains($card->effect, "Floating Memory")) AddToTrie($floatingMemoryTrie, $card->uuid, 0, $card->name);

      CheckImage($card->uuid);
    }

    echo("Page: " . $response->page . "<BR>");
    ++$page;
    $hasMoreData = $response->has_more;
  }
  /*
  subtypes - array
  */


  if (!is_dir("./GeneratedCode")) mkdir("./GeneratedCode", 777, true);

  $generateFilename = "./GeneratedCode/GeneratedCardDictionaries.php";
  $handler = fopen($generateFilename, "w");

  fwrite($handler, "<?php\r\n");

  GenerateFunction($typeTrie, $handler, "CardTypes", true, "");
  GenerateFunction($classTrie, $handler, "CardClasses", true, "");
  GenerateFunction($subtypeTrie, $handler, "CardSubTypes", true, "");
  GenerateFunction($elementTrie, $handler, "CardElement", true, "");
  GenerateFunction($nameTrie, $handler, "CardName", true, "");
  GenerateFunction($nameToUUIDTrie, $handler, "CardUUIDFromName", true, "");
  GenerateFunction($memoryCostTrie, $handler, "CardMemoryCost", false, -1);
  GenerateFunction($reserveCostTrie, $handler, "CardReserveCost", false, -1);
  GenerateFunction($levelTrie, $handler, "CardLevel", false, -1);
  GenerateFunction($powerTrie, $handler, "CardPower", false, -1);
  GenerateFunction($lifeTrie, $handler, "CardLife", false, -1);
  GenerateFunction($durabilityTrie, $handler, "CardDurability", false, -1);
  GenerateFunction($speedTrie, $handler, "CardSpeed", false, -1);
  GenerateFunction($floatingMemoryTrie, $handler, "HasFloatingMemory", false, false, 1);

  fwrite($handler, "?>");

  fclose($handler);

  function GenerateFunction($cardArray, $handler, $functionName, $isString, $defaultValue, $dataType = 0)
  {
    fwrite($handler, "function " . $functionName . "(\$cardID) {\r\n");
    TraverseTrie($cardArray, "", $handler, $isString, $defaultValue, $dataType);
    fwrite($handler, "}\r\n\r\n");
  }

?>
